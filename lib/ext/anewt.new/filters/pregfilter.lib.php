<?php

/*
 * Anewt, Almost No Effort Web Toolkit, filters module
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


anewt_include('filters/filter');


/**
 * Filter to transform a string to all Preg.
 */
class PregFilter extends Filter {

	protected $regexp;
	protected $replace;

	/**
	 * Constructor.
	 *
	 * \param $regexp
	 *   The regular expression. This is the first argument to preg_replace.
	 * \param $replace
	 *   The replacement value. This is the second argument to preg_replace.
	 */
	function __construct($regexp, $replace)
	{
		assert('is_string($regexp)');
		assert('is_string($replace)');

		$this->regexp = $regexp;
		$this->replace = $replace;
	}
	/**
	 * Applies the PregFilter to the passed value.
	 *
	 * \param $value The value to filter.
	 *
	 * \return The string filtered through preg_replace.
	 */
	function filter($value) {
		assert('is_string($value)');
		return preg_replace($this->regexp, $this->replace, $value);
	}
}

?>
