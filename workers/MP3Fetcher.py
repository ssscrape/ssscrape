#!/usr/bin/env python
# encoding: utf-8
"""
MP3Fetcher.py

Created by Breyten Ernsting on 2010-05-04.
Copyright (c) 2010 __MyCompanyName__. All rights reserved.
"""

import sys
import os
import re
import getopt

help_message = '''
Can have the following parameters:

-h|--help               show this message
-v|--verbose            turn on verbose output
-o|--output <file>      redirect output to file
-u|--url <url>          specify the url to fetch

Expects at least the url to be specified.
'''


class Usage(Exception):
    def __init__(self, msg):
        self.msg = msg


def getMetadata(url, anchorText, id3Reader):
    anchorReader = None
    method = 'id3'
    metadata = id3Reader.fetch(url)
    anchorReader = shuffler.AnchorMetadataReader()
    if (not metadata) and anchorText:
        method = 'anchor'
        metadata = anchorReader.fetch(anchorText)
    if not metadata:
        method = 'filename'
        filenameReader = shuffler.FilenameMetadataReader()
        metadata = filenameReader.fetch(url, anchorReader)
    if metadata:
        metadata['method'] = method
    return metadata     

def mergeUnique(l1, l2):
    l3 = [x for x in filter(lambda y: y not in l1, l2)]
    l1.extend(l3)
    return l1

def main(argv=None):
    url = None
    if argv is None:
        argv = sys.argv
    try:
        try:
            opts, args = getopt.getopt(argv[1:], "ho:vu:t:", ["help", "output=", "url=", "track="])
        except getopt.error, msg:
            raise Usage(msg)
        
        # option processing
        for option, value in opts:
            if option == "-v":
                verbose = True
            if option in ("-h", "--help"):
                raise Usage(help_message)
            if option in ("-o", "--output"):
                output = value
            if option in ("-u", "--url"):
                url = value
            if option in ("-t", "--track"):
                track_id = value
    except Usage, err:
        print >> sys.stderr, sys.argv[0].split("/")[-1] + ": " + str(err.msg)
        print >> sys.stderr, "\t for help use --help"
        return 2
    ssscrapeapi.database.connect()
    if url:
        track = shuffler.Track(location=url)
        track_id = track.find()
        if track_id <= 0:
            return 1 # no valid url
    else:
        track = shuffler.Track()
        track.load(track_id)
    feed_item = ssscrapeapi.feeds.FeedItem()
    feed_item.load(track['feed_item_id'])
    feed_metadata = ssscrapeapi.feeds.FeedMetadata(feed_id=feed_item['feed_id'])
    feed_metadata_id = feed_metadata.find()
    feed_metadata.load(feed_metadata_id)
    if feed_metadata['tags']:
        manual_tags = feed_metadata['tags'].split(r'\s*,\s*')
    else:
        manual_tags = None
    #print manual_tags
    url = track['location']
    id3Reader = shuffler.Id3MetadataReader()
    try:
        metadata = getMetadata(url, None, id3Reader)
        if metadata:
            print >>sys.stderr, metadata
            genreReader = shuffler.LastFMGenreReader()
            (image_url, tags) = genreReader.fetch(metadata['artist'], metadata['title'])
            #print >>sys.stderr, tags
            track['artist'] = metadata['artist']
            track['title'] = metadata['title']
            track['method'] = metadata['method']
            if not tags:
                tags = []
            print >>sys.stderr, "found tags : ", tags
            print >>sys.stderr, "manual tags : ", manual_tags
            if manual_tags:
                tags = mergeUnique(tags, manual_tags)
            if len(tags) > 0:
                track['tags'] = ','.join(tags)
            else:
                del track['tags']
            if image_url:
                track['image'] = image_url
            print >>sys.stderr, track
            beanstalk = shuffler.utils.getBeanstalkInstance()
            shuffler.utils.sendScrapedLink(track, beanstalk)
            beanstalk.close()
    except shuffler.Id3MetadataReaderHTTPError, e:
        print >>sys.stderr, e.status
    
# fixup paths
topdir = os.path.normpath(os.path.join(os.path.abspath(sys.argv[0]), os.pardir, os.pardir))

# then add the lib/ and lib/ext/ paths to sys.path
sys.path.insert(0, os.path.join(topdir, 'lib'))
sys.path.insert(0, os.path.join(topdir, 'lib', 'ext'))

import ssscrapeapi
import shuffler

if __name__ == "__main__":
    sys.exit(main())
