CREATE TABLE IF NOT EXISTS `exceptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `type` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(10) unsigned NOT NULL,
  `stack_trace` text NOT NULL,
  `request` text NOT NULL,
  `info` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;