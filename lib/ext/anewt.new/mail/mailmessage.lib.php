<?php

/*
 * Anewt, Almost No Effort Web Toolkit, mail module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


/**
 * Representation of an email message. This class provides both functionality
 * for reading and sending email messages.
 *
 * \todo
 *   Header/body formatting so that it can be used correctly to output
 *   a complete mail message.
 *
 * \todo
 *   What to do with multipart messages?
 *
 * \todo
 *   What to do with different encodings?
 */
class AnewtMailMessage extends AnewtContainer
{
	/* Static methods for parsing messages */

	/**
	 * Creates a new message by parsing a string containing a complete mail
	 * message. The headers are parsed and stored; the body is left intact.
	 *
	 * \param $data
	 *   A string with a complete mail message
	 *
	 * \return
	 *   A new AnewtMailMessage instance
	 */
	static public function from_string($data)
	{
		/* Sanity check */
		assert('is_string($data)');

		/* New message */
		$message = new AnewtMailMessage();

		/* Split headers from body */
		list ($headers, $body) = preg_split('/\n{2,}/', $data, 2);

		/* Parse headers and body */
		$message->parse_headers($headers);
		$message->parse_body($body);

		/* Parsed messages should not be sent out again (well, not without
		 * explicit telling that they should be) */
		$message->set('can-send', false);

		/* Return the message */
		return $message;
	}

	/**
	 * Creates a new message by reading from a stream containing a complete mail
	 * message. The headers are parsed and stored; the body is left intact.
	 *
	 * \param $stream
	 *   An open stream resource, eg. from fopen()
	 *
	 * \return
	 *   A new AnewtMailMessage instance
	 */
	static public function from_stream($stream)
	{
		$data = stream_get_contents($stream);
		$message = AnewtMailMessage::from_string($data);
		return $message;
	}

	/**
	 * Creates a new message by reading from a file containing a complete mail
	 * message. The headers are parsed and stored; the body is left intact.
	 *
	 * \param $filename
	 *   The filename of the file to read
	 *
	 * \return
	 *   A new AnewtMailMessage instance
	 */
	static public function from_file($filename)
	{
		assert('is_string($filename)');
		$data = file_get_contents($filename);
		$message = AnewtMailMessage::from_string($data);
		return $message;
	}


	/* Instance methods and variables */

	/**
	 * Object holding all header data
	 */
	private $headers;

	/**
	 * Initializes a new AnewtMailMessage instance.
	 */
	public function __construct()
	{
		$this->headers = new AnewtContainer();
		
		/* Default settings */
		$this->_seed(array(
			'can-send' => true,
		));
	}


	/* Body related methods */

	/**
	 * Parses the raw body of a message and stores the result in this message.
	 * This method should only be called once during parsing. Bodies that are
	 * encoded using as 'quoted-printable' are converted to normal strings.
	 *
	 * \param $body
	 *   A string that contains the body text
	 *
	 */
	private function parse_body($body)
	{
		/* Convert quoted-printable message bodies */
		if ($this->has_header('Content-Transfer-Encoding')
				&& ($this->get_header('Content-Transfer-Encoding') == 'quoted-printable'))
			$body = quoted_printable_decode($body);

		/* Store the body */
		$this->set('body', $body);
	}


	/* Header related methods */

	/**
	 * Adds a header to the message instance. If the header already existed, the
	 * value is converted to an array and the new values is appended.
	 *
	 * \param $name
	 *   The name of the header
	 *
	 * \param $value
	 *   The value of the header
	 */
	public function add_header($name, $value)
	{
		/* New header name */
		if (!$this->headers->is_set($name))
			$this->headers->set($name, $value);

		/* Already existing header name */
		else
		{
			$current = $this->headers->get($name);

			/* Convert string into array */
			if (is_string($current))
				$this->headers->set($name, array($current));

			/* Add to array */
			$this->headers->add($name, $value);
		}
	}

