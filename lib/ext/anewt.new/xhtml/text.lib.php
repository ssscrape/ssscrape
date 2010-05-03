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
 * Text element classes.
 *
 * See http://www.w3.org/TR/html4/struct/text.html
 */


/* Phrase elements */

/**
 * Inline element to indicate emphasis.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.1
 */
final class AnewtXHTMLEmphasis extends AnewtXHTMLInlineElement
{
	public $node_name = 'em';
}


/**
 * Inline element to indicate stronger emphasis.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.1
 */
final class AnewtXHTMLStrong extends AnewtXHTMLInlineElement
{
	public $node_name = 'strong';
}


/**
 * Inline element for definitions.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.1
 */
final class AnewtXHTMLDefinition extends AnewtXHTMLInlineElement
{
	public $node_name = 'dfn';
}


/**
 * Inline element for fragments of computer code.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.1
 */
final class AnewtXHTMLCode extends AnewtXHTMLInlineElement
{
	public $node_name = 'code';
}


/**
 * Inline element for sample output from programs, scripts, etc.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.1
 */
final class AnewtXHTMLSample extends AnewtXHTMLInlineElement
{
	public $node_name = 'samp';
}


/**
 * Inline element to indicate text to be entered by the user.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.1
 */
final class AnewtXHTMLKeyboard extends AnewtXHTMLInlineElement
{
	public $node_name = 'kbd';
}


/**
 * Inline element to indicate an instance of a variable or program argument.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.1
 */
final class AnewtXHTMLVariable extends AnewtXHTMLInlineElement
{
	public $node_name = 'var';
}


/**
 * Inline element that contains a citation or a reference to other sources.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.1
 */
final class AnewtXHTMLCitation extends AnewtXHTMLInlineElement
{
	public $node_name = 'cite';
}


/**
 * Inline element to indicate an abbreviated form.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.1
 */
final class AnewtXHTMLAbbreviation extends AnewtXHTMLInlineElement
{
	public $node_name = 'abbr';
}


/**
 * Inline element to indicate an acronym.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.1
 */
final class AnewtXHTMLAcronym extends AnewtXHTMLInlineElement
{
	public $node_name = 'acronym';
}


/* Quotations */

/**
 * Block level element for long quotations.
 *
 * Note that you need to put a block level element such as an
 * AnewtXHTMLParagraph instance into this element.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.2
 */
final class AnewtXHTMLBlockQuote extends AnewtXHTMLBlockElement
{
	public $node_name = 'blockquote';
}


/**
 * Inline element for short quotations.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.2
 */
final class AnewtXHTMLQuote extends AnewtXHTMLInlineElement
{
	public $node_name = 'q';
}


/* Subscripts and superscripts */

/**
 * Inline element for text in subscript.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.3
 */
final class AnewtXHTMLSubscript extends AnewtXHTMLInlineElement
{
	public $node_name = 'sub';
}


/**
 * Inline element for text in superscript.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.2.3
 */
final class AnewtXHTMLSuperscript extends AnewtXHTMLInlineElement
{
	public $node_name = 'sup';
}



/* Lines and paragraphs */

/**
 * Block element for paragraphs of text.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.3.1
 */
final class AnewtXHTMLParagraph extends AnewtXHTMLBlockElement
{
	public $node_name = 'p';
}


/**
 * Inline element to force a line break.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.3.2.1
 */
final class AnewtXHTMLBreak extends AnewtXHTMLInlineElement
{
	public $node_name = 'br';
	protected $must_be_empty = true;
	protected $allows_text = false;
	public $always_render_closing_tag = false;
}


/**
 * Block element for preformatted text.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.3.4
 */
final class AnewtXHTMLPreformatted extends AnewtXHTMLBlockElement
{
	public $node_name = 'pre';
}


/* Marking document changes */

/**
 * Inline element to mark insertions.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.4
 */
final class AnewtXHTMLInsertion extends AnewtXHTMLInlineElement
{
	public $node_name = 'ins';
}


/**
 * Inline element to mark deletions.
 *
 * \see http://www.w3.org/TR/html4/struct/text.html#h-9.4
 */
final class AnewtXHTMLDeletion extends AnewtXHTMLInlineElement
{
	public $node_name = 'del';
}


/* Font style element classes */

/**
 * Inline element for teletype text.
 *
 * Try to avoid using this class; use stylesheets instead.
 *
 * \see http://www.w3.org/TR/REC-html40/present/graphics.html#h-15.2.1
 */
final class AnewtXHTMLTeletype extends AnewtXHTMLInlineElement
{
	public $node_name = 'tt';
}


/**
 * Inline element for italic text.
 *
 * Try to avoid using this class; use stylesheets instead.
 *
 * \see http://www.w3.org/TR/REC-html40/present/graphics.html#h-15.2.1
 */
final class AnewtXHTMLItalic extends AnewtXHTMLInlineElement
{
	public $node_name = 'i';
}


/**
 * Inline element for boldface text.
 *
 * \see http://www.w3.org/TR/REC-html40/present/graphics.html#h-15.2.1
 */
final class AnewtXHTMLBold extends AnewtXHTMLInlineElement
{
	public $node_name = 'b';
}


/**
 * Inline element for big text.
 *
 * \see http://www.w3.org/TR/REC-html40/present/graphics.html#h-15.2.1
 */
final class AnewtXHTMLBig extends AnewtXHTMLInlineElement
{
	public $node_name = 'big';
}


/**
 * Inline element for small text.
 *
 * Try to avoid using this class; use stylesheets instead.
 *
 * \see http://www.w3.org/TR/REC-html40/present/graphics.html#h-15.2.1
 */
final class AnewtXHTMLSmall extends AnewtXHTMLInlineElement
{
	public $node_name = 'small';
}


/**
 * Inline element for strike-through text.
 *
 * Try to avoid using this class; use stylesheets instead.
 *
 * \see http://www.w3.org/TR/REC-html40/present/graphics.html#h-15.2.1
 */
final class AnewtXHTMLStrike extends AnewtXHTMLInlineElement
{
	public $node_name = 'strike';
}

/**
 * Inline element for underlined text.
 *
 * Try to avoid using this class; use stylesheets instead.
 *
 * \see http://www.w3.org/TR/REC-html40/present/graphics.html#h-15.2.1
 */
final class AnewtXHTMLUnderline extends AnewtXHTMLInlineElement
{
	public $node_name = 'u';
}

?>
