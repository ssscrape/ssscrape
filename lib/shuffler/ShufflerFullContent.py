__doc__ = '''Twones specific content saver plugin'''

import sys
import os
import re
import urllib
import urlparse
import cgi
import httplib
import urllib2
from xml.dom import minidom

import ssscrapeapi

import shuffler

import feedworker
import feedworker.urn
import beanstalkc
import anyjson

def scheduleTrack(track, job):
    job['type'] = ssscrapeapi.config.get_string('id3', 'default-type', 'id3')
    job['program'] = ssscrapeapi.config.get_string('id3', 'default-program', 'MetadataFetcher.py')
    job['args'] = "-t %s" % (track['id'])
    # Set resource id based on the URL of the permalink    
    resource = ssscrapeapi.Resource()
    resource['name'] = ssscrapeapi.misc.url2resource(track['location'])
    resource_id = resource.find()
    if resource_id <= 0:
        resource.save()
    job['resource_id'] = resource['id']
    print "Looking for scheduled job:"
    print job
    id = job.find()
    if id <= 0:
        job['scheduled'] = 'NOW()'
        job.unescaped = ['scheduled']
        job.save()

class ShufflerPermalinkParser(feedworker.PermalinkScraper):
    def get_base_url(self):
      head = self.soup.find('head')
      if not head:
          return self.service_url
      base = head.find('base')
      if base and base.has_key('href'):
          return base['href']
      else:
          return self.service_url

    def find_mp3_players(self):
      mp3_players = {}
      links = self.soup.findAll('a', href=re.compile('\.mp3$'))
      base_url = self.get_base_url()
      for link in links:
          anchor_text = ''.join(link.findAll(text=True))
          link = urlparse.urljoin(base_url, urllib.unquote_plus(link['href']))
          mp3_players[link] = anchor_text
      return mp3_players

    def find_tumblr_players(self):
      tumblr_players = {}
      print "Finding tumblr players ..."
      players = self.soup.findAll('span', {'id': re.compile('^audio_player_(\d+)$')})
      for player in players:
          audio_player_id = re.search('_(\d+)$', player['id']).group(1)
          # audio_file=http://www.tumblr.com/audio_file/438796839/tumblr_kz2bqjoM6y1qa1h3s
          audio_match = re.search('audio_file=http\:\/\/www\.tumblr\.com\/audio_file\/' + audio_player_id + '\/([^&]+)', self.contents)
          if audio_match:
              audio_player_url = 'http://www.tumblr.com/audio_file/' + audio_player_id + '/' + audio_match.group(1)
              audio_player_url = audio_player_url + '?plead=please-dont-download-this-or-our-lawyers-wont-let-us-host-audio';
              print audio_player_url
              tumblr_players[audio_player_url] = u''
      return tumblr_players

    def find_youtube_players(self):
        youtube_players = {}
        print "Finding youtube players ..."
        # we have object and embeds
        param_players = self.soup.findAll('param', {'name':'src', 'value' : re.compile('youtube\.com\/v')})
        # print param_players
        for player in param_players:
            youtube_players[player['value']] = u''
        players = self.soup.findAll('embed', src=re.compile('youtube\.com\/v'))
        for player in players:
            youtube_players[player['src']] = u''
        # print players
        return youtube_players

    def fetch_soundcloud_permalink(self, permalink):
        consumer_key = ssscrapeapi.config.get_string('soundcloud', 'consumer-key', '')
        resolve_url = 'http://api.soundcloud.com/resolve?' + urllib.urlencode({'url': permalink, 'consumer_key': consumer_key})
        print resolve_url
        contents = None
        http_status = None
        http_url = None
        try:
            url = ssscrapeapi.misc.quote_url(resolve_url)
            opener = urllib2.build_opener(feedworker.CommonPlugins.SmartRedirectHandler())
            req = urllib2.Request(url)
            f = opener.open(req) 
            # FIXME: doesn't this need a while loop? is read() guarantueed to read all data?
            contents = f.read()
            try:
                http_status = f.status # HTTP status
            except AttributeError:
                http_status = f.code
            http_url = f.geturl() # may be a redirect
        except httplib.BadStatusLine, e:
            return
        return (contents, http_status, http_url)

    def find_soundcloud_players(self):
        soundcloud_players = {}
        print "Finding soundcloud players ..."
        # <object height="225" width="100%">
        #  <param name="movie" value="http://player.soundcloud.com/player.swf?url=http%3A%2F%2Fsoundcloud.com%2Fnathantayloronline%2Fsets%2F5eya-as-its-loud-ep&show_comments=true&show_playcount=true&show_artwork=true&color=000000"></param>
        #  <param name="allowscriptaccess" value="always"></param>
        #  <embed allowscriptaccess="always" height="225" src="http://player.soundcloud.com/player.swf?url=http%3A%2F%2Fsoundcloud.com%2Fnathantayloronline%2Fsets%2F5eya-as-its-loud-ep&show_comments=true&show_playcount=true&show_artwork=true&color=000000" type="application/x-shockwave-flash" width="100%"></embed>
        # </object>
        # <span><a href="http://soundcloud.com/nathantayloronline/sets/5eya-as-its-loud-ep">5EYA - 'As Its Loud EP'</a> by <a href="http://soundcloud.com/nathantayloronline">nathantayloronline</a></span>
        players = self.soup.findAll('embed', src=re.compile('player.soundcloud.com\/'))
        for player in players:
            player_url = urlparse.urlparse(player['src'])
            if player_url:
                player_permalink = cgi.parse_qs(player_url.query)['url'][0]
                player_info = self.fetch_soundcloud_permalink(player_permalink)
                player_dom = minidom.parseString(player_info[0])
                if player_dom:
                    if player_dom.documentElement.tagName == 'playlist':
                        tracks = player_dom.getElementsByTagName('track')
                        for track in tracks:
                            track_url, track_title = self.parse_soundcloud_track(track)
                            #print track_url, track_title
                            if track_url:
                                soundcloud_players[track_url] = track_title
                    else:
                        track_url, track_title = self.parse_soundcloud_track(player_dom.documentElement)
                        if track_url:
                            soundcloud_players[track_url] = track_title
                #print player_dom.documentElement.tagName
            #soundcloud_players[player['src']] = u'' 
        return soundcloud_players
    
    def getText(self, nodelist):
        rc = []
        for node in nodelist:
            if node.nodeType == node.TEXT_NODE:
                rc.append(node.data)
        return ''.join(rc)
    
    def parse_soundcloud_track(self, track):
        streamUrl = None
        streams = track.getElementsByTagName('stream-url')
        consumer_key = ssscrapeapi.config.get_string('soundcloud', 'consumer-key', '')
        for stream in streams:
            streamUrl = self.getText(stream.childNodes) + '?' + urllib.urlencode({'consumer_key': consumer_key})
        title = None
        titles = track.getElementsByTagName('title')
        for track_title in titles:
            title = self.getText(track_title.childNodes)
        return (streamUrl, title)
    
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
      self.service_url = service_url
  
      # find all links that end in .mp3
      players = self.find_mp3_players()
      players.update(self.find_tumblr_players())
      players.update(self.find_youtube_players())
      players.update(self.find_soundcloud_players())
      for link in players.keys():
          anchor_text = players[link]
          print link, self.feedUrl, service_url, str(item['pub_date'])
          track = shuffler.Track(feed_item_id=item['id'], location=link)
          track_id = track.find()
          print track_id
          if track_id > 0:
              continue # assume it's saved correctly
          track['posted'] = item['pub_date']
          track['permalink'] = url
          track['site_url'] = service_url
          track['anchor'] = anchor_text
          print >>sys.stderr, track
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
