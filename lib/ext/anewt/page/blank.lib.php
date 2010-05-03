<?php

/*
 * Anewt, Almost No Effort Web Toolkit, page module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * This class allows you to create a beautiful blank page.
 */
class AnewtBlankPage extends AnewtPage
{
	/**
	 * The constructor takes care of the page contents.
	 */
	function __construct()
	{
		parent::__construct();
		$text = 'this page intentionally left blank';
		$this->set('title', $text);
		$link = ax_a_href(
			$text,
			'http://www.this-page-intentionally-left-blank.org/whythat.html',
			array('style' => 'color:black; text-decoration:none;'));
		$paragraph = ax_p(
			$link,
			array('style' => 'position: absolute; right: 4em; bottom: 2em; font-size: small;')
			);
		$this->append($paragraph);
	}
}

?>
