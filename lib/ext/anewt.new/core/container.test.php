<?php

error_reporting(E_ALL | E_STRICT);
require_once '../anewt.lib.php';


/* Test constructor */

$c = new AnewtContainer(array('foo' => 'bar'));
assert('$c->get("foo") === "bar"');


/* Test clear() */

$c = new AnewtContainer();
$c->set('foo', 'foo_value');
$c->set('bar', 'bar_value');
$c->clear();



/* Test custom getters and setters */

class TestContainer extends AnewtContainer {
	function get_foo() {
		return 'foo_value';
	}

	function get_baz($param='default') {
		return 'baz_value_' . $param;
	}
}


$test = new TestContainer();
$test->set('bar', 'bar_value');


/* Test keys() */

$keys = $test->keys();
assert('array_has_value($keys, "bar") === true');
assert('array_has_value($keys, "foo") === true');
assert('array_has_value($keys, "baz") === true');

$keys = $test->_keys();
assert('array_has_value($keys, "bar") === true');
assert('array_has_value($keys, "foo") === false');
assert('array_has_value($keys, "baz") === false');


/* Test to_array() */

$test->set('foo', 'bla');

$data = $test->to_array(true);
assert('$data["foo"] === "foo_value"');
assert('$data["foo"] !== "wrong"');
assert('$data["bar"] === "bar_value"');
assert('$data["bar"] !== "wrong"');
assert('$data["baz"] === "baz_value_default"');
assert('$data["baz"] !== "baz_value"');

$data = $test->to_array(false);
assert('count($data) === 2');
assert('$data["bar"] === "bar_value"');
assert('$data["bar"] !== "wrong"');
assert('$data["foo"] !== "foo_value"');
assert('$data["foo"] === "bla"');


/* Test delete() */

$testdelete = new AnewtContainer();

assert('!$testdelete->is_set("bla")');
$testdelete->set('bla', 'woeiwoei');
assert('$testdelete->is_set("bla")');
$testdelete->delete('bla');
assert('!$testdelete->is_set("bla")');


/* Test underscores and dashes juggling */

$c = new AnewtContainer();
$c->set('foo_bar', 'baz');
assert('$c->get("foo-bar") === "baz"');
assert('$c->get("foo_bar") === "baz"');
$c->set('foo-bar', 'baz2');
assert('$c->get("foo-bar") === "baz2"');
assert('$c->get("foo_bar") === "baz2"');
$c->add('some-list', 'value');
$c->add('some_list', 'value');
assert('count($c->get("some-list")) === 2');


/* Test getdefault() and setdefault() */

$c = new AnewtContainer();

assert('$c->getdefault("foo", "defaultvalue") === "defaultvalue"');
$c->set('foo', 'anothervalue');
assert('$c->getdefault("foo", "defaultvalue") === "anothervalue"');

assert('$c->is_set("bar") === false');
$c->setdefault('bar', 'somevalue');
assert('$c->get("bar") === "somevalue"');
$c->set('bar', 'anothervalue');
assert('$c->get("bar") === "anothervalue"');
$c->setdefault('bar', 'somevalue'); /* should do nothing */
assert('$c->get("bar") === "anothervalue"');
$c->_set('foo', 'bar123');
assert('$c->_getdefault("bar") === "anothervalue"');
assert('$c->_getdefault("this-one-is-not-set", "the-answer") === "the-answer"');

?>
