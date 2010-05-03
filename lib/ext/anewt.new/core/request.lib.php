<?php

/*
 * Anewt, Almost No Effort Web Toolkit, core module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * The AnewtRequest class contains request information. This class provides
 * several static methods that can be used to get information about the current
 * HTTP request.
 */
final class AnewtRequest
{
	/** \{
	 * \name URL methods
	 *
	 * These method can be used to find out various forms of the request url,
	 * e.g. relative and absolute urls, domain names, and so on.
	 */

	/**
	 * Alias for AnewtRequest::relative_url().
	 * 
	 * \param $include_query_string
	 *   Whether the query string (the part after the question mark in HTTP GET
	 *   request should be included (optional, defaults to true)
	 * \return
	 *   The relative URL for the current request.
	 *
	 * \see AnewtRequest::relative_url
	 */
	public static function url($include_query_string=true)
	{
		return AnewtRequest::relative_url($include_query_string);
	}

	/**
	 * Returns the relative URL for the current request
	 *
	 * \param $include_query_string
	 *   Whether the query string (the part after the question mark in HTTP GET
	 *   request should be included (optional, defaults to true)
	 *
	 * \return
	 *   The relative URL for the current request.
	 *
	 * \see AnewtRequest::canonical_url
	 */
	public static function relative_url($include_query_string=true)
	{
		assert('is_bool($include_query_string)');

		/* Normal case */
		if (array_has_key($_SERVER, 'REQUEST_URI'))
			$out = $_SERVER['REQUEST_URI'];

		/* Command line */
		elseif (array_has_key($_SERVER, 'argv'))
			$out = $_SERVER['argv'][0];

		/* If the above fails, this is a strange environment. IIS perhaps? ;) */
		elseif (array_has_key($_SERVER, 'QUERY_STRING'))
			$out = sprintf('%s?%s',
					$_SERVER['PHP_SELF'],
					$_SERVER['QUERY_STRING']);

		/* Fallback to PHP_SELF. */
		else
			$out = $_SERVER['PHP_SELF'];

		/* Strip off query string if needed */
		if (!$include_query_string && str_contains($out, '?'))
			$out = substr($out, 0, strpos($out, '?'));

		return $out;
	}

	/**
	 * Returns the canonical URL for the current request. This includes the
	 * http part, the hostname and (optionally) port number.
	 *
	 * \param $include_query_string
	 *   Whether the query string (the part after the question mark in HTTP GET
	 *   request should be included (optional, defaults to true)
	 *
	 * \return
	 *   The canonical URL for the current request.
	 *
	 * \see AnewtRequest::relative_url
	 * \see AnewtRequest::canonical_base_url
	 */
	public static function canonical_url($include_query_string=true)
	{
		$canonical_base_url = AnewtRequest::canonical_base_url();
		$relative_url = AnewtRequest::relative_url($include_query_string);

		if ($relative_url{0} != '/')
			$relative_url = '/' . $relative_url;

		return $canonical_base_url . $relative_url;
	}

	/**
	 * Returns the canonical base URL for the current request. This includes the
	 * http part, the hostname and (optionally) port number.
	 *
	 * \return
	 *   The canonical base URL for the current request.
	 *
	 * \see AnewtRequest::canonical_url
	 */
	public static function canonical_base_url()
	{
		/* Protocol: http or https? */
		$is_ssl = (array_get_default($_SERVER, 'HTTPS', 'off') === 'on');
		$protocol = $is_ssl ? 'https' : 'http'; 

		/* The host */
		$host = AnewtRequest::host();

		/* Get the port number */
		$port = array_get_int($_SERVER, 'SERVER_PORT', 80);

		/* The SERVER_PORT is not always correct. Use the one from HTTP_HOST
		 * instead, if any is set. Be very careful with this string since it is
		 * user-provided. */
		if (array_key_exists('HTTP_HOST', $_SERVER) &&
				preg_match('/:([0-9]{1,5})$/', $_SERVER['HTTP_HOST'], $matches))
			$port = (int) $matches[1];


		/* Now build the url part for the port number. It's empty if not needed
		 * because the default ports are used. */
		$port_str = (!$is_ssl && ($port != 80)) ||
			($is_ssl && ($port != 443))
			? sprintf(':%d', $port)
			: '';

		return sprintf(
				'%s://%s%s',
				$protocol,
				$host,
				$port_str
				);
	}

	/**
	 * Returns the hostname as provided in the server configuration.
	 *
	 * \return Host name for the current request.
	 */
	public static function host()
	{
		return array_get_default($_SERVER, 'SERVER_NAME', 'localhost');
	}

