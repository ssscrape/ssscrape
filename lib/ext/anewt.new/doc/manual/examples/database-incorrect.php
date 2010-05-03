// This is really evil. Never do this since it allows an
// attacker to execute arbitrary SQL queries!
$pq = $db->prepare(
    "INSERT INTO persons
    (name, age)
    VALUES
    ({$_POST[name]}, {$_POST[age]})");

// This is evil too, although slightly less evil than the
// example above. Notice that $age is not escaped, because
// it is supposed to be a number. However, remember rule
// number one: never trust user input!
$pq = $db->prepare(
    'INSERT INTO persons
    (name, age)
    VALUES
    (' . addslashes($_POST['name']) . ', ' . $_POST['age'] .')');
