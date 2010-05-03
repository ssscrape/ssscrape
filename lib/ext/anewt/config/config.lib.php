<?php

/*
 * Anewt, Almost No Effort Web Toolkit, config module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


global $_anewt_config; /* This is REQUIRED for correct scoping! */
/** \private Configuration data */
$_anewt_config = array();

/**
 * The AnewtConfig class provides static methods for configuration management.
 */
class AnewtConfig {

	/**
	 * Constructor throws an error. Only static usage of this class is allowed.
	 */
	function __construct()
	{
		trigger_error('You cannot create AnewtConfig instances. Use static
				methods instead.', E_USER_ERROR);
	}

	/**
	 * Return a variable from the configuration data.
	 *
	 * \param
	 *   $name The variable name.
	 *
	 * \return
	 *   The value of the variable.
	 */
	public static function get($name)
	{
		assert('is_string($name)');
		assert('AnewtConfig::is_set($name)');
		global $_anewt_config;
		return $_anewt_config[$name];
	}

	/**
	 * Return a variable from the configuration data, returning a default value
	 * if the value is not available.
	 *
	 * \param $name
	 *   The variable name.
	 *
	 * \param $default
	 *   The default value returned if the value was not found.
	 *
	 * \return
	 *   The value of the variable.
	 *
	 * \see array_get_default
	 */
	public static function getdefault($name, $default)
	{
		assert('is_string($name)');
		global $_anewt_config;
		return array_get_default($_anewt_config, $name, $default);
	}

	/**
	 * Store a variable in the configuration data.
	 *
	 * \param $name
	 *   The variable name.
	 * \param $value
	 *   The value of the variable.
	 */
	public static function set($name, $value)
	{
		/* Note: keep in sync with AnewtConfig::seed() */
		assert('is_string($name)');
		global $_anewt_config;
		$_anewt_config[$name] = $value;
	}

	/**
	 * Check if a variable is defined in the configuration data.
	 *
	 * \param $name
	 *   The variable name to check for.
	 *
	 * \return
	 *   True if the variable is available, false otherwise.
	 */
	public static function is_set($name)
	{
		assert('is_string($name)');
		global $_anewt_config;
		return array_has_key($_anewt_config, $name);
	}

	/**
	 * Delete a variable from the configuration data.
	 *
	 * \param $name
	 *   The name of the variable to delete.
	 */
	public static function delete($name)
	{
		assert('is_string($name)');
		global $_anewt_config;
		array_unset_key($_anewt_config, $name);
	}

	/**
	 * Set multiple values at once.
	 *
	 * \param $arr
	 *   An associative array with name/value pairs.
	 *
	 * \see AnewtConfig::to_array
	 */
	public static function seed($arr)
	{
		/* Note: keep in sync with AnewtConfig::set() */
		assert('is_assoc_array($arr)');
		global $_anewt_config;
		foreach ($arr as $name => $value)
		{
			assert('is_string($name)');
			$_anewt_config[$name] = $value;
		}
	}

	/**
	 * Return all configuration data as an array.
	 *
	 * \see AnewtConfig::seed
	 */
	public static function to_array()
	{
		global $_anewt_config;
		return $_anewt_config;
	}

	/**
	 * Load configuration data from a ini file.
	 *
	 * If the ini file contains sections, the name of the configuration settings
	 * will be the section name and the setting name with a hyphen in between,
	 * e.g. the <code>hostname=...</code> setting inside
	 * a <code>[database]</code> setting can be retrieved using
	 * <code>AnewtConfig::get('database-hostname')</code>.
	 *
	 * \param $filename
	 *   The filename of the ini file.
	 */
	public static function load_ini_file($filename)
	{
		assert('is_string($filename);');
		if (!is_readable($filename))
			throw new Exception('The ini file does not exists or is not readable.');

		$parsed = parse_ini_file($filename, true);
		if ($parsed === false)
			throw new Exception('The ini file could not be parsed.');

		$config = array();
		foreach ($parsed as $name => $value)
		{
			/* The value can be either a string (for top level ini settings), or
			 * an array (for ini settings in sections) */

			if (is_string($value))
			{
				$config[$name] = $value;
			}
			elseif (is_assoc_array($value))
			{
				$section = $name;
				foreach ($value as $name => $value)
				{
					$key = sprintf('%s-%s', $section, $name);
					$config[$key] = $value;
				}
			}
			else
			{
				assert('false; // not reached;');
			}
		}

		/* Cast integer and boolean values to the correct type */

		foreach ($config as $name => &$value)
		{
			if (preg_match('/^-?[0-9]+$/', $value))
			{
				$value = (int) $value;
			}
			elseif (preg_match('/^(1|true|yes|on)$/', $value))
				$value = true;
			elseif (preg_match('/^(0|false|no|off)$/', $value))
				$value = false;
		}

		AnewtConfig::seed($config);
	}
}

?>
