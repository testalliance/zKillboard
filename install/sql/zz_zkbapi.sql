
DROP TABLE IF EXISTS `zz_zkbapi`;
CREATE TABLE IF NOT EXISTS `zz_zkbapi` (
  `userID` int(11) NOT NULL,
  `keyCode` varchar(64) NOT NULL,
  `lastAccess` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `accessCount` int(11) NOT NULL,
  `accessLeft` int(11) NOT NULL DEFAULT '5000',
  `additionalAccess` int(11) NOT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `keyCode` (`keyCode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;