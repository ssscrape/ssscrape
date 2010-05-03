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


anewt_include('datetime');


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
		'ANEWT_DATABASE_TYPE_BOOLEAN',

		/* Numeric */
		'ANEWT_DATABASE_TYPE_INTEGER',
		'ANEWT_DATABASE_TYPE_FLOAT',

		/* String */
		'ANEWT_DATABASE_TYPE_STRING',

		/* Dates and times */
		'ANEWT_DATABASE_TYPE_DATE',
		'ANEWT_DATABASE_TYPE_TIME',
		'ANEWT_DATABASE_TYPE_DATETIME',
		'ANEWT_DATABASE_TYPE_TIMESTAMP',

		/* Raw */
		'ANEWT_DATABASE_TYPE_RAW',

		/* SQL internals */
		'ANEWT_DATABASE_TYPE_COLUMN',
		'ANEWT_DATABASE_TYPE_TABLE'
);



/**
 * SQL Template class with mandatory type checking. This class implements the
 * type checking logic for SQL queries.
 */
class SQLTemplate {
	var $db;             /**< \private Database object instance reference */
	var $placeholders;   /**< \private List of parameters (placeholders) */
	var $named_placeholders;   /**< \private List of named parameters (placeholders) */
	var $named_mode = false; /**< \private are we using 'named mode' with named placeholders? */
	var $sql_template;   /**< \private SQL template */

	/**
	 * Constructs a new SQLTemplate instance.
	 *
	 * \param $sql_template
	 *   The template SQL string.
	 *
	 * \param $db
	 *   Reference to the database object instance.
	 *
	 * \see SQLTemplate::parse
	 */
	function SQLTemplate($sql_template, &$db) {
		assert('is_string($sql_template)');
		assert('$db instanceof DB');

		/* Initial values */
		$this->db = &$db;
		$this->placeholders = array();
		$this->named_placeholders = array();
		
		/* Parse the template */
		$this->parse($sql_template);
	}


	/**
	 * Convert a column type string into the associated constant. This function
	 * returns one of the <code>ANEWT_DATABASE_TYPE_*</code> constants, and
	 * triggers and error if $type_str is not a valid identifier.
	 *
	 * Example: The string <code>int</code> results in the
	 * <code>ANEWT_DATABASE_TYPE_INTEGER</code> constant.
	 *
	 * \param $type_str
	 *   A string indicating a database type, e.g. <code>int</code>.
	 *
	 * \return
	 *   Associated <code>ANEWT_DATABASE_TYPE_*</code> constant.
	 */
	public static function column_type_from_string($type_str)
	{
		assert('is_string($type_str);');
		$mapping = array(
			'bool'      => ANEWT_DATABASE_TYPE_BOOLEAN,
			'boolean'   => ANEWT_DATABASE_TYPE_BOOLEAN,
			'i'         => ANEWT_DATABASE_TYPE_INTEGER,
			'int'       => ANEWT_DATABASE_TYPE_INTEGER,
			'integer'   => ANEWT_DATABASE_TYPE_INTEGER,
			'f'         => ANEWT_DATABASE_TYPE_FLOAT,
			'float'     => ANEWT_DATABASE_TYPE_FLOAT,
			'double'    => ANEWT_DATABASE_TYPE_FLOAT,
			's'         => ANEWT_DATABASE_TYPE_STRING,
			'str'       => ANEWT_DATABASE_TYPE_STRING,
			'string'    => ANEWT_DATABASE_TYPE_STRING,
			'varchar'   => ANEWT_DATABASE_TYPE_STRING,
			'date'      => ANEWT_DATABASE_TYPE_DATE,
			'datetime'  => ANEWT_DATABASE_TYPE_DATETIME,
			'time'      => ANEWT_DATABASE_TYPE_TIME,
			'timestamp' => ANEWT_DATABASE_TYPE_TIMESTAMP,
			'r'         => ANEWT_DATABASE_TYPE_RAW,
			'raw'       => ANEWT_DATABASE_TYPE_RAW,
			'col'       => ANEWT_DATABASE_TYPE_COLUMN,
			'column'    => ANEWT_DATABASE_TYPE_COLUMN,
			'table'     => ANEWT_DATABASE_TYPE_TABLE,
		);

		if (array_key_exists($type_str, $mapping))
			return $mapping[$type_str];

		trigger_error(sprintf('Field type "%s" is unknown', $type_str), E_USER_ERROR);
	}

