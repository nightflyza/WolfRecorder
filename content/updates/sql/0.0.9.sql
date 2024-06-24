CREATE TABLE IF NOT EXISTS `custtpls` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL,
  `proto` varchar(10) NOT NULL,
  `main` varchar(255) NOT NULL,
  `sub` varchar(255) NOT NULL,
  `rtspport` INT(11) NOT NULL,
  `sound` tinyint(2) NOT NULL DEFAULT 0,
  `ptz` tinyint(2) NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;