
--
-- This file contains the main control tables for Ssscrape.
--


-- Resource table {{{1
--
-- This table is used by the Ssscrape scheduler to manage the usage of resources.

DROP TABLE IF EXISTS `ssscrape_resource`;
CREATE TABLE `ssscrape_resource` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`latest_run` DATETIME DEFAULT NULL,
	`interval` TIME NOT NULL DEFAULT '00:00:10',
	PRIMARY KEY(`id`)
) DEFAULT CHARSET=UTF8;


-- Task table {{{1
--
-- This table is used by the Ssscrape scheduler, and maintained by monitors.

DROP TABLE IF EXISTS `ssscrape_task`;
CREATE TABLE `ssscrape_task` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`type` VARCHAR(255) NOT NULL,
	`program` VARCHAR(255) NOT NULL,
	`args` VARCHAR(255) NOT NULL DEFAULT '',
	`state` ENUM('enabled', 'disabled') NOT NULL DEFAULT 'enabled',
	`hostname` VARCHAR(255) DEFAULT NULL,
        `autoperiodicity` ENUM('enabled', 'disabled') NOT NULL DEFAULT 'enabled',
	`periodicity` TIME NOT NULL,
	`hour` TINYINT UNSIGNED DEFAULT NULL,
	`minute` TINYINT UNSIGNED DEFAULT NULL,
	`second` TINYINT UNSIGNED DEFAULT NULL,
	`latest_run` DATETIME DEFAULT NULL,
	`resource_id` INTEGER UNSIGNED DEFAULT NULL REFERENCES `ssscrape_resource`.`id`,
	`data` VARCHAR(255) DEFAULT NULL,
	PRIMARY KEY(`id`),
	INDEX (`state`),
	INDEX (`resource_id`)
) DEFAULT CHARSET=UTF8;


-- Job queue  {{{1
--
-- This table is managed by the Ssscape manager, but other code can insert new
-- jobs into it.

DROP TABLE IF EXISTS `ssscrape_job`;
CREATE TABLE `ssscrape_job` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`task_id` INTEGER UNSIGNED DEFAULT NULL REFERENCES `ssscrape_task`.`id`,
	`type` VARCHAR(255) NOT NULL,
	`program` VARCHAR(255) NOT NULL,
	`args` VARCHAR(4096) NOT NULL DEFAULT '',
	`state` ENUM('pending', 'running', 'completed', 'temporary-error', 'permanent-error') NOT NULL DEFAULT 'pending',
	`message` VARCHAR(255),
	`output` TEXT,
	`hostname` VARCHAR(255),
	`process_id` INTEGER,
	`exit_code` TINYINT,
	`attempts` TINYINT NOT NULL DEFAULT 0, -- jobs that return a temporary-error can be ran multiple times
	`scheduled` DATETIME NOT NULL,
	`start` DATETIME,
	`end` DATETIME,
	`last_update` TIMESTAMP NOT NULL,
	`resource_id` INTEGER UNSIGNED DEFAULT NULL REFERENCES `ssscrape_resource`.`id`,
	PRIMARY KEY (`id`),
	INDEX (`state`),         -- e.g. find all errors
	INDEX (`type`),          -- e.g. for job type discovery
	INDEX (`state`, `type`), -- e.g. manager selecting all pending 'fetch' jobs
	INDEX (`scheduled`),     -- e.g. job selection
	INDEX (`task_id`),       -- e.g. scheduler finding non-pending tasks
	INDEX (`resource_id`)    -- e.g. scheduler finding recently used resources
) DEFAULT CHARSET=UTF8;


-- Job log  {{{1
--
-- This table is managed by the Ssscape manager and acts as a log of the executed jobs.

DROP TABLE IF EXISTS `ssscrape_job_log`;
CREATE TABLE `ssscrape_job_log` LIKE `ssscrape_job`;


-- }}}

-- Job hierarchy  {{{1
--
-- This table is managed by the Ssscrape manager, to track jobs starting other jobs.

DROP TABLE IF EXISTS `ssscrape_job_hierarchy`;
CREATE TABLE `ssscrape_job_hierarchy` (
	`id` INTEGER UNSIGNED AUTO_INCREMENT,
	`job_id` INTEGER UNSIGNED NOT NULL,
	`parent_job_id` INTEGER UNSIGNED,
	`parent_task_id` INTEGER UNSIGNED,
	PRIMARY KEY(`id`),
	INDEX(`job_id`),
	INDEX(`parent_job_id`),
	INDEX(`parent_task_id`)
) DEFAULT CHARSET=UTF8;

-- }}}


-- Job creations {{{1
--
-- This table is used to track records in other tables that were the result of running a certain job.

DROP TABLE IF EXISTS `ssscrape_job_table_item`;
CREATE TABLE `ssscrape_job_table_item` (
	`id` INTEGER UNSIGNED auto_increment,
	`job_id` INTEGER UNSIGNED NOT NULL,
	`table_name` VARCHAR(255) NOT NULL,
	`table_row_id` INTEGER UNSIGNED NOT NULL,
	`timestamp` DATETIME DEFAULT NULL, 
	PRIMARY KEY (`id`),
	INDEX(`job_id`),
	INDEX(`table_name`),
	INDEX(`timestamp`)
) DEFAULT CHARSET=UTF8;

-- }}}


-- Modeline for fancy Vim stuff (do not edit):
-- vim: set ft=sql foldenable foldmethod=marker foldlevel=0:
