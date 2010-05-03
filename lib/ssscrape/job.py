
from twisted.python import log

import ssscrape


class Job:
    '''
    Job class.

    This class represents a job to be executed. It is mapped 1-to-1 to a
    record in the ssscrape_job database table.

    Several methods accept a state parameter. Use one of the following
    JOB.STATES.* constants as a value: PENDING, RUNNING, COMPLETED,
    PERMANENT_ERROR, TEMPORARY_ERROR, e.g. JOB.STATES.COMPLETED.

    Note that this class should not be instantiated directly. The methods in
    C{WorkerPool} and in the C{job_queue} module return C{Job} instances.
    '''

    # These states can be accessed as Job.STATES.PENDING
    STATES = ssscrape.misc.AttrDict({
        'PENDING': 'pending',
        'RUNNING': 'running',
        'COMPLETED': 'completed',
        'PERMANENT_ERROR': 'permanent-error',
        'TEMPORARY_ERROR': 'temporary-error',
    })

    def __init__(self, id, type, program, args, task_id=None, resource_id=None):
        '''
        Create a new C{Job} instance.
        '''

        assert isinstance(id, int)
        assert isinstance(program, basestring)
        assert isinstance(args, basestring)

        # NOTE: This method cannot do any computationally expensive tasks since
        # it is called while we hold a lock on the jobs db table!

        self.id = id
        self.type = type
        self.program = program
        self.args = args
        self.task_id = task_id
        self.resource_id = resource_id
        self.state = Job.STATES.RUNNING
        self.output = None
        self.process_id = None


    # helper methods

    def _update_row_data(self, *args, **kwargs):
        '''
        Updates the database row based on the values supplied.
        '''
        columns = [
                'state',
                'message',
                'output',
                'hostname',
                'process_id',
                'exit_code',
                'start',
                'end',
                'task_id',
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
            if column in ('start', 'end') and value == 'NOW':
                update_parts.append('%s = NOW()' % column)
                # no placeholder, so nothing added to values
            else:
                update_parts.append('%s = %%s' % column)
                values.append(value)

        query = r'''UPDATE ssscrape_job SET %s WHERE id = %%s''' % ', '.join(update_parts)

        # add the id (used in the WHERE part)
        values.append(self.id)

	return query, values
        # execute the query
        #ssscrape.database.run_operation(query, values)


    def __str__(self):
        '''
        String representation for this job.
        '''
        return 'Job #%d' % self.id


    def set_state(self, new_state):
        '''
        Change the state of this job.
        '''

        assert new_state in Job.STATES.values()
        self.state = new_state
        query, values = self._update_row_data(state=self.state)
        ssscrape.database.run_operation(query, values)


    def set_message(self, message):
        '''
        Set a status message for this job.
        '''

        assert isinstance(message, basestring)
        query, values = self._update_row_data(message=message)
        ssscrape.database.run_operation(query, values)


    def set_pid(self, process_id):
        '''
        Set the process id on this job.
        '''
        assert isinstance(process_id, int)
        self.process_id = process_id
        query, values = self._update_row_data(process_id=process_id)
        ssscrape.database.run_operation(query, values)


    def _mark_as_finished(self, transaction, exit_code=None, output=None):
        query, values = self._update_row_data(state=self.state, exit_code=exit_code, output=output, end='NOW')
        transaction.execute(query, values)
        if self.state in (Job.STATES.COMPLETED, Job.STATES.PERMANENT_ERROR):
            transaction.execute('DELETE FROM ssscrape_job_log WHERE id = %s', (self.id))
            transaction.execute('INSERT INTO ssscrape_job_log SELECT * FROM ssscrape_job WHERE id = %s', (self.id))
            transaction.execute('DELETE FROM ssscrape_job WHERE id = %s', (self.id))

    def mark_as_finished(self, new_state, exit_code=None, output=None):
        '''
        Mark a job as finished.
        '''
        assert new_state in (Job.STATES.COMPLETED, Job.STATES.TEMPORARY_ERROR, Job.STATES.PERMANENT_ERROR)
        assert exit_code is None or isinstance(exit_code, int)
        assert output is None or isinstance(output, unicode)

        self.state = new_state

        ssscrape.database.run_interaction(self._mark_as_finished, exit_code, output)
