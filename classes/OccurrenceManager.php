<?php
include_once($SERVER_ROOT.'/classes/OccurrenceSearchSupport.php');
include_once($SERVER_ROOT.'/classes/OccurrenceUtilities.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');
include_once($SERVER_ROOT.'/classes/OccurrenceTaxaManager.php');

class OccurrenceManager extends OccurrenceTaxaManager {

	protected $searchTermArr = Array();
	protected $sqlWhere;
	protected $displaySearchArr = Array();
	protected $reset = 0;
	private $voucherManager;
	private $occurSearchProjectExists = 0;
	protected $searchSupportManager = null;
	protected $errorMessage;

	public function __construct($type='readonly'){
		parent::__construct($type);
 		if(array_key_exists('reset',$_REQUEST) && $_REQUEST['reset'])  $this->reset();
		$this->readRequestVariables();
 	}

	public function __destruct(){
		parent::__destruct();
	}

	protected function getConnection($conType = 'readonly'){
		return MySQLiConnectionFactory::getCon($conType);
	}

	public function reset(){
 		$this->reset = 1;
		if(isset($this->searchTermArr['db']) || isset($this->searchTermArr['oic'])){
			//reset all other search terms except maintain the db terms
			$dbsTemp = '';
			if(isset($this->searchTermArr['db'])) $dbsTemp = $this->searchTermArr['db'];
			$clidTemp = '';
			if(isset($this->searchTermArr['clid'])) $clidTemp = $this->searchTermArr['clid'];
			unset($this->searchTermArr);
			if($dbsTemp) $this->searchTermArr['db'] = $dbsTemp;
			if($clidTemp) $this->searchTermArr['clid'] = $clidTemp;
		}
	}

	public function getSqlWhere(){
		if(!$this->sqlWhere) $this->setSqlWhere();
		return $this->sqlWhere;
	}

