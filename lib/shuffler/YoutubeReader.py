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

import shuffler

import feedworker
import feedworker.urn
import beanstalkc
import anyjson

class YoutubeMetadataReader:
    def url2id(self, video_url):
        # video_url is like http://www.youtube.com/v/i3fd4nE8OCI&ap=%2526fmt%3D18&autoplay=0&rel=0&fs=1&color1=0x3a3a3a&color2=0x999999&border=0&loop=0
        m = re.search('youtube\.com\/v\/([^&?]+)', video_url)
        return m.group(1)
    
    def fetch(self, video_id):
        gdata_url = "http://gdata.youtube.com/feeds/api/videos/%s?alt=json" % (video_id)
        gdata_txt = urllib.urlopen(gdata_url).read()
        if gdata_txt == 'Private video':
            return
        video_info = anyjson.deserialize(gdata_txt)
        if video_info.has_key('entry'):
            video_title = video_info['entry']['title']['$t']
            anchorReader = shuffler.AnchorMetadataReader()
            metadata = anchorReader.fetch(video_title, True)
            metadata['method'] = 'filename' #FIXME: should be something else
            return metadata
            