--
-- Table structure for table `chotomouskey`
--

CREATE TABLE `chotomouskey` (
  `stmtid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `statement` varchar(300) NOT NULL,
  `nodeid` int(10) unsigned NOT NULL,
  `parentid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`stmtid`),
  KEY `FK_chotomouskey_taxa` (`tid`),
  CONSTRAINT `FK_chotomouskey_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`tid`)
) ENGINE=InnoDB;


--
-- Table structure for table `fmprojectcategories`
--

CREATE TABLE `fmprojectcategories` (
  `projcatid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL,
  `categoryname` varchar(150) NOT NULL,
  `managers` varchar(100) DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL,
  `parentpid` int(11) DEFAULT NULL,
  `occurrencesearch` int(11) DEFAULT 0,
  `ispublic` int(11) DEFAULT 1,
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`projcatid`),
  KEY `FK_fmprojcat_pid_idx` (`pid`),
  CONSTRAINT `FK_fmprojcat_pid` FOREIGN KEY (`pid`) REFERENCES `fmprojects` (`pid`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB;


--
-- Table structure for table `imageprojectlink`
--

CREATE TABLE `imageprojectlink` (
  `imgid` int(10) unsigned NOT NULL,
  `imgprojid` int(11) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`imgid`,`imgprojid`),
  KEY `FK_imageprojlink_imgprojid_idx` (`imgprojid`),
  CONSTRAINT `FK_imageprojectlink_imgid` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_imageprojlink_imgprojid` FOREIGN KEY (`imgprojid`) REFERENCES `imageprojects` (`imgprojid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


--
-- Table structure for table `imageprojects`
--

CREATE TABLE `imageprojects` (
  `imgprojid` int(11) NOT NULL AUTO_INCREMENT,
  `projectname` varchar(75) NOT NULL,
  `managers` varchar(150) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `projectType` varchar(45) DEFAULT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `ispublic` int(11) NOT NULL DEFAULT 1,
  `notes` varchar(250) DEFAULT NULL,
  `uidcreated` int(11) unsigned DEFAULT NULL,
  `sortsequence` int(11) DEFAULT 50,
  `initialtimestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`imgprojid`),
  KEY `FK_imageproject_collid_idx` (`collid`),
  KEY `FK_imageproject_uid_idx` (`uidcreated`),
  CONSTRAINT `FK_imageproject_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_imageproject_uid` FOREIGN KEY (`uidcreated`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;


--
-- Table structure for table `omoccurrencetypes`
--

CREATE TABLE `omoccurrencetypes` (
  `occurtypeid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned DEFAULT NULL,
  `typestatus` varchar(45) DEFAULT NULL,
  `typeDesignationType` varchar(45) DEFAULT NULL,
  `typeDesignatedBy` varchar(45) DEFAULT NULL,
  `scientificName` varchar(250) DEFAULT NULL,
  `scientificNameAuthorship` varchar(45) DEFAULT NULL,
  `tidinterpreted` int(10) unsigned DEFAULT NULL,
  `basionym` varchar(250) DEFAULT NULL,
  `refid` int(11) DEFAULT NULL,
  `bibliographicCitation` varchar(250) DEFAULT NULL,
  `dynamicProperties` varchar(250) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`occurtypeid`),
  KEY `FK_occurtype_occid_idx` (`occid`),
  KEY `FK_occurtype_refid_idx` (`refid`),
  KEY `FK_occurtype_tid_idx` (`tidinterpreted`),
  CONSTRAINT `FK_occurtype_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_occurtype_refid` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_occurtype_tid` FOREIGN KEY (`tidinterpreted`) REFERENCES `taxa` (`tid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;


--
-- Table structure for table `omoccurresource`
--

CREATE TABLE `omoccurresource` (
  `resourceID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `reourceTitle` varchar(45) NOT NULL,
  `resourceType` varchar(45) NOT NULL,
  `uri` varchar(250) NOT NULL,
  `source` varchar(45) DEFAULT NULL,
  `resourceIdentifier` varchar(45) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned DEFAULT NULL,
  `createdUid` int(10) unsigned DEFAULT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`resourceID`),
  KEY `FK_omoccurresource_occid_idx` (`occid`),
  KEY `FK_omoccurresource_modUid_idx` (`modifiedUid`),
  KEY `FK_omoccurresource_createdUid_idx` (`createdUid`),
  CONSTRAINT `FK_omoccurresource_createdUid` FOREIGN KEY (`createdUid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurresource_modUid` FOREIGN KEY (`modifiedUid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurresource_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


--
-- Table structure for table `taxanestedtree`
--

CREATE TABLE `taxanestedtree` (
  `tid` int(10) unsigned NOT NULL,
  `taxauthid` int(10) unsigned NOT NULL,
  `leftindex` int(10) unsigned NOT NULL,
  `rightindex` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`tid`,`taxauthid`),
  KEY `leftindex` (`leftindex`),
  KEY `rightindex` (`rightindex`),
  KEY `FK_tnt_taxa` (`tid`),
  KEY `FK_tnt_taxauth` (`taxauthid`),
  CONSTRAINT `FK_tnt_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`tid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tnt_taxauth` FOREIGN KEY (`taxauthid`) REFERENCES `taxauthority` (`taxauthid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


--
-- Table structure for table `taxaprofilepubdesclink`
--

CREATE TABLE `taxaprofilepubdesclink` (
  `tdbid` int(10) unsigned NOT NULL,
  `tppid` int(11) NOT NULL,
  `caption` varchar(45) DEFAULT NULL,
  `editornotes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`tdbid`,`tppid`),
  KEY `FK_tppubdesclink_id_idx` (`tppid`),
  CONSTRAINT `FK_tppubdesclink_id` FOREIGN KEY (`tppid`) REFERENCES `taxaprofilepubs` (`tppid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tppubdesclink_tdbid` FOREIGN KEY (`tdbid`) REFERENCES `taxadescrblock` (`tdbid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


--
-- Table structure for table `taxaprofilepubimagelink`
--

CREATE TABLE `taxaprofilepubimagelink` (
  `imgid` int(10) unsigned NOT NULL,
  `tppid` int(11) NOT NULL,
  `caption` varchar(45) DEFAULT NULL,
  `editornotes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`imgid`,`tppid`),
  KEY `FK_tppubimagelink_id_idx` (`tppid`),
  CONSTRAINT `FK_tppubimagelink_id` FOREIGN KEY (`tppid`) REFERENCES `taxaprofilepubs` (`tppid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tppubimagelink_imgid` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


--
-- Table structure for table `taxaprofilepubmaplink`
--

CREATE TABLE `taxaprofilepubmaplink` (
  `mid` int(10) unsigned NOT NULL,
  `tppid` int(11) NOT NULL,
  `caption` varchar(45) DEFAULT NULL,
  `editornotes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`mid`,`tppid`),
  KEY `FK_tppubmaplink_id_idx` (`tppid`),
  CONSTRAINT `FK_tppubmaplink_id` FOREIGN KEY (`tppid`) REFERENCES `taxaprofilepubs` (`tppid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tppubmaplink_tdbid` FOREIGN KEY (`mid`) REFERENCES `taxamaps` (`mid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


--
-- Table structure for table `taxaprofilepubs`
--

CREATE TABLE `taxaprofilepubs` (
  `tppid` int(11) NOT NULL AUTO_INCREMENT,
  `pubtitle` varchar(150) NOT NULL,
  `authors` varchar(150) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `abstract` text DEFAULT NULL,
  `uidowner` int(10) unsigned DEFAULT NULL,
  `externalurl` varchar(250) DEFAULT NULL,
  `rights` varchar(250) DEFAULT NULL,
  `usageterm` varchar(250) DEFAULT NULL,
  `accessrights` varchar(250) DEFAULT NULL,
  `ispublic` int(11) DEFAULT NULL,
  `inclusive` int(11) DEFAULT NULL,
  `dynamicProperties` text DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`tppid`),
  KEY `FK_taxaprofilepubs_uid_idx` (`uidowner`),
  KEY `INDEX_taxaprofilepubs_title` (`pubtitle`),
  CONSTRAINT `FK_taxaprofilepubs_uid` FOREIGN KEY (`uidowner`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

