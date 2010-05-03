<?php

/*
 * Anewt, Almost No Effort Web Toolkit, form module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Form control for a single checkbox.
 *
 * This control renders a HTML <code>input</code> element. Checkboxes have two
 * labels, \c label and \c secondary-label, both of which may be set (both
 * optional). Form renderers should render the \c label value just like labels for
 * other controls are rendered. The \c secondary-label (if set) will be shown
 * right next to the checkbox itself.
 *
 * If you want multiple consecutive AnewtFormControlCheckbox controls to have
 * the user check zero or more items, consider using AnewtFormControlChoice with
 * the \c multiple property enabled instead.
 *
 * Note that for yes/no-like questions you may also use AnewtFormControlChoice
 * with just two options (and make sure it renders as two radio buttons; this is
 * the default if you have just 2 options).
 */
class AnewtFormControlCheckbox extends AnewtFormControl
{
	/**
	 * Create a new AnewtFormControlCheckbox instance.
	 *
	 * \param $name
	 *   The name of this control.
	 */
	function __construct($name)
	{
		parent::__construct($name);
		$this->_seed(array(
			'value' => false,
		));
	}

	function fill($values)
	{
		/* If the value is not present, uncheck the checkbox (since
		 * unchecked form controls are not submitted by user agents and
		 * hence do not turn up in the $values array) */
		$name = $this->get('name');
		$value = (bool) array_get_default($values, $name, false);
		parent::fill(array($name => $value));

		/* Filling is always succesful */
		return true;
	}

	function build_widget()
	{
		$id = $this->get('id');
		$attr = array(
			'name'  => $this->get('name'),
			'id'    => $id,
			'type'  => 'checkbox',
		);

		if ($this->_get('disabled'))
			$attr['disabled'] = 'disabled';

		if ($this->_get('value'))
			$attr['checked'] = 'checked';

		/* Note: Checkboxes cannot be set readonly (only from JavaScript) */

		$help = $this->_get('help');
		if (!is_null($help))
		{
			$help_text = to_string($help);
			$attr['title'] = $help_text;
			$attr['class'] = 'with-help';
		}


		/* Output */

		$input = new AnewtXHTMLInput(null, $attr);

		$secondary_label = $this->_get('secondary-label');
		if (is_null($secondary_label))
		{
			$out = $input;

		} else
		{
			$label_attr = array();

			if (!is_null($help))
			{
				$label_attr['title'] = $help_text;
				$label_attr['class'] = 'with-help';
			}

			$out = new AnewtXHTMLLabel(
				$input, $secondary_label,
				$label_attr);
		}

		return $out;
	}
}


/**
 * Form control for choices.
 *
 * This control is a dynamic control that allows the user to make a choice
 * between various alternative options. Options can be added using the
 * add_option() or add_option_value_label() methods (see AnewtFormOption for
 * more information). Option groups can be added using add_option_group() (see
 * AnewtFormOptionGroup for more information).
 *
 * How this control will be rendered depending on the \c multiple property
 * (disabled by default) and the \c threshold property (set to 7 by default).
 * Depending on these values, this control will be shown as:
 *
 * - a list of radio buttons (\c multiple is \c false and threshold is not
 *   exceeded),
 * - a list of checkboxes (\c multiple is set and threshold is not exceeded),
 * - a drop down menu or single select list (\c multiple is \c false and
 *   threshold is exceeded; rendering depends on the value of \c size), or
 * - a multiple select list (\c multiple is \c true and threshold is exceeded).
 *
 * If the threshold is exceeded the \c size property is taken into account. If
 * a \c size is provided (it's \c null by default), a list of options with the
 * specified \c size as its height is shown. Setting \c size on a single select
 * control forces a selection list instead of a dropdown select box in case the
 * threshold is exceeded.
 *
 * \see AnewtFormOption
 */
class AnewtFormControlChoice extends AnewtFormControl
{
	/**
	 * \private
	 *
	 * List of options.
	 *
	 * Note that this list may contain both AnewtFormOption and
	 * AnewtFormOptionGroup instances.
	 */
	private $_options = array();

	/**
	 * \private
	 *
	 * Whether this control has at least one selected item. This flag is for
	 * internal use only and is only valid when called directly after setting
	 * values.
	 */
	public $at_least_one_selected;


