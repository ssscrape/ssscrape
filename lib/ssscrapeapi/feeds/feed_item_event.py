
import sys

import ssscrapeapi

class FeedItemEvent(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes a FeedItemEvent object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_feed_item_event'
        self.fields = [
            'feed_item_id',
	    'DATETIME_start', # ?
	    'DATETIME_end', # ?
	    'title',
	    'description'
        ]
        self.geo_assoc = ssscrapeapi.feeds.FeedGeo()

    def add(self, object):
        '''
        Adds an association to the given object.
        '''

        if isinstance(object, feeds.Geo):
            self.geo_assoc.add(self['id'], object['id'])

    def delete(self, object):
        '''
        Deletes as association to the given object.
        '''

        if isinstance(object, feeds.Geo):
            self.geo_assoc.delete(self['id'], object['id'])