	/**
	 * Returns the specified header.
	 *
	 * \param $name
	 *   The name of the header
	 *
	 * \return
	 *   A string containing the header value or an array containing multiple
	 *   string values (if the header occurred more than once)
	 */
	public function get_header($name)
	{
		return $this->headers->get($name);
	}

	/**
	 * Checks if a header is defined.
	 *
	 * \param $name
	 *   The name of the header
	 *
	 * \return
	 *   True if the header was found, false otherwise
	 *
	 */
	public function has_header($name)
	{
		return $this->headers->is_set($name);
	}

	/**
	 * Parses raw headers and stores them in this message. This method should
	 * only be called once during parsing.
	 *
	 * \param $headers
	 *   A string or array that contains the headers to be parsed
	 */
	private function parse_headers($headers)
	{
		/* Split string into an array */
		if (is_string($headers))
			$headers = explode("\n", trim($headers));

		assert('is_array($headers)');

		/** Ignore "^From .* if it's the first line (mbox) */
		if ($headers && str_has_prefix($headers[0], 'From '))
			array_shift($headers);

		/* Loop over all lines */
		while (true)
		{
			/* No more lines? */
			if (!$headers)
				break;

			$line = array_shift($headers);

			assert ('strpos($line, ":") !== false');

			/* New header line */
			list ($name, $value) = explode(':', $line, 2);

			/* Peek at next lines to see if this is a multi-line header. If it
			 * is, concatenate the value to the existing value. */
			while ($line = array_shift($headers))
			{
				if (str_has_whitespace_prefix($line))
				{
					/* Multi-line header detected */
					$value .= ' ' . ltrim($line);

				} else {
					/* No multi-line. Put the header back in the list before
					 * to allow further processing */
					array_unshift($headers, $line);
					break;
				}
			}

			/* Sanitize values */
			$name = trim($name);
			$value = trim($value);

			/* Store the header */
			$this->add_header($name, $value);
		}
	}


	/* Mail sending */

	/**
	 * Sends the mail message. This method tries to use the sendmail binary
	 * directly (falls back to the PHP mail() function) to actually send the
	 * message. Note that only messages which have the 'can-send' attribute set
	 * to true are considered for sending. Messages that were parsed from
	 * a file, stream or a string (using the static methods of this class) will
	 * have this attribute set to false, because sending a message you just
	 * parsed is not generally what you want.
	 *
	 * \param $force
	 *   Optional parameter to force sending of non-sendable messages
	 *
	 * \return
	 *   True if the message was sent successfully, false if an error occurred
	 *
	 * \todo
	 *   Handle name/address rfc822 addresses correctly
	 *
	 * \todo
	 *   Include all headers instead of just a few
	 */
	public function send($force=false)
	{
		if (!($this->get('can-send') || $force))
			return false;

		$from = $this->get_header('From');
		$to = $this->get_header('To');
		$subject = $this->get_header('Subject');
		$body = $this->get('body');

		$headers = array();
		$headers[] = sprintf('From: %s', $from);
		$headers[] = sprintf('To: %s', $to);
		$headers[] = sprintf('Subject: %s', $subject);

		if ($this->has_header('Cc')) $headers[] = sprintf('Cc: %s', $this->get_header('Cc'));
		if ($this->has_header('Bcc')) $headers[] = sprintf('Bcc: %s', $this->get_header('Bcc'));

		$message = implode("\r\n", $headers) . "\n\n" . $body;

		/* Try to use the sendmail binary directly */
		if (is_executable('/usr/sbin/sendmail'))
		{
			$command = sprintf('/usr/sbin/sendmail -t -i -f %s', escapeshellarg($from));
			$fd = @popen($command, 'w');
			if ($fd)
			{
				fwrite($fd, $message, strlen($message));
				$status = pclose($fd);
				$exit_code = ($status >> 8) & 0xFF;
				if ($exit_code == 0)
					return true;
			}
		}


		/* Fallback to the PHP mail() function */
		$additional_headers = sprintf('From: %s', $from);
		$result = mail(
				$to,
				$subject,
				$body,
				$additional_headers);

		return $result;
	}
}

?>
