#!/usr/bin/env python
import sys
import os
import re
import getopt

# fixup paths
topdir = os.path.normpath(os.path.join(os.path.abspath(sys.argv[0]), os.pardir, os.pardir))

# then add the lib/ and lib/ext/ paths to sys.path
sys.path.insert(0, os.path.join(topdir, 'lib'))
sys.path.insert(0, os.path.join(topdir, 'lib', 'ext'))
sys.path.insert(0, os.path.join(topdir, 'lib', 'ext', 'PIL'))

import ssscrapeapi
from monitor.sparkline_plotter import *


def generate_plots():

    ssscrapeapi.database.connect()

    # Get the statistics on number of feeds in the past 2 weeks
    res = ssscrapeapi.database.execute("""SELECT feed_id, CURRENT_DATE - date(pub_date), COUNT(*) FROM ssscrape_feed_item 
                                          WHERE pub_date < CURRENT_DATE 
                                            AND pub_date >= CURRENT_DATE - INTERVAL 14 DAY 
                                          GROUP BY feed_id, date(pub_date) 
                                          ORDER BY feed_id, date(pub_date)""")

    rows = res.fetchall()

    # Make statistics per feed
    feed_stat = {}
    for row in rows:
        feed, days_ago, count = row 
        day = 14 - int(days_ago)
        if feed not in feed_stat:
            feed_stat[feed] = [0 for x in range(14)]    
        feed_stat[feed][day] = count

    
    # Find where to put the plots with statistics
    plot_dir = ssscrapeapi.config.get_string('monitor', 'web-root')
    assert plot_dir
    plot_dir += '/plots/feed_statistics/'

    for feed in feed_stat:
        d = feed_stat[feed]
        feed_plot_file = plot_dir + str(feed) + '.png'
        sparkline_smooth(d, feed_plot_file,
                         None, None, 4, 15,
                         '#FF0000', '#00FF00', '#0000FF',
                         False, False, True)



def main(argv=None):
    feedFile = None
    if argv is None:
        argv = sys.argv

    try:
        generate_plots()
    except RuntimeError, err:
        print >>sys.stderr, err.msg
        print >>sys.stderr, "for help use --help"
        return 2 

if __name__ == "__main__":
    sys.exit(main())
