<?php

/*
 * Anewt, Almost No Effort Web Toolkit, core module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * \file
 *
 * Array helper functions
 */


/**
 * Test if the supplied argument is a numeric array (list).
 *
 * For performance reasons, this function only checks the first element of the
 * array by default.
 *
 * \param $arr
 *   The array to test
 * \param $check_all
 *   Whether to check all elements instead of just the first (optional, defaults
 *   to false)
 *
 * \return
 *   Returns true if $arr is a numeric array, false otherwise.
 */
function is_numeric_array($arr, $check_all=false)
{
	if (!is_array($arr))
		return false;

	if (count($arr) == 0)
		return true;

	if ($check_all)
	{
		foreach (array_keys($arr) as $key)
			if (!is_int($key))
				return false;
	} else
	{
		reset($arr);
		return is_int(key($arr));
	}

	return true;
}


/**
 * Test if the supplied argument is an associative array (dictionary, hash).
 *
 * For performance reasons, this function only checks the first element of the
 * array by default.
 *
 * \param $arr
 *   The array to test
 * \param $check_all
 *   Whether to check all elements instead of just the first (optional, defaults
 *   to false)
 *
 * \return
 *   Returns true if $arr is an associative array, false otherwise.
 */
function is_assoc_array($arr, $check_all=false)
{
	if (!is_array($arr))
		return false;

	if (count($arr) == 0)
		return true;

	return !is_numeric_array($arr, $check_all);
}


/**
 * Returns the value in an array specified by a given key or the default value
 * if the key does not exist. This function lets you use a default value for
 * array items that do not exist. This is especially useful if you use
 * a configuration array in which not all keys are filled in.
 *
 * \param $arr
 *   The array containing the data.
 *
 * \param $key
 *   The key, given as int or string.
 *
 * \param $default
 *   The default return value if no value was found.
 *
 * \param $set_if_not_present
 *   Internal parameter, used by array_set_default (optional, do not use
 *   directly)
 *
 * \return
 *   Returns $arr[$key] if it exists, $default otherwise.
 *
 * \see array_set_default
 * \see array_get_int
 * \see array_get_string
 * \see array_get_bool
 */
function array_get_default(&$arr, $key, $default=null, $set_if_not_present=false)
{
	assert('is_array($arr)');
	assert('is_string($key) || is_int($key)');
	assert('is_bool($set_if_not_present)');

	if (array_key_exists($key, $arr))
		return $arr[$key];

	if ($set_if_not_present)
		$arr[$key] = $default;

	return $default;
}


/**
 * Returns the value in an array specified by a given key or the default value
 * if the key does not exist. Additionally, it changes the array by storing the
 * default if the key didn't exist. Note that this function alters the specified
 * $arr parameter.
*
 * \param $arr
 *   The array containing the data.
 *
 * \param $key
 *   The key, given as int or string.
 *
 * \param $default
 *   The default return value if no value was found.
 *
 * \return
 *   Returns $arr[$key] if it exists, $default otherwise.
 *
 * \see array_get_default
 */
function array_set_default(&$arr, $key, $default=null)
{
	return array_get_default($arr, $key, $default, true);
}


/**
 * Returns an integer value from an array specified by a given key or the
 * default value if the key does not exist. If the value is a string, it will be
 * validated and converted to an integer.
 *
 * \param $arr
 *   The array containing the data.
 *
 * \param $key
 *   The key, given as int or string.
 *
 * \param $default
 *   The default return value if no value was found.
 *
 * \return
 *   A integer value or null if no default value was specified.
 *
 * \see
 *   array_get_default
 */
function array_get_int($arr, $key, $default=null)
{
	$value = array_get_default($arr, $key, $default);

	if (is_int($value))
		return $value;
	elseif (is_string($value) && preg_match('/^-?[0-9]+$/', $value))
		return (int) $value;

	assert('is_null($default) || is_int($default)');
	return $default;
}

