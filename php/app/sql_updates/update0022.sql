ALTER TABLE `invites` DROP `hash`;
ALTER TABLE  `invites` ADD  `id` INT( 10 ) UNSIGNED NOT NULL FIRST ;
ALTER TABLE `invites` ADD PRIMARY KEY(`id`);
ALTER TABLE  `invites` CHANGE  `id`  `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ;
ALTER TABLE  `tokens` CHANGE  `id`  `id` INT( 10 ) UNSIGNED NOT NULL ;
ALTER TABLE `suggestions_users` DROP `hash`;