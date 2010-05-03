<?php

error_reporting(E_ALL);

require_once '../anewt.lib.php';

define('ANEWT_TEXTILE_DEVELOPMENT', 1);

anewt_include('gpc');
anewt_include('page');

$p = new AnewtPage();
$p->set('title', 'Textile formatting test');

if (AnewtGPC::get_bool('debug'))
{
	header('Content-type: text/plain');
	$p->set('content_type', 'text/plain');
} else {
	list ($base_url, $params) = URL::parse(Request::url());
	$params['debug'] = true;
	$debug_url = URL::unparse($base_url, $params);
	$p->append(ax_p(ax_a_href('(Page source for debugging)', $debug_url)));
}

anewt_include('textformatting');
anewt_include('textile');

$text = file_get_contents('sample-text.txt');
$formatted_text = TextFormatter::format($text, 'textile');

$p->append(ax_raw($formatted_text));

$p->flush();

?>
