
import sys

import ssscrapeapi

class FeedAuthor(ssscrapeapi.AssociationObject):
    def __init__(self):
        '''
        Initializes a FeedAuthor object.
        '''

        ssscrapeapi.AssociationObject.__init__(self)

        self.table = 'ssscrape_feed2author'
	self.field_from = 'feed_id'
	self.field_to = 'author_id'

class FeedGeo(ssscrapeapi.AssociationObject):
    def __init__(self):
        '''
	Initializes a FeedGeo object.
	'''

	ssscrapeapi.AssociationObject.__init__(self)

	self.table = 'ssscrape_feed2geo'
	self.field_from = 'feed_id'
	self.field_to = 'geo_id'
