
DROP TABLE IF EXISTS `zz_domains`;
CREATE TABLE `zz_domains` (
  `domainID` int(11) NOT NULL AUTO_INCREMENT,
  `cloudFlareID` int(11) DEFAULT NULL,
  `userID` int(11) NOT NULL,
  `createdDTTM` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expirationDTTM` timestamp NULL DEFAULT NULL,
  `adfreeExpirationDTTM` timestamp NULL DEFAULT NULL,
  `domain` varchar(256) NOT NULL,
  `setToDelete` tinyint(1) NOT NULL,
  PRIMARY KEY (`domainID`),
  UNIQUE KEY `domainName` (`domain`),
  KEY `userID` (`userID`),
  KEY `expirationDTTM` (`expirationDTTM`),
  KEY `cloudFlareID` (`cloudFlareID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

