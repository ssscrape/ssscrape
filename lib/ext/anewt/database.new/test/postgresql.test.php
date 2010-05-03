<?php

anewt_include('database.new');

class StringWrap { function render() { return 'test'; } }

class AnewtDatabasePostgreSQLSuite extends PHPUnit_Framework_TestSuite
{
	public static function suite()
	{
		$s = new AnewtDatabasePostgreSQLSuite();
		$s->addTestSuite('AnewtDatabasePostgreSQLTest');
		return $s;
	}

	public function setup()
	{
		$settings = array(
			'type'		=> 'postgresql',
			'hostname'	=> 'localhost',
			'username'	=> 'anewt',
			'password'	=> 'anewt',
			'database'	=> 'anewt',
		);
		AnewtDatabase::setup_connection($settings);
		AnewtDatabase::setup_connection($settings, 'connection2');

		$connection = AnewtDatabase::get_connection();
		$connection->prepare_execute('CREATE TEMPORARY TABLE test_table (
					boolean_col BOOLEAN,
					integer_col INTEGER,
					float_col FLOAT,
					string_col TEXT,
					date_col DATE,
					datetime_col TIMESTAMP,
					timestamp_col TIMESTAMP,
					time_col TIME,
					raw_col TEXT)');
	}
}


class AnewtDatabasePostgreSQLTest extends PHPUnit_Framework_TestCase
{
	private function list_tables() {
		$connection = AnewtDatabase::get_connection();
		$result = $connection->prepare_execute_fetch_all(
			"SELECT c.relname
			FROM pg_catalog.pg_class c
			LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
			WHERE c.relkind in ('r', '') AND n.nspname NOT IN ('pg_catalog', 'pg_toast') AND pg_catalog.pg_table_is_visible(c.oid)");
		$res = array();
		foreach ($result as $row) {
			$res[] = $row['relname'];
		}
		return $res;
	}

	public function setup()
	{
	}

	public function teardown()
	{
	}

	public function invalid_settings_provider() {
		return array(
			array(array(
				'username' => 'anewt',
				'password' => 'anewt',
				'database' => 'anewt',
			)),
			array(array(
				'hostname' => 'localhost',
				'password' => 'anewt',
				'database' => 'anewt',
			)),
			array(array(
				'hostname' => 'localhost',
				'username' => 'anewt',
				'database' => 'anewt',
			)),
			array(array(
				'hostname' => 'localhost',
				'username' => 'anewt',
				'password' => 'anewt',
			)),
		);
	}

	/**
	 * @dataProvider invalid_settings_provider
	 * @expectedException AnewtDatabaseConnectionException
	 */
	public function test_constructor_with_invalid_settings($settings) {
		$db = new AnewtDatabaseConnectionPostgreSQL($settings);
	}

	public function unknown_settings_provider() {
		return array(
			array(array(
				'hostname' => 'invalidplace',
				'username' => 'anewt',
				'password' => 'anewt',
				'database' => 'anewt',
			)),
			array(array(
				'hostname' => 'localhost',
				'username' => 'invalidname',
				'password' => 'anewt',
				'database' => 'anewt',
			)),
			array(array(
				'hostname' => 'localhost',
				'username' => 'anewt',
				'password' => 'invalidpw',
				'database' => 'anewt',
			)),
			array(array(
				'hostname' => 'localhost',
				'username' => 'anewt',
				'password' => 'anewt',
				'database' => 'invaliddb',
			)),
		);
	}

	/**
	 * @dataProvider unknown_settings_provider
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function test_constructor_with_unknown_settings($settings) {
		$db = new AnewtDatabaseConnectionPostgreSQL($settings);
		$db->connect();
	}

	public function assertInvariants() {
		$connection = AnewtDatabase::get_connection();
		$this->assertTrue($connection->is_connected());

		# Table is succesfully created
		$this->assertContains('test_table', $this->list_tables());
	}

	public function assertPreConditions() {
		$this->assertInvariants();
	}

	public function assertPostConditions() {
		$this->assertInvariants();
	}

	public function test_connection()
	{
		$c1 = AnewtDatabase::get_connection();
		$c2 = AnewtDatabase::get_connection('connection2');
		$this->assertNotNull($c1);
		$this->assertNotNull($c2);
		$this->assertType('AnewtDatabaseConnectionPostgreSQL', $c1);
		$this->assertType('AnewtDatabaseConnectionPostgreSQL', $c2);
		$this->assertTrue($c1->is_connected());
		$this->assertTrue($c2->is_connected());

		$c2->connect();
		$this->assertTrue($c2->is_connected());

		$c2->disconnect();
		$this->assertFalse($c2->is_connected());
		$this->assertTrue($c1->is_connected());
	}

	public function test_autoconnect()
	{
		$settings = array(
			'type'		=> 'postgresql',
			'hostname'	=> 'localhost',
			'username'	=> 'anewt',
			'password'	=> 'anewt',
			'database'	=> 'anewt',
			'autoconnect'	=> false,
		);
		AnewtDatabase::setup_connection($settings, 'connection3');

		$connection = AnewtDatabase::get_connection('connection3');
		$this->assertFalse($connection->is_connected());
		$connection->connect();
		$this->assertTrue($connection->is_connected());
		$connection->connect();
		$this->assertTrue($connection->is_connected());
	}


	/* Test valid values */

	function valid_values_provider()
	{
		return array
		(
			/* null */
			array(null, null, null, null, null, null, null, null, null),

			/* boolean */
			array(true, null, null, null, null, null, null, null, null),
			array(false, null, null, null, null, null, null, null, null),

			/* integer */
			array(null, 2, null, null, null, null, null, null, null),
			array(null, '3', null, null, null, null, null, null, null),

			/* float */
			array(null, null, 2.0, null, null, null, null, null, null),
			array(null, null, 1.234, null, null, null, null, null, null),
			array(null, null, 3, null, null, null, null, null, null),

			/* string */
			array(null, null, null, 'Test', null, null, null, null, null),
			array(null, null, null, 'Te\';st', null, null, null, null, null),
			array(null, null, null, "\t\n;--'", null, null, null, null, null),
			array(null, null, null, 2, null, null, null, null, null),
			array(null, null, null, new StringWrap(), null, null, null, null, null),

			/* date */
			array(null, null, null, null, '2006-06-06', null, null, null, null),
			array(null, null, null, null, AnewtDateTime::now(), null, null, null, null),

			/* datetime */
			array(null, null, null, null, null, '2006-06-06 06:06:06', null, null, null),
			array(null, null, null, null, null, AnewtDateTime::now(), null, null, null),
			array(null, null, null, null, null, AnewtDateTime::sql(AnewtDateTime::now()), null, null, null),

			/* timestamp */
			array(null, null, null, null, null, null, '2006-06-06 06:06:06', null, null),
			array(null, null, null, null, null, null, AnewtDateTime::now(), null, null),
			array(null, null, null, null, null, null, AnewtDateTime::sql(AnewtDateTime::now()), null, null),

			/* time */
			array(null, null, null, null, null, null, null, '06:06:06', null),
			array(null, null, null, null, null, null, null, AnewtDateTime::now(), null),
			array(null, null, null, null, null, null, null, AnewtDateTime::sql_time(AnewtDateTime::now()), null),

			/* raw */
			array(null, null, null, null, null, null, null, null, "'?int?'"),

			/* all at once */
			array(true, 3, 2.0, 'Test', '2006-06-06', '2006-06-06 06:06:06', '2006-06-06 06:06:06', '06:06:06', "'?raw?'"),
		);
	}

	/**
	 * @dataProvider valid_values_provider
	 */
	function test_valid_values()
	{
		$values = func_get_args();
		$connection = AnewtDatabase::get_connection();
		$n_affected = $connection->prepare_executev(
			'INSERT INTO test_table VALUES (?bool?, ?int?, ?float?, ?string?, ?date?, ?datetime?, ?timestamp?, ?time?, ?raw?)',
			$values);
		$this->assertTrue($n_affected == 1);
	}


	/* Test invalid values */

	function invalid_values_provider()
	{
		return array
		(
			/* boolean */
			array('foo', null, null, null, null, null, null, null, null),

			/* integer */
			array(null, 'foo', null, null, null, null, null, null, null),

			/* float */
			array(null, null, 'foo', null, null, null, null, null, null),

			/* string */
			array(null, null, null, true, null, null, null, null, null),
			array(null, null, null, 1.234, null, null, null, null, null),

			/* date */
			array(null, null, null, null, 'foo', null, null, null, null),
			array(null, null, null, null, true, null, null, null, null),
			array(null, null, null, null, 2, null, null, null, null),

			/* datetime */
			array(null, null, null, null, null, 'foo', null, null, null),
			array(null, null, null, null, null, true, null, null, null),
			array(null, null, null, null, null, 2, null, null, null),

			/* timestamp */
			array(null, null, null, null, null, null, 'foo', null, null),
			array(null, null, null, null, null, null, true, null, null),
			array(null, null, null, null, null, null, 2, null, null),

			/* time */
			array(null, null, null, null, null, null, null, 'foo', null),
			array(null, null, null, null, null, null, null, true, null),
			array(null, null, null, null, null, null, null, 2, null),
		);
	}

	/**
	 * @dataProvider invalid_values_provider
	 * @expectedException AnewtDatabaseQueryException
	 */
	function test_invalid_values()
	{
		$values = func_get_args();
		$connection = AnewtDatabase::get_connection();
		$pq = $connection->prepare('INSERT INTO test_table VALUES (?bool?, ?int?, ?float?, ?string?, ?date?, ?datetime?, ?timestamp?, ?time?, ?raw?)');
		$pq->executev($values);
	}

	function valid_values_name_mode_provider()
	{
		$null_values = array(
			'mybool' => null,
			'myint' => null,
			'myfloat' => null,
			'mystring' => null,
			'mydate' => null,
			'mydatetime' => null,
			'mytimestamp' => null,
			'mytime' => null,
			'myraw' => null,
		);
		$actual_values = array(
			'mybool' => true,
			'myint' => 3,
			'myfloat' => 2.0,
			'mystring' => 'Test',
			'mydate' => '2006-06-06',
			'mydatetime' => '2006-06-06 06:06:06',
			'mytimestamp' => '2006-06-06 06:06:06',
			'mytime' => '06:06:06',
			'myraw' => "'?raw?'",
		);

		return array
		(
			/* null */
			array($null_values),
			array(new Container($null_values)),

			/* all at once */
			array($actual_values),
			array(new Container($actual_values)),
		);
	}

	/**
	 * @dataProvider valid_values_name_mode_provider
	 */
	function test_named_mode($values)
	{
		$connection = AnewtDatabase::get_connection();

		$sql = 'INSERT INTO test_table
			VALUES (
				?bool:mybool?,
				?int:myint?,
				?float:myfloat?,
				?string:mystring?,
				?date:mydate?,
				?datetime:mydatetime?,
				?timestamp:mytimestamp?,
				?time:mytime?,
				?raw:myraw?
			);';

		$n = $connection->prepare_executev($sql, $values);
		$this->assertEquals(1, $n);

		$pq = $connection->prepare($sql);
		$rs = $pq->executev($values);
		$n = $rs->count_affected();
		$this->assertEquals(1, $n);
	}

	function test_transaction()
	{
		$cnt_sql = 'SELECT COUNT(*) AS cnt FROM test_table';

		$connection = AnewtDatabase::get_connection();

		$row = $connection->prepare_execute_fetch_one($cnt_sql);
		$n_rows_before = $row['cnt'];


		/* Start a transaction, insert, and count rows */
		$connection->transaction_begin();
		$connection->prepare_execute('INSERT INTO test_table (boolean_col) VALUES (TRUE)');
		$row = $connection->prepare_execute_fetch_one($cnt_sql);
		$n_rows_after = $row['cnt'];
		$this->assertEquals($n_rows_before + 1, $n_rows_after);

		/* Rollback, and count rows again */
		$connection->transaction_rollback();
		$row = $connection->prepare_execute_fetch_one($cnt_sql);
		$n_rows_after = $row['cnt'];
		$this->assertEquals($n_rows_before, $n_rows_after);

		/* Again, but now commit */
		$connection->transaction_begin();
		$connection->prepare_execute('INSERT INTO test_table (boolean_col) VALUES (TRUE)');
		$connection->transaction_commit();

		/* And count rows again */
		$row = $connection->prepare_execute_fetch_one($cnt_sql);
		$n_rows_after = $row['cnt'];
		$this->assertEquals($n_rows_before + 1, $n_rows_after);
	}
}

?>
