
import sys

import ssscrapeapi

class EnclosureRestriction(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes an EnclosureRestriction object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_enclosure_restriction'
        self.fields = [
	    'enclosure_id',
	    'link',
	    'type',
	    'relationship',
	    'restriction'
        ]

    def set_enclosure(self, object):
        '''
        Sets the enclosure ID to the ID of the given object.
        '''

        self['enclosure_id'] = object['id']

