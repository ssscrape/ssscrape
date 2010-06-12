#!/usr/bin/env python
# encoding: utf-8
"""
LastFMGenreReader.py

Created by Breyten Ernsting on 2010-05-04.
Copyright (c) 2010 __MyCompanyName__. All rights reserved.
"""

import sys
import os
import re
import tempfile
import httplib
import urllib2
import shutil

import pylast

import ssscrapeapi

import feedworker
import feedworker.urn
import beanstalkc
import anyjson

class LastFMGenreReader:
    def open_network(self):
        return pylast.get_lastfm_network(api_key=ssscrapeapi.config.get_string('lastfm', 'api-key'), api_secret=ssscrapeapi.config.get_string('lastfm', 'api-secret'))
    
    def fetch(self, artist, title):
        network = self.open_network()
        #print >>sys.stderr, "Getting last.fm data for artist %s and track %s" % (artist, title)
        
        # get the track first and find out the top tags
        top_tags = None
        image_url = None
        
        try:
            artist = network.get_artist(artist)
            top_tags = artist.get_top_tags()
            image_url = artist.get_cover_image(pylast.COVER_LARGE)
        except pylast.WSError, e:
            pass
        
        tag_list = None
        min_confidence = ssscrapeapi.config.get_int('lastfm', 'min-tag-confidence', 50)
        if top_tags:
            # pylast has this stupid thing where it returns different structure on python 2.5 v 2.6
            v = sys.version_info
            if v[1] >= 6 and v[0] < 3:
                tag_list = [tag.item.name for tag in top_tags if int(tag.weight) >= min_confidence]
            else:
                tag_list = [tag['item'].name for tag in top_tags if int(tag['weight']) >= min_confidence]
        # return the top tags
        if tag_list:
            tag_list = tag_list[:3]
        return (image_url, tag_list)