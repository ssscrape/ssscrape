<?php

/*
 * Anewt, Almost No Effort Web Toolkit, generator module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Generator class to generate sequences.
 *
 * A generator can be used to generate strings from a sequence. This is useful
 * for e.g. zebra-pattern row listings.
 */
class AnewtGenerator
{
	private $sequence;     /**< The sequence this AnewtGenerator iterates over */
	private $counter = 0;  /**< The number of times the next() method has been called */

	/**
	 * Constructs a new AnewtGenerator instance.
	 *
	 * You should pass the sequence to use, which can be an array array
	 * containing values or, alternatively, multiple parameters.
	 *
	 * Example of using a single array:
	 *
	 * <code>$g = new AnewtGenerator(array('a', 'b', 'c'));</code>
	 *
	 * Example of using multiple parameters:
	 *
	 * <code>$g = new AnewtGenerator('a', 'b', 'c');</code>
	 *
	 * \param $sequence
	 *   An array with sequence values or multiple parameters that will be used
	 *   as a sequence.
	 */
	function __construct($sequence)
	{
		$args = func_get_args();

		if (count($args) == 1 && is_array($args[0]))
			$this->sequence = $args[0];
		else
			$this->sequence = $args;

		assert('$this->sequence; // Sequence must have at least one element');
	}

	/**
	 * Returns the next value from the sequence
	 */
	public function next()
	{
		$idx = $this->counter % count($this->sequence);
		$this->counter++;
		return $this->sequence[$idx];
	}
}

?>
