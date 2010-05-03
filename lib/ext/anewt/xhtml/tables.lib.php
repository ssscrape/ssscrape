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
 * Table element classes.
 *
 * See http://www.w3.org/TR/html4/struct/tables.html
 */


/**
 * Block element for tables.
 *
 * \see http://www.w3.org/TR/html4/struct/tables.html#h-11.2.1
 */
class AnewtXHTMLTable extends _AnewtXHTMLBlockElement
{
	public $node_name = 'table';
}

/* FIXME: AnewtXHTMLTableCaption? */


/* Row groups */

/**
 * Block element for a table head.
 *
 * \see http://www.w3.org/TR/html4/struct/tables.html#h-11.2.3
 */
class AnewtXHTMLTableHead extends _AnewtXHTMLBlockElement
{
	public $node_name = 'thead';
}


/**
 * Block element for a table foot.
 *
 * \see http://www.w3.org/TR/html4/struct/tables.html#h-11.2.3
 */
class AnewtXHTMLTableFoot extends _AnewtXHTMLBlockElement
{
	public $node_name = 'tfoot';
}


/**
 * Block element for a table body.
 *
 * \see http://www.w3.org/TR/html4/struct/tables.html#h-11.2.3
 */
class AnewtXHTMLTableBody extends _AnewtXHTMLBlockElement
{
	public $node_name = 'tbody';
}


/* Column groups */

/**
 * Block element for a column group
 *
 * \see http://www.w3.org/TR/html4/struct/tables.html#h-11.2.4
 */
class AnewtXHTMLTableColumnGroup extends _AnewtXHTMLBlockElement
{
	public $node_name = 'colgroup';
}

/**
 * Inline element for a column.
 *
 * \see http://www.w3.org/TR/html4/struct/tables.html#h-11.2.4
 */
class AnewtXHTMLTableColumn extends _AnewtXHTMLInlineElement
{
	public $node_name = 'col';
}


/* Table rows and cells */

/**
 * Block element for table rows.
 *
 * \see http://www.w3.org/TR/html4/struct/tables.html#h-11.2.5
 */
class AnewtXHTMLTableRow extends _AnewtXHTMLBlockElement
{
	public $node_name = 'tr';
}

/**
 * Inline element for table cells.
 *
 * \see http://www.w3.org/TR/html4/struct/tables.html#h-11.2.6
 */
class AnewtXHTMLTableCell extends _AnewtXHTMLInlineElement
{
	public $node_name = 'td';
}

/**
 * Inline element for table header cells.
 *
 * \see http://www.w3.org/TR/html4/struct/tables.html#h-11.2.6
 */
class AnewtXHTMLTableHeaderCell extends _AnewtXHTMLInlineElement
{
	public $node_name = 'th';
}

?>
