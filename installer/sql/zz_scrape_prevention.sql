
DROP TABLE IF EXISTS `zz_scrape_prevention`;
CREATE TABLE `zz_scrape_prevention` (
  `ip` varchar(64) NOT NULL,
  `uri` varchar(256) NOT NULL,
  `dttm` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

