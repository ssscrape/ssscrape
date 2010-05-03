<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * Copyright (C) 2004-2004  Wouter Bolsterlee <uws@xs4all.nl>
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


define(ANEWT_DATABASE_MSSQL_ROWS_AFFECTED_EXISTS, function_exists('mssql_rows_affected'));


/**
 * MS SQL-specific database result set.
 */
class MssqlResultSet extends ResultSet {

	var $backend; /**< \private The backend instance */
	var $rs;      /**< \private A resultset instance */
	var $rowcount;/**< \private The number of affected rows (for update/insert/delete queries) */
	
	/**
	 * Constructs a new MssqlResultSet.
	 *
	 * \param $sql
	 *   The sql query to execute.
	 * \param $backend
	 *   A reference to the used backend.
	 */
	function MssqlResultSet($sql, &$backend) {
		assert('is_string($sql)');

		$this->sql = $sql;
  		$this->backend = &$backend;

		// Attention! Due to a bug in the mssql driver, when you use insert or
		// update, mssql_query() always returns false, so that error checking is
		// effectively impossible.
		$this->rs = mssql_query($sql, $this->backend->id);
		
		if (!ANEWT_DATABASE_MSSQL_ROWS_AFFECTED_EXISTS) {
			// For update/insert/delete queries, figure out the number of rows affected
			$kw = strtolower(substr(trim($sql), 0, 6));
			if ($kw == "delete" || $kw == "insert" || $kw == "update") {
				$rs = mssql_query("SELECT @@ROWCOUNT", $this->backend->id);
				list($this->rowcount) = mssql_fetch_row($rs);
			} else {
				$this->rowcount = 0;
			}
		}
	}

	function fetch() {
		// Attention! Due to a bug in the sybase driver, a boolean 'true' is
		// returned when the query was succesful but did not return any rows. We
		// work around this problem by checking for this 'true' value.
		if ($this->rs === true) {
			return false;
		}
		return mssql_fetch_assoc($this->rs);
	}

	function count() {
		// Attention! See notes above.
		if ($this->rs === true) {
			return 0;
		}
		return mssql_num_rows($this->rs);
	}

	function count_affected() {
		if (ANEWT_DATABASE_MSSQL_ROWS_AFFECTED_EXISTS) {
			return mssql_rows_affected($this->rs);
		} else {
			return $this->rowcount;
		}
	}
}

?>
