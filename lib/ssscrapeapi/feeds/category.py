
import sys

import ssscrapeapi

class Category(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes a Category object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_category'
        self.fields = [
	    'label',
	    'term',
	    'scheme',
	    'type'
        ]
