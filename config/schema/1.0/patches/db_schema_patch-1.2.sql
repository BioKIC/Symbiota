INSERT IGNORE INTO schemaversion (versionnumber) values ("1.2");

# Modifications to langauge support table
ALTER TABLE `adminlanguages` 
  ADD COLUMN `ISO 639-3` VARCHAR(3) NULL AFTER `iso639_2`;

# Modifications to agent tables
ALTER TABLE `agents` 
  DROP FOREIGN KEY `FK_preferred_recby`;

ALTER TABLE `agents` 
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() AFTER `modifiedUid`,
  CHANGE COLUMN `agentid` `agentID` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `familyname` `familyName` VARCHAR(45) NOT NULL ,
  CHANGE COLUMN `firstname` `firstName` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `middlename` `middleName` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `startyearactive` `startYearActive` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `endyearactive` `endYearActive` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `preferredrecbyid` `preferredRecByID` BIGINT(20) NULL DEFAULT NULL ,
  CHANGE COLUMN `namestring` `nameString` TEXT NULL DEFAULT NULL ,
  CHANGE COLUMN `yearofbirth` `yearOfBirth` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `yearofbirthmodifier` `yearOfBirthModifier` VARCHAR(12) NULL DEFAULT '' ,
  CHANGE COLUMN `yearofdeath` `yearOfDeath` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `yearofdeathmodifier` `yearOfDeathModifier` VARCHAR(12) NULL DEFAULT '' ,
  CHANGE COLUMN `uuid` `recordID` CHAR(43) NULL DEFAULT NULL AFTER `living`,
  CHANGE COLUMN `datelastmodified` `dateLastModified` DATETIME NULL DEFAULT NULL ,
  CHANGE COLUMN `lastmodifiedbyuid` `modifiedUid` INT(11) UNSIGNED NULL DEFAULT NULL ;

ALTER TABLE `agents` 
  ADD CONSTRAINT `FK_preferred_recby`  FOREIGN KEY (`preferredRecByID`)  REFERENCES `agents` (`agentID`)  ON DELETE NO ACTION  ON UPDATE CASCADE;

ALTER TABLE `agents` 
  DROP FOREIGN KEY `FK_preferred_recby`;

ALTER TABLE `agents` 
  ADD COLUMN `createdUid` INT UNSIGNED NULL AFTER `modifiedUid`,
  DROP INDEX `FK_preferred_recby` ,
  ADD INDEX `FK_agents_preferred_recby` (`preferredRecByID` ASC),
  ADD INDEX `FK_agents_modUid_idx` (`modifiedUid` ASC),
  ADD INDEX `FK_agents_createdUid_idx` (`createdUid` ASC);

ALTER TABLE `agents` 
  ADD CONSTRAINT `FK_agents_preferred_recby`  FOREIGN KEY (`preferredRecByID`)  REFERENCES `agents` (`agentID`)  ON DELETE NO ACTION  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_agents_modUid`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_agents_createdUid`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;
  
ALTER TABLE `agentlinks` 
  CHANGE COLUMN `lastmodifiedbyuid` `modifiedUid` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `createdUid`,
  CHANGE COLUMN `timestampcreated` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() AFTER `dateLastModified`,
  CHANGE COLUMN `agentlinksid` `agentLinksID` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `agentid` `agentID` BIGINT NOT NULL ,
  CHANGE COLUMN `createdbyuid` `createdUid` INT(11) UNSIGNED NOT NULL ,
  CHANGE COLUMN `datelastmodified` `dateLastModified` DATETIME NULL DEFAULT NULL ;

ALTER TABLE `agentlinks` 
  ADD INDEX `FK_agentlinks_agentID_idx` (`agentID` ASC);

