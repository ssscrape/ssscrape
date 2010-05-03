$prep_query = $db->prepare('SELECT name, age FROM persons');
$result = $prep_query->execute();
foreach ($result->fetch_all() as $row) {
    printf("%s is %d years old.\n",
        $row['name'],
        $row['age']);
}
