<?php

/*
 * Anewt, Almost No Effort Web Toolkit, XML module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


define('ANEWT_XML_DOM_ELEMENT_NODE',                 1);
define('ANEWT_XML_DOM_ATTRIBUTE_NODE',               2); /* Not implemented */
define('ANEWT_XML_DOM_TEXT_NODE',                    3);
define('ANEWT_XML_DOM_CDATA_SECTION_NODE',           4); /* Not implemented */
define('ANEWT_XML_DOM_ENTITY_REFERENCE_NODE',        5); /* Not implemented */
define('ANEWT_XML_DOM_ENTITY_NODE',                  6); /* Not implemented */
define('ANEWT_XML_DOM_PROCESSING_INSTRUCTION_NODE',  7); /* Not implemented */
define('ANEWT_XML_DOM_COMMENT_NODE',                 8);
define('ANEWT_XML_DOM_DOCUMENT_NODE',                9);
define('ANEWT_XML_DOM_DOCUMENT_TYPE_NODE',          10); /* Not implemented */
define('ANEWT_XML_DOM_DOCUMENT_FRAGMENT_NODE',      11);
define('ANEWT_XML_DOM_NOTATION_NODE',               12); /* Not implemented */

/* Anewt-specific addition for raw nodes */
define('ANEWT_XML_DOM_RAW_NODE',                    99);


/**
 * Base node class.
 *
 * AnewtXMLDomNode instances cannot be instantiated directly, since this class
 * only serve as a base class for e.g. AnewtXMLDomElement and AnewtXMLDomText.
 *
 * \todo: Implement first_child, last_child, previous_sibling and next_sibling
 * attributes in node trees.
 */
abstract class AnewtXMLDomNode
{
	/**
	 * The node type.
	 *
	 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#ID-1060184317
	 */
	public $node_type = null;

	/**
	 * The name of this node, depending on its type.
	 *
	 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#ID-F68D095
	 */
	public $node_name = null;

	/**
	 * Hash of all attributes
	 *
	 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#ID-84CF096
	 */
	protected $_attributes = null;

	/**
	 * The AnewtXMLDomDocument object associated with this node.
	 *
	 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#ID-1060184317
	 */
	public $owner_document = null;

	/**
	 * List of child nodes
	 *
	 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#ID-84CF096
	 */
	public $child_nodes = array();

	/**
	 * The parent of this node
	 *
	 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#ID-84CF096
	 */
	public $parent_node = null;

	/**
	 * Used for identity checking
	 */
	private $__object_id = null;

	/**
	 * List of allowed attributes
	 */
	protected $allowed_attributes = array();

	/**
	 * Whether this node can contain children.
	 */
	protected $must_be_empty = false;

	/**
	 * List of allowed child element names
	 */
	protected $allowed_elements = array();

	/**
	 * Whether this node can have text content
	 */
	protected $allows_text = true;


	/**
	 * Construct and initialise a new node instance.
	 */
	function __construct()
	{
		/* Do nothing */
	}

	/**
	 * Create a new DOM node for any value.
	 *
	 * This method converts any value into a AnewtXMLDomNode instance (or
	 * subclass thereof). If the value is a renderer object, it is rendered. If
	 * the renderer object renders an AnewtXMLDomNode itself, it is used, else
	 * the rendered value is converted to a text node (which takes care of
	 * escaping).
	 *
	 * \param $value
	 *   The value to convert
	 *
	 * \return
	 *   An AnewtXMLDomNode instance (or subclass).
	 */
	static public function create_for_value($value)
	{
		/* If the argument is a already a node, just return it */
		if ($value instanceof AnewtXMLDomNode)
			return $value;

		/* This is not a DOM node, so one needs to be created. Take special care of
		 * renderer objects that can render itself into a DOM node. All other values
		 * are wrapped in a text node instead. The actual string conversion and
		 * escaping is done by the AnewtXMLDomText instance when rendered.
		 */

		/* This is not a DOM node, so one needs to be created. render() methods
		 * may return new objects, which may be either renderers themselves or
		 * (hopefully) eventually an AnewtXMLDomNode instance. Recursively
		 * render $value into something that can be added to the DOM tree. */
		while (is_object($value) && !($value instanceof AnewtXMLDomNode) && method_exists($value, 'render'))
			$value = $value->render();

		if ($value instanceof AnewtXMLDomNode)
		{
			/* If we end up with an AnewtXMLDomNode instance, we just return it. */
			$out = $value;
		} else {
			/* We ended up with something different. Just return a text node for
			 * this value. AnewtXMLDomText takes care of string conversion and
			 * escaping later (when rendering to a string). */
			$out = new AnewtXMLDomText($value);
		}

		return $out;
	}

