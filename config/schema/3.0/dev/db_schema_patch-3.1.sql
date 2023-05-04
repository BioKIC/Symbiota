INSERT INTO schemaversion (versionnumber) values ("3.1");




# Transfer current determinations from omoccurrences table
INSERT IGNORE INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, family, sciname, verbatimIdentification, scientificNameAuthorship, tidInterpreted, 
identificationQualifier, genus, specificEpithet, verbatimTaxonRank, infraSpecificEpithet, isCurrent, identificationReferences, identificationRemarks, 
taxonRemarks)
SELECT occid, IFNULL(identifiedBy, "unknown"), IFNULL(dateIdentified, "s.d."), family, IFNULL(sciname, "undefined"), scientificName, scientificNameAuthorship, tidInterpreted, identificationQualifier, 
genus, specificEpithet, taxonRank, infraSpecificEpithet, 1 as isCurrent, identificationReferences, identificationRemarks, taxonRemarks
FROM omoccurrences;



