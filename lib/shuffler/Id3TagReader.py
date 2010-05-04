__doc__ = '''Fetches the ID3 tag of a given URL.'''

import sys
import os
import re
import tempfile
import httplib
import urllib2
import shutil

from mutagen.easyid3 import EasyID3
from mutagen.mp3 import MP3

import ssscrapeapi

import feedworker
import feedworker.urn
import beanstalkc
import anyjson

class Id3TagReader:
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
                print >>sys.stderr, "Created temp file %s .." % (t.name)
                # shutil.copyfileobj(r, t, SHUFFLER_MAX_FILE_SIZE)
                try:
                    self.http_status = r.status # HTTP status
                except AttributeError:
                    self.http_status = r.code
                self.http_url = r.geturl() # may be a redirect
                if self.http_status >= 400: return # return if not found or something
                max_file_size = ssscrapeapi.config.get_int('id3', 'max-size', 40960)
                cur_file_size = 0
                chunk_size = 1
                buf_file_size = 4096
                while ((chunk_size > 0) and (cur_file_size < max_file_size)):
                    chunk = r.read(buf_file_size)
                    if not chunk: break
                    chunk_size = len(chunk)
                    cur_file_size += chunk_size
                    t.write(chunk)
                os.system("ls -l %s" % (t.name))
                # FIXME: we could try to get the ID3 tag incrementally?
                audio = MP3(t.name, ID3=EasyID3)
                print >>sys.stderr, audio
                #audio.pprint()
                return audio
            except EOFError, e:
                pass # means that thee ID3 information is larger than we fetched
            except httplib.BadStatusLine, e:
                raise feedworker.FeedWorkerException(1, feedworker.FeedWorkerException.KEYWORDS.NOCONNECTION)
        finally:
            if r != None: 
                r.close()