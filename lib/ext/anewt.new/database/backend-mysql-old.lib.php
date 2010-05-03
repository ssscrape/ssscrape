<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * MySQL database connection (old MYSQL versions).
 *
 * This backend uses the older PHP MySQL extension (not MySQLi). Specify
 * \c mysql-old as the connection type when setting up the connection using
 * AnewtDatabase::setup_connection().
 *
 * For newer MySQL versions you should use the AnewtDatabaseConnectionMySQL
 * backend instead.
 *
 * - \c hostname The hostname
 * - \c username The username
 * - \c password The password
 * - \c database The name of the database
 * - \c encoding The character encoding (optional, defaults to <code>UTF8</code>)
 *
 * \see AnewtDatabase
 * \see AnewtDatabaseConnection
 */
final class AnewtDatabaseConnectionMySQLOld extends AnewtDatabaseConnection
{
	public function __construct($settings)
	{
		$default_settings = array(
			'encoding' => 'UTF8',
		);

		parent::__construct(array_merge($default_settings, $settings));

		if (!array_key_exists('hostname', $this->settings))
			throw new AnewtDatabaseConnectionException('Missing hostname in MySQL connection settings.');

		if (!array_key_exists('username', $this->settings))
			throw new AnewtDatabaseConnectionException('Missing username in MySQL connection settings.');

		if (!array_key_exists('password', $this->settings))
			throw new AnewtDatabaseConnectionException('Missing password in MySQL connection settings.');

		if (!array_key_exists('database', $this->settings))
			throw new AnewtDatabaseConnectionException('Missing database in MySQL connection settings.');
	}

	protected function real_connect()
	{
		$hostname = $this->settings['hostname'];
		$username = $this->settings['username'];
		$password = $this->settings['password'];
		$database = $this->settings['database'];
		$encoding = $this->settings['encoding'];

		if ($this->settings['persistent'])
			$this->connection_handle = mysql_pconnect($hostname, $username, $password, true);
		else
			$this->connection_handle = mysql_connect($hostname, $username, $password, true);

		if (!$this->connection_handle)
			throw new AnewtDatabaseConnectionException('Could not connect to MySQL database');

		if (!mysql_select_db($database, $this->connection_handle))
			throw new AnewtDatabaseConnectionException('Could not select MySQL database "%s"', $database);

		if ($encoding)
			$this->prepare_execute('SET NAMES ?str?', $encoding);
	}

	protected function real_disconnect()
	{
		mysql_close($this->connection_handle);
		$this->connection_handle = null;
	}

	public function is_connected()
	{
		return $this->connection_handle && mysql_ping($this->connection_handle);
	}

	public function last_insert_id($options=null)
	{
		return mysql_insert_id($this->connection_handle);
	}

	function real_execute_sql($sql)
	{
		$result_set_handle = mysql_query($sql, $this->connection_handle);

		if (!$result_set_handle)
			throw new AnewtDatabaseQueryException(
				'MySQL error %d: %s',
				mysql_errno($this->connection_handle),
				mysql_error($this->connection_handle));

		return new AnewtDatabaseResultSetMySQLOld($sql, $this->connection_handle, $result_set_handle);
	}

	/* Escaping */

	function escape_string($value)
	{
		assert('is_string($value)');

		/* The mysql_real_escape_string(0 function depends on a valid
		 * connection, so it can only be used if $this->connection_handle is
		 * a mysql resource, otherwise mysql_escape_string() has to be used. The
		 * $this->connection_handle resource is null if the database connection
		 * is (not yet) established and AnewtDatabaseSQLTemplate is used to
		 * escape SQL strings. */

		if ($this->connection_handle)
			$value = mysql_real_escape_string($value, $this->connection_handle);
		else
			$value = mysql_escape_string($value);

		return sprintf("'%s'", $value);
	}

	function escape_table_name($value)
	{
		assert('is_string($value)');

		$parts = explode('.', $value);

		if (count($parts) == 1)
		{
			/* Add quotes */
			$out = sprintf('`%s`', $value);

		} else {
			/* Add quotes around each part */
			$result = array();
			foreach ($parts as $part)
				$result[] = sprintf('`%s`', $part);

			$out = implode('.', $result);
		}

		return $out;
	}

	function escape_column_name($value)
	{
		/* Same as escape_table_name */
		return $this->escape_table_name($value);
	}
}

/**
 * MySQL database result set.
 *
 * \see AnewtDatabaseResultSet
 */
class AnewtDatabaseResultSetMySQLOld extends AnewtDatabaseResultSet
{
	/**
	 * Whether the result set has been freed.
	 */
	private $freed = false;

	/* 
	 * The methods below implement/override AnewtDatabaseConnection methods.
	 */

	public function __construct($sql, $connection_handle, $result_set_handle)
	{
		if (is_resource($result_set_handle))
		{
			parent::__construct($sql, $connection_handle, $result_set_handle);
			$this->n_rows = mysql_num_rows($result_set_handle);
			$this->n_rows_affected = mysql_affected_rows($connection_handle);
		}
	}

	/* Fetching */

	function fetch_one()
	{
		if ($this->freed)
			return null;

		$row = mysql_fetch_assoc($this->result_set_handle);

		if (!$row)
		{
			$this->free();
			return null;
		}

		$this->cast_row($row);
		return $row;
	}


	/* Freeing */

	function free()
	{
		if (!$this->result_set_handle)
			return;

		mysql_free_result($this->result_set_handle);
		$this->result_set_handle = null;
		$this->freed = true;
	}

	/* Type deducing and row casting */

	protected function obtain_field_types()
	{
		for ($i = 0; $i < mysql_num_fields($this->result_set_handle); $i++)
		{
			$name = mysql_field_name($this->result_set_handle, $i);
			$type = mysql_field_type($this->result_set_handle, $i);
			$this->field_types[$name] = $type;
		}
	}

	protected function cast_row(&$row)
	{
		assert('is_assoc_array($row)');

		foreach ($row as $name => $value)
		{
			$type = $this->field_types[$name];

			/* Don't cast null values */
			if (is_null($value))
				continue;

			switch ($type)
			{
				case 'int':
					/* Issue: this doesn't work for BIGINTs on 32 bits
					 * platforms, but there is no way to find out when this
					 * happens (both have a 'int' field type). :(  The solution
					 * is to use MySQLi instead. */
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

			$row[$name] = $value;
		}
	}
}

?>
