<?php

require_once '../anewt.lib.php';
anewt_include('logging');

AnewtLog::init(false);
AnewtLog::add_handler(new AnewtLogHandlerDefault());
AnewtLog::add_handler(new AnewtLogHandlerFile('test.log'));
AnewtLog::add_handler(new AnewtLogHandlerFile('test-debug.log'), ANEWT_LOG_LEVEL_DEBUG);

AnewtLog::set_domain('a');
AnewtLog::error('An error occured.');
AnewtLog::set_domain('b');
AnewtLog::error('Error number %d', 3);
AnewtLog::reset_domain();
AnewtLog::debug('Debugging message: %d: %s', 4, 'dbg');
AnewtLog::set_domain('c');
AnewtLog::warning('This is a warning message without arguments');
AnewtLog::reset_domain();
AnewtLog::warning('This is a warning message: %d: %s', 2, 'test1');
AnewtLog::reset_domain();
AnewtLog::warning('This is a warning message: %d: %s', array(2, 'test2'));
AnewtLog::warning('This is warning with format characters but no values, %s %s %s');

?>
