<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * Copyright (C) 2006 Marijn Kruisselbrink <m.kruisselbrink@student.tue.nl>
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


anewt_include('database/sqlite/resultset');


/**
 * Class providing SQLite database connectivity.
 */
class SqliteDB extends DatabaseBackend {

	var $handle; /**< \private The database handle */

	/**
	 * Returns the type of this database backend.
	 *
	 * \return
	 *   Always returns the string 'sqlite'.
	 */
	function get_type() {
		return 'sqlite';
	}

	/**
	 * Opens the SQLite database.
	 *
	 * \param settings An associative array with connection settings: the
	 * 'filename' and 'mode' indices will be used (but mode is optional).
	 */
	function connect(array $settings) {
		is_array($settings) && array_has_key($settings, 'filename')
			or trigger_error('Invalid parameters to connect()', E_USER_ERROR);

		$mode = array_get_default($settings, 'mode', 0666);
		
		$this->handle = sqlite_open(
				$settings['filename'],
				$mode,
				$error)
			or trigger_error(sprintf('Could not open database (%s)', $error),
					E_USER_ERROR);
	}

	/**
	 * Closes the SQLite database.
	 */
	function disconnect() {
		sqlite_close($this->handle);
	}

	/**
	 * Selects the given database. This closes the current database, and opens
	 * the given database with a default mode of 0666 (octal).
	 *
	 * \param $name
	 *   The filename of the database to use.
	 */
	function select_db($name) {
		assert('is_string($name)');

		disconnect();
		connect(array('filename' => $name));
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

		return "'" . sqlite_escape_string($str) . "'";
	}
}

?>
