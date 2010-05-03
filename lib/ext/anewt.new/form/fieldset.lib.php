<?php

/*
 * Anewt, Almost No Effort Web Toolkit, form module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Form fieldset.
 *
 * A fieldset acts as a grouping container for a form. You can add controls and
 * to it, and add the fieldset to an AnewtForm instance afterwards.
 *
 * The \c label property is required and will be rendered as the \c
 * &lt;legend&gt; XHTML element. You may also set the \c description and \c
 * error properties, which are handled just like form and form control
 * descriptions and error messages.
 */
class AnewtFormFieldset extends AnewtContainer
{
	/**
	 * \private
	 *
	 * Listing of controls.
	 */
	var $_controls = array();

	/**
	 * \private
	 *
	 * Listing of children.
	 */
	var $_children = array();


	/**
	 * Create a new fieldset.
	 *
	 * \param $name
	 *   A name for this fieldset.
	 */
	function __construct($name)
	{
		assert('is_string($name); // form fieldset name must be a string');

		$this->_seed(array(
			'name'          => $name,
			'id'            => null,
			'label'         => null,
			'description'   => null,
			'error'         => null,
			'class'         => null,
		));
	}

	/**
	 * Add a control to this fieldset
	 *
	 * \param $control
	 *   The form control instance to add.
	 */
	function add_control($control)
	{
		assert('$control instanceof AnewtFormControl');
		$this->_children[] = $control;
		$this->_controls[] = $control;
	}

	/**
	 * Add a custom node to this fieldset.
	 *
	 * \param $node
	 *   An AnewtXMLDomNode to embed in the form.
	 */
	function add_node($node)
	{
		assert('$node instanceof AnewtXMLDomNode;');
		$this->_children[] = $node;
	}
}

?>
