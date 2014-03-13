ALTER TABLE  `users` ADD  `notify_suggestion_received` TINYINT(1) UNSIGNED NOT NULL ,
ADD  `notify_suggestion_accepted` TINYINT(1) UNSIGNED NOT NULL ,
ADD  `notify_suggestion_left_alone` TINYINT(1) UNSIGNED NOT NULL ,
ADD  `notify_suggestion_deleted` TINYINT(1) UNSIGNED NOT NULL ,
ADD  `notify_group_memberships` TINYINT(1) UNSIGNED NOT NULL ;