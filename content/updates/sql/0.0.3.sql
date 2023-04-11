CREATE TABLE IF NOT EXISTS `schedule` (
  `id` int(11) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `user` varchar(128) NOT NULL,
  `channel` varchar(64) NOT NULL,
  `datetimefrom` datetime NOT NULL,
  `datetimeto` datetime NOT NULL,
  `sizeforecast` INT(11) NOT NULL,
  `done` tinyint(2) NOT NULL DEFAULT 0,
  `finishdate` datetime DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `user` (`user`),
  KEY `done` (`done`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;