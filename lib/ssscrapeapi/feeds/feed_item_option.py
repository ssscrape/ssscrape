
import sys
import datetime

import ssscrapeapi
from ssscrapeapi.job_table_item import save_job_table_item

class FeedItemOption(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes a FeedItemOption object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_feed_item_option'
        self.fields = [
            'feed_item_id',
            'option',
            'value',
            'mod_date'
        ]
        self.unescaped = [
            'mod_date'
        ]

    def save(self):
        is_new = not self.has_key('id')

        # set the modification date

        self['mod_date'] = 'NOW()'
        if not 'mod_date' in self.unescaped:
           self.unescaped.insert(0, ',mod_date')

        ssscrapeapi.TableObject.save(self)

        if not is_new:
            return

        save_job_table_item(self)

    def add(self, object):
        '''
        Adds an association to the given object.
        '''

        if isinstance(object, ssscrapeapi.feeds.FeedItem):
            self['feed_item_id'] = object['id']

    def delete(self, object):
        '''
        Deletes as association to the given object.
        '''

        if isinstance(object, ssscrapeapi.feeds.FeedItem):
            self['feed_item_id'] = None
