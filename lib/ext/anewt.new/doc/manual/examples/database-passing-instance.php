class Foo
{
    var $db;
    function Foo($db)
	{
        $this->db = $db;
		// now you can use $this->db->prepare(...) from
		// other methods
	}

    function doSomething() {
        $this->db->prepare(...);
    }
}

$foo_instance = new Foo($db);
$foo->doSomething();
