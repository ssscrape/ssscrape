<?php

class AnewtURLTest extends PHPUnit_Framework_TestCase
{
	function url_provider()
	{
		return array
		(
			/* Path building */
			array(
				array(),
				null,
				''
			),
			array(
				'test',
				null,
				'test'
			),
			array(
				array('/'),
				null,
				'/'
			),
			array(
				array('/', '/'),
				null,
				'/'
			),
			array(
				array('/', '', '/'),
				null,
				'/'
			),
			array(
				array('/path/'),
				null,
				'/path/'
			),
			array(
				array('/', 'path', '/'),
				null,
				'/path/'
			),
			array(
				array('/', '/some', 'path', '/'),
				null,
				'/some/path/'
			),
			array(
				array('path/'),
				null,
				'path/'
			),
			array(
				array('path', '/'),
				null,
				'path/'
			),
			array(
				array('/path', 'to', 'file'),
				null,
				'/path/to/file'
			),
			array(
				array('some', '/path', 'to/', 'a/nice/', 'file.ext'),
				null,
				'some/path/to/a/nice/file.ext'
			),
			array(
				array('/', 'path', 'to', 'file'),
				null,
				'/path/to/file'
			),
			array(
				array('path/to', '/file'),
				null,
				'path/to/file'
			),
			array(
				array('http://anewt.net/', 'path'),
				null,
				'http://anewt.net/path'
			),
			array(
				array('http://anewt.net', 'path'),
				null,
				'http://anewt.net/path'
			),
			array(
				array('http://anewt.net/', '/', 'some', 'path'),
				null,
				'http://anewt.net/some/path'
			),

			/* Query string */
			array(
				array(),
				array('foo' => 'bar'),
				'?foo=bar'
			),
			array(
				array(),
				array(),
				''
			),
			array(
				array(),
				array('foo' => 'invalid', 'foo' => 'bar', 'baz' => 'quux'),
				'?foo=bar&baz=quux'
			),
			array(
				array('/test1', 'test2/'),
				array('foo' => null, 'bar' => 'test', 'baz' => null),
				'/test1/test2/?foo&bar=test&baz'
			),
			array(
				'test',
				array('foo' => null, 'bar' => 'test', 'baz' => null),
				'test?foo&bar=test&baz'
			),

			/* URL encode/decode */
			array(
				'&&',
				null,
				'%26%26'
			),
			array(
				'test',
				array('foo' => '&&'),
				'test?foo=%26%26'
			),
			array(
				array('http://www.example.com', 'some/page', 'special&chars!'),
				array('foo' => '3', 'baz' => 'foo'),
				'http://www.example.com/some/page/special%26chars%21?foo=3&baz=foo'
			),
		);
	}

	/**
	 * @dataProvider url_provider
	 */
	function test_build($path, $parameters, $expected)
	{
		$url = AnewtURL::build($path, $parameters);
		$this->assertEquals($expected, $url);
	}

	/**
	 * @dataProvider url_provider
	 */
	function test_parse($path_expected, $parameters_expected, $input)
	{
		if (is_null($parameters_expected))
			$parameters_expected = array();

		list ($path_out, $parameters_out) = AnewtURL::parse($input);

		$this->assertEquals($parameters_expected, $parameters_out);

		if (is_string($path_expected))
			$this->assertEquals($path_expected, $path_out);
	}
}

?>
