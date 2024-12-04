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
		// $columnArr['coreid'] = 'occid'; // @TODO ?
		$termArr['resourceRelationshipID'] = 'https://dwc.tdwg.org/terms/#dwc:resourceRelationshipID';
		$columnArr['resourceRelationshipID'] = 'instanceID';
		$termArr['resourceID'] = 'https://dwc.tdwg.org/terms/#dwc:resourceID';
		$columnArr['resourceID'] = 'occid';
        $termArr['relationshipOfResourceID'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipOfResourceID';
		$columnArr['relationshipOfResourceID'] = 'relationshipID';
		$termArr['relatedResourceID'] = 'https://dwc.tdwg.org/terms/#dwc:relatedResourceID';
		$columnArr['relatedResourceID'] = 'occidAssociate'; // @TODO make this also work resourceUrl or objectId.. what is the objectID alias about?
        $termArr['relationshipOfResource'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipOfResource';
		$columnArr['relationshipOfResource'] = 'relationship';
		$termArr['relationshipAccordingTo'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipAccordingTo';
		$columnArr['relationshipAccordingTo'] = 'accordingTo';
 		$termArr['relationshipEstablishedDate'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipEstablishedDate';
 		$columnArr['relationshipEstablishedDate'] = 'establishedDate';
		$termArr['relationshipRemarks'] = 'https://dwc.tdwg.org/terms/#dwc:relationshipRemarks';
		$columnArr['relationshipRemarks'] = 'notes';

		$termArr['associd'] = 'https://symbiota.org/terms/associd';
		$columnArr['associd'] = 'associd';
		$termArr['associationType'] = 'https://symbiota.org/terms/associationType';
		$columnArr['associationType'] = 'associationType';
		$termArr['subType'] = 'https://symbiota.org/terms/subType';
		$columnArr['subType'] = 'subType';
		$termArr['objectID'] = 'https://symbiota.org/terms/objectID';
		$columnArr['objectID'] = 'objectID';
		$termArr['identifier'] = 'https://symbiota.org/terms/identifier';
		$columnArr['identifier'] = 'identifier';
		$termArr['basisOfRecord'] = 'https://symbiota.org/terms/basisOfRecord';
		$columnArr['basisOfRecord'] = 'basisOfRecord';
		$termArr['verbatimSciname'] = 'https://symbiota.org/terms/verbatimSciname';
		$columnArr['verbatimSciname'] = 'verbatimSciname';
		$termArr['tid'] = 'https://symbiota.org/terms/tid';
		$columnArr['tid'] = 'tid';
		$termArr['locationOnHost'] = 'https://symbiota.org/terms/locationOnHost';
		$columnArr['locationOnHost'] = 'locationOnHost';
		$termArr['conditionOfAssociate'] = 'https://symbiota.org/terms/conditionOfAssociate';
		$columnArr['conditionOfAssociate'] = 'conditionOfAssociate';
		$termArr['imageMapJSON'] = 'https://symbiota.org/terms/imageMapJSON';
		$columnArr['imageMapJSON'] = 'imageMapJSON';
		$termArr['dynamicProperties'] = 'https://symbiota.org/terms/dynamicProperties';
		$columnArr['dynamicProperties'] = 'dynamicProperties';
		$termArr['sourceIdentifier'] = 'https://symbiota.org/terms/sourceIdentifier';
		$columnArr['sourceIdentifier'] = 'sourceIdentifier';
		$termArr['recordID'] = 'https://symbiota.org/terms/recordID';
		$columnArr['recordID'] = 'recordID';
		$termArr['createdUid'] = 'https://symbiota.org/terms/createdUid';
		$columnArr['createdUid'] = 'createdUid';
		$termArr['modifiedTimestamp'] = 'https://symbiota.org/terms/modifiedTimestamp';
		$columnArr['modifiedTimestamp'] = 'modifiedTimestamp';
		$termArr['modifiedUid'] = 'https://symbiota.org/terms/modifiedUid';
		$columnArr['modifiedUid'] = 'modifiedUid';
		$termArr['initialtimestamp'] = 'https://symbiota.org/terms/initialtimestamp';
		$columnArr['initialtimestamp'] = 'initialtimestamp';
        

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

	private function setSqlBase(){
		if($this->fieldArr){
			$sqlFrag = '';
			foreach($this->fieldArr['fields'] as $colName){
				if($colName) $sqlFrag .= ', ' . $colName;
			}
			$this->sqlBase = 'SELECT ' . trim($sqlFrag, ', ') . ' FROM omoccurassociations ';
		}
	}

}

?>