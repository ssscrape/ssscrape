<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * SQLite database connection.
 *
 * Specify \c sqlite as the connection type when setting up the connection using
 * AnewtDatabase::setup_connection().
 *
 * The settings accepted by this backend are:
 *
 * - \c filename: The filename for the database.
 * - \c mode: The mode used to open the file. Defaults to \c 0666.
 *
 * If no filename is specified, an in-memory database is opened.
 *
 * \see AnewtDatabase
 * \see AnewtDatabaseConnection
 */
final class AnewtDatabaseConnectionSQLite extends AnewtDatabaseConnection
{
	public function __construct($settings)
	{
		$default_settings = array(
			'filename'   => ':memory:',
			'mode'       => 0666,
		);

		parent::__construct(array_merge($default_settings, $settings));
	}

	protected function real_connect()
	{
		$filename = $this->settings['filename'];
		$mode = $this->settings['mode'];

		$error = null;
		if ($this->settings['persistent'])
			$this->connection_handle = sqlite_popen($filename, $mode, $error);
		else
			$this->connection_handle = sqlite_open($filename, $mode, $error);

		if (!$this->connection_handle || $error)
			throw new AnewtDatabaseConnectionException($error);
	}

	protected function real_disconnect()
	{
		sqlite_close($this->connection_handle);
		$this->connection_handle = null;
	}

	public function is_connected()
	{
		return (bool) $this->connection_handle;
	}

	public function _result_set_for_query($sql)
	{
		$result_set_handle = sqlite_query($this->connection_handle, $sql);

		if (!$result_set_handle)
			throw new AnewtDatabaseQueryException(
				'SQLite error: %s',
				sqlite_error_string(sqlite_last_error($this->connection_handle)));

		return new AnewtDatabaseResultSetSQLite($sql, $this->connection_handle, $result_set_handle);
	}

	public function escape_string($value)
	{
		assert('is_string($value)');
		return sprintf("'%s'", sqlite_escape_string($value));
	}
}

/**
 * SQLite database result set.
 *
 * \see AnewtDatabaseResultSet
 */
class AnewtDatabaseResultSetSQLite extends AnewtDatabaseResultSet
{
	/* 
	 * The methods below implement/override AnewtDatabaseConnection methods.
	 */

	public function __construct($sql, $connection_handle, $result_set_handle)
	{
		parent::__construct($sql, $connection_handle, $result_set_handle);
		$this->n_rows = sqlite_num_rows($result_set_handle);
		$this->n_rows_affected = sqlite_changes($connection_handle);
	}


	/* Fetching */

	function fetch_one()
	{
		$row = sqlite_fetch_array($this->result_set_handle, SQLITE_ASSOC);

		if (!$row)
			return null;

		return $row;
	}

	function fetch_all()
	{
		$rows = sqlite_fetch_all($this->result_set_handle, SQLITE_ASSOC);
		return $rows;
	}


	/* Freeing */

	function free()
	{
		/* Do nothing; it seems there is way to free result sets in SQLite */
	}


	/* Type deducing and row casting */

	protected function obtain_field_types()
	{
		/* Do nothing; SQLite does not support data types */
	}

	protected function cast_row(&$row)
	{
		/* Do nothing; SQLite does not support data types */
	}
}

?>
