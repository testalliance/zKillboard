
DROP TABLE IF EXISTS `zz_online`;
CREATE TABLE `zz_online` (
  `ip` varchar(64) NOT NULL DEFAULT '',
  `dttm` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

