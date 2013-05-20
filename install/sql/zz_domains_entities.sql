--



DROP TABLE IF EXISTS `zz_domains_entities`;

CREATE TABLE `zz_domains_entities` (

  `domainID` int(11) NOT NULL,

  `entityID` int(14) NOT NULL,

  `entityType` varchar(64) NOT NULL,

  `entityName` varchar(128) NOT NULL,

  UNIQUE KEY `entityID` (`domainID`,`entityID`)

) ENGINE=InnoDB DEFAULT CHARSET=latin1;



--

-- Table structure for table `zz_errors`

