
import sys, os.path
import ConfigParser

#
# Directory contasnts
#

BASE_DIR = os.path.normpath(os.path.join(os.path.dirname(__file__), os.path.pardir, os.pardir))
CONF_DIR = os.path.join(BASE_DIR, 'conf')
ENV_VAR = 'SSSCRAPE_ENV'

#
# Create and populate the ConfigParser instance
#

try:
    _cp
except NameError:
    _cp = ConfigParser.ConfigParser()
    config_files = ['default.conf', 'local.conf']
    _cp.read([os.path.join(CONF_DIR, filename) for filename in config_files])
    del filename
    del config_files

#
# Helper methods
#

WORKER_DEFAULTS_SECTION = 'worker-defaults'

def _section_for_job_type(job_type):
    assert isinstance(job_type, basestring)
    return 'worker-%s' % job_type


# Public API starts here

#
# Helper routines to get information about configuration data
#

def has_section(section):
    '''Determines whether a given section exists, or not.'''
    assert isinstance(section, basestring)

    return _cp.has_section(section)


#
# Getter methods to retrieve configuration data
#


def get_string(section, option, default=None):
    '''Get a string value from the configuration'''
    assert isinstance(section, basestring)
    assert isinstance(option, basestring)
    try:
        value = _cp.get(section, option)
    except (ConfigParser.NoSectionError, ConfigParser.NoOptionError), e:
        if default is not None:
            value = default
        else:
            raise

    assert isinstance(value, basestring)
    return value


def get_int(section, option, default=None):
    '''Get an integer value from the configuration'''
    assert isinstance(section, basestring)
    assert isinstance(option, basestring)
    try:
        value = _cp.getint(section, option)
    except (ConfigParser.NoSectionError, ConfigParser.NoOptionError), e:
        if default is not None:
            value = default
        else:
            raise

    assert isinstance(value, int)
    return value


def get_bool(section, option, default=None):
    '''Get a boolean value from the configuration'''
    assert isinstance(section, basestring)
    assert isinstance(option, basestring)
    try:
        value = _cp.getboolean(section, option)
    except (ConfigParser.NoSectionError, ConfigParser.NoOptionError), e:
        if default is not None:
            value = default
        else:
            raise

    assert isinstance(value, bool)
    return value


def get_time(section, option, default=None):
    '''Get a boolean value from the configuration'''
    assert isinstance(section, basestring)
    assert isinstance(option, basestring)
    try:
        value = _cp.get(section, option)
    except (ConfigParser.NoSectionError, ConfigParser.NoOptionError), e:
        if default is not None:
            value = default
        else:
            raise

    value = ssscrape.misc.parse_time_string_to_seconds(value)

    assert isinstance(value, int)
    return value


#
# Worker-specific configuration
#

def worker_get_string(job_type, option, default=None):
    try:
        out = get_string(_section_for_job_type(job_type), option, default)
    except (ConfigParser.NoSectionError, ConfigParser.NoOptionError), e:
        out = get_string(WORKER_DEFAULTS_SECTION, option, default)

    return out


def worker_get_int(job_type, option, default=None):
    try:
        out = get_int(_section_for_job_type(job_type), option, default)
    except (ConfigParser.NoSectionError, ConfigParser.NoOptionError), e:
        out = get_int(WORKER_DEFAULTS_SECTION, option, default)

    return out


def worker_get_bool(job_type, option, default=None):
    try:
        out = get_bool(_section_for_job_type(job_type), option, default)
    except (ConfigParser.NoSectionError, ConfigParser.NoOptionError), e:
        out = get_bool(WORKER_DEFAULTS_SECTION, option, default)

    return out


def worker_get_time(job_type, option, default=None):
    try:
        out = get_time(_section_for_job_type(job_type), option, default)
    except (ConfigParser.NoSectionError, ConfigParser.NoOptionError), e:
        out = get_time(WORKER_DEFAULTS_SECTION, option, default)

    return out

