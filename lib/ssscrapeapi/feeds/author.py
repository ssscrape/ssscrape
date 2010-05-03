
import sys

import ssscrapeapi

class Author(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes an Author object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_author'
        self.fields = [
            'link',
	    'email',
	    'fullname'
        ]
