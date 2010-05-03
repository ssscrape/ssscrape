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


/**
 * Base class for database backends. Most methods should be overridden in
 * subclasses that implement a specific backend.
 */
abstract class DatabaseBackend
{
	var $db; /**< \private Database object instance */

	/**
	 * Returns the type of the database (must be overridden).
	 */
	abstract function get_type();


	/* Connection handling */

	/**
	 * Sets up the settings when using a deferred connection. Can be overridden.
	 */
	function setup($settings) { }
	
	/**
	 * Connects to the database.
	 *
	 * \param $settings
	 *   The database settings as an associative array.
	 */
	abstract function connect(array $settings);

	/**
	 * Disconnects from the database.
	 */
	abstract function disconnect();

	/**
	 * Selects the database to use.
	 *
	 * \param $name
	 *   The name of teh database to use.
	 */
	abstract function select_db($name);


	/* Escaping */

	/**
	 * Escapes a boolean for use in SQL queries. Override this method if the
	 * specific type of database has better escaping functions available.
	 *
	 * \param $bool
	 *   The boolean to escape.
	 *
	 * \return
	 *   The escaped value.
	 */
	function escape_boolean($bool)
	{
		assert('is_bool($bool)');
		return $bool ? '1' : '0';
	}

	/**
	 * Escapes a string for use in SQL queries. Override this method if the
	 * specific type of database has better escaping functions available.
	 *
	 * \param $str
	 *   The string to escape.
	 *
	 * \return
	 *   The escaped string.
	 */
	function escape_string($str)
	{
		assert('is_string($str)');
		return sprintf("'%s'", addslashes($str));
	}

	/**
	 * Escapes a table name for use in SQL queries. Override this method if the
	 * specific type of database has better escaping functions available.
	 *
	 * \param $str
	 *   The string to escape.
	 *
	 * \return
	 *   The escaped string.
	 */
	function escape_table_name($str)
	{
		assert('is_string($str)');
		return $str;
	}

	/**
	 * Escapes a column name for use in SQL queries. Override this method if the
	 * specific type of database has better escaping functions available.
	 *
	 * \param $str
	 *   The string to escape.
	 *
	 * \return
	 *   The escaped string.
	 */
	function escape_column_name($str)
	{
		assert('is_string($str)');
		return $str;
	}

	/**
	 * Escapes a date. This method only adds quotes.
	 *
	 * \param $date
	 *   The value to escape.
	 *
	 * \return
	 *   The escaped value.
	 */
	function escape_date($date)
	{
		assert('is_string($date)');
		return sprintf("'%s'", $date);
	}

	/**
	 * Escapes a time. This method only adds quotes.
	 *
	 * \param $time
	 *   The value to escape.
	 *
	 * \return
	 *   The escaped value.
	 */
	function escape_time($time)
	{
		assert('is_string($time)');
		return sprintf("'%s'", $time);
	}

	/**
	 * Escapes a datetime. This method only adds quotes.
	 *
	 * \param $datetime
	 *   The value to escape.
	 *
	 * \return
	 *   The escaped value.
	 */
	function escape_datetime($datetime)
	{
		assert('is_string($datetime)');
		return sprintf("'%s'", $datetime);
	}

	/* Transaction-related methods */

	/**
	 * Starts a transaction. Override this method for backends that need
	 * customization.
	 */
	function transaction_begin()
	{
		$this->db->prepare_execute('BEGIN');
	}

	/**
	 * Commits a transaction. Override this method for backends that need
	 * customization.
	 */
	function transaction_commit()
	{
		$this->db->prepare_execute('COMMIT');
	}

	/**
	 * Rolls back a transaction. Override this method for backends that need
	 * customization.
	 */
	function transaction_rollback()
	{
		$this->db->prepare_execute('ROLLBACK');
	}
}

?>
