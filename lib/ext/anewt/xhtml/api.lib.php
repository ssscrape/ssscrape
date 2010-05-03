<?php

/*
 * Anewt, Almost No Effort Web Toolkit, XHTML module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * \file
 *
 * XHTML convenience API functions.
 */


/**
 * Create a new raw content node.
 *
 * \param $content
 *   The content of the raw node.
 *
 * \see AnewtXHTMLRaw
 */
function ax_raw($content)
{
	$node = new AnewtXHTMLRaw($content);
	return $node;
}


/**
 * Create a new fragment node.
 *
 * \param $children
 *   The content of the fragment node.
 *
 * \see AnewtXHTMLFragment
 */
function ax_fragment($children=null)
{
	$node = new AnewtXHTMLFragment();
	$children = func_get_args();
	if ($children)
		$node->append_children($children);
	return $node;
}


/**
 * Format a text node using a format specifier and supplied values.
 *
 * This method acts like sprintf(), but supports DOM nodes and takes care of XML
 * escaping. This function formats its arguments into a (raw) DOM node instead
 * of a string. The supplied values may regular values such as strings and
 * numbers, but may be XML nodes as well. This means you can pass XML node
 * instances created by the functions in the xhtml module as values. The format
 * specifier will be escaped for XML, and XML nodes will be rendered into
 * escaped strings before substitution into the format specifier.
 *
 * This example results in a valid XHTML paragraph:
 *
 * <code>
 * ax_p(ax_sprintf('%s & %s', ax_span_class('Sugar', 'sweet'), 'Spice'));
 * </code>
 *
 * \param $format
 *   A format specifier in sprintf syntax.
 *
 * \param $values
 *   One or more values to be substituted into the format string.
 *
 * \return
 *   An AnewtXHTMLRaw instance that can be added to a DOM tree (or page).
 */
function ax_sprintf($format, $values)
{
	/* Accept multiple parameters, just like sprintf */
	$values = func_get_args();

	/* First parameter is the format */
	$format = array_shift($values);
	if ($format instanceof AnewtXMLDomNode) {
		$format = to_string($format);
	} else {
		assert('is_string($format)');
		$format = htmlspecialchars($format);
	}

	/* Render DOM nodes into strings. The $values array is modified in-place. */
	foreach (array_keys($values) as $key)
	{
		if (is_string($values[$key]))
			$values[$key] = htmlspecialchars($values[$key]);
		elseif ($values[$key] instanceof AnewtXMLDomNode)
			$values[$key] = to_string($values[$key]);
	}

	/* Everything is already escaped, so return a raw node */
	return ax_raw(vsprintf($format, $values));
}


/* Text (See text.lib.php) */

/* Text: Phrase elements */

/**
 * Create an AnewtXHTMLEmphasis element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_em($content, $attributes=null)
{
	$element = new AnewtXHTMLEmphasis($content, $attributes);
	return $element;
}


/**
 * Create an AnewtXHTMLStrong element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_strong($content, $attributes=null)
{
	$element = new AnewtXHTMLStrong($content, $attributes);
	return $element;
}


/**
 * Create an AnewtXHTMLDefinition element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_dfn($content, $attributes=null)
{
	$element = new AnewtXHTMLDefinition($content, $attributes);
	return $element;
}


/**
 * Create an AnewtXHTMLCode element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_code($content, $attributes=null)
{
	$element = new AnewtXHTMLCode($content, $attributes);
	return $element;
}


/**
 * Create an AnewtXHTMLSample element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_sample($content, $attributes=null)
{
	$element = new AnewtXHTMLSample($content, $attributes);
	return $element;
}


/**
 * Create an AnewtXHTMLKeyboard element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_kbd($content, $attributes=null)
{
	$element = new AnewtXHTMLKeyboard($content, $attributes);
	return $element;
}


/**
 * Create an AnewtXHTMLVariable element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_var($content, $attributes=null)
{
	$element = new AnewtXHTMLVariable($content, $attributes);
	return $element;
}


/**
 * Create an AnewtXHTMLCitation element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_cite($content, $attributes=null)
{
	$element = new AnewtXHTMLCitation($content, $attributes);
	return $element;
}


/**
 * Create an AnewtXHTMLAbbreviation element.
 * \param $content The content for this element
 * \param $title The full text for this abbreviation
 * \param $attributes Additional element attributes (optional)
 */
