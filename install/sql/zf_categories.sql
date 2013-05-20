--



DROP TABLE IF EXISTS `zf_categories`;

CREATE TABLE `zf_categories` (

  `id` int(11) NOT NULL AUTO_INCREMENT,

  `categoryID` int(11) NOT NULL,

  `postName` varchar(512) NOT NULL,

  `post` longtext NOT NULL,

  `postedBy` varchar(512) NOT NULL,

  `postDate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',

  UNIQUE KEY `id` (`id`)

) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;



--

-- Table structure for table `zf_posts`

