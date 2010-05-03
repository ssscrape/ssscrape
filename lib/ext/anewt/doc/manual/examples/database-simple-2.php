$pq = $db->prepare('INSERT INTO persons (name, age)
    VALUES (?string?, ?int?)');
$pq->execute('John', 4);
$pq->execute('James', 7);
