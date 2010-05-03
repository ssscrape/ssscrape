<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * Copyright (C) 2006  Wouter Bolsterlee <uws@xs4all.nl>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA+
 */


anewt_include('datetime');


/**
 * PostgreSQL-specific database result set.
 */
class PostgreSQLResultSet extends ResultSet
{
	var $backend;    /**< \private The backend instance */
	var $rs;         /**< \private A resultset instance */
	var $data_types; /**< \private The data types in this result set */
	var $freed;      /**< \private Whether this result set has been freed */

	/**
	 * Constructs a new PostgreSQLResultSet
	 *
	 * \param $sql The sql query to execute.
	 * \param $backend A reference to the used backend.
	 */
	function PostgreSQLResultSet($sql, &$backend)
	{
		assert('is_string($sql)');

		$this->sql = $sql;
		$this->backend = &$backend;

		$this->freed = false;

		$this->rs = pg_query($this->backend->id, $sql) or
			trigger_error(sprintf('Query failed (%s)',
						pg_last_error($this->backend->id)), E_USER_ERROR);


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
			pg_free_result($this->rs);

		$this->freed = true;
	}


	function fetch()
	{
		$row = pg_fetch_assoc($this->rs);

		/* No more rows? */
		if ($row === false)
		{
			$this->free();
			return false;
		}

		$this->cast_row($row);
		return $row;
	}

	function count()
	{
		return pg_num_rows($this->rs);
	}

	function count_affected()
	{
		return pg_affected_rows($this->rs);
	}

	/**
	 * \private Deduces the data types in this result set. This uses information
	 * about the resultset as provided by the database.
	 *
	 * \see PostgreSQLResultSet::cast_types
	 */
	function deduce_types()
	{
		for ($i = 0; $i < pg_num_fields($this->rs); $i++)
		{
			$name = pg_field_name($this->rs, $i);
			$type = pg_field_type($this->rs, $i);
			$this->data_types[$name] = $type;
		}
	}

	/**
	 * \private Casts a row of data into native PHP data types. The array is
	 * modified in-place and no result is returned.
	 *
	 * \param $row
	 *   A row of data.
	 *
	 * \see PostgreSQLResultSet::deduce_types
	 */
	function cast_row(&$row)
	{
		assert('is_assoc_array($row)');

		foreach (array_keys($row) as $key)
		{
			$type = $this->data_types[$key];
			$value = $row[$key];

			/* Don't cast null values */
			if (is_null($value))
				continue;

			switch ($type)
			{
				case 'int2':
				case 'int4':
				case 'int8':
					$value = (int) $value;
					break;

				case 'float4':
				case 'float8':
				case 'numeric':
				case 'money':
					$value = (float) $value;
					break;

				case 'varchar':
				case 'bpchar':
					$value = (string) $value;
					break;

				case 'bool':
					$value = ($value === 't');
					break;

				case 'timestamp':
				case 'date':
				case 'time':
				case 'datetime':
					$value = AnewtDateTime::parse_string($value);
					break;

				case 'inet':
					/* FIXME: What to do with these? */

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
