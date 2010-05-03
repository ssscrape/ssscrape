
import sys

import ssscrapeapi

class Rating(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes an Rating object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_rating'
        self.fields = [
            'scheme',
            'value'
        ]
