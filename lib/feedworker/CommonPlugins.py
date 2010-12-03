#!/usr/bin/env python

__doc__ = '''Common plugin code for the collection worker.'''

import os
import sys
import urllib2
import time
import getopt
import datetime
import httplib
import urlparse

import MySQLdb
from BeautifulSoup import BeautifulSoup

import feedparserx

import ssscrapeapi
import ssscrapeapi.feeds as feeds
import feedworker

def cleanText(t, hexreplace = False):
    try:
        t = unicode(t, 'utf-8')#.encode('utf-8', 'ignore')
    except ValueError:
        try:
            t = unicode(t, 'latin-1')#.encode('utf-8', 'ignore')
        except ValueError:
            #t = unicode(t, 'utf-16')#.encode('utf-8', 'ignore')
            pass
    if hexreplace:
        t = t.replace(u'"', u'%22')
        t = t.replace(u'`', u'%91')
        t = t.replace(u"'", u'%92')
    else:
        t = t.replace(u'"', u'&quot;')
        t = t.replace(u'`', u'&lsquo;')
        t = t.replace(u"'", u'&rsquo;')
        t = t.replace(u"\u2018", u"&rsquo;")
        t = t.replace(u"\u2019", u"&rsquo;")
        t = t.replace(u"\u201c", u'&qout;')
        t = t.replace(u"\u201d", u'&quot;')
        t = t.replace(u"\u203a", u"&rsaquo;")
        t = t.replace(u"\u2039", u"&lsaquo;")
        t = t.replace(u"\u2033", u"&Prime;")
        t = t.replace(u"\u20ac", u'&euro;')
        t = t.replace(u"\u2212", u'&minus;')
        t = t.replace(u"\u2026", u'&hellip;')
        t = t.replace(u"\u2013", u'&ndash;')
        t = t.replace(u"\u2014", u'&mdash;')
        t = t.replace(u"\u201e", u'&bdquo;')
        t = t.replace(u"\u2022", u'&bull;')
        t = t.replace(u"\u25ba", u'&bull;')
    return t

# Taken from : http://diveintopython.org/http_web_services/redirects.html

class SmartRedirectHandler(urllib2.HTTPRedirectHandler):
    def http_error_301(self, req, fp, code, msg, headers):
        result = urllib2.HTTPRedirectHandler.http_error_301(
            self, req, fp, code, msg, headers)
        result.status = code
        return result

    def http_error_302(self, req, fp, code, msg, headers):
        result = urllib2.HTTPRedirectHandler.http_error_302(
            self, req, fp, code, msg, headers)
        result.status = code
        return result