ALTER TABLE `agentlinks` 
  ADD CONSTRAINT `FK_agentlinks_agentID`  FOREIGN KEY (`agentID`)  REFERENCES `agents` (`agentID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `agentlinks`
  CHANGE COLUMN `createdUid` `createdUid` INT(11) UNSIGNED NULL ,
  ADD INDEX `FK_agentlinks_modUid_idx` (`modifiedUid` ASC),
  ADD INDEX `FK_agentlinks_createdUid_idx` (`createdUid` ASC);
  
ALTER TABLE `agentlinks` 
  ADD CONSTRAINT `FK_agentlinks_modUid`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_agentlinks_createdUid`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `agentnames` 
  CHANGE COLUMN `lastmodifiedbyuid` `modifiedUid` INT(11) NULL DEFAULT NULL AFTER `createdUid`,
  CHANGE COLUMN `timestampcreated` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() AFTER `dateLastModified`,
  CHANGE COLUMN `agentnamesid` `agentNamesID` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `agentid` `agentID` INT(11) NOT NULL ,
  CHANGE COLUMN `createdbyuid` `createdUid` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `datelastmodified` `dateLastModified` DATETIME NULL DEFAULT NULL ;

ALTER TABLE `agentteams` 
  DROP FOREIGN KEY `agentteams_ibfk_1`,
  DROP FOREIGN KEY `agentteams_ibfk_2`;

ALTER TABLE `agentteams` 
  CHANGE COLUMN `agentteamid` `agentTeamID` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `teamagentid` `teamAgentID` BIGINT(20) NOT NULL ,
  CHANGE COLUMN `memberagentid` `memberAgentID` BIGINT(20) NOT NULL ;
  
ALTER TABLE `agentteams` 
  ADD CONSTRAINT `agentteams_ibfk_1`  FOREIGN KEY (`teamAgentID`)  REFERENCES `agents` (`agentID`)  ON DELETE NO ACTION  ON UPDATE CASCADE,
  ADD CONSTRAINT `agentteams_ibfk_2`  FOREIGN KEY (`memberAgentID`)  REFERENCES `agents` (`agentID`)  ON DELETE NO ACTION  ON UPDATE CASCADE;

ALTER TABLE `agentsfulltext` 
  CHANGE COLUMN `agentsfulltextid` `agentsFulltextID` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `agentid` `agentID` INT(11) NOT NULL ,
  CHANGE COLUMN `taxonomicgroups` `taxonomicGroups` TEXT NULL DEFAULT NULL ,
  CHANGE COLUMN `collectionsat` `collectionsAt` TEXT NULL DEFAULT NULL ;

ALTER TABLE `agentrelations` 
  DROP FOREIGN KEY `agentrelations_ibfk_1`,
  DROP FOREIGN KEY `agentrelations_ibfk_2`,
  DROP FOREIGN KEY `agentrelations_ibfk_3`;
  
ALTER TABLE `agentrelations` 
  CHANGE COLUMN `timestampcreated` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() AFTER `modifiedUid`,
  CHANGE COLUMN `agentrelationsid` `agentRelationsID` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `fromagentid` `fromAgentID` BIGINT(20) NOT NULL ,
  CHANGE COLUMN `toagentid` `toAgentID` BIGINT(20) NOT NULL ,
  CHANGE COLUMN `createdbyuid` `createdUid` INT(11) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `datelastmodified` `dateLastModified` DATETIME NULL DEFAULT NULL ,
  CHANGE COLUMN `lastmodifiedbyuid` `modifiedUid` INT(11) UNSIGNED NULL DEFAULT NULL ;

ALTER TABLE `agentrelations` 
  ADD CONSTRAINT `FK_agentrelations_ibfk_1`  FOREIGN KEY (`fromAgentID`)  REFERENCES `agents` (`agentID`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_agentrelations_ibfk_2`  FOREIGN KEY (`toAgentID`)  REFERENCES `agents` (`agentID`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_agentrelations_ibfk_3`  FOREIGN KEY (`relationship`)  REFERENCES `ctrelationshiptypes` (`relationship`)  ON DELETE NO ACTION  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_agentrelations_createUid`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_agentrelations_modUid`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;
  
ALTER TABLE `agentrelations` 
  ADD INDEX `FK_agentrelations_modUid_idx` (`modifiedUid` ASC),
  ADD INDEX `FK_agentrelations_createUid_idx` (`createdUid` ASC);

ALTER TABLE `agentnumberpattern` 
  DROP FOREIGN KEY `agentnumberpattern_ibfk_1`;
  
ALTER TABLE `agentnumberpattern` 
  CHANGE COLUMN `agentnumberpatternid` `agentNumberPatternID` BIGINT(20) NOT NULL ,
  CHANGE COLUMN `agentid` `agentID` BIGINT(20) NOT NULL ,
  CHANGE COLUMN `numbertype` `numberType` VARCHAR(50) NULL DEFAULT 'Collector number' ,
  CHANGE COLUMN `numberpattern` `numberPattern` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `numberpatterndescription` `numberPatternDescription` VARCHAR(900) NULL DEFAULT NULL ,
  CHANGE COLUMN `startyear` `startYear` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `endyear` `endYear` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `integerincrement` `integerIncrement` INT(11) NULL DEFAULT NULL ;
  
ALTER TABLE `agentnumberpattern` 
  ADD CONSTRAINT `agentnumberpattern_ibfk_1`  FOREIGN KEY (`agentID`)  REFERENCES `agents` (`agentID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

# Modifications geographic thesaurus tables
CREATE TABLE `geographicthesaurus` (
  `geoThesID` INT NOT NULL AUTO_INCREMENT,
  `geoterm` VARCHAR(100) NULL,
  `abbreviation` VARCHAR(45) NULL,
  `iso2` VARCHAR(45) NULL,
  `iso3` VARCHAR(45) NULL,
  `numcode` INT NULL,
  `category` VARCHAR(45) NULL,
  `termstatus` INT NULL,
  `acceptedID` INT NULL,
  `parentID` INT NULL,
  `notes` VARCHAR(250) NULL,
  `dynamicProps` TEXT NULL,
  `footprintWKT` TEXT NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`geoThesID`),
  INDEX `IX_geothes_termname` (`geoterm` ASC),
  INDEX `IX_geothes_abbreviation` (`abbreviation` ASC),
  INDEX `IX_geothes_iso2` (`iso2` ASC),
  INDEX `IX_geothes_iso3` (`iso3` ASC));

ALTER TABLE `geographicthesaurus` 
  ADD INDEX `FK_geothes_acceptedID_idx` (`acceptedID` ASC),
  ADD INDEX `FK_geothes_parentID_idx` (`parentID` ASC);

ALTER TABLE `geographicthesaurus` 
  ADD CONSTRAINT `FK_geothes_acceptedID`  FOREIGN KEY (`acceptedID`)  REFERENCES `geographicthesaurus` (`geoThesID`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_geothes_parentID`  FOREIGN KEY (`parentID`)  REFERENCES `geographicthesaurus` (`geoThesID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

CREATE TABLE `geographicpolygon` (
  `geoThesID` INT NOT NULL,
  `footprintPolygon` POLYGON NOT NULL,
  `footprintWKT` LONGTEXT NULL,
  `geoJSON` LONGTEXT NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`geoThesID`),
  SPATIAL INDEX `IX_geopoly_polygon` (`footprintPolygon` ASC))
  ENGINE = MyISAM;

ALTER TABLE `lkupstateprovince` 
  CHANGE COLUMN `abbrev` `abbrev` VARCHAR(3) NULL DEFAULT NULL ;

# Modifications to checklist table
ALTER TABLE `fmprojects` 
  CHANGE COLUMN `fulldescription` `fulldescription` VARCHAR(5000) NULL DEFAULT NULL ;

ALTER TABLE `fmchecklists` 
  ADD COLUMN `cidKeyLimits` VARCHAR(250) NULL AFTER `Access`;

ALTER TABLE `fmchklstprojlink` 
   ADD COLUMN `sortSequence` INT NULL AFTER `mapChecklist`;

ALTER TABLE `fmchklsttaxalink` 
  ADD INDEX `FK_chklsttaxalink_tid` (`TID` ASC);

# Modifications to identification key tables
ALTER TABLE `kmcharacters` 
  CHANGE COLUMN `helpurl` `helpurl` VARCHAR(500) NULL DEFAULT NULL AFTER `description`,
  ADD COLUMN `referenceUrl` VARCHAR(250) NULL AFTER `helpurl`;

ALTER TABLE `kmcharacters` 
  CHANGE COLUMN `sortsequence` `sortsequence` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `notes`,
  ADD COLUMN `glossid` INT UNSIGNED NULL AFTER `description`,
  ADD INDEX `FK_kmchar_glossary_idx` (`glossid` ASC);

ALTER TABLE `kmcharacters` 
  ADD CONSTRAINT `FK_kmchar_glossary`  FOREIGN KEY (`glossid`)  REFERENCES `glossary` (`glossid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `kmcharacters`
  ADD COLUMN `activationCode` INT NULL AFTER `notes`;

ALTER TABLE `kmcharacterlang` 
  CHANGE COLUMN `language` `language` VARCHAR(45) NULL ;

ALTER TABLE `kmcharheading` 
  CHANGE COLUMN `language` `language` VARCHAR(45) NULL DEFAULT 'English' ;

CREATE TABLE `kmcharheadinglang` (
  `hid` INT UNSIGNED NOT NULL,
  `langid` INT NOT NULL,
  `headingname` VARCHAR(100) NOT NULL,
  `notes` VARCHAR(250) NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`hid`, `langid`),
  CONSTRAINT `FK_kmcharheadinglang_hid`  FOREIGN KEY (`hid`)  REFERENCES `kmcharheading` (`hid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_kmcharheadinglang_langid`  FOREIGN KEY (`langid`)  REFERENCES `adminlanguages` (`langid`)  ON DELETE CASCADE  ON UPDATE CASCADE
);

ALTER TABLE `kmcs` 
  ADD COLUMN `referenceUrl` VARCHAR(250) NULL AFTER `IllustrationUrl`;

ALTER TABLE `kmcs` 
  ADD COLUMN `glossid` INT UNSIGNED NULL AFTER `referenceUrl`,
  ADD INDEX `FK_kmcs_glossid_idx` (`glossid` ASC);

ALTER TABLE `kmcs` 
  ADD CONSTRAINT `FK_kmcs_glossid`  FOREIGN KEY (`glossid`)  REFERENCES `glossary` (`glossid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

# Addition of controlled vocabulary support tables
CREATE TABLE `ctcontrolvocab` (
  `cvID` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(45) DEFAULT NULL,
  `definition` VARCHAR(250) DEFAULT NULL,
  `authors` VARCHAR(150) DEFAULT NULL,
  `tableName` VARCHAR(45) DEFAULT NULL,
  `fieldName` VARCHAR(45) DEFAULT NULL,
  `resourceUrl` VARCHAR(150) DEFAULT NULL,
  `ontologyClass` VARCHAR(150) DEFAULT NULL,
  `ontologyUrl` VARCHAR(150) DEFAULT NULL,
  `limitToList` INT(2) DEFAULT 0,
  `dynamicProperties` TEXT DEFAULT NULL,
  `notes` VARCHAR(45) DEFAULT NULL,
  `createdUid` INT(10) unsigned DEFAULT NULL,
  `modifiedUid` INT(10) unsigned DEFAULT NULL,
  `modifiedTimestamp` DATETIME DEFAULT NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cvID`),
  KEY `FK_ctControlVocab_createUid_idx` (`createdUid`),
  KEY `FK_ctControlVocab_modUid_idx` (`modifiedUid`),
  CONSTRAINT `FK_ctControlVocab_createUid` FOREIGN KEY (`createdUid`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_ctControlVocab_modUid` FOREIGN KEY (`modifiedUid`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE `ctcontrolvocabterm` (
  `cvTermID` INT NOT NULL AUTO_INCREMENT,
  `cvID` INT NOT NULL,
  `parentCvTermID` INT NULL,
  `term` VARCHAR(45) NOT NULL,
  `inverseRelationship` VARCHAR(45) NULL,
  `collective` VARCHAR(45) NULL,
  `definition` VARCHAR(250) DEFAULT NULL,
  `resourceUrl` VARCHAR(150) DEFAULT NULL,
  `ontologyClass` VARCHAR(150) DEFAULT NULL,
  `ontologyUrl` VARCHAR(150) DEFAULT NULL,
  `notes` VARCHAR(250) NULL,
  `createdUid` INT UNSIGNED NULL,
  `modifiedUid` INT UNSIGNED NULL,
  `modifiedTimestamp` DATETIME NULL,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`cvTermID`),
  INDEX `FK_ctcontrolVocabTerm_cvID_idx` (`cvID` ASC),
  INDEX `FK_ctControlVocabTerm_createUid_idx` (`createdUid` ASC),
  INDEX `FK_ctControlVocabTerm_modUid_idx` (`modifiedUid` ASC),
  INDEX `IX_controlVocabTerm_term` (`term` ASC),
  UNIQUE INDEX `UQ_controlVocabTerm` (`cvID` ASC, `term` ASC),
  CONSTRAINT `FK_ctControlVocabTerm_cvID`
    FOREIGN KEY (`cvID`) REFERENCES `ctcontrolvocab` (`cvID`)  ON DELETE CASCADE   ON UPDATE CASCADE,
  CONSTRAINT `FK_ctControlVocabTerm_createUid`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  CONSTRAINT `FK_ctControlVocabTerm_modUid`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  CONSTRAINT `FK_ctControlVocabTerm_cvTermID`  FOREIGN KEY (`parentCvTermID`)  REFERENCES `ctcontrolvocabterm` (`cvTermID`)  ON DELETE SET NULL  ON UPDATE CASCADE
);

INSERT INTO `ctcontrolvocab` VALUES (1,'Occurrence Relationship Terms',NULL,NULL,'omoccurassociations','relationship',NULL,NULL,NULL,1,NULL,NULL,null,NULL,NULL,'2020-12-02 21:35:38'),(2,'Occurrence Relationship subTypes',NULL,NULL,'omoccurassociations','subType',NULL,NULL,NULL,0,NULL,NULL,null,NULL,NULL,'2020-12-02 22:56:13');

INSERT INTO `ctcontrolvocabterm` VALUES (1,1,NULL,'subsampleOf','originatingSampleOf',NULL,'a sample or occurrence that was subsequently derived from an originating sample',NULL,'has part: http://purl.obolibrary.org/obo/BFO_0000050',NULL,NULL,null,NULL,NULL,'2020-12-02 21:36:51'),(2,1,NULL,'partOf','partOf',NULL,NULL,NULL,NULL,NULL,NULL,null,NULL,NULL,'2020-12-02 21:38:32'),(3,1,NULL,'siblingOf','siblingOf',NULL,NULL,NULL,NULL,NULL,NULL,null,NULL,NULL,'2020-12-02 21:38:32'),(4,1,NULL,'originatingSampleOf','subsampleOf',NULL,'a sample or occurrence that is the originator of a subsequently modified or partial sample',NULL,'partOf: http://purl.obolibrary.org/obo/BFO_0000051',NULL,'originatingSourceOf ??  It isn\'t necessarily a sample.  Could be an observation or occurrence or individual etc',null,NULL,NULL,'2020-12-02 23:27:02'),(5,1,NULL,'sharesOriginatingSample','sharesOriginatingSample',NULL,'two samples or occurrences that were subsequently derived from the same originating sample',NULL,NULL,NULL,NULL,null,NULL,NULL,'2020-12-02 23:44:23'),(6,2,NULL,'tissue',NULL,NULL,'a tissue sample or occurrence that was subsequently derived from an originating sample',NULL,'partOf: http://purl.obolibrary.org/obo/BFO_0000051',NULL,NULL,null,NULL,NULL,'2020-12-02 23:44:23'),(7,2,NULL,'blood',NULL,NULL,'a blood-tissue sample or occurrence that was subsequently derived from an originating sample',NULL,'partOf: http://purl.obolibrary.org/obo/BFO_0000051',NULL,NULL,null,NULL,NULL,'2020-12-02 23:44:23'),(8,2,NULL,'fecal',NULL,NULL,'a fecal sample or occurrence that was subsequently derived from an originating sample',NULL,'partOf: http://purl.obolibrary.org/obo/BFO_0000051',NULL,NULL,null,NULL,NULL,'2020-12-02 23:44:23'),(9,2,NULL,'hair',NULL,NULL,'a hair sample or occurrence that was subsequently derived from an originating sample',NULL,'partOf: http://purl.obolibrary.org/obo/BFO_0000051',NULL,NULL,null,NULL,NULL,'2020-12-02 23:44:23'),(10,2,NULL,'genetic',NULL,NULL,'a genetic extraction sample or occurrence that was subsequently derived from an originating sample',NULL,'partOf: http://purl.obolibrary.org/obo/BFO_0000051',NULL,NULL,null,NULL,NULL,'2020-12-02 23:44:23'),(11,1,NULL,'derivedFromSameIndividual','derivedFromSameIndividual',NULL,'a sample or occurrence that is derived from the same biological individual as another occurrence or sample',NULL,'partOf: http://purl.obolibrary.org/obo/BFO_0000051',NULL,NULL,null,NULL,NULL,'2020-12-02 23:48:45'),(12,1,NULL,'analyticalStandardOf','hasAnalyticalStandard',NULL,'a sample or occurrence that serves as an analytical standard or control for another occurrence or sample',NULL,NULL,NULL,NULL,null,NULL,NULL,'2020-12-02 23:48:45'),(13,1,NULL,'hasAnalyticalStandard','analyticalStandardof',NULL,'a sample or occurrence that has an available analytical standard or control',NULL,NULL,NULL,NULL,null,NULL,NULL,'2020-12-02 23:48:45'),(14,1,NULL,'hasHost','hostOf',NULL,'X \'has host\' y if and only if: x is an organism, y is an organism, and x can live on the surface of or within the body of y',NULL,'ecologically related to: http://purl.obolibrary.org/obo/RO_0008506','http://purl.obolibrary.org/obo/RO_0002454',NULL,null,NULL,NULL,'2020-12-02 23:58:18'),(15,1,NULL,'hostOf','hasHost',NULL,'X is \'Host of\' y if and only if: x is an organism, y is an organism, and y can live on the surface of or within the body of x',NULL,'ecologically related to: http://purl.obolibrary.org/obo/RO_0008506','http://purl.obolibrary.org/obo/RO_0002453',NULL,null,NULL,NULL,'2020-12-02 23:58:18'),(16,1,NULL,'ecologicallyOccursWith','ecologicallyOccursWith',NULL,'An interaction relationship describing an occurrence occurring with another organism in the same time and space or same environment',NULL,'ecologically related to: http://purl.obolibrary.org/obo/RO_0008506','http://purl.obolibrary.org/obo/RO_0008506',NULL,null,NULL,NULL,'2020-12-02 23:58:18');

ALTER TABLE `ctcontrolvocabterm` 
  ADD COLUMN `termDisplay` VARCHAR(75) NULL AFTER `term`,
  ADD COLUMN `activeStatus` INT NULL DEFAULT 1 AFTER `ontologyUrl`;


ALTER TABLE `glossary` 
  ADD COLUMN `langid` INT UNSIGNED NULL AFTER `language`;

ALTER TABLE `glossaryimages` 
  ADD COLUMN `sortSequence` INT NULL AFTER `structures`;


ALTER TABLE `images` 
  CHANGE COLUMN `url` `url` VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE `images` 
  ADD INDEX `Index_images_datelastmod` (`InitialTimeStamp` ASC);

ALTER TABLE `images` 
  ADD COLUMN `sortOccurrence` INT NULL DEFAULT 5 AFTER `sortsequence`;

ALTER TABLE `images` 
  ADD COLUMN `defaultDisplay` INT NULL AFTER `dynamicProperties`;


ALTER TABLE `omexsiccatititles` 
  ADD COLUMN `sourceIdentifier` VARCHAR(150) NULL AFTER `source`,
  ADD COLUMN `recordID` VARCHAR(45) NULL AFTER `lasteditedby`;


ALTER TABLE `referenceobject` 
  CHANGE COLUMN `cheatauthors` `cheatauthors` VARCHAR(400) NULL DEFAULT NULL ,
  CHANGE COLUMN `cheatcitation` `cheatcitation` VARCHAR(500) NULL DEFAULT NULL ;


ALTER TABLE `taxa` 
  ADD COLUMN `reviewStatus` INT NULL AFTER `PhyloSortSequence`,
  ADD COLUMN `displayStatus` INT NULL AFTER `reviewStatus`,
  ADD COLUMN `isLegitimate` INT NULL AFTER `displayStatus`,
  ADD COLUMN `nomenclaturalStatus` VARCHAR(45) NULL AFTER `isLegitimate`,
  ADD COLUMN `nomenclaturalCode` VARCHAR(45) NULL AFTER `nomenclaturalStatus`,
  CHANGE COLUMN `UnitInd3` `unitInd3` VARCHAR(45) NULL DEFAULT NULL,
  CHANGE COLUMN `Status` `statusNotes` VARCHAR(50) NULL DEFAULT NULL ;

ALTER TABLE `taxstatus`
  ADD COLUMN `taxonomicStatus` VARCHAR(45) NULL AFTER `family`,
  ADD COLUMN `taxonomicSource` VARCHAR(45) NULL AFTER `taxonomicStatus`,
  ADD COLUMN `sourceIdentifier` VARCHAR(150) NULL AFTER `taxonomicSource`,
  ADD COLUMN `modifiedUid` INT UNSIGNED NULL AFTER `SortSequence`,
  ADD COLUMN `modifiedTimestamp` DATETIME NULL AFTER `modifiedUid`,
  ADD INDEX `FK_taxstatus_uid_idx` (`modifiedUid` ASC);

ALTER TABLE `taxstatus` 
  ADD CONSTRAINT `FK_taxstatus_uid`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE SET NULL;

ALTER TABLE `taxstatus` 
  DROP INDEX `Index_hierarchy`;

ALTER TABLE `taxstatus` 
  DROP COLUMN `hierarchystr`;

#ALTER TABLE `taxstatus` 
#  DROP PRIMARY KEY,
#  ADD PRIMARY KEY USING BTREE (`tid`, `taxauthid`);

ALTER TABLE `taxstatus` 
  ADD INDEX `Index_tid` (`tid` ASC);

ALTER TABLE `taxstatus` 
  DROP FOREIGN KEY `FK_taxstatus_tidacc`;
  
#ALTER TABLE `taxstatus` 
#  CHANGE COLUMN `tidaccepted` `tidaccepted` INT(10) UNSIGNED NULL DEFAULT NULL ;
  
ALTER TABLE `taxstatus` 
  ADD CONSTRAINT `FK_taxstatus_tidacc` FOREIGN KEY (`tidaccepted`)  REFERENCES `taxa` (`TID`);

UPDATE taxavernaculars v INNER JOIN adminlanguages l ON v.language = l.langname 
SET v.langid = l.langid
WHERE v.langid IS NULL;

UPDATE taxavernaculars v INNER JOIN adminlanguages l ON v.language = l.iso639_1 
SET v.langid = l.langid
WHERE v.langid IS NULL;

UPDATE taxavernaculars v INNER JOIN adminlanguages l ON v.language = l.iso639_2 
SET v.langid = l.langid
WHERE v.langid IS NULL;

ALTER TABLE `taxavernaculars` 
  CHANGE COLUMN `Language` `Language` VARCHAR(15) NULL ,
  DROP INDEX `unique-key` ,
  ADD UNIQUE INDEX `unique-key` (`VernacularName` ASC, `TID` ASC, `langid` ASC);

ALTER TABLE `taxaresourcelinks` 
  ADD UNIQUE INDEX `UNIQUE_taxaresource` (`tid` ASC, `sourcename` ASC);

ALTER TABLE `taxadescrblock` 
  DROP FOREIGN KEY `FK_taxadescrblock_tid`;

ALTER TABLE `taxadescrblock` 
  DROP INDEX `Index_unique` ;

ALTER TABLE `taxadescrblock` 
  ADD INDEX `FK_taxadescrblock_tid_idx` (`tid` ASC);

ALTER TABLE `taxadescrblock` 
  ADD CONSTRAINT `FK_taxadescrblock_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)  ON DELETE RESTRICT  ON UPDATE CASCADE;

ALTER TABLE `taxadescrstmts` 
  CHANGE COLUMN `heading` `heading` VARCHAR(75) NULL ;


#Paleo tables
CREATE TABLE `omoccurpaleo` (
  `paleoID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `occid` INT UNSIGNED NOT NULL,
  `eon` VARCHAR(65) NULL,
  `era` VARCHAR(65) NULL,
  `period` VARCHAR(65) NULL,
  `epoch` VARCHAR(65) NULL,
  `earlyInterval` VARCHAR(65) NULL,
  `lateInterval` VARCHAR(65) NULL,
  `absoluteAge` VARCHAR(65) NULL,
  `storageAge` VARCHAR(65) NULL,
  `stage` VARCHAR(65) NULL,
  `localStage` VARCHAR(65) NULL,
  `biota` VARCHAR(65) NULL COMMENT 'Flora or Fanua',
  `biostratigraphy` VARCHAR(65) NULL COMMENT 'Biozone',
  `taxonEnvironment` VARCHAR(65) NULL COMMENT 'Marine or not',
  `lithogroup` VARCHAR(65) NULL,
  `formation` VARCHAR(65) NULL,
  `member` VARCHAR(65) NULL,
  `bed` VARCHAR(65) NULL,
  `lithology` VARCHAR(250) NULL,
  `stratRemarks` VARCHAR(250) NULL,
  `element` VARCHAR(250) NULL,
  `slideProperties` VARCHAR(1000) NULL,
  `geologicalContextID` VARCHAR(45) NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`paleoID`),
  INDEX `FK_paleo_occid_idx` (`occid` ASC),
  UNIQUE INDEX `UNIQUE_occid` (`occid` ASC),
  CONSTRAINT `FK_paleo_occid`  FOREIGN KEY (`occid`)  REFERENCES `omoccurrences` (`occid`)  ON DELETE CASCADE  ON UPDATE CASCADE
) COMMENT = 'Occurrence Paleo tables';


#ALTER TABLE `omoccurpaleo` 
#  ADD COLUMN `geologicalContextID` VARCHAR(45) NULL AFTER `slideProperties`;

#ALTER TABLE `omoccurpaleo` 
#  CHANGE COLUMN `taxonEnvironment` `taxonEnvironment` VARCHAR(65) NULL DEFAULT NULL COMMENT 'Marine or not' AFTER `biostratigraphy`,
#  CHANGE COLUMN `lithology` `lithology` VARCHAR(250) NULL DEFAULT NULL ,
#  ADD COLUMN `bed` VARCHAR(65) NULL AFTER `member`;

#ALTER TABLE `uploadspectemp` 
#DROP COLUMN `bed`,
#DROP COLUMN `member`,
#DROP COLUMN `formation`,
#DROP COLUMN `lithogroup`,
#DROP COLUMN `lithostratigraphicTermsProperty`,
#DROP COLUMN `highestBiostratigraphicZone`,
#DROP COLUMN `lowestBiostratigraphicZone`,
#DROP COLUMN `latestAgeOrHighestStage`,
#DROP COLUMN `earliestAgeOrLowestStage`,
#DROP COLUMN `latestEpochOrHighestSeries`,
#DROP COLUMN `earliestEpochOrLowestSeries`,
#DROP COLUMN `latestPeriodOrHighestSystem`,
#DROP COLUMN `earliestPeriodOrLowestSystem`,
#DROP COLUMN `latestEraOrHighestErathem`,
#DROP COLUMN `earliestEraOrLowestErathem`,
#DROP COLUMN `latestEonOrHighestEonothem`,
#DROP COLUMN `earliestEonOrLowestEonothem`;


CREATE TABLE `omoccurpaleogts` (
  `gtsid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gtsterm` VARCHAR(45) NOT NULL,
  `rankid` INT NOT NULL,
  `rankname` VARCHAR(45) NULL,
  `parentgtsid` INT UNSIGNED NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  UNIQUE INDEX  `UNIQUE_gtsterm` (`gtsid` ASC),
  PRIMARY KEY (`gtsid`)
);

ALTER TABLE `omoccurpaleogts` 
  ADD INDEX `FK_gtsparent_idx` (`parentgtsid` ASC);

ALTER TABLE `omoccurpaleogts` 
  ADD CONSTRAINT `FK_gtsparent` FOREIGN KEY (`parentgtsid`)  REFERENCES `omoccurpaleogts` (`gtsid`)  ON DELETE NO ACTION  ON UPDATE CASCADE;

INSERT INTO `omoccurpaleogts` (`gtsterm`, `rankid`, `rankname`) VALUES ('Precambrian', 10, 'supereon');

INSERT INTO omoccurpaleogts(gtsterm,rankid,rankname,parentgtsid) SELECT DISTINCT eon, 20, "eon", 1 FROM paleochronostratigraphy;

INSERT INTO omoccurpaleogts(gtsterm,rankid,rankname,parentgtsid)
  SELECT DISTINCT era, 30, "era", g.gtsid FROM paleochronostratigraphy p INNER JOIN omoccurpaleogts g ON p.eon = g.gtsterm WHERE era IS NOT NULL;

INSERT INTO omoccurpaleogts(gtsterm,rankid,rankname,parentgtsid)
  SELECT DISTINCT period, 40, "period", g.gtsid FROM paleochronostratigraphy p INNER JOIN omoccurpaleogts g ON p.era = g.gtsterm WHERE period IS NOT NULL;

INSERT INTO omoccurpaleogts(gtsterm,rankid,rankname,parentgtsid) 
  SELECT DISTINCT epoch, 50, "epoch", g.gtsid FROM paleochronostratigraphy p INNER JOIN omoccurpaleogts g ON p.period = g.gtsterm WHERE epoch IS NOT NULL;

INSERT INTO omoccurpaleogts(gtsterm,rankid,rankname,parentgtsid)
  SELECT DISTINCT p.stage, 60, "age", g.gtsid FROM paleochronostratigraphy p INNER JOIN omoccurpaleogts g ON p.epoch = g.gtsterm WHERE stage IS NOT NULL;

UPDATE omoccurpaleogts
  SET rankid = 40, rankname = "period", parentgtsid = 13
  WHERE gtsterm IN("Pennsylvanian","Mississippian");

DROP TABLE omoccurlithostratigraphy;

DROP TABLE paleochronostratigraphy;

ALTER TABLE `omcollections` 
  ADD COLUMN `dynamicProperties` TEXT NULL AFTER `accessrights`,
  ADD COLUMN `datasetID` VARCHAR(250) NULL AFTER `collectionId`;

ALTER TABLE `omcollections` 
  ADD COLUMN `contactJson` LONGTEXT NULL AFTER `email`;

ALTER TABLE `omcollections` 
  CHANGE COLUMN `contactJson` `contactJson` JSON NULL DEFAULT NULL;

UPDATE omcollections
  SET contactJson = CONCAT('[{"firstName":"","lastName":"',contact,'","email":"',email,'"}]')
  WHERE contact IS NOT NULL AND contactJson IS NULL;

ALTER TABLE `omcollections` 
  ADD COLUMN `resourceJson` LONGTEXT NULL AFTER `homepage`;

ALTER TABLE `omcollections` 
  CHANGE COLUMN `resourceJson` `resourceJson` JSON NULL DEFAULT NULL;

UPDATE omcollections
  SET resourceJson = CONCAT('[{"title":{"en":"Homepage"},"url":"',homepage,'"}]')
  WHERE homepage LIKE "http%" AND resourceJson IS NULL;

DROP TABLE `omcollectioncontacts`;

ALTER TABLE `omcollcategories` 
  ADD COLUMN `sortsequence` INT NULL AFTER `notes`;

CREATE TABLE `omoccurresource` (
  `resourceID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `occid` INT UNSIGNED NOT NULL,
  `reourceTitle` VARCHAR(45) NOT NULL,
  `resourceType` VARCHAR(45) NOT NULL,
  `uri` VARCHAR(250) NOT NULL,
  `source` VARCHAR(45) NULL,
  `resourceIdentifier` VARCHAR(45) NULL,
  `notes` VARCHAR(250) NULL,
  `modifiedUid` INT UNSIGNED NULL,
  `createdUid` INT UNSIGNED NULL,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`resourceID`),
  INDEX `FK_omoccurresource_occid_idx` (`occid` ASC),
  INDEX `FK_omoccurresource_modUid_idx` (`modifiedUid` ASC),
  INDEX `FK_omoccurresource_createdUid_idx` (`createdUid` ASC),
  CONSTRAINT `FK_omoccurresource_occid`  FOREIGN KEY (`occid`)  REFERENCES `omoccurrences` (`occid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurresource_modUid`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurresource_createdUid`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE CASCADE  ON UPDATE CASCADE
);

ALTER TABLE `omoccurdeterminations` 
  DROP FOREIGN KEY `FK_omoccurdets_tid`;
  
ALTER TABLE `omoccurdeterminations` 
  CHANGE COLUMN `scientificNameAuthorship` `scientificNameAuthorship` VARCHAR(100) NULL DEFAULT NULL AFTER `sciname`,
  CHANGE COLUMN `tidinterpreted` `tidInterpreted` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `iscurrent` `isCurrent` INT(2) NULL DEFAULT 0 ,
  CHANGE COLUMN `printqueue` `printQueue` INT(2) NULL DEFAULT 0 ,
  CHANGE COLUMN `sortsequence` `sortSequence` INT(10) UNSIGNED NULL DEFAULT 10 ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ,
  ADD COLUMN `family` VARCHAR(150) NULL AFTER `dateIdentifiedInterpreted`,
  ADD COLUMN `taxonRemarks` VARCHAR(45) NULL AFTER `identificationRemarks`;

ALTER TABLE `omoccurdeterminations` 
  ADD CONSTRAINT `FK_omoccurdets_tid`  FOREIGN KEY (`tidInterpreted`)  REFERENCES `taxa` (`TID`);

ALTER TABLE `omoccurdeterminations` 
  DROP INDEX `Index_unique` ,
  ADD UNIQUE INDEX `UQ_omoccurdets_unique` (`occid` ASC, `dateIdentified` ASC, `identifiedBy` ASC, `sciname` ASC),
  DROP INDEX `Index_dateIdentInterpreted` ,
  ADD INDEX `IX_omoccurdets_dateIdInterpreted` (`dateIdentifiedInterpreted` ASC),
  ADD INDEX `IX_omoccurdets_sciname` (`sciname` ASC),
  ADD INDEX `IX_omoccurdets_family` (`family` ASC),
  ADD INDEX `IX_omoccurdets_isCurrent` (`isCurrent` ASC);


ALTER TABLE `uploadspectemp` 
  ADD COLUMN `organismID` VARCHAR(150) NULL AFTER `datasetID`,
  ADD COLUMN `materialSampleID` VARCHAR(150) NULL AFTER `organismID`,
  ADD COLUMN `locationID` VARCHAR(150) NULL AFTER `preparations`,
  ADD COLUMN `continent` VARCHAR(45) NULL AFTER `locationID`,
  ADD COLUMN `waterBody` VARCHAR(150) NULL AFTER `continent`,
  ADD COLUMN `islandGroup` VARCHAR(75) NULL AFTER `waterBody`,
  ADD COLUMN `island` VARCHAR(75) NULL AFTER `islandGroup`,
  ADD COLUMN `countryCode` VARCHAR(5) NULL AFTER `island`,
  ADD COLUMN `parentLocationID` VARCHAR(150) NULL AFTER `locationID`,
  ADD COLUMN `georeferencedDate` DATETIME NULL AFTER `georeferencedBy`,
  ADD COLUMN `paleoJSON` TEXT NULL AFTER `exsiccatiNotes`;

ALTER TABLE `uploadspectemp` 
  CHANGE COLUMN `basisOfRecord` `basisOfRecord` VARCHAR(32) NULL DEFAULT NULL COMMENT 'PreservedSpecimen, LivingSpecimen, HumanObservation' ;

ALTER TABLE `uploadspectemp` 
  ADD INDEX `Index_uploadspec_othercatalognumbers` (`otherCatalogNumbers` ASC);

ALTER TABLE `uploadspecparameters` 
  ADD COLUMN `endpointPublic` INT NULL AFTER `cleanupsp`,
  ADD COLUMN `createdUid` INT UNSIGNED NULL AFTER `dlmisvalid`,
  ADD INDEX `FK_uploadspecparameters_uid_idx` (`createdUid` ASC);

ALTER TABLE `uploadspecparameters` 
  ADD CONSTRAINT `FK_uploadspecparameters_uid`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE SET NULL;

ALTER TABLE `uploadspecparameters` 
  CHANGE COLUMN `Path` `Path` VARCHAR(500) NULL DEFAULT NULL,
  CHANGE COLUMN `QueryStr` `QueryStr` TEXT NULL DEFAULT NULL;

ALTER TABLE `uploadimagetemp` 
  CHANGE COLUMN `specimengui` `sourceIdentifier` VARCHAR(150) NULL DEFAULT NULL;

ALTER TABLE `uploadimagetemp` 
  ADD COLUMN `sourceUrl` VARCHAR(255) NULL AFTER `owner`,
  ADD COLUMN `referenceurl` VARCHAR(255) NULL AFTER `sourceUrl`,
  ADD COLUMN `copyright` VARCHAR(255) NULL AFTER `referenceurl`,
  ADD COLUMN `accessrights` VARCHAR(255) NULL AFTER `copyright`,
  ADD COLUMN `rights` VARCHAR(255) NULL AFTER `accessrights`,
  ADD COLUMN `locality` VARCHAR(250) NULL AFTER `rights`;

ALTER TABLE `uploadimagetemp` 
  CHANGE COLUMN `url` `url` VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE `uploadtaxa` 
  ADD COLUMN `taxonomicStatus` VARCHAR(45) NULL AFTER `InfraAuthor`,
  CHANGE COLUMN `UnitInd3` `UnitInd3` VARCHAR(45) NULL DEFAULT NULL ;

ALTER TABLE `uploadtaxa` 
  DROP INDEX `UNIQUE_sciname` ,
  ADD UNIQUE INDEX `UNIQUE_sciname` (`SciName` ASC, `RankId` ASC, `Author` ASC, `AcceptedStr` ASC);


ALTER TABLE `users` 
  ADD COLUMN `dynamicProperties` TEXT NULL AFTER `usergroups`;

ALTER TABLE `userroles` 
  ADD UNIQUE INDEX `Unique_userroles` (`uid` ASC, `role` ASC, `tablename` ASC, `tablepk` ASC);
  
# If you run into an error setting the Unique_userroles UNIQUE INDEX above, 
# run the following SQL to locate the duplicate record interferring with setting that INDEX.
# Delete the duplicate records to avoid the error
# SELECT uid, role, tablename, tablepk, count(*) as cnt FROM userroles GROUP BY uid, role, tablename, tablepk HAVING cnt > 1; 

#Tag all collection admin and editors as non-volunteer crowdsource editors   
UPDATE omcrowdsourcecentral c INNER JOIN omcrowdsourcequeue q ON c.omcsid = q.omcsid
  INNER JOIN userroles r ON c.collid = r.tablepk AND q.uidprocessor = r.uid
  SET q.isvolunteer = 0
  WHERE r.role IN("CollAdmin","CollEditor") AND q.isvolunteer = 1;


UPDATE omoccurgenetic SET initialtimestamp = now() WHERE initialtimestamp IS NULL;

ALTER TABLE `omoccurgenetic` 
  CHANGE COLUMN `resourceurl` `resourceurl` VARCHAR(500) NULL ,
  CHANGE COLUMN `notes` `notes` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ;

ALTER TABLE `omoccurgenetic` 
  ADD UNIQUE INDEX `UNIQUE_omoccurgenetic` (`occid` ASC, `resourceurl` ASC);

ALTER TABLE `omoccuridentifiers` 
  ADD COLUMN `sortBy` INT NULL AFTER `notes`;

ALTER TABLE `omoccurassociations` 
  DROP FOREIGN KEY `FK_occurassoc_occidassoc`,
  DROP FOREIGN KEY `FK_occurassoc_uidcreated`,
  DROP FOREIGN KEY `FK_occurassoc_uidmodified`;

ALTER TABLE `omoccurassociations` 
  CHANGE COLUMN `occidassociate` `occidAssociate` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `resourceurl` `resourceUrl` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `verbatimsciname` `verbatimSciname` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `createduid` `createdUid` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `datelastmodified` `modifiedTimestamp` DATETIME NULL DEFAULT NULL ,
  CHANGE COLUMN `modifieduid` `modifiedUid` INT(10) UNSIGNED NULL DEFAULT NULL ,
  ADD COLUMN `subType` VARCHAR(45) NULL AFTER `relationship`;

ALTER TABLE `omoccurassociations` 
  ADD COLUMN `imageMapJSON` TEXT NULL AFTER `dateEmerged`;

ALTER TABLE `omoccurassociations` 
  ADD CONSTRAINT `FK_occurassoc_occidassoc`  FOREIGN KEY (`occidAssociate`)  REFERENCES `omoccurrences` (`occid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_occurassoc_uidcreated`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_occurassoc_uidmodified`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `omoccurassociations` 
  ADD UNIQUE INDEX `UQ_omoccurassoc_occid` (`occid` ASC, `occidAssociate` ASC, `relationship` ASC),
  ADD UNIQUE INDEX `UQ_omoccurassoc_external` (`occid` ASC, `relationship` ASC, `resourceUrl` ASC),
  ADD UNIQUE INDEX `UQ_omoccurassoc_sciname` (`occid` ASC, `verbatimSciname` ASC);


ALTER TABLE `tmtraits` 
  ADD COLUMN `projectGroup` VARCHAR(45) NULL AFTER `notes`,
  ADD COLUMN `isPublic` INT NULL DEFAULT 1 AFTER `projectGroup`,
  ADD COLUMN `includeInSearch` INT NULL AFTER `isPublic`;


ALTER TABLE `omoccurdatasets`
  ADD COLUMN `description` TEXT NULL AFTER `name`,
  ADD COLUMN `category` VARCHAR(45) NULL AFTER `name`,
  ADD COLUMN `isPublic` INT NULL AFTER `category`,
  ADD COLUMN `includeInSearch` INT NULL AFTER `isPublic`;

ALTER TABLE `omoccurdatasets` 
  ADD COLUMN `parentDatasetID` INT UNSIGNED NULL AFTER `isPublic`,
  ADD INDEX `FK_omoccurdatasets_parent_idx` (`parentDatasetID` ASC);

ALTER TABLE `omoccurdatasets` 
  ADD CONSTRAINT `FK_omoccurdatasets_parent` FOREIGN KEY (`parentDatasetID`) REFERENCES `omoccurdatasets` (`datasetid`)  ON DELETE SET NULL  ON UPDATE CASCADE;


CREATE TABLE `referencedatasetlink` (
  `refid` INT NOT NULL,
  `datasetid` INT UNSIGNED NOT NULL,
  `createdUid` INT UNSIGNED NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`refid`, `datasetid`),
  INDEX `FK_refdataset_datasetid_idx` (`datasetid` ASC),
  INDEX `FK_refdataset_uid_idx` (`createdUid` ASC),
  CONSTRAINT `FK_refdataset_refid`  FOREIGN KEY (`refid`)  REFERENCES `referenceobject` (`refid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_refdataset_datasetid`  FOREIGN KEY (`datasetid`)  REFERENCES `omoccurdatasets` (`datasetid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_refdataset_uid`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE);


CREATE TABLE `igsnverification` (
  `igsn` VARCHAR(15) NOT NULL,
  `occid` INT UNSIGNED NULL,
  `status` INT NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  INDEX `FK_igsn_occid_idx` (`occid` ASC),
  INDEX `INDEX_igsn` (`igsn` ASC),
  CONSTRAINT `FK_igsn_occid`  FOREIGN KEY (`occid`)  REFERENCES `omoccurrences` (`occid`)  ON DELETE CASCADE  ON UPDATE CASCADE);

ALTER TABLE `igsnverification` 
  ADD COLUMN `catalogNumber` VARCHAR(45) NULL AFTER `occid`;

CREATE TABLE `omoccurloanuser` (
  `loanid` INT UNSIGNED NOT NULL,
  `uid` INT UNSIGNED NOT NULL,
  `accessType` VARCHAR(45) NOT NULL,
  `notes` VARCHAR(250) NULL,
  `modifiedByUid` INT UNSIGNED NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`loanid`, `uid`),
  INDEX `FK_occurloan_uid_idx` (`uid` ASC),
  INDEX `FK_occurloan_modifiedByUid_idx` (`modifiedByUid` ASC),
  CONSTRAINT `FK_occurloan_loanid`  FOREIGN KEY (`loanid`)  REFERENCES `omoccurloans` (`loanid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloan_uid`  FOREIGN KEY (`uid`)  REFERENCES `users` (`uid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloan_modifiedByUid`  FOREIGN KEY (`modifiedByUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE);


CREATE TABLE `specprocstatus` (
  `spsID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `occid` INT UNSIGNED NOT NULL,
  `processName` VARCHAR(45) NOT NULL,
  `result` VARCHAR(45) NULL,
  `processVariables` VARCHAR(150) NOT NULL,
  `processorUid` INT UNSIGNED NULL,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`spsID`),
  INDEX `specprocstatus_occid_idx` (`occid` ASC),
  INDEX `specprocstatus_uid_idx` (`processorUid` ASC),
  CONSTRAINT `specprocstatus_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `specprocstatus_uid` FOREIGN KEY (`processorUid`) REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE);

ALTER TABLE `omoccurrences` 
  CHANGE COLUMN `eventID` `eventID` VARCHAR(150) NULL DEFAULT NULL,
  CHANGE COLUMN `locationID` `locationID` VARCHAR(150) NULL DEFAULT NULL,
  CHANGE COLUMN `labelProject` `labelProject` varchar(250) DEFAULT NULL,
  CHANGE COLUMN `georeferenceRemarks` `georeferenceRemarks` VARCHAR(500) NULL DEFAULT NULL,
  CHANGE COLUMN `latestDateCollected` `eventDate2` DATE DEFAULT NULL,
  ADD COLUMN `eventTime` VARCHAR(45) NULL AFTER `verbatimEventDate`,
  ADD COLUMN `georeferencedDate` DATETIME NULL AFTER `georeferencedBy`,
  ADD COLUMN `parentLocationID` VARCHAR(150) NULL AFTER `locationID`,
  ADD COLUMN `organismID` VARCHAR(150) NULL AFTER `datasetID`,
  ADD COLUMN `continent` VARCHAR(45) NULL AFTER `locationID`,
  ADD COLUMN `islandGroup` VARCHAR(75) NULL AFTER `waterBody`,
  ADD COLUMN `island` VARCHAR(75) NULL AFTER `islandGroup`,
  ADD COLUMN `countryCode` VARCHAR(5) NULL AFTER `island`,
  ADD COLUMN `availability` INT(2) NULL AFTER `previousIdentifications`,
  CHANGE COLUMN `waterBody` `waterBody` VARCHAR(75) NULL DEFAULT NULL AFTER `continent`,
  DROP INDEX `idx_occrecordedby`;

ALTER TABLE `omoccurrences` 
  DROP INDEX `Index_gui`;  

ALTER TABLE `omoccurrences` 
  DROP INDEX `Index_latestDateCollected`;

ALTER TABLE `omoccurrences` 
  ADD INDEX `IX_omoccur_eventDate2` (`eventDate2` ASC);

ALTER TABLE `omoccurrences` 
  ADD INDEX `Index_locationID` (`locationID` ASC),
  ADD INDEX `Index_eventID` (`eventID` ASC),
  ADD UNIQUE INDEX `UNIQUE_occurrenceID` (`occurrenceID` ASC),
  ADD INDEX `Index_occur_localitySecurity` (`localitySecurity` ASC),
  ADD INDEX `Index_latlng` (`decimalLatitude` ASC, `decimalLongitude` ASC);

DELETE FROM omoccurrencesfulltext 
WHERE locality IS NULL AND recordedby IS NULL;

REPLACE INTO omoccurrencesfulltext(occid,locality,recordedby) 
  SELECT occid, CONCAT_WS("; ", municipality, locality), recordedby
  FROM omoccurrences
  WHERE municipality IS NOT NULL OR locality IS NOT NULL OR recordedby IS NOT NULL;
  
OPTIMIZE table omoccurrencesfulltext;

REPLACE INTO omoccurpoints (occid,point)
  SELECT occid, Point(decimalLatitude, decimalLongitude) 
  FROM omoccurrences 
  WHERE decimalLatitude IS NOT NULL AND decimalLongitude IS NOT NULL;

OPTIMIZE table omoccurpoints;


#Add edittype field and run update query to tag batch updates (edittype = 1)
ALTER TABLE `omoccuredits` 
  ADD COLUMN `editType` INT NULL DEFAULT 0 COMMENT '0 = general edit, 1 = batch edit' AFTER `AppliedStatus`;

UPDATE omoccuredits e INNER JOIN (SELECT initialtimestamp, uid, count(DISTINCT occid) as cnt
  FROM omoccuredits
  GROUP BY initialtimestamp, uid
  HAVING cnt > 2) as inntab ON e.initialtimestamp = inntab.initialtimestamp AND e.uid = inntab.uid
  SET edittype = 1;


#Occurrence Trait/Attribute adjustments
	#Add measurementID (GUID) to tmattribute table 
	#Add measurementAccuracy field
	#Add measurementUnitID field
	#Add measurementMethod field
	#Add exportHeader for trait name
	#Add exportHeader for state name


DELIMITER //

DROP TRIGGER IF EXISTS `omoccurrences_insert`//
CREATE TRIGGER `omoccurrences_insert` AFTER INSERT ON `omoccurrences`
FOR EACH ROW BEGIN
	IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
		INSERT INTO omoccurpoints (`occid`,`point`) 
		VALUES (NEW.`occid`,Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`));
	END IF;
	IF NEW.`recordedby` IS NOT NULL OR NEW.`municipality` IS NOT NULL OR NEW.`locality` IS NOT NULL THEN
		INSERT INTO omoccurrencesfulltext (`occid`,`recordedby`,`locality`) 
		VALUES (NEW.`occid`,NEW.`recordedby`,CONCAT_WS("; ", NEW.`municipality`, NEW.`locality`));
	END IF;
END
//

DROP TRIGGER IF EXISTS `omoccurrences_update`//

CREATE TRIGGER `omoccurrences_update` AFTER UPDATE ON `omoccurrences`
FOR EACH ROW BEGIN
	IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
		IF EXISTS (SELECT `occid` FROM omoccurpoints WHERE `occid`=NEW.`occid`) THEN
			UPDATE omoccurpoints 
			SET `point` = Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`)
			WHERE `occid` = NEW.`occid`;
		ELSE 
			INSERT INTO omoccurpoints (`occid`,`point`) 
			VALUES (NEW.`occid`,Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`));
		END IF;
	ELSE
		DELETE FROM omoccurpoints WHERE `occid` = NEW.`occid`;
	END IF;

	IF NEW.`recordedby` IS NOT NULL OR NEW.`municipality` IS NOT NULL OR NEW.`locality` IS NOT NULL THEN
		IF EXISTS (SELECT `occid` FROM omoccurrencesfulltext WHERE `occid`=NEW.`occid`) THEN
			UPDATE omoccurrencesfulltext 
			SET `recordedby` = NEW.`recordedby`,`locality` = CONCAT_WS("; ", NEW.`municipality`, NEW.`locality`)
			WHERE `occid` = NEW.`occid`;
		ELSE
			INSERT INTO omoccurrencesfulltext (`occid`,`recordedby`,`locality`) 
			VALUES (NEW.`occid`,NEW.`recordedby`,CONCAT_WS("; ", NEW.`municipality`, NEW.`locality`));
		END IF;
	ELSE 
		DELETE FROM omoccurrencesfulltext WHERE `occid` = NEW.`occid`;
	END IF;
END
//

DELIMITER ;
