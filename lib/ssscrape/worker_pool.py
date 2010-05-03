
from twisted.internet import reactor, error
from twisted.python import log

import ssscrape


class WorkerPool:
    '''
    A Worker pool runs one or more worker processes that will excute jobs.
    '''


    def __init__(self, job_type):
        '''
        Initialize a new worker pool for a given job type.

        @param job_type:
        A string denoting the job type this worker pool should execute.
        '''

        assert isinstance(job_type, basestring)

        #log.msg('Creating new WorkerPool for job_type %s' % job_type)

        self.workers = set()
        self.job_type = job_type
        self.max_processes = ssscrape.config.worker_get_int(self.job_type, 'max-processes')

        self.find_job_delayed_call = None


    def start(self):
        '''
        Start the C{WorkerPool}.
        '''

        log.msg('Starting worker pool for %s' % self.job_type)
        self.find_job()


    def stop(self):
        '''
        Stop the C{WorkerPool}.
        '''

        # do nothing for now


    # job finding methods

    def find_job(self):
        '''
        Find a new job to execute.

        This method only does something if the maximum number of concurrently
        running processes is not reached, so it can be safely run wherever it
        might be needed to look for a new job.
        '''

        # Don't do anything if the worker pool has reached its maximum size.
        # This method will be invoked again if a job has finished.
        if len(self.workers) >= self.max_processes:
            log.msg('Maximum number of "%s" jobs reached.' % (self.job_type,))
            return

        # Since we're trying to find a job right now, any pending future call
        # should be aborted. If none is found, the error callback reschedules a
        # call to this method anyway.
        self.unschedule_find_job()

        log.msg('Trying to find job type %s...' % self.job_type)

        d = ssscrape.job_queue.find_job_by_type(self.job_type)
        d.addCallback(self._find_job_cb)
        d.addErrback(self._find_job_eb)
        d.addErrback(log.err)


    def _find_job_cb(self, job):
        '''
        Callback when a new job is found.
        '''
        assert isinstance(job, ssscrape.Job)

        # Execute the job
        self.execute_job(job)

        # Immediately try to find a new job, since this worker pool might not
        # be full yet, since jobs are found one at a time.
        self.find_job()


    def _find_job_eb(self, failure):
        '''
        Errback to handle NoJobFoundError errors.
        '''
        failure.trap(ssscrape.error.NoJobFoundError)

        # No job found right now, so let's check again after a while
        self.schedule_find_job()


    def schedule_find_job(self):
        '''
        Schedule a find_job in a few seconds. This is useful if no job was
        found and we need to check again after a while. The timeout is
        configurable using the poll-interval worker configuration option.
        '''

        # Don't schedule if one is scheduled already.
        if self.find_job_delayed_call:
            return

        poll_interval = ssscrape.config.worker_get_time(self.job_type, 'poll-interval')
        self.find_job_delayed_call = reactor.callLater(poll_interval, self.find_job)


    def unschedule_find_job(self):
        '''
        Unschedules a find_job call, if one is scheduled.
        '''

        if self.find_job_delayed_call is not None:
            try:
                self.find_job_delayed_call.cancel()
            except (error.AlreadyCancelled, error.AlreadyCalled):
                pass

            self.find_job_delayed_call = None



    # job execution

    def execute_job(self, job):
        '''
        Executes a job. This method spawns creates a C{Worker} instance that
        runs an external process based on the job parameters.

        @param job: a C{Job} instance
        '''

        assert isinstance(job, ssscrape.Job)

        worker = ssscrape.Worker(job)
        self.workers.add(worker)

        log.msg('Worker pool "%s": starting %s. (%d/%d)' % (self.job_type, job, len(self.workers), self.max_processes))

        d = worker.run()
        d.addCallback(self._job_finished_cb)
        d.addErrback(log.err)


    def _job_finished_cb(self, result):
        '''
        Callback when a C{Job} finishes.
        '''

        (worker, job) = result

        #log.msg('"%s" job finished.' % (self.job_type,))

        assert worker in self.workers
        self.workers.remove(worker)

        log.msg('Worker pool "%s": finished %s. (%d/%d)' % (self.job_type, job, len(self.workers), self.max_processes))

        # Since we're done, we want to find a new job to execute
        self.find_job()
