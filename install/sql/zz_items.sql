
DROP TABLE IF EXISTS `zz_items`;
CREATE TABLE `zz_items` (
  `killID` int(11) NOT NULL,
  `typeID` int(11) NOT NULL,
  `flag` smallint(2) DEFAULT NULL,
  `qtyDropped` int(11) NOT NULL,
  `qtyDestroyed` int(11) NOT NULL,
  `insertOrder` smallint(2) DEFAULT NULL,
  `price` decimal(16,2) NOT NULL,
  `singleton` tinyint(1) DEFAULT NULL,
  `inContainer` tinyint(1) DEFAULT NULL,
  KEY `killID` (`killID`),
  KEY `typeID` (`typeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

