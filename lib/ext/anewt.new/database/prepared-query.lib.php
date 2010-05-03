<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Prepared query class.
 *
 * This class represents a database query (optionally with placeholders) that
 * has not been executed yet.
 *
 * To obtain an AnewtDatabasePreparedQuery instance, use
 * AnewtDatabaseConnection::prepare(). In many cases the other query methods in
 * AnewtDatabaseConnection suffice, so it is not likely you will be dealing with
 * prepared queries directly.
 *
 * To execute the prepared query, use either
 * AnewtDatabasePreparedQuery::execute()
 * or AnewtDatabasePreparedQuery::executev(), depending on how you want to pass
 * the parameters to be filled in in the query template. See
 * AnewtDatabaseSQLTemplate for more information.
 */
class AnewtDatabasePreparedQuery
{
	/**
	 * \private
	 *
	 * The associated AnewtDatabaseConnection.
	 */
	var $connection;

	/**
	 * \private
	 *
	 * The associated AnewtDatabaseSQLTemplate.
	 */
	var $sql_template;

	/**
	 * \private
	 *
	 * Construct a new AnewtDatabasePreparedQuery.
	 *
	 * Don't use this method directly: use $connection->prepare() instead.
	 *
	 * \param $sql
	 *   SQL query template with <code>?int?</code> style placeholders.
	 *
	 * \param $connection
	 *   An AnewtDatabaseConnection instance
	 *
	 * \see AnewtDatabaseConnection
	 * \see AnewtDatabaseSQLTemplate
	 */
	function __construct($sql, $connection)
	{
		assert('is_string($sql)');
		assert('$connection instanceof AnewtDatabaseConnection');

		$this->connection = $connection;
		$this->sql_template = new AnewtDatabaseSQLTemplate($sql, $connection);
	}

	/**
	 * Execute the query using the values passed as multiple parameters.
	 *
	 * \param $values
	 *   Zero or more values to be substituted for the placeholders.
	 *
	 * \return
	 *   An AnewtDatabaseResultSet instance.
	 *
	 * \see AnewtDatabasePreparedQuery::executev
	 * \see AnewtDatabaseSQLTemplate::fill()
	 */
	public function execute($values=null)
	{
		$values = func_get_args();
		return $this->executev($values);
	}

	/**
	 * Execute the query using the values passed as a single parameter.
	 *
	 * \param $values
	 *   An array of values to be substituted for the placeholders.
	 *
	 * \return
	 *   An AnewtDatabaseResultSet instance.
	 *
	 * \see AnewtDatabasePreparedQuery::execute
	 * \see AnewtDatabaseSQLTemplate::fill()
	 */
	public function executev($values=null)
	{
		$this->connection->connect();
		$sql = $this->sql_template->fillv($values);
		$result_set = $this->connection->execute_sql($sql);
		return $result_set;
	}
}

?>
