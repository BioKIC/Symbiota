INSERT INTO `schemaversion` (versionnumber) values ("3.2");

#Standardize indentification key tables
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
  ADD INDEX `IX_kmchar_charname` (`charName` ASC),
  ADD INDEX `IX_kmchar_sort` (`sortSequence` ASC),
  ADD INDEX `FK_kmchar_glossID_idx` (`glossID` ASC),
  ADD INDEX `FK_kmchar_enteredUid_idx` (`enteredUid` ASC);

ALTER TABLE `kmcharacters` 
  ADD CONSTRAINT `FK_kmchar_glossary`  FOREIGN KEY (`glossID`)  REFERENCES `glossary` (`glossid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_kmchar_enteredUid`  FOREIGN KEY (`enteredUid`)  REFERENCES `users` (`uid`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `kmcharacterlang` 
  DROP FOREIGN KEY `FK_charlang_lang`,
  DROP FOREIGN KEY `FK_characterlang_1`;

ALTER TABLE `kmcharacterlang` 
  CHANGE COLUMN `charname` `charName` VARCHAR(150) NOT NULL ,
  CHANGE COLUMN `langid` `langID` INT(11) NOT NULL ,
  CHANGE COLUMN `helpurl` `helpUrl` VARCHAR(500) NULL DEFAULT NULL ,
  CHANGE COLUMN `InitialTimeStamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `kmcharacterlang` 
  ADD INDEX `FK_kmcharlang_cid_idx` (`cid` ASC),
  ADD INDEX `FK_kmcharlang_langID_idx` (`langID` ASC);
  
ALTER TABLE `kmcharacterlang` 
  ADD CONSTRAINT `FK_charlang_cid`  FOREIGN KEY (`cid`)  REFERENCES `kmcharacters` (`cid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_charlang_lang`  FOREIGN KEY (`langID`)  REFERENCES `adminlanguages` (`langid`)  ON DELETE CASCADE  ON UPDATE CASCADE;

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


#occurrence access tables
ALTER TABLE `omoccuraccess` ENGINE=InnoDB;
ALTER TABLE `omoccuraccesslink` ENGINE=InnoDB;


# Skip if 1.0 install: Table does not exist within db_schema-3.0, thus statement is expected to fail if this was not originally a 1.0 install
# Drop deprecated_media foreign keys to avoid conflicts. If table does not exist, ignore
ALTER TABLE `deprecated_media`
  DROP FOREIGN KEY `FK_media_uid`,
  DROP FOREIGN KEY `FK_media_taxa`,
  DROP FOREIGN KEY `FK_media_occid`,
  DROP INDEX `FK_media_uid_idx`,
  DROP INDEX `FK_media_occid_idx`,
  DROP INDEX `FK_media_taxa_idx`;

#Schema modifications due to deprecating image table in preference with media table
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

INSERT INTO media(mediaID, tid, occid, url, thumbnailUrl, archiveUrl, originalUrl, sourceurl, referenceUrl, caption, creatoruid, creator, owner,
  mediaMD5, format, imagetype, locality, notes, anatomy, username, sourceIdentifier, hashFunction, hashValue, pixelYDimension, pixelXDimension,
  dynamicProperties, defaultDisplay, recordID, copyright, rights, accessRights, sortSequence, sortOccurrence, initialTimestamp, mediaType)
  SELECT imgid, tid, occid, url, thumbnailUrl, archiveUrl, originalUrl, sourceurl, referenceUrl, caption, photographerUid, photographer, owner,
    mediaMD5, format,imagetype, locality, notes, anatomy, username, sourceIdentifier, hashFunction, hashValue, pixelYDimension, pixelXDimension,
    dynamicProperties, defaultDisplay, recordID, copyright, rights, accessRights, sortSequence, sortOccurrence, initialTimestamp, "image" as mediaType
  FROM images;
  
#Drop and reset INDEX and FK names for imagetag table
ALTER TABLE `imagetag` 
  DROP FOREIGN KEY `FK_imagetag_imgid`,
  DROP FOREIGN KEY `FK_imagetag_tagkey`;
  
ALTER TABLE `imagetag` 
  DROP INDEX `imgid`,
  DROP INDEX `keyvalue`,
  DROP INDEX `FK_imagetag_imgid_idx`;

ALTER TABLE `imagetag`
  CHANGE COLUMN `imagetagid` `imageTagID` BIGINT(20) NOT NULL AUTO_INCREMENT,
  CHANGE COLUMN `imgid` `mediaID` INT(10) UNSIGNED NOT NULL,
  CHANGE COLUMN `keyvalue` `keyValue` VARCHAR(30) NOT NULL,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP();

ALTER TABLE `imagetag` 
  ADD INDEX `FK_imagetag_mediaID_idx` (`mediaID` ASC),
  ADD INDEX `FK_imagetag_tagkey_idx` (`keyValue` ASC);

ALTER TABLE `imagetag`
  ADD CONSTRAINT `FK_imagetag_tagkey` FOREIGN KEY (`keyValue`) REFERENCES `imagetagkey` (`tagkey`)  ON DELETE NO ACTION  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_imagetag_mediaID` FOREIGN KEY (`mediaID`) REFERENCES `media`(`mediaID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

#Drop and reset INDEX and FK names for imagetag table
ALTER TABLE `imagekeywords`
  DROP FOREIGN KEY `FK_imagekeywords_imgid`,
  DROP FOREIGN KEY `FK_imagekeyword_uid`,
  DROP INDEX `FK_imagekeyword_uid_idx`,
  DROP INDEX `FK_imagekeywords_imgid_idx`,
  DROP INDEX `INDEX_imagekeyword` ;

ALTER TABLE `imagekeywords`
  CHANGE COLUMN `imgkeywordid` `imgKeywordID` INT(11) NOT NULL AUTO_INCREMENT,
  CHANGE COLUMN `imgid` `mediaID` INT(10) UNSIGNED NOT NULL,
  CHANGE COLUMN `uidassignedby` `uidAssignedBy` INT(10) UNSIGNED NULL DEFAULT NULL,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP();

ALTER TABLE `imagekeywords`
  ADD KEY `FK_imagekeyword_keyword` (`keyword`),
  ADD KEY `FK_imagekeywords_mediaID_idx` (`mediaID`),
  ADD KEY `FK_imagekeyword_uid_idx` (`uidAssignedBy`),
  ADD CONSTRAINT `FK_imagekeyword_uid` FOREIGN KEY (`uidAssignedBy`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_imagekeywords_mediaID` FOREIGN KEY (`mediaID`) REFERENCES `media` (`mediaID`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `specprocessorrawlabels`
  DROP FOREIGN KEY `FK_specproc_images`,
  DROP INDEX `FK_specproc_images` ;

ALTER TABLE `specprocessorrawlabels`
  CHANGE COLUMN `imgid` `mediaID` INT(10) UNSIGNED NULL DEFAULT NULL,
  CHANGE COLUMN `rawstr` `rawStr` TEXT NOT NULL,
  CHANGE COLUMN `processingvariables` `processingVariables` VARCHAR(250) NULL DEFAULT NULL,
  CHANGE COLUMN `sortsequence` `sortSequence` INT(11) NULL DEFAULT NULL,
  CHANGE COLUMN `initialtimestamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP();

ALTER TABLE `specprocessorrawlabels`
  ADD KEY `FK_specproc_media_idx` (`mediaID`),
  ADD CONSTRAINT `FK_specproc_media` FOREIGN KEY (`mediaID`) REFERENCES `media` (`mediaID`)  ON UPDATE CASCADE  ON DELETE CASCADE;

ALTER TABLE `tmattributes`
  DROP FOREIGN KEY `FK_tmattr_imgid`;

ALTER TABLE `tmattributes`
  CHANGE COLUMN `imgid` `mediaID` INT(10) UNSIGNED NULL DEFAULT NULL,
  DROP INDEX `FK_tmattr_imgid_idx`;

ALTER TABLE `tmattributes`
  ADD KEY `FK_tmattr_media_idx` (`mediaID`),
  ADD CONSTRAINT `FK_tmattr_media` FOREIGN KEY (`mediaID`) REFERENCES `media` (`mediaID`) ON DELETE SET NULL ON UPDATE CASCADE;


#Following statements pertain to coordinate index modifications
#Define helper function to alter coordinates
DELIMITER |
CREATE FUNCTION `swap_wkt_coords`(str TEXT) RETURNS text 
  BEGIN 
    DECLARE latStart, latEnd, lngStart, lngEnd, i INT;
    DECLARE cha CHAR;
    DECLARE flipped TEXT;

    SET i = 0;
    SET flipped = '';

    label1: LOOP
      SET i = i + 1;
      IF i <= LENGTH(str) THEN
        SET cha = SUBSTRING(str, i, 1);

        IF cha REGEXP '^[A-Za-z(),]' THEN
          IF latStart is not null and latEnd is not null and lngStart is not null THEN
            SET lngEnd = i;
            SET flipped = CONCAT(flipped, 
              SUBSTRING(str, lngStart, CASE WHEN lngStart = lngEnd THEN 1 ELSE lngEnd - lngStart END),
              " ", 
              SUBSTRING(str, latStart, CASE WHEN latStart = latEnd THEN 1 ELSE latEnd - latStart END)
            );
          END IF;
          -- SET flipped = CONCAT(flipped, lngEnd);
          SET flipped = CONCAT(flipped, cha);
          SET latStart = NULL, latEnd = null, lngStart = null, lngEnd = null;
        ELSEIF cha = " " THEN
          IF latStart is not null THEN
            SET latEnd = i;
            -- SET flipped = CONCAT(flipped, latEnd);
          ELSE
            SET flipped = CONCAT(flipped, ' ');
          END IF;
        ELSE
          IF latStart IS NULL THEN
            SET latStart = i;
            -- SET flipped = CONCAT(flipped, latStart);
          ELSEIF latEnd is not null and lngStart IS NULL THEN
            SET lngStart = i;
            -- SET flipped = CONCAT(flipped, lngStart);
          END IF;
        END IF;
        ITERATE label1;
      END IF;
      LEAVE label1;
    END LOOP label1;

    RETURN flipped;
  END |

DELIMITER ;


#Add and update checklist footprints to be geoJson
ALTER TABLE `fmchecklists` 
  ADD COLUMN footprintGeoJson text DEFAULT NULL;

UPDATE fmchecklists 
  SET footprintGeoJson = ST_ASGEOJSON(ST_GEOMFROMTEXT(swap_wkt_coords(footprintWkt))) 
  WHERE footprintGeoJson IS NULL;


#Removes All omoccurpoints that have null lat or lng values in omocurrences which is needed to recalculate all omoccurpoints into lnglat points
DELETE p.* 
  FROM omoccurpoints p INNER JOIN omoccurrences o ON p.occid = o.occid 
  WHERE o.decimalLatitude IS NULL OR o.decimalLongitude IS NULL; 

#Create and add lng lat points for occurrence data which is needed to do searching is spacial indexes that are lng lat
ALTER TABLE `omoccurpoints` 
  ADD COLUMN lngLatPoint POINT;

UPDATE omoccurpoints p INNER JOIN omoccurrences o ON o.occid = p.occid 
  SET lngLatPoint = ST_POINTFROMTEXT(CONCAT('POINT(',o.decimalLongitude, ' ', o.decimalLatitude, ')')); 

ALTER TABLE `omoccurpoints` 
  MODIFY lngLatPoint POINT NOT NULL;
  
ALTER TABLE `omoccurpoints`
  ADD SPATIAL INDEX(lngLatPoint);

#Following statements pertain to fulltext indexing modifications
DROP TABLE `omoccurrencesfulltext`;

DROP TRIGGER IF EXISTS `omoccurrencesfulltext_insert`;
DROP TRIGGER IF EXISTS `omoccurrencesfulltext_update`;
DROP TRIGGER IF EXISTS `omoccurrencesfulltextpoint_update`;
DROP TRIGGER IF EXISTS `omoccurrencesfulltextpoint_insert`;
DROP TRIGGER IF EXISTS `omoccurrences_insert`;
DROP TRIGGER IF EXISTS `omoccurrences_update`;
DROP TRIGGER IF EXISTS `omoccurrences_delete`;

DELIMITER //
CREATE TRIGGER `omoccurrences_insert` AFTER INSERT ON `omoccurrences`
FOR EACH ROW BEGIN
  IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
    INSERT INTO omoccurpoints (`occid`,`point`, `lngLatPoint`) 
    VALUES (NEW.`occid`,Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`), Point(NEW.`decimalLongitude`, NEW.`decimalLatitude`));
  END IF;
END
//

CREATE TRIGGER `omoccurrences_update` AFTER UPDATE ON `omoccurrences`
FOR EACH ROW BEGIN
  IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
    IF OLD.`decimalLatitude` IS NULL OR (NEW.`decimalLatitude` != OLD.`decimalLatitude` AND NEW.`decimalLongitude` != OLD.`decimalLongitude`) THEN
      IF EXISTS (SELECT `occid` FROM omoccurpoints WHERE `occid`=NEW.`occid`) THEN
        UPDATE omoccurpoints 
        SET `point` = Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`), `lngLatPoint` = Point(NEW.`decimalLongitude`, NEW.`decimalLatitude`)
        WHERE `occid` = NEW.`occid`;
      ELSE 
        INSERT INTO omoccurpoints (`occid`,`point`,`lngLatPoint`) 
        VALUES (NEW.`occid`, Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`), Point(NEW.`decimalLongitude`, NEW.`decimalLatitude`));
      END IF;
    END IF;
  ELSE
    IF OLD.`decimalLatitude` IS NOT NULL THEN
      DELETE FROM omoccurpoints WHERE `occid` = NEW.`occid`;
    END IF;
  END IF;
