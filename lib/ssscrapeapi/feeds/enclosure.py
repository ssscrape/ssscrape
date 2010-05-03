
import sys

import ssscrapeapi

class Enclosure(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes an Enclosure  object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_enclosure'
        self.fields = [
	    'feed_item_id',
	    'guid',
	    'link',
	    'title',
	    'description',
	    'audio_channels',
	    'width',
	    'height',
	    'filesize',
	    'duration',
	    'bit_rate',
	    'sampling_rate',
	    'frame_rate',
	    'expression',
	    'mime',
	    'mime_real',
	    'language',
	    'copyright_url',
	    'copyright_attribution',
	    'medium',
	    'pub_date',
	    'mod_date'
        ]
	self.unescaped = [
	    'pub_date',
	    'mod_date'
	]
        self.rating_assoc = ssscrapeapi.feeds.EnclosureRating()
        self.category_assoc = ssscrapeapi.feeds.EnclosureCategory()

    def add(self, object):
        '''
        Adds an association to the given object.
        '''

        if isinstance(object, ssscrapeapi.feeds.Rating):
            self.rating_assoc.add(self['id'], object['id'])

        if isinstance(object, ssscrapeapi.feeds.Category):
            self.category_assoc.add(self['id'], object['id'])

    def delete(self, object):
        '''
        Deletes as association to the given object.
        '''

        if isinstance(object, ssscrapeapi.feeds.Rating):
            self.rating_assoc.delete(self['id'], object['id'])

        if isinstance(object, ssscrapeapi.feeds.Category):
            self.category_assoc.delete(self['id'], object['id'])

    def set_item(self, object):
        '''
        Associates the feed item with the enclosure.
        '''

        self['feed_item_id'] = object['id']
