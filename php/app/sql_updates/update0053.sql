ALTER TABLE `notifications` DROP FOREIGN KEY `notifications_ibfk_3`; ALTER TABLE `notifications` ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`other_user_id`) REFERENCES `np37882_mealbookers`.`users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;