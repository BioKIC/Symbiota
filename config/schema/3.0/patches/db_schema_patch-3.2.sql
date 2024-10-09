INSERT INTO `schemaversion` (versionnumber) values ("3.2");

# Add cultivar name and trade name columns to taxa table

ALTER TABLE `taxa` ADD COLUMN `cultivarEpithet` VARCHAR(50) NULL AFTER unitName3;
ALTER TABLE `taxa` ADD COLUMN `tradeName` VARCHAR(50) NULL AFTER cultivarEpithet;

#Add cultivar and trade name to uploadspectemp

ALTER TABLE `uploadspectemp` ADD COLUMN `cultivarEpithet` VARCHAR(50) NULL AFTER infraspecificEpithet;
ALTER TABLE `uploadspectemp` ADD COLUMN `tradeName` VARCHAR(50) NULL AFTER cultivarEpithet;

ALTER TABLE `uploadtaxa` ADD COLUMN `cultivarEpithet` VARCHAR(50) NULL AFTER `UnitName3`;
ALTER TABLE `uploadtaxa` ADD COLUMN `tradeName` VARCHAR(50) NULL AFTER `cultivarEpithet`;

#Rename cultivated to cultivar

update taxonunits set rankname='Cultivar' where rankname='Cultivated';