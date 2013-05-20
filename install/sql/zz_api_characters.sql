--



DROP TABLE IF EXISTS `zz_api_characters`;

CREATE TABLE `zz_api_characters` (

  `apiRowID` int(8) NOT NULL AUTO_INCREMENT,

  `keyID` int(16) NOT NULL,

  `characterID` int(16) NOT NULL,

  `corporationID` int(32) NOT NULL,

  `isDirector` varchar(1) NOT NULL,

  `maxKillID` int(16) NOT NULL DEFAULT '0',

  `cachedUntil` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',

  `lastChecked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',

  `errorCode` int(6) NOT NULL,

  PRIMARY KEY (`apiRowID`),

  UNIQUE KEY `characterID_2` (`characterID`,`corporationID`,`isDirector`),

  UNIQUE KEY `keyID` (`keyID`,`characterID`),

  KEY `user_id` (`keyID`),

  KEY `characterID` (`characterID`),

  KEY `corporationID` (`corporationID`),

  KEY `isDirector` (`isDirector`),

  KEY `cachedUntil` (`cachedUntil`)

) ENGINE=InnoDB AUTO_INCREMENT=14395963 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;



--

-- Table structure for table `zz_api_log`

