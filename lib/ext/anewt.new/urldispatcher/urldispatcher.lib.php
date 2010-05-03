<?php

/*
 * Anewt, Almost No Effort Web Toolkit, urldispatcher module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


mkenum(
	'ANEWT_URL_DISPATCHER_ROUTE_TYPE_URL_PARTS',
	'ANEWT_URL_DISPATCHER_ROUTE_TYPE_REGEX'
);


/**
 * HTTP exception.
 */
final class AnewtHTTPException extends AnewtException
{
	/**
	 * Construct a new AnewtException.
	 *
	 * \param $status_code
	 *   The HTTP status code for this error. This should be one of the \c
	 *   HTTP_STATUS_* constants.
	 * \param $fmt
	 *   A sprintf-like format string (optional)
	 * \param $args
	 *   The arguments for the format string placeholders
	 *
	 * \see AnewtException
	 */
	public function __construct($status_code, $fmt=null, $args=null)
	{
		$args = func_get_args();

		$status_code = array_shift($args);

		assert('is_int($status_code);');
		$this->status_code = $status_code;

		$fmt = array_shift($args);
		if (is_null($fmt))
			$fmt = http_status_to_string($status_code);

		assert('is_string($fmt);');

		parent::__construct($status_code, vsprintf($fmt, $args));
	}
}


