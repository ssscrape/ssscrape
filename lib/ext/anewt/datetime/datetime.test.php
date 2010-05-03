<?php

error_reporting(E_ALL | E_STRICT);
require_once '../anewt.lib.php';
anewt_include('datetime');


/* TODO: rewrite using assertions. Split into 2 parts:
 * - Parsing checks
 * - Formatting checks
 */

function f($d) {
	printf("%s\n", $d);
	$d = AnewtDateTime::parse($d);
	printf("%s\n", AnewtDateTime::iso8601($d));
	printf("%s\n", AnewtDateTime::iso8601_week($d));
	printf("%s\n", AnewtDateTime::iso8601_week_day($d));
	printf("%s\n", AnewtDateTime::iso8601_day_of_year($d));
	printf("\n");
}

f(time());

f('2005');

f('200512');
f('2005-09');

f('1983-01-15');
f('19830115');

f('1983-01-15 18:33:12');

f('2005-10-31T12:34:56');
f('20320630T23:59:59');
f('20320630T233641');
f('20320630T2336');

f('1984-060');
f('1984060');

f('This one should fail');

f('1983-01-15T18:30:00');

f('Mon Jan 23 02:04:06 2006');
f('Fri Jun  2 14:45:00 2006');

f('12:34');
f('12:34:56');

f('Fri, 21 Nov 1997 09:55:06 GMT');
f('Fri, 21 Nov 1997 09:55:06 -0600');
f('Fri, 21 Nov 1997 09:55:06 +0630');

echo '(There should only be empty lines below)', NL;
f(null);


assert('AnewtDateTime::date(AnewtDateTime::parse("2008-12-09")) === "2008-12-09";');

/* FIXME: rewrite all tests as assertions */

?>
