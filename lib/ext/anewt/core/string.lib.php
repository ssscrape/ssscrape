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
 * String utility functions
 */


/**
 * Test if a string contains the specified substring. Use strpos or other string
 * functions if you want to know where the substring starts.
 *
 * \param $str
 *   The string to search in.
 *
 * \param $substring
 *   The substring to find in $str.
 *
 * \return
 *   True if $str contains $substring, false otherwise.
 */
function str_contains($str, $substring)
{
	assert('is_string($str)');
	assert('is_string($substring)');

	/* Empty substring always matches */
	if (strlen($substring) == 0)
		return true;

	return strpos($str, $substring) !== false;
}

/**
 * Test if a string consists of whitespace characters only.
 *
 * \param $str
 *   The string to test.
 *
 * \return
 *   True if $str contains only whitespace, false otherwise.
 */
function str_is_whitespace($str)
{
	assert('is_string($str)');
	return strlen(trim($str)) == 0;
}

/**
 * Test if a string starts with the specified prefix.
 *
 * \param $str
 *   The string to search in.
 *
 * \param $prefix
 *   The prefix to match against $str.
 *
 * \return
 *   True if $str starts with $needle, false otherwise.
 *
 * \see str_has_suffix
 */
function str_has_prefix($str, $prefix)
{
	assert('is_string($str)');
	assert('is_string($prefix)');

	if (strlen($prefix) == 0)
		return true;

	return strpos($str, $prefix) === 0;
}

/**
 * Test if a string starts with whitespace.
 *
 * \param $str
 *   The string to test.
 *
 * \return
 *   True if $str starts with whitespace, false otherwise.
 *
 * \see str_has_prefix
 */
function str_has_whitespace_prefix($str)
{
	assert('is_string($str)');

	if (strlen($str) == 0)
		return false;

	return strlen(ltrim($str{0})) === 0;
}

/**
 * Test if a string ends with the specified suffix.
 *
 * \param $str
 *   The string to search in.
 *
 * \param $suffix
 *   The suffix to match against $str.
 *
 * \return
 *   True if $str ends with $suffix, false otherwise.
 *
 * \see str_has_prefix
 */
function str_has_suffix($str, $suffix)
{
	assert('is_string($str)');
	assert('is_string($suffix)');

	if (strlen($suffix) == 0)
		return true;

	$offset = strlen($str) - strlen($suffix);

	if ($offset < 0)
		return false;

	return strpos($str, $suffix, $offset) === $offset;
}

/**
 * Test if a string starts with whitespace.
 *
 * \param $str
 *   The string to test.
 *
 * \return
 *   True if $str starts with whitespace, false otherwise.
 *
 * \see str_has_suffix
 */
function str_has_whitespace_suffix($str)
{
	assert('is_string($str)');

	$length = strlen($str);
	if ($length == 0)
		return false;

	return strlen(ltrim($str{$length-1})) === 0;
}

/**
 * Strip a suffix from a string.
 *
 * If the string didn't end with the given suffix, this function is a no-op: the
 * string is returned unaltered.
 *
 * \param $str
 *   The string to operate on.
 *
 * \param $suffix
 *   The suffix to remove.
 *
 * \return
 *   A string with the suffix removed, if found.
 */
function str_strip_suffix($str, $suffix)
{
	assert('is_string($str)');
	assert('is_string($suffix)');
	
	if (strlen($suffix) == 0)
		return $str;

	if (str_has_suffix($str, $suffix))
	{
		$endpos = strlen($str) - strlen($suffix);
		return substr($str, 0, $endpos);
	}

	return $str;
}

/**
 * Strip a prefix from a string.
 *
 * If the string didn't start with the given prefix, this function is a no-op: the
 * string is returned unaltered.
 *
 * \param $str
 *   The string to operate on.
 *
 * \param $prefix
 *   The prefix to remove.
 *
 * \return
 *   A string with the prefix removed, if found.
 */
