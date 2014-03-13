ALTER TABLE  `group_memberships` ADD  `joined` INT( 10 ) UNSIGNED NOT NULL ;
ALTER TABLE  `group_memberships` ADD INDEX (  `joined` );
CREATE TABLE IF NOT EXISTS `event_log` (
  `user_id` int(10) unsigned DEFAULT NULL,
  `time` int(10) unsigned NOT NULL,
  KEY `time` (`time`,`user_id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;
ALTER TABLE  `meals` ADD INDEX (  `day` ,  `restaurant_id` ,  `language` );
ALTER TABLE  `restaurant_opening_hours` DROP INDEX  `base_index` ,
ADD UNIQUE  `base_index` (  `restaurant_id` ,  `end_weekday` ,  `start_weekday` ,  `type` );
ALTER TABLE  `suggestions` ADD INDEX (  `datetime` ,  `restaurant_id` );