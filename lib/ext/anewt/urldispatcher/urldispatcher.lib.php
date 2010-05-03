<?php

/*
 * Anewt, Almost No Effort Web Toolkit, urldispatcher module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


define('REQUIRE_INTEGER', 1);


/**
 * Dispatch URL requests to request handler methods.
 */
class URLDispatcher extends Container
{
	var $urlmaps; /**< \private URL maps */

	/**
	 * Initalizes a new URLDispatcher instance. Make sure you call this method
	 * from derived classes, if you decide to override the constructor.
	 */
	function URLDispatcher() {
		/* Initialize the url maps */
		$this->urlmaps = array();
		$this->initialize_urlmaps();
	}

	/**
	 * Initializes the url maps. Override this method to add your own maps by
	 * calling add_urlmap() for any URL you like to handle. Although you can add
	 * url maps from other parts of your code, this method provides a consistent
	 * way to define them. This method does nothing by default, which means
	 * that only the default mappings work: command_foo for the /foo url and so
	 * on.
	 *
	 * \see
	 *   add_urlmap
	 */
	function initialize_urlmaps() {}

	/**
	 * Adds a mapping to the list of url mappings.
	 *
	 * \param $command_name
	 *   The name of the command to invoke when this url mapping matches. This
	 *   should be a simple string, eg. "archive". This string will be prefixed
	 *   with "command_" and called if the url matches, eg. the method
	 *   command_archive() will be called. This callback method needs to handle
	 *   the url.
	 *
	 * \param $url
	 *   The url pattern to match. Variable url parts should have a colon
	 *   prefix. Example: /news/:year/:month/:slug/comments. The year, month and
	 *   slug variables will be passed in an array to the method handling the
	 *   request if the url matches.
	 *
	 * \param $requirements
	 *   Optional parameter with regular expressions to match against the
	 *   variables in the url. Only variables matching the regular expression
	 *   will be handled by this mapping. This way you can be sure your method
	 *   will always be called with valid parameters (so you don't need to
	 *   repeat the input checking in your handler methods). Example:
	 *   array('year' => '/^\\d{4}$/', 'month' => '/^\\d{2}$/')
	 *
	 */
	function add_urlmap($command_name, $url, $requirements=null)
	{
		/* Requirements are optional */
		if (is_null($requirements))
			$requirements = array();

		/* Sanity checks */
		assert('is_string($command_name) && strlen($command_name)');
		assert('is_assoc_array($requirements)');

		/* Split the url into smaller pieces. We split on the forward slash
		 * character and put the parts in a list after stripping of leading and
		 * trailing slashes. Eg.  /foo/bar/baz/ is split in (foo, bar, baz). */
		if (is_string($url)) {
			$url = str_strip_prefix($url, '/');
			$url = str_strip_suffix($url, '/');

			/* Special case for top level urls */
			if (strlen($url) == 0) {
				$parts = array(); // no parts
			} else {
				$parts = split('/', $url);
			}
		} else {
			assert('is_array($url)');
			$parts = array_trim_strings($url, '/');;
		}

		/* Parse the pieces and parameters */
		$map = array();
		foreach ($parts as $part) {

			/* Handle variables */
			if (str_has_prefix($part, ':')) {
				$part = substr($part, 1);
				$requirement = array_get_default($requirements, $part, null);
				$map[] = array($part, $requirement);

			/* No variable */
			} else {
				$map[] = $part;
			}
		}

		/* Add the url map to the list of registered url maps */
		$urlmap = array($command_name, $map);
		$this->urlmaps[] = $urlmap;
	}

