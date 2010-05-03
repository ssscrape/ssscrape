<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Database connection wrapper with Memcache result set caching.
 *
 * AnewtDatabaseConnectionMemcache is a wrapper around another database
 * connection, providing a simple caching solution for \c SELECT queries. It is
 * not supposed to be a fully featured database caching solution, but designed
 * to work transparently in simple cases. Using AnewtDatabaseConnectionMemcache
 * does not require any code changes, except for the database connection step.
 * Just invoke AnewtDatabase::setup_connection() twice: one for the real
 * connection, one for the cached connection.
 *
 * Note that connect(), disconnect() and is_connected() only apply to the
 * Memcache server connection, and do not propagate to the underlying database
 * connection. Since connections to the Memcache server are only established if
 * required, it usually does not make sense to connect() and disconnect() the
 * connection manually.
 *
 * How does it work? The rows returned from a \c SELECT query executed against
 * a real database connection are temporarily stored in a Memcache cache. If the
 * same query is executed again within a certain timespan (see
 * <code>expiry</code> below), the exact same result is returned without hitting
 * the database again. Note that this means that changes to the database while
 * the data is in the cache are <strong>not immediately visible</strong>.
 *
 * Result row caching only works if you execute your \c SELECT queries through
 * one of these convenience functions:
 *
 * - prepare_execute_fetch_one()
 * - prepare_executev_fetch_one()
 * - prepare_execute_fetch_all()
 * - prepare_executev_fetch_all()
 *
 * Note that preparing and executing a query manually, i.e. by calling
 * AnewtDatabaseConnection::prepare(), executing the returned
 * AnewtDatabasePreparedQuery and then fetching rows with
 * AnewtDatabaseResultSet::fetch_one() (or other fetch functions) will
 * <strong>not</strong> result in caching!
 *
 * <strong>Important note:</strong> if you change the contents of the database
 * and want to see the results of e.g. \c INSERT queries reflected immediately
 * if you perform a \c SELECT query right after the \c INSERT, you should not
 * rely on AnewtDatabaseConnectionMemcache, but interact directly with the real
 * AnewtDatabaseConnection, e.g. AnewtDatabaseConnectionMySQL. You can use 
 * both the AnewtDatabaseConnectionMemcache connection and the underlying
 * AnewtDatabaseConnection at the same time. This way, you can opt to use
 * the cache for some of your \c SELECT queries (when delays are not an issue),
 * while you deliberately avoid it for other \c SELECT queries (if you want
 * changes to be immediately visible).
 *
 * Additionally, you can also delete all cached result sets by invoking
 * AnewtDatabaseConnectionMemcache::flush_cache() directly.
 *
 * The settings accepted by this backend are:
 *
 * - <code>connection</code>:  The existing AnewtDatabaseConnection to wrap
 * - <code>hostname</code>:    The hostname of the memcache server (optional,
 *                             defaults to <code>localhost</code>)
 * - <code>port</code>:        The port number of the memcache server (optional,
 *                             defaults to <code>11211</code>)
 * - <code>socket</code>:      Path to a Unix domain socket (optional). Instead
 *                             of a network connection, you can also provide
 *                             a path to a Unix socket where \c memcached
 *                             listens. If this is specified, \c hostname and \c
 *                             port are ignored.
 * - <code>expiry</code>:      The number of seconds to cache result sets
 *                             (optional, defaults to <code>600</code>)
 * - <code>identifier</code>:  An application-specific identifier (optional).
 *                             Use this to avoid collisions if two instances of
 *                             the same application use the same Memcache server
 *                             but you do not want them to share their cached
 *                             values. In this case, you should specify an \c
 *                             identifier value that is unique to this
 *                             application instance.
 * - <code>compression</code>: Whether to enable compression (optional, defaults
 *                             to <code>false</code>)
 *
 * \see AnewtDatabase
 * \see AnewtDatabaseConnection
 */
final class AnewtDatabaseConnectionMemcache extends AnewtDatabaseConnection
{
	/** The Memcache instance */
	private $memcache;

	/** Whether we are Memcache server is connected */
	private $memcache_connected;

	/** The number of Memcache hits */
	public $n_cache_hits = 0;

	/** The number of Memcache misses */
	public $n_cache_misses = 0;

	public function __construct($settings)
	{
		$default_settings = array(
			'connection'  => null,
			'hostname'    => 'localhost',
			'port'        => 11211,
			'socket'      => null,
			'expiry'      => 600,
			'identifier'  => 'anewt-database',
			'compression' => false,
		);

		parent::__construct(array_merge($default_settings, $settings));

		if (!$this->settings['connection'] instanceof AnewtDatabaseConnection)
			throw new AnewtDatabaseConnectionException('Connection is not a valid AnewtDatabaseConnection.');

		$this->connection_handle = $this->settings['connection'];
		$this->memcache = new Memcache();
	}

