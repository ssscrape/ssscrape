<?php

/*
 * Anewt, Almost No Effort Web Toolkit, autorecord module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


anewt_include('database');


/**
 * Automatic database record object.
 *
 * AnewtAutoRecord is an advanced database wrapper class implementing the active
 * record pattern. Each class wraps a single database table, providing you with
 * a convenient search API for querying and easy to use db_save(), db_insert()
 * and db_delete() methods for object manipulation.
 *
 * The query API consists of several static methods:
 *
 * - db_find_all() retrieves all records in the database
 * - db_find_all_by_id() and db_find_one_by_id()
 *   return records based on the primary key value
 * - db_find_all_by_sql() and db_find_one_by_sql()
 *   return records based on constraints expressed as SQL query parts.
 * - db_find_all_by_column() and
 *   db_find_one_by_column() return records where a specified
 *   column has the specified value.
 * - db_find_all_by_columns() and
 *   db_find_one_by_columns() return records where specified
 *   columns have the specified values.
 *
 * The data manipulation methods are instance methods that operate on object
 * instances themselves:
 *
 * - db_save() saves the current record
 * - db_insert() inserts a new record
 * - db_update() updates an existing record
 * - db_delete() deletes the current record
 *
 * For more advance SQL query building, several methods can be used to build
 * (part of) the query:
 *
 * - db_sql_select()
 * - db_sql_from()
 * - db_sql_order_by()
 *
 * In order to create an AnewtAutoRecord subclass, you should name your own
 * class <code>MyClass_</code> (with a trailing underscore!), and override some
 * of the methods (db_table() and db_columns() are obligatory). See the
 * documentation on the methods below for more information. Right after your
 * class definition, you should register your AnewtAutoRecord subclass so that
 * the actual magic can be put into place:
 *
 * <code>AnewtAutoRecord::register('MyClass')</code>
 *
 * Now you can use the <code>MyClass</code> class. Example:
 *
 * <code>$my_instance = MyClass::db_find_one_by_id(12);</code>
 */
abstract class AnewtAutoRecord extends AnewtContainer
{
	/** \{
	 * \name Autorecord Registration
	 */

