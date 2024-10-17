INSERT INTO `schemaversion` (versionnumber) values ("3.2");

ALTER TABLE `omoccurpaleogts`
  ADD COLUMN `myaStart` FLOAT NULL DEFAULT NULL AFTER `rankname`,
  ADD COLUMN `myaEnd` FLOAT NULL DEFAULT NULL AFTER `myaStart`,
  ADD COLUMN `errorRange` FLOAT NULL DEFAULT NULL AFTER `myaEnd`,
  ADD COLUMN `colorCode` VARCHAR(10) NULL DEFAULT NULL AFTER `errorRange`;

#reset the values within omoccurpaleogts table 
TRUNCATE omoccurpaleogts;

INSERT INTO `omoccurpaleogts` VALUES
(1,'Precambrian',10,'superera',4567,538.8,NULL,'#F74370',NULL),
(2,'Phanerozoic',20,'eon',538.8,0,NULL,'#9AD9DD',NULL),
(3,'Proterozoic',20,'eon',2500,538.8,NULL,'#F73563',1),
(4,'Archean',20,'eon',4031,2500,NULL,'#F0047F',1),
(5,'Hadean',20,'eon',4567,4031,NULL,'#AE027E',1),
(6,'Cenozoic',30,'era',66,0,NULL,'#F2F91D',2),
(7,'Mesozoic',30,'era',251.902,66,NULL,'#67C5CA',2),
(8,'Paleozoic',30,'era',538.8,251.902,NULL,'#99C08D',2),
(9,'Neoproterozoic',30,'era',1000,538.8,NULL,'#FEB342',3),
(10,'Mesoproterozoic',30,'era',1600,1000,NULL,'#FDB462',3),
(11,'Paleoproterozoic',30,'era',2500,1600,NULL,'#F74370',3),
(12,'Neoarchean',30,'era',2800,2500,NULL,'#F99BC1',4),
(13,'Mesoarchean',30,'era',3200,2800,NULL,'#F768A9',4),
(14,'Paleoarchean',30,'era',3600,3200,NULL,'#F4449F',4),
(15,'Eoarchean',30,'era',4031,3600,NULL,'#DA037F',4),
(16,'Quaternary',40,'period',2.58,0,NULL,'#F9F97F',6),
(17,'Neogene',40,'period',23.03,2.58,NULL,'#FFE619',6),
(18,'Paleogene',40,'period',66,23.03,NULL,'#FD9A52',6),
(19,'Cretaceous',40,'period',145,66,NULL,'#7FC64E',7),
(20,'Jurassic',40,'period',201.4,145,NULL,'#34B2C9',7),
(21,'Triassic',40,'period',251.902,201.4,NULL,'#812B92',7),
(22,'Permian',40,'period',298.9,251.902,NULL,'#F04028',8),
(23,'Carboniferous',40,'period',358.9,298.9,NULL,'#67A599',8),
(24,'Devonian',40,'period',419.2,358.9,NULL,'#CB8C37',8),
(25,'Silurian',40,'period',443.8,419.2,NULL,'#B3E1B6',8),
(26,'Ordovician',40,'period',485.4,443.8,NULL,'#009270',8),
(27,'Cambrian',40,'period',538.8,485.4,NULL,'#7FA056',8),
(28,'Ediacaran',40,'period',635,538.8,NULL,'#FED96A',9),
(29,'Cryogenian',40,'period',720,635,NULL,'',9),
(30,'Tonian',40,'period',1000,720,NULL,'#FEBF4E',9),
(31,'Stenian',40,'period',1200,1000,NULL,'#FED99A',10),
(32,'Ectasian',40,'period',1400,1200,NULL,'#FDCC8A',10),
(33,'Calymmian',40,'period',1600,1400,NULL,'#FDC07A',10),
(34,'Statherian',40,'period',1800,1600,NULL,'#F875A7',11),
(35,'Orosirian',40,'period',2050,1800,NULL,'#F76898',11),
(36,'Rhyacian',40,'period',2300,2050,NULL,'#F75B89',11),
(37,'Siderian',40,'period',2500,2300,NULL,'#F74F7C',11),
(38,'Holocene',50,'epoch',0.0117,0,NULL,'#FEEBD2',16),
(39,'Pleistocene',50,'epoch',2.58,0.0117,NULL,'#FFEFAF',16),
(40,'Pliocene',50,'epoch',5.333,2.58,NULL,'#FFFF99',17),
(41,'Miocene',50,'epoch',23.03,5.333,NULL,'#FFFF00',17),
(42,'Oligocene',50,'epoch',33.9,23.03,NULL,'#FEC07A',18),
(43,'Eocene',50,'epoch',56,33.9,NULL,'#FDB46C',18),
(44,'Paleocene',50,'epoch',66,56,NULL,'#FDA75F',18),
(45,'Upper Cretaceous',50,'epoch',100.5,66,NULL,'',19),
(46,'Lower Cretaceous',50,'epoch',145,100.5,NULL,'',19),
(47,'Upper Jurassic',50,'epoch',161.5,145,NULL,'',20),
(48,'Middle Jurassic',50,'epoch',174.7,161.5,NULL,'',20),
(49,'Lower Jurassic',50,'epoch',201.4,174.7,NULL,'',20),
(50,'Upper Triassic',50,'epoch',237,201.4,NULL,'',21),
(51,'Middle Triassic',50,'epoch',247.2,237,NULL,'',21),
(52,'Lower Triassic',50,'epoch',251.902,247.2,NULL,'',21),
(53,'Lopingian',50,'epoch',259.51,251.902,NULL,'#FBA794',22),
(54,'Guadalupian',50,'epoch',273.01,259.51,NULL,'#FB745C',22),
(55,'Cisuralian',50,'epoch',298.9,273.01,NULL,'#EF5845',22),
(56,'Upper Pennsylvanian',50,'epoch',307,298.9,NULL,'',23),
(57,'Middle Pennsylvanian',50,'epoch',315.2,307,NULL,'',23),
(58,'Lower Pennsylvanian',50,'epoch',323.2,315.2,NULL,'',23),
(59,'Upper Mississippian',50,'epoch',330.9,323.2,NULL,'',23),
(60,'Middle Mississippian',50,'epoch',346.7,330.9,NULL,'',23),
(61,'Lower Mississippian',50,'epoch',358.9,346.7,NULL,'',23),
(62,'Upper Devonian',50,'epoch',382.7,358.9,NULL,'',24),
(63,'Middle Devonian',50,'epoch',393.3,382.7,NULL,'',24),
(64,'Lower Devonian',50,'epoch',419.2,393.3,NULL,'',24),
(65,'Pridoli',50,'epoch',423,419.2,NULL,'#E6F5E1',25),
(66,'Ludlow',50,'epoch',427.4,423,NULL,'#BFE6CF',25),
(67,'Wenlock',50,'epoch',433.4,427.4,NULL,'#B3E1C2',25),
(68,'Llandovery',50,'epoch',443.8,433.4,NULL,'#99D7B3',25),
(69,'Upper Ordovician',50,'epoch',458.4,443.8,NULL,'',26),
(70,'Middle Ordovician',50,'epoch',470,458.4,NULL,'',26),
(71,'Lower Ordovician',50,'epoch',485.4,470,NULL,'',26),
(72,'Furongian',50,'epoch',497,485.4,NULL,'',27),
(73,'Miaolingian',50,'epoch',509,497,NULL,'',27),
(74,'Cambrian Series 2',50,'epoch',521,509,NULL,'',27),
(75,'Terreneuvian',50,'epoch',538.8,521,NULL,'',27),
(76,'Meghalayan',60,'age',0.0042,0,NULL,'',38),
(77,'Northgrippian',60,'age',0.0082,0.0042,NULL,'',38),
(78,'Greenlandian',60,'age',0.0117,0.0082,NULL,'',38),
(79,'Upper Pleistocene',60,'age',0.129,0.0117,NULL,'',39),
(80,'Chibanian',60,'age',0.774,0.129,NULL,'',39),
(81,'Calabrian',60,'age',1.8,0.774,NULL,'#FFF2BA',39),
(82,'Gelasian',60,'age',2.58,1.8,NULL,'#FFEDB3',39),
(83,'Piacenzian',60,'age',3.6,2.58,NULL,'#FFFFBF',40),
(84,'Zanclean',60,'age',5.333,3.6,NULL,'#FFFFB3',40),
(85,'Messinian',60,'age',7.246,5.333,NULL,'#FFFF73',41),
(86,'Tortonian',60,'age',11.63,7.246,NULL,'#FFFF66',41),
(87,'Serravallian',60,'age',13.82,11.63,NULL,'#FFFF59',41),
(88,'Langhian',60,'age',15.98,13.82,NULL,'#FFFF4D',41),
(89,'Burdigalian',60,'age',20.44,15.98,NULL,'#FFFF41',41),
(90,'Aquitanian',60,'age',23.03,20.44,NULL,'#FFFF33',41),
(91,'Chattian',60,'age',27.82,23.03,NULL,'#FEE6AA',42),
(92,'Rupelian',60,'age',33.9,27.82,NULL,'#FED99A',42),
(93,'Priabonian',60,'age',37.71,33.9,NULL,'#FDCDA1',43),
(94,'Bartonian',60,'age',41.2,37.71,NULL,'#FDC091',43),
(95,'Lutetian',60,'age',47.8,41.2,NULL,'#FDB482',43),
(96,'Ypresian',60,'age',56,47.8,NULL,'#FCA773',43),
(97,'Thanetian',60,'age',59.2,56,NULL,'#FDBF6F',44),
(98,'Selandian',60,'age',61.6,59.2,NULL,'#FEBF65',44),
(99,'Danian',60,'age',66,61.6,NULL,'#FDB462',44),
(100,'Maastrichtian',60,'age',72.1,66,NULL,'#F2FA8C',45),
(101,'Campanian',60,'age',83.6,72.1,NULL,'#E6F47F',45),
(102,'Santonian',60,'age',86.3,83.6,NULL,'#D9EF74',45),
(103,'Coniacian',60,'age',89.8,86.3,NULL,'#CCE968',45),
(104,'Turonian',60,'age',93.9,89.8,NULL,'#BFE35D',45),
(105,'Cenomanian',60,'age',100.5,93.9,NULL,'#B3DE53',45),
(106,'Albian',60,'age',113,100.5,NULL,'#CCEA97',46),
(107,'Aptian',60,'age',121.4,113,NULL,'#BFE48A',46),
(108,'Barremian',60,'age',125.77,121.4,NULL,'#B3DF7F',46),
(109,'Hauterivian',60,'age',132.6,125.77,NULL,'#A6D975',46),
(110,'Valanginian',60,'age',139.8,132.6,NULL,'#99D36A',46),
(111,'Berriasian',60,'age',145,139.8,NULL,'#8CCD60',46),
(112,'Tithonian',60,'age',149.2,145,NULL,'#D9F1F7',47),
(113,'Kimmeridgian',60,'age',154.8,149.2,NULL,'#CCECF4',47),
(114,'Oxfordian',60,'age',161.5,154.8,NULL,'#BFE7F1',47),
(115,'Callovian',60,'age',165.3,161.5,NULL,'#BFE7E5',48),
(116,'Bathonian',60,'age',168.2,165.3,NULL,'#B3E2E3',48),
(117,'Bajocian',60,'age',170.9,168.2,NULL,'#A6DDE0',48),
(118,'Aalenian',60,'age',174.7,170.9,NULL,'#9AD9DD',48),
(119,'Toarcian',60,'age',184.2,174.7,NULL,'#99CEE3',49),
(120,'Pliensbachian',60,'age',192.9,184.2,NULL,'#80C5DD',49),
(121,'Sinemurian',60,'age',199.5,192.9,NULL,'#67BCD8',49),
(122,'Hettangian',60,'age',201.4,199.5,NULL,'#4EB3D3',49),
(123,'Rhaetian',60,'age',208.5,201.4,NULL,'#E3B9DB',50),
(124,'Norian',60,'age',227,208.5,NULL,'#D6AAD3',50),
(125,'Carnian',60,'age',237,227,NULL,'#C99BCB',50),
(126,'Ladinian',60,'age',242,237,NULL,'#C983BF',51),
(127,'Anisian',60,'age',247.2,242,NULL,'#BC75B7',51),
(128,'Olenekian',60,'age',251.2,247.2,NULL,'#B051A5',52),
(129,'Induan',60,'age',251.902,251.2,NULL,'#A4469F',52),
(130,'Changhsingian',60,'age',254.14,251.902,NULL,'#FCC0B2',53),
(131,'Wuchiapingian',60,'age',259.51,254.14,NULL,'#FCB4A2',53),
(132,'Capitanian',60,'age',264.28,259.51,NULL,'#FB9A85',54),
(133,'Wordian',60,'age',266.9,264.28,NULL,'#FB8D76',54),
(134,'Roadian',60,'age',273.01,266.9,NULL,'#FB8069',54),
(135,'Kungurian',60,'age',283.5,273.01,NULL,'#E38776',55),
(136,'Artinskian',60,'age',290.1,283.5,NULL,'#E37B68',55),
(137,'Sakmarian',60,'age',293.51,290.1,NULL,'#E36F5C',55),
(138,'Asselian',60,'age',298.9,293.51,NULL,'#E36350',55),
(139,'Gzhelian',60,'age',303.7,298.9,NULL,'',56),
(140,'Kasimovian',60,'age',307,303.7,NULL,'',56),
(141,'Moscovian',60,'age',315.2,307,NULL,'',57),
(142,'Bashkirian',60,'age',323.2,315.2,NULL,'',58),
(143,'Serpukhovian',60,'age',330.9,323.2,NULL,'',59),
(144,'Visean',60,'age',346.7,330.9,NULL,'',60),
(145,'Tournaisian',60,'age',358.9,346.7,NULL,'',61),
(146,'Famennian',60,'age',372.2,358.9,NULL,'#F2EDB3',62),
(147,'Frasnian',60,'age',382.7,372.2,NULL,'#F2EDAD',62),
(148,'Givetian',60,'age',387.7,382.7,NULL,'#F1E185',63),
(149,'Eifelian',60,'age',393.3,387.7,NULL,'#F1D576',63),
(150,'Emsian',60,'age',407.6,393.3,NULL,'#E5D075',64),
(151,'Pragian',60,'age',410.8,407.6,NULL,'#E5C468',64),
(152,'Lochkovian',60,'age',419.2,410.8,NULL,'#E5B75A',64),
(153,'Ludfordian',60,'age',425.6,423,NULL,'#D9F0DF',66),
(154,'Gorstian',60,'age',427.4,425.6,NULL,'#CCECDD',66),
(155,'Homerian',60,'age',430.5,427.4,NULL,'#CCEBD1',67),
(156,'Sheinwoodian',60,'age',433.4,430.5,NULL,'#BFE6C3',67),
(157,'Telychian',60,'age',438.5,433.4,NULL,'#BFE6CF',68),
(158,'Aeronian',60,'age',440.8,438.5,NULL,'#B3E1C2',68),
(159,'Rhuddanian',60,'age',443.8,440.8,NULL,'#A6DCB5',68),
(160,'Hirnantian',60,'age',445.2,443.8,NULL,'#A6DBAB',69),
(161,'Katian',60,'age',453,445.2,NULL,'#99D69F',69),
(162,'Sandbian',60,'age',458.4,453,NULL,'#8CD094',69),
(163,'Darriwilian',60,'age',467.3,458.4,NULL,'#74C69C',70),
(164,'Dapingian',60,'age',470,467.3,NULL,'#66C092',70),
(165,'Floian',60,'age',477.7,470,NULL,'#41B087',71),
(166,'Tremadocian',60,'age',485.4,477.7,NULL,'#33A97E',71),
(167,'Cambrian Stage 10',60,'age',489.5,485.4,NULL,'',72),
(168,'Jiangshanian',60,'age',494,489.5,NULL,'',72),
(169,'Paibian',60,'age',497,494,NULL,'',72),
(170,'Guzhangian',60,'age',500.5,497,NULL,'',73),
(171,'Drumian',60,'age',504.5,500.5,NULL,'',73),
(172,'Wuliuan',60,'age',509,504.5,NULL,'',73),
(173,'Cambrian Stage 4',60,'age',514,509,NULL,'',74),
(174,'Cambrian Stage 3',60,'age',521,514,NULL,'',74),
(175,'Cambrian Stage 2',60,'age',529,521,NULL,'',75),
(176,'Fortunian',60,'age',538.8,529,NULL,'',75);

