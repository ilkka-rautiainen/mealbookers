ALTER TABLE `restaurants` ADD `personnell_only` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `link`;

INSERT INTO `restaurants` (`id`, `name`, `street_address`, `location`, `personnell_only`)
VALUES (13, 'Artturi', 'Kemistintie 1', GeomFromText('POINT(60.1836285 24.8245029)'), 1);