INSERT INTO `schemaversion` (versionnumber) values ("3.2");

# Add cultivar name and trade name columns to taxa table

ALTER TABLE `taxa` ADD COLUMN `cultivarEpithet` VARCHAR(50) NULL AFTER unitName3;
ALTER TABLE `taxa` ADD COLUMN `tradeName` VARCHAR(50) NULL AFTER cultivarEpithet;

#Add cultivar and trade name to uploadspectemp

ALTER TABLE `uploadspectemp` ADD COLUMN `cultivarEpithet` VARCHAR(50) NULL AFTER infraspecificEpithet;
ALTER TABLE `uploadspectemp` ADD COLUMN `tradeName` VARCHAR(50) NULL AFTER cultivarEpithet;

ALTER TABLE `uploadtaxa` ADD COLUMN `cultivarEpithet` VARCHAR(50) NULL AFTER `UnitName3`;
ALTER TABLE `uploadtaxa` ADD COLUMN `tradeName` VARCHAR(50) NULL AFTER `cultivarEpithet`;

# Rename cultivated to cultivar

update taxonunits set rankname='Cultivar' where rankname='Cultivated';


-- Drop deprecated_media foreign keys to avoid conflicts 
ALTER TABLE `deprecated_media` 
  DROP FOREIGN KEY `FK_media_uid`,
  DROP FOREIGN KEY `FK_media_taxa`,
  DROP FOREIGN KEY `FK_media_occid`;

ALTER TABLE `deprecated_media` 
  DROP INDEX `FK_media_uid_idx` ,
  DROP INDEX `FK_media_occid_idx` ,
  DROP INDEX `FK_media_taxa_idx` ;

