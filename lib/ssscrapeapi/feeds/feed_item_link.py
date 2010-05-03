
import sys

import ssscrapeapi

class FeedItemLink(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes a FeedItem object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_feed_item_link'
        self.fields = [
            'feed_item_id',
            'relation',
	    'link',
	    'type',
	    'title'
        ]

    def set_item(self, object):
        '''
        Sets the link's feed item to the given object.
        '''

        self['feed_item_id'] = object['id']
