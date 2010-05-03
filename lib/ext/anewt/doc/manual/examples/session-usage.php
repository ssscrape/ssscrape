// Start a new session, eg. after a succesful login attempt
Session::init('yourapp');

// Store some data in the session
Session::set('username', $username);

// Destroy the session, eg. on logout
Session::destroy();
