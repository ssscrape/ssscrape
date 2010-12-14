#!/usr/bin/env python
"""Basic runner class for the collection worker."""

import sys
import os
import re
import getopt
import ConfigParser
import traceback
import urllib2

import feedparserx

import ssscrapeapi 
import feedworker

class FeedWorkerUsage(Exception):
    def __init__(self, msg):
        self.msg = msg
    # end def __init__
# end class Usage

class FeedWorkerException(Exception):
    # These states can be accessed as FeedWorkerException.KEYWORDS.GENERIC
    KEYWORDS = ssscrapeapi.misc.AttrDict({
        'GENERIC': 'GENERIC',
        'NOCONNECTION': 'NOCONNECTION',
        'URLNOTFOUND': 'URLNOTFOUND',
        'FEEDINVALID': 'FEEDINVALID',
        'UNICODE': 'UNICODE',
    })

    def __init__(self, exit_code, keyword = '', msg = ''):
        self.exit_code = exit_code
        self.keyword = keyword
        self.msg = msg

    def __str__(self):
        return "%s: %s %s " % (repr(self.exit_code), self.keyword, self.msg)
    # end def __init__
# end class Usage


class FeedWorkerRunner:
    def __init__(self, argv):
        self.argv = argv 
        # first connect to the feeds database
        ssscrapeapi.database.connect()
        # then to the control database
        ssscrapeapi.database.connect('database')
        self.load_job()
    # end def __init__

    def load_job(self):
        job_id = int(os.environ["SSSCRAPE_JOB_ID"])
        self.job = ssscrapeapi.Job()
        self.job.load(job_id) 
    # end def load_job
 
    def loadPlugin(self):
        feedModuleName = ssscrapeapi.config.worker_get_string(self.job['type'], "feedworker-module")
        feedClassName = ssscrapeapi.config.worker_get_string(self.job['type'], "feedworker-class")
        feedModule = __import__(feedModuleName)
        components = feedModuleName.split('.')
        for comp in components[1:]:
            feedModule = getattr(feedModule, comp)
        # end for
        feedClass = getattr(feedModule, feedClassName)
        self.plugin = feedClass(self.job)
        self.plugin.open(self.argv)
    # end def loadPlugin
    
    def fetch(self):
        if not hasattr(self.plugin, 'fetch'):
            return

        try:
            self.feedText = self.plugin.fetch()
        except urllib2.HTTPError, e:
           # catch HTTP errors first ( HTTP Error is a subclass of URLEror)
           print >>sys.stderr, 'Bad HTTP response: ', e.code
           print >>sys.stderr,  str(e)
           raise FeedWorkerException(2, FeedWorkerException.KEYWORDS.URLNOTFOUND, "%s" % (e.code))
        except urllib2.URLError, e:
           print >>sys.stderr, 'URL error, reason: ', e.errno
           print >>sys.stderr,  str(e)
           raise FeedWorkerException(1, FeedWorkerException.KEYWORDS.NOCONNECTION, "(%s) %s" % (e.reason[0], e.reason[1]))
        except FeedWorkerException, e:
            raise e # prevent it from becoming a generic error, due to the line below witch catches all Exceptions
        except Exception, e:
           traceback.print_exc(file=sys.stderr)
           try:
               gen_msg = e.message
           except AttributeError:
               gen_msg = u''
           raise FeedWorkerException(2, FeedWorkerException.KEYWORDS.GENERIC, gen_msg)
    # end def fetch
    
    def parse(self):
        if hasattr(self.plugin, 'parse'):
            self.feed = self.plugin.parse(self.feedText)
    # end def parse
    
    def filter(self):
        if hasattr(self.plugin, 'filter'):
            self.filteredItems = []
            for item in self.feed["items"]:
                if self.plugin.filter(self.feed, item):
                    self.filteredItems.append(item)
            # end if
        # end for
    # end def filter
    
    def process(self):
        if hasattr(self.plugin, 'process'):
            for item in self.filteredItems:
                self.plugin.process(self.feed, item)
        # end for
    # end def process
    
    def _storeItems(self):
        if hasattr(self.plugin, 'store'):
            for item in self.filteredItems:
                self.plugin.store(self.feed, item)

    def store(self):
        if hasattr(self.plugin, 'storefeed'):
            self.plugin.storefeed(self.feed)
        self._storeItems()
    # end def store
   
    def post_store(self):
        if hasattr(self.plugin, 'post_store'):
            self.plugin.post_store()
    # end def post_store
 
    def clean(self):
        if hasattr(self.plugin, 'clean'):
            for item in self.filteredItems:
                self.plugin.clean(self.feed, item)
        # end for
    # end def clean
    
    def fetchclean(self):
        if hasattr(self.plugin, 'fetchclean'):
            self.feedText = self.plugin.fetchclean(self.feedText)
    # end def fetchclean
    
    def runStep(self, step):
        #print >>sys.stderr, "Running step %s ..." % (step)
        m = getattr(self, step, None)
        if m:
            result = m()
    # end def runStep
    
    def run(self):
        # 1. Initialization
        self.result = 0
        self.update_msg = True
        try:
            self.loadPlugin()
            for step in ['fetch', 'fetchclean', 'parse', 'filter', 'clean', 'process', 'store']:
                pre_step = "pre_%s" % step
                post_step = "post_%s" % step
                for whichStep in [pre_step, step, post_step]:
                    self.runStep(whichStep)
                # end for
            # end for
        except FeedWorkerException, e:
            self.result = e.exit_code
            self._save_error_msg(e)
            #print >>sys.stderr, e
            self.update_msg = False
        except Exception, e:
            traceback.print_exc(file=sys.stderr)
            self.result = 2
        # end try
        self.close()
        return self.result
    # end def run
    
    def close(self):
        self.plugin.close()
        if self.result == 0 and self.update_msg:
            # plugins can set a message
            msg = u''
            try:
                msg = self.plugin.message
            except AttributeError:
                pass

            # and also a keyword
            kw = u'OK'
            try:
                kw = self.plugin.keyword
            except AttributeError:
                pass
            self.save_status(u"%s %s" % (kw, msg))
        ssscrapeapi.database.disconnect('database')
        ssscrapeapi.database.disconnect()
    # end def close

    def _save_error_msg(self, e):
        msg =  "%s %s" % (e.keyword, e.msg)
        self.save_status(msg)
    # end def _save_error_msg

    def save_status(self, msg):
        job = ssscrapeapi.Job()
        job['id'] = self.job['id']
        job['message'] = msg
        job.save()
    # end def save_status
# end class FeedWorkerRunner
