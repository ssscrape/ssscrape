#!/usr/bin/php
<?php

error_reporting(E_ALL);
require_once dirname(__FILE__) . '/../anewt.lib.php';
anewt_include('textformatting');


/* Options */

array_shift($argv); // skip command name

$use_page = !in_array('--no-page', $argv);

$fd = STDIN;
$title = '';

while (true)
{
	$arg = array_shift($argv);

	if (is_null($arg))
		break;

	if (str_has_prefix($arg, '--'))
		continue;

	$fd = fopen($arg, 'r');
	$title = $arg;
	break;
}


/* Input */

$input  = '';
while (!feof($fd))
	$input .= fread($fd, 16384);
fclose($fd);


/* Output */

$output = TextFormatter::format($input, 'textile');
$output = trim($output);

if ($use_page)
{
	anewt_include('page');
	$page = new Page();
	$page->set('enable_dublin_core', false);
	$page->set('content_type', 'application/xhtml+xml');
	$page->set('charset', 'UTF-8');
	$page->set('title', $title);
	$page->add_stylesheet_href_media('style.css', 'screen'));
	$page->append($output);
	echo to_string($page), NL;
}
else
{
	echo $output, NL;
}

exit(0);

?>