	/**
	 * Returns whether this node is the same node as the given one.
	 *
	 * \param $other_node
	 *   A AnewtXMLDomNode instance to test against.
	 *
	 * \return
	 *   Returns true if the nodes are the same, false otherwise.
	 *
	 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#Node3-isSameNode
	 */
	public function is_same_node($other_node)
	{
		assert('$other_node instanceof AnewtXMLDomNode;');
		return $this->__object_id === $other_node->__object_id;
	}

	/**
	 * Adds the node $new_child to the end of the list of children of this node.
	 * The node may not be attached elsewhere in the tree already.
	 *
	 * \param $new_child
	 *   The node to add.
	 *
	 * \return
	 *   The node added.
	 *
	 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#ID-184E7107
	 */
	public function append_child($new_child)
	{
		/* Sanity checks */

		if (is_null($new_child)) {
			$out = null;
			return $out;
		}

		assert('!$this->must_be_empty; // element cannot contain content');
		if ($new_child instanceof AnewtXMLDomElement) {
			assert('!$this->allowed_elements || in_array($new_child->node_name, $this->allowed_elements); // invalid child element');
		} elseif ($new_child instanceof AnewtXMLDomText) {
			assert('$this->allows_text; // element does not allow text content');
		}

		/* Convert child value to a node (if needed) */

		if (!($new_child instanceof AnewtXMLDomNode))
			$new_child = AnewtXMLDomNode::create_for_value($new_child);

		assert('$new_child instanceof AnewtXMLDomNode;');
		assert('is_null($new_child->parent_node); // node was already added to another element');


		/* Now add the node as a child to this node */

		$out = null;
		if ($new_child instanceof AnewtXMLDomDocumentFragment)
		{
			/* Document fragments are treated differently. All children of the
			 * document fragment are appended instead. Return value is the first
			 * child (if any). */
			if ($new_child->child_nodes)
			{
				$out = &$new_child->child_nodes[0];
				foreach (array_keys($new_child->child_nodes) as $k)
				{
					unset($new_child->child_nodes[$k]->parent_node);
					$new_child->child_nodes[$k]->parent_node = null;
					$this->append_child($new_child->child_nodes[$k]);
				}
			}
		} else {
			$this->child_nodes[] = &$new_child;
			$new_child->parent_node = &$this;
			$out = &$new_child; 
		}

		unset ($new_child);
		return $out;
	}

	/**
	 * Recursively appends children to this element.
	 *
	 * Strings are converted into text nodes. This method expands numerical
	 * arrays and ignores null values.
	 *
	 * \param $new_children
	 *   Array of children elements
	 */
	public function append_children(&$new_children)
	{
		assert('is_numeric_array($new_children);');

		foreach ($new_children as $new_child)
		{
			/* Skip null values. This may happen if a constructor was called by
			 * the convenience API with attributes=null, or if consuming code
			 * passes null instances. */
			if (is_null($new_child))
				continue;

			/* If $new_child is an array, recursively add it to this node. */
			elseif (is_numeric_array($new_child))
			{
				$this->append_children($new_child);
				continue;
			}

			$this->append_child($new_child);
			unset ($new_child);
		}
	}

