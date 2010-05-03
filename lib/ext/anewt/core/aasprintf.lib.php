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
 * The aasprintf method.
 *
 * \see aasprintf
 */

/* FIXME, this is crap ;-) */

/**
 * Returns a formatted string with named variable substitution (DOES NOT WORK!).
 * Normal sprintf-formatting specifiers can be used (eg. %s), with one important
 * modification: %(name)s should be used to specify that $data['name'] should be
 * used to fill in this variable.
 *
 * \param $format
 *   A sprintf-like format specifier with named variable names.
 *
 * \param $data
 *   An associative array containing name/value pairs to be used for
 *   substitution.
 */
function aasprintf($format, &$data) {
	assert('is_assoc_array($data)');
	$keys = array();
	$pattern = '/(?<!%)(%%)?(%%)?(%%)?(%\([^\)]+\))/';
	$parts = preg_split('/(?<!%)(%\([^\)]+\))/', $format, -1,
			PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	for ($i = 0; $i < count($parts); $i++) {
		if (preg_match_all('/^%\(([^\)]+)\)$/', $parts[$i], $matches) == 1) {
			$parts[$i] = '%';
			$name = $matches[1][0];
			/* TODO: allow default values by specifying an array value? */
			assert('array_key_exists($name, $data)');
			$keys[] = $data[$name];
		}
	}
	$format = implode('', $parts);
	return vsprintf($format, $keys);
}


?>
