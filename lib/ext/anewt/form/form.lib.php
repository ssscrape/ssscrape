<?php

/*
 * Anewt, Almost No Effort Web Toolkit, form module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


anewt_include('validator');


mkenum(
	'ANEWT_FORM_METHOD_POST',
	'ANEWT_FORM_METHOD_GET'
);


/**
 * Basic form class.
 *
 * This class can be used to create an XHTML form. An AnewtForm instance holds
 * a number of controls, fieldset or custom XHTML elements. Additionally, it has
 * a few properties that influence its behaviour:
 *
 * - \c id property is the form id
 * - \c method is either \c ANEWT_FORM_METHOD_GET or \c ANEWT_FORM_METHOD_POST
 * - \c action is the URL the form will be posted to
 *
 * Usually an AnewtForm subclass is created. The constructor of this subclass
 * adds controls to the form, and optionally the handle_valid() and
 * handle_invalid() methods are overridden. Calling code then instantiates this
 * subclass, fills it with values using fill() or autofill(), then processes the
 * form using process().
 *
 * \todo Reference module documentation once it's written.
 */
class AnewtForm extends Container
{
	/**
	 * Numeric array to hold all children of this form.
	 */
	private $_children = array();

	/**
	 * Associative array to hold all form controls by name.
	 */
	private $_controls_by_name = array();

	/**
	 * Associative array to hold all form fieldsets by name.
	 */
	private $_fieldsets_by_name = array();

	/**
	 * List of references to hidden controls.
	 */
	private $_hidden_controls = array();

	/**
	 * Initialize a new AnewtForm instance.
	 *
	 * Do not forget to call this method when you override the constructor in
	 * a subclass, i.e. call <code>parent::__construct()</code>.
	 */
	function __construct()
	{
		/* Default values */
		$id = sprintf('form-%s', str_strip_suffix(strtolower(get_class($this)), 'form'));
		$this->_seed(array(
			'id'          => $id,
			'method'      => ANEWT_FORM_METHOD_POST,
			'action'      => Request::relative_url(),

			'description' => null,
			'error'       => null,
		));
	}


	/**
	 * Setup the form in one step. This is just a convenience method, any of the
	 * arguments are optional.
	 *
	 * \param $id
	 *   Id of the form
	 * \param $method
	 *   Form method; must be one of the ANEWT_FORM_METHOD_GET or
	 *   ANEWT_FORM_METHOD_POST constants
	 * \param $action
	 *   The url to post this form to
	 */
	function setup($id=null, $method=null, $action=null)
	{
		if (!is_null($id))
		{
			assert('is_string($id)');
			$this->set('id',   $id);
		}

		if (!is_null($method))
		{
			assert('($method === ANEWT_FORM_METHOD_GET) || ($method === ANEWT_FORM_METHOD_POST)');
			$this->set('method', $method);
		}

		if (!is_null($action))
		{
			assert('is_string($action)');
			$this->set('action', $action);
		}
	}


	/**
	 * \{
	 * \name Getter and setter methods
	 */

	/**
	 * Return the form method as a string.
	 *
	 * \return
	 *   Form method as string, either GET or POST.
	 */
	function get_method_as_string()
	{
		$method = $this->_get('method');
		assert('($method === ANEWT_FORM_METHOD_GET) || ($method === ANEWT_FORM_METHOD_POST)');
		if ($method == ANEWT_FORM_METHOD_GET)
		{
			return 'get';
		} else {
			return 'post';
		}
	}

	/** \} */


	/**
	 * \{
	 * \name Methods for getting and setting form values
	 */

	/**
	 * Fill form using the supplied values. This will iterate over all controls
	 * of the form and set their value accordingly (if any).
	 *
	 * The return value can be used to determine whether a form was completely
	 * filled using the provided data, or that some values were missing
	 * from the \c $values array. See autofill() for more information about
	 * this.
	 *
	 * \param $values
	 *   Associative array used as a name to value mapping.
	 *
	 * \return
	 *   \c true if the form was succesfully and completely using the provided
	 *   \c $values, \c false otherwise.
	 *
	 * \see AnewtFormControl::fill()
	 */
	function fill($values)
	{
		assert('is_assoc_array($values);');
		$out = true;
		foreach (array_keys($this->_controls_by_name) as $name)
		{
			$out = $this->_controls_by_name[$name]->fill($values) && $out;
		}
		return $out;
	}

