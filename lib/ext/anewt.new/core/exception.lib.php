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
	 * The \c $code parameter is optional (even though its the first parameter).
	 * If the first parameter is an integer, it is used as the \c Exception
	 * code. Otherwise, it is assumed to be a (format) string. The remaining
	 * arguments are treated just like the \c sprintf() does.
	 *
	 * Examples:
	 *
	 * - <code>throw new AnewtException('Error');</code>
	 * - <code>throw new AnewtException('Error: %s', $value);</code>
	 * - <code>throw new AnewtException(123, 'Error: %s (%s)', $value1, $value2);</code>
	 *
	 * \param $code
	 *   Optional error code.
	 * \param $fmt
	 *   A error message, optionally with sprintf format specifiers
	 * \param $args
	 *   Zero or more values passed to vsprintf
	 */
	function __construct($code, $fmt=null, $args=null)
	{
		$args = func_get_args();

		/* Use first argument as code only if it is an integer */
		$code = null;
		if (is_int($args[0]))
			$code = array_shift($args);

		/* Treat remaining arguments like sprintf() */
		$fmt = array_shift($args);
		assert('is_string($fmt);');
		$message = vsprintf($fmt, $args);

		if (is_null($code))
			parent::__construct($message);
		else
			parent::__construct($message, $code);
	}
}

?>
