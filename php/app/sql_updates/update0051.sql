ALTER TABLE `notifications` DROP FOREIGN KEY `notifications_ibfk_1`; ALTER TABLE `notifications` ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`suggestion_id`) REFERENCES `np37882_mealbookers`.`suggestions`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;