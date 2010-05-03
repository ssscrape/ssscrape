<?php

/*
 * Anewt, Almost No Effort Web Toolkit, rss module
 *
 * This code is copyrighted and distributed under the terms of the GNU LGPL.
 * See the README file for more information.
 */


anewt_include('datetime');
anewt_include('xml/dom');


mkenum(
	'ANEWT_RSS_ELEMENT_STATUS_REQUIRED',
	'ANEWT_RSS_ELEMENT_STATUS_OPTIONAL'
);

mkenum(
	'ANEWT_RSS_ELEMENT_TYPE_STRING',
	'ANEWT_RSS_ELEMENT_TYPE_CANONICAL_URL',
	'ANEWT_RSS_ELEMENT_TYPE_INTEGER',
	'ANEWT_RSS_ELEMENT_TYPE_DATE'
);


/**
 * RSS channel (feed).
 *
 * AnewtRssChannel instances handle a number of properties , which you can set
 * using regular Container::set() method calls. These will end up as elements of
 * your RSS channel.
 *
 * The required properties are:
 *
 * - \c title
 * - \c link
 * - \c description
 *
 * The optional properties are:
 *
 * - \c language
 * - \c copyright
 * - \c editor
 * - \c webmaster
 * - \c date
 * - \c build-date
 * - \c generator
 * - \c time-to-live
 * - \c image
 *
 * The \c url property can be used to set a custom URL which will be included in
 * a <code>atom:link</code> element, as suggested by the FeedValidator people.
 * The default url detection will work in most cases though, so there's usually
 * no need to fiddle with this.
 *
 * You can specify the text encoding used with the \c encoding property. This
 * defaults to UTF-8. The Anewt RSS module does not do character set conversion,
 * so you should make sure your values are in the right character set before you
 * set them on the AnewtRssChannel and AnewtRssItem instances.
 *
 * There is also a \c content-type property that is set to the correct MIME
 * type, i.e. <code>application/rss+xml</code>. You should not override this
 * except for testing, e.g. temporarily serving as <code>text/html</code> helps
 * with debugging your feed. Even though there are lots of <code>text/xml</code>
 * feeds on the web, you should really stick to application/rss+xml. No cookies
 * for you if you change it for production use!
 *
 * After setting the channel properties, you can add AnewtRssItem instances to
 * it using AnewtRssChannel::add_item(). Finally, you output the complete
 * channel using AnewtRssChannel::flush().
 *
 * \see AnewtRssItem
 */
class AnewtRssChannel extends Container
{
	/** \{
	 * \name Static helper methods
	 */

	/**
	 * \private
	 *
	 * Helper function to create RSS XML elements.
	 *
	 * This is only for internal use.
	 *
	 * \param $obj
	 *   The object from which to retrieve the value
	 *
	 * \param $property
	 *   The property name
	 *
	 * \param $tagname
	 *   The XML tag name
	 *
	 * \param $status
	 *   Status of the element
	 *
	 * \param $type
	 *   Data type of the element
	 *
	 * \return
	 *   An AnewtXMLDomElement instance, or null.
	 */
	public static function _build_rss_element($obj, $property, $tagname, $status, $type)
	{
		if (!$obj->_isset($property))
		{
			/* If an optional element not provided it's not a problem... */
			if ($status == ANEWT_RSS_ELEMENT_STATUS_OPTIONAL)
				return null;

			/* ...but required means required! */
			throw new AnewtException(
				'AnewtRssItem::render(): Required property "%s" not set.',
				$property);
		}

		$value = $obj->_get($property);

		switch ($type) {
			case ANEWT_RSS_ELEMENT_TYPE_STRING:
				assert('is_string($value);');
				break;

			case ANEWT_RSS_ELEMENT_TYPE_CANONICAL_URL:
				assert('is_string($value);');
				if (str_has_prefix($value, '/'))
				{
					/* Fixup relative URL's without a http://hostname.ext/ part */
					$value = Request::canonical_base_url() . $value;
				}
				break;

			case ANEWT_RSS_ELEMENT_TYPE_INTEGER:
				assert('is_int($value);');
				$value = (string) $value;
				break;

			case ANEWT_RSS_ELEMENT_TYPE_DATE:
				assert('$value instanceof AnewtDateTimeAtom;');
				$value = AnewtDateTime::rfc2822($value);
				break;

			default:
				assert('false; // not reached');
				break;
		}

		$element = new AnewtXMLDomElement($tagname);
		$element->append_child(new AnewtXMLDomText($value));
		return $element;
	}

