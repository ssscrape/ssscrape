
import sys

import ssscrapeapi

class FeedLink(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes a FeedLink object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_feed_link'
        self.fields = [
            'feed_id',
            'relation',
	    'link',
	    'type',
	    'title'
        ]

    def set_feed(self, object):
        '''
        Sets the link's feed item to the given object.
        '''

        self['feed_id'] = object['id']
