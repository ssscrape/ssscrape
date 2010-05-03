
import sys

import ssscrapeapi

class Geo(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes an Geo object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_geo'
        self.fields = [
            'latitude',
	    'longitude',
	    'name',
            'description'
        ]