/**
 * Dispatcher that routes requests to request handler methods.
 *
 * Well-designed web applications use a clean URL scheme for all pages and
 * resources in the application. Read ‘<a
 * href="http://www.w3.org/Provider/Style/URI">Cool URIs don't change</a>’ to
 * find out why this is important. Clean web applications do not need ugly
 * <code>.php</code> extensions or weird HTTP GET parameters with cryptic
 * numbers or strange identifers. Instead, you are encouraged to use clean URLs,
 * e.g. <code>/user/USERNAME</code> for a user page.
 *
 * AnewtURLDispatcher offers the basic functionality to implement web
 * applications that use clean URLs. AnewtURLDispatcher processes incoming
 * requests and looks at the URL of the request to decide which piece of code
 * should be invoked to handle that request. This means that AnewtURLDispatcher
 * is at the heart of your application: it is the main entry point for all the
 * functionality your application offers.
 *
 * AnewtURLDispatcher is based around two main concepts:
 * <strong>commands</strong> and <strong>routes</strong>.
 *
 *
 * \section commands Commands
 *
 * Commands are methods that do the actual work, like building an output page,
 * or outputting a RSS feed. There are multiple ways to define commands.
 *
 * Commands can be regular methods on your AnewtURLDispatcher subclass, and look
 * like \c command_xyz(), where \c xyz is the name of the command. What you
 * would normally create a separate PHP file for, e.g. <code>users.php</code>,
 * you can now write as a simple method, e.g. <code>command_users()</code>.
 * Other related pages are just additional methods on the same class. This way,
 * AnewtURLDispatcher allows you to group related functionality together in one
 * file, e.g. a user page, an ‘edit user’ page, or a ‘new user’ page. (Note that
 * if you insist you can still <code>require_once('latest-news.php')</code> in
 * your command method, though this is not how AnewtURLDispatcher is intended to
 * be used).
 *
 * Alternatively, commands can be implemented outside your AnewtURLDispatcher
 * subclass. In this case, commands are callbacks on other classes using the PHP
 * conventions for callbacks: a two-item array like the one you can pass to
 * functions like <code>call_user_func()</code> (in fact, this function is used
 * internally). This approach is particularly useful for larger projects where
 * you do not want to write the code for all pages of your application into the
 * same dispatcher class, because this would lead to an unmaintainable mess.
 * Instead, you can split all functionality related to a certain part of your
 * application into its own class, e.g. article overview, view, and edit urls
 * could point to <code>ArticleCommand::overview()</code>,
 * <code>ArticleCommand::view()</code>, and <code>ArticleCommand::edit()</code>
 * methods. Just create a method for each specific page, and define routes that
 * call those methods. It is recommended to use static methods, but it is also
 * possible to use an object instance whose methods will be called. See the PHP
 * manual on the callable type conventions and <code>call_user_func()</code>.
 *
 * If you use external commands, the classes that implement these commands can
 * be loaded lazily, i.e. only when they are needed. For example, this would
 * mean that the <code>ArticleCommand</code> class is only loaded when an
 * article page is requested, and not if the login page is requested.
 * AnewtURLDispatcher will invoke the method include_command_class() if it
 * encounters a class that cannot be resolved, so override that method and make
 * it load the right classes if you want to use the lazy loading functionality.
 *
 *
 * \section routes Routes
 *
 * Routes define how URLs map to commands. This is where you define your URL
 * scheme and say which command should be executed when AnewtURLDispatcher
 * processes an incoming request. The section on routes below explains how
 * routes work in more detail.
 *
 *
 * \section getting-started Getting Started
 *
 * Getting started with AnewtURLDispatcher is not hard. You define the URLs you
 * want your application to respond to, and implement the corresponding
 * commands. It is really straight-forward:
 *
 * - Subclass AnewtURLDispatcher
 * - Change some settings if you're not happy with the defaults
 * - Add routes to map URLs to commands
 * - Implement commands to actually make your application do something useful,
 *   just like would do otherwise. Commands can be implemented directly into the
 *   dispatcher subclass, or in external files (use callbacks when adding route
 *   in that case)
 *
 * Setting up clean URLs for the Apache web server can be done using this \c
 * .htaccess snippet. This instructs Apache to invoke \c dispatch.php for all
 * URLs that do not point to an existing file (such as a static HTML page or
 * image files, which are not served through the dispatcher).
 *
 * \code
 * RewriteEngine On
 * RewriteBase /the/base/url/of/your/application
 *
 * RewriteRule ^$ dispatch.php [L,QSA]
 *
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteCond %{REQUEST_FILENAME} !-d
 * RewriteRule ^(.*) dispatch.php [L,QSA]
 * \endcode
 *
 * Since AnewtURLDispatcher takes care of the complete request, the only thing
 * the \c dispatch.php file should do is something like this:
 *
 * \code
 * // Load the relevant classes here...
 *
 * $d = new YourDispatcher();
 * $d->dispatch();
 * \endcode
 *
 *
 * \section handling-errors Handling Errors
 *
 * If no command to execute can be found, e.g. because none of the routes match,
 * an AnewtHTTPException will be thrown. Additionally, if you want to raise an
 * error from your command, e.g. because a requested database record does not
 * exists, you can throw an AnewtHTTPException yourself. This will cause a ‘HTTP
 * 404 Not Found’ error page to be shown. Example:
 *
 * \code
 * if ($something_could_not_be_found)
 *     throw AnewtHTTPException(
 *         HTTP_STATUS_NOT_FOUND,
 *         'The requested news item does not exists.');
 * \endcode
 *
 * To create your own error handlers, override the error handling methods, e.g.
 * handle_error_not_found(). See below for more information.
 *
 *
 * \section properties Properties
 *
 * The following properties influence how AnewtURLDispatcher behaves:
 *
 * - \c force-trailing-slash indicates whether a trailing slash should be
 *   enforced on URLs that do not have one, by redirecting the request to the
 *   correct URL. This only applies to GET requests with URLs that do not
 *   contain a filename extension. This property is \c true by default.
 * - \c use-automatic-commands indicates whether the first part of the url
 *   should be used to find a matching command in case none of the explicitly
 *   added routes match, e.g. if the url \c /foo/bar/baz does not match any
 *   route, but the dispatcher has a \c command_foo() method, that is
 *   automatically invoked. This property is \c false by default, since it may
 *   have unwanted side effects when used in combination with explicit routes.
 *   Furthermore, it is often desirable to design your application's URLs
 *   explicitly instead of relying on automatic behaviour.
 * - \c default-command defines the default command that is invoked if no other
 *   routes the current URL. This fallback feature is disabled by default
 *   (defaults to <code>null</code>).
 *
 * When a command is invoked, you can use the following properties to get the
 * URLs of the current request:
 *
 * - \c url-relative contains the relative URL rooted at the dispatcher root
 * - \c url-prefix is the prefix where the dispatcher resides
 * - \c url-full is the complete URL, i.e. both \c url-prefix and \c url-relative
 *
 * For routes based on URL parts, constraints that should apply to all routes
 * (instead of to just one route) can be configured:
 *
 * - \c constraints is an associative array that maps parameter names to regular
 *   expressions. See the section on routes below for more information.
 */
abstract class AnewtURLDispatcher extends AnewtContainer
{
	/**
	 * URL routes
	 */
	private $routes = array();

