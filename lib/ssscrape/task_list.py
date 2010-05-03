
from twisted.python import log

import ssscrape

def find_candidates():
    '''
    Find candidate tasks that need to be scheduled.
    '''
    d = ssscrape.database.run_query('''
        SELECT t.id, t.type, t.program, t.args, t.state, t.hostname, t.periodicity, t.hour, t.minute, t.second, t.latest_run, t.resource_id, t.data, NOW()
        FROM ssscrape_task t
        LEFT JOIN ssscrape_job j ON t.id = j.task_id
        WHERE
            t.state = "enabled"
            AND (t.latest_run IS NULL OR (t.latest_run + t.periodicity) <= NOW())
            AND j.task_id IS NULL;
        ''')
    d.addCallback(_find_candidates)
    return d

def _find_candidates(rows):
    '''
    Callback to convert database rows into a list of task ids.
    '''
    tasks = []
    for row in rows:
        task = ssscrape.Task(row[0])
        dummyid, task.type, task.program, task.program_args, task.state, task.hostname, task.periodicity, task.hour, task.minute, task.second, task.latest_run, task.resource_id, task.data, task.now = row
        tasks.append(task)
    return tasks

def add(task_type, program, program_args, n_seconds, hour=None, minute=None, resource=None, hostname=None):
    # Prepare values

    values = {
        'state': ssscrape.Task.STATES.ENABLED,
        'type': task_type,
        'program': program,
        'args': program_args,
        'periodicity': str(n_seconds),
        'hour': hour,
        'minute': minute,
        'resource_id': resource,
        'hostname': hostname
    }


    # Execute query

    query_tpl = \
        '''INSERT INTO `ssscrape_task` ''' \
        '''(`type`, `program`, `args`, `state`, `hostname`, `periodicity`, `hour`, `minute`, `resource_id`) ''' \
        '''VALUES (%(type)s, %(program)s, %(args)s, %(state)s, %(hostname)s, SEC_TO_TIME(%(periodicity)s), %(hour)s, %(minute)s, %(resource_id)s);'''

    d = ssscrape.database.run_operation(query_tpl, values)
    d.addErrback(log.err)
    return d