	/** \} */

	/** List of properties and their specification */
	private $properties = array(

		/* XXX: Keep this in sync with the documentation block above! */

		/* Required */
		'title'           => array('title',          ANEWT_RSS_ELEMENT_STATUS_REQUIRED, ANEWT_RSS_ELEMENT_TYPE_STRING),
		'link'            => array('link',           ANEWT_RSS_ELEMENT_STATUS_REQUIRED, ANEWT_RSS_ELEMENT_TYPE_CANONICAL_URL),
		'description'     => array('description',    ANEWT_RSS_ELEMENT_STATUS_REQUIRED, ANEWT_RSS_ELEMENT_TYPE_STRING),

		/* Optional */
		'language'        => array('language',       ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_STRING),
		'copyright'       => array('copyright',      ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_STRING),
		'editor'          => array('managingEditor', ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_STRING),
		'webmaster'       => array('webMaster',      ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_STRING),
		'date'            => array('pubDate',        ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_DATE),
		'build-date'      => array('lastBuildDate',  ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_DATE),
		'generator'       => array('generator',      ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_STRING),
		'time-to-live'    => array('ttl',            ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_INTEGER),
		'image'           => array('image',          ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_STRING),
	);

	/** List of items in this feed */
	private $items = array();


	/**
	 * Create a new channel object.
	 *
	 * For convenience, this constructor takes three parameters that must be set
	 * according to the RSS specification. If you don't set them here, you're
	 * supposed to provided them later by calling the set() method.
	 *
	 * \param $title
	 *   The channel's title (optional).
	 *
	 * \param $link
	 *   The channel's link (optional).
	 *
	 * \param $description
	 *   The channel's description (optional).
	 */
	public function __construct($title=null, $link=null, $description=null)
	{
		$this->_seed(array(
			/* Default content-type is application/rss+xml */
			'content-type' => 'application/rss+xml',

			/* Default encoding is UTF-8 */
			'encoding'     => 'UTF-8',

			/* Figure out URL automatically by default */
			'url'          => null,

			/* Shameless self-promotion */
			'generator'    => 'Anewt RSS module',
		));

		if (!is_null($title)) {
			assert('is_string($title);');
			$this->_set('title', $title);
		}

		if (!is_null($link)) {
			assert('is_string($link);');
			$this->_set('link', $link);
		}

		if (!is_null($description)) {
			assert('is_string($description);');
			$this->_set('description', $description);
		}
	}

	/**
	 * Add an AnewtRssItem to the channel.
	 *
	 * \param $item
	 *   A AnewtRssItem instance.
	 */
	public function add_item($item)
	{
		assert('$item instanceof AnewtRssItem');
		$this->items[] = $item;
	}

	/**
	 * Build an XML document for this channel
	 */
	private function build_document()
	{
		/* Build the channel and description properties*/

		$channel = new AnewtXMLDomElement('channel');

		foreach ($this->properties as $property => $property_spec)
		{
			list ($tagname, $status, $type) = $property_spec;
			$element = AnewtRssChannel::_build_rss_element($this, $property, $tagname, $status, $type);

			if (is_null($element))
				continue;

			$channel->append_child($element);
			unset ($element);
		}


		/* Always add 'docs' element */

		$d = new AnewtXMLDomElement('docs');
		$d->append_child(new AnewtXMLDomText('http://www.rssboard.org/rss-specification'));
		$channel->append_child($d);

		
		/* Add an atom:link element. Only call the canonical_url() method if no
		 * explicit url was provided. */

		$url = $this->_get('url');
		if (is_null($url))
			$url = Request::canonical_url();

		$channel->append_child(new AnewtXMLDomElement(
			'atom:link',
			array(
				'href' => $url,
				'rel' => 'self',
				'type' => $this->_get('content-type'),
			)));


		/* Loop over items in the channel and append those */

		foreach ($this->items as $item)
			$channel->append_child($item->_build_element());


		/* Final output */

		$document = new AnewtXMLDomDocument();
		$document->set_content_type($this->_get('content-type'));
		$document->set_encoding($this->_get('encoding'));

		$rss = new AnewtXMLDomElement('rss', array(
			'version' => '2.0',
			'xmlns:atom' => 'http://www.w3.org/2005/Atom',
			));

		$rss->append_child($channel);
		$document->append_child($rss);

		return $document;
	}

