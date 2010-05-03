// Sample array data, included from a configuration file:
$user_data = array(
		'firstname' => 'John',
		'lastname' => 'Doe',
		'age'  => 34);

// Now assume that the 'firstname' and age fields in the
// above data are optional and should default to an empty
// string and the number 0 if omitted.

// Look at this ugly piece of code:
$firstname = '';
if (array_key_exists('firstname', $user_data)) {
	$firstname = $user_data['firstname'];
}
$age = 0;
if (array_key_exists('age', $user_data)) {
	$age = $user_data['age'];
}

// This is a bit shorter, but still really ugly:
$firstname = array_key_exists('firstname', $user_data)
	? $user_data['firstname']
	: '';
$age = array_key_exists('age', $user_data)
	? $user_data['age']
	: 0;

// Things get so much better with Anewt:
$firstname = array_get_default($user_data, 'firstname', '');
$age = array_get_default($user_data, 'age', 0);
