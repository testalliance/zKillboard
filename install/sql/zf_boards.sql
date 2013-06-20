
DROP TABLE IF EXISTS `zf_boards`;
CREATE TABLE `zf_boards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(512) NOT NULL,
  `name` varchar(512) NOT NULL,
  `description` varchar(512) NOT NULL,
  `belongsTo` int(11) NOT NULL,
  `lastPostDate` varchar(512) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastPostBy` varchar(512) NOT NULL,
  `lastPostName` varchar(512) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;

