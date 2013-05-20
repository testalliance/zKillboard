--



DROP TABLE IF EXISTS `zz_feeds`;

CREATE TABLE `zz_feeds` (

  `id` int(11) NOT NULL AUTO_INCREMENT,

  `url` varchar(2048) NOT NULL,

  `lastFetchTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',

  PRIMARY KEY (`id`),

  UNIQUE KEY `id` (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=latin1;



--

-- Table structure for table `zz_irc_access`

