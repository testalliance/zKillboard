
DROP TABLE IF EXISTS `zz_irc_access`;
CREATE TABLE `zz_irc_access` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `host` varchar(256) NOT NULL,
  `accessLevel` int(8) NOT NULL DEFAULT '0',
  `insertTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_2` (`name`,`host`),
  KEY `insertTime` (`insertTime`),
  KEY `name_3` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

