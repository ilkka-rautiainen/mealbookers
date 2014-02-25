ALTER TABLE  `users` ADD  `language` CHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'fi' AFTER  `last_name` ;
ALTER TABLE  `suggestions_users` ADD  `hash` CHAR( 36 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
ADD  `accepted` BOOLEAN NOT NULL ;
ALTER TABLE  `suggestions_users` ADD INDEX (  `hash` ) ;