class DefaultPlugin:
    """DefaultPlugin is the base plugin from which all other plugins are derived.

    DefaultPlugin does nothing by itself, but defines a common interface for the plugins to do their
    thing in the collection worker.
    """
    def __init__(self, job):
        """Initialization of the plugin object."""
        self.job = job
        self.items_new = 0
        self.items_updated = 0
    # end def __init__

    def open(self, argv=None):
        """Opens the plugin, aka some more initialization.

        This method can be used as a more advanced initialization method. Also, it receives an object
        of the parsed configuration file as a paramter. The default plugin saves this value.
        """
        self.transaction = ssscrapeapi.database.cursor() # FIXME: not really the nicest way to do this ...
    # end def open

    def fetch(self):
        """Fetches a URL from the interwebs.

        fetch fetches a URL from the internet. It takes a URL as an argument. The expected output
        is a text string (eithr Unicode or not). 
        """
        self.contents = ""
        return self.contents
    # end def fetch

    def fetchclean(self, pageText):
        """Clean the fetched page before anything else happens.

        In some cases, plugins may need to clean the page text of tags, or convert the character set
        used in the page to another one. This is the place where such things can take place. It takes
        a text string as an argument (which is the contents of the page), and returns a transformed text
        string.
        """ 
        return pageText
    # end def fetchclean

    def parse(self, pageText):
        """Parses the fetched page into a collection with items.

        This is the main routine in the collection worker plugin. It is responsible for taking the contents
        of the fetched page, parse it and return a collection with items in it. It takes a text string as an
        input, which is the text of the fetched page, and returns a Collection object.
        """
        pass
    # end def parse

    def filter(self, feed, item):
        """Decides if an item must be filtered or not.

        After the collection and the items have been extracted from the fetched page, the items need to go
        through a filter, to decide if they are eligible for further processing, or not. This method gets
        a collection, and an item as an input, and returns a boolean value. True means that the item should
        pass the filter, whereas false means it should not.
        """
        return True
    # end def filter

    def clean(self, feed, item):
        """Cleans an individual item from a collection.

        This is a specialized method for cleaning the contents of a isngle item. The inputs of the function 
        are the collection, and the item, which is to be stored. Returns nothing.
        """
        pass
    # end def clean

    def process(self, feed, item):
        """Processes an item.

        After the items have been sent through the filter, it need processing. For example in this step you can
        run NER on the item. The item gets the collection, and an item as an input. It returns nothing.
        """
        pass
    # end def process

    def storefeed(self, feed):
        """Stores a collection.

        Next to storing the feed items, the feed itself should also be stored. This you can do here.
        """
        pass
    # end def storefeed

    def store(self, feed, item):
        """Stores an item.

        The end result of parsing, filtering and processing items is storing the actual items. Sotring can be done
        in a database, for example. The routine gets a collection, and an item as input. It returns nothing.
        """
        pass
    # end def store

    def close(self):
        """This method closes, or deinitializes the object.

        Use this method, if you want to destroy connections etc., when the plugin has done it's work.
        """ 
        pass 
    # end def close

    # helper routines for feed, items, enclosures etc.

    def instantiate(self, what, *args, **kwargs):
        '''
        Instantiates a specified object. This method can be overridden in child classes to control the type
        of objects for authors, etc. The current values for the what parameter can be:
        - 'author'
        - 'category'
        - 'enclosure'
        - 'enclosure_caption'
        - 'enclosure_credits'
        - 'enclosure_restriction'
        - 'enclosure_thumbnail'
        - 'feed'
        - 'feed_metadata'
        - 'feed_image'
        - 'feed_link'
        - 'feed_item'
        - 'feed_item_comment'
        - 'feed_item_event'
        - 'feed_item_link'
        - 'geo'
        - 'rating'
        '''

        if what == 'author':
            return feeds.Author(**kwargs)
        elif what == 'category':
            return feeds.Category(**kwargs)
        elif what == 'enclosure':
            return feeds.Enclosure(**kwargs)
        elif what == 'enclosure_caption':
            return feeds.EnclosureCaption(**kwargs)
        elif what == 'enclosure_credits':
            return feeds.EnclosureCredits(**kwargs)
        elif what == 'enclosure_restriction':
            return feeds.EnclosureRestriction(**kwargs)
        elif what == 'enclosure_thumbnail':
            return feeds.EnclosureThumbnail(**kwargs)
        elif what == 'feed':
            return feeds.Feed(**kwargs)
        elif what == 'feed_metadata':
            return feeds.FeedMetadata(**kwargs)
        elif what == 'feed_image':
            return feeds.FeedImage(**kwargs)
        elif what == 'feed_link':
            return feeds.FeedLink(**kwargs)
        elif what == 'feed_item':
            return feeds.FeedItem(**kwargs)
        elif what == 'feed_item_comment':
            return feeds.FeedItemComment(**kwargs)
        elif what == 'feed_item_event':
            return feeds.FeedItemEvent(**kwargs)
        elif what == 'feed_item_link':
            return feeds.FeedItemLink(**kwargs)
        elif what == 'geo':
            return feeds.Geo(**kwargs)
        elif what == 'rating':
            return feeds.Rating(**kwargs)
        elif what == 'task':
            return ssscrapeapi.Task(**kwargs)
        elif what == 'resource':
            return ssscrapeapi.Resource(**kwargs)
        elif what == 'job':
            return ssscrapeapi.Job(**kwargs)
        elif what == 'joblog':
            return ssscrapeapi.JobLog(**kwargs)
        elif what == 'jobhierarchy':
            return ssscrapeapi.JobHierarchy(**kwargs)
        elif what == 'jobtableitem':
            return ssscrapeapi.JobTableItem(**kwargs)
        else:
            raise Exception('Unknown type: %s' % what)

    def get_geo(self, lat, lng):
        '''
        Find the ID of a geographic point, expressed in longitude/latitude values.

        This routine finds the ID of a geo point, given the logitude and
        latitude. If the point is found, returns the ID of the point, 0
        otherwise.
        '''

        nlat = "%.5f" % float(lat)
        nlng = "%.5f" % float(lng)

        self.transaction.execute(
            'SELECT id FROM ssscrape_geo WHERE (latitude - %s) <= 0.00001 AND (longitude - %s) <= 0.00001 LIMIT 1',
            (nlat, nlng)
        )

        try:
            row = self.transaction.fetchone()
            return int(row[0])
        except (MySQLdb.Error, TypeError):
            return 0


    # routines for saving info
    def save_author(self, author):
        '''
        Saves the given author information in the database.

        Saves the given author into the database. Updates current information if the author is already in the
        database, and adds if it is not. Returns the author id that was allocated for the author.
        '''

        if not author:
            return

        try:
            fullname = author['fullname']
        except KeyError:
            fullname = None

        try:
            email = author['email']
        except KeyError:
            email = None

        if not fullname and not email:
            return

        aid = author.find()
        author.save()

        return author


    def save_feed_author(self, collection):
        '''
        Saves the author of a feed into the database.

        Saves the author of a feed into the database, first using
        C{_saveAuthor}, then adds the right mappings between the feed and the
        author.
        '''

        author = self.save_author(collection["author"])
        collection.add(author) 


    def save_feed_geo_info(self, collection):
        '''
        Saves the geographical information at feed level.

        Saves geographical information for the feed. First get a geographical identifier for the latitude/longitude pairs
        in the feed, then save the mappings.
        '''

        if self.save_geo_info(collection['geo']):
            collection.add(collection['geo'])

    def save_feed_image(self, collection):
        '''Save the image information of a feed.

        Saves the image information (<image> element in RSS feeds) to the database.'''

        if collection.has_key('image'):
          collection['image'].set_feed(collection)
          collection['image'].save()
    # end def save_feed_image

    def save_feed_info(self, collection):
        '''Saves the information for a feed into the database.

        This function saves the basic information of a feed into the database, and updates it if necessary.'''

        # if we haven't got an ID field yet,
        # try to find out the ID
        if not collection.has_key('id'):
            collection.retrieve(url=collection["url"])
        # end if

        collection.save()

        # save & associate metadata with feed
        try:
            if self.metadata:
                self.metadata.set_feed(self.collection)
                self.metadata.save()
        except AttributeError:
            pass
    # end def save_feed_info

    def save_feed_links(self, collection):
        '''Save the links associated with a feed into the DB.

        Saves the links at feed level into the database.'''

        # we must have a feed id
        if not collection.has_key('id'):
            raise FullContentError
        # end if

        # now save each link 
        for linkid in collection["links"]:
            link = collection["links"][linkid]
            # try to find an existing link
            link.retrieve(feed_id=collection['id'], link=link['link'])
            # set the feed id 
            link.set_feed(collection)
            # save it
            link.save()
        # end for
    # end def save_feed_links


    def save_geo_info(self, geo):
        '''
        Save a longitude/latitude pair to the database.

        Saves a longitude/latitude pair into the database. Returns the id if a
        save is succesful, c{None} otherwise.
        '''

        if not 'latitude' in geo and not 'longitude' in geo:
            return None

        # if we don't have an ID, try to find it
        if not geo.has_key('id'):
            i = self.get_geo(geo['latitude'], geo['longitude'])
            if i > 0:
                geo['id'] = i

        # if we really do not have an ID, we should add it to the DB
        if not geo.has_key('id'):
            # limit to 5 decimals
            geo['latitude'] = "%.5f" % (float(geo['latitude']))
            geo['longitude'] = "%.5f" % (float(geo['longitude']))

        geo.save()

        # return the id
        return geo['id']

    def save_item_author(self, collection, item):
        '''Save the author for a feed item.

        Saves the author for a feed item into the database. If there is no author at the item level,
        it simply assumes that the item is written by the author at the feed level (if available).'''

        if item['author']: 
            author = self.save_author(item["author"])
        elif collection.has_key('author'):
            author = self.save_author(collection["author"])
        else:
            author = None
        # end if

        # if the author save was succesful, add mappings
        if author:
            item.add(author)
    # end def save_item_author

    def save_item_categories(self, collection, item):
        '''Saves the categories of a feed item to the DB.

        This routine saves the categories (tags/whatever) to the database.'''

        if not item.has_key('categories'): return

        for category in item['categories']:
            # get the values
            term = category['term']
            try:
                scheme = category['scheme']
            except KeyError:
                scheme = collection['url'] 
            # end try
            if not scheme:
                scheme = collection['url']
            category['scheme'] = scheme
            try:
                label = category['label']
            except KeyError:
                label = term
            # end try
            category['label'] = label

            # now try to find if the category is available.
            cid = category.retrieve(term=category['term'], scheme=category['scheme'], type='c')

            # save the category
            category.save()

            # and associate with the feed item
            item.add(category)
        # end if
    # end def save_item_categories

    def save_item_geo_info(self, collection, item):
        '''Save geo information at feed level into the database.

        Saves theo geographical information at the item level into the database, and adds the mappings.'''

        # save geo information first
        x = self.save_geo_info(item['geo'])

        # if succesful, then add mappings
        if x > 0:
            item.add(item['geo'])
        # end if
    # end def save_item_geo_info

    def save_item_info(self, collection, item):
        '''Saves the basic information for a feed item into the DB.

        This routine saves the basic meta information for a feed item (like title, link etc.) to the database; updates
        if possible (Ie. if the feed item already has an id).'''

        # if we do not have an id yet, try to find it first!
        # and update counts
        if not item.has_key('id'):
            item_id = item.retrieve(guid=item['guid'], feed_id=collection['id'])
            if item_id > 0:
                self.items_updated = self.items_updated + 1
            else:
                self.items_new = self.items_new + 1
        else:
            self.items_updated = self.items_updated + 1
        # end if

        item.set_feed(collection)

        try:
            if isinstance(item["pub_date"], datetime.datetime):
                item_tuple = item["pub_date"].timetuple()
            else:
                item_tuple = item["pub_date"]
            item_time = time.strftime('%Y-%m-%d %H:%M:%S', item_tuple)
        except (TypeError, ValueError, KeyError):
            item_time = 'NOW()'
            item.unescaped.append('pub_date') 
        item['pub_date'] = item_time
        item['mod_date'] = 'NOW()'


        # translate unicode chars that cause problems when encoding
        # This is no longer needed: see revision 810 of lib/ssscrapeapi/database.py 
        #for field in ['title', 'summary', 'content', 'content_clean']:
        #   if item.has_key(field):
        #        item[field] = cleanText(item[field])

        item.save()
    # end def save_item_info

    def save_item_links(self, collection, item):
        '''Save the links for a feed item into the DB.

        This routine saves the links of a feed item into the database.'''

        # we must have a feed item id
        if not item.has_key('id'):
            raise FullContentError
        # end if

        for linkid in item["links"]:
            link = item["links"][linkid]
            # Ignore links with empty URLs
            if link['link']: 
                # try to find the (existing) id of the link
                link.retrieve(feed_item_id=item['id'], link=link['link'])
                link.set_item(item)
                 # save. Adds if new, updates if existing
                link.save()
        # end for
    # end def save_item_links

