#!/usr/bin/php -q
<?php

error_reporting(E_ALL | E_STRICT);
require_once '../anewt.lib.php';
anewt_include('mail');


/* Explain usage */
function usage() {
	printf(
	"Usage: %s [filename]              omit filename or specify - to use stdin\n",
	$_SERVER['argv'][0]);
}


/* Parse arguments */
$argv = $_SERVER['argv'];
array_shift($argv); // drop argv[0]
$argc = count($argv);
$read_stdin = false;
if ($argc == 0) {
	usage();
	die();
}
if (($argc==1) && ($argv[0] == '-')) {
	$read_stdin = true;
}


/* Read stdin */
if ($read_stdin) {
	$fp = fopen('php://stdin', 'r');
	$message = AnewtMailMessage::from_stream($fp);
	fclose($fp);

/* Read from file */
} else {
	$filename = $argv[0];
	$message = AnewtMailMessage::from_file($filename);
}

echo $message->has_header('From')
 ? '"From" header found!'
 : 'No "From" header found!';
echo  NL;

echo $message->get_header('From'), "\n";
echo $message->get_header('To'), "\n";
echo $message->get_header('Subject'), "\n";

echo str_truncate($message->get('body'), 500), "\n";

?>
