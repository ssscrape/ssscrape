
import sys
import datetime

import ssscrapeapi
from ssscrapeapi.job_table_item import save_job_table_item

class FeedItemComment(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes a FeedItemComment object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_feed_item_comment'
        self.fields = [
            'feed_item_id',
            'guid',
	    'comment',
            'author',
            'author_id',
            'pub_date',
            'mod_date'
        ]
        self.unescaped = [
            'pub_date',
            'mod_date'
        ]

    def save(self):
        is_new = not self.has_key('id')

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

        if not is_new:
            return

        save_job_table_item(self)

    def add(self, object):
        '''
        Adds an association to the given object.
        '''

        if isinstance(object, ssscrapeapi.feeds.Author):
            self['author_id'] = object['id']

    def delete(self, object):
        '''
        Deletes as association to the given object.
        '''

        if isinstance(object, ssscrapeapi.feeds.Author):
            self['author_id'] = None

    def set_item(self, object):
        '''
        Sets the feed item id to the given object.
        '''

        self['feed_item_id'] = object['id']

