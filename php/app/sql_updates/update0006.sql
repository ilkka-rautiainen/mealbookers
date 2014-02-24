CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(15) NOT NULL,
  `admin_id` int(10) unsigned NOT NULL,
  `creator_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

INSERT INTO `groups` (`id`, `name`, `admin_id`, `creator_id`) VALUES
(1, 'Group 1', 1, 1),
(2, 'Group 2', 1, 1);



CREATE TABLE IF NOT EXISTS `group_memberships` (
  `user_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `group_memberships` (`user_id`, `group_id`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 2),
(3, 1);



CREATE TABLE IF NOT EXISTS `suggestions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id` int(10) unsigned NOT NULL,
  `datetime` datetime NOT NULL,
  `restaurant_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;



CREATE TABLE IF NOT EXISTS `suggestions_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `suggestion_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;



CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email_address` varchar(255) NOT NULL,
  `passhash` char(40) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `joined` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

INSERT INTO `users` (`id`, `email_address`, `passhash`, `first_name`, `last_name`, `active`, `joined`) VALUES
(1, 'iirautiainen@gmail.com', '80450b5d093f3bc092b37a1e90a9ccb68c0e9c6d', 'Ilkka', 'Rautiainen', 1, 1392984945),
(2, 'simo.haakana@aalto.fi', '65f99581a93cf30dafc32b5c178edc6b0294a07f', 'Simo', 'Haakana', 1, 1392984946),
(3, 'patrick.patoila@aalto.fi', '65751857d3fa9c796cf561530e1dec9570d4dd3d', 'Patrick', 'Patoila', 1, 1392984946);


ALTER TABLE  `meals` DROP FOREIGN KEY  `meals_ibfk_1`;
ALTER TABLE  `meals` ADD CONSTRAINT `meal_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;
ALTER TABLE  `groups` ADD INDEX (  `admin_id` ) COMMENT  '';
ALTER TABLE  `groups` ADD INDEX (  `creator_id` ) COMMENT  '';
ALTER TABLE  `groups` ADD FOREIGN KEY (  `admin_id` ) REFERENCES  `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE  `groups` ADD FOREIGN KEY (  `creator_id` ) REFERENCES  `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE  `group_memberships` ADD FOREIGN KEY (  `user_id` ) REFERENCES  `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE  `group_memberships` ADD FOREIGN KEY (  `group_id` ) REFERENCES  `groups` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE  `suggestions` ADD INDEX (  `restaurant_id` ) COMMENT  '';
ALTER TABLE  `suggestions` ADD INDEX (  `creator_id` ) COMMENT  '';
ALTER TABLE  `suggestions` ADD FOREIGN KEY (  `restaurant_id` ) REFERENCES  `restaurants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE  `suggestions` ADD FOREIGN KEY (  `creator_id` ) REFERENCES  `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE  `suggestions_users` ADD INDEX (  `suggestion_id` ) COMMENT  '';
ALTER TABLE  `suggestions_users` ADD INDEX (  `user_id` ) COMMENT  '';
ALTER TABLE  `suggestions_users` ADD FOREIGN KEY (  `suggestion_id` ) REFERENCES  `suggestions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE  `suggestions_users` ADD FOREIGN KEY (  `user_id` ) REFERENCES  `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE  `suggestions_users` ADD UNIQUE (`suggestion_id` ,`user_id`) COMMENT  '';