<?php

/*
 * Anewt, Almost No Effort Web Toolkit, renderer/grid module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


anewt_include('renderer');


/**
 * Renders data grids as XHTML in a fancy way.
 *
 * Some features:
 *
 * - Row headers
 * - Zebra-pattern row styling
 * - Column highlighting
 * - Custom cell renderers for the columns to allow custom cell rendering like
 *   hyperlinked grid cells or lazy value retrieval from Container objects.
 * - Multiple cell renderers per column to allow for advanced column views.
 * - A summary row that can be used to display some text.
 *
 * These properties can be set to change the behaviour:
 *
 * - <code>show-header</code>:
 *   Whether to show a table header row (boolean, default: false)
 * - <code>show-summary</code>:
 *   Whether to show a summary line in the footer of the table (boolean,
 *   default: false)
 * - <code>summary-text</code>:
 *   Text on the summary line
 * - <code>class</code>
 *   Extra CSS class name for the <code>table</code> tag.
 * - <code>generator</code>:
 *   Optional AnewtGenerator instance for generating row class names. By default
 *   'odd' and 'even' will be used.
 */
class AnewtGridRenderer extends Renderer
{
	/* Static methods */

	/**
	 * \private \static
	 *
	 * Sort function that sorts AnewtGridColumns based on their 'order'
	 * property. This method is used as a callback for usort()
	 *
	 * \param $col1   AnewtGridColumn 1
	 * \param $col2   AnewtGridColumn 2
	 *
	 * \return        An integer than can be used for usort()
	 */
	static function _cmp_column_order($col1, $col2)
	{
		return $col1->_get('order') - $col2->_get('order');
	}


	/* Instance methods and variables */

	/**
	 * Construct a new AnewtGridRenderer instance.
	 */
	function __construct()
	{
		/* Set defaults */
		$this->_seed(array(
			'show-header'  => true,
			'show-summary' => false,
			'summary-text' => '',
			'class'        => null,
			'generator'    => null,
		));
	}


	/* Column-related methods */

	var $_columns = array(); /**< \private List of columns */

	/**
	 * Add a column to the grid. If no custom AnewtGridCellRenderer instances
	 * were previously added to this column, a default AnewtGridCellRenderer is
	 * created and used, resulting in a single string cell, which is the common
	 * (simple) case.
	 *
	 * \param $column
	 *   A AnewtGridColumn instance.
	 *
	 * \see AnewtGridColumn
	 */
	function add_column($column)
	{
		assert('$column instanceof AnewtGridColumn');
		$column_id = $column->id;

		assert('!array_has_key($this->_columns, $column_id); // Column already added');

		/* Add a default AnewtGridCellRenderer with the same id as the column if
		 * the column has no renderers. This simplifies the common
		 * only-a-single-cell-renderer-per-column case */
		if ($column->_n_cell_renderers() == 0)
		{
			$cell_renderer = new AnewtGridCellRenderer($column_id);
			$column->add_cell_renderer($cell_renderer);
		}

		/* Default ordering */
		if (is_null($column->_get('order')))
			$column->_set('order', (count($this->_columns) + 1) * 10);

		$this->_columns[$column_id] = $column;
	}

	/**
	 * Add a counter column to the grid. This is a convenience function that
	 * adds a column with id <code>count</code> and a AnewtCountGridCellRenderer
	 * with id <code>count</code> as the first column in the grid.
	 *
	 * \param $header_text
	 *   The header text to use (optional, defaults to empty string; leave null
	 *   to use default)
	 *
	 * \param $initial_value
	 *   The initial value (optional, defaults to standard numbering; leave null
	 *   to use default)
	 */
	function add_count_column($header_text=null, $initial_value=null)
	{
		if (is_null($header_text))
			$header_text = '';

		assert('is_string($header_text)');

		/* Force the count column to be the leftmost column by giving it a very
		 * negative 'order' property. */
		$order = -100;
		$column = new AnewtGridColumn('count', $header_text, $order);
		$column->add_cell_renderer(new AnewtGridCellRendererCount('count', $initial_value));
		$this->add_column($column);
	}

	/**
	 * Make sure a column is shown in the output. Column visibility defaults to
	 * true, but might have been disabled before.
	 *
	 * \param $column_id
	 *   The id of the column.
	 */
	function show_column($column_id)
	{
		assert('is_string($column_id)');
		assert('array_has_key($this->_columns, $column_id); // Invalid column id');
		$this->_columns[$column_id]->_set('visible', true);
	}

	/**
	 * Make sure a column is hidden from the output. Hidden columns will not be
	 * rendered, so any expensive getter operations will not be executed (this
	 * is a considerable speedup in some cases).
	 *
	 * \param $column_id
	 *   The id of the column.
	 */
	function hide_column($column_id)
	{
		assert('is_string($column_id)');
		assert('array_has_key($this->_columns, $column_id); // Invalid column id');
		$this->_columns[$column_id]->_set('visible', false);
	}

