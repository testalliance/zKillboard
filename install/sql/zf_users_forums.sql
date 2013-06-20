
DROP TABLE IF EXISTS `zf_users_forums`;
CREATE TABLE `zf_users_forums` (
  `userID` int(11) NOT NULL,
  `categoryID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;