	/**
	 * Create a new AnewtFormControlChoice instance.
	 *
	 * \param $name
	 *   The name of this control.
	 */
	function __construct($name)
	{
		parent::__construct($name);
		$this->_seed(array(
			'multiple'  => false,
			'threshold' => 7,
			'size'      => null,
		));
	}


	/** \{
	 * \name Methods for getting and setting values
	 */

	function fill($values)
	{
		/* See AnewtFormControl::fill() for why: */
		if (!$this->_get('can-be-filled')) return true;
		if ($this->_get('disabled'))       return true;

		/* If none of the values in the choice control are selected, there is no
		 * value for this control in $values (just like for checkboxes). */
		$name = $this->get('name');
		$value = array_get_default($values, $name, null);
		parent::fill(array($name => $value));

		/* Filling succeeds if... */
		return $this->get('multiple')         /* (1) this is a multiple select control */
			|| array_has_key($values, $name)  /* (2) the value was actually set */
			|| $this->all_disabled();         /* (3) if all options are disabled */
	}

	/**
	 * \private
	 *
	 * Getter method to obtain the selected option values.
	 *
	 * External callers should use <code>get('value')</code> instead.
	 *
	 * \return
	 *   An array values when \c multiple is enabled, a single value if this is
	 *   a single select control.
	 */
	function get_value()
	{
		$multiple = $this->_get('multiple');

		$values = array();
		foreach ($this->_options as $option_or_option_group)
		{
			foreach ($option_or_option_group->get('values') as $value)
				$values[] = $value;

			/* Break early if only one value is needed */
			if (!$multiple && $values)
				return $values[0];
		}

		/* If this is a single select control, but no value was set, null is
		 * returned. */
		if (!$multiple)
			return null;

		return $values;
	}

	/**
	 * \private
	 *
	 * Setter method to enable the correct options.
	 *
	 * External callers should use <code>set('value', 'the-value-here')</code>
	 * instead.
	 *
	 * \param $value
	 *   The value to set
	 */
	function set_value($value)
	{
		/* Now set the new value(s). It's easier if we treat the values as an
		 * array of values, even if only one value was provided. */

		$this->at_least_one_selected = false;
		$multiple = $this->_get('multiple');

		if (is_numeric_array($value))
		{
			/* Multiple values provided. This only works for multiple select
			 * choice controls; for single select control just use the first
			 * value. */
			$values = $value;
			
			if (!$multiple)
				$values = array_slice($values, 0, 1);
		}
		else
		{
			/* Only a single value was provided. Make a 1-item array for it. */
			$values = array($value);
		}


		/* Iterate over the options and select the right ones */

		foreach ($this->_options as $option_or_option_group)
		{
			$option_or_option_group->set('value', $values);

			if ($option_or_option_group->at_least_one_selected)
			{
				$this->at_least_one_selected = true;

				/* Don't set any other values if this is a single select control */
				if (!$multiple)
					$values = array();
			}
		}

		/* Make sure there's always at least one value selected for single
		 * select choice controls. FIXME: this is controversial... */

		if (!$multiple && !$this->at_least_one_selected)
			$this->_ensure_selection();
	}

	/**
	 * \private
	 *
	 * Returns whether this widget is a composite widget.
	 *
	 * Choice form control widgets are only composite in certain situations
	 * (depending on the mode).
	 */
	function get_composite()
	{
		return count($this->_options) <= $this->_get('threshold');
	}

	/**
	 * \private
	 *
	 * Returns the id to be used to link labels to widgets.
	 *
	 * This method must only be used if this control is composite.
	 */
	function get_composite_for()
	{
		return $this->get('id');
	}

	/**
	 * Returns if a valid value is supplied.
	 *
	 * Very basic validation. Only checks whether exactly one value is supplied
	 * for a single choice control.
	 */
	function is_valid()
	{
		$value = $this->get('value');
		if (!$this->_get('multiple') && is_null($value))
		{
			$this->set('error', 'Invalid value');
			return false;
		}
		return parent::is_valid();
	}

	/** \{
	 * \name Option methods
	 */

