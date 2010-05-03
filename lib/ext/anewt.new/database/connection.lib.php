<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Abstract database connection class.
 *
 * This class is the base class for database connections, and is subclassed by
 * specific database backends. AnewtDatabaseConnection instances cannot be
 * created directly; see AnewtDatabase on how to setup and obtain
 * AnewtDatabaseConnection instances.
 *
 * The settings accepted by all backends are:
 *
 * - <code>persistent</code>: Whether a persistent connection should be made.
 *   This defaults to true.
 *
 * See the documentation for the backend-specific AnewtDatabaseConnection
 * subclasses for more information:
 *
 * - AnewtDatabaseConnectionMySQL
 * - AnewtDatabaseConnectionMySQLOld (for older MySQL versions)
 * - AnewtDatabaseConnectionPostgreSQL
 * - AnewtDatabaseConnectionSQLite
 *
 * \see AnewtDatabase
 */
abstract class AnewtDatabaseConnection
{
	/**
	 * Connection settings.
	 */
	protected $settings;

	/**
	 * The underlying database connection resource.
	 */
	protected $connection_handle;

	/**
	 * The number of executed queries.
	 *
	 * This is mostly useful for debugging.
	 */
	public $n_queries_executed;

	/**
	 * List of executed queries.
	 *
	 * This is mostly useful for debugging. Note that only a certain number of
	 * queries are kept here before the first query is no longer kept around
	 * (ring buffer), hence the list may not be complete.
	 */
	public $queries_executed;

	/**
	 * \private
	 *
	 * Create a new connection instance (internal use only).
	 *
	 * Do not call this method directly; it is for internal use only.
	 * See the AnewtDatabase documentation on how to setup and obtain
	 * AnewtDatabaseConnection instances.
	 *
	 * \param $settings
	 *   Associative array with connection settings.
	 */
	public function __construct($settings)
	{
		assert('is_assoc_array($settings);');

		$this->n_queries_executed = 0;
		$this->queries_executed = array();

		$default_settings = array(
			'persistent' => true,
		);

		$this->settings = array_merge($default_settings, $settings);
	}

	/** \{
	 * \name Connection methods
	 *
	 * These methods can be used to connect and disconnect from the database.
	 * Note that, by default, database connections automatically connect when
	 * they are setup using AnewtDatabase::setup_connection(). If you do not
	 * want this, set the \c autoconnect property to \c false when setting up
	 * the connection.
	 */

	/**
	 * Establish a database connection.
	 *
	 * This function does nothing if the connection has been established
	 * already.
	 */
	public function connect()
	{
		if ($this->is_connected())
			return;

		$this->real_connect();
	}

	/**
	 * Disconnect a database connection.
	 *
	 * This function does nothing if the connection has been disconnected
	 * already.
	 */
	public function disconnect()
	{
		if (!$this->is_connected())
			return;

		$this->real_disconnect();
	}

	/**
	 * Check whether the connection is currently open.
	 *
	 * \return
	 *   \c True if the connection is open, \c false otherwise.
	 */
	abstract public function is_connected();

	/**
	 * \private
	 *
	 * Establish a database connection.
	 */
	abstract protected function real_connect();

	/**
	 * \private
	 *
	 * Disconnect the database connection.
	 */
	abstract protected function real_disconnect();

	/** \} */


	/** \{
	 * \name Query methods
	 *
	 * These methods provide a rich API to execute queries and retrieve
	 * resulting rows. The basic functionality to prepare a query that can later
	 * be executed is provided by prepare(), but several convenience methods are
	 * available that cover the most frequent use cases, e.g. fetching all rows
	 * from a result set.
	 *
	 * To build complex SQL queries, while still using the extensive type
	 * checking this module offers, you can use create_sql_template() to obtain
	 * an AnewtDatabaseSQLTemplate instance that you can fill manually.
	 */

	/**
	 * Prepare a query for execution.
	 *
	 * This method can be used to execute the same query more than once, e.g.
	 * each with different values for the placeholders.
	 *
	 * To obtain a result row (or all result rows) returned by a query, it is
	 * easier to use AnewtDatabaseConnection::prepare_execute_fetch or one of
	 * the other variants.
	 *
	 * \param $sql     The SQL query to be prepared (optionally with placeholders)
	 *
	 * \return
	 *   A new AnewtDatabasePreparedQuery instance.
	 *
	 * \see AnewtDatabasePreparedQuery
	 * \see AnewtDatabaseResultSet
	 */
	public function prepare($sql)
	{
		assert('is_string($sql);');
		return new AnewtDatabasePreparedQuery($sql, $this);
	}

