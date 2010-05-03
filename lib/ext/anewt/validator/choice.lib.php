<?php

/*
 * Anewt, Almost No Effort Web Toolkit, validator module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */

/**
 * Validator to check if a choice control has valid values.
 *
 * FIXME: currently only works for single select choice controls.
 */
class AnewtValidatorChoice extends AnewtValidator
{
	var $_choice_control;	/**< \private The choice form control to check. */

	/**
	 * Create a new AnewtValidatorChoice instance.
	 *
	 * \param $choice_control
	 *   The choice control to check.
	 */
	function __construct($choice_control)
	{
		assert('$choice_control instanceof AnewtFormControlChoice;');

		parent::__construct();

		$this->_choice_control = $choice_control;
	}

	function is_valid($value)
	{
		return !$this->_choice_control->_options[$value]->get('disabled');
	}
}

?>
