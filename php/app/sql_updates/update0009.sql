ALTER TABLE  `suggestions` CHANGE  `creator_id`  `creator_id` INT( 10 ) UNSIGNED NULL ;
ALTER TABLE  `suggestions` DROP FOREIGN KEY  `suggestions_ibfk_2` ;
ALTER TABLE  `suggestions` ADD CONSTRAINT  `suggestions_ibfk_2` FOREIGN KEY (  `creator_id` ) REFERENCES  `users` (
`id`) ON DELETE SET NULL ON UPDATE RESTRICT ;
ALTER TABLE  `groups` CHANGE  `creator_id`  `creator_id` INT( 10 ) UNSIGNED NULL ;
ALTER TABLE  `groups` DROP FOREIGN KEY  `groups_ibfk_1` ;
ALTER TABLE  `groups` ADD CONSTRAINT  `groups_ibfk_1` FOREIGN KEY (  `creator_id` ) REFERENCES `users` (
`id`) ON DELETE SET NULL ON UPDATE RESTRICT ;