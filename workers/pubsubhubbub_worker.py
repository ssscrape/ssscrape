#!/usr/bin/env python
"""Usage: 
    pubsubhubbub_worker.py <feed_id> <action>
    actions is one of:
      - gethub: get the hub url location and save to the feed
      - sub: subscribe
      - unsub: unsubscribe
      - rss2atom: for rss feeds, try to find out an alternative atom feed. if found then transform.
"""
import sys
import os
import re
import getopt 

def main(argv=None):
    if argv is None:
        argv = sys.argv

    try:
        opts, args = getopt.getopt(sys.argv[1:], "f:a:")
    except getopt.GetoptError, err:
        # print help information and exit:
        print str(err) # will print something like "option -a not recognized"
        usage()
        sys.exit(2)
    output = None
    verbose = False
    for o, a in opts:
        if o == "-f":
            feed_id = a
        elif o == '-a':
            action = a
        else:
            assert False, "unhandled option"

    try:
        runner = pubsubhubbub.WorkerRunner(feed_id=feed_id, action=action)
        return runner.run() 
    except pubsubhubbub.WorkerUsage, err:
        print >>sys.stderr, err.msg
        print >>sys.stderr, "for help use --help"
        return 2 

# fixup paths
topdir = os.path.normpath(os.path.join(os.path.abspath(sys.argv[0]), os.pardir, os.pardir))

# then add the lib/ and lib/ext/ paths to sys.path
sys.path.insert(0, os.path.join(topdir, 'lib'))
sys.path.insert(0, os.path.join(topdir, 'lib', 'ext'))

if __name__ == "__main__":
    import pubsubhubbub
    sys.exit(main())
# end if
