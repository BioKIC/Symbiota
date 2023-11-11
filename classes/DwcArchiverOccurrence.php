<?php
class DwcArchiverOccurrence extends Manager{

	private $occurDefArr = array();
	private $schemaType;
	private $extended = false;
	private $includePaleo = false;
	private $includeExsiccatae = false;
	private $includeAssocSeq = false;
	private $relationshipArr;
	private $upperTaxonomy = array();
	private $taxonRankArr = array();
	private $serverDomain;

	public function __construct($conn){
		$this->conn = $conn;
	}

	public function __destruct(){
	}

	public function getOccurrenceArr(){
		if($this->schemaType == 'pensoft') $this->occurDefArr['fields']['Taxon_Local_ID'] = 'ctl.tid AS Taxon_Local_ID';
		else $this->occurDefArr['fields']['id'] = 'o.occid';
		$this->occurDefArr['terms']['institutionCode'] = 'http://rs.tdwg.org/dwc/terms/institutionCode';
		$this->occurDefArr['fields']['institutionCode'] = 'IFNULL(o.institutionCode,c.institutionCode) AS institutionCode';
		$this->occurDefArr['terms']['collectionCode'] = 'http://rs.tdwg.org/dwc/terms/collectionCode';
		$this->occurDefArr['fields']['collectionCode'] = 'IFNULL(o.collectionCode,c.collectionCode) AS collectionCode';
		$this->occurDefArr['terms']['ownerInstitutionCode'] = 'http://rs.tdwg.org/dwc/terms/ownerInstitutionCode';
		$this->occurDefArr['fields']['ownerInstitutionCode'] = 'o.ownerInstitutionCode';
		$this->occurDefArr['terms']['collectionID'] = 'http://rs.tdwg.org/dwc/terms/collectionID';
		$this->occurDefArr['fields']['collectionID'] = 'IFNULL(o.collectionID, c.collectionguid) AS collectionID';
		$this->occurDefArr['terms']['basisOfRecord'] = 'http://rs.tdwg.org/dwc/terms/basisOfRecord';
		$this->occurDefArr['fields']['basisOfRecord'] = 'o.basisOfRecord';
		$this->occurDefArr['terms']['occurrenceID'] = 'http://rs.tdwg.org/dwc/terms/occurrenceID';
		$this->occurDefArr['fields']['occurrenceID'] = 'o.occurrenceID';
		$this->occurDefArr['terms']['catalogNumber'] = 'http://rs.tdwg.org/dwc/terms/catalogNumber';
		$this->occurDefArr['fields']['catalogNumber'] = 'o.catalogNumber';
		$this->occurDefArr['terms']['otherCatalogNumbers'] = 'http://rs.tdwg.org/dwc/terms/otherCatalogNumbers';
		$this->occurDefArr['fields']['otherCatalogNumbers'] = 'o.otherCatalogNumbers';
		$this->occurDefArr['terms']['higherClassification'] = 'http://rs.tdwg.org/dwc/terms/higherClassification';
		$this->occurDefArr['fields']['higherClassification'] = '';
		$this->occurDefArr['terms']['kingdom'] = 'http://rs.tdwg.org/dwc/terms/kingdom';
		$this->occurDefArr['fields']['kingdom'] = '';
		$this->occurDefArr['terms']['phylum'] = 'http://rs.tdwg.org/dwc/terms/phylum';
		$this->occurDefArr['fields']['phylum'] = '';
		$this->occurDefArr['terms']['class'] = 'http://rs.tdwg.org/dwc/terms/class';
		$this->occurDefArr['fields']['class'] = '';
		$this->occurDefArr['terms']['order'] = 'http://rs.tdwg.org/dwc/terms/order';
		$this->occurDefArr['fields']['order'] = '';
		$this->occurDefArr['terms']['family'] = 'http://rs.tdwg.org/dwc/terms/family';
		$this->occurDefArr['fields']['family'] = 'o.family';
		$this->occurDefArr['terms']['scientificName'] = 'http://rs.tdwg.org/dwc/terms/scientificName';
		$this->occurDefArr['fields']['scientificName'] = 'o.sciname AS scientificName';
		//$this->occurDefArr['terms']['verbatimScientificName'] = 'https://symbiota.org/terms/verbatimScientificName';
		//$this->occurDefArr['fields']['verbatimScientificName'] = 'o.scientificname AS verbatimScientificName';
		$this->occurDefArr['terms']['taxonID'] = 'http://rs.tdwg.org/dwc/terms/taxonID';
		$this->occurDefArr['fields']['taxonID'] = 'o.tidinterpreted as taxonID';
		$this->occurDefArr['terms']['scientificNameAuthorship'] = 'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship';
		$this->occurDefArr['fields']['scientificNameAuthorship'] = 'IFNULL(t.author,o.scientificNameAuthorship) AS scientificNameAuthorship';
		$this->occurDefArr['terms']['genus'] = 'http://rs.tdwg.org/dwc/terms/genus';
		$this->occurDefArr['fields']['genus'] = 'IF(t.rankid >= 180,CONCAT_WS(" ",t.unitind1,t.unitname1),NULL) AS genus';
		$this->occurDefArr['terms']['subgenus'] = 'http://rs.tdwg.org/dwc/terms/subgenus';
		$this->occurDefArr['fields']['subgenus'] = '';
		$this->occurDefArr['terms']['specificEpithet'] = 'http://rs.tdwg.org/dwc/terms/specificEpithet';
		$this->occurDefArr['fields']['specificEpithet'] = 'CONCAT_WS(" ",t.unitind2,t.unitname2) AS specificEpithet';
		$this->occurDefArr['terms']['verbatimTaxonRank'] = 'http://rs.tdwg.org/dwc/terms/verbatimTaxonRank';
		$this->occurDefArr['fields']['verbatimTaxonRank'] = 't.unitind3 AS verbatimTaxonRank';
		$this->occurDefArr['terms']['infraspecificEpithet'] = 'http://rs.tdwg.org/dwc/terms/infraspecificEpithet';
		$this->occurDefArr['fields']['infraspecificEpithet'] = 't.unitname3 AS infraspecificEpithet';
		$this->occurDefArr['terms']['taxonRank'] = 'http://rs.tdwg.org/dwc/terms/taxonRank';
		$this->occurDefArr['fields']['taxonRank'] = '';
		$this->occurDefArr['terms']['identifiedBy'] = 'http://rs.tdwg.org/dwc/terms/identifiedBy';
 		$this->occurDefArr['fields']['identifiedBy'] = 'o.identifiedBy';
 		$this->occurDefArr['terms']['dateIdentified'] = 'http://rs.tdwg.org/dwc/terms/dateIdentified';
 		$this->occurDefArr['fields']['dateIdentified'] = 'o.dateIdentified';
 		$this->occurDefArr['terms']['identificationReferences'] = 'http://rs.tdwg.org/dwc/terms/identificationReferences';
 		$this->occurDefArr['fields']['identificationReferences'] = 'o.identificationReferences';
 		$this->occurDefArr['terms']['identificationRemarks'] = 'http://rs.tdwg.org/dwc/terms/identificationRemarks';
 		$this->occurDefArr['fields']['identificationRemarks'] = 'o.identificationRemarks';
 		$this->occurDefArr['terms']['taxonRemarks'] = 'http://rs.tdwg.org/dwc/terms/taxonRemarks';
 		$this->occurDefArr['fields']['taxonRemarks'] = 'o.taxonRemarks';
 		$this->occurDefArr['terms']['identificationQualifier'] = 'http://rs.tdwg.org/dwc/terms/identificationQualifier';
 		$this->occurDefArr['fields']['identificationQualifier'] = 'o.identificationQualifier';
		$this->occurDefArr['terms']['typeStatus'] = 'http://rs.tdwg.org/dwc/terms/typeStatus';
		$this->occurDefArr['fields']['typeStatus'] = 'o.typeStatus';
		$this->occurDefArr['terms']['recordedBy'] = 'http://rs.tdwg.org/dwc/terms/recordedBy';
		$this->occurDefArr['fields']['recordedBy'] = 'o.recordedBy';
		//$this->occurDefArr['terms']['recordedByID'] = 'https://symbiota.org/terms/recordedByID';
		//$this->occurDefArr['fields']['recordedByID'] = 'o.recordedById';
		$this->occurDefArr['terms']['associatedCollectors'] = 'https://symbiota.org/terms/associatedCollectors';
		$this->occurDefArr['fields']['associatedCollectors'] = 'o.associatedCollectors';
		$this->occurDefArr['terms']['recordNumber'] = 'http://rs.tdwg.org/dwc/terms/recordNumber';
		$this->occurDefArr['fields']['recordNumber'] = 'o.recordNumber';
		$this->occurDefArr['terms']['eventDate'] = 'http://rs.tdwg.org/dwc/terms/eventDate';
		$this->occurDefArr['fields']['eventDate'] = 'o.eventDate';
		$this->occurDefArr['terms']['eventDate2'] = 'https://symbiota.org/terms/eventDate2';
		$this->occurDefArr['fields']['eventDate2'] = 'o.eventDate2';
		$this->occurDefArr['terms']['year'] = 'http://rs.tdwg.org/dwc/terms/year';
		$this->occurDefArr['fields']['year'] = 'o.year';
		$this->occurDefArr['terms']['month'] = 'http://rs.tdwg.org/dwc/terms/month';
		$this->occurDefArr['fields']['month'] = 'o.month';
		$this->occurDefArr['terms']['day'] = 'http://rs.tdwg.org/dwc/terms/day';
		$this->occurDefArr['fields']['day'] = 'o.day';
		$this->occurDefArr['terms']['startDayOfYear'] = 'http://rs.tdwg.org/dwc/terms/startDayOfYear';
		$this->occurDefArr['fields']['startDayOfYear'] = 'o.startDayOfYear';
		$this->occurDefArr['terms']['endDayOfYear'] = 'http://rs.tdwg.org/dwc/terms/endDayOfYear';
		$this->occurDefArr['fields']['endDayOfYear'] = 'o.endDayOfYear';
		$this->occurDefArr['terms']['verbatimEventDate'] = 'http://rs.tdwg.org/dwc/terms/verbatimEventDate';
		$this->occurDefArr['fields']['verbatimEventDate'] = 'o.verbatimEventDate';
		$this->occurDefArr['terms']['occurrenceRemarks'] = 'http://rs.tdwg.org/dwc/terms/occurrenceRemarks';
		$this->occurDefArr['terms']['habitat'] = 'http://rs.tdwg.org/dwc/terms/habitat';
		$this->occurDefArr['fields']['occurrenceRemarks'] = 'o.occurrenceRemarks';
		$this->occurDefArr['fields']['habitat'] = 'o.habitat';
		$this->occurDefArr['terms']['substrate'] = 'https://symbiota.org/terms/substrate';
		$this->occurDefArr['fields']['substrate'] = 'o.substrate';
		$this->occurDefArr['terms']['verbatimAttributes'] = 'https://symbiota.org/terms/verbatimAttributes';
		$this->occurDefArr['fields']['verbatimAttributes'] = 'o.verbatimAttributes';
		$this->occurDefArr['terms']['fieldNumber'] = 'http://rs.tdwg.org/dwc/terms/fieldNumber';
		$this->occurDefArr['fields']['fieldNumber'] = 'o.fieldNumber';
		$this->occurDefArr['terms']['eventID'] = 'http://rs.tdwg.org/dwc/terms/eventID';
		$this->occurDefArr['fields']['eventID'] = 'o.eventID';
		$this->occurDefArr['terms']['informationWithheld'] = 'http://rs.tdwg.org/dwc/terms/informationWithheld';
		$this->occurDefArr['fields']['informationWithheld'] = 'o.informationWithheld';
		$this->occurDefArr['terms']['dataGeneralizations'] = 'http://rs.tdwg.org/dwc/terms/dataGeneralizations';
		$this->occurDefArr['fields']['dataGeneralizations'] = 'o.dataGeneralizations';
		$this->occurDefArr['terms']['dynamicProperties'] = 'http://rs.tdwg.org/dwc/terms/dynamicProperties';
		$this->occurDefArr['fields']['dynamicProperties'] = 'o.dynamicProperties';
		$this->occurDefArr['terms']['associatedOccurrences'] = 'http://rs.tdwg.org/dwc/terms/associatedOccurrences';
		$this->occurDefArr['fields']['associatedOccurrences'] = '';
		$this->occurDefArr['terms']['associatedSequences'] = 'http://rs.tdwg.org/dwc/terms/associatedSequences';
		$this->occurDefArr['fields']['associatedSequences'] = '';
		$this->occurDefArr['terms']['associatedTaxa'] = 'http://rs.tdwg.org/dwc/terms/associatedTaxa';
		$this->occurDefArr['fields']['associatedTaxa'] = 'o.associatedTaxa';
		$this->occurDefArr['terms']['reproductiveCondition'] = 'http://rs.tdwg.org/dwc/terms/reproductiveCondition';
		$this->occurDefArr['fields']['reproductiveCondition'] = 'o.reproductiveCondition';
		$this->occurDefArr['terms']['establishmentMeans'] = 'http://rs.tdwg.org/dwc/terms/establishmentMeans';
		$this->occurDefArr['fields']['establishmentMeans'] = 'o.establishmentMeans';
		$this->occurDefArr['terms']['cultivationStatus'] = 'https://symbiota.org/terms/cultivationStatus';
		$this->occurDefArr['fields']['cultivationStatus'] = 'o.cultivationStatus';
		$this->occurDefArr['terms']['lifeStage'] = 'http://rs.tdwg.org/dwc/terms/lifeStage';
		$this->occurDefArr['fields']['lifeStage'] = 'o.lifeStage';
		$this->occurDefArr['terms']['sex'] = 'http://rs.tdwg.org/dwc/terms/sex';
		$this->occurDefArr['fields']['sex'] = 'o.sex';
		$this->occurDefArr['terms']['individualCount'] = 'http://rs.tdwg.org/dwc/terms/individualCount';
		$this->occurDefArr['fields']['individualCount'] = 'CASE WHEN o.individualCount REGEXP("(^[0-9]+$)") THEN o.individualCount ELSE NULL END AS individualCount';
		//$this->occurDefArr['terms']['samplingProtocol'] = 'http://rs.tdwg.org/dwc/terms/samplingProtocol';
		//$this->occurDefArr['fields']['samplingProtocol'] = 'o.samplingProtocol';
		//$this->occurDefArr['terms']['samplingEffort'] = 'http://rs.tdwg.org/dwc/terms/samplingEffort';
		//$this->occurDefArr['fields']['samplingEffort'] = 'o.samplingEffort';
		$this->occurDefArr['terms']['preparations'] = 'http://rs.tdwg.org/dwc/terms/preparations';
		$this->occurDefArr['fields']['preparations'] = 'o.preparations';
		$this->occurDefArr['terms']['locationID'] = 'http://rs.tdwg.org/dwc/terms/locationID';
		$this->occurDefArr['fields']['locationID'] = 'o.locationID';
		$this->occurDefArr['terms']['continent'] = 'http://rs.tdwg.org/dwc/terms/continent';
		$this->occurDefArr['fields']['continent'] = 'o.continent';
		$this->occurDefArr['terms']['waterBody'] = 'http://rs.tdwg.org/dwc/terms/waterBody';
		$this->occurDefArr['fields']['waterBody'] = 'o.waterBody';
		$this->occurDefArr['terms']['islandGroup'] = 'http://rs.tdwg.org/dwc/terms/islandGroup';
		$this->occurDefArr['fields']['islandGroup'] = 'o.islandGroup';
		$this->occurDefArr['terms']['island'] = 'http://rs.tdwg.org/dwc/terms/island';
		$this->occurDefArr['fields']['island'] = 'o.island';
		$this->occurDefArr['terms']['country'] = 'http://rs.tdwg.org/dwc/terms/country';
		$this->occurDefArr['fields']['country'] = 'o.country';
		$this->occurDefArr['terms']['stateProvince'] = 'http://rs.tdwg.org/dwc/terms/stateProvince';
		$this->occurDefArr['fields']['stateProvince'] = 'o.stateProvince';
		$this->occurDefArr['terms']['county'] = 'http://rs.tdwg.org/dwc/terms/county';
		$this->occurDefArr['fields']['county'] = 'o.county';
		$this->occurDefArr['terms']['municipality'] = 'http://rs.tdwg.org/dwc/terms/municipality';
		$this->occurDefArr['fields']['municipality'] = 'o.municipality';
		$this->occurDefArr['terms']['locality'] = 'http://rs.tdwg.org/dwc/terms/locality';
		$this->occurDefArr['fields']['locality'] = 'o.locality';
		$this->occurDefArr['terms']['locationRemarks'] = 'http://rs.tdwg.org/dwc/terms/locationRemarks';
		$this->occurDefArr['fields']['locationRemarks'] = 'o.locationremarks';
		$this->occurDefArr['terms']['localitySecurity'] = 'https://symbiota.org/terms/localitySecurity';
		$this->occurDefArr['fields']['localitySecurity'] = 'o.localitySecurity';
		$this->occurDefArr['terms']['localitySecurityReason'] = 'https://symbiota.org/terms/localitySecurityReason';
		$this->occurDefArr['fields']['localitySecurityReason'] = 'o.localitySecurityReason';
		$this->occurDefArr['terms']['decimalLatitude'] = 'http://rs.tdwg.org/dwc/terms/decimalLatitude';
		$this->occurDefArr['fields']['decimalLatitude'] = 'o.decimalLatitude';
		$this->occurDefArr['terms']['decimalLongitude'] = 'http://rs.tdwg.org/dwc/terms/decimalLongitude';
		$this->occurDefArr['fields']['decimalLongitude'] = 'o.decimalLongitude';
		$this->occurDefArr['terms']['geodeticDatum'] = 'http://rs.tdwg.org/dwc/terms/geodeticDatum';
		$this->occurDefArr['fields']['geodeticDatum'] = 'o.geodeticDatum';
		$this->occurDefArr['terms']['coordinateUncertaintyInMeters'] = 'http://rs.tdwg.org/dwc/terms/coordinateUncertaintyInMeters';
		$this->occurDefArr['fields']['coordinateUncertaintyInMeters'] = 'o.coordinateUncertaintyInMeters';
		//$this->occurDefArr['terms']['footprintWKT'] = 'http://rs.tdwg.org/dwc/terms/footprintWKT';
		//$this->occurDefArr['fields']['footprintWKT'] = 'o.footprintWKT';
		$this->occurDefArr['terms']['verbatimCoordinates'] = 'http://rs.tdwg.org/dwc/terms/verbatimCoordinates';
		$this->occurDefArr['fields']['verbatimCoordinates'] = 'o.verbatimCoordinates';
		$this->occurDefArr['terms']['georeferencedBy'] = 'http://rs.tdwg.org/dwc/terms/georeferencedBy';
		$this->occurDefArr['fields']['georeferencedBy'] = 'o.georeferencedBy';
		$this->occurDefArr['terms']['georeferenceProtocol'] = 'http://rs.tdwg.org/dwc/terms/georeferenceProtocol';
		$this->occurDefArr['fields']['georeferenceProtocol'] = 'o.georeferenceProtocol';
		$this->occurDefArr['terms']['georeferenceSources'] = 'http://rs.tdwg.org/dwc/terms/georeferenceSources';
		$this->occurDefArr['fields']['georeferenceSources'] = 'o.georeferenceSources';
		$this->occurDefArr['terms']['georeferenceVerificationStatus'] = 'http://rs.tdwg.org/dwc/terms/georeferenceVerificationStatus';
		$this->occurDefArr['fields']['georeferenceVerificationStatus'] = 'o.georeferenceVerificationStatus';
		$this->occurDefArr['terms']['georeferenceRemarks'] = 'http://rs.tdwg.org/dwc/terms/georeferenceRemarks';
		$this->occurDefArr['fields']['georeferenceRemarks'] = 'o.georeferenceRemarks';
		$this->occurDefArr['terms']['minimumElevationInMeters'] = 'http://rs.tdwg.org/dwc/terms/minimumElevationInMeters';
		$this->occurDefArr['fields']['minimumElevationInMeters'] = 'o.minimumElevationInMeters';
		$this->occurDefArr['terms']['maximumElevationInMeters'] = 'http://rs.tdwg.org/dwc/terms/maximumElevationInMeters';
		$this->occurDefArr['fields']['maximumElevationInMeters'] = 'o.maximumElevationInMeters';
		$this->occurDefArr['terms']['minimumDepthInMeters'] = 'http://rs.tdwg.org/dwc/terms/minimumDepthInMeters';
		$this->occurDefArr['fields']['minimumDepthInMeters'] = 'o.minimumDepthInMeters';
		$this->occurDefArr['terms']['maximumDepthInMeters'] = 'http://rs.tdwg.org/dwc/terms/maximumDepthInMeters';
		$this->occurDefArr['fields']['maximumDepthInMeters'] = 'o.maximumDepthInMeters';
		$this->occurDefArr['terms']['verbatimDepth'] = 'http://rs.tdwg.org/dwc/terms/verbatimDepth';
		$this->occurDefArr['fields']['verbatimDepth'] = 'o.verbatimDepth';
		$this->occurDefArr['terms']['verbatimElevation'] = 'http://rs.tdwg.org/dwc/terms/verbatimElevation';
		$this->occurDefArr['fields']['verbatimElevation'] = 'o.verbatimElevation';
		if($this->includePaleo){
			$this->occurDefArr['terms']['eon'] = 'https://symbiota.org/terms/paleo-eon';
			$this->occurDefArr['fields']['eon'] = 'paleo.eon';
			$this->occurDefArr['terms']['era'] = 'https://symbiota.org/terms/paleo-era';
			$this->occurDefArr['fields']['era'] = 'paleo.era';
			$this->occurDefArr['terms']['period'] = 'https://symbiota.org/terms/paleo-period';
			$this->occurDefArr['fields']['period'] = 'paleo.period';
			$this->occurDefArr['terms']['epoch'] = 'https://symbiota.org/terms/paleo-epoch';
			$this->occurDefArr['fields']['epoch'] = 'paleo.epoch';
			$this->occurDefArr['terms']['earlyInterval'] = 'https://symbiota.org/terms/paleo-earlyInterval';
			$this->occurDefArr['fields']['earlyInterval'] = 'paleo.earlyInterval';
			$this->occurDefArr['terms']['lateInterval'] = 'https://symbiota.org/terms/paleo-lateInterval';
			$this->occurDefArr['fields']['lateInterval'] = 'paleo.lateInterval';
			$this->occurDefArr['terms']['absoluteAge'] = 'https://symbiota.org/terms/paleo-absoluteAge';
			$this->occurDefArr['fields']['absoluteAge'] = 'paleo.absoluteAge';
			$this->occurDefArr['terms']['storageAge'] = 'https://symbiota.org/terms/paleo-storageAge';
			$this->occurDefArr['fields']['storageAge'] = 'paleo.storageAge';
			$this->occurDefArr['terms']['stage'] = 'https://symbiota.org/terms/paleo-stage';
			$this->occurDefArr['fields']['stage'] = 'paleo.stage';
			$this->occurDefArr['terms']['localStage'] = 'https://symbiota.org/terms/paleo-localStage';
			$this->occurDefArr['fields']['localStage'] = 'paleo.localStage';
			$this->occurDefArr['terms']['biota'] = 'https://symbiota.org/terms/paleo-biota';
			$this->occurDefArr['fields']['biota'] = 'paleo.biota';
			$this->occurDefArr['terms']['biostratigraphy'] = 'https://symbiota.org/terms/paleo-biostratigraphy';
			$this->occurDefArr['fields']['biostratigraphy'] = 'paleo.biostratigraphy';
			$this->occurDefArr['terms']['taxonEnvironment'] = 'https://symbiota.org/terms/paleo-taxonEnvironment';
			$this->occurDefArr['fields']['taxonEnvironment'] = 'paleo.taxonEnvironment';
			$this->occurDefArr['terms']['lithogroup'] = 'http://rs.tdwg.org/dwc/terms/group';
			$this->occurDefArr['fields']['lithogroup'] = 'paleo.lithogroup';
			$this->occurDefArr['terms']['formation'] = 'http://rs.tdwg.org/dwc/terms/formation';
			$this->occurDefArr['fields']['formation'] = 'paleo.formation';
			$this->occurDefArr['terms']['member'] = 'http://rs.tdwg.org/dwc/terms/member';
			$this->occurDefArr['fields']['member'] = 'paleo.member';
			$this->occurDefArr['terms']['bed'] = 'http://rs.tdwg.org/dwc/terms/bed';
			$this->occurDefArr['fields']['bed'] = 'paleo.bed';
			$this->occurDefArr['terms']['lithology'] = 'http://rs.tdwg.org/dwc/terms/lithostratigraphic';
			$this->occurDefArr['fields']['lithology'] = 'paleo.lithology';
			$this->occurDefArr['terms']['stratRemarks'] = 'https://symbiota.org/terms/paleo-stratRemarks';
			$this->occurDefArr['fields']['stratRemarks'] = 'paleo.stratRemarks';
			$this->occurDefArr['terms']['element'] = 'https://symbiota.org/terms/paleo-element';
			$this->occurDefArr['fields']['element'] = 'paleo.element';
			$this->occurDefArr['terms']['slideProperties'] = 'https://symbiota.org/terms/paleo-slideProperties';
			$this->occurDefArr['fields']['slideProperties'] = 'paleo.slideProperties';
			$this->occurDefArr['terms']['geologicalContextID'] = 'http://rs.tdwg.org/dwc/terms/geologicalContextID';
			$this->occurDefArr['fields']['geologicalContextID'] = 'paleo.geologicalContextID';
		}
		$this->occurDefArr['terms']['disposition'] = 'http://rs.tdwg.org/dwc/terms/disposition';
		$this->occurDefArr['fields']['disposition'] = 'o.disposition';
		$this->occurDefArr['terms']['language'] = 'http://purl.org/dc/terms/language';
		$this->occurDefArr['fields']['language'] = 'o.language';
		//$this->occurDefArr['terms']['genericcolumn1'] = 'https://symbiota.org/terms/genericcolumn1';
		//$this->occurDefArr['fields']['genericcolumn1'] = 'o.genericcolumn1';
		//$this->occurDefArr['terms']['genericcolumn2'] = 'https://symbiota.org/terms/genericcolumn2';
		//$this->occurDefArr['fields']['genericcolumn2'] = 'o.genericcolumn2';
		//$this->occurDefArr['terms']['storageLocation'] = 'https://symbiota.org/terms/storageLocation';
		//$this->occurDefArr['fields']['storageLocation'] = 'o.storageLocation';
		$this->occurDefArr['terms']['observerUid'] = 'https://symbiota.org/terms/observerUid';
		$this->occurDefArr['fields']['observerUid'] = 'o.observeruid';
		$this->occurDefArr['terms']['processingStatus'] = 'https://symbiota.org/terms/processingStatus';
		$this->occurDefArr['fields']['processingStatus'] = 'o.processingstatus';
		$this->occurDefArr['terms']['duplicateQuantity'] = 'https://symbiota.org/terms/duplicateQuantity';
		$this->occurDefArr['fields']['duplicateQuantity'] = 'o.duplicateQuantity';
		$this->occurDefArr['terms']['labelProject'] = 'https://symbiota.org/terms/labelProject';
		$this->occurDefArr['fields']['labelProject'] = 'o.labelProject';
		$this->occurDefArr['terms']['recordEnteredBy'] = 'https://symbiota.org/terms/recordEnteredBy';
		$this->occurDefArr['fields']['recordEnteredBy'] = 'o.recordEnteredBy';
		$this->occurDefArr['terms']['dateEntered'] = 'https://symbiota.org/terms/dateEntered';
		$this->occurDefArr['fields']['dateEntered'] = 'o.dateEntered';
		$this->occurDefArr['terms']['dateLastModified'] = 'http://rs.tdwg.org/dwc/terms/dateLastModified';
		$this->occurDefArr['fields']['dateLastModified'] = 'o.datelastmodified';
		$this->occurDefArr['terms']['modified'] = 'http://purl.org/dc/terms/modified';
		$this->occurDefArr['fields']['modified'] = 'IFNULL(o.modified,o.datelastmodified) AS modified';
		$this->occurDefArr['terms']['rights'] = 'http://purl.org/dc/elements/1.1/rights';
		$this->occurDefArr['fields']['rights'] = 'c.rights';
		$this->occurDefArr['terms']['rightsHolder'] = 'http://purl.org/dc/terms/rightsHolder';
		$this->occurDefArr['fields']['rightsHolder'] = 'c.rightsHolder';
		$this->occurDefArr['terms']['accessRights'] = 'http://purl.org/dc/terms/accessRights';
		$this->occurDefArr['fields']['accessRights'] = 'c.accessRights';
		$this->occurDefArr['terms']['sourcePrimaryKey-dbpk'] = 'https://symbiota.org/terms/sourcePrimaryKey-dbpk';
		$this->occurDefArr['fields']['sourcePrimaryKey-dbpk'] = 'o.dbpk';
		$this->occurDefArr['terms']['collID'] = 'https://symbiota.org/terms/collID';
		$this->occurDefArr['fields']['collID'] = 'c.collID';
		$this->occurDefArr['terms']['recordID'] = 'https://symbiota.org/terms/recordID';
		$this->occurDefArr['fields']['recordID'] = 'o.recordID';
		$this->occurDefArr['terms']['references'] = 'http://purl.org/dc/terms/references';
		$this->occurDefArr['fields']['references'] = '';
		// input of the new quick entry form
		// This array is the source of definition of each input. (there is a website that provides the info >> see above)
		// TODO: can update this part when we have more information
		$this->occurDefArr['terms']['barcode'] = '';
		$this->occurDefArr['fields']['barcode'] = '';
		$this->occurDefArr['terms']['accesNum'] = '';
		$this->occurDefArr['fields']['accesNum'] = '';
		$this->occurDefArr['terms']['filedUnder'] = '';
		$this->occurDefArr['fields']['filedUnder'] = '';
		$this->occurDefArr['terms']['currName'] = '';
		$this->occurDefArr['fields']['currName'] = '';
		$this->occurDefArr['terms']['idQualifier'] = '';
		$this->occurDefArr['fields']['idQualifier'] = '';
		$this->occurDefArr['terms']['detText'] = '';
		$this->occurDefArr['fields']['detText'] = '';
		$this->occurDefArr['terms']['provenance'] = '';
		$this->occurDefArr['fields']['provenance'] = '';
		$this->occurDefArr['terms']['container'] = '';
		$this->occurDefArr['fields']['container'] = '';
		$this->occurDefArr['terms']['collTrip'] = '';
		$this->occurDefArr['fields']['collTrip'] = '';
		$this->occurDefArr['terms']['geoWithin'] = '';
		$this->occurDefArr['fields']['geoWithin'] = '';
		$this->occurDefArr['terms']['highGeo'] = '';
		$this->occurDefArr['fields']['highGeo'] = '';
		$this->occurDefArr['terms']['frequency'] = '';
		$this->occurDefArr['fields']['frequency'] = '';
		$this->occurDefArr['terms']['prepMethod'] = '';
		$this->occurDefArr['fields']['prepMethod'] = '';
		$this->occurDefArr['terms']['format'] = '';
		$this->occurDefArr['fields']['format'] = '';
		$this->occurDefArr['terms']['verbLat'] = '';
		$this->occurDefArr['fields']['verbLat'] = '';
		$this->occurDefArr['terms']['verbLong'] = '';
		$this->occurDefArr['fields']['verbLong'] = '';
		$this->occurDefArr['terms']['method'] = '';
		$this->occurDefArr['fields']['method'] = '';
		$this->occurDefArr['terms']['modified'] = '';
		$this->occurDefArr['fields']['modified'] = '';
		$this->occurDefArr['terms']['dateLastModified'] = '';
		$this->occurDefArr['fields']['dateLastModified'] = '';

		if($this->schemaType == 'pensoft'){
			$this->occurDefArr['fields']['occid'] = 'o.occid';
		}

		foreach($this->occurDefArr as $k => $vArr){
			if($this->schemaType == 'dwc' || $this->schemaType == 'pensoft'){
				$trimArr = array('recordedByID','associatedCollectors','substrate','verbatimAttributes','cultivationStatus',
					'localitySecurityReason','genericcolumn1','genericcolumn2','storageLocation','observerUid','processingStatus',
					'duplicateQuantity','labelProject','dateEntered','dateLastModified','sourcePrimaryKey-dbpk');
				$this->occurDefArr[$k] = array_diff_key($vArr,array_flip($trimArr));
			}
			elseif($this->schemaType == 'symbiota'){
				$trimArr = array();
				if(!$this->extended){
					$trimArr = array('collectionID','rights','rightsHolder','accessRights','storageLocation','observerUid','processingStatus','duplicateQuantity','labelProject','dateEntered','dateLastModified',
									// input of the new quick entry form
									// TODO: guessing the type, need to double check if it matters
									'barcode', 'accesNum');
				}
				$this->occurDefArr[$k] = array_diff_key($vArr,array_flip($trimArr));
			}
			elseif($this->schemaType == 'backup'){
				$trimArr = array('collectionID','rights','rightsHolder','accessRights');
				$this->occurDefArr[$k] = array_diff_key($vArr,array_flip($trimArr));
			}
			elseif($this->schemaType == 'coge'){
				$targetArr = array('id','basisOfRecord','institutionCode','collectionCode','catalogNumber','occurrenceID','family','scientificName','scientificNameAuthorship',
					'kingdom','phylum','class','order','genus','specificEpithet','infraSpecificEpithet','recordedBy','recordNumber','eventDate','year','month','day','fieldNumber',
					'locationID','continent','waterBody','islandGroup','island','country','stateProvince','county','municipality',
					'locality','localitySecurity','geodeticDatum','decimalLatitude','decimalLongitude','verbatimCoordinates',
					'minimumElevationInMeters','maximumElevationInMeters','verbatimElevation','maximumDepthInMeters','minimumDepthInMeters','establishmentMeans',
					'occurrenceRemarks','dateEntered','dateLastModified','recordID','references','collID',
					// input of the new quick entry form
					// TODO: guessing the type, need to double check if it matters
					'barcode', 'accesNum', 'filedUnder', 'currName', 'idQualifier','detText', 'provenance', 'container', 'collTrip', 'geoWithin', 'highGeo', 
					'frequencey', 'prepMethod', 'format', 'verbLat', 'verbLong', 'method');
				$this->occurDefArr[$k] = array_intersect_key($vArr,array_flip($targetArr));
			}
		}

		if($this->schemaType == 'dwc' || $this->schemaType == 'pensoft'){
			$this->occurDefArr['fields']['recordedBy'] = 'CONCAT_WS("; ",o.recordedBy,o.associatedCollectors) AS recordedBy';
			$this->occurDefArr['fields']['occurrenceRemarks'] = 'CONCAT_WS("; ",o.occurrenceRemarks,o.verbatimAttributes) AS occurrenceRemarks';
			$this->occurDefArr['fields']['habitat'] = 'CONCAT_WS("; ",o.habitat, o.substrate) AS habitat';
		}
		return $this->occurDefArr;
	}