	/**
	 * Removes the child node from the list of children, and returns it.
	 *
	 * \param $old_child
	 *   The node being removed.
	 *
	 * \return
	 *   The node removed.
	 *
	 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#ID-1734834066
	 */
	public function &remove_child(&$old_child)
	{
		assert('!$this->must_be_empty; // cannot remove content from empty nodes');
		assert('$old_child instanceof AnewtXMLDomNode;');

		/* Document fragments cannot be removed, since upon adding all their
		 * children are added instead. */
		 if ($old_child instanceof AnewtXMLDomDocumentFragment)
		 {
			 $out = null;
			 return $out;
		 }

		$found = false;
		foreach (array_keys($this->child_nodes) as $key)
		{
			if ($this->child_nodes[$key]->is_same_node($old_child))
			{
				unset($this->child_nodes[$key]);
				unset($old_child->parent_node);
				$old_child->parent_node = null;
				$found = true;
				break;
			}
		}

		if (!$found)
			trigger_error(sprintf(
						'Node #%s is not a child node of #%s',
						$old_child->__object_id,
						$this->__object_id), E_USER_WARNING);

		return $old_child;
	}

	/**
	 * Replaces a child node with a new child, and returns the old child node.
	 *
	 * \todo This method is not implemented yet.
	 *
	 * \param $new_child
	 *   The new node to put in the child list.
	 * \param $old_child
	 *   The node being replaced in the list.
	 *
	 * \return
	 *   The node replaced.
	 *
	 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#ID-785887307
	 */
	public function &replace_child(&$new_child, &$old_child)
	{
		assert('$new_child instanceof AnewtXMLDomNode;');
		assert('$old_child instanceof AnewtXMLDomNode;');

		/* TODO: implement */
		trigger_error('Not implemented', E_USER_ERROR);
	}

	/**
	 * Inserts the node $new_child before the existing child node $ref_child.
	 *
	 * If $ref_child is null, insert $new_child at the end of the list of children.
	 *
	 * \todo This method is not implemented yet.
	 *
	 * \param $new_child
	 *   The node to insert.
	 * \param $ref_child
	 *   The reference node, i.e., the node before which the new node must be inserted.
	 *
	 * \return
	 *   The node being inserted.
	 *
	 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#ID-952280727
	 */
	public function &insert_before(&$new_child, &$ref_child)
	{
		assert('$new_child instanceof AnewtXMLDomNode;');
		assert('$ref_child instanceof AnewtXMLDomNode;');

		/* TODO: implement */
		trigger_error('Not implemented', E_USER_ERROR);
	}

	/**
	 * Returns whether this node has any children.
	 *
	 * \return
	 *   Returns true if this node has any children, false otherwise.
	 *
	 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#ID-810594187
	 */
	public function has_child_nodes()
	{
		return (bool) $this->child_nodes;
	}
}


/* Document classes */

/**
 * Document DOM node.
 *
 * The Document interface represents the entire HTML or XML document.
 * Conceptually, it is the root of the document tree, and provides the primary
 * access to the document's data.
 *
 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#i-Document
 */
class AnewtXMLDomDocument extends AnewtXMLDomNode
{
	public $node_type = ANEWT_XML_DOM_DOCUMENT_NODE;
	public $node_name = '#document';

	/**
	 * The character encoding for this document.
	 *
	 * \see get_encoding
	 * \see set_encoding
	 */
	private $_xml_encoding = 'UTF-8';

	/**
	 * Whether this document is specified to be standalone.
	 *
	 * \see get_standalone
	 * \see set_standalone
	 */
	private $_xml_standalone = null;

	/**
	 * The XML version for this document. This is always the literal value
	 * <code>1.0</code>.
	 */
	private $_xml_version = '1.0';

	/**
	 * Reference to the document element.
	 */
	public $document_element = null;

	/**
	 * The content type for this document. Defaults to text/xml.
	 *
	 * \see get_content_type
	 * \see set_content_type
	 */
	private $_content_type = 'text/xml';


	/**
	 * The document type declaration. Null by default.
	 */
	private $_doctype = null;

	/**
	 * Object id counter. Only for internal use.
	 */
	private $__object_id_counter = 0;


	/**
	 * Whether to render the XML declaration. This is detected automatically by
	 * default.
	 */
	protected $render_xml_declaration = null;


	/** \{
	 * \name Constructor method
	 */

	/**
	 * Create a new DOM document instance.
	 */
	public function __construct()
	{
		/* Do nothing */
	}

	/** \} */


