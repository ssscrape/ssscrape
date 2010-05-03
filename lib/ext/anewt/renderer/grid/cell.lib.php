<?php

/*
 * Anewt, Almost No Effort Web Toolkit, renderer/grid module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * A cell renderer for the AnewtGridRenderer. This grid renderer just renders the
 * data as a cell (without any special effects).
 */
class AnewtGridCellRenderer extends Container
{
	var $id;      /**< The id of this cell renderer */
	var $_column; /**< \private Reference to the parent column */

	/**
	 * Constructs a new AnewtGridCellRenderer.
	 *
	 * \param $id
	 *   The id for this cell renderer. It will be used to match against the row
	 *   data in the grid.
	 */
	function __construct($id)
	{
		assert('is_string($id)');
		$this->id = $id;

		$this->_seed(array(
			'title' => null,
		));
	}

	/**
	 * Renders the cell.
	 *
	 * \param $data
	 *   Data to pack into the cell
	 *
	 * \return
	 *   Rendered cell
	 */
	function render_cell($data)
	{
		$value = $this->fetch_value_from_data($data);
		return $this->create_td($value);
	}

	/**
	 * \protected
	 *
	 * Create a XHTML cell based on the provided value. This method handles
	 * a variety of data types easibly convertible to strings, and takes the
	 * <code>highlight</code> property of the AnewtGridColumn into account when
	 * rendering into a HTML <code>&lt;td&gt;</code> table cell.
	 * You can use this method to render the final result in custom cell
	 * renderers (after doing done your custom formatting based on the row
	 * data).
	 *
	 * \param $value
	 *   Value to render
	 *
	 * \return
	 *   XHTML snippet for a table cell
	 */
	function create_td($value)
	{
		$cell = new AnewtXHTMLTableCell($value);

		/* Allow CSS styling and highlighting */
		$class = sprintf('column-%s cell-%s', $this->_column->id, $this->id);
		$cell->add_class($class);

		if ($this->_column->_get('highlight'))
			$cell->add_class('highlight');

		return $cell;
	}


	/**
	 * \protected
	 *
	 * Fetch default value from the row data, based on the cell renderer id.
	 * This method is used internally to fetch the cell data from the row data
	 * object. Both associative arrays and Container objects are handled. Which
	 * value is retrieved depends on the <code>id</code> of the cell renderer;
	 * if you did not instantiate an AnewtGridCellRenderer yourself, this is
	 * will be the same as the column <code>id</code>.
	 *
	 * Custom AnewtGridCellRenderer subclasses may use this method as
	 * a convenience method to get data from the row object prior to further
	 * processing or formatting.
	 *
	 * \param $data
	 *   Row data
	 *
	 * \return
	 *   Value extracted from row data, based on the <code>id</code>
	 */
	function fetch_value_from_data($data)
	{
		/* Handle arrays, */
		if (is_assoc_array($data))
			$value = array_get_default($data, $this->id, null);

		/* Containers, */
		elseif ($data instanceof Container)
			$value = $data->getdefault($this->id, null);

		/* ... but nothing else */
		else
			trigger_error('AnewtGridCellRenderer::render_cell(): This cell renderer can only render associative arrays and Container objects.', E_USER_ERROR);

		return $value;
	}
}

/**
 * Hyperlink cell renderer for hyperlinks for the AnewtGridRenderer. This cell
 * renderer renders data into hyperlink cells. To use this cell renderer, the
 * field should contain a (name, url) tuple.
 */
class AnewtGridCellRendererHyperlink extends AnewtGridCellRenderer
{
	/**
	 * Renders the cell.
	 *
	 * \param $data
	 *   Data to pack into the cell
	 *
	 * \return
	 *   Rendered cell
	 */
	function render_cell($data)
	{
		$value = $this->fetch_value_from_data($data);
		assert('is_numeric_array($value)');
		assert('count($value) == 2');

		list ($name, $url) = $value;

		assert('is_string($name)');
		assert('is_string($url)');
		
		return $this->create_td(ax_a_href($name, $url));
	}
}


/**
 * Cell renderer that just prints the row number. You can use this for the first
 * column to display a counter. Most of the times you will not need to use this
 * class directly, since AnewtGridRenderer::add_count_column covers most use
 * cases.
 *
 * \see AnewtGridRenderer::add_count_column
 */
class AnewtGridCellRendererCount extends AnewtGridCellRenderer
{
	var $_value; /**< \private Row counter value */

	/**
	 * Construct new AnewtGridCellRendererCount instance.
	 *
	 * \param $id
	 *   Cell renderer id
	 *
	 * \param $initial_value
	 *   The initial value for the counter. Defaults to 1, but can be changed
	 *   e.g. paginated grid listings.
	 */
	function AnewtGridCellRendererCount($id, $initial_value=null)
	{
		if (is_null($initial_value))
			$initial_value = 1;

		parent::__construct($id);
		assert('is_int($initial_value)');
		$this->_value = $initial_value;
	}

	/**
	 * Renders the cell.
	 *
	 * \param $data
	 *   Data to pack into the cell
	 *
	 * \return
	 *   Rendered cell
	 */
	function render_cell($data)
	{
		return $this->create_td($this->_value++);
	}

	/**
	 * Set the counter to another value.
	 *
	 * \param $new_value
	 *   The next value to use
	 */
	function set_value($new_value)
	{
		assert('is_int($new_value)');
		$this->_value = $new_value;
	}
}


/**
 * Cell render to render date and time data. This cell renderer renders
 * AnewtDateTimeAtom instances into grid cells using the provided date
 * formatting string.
 */
class AnewtGridCellRendererDate extends AnewtGridCellRenderer
{
	var $_format = null; /**< \private Date formatting string */

	/**
	 * Construct new AnewtGridCellRendererDate instance.
	 *
	 * \param $id
	 *   Cell renderer id
	 *
	 * \param $format
	 *   Date formatting string in strftime syntax
	 */
	function AnewtGridCellRendererDate($id, $format='%c')
	{
		parent::__construct($id);
		assert('is_string($format)');
		$this->_format = $format;
	}

	/**
	 * Render data as formatted date.
	 *
	 * \param $data
	 *   Data to pack into the cell
	 *
	 * \return
	 *   Rendered cell
	 */
	function render_cell($data)
	{
		$dt = $this->fetch_value_from_data($data);
		if (is_null($dt)) {
			$str = null;
		} else {
			assert('$dt instanceof AnewtDateTimeAtom;');
			$str = AnewtDateTime::format($this->_format, $dt);
		}
		return $this->create_td($str);
	}
}