	public function getSqlOccurrences($fieldArr, $fullSql = true){
		$sql = '';
		if($fullSql){
			$sqlFrag = '';
			foreach($fieldArr as $fieldName => $colName){
				if($colName){
					$sqlFrag .= ', '.$colName;
				}
				else{
					$sqlFrag .= ', "" AS t_'.$fieldName;
				}
			}
			$sqlFrag .= ', t.rankid';
			$sql = 'SELECT DISTINCT '.trim($sqlFrag,', ');
		}
		$sql .= ' FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid '.
			'LEFT JOIN taxa t ON o.tidinterpreted = t.TID ';
		if($this->includePaleo) $sql .= 'LEFT JOIN omoccurpaleo paleo ON o.occid = paleo.occid ';
		//if($fullSql) $sql .= ' ORDER BY c.collid ';
		//echo '<div>'.$sql.'</div>'; exit;
		return $sql;
	}

	//Special functions for appending additional data
	public function getAdditionalCatalogNumberStr($occid){
		$retStr = '';
		if(is_numeric($occid)){
			$sql = 'SELECT identifierName, identifierValue FROM omoccuridentifiers WHERE occid = '.$occid.' ORDER BY sortBy';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->identifierName) $retStr .= $r->identifierName.': ';
				$retStr .= $r->identifierValue.'; ';
			}
			$rs->free();
		}
		return trim($retStr,'; ');
	}

	public function setIncludeExsiccatae(){
		$sql = 'SELECT occid FROM omexsiccatiocclink LIMIT 1';
		$rs = $this->conn->query($sql);
		if($rs->num_rows) $this->includeExsiccatae = true;
		$rs->free();
	}

	public function getExsiccateArr($occid){
		$retArr = array();
		if($this->includeExsiccatae && is_numeric($occid)){
			$sql = 'SELECT t.title, t.abbreviation, t.editor, t.exsrange, n.exsnumber, l.notes '.
				'FROM omexsiccatiocclink l INNER JOIN omexsiccatinumbers n ON l.omenid = n.omenid '.
				'INNER JOIN omexsiccatititles t ON n.ometid = t.ometid '.
				'WHERE l.occid = '.$occid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$exsStr = $r->title;
				if($r->abbreviation) $exsStr .= ' ['.$r->abbreviation.']';
				if($r->exsrange) $exsStr .= ', '.$r->exsrange;
				if($r->editor) $exsStr .= ', '.$r->editor;
				$exsStr .= ', exs #: '.$r->exsnumber;
				if($r->notes) $exsStr .= ' ('.$r->notes.')';
				$retArr['exsStr'] = $exsStr;
				$dynProp = array();
				$dynProp['exsTitle'] = $r->title;
				if($r->abbreviation) $dynProp['exsAbbreviation'] = $r->abbreviation;
				if($r->exsrange) $dynProp['exsRange'] = $r->exsrange;
				if($r->editor) $dynProp['exsEditor'] = $r->editor;
				$dynProp['exsNumber'] = $r->exsnumber;
				if($r->notes) $dynProp['exsNotes'] = $r->notes;
				$retArr['exsJson'] = json_encode($dynProp);
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getAssociationStr($occid){
		if(is_numeric($occid)){
			$relOccidArr = array();
			$assocArr = array();
			//Get associations defined within omoccurassociations
			$sql = 'SELECT assocID, occid, occidAssociate, relationship, subType, resourceUrl, identifier FROM omoccurassociations
				WHERE (occid = '.$occid.' OR occidAssociate = '.$occid.') AND verbatimSciname IS NULL ';
			$rs = $this->conn->query($sql);
			if($rs){
				while($r = $rs->fetch_object()){
					$relOccid = $r->occidAssociate;
					$relationship = $r->relationship;
					if($occid == $r->occidAssociate){
						$relOccid = $r->occid;
						$relationship = $this->getInverseRelationship($relationship);
					}
					if($relOccid){
						$assocArr[$r->assocID]['occidassoc'] = $relOccid;
						$relOccidArr[$relOccid][] = $r->assocID;
						$assocArr[$r->assocID]['relationship'] = $relationship;
						$assocArr[$r->assocID]['subtype'] = $r->subType;
					}
					elseif($r->resourceUrl){
						$assocArr[$r->assocID]['resourceurl'] = $r->resourceUrl;
						$assocArr[$r->assocID]['identifier'] = $r->identifier;
						$assocArr[$r->assocID]['relationship'] = $relationship;
						$assocArr[$r->assocID]['subtype'] = $r->subType;
					}
				}
				$rs->free();
			}
			//Append duplicate specimen duplicate associations
			$sql = 'SELECT s.occid, l.occid as occidAssociate
				FROM omoccurduplicatelink s INNER JOIN omoccurduplicates d ON s.duplicateid = d.duplicateid
				INNER JOIN omoccurduplicatelink l ON d.duplicateid = l.duplicateid
				WHERE s.occid IN('.$occid.') AND s.occid != l.occid ';
			$rs = $this->conn->query($sql);
			if($rs){
				while($r = $rs->fetch_object()){
					$assocKey = 'sd-'.$r->occidAssociate;
					$assocArr[$assocKey]['occidassoc'] = $r->occidAssociate;
					$assocArr[$assocKey]['relationship'] = 'herbariumSpecimenDuplicate';
					$relOccidArr[$r->occidAssociate][] = $assocKey;
				}
				$rs->free();
			}
			//Append resource URLs to each output record
			if($relOccidArr){
				$this->setServerDomain();
				//Replace GUID identifiers with occurrenceID values
				$sql = 'SELECT occid, occurrenceID, recordID FROM omoccurrences WHERE occid IN('.implode(',',array_keys($relOccidArr)).')';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					foreach($relOccidArr[$r->occid] as $k => $targetAssocID){
						if($r->occurrenceID){
							$assocArr[$targetAssocID]['resourceurl'] = $r->occurrenceID;
							if(substr($r->occurrenceID, 0, 4) != 'http'){
								$assocArr[$targetAssocID]['resourceurl'] = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].'/collections/individual/index.php?guid='.$r->occurrenceID;
							}
						}
						elseif($r->recordID){
							$assocArr[$targetAssocID]['resourceurl'] = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].'/collections/individual/index.php?guid='.$r->recordID;
						}
					}
				}
				$rs->free();
			}
			//Create output strings
			$retStr = '';
			foreach($assocArr as $assocateArr){
				$retStr .= ' | '.$assocateArr['relationship'];
				if(!empty($assocateArr['subtype'])) $retStr .= ' ('.$assocateArr['subtype'].')';
				$retStr .= ': '.$assocateArr['resourceurl'];
			}
		}
		return trim($retStr,' |');
	}

	public function setIncludeAssociatedSequences(){
		$sql = 'SELECT occid FROM omoccurgenetic LIMIT 1';
		$rs = $this->conn->query($sql);
		if($rs->num_rows) $this->includeAssocSeq = true;
		$rs->free();
	}

	public function getAssociatedSequencesStr($occid){
		$retStr = '';
		if(is_numeric($occid)){
			$sql = 'SELECT identifier, resourceName, title, locus, resourceUrl FROM omoccurgenetic WHERE occid = '.$occid;
			$rs = $this->conn->query($sql);
			if($rs){
				while($r = $rs->fetch_object()){
					$retStr .= '|'.$r->resourceName.', ';
					if($r->title) $retStr .= $r->title.', ';
					if($r->identifier) $retStr .= $r->identifier.', ';
					if($r->locus) $retStr .= $r->locus.', ';
					$retStr .= $r->resourceUrl;
				}
				$rs->free();
			}
		}
		return trim($retStr,' |,');
	}

	public function getAssocTaxa($occid){
		$retStr = '';
		if(is_numeric($occid)){
			$sql = 'SELECT assocID, relationship, subType, verbatimSciname FROM omoccurassociations WHERE occid = '.$occid.' AND verbatimSciname IS NOT NULL ';
			$rs = $this->conn->query($sql);
			if($rs){
				while($r = $rs->fetch_object()){
					$retStr .= '|'.$r->relationship.($r->subType?' ('.$r->subType.')':'').': '.$r->verbatimSciname;
				}
				$rs->free();
			}
		}
		return trim($retStr,' |');
	}

	private function getInverseRelationship($relationship){
		if(!$this->relationshipArr) $this->setRelationshipArr();
		if(array_key_exists($relationship, $this->relationshipArr)) return $this->relationshipArr[$relationship];
		return $relationship;
	}

	private function setRelationshipArr(){
		if(!$this->relationshipArr){
			$sql = 'SELECT t.term, t.inverseRelationship FROM ctcontrolvocabterm t INNER JOIN ctcontrolvocab v  ON t.cvid = v.cvid WHERE v.tableName = "omoccurassociations" AND v.fieldName = "relationship"';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$this->relationshipArr[$r->term] = $r->inverseRelationship;
					$this->relationshipArr[$r->inverseRelationship] = $r->term;
				}
				$rs->free();
			}
		}
	}

	public function appendUpperTaxonomy(&$targetArr){
		if($targetArr['family'] && $this->upperTaxonomy){
			$higherStr = '';
			$famStr = strtolower($targetArr['family']);
			if(isset($this->upperTaxonomy[$famStr]['k'])){
				$targetArr['t_kingdom'] = $this->upperTaxonomy[$famStr]['k'];
				$higherStr = $targetArr['t_kingdom'];
			}
			if(isset($this->upperTaxonomy[$famStr]['p'])){
				$targetArr['t_phylum'] = $this->upperTaxonomy[$famStr]['p'];
				$higherStr .= '|'.$targetArr['t_phylum'];
			}
			if(isset($this->upperTaxonomy[$famStr]['c'])){
				$targetArr['t_class'] = $this->upperTaxonomy[$famStr]['c'];
				$higherStr .= '|'.trim($targetArr['t_class'],'|');
			}
			if(isset($this->upperTaxonomy[$famStr]['o'])){
				$targetArr['t_order'] = $this->upperTaxonomy[$famStr]['o'];
				$higherStr .= '|'.trim($targetArr['t_class'],'|');
			}
			$targetArr['t_higherClassification'] = trim($higherStr,'| ');
		}
	}

	public function appendUpperTaxonomy2(&$r){
		$target = (isset($r['taxonID'])?$r['taxonID']:false);
		if(!$target) $target = ucfirst($r['family']);
		if($target){
			if(array_key_exists($target, $this->upperTaxonomy)){
				if(isset($this->upperTaxonomy[$target]['k'])) $r['t_kingdom'] = $this->upperTaxonomy[$target]['k'];
				if(isset($this->upperTaxonomy[$target]['p'])) $r['t_phylum'] = $this->upperTaxonomy[$target]['p'];
				if(isset($this->upperTaxonomy[$target]['c'])) $r['t_class'] = $this->upperTaxonomy[$target]['c'];
				if(isset($this->upperTaxonomy[$target]['o'])) $r['t_order'] = $this->upperTaxonomy[$target]['o'];
				if(isset($this->upperTaxonomy[$target]['f']) && !$r['family']) $r['family'] = $this->upperTaxonomy[$target]['f'];
				if(isset($this->upperTaxonomy[$target]['s'])) $r['t_subgenus'] = $this->upperTaxonomy[$target]['s'];
				if(isset($this->upperTaxonomy[$target]['u'])) $r['t_higherClassification'] = $this->upperTaxonomy[$target]['u'];
			}
			else{
				$higherStr = '';
				$sql = 'SELECT t.tid, t.sciname, t.rankid FROM taxaenumtree e INNER JOIN taxa t ON e.parentTid = t.tid ';
				if(!is_numeric($target)) $sql .= 'INNER JOIN taxa t2 ON e.tid = t2.tid WHERE e.taxauthid = 1 AND t2.sciname = "'.$this->cleanInStr($target).'" ORDER BY t.rankid';
				else $sql .= 'WHERE e.taxauthid = 1 AND e.tid = '.$target.' ORDER BY t.rankid';
				$rs = $this->conn->query($sql);
				while($row = $rs->fetch_object()){
					if($row->rankid == 10) $r['t_kingdom'] = $row->sciname;
					elseif($row->rankid == 30) $r['t_phylum'] = $row->sciname;
					elseif($row->rankid == 60) $r['t_class'] = $row->sciname;
					elseif($row->rankid == 100) $r['t_order'] = $row->sciname;
					elseif($row->rankid == 140 && !$r['family']) $r['family'] = $row->sciname;
					elseif($row->rankid == 190) $r['t_subgenus'] = $row->sciname;
					$higherStr .= '|'.$row->sciname;
				}
				$rs->free();
				if($higherStr && $this->schemaType != 'coge') $r['t_higherClassification'] = trim($higherStr,'| ');
				if(count($this->upperTaxonomy)<1000 || !is_numeric($target)){
					if(isset($r['t_kingdom'])) $this->upperTaxonomy[$target]['k'] = $r['t_kingdom'];
					if(isset($r['t_phylum'])) $this->upperTaxonomy[$target]['p'] = $r['t_phylum'];
					if(isset($r['t_class'])) $this->upperTaxonomy[$target]['c'] = $r['t_class'];
					if(isset($r['t_order'])) $this->upperTaxonomy[$target]['o'] = $r['t_order'];
					if(isset($r['family'])) $this->upperTaxonomy[$target]['f'] = $r['family'];
					if(isset($r['t_subgenus'])) $this->upperTaxonomy[$target]['s'] = $r['t_subgenus'];
					if(isset($r['t_higherClassification'])) $this->upperTaxonomy[$target]['u'] = $r['t_higherClassification'];
				}
			}
		}
	}

	public function setUpperTaxonomy(){
		if(!$this->upperTaxonomy){
			$sqlOrder = 'SELECT t.sciname AS family, t2.sciname AS taxonorder '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
				'WHERE t.rankid = 140 AND t2.rankid = 100';
			$rsOrder = $this->conn->query($sqlOrder);
			while($rowOrder = $rsOrder->fetch_object()){
				$this->upperTaxonomy[strtolower($rowOrder->family)]['o'] = $rowOrder->taxonorder;
			}
			$rsOrder->free();

			$sqlClass = 'SELECT t.sciname AS family, t2.sciname AS taxonclass '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
				'WHERE t.rankid = 140 AND t2.rankid = 60';
			$rsClass = $this->conn->query($sqlClass);
			while($rowClass = $rsClass->fetch_object()){
				$this->upperTaxonomy[strtolower($rowClass->family)]['c'] = $rowClass->taxonclass;
			}
			$rsClass->free();

			$sqlPhylum = 'SELECT t.sciname AS family, t2.sciname AS taxonphylum '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
				'WHERE t.rankid = 140 AND t2.rankid = 30';
			$rsPhylum = $this->conn->query($sqlPhylum);
			while($rowPhylum = $rsPhylum->fetch_object()){
				$this->upperTaxonomy[strtolower($rowPhylum->family)]['p'] = $rowPhylum->taxonphylum;
			}
			$rsPhylum->free();

			$sqlKing = 'SELECT t.sciname AS family, t2.sciname AS kingdom '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
				'WHERE t.rankid = 140 AND t2.rankid = 10';
			$rsKing = $this->conn->query($sqlKing);
			while($rowKing = $rsKing->fetch_object()){
				$this->upperTaxonomy[strtolower($rowKing->family)]['k'] = $rowKing->kingdom;
			}
			$rsKing->free();
		}
	}

	public function setTaxonRank(){
		$sql = 'SELECT DISTINCT rankid, rankname FROM taxonunits';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->taxonRankArr[$r->rankid] = $r->rankname;
		}
		$rs->free();
	}

	public function getTaxonRank($rankID){
		if(array_key_exists($rankID, $this->taxonRankArr)) return $this->taxonRankArr[$rankID];
		else return '';
	}

	public function setServerDomain(){
		if(!$this->serverDomain) $this->serverDomain = $this->getDomain();
	}

	//Setter and getter
	public function setSchemaType($t){
		$this->schemaType = $t;
	}

	public function setExtended($e){
		if($e) $this->extended = true;
	}

	public function setIncludePaleo($bool){
		if($bool) $this->includePaleo = true;
	}
}
?>