	/** \{
	 * \name Property methods
	 *
	 * These methods can used to get or set several properties of the document.
	 */

	/**
	 * Get the document type.
	 *
	 * \return
	 *   The document type as string, or \c null if none is set.
	 *
	 * \see set_document_type
	 */
	public function get_document_type()
	{
		return $this->_doctype;
	}

	/**
	 * Set the document type.
	 *
	 * \param $doctype
	 *   The document type as string, or \c null to unset.
	 *
	 * \see get_document_type
	 */
	public function set_document_type($doctype)
	{
		assert('is_null($doctype) || is_string($doctype)');
		$this->_doctype = $doctype;
	}

	/**
	 * Get the content type. The default value is UTF-8.
	 *
	 * \return
	 *   The content type as string.
	 *
	 * \see set_content_type
	 */
	public function get_content_type()
	{
		return $this->_content_type;
	}

	/**
	 * Set the content type.
	 *
	 * \param $content_type
	 *   The content type as string.
	 *
	 * \see get_content_type
	 */
	public function set_content_type($content_type)
	{
		assert('is_string($content_type)');
		$this->_content_type = $content_type;
	}

	/**
	 * Get the document encoding.
	 *
	 * \return
	 *   Encoding as string, or \c null if none is set.
	 *
	 * \see set_encoding
	 */
	public function get_encoding()
	{
		return $this->_xml_encoding;
	}

	/**
	 * Set the document encoding.
	 *
	 * \param $encoding
	 *   The encoding as string, or \c null to unset.
	 *
	 * \see get_encoding
	 */
	public function set_encoding($encoding)
	{
		assert('is_null($encoding) || is_string($encoding)');
		$this->_xml_encoding = $encoding;
	}

	/**
	 * Get whether this document is standalone. This is a boolean value
	 * defaulting to null, which means no \c standalone attribute is added to
	 * the XML prolog.
	 *
	 * \return
	 *   Boolean or \c null if not set.
	 *
	 * \see set_standalone
	 */
	public function get_standalone()
	{
		return $this->_xml_standalone;
	}

	/**
	 * Set whether this document is standalone.
	 *
	 * \param $standalone
	 *   Boolean or \c null to unset.
	 *
	 * \see get_standalone
	 */
	public function set_standalone($standalone)
	{
		assert('is_null($standalone) || is_bool($standalone)');
		$this->_xml_standalone = $standalone;
	}

	/** \{
	 * \name Node methods
	 *
	 * These methods can be used to add new nodes to this document, or to create
	 * new nodes for this document. Note that you can also construct
	 * AnewtXMLDomNode subclasses directly.
	 */

	/**
	 * Append a child element to this document.
	 *
	 * Documents can have only one child node (the document's root node), so
	 * this method can be called only once.
	 *
	 * This method verrides the AnewtXMLDomNode::append_child() method to make
	 * sure there is only one top level element.
	 *
	 * \param $root_element
	 *   The root element to add.
	 */
	public function append_child($root_element)
	{
		/* FIXME: this should not fail for things like comments */
		/* FIXME: make this use AnewtXMLDomNode::append_child internally and add
		 * checks for a single root node */
		assert('$root_element instanceof AnewtXMLDomElement;');
		assert('is_null($this->document_element)');
		$this->document_element = &$root_element;
		$root_element->parent_node = &$this;
		return $root_element;
	}

	/**
	 * Create a new element for this document.
	 *
	 * You should add the returned instance to the document yourself.
	 *
	 * \param $tagname
	 *   The tag name for this element
	 *
	 * \param $attributes
	 *   Associative array with initial attribute values for this element
	 *   (optional). This parameter is for convenience only; you can always set
	 *   attributes later.
	 *
	 * \return
	 *   New AnewtXMLDomElement instance.
	 */
	public function &create_element($tagname, $attributes=null)
	{
		$node = new AnewtXMLDomElement($tagname, $attributes);
		$node->__object_id = $this->__object_id_counter++;
		$node->owner_document = &$this;
		return $node;
	}