	/**
	 * Add an AnewtFormOption or AnewtFormOptionGroup instance to this control.
	 *
	 * For simple cases you may want to use add_option_value_label() instead.
	 *
	 * \param $option
	 *   An AnewtFormOption or AnewtFormOptionGroup instance.
	 *
	 * \see add_option_value_label
	 * \see AnewtFormOption
	 * \see AnewtFormOptionGroup
	 */
	function add_option($option)
	{
		assert('$option instanceof AnewtFormOption');
		$option->_choice_control = $this;

		/* Select first option by default.  This avoids radio button
		 * listings without any radio button in a selected state. */
		if (!$this->_get('multiple') && !$this->at_least_one_selected)
			$this->at_least_one_selected = $option->_ensure_selection();

		$this->_options[] = $option;

		/* Option groups are only supported for <select> elements, so set the
		 * threshold to 0 so that the control is always rendered as a drop down
		 * select or (multiple) select box. */
		if ($option instanceof AnewtFormOptionGroup)
			$this->_set('threshold', 0);
	}

	/**
	 * Add a new option to this control.
	 *
	 * This is a convenience method to easily add an option to this control. Use
	 * add_option() if you want more control over the AnewtFormOption element
	 * added to this control.
	 *
	 * \param $value
	 *   The value of this option
	 *
	 * \param $label 
	 *   The visible name for this option
	 *
	 * \see add_option
	 * \see AnewtFormOption
	 */
	function add_option_value_label($value, $label)
	{
		$option = new AnewtFormOption($value, $label);
		$this->add_option($option);
	}

	/**
	 * Add an AnewtFormOptionGroup instance to this control.
	 *
	 * \param $option_group
	 *   An AnewtFormOptionGroup instance.
	 *
	 * \see add_option
	 * \see AnewtFormOptionGroup
	 */
	function add_option_group($option_group)
	{
		assert('$option_group instanceof AnewtFormOptionGroup');
		$this->add_option($option_group);
	}

	/**
	 * \private
	 *
	 * Make sure a valid selection is set.
	 */
	function _ensure_selection()
	{
		/* Nothing to do for multiple select controls */
		if ($this->_get('multiple'))
			return;

		/* Select the first non-disabled option */
		foreach ($this->_options as $option_or_option_group)
		{
			if ($option_or_option_group->_ensure_selection(false))
			{
				$this->at_least_one_selected = true;
				break;
			}
		}
	}

	/**
	 * \private
	 *
	 * Check if all options are disabled.
	 *
	 * For a single select control, this is the only valid case for having no
	 * options enabled.
	 *
	 * \return
	 *   Returns \c false if there are no enabled controls. Returns \c true
	 *   otherwise.
	 */
	function all_disabled()
	{
		foreach ($this->_options as $option)
		{
			if (!$option->all_disabled())
				return false;
		}
		return true;
	}

	/** \} */


	/** \{
	 * \name Rendering methods
	 */

	function build_widget()
	{
		$name = $this->get('name');
		$id = $this->get('id');
		$multiple = $this->_get('multiple');
		$threshold = $this->_get('threshold');
		$num_options = count($this->_options);

		/* Decide how to render. The values 0 and -1 are special, and if there
		 * are no options, always render a single element (perhaps JavaScript is
		 * used to populate the control). */
		if ($num_options === 0)
			$render_many_elements = false;
		elseif ($threshold === -1)
			$render_many_elements = true;
		elseif ($threshold === 0)
			$render_many_elements = false;
		elseif ($num_options <= $threshold)
			$render_many_elements = true;
		else
			$render_many_elements = false;

		if ($render_many_elements)
		{
			/* Render listing of checkboxes or radio buttons */

			$out = new AnewtXHTMLFragment();
			foreach ($this->_options as $option)
			{
				if ($multiple)
					$child = $option->_build_checkbox();
				else
					$child = $option->_build_radiobutton();

				$out->append_child($child);
			}

			/* Set the id property on the first child (this is the radio button
			 * or checkbox) of the first option. */
			if ($this->_options)
				$out->child_nodes[0]->child_nodes[0]->set_attribute('id', $id);
		} else
		{
			/* Render single select element */

			if ($multiple)
				$out = new AnewtXHTMLSelect(null, array(
					'name'     => sprintf('%s[]', $name),
					'multiple' => 'multiple',
				));
			else
				$out = new AnewtXHTMLSelect(null, array(
					'name' => $name,
				));

			/* Set the id property. */
			$out->set_attribute('id', $id);

			/* Set the height if provided. If the 'multiple' property is not
			 * set, this forces a drop down select box to be displayed as
			 * a multiline selection list. */
			$size = $this->_get('size');
			if (!is_null($size))
			{
				assert('is_int($size);');
				$out->set_attribute('size', (string) $size);
			}

			if ($this->_get('disabled'))
				$out->set_attribute('disabled', 'disabled');

			foreach ($this->_options as $option_or_option_group)
			{
				$out->append_child($option_or_option_group->_build_option());
			}
		}

		return $out;
	}

