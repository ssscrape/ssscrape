<?php

/*
 * Anewt, Almost No Effort Web Toolkit, i18n module
 *
 * Copyright (C) 2006  Wouter Bolsterlee <uws@xs4all.nl>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA+
 */



/**
 * \file
 *   Fallback i18n utility functions.
 *
 *   This file provides i18n utility functions that are used if gettext support
 *   was not available. The functions are do-nothing stubs that will only cause
 *   your application to not break directly!
 *
 * \todo
 *   Provide a way to integrate php-gettext in here
 */


/**
 * Lookup a string. This method does nothing by default, it just returns the
 * string passed to this function.
 *
 * Note that this function should be provided by PHP to work, this is just
 * a fallback do-nothing stub!
 *
 * \param $str
 *   The string to lookup.
 *
 * \return
 *   The translated string, or the original string if no translation was
 *   found.
 *
 * \see
 *   _
 */
function gettext($str) {
	assert('is_string($str)');
	return $str;
}

/**
 * Alias for gettext().
 *
 * Note that this function should be provided by PHP to work, this is just
 * a fallback do-nothing stub!
 *
 * \param $str
 *   The string to lookup.
 *
 * \return
 *   The translated string, or the original string if no translation was
 *   found.
 *
 * \see
 *   gettext
 */
function _($str) {
	assert('is_string($str)');
	return $str;
}

/**
 * Lookup a singular or plural string. The result depends on the number.
 *
 * Note that this function should be provided by PHP to work, this is just
 * a fallback do-nothing stub!
 *
 * \param $str_singular
 *   The string to lookup (singular).
 *
 * \param $str_plural
 *   The string to lookup (plural).
 *
 * \param $number
 *   The number.
 *
 * \see
 *   gettext
 */
function ngettext($str_singular, $str_plural, $number) {
	assert('is_string($str_singular)');
	assert('is_string($str_plural)');
	assert('is_int($number)');

	/* This at least works correctly for some western European languages,
	 * including English. It seems better to have it at least work for
	 * English than not at all... */
	return ($number == 1)
		? $str_singular
		: $str_plural;
}

/**
 * Sets the textdomain.
 *
 * Note that this function should be provided by PHP to work, this is just
 * a fallback do-nothing stub!
 *
 * \param $domain
 *   The textdomain to set
 */
function textdomain($domain) {
	assert('is_string($domain)');
	return $domain;
}

/**
 * Binds a textdomain to a directory.
 *
 * Note that this function should be provided by PHP to work, this is just
 * a fallback do-nothing stub!
 *
 * \param $domain
 *   The textdomain.
 *
 * \param $directory
 *   The directory.
 */
function bindtextdomain($domain, $directory) {
	assert('is_string($domain)');
	assert('is_string($directory)');
	return realpath($directory);
}

/**
 * Lookup a string using a custom domain.
 *
 * Note that this function should be provided by PHP to work, this is just
 * a fallback do-nothing stub!
 *
 * \param $domain
 *   The textdomain.
 *
 * \param $str
 *   The string to lookup.
 *
 * \return
 *   The translated string.
 *
 * \see gettext
 * \see dngettext
 */
function dgettext($domain, $str) {
	assert('is_string($domain)');
	assert('is_string($str)');
	return $str;
}

/**
 * Lookup a singular or plural string using a custom domain.
 *
 * Note that this function should be provided by PHP to work, this is just
 * a fallback do-nothing stub!
 *
 * \param $domain
 *   The textdomain.
 *
 * \param $str_singular
 *   The string to lookup (singular).
 *
 * \param $str_plural
 *   The string to lookup.
 *
 * \param $number
 *   The number.
 *
 * \return
 *   The translated string.
 *
 * \see ngettext
 * \see dgettext
 */
function dngettext($domain, $str_singular, $str_plural, $number) {
	assert('is_string($domain)');
	assert('is_string($str_singular)');
	assert('is_string($str_plural)');
	assert('is_int($number)');
	return ngettext($str_singular, $str_plural, $number);
}

?>
