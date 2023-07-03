
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `actionrequest`
--

DROP TABLE IF EXISTS `actionrequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actionrequest` (
  `actionrequestid` bigint(20) NOT NULL AUTO_INCREMENT,
  `fk` int(11) NOT NULL,
  `tablename` varchar(255) DEFAULT NULL,
  `requesttype` varchar(30) NOT NULL,
  `uid_requestor` int(10) unsigned NOT NULL,
  `requestdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `requestremarks` varchar(900) DEFAULT NULL,
  `priority` int(11) DEFAULT NULL,
  `uid_fullfillor` int(10) unsigned NOT NULL,
  `state` varchar(12) DEFAULT NULL,
  `resolution` varchar(12) DEFAULT NULL,
  `statesetdate` datetime DEFAULT NULL,
  `resolutionremarks` varchar(900) DEFAULT NULL,
  PRIMARY KEY (`actionrequestid`),
  KEY `FK_actionreq_uid1_idx` (`uid_requestor`),
  KEY `FK_actionreq_uid2_idx` (`uid_fullfillor`),
  KEY `FK_actionreq_type_idx` (`requesttype`),
  CONSTRAINT `FK_actionreq_uid1` FOREIGN KEY (`uid_requestor`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_actionreq_uid2` FOREIGN KEY (`uid_fullfillor`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_actionreq_type` FOREIGN KEY (`requesttype`) REFERENCES `actionrequesttype` (`requesttype`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `actionrequesttype`
--

DROP TABLE IF EXISTS `actionrequesttype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actionrequesttype` (
  `requesttype` varchar(30) NOT NULL,
  `context` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`requesttype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `adminlanguages`
--

DROP TABLE IF EXISTS `adminlanguages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adminlanguages` (
  `langid` int(11) NOT NULL AUTO_INCREMENT,
  `langname` varchar(45) NOT NULL,
  `iso639_1` varchar(10) DEFAULT NULL,
  `iso639_2` varchar(10) DEFAULT NULL,
  `notes` varchar(45) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`langid`),
  UNIQUE KEY `index_langname_unique` (`langname`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `adminstats`
--

DROP TABLE IF EXISTS `adminstats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adminstats` (
  `idadminstats` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(45) NOT NULL,
  `statname` varchar(45) NOT NULL,
  `statvalue` int(11) DEFAULT NULL,
  `statpercentage` int(11) DEFAULT NULL,
  `dynamicProperties` text,
  `groupid` int(11) NOT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  `note` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idadminstats`),
  KEY `FK_adminstats_collid_idx` (`collid`),
  KEY `FK_adminstats_uid_idx` (`uid`),
  KEY `Index_adminstats_ts` (`initialtimestamp`),
  KEY `Index_category` (`category`),
  CONSTRAINT `FK_adminstats_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_adminstats_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentlinks`
--

DROP TABLE IF EXISTS `agentlinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentlinks` (
  `agentlinksid` bigint(20) NOT NULL AUTO_INCREMENT,
  `agentid` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `link` varchar(900) DEFAULT NULL,
  `isprimarytopicof` tinyint(1) NOT NULL DEFAULT '1',
  `text` varchar(50) DEFAULT NULL,
  `timestampcreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdbyuid` int(11) NOT NULL,
  `datelastmodified` datetime DEFAULT NULL,
  `lastmodifiedbyuid` int(11) DEFAULT NULL,
  PRIMARY KEY (`agentlinksid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentnames`
--

DROP TABLE IF EXISTS `agentnames`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentnames` (
  `agentnamesid` bigint(20) NOT NULL AUTO_INCREMENT,
  `agentid` int(11) NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT 'Full Name',
  `name` varchar(255) DEFAULT NULL,
  `language` varchar(6) DEFAULT 'en_us',
  `timestampcreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdbyuid` int(11) DEFAULT NULL,
  `datelastmodified` datetime DEFAULT NULL,
  `lastmodifiedbyuid` int(11) DEFAULT NULL,
  PRIMARY KEY (`agentnamesid`),
  UNIQUE KEY `agentid` (`agentid`,`type`,`name`),
  KEY `type` (`type`),
  FULLTEXT KEY `ft_collectorname` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentnumberpattern`
--

DROP TABLE IF EXISTS `agentnumberpattern`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentnumberpattern` (
  `agentnumberpatternid` bigint(20) NOT NULL AUTO_INCREMENT,
  `agentid` bigint(20) NOT NULL,
  `numbertype` varchar(50) DEFAULT 'Collector number',
  `numberpattern` varchar(255) DEFAULT NULL,
  `numberpatterndescription` varchar(900) DEFAULT NULL,
  `startyear` int(11) DEFAULT NULL,
  `endyear` int(11) DEFAULT NULL,
  `integerincrement` int(11) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`agentnumberpatternid`),
  KEY `agentid` (`agentid`),
  CONSTRAINT `agentnumberpattern_ibfk_1` FOREIGN KEY (`agentid`) REFERENCES `agents` (`agentid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentrelations`
--

DROP TABLE IF EXISTS `agentrelations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentrelations` (
  `agentrelationsid` bigint(20) NOT NULL AUTO_INCREMENT,
  `fromagentid` bigint(20) NOT NULL,
  `toagentid` bigint(20) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `notes` varchar(900) DEFAULT NULL,
  `timestampcreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdbyuid` int(11) DEFAULT NULL,
  `datelastmodified` datetime DEFAULT NULL,
  `lastmodifiedbyuid` int(11) DEFAULT NULL,
  PRIMARY KEY (`agentrelationsid`),
  KEY `fromagentid` (`fromagentid`),
  KEY `toagentid` (`toagentid`),
  KEY `relationship` (`relationship`),
  CONSTRAINT `agentrelations_ibfk_1` FOREIGN KEY (`fromagentid`) REFERENCES `agents` (`agentid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `agentrelations_ibfk_2` FOREIGN KEY (`toagentid`) REFERENCES `agents` (`agentid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `agentrelations_ibfk_3` FOREIGN KEY (`relationship`) REFERENCES `ctrelationshiptypes` (`relationship`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agents`
--

DROP TABLE IF EXISTS `agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agents` (
  `agentid` bigint(20) NOT NULL AUTO_INCREMENT,
  `familyname` varchar(45) NOT NULL,
  `firstname` varchar(45) DEFAULT NULL,
  `middlename` varchar(45) DEFAULT NULL,
  `startyearactive` int(11) DEFAULT NULL,
  `endyearactive` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT '10',
  `guid` varchar(900) DEFAULT NULL,
  `preferredrecbyid` bigint(20) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uuid` char(43) DEFAULT NULL,
  `biography` text,
  `taxonomicgroups` varchar(900) DEFAULT NULL,
  `collectionsat` varchar(900) DEFAULT NULL,
  `curated` tinyint(1) DEFAULT '0',
  `nototherwisespecified` tinyint(1) DEFAULT '0',
  `type` enum('Individual','Team','Organization') DEFAULT NULL,
  `prefix` varchar(32) DEFAULT NULL,
  `suffix` varchar(32) DEFAULT NULL,
  `namestring` text,
  `mbox_sha1sum` char(40) DEFAULT NULL,
  `yearofbirth` int(11) DEFAULT NULL,
  `yearofbirthmodifier` varchar(12) DEFAULT '',
  `yearofdeath` int(11) DEFAULT NULL,
  `yearofdeathmodifier` varchar(12) DEFAULT '',
  `living` enum('Y','N','?') NOT NULL DEFAULT '?',
  `datelastmodified` datetime DEFAULT NULL,
  `lastmodifiedbyuid` int(11) DEFAULT NULL,
  PRIMARY KEY (`agentid`),
  KEY `firstname` (`firstname`),
  KEY `FK_preferred_recby` (`preferredrecbyid`),
  CONSTRAINT `FK_preferred_recby` FOREIGN KEY (`preferredrecbyid`) REFERENCES `agents` (`agentid`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentsfulltext`
--

DROP TABLE IF EXISTS `agentsfulltext`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentsfulltext` (
  `agentsfulltextid` bigint(20) NOT NULL AUTO_INCREMENT,
  `agentid` int(11) NOT NULL,
  `biography` text,
  `taxonomicgroups` text,
  `collectionsat` text,
  `notes` text,
  `name` text,
  PRIMARY KEY (`agentsfulltextid`),
  FULLTEXT KEY `ft_collectorbio` (`biography`,`taxonomicgroups`,`collectionsat`,`notes`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentteams`
--

DROP TABLE IF EXISTS `agentteams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentteams` (
  `agentteamid` bigint(20) NOT NULL AUTO_INCREMENT,
  `teamagentid` bigint(20) NOT NULL,
  `memberagentid` bigint(20) NOT NULL,
  `ordinal` int(11) DEFAULT NULL,
  PRIMARY KEY (`agentteamid`),
  KEY `teamagentid` (`teamagentid`),
  KEY `memberagentid` (`memberagentid`),
  CONSTRAINT `agentteams_ibfk_1` FOREIGN KEY (`teamagentid`) REFERENCES `agents` (`agentid`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `agentteams_ibfk_2` FOREIGN KEY (`memberagentid`) REFERENCES `agents` (`agentid`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chotomouskey`
--

DROP TABLE IF EXISTS `chotomouskey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chotomouskey` (
  `stmtid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `statement` varchar(300) NOT NULL,
  `nodeid` int(10) unsigned NOT NULL,
  `parentid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`stmtid`),
  KEY `FK_chotomouskey_taxa` (`tid`),
  CONSTRAINT `FK_chotomouskey_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `configpage`
--

DROP TABLE IF EXISTS `configpage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configpage` (
  `configpageid` int(11) NOT NULL AUTO_INCREMENT,
  `pagename` varchar(45) NOT NULL,
  `title` varchar(150) NOT NULL,
  `cssname` varchar(45) DEFAULT NULL,
  `language` varchar(45) NOT NULL DEFAULT 'english',
  `displaymode` int(11) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned NOT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`configpageid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `configpageattributes`
--

DROP TABLE IF EXISTS `configpageattributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configpageattributes` (
  `attributeid` int(11) NOT NULL AUTO_INCREMENT,
  `configpageid` int(11) NOT NULL,
  `objid` varchar(45) DEFAULT NULL,
  `objname` varchar(45) NOT NULL,
  `value` varchar(45) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL COMMENT 'text, submit, div',
  `width` int(11) DEFAULT NULL,
  `top` int(11) DEFAULT NULL,
  `left` int(11) DEFAULT NULL,
  `stylestr` varchar(45) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned NOT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attributeid`),
  KEY `FK_configpageattributes_id_idx` (`configpageid`),
  CONSTRAINT `FK_configpageattributes_id` FOREIGN KEY (`configpageid`) REFERENCES `configpage` (`configpageid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ctnametypes`
--

DROP TABLE IF EXISTS `ctnametypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ctnametypes` (
  `type` varchar(32) NOT NULL,
  PRIMARY KEY (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ctrelationshiptypes`
--

DROP TABLE IF EXISTS `ctrelationshiptypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ctrelationshiptypes` (
  `relationship` varchar(50) NOT NULL,
  `inverse` varchar(50) DEFAULT NULL,
  `collective` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`relationship`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmchecklists`
--

DROP TABLE IF EXISTS `fmchecklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmchecklists` (
  `CLID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL,
  `Title` varchar(150) DEFAULT NULL,
  `Locality` varchar(500) DEFAULT NULL,
  `Publication` varchar(500) DEFAULT NULL,
  `Abstract` text,
  `Authors` varchar(250) DEFAULT NULL,
  `Type` varchar(50) DEFAULT 'static',
  `dynamicsql` varchar(500) DEFAULT NULL,
  `Parent` varchar(50) DEFAULT NULL,
  `parentclid` int(10) unsigned DEFAULT NULL,
  `Notes` varchar(500) DEFAULT NULL,
  `LatCentroid` double(9,6) DEFAULT NULL,
  `LongCentroid` double(9,6) DEFAULT NULL,
  `pointradiusmeters` int(10) unsigned DEFAULT NULL,
  `footprintWKT` text,
  `percenteffort` int(11) DEFAULT NULL,
  `Access` varchar(45) DEFAULT 'private',
  `defaultSettings` varchar(250) DEFAULT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  `SortSequence` int(10) unsigned NOT NULL DEFAULT '50',
  `expiration` int(10) unsigned DEFAULT NULL,
  `DateLastModified` datetime DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CLID`),
  KEY `FK_checklists_uid` (`uid`),
  KEY `name` (`Name`,`Type`) USING BTREE,
  CONSTRAINT `FK_checklists_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmchklstchildren`
--

DROP TABLE IF EXISTS `fmchklstchildren`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmchklstchildren` (
  `clid` int(10) unsigned NOT NULL,
  `clidchild` int(10) unsigned NOT NULL,
  `modifiedUid` int(10) unsigned NOT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`clid`,`clidchild`),
  KEY `FK_fmchklstchild_clid_idx` (`clid`),
  KEY `FK_fmchklstchild_child_idx` (`clidchild`),
  CONSTRAINT `FK_fmchklstchild_clid` FOREIGN KEY (`clid`) REFERENCES `fmchecklists` (`CLID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_fmchklstchild_child` FOREIGN KEY (`clidchild`) REFERENCES `fmchecklists` (`CLID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmchklstcoordinates`
--

DROP TABLE IF EXISTS `fmchklstcoordinates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmchklstcoordinates` (
  `chklstcoordid` int(11) NOT NULL AUTO_INCREMENT,
  `clid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `decimallatitude` double NOT NULL,
  `decimallongitude` double NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`chklstcoordid`),
  UNIQUE KEY `IndexUnique` (`clid`,`tid`,`decimallatitude`,`decimallongitude`),
  KEY `FKchklsttaxalink` (`clid`,`tid`),
  CONSTRAINT `FKchklsttaxalink` FOREIGN KEY (`clid`, `tid`) REFERENCES `fmchklsttaxalink` (`CLID`, `TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmchklstprojlink`
--

DROP TABLE IF EXISTS `fmchklstprojlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmchklstprojlink` (
  `pid` int(10) unsigned NOT NULL,
  `clid` int(10) unsigned NOT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pid`,`clid`),
  KEY `FK_chklst` (`clid`),
  CONSTRAINT `FK_chklstprojlink_clid` FOREIGN KEY (`clid`) REFERENCES `fmchecklists` (`CLID`),
  CONSTRAINT `FK_chklstprojlink_proj` FOREIGN KEY (`pid`) REFERENCES `fmprojects` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmchklsttaxalink`
--

DROP TABLE IF EXISTS `fmchklsttaxalink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmchklsttaxalink` (
  `TID` int(10) unsigned NOT NULL DEFAULT '0',
  `CLID` int(10) unsigned NOT NULL DEFAULT '0',
  `morphospecies` varchar(45) NOT NULL DEFAULT '',
  `familyoverride` varchar(50) DEFAULT NULL,
  `Habitat` varchar(250) DEFAULT NULL,
  `Abundance` varchar(50) DEFAULT NULL,
  `Notes` varchar(2000) DEFAULT NULL,
  `explicitExclude` smallint(6) DEFAULT NULL,
  `source` varchar(250) DEFAULT NULL,
  `Nativity` varchar(50) DEFAULT NULL COMMENT 'native, introducted',
  `Endemic` varchar(45) DEFAULT NULL,
  `invasive` varchar(45) DEFAULT NULL,
  `internalnotes` varchar(250) DEFAULT NULL,
  `dynamicProperties` text,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`TID`,`CLID`,`morphospecies`),
  KEY `FK_chklsttaxalink_cid` (`CLID`),
  CONSTRAINT `FK_chklsttaxalink_cid` FOREIGN KEY (`CLID`) REFERENCES `fmchecklists` (`CLID`),
  CONSTRAINT `FK_chklsttaxalink_tid` FOREIGN KEY (`TID`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmchklsttaxastatus`
--

DROP TABLE IF EXISTS `fmchklsttaxastatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmchklsttaxastatus` (
  `clid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `geographicRange` int(11) NOT NULL DEFAULT '0',
  `populationRank` int(11) NOT NULL DEFAULT '0',
  `abundance` int(11) NOT NULL DEFAULT '0',
  `habitatSpecificity` int(11) NOT NULL DEFAULT '0',
  `intrinsicRarity` int(11) NOT NULL DEFAULT '0',
  `threatImminence` int(11) NOT NULL DEFAULT '0',
  `populationTrends` int(11) NOT NULL DEFAULT '0',
  `nativeStatus` varchar(45) DEFAULT NULL,
  `endemicStatus` int(11) NOT NULL DEFAULT '0',
  `protectedStatus` varchar(45) DEFAULT NULL,
  `localitySecurity` int(11) DEFAULT NULL,
  `localitySecurityReason` varchar(45) DEFAULT NULL,
  `invasiveStatus` varchar(45) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned DEFAULT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`clid`,`tid`),
  KEY `FK_fmchklsttaxastatus_clid_idx` (`clid`,`tid`),
  CONSTRAINT `FK_fmchklsttaxastatus_clidtid` FOREIGN KEY (`clid`, `tid`) REFERENCES `fmchklsttaxalink` (`CLID`, `TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmcltaxacomments`
--

DROP TABLE IF EXISTS `fmcltaxacomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmcltaxacomments` (
  `cltaxacommentsid` int(11) NOT NULL AUTO_INCREMENT,
  `clid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `comment` text NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `ispublic` int(11) NOT NULL DEFAULT '1',
  `parentid` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cltaxacommentsid`),
  KEY `FK_clcomment_users` (`uid`),
  KEY `FK_clcomment_cltaxa` (`clid`,`tid`),
  CONSTRAINT `FK_clcomment_users` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_clcomment_cltaxa` FOREIGN KEY (`clid`, `tid`) REFERENCES `fmchklsttaxalink` (`CLID`, `TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmdynamicchecklists`
--

DROP TABLE IF EXISTS `fmdynamicchecklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmdynamicchecklists` (
  `dynclid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `details` varchar(250) DEFAULT NULL,
  `uid` varchar(45) DEFAULT NULL,
  `type` varchar(45) NOT NULL DEFAULT 'DynamicList',
  `notes` varchar(250) DEFAULT NULL,
  `expiration` datetime NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`dynclid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmdyncltaxalink`
--

DROP TABLE IF EXISTS `fmdyncltaxalink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmdyncltaxalink` (
  `dynclid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`dynclid`,`tid`),
  KEY `FK_dyncltaxalink_taxa` (`tid`),
  CONSTRAINT `FK_dyncltaxalink_dynclid` FOREIGN KEY (`dynclid`) REFERENCES `fmdynamicchecklists` (`dynclid`) ON DELETE CASCADE,
  CONSTRAINT `FK_dyncltaxalink_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmprojectcategories`
--

DROP TABLE IF EXISTS `fmprojectcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmprojectcategories` (
  `projcatid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL,
  `categoryname` varchar(150) NOT NULL,
  `managers` varchar(100) DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL,
  `parentpid` int(11) DEFAULT NULL,
  `occurrencesearch` int(11) DEFAULT '0',
  `ispublic` int(11) DEFAULT '1',
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`projcatid`),
  KEY `FK_fmprojcat_pid_idx` (`pid`),
  CONSTRAINT `FK_fmprojcat_pid` FOREIGN KEY (`pid`) REFERENCES `fmprojects` (`pid`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmprojects`
--

DROP TABLE IF EXISTS `fmprojects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmprojects` (
  `pid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `projname` varchar(45) NOT NULL,
  `displayname` varchar(150) DEFAULT NULL,
  `managers` varchar(150) DEFAULT NULL,
  `briefdescription` varchar(300) DEFAULT NULL,
  `fulldescription` varchar(2000) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `occurrencesearch` int(10) unsigned NOT NULL DEFAULT '0',
  `ispublic` int(10) unsigned NOT NULL DEFAULT '0',
  `parentpid` int(10) unsigned DEFAULT NULL,
  `SortSequence` int(10) unsigned NOT NULL DEFAULT '50',
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pid`),
  KEY `FK_parentpid_proj` (`parentpid`),
  CONSTRAINT `FK_parentpid_proj` FOREIGN KEY (`parentpid`) REFERENCES `fmprojects` (`pid`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmvouchers`
--

DROP TABLE IF EXISTS `fmvouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmvouchers` (
  `TID` int(10) unsigned NOT NULL,
  `CLID` int(10) unsigned NOT NULL,
  `occid` int(10) unsigned NOT NULL,
  `Collector` varchar(100) DEFAULT NULL,
  `editornotes` varchar(50) DEFAULT NULL,
  `Notes` varchar(250) DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`occid`,`CLID`,`TID`),
  KEY `chklst_taxavouchers` (`TID`,`CLID`),
  CONSTRAINT `FK_fmvouchers_occ` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_vouchers_cl` FOREIGN KEY (`TID`, `CLID`) REFERENCES `fmchklsttaxalink` (`TID`, `CLID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geothescontinent`
--

DROP TABLE IF EXISTS `geothescontinent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geothescontinent` (
  `gtcid` int(11) NOT NULL AUTO_INCREMENT,
  `continentterm` varchar(45) NOT NULL,
  `abbreviation` varchar(45) DEFAULT NULL,
  `code` varchar(45) DEFAULT NULL,
  `lookupterm` int(11) NOT NULL DEFAULT '1',
  `acceptedid` int(11) DEFAULT NULL,
  `footprintWKT` text,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gtcid`),
  KEY `FK_geothescontinent_accepted_idx` (`acceptedid`),
  CONSTRAINT `FK_geothescontinent_accepted` FOREIGN KEY (`acceptedid`) REFERENCES `geothescontinent` (`gtcid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geothescountry`
--

DROP TABLE IF EXISTS `geothescountry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geothescountry` (
  `gtcid` int(11) NOT NULL AUTO_INCREMENT,
  `countryterm` varchar(45) NOT NULL,
  `abbreviation` varchar(45) DEFAULT NULL,
  `iso` varchar(2) DEFAULT NULL,
  `iso3` varchar(3) DEFAULT NULL,
  `numcode` int(11) DEFAULT NULL,
  `lookupterm` int(11) NOT NULL DEFAULT '1',
  `acceptedid` int(11) DEFAULT NULL,
  `continentid` int(11) DEFAULT NULL,
  `footprintWKT` text,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gtcid`),
  KEY `FK_geothescountry__idx` (`continentid`),
  KEY `FK_geothescountry_parent_idx` (`acceptedid`),
  CONSTRAINT `FK_geothescountry_accepted` FOREIGN KEY (`acceptedid`) REFERENCES `geothescountry` (`gtcid`),
  CONSTRAINT `FK_geothescountry_gtcid` FOREIGN KEY (`continentid`) REFERENCES `geothescontinent` (`gtcid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geothescounty`
--

DROP TABLE IF EXISTS `geothescounty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geothescounty` (
  `gtcoid` int(11) NOT NULL AUTO_INCREMENT,
  `countyterm` varchar(45) NOT NULL,
  `abbreviation` varchar(45) DEFAULT NULL,
  `code` varchar(45) DEFAULT NULL,
  `lookupterm` int(11) NOT NULL DEFAULT '1',
  `acceptedid` int(11) DEFAULT NULL,
  `stateid` int(11) DEFAULT NULL,
  `footprintWKT` text,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gtcoid`),
  KEY `FK_geothescounty_state_idx` (`stateid`),
  KEY `FK_geothescounty_accepted_idx` (`acceptedid`),
  CONSTRAINT `FK_geothescounty_accepted` FOREIGN KEY (`acceptedid`) REFERENCES `geothescounty` (`gtcoid`),
  CONSTRAINT `FK_geothescounty_state` FOREIGN KEY (`stateid`) REFERENCES `geothesstateprovince` (`gtspid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geothesmunicipality`
--

DROP TABLE IF EXISTS `geothesmunicipality`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geothesmunicipality` (
  `gtmid` int(11) NOT NULL AUTO_INCREMENT,
  `municipalityterm` varchar(45) NOT NULL,
  `abbreviation` varchar(45) DEFAULT NULL,
  `code` varchar(45) DEFAULT NULL,
  `lookupterm` int(11) NOT NULL DEFAULT '1',
  `acceptedid` int(11) DEFAULT NULL,
  `countyid` int(11) DEFAULT NULL,
  `footprintWKT` text,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gtmid`),
  KEY `FK_geothesmunicipality_county_idx` (`countyid`),
  KEY `FK_geothesmunicipality_accepted_idx` (`acceptedid`),
  CONSTRAINT `FK_geothesmunicipality_accepted` FOREIGN KEY (`acceptedid`) REFERENCES `geothesmunicipality` (`gtmid`),
  CONSTRAINT `FK_geothesmunicipality_county` FOREIGN KEY (`countyid`) REFERENCES `geothescounty` (`gtcoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geothesstateprovince`
--

DROP TABLE IF EXISTS `geothesstateprovince`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geothesstateprovince` (
  `gtspid` int(11) NOT NULL AUTO_INCREMENT,
  `stateterm` varchar(45) NOT NULL,
  `abbreviation` varchar(45) DEFAULT NULL,
  `code` varchar(45) DEFAULT NULL,
  `lookupterm` int(11) NOT NULL DEFAULT '1',
  `acceptedid` int(11) DEFAULT NULL,
  `countryid` int(11) DEFAULT NULL,
  `footprintWKT` text,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gtspid`),
  KEY `FK_geothesstate_country_idx` (`countryid`),
  KEY `FK_geothesstate_accepted_idx` (`acceptedid`),
  CONSTRAINT `FK_geothesstate_accepted` FOREIGN KEY (`acceptedid`) REFERENCES `geothesstateprovince` (`gtspid`),
  CONSTRAINT `FK_geothesstate_country` FOREIGN KEY (`countryid`) REFERENCES `geothescountry` (`gtcid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glossary`
--

DROP TABLE IF EXISTS `glossary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `glossary` (
  `glossid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `term` varchar(150) NOT NULL,
  `definition` varchar(600) DEFAULT NULL,
  `language` varchar(45) NOT NULL DEFAULT 'English',
  `source` varchar(45) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`glossid`),
  KEY `Index_term` (`term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glossaryimages`
--

DROP TABLE IF EXISTS `glossaryimages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `glossaryimages` (
  `glimgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `glossid` int(10) unsigned NOT NULL,
  `url` varchar(255) NOT NULL,
  `thumbnailurl` varchar(255) DEFAULT NULL,
  `structures` varchar(150) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`glimgid`),
  KEY `FK_glossaryimages_gloss` (`glossid`),
  CONSTRAINT `FK_glossaryimages_gloss` FOREIGN KEY (`glossid`) REFERENCES `glossary` (`glossid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glossarytaxalink`
--

DROP TABLE IF EXISTS `glossarytaxalink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `glossarytaxalink` (
  `glossgrpid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`glossgrpid`,`tid`),
  KEY `glossarytaxalink_ibfk_1` (`tid`),
  CONSTRAINT `glossarytaxalink_ibfk_1` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `glossarytaxalink_ibfk_2` FOREIGN KEY (`glossgrpid`) REFERENCES `glossarytermlink` (`glossgrpid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glossarytermlink`
--

DROP TABLE IF EXISTS `glossarytermlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `glossarytermlink` (
  `gltlinkid` int(10) NOT NULL AUTO_INCREMENT,
  `glossgrpid` int(10) unsigned NOT NULL,
  `glossid` int(10) unsigned NOT NULL,
  `relationshipType` varchar(45) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gltlinkid`),
  UNIQUE KEY `Unique_termkeys` (`glossgrpid`,`glossid`),
  KEY `glossarytermlink_ibfk_1` (`glossid`),
  CONSTRAINT `glossarytermlink_ibfk_1` FOREIGN KEY (`glossid`) REFERENCES `glossary` (`glossid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `guidimages`
--

DROP TABLE IF EXISTS `guidimages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guidimages` (
  `guid` varchar(45) NOT NULL,
  `imgid` int(10) unsigned DEFAULT NULL,
  `archivestatus` int(3) NOT NULL DEFAULT '0',
  `archiveobj` text,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`guid`),
  UNIQUE KEY `guidimages_imgid_unique` (`imgid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `guidoccurdeterminations`
--

DROP TABLE IF EXISTS `guidoccurdeterminations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guidoccurdeterminations` (
  `guid` varchar(45) NOT NULL,
  `detid` int(10) unsigned DEFAULT NULL,
  `archivestatus` int(3) NOT NULL DEFAULT '0',
  `archiveobj` text,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`guid`),
  UNIQUE KEY `guidoccurdet_detid_unique` (`detid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `guidoccurrences`
--

DROP TABLE IF EXISTS `guidoccurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guidoccurrences` (
  `guid` varchar(45) NOT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `archivestatus` int(3) NOT NULL DEFAULT '0',
  `archiveobj` text,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`guid`),
  UNIQUE KEY `guidoccurrences_occid_unique` (`occid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imageannotations`
--

DROP TABLE IF EXISTS `imageannotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imageannotations` (
  `tid` int(10) unsigned DEFAULT NULL,
  `imgid` int(10) unsigned NOT NULL DEFAULT '0',
  `AnnDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Annotator` varchar(100) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imgid`,`AnnDate`) USING BTREE,
  KEY `TID` (`tid`) USING BTREE,
  CONSTRAINT `FK_resourceannotations_imgid` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`),
  CONSTRAINT `FK_resourceannotations_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imagekeywords`
--

DROP TABLE IF EXISTS `imagekeywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imagekeywords` (
  `imgkeywordid` int(11) NOT NULL AUTO_INCREMENT,
  `imgid` int(10) unsigned NOT NULL,
  `keyword` varchar(45) NOT NULL,
  `uidassignedby` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imgkeywordid`),
  KEY `FK_imagekeywords_imgid_idx` (`imgid`),
  KEY `FK_imagekeyword_uid_idx` (`uidassignedby`),
  KEY `INDEX_imagekeyword` (`keyword`),
  CONSTRAINT `FK_imagekeywords_imgid` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_imagekeyword_uid` FOREIGN KEY (`uidassignedby`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imageprojectlink`
--

DROP TABLE IF EXISTS `imageprojectlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imageprojectlink` (
  `imgid` int(10) unsigned NOT NULL,
  `imgprojid` int(11) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imgid`,`imgprojid`),
  KEY `FK_imageprojlink_imgprojid_idx` (`imgprojid`),
  CONSTRAINT `FK_imageprojectlink_imgid` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_imageprojlink_imgprojid` FOREIGN KEY (`imgprojid`) REFERENCES `imageprojects` (`imgprojid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imageprojects`
--

DROP TABLE IF EXISTS `imageprojects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imageprojects` (
  `imgprojid` int(11) NOT NULL AUTO_INCREMENT,
  `projectname` varchar(75) NOT NULL,
  `managers` varchar(150) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `ispublic` int(11) NOT NULL DEFAULT '1',
  `notes` varchar(250) DEFAULT NULL,
  `uidcreated` int(11) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT '50',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imgprojid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `images`
--

DROP TABLE IF EXISTS `images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `images` (
  `imgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `thumbnailurl` varchar(255) DEFAULT NULL,
  `originalurl` varchar(255) DEFAULT NULL,
  `archiveurl` varchar(255) DEFAULT NULL,
  `photographer` varchar(100) DEFAULT NULL,
  `photographeruid` int(10) unsigned DEFAULT NULL,
  `imagetype` varchar(50) DEFAULT NULL,
  `format` varchar(45) DEFAULT NULL,
  `caption` varchar(100) DEFAULT NULL,
  `owner` varchar(250) DEFAULT NULL,
  `sourceurl` varchar(255) DEFAULT NULL,
  `copyright` varchar(255) DEFAULT NULL,
  `rights` varchar(255) DEFAULT NULL,
  `accessrights` varchar(255) DEFAULT NULL,
  `locality` varchar(250) DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `notes` varchar(350) DEFAULT NULL,
  `anatomy` varchar(100) DEFAULT NULL,
  `username` varchar(45) DEFAULT NULL,
  `sourceIdentifier` varchar(45) DEFAULT NULL,
  `sortsequence` int(10) unsigned NOT NULL DEFAULT '50',
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imgid`) USING BTREE,
  KEY `Index_tid` (`tid`),
  KEY `FK_images_occ` (`occid`),
  KEY `FK_photographeruid` (`photographeruid`),
  CONSTRAINT `FK_images_occ` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`),
  CONSTRAINT `FK_photographeruid` FOREIGN KEY (`photographeruid`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_taxaimagestid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imagetag`
--

DROP TABLE IF EXISTS `imagetag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imagetag` (
  `imagetagid` bigint(20) NOT NULL AUTO_INCREMENT,
  `imgid` int(10) unsigned NOT NULL,
  `keyvalue` varchar(30) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imagetagid`),
  UNIQUE KEY `imgid` (`imgid`,`keyvalue`),
  KEY `keyvalue` (`keyvalue`),
  KEY `FK_imagetag_imgid_idx` (`imgid`),
  CONSTRAINT `FK_imagetag_imgid` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_imagetag_tagkey` FOREIGN KEY (`keyvalue`) REFERENCES `imagetagkey` (`tagkey`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imagetagkey`
--

DROP TABLE IF EXISTS `imagetagkey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imagetagkey` (
  `tagkey` varchar(30) NOT NULL,
  `shortlabel` varchar(30) NOT NULL,
  `description_en` varchar(255) NOT NULL,
  `sortorder` int(11) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tagkey`),
  KEY `sortorder` (`sortorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `institutions`
--

DROP TABLE IF EXISTS `institutions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `institutions` (
  `iid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `InstitutionCode` varchar(45) NOT NULL,
  `InstitutionName` varchar(150) NOT NULL,
  `InstitutionName2` varchar(150) DEFAULT NULL,
  `Address1` varchar(150) DEFAULT NULL,
  `Address2` varchar(150) DEFAULT NULL,
  `City` varchar(45) DEFAULT NULL,
  `StateProvince` varchar(45) DEFAULT NULL,
  `PostalCode` varchar(45) DEFAULT NULL,
  `Country` varchar(45) DEFAULT NULL,
  `Phone` varchar(45) DEFAULT NULL,
  `Contact` varchar(65) DEFAULT NULL,
  `Email` varchar(45) DEFAULT NULL,
  `Url` varchar(250) DEFAULT NULL,
  `Notes` varchar(250) DEFAULT NULL,
  `modifieduid` int(10) unsigned DEFAULT NULL,
  `modifiedTimeStamp` datetime DEFAULT NULL,
  `IntialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`iid`),
  KEY `FK_inst_uid_idx` (`modifieduid`),
  CONSTRAINT `FK_inst_uid` FOREIGN KEY (`modifieduid`) REFERENCES `users` (`uid`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmcharacterlang`
--

DROP TABLE IF EXISTS `kmcharacterlang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmcharacterlang` (
  `cid` int(10) unsigned NOT NULL,
  `charname` varchar(150) NOT NULL,
  `language` varchar(45) NOT NULL,
  `langid` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `helpurl` varchar(500) DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cid`,`langid`) USING BTREE,
  CONSTRAINT `FK_characterlang_1` FOREIGN KEY (`cid`) REFERENCES `kmcharacters` (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmcharacters`
--

DROP TABLE IF EXISTS `kmcharacters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmcharacters` (
  `cid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `charname` varchar(150) NOT NULL,
  `chartype` varchar(2) NOT NULL DEFAULT 'UM',
  `defaultlang` varchar(45) NOT NULL DEFAULT 'English',
  `difficultyrank` smallint(5) unsigned NOT NULL DEFAULT '1',
  `hid` int(10) unsigned DEFAULT NULL,
  `units` varchar(45) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `helpurl` varchar(500) DEFAULT NULL,
  `enteredby` varchar(45) DEFAULT NULL,
  `sortsequence` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cid`),
  KEY `Index_charname` (`charname`),
  KEY `Index_sort` (`sortsequence`),
  KEY `FK_charheading_idx` (`hid`),
  CONSTRAINT `FK_charheading` FOREIGN KEY (`hid`) REFERENCES `kmcharheading` (`hid`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmchardependance`
--

DROP TABLE IF EXISTS `kmchardependance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmchardependance` (
  `CID` int(10) unsigned NOT NULL,
  `CIDDependance` int(10) unsigned NOT NULL,
  `CSDependance` varchar(16) NOT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CSDependance`,`CIDDependance`,`CID`) USING BTREE,
  KEY `FK_chardependance_cid_idx` (`CID`),
  KEY `FK_chardependance_cs_idx` (`CIDDependance`,`CSDependance`),
  CONSTRAINT `FK_chardependance_cid` FOREIGN KEY (`CID`) REFERENCES `kmcharacters` (`cid`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_chardependance_cs` FOREIGN KEY (`CIDDependance`, `CSDependance`) REFERENCES `kmcs` (`cid`, `cs`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmcharheading`
--

DROP TABLE IF EXISTS `kmcharheading`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmcharheading` (
  `hid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `headingname` varchar(255) NOT NULL,
  `language` varchar(45) NOT NULL DEFAULT 'English',
  `langid` int(11) NOT NULL,
  `notes` longtext,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`hid`,`langid`) USING BTREE,
  UNIQUE KEY `unique_kmcharheading` (`headingname`,`langid`),
  KEY `HeadingName` (`headingname`) USING BTREE,
  KEY `FK_kmcharheading_lang_idx` (`langid`),
  CONSTRAINT `FK_kmcharheading_lang` FOREIGN KEY (`langid`) REFERENCES `adminlanguages` (`langid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmchartaxalink`
--

DROP TABLE IF EXISTS `kmchartaxalink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmchartaxalink` (
  `CID` int(10) unsigned NOT NULL DEFAULT '0',
  `TID` int(10) unsigned NOT NULL DEFAULT '0',
  `Status` varchar(50) DEFAULT NULL,
  `Notes` varchar(255) DEFAULT NULL,
  `Relation` varchar(45) NOT NULL DEFAULT 'include',
  `EditabilityInherited` bit(1) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CID`,`TID`),
  KEY `FK_CharTaxaLink-TID` (`TID`),
  CONSTRAINT `FK_chartaxalink_cid` FOREIGN KEY (`CID`) REFERENCES `kmcharacters` (`cid`),
  CONSTRAINT `FK_chartaxalink_tid` FOREIGN KEY (`TID`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmcs`
--

DROP TABLE IF EXISTS `kmcs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmcs` (
  `cid` int(10) unsigned NOT NULL DEFAULT '0',
  `cs` varchar(16) NOT NULL,
  `CharStateName` varchar(255) DEFAULT NULL,
  `Implicit` tinyint(1) NOT NULL DEFAULT '0',
  `Notes` longtext,
  `Description` varchar(255) DEFAULT NULL,
  `IllustrationUrl` varchar(250) DEFAULT NULL,
  `StateID` int(10) unsigned DEFAULT NULL,
  `SortSequence` int(10) unsigned DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `EnteredBy` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`cs`,`cid`),
  KEY `FK_cs_chars` (`cid`),
  CONSTRAINT `FK_cs_chars` FOREIGN KEY (`cid`) REFERENCES `kmcharacters` (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmcsimages`
--

DROP TABLE IF EXISTS `kmcsimages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmcsimages` (
  `csimgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(10) unsigned NOT NULL,
  `cs` varchar(16) NOT NULL,
  `url` varchar(255) NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` varchar(45) NOT NULL DEFAULT '50',
  `username` varchar(45) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`csimgid`),
  KEY `FK_kscsimages_kscs_idx` (`cid`,`cs`),
  CONSTRAINT `FK_kscsimages_kscs` FOREIGN KEY (`cid`, `cs`) REFERENCES `kmcs` (`cid`, `cs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmcslang`
--

DROP TABLE IF EXISTS `kmcslang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmcslang` (
  `cid` int(10) unsigned NOT NULL,
  `cs` varchar(16) NOT NULL,
  `charstatename` varchar(150) NOT NULL,
  `language` varchar(45) NOT NULL,
  `langid` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `intialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cid`,`cs`,`langid`),
  CONSTRAINT `FK_cslang_1` FOREIGN KEY (`cid`, `cs`) REFERENCES `kmcs` (`cid`, `cs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmdescr`
--

DROP TABLE IF EXISTS `kmdescr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmdescr` (
  `TID` int(10) unsigned NOT NULL DEFAULT '0',
  `CID` int(10) unsigned NOT NULL DEFAULT '0',
  `Modifier` varchar(255) DEFAULT NULL,
  `CS` varchar(16) NOT NULL,
  `X` double(15,5) DEFAULT NULL,
  `TXT` longtext,
  `PseudoTrait` int(5) unsigned DEFAULT '0',
  `Frequency` int(5) unsigned NOT NULL DEFAULT '5' COMMENT 'Frequency of occurrence; 1 = rare... 5 = common',
  `Inherited` varchar(50) DEFAULT NULL,
  `Source` varchar(100) DEFAULT NULL,
  `Seq` int(10) DEFAULT NULL,
  `Notes` longtext,
  `DateEntered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`TID`,`CID`,`CS`),
  KEY `CSDescr` (`CID`,`CS`),
  CONSTRAINT `FK_descr_cs` FOREIGN KEY (`CID`, `CS`) REFERENCES `kmcs` (`cid`, `cs`),
  CONSTRAINT `FK_descr_tid` FOREIGN KEY (`TID`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmdescrdeletions`
--

DROP TABLE IF EXISTS `kmdescrdeletions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmdescrdeletions` (
  `TID` int(10) unsigned NOT NULL,
  `CID` int(10) unsigned NOT NULL,
  `CS` varchar(16) NOT NULL,
  `Modifier` varchar(255) DEFAULT NULL,
  `X` double(15,5) DEFAULT NULL,
  `TXT` longtext,
  `Inherited` varchar(50) DEFAULT NULL,
  `Source` varchar(100) DEFAULT NULL,
  `Seq` int(10) unsigned DEFAULT NULL,
  `Notes` longtext,
  `InitialTimeStamp` datetime DEFAULT NULL,
  `DeletedBy` varchar(100) NOT NULL,
  `DeletedTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `PK` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`PK`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lkupcountry`
--

DROP TABLE IF EXISTS `lkupcountry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lkupcountry` (
  `countryId` int(11) NOT NULL AUTO_INCREMENT,
  `countryName` varchar(100) NOT NULL,
  `iso` varchar(2) DEFAULT NULL,
  `iso3` varchar(3) DEFAULT NULL,
  `numcode` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`countryId`),
  UNIQUE KEY `country_unique` (`countryName`),
  KEY `Index_lkupcountry_iso` (`iso`),
  KEY `Index_lkupcountry_iso3` (`iso3`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lkupcounty`
--

DROP TABLE IF EXISTS `lkupcounty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lkupcounty` (
  `countyId` int(11) NOT NULL AUTO_INCREMENT,
  `stateId` int(11) NOT NULL,
  `countyName` varchar(100) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`countyId`),
  UNIQUE KEY `unique_county` (`stateId`,`countyName`),
  KEY `fk_stateprovince` (`stateId`),
  KEY `index_countyname` (`countyName`),
  CONSTRAINT `fk_stateprovince` FOREIGN KEY (`stateId`) REFERENCES `lkupstateprovince` (`stateId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lkupstateprovince`
--

DROP TABLE IF EXISTS `lkupstateprovince`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lkupstateprovince` (
  `stateId` int(11) NOT NULL AUTO_INCREMENT,
  `countryId` int(11) NOT NULL,
  `stateName` varchar(100) NOT NULL,
  `abbrev` varchar(2) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`stateId`),
  UNIQUE KEY `state_index` (`stateName`,`countryId`),
  KEY `fk_country` (`countryId`),
  KEY `index_statename` (`stateName`),
  KEY `Index_lkupstate_abbr` (`abbrev`),
  CONSTRAINT `fk_country` FOREIGN KEY (`countryId`) REFERENCES `lkupcountry` (`countryId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media` (
  `mediaid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `url` varchar(250) NOT NULL,
  `caption` varchar(250) DEFAULT NULL,
  `authoruid` int(10) unsigned DEFAULT NULL,
  `author` varchar(45) DEFAULT NULL,
  `mediatype` varchar(45) DEFAULT NULL,
  `owner` varchar(250) DEFAULT NULL,
  `sourceurl` varchar(250) DEFAULT NULL,
  `locality` varchar(250) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mediaid`),
  KEY `FK_media_taxa_idx` (`tid`),
  KEY `FK_media_occid_idx` (`occid`),
  KEY `FK_media_uid_idx` (`authoruid`),
  CONSTRAINT `FK_media_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON UPDATE CASCADE,
  CONSTRAINT `FK_media_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_media_uid` FOREIGN KEY (`authoruid`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollcategories`
--

DROP TABLE IF EXISTS `omcollcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollcategories` (
  `ccpk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(75) NOT NULL,
  `icon` varchar(250) DEFAULT NULL,
  `acronym` varchar(45) DEFAULT NULL,
  `url` varchar(250) DEFAULT NULL,
  `inclusive` int(2) DEFAULT '1',
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ccpk`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollcatlink`
--

DROP TABLE IF EXISTS `omcollcatlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollcatlink` (
  `ccpk` int(10) unsigned NOT NULL,
  `collid` int(10) unsigned NOT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ccpk`,`collid`),
  KEY `FK_collcatlink_coll` (`collid`),
  CONSTRAINT `FK_collcatlink_cat` FOREIGN KEY (`ccpk`) REFERENCES `omcollcategories` (`ccpk`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_collcatlink_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollections`
--

DROP TABLE IF EXISTS `omcollections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollections` (
  `CollID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `InstitutionCode` varchar(45) NOT NULL,
  `CollectionCode` varchar(45) DEFAULT NULL,
  `CollectionName` varchar(150) NOT NULL,
  `collectionId` varchar(100) DEFAULT NULL,
  `datasetName` varchar(100) DEFAULT NULL,
  `iid` int(10) unsigned DEFAULT NULL,
  `fulldescription` varchar(2000) DEFAULT NULL,
  `Homepage` varchar(250) DEFAULT NULL,
  `IndividualUrl` varchar(500) DEFAULT NULL,
  `Contact` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `latitudedecimal` decimal(8,6) DEFAULT NULL,
  `longitudedecimal` decimal(9,6) DEFAULT NULL,
  `icon` varchar(250) DEFAULT NULL,
  `CollType` varchar(45) NOT NULL DEFAULT 'Preserved Specimens' COMMENT 'Preserved Specimens, General Observations, Observations',
  `ManagementType` varchar(45) DEFAULT 'Snapshot' COMMENT 'Snapshot, Live Data',
  `PublicEdits` int(1) unsigned NOT NULL DEFAULT '1',
  `collectionguid` varchar(45) DEFAULT NULL,
  `securitykey` varchar(45) DEFAULT NULL,
  `guidtarget` varchar(45) DEFAULT NULL,
  `rightsHolder` varchar(250) DEFAULT NULL,
  `rights` varchar(250) DEFAULT NULL,
  `usageTerm` varchar(250) DEFAULT NULL,
  `publishToGbif` int(11) DEFAULT NULL,
  `bibliographicCitation` varchar(1000) DEFAULT NULL,
  `accessrights` varchar(1000) DEFAULT NULL,
  `SortSeq` int(10) unsigned DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CollID`) USING BTREE,
  UNIQUE KEY `Index_inst` (`InstitutionCode`,`CollectionCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollectionstats`
--

DROP TABLE IF EXISTS `omcollectionstats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollectionstats` (
  `collid` int(10) unsigned NOT NULL,
  `recordcnt` int(10) unsigned NOT NULL DEFAULT '0',
  `georefcnt` int(10) unsigned DEFAULT NULL,
  `familycnt` int(10) unsigned DEFAULT NULL,
  `genuscnt` int(10) unsigned DEFAULT NULL,
  `speciescnt` int(10) unsigned DEFAULT NULL,
  `uploaddate` datetime DEFAULT NULL,
  `datelastmodified` datetime DEFAULT NULL,
  `uploadedby` varchar(45) DEFAULT NULL,
  `dynamicProperties` varchar(500) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`collid`),
  CONSTRAINT `FK_collectionstats_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollectors`
--

DROP TABLE IF EXISTS `omcollectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollectors` (
  `recordedById` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `familyname` varchar(45) NOT NULL,
  `firstname` varchar(45) DEFAULT NULL,
  `middlename` varchar(45) DEFAULT NULL,
  `startyearactive` int(11) DEFAULT NULL,
  `endyearactive` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT '10',
  `guid` varchar(45) DEFAULT NULL,
  `preferredrecbyid` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`recordedById`),
  KEY `fullname` (`familyname`,`firstname`),
  KEY `FK_preferred_recby_idx` (`preferredrecbyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollpublications`
--

DROP TABLE IF EXISTS `omcollpublications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollpublications` (
  `pubid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `collid` int(10) unsigned NOT NULL,
  `targeturl` varchar(250) NOT NULL,
  `securityguid` varchar(45) NOT NULL,
  `criteriajson` varchar(250) DEFAULT NULL,
  `includedeterminations` int(11) DEFAULT '1',
  `includeimages` int(11) DEFAULT '1',
  `autoupdate` int(11) DEFAULT '0',
  `lastdateupdate` datetime DEFAULT NULL,
  `updateinterval` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pubid`),
  KEY `FK_adminpub_collid_idx` (`collid`),
  CONSTRAINT `FK_adminpub_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollsecondary`
--

DROP TABLE IF EXISTS `omcollsecondary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollsecondary` (
  `ocsid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `collid` int(10) unsigned NOT NULL,
  `InstitutionCode` varchar(45) NOT NULL,
  `CollectionCode` varchar(45) DEFAULT NULL,
  `CollectionName` varchar(150) NOT NULL,
  `BriefDescription` varchar(300) DEFAULT NULL,
  `FullDescription` varchar(1000) DEFAULT NULL,
  `Homepage` varchar(250) DEFAULT NULL,
  `IndividualUrl` varchar(500) DEFAULT NULL,
  `Contact` varchar(45) DEFAULT NULL,
  `Email` varchar(45) DEFAULT NULL,
  `LatitudeDecimal` double DEFAULT NULL,
  `LongitudeDecimal` double DEFAULT NULL,
  `icon` varchar(250) DEFAULT NULL,
  `CollType` varchar(45) DEFAULT NULL,
  `SortSeq` int(10) unsigned DEFAULT NULL,
  `InitialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ocsid`),
  KEY `FK_omcollsecondary_coll` (`collid`),
  CONSTRAINT `FK_omcollsecondary_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcrowdsourcecentral`
--

DROP TABLE IF EXISTS `omcrowdsourcecentral`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcrowdsourcecentral` (
  `omcsid` int(11) NOT NULL AUTO_INCREMENT,
  `collid` int(10) unsigned NOT NULL,
  `instructions` text,
  `trainingurl` varchar(500) DEFAULT NULL,
  `editorlevel` int(11) NOT NULL DEFAULT '0' COMMENT '0=public, 1=public limited, 2=private',
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`omcsid`),
  UNIQUE KEY `Index_omcrowdsourcecentral_collid` (`collid`),
  KEY `FK_omcrowdsourcecentral_collid` (`collid`),
  CONSTRAINT `FK_omcrowdsourcecentral_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcrowdsourcequeue`
--

DROP TABLE IF EXISTS `omcrowdsourcequeue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcrowdsourcequeue` (
  `idomcrowdsourcequeue` int(11) NOT NULL AUTO_INCREMENT,
  `omcsid` int(11) NOT NULL,
  `occid` int(10) unsigned NOT NULL,
  `reviewstatus` int(11) NOT NULL DEFAULT '0' COMMENT '0=open,5=pending review, 10=closed',
  `uidprocessor` int(10) unsigned DEFAULT NULL,
  `points` int(11) DEFAULT NULL COMMENT '0=fail, 1=minor edits, 2=no edits <default>, 3=excelled',
  `isvolunteer` int(2) NOT NULL DEFAULT '1',
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idomcrowdsourcequeue`),
  UNIQUE KEY `Index_omcrowdsource_occid` (`occid`),
  KEY `FK_omcrowdsourcequeue_occid` (`occid`),
  KEY `FK_omcrowdsourcequeue_uid` (`uidprocessor`),
  CONSTRAINT `FK_omcrowdsourcequeue_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omcrowdsourcequeue_uid` FOREIGN KEY (`uidprocessor`) REFERENCES `users` (`uid`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omexsiccatinumbers`
--

DROP TABLE IF EXISTS `omexsiccatinumbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omexsiccatinumbers` (
  `omenid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exsnumber` varchar(45) NOT NULL,
  `ometid` int(10) unsigned NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`omenid`),
  UNIQUE KEY `Index_omexsiccatinumbers_unique` (`exsnumber`,`ometid`),
  KEY `FK_exsiccatiTitleNumber` (`ometid`),
  CONSTRAINT `FK_exsiccatiTitleNumber` FOREIGN KEY (`ometid`) REFERENCES `omexsiccatititles` (`ometid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omexsiccatiocclink`
--

DROP TABLE IF EXISTS `omexsiccatiocclink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omexsiccatiocclink` (
  `omenid` int(10) unsigned NOT NULL,
  `occid` int(10) unsigned NOT NULL,
  `ranking` int(11) NOT NULL DEFAULT '50',
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`omenid`,`occid`),
  UNIQUE KEY `UniqueOmexsiccatiOccLink` (`occid`),
  KEY `FKExsiccatiNumOccLink1` (`omenid`),
  KEY `FKExsiccatiNumOccLink2` (`occid`),
  CONSTRAINT `FKExsiccatiNumOccLink1` FOREIGN KEY (`omenid`) REFERENCES `omexsiccatinumbers` (`omenid`) ON DELETE CASCADE,
  CONSTRAINT `FKExsiccatiNumOccLink2` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omexsiccatititles`
--

DROP TABLE IF EXISTS `omexsiccatititles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omexsiccatititles` (
  `ometid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `abbreviation` varchar(100) DEFAULT NULL,
  `editor` varchar(150) DEFAULT NULL,
  `exsrange` varchar(45) DEFAULT NULL,
  `startdate` varchar(45) DEFAULT NULL,
  `enddate` varchar(45) DEFAULT NULL,
  `source` varchar(250) DEFAULT NULL,
  `notes` varchar(2000) DEFAULT NULL,
  `lasteditedby` varchar(45) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ometid`),
  KEY `index_exsiccatiTitle` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurassococcurrences`
--

DROP TABLE IF EXISTS `omoccurassococcurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurassococcurrences` (
  `aoid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `occidassociate` int(10) unsigned DEFAULT NULL,
  `relationship` varchar(150) NOT NULL,
  `identifier` varchar(250) DEFAULT NULL COMMENT 'e.g. GUID',
  `resourceurl` varchar(250) DEFAULT NULL,
  `sciname` varchar(250) DEFAULT NULL,
  `tid` int(11) DEFAULT NULL,
  `locationOnHost` varchar(250) DEFAULT NULL,
  `condition` varchar(250) DEFAULT NULL,
  `dateEmerged` datetime DEFAULT NULL,
  `dynamicProperties` text,
  `notes` varchar(250) DEFAULT NULL,
  `createdby` varchar(45) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`aoid`),
  KEY `omossococcur_occid_idx` (`occid`),
  KEY `omossococcur_occidassoc_idx` (`occidassociate`),
  CONSTRAINT `omossococcur_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `omossococcur_occidassoc` FOREIGN KEY (`occidassociate`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurassoctaxa`
--

DROP TABLE IF EXISTS `omoccurassoctaxa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurassoctaxa` (
  `assoctaxaid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned DEFAULT NULL,
  `verbatimstr` varchar(250) DEFAULT NULL,
  `relationship` varchar(45) DEFAULT NULL,
  `verificationscore` int(11) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`assoctaxaid`),
  KEY `FK_assoctaxa_occid_idx` (`occid`),
  KEY `FK_aooctaxa_tid_idx` (`tid`),
  KEY `INDEX_verbatim_str` (`verbatimstr`),
  CONSTRAINT `FK_assoctaxa_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_aooctaxa_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurcomments`
--

DROP TABLE IF EXISTS `omoccurcomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurcomments` (
  `comid` int(11) NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `comment` text NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `reviewstatus` int(10) unsigned NOT NULL DEFAULT '0',
  `parentcomid` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comid`),
  KEY `fk_omoccurcomments_occid` (`occid`),
  KEY `fk_omoccurcomments_uid` (`uid`),
  CONSTRAINT `fk_omoccurcomments_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_omoccurcomments_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurdatasetlink`
--

DROP TABLE IF EXISTS `omoccurdatasetlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurdatasetlink` (
  `occid` int(10) unsigned NOT NULL,
  `datasetid` int(10) unsigned NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`occid`,`datasetid`),
  KEY `FK_omoccurdatasetlink_datasetid` (`datasetid`),
  KEY `FK_omoccurdatasetlink_occid` (`occid`),
  CONSTRAINT `FK_omoccurdatasetlink_datasetid` FOREIGN KEY (`datasetid`) REFERENCES `omoccurdatasets` (`datasetid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurdatasetlink_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurdatasets`
--

DROP TABLE IF EXISTS `omoccurdatasets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurdatasets` (
  `datasetid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `uid` int(11) unsigned DEFAULT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`datasetid`),
  KEY `FK_omoccurdatasets_uid_idx` (`uid`),
  KEY `FK_omcollections_collid_idx` (`collid`),
  CONSTRAINT `FK_omoccurdatasets_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_omcollections_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurdeterminations`
--

DROP TABLE IF EXISTS `omoccurdeterminations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurdeterminations` (
  `detid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `identifiedBy` varchar(60) NOT NULL,
  `idbyid` int(10) unsigned DEFAULT NULL,
  `dateIdentified` varchar(45) NOT NULL,
  `dateIdentifiedInterpreted` date DEFAULT NULL,
  `sciname` varchar(100) NOT NULL,
  `tidinterpreted` int(10) unsigned DEFAULT NULL,
  `scientificNameAuthorship` varchar(100) DEFAULT NULL,
  `identificationQualifier` varchar(45) DEFAULT NULL,
  `iscurrent` int(2) DEFAULT '0',
  `printqueue` int(2) DEFAULT '0',
  `appliedStatus` int(2) DEFAULT '1',
  `detType` varchar(45) DEFAULT NULL,
  `identificationReferences` varchar(255) DEFAULT NULL,
  `identificationRemarks` varchar(255) DEFAULT NULL,
  `sourceIdentifier` varchar(45) DEFAULT NULL,
  `sortsequence` int(10) unsigned DEFAULT '10',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`detid`),
  UNIQUE KEY `Index_unique` (`occid`,`dateIdentified`,`identifiedBy`,`sciname`),
  KEY `FK_omoccurdets_tid` (`tidinterpreted`),
  KEY `FK_omoccurdets_idby_idx` (`idbyid`),
  KEY `Index_dateIdentInterpreted` (`dateIdentifiedInterpreted`),
  CONSTRAINT `FK_omoccurdets_idby` FOREIGN KEY (`idbyid`) REFERENCES `omcollectors` (`recordedById`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `FK_omoccurdets_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurdets_tid` FOREIGN KEY (`tidinterpreted`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurduplicatelink`
--

DROP TABLE IF EXISTS `omoccurduplicatelink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurduplicatelink` (
  `occid` int(10) unsigned NOT NULL,
  `duplicateid` int(11) NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned DEFAULT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`occid`,`duplicateid`),
  KEY `FK_omoccurdupelink_occid_idx` (`occid`),
  KEY `FK_omoccurdupelink_dupeid_idx` (`duplicateid`),
  CONSTRAINT `FK_omoccurdupelink_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurdupelink_dupeid` FOREIGN KEY (`duplicateid`) REFERENCES `omoccurduplicates` (`duplicateid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurduplicates`
--

DROP TABLE IF EXISTS `omoccurduplicates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurduplicates` (
  `duplicateid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `dupeType` varchar(45) NOT NULL DEFAULT 'Exact Duplicate',
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`duplicateid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccureditlocks`
--

DROP TABLE IF EXISTS `omoccureditlocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccureditlocks` (
  `occid` int(10) unsigned NOT NULL,
  `uid` int(11) NOT NULL,
  `ts` int(11) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`occid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccuredits`
--

DROP TABLE IF EXISTS `omoccuredits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccuredits` (
  `ocedid` int(11) NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `FieldName` varchar(45) NOT NULL,
  `FieldValueNew` text NOT NULL,
  `FieldValueOld` text NOT NULL,
  `ReviewStatus` int(1) NOT NULL DEFAULT '1' COMMENT '1=Open;2=Pending;3=Closed',
  `AppliedStatus` int(1) NOT NULL DEFAULT '0' COMMENT '0=Not Applied;1=Applied',
  `uid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ocedid`),
  KEY `fk_omoccuredits_uid` (`uid`),
  KEY `fk_omoccuredits_occid` (`occid`),
  CONSTRAINT `fk_omoccuredits_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_omoccuredits_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurexchange`
--

DROP TABLE IF EXISTS `omoccurexchange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurexchange` (
  `exchangeid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(30) DEFAULT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `iid` int(10) unsigned DEFAULT NULL,
  `transactionType` varchar(10) DEFAULT NULL,
  `in_out` varchar(3) DEFAULT NULL,
  `dateSent` date DEFAULT NULL,
  `dateReceived` date DEFAULT NULL,
  `totalBoxes` int(5) DEFAULT NULL,
  `shippingMethod` varchar(50) DEFAULT NULL,
  `totalExMounted` int(5) DEFAULT NULL,
  `totalExUnmounted` int(5) DEFAULT NULL,
  `totalGift` int(5) DEFAULT NULL,
  `totalGiftDet` int(5) DEFAULT NULL,
  `adjustment` int(5) DEFAULT NULL,
  `invoiceBalance` int(6) DEFAULT NULL,
  `invoiceMessage` varchar(500) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `createdBy` varchar(20) DEFAULT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`exchangeid`),
  KEY `FK_occexch_coll` (`collid`),
  CONSTRAINT `FK_occexch_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurgenetic`
--

DROP TABLE IF EXISTS `omoccurgenetic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurgenetic` (
  `idoccurgenetic` int(11) NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `identifier` varchar(150) DEFAULT NULL,
  `resourcename` varchar(150) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `locus` varchar(500) DEFAULT NULL,
  `resourceurl` varchar(500) DEFAULT NULL,
  `notes` varchar(45) DEFAULT NULL,
  `initialtimestamp` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idoccurgenetic`),
  KEY `FK_omoccurgenetic` (`occid`),
  KEY `INDEX_omoccurgenetic_name` (`resourcename`),
  CONSTRAINT `FK_omoccurgenetic` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurgeoindex`
--

DROP TABLE IF EXISTS `omoccurgeoindex`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurgeoindex` (
  `tid` int(10) unsigned NOT NULL,
  `decimallatitude` double NOT NULL,
  `decimallongitude` double NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tid`,`decimallatitude`,`decimallongitude`),
  CONSTRAINT `FK_specgeoindex_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccuridentifiers`
--

DROP TABLE IF EXISTS `omoccuridentifiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccuridentifiers` (
  `idomoccuridentifiers` int(11) NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `identifiervalue` varchar(45) NOT NULL,
  `identifiername` varchar(45) DEFAULT NULL COMMENT 'barcode, accession number, old catalog number, NPS, etc',
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned NOT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idomoccuridentifiers`),
  KEY `FK_omoccuridentifiers_occid_idx` (`occid`),
  KEY `Index_value` (`identifiervalue`),
  CONSTRAINT `FK_omoccuridentifiers_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurloans`
--

DROP TABLE IF EXISTS `omoccurloans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurloans` (
  `loanid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loanIdentifierOwn` varchar(30) DEFAULT NULL,
  `loanIdentifierBorr` varchar(30) DEFAULT NULL,
  `collidOwn` int(10) unsigned DEFAULT NULL,
  `collidBorr` int(10) unsigned DEFAULT NULL,
  `iidOwner` int(10) unsigned DEFAULT NULL,
  `iidBorrower` int(10) unsigned DEFAULT NULL,
  `dateSent` date DEFAULT NULL,
  `dateSentReturn` date DEFAULT NULL,
  `receivedStatus` varchar(250) DEFAULT NULL,
  `totalBoxes` int(5) DEFAULT NULL,
  `totalBoxesReturned` int(5) DEFAULT NULL,
  `numSpecimens` int(5) DEFAULT NULL,
  `shippingMethod` varchar(50) DEFAULT NULL,
  `shippingMethodReturn` varchar(50) DEFAULT NULL,
  `dateDue` date DEFAULT NULL,
  `dateReceivedOwn` date DEFAULT NULL,
  `dateReceivedBorr` date DEFAULT NULL,
  `dateClosed` date DEFAULT NULL,
  `forWhom` varchar(50) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `invoiceMessageOwn` varchar(500) DEFAULT NULL,
  `invoiceMessageBorr` varchar(500) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `createdByOwn` varchar(30) DEFAULT NULL,
  `createdByBorr` varchar(30) DEFAULT NULL,
  `processingStatus` int(5) unsigned DEFAULT '1',
  `processedByOwn` varchar(30) DEFAULT NULL,
  `processedByBorr` varchar(30) DEFAULT NULL,
  `processedByReturnOwn` varchar(30) DEFAULT NULL,
  `processedByReturnBorr` varchar(30) DEFAULT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`loanid`),
  KEY `FK_occurloans_owninst` (`iidOwner`),
  KEY `FK_occurloans_borrinst` (`iidBorrower`),
  KEY `FK_occurloans_owncoll` (`collidOwn`),
  KEY `FK_occurloans_borrcoll` (`collidBorr`),
  CONSTRAINT `FK_occurloans_borrcoll` FOREIGN KEY (`collidBorr`) REFERENCES `omcollections` (`CollID`) ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloans_borrinst` FOREIGN KEY (`iidBorrower`) REFERENCES `institutions` (`iid`) ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloans_owncoll` FOREIGN KEY (`collidOwn`) REFERENCES `omcollections` (`CollID`) ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloans_owninst` FOREIGN KEY (`iidOwner`) REFERENCES `institutions` (`iid`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurloanslink`
--

DROP TABLE IF EXISTS `omoccurloanslink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurloanslink` (
  `loanid` int(10) unsigned NOT NULL,
  `occid` int(10) unsigned NOT NULL,
  `returndate` date DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`loanid`,`occid`),
  KEY `FK_occurloanlink_occid` (`occid`),
  KEY `FK_occurloanlink_loanid` (`loanid`),
  CONSTRAINT `FK_occurloanlink_loanid` FOREIGN KEY (`loanid`) REFERENCES `omoccurloans` (`loanid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloanlink_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurpoints`
--

DROP TABLE IF EXISTS `omoccurpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurpoints` (
  `geoID` int(11) NOT NULL AUTO_INCREMENT,
  `occid` int(11) NOT NULL,
  `point` point NOT NULL,
  `errradiuspoly` polygon DEFAULT NULL,
  `footprintpoly` polygon DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`geoID`),
  UNIQUE KEY `occid` (`occid`),
  SPATIAL KEY `point` (`point`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurrences`
--

DROP TABLE IF EXISTS `omoccurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurrences` (
  `occid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `collid` int(10) unsigned NOT NULL,
  `dbpk` varchar(150) DEFAULT NULL,
  `basisOfRecord` varchar(32) DEFAULT 'PreservedSpecimen' COMMENT 'PreservedSpecimen, LivingSpecimen, HumanObservation',
  `occurrenceID` varchar(255) DEFAULT NULL COMMENT 'UniqueGlobalIdentifier',
  `catalogNumber` varchar(32) DEFAULT NULL,
  `otherCatalogNumbers` varchar(255) DEFAULT NULL,
  `ownerInstitutionCode` varchar(32) DEFAULT NULL,
  `institutionID` varchar(255) DEFAULT NULL,
  `collectionID` varchar(255) DEFAULT NULL,
  `datasetID` varchar(255) DEFAULT NULL,
  `institutionCode` varchar(64) DEFAULT NULL,
  `collectionCode` varchar(64) DEFAULT NULL,
  `family` varchar(255) DEFAULT NULL,
  `scientificName` varchar(255) DEFAULT NULL,
  `sciname` varchar(255) DEFAULT NULL,
  `tidinterpreted` int(10) unsigned DEFAULT NULL,
  `genus` varchar(255) DEFAULT NULL,
  `specificEpithet` varchar(255) DEFAULT NULL,
  `taxonRank` varchar(32) DEFAULT NULL,
  `infraspecificEpithet` varchar(255) DEFAULT NULL,
  `scientificNameAuthorship` varchar(255) DEFAULT NULL,
  `taxonRemarks` text,
  `identifiedBy` varchar(255) DEFAULT NULL,
  `dateIdentified` varchar(45) DEFAULT NULL,
  `identificationReferences` text,
  `identificationRemarks` text,
  `identificationQualifier` varchar(255) DEFAULT NULL COMMENT 'cf, aff, etc',
  `typeStatus` varchar(255) DEFAULT NULL,
  `recordedBy` varchar(255) DEFAULT NULL COMMENT 'Collector(s)',
  `recordNumber` varchar(45) DEFAULT NULL COMMENT 'Collector Number',
  `recordedbyid` bigint(20) DEFAULT NULL,
  `associatedCollectors` varchar(255) DEFAULT NULL COMMENT 'not DwC',
  `eventDate` date DEFAULT NULL,
  `year` int(10) DEFAULT NULL,
  `month` int(10) DEFAULT NULL,
  `day` int(10) DEFAULT NULL,
  `startDayOfYear` int(10) DEFAULT NULL,
  `endDayOfYear` int(10) DEFAULT NULL,
  `verbatimEventDate` varchar(255) DEFAULT NULL,
  `habitat` text COMMENT 'Habitat, substrait, etc',
  `substrate` varchar(500) DEFAULT NULL,
  `fieldNotes` text,
  `fieldnumber` varchar(45) DEFAULT NULL,
  `occurrenceRemarks` text COMMENT 'General Notes',
  `informationWithheld` varchar(250) DEFAULT NULL,
  `dataGeneralizations` varchar(250) DEFAULT NULL,
  `associatedOccurrences` text,
  `associatedTaxa` text COMMENT 'Associated Species',
  `dynamicProperties` text,
  `verbatimAttributes` text,
  `behavior` varchar(500) DEFAULT NULL,
  `reproductiveCondition` varchar(255) DEFAULT NULL COMMENT 'Phenology: flowers, fruit, sterile',
  `cultivationStatus` int(10) DEFAULT NULL COMMENT '0 = wild, 1 = cultivated',
  `establishmentMeans` varchar(45) DEFAULT NULL COMMENT 'cultivated, invasive, escaped from captivity, wild, native',
  `lifeStage` varchar(45) DEFAULT NULL,
  `sex` varchar(45) DEFAULT NULL,
  `individualCount` varchar(45) DEFAULT NULL,
  `samplingProtocol` varchar(100) DEFAULT NULL,
  `samplingEffort` varchar(200) DEFAULT NULL,
  `preparations` varchar(100) DEFAULT NULL,
  `country` varchar(64) DEFAULT NULL,
  `stateProvince` varchar(255) DEFAULT NULL,
  `county` varchar(255) DEFAULT NULL,
  `municipality` varchar(255) DEFAULT NULL,
  `locality` text,
  `localitySecurity` int(10) DEFAULT '0' COMMENT '0 = no security; 1 = hidden locality',
  `localitySecurityReason` varchar(100) DEFAULT NULL,
  `decimalLatitude` double DEFAULT NULL,
  `decimalLongitude` double DEFAULT NULL,
  `geodeticDatum` varchar(255) DEFAULT NULL,
  `coordinateUncertaintyInMeters` int(10) unsigned DEFAULT NULL,
  `footprintWKT` text,
  `coordinatePrecision` decimal(9,7) DEFAULT NULL,
  `locationRemarks` text,
  `verbatimCoordinates` varchar(255) DEFAULT NULL,
  `verbatimCoordinateSystem` varchar(255) DEFAULT NULL,
  `georeferencedBy` varchar(255) DEFAULT NULL,
  `georeferenceProtocol` varchar(255) DEFAULT NULL,
  `georeferenceSources` varchar(255) DEFAULT NULL,
  `georeferenceVerificationStatus` varchar(32) DEFAULT NULL,
  `georeferenceRemarks` varchar(255) DEFAULT NULL,
  `minimumElevationInMeters` int(6) DEFAULT NULL,
  `maximumElevationInMeters` int(6) DEFAULT NULL,
  `verbatimElevation` varchar(255) DEFAULT NULL,
  `minimumDepthInMeters` int(11) DEFAULT NULL,
  `maximumDepthInMeters` int(11) DEFAULT NULL,
  `verbatimDepth` varchar(50) DEFAULT NULL,
  `previousIdentifications` text,
  `disposition` varchar(100) DEFAULT NULL,
  `storageLocation` varchar(100) DEFAULT NULL,
  `genericcolumn1` varchar(100) DEFAULT NULL,
  `genericcolumn2` varchar(100) DEFAULT NULL,
  `modified` datetime DEFAULT NULL COMMENT 'DateLastModified',
  `language` varchar(20) DEFAULT NULL,
  `observeruid` int(10) unsigned DEFAULT NULL,
  `processingstatus` varchar(45) DEFAULT NULL,
  `recordEnteredBy` varchar(250) DEFAULT NULL,
  `duplicateQuantity` int(10) unsigned DEFAULT NULL,
  `labelProject` varchar(50) DEFAULT NULL,
  `dateEntered` datetime DEFAULT NULL,
  `dateLastModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`occid`) USING BTREE,
  UNIQUE KEY `Index_collid` (`collid`,`dbpk`),
  KEY `Index_sciname` (`sciname`),
  KEY `Index_family` (`family`),
  KEY `Index_country` (`country`),
  KEY `Index_state` (`stateProvince`),
  KEY `Index_county` (`county`),
  KEY `Index_collector` (`recordedBy`),
  KEY `Index_gui` (`occurrenceID`),
  KEY `Index_ownerInst` (`ownerInstitutionCode`),
  KEY `FK_omoccurrences_tid` (`tidinterpreted`),
  KEY `FK_omoccurrences_uid` (`observeruid`),
  KEY `Index_municipality` (`municipality`),
  KEY `Index_collnum` (`recordNumber`),
  KEY `Index_catalognumber` (`catalogNumber`),
  KEY `FK_recordedbyid` (`recordedbyid`),
  KEY `Index_eventDate` (`eventDate`),
  KEY `Index_occurrences_procstatus` (`processingstatus`),
  KEY `occelevmax` (`maximumElevationInMeters`),
  KEY `occelevmin` (`minimumElevationInMeters`),
  KEY `Index_occurrences_cult` (`cultivationStatus`),
  KEY `Index_occurrences_typestatus` (`typeStatus`),
  KEY `idx_occrecordedby` (`recordedBy`),
  KEY `Index_occurDateLastModifed` (`dateLastModified`),
  KEY `Index_occurDateEntered` (`dateEntered`),
  KEY `Index_occurRecordEnteredBy` (`recordEnteredBy`),
  CONSTRAINT `FK_omoccurrences_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurrences_recbyid` FOREIGN KEY (`recordedbyid`) REFERENCES `agents` (`agentid`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurrences_tid` FOREIGN KEY (`tidinterpreted`) REFERENCES `taxa` (`TID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurrences_uid` FOREIGN KEY (`observeruid`) REFERENCES `users` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;

DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `omoccurrencesfulltext_insert` AFTER INSERT ON `omoccurrences`
FOR EACH ROW BEGIN
  INSERT INTO omoccurrencesfulltext (
    `occid`,
    `recordedby`,
    `locality`
  ) VALUES (
    NEW.`occid`,
    NEW.`recordedby`,
    NEW.`locality`
  );
END */;;

DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `omoccurrencesfulltext_update` AFTER UPDATE ON `omoccurrences`
FOR EACH ROW BEGIN
  UPDATE omoccurrencesfulltext SET
    `recordedby` = NEW.`recordedby`,
    `locality` = NEW.`locality`
  WHERE `occid` = NEW.`occid`;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `omoccurrencesfulltext_delete` BEFORE DELETE ON `omoccurrences`
FOR EACH ROW BEGIN
  DELETE FROM omoccurrencesfulltext WHERE `occid` = OLD.`occid`;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `omoccurrencesfulltext`
--

DROP TABLE IF EXISTS `omoccurrencesfulltext`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurrencesfulltext` (
  `occid` int(11) NOT NULL,
  `locality` text,
  `recordedby` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`occid`),
  FULLTEXT KEY `ft_occur_locality` (`locality`),
  FULLTEXT KEY `ft_occur_recordedby` (`recordedby`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurverification`
--

DROP TABLE IF EXISTS `omoccurverification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurverification` (
  `ovsid` int(11) NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `category` varchar(45) NOT NULL,
  `ranking` int(11) NOT NULL,
  `protocol` varchar(100) DEFAULT NULL,
  `source` varchar(45) DEFAULT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ovsid`),
  UNIQUE KEY `UNIQUE_omoccurverification` (`occid`,`category`),
  KEY `FK_omoccurverification_occid_idx` (`occid`),
  KEY `FK_omoccurverification_uid_idx` (`uid`),
  CONSTRAINT `FK_omoccurverification_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurverification_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omsurveyoccurlink`
--

DROP TABLE IF EXISTS `omsurveyoccurlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omsurveyoccurlink` (
  `occid` int(10) unsigned NOT NULL,
  `surveyid` int(10) unsigned NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`occid`,`surveyid`),
  KEY `FK_omsurveyoccurlink_sur` (`surveyid`),
  CONSTRAINT `FK_omsurveyoccurlink_occ` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`),
  CONSTRAINT `FK_omsurveyoccurlink_sur` FOREIGN KEY (`surveyid`) REFERENCES `omsurveys` (`surveyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omsurveyprojlink`
--

DROP TABLE IF EXISTS `omsurveyprojlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omsurveyprojlink` (
  `surveyid` int(10) unsigned NOT NULL,
  `pid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`surveyid`,`pid`) USING BTREE,
  KEY `FK_specprojcatlink_cat` (`pid`) USING BTREE,
  CONSTRAINT `FK_omsurveyprojlink_proj` FOREIGN KEY (`pid`) REFERENCES `fmprojects` (`pid`),
  CONSTRAINT `FK_omsurveyprojlink_sur` FOREIGN KEY (`surveyid`) REFERENCES `omsurveys` (`surveyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omsurveys`
--

DROP TABLE IF EXISTS `omsurveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omsurveys` (
  `surveyid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `projectname` varchar(75) NOT NULL,
  `locality` varchar(1000) DEFAULT NULL,
  `managers` varchar(150) DEFAULT NULL,
  `latcentroid` double(9,6) DEFAULT NULL,
  `longcentroid` double(9,6) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `ispublic` int(10) unsigned NOT NULL DEFAULT '0',
  `sortsequence` int(10) unsigned NOT NULL DEFAULT '50',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`surveyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referenceagentlinks`
--

DROP TABLE IF EXISTS `referenceagentlinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referenceagentlinks` (
  `refid` int(11) NOT NULL,
  `agentid` int(11) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdbyid` int(11) NOT NULL,
  PRIMARY KEY (`refid`,`agentid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referenceauthorlink`
--

DROP TABLE IF EXISTS `referenceauthorlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referenceauthorlink` (
  `refid` int(11) NOT NULL,
  `refauthid` int(11) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`,`refauthid`),
  KEY `FK_refauthlink_refid_idx` (`refid`),
  KEY `FK_refauthlink_refauthid_idx` (`refauthid`),
  CONSTRAINT `FK_refauthlink_refid` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_refauthlink_refauthid` FOREIGN KEY (`refauthid`) REFERENCES `referenceauthors` (`refauthorid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referenceauthors`
--

DROP TABLE IF EXISTS `referenceauthors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referenceauthors` (
  `refauthorid` int(11) NOT NULL AUTO_INCREMENT,
  `lastname` varchar(100) NOT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `modifieduid` int(10) unsigned DEFAULT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refauthorid`),
  KEY `INDEX_refauthlastname` (`lastname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referencechecklistlink`
--

DROP TABLE IF EXISTS `referencechecklistlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referencechecklistlink` (
  `refid` int(11) NOT NULL,
  `clid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`,`clid`),
  KEY `FK_refcheckllistlink_refid_idx` (`refid`),
  KEY `FK_refcheckllistlink_clid_idx` (`clid`),
  CONSTRAINT `FK_refchecklistlink_refid` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_refchecklistlink_clid` FOREIGN KEY (`clid`) REFERENCES `fmchecklists` (`CLID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referencechklsttaxalink`
--

DROP TABLE IF EXISTS `referencechklsttaxalink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referencechklsttaxalink` (
  `refid` int(11) NOT NULL,
  `clid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`,`clid`,`tid`),
  KEY `FK_refchktaxalink_clidtid_idx` (`clid`,`tid`),
  CONSTRAINT `FK_refchktaxalink_ref` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_refchktaxalink_clidtid` FOREIGN KEY (`clid`, `tid`) REFERENCES `fmchklsttaxalink` (`CLID`, `TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referencecollectionlink`
--

DROP TABLE IF EXISTS `referencecollectionlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referencecollectionlink` (
  `refid` int(11) NOT NULL,
  `collid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`,`collid`),
  KEY `FK_refcollectionlink_collid_idx` (`collid`),
  CONSTRAINT `FK_refcollectionlink_refid` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_refcollectionlink_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referenceobject`
--

DROP TABLE IF EXISTS `referenceobject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referenceobject` (
  `refid` int(11) NOT NULL AUTO_INCREMENT,
  `parentRefId` int(11) DEFAULT NULL,
  `ReferenceTypeId` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `secondarytitle` varchar(250) DEFAULT NULL,
  `shorttitle` varchar(250) DEFAULT NULL,
  `tertiarytitle` varchar(250) DEFAULT NULL,
  `alternativetitle` varchar(250) DEFAULT NULL,
  `typework` varchar(150) DEFAULT NULL,
  `figures` varchar(150) DEFAULT NULL,
  `pubdate` varchar(45) DEFAULT NULL,
  `edition` varchar(45) DEFAULT NULL,
  `volume` varchar(45) DEFAULT NULL,
  `numbervolumnes` varchar(45) DEFAULT NULL,
  `number` varchar(45) DEFAULT NULL,
  `pages` varchar(45) DEFAULT NULL,
  `section` varchar(45) DEFAULT NULL,
  `placeofpublication` varchar(45) DEFAULT NULL,
  `publisher` varchar(150) DEFAULT NULL,
  `isbn_issn` varchar(45) DEFAULT NULL,
  `url` varchar(150) DEFAULT NULL,
  `guid` varchar(45) DEFAULT NULL,
  `ispublished` varchar(45) DEFAULT NULL,
  `notes` varchar(45) DEFAULT NULL,
  `cheatauthors` varchar(250) DEFAULT NULL,
  `cheatcitation` varchar(250) DEFAULT NULL,
  `modifieduid` int(10) unsigned DEFAULT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`),
  KEY `INDEX_refobj_title` (`title`),
  KEY `FK_refobj_parentrefid_idx` (`parentRefId`),
  KEY `FK_refobj_typeid_idx` (`ReferenceTypeId`),
  CONSTRAINT `FK_refobj_parentrefid` FOREIGN KEY (`parentRefId`) REFERENCES `referenceobject` (`refid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_refobj_reftypeid` FOREIGN KEY (`ReferenceTypeId`) REFERENCES `referencetype` (`ReferenceTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referenceoccurlink`
--

DROP TABLE IF EXISTS `referenceoccurlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referenceoccurlink` (
  `refid` int(11) NOT NULL,
  `occid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`,`occid`),
  KEY `FK_refoccurlink_refid_idx` (`refid`),
  KEY `FK_refoccurlink_occid_idx` (`occid`),
  CONSTRAINT `FK_refoccurlink_refid` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_refoccurlink_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referencetaxalink`
--

DROP TABLE IF EXISTS `referencetaxalink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referencetaxalink` (
  `refid` int(11) NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`,`tid`),
  KEY `FK_reftaxalink_refid_idx` (`refid`),
  KEY `FK_reftaxalink_tid_idx` (`tid`),
  CONSTRAINT `FK_reftaxalink_refid` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_reftaxalink_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referencetype`
--

DROP TABLE IF EXISTS `referencetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referencetype` (
  `ReferenceTypeId` int(11) NOT NULL AUTO_INCREMENT,
  `ReferenceType` varchar(45) NOT NULL,
  `IsParent` int(11) DEFAULT NULL,
  `Title` varchar(45) DEFAULT NULL,
  `SecondaryTitle` varchar(45) DEFAULT NULL,
  `PlacePublished` varchar(45) DEFAULT NULL,
  `Publisher` varchar(45) DEFAULT NULL,
  `Volume` varchar(45) DEFAULT NULL,
  `NumberVolumes` varchar(45) DEFAULT NULL,
  `Number` varchar(45) DEFAULT NULL,
  `Pages` varchar(45) DEFAULT NULL,
  `Section` varchar(45) DEFAULT NULL,
  `TertiaryTitle` varchar(45) DEFAULT NULL,
  `Edition` varchar(45) DEFAULT NULL,
  `Date` varchar(45) DEFAULT NULL,
  `TypeWork` varchar(45) DEFAULT NULL,
  `ShortTitle` varchar(45) DEFAULT NULL,
  `AlternativeTitle` varchar(45) DEFAULT NULL,
  `ISBN_ISSN` varchar(45) DEFAULT NULL,
  `Figures` varchar(45) DEFAULT NULL,
  `addedByUid` int(11) DEFAULT NULL,
  `initialTimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReferenceTypeId`),
  UNIQUE KEY `ReferenceType_UNIQUE` (`ReferenceType`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `salixwordstats`
--

DROP TABLE IF EXISTS `salixwordstats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salixwordstats` (
  `swsid` int(11) NOT NULL AUTO_INCREMENT,
  `firstword` varchar(45) NOT NULL,
  `secondword` varchar(45) DEFAULT NULL,
  `locality` int(4) NOT NULL DEFAULT '0',
  `localityFreq` int(4) NOT NULL DEFAULT '0',
  `habitat` int(4) NOT NULL DEFAULT '0',
  `habitatFreq` int(4) NOT NULL DEFAULT '0',
  `substrate` int(4) NOT NULL DEFAULT '0',
  `substrateFreq` int(4) NOT NULL DEFAULT '0',
  `verbatimAttributes` int(4) NOT NULL DEFAULT '0',
  `verbatimAttributesFreq` int(4) NOT NULL DEFAULT '0',
  `occurrenceRemarks` int(4) NOT NULL DEFAULT '0',
  `occurrenceRemarksFreq` int(4) NOT NULL DEFAULT '0',
  `totalcount` int(4) NOT NULL DEFAULT '0',
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`swsid`),
  UNIQUE KEY `INDEX_unique` (`firstword`,`secondword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `schemaversion`
--

DROP TABLE IF EXISTS `schemaversion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schemaversion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `versionnumber` varchar(20) NOT NULL,
  `dateapplied` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specprocessorprojects`
--

DROP TABLE IF EXISTS `specprocessorprojects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprocessorprojects` (
  `spprid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `collid` int(10) unsigned NOT NULL,
  `title` varchar(100) NOT NULL,
  `specKeyPattern` varchar(45) DEFAULT NULL,
  `speckeyretrieval` varchar(45) DEFAULT NULL,
  `coordX1` int(10) unsigned DEFAULT NULL,
  `coordX2` int(10) unsigned DEFAULT NULL,
  `coordY1` int(10) unsigned DEFAULT NULL,
  `coordY2` int(10) unsigned DEFAULT NULL,
  `sourcePath` varchar(250) DEFAULT NULL,
  `targetPath` varchar(250) DEFAULT NULL,
  `imgUrl` varchar(250) DEFAULT NULL,
  `webPixWidth` int(10) unsigned DEFAULT '1200',
  `tnPixWidth` int(10) unsigned DEFAULT '130',
  `lgPixWidth` int(10) unsigned DEFAULT '2400',
  `jpgcompression` int(11) DEFAULT '70',
  `createTnImg` int(10) unsigned DEFAULT '1',
  `createLgImg` int(10) unsigned DEFAULT '1',
  `source` varchar(45) DEFAULT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`spprid`),
  KEY `FK_specprocessorprojects_coll` (`collid`),
  CONSTRAINT `FK_specprocessorprojects_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specprocessorrawlabels`
--

DROP TABLE IF EXISTS `specprocessorrawlabels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprocessorrawlabels` (
  `prlid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `imgid` int(10) unsigned DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `rawstr` text NOT NULL,
  `processingvariables` varchar(250) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `source` varchar(150) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`prlid`),
  KEY `FK_specproc_images` (`imgid`),
  KEY `FK_specproc_occid` (`occid`),
  CONSTRAINT `FK_specproc_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_specproc_images` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `specprocessorrawlabelsfulltext_insert` AFTER INSERT ON `specprocessorrawlabels`
FOR EACH ROW BEGIN
  INSERT INTO specprocessorrawlabelsfulltext (
    `prlid`,
    `imgid`,
    `rawstr`
  ) VALUES (
    NEW.`prlid`,
    NEW.`imgid`,
    NEW.`rawstr`
  );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `specprocessorrawlabelsfulltext_update` AFTER UPDATE ON `specprocessorrawlabels`
FOR EACH ROW BEGIN
  UPDATE specprocessorrawlabelsfulltext SET
    `imgid` = NEW.`imgid`,
    `rawstr` = NEW.`rawstr`
  WHERE `prlid` = NEW.`prlid`;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `specprocessorrawlabelsfulltext`
--

DROP TABLE IF EXISTS `specprocessorrawlabelsfulltext`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprocessorrawlabelsfulltext` (
  `prlid` int(11) NOT NULL,
  `imgid` int(11) NOT NULL,
  `rawstr` text NOT NULL,
  PRIMARY KEY (`prlid`),
  KEY `Index_ocr_imgid` (`imgid`),
  FULLTEXT KEY `Index_ocr_fulltext` (`rawstr`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `specprocessorrawlabelsfulltext_delete` BEFORE DELETE ON `specprocessorrawlabelsfulltext`
FOR EACH ROW BEGIN
  DELETE FROM specprocessorrawlabelsfulltext WHERE `prlid` = OLD.`prlid`;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `specprocnlp`
--

DROP TABLE IF EXISTS `specprocnlp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprocnlp` (
  `spnlpid` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(45) NOT NULL,
  `sqlfrag` varchar(250) NOT NULL,
  `patternmatch` varchar(250) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `collid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`spnlpid`),
  KEY `FK_specprocnlp_collid` (`collid`),
  CONSTRAINT `FK_specprocnlp_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specprocnlpfrag`
--

DROP TABLE IF EXISTS `specprocnlpfrag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprocnlpfrag` (
  `spnlpfragid` int(10) NOT NULL AUTO_INCREMENT,
  `spnlpid` int(10) NOT NULL,
  `fieldname` varchar(45) NOT NULL,
  `patternmatch` varchar(250) NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `sortseq` int(5) DEFAULT '50',
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`spnlpfragid`),
  KEY `FK_specprocnlpfrag_spnlpid` (`spnlpid`),
  CONSTRAINT `FK_specprocnlpfrag_spnlpid` FOREIGN KEY (`spnlpid`) REFERENCES `specprocnlp` (`spnlpid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specprocnlpversion`
--

DROP TABLE IF EXISTS `specprocnlpversion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprocnlpversion` (
  `nlpverid` int(11) NOT NULL AUTO_INCREMENT,
  `prlid` int(10) unsigned NOT NULL,
  `archivestr` text NOT NULL,
  `processingvariables` varchar(250) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `source` varchar(150) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`nlpverid`),
  KEY `FK_specprocnlpver_rawtext_idx` (`prlid`),
  CONSTRAINT `FK_specprocnlpver_rawtext` FOREIGN KEY (`prlid`) REFERENCES `specprocessorrawlabels` (`prlid`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Archives field name - value pairs of NLP results loading into an omoccurrence record. This way, results can be easily redone at a later date without copying over date modifed afterward by another user or process ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specprococrfrag`
--

DROP TABLE IF EXISTS `specprococrfrag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprococrfrag` (
  `ocrfragid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `prlid` int(10) unsigned NOT NULL,
  `firstword` varchar(45) NOT NULL,
  `secondword` varchar(45) DEFAULT NULL,
  `keyterm` varchar(45) DEFAULT NULL,
  `wordorder` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ocrfragid`),
  KEY `FK_specprococrfrag_prlid_idx` (`prlid`),
  KEY `Index_keyterm` (`keyterm`),
  CONSTRAINT `FK_specprococrfrag_prlid` FOREIGN KEY (`prlid`) REFERENCES `specprocessorrawlabels` (`prlid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxa`
--

DROP TABLE IF EXISTS `taxa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxa` (
  `TID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kingdomName` varchar(45) DEFAULT NULL,
  `KingdomID` tinyint(3) unsigned DEFAULT NULL,
  `RankId` smallint(5) unsigned DEFAULT NULL,
  `SciName` varchar(250) NOT NULL,
  `UnitInd1` varchar(1) DEFAULT NULL,
  `UnitName1` varchar(50) NOT NULL,
  `UnitInd2` varchar(1) DEFAULT NULL,
  `UnitName2` varchar(50) DEFAULT NULL,
  `UnitInd3` varchar(7) DEFAULT NULL,
  `UnitName3` varchar(35) DEFAULT NULL,
  `Author` varchar(100) DEFAULT NULL,
  `PhyloSortSequence` tinyint(3) unsigned DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Source` varchar(250) DEFAULT NULL,
  `Notes` varchar(250) DEFAULT NULL,
  `Hybrid` varchar(50) DEFAULT NULL,
  `SecurityStatus` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '0 = no security; 1 = hidden locality',
  `modifiedUid` int(10) unsigned DEFAULT NULL,
  `modifiedTimeStamp` datetime DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`TID`),
  UNIQUE KEY `sciname_unique` (`SciName`),
  KEY `rankid_index` (`RankId`),
  KEY `unitname1_index` (`UnitName1`,`UnitName2`) USING BTREE,
  KEY `FK_taxa_uid_idx` (`modifiedUid`),
  CONSTRAINT `FK_taxa_uid` FOREIGN KEY (`modifiedUid`) REFERENCES `users` (`uid`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxadescrblock`
--

DROP TABLE IF EXISTS `taxadescrblock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxadescrblock` (
  `tdbid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL,
  `caption` varchar(20) DEFAULT NULL,
  `source` varchar(250) DEFAULT NULL,
  `sourceurl` varchar(250) DEFAULT NULL,
  `language` varchar(45) DEFAULT 'English',
  `displaylevel` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '1 = short descr, 2 = intermediate descr',
  `uid` int(10) unsigned NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tdbid`),
  UNIQUE KEY `Index_unique` (`tid`,`displaylevel`,`language`),
  CONSTRAINT `FK_taxadescrblock_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxadescrstmts`
--

DROP TABLE IF EXISTS `taxadescrstmts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxadescrstmts` (
  `tdsid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tdbid` int(10) unsigned NOT NULL,
  `heading` varchar(75) NOT NULL,
  `statement` text NOT NULL,
  `displayheader` int(10) unsigned NOT NULL DEFAULT '1',
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` int(10) unsigned NOT NULL DEFAULT '89',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tdsid`),
  KEY `FK_taxadescrstmts_tblock` (`tdbid`),
  CONSTRAINT `FK_taxadescrstmts_tblock` FOREIGN KEY (`tdbid`) REFERENCES `taxadescrblock` (`tdbid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxaenumtree`
--

DROP TABLE IF EXISTS `taxaenumtree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxaenumtree` (
  `tid` int(10) unsigned NOT NULL,
  `taxauthid` int(10) unsigned NOT NULL,
  `parenttid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tid`,`taxauthid`,`parenttid`),
  KEY `FK_tet_taxa` (`tid`),
  KEY `FK_tet_taxauth` (`taxauthid`),
  KEY `FK_tet_taxa2` (`parenttid`),
  CONSTRAINT `FK_tet_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tet_taxauth` FOREIGN KEY (`taxauthid`) REFERENCES `taxauthority` (`taxauthid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tet_taxa2` FOREIGN KEY (`parenttid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxalinks`
--

DROP TABLE IF EXISTS `taxalinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxalinks` (
  `tlid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL,
  `url` varchar(500) NOT NULL,
  `title` varchar(100) NOT NULL,
  `sourceIdentifier` varchar(45) DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `icon` varchar(45) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` int(10) unsigned NOT NULL DEFAULT '50',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tlid`),
  KEY `Index_unique` (`tid`,`url`(255)),
  CONSTRAINT `FK_taxalinks_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxamaps`
--

DROP TABLE IF EXISTS `taxamaps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxamaps` (
  `mid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL,
  `url` varchar(255) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mid`),
  KEY `FK_tid_idx` (`tid`),
  CONSTRAINT `FK_taxamaps_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxanestedtree`
--

DROP TABLE IF EXISTS `taxanestedtree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxanestedtree` (
  `tid` int(10) unsigned NOT NULL,
  `taxauthid` int(10) unsigned NOT NULL,
  `leftindex` int(10) unsigned NOT NULL,
  `rightindex` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tid`,`taxauthid`),
  KEY `leftindex` (`leftindex`),
  KEY `rightindex` (`rightindex`),
  KEY `FK_tnt_taxa` (`tid`),
  KEY `FK_tnt_taxauth` (`taxauthid`),
  CONSTRAINT `FK_tnt_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tnt_taxauth` FOREIGN KEY (`taxauthid`) REFERENCES `taxauthority` (`taxauthid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxaprofilepubdesclink`
--

DROP TABLE IF EXISTS `taxaprofilepubdesclink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxaprofilepubdesclink` (
  `tdbid` int(10) unsigned NOT NULL,
  `tppid` int(11) NOT NULL,
  `caption` varchar(45) DEFAULT NULL,
  `editornotes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tdbid`,`tppid`),
  KEY `FK_tppubdesclink_id_idx` (`tppid`),
  CONSTRAINT `FK_tppubdesclink_tdbid` FOREIGN KEY (`tdbid`) REFERENCES `taxadescrblock` (`tdbid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tppubdesclink_id` FOREIGN KEY (`tppid`) REFERENCES `taxaprofilepubs` (`tppid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxaprofilepubimagelink`
--

DROP TABLE IF EXISTS `taxaprofilepubimagelink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxaprofilepubimagelink` (
  `imgid` int(10) unsigned NOT NULL,
  `tppid` int(11) NOT NULL,
  `caption` varchar(45) DEFAULT NULL,
  `editornotes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imgid`,`tppid`),
  KEY `FK_tppubimagelink_id_idx` (`tppid`),
  CONSTRAINT `FK_tppubimagelink_imgid` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tppubimagelink_id` FOREIGN KEY (`tppid`) REFERENCES `taxaprofilepubs` (`tppid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxaprofilepubmaplink`
--

DROP TABLE IF EXISTS `taxaprofilepubmaplink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxaprofilepubmaplink` (
  `mid` int(10) unsigned NOT NULL,
  `tppid` int(11) NOT NULL,
  `caption` varchar(45) DEFAULT NULL,
  `editornotes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mid`,`tppid`),
  KEY `FK_tppubmaplink_id_idx` (`tppid`),
  CONSTRAINT `FK_tppubmaplink_tdbid` FOREIGN KEY (`mid`) REFERENCES `taxamaps` (`mid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tppubmaplink_id` FOREIGN KEY (`tppid`) REFERENCES `taxaprofilepubs` (`tppid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxaprofilepubs`
--

DROP TABLE IF EXISTS `taxaprofilepubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxaprofilepubs` (
  `tppid` int(11) NOT NULL AUTO_INCREMENT,
  `pubtitle` varchar(150) NOT NULL,
  `authors` varchar(150) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `abstract` text,
  `uidowner` int(10) unsigned DEFAULT NULL,
  `externalurl` varchar(250) DEFAULT NULL,
  `rights` varchar(250) DEFAULT NULL,
  `usageterm` varchar(250) DEFAULT NULL,
  `accessrights` varchar(250) DEFAULT NULL,
  `ispublic` int(11) DEFAULT NULL,
  `inclusive` int(11) DEFAULT NULL,
  `dynamicProperties` text,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tppid`),
  KEY `FK_taxaprofilepubs_uid_idx` (`uidowner`),
  KEY `INDEX_taxaprofilepubs_title` (`pubtitle`),
  CONSTRAINT `FK_taxaprofilepubs_uid` FOREIGN KEY (`uidowner`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxaresourcelinks`
--

DROP TABLE IF EXISTS `taxaresourcelinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxaresourcelinks` (
  `taxaresourceid` int(11) NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL,
  `sourcename` varchar(150) NOT NULL,
  `sourceidentifier` varchar(45) DEFAULT NULL,
  `sourceguid` varchar(150) DEFAULT NULL,
  `url` varchar(250) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `ranking` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`taxaresourceid`),
  KEY `taxaresource_name` (`sourcename`),
  KEY `FK_taxaresource_tid_idx` (`tid`),
  CONSTRAINT `FK_taxaresource_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxauthority`
--

DROP TABLE IF EXISTS `taxauthority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxauthority` (
  `taxauthid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `isprimary` int(1) unsigned NOT NULL DEFAULT '0',
  `name` varchar(45) NOT NULL,
  `description` varchar(250) DEFAULT NULL,
  `editors` varchar(150) DEFAULT NULL,
  `contact` varchar(45) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `url` varchar(150) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `isactive` int(1) unsigned NOT NULL DEFAULT '1',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`taxauthid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxavernaculars`
--

DROP TABLE IF EXISTS `taxavernaculars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxavernaculars` (
  `TID` int(10) unsigned NOT NULL DEFAULT '0',
  `VernacularName` varchar(80) NOT NULL,
  `Language` varchar(15) NOT NULL DEFAULT 'English',
  `Source` varchar(50) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `username` varchar(45) DEFAULT NULL,
  `isupperterm` int(2) DEFAULT '0',
  `SortSequence` int(10) DEFAULT '50',
  `VID` int(10) NOT NULL AUTO_INCREMENT,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`VID`),
  UNIQUE KEY `unique-key` (`Language`,`VernacularName`,`TID`),
  KEY `tid1` (`TID`),
  KEY `vernacularsnames` (`VernacularName`),
  CONSTRAINT `FK_vernaculars_tid` FOREIGN KEY (`TID`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxonunits`
--

DROP TABLE IF EXISTS `taxonunits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxonunits` (
  `taxonunitid` int(11) NOT NULL AUTO_INCREMENT,
  `kingdomid` tinyint(3) unsigned DEFAULT NULL,
  `kingdomName` varchar(45) NOT NULL DEFAULT 'Organism',
  `rankid` smallint(5) unsigned NOT NULL DEFAULT '0',
  `rankname` varchar(15) NOT NULL,
  `suffix` varchar(45) DEFAULT NULL,
  `dirparentrankid` smallint(6) NOT NULL,
  `reqparentrankid` smallint(6) DEFAULT NULL,
  `modifiedby` varchar(45) DEFAULT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`taxonunitid`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxstatus`
--

DROP TABLE IF EXISTS `taxstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxstatus` (
  `tid` int(10) unsigned NOT NULL,
  `tidaccepted` int(10) unsigned NOT NULL,
  `taxauthid` int(10) unsigned NOT NULL COMMENT 'taxon authority id',
  `parenttid` int(10) unsigned DEFAULT NULL,
  `hierarchystr` varchar(200) DEFAULT NULL,
  `family` varchar(50) DEFAULT NULL,
  `UnacceptabilityReason` varchar(250) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `SortSequence` int(10) unsigned DEFAULT '50',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tid`,`tidaccepted`,`taxauthid`) USING BTREE,
  KEY `FK_taxstatus_tidacc` (`tidaccepted`),
  KEY `FK_taxstatus_taid` (`taxauthid`),
  KEY `Index_ts_family` (`family`),
  KEY `Index_parenttid` (`parenttid`),
  KEY `Index_hierarchy` (`hierarchystr`) USING BTREE,
  CONSTRAINT `FK_taxstatus_parent` FOREIGN KEY (`parenttid`) REFERENCES `taxa` (`TID`),
  CONSTRAINT `FK_taxstatus_taid` FOREIGN KEY (`taxauthid`) REFERENCES `taxauthority` (`taxauthid`) ON UPDATE CASCADE,
  CONSTRAINT `FK_taxstatus_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`),
  CONSTRAINT `FK_taxstatus_tidacc` FOREIGN KEY (`tidaccepted`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unknowncomments`
--

DROP TABLE IF EXISTS `unknowncomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unknowncomments` (
  `unkcomid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unkid` int(10) unsigned NOT NULL,
  `comment` varchar(500) NOT NULL,
  `username` varchar(45) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`unkcomid`) USING BTREE,
  KEY `FK_unknowncomments` (`unkid`),
  CONSTRAINT `FK_unknowncomments` FOREIGN KEY (`unkid`) REFERENCES `unknowns` (`unkid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unknownimages`
--

DROP TABLE IF EXISTS `unknownimages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unknownimages` (
  `unkimgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unkid` int(10) unsigned NOT NULL,
  `url` varchar(255) NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`unkimgid`),
  KEY `FK_unknowns` (`unkid`),
  CONSTRAINT `FK_unknowns` FOREIGN KEY (`unkid`) REFERENCES `unknowns` (`unkid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unknowns`
--

DROP TABLE IF EXISTS `unknowns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unknowns` (
  `unkid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned DEFAULT NULL,
  `photographer` varchar(100) DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `locality` varchar(250) DEFAULT NULL,
  `latdecimal` double DEFAULT NULL,
  `longdecimal` double DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `username` varchar(45) NOT NULL,
  `idstatus` varchar(45) NOT NULL DEFAULT 'ID pending',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`unkid`) USING BTREE,
  KEY `FK_unknowns_username` (`username`),
  KEY `FK_unknowns_tid` (`tid`),
  CONSTRAINT `FK_unknowns_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`),
  CONSTRAINT `FK_unknowns_username` FOREIGN KEY (`username`) REFERENCES `userlogin` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploaddetermtemp`
--

DROP TABLE IF EXISTS `uploaddetermtemp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploaddetermtemp` (
  `occid` int(10) unsigned DEFAULT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `dbpk` varchar(150) DEFAULT NULL,
  `identifiedBy` varchar(60) NOT NULL,
  `dateIdentified` varchar(45) NOT NULL,
  `dateIdentifiedInterpreted` date DEFAULT NULL,
  `sciname` varchar(100) NOT NULL,
  `scientificNameAuthorship` varchar(100) DEFAULT NULL,
  `identificationQualifier` varchar(45) DEFAULT NULL,
  `iscurrent` int(2) DEFAULT '0',
  `detType` varchar(45) DEFAULT NULL,
  `identificationReferences` varchar(255) DEFAULT NULL,
  `identificationRemarks` varchar(255) DEFAULT NULL,
  `sourceIdentifier` varchar(45) DEFAULT NULL,
  `sortsequence` int(10) unsigned DEFAULT '10',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `Index_uploaddet_occid` (`occid`),
  KEY `Index_uploaddet_collid` (`collid`),
  KEY `Index_uploaddet_dbpk` (`dbpk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploadimagetemp`
--

DROP TABLE IF EXISTS `uploadimagetemp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploadimagetemp` (
  `tid` int(10) unsigned DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `thumbnailurl` varchar(255) DEFAULT NULL,
  `originalurl` varchar(255) DEFAULT NULL,
  `archiveurl` varchar(255) DEFAULT NULL,
  `photographer` varchar(100) DEFAULT NULL,
  `photographeruid` int(10) unsigned DEFAULT NULL,
  `imagetype` varchar(50) DEFAULT NULL,
  `format` varchar(45) DEFAULT NULL,
  `caption` varchar(100) DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `dbpk` varchar(150) DEFAULT NULL,
  `specimengui` varchar(45) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `username` varchar(45) DEFAULT NULL,
  `sortsequence` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `Index_uploadimg_occid` (`occid`),
  KEY `Index_uploadimg_collid` (`collid`),
  KEY `Index_uploadimg_dbpk` (`dbpk`),
  KEY `Index_uploadimg_ts` (`initialtimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploadspecmap`
--

DROP TABLE IF EXISTS `uploadspecmap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploadspecmap` (
  `usmid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uspid` int(10) unsigned NOT NULL,
  `sourcefield` varchar(45) NOT NULL,
  `symbdatatype` varchar(45) NOT NULL DEFAULT 'string' COMMENT 'string, numeric, datetime',
  `symbspecfield` varchar(45) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usmid`),
  UNIQUE KEY `Index_unique` (`uspid`,`symbspecfield`,`sourcefield`),
  CONSTRAINT `FK_uploadspecmap_usp` FOREIGN KEY (`uspid`) REFERENCES `uploadspecparameters` (`uspid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploadspecparameters`
--

DROP TABLE IF EXISTS `uploadspecparameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploadspecparameters` (
  `uspid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CollID` int(10) unsigned NOT NULL,
  `UploadType` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '1 = Direct; 3 = File',
  `title` varchar(45) NOT NULL,
  `Platform` varchar(45) DEFAULT '1' COMMENT '1 = MySQL; 2 = MSSQL; 3 = ORACLE; 11 = MS Access; 12 = FileMaker',
  `server` varchar(150) DEFAULT NULL,
  `port` int(10) unsigned DEFAULT NULL,
  `driver` varchar(45) DEFAULT NULL,
  `Code` varchar(45) DEFAULT NULL,
  `Path` varchar(150) DEFAULT NULL,
  `PkField` varchar(45) DEFAULT NULL,
  `Username` varchar(45) DEFAULT NULL,
  `Password` varchar(45) DEFAULT NULL,
  `SchemaName` varchar(150) DEFAULT NULL,
  `QueryStr` varchar(2000) DEFAULT NULL,
  `cleanupsp` varchar(45) DEFAULT NULL,
  `dlmisvalid` int(10) unsigned DEFAULT '0',
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uspid`),
  KEY `FK_uploadspecparameters_coll` (`CollID`),
  CONSTRAINT `FK_uploadspecparameters_coll` FOREIGN KEY (`CollID`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploadspectemp`
--

DROP TABLE IF EXISTS `uploadspectemp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploadspectemp` (
  `collid` int(10) unsigned NOT NULL,
  `dbpk` varchar(150) DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `basisOfRecord` varchar(32) DEFAULT 'PreservedSpecimen' COMMENT 'PreservedSpecimen, LivingSpecimen, HumanObservation',
  `occurrenceID` varchar(255) DEFAULT NULL COMMENT 'UniqueGlobalIdentifier',
  `catalogNumber` varchar(32) DEFAULT NULL,
  `otherCatalogNumbers` varchar(255) DEFAULT NULL,
  `ownerInstitutionCode` varchar(32) DEFAULT NULL,
  `institutionID` varchar(255) DEFAULT NULL,
  `collectionID` varchar(255) DEFAULT NULL,
  `datasetID` varchar(255) DEFAULT NULL,
  `institutionCode` varchar(64) DEFAULT NULL,
  `collectionCode` varchar(64) DEFAULT NULL,
  `family` varchar(255) DEFAULT NULL,
  `scientificName` varchar(255) DEFAULT NULL,
  `sciname` varchar(255) DEFAULT NULL,
  `tidinterpreted` int(10) unsigned DEFAULT NULL,
  `genus` varchar(255) DEFAULT NULL,
  `specificEpithet` varchar(255) DEFAULT NULL,
  `taxonRank` varchar(32) DEFAULT NULL,
  `infraspecificEpithet` varchar(255) DEFAULT NULL,
  `scientificNameAuthorship` varchar(255) DEFAULT NULL,
  `taxonRemarks` text,
  `identifiedBy` varchar(255) DEFAULT NULL,
  `dateIdentified` varchar(45) DEFAULT NULL,
  `identificationReferences` text,
  `identificationRemarks` text,
  `identificationQualifier` varchar(255) DEFAULT NULL COMMENT 'cf, aff, etc',
  `typeStatus` varchar(255) DEFAULT NULL,
  `recordedBy` varchar(255) DEFAULT NULL COMMENT 'Collector(s)',
  `recordNumberPrefix` varchar(45) DEFAULT NULL,
  `recordNumberSuffix` varchar(45) DEFAULT NULL,
  `recordNumber` varchar(32) DEFAULT NULL COMMENT 'Collector Number',
  `CollectorFamilyName` varchar(255) DEFAULT NULL COMMENT 'not DwC',
  `CollectorInitials` varchar(255) DEFAULT NULL COMMENT 'not DwC',
  `associatedCollectors` varchar(255) DEFAULT NULL COMMENT 'not DwC',
  `eventDate` date DEFAULT NULL,
  `year` int(10) DEFAULT NULL,
  `month` int(10) DEFAULT NULL,
  `day` int(10) DEFAULT NULL,
  `startDayOfYear` int(10) DEFAULT NULL,
  `endDayOfYear` int(10) DEFAULT NULL,
  `latestDateCollected` date DEFAULT NULL,
  `verbatimEventDate` varchar(255) DEFAULT NULL,
  `habitat` text COMMENT 'Habitat, substrait, etc',
  `substrate` varchar(500) DEFAULT NULL,
  `fieldNotes` text,
  `fieldnumber` varchar(45) DEFAULT NULL,
  `occurrenceRemarks` text COMMENT 'General Notes',
  `informationWithheld` varchar(250) DEFAULT NULL,
  `dataGeneralizations` varchar(250) DEFAULT NULL,
  `associatedOccurrences` text,
  `associatedMedia` text,
  `associatedReferences` text,
  `associatedSequences` text,
  `associatedTaxa` text COMMENT 'Associated Species',
  `dynamicProperties` text COMMENT 'Plant Description?',
  `verbatimAttributes` text,
  `behavior` varchar(500) DEFAULT NULL,
  `reproductiveCondition` varchar(255) DEFAULT NULL COMMENT 'Phenology: flowers, fruit, sterile',
  `cultivationStatus` int(10) DEFAULT NULL COMMENT '0 = wild, 1 = cultivated',
  `establishmentMeans` varchar(32) DEFAULT NULL COMMENT 'cultivated, invasive, escaped from captivity, wild, native',
  `lifeStage` varchar(45) DEFAULT NULL,
  `sex` varchar(45) DEFAULT NULL,
  `individualCount` varchar(45) DEFAULT NULL,
  `samplingProtocol` varchar(100) DEFAULT NULL,
  `samplingEffort` varchar(200) DEFAULT NULL,
  `preparations` varchar(100) DEFAULT NULL,
  `country` varchar(64) DEFAULT NULL,
  `stateProvince` varchar(255) DEFAULT NULL,
  `county` varchar(255) DEFAULT NULL,
  `municipality` varchar(255) DEFAULT NULL,
  `locality` text,
  `localitySecurity` int(10) DEFAULT '0' COMMENT '0 = display locality, 1 = hide locality',
  `localitySecurityReason` varchar(100) DEFAULT NULL,
  `decimalLatitude` double DEFAULT NULL,
  `decimalLongitude` double DEFAULT NULL,
  `geodeticDatum` varchar(255) DEFAULT NULL,
  `coordinateUncertaintyInMeters` int(10) unsigned DEFAULT NULL,
  `footprintWKT` text,
  `coordinatePrecision` decimal(9,7) DEFAULT NULL,
  `locationRemarks` text,
  `verbatimCoordinates` varchar(255) DEFAULT NULL,
  `verbatimCoordinateSystem` varchar(255) DEFAULT NULL,
  `latDeg` int(11) DEFAULT NULL,
  `latMin` double DEFAULT NULL,
  `latSec` double DEFAULT NULL,
  `latNS` varchar(3) DEFAULT NULL,
  `lngDeg` int(11) DEFAULT NULL,
  `lngMin` double DEFAULT NULL,
  `lngSec` double DEFAULT NULL,
  `lngEW` varchar(3) DEFAULT NULL,
  `verbatimLatitude` varchar(45) DEFAULT NULL,
  `verbatimLongitude` varchar(45) DEFAULT NULL,
  `UtmNorthing` varchar(45) DEFAULT NULL,
  `UtmEasting` varchar(45) DEFAULT NULL,
  `UtmZoning` varchar(45) DEFAULT NULL,
  `trsTownship` varchar(45) DEFAULT NULL,
  `trsRange` varchar(45) DEFAULT NULL,
  `trsSection` varchar(45) DEFAULT NULL,
  `trsSectionDetails` varchar(45) DEFAULT NULL,
  `georeferencedBy` varchar(255) DEFAULT NULL,
  `georeferenceProtocol` varchar(255) DEFAULT NULL,
  `georeferenceSources` varchar(255) DEFAULT NULL,
  `georeferenceVerificationStatus` varchar(32) DEFAULT NULL,
  `georeferenceRemarks` varchar(255) DEFAULT NULL,
  `minimumElevationInMeters` int(6) DEFAULT NULL,
  `maximumElevationInMeters` int(6) DEFAULT NULL,
  `elevationNumber` varchar(45) DEFAULT NULL,
  `elevationUnits` varchar(45) DEFAULT NULL,
  `verbatimElevation` varchar(255) DEFAULT NULL,
  `minimumDepthInMeters` int(11) DEFAULT NULL,
  `maximumDepthInMeters` int(11) DEFAULT NULL,
  `verbatimDepth` varchar(50) DEFAULT NULL,
  `previousIdentifications` text,
  `disposition` varchar(32) DEFAULT NULL COMMENT 'Dups to',
  `storageLocation` varchar(100) DEFAULT NULL,
  `genericcolumn1` varchar(100) DEFAULT NULL,
  `genericcolumn2` varchar(100) DEFAULT NULL,
  `modified` datetime DEFAULT NULL COMMENT 'DateLastModified',
  `language` varchar(20) DEFAULT NULL,
  `recordEnteredBy` varchar(250) DEFAULT NULL,
  `duplicateQuantity` int(10) unsigned DEFAULT NULL,
  `labelProject` varchar(45) DEFAULT NULL,
  `processingStatus` varchar(45) DEFAULT NULL,
  `tempfield01` text,
  `tempfield02` text,
  `tempfield03` text,
  `tempfield04` text,
  `tempfield05` text,
  `tempfield06` text,
  `tempfield07` text,
  `tempfield08` text,
  `tempfield09` text,
  `tempfield10` text,
  `tempfield11` text,
  `tempfield12` text,
  `tempfield13` text,
  `tempfield14` text,
  `tempfield15` text,
  `initialTimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `FK_uploadspectemp_coll` (`collid`),
  KEY `Index_uploadspectemp_occid` (`occid`),
  KEY `Index_uploadspectemp_dbpk` (`dbpk`),
  KEY `Index_uploadspec_sciname` (`sciname`),
  KEY `Index_uploadspec_catalognumber` (`catalogNumber`),
  CONSTRAINT `FK_uploadspectemp_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploadtaxa`
--

DROP TABLE IF EXISTS `uploadtaxa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploadtaxa` (
  `TID` int(10) unsigned DEFAULT NULL,
  `SourceId` int(10) unsigned DEFAULT NULL,
  `KingdomID` tinyint(3) unsigned DEFAULT '3',
  `Family` varchar(50) DEFAULT NULL,
  `RankId` smallint(5) DEFAULT NULL,
  `scinameinput` varchar(250) NOT NULL,
  `SciName` varchar(250) DEFAULT NULL,
  `UnitInd1` varchar(1) DEFAULT NULL,
  `UnitName1` varchar(50) DEFAULT NULL,
  `UnitInd2` varchar(1) DEFAULT NULL,
  `UnitName2` varchar(50) DEFAULT NULL,
  `UnitInd3` varchar(7) DEFAULT NULL,
  `UnitName3` varchar(35) DEFAULT NULL,
  `Author` varchar(100) DEFAULT NULL,
  `InfraAuthor` varchar(100) DEFAULT NULL,
  `Acceptance` int(10) unsigned DEFAULT '1' COMMENT '0 = not accepted; 1 = accepted',
  `TidAccepted` int(10) unsigned DEFAULT NULL,
  `AcceptedStr` varchar(250) DEFAULT NULL,
  `SourceAcceptedId` int(10) unsigned DEFAULT NULL,
  `UnacceptabilityReason` varchar(24) DEFAULT NULL,
  `ParentTid` int(10) DEFAULT NULL,
  `ParentStr` varchar(250) DEFAULT NULL,
  `SourceParentId` int(10) unsigned DEFAULT NULL,
  `SecurityStatus` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '0 = no security; 1 = hidden locality',
  `Source` varchar(250) DEFAULT NULL,
  `Notes` varchar(250) DEFAULT NULL,
  `vernacular` varchar(250) DEFAULT NULL,
  `vernlang` varchar(15) DEFAULT NULL,
  `Hybrid` varchar(50) DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `sourceID_index` (`SourceId`),
  KEY `sourceAcceptedId_index` (`SourceAcceptedId`),
  KEY `sciname_index` (`SciName`),
  KEY `scinameinput_index` (`scinameinput`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userlogin`
--

DROP TABLE IF EXISTS `userlogin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userlogin` (
  `uid` int(10) unsigned NOT NULL,
  `username` varchar(45) NOT NULL,
  `password` varchar(45) NOT NULL,
  `alias` varchar(45) DEFAULT NULL,
  `lastlogindate` datetime DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`username`) USING BTREE,
  UNIQUE KEY `Index_userlogin_unique` (`alias`),
  KEY `FK_login_user` (`uid`),
  CONSTRAINT `FK_login_user` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userroles`
--

DROP TABLE IF EXISTS `userroles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userroles` (
  `userroleid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `role` varchar(45) NOT NULL,
  `tablename` varchar(45) DEFAULT NULL,
  `tablepk` int(11) DEFAULT NULL,
  `secondaryVariable` varchar(45) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `uidassignedby` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`userroleid`),
  KEY `FK_userroles_uid_idx` (`uid`),
  KEY `FK_usrroles_uid2_idx` (`uidassignedby`),
  KEY `Index_userroles_table` (`tablename`,`tablepk`),
  CONSTRAINT `FK_userrole_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_userrole_uid_assigned` FOREIGN KEY (`uidassignedby`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `firstname` varchar(45) DEFAULT NULL,
  `lastname` varchar(45) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `institution` varchar(200) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip` varchar(15) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `phone` varchar(45) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `RegionOfInterest` varchar(45) DEFAULT NULL,
  `url` varchar(400) DEFAULT NULL,
  `Biography` varchar(1500) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `ispublic` int(10) unsigned NOT NULL DEFAULT '0',
  `defaultrights` varchar(250) DEFAULT NULL,
  `rightsholder` varchar(250) DEFAULT NULL,
  `rights` varchar(250) DEFAULT NULL,
  `accessrights` varchar(250) DEFAULT NULL,
  `validated` varchar(45) NOT NULL DEFAULT '0',
  `usergroups` varchar(100) DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `Index_email` (`email`,`lastname`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usertaxonomy`
--

DROP TABLE IF EXISTS `usertaxonomy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usertaxonomy` (
  `idusertaxonomy` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `taxauthid` int(10) unsigned NOT NULL DEFAULT '1',
  `editorstatus` varchar(45) DEFAULT NULL,
  `geographicScope` varchar(250) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned NOT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idusertaxonomy`),
  UNIQUE KEY `usertaxonomy_UNIQUE` (`uid`,`tid`,`taxauthid`,`editorstatus`),
  KEY `FK_usertaxonomy_uid_idx` (`uid`),
  KEY `FK_usertaxonomy_tid_idx` (`tid`),
  KEY `FK_usertaxonomy_taxauthid_idx` (`taxauthid`),
  CONSTRAINT `FK_usertaxonomy_taxauthid` FOREIGN KEY (`taxauthid`) REFERENCES `taxauthority` (`taxauthid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_usertaxonomy_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_usertaxonomy_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'symbtest'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-02-19 22:35:51


-- Prime some tables with default data values
INSERT INTO `schemaversion` (versionnumber) values ("1.0");

-- Create the general admin user
INSERT INTO `users`(uid,firstname,lastname,state,country,email) VALUES (1,"General","Administrator","NA","NA","NA");
INSERT INTO `userlogin`(uid,username,password) VALUES (1,"admin",password("admin"));
INSERT INTO `userroles`(uid,role) VALUES (1,"SuperAdmin");

-- Prime taxonunits table
INSERT IGNORE INTO `taxonunits`(kingdomName,rankid,rankName, dirparentrankid, reqparentrankid) 
  VALUES ("Organism",1,"Organism",1,1),("Organism",10,"Kingdom",1,1),("Organism",20,"Subkingdom",10,10),("Organism",30,"Division",20,10),("Organism",40,"Subdivision",30,30),("Organism",50,"Superclass",40,30),("Organism",60,"Class",50,30),("Organism",70,"Subclass",60,60),("Organism",100,"Order",70,60),("Organism",110,"Suborder",100,100),("Organism",140,"Family",110,100),("Organism",150,"Subfamily",140,140),("Organism",160,"Tribe",150,140),("Organism",170,"Subtribe",160,140),("Organism",180,"Genus",170,140),("Organism",190,"Subgenus",180,180),("Organism",200,"Section",190,180),("Organism",210,"Subsection",200,180),("Organism",220,"Species",210,180),("Organism",230,"Subspecies",220,180),("Organism",240,"Variety",220,180),("Organism",250,"Subvariety",240,180),("Organism",260,"Form",220,180),("Organism",270,"Subform",260,180),("Organism",300,"Cultivated",220,220);
INSERT IGNORE INTO `taxonunits`(kingdomName,rankid,rankname, dirparentrankid, reqparentrankid) 
  VALUES ("Monera",1,"Organism",1,1),("Monera",10,"Kingdom",1,1),("Monera",20,"Subkingdom",10,10),("Monera",30,"Phylum",20,10),("Monera",40,"Subphylum",30,30),("Monera",60,"Class",50,30),("Monera",70,"Subclass",60,60),("Monera",100,"Order",70,60),("Monera",110,"Suborder",100,100),("Monera",140,"Family",110,100),("Monera",150,"Subfamily",140,140),("Monera",160,"Tribe",150,140),("Monera",170,"Subtribe",160,140),("Monera",180,"Genus",170,140),("Monera",190,"Subgenus",180,180),("Monera",220,"Species",210,180),("Monera",230,"Subspecies",220,180),("Monera",240,"Morph",220,180);
INSERT IGNORE INTO `taxonunits`(kingdomName,rankid,rankname, dirparentrankid, reqparentrankid) 
  VALUES ("Protista",1,"Organism",1,1),("Protista",10,"Kingdom",1,1),("Protista",20,"Subkingdom",10,10),("Protista",30,"Phylum",20,10),("Protista",40,"Subphylum",30,30),("Protista",60,"Class",50,30),("Protista",70,"Subclass",60,60),("Protista",100,"Order",70,60),("Protista",110,"Suborder",100,100),("Protista",140,"Family",110,100),("Protista",150,"Subfamily",140,140),("Protista",160,"Tribe",150,140),("Protista",170,"Subtribe",160,140),("Protista",180,"Genus",170,140),("Protista",190,"Subgenus",180,180),("Protista",220,"Species",210,180),("Protista",230,"Subspecies",220,180),("Protista",240,"Morph",220,180);
INSERT IGNORE INTO `taxonunits`(kingdomName,rankid,rankname, dirparentrankid, reqparentrankid) 
  VALUES ("Plantae",1,"Organism",1,1),("Plantae",10,"Kingdom",1,1),("Plantae",20,"Subkingdom",10,10),("Plantae",30,"Division",20,10),("Plantae",40,"Subdivision",30,30),("Plantae",50,"Superclass",40,30),("Plantae",60,"Class",50,30),("Plantae",70,"Subclass",60,60),("Plantae",100,"Order",70,60),("Plantae",110,"Suborder",100,100),("Plantae",140,"Family",110,100),("Plantae",150,"Subfamily",140,140),("Plantae",160,"Tribe",150,140),("Plantae",170,"Subtribe",160,140),("Plantae",180,"Genus",170,140),("Plantae",190,"Subgenus",180,180),("Plantae",200,"Section",190,180),("Plantae",210,"Subsection",200,180),("Plantae",220,"Species",210,180),("Plantae",230,"Subspecies",220,180),("Plantae",240,"Variety",220,180),("Plantae",250,"Subvariety",240,180),("Plantae",260,"Form",220,180),("Plantae",270,"Subform",260,180),("Plantae",300,"Cultivated",220,220);
INSERT IGNORE INTO `taxonunits`(kingdomName,rankid,rankname, dirparentrankid, reqparentrankid) 
  VALUES ("Fungi",1,"Organism",1,1),("Fungi",10,"Kingdom",1,1),("Fungi",20,"Subkingdom",10,10),("Fungi",30,"Division",20,10),("Fungi",40,"Subdivision",30,30),("Fungi",50,"Superclass",40,30),("Fungi",60,"Class",50,30),("Fungi",70,"Subclass",60,60),("Fungi",100,"Order",70,60),("Fungi",110,"Suborder",100,100),("Fungi",140,"Family",110,100),("Fungi",150,"Subfamily",140,140),("Fungi",160,"Tribe",150,140),("Fungi",170,"Subtribe",160,140),("Fungi",180,"Genus",170,140),("Fungi",190,"Subgenus",180,180),("Fungi",200,"Section",190,180),("Fungi",210,"Subsection",200,180),("Fungi",220,"Species",210,180),("Fungi",230,"Subspecies",220,180),("Fungi",240,"Variety",220,180),("Fungi",250,"Subvariety",240,180),("Fungi",260,"Form",220,180),("Fungi",270,"Subform",260,180),("Fungi",300,"Cultivated",220,220);
INSERT IGNORE INTO `taxonunits`(kingdomName,rankid,rankname, dirparentrankid, reqparentrankid) 
  VALUES ("Animalia",1,"Organism",1,1),("Animalia",10,"Kingdom",1,1),("Animalia",20,"Subkingdom",10,10),("Animalia",30,"Phylum",20,10),("Animalia",40,"Subphylum",30,30),("Animalia",60,"Class",50,30),("Animalia",70,"Subclass",60,60),("Animalia",100,"Order",70,60),("Animalia",110,"Suborder",100,100),("Animalia",140,"Family",110,100),("Animalia",150,"Subfamily",140,140),("Animalia",160,"Tribe",150,140),("Animalia",170,"Subtribe",160,140),("Animalia",180,"Genus",170,140),("Animalia",190,"Subgenus",180,180),("Animalia",220,"Species",210,180),("Animalia",230,"Subspecies",220,180),("Animalia",240,"Morph",220,180);

INSERT INTO `taxauthority` (`taxauthid`, `isprimary`, `name`) VALUES ('1', '1', 'Central Thesaurus');

INSERT INTO `taxa` (`TID`, `RankId`, `SciName`, `UnitName1`) VALUES ("1", "1", "Organism", "Organism");
INSERT INTO `taxa` (`TID`, `RankId`, `SciName`, `UnitName1`) VALUES ("2", "10", "Monera", "Monera");
INSERT INTO `taxa` (`TID`, `RankId`, `SciName`, `UnitName1`) VALUES ("3", "10", "Protista", "Protista");
INSERT INTO `taxa` (`TID`, `RankId`, `SciName`, `UnitName1`) VALUES ("4", "10", "Plantae", "Plantae");
INSERT INTO `taxa` (`TID`, `RankId`, `SciName`, `UnitName1`) VALUES ("5", "10", "Fungi", "Fungi");
INSERT INTO `taxa` (`TID`, `RankId`, `SciName`, `UnitName1`) VALUES ("6", "10", "Animalia", "Animalia");

INSERT INTO `taxstatus` (`tid`, `tidaccepted`, `taxauthid`, `parenttid`) VALUES ("1", "1", "1", "1");
INSERT INTO `taxstatus` (`tid`, `tidaccepted`, `taxauthid`, `parenttid`) VALUES ("2", "2", "1", "1");
INSERT INTO `taxstatus` (`tid`, `tidaccepted`, `taxauthid`, `parenttid`) VALUES ("3", "3", "1", "1");
INSERT INTO `taxstatus` (`tid`, `tidaccepted`, `taxauthid`, `parenttid`) VALUES ("4", "4", "1", "1");
INSERT INTO `taxstatus` (`tid`, `tidaccepted`, `taxauthid`, `parenttid`) VALUES ("5", "5", "1", "1");
INSERT INTO `taxstatus` (`tid`, `tidaccepted`, `taxauthid`, `parenttid`) VALUES ("6", "6", "1", "1");


-- Geographic thesaurus tables
INSERT  INTO `lkupcountry`(countryId, countryName, iso, iso3, numcode) VALUES (1,'Andorra','AD','AND',20),(2,'United Arab Emirates','AE','ARE',784),(3,'Afghanistan','AF','AFG',4),(4,'Antigua and Barbuda','AG','ATG',28),(5,'Anguilla','AI','AIA',660),(6,'Albania','AL','ALB',8),(7,'Armenia','AM','ARM',51),(8,'Netherlands Antilles','AN','ANT',530),(9,'Angola','AO','AGO',24),(10,'Antarctica','AQ',NULL,NULL),(11,'Argentina','AR','ARG',32),(12,'American Samoa','AS','ASM',16),(13,'Austria','AT','AUT',40),(14,'Australia','AU','AUS',36),(15,'Aruba','AW','ABW',533),(16,'Azerbaijan','AZ','AZE',31),(17,'Bosnia and Herzegovina','BA','BIH',70),(18,'Barbados','BB','BRB',52),(19,'Bangladesh','BD','BGD',50),(20,'Belgium','BE','BEL',56),(21,'Burkina Faso','BF','BFA',854),(22,'Bulgaria','BG','BGR',100),(23,'Bahrain','BH','BHR',48),(24,'Burundi','BI','BDI',108),(25,'Benin','BJ','BEN',204),(26,'Bermuda','BM','BMU',60),(27,'Brunei Darussalam','BN','BRN',96),(28,'Bolivia','BO','BOL',68),(29,'Brazil','BR','BRA',76),(30,'Bahamas','BS','BHS',44),(31,'Bhutan','BT','BTN',64),(32,'Bouvet Island','BV',NULL,NULL),(33,'Botswana','BW','BWA',72),(34,'Belarus','BY','BLR',112),(35,'Belize','BZ','BLZ',84),(36,'Canada','CA','CAN',124),(37,'Cocos (Keeling) Islands','CC',NULL,NULL),(38,'Congo, the Democratic Republic of the','CD','COD',180),(39,'Central African Republic','CF','CAF',140),(40,'Congo','CG','COG',178),(41,'Switzerland','CH','CHE',756),(42,'Cote D\'Ivoire','CI','CIV',384),(43,'Cook Islands','CK','COK',184),(44,'Chile','CL','CHL',152),(45,'Cameroon','CM','CMR',120),(46,'China','CN','CHN',156),(47,'Colombia','CO','COL',170),(48,'Costa Rica','CR','CRI',188),(49,'Serbia and Montenegro','CS',NULL,NULL),(50,'Cuba','CU','CUB',192),(51,'Cape Verde','CV','CPV',132),(52,'Christmas Island','CX',NULL,NULL),(53,'Cyprus','CY','CYP',196),(54,'Czech Republic','CZ','CZE',203),(55,'Germany','DE','DEU',276),(56,'Djibouti','DJ','DJI',262),(57,'Denmark','DK','DNK',208),(58,'Dominica','DM','DMA',212),(59,'Dominican Republic','DO','DOM',214),(60,'Algeria','DZ','DZA',12),(61,'Ecuador','EC','ECU',218),(62,'Estonia','EE','EST',233),(63,'Egypt','EG','EGY',818),(64,'Western Sahara','EH','ESH',732),(65,'Eritrea','ER','ERI',232),(66,'Spain','ES','ESP',724),(67,'Ethiopia','ET','ETH',231),(68,'Finland','FI','FIN',246),(69,'Fiji','FJ','FJI',242),(70,'Falkland  Islands (Malvinas)','FK','FLK',238),(71,'Micronesia, Federated States of','FM','FSM',583),(72,'Faroe Islands','FO','FRO',234);
INSERT  INTO `lkupcountry`(countryId, countryName, iso, iso3, numcode) VALUES (73,'France','FR','FRA',250),(74,'Gabon','GA','GAB',266),(75,'United Kingdom','GB','GBR',826),(76,'Grenada','GD','GRD',308),(77,'Georgia','GE','GEO',268),(78,'French Guiana','GF','GUF',254),(79,'Ghana','GH','GHA',288),(80,'Gibraltar','GI','GIB',292),(81,'Greenland','GL','GRL',304),(82,'Gambia','GM','GMB',270),(83,'Guinea','GN','GIN',324),(84,'Guadeloupe','GP','GLP',312),(85,'Equatorial Guinea','GQ','GNQ',226),(86,'Greece','GR','GRC',300),(87,'South Georgia and the South Sandwich Islands','GS',NULL,NULL),(88,'Guatemala','GT','GTM',320),(89,'Guam','GU','GUM',316),(90,'Guinea-Bissau','GW','GNB',624),(91,'Guyana','GY','GUY',328),(92,'Hong Kong','HK','HKG',344),(93,'Heard Island and Mcdonald Islands','HM',NULL,NULL),(94,'Honduras','HN','HND',340),(95,'Croatia','HR','HRV',191),(96,'Haiti','HT','HTI',332),(97,'Hungary','HU','HUN',348),(98,'Indonesia','ID','IDN',360),(99,'Ireland','IE','IRL',372),(100,'Israel','IL','ISR',376),(101,'India','IN','IND',356),(102,'British Indian Ocean Territory','IO',NULL,NULL),(103,'Iraq','IQ','IRQ',368),(104,'Iran, Islamic Republic of','IR','IRN',364),(105,'Iceland','IS','ISL',352),(106,'Italy','IT','ITA',380),(107,'Jamaica','JM','JAM',388),(108,'Jordan','JO','JOR',400),(109,'Japan','JP','JPN',392),(110,'Kenya','KE','KEN',404),(111,'Kyrgyzstan','KG','KGZ',417),(112,'Cambodia','KH','KHM',116),(113,'Kiribati','KI','KIR',296),(114,'Comoros','KM','COM',174),(115,'Saint Kitts and Nevis','KN','KNA',659),(116,'Korea, Democratic People\'s Republic of','KP','PRK',408),(117,'Korea, Republic of','KR','KOR',410),(118,'Kuwait','KW','KWT',414),(119,'Cayman Islands','KY','CYM',136),(120,'Kazakhstan','KZ','KAZ',398),(121,'Lao People\'s Democratic Republic','LA','LAO',418),(122,'Lebanon','LB','LBN',422),(123,'Saint Lucia','LC','LCA',662),(124,'Liechtenstein','LI','LIE',438),(125,'Sri Lanka','LK','LKA',144),(126,'Liberia','LR','LBR',430),(127,'Lesotho','LS','LSO',426),(128,'Lithuania','LT','LTU',440),(129,'Luxembourg','LU','LUX',442),(130,'Latvia','LV','LVA',428),(131,'Libyan Arab Jamahiriya','LY','LBY',434),(132,'Morocco','MA','MAR',504),(133,'Monaco','MC','MCO',492),(134,'Moldova, Republic of','MD','MDA',498),(135,'Madagascar','MG','MDG',450),(136,'Marshall Islands','MH','MHL',584),(137,'Macedonia, the Former Yugoslav Republic of','MK','MKD',807),(138,'Mali','ML','MLI',466),(139,'Myanmar','MM','MMR',104),(140,'Mongolia','MN','MNG',496),(141,'Macao','MO','MAC',446);
INSERT  INTO `lkupcountry`(countryId, countryName, iso, iso3, numcode) VALUES (142,'Northern Mariana Islands','MP','MNP',580),(143,'Martinique','MQ','MTQ',474),(144,'Mauritania','MR','MRT',478),(145,'Montserrat','MS','MSR',500),(146,'Malta','MT','MLT',470),(147,'Mauritius','MU','MUS',480),(148,'Maldives','MV','MDV',462),(149,'Malawi','MW','MWI',454),(150,'Mexico','MX','MEX',484),(151,'Malaysia','MY','MYS',458),(152,'Mozambique','MZ','MOZ',508),(153,'Namibia','NA','NAM',516),(154,'New Caledonia','NC','NCL',540),(155,'Niger','NE','NER',562),(156,'Norfolk Island','NF','NFK',574),(157,'Nigeria','NG','NGA',566),(158,'Nicaragua','NI','NIC',558),(159,'Netherlands','NL','NLD',528),(160,'Norway','NO','NOR',578),(161,'Nepal','NP','NPL',524),(162,'Nauru','NR','NRU',520),(163,'Niue','NU','NIU',570),(164,'New Zealand','NZ','NZL',554),(165,'Oman','OM','OMN',512),(166,'Panama','PA','PAN',591),(167,'Peru','PE','PER',604),(168,'French Polynesia','PF','PYF',258),(169,'Papua New Guinea','PG','PNG',598),(170,'Philippines','PH','PHL',608),(171,'Pakistan','PK','PAK',586),(172,'Poland','PL','POL',616),(173,'Saint Pierre and Miquelon','PM','SPM',666),(174,'Pitcairn','PN','PCN',612),(175,'Puerto Rico','PR','PRI',630),(176,'Palestinian Territory, Occupied','PS',NULL,NULL),(177,'Portugal','PT','PRT',620),(178,'Palau','PW','PLW',585),(179,'Paraguay','PY','PRY',600),(180,'Qatar','QA','QAT',634),(181,'Reunion','RE','REU',638),(182,'Romania','RO','ROM',642),(183,'Russian Federation','RU','RUS',643),(184,'Rwanda','RW','RWA',646),(185,'Saudi Arabia','SA','SAU',682),(186,'Solomon Islands','SB','SLB',90),(187,'Seychelles','SC','SYC',690),(188,'Sudan','SD','SDN',736),(189,'Sweden','SE','SWE',752),(190,'Singapore','SG','SGP',702),(191,'Saint Helena','SH','SHN',654),(192,'Slovenia','SI','SVN',705),(193,'Svalbard and Jan Mayen','SJ','SJM',744),(194,'Slovakia','SK','SVK',703),(195,'Sierra Leone','SL','SLE',694),(196,'San Marino','SM','SMR',674),(197,'Senegal','SN','SEN',686),(198,'Somalia','SO','SOM',706),(199,'Suriname','SR','SUR',740),(200,'Sao Tome and Principe','ST','STP',678),(201,'El Salvador','SV','SLV',222),(202,'Syrian Arab Republic','SY','SYR',760),(203,'Swaziland','SZ','SWZ',748),(204,'Turks and Caicos Islands','TC','TCA',796),(205,'Chad','TD','TCD',148),(206,'French Southern Territories','TF',NULL,NULL),(207,'Togo','TG','TGO',768),(208,'Thailand','TH','THA',764),(209,'Tajikistan','TJ','TJK',762),(210,'Tokelau','TK','TKL',772),(211,'Timor-Leste','TL',NULL,NULL);
INSERT  INTO `lkupcountry`(countryId, countryName, iso, iso3, numcode) VALUES (212,'Turkmenistan','TM','TKM',795),(213,'Tunisia','TN','TUN',788),(214,'Tonga','TO','TON',776),(215,'Turkey','TR','TUR',792),(216,'Trinidad and Tobago','TT','TTO',780),(217,'Tuvalu','TV','TUV',798),(218,'Taiwan, Province of China','TW','TWN',158),(219,'Tanzania, United Republic of','TZ','TZA',834),(220,'Ukraine','UA','UKR',804),(221,'Uganda','UG','UGA',800),(222,'United States Minor Outlying Islands','UM',NULL,NULL),(223,'United States','US','USA',840),(224,'Uruguay','UY','URY',858),(225,'Uzbekistan','UZ','UZB',860),(226,'Holy See (Vatican City State)','VA','VAT',336),(227,'Saint Vincent and the Grenadines','VC','VCT',670),(228,'Venezuela','VE','VEN',862),(229,'Virgin Islands, British','VG','VGB',92),(230,'Virgin Islands,  U.s.','VI','VIR',850),(231,'Viet Nam','VN','VNM',704),(232,'Vanuatu','VU','VUT',548),(233,'Wallis and Futuna','WF','WLF',876),(234,'Samoa','WS','WSM',882),(235,'Yemen','YE','YEM',887),(236,'Mayotte','YT',NULL,NULL),(237,'South Africa','ZA','ZAF',710),(238,'Zambia','ZM','ZMB',894),(239,'Zimbabwe','ZW','ZWE',716),(256,'USA','US','USA',840),(262,'Russia',NULL,NULL,NULL),(263,'Canary Islands','IC',NULL,NULL),(264,'Brasil','BR','BRA',76);

INSERT INTO `lkupstateprovince`(stateId, countryId, stateName, abbrev) VALUES (1,256,'Alaska','AK'),(2,256,'Alabama','AL'),(3,256,'American Samoa','AS'),(4,256,'Arizona','AZ'),(5,256,'Arkansas','AR'),(6,256,'California','CA'),(7,256,'Colorado','CO'),(8,256,'Connecticut','CT'),(9,256,'Delaware','DE'),(10,256,'District of Columbia','DC'),(11,256,'Federated States of Micronesia','FM'),(12,256,'Florida','FL'),(13,256,'Georgia','GA'),(14,256,'Guam','GU'),(15,256,'Hawaii','HI'),(16,256,'Idaho','ID'),(17,256,'Illinois','IL'),(18,256,'Indiana','IN'),(19,256,'Iowa','IA'),(20,256,'Kansas','KS'),(21,256,'Kentucky','KY'),(22,256,'Louisiana','LA'),(23,256,'Maine','ME'),(24,256,'Marshall Islands','MH'),(25,256,'Maryland','MD'),(26,256,'Massachusetts','MA'),(27,256,'Michigan','MI'),(28,256,'Minnesota','MN'),(29,256,'Mississippi','MS'),(30,256,'Missouri','MO'),(31,256,'Montana','MT'),(32,256,'Nebraska','NE'),(33,256,'Nevada','NV'),(34,256,'New Hampshire','NH'),(35,256,'New Jersey','NJ'),(36,256,'New Mexico','NM'),(37,256,'New York','NY'),(38,256,'North Carolina','NC'),(39,256,'North Dakota','ND'),(40,256,'Northern Mariana Islands','MP'),(41,256,'Ohio','OH'),(42,256,'Oklahoma','OK'),(43,256,'Oregon','OR'),(44,256,'Palau','PW'),(45,256,'Pennsylvania','PA'),(46,256,'Puerto Rico','PR'),(47,256,'Rhode Island','RI'),(48,256,'South Carolina','SC'),(49,256,'South Dakota','SD'),(50,256,'Tennessee','TN'),(51,256,'Texas','TX'),(52,256,'Utah','UT'),(53,256,'Vermont','VT'),(54,256,'Virgin Islands','VI'),(55,256,'Virginia','VA'),(56,256,'Washington','WA'),(57,256,'West Virginia','WV'),(58,256,'Wisconsin','WI'),(59,256,'Wyoming','WY'),(60,256,'Armed Forces Africa','AE'),(61,256,'Armed Forces Americas (except Canada)','AA'),(62,256,'Armed Forces Canada','AE'),(63,256,'Armed Forces Europe','AE'),(64,256,'Armed Forces Middle East','AE'),(65,256,'Armed Forces Pacific','AP'),(128,223,'Alaska','AK'),(129,223,'Alabama','AL'),(130,223,'American Samoa','AS'),(131,223,'Arizona','AZ'),(132,223,'Arkansas','AR'),(133,223,'California','CA'),(134,223,'Colorado','CO'),(135,223,'Connecticut','CT'),(136,223,'Delaware','DE'),(137,223,'District of Columbia','DC'),(138,223,'Federated States of Micronesia','FM'),(139,223,'Florida','FL'),(140,223,'Georgia','GA'),(141,223,'Guam','GU'),(142,223,'Hawaii','HI'),(143,223,'Idaho','ID'),(144,223,'Illinois','IL'),(145,223,'Indiana','IN'),(146,223,'Iowa','IA'),(147,223,'Kansas','KS'),(148,223,'Kentucky','KY'),(149,223,'Louisiana','LA'),(150,223,'Maine','ME'),(151,223,'Marshall Islands','MH'),(152,223,'Maryland','MD'),(153,223,'Massachusetts','MA'),(154,223,'Michigan','MI'),(155,223,'Minnesota','MN'),(156,223,'Mississippi','MS'),(157,223,'Missouri','MO'),(158,223,'Montana','MT'),(159,223,'Nebraska','NE'),(160,223,'Nevada','NV'),(161,223,'New Hampshire','NH'),(162,223,'New Jersey','NJ'),(163,223,'New Mexico','NM'),(164,223,'New York','NY'),(165,223,'North Carolina','NC'),(166,223,'North Dakota','ND'),(167,223,'Northern Mariana Islands','MP'),(168,223,'Ohio','OH'),(169,223,'Oklahoma','OK'),(170,223,'Oregon','OR'),(171,223,'Palau','PW'),(172,223,'Pennsylvania','PA'),(173,223,'Puerto Rico','PR'),(174,223,'Rhode Island','RI'),(175,223,'South Carolina','SC'),(176,223,'South Dakota','SD'),(177,223,'Tennessee','TN'),(178,223,'Texas','TX'),(179,223,'Utah','UT'),(180,223,'Vermont','VT'),(181,223,'Virgin Islands','VI'),(182,223,'Virginia','VA'),(183,223,'Washington','WA'),(184,223,'West Virginia','WV'),(185,223,'Wisconsin','WI'),(186,223,'Wyoming','WY'),(187,223,'Armed Forces Africa','AE'),(188,223,'Armed Forces Americas (except Canada)','AA'),(189,223,'Armed Forces Canada','AE'),(190,223,'Armed Forces Europe','AE'),(191,223,'Armed Forces Middle East','AE'),(192,223,'Armed Forces Pacific','AP'),(193,150,'Quintana Roo',NULL),(194,44,'Los Rios',NULL),(195,44,'Los Lagos',NULL),(196,44,'Araucania',NULL),(197,150,'Sonora',NULL),(198,150,'Baja California',NULL),(199,150,'San Luis Potosi',NULL),(200,150,'Sinaloa',NULL),(202,36,'Manitoba',NULL),(203,36,'British Columbia',NULL),(204,36,'Alberta',NULL),(205,150,'Coahuila',NULL),(206,150,'Chihuahua',NULL),(207,150,'Zacatecas',NULL),(208,150,'Nuevo Leon',NULL),(209,36,'Ottawa',NULL),(210,36,'Ontario',NULL),(211,12,'Sonora',NULL),(212,228,'Aragua',NULL),(214,150,'Tamaulipas',NULL),(215,150,'Hidalgo',NULL),(217,48,'San Jose',NULL),(218,29,'Espirito Santo',NULL),(219,11,'Neuquen',NULL),(221,44,'Magallanes y Antartica Chilena',NULL),(222,28,'Cochabamba',NULL),(223,28,'La Paz',NULL),(225,167,'Cajamarca',NULL),(228,29,'Rio Grande do Sul',NULL),(229,256,'Flagstaff',NULL),(230,29,'Acre',NULL),(231,150,'Aguascalientes',NULL),(232,44,'Aisen',NULL),(233,29,'Alagoas',NULL),(234,48,'Alajuela',NULL),(235,29,'Amapa',NULL),(236,29,'Amazonas',NULL),(237,47,'Amazonas',NULL),(238,167,'Amazonas',NULL),(239,228,'Amazonas',NULL),(240,167,'Ancash',NULL),(241,47,'Antioquia',NULL),(242,44,'Antofagasta',NULL),(243,228,'Anzoategui',NULL),(244,228,'Apure',NULL),(245,167,'Apurimac',NULL),(246,47,'Arauca',NULL),(247,167,'Arequipa',NULL),(248,44,'Arica y Parinacota',NULL),(249,44,'Atacama',NULL),(250,47,'Atlantico',NULL),(251,167,'Ayacucho',NULL),(252,29,'Bahia',NULL),(253,150,'Baja California Sur',NULL),(254,228,'Barinas',NULL),(255,28,'Beni',NULL),(256,44,'Bio Bio',NULL),(257,47,'Bolivar',NULL),(258,228,'Bolivar',NULL),(259,47,'Boyaca',NULL),(260,11,'Buenos Aires',NULL),(261,47,'Caldas',NULL),(262,167,'Callao',NULL),(263,150,'Campeche',NULL),(264,47,'Caqueta',NULL),(265,228,'Carabobo',NULL),(266,48,'Cartago',NULL),(267,47,'Casanare',NULL),(268,11,'Catamarca',NULL),(269,47,'Cauca',NULL),(270,29,'Ceara',NULL),(271,47,'Cesar',NULL),(272,11,'Chaco',NULL),(273,150,'Chiapas',NULL),(274,47,'Choco',NULL),(275,11,'Chubut',NULL),(276,28,'Chuquisaca',NULL),(277,228,'Cojedes',NULL),(278,150,'Colima',NULL),(279,44,'Coquimbo',NULL),(280,11,'Cordoba',NULL),(281,47,'Cordoba',NULL),(282,11,'Corrientes',NULL),(283,47,'Cundinamarca',NULL),(284,167,'Cuzco',NULL),(285,228,'Delta Amacuro',NULL),(286,47,'Distrito Capital',NULL),(287,11,'Distrito Federal',NULL),(288,29,'Distrito Federal',NULL),(289,150,'Distrito Federal',NULL),(290,150,'Durango',NULL),(291,11,'Entre Rios',NULL),(292,228,'Falcon',NULL),(293,11,'Formosa',NULL),(294,29,'Goias',NULL),(295,47,'Guainia',NULL),(296,48,'Guanacaste',NULL),(297,150,'Guanajuato',NULL),(298,228,'Guarico',NULL),(299,47,'Guaviare',NULL),(300,150,'Guerrero',NULL),(301,48,'Heredia',NULL),(302,167,'Huancavelica',NULL),(303,167,'Huanuco',NULL),(304,47,'Huila',NULL),(305,167,'Ica',NULL),(306,150,'Jalisco',NULL),(307,11,'Jujuy',NULL),(308,167,'Junin',NULL),(309,47,'La Guajira',NULL),(310,167,'La Libertad',NULL),(311,11,'La Pampa',NULL),(312,11,'La Rioja',NULL),(313,167,'Lambayeque',NULL),(314,228,'Lara',NULL),(315,167,'Lima',NULL),(316,48,'Limon',NULL),(317,167,'Loreto',NULL),(318,167,'Madre de Dios',NULL),(319,47,'Magdalena',NULL),(320,29,'Maranhao',NULL),(321,29,'Mato Grosso',NULL),(322,29,'Mato Grosso do Sul',NULL),(323,44,'Maule',NULL),(324,11,'Mendoza',NULL),(325,228,'Merida',NULL),(326,47,'Meta',NULL),(327,44,'Metropolitana',NULL),(328,150,'Mexico',NULL),(329,150,'Michoacan',NULL),(330,29,'Minas Gerais',NULL),(331,228,'Miranda',NULL),(332,11,'Misiones',NULL),(333,228,'Monagas',NULL),(334,167,'Moquegua',NULL),(335,150,'Morelos',NULL),(336,47,'Narino',NULL),(337,150,'Nayarit',NULL),(338,47,'Norte de Santander',NULL),(339,228,'Nueva Esparta',NULL),(340,150,'Oaxaca',NULL),(341,44,'O\'Higgins',NULL),(342,28,'Oruro',NULL),(343,28,'Pando',NULL),(344,29,'Para',NULL),(345,29,'Paraiba',NULL),(346,29,'Parana',NULL),(347,167,'Pasco',NULL),(348,29,'Pernambuco',NULL),(349,29,'Piaui',NULL),(350,167,'Piura',NULL),(351,228,'Portuguesa',NULL),(352,28,'Potosi',NULL),(353,150,'Puebla',NULL),(354,167,'Puno',NULL),(355,48,'Puntarenas',NULL),(356,47,'Putumayo',NULL),(357,150,'Queretaro',NULL),(358,47,'Quindio',NULL),(359,29,'Rio de Janeiro',NULL),(360,29,'Rio Grande do Norte',NULL),(361,11,'Rio Negro',NULL),(362,47,'Risaralda',NULL),(363,29,'Rondonia',NULL),(364,29,'Roraima',NULL),(365,11,'Salta',NULL),(366,47,'San Andres y Providencia',NULL),(367,11,'San Juan',NULL),(368,11,'San Luis',NULL),(369,167,'San Martin',NULL),(370,29,'Santa Catarina',NULL),(371,11,'Santa Cruz',NULL),(372,28,'Santa Cruz',NULL),(373,11,'Santa Fe',NULL),(374,47,'Santander',NULL),(375,11,'Santiago del Estero',NULL),(376,29,'Sao Paulo',NULL),(377,29,'Sergipe',NULL),(378,47,'Sucre',NULL),(379,228,'Sucre',NULL),(380,150,'Tabasco',NULL),(381,228,'Tachira',NULL),(382,167,'Tacna',NULL),(383,44,'Tarapaca',NULL),(384,28,'Tarija',NULL),(385,11,'Tierra del Fuego',NULL),(386,150,'Tlaxcala',NULL),(387,29,'Tocantins',NULL),(388,47,'Tolima',NULL),(389,228,'Trujillo',NULL),(390,11,'Tucuman',NULL),(391,167,'Tumbes',NULL),(392,167,'Ucayali',NULL),(393,47,'Valle del Cauca',NULL),(394,44,'Valparaiso',NULL),(395,228,'Vargas',NULL),(396,47,'Vaupes',NULL),(397,150,'Veracruz',NULL),(398,47,'Vichada',NULL),(399,228,'Yaracuy',NULL),(400,150,'Yucatan',NULL),(401,228,'Zulia',NULL),(403,36,'Newfoundland and Labrador',NULL),(404,55,'Nordrhein-Westfalen',NULL),(406,12,'Mexico',NULL),(407,61,'Pichincha',NULL),(409,159,'Overijssel',NULL),(410,66,'Castile-La Mancha',NULL),(411,166,'Panama',NULL),(413,88,'Chiquimula',NULL),(414,94,'Comayagua',NULL),(415,4,'Saint George',NULL),(416,143,'La Trinite',NULL),(417,107,'Saint James',NULL),(418,179,'Alto Parana',NULL),(419,61,'Napo',NULL),(421,179,'Caaguazu',NULL),(422,101,'Punjab',NULL),(424,125,'Central',NULL),(425,79,'Tarkwa',NULL),(426,79,'Western',NULL),(427,109,'Miyagi',NULL),(428,161,'Sagarmatha',NULL),(429,35,'Cayo',NULL),(430,120,'Aktobe',NULL),(431,161,'Dhawalagiri',NULL),(432,68,'Unknown',NULL),(433,192,'Unknown',NULL),(434,179,'Paraguari',NULL),(435,179,'Cordillera',NULL),(436,179,'Itapua',NULL),(437,91,'Upper Takutu-Upper Essequibo',NULL),(438,91,'Cuyuni-Mazaruni',NULL),(439,59,'Elias Pina',NULL),(440,59,'Azua',NULL),(442,59,'La Vega',NULL),(443,61,'Chimborazo',NULL),(444,88,'Peten',NULL),(445,201,'Chalatenango',NULL),(446,224,'Maldonado',NULL),(447,172,'Pomerania',NULL),(448,189,'unknown',NULL),(449,55,'Saxony',NULL),(450,189,'SkÃƒÂ¥ne',NULL),(451,256,'D.C.',NULL),(453,224,'Artigas',NULL),(454,224,'Canelones',NULL),(455,224,'Cerro Largo',NULL),(456,224,'Colonia',NULL),(457,224,'Durazno',NULL),(458,224,'Flores',NULL),(459,224,'Florida',NULL),(460,224,'Lavalleja',NULL),(461,224,'Montevideo',NULL),(462,224,'Paysandu',NULL),(463,224,'Rio Negro',NULL),(464,224,'Rivera',NULL),(465,224,'Rocha',NULL),(466,224,'Salto',NULL),(467,224,'San Jose',NULL),(468,224,'Soriano',NULL),(469,224,'Tacuarembo',NULL),(470,224,'Treinta y Tres',NULL),(471,230,'Saint Croix',NULL),(472,230,'Saint John',NULL),(473,230,'Saint Thomas',NULL),(474,229,'Anegada',NULL),(475,229,'Jost Van Dyke',NULL),(476,229,'Tortola',NULL),(477,229,'Virgin Gorda',NULL),(478,216,'Tobago',NULL),(479,216,'Trinidad',NULL),(480,227,'Charlotte',NULL),(481,227,'Grenadines',NULL),(482,227,'Saint Andrew',NULL),(483,227,'Saint David',NULL),(484,227,'Saint George',NULL),(485,227,'Saint Patrick',NULL),(486,123,'Anse la Raye',NULL),(487,123,'Castries',NULL),(488,123,'Choiseul',NULL),(489,123,'Dauphin',NULL),(490,123,'Dennery',NULL),(491,123,'Gros Islet',NULL),(492,123,'Laborie',NULL),(493,123,'Micoud',NULL),(494,123,'Praslin',NULL),(495,123,'Soufriere',NULL),(496,123,'Vieux Fort',NULL),(497,115,'Nevis',NULL),(498,115,'Saint Kitts',NULL),(499,179,'Alto Paraguay',NULL),(500,179,'Amambay',NULL),(501,179,'Asuncion',NULL),(502,179,'Boqueron',NULL),(503,179,'Caazapa',NULL);
INSERT INTO `lkupstateprovince`(stateId, countryId, stateName, abbrev) VALUES (504,179,'Canindeyu',NULL),(505,179,'Central',NULL),(506,179,'Concepcion',NULL),(507,179,'Misiones',NULL),(508,179,'Neembucu',NULL),(509,179,'Presidente Hayes',NULL),(510,179,'San Pedro',NULL),(511,179,'Distrito Capital',NULL),(512,179,'Guaira',NULL),(513,166,'Bocas del Toro',NULL),(514,166,'Chiriqui',NULL),(515,166,'Cocle',NULL),(516,166,'Colon',NULL),(517,166,'Darien',NULL),(518,166,'Herrera',NULL),(519,166,'Los Santos',NULL),(520,166,'Veraguas',NULL),(521,158,'Boaco',NULL),(522,158,'Carazo',NULL),(523,158,'Chinandega',NULL),(524,158,'Chontales',NULL),(525,158,'Esteli',NULL),(526,158,'Granada',NULL),(527,158,'Jinotega',NULL),(528,158,'Leon',NULL),(529,158,'Madriz',NULL),(530,158,'Managua',NULL),(531,158,'Masaya',NULL),(532,158,'Matagalpa',NULL),(533,158,'Nueva Segovia',NULL),(534,158,'Region Autonoma del Atlantica Sur',NULL),(535,158,'Region Autonoma del Atlantico Norte',NULL),(536,158,'Rio San Juan',NULL),(537,158,'Rivas',NULL),(538,8,'Aruba',NULL),(539,8,'Bonaire',NULL),(540,8,'Curacao',NULL),(541,8,'Saba',NULL),(542,8,'Sint Eustatius',NULL),(543,8,'Sint Maarten',NULL),(544,143,'Fort-de-France',NULL),(545,143,'Le Marin',NULL),(546,143,'Saint-Pierre',NULL),(547,107,'Clarendon',NULL),(550,107,'Kingston',NULL),(551,107,'Hanover',NULL),(552,107,'Manchester',NULL),(553,107,'Portland',NULL),(554,107,'Saint Andrew',NULL),(555,107,'Saint Ann',NULL),(556,107,'Saint Catherine',NULL),(557,107,'Saint Elizabeth',NULL),(558,107,'Saint Mary',NULL),(559,107,'Saint Thomas',NULL),(560,107,'Trelawny',NULL),(561,107,'Westmoreland',NULL),(562,94,'Atlantida',NULL),(563,94,'Choluteca',NULL),(564,94,'Colon',NULL),(565,94,'Copan',NULL),(566,94,'Cortes',NULL),(567,94,'El Paraiso',NULL),(568,94,'Francisco Morazan',NULL),(569,94,'Gracias a Dios',NULL),(570,94,'Intibuca',NULL),(571,94,'Islas de la Bahia',NULL),(572,94,'La Paz',NULL),(573,94,'Lempira',NULL),(574,94,'Ocotepeque',NULL),(575,94,'Olancho',NULL),(576,94,'Santa Barbara',NULL),(577,94,'Valle',NULL),(578,94,'Yoro',NULL),(579,96,'Artibonite',NULL),(580,96,'Centre',NULL),(581,96,'Grand\'Anse',NULL),(582,96,'Nippes',NULL),(583,96,'Nord',NULL),(584,96,'Nord-Est',NULL),(585,96,'Nord-Ouest',NULL),(586,96,'Ouest',NULL),(587,96,'Sud',NULL),(588,96,'Sud-Est',NULL),(589,91,'Barima-Waini',NULL),(590,91,'Demerara-Mahaica',NULL),(591,91,'East Berbice-Corentyne',NULL),(592,91,'Essequibo Islands-West Demerara',NULL),(593,91,'Mahaica-Berbice',NULL),(594,91,'Pomeroon-Supenaam',NULL),(595,91,'Potaro-Siparuni',NULL),(596,91,'Upper Demerara-Berbice',NULL),(597,88,'Alta Verapaz',NULL),(598,88,'Baja Verapaz',NULL),(599,88,'Chimaltenango',NULL),(600,88,'El Progreso',NULL),(601,88,'El Quiche',NULL),(602,88,'Escuintla',NULL),(603,88,'Guatemala',NULL),(604,88,'Huehuetenango',NULL),(605,88,'Izabal',NULL),(606,88,'Jalapa',NULL),(607,88,'Jutiapa',NULL),(608,88,'Quetzaltenango',NULL),(609,88,'Retalhuleu',NULL),(610,88,'Sacatepequez',NULL),(611,88,'San Marcos',NULL),(612,88,'Santa Rosa',NULL),(613,88,'Solola',NULL),(614,88,'Suchitepequez',NULL),(615,88,'Totonicapan',NULL),(616,88,'Zacapa',NULL),(617,76,'Saint Andrew',NULL),(618,76,'Saint David',NULL),(619,76,'Saint George',NULL),(620,76,'Saint John',NULL),(621,76,'Saint Mark',NULL),(622,76,'Saint Patrick',NULL),(623,78,'Cayenne',NULL),(624,78,'Saint Laurent du Maroni',NULL),(625,201,'Ahuachapan',NULL),(626,201,'Cabanas',NULL),(627,201,'Cuscatlan',NULL),(628,201,'La Libertad',NULL),(629,201,'La Paz',NULL),(630,201,'La Union',NULL),(631,201,'Morazan',NULL),(632,201,'San Miguel',NULL),(633,201,'San Salvador',NULL),(634,201,'Santa Ana',NULL),(635,201,'Sonsonate',NULL),(636,201,'Usulutan',NULL),(637,201,'San Vicente',NULL),(638,61,'Azuay',NULL),(639,61,'Bolivar',NULL),(640,61,'Canar',NULL),(641,61,'Carchi',NULL),(642,61,'Cotopaxi',NULL),(643,61,'El Oro',NULL),(644,61,'Esmeraldas',NULL),(645,61,'Galapagos',NULL),(646,61,'Guayas',NULL),(647,61,'Imbabura',NULL),(648,61,'Loja',NULL),(649,61,'Los Rios',NULL),(650,61,'Manabi',NULL),(651,61,'Morona Santiago',NULL),(652,61,'Orellana',NULL),(653,61,'Pastaza',NULL),(654,61,'Santa Elena',NULL),(655,61,'Santo Domingo de los Tsachilas',NULL),(656,61,'Sucumbios',NULL),(657,61,'Tungurahua',NULL),(658,61,'Zamora Chinchipe',NULL),(659,59,'Baoruco',NULL),(660,59,'Barahona',NULL),(661,59,'Dajabon',NULL),(662,59,'Distrito Nacional',NULL),(663,59,'Duarte',NULL),(664,59,'El Seibo',NULL),(665,59,'Espaillat',NULL),(666,59,'Hato Mayor',NULL),(667,59,'Hermanas Mirabel',NULL),(668,59,'Independencia',NULL),(669,59,'La Altagracia',NULL),(670,59,'La Romana',NULL),(674,59,'Maria Trinidad Sanchez',NULL),(675,59,'Monsenor Nouel',NULL),(676,59,'Monte Cristi',NULL),(677,59,'Monte Plata',NULL),(678,59,'Pedernales',NULL),(679,59,'Peravia',NULL),(680,59,'Puerto Plata',NULL),(681,59,'Samana',NULL),(682,59,'San Cristobal',NULL),(683,59,'San Jose de Ocoa',NULL),(684,59,'San Juan',NULL),(685,59,'San Pedro de Macoris',NULL),(686,59,'Sanchez Ramirez',NULL),(687,59,'Santiago',NULL),(688,59,'Santiago Rodriguez',NULL),(689,59,'Santo Domingo',NULL),(690,59,'Valverde',NULL),(691,58,'Saint Andrew',NULL),(692,58,'Saint David',NULL),(693,58,'Saint George',NULL),(694,58,'Saint John',NULL),(695,58,'Saint Joseph',NULL),(696,58,'Saint Luke',NULL),(697,58,'Saint Mark',NULL),(698,58,'Saint Patrick',NULL),(699,58,'Saint Paul',NULL),(700,58,'Saint Peter',NULL),(701,50,'Artemisa',NULL),(702,50,'Camaguey',NULL),(703,50,'Ciego de Avila',NULL),(704,50,'Cienfuegos',NULL),(705,50,'Granma',NULL),(706,50,'Guantanamo',NULL),(707,50,'Holguin',NULL),(708,50,'Isla de la Juventud',NULL),(709,50,'La Habana',NULL),(710,50,'Las Tunas',NULL),(711,50,'Matanzas',NULL),(712,50,'Mayabeque',NULL),(713,50,'Pinar del Rio',NULL),(714,50,'Sancti Spiritus',NULL),(715,50,'Santiago de Cuba',NULL),(716,50,'Villa Clara',NULL),(717,263,'El Hierro',NULL),(718,263,'Fuerteventura',NULL),(719,263,'Gran Canaria',NULL),(720,263,'La Gomera',NULL),(721,263,'La Palma',NULL),(722,263,'Lanzarote',NULL),(723,263,'Rio Negro',NULL),(724,263,'Tenerife',NULL),(725,35,'Belize',NULL),(726,35,'Corozal',NULL),(727,35,'Orange Walk',NULL),(728,35,'Stann Creek',NULL),(729,35,'Toledo',NULL),(730,18,'Christ Church',NULL),(731,18,'Saint Andrew',NULL),(732,18,'Saint George',NULL),(733,18,'Saint James',NULL),(734,18,'Saint John',NULL),(735,18,'Saint Joseph',NULL),(736,18,'Saint Lucy',NULL),(737,18,'Saint Michael',NULL),(738,18,'Saint Peter',NULL),(739,18,'Saint Philip',NULL),(740,18,'Saint Thomas',NULL),(741,30,'Acklins',NULL),(742,30,'Berry Islands',NULL),(743,30,'Bimini',NULL),(744,30,'Black Point',NULL),(746,30,'Cat Island',NULL),(747,30,'Central Abaco',NULL),(748,30,'Central Andros',NULL),(749,30,'Central Eleuthera',NULL),(750,30,'City of Freeport',NULL),(751,30,'Crooked Island',NULL),(752,30,'East Grand Bahama',NULL),(753,30,'Exuma',NULL),(754,30,'Grand Cay',NULL),(755,30,'Green Turtle Cay',NULL),(756,30,'Harbour Island',NULL),(757,30,'Hope Town',NULL),(758,30,'Inagua',NULL),(759,30,'Long Island',NULL),(760,30,'Mangrove Cay',NULL),(761,30,'Mayaguana',NULL),(762,30,'Moore\'s Island',NULL),(763,30,'North Abaco',NULL),(764,30,'North Andros',NULL),(765,30,'North Eleuthera',NULL),(766,30,'Ragged Island',NULL),(767,30,'Rum Cay',NULL),(768,30,'San Salvador',NULL),(769,30,'South Abaco',NULL),(770,30,'South Andros',NULL),(771,30,'South Eleuthera',NULL),(772,30,'Spanish Wells',NULL),(773,30,'West Grand Bahama',NULL),(774,4,'Barbuda',NULL),(775,4,'Redonda',NULL),(776,4,'Saint John',NULL),(777,4,'Saint Mary',NULL),(778,4,'Saint Paul',NULL),(779,4,'Saint Peter',NULL),(780,4,'Saint Philip',NULL),(781,228,'Distrito Capital',NULL),(782,100,'North',NULL),(783,100,'Haifa',NULL),(784,100,'Center',NULL),(785,100,'Tel Aviv',NULL),(786,100,'Jerusalem',NULL),(787,100,'South',NULL),(788,100,'Judea and Samaria',NULL),(789,63,'Alexandria',NULL),(790,63,'Aswan',NULL),(791,63,'Asyut',NULL),(792,63,'Beheira',NULL),(793,63,'Beni Suef',NULL),(794,63,'Cairo',NULL),(795,63,'Dakahlia',NULL),(796,63,'Damietta',NULL),(797,63,'Faiyum',NULL),(798,63,'Gharbia',NULL),(799,63,'Giza',NULL),(800,63,'Ismailia',NULL),(801,63,'Kafr el-Sheikh',NULL),(802,63,'Matruh',NULL),(803,63,'Minya',NULL),(804,63,'Monufia',NULL),(805,63,'New Valley',NULL),(806,63,'North Sinai',NULL),(807,63,'Port Said',NULL),(808,63,'Qalyubia',NULL),(809,63,'Qena',NULL),(810,63,'Red Sea',NULL),(811,63,'Al Sharqia',NULL),(812,63,'Sohag',NULL),(813,63,'South Sinai',NULL),(814,63,'Suez',NULL),(815,63,'Luxor',NULL),(816,36,'Quebec',NULL),(817,36,'Nova Scotia',NULL),(818,36,'New Brunswick',NULL),(819,36,'Prince Edward Island',NULL),(820,36,'Saskatchewan',NULL),(821,208,'Bangkok',NULL),(822,208,'Amnat Charoen',NULL),(823,208,'Ang Thong',NULL),(824,208,'Bueng Kan',NULL),(825,208,'Buriram',NULL),(826,208,'Chachoengsao',NULL),(827,208,'Chainat',NULL),(828,208,'Chaiyaphum',NULL),(829,208,'Chanthaburi',NULL),(830,208,'Chiang Mai',NULL),(831,208,'Chiang Rai',NULL),(832,208,'Chonburi',NULL),(833,208,'Chumphon',NULL),(834,208,'Kalasin',NULL),(835,208,'Kamphaeng',NULL),(836,208,'Kanchanaburi',NULL),(837,208,'Khon Kaen',NULL),(838,208,'Krabi',NULL),(839,208,'Lampang',NULL),(840,208,'Lamphun',NULL),(841,208,'Loei',NULL),(842,208,'Lopburi',NULL),(843,208,'Mae Hong Son',NULL),(844,208,'Maha Sarakham',NULL),(845,208,'Mukdahan',NULL),(846,208,'Nakhon Nayok',NULL),(847,208,'Nakhon Pathom',NULL),(848,208,'Nakhon Phanom',NULL),(849,208,'Nakhon Ratchasima',NULL),(850,208,'Nakhon Sawan',NULL),(851,208,'Nakhon Si Thammarat',NULL),(852,208,'Nan',NULL),(853,208,'Narathiwat',NULL),(854,208,'Nong Bua Lamphu',NULL),(855,208,'Nong Khai',NULL),(856,208,'Nonthaburi',NULL),(857,208,'Pathum Thani',NULL),(858,208,'Pattani',NULL),(859,208,'Phang Nga',NULL),(860,208,'Phatthalung',NULL),(861,208,'Phayao',NULL),(862,208,'Phetchabun',NULL),(863,208,'Phetchaburi',NULL),(864,208,'Phichit',NULL),(865,208,'Phitsanulok',NULL),(866,208,'Phra Nakhon Si Ayutthaya',NULL),(867,208,'Phrae',NULL),(868,208,'Phuket',NULL),(869,208,'Prachinburi',NULL),(870,208,'Prachuap Khiri Khan',NULL),(871,208,'Ranong',NULL),(872,208,'Ratchaburi',NULL),(873,208,'Rayong',NULL),(874,208,'Roi Et',NULL),(875,208,'Sa Kaeo',NULL),(876,208,'Sakon Nakhon',NULL),(877,208,'Samut Prakan',NULL),(878,208,'Samut Sakhon',NULL),(879,208,'Samut Songkhram',NULL),(880,208,'Saraburi',NULL),(881,208,'Satun',NULL),(882,208,'Sing Buri',NULL),(883,208,'Sisaket',NULL),(884,208,'Songkhla',NULL),(885,208,'Sukhothai',NULL),(886,208,'Suphan Buri',NULL),(887,208,'Surat Thani',NULL),(888,208,'Surin',NULL),(889,208,'Tak',NULL),(890,208,'Trang',NULL),(891,208,'Trat',NULL),(892,208,'Ubon Ratchathani',NULL),(893,208,'Udon Thani',NULL),(894,208,'Uthai Thani',NULL),(895,208,'Uttaradit',NULL),(896,208,'Yala',NULL),(897,208,'Yasothon',NULL),(898,161,'Bagmati',NULL),(899,161,'Bheri',NULL),(900,161,'Gandaki',NULL),(901,161,'Janakpur',NULL),(902,161,'Karnali',NULL),(903,161,'Koshi',NULL),(904,161,'Lumbini',NULL),(905,161,'Mahakali',NULL),(906,161,'Mechi',NULL),(907,161,'Narayani',NULL),(908,161,'Rapti',NULL),(910,161,'Seti',NULL),(911,66,'Andalusia',NULL),(912,66,'Aragon',NULL),(913,66,'Asturias',NULL),(914,66,'Balearic Islands',NULL),(915,66,'Basque Country',NULL),(916,66,'Canary Islands',NULL),(917,66,'Cantabria',NULL),(918,66,'Castile and Leon',NULL),(919,66,'Catalonia',NULL),(920,66,'Community of Madrid',NULL),(921,66,'Extremadura',NULL),(922,66,'Galicia',NULL),(923,66,'La Rioja',NULL),(924,66,'Murcia',NULL),(925,66,'Navarre',NULL),(926,66,'Valencian Community',NULL),(927,14,'New South Wales',NULL),(928,14,'Queensland',NULL),(929,14,'South Australia',NULL),(930,14,'Tasmania',NULL),(931,14,'Victoria',NULL),(932,14,'Western Australia',NULL),(933,14,'Australian Capital Territory',NULL),(934,14,'Northern Territory',NULL),(935,199,'Brokopondo',NULL),(936,199,'Commewijne',NULL),(937,199,'Coronie',NULL),(938,199,'Marowijne',NULL),(939,199,'Nickerie',NULL),(940,199,'Para',NULL),(941,199,'Paramaribo',NULL),(942,199,'Saramacca',NULL),(943,199,'Sipaliwini',NULL),(944,199,'Wanica',NULL),(945,36,'Nunavut',NULL),(946,36,'Northwest Territories',NULL),(947,36,'Yukon',NULL),(948,55,'Baden-WÃ¼rttemberg',NULL),(949,55,'Bavaria',NULL),(950,55,'Freistaat Bayern',NULL),(951,55,'Berlin',NULL),(952,55,'Brandenburg',NULL),(953,55,'Bremen',NULL),(954,55,'Freie Hansestadt Bremen',NULL),(955,55,'Hamburg',NULL),(956,55,'Freie und Hansestadt Hamburg',NULL),(957,55,'Hesse',NULL),(958,55,'Hessen',NULL),(959,55,'Lower Saxony',NULL),(960,55,'Niedersachsen',NULL),(961,55,'Mecklenburg-Vorpommern',NULL),(962,55,'North Rhine-Westphalia',NULL),(963,55,'Rhineland-Palatinate',NULL),(964,55,'Rheinland-Pfalz',NULL),(965,55,'Saarland',NULL),(966,55,'Freistaat Sachsen',NULL),(967,55,'Saxony-Anhalt',NULL),(968,55,'Sachsen-Anhalt',NULL),(969,55,'Schleswig-Holstein',NULL),(970,55,'Thuringia',NULL),(971,55,'Freistaat ThÃ¼ringen',NULL),(972,101,'Assam',NULL),(973,101,'Kerala',NULL),(974,66,'Huelva',NULL),(975,66,'Malaga',NULL);

INSERT INTO `lkupcounty`(countyId, stateid, countyName) VALUES (1,164,'Suffolk'),(2,173,'Adjuntas'),(3,173,'Aguada'),(4,173,'Aguadilla'),(5,155,'Mower'),(6,172,'Susquehanna'),(7,158,'Glacier'),(8,179,'Garfield'),(9,173,'Maricao'),(10,173,'Anasco'),(11,173,'Utuado'),(12,173,'Arecibo'),(13,173,'Barceloneta'),(14,173,'Cabo Rojo'),(15,173,'Penuelas'),(16,173,'Camuy'),(17,173,'Lares'),(18,173,'San German'),(19,173,'Sabana Grande'),(20,173,'Ciales'),(21,173,'Dorado'),(22,173,'Guanica'),(23,173,'Florida'),(24,173,'Guayanilla'),(25,173,'Hatillo'),(26,173,'Hormigueros'),(27,173,'Isabela'),(28,173,'Jayuya'),(29,173,'Lajas'),(30,173,'Las Marias'),(31,173,'Manati'),(32,173,'Moca'),(33,173,'Rincon'),(34,173,'Quebradillas'),(35,173,'Mayaguez'),(36,173,'San Sebastian'),(37,173,'Morovis'),(38,173,'Vega Alta'),(39,173,'Vega Baja'),(40,173,'Yauco'),(41,173,'Aguas Buenas'),(42,173,'Guayama'),(43,173,'Aibonito'),(44,173,'Maunabo'),(45,173,'Arroyo'),(46,173,'Ponce'),(47,173,'Naguabo'),(48,173,'Naranjito'),(49,173,'Orocovis'),(50,173,'Rio Grande'),(51,173,'Patillas'),(52,173,'Caguas'),(53,173,'Canovanas'),(54,173,'Ceiba'),(55,173,'Cayey'),(56,173,'Fajardo'),(57,173,'Cidra'),(58,173,'Humacao'),(59,173,'Salinas'),(60,173,'San Lorenzo'),(61,173,'Santa Isabel'),(62,173,'Vieques'),(63,173,'Villalba'),(64,173,'Yabucoa'),(65,173,'Coamo'),(66,173,'Las Piedras'),(67,173,'Loiza'),(68,173,'Luquillo'),(69,173,'Culebra'),(70,173,'Juncos'),(71,173,'Gurabo'),(72,173,'Comerio'),(73,173,'Corozal'),(74,173,'Barranquitas'),(75,173,'Juana Diaz'),(76,181,'Saint Thomas'),(77,181,'Saint Croix'),(78,181,'Saint John'),(79,173,'San Juan'),(80,173,'Bayamon'),(81,173,'Toa Baja'),(82,173,'Toa Alta'),(83,173,'Catano'),(84,173,'Guaynabo'),(85,173,'Trujillo Alto'),(86,173,'Carolina'),(87,153,'Hampden'),(88,153,'Hampshire'),(89,153,'Worcester'),(90,153,'Berkshire'),(91,153,'Franklin'),(92,153,'Middlesex'),(93,153,'Essex'),(94,153,'Plymouth'),(95,153,'Norfolk'),(96,153,'Bristol'),(97,153,'Suffolk'),(98,153,'Barnstable'),(99,153,'Dukes'),(100,153,'Nantucket'),(101,174,'Newport'),(102,174,'Providence'),(103,174,'Washington'),(104,174,'Bristol'),(105,174,'Kent'),(106,161,'Hillsborough'),(107,161,'Rockingham'),(108,161,'Merrimack'),(109,161,'Grafton'),(110,161,'Belknap'),(111,161,'Carroll'),(112,161,'Sullivan'),(113,161,'Cheshire'),(114,161,'Coos'),(115,161,'Strafford'),(116,150,'York'),(117,150,'Cumberland'),(118,150,'Sagadahoc'),(119,150,'Oxford'),(120,150,'Androscoggin'),(121,150,'Franklin'),(122,150,'Kennebec'),(123,150,'Lincoln'),(124,150,'Waldo'),(125,150,'Penobscot'),(126,150,'Piscataquis'),(127,150,'Hancock'),(128,150,'Washington'),(129,150,'Aroostook'),(130,150,'Somerset'),(132,150,'Knox'),(133,180,'Windsor'),(134,180,'Orange'),(135,180,'Caledonia'),(136,180,'Windham'),(137,180,'Bennington'),(138,180,'Chittenden'),(139,180,'Grand Isle'),(140,180,'Franklin'),(141,180,'Lamoille'),(142,180,'Addison'),(143,180,'Washington'),(144,180,'Rutland'),(145,180,'Orleans'),(146,180,'Essex'),(147,135,'Hartford'),(148,135,'Litchfield'),(149,135,'Tolland'),(150,135,'Windham'),(151,135,'New London'),(152,135,'New Haven'),(153,135,'Fairfield'),(154,135,'Middlesex'),(155,162,'Middlesex'),(156,162,'Hudson'),(157,162,'Essex'),(158,162,'Morris'),(159,162,'Bergen'),(160,162,'Passaic'),(161,162,'Union'),(162,162,'Somerset'),(163,162,'Sussex'),(164,162,'Monmouth'),(165,162,'Warren'),(166,162,'Hunterdon'),(167,162,'Salem'),(168,162,'Camden'),(169,162,'Ocean'),(170,162,'Burlington'),(171,162,'Gloucester'),(172,162,'Atlantic'),(173,162,'Cape May'),(174,162,'Cumberland'),(175,162,'Mercer'),(176,164,'New York'),(177,164,'Richmond'),(178,164,'Bronx'),(179,164,'Westchester'),(180,164,'Putnam'),(181,164,'Rockland'),(182,164,'Orange'),(183,164,'Nassau'),(184,164,'Queens'),(185,164,'Kings'),(186,164,'Albany'),(187,164,'Schenectady'),(188,164,'Montgomery'),(189,164,'Greene'),(190,164,'Columbia'),(191,164,'Rensselaer'),(192,164,'Saratoga'),(193,164,'Fulton'),(194,164,'Schoharie'),(195,164,'Washington'),(196,164,'Otsego'),(197,164,'Hamilton'),(198,164,'Delaware'),(199,164,'Ulster'),(200,164,'Dutchess'),(201,164,'Sullivan'),(202,164,'Warren'),(203,164,'Essex'),(204,164,'Clinton'),(205,164,'Franklin'),(206,164,'Saint Lawrence'),(207,164,'Onondaga'),(208,164,'Cayuga'),(209,164,'Oswego'),(210,164,'Madison'),(211,164,'Cortland'),(212,164,'Tompkins'),(213,164,'Oneida'),(214,164,'Seneca'),(215,164,'Chenango'),(216,164,'Wayne'),(217,164,'Lewis'),(218,164,'Herkimer'),(219,164,'Jefferson'),(220,164,'Tioga'),(221,164,'Broome'),(222,164,'Erie'),(223,164,'Genesee'),(224,164,'Niagara'),(225,164,'Wyoming'),(226,164,'Allegany'),(227,164,'Cattaraugus'),(228,164,'Chautauqua'),(229,164,'Orleans'),(230,164,'Monroe'),(231,164,'Livingston'),(232,164,'Yates'),(233,164,'Ontario'),(234,164,'Steuben'),(235,164,'Schuyler'),(236,164,'Chemung'),(237,172,'Beaver'),(238,172,'Washington'),(239,172,'Allegheny'),(240,172,'Fayette'),(241,172,'Westmoreland'),(242,172,'Greene'),(243,172,'Somerset'),(244,172,'Bedford'),(245,172,'Fulton'),(246,172,'Armstrong'),(247,172,'Indiana'),(248,172,'Jefferson'),(249,172,'Cambria'),(250,172,'Clearfield'),(251,172,'Elk'),(252,172,'Forest'),(253,172,'Cameron'),(254,172,'Butler'),(255,172,'Clarion'),(256,172,'Lawrence'),(257,172,'Crawford'),(258,172,'Mercer'),(259,172,'Venango'),(260,172,'Warren'),(261,172,'McKean'),(262,172,'Erie'),(263,172,'Blair'),(264,172,'Huntingdon'),(265,172,'Centre'),(266,172,'Potter'),(267,172,'Clinton'),(268,172,'Tioga'),(269,172,'Bradford'),(270,172,'Cumberland'),(271,172,'Mifflin'),(272,172,'Lebanon'),(273,172,'Dauphin'),(274,172,'Perry'),(275,172,'Juniata'),(276,172,'Northumberland'),(277,172,'York'),(278,172,'Lancaster'),(279,172,'Franklin'),(280,172,'Adams'),(281,172,'Lycoming'),(282,172,'Sullivan'),(283,172,'Union'),(284,172,'Snyder'),(285,172,'Columbia'),(286,172,'Montour'),(287,172,'Schuylkill'),(288,172,'Northampton'),(289,172,'Lehigh'),(290,172,'Carbon'),(291,172,'Bucks'),(292,172,'Montgomery'),(293,172,'Berks'),(294,172,'Monroe'),(295,172,'Luzerne'),(296,172,'Pike'),(297,172,'Lackawanna'),(298,172,'Wayne'),(299,172,'Wyoming'),(300,172,'Delaware'),(301,172,'Philadelphia'),(302,172,'Chester'),(303,136,'New Castle'),(304,136,'Kent'),(305,136,'Sussex'),(306,137,'District of Columbia'),(307,182,'Loudoun'),(308,182,'Rappahannock'),(309,182,'Manassas City'),(310,182,'Manassas Park City'),(311,182,'Fauquier'),(312,182,'Fairfax'),(313,182,'Prince William'),(314,152,'Charles'),(315,152,'Saint Marys'),(316,152,'Prince Georges'),(317,152,'Calvert'),(318,152,'Howard'),(319,152,'Anne Arundel'),(320,152,'Montgomery'),(321,152,'Harford'),(322,152,'Baltimore'),(323,152,'Carroll'),(324,152,'Baltimore City'),(325,152,'Allegany'),(326,152,'Garrett'),(327,152,'Talbot'),(328,152,'Queen Annes'),(329,152,'Caroline'),(330,152,'Kent'),(331,152,'Dorchester'),(332,152,'Frederick'),(333,152,'Washington'),(334,152,'Wicomico'),(335,152,'Worcester'),(336,152,'Somerset'),(337,152,'Cecil'),(338,182,'Fairfax City'),(339,182,'Falls Church City'),(340,182,'Arlington'),(341,182,'Alexandria City'),(342,182,'Fredericksburg City'),(343,182,'Stafford'),(344,182,'Spotsylvania'),(345,182,'Caroline'),(346,182,'Northumberland'),(347,182,'Orange'),(348,182,'Essex'),(349,182,'Westmoreland'),(350,182,'King George'),(351,182,'Richmond'),(352,182,'Lancaster'),(353,182,'Winchester City'),(354,182,'Frederick'),(355,182,'Warren'),(356,182,'Clarke'),(357,182,'Shenandoah'),(358,182,'Page'),(359,182,'Culpeper'),(360,182,'Madison'),(361,182,'Harrisonburg City'),(362,182,'Rockingham'),(363,182,'Augusta'),(364,182,'Albemarle'),(365,182,'Charlottesville City'),(366,182,'Nelson'),(367,182,'Greene'),(368,182,'Fluvanna'),(369,182,'Waynesboro City'),(370,182,'Gloucester'),(371,182,'Amelia'),(372,182,'Buckingham'),(373,182,'Hanover'),(374,182,'King William'),(375,182,'New Kent'),(376,182,'Goochland'),(377,182,'Mathews'),(378,182,'King And Queen'),(379,182,'Louisa'),(380,182,'Cumberland'),(381,182,'Charles City'),(382,182,'Middlesex'),(383,182,'Henrico'),(384,182,'James City'),(385,182,'York'),(386,182,'Powhatan'),(387,182,'Chesterfield'),(388,182,'Richmond City'),(389,182,'Williamsburg City'),(390,182,'Accomack'),(391,182,'Isle of Wight'),(392,182,'Northampton'),(393,182,'Chesapeake City'),(394,182,'Suffolk City'),(395,182,'Virginia Beach City'),(396,182,'Norfolk City'),(397,182,'Newport News City'),(398,182,'Hampton City'),(399,182,'Poquoson City'),(400,182,'Portsmouth City'),(401,182,'Prince George'),(402,182,'Petersburg City'),(403,182,'Brunswick'),(404,182,'Dinwiddie'),(405,182,'Nottoway'),(406,182,'Southampton'),(407,182,'Colonial Heights City'),(408,182,'Surry'),(409,182,'Emporia City'),(410,182,'Franklin City'),(411,182,'Hopewell City'),(412,182,'Sussex'),(413,182,'Greensville'),(414,182,'Prince Edward'),(415,182,'Mecklenburg'),(416,182,'Charlotte'),(417,182,'Lunenburg'),(418,182,'Appomattox'),(419,182,'Roanoke City'),(420,182,'Roanoke'),(421,182,'Botetourt'),(422,182,'Montgomery'),(423,182,'Patrick'),(424,182,'Henry'),(425,182,'Pulaski'),(426,182,'Franklin'),(427,182,'Pittsylvania'),(428,182,'Floyd'),(429,182,'Giles'),(430,182,'Bedford'),(431,182,'Martinsville City'),(432,182,'Craig'),(433,182,'Salem'),(434,182,'Bristol'),(435,182,'Washington'),(436,182,'Wise'),(437,182,'Dickenson'),(438,182,'Lee'),(439,182,'Russell'),(440,182,'Buchanan'),(441,182,'Scott'),(442,182,'Norton City'),(443,182,'Grayson'),(444,182,'Smyth'),(445,182,'Wythe'),(446,182,'Bland'),(447,182,'Carroll'),(448,182,'Galax City'),(449,182,'Tazewell'),(450,182,'Staunton City'),(451,182,'Bath'),(452,182,'Highland'),(453,182,'Rockbridge'),(454,182,'Buena Vista City'),(455,182,'Clifton Forge City'),(456,182,'Covington City'),(457,182,'Alleghany'),(458,182,'Lexington City'),(459,182,'Lynchburg City'),(460,182,'Campbell'),(461,182,'Halifax'),(462,182,'Amherst'),(463,182,'Bedford City'),(464,182,'Danville City'),(465,184,'Mercer'),(466,184,'Wyoming'),(467,184,'McDowell'),(468,184,'Mingo'),(469,184,'Greenbrier'),(470,184,'Pocahontas'),(471,184,'Monroe'),(472,184,'Summers'),(473,184,'Fayette'),(474,184,'Kanawha'),(475,184,'Roane'),(476,184,'Raleigh'),(477,184,'Boone'),(478,184,'Putnam'),(479,184,'Clay'),(480,184,'Logan'),(481,184,'Nicholas'),(482,184,'Mason'),(483,184,'Jackson'),(484,184,'Calhoun'),(485,184,'Gilmer'),(486,184,'Berkeley'),(487,184,'Jefferson'),(488,184,'Morgan'),(489,184,'Hampshire'),(490,184,'Lincoln'),(491,184,'Cabell'),(492,184,'Wayne'),(493,184,'Ohio'),(494,184,'Brooke'),(495,184,'Marshall'),(496,184,'Hancock'),(497,184,'Wood'),(498,184,'Pleasants'),(499,184,'Wirt'),(500,184,'Tyler'),(501,184,'Ritchie'),(502,184,'Wetzel'),(503,184,'Upshur'),(504,184,'Webster'),(505,184,'Randolph'),(506,184,'Barbour'),(507,184,'Tucker'),(508,184,'Harrison'),(509,184,'Lewis'),(510,184,'Braxton'),(511,184,'Doddridge'),(512,184,'Taylor'),(513,184,'Preston'),(514,184,'Monongalia'),(515,184,'Marion'),(516,184,'Grant'),(517,184,'Mineral'),(518,184,'Hardy'),(519,184,'Pendleton'),(520,165,'Davie'),(521,165,'Surry'),(522,165,'Forsyth'),(523,165,'Yadkin'),(524,165,'Rowan'),(525,165,'Stokes'),(526,165,'Rockingham'),(527,165,'Alamance'),(528,165,'Randolph'),(529,165,'Chatham'),(530,165,'Montgomery'),(531,165,'Caswell'),(532,165,'Guilford'),(533,165,'Orange'),(534,165,'Lee'),(535,165,'Davidson'),(536,165,'Moore'),(537,165,'Person'),(538,165,'Harnett'),(539,165,'Wake'),(540,165,'Durham'),(541,165,'Johnston'),(542,165,'Granville'),(543,165,'Franklin'),(544,165,'Wayne'),(545,165,'Vance'),(546,165,'Warren'),(547,165,'Edgecombe'),(548,165,'Nash'),(549,165,'Bertie'),(550,165,'Beaufort'),(551,165,'Pitt'),(552,165,'Wilson'),(553,165,'Hertford'),(554,165,'Northampton'),(555,165,'Halifax'),(556,165,'Hyde'),(557,165,'Martin'),(558,165,'Greene'),(559,165,'Pasquotank'),(560,165,'Dare'),(561,165,'Currituck'),(562,165,'Perquimans'),(563,165,'Camden'),(564,165,'Tyrrell'),(565,165,'Gates'),(566,165,'Washington'),(567,165,'Chowan'),(568,165,'Stanly'),(569,165,'Gaston'),(570,165,'Anson'),(571,165,'Iredell'),(572,165,'Cleveland'),(573,165,'Rutherford'),(574,165,'Cabarrus'),(575,165,'Mecklenburg'),(576,165,'Lincoln'),(577,165,'Union'),(578,165,'Cumberland'),(579,165,'Sampson'),(580,165,'Robeson'),(581,165,'Bladen'),(582,165,'Duplin'),(583,165,'Richmond'),(584,165,'Scotland'),(585,165,'Hoke'),(586,165,'New Hanover'),(587,165,'Brunswick'),(588,165,'Pender'),(589,165,'Columbus'),(590,165,'Onslow'),(591,165,'Lenoir'),(592,165,'Pamlico'),(593,165,'Carteret'),(594,165,'Craven'),(595,165,'Jones'),(596,165,'Catawba'),(597,165,'Avery'),(598,165,'Watauga'),(599,165,'Wilkes'),(600,165,'Caldwell'),(601,165,'Burke'),(602,165,'Ashe'),(603,165,'Alleghany'),(604,165,'Alexander'),(605,165,'Buncombe'),(606,165,'Swain'),(607,165,'Mitchell'),(608,165,'Jackson'),(609,165,'Transylvania'),(610,165,'Henderson'),(611,165,'Yancey'),(612,165,'Haywood'),(613,165,'Polk'),(614,165,'Graham'),(615,165,'Macon'),(616,165,'McDowell'),(617,165,'Madison'),(618,165,'Cherokee'),(619,165,'Clay'),(620,175,'Clarendon'),(621,175,'Richland'),(622,175,'Bamberg'),(623,175,'Lexington'),(624,175,'Kershaw'),(625,175,'Lee'),(626,175,'Chester'),(627,175,'Fairfield'),(628,175,'Orangeburg'),(629,175,'Calhoun'),(630,175,'Union'),(631,175,'Newberry'),(632,175,'Sumter'),(633,175,'Williamsburg'),(634,175,'Lancaster'),(635,175,'Darlington'),(636,175,'Colleton'),(637,175,'Chesterfield'),(638,175,'Saluda'),(639,175,'Florence'),(640,175,'Aiken'),(641,175,'Spartanburg'),(642,175,'Laurens'),(643,175,'Cherokee'),(644,175,'Charleston'),(645,175,'Berkeley'),(646,175,'Dorchester'),(647,175,'Georgetown'),(648,175,'Horry'),(649,175,'Marlboro'),(650,175,'Marion'),(651,175,'Dillon'),(652,175,'Greenville'),(653,175,'Abbeville'),(654,175,'Anderson'),(655,175,'Pickens'),(656,175,'Oconee'),(657,175,'Greenwood'),(658,175,'York'),(659,175,'Allendale'),(660,175,'Barnwell'),(661,175,'McCormick'),(662,175,'Edgefield'),(663,175,'Beaufort'),(664,175,'Hampton'),(665,175,'Jasper'),(666,140,'Dekalb'),(667,140,'Gwinnett'),(668,140,'Fulton'),(669,140,'Cobb'),(670,140,'Barrow'),(671,140,'Rockdale'),(672,140,'Newton'),(673,140,'Walton'),(674,140,'Forsyth'),(675,140,'Jasper'),(676,140,'Bartow'),(677,140,'Polk'),(678,140,'Floyd'),(679,140,'Cherokee'),(680,140,'Carroll'),(681,140,'Haralson'),(682,140,'Douglas'),(683,140,'Paulding'),(684,140,'Gordon'),(685,140,'Pickens'),(686,140,'Lamar'),(687,140,'Fayette'),(688,140,'Pike'),(689,140,'Spalding'),(690,140,'Butts'),(691,140,'Heard'),(692,140,'Meriwether'),(693,140,'Coweta'),(694,140,'Henry'),(695,140,'Troup'),(696,140,'Clayton'),(697,140,'Upson'),(698,140,'Emanuel'),(699,140,'Montgomery'),(700,140,'Wheeler'),(701,140,'Jefferson'),(702,140,'Evans'),(703,140,'Bulloch'),(704,140,'Tattnall'),(705,140,'Screven'),(706,140,'Burke'),(707,140,'Toombs'),(708,140,'Candler'),(709,140,'Jenkins'),(710,140,'Laurens'),(711,140,'Treutlen'),(712,140,'Hall'),(713,140,'Habersham'),(714,140,'Banks'),(715,140,'Union'),(716,140,'Fannin'),(717,140,'Hart'),(718,140,'Jackson'),(719,140,'Franklin'),(720,140,'Gilmer'),(721,140,'Rabun'),(722,140,'White'),(723,140,'Lumpkin'),(724,140,'Dawson'),(725,140,'Stephens'),(726,140,'Towns'),(727,140,'Clarke'),(728,140,'Oglethorpe'),(729,140,'Oconee'),(730,140,'Morgan'),(731,140,'Elbert'),(732,140,'Madison'),(733,140,'Taliaferro'),(734,140,'Greene'),(735,140,'Wilkes'),(736,140,'Murray'),(737,140,'Walker'),(738,140,'Whitfield'),(739,140,'Catoosa'),(740,140,'Chattooga'),(741,140,'Dade'),(742,140,'Columbia'),(743,140,'Richmond'),(744,140,'McDuffie'),(745,140,'Warren'),(746,140,'Glascock'),(747,140,'Lincoln'),(748,140,'Wilcox'),(749,140,'Wilkinson'),(750,140,'Monroe'),(751,140,'Houston'),(752,140,'Taylor'),(753,140,'Dooly'),(754,140,'Peach'),(755,140,'Crisp'),(756,140,'Dodge'),(757,140,'Bleckley'),(758,140,'Twiggs'),(759,140,'Washington'),(760,140,'Putnam'),(761,140,'Jones'),(762,140,'Baldwin'),(763,140,'Pulaski'),(764,140,'Telfair'),(765,140,'Macon'),(766,140,'Johnson'),(767,140,'Crawford'),(768,140,'Hancock'),(769,140,'Bibb'),(770,140,'Liberty'),(771,140,'Chatham'),(772,140,'Effingham'),(773,140,'McIntosh'),(774,140,'Bryan'),(775,140,'Long'),(776,140,'Ware'),(777,140,'Bacon'),(778,140,'Coffee'),(779,140,'Appling'),(780,140,'Pierce'),(781,140,'Glynn'),(782,140,'Jeff Davis'),(783,140,'Charlton'),(784,140,'Brantley'),(785,140,'Wayne'),(786,140,'Camden'),(787,140,'Decatur'),(788,140,'Lowndes'),(789,140,'Cook'),(790,140,'Berrien'),(791,140,'Clinch'),(792,140,'Atkinson'),(793,140,'Brooks'),(794,140,'Thomas'),(795,140,'Lanier'),(796,140,'Echols'),(797,140,'Dougherty'),(798,140,'Sumter'),(799,140,'Turner'),(800,140,'Mitchell'),(801,140,'Colquitt'),(802,140,'Tift'),(803,140,'Ben Hill'),(804,140,'Irwin'),(805,140,'Lee'),(806,140,'Worth'),(807,140,'Talbot'),(808,140,'Marion'),(809,140,'Harris'),(810,140,'Chattahoochee'),(811,140,'Schley'),(812,140,'Muscogee'),(813,140,'Stewart'),(814,140,'Webster'),(815,139,'Clay'),(816,139,'Saint Johns'),(817,139,'Putnam'),(818,139,'Suwannee'),(819,139,'Nassau'),(820,139,'Lafayette'),(821,139,'Columbia'),(822,139,'Union'),(823,139,'Baker'),(824,139,'Bradford'),(825,139,'Hamilton'),(826,139,'Madison'),(827,139,'Duval'),(828,139,'Lake'),(829,139,'Volusia'),(830,139,'Flagler'),(831,139,'Marion'),(832,139,'Sumter'),(833,139,'Leon'),(834,139,'Wakulla'),(835,139,'Franklin'),(836,139,'Liberty'),(837,139,'Gadsden'),(838,139,'Jefferson'),(839,139,'Taylor'),(840,139,'Bay'),(841,139,'Jackson'),(842,139,'Calhoun'),(843,139,'Walton'),(844,139,'Holmes'),(845,139,'Washington'),(846,139,'Gulf'),(847,139,'Escambia'),(848,139,'Santa Rosa'),(849,139,'Okaloosa'),(850,139,'Alachua'),(851,139,'Gilchrist'),(852,139,'Levy'),(853,139,'Dixie'),(854,139,'Seminole'),(855,139,'Orange'),(856,139,'Brevard'),(857,139,'Indian River'),(858,139,'Monroe'),(859,139,'Miami Dade'),(860,139,'Broward'),(861,139,'Palm Beach'),(862,139,'Hendry'),(863,139,'Martin'),(864,139,'Glades'),(865,139,'Hillsborough'),(866,139,'Pasco'),(867,139,'Pinellas'),(868,139,'Polk'),(869,139,'Highlands'),(870,139,'Hardee'),(871,139,'Osceola'),(872,139,'Lee'),(873,139,'Charlotte'),(874,139,'Collier'),(875,139,'Manatee'),(876,139,'Sarasota'),(877,139,'De Soto'),(878,139,'Citrus'),(879,139,'Hernando'),(880,139,'Saint Lucie'),(881,139,'Okeechobee'),(882,129,'Saint Clair'),(883,129,'Jefferson'),(884,129,'Shelby'),(885,129,'Tallapoosa'),(886,129,'Blount'),(887,129,'Talladega'),(888,129,'Marshall'),(889,129,'Cullman'),(890,129,'Bibb'),(891,129,'Walker'),(892,129,'Chilton'),(893,129,'Coosa'),(894,129,'Clay'),(895,129,'Tuscaloosa'),(896,129,'Hale'),(897,129,'Pickens'),(898,129,'Greene'),(899,129,'Sumter'),(900,129,'Winston'),(901,129,'Fayette'),(902,129,'Marion'),(903,129,'Lamar'),(904,129,'Franklin'),(905,129,'Morgan'),(906,129,'Lauderdale'),(907,129,'Limestone'),(908,129,'Colbert'),(909,129,'Lawrence'),(910,129,'Jackson'),(911,129,'Madison'),(912,129,'Etowah'),(913,129,'Cherokee'),(914,129,'De Kalb'),(915,129,'Autauga'),(916,129,'Pike'),(917,129,'Crenshaw'),(918,129,'Montgomery'),(919,129,'Butler'),(920,129,'Barbour'),(921,129,'Elmore'),(922,129,'Bullock'),(923,129,'Macon'),(924,129,'Lowndes'),(925,129,'Covington'),(926,129,'Calhoun'),(927,129,'Cleburne'),(928,129,'Randolph'),(929,129,'Houston'),(930,129,'Henry'),(931,129,'Dale'),(932,129,'Geneva'),(933,129,'Coffee'),(934,129,'Conecuh'),(935,129,'Monroe'),(936,129,'Escambia'),(937,129,'Wilcox'),(938,129,'Clarke'),(939,129,'Mobile'),(940,129,'Baldwin'),(941,129,'Washington'),(942,129,'Dallas'),(943,129,'Marengo'),(944,129,'Perry'),(945,129,'Lee'),(946,129,'Russell'),(947,129,'Chambers'),(948,129,'Choctaw'),(949,177,'Robertson'),(950,177,'Davidson'),(951,177,'Dekalb'),(952,177,'Williamson'),(953,177,'Cheatham'),(954,177,'Cannon'),(955,177,'Coffee'),(956,177,'Marshall'),(957,177,'Bedford'),(958,177,'Sumner'),(959,177,'Stewart'),(960,177,'Hickman'),(961,177,'Dickson'),(962,177,'Smith'),(963,177,'Rutherford'),(964,177,'Montgomery'),(965,177,'Houston'),(966,177,'Wilson'),(967,177,'Trousdale'),(968,177,'Humphreys'),(969,177,'Macon'),(970,177,'Perry'),(971,177,'Warren'),(972,177,'Lincoln'),(973,177,'Maury'),(974,177,'Grundy'),(975,177,'Hamilton'),(976,177,'McMinn'),(977,177,'Franklin'),(978,177,'Polk'),(979,177,'Bradley'),(980,177,'Monroe'),(981,177,'Rhea'),(982,177,'Meigs'),(983,177,'Sequatchie'),(984,177,'Marion'),(985,177,'Moore'),(986,177,'Bledsoe'),(987,177,'Shelby'),(988,177,'Washington'),(989,177,'Greene'),(990,177,'Sullivan'),(991,177,'Johnson'),(992,177,'Hawkins'),(993,177,'Carter'),(994,177,'Unicoi'),(995,177,'Blount'),(996,177,'Anderson'),(997,177,'Claiborne'),(998,177,'Grainger'),(999,177,'Cocke'),(1000,177,'Campbell'),(1001,177,'Morgan'),(1002,177,'Knox'),(1003,177,'Cumberland'),(1004,177,'Jefferson'),(1005,177,'Scott'),(1006,177,'Sevier'),(1007,177,'Loudon'),(1008,177,'Roane'),(1009,177,'Hancock'),(1010,177,'Hamblen'),(1011,177,'Union'),(1012,177,'Crockett'),(1013,177,'Fayette'),(1014,177,'Tipton'),(1015,177,'Dyer'),(1016,177,'Hardeman'),(1017,177,'Haywood'),(1018,177,'Lauderdale'),(1019,177,'Lake'),(1020,177,'Carroll'),(1021,177,'Benton'),(1022,177,'Henry'),(1023,177,'Weakley'),(1024,177,'Obion'),(1025,177,'Gibson'),(1026,177,'Madison'),(1027,177,'McNairy'),(1028,177,'Decatur'),(1029,177,'Hardin'),(1030,177,'Henderson'),(1031,177,'Chester'),(1032,177,'Wayne'),(1033,177,'Giles'),(1034,177,'Lawrence');
INSERT INTO `lkupcounty`(countyId, stateid, countyName) VALUES (1035,177,'Lewis'),(1036,177,'Putnam'),(1037,177,'Fentress'),(1038,177,'Overton'),(1039,177,'Pickett'),(1040,177,'Clay'),(1041,177,'White'),(1042,177,'Jackson'),(1043,177,'Van Buren'),(1044,156,'Lafayette'),(1045,156,'Tate'),(1046,156,'Benton'),(1047,156,'Panola'),(1048,156,'Quitman'),(1049,156,'Tippah'),(1050,156,'Marshall'),(1051,156,'Coahoma'),(1052,156,'Tunica'),(1053,156,'Union'),(1054,156,'De Soto'),(1055,156,'Washington'),(1056,156,'Bolivar'),(1057,156,'Sharkey'),(1058,156,'Sunflower'),(1059,156,'Issaquena'),(1060,156,'Humphreys'),(1061,156,'Lee'),(1062,156,'Pontotoc'),(1063,156,'Monroe'),(1064,156,'Tishomingo'),(1065,156,'Prentiss'),(1066,156,'Alcorn'),(1067,156,'Calhoun'),(1068,156,'Itawamba'),(1069,156,'Chickasaw'),(1070,156,'Grenada'),(1071,156,'Carroll'),(1072,156,'Tallahatchie'),(1073,156,'Yalobusha'),(1074,156,'Holmes'),(1075,156,'Montgomery'),(1076,156,'Leflore'),(1077,156,'Yazoo'),(1078,156,'Hinds'),(1079,156,'Rankin'),(1080,156,'Simpson'),(1081,156,'Madison'),(1082,156,'Leake'),(1083,156,'Newton'),(1084,156,'Copiah'),(1085,156,'Attala'),(1086,156,'Jefferson'),(1087,156,'Scott'),(1088,156,'Claiborne'),(1089,156,'Smith'),(1090,156,'Covington'),(1091,156,'Adams'),(1092,156,'Lawrence'),(1093,156,'Warren'),(1094,156,'Lauderdale'),(1095,156,'Wayne'),(1096,156,'Kemper'),(1097,156,'Clarke'),(1098,156,'Jasper'),(1099,156,'Winston'),(1100,156,'Noxubee'),(1101,156,'Neshoba'),(1102,156,'Greene'),(1103,156,'Forrest'),(1104,156,'Jefferson Davis'),(1105,156,'Perry'),(1106,156,'Pearl River'),(1107,156,'Marion'),(1108,156,'Jones'),(1109,156,'George'),(1110,156,'Lamar'),(1111,156,'Harrison'),(1112,156,'Hancock'),(1113,156,'Jackson'),(1114,156,'Stone'),(1115,156,'Lincoln'),(1116,156,'Franklin'),(1117,156,'Wilkinson'),(1118,156,'Pike'),(1119,156,'Amite'),(1120,156,'Walthall'),(1121,156,'Lowndes'),(1122,156,'Choctaw'),(1123,156,'Webster'),(1124,156,'Clay'),(1125,156,'Oktibbeha'),(1126,140,'Calhoun'),(1127,140,'Early'),(1128,140,'Clay'),(1129,140,'Phelps'),(1130,140,'Terrell'),(1131,140,'Grady'),(1132,140,'Seminole'),(1133,140,'Quitman'),(1134,140,'Baker'),(1135,140,'Randolph'),(1136,148,'Shelby'),(1137,148,'Nelson'),(1138,148,'Trimble'),(1139,148,'Henry'),(1140,148,'Marion'),(1141,148,'Oldham'),(1142,148,'Jefferson'),(1143,148,'Washington'),(1144,148,'Spencer'),(1145,148,'Bullitt'),(1146,148,'Meade'),(1147,148,'Breckinridge'),(1148,148,'Grayson'),(1149,148,'Hardin'),(1150,148,'Mercer'),(1151,148,'Nicholas'),(1152,148,'Powell'),(1153,148,'Rowan'),(1154,148,'Menifee'),(1155,148,'Scott'),(1156,148,'Montgomery'),(1157,148,'Estill'),(1158,148,'Jessamine'),(1159,148,'Anderson'),(1160,148,'Woodford'),(1161,148,'Bourbon'),(1162,148,'Owen'),(1163,148,'Bath'),(1164,148,'Madison'),(1165,148,'Clark'),(1166,148,'Jackson'),(1167,148,'Rockcastle'),(1168,148,'Garrard'),(1169,148,'Lincoln'),(1170,148,'Boyle'),(1171,148,'Fayette'),(1172,148,'Franklin'),(1173,148,'Whitley'),(1174,148,'Laurel'),(1175,148,'Knox'),(1176,148,'Harlan'),(1177,148,'Leslie'),(1178,148,'Bell'),(1179,148,'Letcher'),(1180,148,'Clay'),(1181,148,'Perry'),(1182,148,'Campbell'),(1183,148,'Bracken'),(1184,148,'Harrison'),(1185,148,'Boone'),(1186,148,'Pendleton'),(1187,148,'Carroll'),(1188,148,'Grant'),(1189,148,'Kenton'),(1190,148,'Mason'),(1191,148,'Fleming'),(1192,148,'Gallatin'),(1193,148,'Robertson'),(1194,148,'Boyd'),(1195,148,'Greenup'),(1196,148,'Lawrence'),(1197,148,'Carter'),(1198,148,'Lewis'),(1199,148,'Elliott'),(1200,148,'Martin'),(1201,148,'Johnson'),(1202,148,'Wolfe'),(1203,148,'Breathitt'),(1204,148,'Lee'),(1205,148,'Owsley'),(1206,148,'Morgan'),(1207,148,'Magoffin'),(1208,148,'Pike'),(1209,148,'Floyd'),(1210,148,'Knott'),(1211,148,'McCracken'),(1212,148,'Calloway'),(1213,148,'Carlisle'),(1214,148,'Ballard'),(1215,148,'Marshall'),(1216,148,'Graves'),(1217,148,'Livingston'),(1218,148,'Hickman'),(1219,148,'Crittenden'),(1220,148,'Lyon'),(1221,148,'Fulton'),(1222,148,'Warren'),(1223,148,'Allen'),(1224,148,'Barren'),(1225,148,'Metcalfe'),(1226,148,'Monroe'),(1227,148,'Simpson'),(1228,148,'Edmonson'),(1229,148,'Butler'),(1230,148,'Logan'),(1231,148,'Todd'),(1232,148,'Trigg'),(1233,148,'Christian'),(1234,148,'Daviess'),(1235,148,'Ohio'),(1236,148,'Muhlenberg'),(1237,148,'McLean'),(1238,148,'Hancock'),(1239,148,'Henderson'),(1240,148,'Webster'),(1241,148,'Hopkins'),(1242,148,'Caldwell'),(1243,148,'Union'),(1244,148,'Pulaski'),(1245,148,'Casey'),(1246,148,'Clinton'),(1247,148,'Russell'),(1248,148,'McCreary'),(1249,148,'Wayne'),(1250,148,'Hart'),(1251,148,'Adair'),(1252,148,'Larue'),(1253,148,'Cumberland'),(1254,148,'Taylor'),(1255,148,'Green'),(1256,168,'Licking'),(1257,168,'Franklin'),(1258,168,'Delaware'),(1259,168,'Knox'),(1260,168,'Union'),(1261,168,'Champaign'),(1262,168,'Clark'),(1263,168,'Fairfield'),(1264,168,'Madison'),(1265,168,'Perry'),(1266,168,'Ross'),(1267,168,'Pickaway'),(1268,168,'Fayette'),(1269,168,'Hocking'),(1270,168,'Marion'),(1271,168,'Logan'),(1272,168,'Morrow'),(1273,168,'Wyandot'),(1274,168,'Hardin'),(1275,168,'Wood'),(1276,168,'Sandusky'),(1277,168,'Ottawa'),(1278,168,'Lucas'),(1279,168,'Erie'),(1280,168,'Williams'),(1281,168,'Fulton'),(1282,168,'Henry'),(1283,168,'Defiance'),(1284,168,'Muskingum'),(1285,168,'Noble'),(1286,168,'Belmont'),(1287,168,'Monroe'),(1288,168,'Guernsey'),(1289,168,'Morgan'),(1290,168,'Coshocton'),(1291,168,'Tuscarawas'),(1292,168,'Jefferson'),(1293,168,'Harrison'),(1294,168,'Columbiana'),(1295,168,'Lorain'),(1296,168,'Ashtabula'),(1297,168,'Cuyahoga'),(1298,168,'Geauga'),(1299,168,'Lake'),(1300,168,'Summit'),(1301,168,'Portage'),(1302,168,'Medina'),(1303,168,'Wayne'),(1304,168,'Mahoning'),(1305,168,'Trumbull'),(1306,168,'Stark'),(1307,168,'Carroll'),(1308,168,'Holmes'),(1309,168,'Seneca'),(1310,168,'Hancock'),(1311,168,'Ashland'),(1312,168,'Huron'),(1313,168,'Richland'),(1314,168,'Crawford'),(1315,168,'Hamilton'),(1316,168,'Butler'),(1317,168,'Warren'),(1318,168,'Preble'),(1319,168,'Brown'),(1320,168,'Clermont'),(1321,168,'Adams'),(1322,168,'Clinton'),(1323,168,'Highland'),(1324,168,'Greene'),(1325,168,'Shelby'),(1326,168,'Darke'),(1327,168,'Miami'),(1328,168,'Montgomery'),(1329,168,'Mercer'),(1330,168,'Pike'),(1331,168,'Gallia'),(1332,168,'Lawrence'),(1333,168,'Jackson'),(1334,168,'Vinton'),(1335,168,'Scioto'),(1336,168,'Athens'),(1337,168,'Washington'),(1338,168,'Meigs'),(1339,168,'Allen'),(1340,168,'Auglaize'),(1341,168,'Paulding'),(1342,168,'Putnam'),(1343,168,'Van Wert'),(1344,145,'Madison'),(1345,145,'Hamilton'),(1346,145,'Clinton'),(1347,145,'Hancock'),(1348,145,'Tipton'),(1349,145,'Boone'),(1350,145,'Hendricks'),(1351,145,'Rush'),(1352,145,'Putnam'),(1353,145,'Johnson'),(1354,145,'Marion'),(1355,145,'Shelby'),(1356,145,'Morgan'),(1357,145,'Fayette'),(1358,145,'Henry'),(1359,145,'Brown'),(1360,145,'Porter'),(1361,145,'Lake'),(1362,145,'Jasper'),(1363,145,'La Porte'),(1364,145,'Newton'),(1365,145,'Starke'),(1366,145,'Marshall'),(1367,145,'Kosciusko'),(1368,145,'Elkhart'),(1369,145,'St Joseph'),(1370,145,'Lagrange'),(1371,145,'Noble'),(1372,145,'Huntington'),(1373,145,'Steuben'),(1374,145,'Allen'),(1375,145,'De Kalb'),(1376,145,'Adams'),(1377,145,'Wells'),(1378,145,'Whitley'),(1379,145,'Howard'),(1380,145,'Fulton'),(1381,145,'Miami'),(1382,145,'Carroll'),(1383,145,'Grant'),(1384,145,'Cass'),(1385,145,'Wabash'),(1386,145,'Pulaski'),(1387,145,'Dearborn'),(1388,145,'Union'),(1389,145,'Ripley'),(1390,145,'Franklin'),(1391,145,'Switzerland'),(1392,145,'Ohio'),(1393,145,'Scott'),(1394,145,'Clark'),(1395,145,'Harrison'),(1396,145,'Washington'),(1397,145,'Crawford'),(1398,145,'Floyd'),(1399,145,'Bartholomew'),(1400,145,'Jackson'),(1401,145,'Jennings'),(1402,145,'Jefferson'),(1403,145,'Decatur'),(1404,145,'Delaware'),(1405,145,'Wayne'),(1406,145,'Jay'),(1407,145,'Randolph'),(1408,145,'Blackford'),(1409,145,'Monroe'),(1410,145,'Lawrence'),(1411,145,'Greene'),(1412,145,'Owen'),(1413,145,'Orange'),(1414,145,'Daviess'),(1415,145,'Knox'),(1416,145,'Dubois'),(1417,145,'Perry'),(1418,145,'Martin'),(1419,145,'Spencer'),(1420,145,'Pike'),(1421,145,'Warrick'),(1422,145,'Posey'),(1423,145,'Vanderburgh'),(1424,145,'Gibson'),(1425,145,'Vigo'),(1426,145,'Parke'),(1427,145,'Vermillion'),(1428,145,'Clay'),(1429,145,'Sullivan'),(1430,145,'Tippecanoe'),(1431,145,'Montgomery'),(1432,145,'Benton'),(1433,145,'Fountain'),(1434,145,'White'),(1435,145,'Warren'),(1436,154,'Saint Clair'),(1437,154,'Lapeer'),(1438,154,'Macomb'),(1439,154,'Oakland'),(1440,154,'Wayne'),(1441,154,'Washtenaw'),(1442,154,'Monroe'),(1443,154,'Livingston'),(1444,154,'Sanilac'),(1445,154,'Genesee'),(1446,154,'Huron'),(1447,154,'Shiawassee'),(1448,154,'Saginaw'),(1449,154,'Tuscola'),(1450,154,'Ogemaw'),(1451,154,'Bay'),(1452,154,'Gladwin'),(1453,154,'Gratiot'),(1454,154,'Clare'),(1455,154,'Midland'),(1456,154,'Oscoda'),(1457,154,'Roscommon'),(1458,154,'Arenac'),(1459,154,'Alcona'),(1460,154,'Iosco'),(1461,154,'Isabella'),(1462,154,'Ingham'),(1463,154,'Clinton'),(1464,154,'Ionia'),(1465,154,'Montcalm'),(1466,154,'Eaton'),(1467,154,'Barry'),(1468,154,'Kalamazoo'),(1469,154,'Allegan'),(1470,154,'Calhoun'),(1471,154,'Van Buren'),(1472,154,'Berrien'),(1473,154,'Branch'),(1474,154,'Saint Joseph'),(1475,154,'Cass'),(1476,154,'Jackson'),(1477,154,'Lenawee'),(1478,154,'Hillsdale'),(1479,154,'Kent'),(1480,154,'Muskegon'),(1481,154,'Lake'),(1482,154,'Mecosta'),(1483,154,'Newaygo'),(1484,154,'Ottawa'),(1485,154,'Mason'),(1486,154,'Oceana'),(1487,154,'Wexford'),(1488,154,'Grand Traverse'),(1489,154,'Antrim'),(1490,154,'Manistee'),(1491,154,'Benzie'),(1492,154,'Leelanau'),(1493,154,'Osceola'),(1494,154,'Missaukee'),(1495,154,'Kalkaska'),(1496,154,'Cheboygan'),(1497,154,'Emmet'),(1498,154,'Alpena'),(1499,154,'Montmorency'),(1500,154,'Chippewa'),(1501,154,'Charlevoix'),(1502,154,'Mackinac'),(1503,154,'Otsego'),(1504,154,'Crawford'),(1505,154,'Presque Isle'),(1506,154,'Dickinson'),(1507,154,'Keweenaw'),(1508,154,'Alger'),(1509,154,'Delta'),(1510,154,'Marquette'),(1511,154,'Menominee'),(1512,154,'Schoolcraft'),(1513,154,'Luce'),(1514,154,'Iron'),(1515,154,'Houghton'),(1516,154,'Baraga'),(1517,154,'Ontonagon'),(1518,154,'Gogebic'),(1519,146,'Warren'),(1520,146,'Adair'),(1521,146,'Dallas'),(1522,146,'Marshall'),(1523,146,'Hardin'),(1524,146,'Polk'),(1525,146,'Wayne'),(1526,146,'Story'),(1527,146,'Cass'),(1528,146,'Audubon'),(1529,146,'Guthrie'),(1530,146,'Mahaska'),(1531,146,'Jasper'),(1532,146,'Boone'),(1533,146,'Madison'),(1534,146,'Hamilton'),(1535,146,'Franklin'),(1536,146,'Marion'),(1537,146,'Lucas'),(1538,146,'Greene'),(1539,146,'Carroll'),(1540,146,'Decatur'),(1541,146,'Wright'),(1542,146,'Ringgold'),(1543,146,'Keokuk'),(1544,146,'Poweshiek'),(1545,146,'Union'),(1546,146,'Monroe'),(1547,146,'Tama'),(1548,146,'Clarke'),(1549,146,'Cerro Gordo'),(1550,146,'Hancock'),(1551,146,'Winnebago'),(1552,146,'Mitchell'),(1553,146,'Worth'),(1554,146,'Floyd'),(1555,146,'Kossuth'),(1556,146,'Howard'),(1557,146,'Webster'),(1558,146,'Buena Vista'),(1559,146,'Emmet'),(1560,146,'Palo Alto'),(1561,146,'Humboldt'),(1562,146,'Sac'),(1563,146,'Calhoun'),(1564,146,'Pocahontas'),(1565,146,'Butler'),(1566,146,'Chickasaw'),(1567,146,'Fayette'),(1568,146,'Buchanan'),(1569,146,'Grundy'),(1570,146,'Black Hawk'),(1571,146,'Bremer'),(1572,146,'Delaware'),(1573,146,'Taylor'),(1574,146,'Adams'),(1575,146,'Montgomery'),(1576,146,'Plymouth'),(1577,146,'Sioux'),(1578,146,'Woodbury'),(1579,146,'Cherokee'),(1580,146,'Ida'),(1581,146,'Obrien'),(1582,146,'Monona'),(1583,146,'Clay'),(1584,146,'Lyon'),(1585,146,'Osceola'),(1586,146,'Dickinson'),(1587,146,'Crawford'),(1588,146,'Shelby'),(1589,146,'Pottawattamie'),(1590,146,'Harrison'),(1591,146,'Mills'),(1592,146,'Page'),(1593,146,'Fremont'),(1594,146,'Dubuque'),(1595,146,'Jackson'),(1596,146,'Clinton'),(1597,146,'Clayton'),(1598,146,'Winneshiek'),(1599,146,'Allamakee'),(1600,146,'Washington'),(1601,146,'Linn'),(1602,146,'Iowa'),(1603,146,'Jones'),(1604,146,'Benton'),(1605,146,'Cedar'),(1606,146,'Johnson'),(1607,146,'Wapello'),(1608,146,'Jefferson'),(1609,146,'Van Buren'),(1610,146,'Davis'),(1611,146,'Appanoose'),(1612,146,'Des Moines'),(1613,146,'Lee'),(1614,146,'Henry'),(1615,146,'Louisa'),(1616,146,'Muscatine'),(1617,146,'Scott'),(1618,185,'Sheboygan'),(1619,185,'Washington'),(1620,185,'Dodge'),(1621,185,'Ozaukee'),(1622,185,'Waukesha'),(1623,185,'Fond Du Lac'),(1624,185,'Calumet'),(1625,185,'Manitowoc'),(1626,185,'Jefferson'),(1627,185,'Kenosha'),(1628,185,'Racine'),(1629,185,'Milwaukee'),(1630,185,'Walworth'),(1631,185,'Rock'),(1632,185,'Green'),(1633,185,'Iowa'),(1634,185,'Lafayette'),(1635,185,'Dane'),(1636,185,'Grant'),(1637,185,'Richland'),(1638,185,'Columbia'),(1639,185,'Sauk'),(1640,185,'Crawford'),(1641,185,'Adams'),(1642,185,'Marquette'),(1643,185,'Green Lake'),(1644,185,'Juneau'),(1645,185,'Polk'),(1646,185,'Saint Croix'),(1647,185,'Pierce'),(1648,185,'Oconto'),(1649,185,'Marinette'),(1650,185,'Forest'),(1651,185,'Outagamie'),(1652,185,'Shawano'),(1653,185,'Brown'),(1654,185,'Florence'),(1655,185,'Menominee'),(1656,185,'Kewaunee'),(1657,185,'Door'),(1658,185,'Marathon'),(1659,185,'Wood'),(1660,185,'Clark'),(1661,185,'Portage'),(1662,185,'Langlade'),(1663,185,'Taylor'),(1664,185,'Lincoln'),(1665,185,'Price'),(1666,185,'Oneida'),(1667,185,'Vilas'),(1668,185,'Ashland'),(1669,185,'Iron'),(1670,185,'Rusk'),(1671,185,'La Crosse'),(1672,185,'Buffalo'),(1673,185,'Jackson'),(1674,185,'Trempealeau'),(1675,185,'Monroe'),(1676,185,'Vernon'),(1677,185,'Eau Claire'),(1678,185,'Pepin'),(1679,185,'Chippewa'),(1680,185,'Dunn'),(1681,185,'Barron'),(1682,185,'Washburn'),(1683,185,'Bayfield'),(1684,185,'Douglas'),(1685,185,'Sawyer'),(1686,185,'Burnett'),(1687,185,'Winnebago'),(1688,185,'Waupaca'),(1689,185,'Waushara'),(1690,155,'Washington'),(1691,155,'Chisago'),(1692,155,'Anoka'),(1693,155,'Isanti'),(1694,155,'Pine'),(1695,155,'Goodhue'),(1696,155,'Dakota'),(1697,155,'Rice'),(1698,155,'Scott'),(1699,155,'Wabasha'),(1700,155,'Steele'),(1701,155,'Kanabec'),(1702,155,'Ramsey'),(1703,155,'Hennepin'),(1704,155,'Wright'),(1705,155,'Sibley'),(1706,155,'Sherburne'),(1707,155,'Renville'),(1708,155,'McLeod'),(1709,155,'Carver'),(1710,155,'Meeker'),(1711,155,'Stearns'),(1712,155,'Mille Lacs'),(1713,155,'Lake'),(1714,155,'Saint Louis'),(1715,155,'Cook'),(1716,155,'Carlton'),(1717,155,'Itasca'),(1718,155,'Aitkin'),(1719,155,'Olmsted'),(1720,155,'Winona'),(1721,155,'Houston'),(1722,155,'Fillmore'),(1723,155,'Dodge'),(1724,155,'Blue Earth'),(1725,155,'Nicollet'),(1726,155,'Freeborn'),(1727,155,'Faribault'),(1728,155,'Le Sueur'),(1729,155,'Brown'),(1730,155,'Watonwan'),(1731,155,'Martin'),(1732,155,'Waseca'),(1733,155,'Redwood'),(1734,155,'Cottonwood'),(1735,155,'Nobles'),(1736,155,'Jackson'),(1737,155,'Lincoln'),(1738,155,'Murray'),(1739,155,'Lyon'),(1740,155,'Rock'),(1741,155,'Pipestone'),(1742,155,'Kandiyohi'),(1743,155,'Stevens'),(1744,155,'Swift'),(1745,155,'Big Stone'),(1746,155,'Lac Qui Parle'),(1747,155,'Traverse'),(1748,155,'Yellow Medicine'),(1749,155,'Chippewa'),(1750,155,'Grant'),(1751,155,'Douglas'),(1752,155,'Morrison'),(1753,155,'Todd'),(1754,155,'Pope'),(1755,155,'Otter Tail'),(1756,155,'Benton'),(1757,155,'Crow Wing'),(1758,155,'Cass'),(1759,155,'Hubbard'),(1760,155,'Wadena'),(1761,155,'Becker'),(1762,155,'Norman'),(1763,155,'Clay'),(1764,155,'Mahnomen'),(1765,155,'Polk'),(1766,155,'Wilkin'),(1767,155,'Beltrami'),(1768,155,'Clearwater'),(1769,155,'Lake of the Woods'),(1770,155,'Koochiching'),(1771,155,'Roseau'),(1772,155,'Pennington'),(1773,155,'Marshall'),(1774,155,'Red Lake'),(1775,155,'Kittson'),(1776,176,'Union'),(1777,176,'Brookings'),(1778,176,'Minnehaha'),(1779,176,'Clay'),(1780,176,'McCook'),(1781,176,'Lincoln'),(1782,176,'Turner'),(1783,176,'Lake'),(1784,176,'Moody'),(1785,176,'Hutchinson'),(1786,176,'Yankton'),(1787,176,'Kingsbury'),(1788,176,'Bon Homme'),(1789,176,'Codington'),(1790,176,'Deuel'),(1791,176,'Grant'),(1792,176,'Clark'),(1793,176,'Day'),(1794,176,'Hamlin'),(1795,176,'Roberts'),(1796,176,'Marshall'),(1797,176,'Davison'),(1798,176,'Hanson'),(1799,176,'Jerauld'),(1800,176,'Douglas'),(1801,176,'Sanborn'),(1802,176,'Gregory'),(1803,176,'Miner'),(1804,176,'Beadle'),(1805,176,'Brule'),(1806,176,'Charles Mix'),(1807,176,'Buffalo'),(1808,176,'Hyde'),(1809,176,'Hand'),(1810,176,'Lyman'),(1811,176,'Aurora'),(1812,176,'Brown'),(1813,176,'Walworth'),(1814,176,'Spink'),(1815,176,'Edmunds'),(1816,176,'Faulk'),(1817,176,'McPherson'),(1818,176,'Potter'),(1819,176,'Hughes'),(1820,176,'Sully'),(1821,176,'Jackson'),(1822,176,'Tripp'),(1823,176,'Jones'),(1824,176,'Stanley'),(1825,176,'Bennett'),(1826,176,'Haakon'),(1827,176,'Todd'),(1828,176,'Mellette'),(1829,176,'Perkins'),(1830,176,'Corson'),(1831,176,'Ziebach'),(1832,176,'Dewey'),(1833,176,'Meade'),(1834,176,'Campbell'),(1835,176,'Harding'),(1836,176,'Pennington'),(1837,176,'Shannon'),(1838,176,'Butte'),(1839,176,'Custer'),(1840,176,'Lawrence'),(1841,176,'Fall River'),(1842,166,'Richland'),(1843,166,'Cass'),(1844,166,'Traill'),(1845,166,'Sargent'),(1846,166,'Ransom'),(1847,166,'Barnes'),(1848,166,'Steele'),(1849,166,'Grand Forks'),(1850,166,'Walsh'),(1851,166,'Nelson'),(1852,166,'Pembina'),(1853,166,'Cavalier'),(1854,166,'Ramsey'),(1855,166,'Rolette'),(1856,166,'Pierce'),(1857,166,'Towner'),(1858,166,'Bottineau'),(1859,166,'Wells'),(1860,166,'Benson'),(1861,166,'Eddy'),(1862,166,'Stutsman'),(1863,166,'McIntosh'),(1864,166,'Lamoure'),(1865,166,'Griggs'),(1866,166,'Foster'),(1867,166,'Kidder'),(1868,166,'Sheridan'),(1869,166,'Dickey'),(1870,166,'Logan'),(1871,166,'Burleigh'),(1872,166,'Morton'),(1873,166,'Mercer'),(1874,166,'Emmons'),(1875,166,'Sioux'),(1876,166,'Grant'),(1877,166,'Oliver'),(1878,166,'McLean'),(1879,166,'Stark'),(1880,166,'Slope'),(1881,166,'Golden Valley'),(1882,166,'Bowman'),(1883,166,'Dunn'),(1884,166,'Billings'),(1885,166,'McKenzie'),(1886,166,'Adams'),(1887,166,'Hettinger'),(1888,166,'Ward'),(1889,166,'McHenry'),(1890,166,'Burke'),(1891,166,'Divide'),(1892,166,'Renville'),(1893,166,'Williams'),(1894,166,'Mountrail'),(1895,158,'Stillwater'),(1896,158,'Yellowstone'),(1897,158,'Rosebud'),(1898,158,'Carbon'),(1899,158,'Treasure'),(1900,158,'Sweet Grass'),(1901,158,'Big Horn'),(1902,158,'Park'),(1903,158,'Fergus'),(1904,158,'Wheatland'),(1905,158,'Golden Valley'),(1906,158,'Meagher'),(1907,158,'Musselshell'),(1908,158,'Garfield'),(1909,158,'Powder River'),(1910,158,'Petroleum'),(1911,158,'Roosevelt'),(1912,158,'Sheridan'),(1913,158,'McCone'),(1914,158,'Richland'),(1915,158,'Daniels'),(1916,158,'Valley'),(1917,158,'Dawson'),(1918,158,'Phillips'),(1919,158,'Custer'),(1920,158,'Carter'),(1921,158,'Fallon'),(1922,158,'Prairie'),(1923,158,'Wibaux'),(1924,158,'Cascade'),(1925,158,'Lewis And Clark'),(1926,158,'Pondera'),(1927,158,'Teton'),(1928,158,'Chouteau'),(1929,158,'Toole'),(1930,158,'Judith Basin'),(1931,158,'Liberty'),(1932,158,'Hill'),(1933,158,'Blaine'),(1934,158,'Jefferson'),(1935,158,'Broadwater'),(1936,158,'Silver Bow'),(1937,158,'Madison'),(1938,158,'Deer Lodge'),(1939,158,'Powell'),(1940,158,'Gallatin'),(1941,158,'Beaverhead'),(1942,158,'Missoula'),(1943,158,'Mineral'),(1944,158,'Lake'),(1945,158,'Ravalli'),(1946,158,'Sanders'),(1947,158,'Granite'),(1948,158,'Flathead'),(1949,158,'Lincoln'),(1950,144,'McHenry'),(1951,144,'Lake'),(1952,144,'Cook'),(1953,144,'Du Page'),(1954,144,'Kane'),(1955,144,'De Kalb'),(1956,144,'Ogle'),(1957,144,'Will'),(1958,144,'Grundy'),(1959,144,'Livingston'),(1960,144,'La Salle'),(1961,144,'Kendall'),(1962,144,'Lee'),(1963,144,'Kankakee'),(1964,144,'Iroquois'),(1965,144,'Ford'),(1966,144,'Vermilion'),(1967,144,'Champaign'),(1968,144,'Jo Daviess'),(1969,144,'Boone'),(1970,144,'Stephenson'),(1971,144,'Carroll'),(1972,144,'Winnebago'),(1973,144,'Whiteside'),(1974,144,'Rock Island'),(1975,144,'Mercer'),(1976,144,'Henry'),(1977,144,'Bureau'),(1978,144,'Putnam'),(1979,144,'Marshall'),(1980,144,'Knox'),(1981,144,'McDonough'),(1982,144,'Fulton'),(1983,144,'Warren'),(1984,144,'Henderson'),(1985,144,'Stark'),(1986,144,'Hancock'),(1987,144,'Peoria'),(1988,144,'Schuyler'),(1989,144,'Woodford'),(1990,144,'Mason'),(1991,144,'Tazewell'),(1992,144,'McLean'),(1993,144,'Logan'),(1994,144,'Dewitt'),(1995,144,'Macon'),(1996,144,'Piatt'),(1997,144,'Douglas'),(1998,144,'Coles'),(1999,144,'Moultrie'),(2000,144,'Edgar'),(2001,144,'Shelby'),(2002,144,'Madison'),(2003,144,'Calhoun'),(2004,144,'Macoupin'),(2005,144,'Fayette'),(2006,144,'Jersey'),(2007,144,'Montgomery'),(2008,144,'Greene'),(2009,144,'Bond'),(2010,144,'Saint Clair'),(2011,144,'Christian'),(2012,144,'Washington'),(2013,144,'Clinton'),(2014,144,'Randolph'),(2015,144,'Monroe'),(2016,144,'Perry'),(2017,144,'Adams'),(2018,144,'Pike'),(2019,144,'Brown'),(2020,144,'Effingham'),(2021,144,'Wabash'),(2022,144,'Crawford'),(2023,144,'Lawrence'),(2024,144,'Richland'),(2025,144,'Clark'),(2026,144,'Cumberland'),(2027,144,'Jasper'),(2028,144,'Clay'),(2029,144,'Wayne'),(2030,144,'Edwards'),(2031,144,'Sangamon'),(2032,144,'Morgan'),(2033,144,'Scott'),(2034,144,'Cass'),(2035,144,'Menard'),(2036,144,'Marion'),(2037,144,'Franklin'),(2038,144,'Jefferson'),(2039,144,'Hamilton'),(2040,144,'White'),(2041,144,'Williamson'),(2042,144,'Gallatin'),(2043,144,'Jackson'),(2044,144,'Union'),(2045,144,'Johnson'),(2046,144,'Massac'),(2047,144,'Alexander'),(2048,144,'Saline'),(2049,144,'Hardin'),(2050,144,'Pope'),(2051,144,'Pulaski'),(2052,157,'Saint Louis'),(2053,157,'Jefferson'),(2054,157,'Franklin'),(2055,157,'Saint Francois'),(2056,157,'Washington'),(2057,157,'Gasconade'),(2058,157,'Saint Louis City');
INSERT INTO `lkupcounty`(countyId, stateid, countyName) VALUES (2059,157,'Saint Charles'),(2060,157,'Pike'),(2061,157,'Montgomery'),(2062,157,'Warren'),(2063,157,'Lincoln'),(2064,157,'Audrain'),(2065,157,'Callaway'),(2066,157,'Marion'),(2067,157,'Clark'),(2068,157,'Macon'),(2069,157,'Scotland'),(2070,157,'Shelby'),(2071,157,'Lewis'),(2072,157,'Ralls'),(2073,157,'Knox'),(2074,157,'Monroe'),(2075,157,'Adair'),(2076,157,'Schuyler'),(2077,157,'Sullivan'),(2078,157,'Putnam'),(2079,157,'Linn'),(2080,157,'Iron'),(2081,157,'Reynolds'),(2082,157,'Sainte Genevieve'),(2083,157,'Wayne'),(2084,157,'Madison'),(2085,157,'Bollinger'),(2086,157,'Cape Girardeau'),(2087,157,'Stoddard'),(2088,157,'Perry'),(2089,157,'Scott'),(2090,157,'Mississippi'),(2091,157,'Dunklin'),(2092,157,'Pemiscot'),(2093,157,'New Madrid'),(2094,157,'Butler'),(2095,157,'Ripley'),(2096,157,'Carter'),(2097,157,'Lafayette'),(2098,157,'Cass'),(2099,157,'Jackson'),(2100,157,'Ray'),(2101,157,'Platte'),(2102,157,'Johnson'),(2103,157,'Clay'),(2104,157,'Buchanan'),(2105,157,'Gentry'),(2106,157,'Worth'),(2107,157,'Andrew'),(2108,157,'Dekalb'),(2109,157,'Nodaway'),(2110,157,'Harrison'),(2111,157,'Clinton'),(2112,157,'Holt'),(2113,157,'Atchison'),(2114,157,'Livingston'),(2115,157,'Daviess'),(2116,157,'Carroll'),(2117,157,'Caldwell'),(2118,157,'Grundy'),(2119,157,'Chariton'),(2120,157,'Mercer'),(2121,157,'Bates'),(2122,157,'Saint Clair'),(2123,157,'Henry'),(2124,157,'Vernon'),(2125,157,'Cedar'),(2126,157,'Barton'),(2127,157,'Jasper'),(2128,157,'McDonald'),(2129,157,'Newton'),(2130,157,'Barry'),(2131,157,'Osage'),(2132,157,'Boone'),(2133,157,'Morgan'),(2134,157,'Maries'),(2135,157,'Miller'),(2136,157,'Moniteau'),(2137,157,'Camden'),(2138,157,'Cole'),(2139,157,'Cooper'),(2140,157,'Howard'),(2141,157,'Randolph'),(2142,157,'Pettis'),(2143,157,'Saline'),(2144,157,'Benton'),(2145,157,'Phelps'),(2146,157,'Shannon'),(2147,157,'Dent'),(2148,157,'Crawford'),(2149,157,'Texas'),(2150,157,'Pulaski'),(2151,157,'Laclede'),(2152,157,'Howell'),(2153,157,'Dallas'),(2154,157,'Polk'),(2155,157,'Dade'),(2156,157,'Greene'),(2157,157,'Lawrence'),(2158,157,'Oregon'),(2159,157,'Douglas'),(2160,157,'Ozark'),(2161,157,'Christian'),(2162,157,'Stone'),(2163,157,'Taney'),(2164,157,'Hickory'),(2165,157,'Webster'),(2166,157,'Wright'),(2167,147,'Atchison'),(2168,147,'Douglas'),(2169,147,'Leavenworth'),(2170,147,'Doniphan'),(2171,147,'Linn'),(2172,147,'Wyandotte'),(2173,147,'Miami'),(2174,147,'Anderson'),(2175,147,'Johnson'),(2176,147,'Franklin'),(2177,147,'Jefferson'),(2178,147,'Wabaunsee'),(2179,147,'Shawnee'),(2180,147,'Marshall'),(2181,147,'Nemaha'),(2182,147,'Pottawatomie'),(2183,147,'Osage'),(2184,147,'Jackson'),(2185,147,'Brown'),(2186,147,'Geary'),(2187,147,'Riley'),(2188,147,'Bourbon'),(2189,147,'Wilson'),(2190,147,'Crawford'),(2191,147,'Cherokee'),(2192,147,'Neosho'),(2193,147,'Allen'),(2194,147,'Woodson'),(2195,147,'Lyon'),(2196,147,'Morris'),(2197,147,'Coffey'),(2198,147,'Marion'),(2199,147,'Butler'),(2200,147,'Chase'),(2201,147,'Greenwood'),(2202,147,'Cloud'),(2203,147,'Republic'),(2204,147,'Smith'),(2205,147,'Washington'),(2206,147,'Jewell'),(2207,147,'Sedgwick'),(2208,147,'Harper'),(2209,147,'Sumner'),(2210,147,'Cowley'),(2211,147,'Harvey'),(2212,147,'Pratt'),(2213,147,'Chautauqua'),(2214,147,'Comanche'),(2215,147,'Kingman'),(2216,147,'Kiowa'),(2217,147,'Barber'),(2218,147,'McPherson'),(2219,147,'Montgomery'),(2220,147,'Labette'),(2221,147,'Elk'),(2222,147,'Saline'),(2223,147,'Dickinson'),(2224,147,'Lincoln'),(2225,147,'Mitchell'),(2226,147,'Ottawa'),(2227,147,'Rice'),(2228,147,'Clay'),(2229,147,'Osborne'),(2230,147,'Ellsworth'),(2231,147,'Reno'),(2232,147,'Barton'),(2233,147,'Rush'),(2234,147,'Ness'),(2235,147,'Edwards'),(2236,147,'Pawnee'),(2237,147,'Stafford'),(2238,147,'Ellis'),(2239,147,'Phillips'),(2240,147,'Norton'),(2241,147,'Graham'),(2242,147,'Russell'),(2243,147,'Trego'),(2244,147,'Rooks'),(2245,147,'Decatur'),(2246,147,'Thomas'),(2247,147,'Rawlins'),(2248,147,'Cheyenne'),(2249,147,'Sherman'),(2250,147,'Gove'),(2251,147,'Sheridan'),(2252,147,'Logan'),(2253,147,'Wallace'),(2254,147,'Ford'),(2255,147,'Clark'),(2256,147,'Gray'),(2257,147,'Hamilton'),(2258,147,'Kearny'),(2259,147,'Lane'),(2260,147,'Meade'),(2261,147,'Finney'),(2262,147,'Hodgeman'),(2263,147,'Stanton'),(2264,147,'Seward'),(2265,147,'Wichita'),(2266,147,'Haskell'),(2267,147,'Scott'),(2268,147,'Greeley'),(2269,147,'Grant'),(2270,147,'Morton'),(2271,147,'Stevens'),(2272,159,'Butler'),(2273,159,'Washington'),(2274,159,'Saunders'),(2275,159,'Cuming'),(2276,159,'Sarpy'),(2277,159,'Douglas'),(2278,159,'Cass'),(2279,159,'Burt'),(2280,159,'Dodge'),(2281,159,'Dakota'),(2282,159,'Thurston'),(2283,159,'Gage'),(2284,159,'Thayer'),(2285,159,'Nemaha'),(2286,159,'Seward'),(2287,159,'York'),(2288,159,'Lancaster'),(2289,159,'Pawnee'),(2290,159,'Otoe'),(2291,159,'Johnson'),(2292,159,'Saline'),(2293,159,'Richardson'),(2294,159,'Jefferson'),(2295,159,'Fillmore'),(2296,159,'Clay'),(2297,159,'Platte'),(2298,159,'Boone'),(2299,159,'Wheeler'),(2300,159,'Nance'),(2301,159,'Merrick'),(2302,159,'Colfax'),(2303,159,'Antelope'),(2304,159,'Polk'),(2305,159,'Greeley'),(2306,159,'Madison'),(2307,159,'Dixon'),(2308,159,'Holt'),(2309,159,'Rock'),(2310,159,'Cedar'),(2311,159,'Knox'),(2312,159,'Boyd'),(2313,159,'Wayne'),(2314,159,'Pierce'),(2315,159,'Keya Paha'),(2316,159,'Stanton'),(2317,159,'Hall'),(2318,159,'Buffalo'),(2319,159,'Custer'),(2320,159,'Valley'),(2321,159,'Sherman'),(2322,159,'Hamilton'),(2323,159,'Howard'),(2324,159,'Blaine'),(2325,159,'Garfield'),(2326,159,'Dawson'),(2327,159,'Loup'),(2328,159,'Adams'),(2329,159,'Harlan'),(2330,159,'Furnas'),(2331,159,'Phelps'),(2332,159,'Kearney'),(2333,159,'Webster'),(2334,159,'Franklin'),(2335,159,'Gosper'),(2336,159,'Nuckolls'),(2337,159,'Red Willow'),(2338,159,'Dundy'),(2339,159,'Chase'),(2340,159,'Hitchcock'),(2341,159,'Frontier'),(2342,159,'Hayes'),(2343,159,'Lincoln'),(2344,159,'Arthur'),(2345,159,'Deuel'),(2346,159,'Morrill'),(2347,159,'Keith'),(2348,159,'Kimball'),(2349,159,'Cheyenne'),(2350,159,'Perkins'),(2351,159,'Cherry'),(2352,159,'Thomas'),(2353,159,'Garden'),(2354,159,'Hooker'),(2355,159,'Logan'),(2356,159,'McPherson'),(2357,159,'Brown'),(2358,159,'Box Butte'),(2359,159,'Grant'),(2360,159,'Sheridan'),(2361,159,'Dawes'),(2362,159,'Scotts Bluff'),(2363,159,'Banner'),(2364,159,'Sioux'),(2365,149,'Jefferson'),(2366,149,'Saint Charles'),(2367,149,'Saint Bernard'),(2368,149,'Plaquemines'),(2369,149,'St John the Baptist'),(2370,149,'Saint James'),(2371,149,'Orleans'),(2372,149,'Lafourche'),(2373,149,'Assumption'),(2374,149,'Saint Mary'),(2375,149,'Terrebonne'),(2376,149,'Ascension'),(2377,149,'Tangipahoa'),(2378,149,'Saint Tammany'),(2379,149,'Washington'),(2380,149,'Saint Helena'),(2381,149,'Livingston'),(2382,149,'Lafayette'),(2383,149,'Vermilion'),(2384,149,'Saint Landry'),(2385,149,'Iberia'),(2386,149,'Evangeline'),(2387,149,'Acadia'),(2388,149,'Saint Martin'),(2389,149,'Jefferson Davis'),(2390,149,'Calcasieu'),(2391,149,'Cameron'),(2392,149,'Beauregard'),(2393,149,'Allen'),(2394,149,'Vernon'),(2395,149,'East Baton Rouge'),(2396,149,'West Baton Rouge'),(2397,149,'West Feliciana'),(2398,149,'Pointe Coupee'),(2399,149,'Iberville'),(2400,149,'East Feliciana'),(2401,149,'Bienville'),(2402,149,'Natchitoches'),(2403,149,'Claiborne'),(2404,149,'Caddo'),(2405,149,'Bossier'),(2406,149,'Webster'),(2407,149,'Red River'),(2408,149,'De Soto'),(2409,149,'Sabine'),(2410,149,'Ouachita'),(2411,149,'Richland'),(2412,149,'Franklin'),(2413,149,'Morehouse'),(2414,149,'Union'),(2415,149,'Jackson'),(2416,149,'Lincoln'),(2417,149,'Madison'),(2418,149,'West Carroll'),(2419,149,'East Carroll'),(2420,149,'Rapides'),(2421,149,'Concordia'),(2422,149,'Avoyelles'),(2423,149,'Catahoula'),(2424,149,'La Salle'),(2425,149,'Tensas'),(2426,149,'Winn'),(2427,149,'Grant'),(2428,149,'Caldwell'),(2429,132,'Jefferson'),(2430,132,'Desha'),(2431,132,'Bradley'),(2432,132,'Ashley'),(2433,132,'Chicot'),(2434,132,'Lincoln'),(2435,132,'Cleveland'),(2436,132,'Drew'),(2437,132,'Ouachita'),(2438,132,'Clark'),(2439,132,'Nevada'),(2440,132,'Union'),(2441,132,'Dallas'),(2442,132,'Columbia'),(2443,132,'Calhoun'),(2444,132,'Hempstead'),(2445,132,'Little River'),(2446,132,'Sevier'),(2447,132,'Lafayette'),(2448,132,'Howard'),(2449,132,'Miller'),(2450,132,'Garland'),(2451,132,'Pike'),(2452,132,'Hot Spring'),(2453,132,'Polk'),(2454,132,'Montgomery'),(2455,132,'Perry'),(2456,132,'Pulaski'),(2457,132,'Arkansas'),(2458,132,'Jackson'),(2459,132,'Woodruff'),(2460,132,'Lonoke'),(2461,132,'White'),(2462,132,'Saline'),(2463,132,'Van Buren'),(2464,132,'Prairie'),(2465,132,'Monroe'),(2466,132,'Conway'),(2467,132,'Faulkner'),(2468,132,'Cleburne'),(2469,132,'Stone'),(2470,132,'Grant'),(2471,132,'Independence'),(2472,132,'Crittenden'),(2473,132,'Mississippi'),(2474,132,'Lee'),(2475,132,'Phillips'),(2476,132,'Saint Francis'),(2477,132,'Cross'),(2478,132,'Poinsett'),(2479,132,'Craighead'),(2480,132,'Lawrence'),(2481,132,'Greene'),(2482,132,'Randolph'),(2483,132,'Clay'),(2484,132,'Sharp'),(2485,132,'Izard'),(2486,132,'Fulton'),(2487,132,'Baxter'),(2488,132,'Boone'),(2489,132,'Carroll'),(2490,132,'Marion'),(2491,132,'Newton'),(2492,132,'Searcy'),(2493,132,'Pope'),(2494,132,'Washington'),(2495,132,'Benton'),(2496,132,'Madison'),(2497,132,'Franklin'),(2498,132,'Yell'),(2499,132,'Logan'),(2500,132,'Johnson'),(2501,132,'Scott'),(2502,132,'Sebastian'),(2503,132,'Crawford'),(2504,169,'Caddo'),(2505,169,'Grady'),(2506,169,'Oklahoma'),(2507,169,'McClain'),(2508,169,'Stephens'),(2509,169,'Canadian'),(2510,169,'Kingfisher'),(2511,169,'Cleveland'),(2512,169,'Washita'),(2513,169,'Logan'),(2514,169,'Murray'),(2515,169,'Blaine'),(2516,169,'Kiowa'),(2517,169,'Garvin'),(2518,169,'Noble'),(2519,169,'Custer'),(2520,178,'Travis'),(2521,169,'Carter'),(2522,169,'Love'),(2523,169,'Johnston'),(2524,169,'Marshall'),(2525,169,'Bryan'),(2526,169,'Jefferson'),(2527,169,'Comanche'),(2528,169,'Jackson'),(2529,169,'Tillman'),(2530,169,'Cotton'),(2531,169,'Harmon'),(2532,169,'Greer'),(2533,169,'Beckham'),(2534,169,'Roger Mills'),(2535,169,'Dewey'),(2536,169,'Garfield'),(2537,169,'Alfalfa'),(2538,169,'Woods'),(2539,169,'Major'),(2540,169,'Grant'),(2541,169,'Woodward'),(2542,169,'Ellis'),(2543,169,'Harper'),(2544,169,'Beaver'),(2545,169,'Texas'),(2546,169,'Cimarron'),(2547,169,'Osage'),(2548,169,'Washington'),(2549,169,'Tulsa'),(2550,169,'Creek'),(2551,169,'Wagoner'),(2552,169,'Rogers'),(2553,169,'Pawnee'),(2554,169,'Payne'),(2555,169,'Lincoln'),(2556,169,'Nowata'),(2557,169,'Craig'),(2558,169,'Mayes'),(2559,169,'Ottawa'),(2560,169,'Delaware'),(2561,169,'Muskogee'),(2562,169,'Okmulgee'),(2563,169,'Pittsburg'),(2564,169,'McIntosh'),(2565,169,'Cherokee'),(2566,169,'Sequoyah'),(2567,169,'Haskell'),(2568,169,'Adair'),(2569,169,'Pushmataha'),(2570,169,'Atoka'),(2571,169,'Hughes'),(2572,169,'Coal'),(2573,169,'Latimer'),(2574,169,'Le Flore'),(2575,169,'Kay'),(2576,169,'McCurtain'),(2577,169,'Choctaw'),(2578,169,'Pottawatomie'),(2579,169,'Seminole'),(2580,169,'Pontotoc'),(2581,169,'Okfuskee'),(2582,178,'Dallas'),(2583,178,'Collin'),(2584,178,'Denton'),(2585,178,'Grayson'),(2586,178,'Rockwall'),(2587,178,'Ellis'),(2588,178,'Navarro'),(2589,178,'Van Zandt'),(2590,178,'Kaufman'),(2591,178,'Henderson'),(2592,178,'Hunt'),(2593,178,'Wood'),(2594,178,'Lamar'),(2595,178,'Red River'),(2596,178,'Fannin'),(2597,178,'Delta'),(2598,178,'Hopkins'),(2599,178,'Rains'),(2600,178,'Camp'),(2601,178,'Titus'),(2602,178,'Franklin'),(2603,178,'Bowie'),(2604,178,'Cass'),(2605,178,'Marion'),(2606,178,'Morris'),(2607,178,'Gregg'),(2608,178,'Panola'),(2609,178,'Upshur'),(2610,178,'Harrison'),(2611,178,'Rusk'),(2612,178,'Smith'),(2613,178,'Cherokee'),(2614,178,'Nacogdoches'),(2615,178,'Anderson'),(2616,178,'Leon'),(2617,178,'Trinity'),(2618,178,'Houston'),(2619,178,'Freestone'),(2620,178,'Madison'),(2621,178,'Angelina'),(2622,178,'Newton'),(2623,178,'San Augustine'),(2624,178,'Sabine'),(2625,178,'Polk'),(2626,178,'Shelby'),(2627,178,'Tyler'),(2628,178,'Jasper'),(2629,178,'Tarrant'),(2630,178,'Parker'),(2631,178,'Johnson'),(2632,178,'Wise'),(2633,178,'Hood'),(2634,178,'Somervell'),(2635,178,'Hill'),(2636,178,'Palo Pinto'),(2637,178,'Clay'),(2638,178,'Montague'),(2639,178,'Cooke'),(2640,178,'Wichita'),(2641,178,'Archer'),(2642,178,'Knox'),(2643,178,'Wilbarger'),(2644,178,'Young'),(2645,178,'Baylor'),(2646,178,'Haskell'),(2647,178,'Erath'),(2648,178,'Stephens'),(2649,178,'Jack'),(2650,178,'Shackelford'),(2651,178,'Brown'),(2652,178,'Eastland'),(2653,178,'Hamilton'),(2654,178,'Comanche'),(2655,178,'Callahan'),(2656,178,'Throckmorton'),(2657,178,'Bell'),(2658,178,'Milam'),(2659,178,'Coryell'),(2660,178,'McLennan'),(2661,178,'Williamson'),(2662,178,'Lampasas'),(2663,178,'Falls'),(2664,178,'Robertson'),(2665,178,'Bosque'),(2666,178,'Limestone'),(2667,178,'Mason'),(2668,178,'Runnels'),(2669,178,'McCulloch'),(2670,178,'Coleman'),(2671,178,'Llano'),(2672,178,'San Saba'),(2673,178,'Concho'),(2674,178,'Menard'),(2675,178,'Mills'),(2676,178,'Kimble'),(2677,178,'Edwards'),(2678,178,'Tom Green'),(2679,178,'Irion'),(2680,178,'Reagan'),(2681,178,'Coke'),(2682,178,'Schleicher'),(2683,178,'Crockett'),(2684,178,'Sutton'),(2685,178,'Sterling'),(2686,178,'Harris'),(2687,178,'Montgomery'),(2688,178,'Walker'),(2689,178,'Liberty'),(2690,178,'San Jacinto'),(2691,178,'Grimes'),(2692,178,'Hardin'),(2693,178,'Matagorda'),(2694,178,'Fort Bend'),(2695,178,'Colorado'),(2696,178,'Austin'),(2697,178,'Wharton'),(2698,178,'Brazoria'),(2699,178,'Waller'),(2700,178,'Washington'),(2701,178,'Galveston'),(2702,178,'Chambers'),(2703,178,'Orange'),(2704,178,'Jefferson'),(2705,178,'Brazos'),(2706,178,'Burleson'),(2707,178,'Lee'),(2708,178,'Victoria'),(2709,178,'Refugio'),(2710,178,'De Witt'),(2711,178,'Jackson'),(2712,178,'Goliad'),(2713,178,'Lavaca'),(2714,178,'Calhoun'),(2715,178,'La Salle'),(2716,178,'Bexar'),(2717,178,'Bandera'),(2718,178,'Kendall'),(2719,178,'Frio'),(2720,178,'McMullen'),(2721,178,'Atascosa'),(2722,178,'Medina'),(2723,178,'Kerr'),(2724,178,'Live Oak'),(2725,178,'Webb'),(2726,178,'Zapata'),(2727,178,'Comal'),(2728,178,'Bee'),(2729,178,'Guadalupe'),(2730,178,'Karnes'),(2731,178,'Wilson'),(2732,178,'Gonzales'),(2733,178,'Nueces'),(2734,178,'Jim Wells'),(2735,178,'San Patricio'),(2736,178,'Kenedy'),(2737,178,'Duval'),(2738,178,'Brooks'),(2739,178,'Aransas'),(2740,178,'Jim Hogg'),(2741,178,'Kleberg'),(2742,178,'Hidalgo'),(2743,178,'Cameron'),(2744,178,'Starr'),(2745,178,'Willacy'),(2746,178,'Bastrop'),(2747,178,'Burnet'),(2748,178,'Blanco'),(2749,178,'Hays'),(2750,178,'Caldwell'),(2751,178,'Gillespie'),(2752,178,'Uvalde'),(2753,178,'Dimmit'),(2754,178,'Zavala'),(2755,178,'Kinney'),(2756,178,'Real'),(2757,178,'Val Verde'),(2758,178,'Terrell'),(2759,178,'Maverick'),(2760,178,'Fayette'),(2761,178,'Oldham'),(2762,178,'Gray'),(2763,178,'Wheeler'),(2764,178,'Lipscomb'),(2765,178,'Hutchinson'),(2766,178,'Parmer'),(2767,178,'Potter'),(2768,178,'Moore'),(2769,178,'Hemphill'),(2770,178,'Randall'),(2771,178,'Hartley'),(2772,178,'Armstrong'),(2773,178,'Hale'),(2774,178,'Dallam'),(2775,178,'Deaf Smith'),(2776,178,'Castro'),(2777,178,'Lamb'),(2778,178,'Ochiltree'),(2779,178,'Carson'),(2780,178,'Hansford'),(2781,178,'Swisher'),(2782,178,'Roberts'),(2783,178,'Collingsworth'),(2784,178,'Sherman'),(2785,178,'Childress'),(2786,178,'Dickens'),(2787,178,'Floyd'),(2788,178,'Cottle'),(2789,178,'Hardeman'),(2790,178,'Donley'),(2791,178,'Foard'),(2792,178,'Hall'),(2793,178,'Motley'),(2794,178,'King'),(2795,178,'Briscoe'),(2796,178,'Hockley'),(2797,178,'Cochran'),(2798,178,'Terry'),(2799,178,'Bailey'),(2800,178,'Crosby'),(2801,178,'Yoakum'),(2802,178,'Lubbock'),(2803,178,'Garza'),(2804,178,'Dawson'),(2805,178,'Gaines'),(2806,178,'Lynn'),(2807,178,'Jones'),(2808,178,'Stonewall'),(2809,178,'Nolan'),(2810,178,'Taylor'),(2811,178,'Howard'),(2812,178,'Mitchell'),(2813,178,'Scurry'),(2814,178,'Kent'),(2815,178,'Fisher'),(2816,178,'Midland'),(2817,178,'Andrews'),(2818,178,'Reeves'),(2819,178,'Ward'),(2820,178,'Pecos'),(2821,178,'Crane'),(2822,178,'Jeff Davis'),(2823,178,'Borden'),(2824,178,'Glasscock'),(2825,178,'Ector'),(2826,178,'Winkler'),(2827,178,'Martin'),(2828,178,'Upton'),(2829,178,'Loving'),(2830,178,'El Paso'),(2831,178,'Brewster'),(2832,178,'Hudspeth'),(2833,178,'Presidio'),(2834,178,'Culberson'),(2835,134,'Jefferson'),(2836,134,'Arapahoe'),(2837,134,'Adams'),(2838,134,'Boulder'),(2839,134,'Elbert'),(2840,134,'Douglas'),(2841,134,'Denver'),(2842,134,'El Paso'),(2843,134,'Park'),(2844,134,'Gilpin'),(2845,134,'Eagle'),(2846,134,'Summit'),(2847,134,'Routt'),(2848,134,'Lake'),(2849,134,'Jackson'),(2850,134,'Clear Creek'),(2851,134,'Grand'),(2852,134,'Weld'),(2853,134,'Larimer'),(2854,134,'Morgan'),(2855,134,'Washington'),(2856,134,'Phillips'),(2857,134,'Logan'),(2858,134,'Yuma'),(2859,134,'Sedgwick'),(2860,134,'Cheyenne'),(2861,134,'Lincoln'),(2862,134,'Kit Carson'),(2863,134,'Teller'),(2864,134,'Mohave'),(2865,134,'Pueblo'),(2866,134,'Las Animas'),(2867,134,'Kiowa'),(2868,134,'Baca'),(2869,134,'Otero'),(2870,134,'Crowley'),(2871,134,'Bent'),(2872,134,'Huerfano'),(2873,134,'Prowers'),(2874,134,'Alamosa'),(2875,134,'Conejos'),(2876,134,'Archuleta'),(2877,134,'La Plata'),(2878,134,'Costilla'),(2879,134,'Saguache'),(2880,134,'Mineral'),(2881,134,'Rio Grande'),(2882,134,'Chaffee'),(2883,134,'Gunnison'),(2884,134,'Fremont'),(2885,134,'Montrose'),(2886,134,'Hinsdale'),(2887,134,'Custer'),(2888,134,'Dolores'),(2889,134,'Montezuma'),(2890,134,'San Miguel'),(2891,134,'Delta'),(2892,134,'Ouray'),(2893,134,'San Juan'),(2894,134,'Mesa'),(2895,134,'Garfield'),(2896,134,'Moffat'),(2897,134,'Pitkin'),(2898,134,'Rio Blanco'),(2899,186,'Laramie'),(2900,186,'Albany'),(2901,186,'Park'),(2902,186,'Platte'),(2903,186,'Goshen'),(2904,186,'Niobrara'),(2905,186,'Converse'),(2906,186,'Carbon'),(2907,186,'Fremont'),(2908,186,'Sweetwater'),(2909,186,'Washakie'),(2910,186,'Big Horn'),(2911,186,'Hot Springs'),(2912,186,'Natrona'),(2913,186,'Johnson'),(2914,186,'Weston'),(2915,186,'Crook'),(2916,186,'Campbell'),(2917,186,'Sheridan'),(2918,186,'Sublette'),(2919,186,'Uinta'),(2920,186,'Teton'),(2921,186,'Lincoln'),(2922,143,'Bannock'),(2923,143,'Bingham'),(2924,143,'Power'),(2925,143,'Butte'),(2926,143,'Caribou'),(2927,143,'Bear Lake'),(2928,143,'Custer'),(2929,143,'Franklin'),(2930,143,'Lemhi'),(2931,143,'Oneida'),(2932,143,'Twin Falls'),(2933,143,'Cassia'),(2934,143,'Blaine'),(2935,143,'Gooding'),(2936,143,'Camas'),(2937,143,'Lincoln'),(2938,143,'Jerome'),(2939,143,'Minidoka'),(2940,143,'Bonneville'),(2941,143,'Fremont'),(2942,143,'Teton'),(2943,143,'Clark'),(2944,143,'Jefferson'),(2945,143,'Madison'),(2946,143,'Nez Perce'),(2947,143,'Clearwater'),(2948,143,'Idaho'),(2949,143,'Lewis'),(2950,143,'Latah'),(2951,143,'Elmore'),(2952,143,'Boise'),(2953,143,'Owyhee'),(2954,143,'Canyon'),(2955,143,'Washington'),(2956,143,'Valley'),(2957,143,'Adams'),(2958,143,'Ada'),(2959,143,'Gem'),(2960,143,'Payette'),(2961,143,'Kootenai'),(2962,143,'Shoshone'),(2963,143,'Bonner'),(2964,143,'Boundary'),(2965,143,'Benewah'),(2966,179,'Duchesne'),(2967,179,'Utah'),(2968,179,'Salt Lake'),(2969,179,'Uintah'),(2970,179,'Davis'),(2971,179,'Summit'),(2972,179,'Morgan'),(2973,179,'Tooele'),(2974,179,'Daggett'),(2975,179,'Rich'),(2976,179,'Wasatch'),(2977,179,'Weber'),(2978,179,'Box Elder'),(2979,179,'Cache'),(2980,179,'Carbon'),(2981,179,'San Juan'),(2982,179,'Emery'),(2983,179,'Grand'),(2984,179,'Sevier'),(2985,179,'Sanpete'),(2986,179,'Millard'),(2987,179,'Juab'),(2988,179,'Kane'),(2989,179,'Beaver'),(2990,179,'Iron'),(2991,179,'Wayne'),(2992,179,'Washington'),(2993,179,'Piute'),(2994,131,'Maricopa'),(2995,131,'Pinal'),(2996,131,'Gila'),(2997,131,'Pima'),(2998,131,'Yavapai'),(2999,131,'La Paz'),(3000,131,'Yuma'),(3001,131,'Mohave'),(3002,131,'Graham'),(3003,131,'Greenlee'),(3004,131,'Cochise'),(3005,131,'Santa Cruz'),(3006,131,'Navajo'),(3007,131,'Apache'),(3008,131,'Coconino'),(3009,163,'Sandoval'),(3010,163,'Valencia'),(3011,163,'Cibola'),(3012,163,'Bernalillo'),(3013,163,'Torrance'),(3014,163,'Santa Fe'),(3015,163,'Socorro'),(3016,163,'Rio Arriba'),(3017,163,'San Juan'),(3018,163,'McKinley'),(3019,163,'Taos'),(3020,163,'San Miguel'),(3021,163,'Los Alamos'),(3022,163,'Colfax'),(3023,163,'Guadalupe'),(3024,163,'Mora'),(3025,163,'Harding'),(3026,163,'Catron'),(3027,163,'Sierra'),(3028,163,'Dona Ana'),(3029,163,'Hidalgo'),(3030,163,'Grant'),(3031,163,'Luna'),(3032,163,'Curry'),(3033,163,'Roosevelt'),(3034,163,'Lea'),(3035,163,'De Baca'),(3036,163,'Quay'),(3037,163,'Chaves'),(3038,163,'Eddy'),(3039,163,'Lincoln'),(3040,163,'Otero'),(3041,163,'Union'),(3042,160,'Clark'),(3043,160,'Lincoln'),(3044,160,'Nye'),(3045,160,'Esmeralda'),(3046,160,'White Pine'),(3047,160,'Lander'),(3048,160,'Eureka'),(3049,160,'Washoe'),(3050,160,'Lyon'),(3051,160,'Humboldt'),(3052,160,'Churchill'),(3053,160,'Douglas'),(3054,160,'Mineral'),(3055,160,'Pershing'),(3056,160,'Storey'),(3057,160,'Carson City'),(3058,160,'Elko'),(3059,133,'Los Angeles'),(3060,133,'Orange'),(3061,133,'Ventura'),(3062,133,'San Bernardino'),(3063,133,'Riverside'),(3064,133,'San Diego'),(3065,133,'Imperial'),(3066,133,'Inyo'),(3067,133,'Santa Barbara'),(3068,133,'Tulare'),(3069,133,'Kings'),(3070,133,'Kern'),(3071,133,'Fresno'),(3072,133,'San Luis Obispo'),(3073,133,'Monterey'),(3074,133,'Mono'),(3075,133,'Madera'),(3076,133,'Merced'),(3077,133,'Mariposa'),(3078,133,'San Mateo'),(3079,133,'Santa Clara'),(3080,133,'San Francisco'),(3081,133,'Sacramento'),(3082,133,'Alameda'),(3083,133,'Napa');
INSERT INTO `lkupcounty`(countyId, stateid, countyName) VALUES (3084,133,'Contra Costa'),(3085,133,'Solano'),(3086,133,'Marin'),(3087,133,'Sonoma'),(3088,133,'Santa Cruz'),(3089,133,'San Benito'),(3090,133,'San Joaquin'),(3091,133,'Calaveras'),(3092,133,'Tuolumne'),(3093,133,'Stanislaus'),(3094,133,'Mendocino'),(3095,133,'Lake'),(3096,133,'Humboldt'),(3097,133,'Trinity'),(3098,133,'Del Norte'),(3099,133,'Siskiyou'),(3100,133,'Amador'),(3101,133,'Placer'),(3102,133,'Yolo'),(3103,133,'El Dorado'),(3104,133,'Alpine'),(3105,133,'Sutter'),(3106,133,'Yuba'),(3107,133,'Nevada'),(3108,133,'Sierra'),(3109,133,'Colusa'),(3110,133,'Glenn'),(3111,133,'Butte'),(3112,133,'Plumas'),(3113,133,'Shasta'),(3114,133,'Modoc'),(3115,133,'Lassen'),(3116,133,'Tehama'),(3117,142,'Honolulu'),(3118,142,'Kauai'),(3119,142,'Hawaii'),(3120,142,'Maui'),(3121,130,'American Samoa'),(3122,141,'Guam'),(3123,171,'Palau'),(3124,138,'Federated States of Micro'),(3125,167,'Northern Mariana Islands'),(3126,151,'Marshall Islands'),(3127,170,'Wasco'),(3128,170,'Marion'),(3129,170,'Clackamas'),(3130,170,'Washington'),(3131,170,'Multnomah'),(3132,170,'Hood River'),(3133,170,'Columbia'),(3134,170,'Sherman'),(3135,170,'Yamhill'),(3136,170,'Clatsop'),(3137,170,'Tillamook'),(3138,170,'Polk'),(3139,170,'Linn'),(3140,170,'Benton'),(3141,170,'Lincoln'),(3142,170,'Lane'),(3143,170,'Curry'),(3144,170,'Coos'),(3145,170,'Douglas'),(3146,170,'Klamath'),(3147,170,'Josephine'),(3148,170,'Jackson'),(3149,170,'Lake'),(3150,170,'Deschutes'),(3151,170,'Harney'),(3152,170,'Jefferson'),(3153,170,'Wheeler'),(3154,170,'Crook'),(3155,170,'Umatilla'),(3156,170,'Gilliam'),(3157,170,'Baker'),(3158,170,'Grant'),(3159,170,'Morrow'),(3160,170,'Union'),(3161,170,'Wallowa'),(3162,170,'Malheur'),(3163,183,'King'),(3164,183,'Snohomish'),(3165,183,'Kitsap'),(3166,183,'Whatcom'),(3167,183,'Skagit'),(3168,183,'San Juan'),(3169,183,'Island'),(3170,183,'Pierce'),(3171,183,'Clallam'),(3172,183,'Jefferson'),(3173,183,'Lewis'),(3174,183,'Thurston'),(3175,183,'Grays Harbor'),(3176,183,'Mason'),(3177,183,'Pacific'),(3178,183,'Cowlitz'),(3179,183,'Clark'),(3180,183,'Klickitat'),(3181,183,'Skamania'),(3182,183,'Wahkiakum'),(3183,183,'Chelan'),(3184,183,'Douglas'),(3185,183,'Okanogan'),(3186,183,'Grant'),(3187,183,'Yakima'),(3188,183,'Kittitas'),(3189,183,'Spokane'),(3190,183,'Lincoln'),(3191,183,'Stevens'),(3192,183,'Whitman'),(3193,183,'Adams'),(3194,183,'Ferry'),(3195,183,'Pend Oreille'),(3196,183,'Franklin'),(3197,183,'Benton'),(3198,183,'Walla Walla'),(3199,183,'Columbia'),(3200,183,'Garfield'),(3201,183,'Asotin'),(3202,128,'Anchorage'),(3203,128,'Bethel'),(3204,128,'Aleutians West'),(3205,128,'Lake And Peninsula'),(3206,128,'Kodiak Island'),(3207,128,'Aleutians East'),(3208,128,'Wade Hampton'),(3209,128,'Dillingham'),(3210,128,'Kenai Peninsula'),(3211,128,'Yukon Koyukuk'),(3212,128,'Valdez Cordova'),(3213,128,'Matanuska Susitna'),(3214,128,'Bristol Bay'),(3215,128,'Nome'),(3216,128,'Yakutat'),(3217,128,'Fairbanks North Star'),(3218,128,'Denali'),(3219,128,'North Slope'),(3220,128,'Northwest Arctic'),(3221,128,'Southeast Fairbanks'),(3222,128,'Juneau'),(3223,128,'Skagway Hoonah Angoon'),(3224,128,'Haines'),(3225,128,'Wrangell Petersburg'),(3226,128,'Sitka'),(3227,128,'Ketchikan Gateway'),(3228,128,'Prince Wales Ketchikan'),(3229,4,'Coconino County'),(3230,131,'Coconino County'),(3232,209,'Carleton'),(3233,4,'Yavapai County'),(3234,131,'Yavapai County'),(3236,4,'Maricopa County'),(3237,131,'Maricopa County'),(3239,33,'Pershing County'),(3240,160,'Pershing County'),(3242,51,'Collingsworth County'),(3243,178,'Collingsworth County'),(3245,4,'Mohave County'),(3246,131,'Mohave County'),(3248,33,'Humboldt County'),(3249,160,'Humboldt County'),(3251,33,'Lyon County'),(3252,160,'Lyon County'),(3254,33,'Washoe County'),(3255,160,'Washoe County'),(3257,33,'Nye County'),(3258,160,'Nye County'),(3260,33,'Eureka County'),(3261,160,'Eureka County'),(3263,33,'Lander County'),(3264,160,'Lander County'),(3266,33,'White Pine County'),(3267,160,'White Pine County'),(3269,33,'Douglas County'),(3270,160,'Douglas County'),(3272,33,'Clark County'),(3273,160,'Clark County'),(3275,4,'Cochise County'),(3276,131,'Cochise County'),(3278,4,'Navajo County'),(3279,131,'Navajo County'),(3281,50,'Travis'),(3282,177,'Travis'),(3284,33,'Churchill County'),(3285,160,'Churchill County'),(3287,33,'Storey County'),(3288,160,'Storey County'),(3290,43,'Owyhee'),(3291,170,'Owyhee'),(3293,6,'Mendocino County'),(3294,133,'Mendocino County'),(3296,6,'Inyo County'),(3297,133,'Inyo County'),(3299,6,'Mono County'),(3300,133,'Mono County'),(3302,4,'Pima County'),(3303,131,'Pima County'),(3305,424,'Matale'),(3306,52,'Garfield County'),(3307,179,'Garfield County'),(3309,447,'Starogard Gdaski'),(3310,449,'Zwickau'),(3311,4,'Pinal County'),(3312,131,'Pinal County'),(3314,4,'Graham County'),(3315,131,'Graham County'),(3317,52,'San Juan County'),(3318,179,'San Juan County'),(3320,197,'San Felipe de JesÃºs'),(3321,211,'San Felipe de JesÃºs'),(3323,197,'YÃ©cora'),(3324,211,'YÃ©cora'),(3326,197,'Cucurpe'),(3327,211,'Cucurpe'),(3329,197,'Ãlamos'),(3330,211,'Alamos'),(3331,231,'Aguascalientes'),(3332,231,'Asientos'),(3333,231,'Calvillo'),(3334,231,'CosÃ­o'),(3335,231,'JesÃºs MarÃ­a'),(3336,231,'PabellÃ³n de Arteaga'),(3337,231,'RincÃ³n de Romos'),(3338,231,'San JosÃ© de Gracia'),(3339,231,'TepezalÃ¡'),(3340,231,'El Llano'),(3341,231,'San Francisco de los Romo'),(3342,198,'Ensenada'),(3343,198,'Mexicali'),(3344,198,'Tecate'),(3345,198,'Tijuana'),(3346,198,'Playas de Rosarito'),(3347,253,'ComondÃº'),(3348,253,'MulegÃ©'),(3349,253,'La Paz'),(3350,253,'Los Cabos'),(3351,253,'Loreto'),(3352,263,'CalkinÃ­'),(3353,263,'Campeche'),(3354,263,'Carmen'),(3355,263,'ChampotÃ³n'),(3356,263,'HecelchakÃ¡n'),(3357,263,'HopelchÃ©n'),(3358,263,'Palizada'),(3359,263,'Tenabo'),(3360,263,'EscÃ¡rcega'),(3361,263,'Calakmul'),(3362,263,'Candelaria'),(3363,273,'Acacoyagua'),(3364,273,'Acala'),(3365,273,'Acapetahua'),(3366,273,'Altamirano'),(3367,273,'AmatÃ¡n'),(3368,273,'Amatenango de la Frontera'),(3369,273,'Amatenango del Valle'),(3370,273,'Angel Albino Corzo'),(3371,273,'Arriaga'),(3372,273,'Bejucal de Ocampo'),(3373,273,'Bella Vista'),(3374,273,'BerriozÃ¡bal'),(3375,273,'Bochil'),(3376,273,'El Bosque'),(3377,273,'CacahoatÃ¡n'),(3378,273,'CatazajÃ¡'),(3379,273,'Cintalapa'),(3380,273,'Coapilla'),(3381,273,'ComitÃ¡n de DomÃ­nguez'),(3382,273,'La Concordia'),(3383,273,'CopainalÃ¡'),(3384,273,'ChalchihuitÃ¡n'),(3385,273,'Chamula'),(3386,273,'Chanal'),(3387,273,'Chapultenango'),(3388,273,'ChenalhÃ³'),(3389,273,'Chiapa de Corzo'),(3390,273,'Chiapilla'),(3391,273,'ChicoasÃ©n'),(3392,273,'Chicomuselo'),(3393,273,'ChilÃ³n'),(3394,273,'Escuintla'),(3395,273,'Francisco LeÃ³n'),(3396,273,'Frontera Comalapa'),(3397,273,'Frontera Hidalgo'),(3398,273,'La Grandeza'),(3399,273,'HuehuetÃ¡n'),(3400,273,'HuixtÃ¡n'),(3401,273,'HuitiupÃ¡n'),(3402,273,'Huixtla'),(3403,273,'La Independencia'),(3404,273,'IxhuatÃ¡n'),(3405,273,'IxtacomitÃ¡n'),(3406,273,'Ixtapa'),(3407,273,'Ixtapangajoya'),(3408,273,'Jiquipilas'),(3409,273,'Jitotol'),(3410,273,'JuÃ¡rez'),(3411,273,'LarrÃ¡inzar'),(3412,273,'La Libertad'),(3413,273,'Mapastepec'),(3414,273,'Las Margaritas'),(3415,273,'Mazapa de Madero'),(3416,273,'MazatÃ¡n'),(3417,273,'Metapa'),(3418,273,'Mitontic'),(3419,273,'Motozintla'),(3420,273,'NicolÃ¡s RuÃ­z'),(3421,273,'Ocosingo'),(3422,273,'Ocotepec'),(3423,273,'Ocozocoautla de Espinosa'),(3424,273,'OstuacÃ¡n'),(3425,273,'Osumacinta'),(3426,273,'Oxchuc'),(3427,273,'Palenque'),(3428,273,'PantelhÃ³'),(3429,273,'Pantepec'),(3430,273,'Pichucalco'),(3431,273,'Pijijiapan'),(3432,273,'El Porvenir'),(3433,273,'Villa ComaltitlÃ¡n'),(3434,273,'Pueblo Nuevo SolistahuacÃ¡n'),(3435,273,'RayÃ³n'),(3436,273,'Reforma'),(3437,273,'Las Rosas'),(3438,273,'Sabanilla'),(3439,273,'Salto de Agua'),(3440,273,'San CristÃ³bal de las Casas'),(3441,273,'San Fernando'),(3442,273,'Siltepec'),(3443,273,'Simojovel'),(3444,273,'SitalÃ¡'),(3445,273,'Socoltenango'),(3446,273,'Solosuchiapa'),(3447,273,'SoyalÃ³'),(3448,273,'Suchiapa'),(3449,273,'Suchiate'),(3450,273,'Sunuapa'),(3451,273,'Tapachula'),(3452,273,'Tapalapa'),(3453,273,'Tapilula'),(3454,273,'TecpatÃ¡n'),(3455,273,'Tenejapa'),(3456,273,'Teopisca'),(3457,273,'Tila'),(3458,273,'TonalÃ¡'),(3459,273,'Totolapa'),(3460,273,'La Trinitaria'),(3461,273,'TumbalÃ¡'),(3462,273,'Tuxtla GutiÃ©rrez'),(3463,273,'Tuxtla Chico'),(3464,273,'TuzantÃ¡n'),(3465,273,'Tzimol'),(3466,273,'UniÃ³n JuÃ¡rez'),(3467,273,'Venustiano Carranza'),(3468,273,'Villa Corzo'),(3469,273,'Villaflores'),(3470,273,'YajalÃ³n'),(3471,273,'San Lucas'),(3472,273,'ZinacantÃ¡n'),(3473,273,'San Juan Cancuc'),(3474,273,'Aldama'),(3475,273,'BenemÃ©rito de las AmÃ©ricas'),(3476,273,'Maravilla Tenejapa'),(3477,273,'MarquÃ©s de Comillas'),(3478,273,'Montecristo de Guerrero'),(3479,273,'San AndrÃ©s Duraznal'),(3480,273,'Santiago el Pinar'),(3481,273,'Belisario DomÃ­nguez'),(3482,273,'Emiliano Zapata'),(3483,273,'El Parral'),(3484,273,'Mezcalapa'),(3485,206,'Ahumada'),(3486,206,'Aldama'),(3487,206,'Allende'),(3488,206,'Aquiles SerdÃ¡n'),(3489,206,'AscensiÃ³n'),(3490,206,'Bachiniva'),(3491,206,'Balleza'),(3492,206,'Batopilas'),(3493,206,'Bocoyna'),(3494,206,'Buenaventura'),(3495,206,'Camargo'),(3496,206,'Carichi'),(3497,206,'Casas Grandes'),(3498,206,'Coronado'),(3499,206,'Coyame del Sotol'),(3500,206,'La Cruz'),(3501,206,'CuauhtÃ©moc'),(3502,206,'Cusihuiriachi'),(3503,206,'Chihuahua'),(3504,206,'ChÃ­nipas'),(3505,206,'Delicias'),(3506,206,'Dr. Belisario DomÃ­nguez'),(3507,206,'Galeana'),(3508,206,'Santa Isabel'),(3509,206,'GÃ³mez FarÃ­as'),(3510,206,'Gran Morelos'),(3511,206,'Guachochi'),(3512,206,'Guadalupe'),(3513,206,'Guadalupe y Calvo'),(3514,206,'Guazapares'),(3515,206,'Guerrero'),(3516,206,'Hidalgo del Parral'),(3517,206,'HuejotitÃ¡n'),(3518,206,'Ignacio Zaragoza'),(3519,206,'Janos'),(3520,206,'JimÃ©nez'),(3521,206,'JuÃ¡rez'),(3522,206,'Julimes'),(3523,206,'LÃ³pez'),(3524,206,'Madera'),(3525,206,'Maguarichi'),(3526,206,'Manuel Benavides'),(3527,206,'Matachi'),(3528,206,'Matamoros'),(3529,206,'Meoqui'),(3530,206,'Morelos'),(3531,206,'Moris'),(3532,206,'Namiquipa'),(3533,206,'Nonoava'),(3534,206,'Nuevo Casas Grandes'),(3535,206,'Ocampo'),(3536,206,'Ojinaga'),(3537,206,'PrÃ¡xedis G. Guerrero'),(3538,206,'Riva Palacio'),(3539,206,'Rosales'),(3540,206,'Rosario'),(3541,206,'San Francisco de Borja'),(3542,206,'San Francisco de Conchos'),(3543,206,'San Francisco del Oro'),(3544,206,'Santa BÃ¡rbara'),(3545,206,'Satevo'),(3546,206,'Saucillo'),(3547,206,'TemÃ³sachi'),(3548,206,'El Tule'),(3549,206,'Urique'),(3550,206,'Uruachi'),(3551,206,'Valle de Zaragoza'),(3552,205,'Abasolo'),(3553,205,'AcuÃ±a'),(3554,205,'Allende'),(3555,205,'Arteaga'),(3556,205,'Candela'),(3557,205,'CastaÃ±os'),(3558,205,'CuatrociÃ©negas'),(3559,205,'Escobedo'),(3560,205,'Francisco I. Madero'),(3561,205,'Frontera'),(3562,205,'General Cepeda'),(3563,205,'Guerrero'),(3564,205,'Hidalgo'),(3565,205,'JimÃ©nez'),(3566,205,'JuÃ¡rez'),(3567,205,'Lamadrid'),(3568,205,'Matamoros'),(3569,205,'Monclova'),(3570,205,'Morelos'),(3571,205,'MÃºzquiz'),(3572,205,'Nadadores'),(3573,205,'Nava'),(3574,205,'Ocampo'),(3575,205,'Parras'),(3576,205,'Piedras Negras'),(3577,205,'Progreso'),(3578,205,'Ramos Arizpe'),(3579,205,'Sabinas'),(3580,205,'Sacramento'),(3581,205,'Saltillo'),(3582,205,'San Buenaventura'),(3583,205,'San Juan de Sabinas'),(3584,205,'San Pedro de las Colonias'),(3585,205,'Sierra Mojada'),(3586,205,'TorreÃ³n'),(3587,205,'Viesca'),(3588,205,'Villa UniÃ³n'),(3589,205,'Zaragoza'),(3590,278,'ArmerÃ­a'),(3591,278,'Colima'),(3592,278,'Comala'),(3593,278,'CoquimatlÃ¡n'),(3594,278,'CuauhtÃ©moc'),(3595,278,'IxtlahuacÃ¡n'),(3596,278,'Manzanillo'),(3597,278,'MinatitlÃ¡n'),(3598,278,'TecomÃ¡n'),(3599,278,'Villa de Ãlvarez'),(3600,290,'CanatlÃ¡n'),(3601,290,'Canelas'),(3602,290,'Coneto de Comonfort'),(3603,290,'CuencamÃ©'),(3604,290,'Durango'),(3605,290,'General SimÃ³n BolÃ­var'),(3606,290,'GÃ³mez Palacio'),(3607,290,'Guadalupe Victoria'),(3608,290,'GuanacevÃ­'),(3609,290,'Hidalgo'),(3610,290,'IndÃ©'),(3611,290,'Lerdo'),(3612,290,'MapimÃ­'),(3613,290,'Mezquital'),(3614,290,'Nazas'),(3615,290,'Nombre de Dios'),(3616,290,'Ocampo'),(3617,290,'El Oro'),(3618,290,'OtÃ¡ez'),(3619,290,'PÃ¡nuco de Coronado'),(3620,290,'PeÃ±Ã³n Blanco'),(3621,290,'Poanas'),(3622,290,'Pueblo Nuevo'),(3623,290,'Rodeo'),(3624,290,'San Bernardo'),(3625,290,'San Dimas'),(3626,290,'San Juan de Guadalupe'),(3627,290,'San Juan del RÃ­o'),(3628,290,'San Luis del Cordero'),(3629,290,'San Pedro del Gallo'),(3630,290,'Santa Clara'),(3631,290,'Santiago Papasquiaro'),(3632,290,'SÃºchil'),(3633,290,'Tamazula'),(3634,290,'Tepehuanes'),(3635,290,'Tlahualilo'),(3636,290,'Topia'),(3637,290,'Vicente Guerrero'),(3638,290,'Nuevo Ideal'),(3639,297,'Abasolo'),(3640,297,'AcÃ¡mbaro'),(3641,297,'Allende'),(3642,297,'Apaseo el Alto'),(3643,297,'Apaseo el Grande'),(3644,297,'Atarjea'),(3645,297,'Celaya'),(3646,297,'Manuel Doblado'),(3647,297,'Comonfort'),(3648,297,'Coroneo'),(3649,297,'Cortazar'),(3650,297,'CuerÃ¡maro'),(3651,297,'Doctor Mora'),(3652,297,'Dolores Hidalgo'),(3653,297,'Guanajuato'),(3654,297,'HuanÃ­maro'),(3655,297,'Irapuato'),(3656,297,'Jaral del Progreso'),(3657,297,'JerÃ©cuaro'),(3658,297,'LeÃ³n'),(3659,297,'MoroleÃ³n'),(3660,297,'Ocampo'),(3661,297,'PÃ©njamo'),(3662,297,'Pueblo Nuevo'),(3663,297,'PurÃ­sima del RincÃ³n'),(3664,297,'Romita'),(3665,297,'Salamanca'),(3666,297,'Salvatierra'),(3667,297,'San Diego de la UniÃ³n'),(3668,297,'San Felipe'),(3669,297,'San Francisco del RincÃ³n'),(3670,297,'San JosÃ© Iturbide'),(3671,297,'San Luis de la Paz'),(3672,297,'Santa Catarina'),(3673,297,'Santa Cruz de Juventino Rosas'),(3674,297,'Santiago MaravatÃ­o'),(3675,297,'Silao'),(3676,297,'Tarandacuao'),(3677,297,'Tarimoro'),(3678,297,'Tierra Blanca'),(3679,297,'Uriangato'),(3680,297,'Valle de Santiago'),(3681,297,'Victoria'),(3682,297,'VillagrÃ¡n'),(3683,297,'XichÃº'),(3684,297,'Yuriria'),(3685,300,'Acapulco de JuÃ¡rez'),(3686,300,'Acatepec'),(3687,300,'AjuchitlÃ¡n del Progreso'),(3688,300,'Ahuacuotzingo'),(3689,300,'Alcozauca de Guerrero'),(3690,300,'Alpoyeca'),(3691,300,'Apaxtla'),(3692,300,'Arcelia'),(3693,300,'Atenango del RÃ­o'),(3694,300,'Atlamajalcingo del Monte'),(3695,300,'Atlixtac'),(3696,300,'Atoyac de Ãlvarez'),(3697,300,'Ayutla de los Libres'),(3698,300,'Azoyu'),(3699,300,'Benito JuÃ¡rez'),(3700,300,'Buenavista de CuÃ©llar'),(3701,300,'Chilapa de Ãlvarez'),(3702,300,'Chilpancingo de los Bravo'),(3703,300,'Coahuayutla de JosÃ© MarÃ­a Izazaga'),(3704,300,'Cocula'),(3705,300,'Copala'),(3706,300,'Copalillo'),(3707,300,'Copanatoyac'),(3708,300,'Coyuca de BenÃ­tez'),(3709,300,'Coyuca de CatalÃ¡n'),(3710,300,'Cuajinicuilapa'),(3711,300,'Cualac'),(3712,300,'Cuautepec'),(3713,300,'Cuetzala del Progreso'),(3714,300,'Cutzamala de PinzÃ³n'),(3715,300,'Eduardo Neri'),(3716,300,'Florencio Villarreal'),(3717,300,'General Canuto A. Neri'),(3718,300,'General Heliodoro Castillo'),(3719,300,'HuamuxtitlÃ¡n'),(3720,300,'Huitzuco de los Figueroa'),(3721,300,'Iguala de la Independencia'),(3722,300,'Igualapa'),(3723,300,'Ixcateopan de CuauhtÃ©moc'),(3724,300,'Zihuatanejo de Azueta'),(3725,300,'Juan R. Escudero'),(3726,300,'La UniÃ³n de Isidoro Montes de Oca'),(3727,300,'Leonardo Bravo'),(3728,300,'Malinaltepec'),(3729,300,'MÃ¡rtir de CuilapÃ¡n'),(3730,300,'Metlatonoc'),(3731,300,'MochitlÃ¡n'),(3732,300,'OlinalÃ¡'),(3733,300,'Ometepec'),(3734,300,'Pedro Ascencio Alquisiras'),(3735,300,'PetatlÃ¡n'),(3736,300,'Pilcaya'),(3737,300,'Pungarabato'),(3738,300,'Quechultenango'),(3739,300,'San Luis AcatlÃ¡n'),(3740,300,'San Marcos'),(3741,300,'San Miguel Totolapan'),(3742,300,'Taxco de AlarcÃ³n'),(3743,300,'Tecoanapa'),(3744,300,'TecpÃ¡n de Galeana'),(3745,300,'Teloloapan'),(3746,300,'Tepecoacuilco de Trujano'),(3747,300,'Tetipac'),(3748,300,'Tixtla de Guerrero'),(3749,300,'Tlacoachistlahuaca'),(3750,300,'Tlacoapa'),(3751,300,'Tlalchapa'),(3752,300,'Tlalixtaquilla de Maldonado'),(3753,300,'Tlapa de Comonfort'),(3754,300,'Tlapehuala'),(3755,300,'Xalpatlahuac'),(3756,300,'Xochihuehuetlan'),(3757,300,'Xochistlahuaca'),(3758,300,'ZapotitlÃ¡n Tablas'),(3759,300,'ZirÃ¡ndaro'),(3760,300,'Zitlala'),(3761,300,'Marquelia'),(3762,300,'Cochoapa el Grande'),(3763,300,'JosÃ© JoaquÃ­n de Herrera'),(3764,300,'JuchitÃ¡n'),(3765,300,'Iliatenco'),(3766,215,'AcatlÃ¡n'),(3767,215,'AcaxochitlÃ¡n'),(3768,215,'Actopan'),(3769,215,'Agua Blanca de Iturbide'),(3770,215,'Ajacuba'),(3771,215,'Alfajayucan'),(3772,215,'Almoloya'),(3773,215,'Apan'),(3774,215,'El Arenal'),(3775,215,'AtitalaquÃ­a'),(3776,215,'Atlapexco'),(3777,215,'Atotonilco de Tula'),(3778,215,'Atotonilco El Grande'),(3779,215,'Calnali'),(3780,215,'Cardonal'),(3781,215,'Chapantongo'),(3782,215,'ChapulhuacÃ¡n'),(3783,215,'Chilcuautla'),(3784,215,'Cuautepec de Hinojosa'),(3785,215,'EloxochitlÃ¡n'),(3786,215,'Emiliano Zapata'),(3787,215,'Epazoyucan'),(3788,215,'Francisco I. Madero'),(3789,215,'Huasca de Ocampo'),(3790,215,'Huautla'),(3791,215,'Huazalingo'),(3792,215,'Huehuetla'),(3793,215,'Huejutla de Reyes'),(3794,215,'Huichapan'),(3795,215,'Ixmiquilpan'),(3796,215,'Jacala de Ledezma'),(3797,215,'Jaltocan'),(3798,215,'JuÃ¡rez'),(3799,215,'Lolotla'),(3800,215,'Metepec'),(3801,215,'MetztitlÃ¡n'),(3802,215,'Mineral de la Reforma'),(3803,215,'Mineral del Chico'),(3804,215,'Mineral del Monte'),(3805,215,'La MisiÃ³n'),(3806,215,'Mixquiahuala de JuÃ¡rez'),(3807,215,'Molango de Escamilla'),(3808,215,'NicolÃ¡s Flores'),(3809,215,'Nopala de VillagrÃ¡n'),(3810,215,'OmitlÃ¡n de JuÃ¡rez'),(3811,215,'Pisaflores'),(3812,215,'Pacula'),(3813,215,'Pachuca de Soto'),(3814,215,'Progreso de ObregÃ³n'),(3815,215,'San AgustÃ­n MetzquititlÃ¡n'),(3816,215,'San AgustÃ­n Tlaxiaca'),(3817,215,'San Bartolo Tutotepec'),(3818,215,'San Felipe OrizatlÃ¡n'),(3819,215,'San Salvador'),(3820,215,'Santiago de Anaya'),(3821,215,'Singuilucan'),(3822,215,'Tasquillo'),(3823,215,'Tecozautla'),(3824,215,'Tenango de Doria'),(3825,215,'Tepeapulco'),(3826,215,'TepehuacÃ¡n de Guerrero'),(3827,215,'Tepeji del Rio de Ocampo'),(3828,215,'TepetitlÃ¡n'),(3829,215,'Tetepango'),(3830,215,'Tezontepec de Aldama'),(3831,215,'Tianguistengo'),(3832,215,'Tizayuca'),(3833,215,'Tlahuelilpan'),(3834,215,'Tlahuiltepa'),(3835,215,'Tlanalapa'),(3836,215,'Tlanchinol'),(3837,215,'Tlaxcoapan'),(3838,215,'Tolcayuca'),(3839,215,'Tula de Allende'),(3840,215,'Tulancingo de Bravo'),(3841,215,'Tulantepec de Lugo Guerrero'),(3842,215,'Villa de Tezontepec'),(3843,215,'Xochiatipan'),(3844,215,'XochicoatlÃ¡n'),(3845,215,'Yahualica'),(3846,215,'Zacualtipan de Ãngeles'),(3847,215,'ZapotlÃ¡n de JuÃ¡rez'),(3848,215,'Zempoala'),(3849,215,'Zimapan'),(3850,306,'Acatic'),(3851,306,'AcatlÃ¡n de JuÃ¡rez'),(3852,306,'Ahualulco de Mercado'),(3853,306,'Amacueca'),(3854,306,'AmatitÃ¡n'),(3855,306,'Ameca'),(3856,306,'Arandas'),(3857,306,'Atemajac de Brizuela'),(3858,306,'Atengo'),(3859,306,'Atenguillo'),(3860,306,'Atotonilco El Alto'),(3861,306,'Atoyac'),(3862,306,'AutlÃ¡n de Navarro'),(3863,306,'AyotlÃ¡n'),(3864,306,'Ayutla'),(3865,306,'BolaÃ±os'),(3866,306,'Cabo Corrientes'),(3867,306,'CaÃ±adas de ObregÃ³n'),(3868,306,'Casimiro Castillo'),(3869,306,'Chapala'),(3870,306,'ChimaltitÃ¡n'),(3871,306,'ChiquilistlÃ¡n'),(3872,306,'CihuatlÃ¡n'),(3873,306,'Cocula'),(3874,306,'ColotlÃ¡n'),(3875,306,'ConcepciÃ³n de Buenos Aires'),(3876,306,'CuautitlÃ¡n de GarcÃ­a BarragÃ¡n'),(3877,306,'Cuautla'),(3878,306,'CuquÃ­o'),(3879,306,'Degollado'),(3880,306,'Ejutla'),(3881,306,'El Arenal'),(3882,306,'El Grullo'),(3883,306,'El LimÃ³n'),(3884,306,'El Salto'),(3885,306,'EncarnaciÃ³n de DÃ­az'),(3886,306,'EtzatlÃ¡n'),(3887,306,'GÃ³mez FarÃ­as'),(3888,306,'Guachinango'),(3889,306,'Guadalajara'),(3890,306,'Hostotipaquillo'),(3891,306,'HuejÃºcar'),(3892,306,'Huejuquilla El Alto'),(3893,306,'IxtlahuacÃ¡n de los Membrillos'),(3894,306,'Ixtlahuacan del RÃ­o'),(3895,306,'JalostotitlÃ¡n'),(3896,306,'Jamay'),(3897,306,'JesÃºs MarÃ­a'),(3898,306,'JilotlÃ¡n de los Dolores'),(3899,306,'Jocotepec'),(3900,306,'JuanacatlÃ¡n'),(3901,306,'JuchitlÃ¡n'),(3902,306,'La Barca'),(3903,306,'La Huerta'),(3904,306,'La Manzanilla de La Paz'),(3905,306,'Lagos de Moreno'),(3906,306,'Magdalena'),(3907,306,'Mascota'),(3908,306,'Mazamitla'),(3909,306,'Mexticacan'),(3910,306,'Mezquitic'),(3911,306,'MixtlÃ¡n'),(3912,306,'OcotlÃ¡n'),(3913,306,'Ojuelos de Jalisco'),(3914,306,'PÃ­huamo'),(3915,306,'PoncitlÃ¡n'),(3916,306,'Puerto Vallarta'),(3917,306,'Quitupan'),(3918,306,'San Cristobal de la Barranca'),(3919,306,'San Diego de AlejandrÃ­a'),(3920,306,'San Gabriel'),(3921,306,'San Juan de los Lagos'),(3922,306,'San Juanito de Escobedo'),(3923,306,'San JuliÃ¡n'),(3924,306,'San Marcos'),(3925,306,'San MartÃ­n de BolaÃ±os'),(3926,306,'San MartÃ­n de Hidalgo'),(3927,306,'San Miguel El Alto'),(3928,306,'San SebastiÃ¡n del Oeste'),(3929,306,'Santa MarÃ­a del Oro'),(3930,306,'Santa MarÃ­a de los Angeles'),(3931,306,'Sayula'),(3932,306,'Tala'),(3933,306,'Talpa de Allende'),(3934,306,'Tamazula de Gordiano'),(3935,306,'Tapalpa'),(3936,306,'TecalitlÃ¡n'),(3937,306,'Techaluta de Montenegro'),(3938,306,'TecolotlÃ¡n'),(3939,306,'TenamaxtlÃ¡n'),(3940,306,'Teocaltiche'),(3941,306,'TeocuitatlÃ¡n de Corona'),(3942,306,'TepatitlÃ¡n de Morelos'),(3943,306,'Tequila'),(3944,306,'TeuchitlÃ¡n'),(3945,306,'Tizapan El Alto'),(3946,306,'Tlajomulco de ZuÃ±iga'),(3947,306,'Tlaquepaque'),(3948,306,'TolimÃ¡n'),(3949,306,'TomatlÃ¡n'),(3950,306,'TonalÃ¡'),(3951,306,'Tonaya'),(3952,306,'Tonila'),(3953,306,'Totatiche'),(3954,306,'TototlÃ¡n'),(3955,306,'Tuxcacuesco'),(3956,306,'Tuxcueca'),(3957,306,'Tuxpan'),(3958,306,'UniÃ³n de San Antonio'),(3959,306,'UniÃ³n de Tula'),(3960,306,'Valle de Guadalupe'),(3961,306,'Valle de JuÃ¡rez'),(3962,306,'Villa Corona'),(3963,306,'Villa Guerrero'),(3964,306,'Villa Hidalgo'),(3965,306,'Villa PurificaciÃ³n'),(3966,306,'Yahualica de GonzÃ¡lez Gallo'),(3967,306,'Zacoalco de Torres'),(3968,306,'Zapopan'),(3969,306,'Zapotiltic'),(3970,306,'ZapotitlÃ¡n de Vadillo'),(3971,306,'ZapotlÃ¡n del Rey'),(3972,306,'ZapotlÃ¡n el Grande'),(3973,306,'Zapotlanejo'),(3974,306,'San Ignacio Cerro Gordo'),(3975,335,'Amacuzac'),(3976,335,'Atlatlahucan'),(3977,335,'Axochiapan'),(3978,335,'Ciudad Ayala'),(3979,335,'CoatlÃ¡n del RÃ­o'),(3980,335,'Cuautla'),(3981,335,'Cuernavaca'),(3982,335,'Emiliano Zapata'),(3983,335,'Huitzilac'),(3984,335,'Jantetelco'),(3985,335,'Jiutepec'),(3986,335,'Jojutla'),(3987,335,'Jonacatepec'),(3988,335,'Mazatepec'),(3989,335,'Miacatlan'),(3990,335,'Ocuituco'),(3991,335,'Puente de Ixtla'),(3992,335,'Temixco'),(3993,335,'Temoac'),(3994,335,'Tepalcingo'),(3995,335,'TepoztlÃ¡n'),(3996,335,'Tetecala'),(3997,335,'Tetela del VolcÃ¡n'),(3998,335,'Tlalnepantla'),(3999,335,'TlaltizapÃ¡n'),(4000,335,'Tlaquiltenango'),(4001,335,'Tlayacapan'),(4002,335,'Totolapan'),(4003,335,'Xochitepec'),(4004,335,'Yautepec'),(4005,335,'Yecapixtla'),(4006,335,'Zacatepec de Hidalgo'),(4007,335,'Zacualpan de Amilpas'),(4008,337,'Acaponeta'),(4009,337,'AhuacatlÃ¡n'),(4010,337,'AmatlÃ¡n de CaÃ±as'),(4011,337,'BahÃ­a de Banderas'),(4012,337,'Compostela'),(4013,337,'El Nayar'),(4014,337,'Huajicori'),(4015,337,'IxtlÃ¡n del RÃ­o'),(4016,337,'Jala'),(4017,337,'La Yesca'),(4018,337,'Rosamorada'),(4019,337,'Ruiz'),(4020,337,'San Blas'),(4021,337,'San Pedro Lagunillas'),(4022,337,'Santa MarÃ­a del Oro'),(4023,337,'Santiago Ixcuintla'),(4024,337,'Tecuala'),(4025,337,'Tepic'),(4026,337,'Tuxpan'),(4027,337,'Xalisco'),(4028,340,'Abejones'),(4029,340,'AcatlÃ¡n de PÃ©rez Figueroa'),(4030,340,'Animas Trujano, Oaxaca'),(4031,340,'AsunciÃ³n Cacalotepec'),(4032,340,'AsunciÃ³n Cuyotepeji'),(4033,340,'AsunciÃ³n Ixtaltepec'),(4034,340,'AsunciÃ³n NochixtlÃ¡n'),(4035,340,'AsunciÃ³n OcotlÃ¡n'),(4036,340,'AsunciÃ³n Tlacolulita'),(4037,340,'Ayoquezco de Aldama');
INSERT INTO `lkupcounty`(countyId, stateid, countyName) VALUES (4038,340,'Ayotzintepec'),(4039,340,'CalihualÃ¡'),(4040,340,'Candelaria Loxicha'),(4041,340,'Capulalpam de MÃ©ndez'),(4042,340,'Chahuites'),(4043,340,'Chalcatongo de Hidalgo'),(4044,340,'Chilapa de Diaz'),(4045,340,'ChiquihuitlÃ¡n de Benito JuÃ¡rez'),(4046,340,'CiÃ©nega de ZimatlÃ¡n'),(4047,340,'Ciudad Ixtepec'),(4048,340,'Coatecas Altas'),(4049,340,'CoicoyÃ¡n de las Flores'),(4050,340,'ConcepciÃ³n Buenavista'),(4051,340,'ConcepciÃ³n PÃ¡palo'),(4052,340,'Constancia del Rosario'),(4053,340,'Cosolapa'),(4054,340,'Cosoltepec'),(4055,340,'Cuilapan de Guerrero'),(4056,340,'Ejutla de Crespo'),(4057,340,'EloxochitlÃ¡n de Flores MagÃ³n'),(4058,340,'El Barrio de La Soledad'),(4059,340,'El Espinal'),(4060,340,'Evangelista Analco'),(4061,340,'Fresnillo de Trujano'),(4062,340,'Guadalupe de RamÃ­rez'),(4063,340,'Guadalupe Etla'),(4064,340,'Guelatao de JuÃ¡rez'),(4065,340,'Guevea de Humboldt'),(4066,340,'Huajuapan de LeÃ³n'),(4067,340,'Huautepec'),(4068,340,'Huautla de JimÃ©nez'),(4069,340,'Ixpantepec Nieves'),(4070,340,'IxtlÃ¡n de JuÃ¡rez'),(4071,340,'JuchitÃ¡n de Zaragoza'),(4072,340,'La CompaÃ±ia'),(4073,340,'La Pe'),(4074,340,'La Reforma'),(4075,340,'La Trinidad Vista Hermosa'),(4076,340,'Loma Bonita'),(4077,340,'Magdalena Apasco'),(4078,340,'Magdalena Jaltepec'),(4079,340,'Magdalena Mixtepec'),(4080,340,'Magdalena OcotlÃ¡n'),(4081,340,'Magdalena PeÃ±asco'),(4082,340,'Magdalena Teitipac'),(4083,340,'Magdalena TequisistlÃ¡n'),(4084,340,'Magdalena Tlacotepec'),(4085,340,'Magdalena ZahuatlÃ¡n'),(4086,340,'Mariscala de JuÃ¡rez'),(4087,340,'MÃ¡rtires de Tacubaya'),(4088,340,'MatÃ­as Romero'),(4089,340,'MazatlÃ¡n Villa de Flores'),(4090,340,'Mesones Hidalgo'),(4091,340,'MiahuatlÃ¡n de Porfirio DÃ­az'),(4092,340,'MixistlÃ¡n de la Reforma'),(4093,340,'Monjas'),(4094,340,'Natividad'),(4095,340,'Nazareno Etla'),(4096,340,'Nejapa de Madero'),(4097,340,'Nuevo Zoquiapam'),(4098,340,'Oaxaca de JuÃ¡rez'),(4099,340,'OcotlÃ¡n de Morelos'),(4100,340,'Pinotepa de Don Luis'),(4101,340,'Pinotepa Nacional'),(4102,340,'Pluma Hidalgo'),(4103,340,'Putla Villa de Guerrero'),(4104,340,'Reforma de Pineda'),(4105,340,'Reyes Etla'),(4106,340,'Rojas de CuauhtÃ©moc'),(4107,340,'Salina Cruz'),(4108,340,'San AgustÃ­n Amatengo'),(4109,340,'San AgustÃ­n Atenango'),(4110,340,'San AgustÃ­n Chayuco'),(4111,340,'San AgustÃ­n de las Juntas'),(4112,340,'San AgustÃ­n Etla'),(4113,340,'San AgustÃ­n Loxicha'),(4114,340,'San AgustÃ­n Tlacotepec'),(4115,340,'San AgustÃ­n Yatareni'),(4116,340,'San AndrÃ©s Cabecera Nueva'),(4117,340,'San AndrÃ©s Dinicuiti'),(4118,340,'San AndrÃ©s Huaxpaltepec'),(4119,340,'San AndrÃ©s Huayapam'),(4120,340,'San AndrÃ©s Ixtlahuaca'),(4121,340,'San AndrÃ©s Lagunas'),(4122,340,'San AndrÃ©s NuxiÃ±o'),(4123,340,'San AndrÃ©s PaxtlÃ¡n'),(4124,340,'San AndrÃ©s Sinaxtla'),(4125,340,'San AndrÃ©s Solaga'),(4126,340,'San AndrÃ©s Teotilalpam'),(4127,340,'San AndrÃ©s Tepetlapa'),(4128,340,'San AndrÃ©s YaÃ¡'),(4129,340,'San AndrÃ©s Zabache'),(4130,340,'San AndrÃ©s Zautla'),(4131,340,'San Antonino Castillo Velasco'),(4132,340,'San Antonino El Alto'),(4133,340,'San Antonino Monte Verde'),(4134,340,'San Antonio Acutla'),(4135,340,'San Antonio de la Cal'),(4136,340,'San Antonio Huitepec'),(4137,340,'San Antonio Nanahuatipam'),(4138,340,'San Antonio Sinicahua'),(4139,340,'San Antonio Tepetlapa'),(4140,340,'San Baltazar Chichicapam'),(4141,340,'San Baltazar Loxicha'),(4142,340,'San Baltazar Yatzachi el Bajo'),(4143,340,'San Bartolo Coyotepec'),(4144,340,'San BartolomÃ© Ayautla'),(4145,340,'San BartolomÃ© Loxicha'),(4146,340,'San BartolomÃ© Quialana'),(4147,340,'San BartolomÃ© YucuaÃ±e'),(4148,340,'San BartolomÃ© Zoogocho'),(4149,340,'San Bartolo Soyaltepec'),(4150,340,'San Bartolo Yautepec'),(4151,340,'San Bernardo Mixtepec'),(4152,340,'San Blas Atempa'),(4153,340,'San Carlos Yautepec'),(4154,340,'San CristÃ³bal AmatlÃ¡n'),(4155,340,'San CristÃ³bal Amoltepec'),(4156,340,'San CristÃ³bal Lachirioag'),(4157,340,'San CristÃ³bal Suchixtlahuaca'),(4158,340,'San Dionisio del Mar'),(4159,340,'San Dionisio Ocotepec'),(4160,340,'San Dionisio OcotlÃ¡n'),(4161,340,'San Esteban Atatlahuca'),(4162,340,'San Felipe Jalapa de DÃ­az'),(4163,340,'San Felipe Tejalapam'),(4164,340,'San Felipe Usila'),(4165,340,'San Francisco CahuacÃºa'),(4166,340,'San Francisco Cajonos'),(4167,340,'San Francisco Chapulapa'),(4168,340,'San Francisco ChindÃºa'),(4169,340,'San Francisco del Mar'),(4170,340,'San Francisco HuehuetlÃ¡n'),(4171,340,'San Francisco IxhuatÃ¡n'),(4172,340,'San Francisco Jaltepetongo'),(4173,340,'San Francisco LachigolÃ³'),(4174,340,'San Francisco Logueche'),(4175,340,'San Francisco NuxaÃ±o'),(4176,340,'San Francisco Ozolotepec'),(4177,340,'San Francisco SolÃ¡'),(4178,340,'San Francisco Telixtlahuaca'),(4179,340,'San Francisco Teopan'),(4180,340,'San Francisco Tlapancingo'),(4181,340,'San Gabriel Mixtepec'),(4182,340,'San Ildefonso AmatlÃ¡n'),(4183,340,'San Ildefonso SolÃ¡'),(4184,340,'San Ildefonso Villa Alta'),(4185,340,'San Jacinto Amilpas'),(4186,340,'San Jacinto Tlacotepec'),(4187,340,'San JerÃ³nimo CoatlÃ¡n'),(4188,340,'San JerÃ³nimo Silacayoapilla'),(4189,340,'San JerÃ³nimo Sosola'),(4190,340,'San JerÃ³nimo Taviche'),(4191,340,'San JerÃ³nimo Tecoatl'),(4192,340,'San JerÃ³nimo Tlacochahuaya'),(4193,340,'San Jorge Nuchita'),(4194,340,'San JosÃ© Ayuquila'),(4195,340,'San JosÃ© Chinantequilla (Oaxaca)'),(4196,340,'San JosÃ© Chiltepec'),(4197,340,'San JosÃ© del PeÃ±asco'),(4198,340,'San JosÃ© del Progreso'),(4199,340,'San JosÃ© Estancia Grande'),(4200,340,'San JosÃ© Independencia'),(4201,340,'San JosÃ© Lachiguiri'),(4202,340,'San JosÃ© Tenango'),(4203,340,'San Juan Achiutla'),(4204,340,'San Juan Atepec'),(4205,340,'San Juan Bautista Atatlahuca'),(4206,340,'San Juan Bautista Coixtlahuaca'),(4207,340,'San Juan Bautista Cuicatlan'),(4208,340,'San Juan Bautista Guelache'),(4209,340,'San Juan Bautista JayacatlÃ¡n'),(4210,340,'San Juan Bautista lo de Soto'),(4211,340,'San Juan Bautista Suchitepec'),(4212,340,'San Juan Bautista Tlachichilco'),(4213,340,'San Juan Bautista Tlacoatzintepec'),(4214,340,'San Juan Bautista Tuxtepec'),(4215,340,'San Juan Bautista Valle Nacional'),(4216,340,'San Juan Cacahuatepec'),(4217,340,'San Juan ChicomezÃºchil'),(4218,340,'San Juan Chilateca'),(4219,340,'San Juan Cieneguilla'),(4220,340,'San Juan Coatzospam'),(4221,340,'San Juan Colorado'),(4222,340,'San Juan Comaltepec'),(4223,340,'San Juan CotzocÃ³n'),(4224,340,'San Juan del Estado'),(4225,340,'San Juan de los Cues'),(4226,340,'San Juan del RÃ­o'),(4227,340,'San Juan Diuxi'),(4228,340,'San Juan GuelavÃ­a'),(4229,340,'San Juan Guichicovi'),(4230,340,'San Juan Ihualtepec'),(4231,340,'San Juan Juquila Mixes'),(4232,340,'San Juan Juquila Vijanos'),(4233,340,'San Juan Lachao'),(4234,340,'San Juan Lachigalla'),(4235,340,'San Juan Lajarcia'),(4236,340,'San Juan Lalana'),(4237,340,'San Juan MazatlÃ¡n'),(4238,340,'San Juan Mixtepec, Mixteca'),(4239,340,'San Juan Mixtepec, MiahuatlÃ¡n'),(4240,340,'San Juan Ã‘umÃ­'),(4241,340,'San Juan Ozolotepec'),(4242,340,'San Juan Petlapa'),(4243,340,'San Juan Quiahije'),(4244,340,'San Juan Quiotepec'),(4245,340,'San Juan Sayultepec'),(4246,340,'San Juan TabaÃ¡'),(4247,340,'San Juan Tamazola'),(4248,340,'San Juan Teita'),(4249,340,'San Juan Teitipac'),(4250,340,'San Juan Tepeuxila'),(4251,340,'San Juan Teposcolula'),(4252,340,'San Juan YaeÃ©'),(4253,340,'San Juan Yatzona'),(4254,340,'San Juan Yucuita'),(4255,340,'San Lorenzo'),(4256,340,'San Lorenzo Albarradas'),(4257,340,'San Lorenzo Cacaotepec'),(4258,340,'San Lorenzo Cuaunecuiltitla'),(4259,340,'San Lorenzo Texmelucan'),(4260,340,'San Lorenzo Victoria'),(4261,340,'San Lucas CamotlÃ¡n'),(4262,340,'San Lucas OjitlÃ¡n'),(4263,340,'San Lucas QuiavinÃ­'),(4264,340,'San Lucas Zoquiapam'),(4265,340,'San Luis AmatlÃ¡n'),(4266,340,'San Marcial Ozolotepec'),(4267,340,'San Marcos Arteaga'),(4268,340,'San MartÃ­n de los Cansecos'),(4269,340,'San MartÃ­n Huamelulpam'),(4270,340,'San MartÃ­n Itunyoso'),(4271,340,'San MartÃ­n LachilÃ¡'),(4272,340,'San MartÃ­n Peras'),(4273,340,'San MartÃ­n Tilcajete'),(4274,340,'San MartÃ­n Toxpalan'),(4275,340,'San MartÃ­n Zacatepec'),(4276,340,'San Mateo Cajonos'),(4277,340,'San Mateo del Mar'),(4278,340,'San Mateo Etlatongo'),(4279,340,'San Mateo Nejapam'),(4280,340,'San Mateo PeÃ±asco'),(4281,340,'San Mateo PiÃ±as'),(4282,340,'San Mateo RÃ­o Hondo'),(4283,340,'San Mateo Sindihui'),(4284,340,'San Mateo Tlapiltepec'),(4285,340,'San Mateo YoloxochitlÃ¡n'),(4286,340,'San Melchor Betaza'),(4287,340,'San Miguel Achiutla'),(4288,340,'San Miguel AhuehuetitlÃ¡n'),(4289,340,'San Miguel AloÃ¡pam'),(4290,340,'San Miguel AmatitlÃ¡n'),(4291,340,'San Miguel AmatlÃ¡n'),(4292,340,'San Miguel Chicahua'),(4293,340,'San Miguel Chimalapa'),(4294,340,'San Miguel CoatlÃ¡n'),(4295,340,'San Miguel del Puerto'),(4296,340,'San Miguel del RÃ­o'),(4297,340,'San Miguel Ejutla'),(4298,340,'San Miguel El Grande'),(4299,340,'San Miguel Huautla'),(4300,340,'San Miguel Mixtepec'),(4301,340,'San Miguel Panixtlahuaca'),(4302,340,'San Miguel Peras'),(4303,340,'San Miguel Piedras'),(4304,340,'San Miguel Quetzaltepec'),(4305,340,'San Miguel Santa Flor'),(4306,340,'San Miguel Soyaltepec'),(4307,340,'San Miguel Suchixtepec'),(4308,340,'San Miguel TecomatlÃ¡n'),(4309,340,'San Miguel Tenango'),(4310,340,'San Miguel Tequixtepec'),(4311,340,'San Miguel Tilquiapam'),(4312,340,'San Miguel Tlacamama'),(4313,340,'San Miguel Tlacotepec'),(4314,340,'San Miguel Tulancingo'),(4315,340,'San Miguel Yotao'),(4316,340,'San NicolÃ¡s'),(4317,340,'San NicolÃ¡s Hidalgo'),(4318,340,'San Pablo CoatlÃ¡n'),(4319,340,'San Pablo Cuatro Venados'),(4320,340,'San Pablo Etla'),(4321,340,'San Pablo Huitzo'),(4322,340,'San Pablo Huixtepec'),(4323,340,'San Pablo Macuiltianguis'),(4324,340,'San Pablo Tijaltepec'),(4325,340,'San Pablo Villa de Mitla'),(4326,340,'San Pablo Yaganiza'),(4327,340,'San Pedro Amuzgos'),(4328,340,'San Pedro ApÃ³stol'),(4329,340,'San Pedro Atoyac'),(4330,340,'San Pedro Cajonos'),(4331,340,'San Pedro Comitancillo'),(4332,340,'San Pedro Coxcaltepec CÃ¡ntaros'),(4333,340,'San Pedro El Alto'),(4334,340,'San Pedro Huamelula'),(4335,340,'San Pedro Huilotepec'),(4336,340,'San Pedro IxcatlÃ¡n'),(4337,340,'San Pedro Ixtlahuaca'),(4338,340,'San Pedro Jaltepetongo'),(4339,340,'San Pedro Jicayan'),(4340,340,'San Pedro Jocotipac'),(4341,340,'San Pedro Juchatengo'),(4342,340,'San Pedro MÃ¡rtir'),(4343,340,'San Pedro MÃ¡rtir Quiechapa'),(4344,340,'San Pedro MÃ¡rtir Yucuxaco'),(4345,340,'San Pedro Mixtepec, Juquila'),(4346,340,'San Pedro Mixtepec, MiahuatlÃ¡n'),(4347,340,'San Pedro Molinos'),(4348,340,'San Pedro Nopala'),(4349,340,'San Pedro Ocopetatillo'),(4350,340,'San Pedro Ocotepec'),(4351,340,'San Pedro Pochutla'),(4352,340,'San Pedro Quiatoni'),(4353,340,'San Pedro Sochiapam'),(4354,340,'San Pedro Tapanatepec'),(4355,340,'San Pedro Taviche'),(4356,340,'San Pedro Teozacoalco'),(4357,340,'San Pedro Teutila'),(4358,340,'San Pedro Tidaa'),(4359,340,'San Pedro Topiltepec'),(4360,340,'San Pedro Totolapa'),(4361,340,'San Pedro Yaneri'),(4362,340,'San Pedro YÃ³lox'),(4363,340,'San Pedro y San Pablo Ayutla'),(4364,340,'San Pedro y San Pablo Teposcolula'),(4365,340,'San Pedro y San Pablo Tequixtepec'),(4366,340,'San Pedro Yucunama'),(4367,340,'San Raymundo Jalpan'),(4368,340,'San SebastiÃ¡n Abasolo'),(4369,340,'San SebastiÃ¡n CoatlÃ¡n'),(4370,340,'San SebastiÃ¡n Ixcapa'),(4371,340,'San SebastiÃ¡n Nicananduta'),(4372,340,'San SebastiÃ¡n RÃ­o Hondo'),(4373,340,'San SebastiÃ¡n Tecomaxtlahuaca'),(4374,340,'San SebastiÃ¡n Teitipac'),(4375,340,'San SebastiÃ¡n Tutla'),(4376,340,'San SimÃ³n Almolongas'),(4377,340,'San SimÃ³n Zahuatlan'),(4378,340,'Santa Ana'),(4379,340,'Santa Ana Ateixtlahuaca'),(4380,340,'Santa Ana CuauhtÃ©moc'),(4381,340,'Santa Ana del Valle'),(4382,340,'Santa Ana Tavela'),(4383,340,'Santa Ana Tlapacoyan'),(4384,340,'Santa Ana Yareni'),(4385,340,'Santa Ana Zegache'),(4386,340,'Santa Catalina Quieri'),(4387,340,'Santa Catarina Cuixtla'),(4388,340,'Santa Catarina Ixtepeji'),(4389,340,'Santa Catarina Juquila'),(4390,340,'Santa Catarina Lachatao'),(4391,340,'Santa Catarina Loxicha'),(4392,340,'Santa Catarina MechoacÃ¡n'),(4393,340,'Santa Catarina Minas'),(4394,340,'Santa Catarina QuianÃ©'),(4395,340,'Santa Catarina Quioquitani'),(4396,340,'Santa Catarina Tayata'),(4397,340,'Santa Catarina Ticua'),(4398,340,'Santa Catarina YosonotÃº'),(4399,340,'Santa Catarina Zapoquila'),(4400,340,'Santa Cruz Acatepec'),(4401,340,'Santa Cruz Amilpas'),(4402,340,'Santa Cruz de Bravo'),(4403,340,'Santa Cruz Itundujia'),(4404,340,'Santa Cruz Mixtepec'),(4405,340,'Santa Cruz Nundaco'),(4406,340,'Santa Cruz Papalutla'),(4407,340,'Santa Cruz Tacache de Mina'),(4408,340,'Santa Cruz Tacahua'),(4409,340,'Santa Cruz Tayata'),(4410,340,'Santa Cruz Xitla'),(4411,340,'Santa Cruz XoxocotlÃ¡n'),(4412,340,'Santa Cruz Zenzontepec'),(4413,340,'Santa Gertrudis'),(4414,340,'Santa InÃ©s del Monte'),(4415,340,'Santa InÃ©s de Zaragoza'),(4416,340,'Santa InÃ©s Yatzeche'),(4417,340,'Santa LucÃ­a del Camino'),(4418,340,'Santa LucÃ­a MiahuatlÃ¡n'),(4419,340,'Santa LucÃ­a Monteverde'),(4420,340,'Santa LucÃ­a OcotlÃ¡n'),(4421,340,'Santa Magdalena JicotlÃ¡n'),(4422,340,'Santa MarÃ­a Alotepec'),(4423,340,'Santa MarÃ­a Apazco'),(4424,340,'Santa MarÃ­a Atzompa'),(4425,340,'Santa MarÃ­a CamotlÃ¡n'),(4426,340,'Santa MarÃ­a Chachoapam'),(4427,340,'Santa MarÃ­a Chilchotla'),(4428,340,'Santa MarÃ­a Chimalapa'),(4429,340,'Santa MarÃ­a Colotepec'),(4430,340,'Santa MarÃ­a Cortijo'),(4431,340,'Santa MarÃ­a Coyotepec'),(4432,340,'Santa MarÃ­a del Rosario'),(4433,340,'Santa MarÃ­a del Tule'),(4434,340,'Santa MarÃ­a Ecatepec'),(4435,340,'Santa MarÃ­a GuelacÃ©'),(4436,340,'Santa MarÃ­a Guienagati'),(4437,340,'Santa MarÃ­a Huatulco'),(4438,340,'Santa MarÃ­a HuazolotitlÃ¡n'),(4439,340,'Santa MarÃ­a Ipalapa'),(4440,340,'Santa MarÃ­a IxcatlÃ¡n'),(4441,340,'Santa MarÃ­a Jacatepec'),(4442,340,'Santa MarÃ­a Jalapa del MarquÃ©s'),(4443,340,'Santa MarÃ­a Jaltianguis'),(4444,340,'Santa MarÃ­a la AsunciÃ³n'),(4445,340,'Santa MarÃ­a LachixÃ­o'),(4446,340,'Santa MarÃ­a Mixtequilla'),(4447,340,'Santa MarÃ­a Nativitas'),(4448,340,'Santa MarÃ­a Nduayaco'),(4449,340,'Santa MarÃ­a Ozolotepec'),(4450,340,'Santa MarÃ­a PÃ¡palo'),(4451,340,'Santa MarÃ­a PeÃ±oles'),(4452,340,'Santa MarÃ­a Petapa'),(4453,340,'Santa MarÃ­a Quiegolani'),(4454,340,'Santa MarÃ­a Sola'),(4455,340,'Santa MarÃ­a Tataltepec'),(4456,340,'Santa MarÃ­a Tecomavaca'),(4457,340,'Santa MarÃ­a Temaxcalapa'),(4458,340,'Santa MarÃ­a Temaxcaltepec'),(4459,340,'Santa MarÃ­a Teopoxco'),(4460,340,'Santa MarÃ­a Tepantlali'),(4461,340,'Santa MarÃ­a TexcatitlÃ¡n'),(4462,340,'Santa MarÃ­a Tlahuitoltepec'),(4463,340,'Santa MarÃ­a Tlalixtac'),(4464,340,'Santa MarÃ­a Tonameca'),(4465,340,'Santa MarÃ­a Totolapilla'),(4466,340,'Santa MarÃ­a Xadani'),(4467,340,'Santa MarÃ­a Yalina'),(4468,340,'Santa MarÃ­a YavesÃ­a'),(4469,340,'Santa MarÃ­a Yolotepec'),(4470,340,'Santa MarÃ­a YosoyÃºa'),(4471,340,'Santa MarÃ­a Yucuhiti'),(4472,340,'Santa MarÃ­a Zacatepec'),(4473,340,'Santa MarÃ­a Zaniza'),(4474,340,'Santa MarÃ­a ZoquitlÃ¡n'),(4475,340,'Santiago Amoltepec'),(4476,340,'Santiago Apoala'),(4477,340,'Santiago ApÃ³stol'),(4478,340,'Santiago Astata'),(4479,340,'Santiago AtitlÃ¡n'),(4480,340,'Santiago Ayuquililla'),(4481,340,'Santiago Cacaloxtepec'),(4482,340,'Santiago CamotlÃ¡n'),(4483,340,'Santiago Chazumba'),(4484,340,'Santiago Choapam'),(4485,340,'Santiago Comaltepec'),(4486,340,'Santiago del RÃ­o'),(4487,340,'Santiago HuajolotitlÃ¡n'),(4488,340,'Santiago Huauclilla'),(4489,340,'Santiago IhuitlÃ¡n Plumas'),(4490,340,'Santiago Ixcuintepec'),(4491,340,'Santiago Ixtayutla'),(4492,340,'Santiago Jamiltepec'),(4493,340,'Santiago Jocotepec'),(4494,340,'Santiago Juxtlahuaca'),(4495,340,'Santiago Lachiguiri'),(4496,340,'Santiago Lalopa'),(4497,340,'Santiago Laollaga'),(4498,340,'Santiago Laxopa'),(4499,340,'Santiago Llano Grande'),(4500,340,'Santiago MatatlÃ¡n'),(4501,340,'Santiago Miltepec'),(4502,340,'Santiago Minas'),(4503,340,'Santiago Nacaltepec'),(4504,340,'Santiago Nejapilla'),(4505,340,'Santiago Niltepec'),(4506,340,'Santiago Nundiche'),(4507,340,'Santiago NuyoÃ³'),(4508,340,'Santiago Suchilquitongo'),(4509,340,'Santiago Tamazola'),(4510,340,'Santiago Tapextla'),(4511,340,'Santiago Tenango'),(4512,340,'Santiago Tepetlapa'),(4513,340,'Santiago Tetepec'),(4514,340,'Santiago Texcalcingo'),(4515,340,'Santiago TextitlÃ¡n'),(4516,340,'Santiago Tilantongo'),(4517,340,'Santiago Tillo'),(4518,340,'Santiago Tlazoyaltepec'),(4519,340,'Santiago Xanica'),(4520,340,'Santiago XiacuÃ­'),(4521,340,'Santiago Yaitepec'),(4522,340,'Santiago Yaveo'),(4523,340,'Santiago YolomÃ©catl'),(4524,340,'Santiago YosondÃºa'),(4525,340,'Santiago Yucuyachi'),(4526,340,'Santiago Zacatepec'),(4527,340,'Santiago Zoochila'),(4528,340,'Santo Domingo Albarradas'),(4529,340,'Santo Domingo Armenta'),(4530,340,'Santo Domingo ChihuitÃ¡n'),(4531,340,'Santo Domingo de Morelos'),(4532,340,'Santo Domingo Ingenio'),(4533,340,'Santo Domingo IxcatlÃ¡n'),(4534,340,'Santo Domingo NuxaÃ¡'),(4535,340,'Santo Domingo Ozolotepec'),(4536,340,'Santo Domingo Petapa'),(4537,340,'Santo Domingo Roayaga'),(4538,340,'Santo Domingo Tehuantepec'),(4539,340,'Santo Domingo Teojomulco'),(4540,340,'Santo Domingo Tepuxtepec'),(4541,340,'Santo Domingo Tlatayapam'),(4542,340,'Santo Domingo Tomaltepec'),(4543,340,'Santo Domingo TonalÃ¡'),(4544,340,'Santo Domingo Tonaltepec'),(4545,340,'Santo Domingo XagacÃ­a'),(4546,340,'Santo Domingo YanhuitlÃ¡n'),(4547,340,'Santo Domingo Yodohino'),(4548,340,'Santo Domingo Zanatepec'),(4549,340,'Santos Reyes Nopala'),(4550,340,'Santos Reyes PÃ¡palo'),(4551,340,'Santos Reyes Tepejillo'),(4552,340,'Santos Reyes YucunÃ¡'),(4553,340,'Santo TomÃ¡s Jalieza'),(4554,340,'Santo TomÃ¡s Mazaltepec'),(4555,340,'Santo TomÃ¡s Ocotepec'),(4556,340,'Santo TomÃ¡s Tamazulapan'),(4557,340,'San Vicente CoatlÃ¡n'),(4558,340,'San Vicente LachixÃ­o'),(4559,340,'San Vicente NuÃ±Ãº'),(4560,340,'Silacayoapam'),(4561,340,'Sitio de Xitlapehua'),(4562,340,'Soledad Etla'),(4563,340,'Tamazulapam del EspÃ­ritu Santo'),(4564,340,'Tamazulapam del Progreso'),(4565,340,'Tanetze de Zaragoza'),(4566,340,'Taniche'),(4567,340,'Tataltepec de ValdÃ©s'),(4568,340,'Teococuilco de Marcos PÃ©rez'),(4569,340,'TeotitlÃ¡n de Flores MagÃ³n'),(4570,340,'TeotitlÃ¡n del Valle'),(4571,340,'Teotongo'),(4572,340,'Tepelmeme Villa de Morelos'),(4573,340,'TezoatlÃ¡n de Segura y Luna'),(4574,340,'Tlacolula de Matamoros'),(4575,340,'Tlacotepec Plumas'),(4576,340,'Tlalixtac de Cabrera'),(4577,340,'Tlaxiaco'),(4578,340,'Totontepec Villa de Morelos'),(4579,340,'Trinidad Zaachila'),(4580,340,'UniÃ³n Hidalgo'),(4581,340,'Valerio Trujano'),(4582,340,'Villa de Etla'),(4583,340,'Villa de Tututepec de Melchor Ocampo'),(4584,340,'Villa de Zaachila'),(4585,340,'Cuyamecalco Villa de Zaragoza'),(4586,340,'Villa DÃ­az Ordaz'),(4587,340,'Villa Hidalgo'),(4588,340,'Villa Sola de Vega'),(4589,340,'Villa Talea de Castro'),(4590,340,'Villa Tejupam de la UniÃ³n'),(4591,340,'Yaxe Magdalena'),(4592,340,'Magdalena Yodocono de Porfirio DÃ­az'),(4593,340,'Yogana'),(4594,340,'Yutanduchi de Guerrero'),(4595,340,'ZapotitlÃ¡n del RÃ­o'),(4596,340,'ZapotitlÃ¡n Lagunas'),(4597,340,'ZapotitlÃ¡n Palmas'),(4598,340,'ZimatlÃ¡n de Alvarez'),(4599,353,'Acajete'),(4600,353,'Acateno'),(4601,353,'AcatlÃ¡n de Osorio'),(4602,353,'Acatzingo'),(4603,353,'Acteopan'),(4604,353,'AhuacatlÃ¡n'),(4605,353,'AhuatlÃ¡n'),(4606,353,'Ahuazotepec'),(4607,353,'Ahuehuetitla'),(4608,353,'Ajalpan'),(4609,353,'Albino Zertuche'),(4610,353,'Aljojuca'),(4611,353,'Altepexi'),(4612,353,'Amixtlan'),(4613,353,'Amozoc'),(4614,353,'Aquixtla'),(4615,353,'Atempan'),(4616,353,'Atexcal'),(4617,353,'Atlequizayan'),(4618,353,'Atlixco'),(4619,353,'Atoyatempan'),(4620,353,'Atzala'),(4621,353,'AtzitzihuacÃ¡n'),(4622,353,'Atzitzintla'),(4623,353,'Axutla'),(4624,353,'Ayotoxco de Guerrero'),(4625,353,'Calpan'),(4626,353,'Caltepec'),(4627,353,'Camocuautla'),(4628,353,'CaÃ±ada Morelos'),(4629,353,'CaxhuacÃ¡n'),(4630,353,'Chalchicomula de Sesma'),(4631,353,'Chapulco'),(4632,353,'Chiautla'),(4633,353,'Chiautzingo'),(4634,353,'Chichiquila'),(4635,353,'Chiconcuautla'),(4636,353,'Chietla'),(4637,353,'ChigmecatitlÃ¡n'),(4638,353,'Chignahuapan'),(4639,353,'Chignautla'),(4640,353,'Chila'),(4641,353,'Chila de la Sal'),(4642,353,'Chilchotla'),(4643,353,'Chinantla'),(4644,353,'Coatepec'),(4645,353,'Coatzingo'),(4646,353,'Cohetzala'),(4647,353,'CohuecÃ¡n'),(4648,353,'Coronango'),(4649,353,'CoxcatlÃ¡n'),(4650,353,'Coyomeapan'),(4651,353,'Coyotepec'),(4652,353,'Cuapiaxtla de Madero'),(4653,353,'Cuautempan'),(4654,353,'Cuautinchan'),(4655,353,'Cuautlancingo'),(4656,353,'Cuayuca de Andrade'),(4657,353,'CuetzalÃ¡n del Progreso'),(4658,353,'Cuyoaco'),(4659,353,'Domingo Arenas'),(4660,353,'EloxochitlÃ¡n'),(4661,353,'EpatlÃ¡n'),(4662,353,'Esperanza'),(4663,353,'Francisco Z. Mena'),(4664,353,'General Felipe Ãngeles'),(4665,353,'Guadalupe'),(4666,353,'Guadalupe Victoria'),(4667,353,'Hermenegildo Galeana'),(4668,353,'Honey'),(4669,353,'Huaquechula'),(4670,353,'Huatlatlauca'),(4671,353,'Huauchinango'),(4672,353,'Huehuetla'),(4673,353,'HuehuetlÃ¡n El Chico'),(4674,353,'HuehuetlÃ¡n El Grande'),(4675,353,'Huejotzingo'),(4676,353,'Hueyapan'),(4677,353,'Hueytamalco'),(4678,353,'Hueytlalpan'),(4679,353,'Huitzilan de SerdÃ¡n'),(4680,353,'Huitziltepec'),(4681,353,'Ixcamilpa'),(4682,353,'Ixcaquixtla'),(4683,353,'IxtacamaxtitlÃ¡n'),(4684,353,'Ixtepec'),(4685,353,'IzÃºcar de Matamoros'),(4686,353,'Jalpan'),(4687,353,'Jolalpan'),(4688,353,'Jopala'),(4689,353,'Juan C. Bonilla'),(4690,353,'Juan Galindo'),(4691,353,'Juan N. MÃ©ndez'),(4692,353,'Lafragua'),(4693,353,'Libres'),(4694,353,'La Magdalena Tlatlauquitepec'),(4695,353,'Los Reyes de JuÃ¡rez'),(4696,353,'Mazapiltepec de JuÃ¡rez'),(4697,353,'Mixtla'),(4698,353,'Molcaxac'),(4699,353,'Naupan'),(4700,353,'Nauzontla'),(4701,353,'NealticÃ¡n'),(4702,353,'NicolÃ¡s Bravo'),(4703,353,'Nopalucan'),(4704,353,'Ocotepec'),(4705,353,'Ocoyucan'),(4706,353,'Olintla'),(4707,353,'Oriental'),(4708,353,'PahuatlÃ¡n'),(4709,353,'Palmar de Bravo'),(4710,353,'Pantepec'),(4711,353,'Petlalcingo'),(4712,353,'Piaxtla'),(4713,353,'Puebla'),(4714,353,'Quecholac'),(4715,353,'QuimixtlÃ¡n'),(4716,353,'Rafael Lara Grajales'),(4717,353,'San AndrÃ©s Cholula'),(4718,353,'San Antonio CaÃ±ada'),(4719,353,'San Diego La Mesa Tochimiltzingo'),(4720,353,'San Felipe Teotlalcingo'),(4721,353,'San Felipe TepatlÃ¡n'),(4722,353,'San Gabriel Chilac'),(4723,353,'San Gregorio Atzompa'),(4724,353,'San JerÃ³nimo Tecuanipan'),(4725,353,'San JerÃ³nimo XayacatlÃ¡n'),(4726,353,'San JosÃ© Chiapa'),(4727,353,'San JosÃ© MiahuatlÃ¡n'),(4728,353,'San Juan Atenco'),(4729,353,'San Juan Atzompa'),(4730,353,'San MartÃ­n Texmelucan'),(4731,353,'San MartÃ­n Totoltepec'),(4732,353,'San MatÃ­as Tlalancaleca'),(4733,353,'San Miguel IxitlÃ¡n'),(4734,353,'San Miguel Xoxtla'),(4735,353,'San NicolÃ¡s de Buenos Aires'),(4736,353,'San NicolÃ¡s de los Ranchos'),(4737,353,'San Pablo Anicano'),(4738,353,'San Pedro Cholula'),(4739,353,'San Pedro Yeloixtlahuacan'),(4740,353,'San Salvador El Seco'),(4741,353,'San Salvador El Verde'),(4742,353,'San Salvador Huixcolotla'),(4743,353,'San SebastiÃ¡n Tlacotepec'),(4744,353,'Santa Catarina Tlaltempan'),(4745,353,'Santa InÃ©s Ahuatempan'),(4746,353,'Santa Isabel Cholula'),(4747,353,'Santiago MiahuatlÃ¡n'),(4748,353,'Santo TomÃ¡s HueyotlipÃ¡n'),(4749,353,'Soltepec'),(4750,353,'Tecali'),(4751,353,'Tecamachalco'),(4752,353,'TecomatlÃ¡n'),(4753,353,'TehuacÃ¡n'),(4754,353,'Tehuitzingo'),(4755,353,'Tenampulco'),(4756,353,'TeopantlÃ¡n'),(4757,353,'Teotlalco'),(4758,353,'Tepanco de LÃ³pez'),(4759,353,'Tepango de RodrÃ­guez'),(4760,353,'Tepatlaxco de Hidalgo'),(4761,353,'Tepeaca'),(4762,353,'Tepemaxalco'),(4763,353,'Tepeojuma'),(4764,353,'Tepetzintla'),(4765,353,'Tepexco'),(4766,353,'Tepexi de RodrÃ­guez'),(4767,353,'Tepeyahualco'),(4768,353,'Tepeyahualco de CuauhtÃ©moc'),(4769,353,'Tetela de Ocampo'),(4770,353,'Teteles de Ãvila Castillo'),(4771,353,'TezuitlÃ¡n'),(4772,353,'Tianguismanalco'),(4773,353,'Tilapa'),(4774,353,'Tlachichuca'),(4775,353,'Tlacotepec de Benito JuÃ¡rez'),(4776,353,'Tlacuilotepec'),(4777,353,'Tlahuapan'),(4778,353,'Tlaltenango'),(4779,353,'Tlanepantla'),(4780,353,'Tlaola'),(4781,353,'Tlapacoya'),(4782,353,'Tlapanala'),(4783,353,'Tlatlauquitepec'),(4784,353,'Tlaxco'),(4785,353,'Tochimilco'),(4786,353,'Tochtepec'),(4787,353,'Totoltepec de Guerrero'),(4788,353,'Tulcingo'),(4789,353,'TuzamapÃ¡n de Galeana'),(4790,353,'Tzicatlacoyan'),(4791,353,'Venustiano Carranza'),(4792,353,'Vicente Guerrero'),(4793,353,'XayacatlÃ¡n de Bravo'),(4794,353,'Xicotepec'),(4795,353,'XicotlÃ¡n'),(4796,353,'Xiutetelco'),(4797,353,'Xochiapulco'),(4798,353,'Xochiltepec'),(4799,353,'XochitlÃ¡n de Vicente SuÃ¡rez'),(4800,353,'XochitlÃ¡n Todos Santos'),(4801,353,'Xonotla'),(4802,353,'Yaonahuac'),(4803,353,'Yehualtepec'),(4804,353,'Zacapala'),(4805,353,'Zacapoaxtla'),(4806,353,'ZacatlÃ¡n'),(4807,353,'ZapotitlÃ¡n'),(4808,353,'ZapotitlÃ¡n de MÃ©ndez'),(4809,353,'Zaragoza'),(4810,353,'Zautla'),(4811,353,'Zihuateutla'),(4812,353,'Zinacatepec'),(4813,353,'Zongozotla'),(4814,353,'Zoquiapan'),(4815,353,'ZoquitlÃ¡n'),(4816,193,'Cozumel'),(4817,193,'Felipe Carrillo Puerto'),(4818,193,'Isla Mujeres'),(4819,193,'OthÃ³n P. Blanco'),(4820,193,'Benito JuÃ¡rez'),(4821,193,'JosÃ© MarÃ­a Morelos'),(4822,193,'LÃ¡zaro CÃ¡rdenas'),(4823,193,'Solidaridad'),(4824,193,'Tulum'),(4825,193,'Bacalar'),(4826,200,'Ahome'),(4827,200,'Angostura'),(4828,200,'Badiraguato'),(4829,200,'Concordia'),(4830,200,'CosalÃ¡'),(4831,200,'CuliacÃ¡n'),(4832,200,'Choix'),(4833,200,'Elota'),(4834,200,'Escuinapa'),(4835,200,'El Fuerte'),(4836,200,'Guasave'),(4837,200,'MazatlÃ¡n'),(4838,200,'Mocorito'),(4839,200,'Rosario'),(4840,200,'Salvador Alvarado'),(4841,200,'San Ignacio'),(4842,200,'Sinaloa'),(4843,200,'Navolato'),(4844,197,'Aconchi'),(4845,197,'Agua Prieta'),(4846,197,'Altar'),(4847,197,'Arivechi'),(4848,197,'Arizpe'),(4849,197,'Atil'),(4850,197,'BacadÃ©huachi'),(4851,197,'Bacanora'),(4852,197,'Bacerac');
INSERT INTO `lkupcounty`(countyId, stateid, countyName) VALUES (4853,197,'Bacoachi'),(4854,197,'BÃ¡cum'),(4855,197,'BanÃ¡michi'),(4856,197,'BaviÃ¡cora'),(4857,197,'Bavispe'),(4858,197,'Benito JuÃ¡rez'),(4859,197,'BenjamÃ­n Hill'),(4860,197,'Caborca'),(4861,197,'Cajeme'),(4862,197,'Cananea'),(4863,197,'CarbÃ³'),(4864,197,'Cumpas'),(4865,197,'Divisaderos'),(4866,197,'Empalme'),(4867,197,'Etchojoa'),(4868,197,'Fronteras'),(4869,197,'Granados'),(4870,197,'Guaymas'),(4871,197,'Hermosillo'),(4872,197,'Huachinera'),(4873,197,'HuÃ¡sabas'),(4874,197,'Huatabampo'),(4875,197,'HuÃ©pac'),(4876,197,'Imuris'),(4877,197,'La Colorada'),(4878,197,'Magdalena de Kino'),(4879,197,'MazatÃ¡n'),(4880,197,'Moctezuma'),(4881,197,'Naco'),(4882,197,'NÃ¡cori Chico'),(4883,197,'Nacozari de GarcÃ­a'),(4884,197,'Navojoa'),(4885,197,'Nogales'),(4886,197,'Onavas'),(4887,197,'Opodepe'),(4888,197,'Oquitoa'),(4889,197,'Pitiquito'),(4890,197,'Puerto PeÃ±asco'),(4891,197,'Plutarco ElÃ­as Calles'),(4892,197,'Quiriego'),(4893,197,'RayÃ³n'),(4894,197,'Rosario de Tesopaco'),(4895,197,'Sahuaripa'),(4896,197,'San Ignacio RÃ­o Muerto'),(4897,197,'San Javier'),(4898,197,'San Luis RÃ­o Colorado'),(4899,197,'San Miguel de Horcasitas'),(4900,197,'San Pedro de la Cueva'),(4901,197,'Santa Ana'),(4902,197,'Santa Cruz'),(4903,197,'SÃ¡ric'),(4904,197,'Soyopa'),(4905,197,'Suaqui Grande'),(4906,197,'Tepache'),(4907,197,'Trincheras'),(4908,197,'Tubutama'),(4909,197,'Ures'),(4910,197,'Villa Hidalgo'),(4911,197,'Villa Pesqueira'),(4912,380,'BalancÃ¡n'),(4913,380,'CÃ¡rdenas'),(4914,380,'Centla'),(4915,380,'Centro'),(4916,380,'Comalcalco'),(4917,380,'CunduacÃ¡n'),(4918,380,'Emiliano Zapata'),(4919,380,'Huimanguillo'),(4920,380,'Jalapa'),(4921,380,'Jalpa de MÃ©ndez'),(4922,380,'Jonuta'),(4923,380,'Macuspana'),(4924,380,'Nacajuca'),(4925,380,'ParaÃ­so'),(4926,380,'Tacotalpa'),(4927,380,'Teapa'),(4928,380,'Tenosique'),(4929,214,'Abasolo'),(4930,214,'Aldama'),(4931,214,'Altamira'),(4932,214,'Antiguo Morelos'),(4933,214,'Burgos'),(4934,214,'Bustamante'),(4935,214,'Camargo'),(4936,214,'Casas'),(4937,214,'Ciudad Madero'),(4938,214,'Cruillas'),(4939,214,'GÃ³mez FarÃ­as'),(4940,214,'GonzÃ¡lez'),(4941,214,'GÃ¼Ã©mez'),(4942,214,'Guerrero'),(4943,214,'Gustavo DÃ­az Ordaz'),(4944,214,'Hidalgo'),(4945,214,'Juamave'),(4946,214,'JimÃ©nez'),(4947,214,'Llera'),(4948,214,'Mainero'),(4949,214,'El Mante'),(4950,214,'Matamoros'),(4951,214,'MÃ©ndez'),(4952,214,'Mier'),(4953,214,'Miguel AlemÃ¡n'),(4954,214,'Miquihuana'),(4955,214,'Nuevo Laredo'),(4956,214,'Nuevo Morelos'),(4957,214,'Ocampo'),(4958,214,'Padilla'),(4959,214,'Palmillas'),(4960,214,'Reynosa'),(4961,214,'RÃ­o Bravo'),(4962,214,'San Carlos'),(4963,214,'San Fernando'),(4964,214,'San NicolÃ¡s'),(4965,214,'Soto la Marina'),(4966,214,'Tampico'),(4967,214,'Tula'),(4968,214,'Valle Hermoso'),(4969,214,'Victoria'),(4970,214,'VillagrÃ¡n'),(4971,214,'XicotÃ©ncatl'),(4972,386,'Acuamanala de Miguel Hidalgo'),(4973,386,'Altzayanca'),(4974,386,'Amaxac de Guerrero'),(4975,386,'ApetatitlÃ¡n de Antonio Carvajal'),(4976,386,'Apizaco'),(4977,386,'Atlangatepec'),(4978,386,'Benito JuÃ¡rez'),(4979,386,'Calpulalpan'),(4980,386,'Chiautempan'),(4981,386,'Contla de Juan Cuamatzi'),(4982,386,'Cuapiaxtla'),(4983,386,'Cuaxomulco'),(4984,386,'El Carmen Tequexquitla'),(4985,386,'Emiliano Zapata'),(4986,386,'EspaÃ±ita'),(4987,386,'Huamantla'),(4988,386,'Hueyotlipan'),(4989,386,'Ixtacuixtla de Mariano Matamoros'),(4990,386,'Ixtenco'),(4991,386,'La Magdalena Tlaltelulco'),(4992,386,'LÃ¡zaro CÃ¡rdenas'),(4993,386,'Mazatecochco de JosÃ© MarÃ­a Morelos'),(4994,386,'MuÃ±oz de Domingo Arenas'),(4995,386,'Nanacamilpa de Mariano Arista'),(4996,386,'Nativitas'),(4997,386,'Panotla'),(4998,386,'Papalotla de Xicohtencatl'),(4999,386,'Sanctorum de LÃ¡zaro CÃ¡rdenas'),(5000,386,'San DamiÃ¡n Texoloc'),(5001,386,'San Francisco Tetlanohcan'),(5002,386,'San JerÃ³nimo Zacualpan'),(5003,386,'San JosÃ© Teacalco'),(5004,386,'San Juan Huactzinco'),(5005,386,'San Lorenzo Axocomanitla'),(5006,386,'San Lucas Tecopilco'),(5007,386,'San Pablo del Monte'),(5008,386,'Santa Ana Nopalucan'),(5009,386,'Santa Apolonia Teacalco'),(5010,386,'Santa Catarina Ayometla'),(5011,386,'Santa Cruz Quilehtla'),(5012,386,'Santa Cruz Tlaxcala'),(5013,386,'Santa Isabel Xiloxoxtla'),(5014,386,'Tenancingo'),(5015,386,'Teolocholco'),(5016,386,'Tepetitla de Lardizabal'),(5017,386,'Tepeyanco'),(5018,386,'Terrenate'),(5019,386,'Tetla de la Solidaridad'),(5020,386,'Tetlatlahuca'),(5021,386,'Tlaxcala'),(5022,386,'Tlaxco'),(5023,386,'TocatlÃ¡n'),(5024,386,'Totolac'),(5025,386,'Tzompantepec'),(5026,386,'Xaloztoc'),(5027,386,'Xaltocan'),(5028,386,'Xicohtzinco'),(5029,386,'Yauhquemecan'),(5030,386,'Zacatelco'),(5031,386,'Zitlaltepec de Trinidad SÃ¡nchez Santos'),(5032,397,'Acajete'),(5033,397,'AcatlÃ¡n'),(5034,397,'Acayucan'),(5035,397,'Actopan'),(5036,397,'Acula'),(5037,397,'Acultzingo'),(5038,397,'Agua Dulce'),(5039,397,'Alpatlahuac'),(5040,397,'Alto Lucero de GutiÃ©rrez Barrios'),(5041,397,'Altotonga'),(5042,397,'Alvarado'),(5043,397,'AmatitlÃ¡n'),(5044,397,'AmatlÃ¡n de los Reyes'),(5045,397,'Ãngel R. Cabada'),(5046,397,'Apazapan'),(5047,397,'Aquila'),(5048,397,'Astacinga'),(5049,397,'Atlahuilco'),(5050,397,'Atoyac'),(5051,397,'Atzacan'),(5052,397,'AtzalÃ¡n'),(5053,397,'Ayahualulco'),(5054,397,'Banderilla'),(5055,397,'Benito JuÃ¡rez'),(5056,397,'Boca del RÃ­o'),(5057,397,'Calcahualco'),(5058,397,'CamarÃ³n de Tejeda'),(5059,397,'Camerino Z. Mendoza'),(5060,397,'Carlos A. Carrillo'),(5061,397,'Carrillo Puerto'),(5062,397,'Castillo de Teayo'),(5063,397,'Catemaco'),(5064,397,'Cazones'),(5065,397,'Cerro Azul'),(5066,397,'Chacaltianguis'),(5067,397,'Chalma'),(5068,397,'Chiconamel'),(5069,397,'Chiconquiaco'),(5070,397,'Chicontepec'),(5071,397,'Chinameca'),(5072,397,'Chinampa de Gorostiza'),(5073,397,'Chocaman'),(5074,397,'Chontla'),(5075,397,'Chumatlan'),(5076,397,'Citlaltepetl'),(5077,397,'Coacoatzintla'),(5078,397,'Coahuitlan'),(5079,397,'Coatepec'),(5080,397,'Coatzacoalcos'),(5081,397,'Coatzintla'),(5082,397,'Coetzala'),(5083,397,'Colipa'),(5084,397,'Comapa'),(5085,397,'CÃ³rdoba'),(5086,397,'Cosamaloapan de Carpio'),(5087,397,'CosautlÃ¡n de Carvajal'),(5088,397,'Coscomatepec'),(5089,397,'Cosoleacaque'),(5090,397,'Cotaxtla'),(5091,397,'Coxquihui'),(5092,397,'Coyutla'),(5093,397,'Cuichapa'),(5094,397,'CuitlÃ¡huac'),(5095,397,'El Higo'),(5096,397,'Emiliano Zapata'),(5097,397,'Espinal'),(5098,397,'Filomeno Mata'),(5099,397,'FortÃ­n'),(5100,397,'GutiÃ©rrez Zamora'),(5101,397,'HidalgotitlÃ¡n'),(5102,397,'Huatusco'),(5103,397,'Huayacocotla'),(5104,397,'Hueyapan de Ocampo'),(5105,397,'Huiloapan'),(5106,397,'Ignacio de la Llave'),(5107,397,'IlamatlÃ¡n'),(5108,397,'Isla'),(5109,397,'Ixcatepec'),(5110,397,'IxhuacÃ¡n de los Reyes'),(5111,397,'Ixhuatlancillo'),(5112,397,'IxhuatlÃ¡n del CafÃ©'),(5113,397,'IxhuatlÃ¡n del Sureste'),(5114,397,'IxhuatlÃ¡n de Madero'),(5115,397,'Ixmatlahuacan'),(5116,397,'IxtaczoquitlÃ¡n'),(5117,397,'Jalacingo'),(5118,397,'Jalcomulco'),(5119,397,'Jaltipan'),(5120,397,'Jamapa'),(5121,397,'JesÃºs Carranza'),(5122,397,'Jilotepec'),(5123,397,'JosÃ© Azueta'),(5124,397,'Juan RodrÃ­guez Clara'),(5125,397,'Juchique de Ferrer'),(5126,397,'Landero y Coss'),(5127,397,'La Antigua'),(5128,397,'La Perla'),(5129,397,'Las Choapas'),(5130,397,'Las Minas'),(5131,397,'Las Vigas de RamÃ­rez'),(5132,397,'Lerdo de Tejada'),(5133,397,'Los Reyes'),(5134,397,'Magdalena'),(5135,397,'Maltrata'),(5136,397,'Manlio Fabio Altamirano'),(5137,397,'Mariano Escobedo'),(5138,397,'MartÃ­nez de la Torre'),(5139,397,'MecatlÃ¡n'),(5140,397,'Mecayapan'),(5141,397,'MedellÃ­n'),(5142,397,'MiahuatlÃ¡n'),(5143,397,'MinatitlÃ¡n'),(5144,397,'Misantla'),(5145,397,'Mixtla de Altamirano'),(5146,397,'MoloacÃ¡n'),(5147,397,'Nanchital'),(5148,397,'Naolinco'),(5149,397,'Naranjal'),(5150,397,'Naranjos AmatlÃ¡n'),(5151,397,'Nautla'),(5152,397,'Nogales'),(5153,397,'Oluta'),(5154,397,'Omealca'),(5155,397,'Orizaba'),(5156,397,'OtatitlÃ¡n'),(5157,397,'Oteapan'),(5158,397,'Ozuluama de MascareÃ±as'),(5159,397,'Pajapan'),(5160,397,'PÃ¡nuco'),(5161,397,'Papantla'),(5162,397,'Paso de Ovejas'),(5163,397,'Paso del Macho'),(5164,397,'Perote'),(5165,397,'PlatÃ³n SÃ¡nchez'),(5166,397,'Playa Vicente'),(5167,397,'Poza Rica de Hidalgo'),(5168,397,'Pueblo Viejo'),(5169,397,'Puente Nacional'),(5170,397,'Rafael Delgado'),(5171,397,'Rafael Lucio'),(5172,397,'RÃ­o Blanco'),(5173,397,'Saltabarranca'),(5174,397,'San AndrÃ©s Tenejapan'),(5175,397,'San AndrÃ©s Tuxtla'),(5176,397,'San Juan Evangelista'),(5177,397,'Santiago Tuxtla'),(5178,397,'Sayula de AlemÃ¡n'),(5179,397,'Sochiapa'),(5180,397,'Soconusco'),(5181,397,'Soledad Atzompa'),(5182,397,'Soledad de Doblado'),(5183,397,'Soteapan'),(5184,397,'TamalÃ­n'),(5185,397,'Tamiahua'),(5186,397,'Tampico Alto'),(5187,397,'Tancoco'),(5188,397,'Tantima'),(5189,397,'Tantoyuca'),(5190,397,'Tatahuicapan de JuÃ¡rez'),(5191,397,'Tatatila'),(5192,397,'Tecolutla'),(5193,397,'Tehuipango'),(5194,397,'Temapache'),(5195,397,'Tempoal'),(5196,397,'Tenampa'),(5197,397,'TenochtitlÃ¡n'),(5198,397,'Teocelo'),(5199,397,'Tepatlaxco'),(5200,397,'TepetlÃ¡n'),(5201,397,'Tepetzintla'),(5202,397,'Tequila'),(5203,397,'Texcatepec'),(5204,397,'TexhuacÃ¡n'),(5205,397,'Texistepec'),(5206,397,'Tezonapa'),(5207,397,'Tierra Blanca'),(5208,397,'TihuatlÃ¡n'),(5209,397,'Tlachichilco'),(5210,397,'Tlacojalpan'),(5211,397,'Tlacolulan'),(5212,397,'Tlacotalpan'),(5213,397,'Tlacotepec de MejÃ­a'),(5214,397,'Tlalixcoyan'),(5215,397,'Tlalnelhuayocan'),(5216,397,'Tlaltetela'),(5217,397,'Tlapacoyan'),(5218,397,'Tlaquilpa'),(5219,397,'Tlilapan'),(5220,397,'TomatlÃ¡n'),(5221,397,'Tonayan'),(5222,397,'Totutla'),(5223,397,'Tres Valles'),(5224,397,'Tuxpam'),(5225,397,'Tuxtilla'),(5226,397,'Ãšrsulo GalvÃ¡n'),(5227,397,'Uxpanapa'),(5228,397,'Vega de Alatorre'),(5229,397,'Veracruz'),(5230,397,'Villa Aldama'),(5231,397,'Xalapa'),(5232,397,'Xico'),(5233,397,'Xoxocotla'),(5234,397,'Yanga'),(5235,397,'Yecuatla'),(5236,397,'Zacualpan'),(5237,397,'Zaragoza'),(5238,397,'Zentla'),(5239,397,'Zongolica'),(5240,397,'ZontecomatlÃ¡n de LÃ³pez y Fuentes'),(5241,397,'Zozocolco de Hidalgo'),(5242,397,'San Rafael'),(5243,397,'Santiago Sochiapan'),(5244,207,'Apozol'),(5245,207,'Apulco'),(5246,207,'Atolinga'),(5247,207,'Florencia de Benito JuÃ¡rez'),(5248,207,'Calera de VÃ­ctor Rosales'),(5249,207,'CaÃ±itas de Felipe Pescador'),(5250,207,'ConcepciÃ³n del Oro'),(5251,207,'CuauhtÃ©moc'),(5252,207,'Chalchihuites'),(5253,207,'El Plateado de JoaquÃ­n Amaro'),(5254,207,'El Salvador'),(5255,207,'Fresnillo'),(5256,207,'Genaro Codina'),(5257,207,'General Enrique Estrada'),(5258,207,'General Francisco R MurguÃ­a'),(5259,207,'General PÃ¡nfilo Natera'),(5260,207,'Guadalupe'),(5261,207,'Huanusco'),(5262,207,'Jalpa'),(5263,207,'Jerez de GarcÃ­a Salinas'),(5264,207,'JimÃ©nez del Teul'),(5265,207,'Juan Aldama'),(5266,207,'Juchipila'),(5267,207,'Loreto'),(5268,207,'Luis Moya'),(5269,207,'Mazapil'),(5270,207,'Melchor Ocampo'),(5271,207,'Mezquital del Oro'),(5272,207,'Miguel Auza'),(5273,207,'Momax'),(5274,207,'Monte Escobedo'),(5275,207,'Morelos'),(5276,207,'Moyahua de Estrada'),(5277,207,'NochistlÃ¡n de MejÃ­a'),(5278,207,'Noria de Ãngeles'),(5279,207,'Ojocaliente'),(5280,207,'PÃ¡nuco'),(5281,207,'Pinos'),(5282,207,'RÃ­o Grande'),(5283,207,'Santa MarÃ­a de la Paz'),(5289,207,'SusticacÃ¡n'),(5316,207,'Sombrerete'),(5317,207,'Tabasco'),(5318,207,'TepechitlÃ¡n'),(5319,207,'Tepetongo'),(5320,207,'TeÃºl de GonzÃ¡lez Ortega'),(5321,207,'Tlaltenango de SÃ¡nchez RomÃ¡n'),(5322,207,'Valparaiso'),(5323,207,'Trinidad GarcÃ­a de la Cadena'),(5325,207,'Vetagrande'),(5326,207,'Villa de Cos'),(5327,207,'Villa GarcÃ­a'),(5328,207,'Villa GonzÃ¡lez Ortega'),(5329,207,'Villa Hidalgo'),(5330,207,'Villanueva'),(5331,207,'Zacatecas'),(5332,328,'Acambay'),(5333,328,'Acolman'),(5334,328,'Aculco'),(5335,328,'Almoloya de Alquisiras'),(5336,328,'Almoloya de JuÃ¡rez'),(5337,328,'Almoloya del RÃ­o'),(5338,328,'Amanalco'),(5339,328,'Amatepec'),(5340,328,'Amecameca'),(5341,328,'Apaxco'),(5342,328,'Atenco'),(5343,328,'AtizapÃ¡n'),(5344,328,'AtizapÃ¡n de Zaragoza'),(5345,328,'Atlacomulco'),(5346,328,'Atlautla'),(5347,328,'Axapusco'),(5348,328,'Ayapango'),(5349,328,'Calimaya'),(5350,328,'Capulhuac'),(5351,328,'Coacalco de BerriozÃ¡bal'),(5352,328,'Coatepec Harinas'),(5353,328,'CocotitlÃ¡n'),(5354,328,'Coyotepec'),(5355,328,'CuautitlÃ¡n'),(5356,328,'Chalco'),(5357,328,'Chapa de Mota'),(5358,328,'Chapultepec'),(5359,328,'Chiautla'),(5360,328,'Chicoloapan'),(5361,328,'Chiconcuac'),(5362,328,'ChimalhuacÃ¡n'),(5363,328,'Donato Guerra'),(5364,328,'Ecatepec de Morelos'),(5365,328,'Ecatzingo'),(5366,328,'Huehuetoca'),(5367,328,'Hueypoxtla'),(5368,328,'Huixquilucan'),(5369,328,'Isidro Fabela'),(5370,328,'Ixtapaluca'),(5371,328,'Ixtapan de la Sal'),(5372,328,'Ixtapan del Oro'),(5373,328,'Ixtlahuaca'),(5374,328,'Xalatlaco'),(5375,328,'Jaltenco'),(5376,328,'Jilotepec'),(5377,328,'Jilotzingo'),(5378,328,'Jiquipilco'),(5379,328,'JocotitlÃ¡n'),(5380,328,'Joquicingo'),(5381,328,'Juchitepec'),(5382,328,'Lerma'),(5383,328,'Malinalco'),(5384,328,'Melchor Ocampo'),(5385,328,'Metepec'),(5386,328,'Mexicaltzingo'),(5387,328,'Morelos'),(5388,328,'Naucalpan'),(5389,328,'NezahualcÃ³yotl'),(5390,328,'Nextlalpan'),(5391,328,'NicolÃ¡s Romero'),(5392,328,'Nopaltepec'),(5393,328,'Ocoyoacac'),(5394,328,'OcuilÃ¡n'),(5395,328,'El Oro'),(5396,328,'Otumba'),(5397,328,'Otzoloapan'),(5398,328,'Otzolotepec'),(5399,328,'Ozumba'),(5400,328,'Papalotla'),(5401,328,'La Paz'),(5402,328,'PolotitlÃ¡n'),(5403,328,'RayÃ³n'),(5404,328,'San Antonio la Isla'),(5405,328,'San Felipe del Progreso'),(5406,328,'San MartÃ­n de las PirÃ¡mides'),(5407,328,'San Mateo Atenco'),(5408,328,'San SimÃ³n de Guerrero'),(5409,328,'Santo TomÃ¡s'),(5410,328,'Soyaniquilpan de JuÃ¡rez'),(5411,328,'Sultepec'),(5412,328,'TecÃ¡mac'),(5413,328,'Tejupilco'),(5414,328,'Temamatla'),(5415,328,'Temascalapa'),(5416,328,'Temascalcingo'),(5417,328,'Temascaltepec'),(5418,328,'Temoaya'),(5419,328,'Tenancingo'),(5420,328,'Tenango del Aire'),(5421,328,'Tenango del Valle'),(5422,328,'Teoloyucan'),(5423,328,'TeotihuacÃ¡n'),(5424,328,'Tepetlaoxtoc'),(5425,328,'Tepetlixpa'),(5426,328,'TepotzotlÃ¡n'),(5427,328,'Tequixquiac'),(5428,328,'TexcaltitlÃ¡n'),(5429,328,'Texcalyacac'),(5430,328,'Texcoco'),(5431,328,'Tezoyuca'),(5432,328,'Tianguistenco'),(5433,328,'Timilpan'),(5434,328,'Tlalmanalco'),(5435,328,'Tlalnepantla de Baz'),(5436,328,'Tlatlaya'),(5437,328,'Toluca'),(5438,328,'Tonatico'),(5439,328,'Tultepec'),(5440,328,'TultitlÃ¡n'),(5441,328,'Valle de Bravo'),(5442,328,'Villa de Allende'),(5443,328,'Villa del CarbÃ³n'),(5444,328,'Villa Guerrero'),(5445,328,'Villa Victoria'),(5446,328,'XonacatlÃ¡n'),(5447,328,'Zacazonapan'),(5448,328,'Zacualpan, State of Mexico'),(5449,328,'Zinacantepec'),(5450,328,'ZumpahuacÃ¡n'),(5451,328,'Zumpango'),(5452,328,'CuautitlÃ¡n Izcalli'),(5453,328,'Valle de Chalco Solidaridad'),(5454,328,'Luvianos'),(5455,328,'San JosÃ© del RincÃ³n'),(5456,328,'Tonanitla'),(5457,329,'Acuitzio'),(5458,329,'Aguililla'),(5459,329,'Ãlvaro ObregÃ³n'),(5460,329,'Angamacutiro'),(5461,329,'Angangueo'),(5462,329,'ApatzingÃ¡n'),(5463,329,'Aporo'),(5464,329,'Aquila'),(5465,329,'Ario'),(5466,329,'Arteaga'),(5467,329,'BriseÃ±as'),(5468,329,'Buenavista'),(5469,329,'Caracuaro'),(5470,329,'Charapan'),(5471,329,'Charo'),(5472,329,'Chavinda'),(5473,329,'CherÃ¡n'),(5474,329,'Chilchota'),(5475,329,'Chinicuila'),(5476,329,'ChucÃ¡ndiro'),(5477,329,'Churintzio'),(5478,329,'Churumuco'),(5479,329,'Coahuayana'),(5480,329,'CoalcomÃ¡n de VÃ¡zquez Pallares'),(5481,329,'Coeneo'),(5482,329,'CojumatlÃ¡n de RÃ©gules'),(5483,329,'Contepec'),(5484,329,'CopÃ¡ndaro'),(5485,329,'Cotija'),(5486,329,'Cuitzeo'),(5487,329,'Ecuandureo'),(5488,329,'EpitÃ¡cio Huerta'),(5489,329,'Erongaricuaro'),(5490,329,'Gabriel Zamora'),(5491,329,'Hidalgo'),(5492,329,'La Huacana'),(5493,329,'Huandacareo'),(5494,329,'Huaniqueo'),(5495,329,'Huetamo'),(5496,329,'Huiramba'),(5497,329,'Indaparapeo'),(5498,329,'Irimbo'),(5499,329,'IxtlÃ¡n'),(5500,329,'Jacona'),(5501,329,'JimÃ©nez'),(5502,329,'Jiquilpan'),(5503,329,'JosÃ© Sixto Verduzco'),(5504,329,'JuÃ¡rez'),(5505,329,'Jungapeo'),(5506,329,'Lagunillas'),(5507,329,'La Piedad'),(5508,329,'LÃ¡zaro CÃ¡rdenas'),(5509,329,'Los Reyes'),(5510,329,'Madero'),(5511,329,'MaravatÃ­o'),(5512,329,'Marcos'),(5513,329,'Morelia'),(5514,329,'Morelos'),(5515,329,'MÃºgica'),(5516,329,'NahuatzÃ©n'),(5517,329,'NocupÃ©taro'),(5518,329,'Nuevo Parangaricutiro'),(5519,329,'Nuevo Urecho'),(5520,329,'NumarÃ¡n'),(5521,329,'Ocampo'),(5522,329,'PajacuarÃ¡n'),(5523,329,'Panindicuaro'),(5524,329,'Paracho'),(5525,329,'ParÃ¡cuaro'),(5526,329,'PÃ¡tzcuaro'),(5527,329,'Penjamillo'),(5528,329,'PeribÃ¡n'),(5529,329,'PurÃ©pero'),(5530,329,'PuruÃ¡ndiro'),(5531,329,'QuerÃ©ndaro'),(5532,329,'Quiroga'),(5533,329,'Sahuayo'),(5534,329,'Salvador Escalante'),(5535,329,'San Lucas'),(5536,329,'Santa Ana Maya'),(5537,329,'SenguÃ­o'),(5538,329,'Susupuato'),(5539,329,'TacÃ¡mbaro'),(5540,329,'TancÃ­taro'),(5541,329,'Tangamandapio'),(5542,329,'TangancÃ­cuaro'),(5543,329,'Tanhuato'),(5544,329,'Taretan'),(5545,329,'TarÃ­mbaro'),(5546,329,'Tepalcatepec'),(5547,329,'Tingambato'),(5548,329,'TingÃ¼indÃ­n'),(5549,329,'Tiquicheo de Nicolas Romero'),(5550,329,'Tlalpujahua'),(5551,329,'Tlazazalca'),(5552,329,'Tocumbo'),(5553,329,'TumbiscatÃ­o'),(5554,329,'Turicato'),(5555,329,'Tuxpan'),(5556,329,'Tuzantla'),(5557,329,'Tzintzuntzan'),(5558,329,'TzitzÃ­o'),(5559,329,'Uruapan'),(5560,329,'Venustiano Carranza'),(5561,329,'Villamar'),(5562,329,'Vista Hermosa'),(5563,329,'YurÃ©cuaro'),(5564,329,'ZacapÃº'),(5565,329,'Zamora'),(5566,329,'ZinÃ¡paro'),(5567,329,'ZinapÃ©cuaro'),(5568,329,'Ziracuaretiro'),(5569,329,'ZitÃ¡cuaro'),(5570,208,'Abasolo'),(5571,208,'Agualeguas'),(5572,208,'Allende'),(5573,208,'AnÃ¡huac'),(5574,208,'Apodaca'),(5575,208,'Aramberri'),(5576,208,'Bustamante'),(5577,208,'Cadereyta JimÃ©nez'),(5578,208,'El Carmen'),(5579,208,'Cerralvo'),(5580,208,'China'),(5581,208,'CiÃ©nega de Flores'),(5582,208,'Doctor Arroyo'),(5583,208,'Doctor Coss'),(5584,208,'Doctor GonzÃ¡lez'),(5585,208,'Galeana'),(5586,208,'GarcÃ­a'),(5587,208,'General Bravo'),(5588,208,'General Escobedo'),(5589,208,'General TerÃ¡n'),(5590,208,'General TreviÃ±o'),(5591,208,'General Zaragoza'),(5592,208,'General Zuazua'),(5593,208,'Guadalupe'),(5594,208,'Hidalgo'),(5595,208,'Higueras'),(5596,208,'Hualahuises'),(5597,208,'Iturbide'),(5598,208,'JuÃ¡rez'),(5599,208,'Lampazos de Naranjo'),(5600,208,'Linares'),(5601,208,'Los Aldama'),(5602,208,'Los Herrera'),(5603,208,'Los Ramones'),(5604,208,'MarÃ­n'),(5605,208,'Melchor Ocampo'),(5606,208,'Mier y Noriega'),(5607,208,'Mina'),(5608,208,'Montemorelos'),(5609,208,'Monterrey'),(5610,208,'ParÃ¡s'),(5611,208,'PesquerÃ­a'),(5612,208,'Rayones'),(5613,208,'Sabinas Hidalgo'),(5614,208,'Salinas Victoria'),(5615,208,'San NicolÃ¡s de los Garza'),(5616,208,'San Pedro Garza GarcÃ­a'),(5617,208,'Santa Catarina'),(5618,208,'Santiago'),(5619,208,'Vallecillo'),(5620,208,'Villaldama'),(5621,357,'Amealco de Bonfil'),(5622,357,'Pinal de Amoles'),(5623,357,'Arroyo Seco'),(5624,357,'Cadereyta de Montes'),(5625,357,'ColÃ³n'),(5626,357,'Corregidora'),(5627,357,'Ezequiel Montes'),(5628,357,'Huimilpan'),(5629,357,'Jalpan de Serra'),(5630,357,'Landa de Matamoros'),(5631,357,'El MarquÃ©s'),(5632,357,'Pedro Escobedo'),(5633,357,'PeÃ±amiller'),(5634,357,'QuerÃ©taro'),(5635,357,'San JoaquÃ­n'),(5636,357,'San Juan del RÃ­o'),(5637,357,'Tequisquiapan'),(5638,357,'TolimÃ¡n'),(5639,199,'Ahualulco'),(5640,199,'Alaquines'),(5641,199,'AquismÃ³n'),(5642,199,'Armadillo de los Infante'),(5643,199,'Axtla de Terrazas'),(5644,199,'CÃ¡rdenas'),(5645,199,'Catorce'),(5646,199,'Cedral'),(5647,199,'Cerritos'),(5648,199,'Cerro de San Pedro'),(5649,199,'Charcas'),(5650,199,'Ciudad del MaÃ­z'),(5651,199,'Ciudad FernÃ¡ndez'),(5652,199,'Ciudad Valles'),(5653,199,'CoxcatlÃ¡n'),(5654,199,'Ebano'),(5655,199,'El Naranjo'),(5656,199,'Guadalcazar'),(5657,199,'HuehuetlÃ¡n'),(5658,199,'Lagunillas'),(5659,199,'Matehuala'),(5660,199,'Matlapa'),(5661,199,'Mexquitic de Carmona'),(5662,199,'Moctezuma'),(5663,199,'RayÃ³n'),(5664,199,'Rioverde'),(5665,199,'Salinas'),(5666,199,'San Antonio'),(5667,199,'San Ciro de Acosta'),(5668,199,'San Luis PotosÃ­'),(5669,199,'San MartÃ­n Chalchicuautla'),(5670,199,'San NicolÃ¡s Tolentino'),(5671,199,'Santa Catarina'),(5672,199,'Santa MarÃ­a del RÃ­o'),(5673,199,'Santo Domingo'),(5674,199,'San Vicente Tancuayalab'),(5675,199,'Soledad de Graciano SÃ¡nchez'),(5676,199,'Tamasopo'),(5677,199,'Tamazunchale'),(5678,199,'Tampacan'),(5679,199,'TampamolÃ³n Corona'),(5680,199,'TamuÃ­n'),(5681,199,'Tancanhuitz de Santos'),(5682,199,'TanlajÃ¡s'),(5683,199,'TanquiÃ¡n de Escobedo'),(5684,199,'Tierra Nueva'),(5685,199,'Vanegas'),(5686,199,'Venado'),(5687,199,'Villa de Arista'),(5688,199,'Villa de Arriaga'),(5689,199,'Villa de Guadalupe'),(5690,199,'Villa de La Paz'),(5691,199,'Villa de Ramos'),(5692,199,'Villa de Reyes'),(5693,199,'Villa de Hidalgo'),(5694,199,'Villa JuÃ¡rez'),(5695,199,'Xilitla'),(5696,199,'Zaragoza'),(5697,400,'AbalÃ¡'),(5698,400,'Acanceh'),(5699,400,'Akil'),(5700,400,'Baca'),(5701,400,'BokobÃ¡'),(5702,400,'Buctzotz'),(5703,400,'CacalchÃ©n'),(5704,400,'Calotmul'),(5705,400,'Cansahcab'),(5706,400,'Cantamayec'),(5707,400,'CelestÃºn'),(5708,400,'Cenotillo'),(5709,400,'Conkal'),(5710,400,'Cuncunul'),(5711,400,'CuzamÃ¡'),(5712,400,'ChacsinkÃ­n'),(5713,400,'Chankom'),(5714,400,'Chapab'),(5715,400,'Chemax'),(5716,400,'Chicxulub Pueblo'),(5717,400,'ChichimilÃ¡'),(5718,400,'Chikindzonot'),(5719,400,'ChocholÃ¡'),(5720,400,'Chumayel'),(5721,400,'Dzan'),(5722,400,'Dzemul'),(5723,400,'DzidzantÃºn'),(5724,400,'Dzilam de Bravo'),(5725,400,'Dzilam GonzÃ¡lez'),(5726,400,'DzitÃ¡s'),(5727,400,'Dzoncauich'),(5728,400,'Espita'),(5729,400,'HalachÃ³'),(5730,400,'HocabÃ¡'),(5731,400,'HoctÃºn'),(5732,400,'HomÃºn'),(5733,400,'HuhÃ­'),(5734,400,'HunucmÃ¡'),(5735,400,'Ixil'),(5736,400,'Izamal'),(5737,400,'KanasÃ­n'),(5738,400,'Kantunil'),(5739,400,'Kaua'),(5740,400,'Kinchil'),(5741,400,'KopomÃ¡'),(5742,400,'Mama'),(5743,400,'ManÃ­'),(5744,400,'MaxcanÃº'),(5745,400,'MayapÃ¡n'),(5746,400,'MÃ©rida'),(5747,400,'MocochÃ¡'),(5748,400,'Motul'),(5749,400,'Muna'),(5750,400,'Muxupip'),(5751,400,'OpichÃ©n'),(5752,400,'Oxkutzcab'),(5753,400,'PanabÃ¡'),(5754,400,'Peto'),(5755,400,'Progreso'),(5756,400,'Quintana Roo'),(5757,400,'RÃ­o Lagartos'),(5758,400,'Sacalum'),(5759,400,'Samahil'),(5760,400,'Sanahcat'),(5761,400,'San Felipe'),(5762,400,'Santa Elena'),(5763,400,'SeyÃ©'),(5764,400,'SinanchÃ©'),(5765,400,'Sotuta'),(5766,400,'SucilÃ¡'),(5767,400,'Sudzal'),(5768,400,'Suma'),(5769,400,'TahdziÃº'),(5770,400,'Tahmek'),(5771,400,'Teabo'),(5772,400,'Tecoh'),(5773,400,'Tekal de Venegas'),(5774,400,'TekantÃ³'),(5775,400,'Tekax'),(5776,400,'Tekit'),(5777,400,'Tekom'),(5778,400,'Telchac Pueblo'),(5779,400,'Telchac Puerto'),(5780,400,'Temax'),(5781,400,'TemozÃ³n'),(5782,400,'TepakÃ¡n'),(5783,400,'Tetiz'),(5784,400,'Teya'),(5785,400,'Ticul'),(5786,400,'Timucuy'),(5787,400,'TinÃºm'),(5788,400,'Tixcacalcupul'),(5789,400,'Tixkokob'),(5790,400,'TixmÃ©huac'),(5791,400,'TixpÃ©hual'),(5792,400,'TizimÃ­n'),(5793,400,'TunkÃ¡s'),(5794,400,'Tzucacab'),(5795,400,'Uayma'),(5796,400,'UcÃº'),(5797,400,'UmÃ¡n'),(5798,400,'Valladolid'),(5799,400,'Xocchel'),(5800,400,'YaxcabÃ¡'),(5801,400,'Yaxkukul'),(5802,400,'YobaÃ­n'),(5803,140,'Miller');

