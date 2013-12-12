
DROP TABLE IF EXISTS `zz_api_log`;
CREATE TABLE `zz_api_log` (
  `logID` int(16) NOT NULL AUTO_INCREMENT,
  `requestTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `errorCode` varchar(128) DEFAULT NULL,
  `scope` varchar(64) DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL,
  `options` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`logID`),
  KEY `requestTime` (`requestTime`,`scope`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

