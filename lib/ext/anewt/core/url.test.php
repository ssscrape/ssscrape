<?php

error_reporting(E_ALL | E_STRICT);
require_once '../anewt.lib.php';


/* Test URL::join */

assert('URL::join() == ""');
assert('URL::join("/") == "/"');
assert('URL::join("/", "/") == "/"');
assert('URL::join("/", "", "/") == "/"');

assert('URL::join("/path/") == "/path/"');
assert('URL::join("/", "path", "/") == "/path/"');
assert('URL::join("/", "/some", "path", "/") == "/some/path/"');
assert('URL::join("path/") == "path/"');
assert('URL::join("path", "/") == "path/"');
assert('URL::join(array("some", "/path", "to/", "a/nice//", "file.ext")) == "some/path/to/a/nice/file.ext"');
assert('URL::join("/path", "to", "file") == "/path/to/file"');
assert('URL::join("/", "path", "to", "file") == "/path/to/file"');
assert('URL::join("path/to", "/file") == "path/to/file"');

assert('URL::join("/", 123, "to", 456) == "/123/to/456"');
assert('URL::join(2006, 11, "foo") == "2006/11/foo"');
assert('URL::join("http://anewt.net/", "path") == "http://anewt.net/path"');
assert('URL::join("http://anewt.net", "path") == "http://anewt.net/path"');
assert('URL::join("http://anewt.net/", "/", "some", "path") == "http://anewt.net/some/path"');


/* Test URL::join_ext */
assert('URL::join_ext("some", "file", "txt") == "some/file.txt"');
assert('URL::join_ext("/some", "file", ".txt") == "/some/file.txt"');
assert('URL::join_ext("file", ".txt") == "file.txt"');
assert('URL::join_ext(array("/file", ".txt")) == "/file.txt"');
assert('URL::join_ext("some", "path", "to", "/file", ".tar.gz") == "some/path/to/file.tar.gz"');
assert('URL::join_ext("file/", ".txt") == "file/.txt"');

/* Test URL::parse and URL::unparse */
$url = 'http://www.example.com/some/page?foo=3&foo=bar&baz=foo;';
$x = URL::parse($url);
list($base, $args) = $x;
assert('$base === "http://www.example.com/some/page"');
assert('is_assoc_array($args)');
assert('count($args) == 2');
assert('array_has_key($args, "foo")');
assert('$args["foo"] === "bar"');

$url = 'page.php?foo=3&bar=123';
$x = URL::parse($url);
list($base, $args) = $x;
assert('is_assoc_array($args)');
assert('count($args) == 2');
assert('array_has_key($args, "foo")');
assert('$args["foo"] === "3"');
assert('array_has_key($args, "bar")');
assert('$args["bar"] === "123"');


$url = 'http://example.com/some/page.php?foo=3&bar=123';
$x = URL::parse($url);
list($base, $args) = $x;
$y = URL::unparse($base, $args);
assert('$y === $url');

$url = '/some/page.php';
$x = URL::parse($url);
list($base, $args) = $x;
$y = URL::unparse($base, $args);
assert('$y === $url');

?>
