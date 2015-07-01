DROP TABLE IF EXISTS `device`;
CREATE TABLE `device` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `ip` int(10) unsigned NOT NULL,
  `mac` varchar(17) NOT NULL,
  `name` varchar(30) DEFAULT NULL,
  `lease_start` int(11) NOT NULL,
  `lease_end` int(11) NOT NULL,
  `speed` enum('1','2') NOT NULL DEFAULT '1',
  `active` enum('1','2') NOT NULL DEFAULT '1',
  `privileged` enum('1','2') NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`),
  KEY `lease_start` (`lease_start`),
  KEY `lease_end` (`lease_end`),
  KEY `active` (`active`),
  KEY `privileged` (`privileged`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
