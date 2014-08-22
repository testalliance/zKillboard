
DROP TABLE IF EXISTS `zz_crest_killmail`;
CREATE TABLE `zz_crest_killmail` (
  `killID` int(16) NOT NULL,
  `hash` varchar(64) NOT NULL,
  `processed` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`killID`,`hash`),
  KEY `killID` (`killID`),
  KEY `hash` (`hash`),
  KEY `processed` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

