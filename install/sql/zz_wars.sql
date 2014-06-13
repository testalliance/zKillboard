
DROP TABLE IF EXISTS `zz_wars`;
CREATE TABLE `zz_wars` (
  `warID` int(11) NOT NULL,
  `timeDeclared` timestamp NULL DEFAULT NULL,
  `timeStarted` timestamp NULL DEFAULT NULL,
  `timeFinished` timestamp NULL DEFAULT NULL,
  `openForAllies` tinyint(1) NOT NULL,
  `mutual` tinyint(1) NOT NULL,
  `aggressor` int(11) NOT NULL,
  `agrShipsKilled` int(11) NOT NULL,
  `agrIskKilled` decimal(16,2) NOT NULL,
  `defender` int(11) NOT NULL,
  `dfdShipsKilled` int(11) NOT NULL,
  `dfdIskKilled` decimal(16,2) NOT NULL,
  `lastChecked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`warID`),
  KEY `timeStarted` (`timeStarted`),
  KEY `timeFinished` (`timeFinished`),
  KEY `aggressor` (`aggressor`),
  KEY `defender` (`defender`),
  KEY `agrShipsKilled` (`agrShipsKilled`),
  KEY `dfdShipsKilled` (`dfdShipsKilled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


