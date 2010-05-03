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
class AnewtURL
{
	/**
	 * Build a URL from the passed parameters.
	 *
	 * You can provide a single path string, or an array of strings, in which
	 * case each of the items in \c $path is a path component. The path
	 * components will be concatenated, separated by slashes.
	 *
	 * All slashes are normalized. If the first path component has a leading
	 * slash, the resulting string will also have a leading slash and if it
	 * doesn't, the resulting string won't have one either. The same goes for
	 * the trailing slash: if the last path component ends with a slash, the
	 * resulting string will have one as well.
	 *
	 * If \c $parameters is passed, a HTTP query string will be appended to the
	 * url using the this associatve array.
	 *
	 * Example:
	 *
	 * <code>$url = AnewtURL::join(array('/path/to', $file), array('foo' => 'bar'));</code>
	 *
	 * \param $path
	 *   Single string or array of strings (each item is a path component of the url)
	 *
	 * \param $parameters
	 *   Associative array used to build a query string (optional)
	 *
	 * \return
	 *   The resulting URL path
	 *
	 * \see
	 *   AnewtURL::parse
	 */
	static function build($path, $parameters=null)
	{
		/* Input sanitizing */

		if (is_string($path))
			$path_components = array($path);
		else
			$path_components = $path;

		if (is_null($parameters))
			$parameters = array();

		assert('is_numeric_array($path_components);');
		assert('is_assoc_array($parameters);');


		/* Remove empty path components */

		$path_components = str_all_non_white($path_components);


		/* Leading and trailing slashes */

		if ($path_components)
		{
			/* Path is not empty */
			$use_leading_slash = str_has_prefix($path_components[0], '/');
			$use_trailing_slash = str_has_suffix($path_components[count($path_components) - 1], '/');
		}
		else
		{
			/* Path is empty */
			$use_leading_slash = false;
			$use_trailing_slash = false;
		}


		/* Loop over url parts and clean up */

		$first_part_seen = false;
		$path_components_clean = array();
		while ($path_components)
		{
			$part = array_shift($path_components);
			assert('is_string($part)');

			$part = str_strip_prefix($part, '/');
			$part = str_strip_suffix($part, '/');

			if (!strlen($part))
				continue;

			/* Use url encoding, but the first path component may be something
			 * like "http://...", which should not be url encoded. */
			if (!$first_part_seen && str_contains($part, '://'))
			{
				$first_part_seen = true;
				$part_encoded = $part;
			}
			else
			{
				$part_encoded = urlencode($part);

				/* Url decoding also escapes slashes and some other characters
				 * we want to keep, since escaping those would disallow passing
				 * path components like "/path/to/file". A slash cannot be used
				 * in a filename anyway, so we special-case some characters. */
				$part_encoded = str_replace(
					array('%2F', '%7E'),
					array('/',   '~'),
					$part_encoded);
			}

			$path_components_clean[] = $part_encoded;
		}


		/* Build url by joining all cleaned parts, and adding a leading and
		 * trailing slash, if appropriate. */

		$url = join('/', $path_components_clean);
		if (strlen($url))
		{
			/* Path is not empty */
			if ($use_leading_slash)
				$url = sprintf('/%s', $url);

			if ($use_trailing_slash)
				$url = sprintf('%s/', $url);
		}
		elseif ($use_leading_slash || $use_trailing_slash)
		{
			/* Path is empty, a slash is required */
			$url = '/';
		}


		/* Query parameters */

		assert('is_assoc_array($parameters)');

		$parameters_escaped = array();
		foreach ($parameters as $name => $value)
		{
			if (is_null($value))
			{
				$parameters_escaped[] = urlencode($name);
			}
			else
			{
				assert('is_string($value);');
				$parameters_escaped[] = sprintf(
					'%s=%s',
					urlencode($name),
					urlencode($value)
				);
			}
		}

		if ($parameters_escaped)
			$url = sprintf('%s?%s', $url, implode('&', $parameters_escaped));

		return $url;
	}

	/**
	 * Parse a URL into a path and an array of query parameters.
	 *
	 * This is (partly) the inverse operation of inverse AnewtURL::build() and
	 * can be used to parse an url into a path and query parameters (the GET
	 * string).
	 *
	 * \param $url
	 *   string The url to parse.
	 *
	 * \return
	 *   A (path, parameters) code containing a string and an associative array.
	 *
	 * \see
	 *   AnewtURL::build
	 */
	static function parse($url)
	{
		assert('is_string($url)');

		/* Return early if no query string is found. */

		if (!str_contains($url, '?'))
			return array(urldecode($url), array());


		list ($path, $query) = explode('?', $url, 2);

		$parameters = array();
		if (strlen($query))
		{
			$pairs = explode('&', $query);

			foreach ($pairs as $pair)
			{
				$parts = explode('=', $pair, 2);

				if (count($parts) == 2)
				{
					/* Both name and value */
					list ($name, $value) = $parts;
					$parameters[urldecode($name)] = urldecode($value);
				}
				else
				{
					/* Name only (no value) */
					$name = $parts[0];
					$parameters[urldecode($name)] = null;
				}
			}
		}

		return array(urldecode($path), $parameters);
	}
}

?>
