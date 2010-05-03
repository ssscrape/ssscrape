<?php

/*
 * Anewt, Almost No Effort Web Toolkit, textile module
 * Copyright (C) 2005  Wouter Bolsterlee <uws@xs4all.nl>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA+
 */


anewt_include('xhtml');

/* Some constants to ease coding */
mkenum(
	'BOLD',          'BOLD_OPEN',          'BOLD_CLOSE',
	'ITALIC',        'ITALIC_OPEN',        'ITALIC_CLOSE',
	'STRONG',        'STRONG_OPEN',        'STRONG_CLOSE',
	'EMPH',          'EMPH_OPEN',          'EMPH_CLOSE',
	'SUPERSCRIPT',   'SUPERSCRIPT_OPEN',   'SUPERSCRIPT_CLOSE',
	'SUBSCRIPT',     'SUBSCRIPT_OPEN',     'SUBSCRIPT_CLOSE',
	'STRIKETHROUGH', 'STRIKETHROUGH_OPEN', 'STRIKETHROUGH_CLOSE'
);


/* Regular expressions. A few regular expresion snippets are defined, which can
 * be used later to contruct larger ones. */

/** Start of HTML tokens */
define('ANEWT_TEXTILE_PATTERN_NMSTART', '[_a-zA-Z]');

/** Trailing characters of HTML tokens */
define('ANEWT_TEXTILE_PATTERN_NMCHAR', '[_a-zA-Z0-9-]');

/** HTML identifiers are built using HTML tokens */
define('ANEWT_TEXTILE_PATTERN_IDENT', ANEWT_TEXTILE_PATTERN_NMSTART . ANEWT_TEXTILE_PATTERN_NMCHAR . '*');

/** Class attributes must be valid identifiers */
define('ANEWT_TEXTILE_PATTERN_CLASS', ANEWT_TEXTILE_PATTERN_IDENT);

/** ID attributes must be valid identifiers */
define('ANEWT_TEXTILE_PATTERN_ID', ANEWT_TEXTILE_PATTERN_IDENT);

/** Style declarations end at the first } character */
define('ANEWT_TEXTILE_PATTERN_STYLE', '[^}]+?:[^}]+'); // at least one : character

/** Language definition. E.g. 'nl' or 'nl_NL' */
define('ANEWT_TEXTILE_PATTERN_LANG', '[A-Za-z]{2,4}(_[A-Za-z]{2,4})?');

/** Alignment declarations come from a fixed set. */
define('ANEWT_TEXTILE_PATTERN_ALIGNMENT', '(<|>|=|<>)');

/** Complete block modifier declaration. E.g. the part after h2 up to the real
 * content in
 *
 *   h2(class#id){style}[lang]. This is the text.
 *
 */
define('ANEWT_TEXTILE_PATTERN_MARKUP_MODIFIER', sprintf(
	'(\('                    // start of optional (class#id) part
		. '(?P<class>%s)?'   // the class part (optional)
		. '(#(?P<id>%s))?'   // the id part (optional)
	. '\))?'                 // end of (class#id) part
	. '(\[(?P<lang>%s)?\])?' // language part (optional)
	. '({(?P<style>%s)?})?'  // style part (optional)
	,
	ANEWT_TEXTILE_PATTERN_CLASS,
	ANEWT_TEXTILE_PATTERN_ID,
	ANEWT_TEXTILE_PATTERN_LANG,
	ANEWT_TEXTILE_PATTERN_STYLE)
);



/** Complete block modifier declaration. E.g. something like
 *
 *   h2(class#id){style}[lang]. This is a header.
 *
 */
define('ANEWT_TEXTILE_PATTERN_BLOCK_MODIFIER', sprintf(
	'/^'                         // start of string
	. '(?P<type>p|bq|h[123456])' // the block type
	. ANEWT_TEXTILE_PATTERN_MARKUP_MODIFIER
	. '(?P<align>%s)?'           // alignment specifier (optional)
	. '(?<paddingleft>\(+)?'
	. '(?<paddingright>\)+)?'
	. '\.(?P<continuation>\.)? ' // end of modifier (literal dot and space)
	. '(?P<text>.*)'             // the inline text of the block
	. '$'                        // end of string
	. '/s'                       // s modifier to have the . include newlines as well
	,
	ANEWT_TEXTILE_PATTERN_ALIGNMENT)
);



