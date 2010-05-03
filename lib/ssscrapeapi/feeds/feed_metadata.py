
import sys

import ssscrapeapi

class FeedMetadata(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes a FeedMetadata object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_feed_metadata'
        self.fields = [
	        'feed_id',
            'url',
            'class',
	        'language',
            'kind',
            'partial_args',
            'tags'
        ]
        self.unescaped = [
        ]     

    def set_feed(self, object):
        '''
        Sets the feed id to the given object.
        '''

        self['feed_id'] = object['id']
