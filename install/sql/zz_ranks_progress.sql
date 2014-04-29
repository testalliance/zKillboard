
DROP TABLE IF EXISTS `zz_ranks_progress`;
CREATE TABLE `zz_ranks_progress` (
  `dttm` date NOT NULL,
  `type` varchar(16) CHARACTER SET utf8 NOT NULL,
  `typeID` int(16) NOT NULL,
  `recentRank` mediumint(16) NOT NULL DEFAULT '0',
  `overallRank` mediumint(16) NOT NULL DEFAULT '0',
  PRIMARY KEY (`dttm`,`type`,`typeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

