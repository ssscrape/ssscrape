<?php

anewt_include('autorecord');


class AnewtAutoRecordTestSuite extends PHPUnit_Framework_TestSuite
{
	public static function suite()
	{
		$s = new AnewtAutoRecordTestSuite();
		$s->addTestSuite('AnewtAutoRecordTest');
		return $s;
	}

	public function setup()
	{
		AnewtDatabase::setup_connection(array(
			'type' => 'sqlite'
		));
		$connection = AnewtDatabase::get_connection();

		$connection->prepare_execute('CREATE TABLE Person (
			id INTEGER PRIMARY KEY,
			name VARCHAR(255),
			age INTEGER,
			is_happy BOOLEAN
			)');

		$pq = $connection->prepare('INSERT INTO Person (id, name, age, is_happy) VALUES (?int?, ?str?, ?int?, ?bool?)');
		$pq->execute(1, 'A', 10, true);
		$pq->execute(2, 'B', 11, false);
		$pq->execute(3, 'C', 12, false);
		$pq->execute(4, 'D', 13, null);
		$pq->execute(5, 'E', 14, false);
	}
}


/**
 * Sample Person class with a simple database schema
 */
class Person_ extends AnewtAutoRecord
{
	/**
	 * Table name
	 */
	static function db_table()
	{
		return 'person';
	}

	/**
	 * Default sort column
	 */
	static function db_sort_column()
	{
		return 'age';
	}

	/**
	 * Simple database layout
	 */
	static function db_columns()
	{
		return array(
			'id' => 'integer',
			'name' => 'string',
			'age' => 'integer',
			'is_happy' => 'boolean',
		);
	}

	static function db_columns_order_by()
	{
		return array(
			'age' => 'ASC',
			'name' => 'DESC',
		);
	}
}
AnewtAutoRecord::register('Person');


class AnewtAutoRecordTest extends PHPUnit_Framework_TestCase
{
	public function setup()
	{
	}

	public function teardown()
	{
	}

	/**
	 * Test the AutoRecord retrieval methods.
	 */
	function test_find()
	{
		$result = Person::db_find_one_by_id(2);
		$this->assertNotNull($result);

		$result = Person::db_find_all_by_id(array(2));
		$this->assertEquals(1, count($result));

		$result = Person::db_find_all_by_id(array(2, 4));
		$this->assertEquals(2, count($result));

		$result = Person::db_find_all();
		$this->assertEquals(5, count($result));

		$result = Person::db_find_all_by_sql();
		$this->assertEquals(5, count($result));

		$result = Person::db_find_all_by_sql('WHERE id > ?int? ORDER BY age DESC LIMIT ?int?', array(2, 2));
		$this->assertEquals(2, count($result));

		$result = Person::db_find_one_by_sql(array('limit' => '2'));
		$this->assertType('Person', $result);
		$this->assertEquals(1, count($result));

		$result = Person::db_find_one_by_sql(
			array(
				'where' => 'id > ?int?',
				'order-by' => 'age DESC',
				'limit' => '?int?',
				'offset' => '1',
			),
			array(1, 3));
		$this->assertType('Person', $result);
		$this->assertEquals(1, count($result));

		$result = Person::db_find_one_by_sql(
			array('where' => 'name = ?str?'),
			array('this won\'t result in ?any? matches?int? I think :)')
		);
		$this->assertNull($result);

		$result = Person::db_find_one_by_column('age', 10);
		$this->assertNotNull($result);
		$this->assertType('Person', $result);

		$result = Person::db_find_all_by_column('is_happy', NULL);
		$this->assertEquals(1, count($result));

		$result = Person::db_find_one_by_columns(array(
			'name' => 'B',
			'age' => 11,
		));
		$this->assertNotNull($result);
		$this->assertType('Person', $result);

		$result = Person::db_find_all_by_columns(array(
			'name' => 'something that does not exist',
			'age' => 12,
		));
		$this->assertEquals(0, count($result));
	}

	/**
	 * Test the save (update/insert) and delete methods.
	 */
	function test_manipulation()
	{
		$p1 = Person::db_find_one_by_id(1);
		$p1->set('name', 'This is a very ?very? strange ??int? name');
		$p1->set('age', '12');
		$p1->db_save();

		$p2 = new Person();
		$p2->set('name', 'Foo');
		$p2->set('age', '2');
		$p2->set('is_happy', true);
		$this->assertFalse($p2->is_set('id'));
		$p2->db_insert();
		$this->assertTrue($p2->is_set('id'));
		$p2->db_update();
		$this->assertTrue($p2->is_set('id'));
	}

	/**
	 * @expectedException AnewtDatabaseException
	 */
	function test_invalid_update()
	{
		$p = new Person();
		$p->db_update();
	}

	/**
	 * Test the grouping methods.
	 */
	function test_grouping()
	{
		$all_records = Person::db_find_all();

		$list = Person::array_by_column_value($all_records, 'name', true);
		$this->assertTrue($list['Foo'] instanceof Person);
		$this->assertEquals(6, count($list));

		$list = Person::array_by_column_value($all_records, 'name', false);
		$this->assertTrue(is_array($list['Foo']));
		$this->assertEquals(6, count($list));
		$this->assertTrue($list['Foo'][0] instanceof Person);

		$list = Person::array_by_primary_key_value($all_records);
		$this->assertTrue($list[1] instanceof Person);
		$this->assertEquals(6, count($list));
		$this->assertFalse(array_has_key($list, 7));
	}

	function test_dump()
	{
		/*
		$connection = AnewtDatabase::get_connection();
		$rows = $connection->prepare_execute_fetch_all('SELECT * FROM person');
		foreach ($rows as $row)
		{
			echo array_format($row), NL;
		}
		*/
	}
}

?>
