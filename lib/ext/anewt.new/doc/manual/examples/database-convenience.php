// Fetch a single row:
$single_row = $db->prepare_execute_fetch(
    'SELECT name, age FROM persons WHERE id=?int?', 2);

// The above is exactly the same as:
$pq = $db->prepare(
    'SELECT name, age FROM persons WHERE id=?int?');
$rs = $pq->execute(2);
$single_row = $rs->fetch();

// Fetch all rows:
$all_rows = $db->prepare_execute_fetch_all(
    'SELECT name, age FROM persons ORDER BY age');

// Quickly insert a record:
$db->prepare_execute(
    'INSERT INTO persons (name, age) VALUES (?str?, ?int?)',
    'John', 4);
