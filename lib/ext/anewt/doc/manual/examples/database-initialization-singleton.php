anewt_include('database');
$db_settings = array(...);
$db = DB::get_instance('mysql', $db_settings);
unset($db_settings);
