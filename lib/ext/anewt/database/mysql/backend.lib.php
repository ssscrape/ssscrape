<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * Copyright (C) 2004-2006  Wouter Bolsterlee <uws@xs4all.nl>
 * Copyright (C) 2004  Jasper Looije <jasper@jamu.nl>
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


anewt_include('database/mysql/resultset');


/**
 * Class providing MySQL database connectivity.
 */
class MysqlDB extends DatabaseBackend
{
	var $id; /**< \private The database connection id */

	/**
	 * Connects to the MySQL database.
	 *
	 * \param settings An associative array with connection settings: the
	 * 'hostname', 'username' and 'password' indices will be used.
	 */
	function connect(array $settings)
	{
		is_array($settings) &&
			isset($settings['hostname'], $settings['username'],
					$settings['password'])
			or trigger_error('Invalid parameters to connect()', E_USER_ERROR);

		$this->id = mysql_pconnect(
				$settings['hostname'],
				$settings['username'],
				$settings['password'],
				true)
			or trigger_error(sprintf('Could not connect to database (%d: %s)',
						mysql_errno($this->id), mysql_error($this->id)),
					E_USER_ERROR);

		if (array_has_key($settings, 'database'))
			$this->select_db($settings['database']);
		
	}

	/**
	 * Disconnects from the MySQL database.
	 */
	function disconnect()
	{
		mysql_close($this->id);
	}

	/**
	 * Selects the given database.
	 *
	 * \param $name The name of the database to use.
	 */
	function select_db($name)
	{
		assert('is_string($name)');

		mysql_select_db($name, $this->id) or trigger_error(sprintf('Could not select database (%d: %s)', mysql_errno($this->id), mysql_error($this->id)), E_USER_ERROR);
	}

	/**
	 * Returns the type of this database backend.
	 *
	 * \return Always returns the string 'mysql'.
	 */
	function get_type()
	{
		return 'mysql';
	}

	/**
	 * Escapes a string for SQL queries.
	 *
	 * \param $str The string to escape.
	 *
	 * \return The escaped string.
	 */
	function escape_string($str)
	{
		assert('is_string($str)');

		/* The mysql_real_escape_string function depends on a valid connection,
		 * so it can only be used if $this->id is a mysql resource, otherwise
		 * mysql_escape_string has to be used. The $this->id resource is null if
		 * the database connection is (not yet) established and SQLTemplate is
		 * used to escape SQL strings. */

		if (is_resource($this->id))
			$str = mysql_real_escape_string($str, $this->id);
		else
			$str = mysql_escape_string($str);

		$out = sprintf("'%s'", $str);
		return $out;
	}

	/**
	 * Escapes a table name for SQL queries.
	 *
	 * \param $str The string to escape.
	 *
	 * \return The escaped string.
	 */
	function escape_table_name($str)
	{
		assert('is_string($str)');

		$parts = explode('.', $str);

		if (count($parts) == 1)
		{
			/* Add quotes */
			$out = sprintf('`%s`', $str);

		} else {
			/* Add quotes around each part */
			$result = array();
			foreach ($parts as $part)
				$result[] = sprintf('`%s`', $part);

			$out = implode('.', $result);
		}

		return $out;
	}

	/**
	 * Escapes a column name for SQL queries.
	 *
	 * \param $str The string to escape.
	 *
	 * \return The escaped string.
	 */
	function escape_column_name($str)
	{
		assert('is_string($str)');

		/* Same as escape_table_name */
		$out = $this->escape_table_name($str);
		return $out;
	}

}

?>
