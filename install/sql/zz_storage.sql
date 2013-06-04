--



DROP TABLE IF EXISTS `zz_storage`;

CREATE TABLE `zz_storage` (

  `locker` varchar(64) COLLATE utf8_bin NOT NULL,

  `contents` varchar(4098) COLLATE utf8_bin DEFAULT NULL,

  PRIMARY KEY (`locker`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin ROW_FORMAT=COMPRESSED;



--

-- Table structure for table `zz_tickets`

