class Foo extends Container
{
	function get_foo_()
	{
		// computationally expensive stuff is done here
		// ...

		return $result;
	}
}

$obj = new Foo();

// This triggers the get_foo_() method...
print $obj->get('foo'); // expensive (only the first time)

// ... while subsequent calls just return the cached value:
print $obj->get('foo'); // cheap
print $obj->get('foo'); // cheap
