
DROP TABLE IF EXISTS `zz_campaigns_entities`;
CREATE TABLE `zz_campaigns_entities` (
  `id` int(11) NOT NULL,
  `entityType` varchar(64) NOT NULL,
  `entityID` int(11) NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

