INSERT IGNORE INTO schemaversion (versionnumber) values ("3.0");

CREATE TABLE `adminconfig` (
  `configID` INT NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(45) NULL,
  `attributeName` VARCHAR(45) NOT NULL,
  `attributeValue` VARCHAR(1000) NOT NULL,
  `dynamicProperties` text DEFAULT NULL,
  `notes` VARCHAR(45) NULL,
  `modifiedUid` INT UNSIGNED NULL,
  `modifiedTimestamp` DATETIME NULL,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`configID`),
  INDEX `FK_adminConfig_uid_idx` (`modifiedUid` ASC),
  UNIQUE INDEX `UQ_adminconfig_name` (`attributeName` ASC),
  CONSTRAINT `FK_adminConfig_uid`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE RESTRICT  ON UPDATE RESTRICT
);

ALTER TABLE `agents` 
  CHANGE COLUMN `taxonomicgroups` `taxonomicGroups` VARCHAR(900) NULL DEFAULT NULL ,
  CHANGE COLUMN `collectionsat` `collectionsAt` VARCHAR(900) NULL DEFAULT NULL ,
  CHANGE COLUMN `mbox_sha1sum` `mboxSha1Sum` CHAR(40) NULL DEFAULT NULL ;

ALTER TABLE `agents`
  ADD INDEX `IX_agents_familyname` (`familyName` ASC),
  ADD UNIQUE INDEX `UQ_agents_guid` (`guid` ASC);

ALTER TABLE `agents` 
  DROP INDEX `firstname`,
  ADD INDEX `IX_agents_firstname` (`firstName` ASC);

ALTER TABLE `agents` 
  ADD INDEX `FK_agents_preferred_recby_idx` (`preferredRecByID` ASC),
  DROP INDEX `FK_agents_preferred_recby`;

