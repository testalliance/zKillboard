
DROP TABLE IF EXISTS `zz_irc_log`;
CREATE TABLE `zz_irc_log` (
  `id` int(8) NOT NULL,
  `nick` varchar(64) NOT NULL,
  `command` varchar(64) NOT NULL,
  `parameters` varchar(256) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

