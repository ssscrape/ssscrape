<?php

require_once dirname(__FILE__) . '/../anewt.lib.php';

anewt_include('xhtml');


$fragment = new AnewtXHTMLFragment();


/* Headers */

$fragment->append_child(new AnewtXHTMLHeader1('Tests for the XHTML generation code'));


/* Text */

$fragment->append_child(new AnewtXHTMLHeader2('Text'));

$fragment->append_child(new AnewtXHTMLParagraph('This is some text'));
$fragment->append_child(new AnewtXHTMLDiv(new AnewtXHTMLParagraph('This is some text inside a div'), array('class' => 'some-class')));

$fragment->append_child(
	new AnewtXHTMLParagraph(
		'This is some text ',
		new AnewtXHTMLSpan('with some spanned content', array('id' => 'the-span-id')),
		' and a manual',
		new AnewtXHTMLBreak,
		'linebreak, though I think manual',
		new AnewtXHTMLBreak,
		'linebreaks ',
		new AnewtXHTMLDeletion('must die'),
		' ',
		new AnewtXHTMLInsertion('should be avoided', array('class'=>'inserted')),
		' in most cases.'
	)
);

$fragment->append_child(
	new AnewtXHTMLParagraph(
		new AnewtXHTMLEmphasis('Emphasis, '),
		new AnewtXHTMLStrong('strong, '),
		new AnewtXHTMLDefinition('defitinion, '),
		new AnewtXHTMLCode('code, '),
		new AnewtXHTMLSample('sample, '),
		new AnewtXHTMLKeyboard('keyboard, '),
		new AnewtXHTMLVariable('var, '),
		new AnewtXHTMLAbbreviation('abbr, '),
		'and ',
		new AnewtXHTMLAcronym('acronym'),
		'.'
	)
);

$fragment->append_child(
	new AnewtXHTMLPreformatted(
		'This is some preformatted text.'
	)
);

$fragment->append_child(
	new AnewtXHTMLBlockQuote(
		new AnewtXHTMLParagraph('This is a long quotation.'),
		new AnewtXHTMLParagraph('This is second paragraph of the long quotation.')
	)
);

$fragment->append_child(
	new AnewtXHTMLParagraph(
		'This is a small quote: ',
		new AnewtXHTMLQuote('To be or not to be.')
	)
);

$fragment->append_child(
	new AnewtXHTMLParagraph(
		'This paragraph is to test ',
		new AnewtXHTMLSubscript('subscripted'),
		' and ',
		new AnewtXHTMLSuperscript('superscripted'),
		' text.'
	)
);


/* Lists */

$fragment->append_child(new AnewtXHTMLHeader2('Lists'));

$fragment->append_child(new AnewtXHTMLHeader3('Ordered list'));
$ol = new AnewtXHTMLOrderedList(
			new AnewtXHTMLListItem('one'),
			new AnewtXHTMLListItem('two'),
			array('class' => 'foo'));
$ol->append_child(new AnewtXHTMLListItem('three', ' and last', array('class' => 'last')));
$fragment->append_child($ol);

$fragment->append_child(new AnewtXHTMLHeader3('Definition list'));
$fragment->append_child(
		new AnewtXHTMLDefinitionList(
			new AnewtXHTMLDefinitionTerm('foo'),
			new AnewtXHTMLDefinitionDescription('bar'),
			new AnewtXHTMLDefinitionTerm('quux'),
			new AnewtXHTMLDefinitionDescription('baz')
			), array('class' => 'definitions'));


/* Tables */

$fragment->append_child(new AnewtXHTMLHeader2('Tables'));

$table = new AnewtXHTMLTable();

$table_head = new AnewtXHTMLTableHead(
	new AnewtXHTMLTableRow(ax_fragment(
		new AnewtXHTMLTableHeaderCell('Column 1'),
		new AnewtXHTMLTableHeaderCell('Column 2')
)));
$table->append_child($table_head);

$table_body = new AnewtXHTMLTableBody(ax_fragment(
	new AnewtXHTMLTableRow(ax_fragment(
		new AnewtXHTMLTableCell('r1c1'),
		new AnewtXHTMLTableCell('r1c2')
	)),
	new AnewtXHTMLTableRow(ax_fragment(
		new AnewtXHTMLTableCell('r2c1'),
		new AnewtXHTMLTableCell('r2c2')
	))
));
$table->append_child($table_body);

$fragment->append_child($table);


/* Forms */

$fragment->append_child(new AnewtXHTMLHeader2('Forms'));

$form = new AnewtXHTMLForm(null, array('method' => 'get', 'action' => '#'));

$input_fragment = new AnewtXHTMLFragment();
$input_fragment->append_child(new AnewtXHTMLLabel('Label: ', array('for' => 'test')));
$input_fragment->append_child(new AnewtXHTMLInput(null, array(
				'name' => 'test',
				'id' => 'test',
				'type' => 'text')));
$form->append_child(new AnewtXHTMLParagraph($input_fragment));

$select = new AnewtXHTMLSelect(null, array('name' => 'select'));
$select->append_child(new AnewtXHTMLOption('First', array('value' => 'first')));
$select->append_child(new AnewtXHTMLOption('Second', array('value' => 'second')));
$select->append_child(new AnewtXHTMLOption('Third', array('value' => 'third')));
$form->append_child(new AnewtXHTMLParagraph($select));

$fragment->append_child($form);

$form->append_child(new AnewtXHTMLParagraph(new AnewtXHTMLInput(null,
				array('type' => 'submit'))));



/* Convenience API */

$r = array();

$r[] = ax_h2('Convenience API');

$r[] = ax_p('Test with some <& special characters.', array('style' => 'color: #ccc;'));
$r[] = ax_p_class(ax_raw('This is <strong>strong</strong>'), 'someclass');
$r[] = ax_p(ax_abbr('ICE', 'InterCity Express'));
$r[] = ax_p(array('Test', ax_br(), 'after the break'));

$p = ax_p(array('testje', array('1', '2'), ax_strong('blablabla')));
$p->set_attribute('id', 'paragraph-id');
$p->set_class('foo bar baz');
$p->remove_class('bar');
$p->add_class('quux');
$p->append_child(ax_a_href('name', '/url/'));
$r[] = $p;

$r[] = ax_p(ax_sprintf('%s & %s', ax_span_class('Sugar', 'sweet'), 'Spice'));
$r[] = ax_p(ax_vsprintf('%s & %s', array(ax_span_class('Sugar', 'sweet'), 'Spice')));

$values = array('this', ax_strong('is'), 'a', ax_span('test'));
$r[] = ax_p(ax_join(', ', $values));
$r[] = ax_p(ax_join(ax_em(' & '), $values));

$fragment->append_child(ax_fragment($r, ax_p('final paragraph')));


/* Final output */

anewt_include('page');
$page = new AnewtPage();
$page->title = 'Anewt XHTML output test';
$page->append($fragment);
$page->flush();

?>
