// using a string for an integer field is not allowed
$pq = $db->prepare('SELECT name FROM persons WHERE age = ?int?');
$pq->execute('12'); // works, because the string '12' looks like an integer
$pq->execute('John'); // breaks
