<?php

/*
 * Anewt, Almost No Effort Web Toolkit, validator module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * \protected
 *
 * Base class for validators.
 *
 * This class does nothing by default. Real validator implementations should
 * subclass this class and implement at least the is_valid() method.
 */
abstract class AnewtValidator extends AnewtContainer
{
	/**
	 * Create a new validator instance.
	 *
	 * This method can be overridden in subclasses, e.g. to handle some values
	 * that influence the validator's behaviour.
	 *
	 * Make sure to call the parent constructor from your custom contructor!
	 */
	function __construct()
	{
		/* Do nothing. */
	}

	/**
	 * Checks for validity.
	 *
	 * The return value should be true for valid values, and false otherwise.
	 *
	 * \param $value
	 *   The value to check for validity.
	 *
	 * \return
	 *   True if the value is valid, false otherwise.
	 */
	abstract function is_valid($value);
}

?>
