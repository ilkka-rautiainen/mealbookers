CREATE TABLE IF NOT EXISTS `invites` (
  `email_address` varchar(255) NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `hash` char(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `invites` ADD INDEX(`hash`);

ALTER TABLE  `users` ADD UNIQUE  `email_address` (  `email_address` );