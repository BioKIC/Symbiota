INSERT INTO `schemaversion` (versionnumber) values ("3.1");

ALTER TABLE `ctcontrolvocab` 
  ADD COLUMN `filterVariable` VARCHAR(150) NOT NULL DEFAULT '' AFTER `fieldName`,
  DROP INDEX `UQ_ctControlVocab`;

ALTER TABLE `ctcontrolvocab` 
  ADD UNIQUE INDEX `UQ_ctControlVocab` (`title` ASC, `tableName` ASC, `fieldName` ASC, `filterVariable` ASC);

INSERT INTO `ctcontrolvocab`(title, tableName, fieldName, filterVariable)
  VALUES("Occurrence Associations Type", "omoccurassociations", "relationship", "associationType:resource");

INSERT INTO `ctcontrolvocabterm`(cvID, term, termDisplay)
  SELECT cvID, "fieldNotes", "Field Notes" FROM ctcontrolvocab WHERE tableName = "omoccurassociations" AND fieldName = "relationship" AND filterVariable = "associationType:resource";

INSERT INTO `ctcontrolvocabterm`(cvID, term, termDisplay)
  SELECT cvID, "genericResource", "Generic Resource" FROM ctcontrolvocab WHERE tableName = "omoccurassociations" AND fieldName = "relationship" AND filterVariable = "associationType:resource";

  
ALTER TABLE `fmchklsttaxalink` 
  DROP FOREIGN KEY `FK_chklsttaxalink_cid`;

ALTER TABLE `fmchklsttaxalink` 
  ADD CONSTRAINT `FK_chklsttaxalink_cid`  FOREIGN KEY (`clid`)  REFERENCES `fmchecklists` (`clid`)  ON DELETE CASCADE  ON UPDATE CASCADE;


#Set foreign keys for fmchklstcoordinates
ALTER TABLE `fmchklstcoordinates` 
  DROP INDEX `FKchklsttaxalink` ;

ALTER TABLE `fmchklstcoordinates` 
  ADD INDEX `IX_checklistCoord_tid` (`tid` ASC),
  ADD INDEX `IX_checklistCoord_clid` (`clid` ASC);

ALTER TABLE `fmchklstcoordinates` 
  ADD UNIQUE INDEX `UQ_checklistCoord_unique` (`clid` ASC, `tid` ASC, `decimalLatitude` ASC, `decimalLongitude` ASC);

