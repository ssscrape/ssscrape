
from socket import gethostname

from twisted.python import log

import ssscrape

__doc__ = '''
    Job queue module.

    This module handles the job listing in the db and creates C{Job} instances
    that can be executed by the C{Manager}. This class should be initiated only
    once.
    '''


# Job type discovery

def find_job_types():
    d = ssscrape.database.run_query(
        '''SELECT DISTINCT `type` FROM `ssscrape_job` WHERE `scheduled` <= NOW() AND `state` = 'pending';'''
    )
    d.addCallback(_find_job_types_cb)
    return d


def _find_job_types_cb(rows):
    '''
    Callback to convert resulting database rows into a list of job types.
    '''
    types = [row[0] for row in rows]
    return types


# List jobs
def list_jobs_by_state(job_state):
    '''
    Find all jobs with a specified state.
    '''

    assert isinstance(job_state, basestring)
    d = ssscrape.database.run_query(
        '''SELECT `id`, `type`, `program`, `args`, `attempts`, `task_id`, `resource_id`, `start`, `end` FROM ssscrape_job WHERE `state` = %s''',
        job_state
    )
    d.addCallback(_list_jobs_by_state)
    return d

def _list_jobs_by_state(rows):
    jobs = []
    for row in rows:
        job = ssscrape.Job(int(row[0]), row[1], row[2], row[3], row[5], row[6])
        job.attempts = int(row[4])
        job.start = row[7]
        job.end = row[8]
        jobs.append(job) 
    return jobs

def list_jobs_by_host(hostname):
    '''
    Find all jobs with a specified hostname.
    '''

    assert isinstance(hostname, basestring)
    d = ssscrape.database.run_query(
        '''SELECT `id`, `state`, `type`, `program`, `args`, `hostname`, `start` FROM ssscrape_job WHERE `hostname` = %s''',
        hostname
    )
    d.addCallback(_list_jobs_by_host)
    return d

def _list_jobs_by_host(rows):
    jobs = []
    for row in rows:
        job = ssscrape.Job(int(row[0]), row[2], row[3], row[4])
        job.state = row[1]
        job.hostname = row[5]
        job.start = row[6]
        jobs.append(job) 
    return jobs

# Job finding

def find_job_by_type(job_type):
    '''
    Find an unprocessed job in the job queue.

    @return A Deferred that returns a Job instance as callback value or an
    error if no job was found.
    '''
    assert isinstance(job_type, basestring)

    # the job checking procedure must run as a single interaction, since we
    # need to issue several SQL queries right after each other. Note that
    # the result is a Deferred.
    d = ssscrape.database.run_interaction(_find_job_by_type, job_type)
    return d


def _find_job_by_type(transaction, job_type):
    '''
    Find a job of the specified type. Do not call directly.

    @return A C{Job} instance or None
    '''

    #log.msg('Trying to find job with type %s' % job_type)

    # Get the hostname of the machine we're on
    hostname = gethostname()

    # Gain exclusive access by locking the table 
    transaction.execute('LOCK TABLES `ssscrape_job` WRITE, `ssscrape_resource` WRITE, `ssscrape_task` WRITE;')

    # Find which resources are currently busy
    transaction.execute('DROP TEMPORARY TABLE IF EXISTS tmp_busy_resource')
    transaction.execute("CREATE TEMPORARY TABLE tmp_busy_resource SELECT DISTINCT `resource_id` FROM `ssscrape_job` WHERE `state`='running'")

    # Select the next job to execute, if any
    transaction.execute('''
        SELECT
            `ssscrape_job`.`id`,
            `type`,
            `program`,
            `args`,
            `task_id`,
            `resource_id`,
            `attempts`
        FROM
            `ssscrape_job`
        LEFT JOIN
            `ssscrape_resource`
        ON
            `resource_id` = `ssscrape_resource`.`id`
        WHERE
            `state` = %s
            AND `type` = %s
            AND `scheduled` <= NOW()
            AND (
                (`hostname` IS NULL)
                OR (
                    `hostname` = %s
                ) OR (
                    SUBSTRING_INDEX(`hostname`, ':', 1) = "allow"
                    AND
                    FIND_IN_SET(%s, SUBSTRING_INDEX(`hostname`, ':', -1))
                ) OR (
                    SUBSTRING_INDEX(`hostname`, ':', 1) = "deny"
                    AND
                    NOT FIND_IN_SET(%s, SUBSTRING_INDEX(`hostname`, ':', -1))
                )
            ) AND (
                `resource_id` IS NULL
                OR
                `resource_id` NOT IN (SELECT `resource_id` FROM tmp_busy_resource)
            ) AND (
                (`ssscrape_resource`.`latest_run` IS NULL)
                OR
                ((`ssscrape_resource`.`latest_run` + INTERVAL TIME_TO_SEC(`ssscrape_resource`.`interval`) SECOND) <= NOW())
                OR
                `resource_id` IS NULL
            )
        ORDER BY
            `last_update`
        LIMIT
            1''',
        (ssscrape.Job.STATES.PENDING, job_type, hostname, hostname, hostname))
    row = transaction.fetchone()

    # Throw away any (obviously non-existing) remaining rows, thereby
    # exhausting the result set. This might fix mysql out of sync errors
    # caused by Cursor.__del__ somewhere deeply hidden inside MySQLdb code.
    transaction.fetchall()

    job = None
    if row:
        # Get job details
        (job_id, job_type, job_program, job_args, job_task, job_resource, job_attempts) = row
        job_id = int(job_id)

        # Claim the job.
        transaction.execute('''
            UPDATE `ssscrape_job`
            SET
                `start` = NOW(),
                `state` = %(state)s,
                `hostname` = %(hostname)s,
                `attempts` = `attempts` + 1
            WHERE id = %(job_id)s
            ;''', {
                'state': ssscrape.Job.STATES.RUNNING,
                'hostname': hostname,
                'job_id': job_id}
            )

        # Create a new Job instance.
        job = ssscrape.Job(job_id, job_type, job_program, job_args)
        job.task_id = job_task
        job.resource_id = job_resource
        job.attempts = job_attempts + 1 # add one for this time

    if job is not None:
        if job.task_id is not None:
            task = ssscrape.Task(job.task_id)
            task.mark_latest_run(transaction)

        if job.resource_id is not None:
            resource = ssscrape.Resource(job.resource_id)
            resource.mark_latest_run(transaction)


    # Release the table lock
    transaction.execute('UNLOCK TABLES;')

    # There was no row matching our criteria.
    if job is None:
        raise ssscrape.error.NoJobFoundError('No job of type %s found' % job_type)

    # Finally, return the Job instance (may be None)
    return job


# Job maintenance

def add(job_type, program, program_args, scheduled=0, resource=None, task=None, hostname=None):
    '''
    Adds a job to the job table, and makes sure that resource usage rules are respected.
    '''

    # Prepare values

    values = {
        'state': ssscrape.Job.STATES.PENDING,
        'type': job_type,
        'program': program,
        'args': program_args,
        'scheduled': scheduled,
        'resource': resource,
        'task': task,
        'hostname': hostname
    }

    # Execute query

    query_tpl = \
        '''INSERT INTO `ssscrape_job` ''' \
        '''(`type`, `program`, `args`, `state`, `hostname`, `scheduled`,`resource_id`, `task_id`) ''' \
        '''VALUES (%(type)s, %(program)s, %(args)s, %(state)s, %(hostname)s, NOW() + INTERVAL %(scheduled)s SECOND, %(resource)s, %(task)s);'''

    d = ssscrape.database.run_operation(query_tpl, values)
    d.addErrback(log.err)
    return d


