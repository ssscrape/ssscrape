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

$input_chunks  = array();
while (!feof($fd))
{
	$input_chunks[] = fread($fd, 16384);
}
$input = join('', $input_chunks);
fclose($fd);


/* Output */

$output = TextFormatter::format($input, 'textile');
$output = trim($output);

if ($use_page)
{
	anewt_include('page');
	$page = new AnewtPage();
	$page->enable_dublin_core = false;
	$page->content_type = 'application/xhtml+xml';
	$page->charset = 'UTF-8';
	$page->title = $title;
	$page->add_stylesheet_href_media('style.css', 'screen');
	$page->append(ax_raw($output));
	echo to_string($page), NL;
}
else
{
	echo $output, NL;
}

?>
