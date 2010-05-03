class Foo
{
    function doSomething()
	{
        global $db;
        $db->prepare(...);
    }
}

$foo_instance = new Foo();
$foo->doSomething();
