"""Parsers for HTML content (as used by PermalinkScraper)"""

import os, sys
import re
import getopt

import ssscrapeapi.feeds as feeds

from BeautifulSoup import BeautifulSoup, Comment, Declaration, ProcessingInstruction, Tag
from content_extraction.cleanup import PageCleaner


class SampleParser:
    def __init__(self, parent_plugin):
        print >>sys.stderr, "Sample parser initialized."
        self.parent_plugin = parent_plugin

    def parse(self, soup, collection):
        print "Sample parser called."
        return []

    def filter(self):
        print "Sample filter called."
        return None


BLOCK_LEVEL_TAGS = set(['address', 'blockquote', 'center', 'dir', 'div', 'dl', 
                        'fieldset', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 
                        'hr', 'isindex', 'menu', 'noframes', 'noscript', 'ol', 
                        'p', 'pre', 'table', 'ul', 'dd', 'dt', 'frameset', 
                        'li', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr',
                        'br']) 


class DefaultHTMLParser:
    def __init__(self, parent_plugin):
        self.parent_plugin = parent_plugin

    def parse(self, soup, collection):
        if len(collection['items']) == 0: 
            return []

        item = collection['items'][0]
        #item['content'] = str(soup)    # Saving souped raw html
        # raw_html saved in PermalinkScraper.py/scrape()

        if not item['content']:
            item['content'] = None

        text = PageCleaner.soup_to_text(soup)

        # Save text
        item['content_clean'] = text
        if not item['content_clean']:
            item['content_clean'] = None

        return collection['items']

    def filter(self):
        return None


class HTMLCleaner(DefaultHTMLParser):
    def parse(self, raw_soup, collection):
        metadata = self.parent_plugin.metadata
        threshold = metadata['cleanup_threshold']

        try:
            model_data_str = metadata['cleanup_model']
            # Use array.tostring() in case array.array() is read from the database
            try:
                model_data_str = model_data_str.tostring()
            except AttributeError:
                pass
            model_data = eval(model_data_str)    # str -> dict
        except Exception, e:
            print >>sys.stderr, e
            print >>sys.stderr, "html_parsing.py/HTMLCleaner.parse(): feed url: %s." % metadata['url']
            print >>sys.stderr, "html_parsing.py/HTMLCleaner.parse(): no model, so no cleaning."
            return collection['items']

        # get the feed html for this item
        pagecleaner = PageCleaner(cleanup_model=model_data, cleanup_threshold=0.1, output_format="soup")
        clean_soup_list = pagecleaner.extract(soups=[raw_soup])
        if clean_soup_list is None:
            print >>sys.stderr, "html_parsing.py/HTMLCleaner.parse(): cleanup error"
            return collection['items']

        clean_soup = clean_soup_list[0]

        if len(collection['items']) == 0: 
            return []

        item = collection['items'][0]
        #item['content'] = str(raw_soup)    # Saving souped raw html
        # raw_html saved in PermalinkScraper.py/scrape()

        # cleaned html, and text
        item['content_clean_html'] = str(clean_soup)
        if not item['content_clean_html']:
            item['content_clean_html'] = None

        item['content_clean'] = PageCleaner.soup_to_text(clean_soup)
        if not item['content_clean']:
            item['content_clean'] = None

        return collection['items']

