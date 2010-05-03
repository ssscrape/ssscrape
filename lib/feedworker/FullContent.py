#!/usr/bin/env python

__doc__ = '''Full content parser for the collection worker for scrape.'''

import os, sys, re

import ssscrapeapi
import feedworker


class FullContentError(Exception):
    '''A Generic exception for the Full Content parser.'''
    pass


def makeGeoDict(latitude, longitude):
    '''Return a hash of the given latitude and longitude parameters.'''
    d = {
        'latitude':  latitude,
        'longitude': longitude,
    }
    return d


def parseGeoRssPoint(point_as_str):
    '''
    Parse a GeoRSS point. Simply splits on a space.

    Returns a dictionary, using C{makeGeoDict}.
    '''

    [lat, lng] = point_as_str.split(' ', 1)
    return makeGeoDict(lat, lng)


def parseNPT(nptStr):
    '''
    Parse a string containing an NPT (Near Point Time?)

    Returns a point in time as a float.
    '''

    if not nptStr: return

    nptParts = nptStr.split('-')

    # Just take the first non-empty part for now...
    while nptParts[0] == '':
        del nptParts[0]

    if nptParts[0] == 'now':
        return 0.0 # Huh?

    try:
        i = nptParts[0].index(':')
        [h,m,s] = nptParts[0].split(':', 2)
        try:
            fs = float(s)
        except ValueError:
            fs = 0.0

        return ((int(h) * 3600) + (int(m) * 60) + fs)
    except StandardError:
        try:
            x = float(nptParts[0])
        except StandardError:
            x = 0.0

        return x  

    return 0.0


