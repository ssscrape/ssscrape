
import os, sys

import ssscrapeapi

class Job(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes a Job object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.config_section = 'database'
        self.table = 'ssscrape_job'
        self.fields = [
            'task_id',
            'type',
            'program',
            'args',
            'state',
            'message',
            'output',
            'hostname',
            'process_id',
            'exit_code',
            'attempts',
            'scheduled',
            'start',
            'end',
            'last_update',
            'resource_id'
        ]

    def save(self):
        # need to know if we're dealing with a new job ...
        is_new = not self.has_key('id')

        # save the job info in the ssscrape_job table
        ssscrapeapi.TableObject.save(self)

        if not is_new:
            return

        # get the ID of the *running* job and task
        try:
            current_job_id = int(os.environ['SSSCRAPE_JOB_ID'])
        except KeyError:
            current_job_id = None
        try:
            current_task_id = int(os.environ['SSSCRAPE_TASK_ID'])
        except KeyError:
            current_task_id = None

        # only do this if jobs schedule *other* jobs
        if current_job_id == self['id']:
            return 
            
        # load the job hierarchy info for the parent of this job
        parent_hier = ssscrapeapi.JobHierarchy()
        parent_hier['job_id'] = current_job_id
        parent_hier_id = parent_hier.find()

        # if a task is not set for this job, then use the task of the
        # parent for the hierarchy.
        if not current_task_id and parent_hier_id:
            parent_hier.load(parent_hier_id)
            current_task_id = parent_hier['parent_task_id']

        # save the hierarchy if there is one
        if current_job_id or current_task_id: 
            hier = ssscrapeapi.JobHierarchy()
            hier['job_id'] = self['id']
            hier['parent_job_id'] = current_job_id
            hier['parent_task_id'] = current_task_id
            hier.save()

class JobLog(Job):
    def __init__(self):
        ssscrapeapi.Job.__init__(self)
        self.table = 'ssscrape_joblog'
