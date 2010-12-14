import os
import sys

os.environ["SSSCRAPE_ENV"] = 'test'


# fixup paths
topdir = os.path.normpath(os.path.join(os.path.abspath(sys.argv[0]), os.pardir, os.pardir, os.pardir))

# then add the lib/ and lib/ext/ paths to sys.path
sys.path.insert(0, os.path.join(topdir, 'lib'))
sys.path.insert(0, os.path.join(topdir, 'lib', 'ext'))

import random
import unittest
import urllib2
import urllib
import re
import mox

import pubsubhubbub
import feedparserx
import ssscrapeapi
import ssscrapeapi.feeds as feeds

class Struct:
    pass

class WorkerRunnerTester(unittest.TestCase):

    def setUp(self):
        # connect to the database
        ssscrapeapi.database.connect()
        ssscrapeapi.database.connect('database')

        self.loadFixtures()

        # export SSSCRAPE_JOB_ID=6283;
        os.environ["SSSCRAPE_JOB_ID"] = self.fixtures['job']['id']
        self.m = mox.Mox()
    
    def loadFixtures(self):
        self.fixtures = {}
        feed = feeds.Feed(url='http://www.slothboogie.com/feeds/posts/default?alt=rss')
        feed.save()
        self.fixtures['feed'] = feed

        task = ssscrapeapi.Task(args='-u http://www.slothboogie.com/feeds/posts/default?alt=rss')
        task.save()
        self.fixtures['task'] = task

        job = ssscrapeapi.Job(task_id=task['id'])
        job.save()
        self.fixtures['job'] = job

    def testDb(self):
        print 'test_db'
        feed = feeds.Feed()
        feed.load(self.fixtures['feed']['id'])
        print(feed)

    def testGethub(self):
        # <link rel='hub' href='http://pubsubhubbub.appspot.com/'/>

        runner = pubsubhubbub.WorkerRunner(self.fixtures['feed']['id'], action='not_used')
        self.m.StubOutWithMock(feedparserx, 'parse') 
        fakeDoc = Struct()
        fakeDoc.feed = Struct()
        fakeDoc.feed.links = [{'rel': 'hub', 'href': 'http://a.hub.url'}]
        feedparserx.parse('http://some.feed.url').AndReturn(fakeDoc)
        self.m.ReplayAll()
       
        # all good 
        self.assertEqual(runner.gethub('http://some.feed.url'), 'http://a.hub.url')

        self.m.UnsetStubs()
        self.m.VerifyAll()

    def testSubWithNoHub(self):
        # be sure we dont have a hub
        self.fixtures['feed']['hub'] = None
        self.fixtures['feed'].save()
        
        runner = pubsubhubbub.WorkerRunner(self.fixtures['feed']['id'], action='sub')
        # permanently failed
        self.assertEqual(runner.run(), 2)

    def testSubWithSuccess(self):
        # be sure we have a hub
        self.fixtures['feed']['hub'] = 'http://tumblr.superfeedr.com/'
        self.fixtures['feed'].save()

        self.m.StubOutWithMock(urllib2, 'urlopen')
        urllib2.urlopen(mox.Func(lambda req: re.search(urllib.urlencode({'hub.mode': 'subscribe'}), req.data) and 
                                            re.search(urllib.urlencode({'hub.topic': feed['url']}), req.data) and 
                                            req._Request__original == feed['hub'])).AndRaise(urllib2.HTTPError('some_url.com', 204, 'some msg', 'headers', self.m.CreateMockAnything()))
        
        self.m.ReplayAll()

        runner = pubsubhubbub.WorkerRunner(feed_id=self.fixtures['feed']['id'], action='sub')
        
        # all good
        self.assertEqual(runner.run(), 0)
        
        self.m.UnsetStubs()
        self.m.VerifyAll()

    def testUnsubWithSuccess(self):
        # be sure we have a hub
        self.fixtures['feed']['hub'] = 'http://tumblr.superfeedr.com/'
        self.fixtures['feed'].save()

        self.m.StubOutWithMock(urllib2, 'urlopen')
        urllib2.urlopen(mox.Func(lambda req: re.search(urllib.urlencode({'hub.mode': 'unsubscribe'}), req.data) and
                                            re.search(urllib.urlencode({'hub.topic': feed['url']}), req.data) and
                                            req._Request__original == feed['hub'])).AndRaise(urllib2.HTTPError('some_url.com', 204, 'some msg', 'headers', self.m.CreateMockAnything()))

        self.m.ReplayAll()

        runner = pubsubhubbub.WorkerRunner(feed_id=self.fixtures['feed']['id'], action='unsub')

        # all good
        self.assertEqual(runner.run(), 0)

        self.m.UnsetStubs()
        self.m.VerifyAll()


    def testDiscoverHub(self):
        runner = pubsubhubbub.WorkerRunner(feed_id=self.fixtures['feed']['id'], action='discoverhub')
        self.assertEqual(runner.run(), 0)
 
        return 0

    def tearDown(self):
        """ cleanup fixtures """
        for k in self.fixtures:
            self.fixtures[k].destroy()


if __name__ == '__main__':
    unittest.main()
