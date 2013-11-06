
DROP TABLE IF EXISTS `zz_marketdata`;
CREATE TABLE `zz_marketdata` (
  `orderID` bigint(16) NOT NULL,
  `typeID` int(8) NOT NULL,
  `bid` tinyint(1) NOT NULL,
  `price` decimal(36,2) NOT NULL,
  `minVolume` int(8) NOT NULL,
  `volRemaining` int(16) NOT NULL,
  `volEntered` int(16) NOT NULL,
  `bid_range` int(4) NOT NULL,
  `issued` int(16) NOT NULL,
  `duration` int(3) NOT NULL,
  `expires` int(16) NOT NULL DEFAULT '0',
  `stationID` int(16) NOT NULL,
  `solarSystemID` int(16) NOT NULL,
  `regionID` int(16) NOT NULL,
  `profitChecked` tinyint(1) NOT NULL DEFAULT '0',
  `pid` int(5) NOT NULL DEFAULT '0',
  `insert_dttm` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`orderID`,`regionID`),
  KEY `typeID` (`typeID`),
  KEY `stationID` (`stationID`),
  KEY `regionID` (`regionID`),
  KEY `bid` (`bid`),
  KEY `profitChecked` (`profitChecked`),
  KEY `typeID_2` (`typeID`,`regionID`),
  KEY `insert_dttm` (`insert_dttm`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