	/**
	 * Constraints for URL parts.
	 *
	 * This only applies to routes added using add_route_url_parts().
	 */
	private $url_part_constraints = array();

	/**
	 * Construct a new AnewtURLDispatcher instance.
	 *
	 * Make sure you call this method from derived classes!
	 */
	public function __construct()
	{
		$this->_seed(array(

			'force-trailing-slash'   => true,

			'use-automatic-commands' => false,
			'default-command'        => null,

			/* URLs available to commands */
			'url-relative'           => null,
			'url-prefix'             => null,
			'url-full'               => null,
		));
	}

	/** \{
	 * \name Route Methods
	 *
	 * <strong>Routes</strong> define how URLs map to commands. Whenever a route
	 * matches the request, the corresponding command is invoked to handle the
	 * request. Command methods get passed a \c $parameters argument containing
	 * values for parameters that were defined when setting up the route (either
	 * regular expression matches, or named parameters, depending on the type of
	 * URL route).
	 *
	 * Incoming requests will be matched against all defined routes, until
	 * a route matches the current request, in which case the associated command
	 * is invoked. The routes are tried in the order in which they are added to
	 * the dispatcher. Therefore you should add more specific routes before more
	 * general routes.
	 *
	 * If you decide to use automatic commands, those will be tried if none of
	 * the explicitly defined routes matched the request. See the
	 * <code>automatic-commands</code> property description for more details on
	 * this feature.
	 *
	 * In addition to explicitly created routes, you can also add
	 * a <strong>default command</strong> that will be invoked if none of the
	 * routes match. The default command will be invoked if none of the provided
	 * URL routes (both explicit routes and automatic commands, if enabled)
	 * match the request URL. This method can be used to supply default
	 * functionality. Note that this is not the right way to handle errors; use
	 * handle_error_not_found() or one of the other error callbacks instead.
	 *
	 * Two types of routes can be used (read on for an explanation on
	 * both):
	 *
	 *   -# using regular expressions
	 *   -# using URL parts
	 *
	 * <strong>Regular expression routes</strong> use a regular expression to
	 * match an URL, and can be added using
	 * AnewtURLDispatcher::add_route_regex(). The URL that is used for matching
	 * does not contain a leading <code>/</code>, so you should not specify this
	 * in the regular expression.
	 *
	 * To extract parameters from the URL, the \c $parameters array passed to
	 * the command contains the matches from \c preg_match(), i.e. the
	 * parenthesized expressions. Parameters can have names (instead of numbers)
	 * by using named matches, e.g. <code>(?P<year>\\d{4})</code>. See below for
	 * more information about how regular expressions are used.
	 *
	 * Examples for routes using regular expressions:
	 *
	 * \code
	 * $this->add_route_regex('latest_news', '#^news$#');
	 * $this->add_route_regex('month_archive', '#^news/(\\d{4})$#');
	 * $this->add_route_regex('month_archive', '#^news/(?P<year>\\d{4})/(?P<month>\\d{1,2})$#');
	 * \endcode
	 *
	 * <strong>Routes based on URL parts</strong> are the other way to define
	 * URL routes. These routes can be added using
	 * AnewtURLDispatcher::add_route_url_parts(). To define a route, you can
	 * provide either a URL string, or an array of URL parts. An URL part
	 * corresponds to a "path component" in the URL, e.g. the URL
	 * <code>/news/2009/06/</code> consists of three parts.
	 *
	 * For routes based on URL parts, parameters can be specified using a
	 * \c : character before the parameter name, e.g. \c :year The constraints
	 * used for the parameters are regular expressions that will be matched
	 * using \c preg_match(). If no constraint is specified for a parameter, no
	 * checking is performed (any value is accepted). See below for more
	 * information about how regular expressions are used.
	 *
	 * Examples for routes using URL parts:
	 *
	 * \code
	 * $this->constraints = array('year' => '#^\\d{4}$#', 'month' => '#^\\d{1,2}$#');
	 * $this->add_route_url_parts('latest_news', 'news');
	 * $this->add_route_url_parts('month_archive', 'news/:year/:month/');
	 * $this->add_route_url_parts('month_archive', array('news', ':year', ':month'));
	 * \endcode
	 *
	 * Alternatively, the same, but now using external commands:
	 *
	 * \code
	 * $this->constraints = array('year' => '#^\\d{4}$#', 'month' => '#^\\d{1,2}$#');
	 * $this->add_route_url_parts(array('NewsCommand', 'latest_news'), 'news');
	 * $this->add_route_url_parts(array('NewsCommand', 'month_archive'), 'news/:year/:month/');
	 * $this->add_route_url_parts(array('NewsCommand', 'month_archive'), array('news', ':year', ':month'));
	 * \endcode
	 *
	 * Constraints should be provided as an associative array that maps
	 * parameter names to regular expressions. The constraints in the \c
	 * constraints property apply to all routes using URL parts, but can be
	 * overridden by specifying additional constraints when calling
	 * AnewtURLDispatcher::add_route_url_parts().
	 *
	 * Note about the regular expressions used for URL and parameter matching:
	 * the pattern is passed directly to <code>preg_match()</code>. Make sure to
	 * include <code>^</code> and <code>$</code> appropriately to match the full
	 * string, since otherwise you might match URLs that you do not want to
	 * match! Hint: use a <code>#</code> character for the regular expression
	 * delimiter, since the often-used <code>/</code> is confusing because it is
	 * a common character in URLs. (A literal <code>#</code> cannot occur in
	 * URLs anyway since it is used for the client-side fragment specifier.)
	 */

