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
DROP TRIGGER specprocessorrawlabelsfulltext_insert
DROP TRIGGER specprocessorrawlabelsfulltext_update
DROP TRIGGER specprocessorrawlabelsfulltext_delete
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
  CHANGE COLUMN `charname` `charName` VARCHAR(150) NOT NULL ,
  CHANGE COLUMN `chartype` `charType` VARCHAR(2) NOT NULL DEFAULT 'UM' ,
  CHANGE COLUMN `defaultlang` `defaultLang` VARCHAR(45) NOT NULL DEFAULT 'English' ,
  CHANGE COLUMN `difficultyrank` `difficultyRank` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 1 ,
  CHANGE COLUMN `description` `description` VARCHAR(1500) NULL DEFAULT NULL ,
  CHANGE COLUMN `glossid` `glossID` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `helpurl` `helpUrl` VARCHAR(500) NULL DEFAULT NULL ,
  CHANGE COLUMN `sortsequence` `sortSequence` INT(10) UNSIGNED NULL DEFAULT NULL ,
  CHANGE COLUMN `enteredby` `enteredBy` VARCHAR(45) NULL DEFAULT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `kmcharacters` 
  ADD CONSTRAINT `FK_kmchar_glossary`  FOREIGN KEY (`glossID`)  REFERENCES `glossary` (`glossid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

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


ALTER TABLE `kmcharheading` 
  DROP FOREIGN KEY `FK_kmcharheading_lang`;

ALTER TABLE `kmcharheading` 
  CHANGE COLUMN `headingname` `headingName` VARCHAR(255) NOT NULL ,
  CHANGE COLUMN `langid` `langID` INT(11) NOT NULL ,
  CHANGE COLUMN `sortsequence` `sortSequence` INT(11) NULL DEFAULT NULL ,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

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
  CHANGE COLUMN `InitialTimeStamp` `initialTimeStamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`cid`, `cs`);

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
  CONSTRAINT `upload_key_temp_uid` FOREIGN KEY (`upload_uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE)

# We need to relax this if we want inverse relationship entries in omoccurassociations for derivedFromSameIndividual
ALTER TABLE omoccurassociations DROP INDEX UQ_omoccurassoc_sciname, ADD INDEX `UQ_omoccurassoc_sciname` (`occid`, `verbatimSciname`, `associationType`) USING BTREE;


ALTER TABLE `omoccuraccess` ENGINE=InnoDB;
ALTER TABLE `omoccuraccesslink` ENGINE=InnoDB;