/**
 * Textile formatting class. This class transforms input text in textile format
 * into valid XHTML strict.
 *
 * The Textile class has several attributes to control the behaviour of the
 * engine.
 *
 * - <code>line-folding</code>: Boolean indicating whether to enable line folding
 * (enabled by default). In line folding mode, you can include newline
 * characters in your input text. These newlines will not be used as line wraps.
 * If line folding is off, every newline in the input text is treated as the
 * start of a new block. This setting is compatible with other Textile
 * implementations.
 *
 * The above attributes can be queried and set using the standard
 * AnewtContainer::get and AnewtContainer::set methods.
 */
class AnewtTextile extends AnewtContainer
{
	/** \private The block modifiers used in block continuation mode, if enabled */
	var $_saved_block_modifiers = array();

	/**
	 * Constructor method. Initialises the default settings.
	 */
	function AnewtTextile()
	{
		$this->_seed(array(
			'line-folding' => true,
		));
	}


	/**
	 * \private
	 *
	 * Normalize text by cleaning up newlines and whitespace.
	 *
	 * \param $text
	 *   The text string to normalize
	 *
	 * \return
	 *   Normalized string
	 */
	function _normalize_text($text) {
		/* Remove leading and trailing whitespace */
		$text = trim($text);

		/* Normalize line endings */
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);