	 /**
	  * Callback used to lazy-load a command class.
	  *
	  * Override this method to hook in application-specific logic to make an
	  * external class with dispatcher commands available to the dispatcher, and
	  * you only want load the class when needed (lazy loading). This is used
	  * for routes pointing to external commands (those defined outside the
	  * AnewtURLDispatcher itself) when the class is not made available yet.
	  *
	  * \param $class_name
	  *   The name of the class this method should load.
	  */
	 protected function include_command_class($class_name)
	 {
		 /* Do nothing by default */
	 }

	 /**
	  * Validate a command.
	  *
	  * \param $command
	  *   The command to validate
	  *
	  * \return
	  *   The validate command
	  */
	 private function validate_command($command)
	 {
		if (is_string($command))
			$command = array($this, sprintf('command_%s', $command));

		list ($is_valid, $name_for_error_message) = $this->is_valid_command($command);

		if (!$is_valid)
			throw new AnewtHTTPException(
				HTTP_STATUS_INTERNAL_SERVER_ERROR,
				'Dispatcher command invalid or not available: %s.',
				$name_for_error_message);

		return $command;
	 }

	 /**
	  * Check whether a command is valid.
	  *
	  * \param $command
	  *   The command to validate
	  *
	  * \return
	  *   A 2-tuple with a 'valid' flag and an error message.
	  */
	 private function is_valid_command($command)
	 {
		$is_valid = false;
		$name_for_error_message = $command;

		if (is_string($command))
			$command = array($this, sprintf('command_%s', $command));

		if (is_numeric_array($command) && count($command) == 2 && is_string($command[1]))
		{
			if (is_string($command[0]))
			{
				/* Static method: Foo::bar() */
				if (class_exists($command[0]))
				{
					/* Class is already loaded, so check thoroughly */
					$is_valid = is_callable($command, false, $name_for_error_message);
				}
				else
				{
					/* Class is not loaded yet (see include_command_class()), so
					 * we can only check syntax in this case. */
					$is_valid = is_callable($command, true, $name_for_error_message);
				}
			}
			elseif (is_object($command[0]))
			{
				/* Instance method: $foo->bar() */
				$is_valid = method_exists($command[0], $command[1]);
				$name_for_error_message = sprintf('%s::%s', get_class($command[0]), $command[1]);
			}
		}

		return array($is_valid, $name_for_error_message);
	 }

	/**
	 * Add a route based on a regular expression.
	 *
	 * \param $command
	 *   The command to execute when this route matches.
	 * \param $regex
	 *   The regular expression to match against the URL.
	 *
	 * \see add_route_url_parts
	 */
	public function add_route_regex($command, $regex)
	{
		$command = $this->validate_command($command);

		$this->routes[] = array(
			ANEWT_URL_DISPATCHER_ROUTE_TYPE_REGEX,
			$command, $regex);
	}

