--



DROP TABLE IF EXISTS `zz_items`;

CREATE TABLE `zz_items` (

  `killID` int(11) NOT NULL,

  `year` smallint(4) DEFAULT NULL,

  `week` tinyint(2) DEFAULT NULL,

  `typeID` int(11) NOT NULL,

  `flag` smallint(2) DEFAULT NULL,

  `qtyDropped` int(11) NOT NULL,

  `qtyDestroyed` int(11) NOT NULL,

  `insertOrder` smallint(2) DEFAULT NULL,

  `price` decimal(16,2) NOT NULL,

  `singleton` tinyint(1) DEFAULT NULL,

  `inContainer` tinyint(1) DEFAULT NULL,

  KEY `killID` (`killID`),

  KEY `killID_2` (`killID`,`year`,`week`),

  KEY `year` (`year`),

  KEY `week` (`week`),

  KEY `typeID` (`typeID`)

) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;



--

-- Table structure for table `zz_items_temporary`