	/** \} */
}


/**
 * Form option for AnewtFormControlChoice.
 *
 * This class represents a single choice for an AnewtFormControlChoice instance.
 * You can instantiate this class directly and add it to a choice control using
 * AnewtFormControlChoice::add_option(), however for most simple cases
 * AnewtFormControlChoice::add_option_value_label() suffices.
 *
 * In addition to the \c label and \c value, the \c disabled property can be set
 * to disable specific option of an AnewtFormControlChoice. Note that if the
 * containing AnewtFormControlChoice instance itself is \c disabled, all options
 * will be automatically disabled as well.
 *
 * Note that you cannot select form options directly. Use the \c value property
 * of the containing AnewtFormControlChoice instead, e.g. use something like
 * <code>$choice_control->set('value', ...)</code> to set the selection.
 *
 * \see AnewtFormControlChoice
 */
class AnewtFormOption extends Container
{
	/**
	 * \private
	 *
	 * Reference to the parent AnewtFormControlChoice.
	 */
	public $_choice_control;

	/**
	 * \private
	 *
	 * Reference to the parent AnewtFormOptionGroup, if any.
	 */
	public $_option_group;

	/**
	 * \private
	 *
	 * Whether this form option is selected.
	 */
	public $_selected = false;

	/**
	 * \private
	 *
	 * Whether this option is selected. This flag is for internal use only
	 * and is only valid when called directly after setting values on the
	 * parent choice control.
	 */
	public $at_least_one_selected;


	/**
	 * Create a new AnewtFormOption.
	 *
	 * \param $value
	 *   The value that will be used as the AnewtFormControlChoice value if this
	 *   option is selected. If the \c multiple property of the
	 *   AnewtFormControlChoice instance is enabled, this is the value that will
	 *   be included in the list of values of the control.
	 *
	 * \param $label
	 *   The human-visible label.
	 */
	function __construct($value=null, $label=null)
	{
		$this->_seed(array(
			'value'    => $value,
			'label'    => $label,
			'disabled' => false,
		));
	}

	/**
	 * \private
	 *
	 * Get the value of this option.
	 *
	 * This method is only intended to be called internally.
	 */
	public function get_values()
	{
		return $this->_selected
			? array($this->get('value'))
			: array();
	}

	/**
	 * \private
	 *
	 * Set the value for this group.
	 *
	 * This method is only intended to be called internally.
	 */
	public function set_value($values)
	{
		assert('is_numeric_array($values)');
		$this->_selected = (!$this->_get('disabled') && in_array($this->get('value'), $values));

		$this->at_least_one_selected = $this->_selected;
	}

	/**
	 * \private
	 *
	 * Make sure a selection is set.
	 *
	 * This method is for internal use only.
	 */
	public function _ensure_selection($force_first=false)
	{
		$this->_selected = !$this->_get('disabled') || $force_first;
		
		$this->at_least_one_selected = $this->_selected;
		return $this->_selected;
	}

	/**
	 * \private
	 * 
	 * Returns whether this control is disabled.
	 */
	public function all_disabled()
	{
		return $this->_get('disabled');
	}

	/**
	 * \private
	 *
	 * Build an option element to be included in a select element.
	 */
	function _build_option()
	{
		$value = $this->_get('value');
		assert('is_string($value); // option value must be a string');

		$option_attr = array(
			'value' => $value,
		);

		if ($this->_selected)
			$option_attr['selected'] = 'selected';

		if ($this->_choice_control->_get('disabled') || $this->_get('disabled'))
			$option_attr['disabled'] = 'disabled';

		if ($this->_option_group && $this->_option_group->_get('disabled'))
			$option_attr['disabled'] = 'disabled';

		$option = new AnewtXHTMLOption($this->_get('label'), $option_attr);
		return $option;
	}

