
import time
import urlparse

from twisted.python import log

import ssscrape

def add(name, interval_sec = 10):
    d = ssscrape.database.run_interaction(_add, name, interval_sec)
    return d

def _add(transaction, name, interval_sec):
    transaction.execute('''SELECT MIN(id) FROM `ssscrape_resource` WHERE name = %s''', (name))
    row = transaction.fetchone()
    try:
        id = int(row[0])
    except TypeError:
        id = 0
    except ValueError:
        id = 0
    if id == 0:
        transaction.execute('''INSERT INTO `ssscrape_resource` (`name`, `interval`) VALUES(%s, SEC_TO_TIME(%s))''', (name, interval_sec))
        transaction.execute('''SELECT LAST_INSERT_ID()''')
        row = transaction.fetchone()
        id = int(row[0])
    return id

def url2resource(url):
   parts = urlparse.urlparse(url)
   return parts[1] # only return the hostname and port bit
