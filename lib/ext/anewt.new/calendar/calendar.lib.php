<?php

/*
 * Anewt, Almost No Effort Web Toolkit, calendar module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/*
 * XXX: Content types not (yet) supported (not really needed for the basic
 * functionality):
 *
 * - VTODO
 * - VJOURNAL
 * - VFREEBUSY
 * - VTIMEZONE
 * - VALARM
 */


/**
 * A class representing an iCalendar calendar object.
 *
 * It has some basic properties and may contain one or more events (represented
 * by AnewtCalendarEvent). An instance of this class can be serialized to
 * a <code>text/calendar</code> (<code>.ics</code>) file, which can be imported
 * into calendering applications such as Evolution, iCal or Outlook.
 *
 * The available properties are:
 *
 * - \c filename contains the filename for this calendar
 * - \c method defines the \c METHOD for the calendar. This is \c PUBLISH by
 *   default.
 * - \c generator contains the product identifier (optional, defaults to an
 *   Anewt-specific string)
 *
 * A filename is optional, but it is recommended to set one for better
 * client compatibility, since some platforms ignore the MIME type information
 * and rely on the file name (extension) to determine what actions to perform.
 *
 * \see AnewtCalendarEvent
 */
class AnewtCalendar extends AnewtContainer
{
	/* Static members */

	/**
	 * Escape a string so that it can be used for TEXT fields. Newlines and
	 * other characters are escaped to match the iCalendar specification.
	 *
	 * \param $str
	 *   A string to escape.
	 *
	 * \param $multiline
	 *   Boolean to indicate whether this is a multiline string. This affects
	 *   how the string is escaped.
	 *
	 * \return
	 *   The escaped string
	 */
	private static function escape_string($str, $multiline)
	{
		assert('is_string($str)');
		assert('is_bool($multiline)');

		/* Escape comma's, semicolons, backslashes */
		$str = str_replace('\\', '\\\\', $str);
		$str = str_replace(',', '\,', $str);
		$str = str_replace(';', '\;', $str);

		/* Remove or escape newlines. Newlines are escaped as a literal \N */

		$newline_replacement = $multiline ? '\N' : ' ';
		$str = preg_replace('/\r?\n/', $newline_replacement, $str);

		return $str;
	}


	/* Instance members */

	/**
	 * Array holding all events
	 */
	private $events = array();

	/**
	 * Construct a new AnewtCalendar instance.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->_seed(array (
			'filename'  => null,
			'generator' => '-//Almost No Effort Web Toolkit//Calendar module//EN',
			'method'    => 'PUBLISH',
		));
	}

	/**
	 * Add an event to the calendar.
	 *
	 * \param $event
	 *   An AnewtCalendarEvent instance
	 */
	function add_event($event)
	{
		assert('$event instanceof AnewtCalendarEvent');
		$this->events[] = $event;
	}

	/**
	 * Render this calendar to a string in iCal format.
	 *
	 * \return
	 *   String representation of this calendar
	 */
	function render()
	{
		$out = array();

		$out[] = 'BEGIN:VCALENDAR';
		$out[] = 'VERSION:2.0';
		$out[] = sprintf('METHOD:%s', $this->method);
		$out[] = sprintf('PRODID:%s', $this->generator);
		$out[] = '';

		foreach ($this->events as $event)
		{
			$out[] = 'BEGIN:VEVENT';

			/* Generation date */

			$out[] = sprintf('DTSTAMP;VALUE=DATE:%s', AnewtDateTime::iso8601_compact(AnewtDateTime::now()));


			/* Summary and description */

			assert('!is_null($event->summary)');

			$description = $event->description;
			if (is_null($description))
				$description = $event->summary;

			$out[] = sprintf('SUMMARY:%s', AnewtCalendar::escape_string($event->summary, false));
			$out[] = sprintf('DESCRIPTION:%s', AnewtCalendar::escape_string($description, true));


			/* Unique ID */

			$uid = $event->uid;
			if (is_null($uid))
			{
				/* Generate a unique id */
				$uid = strtoupper(sprintf('%s-%s',
						md5($event->summary),
						md5(AnewtDateTime::iso8601_compact($event->date_start))
				));
			}
			$out[] = sprintf('UID:%s', $uid);


			/* Dates */

			assert('$event->date_start instanceof AnewtDateTimeAtom');
			$datestart_str  = $event->all_day
				? AnewtDateTime::iso8601_date_compact($event->date_start)   /* Without time */
				: AnewtDateTime::iso8601_compact($event->date_start);       /* With time */
			$out[] = sprintf('DTSTART;VALUE=DATE:%s', $datestart_str);

			if (!is_null($event->date_end))
			{
				assert('$event->date_end instanceof AnewtDateTimeAtom');
				$date_end_str = $event->all_day
					? AnewtDateTime::iso8601_date_compact($event->date_end) /* Without time */
					: AnewtDateTime::iso8601_compact($event->date_end);     /* With time */
				$out[] = sprintf('DTEND;VALUE=DATE:%s', $date_end_str);
			}

			$out[] = sprintf('TRANSP:%s', $event->transparent ? 'TRANSPARENT' : 'OPAQUE');


			/* Misc  */

			if (!is_null($event->location))
				$out[] = sprintf('LOCATION:%s', $event->location);

			if (!is_null($event->url))
				$out[] = sprintf('URL:%s', $event->url);

			$out[] = 'END:VEVENT';
			$out[] = '';
		}

		$out[] = 'END:VCALENDAR';

		return implode(CRLF, $out);
	}

