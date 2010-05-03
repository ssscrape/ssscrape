<?php

/*
 * Anewt, Almost No Effort Web Toolkit, datetime module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * \file
 *   Date and time functionality.
 *
 * \todo
 *   Time zone support
 */


/*
 * Regular expressions for parsing date strings.
 */
/** Regular expression for years */
define('DATETIME_RE_YEAR',               '(\d{4})');
/** Regular expression for months */
define('DATETIME_RE_MONTH',              '(0[0-9]|1[012])');
/** Regular expression for days */
define('DATETIME_RE_DAY',                '([012 ]\d|3[01])');
/** Regular expression for hours */
define('DATETIME_RE_HOUR',               '([01]\d|2[0-3])');
/** Regular expression for minutes */
define('DATETIME_RE_MINUTE',             '([0-5]\d)');
/** Regular expression for seconds */
define('DATETIME_RE_SECOND',             DATETIME_RE_MINUTE);
/** Regular expression for day of week */
define('DATETIME_RE_DAY_OF_WEEK',        '([1-7])');
/** Regular expression for day of year */
define('DATETIME_RE_DAY_OF_YEAR',        '([0-3]\d{2})');
/** Regular expression for week of year */
define('DATETIME_RE_WEEK_OF_YEAR',       '([0-4]\d|5[0-3])');
/** Regular expression for abbreviated day names */
define('DATETIME_RE_DAY_NAMES_ABBR',    '(Sun|Mon|Tue|Wed|Thu|Fri|Sat)');
/** Regular expression for abbreviated month names */
define('DATETIME_RE_MONTH_NAMES_ABBR',   '(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dev)');
/** Regular expression for timezones */
define('DATETIME_RE_TIMEZONE',          '(GMT|[\+\-][012][0-9][03]0)');


/**
 * This class is used to represent a date. Don't use this class directly, it's
 * mainly for internal usage. Use AnewtDateTime's parse functions to create
 * AnewtDateTimeAtom objects.
 */
final class AnewtDateTimeAtom
{
	var $year;   /**< Year    */
	var $month;  /**< Month   */
	var $day;    /**< Day     */
	var $hour;   /**< Hours   */
	var $minute; /**< Minutes */
	var $second; /**< Seconds */

	/**
	 * Constructs a new AnewtDateTimeAtom object.
	 *
	 * \param $year
	 *   Year.
	 * \param $month
	 *   Month (optional, defaults to 1).
	 * \param $day
	 *   Day (optional, defaults to 1).
	 * \param $hour
	 *   Hours (optional, defaults to 0).
	 * \param $minute
	 *   Minutes (optional, defaults to 0).
	 * \param $second
	 *   Seconds (optional, defaults to 0).
	 */
	function __construct($year, $month=1, $day=1, $hour=0, $minute=0, $second=0)
	{
		/* Date */
		assert('AnewtDateTime::is_valid_date_ymd($year, $month, $day)');
		$this->year =  (int) $year;
		$this->month = (int) $month;
		$this->day =   (int) $day;

		/* Time */
		assert('AnewtDateTime::is_valid_time_hms($hour, $minute, $second)');
		$this->hour = (int) $hour;
		$this->minute = (int) $minute;
		$this->second = (int) $second;
	}

	/**
	 * Returns a timestamp for this AnewtDateTimeAtom object. Timestamps are very
	 * useful when formatting dates using strftime() or date().
	 *
	 * \return
	 *   An integer representing this AnewtDateTimeAtom instance.
	 */
	function timestamp()
	{
		return mktime(
			$this->hour, $this->minute, $this->second,
			$this->month, $this->day, $this->year);
	}

	/**
	 * Renders this object to a string. This method makes AnewtDateTimeAtom conform
	 * to the "renderable" interface.
	 *
	 * \return
	 *   A formatted string.
	 */
	function render()
	{
		return AnewtDateTime::iso8601($this);
	}
}


/**
 * This class provides methods for date and time operations. It supports both
 * parsing and formatting.
 */
