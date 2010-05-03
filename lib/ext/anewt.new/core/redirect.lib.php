<?php

/*
 * Anewt, Almost No Effort Web Toolkit, core module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * \file
 *
 * The redirect function
 *
 * \see redirect
 */

/**
 * This function redirects the browser and stops further processing. There's no
 * need to call die()/exit() after calling this function, because execution
 * stops at the end of this method.
 *
 * \param $location An optional parameter specifying the url to redirect to.
 * This defaults to / (used if omitted). You can also use a numeric -1 to use
 * the HTTP referrer if available (will default to / if no referrer was sent).
 * 
 * \param $http_status An optional parameter specifying the http status to be
 * sent back to the browser. If omitted, HTTP/1.1 302: Found will be used.
 *
 * Examples:
 *
 * \code
 * redirect();        // redirect to /
 * redirect('/foo');  // redirect to /foo
 * redirect('/bar', HTTP_STATUS_MOVED_PERMANENTLY);
 *                    // redirect to /bar with a
 *                    // permanent redirect (i.e. client should remember
 *                    // the new address instead of the old one)
 * redirect(-1);      // redirect to the referring page
 * \endcode
 */
function redirect($location='/', $http_status=HTTP_STATUS_FOUND) {

	/* Use the referring page (or the root url if not available) when the
	 * special value -1 was specified */
	if ($location === -1)
		$location = array_get_default($_SERVER, 'HTTP_REFERER', '/');

	assert('is_string($location)');
	assert('is_int($http_status)');
	assert('($http_status == HTTP_STATUS_FOUND)
			|| ($http_status == HTTP_STATUS_MOVED_PERMANENTLY)');

	/* Only send custom HTTP header if it's not the default */
	if ($http_status != HTTP_STATUS_FOUND)
		header(sprintf('HTTP/1.1 %03d', $http_status));

	header('Location: ' . $location);
	die();
}

?>
