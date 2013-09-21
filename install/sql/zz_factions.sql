DROP TABLE IF EXISTS `zz_factions`;
CREATE TABLE `zz_factions` (
  `factionID` int(16) NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL,
  `ticker` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`factionID`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;

INSERT INTO `zz_factions` (`factionID`, `name`, `ticker`) VALUES
(500001, 'Caldari State', 'caldari'),
(500002, 'Minmatar Republic', 'minmatar'),
(500003, 'Amarr Empire', 'amarr'),
(500004, 'Gallente Federation', 'gallente');