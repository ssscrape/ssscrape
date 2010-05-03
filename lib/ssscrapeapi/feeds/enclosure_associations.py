
import sys

import ssscrapeapi

class EnclosureRating(ssscrapeapi.AssociationObject):
    def __init__(self):
        '''
	Initializes a EnclosureRating object.
	'''

	ssscrapeapi.AssociationObject.__init__(self)

	self.table = 'ssscrape_enclosure2rating'
	self.field_from = 'enclosure_id'
	self.field_to = 'rating_id'

class EnclosureCategory(ssscrapeapi.AssociationObject):
    def __init__(self):
        '''
	Initializes a  EnclosureCategory object.
	'''

	ssscrapeapi.AssociationObject.__init__(self)

	self.table = 'ssscrape_enclosure2category'
	self.field_from = 'enclosure_id'
	self.field_to = 'category_id'
