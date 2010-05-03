
__doc__ = '''MultiMatch specific content saver plugin'''

import feedworker
import feedworker.urn

class MMFullContentPlugin(feedworker.FullContent.FullContentPlugin):
    def _hasFeed(self, id):
        '''Checks if the given feed id has a MultiMatch URN.

        Checks if the given feed has a MultiMatch URN or not. Returns true if so, false if not.'''

        self.transaction.execute("""SELECT COUNT(feed_id) FROM multimatch_feed_urn WHERE feed_id = %s""", (id,))
        x = self.transaction.fetchone()
        return (int(x[0]) > 0)

    def _hasFeedItem(self, id):
        '''Checks if the given feed item has a MultiMatch URN.

        Checks if the given feed item has a MultiMatch URN or not. Returns true if so, false if not.'''

        self.transaction.execute("""SELECT COUNT(feed_item_id) FROM multimatch_feed_item_urn WHERE feed_item_id = %s""", (id,))
        x = self.transaction.fetchone()
        return (int(x[0]) > 0)

    def _hasEnclosure(self, id):
        '''Checks if the given enclosure has a MultiMatch URN.

        Checks if the given enclosure has a MultiMatch URN or not. Returns true if so, false if not.'''

        self.transaction.execute("""SELECT COUNT(enclosure_id) FROM multimatch_enclosure_urn WHERE enclosure_id = %s""", (id,))
        x = self.transaction.fetchone()
        return (int(x[0]) > 0)

    def _saveEnclosure(self, transaction, collection, item, enclosure):
        '''Saves an enclosure into the database.

        Saves an enclosure, that is attached to a feed item, into the database. Generates MultiMatch URNs if they are not already generated.'''

        # call the method of the duper class 
        feedworker.FullContent.FullContentPlugin._saveEnclosure(self, transaction, collection, item, enclosure)

        # if the enclosure is saved successfully and if it has no enclosures already
        if enclosure.has_key("id") and not self._hasEnclosure(enclosure['id']):
            # generate some MM urns
            urn = feedworker.urn.generate_cached_urn('text/xml')
            wrapper_urn = feedworker.urn.generate_cached_urn('text/xml')
            transcription_urn = feedworker.urn.generate_cached_urn('text/xml')
            transaction.execute("""INSERT INTO multimatch_enclosure_urn (enclosure_id, urn, wrapper_urn, transcription_urn) VALUES(%s, %s, %s, %s)""", (enclosure['id'], urn, wrapper_urn, transcription_urn))

    def store(self, collection, item):
        '''Stores a single item into the database.

        This routine stores a single item into the database.'''

        # call the method of our super class 
        feedworker.FullContent.FullContentPlugin.store(self, collection, item)

        # if the item has an id (Ie. it is stored successfully in the DB.) and
        # if a URN for this item has not been generated before then generate it
        if item.has_key("id") and not self._hasFeedItem(item['id']):
            urn = feedworker.urn.generate_cached_urn('text/xml')
            self.transaction.execute("""INSERT INTO `multimatch_feed_item_urn` (feed_item_id, urn) VALUES(%s, %s)""", (item['id'], urn))

    def storefeed(self, collection):
        '''Stores a feed (excl. the items) in the database.

        This routine stores the information for a feed into the database.'''

        feedworker.FullContent.FullContentPlugin.storefeed(self, collection)

        # if the feed was saved successfully and if we did not generate a MM
        # URN for this feed yet then generate one
        if collection.has_key("id") and not self._hasFeed(collection['id']):
            urn = feedworker.urn.generate_cached_urn('text/xml')
            self.transaction.execute("""INSERT INTO multimatch_feed_urn (feed_id, urn) VALUES(%s, %s)""", (collection['id'], urn))
