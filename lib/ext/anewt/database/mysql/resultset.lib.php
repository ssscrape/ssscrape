<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


anewt_include('datetime');


/**
 * MySQL-specific database result set.
 */
class MysqlResultSet extends ResultSet {

	var $backend;              /**< \private The backend instance */
	var $rs;                   /**< \private A resultset instance */
	var $data_types;           /**< \private The data types in this result set */
	var $completely_traversed; /**< \private Whether this result set has been completely traversed */
	var $num_rows;             /**< \private Total number of rows; only valid if completely_traversed is true */
	var $freed;                /**< \private Whether this result set has been freed */

	/**
	 * Constructs a new MysqlResultSet
	 *
	 * \param $sql The sql query to execute.
	 * \param $backend A reference to the used backend.
	 */
	function MysqlResultSet($sql, &$backend)
	{
		assert('is_string($sql)');

		$this->sql = $sql;
		$this->backend = &$backend;

		$this->completely_traversed = false;
		$this->freed = false;

		$this->rs = mysql_query($sql, $this->backend->id) or
			trigger_error(sprintf('Query failed (%d: %s)',
						mysql_errno($this->backend->id),
						mysql_error($this->backend->id)), E_USER_ERROR);


		/* Deduce column types for SELECT queries */

		if ($this->query_type() == ANEWT_DATABASE_SQL_QUERY_TYPE_SELECT)
			$this->deduce_types();
	}

	/**
	 * Free resources associated with this result set.
	 */
	function free()
	{
		if (!$this->freed && is_resource($this->rs))
			mysql_free_result($this->rs);

		$this->freed = true;
	}


	/* Fetching results */

	function fetch()
	{
		if ($this->completely_traversed)
			return false;

		$row = mysql_fetch_assoc($this->rs);

		/* No more rows? */
		if ($row === false)
		{
			$this->completely_traversed = true;
			$this->free();
			return false;
		}

		$this->num_rows++;
		$this->cast_row($row);
		return $row;
	}


	/* Result counting */

	function count()
	{
		/* mysql_num_rows() fails if the result set was freed, so we return the
		 * number of rows we fetched earlier */
		if ($this->completely_traversed)
			return $this->num_rows;

		return mysql_num_rows($this->rs);
	}

	function count_affected()
	{
		return mysql_affected_rows($this->backend->id);
	}


	/* Type casting */

	function deduce_types()
	{
		for ($i = 0; $i < mysql_num_fields($this->rs); $i++)
		{
			$name = mysql_field_name($this->rs, $i);
			$type = mysql_field_type($this->rs, $i);
			$this->data_types[$name] = $type;
		}
	}

	function cast_row(&$row)
	{
		assert('is_assoc_array($row)');

		foreach (array_keys($row) as $key)
		{
			$type = $this->data_types[$key];
			$value = &$row[$key];

			/* Don't cast null values */
			if (is_null($value))
				continue;

			switch ($type)
			{
				case 'int':
					$value = (int) $value;
					break;

				case 'real':
					$value = (float) $value;
					break;

				case 'string':
				case 'blob':
					$value = (string) $value;
					break;

				case 'date':
				case 'datetime':
				case 'time':
				case 'timestamp':
					$value = AnewtDateTime::parse_string($value);
					break;

				default:
					/* No conversion, leave as string */
					break;
			}

			$row[$key] = $value;
			unset($value);
		}
	}
}

?>
