<?php

require_once '../anewt.lib.php';
anewt_include('validator');

function usage() {
	echo str_join_wrap(
		'Error: please specify a filename with mail addresses to test against
		the validator. There should be one address per line.'
	);
	echo NL;
}

if ($_SERVER['argc'] != 2) {
	usage();
	die();
}

$validator = &new AnewtValidatorEmail();

$filename = $_SERVER['argv'][1];
foreach (file($filename) as $line)
{
	$line = trim($line);

	/* Skip empty lines and comments */
	if (!strlen($line) || str_has_prefix($line, '#'))
		continue;

	$valid = $validator->is_valid($line);
	if (!$valid)
		printf("Invalid address: %s\n", $line);
}

?>
