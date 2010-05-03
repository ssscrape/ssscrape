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


/**
 * Sqlite-specific database result set.
 */
class SqliteResultSet extends ResultSet {

	var $backend;        /**< \private The backend instance */
	var $rs;             /**< \private A resultset instance */
	var $rows_affected;  /**< \private The number of rows that was changed by this query */

	/**
	 * Constructs a new SqliteResultSet
	 *
	 * \param $sql
	 *   The sql query to execute.
	 *
	 * \param $backend
	 *   A reference to the used backend.
	 */
	function SqliteResultSet($sql, &$backend) {
		assert('is_string($sql)');

		$this->sql = $sql;
		$this->backend = &$backend;

		$this->rs = sqlite_query($this->backend->handle, $sql)
			or trigger_error(sprintf('Query failed (%s)',
				sqlite_error_string(sqlite_last_error($this->backend->handle))),
				E_USER_ERROR);
		$this->rows_affected = sqlite_changes($this->backend->handle);
	}

	function fetch() {
		return sqlite_fetch_array($this->rs, SQLITE_ASSOC, true);
	}

	function count() {
		return sqlite_num_rows($this->rs);
	}

	function count_affected() {
		return $this->rows_affected;
	}
}

?>
