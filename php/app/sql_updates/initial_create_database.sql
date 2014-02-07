
CREATE TABLE IF NOT EXISTS `config` (
  `sql_version` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO config (sql_version) VALUES
(0);