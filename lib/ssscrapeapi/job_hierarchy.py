
import sys

import ssscrapeapi

class JobHierarchy(ssscrapeapi.TableObject):
    def __init__(self):
        '''
        Initializes a Job object.
        '''

        ssscrapeapi.TableObject.__init__(self)

        self.config_section = 'database'
        self.table = 'ssscrape_job_hierarchy'
        self.fields = [
            'job_id',
            'parent_job_id',
            'parent_task_id',
        ]

    def set_job(self, job):
        self['job_id'] = job['id']

    def set_parent_job(self, job):
        self['parent_job_id'] = job['id']

    def set_parent_task(self, task):
        self['parent_task_id'] = task['id']
