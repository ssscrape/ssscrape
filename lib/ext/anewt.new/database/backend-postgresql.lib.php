<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * PostgreSQL database connection.
 *
 * Specify \c postgresql as the connection type when setting up the connection
 * using AnewtDatabase::setup_connection().
 *
 * The settings accepted by this backend are:
 *
 * - \c hostname The hostname
 * - \c username The username
 * - \c password The password
 * - \c database The name of the database
 * - \c encoding The character encoding (optional, defaults to <code>UNICODE</code>)
 *
 * \see AnewtDatabase
 * \see AnewtDatabaseConnection
 */
final class AnewtDatabaseConnectionPostgreSQL extends AnewtDatabaseConnection
{
	public function __construct($settings)
	{
		$default_settings = array(
			'encoding' => 'UNICODE',
		);

		parent::__construct(array_merge($default_settings, $settings));

		if (!array_key_exists('hostname', $this->settings))
			throw new AnewtDatabaseConnectionException('Missing hostname in PostgreSQL connection settings.');

		if (!array_key_exists('username', $this->settings))
			throw new AnewtDatabaseConnectionException('Missing username in PostgreSQL connection settings.');

		if (!array_key_exists('password', $this->settings))
			throw new AnewtDatabaseConnectionException('Missing password in PostgreSQL connection settings.');

		if (!array_key_exists('database', $this->settings))
			throw new AnewtDatabaseConnectionException('Missing database in PostgreSQL connection settings.');
	}

	protected function real_connect()
	{
		$hostname = $this->settings['hostname'];
		$username = $this->settings['username'];
		$password = $this->settings['password'];
		$database = $this->settings['database'];
		$encoding = $this->settings['encoding'];

		$connection_string = sprintf(
			'host=%s dbname=%s user=%s password=%s',
			addslashes($hostname),
			addslashes($database),
			addslashes($username),
			addslashes($password)
		);

		if ($this->settings['persistent'])
			$this->connection_handle = pg_pconnect($connection_string, PGSQL_CONNECT_FORCE_NEW);
		else
			$this->connection_handle = pg_connect($connection_string, PGSQL_CONNECT_FORCE_NEW);

		if (!$this->connection_handle)
			throw new AnewtDatabaseConnectionException('Could not connect to PostgreSQL database');

		if ($encoding)
			pg_set_client_encoding($this->connection_handle, $encoding);
	}

	protected function real_disconnect()
	{
		pg_close($this->connection_handle);
		$this->connection_handle = null;
	}

	public function is_connected()
	{
		return $this->connection_handle && pg_ping($this->connection_handle);
	}

	/**
	 * Get the last insert id for PostgreSQL by getting the current value of
	 * a sequence.
	 *
	 * \param $sequence
	 *   The name of the sequence.
	 */
	public function last_insert_id($sequence=null)
	{
		assert('is_string($sequence);');
		$row = $connection->prepare_execute_fetch_one(
				'SELECT currval(?string?) AS id',
				$sequence);
		return $row['id'];
	}

	function real_execute_sql($sql)
	{
		$result_set_handle = pg_query($this->connection_handle, $sql);

		if (!$result_set_handle)
			throw new AnewtDatabaseQueryException(
				'PostgreSQL error: %s',
				pg_last_error($this->connection_handle));

		return new AnewtDatabaseResultSetPostgreSQL($sql, $this->connection_handle, $result_set_handle);
	}

	/* Escaping */

	function escape_boolean($value)
	{
		assert('is_bool($value)');
		return $value ? 'true' : 'false';
	}

	function escape_string($value)
	{
		assert('is_string($value)');
		return sprintf("'%s'", pg_escape_string($value));
	}

	function escape_table_name($value)
	{
		assert('is_string($value)');

		$quote_char = '"';
		$parts = explode('.', $value);

		if (count($parts) === 1)
		{
			/* Add quotes */
			return sprintf('"%s"', $value);
		}
		else
		{
			/* Add quote only for the last part */
			$parts_quoted = array();
			foreach ($parts as $part)
				$parts_quoted[] = sprintf('"%s"', $part);

			return implode('.', $parts_quoted);
		}
	}

	function escape_column_name($value)
	{
		/* Same as escape_table_name */
		return $this->escape_table_name($value);
	}

}

/**
 * PostgreSQL database result set.
 *
 * \see AnewtDatabaseResultSet
 */
class AnewtDatabaseResultSetPostgreSQL extends AnewtDatabaseResultSet
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
		parent::__construct($sql, $connection_handle, $result_set_handle);
		$this->n_rows = pg_num_rows($result_set_handle);
		$this->n_rows_affected = pg_affected_rows($result_set_handle);
	}


	/* Fetching */

	function fetch_one()
	{
		if ($this->freed)
			return null;

		$row = pg_fetch_assoc($this->result_set_handle);

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

		pg_free_result($this->result_set_handle);
		$this->result_set_handle = null;
		$this->freed = true;
	}


	/* Type deducing and row casting */

	protected function obtain_field_types()
	{
		for ($i = 0; $i < pg_num_fields($this->result_set_handle); $i++)
		{
			$name = pg_field_name($this->result_set_handle, $i);
			$type = pg_field_type($this->result_set_handle, $i);
			$this->field_types[$name] = $type;
		}
	}

	protected function cast_row(&$row)
	{
		assert('is_assoc_array($row)');

		foreach ($row as $name => $value)
		{
			/* Don't cast null values */
			if (is_null($value))
				continue;

			switch ($this->field_types[$name])
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
				case 'timestamptz':
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

			$row[$name] = $value;
		}
	}
}

?>
