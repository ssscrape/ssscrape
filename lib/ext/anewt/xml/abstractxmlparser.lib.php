<?php

/*
 * Anewt, Almost No Effort Web Toolkit, XML module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Implements a simple XML parser base class that can be overridden to create an
 * XML parser.
 *
 * You should extend this class and add methods of the form start_foo($attr) and
 * end_foo(), where foo is the tagname you want to match.
 *
 * You can also add methods of the form handle_foo_data($data) to handle
 * character data that appears in the tag named foo. This can be used to easily
 * extract data from simple XML documents.
 *
 * If the parser encounters a tag that does not have corresponding start_foo and
 * end_foo methods, unknown_starttag or unknown_endtag are called.
 *
 * \todo
 *   Character/entity references (maybe support all HTML entities by default?)
 */
abstract class AbstractXMLParser
{
	private $parser;        /**< \private The xml_parser instance */
	private $finalized;     /**< \private Boolean indicating the parser state */
	private $cdata_buffer;  /**< \private Character data buffer */
	private $tags;          /**< \private Stack used to track tag nesting */

	/**
	 * Initializes the parser.
	 *
	 * The constructor method instantiates and configures the underlying XML
	 * parser and its handlers.
	 *
	 * \param $encoding The character encoding to use for this parser. This
	 * parameter is optional (defaults to null) and can be safely omitted. See
	 * the PHP manual for xml_parser_create for more information about
	 * encodings.
	 */
	public function __construct($encoding=null)
	{
		$this->finalized = false;
		$this->cdata_buffer = array();
		$this->tags = array();

		/* Create the parser instance */
		if (is_null($encoding)) {
			$this->parser = xml_parser_create();
		} else {
			assert('is_string($encoding)');
			$this->parser = xml_parser_create($encoding);
		}

		/* Set some options */
		xml_parser_set_option( $this->parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parser_set_option( $this->parser, XML_OPTION_CASE_FOLDING, false);

		/* Always use UTF-8 */
		xml_parser_set_option( $this->parser, XML_OPTION_TARGET_ENCODING,
				'UTF-8');

		/* Setup the handlers */
		xml_set_element_handler(
			$this->parser,
			array(&$this, 'start_element_handler'),
			array(&$this, 'end_element_handler')
			);

		xml_set_character_data_handler(
			$this->parser,
			array(&$this, '_handle_data')
			);

		xml_set_processing_instruction_handler(
			$this->parser,
			array(&$this, 'handle_pi')
			);

		xml_set_default_handler(
			$this->parser,
			array(&$this, '_default_handler')
			);
	}

	/**
	 * Passes some data to the parser.
	 *
	 * The parser will process it and invoke the appropriate handlers and
	 * callback methods.
	 *
	 * \param $data A string containing some data
	 *
	 * \see feed_file
	 */
	public function feed($data)
	{
		assert('is_string($data)');

		/* No feeding after finalizing */
		assert('$this->finalized === false');

		/* Feed the data to the parser */
		$result = xml_parse($this->parser, $data);

		/* Check for errors */
		if (!$result)
			$this->_handle_error();
	}

	/**
	 * Passes the contents of a complete file to the parser.
	 *
	 * The parser will process it and invoke the appropriate handlers and
	 * callback methods.
	 *
	 * \param $filename A string pointing to an XML file
	 *
	 * \see feed
	 */
	public function feed_file($filename)
	{
		assert('is_string($filename);');

		$fp = fopen($filename, 'r');

		if ($fp === false)
			trigger_error(sprintf(
				'AbstractXMLParser::feed_file(): failed to open file \'%s\'',
				$filename), E_USER_ERROR);

		while (!feof($fp))
			$this->feed(fread($fp, 8192));

		fclose($fp);
	}

	/**
	 * Tells the parser that there's no more input data.
	 *
	 * If you override this method, make sure you call this one too by using
	 * parent::close();
	 */
	public function close()
	{
		assert('$this->finalized === false');

		/* Tell the parser we're done */
		$this->feed('', true);

		/* Finalize and free the parser instance */
		$this->finalized = true;
		xml_parser_free($this->parser);
	}

	/* Handlers */

	/**
	 * Callback method for the XML parser.
	 *
	 * \param $parser The parser instance
	 * \param $name The name of the tag
	 * \param $attr The attributes for this tag
	 */
	private function start_element_handler($parser, $name, $attr)
	{
		assert('is_string($name)');
		assert('is_assoc_array($attr)');

		/* First flush the cdata buffer */
		$this->flush_data();

		/* Store tag name at the top of the tag stack */
		$this->tags[] = $name;

		/* Call start_$name() if the method is available */
		$method_name = sprintf('start_%s', $name);
		$method_name = str_replace('-', '_', $method_name);
		if (method_exists($this, $method_name))
		{
			$this->$method_name($attr);
			return;
		}

		/* No handler found for this tag */
		$this->unknown_starttag($name, $attr);
	}

	/**
	 * Callback method for the XML parser.
	 *
	 * \param $parser The parser instance
	 * \param $name The name of the tag
	 */
	private function end_element_handler($parser, $name)
	{
		assert('is_string($name)');

		/* First flush the cdata buffer */
		$this->flush_data();

		/* Remove tag name from stack */
		array_pop($this->tags);

		/* Call end_$name() if the method is available */
		$method_name = sprintf('end_%s', $name);
		$method_name = str_replace('-', '_', $method_name);
		if (method_exists($this, $method_name))
		{
			$this->$method_name();
			return;
		}

		/* No handler found for this tag */
		$this->unknown_endtag($name);
	}

	/**
	 * Called when an unhandled start tag is found.
	 *
	 * Override this method if you want your parser to have a default start tag
	 * handler.
	 *
	 * \param $name The name of the tag
	 * \param $attr The attributes for this tag
	 */
	function unknown_starttag($name, $attr)
	{
		/* Do nothing */
	}

	/**
	 * Called when an unhandled end tag is found.
	 *
	 * Override this method if you want your parser to have a default end tag
	 * handler.
	 *
	 * \param $name The name of the tag
	 */
	function unknown_endtag($name)
	{
		/* Do nothing */
	}

	/**
	 * Called when text data from the XML document is encountered.
	 *
	 * This method stores the data in an internal buffer, which is flushed when
	 * a tag ends.
	 *
	 * \param $parser The parser instance
	 * \param $data A string containing text data
	 */
	private function _handle_data($parser, $data)
	{
		/* Store the data in a buffer */
		if (strlen(trim($data)) > 0) /* Skip whitespace only */
			$this->cdata_buffer[] = $data;
	}

	/**
	 * Tells the parser to flush all buffered data.
	 *
	 * This method is called when a tag ends to ensure data is processed in the
	 * correct order.
	 */
	private function flush_data()
	{
		if (count($this->cdata_buffer) > 0)
		{
			/* There's data in the buffer */
			$data = implode('', $this->cdata_buffer);
			$this->cdata_buffer = array();
			$data = preg_replace('/\s+/', ' ', $data);

			/* Check for handle_$tag_data */
			$num_tags = count($this->tags);
			if ($num_tags > 0)
			{
				$tag_name = $this->tags[$num_tags - 1];
				$method_name = sprintf('handle_%s_data', $tag_name);
				$method_name = str_replace('-', '_', $method_name);
				if (method_exists($this, $method_name))
				{
					$this->$method_name($data);
					return;
				}
			}

			/* Fallback to general data handling method */
			$this->handle_data($data);
		}
	}

	/**
	 * Handle character data.
	 *
	 * This method can be overridden to do something sensible with character
	 * data from the input data.
	 *
	 * \param $data The preprocessed data (with whitespace collapsed)
	 */
	protected function handle_data($data)
	{
		/* Do nothing */
	}

	/**
	 * Callback method for the XML parser.
	 *
	 * \param $parser The parser instance
	 * \param $name The name of the processing instruction
	 * \param $data A string containing text data
	 */
	private function handle_pi($parser, $name, $data)
	{
		/* First flush the cdata buffer */
		$this->flush_data();

		/* Call process_$name() if the method is available */
		$method_name = sprintf('process_%s', $name);
		$method_name = str_replace('-', '_', $method_name);
		if (method_exists($this, $method_name))
		{
			$this->$method_name($data);
			return;
		}

		/* No handler found for this processing instruction */
		$this->unknown_pi($name, $data);
	}

	/**
	 * Called when an unhandled processing instruction is found.
	 *
	 * Override this method if you want your parser to have a default processing
	 * instruction handler.
	 *
	 * \param $name The name of the processing instruction
	 * \param $data A string containing text data
	 */
	protected function unknown_pi($name, $data)
	{
		/* Do nothing */
	}

	/**
	 * Called when comments are found in the input data.
	 *
	 * \param $data The comment data (comment markers are already trimmed)
	 */
	protected function handle_comment($data)
	{
		/* Do nothing */
	}

	/**
	 * Internal default handler preprocessor that looks for XML comments.
	 *
	 * \param $parser The parser instance
	 * \param $data A string containing text data
	 */
	private function _default_handler($parser, $data)
	{
		/* First flush the cdata buffer */
		$this->flush_data();

		/* Check if the data is comment */
		$comment_start = '<!--';
		$comment_end = '-->';
		if (str_has_prefix($data, $comment_start) &&
				str_has_suffix($data, $comment_end))
		{
			$comment = substr($data, strlen($comment_start));
			$comment = substr($comment, 0, strlen($comment) - strlen($comment_end));

			/* strip whitespace */
			$comment = trim($comment);
			$comment = preg_replace('/\s+/', ' ', $comment);

			/* hand over control to the command handler */
			$this->handle_comment($comment);
			return;
		}

		/* hand over control to the default handler */
		$this->default_handler($data);
	}

	/**
	 * Default handler.
	 *
	 * Override this method if you want to specify a default handler.
	 *
	 * \param $data A string containing text data
	 */
	protected function default_handler($data)
	{
		/* Do nothing */
	}


	/* Error handling */

	/**
	 * Internal error handler. Collects error info and dispatches to the real
	 * error handler method.
	 */
	private function _handle_error()
	{
		/* Flush data first */
		$this->flush_data();

		/* Get error data and dispatch */
		$errno = xml_get_error_code($this->parser);
		$this->handle_error(
			$errno,
			xml_error_string($errno),
			xml_get_current_line_number($this->parser),
			xml_get_current_column_number($this->parser),
			xml_get_current_byte_index($this->parser)
			);

		/* Always stop on error */
		$this->finalized = true;
	}

	/**
	 * Default error handler. Just throws a fatal error. Override this method
	 * if you want to do less intrusive things.
	 *
	 * \param $errno Error number
	 * \param $errstr Error string
	 * \param $line Line where the error occurred
	 * \param $col Column where the error occurred
	 * \param $byte Byte offset where the error occurred
	 */
	protected function handle_error($errno, $errstr, $line, $col, $byte)
	{
		$message = sprintf('%s::%s: Parse error %d (%s) while parsing input at
				line %d, col %d (byte offset: %d)', __CLASS__, __FUNCTION__,
				$errno, $errstr, $line, $col, $byte);
		trigger_error($message, E_USER_ERROR);
	}


	/**
	 * Return the current parent tag name. An optional parameter can be
	 * specified to specify additional steps up the tree.
	 *
	 * \param $number_of_levels_up
	 *   Controls how many levels up the tree (default is 1)
	 *
	 * \return
	 *   Tag name or null
	 */
	protected function parent_tag($number_of_levels_up=1)
	{
		assert('is_int($number_of_levels_up)');
		assert('$number_of_levels_up >= 0');

		$tag = null;

		if ((count($this->tags) -1) >= $number_of_levels_up)
			$tag = $this->tags[count($this->tags) - $number_of_levels_up - 1];

		return $tag;
	}

	/**
	 * Return the current tag name. This can be useful when handling cdata.
	 *
	 * \return
	 *   Tag name or null
	 */
	protected function current_tag()
	{
		return $this->parent_tag(0);
	}

}

?>
