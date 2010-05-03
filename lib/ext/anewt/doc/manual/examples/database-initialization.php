anewt_include('database');
$db_settings = array(
    'hostname' => 'localhost',
    'username' => 'foo',
    'password' => 'bar',
    'database' => 'yourapp');
$db = new DB('mysql', $db_settings);
unset($db_settings);
