<?php

error_reporting(E_ALL | E_STRICT);
require_once '../anewt.lib.php';

anewt_include('sparkline');
anewt_include('sparkline/line');

$sl = &new AnewtSparklineImageLine();

$sl->set('debug-resize-factor', 8);

$sl->set('background-color', $sl->color_from_string('#ff0'));

$sl->set('draw-zero-axis', true);

$sl->set('image-border', 3, 3);
$sl->set('point-spacing', 2);

$sl->set('value-scale', 1);

$values = range(-7, 7);
for ($i = 0; $i < 30; $i++) {
	$values[] = rand(-7, 7);
}

$sl->set('values', $values);

$sl->flush_png();

?>
