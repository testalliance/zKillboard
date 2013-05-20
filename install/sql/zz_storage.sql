--



DROP TABLE IF EXISTS `zz_storage`;

CREATE TABLE `zz_storage` (

  `locker` varchar(64) NOT NULL,

  `contents` varchar(256) DEFAULT NULL,

  PRIMARY KEY (`locker`)

) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;



--

-- Table structure for table `zz_tickets`

