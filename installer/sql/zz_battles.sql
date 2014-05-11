
DROP TABLE IF EXISTS `zz_battles`;
CREATE TABLE `zz_battles` (
  `battleID` int(11) NOT NULL AUTO_INCREMENT,
  `solarSystemID` int(11) NOT NULL,
  `solarSystemName` varchar(100) NOT NULL,
  `dttm` datetime NOT NULL,
  `involved` int(11) NOT NULL,
  `kills` int(11) NOT NULL,
  PRIMARY KEY (`battleID`),
  UNIQUE KEY `solarSystemID` (`solarSystemID`,`dttm`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

