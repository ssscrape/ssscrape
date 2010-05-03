<?php

error_reporting(E_ALL | E_STRICT);
require_once dirname(__FILE__) . '/../../anewt.lib.php';

anewt_include('page');
anewt_include('renderer/grid');

$grid = new AnewtGridRenderer();

$column = new AnewtGridColumn('col-2', 'Second column', 2);
$grid->add_column($column);

$column = new AnewtGridColumn('somecol', 'First column', 1);
$cell_renderer = new AnewtGridCellRenderer('col-1a', 'Column 1a');
$cell_renderer->set('title', 'Column 1a');
$column->add_cell_renderer($cell_renderer);
$cell_renderer = new AnewtGridCellRenderer('col-1b', 'Column 1b');
$cell_renderer->set('title', 'Column 1b');
$column->add_cell_renderer($cell_renderer);
$grid->add_column($column);

$rows = array(
	array(
		'col-1a' => 'r1c1a',
		'col-1b' => 'r1c1b',
		'col-2'  => 'r1c2',
	),
	array(
		'col-1a' => 'r2c1a',
		'col-1b' => 'r2c1b',
		'col-2'  => 'r2c2',
	),
);
$grid->set_rows($rows);

$grid->add_row(array(
	'col-1a' => 'r3c1a',
	/* No col-1b value */
	'col-2' => 'r3c2',
));

$p = new AnewtPage();
$p->set('title', 'Anewt Grid Renderer');
$p->append($grid);
$p->flush();

?>
