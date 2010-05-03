
--
-- This file is organized as follows:
--
-- 1.  Main tables, i.e. the ones holding the data about the main entities.
-- 2.  Child tables, i.e. entities belonging to one of the main entities (one-to-many relations)
-- 3.  Cross tables, i.e. the ones linking pieces of data together (many-to-many)
--



-- Main tables  {{{1

-- Feed Metadata {{2
--
-- This is a list of feeds with their associated (static) metadata

DROP TABLE IF EXISTS `ssscrape_feed_metadata`;
CREATE TABLE `ssscrape_feed_metadata` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`feed_id` INTEGER UNSIGNED DEFAULT NULL REFERENCES `ssscrape_feed`.`id`,
	`url` VARCHAR(255) NOT NULL COMMENT 'feed url',
	`class` ENUM('text', 'audio', 'video') NOT NULL DEFAULT 'text',
	`language` VARCHAR(255) DEFAULT NULL,
        `kind` ENUM('full', 'partial') NOT NULL DEFAULT 'full', -- whether it is a full content feed, or partial
	`partial_args` VARCHAR(255) DEFAULT NULL, -- arguments for crawler for permalinks (partial content feeds)
        `tags` VARCHAR(255) DEFAULT NULL, -- tags for feeds are comma-seperated, Ie. tag1,tag2,tag3. Allows grouping of related feeds
	PRIMARY KEY(`id`),
	UNIQUE KEY(`url`),
	KEY(`feed_id`)
) DEFAULT CHARSET=UTF8;

-- Feeds  {{{2
--
-- This is a list of feed items to track.

DROP TABLE IF EXISTS `ssscrape_feed`;
CREATE TABLE `ssscrape_feed` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`url` VARCHAR(255) NOT NULL COMMENT 'permalink',
	`title` VARCHAR(255) NOT NULL,
	`description` TEXT,
	`language` VARCHAR(255) DEFAULT NULL,
	`copyright` VARCHAR(255) DEFAULT NULL,
	`type` VARCHAR(255) DEFAULT NULL,  -- atom, rss, ...
	`class` ENUM('text', 'audio', 'video') NOT NULL DEFAULT 'text',
	`favicon` VARCHAR(255) DEFAULT NULL,
	`encoding` VARCHAR(255) DEFAULT NULL,
	`lastmod` VARCHAR(255) DEFAULT NULL,
	`etag` VARCHAR(255) DEFAULT NULL,
	`pub_date` DATETIME DEFAULT NULL,
	`mod_date` DATETIME DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY(`url`)
) DEFAULT CHARSET=UTF8;


-- Feed items  {{{2
--
-- This holds the actual feed item data

DROP TABLE IF EXISTS `ssscrape_feed_item`;
CREATE TABLE `ssscrape_feed_item` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`feed_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed`.`id`,
	`guid` VARCHAR(255) DEFAULT NULL COMMENT 'guid',
	`title` VARCHAR(255) DEFAULT NULL,
	`summary` TEXT,
	`content` MEDIUMTEXT COMMENT 'raw HTML of the permalink',
	`content_clean_html` MEDIUMTEXT COMMENT 'cleaned HTML of the permalink',
	`content_clean` TEXT COMMENT 'cleaned plain text of the permalink',
	`comments_url` VARCHAR(255) DEFAULT NULL,
	`pub_date` DATETIME DEFAULT NULL,
	`mod_date` DATETIME DEFAULT NULL,
	`fetch_date` DATETIME DEFAULT NULL,
	`copyright` VARCHAR(255) DEFAULT NULL,
	`language` VARCHAR(255) DEFAULT NULL,
	PRIMARY KEY(`id`),
	UNIQUE KEY(`feed_id`, `guid`),
	KEY(`pub_date`)
) DEFAULT CHARSET=UTF8;


-- Authors  {{{2
--
-- This is a list of authors. Both feeds and feed items can have an author
-- field.

DROP TABLE IF EXISTS `ssscrape_author`;
CREATE TABLE `ssscrape_author` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`link` VARCHAR(255) DEFAULT NULL,
	`email` VARCHAR(64) DEFAULT NULL,
	`fullname` VARCHAR(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`fullname`, `email`)
) DEFAULT CHARSET=UTF8;


-- Enclosures  {{{2
--
-- Enclosures belong to a feed item and can have a wide range of properties.

DROP TABLE IF EXISTS `ssscrape_enclosure`;
CREATE TABLE `ssscrape_enclosure` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`feed_item_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed_item`.`id`,
	`guid` VARCHAR(255) DEFAULT NULL,
	`link` VARCHAR(255) NOT NULL,
	`title` VARCHAR(255) DEFAULT NULL,
	`description` VARCHAR(255) DEFAULT NULL,
	`audio_channels` TINYINT UNSIGNED DEFAULT NULL,
	`height` SMALLINT UNSIGNED DEFAULT NULL,
	`width` SMALLINT UNSIGNED DEFAULT NULL,
	`filesize` INTEGER UNSIGNED DEFAULT NULL,
	`duration` INTEGER DEFAULT NULL,
	`bit_rate` FLOAT DEFAULT NULL,
	`sampling_rate` FLOAT DEFAULT NULL,
	`frame_rate` FLOAT DEFAULT NULL,
	`expression` VARCHAR(255) DEFAULT NULL,
	`mime` VARCHAR(255) DEFAULT NULL,
	`mime_real` VARCHAR(255) DEFAULT NULL,
	`language` VARCHAR(255) DEFAULT NULL,
	`copyright_url` VARCHAR(255) DEFAULT NULL,
	`copyright_attribution` TEXT DEFAULT NULL,
	`medium` VARCHAR(32) DEFAULT NULL,
	`pub_date` DATETIME DEFAULT NULL,
	`mod_date` DATETIME DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`guid`)
) DEFAULT CHARSET=UTF8;


-- Geo data  {{{2

DROP TABLE IF EXISTS `ssscrape_geo`;
CREATE TABLE `ssscrape_geo` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`latitude` FLOAT DEFAULT NULL,
	`longitude` FLOAT DEFAULT NULL,
	`name` VARCHAR(255) DEFAULT NULL,
	`description` TEXT DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`latitude`, `longitude`)
) DEFAULT CHARSET=UTF8;


-- Ratings  {{{2

DROP TABLE IF EXISTS `ssscrape_rating`;
CREATE TABLE `ssscrape_rating` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`scheme` VARCHAR(255) DEFAULT NULL,
	`value` VARCHAR(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`scheme`(165), `value`(165))
) DEFAULT CHARSET=UTF8;


-- Categories and keywords  {{{2

DROP TABLE IF EXISTS `ssscrape_category`;
CREATE TABLE `ssscrape_category` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`label` VARCHAR(255) DEFAULT NULL, -- Extended name
	`term` VARCHAR(255) NOT NULL, -- Short name
	`scheme` VARCHAR(255) DEFAULT NULL, -- Namespace, e.g. technorati tags
	`type` ENUM('c', 'k') DEFAULT 'c' NOT NULL COMMENT 'c=category, k=keyword',
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=UTF8;


-- }}}


-- Child tables (one to many relationships)  {{{1

-- Feed links  {{{2
--
-- This keeps a list of links for feeds, e.g. alternate versions, or references
-- to the homepage.

DROP TABLE IF EXISTS `ssscrape_feed_link`;
CREATE TABLE `ssscrape_feed_link` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`feed_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed`.`id`,
	`relation` VARCHAR(100) DEFAULT NULL, -- e.g. alternate
	`link` VARCHAR(255) DEFAULT NULL, -- href value
	`type` VARCHAR(255) DEFAULT NULL, -- MIME type
	`title` VARCHAR(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`feed_id`, `link`)
) DEFAULT CHARSET=UTF8;


-- Feed images  {{{2

DROP TABLE IF EXISTS `ssscrape_feed_image`;
CREATE TABLE `ssscrape_feed_image` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`feed_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed`.`id`,
	`url` VARCHAR(255) DEFAULT NULL,
	`title` VARCHAR(255) DEFAULT NULL,
	`width` INTEGER UNSIGNED DEFAULT NULL,
	`height` INTEGER UNSIGNED DEFAULT NULL,
	`description` TEXT DEFAULT NULL,
	`link` VARCHAR(255) DEFAULT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=UTF8;


-- Feed item options {{{2
--
-- Simple options for feed items. Key/value pairs
DROP TABLE IF EXISTS `ssscrape_feed_item_option`;
CREATE TABLE `ssscrape_feed_item_option` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`feed_item_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed_item`.`id`,
	`option` VARCHAR(255) NOT NULL,
	`value` VARCHAR(255) NOT NULL,
	`mod_date` DATETIME DEFAULT NULL,
	PRIMARY KEY(`id`)
) DEFAULT CHARSET=UTF8; 


-- Feed item links  {{{2
--
-- See the description for the feed links table

DROP TABLE IF EXISTS `ssscrape_feed_item_link`;
CREATE TABLE `ssscrape_feed_item_link` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`feed_item_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed_item`.`id`,
	`relation` VARCHAR(100) DEFAULT NULL,
	`link` VARCHAR(255) DEFAULT NULL,
	`type` VARCHAR(255) DEFAULT NULL,
	`title` VARCHAR(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`feed_item_id`, `relation`(25), `link`),
	INDEX (`link`)
) DEFAULT CHARSET=UTF8;


-- Feed item comments  {{{2

DROP TABLE IF EXISTS `ssscrape_feed_item_comment`;
CREATE TABLE `ssscrape_feed_item_comment` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`feed_item_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed_item`.`id`,
	`guid` VARCHAR(255) DEFAULT NULL,
	`comment` TEXT,
        `author` VARCHAR(255) DEFAULT NULL,
        `author_id` INTEGER UNSIGNED DEFAULT NULL REFERENCES `ssscrape_author`.`id`,
	`pub_date` DATETIME DEFAULT NULL,
	`mod_date` DATETIME DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`guid`),
	KEY(`pub_date`)
) DEFAULT CHARSET=UTF8;


-- Feed item events  {{{2
--
-- Feed items can be linked to events

DROP TABLE IF EXISTS `ssscrape_feed_item_event`;
CREATE TABLE `ssscrape_feed_item_event` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`feed_item_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed_item`.`id`,
	`DATETIME_start` DATETIME DEFAULT NULL,
	`DATETIME_end` DATETIME DEFAULT NULL,
	`title` VARCHAR(255) DEFAULT NULL,
	`description` TEXT,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=UTF8 COMMENT='this should be linked to geodata as well';


-- Enclosure captions  {{{2
--
-- Enclosures may have multiple captions with different time spans.

DROP TABLE IF EXISTS `ssscrape_enclosure_caption`;
CREATE TABLE `ssscrape_enclosure_caption` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`enclosure_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_enclosure`.`id`,
	`link` VARCHAR(255) NOT NULL,
	`format` ENUM('machine', 'native') NOT NULL, -- FIXME: what is this?
	`language` VARCHAR(255) DEFAULT NULL,
	`type` ENUM('plain', 'html') DEFAULT NULL,
	`time_start` FLOAT NOT NULL,
	`time_end` FLOAT NOT NULL,
	`caption` TEXT NOT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=UTF8;


-- Enclosure credits  {{{2
--
-- Enclosures can have an extended credits specification, composed of multiple
-- pieces.

DROP TABLE IF EXISTS `ssscrape_enclosure_credits`;
CREATE TABLE `ssscrape_enclosure_credits` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`enclosure_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_enclosure`.`id`,
	`link` VARCHAR(255) DEFAULT NULL,
	`role` VARCHAR(255) DEFAULT NULL,
	`scheme` VARCHAR(255) DEFAULT NULL,
	`credit` VARCHAR(255) DEFAULT NULL COMMENT 'This is a defined list of accepted values',
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=UTF8;


-- Enclosure restrictions  {{{2

DROP TABLE IF EXISTS `ssscrape_enclosure_restriction`;
CREATE TABLE `ssscrape_enclosure_restriction` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`enclosure_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_enclosure`.`id`,
	`link` VARCHAR(255) DEFAULT NULL,
	`type` ENUM('allow','deny') DEFAULT NULL,
	`relationship` VARCHAR(255) DEFAULT NULL,
	`restriction` VARCHAR(255) NOT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=UTF8;


-- Enclosure thumbnails  {{{2

DROP TABLE IF EXISTS `ssscrape_thumbnail`;
CREATE TABLE `ssscrape_thumbnail` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`enclosure_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_enclosure`.`id`,
	`link` VARCHAR(255) DEFAULT NULL,
	`url` VARCHAR(255) DEFAULT NULL COMMENT 'url of the thumbnail image',
	`height` INTEGER UNSIGNED DEFAULT NULL,
	`width` INTEGER UNSIGNED DEFAULT NULL,
	`time` FLOAT DEFAULT NULL,
	PRIMARY KEY (`id`)
) DEFAULT CHARSET=UTF8;

-- }}}


-- Cross tables (many to many relationships)  {{{1

-- Link feed to authors  {{{2

DROP TABLE IF EXISTS `ssscrape_feed2author`;
CREATE TABLE `ssscrape_feed2author` (
	`feed_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed`.`id`,
	`author_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_author`.`id`,
	`type` ENUM('a', 'c') DEFAULT 'a' NOT NULL COMMENT 'type a=author, c=contributor',
	PRIMARY KEY(`feed_id`, `author_id`)
) DEFAULT CHARSET=UTF8;


-- Link feed items to authors  {{{2

DROP TABLE IF EXISTS `ssscrape_feed_item2author`;
CREATE TABLE `ssscrape_feed_item2author` (
	`feed_item_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed_item`.`id`,
	`author_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_author`.`id`,
	`type` ENUM('a', 'c') DEFAULT 'a' NOT NULL COMMENT 'a=author, c=contributor',
	PRIMARY KEY(`feed_item_id`, `author_id`)
) DEFAULT CHARSET=UTF8;


-- Link feed items to categories  {{{2

DROP TABLE IF EXISTS `ssscrape_feed_item2category`;
CREATE TABLE `ssscrape_feed_item2category` (
	`feed_item_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed_item`.`id`,
	`category_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_category`.`id`,
	PRIMARY KEY(`feed_item_id`, `category_id`)
) DEFAULT CHARSET=UTF8;


-- Link enclosures to categories  {{{2

DROP TABLE IF EXISTS `ssscrape_enclosure2category`;
CREATE TABLE `ssscrape_enclosure2category` (
	`enclosure_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_enclosure`.`id`,
	`category_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_category`.`id`,
	PRIMARY KEY(`enclosure_id`, `category_id`)
) DEFAULT CHARSET=UTF8;


-- Link enclosures to ratings  {{{2

DROP TABLE IF EXISTS `ssscrape_enclosure2rating`;
CREATE TABLE `ssscrape_enclosure2rating` (
	`enclosure_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_enclosure`.`id`,
	`rating_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_rating`.`id`,
	PRIMARY KEY(`enclosure_id`, `rating_id`)
) DEFAULT CHARSET=UTF8;


-- Link feed to geo data  {{{2

DROP TABLE IF EXISTS `ssscrape_feed2geo`;
CREATE TABLE `ssscrape_feed2geo` (
	`feed_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed`.`id`,
	`geo_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_geo`.`id`,
	PRIMARY KEY(`feed_id`, `geo_id`)
) DEFAULT CHARSET=UTF8;


-- Link feed items to geo data  {{{2

DROP TABLE IF EXISTS `ssscrape_feed_item2geo`;
CREATE TABLE `ssscrape_feed_item2geo` (
	`feed_item_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed_item`.`id`,
	`geo_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_geo`.`id`,
	PRIMARY KEY(`feed_item_id`, `geo_id`)
) DEFAULT CHARSET=UTF8;


-- Link events to geo data  {{{2

DROP TABLE IF EXISTS `ssscrape_feed_item_event2geo`;
CREATE TABLE `ssscrape_feed_item_event2geo` (
	`event_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_feed_item_event`.`id`,
	`geo_id` INTEGER UNSIGNED NOT NULL REFERENCES `ssscrape_geo`.`id`,
	PRIMARY KEY(`event_id`, `geo_id`)
) DEFAULT CHARSET=UTF8;



-- }}}


-- Modeline for fancy Vim stuff (do not edit):
-- vim: set ft=sql foldenable foldmethod=marker foldlevel=0:
