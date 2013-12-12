
DROP TABLE IF EXISTS `zz_battles`;
CREATE TABLE `zz_battles` (
  `battleID` int(11) NOT NULL AUTO_INCREMENT,
  `solarSystemID` int(11) NOT NULL,
  `solarSystemName` varchar(100) NOT NULL,
  `timestamp` varchar(14) NOT NULL DEFAULT '00000000000000',
  `involved` int(11) NOT NULL,
  `kills` int(11) NOT NULL,
  PRIMARY KEY (`battleID`),
  UNIQUE KEY `battleID` (`battleID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

