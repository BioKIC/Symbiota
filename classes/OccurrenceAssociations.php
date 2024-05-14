<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/Manager.php');

class OccurrenceAssociations extends Manager {

 	public function __construct(){
		parent::__construct(null,'write');
 	}

 	public function __destruct(){
		parent::__destruct();
 	}

	public function parseAssociatedTaxa($collid = 0){
		if(!is_numeric($collid)){
			echo '<div><b>FAIL ERROR: abort process</b></div>';
			return;
		}
		set_time_limit(900);
		echo '<ul>';
		echo '<li>Starting to parse associated species text blocks </li>';
		ob_flush();
		flush();
		$sql = 'SELECT o.occid, o.associatedtaxa '.
			'FROM omoccurrences o LEFT JOIN omoccurassociations a ON o.occid = a.occid '.
			'WHERE (o.associatedtaxa IS NOT NULL) AND (o.associatedtaxa <> "") AND (a.occid IS NULL) ';
		if($collid && is_numeric($collid)) $sql .= 'AND (o.collid = '.$collid.') ';
		//$sql .= ' LIMIT 100';
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		echo '<li>Parsing new associated species text blocks (target count: '.$rs->num_rows.')... </li>';
		ob_flush();
		flush();
		$cnt = 1;
		while($r = $rs->fetch_object()){
			$assocArr = $this->parseAssocSpecies($r->associatedtaxa,$r->occid);
			if($cnt%5000 == 0) echo '<li style="margin-left:10px">'.$cnt.' specimens parsed</li>';
			$cnt++;
		}
		$rs->free();

		//Populate tid field using taxa table
		echo '<li>Populate tid field using taxa table... </li>';
		ob_flush();
		flush();
		$sql2 = '';
		if($collid){
			$sql2 = 'UPDATE omoccurassociations a INNER JOIN taxa t ON a.verbatimsciname = t.sciname '.
				'INNER JOIN omoccurrences o ON a.occid = o.occid '.
				'SET a.tid = t.tid '.
				'WHERE a.tid IS NULL AND (o.collid = '.$collid.') ';
		}
		else{
			$sql2 = 'UPDATE omoccurassociations a INNER JOIN taxa t ON a.verbatimsciname = t.sciname SET a.tid = t.tid WHERE a.tid IS NULL';
		}
		if(!$this->conn->query($sql2)){
			echo '<li style="margin-left:20px;">Unable to populate tid field using taxa table: '.$this->conn->error.'</li>';
			//echo '<li style="margin-left:20px;">'.$sql2.'</li>';
		}

		//Populate tid field using taxavernaculars table
		echo '<li>Populate tid field using taxavernaculars table... </li>';
		ob_flush();
		flush();
		$sql3 = '';
		if($collid){
			$sql3 = 'UPDATE omoccurassociations a INNER JOIN taxavernaculars v ON a.verbatimsciname = v.vernacularname '.
				'INNER JOIN omoccurrences o ON a.occid = o.occid '.
				'SET a.tid = v.tid '.
				'WHERE (a.tid IS NULL) AND (o.collid = '.$collid.') ';
		}
		else{
			$sql3 = 'UPDATE omoccurassociations a INNER JOIN taxavernaculars v ON a.verbatimsciname = v.vernacularname SET a.tid = v.tid WHERE a.tid IS NULL ';
		}
		if(!$this->conn->query($sql3)){
			echo '<li style="margin-left:20px;">Unable to populate tid field using taxavernaculars table: '.$this->conn->error.'</li>';
			//echo '<li style="margin-left:20px;">'.$sql3.'</li>';
		}

		//Populate tid field by linking back to omoccurassociations table
		//This assumes that tids are correct; in future verificationscore field can be used to select only those that have been verified
		echo '<li>Populate tid field by linking back to omoccurassociations table... </li>';
		ob_flush();
		flush();
		$sql4 = '';
		if($collid){
			$sql4 = 'UPDATE omoccurassociations a INNER JOIN omoccurassociations a2 ON a.verbatimsciname = a2.verbatimsciname '.
				'INNER JOIN omoccurrences o ON a.occid = o.occid '.
				'SET a.tid = a2.tid '.
				'WHERE (a.tid IS NULL) AND (a2.tid IS NOT NULL) AND (o.collid = '.$collid.') ';
		}
		else{
			$sql4 = 'UPDATE omoccurassociations a INNER JOIN omoccurassociations a2 ON a.verbatimsciname = a2.verbatimsciname '.
				'SET a.tid = a2.tid '.
				'WHERE a.tid IS NULL AND a2.tid IS NOT NULL ';
		}
		if(!$this->conn->query($sql4)){
			echo '<li style="margin-left:20px;">Unable to populate tid field relinking back to omoccurassociations table: '.$this->conn->error.'</li>';
			//echo '<li style="margin-left:20px;">'.$sql4.'</li>';
		}

		//Lets get the harder ones
		echo '<li>Mining database for the more difficult matches... </li>';
		ob_flush();
		flush();
		$sql5 = '';
		if($collid) $sql5 = 'SELECT DISTINCT a.verbatimsciname FROM omoccurassociations a INNER JOIN omoccurrences o ON a.occid = o.occid WHERE (a.tid IS NULL) AND (o.collid = '.$collid.') ';
		else $sql5 = 'SELECT DISTINCT verbatimsciname FROM omoccurassociations WHERE tid IS NULL ';
		$rs5 = $this->conn->query($sql5);
		while($r5 = $rs5->fetch_object()){
			$verbStr = $r5->verbatimsciname;
			$tid = $this->mineAssocSpeciesMatch($verbStr);
			if($tid){
				$sql5b = 'UPDATE omoccurassociations SET tid = '.$tid.' WHERE tid IS NULL AND verbatimsciname = "'.$verbStr.'"';
				if(!$this->conn->query($sql5b)){
					echo '<li style="margin-left:20px;">Unable to populate NULL tid field: '.$this->conn->error.'</li>';
					//echo '<li style="margin-left:20px;">'.$sql5b.'</li>';
				}
			}
		}
		$rs5->free();

		echo '<li>DONE!</li>';
		echo '</ul>';
		ob_flush();
		flush();
	}

