INSERT INTO `schemaversion` (versionnumber) values ("3.2");

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


ALTER TABLE imagetag 
  DROP CONSTRAINT FK_imagetag_imgid,
  DROP FOREIGN KEY `FK_imagetag_tagkey`;

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
