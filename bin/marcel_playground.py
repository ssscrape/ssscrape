
# marcel's python playground. 
#   here marcel learns about python and tries to fix things. forgive his ignorance.

import sys
import os
import re
import getopt


def main():
  url = 'http://stereogum.com/mp3/Liars%20-%20Scissor.mp3'
  anchorReader = shuffler.AnchorMetadataReader()
  filenameReader = shuffler.FilenameMetadataReader()
  metadata = filenameReader.fetch(url, anchorReader)
  print metadata['artist']
  print metadata['title']


 # fixup paths
topdir = os.path.normpath(os.path.join(os.path.abspath(sys.argv[0]), os.pardir, os.pardir))

# then add the lib/ and lib/ext/ paths to sys.path
sys.path.insert(0, os.path.join(topdir, 'lib'))
sys.path.insert(0, os.path.join(topdir, 'lib', 'ext'))

import ssscrapeapi
import shuffler
from shuffler.utils import AnchorMetadataReader, FilenameMetadataReader

if __name__ == "__main__":
    sys.exit(main()) 


