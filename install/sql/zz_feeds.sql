
DROP TABLE IF EXISTS `zz_feeds`;
CREATE TABLE `zz_feeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(2048) NOT NULL,
  `lastFetchTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `edkStyle` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `edkStyle` (`edkStyle`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

