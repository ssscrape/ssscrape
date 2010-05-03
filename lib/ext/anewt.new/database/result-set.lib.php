<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Database result set.
 *
 * Result sets can be used to fetch or count rows returned by a database query.
 *
 * You cannot instantiate AnewtDatabaseResultSet instances directly, instances
 * of this class will be returned when an AnewtDatabasePreparedQuery is executed
 * using AnewtDatabasePreparedQuery::prepare().
 */
abstract class AnewtDatabaseResultSet
{
	/**
	 * The SQL query for this result set.
	 */
	protected $sql;

	/**
	 * The underlying database connection resource.
	 */
	protected $connection_handle;

	/**
	 * The underlying database result set resource.
	 */
	protected $result_set_handle;

	/**
	 * The number of rows.
	 */
	protected $n_rows;

	/**
	 * The number of affected rows.
	 */
	protected $n_rows_affected;

	/**
	 * The data types for the columns of this result set.
	 */
	protected $field_types;

	/**
	 * \private
	 *
	 * Construct a new AnewtDatabaseResultSet instance (internal use).
	 *
	 * Do not call this method directly; it is for internal use only.
	 *
	 * (Note to backend implementors: you should override this constructor, call
	 * into the parent, and then store the \c n_rows and \c n_rows_affected
	 * values)
	 *
	 * \param $sql                The SQL query for this result set
	 * \param $connection_handle  The internal connection handle
	 * \param $result_set_handle  The internal result set handle
	 *
	 * \see AnewtDatabaseConnection::prepare
	 */
	public function __construct($sql, $connection_handle, $result_set_handle)
	{
		$this->sql = $sql;
		$this->connection_handle = $connection_handle;
		$this->result_set_handle = $result_set_handle;

		/* Deduce column types for SELECT queries */
		if (AnewtDatabaseSQLTemplate::query_type_for_sql($sql) == ANEWT_DATABASE_SQL_QUERY_TYPE_SELECT)
			$this->obtain_field_types();
	}

	/**
	 * Free resources associated with this result set. You cannot use any
	 * methods on the result set instance anymore after calling this method.
	 */
	abstract function free();


	/** \{
	 * \name Row Fetching Methods
	 */

	/**
	 * Return the next row.
	 *
	 * \return
	 *   An associative array containing all fields of the next result row from
	 *   the result set.
	 *
	 * \see AnewtDatabaseResultSet::fetch_all
	 * \see AnewtDatabaseResultSet::fetch_many
	 */
	abstract function fetch_one();

	/**
	 * Return all remaining rows.
	 *
	 * \return
	 *   A numeric array containing the result rows as associative arrays.
	 *   This may be an empty list.
	 *
	 * \see AnewtDatabaseResultSet::fetch
	 * \see AnewtDatabaseResultSet::fetch_many
	 */
	function fetch_all()
	{
		$rows = array();

		while (true)
		{
			$row = $this->fetch_one();

			if (!$row)
				break;

			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Return up to the specified number of rows.
	 *
	 * The actual number of rows may be lower than the value specified, since
	 * there may be less rows in the set.
	 *
	 * \param $how_many
	 *   The number of rows to return. This must be a positive integer value.
	 *
	 * \return
	 *   A numeric array containing the result rows as associative arrays.
	 *   This may be an empty list.
	 *
	 * \see AnewtDatabaseResultSet::fetch
	 * \see AnewtDatabaseResultSet::fetch_all
	 */
	function fetch_many($how_many)
	{
		assert('is_int($how_many) && $how_many >= 1;');

		$rows = array();
		while ($num-- > 0)
		{
			$row = $this->fetch_one();

			if (!$row)
				break;

			$rows[] = $row;
		}
		return $rows;
	}

	/** \} */


	/** \{
	 * \name Row Counting Methods
	 */

	/**
	 * Return the number of resulting rows in this result set.
	 *
	 * \return
	 *   The total number of result rows.
	 */
	public function count()
	{
		return $this->n_rows;
	}

	/**
	 * Returns the number of rows that where affected by the last executed query.
	 *
	 * \return
	 *   The total number of affected rows.
	 */
	public function count_affected()
	{
		return $this->n_rows_affected;
	}

	/** \} */

	
	/** \{
	 * \name Helper Methods
	 */

	/**
	 * Deduce the data types in this result set.
	 *
	 * This method should be implemented in backends to perform automatic type
	 * conversion.
	 */
	abstract protected function obtain_field_types();

	/**
	 * \private
	 *
	 * Cast a row of data into native PHP data types.
	 *
	 * This method should be implemented in backends to perform automatic type
	 * conversion.
	 *
	 * Note that the array is modified in-place (and no result is returned).
	 *
	 * \param $row
	 *   A row of data.
	 */
	abstract protected function cast_row(&$row);
}

?>