class FullContentPlugin(feedworker.CommonPlugins.FeedPlugin):
    def _getEnclosureCaption(self, feed, entry, caption):
        enclosure_caption = self.instantiate('enclosure_caption')
        # first get some sane values
        enclosure_caption['caption'] = caption.get('label', None)
        enclosure_caption['type'] = caption.get('type', None)
        enclosure_caption['language'] = caption.get('lang', None)
        enclosure_caption['time_start'] = parseNPT(caption.get('start', ''))
        enclosure_caption['time_end'] = parseNPT(caption.get('end', ''))
        enclosure_caption['format'] = 'native' #Huh?
        enclosure_caption['link'] = caption.get('url', None)
        return enclosure_caption
    # end def _getEnclosureCaption

    def _getEnclosureCategory(self, feed, entry, category):
        enclosure_category = self.instantiate('category')
        enclosure_category['term'] = category.get('term', None)
        enclosure_category['scheme'] = category.get('scheme', None)
        enclosure_category['label'] = category.get('label', None)
        try:
            type = tag['type'][0]
        except StandardError:
            type = 'c'
        # end try
        enclosure_category['type'] = type
        return enclosure_category
    # end def _getEnclosureCategory

    def _getEnclosureCredits(self, feed, item, credits):
        enclosure_credits = self.instantiate('enclosure_credits')
        enclosure_credits['role'] = credits.get('role', None)
        enclosure_credits['scheme'] = credits.get('scheme', 'urn:ebu')
        enclosure_credits['credit'] = credits.get('who', None)
        return enclosure_credits
    # end def _saveEnclosureCredits

    def _getEnclosureRating(self, feed, entry, rating):
        enclosure_rating = self.instantiate('rating')
        enclosure_rating['scheme'] = rating.get('scheme', 'urn:simple')
        enclosure_rating['value'] = rating.get('rating', None)
        return enclosure_rating
    # end def _getEnclosureRating

    def _getEnclosureRestriction(self, feed, entry, restriction):
        enclosure_restriction = self.instantiate('enclosure_restriction')
        enclosure_restriction['type'] = restriction.get('type', None)
        enclosure_restriction['relationship'] = restriction.get('relationship', None)
        enclosure_restriction['restriction'] = restrction.get('value', '')
        return enclosure_restriction
    # end def _getEnclosureRestriction

    def _getEnclosureThumbnail(self, feed, entry, thumbnail):
        enclosure_thumbnail = self.instantiate('enclosure_thumbnail')
        enclosure_thumbnail['url'] = thumbnail.get('url', None)
        enclosure_thumbnail['height'] = thumbnail.get('height', None)
        enclosure_thumbnail['width'] = thumbnail.get('width', None)
        enclosure_thumbnail['time'] = parseNPT(thumbnail.get('time', ''))
        return enclosure_thumbnail
    # end def _getEnclosureThumbnail

    def _getEntryAuthors(self, entry):
        '''Get the author(s) of an entry.

        Parses the details of an author, given an entry. Tries to find name, link and e-mail values.
        Returns a hash if the entry has an author, else returns an empty hash.'''

        # field conversion for DB
        feed2db_fields = {
            'email': 'email',
            'name': 'fullname',
            'href': 'link',
        }

        author = None

        if entry.has_key('author'):
            author = self.instantiate('author')
            for what in ['name', 'href', 'email']:
                try:
                    author[feed2db_fields[what]] = entry.author_detail[what]
                except StandardError:
                    pass 
                # end try
            # end for
        # end if
        return author
    # end def _getEntryAuthors

    def _getEntryCategories(self, entry):
        '''Parse category information for feed items.

        Given a feed item, this routine parses the tags of the entry, and tries to find scheme and label
        values, combined with the term.'''

        categories = []
        if entry.has_key('tags'):
            for tag in entry.tags:
                tagInfo = self.instantiate('category', type='c') # create a category 
                for what in ['term', 'scheme', 'label']:
                    try:
                        tagInfo[what] = tag[what]
                    except KeyError:
                        pass
                # end for
                categories.append(tagInfo)
            # end for
        # end if
        return categories
    # end def _getEntryCategories

    def _getEntryEvents(self, feed, entry):
        '''Get the events mentioned in an entry.

        Currently does nothing useful. Returns an empty array.'''

        events = []
        return events
    # end def _getEntryEvents

    def _getEntryLatLng(self, feed, entry):
        '''Retrieve the geographical information for an entry.

        This routine gets the geographical information for an entry in a feed. This information
        can come in two different formats. It handles thhem both and parses them. Returns
        a dictionary with the keys for longitude and latitude.'''

        # See also _getFeedLatLng(), -- this works in the exact same way
        latlng = {}
        if 'georss' in feed.namespaces and 'point' in entry:
            latlng = parseGeoRssPoint(entry.point)
        elif 'geo' in feed.namespaces and 'geo_lat' in entry and 'geo_long' in entry:
            latlng = makeGeoDict(entry['geo_lat'], entry['geo_long'])

        return self.instantiate('geo', **latlng)

    # end def _getEntryLatLng

    def _getEntryLinks(self, feed, entry):
        '''Gets links in a feed entry.

        Feed entries can have links (not mentioned in the description field), to point to
        various places. Often, this means a link to a certain alternate version of this
        item, but there are other possibilities as well. This routine gets those links,
        and returns them as a hash.'''

        # feed 2 DB table
        feed2db_fields = {
            'rel': 'relation',
            'href': 'link',
            'type': 'type',
            'title': 'title'
        }

        links = {} 
        linkInfo = self.instantiate('feed_item_link')
        # if an entry has a link (Ie. <link> field in RSS 2.0, then we make a
        # default link, the permalink, that is considered an alternate representation
        # of the item.
        if entry.has_key('link'):
            linkInfo['relation'] = 'alternate' # alternate is the default for feeds
            linkInfo['type'] = 'text/html'
            linkInfo['link'] = entry.link 
            if entry.has_key('title'):
                linkInfo['title'] = entry.title
            else:
                linkInfo['title'] = ''
            # end if
            links[linkInfo['link'] + ':alternate'] = linkInfo
        # end if
        if 'feedburner' in feed.namespaces:
            #    linkInfo['link'] = entry.feedburner_origlink # TODO: 
            if not entry.has_key('links'):
                entry['links'] = []
            entry['links'].append({
              'rel': 'feedburner_origlink',
              'type': 'text/html',
              'href': entry.feedburner_origlink,
              'title': 'Feedburner original link'
            })
        # if it has additional links, then add them
        if entry.has_key('links'):
            for link in entry.links:
                if not link.has_key('href'):
                    continue
                # end if
                linkInfo = self.instantiate('feed_item_link')
                for what in ['rel', 'type', 'href', 'title']:
                    try:
                        linkInfo[feed2db_fields[what]] = link[what]
                    except StandardError:
                        pass 
                    # end try
                # end for
                links[link['href'] + ':' + link['rel']] = linkInfo
            # end for
        # end if
        return links
    # end def _getEntryLinks

    def _getEntryMediaEnclosures(self, feed, entry):
        '''Get the enclosures of a feed entry.

        This ro utine gets the enclosures of a feed entry and returns them as an array.'''

        enclosures = []
        if entry.has_key('media_content'):
            for content in entry['media_content']:
                #print >>sys.stderr, content
                enclosure = self.instantiate('enclosure')

                # first get some simple values to store
                try:
                    enclosure['link'] = content['url']
                except KeyError:
                    pass 
                # end try
                try:
                    enclosure['filesize'] = int(content['filesize'])
                except KeyError:
                    pass 
                # end try
                try:
                    enclosure['mime'] = content['type']
                except KeyError:
                    pass 
                # end try
                try:
                    enclosure['medium'] = content['medium']
                except KeyError:
                    pass 
                # end try
                try:
                    enclosure['bit_rate'] = int(content['bitrate'])
                except KeyError:
                    pass 
                # end try
                try:
                    enclosure['frame_rate'] = int(content['framerate'])
                except KeyError:
                    pass 
                # end try
                try:
                    enclosure['sampling_rate'] = float(content['samplingrate'])
                except KeyError:
                    pass 
                # end try
                try:
                    enclosure['audio_channels'] = int(content['channels'])
                except KeyError:
                    pass 
                # end try
                try:
                    enclosure['expression'] = content['expression']
                except KeyError:
                    pass 
                # end try
                try:
                    enclosure['duration'] = int(content['duration'])
                except KeyError:
                    pass 
                # end try
                try:
                    enclosure['width'] = int(content['width'])
                except KeyError:
                    pass 
                # end try
                try:
                    enclosure['height'] = int(content['height'])
                except KeyError:
                    pass
                # end try
                try:
                    enclosure['language'] = content['lang']
                except KeyError:
                    pass 
                # end try
                # use media:title
                try:
                    enclosure['title'] = content['media_title']
                except KeyError:
                    pass 
                #  end try
                #  use media:description
                try:
                    enclosure['description'] = content['media_description']
                except KeyError:
                    pass 
                # end try


                if content.has_key('media_copyright'):
                    enclosure['copyright_url'] = content['media_copyright'].get('url', '')
                    enclosure['copyright_attribution'] = content['media_copyright'].get('label', '')
                # end if


                if content.has_key('media_text'):
                    enclosure['captions'] = []
                    for caption in content['media_text']:
                        enclosure['captions'].append(self._getEnclosureCaption(feed, entry, caption))

                if content.has_key('media_credit'):
                    enclosure['credits'] = []
                    for credit in content['media_credit']:
                        enclosure['credits'].append(self._getEnclosureCredits(feed, entry, credit))

                if content.has_key('tags'):
                    enclosure['categories'] = []
                    for category in content['tags']:
                        enclosure['categories'].append(self._getEnclosureCategory(feed, entry, category))
                    # end for
                # end if

                if content.has_key('media_rating'):
                    enclosure['rating'] = self._getEnclosureRating(feed, entry, content['media_rating'])
                # end if

                if content.has_key('media_restriction'):
                    enclosure['restrictions'] = []
                    for restriction in content['media_restriction']:
                        enclosure['restrictions'].append(self._getEnclosureRestriction(feed, entry, restriction))
                    # end for
                # end if

                if content.has_key('media_thumbnail'):
                    enclosure['thumbnail'] = self._getEnclosureThumbnail(feed, entry, content['media_thumbnail'])
                # end if

                enclosures.append(enclosure)
            # end for
        # end if

        if entry.has_key('enclosures'):
            for simple_enclosure in entry.enclosures:
                enclosure = self.instantiate('enclosure')
                enclosure['link'] = simple_enclosure.get('href', '')
                enclosure['filesize'] = simple_enclosure.get('length', '')
                enclosure['mime'] = simple_enclosure.get('type', '')
                enclosures.append(enclosure) 
            # end for
        # end if

        return enclosures
    # end def _getEntryMediaEnclosures

    def _getFeedAuthors(self, feed):
        '''Get the author(s) of a feed at the feed level.

        This routine gets the author(s) of a feed and returns them as an array. If the
        feed has no author, it returns an empty array.'''

        # field conversion for DB
        feed2db_fields = {
            'email': 'email',
            'name': 'fullname',
            'href': 'link',
        }

        author = None

        if feed.feed.has_key('author'):
            author = self.instantiate('author')
            for what in ['name', 'href', 'email']:
                try:
                    author[feed2db_fields[what]] = feed.feed.author_detail[what]
                except StandardError:
                    pass 
                # end try
            # end for
        # end if
        return author
    # end def _getFeedAuthors

    def _getFeedImage(self, feed):
        '''Get the representative image of a feed.

        This parses and stores the <image> element in RSS and returns it as a dictionary. If the element is not in the feed,
        then it returns a dictionary with empty values for the keys.'''

        image = self.instantiate('feed_image') 
        if feed.feed.has_key('image'):
            for elem in ['title', 'href', 'url', 'link', 'width', 'height', 'description']:
                obj_elem = elem
                if elem == 'href':
                    obj_elem = 'url' # field rwriting
                try:
                    image[obj_elem] = feed.feed.image[elem]
                except StandardError:
                    pass 
                # end try
            # end for
        # end if
        return image
    # end def _getFeedImage

    def _getFeedLatLng(self, feed):
        '''Gets the longitude and latitude at the feed level.

        Gets the longitude and latitude values of a feed as a dictionary. Returns an empty
        one if there is no Geo RSS information.'''

        # See also _getEntryLatLng(), -- this works in the exact same way
        latlng = {}
        if 'georss' in feed.namespaces and 'point' in feed.feed:
            latlng = parseGeoRssPoint(feed.feed.point)
        elif 'geo' in feed.namespaces and 'geo_lat' in feed.feed and 'geo_long' in feed.feed:
            latlng = makeGeoDict(feed.feed['geo_lat'], feed.feed['geo_long'])

        return self.instantiate('geo', **latlng)

    # end def _getFeedLatLng

    def _getFeedLinks(self, feed):
        '''Gets the links at the feed level.

        Get the links of a feed. These are often alternate versions. In any case, an entry has
        at least one link: it's permalink, which is an alternate representation of the feed
        (Often in HTML). Returns a dictionary of links.'''

        # feed 2 DB table
        feed2db_fields = {
            'rel': 'relation',
            'href': 'link',
            'type': 'type',
            'title': 'title'
        }

        links = {} 
        linkInfo = self.instantiate('feed_link') 
        if feed.feed.has_key('link'):
            # every feed has at least one link: the link that the <link> element points to
            # that link is considered an alternate representation of the feed, in HTML.
            linkInfo['relation'] = 'alternate' # alternate is the default for feeds
            linkInfo['type'] = 'text/html'
            linkInfo['link'] = feed.feed.link 
            if feed.feed.has_key('title'):
                linkInfo['title'] = feed.feed.title
            else:
                linkInfo['title'] = '' 
            # end if
            links[feed.feed.link + ':alternate'] = linkInfo
        # if there are any ohter links present, add them
        if feed.feed.has_key('links'):
            for link in feed.feed.links:
                if not link.has_key('href'):
                    continue
                # end if
                linkInfo = self.instantiate('feed_link')
                for what in ['rel', 'type', 'href', 'title']:
                    try:
                        linkInfo[feed2db_fields[what]] = link[what]
                    except StandardError:
                        pass 
                    # end try
                # end for
                links[link['href'] + ':' + link['rel']] = linkInfo
            # end for
        # end if
        return links
    # end def _getFeedLinks

    def _parseEntry(self, feed, entry):
        '''Parse an entry, and returns an item for the entry.

        This routine parses an entry (result from parsing with feedparser), and returns an item dictionary.'''

        item = feedworker.CommonPlugins.FeedPlugin._parseEntry(self, feed, entry)

        #print >>sys.stderr, "Parsing item %s ..." % (item['guid'])

        # first get some basic and simple information
        try:
            item['pub_date'] = entry.updated_parsed
        except AttributeError:
            item['pub_date'] = 'NOW()' 
        # end try
        try:
            item['guid'] = entry.guid
        except AttributeError:
            item['guid'] = None 
        if not item['guid']:
            try:
                item['guid'] = entry.link
            except AttributeError:
                pass
        # end try
        try:
            item['summary'] = entry.summary
        except AttributeError:
            item['summary'] = None 
        if not item['summary']:
            try:
                item['summary' ] = entry.description
            except AttributeError:
                pass
        # end try
        try:
            item['content'] = entry.content.value
        except AttributeError:
            item['content'] = None 
        if not item['content']:
            try:
                item['content'] = entry.description
            except AttributeError:
                pass
        # Avoid saving empty content
        if not item['content']:
            item['content'] = None
        # end try
        try:
            item['copyright'] = entry.license
        except AttributeError:
            pass 
        # end try
        try:
            item['comments_url'] = entry.comments
        except AttributeError:
            pass 
        # end try
        try:
            item['enclosures'] = entry.enclosures
        except AttributeError:
            item['enclosures'] = []
        # fix for stupid feedburner shit
        if entry.has_key('feedburner_origenclosurelink'):
            for enclosure in item['enclosures']:
                #print enclosure['href']
                if re.search(r'feedproxy\.google\.com', enclosure['href']):
                    #print "-->", entry['feedburner_origenclosurelink']
                    enclosure['href'] = entry['feedburner_origenclosurelink']
        
        # end try
        # language (tricky, rss feeds don't have a way to specify language in *items*)
        # Also, not sure if this is the correct place to do it
        try:
            item['language'] = feed.feed.language
        except AttributeError:
            item['language'] = None

        # now move on to the more complex structures that can be present in the item
        #print >>sys.stderr, "* Parsing item authors ..."
        item['author'] = self._getEntryAuthors(entry)
        #print >>sys.stderr, "* Parsing item geo information ..."
        item['geo'] = self._getEntryLatLng(feed, entry)
        #print >>sys.stderr, "* Parsing item categories ..."
        item['categories'] = self._getEntryCategories(entry)
        #print >>sys.stderr, "* Parsing item events ..."
        item['events'] = self._getEntryEvents(feed, entry)
        #print >>sys.stderr, "* Parsing item enclosures ..."
        item['media'] = self._getEntryMediaEnclosures(feed, entry)
        #print >>sys.stderr, "* Parsing item links ..."
        item['links'] = self._getEntryLinks(feed, entry)

        # return the item
        return item
    # end def _parseEntry

    def _parseFeed(self, feed):
        '''See FeedPlugin._parseFeed.

        This method overrides the one from the base class. In addition to just
        parsing the feed, it also parses the fields into the right structure.
        Returns a dictionary.'''

        collection = feedworker.CommonPlugins.FeedPlugin._parseFeed(self, feed)
        # get some common data first
        try: 
            collection['description'] = feed.feed.description
        except AttributeError:
            collection['description'] = ''
        # end try
        try: 
            collection['type'] = feed.version
        except AttributeError:
            collection['type'] = ''
        # end try
        try: 
            collection['encoding'] = feed.encoding
        except AttributeError:
            collection['encoding'] = 'utf-8'
        # end try
        try: 
            collection['lastmod'] = feed.headers['last-modified']
        except AttributeError:
            collection['lastmod'] = ''
        # end try
        try:
            collection['etag'] = feed.headers['etag']
        except AttributeError:
            collection['etag'] = ''
        # end try
        try:
            collection['language'] = feed.feed.language
        except AttributeError:
            collection['language'] = None # default language is null 
        # end try

        # For copyright, use both machine readable and human string
        rights = []
        try:
            rights.append(feed.feed.rights)
        except AttributeError:
            pass
        try:
            rights.append(feed.feed.license)
        except AttributeError:
            pass
        collection['copyright'] = ' - '.join(rights);
            
        # end try
        try:
            collection['favicon'] = feed.feed.icon
        except AttributeError:
            collection['favicon'] = ''
        # end try

        # now move on to more complex data structures

        # get links
        collection['links'] = self._getFeedLinks(feed)
        # get author
        collection['author'] = self._getFeedAuthors(feed)
        # now get the image information
        collection['image'] = self._getFeedImage(feed)
        # and the geo location
        collection['geo'] = self._getFeedLatLng(feed)

        # return the collection
        return collection
    # end def _parseFeed

    def _saveEnclosure(self, transaction, collection, item, enclosure):
        '''Saves an enclosure into the database.

        Saves an enclosure, that is attached to a feed item, into the database.'''

        # try to find out id
        if not enclosure.has_key('id'):
            x = enclosure.retrieve(feed_item_id=item['id'], link=enclosure['link'])
        # end if

        if not enclosure.has_key('pub_date'):
            enclosure['pub_date'] = 'NOW()'

        if not enclosure.has_key('mod_date'):
            enclosure['mod_date'] = 'NOW()'

        # save enclosure
        enclosure.set_item(item)
        enclosure.save()

        # now save the rest of the stuff for enclosures, if id > 0
        if enclosure.has_key('captions'):
            for caption in enclosure['captions']:
                caption.set_enclosure(enclosure)
                caption.save()
            # end for
        # end if

        if enclosure.has_key('credits'):
            for credit in enclosure['credits']:
                 credit.set_enclosure(enclosure)
                 credit.save()
            # end for
        # end if

        if enclosure.has_key('categories'):
            for category in enclosure['categories']:
                category.save()
                enclosure.add(category)
            # end for
        # end if

        if enclosure.has_key('rating'):
            enclosure['rating'].save()
            enclosure.add(enclosure['rating']) 
        # end if

        if enclosure.has_key('restrictions'):
            for restriction in enclosure['restrictions']:
                restriction.set_enclosure(enclosure) 
                restriction.save() 
            # end for
        # end if

        if enclosure.has_key('thumbnail'):
            enclosure['thumbnail'].set_enclosure(enclosure) 
            enclosure['thumbnail'].save()
        # end if

    # end def _saveEnclosure

    def _saveItemEnclosures(self, collection, item):
        '''Save all the information for item enclosures in the database.

        Saves all the enclosures of an item into the database.'''

        if item.has_key('media'): 
            for enclosure in item['media']:
                self._saveEnclosure(self.transaction, collection, item, enclosure)
            # end for
        # end if
    # end def _saveItemEnclosures

    def _calculate_task_periodicity(self, transaction):
        # find out task id
        try:
            task_id = int(os.environ["SSSCRAPE_TASK_ID"])
        except (ValueError, KeyError):
            return

        # check if we need to auto update the periodicity
        task = ssscrapeapi.Task()
        task.load(task_id)
        if task['autoperiodicity'] == 'disabled':
            return

        # update the periodicity
        min_periodicity = ssscrapeapi.misc.parse_time_string_to_seconds(ssscrapeapi.config.get_string('feeds', 'periodicity-minimum', '60s'))
        max_periodicity = ssscrapeapi.misc.parse_time_string_to_seconds(ssscrapeapi.config.get_string('feeds', 'periodicity-maximum', '99h'))
        def_periodicity = ssscrapeapi.misc.parse_time_string_to_seconds(ssscrapeapi.config.get_string('feeds', 'periodicity-default', '1h'))

        self.transaction.execute('''SELECT pub_date FROM ssscrape_feed_item WHERE (feed_id = (SELECT id FROM ssscrape_feed WHERE url = %s)) AND (pub_date >= (NOW() - INTERVAL 7 DAY)) ORDER BY pub_date''', self.feedUrl)
        rows = self.transaction.fetchall()
        row_count = 0
        avg_time = 0.0 # in seconds
        for row in rows:
            # print >>sys.stderr, row, row_count, avg_time
            if row_count > 0:
                delta = row[0] - last_date
                avg_time = (((avg_time * row_count) + delta.seconds) * 1.0) / (row_count + 1)
                pass
            row_count = row_count + 1
            last_date = row[0]

        periodicity = int(avg_time)
        if periodicity == 0:
            periodicity = def_periodicity
        if periodicity < min_periodicity:
            periodicity = min_periodicity
        if periodicity > max_periodicity:
            periodicity = max_periodicity

        task.unescaped = ['periodicity'] # dirty hack
        task['periodicity'] = "SEC_TO_TIME(%s)" % (periodicity)
        task.save() 
        #print >>sys.stderr, "New periodicity for task %s is %s seconds." % (task_id, periodicity)

    def get_permalink_fetching_job(self, feed, item):
         """Return the job that should be used to fetch and further process permalinks"""

         job = ssscrapeapi.Job()
         job['type'] = ssscrapeapi.config.get_string('feeds', 'default-partial-type', 'permalink')
         job['program'] = ssscrapeapi.config.get_string('feeds', 'default-partial-program', 'feedworker.py')
         job['args'] = "-i %s" % (item['id'])
         if self.metadata['partial_args']:
             job['args'] = job['args'] + " " + self.metadata['partial_args']

         # Find URL associated with this item
         url = None 
         if item.has_key('links'):
             for link in item['links'].itervalues():
                 if link.has_key('relation') and link['relation'] == 'alternate' and link.has_key('link'):
                     url = link['link']
                     break

         if url:
             # Set resource id based on the URL of the permalink    
             resource = ssscrapeapi.Resource()
             resource['name'] = ssscrapeapi.misc.url2resource(url)
             resource_id = resource.find()
             if resource_id <= 0:
                 resource.save()
             job['resource_id'] = resource['id']
         else: 
             # Otherwise, use the same resource as the entire feed
             job['resource_id'] = self.job['resource_id'] 

         return job

    def _schedule_permalink(self, feed, item):
        '''
        Schedule a permalink fetching worker for the given item.
        '''

        job = self.get_permalink_fetching_job(feed, item)
        
        id = job.find()
        if id <= 0:
            job['scheduled'] = 'NOW()'
            job.unescaped = ['scheduled']
            job['hostname'] = self.job['hostname'] # assume running conditions are the same
            job.save()

    def close(self):
        '''Unloads the plugin.

        This routines unloads the plugin, and closes the database connection.'''
        pass
    # end def close

    def post_store(self):
        self.message = "Added %s new items and updated %s items." % (self.items_new, self.items_updated)
        #self.job.save() 
    # end def post_store

    def fetch(self):
        '''Fetch the feed URL.

        This routine currently does nothing more that call the super.'''

        feedworker.CommonPlugins.FeedPlugin.fetch(self)

        return self.contents
    # end def fetch

    def fetchclean(self, pageText):
        '''Cleans the page content before it gets parsed.'''

        #return feedworker.CommonPlugins.cleanText(pageText)
        return pageText
    # end def fetchclean

    def filter(self, feed, item):
        '''Filters items from a feed for processing.

        This functions filters certain items from the feed for processing. For example,
        we can enforce that only new items are processed.'''

        #return not self._hasFeedItem(item['guid'])  # for now...
        return True
    # end def filter

    def open(self, argv):
        '''Loads the plugin.

        This function loads the plugin, and initializes the database connection.'''

        feedworker.CommonPlugins.FeedPlugin.open(self, argv) 
        self.items_new = 0
        self.items_updated = 0

    # end def open

    def process(self, feed, item):
        '''Process an item.

        This routine currently does nothing.'''

        pass
    # end def process

    def store(self, collection, item):
        '''Stores a single item into the database.

        This routine stores a single item into the database.'''

        #print >>sys.stderr, "Storing item %s ..." % (item['guid'])
        is_new = not item.has_key('id')

        #print >>sys.stderr, "* Storing item info ..."
        self.save_item_info(collection, item)
        #print >>sys.stderr, "* Storing item author ..."
        self.save_item_author(collection, item)
        #print >>sys.stderr, "* Storing item geo info ..."
        self.save_item_geo_info(collection, item)
        #print >>sys.stderr, "* Storing item link info ..."
        self.save_item_links(collection, item)
        #print >>sys.stderr, "* Storing item category info ..."
        self.save_item_categories(collection, item)
        #print >>sys.stderr, "* Storing item enclosure info ..."
        self._saveItemEnclosures(collection, item)

        # check if we need to refetch updated items or not
        must_refetch = ssscrapeapi.config.get_bool('feeds', 'default-partial-update-refetch', False)
        
        # check if we have a partial content feed
        if self.metadata:
            try:
                if (is_new or must_refetch) and (self.metadata['kind'] == 'partial'):
                    self._schedule_permalink(collection, item)
            except KeyError:
                pass # not a full content feed anyway
        #print >>sys.stderr, "Stored item %s ..." % (item['guid'])
    # end def store

    def storefeed(self, collection):
        '''Stores a feed (excl. the items) in the database.

        This routine stores the information for a feed into the database.'''

        self.save_feed_info(collection)
        self.save_feed_geo_info(collection)
        self.save_feed_links(collection)
        self.save_feed_author(collection)

        # debugging stuff
    # end def storefeed
# end class FullContentPlugin
