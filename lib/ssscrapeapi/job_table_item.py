
import os, sys

import ssscrapeapi

class JobTableItem(ssscrapeapi.TableObject):
    def __init__(self):
        '''
        Initializes a Job object.
        '''

        ssscrapeapi.TableObject.__init__(self)

        self.config_section = 'database'
        self.table = 'ssscrape_job_table_item'
        self.fields = [
            'job_id',
            'table_name',
            'table_row_id',
            'timestamp'
        ]

    def set_job(self, job):
        self['job_id'] = job['id']

    def set_obj(self, obj):
        self['table_name'] = obj.table
        self['table_row_id'] = obj['id']

def save_job_table_item(obj):
    ji = JobTableItem()
    try:
        ji['job_id'] = int(os.environ['SSSCRAPE_JOB_ID'])
    except KeyError:
        return
    ji['timestamp'] = 'NOW()'
    ji.unescaped = ['timestamp']
    ji.set_obj(obj)
    ji.save()
    return ji
