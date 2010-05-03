<?php

/*
 * Anewt, Almost No Effort Web Toolkit, form module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * \private
 *
 * Base form control for text input.
 *
 * This control supports both single line and multiline input and renderers
 * a HTML <code>input</code> or <code>textarea</code> widget.
 *
 * Do not instantiate this class, use one of its descendants instead.
 */
abstract class AnewtFormControlTextBase extends AnewtFormControl
{
	/**
	 * Create a new base text control.
	 *
	 * This constructor must be called from subclass constructors, e.g.
	 * AnewtFormControlText, AnewtFormControlTextMultiline, and
	 * AnewtFormControlPassword.
	 *
	 * \param $name
	 *   The name of this control.
	 */
	function __construct($name)
	{
		parent::__construct($name);
		$this->_seed(array(
			'multiline'  => false,
			'password'   => false,
			'value'      => '',
			'size'       => null,
			'maxlength'  => null,
			'rows'       => 5,
			'columns'    => 72,
			'show-value' => true,
		));
	}

	function build_widget()
	{
		/* Convert value to string if possible */

		$value = $this->_get('value');

		if (is_null($value))
			$value = "";
		elseif (is_int($value) || is_float($value))
			$value = (string) $value;


		if (!is_string($value))
			throw new Exception(sprintf('Text control "%s" can only contain strings or numeric values (%s provided)', $this->get('name'), gettype($value)));


		/* XML tag attributes used both for single line and multiline */

		$attr = array(
			'name'     => $this->get('name'),
			'id'       => $this->get('id'),
		);

		if ($this->_get('readonly'))
			$attr['readonly'] = 'readonly';

		if ($this->_get('disabled'))
			$attr['disabled'] = 'disabled';


		/* Now differentiate between single and multiline controls */

		if ($this->_get('multiline'))
		{
			/* Output textarea element */

			$rows = $this->_get('rows');
			assert('is_int($rows)');
			$attr['rows'] = (string) $rows;

			$columns = $this->_get('columns');
			assert('is_int($columns)');
			$attr['cols'] = (string) $columns;

			$widget = new AnewtXHTMLTextarea($value, $attr);

			/* Styling */

			if (!$this->_get('required'))
				$widget->add_class('optional');

		} else
		{
			/* Output input element */

			$is_password = $this->_get('password');
			if ($is_password) {
				$attr['type'] = 'password';
			} else {
				$attr['type'] = 'text';
			}

			if ($this->_get('show-value')) {
				$attr['value'] =  $value;
			} else {
				$attr['value'] = '';
			}

			$size = $this->_get('size');
			if (!is_null($size))
			{
				assert('is_int($size);');
				$attr['size'] = (string) $size;
			}

			$maxlength = $this->_get('maxlength');
			if (!is_null($maxlength))
			{
				assert('is_int($maxlength);');
				$attr['maxlength'] = (string) $maxlength;
			}

			$widget = new AnewtXHTMLInput(null, $attr);

			/* Styling */

			$widget->add_class('text');

			if ($is_password)
				$widget->add_class('password');

			if (!$this->_get('required'))
				$widget->add_class('text-optional');
		}

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

		/* Add secondary label, if any */
		$secondary_label = $this->_get('secondary-label');
		if (is_null($secondary_label))
			$out = $widget;
		else
			$out = ax_fragment($widget, $secondary_label);

		return $out;
	}

	/** \{
	 * \name Validation methods
	 */

	function is_valid()
	{
		/* If this is a single line text input control, we can do some
		 * preliminary checks to see whether the user has tried to mess things
		 * up by now respecting our value restrictions in the html. This will
		 * not result in pretty error messages, but hey, who's trying to fool
		 * who anyway? */
		if (!$this->_get('multiline'))
		{
			$value = $this->_get('value');

			/* Newlines are invalid */
			if (str_contains($value, "\n") || str_contains($value, "\r")) {
				$this->set('valid', false);
				$this->set('error', 'Invalid newlines');
				return false;
			}

			/* Check maxlength. FIXME: this likeley does not work correctly with
			 * multibyte UTF-8 characters because of strlen() counting bytes
			 * instead of characters... */
			$maxlength = $this->_get('maxlength');
			if (!is_null($maxlength) && strlen($value) > $maxlength) {
				$this->set('valid', false);
				$this->set('error', 'Too long (probably because of multibyte characters)');
				return false;
			}
		}

		/* Now get along with validators and perhaps other validation */
		return parent::is_valid();
	}

	/** \} */
}


/**
 * Text input form control.
 */
class AnewtFormControlText extends AnewtFormControlTextBase
{
	/**
	 * Create a new text form control.
	 *
	 * \param $name
	 *   The name of this control.
	 */
	function __construct($name)
	{
		parent::__construct($name);
		/* Simply inherit AnewtFormControlTextBase */
	}
}


/**
 * Password input form control.
 */
class AnewtFormControlPassword extends AnewtFormControlTextBase
{
	/**
	 * Create a new password form control.
	 *
	 * \param $name
	 *   The name of this control.
	 */
	function __construct($name)
	{
		parent::__construct($name);
		$this->_seed(array(
			'password'   => true,
			'show-value' => false,
		));
	}
}


/**
 * Multiline text input form control.
 */
class AnewtFormControlTextMultiline extends AnewtFormControlTextBase
{
	/**
	 * Create a new multiline text form control.
	 *
	 * \param $name
	 *   The name of this control.
	 */
	function __construct($name)
	{
		parent::__construct($name);
		$this->_set('multiline', true);
	}
}


/**
 * Hidden form control.
 *
 * This control only has name, id (optional) and value properties. This control
 * is not visible when rendered.
 */
class AnewtFormControlHidden extends AnewtFormControl
{
	function build_widget()
	{
		$attr = array(
			'name'  => $this->get('name'),
			'id'    => $this->get('id'),
			'value' => $this->get('value'),
			'type'  => 'hidden',
		);

		$widget = new AnewtXHTMLInput(null, $attr);
		return $widget;
	}
}

?>
