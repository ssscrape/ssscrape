__doc__ = '''Twones specific content saver plugin'''

import os
import re

import ssscrapeapi

import feedworker
import feedworker.urn
import beanstalkc
import anyjson

def getBeanstalkInstance(tube='tracks'):
    # print "Initiating beanstalk connection ..."
    configs = {
      'development': {
        'host': 'localhost',
        'port': 11300
      },
      'preproduction': {
        'host': 'localhost',
        'port': 11300
      },
      'production': {
        'host': 'sf01-int.twones.com',
        'port': 11300
      },
    }
    environment = ssscrapeapi.config.get_string('twones', 'environment', 'production') #os.getenv('CAKEPHP_ENV')
    # print environment, configs[environment]['host'], configs[environment]['port']
    beanstalk = beanstalkc.Connection(host=configs[environment]['host'], port=configs[environment]['port'])
    beanstalk.use(tube)
    return beanstalk

def sendScrapedLink(link, url, service_url, anchor_text, created, beanstalk):
    json_obj = anyjson.serialize({
      'link': link,
      'web_link': url,
      'service_url': service_url,
      'anchor_text': anchor_text,
      'created': created
    })
    beanstalk.put(json_obj)

class TwonesPermalinkParser(feedworker.PermalinkScraper):
  def scrape(self, collection):
      # intantiate beanstalk connection
      beanstalk = getBeanstalkInstance()
      
      # load info about feed item
      item = self.instantiate('feed_item')
      item.load(self.feed_item_id)
      
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
          sendScrapedLink(link, self.feedUrl, service_url, anchor_text, str(item['pub_date']), beanstalk)
      
      # close beanstalk connection
      beanstalk.close()
      
      collection['items'] = []

class TwonesFullContentPlugin(feedworker.FullContent.FullContentPlugin):
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
            sendScrapedLink(enclosure['link'], url, service_url, None, item['pub_date'], self.beanstalk)

    def pre_store(self):
        self.beanstalk = getBeanstalkInstance()

    def post_store(self):
        # print "Destroying beanstalk connection ..."
        self.beanstalk.close()
        feedworker.FullContent.FullContentPlugin.post_store(self)