	/**
	 * Sets the default command. This command will be called if none of the
	 * other url maps (both explicit and implicit) match the url requested. Use
	 * this method to supply default functionality, but do not handle errors
	 * here; use handle_error_not_found() instead.
	 *
	 * \param $method_name
	 *   The name of the method to use as a default command. The method
	 *   specified must exist.
	 */
	function set_default_command($method_name)
	{
		assert('is_string($method_name)');
		if (!method_exists($this, 'command_' . $method_name)) {
			trigger_error(sprintf('URLDispatcher::set_default_command():
				Supplied method (%s) does not exist.', $method_name),
				E_USER_ERROR);
		}
		$this->_set('default-command', $method_name);
	}

	/**
	 * \private
	 *
	 * Matches an input value against a given pattern.
	 *
	 * \param $input
	 *   The input value
	 *
	 * \param $pattern
	 *   The pattern to match against
	 *
	 * \return
	 *   True if the value matches the pattern, false otherwise.
	 *
	 * \see URLDispatcher::_match_inputs_with_patterns
	 */
	function _match_input_with_pattern($input, $pattern)
	{
		/* Literal strings must match */
		if (is_string($pattern)) {
			if ($pattern == $input) {
				return true;
			}
		
		} else {
			assert('is_array($pattern)');
			list($parameter, $req) = $pattern;

			/* null values mean there should be no validation */
			if (is_null($req)) {
				return array($parameter, $input);
			}

			/* Special data types */
			elseif (is_int($req)) {

				switch ($req) {
					case REQUIRE_INTEGER:
						if (preg_match('/^\d+$/', $input)) {
							return array($parameter, (int) $input);
						}

					default:
						return false;
				}

			/* Try regular expression matching */
			} else {
				assert('is_string($req)');
				if (preg_match($req, $input)) {
					return array($parameter, $input);
				}
			}
		}

		return false;
	}

	/**
	 * \private
	 *
	 * Matches input values against patterns.
	 *
	 * \param $inputs
	 *   The input values
	 *
	 * \param $patterns
	 *   The patterns to match against.
	 *
	 * \return
	 *   True if the values match the patterns, false otherwise.
	 *
	 * \see URLDispatcher::_match_input_with_pattern
	 */
	function _match_inputs_with_patterns($inputs, $patterns)
	{
		/* The number of parameters must match */
		if (count($inputs) != count($patterns))
			return false;

		$parameters = array();
		while ($patterns) {
			$pattern = array_shift($patterns);
			$input = array_shift($inputs);

			$result = $this->_match_input_with_pattern($input, $pattern);

			if ($result === false) {
				return false;
			} elseif ($result === true) {
				// do nothing, this was a simple string match
			} else {
				list ($name, $value) = $result;
				$parameters[$name] = $value;
			}
		}
		return $parameters;
	}

	/**
	 * Dispatch an URL to the correct handlers. This method does the actual
	 * magic, such as url parsing, matching and command invocation. You can
	 * optionally provide a custom url and tell the dispatcher that some parts
	 * of the url should be skipped when handling this request.
	 *
	 * \param $url
	 *   The url to dispatch (optional, defaults to null). Omit this value (or
	 *   provide null) to use the current request url.
	 * 
	 * \param $skip_path_components
	 *   The number of path components to skip when handling this request
	 *   (optional, defaults to null). This is useful if you want to skip some
	 *   prefix present in all urls, such as ~username. If you don't specify
	 *   this parameter the value of <code>$_SERVER['PHP_SELF']</code> will be
	 *   used to figure out how many components to skip.
	 */
	function dispatch($url=null, $skip_path_components=null)
	{
		if (is_null($skip_path_components))
		{
			/* Figure out the current script location. This is most likely
			 * the script living in the document root calling this method. Use
			 * the directory path component of this file to figure out how many
			 * path components should be skipped. */
			$dir_url = dirname($_SERVER['PHP_SELF']);

			if ($dir_url == DIRECTORY_SEPARATOR)
				$skip_path_components = 0;
			else
				$skip_path_components = count(explode('/', str_strip_prefix($dir_url, '/')));
		}

		/* Initialization */
		$get_params = '';

		/* Use the current url if no explicit url was given */
		if (is_null($url)) {
			$url = Request::relative_url();
		}

		/* We need to strip off the GET parameters */
		$question_mark_pos = strpos($url, '?');
		if ($question_mark_pos !== false) {
			$get_params = substr($url, $question_mark_pos);
			$url = substr($url, 0, $question_mark_pos);
		}

		/* Sanity checks */
		assert('is_int($skip_path_components) && $skip_path_components >= 0');
		assert('is_string($url)');

		/* Force trailing slash for GET requests? */
		if (
				// only rewrite GET requests:
				Request::is_get() &&

				// only if enabled:
				$this->_getdefault('force-trailing-slash', true) &&

				// do not rewrite the toplevel url:
				($url != '/') &&

				// only rewrite if there is no slash at the end:
				!str_has_suffix($url, '/') &&

				// only if the last part doesn't contain a . character (file extension):
				preg_match('/^.*\/[^.\/]+$/', $url)
		   ) {
			redirect($url . '/' . $get_params, HTTP_STATUS_MOVED_PERMANENTLY);
		}

		/* Store the url so that it can be used later */
		$this->_set('url', $url);

		/* Split the url into smaller pieces */
		$url = str_strip_prefix($url, '/');
		$url = str_strip_suffix($url, '/');
		$components = split('/', $url);

		/* Skip some components if requested, and store the cut-off part in the
		 * 'base-url' property. */
		if (count($components) < $skip_path_components) {
			$this->_handle_result(HTTP_STATUS_INTERNAL_SERVER_ERROR);
		}
		$base_url = sprintf('/%s/', join('/', array_slice($components, 0, $skip_path_components)));
		$this->_set('base-url', $base_url);
		$components = array_slice($components, $skip_path_components);

		/* Special case for top level urls */
		if ((count($components) == 1) && (strlen($components[0]) == 0)) {
			$components = array();
		}


		/* Try all URL maps and see if they match the input url */
		$found_map = false;
		$command_name = null;
		$parameters = array();
		foreach ($this->urlmaps as $urlmap) {

			list ($command_name, $patterns) = $urlmap;

			/* Check for valid parameters */
			$match = $this->_match_inputs_with_patterns($components, $patterns);

			/* This urlmap didn't match, try next one */
			if ($match === false) {
				continue;

			/* This urlmap matched! */
			} else {
				$parameters = $match;
				$found_map = true;
				break;
			}
		}

		/* No explicit map found, try an implicit map */
		if (!$found_map && $this->_getdefault('use-implicit-commands', true)) {
			$command_name = join('_', $components);
			$command_name = str_replace('-', '_', $command_name);
			/* The method must exist */
			$command = 'command_' . $command_name;
			$found_map = method_exists($this, $command);
		}

		/* As a last resort try the default handler, if one was set. There's no
		 * need to check the availability of the method; set_default_command()
		 * already did that. */
		if (!$found_map && $this->_isset('default-command')) {
			$command_name = $this->_get('default-command');
			$found_map = true;
		}

		/* Sanity check: is the method available? */
		$command = 'command_' . $command_name;
		if (!method_exists($this, $command)) {
			/* FIXME: it's not clear if this is desirable */
			/* We found a handler name but the method doesn't exist... */
			/* Trying the default handler, but remember which command
			 * we wanted to access. */
			if (!$this->_isset('default_command')) {
				/* We give up. */
				$found_map = false;
			} else {
				$command = 'command_' . $this->_get('default-command');
			}
				
		}

		/* If we still don't have a command, we give up. Too bad... not found */
		if (!$found_map) {
			$this->_handle_result(HTTP_STATUS_NOT_FOUND);
			return false;
		}

		/* Store the command name for use by _handle_result() and possibly
		 * pre_command(). */
		$this->_set('command', $command_name);

		/* If this piece of code is reached, a valid command has been found. Run
		 * the pre-command hook, call the appropriate method and handle the
		 * result. The pre_command() method may return HTTP_STATUS_NOT_FOUND or
		 * HTTP_STATUS_FORBIDDEN as well, so we need to handle the status
		 * carefully. */

		$status = $this->pre_command($parameters);

		/* The pre_command() method is not required to have a return value. If
		 * it doesn't return anything, $status will be null at this point. If
		 * that's the case we assume everything is alright. */
		if (is_null($status)) {
			$status = HTTP_STATUS_OK;
		}

		if ($status == HTTP_STATUS_OK) {
			/* The pre_command() method succeeded. Run the actual command and
			 * keep track of the status. */
			$status = $this->$command($parameters);
		}
		/* Regardless of whether the actual command has been executed, the
		 * result handler is invoked. Note: The post_command() is only invoked
		 * if both the pre_command() and the actual command method return
		 * HTTP_STATUS_OK (there's no danger of calling post_command() if no
		 * real command has been executed). */
		$this->_handle_result($status);
	}


	/**
	 * \private
	 *
	 * Handles the result of a command invocation. Don't invoke this method from
	 * the outside.
	 *
	 * \param $status
	 *   The status code of the result.
	 */
	function _handle_result($status)
	{
		/* Handle some special status codes */
		if (is_null($status)) {
			trigger_error(sprintf(
				'Command \'command_%s\' did not return a status code',
				$this->_get('command')),
			E_USER_ERROR);
		}
		
		if (is_bool($status)) {
			$status = $status
				? HTTP_STATUS_OK
				: HTTP_STATUS_INTERNAL_SERVER_ERROR;
		}

		/* Status must be an integer once this code is reached */
		if (!is_int($status)) {
			$status = HTTP_STATUS_INTERNAL_SERVER_ERROR;
		}

		/* Redirection cannot be done using status codes */
		if (($status >= 300) && ($status < 400)) {
			$status = HTTP_STATUS_INTERNAL_SERVER_ERROR;
		}

		/* Command completed successfully */
		if (($status >= 200) && ($status < 300)) {

			/* Post-dispatch hook */
			$this->post_command();

			return true;


		/* Command did not complete successfully */
		} else {

			$this->_handle_error($status);

			return false;
		}
	}

	/** \{
	 * \name Error handling methods
	 */

	/**
	 * Handles errors. Override this method for custom error handling.
	 *
	 * \param $http_status_code
	 *   The error code to be handled.
	 */
	function handle_error($http_status_code)
	{
		$this->_show_error_page($http_status_code);
	}

	/**
	 * Handles 'not found' errors. Override this method for custom error handling.
	 */
	function handle_error_not_found()
	{
		$this->_show_error_page(HTTP_STATUS_NOT_FOUND);
	}

	/**
	 * Handles 'forbidden' errors. Override this method for custom error handling.
	 */
	function handle_error_forbidden()
	{
		$this->_show_error_page(HTTP_STATUS_FORBIDDEN);
	}

	/**
	 * \private
	 *
	 * Handle command errors. This method sends the correct HTTP headers and
	 * calls another function to do the actual error handling. Do not override
	 * this function!
	 *
	 * \param $status
	 *   The status code returned by the command
	 *
	 * \see URLDispatcher::handle_error
	 * \see URLDispatcher::handle_error_not_found
	 * \see URLDispatcher::handle_error_forbidden
	 */
	function _handle_error($status) {
		/* Send the HTTP repsonse header */
		header(sprintf('HTTP/1.1 %03d', $status));
		
		/* Handle common errors (not found and forbidden) */
		if ($status == HTTP_STATUS_NOT_FOUND) {
			$this->handle_error_not_found();

		} elseif ($status == HTTP_STATUS_FORBIDDEN) {
			$this->handle_error_forbidden();

		/* Fallback to general error handler for other status codes */
		} else {
			$this->handle_error($status);
		}
	}

	/**
	 * \private
	 *
	 * Show a simple error page.
	 *
	 * \param $http_status_code
	 *   The http status code.
	 */
	function _show_error_page($http_status_code)
	{
		anewt_include('page');

		$title = sprintf(
			'%d - %s',
			$http_status_code,
			http_status_to_string($http_status_code));

		$p = new AnewtPage();
		$p->set('show-dublin-core', false);
		$p->set('title', $title);

		$p->append(ax_h1($title));

		switch ($http_status_code)
		{
			case HTTP_STATUS_NOT_FOUND:
				$p->append(ax_p('The requested resource cannot be found.'));
				break;

			case HTTP_STATUS_FORBIDDEN:
				$p->append(ax_p('Access to the requested resource was denied.'));
				break;

			default:
				/* No explanation (just a title) */
				break;
		}

		$p->flush();
	}

	/** \} */

	/** \{
	 * \name Callback methods
	 */

	/**
	 * Called before the actual command is invoked. Does nothing by default.
	 *
	 * \param $parameters
	 *   The parameters array, as passed to the command itself as well.
	 */
	function pre_command($parameters) {}

	/**
	 * Called after the command has completed succesfully. Does nothing by
	 * default.
	 */
	function post_command() {}

	/** \} */
}

?>
