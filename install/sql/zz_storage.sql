
DROP TABLE IF EXISTS `zz_storage`;
CREATE TABLE `zz_storage` (
  `locker` varchar(64) NOT NULL,
  `contents` text,
  PRIMARY KEY (`locker`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

