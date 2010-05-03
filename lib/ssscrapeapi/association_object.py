
import sys
from UserDict import UserDict

import ssscrapeapi

class AssociationObject(UserDict):
    '''
    An object with methods to associate objects in a database. The basis is a simple table that contains (id1, id2) pairs
    in a many-tomany relationship.
    '''

    def __init__(self):
        '''
        Initializes the table object.
        '''

        UserDict.__init__(self)
        # self.table contains the name of the table that this object will be saved to 
        self.table = None
	self.field_from = None
	self.field_to = None

    def _get_field_names(self, use_from = True):
        '''
	Get field names to use in queries. Order depends on the use_from parameter.
	'''
	
        if use_from:
	    field_search = self.field_from
	    field_result = self.field_to
	else:
	    field_search = self.field_to
	    field_result = self.field_from
        return field_search, field_result

    def get(self, id, use_from = True):
        '''
	Get all ids which link to the given id. Direction can be set using the use_from parameter.
	'''
	
        field_search, field_result = self._get_field_names(use_from)	    
	cursor = ssscrapeapi.database.execute('''SELECT `%s` FROM `%s` WHERE `%s` = %%s''' % (field_result, self.table, field_search), (id))
	rows = cursor.fetchall()
	return [row[0] for row in rows]

    def delete(self, id_from, id_to):
        '''
	Delete a link combination from the database.
	'''
	
        ssscrapeapi.database.execute('''DELETE FROM `%s` WHERE `%s` = %%s AND `%s` = %%s''' % (self.table, self.field_from, self.field_to), (id_from, id_to))

    def delete_all(self, id, use_from = True):
        '''
	Delete all ids which link to the given id. Direction can be wset using the use_from parameter.
	'''

	field_search, field_result = self._get_field_names(use_from)
	ssscrapeapi.database.execute('''DELETE FROM `%s` WHERE `%s` = %%s''' % (self.table, field_search), (id))

    def add(self, id_from ,id_to):
        '''
	Add an id combination.
	'''

	ssscrapeapi.database.execute('''REPLACE INTO `%s` (`%s`, `%s`) VALUES(%%s, %%s)''' % (self.table, self.field_from, self.field_to), (id_from, id_to))
