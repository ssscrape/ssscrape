#!/usr/bin/env python
"""Full content parser for the collection worker for scrape.

"""

import sys
import re
import urllib2
import time

import MySQLdb
import feedparserx

import collectionworker

def fetchAndParseTestFile(testUrl):
    f = open(testUrl)
    contents = f.read()
    f.close()
    # the test cases are much inspired by the tests in the feedparser,
    # written by Mark Pilgrim and Sam Ruby.
    desc_re = re.compile("Description:\s*(.*?)\s*Expect:\s*(.*)\s*-->")
    search_results = desc_re.search(contents)
    if not search_results:
        raise RuntimeError, "can't parse %s" % xmlfile
    test_desc = search_results.group(1).strip() 
    test_eval = search_results.group(2).strip()
    return [contents, test_desc, test_eval]

class TestPluginException(Exception):
    pass

class TestPlugin(collectionworker.CommonPlugins.FeedPlugin):
    def fetch(self, feedUrl):
        """Fetches the URL from the interwebs."""
        self.feedUrl = feedUrl
        [self.contents, self.test_desc, self.test_eval] = fetchAndParseTestFile(self.feedUrl)
        return self.contents
    # end def fetch

    def close(self):
        env = {}
        env['feed'] = self.collection
        env['entries'] = self.collection["items"]
        if not eval(self.test_eval, env):
            raise TestPluginException("Did not pass test")
    # end def close

class TestFullContentPlugin(collectionworker.FullContent.FullContentPlugin):
    def fetch(self, feedUrl):
        """Fetches the URL from the interwebs."""
        print feedUrl
        self.feedUrl = feedUrl
        [self.contents, self.test_desc, self.test_eval] = fetchAndParseTestFile(self.feedUrl)
        return self.contents
    # end def fetch

    def close(self):
        env = {}
        env['feed'] = self.collection
        env['entries'] = self.collection["items"]
        if not eval(self.test_eval, env):
            raise TestPluginException("Did not pass test")
    # end def close
