<?php

error_reporting(E_ALL);
require_once '../../anewt.lib.php';
anewt_include('database');

// Create a database instance
$db = new DB('sqlite', array('filename' => ':memory:'));

// Create a table and insert some test rows
$db->prepare_execute("BEGIN");
$db->prepare_execute("CREATE TABLE foo (a)");
$ins = $db->prepare("INSERT INTO foo (a) VALUES (?str?)");
$ins->execute("foo");
$ins->execute("b\"ar");
$db->prepare_execute("COMMIT");

// Retrieve the rows from the table
$qry = $db->prepare("SELECT * FROM foo ORDER BY a");
$rs = $qry->execute();
assert('is_a($rs, "SqliteResultSet")');
assert('$rs->count() === 2');
$rows = $rs->fetch_all();
assert('count($rows) === 2');
assert('is_array($rows[0])');
assert('is_array($rows[1])');
assert('count($rows[0]) === 1');
assert('$rows[0]["a"] === "b\"ar"');

// Test retrieving of an empty resultset
$qry = $db->prepare("SELECT * FROM foo WHERE a <> a");
$rs = $qry->execute();
assert('$rs->count() === 0');
assert('$rs->fetch() === FALSE');
assert('$rs->fetch() === FALSE');
$rs = $qry->execute();
assert('$rs->fetch_all() === array()');

// Test resulting resultset for an update query
$qry = $db->prepare("UPDATE foo SET a=?str? WHERE a=?str?");
$rs = $qry->execute('boo', 'b"ar');
assert('$rs->count_affected() === 1');
assert('$rs->count() === 0');
assert('$rs->fetch() === FALSE');
$rs = $qry->execute('boo', 'bar');
assert('$rs->count_affected() === 0');

// Test transaction methods
$db->transaction_begin();
$db->prepare_execute("INSERT INTO foo (a) VALUES (?str?)", "marijn");
$db->transaction_rollback();
assert('count($db->prepare_execute_fetch_all("SELECT * FROM foo WHERE a=?str?", "marijn")) === 0');

$db->transaction_begin();
$db->prepare_execute("INSERT INTO foo (a) VALUES (?str?)", "marijn");
$db->transaction_commit();
assert('count($db->prepare_execute_fetch_all("SELECT * FROM foo WHERE a=?str?", "marijn")) === 1');

$db->disconnect();

?>
