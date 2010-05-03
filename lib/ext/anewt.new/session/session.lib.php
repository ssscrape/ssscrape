<?php

/*
 * Anewt, Almost No Effort Web Toolkit, session module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


global $anewt_session_current_name; /* This is REQUIRED for correct scoping! */
/** \private Holds current session name */
$anewt_session_current_name = null;

/**
 * The session class provides several static methods for session handling.
 */
class AnewtSession
{
	/** \{
	 * \name Methods to start and destroy sessions
	 *
	 * These methods will setup and teardown user sessions.
	 */

	/**
	 * Initialize the session with the given name.
	 *
	 * \param $name The name this session should have.
	 * \param $timeout The timeout value for this session.
	 */
	public static function init($name, $timeout=null)
	{
		assert('is_string($name)');

		global $anewt_session_current_name;

		/* Do not break when initializing the same session twice. Note that this
		 * still throws a warning if the user tries to register a session with
		 * a different name (session_start will do that). */
		if ($anewt_session_current_name === $name)
			return;

		if (!is_null($timeout))
		{
			assert('is_int($timeout)');
			session_set_cookie_params($timeout);
		}

		session_name($name);
		session_start();

		$anewt_session_current_name = $name;
	}

	/**
	 * Destroy the current session.
	 */
	public static function destroy()
	{
		$_SESSION = array();
		session_destroy();
	}

	/** \} */


	/** \{
	 * \name Methods for handling session data
	 *
	 * These methods can be used to get and set session data.
	 */

	/**
	 * Store a variable in the session.
	 *
	 * \param $name The variable name.
	 * \param $value The value of the variable.
	 */
	public static function set($name, $value)
	{
		assert('is_string($name)');

		$_SESSION[$name] = $value;
	}

	/**
	 * Obtain a value from the session.
	 *
	 * If no value was set for the provided name, an error is thrown.
	 *
	 * \param $name The variable name.
	 *
	 * \return The value of the variable.
	 */
	public static function get($name)
	{
		assert('is_string($name)');

		if (!AnewtSession::is_set($name))
			throw new AnewtException('Session variabe "%s" is not set.', $name);

		return $_SESSION[$name];
	}

	/**
	 * Deletes a variable from the session.
	 *
	 * If no value was set for the provided name, an error is thrown.
	 *
	 * \param $name The name of the variable to delete.
	 *
	 * \return The value of deleted variable.
	 */
	public static function delete($name)
	{
		assert('is_string($name)');

		if (!AnewtSession::is_set($name))
			throw new AnewtException('Session variabe "%s" is not set.', $name);

		$result = $_SESSION[$name];
		unset($_SESSION[$name]);
		return $result;
	}

	/**
	 * Checks if a variable is defined in the session.
	 *
	 * \param $name The variable name to check for.
	 *
	 * \return True if the variable is available, false otherwise.
	 */
	public static function is_set($name)
	{
		assert('is_string($name)');

		return array_key_exists($name, $_SESSION);
	}

	/** \} */
}

?>
