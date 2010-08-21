__doc__ = '''Fetches the ID3 tag of a given URL.'''

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

class YoutubeMetadataReader:
    def fetch(self, video_id):
        gdata_url = "http://gdata.youtube.com/feeds/api/videos/%s?alt=json" % (video_id)