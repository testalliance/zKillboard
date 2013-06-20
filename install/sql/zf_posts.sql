
DROP TABLE IF EXISTS `zf_posts`;
CREATE TABLE `zf_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `postID` int(11) NOT NULL,
  `postedBy` varchar(512) NOT NULL,
  `postedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;

