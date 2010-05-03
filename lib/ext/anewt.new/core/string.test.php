<?php

error_reporting(E_ALL | E_STRICT);
require_once '../anewt.lib.php';


/* Test str_contains */
assert("str_contains ('foobar', 'foo')     === true   ");
assert("str_contains ('foobar', 'oob')     === true   ");
assert("str_contains ('foobar', 'ba')      === true   ");
assert("str_contains ('foobar', 'ab')      === false  ");
assert("str_contains ('foobar', 'bar ')    === false  ");
assert("str_contains ('foobar', 'baz')     === false  ");
assert("str_contains ('foobar', '')        === true   ");
assert("str_contains ('', '')              === true   ");
assert("str_contains ('', 'foo')           === false  ");

/* Test str_is_whitespace */
assert("str_is_whitespace (' ')            === true  ");
assert("str_is_whitespace ('x')            === false ");
assert("str_is_whitespace (' a')           === false ");
assert("str_is_whitespace ('')             === true  ");
assert('str_is_whitespace ("\n\t")         === true  ');

/* Test str_has_prefix */
assert("str_has_prefix ('foobar', 'foo')     === true   ");
assert("str_has_prefix ('foobar', 'fo' )     === true   ");
assert("str_has_prefix ('foobar', 'bar')     === false  ");
assert("str_has_prefix ('foobar', 'baz')     === false  ");
assert("str_has_prefix ('foo', 'longstring') === false  ");
assert("str_has_prefix ('foo', '')           === true   ");
assert("str_has_prefix ('', '')              === true   ");
assert("str_has_prefix ('', 'foo')           === false  ");
assert("str_has_prefix ('f', 'f')            === true   ");

/* Test str_has_whitespace_prefix */
assert('str_has_whitespace_prefix ("  foo")     === true   ');
assert('str_has_whitespace_prefix ("\t asd")    === true   ');
assert('str_has_whitespace_prefix ("a ds ")     === false  ');
assert('str_has_whitespace_prefix ("\naf ")     === true   ');

/* Test str_has_suffix */
assert("str_has_suffix   ('foobar', 'bar')     === true   ");
assert("str_has_suffix   ('foobar', 'ar' )     === true   ");
assert("str_has_suffix   ('foobar', 'foo')     === false  ");
assert("str_has_suffix   ('foobar', 'baz')     === false  ");
assert("str_has_suffix   ('foobar', 'sfoobar') === false  ");
assert("str_has_suffix   ('/test/path/', '/')  === true   ");
assert("str_has_suffix   ('1212121212', '12')  === true   ");
assert("str_has_suffix   ('1212121212', '21')  === false  ");
assert("str_has_suffix   ('foobar', '')        === true   ");
assert("str_has_suffix ('f', 'f')              === true   ");

/* Test str_has_whitespace_suffix */
assert('str_has_whitespace_suffix ("foo  ")    === true   ');
assert('str_has_whitespace_suffix ("\t asd\t") === true   ');
assert('str_has_whitespace_suffix (" a d s")   === false  ');
assert('str_has_whitespace_suffix ("af\n")     === true   ');

/* Test str_strip_prefix */
assert("str_strip_prefix('foobar', 'foo') === 'bar'");
assert("str_strip_prefix('foobar', 'bar') === 'foobar'");
assert("str_strip_prefix('foobar', 'fooz') === 'foobar'");
assert("str_strip_prefix('bazbaz', 'ba') === 'zbaz'");
assert("str_strip_prefix('foobar', '') === 'foobar'");
assert("str_strip_prefix('a', 'a') === ''");
assert("str_strip_prefix('foo', 'foo') === ''");

/* Test str_strip_suffix */
assert("str_strip_suffix('foobar', 'foo') === 'foobar'");
assert("str_strip_suffix('foobar', 'bar') === 'foo'");
assert("str_strip_suffix('foobar', 'baz') === 'foobar'");
assert("str_strip_suffix('bazbaz', 'az') === 'bazb'");
assert("str_strip_suffix('foobar', '') === 'foobar'");
assert("str_strip_suffix('a', 'a') === ''");
assert("str_strip_suffix('foo', 'foo') === ''");

/* Test str_truncate */
$testcases = array(
		array('12345',         7,    null,     null,      '12345'),
		array('12345 7890',    5,    null,     null,      '12...'),
		array('12345 7890',    5,    '..',     null,      '123..'),
		array('12345 7890',    9,    null,     null,      '12345...'),
		array("12345 \t foo ", 8,    null,     null,      '12345...'),
		array('12345 7890',    10,   null,     null,      '12345 7890'),
		array('1234567890',    6,    '',       false,     '123456'),
		array('123 56 890',    7,    '',       null,      '123 56'),
		array('1234567890',    8,    null,     null,      '12345...'),
		);
foreach ($testcases as $testcase) {
	list ($input, $length, $trail, $words, $expected) = $testcase;
	$output = str_truncate($input, $length, $trail, $words);
	assert('$output === $expected');
	assert('strlen($output) <= strlen($input)');
}

/* Test str_first_non_white */
assert('str_first_non_white() === ""');
assert('str_first_non_white("foo", "bar") === "foo"');
assert('str_first_non_white("", "  ", "	", "foo", "   ") === "foo"');
assert('str_first_non_white(array("", " ", "foo", "")) === "foo"');
assert('str_first_non_white("", null, "   ") === ""');
assert('str_first_non_white(array(null, null, "foo", "")) === "foo"');

/* Test str_all_non_white */
$input = array(' ', 'foo', "\n", 'bar');
$output = str_all_non_white($input);
assert('count($output) == 2');
assert('$output[0] == "foo"');
assert('$output[1] == "bar"');

/* Test implode_newlines */
assert('implode_newlines(array("a", "b")) === "a\nb"');

/* Test implode_spaces */
assert('implode_spaces(array("a", "b", "foo")) === "a b foo"');

/* Test str_amputate */
$data = array(
		'nothing' => 'nothing',
		'&' => '&amp;',
		'a & b &c' => 'a &amp; b &amp;c',
		'H&eacute;' => 'H&eacute;',
		'H&eacute;&' => 'H&eacute;&amp;',
		'stresstest&amp;&eacute;&&&amp;' => 'stresstest&amp;&eacute;&amp;&amp;&amp;',
		"multiline\n&&eacute; &\nmulti&line" => "multiline\n&amp;&eacute; &amp;\nmulti&amp;line",
		'&&#x2606;&a' => '&amp;&#x2606;&amp;a',
		'&&#x260A;&a' => '&amp;&#x260A;&amp;a',
		'&&#123;&a' => '&amp;&#123;&amp;a',
		);
foreach ($data as $input => $expected_output) {
	$real_output = str_amputate($input);
	if ($real_output !== $expected_output)
		printf(
			"'%s' gives '%s' while '%s' was expected\n",
			$input,
			$real_output,
			$expected_output);

	assert('$real_output === $expected_output');
}

/* Test to_string */
class A { function render()    { return 'foo';                       } }
class B { function to_string() { return array(1, "foo", 1.2, false); } }
$a = new A();
$b = new B();
assert('to_string("foo") === "foo"');
assert('to_string(12) === "12"');
assert('to_string(1.2) === "1.2"');
assert('to_string(array("foo", "bar")) === "foo\nbar"');
assert('to_string($a) === "foo"');
assert('to_string($b) === "1\nfoo\n1.2\n0"');
assert('to_string($a, "foo") === "foo\nfoo"');

?>
