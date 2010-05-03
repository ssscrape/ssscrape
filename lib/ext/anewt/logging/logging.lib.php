<?php

/*
 * Anewt, Almost No Effort Web Toolkit, logging module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/* These constants are all for private use only */
define('ANEWT_LOG_LEVEL_ERROR',    0);
define('ANEWT_LOG_LEVEL_CRITICAL', 1);
define('ANEWT_LOG_LEVEL_WARNING',  2);
define('ANEWT_LOG_LEVEL_MESSAGE',  3);
define('ANEWT_LOG_LEVEL_INFO',     4);
define('ANEWT_LOG_LEVEL_DEBUG',    5);


/**
 * \static
 *
 * The AnewtLog class provides static methods for logging.
 */
class AnewtLog
{
	/**
	 * Constructor throws an error. Only static usage of this class is allowed.
	 */
	function __construct()
	{
		trigger_error('You cannot create AnewtLog instances. Call
				AnewtLog::init() for initialization and use the static methods
				afterwards.', E_USER_ERROR);
	}

	/**
	 * Initializes the AnewtLog module.
	 *
	 * \param $setup_default_handler
	 *   Whether to setup a default handler. If true, a new
	 *   AnewtLogHandlerDefault instance with default level filtering will be
	 *   initialized. This parameter defaults to false, which means a log
	 *   handler needs to be added manually before using the logging facilities.
	 */
	static function init($setup_default_handler=false)
	{
		global $anewt_logging_handlers;
		global $anewt_logging_domain_stack;

		assert('is_bool($setup_default_handler)');

		$anewt_logging_handlers = array();
		$anewt_logging_domain_stack = array();

		/* Setup default handler */
		if ($setup_default_handler)
		{
			$handler = new AnewtLogHandlerDefault();
			AnewtLog::add_handler($handler);
		}
	}

	/**
	 * Adds a log handler to the list of log handlers.
	 *
	 * \param $handler
	 *   A AnewtLogHandlerBase subclass instance
	 *
	 * \param $level
	 *   The minimum log level this handler should log messages for. The default
	 *   is ANEWT_LOG_LEVEL_MESSAGE, which means that normal messages, warnings,
	 *   critical warnings and errors are logged. Set to ANEWT_LOG_LEVEL_DEBUG
	 *   to enable logging of all messages, set to ANEWT_LOG_LEVEL_WARNING to
	 *   only log warnings and higher priority messages.
	 */
	static function add_handler(&$handler, $level=ANEWT_LOG_LEVEL_MESSAGE)
	{
		assert('is_int($level) && ($level >= 0) && ($level <= 5)');
		assert('$handler instanceof AnewtLogHandlerBase');

		global $anewt_logging_handlers;

		$handler->level = $level;
		$anewt_logging_handlers[] = &$handler;
	}

	/**
	 * \private
	 *
	 * Checks if the AnewtLog module was initialized.
	 *
	 * \return
	 *   True if initialized, false otherwise.
	 */
	private static function _is_initialized()
	{
		global $anewt_logging_handlers;
		return is_array($anewt_logging_handlers);
	}

	/**
	 * Sets the current log domain to the specified domain. Previous domain
	 * names will be remembered (stack-based).
	 *
	 * \param $domain
	 *   A string with the new domain name.
	 *
	 * \see AnewtLog::reset_domain
	 * \see AnewtLog::get_domain
	 */
	static function set_domain($domain) {
		assert('is_string($domain)');
		global $anewt_logging_domain_stack;
		$anewt_logging_domain_stack[] = $domain;
	}

	/**
	 * Resets the log domain to the previous domain.
	 *
	 * \return
	 *   The current log domain.
	 *
	 * \see AnewtLog::set_domain
	 * \see AnewtLog::get_domain
	 */
	static function reset_domain() {
		global $anewt_logging_domain_stack;
		return array_pop($anewt_logging_domain_stack);
	}

	/**
	 * Retrieves the current log domain.
	 *
	 * \return
	 *   The current log domain.
	 *
	 * \see AnewtLog::set_domain
	 * \see AnewtLog::reset_domain
	 */
	static function get_domain() {
		global $anewt_logging_domain_stack;
		if (!$anewt_logging_domain_stack) return null;
		return $anewt_logging_domain_stack[count($anewt_logging_domain_stack)-1];
	}

	/**
	 * Converts a log level integer to a string representation.
	 *
	 * \param $level
	 *   The log level to convert, eg. ANEWT_LOG_LEVEL_MESSAGE
	 *
	 * \return
	 *   The name for this log level.
	 */
	static function loglevel_to_string($level) {
		assert('is_int($level) && ($level >= 0) && ($level <= 5)');
		switch ($level) {
			case ANEWT_LOG_LEVEL_ERROR:    return 'error';
			case ANEWT_LOG_LEVEL_CRITICAL: return 'critical';
			case ANEWT_LOG_LEVEL_WARNING:  return 'warning';
			case ANEWT_LOG_LEVEL_MESSAGE:  return 'message';
			case ANEWT_LOG_LEVEL_INFO:     return 'info';
			case ANEWT_LOG_LEVEL_DEBUG:    return 'debug';

			/* This should never happen: */
			default: trigger_error(sprintf(
								 '%s:%s(): unknown level. Please report a bug!',
								 __CLASS__, __FUNCTION__), E_USER_ERROR);
		}
	}

	/**
	 * Log a message to the output handlers.
	 * 
	 * \param $level
	 *   The message log level. This should be one of the ANEWT_LOG_LEVEL_*
	 *   constants.
	 *
	 * \param $message
	 *   The message itself. sprintf-style placeholders can be specified.
	 *
	 * \param $args
	 *   An array with data (optional). These values will be substituted for the
	 *   placeholders in $message.
	 */
	private static function _log($level, $message, $args=null) {
		if (!AnewtLog::_is_initialized())
			AnewtLog::init();

		global $anewt_logging_handlers;

		if (is_null($args))
			$args = array();

		assert('is_int($level) && ($level >= 0) && ($level <= 5)');
		assert('is_string($message)');
		assert('is_array($args)');

		/* Get the variable arguments list */
		while ((count($args) == 1) && is_array($args[0])) {
			$args = $args[0];
		}

		if ($args)
			$message = vsprintf($message, $args);

		$domain = AnewtLog::get_domain();
		foreach ($anewt_logging_handlers as $handler) {
			if ($handler->level >= $level)
				$handler->log($domain, $level, $message);
		}
	}

	/**
	 * Log a message to the output handlers, temporarily overriding the log
	 * domain.
	 *
	 * \param $domain
	 *   The temporary log domain
	 *
	 * \param $level
	 * \param $message
	 * \param $args
	 *
	 * \see AnewtLog::_log
	 */
	private static function _log_with_domain($domain, $level, $message, $args) {
		AnewtLog::set_domain($domain);
		AnewtLog::_log($level, $message, $args);
		AnewtLog::reset_domain();
	}


	/* Errors */

	/**
	 * Log an error.
	 *
	 * \param $message
	 *   The message to log, with optional sprintf-style placeholders.
	 *
	 * \param $args
	 *   One or more parameters (or a single array) with values that will be
	 *   substituted for the placeholders in $message.
	 */
	static function error($message, $args=null) {
		$args = func_get_args();
		$message = array_shift($args);
		AnewtLog::_log(ANEWT_LOG_LEVEL_ERROR, $message, $args);
	}

	/**
	 * Log an error with a custom domain. This is particularly useful for
	 * library routines.
	 *
	 * \param $domain
	 *   The custom domain
	 *
	 * \param $message
	 * \param $args
	 * \see AnewtLog::error
	 */
	static function error_with_domain($domain, $message, $args=null) {
		$args = func_get_args();
		$domain = array_shift($args);
		$message = array_shift($args);
		AnewtLog::_log_with_domain($domain, ANEWT_LOG_LEVEL_ERROR, $message, $args);
	}


	/* Critical warnings */

	/**
	 * Log a critical warning.
	 *
	 * \param $message
	 * \param $args
	 * \see AnewtLog::error
	 */
	static function critical($message, $args=null) {
		$args = func_get_args();
		$message = array_shift($args);
		AnewtLog::_log(ANEWT_LOG_LEVEL_CRITICAL, $message, $args);
	}

	/**
	 * Log a critical warning with a custom domain.
	 *
	 * \param $domain
	 * \param $message
	 * \param $args
	 * \see AnewtLog::error_with_domain
	 */
	static function critical_with_domain($domain, $message, $args=null) {
		$args = func_get_args();
		$domain = array_shift($args);
		$message = array_shift($args);
		AnewtLog::_log_with_domain($domain, ANEWT_LOG_LEVEL_CRITICAL, $message, $args);
	}


	/* Warnings */

	/**
	 * Log a warning.
	 *
	 * \param $message
	 * \param $args
	 * \see AnewtLog::error
	 */
	static function warning($message, $args=null) {
		$args = func_get_args();
		$message = array_shift($args);
		AnewtLog::_log(ANEWT_LOG_LEVEL_WARNING, $message, $args);
	}

	/**
	 * Log a warning with a custom domain.
	 *
	 * \param $domain
	 * \param $message
	 * \param $args
	 * \see AnewtLog::error_with_domain
	 */
	static function warning_with_domain($domain, $message, $args=null) {
		$args = func_get_args();
		$domain = array_shift($args);
		$message = array_shift($args);
		AnewtLog::_log_with_domain($domain, ANEWT_LOG_LEVEL_WARNING, $message, $args);
	}


	
	/* Normal messages */

	/**
	 * Log a normal message.
	 *
	 * \param $message
	 * \param $args
	 * \see AnewtLog::error
	 */
	static function message($message, $args=null) {
		$args = func_get_args();
		$message = array_shift($args);
		AnewtLog::_log(ANEWT_LOG_LEVEL_MESSAGE, $message, $args);
	}

	/**
	 * Log a normal message with a custom domain.
	 *
	 * \param $domain
	 * \param $message
	 * \param $args
	 * \see AnewtLog::error_with_domain
	 */
	static function message_with_domain($domain, $message, $args=null) {
		$args = func_get_args();
		$domain = array_shift($args);
		$message = array_shift($args);
		AnewtLog::_log_with_domain($domain, ANEWT_LOG_LEVEL_MESSAGE, $message, $args);
	}


	/* Information messages */

	/**
	 * Log an informational message.
	 *
	 * \param $message
	 * \param $args
	 * \see AnewtLog::error
	 */
	static function info($message, $args=null) {
		$args = func_get_args();
		$message = array_shift($args);
		AnewtLog::_log(ANEWT_LOG_LEVEL_INFO, $message, $args);
	}

	/**
	 * Log an informational message with a custom domain.
	 *
	 * \param $domain
	 * \param $message
	 * \param $args
	 * \see AnewtLog::error_with_domain
	 */
	static function info_with_domain($domain, $message, $args=null) {
		$args = func_get_args();
		$domain = array_shift($args);
		$message = array_shift($args);
		AnewtLog::_log_with_domain($domain, ANEWT_LOG_LEVEL_INFO, $message, $args);
	}



	/* Debug messages */

	/**
	 * Log a debug message.
	 *
	 * \param $message
	 * \param $args
	 * \see AnewtLog::error
	 */
	static function debug($message, $args=null) {
		$args = func_get_args();
		$message = array_shift($args);
		AnewtLog::_log(ANEWT_LOG_LEVEL_DEBUG, $message, $args);
	}

	/**
	 * Log a debug message with a custom domain.
	 *
	 * \param $domain
	 * \param $message
	 * \param $args
	 * \see AnewtLog::error_with_domain
	 */
	static function debug_with_domain($domain, $message, $args=null) {
		$args = func_get_args();
		$domain = array_shift($args);
		$message = array_shift($args);
		AnewtLog::_log_with_domain($domain, ANEWT_LOG_LEVEL_DEBUG, $message, $args);
	}
}

?>
