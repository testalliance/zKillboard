--



DROP TABLE IF EXISTS `ccp_regions`;

CREATE TABLE `ccp_regions` (

  `regionID` int(11) NOT NULL DEFAULT '0',

  `regionName` char(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,

  PRIMARY KEY (`regionID`),

  KEY `regionName` (`regionName`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;



--

-- Dumping data for table `ccp_regions`

--



LOCK TABLES `ccp_regions` WRITE;

INSERT INTO `ccp_regions` VALUES (10000019,'A821-A'),(10000054,'Aridia'),(10000069,'Black Rise'),(10000055,'Branch'),(10000007,'Cache'),(10000014,'Catch'),(10000051,'Cloud Ring'),(10000053,'Cobalt Edge'),(10000012,'Curse'),(10000035,'Deklein'),(10000060,'Delve'),(10000001,'Derelik'),(10000005,'Detorid'),(10000036,'Devoid'),(10000043,'Domain'),(10000039,'Esoteria'),(10000064,'Essence'),(10000027,'Etherium Reach'),(10000037,'Everyshore'),(10000046,'Fade'),(10000056,'Feythabolis'),(10000058,'Fountain'),(10000029,'Geminate'),(10000067,'Genesis'),(10000011,'Great Wildlands'),(10000030,'Heimatar'),(10000025,'Immensea'),(10000031,'Impass'),(10000009,'Insmother'),(10000017,'J7HZ-F'),(10000052,'Kador'),(10000049,'Khanid'),(10000065,'Kor-Azor'),(10000016,'Lonetrek'),(10000013,'Malpais'),(10000042,'Metropolis'),(10000028,'Molden Heath'),(10000040,'Oasa'),(10000062,'Omist'),(10000021,'Outer Passage'),(10000057,'Outer Ring'),(10000059,'Paragon Soul'),(10000063,'Period Basis'),(10000066,'Perrigen Falls'),(10000048,'Placid'),(10000047,'Providence'),(10000023,'Pure Blind'),(10000050,'Querious'),(10000008,'Scalding Pass'),(10000032,'Sinq Laison'),(10000044,'Solitude'),(10000022,'Stain'),(10000041,'Syndicate'),(10000020,'Tash-Murkon'),(10000045,'Tenal'),(10000061,'Tenerifis'),(10000038,'The Bleak Lands'),(10000033,'The Citadel'),(10000002,'The Forge'),(10000034,'The Kalevala Expanse'),(10000018,'The Spire'),(10000010,'Tribute'),(10000004,'UUA-F4'),(10000003,'Vale of the Silent'),(10000015,'Venal'),(10000068,'Verge Vendor'),(11000001,'W-Space #1'),(11000010,'W-Space #10'),(11000011,'W-Space #11'),(11000012,'W-Space #12'),(11000013,'W-Space #13'),(11000014,'W-Space #14'),(11000015,'W-Space #15'),(11000016,'W-Space #16'),(11000017,'W-Space #17'),(11000018,'W-Space #18'),(11000019,'W-Space #19'),(11000002,'W-Space #2'),(11000020,'W-Space #20'),(11000021,'W-Space #21'),(11000022,'W-Space #22'),(11000023,'W-Space #23'),(11000024,'W-Space #24'),(11000025,'W-Space #25'),(11000026,'W-Space #26'),(11000027,'W-Space #27'),(11000028,'W-Space #28'),(11000029,'W-Space #29'),(11000003,'W-Space #3'),(11000030,'W-Space #30'),(11000004,'W-Space #4'),(11000005,'W-Space #5'),(11000006,'W-Space #6'),(11000007,'W-Space #7'),(11000008,'W-Space #8'),(11000009,'W-Space #9'),(10000006,'Wicked Creek');

UNLOCK TABLES;



--

-- Table structure for table `ccp_systems`

