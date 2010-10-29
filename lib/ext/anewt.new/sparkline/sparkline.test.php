<?php

require_once '../anewt.lib.php';

anewt_include('sparkline');

$values = array(5, 6, 8, 9);

/**
 * Calculate the mean of a series of numbers.
 */
function mean($values) {
	assert('is_numeric_array($values)');
	return array_sum($values) / count($values);
}

function variance($values) {
	$mean = mean($values);
	$num = count($values);

	$x = 0;
	foreach ($values as $value) {
		$diff = $value - $mean;
		$x += $diff * $diff;
	}
	$variance = ($x) / $num;
	return $variance;
}

function stddev($values) {
	return sqrt(variance($values));
}

header('Content-type: text/plain');
var_dump(join(', ', $values));
var_dump(mean($values));
var_dump(variance($values));
var_dump(stddev($values));

?>
