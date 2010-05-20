__doc__ = '''Twones specific content saver plugin'''

import sys
import os
import re

import ssscrapeapi

import shuffler

import feedworker
import feedworker.urn
import beanstalkc
import anyjson

def scheduleTrack(track, job):
    job['type'] = ssscrapeapi.config.get_string('id3', 'default-type', 'id3')
    job['program'] = ssscrapeapi.config.get_string('id3', 'default-program', 'MP3Fetcher.py')
    job['args'] = "-t %s" % (track['id'])
    # Set resource id based on the URL of the permalink    
    resource = ssscrapeapi.Resource()
    resource['name'] = ssscrapeapi.misc.url2resource(track['location'])
    resource_id = resource.find()
    if resource_id <= 0:
        resource.save()
    job['resource_id'] = resource['id']
    id = job.find()
    if id <= 0:
        job['scheduled'] = 'NOW()'
        job.unescaped = ['scheduled']
        job.save()

class ShufflerPermalinkParser(feedworker.PermalinkScraper):
  def scrape(self, collection):      
      # load info about feed item
      item = self.instantiate('feed_item')
      item.load(self.feed_item_id)
      
      # Find URL associated with this item
      url = self.feedUrl

      # find the feed url
      feed_link = self.instantiate('feed_link', feed_id=item['feed_id'], relation="alternate", type="text/html")
      feed_link.find()
      if feed_link.has_key('id'):
          feed_link.load(feed_link['id'])
          service_url = feed_link['link']
      else:
          service_url = self.feedUrl
          
      # find all links that end in .mp3
      links = self.soup.findAll('a', href=re.compile('\.mp3$'))
      for link in links:
          anchor_text = ''.join(link.findAll(text=True))
          link =  link['href']
          # print link, self.feedUrl, service_url, post_title.encode('ascii', 'ignore'), str(item['pub_date'])
          track = shuffler.Track(feed_item_id=item['id'], location=link)
          track_id = track.find()
          if track_id > 0:
              continue # assume it's saved correctly
          track['posted'] = item['pub_date']
          track['permalink'] = url
          track['site_url'] = service_url
          track['anchor'] = anchor_text
          #print >>sys.stderr, track
          track.save()  
          job = self.instantiate('job')
          scheduleTrack(track, job)
          #sendScrapedLink(link, self.feedUrl, service_url, anchor_text, str(item['pub_date']), beanstalk)
            
      collection['items'] = []

class ShufflerFullContentPlugin(feedworker.FullContent.FullContentPlugin):
    def _hasEnclosure(self, id):
        '''Checks if the given enclosure was sent to Twones.'''

        self.transaction.execute("""SELECT COUNT(enclosure_id) FROM twones_enclosure WHERE enclosure_id = %s""", (id,))
        x = self.transaction.fetchone()
        return (int(x[0]) > 0)

    def _saveEnclosure(self, transaction, collection, item, enclosure):
        '''Saves an enclosure to Twones.'''
        
        # call the method of the duper class 
        feedworker.FullContent.FullContentPlugin._saveEnclosure(self, transaction, collection, item, enclosure)

        # check for mp3 links
        if not re.search('\.mp3$', enclosure['link']):
            return
        
        # if the enclosure is saved successfully and if it has no enclosures already
        if enclosure.has_key("id") and not self._hasEnclosure(enclosure['id']):
            transaction.execute("""INSERT INTO twones_enclosure (enclosure_id, sent) VALUES(%s, NOW())""", (enclosure['id'], ))
            # print enclosure['link']
            if item.has_key('title'):
                item_title = item['title']
            else:
                item_title = None
            # Find URL associated with this item
            url = None 
            if item.has_key('links'):
                for relation in ['feedburner_origlink', 'alternate']:
                    if url is not None:
                        break
                    for link in item['links'].itervalues():
                        #print link
                        if link.has_key('relation') and link['relation'] == relation and link.has_key('link'):
                            url = link['link']
                            break
            # print url            
            # Find URL associated with this item
            service_url = None 
            if collection.has_key('links'):
                for link in collection['links'].itervalues():
                    if link.has_key('relation') and link['relation'] == 'alternate' and link.has_key('link'):
                        service_url = link['link']
                        break
            print >>sys.stderr, "Creating track object for %s ..." % (enclosure['link'])
            track = shuffler.Track(feed_item_id=item['id'], location=enclosure['link'])
            track_id = track.find()
            if track_id > 0:
                return # assume it's saved correctly
            track['posted'] = item['pub_date']
            track['permalink'] = url
            track['site_url'] = service_url
            #print >>sys.stderr, track
            track.save()  
            job = self.instantiate('job')
            scheduleTrack(track, job)
            #sendScrapedLink(enclosure['link'], url, service_url, None, item['pub_date'], self.beanstalk)

    def pre_store(self):
        #self.beanstalk = getBeanstalkInstance()
        pass
    
    def post_store(self):
        # print "Destroying beanstalk connection ..."
        #self.beanstalk.close()
        feedworker.FullContent.FullContentPlugin.post_store(self)
