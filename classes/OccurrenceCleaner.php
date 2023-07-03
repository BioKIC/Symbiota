<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
include_once($SERVER_ROOT.'/classes/AgentManager.php');

class OccurrenceCleaner extends Manager{

	private $collid;
	private $obsUid;
	private $featureCount = 0;
	private $googleApi;

	public function __construct(){
		parent::__construct(null,'write');
		$urlPrefix = 'http://';
		if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
		$this->googleApi = $urlPrefix.'maps.googleapis.com/maps/api/geocode/json?sensor=false';
	}

	public function __destruct(){
		parent::__destruct();
	}

	//Search and resolve duplicate specimen records
	public function getDuplicateCatalogNumber($type,$start,$limit = 500){
		//Search is not available for personal specimen management
		$dupArr = array();
		$catArr = array();
		$cnt = 0;
		if($type=='cat'){
			$sql1 = 'SELECT catalognumber '.
				'FROM omoccurrences '.
				'WHERE catalognumber IS NOT NULL AND collid = '.$this->collid;
		}
		else{
			$sql1 = 'SELECT otherCatalogNumbers '.
				'FROM omoccurrences '.
				'WHERE otherCatalogNumbers IS NOT NULL AND collid = '.$this->collid;
		}
		//echo $sql1;
		$rs = $this->conn->query($sql1);
		while($r = $rs->fetch_object()){
			$cn = ($type=='cat'?$r->catalognumber:$r->otherCatalogNumbers);
			if(array_key_exists($cn,$catArr)){
				//Dupe found
				$cnt++;
				if($start < $cnt && !array_key_exists($cn,$dupArr)){
					//Add dupe to array
					$dupArr[$this->cleanInStr($cn)] = '';
					if(count($dupArr) > $limit) break;
				}
			}
			else{
				$catArr[$cn] = '';
			}
		}
		$rs->free();

		$retArr = array();
		if($type=='cat'){
			$sql = 'SELECT o.catalognumber AS dupid, o.occid, o.catalognumber, o.othercatalognumbers, o.family, o.sciname, '.
				'o.recordedby, o.recordnumber, o.associatedcollectors, o.eventdate, o.verbatimeventdate, '.
				'o.country, o.stateprovince, o.county, o.municipality, o.locality, o.datelastmodified '.
				'FROM omoccurrences o '.
				'WHERE o.collid = '.$this->collid.' AND o.catalognumber IN("'.implode('","',array_keys($dupArr)).'") '.
				'ORDER BY o.catalognumber';
		}
		else{
			$sql = 'SELECT o.otherCatalogNumbers AS dupid, o.occid, o.catalognumber, o.othercatalognumbers, o.family, o.sciname, '.
				'o.recordedby, o.recordnumber, o.associatedcollectors, o.eventdate, o.verbatimeventdate, '.
				'o.country, o.stateprovince, o.county, o.municipality, o.locality, o.datelastmodified '.
				'FROM omoccurrences o '.
				'WHERE o.collid = '.$this->collid.' AND o.otherCatalogNumbers IN("'.implode('","',array_keys($dupArr)).'") '.
				'ORDER BY o.otherCatalogNumbers';
		}
		//echo $sql;

		$retArr = $this->getDuplicates($sql);
		return $retArr;
	}

