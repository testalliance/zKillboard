
DROP TABLE IF EXISTS `zz_battle_report`;
CREATE TABLE `zz_battle_report` (
  `battleID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `solarSystemID` int(16) NOT NULL,
  `startTime` timestamp NULL DEFAULT NULL,
  `endTime` timestamp NULL DEFAULT NULL,
  `teamA` text,
  `teamB` text,
  PRIMARY KEY (`battleID`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

