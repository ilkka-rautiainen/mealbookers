
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

INSERT INTO `restaurants` (`id`, `name`, `street_address`, `location`)
VALUES (1, 'Amica Alvari', 'Otakaari 1', GeomFromText('POINT(60.1856866 24.826957)'));

INSERT INTO `restaurants` (`id`, `name`, `street_address`, `location`)
VALUES (2, 'Amica Kvarkki', 'Otakaari 3', GeomFromText('POINT(60.1883477 24.8298963)'));

INSERT INTO `restaurants` (`id`, `name`, `street_address`, `location`)
VALUES (3, 'Amica TUAS', 'Otaniementie 17', GeomFromText('POINT(60.187085 24.8199059)'));

INSERT INTO `restaurants` (`id`, `name`, `street_address`, `location`)
VALUES (4, 'Amica Puu 2', 'Tekniikantie 3', GeomFromText('POINT(60.180788 24.8249963)'));