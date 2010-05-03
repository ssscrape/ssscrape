<?php

/*
 * Anewt, Almost No Effort Web Toolkit, form module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * \protected
 *
 * Abstract base class for form renderers. This class must be subclassed to
 * provide required functionality; the base implementation only provides some
 * functionality shared by all form renderers.
 */
abstract class AnewtFormRenderer extends Renderer
{
	/**
	 * Form instance of this form renderer.
	 */
	protected $_form = null;

	/**
	 * Create a new form renderer instance.
	 *
	 * Optionally an AnewtForm instance can be passed, which is passed to
	 * AnewtFormRenderer::set_form.
	 *
	 * \param $form
	 *   Optional form.
	 *
	 * \see AnewtFormRenderer::set_form
	 */
	function __construct($form=null)
	{
		if ($form)
			$this->set_form($form);
	}

	/**
	 * Set a reference to a form instance on this form renderer.
	 *
	 * \param $form
	 *   An AnewtForm instance.
	 */
	function set_form($form)
	{
		assert('$form instanceof AnewtForm; // Form must be an AnewtForm instance');
		$this->_form = $form;
	}

	/**
	 * Build the form element.
	 */
	protected function build_form_element()
	{
		/* FIXME: Form id's should be unique, even if a form is rendered twice.
		 * Perhaps a global cache of used form id's should be kept, and '-2'
		 * suffixes added as needed? */

		assert('$this->_form instanceof AnewtForm; // Form renderer must have a AnewtForm instance');

		/* Default attributes */

		$id = $this->_form->_get('id');

		$attributes = array(
			'method' => $this->_form->get('method-as-string'),
			'action' => $this->_form->_get('action'),
			'id'     => $id,
		);

		/* Encoding type */

		if ($this->_form->_contains_file_upload_control())
			$attributes['enctype'] = 'multipart/form-data';


		/* Class name */

		if ($this->_form->_isset('class'))
			$attributes['class'] = $this->_form->_get('class');


		/* Output <form> element with all hidden elements */

		$form = new AnewtXHTMLForm(null, $attributes);

		/* The HTML DTD does not allow <input> elements as direct childs of
		 * a <form> element. Use a <fieldset> that is completely hidden from
		 * view instead. */
		$hidden_controls_fieldset = new AnewtXHTMLFieldset();
		$hidden_controls_fieldset->set_attribute('style', 'display: none;');
		foreach ($this->_form->_hidden_controls() as $hidden_control)
			$hidden_controls_fieldset->append_child($hidden_control->build_widget());

		$form->append_child($hidden_controls_fieldset);

		return $form;
	}

	/**
	 * Render the form.
	 *
	 * \return
	 *   Rendered form as a AnewtXMLDomNode.
	 */
	abstract function render_default();
}

?>
