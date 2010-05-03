
import sys

import ssscrapeapi

class EnclosureCredits(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes an EnclosureCredits object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_enclosure_credits'
        self.fields = [
	    'enclosure_id',
	    'link',
	    'role',
	    'scheme',
	    'credit'
        ]

    def set_enclosure(self, object):
        '''
        Sets the enclosure ID to the ID of the given object.
        '''

        self['enclosure_id'] = object['id']
