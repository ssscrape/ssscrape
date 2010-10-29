<?php

require_once dirname(__FILE__) . '/../anewt.lib.php';

anewt_include('xml/dom');

$doc = new AnewtXMLDomDocument();

if (AnewtRequest::get_bool('debug'))
	$doc->set_content_type('text/plain');

$root = $doc->create_element('Toplevel', array('foo' => 'bar', 'bar' => '&baz'));
$root->set_attribute('foo', null); // remove attribute
$doc->append_child($root);

$element1 = $root->append_child($doc->create_element('SomeElement'));
$element1->append_child($doc->create_text_node('Some & text.'));
$element1->append_child($doc->create_raw_node(' Some <b>previously marked up</b> text.'));
$element1->append_child_raw(' Some more text with <b>markup</b>.');
$element1->append_child($doc->create_comment('This is a comment -- containing << strange characters >>.'));
$element1->append_child($doc->create_text_node(' Even more text.'));

$element2 = $root->append_child($doc->create_element('SomeElement'));
$frag = $doc->create_document_fragment();
$frag->append_child($doc->create_text_node('AAA.'));
$frag->append_child($doc->create_text_node('BBB.'));
$frag->append_child($doc->create_text_node('CCC.'));
$element2->append_child($frag);

$sub1 = $element2->append_child($doc->create_element('Sub1'));
$sub1->append_child($doc->create_element('Sub2'));
$sub2 = $sub1->append_child($doc->create_element('Sub2'));
$sub3 = $sub2->append_child($doc->create_element('Sub3'));
$sub3->append_child($doc->create_text_node('First content in sub 3'));
$sub4 = $sub3->append_child($doc->create_element('Sub4'));
$sub4->append_child($doc->create_text_node('Contents of sub 4'));
$sub4->render_as_block = false;
$sub3->append_child($doc->create_text_node('More content in sub 3'));
$another_sub3 = $sub2->append_child($doc->create_element('Sub3'));
$another_sub3->always_render_closing_tag = true;

$element2->append_child($doc->create_element('Sub1'));
$another_sub1 = $element2->append_child($doc->create_element('Sub1'));
$another_sub1->append_child($doc->create_text_node('Some text.'));
$another_sub1->append_child_text(' Some more text.');
$element2->append_child($doc->create_element('Sub1', array('foo' => 'bar')));

$doc->flush();

?>
