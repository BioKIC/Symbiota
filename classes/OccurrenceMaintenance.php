<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class OccurrenceMaintenance {

	protected $conn;
	private $destructConn = true;
	private $collidStr = null;
	private $verbose = false;	// 0 = silent, 1 = echo as list item
	private $errorArr = array();

	public function __construct($con = null, $conType = 'write'){
		if($con){
			//Inherits connection from another class
			$this->conn = $con;
			$this->destructConn = false;
		}
		else{
			$this->conn = MySQLiConnectionFactory::getCon($conType);
		}
	}

	public function __destruct(){
		if($this->destructConn && !($this->conn === null)){
			$this->conn->close();
			$this->conn = null;
		}
 	}

	//General cleaning functions
	public function generalOccurrenceCleaning(){
		set_time_limit(600);
		$status = true;

		$this->indexOccurrencesToTaxa();

		//Update NULL sciname with family designations when family field is not null
		$occidArr = array();
		$this->outputMsg('Updating null scientific names of family rank identifications... ',1);
		$sql = 'SELECT occid FROM omoccurrences WHERE family IS NOT NULL AND sciname IS NULL ';
		if($this->collidStr) $sql .= 'AND collid IN('.$this->collidStr.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$occidArr[] = $r->occid;
			if(count($occidArr) > 1000){
				$this->batchScinameWithFamily($occidArr);
				unset($occidArr);
				$occidArr = array();
			}
		}
		$rs->free();
		$this->batchScinameWithFamily($occidArr);
		unset($occidArr);

		//Update NULL image tids with non-NULL occurrence tids
		$occidArr = array();
		$this->outputMsg('Updating and indexing occurrence images... ',1);
		$sql = 'SELECT o.occid FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid WHERE (i.tid IS NULL) AND (o.tidinterpreted IS NOT NULL) ';
		if($this->collidStr) $sql .= 'AND o.collid IN('.$this->collidStr.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$occidArr[] = $r->occid;
			if(count($occidArr) > 1000){
				$this->batchUpdateImageTid($occidArr);
				unset($occidArr);
				$occidArr = array();
			}
		}
		$rs->free();
		$this->batchUpdateImageTid($occidArr);
		unset($occidArr);

		//Update NULL families with taxstatus family values
		$occidArr = array();
		$this->outputMsg('Updating null families using taxonomic thesaurus... ',1);
		$sql = 'SELECT o.occid FROM omoccurrences o INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid WHERE (ts.taxauthid = 1) AND (ts.family IS NOT NULL) AND (o.family IS NULL)';
		if($this->collidStr) $sql .= 'AND o.collid IN('.$this->collidStr.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$occidArr[] = $r->occid;
			if(count($occidArr) > 1000){
				$this->batchUpdateFamily($occidArr);
				unset($occidArr);
				$occidArr = array();
			}
		}
		$rs->free();
		$this->batchUpdateFamily($occidArr);
		unset($occidArr);

		//Updating records with null author
		$occidArr = array();
		$this->outputMsg('Updating null scientific authors using taxonomic thesaurus... ',1);
		$sql = 'SELECT o.occid FROM omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid WHERE o.scientificNameAuthorship IS NULL AND t.author IS NOT NULL ';
		if($this->collidStr) $sql .= 'AND o.collid IN('.$this->collidStr.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$occidArr[] = $r->occid;
			if(count($occidArr) > 1000){
				$this->batchUpdateAuthor($occidArr);
				unset($occidArr);
			}
		}
		$rs->free();
		if(isset($occidArr)) $this->batchUpdateAuthor($occidArr);

		return $status;
	}

	public function indexOccurrencesToTaxa(){
		$this->outputMsg('Indexing validated scientific names (e.g. populating tidInterpreted)... ', 1);

		//Avoid using straight UPDATE SQL since they will often lock omoccurrences table for a significant amount of time when database is large
		$occidArr = array();
		//Index based on matching sciname and author
		$activeTid = 0;
		$sql = 'SELECT t.tid, o.occid
			FROM omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname AND o.scientificnameauthorship = t.author
			WHERE (o.TidInterpreted IS NULL) ';
		if($this->collidStr) $sql .= 'AND o.collid IN('.$this->collidStr.') ';
		$sql .= 'ORDER BY t.tid';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($occidArr && $r->tid != $activeTid) $this->batchUpdateTidInterpreted($occidArr);
			$activeTid = $r->tid;
			$occidArr[$r->tid][] = $r->occid;
		}
		$rs->free();
		$this->batchUpdateTidInterpreted($occidArr);

		//Index base on matching sciname and family to improve correct match when cross kingdom homonyms exist
		$activeTid = 0;
		$sql = 'SELECT t.tid, o.occid
			FROM omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname
			INNER JOIN taxaenumtree e ON t.tid = e.tid
			INNER JOIN taxa t2 ON e.parenttid = t2.tid
			WHERE (o.TidInterpreted IS NULL) AND (t2.rankid = 140) AND (t2.sciname = o.family) ';
		if($this->collidStr) $sql .= 'AND o.collid IN('.$this->collidStr.') ';
		$sql .= 'ORDER BY t.tid';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($occidArr && $r->tid != $activeTid) $this->batchUpdateTidInterpreted($occidArr);
			$activeTid = $r->tid;
			$occidArr[$r->tid][] = $r->occid;
		}
		$rs->free();
		$this->batchUpdateTidInterpreted($occidArr);

		//Update remaining taxa that can only be match on scientific name
		$activeTid = 0;
		$sql = 'SELECT t.tid, o.occid FROM omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname WHERE o.TidInterpreted IS NULL ';
		if($this->collidStr) $sql .= 'AND o.collid IN('.$this->collidStr.') ';
		$sql .= 'ORDER BY t.tid';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($occidArr && $r->tid != $activeTid) $this->batchUpdateTidInterpreted($occidArr);
			$activeTid = $r->tid;
			$occidArr[$r->tid][] = $r->occid;
		}
		$rs->free();
		$this->batchUpdateTidInterpreted($occidArr);

		//Match subgeneric names
		$activeTid = 0;
		$sql = 'SELECT t.tid, o.occid
			FROM omoccurrences o INNER JOIN taxa t ON CONCAT(SUBSTRING_INDEX(o.sciname, " (", 1), " ", SUBSTRING_INDEX(o.sciname, ") ", -1)) = t.sciname
			WHERE o.tidinterpreted IS NULL AND o.sciname LIKE "% (%) %" ';
		if($this->collidStr) $sql .= 'AND o.collid IN('.$this->collidStr.') ';
		$sql .= 'ORDER BY t.tid';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($occidArr && $r->tid != $activeTid) $this->batchUpdateTidInterpreted($occidArr);
			$activeTid = $r->tid;
			$occidArr[$r->tid][] = $r->occid;
		}
		$rs->free();
		$this->batchUpdateTidInterpreted($occidArr);
	}

	private function batchUpdateTidInterpreted(&$occidArr){
		$status = false;
		foreach($occidArr as $tid => $idArr){
			$sql = 'UPDATE omoccurrences SET tidInterpreted = '.$tid.' WHERE tidinterpreted IS NULL AND occid IN('.implode(',',$idArr).') ';
			if($this->conn->query($sql)){
				$status = true;
			}
			else{
				$this->errorArr[] = 'WARNING: unable to update tidinterpreted; '.$this->conn->error;
				$this->outputMsg($this->errorArr,2);
				$status = false;
			}
			unset($occidArr[$tid]);
		}
		return $status;
	}

	private function batchUpdateImageTid($occidArr){
		$status = false;
		if($occidArr){
			$sql = 'UPDATE omoccurrences o INNER JOIN images i ON o.occid = i.occid SET i.tid = o.tidinterpreted WHERE o.occid IN('.implode(',',$occidArr).')';
			if($this->conn->query($sql)){
				$status = true;
			}
			else{
				$this->errorArr[] = 'WARNING: unable to update image tid field; '.$this->conn->error;
				$this->outputMsg($this->errorArr,2);
				$status = false;
			}
		}
		return $status;
	}

	private function batchScinameWithFamily($occidArr){
		$status = false;
		if($occidArr){
			$sql = 'UPDATE omoccurrences SET sciname = family WHERE occid IN('.implode(',',$occidArr).') ';
			if($this->conn->query($sql)){
				$status = true;
			}
			else{
				$this->errorArr[] = 'WARNING: unable to update sciname using family; '.$this->conn->error;
				$this->outputMsg($this->errorArr,2);
				$status = false;
			}
		}
		return $status;
	}

	private function batchUpdateFamily($occidArr){
		$status = false;
		if($occidArr){
			$sql = 'UPDATE omoccurrences o INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid SET o.family = ts.family WHERE o.occid IN('.implode(',',$occidArr).')';
			if($this->conn->query($sql)){
				$status = true;
			}
			else{
				$this->errorArr[] = 'WARNING: unable to update family in omoccurrence table; '.$this->conn->error;
				$this->outputMsg($this->errorArr,2);
				$status = false;
			}
		}
		return $status;
	}

	private function batchUpdateAuthor($occidArr){
		$status = false;
		if($occidArr){
			$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid SET o.scientificNameAuthorship = t.author WHERE (o.occid IN('.implode(',',$occidArr).'))';
			if($this->conn->query($sql)){
				$status = true;
			}
			else{
				$this->errorArr[] = 'WARNING: unable to update author; '.$this->conn->error;
				$this->outputMsg($this->errorArr,2);
				$status = false;
			}
		}
		return $status;
	}

	public function batchUpdateGeoreferenceIndex(){
		$status = false;
		$this->outputMsg('Updating georeference index... ',1);
		$sql = 'INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude)
			SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,2), round(o.decimallongitude,2)
			FROM omoccurrences o
			WHERE (o.tidinterpreted IS NOT NULL) AND (o.decimallatitude between -90 and 90) AND (o.decimallongitude between -180 and 180)
			AND (o.cultivationStatus IS NULL OR o.cultivationStatus = 0) AND (o.coordinateUncertaintyInMeters IS NULL OR o.coordinateUncertaintyInMeters < 10000) ';
		if($this->conn->query($sql)){
			$status = true;
		}
		else{
			$errStr = 'WARNING: unable to update georeference index; '.$this->conn->error;
			$this->errorArr[] = $errStr;
			$this->outputMsg($errStr,2);
		}
		return $status;
	}


	//Protect Rare species data
	public function protectRareSpecies(){
		$status = 0;
		$status = $this->protectGlobalSpecies();
		$status += $this->batchProtectStateRareSpecies();
		return $status;
	}

	public function protectGlobalSpecies(){
		$status = 0;
		//protect globally rare species
		$this->outputMsg('Protecting globally rare species... ',1);
		//Only protect names on list and synonym of accepted names
		$sensitiveArr = $this->getSensitiveTaxa();

		if($sensitiveArr){
			$sql = 'UPDATE omoccurrences '.
				'SET LocalitySecurity = 1 '.
				'WHERE (LocalitySecurity IS NULL OR LocalitySecurity = 0) AND (localitySecurityReason IS NULL) AND (tidinterpreted IN('.implode(',',$sensitiveArr).')) ';
			if($this->collidStr) $sql .= 'AND collid IN('.$this->collidStr.')';
			if($this->conn->query($sql)){
				$status += $this->conn->affected_rows;
			}
			else{
				$errStr = 'WARNING: unable to protect globally rare species; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				$this->outputMsg($errStr,2);
				$status = false;
			}
		}
		return $status;
	}

	private function getSensitiveTaxa(){
		$sensitiveArr = array();
		//Get names on list
		$sql = 'SELECT DISTINCT tid FROM taxa WHERE (SecurityStatus > 0)';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$sensitiveArr[] = $r->tid;
		}
		$rs->free();
		//Get synonyms of names on list
		$sql2 = 'SELECT DISTINCT ts.tid '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted '.
			'WHERE (ts.taxauthid = 1) AND (t.SecurityStatus > 0) AND (t.tid != ts.tid)';
		$rs2 = $this->conn->query($sql2);
		while($r2 = $rs2->fetch_object()){
			$sensitiveArr[] = $r2->tid;
		}
		$rs2->free();
		return $sensitiveArr;
	}

	public function batchProtectStateRareSpecies(){
		$status = 0;
		//Protect state level rare species
		$this->outputMsg('Protecting state level rare species... ',1);
		$sql = 'SELECT clid, locality FROM fmchecklists WHERE type = "rarespp"';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$status += $this->protectStateRareSpecies($r->clid,$r->locality);
		}
		$rs->free();
		return $status;
	}

	public function protectStateRareSpecies($clid,$locality){
		$status = 0;
		$occArr = array();
		$sql = 'SELECT o.occid FROM omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
			'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
			'INNER JOIN fmchklsttaxalink cl ON  ts2.tid = cl.tid '.
			'WHERE (o.localitysecurity IS NULL OR o.localitysecurity = 0) AND (o.localitySecurityReason IS NULL) '.
			'AND (o.stateprovince = "'.$locality.'") AND (cl.clid = '.$clid.') AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$occArr[] = $r->occid;
		}
		$rs->free();

		if($occArr){
			$sql2 = 'UPDATE omoccurrences SET localitysecurity = 1 WHERE occid IN('.implode(',',$occArr).')';
			if($this->conn->query($sql2)){
				$status = $this->conn->affected_rows;
			}
			else{
				$errStr = 'WARNING: unable to protect state level rare species; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				$this->outputMsg($errStr,2);
				$status = false;
			}
		}
		return $status;
	}

	public function getStateProtectionCount($clid, $state){
		$retCnt = 0;
		if(is_numeric($clid) && $state){
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS cnt '.
				'FROM omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
				'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN fmchklsttaxalink cl ON  ts2.tid = cl.tid '.
				'WHERE (o.localitysecurity IS NULL OR o.localitysecurity = 0) AND (o.localitySecurityReason IS NULL) '.
				'AND (o.stateprovince = "'.$state.'") AND (cl.clid = '.$clid.') AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retCnt = $r->cnt;
			}
			$rs->free();
		}
		return $retCnt;
	}

	//Update statistics
	public function updateCollectionStatsFull(){
		if($this->collidStr){
			set_time_limit(600);
			$collArr = explode(',', $this->collidStr);
			foreach($collArr as $collid){
				if(is_numeric($collid)){
					$statsArr = Array();
					$this->outputMsg('Calculating specimen, georeference, family, genera, and species counts... ', 1);
					$sql = 'SELECT COUNT(CASE WHEN t.RankId >= 220 THEN o.occid ELSE NULL END) AS SpecimensCountID,
						COUNT(DISTINCT CASE WHEN t.RankId >= 220 THEN t.SciName ELSE NULL END) AS TotalTaxaCount, COUNT(o.typeStatus) AS TypeCount
						FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID
						WHERE o.collid IN('.$collid.')';
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						$statsArr['SpecimensCountID'] = $r->SpecimensCountID;
						$statsArr['TotalTaxaCount'] = $r->TotalTaxaCount;
						$statsArr['TypeCount'] = $r->TypeCount;
					}
					$rs->free();

					$this->outputMsg('Calculating number of specimens imaged... ', 1);
					$sql = 'SELECT count(DISTINCT o.occid) as imgspeccnt, count(DISTINCT i.imgid) AS imgcnt
						FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid
						WHERE o.collid = '.$collid;
					$rs = $this->conn->query($sql);
					if($r = $rs->fetch_object()){
						$statsArr['imgcnt'] = $r->imgcnt.':'.$r->imgspeccnt;
					}
					$rs->free();

					$this->outputMsg('Calculating genetic resources counts... ', 1);
					$sql = 'SELECT COUNT(CASE WHEN g.resourceurl LIKE "%boldsystems%" THEN o.occid ELSE NULL END) AS boldcnt,
						COUNT(CASE WHEN g.resourceurl LIKE "%ncbi%" THEN o.occid ELSE NULL END) AS gencnt,
						COUNT(CASE WHEN g.resourceurl NOT LIKE "%boldsystems%" AND g.resourceurl NOT LIKE "%ncbi%" THEN o.occid ELSE NULL END) AS geneticcnt
						FROM omoccurrences o INNER JOIN omoccurgenetic g ON o.occid = g.occid
						WHERE o.collid = '.$collid;
					$rs = $this->conn->query($sql);
					if($r = $rs->fetch_object()){
						$statsArr['boldcnt'] = $r->boldcnt;
						$statsArr['gencnt'] = $r->gencnt;
						$statsArr['geneticcnt'] = $r->geneticcnt;
					}
					$rs->free();

					$this->outputMsg('Calculating reference counts... ', 1);
					$sql = 'SELECT count(r.occid) as refcnt FROM omoccurrences o INNER JOIN referenceoccurlink r ON o.occid = r.occid WHERE o.collid = '.$collid;
					$rs = $this->conn->query($sql);
					if($r = $rs->fetch_object()){
						$statsArr['refcnt'] = $r->refcnt;
					}
					$rs->free();

					$this->outputMsg('Calculating counts per family... ', 1);
					$sql = 'SELECT o.family, COUNT(o.occid) AS SpecimensPerFamily, COUNT(o.decimalLatitude) AS GeorefSpecimensPerFamily,
						COUNT(CASE WHEN t.RankId >= 220 THEN o.occid ELSE NULL END) AS IDSpecimensPerFamily,
						COUNT(CASE WHEN t.RankId >= 220 AND o.decimalLatitude IS NOT NULL THEN o.occid ELSE NULL END) AS IDGeorefSpecimensPerFamily
						FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID
						WHERE o.collid = '.$collid.' GROUP BY o.family ';
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						$family = str_replace(array('"',"'"),"",$r->family);
						if($family){
							$statsArr['families'][$family]['SpecimensPerFamily'] = $r->SpecimensPerFamily;
							$statsArr['families'][$family]['GeorefSpecimensPerFamily'] = $r->GeorefSpecimensPerFamily;
							$statsArr['families'][$family]['IDSpecimensPerFamily'] = $r->IDSpecimensPerFamily;
							$statsArr['families'][$family]['IDGeorefSpecimensPerFamily'] = $r->IDGeorefSpecimensPerFamily;
						}
					}
					$rs->free();

					$this->outputMsg('Calculating counts per country... ', 1);
					$sql = 'SELECT o.country, COUNT(o.occid) AS CountryCount, COUNT(o.decimalLatitude) AS GeorefSpecimensPerCountry,
						COUNT(CASE WHEN t.RankId >= 220 THEN o.occid ELSE NULL END) AS IDSpecimensPerCountry,
						COUNT(CASE WHEN t.RankId >= 220 AND o.decimalLatitude IS NOT NULL THEN o.occid ELSE NULL END) AS IDGeorefSpecimensPerCountry
						FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID
						WHERE o.collid = '.$collid.' GROUP BY o.country ';
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						$country = str_replace(array('"',"'"),"",$r->country);
						if($country){
							$statsArr['countries'][$country]['CountryCount'] = $r->CountryCount;
							$statsArr['countries'][$country]['GeorefSpecimensPerCountry'] = $r->GeorefSpecimensPerCountry;
							$statsArr['countries'][$country]['IDSpecimensPerCountry'] = $r->IDSpecimensPerCountry;
							$statsArr['countries'][$country]['IDGeorefSpecimensPerCountry'] = $r->IDGeorefSpecimensPerCountry;
						}
					}
					$rs->free();

					if($statsArr) $this->updateCollectionStats(array('dynamicProperties' => json_encode($statsArr)), $collid);
					$this->updateCollectionStatsBasic($collid);
				}
			}
		}
	}

	public function updateCollectionStatsBasic($collid){
		if(is_numeric($collid)){
			$this->outputMsg('Calculating specimen, georeference, family, genera, and species counts... ',1);
			$statArr = array();
			$sql = 'SELECT COUNT(o.occid) AS SpecimenCount, COUNT(o.decimalLatitude) AS GeorefCount, COUNT(DISTINCT o.family) AS FamilyCount, '.
				'COUNT(DISTINCT CASE WHEN t.RankId >= 180 THEN t.UnitName1 ELSE NULL END) AS GeneraCount, '.
				'COUNT(DISTINCT CASE WHEN t.RankId = 220 THEN t.SciName ELSE NULL END) AS SpeciesCount '.
				'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID '.
				'WHERE o.collid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $collid);
				$stmt->execute();
				$stmt->bind_result($recCnt, $georefCnt, $familyCnt, $genusCnt, $speciesCnt);
				if($stmt->fetch()){
					$statArr['recordcnt'] = $recCnt;
					$statArr['georefcnt'] = $georefCnt;
					$statArr['familycnt'] = $familyCnt;
					$statArr['genuscnt'] = $genusCnt;
					$statArr['speciescnt'] =  $speciesCnt;
				}
				$stmt->close();
			}
			if($statArr) $this->updateCollectionStats($statArr, $collid);
		}
	}

	private function updateCollectionStats($inputData, $collid){
		$status = false;
		if($inputData){
			$paramArr = array();
			$type = '';
			$sql = 'UPDATE omcollectionstats SET ';
			foreach($inputData as $field => $value){
				$sql .= $field.' = ?, ';
				if($field == 'dynamicProperties') $type .= 's';
				else $type .= 'i';
				$paramArr[] = $value;
			}
			$sql .= ' datelastmodified = NOW() WHERE collid = ?';
			$type .= 'i';
			$paramArr[] = $collid;
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param($type, ...$paramArr);
				$stmt->execute();
				if($stmt->error){
					$errStr = 'ERROR updating collection stats table: '.$stmt->error;
					$this->errorArr[] = $errStr;
					$this->outputMsg($errStr,2);
				}
				else $status = true;
				$stmt->close();
			}
		}
		return $status;
	}

	//Setters and getters
	public function setCollidStr($idStr){
		if($idStr && preg_match('/^[\d,]+$/', $idStr)) $this->collidStr = $idStr;
	}

	public function setVerbose($v){
		if($v){
			$this->verbose = true;
		}
		else{
			$this->verbose = false;
		}
	}

	public function getErrorArr(){
		return $this->errorArr;
	}

	//Misc support functions
	private function outputMsg($str, $indent = 0){
		if($this->verbose){
			echo '<li style="margin-left:'.($indent*15).'px;">'.$str.'</li>';
			ob_flush();
			flush();
		}
	}
}
?>