	/**
	 * Create a text node for the given data.
	 *
	 * You should add the returned instance to the document yourself.
	 *
	 * \param $data
	 *   The textual data for this node.
	 *
	 * \return
	 *   New AnewtXMLDomText instance.
	 */
	public function &create_text_node($data)
	{
		$node = new AnewtXMLDomText($data);
		$node->__object_id = $this->__object_id_counter++;
		$node->owner_document = &$this;
		return $node;
	}

	/**
	 * Create a raw text node for the given data.
	 *
	 * This is useful to add XML snippets generated by other means to a DOM
	 * tree.
	 *
	 * You should add the returned instance to the document yourself.
	 *
	 * \param $data
	 *   The textual data for this node.
	 *
	 * \return
	 *   New AnewtXMLDomRaw instance.
	 */
	public function &create_raw_node($data)
	{
		$node = new AnewtXMLDomRaw($data);
		$node->__object_id = $this->__object_id_counter++;
		$node->owner_document = &$this;
		return $node;
	}

	/**
	 * Create a comment node for the given data.
	 *
	 * You should add the returned instance to the document yourself.
	 *
	 * \param $data
	 *   The comment text.
	 *
	 * \return
	 *   New AnewtXMLDomComment instance.
	 */
	public function &create_comment($data)
	{
		$node = new AnewtXMLDomComment($data);
		$node->__object_id = $this->__object_id_counter++;
		$node->owner_document = &$this;
		return $node;
	}

	/**
	 * Create a document fragment node.
	 *
	 * You should add the returned instance to the document yourself.
	 *
	 * \return
	 *   New AnewtXMLDomDocumentFragment instance.
	 */
	public function &create_document_fragment()
	{
		$node = new AnewtXMLDomDocumentFragment();
		$node->__object_id = $this->__object_id_counter++;
		$node->owner_document = &$this;
		return $node;
	}

	/** \} */

	/** \{
	 * \name Rendering methods
	 */

	/**
	 * Render this document to a string.
	 *
	 * \return
	 *   Rendered string with XML data.
	 */
	public function render()
	{
		$out = array();

		if (is_null($this->render_xml_declaration))
		{
			/* Do not render the XML declaration for text/html content types
			 * since that confuses some web browsers (notably MSIE). */
			$render_xml_declaration = ($this->_content_type !== 'text/html');
		} else
			$render_xml_declaration = $this->render_xml_declaration;

		if ($render_xml_declaration)
			$out[] = $this->xml_declaration();

		if (!is_null($this->_doctype))
		{
			$out[] = $this->_doctype;
			$out[] = NL;
		}

		/* Document might be empty */
		if ($this->document_element)
			$out[] = $this->document_element->render();

		return join('', $out);
	}

	/**
	 * Flush output to the browser.
	 * 
	 * This serializes the document into a string and sends it to the browser
	 * with the specified content type.
	 *
	 * \see flush_to_file
	 */
	public function flush()
	{
		$content_type_header = sprintf('Content-type: %s', $this->_content_type);

		if (!is_null($this->_xml_encoding))
			$content_type_header .= sprintf(';charset=%s', $this->_xml_encoding);

		header($content_type_header);
		echo $this->render(), NL;
	}

	/**
	 * Flush output to a file.
	 *
	 * This method renders the document and outputs it to the specified file.
	 *
	 * \param $filename
	 *   The filename to write the output to.
	 *
	 * \see flush
	 */
	public function flush_to_file($filename)
	{
		assert('is_string($filename)');

		$fp = fopen($filename, 'w');
		fwrite($fp, $this->render());
		fwrite($fp, NL);
		fclose($fp);
	}


	/** \} */

	/**
	 * Return the XML declaration for this document.
	 *
	 * \return
	 *   XML declaration string.
	 */
	private function xml_declaration()
	{
		/* Version is mandatory, encoding and standalone are not */
		$out = array();
		$out[] = sprintf('<?xml version="%s"', $this->_xml_version);

		if (!is_null($this->_xml_encoding))
		{
			assert('is_string($this->_xml_encoding)');
			$out[] = sprintf(' encoding="%s"', $this->_xml_encoding);
		}

		if (!is_null($this->_xml_standalone))
		{
			assert('is_bool($this->_xml_standalone)');
			$out[] = sprintf(' standalone="%s"', $this->_xml_standalone ? 'yes' : 'no');
		}

		$out[] = '?'; /* Two lines to not break Vim's syntax highlighting */
		$out[] = ">\n";
		return join('', $out);
	}
}