/**
 * Returns a boolean value from an array specified by a given key or the default
 * value if the key does not exist. If the value is a string, it will be
 * validated and converted to a boolean. Recognized strings: 1, 0, true, false,
 * yes, no, on, off.
 *
 * \param $arr
 *   The array containing the data.
 *
 * \param $key
 *   The key, given as int or string.
 *
 * \param $default
 *   The default return value if no value was found.
 *
 * \return
 *   A boolean value or null if no default value was specified.
 *
 * \see
 *   array_get_default
 *
 * \todo
 *   Write tests for this method
 */
function array_get_bool($arr, $key, $default=null)
{
	$value = array_get_default($arr, $key, $default);

	if (is_bool($value))
		return $value;

	if (preg_match('/^(1|true|yes|on)$/', $value))
		return true;

	if (preg_match('/^(0|false|no|off)$/', $value))
		return false;

	assert('is_null($default) || is_bool($default)');
	return $default;
}


/**
 * Removes a key from an array. The specified array is altered in-place.
 *
 * \param $arr
 *   The array containing the data.
 *
 * \param $key
 *   The key to unset.
 */
function array_unset_key(&$arr, $key)
{
	assert('is_array($arr)');
	assert('is_string($key)');

	if (array_key_exists($key, $arr))
		unset($arr[$key]);
}

/**
 * Unsets all items in $arr that have an index that is present in $keys. This
 * function does not have a return value: the specified array is altered
 * in-place.
 *
 * \param $arr
 *   The array containing the data (original)
 *
 * \param $keys
 *   A numeric array containing all key names to be unset.
 */
function array_unset_keys(&$arr, $keys)
{
	assert('is_array($arr)');
	assert('is_numeric_array($keys)');

	foreach ($keys as $key)
	{
		if (array_key_exists($key, $arr))
			unset($arr[$key]);
	}
}


/**
 * Alias for the array_unset_keys() function.
 *
 * \param $arr 
 *   The array containing the data (original)
 *
 * \param $keys
 *   A numeric array containing all key names to be unset.
 *
 * \see array_unset_keys
 */
function array_remove_keys(&$arr, $keys)
{
	return array_unset_keys($arr, $keys);
}


/**
 * Formats a one-dimensional associative array into an human-readable multiline
 * string. The result can be used for storage in a text file, database record or
 * email message. The format was inspired by the output of the \\G command on
 * a mysql command prompt.
 *
 * \param $arr
 *   The array to be formatted.
 *
 * \param $align_left
 *   An optional parameter specifying if the key names should be left-aligned or
 *   right-aligned. The default is to use left-aligned key names..
 *
 * \return
 *   Returns a multiline formatted string.
 */
function array_format($arr, $align_left=true)
{
	assert('is_array($arr)');

	$num_items = count($arr);


	/* Don't format empty arrays */

	if ($num_items == 0)
		return '';


	/* Find the longest key */

	$strlens = array_map('strlen', array_keys($arr));

	// Add a dummy value, because max() needs at least two parameters
	if ($num_items == 1)
		array_push($strlens, 0);

	$len = call_user_func_array('max', $strlens);
	$len++; // one extra character, because a colon character is added below


	/* Handle alignment parameter */

	$pattern = $align_left
		? "% -{$len}s %s\n"
		: "% {$len}s %s\n";


	/* Format all elements */

	$r = array();
	foreach ($arr as $name => $value)
	{
		assert('!is_array($value)');

		/* handle multi-line values correctly */
		$value = str_replace("\r\n", "\n", $value); // dos eol
		$value = str_replace("\r", "\n", $value); // mac eol
		if (strpos($value, "\n") !== false)
		{
			/* $value is multi-line */
			$leader = str_repeat(' ', $len + 1);
			$lines = split("\n", $value);
			$indented_lines = array();
			$first = true;
			foreach ($lines as $line)
			{
				if ($first)
				{
					/* Don't indent the first line */
					$indented_lines[] = rtrim($line);
					$first = false;
				} else
					$indented_lines[] = $leader . rtrim($line);
			}
			$value = implode("\n", $indented_lines);
		}

		$r[]= sprintf($pattern, $name.':', $value);
	}

	return implode($r);
}


