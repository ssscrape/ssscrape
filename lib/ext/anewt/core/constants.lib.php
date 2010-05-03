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
 * This files defines some common constants and some helper functions.
 * */


/* Newline characters, easier to type than "\n" */

/**< Newline character */
define('NL', "\n");
/**< Newline character */
define('LF', "\n");
/**< Carriage return character */
define('CR', "\r");
/**< Carriage return character followed by newline character */
define('CRLF', "\r\n");

/* XHTML Document Type Definitions */

/** Document Type Definition for XHTML 1.0 Strict */
define('DTD_XHTML_1_0_STRICT', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">');
/** Document Type Definition for XHTML 1.0 Transitional */
define('DTD_XHTML_1_0_TRANSITIONAL', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">');
/** Document Type Definition for XHTML 1.0 Frameset */
define('DTD_XHTML_1_0_FRAMESET', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">');


/* HTTP Status Codes. See http://www.w3.org/Protocols/rfc2616/rfc2616.html */

/** HTTP Status OK */
define('HTTP_STATUS_200', 200);
/** HTTP Status OK */
define('HTTP_STATUS_OK', 200);

/** HTTP Status Moved */
define('HTTP_STATUS_301', 301);
/** HTTP Status Moved */
define('HTTP_STATUS_MOVED_PERMANENTLY', 301);
/** HTTP Status Moved */
define('HTTP_STATUS_MOVED', 301);

/** HTTP Status Found */
define('HTTP_STATUS_302', 302);
/** HTTP Status Found */
define('HTTP_STATUS_FOUND', 302);

/** HTTP Status Not Modified */
define('HTTP_STATUS_304', 304);
/** HTTP Status Not Modified */
define('HTTP_STATUS_NOT_MODIFIED', 304);

/** HTTP Status Unauthorized */
define('HTTP_STATUS_401', 401);
/** HTTP Status Unauthorized */
define('HTTP_STATUS_UNAUTHORIZED', 401);

/** HTTP Status Forbidden */
define('HTTP_STATUS_403', 403);
/** HTTP Status Forbidden */
define('HTTP_STATUS_FORBIDDEN', 403);

/** HTTP Status Not Found */
define('HTTP_STATUS_404', 404);
/** HTTP Status Not Found */
define('HTTP_STATUS_NOT_FOUND', 404);

/** HTTP Status Internal Server Error */
define('HTTP_STATUS_500', 500);
/** HTTP Status Internal Server Error */
define('HTTP_STATUS_INTERNAL_SERVER_ERROR', 500);
/** HTTP Status Internal Server Error */
define('HTTP_STATUS_SERVER_ERROR', 500);

/** HTTP Status Not Implemented */
define('HTTP_STATUS_501', 501);
/** HTTP Status Not Implemented */
define('HTTP_STATUS_NOT_IMPLEMENTED', 501);

global $__anewt_http_status_strings;
/**
 * \private
 *
 * HTTP status code to string mapping.
 */
$__anewt_http_status_strings = array(
	HTTP_STATUS_200 => 'OK',
	HTTP_STATUS_301 => 'Moved Permanently',
	HTTP_STATUS_302 => 'Found',
	HTTP_STATUS_401 => 'Unauthorized',
	HTTP_STATUS_403 => 'Forbidden',
	HTTP_STATUS_404 => 'Not Found',
	HTTP_STATUS_500 => 'Internal Server Error',
	HTTP_STATUS_501 => 'Not Implemented',
);

/**
 * Convert HTTP error code into a string.
 *
 * Returns the appropriate human readable status string for the given status
 * code as per rfc2616.
 *
 * \param $http_status
 *   The HTTP status ok, e.g. \c 200
 *
 * \return
 *   A string with the status code
 */
function http_status_to_string($http_status) {
	assert('is_int($http_status)');

	global $__anewt_http_status_strings;
	return array_get_default($__anewt_http_status_strings, $http_status, 'Unknown HTTP status code');
}

/**
 * Creates an enumeration of constants. The strings supplied to this function
 * will be defined as constants with a unique value.
 *
 * Example: mkenum('TYPE_FIRST', 'TYPE_SECOND', 'TYPE_THIRD');
 *
 * Note that multiple invokations of this function results in the same values
 * being used again, but that's pointless anyway.
 *
 * \param $items
 *   One or more strings (or a single array of strings)
 */
function mkenum($items) {
	$args = func_get_args();
	$num_args = func_num_args();

	if (($num_args == 1) && is_array($args[0])) {
		$args = $args[0];
		$num_args = count($args);
	}

	$i = 0;
	foreach ($args as $item) {
		define($item, $i);
		$i++;
	}
}

?>
