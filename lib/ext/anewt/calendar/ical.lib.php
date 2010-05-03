<?php

/* vim:set fdm=indent: */

/*
 * Anewt, Almost No Effort Web Toolkit, calendar module
 *
 * Copyright (C) 2006  Wouter Bolsterlee <uws@xs4all.nl>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA+
 */


anewt_include('datetime');

/*
 * Content types not (yet) supported (not really needed for the basic
 * functionality):
 *
 * - VTODO
 * - VJOURNAL
 * - VFREEBUSY
 * - VTIMEZONE
 * - VALARM
 */


/**
 * A class representing an iCalendar calendar object. It has some basic
 * properties and may contain one or more events. An instance of this class can
 * be serialized to a text/calendar (.ics) file, which can be imported into
 * calendering applications such as Evolution, iCal or Outlook.
 */
class iCalendar extends Container
{
	var $events = array(); /**< \private Array holding all events */

	/**
	 * \static
	 *
	 * Escape a string so that it can be used for TEXT fields. Newlines and
	 * other characters are escaped to match the iCalendar specification.
	 *
	 * \param $str
	 *   A string to escape.
	 *
	 * \return
	 *   The escaped string
	 */
	function escape_string($str)
	{
		assert('is_string($str)');

		/* Escape comma's, semicolons, backslashes */
		$str = str_replace('\\', '\\\\', $str);
		$str = str_replace(',', '\,', $str);
		$str = str_replace(';', '\;', $str);

		/* Newlines need escaping too: literal \N should be put in the output */
		$str = preg_replace('/\r?\n/', '\N', $str);

		return $str;
	}

	/**
	 * Create a new calendar instance.
	 */
	function iCalendar()
	{
		/* Do nothing */
	}

	/**
	 * Add an event to the calendar.
	 *
	 * \param $event
	 *   An iCalendarEvent instance
	 */
	function add_event(&$event)
	{
		assert('is_object($event)');
		assert('method_exists($event, "to_ical")');
		$this->events[] = &$event;
	}

	/**
	 * Returns a string representing this iCalender.
	 *
	 * \return
	 *   String with iCalender data.
	 */
	function to_ical()
	{
		$r = array();

		/* Generic calendar data */
		$r[] = 'BEGIN:VCALENDAR';
		$r[] = 'VERSION:2.0';
		$r[] = sprintf('METHOD:%s', $this->getdefault('method', 'PUBLISH'));
		$r[] = sprintf('PRODID:%s', $this->getdefault('generator', '-//Almost No Effort Web Toolkit//Calendar module//EN'));

		$r[] = '';

		/* Loop over all events in the calendar */
		foreach (array_keys($this->events) as $key)
		{
			$event = &$this->events[$key];
			$r[] = $event->to_ical();
		}

		$r[] = 'END:VCALENDAR';

		return implode("\r\n", $r);
	}

	/**
	 * Outputs the iCalendar to a browser.
	 *
	 * \param $exit_after_flush
	 *   Boolean value indicating whether to stop execution after flushing to
	 *   the client (default false). If true, make sure the call to flush() is
	 *   the last call in your code, because statements below the call to
	 *   flush() will never be executed.
	 */
	function flush($exit_after_flush=false)
	{
		/* MIME type */
		header('Content-Type: text/calendar');

		/* Easier debugging (without a browser "save as" dialog) */
		if ($this->getdefault('debug', false))
			header('Content-Type: text/plain');

		/* A filename is optional, but it is recommended to set one for better
		 * client compatibility (some platforms ignore the MIME type information
		 * and use the file name (extension) to determine what actions to
		 * perform. */
		if ($this->is_set('filename'))
		{
			$filename = $this->get('filename');
			$filename = str_strip_suffix($filename, '.ics');
			header(sprintf('Content-Disposition: inline; filename=%s.ics', $filename));
		}

		echo $this->to_ical();

		assert('is_bool($exit_after_flush)');
		if ($exit_after_flush)
			exit(0);
	}
}


