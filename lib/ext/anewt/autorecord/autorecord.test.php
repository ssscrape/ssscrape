<?php

error_reporting(E_ALL | E_STRICT);
require_once '../anewt.lib.php';

anewt_include('autorecord');

/**
 * Sample Person class with a simple database schema
 */
class Person_ extends AutoRecord
{
	/**
	 * Table name
	 */
	static function _db_table()
	{
		return 'person';
	}

	/**
	 * Default sort column
	 */
	static function _db_sort_column()
	{
		return 'age';
	}

	/**
	 * Simple database layout
	 */
	static function _db_columns()
	{
		return array(
			'id' => 'integer',
			'name' => 'string',
			'age' => 'integer',
			'is_happy' => 'boolean',
		);
	}
}

/* Register the Person/Person_ class as an AutoRecord */
AutoRecord::register('Person');


/* Database connection (in-memory SQLite database) */
$db = DB::get_instance('sqlite', array(
			'filename' => ':memory:',
			'debug' => true,
			'debug_print' => true,
			));

/* Create the schema (sqlite ignores column types, though) */
$db->prepare_execute('CREATE TABLE Person (
	id INTEGER PRIMARY KEY,
	name VARCHAR(255),
	age INTEGER,
	is_happy BOOLEAN
	)');

$pq = $db->prepare('INSERT INTO Person (id, name, age, is_happy) VALUES (?int?, ?str?, ?int?, ?bool?)');
$pq->execute(1, 'A', 10, true);
$pq->execute(2, 'B', 11, false);
$pq->execute(3, 'C', 12, false);
$pq->execute(4, 'D', 13, null);
$pq->execute(5, 'E', 14, false);



/* Test the AutoRecord retrieval methods */

$result = Person::find_one_by_id(2);
// $result = Person::find_by_id(2);
// $result = Person::find_by_id(array(2, 4));
// $result = Person::find_by_id(2, 3);

// $result = Person::find_all();
// $result = Person::find_by_sql();
// $result = Person::find_by_sql('WHERE id > ?int? ORDER BY age DESC LIMIT ?int?', 2, 2);
// $result = Person::find_one_by_sql(array(
// 			'where' => 'id > ?int?',
// 			'order-by' => 'age DESC',
// 			'limit' => '?int?',
// 			), 1, 3);

var_dump($result);

/* Test the save (update/insert) and delete methods */

$p1 = Person::find_one_by_id(1);
$p1->set('name', 'John');
$p1->set('age', '12');
$p1->save();

$p2 = new Person();
$p2->set('name', 'Foo');
$p2->set('age', '2');
$p2->set('is_happy', true);
$p2->save();

/* Test toggle() */

$p1->toggle('is_happy');
$p1->save();

$p2->toggle('is_happy');
$p2->save();


/* Dump all data in the person table */
$rows = $db->prepare_execute_fetch_all('SELECT * FROM person');
foreach ($rows as $row) {
	echo array_format($row), "\n";
}

?>
