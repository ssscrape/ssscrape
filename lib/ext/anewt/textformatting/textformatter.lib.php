<?php

/* vim:set fdm=marker: */

/*
 * Anewt, Almost No Effort Web Toolkit, textformatter module
 *
 * Copyright (C) 2005-2007  Wouter Bolsterlee <uws@xs4all.nl>
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


/**
 * The TextFormatter class eases text formatting. It provides 4 formatter
 * methods to format the input text: textile, entities, specialchars and raw.
 * The 'textile' formatter formats the text using the rich Textile formatting
 * language. The 'entities' and 'specialchars' formatters escape HTML entities
 * so that the result can safely be displayed in a web browser. The 'raw'
 * formatter returns the unchanged input text (this is useful if the formatter
 * type comes from a database column). Additionally, there's the 'auto'
 * formatter that chooses between textile and raw, depending on the input
 * looking like XHTML (using a very simple heuristic).
 */
class TextFormatter extends Container { // {{{

	/* Static methods */

	/**
	 * \static Formats text using the given formatter. This method is useful if
	 * you don't want to instantiate a TextFormatter object. If you want to
	 * reuse the textformatter instance, don't use this method.
	 *
	 * \param $text
	 *   String with text to format
	 *
	 * \param $formatter
	 *   The name of the formatter to use (optional)
	 *
	 * \return
	 *   String containing the formatted text
	 */
	function format($text, $formatter=null) { // {{{
		assert('is_string($text)');
		assert('is_string($formatter) || is_null($formatter)');

		$f = new TextFormatter($text, $formatter);
		return $f->render();
	} // }}}


	/* Instance methods and variables */

	/** \private List of all available formatters. */
	var $formatters = array(
			'auto',
			'entities',
			'raw',
			'specialchars',
			'textile',
			);

	/** The default formatter */
	var $default_formatter = 'auto';

	/**
	 * Initialize a new text formatter instance. Both the text and formatter
	 * parameters are optional and can be set later using the 'text' and
	 * 'formatter' container properties.
	 *
	 * \param $text
	 *   String with text to format (optional).
	 *
	 * \param $formatter
	 *   The name of the formatter to use (optional).
	 */
	function TextFormatter($text=null, $formatter=null) { // {{{
		if (!is_null($text))
			$this->set('text', $text);

		if (!is_null($formatter)) {
			assert('in_array($formatter, $this->formatters)');
			$this->set('formatter', $formatter);
		}
	} // }}}

	/**
	 * Return the formatted text.
	 *
	 * \return
	 *   The formatted text
	 */
	function render() { // {{{
		$formatter = $this->getdefault('formatter', $this->default_formatter);
		$text = $this->get('text');

		assert('in_array($formatter, $this->formatters, true)');
		assert('$this->is_set("text")');
		assert('is_string($this->get("text"))');

		/**
		 * Simple heuristic for the automatic mode: check whether the first
		 * non-whitespace character is a < character. If so, assume it's already
		 * formatted as HTML and treat it as 'raw'. Otherwise, use textile. To
		 * improve speed, only the first 50 characters are considered.
		 */
		if ($formatter == 'auto') {
			$formatter = str_has_prefix(ltrim(substr($text, 0, 50)), '<')
				? 'raw'
				: 'textile';
		}

		switch ($formatter) {

			case 'raw':
				break;

			case 'entities':
				$text = htmlentities($text);
				break;

			case 'specialchars':
				$text = htmlspecialchars($text);
				break;

			case 'textile':
				anewt_include('textile');

				/* FIXME: remove the ANEWT_TEXTILE_DEVELOPMENT workaround when
				 * textile code is ready for production code. */
				if (defined('ANEWT_TEXTILE_DEVELOPMENT')) {
					$t = new AnewtTextile();
					$result = $t->process($text);
				} else {
					$t = new Textile();
					$result = $t->TextileThis($text);
				}

				$text = trim($result);
				break;

			default:
				trigger_error(sprintf(
							'%s:%s(): Unknown formatter: \'%s\'',
							__CLASS__,
							__FUNCTION__,
							$formatter
							), E_USER_ERROR);

		}

		return $text;
	} // }}}
} // }}}

?>
