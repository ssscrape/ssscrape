
import sys

import ssscrapeapi

class Track(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes a track object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'shuffler_track'
        self.fields = [
	        'feed_item_id',
	        'permalink',
            'location',
            'anchor',
            'posted'
        ]
        self.unescaped = [
#            'posted',
            'sent'
        ]     

    def set_feeditem(self, object):
        '''
        Sets the feed id to the given object.
        '''

        self['feed_item_id'] = object['id']
