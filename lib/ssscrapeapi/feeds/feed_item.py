
import sys
import datetime

import ssscrapeapi
from ssscrapeapi.job_table_item import save_job_table_item

class FeedItem(ssscrapeapi.TableObject):
    def __init__(self, *args, **kwargs):
        '''
        Initializes a FeedItem object.
        '''

        ssscrapeapi.TableObject.__init__(self, **kwargs)

        self.table = 'ssscrape_feed_item'
        self.fields = [
            'feed_id',
            'guid',
            'title',
            'summary',
            'content',
            'content_clean_html',
            'content_clean',
            'comments_url',
            'pub_date',
            'mod_date',
            'fetch_date',
            'copyright',
            'language'
        ]
        self.unescaped = [
            'mod_date'
        ]
        self.author_assoc = ssscrapeapi.feeds.FeedItemAuthor()
        self.geo_assoc = ssscrapeapi.feeds.FeedItemGeo()
        self.category_assoc = ssscrapeapi.feeds.FeedItemCategory()
        self.options = {}
        self.options_changed = False

    def save(self):
        is_new = not self.has_key('id')

        for date in ['pub_date', 'mod_date', 'fetch_date']:
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

        # record the date/time when we first came across this item
        if is_new:
           self['fetch_date'] = 'NOW()'
           self.unescaped.append('fetch_date')

        # save info
        ssscrapeapi.TableObject.save(self)

        self.save_options()

        if not is_new:
            return

        save_job_table_item(self)
        
    def add(self, object):
        '''
        Adds an association to the given object.
        '''

        if isinstance(object, ssscrapeapi.feeds.Author):
            self.author_assoc.add(self['id'], object['id'])

        if isinstance(object, ssscrapeapi.feeds.Geo):
            self.geo_assoc.add(self['id'], object['id'])

        if isinstance(object, ssscrapeapi.feeds.Category):
            self.category_assoc.add(self['id'], object['id'])

        if isinstance(object, ssscrapeapi.feeds.FeedItemOption):
            self.options[object['option']] = object
            object.add(self)

    def delete(self, object):
        '''
        Deletes as association to the given object.
        '''

        if isinstance(object, ssscrapeapi.feeds.Author):
            self.author_assoc.delete(self['id'], object['id'])

        if isinstance(object, ssscrapeapi.feeds.Geo):
            self.geo_assoc.delete(self['id'], object['id'])

        if isinstance(object, ssscrapeapi.feeds.Category):
            self.category_assoc.delete(self['id'], object['id'])

        if isinstance(object, ssscrapeapi.feeds.FeedItemOption):
            try:
                del self.options[object['option']]
            except KeyError:
                pass
            object.delete(self)

    def set_feed(self, object):
        '''
        Sets the feed id to the given object.
        '''

        self['feed_id'] = object['id']

    def num_comments(self):
        '''
        Gets the number of comments on this item.
        '''

        c = ssscrapeapi.database.execute('''SELECT COUNT(*) FROM ssscrape_feed_item_comment WHERE feed_item_id = %s''', (self['id']))
        row = c.fetchone()
        return int(row[0])

    def _find_option(self, option_name):
        '''
        Find a named option, if it exists.
        '''

        option = ssscrapeapi.feeds.FeedItemOption()
        option['option'] = option_name
        option.add(self)
        id = option.find()
        if id > 0:
            option.load(id)

        if option.has_key('id'):
            return option

    def get_option(self, option_name):
        '''
        Get the value of a named option.
        '''

        try:
            option = self.options[option_name]
        except KeyError:
            option = self._find_option(option_name) 
            if option:
                self.add(option)

        if option:
            return option['value']

    def set_option(self, option_name, option_value):
        '''
        Set the value of a named option.
        '''

        if not self.options.has_key(option_name):
            option = self._find_option(option_name)
        else:
            option = self.options[option_name]

        if not option: 
            option = ssscrapeapi.feeds.FeedItemOption()
            option['option'] = option_name

        self.add(option)

        self.options[option_name]['value'] = option_value
        self.options_changed = True

    def load_options(self):
        '''
        Load named options for this feed item.
        '''

        if not self.has_key('id'):
            return

        cursor = ssscrapeapi.database.execute('''SELECT `id` FROM `ssscrape_feed_item_option` WHERE `feed_item_id` = %s''', (self['id'],))
        self.options = {}
        for [id] in cursor.fetchall():
            option = ssscrapeapi.feeds.FeedItemOption()
            option.load(id)
            self.options[option['option']] = option

        self.options_changed = False

    def save_options(self):
        '''
        Save named options for this feed item.
        '''

        if not self.options_changed:
            return

        for option in self.options.keys():
            self.options[option].save()
