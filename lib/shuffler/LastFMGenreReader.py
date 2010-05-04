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
        track = network.get_track(artist, title)
        #print >>sys.stderr, track
        top_tags = None
        top_tags = track.get_top_tags()
        
        # if there were no top tags for the track, then find out top tags for the artist
        if ((not top_tags) or (len(top_tags) <= 0)):
            artist = network.get_artist(artist)
            top_tags = artist.get_top_tags()
        
        # return the top tags
        return top_tags