	/**
	 * Mark a single column for highlighting. This method optionally disables
	 * the highlight property of all other columns.
	 *
	 * \param $column_id
	 *   The id of the column.
	 *
	 * \param $unhighlight_others
	 */
	function highlight_column($column_id, $unhighlight_others=true)
	{
		assert('is_string($column_id)');
		assert('is_bool($unhighlight_others)');
		assert('array_has_key($this->_columns, $column_id); // Invalid column id');

		/* Iterate over all columns and (un)set highlight property */
		if ($unhighlight_others)
		{
			foreach (array_keys($this->_columns) as $column_id_to_unhighlight)
			{
				$this->_columns[$column_id_to_unhighlight]->_set('highlight', false);
			}
		}

		/* Now enable highlighting on the specified column */
		$this->_columns[$column_id]->_set('highlight', true);
	}

	/**
	 * Return a list of columns that are visible using the current settings.
	 *
	 * \return
	 *   A list of column objects.
	 */
	function visible_columns()
	{
		$columns = array();

		/* Get all visible columns */
		foreach (array_keys($this->_columns) as $column_id)
		{
			if ($this->_columns[$column_id]->_get('visible'))
				$columns[] = $this->_columns[$column_id];
		}

		/* Order the columns based on the 'order' property */
		usort($columns, array(__CLASS__, '_cmp_column_order'));

		return $columns;
	}

	/**
	 * \private
	 *
	 * Calculate the total number of XHTML columns that will be used to render
	 * the output. This depends on the number of cell renderers in each column.
	 *
	 * \return
	 *   A number indicating the number of visible columns.
	 */
	function _number_of_expanded_visible_columns()
	{
		$columns = $this->visible_columns();
		$result = 0;
		foreach ($columns as $column)
		{
			$result += $column->_n_cell_renderers();
		}

		return $result;
	}


	/* Row-related methods */

	var $_rows = array();    /**< \private List of data rows */

	/**
	 * Add a row of data to the grid. The data can be anything except null.
	 * However, if you want to use default renderers (those suffice for most
	 * simple cases), you should use a Container object or an associative array
	 * for the $data parameter, since those can be rendered by the default
	 * AnewtGridCellRenderer using the id of the cell renderer as the property
	 * name of the object or as the key of the array.
	 *
	 * \param $row
	 *   Any value representing the row data (but not null).
	 *
	 * \see AnewtGridCellRenderer::render_cell
	 * \see AnewtGridRenderer::add_row
	 * \see AnewtGridRenderer::set_rows
	 */
	function add_row($row)
	{
		assert('!is_null($row); // row data cannot be null');
		$this->_rows[] = $row;
	}

	/**
	 * Convenience function to add a bunch of rows at once.
	 * 
	 * \param $rows
	 *   Array of row data
	 *
	 * \see AnewtGridRenderer::add_row
	 * \see AnewtGridRenderer::set_rows
	 */
	function add_rows($rows)
	{
		assert('is_numeric_array($rows); // row data should be a list');
		foreach (array_keys($rows) as $row_key)
		{
			assert('!is_null($rows[$row_key]); // row data cannot be null');
			$this->_rows[] = $rows[$row_key];
		}
	}

	/**
	 * Set rows to the provided list. This means all existing rows will be
	 * discarded. This method does not check the rows for null values (which are
	 * not allowed).
	 *
	 * \param $rows
	 *   Array of row data
	 *
	 * \see AnewtGridRenderer::add_row
	 * \see AnewtGridRenderer::add_rows
	 */
	function set_rows($rows)
	{
		assert('is_numeric_array($rows); // row data should be a list');
		$this->_rows = $rows;
	}


	/* Rendering */

