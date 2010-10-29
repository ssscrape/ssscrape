<?php

require_once dirname(__FILE__) . '/../anewt.lib.php';

anewt_include('rss');

$channel = new AnewtRssChannel('Title', 'http://example.com', 'This is a test');

$channel->set('author', 'Anewt test');
$channel->set('build-date', AnewtDateTime::now());

$item = new AnewtRssItem('test', 'http://example.com/some-item');
$item->set('description', 'The description goes here.');
$item->set('guid', 'http://example.com/some-item');
$item->set('date', AnewtDateTime::now());
$channel->add_item($item);

$item = new AnewtRssItem('another test');
$item->set('description', 'The description goes here.');
$item->set('link', 'http://example.com/another-item');
$item->set('guid', 'http://example.com/another-item');
$item->set('date', AnewtDateTime::now());
$channel->add_item($item);

$channel->flush();

?>
