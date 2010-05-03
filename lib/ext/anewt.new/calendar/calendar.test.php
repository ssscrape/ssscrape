<?php

error_reporting(E_ALL | E_STRICT);
require_once dirname(__FILE__) . '/../anewt.lib.php';

anewt_include('calendar');

$calendar = new AnewtCalendar();

$event = new AnewtCalendarEvent('Title');
$event->date_start = AnewtDateTime::parse_string('2009-01-01 12:00');
$event->date_end = AnewtDateTime::parse_string('2009-01-01 14:00');
$event->summary = 'This is the summary';
$event->location = 'This is the location';
$event->url = 'http://example.org/foo';
$event->uid = 'abc1234567890';
$calendar->add_event($event);

$event = new AnewtCalendarEvent('Another event', AnewtDateTime::now());
$event->summary = "This is a multiline\nsummary";
$event->summary = "This is a multiline\ndescription";
$calendar->add_event($event);

$calendar->flush();

?>