/**
 * Document fragment DOM node.
 *
 * This is a lightweight Document object that can be used to group various nodes
 * together in a subtree. The children of a DocumentFragment node are zero or
 * more nodes representing the tops of any sub-trees defining the structure of
 * the document.
 *
 * \see http://www.w3.org/TR/DOM-Level-3-Core/core.html#ID-B63ED1A3
 */
class AnewtXMLDomDocumentFragment extends AnewtXMLDomNode
{
	public $node_type = ANEWT_XML_DOM_DOCUMENT_FRAGMENT_NODE;
	public $node_name = '#document-fragment';
}



/**
 * Element DOM node.
 *
 * This is a node type that corresponds to an XML element (tag). Note that the
 * attribute API does not strictly match the W3C DOM standard. Instead of using
 * a special attribute class, a simple associative-array based approach is used.
 * This means there is no get_attribute_node method. instead, use methods such
 * as get_attribute instead to retrieve the (string) value of an attribute.
 */
class AnewtXMLDomElement extends AnewtXMLDomNode
{
	public $node_type = ANEWT_XML_DOM_ELEMENT_NODE;
	protected $_attributes = array();

	/**
	 * Whether this element should be rendered as a block (the default). If
	 * false, the element is rendered inline, without indentation and
	 * surrounding newlines.
	 */
	public $render_as_block = true;

	/**
	 * Whether the closing tag for this element should always be rendered in
	 * full instead of using shorthand notation. This is needed for e.g. the
	 * script tag in XHTML because of browser imcompatibilities.
	 */
	public $always_render_closing_tag = false;


	/* Constructor */

	/**
	 * Create a new document element.
	 *
	 * \param $tag_name
	 *   The tag name for this element
	 *
	 * \param $attributes
	 *   Associative array with initial attribute values for this element
	 *   (optional). This parameter is for convenience only; you can always set
	 *   attributes later.
	 *
	 * \see set_attribute
	 * \see set_attributes
	 */
	public function __construct($tag_name, $attributes=null)
	{
		assert('is_string($tag_name);');
		$this->tag_name = $tag_name;
		$this->node_name = &$this->tag_name;
		
		if (!is_null($attributes))
			$this->set_attributes($attributes);
	}


	/** \{
	 * \name Attribute methods
	 */

	/**
	 * Return whether this element has attributes
	 *
	 * \return
	 *   True if the element has attributes; false otherwise.
	 */
	public function has_attributes()
	{
		return (bool) $this->_attributes;
	}

	/**
	 * Check whether this element has the given attribute.
	 *
	 * \param $name
	 *   The attribute name to test.
	 *
	 * \return
	 *   True if the element has the given attribute; false otherwise.
	 */
	public function has_attribute($name)
	{
		assert('is_string($name);');

		return array_key_exists($name, $this->_attributes);
	}

	/**
	 * Get value for the given attribute.
	 *
	 * The attribute must be set for this method to succeed.
	 *
	 * \param $name
	 *   The name of attribute to retrieve the value for.
	 *
	 * \return
	 *   The value of the given attribute.
	 *
	 * \see get_attributes
	 * \see set_attribute
	 */
	public function get_attribute($name)
	{
		assert('is_string($name);');
		assert('$this->has_attribute($name);');

		return $this->_attributes[$name];
	}

	/**
	 * Set value for the given attribute.
	 *
	 * \param $name
	 *   The name of attribute for which to set the value.
	 *
	 * \param $value
	 *   The value of the attribute.
	 *
	 * \see get_attribute
	 * \see set_attributes
	 */
	public function set_attribute($name, $value)
	{
		assert('is_string($name);');
		assert('is_null($value) || is_string($value);');
		assert('!$this->allowed_attributes || in_array($name, $this->allowed_attributes); // invalid attribute set');

		if (is_null($value))
			$this->remove_attribute($name);
		else
			$this->_attributes[$name] = $value;
	}

