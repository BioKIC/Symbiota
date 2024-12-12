<?php
include_once($SERVER_ROOT . '/classes/DwcArchiverBaseManager.php');

class DwcArchiverAssociation extends DwcArchiverBaseManager{
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
		$termArr['resourceRelationshipID'] = 'https://dwc.tdwg.org/terms/#dwc:resourceRelationshipID';
		$columnArr['resourceRelationshipID'] = 'IFNULL(oa.instanceID,oa.recordId)';
		$termArr['resourceID'] = 'https://dwc.tdwg.org/terms/#dwc:resourceID';
		// $columnArr['resourceID'] = 'oa.occid'; // @TODO occurrence o o.occoccurrenceID
		$columnArr['resourceID'] = 'o.occurrenceID'; // @TODO occurrence o o.occoccurrenceID
        $termArr['relationshipOfResourceID'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipOfResourceID';
		$columnArr['relationshipOfResourceID'] = 'oa.relationshipID';
		$termArr['relatedResourceID'] = 'https://dwc.tdwg.org/terms/#dwc:relatedResourceID';
		$columnArr['relatedResourceID'] = 'IFNULL(oa.instanceID,oa.resourceUrl)'; // @TODO maybe add logic IF associationType='externalOccurrence' ? resourceUrl : IFNULL(instanceID,resourceUrl)
        $termArr['relationshipOfResource'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipOfResource';
		$columnArr['relationshipOfResource'] = 'oa.relationship';
		$termArr['relationshipAccordingTo'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipAccordingTo';
		$columnArr['relationshipAccordingTo'] = 'oa.accordingTo';
 		$termArr['relationshipEstablishedDate'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipEstablishedDate';
 		$columnArr['relationshipEstablishedDate'] = 'oa.establishedDate';
		$termArr['relationshipRemarks'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipRemarks';
		$columnArr['relationshipRemarks'] = 'oa.notes';
		$termArr['scientificName'] = 'https://symbiota.org/terms/scientificName';
		$columnArr['scientificName'] = 'oa.verbatimSciname';

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
		$termArr['verbatimSciname'] = 'https://symbiota.org/terms/verbatimSciname';
		$columnArr['verbatimSciname'] = 'oa.verbatimSciname';
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
			$trimArr = array('oa.associd', 'oa.associationType', 'oa.subType', 'oa.objectID', 'oa.identifier',
			 'oa.basisOfRecord', 'oa.verbatimSciname', 'oa.tid', 'oa.locationOnHost', 'oa.conditionOfAssociate',
			  'oa.imageMapJSON', 'oa.dynamicProperties', 'oa.sourceIdentifier', 'oa.recordID', 'oa.createdUid',
			   'oa.modifiedTimestamp', 'oa.modifiedUid', 'oa.initialtimestamp');
		}
		return array_diff_key($dataArr, array_flip($trimArr));
	}

	// @TODO decide if setDynamicFields is needed

	private function setSqlBase(){
		if($this->fieldArr){
			$sqlFrag = '';
			foreach($this->fieldArr['fields'] as $colName){
				if($colName) $sqlFrag .= ', ' . $colName;
			}
			// $this->sqlBase = 'SELECT ' . trim($sqlFrag, ', ') . ' FROM omoccurassociations ';
			$this->sqlBase = 'SELECT ' . trim($sqlFrag, ', ') . ' FROM omoccurrences o INNER JOIN omoccurassociations oa ON o.occid = oa.occidAssociate ';
		}
	}

}

?>