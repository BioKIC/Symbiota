<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class ChecklistVoucherAdmin extends Manager {

	protected $clid;
	protected $clName;
	protected $clMetadata;
	private $childClidArr = array();
	private $footprintWkt;
	private $queryVariablesArr = array();

	function __construct($con=null) {
		parent::__construct(null, 'write', $con);
	}

	function __destruct(){
		parent::__destruct();
	}

	public function setClid($clid){
		$clid = filter_var($clid, FILTER_SANITIZE_NUMBER_INT);
		if($clid){
			$this->clid = $clid;
			$this->setMetaData();
			//Get children checklists
			$sqlBase = 'SELECT ch.clidchild, cl2.name '.
				'FROM fmchecklists cl INNER JOIN fmchklstchildren ch ON cl.clid = ch.clid '.
				'INNER JOIN fmchecklists cl2 ON ch.clidchild = cl2.clid '.
				'WHERE (cl2.type != "excludespp") AND cl.clid IN(';
			$sql = $sqlBase.$this->clid.')';
			do{
				$childStr = "";
				$rsChild = $this->conn->query($sql);
				while($r = $rsChild->fetch_object()){
					$this->childClidArr[] = $r->clidchild;
					$childStr .= ','.$r->clidchild;
				}
				$sql = $sqlBase.substr($childStr,1).')';
			}while($childStr);
		}
	}

	private function setMetaData(){
		if($this->clid){
			$sql = 'SELECT clid, name, locality, publication, abstract, authors, parentclid, notes, latcentroid, longcentroid, pointradiusmeters, '.
				'footprintwkt, access, defaultSettings, dynamicsql, datelastmodified, uid, type, initialtimestamp '.
				'FROM fmchecklists WHERE (clid = '.$this->clid.')';
		 	$rs = $this->conn->query($sql);
			if($rs){
		 		if($row = $rs->fetch_object()){
					$this->clName = $row->name;
					$this->clMetadata["locality"] = $row->locality;
					$this->clMetadata["notes"] = $row->notes;
					$this->clMetadata["type"] = $row->type;
					$this->clMetadata["publication"] = $row->publication;
					$this->clMetadata["abstract"] = $row->abstract;
					$this->clMetadata["authors"] = $row->authors;
					$this->clMetadata["parentclid"] = $row->parentclid;
					$this->clMetadata["uid"] = $row->uid;
					$this->clMetadata["latcentroid"] = $row->latcentroid;
					$this->clMetadata["longcentroid"] = $row->longcentroid;
					$this->clMetadata["pointradiusmeters"] = $row->pointradiusmeters;
					$this->clMetadata['footprintwkt'] = $row->footprintwkt;
					$this->clMetadata["access"] = $row->access;
					$this->clMetadata["defaultSettings"] = $row->defaultSettings;
					$this->clMetadata["dynamicsql"] = $row->dynamicsql;
					$this->clMetadata["datelastmodified"] = $row->datelastmodified;
				}
				$rs->free();
			}
			else{
				trigger_error('ERROR: unable to set checklist metadata => '.$sql, E_USER_ERROR);
			}
			//Temporarly needed as a separate call until db_schema_patch-1.1.sql is applied
			$sql = 'SELECT headerurl FROM fmchecklists WHERE (clid = '.$this->clid.')';
			$rs = $this->conn->query($sql);
			if($rs){
				if($r = $rs->fetch_object()){
					$this->clMetadata['headerurl'] = $r->headerurl;
				}
				$rs->free();
			}
		}
	}

	public function getPolygonCoordinates(){
		$retArr = array();
		if($this->clid){
			if($this->clMetadata['dynamicsql']){
				$sql = 'SELECT o.decimallatitude, o.decimallongitude FROM omoccurrences o ';
				if($this->clMetadata['footprintwkt'] && substr($this->clMetadata['footprintwkt'],0,7) == 'POLYGON'){
					$sql .= 'INNER JOIN omoccurpoints p ON o.occid = p.occid WHERE (ST_Within(p.point,GeomFromText("'.$this->clMetadata['footprintwkt'].'"))) ';
				}
				else{
					$this->setCollectionVariables();
					$sql .= 'WHERE ('.$this->getSqlFrag().') ';
				}
				$sql .= 'LIMIT 50';
				//echo $sql; exit;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$retArr[] = $r->decimallatitude.','.$r->decimallongitude;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	//Dynamic query variable functions
	public function setCollectionVariables(){
		if($this->clid){
			$sql = 'SELECT name, dynamicsql, footprintwkt FROM fmchecklists WHERE (clid = '.$this->clid.')';
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->clName = $this->cleanOutStr($row->name);
				$this->footprintWkt = $row->footprintwkt;
				$sqlFrag = $row->dynamicsql;
				$varArr = json_decode($sqlFrag,true);
				if(json_last_error() != JSON_ERROR_NONE){
					$varArr = $this->parseSqlFrag($sqlFrag);
					$this->saveQueryVariables($varArr);
				}
				$this->queryVariablesArr = $varArr;
			}
			else{
				$this->clName = 'Unknown';
			}
			$result->free();
			//Get children checklists
			$sqlChildBase = 'SELECT clidchild FROM fmchklstchildren WHERE clid IN(';
			$sqlChild = $sqlChildBase.$this->clid.')';
			do{
				$childStr = "";
				$rsChild = $this->conn->query($sqlChild);
				while($rChild = $rsChild->fetch_object()){
					$this->childClidArr[] = $rChild->clidchild;
					$childStr .= ','.$rChild->clidchild;
				}
				$sqlChild = $sqlChildBase.substr($childStr,1).')';
			}while($childStr);
		}
	}

	private function parseSqlFrag($sqlFrag){
		$retArr = array();
		if($sqlFrag){
			if(preg_match('/country = "([^"]+)"/',$sqlFrag,$m)){
				$retArr['country'] = $m[1];
			}
			if(preg_match('/stateprovince = "([^"]+)"/',$sqlFrag,$m)){
				$retArr['state'] = $m[1];
			}
			if(preg_match('/county LIKE "([^%"]+)%"/',$sqlFrag,$m)){
				$retArr['county'] = trim($m[1],' %');
			}
			if(preg_match('/locality LIKE "%([^%"]+)%"/',$sqlFrag,$m)){
				$retArr['locality'] = trim($m[1],' %');
			}
			if(preg_match('/parenttid = (\d+)\)/',$sqlFrag,$m)){
				$retArr['taxon'] = $this->getSciname($m[1]);
			}
			if(preg_match_all('/AGAINST\("([^()"]+)"\)/',$sqlFrag,$m)){
				$retArr['recordedby'] = implode(',',$m[1]);
			}
			if(preg_match('/decimallatitude BETWEEN ([-\.\d]+) AND ([-\.\d]+)\D+/',$sqlFrag,$m)){
				$retArr['latsouth'] = $m[1];
				$retArr['latnorth'] = $m[2];
			}
			if(preg_match('/decimallongitude BETWEEN ([-\.\d]+) AND ([-\.\d]+)\D+/',$sqlFrag,$m)){
				$retArr['lngwest'] = $m[1];
				$retArr['lngeast'] = $m[2];
			}
			if(preg_match('/collid = (\d+)\D/',$sqlFrag,$m)){
				$retArr['collid'] = $m[1];
			}
			if(preg_match('/decimallatitude/',$sqlFrag)){
				$retArr['onlycoord'] = 1;
			}
			if(preg_match('/cultivationStatus/',$sqlFrag)){
				$retArr['excludecult'] = 1;
			}
		}
		return $retArr;
	}

	public function saveQueryVariables($postArr){
		$fieldArr = array('country','state','county','locality','taxon','collid','recordedby','latnorth','latsouth','lngeast','lngwest','onlycoord','includewkt','excludecult');
		$jsonArr = array();
		foreach($fieldArr as $fieldName){
			if(isset($postArr[$fieldName]) && $postArr[$fieldName]) $jsonArr[$fieldName] = $postArr[$fieldName];
		}
		$sql = 'UPDATE fmchecklists c SET c.dynamicsql = '.($jsonArr?'"'.$this->cleanInStr(json_encode($jsonArr)).'"':'NULL').' WHERE (c.clid = '.$this->clid.')';
		//echo $sql; exit;
		if(!$this->conn->query($sql)){
			$this->errorMessage = 'ERROR: unable to create or modify search statement ('.$this->conn->error.')';
		}
	}

	public function deleteQueryVariables(){
		$statusStr = '';
		if($this->conn->query('UPDATE fmchecklists c SET c.dynamicsql = NULL WHERE (c.clid = '.$this->clid.')')){
			unset($this->queryVariablesArr);
			$this->queryVariablesArr = array();
		}
		else{
			$statusStr = 'ERROR: '.$this->conn->error;
		}
		return $statusStr;
	}

	public function getQueryVariableArr(){
		return $this->queryVariablesArr;
	}

	public function getQueryVariableStr(){
		$retStr = '';
		if(isset($this->queryVariablesArr['collid'])){
			$collArr = $this->getCollectionList($this->queryVariablesArr['collid']);
			$retStr .= current($collArr).'; ';
		}
		if(isset($this->queryVariablesArr['country'])) $retStr .= $this->queryVariablesArr['country'].'; ';
		if(isset($this->queryVariablesArr['state'])) $retStr .= $this->queryVariablesArr['state'].'; ';
		if(isset($this->queryVariablesArr['county'])) $retStr .= $this->queryVariablesArr['county'].'; ';
		if(isset($this->queryVariablesArr['locality'])) $retStr .= $this->queryVariablesArr['locality'].'; ';
		if(isset($this->queryVariablesArr['taxon'])) $retStr .= $this->queryVariablesArr['taxon'].'; ';
		if(isset($this->queryVariablesArr['recordedby'])) $retStr .= $this->queryVariablesArr['recordedby'].'; ';
		if(isset($this->queryVariablesArr['latsouth']) && isset($this->queryVariablesArr['latnorth'])) $retStr .= 'Lat between '.$this->queryVariablesArr['latsouth'].' and '.$this->queryVariablesArr['latnorth'].'; ';
		if(isset($this->queryVariablesArr['lngwest']) && isset($this->queryVariablesArr['lngeast'])) $retStr .= 'Long between '.$this->queryVariablesArr['lngwest'].' and '.$this->queryVariablesArr['lngeast'].'; ';
		if(isset($this->queryVariablesArr['includewkt'])) $retStr .= 'Search based on polygon; ';
		if(isset($this->queryVariablesArr['excludecult'])) $retStr .= 'Exclude cultivated/captive records; ';
		if(isset($this->queryVariablesArr['onlycoord'])) $retStr .= 'Only include occurrences with coordinates; ';
		return trim($retStr,' ;');
	}

	public function getSqlFrag(){
		$sqlFrag = '';
		if(isset($this->queryVariablesArr['country']) && $this->queryVariablesArr['country']){
			$countrySql = '';
			$countryArr = explode(';',str_replace(',',';',$this->queryVariablesArr['country']));
			foreach($countryArr as $cTerm){
				$countrySql .= ',"'.$this->cleanInStr(trim($cTerm)).'"';
			}
			$sqlFrag .= 'AND (o.country IN('.trim($countrySql,',').')) ';
		}
		if(isset($this->queryVariablesArr['state']) && $this->queryVariablesArr['state']){
			$stateSql = '';
			$stateArr = explode(';',str_replace(',',';',$this->queryVariablesArr['state']));
			foreach($stateArr as $sTerm){
				$stateSql .= ',"'.$this->cleanInStr(trim($sTerm)).'"';
			}
			$sqlFrag .= 'AND (o.stateprovince IN('.trim($stateSql,',').')) ';
		}
		if(isset($this->queryVariablesArr['county']) && $this->queryVariablesArr['county']){
			$countyStr = str_replace(';',',',$this->queryVariablesArr['county']);
			$cArr = explode(',', $countyStr);
			$cStr = '';
			foreach($cArr as $str){
				$cStr .= 'OR (o.county LIKE "'.$this->cleanInStr($str).'%") ';
			}
			$sqlFrag .= 'AND ('.substr($cStr, 2).') ';
		}
		//taxonomy
		if(isset($this->queryVariablesArr['taxon']) && $this->queryVariablesArr['taxon']){
			$tStr = $this->cleanInStr($this->queryVariablesArr['taxon']);
			$tidPar = $this->getTid($tStr);
			if($tidPar){
				$sqlFrag .= 'AND (o.tidinterpreted IN (SELECT ts.tid FROM taxaenumtree e INNER JOIN taxstatus ts ON e.tid = ts.tidaccepted WHERE ts.taxauthid = 1 AND e.taxauthid = 1 AND e.parenttid = '.$tidPar.')) ';
			}
		}
		//Locality and Latitude and longitude
		$locStr = '';
		if(isset($this->queryVariablesArr['locality']) && $this->queryVariablesArr['locality']){
			$localityStr = str_replace(';',',',$this->queryVariablesArr['locality']);
			$locArr = explode(',', $localityStr);
			foreach($locArr as $str){
				$str = $this->cleanInStr($str);
				if(strlen($str) > 4){
					$locStr .= 'OR (MATCH(f.locality) AGAINST(\'"'.$str.'"\' IN BOOLEAN MODE)) ';
				}
				else{
					$locStr .= 'OR (o.locality LIKE "%'.$str.'%") ';
				}
				//$locStr .= 'OR (o.locality LIKE "%'.$this->cleanInStr($str).'%") ';
			}
		}
		$llStr = '';
		if(isset($this->queryVariablesArr['latnorth']) && isset($this->queryVariablesArr['latsouth']) && is_numeric($this->queryVariablesArr['latnorth']) && is_numeric($this->queryVariablesArr['latsouth'])){
			if(isset($this->queryVariablesArr['lngwest']) && isset($this->queryVariablesArr['lngeast']) && is_numeric($this->queryVariablesArr['lngwest']) && is_numeric($this->queryVariablesArr['lngeast'])){
				$llStr .= '(o.decimallatitude BETWEEN '.$this->queryVariablesArr['latsouth'].' AND '.$this->queryVariablesArr['latnorth'].') '.
					'AND (o.decimallongitude BETWEEN '.$this->queryVariablesArr['lngwest'].' AND '.$this->queryVariablesArr['lngeast'].') ';
			}
		}
		if(isset($this->queryVariablesArr['includewkt']) && $this->queryVariablesArr['includewkt'] && $this->footprintWkt){
			//Searh based on polygon
			$sqlFrag .= 'AND (ST_Within(p.point,GeomFromText("'.$this->footprintWkt.'"))) ';
			$llStr = false;
		}
		if(isset($this->queryVariablesArr['latlngor']) && $this->queryVariablesArr['latlngor'] && $locStr && $llStr){
			//Query coordinates or locality string
			$sqlFrag .= 'AND (('.substr($locStr, 2).') OR ('.trim($llStr).')) ';
		}
		else{
			if($locStr) $sqlFrag .= 'AND ('.substr($locStr, 2).') ';
			if($llStr) $sqlFrag .= 'AND ('.trim($llStr).') ';
		}
		if(isset($this->queryVariablesArr['onlycoord']) && $this->queryVariablesArr['onlycoord'] && !$llStr && $llStr !== false){
			//Use occurrences only with coordinates
			$sqlFrag .= 'AND (o.decimallatitude IS NOT NULL) ';
		}
		//Exclude taxonomy
		if(isset($this->queryVariablesArr['excludecult']) && $this->queryVariablesArr['excludecult']){
			$sqlFrag .= 'AND (o.cultivationStatus = 0 OR o.cultivationStatus IS NULL) ';
		}
		//Limit by collection
		if(isset($this->queryVariablesArr['collid']) && is_numeric($this->queryVariablesArr['collid'])){
			$sqlFrag .= 'AND (o.collid IN('.$this->queryVariablesArr['collid'].')) ';
		}
		//Limit by collector
		if(isset($this->queryVariablesArr['recordedby']) && $this->queryVariablesArr['recordedby']){
			$collStr = str_replace(',', ';', strtolower($this->queryVariablesArr['recordedby']));
			$collArr = explode(';',$collStr);
			$tempArr = array();
			foreach($collArr as $str){
				if(strlen($str) < 4 || in_array($str,array('best','little'))){
					//Need to avoid FULLTEXT stopwords interfering with return
					$tempArr[] = '(o.recordedby LIKE "%'.$this->cleanInStr($str).'%")';
				}
				else{
					$tempArr[] = '(MATCH(f.recordedby) AGAINST("'.$this->cleanInStr($str).'"))';
				}
			}
			$sqlFrag .= 'AND ('.implode(' OR ', $tempArr).') ';
		}
		//Save SQL fragment
		if($sqlFrag) $sqlFrag = trim(substr($sqlFrag,3));
		return $sqlFrag;
	}

	//Voucher loading functions
	public function linkVouchers($occidArr){
		$statusCnt = 0;
		foreach($occidArr as $v){
			$vArr = explode('-',$v);
			if(count($vArr) == 2 && $vArr[0] && $vArr[1]){
				$occid = $vArr[0];
				$clTaxaID = $vArr[1];
				if($this->insertVoucher($clTaxaID, $occid)){
					$statusCnt++;
				}
			}
		}
		return $statusCnt;
	}

	public function linkVoucher($taxa, $occid, $morphoSpecies=''){
		$status = false;
		if($this->voucherIsLinked($occid)){
			$this->errorMessage = 'voucherAlreadyLinked';
			return false;
		}
		if(!is_numeric($taxa)) $taxa = $this->getTid($taxa);
		$clTaxaID = $this->getClTaxaID($taxa, $morphoSpecies);
		if(!$clTaxaID) $clTaxaID = $this->insertChecklistTaxaLink($taxa);
		if($clTaxaID){
			$status = $this->insertVoucher($clTaxaID, $occid);
		}
		return $status;
	}

	public function linkTaxaVouchers($occidArr, $useCurrentTaxon = true, $linkVouchers = true){
		$tidsUsed = array();
		foreach($occidArr as $v){
			$vArr = explode('-',$v);
			$tid = $vArr[1];
			$occid = $vArr[0];
			if(count($vArr) == 2 && is_numeric($occid) && is_numeric($tid)){
				if($useCurrentTaxon) $tid = $this->getTidAccepted($tid);
				if(!in_array($tid, $tidsUsed)){
					//Add name to checklist
					$clTaxaID = $this->insertChecklistTaxaLink($tid);
					$tidsUsed[] = $tid;
					if($clTaxaID && $linkVouchers){
						$this->insertVoucher($clTaxaID, $occid);
					}
				}
			}
		}
	}

	public function batchTransferConflicts($occidArr, $removeTaxa){
		foreach($occidArr as $occid){
			if(is_numeric($occid)){
				$voucherID = 0;
				$oldClTaxaID = 0;
				//Get checklist voucher and clTaxa IDs
				$sql = 'SELECT v.voucherID, v.cltaxaid FROM fmchklsttaxalink c INNER JOIN fmvouchers v ON c.cltaxaid = v.cltaxaid WHERE (c.clid = ?) AND (v.occid = ?)';
				if($stmt = $this->conn->prepare($sql)) {
					$stmt->bind_param('ii', $this->clid, $occid);
					$stmt->execute();
					$stmt->bind_result($voucherID, $oldClTaxaID);
					$stmt->fetch();
					$stmt->close();
				}

				//Get voucher tid
				$tidTarget = $this->getTidInterpreted($occid);
				if($oldClTaxaID && $tidTarget){
					//Make sure target name is already linked to checklist
					$sql2 = 'INSERT IGNORE INTO fmchklsttaxalink(tid, clid, morphospecies, familyoverride, habitat, abundance, notes, explicitExclude, source, internalnotes, dynamicProperties) '.
						'SELECT '.$tidTarget.' as tid, c.clid, c.morphospecies, c.familyoverride, c.habitat, c.abundance, c.notes, c.explicitExclude, c.source, c.internalnotes, c.dynamicProperties '.
						'FROM fmchklsttaxalink WHERE (cltaxaid = ?)';
					if($stmt2 = $this->conn->prepare($sql2)) {
						$stmt2->bind_param('i', $oldClTaxaID);
						$stmt2->execute();
						$stmt2->close();
					}
					//Transfer voucher to new taxon
					$sql3 = 'UPDATE fmvouchers v INNER JOIN fmchklsttaxalink s ON v.cltaxaid = s.cltaxaid
						INNER JOIN fmchklsttaxalink t ON s.clid = t.clid AND s.morphospecies = t.morphospecies
						SET v.cltaxaid = t.cltaxaid
						WHERE v.voucherID = ? AND t.tid = ?';
					if($stmt3 = $this->conn->prepare($sql3)) {
						$stmt3->bind_param('ii', $voucherID, $tidTarget);
						$stmt3->execute();
						$stmt3->close();
					}
					if($removeTaxa){
						//Remove old taxa if there are no longer any linked vouchers
						$sql4 = 'DELETE c.* FROM fmchklsttaxalink c LEFT JOIN fmvouchers v ON c.cltaxaid = v.cltaxaid WHERE c.cltaxaid = ? AND v.voucherID IS NULL';
						if($stmt4 = $this->conn->prepare($sql4)) {
							$stmt4->bind_param('i', $oldClTaxaID);
							$stmt4->execute();
							$stmt4->close();
						}
					}
				}
			}
		}
	}

	//Data mod functions
	protected function insertChecklistTaxaLink($tid, $clid = null, $morpho = ''){
		$status = false;
		if(!$clid) $clid = $this->clid;
		if(is_numeric($tid) && is_numeric($clid)){
			$sql = 'INSERT INTO fmchklsttaxalink(tid,clid,morphospecies) VALUES(?,?,?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('iis', $tid, $clid, $morpho);
				$stmt->execute();
				if($stmt->affected_rows && !$stmt->error){
					$status = $stmt->insert_id;
				}
				elseif($stmt->error) $this->errorMessage = 'ERROR inserting checklist-taxa-link: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for checklist-taxa-link insert: '.$this->conn->error;
		}
		return $status;
	}

	protected function insertVoucher($clTaxaID, $occid, $editorNotes = null, $notes = null){
		$status = false;
		if(is_numeric($clTaxaID) && is_numeric($occid)){
			if($editorNotes == '') $editorNotes = null;
			if($notes == '') $notes = null;
			$sql = 'INSERT INTO fmvouchers(clTaxaID, occid, editorNotes, notes) VALUES (?,?,?,?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('iiss', $clTaxaID, $occid, $editorNotes, $notes);
				$stmt->execute();
				if($stmt->affected_rows){
					$status = $stmt->insert_id;
				}
				elseif($stmt->error) $this->errorMessage = 'ERROR inserting voucher: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for voucher insert: '.$this->conn->error;
		}
		return $status;
	}

	private function transferVouchers($target, $source){
		$status = false;
		if(is_numeric($target) && is_numeric($source)){
			$sql = 'UPDATE fmvouchers SET clTaxaID = ? WHERE clTaxaID = ?';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('ii', $target, $source);
				$stmt->execute();
				if($stmt->error) $this->errorMessage = 'ERROR transferring vouchers: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for voucher transfer: '.$this->conn->error;
		}
		return $status;
	}

	//Misc support and data functions
	protected function getClTaxaID($tid, $morphoSpecies = ''){
		$clTaxaID = 0;
		if(is_numeric($tid)){
			$sql = 'SELECT clTaxaID FROM fmchklsttaxalink WHERE clid = ? AND tid = ? AND morphospecies = ?';
			if($stmt = $this->conn->prepare($sql)) {
				if($stmt->bind_param('iis', $this->clid, $tid, $morphoSpecies)){
					$stmt->execute();
					$stmt->bind_result($clTaxaID);
					$stmt->fetch();
					$stmt->close();
				}
				else $this->errorMessage = 'ERROR binding params for getClTaxaID: '.$this->conn->error;
			}
			else $this->errorMessage = 'ERROR preparing statement for getClTaxaID: '.$this->conn->error;
		}
		return $clTaxaID;
	}

	private function getSciname($tid){
		$sciname = '';
		if(is_numeric($tid)){
			$sql = 'SELECT sciname FROM taxa WHERE tid = ?';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('i', $tid);
				$stmt->execute();
				$stmt->bind_result($sciname);
				$stmt->fetch();
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for getSciname: '.$this->conn->error;
		}
		return $sciname;
	}

	private function getTid($sciname){
		$tid = 0;
		if($sciname){
			$sql = 'SELECT tid FROM taxa WHERE sciname = (?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('s', $sciname);
				$stmt->execute();
				$stmt->bind_result($tid);
				$stmt->fetch();
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for getTid: '.$this->conn->error;
		}
		return $tid;
	}

	private function getTidAccepted($tid){
		$tidAccepted = 0;
		if(is_numeric($tid)){
			$sql = 'SELECT tidaccepted FROM taxstatus WHERE taxauthid = 1 AND tid = ?';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('i', $tid);
				$stmt->execute();
				$stmt->bind_result($tidAccepted);
				$stmt->fetch();
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for getTidAccepted: '.$this->conn->error;
		}
		return $tidAccepted;
	}

	protected function getTidInterpreted($occid){
		$tidInterpreted = 0;
		if(is_numeric($occid)){
			$sql = 'SELECT tidinterpreted FROM omoccurrences WHERE occid = ?';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('i', $occid);
				$stmt->execute();
				$stmt->bind_result($tidInterpreted);
				$stmt->fetch();
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for getTidInterpreted: '.$this->conn->error;
		}
		return $tidInterpreted;
	}

	public function voucherIsLinked($occid){
		$bool = false;
		if($this->clid && is_numeric($occid)){
			$sql = 'SELECT v.voucherID FROM fmvouchers v INNER JOIN fmchklsttaxalink c ON v.cltaxaid = c.cltaxaid WHERE (c.clid = ?) AND (occid = ?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('ii', $this->clid, $occid);
				$stmt->execute();
				$stmt->store_result();
				if($stmt->num_rows) $bool = true;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for voucherIsLinked: '.$this->conn->error;
		}
		return $bool;
	}

	public function vouchersExist(){
		$bool = false;
		if($this->clid){
			$sql = 'SELECT c.tid FROM fmvouchers v INNER JOIN fmchklsttaxalink c ON v.cltaxaid = c.cltaxaid WHERE (c.clid = ?) LIMIT 1';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('i', $this->clid);
				$stmt->execute();
				$stmt->store_result();
				if($stmt->num_rows) $bool = true;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for vouchersExist: '.$this->conn->error;
		}
		return $bool;
	}

	public function getCollectionList($collId = 0){
		$retArr = array();
		$sql = 'SELECT collid, collectionname FROM omcollections ';
		if($collId) $sql .= 'WHERE collid = '.$collId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	public function getVoucherProjects(){
		global $USER_RIGHTS;
		$retArr = array();
		$runQuery = true;
		$sql = 'SELECT collid, collectionname FROM omcollections WHERE (colltype = "Observations" OR colltype = "General Observations") ';
		if(!array_key_exists('SuperAdmin',$USER_RIGHTS)){
			$collInStr = '';
			foreach($USER_RIGHTS as $k => $v){
				if($k == 'CollAdmin' || $k == 'CollEditor'){
					$collInStr .= ','.implode(',',$v);
				}
			}
			if($collInStr){
				$sql .= 'AND collid IN ('.substr($collInStr,1).') ';
			}
			else{
				$runQuery = false;
			}
		}
		$sql .= 'ORDER BY colltype,collectionname';
		//echo $sql;
		if($runQuery){
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$r->collid] = $r->collectionname;
				}
				$rs->free();
			}
			if($retArr && $this->clid){
				//Tag collection most likely to be target
				$sql = 'SELECT o.collid, COUNT(v.occid) as cnt
					FROM fmvouchers v INNER JOIN omoccurrences o ON v.occid = o.occid
					INNER JOIN fmchklsttaxalink c ON v.cltaxaid = c.cltaxaid
					WHERE c.clid = '.$this->clid.' AND o.collid IN('.implode(',', array_keys($retArr)).')
					GROUP BY o.collid ORDER BY cnt DESC';
				if($rs = $this->conn->query($sql)){
					if($r = $rs->fetch_object()) $retArr['target'] = $r->collid;
					$rs->free();
				}
			}
		}
		return $retArr;
	}

	public function hasVoucherProjects(){
		global $USER_RIGHTS;
		$retBool = false;
		$runQuery = true;
		$sql = 'SELECT collid, collectionname FROM omcollections WHERE (colltype = "Observations" OR colltype = "General Observations") ';
		if(!array_key_exists('SuperAdmin',$USER_RIGHTS)){
			$collInStr = '';
			foreach($USER_RIGHTS as $k => $v){
				if($k == 'CollAdmin' || $k == 'CollEditor'){
					$collInStr .= ','.implode(',',$v);
				}
			}
			if($collInStr){
				$sql .= 'AND collid IN ('.substr($collInStr,1).') ';
			}
			else{
				$runQuery = false;
			}
		}
		$sql .= ' LIMIT 1';
		//echo $sql;
		if($runQuery){
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()){
					$retBool = true;
				}
				$rs->free();
			}
		}
		return $retBool;
	}

	//Setters and getters
	public function getClid(){
		return $this->clid;
	}

	public function getChildClidArr(){
		return $this->childClidArr;
	}

	public function getClidFullStr(){
		$clidStr = $this->clid;
		if($this->childClidArr) $clidStr .= ','.implode(',',$this->childClidArr);
		return $clidStr;
	}

	public function getClName(){
		return $this->clName;
	}

	public function getClMetadata(){
		return $this->clMetadata;
	}

	public function getClFootprintWkt(){
		return $this->footprintWkt;
	}

	//Misc functions
	protected function encodeArr(&$inArr){
		$charSetOut = 'ISO-8859-1';
		$charSetSource = strtoupper($GLOBALS['CHARSET']);
		if($charSetSource && $charSetOut != $charSetSource){
			foreach($inArr as $k => $v){
				$inArr[$k] = $this->encodeStr($v);
			}
		}
	}

	protected function encodeStr($inStr){
		$charSetSource = strtoupper($GLOBALS['CHARSET']);
		$charSetOut = 'ISO-8859-1';
		$retStr = $inStr;
		if($inStr && $charSetSource){
			if($charSetOut == 'UTF-8' && $charSetSource == 'ISO-8859-1'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == 'ISO-8859-1'){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif($charSetOut == "ISO-8859-1" && $charSetSource == 'UTF-8'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == 'UTF-8'){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
		}
		return $retStr;
	}
}
?>