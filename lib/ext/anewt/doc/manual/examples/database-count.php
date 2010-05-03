// Getting the number of result rows
$pq = $db->prepare('SELECT name, age FROM persons WHERE id=?int?');
$rs = $pq->execute(2);
$number_of_rows = $rs->count();

// Getting the number of affected rows
$pq = $db->prepare('UPDATE persons set age = 12');
$rs = $pq->execute();
$number_of_rows_affected = $rs->count_affected();
