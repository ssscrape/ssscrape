<?php

/*
 * Anewt, Almost No Effort Web Toolkit, gpc module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/* TODO: revise documentation */

/**
 * This class allows you to get type-safe data from GET, POST and COOKIE values.
 * All methods are static.
 */
class AnewtGPC
{
	/* All methods are static... */

	/**
	 * Constructor throws an error. All methods are static and should not be
	 * called on object instances.
	 */
	private function __construct()
	{
		throw new Exception('The AnewtGPC class only provides static methods.');
	}

	/* Integers */

	/**
	 * Get an integer from the GET request data.
	 *
	 * \param $key The name of the integer.
	 * \param $default The default value to return if no valid integer was
	 * found. If you omit this parameter, null is used.
	 *
	 * \return A valid integer or the default value.
	 */
	public static function get_int($key, $default=null)
	{
		return array_get_int($_GET, $key, $default);
	}

	/**
	 * Gets an integer from the POST request data.
	 *
	 * \param $key The name of the integer.
	 * \param $default The default value to return if no valid integer was
	 * found. If you omit this parameter, null is used.
	 *
	 * \return A valid integer or the default value.
	 */
	public static function post_int($key, $default=null)
	{
		return array_get_int($_POST, $key, $default);
	}

	/**
	 * Gets an integer from the COOKIE data.
	 *
	 * \param $key The name of the integer.
	 * \param $default The default value to return if no valid integer was
	 * found. If you omit this parameter, null is used.
	 *
	 * \return A valid integer or the default value.
	 */
	public static function cookie_int($key, $default=null)
	{
		return array_get_int($_COOKIE, $key, $default);
	}


	/* Strings */

	/**
	 * Gets a string from the GET request data.
	 *
	 * \param $key The name of the string.
	 * \param $default The default value to return if no string was found. If
	 * you omit this parameter, null is used.
	 *
	 * \return A string or the default value.
	 */
	public static function get_string($key, $default=null)
	{
		return array_get_default($_GET, $key, $default);
	}

	/**
	 * Gets a string from the POST request data.
	 *
	 * \param $key The name of the string.
	 * \param $default The default value to return if no string was found. If
	 * you omit this parameter, null is used.
	 *
	 * \return A string or the default value.
	 */
	public static function post_string($key, $default=null)
	{
		return array_get_default($_POST, $key, $default);
	}

	/**
	 * Gets a string from the COOKIE data.
	 *
	 * \param $key The name of the string.
	 * \param $default The default value to return if no string was found. If
	 * you omit this parameter, null is used.
	 *
	 * \return A string or the default value.
	 */
	public static function cookie_string($key, $default=null)
	{
		return array_get_default($_COOKIE, $key, $default);
	}


	/* Booleans */


	/**
	 * Gets a boolean value from the GET data.
	 *
	 * \param $key
	 *   The name of the boolean.
	 *
	 * \param $default
	 *   The default value to return if no valid boolean was found. If you omit
	 *   this parameter, null is used.
	 *
	 * \return
	 *   A string or the default value.
	 *
	 * \see array_get_bool
	 */
	public static function get_bool($key, $default=null)
	{
		return array_get_bool($_GET, $key, $default);
	}

	/**
	 * Gets a boolean value from the POST data.
	 *
	 * \param $key
	 *   The name of the boolean.
	 *
	 * \param $default
	 *   The default value to return if no valid boolean was found. If you omit
	 *   this parameter, null is used.
	 *
	 * \return
	 *   A string or the default value.
	 *
	 * \see array_get_bool
	 */
	public static function post_bool($key, $default=null)
	{
		return array_get_bool($_POST, $key, $default);
	}

	/**
	 * Gets a boolean value from the COOKIE data.
	 *
	 * \param $key
	 *   The name of the boolean.
	 *
	 * \param $default
	 *   The default value to return if no valid boolean was found. If you omit
	 *   this parameter, null is used.
	 *
	 * \return
	 *   A string or the default value.
	 *
	 * \see array_get_bool
	 */
	public static function cookie_bool($key, $default=null)
	{
		return array_get_bool($_COOKIE, $key, $default);
	}
}

?>