	private function parseAssocSpecies($assocSpeciesStr,$occid){
		$parseArr = array();
		if($assocSpeciesStr){
			//Separate associated species
			$assocSpeciesStr = str_replace(array('&',' and ',';'),',',$assocSpeciesStr);
			$assocArr = explode(',',$assocSpeciesStr);
			//Add to return array
			foreach($assocArr as $v){
				$vStr = trim($v,'."-()[]:#\' ');
				if(substr($vStr,-3) == ' sp') $vStr = substr($vStr,0,strlen($vStr)-3);
				if(substr($vStr,-4) == ' spp') $vStr = substr($vStr,0,strlen($vStr)-4);
				$vStr = preg_replace('/\s\s+/', ' ',$vStr);
				if($vStr){
					//If genus is abbreviated (e.g. P. ponderosa), try to get genus from previous entry
					if(preg_match('/^([A-Z]{1})\.{0,1}\s{1}([a-z]*)$/',$vStr,$m)){
						//Iterate through parseArr in reverse until match is found
						$cnt = 0;
						for($i = (count($parseArr)-1); $i >= 0; $i--){
							if(preg_match('/^('.$m[1].'{1}[a-z]+)\s+/',$vStr,$m2)){
								$vStr = $m2[1].' '.$m[2];
								//Possible code to add: verify that name is in taxa tables
								break;
							}
							if($cnt > 3) break;
							$cnt++;
						}
					}
					$parseArr[] = $vStr;
				}
			}
			//Database verbatim values
			$this->databaseAssocSpecies($parseArr,$occid);
		}
	}

