<?php

/*
 * Anewt, Almost No Effort Web Toolkit, filters module
 *
 * Copyright (C) 2005  Wouter Bolsterlee <uws@xs4all.nl>
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
 * Filter to transform a string to all lowercase.
 */
class LowercaseFilter extends Filter {

	/**
	 * Applies the LowercaseFilter to the passed value.
	 *
	 * \param $value The value to filter.
	 *
	 * \return The filtered string with all characters converted to lowercase.
	 */
	function filter($value) {
		assert('is_string($value)');
		return strtolower($value);
	}
}

?>
