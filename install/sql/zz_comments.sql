
DROP TABLE IF EXISTS `zz_comments`;
CREATE TABLE `zz_comments` (
  `commentID` int(11) NOT NULL AUTO_INCREMENT,
  `pageID` varchar(64) NOT NULL,
  `userID` int(11) NOT NULL,
  `datePosted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `characterID` int(64) NOT NULL,
  `theComment` varchar(512) NOT NULL,
  `thumbsup` int(6) NOT NULL DEFAULT '0',
  `thumbsdown` int(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`commentID`),
  UNIQUE KEY `pageID_2` (`pageID`,`userID`,`characterID`,`theComment`),
  KEY `killID` (`pageID`),
  KEY `pageID` (`pageID`),
  FULLTEXT KEY `theComment` (`theComment`)
) ENGINE=MyIsam AUTO_INCREMENT=111 DEFAULT CHARSET=latin1  ;