# end class DefaultPlugin

def _decode_arg(arg):
    try:
        t = arg.decode('utf-8')
    except UnicodeDecodeError:
        t = arg.decode('latin1')
    return t

class UrlPlugin(DefaultPlugin):
    '''
    A simple extension of the default plugin that collects the url from the internet.

    This plugin is not meant to be instantiated on its own. It's simply an extension of the
    default plugin that only overrides the fetch method, to provide a simple URL fetching capability.
    '''

    def open(self, argv=None):
        feedworker.CommonPlugins.DefaultPlugin.open(self, argv)
        try:
            opts, args = getopt.getopt(argv[1:], "hu:", ["help", "url"])
        except getopt.error, msg:
            raise feedworker.FeedWorkerUsage(msg)
        # end try
        # more code, unchanged
        # process options
        feedFile = None
        for o, a in opts:
            if o in ("-h", "--help"):
                print __doc__
                sys.exit(0)
            # end if
            if o in ("-u", "--url"):
                feedFile = _decode_arg(a)
            # end if
        # end for
        if feedFile:
            self.feedUrl = feedFile
        else:
            raise feedworker.FeedWorkerUsage(__doc__)
        # end if

    def fetch(self):
        '''Fetches the URL contents from the internet.'''
        #self.feedUrl = feedUrl
        self.contents = None
        f = None
        try:
            # quote non-ascii characters in URLs to percent encodings. See #119 in Trac.
            try:
                url = ssscrapeapi.misc.quote_url(self.feedUrl)
                opener = urllib2.build_opener(SmartRedirectHandler())
                req = urllib2.Request(url)
                f = opener.open(req) 
                # FIXME: doesn't this need a while loop? is read() guarantueed to read all data?
                self.contents = f.read()
                try:
                    self.http_status = f.status # HTTP status
                except AttributeError:
                    self.http_status = f.code
                self.http_url = f.geturl() # may be a redirect
            except httplib.BadStatusLine, e:
                raise feedworker.FeedWorkerException(1, feedworker.FeedWorkerException.KEYWORDS.NOCONNECTION)
        finally:
            if f != None: 
                f.close()

        return self.contents

