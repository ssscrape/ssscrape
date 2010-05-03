<?php

class AnewtArrayFunctionsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test is_numeric_array and is_assoc_array
	 */
	function test_is_functions()
	{
		$this->assertTrue(is_numeric_array(array()));
		$this->assertTrue(is_assoc_array(array()));

		$data = array('a', 'b', 'c');
		$this->assertTrue(is_numeric_array($data));
		$this->assertTrue(is_numeric_array($data, true));
		$this->assertFalse(is_assoc_array($data));
		$this->assertFalse(is_assoc_array($data, true));

		$data = array(1, 2, 3);
		$this->assertTrue(is_numeric_array($data));
		$this->assertTrue(is_numeric_array($data, true));
		$this->assertFalse(is_assoc_array($data));
		$this->assertFalse(is_assoc_array($data, true));

		$data = array('a' => 'aa', 'b' => 'bb', 'c' => 'cc');
		$this->assertFalse(is_numeric_array($data));
		$this->assertTrue(is_assoc_array($data));

		$data = array('a', 'b' => 'bb', 'c' => 'cc', 'd' => 'dd');
		$this->assertTrue(is_numeric_array($data));
		$this->assertFalse(is_numeric_array($data, true));
		$this->assertFalse(is_assoc_array($data));
		$this->assertTrue(is_assoc_array($data, true));

		$data = array('a' => 'aa', 'b', 'c');
		$this->assertFalse(is_numeric_array($data));
		$this->assertFalse(is_numeric_array($data, true));
		$this->assertTrue(is_assoc_array($data));
		$this->assertTrue(is_assoc_array($data, true));
	}

	/**
	 * Test array_has_key and array_has_value
	 */
	function test_has_key_value()
	{
		$data = array(
				'first' => 'one',
				'second' => 'two',
				'third' => 'three'
				);
		$this->assertTrue(array_has_key($data, "first"));
		$this->assertFalse(array_has_key($data, "foo"));
		$this->assertFalse(array_has_key($data, "one"));
		$this->assertTrue(array_has_value($data, "three"));
		$this->assertFalse(array_has_value($data, "foo"));
		$this->assertFalse(array_has_value($data, "first"));
	}

	/**
	 * Test array_unset_key
	 */
	function test_array_unset_key()
	{
		$data = array(
				'first' => 'one',
				'second' => 'two',
				'third' => 'three'
				);
		array_unset_key($data, 'first');
		$this->assertFalse(array_has_key($data, "first"));
		$this->assertTrue(array_has_key($data, "third"));
		array_unset_key($data, 'third');
		$this->assertFalse(array_has_key($data, "third"));
		array_unset_key($data, 'notpresent');
		$this->assertFalse(array_has_key($data, "notpresent"));
	}

	/**
	 * Test array_unset_keys
	 */
	function test_array_unset_keys()
	{
		$data = array(
				'first' => 'one',
				'second' => 'two',
				'third' => 'three'
				);
		$keys_to_remove = array ('first', 'second', 'notpresent');
		array_unset_keys($data, $keys_to_remove);
		$this->assertFalse(array_has_key($data, "first"));
		$this->assertFalse(array_has_key($data, "second"));
		$this->assertTrue(array_has_key($data, "third"));
		$this->assertFalse(array_has_key($data, "notpresent"));
	}

	/**
	 * Test array_clear
	 */
	function test_array_clear()
	{
		$data = array(
				'first' => 'one',
				'second' => 'two',
				'third' => 'three'
				);
		array_clear($data);
		$this->assertEquals(0, count($data));
	}

	/**
	 * Test array_flip_string_keys
	 */
	function test_array_flip_string_keys(){
		$data = array(
				'first' => 'one',
				'second' => 'two',
				'third' => 'three',
				4 => 'four',
				5 => 'five',
				'six' => 6,
				);
		$data = array_flip_string_keys($data);
		$this->assertTrue(array_has_key($data, "one"));
		$this->assertTrue(array_has_key($data, "two"));
		$this->assertFalse(array_has_key($data, "first"));
		$this->assertTrue(array_has_key($data, 4));
		$this->assertTrue(array_has_key($data, 6));
		$this->assertFalse(array_has_key($data, "six"));
		$data = array_flip_string_keys($data);
		$this->assertTrue(array_has_key($data, 6));
		$this->assertTrue(array_has_key($data, "first"));
	}

	/**
	 * Test array_check_types and require_args
	 */
	function test_array_type_checking()
	{
		$data = array('foo', 1, '2', true, 'bar', array());
		$this->assertFalse(array_check_types($data, "siibsa", false));
		$this->assertTrue(array_check_types($data, "siibsa"));
		$this->assertFalse(array_check_types($data, "a"));
		$this->assertFalse(array_check_types($data, "abc"));

		require_args($data, 'siibsa'); // should not throw an error
	}

	/**
	 * Test require_args
	 *
	 * @expectedException Exception
	 */
	function test_require_args_error()
	{
		require_args($data, 'bsa');
	}

	/**
	 * Test array_trim_strings
	 */
	function test_array_trim_strings()
	{
		$data = array('foo  ', '  bar', '---foo---', 'bar---  ');

		$expected = array('foo', 'bar', '---foo---', 'bar---');
		$result = array_trim_strings($data);
		$this->assertEquals($result, $expected);

		$expected = array('foo  ', '  bar', 'foo', 'bar---  ');
		$result = array_trim_strings($data, '-');
		$this->assertEquals($result, $expected);

		$expected = array('foo', 'bar', 'foo', 'bar');
		$result = array_trim_strings($data, ' -');
		$this->assertEquals($result, $expected);
	}

	/**
	 * Test array_get_int
	 */
	function test_array_get_int()
	{
		$data = array(
				'first' => 1,
				'second' => '2',
				'third' => 'drei',
				4 => 4);
		$this->assertEquals(1, array_get_int($data, "first"));
		$this->assertEquals(1, array_get_int($data, "first", 2));
		$this->assertEquals(3, array_get_int($data, "notfound", 3));
		$this->assertEquals(33, array_get_int($data, "third", 33));
		$this->assertEquals(4, array_get_int($data, "4"));
		$this->assertEquals(4, array_get_int($data, 4));
	}

	/**
	 * Test natksort
	 */
	function test_natksort()
	{
		$data = array('a1'=>1, 'a20'=>2, 'a2'=>3);
		$expected = array('a1'=>1, 'a2'=>3, 'a20'=>2);
		natksort($data);
		$this->assertEquals($data, $expected);
	}
}

?>