function str_strip_prefix($str, $prefix)
{
	assert('is_string($str)');
	assert('is_string($prefix)');

	if (strlen($prefix) == 0)
		return $str;

	if ($str == $prefix)
		return '';

	if (str_has_prefix($str, $prefix))
	{
		$startpos = strlen($prefix);
		return substr($str, $startpos);
	}

	return $str;
}

/**
 * Truncate a string to a given length, optionally adding a trailing suffix. The
 * trail can be used to indicate that the string was truncated (e.g. an
 * ellipsis). The resulting string is guaranteed to be no longer than the
 * $length specified, so even with huge words you can safely use the result in
 * places where you don't have much space, e.g. intro text boxes on websites,
 * text in a textfile and database records.
 *
 * This function is heavily inspired by the smarty_modifier_truncate() function
 * written by Monte Ohrt <monte@ohrt.com> for the Smarty Template Engine, which
 * is released under the LGPL. This function differs in some ways (e.g. the
 * resulting string is never longer than the specified $length, the $middle
 * parameter is not available and default values are different). In short, this
 * version rocks harder!
 *
 * \param $str
 *   The string to operate on.
 *
 * \param $length
 *   A number indicating the maximum length the resulting string can have. This
 *   parameter is optional and defaults to 70. You can provide null if you want
 *   to provide additional parameters without specifying an explicit value.
 *
 * \param $trail
 *   A string that is appended at the resulting string, but only if the original
 *   needed truncating.
 *
 * \param $use_word_boundaries
 *   If true, strings are truncated only at word boundaries. This parameter is
 *   optional and defaults to true. Note that in case no suitable word boundary
 *   was found, the string will be truncated without respecting word boundaries!
 *
 * \return
 *   The resulting string after truncating and addition of the trail.
 */
function str_truncate($str, $length=70, $trail='...', $use_word_boundaries=true)
{
	/* Use defaults for null parameters */
	if (is_null($length))
		$length = 70;
	
	if (is_null($trail))
		$trail = '...';

	if (is_null($use_word_boundaries))
		$use_word_boundaries = true;

	/* Sanity checks */
	assert('is_string($str)');
	assert('is_int($length)');
	assert('is_string($trail)');
	assert('is_bool($use_word_boundaries)');

	/* Be clever about small lengths */
	if ($length <= 0)
		return '';

	/* Don't truncate strings that don't need it */
	if (strlen($str) <= $length)
		return $str;

	if ($use_word_boundaries)
	{
		/* We don't want to do computationally expensive regexp operations on
		 * strings that are way too long, so we cut off before using regular
		 * expresions. */
		$str = substr($str, 0, $length - strlen($trail) + 1);
		$str = preg_replace('/\s+?(\S+)?$/', '', $str);

		if (strlen($str) <= $length - strlen($trail))
		{
			/* Yay, all is fine because we found a usable word boundary! */
			return $str . $trail;
		}
		
		/* If this point is reached, no word boundary is found that can be used
		 * as a cut-off point. The only way out is to fallback to truncating
		 * without respecting word boundaries (hence no return statement here!),
		 * because we promised not to return strings longer than the $length
		 * specified by the calling code. This is different in the original
		 * smarty truncate function! */
	}

	/* Cut off, ignoring word boundaries. */
	return substr($str, 0, $length - strlen($trail)) . $trail;
}

/**
 * Return the first non-whitespace value from a list of strings.
 *
 * \param $list
 *   One ore more string parameters (or a single array).
 *
 * \return
 *   The first non-whitespace string or the empty string if no non-whitespace
 *   strings where found.
 *
 * \see str_all_non_white
 */
function str_first_non_white($list=null)
{
	$args = func_get_args();
	$num_args = func_num_args();

	if ($num_args == 0)
		return '';

	if (($num_args == 1) && is_array($args[0]))
		$args = $args[0];

	foreach ($args as $arg)
	{
		if (is_null($arg))
			continue;

		assert('is_string($arg)');

		if (strlen(trim($arg)))
			return $arg;
	}

	return '';
}

/**
 * Return all non-whitespace values from a list of strings. This function
 * returns a new array; the original array is left untouched.
 *
 * \param $list
 *   One ore more string parameters (or a single array).
 *
 * \return
 *   An array containing all non-whitespace values (this can be an empty array).
 *
 * \see str_first_non_white
 */
