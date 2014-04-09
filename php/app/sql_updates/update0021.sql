CREATE TABLE IF NOT EXISTS `tokens` (
  `token` char(40) NOT NULL,
  `id` int(10) NOT NULL,
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE  `suggestions_users` CHANGE  `hash`  `hash` CHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE  `invites` CHANGE  `hash`  `hash` CHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;