	/**
	 * Render the grid into a valid XHTML snippet.
	 *
	 * \return
	 *   XHTML string that can be used directly for output
	 */
	function render()
	{
		/* Create the table */

		$table = new AnewtXHTMLTable();
		$table->set_class('grid');

		$extra_class = $this->_get('class');
		if (!is_null($extra_class))
			$table->add_class($extra_class);


		/* Which columns do we need to process? */

		$visible_columns = $this->visible_columns();
		$number_of_expanded_visible_columns = $this->_number_of_expanded_visible_columns();


		/* Render colgroup and col elements */

		foreach ($visible_columns as $column)
		{
			$column_group = new AnewtXHTMLTableColumnGroup();
			$column_group->set_class($column->id);

			$cell_renderers = $column->cell_renderers();
			foreach ($cell_renderers as $cell_renderer)
				$column_group->append_child(new AnewtXHTMLTableColumn(null, array('class' => $cell_renderer->id)));

			$table->append_child($column_group);
			unset($column_group);
		}


		/* Render the header */

		if ($this->_get('show-header'))
		{
			$table_head = new AnewtXHTMLTableHead();
			$header_cells = new AnewtXHTMLFragment();
			$sub_header_cells = new AnewtXHTMLFragment();

			/* Find out in advance whether we need to show a subheader (a second
			 * row of header cells with cellrenderer titles instead of column
			 * titles.) */

			$show_sub_header = false;
			foreach ($visible_columns as $column)
			{
				foreach ($column->cell_renderers() as $cell_renderer)
				{
					$cell_renderer_title = $cell_renderer->_get('title');
					if (!is_null($cell_renderer_title))
					{
						/* Okay, there is at least one cell renderer with
						 * a non-empty title. */
						$show_sub_header = true;
						break 2;
					}
				}
			}

			foreach ($visible_columns as $column)
			{
				$column_title = $column->_get('title');
				$header_cell = new AnewtXHTMLTableHeaderCell($column_title);

				/* Allow CSS styling and highlighting on a column level */

				$header_cell->set_class(sprintf('column-%s', $column->id));

				if ($column->_get('highlight'))
					$header_cell->add_class('highlight');

				$cell_renderers = $column->cell_renderers();
				$n_cell_renderers = count($cell_renderers);


				/* If we need to show a subheader row, we loop over the cell
				 * renderers and use their titles, if set. If no subheader is
				 * shown anyway, we jus skip over this part. */

				$column_has_sub_header_titles = false;
				if ($show_sub_header)
				{
					$sub_header_cells_for_column = new AnewtXHTMLFragment();
					foreach ($cell_renderers as $cell_renderer)
					{
						$cell_title = $cell_renderer->_get('title');
						$sub_header_cell = new AnewtXHTMLTableHeaderCell($cell_title);

						$sub_header_cell->set_class(sprintf('column-%s cell-%s', $column->id, $cell_renderer->id));
						if ($column->_get('highlight'))
							$sub_header_cell->add_class('highlight');

						$sub_header_cells_for_column->append_child($sub_header_cell);

						if (strlen($cell_title) > 0)
							$column_has_sub_header_titles = true;

						unset ($sub_header_cell);
					}


					/* Only add the sub header cells if there are any. Otherwise
					 * we set a row span to make the header span two rows, since
					 * other rows use the second row to display the sub header
					 * cells. */

					if ($column_has_sub_header_titles)
						$sub_header_cells->append_child($sub_header_cells_for_column);
					else
						$header_cell->set_attribute('rowspan', (string) 2);
				}


				/* We need to set a colspan if the column contains multiple cell
				 * renderers, because each cell renderer results in a td element
				 * in the final output, and the header should span all of the
				 * child cells. */

				if ($n_cell_renderers >= 2)
					$header_cell->set_attribute('colspan', (string) $n_cell_renderers);

				$column_title = $column->_getdefault('title', '');

				$header_cells->append_child($header_cell);

				unset($header_cell);
			}


			/* Now add the rows to the header. FIXME: should create
			 * AnewtXHTMLTableHead early on instead. */

			$table_head->append_child(new AnewtXHTMLTableRow($header_cells));

			if ($show_sub_header)
				$table_head->append_child(new AnewtXHTMLTableRow($sub_header_cells));

			$table->append_child($table_head);
		}


		/* Grid summary text in table foot */

		if ($this->_get('show-summary'))
		{
			$table_foot = new AnewtXHTMLTableFoot();
			$table_foot->append_child(new AnewtXHTMLTableRow(new AnewtXHTMLTableCell(
				$this->_get('summary-text'),
				array(
					'class' => 'summary',
					'colspan' => (string) $number_of_expanded_visible_columns,
				)
			)));
			$table->append_child($table_foot);
		}


		/* Make alternating row colors possible (zebra pattern) */

		$generator = $this->get('generator');
		if (is_null($generator))
		{
			anewt_include('generator');
			$generator = new AnewtGenerator('odd', 'even');
		}
		assert('$generator instanceof AnewtGenerator');


		/* Render the row data itself */

		$table_body = new AnewtXHTMLTableBody();
		foreach ($this->_rows as $data)
		{
			/* This array will hold the rendered cells */
			$cells = array();

			/* Iterate over all visible columns of the Grid */

			foreach (array_keys($visible_columns) as $column_id)
			{
				$column = $visible_columns[$column_id];

				/* Iterate over the cell renderers */
				$cell_renderers = $column->cell_renderers();
				foreach (array_keys($cell_renderers) as $cell_renderer_key)
				{
					$rendered_cell = $cell_renderers[$cell_renderer_key]->render_cell($data);
					$cells[] = $rendered_cell;
				}
				
			}

			$row_attr = array(
				'class' => $generator->next(),
			);

			$table_body->append_child(new AnewtXHTMLTableRow($cells, $row_attr));
		}
		$table->append_child($table_body);

		return $table;
	}
}

?>