END //

CREATE TRIGGER `omoccurrences_delete` BEFORE DELETE ON `omoccurrences`
FOR EACH ROW BEGIN
  DELETE FROM omoccurpoints WHERE `occid` = OLD.`occid`;
END//

DELIMITER ;


DROP TRIGGER specprocessorrawlabelsfulltext_insert;
DROP TRIGGER specprocessorrawlabelsfulltext_update;
DROP TRIGGER specprocessorrawlabelsfulltext_delete;

DROP TABLE `specprocessorrawlabelsfulltext`;

#Standardize occurrence identifier table
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

# Occurrence table adjustments
# Add fulltext indexes 
ALTER TABLE `omoccurrences` 
  ADD FULLTEXT INDEX `FT_omoccurrence_locality` (`locality`),
  ADD FULLTEXT INDEX `FT_omoccurrence_recordedBy` (`recordedBy`),
  DROP INDEX `IX_occurrences_locality`;

# Add indexes for countryCode and continent
ALTER TABLE `omoccurrences` 
  ADD INDEX `IX_occurrences_countryCode` (`countryCode` ASC),
  ADD INDEX `IX_occurrences_continent` (`continent` ASC);

# Make sure synonym countries have the same countryCode as the accepted country 
UPDATE geographicthesaurus g INNER JOIN geographicthesaurus a ON g.acceptedID = a.geoThesID 
  SET g.iso2 = a.iso2
  WHERE g.iso2 IS NULL AND a.iso2 IS NOT NULL;

