$pq = $db->prepare('INSERT INTO persons (name, age)
    VALUES (?string?, ?int?)');

// multiple parameters
$pq->execute('John', 4);

// one array parameter
$values = array('James', 7);
$pq->execute($values);
