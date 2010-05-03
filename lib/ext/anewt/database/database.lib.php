<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * Copyright (C) 2004-2006  Wouter Bolsterlee <uws@xs4all.nl>
 * Copyright (C) 2004-2005  Jasper Looije <jasper@jamu.nl>
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


anewt_include('database/backend');
anewt_include('database/sqltemplate');
anewt_include('database/preparedquery');
anewt_include('database/resultset');


/**
 * Database connection class.
 *
 * This class is used to represent a database connection. It support multiple
 * backends.
 *
 * This class provides a get_instance convenience method that eases development
 * with just one database connection (as is the case with almost all
 * applications).
 */
class DB
{
	/* Static methods */

	/**
	 * Obtain a database instance. This method is modeled after the singleton
	 * design pattern, but you will need to initialize it with the connection
	 * settings. The first call to this method will initialize the database with
	 * the provided settings. Subsequent files return a reference to the
	 * database instance (and paramaters are ignored).
	 *
	 * \param $type
	 *   The type of database to be used.
	 *
	 * \param $settings
	 *   An associative array of connection settings.
	 *
	 * \see DB
	 * \see DB::connect
	 */
	static function get_instance($type=null, $settings=null)
	{
		static $db;

		if (is_null($db))
		{
			if (is_null($type))
				trigger_error('Cannot create instance of DB, no type is specified.', E_USER_ERROR);

			$db = new DB($type);

			if (!is_null($settings))
				$db->settings = $settings;
		}

		return $db;
	}


	/* Instance variables and methods */

	var $type;                         /**< \private Type of database being used */
	var $backend;                      /**< \private Database backend instance */
	var $databasename;                 /**< \private Current database name */
	var $connected;                    /**< \private Boolean indication the connection status */
	var $last_query;                   /**< \private Last query executed */
	var $num_queries_executed = 0;     /**< \private Counter for the number of queries executed */
	var $settings;                     /**< \private Settings for DB::setup($settings) */


	/* Debugging */
	var $debug = false;                /**< \private Enable/disable debugging */
	var $debug_print = false;          /**< \private Print queries before execution */
	var $queries_executed = array();   /**< \private List of executed queries (only used
										 if debugging is on)*/

	/**
	 * Constructor initalizes the database layer.
	 *
	 * \param $type
	 *   The type of database to be used (e.g. \c mysql or \c sqlite).
	 *
	 * \param $settings
	 *   Optional argument to be passed to connect().
	 *
	 * \see connect
	 */
	function DB($type='mysql', $settings=null)
	{
		assert('is_string($type)');

		/* Initialize the backend */
		anewt_include(sprintf('database/%s/backend', $type));
		$dbtype = ucfirst(strtolower($type)) . 'DB';
		$this->backend = new $dbtype();
		$this->backend->db = $this;
		$this->type = $type;

		/* Initial connection */
		$this->connected = false;
		if (!is_null($settings))
		{
			$settings['type'] = $type;
			$this->connect($settings);
		}
	}

	/**
	 * Prepares the database layer for deferred connection.
	 *
	 * \param $settings
	 *   Associative array of connection settings.
	 */
	function setup($settings)
	{
		assert('is_assoc_array($settings)');
		$dbtype = array_get_default($settings, 'type', 'mysql');
		$db = DB::get_instance($dbtype, $settings);
		return $db;
	}
	
	/**
	 * Connects to the database using the specified connection settings.
	 *
	 * \param $settings
	 *   An associative array of connection settings. Set the \c debug and
	 *   \c debug_print keys to true if you want to debug your queries.
	 */
	function connect($settings=null)
	{
		/* Connect only once */
		if (!$this->connected)
		{
			if (is_null($settings))
			{
				if (is_null($this->settings))
					trigger_error('Cannot connect to database, no settings are specified.', E_USER_ERROR);

				$settings = $this->settings;
			}
			assert('is_assoc_array($settings)');
			$this->backend->connect($settings);
			$this->connected = true;
			$this->debug = array_get_default($settings, 'debug', false);
			$this->debug_print = array_get_default($settings, 'debug_print', false);
			unset($this->settings);
		}
	}

	/**
	 * Disconnects from the database. Not calling this method is harmless for
	 * most database backends.
	 */
	function disconnect()
	{
		if ($this->connected)
		{
			$this->backend->disconnect();
			$this->connected = false;
		}
	}

	/**
	 * Selects the database to be used
	 *
	 * \param $name
	 *   The database name.
	 */
	function select_db($name)
	{
		assert('is_string($name)');
		$this->backend->select_db($name);
		$this->databasename = $name;
	}

	/** \{
	 * \name Query methods
	 */

