<?php

/**
 *
 * \mainpage Anewt API Documentation
 *
 * \section intro Introduction
 *
 * This is the reference documentation for Anewt, the Almost No Effort Web
 * Toolkit. All classes, functions, parameters and other useful bits are
 * (supposed to be) documented here.
 *
 * Make sure you also read the (unfortunately not yet complete) manual to get
 * a better understanding. These pages are for reference and exploration only,
 * and not intended as a tutorial or how-to.
 *
 * \section quickstart Anewt Quick Start
 *
 * - Checkout the source code or extract an Anewt tarball
 * - Make sure your code (often this is the web server) has read access to all
 *   the files in the anewt/ directory.
 * 
 * To use Anewt in your own code, this line of code suffices:
 *
 * - \code require_once '/path/to/anewt/anewt.lib.php'; \endcode
 *
 * Now you can load modules like this:
 *
 * - \code anewt_include('database'); \endcode
 *
 * Good luck!
 *
 */

trigger_error(sprintf('This file (%s) should not be included.', __FILE__),
		E_USER_ERROR);

?>
