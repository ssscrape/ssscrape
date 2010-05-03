import sys
import os.path
import datetime

from twisted.internet import task
from twisted.internet import reactor
from twisted.python import log

import ssscrape

def tasks_list_problematic(minRuns, maxRuns):
    pass

def jobs_count_state_by_types(job_type = '', use_log = False):
    '''
    Generate an overview of states per job type, with their counts.

    The @job_type specifies an optinal job type to select on. The @use_log
    parameter tells is the log table should be used instead of the normal table.
    '''

    if use_log:
        job_table = 'ssscrape_job_log'
    else:
        job_table = 'ssscrape_job'
    if job_type == '':
        job_where = ''
    else:
        job_where = ' WHERE `type` = %(type)s '
    query = '''SELECT `type`, `state`, COUNT(*) FROM ''' + job_table + job_where + ''' GROUP BY `type`, `state` ORDER BY `type`, `state`'''
    values = {
        'type': job_type
    }
    d = ssscrape.database.run_query(query, values)
    d.addCallback(_jobs_count_state_by_types)
    return d

def tasks_count_state_by_types(task_type = ''):
    '''
    Generate an overview of states per task, with their counts.

    The @task_type specifies an optinal job type, or task id to filter on. The @use_log
    parameter tells is the log table should be used instead of the normal table.
    '''

    if task_type == '':
        job_where = ''
    else:
        try:
            x = int(task_type)
            job_where = ' AND `task_id` = %(type)s '
        except TypeError:
            job_where = ' AND `type` = %(type)s '
        except ValueError:
            job_where = ' AND `type` = %(type)s '
    query = '''(SELECT `task_id`, `state`, COUNT(*) FROM `ssscrape_job` WHERE `task_id` IS NOT NULL ''' + job_where + '''GROUP BY `task_id`, `state`) UNION (SELECT `task_id`, `state`, COUNT(*) FROM `ssscrape_job_log` WHERE `task_id` IS NOT NULL ''' + job_where + '''GROUP BY `task_id`, `state`) ORDER BY `task_id`, `state`'''
    values = {
        'type': task_type
    }
    d = ssscrape.database.run_query(query, values)
    d.addCallback(_tasks_count_state_by_types)
    return d

def _tasks_count_state_by_types(rows):
    '''
    Callback to convert type, state counts into a more workable structure.
    '''
    
    counts = {} 
    for row in rows:
        row_task = row[0]
        row_state = row[1]
        row_count = row[2]
        try:
            counts[row_task][row_state] += row_count
        except KeyError:
            try:
                counts[row_task][row_state] = row_count
            except KeyError:
                counts[row_task] = {row_state: row_count}
    return counts

def _jobs_count_state_by_types(rows):
    '''
    Callback to convert type, state counts into a more workable structure.
    '''
    
    counts = {} 
    for row in rows:
        try:
            counts[row[0]][row[1]] = row[2]
        except KeyError:
            counts[row[0]] = {row[1]: row[2]}
    return counts

def feed_statistics(tag = '', period = 10000):
    '''
    Generate an overview of feeds with a given tag and their feed items.
    '''

    if tag == '':
        tag_condition = ' %(tag)s="" '
    else:
        tag_condition = ' m.tags IS NOT NULL AND FIND_IN_SET(%(tag)s, m.tags) '

    period = ssscrape.misc.parse_time_string_to_seconds(period)

    query = '''
            SELECT m.tags tags, DATE(i.pub_date) pub_date, count(distinct m.feed_id) feeds, count(*) count, count(content_clean) count_content
            FROM ssscrape.ssscrape_feed_metadata m, ssscrape.ssscrape_feed_item i 
            WHERE m.feed_id = i.feed_id 
              AND ''' + tag_condition + ''' 
              AND i.pub_date >= DATE_SUB(CURDATE(),INTERVAL ''' + str(period) + ''' SECOND) AND i.pub_date <= NOW() 
            GROUP BY m.tags, DATE(i.pub_date)
            '''
    values = {
        'tag': tag,
    }
    d = ssscrape.database.run_query(query, values)
    d.addCallback(_feed_statistics)
    return d

def _feed_statistics(rows):
    '''
    Callback to convert into a more workable structure.
    '''
    counts = {} 
    dates = {}
    for row in rows:
        (tags, date, rest) = (row[0], row[1], row[2:])  
        dates[date] = 1
        try:
            counts[tags][date] = rest 
        except KeyError:
            counts[tags] = {date: rest}
    for tags in counts.keys():
        for date in dates.keys():
            if date not in counts[tags]:
                counts[tags][date] = [0, 0, 0]
    return counts