function ax_abbr($content, $title, $attributes=null)
{
	$element = new AnewtXHTMLAbbreviation($content, $attributes);
	$element->set_attribute('title', $title);
	return $element;
}

/**
 * Create an AnewtXHTMLAcronym element.
 * \param $content The content for this element
 * \param $title The full text for this acronym
 * \param $attributes Additional element attributes (optional)
 */
function ax_acronym($content, $title, $attributes=null)
{
	$element = new AnewtXHTMLAcronym($content, $attributes);
	$element->set_attribute('title', $title);
	return $element;
}


/* Text: Quotations */

/**
 * Create an AnewtXHTMLBlockQuote element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_blockquote($content, $attributes=null)
{
	$element = new AnewtXHTMLBlockQuote($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLQuote element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_q($content, $attributes=null)
{
	$element = new AnewtXHTMLQuote($content, $attributes);
	return $element;
}


/* Text: Subscripts and superscripts */

/**
 * Create an AnewtXHTMLSubscript element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_sub($content, $attributes=null)
{
	$element = new AnewtXHTMLSubscript($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLSuperscript element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_sup($content, $attributes=null)
{
	$element = new AnewtXHTMLSuperscript($content, $attributes);
	return $element;
}


/* Text: Lines and paragraphs */

/**
 * Create an AnewtXHTMLParagraph element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_p($content, $attributes=null)
{
	$element = new AnewtXHTMLParagraph($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLParagraph element with a class.
 * \param $content The content for this element
 * \param $class The class name for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_p_class($content, $class, $attributes=null)
{
	$element = ax_p($content, $attributes);
	$element->set_attribute('class', $class);
	return $element;
}

/**
 * Create an AnewtXHTMLBreak element.
 * \param $attributes Additional element attributes (optional)
 */
