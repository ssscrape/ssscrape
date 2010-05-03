<?php

/*
 * Anewt, Almost No Effort Web Toolkit, validator module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */

anewt_include('datetime');


/**
 * Validator for dates.
 *
 * This validator only accepts valid dates in \c YYYY-MM-DD format.
 */
class AnewtValidatorDate extends AnewtValidator
{
	function is_valid($value)
	{
		assert('is_string($value)');

		$pattern = '/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/';
		if (!preg_match($pattern, $value, $matches))
			return false;

		return AnewtDateTime::is_valid_date_ymd(
			$matches[1],
			$matches[2],
			$matches[3]);
	}
}

?>
