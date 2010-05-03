<?php

error_reporting(E_ALL | E_STRICT);
require_once '../anewt.lib.php';


/* test is_numeric_array and is_assoc_array */

function dump_results($a) {
	print_r($a);
	is_numeric_array($a)
		? printf("%s\n", 'This array is numeric')
		: printf("%s\n", 'This array is not numeric');
	is_assoc_array($a)
		? printf("%s\n", 'This array is associative')
		: printf("%s\n", 'This array is not associative');
	print "\n\n";
}

$data = array('a', 'b', 'c');
assert('true === is_numeric_array($data)');
assert('true === is_numeric_array($data, true)');
assert('false === is_assoc_array($data)');
assert('false === is_assoc_array($data, true)');

$data = array(1, 2, 3);
assert('true === is_numeric_array($data)');
assert('true === is_numeric_array($data, true)');
assert('false === is_assoc_array($data)');
assert('false === is_assoc_array($data, true)');

$data = array('a' => 'aa', 'b' => 'bb', 'c' => 'cc');
assert('false === is_numeric_array($data)');
assert('true === is_assoc_array($data)');

$data = array('a', 'b' => 'bb', 'c' => 'cc');
$data['d'] = 'dd';
assert('true === is_numeric_array($data)');
assert('false === is_numeric_array($data, true)');
assert('false === is_assoc_array($data)');
assert('true === is_assoc_array($data, true)');

$data = array('a' => 'aa', 'b', 'c');
$data['d'] = 'dd';
assert('false === is_numeric_array($data)');
assert('false === is_numeric_array($data, true)');
assert('true === is_assoc_array($data)');
assert('true === is_assoc_array($data, true)');



/* test array_has_key and array_has_value */

$data = array(
		'first' => 'one',
		'second' => 'two',
		'third' => 'three'
		);
assert('true === array_has_key($data, "first")');
assert('false === array_has_key($data, "foo")');
assert('false === array_has_key($data, "one")');
assert('true === array_has_value($data, "three")');
assert('false === array_has_value($data, "foo")');
assert('false === array_has_value($data, "first")');


/* test array_unset_key */
$data = array(
		'first' => 'one',
		'second' => 'two',
		'third' => 'three'
		);
array_unset_key($data, 'first');
assert('false === array_has_key($data, "first")');
assert('true === array_has_key($data, "third")');
array_unset_key($data, 'third');
assert('false === array_has_key($data, "third")');
array_unset_key($data, 'notpresent');
assert('false === array_has_key($data, "notpresent")');


/* test array_unset_keys */

$data = array(
		'first' => 'one',
		'second' => 'two',
		'third' => 'three'
		);
$keys_to_remove = array ('first', 'second', 'notpresent');
array_unset_keys($data, $keys_to_remove);
assert('false === array_has_key($data, "first")');
assert('false === array_has_key($data, "second")');
assert('true === array_has_key($data, "third")');
assert('false === array_has_key($data, "notpresent")');


/* test array_clear */

$data = array(
		'first' => 'one',
		'second' => 'two',
		'third' => 'three'
		);
array_clear($data);
assert('count($data) == 0');


/* test array_flip_string_keys */
$data = array(
		'first' => 'one',
		'second' => 'two',
		'third' => 'three',
		4 => 'four',
		5 => 'five',
		'six' => 6,
		);
$data = array_flip_string_keys($data);
assert('true === array_has_key($data, "one")');
assert('true === array_has_key($data, "two")');
assert('false === array_has_key($data, "first")');
assert('true === array_has_key($data, 4)');
assert('true === array_has_key($data, 6)');
assert('false === array_has_key($data, "six")');
$data = array_flip_string_keys($data);
assert('true === array_has_key($data, 6)');
assert('true === array_has_key($data, "first")');


/* test array_check_types */
$data = array('foo', 1, '2', true, 'bar', array());
assert('!array_check_types($data, "siibsa", false)');
assert('array_check_types($data, "siibsa")');
assert('!array_check_types($data, "a")');
assert('!array_check_types($data, "abc")');

/* test require_args */
require_args($data, 'siibsa'); // should not throw an error

/* test array_trim_strings */

$data = array('foo  ', '  bar', '---foo---', 'bar---  ');

$expected = array('foo', 'bar', '---foo---', 'bar---');
$result = array_trim_strings($data);
assert('$result === $expected');

$expected = array('foo  ', '  bar', 'foo', 'bar---  ');
$result = array_trim_strings($data, '-');
assert('$result === $expected');

$expected = array('foo', 'bar', 'foo', 'bar');
$result = array_trim_strings($data, ' -');
assert('$result === $expected');

/* test array_get_int */
$data = array(
		'first' => 1,
		'second' => '2',
		'third' => 'drei',
		4 => 4);
assert('array_get_int($data, "first") == 1');
assert('array_get_int($data, "first", 2) == 1');
assert('array_get_int($data, "notfound", 3) == 3');
assert('array_get_int($data, "third", 33) == 33');
assert('array_get_int($data, "4") == 4');
assert('array_get_int($data, 4) == 4');

/* test numeric_array_to_associative_array */
$x = numeric_array_to_associative_array('one', 2, 3, 'data');
$y = numeric_array_to_associative_array(array('one', 2, 3, 'data'));
assert('count($x) == 2');
assert('array_has_key($x, "one")');
assert('array_has_key($x, 3)');
assert('!array_has_key($x, 2)');
assert('!array_has_key($x, "data")');
assert('count($y) == 2');
assert('array_has_key($y, "one")');
assert('array_has_key($y, 3)');
assert('!array_has_key($y, 2)');
assert('!array_has_key($y, "data")');

/* test natksort */
$data = array('a1'=>1, 'a20'=>2, 'a2'=>3);
$expected = array('a1'=>1, 'a2'=>3, 'a20'=>2);
natksort($data);
assert('$data === $expected');

?>
