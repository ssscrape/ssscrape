<?php

/*
 * Anewt, Almost No Effort Web Toolkit, form module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


anewt_include('form/renderer');


/**
 * Default form renderer.
 *
 * This form renderer will render all controls into div elements, handles
 * fieldsets and hidden inputs correctly, and supports custom nodes as well. The
 * \c error, \c description and \c label properties are properly rendered before
 * each control widget. Many output elements have class names for easy styling
 * using CSS.
 */
class AnewtFormRendererDefault extends AnewtFormRenderer
{
	/**
	 * Render the form into XHTML.
	 */
	function render_default()
	{
		$f = $this->build_form_element();

		$f->append_child($this->_build_error_node($this->_form));
		$f->append_child($this->_build_description_node($this->_form));

		/* Add all form children */

		$children = $this->_form->_children();
		foreach (array_keys($children) as $key)
		{
			/* Hidden controls are handled by build_form_element() */
			if ($children[$key] instanceof AnewtFormControlHidden)
				continue;

			$f->append_child($this->_build_child_node($children[$key]));
		}

		return $f;
	}

	/**
	 * Build XHTML for a form child.
	 *
	 * \param $node
	 *   A valid form child instance, e.g. AnewtFormControl or
	 *   AnewtFormFieldset.
	 */
	protected function _build_child_node($node)
	{
		if ($node instanceof AnewtFormFieldset)
			$result_node = $this->_build_fieldset_node($node);
		else if ($node instanceof AnewtFormControlChoice)
			/* TODO: Implement proper _build_form_control_choice_node() */
			$result_node = $this->_build_form_control_node($node);
		else if ($node instanceof AnewtFormControl)
			$result_node = $this->_build_form_control_node($node);
		else if ($node instanceof AnewtXMLDomNode)
			$result_node = $node;
		else {
			assert('false; // not reached');
		}

		return $result_node;
	}

	/**
	 * Build XHTML for a form control.
	 *
	 * \param $control
	 *   An AnewtFormControl instance.
	 */
	protected function _build_form_control_node($control)
	{
		$control_div = ax_div_class(null, 'form-control');

		$control_div->append_child($this->_build_description_node($control));
		$control_div->append_child($this->_build_error_node($control));


		/* Label and the widget itself are combined. Buttons do not have
		 * explicit labels, since the label text is on the button itself. */

		$widget = $control->build_widget();

		$label_text = $control->_get('label');
		if (is_null($label_text) || $control instanceof AnewtFormControlButton)
		{
			/* No label (none set or this is a button) */
			$control_div->append_child($widget);

		} else
		{
			/* This control has a label */

			assert('is_string($label_text) || $label_text instanceof AnewtXMLDomNode');
			$label = new AnewtXHTMLLabel($label_text, array(
				'class' => 'form-control',
			));

			/* Some composite widgets support allow the 'for=' attribute to be
			 * filled in, even though they consist of multiple html widgets. */
			if ($control->get('composite'))
			{
				$composite_for = $control->get('composite-for');
				if (!is_null($composite_for))
					$label->set_attribute('for', $composite_for);
			}
			else
				$label->set_attribute('for', $control->get('id'));


			/* Help text */

			$help = $control->_get('help');
			if (!is_null($help))
			{
				$help_text = to_string($help);
				$label->set_attribute('title', $help_text);
				$label->add_class('with-help');
			}

			$control_div->append_child($label);
			$control_div->append_child($widget);
			unset ($label);
		}
		unset ($widget);
		return $control_div;
	}

	/**
	 * Build XHTML for a fieldset
	 *
	 * \param $fieldset
	 *   An AnewtFormFieldset instance.
	 */
	protected function _build_fieldset_node($fieldset)
	{
		$fieldset_node = new AnewtXHTMLFieldset();

		$id = $fieldset->_get('id');
		if (!is_null($id))
			$fieldset_node->set_attribute('id', $id);

		$class = $fieldset->_get('class');
		if (!is_null($class))
			$fieldset_node->set_attribute('class', $class);

		$label = $fieldset->_get('label');
		if (!is_null($label))
		{
			$legend_node = new AnewtXHTMLLegend();
			$legend_node->append_child($label);
			$fieldset_node->append_child($legend_node);
		}

		$fieldset_node->append_child($this->_build_description_node($fieldset));
		$fieldset_node->append_child($this->_build_error_node($fieldset));

		foreach (array_keys($fieldset->_children) as $key)
		{
			$fieldset_node->append_child(
				$this->_build_child_node($fieldset->_children[$key]));
		}

		return $fieldset_node;
	}


	/**
	 * Build a description node.
	 */
	protected function _build_description_node($obj)
	{
		$description = $obj->_get('description');

		if (is_null($description))
		{
			$out = null;
			return $out;
		}

		$description_node = new AnewtXHTMLDiv();
		if (is_string($description))
		{
			/* Wrap string in a paragraph. */
			$description_node->append_child(ax_p($description));
		} else {
			/* Assume it's an XHTML element and append it directly */
			assert('$description instanceof AnewtXMLDomNode;');
			$description_node->append_child($description);
		}

		if ($obj instanceof AnewtForm)
			$class = 'form-description form-description-form';
		elseif ($obj instanceof AnewtFormFieldset)
			$class = 'form-description form-description-fieldset';
		elseif ($obj instanceof AnewtFormControl)
			$class = 'form-description form-description-control';
		else
			assert('false; // not reached');

		$description_node->set_class($class);
		return $description_node;
	}


	/**
	 * Build an error node.
	 */
	protected function _build_error_node($obj)
	{
		/* FIXME: share code with _build_description_node */
		$error = $obj->_get('error');

		if (is_null($error))
		{
			$out = null;
			return $out;
		}

		$error_node = new AnewtXHTMLDiv();
		if (is_string($error))
		{
			/* Wrap string in a paragraph. */
			$error_node->append_child(ax_p($error));
		} else {
			/* Assume it's an XHTML element and append it directly */
			assert('$error instanceof AnewtXMLDomNode;');
			$error_node->append_child($error);
		}

		if ($obj instanceof AnewtForm)
			$class = 'form-error form-error-form';
		elseif ($obj instanceof AnewtFormFieldset)
			$class = 'form-error form-error-fieldset';
		elseif ($obj instanceof AnewtFormControl)
			$class = 'form-error form-error-control';
		else
			assert('false; // not reached');

		$error_node->set_class($class);
		return $error_node;
	}
}

?>
