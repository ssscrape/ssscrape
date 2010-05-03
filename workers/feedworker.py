#!/usr/bin/env python
"""Usage: feedworker.py -u|--url <url>"""
import sys
import os
import re
import getopt

def main(argv=None):
    feedFile = None
    if argv is None:
        argv = sys.argv
    # end if
    try:
        runner = feedworker.FeedWorkerRunner(argv)
        return runner.run() 
    except feedworker.FeedWorkerUsage, err:
        print >>sys.stderr, err.msg
        print >>sys.stderr, "for help use --help"
        return 2 
    # end try
# end def main

# fixup paths
topdir = os.path.normpath(os.path.join(os.path.abspath(sys.argv[0]), os.pardir, os.pardir))

# then add the lib/ and lib/ext/ paths to sys.path
sys.path.insert(0, os.path.join(topdir, 'lib'))
sys.path.insert(0, os.path.join(topdir, 'lib', 'ext'))

if __name__ == "__main__":
    import feedworker
    sys.exit(main())
# end if
