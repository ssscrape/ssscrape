
import sys

import ssscrapeapi

class EventGeo(ssscrapeapi.AssociationObject):
    def __init__(self):
        '''
	Initializes a EventGeo object.
	'''

	ssscrapeapi.AssociationObject.__init__(self)

	self.table = 'ssscrape_feed_item_event2geo'
	self.field_from = 'event_id'
	self.field_to = 'geo_id'
