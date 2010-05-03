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
 * Prepared Query class. This class takes care of parameter checking and value
 * substitution.
 */
class PreparedQuery {
	var $db;                           /**< \private Database object instance reference */
	var $sql_template;                 /**< \private SQLTemplate instance */
	var $debug = false;                /**< Enable/disable debugging */
	var $debug_print = false;          /**< Print queries before execution */

	/**
	 * \private Constructs a new PreparedQuery. This instance can be executed
	 * later. Don't use this method directly: use $db->prepare() instead.
	 *
	 * \param $sql_template_str
	 *   SQL query template with ?int? style placeholders.
	 *
	 * \param $db
	 *   Reference to the database object instance.
	 *
	 * \see SQLTemplate
	 */
	function PreparedQuery($sql_template_str, &$db) {

		/* Sanity checks */
		assert('is_string($sql_template_str)');
		assert('is_object($db)');

		/* Initialize */
		$this->db = &$db;
		$this->sql_template = new SQLTemplate($sql_template_str, $db);
	}


	/**
	 * Executes a query. This function takes a variable number of arguments or
	 * one array parameter.
	 *
	 * \param $values
	 *   One array or multiple values that will be substituted for the
	 *   placeholders in the prepared query.
	 *
	 * \return
	 *   A ResultSet instance for this query.
	 *
	 * \see
	 *   SQLTemplate::fill()
	 */
	function &execute($values=null) {

		/* Connect if that's still needed. */
		$this->db->connect();
		
		/* Pass along parameters to SQLTemplate::fill() */
		$args = func_get_args();
		if ((count($args) == 1) && (is_array($args[0]) || $args[0] instanceof Container))
			$args = $args[0];
		$query = $this->sql_template->fill($args);

		/* Debug mode? */
		if ($this->debug) {
			/* Don't keep too many queries since it may cause memory exhaustion */
			if (count($this->db->queries_executed) > 500)
				array_splice($this->db->queries_executed, 0, 250);

			$this->db->queries_executed[] = $query;
		}
		if ($this->debug_print) {
			echo $query, "\n";
		}

		$rstype = ucfirst(strtolower($this->db->backend->get_type())) . 'ResultSet';
		$this->db->num_queries_executed++;
		$this->db->last_query = $query;

		$rs = new $rstype($query, $this->db->backend);
		return $rs;
	}

}

?>