CREATE TABLE `agentoccurrencelink` (
  `agentID` BIGINT(20) NOT NULL,
  `occid` INT UNSIGNED NOT NULL,
  `role` VARCHAR(45) NOT NULL DEFAULT '',
  `createdUid` INT UNSIGNED NULL,
  `modifiedUid` INT UNSIGNED NULL,
  `dateLastModified` DATETIME NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`agentID`, `occid`, `role`),
  INDEX `FK_agentoccurlink_occid_idx` (`occid` ASC),
  INDEX `FK_agentoccurlink_created_idx` (`createdUid` ASC),
  INDEX `FK_agentoccurlink_modified_idx` (`modifiedUid` ASC),
  INDEX `FK_agentoccurlink_role` (`role` ASC),
  CONSTRAINT `FK_agentoccurlink_agentID`  FOREIGN KEY (`agentID`)  REFERENCES `agents` (`agentID`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_agentoccurlink_occid`  FOREIGN KEY (`occid`)  REFERENCES `omoccurrences` (`occid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_agentoccurlink_created`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE RESTRICT  ON UPDATE CASCADE,
  CONSTRAINT `FK_agentoccurlink_modified` FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE RESTRICT  ON UPDATE CASCADE
);

INSERT IGNORE INTO agents(familyName,firstName,middleName,startYearActive,endYearActive,notes,rating,guid)
  SELECT DISTINCT c.familyname, c.firstname, c.middlename, c.startyearactive, c.endyearactive, c.notes, c.rating, c.guid 
  FROM omcollectors c LEFT JOIN agents a ON c.guid = a.guid
  WHERE a.guid IS NULL;

INSERT IGNORE INTO agentoccurrencelink(agentID, occid, role)
  SELECT a.agentID, o.occid, "primaryCollector"
  FROM agents a INNER JOIN omcollectors c ON a.guid = c.guid
  INNER JOIN omoccurrences o ON c.recordedbyid = o.recordedbyid;

CREATE TABLE `agentdeterminationlink` (
  `agentID` BIGINT(20) NOT NULL,
  `detID` INT UNSIGNED NOT NULL,
  `role` VARCHAR(45) NOT NULL DEFAULT '',
  `createdUid` INT UNSIGNED NULL DEFAULT NULL,
  `modifiedUid` INT UNSIGNED NULL DEFAULT NULL,
  `dateLastModified` DATETIME NULL DEFAULT NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`agentID`, `detID`, `role`),
  INDEX `FK_agentdetlink_detid_idx` (`detID` ASC),
  INDEX `FK_agentdetlink_modified_idx` (`modifiedUid` ASC),
  INDEX `FK_agentdetlink_created_idx` (`createdUid` ASC),
  INDEX `IX_agentdetlink_role` (`role` ASC),
  CONSTRAINT `FK_agentdetlink_agentID`  FOREIGN KEY (`agentID`)  REFERENCES `agents` (`agentID`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_agentdetlink_detid`  FOREIGN KEY (`detID`)  REFERENCES `omoccurdeterminations` (`detid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_agentdetlink_modified`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE RESTRICT  ON UPDATE CASCADE,
  CONSTRAINT `FK_agentdetlink_created`  FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE RESTRICT  ON UPDATE CASCADE
);

ALTER TABLE `agentlinks` 
  CHANGE COLUMN `isprimarytopicof` `isPrimaryTopicOf` TINYINT(1) NOT NULL DEFAULT 1 ;

ALTER TABLE `agentnames` 
  ENGINE = InnoDB ,
  CHANGE COLUMN `agentNamesID` `agentNamesID` BIGINT(20) NOT NULL ,
  CHANGE COLUMN `agentID` `agentID` BIGINT(20) NOT NULL ,
  CHANGE COLUMN `type` `nameType` VARCHAR(32) NOT NULL DEFAULT 'Full Name' ,
  CHANGE COLUMN `name` `agentName` VARCHAR(255) NOT NULL ;

ALTER TABLE `agentnames` 
  ADD CONSTRAINT `FK_agentnames_agentID`  FOREIGN KEY (`agentID`)  REFERENCES `agents` (`agentID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `agentnames` 
  ADD INDEX `IX_agentnames_name` (`agentName` ASC),
  DROP INDEX `ft_collectorname` ;

ALTER TABLE `agentnames` 
  ADD UNIQUE INDEX `UQ_agentnames_unique` (`agentID` ASC, `nameType` ASC, `agentName` ASC);

ALTER TABLE `agentnames` 
  DROP INDEX `type`,
  DROP INDEX `agentid`;

ALTER TABLE `agentnumberpattern` 
  ADD INDEX `IX_agentnumberpattern_agentid` (`agentID` ASC),
  DROP INDEX `agentid`;

ALTER TABLE `agentrelations` 
  ADD INDEX `FK_agentrelations_fromagentid_idx` (`fromAgentID` ASC),
  ADD INDEX `FK_agentrelations_toagentid_idx` (`toAgentID` ASC),
  ADD INDEX `FK_agentrelations_relationship_idx` (`relationship` ASC),
  DROP INDEX `relationship`,
  DROP INDEX `toagentid`,
  DROP INDEX `fromagentid`;

ALTER TABLE `agentteams` 
  DROP FOREIGN KEY `agentteams_ibfk_1`,
  DROP FOREIGN KEY `agentteams_ibfk_2`;

ALTER TABLE `agentteams` 
  ADD INDEX `FK_agentteams_teamagentid_idx` (`teamAgentID` ASC),
  ADD INDEX `FK_agentteams_memberagentid_idx` (`memberAgentID` ASC),
  DROP INDEX `memberagentid`,
  DROP INDEX `teamagentid`;

ALTER TABLE `agentteams` 
  ADD CONSTRAINT `FK_agentteams_teamAgentID`  FOREIGN KEY (`teamAgentID`)  REFERENCES `agents` (`agentID`)  ON DELETE NO ACTION  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_agentteams_memberAgentID`  FOREIGN KEY (`memberAgentID`)  REFERENCES `agents` (`agentID`)  ON DELETE NO ACTION  ON UPDATE CASCADE; 


ALTER TABLE `fmchecklists` 
  CHANGE COLUMN `Name` `name` VARCHAR(100) NOT NULL ,
  CHANGE COLUMN `Title` `title` VARCHAR(150) NULL DEFAULT NULL ,
  CHANGE COLUMN `Locality` `locality` VARCHAR(500) NULL DEFAULT NULL ,
  CHANGE COLUMN `Publication` `publication` VARCHAR(500) NULL DEFAULT NULL ,
  CHANGE COLUMN `Abstract` `abstract` TEXT NULL DEFAULT NULL ,
  CHANGE COLUMN `Authors` `authors` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `Type` `type` VARCHAR(50) NULL DEFAULT 'static' ,
  CHANGE COLUMN `dynamicsql` `dynamicSql` VARCHAR(500) NULL DEFAULT NULL ,
  CHANGE COLUMN `Parent` `parent` VARCHAR(50) NULL DEFAULT NULL ,
  CHANGE COLUMN `parentclid` `parentClid` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `Notes` `notes` VARCHAR(500) NULL DEFAULT NULL ,
  CHANGE COLUMN `LatCentroid` `latCentroid` DOUBLE(9,6) NULL DEFAULT NULL ,
  CHANGE COLUMN `LongCentroid` `longCentroid` DOUBLE(9,6) NULL DEFAULT NULL ,
  CHANGE COLUMN `pointradiusmeters` `pointRadiusMeters` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `footprintWKT` `footprintWkt` TEXT NULL DEFAULT NULL ,
  CHANGE COLUMN `percenteffort` `percentEffort` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `Access` `access` VARCHAR(45) NULL DEFAULT 'private' ,
  CHANGE COLUMN `SortSequence` `sortSequence` INT(10) UNSIGNED NOT NULL DEFAULT 50 ,
  CHANGE COLUMN `DateLastModified` `dateLastModified` DATETIME NULL DEFAULT NULL ,
  CHANGE COLUMN `InitialTimeStamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `fmchecklists` 
  CHANGE COLUMN `CLID` `clid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ;

ALTER TABLE `fmchecklists`
  ADD COLUMN `dynamicProperties` TEXT NULL AFTER `headerUrl`,
  ADD COLUMN `guid` VARCHAR(45) NULL AFTER `expiration`,
  ADD COLUMN `recordID` VARCHAR(45) NULL AFTER `guid`,
  ADD COLUMN `modifiedUid` INT UNSIGNED NULL AFTER `recordID`;

ALTER TABLE `fmvouchers` 
  DROP FOREIGN KEY `FK_vouchers_cl`;

ALTER TABLE `fmvouchers` 
  DROP INDEX `chklst_taxavouchers` ;

ALTER TABLE `fmvouchers` 
  DROP FOREIGN KEY `FK_fmvouchers_occ`;

ALTER TABLE `fmchklstcoordinates` 
  DROP FOREIGN KEY `FKchklsttaxalink`;
  
ALTER TABLE `fmchklstcoordinates` 
  DROP INDEX `IndexUnique` ;

DROP TABLE IF EXISTS `fmchklsttaxastatus`;

DROP TABLE IF EXISTS `fmcltaxacomments`;

UPDATE fmchklsttaxalink SET morphospecies = '' WHERE morphospecies IS NULL;

ALTER TABLE `fmchklsttaxalink` 
  ADD COLUMN `clTaxaID` INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
  CHANGE COLUMN `morphospecies` `morphospecies` VARCHAR(45) NOT NULL DEFAULT '' ,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`clTaxaID`),
  ADD UNIQUE INDEX `UQ_chklsttaxalink` (`CLID` ASC, `TID` ASC, `morphospecies` ASC);

ALTER TABLE `fmchklsttaxalink` 
  CHANGE COLUMN `TID` `tid` INT(10) UNSIGNED NOT NULL,
  CHANGE COLUMN `CLID` `clid` INT(10) UNSIGNED NOT NULL,
  CHANGE COLUMN `morphospecies` `morphoSpecies` VARCHAR(45) NULL DEFAULT '' ,
  CHANGE COLUMN `familyoverride` `familyOverride` VARCHAR(50) NULL DEFAULT NULL ,
  CHANGE COLUMN `Habitat` `habitat` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `Abundance` `abundance` VARCHAR(50) NULL DEFAULT NULL ,
  CHANGE COLUMN `Notes` `notes` VARCHAR(2000) NULL DEFAULT NULL ,
  CHANGE COLUMN `Nativity` `nativity` VARCHAR(50) NULL DEFAULT NULL COMMENT 'native, introducted' ,
  CHANGE COLUMN `Endemic` `endemic` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `internalnotes` `internalNotes` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `InitialTimeStamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `fmvouchers` 
  DROP PRIMARY KEY;

ALTER TABLE `fmvouchers` 
  ADD COLUMN `voucherID` INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
  ADD PRIMARY KEY (`voucherID`);

ALTER TABLE `fmvouchers` 
  ADD COLUMN `clTaxaID` INT UNSIGNED NULL AFTER `voucherID`,
  ADD INDEX `FK_fmvouchers_occ_idx` (`occid` ASC),
  ADD INDEX `FK_fmvouchers_tidclid_idx` (`clTaxaID` ASC);

ALTER TABLE `fmvouchers` 
  ADD UNIQUE INDEX `UQ_fmvouchers_clTaxaID_occid` (`clTaxaID` ASC, `occid` ASC);

UPDATE IGNORE fmvouchers v INNER JOIN fmchklsttaxalink c ON v.clid = c.clid AND v.tid = c.tid
  SET v.clTaxaID = c.clTaxaID
  WHERE v.clTaxaID IS NULL;

#Fix if following statement falis: DELETE FROM fmvouchers WHERE clTaxaID IS NULL
ALTER TABLE `fmvouchers` 
  CHANGE COLUMN `clTaxaID` `clTaxaID` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `CLID` `CLID` INT(10) UNSIGNED NULL ;

ALTER TABLE `fmvouchers` 
  ADD CONSTRAINT `FK_fmvouchers_occ`  FOREIGN KEY (`occid`)  REFERENCES `omoccurrences` (`occid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_fmvouchers_tidclid`  FOREIGN KEY (`clTaxaID`)  REFERENCES `fmchklsttaxalink` (`clTaxaID`)  ON DELETE CASCADE  ON UPDATE CASCADE;


ALTER TABLE `fmchklstcoordinates`
  ADD COLUMN `sourceName` VARCHAR(75) NULL AFTER `decimalLongitude`,
  ADD COLUMN `sourceIdentifier` VARCHAR(45) NULL AFTER `sourceName`,
  ADD COLUMN `referenceUrl` VARCHAR(250) NULL AFTER `sourceIdentifier`,
  ADD COLUMN `dynamicProperties` TEXT NULL AFTER `notes`,
  CHANGE COLUMN `chklstcoordid` `clCoordID` INT(11) NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `decimallatitude` `decimalLatitude` DOUBLE NOT NULL ,
  CHANGE COLUMN `decimallongitude` `decimalLongitude` DOUBLE NOT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `ctcontrolvocab` 
  ADD COLUMN `collid` INT UNSIGNED NULL DEFAULT NULL AFTER `cvID`,
  ADD INDEX `FK_ctControlVocab_collid_idx` (`collid` ASC);

ALTER TABLE `ctcontrolvocab` 
  ADD CONSTRAINT `FK_ctControlVocab_collid`  FOREIGN KEY (`collid`)  REFERENCES `omcollections` (`CollID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `ctcontrolvocab` 
  CHANGE COLUMN `title` `title` VARCHAR(45) NOT NULL ,
  CHANGE COLUMN `tableName` `tableName` VARCHAR(45) NOT NULL ,
  CHANGE COLUMN `fieldName` `fieldName` VARCHAR(45) NOT NULL ,
  ADD UNIQUE INDEX `UQ_ctControlVocab` (`title` ASC, `tableName` ASC, `fieldName` ASC);


ALTER TABLE `fmprojects` 
  CHANGE COLUMN `projname` `projname` VARCHAR(75) NOT NULL ;

ALTER TABLE `geographicthesaurus` 
  ADD COLUMN `geoLevel` INT NOT NULL AFTER `category`;

ALTER TABLE `geographicthesaurus` 
  DROP FOREIGN KEY `FK_geothes_parentID`;

ALTER TABLE `geographicthesaurus` 
ADD CONSTRAINT `FK_geothes_parentID`  FOREIGN KEY (`parentID`)  REFERENCES `geographicthesaurus` (`geoThesID`)  ON DELETE RESTRICT  ON UPDATE CASCADE;

ALTER TABLE `geographicthesaurus` 
  ADD UNIQUE INDEX `UQ_geothes` (`geoterm` ASC, `parentID` ASC);

#Get rid of old geographic thesaurus tables that were never used
DROP TABLE geothesmunicipality;
DROP TABLE geothescounty;
DROP TABLE geothesstateprovince;
DROP TABLE geothescountry;
DROP TABLE geothescontinent;


ALTER TABLE `glossary` 
  ADD COLUMN `plural` VARCHAR(150) NULL AFTER `term`,
  ADD COLUMN `termType` VARCHAR(45) NULL AFTER `plural`,
  ADD COLUMN `origin` VARCHAR(45) NULL AFTER `langid`,
  ADD COLUMN `notesInternal` VARCHAR(250) NULL AFTER `notes`,
  ADD INDEX `IX_gloassary_plural` (`plural` ASC);

ALTER TABLE `glossary` 
  CHANGE COLUMN `resourceurl` `resourceUrl` VARCHAR(600) NULL DEFAULT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

CREATE TABLE `glossarycategory` (
  `glossCatID` INT NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(45) NULL,
  `rankID` INT NULL DEFAULT 10,
  `langID` INT NULL,
  `parentCatID` INT NULL,
  `translationCatID` INT NULL,
  `notes` VARCHAR(150) NULL,
  `modifiedUid` INT UNSIGNED NULL,
  `modifiedTimestamp` TIMESTAMP NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`glossCatID`),
  INDEX `FK_glossarycategory_lang_idx` (`langID` ASC),
  INDEX `IX_glossarycategory_cat` (`category` ASC),
  INDEX `FK_glossarycategory_transCatID_idx` (`translationCatID` ASC),
  INDEX `FK_glossarycategory_parentCatID_idx` (`parentCatID` ASC),
  UNIQUE INDEX `UQ_glossary_category_term` (`category` ASC, `langID` ASC, `rankid` ASC),
  CONSTRAINT `FK_glossarycategory_lang`   FOREIGN KEY (`langID`)  REFERENCES `adminlanguages` (`langid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  CONSTRAINT `FK_glossarycategory_transCatID`  FOREIGN KEY (`translationCatID`)  REFERENCES `glossarycategory` (`glossCatID`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  CONSTRAINT `FK_glossarycategory_parentCatID`  FOREIGN KEY (`parentCatID`)  REFERENCES `glossarycategory` (`glossCatID`)  ON DELETE SET NULL  ON UPDATE CASCADE
);

CREATE TABLE `glossarycategorylink` (
  `glossCatLinkID` INT NOT NULL AUTO_INCREMENT,
  `glossID` INT UNSIGNED NOT NULL,
  `glossCatID` INT NOT NULL,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`glossCatLinkID`),
  INDEX `FK_glossCatLink_glossID_idx` (`glossID` ASC),
  INDEX `FK_glossCatLink_glossCatID_idx` (`glossCatID` ASC),
  CONSTRAINT `FK_glossCatLink_glossID`  FOREIGN KEY (`glossID`)  REFERENCES `glossary` (`glossid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_glossCatLink_glossCatID`  FOREIGN KEY (`glossCatID`)  REFERENCES `glossarycategory` (`glossCatID`)  ON DELETE CASCADE  ON UPDATE CASCADE
);


ALTER TABLE `igsnverification` 
  CHANGE COLUMN `status` `syncStatus` VARCHAR(45) NULL DEFAULT NULL ;

ALTER TABLE `igsnverification` 
  DROP FOREIGN KEY `FK_igsn_occid`;

ALTER TABLE `igsnverification` 
  CHANGE COLUMN `occid` `occidInPortal` INT(10) UNSIGNED NULL DEFAULT NULL ;

ALTER TABLE `igsnverification` 
  ADD COLUMN `occidInSesar` INT UNSIGNED NULL AFTER `occidInPortal`;

ALTER TABLE `igsnverification` 
  ADD CONSTRAINT `FK_igsn_occid`  FOREIGN KEY (`occidInPortal`)  REFERENCES `omoccurrences` (`occid`)  ON DELETE CASCADE  ON UPDATE CASCADE;


ALTER TABLE `images` 
  DROP FOREIGN KEY `FK_photographeruid`;

ALTER TABLE `images` 
  CHANGE COLUMN `thumbnailurl` `thumbnailUrl` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `originalurl` `originalUrl` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `archiveurl` `archiveUrl` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `photographeruid` `photographerUid` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `imagetype` `imageType` VARCHAR(50) NULL DEFAULT NULL ,
  CHANGE COLUMN `sourceurl` `sourceUrl` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `accessrights` `accessRights` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `sortsequence` `sortSequence` INT(10) UNSIGNED NOT NULL DEFAULT 50 ;

ALTER TABLE `images` 
  ADD CONSTRAINT `FK_photographeruid`  FOREIGN KEY (`photographerUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `images` 
  ADD COLUMN `hashFunction` VARCHAR(45) NULL AFTER `sourceIdentifier`,
  ADD COLUMN `hashValue` VARCHAR(45) NULL AFTER `hashFunction`,
  ADD COLUMN `recordID` VARCHAR(45) NULL AFTER `defaultDisplay`;

ALTER TABLE `images` 
  ADD INDEX `IX_images_recordID` (`recordID` ASC);
  
UPDATE images i INNER JOIN guidimages g ON i.imgid = g.imgid SET i.recordID = g.guid WHERE i.recordID IS NULL;


ALTER TABLE `imagetagkey` 
  ADD COLUMN `resourceLink` VARCHAR(250) NULL AFTER `description_en`,
  ADD COLUMN `audubonCoreTarget` VARCHAR(45) NULL AFTER `resourceLink`;

ALTER TABLE `imagetagkey` 
  ADD COLUMN `tagDescription` VARCHAR(255) NOT NULL AFTER `description_en`;

UPDATE imagetagkey
  SET tagDescription = description_en
  WHERE tagDescription = "";

CREATE TABLE `imagetaggroup` (
  `imgTagGroupID` INT NOT NULL AUTO_INCREMENT,
  `groupName` VARCHAR(45) NOT NULL,
  `category` VARCHAR(45) NULL,
  `resourceUrl` VARCHAR(150) NULL,
  `audubonCoreTarget` VARCHAR(45) NULL,
  `controlType` VARCHAR(45) NULL,
  `notes` VARCHAR(250) NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`imgTagGroupID`),
  INDEX `IX_imagetaggroup` (`groupName` ASC)
);

ALTER TABLE `imagetagkey` 
  ADD COLUMN `imgTagGroupID` INT NULL AFTER `tagkey`,
  ADD INDEX `FK_imageTagKey_imgTagGroupID_idx` (`imgTagGroupID` ASC);

ALTER TABLE `imagetagkey` 
  ADD CONSTRAINT `FK_imageTagKey_imgTagGroupID`  FOREIGN KEY (`imgTagGroupID`)  REFERENCES `imagetaggroup` (`imgTagGroupID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `imagetag` 
  ADD COLUMN `imageBoundingBox` VARCHAR(45) NULL AFTER `keyvalue`,
  ADD COLUMN `notes` VARCHAR(250) NULL AFTER `imageBoundingBox`;

ALTER TABLE `imageprojects` 
  ADD COLUMN `projectType` VARCHAR(45) NULL AFTER `description`,
  ADD COLUMN `collid` INT UNSIGNED NULL AFTER `projectType`,
  CHANGE COLUMN `uidcreated` `uidcreated` INT(11) UNSIGNED NULL DEFAULT NULL ,
  ADD INDEX `FK_imageproject_collid_idx` (`collid` ASC),
  ADD INDEX `FK_imageproject_uid_idx` (`uidcreated` ASC);

ALTER TABLE `imageprojects` 
  ADD CONSTRAINT `FK_imageproject_collid`  FOREIGN KEY (`collid`)  REFERENCES `omcollections` (`CollID`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_imageproject_uid`  FOREIGN KEY (`uidcreated`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `institutions` 
  ADD COLUMN `institutionID` VARCHAR(45) NULL AFTER `iid`;

# Modify the institutions table so that some fields can hold more data
# This is to allow extra content from GrSciColl/Index Herbariorum (e.g., multiple contacts)
ALTER TABLE `institutions` 
  CHANGE COLUMN `InstitutionName2` `InstitutionName2` VARCHAR(255) NULL DEFAULT NULL,
  CHANGE COLUMN `Phone` `Phone` VARCHAR(100) NULL DEFAULT NULL,
  CHANGE COLUMN `Contact` `Contact` VARCHAR(255) NULL DEFAULT NULL,
  CHANGE COLUMN `Email` `Email` VARCHAR(255) NULL DEFAULT NULL,
  CHANGE COLUMN `Notes` `Notes` VARCHAR(19500) NULL DEFAULT NULL;


ALTER TABLE `omcollections` 
  CHANGE COLUMN `CollID` `collID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `InstitutionCode` `institutionCode` VARCHAR(45) NOT NULL ,
  CHANGE COLUMN `CollectionCode` `collectionCode` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `CollectionName` `collectionName` VARCHAR(150) NOT NULL ,
  CHANGE COLUMN `collectionId` `collectionID` VARCHAR(100) NULL DEFAULT NULL ,
  CHANGE COLUMN `fulldescription` `fullDescription` VARCHAR(2000) NULL DEFAULT NULL ,
  CHANGE COLUMN `Homepage` `homepage` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `IndividualUrl` `individualUrl` VARCHAR(500) NULL DEFAULT NULL ,
  CHANGE COLUMN `latitudedecimal` `latitudeDecimal` DOUBLE(8,6) NULL DEFAULT NULL ,
  CHANGE COLUMN `longitudedecimal` `longitudeDecimal` DOUBLE(9,6) NULL DEFAULT NULL ,
  CHANGE COLUMN `CollType` `collType` VARCHAR(45) NOT NULL DEFAULT 'Preserved Specimens' COMMENT 'Preserved Specimens, General Observations, Observations' ,
  CHANGE COLUMN `ManagementType` `managementType` VARCHAR(45) NULL DEFAULT 'Snapshot' COMMENT 'Snapshot, Live Data' ,
  CHANGE COLUMN `PublicEdits` `publicEdits` INT(1) UNSIGNED NOT NULL DEFAULT 1 ,
  CHANGE COLUMN `collectionguid` `collectionGuid` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `securitykey` `securityKey` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `guidtarget` `guidTarget` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `accessrights` `accessRights` VARCHAR(1000) NULL DEFAULT NULL ,
  CHANGE COLUMN `SortSeq` `sortSeq` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `InitialTimeStamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `omcollections` 
  ADD COLUMN `dwcTermJson` TEXT NULL AFTER `aggKeysStr`,
  ADD COLUMN `recordID` VARCHAR(45) NULL AFTER `dwcTermJson`;


ALTER TABLE `omoccurdeterminations` 
  DROP FOREIGN KEY `FK_omoccurdets_idby`;

ALTER TABLE `omoccurdeterminations` 
  DROP INDEX `FK_omoccurdets_idby_idx` ;
  
ALTER TABLE `omoccurdeterminations` 
  DROP COLUMN `idbyid`;

DROP TABLE omcollectors;


CREATE TABLE `omcollproperties` (
  `collPropID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `collid` INT UNSIGNED NOT NULL,
  `propCategory` VARCHAR(45) NOT NULL,
  `propTitle` VARCHAR(45) NOT NULL,
  `propJson` LONGTEXT NULL,
  `notes` VARCHAR(255) NULL,
  `modifiedUid` INT UNSIGNED NULL,
  `modifiedTimestamp` DATETIME NULL,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`collPropID`),
  INDEX `FK_omcollproperties_collid_idx` (`collid` ASC),
  INDEX `FK_omcollproperties_uid_idx` (`modifiedUid` ASC),
  CONSTRAINT `FK_omcollproperties_collid`  FOREIGN KEY (`collid`)  REFERENCES `omcollections` (`CollID`)   ON DELETE CASCADE   ON UPDATE CASCADE,
  CONSTRAINT `FK_omcollproperties_uid`   FOREIGN KEY (`modifiedUid`)   REFERENCES `users` (`uid`)   ON DELETE CASCADE   ON UPDATE CASCADE
);


CREATE TABLE `omcrowdsourceproject` (
  `csProjID` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(45) NOT NULL,
  `description` VARCHAR(250) NULL,
  `instructions` TEXT NULL,
  `trainingurl` VARCHAR(250) NULL,
  `managers` VARCHAR(150) NULL,
  `criteria` VARCHAR(1500) NULL,
  `notes` VARCHAR(250) NULL,
  `modifiedUid` INT UNSIGNED NULL,
  `modifiedTimestamp` DATETIME NULL,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`csProjID`)
);

ALTER TABLE `omcrowdsourceproject` 
  ADD INDEX `FK_croudsourceproj_uid_idx` (`modifiedUid` ASC) ;

ALTER TABLE `omcrowdsourceproject`
  ADD CONSTRAINT `FK_croudsourceproj_uid`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `omcrowdsourcequeue` 
  ADD COLUMN `csProjID` INT NULL AFTER `omcsid`,
  ADD INDEX `FK_omcrowdsourcequeue_csProjID_idx` (`csProjID` ASC);

ALTER TABLE `omcrowdsourcequeue` 
  ADD CONSTRAINT `FK_omcrowdsourcequeue_csProjID`  FOREIGN KEY (`csProjID`)  REFERENCES `omcrowdsourceproject` (`csProjID`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `omcrowdsourcequeue` 
  ADD COLUMN `dateProcessed` DATETIME NULL AFTER `isvolunteer`,
  ADD COLUMN `dateReviewed` DATETIME NULL AFTER `dateProcessed`;


CREATE TABLE `omoccuraccess` (
  `occurAccessID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ipaddress` VARCHAR(45) NOT NULL,
  `accessType` VARCHAR(45) NOT NULL,
  `queryStr` TEXT NULL,
  `userAgent` TEXT NULL,
  `frontendGuid` VARCHAR(45) NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`occurAccessID`)
) ENGINE = MyISAM;

CREATE TABLE `omoccuraccesslink` (
  `occurAccessID` BIGINT(20) UNSIGNED NOT NULL,
  `occid` INT UNSIGNED NOT NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`occurAccessID`, `occid`)
) ENGINE = MyISAM;

CREATE TABLE `omoccuraccesssummary` (
  `oasid` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ipaddress` VARCHAR(45) NOT NULL,
  `accessDate` DATE NOT NULL,
  `cnt` INT UNSIGNED NOT NULL,
  `accessType` VARCHAR(45) NOT NULL,
  `queryStr` TEXT NULL,
  `userAgent` TEXT NULL,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`oasid`),
  UNIQUE INDEX `UNIQUE_occuraccess` (`ipaddress` ASC, `accessdate` ASC, `accesstype` ASC)
);

CREATE TABLE `omoccuraccesssummarylink` (
  `oasid` BIGINT(20) UNSIGNED NOT NULL,
  `occid` INT UNSIGNED NOT NULL,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`oasid`, `occid`),
  INDEX `omoccuraccesssummarylink_occid_idx` (`occid` ASC),
  CONSTRAINT `FK_omoccuraccesssummarylink_oasid`  FOREIGN KEY (`oasid`)  REFERENCES `omoccuraccesssummary` (`oasid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccuraccesssummarylink_occid`  FOREIGN KEY (`occid`)  REFERENCES `omoccurrences` (`occid`)  ON DELETE CASCADE  ON UPDATE CASCADE
);

DROP TABLE omoccuraccessstats;


CREATE TABLE `omoccurarchive` (
  `archiveID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `archiveObj` TEXT NOT NULL,
  `occid` INT UNSIGNED NULL,
  `catalogNumber` VARCHAR(45) NULL,
  `occurrenceID` VARCHAR(255) NULL,
  `recordID` VARCHAR(45) NULL,
  `archiveReason` VARCHAR(45) NULL,
  `remarks` VARCHAR(150) NULL,
  `createdUid` INT UNSIGNED NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`archiveID`),
  INDEX `IX_occurarchive_catnum` (`catalogNumber` ASC),
  INDEX `IX_occurarchive_occurrenceID` (`occurrenceID` ASC),
  INDEX `IX_occurarchive_recordID` (`recordID` ASC),
  INDEX `FK_occurarchive_uid_idx` (`createdUid` ASC),
  UNIQUE INDEX `UQ_occurarchive_occid` (`occid` ASC),
  CONSTRAINT `FK_occurarchive_uid` FOREIGN KEY (`createdUid`)  REFERENCES `users` (`uid`)  ON DELETE RESTRICT  ON UPDATE CASCADE
);

INSERT INTO omoccurarchive(archiveObj, occid, recordID)
SELECT archiveObj, occid, guid FROM guidoccurrences WHERE archiveObj IS NOT NULL;

UPDATE omoccurarchive SET occid = SUBSTRING_INDEX(SUBSTRING(archiveObj, 11), '"', 1) WHERE occid IS NULL;

ALTER TABLE `omoccurassociations` 
  ADD COLUMN `relationshipID` VARCHAR(45) NULL COMMENT 'dwc:relationshipOfResourceID (e.g. ontology link)' AFTER `relationship`,
  ADD COLUMN `accordingTo` VARCHAR(45) NULL COMMENT 'dwc:relationshipAccordingTo (verbatim text)' AFTER `notes`,
  ADD COLUMN `sourceIdentifier` VARCHAR(45) NULL COMMENT 'dwc:resourceRelationshipID, if association was defined externally ' AFTER `accordingTo`,
  ADD COLUMN `recordID` VARCHAR(45) NULL COMMENT 'dwc:resourceRelationshipID, if association was defined internally ' AFTER `sourceIdentifier`,
  CHANGE COLUMN `condition` `conditionOfAssociate` VARCHAR(250) NULL DEFAULT NULL,
  CHANGE COLUMN `relationship` `relationship` VARCHAR(150) NOT NULL COMMENT 'dwc:relationshipOfResource',
  CHANGE COLUMN `identifier` `identifier` VARCHAR(250) NULL DEFAULT NULL COMMENT 'dwc:relatedResourceID (object identifier)',
  CHANGE COLUMN `resourceUrl` `resourceUrl` VARCHAR(250) NULL DEFAULT NULL COMMENT 'link to resource',
  CHANGE COLUMN `dateEmerged` `establishedDate` DATETIME NULL DEFAULT NULL COMMENT 'dwc:relationshipEstablishedDate',
  CHANGE COLUMN `notes` `notes` VARCHAR(250) NULL DEFAULT NULL COMMENT 'dwc:relationshipRemarks';
  

ALTER TABLE `omoccurdatasets` 
  CHANGE COLUMN `datasetid` `datasetID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  ADD COLUMN `datasetIdentifier` VARCHAR(150) NULL AFTER `description`,
  ADD COLUMN `datasetName` VARCHAR(150) NULL AFTER `datasetID`,
  ADD COLUMN `bibliographicCitation` VARCHAR(500) NULL AFTER `datasetName`,
  ADD COLUMN `dynamicProperties` TEXT NULL DEFAULT NULL AFTER `notes`,
  CHANGE COLUMN `sortsequence` `sortSequence` INT(11) NULL DEFAULT NULL,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;


ALTER TABLE `omoccurdeterminations` 
  ADD COLUMN `identifiedByAgentID` BIGINT NULL AFTER `identifiedBy`,
  ADD COLUMN `identifiedByID` VARCHAR(45) NULL AFTER `identifiedByAgentID`,
  ADD COLUMN `higherClassification` VARCHAR(150) NULL AFTER `dateIdentifiedInterpreted`,
  ADD COLUMN `verbatimIdentification` VARCHAR(250) NULL AFTER `sciname`,
  ADD COLUMN `genus` VARCHAR(45) NULL AFTER `identificationQualifier`,
  ADD COLUMN `specificEpithet` VARCHAR(45) NULL AFTER `genus`,
  ADD COLUMN `verbatimTaxonRank` VARCHAR(45) NULL AFTER `specificEpithet`,
  ADD COLUMN `taxonRank` VARCHAR(45) NULL AFTER `verbatimTaxonRank`,
  ADD COLUMN `infraSpecificEpithet` VARCHAR(45) NULL AFTER `taxonRank`,
  ADD COLUMN `securityStatus` INT NOT NULL DEFAULT 0 AFTER `appliedStatus`,
  ADD COLUMN `securityStatusReason` VARCHAR(100) NULL AFTER `securityStatus`,
  ADD COLUMN `identificationVerificationStatus` VARCHAR(45) NULL AFTER `taxonRemarks`,
  ADD COLUMN `taxonConceptID` VARCHAR(45) NULL AFTER `identificationVerificationStatus`,
  ADD COLUMN `recordID` VARCHAR(45) NULL AFTER `sortSequence`,
  CHANGE COLUMN `identifiedBy` `identifiedBy` VARCHAR(255) NOT NULL DEFAULT '' ,
  CHANGE COLUMN `dateIdentified` `dateIdentified` VARCHAR(45) NOT NULL DEFAULT '' ,
  ADD INDEX `FK_omoccurdets_agentID_idx` (`identifiedByAgentID` ASC);

ALTER TABLE `omoccurdeterminations` 
  CHANGE COLUMN `family` `family` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `sciname` `sciname` VARCHAR(255) NOT NULL ,
  CHANGE COLUMN `scientificNameAuthorship` `scientificNameAuthorship` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `identificationQualifier` `identificationQualifier` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `identificationReferences` `identificationReferences` VARCHAR(2000) NULL DEFAULT NULL ,
  CHANGE COLUMN `identificationRemarks` `identificationRemarks` VARCHAR(2000) NULL DEFAULT NULL ,
  CHANGE COLUMN `taxonRemarks` `taxonRemarks` VARCHAR(2000) NULL DEFAULT NULL;

ALTER TABLE `omoccurdeterminations` 
  ADD CONSTRAINT `FK_omoccurdets_agentID`  FOREIGN KEY (`identifiedByAgentID`)  REFERENCES `agents` (`agentID`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `omoccurdeterminations` 
  ADD COLUMN `enteredByUid` INT UNSIGNED NULL AFTER `recordID`,
  ADD COLUMN `dateLastModified` TIMESTAMP NULL AFTER `enteredByUid`,
  ADD INDEX `FK_omoccurdets_uid_idx` (`enteredByUid` ASC);

ALTER TABLE `omoccurdeterminations` 
  ADD CONSTRAINT `FK_omoccurdets_uid`  FOREIGN KEY (`enteredByUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `omoccurdeterminations` 
  ADD INDEX `IX_omoccurdets_recordID` (`recordID` ASC),
  ADD INDEX `FK_omoccurdets_dateModified` (`dateLastModified` ASC),
  ADD INDEX `FK_omoccurdets_initialTimestamp` (`initialTimestamp` ASC);

# Transfer GUIDs from old guid determination table  
UPDATE omoccurdeterminations d INNER JOIN guidoccurdeterminations g ON d.detid = g.detid SET d.recordID = g.guid WHERE d.recordID IS NULL;

# Transfer current determinations from omoccurrences table
INSERT IGNORE INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, family, sciname, verbatimIdentification, scientificNameAuthorship, tidInterpreted, 
identificationQualifier, genus, specificEpithet, verbatimTaxonRank, infraSpecificEpithet, isCurrent, identificationReferences, identificationRemarks, 
taxonRemarks)
SELECT occid, IFNULL(identifiedBy, "unknown"), IFNULL(dateIdentified, "s.d."), family, IFNULL(sciname, "undefined"), scientificName, scientificNameAuthorship, tidInterpreted, identificationQualifier, 
genus, specificEpithet, taxonRank, infraSpecificEpithet, 1 as isCurrent, identificationReferences, identificationRemarks, taxonRemarks
FROM omoccurrences;

INSERT IGNORE INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, family, sciname, verbatimIdentification, scientificNameAuthorship, tidInterpreted, 
identificationQualifier, genus, specificEpithet, verbatimTaxonRank, infraSpecificEpithet, isCurrent, identificationReferences, identificationRemarks, 
taxonRemarks)
SELECT o.occid, IFNULL(o.identifiedBy, "unknown"), IFNULL(o.dateIdentified, "s.d."), o.family, IFNULL(o.sciname, "undefined"), o.scientificName, o.scientificNameAuthorship, o.tidInterpreted, 
o.identificationQualifier, o.genus, o.specificEpithet, o.taxonRank, o.infraSpecificEpithet, 1 as isCurrent, o.identificationReferences, o.identificationRemarks, 
o.taxonRemarks
FROM omoccurrences o LEFT JOIN omoccurdeterminations d ON o.occid = d.occid
WHERE d.occid IS NULL;


ALTER TABLE `omoccuredits` 
  CHANGE COLUMN `FieldName` `fieldName` VARCHAR(45) NOT NULL ,
  CHANGE COLUMN `FieldValueNew` `fieldValueNew` TEXT NOT NULL ,
  CHANGE COLUMN `FieldValueOld` `fieldValueOld` TEXT NOT NULL ,
  CHANGE COLUMN `ReviewStatus` `reviewStatus` INT(1) NOT NULL DEFAULT 1 COMMENT '1=Open;2=Pending;3=Closed' ,
  CHANGE COLUMN `AppliedStatus` `appliedStatus` INT(1) NOT NULL DEFAULT 0 COMMENT '0=Not Applied;1=Applied' ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `omoccuredits` 
  ADD COLUMN `isActive` INT(1) NULL DEFAULT NULL COMMENT '0 = not the value applied within the active field, 1 = valued applied within active field' AFTER `editType`,
  ADD COLUMN `reapply` INT(1) NULL COMMENT '0 = do not reapply edit; 1 = reapply edit when snapshot is refreshed, if edit isActive and snapshot value still matches old value ' AFTER `isActive`;

ALTER TABLE `omoccuredits` 
  DROP FOREIGN KEY `fk_omoccuredits_uid`;

ALTER TABLE `omoccuredits` 
  ADD CONSTRAINT `fk_omoccuredits_uid`  FOREIGN KEY (`uid`)  REFERENCES `users` (`uid`)  ON DELETE RESTRICT  ON UPDATE CASCADE;

ALTER TABLE `omoccuredits` 
  ADD INDEX `IX_omoccuredits_timestamp` (`initialtimestamp` ASC);

ALTER TABLE `omoccuredits` 
  ADD COLUMN `tableName` VARCHAR(45) NULL AFTER `occid`;

ALTER TABLE `omoccuredits` 
  ADD INDEX `FK_omoccuredits_tableName` (`tableName` ASC),
  ADD INDEX `FK_omoccuredits_fieldName` (`fieldName` ASC),
  ADD INDEX `FK_omoccuredits_reviewedStatus` (`reviewStatus` ASC),
  ADD INDEX `FK_omoccuredits_appliedStatus` (`appliedStatus` ASC);


UPDATE omoccuridentifiers SET identifiername = "" WHERE identifiername IS NULL;

ALTER TABLE `omoccuridentifiers` 
  CHANGE COLUMN `identifiername` `identifiername` VARCHAR(45) NOT NULL DEFAULT '' COMMENT 'barcode, accession number, old catalog number, NPS, etc' ;

ALTER TABLE `omoccuridentifiers` 
  ADD UNIQUE INDEX `UQ_omoccuridentifiers` (`occid` ASC, `identifiervalue` ASC, `identifiername` ASC);

ALTER TABLE `omoccuridentifiers` 
  DROP INDEX `Index_value` ;

ALTER TABLE `omoccuridentifiers` 
  ADD INDEX `IX_omoccuridentifiers_value` (`identifiervalue` ASC);


# Table for correspondance attachments for loans/exchanges/gifts etc.
CREATE TABLE `omoccurloansattachment` (
  `attachmentid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `loanid` int(10) UNSIGNED DEFAULT NULL,
  `exchangeid` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(80) NOT NULL,
  `path` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attachmentid`),
  KEY `FK_occurloansattachment_loanid_idx` (`loanid`),
  KEY `FK_occurloansattachment_exchangeid_idx` (`exchangeid`)
);

ALTER TABLE `omoccurloansattachment`
  ADD CONSTRAINT `FK_occurloansattachment_exchangeid` FOREIGN KEY (`exchangeid`) REFERENCES `omoccurexchange` (`exchangeid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_occurloansattachment_loanid` FOREIGN KEY (`loanid`) REFERENCES `omoccurloans` (`loanid`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `omoccurrences` 
  DROP FOREIGN KEY `FK_omoccurrences_recbyid`;

ALTER TABLE `omoccurrences` 
  DROP COLUMN `recordedbyid`,
  DROP INDEX `FK_recordedbyid` ;

ALTER TABLE `omoccurrences` 
  DROP INDEX `Index_latlng`,
  ADD INDEX `IX_occurrences_lat` (`decimalLatitude` ASC),
  ADD INDEX `IX_occurrences_lng` (`decimalLongitude` ASC);

ALTER TABLE `omoccurrences` 
  DROP FOREIGN KEY `FK_omoccurrences_tid`,
  DROP FOREIGN KEY `FK_omoccurrences_uid`;

ALTER TABLE `omoccurrences` 
  ADD COLUMN `recordID` VARCHAR(45) NULL AFTER `dynamicFields`,
  CHANGE COLUMN `tidinterpreted` `tidInterpreted` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `fieldnumber` `fieldNumber` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `genericcolumn1` `genericColumn1` VARCHAR(100) NULL DEFAULT NULL ,
  CHANGE COLUMN `genericcolumn2` `genericColumn2` VARCHAR(100) NULL DEFAULT NULL ,
  CHANGE COLUMN `observeruid` `observerUid` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `processingstatus` `processingStatus` VARCHAR(45) NULL DEFAULT NULL ;

UPDATE omoccurrences o INNER JOIN guidoccurrences g ON o.occid = g.occid SET o.recordID = g.guid WHERE o.recordID IS NULL;

ALTER TABLE `omoccurrences` 
  ADD INDEX `IX_omoccurrences_recordID` (`recordID` ASC);

ALTER TABLE `omoccurrences` 
  ADD CONSTRAINT `FK_omoccurrences_tid`  FOREIGN KEY (`tidInterpreted`)  REFERENCES `taxa` (`tid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_omoccurrences_uid`  FOREIGN KEY (`observerUid`)  REFERENCES `users` (`uid`);


#DROP TABLE IF EXISTS `portaloccurrences`
#DROP TABLE IF EXISTS `portalpublications`
#DROP TABLE IF EXISTS `portalindex`
CREATE TABLE `portalindex` (
  `portalID` int(11) NOT NULL AUTO_INCREMENT,
  `portalName` varchar(150) NOT NULL,
  `acronym` varchar(45) DEFAULT NULL,
  `portalDescription` varchar(250) DEFAULT NULL,
  `urlRoot` varchar(150) NOT NULL,
  `securityKey` varchar(45) DEFAULT NULL,
  `symbiotaVersion` varchar(45) DEFAULT NULL,
  `guid` varchar(45) DEFAULT NULL,
  `manager` varchar(45) DEFAULT NULL,
  `managerEmail` varchar(45) DEFAULT NULL,
  `primaryLead` varchar(45) DEFAULT NULL,
  `primaryLeadEmail` varchar(45) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialTimestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`portalID`),
  UNIQUE KEY `UQ_portalIndex_guid` (`guid`)
);

CREATE TABLE `portalpublications` (
  `pubid` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pubTitle` varchar(45) NOT NULL,
  `description` varchar(250) DEFAULT NULL,
  `guid` VARCHAR(45) NULL,
  `collid` int(10) unsigned NULL,
  `portalID` int(11) NOT NULL,
  `direction` varchar(45) NOT NULL,
  `criteriaJson` text DEFAULT NULL,
  `includeDeterminations` int(11) DEFAULT 1,
  `includeImages` int(11) DEFAULT 1,
  `autoUpdate` int(11) DEFAULT 1,
  `lastDateUpdate` datetime DEFAULT NULL,
  `updateInterval` int(11) DEFAULT NULL,
  `createdUid` int(10) unsigned DEFAULT NULL,
  `initialTimestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pubid`),
  KEY `FK_portalpub_collid_idx` (`collid`),
  KEY `FK_portalpub_portalID_idx` (`portalID`),
  KEY `FK_portalpub_uid_idx` (`createdUid`),
  UNIQUE INDEX `UQ_portalpub_guid` (`guid` ASC),
  CONSTRAINT `FK_portalpub_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`collID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_portalpub_createdUid` FOREIGN KEY (`createdUid`) REFERENCES `users` (`uid`) ON UPDATE CASCADE,
  CONSTRAINT `FK_portalpub_portalID` FOREIGN KEY (`portalID`) REFERENCES `portalindex` (`portalID`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `portaloccurrences` (
  `portalOccurrencesID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `pubid` int(10) unsigned NOT NULL,
  `remoteOccid` int(11) NULL DEFAULT NULL,
  `verification` int(11) NOT NULL DEFAULT 0,
  `refreshTimestamp` datetime NOT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`portalOccurrencesID`),
  KEY `FK_portalOccur_occid_idx` (`occid`),
  KEY `FK_portalOccur_pubID_idx` (`pubid`),
  UNIQUE INDEX `UQ_portalOccur_occid_pubid` (`occid` ASC, `pubid` ASC),
  CONSTRAINT `FK_portalOccur_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_portalOccur_pubid` FOREIGN KEY (`pubid`) REFERENCES `portalpublications` (`pubid`) ON DELETE CASCADE ON UPDATE CASCADE
);


ALTER TABLE `specprocessorprojects` 
  ADD COLUMN `additionalOptions` TEXT NULL AFTER `createLgImg`,
  ADD COLUMN `createdByUid` INT UNSIGNED NULL AFTER `lastRunDate`,
  ADD COLUMN `processingCode` INT NULL AFTER `source`,
  CHANGE COLUMN `projecttype` `projectType` VARCHAR(45) NULL DEFAULT NULL,
  CHANGE COLUMN `speckeyretrieval` `specKeyRetrieval` VARCHAR(45) NULL DEFAULT NULL,
  CHANGE COLUMN `lastrundate` `lastRunDate` DATE NULL DEFAULT NULL,
  ADD INDEX `FK_specprocprojects_uid_idx` (`createdByUid` ASC);

ALTER TABLE `specprocessorprojects`
  ADD CONSTRAINT `FK_specprocprojects_uid`  FOREIGN KEY (`createdByUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;


ALTER TABLE `taxa` 
  CHANGE COLUMN `TID` `tid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `RankId` `rankID` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `SciName` `sciName` VARCHAR(250) NOT NULL ,
  CHANGE COLUMN `UnitInd1` `unitInd1` VARCHAR(1) NULL DEFAULT NULL ,
  CHANGE COLUMN `UnitName1` `unitName1` VARCHAR(50) NOT NULL ,
  CHANGE COLUMN `UnitInd2` `unitInd2` VARCHAR(1) NULL DEFAULT NULL ,
  CHANGE COLUMN `UnitName2` `unitName2` VARCHAR(50) NULL DEFAULT 't' ,
  CHANGE COLUMN `UnitName3` `unitName3` VARCHAR(35) NULL DEFAULT NULL ,
  CHANGE COLUMN `PhyloSortSequence` `phyloSortSequence` TINYINT(3) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `Source` `source` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `Notes` `notes` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `Hybrid` `hybrid` VARCHAR(50) NULL DEFAULT NULL ,
  CHANGE COLUMN `SecurityStatus` `securityStatus` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = no security; 1 = hidden locality' ;

UPDATE IGNORE taxa SET author = "" WHERE author IS NULL;

ALTER TABLE `taxa` 
  CHANGE COLUMN `Author` `author` VARCHAR(150) NOT NULL DEFAULT '';

UPDATE taxa SET kingdomName = "" WHERE kingdomName IS NULL;

UPDATE taxa SET rankID = 0 WHERE rankID IS NULL;

ALTER TABLE `taxa` 
  CHANGE COLUMN `kingdomName` `kingdomName` VARCHAR(45) NOT NULL DEFAULT '' ,
  CHANGE COLUMN `rankID` `rankID` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0 ;

ALTER TABLE `taxa` 
  DROP INDEX `sciname_unique`;

# Following statement is the default unique index in the taxa table to support a multi-kingdom portal 
# If UNIQUE INDEX fails, run following query below to identify duplicate records. Duplicates can be deleted, or you can apply the alternate index that supports the existance of homonyms 
# Duplicate check: SELECT sciname, rankID, kingdomName, count(*) as cnt FROM taxa GROUP BY sciname, rankID, kingdomName HAVING cnt > 1
ALTER TABLE `taxa` 
  ADD UNIQUE INDEX `UQ_taxa_sciname` (`sciName` ASC, `rankId` ASC, `kingdomName` ASC);

# The default UNIQUE INDEX applied above supports cross-kingdom homonyms
# Alternate UNIQUE INDEX that support homonyms within a single kingdom (not recommended) 
#  ALTER TABLE `taxa` ADD UNIQUE INDEX `UQ_taxa_sciname` (`sciName` ASC, `author` ASC, `rankId` ASC, `kingdomName` ASC)
# Alternate more restrictive UNIQUE INDEX that can be used for a single kingdom portal. Cross-kingdom homonyms are not supported
#  ALTER TABLE `taxa` ADD UNIQUE INDEX `UQ_taxa_sciname` (`sciName` ASC, `rankId` ASC)
  
ALTER TABLE `taxstatus` 
  CHANGE COLUMN `taxonomicSource` `taxonomicSource` VARCHAR(500) NULL DEFAULT NULL;

CREATE TABLE `taxadescrprofile` (
  `tdProfileID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `authors` VARCHAR(100) NULL,
  `caption` VARCHAR(40) NOT NULL,
  `projectDescription` VARCHAR(500) NULL,
  `abstract` TEXT NULL,
  `publication` VARCHAR(500) NULL,
  `urlTemplate` VARCHAR(250) NULL,
  `internalNotes` VARCHAR(250) NULL,
  `langid` INT NULL DEFAULT 1,
  `defaultDisplayLevel` INT NULL DEFAULT 1,
  `dynamicProperties` TEXT NULL,
  `modifiedUid` INT UNSIGNED NULL,
  `modifiedTimestamp` TIMESTAMP NULL,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`tdProfileID`),
  INDEX `FK_taxadescrprofile_langid_idx` (`langid` ASC),
  INDEX `FK_taxadescrprofile_uid_idx` (`modifiedUid` ASC),
  CONSTRAINT `FK_taxadescrprofile_langid`  FOREIGN KEY (`langid`)  REFERENCES `adminlanguages` (`langid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  CONSTRAINT `FK_taxadescrprofile_uid`  FOREIGN KEY (`modifiedUid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE
);

ALTER TABLE `taxadescrblock` 
  ADD COLUMN `tdProfileID` INT UNSIGNED NULL AFTER `tdbid`,
  ADD INDEX `FK_taxadescrblock_tdProfileID_idx` (`tdProfileID` ASC);

ALTER TABLE `taxadescrblock` 
  ADD CONSTRAINT `FK_taxadescrblock_tdProfileID`  FOREIGN KEY (`tdProfileID`)  REFERENCES `taxadescrprofile` (`tdProfileID`)  ON DELETE RESTRICT  ON UPDATE CASCADE;

INSERT INTO taxadescrprofile(title, caption, langid)
SELECT DISTINCT IFNULL(caption, "Undefined"), IFNULL(caption, "Undefined"), IFNULL(langid, 1)
FROM taxadescrblock;

UPDATE taxadescrblock b INNER JOIN taxadescrprofile p ON IFNULL(b.caption, "Undefined") = p.caption AND IFNULL(b.langid, 1) = p.langid
SET b.tdProfileID = p.tdProfileID 
WHERE b.tdProfileID IS NULL;

ALTER TABLE `taxadescrblock` 
  DROP FOREIGN KEY `FK_taxadescrblock_tdProfileID`;

ALTER TABLE `taxadescrblock` 
  CHANGE COLUMN `tdProfileID` `tdProfileID` INT(10) UNSIGNED NOT NULL ;

ALTER TABLE `taxadescrblock` 
  ADD CONSTRAINT `FK_taxadescrblock_tdProfileID`  FOREIGN KEY (`tdProfileID`)  REFERENCES `taxadescrprofile` (`tdProfileID`)  ON UPDATE CASCADE  ON DELETE CASCADE;

# Test query if following UNIQUE INDEX fails: SELECT url, tid, count(*) as cnt FROM taxalinks GROUP BY url, tid HAVING cnt > 1
ALTER TABLE `taxalinks` 
  ADD INDEX `FK_taxaLinks_tid` (`tid` ASC),
  ADD UNIQUE INDEX `UQ_taxaLinks_tid_url` (`tid` ASC, `url` ASC);

ALTER TABLE `taxalinks` 
  DROP INDEX `Index_unique` ;


ALTER TABLE `uploaddetermtemp` 
  ADD COLUMN `higherClassification` VARCHAR(150) NULL AFTER `dateIdentifiedInterpreted`,
  ADD COLUMN `verbatimIdentification` VARCHAR(250) NULL AFTER `sciname`,
  ADD COLUMN `family` VARCHAR(255) NULL AFTER `identificationQualifier`,
  ADD COLUMN `genus` VARCHAR(45) NULL AFTER `family`,
  ADD COLUMN `specificEpithet` VARCHAR(45) NULL AFTER `genus`,
  ADD COLUMN `verbatimTaxonRank` VARCHAR(45) NULL AFTER `specificEpithet`,
  ADD COLUMN `taxonRank` VARCHAR(45) NULL AFTER `verbatimTaxonRank`,
  ADD COLUMN `infraSpecificEpithet` VARCHAR(45) NULL AFTER `taxonRank`,
  ADD COLUMN `taxonRemarks` VARCHAR(2000) NULL AFTER `identificationRemarks`,
  ADD COLUMN `identificationVerificationStatus` VARCHAR(45) NULL AFTER `taxonRemarks`,
  ADD COLUMN `taxonConceptID` VARCHAR(45) NULL AFTER `identificationVerificationStatus`,
  CHANGE COLUMN `identifiedBy` `identifiedBy` VARCHAR(255) NOT NULL DEFAULT '',
  CHANGE COLUMN `dateIdentified` `dateIdentified` VARCHAR(45) NOT NULL DEFAULT '',
  CHANGE COLUMN `sciname` `sciname` VARCHAR(255) NOT NULL ;


ALTER TABLE `uploadspecparameters` 
  DROP FOREIGN KEY `FK_uploadspecparameters_coll`;

ALTER TABLE `uploadspecparameters` 
  ADD COLUMN `internalQuery` VARCHAR(250) NULL AFTER `schemaName`;

ALTER TABLE `uploadspecparameters` 
  CHANGE COLUMN `CollID` `collid` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `UploadType` `uploadType` INT(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 = Direct; 2 = DiGIR; 3 = File' ,
  CHANGE COLUMN `Platform` `platform` VARCHAR(45) NULL DEFAULT '1' COMMENT '1 = MySQL; 2 = MSSQL; 3 = ORACLE; 11 = MS Access; 12 = FileMaker' ,
  CHANGE COLUMN `Code` `code` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `Path` `path` VARCHAR(500) NULL DEFAULT NULL ,
  CHANGE COLUMN `PkField` `pkField` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `Username` `username` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `Password` `password` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `SchemaName` `schemaName` VARCHAR(150) NULL DEFAULT NULL ,
  CHANGE COLUMN `QueryStr` `queryStr` TEXT NULL DEFAULT NULL ,
  CHANGE COLUMN `cleanupsp` `cleanupSP` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `InitialTimeStamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `uploadspecparameters` 
  ADD CONSTRAINT `FK_uploadspecparameters_coll`  FOREIGN KEY (`collid`)  REFERENCES `omcollections` (`collID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `eventTime` VARCHAR(45) NULL AFTER `verbatimEventDate`,
  ADD COLUMN `observeruid` INT NULL AFTER `language`,
  ADD COLUMN `dateEntered` DATETIME NULL AFTER `recordEnteredBy`,
  ADD COLUMN `eventID` VARCHAR(45) NULL AFTER `fieldnumber`,
  CHANGE COLUMN `taxonRemarks` `taxonRemarks` VARCHAR(2000) NULL DEFAULT NULL ,
  CHANGE COLUMN `identificationReferences` `identificationReferences` VARCHAR(2000) NULL DEFAULT NULL ,
  CHANGE COLUMN `identificationRemarks` `identificationRemarks` VARCHAR(2000) NULL DEFAULT NULL ,
  CHANGE COLUMN `establishmentMeans` `establishmentMeans` VARCHAR(150) NULL DEFAULT NULL,
  CHANGE COLUMN `disposition` `disposition` varchar(250) NULL DEFAULT NULL,
  CHANGE COLUMN `LatestDateCollected` `eventDate2` DATE NULL DEFAULT NULL AFTER `eventDate`;

ALTER TABLE `uploadspectemp` 
  DROP COLUMN `materialSampleID`,
  ADD COLUMN `materialSampleJSON` TEXT NULL AFTER `paleoJSON`;


ALTER TABLE `useraccesstokens` 
  CHANGE COLUMN `tokid` `tokenID` INT(11) NOT NULL AUTO_INCREMENT,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  ADD COLUMN `experationDate` DATETIME NULL AFTER `device`;

ALTER TABLE `users` 
  DROP COLUMN `usergroups`,
  DROP COLUMN `defaultrights`,
  DROP COLUMN `ispublic`,
  DROP COLUMN `Biography`,
  ADD COLUMN `username` VARCHAR(45) NULL AFTER `guid`,
  ADD COLUMN `password` VARCHAR(45) NULL AFTER `username`,
  ADD COLUMN `lastLoginDate` DATETIME NULL AFTER `password`,
  ADD COLUMN `loginModified` DATETIME NULL AFTER `lastLoginDate`,
  CHANGE COLUMN `firstname` `firstName` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `lastname` `lastName` VARCHAR(45) NOT NULL ,
  CHANGE COLUMN `RegionOfInterest` `regionOfInterest` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `rightsholder` `rightsHolder` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `accessrights` `accessrRights` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `validated` `validated` INT NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `InitialTimeStamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `users` 
  ADD UNIQUE INDEX `UQ_users_username` (`username` ASC);
  
ALTER TABLE `users` 
  DROP INDEX `Index_email` ;
  
ALTER TABLE `users` 
  ADD INDEX `IX_users_email` (`lastName` ASC, `email` ASC);

UPDATE users u INNER JOIN userlogin l ON u.uid = l.uid
SET u.username = l.username, u.password = l.password, u.lastLoginDate = l.lastlogindate
WHERE u.username IS NULL;

ALTER TABLE `userroles` 
  DROP FOREIGN KEY `FK_userrole_uid_assigned`;

ALTER TABLE `userroles` 
  CHANGE COLUMN `userroleid` `userRoleID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `tablename` `tableName` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `tablepk` `tablePK` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `uidassignedby` `uidAssignedBy` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `userroles` 
  ADD CONSTRAINT `FK_userrole_uid_assigned`  FOREIGN KEY (`uidAssignedBy`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

UPDATE userroles SET tablename = "fmprojects" WHERE tablename = "fmproject";


#Material Sample schema developments
CREATE TABLE `ommaterialsample` (
  `matSampleID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `occid` INT UNSIGNED NOT NULL,
  `sampleType` VARCHAR(45) NOT NULL,
  `catalogNumber` VARCHAR(45) NULL,
  `guid` VARCHAR(150) NULL,
  `sampleCondition` VARCHAR(45) NULL,
  `disposition` VARCHAR(45) NULL,
  `preservationType` VARCHAR(45) NULL,
  `preparationDetails` VARCHAR(250) NULL,
  `preparationDate` DATE NULL,
  `preparedByUid` INT UNSIGNED NULL,
  `individualCount` VARCHAR(45) NULL,
  `sampleSize` VARCHAR(45) NULL,
  `storageLocation` VARCHAR(45) NULL,
  `remarks` VARCHAR(250) NULL,
  `dynamicFields` JSON NULL,
  `recordID` VARCHAR(45) NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`matSampleID`),
  INDEX `FK_ommatsample_occid_idx` (`occid` ASC),
  INDEX `FK_ommatsample_prepUid_idx` (`preparedByUid` ASC),
  CONSTRAINT `FK_ommatsample_occid` FOREIGN KEY (`occid`)   REFERENCES `omoccurrences` (`occid`)   ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_ommatsample_prepUid`   FOREIGN KEY (`preparedByUid`)   REFERENCES `users` (`uid`)   ON DELETE CASCADE  ON UPDATE CASCADE
);

ALTER TABLE `ommaterialsample`
  ADD UNIQUE INDEX `UQ_ommatsample_recordID` (`recordID`);

# If following ALTER TABLE statement fails, run query below to identify records with duplicate material sample catalogNumbers for a given occurrence record. 
# Catalog numbers need to be unique for each occurrence, thus duplicate records need to be removed.  
# FIX: SELECT occid, catalogNumber, count(*) as cnt FROM ommaterialsample WHERE catalogNumber IS NOT NULL GROUP BY occid, catalogNumber HAVING cnt > 1
ALTER TABLE `ommaterialsample`
  ADD UNIQUE INDEX `UQ_ommatsample_catNum` (`occid`, `catalogNumber`);

# If following ALTER TABLE statement fails, run query below to identify records with duplicate material sample guid values for a given occurrence record. GUIDs need to be unique for each occurrence. The duplicate records need to be removed.  
# FIX: SELECT occid, guid, count(*) as cnt FROM ommaterialsample WHERE guid IS NOT NULL GROUP BY occid, guid HAVING cnt > 1
ALTER TABLE `ommaterialsample`
  ADD UNIQUE INDEX `UQ_ommatsample_guid` (`occid`, `guid`);


INSERT INTO ctcontrolvocab(title,tableName,fieldName, limitToList)
  VALUES("Material Sample Type","ommaterialsample","sampleType",1);

INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "tissue", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "sampleType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "culture strain", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "sampleType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "specimen", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "sampleType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "DNA", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "sampleType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "RNA", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "sampleType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "Protein", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "sampleType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "Skin", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "sampleType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "Skull", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "sampleType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "liver", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "sampleType";

INSERT INTO ctcontrolvocab(title,tableName,fieldName, limitToList)
  VALUES("Material Sample Type","ommaterialsample","disposition",1);

INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "being processed", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "disposition";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "in collection", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "disposition";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "deaccessioned", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "disposition";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "consumed", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "disposition";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "discarded", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "disposition";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "missing", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "disposition";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "on exhibit", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "disposition";

INSERT INTO ctcontrolvocab(title,tableName,fieldName, limitToList)
  VALUES("Material Sample Type","ommaterialsample","preservationType",1);

INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "alsever's solution", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "arsenic", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "Bouin's solution", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "buffer", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "cleared", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "carbonization", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "DMSO/EDTA", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "DESS", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "DMSO", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "desiccated", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "dry", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "ethanol 95%", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "ethanol 80%", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "ethanol 75%", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "ethanol 70%", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "EDTA", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "sampleDesignation", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "Frozen -20°C", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "Frozen -80°C", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "Frozen -196°C", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";
INSERT INTO ctcontrolvocabterm(cvID, term, activeStatus) SELECT cvID, "Liquid Nitrogen", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsample" AND fieldName = "preservationType";


CREATE TABLE `ommaterialsampleextended` (
  `matSampleExtendedID` INT NOT NULL AUTO_INCREMENT,
  `matSampleID` INT UNSIGNED NOT NULL,
  `fieldName` VARCHAR(45) NOT NULL,
  `fieldValue` VARCHAR(250) NOT NULL,
  `fieldUnits` VARCHAR(45) NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`matSampleExtendedID`),
  INDEX `FK_matsampleextend_matSampleID_idx` (`matSampleID` ASC),
  INDEX `IX_matsampleextend_fieldName` (`fieldName` ASC),
  INDEX `IX_matsampleextend_fieldValue` (`fieldValue` ASC),
  CONSTRAINT `FK_matsampleextend_matSampleID`  FOREIGN KEY (`matSampleID`)   REFERENCES `ommaterialsample` (`matSampleID`)   ON DELETE CASCADE   ON UPDATE CASCADE
);


INSERT INTO ctcontrolvocab(title,tableName,fieldName, limitToList)
  VALUES("Material Sample Type","ommaterialsampleextended","fieldName",0);

INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "concentration", "http://data.ggbn.org/schemas/ggbn/terms/concentration", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "concentrationMethod", "http://data.ggbn.org/schemas/ggbn/terms/methodDeterminationConcentrationAndRatios", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "ratioOfAbsorbance260_230", "http://data.ggbn.org/schemas/ggbn/terms/ratioOfAbsorbance260_230", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "ratioOfAbsorbance260_280", "http://data.ggbn.org/schemas/ggbn/terms/ratioOfAbsorbance260_280", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "volume", "http://data.ggbn.org/schemas/ggbn/terms/volume", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "weight", "http://data.ggbn.org/schemas/ggbn/terms/weight", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "weightMethod", "http://data.ggbn.org/schemas/ggbn/terms/methodDeterminationWeight", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "purificationMethod", "http://data.ggbn.org/schemas/ggbn/terms/purificationMethod", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "quality", "http://data.ggbn.org/schemas/ggbn/terms/quality", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "qualityRemarks", "http://data.ggbn.org/schemas/ggbn/terms/qualityRemarks", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "qualityCheckDate", "http://data.ggbn.org/schemas/ggbn/terms/qualityCheckDate", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "sieving", "http://gensc.org/ns/mixs/sieving", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "dnaHybridization", "http://data.ggbn.org/schemas/ggbn/terms/DNADNAHybridization", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "dnaMeltingPoint", "http://data.ggbn.org/schemas/ggbn/terms/DNAMeltingPoint", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "estimatedSize", "http://gensc.org/ns/mixs/estimated_size", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "poolDnaExtracts", "http://gensc.org/ns/mixs/pool_dna_extracts", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";
INSERT INTO ctcontrolvocabterm(cvID, term, resourceUrl, activeStatus) SELECT cvID, "sampleDesignation", "http://data.ggbn.org/schemas/ggbn/terms/sampleDesignation", 1 FROM ctcontrolvocab WHERE tableName = "ommaterialsampleextended" AND fieldName = "fieldName";


#Rename for archive various deprecated tables 
ALTER TABLE `guidimages` 
  RENAME TO  `deprecated_guidimages` ;

ALTER TABLE `guidoccurrences` 
  RENAME TO  `deprecated_guidoccurrences` ;

ALTER TABLE `guidoccurdeterminations` 
  RENAME TO  `deprecated_guidoccurdeterminations` ;

ALTER TABLE `userlogin` 
  RENAME TO  `deprecated_userlogin` ;

ALTER TABLE `adminstats` 
  RENAME TO  `deprecated_adminstats` ;

ALTER TABLE `imageannotations` 
  RENAME TO  `deprecated_imageannotations` ;

ALTER TABLE `kmdescrdeletions` 
  RENAME TO  `deprecated_kmdescrdeletions` ;

ALTER TABLE `media` 
  RENAME TO  `deprecated_media` ;

ALTER TABLE `omcollsecondary` 
  RENAME TO  `deprecated_omcollsecondary` ;

ALTER TABLE `unknowncomments` 
  RENAME TO  `deprecated_unknowncomments` ;

ALTER TABLE `unknownimages` 
  RENAME TO  `deprecated_unknownimages` ;

ALTER TABLE `unknowns` 
  RENAME TO  `deprecated_unknowns` ;

ALTER TABLE `omcollpublications` 
  RENAME TO  `deprecated_omcollpublications` ;

ALTER TABLE `omcollpuboccurlink` 
  RENAME TO  `deprecated_omcollpuboccurlink` ;
