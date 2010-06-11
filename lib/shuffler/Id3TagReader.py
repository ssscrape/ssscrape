__doc__ = '''Fetches the ID3 tag of a given URL.'''

import sys
import os
import re
import tempfile
import httplib
import urllib2
import shutil

import mutagen.mp3
from mutagen.easyid3 import EasyID3
from mutagen.mp3 import MP3

import ssscrapeapi

import feedworker
import feedworker.urn
import beanstalkc
import anyjson

class Id3MetadataReaderException(Exception):
    def __init__(self, msg):
        self.msg = msg

class Id3MetadataReaderHTTPError(Id3MetadataReaderException):
    def __init__(self, status):
        self.status = status
        Id3MetadataReaderException.__init__(self, "HTTP Error %d" % (status))

class Id3MetadataReader:
    def id3(self, fileName):
        try:
            audio = MP3(fileName, ID3=EasyID3)
            #print >>sys.stderr, audio
            #audio.pprint()
            if audio.has_key('artist') and audio.has_key('title'):
                return {
                    'artist' : audio['artist'][0],
                    'title': audio['title'][0]
                }
        except mutagen.mp3.HeaderNotFoundError, e:
            #print >>sys.stderr, "id3 header not found!"
            pass #no id3 info               
        except EOFError, e:
            #print >>sys.stderr, "not complete id3 tag!"
            pass # means that thee ID3 information is larger than we fetched
    
    def fetch(self, orig_url):
        r = None
        try:
            # quote non-ascii characters in URLs to percent encodings. See #119 in Trac.
            try:
                url = ssscrapeapi.misc.quote_url(orig_url)
                opener = urllib2.build_opener(feedworker.CommonPlugins.SmartRedirectHandler())
                req = urllib2.Request(url)
                r = opener.open(req) 
                t = tempfile.NamedTemporaryFile()
                # print >>sys.stderr, "Created temp file %s .." % (t.name)
                # shutil.copyfileobj(r, t, SHUFFLER_MAX_FILE_SIZE)
                try:
                    self.http_status = r.status # HTTP status
                except AttributeError:
                    self.http_status = r.code
                self.http_url = r.geturl() # may be a redirect
                if self.http_status >= 400:
                     raise Id3MetadataReaderHTTPError(self.http_status)
                max_file_size = ssscrapeapi.config.get_int('id3', 'max-size', 102400)
                #print >>sys.stderr, "Max file size : %s" % (max_file_size)
                cur_file_size = 0
                chunk_size = 1
                buf_file_size = 4096
                id3_tries = 0
                #while ((chunk_size > 0) and (cur_file_size < max_file_size)):
                while (chunk_size > 0):
                    chunk = r.read(buf_file_size)
                    if not chunk: break
                    chunk_size = len(chunk)
                    cur_file_size += chunk_size
                    t.write(chunk)
                    # print >>sys.stderr, "%s /  %s " % (cur_file_size, max_file_size)
                    if ((cur_file_size >= max_file_size) and (id3_tries == 0)):
                        #print >>sys.stderr, "Getting id3 tag ..."
                        audio = self.id3(t.name)
                        id3_tries = 1
                        #print audio
                        if audio:
                            return audio
                return self.id3(t.name)
                # os.system("ls -l %s" % (t.name))
                # FIXME: we could try to get the ID3 tag incrementally?
            except urllib2.HTTPError, e:
                raise Id3MetadataReaderHTTPError(e.code) 
            except httplib.BadStatusLine, e:
                raise feedworker.FeedWorkerException(1, feedworker.FeedWorkerException.KEYWORDS.NOCONNECTION)
        finally:
            if r != None: 
                r.close()