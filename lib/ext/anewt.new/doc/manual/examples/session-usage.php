/* Start a new session, eg. after a succesful login attempt */
AnewtSession::init('yourapp');

/* Store some data in the session */
AnewtSession::set('username', $username);

/* Destroy the session, eg. on logout */
AnewtSession::destroy();