class FeedPlugin(UrlPlugin):
    """The feed plugin is a base plugin for parsing feeds.

    The feed plugin is a base plugin for parsing feeds. It utilizes the Universal Feed Parsing plugin
    to perform generic parsing. Plugins may extend this base class to offer specific method for storing
    etc.
    """
    def _parseFeed(self, feed):
        try:
            title = feed.feed.title
        except StandardError:
            title = ''
        try:
            link = feed.feed.link
        except StandardError:
            link = ''
        collection = self.instantiate('feed', title=title, url=self.feedUrl)
        collection['feedurl'] = self.feedUrl
        collection['items'] = []
        for entry in feed.entries:
            item = self._parseEntry(feed, entry)
            collection['items'].append(item)
        # end for
        return collection
    # end def _parseFeed

    def _parseEntry(self, feed, entry):
        item = None
        title = None
        link = None
        desc = None

        try:
            title = entry.title
        except AttributeError:
            title = ''

        try:
            link = entry.link
        except AttributeError:
            link = ''

        try:
            desc = entry.summary
        except AttributeError:
            desc = ''

        try:
            item = self.instantiate('feed_item', guid=link, title=title, content=desc)
        except Exception, err:
            print >>sys.stderr, err.msg 

        return item

    def _redirect(self):
        # first, deal with metadata
        metadata2 = self.instantiate('feed_metadata', url=self.http_url)
        id = metadata2.find()
        if id > 0:
            # if we have a metadata record f/t url, then load it -- we want to ue the correct one
            metadata2.load(id)
            self.metadata = metadata2
        else:
            self.metadata['url'] = self.http_url
            self.metadata.save()

        # now deal with the feed entry in the table
        if self.metadata['feed_id']:
            feed = self.instantiate('feed')
            feed.load(self.metadata['feed_id'])
            if feed['url'] != self.metadata['url']:
                feed['url'] = self.metadata['url']
                feed.save()

        # now address the task
        if os.environ.has_key('SSSCRAPE_TASK_ID'):
            # load the task first
            task = self.instantiate('task')
            task.load(int(os.environ['SSSCRAPE_TASK_ID']))

            # then deal with changing the program arguments
            task['args'] = task['args'].replace(ssscrapeapi.misc.quote_url(self.feedUrl), ssscrapeapi.misc.quote_url(self.metadata['url']))

            # finally, change the resource id
            resource_name = ssscrapeapi.misc.url2resource(ssscrapeapi.misc.quote_url(self.metadata['url']))
            resource = self.instantiate('resource')
            resource['name'] = resource_name
            id = resource.find()
            if id <= 0:
                resource.save()
            task['resource_id'] = resource['id']

            # and save the (updated) task
            task.save()

        # finally, set the feed url correctly
        self.feedUrl = self.http_url

    def fetch(self):
        contents = feedworker.CommonPlugins.UrlPlugin.fetch(self)
        if self.http_status == 301: # permanent redirect
            self._redirect()
        return contents

    def open(self, argv=None):
        feedworker.CommonPlugins.UrlPlugin.open(self, argv)
        self.metadata = self.instantiate('feed_metadata', url=self.feedUrl)
        id = self.metadata.find()
        if id > 0:
           self.metadata.load(id)

    def parse(self, pageText):
        self.feed = feedparserx.parse(pageText)
        # check if the feedparser was able to get anything useful out of the URL
        # if not, then generate an exception
        if self.feed.bozo and self.feed.has_key('bozo_exception') and self.feed.version == '' and len(self.feed.entries) == 0:
            raise feedworker.FeedWorkerException(2, feedworker.FeedWorkerException.KEYWORDS.FEEDINVALID, str(self.feed.bozo_exception))
        self.collection = self._parseFeed(self.feed)
        return self.collection

    def process(self, feed, item):
        pass

    def store(self, feed, item):
        print "Storing item %s ..." % (item)
        pass

    def storefeed(self, feed):
        print "Storing feed ...."
        pass



