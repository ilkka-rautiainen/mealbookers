ALTER TABLE  `users` ADD  `role` ENUM(  'normal',  'admin' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'normal' AFTER `language` ;