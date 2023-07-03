ALTER TABLE `schemaversion` 
  ADD UNIQUE INDEX `versionnumber_UNIQUE` (`versionnumber` ASC);

INSERT IGNORE INTO `schemaversion` (versionnumber) values ("1.1");


#Specimen attribute (traits) model
CREATE TABLE `tmtraits` (
  `traitid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `traitname` VARCHAR(100) NOT NULL,
  `traittype` VARCHAR(2) NOT NULL DEFAULT 'UM',
  `units` VARCHAR(45) NULL,
  `description` VARCHAR(250) NULL,
  `refurl` VARCHAR(250) NULL,
  `notes` VARCHAR(250) NULL,
  `dynamicProperties` TEXT NULL,
  `modifieduid` INT UNSIGNED NULL,
  `datelastmodified` DATETIME NULL,
  `createduid` INT UNSIGNED NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`traitid`),
  INDEX `traitsname` (`traitname` ASC),
  INDEX `FK_traits_uidcreated_idx` (`createduid` ASC),
  INDEX `FK_traits_uidmodified_idx` (`modifieduid` ASC),
  CONSTRAINT `FK_traits_uidcreated`
    FOREIGN KEY (`createduid`)   REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE,
  CONSTRAINT `FK_traits_uidmodified`
    FOREIGN KEY (`modifieduid`)   REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE);

CREATE TABLE `tmstates` (
  `stateid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `traitid` INT UNSIGNED NOT NULL,
  `statecode` VARCHAR(2) NOT NULL,
  `statename` VARCHAR(75) NOT NULL,
  `description` VARCHAR(250) NULL,
  `refurl` VARCHAR(250) NULL,
  `notes` VARCHAR(250) NULL,
  `sortseq` INT NULL,
  `modifieduid` INT UNSIGNED NULL,
  `datelastmodified` DATETIME NULL,
  `createduid` INT UNSIGNED NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`stateid`),
  UNIQUE INDEX `traitid_code_UNIQUE` (`traitid` ASC, `statecode` ASC),
  INDEX `FK_tmstate_uidcreated_idx` (`createduid` ASC),
  INDEX `FK_tmstate_uidmodified_idx` (`modifieduid` ASC),
  CONSTRAINT `FK_tmstates_uidcreated`
    FOREIGN KEY (`createduid`)   REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE,
  CONSTRAINT `FK_tmstates_uidmodified`
    FOREIGN KEY (`modifieduid`)   REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE,
  CONSTRAINT `FK_tmstates_traits`
    FOREIGN KEY (`traitid`)   REFERENCES `tmtraits` (`traitid`)   ON DELETE RESTRICT   ON UPDATE CASCADE);

