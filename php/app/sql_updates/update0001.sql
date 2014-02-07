
CREATE TABLE IF NOT EXISTS `restaurants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `street_address` text NOT NULL,
  `location` point DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `meals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `language` varchar(5) NOT NULL,
  `day` date NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `restaurant_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
ALTER TABLE `meals` ADD INDEX  `restaurant_id` (  `restaurant_id` );

ALTER TABLE `meals`
  ADD CONSTRAINT `meals_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

INSERT INTO `app`.`restaurants` (`id`, `name`, `street_address`, `location`) VALUES (NULL, 'Alvari', 'Otakaari 1', GeomFromText('POINT(60.1856866 24.826957)'));
