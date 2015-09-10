
UPDATE `restaurants` SET `location` = GeomFromText('POINT(60.1869024 24.8215163)',0), `link` = 'http://www.subway.fi/fi/ravintolat/espoo/espoo-otaniemi' WHERE `restaurants`.`id` = 15;