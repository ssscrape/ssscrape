Ssscrape v 1.0 README

(c) 2007-2010 ISLA, University of Amsterdam

Contact: jijkoun@uva.nl



Ssscrape stands for Syndicated and Semi-Structured Content Retrieval and
Processing Environment. Ssscrape is a framework for crawling and processing
dynamic web data, such as RSS/Atom feeds.


General
=======

Ssscrape is a system for tracking dynamic online collections of items: RSS
feeds, blogs, news, podcasts etc. For a set of online data sources, user can
configure Ssscrape to:

  - periodically check for new information items;
  - download and store (e.g., in a database) items along with available
    meta-data;
  - clean the content (e.g., producing plain text) and perform other
    application-specific processing (e.g., tagging, duplicate detection,
    linking) 
  - monitor activity and report errors

Ssscrape is flexible and easily expandable:

  - new online data sources added simply by specifying URLs, periodicity and
    specific processing methods
  - new data processing methods (workers) can easily be added as scripts with a
    simple API 


Terminology
===========

In Ssscrape, the following modules and entities are defined:

  - Task: a periodic activity added by a user via Sscrape monitor (shell script
    or web interface); a task is defined by its id, worker, options, type, status
    (active/inactive), periodicity or (optional) hour/minute/second of execution,
    actual start time of the latest job (updated by manager), remote resource id
    (e.g,. hostname for tasks involving fetching web content)

  - Monitor: module for monitoring Ssscrape's activity and reporting errors
    from command line or through a web interface.

  - Job: one-time activity scheduled for execution by a user (via monitor), by the
    scheduler or a worker. A job is defined by its id, (optional) task id, worker,
    options, type, status (pending, running, temporary error), scheduled start
    time, actual start time, completion time, last update time, worker's output,
    status message. Completed jobs are stored to the job logs.

  - Scheduler: a process that checks which periodic tasks are ready for
    execution and schedules corresponding jobs; there is a single scheduler for
    a Ssscrape instance.

  - Manager: a process that selects and executes scheduled jobs based on
    available resources; there can be multiple managers (running on one or
    multiple hosts) for a single Ssscrape instance: they will be serving the
    same job queue.

  - Worker: an executable used by a manager to execute a job.

  - Feed: a dynamic online data source (RSS/Atom Feed, blog, web page with user
    comments etc.) that can be viewed as providing a list of items.  

For more details on tasks, jobs, feeds, items, see database table definitions
in files database/*.sql.

The following section describes the operation of Ssscrape modules.

Learn more on the wiki. [wiki](https://github.com/ssscrape/ssscrape/wiki)

