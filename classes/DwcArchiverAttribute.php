<?php
include_once($SERVER_ROOT.'/classes/DwcArchiverBaseManager.php');

class DwcArchiverAttribute extends DwcArchiverBaseManager{

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

	private function setFieldArr(){
		$columnArr['coreid'] = 'a.occid';
		//$termArr['measurementID'] = 'http://rs.tdwg.org/dwc/terms/measurementID';
		//$columnArr['measurementID'] = '';
		$termArr['measurementType'] = 'http://rs.tdwg.org/dwc/terms/measurementType';
		$columnArr['measurementType'] = 'm.traitname';
		$termArr['measurementTypeID'] = 'http://rs.iobis.org/obis/terms/measurementTypeID';
		$columnArr['measurementTypeID'] = 'm.refurl AS measurementTypeID';
		$termArr['measurementValue'] = 'http://rs.tdwg.org/dwc/terms/measurementValue';
		$columnArr['measurementValue'] = 'IFNULL(a.xvalue, s.statename) AS measurementValue';
		$termArr['measurementValueID'] = 'http://rs.iobis.org/obis/terms/measurementValueID';
		$columnArr['measurementValueID'] = 's.refurl AS measurementValueID';
		//$termArr['measurementAccuracy'] = 'http://rs.tdwg.org/dwc/terms/measurementAccuracy';
		//$columnArr['measurementAccuracy'] = '';
		$termArr['measurementUnit'] = 'http://rs.tdwg.org/dwc/terms/measurementUnit';
		$columnArr['measurementUnit'] = 'm.units';
		//$termArr['measurementUnitID'] = 'http://rs.iobis.org/obis/terms/measurementUnitID';
		//$columnArr['measurementUnitID'] = '';
		$termArr['measurementDeterminedDate'] = 'http://rs.tdwg.org/dwc/terms/measurementDeterminedDate';
		$columnArr['measurementDeterminedDate'] = 'DATE_FORMAT(IFNULL(a.datelastmodified,a.initialtimestamp), "%Y-%m-%dT%TZ") AS detDate';
		$termArr['measurementDeterminedBy'] = 'http://rs.tdwg.org/dwc/terms/measurementDeterminedBy';
		$columnArr['measurementDeterminedBy'] = 'u.username';
		//$termArr['measurementMethod'] = 'http://rs.tdwg.org/dwc/terms/measurementMethod';
		//$columnArr['measurementMethod'] = '';
		$termArr['measurementRemarks'] = 'http://rs.tdwg.org/dwc/terms/measurementRemarks';
		$columnArr['measurementRemarks'] = 'a.notes';

		$this->fieldArr['terms'] = $this->trimBySchemaType($termArr);
		$this->fieldArr['fields'] = $this->trimBySchemaType($columnArr);
	}

	private function trimBySchemaType($dataArr){
		$trimArr = array();
		if($this->schemaType == 'backup'){
			//$trimArr = array('Owner', 'UsageTerms', 'WebStatement');
		}
		return array_diff_key($dataArr,array_flip($trimArr));
	}

	private function setSqlBase(){
		if($this->fieldArr){
			$sqlFrag = '';
			foreach($this->fieldArr['fields'] as $colName){
				if($colName && $colName != 'msDynamicField') $sqlFrag .= ', '.$colName;
			}
			$this->sqlBase = 'SELECT '.trim($sqlFrag,', ').'
				FROM tmtraits m INNER JOIN tmstates s ON m.traitid = s.traitid
				INNER JOIN tmattributes a ON s.stateid = a.stateid
				INNER JOIN users u ON a.createduid = u.uid ';
		}
	}
}
?>