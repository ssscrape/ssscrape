<?php

/*
 * Anewt, Almost No Effort Web Toolkit, renderer/grid module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * A grid column for the AnewtGridRenderer. This is merely an object to hold
 * some column-related properties, as well as a list of AnewtGridCellRenderer
 * instances that do the actual rendering of data.
 */
class AnewtGridColumn extends Container
{
	var $id;                        /**< The id of this grid column */
	var $_cell_renderers = array(); /**< \private list of cell renderers */

	/**
	 * Constructs a new AnewtGridColumn.
	 *
	 * \param $column_id
	 *   A string id to use for this column. This string can be used afterwards
	 *   to show, hide or highlight the column.
	 *
	 * \param $title
	 *   A string that will be used in the header row as the title for this
	 *   column (optional).
	 *
	 * \param $order
	 *   The order of this column (optional, defaults to null). Usually you
	 *   won't need this. The default order is the order in which the columns
	 *   are added to the grid.
	 *
	 * \param $visible
	 *   Initial visibility for this column (optional). Usually you won't need
	 *   this, but it can be useful if you're using the same grid renderer for
	 *   different views on the same type of data.
	 */
	function __construct($column_id, $title=null, $order=null, $visible=null)
	{
		/* Set defaults */
		if (is_null($title))    $title = '';
		if (is_null($visible))  $visible = true;

		/* Sanity checks */
		assert('is_string($column_id)');
		assert('is_string($title)');
		assert('is_null($order) || is_int($order)');
		assert('is_bool($visible)');

		/* Store settings */
		$this->id = $column_id;
		$this->_seed(array(
			'title' => $title,
			'order' => $order,
			'visible' => $visible,
			'highlight' => false,
		));
	}

	/**
	 * Add a cell renderer to this column.
	 *
	 * \param $cell_renderer
	 *   A AnewtGridCellRenderer instance.
	 *
	 * \see AnewtGridCellRenderer
	 */
	function add_cell_renderer($cell_renderer)
	{
		assert('$cell_renderer instanceof AnewtGridCellRenderer');
		/* Back-reference to the column object (needed for highlighting when
		 * rendering */
		$cell_renderer->_column = $this;

		/* Use the cell renderer id as the key in the cellrender hash */
		$this->_cell_renderers[$cell_renderer->id] = $cell_renderer;
	}

	/**
	 * Return the specified cell renderer. If no name is given, the first cell
	 * renderer instance is returned.
	 *
	 * \param $cell_renderer_id
	 *   Name of the cell renderer (optional).
	 *
	 * \return
	 *   A AnewtGridCellRenderer instance
	 *
	 * \see AnewtGridCellRenderer
	 */
	function cell_renderer($cell_renderer_id=null)
	{
		/* Return the first cell_renderer if no id was specified */
		if (is_null($cell_renderer_id)) {
			$keys = array_keys($this->_cell_renderers);
			$cell_renderer_id = $keys[0];
		}
		assert('array_has_key($this->_cell_renderers, $cell_renderer_id); // cell renderer $cell_renderer_id does not exist');
		return $this->_cell_renderers[$cell_renderer_id];
	}

	/**
	 * Return all AnewtGridCellRenderer instances associated with this column.
	 *
	 * \return
	 *   Array of AnewtGridCellRenderer instances
	 *
	 * \see AnewtGridCellRenderer
	 */
	function cell_renderers()
	{
		return $this->_cell_renderers;
	}

	/**
	 * \private 
	 *
	 * Returns the number of cell renderers in this column.
	 *
	 * \return
	 *   The number of cell renderers.
	 *
	 * \see AnewtGridCellRenderer
	 */
	function _n_cell_renderers()
	{
		return count($this->_cell_renderers);
	}
}

?>
