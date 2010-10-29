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
 * Remove all quote-obfuscation introduced by evil PHP settings. We know
 * perfectly well what data needs to be escaped...
 */


/**
 * Recursively calls stripslashes on strings and arrays.
 *
 * \param $value
 *   A string or array to operate on.
 *
 * \return
 *   The parameter with stripslashes() applied to values.
 */
function stripslashes_recursive($value) {
	if (is_array($value))
		return array_map('stripslashes_recursive', $value);
	
	return stripslashes($value);
}


// set_magic_quotes_runtime(0);

if (get_magic_quotes_gpc()) {
	$_GET = array_map('stripslashes_recursive', $_GET);       /**< Clean GET */
	$_POST = array_map('stripslashes_recursive', $_POST);     /**< Clean POST */
	$_COOKIE = array_map('stripslashes_recursive', $_COOKIE); /**< Clean COOKIE */
}

?>
