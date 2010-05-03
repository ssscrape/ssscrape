<?php

/*
 * Anewt, Almost No Effort Web Toolkit, core module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * The Request class contains request information. This class provides several
 * static methods that can be used to get information about the current HTTP
 * request.
 */
final class Request
{
	/**
	 * The constructor throws an error. This class only provides static methods.
	 */
	function Request()
	{
		trigger_error('Cannot create a new Request instance. The Request class
				only provides static methods.', E_USER_ERROR);
	}

	/**
	 * Alias for Request::relative_url().
	 * 
	 * \param $include_query_string
	 *   Whether the query string (the part after the question mark in HTTP GET
	 *   request should be included (optional, defaults to true)
	 * \return
	 *   The relative URL for the current request.
	 *
	 * \see Request::relative_url
	 */
	static function url($include_query_string=true)
	{
		return Request::relative_url($include_query_string);
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
	 * \see Request::canonical_url
	 */
	static function relative_url($include_query_string=true)
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
	 * Returns the canonical base URL for the current request. This includes the
	 * http part, the hostname and (optionally) port number.
	 *
	 * \return
	 *   The canonical base URL for the current request.
	 *
	 * \see Request::canonical_url
	 */
	static function canonical_base_url()
	{
		/* Protocol: http or https? */
		$is_ssl = (array_get_default($_SERVER, 'HTTPS', 'off') === 'on');
		$protocol = $is_ssl ? 'https' : 'http'; 

		/* The host */
		$host = Request::host();

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
			($is_ssl && ($port != 430))
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
	 * \see Request::relative_url
	 * \see Request::canonical_base_url
	 */
	static function canonical_url($include_query_string=true)
	{
		$canonical_base_url = Request::canonical_base_url();
		$relative_url = Request::relative_url($include_query_string);

		if ($relative_url{0} != '/')
			$relative_url = '/' . $relative_url;

		return $canonical_base_url . $relative_url;
	}

	/**
	 * Returns the method type of this request (GET or POST).
	 *
	 * \return
	 *   A string telling which type of request this is. This can be either
	 *   'GET' or 'POST'.
	 *
	 * \see Request::is_get
	 * \see Request::is_post
	 */
	static function method()
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
	 * \see Request::is_post
	 * \see Request::method
	 */
	static function is_get()
	{
		return Request::method() === 'GET';
	}

	/**
	 * Check if the request is a HTTP POST request.
	 *
	 * \return
	 *   True if the request is a POST request, false otherwise.
	 * 
	 * \see Request::is_get
	 * \see Request::method
	 */
	static function is_post()
	{
		return Request::method() === 'POST';
	}


	/**
	 * Returns the hostname as provided in the server configuration.
	 *
	 * \return Host name for the current request.
	 */
	static function host()
	{
		return array_get_default($_SERVER, 'SERVER_NAME', 'localhost');
	}

	/**
	 * Returns the host name as provided in the client's HTTP Host header. Note
	 * that this value should be treated as unsafe, since it's user-provided.
	 *
	 * \return Host name for the current request.
	 */
	static function http_host()
	{
		return array_get_default($_SERVER, 'HTTP_HOST', 'localhost');
	}

	/**
	 * Return the domain name for the current request. Heuristics are used to
	 * detect the the domain name. This will fail horribly for extremely short
	 * domain names. A domain name is assumed to be the rightmost part of the
	 * full hostname. A test on very short strings is used to detect top level
	 * domains such as .com, .nl and double ones like .co.uk. This obviously
	 * fails for longer top level domains en extremely short domain names.
	 *
	 * \return Domain name for the current request.
	 */
	static function domain()
	{
		$host = Request::host();

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
	static function referer()
	{
		return array_get_default($_SERVER, 'HTTP_REFERER', null);
	}

	/**
	 * Alias for Request::referer.
	 *
	 * \see Request::referer
	 */
	static function referrer()
	{
		return Request::referer();
	}
}

?>
