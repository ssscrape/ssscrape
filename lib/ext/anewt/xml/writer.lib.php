<?php

/*
 * Anewt, Almost No Effort Web Toolkit, XML module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


anewt_include('xml/dom');


/**
 * Procedural-style class to write out XML documents.
 *
 * This class can be used to write out XML documents with a simple procedural
 * interface, allowing you to build output documents in a linear way. The basic
 * usage pattern is:
 *
 * - Create a new AnewtXMLWriter instance
 * - Set some properties on the AnewtXMLWriter instance
 * - Start the document using AnewtXMLWriter::write_start_document()
 * - Write out the elements, attributes and textual content using the various
 *   methods provided for that, e.g.AnewtXMLWriter::write_start_element() and
 *   AnewtXMLWriter::write_attribute
 * - End the document using AnewtXMLWriter::write_start_document()
 * - Render or flush the output using AnewtXMLWriter::render() or
 *   AnewtXMLWriter::flush()
 *
 * Note that this class operates completely using in-memory DOM tree
 * representations. It is not suitable to write very large XML documents to
 * disk, but for relatively small XML documents it should be good enough.
 */
class AnewtXMLWriter
{
	/**
	 * The document used in this AnewtXMLWriter instance.
	 *
	 * \see get_document
	 */
	private $_document = null;

	/**
	 * Reference to the current element.
	 *
	 * This points to the variable to which subsequent calls to the write
	 * methods will add child nodes, attributes or textual content.
	 */
	private $_current_element = null;


	/**
	 * Create a new XML writer instance.
	 */
	public function __construct()
	{
		$this->_document = new AnewtXMLDomDocument();
	}

	/** \{
	 * \name Document-related methods
	 *
	 * These methods should be used to start and end documents. Calls to
	 * AnewtXMLWriter::write_start_document() and
	 * AnewtXMLWriter::write_end_document() should appear at the start and the
	 * end of your code, respectively.
	 */

	/**
	 * Write the start of the document.
	 */
	public function write_start_document()
	{
		/* Do nothing for now */
	}

	/**
	 * Write the end of the document.
	 *
	 * After calling this method, no further data can be written to this
	 * AnewtXMLWriter instance.
	 */
	public function write_end_document()
	{
		/* Check that all elements were properly closed */
		assert('$this->_current_element->is_same_node($this->_document); // not elements are closed');

		unset ($this->_current_element);
		$this->_current_element = null;
	}

	/**
	 * Set the document type for the generated document.
	 *
	 * \param $doctype
	 *   The document type as string, or \c null to unset.
	 *
	 * \see AnewtXMLDomDocument::set_document_type
	 */
	public function set_document_type($doctype)
	{
		$this->_document->set_document_type($doctype);
	}

	/**
	 * Set the encoding for the generated document.
	 *
	 * \param $encoding
	 *   The encoding as string, or \c null to unset.
	 *
	 * \see AnewtXMLDomDocument::set_encoding
	 */
	public function set_encoding($encoding)
	{
		$this->_document->set_encoding($encoding);
	}

	/**
	 * Set the content type for the generated document.
	 *
	 * \param $content_type
	 *   The content type as string.
	 *
	 * \see AnewtXMLDomDocument::set_content_type
	 */
	public function set_content_type($content_type)
	{
		$this->_document->set_content_type($content_type);
	}

	/**
	 * Set whether the generated document is standalone.
	 *
	 * \param $standalone
	 *   Boolean or \c null to unset.
	 *
	 * \see AnewtXMLDomDocument::set_standalone
	 */
	public function set_standalone($standalone)
	{
		$this->_document->set_standalone($standalone);
	}

	/**
	 * Return a reference to the current document.
	 *
	 * This method can be used to retrieve the generated document. This method
	 * is only supposed to be called after a call to
	 * AnewtXMLWriter::write_end_document(). Usually you don't need this method,
	 * since the AnewtXMLWriter::render() and AnewtXMLWriter::flush() methods
	 * can take care of rendering the document properly.
	 *
	 * \return
	 *   An AnewtXMLDomDocument instance.
	 */
	public function &get_document()
	{
		return $this->_document;
	}

	/** \} */


	/** \{
	 * \name Element methods
	 *
	 * Methods for writing elements to the document.
	 */

	/**
	 * Write the start of an element.
	 *
	 * \param $name
	 *   The tag name for the element.
	 *
	 * \param $attributes
	 *   Associative array with initial attribute values for this element
	 *   (optional). This parameter is for convenience only; you can always set
	 *   attributes later using write_attribute.
	 */
	public function write_start_element($name, $attributes=null)
	{
		assert('!is_null($this->_document); // no document has been started');

		$new_element = &$this->_document->create_element($name, $attributes);

		if (is_null($this->_current_element))
		{
			$this->_document->append_child($new_element);
			$this->_current_element = &$new_element;
		}
		else
		{
			$this->_current_element->append_child($new_element);
			unset ($this->_current_element);
			$this->_current_element = &$new_element;
		}
	}