	/**
	 * Parses a template string and extracts placeholders.
	 *
	 * \param $sql_template
	 *   The template SQL string.
	 */
	function parse($sql_template) {
		assert('is_string($sql_template)');

		/* Since vsprintf is used to substitute escaped values into the sql
		 * query later on, % characters need to be escaped. */
		$sql_template = str_replace('%', '%%', $sql_template); // escape old values

		/* Find placeholders fields. All placeholders start with ? followed by
		 * a keyword and end with ? too. Examples ?string? and ?int? */
		$fields = array();
		$named_fieldspattern = '/\?([a-z]+):([^?]*)\?/i';
		$fieldspattern = '/\?([a-z]+)\?/i';

		if (preg_match_all($named_fieldspattern, $sql_template, $fields)) {
			assert('!preg_match_all($fieldspattern, $sql_template, $dummy); // mixing named placeholders with anoymous placeholders is not supported');
			$this->named_mode = true;	// switch to named placeholders

			/* $fields[1] now contains the matches inside the first
			 * parenthesized expression. Assign the special types to the params
			 * list, so that proper validation and escaping/quoting can be done
			 * when providing values to these placeholders. */
			$match = 0;
			foreach ($fields[0] as $field)
			{
				$this->named_placeholders[] = array(
					'type' => SQLTemplate::column_type_from_string($fields[1][$match]),
					'var' => $fields[2][$match]
				);
				$match++;
			}

			/* Replace all ?type:var? parts with %s to allow easy vsprintf
			 * substitution when filling in values. Quoting the values is taken
			 * care of in the fill() method. */
			$sql_template = preg_replace($named_fieldspattern, '%s', $sql_template);

		} elseif (preg_match_all($fieldspattern, $sql_template, $fields)) {
			/* $fields[1] now contains the matches inside the first
			 * parenthesized expression. Assign the special types to the params
			 * list, so that proper validation and escaping/quoting can be done
			 * when providing values to these placeholders. */
			foreach ($fields[1] as $field)
			{
				$this->placeholders[] = SQLTemplate::column_type_from_string($field);
			}

			/* Replace all ?field? parts with %s to allow easy vsprintf
			 * substitution when filling in values. Quoting the values is taken
			 * care of in the fill() method. */
			$sql_template = preg_replace($fieldspattern, '%s', $sql_template);
		}
		$this->sql_template = $sql_template;
	}