final class AnewtDateTime
{
	/**
	 * Constructor (triggers an error). This class provides only static methods.
	 */
	function __construct()
	{
		trigger_error('AnewtDateTime instances cannot be instantiated; this
			class provides static methods only.', E_USER_ERROR);
	}


	/** \{
	 * \name Construction and parsing methods
	 *
	 * These methods can be used to obtain an AnewtDateTimeAtom instance. There
	 * are multiple methods to parse dates in various formats.
	 */

	/**
	 * Get the current date and time.
	 *
	 * \return
	 *   AnewtDateTimeAtom representing the current date.
	 */
	static function now()
	{
		return AnewtDateTime::parse(time());
	}

	/**
	 * Parses a date into a AnewtDateTimeAtom instance. The function also accepts
	 * AnewtDateTimeAtom instances. In that case the supplied instance is returned.
	 * If parsing fails, null is returned.
	 *
	 * Note that in many cases you should just call one of the more specific
	 * parsing methods instead.
	 *
	 * \param $date
	 *   An int/string/AnewtDateTimeAtom which will be parsed.
	 *
	 * \return
	 *   A newly created AnewtDateTimeAtom instance or null if parsing failed.
	 *
	 * \see AnewtDateTime::parse_timestamp
	 * \see AnewtDateTime::parse_string
	 * \see AnewtDateTime::parse_ymd
	 */
	static function parse($date)
	{
		/* Integers are treated as unix timestamps */
		if (is_int($date))
			return AnewtDateTime::parse_timestamp($date);

		/* Try to parse strings */
		if (is_string($date))
			return AnewtDateTime::parse_string($date);

		/* Double parsing should be harmless */
		if ($date instanceof AnewtDateTimeAtom)
			return $date;

		/* Too bad, better luck next time... */
		return null;
	}

	/**
	 * Converts a timestamp into a AnewtDateTimeAtom instance.
	 *
	 * \param $timestamp
	 *   Timestamp to use.
	 *
	 * \return
	 *   A newly created AnewtDateTimeAtom instance or null if parsing failed.
	 */
	static function parse_timestamp($timestamp)
	{
		assert('is_int($timestamp);');

		list ($y, $m, $d, $h, $i, $s) = explode(' ', date('Y m d H i s', $timestamp));
		$d = new AnewtDateTimeAtom((int) $y, (int) $m, (int) $d, (int) $h, (int) $i, (int) $s);
		return $d;
	}

	/**
	 * Parses a date string and returns a AnewtDateTimeAtom instance. This method
	 * supports many common date and time formats, including SQL's DATETIME and
	 * several ISO 8601 forms.
	 *
	 * \param $date
	 *   A string specifying a date.
	 *
	 * \return
	 *   A newly created AnewtDateTimeAtom instance or null if parsing failed.
	 */
	static function parse_string($date)
	{
		assert('is_string($date)');

		/* No date given? */
		if (strlen($date) == 0)
			return null;


		/* Defaults */

		$y = false; $m = 1; $d = 1; // date
		$h = 0; $i = 0; $s = 0; // time


		/* Date only */

		// ISO 8601: 2005
		$pattern = sprintf('/^(%s)$/', DATETIME_RE_YEAR);
		if (preg_match($pattern, $date, $matches) === 1)
			$y = $matches[1];


		// ISO 8601: 2005-10
		$pattern = sprintf('/^(%s)-?(%s)$/', DATETIME_RE_YEAR,
				DATETIME_RE_MONTH);
		if (preg_match($pattern, $date, $matches))
		{
			$y = $matches[1];
			$m = $matches[3];
		}

		// ISO 8601: 2005-10-31
		$pattern = sprintf('/^(%s)-?(%s)-?(%s)$/', DATETIME_RE_YEAR,
				DATETIME_RE_MONTH, DATETIME_RE_DAY);
		if (preg_match($pattern, $date, $matches) === 1)
		{
			$y = $matches[1];
			$m = $matches[3];
			$d = $matches[5];
		}


		/* Time only */

		/* Time without seconds */
		$pattern = sprintf('/^(%s):(%s)$/', DATETIME_RE_HOUR, DATETIME_RE_MINUTE);
		if (preg_match($pattern, $date, $matches) === 1)
		{
			list ($y, $m, $d) = explode('-', strftime('%Y-%m-%d'));
			$y = (int) $y;
			$m = (int) $m;
			$d = (int) $d;
			$h = $matches[1];
			$i = $matches[3];
			$s = 0;
		}

		/* Time with seconds */
		$pattern = sprintf('/^(%s):(%s):(%s)$/', DATETIME_RE_HOUR,
				DATETIME_RE_MINUTE, DATETIME_RE_SECOND);
		if (preg_match($pattern, $date, $matches) === 1)
		{
			list ($y, $m, $d) = explode('-', strftime('%Y-%m-%d'));
			$y = (int) $y;
			$m = (int) $m;
			$d = (int) $d;
			$h = $matches[1];
			$i = $matches[3];
			$s = $matches[5];
		}


		/* Date and time */

		// SQL92 DATETIME: 2005-10-31 12:00:00
		$pattern = sprintf('/^(%s)-(%s)-(%s) (%s):(%s):(%s)(\.\d+)?$/', DATETIME_RE_YEAR,
				DATETIME_RE_MONTH, DATETIME_RE_DAY, DATETIME_RE_HOUR, DATETIME_RE_MINUTE,
				DATETIME_RE_SECOND);
		if (preg_match($pattern, $date, $matches) === 1)
		{
			$y = $matches[1];
			$m = $matches[3];
			$d = $matches[5];
			$h = $matches[7];
			$i = $matches[9];
			$s = $matches[11];
		}

		// ISO 8601: 2005-10-31T12:00:00 (with and without T)
		$pattern = sprintf('/^(%s)-?(%s)-?(%s)[T ](%s):?(%s):?(%s)$/',
				DATETIME_RE_YEAR, DATETIME_RE_MONTH, DATETIME_RE_DAY,
				DATETIME_RE_HOUR, DATETIME_RE_MINUTE, DATETIME_RE_SECOND);
		if (preg_match($pattern, $date, $matches) === 1)
		{
			$y = $matches[1];
			$m = $matches[3];
			$d = $matches[5];
			$h = $matches[7];
			$i = $matches[9];
			$s = $matches[11];
		}

		// ISO 8601: 2005-10-31T12:00 (with and without T)
		$pattern = sprintf('/^(%s)-?(%s)-?(%s)[T ](%s):?(%s)$/',
				DATETIME_RE_YEAR, DATETIME_RE_MONTH, DATETIME_RE_DAY,
				DATETIME_RE_HOUR, DATETIME_RE_MINUTE);
		if (preg_match($pattern, $date, $matches) === 1)
		{
			$y = $matches[1];
			$m = $matches[3];
			$d = $matches[5];
			$h = $matches[7];
			$i = $matches[9];
		}

		// ISO 8601: 2005-123
		$pattern = sprintf('/^(%s)-?(%s)$/', DATETIME_RE_YEAR, DATETIME_RE_DAY_OF_YEAR);
		if (preg_match($pattern, $date, $matches) === 1)
		{
			$y = $matches[1];
			$timestamp = mktime(0, 0, 0, 1, $matches[3], $matches[1]);
			$m = (int) strftime('%m', $timestamp);
			$d = (int) strftime('%d', $timestamp);
		}

		// ISO 8601: 2005-W02
		/* TODO: Dates specified by year, week and day. */


		// MS SQL default format: Mon Jan 23 00:00:00 2006
		$pattern = sprintf('/^%s,? %s %s %s:?%s:?%s %s$/',
				DATETIME_RE_DAY_NAMES_ABBR, DATETIME_RE_MONTH_NAMES_ABBR,
				DATETIME_RE_DAY, DATETIME_RE_HOUR, DATETIME_RE_MINUTE,
				DATETIME_RE_SECOND, DATETIME_RE_YEAR);
		if (preg_match($pattern, $date, $matches) === 1)
		{
			$y = $matches[7];
			$month_names_abbr = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May',
				'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
			$month_names_abbr = array_flip($month_names_abbr);
			$m = $month_names_abbr[$matches[2]];
			$d = $matches[3];

			$h = $matches[4];
			$i = $matches[5];
			$s = $matches[6];
		}
		
		// RFC 2822 format: Tue, 10 Jun 2003 04:00:00 GMT
		$pattern = sprintf('/^%s,? %s %s %s %s:%s:%s %s$/',
				DATETIME_RE_DAY_NAMES_ABBR, DATETIME_RE_DAY,
				DATETIME_RE_MONTH_NAMES_ABBR, DATETIME_RE_YEAR,
				DATETIME_RE_HOUR, DATETIME_RE_MINUTE,
				DATETIME_RE_SECOND, DATETIME_RE_TIMEZONE);
		if (preg_match($pattern, $date, $matches) === 1)
		{
			$y = $matches[4];
			$month_names_abbr = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May',
					'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
			$month_names_abbr = array_flip($month_names_abbr);
			$m = $month_names_abbr[$matches[3]];
			$d = $matches[2];
			
			$h = $matches[5];
			$i = $matches[6];
			$s = $matches[7];
		}

		/* Parsing done */

		if (!AnewtDateTime::is_valid_date_ymd($y, $m, $d))
			return null;

		if (!AnewtDateTime::is_valid_time_hms($h, $i, $s))
			return null;

		return new AnewtDateTimeAtom($y, $m, $d, $h, $i, $s);
	}

	/**
	 * Parse year, month and day integers and return a AnewtDateTimeAtom
	 * instance. This method is useful if you want to transform year, month and
	 * day values into an AnewtDateTimeAtom instance.
	 *
	 * \param $year
	 *   The year (integer)
	 *
	 * \param $month
	 *   The month (integer, defaults to 1 if omitted)
	 *
	 * \param $day
	 *   The day (integer, defaults to 1 if omitted)
	 *
	 * \return
	 *   A newly created AnewtDateTimeAtom instance or null if parsing failed.
	 */
	static function parse_ymd($year, $month=1, $day=1)
	{
		assert('is_int($year)');
		assert('is_int($month)');
		assert('is_int($day)');

		if (!AnewtDateTime::is_valid_date_ymd($year, $month, $day))
			return null;

		return new AnewtDateTimeAtom($year, $month, $day);
	}

	/** \} */


	/** \{
	 * \name Validating and checking methods
	 */

	/**
	 * Check if a date (without time) is valid.
	 *
	 * \param $year
	 *   Year
	 * \param $month
	 *   Month
	 * \param $day
	 *   Day
	 *
	 * \return
	 *   True if this is a valid date, false otherwise.
	 */
	static function is_valid_date_ymd($year, $month, $day)
	{
		return checkdate($month, $day, $year); // note the weird parameter order!
	}

	/**
	 * Check if a time (without date) is valid.
	 *
	 * \param $hour
	 *   Hour
	 * \param $minute
	 *   Minute
	 * \param $second
	 *   Second
	 *
	 * \return
	 *   True if this is a valid time, false otherwise.
	 */
	static function is_valid_time_hms($hour, $minute, $second)
	{
		return ($hour >= 0) && ($hour < 24)
			&& ($minute >= 0) && ($minute < 60)
			&& ($second >= 0) && ($second < 60);
	}

	/**
	 * Checks if a given date is valid.
	 *
	 * \param $date
	 *   Any date-like object; this could be a string, timestamp or
	 *   a AnewtDateTimeAtom instance.
	 *
	 * \return
	 *   True if this is a valid date, false otherwise.
	 */
	static function is_valid($date)
	{
		assert('$date instanceof AnewtDateTimeAtom;');

		return AnewtDateTime::is_valid_date_ymd(
				AnewtDateTime::year($date),
				AnewtDateTime::month($date),
				AnewtDateTime::day($date)
				)
			&& AnewtDateTime::is_valid_time_hms(
				AnewtDateTime::hour($date),
				AnewtDateTime::minute($date),
				AnewtDateTime::second($date)
				);
	}

	/**
	 * Checks if a date is in the current year.
	 *
	 * \param $date
	 *   A date-like object.
	 *
	 * \return
	 *   True if the given date is in the current year, false otherwise.
	 */
	static function is_current_year($date)
	{
		return AnewtDateTime::year($date) === (int) date('Y');
	}

	 /**
	  * Checks if a date is the same date as today.
	  *
	  * \param $date
	  *   A date-like object.
	  *
	  * \return
	  *   True if the date is today, false otherwise.
	  */
	static function is_today($date)
	{
		return (AnewtDateTime::year($date) === (int) date('Y'))
			&& (AnewtDateTime::month($date) === (int) date('m'))
			&& (AnewtDateTime::day($date) === (int) date('d'));
	}


	/** \} */

	/** \{
	 * \name Formatting methods
	 */

	/**
	 * Get the year.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   The year of the given date.
	 */
	static function year($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return $date->year;
	}

	/**
	 * Get the month.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   The month of the given date.
	 */
	static function month($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return $date->month;
	}

	/**
	 * Get the day.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   The day of the given date.
	 */
	static function day($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return $date->day;
	}

	/**
	 * Get the hours.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   The hours of the given date.
	 */
	static function hour($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return $date->hour;
	}

	/**
	 * Get the minutes.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   The minutes of the given date.
	 */
	static function minute($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return $date->minute;
	}

	/**
	 * Get the seconds.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   The seconds of the given date.
	 */
	static function second($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return $date->second;
	}

	/**
	 * Get a timestamp.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A timestamp representing the given date.
	 */
	static function timestamp($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return $date->timestamp();
	}

	/**
	 * Formats a date in strftime()-like format.
	 *
	 * \param $format
	 *   A format specifier in strftime() syntax.
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A formatted date representation.
	 */
	static function format($format, $date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');
		assert('is_string($format)');

		return strftime($format, $date->timestamp());
	}

	/**
	 * Get the date only. Example: 1983-01-15.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in yyyy-mm-dd format.
	 */
	static function date($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return strftime('%Y-%m-%d', $date->timestamp());
	}

	/**
	 * Get the time only. Example: 18:30:00.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in hh:mm:ss format.
	 */
	static function time($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return strftime('%H:%M:%S', $date->timestamp());
	}

	/**
	 * Get the week number. Example: 1983-W02.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   An integer denoting the week number of this year.
	 */
	static function week($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return strftime('%V', $date->timestamp());
	}

	/**
	 * Get the day number in the year. Example: 15.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   An integer denoting the day number of this year.
	 */
	static function day_of_year($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return (int) strftime('%j', $date->timestamp());
	}

	/**
	 * Format the date in standard ISO 8601 format. Example:
	 * 1983-01-15T18:30:00.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in yyyy-mm-ddThh:mm:ss format.
	 */
	static function iso8601($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return strftime('%Y-%m-%dT%H:%M:%S', $date->timestamp());
	}

	/**
	 * Format the date in compact ISO 8601 format. Example: 19830115T183000.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in yyyymmddThhmmss format.
	 */
	static function iso8601_compact($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return strftime('%Y%m%dT%H%M%S', $date->timestamp());
	}

	/**
	 * Format the date in compact ISO 8601 date format. Example: 19830115.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in yyyymmdd format.
	 */
	static function iso8601_date_compact($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return strftime('%Y%m%d', $date->timestamp());
	}

	/**
	 * Format the date in compact ISO 8601 time format. Example: 183000.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in hhmmss format.
	 */
	static function iso8601_time_compact($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return strftime('%H%M%S', $date->timestamp());
	}

	/**
	 * Format the date in ISO 8601 week format. Example: 1983-W02.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in yyyy-Wnn format.
	 */
	static function iso8601_week($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return strftime('%G-W%V', $date->timestamp());
	}

	/**
	 * Format the date in ISO 8601 week and day format. Example: 1983-W02-6.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in yyyy-Wnn-m format.
	 */
	static function iso8601_week_day($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return strftime('%G-W%V-%u', $date->timestamp());
	}

	/**
	 * Format the date in ISO 8601 year-day format. Example: 1983-015.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in yyyy-nnn format.
	 */
	static function iso8601_day_of_year($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return strftime('%Y-%j', $date->timestamp());
	}

	/**
	 * Format the date in RFC 2922 format. Example: Sat, 15 Jan 1983 18:30:00
	 * +0100. This format can be used for e-mail messages or RSS 2 feeds.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in yyyy-nnn format.
	 */
	static function rfc2822($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return date('r', $date->timestamp());
	}

	/**
	 * Format the date in SQL92 DATETIME format. Example: 1983-01-15 18:30:00.
	 * This format can be used in SQL queries.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in yyyy-mm-dd hh:mm:ss format.
	 * 
	 * \see AnewtDateTime::sql_date
	 * \see AnewtDateTime::sql_time
	 */
	static function sql($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return strftime('%Y-%m-%d %H:%M:%S', $date->timestamp());
	}

	/**
	 * Format the date in SQL92 DATE format. Example: 1983-01-15. This format
	 * can be used in SQL queries.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in yyyy-mm-dd format.
	 *
	 * \see AnewtDateTime::sql
	 * \see AnewtDateTime::sql_time
	 */
	static function sql_date($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return AnewtDateTime::date($date);
	}

	/**
	 * Format the date in SQL92 TIME format. Example: 18:30:00. This format can
	 * be used in SQL queries.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in hh:mm:ss format.
	 * 
	 * \see AnewtDateTime::sql
	 * \see AnewtDateTime::sql_date
	 */
	static function sql_time($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return AnewtDateTime::time($date);
	}

	/**
	 * Get the swatch internet time beats.
	 *
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A string in <code>\@000</code> format.
	 */
	static function beat($date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');

		return date('@B', $date->timestamp());
	}

	/**
	 * Formats a date, using an alternative format if the given date is in the
	 * current year.
	 *
	 * \param $format_if_current_year
	 *   A format specifier in strftime() syntax, used if the date is in the
	 *   current year.
	 * \param $format_if_not_current_year
	 *   A format specifier in strftime() syntax, used if the date is not in the
	 *   current year.
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A formatted date representation.
	 *
	 * \see AnewtDateTime::format
	 * \see AnewtDateTime::format_if_today
	 */
	static function format_if_current_year($format_if_current_year, $format_if_not_current_year, $date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');
		assert('is_string($format_if_current_year)');
		assert('is_string($format_if_not_current_year)');

		if (AnewtDateTime::is_current_year($date))
			return strftime($format_if_current_year, $date->timestamp());
		else
			return strftime($format_if_not_current_year, $date->timestamp());
	}

	/**
	 * Formats a date, using an alternative format if the given date is the same
	 * day as today.
	 *
	 * \param $format_if_today
	 *   A format specifier in strftime() syntax, used if the date is today.
	 * \param $format_if_not_today
	 *   A format specifier in strftime() syntax, used if the date is not today.
	 * \param $date
	 *   A date-like object (optional).
	 *
	 * \return
	 *   A formatted date representation.
	 *
	 * \see AnewtDateTime::format
	 * \see AnewtDateTime::format_if_current_year
	 */
	static function format_if_today($format_if_today, $format_if_not_today, $date)
	{
		if (is_null($date))
			return null;

		assert('$date instanceof AnewtDateTimeAtom;');
		assert('is_string($format_if_today)');
		assert('is_string($format_if_not_today)');

		if (AnewtDateTime::is_today($date))
			return strftime($format_if_today, $date->timestamp());
		else
			return strftime($format_if_not_today, $date->timestamp());
	}

	/** \} */
}

?>
