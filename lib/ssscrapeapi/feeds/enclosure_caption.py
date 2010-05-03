
import sys

import ssscrapeapi

class EnclosureCaption(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes an EnclosureCaption object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_enclosure_caption'
        self.fields = [
	    'enclosure_id',
	    'link',
	    'format',
	    'language',
	    'type',
	    'time_start',
	    'time_end',
	    'caption'
        ]

    def set_enclosure(self, object):
        '''
        Sets the enclosure ID to the ID of the given object.
        '''

        self['enclosure_id'] = object['id'] 