#Populate NULL country codes
UPDATE omoccurrences o INNER JOIN geographicthesaurus g ON o.country = g.geoterm
  SET o.countryCode = g.iso2
  WHERE o.countryCode IS NULL AND g.geoLevel = 50 AND g.acceptedID IS NULL AND g.iso2 IS NOT NULL;

#Fix bad country code (likely bad imported values)
UPDATE omoccurrences o INNER JOIN geographicthesaurus g ON o.country = g.geoterm
  SET o.countryCode = g.iso2
  WHERE o.countryCode != g.iso2 AND g.geoLevel = 50 AND g.acceptedID IS NULL AND g.iso2 IS NOT NULL;

#Populate NULL continent values
UPDATE omoccurrences o INNER JOIN geographicthesaurus g ON o.countryCode = g.iso2 
  INNER JOIN geographicthesaurus p ON g.parentID = p.geoThesID
  SET o.continent = p.geoTerm
  WHERE o.continent IS NULL AND g.geoLevel = 50 AND p.acceptedID IS NULL AND g.acceptedID IS NULL;

#Fix bad continent values (likely bad improted values)
UPDATE omoccurrences o INNER JOIN geographicthesaurus g ON o.countryCode = g.iso2
  INNER JOIN geographicthesaurus p ON g.parentID = p.geoThesID
  SET o.continent = p.geoTerm
  WHERE o.continent != p.geoTerm AND g.geoLevel = 50 AND g.acceptedID IS NULL;


