
DROP TABLE IF EXISTS `zz_dna`;
CREATE TABLE `zz_dna` (
  `rowID` int(11) NOT NULL AUTO_INCREMENT,
  `shipTypeID` int(16) NOT NULL,
  `dna` varchar(256) NOT NULL,
  `killID` int(16) NOT NULL,
  `killDate` date NOT NULL,
  `total` int(11) NOT NULL,
  PRIMARY KEY (`rowID`),
  UNIQUE KEY `shipTypeID` (`shipTypeID`,`dna`,`killDate`),
  KEY `typeID` (`shipTypeID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

