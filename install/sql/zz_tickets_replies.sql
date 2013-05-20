--



DROP TABLE IF EXISTS `zz_tickets_replies`;

CREATE TABLE `zz_tickets_replies` (

  `userid` int(11) NOT NULL,

  `belongsTo` int(11) NOT NULL,

  `name` varchar(512) NOT NULL,

  `reply` mediumtext NOT NULL,

  `moderator` int(11) NOT NULL DEFAULT '0',

  `datePosted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=latin1;



--

-- Table structure for table `zz_users`