# Add cultivar name and trade name columns to taxa table
ALTER TABLE `taxa`
  ADD COLUMN `cultivarEpithet` VARCHAR(50) NULL AFTER unitName3,
  ADD COLUMN `tradeName` VARCHAR(50) NULL AFTER cultivarEpithet;

#Rename cultivated to cultivar
UPDATE taxonunits SET rankname = "Cultivar" WHERE rankname = "Cultivated";


#Add cultivar and trade name to uploadspectemp
ALTER TABLE `uploadspectemp` 
  ADD COLUMN `cultivarEpithet` VARCHAR(50) NULL AFTER infraspecificEpithet,
  ADD COLUMN `tradeName` VARCHAR(50) NULL AFTER cultivarEpithet;

#Add cultivar and trade name to uploadtaxa
ALTER TABLE `uploadtaxa` 
  ADD COLUMN `cultivarEpithet` VARCHAR(50) NULL AFTER `UnitName3`,
  ADD COLUMN `tradeName` VARCHAR(50) NULL AFTER `cultivarEpithet`;


CREATE TABLE `uploadkeyvaluetemp`(
  `keyValueID` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `dbpk` varchar(255) NOT NULL,
  `uploadUid` int(10) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`keyValueID`),
  KEY `IX_uploadKeyValue_occid` (`occid`),
  KEY `IX_uploadKeyValue_collid` (`collid`),
  KEY `IX_uploadKeyValue_uploadUid` (`uploadUid`),
  CONSTRAINT `FK_uploadKeyValue_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_uploadKeyValue_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`collID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_uploadKeyValue_uid` FOREIGN KEY (`uploadUid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE);



ALTER TABLE `omoccurpaleo` 
  CHANGE COLUMN `biota` `biota` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Flora or Fanua' ,
  CHANGE COLUMN `lithology` `lithology` VARCHAR(700) NULL DEFAULT NULL ,
  CHANGE COLUMN `stratRemarks` `stratRemarks` VARCHAR(1000) NULL DEFAULT NULL ,
  CHANGE COLUMN `geologicalContextID` `geologicalContextID` VARCHAR(100) NULL DEFAULT NULL ;

ALTER TABLE `omoccurpaleogts`
  ADD COLUMN `myaStart` FLOAT NULL DEFAULT NULL AFTER `rankname`,
  ADD COLUMN `myaEnd` FLOAT NULL DEFAULT NULL AFTER `myaStart`,
  ADD COLUMN `errorRange` FLOAT NULL DEFAULT NULL AFTER `myaEnd`,
  ADD COLUMN `colorCode` VARCHAR(10) NULL DEFAULT NULL AFTER `errorRange`,
  ADD COLUMN `geoTimeID` INT NULL DEFAULT NULL AFTER `parentgtsid`;

#reset the values within omoccurpaleogts table 
TRUNCATE omoccurpaleogts;

