<?php

error_reporting(E_ALL | E_STRICT);
require_once '../anewt.lib.php';
anewt_include('core/aasprintf');

/* Test aasprintf */

$data = array(
		'one' => 'first',
		'two' => 'second',
		'three' => 'third',
		);

assert('aasprintf("%(one)s %(two)s %(three)2s", $data) === "first second third"');

?>
