<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class OccurrenceLabel{

	private $conn;
	private $collid;
	private $collArr = array();
	private $labelFieldArr = array();
	private $errorArr = array();

	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	//Label functions
	public function queryOccurrences($postArr, $limit){
		global $USER_RIGHTS;
		$canReadRareSpp = false;
		if($GLOBALS['IS_ADMIN'] || array_key_exists('CollAdmin', $USER_RIGHTS) || array_key_exists('RareSppAdmin', $USER_RIGHTS) || array_key_exists('RareSppReadAll', $USER_RIGHTS)){
			$canReadRareSpp = true;
		}
		elseif((array_key_exists('CollEditor', $USER_RIGHTS) && in_array($this->collid,$USER_RIGHTS['CollEditor'])) || (array_key_exists('RareSppReader', $USER_RIGHTS) && in_array($this->collid,$USER_RIGHTS['RareSppReader']))){
			$canReadRareSpp = true;
		}
		$retArr = array();
		if($this->collid){
			$sqlWhere = '';
			$sqlOrderBy = '';
			if($postArr['taxa']){
				$sqlWhere .= 'AND (o.sciname LIKE "'.$this->cleanInStr($postArr['taxa']).'%") ';
			}
			if($postArr['labelproject']){
				$sqlWhere .= 'AND (o.labelproject = "'.$this->cleanInStr($postArr['labelproject']).'") ';
			}
			if($postArr['recordenteredby']){
				$sqlWhere .= 'AND (o.recordenteredby = "'.$this->cleanInStr($postArr['recordenteredby']).'") ';
			}
			$date1 = $this->cleanInStr($postArr['date1']);
			$date2 = $this->cleanInStr($postArr['date2']);
			if(!$date1 && $date2){
				$date1 = $date2;
				$date2 = '';
			}
			$dateTarget = $this->cleanInStr($postArr['datetarget']);
			if($date1){
				if($date2) $sqlWhere .= 'AND (DATE(o.'.$dateTarget.') BETWEEN "'.$date1.'" AND "'.$date2.'") ';
				else $sqlWhere .= 'AND (DATE(o.'.$dateTarget.') = "'.$date1.'") ';
				$sqlOrderBy .= ',o.'.$dateTarget;
			}
			if($postArr['recordnumber']){
				$rnArr = explode(',',$this->cleanInStr($postArr['recordnumber']));
				$rnBetweenFrag = array();
				$rnInFrag = array();
				foreach($rnArr as $v){
					$v = trim($v);
					if($p = strpos($v,' - ')){
						$term1 = trim(substr($v,0,$p));
						$term2 = trim(substr($v,$p+3));
						if(is_numeric($term1) && is_numeric($term2)){
							$rnBetweenFrag[] = '(o.recordnumber BETWEEN '.$term1.' AND '.$term2.')';
						}
						else{
							$catTerm = 'o.recordnumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
							if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(o.recordnumber) = '.strlen($term2);
							$rnBetweenFrag[] = '('.$catTerm.')';
						}
					}
					else{
						$rnInFrag[] = $v;
					}
				}
				$rnWhere = '';
				if($rnBetweenFrag){
					$rnWhere .= 'OR '.implode(' OR ',$rnBetweenFrag);
				}
				if($rnInFrag){
					$rnWhere .= 'OR (o.recordnumber IN("'.implode('","',$rnInFrag).'")) ';
				}
				$sqlWhere .= 'AND ('.substr($rnWhere,3).') ';
			}
			if($postArr['recordedby']){
				$recordedBy = $this->cleanInStr($postArr['recordedby']);
				if(strlen($recordedBy) < 4 || in_array(strtolower($recordedBy),array('best','little'))){
					//Need to avoid FULLTEXT stopwords interfering with return
					$sqlWhere .= 'AND (o.recordedby LIKE "%'.$recordedBy.'%") ';
				}
				else{
					$sqlWhere .= 'AND (MATCH(f.recordedby) AGAINST("'.$recordedBy.'")) ';
				}
			}
			if($postArr['identifier']){
				$iArr = explode(',',$this->cleanInStr($postArr['identifier']));
				$iBetweenFrag = array();
				$iInFrag = array();
				foreach($iArr as $v){
					$v = trim($v);
					if($p = strpos($v,' - ')){
						$term1 = trim(substr($v,0,$p));
						$term2 = trim(substr($v,$p+3));
						if(is_numeric($term1) && is_numeric($term2)){
							$iBetweenFrag[] = '((o.catalogNumber BETWEEN '.$term1.' AND '.$term2.') OR (o.otherCatalogNumbers BETWEEN '.$term1.' AND '.$term2.') OR (i.identifiervalue BETWEEN '.$term1.' AND '.$term2.'))';
						}
						else{
							$catTerm = '(o.catalogNumber BETWEEN "'.$term1.'" AND "'.$term2.'" OR o.othercatalogNumbers BETWEEN "'.$term1.'" AND "'.$term2.'" OR i.identifiervalue BETWEEN "'.$term1.'" AND "'.$term2.'")';
							//if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(IFNULL(i.identifiervalue, o.catalogNumber)) = '.strlen($term2);
							$iBetweenFrag[] = '('.$catTerm.')';
						}
					}
					else $iInFrag[] = $v;
				}
				$iWhere = '';
				if($iBetweenFrag) $iWhere .= 'OR '.implode(' OR ',$iBetweenFrag);
				if($iInFrag){
					$iWhere .= 'OR (o.catalogNumber IN("'.implode('","',$iInFrag).'") OR o.otherCatalogNumbers IN("'.implode('","',$iInFrag).'") OR i.identifiervalue IN("'.implode('","',$iInFrag).'")) ';
				}
				$sqlWhere .= 'AND ('.substr($iWhere,3).') ';
				$sqlOrderBy .= ',i.identifiervalue,o.catalogNumber,o.otherCatalogNumbers';
			}
			if($this->collArr['colltype'] == 'General Observations'){
				$sqlWhere .= 'AND (o.collid = '.$this->collid.') ';
				if(!array_key_exists('extendedsearch', $postArr)) $sqlWhere .= ' AND (o.observeruid = '.$GLOBALS['SYMB_UID'].') ';
			}
			elseif(!array_key_exists('extendedsearch', $postArr)){
				$sqlWhere .= 'AND (o.collid = '.$this->collid.') ';
			}
			$sql = 'SELECT DISTINCT o.occid, o.collid, IFNULL(o.duplicatequantity,1) AS q, CONCAT_WS(" ",o.recordedby,IFNULL(o.recordnumber,o.eventdate)) AS collector, o.observeruid, '.
				'o.family, o.sciname, CONCAT_WS("; ",o.country, o.stateProvince, o.county, o.locality) AS locality, IFNULL(o.localitySecurity,0) AS localitySecurity '.
				'FROM omoccurrences o LEFT JOIN omoccuridentifiers i ON o.occid = i.occid ';
			if(strpos($sqlWhere,'MATCH(f.recordedby)') || strpos($sqlWhere,'MATCH(f.locality)')){
				$sql.= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
			}
			if($sqlWhere) $sql .= 'WHERE '.substr($sqlWhere, 4);
			if($sqlOrderBy) $sql .= ' ORDER BY '.substr($sqlOrderBy,1);
			else $sql .= ' ORDER BY (o.recordnumber+1)';
			$sql .= ' LIMIT ' . $limit;
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$localitySecurity = $r->localitySecurity;
				if(!$localitySecurity || $canReadRareSpp || ($r->observeruid == $GLOBALS['SYMB_UID'])){
					$occId = $r->occid;
					$retArr[$occId]['collid'] = $r->collid;
					$retArr[$occId]['q'] = $r->q;
					$retArr[$occId]['c'] = $r->collector;
					//$retArr[$occId]['f'] = $r->family;
					$retArr[$occId]['s'] = $r->sciname;
					$retArr[$occId]['l'] = $r->locality;
					$retArr[$occId]['uid'] = $r->observeruid;
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getLabelArray($occidArr, $speciesAuthors = false){
		$retArr = array();
		if($occidArr){
			$authorArr = array();
			$occidStr = implode(',',$occidArr);
			if(!preg_match('/^[,\d]+$/', $occidStr)) return null;
			$sqlWhere = 'WHERE (o.occid IN('.$occidStr.')) ';
			//Get species authors for infraspecific taxa
			$sql1 = 'SELECT o.occid, t2.author '.
				'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
				'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'INNER JOIN taxa t2 ON ts.parenttid = t2.tid '.
				$sqlWhere.' AND t.rankid > 220 AND ts.taxauthid = 1 ';
			if(!$speciesAuthors) $sql1 .= 'AND t.unitname2 = t.unitname3 ';
			//echo $sql1; exit;
			if($rs1 = $this->conn->query($sql1)){
				while($row1 = $rs1->fetch_object()){
					$authorArr[$row1->occid] = $row1->author ?? '';
				}
				$rs1->free();
			}
			//Get occurrence records
			$this->setLabelFieldArr();
			$sql2 = 'SELECT '.implode(',',$this->labelFieldArr).' FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.tid '.$sqlWhere;
			//echo 'SQL: '.$sql2;
			if($rs2 = $this->conn->query($sql2)){
				while($row2 = $rs2->fetch_assoc()){
					$row2 = array_change_key_case($row2);
					if(array_key_exists($row2['occid'],$authorArr)) $row2['parentauthor'] = $authorArr[$row2['occid']];
					$retArr[$row2['occid']] = $row2;
				}
				$rs2->free();
			}
			//Append identifiers indexed within omoccurridentifier
			if($retArr){
				$sql = 'SELECT occid, identifiername, identifiervalue FROM omoccuridentifiers WHERE occid IN('.implode(',',array_keys($retArr)).') ORDER BY sortBy';
				if($rs = $this->conn->query($sql)){
					$otherCatArr = array();
					$cnt = 0;
					while($r = $rs->fetch_object()){
						$otherCatArr[$r->occid][$cnt]['v'] = $r->identifiervalue;
						$otherCatArr[$r->occid][$cnt]['n'] = $r->identifiername ?? '';
						$cnt++;
					}
					$rs->free();
					foreach($otherCatArr as $occid => $ocnArr){
						$verbIdStr = $retArr[$occid]['othercatalognumbers'] ?? '';
						$ocnStr = '';
						foreach($ocnArr as $idArr){
							$ocnStr .= '; '.($idArr['n']?$idArr['n'].': ':'').$idArr['v'];
							$verbIdStr = str_ireplace($idArr['n'],'',$verbIdStr);
							$verbIdStr = str_ireplace($idArr['v'],'',$verbIdStr);
						}
						$ocnStr = trim($ocnStr,';,: ');
						$verbIdStr = trim($verbIdStr,';,: ');
						if($verbIdStr) $ocnStr = $ocnStr.'; '.$verbIdStr;
						$retArr[$occid]['othercatalognumbers'] = $ocnStr;
					}
				}
			}
		}
		return $retArr;
	}

	public function exportLabelCsvFile($postArr){
		global $CHARSET;
		$occidArr = $postArr['occid'];
		if($occidArr){
			$speciesAuthors = 0;
			if(array_key_exists('speciesauthors',$postArr) && $postArr['speciesauthors']) $speciesAuthors = 1;
			$labelArr = $this->getLabelArray($occidArr, $speciesAuthors);
			if($labelArr){
				$fileName = 'labeloutput_'.time().".csv";
				ob_start();
				ob_clean();
				ob_end_flush();
				header('Content-Description: Symbiota Label Output File');
				header ('Content-Type: text/csv');
				header ('Content-Disposition: attachment; filename="'.$fileName.'"');
				header('Content-Transfer-Encoding: '.strtoupper($CHARSET));
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');

				$fh = fopen('php://output','w');
				$this->setLabelFieldArr();
				$headerArr = array_diff(array_keys($this->labelFieldArr), array('collid','duplicateQuantity','dateLastModified'));
				fputcsv($fh,$headerArr);
				//change header value to lower case
				$headerLcArr = array();
				foreach($headerArr as $k => $v){
					$headerLcArr[strtolower($v)] = $k;
				}
				//Output records
				foreach($labelArr as $occid => $occArr){
					$dupCnt = $postArr['q-'.$occid];
					if(isset($occArr['parentauthor']) && $occArr['parentauthor']){
						$occArr['scientificname_with_author'] = trim($occArr['speciesname'].' '.trim($occArr['parentauthor'].' '.$occArr['taxonrank']).' '.$occArr['infraspecificepithet'].' '.$occArr['scientificnameauthorship']);
					}
					for($i = 0;$i < $dupCnt;$i++){
						fputcsv($fh,array_intersect_key($occArr,$headerLcArr));
					}
				}
				fclose($fh);
			}
			else{
				echo "Recordset is empty.\n";
			}
		}
	}

	private function setLabelFieldArr(){
		if(!$this->labelFieldArr){
			$this->labelFieldArr = array('occid'=>'o.occid', 'collid'=>'o.collid', 'catalogNumber'=>'o.catalognumber', 'otherCatalogNumbers'=>'o.othercatalognumbers', 'family'=>'o.family',
				'scientificName'=>'o.sciname AS scientificname', 'scientificName_with_author'=>'CONCAT_WS(" ",o.sciname,o.scientificnameauthorship) AS scientificname_with_author',
				'speciesName'=>'TRIM(CONCAT_WS(" ",t.unitind1,t.unitname1,t.unitind2,t.unitname2)) AS speciesname', 'taxonRank'=>'t.unitind3 AS taxonrank',
				'infraSpecificEpithet'=>'t.unitname3 AS infraspecificepithet', 'scientificNameAuthorship'=>'o.scientificnameauthorship', 'parentAuthor'=>'"" AS parentauthor','identifiedBy'=>'o.identifiedby',
				'dateIdentified'=>'o.dateidentified', 'identificationReferences'=>'o.identificationreferences', 'identificationRemarks'=>'o.identificationremarks', 'taxonRemarks'=>'o.taxonremarks',
				'identificationQualifier'=>'o.identificationqualifier', 'typeStatus'=>'o.typestatus', 'recordedBy'=>'o.recordedby', 'recordNumber'=>'o.recordnumber', 'associatedCollectors'=>'o.associatedcollectors',
				'eventDate'=>'DATE_FORMAT(o.eventdate,"%e %M %Y") AS eventdate', 'year'=>'o.year', 'month'=>'o.month', 'day'=>'o.day', 'monthName'=>'DATE_FORMAT(o.eventdate,"%M") AS monthname',
				'verbatimEventDate'=>'o.verbatimeventdate', 'habitat'=>'o.habitat', 'substrate'=>'o.substrate', 'occurrenceRemarks'=>'o.occurrenceremarks', 'associatedTaxa'=>'o.associatedtaxa',
				'dynamicProperties'=>'o.dynamicproperties','verbatimAttributes'=>'o.verbatimattributes', 'behavior'=>'behavior', 'reproductiveCondition'=>'o.reproductivecondition', 'cultivationStatus'=>'o.cultivationstatus',
				'establishmentMeans'=>'o.establishmentmeans','lifeStage'=>'lifestage','sex'=>'sex','individualCount'=>'individualcount','samplingProtocol'=>'samplingprotocol','preparations'=>'preparations',
				'country'=>'o.country', 'stateProvince'=>'o.stateprovince', 'county'=>'o.county', 'municipality'=>'o.municipality', 'locality'=>'o.locality', 'decimalLatitude'=>'o.decimallatitude',
				'decimalLongitude'=>'o.decimallongitude', 'geodeticDatum'=>'o.geodeticdatum', 'coordinateUncertaintyInMeters'=>'o.coordinateuncertaintyinmeters', 'verbatimCoordinates'=>'o.verbatimcoordinates',
				'minimumElevationInMeters'=>'o.minimumelevationinmeters', 'maximumElevationInMeters'=>'o.maximumelevationinmeters',
				'elevationInMeters'=>'CONCAT_WS(" - ",o.minimumElevationInMeters,o.maximumElevationInMeters) AS elevationinmeters', 'verbatimElevation'=>'o.verbatimelevation',
				'minimumDepthInMeters'=>'minimumdepthinmeters', 'maximumDepthInMeters'=>'maximumdepthinmeters', 'verbatimDepth'=>'verbatimdepth',
				'disposition'=>'o.disposition', 'storageLocation'=>'storagelocation', 'duplicateQuantity'=>'o.duplicatequantity', 'dateLastModified'=>'o.datelastmodified');
		}
	}

	public function getLabelBlock($blockArr,$occArr){
		$outStr = '';
		foreach($blockArr as $bArr){
			if(array_key_exists('divBlock', $bArr)){
				$outStr .= $this->getDivBlock($bArr['divBlock'],$occArr);
			}
			elseif(array_key_exists('fieldBlock', $bArr)){
				$delimiter = (isset($bArr['delimiter'])?$bArr['delimiter']:'');
				$cnt = 0;
				$fieldDivStr = '';
				foreach($bArr['fieldBlock'] as $fieldArr){
					$fieldName = strtolower($fieldArr['field']);
					$fieldValue = trim($occArr[$fieldName] ?? '');
					if($fieldValue){
						if($delimiter && $cnt) $fieldDivStr .= $delimiter;
						$fieldDivStr .= '<span class="'.$fieldName.(isset($fieldArr['className'])?' '.$fieldArr['className']:'').'" '.(isset($fieldArr['style'])?'style="'.$fieldArr['style'].'"':'').'>';
						if(isset($fieldArr['prefix']) && $fieldArr['prefix']){
							$fieldDivStr .= '<span class="'.$fieldName.'Prefix"'.(isset($fieldArr['prefixStyle'])?' style="'.$fieldArr['prefixStyle'].'"':'').'>'.$fieldArr['prefix'].'</span>';
						}
						$fieldDivStr .= $fieldValue;
						if(isset($fieldArr['suffix']) && $fieldArr['suffix']){
							$fieldDivStr .= '<span class="'.$fieldName.'Suffix"'.(isset($fieldArr['suffixStyle'])?' style="'.$fieldArr['suffixStyle'].'"':'').'>'.$fieldArr['suffix'].'</span>';
						}
						$fieldDivStr .= '</span>';
						$cnt++;
					}
				}
				if($fieldDivStr) $outStr .= '<div class="field-block'.(isset($bArr['className'])?' '.$bArr['className']:'').'"'.(isset($bArr['style'])?' style="'.$bArr['style'].'"':'').'>'.$fieldDivStr.'</div>';
			}
		}
		return $outStr;
	}

	private function getDivBlock($divArr,$occArr){
		$contentStr = '';
		if(array_key_exists('blocks', $divArr)) $contentStr = $this->getLabelBlock($divArr['blocks'],$occArr);
		elseif(array_key_exists('content', $divArr)) $contentStr = $divArr['content'];
		if($contentStr){
			$attrStr = '';
			if(isset($divArr['className'])) $attrStr .= 'class="'.$divArr['className'].'"';
			if(isset($divArr['style']) && $divArr['style']) $attrStr .= 'style="'.$divArr['style'].'"';
			return '<div '.trim($attrStr).'>'.$contentStr.'</div>'."\n";
		}
		return '';
	}

	public function getLabelFormatByID($labelCat, $labelIndex){
		if(is_numeric($labelIndex)){
			if($labelCat == 'global'){
				if(file_exists($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php')){
					include($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php');
					if(isset($LABEL_FORMAT_JSON)){
						if($labelFormatArr = json_decode($LABEL_FORMAT_JSON,true)){
							if(isset($labelFormatArr['labelFormats'][$labelIndex])){
								return $labelFormatArr['labelFormats'][$labelIndex];
							}
							else $this->errorArr[] = 'ERROR returning global format: index does not exist';
						}
						else $this->errorArr[] = 'ERROR returning global format: issue parsing JSON string';
					}
					else $this->errorArr[] = 'ERROR returning global format: $LABEL_FORMAT_JSON does not exist';
				}
				else $this->errorArr[] = 'ERROR returning global format: /content/collections/reports/labeljson.php does not exist';
				return false;
			}
			elseif($labelCat == 'coll'){
				if($this->collArr['dynprops']){
					if($dymPropArr = json_decode($this->collArr['dynprops'],true)){
						if(isset($dymPropArr['labelFormats'][$labelIndex])){
							return $dymPropArr['labelFormats'][$labelIndex];
						}
						else $this->errorArr[] = 'ERROR returning collection format: labelFormats or index does not exist';
					}
					else $this->errorArr[] = 'ERROR returning collection format: issue parsing JSON string';
				}
				else $this->errorArr[] = 'ERROR returning collection format: dynamicProperties not defined';
			}
			elseif($labelCat == 'user'){
				$dynPropStr = '';
				$sql = 'SELECT dynamicProperties FROM users WHERE uid = '.$GLOBALS['SYMB_UID'];
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$dynPropStr = $r->dynamicProperties;
				}
				$rs->free();
				if($dynPropStr){
					if($dymPropArr = json_decode($dynPropStr,true)){
						if(isset($dymPropArr['labelFormats'][$labelIndex])){
							return $dymPropArr['labelFormats'][$labelIndex];
						}
						else $this->errorArr[] = 'ERROR returning user format: labelFormats or index does not exist';
					}
					else $this->errorArr[] = 'ERROR returning user format: issue parsing JSON string';
				}
				else $this->errorArr[] = 'ERROR returning user format: dynamicProperties not defined';
			}
		}
		return false;
	}

	public function getLabelFormatArr($annotated = false){
		$retArr = array();
		if($GLOBALS['SYMB_UID']){
			//Add global portal defined label formats
			if(!file_exists($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php')){
				@copy($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson_template.php',$GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php');
			}
			if(file_exists($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php')){
				include($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php');
				if(isset($LABEL_FORMAT_JSON)){
					if($globalFormatArr = json_decode($LABEL_FORMAT_JSON,true)){
						if($annotated){
							if(isset($globalFormatArr['labelFormats'])){
								foreach($globalFormatArr['labelFormats'] as $k => $labelObj){
									unset($labelObj['labelFormats']);
									$retArr['g'][$k] = $labelObj;
								}
							}
						}
						else $retArr['g'] = $globalFormatArr['labelFormats'];
					}
				}
			}
			else $retArr['g'] = array('labelFormats'=>array());
			//Add collection defined label formats
			if($this->collid){
				$collFormatArr = json_decode($this->collArr['dynprops'] ?? '[]',true);
				if($annotated){
					if(isset($collFormatArr['labelFormats'])){
						foreach($collFormatArr['labelFormats'] as $k => $labelObj){
							unset($labelObj['labelBlocks']);
							$retArr['c'][$k] = $labelObj;
						}
					}
				}
				elseif(isset($collFormatArr['labelFormats'])) $retArr['c'] = $collFormatArr['labelFormats'];
				else $retArr['c'] = array();
			}
			//Add label formats associated with user profile
			$sql = 'SELECT dynamicProperties FROM users WHERE uid = '.$GLOBALS['SYMB_UID'];
			$rs = $this->conn->query($sql);
			if($rs){
				$dynPropStr = '';
				if($r = $rs->fetch_object()){
					$dynPropStr = $r->dynamicProperties;
				}
				$rs->free();
				$dynPropArr = json_decode($dynPropStr  ?? '',true);
				if($annotated){
					if(isset($dynPropArr['labelFormats'])){
						foreach($dynPropArr['labelFormats'] as $k => $labelObj){
							unset($labelObj['labelBlocks']);
							$retArr['u'][$k] = $labelObj;
						}

					}
				}
				elseif(isset($dynPropArr['labelFormats'])) $retArr['u'] = $dynPropArr['labelFormats'];
				else $retArr['u'] = array();
			}
		}
		return $retArr;
	}

	public function saveLabelJson($postArr){
		$status = true;
		$group = $postArr['group'];
		$labelIndex = '';
		if(isset($postArr['index'])) $labelIndex = $postArr['index'];
		if(is_numeric($labelIndex) || $labelIndex == ''){
			if($group == 'g'){
				$globalFormatArr = array();
				if(file_exists($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php')){
					include($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php');
					if(isset($LABEL_FORMAT_JSON)) $globalFormatArr = json_decode($LABEL_FORMAT_JSON,true);
				}
				$this->setLabelFormatAttributes($globalFormatArr,$labelIndex,$postArr);
				$status = $this->saveGlobalJson($globalFormatArr);
			}
			elseif($group == 'c'){
				if($this->collid){
					$collFormatArr = array();
					if($this->collArr['dynprops']) $collFormatArr = json_decode($this->collArr['dynprops'],true);
					$this->setLabelFormatAttributes($collFormatArr,$labelIndex,$postArr);
					$status = $this->updateCollectionJson($collFormatArr);
				}
				else{
					$this->errorArr[] = 'ERROR saving label format to omcollections table: collid not set';
					$status = false;
				}
			}
			elseif($group == 'u'){
				$sql = 'SELECT dynamicProperties FROM users WHERE uid = '.$GLOBALS['SYMB_UID'];
				$rs = $this->conn->query($sql);
				if($rs){
					$dynPropArr = array();
					if($r = $rs->fetch_object()){
						if($r->dynamicProperties) $dynPropArr = json_decode($r->dynamicProperties,true);
					}
					$rs->free();
					$this->setLabelFormatAttributes($dynPropArr,$labelIndex,$postArr);
					$status = $this->updateUserJson($dynPropArr);
				}
			}
		}
		return $status;
	}

	private function setLabelFormatAttributes(&$labelFormatArr,$labelIndex,$postArr){
		$labelArr = array();
		$labelArr['title'] = $postArr['title'];
		$labelArr['labelHeader']['prefix'] = $postArr['hPrefix'];
		if(isset($postArr['hMidText']) && is_numeric($postArr['hMidText'])) $labelArr['labelHeader']['midText'] = $postArr['hMidText'];
		else $labelArr['labelHeader']['midText'] = "0";
		$labelArr['labelHeader']['suffix'] = $postArr['hSuffix'];
		$labelArr['labelHeader']['className'] = $postArr['hClassName'];
		$labelArr['labelHeader']['style'] = $postArr['hStyle'];
		$labelArr['labelFooter']['textValue'] = $postArr['fTextValue'];
		$labelArr['labelFooter']['className'] = $postArr['fClassName'];
		$labelArr['labelFooter']['style'] = $postArr['fStyle'];
		$labelArr['customStyles'] = $postArr['customStyles'];
		$labelArr['defaultCss'] = $postArr['defaultCss'];
		$labelArr['customCss'] = $postArr['customCss'];
		$labelArr['customJS'] = $postArr['customJS'];
		$labelArr['labelType'] = $postArr['labelType'];
		$labelArr['pageSize'] = $postArr['pageSize'];
		if(isset($postArr['displaySpeciesAuthor']) && $postArr['displaySpeciesAuthor']) $labelArr['displaySpeciesAuthor'] = 1;
		else $labelArr['displaySpeciesAuthor'] = 0;
		if(isset($postArr['displayBarcode']) && $postArr['displayBarcode']) $labelArr['displayBarcode'] = 1;
		else $labelArr['displayBarcode'] = 0;
		$labelArr['labelBlocks'] = json_decode($postArr['json'],true);
		if(is_numeric($labelIndex)) $labelFormatArr['labelFormats'][$labelIndex] = $labelArr;
		else $labelFormatArr['labelFormats'][] = $labelArr;
	}

	public function cloneLabelJson($postArr){
		$status = true;
		$cloneTarget = $postArr['cloneTarget'];
		$group = $postArr['group'];
		$labelIndex = '';
		if(isset($postArr['index'])) $labelIndex = $postArr['index'];
		if(is_numeric($labelIndex) && $cloneTarget){
			//Grab source
			$globalFormatArr = array();
			$collFormatArr = array();
			$dynPropArr = array();
			$sourceLabelArr = array();
			if($group == 'g' || $cloneTarget == 'g'){
				if(file_exists($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php')){
					include($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php');
					if(isset($LABEL_FORMAT_JSON)) $globalFormatArr = json_decode($LABEL_FORMAT_JSON,true);
					if($group == 'g') $sourceLabelArr = $globalFormatArr['labelFormats'][$labelIndex];
				}
			}
			if($group == 'c' || $cloneTarget == 'c'){
				if($this->collid){
					if($this->collArr['dynprops']) $collFormatArr = json_decode($this->collArr['dynprops'],true);
					if($group == 'c') $sourceLabelArr = $collFormatArr['labelFormats'][$labelIndex];
				}
				else{
					$this->errorArr[] = 'ERROR cloning label format to omcollections table: collid not set';
					$status = false;
				}
			}
			if($group == 'u' || $cloneTarget == 'u'){
				$sql = 'SELECT dynamicProperties FROM users WHERE uid = '.$GLOBALS['SYMB_UID'];
				$rs = $this->conn->query($sql);
				if($rs){
					if($r = $rs->fetch_object()){
						if($r->dynamicProperties) $dynPropArr = json_decode($r->dynamicProperties,true);
						if($group == 'u') $sourceLabelArr = $dynPropArr['labelFormats'][$labelIndex];
					}
					$rs->free();
				}
			}
			$sourceLabelArr['title'] = $sourceLabelArr['title'].' - CLONE';
			//Save to target group
			if($cloneTarget == 'g'){
				$globalFormatArr['labelFormats'][] = $sourceLabelArr;
				$status = $this->saveGlobalJson($globalFormatArr);

			}
			elseif($cloneTarget == 'c'){
				$collFormatArr['labelFormats'][] = $sourceLabelArr;
				$status = $this->updateCollectionJson($collFormatArr);

			}
			elseif($cloneTarget == 'u'){
				$dynPropArr['labelFormats'][] = $sourceLabelArr;
				$status = $this->updateUserJson($dynPropArr);

			}
		}
		return $status;
	}

	public function deleteLabelFormat($group, $labelIndex){
		$status = true;
		if(is_numeric($labelIndex)){
			if($group == 'g'){
				$globalFormatArr = array();
				if(file_exists($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php')){
					include($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php');
					if(isset($LABEL_FORMAT_JSON)){
						$globalFormatArr = json_decode($LABEL_FORMAT_JSON,true);
						unset($globalFormatArr['labelFormats'][$labelIndex]);
						$globalFormatArr['labelFormats'] = array_values($globalFormatArr['labelFormats']);
						$status = $this->saveGlobalJson($globalFormatArr);
					}
				}
			}
			elseif($group == 'c'){
				if($this->collid){
					$collFormatArr = array();
					if($this->collArr['dynprops']) $collFormatArr = json_decode($this->collArr['dynprops'],true);
					unset($collFormatArr['labelFormats'][$labelIndex]);
					$collFormatArr['labelFormats'] = array_values($collFormatArr['labelFormats']);
					$status = $this->updateCollectionJson($collFormatArr);
				}
				else{
					$this->errorArr[] = 'ERROR saving label format to omcollections table: collid not set';
					$status = false;
				}
			}
			elseif($group == 'u'){
				$sql = 'SELECT dynamicProperties FROM users WHERE uid = '.$GLOBALS['SYMB_UID'];
				$rs = $this->conn->query($sql);
				if($rs){
					$dynPropArr = array();
					if($r = $rs->fetch_object()){
						if($r->dynamicProperties) $dynPropArr = json_decode($r->dynamicProperties,true);
					}
					$rs->free();
					unset($dynPropArr['labelFormats'][$labelIndex]);
					$dynPropArr['labelFormats'] = array_values($dynPropArr['labelFormats']);
					$status = $this->updateUserJson($dynPropArr);
				}
			}
		}
		return $status;
	}

	private function saveGlobalJson($formatArr){
		$status = false;
		$jsonStr = "<?php\n ".'$LABEL_FORMAT_JSON = \''.json_encode($formatArr,JSON_PRETTY_PRINT | JSON_HEX_APOS)."'; \n?>";
		if($fh = fopen($GLOBALS['SERVER_ROOT'].'/content/collections/reports/labeljson.php','w')){
			if(!fwrite($fh,$jsonStr)){
				$this->errorArr[] = 'ERROR saving label format to global file ';
				$status = false;
			}
			fclose($fh);
		}
		else{
			$this->errorArr[] = 'ERROR saving label format: unable opening/creating labeljson.php for writing';
			$status = false;
		}
		return $status;
	}

	private function updateCollectionJson($formatArr){
		$status = true;
		$sql = 'UPDATE omcollections SET dynamicProperties = "'.$this->conn->real_escape_string(json_encode($formatArr)).'" WHERE collid = '.$this->collid;
		if($this->conn->query($sql)) $this->setCollMetadata();
		else{
			$this->errorArr[] = 'ERROR saving label format to omcollections table: '.$this->conn->error;
			$status = false;
		}
		return $status;
	}

	private function updateUserJson($formatArr){
		$status = true;
		$sql = 'UPDATE users SET dynamicProperties = "'.$this->conn->real_escape_string(json_encode($formatArr)).'" WHERE uid = '.$GLOBALS['SYMB_UID'];
		if(!$this->conn->query($sql)){
			$this->errorArr[] = 'ERROR saving label format to users table: '.$this->conn->error;
			$status = false;
		}
		return $status;
	}

	//Annotation functions
	public function getAnnoArray($detidArr, $speciesAuthors){
		$retArr = array();
		if($detidArr){
			$authorArr = array();
			$sqlWhere = 'WHERE (d.detid IN('.implode(',',$detidArr).')) ';
			//Get species authors for infraspecific taxa
			$sql1 = 'SELECT d.detid, t2.author '.
				'FROM (taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted) '.
				'INNER JOIN omoccurdeterminations d ON o.occid = d.occid '.
				'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'INNER JOIN taxa t2 ON ts.parenttid = t2.tid '.
				$sqlWhere.' AND t.rankid > 220 AND ts.taxauthid = 1 ';
			if(!$speciesAuthors){
				$sql1 .= 'AND t.unitname2 = t.unitname3 ';
			}
			//echo $sql1; exit;
			if($rs1 = $this->conn->query($sql1)){
				while($row1 = $rs1->fetch_object()){
					$authorArr[$row1->detid] = $row1->author;
				}
				$rs1->free();
			}

			//Get determination records
			$sql2 = 'SELECT d.detid, d.identifiedBy, d.dateIdentified, d.sciname, d.scientificNameAuthorship, d.identificationQualifier, '.
				'd.identificationReferences, d.identificationRemarks, IFNULL(o.catalogNumber,o.otherCatalogNumbers) AS catalogNumber '.
				'FROM omoccurdeterminations d INNER JOIN omoccurrences o ON d.occid = o.occid '.$sqlWhere;
			//echo 'SQL: '.$sql2;
			if($rs2 = $this->conn->query($sql2)){
				while($row2 = $rs2->fetch_assoc()){
					$row2 = array_change_key_case($row2);
					if(array_key_exists($row2['detid'],$authorArr)){
						$row2['parentauthor'] = $authorArr[$row2['detid']];
					}
					$retArr[$row2['detid']] = $row2;
				}
				$rs2->free();
			}
		}
		return $retArr;
	}

	public function clearAnnoQueue($detidArr){
		$statusStr = '';
		if($detidArr){
			$sql = 'UPDATE omoccurdeterminations '.
				'SET printqueue = NULL '.
				'WHERE (detid IN('.implode(',',$detidArr).')) ';
			//echo $sql; exit;
			if($this->conn->query($sql)){
				$statusStr = 'Success!';
			}
		}
		return $statusStr;
	}

	public function getAnnoQueue(){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT o.occid, d.detid, CONCAT_WS(" ",o.recordedby,IFNULL(o.recordnumber,o.eventdate)) AS collector, '.
				'CONCAT_WS(" ",d.identificationQualifier,d.sciname) AS sciname, '.
				'CONCAT_WS(", ",d.identifiedBy,d.dateIdentified,d.identificationRemarks,d.identificationReferences) AS determination '.
				'FROM omoccurrences o INNER JOIN omoccurdeterminations d ON o.occid = d.occid '.
				'WHERE (o.collid = '.$this->collid.') AND (d.printqueue = 1) ';
			if($this->collArr['colltype'] == 'General Observations') $sql .= ' AND (o.observeruid = '.$GLOBALS['SYMB_UID'].') ';
			$sql .= 'LIMIT 400 ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->detid]['occid'] = $r->occid;
				$retArr[$r->detid]['detid'] = $r->detid;
				$retArr[$r->detid]['collector'] = $r->collector;
				$retArr[$r->detid]['sciname'] = $r->sciname;
				$retArr[$r->detid]['determination'] = $r->determination;
			}
			$rs->free();
		}
		return $retArr;
	}

	//General functions
	public function getLabelProjects(){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT DISTINCT labelproject FROM omoccurrences WHERE labelproject IS NOT NULL AND collid = '.$this->collid.' ';
			if($this->collArr['colltype'] == 'General Observations' && !array_key_exists('extendedsearch', $GLOBALS['_POST'])) $sql .= 'AND (observeruid = '.$GLOBALS['SYMB_UID'].') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[] = $r->labelproject;
			}
			sort($retArr);
			$rs->free();
		}
		return $retArr;
	}

	public function getDatasetProjects(){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT DISTINCT ds.datasetid, ds.name '.
				'FROM omoccurdatasets ds INNER JOIN userroles r ON ds.datasetid = r.tablepk '.
				'INNER JOIN omoccurdatasetlink dl ON ds.datasetid = dl.datasetid '.
				'INNER JOIN omoccurrences o ON dl.occid = o.occid '.
				'WHERE (r.tablename = "omoccurdatasets") AND (o.collid = '.$this->collid.') ';
			if($this->collArr['colltype'] == 'General Observations' && !array_key_exists('extendedsearch', $GLOBALS['_POST'])) $sql .= 'AND (o.observeruid = '.$GLOBALS['SYMB_UID'].') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->datasetid] = $r->name;
			}
			$rs->free();
		}
		return $retArr;
	}

	//General setters and getters
	public function setCollid($collid){
		if(is_numeric($collid)){
			$this->collid = $collid;
			$this->setCollMetadata();
		}
	}

	public function getCollid(){
		return $this->collid;
	}

	public function getCollName(){
		return $this->collArr['collname'].' ('.$this->collArr['instcode'].($this->collArr['collcode']?':'.$this->collArr['collcode']:'').')';
	}

	public function getAnnoCollName(){
		return $this->collArr['collname'].' ('.$this->collArr['instcode'].')';
	}

	public function getMetaDataTerm($key){
		if(!$this->collArr) return;
		if($this->collArr && array_key_exists($key,$this->collArr)){
			return $this->collArr[$key];
		}
	}

	private function setCollMetadata(){
		if($this->collid){
			$sql = 'SELECT institutioncode, collectioncode, collectionname, colltype, dynamicProperties FROM omcollections WHERE collid = '.$this->collid;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$this->collArr['instcode'] = $r->institutioncode;
					$this->collArr['collcode'] = $r->collectioncode;
					$this->collArr['collname'] = $r->collectionname;
					$this->collArr['colltype'] = $r->colltype;
					$this->collArr['dynprops'] = $r->dynamicProperties;
				}
				$rs->free();
			}
		}
	}

	public function getErrorArr(){
		return $this->errorArr;
	}

	//Internal cleaning functions
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>