-- Define media
DROP TABLE IF EXISTS `media`;
CREATE TABLE `media` (
  `mediaID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `url` varchar(250) DEFAULT NULL,
  `thumbnailUrl` varchar(255) DEFAULT NULL, 
  `originalUrl` varchar(255) DEFAULT NULL,
  `archiveUrl` varchar(255) DEFAULT NULL,
  `sourceUrl` varchar(250) DEFAULT NULL,
  `referenceUrl` varchar(255) DEFAULT NULL,
  `mediaType` varchar(45) DEFAULT NULL,
  `imageType` varchar(50) DEFAULT NULL,
  `format` varchar(45) DEFAULT NULL,
  `caption` varchar(250) DEFAULT NULL,
  `creatorUid` int(10) unsigned DEFAULT NULL,
  `creator` varchar(45) DEFAULT NULL,
  `owner` varchar(250) DEFAULT NULL,
  `locality` varchar(250) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `anatomy` varchar(100) DEFAULT NULL,
  `notes` varchar(350) DEFAULT NULL,
  `username` varchar(45) DEFAULT NULL,
  `sourceIdentifier` varchar(150) DEFAULT NULL,
  `mediaMD5` varchar(45) DEFAULT NULL,
  `hashValue` varchar(45) DEFAULT NULL,
  `hashFunction` varchar(45) DEFAULT NULL,
  `pixelYDimension` int(11) DEFAULT NULL,
  `pixelXDimension` int(11) DEFAULT NULL,
  `dynamicProperties` text DEFAULT NULL,
  `defaultDisplay` int(11) DEFAULT NULL,
  `recordID` varchar(45) DEFAULT NULL,
  `copyright` varchar(255) DEFAULT NULL,
  `rights` varchar(255) DEFAULT NULL,
  `accessRights` varchar(255) DEFAULT NULL,
  `sortSequence` int(11) DEFAULT NULL,
  `sortOccurrence` int(11) DEFAULT 5,
  `initialTimestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`mediaID`),
  CONSTRAINT `FK_media_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_media_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`tid`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_creator_uid` FOREIGN KEY (`creatorUid`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO 
	media(mediaID, tid, occid, 
	url, thumbnailUrl, archiveUrl, originalUrl, sourceurl, referenceUrl,
	caption, creatoruid, creator, owner, 
	mediaMD5, format, imagetype,
	locality, notes, anatomy,
	username, sourceIdentifier, 
	hashFunction, hashValue,
	pixelYDimension, pixelXDimension,
	dynamicProperties, defaultDisplay, recordID,
	copyright, rights, accessRights,
	sortSequence, sortOccurrence, 
	initialTimestamp, 
	mediaType) 
	SELECT 
		imgid, tid, occid,  
		url, thumbnailUrl, archiveUrl, originalUrl, sourceurl, referenceUrl,
		caption, photographerUid, photographer, owner,  
		mediaMD5, format,imagetype,
		locality, notes, anatomy,
		username, sourceIdentifier, 
		hashFunction, hashValue,
		pixelYDimension, pixelXDimension,
		dynamicProperties, defaultDisplay, recordID,
		copyright, rights, accessRights,
		sortSequence, sortOccurrence,
		initialTimestamp, 
		"image" as mediaType
	from images;

-- Key names may very depending on database and history
ALTER TABLE imagetag 
  DROP CONSTRAINT IF EXISTS FK_imagetag_imgid,
  DROP CONSTRAINT IF EXISTS FK_imagetag_imgid_idx,
  DROP FOREIGN KEY IF EXISTS `FK_imagetag_tagkey`;

ALTER TABLE `imagetag` 
  CHANGE COLUMN `imagetagid` `imageTagID` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `imgid` `mediaID` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `keyvalue` `keyValue` VARCHAR(30) NOT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `imagetag` 
  ADD CONSTRAINT `FK_imagetag_tagkey` FOREIGN KEY (`keyValue`) REFERENCES `imagetagkey` (`tagkey`)  ON DELETE NO ACTION  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_imagetag_mediaID` FOREIGN KEY (`mediaID`) REFERENCES media(mediaID)  ON DELETE CASCADE  ON UPDATE CASCADE;


ALTER TABLE `imagekeywords` 
  DROP FOREIGN KEY `FK_imagekeywords_imgid`,
  DROP FOREIGN KEY `FK_imagekeyword_uid`,
  DROP INDEX `FK_imagekeyword_uid_idx` ,
  DROP INDEX `FK_imagekeywords_imgid_idx` ;


ALTER TABLE `imagekeywords` 
  CHANGE COLUMN `imgkeywordid` `imgKeywordID` INT(11) NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `imgid` `mediaID` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `uidassignedby` `uidAssignedBy` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP();

ALTER TABLE `imagekeywords` 
  ADD KEY `FK_imagekeywords_mediaID_idx` (`mediaID`),
  ADD KEY `FK_imagekeyword_uid_idx` (`uidAssignedBy`),
  ADD CONSTRAINT `FK_imagekeyword_uid` FOREIGN KEY (`uidAssignedBy`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_imagekeywords_mediaID` FOREIGN KEY (`mediaID`) REFERENCES `media` (`mediaID`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `specprocessorrawlabels` 
  DROP FOREIGN KEY `FK_specproc_images`,
  DROP INDEX `FK_specproc_images` ;

ALTER TABLE `specprocessorrawlabels` 
  CHANGE COLUMN `imgid` `mediaID` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `rawstr` `rawStr` TEXT NOT NULL ,
  CHANGE COLUMN `processingvariables` `processingVariables` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `sortsequence` `sortSequence` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `specprocessorrawlabels` 
  ADD KEY `FK_specproc_media_idx` (`mediaID`),
  ADD CONSTRAINT `FK_specproc_media` FOREIGN KEY (`mediaID`) REFERENCES `media` (`mediaID`)  ON UPDATE CASCADE  ON DELETE CASCADE;


ALTER TABLE `tmattributes` 
  DROP FOREIGN KEY `FK_tmattr_imgid`;

ALTER TABLE `tmattributes` 
  CHANGE COLUMN `imgid` `mediaID` INT(10) UNSIGNED NULL DEFAULT NULL ,
  DROP INDEX `FK_tmattr_imgid_idx` ;

ALTER TABLE `tmattributes` 
  ADD KEY `FK_tmattr_media_idx` (`mediaID`),
  ADD CONSTRAINT `FK_tmattr_media` FOREIGN KEY (`mediaID`) REFERENCES `media` (`mediaID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Fixing after merge 

ALTER TABLE `omoccurrences` 
  ADD FULLTEXT INDEX `FT_omoccurrence_locality` (`locality`),
  ADD FULLTEXT INDEX `FT_omoccurrence_recordedBy` (`recordedBy`),
  DROP INDEX `Index_locality` ;
  
DROP TABLE `omoccurrencesfulltext`;

DROP TRIGGER IF EXISTS `omoccurrences_insert`;

DROP TRIGGER IF EXISTS `omoccurrences_update`;

DROP TRIGGER IF EXISTS `omoccurrences_delete`;


DELIMITER $$

CREATE TRIGGER `omoccurrences_insert` AFTER INSERT ON `omoccurrences`
FOR EACH ROW BEGIN
	IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
		INSERT INTO omoccurpoints (`occid`,`point`) 
		VALUES (NEW.`occid`,Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`));
	END IF;
END$$

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
END$$

CREATE TRIGGER `omoccurrences_delete` BEFORE DELETE ON `omoccurrences`
FOR EACH ROW BEGIN
	DELETE FROM omoccurpoints WHERE `occid` = OLD.`occid`;
END$$

DELIMITER ;

DROP TRIGGER specprocessorrawlabelsfulltext_insert;
DROP TRIGGER specprocessorrawlabelsfulltext_update;
DROP TRIGGER specprocessorrawlabelsfulltext_delete;
DROP TABLE specprocessorawlabelsfulltext;


ALTER TABLE `omoccuridentifiers`
  CHANGE COLUMN `identifiervalue` `identifierValue` VARCHAR(75) NOT NULL AFTER `occid`,
  CHANGE COLUMN `identifiername` `identifierName` VARCHAR(45) NOT NULL DEFAULT '' AFTER `identifierValue`,
  ADD COLUMN `format` VARCHAR(45) NULL DEFAULT NULL AFTER `identifierName`,
  ADD COLUMN `recordID` VARCHAR(45) NULL DEFAULT NULL AFTER `sortBy`,
  CHANGE COLUMN `modifiedtimestamp` `modifiedTimestamp` DATETIME NULL DEFAULT NULL AFTER `modifiedUid`,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp() AFTER `modifiedTimestamp`,
  DROP INDEX `UQ_omoccuridentifiers`,
  ADD UNIQUE INDEX `UQ_omoccuridentifiers` (`occid`, `identifierValue`, `identifierName`),
  DROP INDEX `IX_omoccuridentifiers_value`,
  ADD INDEX `IX_omoccuridentifiers_value` (`identifierValue`);


ALTER TABLE `kmcharacters` 
  DROP FOREIGN KEY `FK_kmchar_glossary`;

ALTER TABLE `kmcharacters` 
  ADD COLUMN `enteredUid` INT UNSIGNED NULL AFTER `enteredBy`,
  CHANGE COLUMN `charname` `charName` VARCHAR(150) NOT NULL ,
  CHANGE COLUMN `chartype` `charType` VARCHAR(2) NOT NULL DEFAULT 'UM' ,
  CHANGE COLUMN `defaultlang` `defaultLang` VARCHAR(45) NOT NULL DEFAULT 'English' ,
  CHANGE COLUMN `difficultyrank` `difficultyRank` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 1 ,
  CHANGE COLUMN `description` `description` VARCHAR(1500) NULL DEFAULT NULL ,
  CHANGE COLUMN `glossid` `glossID` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `helpurl` `helpUrl` VARCHAR(500) NULL DEFAULT NULL ,
  CHANGE COLUMN `sortsequence` `sortSequence` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `enteredby` `enteredBy` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  DROP INDEX `Index_charname` ,
  DROP INDEX `Index_sort`;

ALTER TABLE `kmcharacters` 
  ADD INDEX `IX_charname` (`charName` ASC),
  ADD INDEX `IX_sort` (`sortSequence` ASC),
  ADD INDEX `FK_kmchar_enteredUid_idx` (`enteredUid` ASC);

ALTER TABLE `kmcharacters` 
  ADD CONSTRAINT `FK_kmchar_glossary`  FOREIGN KEY (`glossID`)  REFERENCES `glossary` (`glossid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_kmchar_enteredUid`  FOREIGN KEY (`enteredUid`)  REFERENCES `users` (`uid`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `kmcharacterlang` 
  DROP FOREIGN KEY `FK_charlang_lang`;

ALTER TABLE `kmcharacterlang` 
  CHANGE COLUMN `charname` `charName` VARCHAR(150) NOT NULL ,
  CHANGE COLUMN `langid` `langID` INT(11) NOT NULL ,
  CHANGE COLUMN `helpurl` `helpUrl` VARCHAR(500) NULL DEFAULT NULL ,
  CHANGE COLUMN `InitialTimeStamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `kmcharacterlang` 
  ADD CONSTRAINT `FK_charlang_lang`  FOREIGN KEY (`langID`)  REFERENCES `adminlanguages` (`langid`)  ON DELETE NO ACTION  ON UPDATE NO ACTION;

ALTER TABLE `kmchardependance` 
  DROP FOREIGN KEY `FK_chardependance_cid`,
  DROP FOREIGN KEY `FK_chardependance_cs`;

ALTER TABLE `kmchardependance` 
  CHANGE COLUMN `CID` `cid` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `CIDDependance` `cidDependance` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `CSDependance` `csDependance` VARCHAR(16) NOT NULL ,
  CHANGE COLUMN `InitialTimeStamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `kmchardependance` 
  ADD CONSTRAINT `FK_chardependance_cid`  FOREIGN KEY (`cid`)  REFERENCES `kmcharacters` (`cid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_chardependance_cs`  FOREIGN KEY (`cidDependance` , `csDependance`)  REFERENCES `kmcs` (`cid` , `cs`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `kmchardependance` 
  ADD COLUMN `charDependID` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`charDependID`),
  ADD INDEX `UQ_charDependance_cid_cidDep_cs` (`cid` ASC, `cidDependance` ASC, `csDependance` ASC);


ALTER TABLE `kmcharheading` 
  DROP FOREIGN KEY `FK_kmcharheading_lang`;

ALTER TABLE `kmcharheading` 
  CHANGE COLUMN `headingname` `headingName` VARCHAR(255) NOT NULL ,
  CHANGE COLUMN `langid` `langID` INT(11) NOT NULL ,
  CHANGE COLUMN `sortsequence` `sortSequence` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `kmcharheading` 
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`hid`),
  DROP INDEX `unique_kmcharheading`,
  DROP INDEX `HeadingName`;
  
ALTER TABLE `kmcharheading` 
  ADD INDEX `FK_kmcharheading_lang_idx` (`langID` ASC),
  ADD INDEX `IX_kmcharheading_name` (`headingName` ASC);
    
ALTER TABLE `kmcharheading` 
  ADD CONSTRAINT `FK_kmcharheading_lang`  FOREIGN KEY (`langID`)  REFERENCES `adminlanguages` (`langid`);

ALTER TABLE `kmcharheadinglang` 
  DROP FOREIGN KEY `FK_kmcharheadinglang_langid`;

ALTER TABLE `kmcharheadinglang` 
  CHANGE COLUMN `langid` `langID` INT(11) NOT NULL ,
  CHANGE COLUMN `headingname` `headingName` VARCHAR(100) NOT NULL ;

ALTER TABLE `kmcharheadinglang`
  ADD CONSTRAINT `FK_kmcharheadinglang_langid`  FOREIGN KEY (`langID`)  REFERENCES `adminlanguages` (`langid`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `kmchartaxalink` 
  DROP FOREIGN KEY `FK_chartaxalink_cid`,
  DROP FOREIGN KEY `FK_chartaxalink_tid`;

ALTER TABLE `kmchartaxalink` 
  CHANGE COLUMN `CID` `cid` INT(10) UNSIGNED NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `TID` `tid` INT(10) UNSIGNED NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `Status` `status` VARCHAR(50) NULL DEFAULT NULL ,
  CHANGE COLUMN `Notes` `notes` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `Relation` `relation` VARCHAR(45) NOT NULL DEFAULT 'include' ,
  CHANGE COLUMN `EditabilityInherited` `editabilityInherited` BIT(1) NULL DEFAULT NULL ,
  CHANGE COLUMN `timestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `kmchartaxalink` 
  ADD COLUMN `charTaxaLinkID` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP INDEX `FK_CharTaxaLink-TID` ,
  ADD INDEX `FK_charTaxaLink_tid_idx` (`tid` ASC),
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`charTaxaLinkID`),
  ADD UNIQUE INDEX `UQ_charTaxaLink_cid_tid` (`cid` ASC, `tid` ASC);

ALTER TABLE `kmchartaxalink` 
  ADD CONSTRAINT `FK_chartaxalink_cid`  FOREIGN KEY (`cid`)  REFERENCES `kmcharacters` (`cid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_chartaxalink_tid`  FOREIGN KEY (`tid`)  REFERENCES `taxa` (`TID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `kmcs` 
  DROP FOREIGN KEY `FK_kmcs_glossid`;

ALTER TABLE `kmcs` 
  CHANGE COLUMN `EnteredBy` `enteredBy` VARCHAR(45) NULL DEFAULT NULL AFTER `sortSequence`,
  CHANGE COLUMN `CharStateName` `charStateName` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `Implicit` `implicit` TINYINT(1) NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `Notes` `notes` LONGTEXT NULL DEFAULT NULL ,
  CHANGE COLUMN `Description` `description` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `IllustrationUrl` `illustrationUrl` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `glossid` `glossID` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `StateID` `stateID` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `SortSequence` `sortSequence` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `InitialTimeStamp` `initialTimeStamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `kmcs` 
  CHANGE COLUMN `stateID` `stateID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`stateID`),
  ADD UNIQUE INDEX `UQ_kmcs_cid_cs` (`cid` ASC, `cs` ASC);

ALTER TABLE `kmcs` 
  DROP INDEX `FK_cs_chars`,
  ADD INDEX `FK_kmcs_cid_idx` (cid);

ALTER TABLE `kmcs` 
  ADD CONSTRAINT `FK_kmcs_glossid`  FOREIGN KEY (`glossID`)  REFERENCES `glossary` (`glossid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `kmcsimages` 
  CHANGE COLUMN `csimgid` `csImgID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `sortsequence` `sortSequence` VARCHAR(45) NOT NULL DEFAULT '50' ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP() ;

ALTER TABLE `kmcslang` 
  DROP FOREIGN KEY `FK_cslang_lang`;

ALTER TABLE `kmcslang` 
  CHANGE COLUMN `charstatename` `charStateName` VARCHAR(150) NOT NULL ,
  CHANGE COLUMN `langid` `langID` INT(11) NOT NULL ,
  CHANGE COLUMN `intialtimestamp` `intialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `kmcslang` 
  ADD CONSTRAINT `FK_cslang_lang`  FOREIGN KEY (`langID`)  REFERENCES `adminlanguages` (`langid`)  ON DELETE NO ACTION  ON UPDATE NO ACTION;

ALTER TABLE `kmdescr` 
  DROP FOREIGN KEY `FK_descr_cs`,
  DROP FOREIGN KEY `FK_descr_tid`;

ALTER TABLE `kmdescr` 
  CHANGE COLUMN `TID` `tid` INT(10) UNSIGNED NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `CID` `cid` INT(10) UNSIGNED NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `Modifier` `modifier` VARCHAR(255) NULL DEFAULT NULL ,
  CHANGE COLUMN `CS` `cs` VARCHAR(16) NOT NULL ,
  CHANGE COLUMN `X` `x` DOUBLE(15,5) NULL DEFAULT NULL ,
  CHANGE COLUMN `TXT` `txt` LONGTEXT NULL DEFAULT NULL ,
  CHANGE COLUMN `PseudoTrait` `pseudoTrait` INT(5) UNSIGNED NULL DEFAULT 0 ,
  CHANGE COLUMN `Frequency` `frequency` INT(5) UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Frequency of occurrence; 1 = rare... 5 = common' ,
  CHANGE COLUMN `Inherited` `inherited` VARCHAR(50) NULL DEFAULT NULL ,
  CHANGE COLUMN `Source` `source` VARCHAR(100) NULL DEFAULT NULL ,
  CHANGE COLUMN `Seq` `seq` INT(10) NULL DEFAULT NULL ,
  CHANGE COLUMN `Notes` `notes` LONGTEXT NULL DEFAULT NULL ,
  CHANGE COLUMN `DateEntered` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `kmdescr` 
  ADD CONSTRAINT `FK_descr_cs`  FOREIGN KEY (`cid` , `cs`)  REFERENCES `kmcs` (`cid` , `cs`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_descr_tid`  FOREIGN KEY (`tid`)  REFERENCES `taxa` (`TID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

#Adjust FK to restrict deletion of record upon deletion of either internal occurrence or createdBy/modifiedBy users  
ALTER TABLE `omoccurassociations`
  DROP FOREIGN KEY `FK_occurassoc_occidassoc`,
  DROP FOREIGN KEY `FK_occurassoc_uidcreated`,
  DROP FOREIGN KEY `FK_occurassoc_uidmodified`;

ALTER TABLE `omoccurassociations`
  ADD CONSTRAINT `FK_occurassoc_occidassoc` FOREIGN KEY (`occidAssociate`) REFERENCES `omoccurrences` (`occid`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT `FK_occurassoc_uidcreated` FOREIGN KEY (`createdUid`) REFERENCES `users` (`uid`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT `FK_occurassoc_uidmodified` FOREIGN KEY (`modifiedUid`) REFERENCES `users` (`uid`) ON UPDATE RESTRICT ON DELETE RESTRICT;




CREATE TABLE `uploadKeyValueTemp`(
  `key_value_id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `dbpk` varchar(255) NOT NULL,
  `upload_uid` int(10) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`key_value_id`),
  KEY `occid` (`occid`),
  KEY `collid` (`collid`),
  KEY `upload_key_temp_uid` (`upload_uid`),
  CONSTRAINT `uploadKeyValueTemp_ibfk_1` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `uploadKeyValueTemp_ibfk_2` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`collID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `upload_key_temp_uid` FOREIGN KEY (`upload_uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE);

# We need to relax this if we want inverse relationship entries in omoccurassociations for derivedFromSameIndividual
ALTER TABLE omoccurassociations DROP INDEX UQ_omoccurassoc_sciname, ADD INDEX `UQ_omoccurassoc_sciname` (`occid`, `verbatimSciname`, `associationType`) USING BTREE;


ALTER TABLE `omoccuraccess` ENGINE=InnoDB;
ALTER TABLE `omoccuraccesslink` ENGINE=InnoDB;
