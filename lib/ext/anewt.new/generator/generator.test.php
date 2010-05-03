<?php

error_reporting(E_ALL | E_STRICT);
require_once '../anewt.lib.php';

anewt_include('generator');


$g = new AnewtGenerator('first', 'second', 'third');

assert('$g->next() === "first";');
assert('$g->next() === "second";');
assert('$g->next() === "third";');
assert('$g->next() === "first";');


$g = new AnewtGenerator(array('first', 'second', 'third'));

assert('$g->next() === "first";');
assert('$g->next() === "second";');
assert('$g->next() === "third";');
assert('$g->next() === "first";');

?>
