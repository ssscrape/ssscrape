<?php

/*
 * Anewt, Almost No Effort Web Toolkit, core module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * This class provides static methods for easy URL manipulation.
 */
class URL {

	/**
	 * Join all parameters into a URL path. This means all slashes are
	 * normalized, removing all double slashes. If the first paramater has
	 * a leading slash, the resulting string will also have a leading slash. If
	 * it doesn't, the resulting string won't have one either.
	 *
	 * \param $parts
	 *   Any number of string parameters (or an array)
	 *
	 * \return
	 *   The resulting URL path
	 *
	 * \see URL::join_ext
	 */
	static function join($parts=null)
	{
		$args = func_get_args();
		$num_args = func_num_args();

		/* Accept one single array argument too */
		if (($num_args == 1) && is_numeric_array($args[0]))
			$args = $args[0];

		assert('is_numeric_array($args)');

		$use_leading_slash = $args && is_string($args[0]) && str_has_prefix($args[0], '/');
		$use_trailing_slash = false; // decide later

		$list = array();
		while ($args)
		{
			$arg = array_shift($args);
		
			if (is_int($arg))
				$arg = (string) $arg;

			assert('is_string($arg)');

			/* Check the last item for a trailing slash */
			if (!$args)
			{
				/* This is the last item */
				$use_trailing_slash = str_has_suffix($arg, '/');
			}

			/* Strip leading slashes */
			if (str_has_prefix($arg, '/'))
				$arg = preg_replace('#^/*(.*)$#', '\1', $arg);

			/* Strip trailing slashes */
			if (str_has_suffix($arg, '/'))
				$arg = preg_replace('#^(.*?)/+$#', '\1', $arg);

			/* Add to the list */
			$list[] = $arg;
		}

		/* Only non-whitespace strings are taken into account */
		$list = str_all_non_white($list);
		$joined = join('/', $list);

		/* Special case for empty results */
		if (strlen($joined) == '')
			return ($use_leading_slash || $use_trailing_slash)
				? '/'
				: '';

		/* Leading slash */
		if ($use_leading_slash)
			$joined = '/' . $joined;

		/* Trailing slash */
		if ($use_trailing_slash)
			$joined = $joined . '/';

		return $joined;
	}

	/**
	 * Join all parameters into a URL path with an extension. This function does
	 * the same as URL::join, but uses the last parameter as a file extension.
	 *
	 * \param $parts
	 *   Any number of string parameters (or an array)
	 *
	 * \param $ext
	 *   The extension to append to the path. All strings are accepted and
	 *   a leading dot is taken care of, so that both "txt" and ".txt" work
	 *   correctly.
	 *
	 * \return
	 *   The resulting URL path
	 *
	 * \see URL::join
	 */
	static function join_ext($parts, $ext=null)
	{
		$args = func_get_args();
		$num_args = func_num_args();

		/* Accept one single array argument too */
		if (($num_args == 1) && is_numeric_array($args[0]))
			$args = $args[0];

		/* Require at least 2 parameters */
		assert('is_numeric_array($args)');
		assert('count($args) >= 2');

		$ext = array_pop($args);
		assert('is_string($ext)');
		$ext = str_strip_prefix($ext, '.');

		$path = URL::join($args);

		return sprintf('%s.%s', $path, $ext);
	}

	/**
	 * Constructs an URL that can be used for HTTP GET requests. By specifying
	 * a base name and an array containing all GET parameters, a new string can
	 * be constructed.
	 *
	 * \param $location string The base url to use.
	 * \param $parameters array An associative array with all GET parameters.
	 *
	 * \return Returns the generated URL string.
	 *
	 * \sa URL::parse
	 */
	static function unparse($location=null, $parameters)
	{
		if (is_null($location))
			$location = $_SERVER['PHP_SELF'];

		assert('is_string($location)');
		assert('is_assoc_array($parameters)');

		$list = array();
		foreach ($parameters as $n => $v)
			$list[] = sprintf('%s=%s', urlencode($n), urlencode($v));

		$suffix = count($list)>0 ? '?'.implode('&', $list) : '';
		return $location . $suffix;
	}

	/**
	 * Parses an URL and returns the base url and all GET parameters. This is
	 * the inverse operation of inverse URL::unparse() and can be used to parse
	 * an url into a path and query parameters.
	 *
	 * \param $url
	 *   string The url to parse.
	 *
	 * \return
	 *   Returns a tuple (2-item array) with the path component at index 0, and
	 *   an associative array of all query parameters at index 1.
	 *
	 * \sa
	 *   URL::unparse
	 */
	static function parse($url)
	{
		/* Sanitize input */
		assert('is_string($url)');
		$url = str_replace('&amp;', '&', $url);
		$url = urldecode($url);

		/* No query string? Don't do anything fancy. */
		if (strpos($url, '?') === false)
			return array($url, array());

		/* Yuck, a query string */
		list ($path, $query) = explode('?', $url, 2);
		$parameters = array();
		if (strlen($query) > 0) {
			foreach (explode('&', $query) as $pair) {
				$parts = explode('=', $pair, 2);
				$num_items = count($parts);
				if ($num_items == 2) {
					/* Both name and value */
					list ($name, $value) = $parts;
					$parameters[$name] = $value;
				} else {
					/* No value */
					$name = $parts[0];
					$parameters[$name] = null;
				}
			}
		}

		return array($path, $parameters);
	}
}

?>