	/**
	 * Add a route based on URL parts.
	 *
	 * \param $command
	 *   The command to execute when this route matches.
	 * \param $url_parts
	 *   Array with the URL parts, or a URL string. Variable parts (parameters)
	 *   can be specified using a \c : character, e.g. \c :year
	 * \param $additional_constraints
	 *   Associative array with constraints specific to this route. These are
	 *   applied on top of the default constraints that can be set using the \c
	 *   constraints property on the AnewtURLDispatcher instance.
	 *
	 * \see add_route_regex
	 */
	public function add_route_url_parts($command, $url_parts, $additional_constraints=null)
	{
		$command = $this->validate_command($command);

		if (is_null($additional_constraints))
			$additional_constraints = array();

		$this->routes[] = array(
			ANEWT_URL_DISPATCHER_ROUTE_TYPE_URL_PARTS,
			$command, $url_parts, $additional_constraints);
	}

	/** \} */


	/** \{
	 * \name Dispatching Methods
	 */

	/**
	 * Dispatch an URL to the correct handlers.
	 *
	 * \param $url
	 *   The URL to dispatch (optional, defaults to null). In almost all cases
	 *   you should not provide this URL to let the dispatcher figure out the
	 *   current request URL and prefix.
	 *
	 * \param $prefix
	 *   The prefix of the URL that should not be taken into account to match
	 *   URL routes. The automatic detection works fine in almost all case, so
	 *   you should likely omit this parameter.
	 *
	 * \see AnewtURLDispatcher::real_dispatch
	 */
	public function dispatch($url=null, $prefix=null)
	{
		try
		{
			$this->real_dispatch($url, $prefix);
		}
		catch (Exception $e)
		{
			$http_status = ($e instanceof AnewtHTTPException)
				? $e->getCode()
				: HTTP_STATUS_INTERNAL_SERVER_ERROR;

			/* Send the HTTP response header... */
			header(sprintf('HTTP/1.1 %03d', $http_status));;

			/* ...and call an error handling method */
			switch ($http_status)
			{
				case HTTP_STATUS_NOT_FOUND:
					$this->handle_error_not_found($e);
					break;

				case HTTP_STATUS_FORBIDDEN:
					$this->handle_error_forbidden($e);
					break;

				default:
					$this->handle_error($e);
					break;
			}
		}
	}

