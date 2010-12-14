#!/usr/bin/env python
"""Basic runner class for the pubsubhubbub client."""

import sys
import os
import re
import ConfigParser
import traceback
import urllib
import urllib2

import feedparserx
from BeautifulSoup import BeautifulSoup

import ssscrapeapi 
import ssscrapeapi.feeds as feeds
import pubsubhubbub 

class WorkerUsage(Exception):
    def __init__(self, msg):
        self.msg = msg

class WorkerException(Exception):
    def __init__(self, exit_code, keyword = '', msg = ''):
        self.exit_code = exit_code
        self.keyword = keyword
        self.msg = msg

    def __str__(self):
        return "%s: %s %s " % (repr(self.exit_code), self.keyword, self.msg)


class WorkerRunner:
    def __init__(self, feed_id, action):

        self.feed_id = feed_id
        self.action = action

        self.base_pub_url = 'http://http://dev.ssscrape-api.shuffler.fm/'

        # connect to databases
        ssscrapeapi.database.connect()
        ssscrapeapi.database.connect('database')
       
        # load the associated job 
        job_id = int(os.environ["SSSCRAPE_JOB_ID"])
        self.job = ssscrapeapi.Job()
        self.job.load(job_id) 

    # actions

    ''' subscribe '''
    def sub(self, feed):
        return self._sub(feed, 'subscribe')

    ''' unsubscribe '''
    def unsub(self, feed):
        return self._sub(feed, 'unsubscribe')


    def _sub(self, feed, mode):
        # Send an POST request to http://tumblr.superfeedr.com, with the following params :
        #   hub.mode : subscribe or unsubscribe
        #   hub.verify : sync or async
        #   hub.callback : http://domain.tld/your/callback
        #   hub.topic : http//feed.you.want.to/subscribe/to
        if feed['hub']:
            data = urllib.urlencode({
                'hub.mode' : mode,
                'hub.verify' : 'async',
                'hub.callback' : self.base_pub_url + '/feeds/pub/' + str(feed['id']),
                'hub.topic' : feed['url']
            })
            req = urllib2.Request(feed['hub'], data)
            req.timeout = 10 # GAE is picky about this and will throw "DownloadError 5" (timeout) for everything
            try:
                f = urllib2.urlopen(req)
                raise WorkerException(1, keyword = 'hub', msg = 'Should raise 204 or 202 urllib2.HTTPError. 200 is not ok')
            except urllib2.HTTPError, error:
                if (error.code != 204) and (error.code != 202):
                    msg = 'Error contacting hub. Http response status: ' + str(error.code)
                    raise WorkerException(1, keyword = 'hub', msg = msg)
                #else:
                    # 204: No Content and 202: Accepted
                    # 204 means that we are subscribed. 202 means its going to be done later
        else:
            msg = "This feed has no assigned hub. Can't " + mode
            raise WorkerException(2, keyword = 'hub', msg = msg)
        # everything ok
        return 0

    def discoverhub(self, feed):
        ''' tries to find a hub for the feed. loops through alternates or try common feed urls '''
        
        # first try to get from the current feed
        hub_url = self.gethub(feed['url'])
        if hub_url:
            f = feeds.Feed()
            f['id'] = feed['id']
            f['hub'] = hub_url
            f.save()
            return 0
            
        # try to find a different feed that has it

        # ask for the html and look for an alternate feed
        html_link = None
        feed_links = ssscrapeapi.database.execute("SELECT * FROM ssscrape_feed_link WHERE feed_id = %s", (feed['id']))
        for link in feed_links:
            if link[4] == 'text/html': # type
                html_link = link[3] # link

        # lets find a feed that has a hub
        feed_url = hub_url = None
        
        if html_link:
            print(html_link)
            req = urllib2.Request(html_link)
            html = urllib2.urlopen(req).read()
            soup = BeautifulSoup(html)
            alternates = soup.findAll('link', attrs={'rel':'alternate'})
            for link in alternates:
                feed_url = link['href'] 
                if re.search(r'comments', link['href']):
                    continue # remove feeds with "comments" on it

                hub_url = self.gethub(feed_url)
                if hub_url:
                    break

        if hub_url == None:
            guesses = []
            # try to guess feed urls
            if re.search(r'blogspot.com', feed['url']) and re.search(r'alt=rss', feed['url']):
                guesses.append(re.sub(r'alt=rss', '', feed['url']))
                
            # wordpress blogs have
            #   http://example.com/?feed=rss => http://example.com/?feed=atom or
            #   http://example.com/feed => http://example.com/feed/atom/ 
            if re.search(r'feed=rss', feed['url']):
                guesses.append(re.sub(r'feed=rss', 'feed=atom', feed['url']))
            if re.search(r'feed/rss', feed['url']):
                guesses.append(re.sub(r'feed/rss', 'feed/atom', feed['url']))

            for feed_url in guesses:
                hub_url = self.gethub(feed_url)
                if hub_url:
                     break;
           
        if hub_url:
            # yey we found something
            # update task
            task = ssscrapeapi.database.execute('SELECT id, args FROM ssscrapecontrol.ssscrape_task WHERE args LIKE "%' + feed['url'] + '%"')
            task_id, task_args = task.fetchone()
            t = ssscrapeapi.Task()
            t['id'] = task_id
            t['args'] = re.sub(feed['url'], feed_url, task_args)
            t.save()
            
            # update feed table
            f = feeds.Feed()
            f['id'] = feed['id']
            f['hub'] = hub_url
            f['url'] = feed_url
            f.save()
            return 0
        else: 
            return 1

    def gethub(self, feed_url):
        ''' get the hub url location from the feed '''
        # request and parse feed
        d = feedparserx.parse(feed_url)
        # get the hub
        # <link rel='hub' href='http://pubsubhubbub.appspot.com/'/>
        # <atom:link href="http://posterous.superfeedr.com" rel="hub"/>
        hub_url = None
        try:
            hub_url = (link for link in d.feed.links if link['rel'] == 'hub' ).next()['href']
        except:
            raise WorkerException(2, keyword = 'action', msg = 'This feed has no hub link')
        # update
        return hub_url
            

    def run(self):
        # 1. Initialization
        self.update_msg = True
        try:
            # execute the action
            m = getattr(self, self.action, None)
            if m:
                feed = feeds.Feed()
                feed.load(self.feed_id)                
                self.result = m(feed)
            else:
                raise WorkerException(2, keyword = 'action', msg = 'Invalid worker action') 
        except WorkerException, e:
            self.result = e.exit_code
            self._save_error_msg(e)
            print >> sys.stderr, 'WorkerException ' + str(e)
            self.update_msg = False
        except Exception, e:
            traceback.print_exc(file=sys.stderr)
            self.result = 2
        ssscrapeapi.database.disconnect('database')
        ssscrapeapi.database.disconnect()
        return self.result
    

    def _save_error_msg(self, e):
        msg =  "%s %s" % (e.keyword, e.msg)
        self.save_status(msg)

    def save_status(self, msg):
        job = ssscrapeapi.Job()
        job['id'] = self.job['id']
        job['message'] = msg
        job.save()
