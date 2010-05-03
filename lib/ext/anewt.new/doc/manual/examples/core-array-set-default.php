// If you want to augment the $user_data array by providing
// defaults for missing values, this piece of code suffices:
array_set_default($user_data, 'firstname', '');
array_set_default($user_data, 'age', 0);

// Now you are sure all keys can be safely accessed (without
// generating PHP warnings):
do_something_useful($user_data['firstname'],
	$user_data['lastname'],
	$user_data['age']);