	public function getDuplicateCollectorNumber($start){
		$retArr = array();
		$sql = '';
		if($this->obsUid){
			$sql = 'SELECT o.occid, o.eventdate, recordedby, o.recordnumber '.
				'FROM omoccurrences o INNER JOIN '.
				'(SELECT eventdate, recordnumber FROM omoccurrences GROUP BY eventdate, recordnumber, collid, observeruid '.
				'HAVING Count(*)>1 AND collid = '.$this->collid.' AND observeruid = '.$this->obsUid.
				' AND eventdate IS NOT NULL AND recordnumber IS NOT NULL '.
				'AND recordnumber NOT IN("sn","s.n.","Not Provided","unknown")) intab '.
				'ON o.eventdate = intab.eventdate AND o.recordnumber = intab.recordnumber '.
				'WHERE collid = '.$this->collid.' AND observeruid = '.$this->obsUid.' ';
		}
		else{
			$sql = 'SELECT o.occid, o.eventdate, recordedby, o.recordnumber '.
				'FROM omoccurrences o INNER JOIN '.
				'(SELECT eventdate, recordnumber FROM omoccurrences GROUP BY eventdate, recordnumber, collid '.
				'HAVING Count(*)>1 AND collid = '.$this->collid.
				' AND eventdate IS NOT NULL AND recordnumber IS NOT NULL '.
				'AND recordnumber NOT IN("sn","s.n.","Not Provided","unknown")) intab '.
				'ON o.eventdate = intab.eventdate AND o.recordnumber = intab.recordnumber '.
				'WHERE collid = '.$this->collid.' ';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		$collArr = array();
		while($r = $rs->fetch_object()){
			$nameArr = Agent::parseLeadingNameInList($r->recordedby);
			if(isset($nameArr['last']) && $nameArr['last'] && strlen($nameArr['last']) > 2){
				$lastName = $nameArr['last'];
				$collArr[$r->eventdate][$r->recordnumber][$lastName][] = $r->occid;
			}
		}
		$rs->free();

		//Collection duplicate clusters
		$occidArr = array();
		$cnt = 0;
		foreach($collArr as $ed => $arr1){
			foreach($arr1 as $rn => $arr2){
				foreach($arr2 as $ln => $dupArr){
					if(count($dupArr) > 1){
						//Skip records until start is reached
						if($cnt >= $start){
							$sql = 'SELECT '.$cnt.' AS dupid, o.occid, o.catalognumber, o.othercatalognumbers, o.othercatalognumbers, o.family, o.sciname, o.recordedby, o.recordnumber, '.
								'o.associatedcollectors, o.eventdate, o.verbatimeventdate, o.country, o.stateprovince, o.county, o.municipality, o.locality, datelastmodified '.
								'FROM omoccurrences o '.
								'WHERE occid IN('.implode(',',$dupArr).') ';
							//echo $sql;
							$retArr = array_merge($retArr,$this->getDuplicates($sql));
						}
						if($cnt > ($start+200)) break 3;
						$cnt++;
					}
				}
			}
		}
		return $retArr;
	}

	private function getDuplicates($sql){
		$retArr = array();
		$cnt = 0;
		$dupid = '';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_assoc()){
			if($dupid != $row['dupid']) $cnt++;
			$retArr[$cnt][$row['occid']] = array_change_key_case($row);
			$dupid = $row['dupid'];
		}
		$rs->free();
		return $retArr;
	}

	public function mergeDupeArr($occidArr){
		$status = true;
		$this->verboseMode = 2;
		$editorManager = new OccurrenceEditorManager($this->conn);
		foreach($occidArr as $target => $occArr){
			$mergeArr = array($target);
			foreach($occArr as $source){
				if($source != $target){
					if($editorManager->mergeRecords($target,$source)){
						$mergeArr[] = $source;
					}
					else{
						$this->logOrEcho(trim($editorManager->getErrorStr(),' ;'),1);
						$status = false;
					}
				}
			}
			if(count($mergeArr) > 1){
				$this->logOrEcho('Merged records: '.implode(', ',$mergeArr),1);
			}
		}
		return $status;
	}

	public function hasDuplicateClusters(){
		$retStatus = false;
		$sql = 'SELECT o.occid FROM omoccurrences o INNER JOIN omoccurduplicatelink d ON o.occid = d.occid WHERE (o.collid = '.$this->collid.') LIMIT 1';
		$rs = $this->conn->query($sql);
		if($rs->num_rows) $retStatus = true;
		$rs->free();
		return $retStatus;
	}

    /** Populate omoccurrences.recordedbyid using data from omoccurrences.recordedby.
     */
	public function indexCollectors(){
		//Try to populate using already linked names
		$sql = 'UPDATE omoccurrences o1 INNER JOIN (SELECT DISTINCT recordedbyid, recordedby FROM omoccurrences WHERE recordedbyid IS NOT NULL) o2 ON o1.recordedby = o2.recordedby '.
			'SET o1.recordedbyid = o2.recordedbyid '.
			'WHERE o1.recordedbyid IS NULL';
		$this->conn->query($sql);

		//Query unlinked specimens and try to parse each collector
		$collArr = array();
		$sql = 'SELECT occid, recordedby '.
			'FROM omoccurrences '.
			'WHERE recordedbyid IS NULL';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$collArr[$r->recordedby][] = $r->occid;
		}
		$rs->free();

