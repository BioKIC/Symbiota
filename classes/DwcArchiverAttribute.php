<?php
class DwcArchiverAttribute{

	public static function getFieldArr(){
		$fieldArr['coreid'] = 'o.occid';
		//$termArr['measurementID'] = 'http://rs.tdwg.org/dwc/terms/measurementID';
		//$fieldArr['measurementID'] = '';
		$termArr['measurementType'] = 'http://rs.tdwg.org/dwc/terms/measurementType';
		$fieldArr['measurementType'] = 'm.traitname';
		$termArr['measurementTypeID'] = 'http://rs.iobis.org/obis/terms/measurementTypeID';
		$fieldArr['measurementTypeID'] = 'm.refurl AS measurementTypeID';
		$termArr['measurementValue'] = 'http://rs.tdwg.org/dwc/terms/measurementValue';
		$fieldArr['measurementValue'] = 'IFNULL(a.xvalue,s.statename) AS measurementValue';
		$termArr['measurementValueID'] = 'http://rs.iobis.org/obis/terms/measurementValueID';
		$fieldArr['measurementValueID'] = 's.refurl AS measurementValueID';
		//$termArr['measurementAccuracy'] = 'http://rs.tdwg.org/dwc/terms/measurementAccuracy';
		//$fieldArr['measurementAccuracy'] = '';
		$termArr['measurementUnit'] = 'http://rs.tdwg.org/dwc/terms/measurementUnit';
		$fieldArr['measurementUnit'] = 'm.units';
		//$termArr['measurementUnitID'] = 'http://rs.iobis.org/obis/terms/measurementUnitID';
		//$fieldArr['measurementUnitID'] = '';
		$termArr['measurementDeterminedDate'] = 'http://rs.tdwg.org/dwc/terms/measurementDeterminedDate';
		$fieldArr['measurementDeterminedDate'] = 'DATE_FORMAT(IFNULL(a.datelastmodified,a.initialtimestamp), "%Y-%m-%dT%TZ") AS detDate';
		$termArr['measurementDeterminedBy'] = 'http://rs.tdwg.org/dwc/terms/measurementDeterminedBy';
		$fieldArr['measurementDeterminedBy'] = 'u.username';
		//$termArr['measurementMethod'] = 'http://rs.tdwg.org/dwc/terms/measurementMethod';
		//$fieldArr['measurementMethod'] = '';
		$termArr['measurementRemarks'] = 'http://rs.tdwg.org/dwc/terms/measurementRemarks';
		$fieldArr['measurementRemarks'] = 'a.notes';

		$retArr['terms'] = $termArr;
		$retArr['fields'] = $fieldArr;
		return $retArr;
	}

	public static function getSql($fieldArr, $conditionSql){
		$sql = '';
		if($fieldArr && $conditionSql){
			$sqlFrag = '';
			foreach($fieldArr as $fieldName => $colName){
				if($colName) $sqlFrag .= ', '.$colName;
			}
			$sql = 'SELECT '.trim($sqlFrag,', ').
				' FROM tmtraits m INNER JOIN tmstates s ON m.traitid = s.traitid '.
				'INNER JOIN tmattributes a ON s.stateid = a.stateid '.
				'INNER JOIN users u ON a.createduid = u.uid '.
				'INNER JOIN omoccurrences o ON a.occid = o.occid ';
			if(strpos($conditionSql,'ts.taxauthid')){
				$sql .= 'LEFT JOIN taxstatus ts ON o.tidinterpreted = ts.tid ';
			}
			if(stripos($conditionSql,'e.parenttid')){
				$sql .= 'LEFT JOIN taxaenumtree e ON o.tidinterpreted = e.tid ';
			}
			if(strpos($conditionSql,'ctl.clid')){
				//Search criteria came from custom search page
				$sql .= 'LEFT JOIN fmvouchers v ON o.occid = v.occid LEFT JOIN fmchklsttaxalink ctl ON v.clTaxaID = ctl.clTaxaID ';
			}
			if(strpos($conditionSql,'p.point')){
				//Search criteria came from map search page
				$sql .= 'LEFT JOIN omoccurpoints p ON o.occid = p.occid ';
			}
			if(strpos($conditionSql,'MATCH(f.recordedby)') || strpos($conditionSql,'MATCH(f.locality)')){
				$sql .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
			}
			$sql .= $conditionSql;
			$sql .= ' ORDER BY o.occid ';
			//echo '<div>'.$sql.'</div>'; exit;
		}
		return $sql;
	}
}
?>