<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * Copyright (C) 2006  Wouter Bolsterlee <uws@xs4all.nl>
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


anewt_include('database/postgresql/resultset');


/**
 * Class providing PostgreSQL database connectivity.
 */
class PostgreSQLDB extends DatabaseBackend {

	var $id;        /**< \private The database connection id */
	var $settings;  /**< \private Database connection settings */

	/**
	 * Sets up the settings for this backend. This is used for deferred database
	 * connections, when the settings are needed before a connection is made.
	 * Will be overwritten by PostgreSQLDB::connect($settings).
	 *
	 * \see connect
	 */
	function setup($settings) {
		$this->settings = $settings;
	}
	
	/**
	 * Connects to the PostgreSQL database.
	 *
	 * \param settings An associative array with connection settings: the
	 * 'hostname', 'username' and 'password' indices will be used for connection
	 * setttings. The key 'keep_settings' can be used to indicate whether the
	 * settings are stored. The 'escape_column_names' and 'escape_table_names'
	 * keys can be set to indicate whether column and table names should be
	 * escaped when using CRUD functions.
	 */
	function connect(array $settings) {
		if (is_null($settings))
			$settings = array();

		assert('is_assoc_array($settings)');

		/* We support "hostname", "username" and "pass" too, although the
		 * real connection string uses other names */
		$aliases = array(
				'hostname' => 'host',
				'pass' => 'password',
				'username' => 'user',
				'database' => 'dbname',
				);
		foreach ($aliases as $old => $new) {
			if (array_has_key($settings, $old)) {
				array_set_default($settings, $new, $settings[$old]);
			}
		}

		/* List of keywords that are allowed in the connection string: */
		$keywords = array('host', 'hostaddr', 'port', 'dbname', 'user',
				'password', 'connect_timeout', 'options', 'sslmode', 'service');

		/* Create a connection string from the supplied values, leaving out
		 * illegal (name, value) pairs */
		$options = array();
		foreach ($keywords as $keyword) {
			if (array_key_exists($keyword, $settings)) {
				$value = $settings[$keyword];

				assert('is_string($value)');

				/* Escape single and double quotes */
				$value = str_replace("'", "\'", $value);
				$value = str_replace('"', '\"', $value);

				$options[] = sprintf('%s=%s', $keyword, $value);
			}
		}
		$connection_string = implode(' ', $options);

		$this->id = pg_connect($connection_string) or
			trigger_error(sprintf('Could not connect to database %s',
						$settings['dbname']), E_USER_ERROR);

		/* Keep connection settings only if requested. This makes select_db()
		 * work, but stores the plaintext password in the object's memory. */
		if (!array_get_default($settings, 'keep_settings', false)) {
			/* Unset both the aliases and the connection keywords */
			array_unset_keys($settings, array_keys($aliases));
			array_unset_keys($settings, $keywords);
		}

		$this->settings = $settings;
	}

	/**
	 * Disconnects from the PostgreSQL database.
	 */
	function disconnect() {
		pg_close($this->id);
	}

	/**
	 * Selects the given database. PostgreSQL does not support run-time database
	 * switching, so we connect again with a different dbname parameter. This
	 * requires keep_settings to be true in the connection options passed to
	 * connect().
	 *
	 * \param $name The name of the database to use.
	 *
	 * \see connect
	 */
	function select_db($name) {
		assert('is_string($name)');

		if (isset($this->settings) && is_array($this->settings)) {
			$settings = $this->settings;
			$settings['dbname'] = $name;
			$this->connect($settings);

		} else {
			trigger_error('PostgreSQLDB::select_db() does not work if you
					didn\'t set keep_settings to true in the connection
					options.', E_USER_ERROR);
		}
	}

	/**
	 * Returns the type of this database backend.
	 *
	 * \return Always returns the string 'postgresql'.
	 */
	function get_type() {
		return 'postgresql';
	}

	/**
	 * Escapes a boolean for SQL queries.
	 *
	 * \param $bool The boolean to escape.
	 *
	 * \return The escaped value.
	 */
	function escape_boolean($bool) {
		assert('is_bool($bool)');
		return $bool ? 'true' : 'false';
	}

	/**
	 * Escapes a string for SQL queries.
	 *
	 * \param $str The string to escape.
	 *
	 * \return The escaped string.
	 */
	function escape_string($str) {
		assert('is_string($str)');
		return "'" . pg_escape_string($str) . "'";
	}

	/**
	 * \private
	 *
	 * Escapes a column or table name unconditionally.
	 *
	 * \param $str
	 *   The string to escape
	 *
	 * \return
	 *   The escaped string.
	 */
	function _escape_column_or_table_name($str) {
		assert('is_string($str)');

		$quote_char = '"';
		$parts = explode('.', $str);

		if (count($parts) === 1) {
			/* Add quotes */
			return $quote_char . $str . $quote_char;

		} else {
			/* Add quote only for the last part */
			$result = array();
			foreach ($parts as $part) {
				$result[] = $quote_char . $part . $quote_char;
			}
			return implode('.', $result);
		}
	}

	/**
	 * Escapes a table name for SQL queries.
	 *
	 * \param $str The string to escape.
	 *
	 * \return The escaped string.
	 */
	function escape_table_name($str) {
		assert('is_string($str)');

		if (!array_get_default($this->settings, 'escape_table_names', true))
			return $str;

		return $this->_escape_column_or_table_name($str);
	}

	/**
	 * Escapes a column name for SQL queries.
	 *
	 * \param $str The string to escape.
	 *
	 * \return The escaped string.
	 */
	function escape_column_name($str) {
		assert('is_string($str)');

		if (!array_get_default($this->settings, 'escape_column_names', true))
			return $str;

		return $this->_escape_column_or_table_name($str);
	}

}


?>
