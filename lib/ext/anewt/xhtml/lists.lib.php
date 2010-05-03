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
 * List classes.
 *
 * See http://www.w3.org/TR/html4/struct/lists.html
 */


/**
 * \private
 *
 * Base class for list elements.
 *
 * Do not instantiate this class directly.
 *
 * \see AnewtXHTMLUnorderedList
 * \see AnewtXHTMLOrderedList
 */
class _AnewtXHTMLListBase extends _AnewtXHTMLBlockElement
{
	protected $allowed_elements = array('li');
	protected $allows_text = false;

	function render($indent_level=0)
	{
		/* Override default DOM node rendering: do not output anything if there
		 * are no child nodes at all. Empty lists are not allowed in XHTML. */
		if (!$this->has_child_nodes())
			return '';

		return _AnewtXHTMLBlockElement::render($indent_level);
	}
}


/* Unordered lists, ordered lists, and list items */

/**
 * Unordered list element.
 *
 * \see AnewtXHTMLOrderedList
 * \see AnewtXHTMLListItem
 * \see http://www.w3.org/TR/html4/struct/lists.html#h-10.2
 */
class AnewtXHTMLUnorderedList extends _AnewtXHTMLListBase
{
	public $node_name = 'ul';
}


/**
 * Ordered list element.
 *
 * \see AnewtXHTMLUnorderedList
 * \see AnewtXHTMLListItem
 * \see http://www.w3.org/TR/html4/struct/lists.html#h-10.2
 */
class AnewtXHTMLOrderedList extends _AnewtXHTMLListBase
{
	public $node_name = 'ol';
}


/**
 * List item element.
 *
 * \see AnewtXHTMLUnorderedList
 * \see AnewtXHTMLOrderedList
 * \see http://www.w3.org/TR/html4/struct/lists.html#h-10.2
 */
class AnewtXHTMLListItem extends _AnewtXHTMLElement
{
	public $node_name = 'li';
}


/* Definition lists */

/**
 * Definition list element.
 *
 * This element may contain AnewtXHTMLDefinitionTerm and
 * AnewtXHTMLDefinitionDescription instances, usually grouped in pairs though
 * multiple defitions may be given for a single term.
 *
 * \see AnewtXHTMLDefinitionTerm
 * \see AnewtXHTMLDefinitionDescription
 * \see http://www.w3.org/TR/html4/struct/lists.html#h-10.3
 */
class AnewtXHTMLDefinitionList extends _AnewtXHTMLListBase
{
	public $node_name = 'dl';
	protected $allowed_elements = array('dt', 'dd');
	protected $allows_text = false;
}


/**
 * Definition term element.
 *
 * Instances should be added to an AnewtXHTMLDefinitionList instance.
 *
 * \see AnewtXHTMLDefinitionList
 * \see AnewtXHTMLDefinitionDescription
 * \see http://www.w3.org/TR/html4/struct/lists.html#h-10.3
 */
class AnewtXHTMLDefinitionTerm extends _AnewtXHTMLElement
{
	public $node_name = 'dt';
}


/**
 * Definition description element.
 *
 * Instances should be added to an AnewtXHTMLDefinitionList instance.
 *
 * \see AnewtXHTMLDefinitionList
 * \see AnewtXHTMLDefinitionTerm
 * \see http://www.w3.org/TR/html4/struct/lists.html#h-10.3
 */
class AnewtXHTMLDefinitionDescription extends _AnewtXHTMLElement
{
	public $node_name = 'dd';
}

?>
