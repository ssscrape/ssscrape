
import sys

import ssscrapeapi

class FeedItemAuthor(ssscrapeapi.AssociationObject):
    def __init__(self):
        '''
        Initializes a FeedAuthor object.
        '''

        ssscrapeapi.AssociationObject.__init__(self)

        self.table = 'ssscrape_feed_item2author'
	self.field_from = 'feed_item_id'
	self.field_to = 'author_id'

class FeedItemGeo(ssscrapeapi.AssociationObject):
    def __init__(self):
        '''
	Initializes a FeedGeo object.
	'''

	ssscrapeapi.AssociationObject.__init__(self)

	self.table = 'ssscrape_feed_item2geo'
	self.field_from = 'feed_item_id'
	self.field_to = 'geo_id'

class FeedItemCategory(ssscrapeapi.AssociationObject):
    def __init__(self):
        '''
	Initializes a FeedGeo object.
	'''

	ssscrapeapi.AssociationObject.__init__(self)

	self.table = 'ssscrape_feed_item2category'
	self.field_from = 'feed_item_id'
	self.field_to = 'category_id'