function str_all_non_white($list=null)
{
	$args = func_get_args();
	$num_args = func_num_args();

	if ($num_args == 0)
		return '';

	if (($num_args == 1) && is_array($args[0]))
		$args = $args[0];

	$result = array();
	foreach ($args as $arg)
	{
		if (is_null($arg))
			continue;

		assert('is_string($arg)');

		if (strlen(trim($arg)))
			$result[] = $arg;
	}

	return $result;
}


/**
 * Strip leading whitespace from a multiline string and word wrap the string at
 * the given line length. Whitespace before and after line breaks is reduced to
 * a single space. This function can be used to fixup indented multiline
 * strings.
 *
 * \param $str
 *   The string to operate on.
 *
 * \param $width
 *   Width of the wrapped text (optional, defaults to 70).
 *
 * \return
 *   The wordwrapped string.
 */
function str_join_wrap($str, $width=null)
{
	if (is_null($width)) $width = 70;

	assert('is_string($str)');
	assert('is_int($width)');

	$str = trim($str);
	$str = preg_replace('/\s*\r?\n\s*/', ' ', $str);
	return wordwrap($str, $width);
}


/**
 * Convert ampersands in a string to the corresponding XML entity. Existing
 * entities in the string are left untouched, so you won't end up with
 * things like &amp;amp;eacute; or other entities. You can use this function to
 * prepare strings for inclusion in HTML or XML documents.
 *
 * This function uses the regular expression from Amputator
 * (http://bumppo.net/projects/amputator/), written by Nat Irons.
 *
 * \param $str
 *   The string to operate on.
 *
 * \return
 *   A string with all standalone &-signs replaced by entities.
 */
function str_amputate($str)
{
	assert('is_string($str)');

	return preg_replace('
			/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w{1,8});)/i',
			'&amp;',
			$str);
}


/**
 * Render various objects to a string. This function creates a string from
 * various types of objects by applying various conversion methods. Supported
 * data types:
 * 
 * - Simple values: strings, integers, floats, boolean (0 or 1).
 * - Any object implementing a render(), to_xhtml(), to_string() or toString() method.
 * - Numerical arrays containing any of the supported types are recursively
 *   converted to string values, separated by newlines.
 *
 * An error is thrown if the passed object cannot be handled.
 *
 * \param $args
 *   Any value supported by this function. You can also pass multiple
 *   parameters.
 *
 * \return
 *   String representation of the renderered object.
 */
function to_string($args)
{
	$args = func_get_args();
	$r = array();
	foreach ($args as $arg)
	{
		/* Strings are left as-is. Easy enough. */
		if (is_string($arg))
		{
			$r[] = $arg;
			continue;
		}

		/* Null values are skipped */
		if (is_null($arg))
			continue;

		/* Numbers are converted to strings. */
		if (is_integer($arg) || is_float($arg))
		{
			$r[] = (string) $arg;
			continue;
		}

		/* Boolean values are converted to 1 or 0. */
		if (is_bool($arg))
		{
			$r[] = $arg ? '1' : '0';
			continue;
		}

		/* Numerical arrays: recursively iterate over the items */
		if (is_numeric_array($arg))
		{
			$tmp = array();
			foreach (array_keys($arg) as $key)
				$tmp[] = to_string($arg[$key]);

			$r[] = implode(NL, $tmp);
			continue;
		}

		/* Handle objects */
		if (is_object($arg))
		{
			$found = false;
			foreach (array('render', 'to_string', 'toString') as $func)
			{
				if (method_exists($arg, $func))
				{
					/* Call to_string() again because the method may not return
					 * a string but (for instance) an array. */
					$r[] = to_string($arg->$func());
					$found = true;
					break; /* break out of inner loop */
				}
			}
			if ($found)
				continue; /* continue outer loop */
		}

		/* All our attempts failed... throw an error */
		throw new AnewtException('Could not convert value to string: "%s"', $arg);
	}

	/* Yay, done. */
	return implode(NL, $r);
}

?>
