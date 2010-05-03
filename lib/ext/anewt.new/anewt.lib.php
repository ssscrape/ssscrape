<?php

/*
 * Anewt, Almost No Effort Web Toolkit
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * \file
 *
 * Anewt core file. Include this file to load Anewt.
 */

/** The base directory path for all Anewt modules */
define('ANEWT_PATH', dirname(__FILE__));

/* Load the string functions without anewt_include because
 * create_include_function and the generated include function itself depend on
 * some of these string functions */
require_once ANEWT_PATH . '/core/string.lib.php';


/**
 * Create a simple include function that loads modules. Call this method from
 * a default include file to have a anewt_include() like function for your own
 * libraries.
 *
 * \param $name
 *   Name of the function. This name will be suffixed with _include, e.g.
 *   myproject will result in the function myproject_include() being defined
 * \param $path
 *   The file path to use for inclusion
 * \param $file_suffix
 *   Filename suffix (optional, default to .lib.php)
 * \param $main_file
 *   Default filename in case the argument to the include function is
 *   a directory (optional, default to main)
 *
 * \see anewt_include
 */
function create_include_function($name, $path, $file_suffix='.lib.php', $main_file='main')
{
	assert('is_string($name)');
	assert('is_string($path)');
	assert('is_string($file_suffix)');
	assert('is_string($main_file)');
	assert('is_dir($path)');

	$function_name = sprintf('%s_include', $name);
	$path = str_strip_suffix($path, '/');

	assert('function_exists($function_name) === false');

	$function_definition = str_replace(
		array('@@FUNC@@', '@@PATH@@', '@@MAIN@@', '@@SUFFIX@@'),
		array($function_name, $path, $main_file, $file_suffix),
		'function @@FUNC@@($module_or_file)
		{
			$args = func_get_args();
			foreach ($args as $module_or_file)
			{
				$filename = \'@@PATH@@/\' . str_strip_suffix($module_or_file, \'/\');
				if (is_dir($filename)) $filename .= \'/@@MAIN@@\';
				$filename .= \'@@SUFFIX@@\';
				require_once $filename;
			}
		}'
	);

	/* Evaluate the function definition */
	eval($function_definition);
}


/* Now we can create anewt_include() and load the core module */
create_include_function('anewt', dirname(__FILE__));
anewt_include('core');

?>
