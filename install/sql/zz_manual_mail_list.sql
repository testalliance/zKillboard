--



DROP TABLE IF EXISTS `zz_manual_mail_list`;

CREATE TABLE `zz_manual_mail_list` (

  `eveKillID` int(16) DEFAULT NULL,

  `processed` int(8) DEFAULT NULL,

  UNIQUE KEY `eveKillID` (`eveKillID`),

  KEY `processed` (`processed`),

  KEY `processed_2` (`processed`,`eveKillID`)

) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;



--

-- Table structure for table `zz_manual_mails`