	/**
	 * Write the end of an element.
	 *
	 * \param $name
	 *   The tag name of the element to end (optional). This value is not
	 *   strictly needed, but if specified it is tested against the currently
	 *   open element, triggering an assertion error if the wrong elements was
	 *   open. This is mainly helpful to make sure your code is correct and you
	 *   don't close elements you did not expect to close.
	 */
	public function write_end_element($name=null)
	{
		assert('!is_null($this->_document); // no document has been started');
		assert('!is_null($this->_current_element); // no element open');
		assert('!($this->_current_element instanceof AnewtXMLDomDocument); // cannot close document element using write_end_element');
		assert('!is_null($this->_current_element->parent_node); // current element has no parent');

		/* Optional safety checks */
		if (!is_null($name))
		{
			assert('$this->_current_element->node_name == $name; // element name mismatch');
		}
		
		$parent_node = &$this->_current_element->parent_node;
		unset ($this->_current_element);
		$this->_current_element = &$parent_node;
	}

	/**
	 * Write element with textual data.
	 *
	 * This method opens a new element, writes the text and ends the element
	 * again.
	 *
	 * \param $name
	 *   The tag name for the element to write.
	 *
	 * \param $data
	 *   The textual data for this element.
	 *
	 * \param $attributes
	 *   Associative array with initial attribute values for this element
	 *   (optional). This parameter is for convenience only; you can always set
	 *   attributes later using write_attribute.
	 *
	 * \see write_text
	 * \see write_element_raw
	 * \see AnewtXMLDomText
	 */
	public function write_element_text($name, $data, $attributes=null)
	{
		assert('!is_null($this->_document); // no document has been started');
		$this->write_start_element($name, $attributes);
		$this->write_text($data);
		$this->write_end_element();
	}

	/**
	 * Write element with raw data.
	 *
	 * This method opens a new element, writes the raw data and ends the element
	 * again.
	 *
	 * \param $name
	 *   The tag name for the element to write.
	 *
	 * \param $data
	 *   The textual data for this element.
	 *
	 * \param $attributes
	 *   Associative array with initial attribute values for this element
	 *   (optional). This parameter is for convenience only; you can always set
	 *   attributes later using write_attribute.
	 *
	 * \see write_raw
	 * \see write_element_text
	 * \see AnewtXMLDomRaw
	 */
	public function write_element_raw($name, $data, $attributes=null)
	{
		assert('!is_null($this->_document); // no document has been started');
		$this->write_start_element($name, $attributes);
		$this->write_raw($data);
		$this->write_end_element();
	}

	/** \} */


	/** \{
	 * \name Attribute methods
	 *
	 * Methods to write attributes to the currently open element.
	 */

	/**
	 * Write an attribute to the currently open element.
	 *
	 * \param $name
	 *   The name of attribute for which to set the value.
	 *
	 * \param $value
	 *   The value of the attribute.
	 *
	 * \see write_attributes
	 */
	public function write_attribute($name, $value)
	{
		assert('!is_null($this->_document); // no document has been started');
		assert('!is_null($this->_current_element); // no current element');
		assert('$this->_current_element instanceof AnewtXMLDomElement; // no valid element open');
		$this->_current_element->set_attribute($name, $value);
	}

	/**
	 * Write multiple attributes to the currently open element.
	 *
	 * \param $attributes
	 *   Associative array with attribute named and values.
	 *
	 * \see write_attribute
	 */
	public function write_attributes($attributes)
	{
		assert('is_assoc_array($attributes)');
		foreach ($attributes as $name => $value)
			$this->_current_element->set_attribute($name, $value);
	}

	/** \} */


	/** \{
	 * \name Textual data methods
	 *
	 * Methods to write textual data to the document.
	 */

	/**
	 * Write textual to the currently open element.
	 *
	 * \param $data
	 *   The textual data for this element.
	 *
	 * \see write_element_text
	 * \see write_raw
	 */
	public function write_text($data)
	{
		assert('!is_null($this->_document); // no document has been started');
		$this->_current_element->append_child($this->_document->create_text_node($data));
	}

	/**
	 * Write raw data to the currently open element.
	 *
	 * \param $data
	 *   The raw data for this element.
	 *
	 * \see write_element_raw
	 * \see write_text
	 */
	public function write_raw($data)
	{
		assert('!is_null($this->_document); // no document has been started');
		$this->_current_element->append_child($this->_document->create_raw_node($data));
	}


	/**
	 * \{
	 * \name Miscellaneous writer methods
	 *
	 * Various methods to write data to the document.
	 */

	/**
	 * Write a comment node to the document.
	 *
	 * \param $data
	 *   The comment text to write.
	 */
	public function write_comment($data)
	{
		assert('!is_null($this->_document); // no document has been started');
		$this->_current_element->append_child($this->_document->create_comment($data));
	}


	/**
	 * \{
	 * \name Output methods
	 *
	 * Methods to produce output.
	 */

	/**
	 * Render this document to a string.
	 *
	 * \return
	 *   Rendered string with XML data.
	 *
	 * \see flush
	 * \see AnewtXMLDomDocument::render
	 */
	public function render()
	{
		assert('!is_null($this->_document); // no document has been started');
		assert('is_null($this->_current_element); // still elements open');

		$out = $this->_document->render();
		return $out;
	}

	/**
	 * Flush output to the browser.
	 *
	 * This method renders the document and outputs it to the browser with the
	 * correct headers.
	 *
	 * \see set_content_type
	 * \see render
	 * \see flush_to_file
	 * \see AnewtXMLDomDocument::flush
	 */
	public function flush()
	{
		assert('!is_null($this->_document); // no document has been started');
		assert('is_null($this->_current_element); // still elements open');

		$this->_document->flush();
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
		assert('!is_null($this->_document); // no document has been started');
		assert('is_null($this->_current_element); // still elements open');
		assert('is_string($filename)');

		$this->_document->flush_to_file($filename);
	}
}

?>