	/**
	 * Get all attribute names and values.
	 *
	 * \return
	 *   Associative array with all attribute names and values.
	 *
	 * \see get_attribute
	 * \see set_attributes
	 */
	public function get_attributes()
	{
		return $this->_attributes;
	}

	/**
	 * Set multiple attributes at once.
	 *
	 * This is a convenience method.
	 *
	 * \param $attributes
	 *   Associative array of attributes.
	 *
	 * \see set_attribute
	 * \see get_attributes
	 */
	public function set_attributes($attributes)
	{
		assert('is_assoc_array($attributes);');
		foreach ($attributes as $name => $value)
			$this->set_attribute($name, $value);
	}

	/**
	 * Remove the given attribute from this element.
	 *
	 * This function does nothing if the attribute was not set.
	 *
	 * \param $name
	 *   The name of attribute to remove.
	 */
	public function remove_attribute($name)
	{
		assert('is_string($name);');

		if (array_key_exists($name, $this->_attributes))
			unset($this->_attributes[$name]);
	}

	/** \} */

	/** \{
	 * \name Node methods
	 */

	/**
	 * Append a text node.
	 *
	 * \param $text
	 *   The text to append.
	 *
	 * \see AnewtXMLDomNode::append_child
	 * \see AnewtXMLDomElement::append_child_raw
	 */
	function append_child_text($text)
	{
		$this->append_child(new AnewtXMLDomText($text));
	}

	/**
	 * Append a raw text node.
	 *
	 * \param $data
	 *   The raw text to append.
	 *
	 * \see AnewtXMLDomNode::append_child
	 * \see AnewtXMLDomElement::append_child_raw
	 */
	function append_child_raw($data)
	{
		$this->append_child(new AnewtXMLDomRaw($data));
	}

	/** \} */

	/** \{
	 * \name Rendering methods
	 */

	/**
	 * Build an attribute string for this element.
	 *
	 * \return
	 *   String containing <code>name=value</code> pairs.
	 */
	private function _build_attribute_string()
	{
		$out = array();
		foreach ($this->_attributes as $name => $value)
		{
			/* Both $name and $value are known to be strings, since
			 * set_attribute make sure $this->_attributes stays clean. */
			 
			$out[] = sprintf(
				' %s="%s"',
				htmlspecialchars($name),
				htmlspecialchars($value)
				);
		}
		return join('', $out);
	}

	/**
	 * Render this element to a string.
	 *
	 * \param $indent_level
	 *   The indentation level to use for block level elements. Don't specify
	 *   this attribute when calling this method, it's useful for internal
	 *   purposes only.
	 *
	 * \return
	 *   Rendered string with XML data.
	 */
	public function render($indent_level=0)
	{
		/* TODO:
		 * - Allow custom indentation string, e.g. two spaces instead of a tab.
		 */

		$out = array();

		/* Indent if needed (for block display). Don't do this for the top level
		 * element and unattached elements without parent nodes (document
		 * fragments included). */
		if ($this->render_as_block
				&& !is_null($this->parent_node)
				&& !($this->parent_node instanceof AnewtXMLDomDocumentFragment)
				&& !($this->parent_node instanceof AnewtXMLDomDocument))
		{
			$out[] = NL;
			$out[] = str_repeat("\t", $indent_level);
		}

		/* Open the start tag, but don't close it (depends on children) */

		$out[] = '<';
		$out[] = $this->node_name;
		if ($this->has_attributes())
			$out[] = $this->_build_attribute_string();


		if ($this->has_child_nodes())
		{
			/* Close the opening tag. */

			$out[] = '>';


			/* If one of the children of this node is an element that should be
			 * rendered as a block (not inline), this element is considered to
			 * contain more than inline content only. This is used to decide
			 * whether the child nodes should be rendered with or without
			 * indents and newlines. */

			$node_has_inline_content_only = true;
			foreach (array_keys($this->child_nodes) as $child_node_idx)
			{
				if ($this->child_nodes[$child_node_idx] instanceof AnewtXMLDomElement
						&& $this->child_nodes[$child_node_idx]->render_as_block)
				{
					$node_has_inline_content_only = false;
					break;
				}
			}

			if ($node_has_inline_content_only)
			{
				/* This element has only inline child nodes (text-like and
				 * element children that should be rendered inline). Concatenate
				 * the values without indentation and without newlines. */

				foreach ($this->child_nodes as $child_node)
					$out[] = $child_node->render(0);

			}
			else
			{
				/* This element has at least one element child node that should
				 * be rendered as a block. Render all child nodes with
				 * indentation and newlines to create pretty output. */

				foreach ($this->child_nodes as $child_node)
					$out[] = $child_node->render($indent_level + 1);


				/* If this element was rendered as a block, newline and
				 * indentation is added to properly align the closing tag with
				 * the opening tag. */

				if ($this->render_as_block)
				{
					$out[] = NL;
					$out[] = str_repeat("\t", $indent_level);
				}
			}


			/* Write the closing tag */

			$out[] = '</';
			$out[] = $this->node_name;
			$out[] = '>';
		}
		else
		{
			/* If the element has no child nodes, rendering is pretty simple.
			 * Close the just-opened tag and be done with it. */

			if ($this->always_render_closing_tag)
			{
				$out[] = '></';
				$out[] = $this->node_name;
				$out[] = '>';
			} else {
				$out[] = ' />';
			}
		}

		/* Final output */
		return join('', $out);
	}
}


