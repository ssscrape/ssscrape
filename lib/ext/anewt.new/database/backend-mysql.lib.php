<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * MySQL database connection.
 *
 * This backend uses the PHP MySQLi extension. Specify \c mysql as the
 * connection type when setting up the connection using
 * AnewtDatabase::setup_connection().
 *
 * Older MySQL versions are supported using the old MySQL backend. See
 * AnewtDatabaseConnectionMySQLOld for more information.
 *
 * The settings accepted by this backend are:
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
final class AnewtDatabaseConnectionMySQL extends AnewtDatabaseConnection
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


		/* Persistent connection are only available since PHP 5.3 */

		if (version_compare(PHP_VERSION, '5.3.0', '>='))
			$hostname = sprintf('p:%s', $hostname);


		/* Create the connection */

		try
		{
			$this->connection_handle = new MySQLi($hostname, $username, $password, $database);
		}
		catch (Exception $e)
		{
			throw new AnewtDatabaseConnectionException('Could not connect to MySQL database: %s', $e->getMessage());
		}


		/* Set the encoding */

		if ($encoding)
		{
			try
			{
				$charset_success = $this->connection_handle->set_charset($encoding);

				/* Issue SET NAMES query as a fallback only */
				if (!$charset_success)
					$this->prepare_execute('SET NAMES ?str?', $encoding);
			}
			catch (Exception $e)
			{
				throw new AnewtDatabaseConnectionException('Could not set MySQL encoding to "%s": %s', $encoding, $e->getMessage());
			}
		}
	}

	protected function real_disconnect()
	{
		$this->connection_handle->close();
		$this->connection_handle = null;
	}

	public function is_connected()
	{
		return $this->connection_handle && $this->connection_handle->ping();
	}

	public function last_insert_id($options=null)
	{
		return $this->connection_handle->insert_id;
	}

	function real_execute_sql($sql)
	{
		$result_set_handle = $this->connection_handle->query($sql);

		if (!$result_set_handle)
			throw new AnewtDatabaseQueryException(
				'MySQL error %d: %s',
				$this->connection_handle->errno,
				$this->connection_handle->error);

		return new AnewtDatabaseResultSetMySQL($sql, $this->connection_handle, $result_set_handle);
	}

	/* Escaping */

	function escape_string($value)
	{
		assert('is_string($value)');
		$value = $this->connection_handle->escape_string($value);
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
class AnewtDatabaseResultSetMySQL extends AnewtDatabaseResultSet
{
	/* 
	 * The methods below implement/override AnewtDatabaseConnection methods.
	 */

	public function __construct($sql, $connection_handle, $result_set_handle)
	{
		if ($result_set_handle instanceof MySQLi_Result)
		{
			parent::__construct($sql, $connection_handle, $result_set_handle);
			$this->n_rows = $result_set_handle->num_rows;
			$this->n_rows_affected = $connection_handle->affected_rows;
		}
	}

	/* Fetching */

	function fetch_one()
	{
		if (!$this->result_set_handle)
			return null;

		$row = $this->result_set_handle->fetch_assoc();

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

		$this->result_set_handle->free();
		$this->result_set_handle = null;
	}

	/* Type deducing and row casting */

	protected function obtain_field_types()
	{
		$fields = $this->result_set_handle->fetch_fields();

		foreach ($fields as $field)
			$this->field_types[$field->name] = $field->type;
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
				case MYSQLI_TYPE_DECIMAL:
				case MYSQLI_TYPE_NEWDECIMAL:
				case MYSQLI_TYPE_BIT:
				case MYSQLI_TYPE_TINY:
				case MYSQLI_TYPE_SHORT:
				case MYSQLI_TYPE_LONG:
				case MYSQLI_TYPE_INT24:
				case MYSQLI_TYPE_YEAR:
					$value = (int) $value;
					break;

				case MYSQLI_TYPE_LONGLONG:
					/* Only cast BIGINTs on 64 bit platforms that can actually
					   hold the values in an integer data type. */
					if (PHP_INT_SIZE >= 8)
						$value = (int) $value;

					break;

				case MYSQLI_TYPE_FLOAT:
				case MYSQLI_TYPE_DOUBLE:
					$value = (float) $value;
					break;

				case MYSQLI_TYPE_TINY_BLOB:
				case MYSQLI_TYPE_MEDIUM_BLOB:
				case MYSQLI_TYPE_LONG_BLOB:
				case MYSQLI_TYPE_BLOB:
				case MYSQLI_TYPE_VAR_STRING:
				case MYSQLI_TYPE_STRING:
				case MYSQLI_TYPE_CHAR:
					$value = (string) $value;
					break;

				case MYSQLI_TYPE_TIMESTAMP:
				case MYSQLI_TYPE_DATE:
				case MYSQLI_TYPE_TIME:
				case MYSQLI_TYPE_DATETIME:
				case MYSQLI_TYPE_NEWDATE:
					$value = AnewtDateTime::parse_string($value);
					break;

				case MYSQLI_TYPE_ENUM:
				case MYSQLI_TYPE_INTERVAL:
				case MYSQLI_TYPE_ENUM:
				case MYSQLI_TYPE_SET:
				case MYSQLI_TYPE_GEOMETRY:
					/* XXX: Fall-through: what should be done with these? */

				default:
					/* No conversion, leave as string */
					break;
			}

			$row[$name] = $value;
		}
	}
}

?>