		foreach($collArr as $collStr => $occidArr){
            // check to see if collector is listed in agents table.
            $sql = "select distinct agentid from agentname where name = ? ";
            if ($stmt = $this->conn->prepare($sql)) {
               $stmt->bind_param('s',$collStr);
               $stmt->execute();
               $stmt->bind_result($agentid);
               $stmt->store_result();
               $matches = $stmt->num_rows;
               $stmt->fetch();
               $stmt->close();
               if ($matches>0) {
                  $recById= $agentid;
               }
               else {
                  // no matches found to collector, add to agent table.
                  $am = new AgentManager();
                  $agent = $am->constructAgentDetType($collStr);
                  if ($agent!=null) {
                     $am->saveNewAgent($agent);
                     $agentid = $agent->getagentid();
                     $recById= $agentid;
                  }
               }
            }
            else {
               throw new Exception("Error preparing query $sql " . $this->conn->error);
            }

			//Add recordedbyid to omoccurrence table
			if($recById){
				$sql = 'UPDATE omoccurrences '.
					'SET recordedbyid = '.$recById.
					' WHERE occid IN('.implode(',',$occidArr).') AND recordedbyid IS NULL ';
				$this->conn->query($sql);
			}
		}
	}

	//Geographic functions
	public function countryCleanFirstStep(){
		//Country cleaning
		echo '<div style="margin-left:15px;">Preparing countries index...</div>';
		flush();
		ob_flush();
		$occArr = array();
		$sql = 'SELECT occid FROM omoccurrences WHERE ((country LIKE " %") OR (country LIKE "% ")) AND collid = '.$this->collid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$occArr[] = $r->occid;
		}
		$rs->free();
		if($occArr){
			$sqlTrim = 'UPDATE omoccurrences SET country = trim(country) WHERE (occid IN('.implode(',',$occArr).'))';
			$this->conn->query($sqlTrim);
		}

		$sqlEmpty = 'UPDATE omoccurrences SET country = NULL WHERE (country = "")';
		$this->conn->query($sqlEmpty);

		//State cleaning
		echo '<div style="margin-left:15px;">Preparing state index...</div>';
		flush();
		ob_flush();
		unset($occArr);
		$occArr = array();
		$sql = 'SELECT occid FROM omoccurrences WHERE ((stateprovince LIKE " %") OR (stateprovince LIKE "% ")) AND collid = '.$this->collid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$occArr[] = $r->occid;
		}
		$rs->free();
		if($occArr){
			$sqlTrim = 'UPDATE omoccurrences SET stateprovince = trim(stateprovince) WHERE (occid IN('.implode(',',$occArr).'))';
			$this->conn->query($sqlTrim);
		}

		$sqlEmpty = 'UPDATE omoccurrences SET stateprovince = NULL WHERE (stateprovince = "")';
		$this->conn->query($sqlEmpty);

		//County cleaning
		echo '<div style="margin-left:15px;">Preparing county index...</div>';
		flush();
		ob_flush();
		unset($occArr);
		$occArr = array();
		$sql = 'SELECT occid FROM omoccurrences WHERE ((county LIKE " %") OR (county LIKE "% ")) AND collid = '.$this->collid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$occArr[] = $r->occid;
		}
		$rs->free();
		if($occArr){
			$sqlTrim = 'UPDATE omoccurrences SET county = trim(county) WHERE (occid IN('.implode(',',$occArr).'))';
			$this->conn->query($sqlTrim);
		}

		$sqlEmpty = 'UPDATE omoccurrences SET county = NULL WHERE (county = "")';
		$this->conn->query($sqlEmpty);

		//Municipality cleaning
		/*
		echo '<div style="margin-left:15px;">Preparing municipality index...</div>';
		flush();
		ob_flush();
		unset($occArr);
		$occArr = array();
		$sql = 'SELECT occid FROM omoccurrences WHERE ((municipality LIKE " %") OR (municipality LIKE "% ")) AND collid = '.$this->collid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$occArr[] = $r->occid;
		}
		$rs->free();
		if($occArr){
			$sqlTrim = 'UPDATE omoccurrences SET municipality = trim(municipality) WHERE (occid IN('.implode(',',$occArr).'))';
			echo $sqlTrim.'<br/>';
			$this->conn->query($sqlTrim);
		}

		$sqlEmpty = 'UPDATE omoccurrences SET municipality = NULL WHERE (municipality = "")';
		$this->conn->query($sqlEmpty);
		*/
	}

	//Bad countries
	public function getBadCountryCount(){
		$retCnt = 0;
		$sql = 'SELECT COUNT(DISTINCT o.country) AS cnt '.
			'FROM omoccurrences o LEFT JOIN lkupcountry l ON o.country = l.countryname '.
			'WHERE o.country IS NOT NULL AND o.collid = '.$this->collid.' AND l.countryid IS NULL ';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getBadCountryArr(){
		$retArr = array();
		$sql = 'SELECT country, count(o.occid) as cnt '.
			'FROM omoccurrences o LEFT JOIN lkupcountry l ON o.country = l.countryname '.
			'WHERE o.country IS NOT NULL AND o.collid = '.$this->collid.' AND l.countryid IS NULL '.
			'GROUP BY o.country ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->country] = $r->cnt;
		}
		$rs->free();
		$this->featureCount = count($retArr);
		ksort($retArr);
		return $retArr;
	}

	public function getGoodCountryArr($includeStates = false){
		$retArr = array();
		if($includeStates){
			$sql = 'SELECT c.countryname, s.statename FROM lkupcountry c LEFT JOIN lkupstateprovince s ON c.countryid = s.countryid ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->countryname][] = $r->statename;
			}
			$rs->free();
			ksort($retArr);
		}
		else{
			$sql = 'SELECT countryname FROM lkupcountry';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[] = $r->countryname;
			}
			$rs->free();
			sort($retArr);
			$retArr[] = 'unknown';
		}
		return $retArr;
	}

	public function getNullCountryNotStateCount(){
		$retCnt = 0;
		$sql = 'SELECT COUNT(DISTINCT stateprovince) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collid.') AND (country IS NULL) AND (stateprovince IS NOT NULL)';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getNullCountryNotStateArr(){
		$retArr = array();
		$sql = 'SELECT stateprovince, COUNT(occid) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collid.') AND (country IS NULL) AND (stateprovince IS NOT NULL) '.
			'GROUP BY stateprovince';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[ucwords(strtolower($r->stateprovince))] = $r->cnt;
		}
		$rs->free();
		$this->featureCount = count($retArr);
		ksort($retArr);
		return $retArr;
	}

	//States cleaning functions
	public function getBadStateCount($country = ''){
		$retCnt = array();
		$sql = 'SELECT COUNT(DISTINCT o.stateprovince) as cnt '.$this->getBadStateSqlBase();
		if($country) $sql .= 'AND o.country = "'.$this->cleanInStr($country).'" ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getBadStateArr(){
		$retArr = array();
		$sqlFrag = $this->getBadStateSqlBase();
		if($sqlFrag){
			$sql = 'SELECT o.country, o.stateprovince, count(DISTINCT o.occid) as cnt '.
				$this->getBadStateSqlBase().
				'GROUP BY o.stateprovince ';
			$rs = $this->conn->query($sql);
			$cnt = 0;
			while($r = $rs->fetch_object()){
				$retArr[$r->country][ucwords(strtolower($r->stateprovince))] = $r->cnt;
				$cnt++;
			}
			$rs->free();
			$this->featureCount = $cnt;
			ksort($retArr);
		}
		else{
			$this->errorMessage = '';
		}
		return $retArr;
	}

	private function getBadStateSqlBase(){
		$retStr = '';
		$countryArr = array();
		$sql = 'SELECT DISTINCT c.countryname FROM lkupcountry c INNER JOIN lkupstateprovince s ON c.countryid = s.countryid ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$countryArr[] = $r->countryname;
		}
		$rs->free();

		if($countryArr){
			$retStr = 'FROM omoccurrences o LEFT JOIN lkupstateprovince l ON o.stateprovince = l.statename '.
				'WHERE (o.country IN("'.implode('","', $countryArr).'")) AND (o.stateprovince IS NOT NULL) AND (o.collid = '.$this->collid.') AND (l.stateid IS NULL) ';
		}

		return $retStr;
	}

	public function getGoodStateArr($includeCounties = false){
		$retArr = array();
		if($includeCounties){
			$sql = 'SELECT c.countryname, s.statename, co.countyname '.
				'FROM lkupstateprovince s INNER JOIN lkupcountry c ON s.countryid = c.countryid '.
				'LEFT JOIN lkupcounty co ON s.stateid = co.stateid ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[strtoupper($r->countryname)][ucwords(strtolower($r->statename))][] = str_replace(array(' county',' co.',' co'),'',strtolower($r->countyname));
			}
			$rs->free();
		}
		else{
			$sql = 'SELECT c.countryname, s.statename '.
				'FROM lkupstateprovince s INNER JOIN lkupcountry c ON s.countryid = c.countryid ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->countryname][] = $r->statename;
			}
			$rs->free();
		}
		ksort($retArr);
		$retArr[] = 'unknown';
		return $retArr;
	}

	public function getNullStateNotCountyCount(){
		$retCnt = 0;
		$sql = 'SELECT COUNT(DISTINCT county) AS cnt '.$this->getNullStateNotCountySqlFrag();
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getNullStateNotCountyArr(){
		$retArr = array();
		$sql = 'SELECT country, county, COUNT(occid) AS cnt '.$this->getNullStateNotCountySqlFrag().'GROUP BY county';
		$rs = $this->conn->query($sql);
		$cnt = 0;
		while($r = $rs->fetch_object()){
			$retArr[strtoupper($r->country)][$r->county] = $r->cnt;
			$cnt++;
		}
		$rs->free();
		$this->featureCount = $cnt;
		ksort($retArr);
		return $retArr;
	}

	private function getNullStateNotCountySqlFrag(){
		$retStr = 'FROM omoccurrences '.
			'WHERE (collid = '.$this->collid.') AND (stateprovince IS NULL) AND (county IS NOT NULL) AND (country IS NOT NULL) ';
		return $retStr;
	}

	//Bad Counties
	public function getBadCountyCount($state = ''){
		$retCnt = array();
		$sql = 'SELECT COUNT(DISTINCT o.county) as cnt '.$this->getBadCountySqlFrag();
		if($state) $sql .= 'AND o.stateprovince = "'.$this->cleanInStr($state).'" ';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getBadCountyArr(){
		$retArr = array();
		$sql = 'SELECT o.country, o.stateprovince, o.county, count(o.occid) as cnt '.$this->getBadCountySqlFrag().'GROUP BY o.country, o.stateprovince, o.county ';
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		$cnt = 0;
		while($r = $rs->fetch_object()){
			$retArr[strtoupper($r->country)][ucwords(strtolower($r->stateprovince))][$r->county] = $r->cnt;
			$cnt++;
		}
		$rs->free();
		$this->featureCount = $cnt;
		//ksort($retArr);
		return $retArr;
	}

	private function getBadCountySqlFrag(){
		$retStr = '';
		$stateyArr = array();
		$sql = 'SELECT DISTINCT s.statename '.
			'FROM lkupstateprovince s INNER JOIN lkupcounty co ON s.stateid = co.stateid ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$stateyArr[] = $r->statename;
		}
		$rs->free();
		if($stateyArr){
			$retStr = 'FROM omoccurrences o LEFT JOIN lkupcounty l ON o.county = l.countyname '.
			'WHERE (o.county IS NOT NULL) AND (o.country = "USA") AND (o.stateprovince IN("'.implode('","', $stateyArr).'")) '.
			'AND (o.collid = '.$this->collid.') AND (l.countyid IS NULL) ';
		}
		return $retStr;
	}

	public function getGoodCountyArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT statename, REPLACE(countyname," County","") AS countyname '.
			'FROM lkupcounty c INNER JOIN lkupstateprovince s ON c.stateid = s.stateid '.
			'ORDER BY c.countyname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[strtolower($r->statename)][] = $r->countyname;
		}
		$rs->free();
		$retArr[] = 'unknown';
		return $retArr;
	}

	public function getNullCountyNotLocalityCount(){
		$retCnt = 0;
		$sql = 'SELECT COUNT(DISTINCT locality) AS cnt '.$this->getNullCountyNotLocalitySqlFrag();
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getNullCountyNotLocalityArr(){
		$retArr = array();
		$sql = 'SELECT country, stateprovince, locality, COUNT(occid) AS cnt '.
			$this->getNullCountyNotLocalitySqlFrag().
			'GROUP BY country, stateprovince, locality';
		$rs = $this->conn->query($sql);
		$cnt = 0;
		while($r = $rs->fetch_object()){
			$locStr = $r->locality;
			//if(strlen($locStr) > 40) $locStr = substr($locStr,0,40).'...';
			$retArr[$r->country][ucwords(strtolower($r->stateprovince))][$locStr] = $r->cnt;
			$cnt++;
		}
		$rs->free();
		$this->featureCount = $cnt;
		ksort($retArr);
		return $retArr;
	}

	private function getNullCountyNotLocalitySqlFrag(){
		$retStr = 'FROM omoccurrences '.
			'WHERE (collid = '.$this->collid.') AND (county IS NULL) AND (locality IS NOT NULL) '.
			'AND country IN("USA","United States") AND (stateprovince IS NOT NULL) AND (stateprovince NOT IN("District Of Columbia","DC")) ';
		return $retStr;
	}

	//Coordinate field verifier
	public function getCoordStats(){
		$retArr = array();
		//Get count georeferenced
		$sql = 'SELECT count(*) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid IN('.$this->collid.')) AND (decimallatitude IS NOT NULL) AND (decimallongitude IS NOT NULL)';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['coord'] = $r->cnt;
		}
		$rs->free();

		//Get count not georeferenced
		$sql = 'SELECT count(*) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid IN('.$this->collid.')) AND (decimallatitude IS NULL) AND (decimallongitude IS NULL)';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['noCoord'] = $r->cnt;
		}
		$rs->free();

		//Count not georeferenced with verbatimCoordinates info
		$sql = 'SELECT count(*) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid IN('.$this->collid.')) AND (decimallatitude IS NULL) AND (decimallongitude IS NULL) AND (verbatimcoordinates IS NOT NULL)';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['noCoord_verbatim'] = $r->cnt;
		}
		$rs->free();

		//Count not georeferenced without verbatimCoordinates info
		$sql = 'SELECT count(*) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid IN('.$this->collid.')) AND (decimallatitude IS NULL) AND (decimallongitude IS NULL) AND (verbatimcoordinates IS NULL)';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['noCoord_noVerbatim'] = $r->cnt;
		}
		$rs->free();
		return $retArr;
	}

	public function getUnverifiedByCountry(){
		$retArr = array();
		$sql = 'SELECT country, count(occid) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid IN('.$this->collid.')) AND (decimallatitude IS NOT NULL) AND (decimallongitude IS NOT NULL) AND country IS NOT NULL '.
			'AND (occid NOT IN(SELECT occid FROM omoccurverification WHERE category = "coordinate")) '.
			'GROUP BY country';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->country] = $r->cnt;
		}
		$rs->free();
		return $retArr;
	}

	public function verifyCoordAgainstPolitical($queryCountry){
		set_time_limit(3600);
		$recCnt = 0;
		$googleCallCnt = 0;
		echo '<ul>';
		echo '<li>Starting coordinate crawl...</li>';
		$sql = 'SELECT occid, country, stateprovince, county, decimallatitude, decimallongitude '.
			'FROM omoccurrences '.
			'WHERE (collid IN('.$this->collid.')) AND (decimallatitude IS NOT NULL) AND (decimallongitude IS NOT NULL) AND (country = "'.$queryCountry.'") '.
			'AND (occid NOT IN(SELECT occid FROM omoccurverification WHERE category = "coordinate")) '.
			'ORDER BY decimallatitude, decimallongitude '.
			'LIMIT 50000';
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		$previousCoordStr = '';
		while($r = $rs->fetch_object()){
			echo '<li>Checking occurrence <a href="../editor/occurrenceeditor.php?occid='.$r->occid.'" target="_blank">'.$r->occid.'</a>...</li>';
			$recCnt++;
			if($previousCoordStr != $r->decimallatitude.','.$r->decimallongitude){
				$googleUnits = $this->callGoogleApi($r->decimallatitude, $r->decimallongitude);
				$googleCallCnt++;
				$previousCoordStr = $r->decimallatitude.','.$r->decimallongitude;
			}
			$ranking = 0;
			$protocolStr = '';
			if(isset($googleUnits['country'])){
				if($this->countryUnitsEqual($googleUnits['country'],$r->country)){
					$ranking = 2;
					$protocolStr = 'GoogleApiMatch:countryEqual';
					if(isset($googleUnits['state'])){
						if($this->unitsEqual($googleUnits['state'], $r->stateprovince)){
							$ranking = 5;
							$protocolStr = 'GoogleApiMatch:stateEqual';
							if(isset($googleUnits['county'])){
								if($this->countyUnitsEqual($googleUnits['county'], $r->county)){
									$ranking = 7;
									$protocolStr = 'GoogleApiMatch:countyEqual';
								}
								else{
									echo '<li style="margin-left:15px;">County not equal (source: '.$r->county.'; Google value: '.$googleUnits['county'].')</li>';
								}
							}
							else{
								echo '<li style="margin-left:15px;">County not provided by Google</li>';
							}
						}
						else{
							echo '<li style="margin-left:15px;">State/Province not equal (source: '.$r->stateprovince.'; Google value: '.$googleUnits['state'].')</li>';
						}
					}
					else{
						echo '<li style="margin-left:15px;">State/Province not provided by Google</li>';
					}
				}
				else{
					echo '<li style="margin-left:15px;">Country not equal (source: '.$r->country.'; Google value: '.$googleUnits['country'].')</li>';
				}
			}
			else{
				echo '<li style="margin-left:15px;">Country not provided by Google</li>';
			}
			if($ranking){
				$this->setVerification($r->occid, 'coordinate', $ranking, $protocolStr);
				echo '<li style="margin-left:15px;">Verification status set (rank: '.$ranking.', '.$protocolStr.')</li>';
			}
			else{
				echo '<li style="margin-left:15px;">Unable to set verification status</li>';
			}
			if($recCnt%100 == 0) echo '<div><b>Processing count: '.$recCnt.' (Google calls '.$googleCallCnt.')</b></div>';
			flush();
			ob_flush();
		}
		$rs->free();
	}

	private function callGoogleApi($lat, $lng){
		$retArr = array();
		$apiUrl = $this->googleApi.'&latlng='.$lat.','.$lng;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_URL, $apiUrl);

		$data = curl_exec($curl);
		curl_close($curl);

		//Extract country, state, and county from results
		$dataObj = json_decode($data);
		$retArr['status'] = $dataObj->status;
		if($dataObj->status == "OK"){
			$rs = $dataObj->results[0];
			if($rs->address_components){
				$compArr = $rs->address_components;
				foreach($compArr as $compObj){
					if($compObj->long_name && $compObj->types){
						$longName = $compObj->long_name;
						$types = $compObj->types;
						if($types[0] == "country"){
							$retArr['country'] = $longName;
						}
						elseif($types[0] == "administrative_area_level_1"){
							$retArr['state'] = $longName;
						}
						elseif($types[0] == "administrative_area_level_2"){
							$retArr['county'] = $longName;
						}
					}
				}
			}
		}
		else{
			echo '<li style="margin-left:15px;">Unable to get return from Google API (status: '.$dataObj->status.')</li>';
		}
		return $retArr;
	}

	private function unitsEqual($googleTerm, $dbTerm){
		$googleTerm = strtolower(trim($googleTerm));
		$dbTerm = strtolower(trim($dbTerm));

		if($googleTerm == $dbTerm) return true;
		return false;
	}

	private function countryUnitsEqual($countryGoogle,$countryDb){

		if($this->unitsEqual($countryGoogle,$countryDb)) return true;

		$countryGoogle = strtolower(trim($countryGoogle));
		$countryDb = strtolower(trim($countryDb));

		$synonymArr = array(array('united states','usa','united states of america','u.s.a.'));

		foreach($synonymArr as $synArr){
			if(in_array($countryGoogle, $synArr)){
				if(in_array($countryDb, $synArr)) return true;
			}
		}
		return false;
	}

	private function countyUnitsEqual($countyGoogle,$countyDb){
		$countyGoogle = strtolower(trim($countyGoogle));
		$countyDb = strtolower(trim($countyDb));

		$countyGoogle = trim(str_replace(array('county','parish'), '', $countyGoogle));
		if(strpos($countyDb,$countyGoogle) !== false) return true;

		return false;
	}

	private function setVerification($occid, $category, $ranking, $protocol = '', $source = '', $notes = ''){
		$sql = 'INSERT INTO omoccurverification(occid, category, ranking, protocol, source, notes, uid) '.
			'VALUES('.$occid.',"'.$category.'",'.$ranking.','.
			($protocol?'"'.$protocol.'"':'NULL').','.
			($source?'"'.$source.'"':'NULL').','.
			($notes?'"'.$notes.'"':'NULL').','.
			$GLOBALS['SYMB_UID'].')';
		if(!$this->conn->query($sql)){
			$this->errorMessage = 'ERROR thrown setting occurrence verification: '.$this->conn->error;
			echo '<li style="margin-left:15px;">'.$this->errorMessage.'</li>';
		}
	}

	//General ranking functions
	public function getCategoryList(){
		$retArr = array();
		$sql = 'SELECT DISTINCT v.category '.
			'FROM omoccurverification v INNER JOIN omoccurrences o ON v.occid = o.occid '.
			'WHERE (o.collid IN('.$this->collid.'))';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->category;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	public function getRankingStats($category){
		$retArr = array();
		$category = $this->cleanInStr($category);
		$sql = 'SELECT o.collid, v.category, v.ranking, v.protocol, COUNT(v.occid) as cnt '.
			'FROM omoccurverification v INNER JOIN omoccurrences o ON v.occid = o.occid '.
			'WHERE (o.collid IN('.$this->collid.')) AND v.category = "'.$category.'" '.
			'GROUP BY o.collid, v.category, v.ranking, v.protocol';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->category][$r->ranking][$r->protocol] = $r->cnt;
		}
		$rs->free();
		if($category){
			//Get unranked count
			$sql = 'SELECT COUNT(occid) AS cnt '.
				'FROM omoccurrences '.
				'WHERE (collid IN('.$this->collid.')) AND (decimallatitude IS NOT NULL) AND (occid NOT IN(SELECT occid FROM omoccurverification WHERE category = "'.$category.'"))';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr[$category]['unverified'][''] = $r->cnt;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getOccurList($category, $ceilingRank, $floorRank = 0){
		$retArr = array();
		if(is_numeric($ceilingRank) && is_numeric($floorRank)){
			$sql = 'SELECT v.ovsid, v.occid, v.category, v.ranking, v.protocol, v.source, v.uid, v.notes, v.initialtimestamp '.
				'FROM omoccurverification v INNER JOIN omoccurrences o ON v.occid = o.occid '.
				'WHERE (o.collid IN('.$this->collid.')) AND (v.category = "'.$this->cleanInStr($category).'") '.
				'AND (v.ranking BETWEEN '.$floorRank.' AND '.$ceilingRank.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){

			}
			$rs->free();
		}
		return $retArr;
	}

	public function getOccurrenceRankingArr($category, $ranking){
		$retArr = array();
		if(is_numeric($ranking)){
			$sql = 'SELECT DISTINCT v.occid, u.username, v.initialtimestamp '.
				'FROM omoccurverification v INNER JOIN omoccurrences o ON v.occid = o.occid '.
				'INNER JOIN users u ON v.uid = u.uid '.
				'WHERE (o.collid IN('.$this->collid.')) AND (v.category = "'.$this->cleanInStr($category).'") AND (ranking = '.$ranking.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid]['username'] = $r->username;
				$retArr[$r->occid]['ts'] = $r->initialtimestamp;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getRankList(){
		$retArr = array();
		$sql = 'SELECT DISTINCT v.ranking FROM omoccurverification v INNER JOIN omoccurrences o ON v.occid = o.occid WHERE (o.collid IN('.$this->collid.'))';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->ranking;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	//General field updater
	public function updateField($fieldName, $oldValue, $newValue, $conditionArr = null){
		if(is_numeric($this->collid) && $fieldName && $newValue){
			$editorManager = new OccurrenceEditorManager($this->conn);
			$qryArr = array('cf1'=>'collid','ct1'=>'EQUALS','cv1'=>$this->collid);
			if($conditionArr){
				$cnt = 2;
				foreach($conditionArr as $k => $v){
					$qryArr['cf'.$cnt] = $k;
					if($v == '--ISNULL--'){
						$qryArr['ct'.$cnt] = 'NULL';
						$qryArr['cv'.$cnt] = '';
					}
					else{
						$qryArr['ct'.$cnt] = 'EQUALS';
						$qryArr['cv'.$cnt] = $v;
					}
					$cnt++;
					if($cnt > 4) break;
				}
			}
			$editorManager->setQueryVariables($qryArr);
			$editorManager->batchUpdateField($fieldName,$oldValue,$newValue,false);
		}
		return true;
	}

	//Setters and getters
	public function setCollId($collid){
		if(preg_match('/^[\d,]+$/', $collid)){
			$this->collid = $collid;
		}
	}

	public function setObsuid($obsUid){
		if(is_numeric($obsUid)){
			$this->obsUid = $obsUid;
		}
	}

	public function getFeatureCount(){
		return $this->featureCount;
	}

	//Misc fucntions
	public function getCollMap(){
		$retArr = Array();
		$sql = 'SELECT collid, CONCAT_WS("-",institutioncode, collectioncode) AS code, collectionname, icon, colltype, managementtype FROM omcollections ';
		if($this->collid) $sql .= 'WHERE (collid IN('.$this->collid.')) ';
		$sql .= 'ORDER BY collectionname,institutioncode,collectioncode';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->collid]['code'] = $row->code;
			$retArr[$row->collid]['collectionname'] = $row->collectionname;
			$retArr[$row->collid]['icon'] = $row->icon;
			$retArr[$row->collid]['colltype'] = $row->colltype;
			$retArr[$row->collid]['managementtype'] = $row->managementtype;
		}
		$rs->free();
		return $retArr;
	}
}
?>