
import sys

import ssscrapeapi

class Resource(ssscrapeapi.TableObject):
    def __init__(self):
        '''
        Initializes a Resource object.
        '''

        ssscrapeapi.TableObject.__init__(self)

        self.config_section = 'database'
        self.table = 'ssscrape_resource'
        self.fields = [
            'name',
            'latest_run',
            'interval'
        ]
