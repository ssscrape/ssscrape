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
 * Miscellaneous element classes.
 */


/* Grouping elements */

/**
 * Div element for block-level grouping.
 *
 * \see http://www.w3.org/TR/html4/struct/global.html#h-7.5.4
 */
final class AnewtXHTMLDiv extends AnewtXHTMLBlockElement
{
	public $node_name = 'div';
}


/**
 * Span element for inline grouping.
 *
 * \see http://www.w3.org/TR/html4/struct/global.html#h-7.5.4
 */
final class AnewtXHTMLSpan extends AnewtXHTMLInlineElement
{
	public $node_name = 'span';
}


/* Headings */

/**
 * \private
 *
 * Abstract base class for header elements.
 */
abstract class AnewtXHTMLHeaderBase extends AnewtXHTMLBlockElement
{
}


/**
 * Level 1 header element.
 *
 * \see http://www.w3.org/TR/html4/struct/global.html#h-7.5.5
 */
final class AnewtXHTMLHeader1 extends AnewtXHTMLHeaderBase
{
	public $node_name = 'h1';
}


/**
 * Level 2 header element.
 *
 * \see http://www.w3.org/TR/html4/struct/global.html#h-7.5.5
 */
final class AnewtXHTMLHeader2 extends AnewtXHTMLHeaderBase
{
	public $node_name = 'h2';
}


/**
 * Level 3 header element.
 *
 * \see http://www.w3.org/TR/html4/struct/global.html#h-7.5.5
 */
final class AnewtXHTMLHeader3 extends AnewtXHTMLHeaderBase
{
	public $node_name = 'h3';
}


/**
 * Level 4 header element.
 *
 * \see http://www.w3.org/TR/html4/struct/global.html#h-7.5.5
 */
final class AnewtXHTMLHeader4 extends AnewtXHTMLHeaderBase
{
	public $node_name = 'h4';
}


/**
 * Level 5 header element.
 *
 * \see http://www.w3.org/TR/html4/struct/global.html#h-7.5.5
 */
final class AnewtXHTMLHeader5 extends AnewtXHTMLHeaderBase
{
	public $node_name = 'h5';
}


/**
 * Level 6 header element.
 *
 * \see http://www.w3.org/TR/html4/struct/global.html#h-7.5.5
 */
final class AnewtXHTMLHeader6 extends AnewtXHTMLHeaderBase
{
	public $node_name = 'h6';
}


/* Links: Hypertext and Media-Independent Links  */

/**
 * Anchor element.
 *
 * \see http://www.w3.org/TR/html4/struct/links.html#h-12.2
 */
final class AnewtXHTMLAnchor extends AnewtXHTMLInlineElement
{
	public $node_name = 'a';
}


/**
 * Link element for specifying document relationships.
 *
 * \see http://www.w3.org/TR/html4/struct/links.html#h-12.3
 */
final class AnewtXHTMLLink extends AnewtXHTMLBlockElement
{
	public $node_name = 'link';
	protected $must_be_empty = true;
	protected $allows_text = false;
	public $always_render_closing_tag = false;
}


/**
 * Base element for specifying path information.
 *
 * \see http://www.w3.org/TR/html4/struct/links.html#h-12.4
 */
final class AnewtXHTMLBase extends AnewtXHTMLBlockElement
{
	public $node_name = 'base';
	protected $must_be_empty = true;
	protected $allows_text = false;
	public $always_render_closing_tag = false;
}


/* Objects, Images, and Applets */

/* TODO: object, flash */

/**
 * Image element.
 *
 * \see http://www.w3.org/TR/REC-html40/struct/objects.html#h-13.2
 */
final class AnewtXHTMLImage extends AnewtXHTMLInlineElement
{
	public $node_name = 'img';
	protected $must_be_empty = true;
	protected $allows_text = false;
	public $always_render_closing_tag = false;
}


/* Additional elements */

/**
 * Script element
 *
 * \see http://www.w3.org/TR/REC-html40/interact/scripts.html#h-18.2.1
 */
final class AnewtXHTMLScript extends AnewtXHTMLBlockElement
{
	public $node_name = 'script';
}


/**
 * Meta element.
 *
 * \see http://www.w3.org/TR/REC-html40/struct/global.html#h-7.4.4.2
 */
final class AnewtXHTMLMeta extends AnewtXHTMLBlockElement
{
	public $node_name = 'meta';
	public $always_render_closing_tag = false;
	protected $must_be_empty = true;
	protected $allows_text = false;
}

/**
 * Title element.
 *
 * \see http://www.w3.org/TR/REC-html40/struct/global.html#h-7.4.2
 */
final class AnewtXHTMLTitle extends AnewtXHTMLBlockElement
{
	public $node_name = 'title';
}

/**
 * Style element.
 *
 * \see http://www.w3.org/TR/REC-html40/present/styles.html#h-14.2.3
 */
final class AnewtXHTMLStyle extends AnewtXHTMLBlockElement
{
	public $node_name = 'style';
}

?>
