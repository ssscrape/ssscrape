<?php

/*
 * Anewt, Almost No Effort Web Toolkit, validator module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Validator to only allow integer numbers.
 *
 * This validator checks whether the value is or looks like an integer and is
 * between the minimum and maximum value.
 */
class AnewtValidatorInteger extends AnewtValidator
{
	var $_min; /**< \private Minimal value */
	var $_max; /**< \private Maximal value */

	/**
	 * Create a new AnewtValidatorInteger.
	 *
	 * \param $min
	 *   Minimum allowable value. Provide \c null for no limit. Defaults to 0.
	 *
	 * \param $max
	 *   Maximum allowable value. Provide \c null for no limit. Defaults to null.
	 */
	function __construct($min=0, $max=null)
	{
		assert('is_int($min) || is_null($min)');
		assert('is_int($max) || is_null($max)');

		parent::__construct();

		$this->_min = $min;
		$this->_max = $max;
	}

	function is_valid($value)
	{
		assert('is_string($value) || is_int($value)');

		/* Convert safe string values to integers */

		if (is_string($value))
		{
			if (!preg_match('/^-?[0-9]+$/', $value))
				return false;

			/* String format is valid, so casting will always work */
			$value = (int) $value;
		}

		/* From here on $value is an integer */

		if (!is_null($this->_min) && ($value < $this->_min))
			return false;

		if (!is_null($this->_max) && ($value > $this->_max))
			return false;

		return true;
	}
}

/**
 * Validator to only allow floating point numbers.
 *
 * This validator checks whether the value is or looks like a floating point
 * number and is between the minimum and maximum value.
 */
class AnewtValidatorFloat extends AnewtValidator
{
	var $_min; /**< \private Minimal value */
	var $_max; /**< \private Maximal value */

	/**
	 * Create a new AnewtValidatorFloat.
	 *
	 * \param $min
	 *   Minimum allowable value. Provide \c null for no limit. Defaults to 0.
	 *
	 * \param $max
	 *   Maximum allowable value. Provide \c null for no limit. Defaults to null.
	 */
	function __construct($min=0.0, $max=null)
	{
		assert('is_int($min) || is_float($min) || is_null($min)');
		assert('is_int($max) || is_float($max) || is_null($max)');

		parent::__construct();

		$this->_min = $min;
		$this->_max = $max;
	}

	function is_valid($value)
	{
		assert('is_string($value) || is_int($value) || is_float($value)');

		/* Convert safe string values to floats */

		if (is_string($value))
		{
			if (!preg_match('/^-?[0-9]*\.?[0-9]+$/', $value))
				return false;

			/* String format is valid, so casting will always work */
			$value = (float) $value;
		}

		/* From here on $value is a float */

		if (!is_null($this->_min) && ($value < $this->_min))
			return false;

		if (!is_null($this->_max) && ($value > $this->_max))
			return false;

		return true;
	}
}

?>
