<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */

/* Query types */

mkenum(
		/* Data Manipulation Language (DML) */
		'ANEWT_DATABASE_SQL_QUERY_TYPE_SELECT',
		'ANEWT_DATABASE_SQL_QUERY_TYPE_INSERT',
		'ANEWT_DATABASE_SQL_QUERY_TYPE_UPDATE',
		'ANEWT_DATABASE_SQL_QUERY_TYPE_DELETE',
		'ANEWT_DATABASE_SQL_QUERY_TYPE_REPLACE',

		/* Data Definition Language (DDL) */
		'ANEWT_DATABASE_SQL_QUERY_TYPE_CREATE',
		'ANEWT_DATABASE_SQL_QUERY_TYPE_ALTER',
		'ANEWT_DATABASE_SQL_QUERY_TYPE_DROP',

		/* Transactions */
		'ANEWT_DATABASE_SQL_QUERY_TYPE_BEGIN',
		'ANEWT_DATABASE_SQL_QUERY_TYPE_COMMIT',
		'ANEWT_DATABASE_SQL_QUERY_TYPE_ROLLBACK',

		/* Unknown */
		'ANEWT_DATABASE_SQL_QUERY_TYPE_UNKNOWN'
		);


/* Column types */

mkenum(
		/* Boolean */
		'ANEWT_DATABASE_SQL_FIELD_TYPE_BOOLEAN',

		/* Numeric */
		'ANEWT_DATABASE_SQL_FIELD_TYPE_INTEGER',
		'ANEWT_DATABASE_SQL_FIELD_TYPE_FLOAT',

		/* String */
		'ANEWT_DATABASE_SQL_FIELD_TYPE_STRING',

		/* Dates and times */
		'ANEWT_DATABASE_SQL_FIELD_TYPE_DATE',
		'ANEWT_DATABASE_SQL_FIELD_TYPE_TIME',
		'ANEWT_DATABASE_SQL_FIELD_TYPE_DATETIME',
		'ANEWT_DATABASE_SQL_FIELD_TYPE_TIMESTAMP',

		/* Raw */
		'ANEWT_DATABASE_SQL_FIELD_TYPE_RAW',

		/* SQL internals */
		'ANEWT_DATABASE_SQL_FIELD_TYPE_COLUMN',
		'ANEWT_DATABASE_SQL_FIELD_TYPE_TABLE'
);


/* Exceptions */

/** Generic database exception */
class AnewtDatabaseException extends AnewtException {}

/** Database configuration exception */
class AnewtDatabaseConfigurationException extends AnewtDatabaseException {}

/** Database connection exception */
class AnewtDatabaseConnectionException extends AnewtDatabaseException {}

/** Database query exception */
class AnewtDatabaseQueryException extends AnewtDatabaseException {}


/**
 * Static database connectivity support.
 *
 * Use this class to setup database connections and to obtain
 * AnewtDatabaseConnection instances.
 */
final class AnewtDatabase
{
	/** Hash table with database connections */
	static $connections = array();

	/**
	  * Setup a new database connection.
	  *
	  * This stores the connection configuration in the database pool.
	  * See the AnewtDatabaseConnection (and subclasses) documentation for the
	  * description of the settings array.
	  *
	  * \param $settings
	  *   An associative array with connection settings. At least the \c type
	  *   key must be provided to specify the database connection type. See
	  *   AnewtDatabaseConnection for the possible values.
	  * \param $id
	  *   The connection id to use for this connection (optional, defaults to
	  *   <code>default</code>)
	  *
	  * \see AnewtDatabaseConnection
	  */
	static public function setup_connection($settings, $id='default')
	{
		assert('is_assoc_array($settings)');
		assert('array_has_key($settings, "type"); // "type" key must be present in database settings array');
		assert('is_string($id)');

		/* A connection can be setup only once */

		if (array_key_exists($id, AnewtDatabase::$connections))
			throw new AnewtDatabaseException('Connection "%s" has been setup already.', $id);


		/* Create an AnewtDatabaseConnection instance */

		$connection_type = $settings['type'];
		switch ($connection_type)
		{
			case 'sqlite':
				anewt_include('database.new/backend-sqlite'); // FIXME: module name
				$connection = new AnewtDatabaseConnectionSQLite($settings);
				break;

			case 'mysql':
				anewt_include('database.new/backend-mysql'); // FIXME: module name
				$connection = new AnewtDatabaseConnectionMySQL($settings);
				break;

			case 'mysql-old':
				anewt_include('database.new/backend-mysql-old'); // FIXME: module name
				$connection = new AnewtDatabaseConnectionMySQLOld($settings);
				break;

			case 'postgresql':
				anewt_include('database.new/backend-postgresql'); // FIXME: module name
				$connection = new AnewtDatabaseConnectionPostgreSQL($settings);
				break;

			default:
				throw new AnewtDatabaseException('Database type "%s" is not supported', $connection_type);
				break;
		}


		/* Connect by default, unless instructed not to */

		if (array_get_bool($settings, 'autoconnect', true))
			$connection->connect();


		/* Store the connection instance */

		AnewtDatabase::$connections[$id] = $connection;
	}

	/**
	 * Get an already existing AnewtDatabaseConnection.
	 *
	 * \param $id
	 *   The connection id (optional, defaults to <code>default</code>)
	 *
	 * \return
	 *   An AnewtDatabaseConnection instance.
	 */
	static public function get_connection($id='default')
	{
		assert('is_string($id)');
		assert('array_key_exists($id, AnewtDatabase::$connections); // Specified connection must exist');

		return AnewtDatabase::$connections[$id];
	}
}

?>
