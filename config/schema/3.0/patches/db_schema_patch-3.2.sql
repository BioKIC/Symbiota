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
UPDATE omoccurrences o INNER JOIN geographicThesaurus g ON o.countryCode = g.iso2 
  INNER JOIN geographicThesaurus p ON g.parentID = p.geoThesID
  SET o.continent = p.geoTerm
  WHERE o.continent IS NULL AND g.geoLevel = 50 AND p.acceptedID IS NULL AND g.acceptedID IS NULL;

#Fix bad continent values (likely bad improted values)
UPDATE omoccurrences o INNER JOIN geographicThesaurus g ON o.countryCode = g.iso2
  INNER JOIN geographicThesaurus p ON g.parentID = p.geoThesID
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


# Skip if 1.0 install: Table does not exist within db_schema-3.0, thus statement is expected to fail if this was not originally a 1.0 install
# Drop deprecated_media foreign keys to avoid conflicts. If table does not exist, ignore
ALTER TABLE `deprecated_media`
  DROP FOREIGN KEY `FK_media_uid`,
  DROP FOREIGN KEY `FK_media_taxa`,
  DROP FOREIGN KEY `FK_media_occid`,
  DROP INDEX `FK_media_uid_idx`,
  DROP INDEX `FK_media_occid_idx`,
  DROP INDEX `FK_media_taxa_idx`;
