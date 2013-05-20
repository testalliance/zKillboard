--



DROP TABLE IF EXISTS `zz_manual_mail_fails`;

CREATE TABLE `zz_manual_mail_fails` (

  `eveKillID` int(16) DEFAULT NULL,

  `error` varchar(128) NOT NULL,

  UNIQUE KEY `eveKillID` (`eveKillID`),

  UNIQUE KEY `eveKillID_2` (`eveKillID`,`error`),

  KEY `error` (`error`)

) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;



--

-- Table structure for table `zz_manual_mail_list`

