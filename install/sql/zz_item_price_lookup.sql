
DROP TABLE IF EXISTS `zz_item_price_lookup`;
CREATE TABLE `zz_item_price_lookup` (
  `typeID` int(11) NOT NULL,
  `priceDate` date NOT NULL DEFAULT '0000-00-00',
  `price` decimal(16,2) NOT NULL,
  PRIMARY KEY (`typeID`,`priceDate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

