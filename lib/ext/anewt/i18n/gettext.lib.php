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
 *
 * i18n utility functions.
 */


/**
 * Marks a string for translation but does return the original (untranslated)
 * string.
 *
 * \param $str
 *   The string to translate.
 *
 * \return
 *   The original string
 *
 * \see N_
 */
function gettext_noop($str) {
	assert('is_string($str)');
	return $str;
}

/**
 * Alias for gettext_noop.
 *
 * \param $str
 *   The string to translate.
 *
 * \return
 *   The original string
 *
 * \see
 *   gettext_noop
 */
function N_($str) {
	assert('is_string($str)');
	return $str;
}

/**
 * Just like _(), but strips the context (the part before the first | character)
 * if the translation is the same as the original string. This function can be
 * used to provide context to the translator.
 *
 * \param $str
 *   The string to translate.
 *
 * \return
 *   The translated string, or the original string if no translation was
 *   found (with any prefix stripped)
 *
 * \see
 *   _
 */
function Q_($str) {
	assert('is_string($str)');

	/* Try to translate normally */
	$translated = gettext($str);

	/* Translation succeeded? */
	if ($str != $translated)
		return $translated;

	/* We need to strip the context prefix when the untranslated and translated
	 * string are unique, ie. the part after the first | character. Note that
	 * strstr() returns false if the needle was not found. */
	$translated = strstr($str, '|');
	if (($translated !== false) && (strlen($translated) > 1))
		return substr($translated, 1); /* skip the | character */

	return $str;
}


/* Check for the gettext() function. If it was not found, we include the
 * fallback functions. */

if (!function_exists('gettext')) {
	anewt_include('i18n/gettext-fallback');
}


?>
