// do this only the first time (from an include file):
$db = DB::get_instance('postgresql', $db_settings);

// Now use the singleton instance like this:
class Foo
{
	function doSomething()
	{
		$db = DB::get_instance();
		$db->prepare(...);
	}
}

$foo_instance = new Foo();
$foo->doSomething();
