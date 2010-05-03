<?php

class MyPage extends AnewtPage
{
	function MyPage()
	{
		/* Call the parent constructor */
		AnewtPage::AnewtPage();

		/* Add stylesheets */
		$this->add_stylesheet_href('style.css');
		$this->add_stylesheet_href_media('print.css', 'print');

		/* Link to an external JavaScript file */
		$this->add_javascript_src('some-script.js');

		/* Embed JavaScript code directly */
		$this->add_javascript_content(
			'function foo() {
				alert("foo");
			}'
		);

		/* Provide a list of blocks */
		$this->set('blocks', array('header', 'content', 'footer'));

		/* Set some default values */
		$this->set('title', 'This is the default title');
	}
}


/* You can use this page like this: */

$p = new MyPage();
/* ... */
$p->flush();

?>