	/**
	 * (Really) dispatch an URL to the correct handlers.
	 *
	 * This method does the actual magic, such as URL parsing, matching and
	 * command invocation. You can optionally provide a custom URL and tell the
	 * dispatcher that some parts of the URL should be skipped when handling
	 * this request.
	 *
	 * \param $url
	 * \param $prefix
	 * \see AnewtURLDispatcher::dispatch
	 */
	private function real_dispatch($url=null, $prefix=null)
	{
		/* Use the current URL if no explicit url was given */

		if (is_null($url))
			$url = AnewtRequest::relative_url();

		/* Figure out the right base location if no prefix was given. If the URL
		 * starts with the PHP script name, we assume no htaccess file has been
		 * setup to beautify the website URLs. In this case the relevant parts
		 * of the URL are added after the PHP script name. Example URL of such
		 * a setup is http://.../dispatch.php/a/b/c/. Otherwise, it is quite
		 * likely a htaccess file is used to point all requests to a script that
		 * invokes the dispatcher. We assume this script is placed in the
		 * toplevel directory, so we use that directory as the prefix. */
		if (is_null($prefix))
		{
			if (str_has_prefix($url, $_SERVER['SCRIPT_NAME']))
				$prefix = $_SERVER['SCRIPT_NAME'];
			else
				$prefix = dirname($_SERVER['SCRIPT_NAME']);
		}

		assert('is_string($url)');
		assert('is_string($prefix)');


		/* Strip off the GET parameters from the URL */

		$get_params = '';
		$question_mark_pos = strpos($url, '?');
		if ($question_mark_pos !== false) {
			$get_params = substr($url, $question_mark_pos);
			$url = substr($url, 0, $question_mark_pos);
		}


		/* Redirect GET requests when trailing slash is required but missing */

		if (!str_has_suffix($url, '/') /* Only if there is no slash at the end */
				&& $this->force_trailing_slash /* Only if enabled */
				&& AnewtRequest::is_get() /* Only for GET requests */
				&& !preg_match('#^.*\.[^\/]*$#', $url) /* Only if the last part doesn't contain a . character (file extension) */
			)
		{
			redirect(
				sprintf('%s/%s', $url, $get_params),
				HTTP_STATUS_MOVED_PERMANENTLY);
		}


		/* Strip off prefix and slashes */

		$this->request_url_full = $url;
		$url = str_strip_prefix($url, $prefix);
		$url = str_strip_prefix($url, '/');
		$url = str_strip_suffix($url, '/');
		$this->request_url = $url;


		/* Try to find a matching route and extract the parameters */

		$found_route = false;
		$command = null;
		$parameters = array();
		$url_parts = strlen($url) > 0
			? explode('/', $url)
			: array();

		foreach ($this->routes as $route)
		{
			$route_type = array_shift($route);
			$route_command = array_shift($route);
			$route_parameters = array();


			/* Type I: Routes using regular expression */

			if ($route_type == ANEWT_URL_DISPATCHER_ROUTE_TYPE_REGEX)
			{
				list ($pattern) = $route;

				/* Try both with and without trailing slash */
				if (
						preg_match($pattern, $url, $route_parameters)
						|| preg_match($pattern, sprintf('%s/', $url), $route_parameters)
				   )
				{
					/* We don't care about $parameters[0] (it contains the full match) */
					array_shift($route_parameters);
					$route_parameters = array_map('urldecode', $route_parameters);

					$command = $route_command;
					$parameters = $route_parameters;
					$found_route = true;
					break;
				}
			}


			/* Type II: Routes using URL parts */

			elseif ($route_type == ANEWT_URL_DISPATCHER_ROUTE_TYPE_URL_PARTS)
			{
				list ($route_url, $additional_constraints) = $route;


				/* Route URL can be a string or an array */

				if (is_string($route_url))
				{
					$route_url = str_strip_prefix($route_url, '/');
					$route_url = str_strip_suffix($route_url, '/');

					$route_url_parts = strlen($route_url) > 0
						? explode('/', $route_url)
						: array();
				}
				elseif (is_numeric_array($route_url))
					$route_url_parts = $route_url;
				else
					throw new AnewtException('Invalid url route: %s', $route_url);


				/* Match the URL parts against the route URL parts */

				if (count($url_parts) != count($route_url_parts))
					continue;

				$constraints = array_merge($this->url_part_constraints, $additional_constraints);

				for ($i = 0; $i < count($url_parts); $i++)
				{
					/* If the URL starts with a ':' character it is
					 * a parameter... */

					if ($route_url_parts[$i]{0} === ':')
					{
						$parameter_name = substr($route_url_parts[$i], 1);
						$parameter_value = $url_parts[$i];

						/* If there is a constraint for this parameter, the
						 * value must match the constraint. If not, this route
						 * cannot be used. */
						if (array_key_exists($parameter_name, $constraints))
						{
							$pattern = $constraints[$parameter_name];

							if (!preg_match($pattern, $parameter_value))
								continue 2;
						}

						$route_parameters[$parameter_name] = urldecode($parameter_value);
					}


					/* ...otherwise, it is a fixed value */

					elseif ($url_parts[$i] !== $route_url_parts[$i])
						continue 2;
				}


				/* If this code is reached, we found a matching route with all
				 * the constraints on the URL parts satisfied. */

				$command = $route_command;
				$parameters = $route_parameters;
				$found_route = true;
				break;
			}
			else
			{
				assert('false; // not reached');
			}
		}


		/* If no route matches, try an automatic route. Only the first URL part
		 * is considered for this. */

		if (!$found_route && $this->use_automatic_commands)
		{
			$url_parts = explode('/', $url, 2);
			if ($url_parts)
			{
				$command = array($this, sprintf('command_%s', $url_parts[0]));
				list ($found_route, $error_message_to_ignore) = $this->is_valid_command($command);
			}
		}


		/* As a last resort try the default handler, if one was set. */

		$default_command = $this->default_command;
		if (!$found_route && !is_null($default_command))
		{
			$command = $default_command;
			$command = $this->validate_command($command);
			$found_route = true;
		}


		/* If we still don't have a command, we give up. Too bad... not found */

		if (!$found_route)
			throw new AnewtHTTPException(HTTP_STATUS_NOT_FOUND);


		/* Check the command for validity. In most cases we already know the
		 * command exists since that is already checked in the add_route_*()
		 * methods or in the code above, except for lazily loaded commands, so
		 * we try to load them and check for validity afterwards. */

		if (is_array($command) && is_string($command[0]))
		{
			$this->include_command_class($command[0]);
			$command = $this->validate_command($command);
		}


		/* Finally... run the command and the pre and post command hooks. */

		$this->pre_command($parameters);
		call_user_func($command, $parameters);
		$this->post_command($parameters);
	}