	/**
	 * Prepares a query for execution.
	 *
	 * \param $sql
	 *   The SQL query to be prepared. Use <code>?fieldtype?</code> strings.
	 *
	 * \return
	 *   A new PreparedQuery instance that can be executed.
	 */
	function prepare($sql)
	{
		is_string($sql) && (strlen($sql)>0)
			or trigger_error('DB::prepare() needs a string parameter',
					E_USER_ERROR);

		$pq = new PreparedQuery($sql, $this);
		$pq->debug = $this->debug;
		$pq->debug_print = $this->debug_print;
		return $pq;
	}

	/**
	 * Convenience method to quickly execute a single query and fetch all
	 * resulting rows from the result set. If you want to use a query multiple
	 * times, you should obtain a PreparedQuery object by calling the prepare()
	 * method, execute it and operate on the ResultSet object.
	 *
	 * \param $sql
	 *   The SQL query to execute. Placeholders (e.g. <code>?int?</code>) should be used.
	 *
	 * \param $params
	 *   Zero or more parameters to be filled in in the $query. The number of
	 *   parameters should match the number of placeholders in $query
	 *
	 * \see DB::prepare_execute
	 * \see DB::prepare_execute_fetch
	 * \see DB::prepare
	 * \see PreparedQuery
	 * \see ResultSet::fetch_all
	 */
	function prepare_execute_fetch_all($sql, $params=null)
	{
		$args = func_get_args();
		assert('count($args) >= 1');
		array_shift($args); // remove first argument (sql query)

		if ((count($args) == 1) && (is_array($args[0]) || $args[0] instanceof Container))
			$args = $args[0];

		$pq = $this->prepare($sql);
		$rs = $pq->execute($args);
		return $rs->fetch_all();
	}

	/**
	 * Convenience method to quickly execute a single query and fetch one
	 * resulting row from the result set. See DB::prepare_execute_fetch_all for
	 * more information.
	 *
	 * \param
	 *   $sql The SQL query to execute.
	 *
	 * \param
	 *   $params Zero or more parameters to be filled in in the $query.
	 *
	 * \see DB::prepare_execute
	 * \see DB::prepare_execute_fetch_all
	 * \see DB::prepare
	 * \see PreparedQuery
	 * \see ResultSet::fetch_all
	 */
	function prepare_execute_fetch($sql, $params=null)
	{
		$args = func_get_args();
		assert('count($args) >= 1');
		array_shift($args); // remove first argument (sql query)

		if ((count($args) == 1) && (is_array($args[0]) || $args[0] instanceof Container))
			$args = $args[0];

		$pq = $this->prepare($sql);
		$rs = $pq->execute($args);
		$row = $rs->fetch();
		$rs->free();
		return $row;
	}

	/**
	 * Convenience method to quickly execute a single query. No results are
	 * retrieved. This method is pretty useless for SELECT queries, the number
	 * of affected rows is returned instead of result rows. See
	 * DB::prepare_execute_fetch_all for more information.
	 *
	 * \param $sql
	 *   The SQL query to execute.
	 *
	 * \param $params
	 *   Zero or more parameters to be filled in in the $query.
	 *
	 * \return
	 *   The number of rows affected by the query. This only works for queries
	 *   that operate on a number of rows, i.e. \c INSERT, \c UPDATE, \c
	 *   REPLACE, and \c DELETE queries. For other query types \c null is
	 *   returned.
	 *
	 * \see DB::prepare_execute_fetch
	 * \see DB::prepare_execute_fetch_all
	 * \see DB::prepare
	 * \see PreparedQuery
	 * \see ResultSet::fetch_all
	 */
	function prepare_execute($sql, $params=null)
	{
		$args = func_get_args();
		assert('count($args) >= 1');
		array_shift($args); // remove first argument (sql query)

		if ((count($args) == 1) && (is_array($args[0]) || $args[0] instanceof Container))
			$args = $args[0];

		$pq = $this->prepare($sql);
		$rs = $pq->execute($args);

		$query_type = $rs->query_type();
		$ret = null;
		switch ($query_type)
		{
			case ANEWT_DATABASE_SQL_QUERY_TYPE_INSERT:
			case ANEWT_DATABASE_SQL_QUERY_TYPE_UPDATE:
			case ANEWT_DATABASE_SQL_QUERY_TYPE_REPLACE:
			case ANEWT_DATABASE_SQL_QUERY_TYPE_DELETE:
				$ret = $rs->count_affected();
				break;

			default:
				/* Return nothing */
				break;
		}
		$rs->free();
		return $ret;
	}

	/** \} */

	/** \{
	 * \name Transaction methods
	 */

	/**
	 * Starts a transaction. This method calls into the backend.
	 */
	function transaction_begin()
	{
		$this->backend->transaction_begin();
	}

	/**
	 * Commits a transaction. This method calls into the backend.
	 */
	function transaction_commit()
	{
		$this->backend->transaction_commit();
	}

	/**
	 * Rolls back a transaction. This method calls into the backend.
	 */
	function transaction_rollback()
	{
		$this->backend->transaction_rollback();
	}

	/** \} */
}

?>
