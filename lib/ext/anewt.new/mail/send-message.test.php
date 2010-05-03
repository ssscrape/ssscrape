#!/usr/bin/php -q
<?php

error_reporting(E_ALL);
require_once '../anewt.lib.php';
anewt_include('mail');

$m = new AnewtMailMessage();

function usage() {
	printf("Usage: %s address\n", $_SERVER['argv'][0]);
	exit(1);
}

$address = array_get_default($_SERVER['argv'], 1, null);
if (is_null($address))
{
	echo 'Error: no address specified', NL;
	usage();
}


$m->add_header('To', $address);
$m->add_header('Subject', 'Test');
$m->add_header('From', $address);

$m->set('body',
	'This is a test message sent using the AnewtMailMessage class.');

$result = $m->send();

if ($result)
	echo 'Mail sent successfully.', NL;
else
	echo 'Sending mail failed.', NL;

?>
