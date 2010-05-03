<?php

/* Initialization */

error_reporting(E_ALL | E_STRICT);

require_once(dirname(__FILE__) . '/../anewt.lib.php');


/* Module loading */

anewt_include('xhtml');
anewt_include('page');

anewt_include('form');
anewt_include('form/renderer/default');


/* Styling */

$css = <<<EOCSS
div.form-control {
 margin-top: .5em;
 margin-bottom: .5em;
 padding: .3em;
 background-color: #eee;
}

.form-control label.form-control {
 display: block;
 float: left;
 width: 15em;
}

.form-control span.form-option {
 display: block;
}

form .with-help {
 cursor: help;
}

.form-error {
 color: #c00;
}

form input,
form textarea {
 margin-right: .5em;
}

form optgroup {
 margin-left: 2px;
 margin-right: 2px;
 font-weight: bold;
 font-style: normal;
}


EOCSS;


/* Form definition */

class TestForm extends AnewtForm
{
	function TestForm()
	{
		parent::__construct();

		/* General form setup and test some properties */

		$this->setup('test-name', ANEWT_FORM_METHOD_POST, AnewtRequest::url(true));

		$this->set('error', 'This is a general form error.');
		$this->set('description', 'This is the form\'s description.');


		/* Test text entry controls */

		$text1 = new AnewtFormControlText('text1');
		$text1->set('label', 'Text field:');
		$text1->set('secondary-label', 'Secondary label');
		$text1->set('value', 'Short string with & spécial chars');
		$text1->set('help', 'You can type some text here.');
		$text1->set('description', 'Type at least 3 characters here.');
		$text1->add_validator(
			new AnewtValidatorLength(3, null),
			'Too short (minimum number of characters is 3).');
		$this->add_control($text1);

		$text2 = new AnewtFormControlTextMultiline('text2');
		$this->add_control($text2);
		/* Set properties after adding to test references */
		$text2->set('description', 'You may enter some longer text here.');
		$text2->set('value', "Some readonly text\nwith multiple\nlines\nand some spécial characters\nlike &, ', < and >.");
		$text2->set('label', 'Multiline textfield:');
		$text2->set('readonly', true);

		$text3 = new AnewtFormControlTextMultiline('text3');
		$text3->set('value', "A larger control with a\nbit more space (4 lines!)\nto type some text, if only\nit wasn't disabled…");
		$text3->set('label', 'Larger multiline:');
		$text3->set('secondary-label', 'Secondary label');
		$text3->set('rows', 4);
		$text3->set('columns', 40);
		$text3->set('disabled', true);
		$text3->set('error', 'This is an error description.');
		$this->add_control($text3);

		$text4 = new AnewtFormControlTextMultiline('text4');
		$text4->set('value', 'Okay, this is multiline text you can edit yourself…');
		$text4->set('label', 'Another multiline:');
		$text4->set('rows', 2);
		$text4->set('columns', 50);
		$this->add_control($text4);


		/* Test adding custom nodes to the form */

		$this->add_node(ax_p('This is a normal paragraph inside the form.'));
		$this->add_node(ax_raw('<p><strong>Formatted text</strong> can be here as well.</p>'));


		/* Some more text controls with various property combinations */

		$text5 = new AnewtFormControlText('text5');
		$text5->set('label', 'A number between 1 and 10:');
		$text5->set('description', 'This should be 8 by default. You should not be able to type more than 2 characters into this field.');
		$text5->set('value', '7'); // Set again to 8 later using set_control_value!
		$text5->set('help', 'Enter a number between 1 and 10 inclusive, please.');
		$text5->set('size', 2);
		$text5->set('maxlength', 2);
		$text5->add_validator(
			new AnewtValidatorInteger(1, 10),
			'This number is not valid.');
		$this->add_control($text5);

		$text6 = new AnewtFormControlPassword('text6');
		$text6->set('label', 'Password:');
		$text6->set('description', 'This control can hold only 8 characters. This field is not required and will give a NULL value when left empty.');
		$text6->set('size', 8);
		$text6->set('maxlength', 8);
		$text6->set('value', 'not-shown');
		$text6->set('required', false);
		$text6->set('help', 'Enter a password, please.');
		$this->add_control($text6);

		$text7 = new AnewtFormControlPassword('text7');
		$text7->set('label', 'Echoed password:');
		$text7->set('description', 'This password is echoed back when the form is submitted.');
		$text7->set('value', 'shown');
		$text7->set('show-value', true);
		$this->add_control($text7);

		$text8 = new AnewtFormControlText('text8');
		$text8->set('label', 'Email:');
		$text8->set('value', 'someone@example.com');
		$text8->add_validator(
			new AnewtValidatorEmail(),
			'This does not look like an email address.');
		$this->add_control($text8);


		/* Check box */

		$check1 = new AnewtFormControlCheckbox('check1');
		$check1->set('label', 'Checkbox:');
		$check1->set('secondary-label', 'Check me');
		$check1->set('help', 'Feel free to check me!');
		$this->add_control($check1);

		$check2 = new AnewtFormControlCheckbox('check2');
		$check2->set('label', 'Disabled checkbox:');
		$check2->set('disabled', true);
		$this->add_control($check2);

		$check3 = new AnewtFormControlCheckbox('check3');
		$check3->set('label', 'Another checkbox:');
		$check3->set('secondary-label', 'Check me too');
		$check3->set('value', true);
		$this->add_control($check3);


		/* Choice control, multiple same values */

		$choice_same_values = new AnewtFormControlChoice('choice-same-values');
		$choice_same_values->set('label', 'Choose:');
		$choice_same_values->set('description', 'Multiple options have the same value. Only the first should be selected.');
		$choice_same_values->add_option_value_label('same-value', 'Option 1 (same value as option 2');
		$choice_same_values->add_option_value_label('same-value', 'Option 2 (same value as option 1');
		$choice_same_values->add_option_value_label('other-value', 'Option 3');
		$choice_same_values->set('value', 'same-value');
		$this->add_control($choice_same_values);


		/* Choice control, single select */

		$single_choice_fieldset = new AnewtFormFieldset('single-choice');
		$single_choice_fieldset->set('label', 'Single select');
		$single_choice_fieldset->set('description', 'You can select a single value here. By default, the second option is selected in both cases, and the first option is disabled.');

		$choice1 = new AnewtFormControlChoice('choice1');
		$choice1->set('label', 'Make a choice:');
		$option = new AnewtFormOption('first', 'First option');
		$option->set('disabled', true);
		$choice1->add_option($option);
		unset($option);
		$choice1->add_option_value_label('second', 'Second option');
		$choice1->add_option_value_label('third', 'Third option');
		$choice1->add_option_value_label('fourth', 'Fourth option');
		$choice1->set('value', 'second');
		$single_choice_fieldset->add_control($choice1);

		$choice2 = new AnewtFormControlChoice('choice2');
		$choice2->set('threshold', 3);
		$choice2->set('label', 'Make a choice:');
		$option = new AnewtFormOption('first', 'First option');
		$option->set('disabled', true);
		$choice2->add_option($option);
		unset($option);
		$choice2->add_option_value_label('second', 'Second option');
		$choice2->add_option_value_label('third', 'Third option');
		$choice2->add_option_value_label('fourth', 'Fourth option');
		$choice2->set('value', 'second');
		$single_choice_fieldset->add_control($choice2);

		$choice3 = new AnewtFormControlChoice('choice3');
		$choice3->set('threshold', 3);
		$choice3->set('size', 4);
		$choice3->set('label', 'Make a choice:');
		$option = new AnewtFormOption('first', 'First option');
		$option->set('disabled', true);
		$choice3->add_option($option);
		unset($option);
		$choice3->add_option_value_label('second', 'Second option');
		$choice3->add_option_value_label('third', 'Third option');
		$choice3->add_option_value_label('fourth', 'Fourth option');
		$choice3->set('value', 'second');
		$single_choice_fieldset->add_control($choice3);

		$single_choice_fieldset->add_node(ax_p('This is a normal paragraph inside a fieldset.'));
		$this->add_fieldset($single_choice_fieldset);


		/* Choice control, multiple select */

		$multiple_choice_fieldset = new AnewtFormFieldset('multiple-choice');
		$multiple_choice_fieldset->set('label', 'Multiple select');
		$multiple_choice_fieldset->set('description', 'You can select multiple values here. By default, the second and third options are selected in both cases, and the first option is disabled.');

		$choice4 = new AnewtFormControlChoice('choice4');
		$choice4->set('multiple', true);
		$choice4->set('label', 'Make a choice:');
		$option = new AnewtFormOption('first', 'First option');
		$option->set('disabled', true);
		$choice4->add_option($option);
		unset($option);
		$choice4->add_option_value_label('second', 'Second option');
		$choice4->add_option_value_label('third', 'Third option');
		$choice4->add_option_value_label('fourth', 'Fourth option');
		/* Value is deliberately set twice to test unsetting of old values */
		$choice4->set('value', array('first', 'second'));
		$choice4->set('value', array('second', 'third'));
		$multiple_choice_fieldset->add_control($choice4);

		$choice5 = new AnewtFormControlChoice('choice5');
		$choice5->set('multiple', true);
		$choice5->set('threshold', 3);
		$choice5->set('label', 'Make a choice:');
		$option = new AnewtFormOption('first', 'First option');
		$option->set('disabled', true);
		$choice5->add_option($option);
		unset($option);
		$choice5->add_option_value_label('second', 'Second option');
		$choice5->add_option_value_label('third', 'Third option');
		$choice5->add_option_value_label('fourth', 'Fourth option');
		$choice5->set('value', array('second', 'third'));
		$multiple_choice_fieldset->add_control($choice5);

		$this->add_fieldset($multiple_choice_fieldset);


		/* Choice control, with option groups */

		$option_groups_fieldset = new AnewtFormFieldset('option-groups-fieldset');
		$option_groups_fieldset->set('label', 'Option groups');
		$option_groups_fieldset->set('description', 'Single and multiple select controls with option groups.');

		$choice6 = new AnewtFormControlChoice('choice6');
		$og = new AnewtFormOptionGroup('First group');
		$og->add_option_value_label('1a', 'A');
		$og->add_option_value_label('1b', 'B');
		$choice6->add_option_group($og);
		$og = new AnewtFormOptionGroup('Second group');
		$og->add_option_value_label('2a', 'A');
		$og->add_option_value_label('2b', 'B');
		$choice6->add_option_group($og);

		$choice6->set('label', 'Make a choice:');
		$choice6->set('description', 'Choice 2a selected by default.');
		$choice6->set('value', '2a');
		$option_groups_fieldset->add_control($choice6);


		$choice7 = new AnewtFormControlChoice('choice7');
		$choice7->add_option_value_label('o1', 'First option (not in a group)');

		$option_group_1 = new AnewtFormOptionGroup('First group');
		$option = new AnewtFormOption('g1o1', 'First disabled suboption in first group');
		$option->set('disabled', true);
		$option_group_1->add_option($option);
		$option_group_1->add_option_value_label('g1o2', 'Second suboption in first group');
		$option_group_1->add_option_value_label('g1o3', 'Third suboption in first group');
		$choice7->add_option_group($option_group_1);

		$option_group_2 = new AnewtFormOptionGroup('Second group');
		$option_group_2->add_option_value_label('g2o1', 'First suboption in second group');
		$option_group_2->add_option_value_label('g2o2', 'Second suboption in second group');
		$choice7->add_option_group($option_group_2);

		$choice7->add_option_value_label('o3', 'Third option (not in a group)');

		$option_group_3 = new AnewtFormOptionGroup('Third group (completely disabled)');
		$option_group_3->set('disabled', true);
		$option_group_3->add_option_value_label('g3o1', 'Only suboption in third group');
		$choice7->add_option_group($option_group_3);

		$choice7->add_option_value_label('04', 'Fourth option, not in a group');

		$choice7->set('label', 'Make multiple choices:');
		$choice7->set('description', 'Options 1 and group 2 option 1 are selected by default.');
		$choice7->set('multiple', true);
		$choice7->set('value', array('g2o1', 'o1'));
		$option_groups_fieldset->add_control($choice7);


		$choice8 = new AnewtFormControlChoice('choice8');
		$choice8->set('label', 'No selection here:');
		$choice8->set('description', 'This choice control only contains disabled options and disabled or empty option groups. The first one should be forcibly selected. Yes, this is a really nasty edge case.');
		$og = new AnewtFormOptionGroup('Empty group 1');
		$choice8->add_option_group($og);
		$og = new AnewtFormOptionGroup('Disabled group 2');
		$og->set('disabled', true);
		$og->add_option_value_label('none', '1a');
		$og->add_option_value_label('none', '1b');
		$choice8->add_option_group($og);
		$option = new AnewtFormOption('none', 'Disabled option');
		$option->set('disabled', true);
		$choice8->add_option($option);
		$choice8->set('value', 'too bad this is totally invalid');
		$option_groups_fieldset->add_control($choice8);

		$this->add_fieldset($option_groups_fieldset);


		/* Hidden controls */

		$hidden1 = new AnewtFormControlHidden('hidden1');
		$hidden1->set('value', 'sekr1t!');
		$this->add_control($hidden1);

		$this->add_hidden_control('hidden2', 'another && śèkŕ1t');


		/* Buttons */

		$button_fieldset = new AnewtFormFieldset('buttons');
		$button_fieldset->set('label', 'Button fieldset');

		$submit = new AnewtFormControlButtonSubmit('submit');
		$submit->set('label', 'Submit form');
		$submit->set('help', 'Press this button to submit this form');
		$button_fieldset->add_control($submit);

		$submit2 = new AnewtFormControlButtonSubmit('submit2');
		$submit2->set('label', 'Disabled submit button');
		$submit2->set('disabled', true);
		$button_fieldset->add_control($submit2);

		$submit = new AnewtFormControlButtonSubmit('submit3');
		$submit->set('label', 'Another submit button');
		$button_fieldset->add_control($submit);

		$reset = new AnewtFormControlButtonReset('reset');
		$reset->set('label', 'Reset to default values');
		$reset->set('help', 'Reset the values of the form fields to their original values');
		$button_fieldset->add_control($reset);

		$extra = new AnewtFormControlButton('extra-button');
		$extra->set('label', 'Extra button that does not do anything');
		$button_fieldset->add_control($extra);

		$this->add_fieldset($button_fieldset);
	}
}


/* Show a page and test the form */

$page = new AnewtPage();
$page->set('title', 'Anewt form test page');
$page->add_stylesheet(ax_stylesheet($css));

$page->append(ax_h1('Test form'));

$form = new TestForm();

assert('$form->get_control_value("text5") === "7"');
$form->set_control_value('text5', '8');


if ($form->autofill()) {
	if ($form->process())
	{
		$page->append(ax_p('Form succesfully processed!'));
	} else {
		$page->append(ax_p('Error while processing form!'));
	}
} else {
	$page->append(ax_p('Form not processed.'));
}


$fr = new AnewtFormRendererDefault();
$fr->set_form($form);

$page->append(ax_h2('The form'));
$page->append($fr);

if (AnewtRequest::is_post())
{
	$values = $form->get_control_values();
	ob_start(); var_dump($values); $values = ob_get_clean();
	$page->append(ax_h2('Form output'));
	$page->append(ax_pre($values));

	$page->append(ax_h2('$_POST values'));
	$page->append(ax_pre(print_r($_POST, true)));
}

$page->flush();

?>