	private function databaseAssocSpecies($assocArr, $occid){
		if($assocArr){
			$sql = 'INSERT INTO omoccurassociations(occid, verbatimsciname, relationship) VALUES';
			foreach($assocArr as $aStr){
				$sql .= '('.$occid.',"'.$this->conn->real_escape_string($aStr).'","associatedSpecies"), ';
			}
			$sql = trim($sql,', ');
			//echo $sql; exit;
			if(!$this->conn->query($sql)){
				echo '<li style="margin-left:20px;">ERROR adding assocaited values (<a href="../individual/index.php?occid=' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>): ' . htmlspecialchars($this->conn->error, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</li>';
				//echo '<li style="margin-left:20px;">SQL: '.$sql.'</li>';
			}
		}
	}

	private function mineAssocSpeciesMatch($verbStr){
		$retTid = 0;
		//Pattern: P. ponderosa
		if(preg_match('/^([A-Z]{1})\.{0,1}\s{1}([a-z]*)$/',$verbStr,$m)){
			$sql = 'SELECT tid, sciname FROM taxa WHERE unitname1 LIKE "'.$m[1].'%" AND unitname2 = "'.$m[2].'" AND rankid = 220';
			$rs = $this->conn->query($sql);
			if($rs->num_rows == 1){
				if($r = $rs->fetch_object()){
					$retTid = $r->tid;
				}
			}
			$rs->free();
		}
		//Add code that uses Levenshtein distance matching on taxa table


		//Add code that uses Levenshtein distance matching on taxavernaculars table


		return $retTid;
	}

	public function getParsingStats($collid){
		$retArr = array();
		//Get parsed count
		$sqlZ = 'SELECT COUNT(DISTINCT o.occid) as cnt '.
			'FROM omoccurrences o INNER JOIN omoccurassociations a ON o.occid = a.occid '.
			'WHERE (a.relationship = "associatedSpecies") ';
		if($collid){
			$sqlZ .= 'AND (o.collid = '.$collid.') ';
		}
		$rsZ = $this->conn->query($sqlZ);
		while($rZ = $rsZ->fetch_object()){
			$retArr['parsed'] = $rZ->cnt;
		}
		$rsZ->free();

		//Get unparsed count
		$sqlA = 'SELECT count(o.occid) as cnt '.
			'FROM omoccurrences o LEFT JOIN omoccurassociations a ON o.occid = a.occid '.
			'WHERE (o.associatedtaxa IS NOT NULL) AND (o.associatedtaxa <> "") AND (a.occid IS NULL) ';
		if($collid){
			$sqlA .= 'AND (o.collid = '.$collid.') ';
		}
		$rsA = $this->conn->query($sqlA);
		while($rA = $rsA->fetch_object()){
			$retArr['unparsed'] = $rA->cnt;
		}
		$rsA->free();

		//Get field count for parsing failures
		$sqlB = 'SELECT count(a.occid) as cnt '.
			'FROM omoccurrences o INNER JOIN omoccurassociations a ON o.occid = a.occid '.
			'WHERE (a.verbatimsciname IS NOT NULL) AND (a.tid IS NULL) ';
		if($collid){
			$sqlB .= 'AND (o.collid = '.$collid.') ';
		}
		$rsB = $this->conn->query($sqlB);
		while($rB = $rsB->fetch_object()){
			$retArr['failed'] = $rB->cnt;
		}
		$rsB->free();

		//Get specimen count for parsing failures
		$sqlC = 'SELECT count(DISTINCT o.occid) as cnt '.
			'FROM omoccurrences o INNER JOIN omoccurassociations a ON o.occid = a.occid '.
			'WHERE (a.verbatimsciname IS NOT NULL) AND (a.tid IS NULL) ';
		if($collid){
			$sqlC .= 'AND (o.collid = '.$collid.') ';
		}
		$rsC = $this->conn->query($sqlC);
		while($rC = $rsC->fetch_object()){
			$retArr['failedOccur'] = $rC->cnt;
		}
		$rsC->free();
		return $retArr;
	}

	//Misc support functions
	public function getCollectionMetadata($collid){
		$retArr = array();
		if(is_numeric($collid)){
			$sql = 'SELECT institutioncode, collectioncode, collectionname, colltype, managementtype FROM omcollections WHERE collid = '.$collid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['instcode'] = $r->institutioncode;
				$retArr['collcode'] = $r->collectioncode;
				$retArr['collname'] = $r->collectionname;
				$retArr['colltype'] = $r->colltype;
				$retArr['mantype'] = $r->managementtype;
			}
			$rs->free();
		}
		return $retArr;
	}
}
?>