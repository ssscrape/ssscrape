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


Learn more on the wiki. [wiki](https://github.com/ssscrape/ssscrape/wiki)

