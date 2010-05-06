-- Track Metadata {{2
--
-- This is a list of tracks with their associated metadata

DROP TABLE IF EXISTS `shuffler_track`;
CREATE TABLE `shuffler_track` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`feed_item_id` INTEGER UNSIGNED DEFAULT NULL REFERENCES `ssscrape_feed_item`.`id`,
	`site_url` VARCHAR(255) DEFAULT NULL,
	`permalink` VARCHAR(255) NOT NULL COMMENT 'permalink',
	`location` VARCHAR(255) NOT NULL COMMENT 'mp3/.. url',
	`anchor` TEXT,
	`posted` DATETIME DEFAULT NULL,
	`sent` DATETIME DEFAULT NULL,
	PRIMARY KEY(`id`),
    KEY(`feed_item_id`, `location`),
	KEY(`permalink`)
) DEFAULT CHARSET=UTF8;
