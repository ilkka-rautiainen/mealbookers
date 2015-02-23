ALTER TABLE `notifications` ADD INDEX(`user_id`);
ALTER TABLE `notifications` ADD FOREIGN KEY (`user_id`) REFERENCES `np37882_mealbookers`.`users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;