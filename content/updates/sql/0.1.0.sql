CREATE TABLE IF NOT EXISTS `camopts` (
  `id` int(11) NOT NULL auto_increment,
  `cameraid` INT(11) NOT NULL,
  `rtspport` INT(11) DEFAULT NULL,
  `keepsubalive` tinyint(2) NOT NULL DEFAULT 0,
  `order` INT(11) DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `cameraid` (`cameraid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;