#!/usr/bin/env python

__doc__ = '''Usage: feedworker.py -i <itemid> [-p <parser>]
When the parser is not specified, the cleanup flag from the feed metadata determines which parser will be used, either the default parser, or the html cleaning parser.'''

import os, sys
import re
import getopt

from BeautifulSoup import BeautifulSoup, Comment, Declaration, ProcessingInstruction, Tag

import ssscrapeapi
import feedworker
import feedworker.html_parsing


class PermalinkScraper(feedworker.CommonPlugins.HTMLPlugin):
    '''
    A HTML permalink scraper for Ssscrape.
    '''

    def load_metadata(self): 
        feed_item = self.instantiate('feed_item')
        feed_item.load(self.feed_item_id)

        # get ssscrape_feed_metadata for this feed_item_id
        feed_id = feed_item['feed_id'] 
        metadata = self.instantiate('feed_metadata', feed_id=feed_id)
        metadata_id = metadata.find()
        assert metadata_id >= 0
        metadata.load(metadata_id)

        self.metadata = metadata

    def load_permalink_parser(self):
        self.load_metadata()
        #if hasattr(self, "feed_item_id"):
        #    self.load_metadata()

        if not self.permalink_parser:
            # No parser specified. 
            # The cleanup flag in the feed metadata determines which parser to use. 
            cleanup = self.metadata['cleanup']
            if cleanup == 'disabled':
                self.parser = feedworker.html_parsing.DefaultHTMLParser(self)
            else:   # 'enabled'
                self.parser = feedworker.html_parsing.HTMLCleaner(self)

            return

        parser_parts = self.permalink_parser.split('.')
        parser_module_name = '.'.join(parser_parts[:-1])
        parser_class_name = parser_parts[-1]
        parser_module = __import__(parser_module_name)
        for comp in parser_parts[1:-1]:
            parser_module = getattr(parser_module, comp)
        # end for
        parser_class = getattr(parser_module, parser_class_name)
        self.parser = parser_class(self)
    # end def load_permalink_parser

    def open(self, argv=None):
        feedworker.CommonPlugins.DefaultPlugin.open(self, argv)
        try:
            opts, args = getopt.getopt(argv[1:], "hi:p:u:l", ["help", "item", "parser", "url", "local"])
        except getopt.error, msg:
            raise feedworker.FeedWorkerUsage(msg)
        # end try
        # more code, unchanged
        # process options
        feedFile = None
        altFeedFile = None
        self.permalink_parser = None
        self.fetched_content = None
        for o, a in opts:
            if o in ("-h", "--help"):
                print __doc__
                sys.exit(0)
            
            if o in ("-i", "--item"):
                self.feed_item_id = int(a)
                link = self.instantiate('feed_item_link', feed_item_id=self.feed_item_id, relation="alternate", type="text/html")
                link.find()
                if link.has_key('id'):
                    link.load(link['id'])
                    feedFile = link['link']
                else:
                    cursor = ssscrapeapi.database.execute('''SELECT guid FROM `ssscrape_feed_item` WHERE id = %s''', (self.feed_item_id,))
                    row = cursor.fetchone()
                    if row and re.match('http:\/\/', row[0]):
                        feedFile = row[0] 
            
            if o in ("-p", "--parser"):
                self.permalink_parser = a
            
            if o in ("-u", "--url"):
                altFeedFile = a
            
            if o in ("-l", "--local"):
                # Content should be loaded from the DB 
                self.fetched_content = ""

        if self.fetched_content is not None:
            # Local-only permalink processing: fetch content from the database instead of the web
            assert hasattr(self, 'feed_item_id'), "-i should be used if -l is used"
            item = self.instantiate('feed_item')
            item.load(self.feed_item_id)
            assert item.has_key('content'), "Item %d not found or has empty content" % (self.feed_item_id,)
            self.fetched_content = str(item['content'])


           
        if altFeedFile:
            feedFile = altFeedFile
        if feedFile:
            self.feedUrl = feedFile
        else:
            raise feedworker.FeedWorkerUsage(__doc__)

        self.parser = None
        self.load_permalink_parser()
        # end if

    def fetch(self):
        if self.fetched_content is not None:
            # Don't fetch from the web: use what we already have
            print "Skipping web access - using DB version of item content"
            return self.fetched_content

        # Otherwise, fetch content as usual     
        return feedworker.CommonPlugins.HTMLPlugin.fetch(self)
             
    def parse(self, pageText):
        self.soup_filter = None
        try:
            self.soup_filter = self.parser.filter()
        except AttributeError, e:
            pass 
        self.raw_html = pageText
        self.soup = BeautifulSoup(pageText, convertEntities=BeautifulSoup.HTML_ENTITIES, smartQuotesTo=None, parseOnlyThese=self.soup_filter)
        return self._scrape()
    # end def parse

    def scrape(self, collection):
        if self.parser:
            try:
                items = self.parser.parse(self.soup, collection)
                for item in items:
                    item['content'] = self.raw_html
            except UnicodeDecodeError, e:
                raise FeedWorkerException(2, FeedWorkerException.KEYWORDS.UNICODE, "%s" % (e))
        else:
            items = []
        collection['items'] = items

    def store(self, collection, item):
        '''Stores a single item into the database.

        This routine stores a single item into the database.'''

        #print >>sys.stderr, "Storing item %s ..." % (item['guid'])

        #print >>sys.stderr, "* Storing item info ..."
        self.save_item_info(collection, item)
        #print >>sys.stderr, "* Storing item author ..."
        if not item.has_key('author'):
            item['author'] = None
        self.save_item_author(collection, item)

        #print >>sys.stderr, "* Storing item geo info ..."
        if item.has_key('geo'):
            self.save_item_geo_info(collection, item)

        #print >>sys.stderr, "* Storing item link info ..."
        if item.has_key('links'):
            self.save_item_links(collection, item)

        #print >>sys.stderr, "* Storing item category info ..."
        self.save_item_categories(collection, item)

        #print >>sys.stderr, "Stored item %s ..." % (item['guid'])
    # end def store

    def storefeed(self, collection):
        '''Stores a feed (excl. the items) in the database.

        This routine stores the information for a feed into the database.'''

        self.save_feed_info(collection)

        if collection.has_key('geo'):
            self.save_feed_geo_info(collection)

        if collection.has_key('links'):
            self.save_feed_links(collection)

        if collection.has_key('author'):
            self.save_feed_author(collection)

        # debugging stuff
    # end def storefeed

    def close(self):
        try:
            self.message = self.parser.message
        except AttributeError:
            pass
        try:
            self.keyword = self.parser.keyword
        except AttributeError:
            pass
        feedworker.CommonPlugins.HTMLPlugin.close(self)
