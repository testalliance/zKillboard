--



DROP TABLE IF EXISTS `zz_factions`;

CREATE TABLE `zz_factions` (

  `factionID` int(16) NOT NULL DEFAULT '0',

  `name` varchar(64) NOT NULL,

  `ticker` varchar(16) DEFAULT NULL,

  PRIMARY KEY (`factionID`),

  KEY `name` (`name`)

) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;



--

-- Table structure for table `zz_feeds`

