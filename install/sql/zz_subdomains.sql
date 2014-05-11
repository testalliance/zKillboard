
DROP TABLE IF EXISTS `zz_subdomains`;
CREATE TABLE `zz_subdomains` (
  `subdomainID` int(11) NOT NULL AUTO_INCREMENT,
  `subdomain` varchar(64) NOT NULL,
  `adfreeUntil` timestamp NULL DEFAULT NULL,
  `banner` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`subdomainID`),
  UNIQUE KEY `subdomain` (`subdomain`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