	/**
	 * Register a class as an AnewtAutoRecord.
	 *
	 * This does some evil voodoo magic to get things to work in a decent way.
	 * Your own class name should be called <code>MyClass_</code> (with
	 * a trailing underscore) and should extend AnewtAutoRecord. After calling
	 * this method class <code>MyClass</code> will be dynamically generated with
	 * all the static methods in place.
	 *
	 * \param $class
	 *   The name of the class to register as an active record class (without
	 *   the trailing underscore)
	 */
	final public static function register($class)
	{
		assert('is_string($class)');

		/* Extreme precautions because eval() is used */
		if (!preg_match('/^[a-z0-9_]+$/i', $class))
			throw new AnewtException('Invalid class name: "%s"', $class);

		/* There must be a base class with an underscore at the end of the name. */
		$base_class = sprintf('%s_', $class);
		if (!class_exists($base_class))
			throw new AnewtException('Class name "%s_" does not exist.', $class);

		/* What follows below is a hack to get some static methods in place, in
		 * order to provide a nice API. Too bad there is no way to retrieve the
		 * current class name when calling static methods from derived classes.
		 * debug_backtrace() could be used for this in PHP 4, but this doesn't
		 * work for PHP 5. XXX: Perhaps static:: could be used in PHP > 5.3. */

		$methods = array();


		/* SQL: SELECT, FROM, ORDER BY */

		$methods['db_sql_select'] =
			'final public static function db_sql_select($table_alias=null)
			{
				return @@CLASS@@::_db_sql_select(\'@@CLASS@@\', $table_alias, @@CLASS@@::db_connection());
			}';

		$methods['db_sql_from'] =
			'final public static function db_sql_from($table_alias=null)
			{
				return @@CLASS@@::_db_sql_from(\'@@CLASS@@\', $table_alias, @@CLASS@@::db_connection());
			}';

		$methods['db_sql_order_by'] =
			'final public static function db_sql_order_by($table_alias=null)
			{
				return @@CLASS@@::_db_sql_order_by(\'@@CLASS@@\', $table_alias, @@CLASS@@::db_connection());
			}';



		/* Create instances from arrays (e.g. database records) */

		$methods['db_object_from_array'] =
			'final public static function db_object_from_array($arr)
			{
				return @@CLASS@@::_db_object_from_array(\'@@CLASS@@\', $arr);
			}';

		$methods['db_objects_from_arrays'] =
			'final public static function db_objects_from_arrays($arrs)
			{
				return @@CLASS@@::_db_objects_from_arrays(\'@@CLASS@@\', $arrs);
			}';


		/* Finder methods */

		$methods['db_find_all'] =
			'final public static function db_find_all()
			{
				return @@CLASS@@::db_find_all_by_sql();
			}';

		$methods['db_find_all_by_id'] =
			'final public static function db_find_all_by_id($values=array())
			{
				assert(\'is_numeric_array($values) && $values\');
				return AnewtAutoRecord::_db_find_by_id(\'@@CLASS@@\', false, $values, @@CLASS@@::db_connection());
			}';

		$methods['db_find_one_by_id'] =
			'final public static function db_find_one_by_id($value)
			{
				assert(\'is_int($value)\');
				return AnewtAutoRecord::_db_find_by_id(\'@@CLASS@@\', true, array($value), @@CLASS@@::db_connection());
			}';

		$methods['db_find_all_by_sql'] =
			'final public static function db_find_all_by_sql($sql=null, $values=array())
			{
				return AnewtAutoRecord::_db_find_by_sql(\'@@CLASS@@\', false, $sql, $values, @@CLASS@@::db_connection());
			}';

		$methods['db_find_one_by_sql'] =
			'final public static function db_find_one_by_sql($sql=null, $values=array())
			{
				return AnewtAutoRecord::_db_find_by_sql(\'@@CLASS@@\', true, $sql, $values, @@CLASS@@::db_connection());
			}';

		$methods['db_find_all_by_column'] =
			'final public static function db_find_all_by_column($column, $value)
			{
				return AnewtAutoRecord::_db_find_by_columns(\'@@CLASS@@\', false, array($column => $value), @@CLASS@@::db_connection());
			}';

		$methods['db_find_one_by_column'] =
			'final public static function db_find_one_by_column($column, $value)
			{
				return AnewtAutoRecord::_db_find_by_columns(\'@@CLASS@@\', true, array($column => $value), @@CLASS@@::db_connection());
			}';

		$methods['db_find_all_by_columns'] =
			'final public static function db_find_all_by_columns($columns_with_values)
			{
				return AnewtAutoRecord::_db_find_by_columns(\'@@CLASS@@\', false, $columns_with_values, @@CLASS@@::db_connection());
			}';

		$methods['db_find_one_by_columns'] =
			'final public static function db_find_one_by_columns($columns_with_values)
			{
				return AnewtAutoRecord::_db_find_by_columns(\'@@CLASS@@\', true, $columns_with_values, @@CLASS@@::db_connection());
			}';


		/* Create the actual class definition string */

		$class_code = array();
		$class_code[] = 'class @@CLASS@@ extends @@CLASS@@_ {';
		foreach ($methods as $m)
		{
			$class_code[] = $m;
		}
		$class_code[] = '}';

		/* Replace placeholders with actual values */
		$class_code = str_replace('@@CLASS@@', $class, join(NL, $class_code));

		/* Actually define the class */
		eval($class_code);
	}

	/** \} */


	/** \{
	 * \name Database Methods
	 */

	/**
	 * Obtain a database connection.
	 *
	 * By default this returns the default database connection. Override this
	 * method if you want to use a custom database connection.
	 *
	 * \return
	 *   An AnewtDatabaseConnection instance
	 *
	 * \see AnewtDatabase::get_connection()
	 * \see AnewtDatabaseConnection
	 */
	public static function db_connection()
	{
		return AnewtDatabase::get_connection();
	}

	/**
	 * Return the name of the table to use.
	 *
	 * You must override this method for your own classes. Example:
	 * <code>return 'person';</code>
	 *
	 * \return
	 *   An string with the table name to use
	 *
	 * \see db_columns
	 */
	public static function db_table()
	{
		/* XXX: Static methods cannot be abstract for some reason... */
		throw new AnewtException('AnewtAutoRecord::db_table() must be overridden.');
	}

	/**
	 * Return an associative array of column name to column type mappings.
	 *
	 * You must override this method for your own classes. Example:
	 *
	 * <code>
	 * return array(
	 *     'id'   => 'int',
	 *     'name' => 'str',
	 *     'age' => 'int'
	 *     );
	 * </code>
	 *
	 * \return
	 *   An associative array mapping column names to column types
	 *
	 * \see db_table
	 * \see db_columns_skip_on_insert
	 * \see db_columns_skip_on_update
	 * \see db_columns_skip_on_save
	 * \see AnewtDatabaseSQLTemplate::column_type_from_string()
	 */
	public static function db_columns()
	{
		/* XXX: Static methods cannot be abstract for some reason... */
		throw new AnewtException('AnewtAutoRecord::db_columns() must be overridden.');
	}

	/**
	 * Return an array of column names which are read-only and should never be
	 * written.
	 *
	 * This is like db_columns_skip_on_insert() and db_columns_skip_on_update(),
	 * but is taken into account for both \c INSERT and \c UPDATE queries.
	 */
	public static function db_columns_skip_on_save()
	{
		return array();
	}

	/**
	 * Return an array of column names which should be skipped on insert queries
	 * when no values are given.
	 *
	 * The database is expected to fill a default value for these columns.
	 */
	public static function db_columns_skip_on_insert()
	{
		return array();
	}

	/**
	 * Return an array of column names which should be skipped on update queries.
	 *
	 * These are usually read-only values which don't need to get updated every
	 * time.
	 */
	public static function db_columns_skip_on_update()
	{
		return array();
	}

	/**
	 * Return an associative array with sort columns and sort orders.
	 *
	 * These columns are used for sorting in the find methods when no explicit
	 * <code>ORDER BY</code> clause has been provided. The keys in the returned
	 * array must be column names, and the values must be one of the string
	 * literals \c ASC or \c DESC.
	 *
	 * If not specified, no <code>ORDER BY</code> clause will be used by
	 * the standard find methods.
	 *
	 * Example:
	 *
	 * <code>
	 * return array(
	 *     'name' => 'ASC',
	 *     'age'  => 'DESC',
	 *     );
	 * </code>
	 *
	 * \see db_find_all_by_sql
	 * \see db_find_one_by_sql
	 */
	public static function db_columns_order_by()
	{
		return array();
	}

	/**
	 * Return the name of the primary key.
	 *
	 * Override this method if you don't want to use the default value
	 * <code>id</code>.
	 *
	 * \return
	 *   The name of the primary key column
	 */
	public static function db_primary_key_column()
	{
		return 'id';
	}

	/**
	 * Return the name of the sequence used for the primary key (PostgreSQL
	 * only).
	 *
	 * Override this function if you're using a non-standard sequence for the
	 * primary key values of your table.
	 *
	 * \return
	 *   The name of the sequence used for the primary key value
	 */
	public static function db_primary_key_sequence()
	{
		return null;
	}

	/**
	 * Return a SQL \c SELECT clause.
	 *
	 * Note that you cannot override this method.
	 *
	 * \param $table_alias  Optional table alias name
	 *
	 * \return \c SELECT string
	 */
	public static function db_sql_select($table_alias=null)
	{
		/* Implemented in register() */
	}

	/**
	 * Return a SQL \c FROM clause.
	 *
	 * Note that you cannot override this method.
	 *
	 * \param $table_alias  Optional table alias name
	 *
	 * \return \c FROM string
	 */
	public static function db_sql_from($table_alias=null)
	{
		/* Implemented in register() */
	}

	/**
	 * Return a SQL <code>ORDER BY</code> clause.
	 *
	 * By default the array returned by db_columns_order_by is used to build the
	 * <code>ORDER BY</code> clause, which suffices for most simple cases. Note
	 * that \c null is return if no columns where specified in
	 * db_columns_order_by.
	 *
	 * You can override this method if you want more complex sorting by default.
	 * Make sure you honor the \c $table_alias argument if you do so (and make
	 * sure you escape it properly). Example:
	 *
	 * <code>return sprintf('SOME_FUNCTION(%s.some_column) ASC', MyClass::db_connection()->escape_table_name($table_alias);</code>
	 *
	 * \param $table_alias  Optional table alias name
	 *
	 * \return <code>ORDER BY</code> string, or \c null
	 *
	 * \see db_columns_order_by
	 */
	public static function db_sql_order_by($table_alias=null)
	{
		/* Implemented in register() */
	}

	/**
	 * Return a SQL \c SELECT clause.
	 *
	 * The \c SELECT keyword is not included.
	 *
	 * \param $class  Class name
	 * \param $table_alias  Optional table alias name
	 * \param $connection  AnewtDatabaseConnection instance
	 *
	 * \return
	 *   String with comma-separated escaped column names. This string can be
	 *   used directly (unescaped) in the SELECT part of an SQL query, i.e.
	 *   using a <code>?raw?</code> placeholder.
	 */
	final protected static function _db_sql_select($class, $table_alias=null, $connection)
	{
		assert('is_string($class)');
		assert('is_null($table_alias) || is_string($table_alias)');
		assert('$connection instanceof AnewtDatabaseConnection');

		if (!is_null($table_alias))
			$table_alias_escaped = $connection->escape_table_name($table_alias);

		$columns = call_user_func(array($class, 'db_columns'));
		$sql_parts = array();

		foreach(array_keys($columns) as $column_name)
		{
			$column_name_escaped = $connection->escape_column_name($column_name);

			if (is_null($table_alias))
				$sql_parts[] = $column_name_escaped;
			else
				$sql_parts[] = sprintf('%s.%s', $table_alias_escaped, $column_name_escaped);
		}

		return join(', ', $sql_parts);
	}

	/**
	 * Return a SQL \c FROM clause.
	 *
	 * \param $class  Class name
	 * \param $table_alias  Optional table alias name
	 * \param $connection  AnewtDatabaseConnection instance
	 *
	 * \return
	 *   String with comma-separated escaped table names with join
	 *   conditions. This string can be used directly (unescaped) in the
	 *   FROM part of an SQL query.
	 */
	final protected static function _db_sql_from($class, $table_alias=null, $connection)
	{
		assert('is_string($class)');
		assert('is_null($table_alias) || is_string($table_alias)');
		assert('$connection instanceof AnewtDatabaseConnection;');

		$table_escaped = $connection->escape_table_name(call_user_func(array($class, 'db_table')));

		if (is_null($table_alias))
		{
			$out = $table_escaped;
		}
		else
		{
			$table_alias_escaped = $connection->escape_table_name($table_alias);
			$out = sprintf('%s %s', $table_escaped, $table_alias_escaped);
		}

		return $out;
	}

	/**
	 * Return a SQL <code>ORDER BY</code> clause.
	 *
	 * \param $class  Class name
	 * \param $table_alias  Optional table alias name
	 * \param $connection  AnewtDatabaseConnection instance
	 *
	 * \return
	 *   String with comma-separated escaped table names with join
	 *   conditions. This string can be used directly (unescaped) in the
	 *   FROM part of an SQL query.
	 */
	final protected static function _db_sql_order_by($class, $table_alias=null, $connection)
	{
		assert('is_string($class)');
		assert('is_null($table_alias) || is_string($table_alias)');
		assert('$connection instanceof AnewtDatabaseConnection;');

		$allowed_orders = array('ASC', 'DESC');

		if (!is_null($table_alias))
			$table_alias_escaped = $connection->escape_table_name($table_alias);

		$column_names = array_keys(call_user_func(array($class, 'db_columns')));
		$columns_order_by = call_user_func(array($class, 'db_columns_order_by'));

		$sql_parts = array();
		foreach ($columns_order_by as $column_name => $order)
		{
			if (!in_array(strtoupper($order), $allowed_orders))
				throw new AnewtException('Invalid ORDER BY order specification should be "ASC" or "DESC", but "%s" was specified', $order);

			if (!in_array($column_name, $column_names))
				throw new AnewtException('Unknown ORDER BY column: "%s"', $column_name);

			$column_name_escaped = $connection->escape_column_name($column_name);

			if (is_null($table_alias))
				$sql_parts[] = sprintf('%s %s', $column_name_escaped, $order);
			else
				$sql_parts[] = sprintf('%s.%s %s', $table_alias_escaped, $column_name_escaped, $order);
		}

		if ($sql_parts)
			return join(', ', $sql_parts);
		else
			return null;
	}

	/** \} */


	/**\{
	 * \name Record Finding Methods
	 *
	 * Note: These methods only have signatures, so that they can be documented.
	 * The actual implementation is done using magic in the register() method.
	 */

	/**
	 * Find all records in the database table.
	 *
	 * \return
	 *   Array of AnewtAutoRecord instances (may be empty)
	 */
	public static function db_find_all()
	{
		/* Implemented in register() */
	}

	/**
	 * Find records by id.
	 *
	 * \param $values
	 *   The primary key values of the records to retrieve
	 *
	 * \return
	 *   Array of AnewtAutoRecord instances (may be empty)
	 *
	 * \see db_find_one_by_id
	 */
	public static function db_find_all_by_id($values=array())
	{
		/* Implemented in register() */
	}

	/**
	 * Find a single record by primary key value.
	 *
	 * \param $value
	 *   The primary key value of the record to retrieve
	 *
	 * \return
	 *   AnewtAutoRecord instance (or NULL)
	 *
	 * \see db_find_all_by_id
	 */
	public static function db_find_one_by_id($value)
	{
		/* Implemented in register() */
	}

	/**
	 * Find records by providing SQL contraints.
	 *
	 * The \c $sql argument can be:
	 *
	 * - a string: the part of the \c WHERE clause up to the end of the query
	 * - an associative array with one or more of the following keys (with string values), all optional:
	 *     - \c where for the \c WHERE clause
	 *     - \c order-by for a custom <code>ORDER BY</code> to be used instead of
	 *       the the order specified by the db_columns_order_by() method
	 *     - \c limit for the \c LIMIT clause
	 *     - \c offset for the \c OFFSET clause
	 *     - \c select, which can be be used to provide additional columns for the
	 *       \c SELECT part of the query, in addition to the standard columns
	 *       specified in db_columns()
	 *     - \c join, which will be inserted right after the \c FROM clause of
	 *       the generated query, so that it is easy to create simple joins.
	 *       Note that you should provide a complete string here just as you
	 *       would in normal regular SQL queries, i.e. including the \c JOIN and
	 *       \c ON or \c USING keywords.
	 *     - \c table-alias for the table alias used for the main table
	 *       (most useful if \c join is used as well)
	 *
	 * In both cases, <code>?str?</code>-style placeholders can be used in the
	 * values provided for the \c $sql parameter. The \c $values array will be
	 * used to fill these placeholders.
	 *
	 * \param $sql  The constraints of the SQL query
	 * \param $values  Array with placeholder values
	 *
	 * \see db_find_one_by_sql
	 */
	public static function db_find_all_by_sql($sql=null, $values=array())
	{
		/* Implemented in register() */
	}

	/**
	 * Find a single record by providing SQL contraints. See db_find_all_by_sql
	 * for a detailed description of the \c $sql parameter.
	 *
	 * \param $sql
	 *   Contraints of the SQL query
	 *
	 * \param $values
	 *   Values to be substituted in the query
	 *
	 * \return
	 *   AnewtAutoRecord instance (or NULL)
	 *
	 * \see db_find_all_by_sql
	 */
	public static function db_find_one_by_sql($sql=null, $values=array())
	{
		/* Implemented in register() */
	}

	/**
	 * Find records by column value. This is a shorthand to find records based
	 * on the value of a single column.
	 *
	 * \param $column
	 *   The name of the column to use
	 *
	 * \param $value
	 *   The value for the column
	 *
	 * \return
	 *   Array of AnewtAutoRecord instances (may be empty)
	 *
	 * \see db_find_one_by_column
	 */
	public static function db_find_all_by_column($column, $value)
	{
		/* Implemented in register() */
	}

	/**
	 * Find a single record by column value. This is a shorthand to find a record
	 * based on the value of a single column.
	 *
	 * \param $column
	 *   The name of the column to use
	 *
	 * \param $value
	 *   The value for the column
	 *
	 * \return
	 *   AnewtAutoRecord instance (or NULL)
	 *
	 * \see db_find_all_by_column
	 */
	public static function db_find_one_by_column($column, $value)
	{
		/* Implemented in register() */
	}

	/**
	 * Find records by column values. This is a shorthand to find records based
	 * on the value of multiple column (using \c AND in the \c WHERE clause).
	 *
	 * \param $columns_with_values
	 *   Associative array with column names as keys, and column values as
	 *   values.
	 *
	 * \return
	 *   Array of AnewtAutoRecord instances (may be empty)
	 *
	 * \see db_find_one_by_column
	 */
	public static function db_find_all_by_columns($columns_with_values)
	{
		/* Implemented in register() */
	}

	/**
	 * Find a single record by column values. This is a shorthand to find a record
	 * based on the value of multiple columns (using \c AND in the \c WHERE
	 * clause).
	 *
	 * \param $columns_with_values
	 *   Associative array with column names as keys, and column values as
	 *   values.
	 *
	 * \return
	 *   AnewtAutoRecord instance (or NULL)
	 *
	 * \see db_find_all_by_column
	 */
	public static function db_find_one_by_columns($columns_with_values)
	{
		/* Implemented in register() */
	}

	/**
	 * Find one or more records by primary key value.
	 *
	 * \param $class  Class name
	 *
	 * \param $just_one_result
	 *   Whether to return just one instance (or null) or an array of instances
	 *   (possibly empty)
	 *
	 * \param $values
	 *   The values to search for (primary key values)
	 *
	 * \param $connection  AnewtDatabaseConnection instance
	 *
	 * \return
	 *   A single instance (or null) or an array of instances (or empty array)
	 */
	final protected static function _db_find_by_id($class, $just_one_result, $values, $connection)
	{
		assert('is_numeric_array($values) && $values;');

		$columns = call_user_func(array($class, 'db_columns'));
		$primary_key_column = call_user_func(array($class, 'db_primary_key_column'));
		$primary_key_column_type = $columns[$primary_key_column];

		if (count($values) == 1)
		{
			/* Simple lookup by column */
			return AnewtAutoRecord::_db_find_by_columns(
				$class, $just_one_result, array($primary_key_column => $values[0]), $connection);
		}
		else
		{
			/* Lookup using IN() query */
			$where = sprintf('?column? IN (?%s[]?)', $primary_key_column_type);
			return AnewtAutoRecord::_db_find_by_sql(
				$class,
				$just_one_result,
				array('where' => $where),
				array($primary_key_column, $values),
				$connection);
		}
	}

	/**
	 * Find one or more records by providing a part of the SQL query.
	 *
	 * \param $class  Class name
	 *
	 * \param $just_one_result
	 *   Whether to return just one instance (or null) or an array of instances
	 *
	 * \param $sql  The constraints of the SQL query
	 * \param $values  Array with placeholder values
	 *
	 * \param $connection  AnewtDatabaseConnection instance
	 */
	final protected static function _db_find_by_sql($class, $just_one_result=false, $sql=null, $values=array(), $connection)
	{
		assert('is_string($class)');
		assert('is_bool($just_one_result)');
		assert('is_null($sql) || is_string($sql) || is_assoc_array($sql)');
		assert('is_array($values)');
		assert('$connection instanceof AnewtDatabaseConnection');


		/* Table alias.
		 *
		 * Several possibilities exist:
		 * - no alias,
		 * - alias provided explicitly, or
		 * - not specified but still needed. */

		$table_alias = null;

		if (is_assoc_array($sql))
		{
			/* Table alias might be provided explicitly */
			$table_alias = array_get_default($sql, 'table-alias', null);

			/* If JOINs are used, a table alias must be used for all columns in
			 * the SELECT clause to avoid ambiguous column names if the same
			 * column names are used in one of the JOINed tables. If no
			 * table-alias is provided explicitly, the table name is reused. */
			if (is_null($table_alias) && array_key_exists('join', $sql))
				$table_alias = call_user_func(array($class, 'db_table'));
		}


		/* Standard query parts */

		$sql_select = AnewtAutoRecord::_db_sql_select($class, $table_alias, $connection);
		$sql_from = AnewtAutoRecord::_db_sql_from($class, $table_alias, $connection);
		$sql_order_by = AnewtAutoRecord::_db_sql_order_by($class, $table_alias, $connection);


		/* Build the SQL query.
		 *
		 * There are three possibilities for the $sql parameter:
		 * 1. null
		 * 2. a string
		 * 3. an associative array
		 */

		if (is_null($sql))
		{
			/* No SQL: find all records */

			$sql_order_by_full = is_null($sql_order_by)
				? ''
				: sprintf('ORDER BY %s', $sql_order_by);

			$sql_full = $connection->create_sql_template
				('SELECT ?raw? FROM ?raw? ?raw?;')
				->fill($sql_select, $sql_from, $sql_order_by_full);
		}
		elseif (is_string($sql))
		{
			/* SQL string */

			$sql_with_values = $connection->create_sql_template($sql)->fillv($values);

			$sql_full =
				$connection->create_sql_template('SELECT ?raw? FROM ?raw? ?raw?;')
				->fill($sql_select, $sql_from, $sql_with_values);
		}
		else
		{
			/* Associative array with SQL */

			$sql_parts = array();


			/* SELECT and possible additions */

			$sql_select_addition = array_get_default($sql, 'select', null);
			if ($sql_select_addition)
				$sql_select = sprintf('%s, %s', $sql_select, $sql_select_addition);


			/* WHERE */

			$sql_where = array_get_default($sql, 'where', null);
			if (!is_null($sql_where))
				$sql_parts[] = sprintf('WHERE %s', $sql_where);


			/* JOIN */

			$sql_join = array_get_default($sql, 'join', null);
			if (!is_null($sql_join))
				$sql_from = sprintf('%s %s', $sql_from, $sql_join);


			/* ORDER BY */

			$sql_order_by = array_get_default($sql, 'order-by', $sql_order_by);
			if (!is_null($sql_order_by))
				$sql_parts[] = sprintf('ORDER BY %s', $sql_order_by);


			/* LIMIT. Note that "optimizing" this depending on the value of
			 * $just_one_result is impossible since it may contain a placeholder
			 * string and not a literal value. We take care of $just_one_result
			 * when fetching the result rows. */

			$sql_limit = array_get_default($sql, 'limit', null);
			if (!is_null($sql_limit))
				$sql_parts[] = sprintf('LIMIT %s', $sql_limit);


			/* OFFSET */

			$sql_offset = array_get_default($sql, 'offset', null);
			if (!is_null($sql_offset))
				$sql_parts[] = sprintf('OFFSET %s', $sql_offset);


			/* Combine */

			$sql_parts_combined =
				$connection->create_sql_template(join(' ', $sql_parts))
				->fillv($values);

			$sql_full =
				$connection->create_sql_template('SELECT ?raw? FROM ?raw? ?raw?;')
				->fill($sql_select, $sql_from, $sql_parts_combined);
		}


		/* Fetch resulting row(s) and create AnewtAutoRecord instances.
		 *
		 * The generated SQL query may contain placeholders (e.g. the string
		 * '?int?' could be somewhere in a value), but those must not be parsed
		 * by AnewtDatabaseSQLTemplate. Since the generated SQL is already fully
		 * escaped, it is passed as a single value for a ?raw? query. See
		 * bug:502916 for more information.
		 */

		if ($just_one_result)
		{
			$row = $connection->prepare_execute_fetch_one('?raw?', $sql_full);

			if (!$row)
				return null;

			return AnewtAutoRecord::_db_object_from_array($class, $row);
		}
		else
		{
			$rows = $connection->prepare_execute_fetch_all('?raw?', $sql_full);
			return AnewtAutoRecord::_db_objects_from_arrays($class, $rows);
		}
	}

	/**
	 * Find one or more records by one column value.
	 *
	 * \param $class  Class name
	 *
	 * \param $just_one_result
	 *   Whether to return just one instance (or null) or an array of instances
	 *   (possibly empty)
	 *
	 * \param $columns_with_values
	 *   Associative array with column names as keys, and column values as
	 *   values.
	 *
	 * \param $connection  AnewtDatabaseConnection instance
	 */
	final protected static function _db_find_by_columns($class, $just_one_result, $columns_with_values, $connection)
	{
		assert('is_string($class)');
		assert('is_bool($just_one_result)');
		assert('is_assoc_array($columns_with_values)');
		assert('$connection instanceof AnewtDatabaseConnection');

		$columns = call_user_func(array($class, 'db_columns'));

		$sql_where_parts = array();

		foreach ($columns_with_values as $column => $value)
		{
			if (!array_key_exists($column, $columns))
				throw new AnewtException('Class "%s" does not have a column named "%s"', $class, $column);

			if (is_null($value))
			{
				$sql_where_parts[] = '?column? IS NULL';
				$placeholder_values[] = $column;
			}
			else
			{
				$column_type = $columns[$column];
				$sql_where_parts[] = sprintf('?column? = ?%s?', $column_type);
				$placeholder_values[] = $column;
				$placeholder_values[] = $value;
			}
		}

		$sql_where = join(' AND ', $sql_where_parts);

		return AnewtAutoRecord::_db_find_by_sql($class, $just_one_result,
			array('where' => $sql_where), $placeholder_values, $connection);
	}

	/** \} */


	/** \{
	 * \name Instance Methods
	 */

	/**
	 * Save a record in the database.
	 *
	 * If the record was previously unsaved (no primary key value was set), an
	 * \c INSERT query is performed; otherwise, an \c UPDATE on the existing row
	 * is done.
	 *
	 * \see db_insert
	 * \see db_update
	 */
	final public function db_save()
	{
		$primary_key_column = $this->db_primary_key_column();

		if ($this->is_set($primary_key_column))
			$this->db_update();
		else
			$this->db_insert();
	}

	/**
	 * Insert the data as a new record in the database.
	 *
	 * In most cases you should use db_save() instead.
	 *
	 * \see db_save
	 * \see db_update
	 */
	final public function db_insert()
	{
		$connection = $this->db_connection();
		$table = $this->db_table();
		$columns = $this->db_columns();
		$columns_to_skip = array_unique(array_merge(
			$this->db_columns_skip_on_save(),
			$this->db_columns_skip_on_insert()));


		/* Loop over the columns and build an INSERT query, which contains two
		 * lists: one list of column names and one list of values to be inserted */

		$column_names_escaped = array();
		$placeholders = array();
		$placeholder_values = array();
		foreach ($columns as $column_name => $column_type)
		{
			assert('is_string($column_name)');
			assert('is_string($column_type)');

			if (in_array($column_name, $columns_to_skip))
				continue;

			if (!$this->is_set($column_name))
				continue;

			$column_names_escaped[] = $connection->escape_column_name($column_name);
			$placeholders[] = sprintf('?%s?', $column_type);
			$placeholder_values[] = $this->get($column_name);
		}


		/* No columns to save? */

		if (!$placeholders)
			throw new AnewtDatabaseException('No columns remain for INSERT query.');

		/* Execute query */

		$columns_sql = join(', ', $column_names_escaped);
		$values_sql =  $connection->create_sql_template(join(', ', $placeholders))->fillv($placeholder_values);
		$connection->prepare_execute(
			'INSERT INTO ?table? (?raw?) VALUES (?raw?)',
			$table, $columns_sql, $values_sql);


		/* Figure out the primary key value, if not set already */

		$primary_key_column = $this->db_primary_key_column();
		if ($this->is_set($primary_key_column))
			return;

		if ($connection instanceof AnewtDatabaseConnectionSQLite
			|| $connection instanceof AnewtDatabaseConnectionMySQL
			|| $connection instanceof AnewtDatabaseConnectionMySQLOld)
		{
			/* For these database backends obtaining the last insert id does not
			 * require any parameters. */
			$primary_key_value = $connection->last_insert_id();
		}
		elseif ($connection instanceof AnewtDatabaseConnectionPostgreSQL)
		{
			/* PostgreSQL uses sequences */
			$primary_key_sequence = $this->db_primary_key_sequence();
			if (is_null($primary_key_sequence))
			{
				/* Try to use PostgreSQL default sequence names */
				$primary_key_sequence = sprintf(
						'%s_%s_seq',
						$table,
						$primary_key_column);
			}
			$primary_key_value = $connection->last_insert_id($primary_key_sequence);
		}
		else
		{
			/* Fallback for unsupported backends */
			$row = $connection->prepare_execute_fetch_one(
					'SELECT MAX(?column?) AS id FROM ?table?;',
					$primary_key_column, $table);
			$primary_key_value = $row['id'];
		}

		$this->_set($primary_key_column, $primary_key_value);
	}

	/**
	 * Update an existing record in the database.
	 *
	 * In most cases you should use db_save() instead.
	 *
	 * \see db_save
	 * \see db_insert
	 */
	final public function db_update()
	{
		$connection = $this->db_connection();
		$table = $this->db_table();
		$columns = $this->db_columns();
		$columns_to_skip = array_unique(array_merge(
			$this->db_columns_skip_on_save(),
			$this->db_columns_skip_on_update()));
		$primary_key_column = $this->db_primary_key_column();

		/* Loop over the columns and build an UPDATE query */

		$placeholders = array();
		$placeholder_values = array();
		foreach ($columns as $column_name => $column_type)
		{
			assert('is_string($column_name)');
			assert('is_string($column_type)');

			if ($column_name === $primary_key_column)
				continue;

			if (in_array($column_name, $columns_to_skip))
				continue;

			if (!$this->is_set($column_name))
				continue;

			$placeholders[] = sprintf('%s = ?%s?', $connection->escape_column_name($column_name), $column_type);
			$placeholder_values[] = $this->get($column_name);
		}


		/* No columns to save? */

		if (!$placeholders)
			throw new AnewtDatabaseException('No columns remain for UPDATE query.');


		/* Execute query */

		$values_sql = $connection->create_sql_template(join(', ', $placeholders))->fillv($placeholder_values);
		$connection->prepare_execute(
			'UPDATE ?table? SET ?raw? WHERE ?column? = ?int?',
			$table, $values_sql, $primary_key_column, $this->get($primary_key_column));
	}

	/**
	 * Delete this record from the database.
	 *
	 * If the record was previously unsaved, this method does nothing. If
	 * a value for the the primary key has been set, the record is assumed to
	 * have been saved.
	 */
	final public function db_delete()
	{
		$primary_key_column = $this->db_primary_key_column();

		if (!$this->is_set($primary_key_column))
			return;

		$connection = $this->db_connection();
		$connection->prepare_execute(
			'DELETE FROM ?table? WHERE ?column? = ?int?;',
			$this->db_table(),
			$primary_key_column,
			$this->get($primary_key_column)
		);
	}

	/** \} */


	/** \{
	 * \name Helper Methods
	 */

	/**
	 * Group AnewtAutoRecord instances by the value of a column.
	 *
	 * This method can be used to group an array of AnewtAutoRecord instances by
	 * the value of a column. It handles both unique and non-unique column
	 * values, based on the \c $unique parameter.
	 *
	 * Note that the column value should be a string, or convertable to
	 * a string.
	 *
	 * The resulting array uses the column values as keys. If \c $unique is \c
	 * true, each array value is an object instance. If \c $unique is \c false,
	 * each array value is an array of object instances.
	 *
	 *   the column value as the key and the
	 *   instance itself as the value (if \c $unique is true)
	 *
	 * \param $instances
	 *   A list of AnewtAutoRecord instances
	 *
	 * \param $column
	 *   The name of the (unique) column to use as the associative array.
	 *
	 *  \param $unique
	 *    Whether the column value should be unique. This influences the
	 *    structure of the resulting array.
	 *
	 * \return
	 *   Associative array with instances by column value
	 *
	 * \see array_by_primary_key_value
	 */
	final public static function array_by_column_value($instances, $column, $unique)
	{
		assert('is_numeric_array($instances)');
		assert('is_string($column)');
		assert('is_bool($unique)');

		if (!$instances)
			return array();

		$out = array();
		foreach ($instances as $instance)
		{
			assert('$instance instanceof AnewtAutoRecord;');

			$key_value = to_string($instance->get($column));

			if ($unique)
			{
				if (array_key_exists($key_value, $out))
					throw new AnewtException('Values for column "%s" are not unique.', $column);

				$out[$key_value] = $instance;
			}
			else
			{
				if (!array_key_exists($key_value, $out))
					$out[$key_value] = array();

				$out[$key_value][] = $instance;
			}
		}
		return $out;
	}

	/**
	 * Convert a list of AnewtAutoRecord instances to an associative array
	 * indexed by primary key.
	 *
	 * \param $instances
	 *   A list of AnewtAutoRecord instances
	 *
	 * \return
	 *   An associative array with the primary key as the key and the instance
	 *   itself as the value
	 *
	 * \see array_by_column_value
	 */
	final public static function array_by_primary_key_value($instances)
	{
		assert('is_numeric_array($instances)');

		if (!$instances)
			return array();

		/* Discover the primary key column by looking at the first instance. */
		$primary_key_column = $instances[0]->db_primary_key_column();

		return AnewtAutoRecord::array_by_column_value($instances, $primary_key_column, true);
	}

	/**
	 * Create instance from array.
	 *
	 * \param $arr
	 *   An associative array with data, e.g. a row from a database
	 *
	 * \return
	 *   AnewtAutoRecord instance
	 *
	 * \see db_objects_from_arrays
	 */
	public static function db_object_from_array($arr)
	{
		/* Implemented in register() */
	}

	/**
	 * Create instances from arrays.
	 *
	 * \param $arrs
	 *   List of associative arrays with data, e.g. multiple rows from
	 *   a database
	 *
	 * \return
	 *   Array of AnewtAutoRecord instances (may be empty)
	 *
	 * \see db_object_from_array
	 */
	public static function db_objects_from_arrays($arrs)
	{
		/* Implemented in register() */
	}

	/**
	 * Converts a result row into an object instance.
	 *
	 * \param $class  Class name
	 *
	 * \param $row
	 *   The row with data (or null).
	 *
	 * \return
	 *   A reference to a new instance or null if no data was provided
	 *
	 * \see db_object_from_array
	 * \see _db_objects_from_arrays
	 */
	final protected static function _db_object_from_array($class, $row)
	{
		assert('is_assoc_array($row)');
		$out = new $class();
		$out->_seed($row);
		return $out;
	}

	/**
	 * Convert result rows into object instances.
	 *
	 * \param $class  Class name
	 *
	 * \param $rows
	 *   The rows with data (or an empty array).
	 *
	 * \return
	 *   An array with references to new instances or an empty array no data was provided
	 *
	 * \see db_objects_from_arrays
	 * \see _db_objects_from_array
	 */
	final protected static function _db_objects_from_arrays($class, $rows)
	{
		assert('is_numeric_array($rows)');

		$out = array();
		foreach ($rows as $row)
			$out[] = AnewtAutoRecord::_db_object_from_array($class, $row);

		return $out;
	}

	/** \} */
}

?>
