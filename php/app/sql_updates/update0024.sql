ALTER TABLE  `invites` ADD UNIQUE (`code`);
ALTER TABLE  `invites` ADD INDEX (  `group_id` );
ALTER TABLE  `invites` ADD INDEX (  `inviter_id` );
ALTER TABLE  `invites` ADD FOREIGN KEY (  `group_id` ) REFERENCES  `groups` ( `id` ) ON DELETE CASCADE ON UPDATE CASCADE ;
ALTER TABLE  `invites` CHANGE  `inviter_id`  `inviter_id` INT( 10 ) UNSIGNED NULL DEFAULT NULL ;
ALTER TABLE  `invites` ADD FOREIGN KEY (  `inviter_id` ) REFERENCES  `users` ( `id` ) ON DELETE SET NULL ON UPDATE CASCADE ;