class HTMLPlugin(UrlPlugin):
    """The HTML Plugin is a base plugin for scraping HTML pages.

    The HTML plugin is a base plugin for scraping HTML pages. You might want to use scraping when
    you want to fetch the full text for a permalink, for example. Or when you want a meta feed for
    something like Hyves. The plugin utilizes BeautifulSoup for scraping. It creates an instance
    of it, and feeds it the text of the page. It also initializes a default collection.
    """
    def parse(self, pageText):
        self.soup = BeautifulSoup(pageText, convertEntities=BeautifulSoup.HTML_ENTITIES, smartQuotesTo=None)
        return self._scrape()
    # end def parse

    def _scrape(self):
        try:
            feed_item_id = self.feed_item_id
        except AttributeError:
            feed_item_id = -1

        item = self.instantiate('feed_item')

        if feed_item_id >= 0:
            item.load(feed_item_id)
            collection = self.instantiate('feed')
            collection.load(item['feed_id'])
        else:
            collection = self.instantiate('feed', title=self.soup.title.string, url=self.feedUrl)
            item['title'] = self.soup.title.string
            item['guid'] = self.feedUrl
 
        collection['items'] = [item]
        self.scrape(collection)

        return collection
    # end def _scrape

    def scrape(self, collection):
        collection['items'] = []
