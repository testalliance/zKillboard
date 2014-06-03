
DROP TABLE IF EXISTS `zz_campaigns`;
CREATE TABLE `zz_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `domainID` int(16) NOT NULL DEFAULT '0',
  `uri` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subTitle` varchar(255) NOT NULL,
  `start_dttm` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_dttm` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creation_dttm` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `definition` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `domainID` (`domainID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

