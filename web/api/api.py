#!/usr/bin/env python

import tornado.httpserver
import tornado.ioloop
import tornado.options
import tornado.web
import tornado.database
import tornado.escape

from tornado.options import define, options

define("port", default=8888, help="run on the given port", type=int)


class MainHandler(tornado.web.RequestHandler):
  def get(self):
    self.write("Hello, this is the ssscrape api")

class FeedsHandler(tornado.web.RequestHandler):
  def initialize(self):
    self.db = tornado.database.Connection("localhost", "ssscrape", user="root", password="root")

  def get(self, feed_id, action):
    feed = self.db.get("SELECT * FROM ssscrape_feed WHERE ssscrape_feed.id = %s", feed_id)
    if action == 'pub':
      action = 'verification'
    getattr(self, action)(feed)

  def post(self, feed_id, action):
    feed = self.db.get("SELECT * FROM ssscrape_feed WHERE ssscrape_feed.id = %s", feed_id)
    getattr(self, action)(feed)

  # actions:
  def show(self, feed):
    feed['pub_date'] = str(feed['pub_date'])
    feed['mod_date'] = str(feed['mod_date'])
    self.write(tornado.escape.json_encode(feed))

  def pub(self, feed):
    # create a job?
    self.write('TODO');

  def verification(self, feed):
    # hub.mode
    #   REQUIRED. The literal string "subscribe" or "unsubscribe", which matches the original request to the hub from the subscriber.
    # hub.topic
    #   REQUIRED. The topic URL given in the corresponding subscription request.
    # hub.challenge
    #   REQUIRED. A hub-generated, random string that MUST be echoed by the subscriber to verify the subscription.
    self.write(self.get_argument("hub.challenge"))



def main():
  tornado.options.parse_command_line()
  application = tornado.web.Application([
    (r"/", MainHandler),
    (r"/feeds/([0-9]+)/(\w+)", FeedsHandler)
  ])
  http_server = tornado.httpserver.HTTPServer(application)
  http_server.listen(options.port)
  tornado.ioloop.IOLoop.instance().start()


if __name__ == "__main__":
  main()
