<?php
include_once($SERVER_ROOT . '/classes/DwcArchiverBaseManager.php');

class DwcArchiverResourceRelationship extends DwcArchiverBaseManager{
    public function __construct($connOverride){
		parent::__construct('write', $connOverride);
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function initiateProcess($filePath){
		$this->setFieldArr();
		$this->setSqlBase();


		$this->setFileHandler($filePath);
	}

    //Based on https://rs.gbif.org/extension/resource_relationship_2024-02-19.xml
	private function setFieldArr(){
		$columnArr = array();
		// $termArr['deleteMeOccid'] = 'https://dwc.tdwg.org/terms/#dwc:resourceRelationshipID';
		// $columnArr['deleteMeOccid'] = 'oa.occid';
		// $termArr['deleteMeOccidAssociate'] = 'https://dwc.tdwg.org/terms/#dwc:resourceRelationshipID';
		// $columnArr['deleteMeOccidAssociate'] = 'oa.occidAssociate';
		$termArr['resourceRelationshipID'] = 'https://dwc.tdwg.org/terms/#dwc:resourceRelationshipID';
		// $columnArr['resourceRelationshipID'] = 'IFNULL(IFNULL(IFNULL(oa.objectID, o.occurrenceID), o.recordID), oa.resourceUrl)';
		$columnArr['resourceRelationshipID'] = 'IFNULL(oa.instanceID, oa.recordID)';
		$termArr['resourceID'] = 'https://dwc.tdwg.org/terms/#dwc:resourceID';
		// $columnArr['resourceID'] = 'oa.occid'; // @TODO occurrence o o.occoccurrenceID
		$columnArr['resourceID'] = 'IFNULL(o.occurrenceID, o.recordID)'; // @TODO occurrence o o.occoccurrenceID
        $termArr['relationshipOfResourceID'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipOfResourceID';
		$columnArr['relationshipOfResourceID'] = 'oa.relationshipID';
		$termArr['relatedResourceID'] = 'https://dwc.tdwg.org/terms/#dwc:relatedResourceID';
		// $columnArr['relatedResourceID'] = 'IFNULL(oo.instanceID,oo.resourceUrl)'; // @TODO maybe add logic IF associationType='externalOccurrence' ? resourceUrl : IFNULL(instanceID,resourceUrl)
		// $columnArr['relatedResourceID'] = 'IFNULL(oo.occurrenceID, oo.recordID)';
		$columnArr['relatedResourceID'] = 'IFNULL(IFNULL(IFNULL(oa.objectID, oo.occurrenceID), oo.recordID), oa.resourceUrl)';
        $termArr['relationshipOfResource'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipOfResource';
		$columnArr['relationshipOfResource'] = 'oa.relationship';
		$termArr['relationshipAccordingTo'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipAccordingTo';
		$columnArr['relationshipAccordingTo'] = 'oa.accordingTo';
 		$termArr['relationshipEstablishedDate'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipEstablishedDate';
 		$columnArr['relationshipEstablishedDate'] = 'oa.establishedDate';
		$termArr['relationshipRemarks'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipRemarks';
		$columnArr['relationshipRemarks'] = 'oa.notes';
		$termArr['scientificName'] = 'https://symbiota.org/terms/scientificName';
		$columnArr['scientificName'] = 'o.sciname';

		$termArr['associd'] = 'https://symbiota.org/terms/associd';
		$columnArr['associd'] = 'oa.associd';
		$termArr['associationType'] = 'https://symbiota.org/terms/associationType';
		$columnArr['associationType'] = 'oa.associationType';
		$termArr['subType'] = 'https://symbiota.org/terms/subType';
		$columnArr['subType'] = 'oa.subType';
		$termArr['objectID'] = 'https://symbiota.org/terms/objectID';
		$columnArr['objectID'] = 'oa.objectID';
		$termArr['identifier'] = 'https://symbiota.org/terms/identifier';
		$columnArr['identifier'] = 'oa.identifier';
		$termArr['basisOfRecord'] = 'https://symbiota.org/terms/basisOfRecord';
		$columnArr['basisOfRecord'] = 'oa.basisOfRecord';
		// $termArr['verbatimSciname'] = 'https://symbiota.org/terms/verbatimSciname';
		// $columnArr['verbatimSciname'] = 'oa.verbatimSciname';
		$termArr['tid'] = 'https://symbiota.org/terms/tid';
		$columnArr['tid'] = 'oa.tid';
		$termArr['locationOnHost'] = 'https://symbiota.org/terms/locationOnHost';
		$columnArr['locationOnHost'] = 'oa.locationOnHost';
		$termArr['conditionOfAssociate'] = 'https://symbiota.org/terms/conditionOfAssociate';
		$columnArr['conditionOfAssociate'] = 'oa.conditionOfAssociate';
		$termArr['imageMapJSON'] = 'https://symbiota.org/terms/imageMapJSON';
		$columnArr['imageMapJSON'] = 'oa.imageMapJSON';
		$termArr['dynamicProperties'] = 'https://symbiota.org/terms/dynamicProperties';
		$columnArr['dynamicProperties'] = 'oa.dynamicProperties';
		$termArr['sourceIdentifier'] = 'https://symbiota.org/terms/sourceIdentifier';
		$columnArr['sourceIdentifier'] = 'oa.sourceIdentifier';
		$termArr['recordID'] = 'https://symbiota.org/terms/recordID';
		$columnArr['recordID'] = 'oa.recordID';
		$termArr['createdUid'] = 'https://symbiota.org/terms/createdUid';
		$columnArr['createdUid'] = 'oa.createdUid';
		$termArr['modifiedTimestamp'] = 'https://symbiota.org/terms/modifiedTimestamp';
		$columnArr['modifiedTimestamp'] = 'oa.modifiedTimestamp';
		$termArr['modifiedUid'] = 'https://symbiota.org/terms/modifiedUid';
		$columnArr['modifiedUid'] = 'oa.modifiedUid';
		$termArr['initialtimestamp'] = 'https://symbiota.org/terms/initialtimestamp';
		$columnArr['initialtimestamp'] = 'oa.initialtimestamp';


		$this->fieldArr['terms'] = $this->trimBySchemaType($termArr);
		$this->fieldArr['fields'] = $this->trimBySchemaType($columnArr);
	}

    private function trimBySchemaType($dataArr){
		$trimArr = array();
		if($this->schemaType == 'backup'){
			//$trimArr = array();
		}
		elseif($this->schemaType == 'dwc'){
			$trimArr = array('associd', 'associationType', 'subType', 'objectID', 'identifier',
			 'basisOfRecord', 'verbatimSciname', 'tid', 'locationOnHost', 'conditionOfAssociate',
			  'imageMapJSON', 'dynamicProperties', 'sourceIdentifier', 'recordID', 'createdUid',
			   'modifiedTimestamp', 'modifiedUid', 'initialtimestamp');
		}
		return array_diff_key($dataArr, array_flip($trimArr));
	}

	// @TODO decide if setDynamicFields is needed

	public function setSqlBase($getInverse = false){
		if($this->fieldArr){
			$sqlFrag = '';
			if(!$getInverse){
				foreach($this->fieldArr['fields'] as $colName){
					if($colName) $sqlFrag .= ', ' . $colName;
				}
				// $this->sqlBase = 'SELECT ' . trim($sqlFrag, ', ') . ' FROM omoccurassociations ';
				$this->sqlBase = 'SELECT DISTINCT ' . trim($sqlFrag, ', ') . ' FROM omoccurrences o INNER JOIN omoccurassociations oa ON o.occid = oa.occid LEFT JOIN omoccurrences oo ON oo.occid = oa.occidAssociate';
			}
			else{
				$this->fieldArr['fields']['relationship'] = 'terms.inverseRelationship AS relationship';
				foreach($this->fieldArr['fields'] as $colName){
					if($colName) $sqlFrag .= ', ' . $colName;
				}
				// $this->sqlBase = 'SELECT ' . trim($sqlFrag, ', ') . ' FROM omoccurassociations ';
				$this->sqlBase = 'SELECT DISTINCT ' . trim($sqlFrag, ', ') . ' FROM omoccurrences o INNER JOIN omoccurassociations oa ON o.occid = oa.occidAssociate LEFT JOIN omoccurrences oo ON oo.occid = oa.occid
					LEFT JOIN (SELECT t.term, t.inverseRelationship
					FROM ctcontrolvocabterm t INNER JOIN ctcontrolvocab v ON t.cvID = v.cvID
					WHERE v.tablename = "omoccurassociations" AND fieldName = "relationship" AND t.inverseRelationship IS NOT NULL) terms ON oa.relationship = terms.term ';
			}
		}
	}

}

?>