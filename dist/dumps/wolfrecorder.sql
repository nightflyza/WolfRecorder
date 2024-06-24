-- clean WolfRecorder MySQL dump. 

CREATE DATABASE wr;

USE wr;

CREATE TABLE IF NOT EXISTS `weblogs` (
  `id` int(11) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `admin` varchar(45) default NULL,
  `ip` varchar(64) default NULL,
  `event` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `date` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `storages` (
  `id` int(11) NOT NULL auto_increment,
  `path` varchar(200) NOT NULL,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `models` (
  `id` int(11) NOT NULL auto_increment,
  `modelname` varchar(255) NOT NULL,
  `template` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `cameras` (
  `id` int(11) NOT NULL auto_increment,
  `modelid` INT(11) NOT NULL,
  `ip` varchar(64) NOT NULL,
  `login` varchar(128) NOT NULL,
  `password` varchar(128) NOT NULL,
  `active` tinyint(2) NOT NULL DEFAULT 0,
  `storageid` INT(11) NOT NULL,
  `channel`  varchar(64) NOT NULL,
  `comment`  varchar(255) DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `lair` (
 `id` int(11) NOT NULL auto_increment,
 `key` VARCHAR( 40 ) NOT NULL ,
 `value` VARCHAR ( 255 ) NULL,
  PRIMARY KEY  (`id`)
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `schedule` (
  `id` int(11) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `user` varchar(128) NOT NULL,
  `channel` varchar(64) NOT NULL,
  `datetimefrom` datetime NOT NULL,
  `datetimeto` datetime NOT NULL,
  `sizeforecast` BIGINT(20) NOT NULL,
  `done` tinyint(2) NOT NULL DEFAULT 0,
  `finishdate` datetime DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `user` (`user`),
  KEY `done` (`done`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `acl` (
  `id` int(11) NOT NULL auto_increment,
  `user` varchar(128) NOT NULL,
  `cameraid` INT(11) NOT NULL,
  `channel` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 0.0.8 update
ALTER TABLE `lair` CHANGE `value` `value` LONGTEXT NULL DEFAULT NULL;

-- 0.0.9 update
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