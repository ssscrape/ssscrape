
import sys

import ssscrapeapi

class EnclosureThumbnail(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes an EnclosureThumbnail object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_thumbnail'
        self.fields = [
	    'enclosure_id',
            'link',
	    'url',
	    'width',
	    'height',
	    'time'
        ]

    def set_enclosure(self, object):
        '''
        Sets the enclosure ID to the ID of the given object.
        '''

        self['enclosure_id'] = object['id']
