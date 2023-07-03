<?php
class DwcArchiverDetermination{

	public static function getDeterminationArr($schemaType,$extended){
		$fieldArr = array();
		$fieldArr['coreid'] = 'o.occid';
		$termArr['identifiedBy'] = 'http://rs.tdwg.org/dwc/terms/identifiedBy';
		$fieldArr['identifiedBy'] = 'd.identifiedBy';
		//$termArr['identifiedByID'] = 'https://symbiota.org/terms/identifiedByID';
		//$fieldArr['identifiedByID'] = 'd.idbyid';
		$termArr['dateIdentified'] = 'http://rs.tdwg.org/dwc/terms/dateIdentified';
		$fieldArr['dateIdentified'] = 'd.dateIdentified';
		$termArr['identificationQualifier'] = 'http://rs.tdwg.org/dwc/terms/identificationQualifier';
		$fieldArr['identificationQualifier'] = 'd.identificationQualifier';
		$termArr['scientificName'] = 'http://rs.tdwg.org/dwc/terms/scientificName';
		$fieldArr['scientificName'] = 'd.sciName AS scientificName';
		$termArr['tidInterpreted'] = 'https://symbiota.org/terms/tidInterpreted';
		$fieldArr['tidInterpreted'] = 'd.tidinterpreted';
		$termArr['identificationIsCurrent'] = 'https://symbiota.org/terms/identificationIsCurrent';
		$fieldArr['identificationIsCurrent'] = 'd.iscurrent';
		$termArr['scientificNameAuthorship'] = 'http://rs.tdwg.org/dwc/terms/scientificNameAuthorship';
		$fieldArr['scientificNameAuthorship'] = 'd.scientificNameAuthorship';
		$termArr['genus'] = 'http://rs.tdwg.org/dwc/terms/genus';
		$fieldArr['genus'] = 'CONCAT_WS(" ",t.unitind1,t.unitname1) AS genus';
		$termArr['specificEpithet'] = 'http://rs.tdwg.org/dwc/terms/specificEpithet';
		$fieldArr['specificEpithet'] = 'CONCAT_WS(" ",t.unitind2,t.unitname2) AS specificEpithet';
		$termArr['taxonRank'] = 'http://rs.tdwg.org/dwc/terms/taxonRank';
		$fieldArr['taxonRank'] = 't.unitind3 AS taxonRank';
		$termArr['infraspecificEpithet'] = 'http://rs.tdwg.org/dwc/terms/infraspecificEpithet';
		$fieldArr['infraspecificEpithet'] = 't.unitname3 AS infraspecificEpithet';
		$termArr['identificationReferences'] = 'http://rs.tdwg.org/dwc/terms/identificationReferences';
		$fieldArr['identificationReferences'] = 'd.identificationReferences';
		$termArr['identificationRemarks'] = 'http://rs.tdwg.org/dwc/terms/identificationRemarks';
		$fieldArr['identificationRemarks'] = 'd.identificationRemarks';
		$termArr['recordID'] = 'http://portal.idigbio.org/terms/recordID';
		$fieldArr['recordID'] = 'd.recordID AS recordID';
		$termArr['modified'] = 'http://purl.org/dc/terms/modified';
		$fieldArr['modified'] = 'd.initialTimeStamp AS modified';
		$termArr['detID'] = 'https://symbiota.org/terms/detID';
		$fieldArr['detID'] = 'd.detID';

		$retArr['terms'] = self::trimBySchemaType($termArr,$schemaType,$extended);
		$retArr['fields'] = self::trimBySchemaType($fieldArr,$schemaType,$extended);
		return $retArr;
	}

	private static function trimBySchemaType($detArr,$schemaType,$extended){
		$trimArr = array();
		if($schemaType == 'dwc'){
			$trimArr = array('identifiedByID');
			$trimArr = array('tidInterpreted');
			$trimArr = array('identificationIsCurrent');
		}
		elseif($schemaType == 'symbiota'){
			if(!$extended){
				$trimArr = array('identifiedByID');
				$trimArr = array('tidInterpreted');
			}
		}
		elseif($schemaType == 'backup'){
			$trimArr = array();
		}
		elseif($schemaType == 'coge'){
			$trimArr = array();
		}
		return array_diff_key($detArr,array_flip($trimArr));
	}

	public static function getSql($fieldArr, $tableJoins, $conditionSql){
		$sql = '';
		if($fieldArr && $conditionSql){
			$sql = 'SELECT ';
			$delimiter = '';
			foreach($fieldArr as $fieldSql){
				if($fieldSql) $sql .= $delimiter.$fieldSql;
				$delimiter = ', ';
			}
			$sql .= ' FROM omoccurdeterminations d INNER JOIN omoccurrences o ON d.occid = o.occid LEFT JOIN taxa t ON d.tidinterpreted = t.tid ';
			$sql .= $tableJoins;
			$sql .= $conditionSql.' AND d.appliedstatus = 1 ';
			$sql .= 'ORDER BY o.collid';
			//echo '<div>'.$sql.'</div>'; exit;
		}
		return $sql;
	}
}
?>