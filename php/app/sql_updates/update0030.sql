CREATE TABLE IF NOT EXISTS `users_restaurants_order` (
  `user_id` int(10) unsigned NOT NULL,
  `restaurant_id` int(10) unsigned NOT NULL,
  `order_points` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`restaurant_id`),
  KEY `restaurant_id` (`restaurant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `users_restaurants_order`
  ADD CONSTRAINT `users_restaurants_order_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_restaurants_order_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