/**
 * Checks if an array has a given key. This function does the same as
 * array_key_exists, but the arguments are swapped.
 *
 * \param $arr
 *   An array
 *
 * \param $key
 *   The key name to check for
 *
 * \return
 *   True if the key exists, false otherwise.
 */
function array_has_key($arr, $key)
{
	return array_key_exists($key, $arr);
}


/**
 * Checks if an array has a given value. This function does the same as
 * in_array, but the arguments are swapped.
 *
 * \param $arr
 *   An array
 *
 * \param $key
 *   The key name to check for
 *
 * \param $strict
 *   Boolean indicating whether type-checking should be done
 *
 * \return
 *   True if the value exists, false otherwise.
 */
function array_has_value($arr, $key, $strict=false)
{
	return in_array($key, $arr, $strict);
}


/**
 * Removes all items from the array. The passed array is modified in-place. If
 * you just want an empty array, use array() instead;
 *
 * \param $arr
 *   The array to clear
 */
function array_clear(&$arr)
{
	$arr = array();
}


/**
 * Exchanges all string keys with their associated values. This function does
 * basically the same as array_flip(), but this function does not flip key/value
 * pairs with an integer key, while array_flip() does. This function returns
 * a modified copy of your array; the original array is left untouched.
 *
 * \param $arr
 *   The array containing the data
 *
 * \return
 *   A copy of the original array with all string keys flipped
 */
function array_flip_string_keys($arr)
{
	foreach (array_keys($arr) as $key)
	{
		if (!is_int($key))
		{
			$value = $arr[$key];
			assert('is_int($value) || is_string($value)');
			$arr[$value] = $key;
			unset($arr[$key]);
		}
	}
	return $arr;
}


/**
 * Checks the types of all elements in an array against a given type
 * specification. This method can be used to easily validate the types of data
 * in an array.
 *
 * \param $arr
 *   The array containing the data. Example: (1, "foo", true)
 *
 * \param $typespec
 *   A string with letters denoting variable types. These letters are supported:
 *   a: array, b: boolean, f: float, i: integer, o: object and s: string.
 *   Example: "isb".
 *
 * \param $cast
 *   If this parameter is true, strings that look like integers are casted to
 *   real integers. Note that the original array will be modified in-place.
 *
 * \return
 *   True if all values are of the correct type; false otherwise.
 */
function array_check_types($arr, $typespec, $cast=true)
{
	/* Sanity checks */
	assert('is_array($arr)');
	assert('is_string($typespec)');
	assert('is_bool($cast)');

	/* Numbers should match */
	$howmany = count($arr);
	if ($howmany != strlen($typespec))
		return false;

	/* Check types */
	for ($i = 0; $i < $howmany; $i++)
	{
		unset ($value); // clear the reference
		$value = $arr[$i];
		$type = $typespec[$i];

		switch ($type)
		{
			/* Array */
			case 'a':
				if (!is_array($value)) return false;
				break;

			/* Boolean */
			case 'b':
				if (!is_bool($value)) return false;
				break;

			/* Float */
			case 'f':
				if (!is_float($value)) return false;
				break;

			/* Integer */
			case 'i':
				if ($cast && preg_match('/^[0-9]+$/', $value))
					$value = (int) $value;

				if (!is_int($value)) return false;
				break;

			/* Object */
			case 'o':
				if (!is_object($value)) return false;

			/* String */
			case 's':
				if (!is_string($value)) return false;
				break;

			/* Default */
			default:
				throw new AnewtException('Unknown data type "%s"', $type);
		}
	}

	return true;
}


