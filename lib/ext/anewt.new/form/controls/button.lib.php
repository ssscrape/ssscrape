<?php

/*
 * Anewt, Almost No Effort Web Toolkit, form module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Base button control.
 *
 * This class provides basic button functionality.
 *
 * Notet that using this class directly is almost always not what you want.
 * Submit and reset buttons are provided by descendants of this class,
 * AnewtFormControlButtonSubmit and AnewtFormControlButtonReset. This class is
 * only for buttons that don't do anything unless behaviour is attached to them
 * using JavaScript.
 *
 * \see AnewtFormControlButtonSubmit
 * \see AnewtFormControlButtonReset
 */
class AnewtFormControlButton extends AnewtFormControl
{
	/**
	 * \protected
	 *
	 * Create a new base button.
	 *
	 * This constructor must be called from subclass constructors, e.g.
	 * AnewtFormControlButtonSubmit and AnewtFormControlButtonReset
	 *
	 * \param $name
	 *   The name of this control.
	 */
	function __construct($name)
	{
		parent::__construct($name);

		$this->_seed(array(
			'input-type'  => 'button',
			'extra-class' => 'button-normal',

			/* Most buttons cannot be edited, so their value should not be
			 * filled from incoming data such as $_GET or $_POST, or other
			 * arrays passed to AnewtForm::fill() */
			'can-be-filled' => false,

			/* Whether a 'name' attribute should be included when rendering this
			 * form button. It can be set to false to reduce URI clutter for
			 * simple GET forms. The 'id' attribute is always include, so
			 * setting this to false does not limit scripting possibilities. */
			'render-name' => true,
		));
	}

	/**
	 * Build widget HTML for this form control.
	 */
	function build_widget()
	{
		/* Output <input ... /> */

		$attr = array(
			'id'    => $this->get('id'),
			'type'  => $this->_get('input-type'),
			'class' => sprintf('button %s', $this->_get('extra-class')),
		);

		if ($this->_get('render-name'))
			$attr['name'] = $this->get('name');

		$label = $this->get('label');
		if (!is_null($label))
			$attr['value'] = $label;

		if ($this->get('disabled'))
			$attr['disabled'] = 'disabled';

		$widget = new AnewtXHTMLInput($attr);

		/* Optional extra class value */
		$class = $this->_get('class');
		if (!is_null($class))
			$widget->add_class($class);

		/* Help text, if any */
		$help = $this->_get('help');
		if (!is_null($help))
		{
			$help_text = to_string($help);
			$widget->set_attribute('title', $help_text);
			$widget->add_class('with-help');
		}

		return $widget;
	}
}


/**
 * Submit control form button.
 */
class AnewtFormControlButtonSubmit extends AnewtFormControlButton
{
	/**
	 * Create a new submit button.
	 *
	 * \param $name
	 *   The name of this control.
	 */
	function __construct($name)
	{
		parent::__construct($name);
		$this->_seed(array(
			'input-type'  => 'submit',
			'extra-class' => 'button-submit',

			/* Submit buttons can be filled. In case a form has multiple submit
			 * buttons, the value on the one that was used to submit the form
			 * will be set to true. */
			'can-be-filled' => true,
		));
	}

	function fill($values)
	{
		/* See the comments for the 'can-be-filled' property above. */

		$name = $this->get('name');
		if (array_key_exists($name, $values))
			$this->set('value', true);

		return true;
	}
}

/**
 * Reset control form button.
 */
class AnewtFormControlButtonReset extends AnewtFormControlButton
{
	/**
	 * Create a new reset button.
	 *
	 * \param $name
	 *   The name of this control.
	 */
	function __construct($name)
	{
		parent::__construct($name);
		$this->_seed(array(
			'input-type'  => 'reset',
			'extra-class' => 'button-reset',
		));
	}
}

/* TODO: AnewtFormControlButtonImage */

?>
