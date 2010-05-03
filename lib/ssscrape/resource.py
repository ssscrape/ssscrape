
import time

from twisted.python import log

import ssscrape

class Resource:
    def __init__(self, id):
        self.id = id

    # helper methods

    def _update_row_data(self, *args, **kwargs):
        '''
        Updates the database row based on the values supplied.
        '''
        columns = [
                  'name',
                  'latest_run',
                  'interval'
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
                update_parts.append('`%s` = NOW()' % column)
                # no placeholder, so nothing added to values
            else:
                update_parts.append('`%s` = %%s' % column)
                values.append(value)

        query = r'''UPDATE ssscrape_resource SET %s WHERE id = %%s''' % ', '.join(update_parts)

        # add the id (used in the WHERE part)
        values.append(self.id)

	return query, values
        # execute the query
        #ssscrape.database.run_operation(query, values)

    def load(self):
        d = ssscrape.database.run_query('''SELECT `name`, `latest_run`, `interval` FROM ssscrape_resource WHERE id = %s''', self.id)
	d.addCallback(self._load)
        return d

    def _load(self, rows):
       for row in rows:
           self.name = row[0]
           self.latest_run = row[1]
           self.interval = row[2]
       return self

    def mark_latest_run(self, transaction):
        '''
        Update when a job of this task starting running.
        '''

        query, values = self._update_row_data(latest_run='NOW')
        transaction.execute(query, values)

