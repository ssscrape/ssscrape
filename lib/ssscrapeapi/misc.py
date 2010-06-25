
__doc__ = '''Miscellaneous classes and functions'''

import urllib
import urlparse

def url2resource(url):
    '''
    Converts a URL to a resource name. This can then be used to find a resource ID, for example.
    '''
    parts = urlparse.urlparse(url)
    return parts[1] # only return the hostname and port bit

# url_fix was taken from http://stackoverflow.com/questions/120951/how-can-i-normalize-a-url-in-python
def url_fix(s, charset='utf-8'):
    """Sometimes you get an URL by a user that just isn't a real
    URL because it contains unsafe characters like ' ' and so on.  This
    function can fix some of the problems in a similar way browsers
    handle data entered by the user:

    :param charset: The target charset for the URL if the url was
                    given as unicode string.
    """
    if isinstance(s, unicode):
        s = s.encode(charset, 'ignore')
    scheme, netloc, path, qs, anchor = urlparse.urlsplit(s)
    path = urllib.quote(path, '/%')
    qs = urllib.quote_plus(qs, ':&=')
    return urlparse.urlunsplit((scheme, netloc, path, qs, anchor))

def quote_url(url):
    '''
    Quotes a URL using percent encoding when necessary (Ie. when there is a character
    that is not an ASCII character.
    '''
    chars = []
    for c in url:
        if ord(c) <= 127:
            chars.append(c)
        else:
            chars.append(urllib.quote(c.encode('utf-8')))
    return ''.join(chars)

def parse_time_string_to_seconds(time_str):
    '''
    Parse a time string into a number of seconds. Seconds, minutes, hours, and
    days are recognized by a s, m, h, d, w and M suffix. The suffix is required.
    Example; 10m becomes 600. This method raises ValueError in case of invalid input.

    @param time_str: The time string to parse.

    @return: The number of seconds
    '''
    assert isinstance(time_str, basestring)

    try:
        suffix = time_str[-1]
        value = time_str[:-1]
    except StandardError:
        raise ValueError('Invalid time string: %s.' % time_str)

    suffix_factors = {
            's': 1,
            'm': 60,
            'h': 60 * 60,
            'd': 60 * 60 * 24,
            'w': 60 * 60 * 24 * 7,
            'M': 60 * 60 * 24 * 30,
            }

    if not suffix in suffix_factors:
        raise ValueError('Invalid time suffix: %s' % suffix)

    return int(value) * suffix_factors[suffix]


class AttrDict(dict):
    '''
    A dictionary with attribute-style access.
    
    It maps attribute access to the real dictionary.

    Source: http://aspn.activestate.com/ASPN/Cookbook/Python/Recipe/473786
    '''
    def __init__(self, init={}):
        dict.__init__(self, init)

    def __getstate__(self):
        return self.__dict__.items()

    def __setstate__(self, items):
        for key, val in items:
            self.__dict__[key] = val

    def __repr__(self):
        return "%s(%s)" % (self.__class__.__name__, dict.__repr__(self))

    def __setitem__(self, key, value):
        return super(AttrDict, self).__setitem__(key, value)

    def __getitem__(self, name):
        return super(AttrDict, self).__getitem__(name)

    def __delitem__(self, name):
        return super(AttrDict, self).__delitem__(name)

    __getattr__ = __getitem__
    __setattr__ = __setitem__

    def copy(self):
        ch = AttrDict(self)
        return ch
