<?php

/*
 * Anewt, Almost No Effort Web Toolkit, autorecord module
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


anewt_include('database');


/**
 * Automatic database record object.
 *
 * AutoRecord is an advanced database wrapper class implementing the active
 * record pattern. Each class wraps a single database table, providing you with
 * a convenient search API for querying and easy to use save(), insert() and
 * delete() methods for object manipulation.
 *
 * The query API consists of several static methods:
 *
 * - AutoRecord::find_all() retrieves all records in the database
 * - AutoRecord::find_by_id() and AutoRecord::find_one_by_id() return records
 *   based on the primary key value
 * - AutoRecord::find_by_sql() and AutoRecord::find_one_by_sql() return records
 *   based on constraints expressed as SQL query parts.
 * - AutoRecord::find_by_column() and AutoRecord::find_one_by_column() return
 *   records where a specified column has the specified value.
 *
 * The data manipulation methods are instance methods that operate on object
 * instances themselves:
 *
 * - AutoRecord::save() saves the current record
 * - AutoRecord::delete() deletes the current record
 *
 * In order to create an AutoRecord subclass, you should name your own class
 * <code>Foo_</code> (with a trailing underscore), and override some of the methods
 * (_db_table() and _db_columns() are obligatory). See the documentation on the
 * methods below for more information. Right after your class definition, you
 * should register your AutoRecord subclass so that the actual magic can be put
 * into place: <code>AutoRecord::register('Foo')</code>. Now you can use the
 * <code>Foo</code> class. Example:
 * <code>$somefoo = Foo::find_one_by_id(12)</code>
 *
 * \todo
 *   find_previous() and find_next() methods (based on sort order)
 */
class AutoRecord extends Container
{
	/** \{
	 * \name Static methods
	 */

	/**
	 * Return a reference to the default database connection. Override this
	 * method if you want to use a custom database instance.
	 *
	 * \return
	 *   A reference to a Database instance
	 */
	protected static function _db()
	{
		$db = DB::get_instance();
		return $db;
	}

	/**
	 * Return the name of the table to use. You must override this method for
	 * your own classes. An example might be the following one-liner:
	 * <code>return 'person';</code>
	 *
	 * \return
	 *   An string with the table name to use
	 *
	 * \see AutoRecord::_db_columns
	 */
	protected static function _db_table()
	{
		throw new Exception('AutoRecord::_db_table() must be overridden.');
	}

	/**
	 * Return an associative array of column name => column type mappings. You
	 * must override this method for your own classes. An example might be the
	 * following one-liner:
	 * <code>return array('id' => 'int', 'name' => 'str', 'age' =>
	 * 'int');</code>
	 *
	 * \return
	 *   An associative array with column name => type items
	 *
	 * \see AutoRecord::_db_table
	 */
	protected static function _db_columns()
	{
		throw new Exception('AutoRecord::_db_columns() must be overridden.');
	}

	/**
	 * Return an array of column names which should be skipped on insert queries
	 * when no values are given. The database is expected to fill a default
	 * value for these columns.
	 */
	protected static function _db_skip_on_insert()
	{
		return array();
	}

	/**
	 * Return an array of column names which should be skipped on update queries.
	 * These are usually read-only values which don't need to get updated
	 * every time.
	 */
	protected static function _db_skip_on_update()
	{
		return array();
	}

	/**
	 * \static \protected
	 *
	 * Return an array of column names which are read-only and should never be
	 * written. The database is expected to fill a default value for these
	 * columns.
	 *
	 * Note that like _db_skip_on_insert the values are still written on an
	 * insert when they are supplied, but they are never written on an update.
	 */
	protected static function _db_skip_on_save()
	{
		return array();
	}

	/**
	 * \static \protected
	 *
	 * Return the name of the primary key. Override this method if you don't
	 * want to use the default value 'id'.
	 *
	 * \return
	 *   The name of the primary key column
	 */
	protected static function _db_primary_key()
	{
		return 'id';
	}

	/**
	 * Return the name of the sequence used for the primary key (PostgreSQL
	 * only). Override this function if you're using a non-standard sequence
	 * for the primary key values of your table.
	 *
	 * \return
	 *   The name of the sequence used for the primary key value
	 */
	protected static function _db_primary_key_sequence()
	{
		return null;
	}

	/**
	 * Return the name of the default sort column. This column is used to sort
	 * the records in some methods that return multiple columns. If you specify
	 * order-by parameters to methods, this value is not used, but for simple
	 * cases like find_all() it serves as the sort column. By default, the
	 * primary key value is used. Override this method if you want a custom
	 * column to be used, eg. a position or date column.
	 *
	 * It is also possible to order by multiple columns by returning an array
	 * of columns. In this case you have to override _db_sort_order() as well
	 * by letting it return an array with the same amount of elements.
	 *
	 * Furthermore you can specify table aliasses used in _db_join_one() by
	 * using "table_alias.column" syntax.
	 * 
	 * \return
	 *   The name of the default sort column or an array of columns in order
	 *   of high to low priority.
	 *
	 * \see AutoRecord::_db_sort_order
	 */
	protected static function _db_sort_column()
	{
		return null;
	}

	/**
	 * Return the default sort order. The value returned by this method should
	 * be ASC or DESC. The default is ascending sort order. Override this method
	 * if you want to change it.
	 *
	 * If you have overridden _db_sort_column() to return multiple column names,
	 * then override this method as well to return the same amount of elements.
	 *
	 * \return
	 *   The default sort order (ASC or DESC) or an array of sort orders.
	 *
	 * \see AutoRecord::_db_sort_column
	 */
	protected static function _db_sort_order()
	{
		return 'ASC';
	}

