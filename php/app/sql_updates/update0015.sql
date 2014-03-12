CREATE TABLE IF NOT EXISTS `restaurant_opening_hours` (
  `restaurant_id` int(10) unsigned NOT NULL,
  `start_weekday` tinyint(1) unsigned NOT NULL,
  `end_weekday` tinyint(1) unsigned NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `type` enum('normal','lunch','breakfast','alacarte') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE  `restaurant_opening_hours` ADD UNIQUE  `base_index` (  `restaurant_id` ,  `start_weekday` ,  `end_weekday` ,  `type` );