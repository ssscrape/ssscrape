#!/usr/bin/env python
import sys
import os
import os.path
import glob
import re
import getopt
import unittest

class Usage(Exception):
    def __init__(self, msg):
        self.msg = msg
    # end def __init__
# end class Usage

class DefaultCollectionWorkerTestCase(unittest.TestCase):
    def __init__(self, configFile, testFile):
        unittest.TestCase.__init__(self)
        self.configFile = configFile
        self.testFile = testFile

    def setUp(self):
        self.cw = collectionworker.CollectionWorkerRunner(self.configFile, self.testFile)

    def runTest(self):
        try:
            self.cw.run()
        except:
            self.fail("Exception caught in test " + self.testFile)

def main(argv=None):
    if argv is None:
        argv = sys.argv
    try:
        which_tests = sys.argv[1]
        config_file = sys.argv[1] + '.ini'
    except:
        which_tests = '**'
        config_file = 'tests.ini'
    fpath = os.path.join(sys.path[1], 'tests', which_tests, '*.xml')
    allfiles = glob.glob(fpath)
    suite = unittest.TestSuite()
    for test_file in allfiles:
        suite.addTest(DefaultCollectionWorkerTestCase(config_file, test_file))
    runner = unittest.TextTestRunner()
    runner.run(suite)
    return 0
# end def main

# fixup paths
topdir = os.path.normpath(os.path.join(os.path.abspath(sys.argv[0]), os.pardir, os.pardir))
print topdir
sys.path.insert(0, topdir)
sys.path.insert(0, os.path.join(topdir, 'ext'))

if __name__ == "__main__":
    import collectionworker
    sys.exit(main())
# end if