	/**
	 * Execute a query using the values passed as multiple parameters, without
	 * retrieving resulting rows.
	 *
	 * See AnewtDatabaseConnection::prepare_executev for a detailed description.
	 *
	 * \param $sql     The SQL query to be prepared (optionally with placeholders)
	 * \param $values  Zero or more values to be substituted for the placeholders
	 *
	 * \see AnewtDatabaseConnection::prepare_executev
	 * \see AnewtDatabaseConnection::prepare_execute_fetch_one
	 * \see AnewtDatabaseConnection::prepare_execute_fetch_all
	 */
	public function prepare_execute($sql, $values=null)
	{
		$values = func_get_args();
		assert('count($values) >= 1; // At least an SQL query must be provided. ');
		$sql = array_shift($values);
		return $this->prepare_executev($sql, $values);
	}

	/**
	 * Execute a query using the values passed as a single parameter, without
	 * retrieving resulting rows.
	 *
	 * For some query types the number of affected rows is returned. This only
	 * works for queries that operate on a number of rows, i.e. \c INSERT, \c
	 * UPDATE, \c REPLACE, and \c DELETE queries. For other query types \c null
	 * is returned.
	 *
	 * Note that this method is mostly useless for \c SELECT queries since it
	 * will not return any results; use
	 * AnewtDatabaseConnection::prepare_execute_fetch or
	 * AnewtDatabaseConnection::prepare_execute_fetch_all if you want to
	 * retrieve result rows.
	 *
	 * \param $sql     The SQL query to be prepared (optionally with placeholders)
	 * \param $values  Zero or more values to be substituted for the placeholders
	 *
	 * \return
	 *   The number of rows affected by the query.
	 *
	 * \see AnewtDatabaseConnection::prepare_execute
	 * \see AnewtDatabaseConnection::prepare_executev_fetch_one
	 * \see AnewtDatabaseConnection::prepare_executev_fetch_all
	 */
	public function prepare_executev($sql, $values=null)
	{
		assert('is_string($sql);');

		$pq = $this->prepare($sql);
		$rs = $pq->executev($values);

		$out = null;
		switch (AnewtDatabaseSQLTemplate::query_type_for_sql($sql))
		{
			case ANEWT_DATABASE_SQL_QUERY_TYPE_INSERT:
			case ANEWT_DATABASE_SQL_QUERY_TYPE_UPDATE:
			case ANEWT_DATABASE_SQL_QUERY_TYPE_REPLACE:
			case ANEWT_DATABASE_SQL_QUERY_TYPE_DELETE:
				$out = $rs->count_affected();
				break;

			default:
				/* Do nothing */
				break;
		}

		$rs->free();

		return $out;
	}

	/**
	 * Execute a query using the values passed as multiple parameters, and fetch
	 * the first row.
	 *
	 * \param $sql     The SQL query to be prepared (optionally with placeholders)
	 * \param $values  Zero or more values to be substituted for the placeholders
	 * \return         A single row, or \c NULL
	 *
	 * \see AnewtDatabaseConnection::prepare_executev_fetch_one
	 * \see AnewtDatabaseConnection::prepare_execute
	 * \see AnewtDatabaseConnection::prepare_execute_fetch_all
	 */
	public function prepare_execute_fetch_one($sql, $values=null)
	{
		$values = func_get_args();
		assert('count($values) >= 1; // At least an SQL query must be provided. ');
		$sql = array_shift($values);
		return $this->prepare_executev_fetch_one($sql, $values);
	}

	/**
	 * Execute a query using the values passed as a single parameter, and fetch
	 * the first row.
	 *
	 * \param $sql     The SQL query to be prepared (optionally with placeholders)
	 * \param $values  Zero or more values to be substituted for the placeholders
	 * \return         A single row, or \c NULL
	 *
	 * \see AnewtDatabaseConnection::prepare_execute_fetch_one
	 * \see AnewtDatabaseConnection::prepare_executev
	 * \see AnewtDatabaseConnection::prepare_executev_fetch_all
	 */
	public function prepare_executev_fetch_one($sql, $values=null)
	{
		$pq = $this->prepare($sql);
		$rs = $pq->executev($values);
		$row = $rs->fetch_one();
		$rs->free();
		return $row;
	}

	/**
	 * Execute a query using the values passed as multiple parameters, and fetch
	 * all rows.
	 *
	 * \param $sql     The SQL query to be prepared (optionally with placeholders)
	 * \param $values  Zero or more values to be substituted for the placeholders
	 * \return         Array of all rows (may be empty)
	 *
	 * \see AnewtDatabaseConnection::prepare_executev_fetch_all
	 * \see AnewtDatabaseConnection::prepare_execute
	 * \see AnewtDatabaseConnection::prepare_execute_fetch_one
	 */
	public function prepare_execute_fetch_all($sql, $values=null)
	{
		$values = func_get_args();
		assert('count($values) >= 1; // At least an SQL query must be provided. ');
		$sql = array_shift($values);
		return $this->prepare_executev_fetch_all($sql, $values);
	}

	/**
	 * Execute a query using the values passed as a single parameter, and fetch
	 * all rows.
	 *
	 * \param $sql     The SQL query to be prepared (optionally with placeholders)
	 * \param $values  Zero or more values to be substituted for the placeholders
	 * \return         Array of all rows (may be empty)
	 *
	 * \see AnewtDatabaseConnection::prepare_execute_fetch_all
	 * \see AnewtDatabaseConnection::prepare_executev
	 * \see AnewtDatabaseConnection::prepare_executev_fetch_one
	 */
	public function prepare_executev_fetch_all($sql, $values=null)
	{
		$pq = $this->prepare($sql);
		$rs = $pq->executev($values);
		return $rs->fetch_all();
	}