CREATE TABLE `tmattributes` (
  `stateid` INT UNSIGNED NOT NULL,
  `occid` INT UNSIGNED NOT NULL,
  `modifier` VARCHAR(100) NULL,
  `xvalue` DOUBLE(15,5) NULL,
  `imgid` INT UNSIGNED NULL,
  `imagecoordinates` VARCHAR(45) NULL,
  `source` VARCHAR(250) NULL,
  `notes` VARCHAR(250) NULL,
  `statuscode` TINYINT NULL,
  `modifieduid` INT UNSIGNED NULL,
  `datelastmodified` DATETIME NULL,
  `createduid` INT UNSIGNED NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`stateid`,`occid`),
  INDEX `FK_tmattr_stateid_idx` (`stateid` ASC),
  INDEX `FK_tmattr_occid_idx` (`occid` ASC),
  INDEX `FK_tmattr_imgid_idx` (`imgid` ASC),
  INDEX `FK_attr_uidcreate_idx` (`createduid` ASC),
  INDEX `FK_tmattr_uidmodified_idx` (`modifieduid` ASC),
  CONSTRAINT `FK_tmattr_stateid`
    FOREIGN KEY (`stateid`)   REFERENCES `tmstates` (`stateid`)   ON DELETE CASCADE   ON UPDATE CASCADE,
  CONSTRAINT `FK_tmattr_occid`
    FOREIGN KEY (`occid`)   REFERENCES `omoccurrences` (`occid`)   ON DELETE CASCADE   ON UPDATE CASCADE,
  CONSTRAINT `FK_tmattr_imgid`
    FOREIGN KEY (`imgid`)  REFERENCES `images` (`imgid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  CONSTRAINT `FK_tmattr_uidcreate`
    FOREIGN KEY (`createduid`)   REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE,
  CONSTRAINT `FK_tmattr_uidmodified`
    FOREIGN KEY (`modifieduid`)   REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE
);

CREATE TABLE `tmtraittaxalink` (
  `traitid` INT UNSIGNED NOT NULL,
  `tid` INT UNSIGNED NOT NULL,
  `relation` VARCHAR(45) NOT NULL DEFAULT 'include',
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`traitid`, `tid`),
  INDEX `FK_traittaxalink_traitid_idx` (`traitid` ASC),
  INDEX `FK_traittaxalink_tid_idx` (`tid` ASC),
  CONSTRAINT `FK_traittaxalink_traitid`
    FOREIGN KEY (`traitid`)  REFERENCES `tmtraits` (`traitid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_traittaxalink_tid`
    FOREIGN KEY (`tid`)  REFERENCES `taxa` (`TID`)  ON DELETE CASCADE  ON UPDATE CASCADE
);

CREATE TABLE `tmtraitdependencies` (
  `traitid` INT UNSIGNED NOT NULL,
  `parentstateid` INT UNSIGNED NOT NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`traitid`, `parentstateid`),
  INDEX `FK_tmdepend_traitid_idx` (`traitid` ASC),
  INDEX `FK_tmdepend_stateid_idx` (`parentstateid` ASC),
  CONSTRAINT `FK_tmdepend_traitid`  FOREIGN KEY (`traitid`) REFERENCES `tmtraits` (`traitid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_tmdepend_stateid`  FOREIGN KEY (`parentstateid`)  REFERENCES `tmstates` (`stateid`)  ON DELETE CASCADE  ON UPDATE CASCADE  
);


#Occurrence associations
ALTER TABLE `omoccurassococcurrences` 
  DROP FOREIGN KEY `omossococcur_occid`,
  DROP FOREIGN KEY `omossococcur_occidassoc`;

ALTER TABLE `omoccurassococcurrences` 
  DROP COLUMN `createdby`,
  CHANGE COLUMN `aoid` `associd` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `sciname` `verbatimsciname` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `tid` `tid` INT(11) UNSIGNED NULL DEFAULT NULL ,
  ADD COLUMN `basisOfRecord` VARCHAR(45) NULL AFTER `identifier`,
  ADD COLUMN `createduid` INT UNSIGNED NULL AFTER `notes`,
  ADD COLUMN `datelastmodified` DATETIME NULL AFTER `createduid`,
  ADD COLUMN `modifieduid` INT UNSIGNED NULL AFTER `datelastmodified`,
  ADD INDEX `FK_occurassoc_tid_idx` (`tid` ASC),
  ADD INDEX `FK_occurassoc_uidmodified_idx` (`modifieduid` ASC),
  ADD INDEX `FK_occurassoc_uidcreated_idx` (`createduid` ASC);

ALTER TABLE `omoccurassococcurrences` 
  ADD CONSTRAINT `FK_occurassoc_occid`
    FOREIGN KEY (`occid`)   REFERENCES `omoccurrences` (`occid`)    ON DELETE CASCADE    ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_occurassoc_occidassoc`
    FOREIGN KEY (`occidassociate`)    REFERENCES `omoccurrences` (`occid`)    ON DELETE SET NULL    ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_occurassoc_tid`
    FOREIGN KEY (`tid`)  REFERENCES `taxa` (`TID`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_occurassoc_uidmodified`
    FOREIGN KEY (`modifieduid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_occurassoc_uidcreated`
    FOREIGN KEY (`createduid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `omoccurassococcurrences` 
  RENAME TO  `omoccurassociations` ;

ALTER TABLE `omoccurassociations` 
  ADD INDEX `INDEX_verbatimSciname` (`verbatimsciname` ASC);

DROP TABLE IF EXISTS `omoccurassoctaxa`;

CREATE TABLE `lkupmunicipality` (
  `municipalityId` int NOT NULL AUTO_INCREMENT,
  `stateId` int NOT NULL,
  `municipalityName` varchar(100) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`municipalityId`),
  UNIQUE KEY `unique_municipality` (`stateId`,`municipalityName`),
  KEY `fk_stateprovince` (`stateId`),
  KEY `index_municipalityname` (`municipalityName`),
  CONSTRAINT `lkupmunicipality_ibfk_1` FOREIGN KEY (`stateId`) REFERENCES `lkupstateprovince` (`stateId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


#Checklist voucher changes
ALTER TABLE `fmvouchers` 
  DROP COLUMN `Collector`,
  ADD COLUMN `preferredImage` INT NULL DEFAULT 0 AFTER `editornotes`;

ALTER TABLE `fmvouchers` 
  DROP FOREIGN KEY `FK_vouchers_cl`;

#Remove any bad double referenced occurrence vouchers within the same checklist
CREATE TABLE `temp_voucher_delete` (
  `clid` INT NOT NULL,
  `occid` INT NOT NULL);

INSERT INTO `temp_voucher_delete`(clid,occid)
  SELECT CLID,occid FROM fmvouchers GROUP BY CLID, occid HAVING Count(*)>1;

DELETE v.*
FROM fmvouchers v INNER JOIN temp_voucher_delete t ON v.clid = t.clid AND v.occid = t.occid
INNER JOIN taxstatus ts ON v.tid = ts.tid
WHERE ts.taxauthid = 1 AND ts.tid != ts.tidaccepted;

DELETE v.*
FROM fmvouchers v INNER JOIN temp_voucher_delete t ON v.clid = t.clid AND v.occid = t.occid;

DROP TABLE temp_voucher_delete;

ALTER TABLE `fmvouchers` 
  CHANGE COLUMN `TID` `TID` INT(10) UNSIGNED NULL ,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`occid`, `CLID`);

ALTER TABLE `fmvouchers` 
  ADD CONSTRAINT `FK_vouchers_cl`  FOREIGN KEY (`TID` , `CLID`)  REFERENCES `fmchklsttaxalink` (`TID` , `CLID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

  
#Checklist changes
ALTER TABLE `fmchklstprojlink` 
  ADD COLUMN `clNameOverride` VARCHAR(100) NULL AFTER `clid`,
  ADD COLUMN `mapChecklist` SMALLINT NULL DEFAULT 1 AFTER `clNameOverride`,
  ADD COLUMN `notes` VARCHAR(250) NULL AFTER `mapChecklist`;

ALTER TABLE `fmchecklists` 
  ADD COLUMN `politicalDivision` VARCHAR(45) NULL AFTER `Type`,
  ADD COLUMN `iconUrl` VARCHAR(150) NULL AFTER `defaultSettings`,
  ADD COLUMN `headerUrl` VARCHAR(150) NULL AFTER `iconUrl`;

ALTER TABLE `fmprojects` 
  ADD COLUMN `iconUrl` VARCHAR(150) NULL AFTER `notes`,
  ADD COLUMN `headerUrl` VARCHAR(150) NULL AFTER `iconUrl`,
  ADD COLUMN `dynamicProperties` TEXT NULL AFTER `ispublic`;

ALTER TABLE `fmprojects` 
  CHANGE COLUMN `fulldescription` `fulldescription` VARCHAR(5000) NULL DEFAULT NULL ;

#Identification key
ALTER TABLE `kmcharacterlang` DROP FOREIGN KEY `FK_characterlang_1`;
ALTER TABLE `kmcharacterlang` 
  ADD CONSTRAINT `FK_characterlang_1` FOREIGN KEY (`cid`)  REFERENCES `kmcharacters` (`cid`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `kmchartaxalink` 
  DROP FOREIGN KEY `FK_chartaxalink_cid`,
  DROP FOREIGN KEY `FK_chartaxalink_tid`;
ALTER TABLE `kmchartaxalink` 
  ADD CONSTRAINT `FK_chartaxalink_cid`  FOREIGN KEY (`CID`)  REFERENCES `kmcharacters` (`cid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_chartaxalink_tid`  FOREIGN KEY (`TID`)  REFERENCES `taxa` (`TID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `kmchardependance` 
  DROP FOREIGN KEY `FK_chardependance_cid`,
  DROP FOREIGN KEY `FK_chardependance_cs`;
ALTER TABLE `kmchardependance` 
  ADD CONSTRAINT `FK_chardependance_cid`  FOREIGN KEY (`CID`)  REFERENCES `kmcharacters` (`cid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_chardependance_cs`  FOREIGN KEY (`CIDDependance` , `CSDependance`)  REFERENCES `kmcs` (`cid` , `cs`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `kmcsimages`
  DROP FOREIGN KEY `FK_kscsimages_kscs`;
ALTER TABLE `kmcsimages` 
  ADD CONSTRAINT `FK_kscsimages_kscs`  FOREIGN KEY (`cid` , `cs`)  REFERENCES `kmcs` (`cid` , `cs`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `kmcs` 
  DROP FOREIGN KEY `FK_cs_chars`;
ALTER TABLE `kmcs` 
  ADD CONSTRAINT `FK_cs_chars`  FOREIGN KEY (`cid`)  REFERENCES `kmcharacters` (`cid`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `kmcslang` 
  DROP FOREIGN KEY `FK_cslang_1`;
ALTER TABLE `kmcslang` 
  ADD CONSTRAINT `FK_cslang_1`  FOREIGN KEY (`cid` , `cs`)  REFERENCES `kmcs` (`cid` , `cs`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `kmdescr` 
  DROP FOREIGN KEY `FK_descr_cs`,
  DROP FOREIGN KEY `FK_descr_tid`;
ALTER TABLE `kmdescr` 
  ADD CONSTRAINT `FK_descr_cs`  FOREIGN KEY (`CID` , `CS`)  REFERENCES `kmcs` (`cid` , `cs`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_descr_tid`  FOREIGN KEY (`TID`)  REFERENCES `taxa` (`TID`)  ON DELETE CASCADE  ON UPDATE CASCADE;


#Occurrence revisions
CREATE TABLE `omoccurrevisions` (
  `orid` INT NOT NULL AUTO_INCREMENT,
  `occid` INT UNSIGNED NOT NULL,
  `oldValues` TEXT NULL,
  `newValues` TEXT NULL,
  `externalSource` VARCHAR(45) NULL,
  `externalEditor` VARCHAR(100) NULL,
  `reviewStatus` INT NULL,
  `appliedStatus` INT NULL,
  `errorMessage` VARCHAR(500) NULL,
  `uid` INT UNSIGNED NULL,
  `externalTimestamp` DATETIME NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`orid`),
  INDEX `fk_omrevisions_occid_idx` (`occid` ASC),
  INDEX `fk_omrevisions_uid_idx` (`uid` ASC),
  INDEX `Index_omrevisions_applied` (`appliedStatus` ASC),
  INDEX `Index_omrevisions_reviewed` (`reviewStatus` ASC),
  INDEX `Index_omrevisions_source` (`externalSource` ASC),
  INDEX `Index_omrevisions_editor` (`externalEditor` ASC),
  CONSTRAINT `fk_omrevisions_occid`  FOREIGN KEY (`occid`)  REFERENCES `omoccurrences` (`occid`)   ON DELETE CASCADE   ON UPDATE CASCADE,
  CONSTRAINT `fk_omrevisions_uid`    FOREIGN KEY (`uid`)    REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE
);

ALTER TABLE `omoccurrevisions` 
  ADD COLUMN `guid` VARCHAR(45) NULL AFTER `externalEditor`,
  ADD UNIQUE INDEX `guid_UNIQUE` (`guid` ASC);

ALTER TABLE `omoccuredits` 
  ADD COLUMN `guid` VARCHAR(45) NULL AFTER `AppliedStatus`,
  ADD UNIQUE INDEX `guid_UNIQUE` (`guid` ASC);

ALTER TABLE `omoccurgeoindex` 
  DROP FOREIGN KEY `FK_specgeoindex_taxa`;
ALTER TABLE `omoccurgeoindex` 
  ADD CONSTRAINT `FK_specgeoindex_taxa`  FOREIGN KEY (`tid`)  REFERENCES `taxa` (`TID`)  ON DELETE CASCADE  ON UPDATE CASCADE;

CREATE TABLE `omcollpuboccurlink` (
  `pubid` INT UNSIGNED NOT NULL,
  `occid` INT UNSIGNED NOT NULL,
  `verification` INT NOT NULL DEFAULT 0,
  `refreshtimestamp` DATETIME NOT NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`pubid`, `occid`),
  INDEX `FK_ompuboccid_idx` (`occid` ASC),
  CONSTRAINT `FK_ompuboccid`  FOREIGN KEY (`occid`)  REFERENCES `omoccurrences` (`occid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_ompubpubid`  FOREIGN KEY (`pubid`)  REFERENCES `omcollpublications` (`pubid`)  ON DELETE CASCADE  ON UPDATE CASCADE);


#Remove deprecated survey tables
DROP TABLE `omsurveyprojlink`;
DROP TABLE `omsurveyoccurlink`;
DROP TABLE `omsurveys`;


#Copy over INSERT data priming statements that should have been included in original schema definition 
INSERT IGNORE INTO `adminlanguages`(langid,langname,iso639_1) 
VALUES ('1', 'English', 'en'), ('2', 'German', 'de'), ('3', 'French', 'fr'), ('4', 'Dutch', 'nl'), ('5', 'Italian', 'it'), ('6', 'Spanish', 'es'), ('7', 'Polish', 'pl'), ('8', 'Russian', 'ru'), ('9', 'Japanese', 'ja'), ('10', 'Portuguese', 'pt'), ('11', 'Swedish', 'sv'), ('12', 'Chinese', 'zh'), ('13', 'Catalan', 'ca'), ('14', 'Ukrainian', 'uk'), ('15', 'Norwegian (Bokm�l)', 'no'), ('16', 'Finnish', 'fi'), ('17', 'Vietnamese', 'vi'), ('18', 'Czech', 'cs'), ('19', 'Hungarian', 'hu'), ('20', 'Korean', 'ko'), ('21', 'Indonesian', 'id'), ('22', 'Turkish', 'tr'), ('23', 'Romanian', 'ro'), ('24', 'Persian', 'fa'), ('25', 'Arabic', 'ar'), ('26', 'Danish', 'da'), ('27', 'Esperanto', 'eo'), ('28', 'Serbian', 'sr'), ('29', 'Lithuanian', 'lt'), ('30', 'Slovak', 'sk'), ('31', 'Malay', 'ms'), ('32', 'Hebrew', 'he'), ('33', 'Bulgarian', 'bg'), ('34', 'Slovenian', 'sl'), ('35', 'Volap�k', 'vo'), ('36', 'Kazakh', 'kk'), ('37', 'Waray-Waray', 'war'), ('38', 'Basque', 'eu'), ('39', 'Croatian', 'hr'), ('40', 'Hindi', 'hi'), ('41', 'Estonian', 'et'), ('42', 'Azerbaijani', 'az'), ('43', 'Galician', 'gl'), ('44', 'Simple English', 'simple'), ('45', 'Norwegian (Nynorsk)', 'nn'), ('46', 'Thai', 'th'), ('47', 'Newar / Nepal Bhasa', 'new'), ('48', 'Greek', 'el'), ('49', 'Aromanian', 'roa-rup'), ('50', 'Latin', 'la'), ('51', 'Occitan', 'oc'), ('52', 'Tagalog', 'tl'), ('53', 'Haitian', 'ht'), ('54', 'Macedonian', 'mk'), ('55', 'Georgian', 'ka'), ('56', 'Serbo-Croatian', 'sh'), ('57', 'Telugu', 'te'), ('58', 'Piedmontese', 'pms'), ('59', 'Cebuano', 'ceb'), ('60', 'Tamil', 'ta'), ('61', 'Belarusian (Tara�kievica)', 'be-x-old'), ('62', 'Breton', 'br'), ('63', 'Latvian', 'lv'), ('64', 'Javanese', 'jv'), ('65', 'Albanian', 'sq'), ('66', 'Belarusian', 'be'), ('67', 'Marathi', 'mr'), ('68', 'Welsh', 'cy'), ('69', 'Luxembourgish', 'lb'), ('70', 'Icelandic', 'is'), ('71', 'Bosnian', 'bs'), ('72', 'Yoruba', 'yo'), ('73', 'Malagasy', 'mg'), ('74', 'Aragonese', 'an'), ('75', 'Bishnupriya Manipuri', 'bpy'), ('76', 'Lombard', 'lmo'), ('77', 'West Frisian', 'fy'), ('78', 'Bengali', 'bn'), ('79', 'Ido', 'io'), ('80', 'Swahili', 'sw'), ('81', 'Gujarati', 'gu'), ('82', 'Malayalam', 'ml'), ('83', 'Western Panjabi', 'pnb'), ('84', 'Afrikaans', 'af'), ('85', 'Low Saxon', 'nds'), ('86', 'Sicilian', 'scn'), ('87', 'Urdu', 'ur'), ('88', 'Kurdish', 'ku'), ('89', 'Cantonese', 'zh-yue'), ('90', 'Armenian', 'hy'), ('91', 'Quechua', 'qu'), ('92', 'Sundanese', 'su'), ('93', 'Nepali', 'ne'), ('94', 'Zazaki', 'diq'), ('95', 'Asturian', 'ast'), ('96', 'Tatar', 'tt'), ('97', 'Neapolitan', 'nap'), ('98', 'Irish', 'ga'), ('99', 'Chuvash', 'cv'), ('100', 'Samogitian', 'bat-smg'), ('101', 'Walloon', 'wa'), ('102', 'Amharic', 'am'), ('103', 'Kannada', 'kn'), ('104', 'Alemannic', 'als'), ('105', 'Buginese', 'bug'), ('106', 'Burmese', 'my'), ('107', 'Interlingua', 'ia');

INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('HasOrganism','Image shows an organism.','Organism',0);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('HasLabel','Image shows label data.','Label',10);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('HasIDLabel','Image shows an annotation/identification label.','Annotation',20);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('TypedText','Image has typed or printed text.','Typed/Printed',30);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('Handwriting','Image has handwritten label text.','Handwritten',40);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('ShowsHabitat','Field image of habitat.','Habitat',50);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('HasProblem','There is a problem with this image.','QC Problem',60);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('ImageOfAdult','Image contains the adult organism.','Adult',80);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('Diagnostic','Image contains a diagnostic character.','Diagnostic',70);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('ImageOfImmature','Image contains the immature organism.','Immature',90);

insert into ctrelationshiptypes (relationship, inverse, collective) values ('Child of', 'Parent of', 'Children');
insert into ctrelationshiptypes (relationship, inverse, collective) values ('Student of', 'Teacher of', 'Students');
insert into ctrelationshiptypes (relationship, inverse, collective) values ('Spouse of', 'Spouse of', 'Married to');
insert into ctrelationshiptypes (relationship, inverse, collective) values ('Could be', 'Confused with', 'Confused with');

insert into ctnametypes (type) values ('Full Name');
insert into ctnametypes (type) values ('Initials Last Name');
insert into ctnametypes (type) values ('Last Name, Initials');
insert into ctnametypes (type) values ('First Initials Last');
insert into ctnametypes (type) values ('First Last');
insert into ctnametypes (type) values ('Standard Abbreviation');
insert into ctnametypes (type) values ('Standard DwC List');
insert into ctnametypes (type) values ('Also Known As');

INSERT INTO `referencetype` VALUES ('1', 'Generic', null, 'Title', 'SecondaryTitle', 'PlacePublished', 'Publisher', 'Volume', 'NumberVolumes', 'Number', 'Pages', 'Section', 'TertiaryTitle', 'Edition', 'Date', 'TypeWork', 'ShortTitle', 'AlternativeTitle', 'Isbn_Issn', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('2', 'Journal Article', null, 'Title', 'Periodical Title', null, null, 'Volume', null, 'Issue', 'Pages', null, null, null, 'Date', null, 'Short Title', 'Alt. Jour.', null, 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('3', 'Book', '1', 'Title', 'Series Title', 'City', 'Publisher', 'Volume', 'No. Vols.', 'Number', 'Pages', null, null, 'Edition', 'Date', null, 'Short Title', null, 'ISBN', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('4', 'Book Section', null, 'Title', 'Book Title', 'City', 'Publisher', 'Volume', 'No. Vols.', 'Number', 'Pages', null, 'Ser. Title', 'Edition', 'Date', null, 'Short Title', null, 'ISBN', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('5', 'Manuscript', null, 'Title', 'Collection Title', 'City', null, null, null, 'Number', 'Pages', null, null, 'Edition', 'Date', 'Type Work', 'Short Title', null, null, 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('6', 'Edited Book', '1', 'Title', 'Series Title', 'City', 'Publisher', 'Volume', 'No. Vols.', 'Number', 'Pages', null, null, 'Edition', 'Date', null, 'Short Title', null, 'ISBN', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('7', 'Magazine Article', null, 'Title', 'Periodical Title', null, null, 'Volume', null, 'Issue', 'Pages', null, null, null, 'Date', null, 'Short Title', null, null, 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('8', 'Newspaper Article', null, 'Title', 'Periodical Title', 'City', null, null, null, null, 'Pages', 'Section', null, 'Edition', 'Date', 'Type Art.', 'Short Title', null, null, 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('9', 'Conference Proceedings', null, 'Title', 'Conf. Name', 'Conf. Loc.', 'Publisher', 'Volume', 'No. Vols.', null, 'Pages', null, 'Ser. Title', 'Edition', 'Date', null, 'Short Title', null, 'ISBN', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('10', 'Thesis', null, 'Title', 'Academic Dept.', 'City', 'University', null, null, null, 'Pages', null, null, null, 'Date', 'Thesis Type', 'Short Title', null, null, 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('11', 'Report', null, 'Title', null, 'City', 'Institution', null, null, null, 'Pages', null, null, null, 'Date', 'Type Work', 'Short Title', null, 'Rpt. No.', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('12', 'Personal Communication', null, 'Title', null, 'City', 'Publisher', null, null, null, null, null, null, null, 'Date', 'Type Work', 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('13', 'Computer Program', null, 'Title', null, 'City', 'Publisher', 'Version', null, null, null, null, null, 'Platform', 'Date', 'Type Work', 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('14', 'Electronic Source', null, 'Title', null, null, 'Publisher', 'Access Year', 'Extent', 'Acc. Date', null, null, null, 'Edition', 'Date', 'Medium', 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('15', 'Audiovisual Material', null, 'Title', 'Collection Title', 'City', 'Publisher', null, null, 'Number', null, null, null, null, 'Date', 'Type Work', 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('16', 'Film or Broadcast', null, 'Title', 'Series Title', 'City', 'Distributor', null, null, null, 'Length', null, null, null, 'Date', 'Medium', 'Short Title', null, 'ISBN', null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('17', 'Artwork', null, 'Title', null, 'City', 'Publisher', null, null, null, null, null, null, null, 'Date', 'Type Work', 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('18', 'Map', null, 'Title', null, 'City', 'Publisher', null, null, null, 'Scale', null, null, 'Edition', 'Date', 'Type Work', 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('19', 'Patent', null, 'Title', 'Published Source', 'Country', 'Assignee', 'Volume', 'No. Vols.', 'Issue', 'Pages', null, null, null, 'Date', null, 'Short Title', null, 'Pat. No.', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('20', 'Hearing', null, 'Title', 'Committee', 'City', 'Publisher', null, null, 'Doc. No.', 'Pages', null, 'Leg. Boby', 'Session', 'Date', null, 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('21', 'Bill', null, 'Title', 'Code', null, null, 'Code Volume', null, 'Bill No.', 'Pages', 'Section', 'Leg. Boby', 'Session', 'Date', null, 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('22', 'Statute', null, 'Title', 'Code', null, null, 'Code Number', null, 'Law No.', '1st Pg.', 'Section', null, 'Session', 'Date', null, 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('23', 'Case', null, 'Title', null, null, 'Court', 'Reporter Vol.', null, null, null, null, null, null, 'Date', null, null, null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('24', 'Figure', null, 'Title', 'Source Program', null, null, null, '-', null, null, null, null, null, 'Date', null, null, null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('25', 'Chart or Table', null, 'Title', 'Source Program', null, null, null, null, null, null, null, null, null, 'Date', null, null, null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('26', 'Equation', null, 'Title', 'Source Program', null, null, 'Volume', null, 'Number', null, null, null, null, 'Date', null, null, null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('27', 'Book Series', '1', 'Title', null, 'City', 'Publisher', null, 'No. Vols.', null, 'Pages', null, null, 'Edition', 'Date', null, null, null, 'ISBN', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('28', 'Determination', null, 'Title', null, null, 'Institution', null, null, null, null, null, null, null, 'Date', null, null, null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('29', 'Sub-Reference', null, 'Title', null, null, null, null, null, null, 'Pages', null, null, null, 'Date', null, null, null, null, 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('30', 'Periodical', '1', 'Title', null, 'City', null, 'Volume', null, 'Issue', null, null, null, 'Edition', 'Date', null, 'Short Title', 'Alt. Jour.', null, null, null, '2014-10-30 21:34:44');
INSERT INTO `referencetype` VALUES ('31', 'Web Page', null, 'Title', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, '2014-10-30 21:37:12');


#Language support extended
ALTER TABLE `kmcslang` 
  ADD INDEX `FK_cslang_lang_idx` (`langid` ASC),
  ADD CONSTRAINT `FK_cslang_lang`  FOREIGN KEY (`langid`)  REFERENCES `adminlanguages` (`langid`)  ON DELETE NO ACTION  ON UPDATE NO ACTION;

ALTER TABLE `kmcharacterlang` 
  ADD INDEX `FK_charlang_lang_idx` (`langid` ASC);

ALTER TABLE `kmcharacterlang` 
  ADD CONSTRAINT `FK_charlang_lang`  FOREIGN KEY (`langid`)  REFERENCES `adminlanguages` (`langid`)  ON DELETE NO ACTION  ON UPDATE NO ACTION;

ALTER TABLE `taxadescrblock` 
  ADD COLUMN `langid` INT NULL AFTER `language`,
  ADD INDEX `FK_taxadesc_lang_idx` (`langid` ASC);

ALTER TABLE `taxadescrblock` 
  ADD CONSTRAINT `FK_taxadesc_lang`  FOREIGN KEY (`langid`)  REFERENCES `adminlanguages` (`langid`)  ON DELETE NO ACTION  ON UPDATE NO ACTION;

UPDATE taxadescrblock t INNER JOIN adminlanguages l ON t.language = l.langname
  SET t.langid = l.langid
  WHERE t.langid IS NULL;

ALTER TABLE `taxadescrblock`
	MODIFY COLUMN `caption`  varchar(40) NULL DEFAULT NULL AFTER `tid`;


ALTER TABLE `taxavernaculars` 
  ADD COLUMN `langid` INT NULL AFTER `Language`,
  ADD INDEX `FK_vern_lang_idx` (`langid` ASC);

ALTER TABLE `taxavernaculars` 
  ADD CONSTRAINT `FK_vern_lang`  FOREIGN KEY (`langid`)  REFERENCES `adminlanguages` (`langid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

UPDATE taxavernaculars t INNER JOIN adminlanguages l ON t.language = l.langname
  SET t.langid = l.langid
  WHERE t.langid IS NULL;


#Misc
DELETE FROM `uploadspectemp`;
ALTER TABLE `uploadspectemp` 
  ADD COLUMN `exsiccatiIdentifier` INT NULL AFTER `genericcolumn2`,
  ADD COLUMN `exsiccatiNumber` VARCHAR(45) NULL AFTER `exsiccatiIdentifier`,
  ADD COLUMN `exsiccatiNotes` VARCHAR(250) NULL AFTER `exsiccatiNumber`,
  ADD COLUMN `host`  varchar(250) NULL AFTER `substrate`;

DELETE FROM `uploadtaxa`;
ALTER TABLE `uploadtaxa` 
  ADD COLUMN `ErrorStatus` VARCHAR(150) NULL AFTER `Hybrid`,
  ADD COLUMN `RankName` VARCHAR(45) NULL AFTER `RankId`,
  DROP COLUMN `KingdomID`;

ALTER TABLE `uploadtaxa` 
  ADD INDEX `parentStr_index` (`ParentStr` ASC),
  ADD INDEX `acceptedStr_index` (`AcceptedStr` ASC),
  ADD INDEX `unitname1_index` (`UnitName1` ASC),
  ADD INDEX `sourceParentId_index` (`SourceParentId` ASC),
  ADD INDEX `acceptance_index` (`Acceptance` ASC);

ALTER TABLE `uploadtaxa` 
  ADD UNIQUE INDEX `UNIQUE_sciname` (`SciName` ASC, `RankId` ASC, `Author` ASC);

ALTER TABLE `taxa` 
  DROP COLUMN `KingdomID`,
  DROP INDEX `sciname_unique`,
  ADD UNIQUE INDEX `sciname_unique` (`SciName` ASC, `RankId` ASC, `Author` ASC),
  ADD INDEX `sciname_index` (`SciName` ASC);

ALTER TABLE `taxa` 
  ADD INDEX `idx_taxa_kingdomName` (`kingdomName` ASC);
  
ALTER TABLE `taxalinks` 
  ADD COLUMN `inherit` INT NULL DEFAULT 1 AFTER `icon`;

# Needed for FP functions
CREATE INDEX idx_taxacreated ON taxa(initialtimestamp);

ALTER TABLE `taxonunits` 
  DROP COLUMN `kingdomid`,
  ADD UNIQUE INDEX `UNIQUE_taxonunits` (`kingdomName` ASC, `rankid` ASC);

ALTER TABLE `specprocessorprojects` 
  ADD COLUMN `projecttype` VARCHAR(45) NULL AFTER `title`,
  ADD COLUMN `lastrundate` DATE NULL AFTER `source`,
  ADD COLUMN `patternReplace` VARCHAR(45) NULL AFTER `specKeyPattern`,
  ADD COLUMN `replaceStr` VARCHAR(45) NULL AFTER `patternReplace`;

ALTER TABLE `images` 
  CHANGE COLUMN `sourceIdentifier` `sourceIdentifier` VARCHAR(150) NULL DEFAULT NULL,
  ADD COLUMN `referenceUrl` VARCHAR(255) NULL AFTER `sourceurl`,
  ADD COLUMN `dynamicProperties` TEXT NULL AFTER `sourceIdentifier`,
  ADD COLUMN `mediaMD5` VARCHAR(45) NULL DEFAULT NULL AFTER `sourceIdentifier`;

ALTER TABLE `media` 
  ADD COLUMN `mediaMD5` VARCHAR(45) NULL AFTER `notes`;

ALTER TABLE `omcollections` 
  ADD COLUMN `publishToIdigbio` INT(11) AFTER `publishToGbif`,
  ADD COLUMN `aggKeysStr` VARCHAR(1000) AFTER `publishToIdigbio`,
  ADD COLUMN `dwcaUrl` VARCHAR(250) NULL AFTER `aggKeysStr`,
  CHANGE COLUMN `Contact` `Contact` VARCHAR(250) NULL DEFAULT NULL;

ALTER TABLE `omcollections` 
  ADD INDEX `FK_collid_iid_idx` (`iid` ASC);

ALTER TABLE `omcollections` 
  ADD CONSTRAINT `FK_collid_iid` FOREIGN KEY (`iid`) REFERENCES `institutions` (`iid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `omcollectionstats`
	MODIFY COLUMN `dynamicProperties` longtext NULL AFTER `uploadedby`;

ALTER TABLE `omcollcatlink` 
  ADD COLUMN `isPrimary` TINYINT(1) NULL DEFAULT 1 AFTER `collid`;

CREATE TABLE `omoccuraccessstats` (
  `oasid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `occid` INT UNSIGNED NOT NULL,
  `accessdate` DATE NOT NULL,
  `ipaddress` VARCHAR(45) NOT NULL,
  `cnt` INT UNSIGNED NOT NULL,
  `accesstype` VARCHAR(45) NOT NULL,
  `dynamicProperties` VARCHAR(250) NULL,
  `notes` VARCHAR(250) NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`oasid`),
  UNIQUE INDEX `UNIQUE_occuraccess` (`occid` ASC, `accessdate` ASC, `ipaddress` ASC, `accesstype` ASC),
  CONSTRAINT `FK_occuraccess_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`)  ON DELETE CASCADE  ON UPDATE CASCADE);


# Establishes many-many relationship to be used in DwC eml.xml file
CREATE TABLE `omcollectioncontacts` (
  `collid` INT UNSIGNED NOT NULL,
  `uid` INT UNSIGNED NOT NULL,
  `positionName` VARCHAR(45) NULL,
  `role` VARCHAR(45) NULL,
  `notes` VARCHAR(250) NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`collid`, `uid`),
  INDEX `FK_contact_uid_idx` (`uid` ASC),
  CONSTRAINT `FK_contact_collid`   FOREIGN KEY (`collid`)   REFERENCES `omcollections` (`CollID`)   ON DELETE CASCADE   ON UPDATE CASCADE,
  CONSTRAINT `FK_contact_uid`   FOREIGN KEY (`uid`)   REFERENCES `users` (`uid`)   ON DELETE CASCADE   ON UPDATE CASCADE);

CREATE TABLE `omoccurrencetypes` (
  `occurtypeid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `occid` INT UNSIGNED NULL,
  `typestatus` VARCHAR(45) NULL,
  `typeDesignationType` VARCHAR(45) NULL,
  `typeDesignatedBy` VARCHAR(45) NULL,
  `scientificName` VARCHAR(250) NULL,
  `scientificNameAuthorship` VARCHAR(45) NULL,
  `tidinterpreted` INT UNSIGNED NULL,
  `basionym` VARCHAR(250) NULL,
  `refid` INT NULL,
  `bibliographicCitation` VARCHAR(250) NULL,
  `dynamicProperties` VARCHAR(250) NULL,
  `notes` VARCHAR(250) NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`occurtypeid`),
  INDEX `FK_occurtype_occid_idx` (`occid` ASC),
  INDEX `FK_occurtype_refid_idx` (`refid` ASC),
  INDEX `FK_occurtype_tid_idx` (`tidinterpreted` ASC),
  CONSTRAINT `FK_occurtype_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_occurtype_refid` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  CONSTRAINT `FK_occurtype_tid` FOREIGN KEY (`tidinterpreted`) REFERENCES `taxa` (`TID`)  ON DELETE SET NULL  ON UPDATE CASCADE);

ALTER TABLE `omoccurrences` 
  ADD INDEX `Index_locality` (`locality`(100) ASC),
  ADD INDEX `Index_otherCatalogNumbers` (`otherCatalogNumbers` ASC);

ALTER TABLE `omoccurrences` 
  ADD COLUMN `waterBody`  varchar(75) NULL AFTER `preparations`,
  ADD COLUMN `locationID` VARCHAR(100) NULL AFTER `preparations`,
  ADD COLUMN `eventID` VARCHAR(45) NULL AFTER `fieldnumber`,
  ADD COLUMN `latestDateCollected` DATE NULL AFTER `eventDate`,
  ADD COLUMN `dynamicFields` TEXT NULL AFTER `labelProject`,
  CHANGE COLUMN `establishmentMeans` `establishmentMeans` VARCHAR(150) NULL DEFAULT NULL,
  CHANGE COLUMN `disposition` `disposition` varchar(250) NULL DEFAULT NULL,
  ADD INDEX `Index_latestDateCollected` (`latestDateCollected` ASC);

ALTER TABLE `omoccurdeterminations` 
  CHANGE COLUMN `identificationRemarks` `identificationRemarks` VARCHAR(500) NULL DEFAULT NULL ;

ALTER TABLE `salixwordstats` 
  ADD INDEX `INDEX_secondword` (`secondword` ASC);


ALTER TABLE `users` 
  ADD COLUMN `guid` VARCHAR(45) NULL AFTER `accessrights`;


# Spatial Indexing
SET FOREIGN_KEY_CHECKS=0;

TRUNCATE TABLE `omoccurpoints`;

SET FOREIGN_KEY_CHECKS=1;

INSERT INTO omoccurpoints (occid,point)
SELECT occid,Point(decimalLatitude, decimalLongitude) FROM omoccurrences WHERE decimalLatitude IS NOT NULL AND decimalLongitude IS NOT NULL;

DELIMITER //
DROP TRIGGER IF EXISTS `omoccurrencesfulltext_insert`//
DROP TRIGGER IF EXISTS `omoccurrencesfulltextpoint_insert`//
CREATE TRIGGER `omoccurrences_insert` AFTER INSERT ON `omoccurrences`
FOR EACH ROW BEGIN
	IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
		INSERT INTO omoccurpoints (`occid`,`point`) 
		VALUES (NEW.`occid`,Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`));
	END IF;
	INSERT INTO omoccurrencesfulltext (`occid`,`recordedby`,`locality`) 
	VALUES (NEW.`occid`,NEW.`recordedby`,NEW.`locality`);
END
//

DROP TRIGGER IF EXISTS `omoccurrencesfulltext_update`//
DROP TRIGGER IF EXISTS `omoccurrencesfulltextpoint_update`//
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
	END IF;
	UPDATE omoccurrencesfulltext 
	SET `recordedby` = NEW.`recordedby`,`locality` = NEW.`locality`
	WHERE `occid` = NEW.`occid`;
END
//

DROP TRIGGER IF EXISTS `omoccurrencesfulltext_delete`//
DROP TRIGGER IF EXISTS `omoccurrencesfulltextpoint_delete`//
CREATE TRIGGER `omoccurrences_delete` BEFORE DELETE ON `omoccurrences`
FOR EACH ROW BEGIN
	DELETE FROM omoccurpoints WHERE `occid` = OLD.`occid`;
	DELETE FROM omoccurrencesfulltext WHERE `occid` = OLD.`occid`;
END
//

DELIMITER ;

# Glossary tables
ALTER TABLE `glossary`
  ADD COLUMN `resourceurl`  varchar(600) NULL AFTER `notes`,
  MODIFY COLUMN `definition`  varchar(2000) NULL DEFAULT NULL AFTER `term`,
  MODIFY COLUMN `source`  varchar(1000) NULL DEFAULT NULL AFTER `language`,
  ADD COLUMN `translator`  varchar(250) NULL AFTER `source`,
  ADD COLUMN `author`  varchar(250) NULL AFTER `translator`;

ALTER TABLE `glossary` 
  ADD INDEX `Index_glossary_lang` (`language` ASC);

ALTER TABLE `glossaryimages`
  ADD COLUMN `createdBy`  varchar(250) NULL AFTER `notes`;

ALTER TABLE `glossarytaxalink` 
  DROP FOREIGN KEY `glossarytaxalink_ibfk_1`,
  DROP FOREIGN KEY `glossarytaxalink_ibfk_2`;
	
ALTER TABLE `glossarytermlink` DROP FOREIGN KEY `glossarytermlink_ibfk_1`;

CREATE TABLE `glossarysources` (
  `tid` int unsigned NOT NULL,
  `contributorTerm` varchar(1000) DEFAULT NULL,
  `contributorImage` varchar(1000) DEFAULT NULL,
  `translator` varchar(1000) DEFAULT NULL,
  `additionalSources` varchar(1000) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tid`)
);

CREATE TABLE `uploadglossary` (
  `term` varchar(150) DEFAULT NULL,
  `definition` varchar(1000) DEFAULT NULL,
  `language` varchar(45) DEFAULT NULL,
  `source` varchar(1000) DEFAULT NULL,
  `author` varchar(250) DEFAULT NULL,
  `translator` varchar(250) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `resourceurl` varchar(600) DEFAULT NULL,
  `tidStr` varchar(100) DEFAULT NULL,
  `synonym` tinyint(1) DEFAULT NULL,
  `newGroupId` int(10) DEFAULT NULL,
  `currentGroupId` int(10) DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `term_index` (`term`),
  KEY `relatedterm_index` (`newGroupId`)
);

ALTER TABLE `glossarytaxalink` 
  CHANGE COLUMN `glossgrpid` `glossid` INT(10) UNSIGNED NOT NULL ;

ALTER TABLE `glossarytaxalink` 
  ADD CONSTRAINT `FK_glossarytaxa_tid`  FOREIGN KEY (`tid`)  REFERENCES `taxa` (`TID`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_glossarytaxa_glossid`  FOREIGN KEY (`glossid`)  REFERENCES `glossary` (`glossid`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `glossaryimages` 
  DROP FOREIGN KEY `FK_glossaryimages_gloss`;
ALTER TABLE `glossaryimages` 
  ADD INDEX `FK_glossaryimages_uid_idx` (`uid` ASC);
ALTER TABLE `glossaryimages` 
  ADD CONSTRAINT `FK_glossaryimages_glossid` FOREIGN KEY (`glossid`)  REFERENCES `glossary` (`glossid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_glossaryimages_uid`  FOREIGN KEY (`uid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE SET NULL;

ALTER TABLE `glossarysources` 
  ADD CONSTRAINT `FK_glossarysources_tid`  FOREIGN KEY (`tid`)  REFERENCES `taxa` (`TID`)  ON DELETE RESTRICT  ON UPDATE RESTRICT;

ALTER TABLE `glossary` 
  ADD INDEX `FK_glossary_uid_idx` (`uid` ASC);
ALTER TABLE `glossary` 
  ADD CONSTRAINT `FK_glossary_uid`  FOREIGN KEY (`uid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE SET NULL;

DELETE l.*
FROM glossarytermlink l LEFT JOIN glossary g ON l.glossid = g.glossid
WHERE g.glossid IS NULL;

DELETE l.*
FROM glossarytermlink l LEFT JOIN glossary g ON l.glossgrpid = g.glossid
WHERE g.glossid IS NULL;

ALTER TABLE `glossarytermlink` 
  ADD CONSTRAINT `FK_glossarytermlink_glossid`  FOREIGN KEY (`glossid`)  REFERENCES `glossary` (`glossid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_glossarytermlink_glossgrpid`  FOREIGN KEY (`glossgrpid`)  REFERENCES `glossary` (`glossid`)  ON DELETE CASCADE  ON UPDATE CASCADE;

  
#Paleo Tables
CREATE TABLE `paleochronostratigraphy` (
  `chronoId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Eon` varchar(255) DEFAULT NULL,
  `Era` varchar(255) DEFAULT NULL,
  `Period` varchar(255) DEFAULT NULL,
  `Epoch` varchar(255) DEFAULT NULL,
  `Stage` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`chronoId`),
  INDEX `Eon` (`Eon`),
  INDEX `Era` (`Era`),
  INDEX `Period` (`Period`),
  INDEX `Epoch` (`Epoch`),
  INDEX `Stage` (`Stage`)
);

INSERT INTO `paleochronostratigraphy` VALUES ('1', 'Hadean', null, null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('2', 'Archean', null, null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('3', 'Archean', 'Eoarchean', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('4', 'Archean', 'Paleoarchean', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('5', 'Archean', 'Mesoarchean', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('6', 'Archean', 'Neoarchean', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('7', 'Proterozoic', null, null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('8', 'Proterozoic', 'Paleoproterozoic', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('9', 'Proterozoic', 'Paleoproterozoic', 'Siderian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('10', 'Proterozoic', 'Paleoproterozoic', 'Rhyacian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('11', 'Proterozoic', 'Paleoproterozoic', 'Orosirian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('12', 'Proterozoic', 'Paleoproterozoic', 'Statherian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('13', 'Proterozoic', 'Mesoproterozoic', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('14', 'Proterozoic', 'Mesoproterozoic', 'Calymmian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('15', 'Proterozoic', 'Mesoproterozoic', 'Ectasian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('16', 'Proterozoic', 'Mesoproterozoic', 'Stenian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('17', 'Proterozoic', 'Neoproterozoic', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('18', 'Proterozoic', 'Neoproterozoic', 'Tonian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('19', 'Proterozoic', 'Neoproterozoic', 'Gryogenian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('20', 'Proterozoic', 'Neoproterozoic', 'Ediacaran', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('21', 'Phanerozoic', null, null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('22', 'Phanerozoic', 'Paleozoic', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('23', 'Phanerozoic', 'Paleozoic', 'Cambrian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('24', 'Phanerozoic', 'Paleozoic', 'Cambrian', 'Lower Cambrian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('25', 'Phanerozoic', 'Paleozoic', 'Cambrian', 'Middle Cambrian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('26', 'Phanerozoic', 'Paleozoic', 'Cambrian', 'Upper Cambrian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('27', 'Phanerozoic', 'Paleozoic', 'Ordovician', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('28', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Lower Ordovician', null);
INSERT INTO `paleochronostratigraphy` VALUES ('29', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Lower Ordovician', 'Tremadocian');
INSERT INTO `paleochronostratigraphy` VALUES ('30', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Lower Ordovician', 'Floian');
INSERT INTO `paleochronostratigraphy` VALUES ('31', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Middle Ordovician', null);
INSERT INTO `paleochronostratigraphy` VALUES ('32', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Middle Ordovician', 'Dapingian');
INSERT INTO `paleochronostratigraphy` VALUES ('33', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Middle Ordovician', 'Darriwilian');
INSERT INTO `paleochronostratigraphy` VALUES ('34', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Upper Ordivician', null);
INSERT INTO `paleochronostratigraphy` VALUES ('35', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Upper Ordivician', 'Sandbian');
INSERT INTO `paleochronostratigraphy` VALUES ('36', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Upper Ordivician', 'Katian');
INSERT INTO `paleochronostratigraphy` VALUES ('37', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Upper Ordivician', 'Hirnantian');
INSERT INTO `paleochronostratigraphy` VALUES ('38', 'Phanerozoic', 'Paleozoic', 'Silurian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('39', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Llandovery', null);
INSERT INTO `paleochronostratigraphy` VALUES ('40', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Llandovery', 'Rhuddanian');
INSERT INTO `paleochronostratigraphy` VALUES ('41', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Llandovery', 'Aeronian');
INSERT INTO `paleochronostratigraphy` VALUES ('42', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Llandovery', 'Telychian');
INSERT INTO `paleochronostratigraphy` VALUES ('43', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Wenlock', null);
INSERT INTO `paleochronostratigraphy` VALUES ('44', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Wenlock', 'Sheinwoodian');
INSERT INTO `paleochronostratigraphy` VALUES ('45', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Wenlock', 'Homerian');
INSERT INTO `paleochronostratigraphy` VALUES ('46', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Ludlow', null);
INSERT INTO `paleochronostratigraphy` VALUES ('47', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Ludlow', 'Gorstian');
INSERT INTO `paleochronostratigraphy` VALUES ('48', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Ludlow', 'Ludfordian');
INSERT INTO `paleochronostratigraphy` VALUES ('49', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Pridoli', null);
INSERT INTO `paleochronostratigraphy` VALUES ('50', 'Phanerozoic', 'Paleozoic', 'Devonian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('51', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Lower Devonian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('52', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Lower Devonian', 'Lochkovian');
INSERT INTO `paleochronostratigraphy` VALUES ('53', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Lower Devonian', 'Pragian');
INSERT INTO `paleochronostratigraphy` VALUES ('54', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Lower Devonian', 'Emsian');
INSERT INTO `paleochronostratigraphy` VALUES ('55', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Middle Devonian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('56', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Middle Devonian', 'Eifelian');
INSERT INTO `paleochronostratigraphy` VALUES ('57', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Middle Devonian', 'Givetian');
INSERT INTO `paleochronostratigraphy` VALUES ('58', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Upper Devonian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('59', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Upper Devonian', 'Frasnian');
INSERT INTO `paleochronostratigraphy` VALUES ('60', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Upper Devonian', 'Famennian');
INSERT INTO `paleochronostratigraphy` VALUES ('61', 'Phanerozoic', 'Paleozoic', 'Carboniferous', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('62', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Mississippian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('63', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Mississippian', 'Lower Mississippian');
INSERT INTO `paleochronostratigraphy` VALUES ('64', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Mississippian', 'Middle Mississippian');
INSERT INTO `paleochronostratigraphy` VALUES ('65', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Mississippian', 'Upper Mississippian');
INSERT INTO `paleochronostratigraphy` VALUES ('66', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Pennsylvanian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('67', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Pennsylvanian', 'Lower Pennsylvanian');
INSERT INTO `paleochronostratigraphy` VALUES ('68', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Pennsylvanian', 'Middle Pennsylvanian');
INSERT INTO `paleochronostratigraphy` VALUES ('69', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Pennsylvanian', 'Upper Pennsylvanian');
INSERT INTO `paleochronostratigraphy` VALUES ('70', 'Phanerozoic', 'Paleozoic', 'Permian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('71', 'Phanerozoic', 'Paleozoic', 'Permian', 'Cisuralian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('72', 'Phanerozoic', 'Paleozoic', 'Permian', 'Cisuralian', 'Asselian');
INSERT INTO `paleochronostratigraphy` VALUES ('73', 'Phanerozoic', 'Paleozoic', 'Permian', 'Cisuralian', 'Sakmarian');
INSERT INTO `paleochronostratigraphy` VALUES ('74', 'Phanerozoic', 'Paleozoic', 'Permian', 'Cisuralian', 'Artinskian');
INSERT INTO `paleochronostratigraphy` VALUES ('75', 'Phanerozoic', 'Paleozoic', 'Permian', 'Cisuralian', 'Kungurian');
INSERT INTO `paleochronostratigraphy` VALUES ('76', 'Phanerozoic', 'Paleozoic', 'Permian', 'Guadalupian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('77', 'Phanerozoic', 'Paleozoic', 'Permian', 'Guadalupian', 'Roadian');
INSERT INTO `paleochronostratigraphy` VALUES ('78', 'Phanerozoic', 'Paleozoic', 'Permian', 'Guadalupian', 'Wordian');
INSERT INTO `paleochronostratigraphy` VALUES ('79', 'Phanerozoic', 'Paleozoic', 'Permian', 'Guadalupian', 'Capitanian');
INSERT INTO `paleochronostratigraphy` VALUES ('80', 'Phanerozoic', 'Paleozoic', 'Permian', 'Lopingian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('81', 'Phanerozoic', 'Paleozoic', 'Permian', 'Lopingian', 'Wuchiapingian');
INSERT INTO `paleochronostratigraphy` VALUES ('82', 'Phanerozoic', 'Paleozoic', 'Permian', 'Lopingian', 'Changhsingian');
INSERT INTO `paleochronostratigraphy` VALUES ('83', 'Phanerozoic', 'Mesozoic', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('84', 'Phanerozoic', 'Mesozoic', 'Triassic', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('85', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Lower Triassic', null);
INSERT INTO `paleochronostratigraphy` VALUES ('86', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Lower Triassic', 'Induan');
INSERT INTO `paleochronostratigraphy` VALUES ('87', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Lower Triassic', 'Olenekian');
INSERT INTO `paleochronostratigraphy` VALUES ('88', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Middle Triassic', null);
INSERT INTO `paleochronostratigraphy` VALUES ('89', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Middle Triassic', 'Anisian');
INSERT INTO `paleochronostratigraphy` VALUES ('90', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Middle Triassic', 'Ladinian');
INSERT INTO `paleochronostratigraphy` VALUES ('91', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Upper Triassic', null);
INSERT INTO `paleochronostratigraphy` VALUES ('92', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Upper Triassic', 'Carnian');
INSERT INTO `paleochronostratigraphy` VALUES ('93', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Upper Triassic', 'Norian');
INSERT INTO `paleochronostratigraphy` VALUES ('94', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Upper Triassic', 'Rhaetian');
INSERT INTO `paleochronostratigraphy` VALUES ('95', 'Phanerozoic', 'Mesozoic', 'Jurassic', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('96', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Lower Jurassic', null);
INSERT INTO `paleochronostratigraphy` VALUES ('97', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Lower Jurassic', 'Hettangian');
INSERT INTO `paleochronostratigraphy` VALUES ('98', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Lower Jurassic', 'Sinemurian');
INSERT INTO `paleochronostratigraphy` VALUES ('99', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Lower Jurassic', 'Pliensbachian');
INSERT INTO `paleochronostratigraphy` VALUES ('100', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Lower Jurassic', 'Toarcian');
INSERT INTO `paleochronostratigraphy` VALUES ('101', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Middle Jurassic', null);
INSERT INTO `paleochronostratigraphy` VALUES ('102', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Middle Jurassic', 'Aalenian');
INSERT INTO `paleochronostratigraphy` VALUES ('103', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Middle Jurassic', 'Bajocian');
INSERT INTO `paleochronostratigraphy` VALUES ('104', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Middle Jurassic', 'Bathonian');
INSERT INTO `paleochronostratigraphy` VALUES ('105', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Middle Jurassic', 'Callovian');
INSERT INTO `paleochronostratigraphy` VALUES ('106', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Upper Jurassic', null);
INSERT INTO `paleochronostratigraphy` VALUES ('107', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Upper Jurassic', 'Oxfordian');
INSERT INTO `paleochronostratigraphy` VALUES ('108', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Upper Jurassic', 'Kimmeridgian');
INSERT INTO `paleochronostratigraphy` VALUES ('109', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Upper Jurassic', 'Tithonian');
INSERT INTO `paleochronostratigraphy` VALUES ('110', 'Phanerozoic', 'Mesozoic', 'Cretaceous', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('111', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', null);
INSERT INTO `paleochronostratigraphy` VALUES ('112', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', 'Berriasian');
INSERT INTO `paleochronostratigraphy` VALUES ('113', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', 'Valanginian');
INSERT INTO `paleochronostratigraphy` VALUES ('114', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', 'Hauterivian');
INSERT INTO `paleochronostratigraphy` VALUES ('115', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', 'Barremian');
INSERT INTO `paleochronostratigraphy` VALUES ('116', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', 'Aptian');
INSERT INTO `paleochronostratigraphy` VALUES ('117', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', 'Albian');
INSERT INTO `paleochronostratigraphy` VALUES ('118', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', null);
INSERT INTO `paleochronostratigraphy` VALUES ('119', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', 'Cenomanian');
INSERT INTO `paleochronostratigraphy` VALUES ('120', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', 'Turonian');
INSERT INTO `paleochronostratigraphy` VALUES ('121', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', 'Coniacian');
INSERT INTO `paleochronostratigraphy` VALUES ('122', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', 'Santonian');
INSERT INTO `paleochronostratigraphy` VALUES ('123', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', 'Campanian');
INSERT INTO `paleochronostratigraphy` VALUES ('124', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', 'Maastrichtian');
INSERT INTO `paleochronostratigraphy` VALUES ('125', 'Phanerozoic', 'Cenozoic', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('126', 'Phanerozoic', 'Cenozoic', 'Paleogene', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('127', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Paleocene', null);
INSERT INTO `paleochronostratigraphy` VALUES ('128', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Paleocene', 'Danian');
INSERT INTO `paleochronostratigraphy` VALUES ('129', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Paleocene', 'Selandian');
INSERT INTO `paleochronostratigraphy` VALUES ('130', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Paleocene', 'Thanetian');
INSERT INTO `paleochronostratigraphy` VALUES ('131', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Eocene', null);
INSERT INTO `paleochronostratigraphy` VALUES ('132', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Eocene', 'Ypresian');
INSERT INTO `paleochronostratigraphy` VALUES ('133', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Eocene', 'Lutetian');
INSERT INTO `paleochronostratigraphy` VALUES ('134', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Eocene', 'Bartonian');
INSERT INTO `paleochronostratigraphy` VALUES ('135', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Eocene', 'Priabonian');
INSERT INTO `paleochronostratigraphy` VALUES ('136', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Oligocene', null);
INSERT INTO `paleochronostratigraphy` VALUES ('137', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Oligocene', 'Rupelian');
INSERT INTO `paleochronostratigraphy` VALUES ('138', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Oligocene', 'Chattian');
INSERT INTO `paleochronostratigraphy` VALUES ('139', 'Phanerozoic', 'Cenozoic', 'Neogene', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('140', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', null);
INSERT INTO `paleochronostratigraphy` VALUES ('141', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', 'Aquitanian');
INSERT INTO `paleochronostratigraphy` VALUES ('142', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', 'Burdigalian');
INSERT INTO `paleochronostratigraphy` VALUES ('143', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', 'Langhian');
INSERT INTO `paleochronostratigraphy` VALUES ('144', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', 'Serravallian');
INSERT INTO `paleochronostratigraphy` VALUES ('145', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', 'Tortonian');
INSERT INTO `paleochronostratigraphy` VALUES ('146', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', 'Messinian');
INSERT INTO `paleochronostratigraphy` VALUES ('147', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Pliocene', null);
INSERT INTO `paleochronostratigraphy` VALUES ('148', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Pliocene', 'Zanclean');
INSERT INTO `paleochronostratigraphy` VALUES ('149', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Pliocene', 'Piacenzian');
INSERT INTO `paleochronostratigraphy` VALUES ('150', 'Phanerozoic', 'Cenozoic', 'Quaternary', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('151', 'Phanerozoic', 'Cenozoic', 'Quaternary', 'Pleistocene', null);
INSERT INTO `paleochronostratigraphy` VALUES ('152', 'Phanerozoic', 'Cenozoic', 'Quaternary', 'Pleistocene', 'Gelasian');
INSERT INTO `paleochronostratigraphy` VALUES ('153', 'Phanerozoic', 'Cenozoic', 'Quaternary', 'Pleistocene', 'Calabrian');
INSERT INTO `paleochronostratigraphy` VALUES ('154', 'Phanerozoic', 'Cenozoic', 'Quaternary', 'Pleistocene', 'Middle Pleistocene');
INSERT INTO `paleochronostratigraphy` VALUES ('155', 'Phanerozoic', 'Cenozoic', 'Quaternary', 'Pleistocene', 'Upper Pleistocene');
INSERT INTO `paleochronostratigraphy` VALUES ('156', 'Phanerozoic', 'Cenozoic', 'Quaternary', 'Holocene', null);

CREATE TABLE `omoccurlithostratigraphy` (
  `occid` int unsigned NOT NULL,
  `chronoId` int(10) unsigned NOT NULL,
  `Group` varchar(255) DEFAULT NULL,
  `Formation` varchar(255) DEFAULT NULL,
  `Member` varchar(255) DEFAULT NULL,
  `Bed` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`occid`,`chronoId`),
  INDEX `FK_occurlitho_chronoid` (`chronoId`),
  INDEX `FK_occurlitho_occid` (`occid`),
  INDEX `Group` (`Group`),
  INDEX `Formation` (`Formation`),
  INDEX `Member` (`Member`),
  CONSTRAINT `FK_occurlitho_chronoid` FOREIGN KEY (`chronoId`) REFERENCES `paleochronostratigraphy` (`chronoId`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_occurlitho_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE  ON UPDATE CASCADE
);


CREATE TABLE `useraccesstokens` (
  `tokid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int unsigned NOT NULL,
  `token` varchar(50) NOT NULL,
  `device` varchar(50) NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tokid`),
  KEY `FK_useraccesstokens_uid_idx` (`uid`),
  CONSTRAINT `FK_useraccess_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE  ON UPDATE CASCADE
);

