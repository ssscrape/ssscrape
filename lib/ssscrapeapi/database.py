
import sys
import MySQLdb
import time

import ssscrapeapi

try:
    _handle
except NameError:
    _handles = {}
    _cursors = {}
    _handle = None
    _cursor = None

# main methods

def connect(config_section='database-workers'):
    '''
    Connect to a database server as specified in the designated configuration section.
    '''

    if not ssscrapeapi.database._handles.has_key(config_section):
        if ssscrapeapi.config.has_section(config_section):
            ssscrapeapi.database._handles[config_section] = MySQLdb.connect(
                host = ssscrapeapi.config.get_string(config_section, 'hostname'),
                port = ssscrapeapi.config.get_int(config_section, 'port', 3306),
                db = ssscrapeapi.config.get_string(config_section, 'database'),
                user = ssscrapeapi.config.get_string(config_section, 'username'),
                passwd = ssscrapeapi.config.get_string(config_section, 'password'),
                charset = "utf8",
                use_unicode = True
            )
            ssscrapeapi.database._cursors[config_section] = _handles[config_section].cursor()
        else:
            # the database section is a fallback
            connect('database') # connect if not connected
            ssscrapeapi.database._handles[config_section] = ssscrapeapi.database._handles['database']
            ssscrapeapi.database._cursors[config_section] = ssscrapeapi.database._cursors['database']
    return _cursors[config_section]

def disconnect(config_section='database-workers'):
    '''
    Disconnect from the database server.
    '''

    if _cursors.has_key(config_section):
        try:
            _cursors[config_section].close()
        except MySQLdb.ProgrammingError: # was already closed
            pass
        del _cursors[config_section]
    if _handles.has_key(config_section):
        try:
            _handles[config_section].close()
        except MySQLdb.ProgrammingError: # Was already closed
            pass
        del _handles[config_section]

def cursor(config_section='database-workers'):
    '''
    Return a database cursor.
    '''

    return _cursors[config_section]

def execute(query, values = None, config_section='database-workers'):
    '''
    Execute a query and return the database cursor.
    '''

    #print >>sys.stderr, "Executing query %s, with values %s on DB %s" % (query, values, config_section)
    try:
        #t = time.time()
        _cursors[config_section].execute(query, values)
        #t2 = time.time()
        #print "Time taken for query: %s\n-- %s seconds" % (query, str(t2 - t))
    except Exception, e:
        print e
        raise e    
    return _cursors[config_section]

# helper methods

def insert_row_data(table, valid_fields, unescaped_fields = [], config_section = 'database-workers', *args, **kwargs):
    '''
    Inserts a database row based on the values supplied.
    '''

    row_data = {}

    for col in valid_fields:
        if not col in kwargs:
            continue

        row_data[col] = kwargs[col]

    if not row_data:
        # Nothing to update
        return

    # prepare a query
    query = r'''INSERT INTO ''' + table
    field_parts = []
    insert_parts = []
    values = []
    for column, value in row_data.items():
        field_parts.append('`' + column + '`')
        # hack to have this method work with unescaped values
        if column in unescaped_fields: 
            insert_parts.append('%s' % (value))
            # no placeholder, so nothing added to values
        else:
            insert_parts.append('%s')
            values.append(value)

    query = query + r''' (%s) VALUES(%s)''' % (', '.join(field_parts), ', '.join(insert_parts)) 

    # return query, values
    # execute the query
    retval = execute(query, values, config_section)
    return retval

def update_row_data(table, identifier, valid_fields, unescaped_fields = [], config_section = 'database-workers', *args, **kwargs):
    '''
    Updates the database row based on the values supplied.
    '''

    row_data = {}

    for col in valid_fields:
        if not col in kwargs:
            continue

        row_data[col] = kwargs[col]

    if not row_data:
        # Nothing to update
        return

    # prepare a query
    update_parts = []
    values = []
    for column, value in row_data.items():
        # hack to have this method work with unescaped values
        if column in unescaped_fields: 
            update_parts.append('`%s` = %s' % (column, value))
            # no placeholder, so nothing added to values
        else:
            update_parts.append('`%s` = %%s' % column)
            values.append(value)

    query = r'''UPDATE ''' + table + r''' SET %s WHERE `id` = %%s''' % ', '.join(update_parts)

    # add the id (used in the WHERE part)
    values.append(identifier)

    # return query, values
    # execute the query
    return execute(query, values, config_section)

def find_row_data(table, valid_fields, unescaped_fields = [], config_section = 'database-workers', *args, **kwargs):
    '''
    Finds the id of the database row based on the values supplied.
    '''

    row_data = {}

    for col in valid_fields:
        if not col in kwargs:
            continue

        row_data[col] = kwargs[col]

    if not row_data:
        # Nothing to update
        return

    # prepare a query
    find_parts = []
    values = []
    for column, value in row_data.items():
        # hack to have this method work with unescaped values
        if column in unescaped_fields: 
            find_parts.append('`%s` = %s' % (column, value))
            # no placeholder, so nothing added to values
        else:
            find_parts.append('`%s` = %%s' % column)
            values.append(value)

    query = r'''SELECT `id` FROM ''' + table + r''' WHERE %s''' % ' AND '.join(find_parts)

    # return query, values
    # execute the query
    cursor = execute(query, values, config_section)

    # now try to get the id
    id = 0
    try:
        row = cursor.fetchone()
        id = int(row[0])
    except IndexError:
        id = 0
    except TypeError:
        id = 0

    return id
