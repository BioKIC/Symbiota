<?php
class DwcArchiverImage{

	public static function getImageArr($schemaType){
		$fieldArr['coreid'] = 'o.occid';
		$termArr['identifier'] = 'http://purl.org/dc/terms/identifier';
		$fieldArr['identifier'] = 'IFNULL(m.originalurl,m.url) as identifier';
		$termArr['accessURI'] = 'http://rs.tdwg.org/ac/terms/accessURI';
		$fieldArr['accessURI'] = 'IFNULL(NULLIF(m.originalurl,""),m.url) as accessURI';
		$termArr['thumbnailAccessURI'] = 'http://rs.tdwg.org/ac/terms/thumbnailAccessURI';
		$fieldArr['thumbnailAccessURI'] = 'm.thumbnailurl as thumbnailAccessURI';
		$termArr['goodQualityAccessURI'] = 'http://rs.tdwg.org/ac/terms/goodQualityAccessURI';
		$fieldArr['goodQualityAccessURI'] = 'm.url as goodQualityAccessURI';
		$termArr['rights'] = 'http://purl.org/dc/terms/rights';
		$fieldArr['rights'] = 'c.rights';
		$termArr['Owner'] = 'http://ns.adobe.com/xap/1.0/rights/Owner';	//Institution name
		$fieldArr['Owner'] = 'IFNULL(c.rightsholder,CONCAT(c.collectionname," (",CONCAT_WS("-",c.institutioncode,c.collectioncode),")")) AS owner';
		$termArr['creator'] = 'http://purl.org/dc/elements/1.1/creator';
		$fieldArr['creator'] = 'IF(m.creatorUid IS NOT NULL,CONCAT_WS(" ",u.firstname,u.lastname),m.creator) AS creator';
		$termArr['UsageTerms'] = 'http://ns.adobe.com/xap/1.0/rights/UsageTerms';	//Creative Commons BY-SA 4.0 license
		$fieldArr['UsageTerms'] = 'm.copyright AS usageterms';
		$termArr['WebStatement'] = 'http://ns.adobe.com/xap/1.0/rights/WebStatement';	//https://creativecommons.org/licenses/by-nc-sa/4.0/us/
		$fieldArr['WebStatement'] = 'c.accessrights AS webstatement';
		$termArr['caption'] = 'http://rs.tdwg.org/ac/terms/caption';
		$fieldArr['caption'] = 'm.caption';
		$termArr['comments'] = 'http://rs.tdwg.org/ac/terms/comments';
		$fieldArr['comments'] = 'm.notes';
		$termArr['providerManagedID'] = 'http://rs.tdwg.org/ac/terms/providerManagedID';	//GUID
		$fieldArr['providerManagedID'] = 'm.recordID AS providermanagedid';
		$termArr['MetadataDate'] = 'http://ns.adobe.com/xap/1.0/MetadataDate';	//timestamp
		$fieldArr['MetadataDate'] = 'm.initialtimestamp AS metadatadate';
		$termArr['format'] = 'http://purl.org/dc/terms/format';		//jpg
		$fieldArr['format'] = 'm.format';
		$termArr['associatedSpecimenReference'] = 'http://rs.tdwg.org/ac/terms/associatedSpecimenReference';	//reference url in portal

		//This select statement must be here for the csv columns to be correct
		$fieldArr['associatedSpecimenReference'] = 'null as associatedSpecimenReference';
		$termArr['type'] = 'http://purl.org/dc/terms/type';		//StillImage or Sound
		$fieldArr['type'] = 'CASE
		WHEN m.mediaType = "audio" THEN "Sound"
		ELSE "StillImage" END as type';

		$termArr['subtype'] = 'http://rs.tdwg.org/ac/terms/subtype';		//Photograph or Recorded Organism
		$fieldArr['subtype'] = 'CASE
		WHEN m.mediaType = "audio" THEN "Recorded Organism"
		ELSE "Photograph" END as subtype';

		$termArr['metadataLanguage'] = 'http://rs.tdwg.org/ac/terms/metadataLanguage';	//en
		$fieldArr['metadataLanguage'] = '';
		$termArr['mediaID'] = 'https://symbiota.org/terms/mediaID';	//en
		$fieldArr['mediaID'] = 'm.mediaID';

		if($schemaType == 'backup') $fieldArr['rights'] = 'm.copyright';

		$retArr['terms'] = self::trimBySchemaType($termArr, $schemaType);
		$retArr['fields'] = self::trimBySchemaType($fieldArr, $schemaType);
		return $retArr;
	}

	private static function trimBySchemaType($imageArr, $schemaType){
		$trimArr = array();
		if($schemaType == 'backup'){
			$trimArr = array('Owner', 'UsageTerms', 'WebStatement');
		}
		return array_diff_key($imageArr,array_flip($trimArr));
	}

	public static function getSqlImages($fieldArr, $conditionSql, $redactLocalities, $rareReaderArr){
		$sql = '';
		if($fieldArr && $conditionSql){
			$sqlFrag = '';
			foreach($fieldArr as $fieldName => $colName){
				if($colName) $sqlFrag .= ', '.$colName;
			}
			$sql = 'SELECT '.trim($sqlFrag,', ').
				' FROM media m INNER JOIN omoccurrences o ON m.occid = o.occid '.
				'INNER JOIN omcollections c ON o.collid = c.collid '.
				'LEFT JOIN users u ON m.creatorUid = u.uid ';
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
			if(strpos($conditionSql,'ds.datasetid')){
				$sql .= 'LEFT JOIN omoccurdatasetlink ds ON o.occid = ds.occid ';
			}
			if(stripos($conditionSql,'a.stateid')){
				//Search is limited by occurrence attribute
				$sql .= 'INNER JOIN tmattributes a ON o.occid = a.occid ';
			}
			elseif(stripos($conditionSql,'s.traitid')){
				//Search is limited by occurrence trait
				$sql .= 'INNER JOIN tmattributes a ON o.occid = a.occid '.
					'INNER JOIN tmstates s ON a.stateid = s.stateid ';
			}
			$sql .= $conditionSql;
			if($redactLocalities){
				if($rareReaderArr){
					$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL OR c.collid IN('.implode(',',$rareReaderArr).')) ';
				}
				else{
					$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL) ';
				}
			}
		}
		return $sql;
	}
}
?>