	/* Connection methods */

	protected function real_connect()
	{
		$socket = $this->settings['socket'];
		if (!is_null($socket))
		{
			$this->memcache_connected = $this->memcache->addServer(
				sprintf('unix://%s', $socket),
				0,
				$this->settings['persistent']
			);
		}
		else
		{
			$this->memcache_connected = $this->memcache->addServer(
				$this->settings['hostname'],
				$this->settings['port'],
				$this->settings['persistent']
			);
		}
	}

	protected function real_disconnect()
	{
		$this->memcache_connected = !$this->memcache->close(); /* Yes, negated */
	}

	public function is_connected()
	{
		return $this->memcache_connected;
	}

	public function last_insert_id($options=null)
	{
		return $this->connection_handle->last_insert_id($options);
	}

	function real_execute_sql($sql)
	{
		return $this->connection_handle->execute_sql($sql);
	}


	/* Caching query methods */

	/**
	 * Construct a key to be used as a memcache key.
	 *
	 * \param $sql       The SQL query
	 * \param $values    The values for the SQL query
	 * \param $all_rows  Whether this key is intended for single row or multiple
	 *                   row caching (boolean)
	 *
	 * \return  A key that can be used as a memcache key.
	 */
	private function build_key($sql, $values, $all_rows)
	{
		return sprintf(
			'%s-%s-%s',
			$this->settings['identifier'],
			md5($sql . serialize($values)),
			$all_rows ? 'all' : 'one'
		);
	}

	public function prepare_executev_fetch_one($sql, $values=null)
	{
		$key = null;
		$store_in_cache = false;

		if (AnewtDatabaseSQLTemplate::query_type_for_sql($sql) == ANEWT_DATABASE_SQL_QUERY_TYPE_SELECT)
		{
			$key = $this->build_key($sql, $values, false);
			$row = $this->memcache->get($key);

			if ($row === false)
			{
				$this->n_cache_misses++;
				$store_in_cache = true;
			}
			else
			{
				$this->n_cache_hits++;
				return $row;
			}
		}

		$row = $this->connection_handle->prepare_executev_fetch_one($sql, $values);

		if ($store_in_cache)
			$this->memcache->set(
				$key,
				$row,
				$this->settings['compression'] ? MEMCACHE_COMPRESSED : 0,
				$this->settings['expiry']
			);

		return $row;
	}

	public function prepare_executev_fetch_all($sql, $values=null)
	{
		$key = null;
		$store_in_cache = false;

		if (AnewtDatabaseSQLTemplate::query_type_for_sql($sql) == ANEWT_DATABASE_SQL_QUERY_TYPE_SELECT)
		{
			$key = $this->build_key($sql, $values, true);
			$rows = $this->memcache->get($key);

			if ($rows === false)
			{
				$this->n_cache_misses++;
				$store_in_cache = true;
			}
			else
			{
				$this->n_cache_hits++;
				return $rows;
			}
		}

		$rows = $this->connection_handle->prepare_executev_fetch_all($sql, $values);

		if ($store_in_cache)
			$this->memcache->set(
				$key,
				$rows,
				$this->settings['compression'] ? MEMCACHE_COMPRESSED : 0,
				$this->settings['expiry']
			);

		return $rows;
	}

	/**
	 * Flush the contents of the query cache.
	 *
	 * This method can be used to flush the contents of the query cache. Note
	 * that this will <strong>delete all cached data</strong> from the Memcache
	 * server, i.e. including any data that has not been set from this
	 * connection!
	 */
	public function flush_cache()
	{
		$this->memcache->flush();
	}


	/* Escaping methods */

	function escape_boolean($value)
	{
		return $this->connection_handle->escape_boolean($value);
	}

	function escape_string($value)
	{
		return $this->connection_handle->escape_string($value);
	}

	function escape_table_name($value)
	{
		return $this->connection_handle->escape_table_name($value);
	}

	function escape_column_name($value)
	{
		return $this->connection_handle->escape_column_name($value);
	}

	function escape_date($value)
	{
		return $this->connection_handle->escape_date($value);
	}

	function escape_time($value)
	{
		return $this->connection_handle->escape_time($value);
	}

	function escape_datetime($value)
	{
		return $this->connection_handle->escape_datetime($value);
	}
}

?>
