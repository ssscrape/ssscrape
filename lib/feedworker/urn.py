
__doc__ = '''UUID generation methods for MultiMatch'''

import random

def _transform_mime_type(mime_type):
    '''Transform a MIME type string into the representation used in URNs'''
    #TODO: add regex check and throw exception if it does not match
    return mime_type.replace('/', '_')

def _generate_urn(cached, mime_type):
    '''Generate urn for the given cached value and MIME type (internal)'''

    cached_str = cached and 'cached' or 'nonCached'
    mime_str = _transform_mime_type(mime_type)
    uuid_str = ''.join(['%x' % n for n in [random.randint(0, 15) for m in xrange(32)]])

    out = 'urn:multimatch:%s:%s:%s' % (cached_str, mime_str, uuid_str)
    return out

def generate_cached_urn(mime_type):
    '''Generate a cached URN for the given MIME type'''
    return _generate_urn(True, mime_type)

def generate_non_cached_urn(mime_type):
    '''Generate a non-cached URN for the given MIME type'''
    return _generate_urn(False, mime_type)


# Print debug output when run directly:
if __name__ == '__main__':

    import sys
    try:
        mime_type = sys.argv[1]
    except IndexError:
        mime_type = 'text/xml'

    print 'cached:   ', generate_cached_urn(mime_type)
    print 'noncached:', generate_non_cached_urn(mime_type)
