#!/usr/bin/env python
# encoding: utf-8
"""
utils.py

Created by Breyten Ernsting on 2010-05-05.
Copyright (c) 2010 __MyCompanyName__. All rights reserved.
"""

import sys
import os
import re
import tempfile
import httplib
import urllib2
import shutil

import ssscrapeapi

import feedworker
import feedworker.urn
import beanstalkc
import anyjson

class AnchorMetadataReader:
    def fetch(self, anchor_text):
        hasSplitter = re.search(r'[\s_]*(\-|\:\:?|_\-_|' + u'\2013' + '|' + u'\2014' + ')[\s_]*', anchor_text, re.U)
        if hasSplitter:
            splitter = hasSplitter.group(1)
            [artist, title] = anchor_text.split(splitter, 1)
            artist = re.sub(r'^["\'_]*', '', artist)
            artist = re.sub(r'["\'_]*$', '', artist)
            title = re.sub(r'^["\'_]*', '', title)
            title = re.sub(r'["\'_]*$', '', title)
            return {
                'artist': artist.strip(),
                'title': title.strip()
            }

class FilenameMetadataReader:
    def fetch(self, filename, anchorReader = None):
        hasFilename = re.search(r'([^\/\\]+)\.(\w+)$', filename)
        if hasFilename:
            realFilename = hasFilename.group(1)
            if not anchorReader:
                anchorReader = AnchorMetadataReader()
            return anchorReader.fetch(realFilename)

def getBeanstalkInstance(tube='tracks'):
    # print "Initiating beanstalk connection ..."
    configs = {
      'development': {
        'host': 'localhost',
        'port': 11300
      },
      'preproduction': {
        'host': 'localhost',
        'port': 11300
      },
      'production': {
        'host': 'localhost',
        'port': 11300
      },
    }
    environment = 'development' # ssscrapeapi.config.get_string('twones', 'environment', 'production') #os.getenv('CAKEPHP_ENV')
    # print environment, configs[environment]['host'], configs[environment]['port']
    beanstalk = beanstalkc.Connection(host=configs[environment]['host'], port=configs[environment]['port'])
    beanstalk.use(tube)
    return beanstalk

def sendScrapedLink(permalink, url, site_url, artist, title, tags, created, beanstalk):
    json_obj = anyjson.serialize({
      'permalink': link,
      'location': url,
      'artist': artist,
      'title': title,
      'tags': tags,
      'created': created,
      'site_url': site_url
    })
    beanstalk.put(json_obj)