	/**
	 * Returns the host name as provided in the client's HTTP Host header. Note
	 * that this value should be treated as unsafe, since it's user-provided.
	 *
	 * \return Host name for the current request.
	 */
	public static function http_host()
	{
		return array_get_default($_SERVER, 'HTTP_HOST', 'localhost');
	}

	/**
	 * Return the domain name for the current request. Heuristics are used to
	 * detect the the domain name. This will fail horribly for extremely short
	 * domain names. A domain name is assumed to be the rightmost part of the
	 * full hostname. A test on very short strings is used to detect top level
	 * domains such as .com, .nl and double ones like .co.uk. This obviously
	 * fails for longer top level domains and extremely short domain names.
	 *
	 * \return Domain name for the current request.
	 */
	public static function domain()
	{
		$host = AnewtRequest::host();

		/* If it looks like an ip address we just return the IP address */
		if (preg_match('/^[0-9]{1,3}(\.[0-9]{1,3}){3}$/', $host))
		{
			return $host;
		}

		$parts = explode('.', $host);
		$num_parts = count($parts);

		if ($num_parts <= 2)
		{
			/* The hostname doesn't contain a subdomain part, or it is not
			 * a canonical hostname after all (eg. localhost). */
			$domain = $host;
		} else {
			/* There's at least 3 parts: xx.yy.zz. Return either:
			 * - yy.zz if yy is > 2 characters (example.com)
			 * - xx.yy..zz if yy is <= 2 characters (example.co.uk)
			 */
			$zz = array_pop($parts);
			$yy = array_pop($parts);
			if (strlen($yy) > 2)
			{
				$domain = sprintf('%s.%s', $yy, $zz);
			} else {
				$xx = array_pop($parts);
				$domain = sprintf('%s.%s.%s', $xx, $yy, $zz);
			}
		}

		return $domain;
	}

	/**
	 * Return the HTTP referer of the current request, if any. This method uses
	 * the HTTP_REFERER header in the HTTP request, and will return null if no
	 * referer was set.
	 *
	 * \return
	 *   The refererring url of the current request, or null.
	 */
	public static function referer()
	{
		return array_get_default($_SERVER, 'HTTP_REFERER', null);
	}

	/**
	 * Alias for AnewtRequest::referer.
	 *
	 * \see AnewtRequest::referer
	 */
	public static function referrer()
	{
		return AnewtRequest::referer();
	}

	/** \} */

	/** \{
	 * \name GET, POST, and cookie methods
	 *
	 * These methods allow you to find out the request method of the current
	 * request, and offers various type-safe methods to extract values from \c
	 * GET and \c POST parameters and cookies.
	 */

	/**
	 * Returns the method type of this request (GET or POST).
	 *
	 * \return
	 *   A string telling which type of request this is. This can be either
	 *   'GET' or 'POST'.
	 *
	 * \see AnewtRequest::is_get
	 * \see AnewtRequest::is_post
	 */
	public static function method()
	{
		$method = array_get_default($_SERVER, 'REQUEST_METHOD');
		if (!is_null($method))
		{
			if ($method == 'GET') return 'GET';
			elseif ($method == 'POST') return 'POST';
			// else: fall-through to fallback code
		}

		/* As a fallback, the contents of the $_POST array are checked. If it
		 * contains data, this is a POST request. It it's empty, it's (most
		 * likely) a GET request. */
		return (count($_POST) > 0)
			? 'POST'
			: 'GET';
	}

	/**
	 * Check if the request is a HTTP GET request.
	 *
	 * \return
	 *   True if the request is a GET request, false otherwise.
	 *
	 * \see AnewtRequest::is_post
	 * \see AnewtRequest::method
	 */
	public static function is_get()
	{
		return AnewtRequest::method() === 'GET';
	}

	/**
	 * Check if the request is a HTTP POST request.
	 *
	 * \return
	 *   True if the request is a POST request, false otherwise.
	 * 
	 * \see AnewtRequest::is_get
	 * \see AnewtRequest::method
	 */
	public static function is_post()
	{
		return AnewtRequest::method() === 'POST';
	}

	/**
	 * Check if the request is an AJAX/XMLHttpRequest.
	 *
	 * This is based on a heuristic: if the
	 * <code>X-Requested-With</code> HTTP header equals
	 * <code>XMLHttpRequest</code> the request is considered an AJAX request;
	 * otherwise it is not.
	 */
	public static function is_ajax()
	{
		return array_get_default($_SERVER, 'HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
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

	/** \} */
}

?>
