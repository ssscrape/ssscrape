
--
-- This file contains a utility table for Twones.
--


-- Twones enclosure table {{{1
--
-- This table is used by Twones to keep track of which enclosures where sent to Twones

DROP TABLE IF EXISTS `twones_enclosure`;
CREATE TABLE `twones_enclosure` (
	`enclosure_id` INTEGER UNSIGNED NOT NULL,
	`sent` DATETIME DEFAULT NULL,
	PRIMARY KEY(`enclosure_id`)
) DEFAULT CHARSET=UTF8;