	/**
	 * \todo document this
	 */
	protected static function _db_has_many()
	{
		return array();
	}

	/**
	 * \todo document this
	 */
	protected static function _db_has_one()
	{
		return array();
	}

	/**
	 * Override this method if you want the default queries to contain
	 * joins with other tables, specified by other AutoRecord classes.
	 * 
	 * The format is an nummeric array of associative arrays. The
	 * associative arrays can have the following keys:
	 * 	foreign_class	- The AutoRecord class which corresponds to the
	 * 			  other table.
	 * 	own_key		- (optional) The foreign key in this table to
	 *			  join on. Use this if the column in this table
	 *			  doesn't match the primary key of the foreign
	 *			  table.
	 * 	foreign_alias	- (optional) The alias name of the other table.
	 *			  Use this if you want the same table joined
	 *			  multiple times.
	 * 	own_alias	- (optional) Use this if 'own_key' is not part
	 * 			  of the table specified by this class; must be
	 * 			  a table name, not a class name.
	 * 	join_type	- (optional) the type of join. Defaults to
	 * 			  'left', but can be 'right' or 'inner' or
	 * 			  anything which can go before the "JOIN"
	 * 			  keyword in the SQL syntax.
	 * \return
	 *	A nummeric array of associative arrays.
	 */
	protected static function _db_join_one()
	{
		return array();
	}

	/**
	 * Register a class as an AutoRecord. This does some evil voodoo magic to
	 * get things to work in a decent way. Your own class name should be called
	 * Foo_ (with a trailing underscore) and should extend AutoRecord; this
	 * method will dynamically create a class Foo extending your class with all
	 * the static methods in place.
	 *
	 * \param $class
	 *   The name of the class to register as an "active record" class (without
	 *   the trailing underscore)
	 */
	public static function register($class)
	{
		assert('is_string($class)');

		/* Extreme precautions because eval() is used */
		if (!preg_match('/^[a-z0-9_]+$/i', $class))
			trigger_error(sprintf(
				'AutoRecord::register(): illegal class name \'%s\'',
				$class), E_USER_ERROR);

		/* There should be a base class with an underscore at the end */
		if (!class_exists($class . '_'))
			trigger_error(sprintf(
				'AutoRecord::register(): class name \'%s_\' does not exist.',
				$class), E_USER_ERROR);

		/* Some useful variables */
		$class_ = $class . '_';

		/* Nasty hack to get some static methods in place, providing a nice API.
		 * Too bad there is no way to retrieve the current class name when
		 * calling static methods from derived classes (debug_backtrace() can be
		 * used in PHP4, but this doesn't work for PHP5). */

		$methods = array();

		/* Select clause with all fields */
		$methods['_db_select_clause'] = 'protected static function _db_select_clause($table_alias=null, $joins=null) {
			$db = @@CLASS@@::_db();
			return @@CLASS@@::__db_select_clause(\'@@CLASS@@\', $table_alias, $joins, $db);
		}';

		/* From clause with all joins */
		$methods['_db_from_clause'] = 'function _db_from_clause($table_alias=null, $joins=null) {
			$db = &@@CLASS@@::_db();
			return @@CLASS@@::__db_from_clause(\'@@CLASS@@\', $table_alias, $joins, $db);
		}';

		/* Create instances from arrays (e.g. database records) */
		$methods['_db_object_from_array'] = 'protected static function _db_object_from_array($arr) {
			$r = @@CLASS@@::__db_object_from_array(\'@@CLASS@@\', $arr);
			return $r;
		}';
		$methods['_db_objects_from_arrays'] = 'protected static function _db_objects_from_arrays($arrs) {
			$r = @@CLASS@@::__db_objects_from_arrays(\'@@CLASS@@\', $arrs);
			return $r;
		}';

		/* Find all */
		$methods['find_all'] = 'public static function find_all() {
			$result = @@CLASS@@::find_by_sql();
			return $result;
		}';

		/* Find by id */
		$methods['find_by_id'] = 'public static function find_by_id($values) {
			$args = func_get_args();
			$num_args = func_num_args();
			/* Accept both multiple parameters and a single array */
			if (($num_args == 1) && is_array($args[0])) {
				$args = $args[0];
			}
			$db = @@CLASS@@::_db();
			$result = AutoRecord::__db_find_by_id(\'@@CLASS@@\', false, $args, $db);
			return $result;
		}';

