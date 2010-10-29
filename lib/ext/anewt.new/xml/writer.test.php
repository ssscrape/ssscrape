<?php

require_once dirname(__FILE__) . '/../anewt.lib.php';

anewt_include('xml/writer');


$xw = new AnewtXMLWriter();

$xw->write_start_document();
$xw->set_encoding('UTF-8');
$xw->set_standalone(true);
$xw->write_start_element('RootElement');

	/* Element without content */
	$xw->write_start_element('Element');
		$xw->write_attribute('foo', 'bar');
		$xw->write_attribute('foo', 'baz');
		$xw->write_attribute('foo2', 'bar');
		$xw->write_attributes(array('foo3' => 'bar', 'foo4' => 'bah'));
	$xw->write_end_element();

	/* Element with content */
	$xw->write_start_element('Element');
		$xw->write_text('foo');
	$xw->write_end_element();

	/* Element with attributes, text and raw text */
	$xw->write_start_element('AnotherElement', array('test' => 'testing'));
		$xw->write_attribute('foo2', 'bar');
		$xw->write_text('This is & text.');
		$xw->write_raw(' And this is <b>also text!</b>');
	$xw->write_end_element('AnotherElement');

	/* Top level text */
	$xw->write_text('This is some more text.');

	/* And some comments */
	$xw->write_comment('This is comment --- yes it is');

$xw->write_end_element('RootElement');
$xw->write_end_document();

$xw->flush();

?>
