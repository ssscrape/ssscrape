<?php

/*
 * Anewt, Almost No Effort Web Toolkit, XHTML module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * \file
 *
 * Form classes.
 *
 * See http://www.w3.org/TR/html4/struct/lists.html
 */


/**
 * XHTML form element.
 *
 * \see http://www.w3.org/TR/html4/interact/forms.html#h-17.3
 */
class AnewtXHTMLForm extends _AnewtXHTMLBlockElement
{
	public $node_name = 'form';
}


/**
 * XHTML form input element.
 *
 * \see http://www.w3.org/TR/html4/interact/forms.html#h-17.4
 */
class AnewtXHTMLInput extends _AnewtXHTMLInlineElement
{
	public $node_name = 'input';
	protected $must_be_empty = true;
	protected $allows_text = false;
	public $always_render_closing_tag = false;
}


/**
 * XHTML form button element.
 *
 * \see http://www.w3.org/TR/html4/interact/forms.html#h-17.5
 */
class AnewtXHTMLButton extends _AnewtXHTMLInlineElement
{
	public $node_name = 'button';
	protected $allows_text = true;
	public $always_render_closing_tag = false;
}


/**
 * XHTML form textarea element.
 *
 * \see http://www.w3.org/TR/html4/interact/forms.html#h-17.7
 */
class AnewtXHTMLTextarea extends _AnewtXHTMLInlineElement
{
	public $node_name = 'textarea';
	protected $allows_text = true;
}


/**
 * XHTML form select element.
 *
 * \see http://www.w3.org/TR/html4/interact/forms.html#h-17.6
 */
class AnewtXHTMLSelect extends _AnewtXHTMLInlineElement
{
	public $node_name = 'select';
	protected $allowed_elements = array('option', 'optgroup');
	protected $allows_text = false;
}


/**
 * XHTML form select option element.
 *
 * \see http://www.w3.org/TR/html4/interact/forms.html#h-17.6
 */
class AnewtXHTMLOption extends _AnewtXHTMLBlockElement
{
	public $node_name = 'option';
}

/**
 * XHTML form select option group element.
 *
 * \see http://www.w3.org/TR/html4/interact/forms.html#h-17.6
 */
class AnewtXHTMLOptionGroup extends _AnewtXHTMLBlockElement
{
	public $node_name = 'optgroup';
	protected $allowed_elements = array('option');
	protected $allows_text = false;
}

/**
 * XHTML form label element.
 *
 * \see http://www.w3.org/TR/html4/interact/forms.html#h-17.6
 */
class AnewtXHTMLLabel extends _AnewtXHTMLInlineElement
{
	public $node_name = 'label';
}

/**
 * XHTML fieldset element.
 *
 * \see http://www.w3.org/TR/REC-html40/interact/forms.html#h-17.10
 */
class AnewtXHTMLFieldset extends _AnewtXHTMLBlockElement
{
	public $node_name = 'fieldset';
}

/**
 * XHTML fieldset legend element.
 *
 * \see http://www.w3.org/TR/REC-html40/interact/forms.html#h-17.10
 */
class AnewtXHTMLLegend extends _AnewtXHTMLBlockElement
{
	public $node_name = 'legend';
}

?>
