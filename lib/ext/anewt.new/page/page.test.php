<?php

require_once dirname(__FILE__) . '/../anewt.lib.php';

anewt_include('page');

class TestPage extends AnewtPage
{
	function __construct()
	{
		parent::__construct();
		$this->set('blocks', array('header', 'main', 'footer'));
	}

	function build_header()
	{
		return ax_fragment(array(
			ax_p('Header line 1'),
			ax_p('Header line 2')
		));
	}
}


$p = new TestPage();
$p->title = 'Anewt Page test';
$p->default_block = 'main';

$p->add_stylesheet_href('1.css');
$p->add_stylesheet_href('2.css');
$p->add_stylesheet(ax_stylesheet_href('3.css'));
$p->add_stylesheet(ax_stylesheet_href_media('screen.css', 'screen'));
$p->add_stylesheet_href_media('print.css', 'print');

$p->add_javascript_content('function test() {alert("test");}');
$p->add_javascript_src('foo.js');

$p->append_to('main', ax_h1('Title'));
$p->append(ax_p('Test paragraph'));
$p->append(ax_p(ax_a_href('Click me!', 'javascript:test()')));

$p->append_to('footer', ax_p('This is the footer text'));

$p->flush();

?>
