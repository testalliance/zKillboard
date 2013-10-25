
DROP TABLE IF EXISTS `zz_prices`;
CREATE TABLE `zz_prices` (
  `typeID` int(16) NOT NULL,
  `price` decimal(32,2) NOT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`typeID`),
  KEY `expires` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