	/**
	 * Return an AnewtDatabaseSQLTemplate for this connection.
	 *
	 * \param $sql     The SQL query to be prepared (optionally with placeholders)
	 *
	 * \return
	 *   A new AnewtDatabaseSQLTemplate instance.
	 */
	public function create_sql_template($sql)
	{
		return new AnewtDatabaseSQLTemplate($sql, $this);
	}

	/**
	 * Get the last insert id for this connection.
	 *
	 * The \c $options parameter is database backend specific. For most backends
	 * this value is not needed. At least PostgreSQL needs a sequence name. See
	 * the backend documentation for more information.
	 *
	 * \param $options
	 *   Backend-specific value.
	 */
	abstract public function last_insert_id($options=null);

	/**
	 * \private
	 *
	 * Execute SQL and create an AnewtDatabaseResultSet.
	 *
	 * This method is for internal use only. Use one of the prepare() methods to
	 * prepare and execute queries.
	 *
	 * \param $sql
	 *   The SQL query to execute.
	 *
	 * \return
	 *   A new AnewtDatabaseResultSet instance.
	 */
	function execute_sql($sql)
	{
		$this->n_queries_executed ++;

		/* There is a maximum on the number of queries kept in the list to avoid
		 * memory issues with long running scripts that execute many queries. */
		if (count($this->queries_executed) >= 100)
			array_shift($this->queries_executed);

		$this->queries_executed[] = $sql;

		return $this->real_execute_sql($sql);
	}

	/**
	 * Execute SQL and create an AnewtDatabaseResultSet for a query.
	 * 
	 * This method is for internal use only and is backend-specific.
	 *
	 * \param $sql
	 *   The SQL query to execute.
	 */
	abstract protected function real_execute_sql($sql);

	/** \} */


	/** \{
	 * \name Transaction Methods
	 */

	/**
	 * Start a transaction.
	 */
	public function transaction_begin()
	{
		$this->prepare_execute('BEGIN;');
	}

	/**
	 * Commit a transaction.
	 */
	public function transaction_commit()
	{
		$this->prepare_execute('COMMIT;');
	}


	/**
	 * Roll back a transaction.
	 */
	public function transaction_rollback()
	{
		$this->prepare_execute('ROLLBACK;');
	}

	/** \} */


	/** \{
	 * \name Escaping Methods
	 *
	 * The implementations of these methods in AnewtDatabaseConnection are
	 * generic. Some of those methods are overridden in the database backend
	 * specific subclasses.
	 *
	 * Note that it is often not the right approach to invoke these methods
	 * yourself when building complex SQL queries. Creating an
	 * AnewtDatabaseSQLTemplate with create_sql_template() and filling that
	 * instead results in code that is much cleaner and more easy to read.
	 */

	/**
	 * Escape a boolean for use in SQL queries.
	 *
	 * \param $value  The value to escape
	 * \return        The escaped value
	 */
	function escape_boolean($value)
	{
		assert('is_bool($value)');
		return $value ? '1' : '0';
	}

	/**
	 * Escape a string for use in SQL queries.
	 *
	 * \param $value  The value to escape
	 * \return        The escaped value
	 */
	function escape_string($value)
	{
		assert('is_string($value)');
		return sprintf("'%s'", addslashes($value));
	}

	/**
	 * Escape a table name for use in SQL queries.
	 *
	 * \param $value  The value to escape
	 * \return        The escaped value
	 */
	function escape_table_name($value)
	{
		assert('is_string($value)');
		return $value;
	}

	/**
	 * Escape a column name for use in SQL queries.
	 *
	 * \param $value  The value to escape
	 * \return        The escaped value
	 */
	function escape_column_name($value)
	{
		assert('is_string($value)');
		return $value;
	}

	/**
	 * Escape a date for use in SQL queries.
	 *
	 * This method merely adds quotes.
	 *
	 * \param $value  The value to escape
	 * \return        The escaped value
	 */
	function escape_date($value)
	{
		assert('is_string($value)');
		return sprintf("'%s'", $value);
	}

	/**
	 * Escape a time for use in SQL queries.
	 *
	 * This method merely adds quotes.
	 *
	 * \param $value  The value to escape
	 * \return        The escaped value
	 */
	function escape_time($value)
	{
		assert('is_string($value)');
		return sprintf("'%s'", $value);
	}

	/**
	 * Escape a datetime for use in SQL queries.
	 *
	 * This method merely adds quotes.
	 *
	 * \param $value  The value to escape
	 * \return        The escaped value
	 */
	function escape_datetime($value)
	{
		assert('is_string($value)');
		return sprintf("'%s'", $value);
	}

	/** \} */
}

?>
