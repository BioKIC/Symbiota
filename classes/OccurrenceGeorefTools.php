<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class OccurrenceGeorefTools {

	private $conn;
	private $collid;
	private $collStr;
	private $qryVars = array();
	private $errorStr;

	function __construct($type = 'write') {
		$this->conn = MySQLiConnectionFactory::getCon($type);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getLocalityArr(){
		$retArr = array();
		if($this->collStr){
			$sql = 'SELECT occid, country, stateprovince, county, municipality, locality, verbatimcoordinates ,decimallatitude, decimallongitude '.
				'FROM omoccurrences WHERE (collid IN('.$this->collStr.')) AND (locality IS NOT NULL OR verbatimcoordinates IS NOT NULL) ';
			if(!$this->qryVars || !array_key_exists('qdisplayall',$this->qryVars) || !$this->qryVars['qdisplayall']){
				$sql .= 'AND (decimalLatitude IS NULL) ';
			}
			$orderBy = '';
			if($this->qryVars){
				if(array_key_exists('qsciname',$this->qryVars) && $this->qryVars['qsciname']){
					$sql .= 'AND (family = "'.$this->qryVars['qsciname'].'" OR sciname LIKE "'.$this->qryVars['qsciname'].'%") ';
				}
				if(array_key_exists('qvstatus',$this->qryVars)){
					$vs = $this->qryVars['qvstatus'];
					if(strtolower($vs) == 'is null'){
						$sql .= 'AND (georeferenceVerificationStatus IS NULL) ';
					}
					else{
						$sql .= 'AND (georeferenceVerificationStatus = "'.$vs.'") ';
					}
				}
				if(array_key_exists('qcountry',$this->qryVars) && $this->qryVars['qcountry']){
					$countySearch = $this->qryVars['qcountry'];
					$synArr = array('usa','u.s.a', 'united states','united states of america','u.s.');
					if(in_array($countySearch,$synArr)){
						$countySearch = implode('","',$synArr);
					}
					$sql .= 'AND (country IN("'.$countySearch.'")) ';
				}
				else{
					$orderBy .= 'country,';
				}
				if(array_key_exists('qstate',$this->qryVars) && $this->qryVars['qstate']){
					$sql .= 'AND (stateProvince = "'.$this->qryVars['qstate'].'") ';
				}
				else{
					$orderBy .= 'stateprovince,';
				}
				if(array_key_exists('qcounty',$this->qryVars) && $this->qryVars['qcounty']){
					$sql .= 'AND (county = "'.$this->qryVars['qcounty'].'") ';
				}
				else{
					$orderBy .= 'county,';
				}
				if(array_key_exists('qmunicipality',$this->qryVars) && $this->qryVars['qmunicipality']){
					$sql .= 'AND (municipality = "'.$this->qryVars['qmunicipality'].'") ';
				}
				else{
					$orderBy .= 'municipality,';
				}
				if(array_key_exists('qprocessingstatus',$this->qryVars) && $this->qryVars['qprocessingstatus']){
					$sql .= 'AND (processingstatus = "'.$this->qryVars['qprocessingstatus'].'") ';
				}
				if(array_key_exists('qlocality',$this->qryVars) && $this->qryVars['qlocality']){
					$sql .= 'AND (locality LIKE "%'.$this->qryVars['qlocality'].'%") ';
				}
			}
			$sql .= 'ORDER BY '.$orderBy.'locality,verbatimcoordinates ';
			//echo $sql;
			$totalCnt = 0;
			$locCnt = 1;
			$countryStr='';$stateStr='';$countyStr='';$municipalityStr='';$localityStr='';$verbCoordStr = '';$decLatStr='';$decLngStr='';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$localityStrNew = trim($r->locality ?? '',' .,;');
				$verbCoordStrNew = trim($r->verbatimcoordinates ?? '',' .,;');
				if($localityStrNew || $verbCoordStrNew){
					if($countryStr != trim($r->country ?? '') || $stateStr != trim($r->stateprovince ?? '') || $countyStr != trim($r->county ?? '') || $municipalityStr != trim($r->municipality ?? '')
						|| $localityStr != $localityStrNew || $verbCoordStr != $verbCoordStrNew || $decLatStr != $r->decimallatitude || $decLngStr != $r->decimallongitude){
						$countryStr = trim($r->country ?? '');
						$stateStr = trim($r->stateprovince ?? '');
						$countyStr = trim($r->county ?? '');
						$municipalityStr = trim($r->municipality ?? '');
						$localityStr = $localityStrNew;
						$verbCoordStr = $verbCoordStrNew;
						$decLatStr = $r->decimallatitude;
						$decLngStr = $r->decimallongitude;
						$totalCnt++;
						$retArr[$totalCnt]['occid'] = $r->occid;
						$retArr[$totalCnt]['country'] = $countryStr;
						$retArr[$totalCnt]['stateprovince'] = $stateStr;
						$retArr[$totalCnt]['county'] = $countyStr;
						$retArr[$totalCnt]['municipality'] = $municipalityStr;
						$retArr[$totalCnt]['locality'] = $localityStr;
						$retArr[$totalCnt]['verbatimcoordinates'] = $verbCoordStr;
						$retArr[$totalCnt]['decimallatitude'] = $decLatStr;
						$retArr[$totalCnt]['decimallongitude'] = $decLngStr;
						$retArr[$totalCnt]['cnt'] = 1;
						$locCnt = 1;
					}
					else{
						$locCnt++;
						$newOccidStr = $retArr[$totalCnt]['occid'].','.$r->occid;
						$retArr[$totalCnt]['occid'] = $newOccidStr;
						$retArr[$totalCnt]['cnt'] = $locCnt;
					}
				}
				if($totalCnt > 999) break;
			}
			$rs->free();
		}
		//usort($retArr,array('OccurrenceGeorefTools', '_cmpLocCnt'));
		return $retArr;
	}

	public function updateCoordinates($geoRefArr){
		if($this->collStr){
			if(is_numeric($geoRefArr['decimallatitude']) && is_numeric($geoRefArr['decimallongitude'])){
				set_time_limit(1000);
				$localStr =  $this->cleanInStr(implode(',',$geoRefArr['locallist']));
				unset($geoRefArr['locallist']);
				$geoRefArr = $this->cleanInArr($geoRefArr);
				if($localStr){
					//Update coordinates
					$this->addOccurEdits('decimallatitude',$geoRefArr['decimallatitude'],$localStr);
					$this->addOccurEdits('decimallongitude',$geoRefArr['decimallongitude'],$localStr);
					$this->addOccurEdits('georeferencedby',$geoRefArr['georeferencedby'],$localStr);
					$sql = 'UPDATE omoccurrences '.
						'SET decimallatitude = '.$geoRefArr['decimallatitude'].', decimallongitude = '.$geoRefArr['decimallongitude'].
						',georeferencedBy = "'.$geoRefArr['georeferencedby'].' ('.date('Y-m-d H:i:s').')'.'" ';
					if($geoRefArr['georeferenceverificationstatus']){
						$sql .= ',georeferenceverificationstatus = "'.$geoRefArr['georeferenceverificationstatus'].'" ';
						$this->addOccurEdits('georeferenceverificationstatus',$geoRefArr['georeferenceverificationstatus'],$localStr);
					}
					if($geoRefArr['georeferencesources']){
						$sql .= ',georeferencesources = "'.$geoRefArr['georeferencesources'].'" ';
						$this->addOccurEdits('georeferencesources',$geoRefArr['georeferencesources'],$localStr);
					}
					if($geoRefArr['georeferenceprotocol']){
						$sql .= ',georeferenceprotocol = "'.$geoRefArr['georeferenceprotocol'].'" ';
						$this->addOccurEdits('georeferenceprotocol',$geoRefArr['georeferenceprotocol'],$localStr);
					}
					if($geoRefArr['georeferenceremarks']){
						$sql .= ',georeferenceremarks = "'.$geoRefArr['georeferenceremarks'].'" ';
						$this->addOccurEdits('georeferenceremarks',$geoRefArr['georeferenceremarks'],$localStr);
					}
					if($geoRefArr['coordinateuncertaintyinmeters']){
						$sql .= ',coordinateuncertaintyinmeters = '.$geoRefArr['coordinateuncertaintyinmeters'];
						$this->addOccurEdits('coordinateuncertaintyinmeters',$geoRefArr['coordinateuncertaintyinmeters'],$localStr);
					}
					if($geoRefArr['footprintwkt']){
						$sql .= ',footprintwkt = "'.$geoRefArr['footprintwkt'].'" ';
						$this->addOccurEdits('footprintwkt',$geoRefArr['footprintwkt'],$localStr);
					}
					if($geoRefArr['geodeticdatum']){
						$sql .= ', geodeticdatum = "'.$geoRefArr['geodeticdatum'].'" ';
						$this->addOccurEdits('geodeticdatum',$geoRefArr['geodeticdatum'],$localStr);
					}
					if($geoRefArr['maximumelevationinmeters']){
						$sql .= ',maximumelevationinmeters = IF(minimumelevationinmeters IS NULL,'.$geoRefArr['maximumelevationinmeters'].',maximumelevationinmeters) ';
						$this->addOccurEdits('maximumelevationinmeters',$geoRefArr['maximumelevationinmeters'],$localStr);
					}
					if($geoRefArr['minimumelevationinmeters']){
						$sql .= ',minimumelevationinmeters = IF(minimumelevationinmeters IS NULL,'.$geoRefArr['minimumelevationinmeters'].',minimumelevationinmeters) ';
						$this->addOccurEdits('minimumelevationinmeters',$geoRefArr['minimumelevationinmeters'],$localStr);
					}
					if($geoRefArr['processingstatus']){
						$sql .= ',processingstatus = "'.$geoRefArr['processingstatus'].'" ';
						$this->addOccurEdits('processingstatus',$geoRefArr['processingstatus'],$localStr);
					}
					$sql .= ' WHERE (collid IN('.$this->collStr.')) AND (occid IN('.$localStr.'))';
					//echo $sql; exit;
					if(!$this->conn->query($sql)){
						$this->errorStr = 'ERROR batch updating coordinates: '.$this->conn->error;
						echo $this->errorStr;
					}
				}
			}
		}
	}

	private function addOccurEdits($fieldName, $fieldValue, $occidStr){
		//Temporary code needed for to test for new schema update
		$hasEditType = false;
		$rsTest = $this->conn->query('SHOW COLUMNS FROM omoccuredits WHERE field = "editType"');
		if($rsTest->num_rows) $hasEditType = true;
		$rsTest->free();

		$sql = 'INSERT INTO omoccuredits(occid, FieldName, FieldValueNew, FieldValueOld, appliedstatus, uid'.($hasEditType?',editType ':'').') '.
			'SELECT occid, "'.$fieldName.'", "'.$fieldValue.'", IFNULL('.$fieldName.',""), 1 as ap, '.$GLOBALS['SYMB_UID'].($hasEditType?',1 ':'').' FROM omoccurrences '.
			'WHERE (collid IN('.$this->collStr.')) AND (occid IN('.$occidStr.')) ';
		if(strpos($fieldName,'elevationinmeters')) $sql .= 'AND (minimumelevationinmeters IS NULL)';
		//echo $sql.';<br/>';
		if(!$this->conn->query($sql)){
			$this->errorStr = 'ERROR batch updating coordinates: '.$this->conn->error;
			echo $this->errorStr;
		}
	}

	public function getCoordStatistics(){
		$retArr = array();
		$totalCnt = 0;
		$sql = 'SELECT COUNT(*) AS cnt FROM omoccurrences WHERE (collid IN('.$this->collStr.'))';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$totalCnt = $r->cnt;
		}
		$rs->free();

		//Full count
		$sql2 = 'SELECT COUNT(occid) AS cnt FROM omoccurrences WHERE (collid IN('.$this->collStr.')) AND (decimalLatitude IS NULL) AND (georeferenceVerificationStatus IS NULL) ';
		if($rs2 = $this->conn->query($sql2)){
			if($r2 = $rs2->fetch_object()){
				$retArr['total'] = $r2->cnt;
				$retArr['percent'] = round($r2->cnt*100/$totalCnt,1);
			}
			$rs2->free();
		}

		return $retArr;
	}

	public function getGeorefClones($locality, $country, $state, $county, $searchType, $collStr){
		$occArr = array();
		$sql = 'SELECT count(o.occid) AS cnt, o.decimallatitude, o.decimallongitude, o.coordinateUncertaintyInMeters, o.georeferencedby, o.locality '.
			'FROM omoccurrences o ';
		$sqlWhere = 'WHERE (o.decimallatitude IS NOT NULL) AND (o.decimallongitude IS NOT NULL) ';
		if($collStr){
			$sqlWhere .= 'AND (o.collid IN('.$collStr.')) ';
		}
		if($searchType == 2){
			//Wildcard search
			$sql .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
			$sqlWhere .= 'AND (MATCH(f.locality) AGAINST(\'"'.$locality.'"\' IN BOOLEAN MODE)) ';
		}
		elseif($searchType == 3){
			//Deep search
			$sql .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
			$localArr = explode(' ', $locality);
			foreach($localArr as $str){
				$sqlWhere .= 'AND (MATCH(f.locality) AGAINST("'.$str.'")) ';
			}
		}
		else{
			//Exact search
			$sqlWhere .= 'AND o.locality = "'.trim($this->cleanInStr($locality) ?? '', " .").'" ';
		}
		if($country){
			$country = $this->cleanInStr($country);
			$synArr = array('usa','u.s.a', 'united states','united states of america','u.s.');
			if(in_array(strtolower($country),$synArr)) $country = implode('","',$synArr);
			$sqlWhere .= 'AND (o.country IN("'.$country.'")) ';
		}
		if($state){
			$sqlWhere .= 'AND (o.stateprovince = "'.$this->cleanInStr($state).'") ';
		}
		if($county){
			$county = str_ireplace(array(' county',' parish'),'',$county);
			$sqlWhere .= 'AND (o.county LIKE "'.$this->cleanInStr($county).'%") ';
		}
		$sql .= $sqlWhere;
		$sql .= 'GROUP BY o.decimallatitude, o.decimallongitude LIMIT 25';
		//echo '<div>'.$sql.'</div>'; exit;

		$rs = $this->conn->query($sql);
		$cnt = 0;
		while($r = $rs->fetch_object()){
			$occArr[$cnt]['cnt'] = $r->cnt;
			$occArr[$cnt]['lat'] = $r->decimallatitude;
			$occArr[$cnt]['lng'] = $r->decimallongitude;
			$occArr[$cnt]['err'] = $r->coordinateUncertaintyInMeters;
			//$occArr[$cnt]['footprint'] = $r->footprintWKT;
			//$occArr[$cnt]['country'] = $r->country;
			//$occArr[$cnt]['state'] = $r->stateprovince;
			//$occArr[$cnt]['county'] = $r->county;
			$occArr[$cnt]['georefby'] = $r->georeferencedby;
			$occArr[$cnt]['locality'] = $r->locality;
			$cnt++;
		}
		$rs->free();
		return $occArr;
	}

	//Setters and getters
	public function setCollId($collidStr){
		if(preg_match('/^[\d,]+$/',$collidStr)) $this->collStr = $collidStr;
		if(is_numeric($collidStr)) $this->collid = $collidStr;
	}

	public function getCollMap(){
		global $USER_RIGHTS, $IS_ADMIN;
		$retArr = Array();
		$rightArr = array();
		if(isset($USER_RIGHTS['CollAdmin'])) $rightArr = $USER_RIGHTS['CollAdmin'];
		if(isset($USER_RIGHTS['CollEditor'])) $rightArr = array_merge($rightArr, $USER_RIGHTS['CollEditor']);
		if($rightArr || $IS_ADMIN){
			$sql = 'SELECT collid, CONCAT_WS("-",institutioncode, collectioncode) AS code, collectionname, icon, colltype, managementtype FROM omcollections '.
				'WHERE ((colltype IN("Preserved Specimens","Observations")) ';
			if($rightArr) $sql .= 'AND (collid IN('.implode(',', $rightArr).')) ';
			$sql .= ') ';
			if($IS_ADMIN && $this->collStr) $sql .= 'OR (collid IN('.$this->collStr.')) ';
			$sql .= 'ORDER BY collectionname, collectioncode ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->collid]['code'] = $r->code;
				$retArr[$r->collid]['collectionname'] = $r->collectionname;
				$retArr[$r->collid]['icon'] = $r->icon;
				$retArr[$r->collid]['colltype'] = $r->colltype;
				$retArr[$r->collid]['managementtype'] = $r->managementtype;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function setQueryVariables($k,$v){
		$this->qryVars[$k] = $this->cleanInStr($v);
	}

	//Get data functions
	public function getCountryArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT country FROM omoccurrences WHERE decimalLatitude IS NULL AND collid IN('.$this->collStr.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$cStr = trim($r->country ?? '');
			if($cStr) $retArr[] = $cStr;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	public function getStateArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT stateprovince FROM omoccurrences WHERE decimalLatitude IS NULL AND collid IN('.$this->collStr.') ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$sStr = trim($r->stateprovince ?? '');
			if($sStr) $retArr[] = $sStr;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	public function getCountyArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT county FROM omoccurrences WHERE decimalLatitude IS NULL AND collid IN('.$this->collStr.') ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$cStr = trim($r->county ?? '');
			if($cStr) $retArr[] = $cStr;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	public function getMunicipalityArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT municipality FROM omoccurrences WHERE decimalLatitude IS NULL AND collid IN('.$this->collStr.') ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$mStr = trim($r->municipality ?? '');
			if($mStr) $retArr[] = $mStr;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	public function getProcessingStatus(){
		$retArr = array();
		$sql = 'SELECT DISTINCT processingstatus FROM omoccurrences WHERE decimalLatitude IS NULL AND collid IN('.$this->collStr.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->processingstatus) $retArr[] = $r->processingstatus;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	//Misc functions
	private function cleanInArr($arr){
		$retArr = array();
		foreach($arr as $k => $v){
			$retArr[$k] = $this->cleanInStr($v);
		}
		return $retArr;
	}
	private function cleanInStr($str){
		$newStr = trim($str ?? '');
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private static function _cmpLocCnt ($a, $b){
		$aCnt = $a['cnt'];
		$bCnt = $b['cnt'];
		if($aCnt == $bCnt){
			return 0;
		}
		return ($aCnt > $bCnt) ? -1 : 1;
	}
}
?>