	/**
	 * Fills in the valus in the SQL template. This method will check all values
	 * for correctness to avoid nasty SQL injection vulnerabilities.
	 *
	 * \param $args
	 *   Array with values to use for substitution.
	 *
	 * \return
	 *   The query containing all values, quoted correctly.
	 */
	function fill($args=null) {
		/* We accept either:
		 * - no parameters
		 * - multiple scalar parameters
		 * - 1 array parameter with scalar elements
		 * - 1 associative array parameter
		 * - 1 container parameter
		 */
		$args = func_get_args();
		if($this->named_mode) {
			if (count($args) != 1) {
				trigger_error('associative array or Container expected', E_USER_ERROR);
			}
			if($args[0] instanceof Container) {
				$args = $args[0]->to_array();
			} elseif(is_array($args[0])) {
				$args = $args[0];
			} else {
				trigger_error('associative array or Container expected', E_USER_ERROR);
			}

			$numargs = count($this->named_placeholders);
		} else {
			if ((count($args) == 1) && is_numeric_array($args[0]))
				$args = $args[0];
	
			assert('is_numeric_array($args)');
	
			if (count($args) != count($this->placeholders)) {
				trigger_error(sprintf(
					'Incorrect number of parameters to SQLTemplate::fill(): expected %d, got %d',
					count($this->placeholders), count($args)), E_USER_ERROR);
			};

			$numargs = count($args);
		}

		/* Note: don't use foreach() here, because it copies the values in
		 * memory and leaves the original values untouched! */
		for ($i = 0; $i < $numargs; $i++) {
			if($this->named_mode) {
				$fieldtype = $this->named_placeholders[$i]['type'];
				$var = $this->named_placeholders[$i]['var'];
				if(!isset($args[$var])) {
					$var = str_replace('-', '_', $var);	// Container replaces '-' with '_'
					if(!array_key_exists($var, $args)) {
						trigger_error(sprintf('SQLTemplate::fill(): missing expected parameter "%s"',
							$this->named_placeholders[$i]['var']),
							E_USER_ERROR);
					}
				}
				$value = $args[$var];
				$argname = "`".$var."'";
			} else {
				$fieldtype = $this->placeholders[$i];
				$value = &$args[$i];
				$argname = $i + 1;
			}

			/* Handle NULL values here. Escaping is not needed for NULL values. */
			if (is_null($value)) {
				$value = 'NULL';
				if($this->named_mode) {
					$arglist[$i] = $value;
				}
				continue;
			}

			/* The value is non-null. Perform very restrictive input sanitizing
			 * based on the field type. */

			switch ($fieldtype) {

				case ANEWT_DATABASE_TYPE_BOOLEAN:

					/* Integers: only accept 0 and 1 (no type juggling!) */
					if (is_int($value)) {
						if ($value === 0) {
							$value = false;
						} elseif ($value === 1) {
							$value = true;
						}
					}

					/* Strings: only accept literal "0" and "1" (no type juggling!) */
					if (is_string($value)) {
						if ($value === "0") {
							$value = false;
						} elseif ($value === "1") {
							$value = true;
						}
					}

					if (is_bool($value)) {
						$value = $this->db->backend->escape_boolean($value);
						break;
					}

					trigger_error(sprintf('Invalid boolean value: "%s" on argument %s', $value, $argname),
							E_USER_ERROR);

				case ANEWT_DATABASE_TYPE_INTEGER:

					if (is_int($value)) {
						$value = (string) $value;
						break;
					}
					
					if (is_string($value) && preg_match('/^-?\d+$/', $value))
						break;

					trigger_error(sprintf('Invalid integer value: "%s" on argument %s', $value, $argname),
							E_USER_ERROR);

				case ANEWT_DATABASE_TYPE_FLOAT:

					// FIXME: this does not accept .123 (without a leading zero)
					if (is_string($value) && preg_match('/^-?\d+(\.\d*)?$/', $value)) {
						/* Enough checks done by the regex, no need to do any
						 * formatting/escaping */
						break;

					/* Locale-agnostic float formatting */
					} elseif (is_int($value) || is_float($value)) {
						$value = number_format($value, 10, '.', '');
						if (str_has_suffix($value, '.')) $value .= '0';
						break;
					}

					trigger_error(sprintf('Invalid float value: "%s" on argument %s', $value, $argname),
							E_USER_ERROR);

				case ANEWT_DATABASE_TYPE_STRING:

					/* Accept integers and objects with a render() method. */
					if (is_int($value)) {
						$value = (string) $value;
					} elseif (is_object($value) && method_exists($value, 'render')) {
						$value = $value->render();
					}

					/* From this point on, only strings are accepted. */
					if (is_string($value)) {
						$value = $this->db->backend->escape_string($value);
						break;
					}

					trigger_error(sprintf('Invalid string value: "%s" on argument %s', $value, $argname),
							E_USER_ERROR);

				case ANEWT_DATABASE_TYPE_DATE:

					if ($value instanceof AnewtDateTimeAtom)
						$value = AnewtDateTime::sql_date($value);

					if (is_string($value) && preg_match('/^\d{2,4}-\d{2}-\d{2}$/', $value)) {
						$value = $this->db->backend->escape_date($value);
						break;
					}
					if (is_string($value) && strtoupper($value) == "NOW") {
						$value = "NOW()";
						break;
					}

					if (is_string($value) && strtoupper($value) == 'NOW') {
						$value = 'NOW()';
						break;
					}

					trigger_error(sprintf('Invalid date value: "%s" on argument %s',
								$value, $argname), E_USER_ERROR);

				case ANEWT_DATABASE_TYPE_TIME:

					if ($value instanceof AnewtDateTimeAtom)
						$value = AnewtDateTime::sql_time($value);

					if (is_string($value) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
						$value = $this->db->backend->escape_time($value);
						break;
					}
					if (is_string($value) && strtoupper($value) == "NOW") {
						$value = "NOW()";
						break;
					}

					if (is_string($value) && strtoupper($value) == 'NOW') {
						$value = 'NOW()';
						break;
					}

					trigger_error(sprintf('Invalid time value: "%s" on argument %s',
								$value, $argname), E_USER_ERROR);

				case ANEWT_DATABASE_TYPE_DATETIME:
				case ANEWT_DATABASE_TYPE_TIMESTAMP:

					if ($value instanceof AnewtDateTimeAtom)
						$value = AnewtDateTime::sql($value);

					if (is_string($value) && preg_match('/^\d{2,4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
						$value = $this->db->backend->escape_datetime($value);
						break;
					}
					if (is_string($value) && strtoupper($value) == "NOW") {
						$value = "NOW()";
						break;
					}

					if (is_string($value) && strtoupper($value) == 'NOW') {
						$value = 'NOW()';
						break;
					}

					trigger_error(sprintf('Invalid datetime or timestamp value: "%s" on argument %s',
								$value, $argname), E_USER_ERROR);

				case ANEWT_DATABASE_TYPE_RAW:
					/* No checking, no escaping... use at your own risk ;-) */
					break;


				/* The column and table type are mostly for internal usage, it's
				 * a BAD idea to use user data for these fields! */

				case ANEWT_DATABASE_TYPE_COLUMN:

					if (is_string($value) && preg_match('/^([a-z0-9_-]+\.)*[a-z0-9_-]+$/i', $value)) {
						$value = $this->db->backend->escape_column_name($value);
						break;
					}

					trigger_error(sprintf('Invalid column value: "%s" on argument %s', $value, $argname),
							E_USER_ERROR);


				case ANEWT_DATABASE_TYPE_TABLE:

					if (is_string($value) && preg_match('/^([a-z0-9_-]+\.)*[a-z0-9_-]+$/i', $value)) {
						$value = $this->db->backend->escape_table_name($value);
						break;
					}

					trigger_error(sprintf('Invalid table value: "%s" on argument %s', $value, $argname),
							E_USER_ERROR);


				default:
					trigger_error('This is a bug! Fieldtype unknown',
							E_USER_ERROR);
					break;
			}

			assert('is_string($value)');

			if($this->named_mode) {
				$arglist[$i] = $value;
			}
		}

		/* Now that all supplied values are validated and escaped properly, we
		 * can easily substitute them into the query template. The %s
		 * placeholders were already prepared during initial parsing. */
		if($this->named_mode) {
			$query = vsprintf($this->sql_template, $arglist);
		} else {
			$query = vsprintf($this->sql_template, $args);
		}

		return $query;
	}
}

?>
