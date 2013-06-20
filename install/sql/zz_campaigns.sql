
DROP TABLE IF EXISTS `zz_campaigns`;
CREATE TABLE `zz_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `campaignTitle` varchar(256) NOT NULL,
  `campaignStart` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `campaignEnd` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `campaignActive` int(11) NOT NULL DEFAULT '1',
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