		$methods['find_one_by_id'] = 'public static function find_one_by_id($value) {
			assert(\'is_int($value)\');
			/* Check for just one single parameter. This helps finding
			 * bugs where find_by_id() was meant to be used */
			$num_args = func_num_args();
			assert(\'$num_args === 1\');
			$db = @@CLASS@@::_db();
			$result = AutoRecord::__db_find_by_id(\'@@CLASS@@\', true, array($value), $db);
			return $result;
		}';

		/* Find by SQL */
		$methods['find_by_sql'] = 'public static function find_by_sql($sql=null, $values=null) {
			$args = func_get_args();
			$sql = array_shift($args);
			if (count($args) == 1 && is_array($args[0])) $args = $args[0];
			$db = @@CLASS@@::_db();
			$result = AutoRecord::__db_find_by_sql(\'@@CLASS@@\', false, $sql, $args, $db);
			return $result;
		}';
		$methods['find_one_by_sql'] = 'public static function find_one_by_sql($sql=null, $values=null) {
			$args = func_get_args();
			$sql = array_shift($args);
			if (count($args) == 1 && is_array($args[0])) $args = $args[0];
			$db = @@CLASS@@::_db();
			$result = AutoRecord::__db_find_by_sql(\'@@CLASS@@\', true, $sql, $args, $db);
			return $result;
		}';

		/* Find by column */
		$methods['find_by_column'] = 'public static function find_by_column($column, $value) {
			$db = @@CLASS@@::_db();
			$result = AutoRecord::__db_find_by_column(\'@@CLASS@@\', false, $column, $value, $db);
			return $result;
		}';
		$methods['find_one_by_column'] = 'public static function find_one_by_column($column, $value) {
			$db = @@CLASS@@::_db();
			$result = AutoRecord::__db_find_by_column(\'@@CLASS@@\', true, $column, $value, $db);
			return $result;
		}';

		/* Has-many relations */
		$has_many_definitions = call_user_func(array($class_, '_db_has_many'));
		foreach ($has_many_definitions as $has_many_definition)
		{
			assert('is_numeric_array($has_many_definition)');
			assert('count($has_many_definition) == 4');
			list ($method_name, $foreign_class, $local_key, $foreign_key) = $has_many_definition;
			assert('is_string($method_name)');
			assert('is_string($foreign_class)');
			assert('is_string($local_key)');
			assert('is_string($foreign_key)');
			$methods[$method_name] = sprintf('
				var $__autorecord_%s;
				public function %s()
				{
					if (is_null($this->__autorecord_%s))
						$this->__autorecord_%s = %s::find_by_column(\'%s\', $this->get(\'%s\'));

					return $this->__autorecord_%s;
				}',
				$method_name, $method_name, $method_name, $method_name,
				$foreign_class, $foreign_key, $local_key, $method_name);
		}

		/* Has-one relations */
		$has_one_definitions = call_user_func(array($class_, '_db_has_one'));
		foreach ($has_one_definitions as $has_one_definition)
		{
			assert('is_numeric_array($has_one_definition)');
			assert('count($has_one_definition) == 4');
			list ($method_name, $foreign_class, $local_key, $foreign_key) = $has_one_definition;
			assert('is_string($method_name)');
			assert('is_string($foreign_class)');
			assert('is_string($local_key)');
			assert('is_string($foreign_key)');
			$methods[$method_name] = sprintf('
				var $__autorecord_%s;
				public function %s()
				{
					if (is_null($this->__autorecord_%s))
						$this->__autorecord_%s = %s::find_one_by_column(\'%s\', $this->get(\'%s\'));

					return $this->__autorecord_%s;
				}',
				$method_name, $method_name, $method_name, $method_name,
				$foreign_class, $foreign_key, $local_key, $method_name);
		}

		/* Custom extra methods */
		$extra_methods = call_user_func(array($class_, '_autorecord_extra_methods'));
		$methods = array_merge($methods, $extra_methods);

		/* Create the actual class definition string. */
		$class_code = array();
		$class_code[] = 'class @@CLASS@@ extends @@CLASS@@_ {';
		foreach ($methods as $m)
			$class_code[] = $m;

		$class_code[] = '}';

		/* Replace placeholders with actual values */
		$class_code = str_replace('@@CLASS@@', $class, join(NL, $class_code));

		/* Actually define the class */
		eval($class_code);
	}

	/**
	 * Create a SQL query part with columns to select. The SELECT keyword is not
	 * included.
	 *
	 * \param $class
	 *   The class name
	 *
	 * \param $table_alias
	 *   Optional string parameter to use as a table alias. If specified, this
	 *   string is prepended to all column names. This is useful if you do
	 *   selects from multiple tables (and identical column names) and you want
	 *   to select all columns from an AutoRecord table (eg. combined with
	 *   a join).
	 *
	 * \param $db
	 *   Reference to the database object instance.
	 *
	 * \return
	 *   String with comma-separated escaped column names. This string can be
	 *   used directly (unescaped) in the SELECT part of an SQL query.
	 */
	protected static function __db_select_clause($class, $table_alias=null, $joins=null, $db)
	{
		assert('is_string($class)');
		assert('$db instanceof DB');

		$column_spec = array();
		$column_data = array();

		$columns = call_user_func(array($class, '_db_columns'));

		if (is_null($table_alias))
			$table_alias = call_user_func(array($class, '_db_table'));

		foreach(array_keys($columns) as $column)
		{
			$column_spec[] = "?table?.?column?";
			$column_data[] = $table_alias;
			$column_data[] = $column;
		}

		if (is_null($joins)) {
			$joins = call_user_func(array($class, '_db_join_one'));
		}

		foreach($joins as $join)
		{
			$foreign_class = $join['foreign_class'];
			$has_alias = false;
			assert('class_exists($foreign_class); // '.$foreign_class);
			if (array_has_key($join, 'foreign_alias'))
			{
				$foreign_alias = $join['foreign_alias'];
				$has_alias = true;
			} else
				$foreign_alias = call_user_func(array($foreign_class, '_db_table'));

			$columns = call_user_func(array($foreign_class, '_db_columns'));
			foreach(array_keys($columns) as $column)
			{
				if ($has_alias)
				{
					$column_spec[] = "?table?.?column? AS ?column?";
					$column_data[] = $foreign_alias;
					$column_data[] = $column;
					$column_data[] = sprintf("%s_%s", $foreign_alias, $column);
				} else {
					$column_spec[] = "?table?.?column?";
					$column_data[] = $foreign_alias;
					$column_data[] = $column;
				}
			}
		}
		$tpl = new SQLTemplate(join(",\n  ", $column_spec), $db);
		return $tpl->fill($column_data);
	}

	/**
	 * Create a SQL query part with tables to select from. The FROM keyword
	 * is not included.
	 *
	 * \param $class
	 *   The class name
	 *
	 * \param $table_alias
	 *   Optional string parameter to use as a table alias.
	 *
	 * \param $db
	 *   Reference to the database object instance.
	 *
	 * \return
	 *   String with comma-separated escaped table names with join
	 *   conditions. This string can be used directly (unescaped) in the
	 *   FROM part of an SQL query.
	 */
	protected static function __db_from_clause($class, $table_alias=null, $joins=null, $db)
	{
		assert('is_string($class)');
		assert('$db instanceof DB;');

		$from_clause = $db->backend->escape_table_name(call_user_func(array($class, '_db_table')));

		if (is_null($table_alias))
			$table_alias = $db->backend->escape_table_name(call_user_func(array($class, '_db_table')));
		else
			$from_clause = sprintf('%s %s', $from_clause, $table_alias);

		if (is_null($joins)) {
			$joins = call_user_func(array($class, '_db_join_one'));
		}

		foreach ($joins as $join)
		{
			$foreign_class = $join['foreign_class'];
			assert('class_exists($foreign_class)');
			$join_type = strtoupper(array_get_default($join, 'join_type', 'left'));
			$foreign_alias = array_get_default($join, 'foreign_alias');
			$own_alias = array_get_default($join, 'own_alias', $table_alias);

			$foreign_table = $db->backend->escape_table_name(call_user_func(array($foreign_class, '_db_table')));
			if (array_has_key($join, 'foreign_key'))
				$foreign_key = $join['foreign_key'];
			else
				$foreign_key = call_user_func(array($foreign_class, '_db_primary_key'));
			$own_key = array_get_default($join, 'own_key', $foreign_key);

			if (is_null($foreign_alias))
				$foreign_alias = $foreign_table;
			else
				$foreign_table = sprintf('%s %s', $foreign_table, $foreign_alias);

			$from_clause = sprintf(
				"%s\n  %s JOIN %s ON (%s.%s = %s.%s)",
				$from_clause,
				$join_type,
				$foreign_table,
				$own_alias,
				$own_key,
				$foreign_alias,
				$foreign_key);
		}

		return $from_clause;
	}

	/**
	 * Creates the order by part of an SQL query. The ORDER BY keyword is
	 * not included.
	 *
	 * \param $class
	 *   The class name
	 *
	 * \param $table_alias
	 *   Optional string parameter to use as a table alias.
	 *
	 * \param $db
	 *   Reference to the database object instance.
	 *
	 * \return
	 *   String with comma-separated escaped order elements.  This string
	 *   can be used directly (unescaped) in the FROM part of an SQL query.
	 */
	protected static function __db_order_clause($class, $table_alias=null, $db)
	{
		if(is_null($table_alias))
			$table_alias = $db->backend->escape_table_name(call_user_func(array($class, '_db_table')));

		$sort_column = call_user_func(array($class, '_db_sort_column'));
		if (is_null($sort_column))
			$sort_column = call_user_func(array($class, '_db_primary_key'));

		if (!is_array($sort_column))
			$sort_column = array($sort_column);

		$sort_order = call_user_func(array($class, '_db_sort_order'));
		if (!is_array($sort_order))
			$sort_order = array($sort_order);

		$order_elements = array();
		foreach(array_keys($sort_column) as $key)
		{
			assert('($sort_order[$key] === "ASC") || ($sort_order[$key] === "DESC")');
			assert('is_string($sort_column[$key])');
			$parts = explode(".", $sort_column[$key], 2);
			if (count($parts) > 1)
			{
				$table = $db->backend->escape_table_name($parts[0]);
				$column = $db->backend->escape_column_name($parts[1]);
			} else {
				$table = $table_alias;
				$column = $db->backend->escape_column_name($sort_column[$key]);
			}
			$order_elements[] = $table . "." . $column . " " . $sort_order[$key];
		}

		return implode(', ', $order_elements);
	}

	/**
	 * Converts a result row into an object instance.
	 *
	 * \param $class
	 *   The class name.
	 *
	 * \param $row
	 *   The row with data (or false).
	 *
	 * \return
	 *   A reference to a new instance or null if no data was provided
	 *
	 * \see AutoRecord::_db_objects_from_arrays
	 */
	protected static function __db_object_from_array($class, $row)
	{
		if ($row === false)
			return null;

		assert('is_assoc_array($row)');
		$instance = new $class();
		$instance->_seed($row);
		return $instance;
	}

	/**
	 * Convert result rows into object instances.
	 *
	 * \param $class
	 *   The class name.
	 *
	 * \param $rows
	 *   The rows with data (or an empty array).
	 *
	 * \return
	 *   An array with references to new instances or an empty array no data was provided
	 *
	 * \see AutoRecord::__db_object_from_array
	 */
	protected static function __db_objects_from_arrays($class, $rows)
	{
		assert('is_numeric_array($rows)');

		$result = array();
		foreach ($rows as $row)
			$result[] = AutoRecord::__db_object_from_array($class, $row);

		return $result;
	}

	/**
	 * Find one or more records by id. Don't use this method directly, use
	 * find_by_id or find_one_by_id on the class itself.
	 *
	 * \param $class
	 *   The class name.
	 *
	 * \param $just_one_result
	 *   Whether to return just one instance (or null) or an array of instances
	 *   (possibly empty)
	 *
	 * \param $values
	 *   The values to search for (primary key values)
	 *
	 * \param $db
	 *   Reference to the database object instance.
	 *
	 * \return
	 *   A single instance (or null) or an array of instances (or empty array)
	 */
	protected static function __db_find_by_id($class, $just_one_result, $values, $db)
	{
		assert('is_string($class)');
		assert('is_bool($just_one_result)');
		assert('is_numeric_array($values)');
		assert('$db instanceof DB');

		$table = call_user_func(array($class, '_db_table'));
		$primary_key = call_user_func(array($class, '_db_primary_key'));

		$select_clause = AutoRecord::__db_select_clause($class, null, null, $db);
		$from_clause = AutoRecord::__db_from_clause($class, null, null, $db);

		/* Single item requested: return an instance or null */
		if ($just_one_result)
		{
			$pq = $db->prepare("SELECT\n  ?raw?\nFROM\n  ?raw?\nWHERE ?table?.?column? = ?int?");
			$rs = $pq->execute($select_clause, $from_clause, $table, $primary_key, $values[0]);
			$row = $rs->fetch();
			$instance = AutoRecord::__db_object_from_array($class, $row);
			return $instance;

		/* Possibly multiple results: return an array with zero or more instances */
		} else
		{
			/* Return early if there are no id values at all */
			$num_values = count($values);
			if ($num_values == 0)
			{
				$r = array();
				return $r;
			}

			/* Build string for "WHERE id IN (...)" query */
			$where = join(', ', array_fill(0, $num_values, '?int?'));
			$tpl = new SQLTemplate($where, $db);
			$ids = $tpl->fill($values);

			/* Execute */
			$pq = $db->prepare("SELECT\n  ?raw?\nFROM\n  ?raw?\nWHERE ?table?.?column? IN (?raw?)");
			$rs = $pq->execute($select_clause, $from_clause, $table, $primary_key, $ids);

			/* Handle results */
			$rows = $rs->fetch_all();
			$instances = AutoRecord::__db_objects_from_arrays($class, $rows);
			return $instances;
		}
	}

	/**
	 * Find one or more records by providing a part of the SQL query. Don't use
	 * this method directly; use find_by_sql or find_one_by_sql on the instance
	 * itself.
	 *
	 * \param $class
	 *   The class name.
	 *
	 * \param $just_one_result
	 *   Whether to return just one instance (or null) or an array of instances
	 *
	 * \param $sql
	 *   The constraints of the SQL query. This can be either null (no
	 *   constraints, selects all records), a string (the part of the WHERE
	 *   clause up to the end of the query) or an associative array (with
	 *   join, where, order-by, limit and offset indices, all optional).
	 *   You can use ?str?-style placholders for the data provided in
	 *   $values
	 *
	 * \param $values
	 *   The values to be substituted for the placeholders your provided with
	 *   your constraints.
	 *
	 * \param $db
	 *   Reference to the database object instance.
	 */
	protected static function __db_find_by_sql($class, $just_one_result=false, $sql=null, $values=null, $db)
	{
		/* Input sanitizing */
		if (is_null($values))
			$values = array();

		assert('is_string($class)');
		assert('is_bool($just_one_result)');
		assert('is_null($sql) || is_string($sql) || is_assoc_array($sql)');
		assert('is_array($values)');
		assert('$db instanceof DB');

		/* Get basic database settings */
		$table = call_user_func(array($class, '_db_table'));
		$primary_key = call_user_func(array($class, '_db_primary_key'));

		/* Basic clauses */
		if (is_array($sql))
			$joins = array_get_default($sql, 'join', null);
		else
			$joins = null;

		$select_clause = AutoRecord::__db_select_clause($class, null, $joins, $db);

		$from_clause = AutoRecord::__db_from_clause($class, null, $joins, $db);

		$order_clause = AutoRecord::__db_order_clause($class, null, $db);

		/* Find all records when no sql was specified*/
		if (is_null($sql))
		{
			$pq = $db->prepare("SELECT\n  ?raw?\nFROM\n  ?raw?\nORDER BY ?raw?");
			$rs = $pq->execute($select_clause, $from_clause, $order_clause);

		/* Plain SQL */
		} elseif (is_string($sql))
		{
			$constraints_tpl = new SQLTemplate($sql, $db);
			$constraints_sql = $constraints_tpl->fill($values);

			$pq = $db->prepare("SELECT\n  ?raw?\nFROM\n  ?raw?\n?raw?");
			$rs = $pq->execute($select_clause, $from_clause, $constraints_sql);

		/* Associative array with constraints.
		 * These may contain both literal values and placeholder strings. It's
		 * just a fancy way of calling this method with a string parameters
		 * (useful when using user input). Note that all constraints are treated
		 * as plain strings. The parameters to be filled in for placeholders in
		 * these strings should be specified in the $values array. */
		} else {
			$constraints = array();

			/* Where. Nothing fancy, just use it. */
			$where = array_get_default($sql, 'where', null);
			if (!is_null($where))
				$constraints[] = sprintf('WHERE %s', $where);

			/* Order by. Both 'order-by' and 'order' keys are recognized. */
			$order_by = array_get_default($sql, 'order-by', null);
			if (is_null($order_by))
				$order_by = array_get_default($sql, 'order', null);

			if (is_null($order_by))
				$constraints[] = sprintf("\nORDER BY %s", $order_clause);
			else
				$constraints[] = sprintf("\nORDER BY %s", $order_by);

			/* Limit. "Optimizing" this depending on the value of
			 * $just_one_result is impossible since it may contain a placeholder
			 * string and not a literal value. We take care of $just_one_result
			 * when fetching the result rows. */
			$limit = array_get_default($sql, 'limit', null);
			if (!is_null($limit))
				$constraints[] = sprintf("\nLIMIT %s", $limit);

			/* Offset. How many rows to skip? */
			$offset = array_get_default($sql, 'offset', null);
			if (!is_null($offset))
				$constraints[] = sprintf("\nOFFSET %s", $offset);

			$constraints_tpl = new SQLTemplate(join(' ', $constraints), $db);
			$constraints_sql = $constraints_tpl->fill($values);

			$pq = $db->prepare("SELECT\n  ?raw?\nFROM\n  ?raw?\n?raw?");
			$rs = $pq->execute($select_clause, $from_clause, $constraints_sql);
		}

		if ($just_one_result)
		{
			$row = $rs->fetch();
			$instance = AutoRecord::__db_object_from_array($class, $row);
			$rs->free(); // don't need it anymore
			return $instance;
		} else {
			$rows = $rs->fetch_all();
			$instances = AutoRecord::__db_objects_from_arrays($class, $rows);
			return $instances;
		}
	}


	/**
	 * Find one or more records by one column value. Don't use this method
	 * directly, use find_by_column or find_one_by_column on the class itself.
	 *
	 * \param $class
	 *   The class name
	 *
	 * \param $just_one_result
	 *   Whether to return just one instance (or null) or an array of instances
	 *   (possibly empty)
	 *
	 * \param $column
	 *   The column to match
	 *
	 * \param $value
	 *   The value to match
	 *
	 * \param $db
	 *   Reference to the database object instance.
	 */
	protected static function __db_find_by_column($class, $just_one_result, $column, $value, $db)
	{
		/* Input sanitizing */
		assert('is_string($class)');
		assert('is_bool($just_one_result)');
		assert('is_string($column)');
		assert('$db instanceof DB');

		$table = call_user_func(array($class, '_db_table'));

		/* Find out the column type */
		$columns = call_user_func(array($class, '_db_columns'));
		if (!array_has_key($columns, $column))
			trigger_error(sprintf("Column %s not found in column list of %s", $column, $class));

		/* The array form of __db_find_by_sql is used, so that the default sort
		 * column is used. If a plain sql string is provided, no sorting will
		 * be done at all. */
		if (is_null($value))
		{
			$where_clause = '?table?.?column? IS NULL';
			$placeholder_values = array($table, $column);
		} else
		{
			$where_clause = sprintf('?table?.?column? = ?%s?', $columns[$column]);
			$placeholder_values = array($table, $column, $value);
		}

		$result = AutoRecord::__db_find_by_sql(
				$class,
				$just_one_result,
				array('where' => $where_clause),
				$placeholder_values,
				$db);
		return $result;
	}


	/**
	 * Convert a list of instances to a hash, based on a unique key of the
	 * passed AutoRecord instances.
	 *
	 * \param $objects
	 *   A list of AutoRecord instances
	 *
	 * \param $column
	 *   The name of the (unique) column to use as the associative array.
	 *
	 * \return
	 *   An associative array with the (unique) column value as the key and the
	 *   object itself as the value
	 *
	 * \see AutoRecord::convert_to_primary_key_hash
	 */
	public static function convert_to_key_hash($objects, $column)
	{
		assert('is_numeric_array($objects)');
		assert('is_string($column)');

		/* Handle empty lists */
		if (count($objects) == 0)
			return array();

		/* Now iterate over objects and put into hash */
		$r = array();
		foreach (array_keys($objects) as $object_key)
		{
			assert('$objects[$object_key] instanceof AutoRecord;');
			$r[$objects[$object_key]->_get($column)] = $objects[$object_key];
		}
		return $r;
	}

	/**
	 * Convert a list of instances to a hash, based on the primary key of the
	 * passed AutoRecord instances.
	 *
	 * \param $objects
	 *   A list of AutoRecord instances
	 *
	 * \return
	 *   An associative array with the primary key as the key and the object
	 *   itself as the value
	 *
	 * \see AutoRecord::convert_to_key_hash
	 */
	public static function convert_to_primary_key_hash($objects)
	{
		assert('is_numeric_array($objects)');

		/* Handle empty lists */
		if (count($objects) == 0)
			return array();

		/* Find out the primary key column by looking in the first object */
		$primary_key_column = $objects[0]->_db_primary_key();

		$r = AutoRecord::convert_to_key_hash($objects, $primary_key_column);
		return $r;
	}

	/** \} */


	/** \{
	 * \name Instance methods
	 */

	/**
	 * Inserts the data as a new record in the database. Don't use this method
	 * directly, use save() instead.
	 *
	 * \param $skip_primary_key
	 *	Whether to skip the primary key in the column list.
	 *
	 * \see AutoRecord::save
	 */
	protected function __db_insert($skip_primary_key=true)
	{
		$table = $this->_db_table();
		$columns = $this->_db_columns();
		$skip_on_insert = $this->_db_skip_on_insert();
		$skip_on_save = $this->_db_skip_on_save();
		$skip_on_insert = array_merge($skip_on_insert, $skip_on_save);
		$db = $this->_db();
		$primary_key = $this->_db_primary_key();

		$this->before_insert();

		/* Loop over the columns and build an INSERT query, which contains two
		 * lists: one list of column names and one list of values to be inserted */
		$number_of_columns = 0;
		$names = array();
		$values = array();
		foreach ($columns as $name => $type)
		{
			assert('is_string($name)');
			assert('is_string($type)');

			/* Skip the primary key */
			if ($name === $primary_key && $skip_primary_key)
				continue;

			/* Skip columns which should be filled by the database */
			if (in_array($name, $skip_on_insert) && !$this->is_set($name))
				continue;

			$number_of_columns++;
			$names[] = $name;
			$value_types[] = sprintf('?%s:%s?', $type, $name); /* placeholder for real values */
			$values[$name] = $this->getdefault($name, null);
		}
    
		/* Create SQL for the list of column names */
		$columns_tpl = new SQLTemplate(
				join(', ', array_fill(0, $number_of_columns, '?column?')),
			   	$db);
		$columns_sql = $columns_tpl->fill($names);

		/* Create SQL for the list of column values */
		$values_tpl = new SQLTemplate(
				join(', ', $value_types),
				$db);
		$values_sql = $values_tpl->fill($values);


		/* Prepare and execute the query */
		$query = sprintf('INSERT INTO ?table? (%s) VALUES (%s)', $columns_sql, $values_sql);
		$pq = $db->prepare($query);
		$rs = $pq->execute($table);

		if ($skip_primary_key)
		{
			/* Find out the new primary key value */
			switch ($db->type)
			{
				/* MySQL has a custom SQL function */
				case 'mysql':
					$row = $db->prepare_execute_fetch(
							'SELECT LAST_INSERT_ID() AS id');
					$this->_set($primary_key, $row['id']);
					break;

				/* SQLite has a special function */
				case 'sqlite':
					$this->_set($primary_key, sqlite_last_insert_rowid($db->backend->handle));
					break;

				/* PostgreSQL uses sequences */
				case 'postgresql':
					$primary_key_sequence = $this->_db_primary_key_sequence();
					if (is_null($primary_key_sequence))
					{
						/* Try to use PostgreSQL defaults */
						$primary_key_sequence = sprintf(
								'%s_%s_seq',
								$table,
								$primary_key);
					}
					assert('is_string($primary_key_sequence)');
					$row = $db->prepare_execute_fetch(
							'SELECT currval(?string?) AS id',
							$primary_key_sequence);
					$this->_set($primary_key, $row['id']);
					break;

				/* Fallback for unsupported backends */
				default:
					$row = $db->prepare_execute_fetch(
							'SELECT MAX(?column?) AS id FROM ?table?',
							$primary_key, $table);
					$this->_set($primary_key, $row['id']);
					break;
			}
		}

		$this->after_insert();
	}

	/**
	 * Updates an existing record in the database with the current instance
	 * data. Don't use this method directly, use save() instead.
	 *
	 * \see AutoRecord::save
	 */
	protected function __db_update()
	{
		$table = $this->_db_table();
		$columns = $this->_db_columns();
		$db = $this->_db();
		$skip_on_update = $this->_db_skip_on_update();
		$skip_on_save = $this->_db_skip_on_save();
		$skip_on_update = array_merge($skip_on_update, $skip_on_save);
		$primary_key = $this->_db_primary_key();
		$primary_key_value = $this->get($primary_key);

		$this->before_update();

		/* Loop over the columns and build an UPDATE query */
		$placeholders = array();
		$values = array();
		foreach ($columns as $name => $type)
		{
			assert('is_string($name)');
			assert('is_string($type)');

			/* Skip the primary key */
			if ($name === $primary_key)
				continue;

			/* Skip read-only values */
			if (in_array($name, $skip_on_update))
				continue;

			$placeholders[] = sprintf('%s = ?%s:%s?', $db->backend->escape_column_name($name), $type, $name);
			$values[$name] = $this->getdefault($name, null);
		}

		if(count($placeholders))
		{
			/* Create SQL for the list of column names */
			$update_tpl = new SQLTemplate(join(', ', $placeholders), $db);
			$update_sql = $update_tpl->fill($values);
	
			/* Prepare and execute the query */
			$pq = $db->prepare('UPDATE ?table? SET ?raw? WHERE ?column? = ?int?');
			$rs = $pq->execute($table, $update_sql, $primary_key, $primary_key_value);
		}

		$this->after_update();
	}

	/**
	 * Save this record in the database. If the record was previously unsaved
	 * (no primary key value was set), an INSERT query is performed. Otherwise,
	 * an UPDATE on the existing row is done.
	 *
	 * \param new
	 *   Whether or not we should insert a new row. Leave empty to check on the
	 *   primary key.
	 */
	public function save($new=null)
	{
		$this->before_save();

		if (is_null($new))
		{
			/* Default behaviour */
			$primary_key = $this->_db_primary_key();
			if ($this->is_set($primary_key))
				$this->__db_update();
			else
				$this->__db_insert();
		} else
		{
			/* Forced new/existing record */
			if ($new)
				$this->__db_insert(false);
			else
				$this->__db_update();
		}

		$this->after_save();
	}

	/**
	 * Delete this record from the database. If the record was previously
	 * unsaved, this is a no-op. Note that this method overrides
	 * Container::delete() (and Container::invalidate()). If you provide
	 * a parameter, the call is handed off to Container::delete() instead of
	 * deleting the record from the database.
	 *
	 * \param $name
	 *   Do not specify this! It's for code compatibility with Container only.
	 *
	 * \fixme See bug 173044
	 */
	public function delete($name=null)
	{
		/* If there are any parameters, we propagate the call to Container,
		 * instead of deleting the record from the database. This is evil and
		 * should be solved in a clean way. See bug 173044. */
		if (!is_null($name))
			return Container::delete($name);

		$this->before_delete();

		$primary_key = $this->_db_primary_key();
		if ($this->is_set($primary_key))
		{
			$primary_key_value = $this->get($primary_key);
			$db = $this->_db();
			$table = $this->_db_table();
			$db->prepare_execute(
				'DELETE FROM ?table? WHERE ?column? = ?int?;',
				$table, $primary_key, $primary_key_value);
		}

		$this->after_delete();
	}

	/**
	 * Toggle a boolean value in this record. If the value was previously unset
	 * (SQL NULL), the value is initialized to true.
	 *
	 * \param $column
	 *   The name of the column to toggle.
	 */
	public function toggle($column)
	{
		assert('is_string($column)');
		$columns = $this->_db_columns();
		assert('array_has_key($columns, $column)');
		assert('SQLTemplate::column_type_from_string($columns[$column]) == ANEWT_DATABASE_TYPE_BOOLEAN;');

		$current_value = $this->getdefault($column, null);
		if (is_null($current_value))
			$new_value = true;

		else
		{

			/* Handle strings and integers, because some databases don't support
			 * boolean column types (MySQL) and others don't support column
			 * types at all (SQLite). */
			if (is_int($current_value))
				$current_value = (bool) $current_value;
			elseif (is_string($current_value))
				$current_value = ($current_value !== '0');

			assert('is_bool($current_value)');
			$new_value = !$current_value;
		}
		$this->set($column, $new_value);
	}

	/** \} */


	/**
	 * \{
	 * \name Callback methods
	 *
	 * Callback methods that can be overridden to add specific functionality.
	 *
	 * Autorecord instances call some special callback methods when certain
	 * actions are about to take place (e.g. AutoRecord::before_save()) or have
	 * just taken place (e.g. AutoRecord::after_delete). These methods do
	 * nothing by default, but you can override one or more of them in your own
	 * classes if you want to do specific things, e.g. to fix up values before
	 * they enter the database (but you should really consider using
	 * Container::get() and Container::set() for this purpose) or to break
	 * foreign key references.
	 */

	/** Callback before saving */
	public function before_save() {}

	/** Callback after saving */
	public function after_save() {}

	/** Callback before inserting */
	public function before_insert() {}

	/** Callback after inserting */
	public function after_insert() {}

	/** Callback before updating */
	public function before_update() {}

	/** Callback after updating */
	public function after_update() {}

	/** Callback before deletion */
	public function before_delete() {}

	/** Callback after deletion */
	public function after_delete() {}

	/** \} */


	/**\{
	 * \name Static public API methods
	 *
	 * Note: These methods only have signatures, so that they can be documented.
	 * The actual implementation is done using magic in the register() method.
	 */

	/**
	 * Select clause with all fields
	 */
	protected static function _db_select_clause($table_alias=null) {}

	/**
	 * Create instance from array.
	 *
	 * \param $arr
	 *   An associative array with data, e.g. a row from a database
	 *
	 * \return
	 *   AutoRecord instance
	 *
	 * \see _db_objects_from_arrays
	 */
	protected static function _db_object_from_array($arr) {}

	/**
	 * Create instances from arrays.
	 *
	 * \param $arrs
	 *   List of associative arrays with data, e.g. multiple rows from
	 *   a database
	 *
	 * \return
	 *   Array of AutoRecord instances (may be empty)
	 *
	 * \see _db_object_from_array
	 */
	protected static function _db_objects_from_arrays($arrs) {}

	/**
	 * Find all records in the database
	 * 
	 * \return
	 *   Array of AutoRecord instances (may be empty)
	 */
	public static function find_all() {}

	/**
	 * Find records by id.
	 *
	 * \param $values
	 *   The primary key values of the records to retrieve
	 *
	 * \return
	 *   Array of AutoRecord instances (may be empty)
	 *
	 * \see find_one_by_id
	 */
	public static function find_by_id($values) {}

	/**
	 * Find a single record by id.
	 *
	 * \param $value
	 *   The primary key value of the record to retrieve
	 *
	 * \return
	 *   AutoRecord instance (or NULL)
	 *
	 * \see find_by_id
	 */
	public static function find_one_by_id($value) {}

	/**
	 * Find records by providing SQL contraints.
	 *
	 * \param $sql
	 *   The constraints of the SQL query. This can be either null (no
	 *   constraints, selects all records, but please use find_all() instead),
	 *   a string (the part of the WHERE clause up to the end of the query) or
	 *   an associative array (with where, order-by, limit and offset indices,
	 *   all optional). You can use ?str?-style placholders for the data
	 *   provided in $values
	 *
	 * \param $values
	 *   The values to be substituted for the placeholders your provided with
	 *   the constraints.
	 *
	 * \see find_one_by_sql
	 */
	public static function find_by_sql($sql=null, $values=null) {}

	/**
	 * Find a single record by providing SQL contraints.
	 *
	 * \param $sql
	 *   Contraints of the SQL query
	 *
	 * \param $values
	 *   Values to be substituted in the query
	 *
	 * \return
	 *   AutoRecord instance (or NULL)
	 *
	 * \see find_by_sql
	 */
	public static function find_one_by_sql($sql=null, $values=null) {}

	/**
	 * Find records by column value. This is a shorthand to find records based
	 * on the value of a single column, e.g. a unique key.
	 *
	 * \param $column
	 *   The name of the column to use
	 *
	 * \param $value
	 *   The value for the column
	 *
	 * \return
	 *   Array of AutoRecord instances (may be empty)
	 */
	public static function find_by_column($column, $value) {}

	/**
	 * Find a single record by column value. This is a shorthand to find a record
	 * based on the value of a single column, e.g. a unique key.
	 *
	 * \param $column
	 *   The name of the column to use
	 *
	 * \param $value
	 *   The value for the column
	 *
	 * \return
	 *   AutoRecord instance (or NULL)
	 */
	public static function find_one_by_column($column, $value) {}

	/**
	 * Returns an array of additional static methods to be included in the new class
	 * on registering.
	 *
	 * The format of the array elements is $method_name => $method_code, where
	 * $method_name is the name of the method, en $method_code is a string containing
	 * the code of the method, including the "function" keyword.
	 *
	 * Every instance of @@CLASS@@ in $method_code will be substituted for the class
	 * name of the registring function.
	 */
	protected static function _autorecord_extra_methods() { return array(); }

	/** \} */
}

?>
