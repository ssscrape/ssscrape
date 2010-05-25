ALTER TABLE `shuffler_track` ADD `artist` VARCHAR( 255 ) NULL AFTER `anchor` ,
ADD `title` VARCHAR( 255 ) NULL AFTER `artist` ,
ADD `tags` VARCHAR( 255 ) NULL AFTER `title` ,
ADD `method` ENUM( 'id3', 'filename', 'anchor' ) NULL AFTER `tags` ;