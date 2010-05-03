<?php

/*
 * Anewt, Almost No Effort Web Toolkit, logging module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


anewt_include('datetime');


/**
 * Base class for log handlers. This class should be subclassed to be useful.
 * Sample implementations are included.
 *
 * \see AnewtLogHandlerDefault
 * \see AnewtLogHandlerFile
 * \see AnewtLogHandlerAbort
 */
abstract class AnewtLogHandlerBase
{
	/**
	 * The minimum log level to log for. Though this field is marked as public,
	 * you should never change it. The AnewtLog class will take care of the log
	 * level when calling AnewtLog::add_handler.
	 *
	 * \see AnewtLog::add_handler
	 */
	public $level = ANEWT_LOG_LEVEL_MESSAGE;

	/**
	 * Log a message.
	 *
	 * \param $domain
	 *   The log domain. This can be null.
	 *
	 * \param $level
	 *   The log level.
	 *
	 * \param $message
	 *   The string to log.
	 *
	 * \see AnewtLog
	 */
	abstract function log($domain, $level, $message);

	/**
	 * \public
	 *
	 * Format a log message. This method should be used in AnewtLogHandlerBase
	 * subclasses to format the log messages.
	 *
	 * \param $domain
	 *   The log domain. This can be null.
	 *
	 * \param $level
	 *   The log level.
	 *
	 * \param $message
	 *   The string to log.
	 *
	 * \return
	 *   Formatted log string.
	 */
	protected function format_log_message($domain=null, $level, $message)
	{
		assert('is_int($level)');
		assert('is_string($message)');

		$name = AnewtLog::loglevel_to_string($level);

		/* Without logging domain */
		if (is_null($domain)) {
			$output = sprintf(
					'%s: %s',
					$name,
					$message);

		/* With logging domain */
		} else {
			assert('is_string($domain)');
			$output = sprintf(
					'(%s) %s: %s',
					$domain,
					$name,
					$message);
		}

		/* Reduce new lines and indentation and make it a single line, since
		 * error logs should not contain newlines */
		$output = str_join_wrap($output);
		$output = str_replace("\n", ' ', $output);

		return $output;
	}
}


/**
 * Log handler that uses the default error output mechanism.
 *
 * For Apache web servers the message will be written to the ErrorLog file (eg.
 * error.log or error_log). For command line applications the message will be
 * written to stderr.
 */
final class AnewtLogHandlerDefault extends AnewtLogHandlerBase
{
	/**
	 * Log a message.
	 *
	 * \param $domain
	 * \param $level
	 * \param $message
	 *
	 * \see AnewtLogHandlerBase::log
	 */
	function log($domain, $level, $message)
	{
		$output = $this->format_log_message($domain, $level, $message);
		error_log($output);
	}
}


/**
 * Log handler that saves all output to a file.
 */
final class AnewtLogHandlerFile extends AnewtLogHandlerBase
{
	private $fp;          /**< File descriptor */
	private $timestamps;  /**< Whether to output timestamps */

	/**
	 * Constructor for the AnewtLogHandlerFile.
	 *
	 * \param $filename
	 *   The filename to log to.
	 *
	 * \param $timestamps
	 *   Whether to enable timestamp output in the log file (on by default).
	 */
	function __construct($filename, $timestamps=true)
	{
		assert('is_string($filename)');
		assert('is_bool($timestamps)');

		$this->fp = fopen($filename, 'a+');
		$this->timestamps = $timestamps;
	}

	/**
	 * Log a message.
	 *
	 * \param $domain
	 * \param $level
	 * \param $message
	 *
	 * \see AnewtLogHandlerBase::log
	 */
	public function log($domain, $level, $message)
	{
		$name = AnewtLog::loglevel_to_string($level);

		$output = $this->format_log_message($domain, $level, $message);

		/* Optionally prefix with timestamp */
		if ($this->timestamps) {
			$date = AnewtDateTime::iso8601(AnewtDateTime::now());
			$output = sprintf('[%s] %s', $date, $output);
		}

		/* Make sure there is a trailing newline */
		if (!str_has_prefix($output, NL))
			$output .= NL;

		fwrite($this->fp, $output);
	}
}


/**
 * AnewtLogHandlerAbort aborts script execution on critical warnings and errors.
 * All messages below this threshold are discarded, so you should use another
 * log handler as well in addition to AnewtLogHandlerAbort. This class is
 * primarily useful for debugging; you should not use this handler when
 * deploying an application. The standard trigger_error function is used.
 */
final class AnewtLogHandlerAbort extends AnewtLogHandlerBase
{
	/**
	 * Triggers an error with the log message when the logged message is
	 * a critical warning or an error.
	 *
	 * \param $domain
	 * \param $level
	 * \param $message
	 *
	 * \see AnewtLogHandlerBase::log
	 */
	public function log($domain, $level, $message)
	{
		/* Only abort for serious log messages */
		$error_type = null;
		switch ($level)
		{
			case ANEWT_LOG_LEVEL_CRITICAL:
			case ANEWT_LOG_LEVEL_ERROR:
				$error_type = E_USER_ERROR;
				break;

			case ANEWT_LOG_LEVEL_WARNING:
				$error_type = E_USER_WARNING;
				break;

			default:
				return;
		}

		$output = $this->format_log_message($domain, $level, $message);
		trigger_error($output, $error_type);
	}
}

?>