	/** \} */


	/** \{
	 * \name Callback Methods
	 */

	/**
	 * Called before the actual command is invoked.
	 *
	 * This method does nothing by default.
	 *
	 * \param $parameters
	 *   The parameters array, as passed to the command.
	 */
	protected function pre_command($parameters) {}

	/**
	 * Called after the command has completed succesfully.
	 *
	 * Note that this method may or may not be called depending on the
	 * succcesful completion of pre_command() and real command method.
	 *
	 * This method does nothing by default.
	 *
	 * \param $parameters
	 *   The parameters array, as passed to the command.
	 */
	protected function post_command($parameters) {}

	/** \} */


	/** \{
	 * \name Error Handling Methods
	 *
	 * A command may throw an AnewtHTTPException (or another exception) to
	 * indicate something went wrong during the request. In this case,
	 * AnewtURLDispatcher::dispatch() will invoke the
	 * AnewtURLDispatcher::handle_error() method with the exception that was
	 * thrown.
	 *
	 * The common "HTTP 404 Not Found" and "HTTP 403 Forbidden" cases have their
	 * own convenience error handler methods that you can override:
	 * AnewtURLDispatcher::handle_error_not_found() and
	 * AnewtURLDispatcher::handle_error_forbidden(). All other errors are passed
	 * directly to AnewtURLDispatcher::handle_error().
	 */

	/**
	 * Handle dispatcher errors.
	 *
	 * Override this method for custom error handling.
	 *
	 * \param $exception
	 *   The exception to be handled.
	 *
	 * \see handle_error_not_found
	 * \see handle_error_forbidden
	 */
	protected function handle_error($exception)
	{
		$this->show_error_page($exception);
	}

	/**
	 * Handle 'Not Found' errors.
	 *
	 * Override this method for custom error handling. The default
	 * implementation just propagates the error by calling
	 * AnewtURLDispatcher::handle_error().
	 *
	 * \param $exception
	 *
	 * \see handle_error
	 */
	protected function handle_error_not_found($exception)
	{
		$this->handle_error($exception);
	}

	/**
	 * Handle 'Forbidden' errors.
	 *
	 * Override this method for custom error handling. The default
	 * implementation just propagates the error by calling
	 * AnewtURLDispatcher::handle_error().
	 *
	 * \param $exception
	 *
	 * \see handle_error
	 */
	protected function handle_error_forbidden($exception)
	{
		$this->handle_error($exception);
	}

	/**
	 * \private
	 *
	 * Show a simple error page.
	 *
	 * \param $exception
	 *   The AnewtHTTPException instance
	 */
	protected function show_error_page($exception)
	{
		assert('$exception instanceof Exception;');

		anewt_include('page');

		$p = new AnewtPage();
		$p->show_dublin_core = false;


		/* Title */

		if ($exception instanceof AnewtHTTPException)
			$title = sprintf(
				'%d - %s',
				$exception->getCode(),
				http_status_to_string($exception->getCode()));
		else
			$title = sprintf(
				'%d - %s',
				HTTP_STATUS_INTERNAL_SERVER_ERROR,
				http_status_to_string(HTTP_STATUS_INTERNAL_SERVER_ERROR));

		$p->title = $title;
		$p->append(ax_h1($title));


		/* Add default explanation (instead of just a title) for some exceptions. */

		$message = $exception->getMessage();
		if ($message)
		{
			$p->append(ax_p($message));
		}
		else
		{
			switch ($exception->getCode())
			{
				case HTTP_STATUS_NOT_FOUND:
					$p->append(ax_p('The requested resource cannot be found.'));
					break;

				case HTTP_STATUS_FORBIDDEN:
					$p->append(ax_p('Access to the requested resource was denied.'));
					break;

				case HTTP_STATUS_INTERNAL_SERVER_ERROR:
					$p->append(ax_p('An internal server error occurred while processing your request.'));
					break;

				default:
					break;
			}
		}

		/* Backtrace */

		$p->append(ax_pre($exception->getTraceAsString()));

		$p->flush();
	}

	/** \} */
}

?>