	protected function setSqlWhere(){
		$sqlWhere = '';
		if(array_key_exists("targetclid",$this->searchTermArr) && is_numeric($this->searchTermArr["targetclid"])){
			$voucherVariableArr = $this->voucherManager->getQueryVariableArr();
			if($voucherVariableArr){
				if(isset($voucherVariableArr['collid'])) $this->searchTermArr['db'] = $voucherVariableArr['collid'];
				if(isset($voucherVariableArr['country'])) $this->searchTermArr['country'] = $voucherVariableArr['country'];
				if(isset($voucherVariableArr['state'])) $this->searchTermArr['state'] = $voucherVariableArr['state'];
				if(isset($voucherVariableArr['county'])) $this->searchTermArr['county'] = $voucherVariableArr['county'];
				if(isset($voucherVariableArr['locality'])) $this->searchTermArr['local'] = $voucherVariableArr['locality'];
				if(isset($voucherVariableArr['recordedby'])) $this->searchTermArr['collector'] = $voucherVariableArr['recordedby'];
				if(isset($voucherVariableArr['taxon']) && !$this->taxaArr) $this->setTaxonRequestVariable(array('taxa'=>$voucherVariableArr['taxon'],'usethes'=>1));
				if(isset($voucherVariableArr['latsouth'])) $this->searchTermArr['llbound'] = $voucherVariableArr['latnorth'].';'.$voucherVariableArr['latsouth'].';'.$voucherVariableArr['lngwest'].';'.$voucherVariableArr['lngeast'];
				if(isset($voucherVariableArr['includewkt'])) $this->searchTermArr['footprintwkt'] = $this->voucherManager->getClFootprintWkt();
				if(!isset($voucherVariableArr['excludecult'])) $this->searchTermArr['includecult'] = 1;
				if(isset($voucherVariableArr['onlycoord'])){
					//Include details to limit to coordinates
					$this->displaySearchArr[] = 'Only include occurrences with coordinates';
					$sqlWhere .= 'AND (o.decimallatitude IS NOT NULL) ';
				}
			}
			if(array_key_exists('mode', $_REQUEST) && $_REQUEST['mode'] == 'voucher'){
				//Exclude vouchers already linked to target checklist
				$clOccidArr = array();
				if(isset($this->taxaArr['search']) && is_numeric($this->taxaArr['search'])){
					$sql = 'SELECT DISTINCT v.occid
						FROM fmvouchers v INNER JOIN fmchklsttaxalink ctl ON v.clTaxaID = ctl.clTaxaID
						INNER JOIN taxstatus ts ON ctl.tid = ts.tidaccepted
						INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted
						WHERE (ctl.clid = '.$this->searchTermArr['targetclid'].') AND (ctl.tid IN('.$this->taxaArr['search'].'))';
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						$clOccidArr[] = $r->occid;
					}
					$rs->free();
				}
				if($clOccidArr) $sqlWhere .= 'AND (o.occid NOT IN('.implode(',',$clOccidArr).')) ';
			}
			//$this->displaySearchArr[] = $this->voucherManager->getQueryVariableStr();
		}
		elseif(array_key_exists('clid',$this->searchTermArr) && preg_match('/^[0-9,]+$/', $this->searchTermArr['clid'])){
			if(isset($this->searchTermArr["cltype"]) && $this->searchTermArr["cltype"] == 'all'){
				$sqlWhere .= 'AND (cl.clid IN('.$this->searchTermArr['clid'].')) ';
			}
			else{
				$sqlWhere .= 'AND (ctl.clid IN('.$this->searchTermArr['clid'].')) ';
			}
			$this->displaySearchArr[] = 'Checklist ID: '.$this->searchTermArr['clid'];
		}
		elseif(array_key_exists('db',$this->searchTermArr) && $this->searchTermArr['db']){
			$sqlWhere .= OccurrenceSearchSupport::getDbWhereFrag($this->cleanInStr($this->searchTermArr['db']));
		}
		if(array_key_exists('datasetid',$this->searchTermArr)){
			$sqlWhere .= 'AND (d.datasetid IN('.$this->searchTermArr['datasetid'].')) ';
			$this->displaySearchArr[] = 'Dataset(s): '.$this->getDatasetTitle($this->searchTermArr['datasetid']);
		}
		$sqlWhere .= $this->getTaxonWhereFrag();
		if(array_key_exists('country',$this->searchTermArr)){
			$countryArr = explode(";",$this->searchTermArr["country"]);
			$tempArr = Array();
			foreach($countryArr as $k => $value){
				if($value == 'NULL'){
					$countryArr[$k] = 'Country IS NULL';
					$tempArr[] = '(o.Country IS NULL)';
				}
				else{
					$tempArr[] = '(o.Country = "'.$this->cleanInStr($value).'")';
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->displaySearchArr[] = implode(' OR ',$countryArr);
		}
		if(array_key_exists("state",$this->searchTermArr)){
			$stateAr = explode(";",$this->searchTermArr["state"]);
			$tempArr = Array();
			foreach($stateAr as $k => $value){
				if($value == 'NULL'){
					$tempArr[] = '(o.StateProvince IS NULL)';
					$stateAr[$k] = 'State IS NULL';
				}
				else{
					$tempArr[] = '(o.StateProvince = "'.$this->cleanInStr($value).'")';
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->displaySearchArr[] = implode(' OR ',$stateAr);
		}
		if(array_key_exists("county",$this->searchTermArr)){
			$countyArr = explode(";",$this->searchTermArr["county"]);
			$tempArr = Array();
			foreach($countyArr as $k => $value){
				if($value == 'NULL'){
					$tempArr[] = '(o.county IS NULL)';
					$countyArr[$k] = 'County IS NULL';
				}
				else{
					$term = $this->cleanInStr(trim(str_ireplace(' county',' ',$value),'%'));
					//if(strlen($term) < 4) $term .= ' ';
					$tempArr[] = '(o.county LIKE "'.$term.'%")';
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
			$this->displaySearchArr[] = implode(' OR ',$countyArr);
		}
		if(array_key_exists('local',$this->searchTermArr)){
			$localArr = explode(';',$this->searchTermArr['local']);
			$tempSqlArr = Array();
			$tempTermArr = Array();
			$fullTextArr = array();
			foreach($localArr as $k => $value){
				$value = trim($value);
				if($value == 'NULL'){
					$tempSqlArr[] = '(o.locality IS NULL)';
					$tempTermArr[] = 'Locality IS NULL';
				}
				else{
					$fullTextSearch = true;
					if(strlen($value) < 4) $fullTextSearch = false;
					elseif(strpos($value,' ')){
						$wordArr = explode(' ',$value);
						$fullTextSearch = false;
						foreach($wordArr as $w){
							if(strlen($w) > 3){
								$fullTextSearch = true;
								break;
							}
						}
					}
					if($fullTextSearch) $fullTextArr[] = $this->cleanInStr(str_replace('"', '', $value));
					else{
						$tempSqlArr[] = '(o.municipality LIKE "'.$this->cleanInStr($value).'%" OR o.Locality LIKE "%'.$this->cleanInStr($value).'%")';
						$tempTermArr[] = $value;
					}
					//if($fullTextSearch) $tempArr[] = '(MATCH(f.locality) AGAINST(\'"'.$this->cleanInStr(str_replace('"', '', $value)).'"\' IN BOOLEAN MODE)) ';
					//else $tempArr[] = '(o.municipality LIKE "'.$this->cleanInStr($value).'%" OR o.Locality LIKE "%'.$this->cleanInStr($value).'%")';
				}
			}
			if($fullTextArr){
				if(count($fullTextArr) == 1){
					$searchTerm = array_pop($fullTextArr);
					$tempSqlArr[] = '(MATCH(f.locality) AGAINST(\'"'.$searchTerm.'"\' IN BOOLEAN MODE)) ';
					$this->displaySearchArr[] = $searchTerm;
				}
				else{
					$tempSqlArr[] = '(MATCH(f.locality) AGAINST("'.implode(' ',$fullTextArr).'")) ';
					$this->displaySearchArr[] = implode(' OR ',$fullTextArr);
				}
			}
			$sqlWhere .= 'AND ('.implode(' OR ',$tempSqlArr).') ';
			if($tempTermArr) $this->displaySearchArr[] = implode(' OR ',$tempTermArr);
		}
		if(array_key_exists("elevlow",$this->searchTermArr) || array_key_exists("elevhigh",$this->searchTermArr)){
			$elevlow = -200;
			$elevhigh = 9000;
			if(array_key_exists("elevlow",$this->searchTermArr)) $elevlow = $this->searchTermArr["elevlow"];
			if(array_key_exists("elevhigh",$this->searchTermArr)) $elevhigh = $this->searchTermArr["elevhigh"];
			$sqlWhere .= 'AND (( minimumElevationInMeters >= '.$elevlow.' AND maximumElevationInMeters <= '.$elevhigh.' ) OR ' .
				'( maximumElevationInMeters is null AND minimumElevationInMeters >= '.$elevlow.' AND minimumElevationInMeters <= '.$elevhigh.' )) ';
			$this->displaySearchArr[] = 'Elev: '.$elevlow.($elevhigh?' - '.$elevhigh:'');
		}
		if(array_key_exists("llbound",$this->searchTermArr)){
			$llboundArr = explode(";",$this->searchTermArr["llbound"]);
			if(count($llboundArr) == 4){
				$uLat = $llboundArr[0];
				$bLat = $llboundArr[1];
				$lLng = $llboundArr[2];
				$rLng = $llboundArr[3];
				//$sqlWhere .= 'AND (o.DecimalLatitude BETWEEN '.$llboundArr[1].' AND '.$llboundArr[0].' AND o.DecimalLongitude BETWEEN '.$llboundArr[2].' AND '.$llboundArr[3].') ';
				$sqlWhere .= 'AND (ST_Within(p.point,GeomFromText("POLYGON(('.$uLat.' '.$rLng.','.$bLat.' '.$rLng.','.$bLat.' '.$lLng.','.$uLat.' '.$lLng.','.$uLat.' '.$rLng.'))"))) ';
				$this->displaySearchArr[] = 'Lat: '.$llboundArr[1].' - '.$llboundArr[0].' Long: '.$llboundArr[2].' - '.$llboundArr[3];
			}
		}
		elseif(array_key_exists("llpoint",$this->searchTermArr)){
			$pointArr = explode(";",$this->searchTermArr["llpoint"]);
			if(count($pointArr) == 4){
				$lat = $pointArr[0];
				$lng = $pointArr[1];
				$radius = $pointArr[2];
				if($pointArr[3] == 'km') $radius *= 0.6214;

				//Formulate bounding box to carete an approximate return
				$latRadius = $radius / 69.1;
				$longRadius = cos($pointArr[0]/57.3)*($radius/69.1);
				$lat1 = $pointArr[0] - $latRadius;
				$lat2 = $pointArr[0] + $latRadius;
				$long1 = $pointArr[1] - $longRadius;
				$long2 = $pointArr[1] + $longRadius;
				$sqlWhere .= 'AND o.occid IN(SELECT occid FROM omoccurrences WHERE (DecimalLatitude BETWEEN '.$lat1.' AND '.$lat2.') AND (DecimalLongitude BETWEEN '.$long1.' AND '.$long2.')) ';
				//Add a more percise circular definition that will run on bounding box points
				$sqlWhere .= 'AND (( 3959 * acos( cos( radians('.$lat.') ) * cos( radians( o.DecimalLatitude ) ) * cos( radians( o.DecimalLongitude )'.
						' - radians('.$lng.') ) + sin( radians('.$lat.') ) * sin(radians(o.DecimalLatitude)) ) ) < '.$radius.') ';
				/*
				if($this->hasFullSpatialSupport()){

				}
				else{

				}
				*/
			}
			$this->displaySearchArr[] = $pointArr[0]." ".$pointArr[1]." +- ".$pointArr[2].$pointArr[3];
		}
		elseif(array_key_exists('footprintwkt',$this->searchTermArr)){
			$sqlWhere .= 'AND (ST_Within(p.point,GeomFromText("'.$this->searchTermArr['footprintwkt'].'"))) ';
			$this->displaySearchArr[] = 'Polygon search (not displayed)';
		}
		if(array_key_exists('collector',$this->searchTermArr)){
			$collectorArr = explode(';',$this->searchTermArr['collector']);
			$tempCollSqlArr = Array();
			$tempCollTextArr = Array();
			if(count($collectorArr) == 1 && $collectorArr[0] == 'NULL'){
				$tempCollSqlArr[] = '(o.recordedBy IS NULL)';
				$tempCollTextArr[] = 'Collector IS NULL';
			}
			else{
				$fullCollArr = array();
				foreach($collectorArr AS $collStr){
					if(strlen($collStr) == 2 || strlen($collStr) == 3 || in_array(strtolower($collStr),array('best','little'))){
						//Need to avoid FULLTEXT stopwords interfering with return
						$tempCollSqlArr[] = '(o.recordedBy LIKE "%'.$this->cleanInStr($collStr).'%")';
						$tempCollTextArr[] = $collStr;
					}
					else{
						$fullCollArr[] = $this->cleanInStr(str_replace('"','',$collStr));
						//$tempArr[] = '(MATCH(f.recordedby) AGAINST("'.$this->cleanInStr($collStr).'")) ';
					}
				}
				if(count($fullCollArr) == 1){
					$collTerm = array_pop($fullCollArr);
					$tempCollSqlArr[] = '(MATCH(f.recordedby) AGAINST(\'"'.$collTerm.'"\' IN BOOLEAN MODE)) ';
					$tempCollTextArr[] = $collTerm;
				}
				else{
					$tempCollSqlArr[] = '(MATCH(f.recordedby) AGAINST("'.implode(' ',$fullCollArr).'")) ';
					$tempCollTextArr = array_merge($tempCollTextArr, explode(' ',implode(' ',$fullCollArr)));
				}
			}
			if($tempCollSqlArr) $sqlWhere .= 'AND ('.implode(' OR ',$tempCollSqlArr).') ';
			$this->displaySearchArr[] = implode(' OR ',$tempCollTextArr);
		}
		if(array_key_exists("collnum",$this->searchTermArr)){
			$collNumArr = explode(";",$this->cleanInStr($this->searchTermArr["collnum"]));
			$rnWhere = '';
			foreach($collNumArr as $v){
				$v = trim($v);
				if($p = strpos($v,' - ')){
					$term1 = trim(substr($v,0,$p));
					$term2 = trim(substr($v,$p+3));
					if(is_numeric($term1) && is_numeric($term2)){
						$rnWhere .= 'OR (o.recordnumber BETWEEN '.$term1.' AND '.$term2.')';
					}
					else{
						if(strlen($term2) > strlen($term1)) $term1 = str_pad($term1,strlen($term2),"0",STR_PAD_LEFT);
						$catTerm = '(o.recordnumber BETWEEN "'.$term1.'" AND "'.$term2.'")';
						$catTerm .= ' AND (length(o.recordnumber) <= '.strlen($term2).')';
						$rnWhere .= 'OR ('.$catTerm.')';
					}
				}
				else{
					$rnWhere .= 'OR (o.recordNumber = "'.$v.'") ';
				}
			}
			if($rnWhere){
				$sqlWhere .= "AND (".substr($rnWhere,3).") ";
				$this->displaySearchArr[] = implode(", ",$collNumArr);
			}
		}
		if(array_key_exists('eventdate1',$this->searchTermArr)){
			$dateArr = array();
			if(strpos($this->searchTermArr['eventdate1'],' to ')){
				$dateArr = explode(' to ',$this->searchTermArr['eventdate1']);
			}
			elseif(strpos($this->searchTermArr['eventdate1'],' - ')){
				$dateArr = explode(' - ',$this->searchTermArr['eventdate1']);
			}
			else{
				$dateArr[] = $this->searchTermArr['eventdate1'];
				if(isset($this->searchTermArr['eventdate2'])){
					$dateArr[] = $this->searchTermArr['eventdate2'];
				}
			}
			if($dateArr[0] == 'NULL'){
				$sqlWhere .= 'AND (o.eventdate IS NULL) ';
				$this->displaySearchArr[] = 'Date IS NULL';
			}
			elseif($eDate1 = $this->cleanInStr($this->formatDate($dateArr[0]))){
				$eDate2 = (count($dateArr)>1?$this->cleanInStr($this->formatDate($dateArr[1])):'');
				if($eDate2){
					if(substr($eDate2,-6) == '-00-00') $eDate2 = str_replace('-00-00', '-12-31', $eDate2);
					elseif(preg_match('/-(\d{2})-00$/', $eDate2, $m)){
						$day = '00';
						$month = $m[1];
						if($month == 12) $day = '31';
						else $month++;
						if(strlen($month) == 1) $month = '0'.$month;
						$eDate2 = substr($eDate2,0,4).'-'.$month.'-'.$day;
					}
					$sqlWhere .= 'AND (o.eventdate BETWEEN "'.$eDate1.'" AND "'.$eDate2.'") ';
				}
				else{
					if(substr($eDate1,-5) == '00-00'){
						$sqlWhere .= 'AND (o.eventdate LIKE "'.substr($eDate1,0,5).'%") ';
					}
					elseif(substr($eDate1,-2) == '00'){
						$sqlWhere .= 'AND (o.eventdate LIKE "'.substr($eDate1,0,8).'%") ';
					}
					else{
						$sqlWhere .= 'AND (o.eventdate = "'.$eDate1.'") ';
					}
				}
				$this->displaySearchArr[] = $this->searchTermArr['eventdate1'].(isset($this->searchTermArr['eventdate2'])?' to '.$this->searchTermArr['eventdate2']:'');
			}
		}
		if(array_key_exists('catnum',$this->searchTermArr)){
			$catStr = $this->cleanInStr($this->searchTermArr['catnum']);
			$includeOtherCatNum = array_key_exists('includeothercatnum',$this->searchTermArr)?true:false;

			$catArr = explode(',',str_replace(';',',',$catStr));
			$betweenFrag = array();
			$inFrag = array();
			$identFrag = array();
			foreach($catArr as $v){
				if($p = strpos($v,' - ')){
					$term1 = trim(substr($v,0,$p));
					$term2 = trim(substr($v,$p+3));
					if(is_numeric($term1) && is_numeric($term2)){
						$betweenFrag[] = '(o.catalogNumber BETWEEN '.$term1.' AND '.$term2.')';
						if($includeOtherCatNum){
							$betweenFrag[] = '(o.othercatalognumbers BETWEEN '.$term1.' AND '.$term2.')';
							//$betweenFrag[] = '(oi.identifiervalue BETWEEN '.$term1.' AND '.$term2.')';
							$identFrag[] = '(identifiervalue BETWEEN '.$term1.' AND '.$term2.')';
						}
					}
					else{
						$catTerm = 'o.catalogNumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
						if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(o.catalogNumber) = '.strlen($term2);
						$betweenFrag[] = '('.$catTerm.')';
						if($includeOtherCatNum){
							$betweenFrag[] = '(o.othercatalognumbers BETWEEN "'.$term1.'" AND "'.$term2.'")';
							//$betweenFrag[] = '(oi.identifiervalue BETWEEN "'.$term1.'" AND "'.$term2.'")';
							$identFrag[] = '(identifiervalue BETWEEN "'.$term1.'" AND "'.$term2.'")';
						}
					}
				}
				else{
					$vStr = trim($v);
					$inFrag[] = $vStr;
					if(is_numeric($vStr) && substr($vStr,0,1) == '0'){
						$inFrag[] = ltrim($vStr,0);
					}
				}
			}
			$catWhere = '';
			if($betweenFrag){
				$catWhere .= 'OR '.implode(' OR ',$betweenFrag);
			}
			if($inFrag){
				$catWhere .= 'OR (o.catalogNumber IN("'.implode('","',$inFrag).'")) ';
				if($includeOtherCatNum){
					$catWhere .= 'OR (o.othercatalognumbers IN("'.implode('","',$inFrag).'")) ';
					$catWhere .= 'OR (o.occurrenceID IN("'.implode('","',$inFrag).'")) ';
					$catWhere .= 'OR (o.recordID IN("'.implode('","',$inFrag).'")) ';
					//$catWhere .= 'OR (oi.identifiervalue IN("'.implode('","',$inFrag).'")) ';
					$identFrag[] = '(identifiervalue IN("'.implode('","',$inFrag).'"))';
				}
			}
			if($identFrag){
				$occidList = $this->getAdditionIdentifiers($identFrag);
				if($occidList) $catWhere .= 'OR (o.occid IN('.implode(',',$occidList).')) ';
			}
			$sqlWhere .= 'AND ('.substr($catWhere,3).') ';
			$this->displaySearchArr[] = $this->searchTermArr['catnum'];
		}
		if(array_key_exists("typestatus",$this->searchTermArr)){
			$sqlWhere .= "AND (o.typestatus IS NOT NULL) ";
			$this->displaySearchArr[] = 'is type';
		}
		if(array_key_exists("hasimages",$this->searchTermArr)){
			$sqlWhere .= "AND (o.occid IN(SELECT occid FROM images)) ";
			$this->displaySearchArr[] = 'has images';
		}
		if(array_key_exists("hasgenetic",$this->searchTermArr)){
			$sqlWhere .= "AND (o.occid IN(SELECT occid FROM omoccurgenetic)) ";
			$this->displaySearchArr[] = 'has genetic data';
		}
		if(array_key_exists("hascoords",$this->searchTermArr)){
			$sqlWhere .= "AND (o.decimalLatitude IS NOT NULL) ";
			$this->displaySearchArr[] = 'has geocoordinates';
		}
		if($sqlWhere){
			if(!array_key_exists("includecult",$this->searchTermArr)){
				$sqlWhere .= "AND (o.cultivationStatus IS NULL OR o.cultivationStatus = 0) ";
				$this->displaySearchArr[] = 'excluding cultivated/captive occurrences';
			}
			else{
				$this->displaySearchArr[] = 'includes cultivated/captive occurrences';
			}
		}
		if(array_key_exists('attr',$this->searchTermArr)){
			$traitNameSql = 'SELECT t.traitName, s.stateName FROM tmtraits t JOIN tmstates s ON s.traitid = t.traitid WHERE s.stateid IN(' . $this->searchTermArr['attr'] . ')';
			$rs = $this->conn->query($traitNameSql);
			if($rs){
				$traitArr = array();
				while($r = $rs->fetch_object()) {
					$traitArr[$r->traitName][] = $r->stateName;
				}
				$rs->free();
				$displayStr = '';
				foreach($traitArr as $traitName => $stateName){
					$displayStr .= $traitName.': '.implode(', ',$stateName).'; ';
				}
				$this->displaySearchArr[] = trim($displayStr,'; ');
			}
			$sqlWhere .= 'AND (o.occid IN(SELECT occid FROM tmattributes WHERE stateid IN(' . $this->searchTermArr['attr'] . '))) ';
		}

		if($sqlWhere) $this->sqlWhere = 'WHERE '.substr($sqlWhere,4);
		else{
			//Make the sql valid, but return nothing
			//$this->sqlWhere = 'WHERE o.occid IS NULL ';
		}
		//echo $this->sqlWhere;
	}

	private function getAdditionIdentifiers($identFrag){
		$retArr = array();
		if($identFrag){
			$sql = 'SELECT occid FROM omoccuridentifiers WHERE '.implode(' OR ',$identFrag);
			$rs = $this->conn->query($sql);
			if($rs){
				while($r = $rs->fetch_object()){
					$retArr[] = $r->occid;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	protected function formatDate($inDate){
		$retDate = OccurrenceUtilities::formatDate($inDate);
		return $retDate;
	}

	protected function getTableJoins($sqlWhere){
		$sqlJoin = '';
		if(array_key_exists('clid',$this->searchTermArr) && $this->searchTermArr['clid']){
			if(strpos($sqlWhere,'ctl.clid')){
				$sqlJoin .= 'INNER JOIN fmvouchers v ON o.occid = v.occid INNER JOIN fmchklsttaxalink ctl ON v.clTaxaID = ctl.clTaxaID ';
			}
			else{
				$sqlJoin .= 'INNER JOIN fmchklsttaxalink cl ON o.tidinterpreted = cl.tid ';
			}
		}
		if(strpos($sqlWhere,'MATCH(f.recordedby)') || strpos($sqlWhere,'MATCH(f.locality)')){
			$sqlJoin .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
		}
		if(strpos($sqlWhere,'e.taxauthid')){
			$sqlJoin .= 'INNER JOIN taxaenumtree e ON o.tidinterpreted = e.tid ';
		}
		if(strpos($sqlWhere,'ts.family')){
			$sqlJoin .= 'LEFT JOIN taxstatus ts ON o.tidinterpreted = ts.tid ';
		}
		if(strpos($sqlWhere,'d.datasetid')){
			$sqlJoin .= 'INNER JOIN omoccurdatasetlink d ON o.occid = d.occid ';
		}
		if(array_key_exists('polycoords',$this->searchTermArr) || strpos($sqlWhere,'p.point')){
			$sqlJoin .= 'INNER JOIN omoccurpoints p ON o.occid = p.occid ';
		}
		/*
		if(array_key_exists('includeothercatnum',$this->searchTermArr)){
			$sqlJoin .= 'LEFT JOIN omoccuridentifiers oi ON o.occid = oi.occid ';
		}
		*/
		return $sqlJoin;
	}

	public function getFullCollectionList($catId = ''){
		if(!$this->searchSupportManager) $this->searchSupportManager = new OccurrenceSearchSupport($this->conn);
		if(isset($this->searchTermArr['db'])) $this->searchSupportManager->setCollidStr($this->searchTermArr['db']);
		return $this->searchSupportManager->getFullCollectionList($catId);
	}

	public function outputFullCollArr($collGrpArr, $targetCatID = 0, $displayIcons = true, $displaySearchButtons = true){
		if(!$this->searchSupportManager) $this->searchSupportManager = new OccurrenceSearchSupport($this->conn);
		$this->searchSupportManager->outputFullCollArr($collGrpArr, $targetCatID, $displayIcons, $displaySearchButtons);
	}

	public function getOccurVoucherProjects(){
		$retArr = Array();
		$titleArr = Array();
		$sql = 'SELECT p2.pid AS parentpid, p2.projname as catname, p1.pid, p1.projname, c.clid, c.name as clname '.
			'FROM fmprojects p1 INNER JOIN fmprojects p2 ON p1.parentpid = p2.pid '.
			'INNER JOIN fmchklstprojlink cl ON p1.pid = cl.pid '.
			'INNER JOIN fmchecklists c ON cl.clid = c.clid '.
			'WHERE p2.occurrencesearch = 1 AND p1.ispublic = 1 ';
		//echo "<div>$sql</div>";
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if(!isset($titleArr['cat'][$r->parentpid])) $titleArr['cat'][$r->parentpid] = $r->catname;
			if(!isset($titleArr['proj'][$r->pid])) $titleArr[$r->parentpid]['proj'][$r->pid] = $r->projname;
			$retArr[$r->pid][$r->clid] = $r->clname;
		}
		$rs->free();
		if($titleArr) $retArr['titles'] = $titleArr;
		return $retArr;
	}

	public function getCollectionSearchStr(){
		$retStr ="";
		if(!array_key_exists('db',$this->searchTermArr) || $this->searchTermArr['db'] == 'all'){
			$retStr = "All Collections";
		}
		elseif($this->searchTermArr['db'] == 'allspec'){
			$retStr = "All Specimen Collections";
		}
		elseif($this->searchTermArr['db'] == 'allobs'){
			$retStr = "All Observation Projects";
		}
		else{
			$cArr = explode(';',$this->cleanInStr($this->searchTermArr['db']));
			if($cArr[0]){
				$sql = 'SELECT collid, CONCAT_WS("-",institutioncode,collectioncode) as instcode '.
					'FROM omcollections WHERE collid IN('.$cArr[0].') ORDER BY institutioncode,collectioncode';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$retStr .= '; '.$r->instcode;
				}
				$rs->free();
			}
			/*
			if(isset($cArr[1]) && $cArr[1]){
				$sql = 'SELECT ccpk, category FROM omcollcategories WHERE ccpk IN('.$cArr[1].') ORDER BY category';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$retStr .= '; '.$r->category;
				}
				$rs->free();
			}
			*/
			$retStr = substr($retStr,2);
		}
		return $retStr;
	}

	public function getLocalSearchStr(){
		return implode("; ", $this->displaySearchArr);
	}

	protected function setSearchTerm($termKey, $termValue){
		$this->searchTermArr[$termKey] = $this->cleanInputStr($termValue);
	}

	public function getSearchTerm($k){
		if($k && isset($this->searchTermArr[$k])){
			return trim($this->searchTermArr[$k],' ;');
		}
		return '';
	}

	public function getQueryTermStr(){
		//Returns a search variable string
		$retStr = '';
		foreach($this->searchTermArr as $k => $v){
			if(is_array($v)) $v = implode(',', $v);
			$retStr .= '&'.$k.'='.urlencode($v);
		}
		if(isset($this->taxaArr['search'])){
			$retStr .= '&taxa='.urlencode($this->taxaArr['search']);
			if($this->taxaArr['usethes']) $retStr .= '&usethes=1';
			$retStr .= '&taxontype='.$this->taxaArr['taxontype'];
		}
		return trim($retStr,' &');
	}

	public function addOccurrencesToDataset($datasetID){
		if(!is_numeric($datasetID)) return false;
		$this->setSqlWhere();
		$sql = 'INSERT IGNORE INTO omoccurdatasetlink(occid,datasetid) SELECT DISTINCT o.occid, '.$datasetID.' as dsID FROM omoccurrences o '.$this->getTableJoins($this->sqlWhere).$this->sqlWhere;
		if(!$this->conn->query($sql)){
			$this->errorMessage = 'ERROR adding records to dataset(#'.$datasetID.'): '.$this->conn->error;
			return false;
		}
		return true;
	}

	private function getDatasetTitle($dsIdStr){
		$retStr = '';
		$sql = 'SELECT name FROM omoccurdatasets WHERE datasetid IN('.$dsIdStr.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retStr .= ', '.$r->name;
		}
		$rs->free();
		return trim($retStr,', ');
	}

	protected function readRequestVariables(){
		if(array_key_exists('searchvar',$_REQUEST)){
			$parsedArr = array();
			$taxaArr = array();
			parse_str($this->cleanInputStr($_REQUEST['searchvar']), $parsedArr);
			if(isset($parsedArr['taxa'])){
				$taxaArr['taxa'] = $parsedArr['taxa'];
				unset($parsedArr['taxa']);
				if(isset($parsedArr['usethes']) && is_numeric($parsedArr['usethes'])){
					$taxaArr['usethes'] = $parsedArr['usethes'];
					unset($parsedArr['usethes']);
				}
				if(isset($parsedArr['taxontype']) && is_numeric($parsedArr['taxontype'])){
					$taxaArr['taxontype'] = $parsedArr['taxontype'];
					unset($parsedArr['taxontype']);
				}
				$this->setTaxonRequestVariable($taxaArr);
			}
			if($parsedArr) $this->searchTermArr = $parsedArr;
		}
		//Search will be confinded to a clid vouchers, collid, catid, or will remain open to all collection
		if(array_key_exists('targetclid',$_REQUEST) && is_numeric($_REQUEST['targetclid'])){
			$this->searchTermArr['targetclid'] = $_REQUEST['targetclid'];
			$this->setChecklistVariables($_REQUEST['targetclid']);
		}
		elseif(array_key_exists('clid',$_REQUEST)){
			//Limit by checklist voucher links
			$clidIn = $_REQUEST['clid'];
			$clidStr = '';
			if(is_string($clidIn)) $clidStr = $clidIn;
			else $clidStr = implode(',',array_unique($clidIn));
			if(!preg_match('/^[0-9,]+$/', $clidStr)) $clidStr = '';
			$this->setChecklistVariables($clidStr);
			$this->searchTermArr['clid'] = $clidStr;
		}
		elseif(array_key_exists('db',$_REQUEST) && $_REQUEST['db']){
			$dbStr = $this->cleanInputStr(OccurrenceSearchSupport::getDbRequestVariable($_REQUEST));
			if(preg_match('/^[0-9,;]+$/', $dbStr)) $this->searchTermArr['db'] = $dbStr;
		}
		if(array_key_exists('datasetid',$_REQUEST) && $_REQUEST['datasetid']){
			if(is_array($_REQUEST['datasetid'])){
				$dsStr = implode(',',$_REQUEST['datasetid']);
				if(preg_match('/^[\d,]+$/',$dsStr)) $this->searchTermArr['datasetid'] = $dsStr;
			}
			elseif(preg_match('/^[\d,]+$/',$_REQUEST['datasetid'])) $this->searchTermArr['datasetid'] = $_REQUEST['datasetid'];
		}
		if(array_key_exists('taxa',$_REQUEST) && $_REQUEST['taxa']){
			$this->setTaxonRequestVariable();
		}
		if(array_key_exists('country',$_REQUEST)){
			$country = $this->cleanInputStr($_REQUEST['country']);
			if($country){
				$str = str_replace(',',';',$country);
				if(stripos($str, 'USA') !== false || stripos($str, 'United States') !== false || stripos($str, 'U.S.A.') !== false || stripos($str, 'United States of America') !== false){
					if(stripos($str, 'USA') === false){
						$str .= ';USA';
					}
					if(stripos($str, 'United States') === false){
						$str .= ';United States';
					}
					if(stripos($str, 'U.S.A.') === false){
						$str .= ';U.S.A.';
					}
					if(stripos($str, 'United States of America') === false){
						$str .= ';United States of America';
					}
				}
				$this->searchTermArr['country'] = $str;
			}
			else unset($this->searchTermArr['country']);
		}
		if(array_key_exists('state',$_REQUEST)){
			$state = $this->cleanInputStr($_REQUEST['state']);
			if($state){
				if(strlen($state) == 2 && (!isset($this->searchTermArr['country']) || stripos($this->searchTermArr['country'],'USA') !== false)){
					$sql = 'SELECT s.statename, c.countryname '.
						'FROM lkupstateprovince s INNER JOIN lkupcountry c ON s.countryid = c.countryid '.
						'WHERE c.countryname IN("USA","United States") AND (s.abbrev = "'.$state.'")';
					$rs = $this->conn->query($sql);
					if($r = $rs->fetch_object()){
						$state = $r->statename;
					}
					$rs->free();
				}
				$str = str_replace(',',';',$state);
				$this->searchTermArr['state'] = $str;
			}
			else{
				unset($this->searchTermArr['state']);
			}
		}
		if(array_key_exists('county',$_REQUEST)){
			$county = $this->cleanInputStr($_REQUEST['county']);
			$county = str_ireplace(' Co.','',$county);
			$county = str_ireplace(' County','',$county);
			if($county){
				$str = str_replace(',',';',$county);
				$this->searchTermArr['county'] = $str;
			}
			else{
				unset($this->searchTermArr['county']);
			}
		}
		if(array_key_exists('local',$_REQUEST)){
			$local = $this->cleanInputStr($_REQUEST['local']);
			if($local){
				$str = str_replace(',',';',$local);
				$this->searchTermArr['local'] = $str;
			}
			else{
				unset($this->searchTermArr['local']);
			}
		}
		if(array_key_exists('elevlow',$_REQUEST)){
			$elevLow = filter_var(trim($_REQUEST['elevlow']), FILTER_SANITIZE_NUMBER_INT);
			if(is_numeric($elevLow)) $this->searchTermArr['elevlow'] = $elevLow;
			else unset($this->searchTermArr['elevlow']);
		}
		if(array_key_exists('elevhigh',$_REQUEST)){
			$elevHigh = filter_var(trim($_REQUEST['elevhigh']), FILTER_SANITIZE_NUMBER_INT);
			if(is_numeric($elevHigh)) $this->searchTermArr['elevhigh'] = $elevHigh;
			else unset($this->searchTermArr['elevhigh']);
		}
		if(array_key_exists('collector',$_REQUEST)){
			$collector = $this->cleanInputStr($_REQUEST['collector']);
			$collector = str_replace('%', '', $collector);
			if($collector){
				$str = str_replace(',',';',$collector);
				$this->searchTermArr['collector'] = $str;
			}
			else{
				unset($this->searchTermArr['collector']);
			}
		}
		if(array_key_exists('collnum',$_REQUEST)){
			$collNum = $this->cleanInputStr($_REQUEST['collnum']);
			if($collNum){
				$str = str_replace(',',';',$collNum);
				$this->searchTermArr['collnum'] = $str;
			}
			else{
				unset($this->searchTermArr['collnum']);
			}
		}
		if(array_key_exists('eventdate1',$_REQUEST)){
			if($eventDate = $this->cleanInputStr($_REQUEST['eventdate1'])){
				$this->searchTermArr['eventdate1'] = $eventDate;
				if(array_key_exists('eventdate2',$_REQUEST)){
					if($eventDate2 = $this->cleanInputStr($_REQUEST['eventdate2'])){
						if($eventDate2 != $eventDate){
							$this->searchTermArr['eventdate2'] = $eventDate2;
						}
					}
					else{
						unset($this->searchTermArr['eventdate2']);
					}
				}
			}
			else{
				unset($this->searchTermArr['eventdate1']);
			}
		}
		if(array_key_exists('catnum',$_REQUEST)){
			$catNum = $this->cleanInputStr(str_replace(',', ';', $_REQUEST['catnum']));
			if($catNum){
				$this->searchTermArr['catnum'] = $catNum;
				if(array_key_exists('includeothercatnum',$_REQUEST)) $this->searchTermArr['includeothercatnum'] = '1';
			}
			else{
				unset($this->searchTermArr['catnum']);
			}
		}
		if(array_key_exists('typestatus',$_REQUEST)){
			if($_REQUEST['typestatus']) $this->searchTermArr['typestatus'] = true;
			else unset($this->searchTermArr['typestatus']);
		}
		if(array_key_exists('hasimages',$_REQUEST)){
			if($_REQUEST['hasimages']) $this->searchTermArr['hasimages'] = true;
			else unset($this->searchTermArr['hasimages']);
		}
		if(array_key_exists('hasgenetic',$_REQUEST)){
			if($_REQUEST['hasgenetic']) $this->searchTermArr['hasgenetic'] = true;
			else unset($this->searchTermArr['hasgenetic']);
		}
		if(array_key_exists('hascoords',$_REQUEST)){
			if($_REQUEST['hascoords']) $this->searchTermArr['hascoords'] = true;
			else unset($this->searchTermArr['hascoords']);
		}
		if(array_key_exists('includecult',$_REQUEST)){
			if($_REQUEST['includecult']) $this->searchTermArr['includecult'] = true;
			else unset($this->searchTermArr['includecult']);
		}
		if(array_key_exists('attr',$_REQUEST)){
			//Occurrence trait attributed passed as stateIDs
			$stateIdStr = $_REQUEST['attr'];
			if(is_array($_REQUEST['attr'])) $stateIdStr = implode(',',array_unique($_REQUEST['attr']));
			if(preg_match('/^[0-9,]+$/', $stateIdStr)) $this->searchTermArr['attr'] = $stateIdStr;
		}
		$llPattern = '-?\d+\.{0,1}\d*';
		if(array_key_exists('upperlat',$_REQUEST)){
			$upperLat = ''; $bottomlat = ''; $leftLong = ''; $rightlong = '';
			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['upperlat']), $m1)){
				$upperLat = round($m1[1],5);
				$uLatDir = (isset($_REQUEST['upperlat_NS'])?strtoupper($_REQUEST['upperlat_NS']):'');
				if(($uLatDir == 'N' && $upperLat < 0) || ($uLatDir == 'S' && $upperLat > 0)) $upperLat *= -1;
			}

			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['bottomlat']), $m2)){
				$bottomlat = round($m2[1],5);
				$bLatDir = (isset($_REQUEST['bottomlat_NS'])?strtoupper($_REQUEST['bottomlat_NS']):'');
				if(($bLatDir == 'N' && $bottomlat < 0) || ($bLatDir == 'S' && $bottomlat > 0)) $bottomlat *= -1;
			}

			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['leftlong']), $m3)){
				$leftLong = round($m3[1],5);
				$lLngDir = (isset($_REQUEST['leftlong_EW'])?strtoupper($_REQUEST['leftlong_EW']):'');
				if(($lLngDir == 'E' && $leftLong < 0) || ($lLngDir == 'W' && $leftLong > 0)) $leftLong *= -1;
			}

			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['rightlong']), $m4)){
				$rightlong = round($m4[1],5);
				$rLngDir = (isset($_REQUEST['rightlong_EW'])?strtoupper($_REQUEST['rightlong_EW']):'');
				if(($rLngDir == 'E' && $rightlong < 0) || ($rLngDir == 'W' && $rightlong > 0)) $rightlong *= -1;
			}

			if(is_numeric($upperLat) && is_numeric($bottomlat) && is_numeric($leftLong) && is_numeric($rightlong)){
				$latLongStr = $upperLat.';'.$bottomlat.';'.$leftLong.';'.$rightlong;
				$this->searchTermArr['llbound'] = $latLongStr;
			}
			else{
				unset($this->searchTermArr['llbound']);
			}
		}
		if(array_key_exists('llbound',$_REQUEST) && $_REQUEST['llbound']){
			$this->searchTermArr['llbound'] = $this->cleanInputStr($_REQUEST['llbound']);
		}
		if(array_key_exists('pointlat',$_REQUEST)){
			$pointLat = '';
			$pointLong = '';
			$radius = '';
			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['pointlat']), $m1)){
				$pointLat = $m1[1];
				if(isset($_REQUEST['pointlat_NS'])){
					if($_REQUEST['pointlat_NS'] == 'S' && $pointLat > 0) $pointLat *= -1;
					elseif($_REQUEST['pointlat_NS'] == 'N' && $pointLat < 0) $pointLat *= -1;
				}
			}
			if(preg_match('/('.$llPattern.')/', trim($_REQUEST['pointlong']), $m2)){
				$pointLong = $m2[1];
				if(isset($_REQUEST['pointlong_EW'])){
					if($_REQUEST['pointlong_EW'] == 'W' && $pointLong > 0) $pointLong *= -1;
					elseif($_REQUEST['pointlong_EW'] == 'E' && $pointLong < 0) $pointLong *= -1;
				}
			}
			if(preg_match('/(\d+)/', $_REQUEST['radius'], $m3)){
				$radius = $m3[1];
			}
			if($pointLat && $pointLong && is_numeric($radius)){
				$radiusUnits = (isset($_REQUEST['radiusunits'])?$this->cleanInputStr($_REQUEST['radiusunits']):'mi');
				$pointRadiusStr = $pointLat.';'.$pointLong.';'.$radius.';'.$radiusUnits;
				$this->searchTermArr['llpoint'] = $pointRadiusStr;
			}
			else{
				unset($this->searchTermArr['llpoint']);
			}
		}
		if(array_key_exists('llpoint',$_REQUEST) && $_REQUEST['llpoint']){
			$this->searchTermArr['llpoint'] = $this->cleanInputStr($_REQUEST['llpoint']);
		}
		if(array_key_exists('footprintwkt',$_REQUEST) && $_REQUEST['footprintwkt']){
			$this->searchTermArr['footprintwkt'] = $this->cleanInputStr($_REQUEST['footprintwkt']);
		}
	}

	private function setChecklistVariables($clid){
		$this->voucherManager = new ChecklistVoucherAdmin($this->conn);
		$this->voucherManager->setClid($clid);
		$this->voucherManager->setCollectionVariables();
	}

	//Misc support functions
	private function hasFullSpatialSupport(){
		$serverStr = '';
		if(mysqli_get_server_info($this->conn)) $serverStr = mysqli_get_server_info($this->conn);
		else $serverStr = shell_exec('mysql -V');
		if($serverStr){
			if(strpos($serverStr,'MariaDB') !== false) return true;
			else{	//db = mysql;
				preg_match('@[0-9]+\.[0-9]+\.[0-9]+@',$serverStr,$m);
				$mysqlVerNums = explode('.', $m[0]);
				if($mysqlVerNums[0] > 5) return true;
				elseif($mysqlVerNums[0] == 5){
					if($mysqlVerNums[1] > 6) return true;
					elseif($mysqlVerNums[1] == 6){
						if($mysqlVerNums[2] >= 1) return true;
					}
				}
			}
		}
		return false;
	}

	//Setters and getters
	public function getClName(){
		if(!$this->voucherManager) return false;
		return $this->voucherManager->getClName();
	}

	public function getClFootprintWkt(){
		if(!$this->voucherManager) return false;
		return $this->voucherManager->getClFootprintWkt();
	}

	public function getTaxaArr(){
		return $this->taxaArr;
	}

	public function getErrorMessage(){
		return $this->errorMessage;
	}
}
?>
