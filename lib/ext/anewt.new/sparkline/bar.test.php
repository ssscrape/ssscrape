<?php

error_reporting(E_ALL | E_STRICT);
require_once '../anewt.lib.php';

anewt_include('sparkline');
anewt_include('sparkline/bar');

$sl = &new AnewtSparklineImageBar();

$sl->set('debug-resize-factor', 1);
$sl->set('debug-resize-factor', 4);

$sl->set('draw-zero-axis', true);

$sl->set('image-border', 1, 2);
$sl->set('bar-spacing', 1);
$sl->set('bar-width', 2);
//$sl->set('bar-height', 5);

$sl->set('value-scale', 1.2);
//$sl->set('max-value', 5);
//$sl->set('min-value', -5);


$values = range(-7, 7);

/* Random values */
for ($i = 0; $i < 30; $i++) {
	$values[] = rand(-7, 7);
}


$sl->set('values', $values);

$sl->flush_png();

?>
