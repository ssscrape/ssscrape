
import sys
import datetime

import ssscrapeapi

class Feed(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes a Feed object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_feed'
        self.fields = [
            'url',
            'title',
	    'description',
	    'language',
	    'copyright',
	    'type',
	    'class',
	    'favicon',
	    'encoding',
	    'lastmod',
	    'etag',
	    'pub_date',
	    'mod_date',
        'hub',
        'subscription_state'
        ]
	self.unescaped = [
	    'pub_date',
	    'mod_date'
	]

        self.author_assoc = ssscrapeapi.feeds.FeedAuthor()
        self.geo_assoc = ssscrapeapi.feeds.FeedGeo()


    def save(self):
        if not self.has_key('id'):
            self['pub_date'] = 'NOW()'
        self['mod_date'] = 'NOW()'
        for date in ['pub_date', 'mod_date']:
            try:
                date_idx = self.unescaped.index(date)
            except ValueError:
                date_idx = -1
            # if pub_date is a datetime object,
            # we must not try to unescape it.
            try:
                if isinstance(self[date], datetime.datetime):
                    if date_idx >= 0:
                        del self.unescaped[date_idx]
            except KeyError:
                pass
        ssscrapeapi.TableObject.save(self)

    def add(self, object):
        '''
        Adds an association to the given object.
        '''

        if isinstance(object, ssscrapeapi.feeds.Author):
            self.author_assoc.add(self['id'], object['id'])

        if isinstance(object, ssscrapeapi.feeds.Geo):
            self.geo_assoc.add(self['id'], object['id'])

    def delete(self, object):
        '''
        Deletes as association to the given object.
        '''

        if isinstance(object, ssscrapeapi.feeds.Author):
            self.author_assoc.delete(self['id'], object['id'])

        if isinstance(object, ssscrapeapi.feeds.Geo):
            self.geo_assoc.delete(self['id'], object['id'])
