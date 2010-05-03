
import sys

import ssscrapeapi

class Task(ssscrapeapi.TableObject):
    def __init__(self):
        '''
        Initializes a Task object.
        '''

        ssscrapeapi.TableObject.__init__(self)

        self.config_section = 'database'
        self.table = 'ssscrape_task'
        self.fields = [
            'type',
            'program',
            'args',
            'state',
            'hostname',
            'autoperiodicity',
            'periodicity',
            'hour',
            'minute',
            'second',
            'latest_run',
            'resource_id',
            'data'
        ]
