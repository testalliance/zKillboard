--



DROP TABLE IF EXISTS `zz_blog`;

CREATE TABLE `zz_blog` (

  `blogID` int(8) NOT NULL AUTO_INCREMENT,

  `url` varchar(256) NOT NULL,

  `title` varchar(256) NOT NULL,

  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  `postedBy` varchar(256) NOT NULL,

  `post` mediumtext NOT NULL,

  PRIMARY KEY (`blogID`),

  UNIQUE KEY `url` (`url`),

  KEY `blogID` (`blogID`),

  FULLTEXT KEY `url_2` (`url`,`post`)

) ENGINE=MyIsam AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1;



--

-- Table structure for table `zz_campaigns`

