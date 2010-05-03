<?php

/*
 * Anewt, Almost No Effort Web Toolkit, urldispatcher module
 *
 * Copyright (C) 2007  Wouter Bolsterlee <uws@xs4all.nl>
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
 * Dispatches url requests to different URLDispatcher classes, based on the
 * prefix of the url dispatched.
 *
 * This class is useful for larger sites composed of different 'modules', all
 * running in a different url prefix ('url namespace'). An example: You run
 * several news pages (view item, archive, ...) at urls starting with '/news'.
 * You want to have all news-related dispatchin code into a separate dispatcher
 * class, located in a file of its own.
 *
 * To use this class, you should override this class and implement
 * setup_mappings() and optionally load_module().
 *
 * Your setup_mappings() method should set the 'default-class' property to the
 * name of the default dispatcher class to use. You may also set the
 * 'default-module' property (see below). Additionally, this methods should
 * contain calls to add_prefix to define which module/class name should be used
 * for the supplied url prefix.
 *
 * Override and implement the load_module() method if the dispatcher class names
 * used in your add_prefix() calls are not yet available when the dispatcher is
 * invoked. A common and simple implementation of the load_module() should make
 * sure the class name is loaded, e.g. by including the right file. (Hint: use
 * a foo_include() function; see create_include_function() for details.)
 *
 * Note that the dispatchers will have the url prefix stripped off when called!
 * Coming back to the news example, this means that the url maps of your news
 * UrlDispatcher class should not include the '/news' part in the urlmaps.
 *
 * \see URLDispatcher
 * \see create_include_function
 */
class MultiURLDispatcher extends AnewtContainer {

	var $prefix_to_dispatcher_mapping; /**< \private URL maps */

	/**
	 * Constructor for a new MultiURLDispatcher.
	 */
	function MultiURLDispatcher() {
		$this->prefix_to_dispatcher_mapping = array();
		$this->setup_mappings();
	}

	/**
	 * Add a prefix to the list of prefix mappings. Call this method from your
	 * setup_mappings() method.
	 * 
	 * \param $prefix
	 *   The url prefix to match
	 *
	 * \param $module_name
	 *   The module name passed to load_module() (optional, specify null if you
	 *   don't need it)
	 *
	 * \param $class_name
	 *   The class name of the dispatcher to instantiate and invoke.
	 *
	 * \see MultiURLDispatcher::setup_mappings
	 * \see MultiURLDispatcher::load_module
	 */
	function add_prefix($prefix, $module_name=null, $class_name) {
		assert('is_string($prefix)');
		assert('is_string($class_name)');
		assert('is_null($module_name) || is_string($module_name)');

		/* Make sure the prefix does not start or end with a / character */
		$prefix = str_strip_prefix($prefix, '/');
		$prefix = str_strip_suffix($prefix, '/');

		/* Just store a list of mappings */
		$this->prefix_to_dispatcher_mapping[$prefix] = array($module_name, $class_name);
	}

	/**
	 * Dispatch an URL to the correct handlers. See the documentation on
	 * URLDispatcher::dispatch() for more information on the parameters.
	 *
	 * \param $url
	 *   The url to dispatch (optional, defaults to null).
	 *
	 * \see URLDispatcher::dispatch
	 */
	function dispatch($url=null) {

		if (is_null($url)) $url = AnewtRequest::relative_url();

		assert('is_string($url)');

		/* Get the default settings */
		$module_name = $this->_getdefault('default-module', null);
		$class_name = $this->_get('default-class');

		$skip_path_components = 0;

		/* Iterate over the mappings and override the default if the mapping matches */
		$test_url = str_strip_prefix($url, '/');
		foreach ($this->prefix_to_dispatcher_mapping as $prefix => $mapping) {

			/* Try to match the prefix. Add a trailing slash, otherwise the url
			 * /newsxyz would also match the /news mapping, which is not
			 * intended behaviour. */
			$test_prefix = $prefix . '/';
			if (str_has_prefix($test_url, $test_prefix)) {

				/* The prefix matches the url */
				list ($module_name, $class_name) = $mapping;
				$skip_path_components = count(explode('/', $prefix));

				break;
			}
		}

		/* Load module (if supplied) */
		if (!is_null($module_name))
			$this->load_module($module_name);

		/* Create and invoke dispatcher */
		$dispatcher = &new $class_name();
		$dispatcher->dispatch($url, $skip_path_components);
	}


	/* Stubs that should be overridden */

	/**
	 * Set up the url mappings. You must override this method.
	 *
	 * \see MultiURLDispatcher
	 */
	function setup_mappings() {
		trigger_error('MultiURLDispatcher::setup_mappings() should be overridden.', E_USER_ERROR);
	}

	/**
	 * Load a module specified in a prefix mapping. Override this method to load
	 * needed dispatcher classes on demand.
	 *
	 * \param $module_name
	 *   Name of the module to load (string)
	 *
	 * \see MultiURLDispatcher
	 */
	function load_module($module_name) {
		/* Do nothing by default */
	}
}

?>