		return $text;
	}

	/**
	 * \private
	 *
	 * Splits text into blocks. The input text is split into blocks that can be
	 * separately formatted. Both line folded and non-line folded
	 * text is handled, as well as lists, tables en pre-formatted code.
	 *
	 * \param $text
	 *   Text to split into blocks
	 *
	 * \return
	 *   An array containing all blocks
	 */
	function _split_into_blocks($text)
	{
		assert('is_string($text);');

		/* Split on multiple successive newlines if line folding is enabled */

		if ($this->_get('line-folding'))
		{
			$blocks = explode("\n\n", $text);
			$blocks = array_map('trim', $blocks);

			return $blocks;


		/* Split on single newlines if line folding is disabled */

		} else {
			$blocks = explode("\n", $text);

			/* TODO: Fix lists and tables, since these contain newlines */

			return $blocks;
		}
	}

	/**
	 * \private
	 *
	 * Parse a block of text with optional modifiers.
	 *
	 * \param $text
	 *   A block of text
	 *
	 * \return
	 *   Associative array containing all block properties (or default values)
	 */
	function _parse_block($text)
	{
		assert('is_string($text);');

		/* This will be the resulting array. The default values will be
		 * overwritten if the modifier specifies other values. */

		$result = array(
			'text'          => $text,
			'type'          => null,
			'class'         => null,
			'id'            => null,
			'style'         => null,
			'lang'          => null,
			'align'         => null,
			'continuation'  => false,
			'padding-left'  => 0,
			'padding-right' => 0,
			'level'         => null, // only used for headers
		);

		/* Match headers and regular blocks. */

		$matches = array(); // this will hold all regular expression matches
		if (preg_match(ANEWT_TEXTILE_PATTERN_BLOCK_MODIFIER, $text, $matches))
		{
			/* The text matches. Extract the useful bits. */

			/* These pieces are always available */
			$result['type'] = $matches['type'];
			$result['text'] = $matches['text'];
			$result['continuation'] = (bool) $matches['continuation'];

			/* The modifiers below are optional. Empty values are skipped */
			foreach (array('class', 'id', 'style', 'lang', 'align') as $key)
			{
				$value = trim($matches[$key]);

				if (strlen($value) == 0)
					continue;

				$result[$key] = $matches[$key];
			}
			
			/* Extract padding parameters */
			$result['padding-left']  = strlen($matches['paddingleft']);
			$result['padding-right'] = strlen($matches['paddingright']);

			/* Extract the header level from $type, if this was a header. */
			if (str_has_prefix($matches['type'], 'h'))
			{
				/* TODO: once "relative header level" support is implemented,
				 * this is a good place to extract that data. */
				$result['level'] = (int) $matches['type']{1};
				$result['type'] = 'h';
			}
		}
		/* Else: this text block did not contain a modifier. The $result array
		 * still contains the default values. */


		/* Block continuation */

		if (!is_null($result['type']))
		{
			/* A block type was specified, so this is not a continued block.
			 * Delete the saved block modifiers. */
			$this->_saved_block_modifiers = array();

			/* If this block has a continuation marker to indicate that the
			 * formatting should be retained for subsequent blocks without
			 * formatting instructions, any specified class, style, language or
			 * alignment attributes are saved for later use. */
			if ($result['continuation'])
			{
				foreach (array('class', 'style', 'lang', 'align', 'padding-left', 'padding-right') as $key)
				{
					if (is_null($result[$key]))
						continue;

					$this->_saved_block_modifiers[$key] = $result[$key];
				}
			}
		} else {
			/* No block type specified, so no formatting instructions either.
			 * Apply the saved block modifiers to this block (if any). */
			$result = array_merge($result, $this->_saved_block_modifiers);
		}

		return $result;
	}

	/**
	 * \private
	 *
	 * Parse a block of text into a list structure. If this block is not a list
	 * NULL is returned.
	 *
	 * \param $text
	 *   The text to parse into a list.
	 */
	function _parse_list($text)
	{
		assert('is_string($text);');

		$markers = array('*' => 'ul', '#' => 'ol');
		foreach ($markers as $marker => $list_type)
		{
			/* Check very first character of text */
			if (!str_has_prefix($text, $marker)) {
				/* Too bad */
				continue;
			}

			/* Ok. This might be a list */
			{
				$lines = explode("\n", $text);

				/* Require all lines to start with the same list marker */
				$items = array();
				foreach ($lines as $line)
				{
					/* Bail out if this is not a list after all */
					if (!str_has_prefix($line, $marker))
						return null;

					/* Cut off the list marker */
					$items[] = trim(substr($line, 1));
				}

				return array($items, $list_type);
			}
		}

		/* No list, no result. */
		return null;
	}

	/**
	 * \private
	 *
	 * Process a single block of text. This method detects the type of block and
	 * formats it accordingly. It detects headers, paragraphs, block quotes,
	 * lists, tables and preformatted code.
	 *
	 * \param $text
	 *   The input text, e.g. obtained using from _split_into_blocks.
	 *
	 * \return XHTML-formatted output
	 */
	function _process_block($text)
	{
		/* Lists */
		
		$list_data = $this->_parse_list($text);
		if (!is_null($list_data)) {
			/* FIXME */
			list ($items, $list_type) = $list_data;
			return $this->_format_list($items, $list_type);
		}


		/* Text blocks */

		$parsed_block = $this->_parse_block($text);
		$block_type = $parsed_block['type'];

		/* The default block type is a paragraph of text */
		if (is_null($block_type))
			$block_type = 'p';

		switch ($block_type)
		{
			/* Normal paragraphs */
			case 'p':
				return $this->_format_paragraph($parsed_block);
				break;

			/* Block quotes */
			case 'bq':
				return $this->_format_blockquote($parsed_block);
				break;

			/* Headers */
			case 'h':
				return $this->_format_header($parsed_block);
				break;

			default:
				assert('false; // may not be reached');
				break;
		}

		assert('false; // may not be reached');
	}

	/**
	 * \private
	 *
	 * Build a block level HTML element with the specified properties.
	 *
	 * \param $tag_name
	 *   The name of the XHTML tag that should be created. Only a few
	 *   block-level tags can be handled.
	 *
	 * \param $parsed_block
	 *   A parsed block
	 *
	 * \return
	 *   XHTML snippet as a string
	 */
	function _build_html_block_element($tag_name, $parsed_block)
	{
		assert('is_string($tag_name); // tag name must be a string');
		assert('in_array($tag_name, explode(",", "p,h1,h2,h3,h4,h5,h6,div")); // invalid tag name');
		$attr = array();

		/* Class, id and language attributes can be copied. */

		foreach (array('class', 'id', 'lang') as $key) {
			if (!is_null($parsed_block[$key]))
				$attr[$key] = $parsed_block[$key];
		}

		/* The style attribute is a bit trickier, since the alignment and padding
		 * specificiers must be incorporated as well. */

		$attr['style'] = '';
		if (!is_null($parsed_block['style']))
		{
			$attr['style'] = $parsed_block['style'];

			/* Make sure the style declaration ends with a semicolon, so that we
			 * can safely append other CSS snippets. */
			if (!str_has_suffix($attr['style'], ';'))
				$attr['style'] .= ';';
		}

		/* Alignment */

		if (!is_null($parsed_block['align']))
		{
			$spec_to_value_mapping = array(
				'<'  => 'left',
				'>'  => 'right',
				'='  => 'center',
				'<>' => 'justify',
			);
			$attr['style'] .= sprintf(' text-align: %s;', $spec_to_value_mapping[$parsed_block['align']]);
		}

		/* Padding */

		if ($parsed_block['padding-left'] > 0)
			$attr['style'] .= sprintf(' padding-left: %dem;', $parsed_block['padding-left']);

		if ($parsed_block['padding-right'] > 0)
			$attr['style'] .= sprintf(' padding-right: %dem;', $parsed_block['padding-right']);


		/* Only include style attribute if needed */

		if (strlen($attr['style']) == 0)
			array_unset_key($attr, 'style');


		/* Generate the result */

		switch ($tag_name)
		{
			case 'p':
				$el = new AnewtXHTMLParagraph(null);
				break;

			case 'div':
				$el = new AnewtXHTMLDiv(null);
				break;

			case 'h1':
				$el = new AnewtXHTMLHeader1(null);
				break;

			case 'h2':
				$el = new AnewtXHTMLHeader2(null);
				break;

			case 'h3':
				$el = new AnewtXHTMLHeader3(null);
				break;

			case 'h4':
				$el = new AnewtXHTMLHeader4(null);
				break;

			case 'h5':
				$el = new AnewtXHTMLHeader5(null);
				break;

			case 'h6':
				$el = new AnewtXHTMLHeader6(null);
				break;

			default:
				assert('false; // unknown block tag name');
				break;
		}
		$el->append_child(ax_raw($parsed_block['markup']));
		$el->set_attributes($attr);
		return $el;
	}

	/**
	 * \private
	 *
	 * Formats a single paragraph. This method will format a paragraph,
	 * processing several optional markup attributes.
	 *
	 * \param $parsed_block
	 *   A parsed block
	 */
	function _format_paragraph($parsed_block)
	{
		$parsed_block['markup'] = $this->_format_inline($parsed_block['text']);
		return $this->_build_html_block_element('p', $parsed_block);
	}

	/**
	 * \private
	 *
	 * Format a block quote.
	 *
	 * \param $parsed_block
	 *   A parsed block instance
	 *
	 * \return
	 *   Blockquote HTML for this block.
	 */
	function _format_blockquote($parsed_block)
	{
		/* TODO: make this method work properly using _build_html_block_element
		 * or something like that. Multi-paragraph block quotes should be
		 * handled too. The <p> tags will go in a surrounding <blockquote> tag.
		 */
		return new AnewtXHTMLBlockQuote(new AnewtXHTMLParagraph($parsed_block['text']));
	}

	/**
	 * \private
	 *
	 * Formats a header block. This method will format a paragraph,
	 * processing several optional markup attributes.
	 *
	 * \param $parsed_block
	 *   A parsed block
	 */
	function _format_header($parsed_block)
	{
		$tag_name = sprintf('h%d', $parsed_block['level']);
		$parsed_block['markup'] = $this->_format_inline($parsed_block['text']);
		return $this->_build_html_block_element($tag_name, $parsed_block);
	}

	/**
	 * \private
	 *
	 * Format a list block
	 */
	function _format_list($items, $type=null)
	{
		assert('is_array($items);');
		assert('($type == "ol") || ($type) == "ul";');

		switch ($type)
		{
			case 'ol':
				$list = new AnewtXHTMLOrderedList();
				break;

			case 'ul':
				$list = new AnewtXHTMLUnorderedList();
				break;

			default:
				trigger_error(
					sprintf('Unknown list type: %s. This should not happen.', $type),
					E_USER_ERROR);
				break;
		}

		foreach ($items as $item)
		{
			$list->append_child(new AnewtXHTMLListItem($this->_format_inline($item)));

			//$this->_format_list($text);
			// TODO: support for recursive lists and styling
		}

		return $list;
	}

	/**
	 * \private
	 *
	 * Format inline text.
	 */
	function _format_inline($inline)
	{
		/*
		 * TODO
		 *
		 * - match hyperlinks
		 * - insert images
		 * - match strong/emphasis/bold/italics
		 * - entities and special characters
		 * - insert typographically correct quotes
		 * - replace '(c)'   ->   copyright sign
		 * - replace '(TM)'  ->   trademark sign
		 * - replace '(R)'   ->   registerd sign
		 * - replace '--'    ->   em dash
		 * - replace ' - '   ->   en dash
		 * - replace '...'   ->   ellipsis
		 * - replace \dx\d   ->   \d×\d (dimension sign)
		 * - acronyms
		 * */

		//old stuff:
		//var_dump($tokens);
		//$fmt_delim = '(\b|[^*_-])';
		//$inline = preg_replace('/\*\*\b([^\*]+?)\b\*\*/', '<b>\1</b>', $inline);
		//$inline = preg_replace('/\*([^\*]+?)\b\*/', '<strong>\1</strong>', $inline);


		assert('is_string($inline);');

		$inline_state = array(
				BOLD => false,
				ITALIC => false,
				STRONG => false,
				EMPH => false,
				SUPERSCRIPT => false,
				SUBSCRIPT => false,
				STRIKETHROUGH => false,
				);

		$modifier_mapping = array(
			'**' => BOLD,
			'__' => ITALIC,
			'*'  => STRONG,
			'_'  => EMPH,
			'^'  => SUPERSCRIPT,
			'~'  => SUBSCRIPT,
			'-'  => STRIKETHROUGH,
		);
		$modifiers = array_keys($modifier_mapping);
		$modifiers_escaped = array();
		foreach ($modifiers as $modifier)
			$modifiers_escaped[] = preg_quote($modifier);

		$splitter = sprintf(
			'/(%s)/',
			join('|', $modifiers_escaped)
		);
		$tokens = preg_split($splitter, $inline, -1, PREG_SPLIT_DELIM_CAPTURE);
		// $tokens = preg_split('/\b/', $inline);
		// $tokens = preg_split('/(\s+)/', $inline, -1, PREG_SPLIT_DELIM_CAPTURE);

		// var_dump($tokens);
		$out_tokens = array();
		foreach ($tokens as $token)
		{
			if (strlen($token) == 0)
				continue;

			if (in_array($token, $modifiers)) {
				$out_tokens[] = $modifier_mapping[$token];
			} else {
				$out_tokens[] = $token;
			}
		}

		$style_stack = array();
		foreach ($out_tokens as $token)
		{
			if (is_int($token))
			{

				if (in_array($token, $style_stack)) {
					/* Close currently open style, taking wrong nesting into
					 * account by just ignoring the incorrectly nested
					 * modifiers. */
					while (true) {
						$last_open_style = array_pop($style_stack);

						switch ($last_open_style) {
							case BOLD:        $out[] = '</b>';      break;
							case ITALIC:      $out[] = '</i>';      break;
							case STRONG:      $out[] = '</strong>'; break;
							case EMPH:        $out[] = '</em>';     break;
							case SUBSCRIPT:   $out[] = '</sub>';    break;
							case SUPERSCRIPT: $out[] = '</sup>';    break;
							default: break;
						}

						if ($last_open_style == $token)
							break;
					}

				} else {
					array_push($style_stack, $token);
					switch ($token) {
						case BOLD:        $out[] = '<b>';      break;
						case ITALIC:      $out[] = '<i>';      break;
						case STRONG:      $out[] = '<strong>'; break;
						case EMPH:        $out[] = '<em>';     break;
						case SUBSCRIPT:   $out[] = '<sub>';    break;
						case SUPERSCRIPT: $out[] = '<sup>';    break;
						default: break;
					}

				}
			} else {
				$out[] = $token;
			}
		}

		/* Close the remaining bits */
		while ($style_stack) {
			$style = array_pop($style_stack);
			switch ($style) {
				case BOLD:        $out[] = '</b>';      break;
				case ITALIC:      $out[] = '</i>';      break;
				case STRONG:      $out[] = '</strong>'; break;
				case EMPH:        $out[] = '</em>';     break;
				case SUBSCRIPT:   $out[] = '</sub>';    break;
				case SUPERSCRIPT: $out[] = '</sup>';    break;
				default: break;
			}
		}

		return join('', $out);
		//return $inline;
	}


	/* Public API starts here */

	/**
	 * Processes the text. This method will transform the input text into XHTML,
	 * thereby converting headers, paragraphs, lists, block quotes and other
	 * block elements into their corresponding XHTML tags. Hyperlinks, inline
	 * markup (like emphasized or strong words), images and code is also
	 * converted. Additionally, several typographic enhancements are made to
	 * inline text (curly quotes, em dashes, entity replacements, etc).
	 *
	 * \param $text
	 *   Input text in Textile format
	 *
	 * \return
	 *   Processed text in XHTML format
	 */
	function process($text)
	{
		/* Normalize */
		$text = $this->_normalize_text($text);

		/* Split into blocks */
		$blocks = $this->_split_into_blocks($text);

		/* Process each block */
		$out = array();
		foreach ($blocks as $block)
			$out[] = $this->_process_block($block);

		/* Result */
		return to_string($out);
	}
}

?>