	/**
	 * \private
	 *
	 * Build a checkbox to be included in multiple select controls.
	 */
	function _build_checkbox()
	{
		$value = $this->_get('value');
		assert('is_string($value); // option value must be a string');

		$input_attr = array(
			'type'  => 'checkbox',
			'name'  => sprintf('%s[]', $this->_choice_control->_get('name')),
			'value' => $value,
		);

		if ($this->_selected)
			$input_attr['checked'] = 'checked';

		if ($this->_choice_control->_get('disabled') || $this->_get('disabled'))
			$input_attr['disabled'] = 'disabled';

		$input = new AnewtXHTMLInput(null, $input_attr);
		$label = $this->_build_label($input);
		return $label;
	}

	/**
	 * \private
	 *
	 * Build a radio button to be included in single select controls.
	 */
	function _build_radiobutton()
	{
		$value = $this->_get('value');
		assert('is_string($value); // option value must be a string');

		$input_attr = array(
			'type'     => 'radio',
			'name'     => $this->_choice_control->_get('name'),
			'value'    => $value,
		);

		if ($this->_selected)
			$input_attr['checked'] = 'checked';

		if ($this->_choice_control->_get('disabled') || $this->_get('disabled'))
			$input_attr['disabled'] = 'disabled';

		$input = new AnewtXHTMLInput(null, $input_attr);
		$label = $this->_build_label($input);
		return $label;
	}

	/**
	 * \private
	 *
	 * Wrap an input element into a label element.
	 */
	function _build_label($input)
	{
		$label_text = $this->_get('label');
		$label = new AnewtXHTMLLabel(
			$input,
			$label_text,
			array(
				'class' => 'choice',
			));
		return $label;
	}
}


/**
 * Form option group for AnewtFormControlChoice.
 *
 * This class represents a group of choices for an AnewtFormControlChoice
 * instance. An option group has a label, which can be set specified using the
 * \c label property. Option groups will be rendered in a visually distinct
 * style. Note that options and option groups can be mixed in a single choice
 * control.
 *
 * Groups themselves cannot be selected, only the options it consists of
 * (AnewtFormOption instance) can be selected. See the documentation for
 * AnewtFormControlChoice and AnewtFormOption for details about selecting values
 * in choice controls.
 *
 * You can instantiate this class directly and add options to it using
 * add_option() (with an AnewtFormOption instance) or using
 * add_option_value_label(). Both methods work exactly like the methods of the
 * same name (and signature) in the AnewtFormControlChoice class, but operate on
 * the option group, instead of on the choice control as a whole.
 *
 * After adding the options, add the the option group to a choice control using
 * AnewtFormControlChoice::add_option_group().
 */
class AnewtFormOptionGroup extends AnewtFormOption
{
	/**
	 * \private
	 *
	 * List of options in this group.
	 *
	 * This is an array of AnewtFormOption instances.
	 */
	public $_options = array();

	/**
	 * \private
	 *
	 * The choice control this group has been added to. This value is only set
	 * after the group was added to a choice control, so it can only be used
	 * when processing or rendering the form.
	 */
	public $_choice_control = null;

	/**
	 * \private
	 *
	 * Whether this group has at least one selected item. This flag is for
	 * internal use only and is only valid when called directly after setting
	 * values on the parent choice control.
	 */
	public $at_least_one_selected;

	/**
	 * Create a new AnewtFormOptionGroup.
	 *
	 * \param $label
	 *   The human-visible label (optional). This can also be set later using
	 *   the \c label property.
	 */
	public function __construct($label=null)
	{
		assert('iS_null($label) || is_string($label);');

		$this->_seed(array(
			'label'    => $label,
			'disabled' => false,
		));
	}

	/**
	 * Add an AnewtFormOption instance to this group.
	 *
	 * For simple cases you may want to use add_option_value_label() instead.
	 *
	 * \param $option
	 *   An AnewtFormOption instance.
	 *
	 * \see add_option_value_label
	 * \see AnewtFormControlChoice::add_option
	 * \see AnewtFormOption
	 */
	public function add_option($option)
	{
		assert('$option instanceof AnewtFormOption;');
		$option->_option_group = $this;
		$this->_options[] = $option;
	}

