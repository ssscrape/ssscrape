<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * Copyright (C) 2004-2006  Wouter Bolsterlee <uws@xs4all.nl>
 * Copyright (C) 2005  Daniel de Jong <djong@ortec.nl>
 * Copyright (C) 2006  Marijn Kruisselbrink <m.kruisselbrink@student.tue.nl>
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


anewt_include('database/mssql/resultset');


/**
 * MS SQL database abstraction.
 */
class MssqlDB extends DatabaseBackend {

	var $id;  /**< \private The mysql connection id */

	/**
	 * Connects to the MSSQL database.
	 *
	 * \param $settings
	 *   An associative array with connection settings: the 'hostname',
	 *   'username' and 'password' indices will be used.
	 */
	function connect(array $settings) {
		is_array($settings) && isset($settings['hostname'],
				$settings['username'], $settings['password'])
			or trigger_error('Invalid parameters to connect()', E_USER_ERROR);

		$this->id = &mssql_pconnect(
				$settings['hostname'],
				$settings['username'],
				$settings['password'])
			or trigger_error(sprintf('Could not connect to databaseserver (%s)',
						$settings['hostname']), E_USER_ERROR);

		// suppress warnings
		if (function_exists('mssql_min_client_severity')) mssql_min_client_severity(100);
		if (function_exists('mssql_min_server_severity')) mssql_min_server_severity(100);
		if (function_exists('mssql_min_message_severity')) mssql_min_message_severity(100);
		if (function_exists('mssql_min_error_severity')) mssql_min_error_severity(100);

	}

	/**
	 * Disconnects from the MSSQL database.
	 */
	function disconnect() {
		mssql_close($this->id);
	}

	/**
	 * Selects the given database.
	 *
	 * \param $name
	 *   The name of the database to use.
	 */
	function select_db($name) {
		assert('is_string($name)');

		mssql_select_db($name, $this->id) or trigger_error('Could not select database', E_USER_ERROR);
	}

	/**
	 * Returns the type of this database backend.
	 *
	 * \return
	 *   Always returns the string 'mssql'.
	 */
	function get_type() {
		return 'mssql';
	}

	/**
	 * Escapes a string for SQL queries.
	 *
	 * \param $str
	 *   The string to escape.
	 *
	 * \return
	 *   The escaped string.
	 */
	function escape_string($str) {
		if (is_null($str))
			return 'NULL';

		return "'" . str_replace("'", "''", $str) . "'";
	}
}

?>