	/**
	 * Output the channel to a browser.
	 *
	 * This renders the page to the browser, including the correct
	 * <code>Content-type</code> headers and XML prolog.
	 */
	public function flush()
	{
		$document = $this->build_document();
		$document->flush();
	}
}


/**
 * RSS item.
 *
 * AnewtRssItem instances handle a number of properties, which you can set using
 * regular Container::set() method calls.
 *
 * The required properties are:
 *
 * - \c title
 * - \c link
 * - \c description
 * 
 * The optional properties are:
 *
 * - \c author
 * - \c comments
 * - \c guid
 * - \c date
 *
 * After creating an item, you should add it to an AnewtRssChannel instance
 * using AnewtRssChannel::add_item().
 *
 * \see AnewtRssChannel
 * \see AnewtRssChannel::add_item
 */
class AnewtRssItem extends Container
{
	/** List of properties and their specification */
	private $properties = array(

		/* XXX: Keep this in sync with the documentation block above! */

		/* Required */
		'title'           => array('title',       ANEWT_RSS_ELEMENT_STATUS_REQUIRED, ANEWT_RSS_ELEMENT_TYPE_STRING),
		'link'            => array('link',        ANEWT_RSS_ELEMENT_STATUS_REQUIRED, ANEWT_RSS_ELEMENT_TYPE_CANONICAL_URL),
		'description'     => array('description', ANEWT_RSS_ELEMENT_STATUS_REQUIRED, ANEWT_RSS_ELEMENT_TYPE_STRING),

		/* Optional */
		'author'          => array('author',      ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_STRING),
		'comments'        => array('comments',    ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_STRING),
		'guid'            => array('guid',        ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_CANONICAL_URL),
		'date'            => array('pubDate',     ANEWT_RSS_ELEMENT_STATUS_OPTIONAL, ANEWT_RSS_ELEMENT_TYPE_DATE),
	);

	/**
	 * Construct a new Rss item instance.
	 *
	 * For convenience, this constructor takes three parameters that must be set
	 * according to the RSS specification. If you don't set them here, you're
	 * supposed to provided them later by calling the set() method.
	 *
	 * \param $title
	 *   The item's title.
	 *
	 * \param $link
	 *   The item's link.
	 *
	 * \param $description
	 *   The item description.
	 */
	function __construct($title=null, $link=null, $description=null)
	{
		if (!is_null($title)) {
			assert('is_string($title);');
			$this->_set('title', $title);
		}

		if (!is_null($link)) {
			assert('is_string($link);');
			$this->_set('link', $link);
		}

		if (!is_null($description)) {
			assert('is_string($description);');
			$this->_set('description', $description);
		}
	}

	/**
	 * \private
	 *
	 * Build an XML element representing this item.
	 *
	 * This method should not be called directly, it is only used by the
	 * AnewtRssChannel class.
	 *
	 * \return
	 *   A XML element representing this item.
	 */
	public function _build_element()
	{
		$item = new AnewtXMLDomElement('item');

		/* Loop over all properties of this item */
		foreach ($this->properties as $property => $property_spec)
		{
			list ($tagname, $status, $type) = $property_spec;
			$element = AnewtRssChannel::_build_rss_element($this, $property, $tagname, $status, $type);

			if (is_null($element))
				continue;

			$item->append_child($element);
			unset($element);
		}

		return $item;
	}
}

?>