ALTER TABLE `fmchklstcoordinates` 
  ADD CONSTRAINT `FK_checklistCoord_clid`  FOREIGN KEY (`clid`)  REFERENCES `fmchecklists` (`clid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_checklistCoord_tid`  FOREIGN KEY (`tid`)  REFERENCES `taxa` (`tid`)  ON DELETE CASCADE  ON UPDATE CASCADE;


-- Ensure these older tables are innoDB
ALTER TABLE `geographicpolygon` 
  ENGINE = InnoDB;

ALTER TABLE `geographicthesaurus` 
  ENGINE = InnoDB;

ALTER TABLE `geographicpolygon` 
  MODIFY COLUMN footprintPolygon geometry NOT NULL;

DROP PROCEDURE IF EXISTS `insertGeographicPolygon`;

DROP PROCEDURE IF EXISTS `updateGeographicPolygon`;

DELIMITER |
CREATE PROCEDURE `insertGeographicPolygon`(IN geo_id int, IN geo_json longtext)
  BEGIN
    INSERT INTO geographicpolygon (geoThesID, footprintPolygon, geoJSON) VALUES (geo_id, ST_GeomFromGeoJSON(geo_json), geo_json);
  END |
CREATE PROCEDURE `updateGeographicPolygon`(IN geo_id int, IN geo_json longtext)
  BEGIN
    UPDATE geographicpolygon SET geoJSON = geo_json, footprintPolygon = ST_GeomFromGeoJSON(geo_json) WHERE geoThesID = geo_id;
  END | 
DELIMITER ;


ALTER TABLE `images` 
  ADD COLUMN `pixelYDimension` INT NULL AFTER `mediaMD5`,
  ADD COLUMN `pixelXDimension` INT NULL AFTER `pixelYDimension`,
  CHANGE COLUMN `InitialTimeStamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;  


ALTER TABLE `ommaterialsample` 
  ADD INDEX `IX_ommatsample_sampleType` (`sampleType` ASC);


ALTER TABLE `omoccurassociations` 
  ADD COLUMN `associationType` VARCHAR(45) NOT NULL AFTER `occid`;

ALTER TABLE `omoccurassociations` 
  ADD COLUMN `objectID` VARCHAR(250) NULL DEFAULT NULL COMMENT 'dwc:relatedResourceID (object identifier)' AFTER `subType`,
  ADD COLUMN `instanceID` VARCHAR(45) NULL DEFAULT NULL COMMENT 'dwc:resourceRelationshipID, if association was defined externally ' AFTER `accordingTo`,
  CHANGE COLUMN `identifier` `identifier` VARCHAR(250) NULL DEFAULT NULL COMMENT 'Deprecated field' ,
  CHANGE COLUMN `sourceIdentifier` `sourceIdentifier` VARCHAR(45) NULL DEFAULT NULL COMMENT 'deprecated field' ;
  
UPDATE `omoccurassociations`
  SET objectID = identifier
  WHERE objectID IS NULL AND identifier IS NOT NULL;

UPDATE `omoccurassociations`
  SET instanceID = sourceIdentifier
  WHERE instanceID IS NULL AND sourceIdentifier IS NOT NULL;

ALTER TABLE `omoccurassociations` 
  DROP INDEX `UQ_omoccurassoc_sciname`;
  
ALTER TABLE `omoccurassociations` 
  ADD UNIQUE INDEX `UQ_omoccurassoc_sciname` (`occid` ASC, `verbatimSciname` ASC, `associationType` ASC);

ALTER TABLE `omoccurassociations` 
  ADD INDEX `IX_occurassoc_identifier` (`identifier` ASC),
  ADD INDEX `IX_occurassoc_recordID` (`recordID` ASC);
  

ALTER TABLE `omoccurassociations` 
  DROP INDEX `omossococcur_occid_idx`,
  ADD INDEX `IX_ossococcur_occid` (`occid` ASC);

ALTER TABLE `omoccurassociations` 
  DROP INDEX `omossococcur_occidassoc_idx`,
  ADD INDEX `IX_ossococcur_occidassoc` (`occidAssociate` ASC);

ALTER TABLE `omoccurassociations` 
  DROP INDEX `INDEX_verbatimSciname`,
  ADD INDEX `IX_occurassoc_verbatimSciname` (`verbatimSciname` ASC);

ALTER TABLE `omoccurassociations` 
  ADD UNIQUE INDEX `UQ_omoccurassoc_identifier` (`occid` ASC, `identifier` ASC);

UPDATE `omoccurassociations`
  SET associationType = "internalOccurrence"
  WHERE associationType = "" AND occidAssociate IS NOT NULL;

UPDATE `omoccurassociations`
  SET associationType = "externalOccurrence"
  WHERE associationType = "" AND occidAssociate IS NULL AND resourceUrl IS NOT NULL;

UPDATE `omoccurassociations`
  SET associationType = "observational"
  WHERE associationType = "" AND occidAssociate IS NULL AND resourceUrl IS NULL AND verbatimSciname IS NOT NULL;


# Skip if 1.0 install: Corrects an issue with db_schema-3.0.sql. Will fail when udating 1.x schemas, thus ignore 
ALTER TABLE `omoccurdeterminations` 
  CHANGE COLUMN `identificationID` `sourceIdentifier` VARCHAR(45) NULL DEFAULT NULL ;


# Needed to ensure basisOfRecord values are tagged correctly based on collection type (aka collType field)
UPDATE `omoccurrences` o INNER JOIN omcollections c ON o.collid = c.collid
  SET o.basisofrecord = "PreservedSpecimen"
  WHERE (o.basisofrecord = "HumanObservation" OR o.basisofrecord IS NULL) AND c.colltype = 'Preserved Specimens'
  AND o.occid NOT IN(SELECT occid FROM omoccuredits WHERE fieldname = "basisofrecord");

ALTER TABLE `omoccurrences` 
  ADD COLUMN `vitality` VARCHAR(150) NULL DEFAULT NULL AFTER `behavior`;

#Standardize naming of indexes within occurrence table 
SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE `omoccurrences` 
  DROP INDEX `Index_collid`,
  DROP INDEX `UNIQUE_occurrenceID`,
  DROP INDEX `Index_sciname`,
  DROP INDEX `Index_family`,
  DROP INDEX `Index_country`,
  DROP INDEX `Index_state`,
  DROP INDEX `Index_county`,
  DROP INDEX `Index_collector`,
  DROP INDEX `Index_ownerInst`,
  DROP INDEX `FK_omoccurrences_tid`,
  DROP INDEX `FK_omoccurrences_uid`,
  DROP INDEX `Index_municipality`,
  DROP INDEX `Index_collnum`,
  DROP INDEX `Index_catalognumber`,
  DROP INDEX `Index_eventDate`,
  DROP INDEX `Index_occurrences_procstatus`,
  DROP INDEX `occelevmin`,
  DROP INDEX `occelevmax`,
  DROP INDEX `Index_occurrences_cult`,
  DROP INDEX `Index_occurrences_typestatus`,
  DROP INDEX `Index_occurDateLastModifed`,
  DROP INDEX `Index_occurDateEntered`,
  DROP INDEX `Index_occurRecordEnteredBy`,
  DROP INDEX `Index_locality`,
  DROP INDEX `Index_otherCatalogNumbers`,
  DROP INDEX `Index_locationID`,
  DROP INDEX `Index_eventID`,
  DROP INDEX `Index_occur_localitySecurity`,
  DROP INDEX `IX_omoccur_eventDate2`,
  DROP INDEX `IX_omoccurrences_recordID`;

ALTER TABLE `omoccurrences` 
  ADD UNIQUE INDEX `UQ_occurrences_collid_dbpk` (`collid` ASC, `dbpk` ASC),
  ADD UNIQUE INDEX `UQ_occurrences_occurrenceID` (`occurrenceID` ASC),
  ADD INDEX `IX_occurrences_collid` (`collid` ASC),
  ADD INDEX `IX_occurrences_tid` (`tidInterpreted` ASC),
  ADD INDEX `IX_occurrences_uid` (`observerUid` ASC),
  ADD INDEX `IX_occurrences_ownerInst` (`ownerInstitutionCode` ASC),
  ADD INDEX `IX_occurrences_catalognumber` (`catalogNumber` ASC),
  ADD INDEX `IX_occurrences_otherCatalogNumbers` (`otherCatalogNumbers` ASC),
  ADD INDEX `IX_occurrences_sciname` (`sciname` ASC),
  ADD INDEX `IX_occurrences_family` (`family` ASC),
  ADD INDEX `IX_occurrences_recordedBy` (`recordedBy` ASC),
  ADD INDEX `IX_occurrences_recordNumber` (`recordNumber` ASC),
  ADD INDEX `IX_occurrences_eventDate` (`eventDate` ASC),
  ADD INDEX `IX_occurrences_eventDate2` (`eventDate2` ASC),
  ADD INDEX `IX_occurrences_eventID` (`eventID` ASC),
  ADD INDEX `IX_occurrences_cultStatus` (`cultivationStatus` ASC),
  ADD INDEX `IX_occurrences_typestatus` (`typeStatus` ASC),
  ADD INDEX `IX_occurrences_country` (`country` ASC),
  ADD INDEX `IX_occurrences_stateProvince` (`stateProvince` ASC),
  ADD INDEX `IX_occurrences_county` (`county` ASC),
  ADD INDEX `IX_occurrences_municipality` (`municipality` ASC),
  ADD INDEX `IX_occurrences_locality` (`locality`(100) ASC),
  ADD INDEX `IX_occurrences_locationID` (`locationID` ASC),
  ADD INDEX `IX_occurrences_localitySecurity` (`localitySecurity` ASC),
  ADD INDEX `IX_occurrences_elevMin` (`minimumElevationInMeters` ASC),
  ADD INDEX `IX_occurrences_elevMax` (`maximumElevationInMeters` ASC),
  ADD INDEX `IX_occurrences_procStatus` (`processingStatus` ASC),
  ADD INDEX `IX_occurrences_recordID` (`recordID` ASC),
  ADD INDEX `IX_occurrences_recordEnteredBy` (`recordEnteredBy` ASC),
  ADD INDEX `IX_occurrences_dateEntered` (`dateEntered` ASC),
  ADD INDEX `IX_occurrences_dateLastModified` (`dateLastModified` ASC);

SET FOREIGN_KEY_CHECKS=1; 


# Clean up localitySecurity for occurrences that are cultivated and have not explicitly had their localitySecurity edited to be 1 (and are missing a security reason) more recently than it has been edited to 0.
UPDATE omoccurrences o INNER JOIN omoccuredits e ON o.occid = e.occid
  LEFT JOIN (SELECT occid, ocedid FROM omoccuredits WHERE fieldName = "localitySecurity" AND fieldValueNew = 0) e2 ON e.occid = e2.occid AND e.ocedid < e2.ocedid
  SET o.localitySecurity = 1, o.localitySecurityReason = "[Security Setting Explicitly Locked]"
  WHERE o.localitySecurityReason IS NULL AND e.fieldName = "localitySecurity" AND e.fieldValueNew = 1 AND e2.occid IS NULL;

UPDATE omoccurrences SET localitySecurity=0 WHERE cultivationStatus=1 AND localitySecurity=1 AND localitySecurityReason IS NULL;


ALTER TABLE `taxa` 
  ADD COLUMN `rankName` VARCHAR(45) NULL AFTER `rankID`,
  CHANGE COLUMN `modifiedTimeStamp` `modifiedTimestamp` DATETIME NULL DEFAULT NULL ,
  CHANGE COLUMN `InitialTimeStamp` `initialTimestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ;

ALTER TABLE `taxa` 
  ADD INDEX `IX_taxa_unitname1` (`unitName1` ASC),
  ADD INDEX `IX_taxa_unitname2` (`unitName2` ASC),
  ADD INDEX `IX_taxa_unitname3` (`unitName3` ASC),
  ADD INDEX `IX_taxa_rankid` (`rankID` ASC),
  ADD INDEX `IX_taxa_sciname` (`sciName` ASC),
  ADD INDEX `IX_taxa_kingdom` (`kingdomName` ASC),
  ADD INDEX `FK_taxa_ts` (`InitialTimeStamp` ASC),
  DROP INDEX `idx_taxacreated` ,
  DROP INDEX `idx_taxa_kingdomName` ,
  DROP INDEX `sciname_index` ,
  DROP INDEX `unitname1_index` ,
  DROP INDEX `rankid_index` ;


ALTER TABLE `uploadspectemp` 
  ADD COLUMN `vitality` VARCHAR(150) NULL DEFAULT NULL AFTER `behavior`;

ALTER TABLE `uploadspectemp` 
  DROP INDEX `Index_uploadspectemp_occid`,
  DROP INDEX `Index_uploadspectemp_dbpk`,
  DROP INDEX `Index_uploadspec_sciname`,
  DROP INDEX `Index_uploadspec_catalognumber`,
  DROP INDEX `Index_uploadspec_othercatalognumbers`;
  
ALTER TABLE `uploadspectemp` 
  ADD INDEX `IX_uploadspectemp_occid` (`occid` ASC),
  ADD INDEX `IX_uploadspectemp_dbpk` (`dbpk` ASC),
  ADD INDEX `IX_uploadspec_sciname` (`sciname` ASC),
  ADD INDEX `IX_uploadspec_catalognumber` (`catalogNumber` ASC),
  ADD INDEX `IX_uploadspec_othercatalognumbers` (`otherCatalogNumbers` ASC);
  
ALTER TABLE `uploadspectemp` 
  ADD INDEX `IX_uploadspectemp_occurrenceID` (`occurrenceID` ASC);


# Establish a table to track third party auth
CREATE TABLE `usersthirdpartyauth` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` INT(10) UNSIGNED NOT NULL,
  `subUuid` VARCHAR(100) NOT NULL,
  `provider` VARCHAR(200) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_users_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE
);


# Skip if 3.0 install: Table does not exist within db_schema-3.0, thus statement is expected to fail if this was not originally a 1.0 install
# Deprecate omoccurresource table in preference for omoccurassociations. 
ALTER TABLE `omoccurresource` 
  RENAME TO  `deprecated_omoccurresource` ;

