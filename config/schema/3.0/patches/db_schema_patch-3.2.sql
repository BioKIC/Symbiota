INSERT INTO `schemaversion` (versionnumber) values ("3.2");

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

#Adjust FK to restrict deletion of record upon deletion of either internal occurrence or createdBy/modifiedBy users  
ALTER TABLE `omoccurassociations`
  DROP FOREIGN KEY `FK_occurassoc_occidassoc`,
  DROP FOREIGN KEY `FK_occurassoc_uidcreated`,
  DROP FOREIGN KEY `FK_occurassoc_uidmodified`;

ALTER TABLE `omoccurassociations`
  ADD CONSTRAINT `FK_occurassoc_occidassoc` FOREIGN KEY (`occidAssociate`) REFERENCES `omoccurrences` (`occid`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT `FK_occurassoc_uidcreated` FOREIGN KEY (`createdUid`) REFERENCES `users` (`uid`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT `FK_occurassoc_uidmodified` FOREIGN KEY (`modifiedUid`) REFERENCES `users` (`uid`) ON UPDATE RESTRICT ON DELETE RESTRICT;




