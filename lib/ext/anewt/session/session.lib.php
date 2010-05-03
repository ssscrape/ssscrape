<?php

/*
 * Anewt, Almost No Effort Web Toolkit, session module
 *
 * Copyright (C) 2004  Wouter Bolsterlee <uws@xs4all.nl>
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


global $anewt_session_current_name; /* This is REQUIRED for correct scoping! */
/** \private Holds current session name */
$anewt_session_current_name = null;

/**
 * The session class provides several static methods for session handling.
 */
class Session {

	/**
	 * Initializes the session with the given name.
	 *
	 * \param $name The name this session should have.
	 * \param $timeout The timeout value for this session.
	 */
	function init($name, $timeout=null) {
		assert('is_string($name)');

		global $anewt_session_current_name;

		/* Do not break when initializing the same session twice. Note that this
		 * still throws a warning if the user tries to register a session with
		 * a different name (session_start will do that). */
		if ($anewt_session_current_name == $name)
			return;

		if (!is_null($timeout)) {
			assert('is_int($timeout)');
			session_set_cookie_params($timeout);
		}

		session_name($name);
		session_start();

		$anewt_session_current_name = $name;
	}

	/**
	 * Destroys the current session.
	 */
	function destroy() {
		$_SESSION = array();
		session_destroy();
	}

	/**
	 * Stores a variable in the session.
	 *
	 * \param $name The variable name.
	 * \param $value The value of the variable.
	 */
	function set($name, $value) {
		assert('is_string($name)');

		$_SESSION[$name] = $value;
	}

	/**
	 * Returns a variable from the session.
	 *
	 * \param $name The variable name.
	 *
	 * \return The value of the variable.
	 */
	function get($name) {
		assert('is_string($name)');
		assert('Session::is_set($name)');

		return $_SESSION[$name];
	}

	/**
	 * Deletes a variable from the session.
	 *
	 * \param $name The name of the variable to delete.
	 *
	 * \return The value of deleted variable.
	 */
	function delete($name) {
		assert('is_string($name)');
		assert('Session::is_set($name)');

		$result = $_SESSION[$name];
		unset($_SESSION[$name]);
		return $result;
	}

	/**
	 * Shorthand for the delete() function. Deletes a variable from the session.
	 *
	 * \param $name The name of the variable to delete.
	 *
	 * \return The value of deleted variable.
	 *
	 * \see Session::delete
	 */
	function del($name) {
		return Session::delete($name);
	}

	/**
	 * Checks if a variable is defined in the session.
	 *
	 * \param $name The variable name to check for.
	 *
	 * \return True if the variable is available, false otherwise.
	 */
	function is_set($name) {
		assert('is_string($name)');

		return isset($_SESSION[$name]);
	}
}

?>