INSERT INTO `omoccurpaleogts` VALUES
(1,'Precambrian',10,'superera',4567,538.8,NULL,'#F74370',NULL,1374,'2024-10-17 19:59:29'),
(2,'Phanerozoic',20,'eon',538.8,0,NULL,'#9AD9DD',NULL,1376,'2024-10-17 19:59:29'),
(3,'Proterozoic',20,'eon',2500,538.8,NULL,'#F73563',1,1377,'2024-10-17 19:59:29'),
(4,'Archean',20,'eon',4031,2500,NULL,'#F0047F',1,1378,'2024-10-17 19:59:29'),
(5,'Hadean',20,'eon',4567,4031,NULL,'#AE027E',1,1375,'2024-10-17 19:59:29'),
(6,'Cenozoic',30,'era',66,0,NULL,'#F2F91D',2,1383,'2024-10-17 19:59:29'),
(7,'Mesozoic',30,'era',251.902,66,NULL,'#67C5CA',2,1385,'2024-10-17 19:59:29'),
(8,'Paleozoic',30,'era',538.8,251.902,NULL,'#99C08D',2,1388,'2024-10-17 19:59:29'),
(9,'Neoproterozoic',30,'era',1000,538.8,NULL,'#FEB342',3,1386,'2024-10-17 19:59:29'),
(10,'Mesoproterozoic',30,'era',1600,1000,NULL,'#FDB462',3,1384,'2024-10-17 19:59:29'),
(11,'Paleoproterozoic',30,'era',2500,1600,NULL,'#F74370',3,1387,'2024-10-17 19:59:29'),
(12,'Neoarchean',30,'era',2800,2500,NULL,'#F99BC1',4,1381,'2024-10-17 19:59:29'),
(13,'Mesoarchean',30,'era',3200,2800,NULL,'#F768A9',4,1380,'2024-10-17 19:59:29'),
(14,'Paleoarchean',30,'era',3600,3200,NULL,'#F4449F',4,1382,'2024-10-17 19:59:29'),
(15,'Eoarchean',30,'era',4031,3600,NULL,'#DA037F',4,1379,'2024-10-17 19:59:29'),
(16,'Quaternary',40,'period',2.58,0,NULL,'#F9F97F',6,1402,'2024-10-17 19:59:29'),
(17,'Neogene',40,'period',23.03,2.58,NULL,'#FFE619',6,1401,'2024-10-17 19:59:29'),
(18,'Paleogene',40,'period',66,23.03,NULL,'#FD9A52',6,1406,'2024-10-17 19:59:29'),
(19,'Cretaceous',40,'period',145,66,NULL,'#7FC64E',7,1400,'2024-10-17 19:59:29'),
(20,'Jurassic',40,'period',201.4,145,NULL,'#34B2C9',7,1404,'2024-10-17 19:59:29'),
(21,'Triassic',40,'period',251.902,201.4,NULL,'#812B92',7,1408,'2024-10-17 19:59:29'),
(22,'Permian',40,'period',298.9,251.902,NULL,'#F04028',8,1407,'2024-10-17 19:59:29'),
(23,'Carboniferous',40,'period',358.9,298.9,NULL,'#67A599',8,1399,'2024-10-17 19:59:29'),
(24,'Devonian',40,'period',419.2,358.9,NULL,'#CB8C37',8,1403,'2024-10-17 19:59:29'),
(25,'Silurian',40,'period',443.8,419.2,NULL,'#B3E1B6',8,1410,'2024-10-17 19:59:29'),
(26,'Ordovician',40,'period',485.4,443.8,NULL,'#009270',8,1405,'2024-10-17 19:59:29'),
(27,'Cambrian',40,'period',538.8,485.4,NULL,'#7FA056',8,1409,'2024-10-17 19:59:29'),
(28,'Ediacaran',40,'period',635,538.8,NULL,'#FED96A',9,1392,'2024-10-17 19:59:29'),
(29,'Cryogenian',40,'period',720,635,NULL,'',9,1390,'2024-10-17 19:59:29'),
(30,'Tonian',40,'period',1000,720,NULL,'#FEBF4E',9,1398,'2024-10-17 19:59:29'),
(31,'Stenian',40,'period',1200,1000,NULL,'#FED99A',10,1397,'2024-10-17 19:59:29'),
(32,'Ectasian',40,'period',1400,1200,NULL,'#FDCC8A',10,1391,'2024-10-17 19:59:29'),
(33,'Calymmian',40,'period',1600,1400,NULL,'#FDC07A',10,1389,'2024-10-17 19:59:29'),
(34,'Statherian',40,'period',1800,1600,NULL,'#F875A7',11,1396,'2024-10-17 19:59:29'),
(35,'Orosirian',40,'period',2050,1800,NULL,'#F76898',11,1393,'2024-10-17 19:59:29'),
(36,'Rhyacian',40,'period',2300,2050,NULL,'#F75B89',11,1394,'2024-10-17 19:59:29'),
(37,'Siderian',40,'period',2500,2300,NULL,'#F74F7C',11,1395,'2024-10-17 19:59:29'),
(38,'Holocene',50,'epoch',0.0117,0,NULL,'#FEEBD2',16,1432,'2024-10-17 19:59:29'),
(39,'Pleistocene',50,'epoch',2.58,0.0117,NULL,'#FFEFAF',16,1444,'2024-10-17 19:59:29'),
(40,'Pliocene',50,'epoch',5.333,2.58,NULL,'#FFFF99',17,1425,'2024-10-17 19:59:29'),
(41,'Miocene',50,'epoch',23.03,5.333,NULL,'#FFFF00',17,1446,'2024-10-17 19:59:29'),
(42,'Oligocene',50,'epoch',33.9,23.03,NULL,'#FEC07A',18,1424,'2024-10-17 19:59:29'),
(43,'Eocene',50,'epoch',56,33.9,NULL,'#FDB46C',18,1441,'2024-10-17 19:59:29'),
(44,'Paleocene',50,'epoch',66,56,NULL,'#FDA75F',18,1436,'2024-10-17 19:59:29'),
(45,'Late Cretaceous',50,'epoch',100.5,66,NULL,'',19,1447,'2024-10-17 19:59:29'),
(46,'Early Cretaceous',50,'epoch',145,100.5,NULL,'',19,1445,'2024-10-17 19:59:29'),
(47,'Late Jurassic',50,'epoch',161.5,145,NULL,'',20,1437,'2024-10-17 19:59:29'),
(48,'Middle Jurassic',50,'epoch',174.7,161.5,NULL,'',20,1443,'2024-10-17 19:59:29'),
(49,'Early Jurassic',50,'epoch',201.4,174.7,NULL,'',20,1442,'2024-10-17 19:59:29'),
(50,'Late Triassic',50,'epoch',237,201.4,NULL,'',21,1439,'2024-10-17 19:59:29'),
(51,'Middle Triassic',50,'epoch',247.2,237,NULL,'',21,1423,'2024-10-17 19:59:29'),
(52,'Early Triassic',50,'epoch',251.902,247.2,NULL,'',21,1419,'2024-10-17 19:59:29'),
(53,'Lopingian',50,'epoch',259.51,251.902,NULL,'#FBA794',22,1417,'2024-10-17 19:59:29'),
(54,'Guadalupian',50,'epoch',273.01,259.51,NULL,'#FB745C',22,1431,'2024-10-17 19:59:29'),
(55,'Cisuralian',50,'epoch',298.9,273.01,NULL,'#EF5845',22,1440,'2024-10-17 19:59:29'),
(56,'Late Pennsylvanian',50,'epoch',307,298.9,NULL,'',23,1428,'2024-10-17 19:59:29'),
(57,'Middle Pennsylvanian',50,'epoch',315.2,307,NULL,'',23,1414,'2024-10-17 19:59:29'),
(58,'Early Pennsylvanian',50,'epoch',323.2,315.2,NULL,'',23,1412,'2024-10-17 19:59:29'),
(59,'Late Mississippian',50,'epoch',330.9,323.2,NULL,'',23,1415,'2024-10-17 19:59:29'),
(60,'Middle Mississippian',50,'epoch',346.7,330.9,NULL,'',23,1413,'2024-10-17 19:59:29'),
(61,'Early Mississippian',50,'epoch',358.9,346.7,NULL,'',23,1411,'2024-10-17 19:59:29'),
(62,'Late Devonian',50,'epoch',382.7,358.9,NULL,'',24,1427,'2024-10-17 19:59:29'),
(63,'Middle Devonian',50,'epoch',393.3,382.7,NULL,'',24,1421,'2024-10-17 19:59:29'),
(64,'Early Devonian',50,'epoch',419.2,393.3,NULL,'',24,1434,'2024-10-17 19:59:29'),
(65,'Pridoli',50,'epoch',423,419.2,NULL,'#E6F5E1',25,1448,'2024-10-17 19:59:29'),
(66,'Ludlow',50,'epoch',427.4,423,NULL,'#BFE6CF',25,1420,'2024-10-17 19:59:29'),
(67,'Wenlock',50,'epoch',433.4,427.4,NULL,'#B3E1C2',25,1429,'2024-10-17 19:59:29'),
(68,'Llandovery',50,'epoch',443.8,433.4,NULL,'#99D7B3',25,1433,'2024-10-17 19:59:29'),
(69,'Late Ordovician',50,'epoch',458.4,443.8,NULL,'',26,1438,'2024-10-17 19:59:29'),
(70,'Middle Ordovician',50,'epoch',470,458.4,NULL,'',26,1422,'2024-10-17 19:59:29'),
(71,'Early Ordovician',50,'epoch',485.4,470,NULL,'',26,1418,'2024-10-17 19:59:29'),
(72,'Furongian',50,'epoch',497,485.4,NULL,'',27,1430,'2024-10-17 19:59:29'),
(73,'Miaolingian',50,'epoch',509,497,NULL,'',27,1435,'2024-10-17 19:59:29'),
(74,'Cambrian Series 2',50,'epoch',521,509,NULL,'',27,1416,'2024-10-17 19:59:29'),
(75,'Terreneuvian',50,'epoch',538.8,521,NULL,'',27,1426,'2024-10-17 19:59:29'),
(76,'Meghalayan',60,'age',0.0042,0,NULL,'',38,1510,'2024-10-17 19:59:29'),
(77,'Northgrippian',60,'age',0.0082,0.0042,NULL,'',38,1514,'2024-10-17 19:59:29'),
(78,'Greenlandian',60,'age',0.0117,0.0082,NULL,'',38,1491,'2024-10-17 19:59:29'),
(79,'Late Pleistocene',60,'age',0.129,0.0117,NULL,'',39,1542,'2024-10-17 19:59:29'),
(80,'Chibanian',60,'age',0.774,0.129,NULL,'',39,1476,'2024-10-17 19:59:29'),
(81,'Calabrian',60,'age',1.8,0.774,NULL,'#FFF2BA',39,1464,'2024-10-17 19:59:29'),
(82,'Gelasian',60,'age',2.58,1.8,NULL,'#FFEDB3',39,1488,'2024-10-17 19:59:29'),
(83,'Piacenzian',60,'age',3.6,2.58,NULL,'#FFFFBF',40,1518,'2024-10-17 19:59:29'),
(84,'Zanclean',60,'age',5.333,3.6,NULL,'#FFFFB3',40,1549,'2024-10-17 19:59:29'),
(85,'Messinian',60,'age',7.246,5.333,NULL,'#FFFF73',41,1511,'2024-10-17 19:59:29'),
(86,'Tortonian',60,'age',11.63,7.246,NULL,'#FFFF66',41,1538,'2024-10-17 19:59:29'),
(87,'Serravallian',60,'age',13.82,11.63,NULL,'#FFFF59',41,1531,'2024-10-17 19:59:29'),
(88,'Langhian',60,'age',15.98,13.82,NULL,'#FFFF4D',41,1505,'2024-10-17 19:59:29'),
(89,'Burdigalian',60,'age',20.44,15.98,NULL,'#FFFF41',41,1463,'2024-10-17 19:59:29'),
(90,'Aquitanian',60,'age',23.03,20.44,NULL,'#FFFF33',41,1454,'2024-10-17 19:59:29'),
(91,'Chattian',60,'age',27.82,23.03,NULL,'#FEE6AA',42,1475,'2024-10-17 19:59:29'),
(92,'Rupelian',60,'age',33.9,27.82,NULL,'#FED99A',42,1525,'2024-10-17 19:59:29'),
(93,'Priabonian',60,'age',37.71,33.9,NULL,'#FDCDA1',43,1521,'2024-10-17 19:59:29'),
(94,'Bartonian',60,'age',41.2,37.71,NULL,'#FDC091',43,1459,'2024-10-17 19:59:29'),
(95,'Lutetian',60,'age',47.8,41.2,NULL,'#FDB482',43,1508,'2024-10-17 19:59:29'),
(96,'Ypresian',60,'age',56,47.8,NULL,'#FCA773',43,1548,'2024-10-17 19:59:29'),
(97,'Thanetian',60,'age',59.2,56,NULL,'#FDBF6F',44,1535,'2024-10-17 19:59:29'),
(98,'Selandian',60,'age',61.6,59.2,NULL,'#FEBF65',44,1529,'2024-10-17 19:59:29'),
(99,'Danian',60,'age',66,61.6,NULL,'#FDB462',44,1478,'2024-10-17 19:59:29'),
(100,'Maastrichtian',60,'age',72.1,66,NULL,'#F2FA8C',45,1509,'2024-10-17 19:59:29'),
(101,'Campanian',60,'age',83.6,72.1,NULL,'#E6F47F',45,1470,'2024-10-17 19:59:29'),
(102,'Santonian',60,'age',86.3,83.6,NULL,'#D9EF74',45,1528,'2024-10-17 19:59:29'),
(103,'Coniacian',60,'age',89.8,86.3,NULL,'#CCE968',45,1477,'2024-10-17 19:59:29'),
(104,'Turonian',60,'age',93.9,89.8,NULL,'#BFE35D',45,1541,'2024-10-17 19:59:29'),
(105,'Cenomanian',60,'age',100.5,93.9,NULL,'#B3DE53',45,1473,'2024-10-17 19:59:29'),
(106,'Albian',60,'age',113,100.5,NULL,'#CCEA97',46,1451,'2024-10-17 19:59:29'),
(107,'Aptian',60,'age',121.4,113,NULL,'#BFE48A',46,1453,'2024-10-17 19:59:29'),
(108,'Barremian',60,'age',125.77,121.4,NULL,'#B3DF7F',46,1458,'2024-10-17 19:59:29'),
(109,'Hauterivian',60,'age',132.6,125.77,NULL,'#A6D975',46,1494,'2024-10-17 19:59:29'),
(110,'Valanginian',60,'age',139.8,132.6,NULL,'#99D36A',46,1543,'2024-10-17 19:59:29'),
(111,'Berriasian',60,'age',145,139.8,NULL,'#8CCD60',46,1462,'2024-10-17 19:59:29'),
(112,'Tithonian',60,'age',149.2,145,NULL,'#D9F1F7',47,1536,'2024-10-17 19:59:29'),
(113,'Kimmeridgian',60,'age',154.8,149.2,NULL,'#CCECF4',47,1502,'2024-10-17 19:59:29'),
(114,'Oxfordian',60,'age',161.5,154.8,NULL,'#BFE7F1',47,1516,'2024-10-17 19:59:29'),
(115,'Callovian',60,'age',165.3,161.5,NULL,'#BFE7E5',48,1465,'2024-10-17 19:59:29'),
(116,'Bathonian',60,'age',168.2,165.3,NULL,'#B3E2E3',48,1461,'2024-10-17 19:59:29'),
(117,'Bajocian',60,'age',170.9,168.2,NULL,'#A6DDE0',48,1457,'2024-10-17 19:59:29'),
(118,'Aalenian',60,'age',174.7,170.9,NULL,'#9AD9DD',48,1449,'2024-10-17 19:59:29'),
(119,'Toarcian',60,'age',184.2,174.7,NULL,'#99CEE3',49,1537,'2024-10-17 19:59:29'),
(120,'Pliensbachian',60,'age',192.9,184.2,NULL,'#80C5DD',49,1519,'2024-10-17 19:59:29'),
(121,'Sinemurian',60,'age',199.5,192.9,NULL,'#67BCD8',49,1533,'2024-10-17 19:59:29'),
(122,'Hettangian',60,'age',201.4,199.5,NULL,'#4EB3D3',49,1495,'2024-10-17 19:59:29'),
(123,'Rhaetian',60,'age',208.5,201.4,NULL,'#E3B9DB',50,1522,'2024-10-17 19:59:29'),
(124,'Norian',60,'age',227,208.5,NULL,'#D6AAD3',50,1513,'2024-10-17 19:59:29'),
(125,'Carnian',60,'age',237,227,NULL,'#C99BCB',50,1472,'2024-10-17 19:59:29'),
(126,'Ladinian',60,'age',242,237,NULL,'#C983BF',51,1504,'2024-10-17 19:59:29'),
(127,'Anisian',60,'age',247.2,242,NULL,'#BC75B7',51,1452,'2024-10-17 19:59:29'),
(128,'Olenekian',60,'age',251.2,247.2,NULL,'#B051A5',52,1515,'2024-10-17 19:59:29'),
(129,'Induan',60,'age',251.902,251.2,NULL,'#A4469F',52,1498,'2024-10-17 19:59:29'),
(130,'Changhsingian',60,'age',254.14,251.902,NULL,'#FCC0B2',53,1474,'2024-10-17 19:59:29'),
(131,'Wuchiapingian',60,'age',259.51,254.14,NULL,'#FCB4A2',53,1546,'2024-10-17 19:59:29'),
(132,'Capitanian',60,'age',264.28,259.51,NULL,'#FB9A85',54,1471,'2024-10-17 19:59:29'),
(133,'Wordian',60,'age',266.9,264.28,NULL,'#FB8D76',54,1545,'2024-10-17 19:59:29'),
(134,'Roadian',60,'age',273.01,266.9,NULL,'#FB8069',54,1524,'2024-10-17 19:59:29'),
(135,'Kungurian',60,'age',283.5,273.01,NULL,'#E38776',55,1503,'2024-10-17 19:59:29'),
(136,'Artinskian',60,'age',290.1,283.5,NULL,'#E37B68',55,1455,'2024-10-17 19:59:29'),
(137,'Sakmarian',60,'age',293.51,290.1,NULL,'#E36F5C',55,1526,'2024-10-17 19:59:29'),
(138,'Asselian',60,'age',298.9,293.51,NULL,'#E36350',55,1456,'2024-10-17 19:59:29'),
(139,'Gzhelian',60,'age',303.7,298.9,NULL,'',56,1493,'2024-10-17 19:59:29'),
(140,'Kasimovian',60,'age',307,303.7,NULL,'',56,1500,'2024-10-17 19:59:29'),
(141,'Moscovian',60,'age',315.2,307,NULL,'',57,1512,'2024-10-17 19:59:29'),
(142,'Bashkirian',60,'age',323.2,315.2,NULL,'',58,1460,'2024-10-17 19:59:29'),
(143,'Serpukhovian',60,'age',330.9,323.2,NULL,'',59,1530,'2024-10-17 19:59:29'),
(144,'Visean',60,'age',346.7,330.9,NULL,'',60,1544,'2024-10-17 19:59:29'),
(145,'Tournaisian',60,'age',358.9,346.7,NULL,'',61,1539,'2024-10-17 19:59:29'),
(146,'Famennian',60,'age',372.2,358.9,NULL,'#F2EDB3',62,1484,'2024-10-17 19:59:29'),
(147,'Frasnian',60,'age',382.7,372.2,NULL,'#F2EDAD',62,1487,'2024-10-17 19:59:29'),
(148,'Givetian',60,'age',387.7,382.7,NULL,'#F1E185',63,1489,'2024-10-17 19:59:29'),
(149,'Eifelian',60,'age',393.3,387.7,NULL,'#F1D576',63,1482,'2024-10-17 19:59:29'),
(150,'Emsian',60,'age',407.6,393.3,NULL,'#E5D075',64,1483,'2024-10-17 19:59:29'),
(151,'Pragian',60,'age',410.8,407.6,NULL,'#E5C468',64,1520,'2024-10-17 19:59:29'),
(152,'Lochkovian',60,'age',419.2,410.8,NULL,'#E5B75A',64,1506,'2024-10-17 19:59:29'),
(153,'Ludfordian',60,'age',425.6,423,NULL,'#D9F0DF',66,1507,'2024-10-17 19:59:29'),
(154,'Gorstian',60,'age',427.4,425.6,NULL,'#CCECDD',66,1490,'2024-10-17 19:59:29'),
(155,'Homerian',60,'age',430.5,427.4,NULL,'#CCEBD1',67,1497,'2024-10-17 19:59:29'),
(156,'Sheinwoodian',60,'age',433.4,430.5,NULL,'#BFE6C3',67,1532,'2024-10-17 19:59:29'),
(157,'Telychian',60,'age',438.5,433.4,NULL,'#BFE6CF',68,1534,'2024-10-17 19:59:29'),
(158,'Aeronian',60,'age',440.8,438.5,NULL,'#B3E1C2',68,1450,'2024-10-17 19:59:29'),
(159,'Rhuddanian',60,'age',443.8,440.8,NULL,'#A6DCB5',68,1523,'2024-10-17 19:59:29'),
(160,'Hirnantian',60,'age',445.2,443.8,NULL,'#A6DBAB',69,1496,'2024-10-17 19:59:29'),
(161,'Katian',60,'age',453,445.2,NULL,'#99D69F',69,1501,'2024-10-17 19:59:29'),
(162,'Sandbian',60,'age',458.4,453,NULL,'#8CD094',69,1527,'2024-10-17 19:59:29'),
(163,'Darriwilian',60,'age',467.3,458.4,NULL,'#74C69C',70,1480,'2024-10-17 19:59:29'),
(164,'Dapingian',60,'age',470,467.3,NULL,'#66C092',70,1479,'2024-10-17 19:59:29'),
(165,'Floian',60,'age',477.7,470,NULL,'#41B087',71,1485,'2024-10-17 19:59:29'),
(166,'Tremadocian',60,'age',485.4,477.7,NULL,'#33A97E',71,1540,'2024-10-17 19:59:29'),
(167,'Cambrian Stage 10',60,'age',489.5,485.4,NULL,'',72,1466,'2024-10-17 19:59:29'),
(168,'Jiangshanian',60,'age',494,489.5,NULL,'',72,1499,'2024-10-17 19:59:29'),
(169,'Paibian',60,'age',497,494,NULL,'',72,1517,'2024-10-17 19:59:29'),
(170,'Guzhangian',60,'age',500.5,497,NULL,'',73,1492,'2024-10-17 19:59:29'),
(171,'Drumian',60,'age',504.5,500.5,NULL,'',73,1481,'2024-10-17 19:59:29'),
(172,'Wuliuan',60,'age',509,504.5,NULL,'',73,1547,'2024-10-17 19:59:29'),
(173,'Cambrian Stage 4',60,'age',514,509,NULL,'',74,1469,'2024-10-17 19:59:29'),
(174,'Cambrian Stage 3',60,'age',521,514,NULL,'',74,1468,'2024-10-17 19:59:29'),
(175,'Cambrian Stage 2',60,'age',529,521,NULL,'',75,1467,'2024-10-17 19:59:29'),
(176,'Fortunian',60,'age',538.8,529,NULL,'',75,1486,'2024-10-17 19:59:29');
