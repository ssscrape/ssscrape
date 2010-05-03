// using a string for an integer field is not allowed
$pq = $db->prepare('SELECT name FROM persons WHERE age = ?int?');
$pq->execute('John');
// script execution stops here
print 'This line is never printed';