/**
 * Text DOM node.
 */
class AnewtXMLDomText extends AnewtXMLDomNode
{
	public $node_type = ANEWT_XML_DOM_TEXT_NODE;
	public $node_name = '#text';

	/**
	 * The value of this node.
	 */
	public $node_value;

	/**
	 * Create a new AnewtXMLDomText instance.
	 *
	 * \param $data
	 *   The textual data for this node.
	 */
	public function __construct($data)
	{
		$this->node_value = $data;
	}

	/**
	 * Render this node to a string.
	 *
	 * \return
	 *   Rendered string with XML data.
	 */
	public function render()
	{
		$node_value_escaped = htmlspecialchars(to_string($this->node_value));
		return $node_value_escaped;
	}
}


/**
 * Comment DOM node.
 */
class AnewtXMLDomComment extends AnewtXMLDomNode
{
	public $node_type = ANEWT_XML_DOM_COMMENT_NODE;
	public $node_name = '#comment';

	/**
	 * Create a new AnewtXMLDomComment instance.
	 *
	 * \param $data
	 *   The text for this comment.
	 */
	public function __construct($data)
	{
		assert('is_string($data);');
		$this->node_value = $data;
	}

	/**
	 * Render comment into HTML.
	 *
	 * This escapes invalid content so that the result is always a valid XHTML
	 * snippet.
	 */
	public function render()
	{
		/* Don't allow -- in comment text */
		$value = to_string($this->node_value);
		assert('is_string($value);');
		$node_value_escaped = preg_replace('/-(?=-)/', '- ', $value);

		$out = sprintf('<!-- %s -->', $node_value_escaped);
		return $out;
	}
}


/**
 * Raw text DOM node.
 *
 * This is an Anewt-specific extension class that allows one to use strings
 * containing XML snippets (that are assumed to be well-formed) as nodes in
 * a DOM tree. Example usage: preformatted XHTML stuff, or snippets of XML
 * generated by other code (e.g. by text-to-markup convertors).
 */
class AnewtXMLDomRaw extends AnewtXMLDomNode
{
	public $node_type = ANEWT_XML_DOM_RAW_NODE;
	public $node_name = '#raw';

	/**
	 * Construct a new AnewtXMLDomRaw node.
	 *
	 * Don't call this method directly, use Document::create_raw_node instead.
	 *
	 * \param $data
	 *   The data for this node
	 */
	public function __construct($data)
	{
		$this->node_value = $data;
	}

	/**
	 * Render this node to a string.
	 *
	 * \return
	 *   Rendered string with XML data.
	 */
	public function render()
	{
		$node_value_escaped = to_string($this->node_value);
		assert('is_string($node_value_escaped);');
		return $node_value_escaped;
	}
}

?>
