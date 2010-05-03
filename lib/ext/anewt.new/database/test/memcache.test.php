<?php

anewt_include('database');

class AnewtDatabaseMemcacheTestSuite extends PHPUnit_Framework_TestSuite
{
	public static function suite()
	{
		$s = new AnewtDatabaseMemcacheTestSuite();
		$s->addTestSuite('AnewtDatabaseMemcacheTest');
		return $s;
	}

	public function setup()
	{
		/* The real, underlying connection... */
		$settings = array(
			'type' => 'sqlite',
		);
		AnewtDatabase::setup_connection($settings, 'non-cached');

		/* ...and the caching connection */
		$settings = array(
			'type'       => 'memcache',
			'connection' => AnewtDatabase::get_connection('non-cached'),
			'expiry'     => 2, /* Extremely short expiry time for testing purposes */
			'identifier' => 'test-id',
		);
		AnewtDatabase::setup_connection($settings, 'cached');
	}
}


class AnewtDatabaseMemcacheTest extends PHPUnit_Framework_TestCase
{
	function xxxtest_connection()
	{
		$connection = AnewtDatabase::get_connection('cached');
		$this->assertTrue($connection->is_connected());
		$connection->disconnect();
		$this->assertFalse($connection->is_connected());
		$connection->connect();
		$this->assertTrue($connection->is_connected());

		$this->assertEquals(0, $connection->n_cache_hits);
		$this->assertEquals(0, $connection->n_cache_misses);
	}

	function test_caching()
	{
		$connection = AnewtDatabase::get_connection('cached');

		/* This should not not hit the cache */
		$row = $connection->prepare('SELECT 1 AS test;')->execute()->fetch_one();
		$this->assertEquals(1, $row['test']);

		$this->assertEquals(0, $connection->n_cache_hits);
		$this->assertEquals(0, $connection->n_cache_misses);

		/* This should hit the cache the second time */
		$rows = $connection->prepare_execute_fetch_all('SELECT 1 AS test;');
		$this->assertEquals(1, count($rows));
		$this->assertEquals(1, $rows[0]['test']);
		$this->assertEquals(0, $connection->n_cache_hits);
		$this->assertEquals(1, $connection->n_cache_misses);
		$rows = $connection->prepare_execute_fetch_all('SELECT 1 AS test;');
		$this->assertEquals(1, count($rows));
		$this->assertEquals(1, $rows[0]['test']);
		$this->assertEquals(1, $connection->n_cache_hits);
		$this->assertEquals(1, $connection->n_cache_misses);

		/* This should hit the cache the second time */
		$row = $connection->prepare_execute_fetch_one('SELECT 1 AS test;');
		$this->assertEquals(1, $row['test']);
		$this->assertEquals(1, $connection->n_cache_hits);
		$this->assertEquals(2, $connection->n_cache_misses);
		$row = $connection->prepare_execute_fetch_one('SELECT 1 AS test;');
		$this->assertEquals(1, $row['test']);
		$this->assertEquals(2, $connection->n_cache_hits);
		$this->assertEquals(2, $connection->n_cache_misses);

		/* Flush the cache */
		$connection->flush_cache();
		$row = $connection->prepare_execute_fetch_one('SELECT 1 AS test;');
		$this->assertEquals(2, $connection->n_cache_hits);
		$this->assertEquals(3, $connection->n_cache_misses);
	}
}

?>