	/**
	 * Output the calendar to a browser.
	 *
	 * This renders the calendar and all its asssociated events, and sends the
	 * output to the browser with the correct HTTP headers for the MIME type and
	 * (optionally) the downoad filename.
	 */
	function flush()
	{
		header('Content-Type: text/calendar');

		$filename = $this->filename;
		if (!is_null($filename))
		{
			/* Make sure the filename ends with .ics */
			if (!str_has_prefix($filename, 'ics'))
				$filename = sprintf('%s.ics', $filename);

			header(sprintf('Content-Disposition: inline; filename=%s', $filename));
		}

		echo to_string($this), NL;
	}
}


/**
 * Calendar event for AnewtCalendar calendars.
 *
 * AnewtCalendarEvent instances can be added to an AnewtCalendar using
 * AnewtCalendar::add_event().
 *
 * The properties that can be set on AnewtCalendar instances are:
 *
 * - <code>uid</code>: A unique ID for this event (optional, defaults to an
 *   autogenerated, deterministic ID based on the summary and start date)
 * - <code>summary</code>: The summary line for this event
 * - <code>description</code>: A longer (multiline) description for this event
 *   (optional)
 * - <code>location</code>: The location of this event (optional)
 * - <code>url</code>: An associated URL for this event (optional)
 * - <code>date-start</code>: The start date for this event (AnewtDateTimeAtom
 *   instance)
 * - <code>date-end</code>: The end date for this event (AnewtDateTimeAtom
 *   instance, optional)
 * - <code>all-day</code>: Whether this is an all-day event (boolean, defaults
 *   to \c false)
 * - <code>transparent</code>: Whether this event is transparent and hence
 *   should not conflict with other appointments (boolean, defaults to \c false)
 *
 * Note that the \c summary and \c date-start properties are required by the
 * iCalendar specification.
 *
 * \see AnewtCalendar
 */
class AnewtCalendarEvent extends AnewtContainer
{
	/**
	 * Construct a new AnewtCalendarEvent instance.
	 *
	 * If you don't specify \c $summary or \c $date_start these values need to
	 * be set later.
	 *
	 * \param $summary
	 *   A summary line for this event (optional).
	 *
	 * \param $date_start
	 *   A AnewtDateTimeAtom instance describing the start date for this event
	 *   (optional).
	 */
	function __construct($summary=null, $date_start=null)
	{
		parent::__construct();

		$now = AnewtDateTime::now();

		$this->_seed(array(

			'uid'         => null,

			'summary'     => null,
			'description' => null,
			'location'    => null,
			'url'         => null,

			'date-start'  => $now,
			'date-end'    => $now,
			'all-day'     => false,
			'transparent' => false,
		));

		if (!is_null($summary))
		{
			assert('is_string($summary)');
			$this->summary = $summary;
		}

		if (!is_null($date_start))
		{
			assert('$date_start instanceof AnewtDateTimeAtom');
			$this->date_start = $date_start;
		}
	}
}

?>
