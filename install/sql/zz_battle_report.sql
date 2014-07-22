
DROP TABLE IF EXISTS `zz_battle_report`;
CREATE TABLE `zz_battle_report` (
  `battleID` int(11) NOT NULL AUTO_INCREMENT,
  `solarSystemID` int(16) NOT NULL,
  `dttm` varchar(16) NOT NULL,
  `options` text NOT NULL,
  PRIMARY KEY (`battleID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

