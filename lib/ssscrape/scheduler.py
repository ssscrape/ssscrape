import sys
import os.path
import datetime

from twisted.internet import task
from twisted.internet import reactor
from twisted.python import log

import ssscrape


# IDEA: Use a DeferredSemaphore for each of the process pools (per job type)
# See http://twistedmatrix.com/documents/current/api/twisted.internet.defer.DeferredSemaphore.html

class Scheduler:
    '''
    Schedules periodic tasks in the job table. 
    '''

    def __init__(self, **kwargs):
        '''
        Initialize the C{Scheduler} instance.
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
        log_fp = open(os.path.join(ssscrape.config.LOG_DIR, 'ssscrape-scheduler.log'), 'a')
        log.addObserver(log.FileLogObserver(log_fp).emit)

        if options.verbose:
            log.addObserver(log.FileLogObserver(sys.stdout).emit)


        # We cannot recover from SQL errors.
        ssscrape.database.add_error_callback(self.stop)

    def start(self):
        '''
        Start the scheduler.

        This method start the reactor and blocks until the reactor is stopped.
        '''

        log.msg('Starting the scheduler...')

        # FIXME: stop automatically after a while for now
        #reactor.callLater(30, self.stop)

        # Periodically schedule tasks 
        self._schedule_tasks_looping_call = task.LoopingCall(self.schedule_tasks)
        interval = ssscrape.misc.parse_time_string_to_seconds(
            ssscrape.config.get_string('scheduler', 'schedule-tasks-interval'))
        self._schedule_tasks_looping_call.start(interval, now=True)

        # Periodically reschedule jobs with temporary errors
        self._reschedule_jobs_looping_call = task.LoopingCall(self.reschedule_jobs)
        interval = ssscrape.misc.parse_time_string_to_seconds(
            ssscrape.config.get_string('scheduler', 'reschedule-jobs-interval'))
        self._reschedule_jobs_looping_call.start(interval, now=True)

        # Now run the reactor
        ret = reactor.run()
        return ret

    def stop(self, *args, **kwargs):
        '''
        Stop the scheduler
        '''

        log.msg('Shutting down the scheduler...')

        self._schedule_tasks_looping_call.stop()
        reactor.stop()

    def schedule_tasks(self):
        '''
        Schedules tasks in the job queue. This method runs periodically.
        '''

        d = ssscrape.task_list.find_candidates()
        d.addCallback(self._schedule_tasks)

    def _schedule_tasks(self, tasks):
        for task in tasks:
            is_timed = (task.hour is not None) or (task.minute is not None) # forget about seconds for now..
            if is_timed:
                scheduleNow = False
                #current_time = datetime.datetime.now()
                current_time = task.now
                if task.hour is None: # we only have prefered minute -- goud for hourly things, ie. something that runs like 18 *
                    scheduleNow = (task.minute == current_time.minute)
                else:
                    scheduleNow = (task.hour == current_time.hour)
                    if task.minute is not None: 
                        scheduleNow = scheduleNow and (task.minute == current_time.minute)
            else:
                scheduleNow = True
            if scheduleNow:
                log.msg('Scheduling %s now ...' % (task))
                ssscrape.job_queue.add(task.type, task.program, task.program_args, 0, task.resource_id, task.id, task.hostname) # 0 means 'now'

    def reschedule_jobs(self):
        '''
        Reschedules tasks in the job queue. This method runs periodically.
        '''

        d = ssscrape.database.run_interaction(self._reschedule_jobs, ssscrape.Job.STATES.TEMPORARY_ERROR)
        return d 

    def _reschedule_jobs(self, transaction, job_state):
        '''
        Reschedule jobs, if they are meant to be rescheduled.
        '''

        # first get the current time
        transaction.execute('''SELECT NOW()''')
        row = transaction.fetchone()
        cur_time = row[0]

        # now find jobs with a certain state
        transaction.execute('''
            SELECT
                `id`,
                `type`,
                `program`,
                `args`,
                `attempts`,
                `task_id`,
                `resource_id`,
                `start`,
                `end`
            FROM
                ssscrape_job
            WHERE
                `state` = %s
        ''', job_state)
        rows = transaction.fetchall()
        jobs = ssscrape.job_queue._list_jobs_by_state(rows)

        for job in jobs:
            # first check if the worker type allows rescheduling of jobs
            if not ssscrape.config.worker_get_bool(job.type, 'auto-reschedule-after-temporary-error', False):
                continue

            # now check if enough time has elapsed to reschedule
            n_seconds =  ssscrape.misc.parse_time_string_to_seconds(
                ssscrape.config.worker_get_string(job.type, 'reschedule-after', '1h')
            )
            reschedule_after = datetime.timedelta(seconds=n_seconds)
            if (job.start + reschedule_after) > cur_time:
                continue

            # now actually reschedule the job
            log.msg('Rescheduling %s ...' % (job))
            job.set_state(ssscrape.Job.STATES.PENDING)

