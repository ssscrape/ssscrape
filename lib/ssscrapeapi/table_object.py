
import sys
from UserDict import UserDict

import ssscrapeapi

class TableObject(UserDict):
    '''
    An object with methods to store items in a database.
    '''

    def __init__(self, *args, **kwargs):
        '''
        Initializes the table object.
        '''

        UserDict.__init__(self)
        # self.config_section contains the name of the config section to use for the DB
        self.config_section = 'database-workers' #FIXME: fix this more nicely in the ufutre
        # self.table contains the name of the table that this object will be saved to 
        self.table = None
        # self.fields is a list of allowed fields in the table
        self.fields = []
        # self.unescaped is a list of fields that are not escaped in the queries
        self.unescaped = []

        # now set default values for given properties
        for kw in kwargs:
	        self[kw] = kwargs[kw]

    def load(self, id):
        '''
        Loads the object from the table based on the given id.
        '''

        # first backtic the field names
        fields = ['`' + field + '`' for field in self.fields]
        # execute the query
        cursor = ssscrapeapi.database.execute('SELECT %s FROM %s WHERE id = %%s' % (', '.join(fields), self.table), (id), self.config_section)
        row = cursor.fetchone()

        # self the field values in self
        i = 0 
        for field in self.fields:
            self[field] = row[i]
            i = i + 1

        # if we get this far, loading was successful. Hence, set the id.
        self['id'] = id

    def save(self):
        '''
        Saves the object in the table and generates an id.
        '''

        # if this object has an id
        if self.has_key('id'):
            # we need to update the row data
            ssscrapeapi.database.update_row_data(self.table, self['id'], self.fields, self.unescaped, self.config_section, **self.data)
        else:
            # else we need to insert
            ssscrapeapi.database.insert_row_data(self.table, self.fields, self.unescaped, self.config_section, **self.data)

            # go and fetch the inserted id
            cursor = ssscrapeapi.database.execute('''SELECT LAST_INSERT_ID()''', None, self.config_section)
            row = cursor.fetchone()
            self['id'] = int(row[0])

    def find(self):
        '''
	    Finds the id of an object in the table with the specified key/value pairs of the object.
	    '''
        # try to find the id
        id = ssscrapeapi.database.find_row_data(self.table, self.fields, self.unescaped, self.config_section, **self.data)

        # if a valid id was found, update the property
        if id > 0:
            self['id'] = id
        
        return id

    def retrieve(self, *args, **kwargs):
        '''
	    Finds the id of an object in the table using the specified kkey/value pairs given as arguments, and sets the ID of the object, if found.
	    '''

	    # try to find the id
        id = ssscrapeapi.database.find_row_data(self.table, self.fields, self.unescaped, self.config_section, **kwargs)

        # if a valid id was found, update the property
        if id > 0:
            self['id'] = id

        return id


    def destroy(self):
        if self['id']:
            cursor = ssscrapeapi.database.execute('DELETE FROM %s WHERE id = %%s' % (self.table), (self['id']), self.config_section)
            return cursor.rowcount
        else:
            return False
