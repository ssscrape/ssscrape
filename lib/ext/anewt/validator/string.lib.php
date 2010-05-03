<?php

/*
 * Anewt, Almost No Effort Web Toolkit, validator module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */

/**
 * Validator to check the length of a string.
 */
class AnewtValidatorLength extends AnewtValidator
{
	var $_min; /**< \private Minimal value */
	var $_max; /**< \private Maximal value */

	/**
	 * Create a new AnewtValidatorLength instance.
	 *
	 * \param $min
	 *   Minimum allowable value. Provide \c null for no limit.
	 *
	 * \param $max
	 *   Maximum allowable value. Provide \c null for no limit.
	 */
	function __construct($min=null, $max=null)
	{
		assert('is_int($min) || is_null($min)');
		assert('is_int($max) || is_null($max)');

		parent::__construct();

		$this->_min = $min;
		$this->_max = $max;
	}

	function is_valid($value)
	{
		assert('is_string($value)');

		if (!is_null($this->_min) && (strlen($value) < $this->_min))
			return false;

		if (!is_null($this->_max) && (strlen($value) > $this->_max))
			return false;

		return true;
	}
}


/**
 * Validator to check a value against a regular expression.
 */
class AnewtValidatorPreg extends AnewtValidator
{
	var $_pattern; /**< \private The regular expression used in this validator. */

	/**
	 * Create an AnewtValidatorPreg instance.
	 *
	 * \param $pattern
	 *   A regular expression to use for validating.
	 */
	function __construct($pattern)
	{
		parent::__construct();

		/* Pattern must be a string */
		assert('is_string($pattern)');

		/* At least one opening and one closing delimiter */
		assert('strlen($pattern) > 2');

		/* Require delimiter characters to be the same. Note that pattern
		 * modifiers can be placed after the closing delimiter. */
		assert('strpos($pattern, $pattern[0], 1) !== false');

		$this->_pattern = $pattern;
	}

	function is_valid($value)
	{
		assert('is_string($value)');
		return (bool) preg_match($this->_pattern, $value);
	}
}


/**
 * Validator to check for valid mail addresses.
 *
 * Checking email address reliably is not really doable, but this validator does
 * a fair job detecting malformed mail addresses.
 */
class AnewtValidatorEmail extends AnewtValidatorPreg
{
	/**
	 * Create an AnewtValidatorEmail instance.
	 */
	function __construct()
	{
		parent::__construct(
			'/^[-a-zA-Z0-9_][-.a-zA-Z0-9_+]*@([a-z0-9][-a-z0-9]*\.)+[a-z]+$/');
	}
}

/**
 * Validator to check for repeated strings, usually for password checking.
 *
 * Add this validator to the second password control, and pass the the first
 * control when creating the AnewtValidatorRepeat instance.
 */
class AnewtValidatorRepeat extends AnewtValidator
{
	/**
	 * Reference to the other control.
	 */
	private $other_control;

	/**
	 * Create an AnewtValidatorRepeat instance.
	 *
	 * \param $other_control
	 *	 The other control to compare the value against.
	 */
	function __construct($other_control)
	{
		assert('$other_control instanceof AnewtFormControl');
		$this->other_control = $other_control;
	}

	function is_valid($value)
	{
		return $value == $this->other_control->get('value');
	}
}

?>
