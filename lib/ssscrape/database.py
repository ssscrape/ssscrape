from _mysql_exceptions import MySQLError

from twisted.enterprise import adbapi
from twisted.python import log

import ssscrape


_error_callbacks = []

def _setup_db_pool():
    '''
    Setup a database connection pool
    '''
    db_pool = adbapi.ConnectionPool(
        'MySQLdb',
        cp_min = 1,
        cp_max = ssscrape.config.get_int('database', 'max-connections'),
        cp_noisy = False, # set to true for db debugging
        cp_reconnect = True,
        cp_openfun = _db_connection_made_cb,
        host = ssscrape.config.get_string('database', 'hostname'),
        port = ssscrape.config.get_int('database', 'port', 3306),
        db = ssscrape.config.get_string('database', 'database'),
        user = ssscrape.config.get_string('database', 'username'),
        passwd = ssscrape.config.get_string('database', 'password'),
        use_unicode = True
    )
    return db_pool

def _db_connection_made_cb(connection):
    '''
    Callback when a database connection is established.
    '''

    # set the connection character set
    connection.set_character_set('UTF8')

def _sql_error_cb(failure):
    '''
    Abort on SQL errors.

    These are serious and should not be ignored.
    '''

    failure.trap(MySQLError)
    failure.trap(adbapi.ConnectionLost)

    # log the error message...
    log.err(failure)

    # ...and execute error callbacks
    for (cb, args, kwargs) in _error_callbacks:
        cb(*args, **kwargs)


# Public API starts here

def add_error_callback(callback, *args, **kwargs):
    _error_callbacks.append((callback, args, kwargs))

def run_query(query, args=None):
    '''
    Execute a SQL statement and return a Deferred with the resulting rows
    (if any).
    '''
    assert isinstance(query, basestring)
    d = _db_pool.runQuery(query, args)
    d.addErrback(_sql_error_cb)
    return d

def run_operation(query, args):
    '''
    Execute a SQL statement and return a Deferred that fires None.

    The first parameter should be a SQL query with %s-style placeholders.
    The remaining parameters are the values used for the substitution.
    '''
    assert isinstance(query, basestring)
    d = _db_pool.runOperation(query, args)
    d.addErrback(_sql_error_cb)
    return d

def run_interaction(interaction, *args, **kwargs):
    '''
    Run a database interaction.
    '''
    d = _db_pool.runInteraction(interaction, *args, **kwargs)
    d.addErrback(_sql_error_cb)
    return d



# Initialization (happens only once)

try:
    _db_pool
except NameError:
    _db_pool = _setup_db_pool()