/**
 * Represents an event on a calendar. Several properties can be set: summary,
 * description, date-start, date-end, all-day (boolean), location, url.
 */
class iCalendarEvent extends Container {
	/**
	 * Construct creates a new iCalendar event.
	 *
	 * \param $summary
	 *   A summary line for this event (optional).
	 *
	 * \param $datestart
	 *   A AnewtDateTimeAtom instance describing the start date for this event
	 *   (optional).
	 */
	function iCalendarEvent($summary=null, $datestart=null)
	{
		$this->set('all-day', false);

		if (!is_null($summary))
		{
			assert('is_string($summary)');
			$this->set('summary', $summary);
		}

		if (!is_null($datestart))
		{
			assert('$datestart instanceof AnewtDateTimeAtom');
			$this->set('datestart', $datestart);
		}
	}

	/**
	 * Setter for the event summary. This string must not contain newlines, so
	 * they will be replaced with space characters.
	 *
	 * \param $str
	 *   The summary to set
	 */
	function set_summary($str)
	{
		$str = preg_replace('/\r?\n/', '', $str);
		$this->_set('summary', $str);
	}

	/**
	 * Setter for the event description. This value needs to be escaped.
	 *
	 * \param $str
	 *   The description to set
	 */
	function set_description($str)
	{
		$this->_set('description', iCalendar::escape_string($str));
	}

	/**
	 * Return a string representing this event.
	 *
	 * \return
	 *   String with iCalender data.
	 */
	function to_ical()
	{
		$r = array();
		$r[] = 'BEGIN:VEVENT';

		/* Generation date */
		$r[] = sprintf('DTSTAMP;VALUE=DATE:%s', AnewtDateTime::iso8601_compact(AnewtDateTime::now()));

		/* Summary */
		$r[] = sprintf('SUMMARY:%s', $this->get('summary'));

		/* Description */
		if (!$this->is_set('description'))
		{
			$this->set('description', $this->get('summary'));
		}
		$r[] = sprintf('DESCRIPTION:%s', $this->get('description'));

		/* Unique ID */
		if (!$this->is_set('uid'))
		{
			/* Generate a unique id */
			$uid = strtoupper(sprintf('%s-%s',
					md5($this->get('summary')),
					md5(AnewtDateTime::iso8601_compact($this->get('datestart')))));
			$this->set('uid', $uid);
		}
		$r[] = sprintf('UID:%s', $this->get('uid'));


		/* All day event? */
		$all_day = $this->get('all-day');
		assert('is_bool($all_day)');

		/* Start date */
		$datestart = $this->get('datestart');
		$datestart_str  = $all_day
			? AnewtDateTime::iso8601_date_compact($datestart)   /* without time */
			: AnewtDateTime::iso8601_compact($datestart);       /* with time */
		$r[] = sprintf('DTSTART;VALUE=DATE:%s', $datestart_str);

		/* End date */
		if ($this->is_set('dateend'))
		{
			$dateend = $this->get('dateend');
			assert('$dateend instanceof AnewtDateTimeAtom');
			$dateend_str = $all_day
				? AnewtDateTime::iso8601_date_compact($dateend) /* without time */
				: AnewtDateTime::iso8601_compact($dateend);     /* with time */
			$r[] = sprintf('DTEND;VALUE=DATE:%s', $dateend_str);
		}

		/* Transparency */
		$transparent = $this->getdefault('transparent', false);
		assert('is_bool($transparent)');
		$r[] = sprintf('TRANSP:%s', $transparent ? 'TRANSPARENT' : 'OPAQUE');

		/* Location  */
		if ($this->is_set('location'))
		{
			$r[] = sprintf('LOCATION:%s', $this->get('location'));
		}

		/* URL  */
		if ($this->is_set('url'))
		{
			$r[] = sprintf('URL:%s', $this->get('url'));
		}

		$r[] = 'END:VEVENT';
		$r[] = '';

		return implode("\r\n", $r);
	}
}

?>
