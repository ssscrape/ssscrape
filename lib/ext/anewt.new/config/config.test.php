<?php

require_once '../anewt.lib.php';

anewt_include('config');

AnewtConfig::set('foo', 'bar');
AnewtConfig::set('sampleint', 2);

assert('AnewtConfig::get("foo") === "bar"');
assert('AnewtConfig::getdefault("foo", "baz") === "bar"');
assert('AnewtConfig::getdefault("nonexistent", "baz") === "baz"');
assert('AnewtConfig::get("sampleint") === 2');
assert('AnewtConfig::getdefault("nonexistentint", 3) === 3');

AnewtConfig::seed(array('test' => 'foo', 'foo' => 'baz'));
assert('AnewtConfig::get("test") === "foo"');
assert('AnewtConfig::get("foo") === "baz"');

AnewtConfig::delete('foo');
assert('AnewtConfig::is_set("blah") === false');
assert('AnewtConfig::is_set("foo") === false');
assert('AnewtConfig::is_set("test") === true');

$export = AnewtConfig::to_array();
assert('array_has_key($export, "sampleint")');
assert('array_has_key($export, "test")');
assert('array_has_key($export, "foo") === false');

?>
