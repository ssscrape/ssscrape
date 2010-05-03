// The class used in the examples below:
class Person extends Container
{
	function get_fullname()
	{
		/* format the name correctly */
		return sprintf('%s %s',
				$this->get('firstname'),
				$this->get('lastname'));
	}
	
	function set_age($age)
	{
		/* input checking */
		assert('is_int($age)');
		if (($age > 100) || ($age < 0))
			return;

		/* everything seems to be ok */
		$this->_set('age', $age);
	}
}

/* Create a new Person instance and store some data in it */
$p = new Person();
$person->set('firstname', 'John');
$person->set('lastname', 'Doe');

/* We can also use object properties instead of methods */
$person->age = 23;

/* Storing several values at once is easy (e.g. rows from a
 * database) */
$values = array(
		'firstname' => 'John',
		'lastname' => 'Doe',
		'age' => 23);
$person->seed($values);

/* Now you can get data from the object */
$age_next_decennium = $person->get('age') + 10;

/* This will invoke the get_fullname() method */
$fullname = $person->get('fullname');

/* These lines throw errors or don't have any effect */
$person->set('age', 'invalid');
$person->set('age', 800);
