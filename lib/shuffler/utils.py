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
import urllib
import shutil

import ssscrapeapi

import feedworker
import feedworker.urn
import beanstalkc
import anyjson

class AnchorMetadataReader:
    def fetch(self, anchor_text, title_only = False):
        hasSplitter = re.search(r'[\s_]*(\-|\:\:?|_\-_|' + u'\2013' + '|' + u'\2014' + ')[\s_]*', anchor_text, re.U)
        artist = ''
        title = ''
        if hasSplitter:
            splitter = hasSplitter.group(1)
            [artist, title] = anchor_text.split(splitter, 1)
            artist = urllib.unquote(artist)
            artist = re.sub(r'^\s*["\'_]*', '', artist)
            artist = re.sub(r'["\'_]*\s*$', '', artist)
            title = urllib.unquote(title)
            title = re.sub(r'^\s*["\'_]*', '', title)
            title = re.sub(r'["\'_]*\s*$', '', title)
        else :
            if title_only:
                title = re.sub(r'["\'_]*', '', anchor_text)
            else:
                return
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
            return anchorReader.fetch(realFilename, True)

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

def sendScrapedLink(track, beanstalk=None):
    track_fields = {
      'permalink': track['permalink'],
      'location': track['location'],
      'artist': track.get('artist', u''),
      'title': track.get('title', u''),
      'tags': track.get('tags', u''),
      'created': track['posted'].isoformat(),
      'site_url': track['site_url']
    }
    if 'image' in track:
        track_fields['image'] = track['image']
    json_obj = anyjson.serialize(track_fields)
    print >>sys.stderr, json_obj
    if beanstalk:
        track['sent'] = 'NOW()'
        track.save()
        beanstalk.put(json_obj)
