
DROP TABLE IF EXISTS `zz_online_uri`;
CREATE TABLE `zz_online_uri` (
  `uri` varchar(256) NOT NULL,
  `dttm` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