	/**
	 * Fill form automatically from query data. If the form uses the GET method
	 * the data comes from <code>$_GET</code>. If the form uses the POST method
	 * the data comes from <code>$_POST</code>.
	 *
	 * The return value (see also fill() for an explanation) can be used to
	 * detect incomplete \c $_GET or \c $_POST values. This may happen when
	 * handling different form flavours with different controls using a single
	 * form, e.g. a simple and advanced search form pointing to the same page in
	 * which an instance of the advanced flavour of the form handles the
	 * submitted data. It may also happen when users are messing with the
	 * submitted data. You may then decide to process() the form only if all
	 * values were provided. Example:
	 * <code>if ($form->autofill() && $form->process()) { ...} else { ...}</code>
	 *
	 * \return
	 *   True if this fill could be automatically filled from \c $_GET or \c
	 *   $_POST, false if this was not the case.
	 *
	 * \see AnewtForm::fill()
	 */
	function autofill()
	{
		$form_method = $this->_get('method');
		if (Request::is_get() && $form_method == ANEWT_FORM_METHOD_GET) {
			return $this->fill($_GET);
		}
		elseif (Request::is_post() && $form_method == ANEWT_FORM_METHOD_POST) {
			return $this->fill($_POST);
		}
		return false;
	}

	/**
	 * Get the value of a control.
	 *
	 * The specified control must exist.
	 *
	 * This method takes into account whether a control is empty and optional,
	 * in which case \c NULL is returned. For this reason, the value as returned
	 * by this method may differ from the value obtained using
	 * <code>get('value')</code> on the control instance itself, which always
	 * returns the ‘real’ control value. (This is because you can change the \c
	 * required property afterwards, so trying to be smart is not what we want
	 * there.)
	 *
	 * \param $name
	 *   The name of the control
	 *
	 * \return
	 *   The value of the control
	 *
	 * \see AnewtForm::get_control_values
	 * \see AnewtForm::set_control_value
	 */
	function get_control_value($name)
	{
		assert('is_string($name)');
		assert('array_has_key($this->_controls_by_name, $name); // control must exist');

		$control = $this->_controls_by_name[$name];

		if ($control->is_empty() && !$control->get('required'))
			return null;

		return $control->get('value');
	}

	/**
	 * Set the value of a control.
	 *
	 * The specified control must exist.
	 *
	 * \param $name
	 *   The name of the control
	 *
	 * \param $value
	 *   The value to set
	 *
	 * \see AnewtForm::get_control_value
	 */
	function set_control_value($name, $value)
	{
		assert('is_string($name)');
		assert('array_has_key($this->_controls_by_name, $name); // control must exist');
		$this->_controls_by_name[$name]->set('value', $value);
	}

	/**
	 * Retrieve all form values.
	 *
	 * See AnewtForm::get_control_value for a description of the values returned
	 * by this method, which may contain \c NULL in some cases.
	 *
	 * \return
	 *   Associative array with all form control values by control name.
	 *
	 * \see AnewtForm::get_control_value
	 */
	function get_control_values()
	{
		$out = array();
		foreach (array_keys($this->_controls_by_name) as $name)
			$out[$name] = $this->get_control_value($name);

		return $out;
	}

	/** \} */


	/**
	 * \{
	 * \name Form processing methods
	 */

	/**
	 * Process this form.
	 *
	 * This will validate the form and all controls, and call either
	 * handle_valid() or handle_invalid() depending on the validation results.
	 * The return value of the handle_valid() or handle_invalid() function is
	 * returned to the caller of this method.
	 *
	 * If the handle_valid() and handle_invalid() methods are not overridden
	 * \c true and \c false are returned, respectively. This allows calling code
	 * to use something like this:
	 * <code>if ($form->process()) { ... } else { ... }</code>
	 *
	 * \return
	 *   Return value from handle_valid() or handle_invalid();
	 *
	 * \see AnewtForm::is_valid
	 * \see AnewtForm::handle_valid
	 * \see AnewtForm::handle_invalid
	 */
	function process()
	{
		if ($this->is_valid())
			return $this->handle_valid();
		else
			return $this->handle_invalid();
	}

	/**
	 * Callback when form validation was succesful.
	 *
	 * This method returns true. You may override this method in subclasses.
	 */
	function handle_valid()
	{
		return true;
	}

	/**
	 * Callback when form validation did not succeed.
	 *
	 * This method returns \c false. You may override this method in subclasses.
	 */
	function handle_invalid()
	{
		return false;
	}

	/** \} */


	/**
	 * \{
	 * \name Validation methods
	 */

	/**
	 * Checks whether the form is completely valid. This iterates over all
	 * controls and checks their validity as well.
	 *
	 * \return True if valid, false otherwise
	 */
	function is_valid()
	{
		/* The default return value is true. This does not expose security or
		 * validation vulnerabilities, because forms without controls are always
		 * considered valid. */
		$result = true;

		/* Now validate all controls */
		foreach (array_keys($this->_controls_by_name) as $name)
		{
			$result = $this->_controls_by_name[$name]->is_valid() && $result;
		}

		return $result;
	}

	/** \} */


	/**
	 * \{
	 * \name Methods for controls and other form elements
	 */