/**
 * Convenience wrapper for array_check_types that throws an error if the array
 * didn't match the given type specification. Refer to the array_check_types
 * documentation for more information.
 *
 * \param $arr
 *   The array to check.
 *
 * \param $typespec
 *   A type specification.
 *
 * \see
 *   array_check_types
 */
function require_args(&$arr, $typespec)
{
	if (!array_check_types($arr, $typespec))
		throw new AnewtException('Invalid data typespec "%s".', $typespec);
}


/**
 * Joins all elements of an array containing strings into one single string,
 * using new line characters as glue.
 *
 * \param $arr
 *   The array to join together.
 *
 * \return
 *   A string with all items of the arrays joined together.
 */
function implode_newlines($arr)
{
	assert('is_array($arr)');
	return implode(NL, $arr);
}


/**
 * Joins all elements of an array containing strings into one single string,
 * using space characters as glue.
 *
 * \param $arr
 *   The array to join together.
 *
 * \return
 *   A string with all items of the arrays joined together.
 */
function implode_spaces($arr)
{
	assert('is_array($arr)');
	return implode(' ', $arr);
}


/**
 * Joins all elements of an array containing strings into one single string,
 * using the empty string as glue.
 *
 * \param $arr
 *   The array to join together.
 *
 * \return
 *   A string with all items of the arrays joined together.
 */
function implode_empty($arr)
{
	assert('is_array($arr)');
	return implode('', $arr);
}


/**
 * Trims all values of an array with strings. This function calls trim() on each
 * value in the array. This function operates on a copy of the array; the
 * original array is left untouched.
 * 
 * \param $arr
 *   The array containing strings to be trimmed.
 *
 * \param $charlist
 *   Optional list of characters to be trimmed (passed on unmodified to the
 *   trim() function
 *
 * \return
 *   The trimmed array.
 */
function array_trim_strings($arr, $charlist=null)
{
	assert('is_null($charlist) || is_string($charlist)');
	assert('is_array($arr)');
	
	if (is_null($charlist))
		return array_map('trim', $arr);

	array_walk($arr, '_array_trim_strings_cb', $charlist);
	return $arr;
}


/**
 * \private
 *
 * Callback function to apply trim() in-place to the specified value.
 *
 * \param &$value
 *   The string to be trimmed.
 *
 * \param $key
 *   Required by array_walk, not of any use here
 *
 * \param $charlist
 *   The characters to be trimmed. Passed on to trim()
 *
 * \see array_trim_strings
 */
function _array_trim_strings_cb(&$value, $key=null, $charlist=null)
{
	assert('is_string($value)');
	
	if ($charlist != null)
		$value = trim($value, $charlist);
	else
		$value = trim($value);
}


/**
 * Transforms a numeric array into an associative array pair-wise. The numeric
 * array is assumed to be a (key, value, key, value, ...) list. All keys must be
 * strings or integers, values can be anything. Make sure you provide an even
 * number of values in the input; if not, the last value will be silently
 * ignored.
 *
 * \param $arr
 *   Multiple values or a single array to be converted
 *
 * \return
 *   An associative array
 */
function numeric_array_to_associative_array($arr)
{
	$args = func_get_args();
	$num_args = func_num_args();

	if (($num_args == 1) && is_array($args[0]))
		$args = $args[0];

	$result = array();
	while (count($args) >= 2)
	{
		$name = array_shift($args);
		assert('is_int($name) || is_string($name)');
		$result[$name] = array_shift($args);
	}
	return $result;
}

/**
 * Sort array keys using natural order. The passed array is modified in-place.
 * This function orders an array the 'natural' way, based on key values. Keys as
 * a1, a10, a2 will sort as a1, a2, a10.
 *
 * \param $arr
 *   Reference to array to be natsorted
 *
 * \return
 *   True on success, false on failure.
 */
function natksort(&$arr)
{
	return uksort($arr, 'strnatcasecmp');
}

?>
