
from twisted.python import log

import ssscrape

class Task:
    '''
    Task class.

    This class represents a periodic task. It is mapped 1-to-1 to a
    record in the ssscrape_task database table.

    Several methods accept a state parameter. Use one of the following
    Task.STATES.* constants as a value: ENABLED, DISABLED, e.g. 
    Task.STATES.COMPLETED.

    '''
    
    
    # These states can be accessed as Task.STATES.ENABLED
    STATES = ssscrape.misc.AttrDict({
        'ENABLED': 'enabled',
        'DISABLED': 'disabled',
    })
    
    def __init__(self, id):
        '''
        Create a new C{Task} instance.
        '''

        self.id = id

    # helper methods

    def _update_row_data(self, *args, **kwargs):
        '''
        Updates the database row based on the values supplied.
        '''
        columns = [
                'type',
                'program',
                'args',
                'state',
                'hostname',
                'periodicity',
                'hour',
                'minute',
                'second',
                'latest_run',
                'data',
                'resource_id'
                ]

        row_data = {}

        for col in columns:
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
            # hack to have this method work with NOW values
            if column in ('latest_run') and value == 'NOW':
                update_parts.append('%s = NOW()' % column)
                # no placeholder, so nothing added to values
            else:
                update_parts.append('%s = %%s' % column)
                values.append(value)

        query = r'''UPDATE ssscrape_task SET %s WHERE id = %%s''' % ', '.join(update_parts)

        # add the id (used in the WHERE part)
        values.append(self.id)

	return query, values
        # execute the query
        #ssscrape.database.run_operation(query, values)


    def __str__(self):
        '''
        String representation for this task.
        '''
        return 'Task #%d' % self.id


    def set_state(self, new_state):
        '''
        Change the state of this task.
        '''

        assert new_state in Task.STATES.values()
        self.state = new_state
        query, values = self._update_row_data(state=self.state)
        ssscrape.database.run_operation(query, values)

    def mark_latest_run(self, transaction):
        '''
        Update when a job of this task starting running.
        '''

        query, values = self._update_row_data(latest_run='NOW')
        transaction.execute(query, values)

    def load(self):
        d = ssscrape.database.run_query('''SELECT `id`, `type`, `program`, `args`, `state`, `hostname`, `periodicity`, `hour`, `minute`, `second`, `latest_run`, `resource_id`, `data` FROM ssscrape_task WHERE id = %s''', self.id)
        d.addCallback(self._load)
        return d

    def _load(self, rows):
        dummyid, self.type, self.program, self.program_args, self.state, self.hostname, self.periodicity, self.hour, self.minute, self.second, self.latest_run, self.resource, self.data = rows[0]
        return self
