<?php

/*
 * Anewt, Almost No Effort Web Toolkit, smarty module
 *
 * Copyright (C) 2006  Wouter Bolsterlee <uws@xs4all.nl>
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


/**
 * Smarty template abstraction. This class wraps a Smarty instance with some
 * Anewt-specific helper methods.
 */
class SmartyTemplate extends AnewtContainer
{
	var $smarty; /**< Smarty instance */

	/**
	 * Constructor for the SmartyTemplate class.
	 *
	 * \param $template Optional parameter with the filename of the template to
	 * use. This is just a shortcut for $template->set('template',
	 * 'filename.tpl').
	 * \param $directories Optional parameter with up to four items that will be
	 * passed to SmartyTemplate::setup().
	 *
	 * \see setup
	 */
	function SmartyTemplate($template=null, $directories=null)
	{
		/* We really need a Smarty class  */

		if (!class_exists('Smarty'))
			require_once 'smarty/libs/Smarty.class.php';

		assert('class_exists("Smarty")');


		/* Initialize Smarty instance */

		$this->smarty = &new Smarty();


		/* Template specified? */
		if (!is_null($template))
		{
			assert('is_string($template)');
			$this->set('template', $template);
		}


		/* Directories specified? */

		if (is_null($directories))
		{
			/* No. Setup defaults. */
			$this->setup();

		} else {
			/* Yes. Pass values to setup() method */
			assert('is_numeric_array($directories)');
			assert('count($directories) <= 4');

			call_user_func_array(
					array(&$this, 'setup'),
					$directories
					);
		}
	}

	/**
	 * Configure the directories used for Smarty templates. If parameters are
	 * omitted or if null values are supplied, the following constants are used
	 * as defaults: ANEWT_SMARTY_TEMPLATE_TEMPLATE_DIR,
	 * ANEWT_SMARTY_TEMPLATE_COMPILE_DIR, ANEWT_SMARTY_TEMPLATE_CONFIG_DIR and
	 * ANEWT_SMARTY_TEMPLATE_CACHE_DIR, ANEWT_SMARTY_TEMPLATE_PLUGIN_DIR. Define
	 * these constants in a configuration file.
	 *
	 * \param $template_dir The directory used for templates. 
	 * \param $compile_dir The directory used for compiled templates. 
	 * \param $config_dir The directory used for configuration files. 
	 * \param $cache_dir The directory used for cached files. 
	 * \param $plugins_dir One or more directories where plugins are stored.
	 */
	function setup($template_dir=null, $compile_dir=null, $config_dir=null, $cache_dir=null, $plugins_dir=null)
	{
		if (is_null($template_dir))
		{
			assert('defined("ANEWT_SMARTY_TEMPLATE_TEMPLATE_DIR")');
			$template_dir = ANEWT_SMARTY_TEMPLATE_TEMPLATE_DIR;
		}
		assert('is_string($template_dir)');
		$this->smarty->template_dir = $template_dir;

		if (is_null($compile_dir))
		{
			assert('DEFINED("ANEWT_SMARTY_TEMPLATE_COMPILE_DIR")');
			$compile_dir = ANEWT_SMARTY_TEMPLATE_COMPILE_DIR;
		}
		assert('is_string($compile_dir)');
		$this->smarty->compile_dir = $compile_dir;

		if (is_null($config_dir))
		{
			assert('defined("ANEWT_SMARTY_TEMPLATE_CONFIG_DIR")');
			$config_dir = ANEWT_SMARTY_TEMPLATE_CONFIG_DIR;
		}
		assert('is_string($config_dir)');
		$this->smarty->config_dir = $config_dir;

		if (is_null($cache_dir))
		{
			assert('defined("ANEWT_SMARTY_TEMPLATE_CACHE_DIR")');
			$cache_dir = ANEWT_SMARTY_TEMPLATE_CACHE_DIR;
		}
		assert('is_string($cache_dir)');
		$this->smarty->cache_dir = $cache_dir;

		if (is_null($plugins_dir) && defined("ANEWT_SMARTY_TEMPLATE_PLUGINS_DIR"))
			$plugins_dir = ANEWT_SMARTY_TEMPLATE_PLUGINS_DIR;

		if (!is_null($plugins_dir))
		{
			if (!is_array($plugins_dir))
			{
				assert('is_string($plugins_dir)');
				$plugins_dir = array($plugins_dir);
			}
			$this->smarty->plugins_dir = array_merge($this->smarty->plugins_dir, $plugins_dir);
		}

	}

	/**
	 * Renders this template to a string.
	 *
	 * \param $template The filename of the template to use.
	 *
	 * \return The resulting string after rendering by Smarty.
	 */
	function render($template=null)
	{
		if (is_null($template))
			$template = $this->get('template');

		assert('is_string($template)');
		$this->set('template', $template);

		/* Callback */
		$this->before_render();

		/* Fill the $smarty instance with data */
		$values = $this->to_array(true); // greedy export
		$this->smarty->assign($values);

		/* Run smarty! */
		return $this->smarty->fetch($template);
	}

	/**
	 * Directly renders this template.
	 *
	 * \param $template The filename of the template to use.
	 *
	 * \see render()
	 */
	function display($template=null)
	{
		echo $this->render($template);
	}

	/**
	 * Callback method before rendering starts.
	 *
	 * This method does nothing by default. Override it if you want to do some
	 * special stuff before the actual rendering starts.
	 */
	function before_render()
	{
		/* Do nothing by default. */
	}

	/**
	 * Checks whether a given template is available.
	 *
	 * \param $template The filename of the template to use.
	 */
	function template_is_available($template=null)
	{
		if (is_null($template))
			$template = $this->get('template');

		assert('is_string($template)');
		$path = sprintf('%s/%s', $this->smarty->template_dir, $template);
		return realpath($path) !== false;
	}
}

?>
