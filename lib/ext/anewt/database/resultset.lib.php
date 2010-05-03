<?php

/*
 * Anewt, Almost No Effort Web Toolkit, database module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Class representing database result sets.
 *
 * Several methods are available to fetch the actual data from the database.
 */
class ResultSet
{
	/**
	 * Constructor executes the query.
	 */
	function ResultSet($sql, &$backend) {
		trigger_error('ResultSet() must be overridden', E_USER_ERROR);
	}

	/**
	 * Find out the type of the query for this ResultSet instance.
	 *
	 * \return
	 *   The type of the query. These are constants like
	 *   ANEWT_DATABASE_SQL_QUERY_TYPE_SELECT and
	 *   ANEWT_DATABASE_SQL_QUERY_TYPE_INSERT.
	 */
	function query_type()
	{
		$first_word = preg_replace('/^([a-z]+).*$/s', '\1', strtolower(trim(substr(ltrim($this->sql), 0, 10))));
		switch ($first_word)
		{
			case 'select':    return ANEWT_DATABASE_SQL_QUERY_TYPE_SELECT;
			case 'insert':    return ANEWT_DATABASE_SQL_QUERY_TYPE_INSERT;
			case 'replace':   return ANEWT_DATABASE_SQL_QUERY_TYPE_REPLACE;
			case 'update':    return ANEWT_DATABASE_SQL_QUERY_TYPE_UPDATE;
			case 'delete':    return ANEWT_DATABASE_SQL_QUERY_TYPE_DELETE;

			case 'create':    return ANEWT_DATABASE_SQL_QUERY_TYPE_CREATE;
			case 'alter':     return ANEWT_DATABASE_SQL_QUERY_TYPE_ALTER;
			case 'drop':      return ANEWT_DATABASE_SQL_QUERY_TYPE_DROP;

			case 'begin':     return ANEWT_DATABASE_SQL_QUERY_TYPE_BEGIN;
			case 'commit':    return ANEWT_DATABASE_SQL_QUERY_TYPE_COMMIT;
			case 'rollback':  return ANEWT_DATABASE_SQL_QUERY_TYPE_ROLLBACK;

			default:          return ANEWT_DATABASE_SQL_QUERY_TYPE_UNKNOWN;
		}
	}

	/**
	 * Free resources associated with this result set. You cannot use any
	 * methods on the result set instance anymore after calling this method.
	 *
	 * This method should be overridden using backend-specific code.
	 */
	function free()
	{
		/* Do nothing */
	}

	/* Fetching results */

	/**
	 * Returns the next row in this result set.
	 *
	 * \return
	 *   An associative array containing all fields of the next result row from
	 *   the result set.
	 *
	 * \see ResultSet::fetch_all
	 * \see ResultSet::fetch_many
	 */
	function fetch()
	{
		trigger_error('ResultSet::fetch() must be overridden', E_USER_ERROR);
	}

	/**
	 * Returns all remaining rows from the current resultset.
	 *
	 * \return
	 *   A numeric array containing the result rows as associative array (may be
	 *   an empty list).
	 *
	 * \see ResultSet::fetch
	 * \see ResultSet::fetch_many
	 */
	function fetch_all()
	{
		$rows = array();
		while ($row = $this->fetch())
			$rows[] = $row;

		return $rows;
	}

	/**
	 * Returns the specified number of rows from the current resultset. Note
	 * that the actual number of rows may be lower than the value specified,
	 * since there may be less rows in the set.
	 *
	 * \param $num
	 *   The number of rows to return (optional, defaults to 1).
	 *
	 * \return
	 *   A numeric array containing the result rows as associative array
	 *   (the list may contain less than $num rows, or may even be empty).
	 *
	 * \see ResultSet::fetch
	 * \see ResultSet::fetch_all
	 */
	function fetch_many($num=1)
	{
		assert('is_int($num) && $num >= 1;');

		$rows = array();
		while ($num-- > 0)
		{
			$row = $this->fetch();

			if (!$row)
				break;

			$rows[] = $row;
		}
		return $rows;
	}


	/* Result counting */

	/**
	 * Returns the number of resulting rows in this resultset. This method might
	 * not be available for some databases (it works at least with MySQL and
	 * PostgreSQL though).
	 *
	 * \return
	 *   The total number of result rows.
	 */
	function count()
	{
		trigger_error('ResultSet::count() must be overridden', E_USER_ERROR);
	}

	/**
	 * Returns the number of rows that where affected by the last executed
	 * query. This method might nog be available for some databases (it works at
	 * least with MySQL and PostgreSQL though).
	 */
	function count_affected()
	{
		trigger_error('ResultSet::count_affected() must be overridden', E_USER_ERROR);
	}


	/* Type casting */

	/**
	 * \private Deduces the data types in this result set. This method can be
	 * implemented in backends to do automatic type conversion.
	 */
	function deduce_types()
	{
	}

	/**
	 * \private Casts a row of data into native PHP data types. The array is
	 * modified in-place and no result is returned. This method can be
	 * implemented in backend to do automatic type conversion.
	 *
	 * \param $row
	 *   A row of data.
	 */
	function cast_row(&$row)
	{
	}
}

?>