	/**
	 * Add a new option to this group.
	 *
	 * This is a convenience method to easily add an option to this group. Use
	 * add_option() if you want more control over the AnewtFormOption element
	 * added to this control.
	 *
	 * \param $value
	 *   The value of this option
	 *
	 * \param $label 
	 *   The visible name for this option
	 *
	 * \see add_option
	 * \see AnewtFormControlChoice::add_option_value_label
	 * \see AnewtFormOption
	 */
	public function add_option_value_label($value, $label)
	{
		$option = new AnewtFormOption($value, $label);
		$this->add_option($option);
	}

	/**
	 * \private
	 *
	 * Get the values for this option group.
	 *
	 * This method is for internal use only. Use the AnewtFormControlCheckbox
	 * instance to obtain the current selection.
	 *
	 * \return
	 *   Array of values (may be empty).
	 */
	public function get_values()
	{
		$values = array();
		foreach ($this->_options as $option)
		{
			foreach ($option->get_values() as $value)
				$values[] = $value;
		}
		return $values;
	}

	/**
	 * \private
	 *
	 * Set the value for this group.
	 *
	 * This method is only intended to be called internally.
	 *
	 * \param $values
	 *   Array of values
	 */
	public function set_value($values)
	{
		assert('is_numeric_array($values);');

		$this->at_least_one_selected = false;

		/* If this option is disabled don't return just yet. We have
		 * to make sure previous selected items are unset.
		 */
		if ($this->_get('disabled')) $values = array();

		$multiple = $this->_choice_control->_get('multiple');
		foreach ($this->_options as $option)
		{
			$option->set_value($values);
			if ($option->at_least_one_selected)
			{
				$this->at_least_one_selected = true;

				/* Don't set any other options */
				if (!$multiple)
					$values = array();
			}
		}
	}

	/**
	 * \private
	 *
	 * Make sure a selection is set.
	 *
	 * This method is for internal use only.
	 *
	 * \param $force_first
	 *   Whether to force the first element to be selected.
	 */
	public function _ensure_selection($force_first=false)
	{
		assert('is_bool($force_first);');

		/* Select the first non-disabled option */
		/* Note: if $force_first is true, then it was probably already
		 * called once with $force_first is false and that didn't work,
		 * so no use trying it without $force_first first.
		 */
		$this->at_least_one_selected = false;
		foreach ($this->_options as $option)
		{
			if ($option->_ensure_selection($force_first))
			{
				$this->at_least_one_selected = true;
				break;
			}
		}

		return $this->at_least_one_selected;
	}

	/**
	 * \private
	 *
	 * Checks if all options are disabled.
	 *
	 * \return
	 *   Returns \c true if there are no enabled controls. Returns \c false
	 *   otherwise.
	 */
	public function all_disabled()
	{
		if ($this->_get('disabled'))
			return true;

		foreach ($this->_options as $option)
		{
			if (!$option->all_disabled())
				return false;
		}

		return true;
	}

	/**
	 * \private
	 *
	 * Build an option group element to be included in a select element.
	 */
	public function _build_option()
	{
		$label = $this->get('label');
		assert('is_string($label); // option group must have a string label');

		$option_group_attr = array();
		$option_group_attr['label'] = $label;

		if ($this->_choice_control->_get('disabled') || $this->_get('disabled'))
			$option_group_attr['disabled'] = 'disabled';

		$option_group = new AnewtXHTMLOptionGroup(null, $option_group_attr);

		foreach ($this->_options as $option)
		{
			$option->_choice_control = $this->_choice_control;
			$option_group->append_child($option->_build_option());
		}

		return $option_group;
	}

	/**
	 * Build a checkbox (throws an error).
	 *
	 * AnewtFormOptionGroup cannot be rendered as checkboxes.
	 */
	public function _build_checkbox()
	{
		throw new AnewtException('Option groups cannot be rendered as checkboxes');
	}

	/**
	 * Build a radio button (throws an error).
	 *
	 * AnewtFormOptionGroup cannot be rendered as radio buttons.
	 */
	public function _build_radiobutton()
	{
		throw new AnewtException('Option groups cannot be rendered as radio buttons');
	}
}

?>