	/**
	 * Add a control to this form.
	 *
	 * \param $control
	 *   The form control instance to add
	 */
	function add_control($control)
	{
		assert('$control instanceof AnewtFormControl');

		$name = $control->get('name');
		assert('!array_has_key($this->_controls_by_name, $name); // form control names must be unique');
		$control->_set_form($this);

		$this->_controls_by_name[$name] = $control;

		/* Keep an additional list of references to hidden controls */
		if ($control instanceof AnewtFormControlHidden)
			$this->_hidden_controls[] = $control;

		$this->_children[] = $control;
	}

	/**
	 * Easily add a hidden control to this form.
	 *
	 * This is a convenience method to easily add a hidden form control to this
	 * form. It is exactly the same as creating an AnewtFormControlHidden
	 * instance manually and adding to the form using AnewtForm::add_control
	 *
	 * \param $name
	 *   The name of the control
	 *
	 * \param $value
	 *   The hidden value
	 *
	 * \see AnewtForm::add_control
	 * \see AnewtFormControlHidden
	 */
	function add_hidden_control($name, $value)
	{
		$control = new AnewtFormControlHidden($name);
		$control->set('value', $value);
		$this->add_control($control);
	}

	/**
	 * Add a fieldset to this form.
	 *
	 * \param $fieldset
	 *   The fieldset instance to add
	 */
	function add_fieldset($fieldset)
	{
		assert('$fieldset instanceof AnewtFormFieldset');
		$this->_children[] = $fieldset;
		$fieldset_name = $fieldset->_get('name');
		assert('!array_key_exists($fieldset_name, $this->_fieldsets_by_name); // fieldset names must be unique');
		$this->_fieldsets_by_name[$fieldset_name] = $fieldset;

		foreach (array_keys($fieldset->_children) as $key)
		{
			if ($fieldset->_children[$key] instanceof AnewtFormControl)
			{
				$control_name = $fieldset->_children[$key]->get('name');
				assert('!array_key_exists($control_name, $this->_controls_by_name); // control names must be unique');
				$this->_controls_by_name[$control_name] = $fieldset->_children[$key];
			}
		}
	}

	/**
	 * Add a custom node to this form.
	 *
	 * This node is directly embedded in the rendered output by form renderers.
	 *
	 * \param $node
	 *   An AnewtXMLDomNode to embed in the form.
	 */
	function add_node($node)
	{
		assert('$node instanceof AnewtXMLDomNode;');
		$this->_children[] = $node;
	}

	/**
	 * Check whether a control with this name already exists.
	 *
	 * \param $name
	 *   The name of the control
	 *
	 * \return
	 *   Boolean indicating Whether the control exists
	 */
	function has_control($name)
	{
		assert('is_string($name)');
		return array_key_exists($name, $this->_controls_by_name);
	}

	/**
	 * Get a reference to a control.
	 *
	 * The control must exist for this function to work.
	 *
	 * \param $name
	 *   The name of the control
	 *
	 * \return
	 *   A reference to the control instance
	 */
	function get_control($name)
	{
		assert('is_string($name)');
		assert('array_has_key($this->_controls_by_name, $name); // control must exist');
		return $this->_controls_by_name[$name];
	}

	/**
	 * Get a reference to a fieldset.
	 *
	 * The fieldset must exist for this function to work.
	 *
	 * \param $name
	 *   The name of the fieldset
	 *
	 * \return
	 *   A reference to the AnewtFormFieldset instance
	 */
	function get_fieldset($name)
	{
		assert('is_string($name)');
		assert('array_has_key($this->_fieldsets_by_name, $name); // fieldset must exist');
		return $this->_fieldsets_by_name[$name];
	}

	/**
	 * \protected
	 *
	 * Return all children on this form as a list.
	 *
	 * This method is intended for form renderer implementations only.
	 *
	 * \return
	 *   List of children, e.g. form control instances and fieldsets.
	 */
	function _children()
	{
		return $this->_children;
	}

	/**
	 * Return hidden form controls.
	 *
	 * This method is intended for form renderer implementations only.
	 *
	 * \return
	 *   Array of hidden form controls (may be empty)
	 */
	function _hidden_controls()
	{
		return $this->_hidden_controls;
	}

	/**
	 * Check whether this form contains a file upload control (or a derived
	 * control). This is useful to find out which enctype the form should have.
	 *
	 * This method is intended for form renderer implementations only.
	 *
	 * \return
	 *   True if the form contains a file upload control, false otherwise.
	 *
	 * \todo Implement AnewtFormControlFileUpload
	 * \todo Set a special flag in add_control instead of using this method
	 */
	function _contains_file_upload_control()
	{
		foreach (array_keys($this->_controls_by_name) as $key) {
			if ($this->_controls_by_name[$key] instanceof AnewtFormControlFileUpload)
				return true;
		}

		return false;
	}

	/** \} */
}

?>
