
import sys

import ssscrapeapi

class FeedImage(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes a FeedImage object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_feed_image'
        self.fields = [
            'feed_id',
            'url',
	    'title',
	    'width',
	    'height',
            'description',
	    'link'
        ]

    def set_feed(self, object):
        '''
        Sets the link's feed item to the given object.
        '''

        self['feed_item_id'] = object['id']