function ax_br($attributes=null)
{
	$element = new AnewtXHTMLBreak(null, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLPreformatted element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_pre($content, $attributes=null)
{
	$element = new AnewtXHTMLPreformatted($content, $attributes);
	return $element;
}


/* Text: Marking document changes */

/**
 * Create an AnewtXHTMLInsertion element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_ins($content, $attributes=null)
{
	$element = new AnewtXHTMLInsertion($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLDeletion element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_del($content, $attributes=null)
{
	$element = new AnewtXHTMLDeletion($content, $attributes);
	return $element;
}


/* Headings */

/**
 * Create an AnewtXHTMLHeader1 element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_h1($content, $attributes=null)
{
	$element = new AnewtXHTMLHeader1($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLHeader2 element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_h2($content, $attributes=null)
{
	$element = new AnewtXHTMLHeader2($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLHeader3 element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_h3($content, $attributes=null)
{
	$element = new AnewtXHTMLHeader3($content, $attributes);
	return $element;
}


/**
 * Create an AnewtXHTMLHeader4 element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_h4($content, $attributes=null)
{
	$element = new AnewtXHTMLHeader4($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLHeader5 element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_h5($content, $attributes=null)
{
	$element = new AnewtXHTMLHeader5($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLHeader6 element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_h6($content, $attributes=null)
{
	$element = new AnewtXHTMLHeader6($content, $attributes);
	return $element;
}


/* Lists */

/* Lists: Unordered lists, ordered lists, and list items */

/**
 * Create an AnewtXHTMLUnorderedList element.
 * \param $attributes Additional element attributes (optional)
 */
function ax_ul($attributes=null)
{
	$element = new AnewtXHTMLUnorderedList($attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLUnorderedList element with items.
 * \param $items List of AnewtXHTMLListItem instances to append to the list.
 * \param $attributes Additional element attributes (optional)
 */
function ax_ul_items($items, $attributes=null)
{
	assert('is_numeric_array($items);');
	$element = ax_ul($attributes);
	$element->append_children($items);
	return $element;
}

/**
 * Create an AnewtXHTMLOrderedList element.
 * \param $attributes Additional element attributes (optional)
 */
function ax_ol($attributes=null)
{
	$element = new AnewtXHTMLOrderedList($attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLOrderedList element with items.
 * \param $items List of AnewtXHTMLListItem instances to append to the list.
 * \param $attributes Additional element attributes (optional)
 */
function ax_ol_items($items, $attributes=null)
{
	assert('is_numeric_array($items);');
	$element = ax_ol($attributes);
	$element->append_children($items);
	return $element;
}

/**
 * Create an AnewtXHTMLListItem element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_li($content, $attributes=null)
{
	$element = new AnewtXHTMLListItem($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLListItem element with a class.
 * \param $content The content for this element
 * \param $class The class name for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_li_class($content, $class, $attributes=null)
{
	$element = new AnewtXHTMLListItem($content, $attributes);
	$element->set_attribute('class', $class);
	return $element;
}


/* Lists: Definition lists */

/**
 * Create an AnewtXHTMLDefinitionList element.
 * \param $attributes Additional element attributes (optional)
 */
function ax_dl($attributes=null)
{
	$element = new AnewtXHTMLDefinitionList($attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLDefinitionList element with items.
 * \param $items List of AnewtXHTMLDefinitionTerm or
 * AnewtXHTMLDefinitionDescription instances to append to the list.
 * \param $attributes Additional element attributes (optional)
 */
function ax_dl_items($items, $attributes=null)
{
	$element = ax_dl($attributes);
	$element->append_children($items);
	return $element;
}

/**
 * Create an AnewtXHTMLDefinitionTerm element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_dt($content, $attributes=null)
{
	$element = new AnewtXHTMLDefinitionTerm($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLDefinitionDescription element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_dd($content, $attributes=null)
{
	$element = new AnewtXHTMLDefinitionDescription($content, $attributes);
	return $element;
}


/* Grouping */

/**
 * Create an AnewtXHTMLDiv element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_div($content, $attributes=null)
{
	$element = new AnewtXHTMLDiv($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLDiv element with an id.
 * \param $content The content for this element
 * \param $id The id for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_div_id($content, $id, $attributes=null)
{
	$element = ax_div($content, $attributes);
	$element->set_attribute('id', $id);
	return $element;
}

/**
 * Create an AnewtXHTMLDiv element with a class.
 * \param $content The content for this element
 * \param $class The class name for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_div_class($content, $class, $attributes=null)
{
	$element = ax_div($content, $attributes);
	$element->set_attribute('class', $class);
	return $element;
}

/**
 * Create an AnewtXHTMLDiv element with a class and id.
 * \param $content The content for this element
 * \param $class The class name for this element
 * \param $id The id for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_div_class_id($content, $class, $id, $attributes=null)
{
	$element = ax_div($content, $attributes);
	$element->set_attribute('class', $class);
	$element->set_attribute('id', $id);
	return $element;
}

/**
 * Create an AnewtXHTMLSpan element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_span($content, $attributes=null)
{
	$element = new AnewtXHTMLSpan($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLSpan element with an id.
 * \param $content The content for this element
 * \param $id The id for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_span_id($content, $id, $attributes=null)
{
	$element = ax_span($content, $attributes);
	$element->set_attribute('id', $id);
	return $element;
}

/**
 * Create an AnewtXHTMLSpan element with a class.
 * \param $content The content for this element
 * \param $class The class name for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_span_class($content, $class, $attributes=null)
{
	$element = ax_span($content, $attributes);
	$element->set_attribute('class', $class);
	return $element;
}

/**
 * Create an AnewtXHTMLSpan element with a class and id.
 * \param $content The content for this element
 * \param $class The class name for this element
 * \param $id The id for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_span_class_id($content, $class, $id, $attributes=null)
{
	$element = ax_span($content, $attributes);
	$element->set_attribute('class', $class);
	$element->set_attribute('id', $id);
	return $element;
}


/* Links: Hypertext and Media-Independent Links  */

/**
 * Create an AnewtXHTMLAnchor element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_a($content, $attributes=null)
{
	$element = new AnewtXHTMLAnchor($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLAnchor element with a href attribute.
 * \param $content The content for this element
 * \param $href The link target used for the href attribute
 * \param $attributes Additional element attributes (optional)
 */
function ax_a_href($content, $href, $attributes=null)
{
	$element = new AnewtXHTMLAnchor($content, $attributes);
	$element->set_attribute('href', $href);
	return $element;
}

/**
 * Create an AnewtXHTMLAnchor element with a href attribute.
 * \param $content The content for this element
 * \param $href The link target used for the href attribute
 * \param $rel The value for the rel attribute
 * \param $attributes Additional element attributes (optional)
 */
function ax_a_href_rel($content, $href, $rel, $attributes=null)
{
	$element = new AnewtXHTMLAnchor($content, $attributes);
	$element->set_attribute('href', $href);
	$element->set_attribute('rel', $rel);
	return $element;
}

/**
 * Create an AnewtXHTMLAnchor element with a href attribute.
 * \param $content The content for this element
 * \param $href The link target used for the href attribute
 * \param $title The value for the title attribute
 * \param $attributes Additional element attributes (optional)
 */
function ax_a_href_title($content, $href, $title, $attributes=null)
{
	$element = new AnewtXHTMLAnchor($content, $attributes);
	$element->set_attribute('href', $href);
	$element->set_attribute('title', $title);
	return $element;
}

/**
 * Create an AnewtXHTMLLink element.
 * \param $attributes Additional element attributes (optional)
 */
function ax_link($attributes=null)
{
	$element = new AnewtXHTMLLink($attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLLink element for RSS feeds.
 * \param $href The link target used for the href attribute
 * \param $title The value for the title attribute
 * \param $attributes Additional element attributes (optional)
 */
function ax_link_rss($href, $title, $attributes=null)
{
	$element = new AnewtXHTMLLink($attributes);
	$element->set_attribute('href', $href);
	$element->set_attribute('title', $title);
	$element->set_attribute('rel', 'alternate');
	$element->set_attribute('type', 'application/rss+xml');
	return $element;
}

/**
 * Create an AnewtXHTMLBase element.
 * \param $attributes Additional element attributes (optional)
 */
function ax_base($attributes=null)
{
	$element = new AnewtXHTMLBase($attributes);
	return $element;
}


/* Objects, Images, and Applets */

/**
 * Create an image element. This is an alias for ax_img_src.
 * \param $src Source for the image
 * \param $attributes Additional element attributes (optional)
 * \see ax_img_src
 */
function ax_img($src, $attributes=null)
{
	$element = ax_img_src($src, $attributes);
	return $element;
}

/**
 * Create an image element.
 * \param $src Source for the image
 * \param $attributes Additional element attributes (optional)
 */
function ax_img_src($src, $attributes=null)
{
	/* Use src for alt, since the alt attribute is required */
	$element = ax_img_src_alt($src, $src, $attributes);
	return $element;
}

/**
 * Create an image element with an alt attribute.
 * \param $src Source for the image
 * \param $alt The alternative text for the image
 * \param $attributes Additional element attributes (optional)
 */
function ax_img_src_alt($src, $alt, $attributes=null)
{
	$element = new AnewtXHTMLImage($attributes);
	$element->set_attribute('src', $src);
	$element->set_attribute('alt', $alt);
	return $element;
}

/**
 * Create an image element with alt and title attributes.
 * \param $src Source for the image
 * \param $alt The alternative text for the image
 * \param $title The title for the image
 * \param $attributes Additional element attributes (optional)
 */
function ax_img_src_alt_title($src, $alt, $title, $attributes=null)
{
	$element = ax_img_src_alt($src, $alt, $attributes);
	$element->set_attribute('title', $title);
	return $element;
}


/* Font style elements */

/**
 * Create an AnewtXHTMLTeletype element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_tt($content, $attributes=null)
{
	$element = new AnewtXHTMLTeletype($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLItalic element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_i($content, $attributes=null)
{
	$element = new AnewtXHTMLItalic($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLBold element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_b($content, $attributes=null)
{
	$element = new AnewtXHTMLBold($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLBig element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_big($content, $attributes=null)
{
	$element = new AnewtXHTMLBig($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLSmall element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_small($content, $attributes=null)
{
	$element = new AnewtXHTMLSmall($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLStrike element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_strike($content, $attributes=null)
{
	$element = new AnewtXHTMLStrike($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLUnderline element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_u($content, $attributes=null)
{
	$element = new AnewtXHTMLUnderline($content, $attributes);
	return $element;
}


/* Additional elements */

/**
 * Create an AnewtXHTMLStyle element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_stylesheet($content, $attributes=null)
{
	$element = new AnewtXHTMLStyle($content, $attributes);
	$element->set_attribute('type', 'text/css');
	return $element;
}

/**
 * Create an AnewtXHTMLStyle element with an href attribute.
 * \param $href The link target used for the href attribute
 * \param $attributes Additional element attributes (optional)
 */
function ax_stylesheet_href($href, $attributes=null)
{
	$element = ax_stylesheet_href_media($href, 'all', $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLStyle element with href and media attributes.
 * \param $href The link target used for the href attribute
 * \param $media The value for the media attribue
 * \param $attributes Additional element attributes (optional)
 */
function ax_stylesheet_href_media($href, $media, $attributes=null)
{
	$element = ax_link($attributes);
	$element->set_attribute('type', 'text/css');
	$element->set_attribute('rel', 'stylesheet');
	$element->set_attribute('media', $media);
	$element->set_attribute('href', $href);
	return $element;
}

/**
 * Create an AnewtXHTMLScript element.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_script($content, $attributes=null)
{
	$element = new AnewtXHTMLScript($content, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLScript element for JavaScript.
 * \param $content The content for this element
 * \param $attributes Additional element attributes (optional)
 */
function ax_javascript($content, $attributes=null)
{
	$element = ax_script($content, $attributes);
	$element->set_attribute('type', 'text/javascript');
	return $element;
}

/**
 * Create an AnewtXHTMLScript element for JavaScript with a src attribute.
 * \param $src Source url of the script
 * \param $attributes Additional element attributes (optional)
 */
function ax_javascript_src($src, $attributes=null)
{
	$element = ax_javascript(null);
	$element->set_attribute('src', $src);
	return $element;
}

/**
 * Create an AnewtXHTMLMeta element.
 * \param $attributes Additional element attributes (optional)
 */
function ax_meta($attributes=null)
{
	$element = new AnewtXHTMLMeta(null, $attributes);
	return $element;
}

/**
 * Create an AnewtXHTMLMeta element with name a content attributes.
 * \param $name The name attribute for this meta element
 * \param $content The content attribute for this meta element
 * \param $attributes Additional element attributes (optional)
 */
function ax_meta_name_content($name, $content, $attributes=null)
{
	$element = ax_meta();
	$element->set_attribute('name', $name);
	$element->set_attribute('content', $content);
	return $element;
}

/**
 * Create an AnewtXHTMLTitle element.
 * \param $content The content for this element
 */
function ax_title($content)
{
	$element = new AnewtXHTMLTitle($content);
	return $element;
}

?>
