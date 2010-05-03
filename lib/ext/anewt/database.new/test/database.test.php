<?php

anewt_include('database.new');


class AnewtDatabaseTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @expectedException AnewtDatabaseException
	 */
	function test_invalid_connection_type()
	{
		$settings = array(
			'type'        => 'invalid-database-type',
		);
		AnewtDatabase::setup_connection($settings, 'invalid-connection');
	}

	function test_two_connections() {
		$settings = array('type' => 'sqlite');
		AnewtDatabase::setup_connection($settings);
		AnewtDatabase::setup_connection($settings, 'connection2');

		$c2 = AnewtDatabase::get_connection('connection2');
		$c1 = AnewtDatabase::get_connection();

		$this->assertTrue($c1->is_connected());
		$this->assertTrue($c2->is_connected());

		$c1->disconnect();

		$this->assertFalse($c1->is_connected());
		$this->assertTrue($c2->is_connected());
	}
}

?>
