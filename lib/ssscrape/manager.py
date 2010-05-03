
import sys
import os.path
from socket import gethostname

from twisted.internet import task
from twisted.internet import reactor
from twisted.python import log

import ssscrape


# IDEA: Use a DeferredSemaphore for each of the process pools (per job type)
# See http://twistedmatrix.com/documents/current/api/twisted.internet.defer.DeferredSemaphore.html

class Manager:
    '''
    Manages workers by handing them tasks to execute
    '''

    # The worker_pools dictionary maps job types to WorkerPool instances.
    worker_pools = {}

    def __init__(self, **kwargs):
        '''
        Initialize the C{Manager} instance.
        '''

        # Handle command line options
        from optparse import OptionParser
        parser = OptionParser(usage="%prog")
        parser.add_option('-v', '--verbose', action='store_true',
                dest='verbose', default=False, help='Enable verbose mode')

        (options, args) = parser.parse_args()

        # Don't accept any positional arguments
        if args:
            parser.error('Positional parameters are not supported: %s' % ' '.join(args))
            sys.exit(1)

        # Logging
        # TODO: log in database table perhaps? See ticket:6.
        log_fp = open(os.path.join(ssscrape.config.LOG_DIR, 'ssscrape-daemon.log'), 'a')
        log.addObserver(log.FileLogObserver(log_fp).emit)

        if options.verbose:
            log.addObserver(log.FileLogObserver(sys.stdout).emit)


        # We cannot recover from SQL errors.
        ssscrape.database.add_error_callback(self.stop)


    def start(self):
        '''
        Start the manager.

        This method start the reactor and blocks until the reactor is stopped.
        '''

        log.msg('Starting the manager...')

        # FIXME: stop automatically after a while for now
        #reactor.callLater(30, self.stop)
        self.check_jobs()

        # Periodically check for new job types
        self._discover_job_types_looping_call = task.LoopingCall(self.discover_job_types)
        interval = ssscrape.misc.parse_time_string_to_seconds(
            ssscrape.config.get_string('manager', 'discover-job-types-interval'))
        self._discover_job_types_looping_call.start(interval, now=True)


        # Now run the reactor
        ret = reactor.run()
        return ret

    def stop(self, *args, **kwargs):
        '''
        Stop the manager
        '''
        log.msg('Shutting down the manager...')

        self._discover_job_types_looping_call.stop()

        # Now stop the reactor. Don't invoke reactor.stop() directly, since
        # that causes shutdown problems. Instead, finish the current event loop
        # iteration and schedule a call to reactor.stop() as soon a possible
        # thereafter.
        reactor.callLater(0, reactor.stop)

    def discover_job_types(self):
        '''
        Discovers job types in the job queue. This method runs periodically.

        @see: _discover_job_types_cb
        '''
        d = ssscrape.job_queue.find_job_types()
        d.addCallback(self._discover_job_types_cb)
        d.addErrback(log.err)

    def _discover_job_types_cb(self, job_types):
        '''
        Callback when all job types have been discovered.
        
        A new worker pool will be created for each job type for which no worker
        pool is running yet.

        @see discover_job_types
        '''

        for job_type in job_types:

            # if there's already a worker pool we should not create another one
            if job_type in self.worker_pools:
                continue

            log.msg('Creating new worker pool for job type "%s"' % job_type)

            wp = ssscrape.WorkerPool(job_type)
            self.worker_pools[job_type] = wp

            wp.start()

    def check_jobs(self):
        '''
        Check jobs that were previously started on this host for integrity issues.

        @see: _check_jobs
        '''

        log.msg('Checking jobs ...')
        d = ssscrape.job_queue.list_jobs_by_host(gethostname())
        d.addCallback(self._check_jobs)
        d.addErrback(log.err)
        log.msg('Checked jobs ...')

    def _check_jobs(self, jobs):
        '''
        Callback when a list of jobs for a host was found.
        '''

        for job in jobs:
            # disregard pending jobs, or jobs with a temporary error
            if job.state in [ssscrape.Job.STATES.PENDING, ssscrape.Job.STATES.TEMPORARY_ERROR]:
                continue

            log.msg('Checking %s' % (job))
            new_state = job.state
            if job.state in [ssscrape.Job.STATES.RUNNING]:
                new_state = ssscrape.Job.STATES.TEMPORARY_ERROR
            job.mark_as_finished(new_state) 
