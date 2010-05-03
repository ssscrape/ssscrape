<?php

/*
 * Anewt, Almost No Effort Web Toolkit, XHTML module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


anewt_include('xml/dom');


/**
 * XHTML fragment class use to hold zero or more XHTML elements.
 *
 * This class is useful to group XHTML elements into one entity. It is basically
 * a list that can be inserted into a DOM tree.
 *
 * \see AnewtXMLDomDocumentFragment
 */
final class AnewtXHTMLFragment extends AnewtXMLDomDocumentFragment
{
	/**
	 * Render this fragment into a string.
	 *
	 * This method renders all children and concenates those strings into one
	 * single value. Usually XHTML fragments are not rendered directly, but
	 * added to a DOM tree instead. When that happens, all child nodes of the
	 * fragment are added to the DOM tree, and the document fragment instance
	 * itself is no longer of any use. This means that this method is not
	 * invoked if document fragments are used in combination with a proper DOM
	 * document (e.g. as used by AnewtPage).
	 *
	 * \return
	 *   Rendered XML output or an empty string if the fragment was empty.
	 */
	function render()
	{
		$out = array();
		foreach ($this->child_nodes as $child_node)
		{
			$out[] = $child_node->render();
		}
		return to_string($out);
	}
}


/**
 * \private
 *
 * Base XHTML element class.
 *
 * This class is extended by all of the XHTML element classes.
 * AnewtXHTMLElement provides functionality shared by all XHTML element
 * classes.
 */
abstract class AnewtXHTMLElement extends AnewtXMLDomElement
{
	/* Rendering instructions for XML nodes */

	protected $must_be_empty = false;
	public $always_render_closing_tag = true;


	/**
	 * \protected
	 *
	 * Create a new XHTML element.
	 *
	 * This constructor accepts a variable number of arguments. Each argument
	 * is appended as a child node, but the last argument is handled
	 * differently. If the last argument is an associative array, its values are
	 * used as attribute names and values. If it's not an associative array, it
	 * is treated just as the other arguments and appended as a child node.
	 *
	 * The AnewtXHTMLElement class is abstract and cannot be instantiated
	 * directly. Use one of its descendants instead (all XHTML element classes
	 * extend AnewtXHTMLElement); there is one class for each XHTML element in
	 * the HTML specification.
	 *
	 * \param $children
	 *   One or more child nodes (optional).
	 *
	 * \param $attributes
	 *   Associative array with element attributes (optional).
	 */
	function __construct($children=null, $attributes=null)
	{
		assert('!is_null($this->node_name); // node name must be specified');
		parent::__construct($this->node_name);

		$num_args = func_num_args();

		if ($num_args == 0)
			return;

		$args = func_get_args();

		/* Use last element for attributes, but only if it's an associative
		 * array. */
		if (is_assoc_array($args[$num_args - 1]))
		{
			$attributes = array_pop($args);
			$this->set_attributes($attributes);
		}

		/* Add additional arguments as child nodes. */
		$this->append_children($args);
	}

	/** \{
	 * \name Class methods
	 *
	 * These methods can be used to manipulate the \c class attribute of an
	 * element. These methods are here for convenience only, since the
	 * attributes methods of the AnewtXMLDomElement class van be used as well to
	 * set the value of the \c class attribute.
	 */

	/**
	 * Set a class for this element.
	 *
	 * \param $class_name
	 *   The class name to set.
	 */
	function set_class($class_name)
	{
		assert('is_string($class_name);');
		$this->set_attribute('class', $class_name);
	}

	/**
	 * Add a class to this element.
	 *
	 * The current classes are left as-is.
	 *
	 * \param $class_name
	 *   The class name to add.
	 */
	function add_class($class_name)
	{
		assert('is_string($class_name);');

		if ($this->has_attribute('class'))
			$new_class = sprintf(
				'%s %s',
				$this->get_attribute('class'),
				$class_name
			);
		else
			$new_class = $class_name;

		$this->set_class($new_class);
	}

	/**
	 * Remove class attribute(s) from this element.
	 *
	 * If no parameters are specified, the class attribute is removed.
	 *
	 * If a class name is provided, it will be removed from the current classes
	 * of this element. If the provided class name was not set on this element,
	 * nothing happens.
	 *
	 * \param $class_name
	 *   The class to remove (optional). If not given (or \c null), all classes
	 *   are removed.
	 */
	function remove_class($class_name=null)
	{
		if (!$this->has_attribute('class'))
			return;

		if (is_null($class_name))
		{
			$this->remove_attribute('class');
			return;
		}

		assert('is_string($class_name)');

		$current_classes = explode(' ', $this->get_attribute('class'));
		$new_classes = array();
		foreach ($current_classes as $current_class)
		{
			if ($current_class == $class_name)
				continue;

			$new_classes[] = $current_class;
		}

		$this->set_class(join(' ', $new_classes));
	}

	/** \} */
}


/**
 * \private
 *
 * Base class for block elements.
 *
 * Do not instantiate this class directly. Inline elements are rendered with
 * surrounding whitespace and indentation.
 */
abstract class AnewtXHTMLBlockElement extends AnewtXHTMLElement
{
	public $render_as_block = true;
}

/**
 * \private
 *
 * Base class for inline elements.
 *
 * Do not instantiate this class directly. Inline elements are rendered without
 * surrounding whitespace and indentation.
 */
abstract class AnewtXHTMLInlineElement extends AnewtXHTMLElement
{
	public $render_as_block = false;
}

/**
 * Raw HTML node for literal HTML data.
 *
 * Instances of this class can be used to insert literal HTML (e.g. generated by
 * other means) into the DOM tree. See AnewtXMLDomRaw for more information
 *
 * \see AnewtXMLDomRaw
 */
final class AnewtXHTMLRaw extends AnewtXMLDomRaw
{
}

?>
