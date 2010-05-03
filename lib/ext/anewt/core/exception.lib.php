<?php

/*
 * Anewt, Almost No Effort Web Toolkit, core module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Base class for exceptions thrown by Anewt.
 */
class AnewtException extends Exception
{
	/**
	 * Create a new AnewtException.
	 *
	 * \param $fmt
	 *   A error message, optionally with sprintf format specifiers
	 * \param $args
	 *   Zero or more values passed to vsprintf
	 */
	function __construct($fmt, $args=null)
	{
		$args = func_get_args();
		$fmt = array_shift($args);
		assert('is_string($fmt);');
		parent::__construct(vsprintf($fmt, $args));
	}
}

?>
