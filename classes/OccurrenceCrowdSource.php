<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class OccurrenceCrowdSource {

	private $conn;
	private $collid;
	private $omcsid;
	private $headArr = Array();

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		$this->headArr = array('catalogNumber','family','sciname','identifiedBy','dateIdentified','recordedBy','recordNumber',
			'associatedCollectors','eventDate','verbatimEventDate','country','stateProvince','county','locality',
			'decimalLatitude','decimalLongitude','coordinateUncertaintyInMeters','verbatimCoordinates','minimumElevationInMeters',
			'maximumElevationInMeters','verbatimElevation','habitat','reproductiveCondition','substrate','occurrenceRemarks',
			'processingstatus','dateLastModified');
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	//Functions used in controlpanel.php
	public function getProjectDetails(){
		$retArr = array();
		//Currently returns one first CS project associated with collection
		//we could support multiple CS projects per collection each with different instructions, training, and data entry personnel
		if($this->collid){
			$sql = 'SELECT CONCAT_WS(":",c.institutioncode,c.collectioncode) AS collcode, c.collectionname, '.
				'csc.omcsid, csc.instructions, csc.trainingurl '.
				'FROM omcollections c LEFT JOIN omcrowdsourcecentral csc ON c.collid = csc.collid '.
				'WHERE c.collid = '.$this->collid;
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr['name'] = $r->collectionname.' ('.$r->collcode.')';
				$retArr['instr'] = $r->instructions;
				$retArr['url'] = $r->trainingurl;
				$retArr['omcsid'] = $r->omcsid;
				$this->omcsid = $r->omcsid;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function editProject($omcsid,$instr,$url){
		$statusStr = '';
		if(is_numeric($omcsid)){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omcrowdsourcecentral '.
				'SET instructions = '.($instr?'"'.$this->cleanInStr($instr).'"':'NULL').',trainingurl = '.($url?'"'.$this->cleanInStr($url).'"':'NULL').
				' WHERE omcsid = '.$omcsid;
			if(!$con->query($sql)){
				$statusStr = 'ERROR editing project: '.$con->error;
			}
			$con->close();
		}
		return $statusStr;
	}

	private function createNewProject(){
		if($this->collid){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'INSERT INTO omcrowdsourcecentral(collid,instructions,trainingurl) VALUES('.$this->collid.',NULL,NULL)';
			//echo $sql;
			if($con->query($sql)){
				$this->omcsid = $con->insert_id;
			}
			$con->close();
		}
	}

	public function getProjectStats(){
		$retArr = array();
		if($this->collid){
			//Get review status total counts
			$sql = 'SELECT q.reviewstatus, count(q.occid) as cnt '.
				'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid '.
				'WHERE c.collid = '.$this->collid.' '.
				'GROUP BY q.reviewstatus';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->reviewstatus] = $r->cnt;
			}
			$rs->free();

			//Get record count for those available for adding to queue
			$sql = 'SELECT count(DISTINCT o.occid) as cnt
				FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid
				LEFT JOIN omcrowdsourcequeue q ON o.occid = q.occid
				WHERE o.collid = '.$this->collid.' AND (o.processingstatus = "unprocessed") AND q.occid IS NULL ';
			$toAddCnt = 0;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$toAddCnt = $r->cnt;
			}
			$rs->free();
			$retArr['toadd'] = $toAddCnt;
		}
		return $retArr;
	}

	public function getProcessingStats(){
		$retArr = array();
		if($this->collid){
			//Processing scores by user
			$sql = 'SELECT CONCAT_WS(", ", u.lastname, u.firstname) as username, u.uid, q.isvolunteer, sum(IFNULL(q.points,0)) as usersum '.
				'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid '.
				'INNER JOIN users u ON q.uidprocessor = u.uid '.
				'WHERE c.collid = '.$this->collid.' '.
				'GROUP BY username, u.uid, q.isvolunteer ORDER BY usersum DESC ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$tag = ($r->isvolunteer==1?'v':'e');
				$retArr[$tag][$r->uid]['score'] = $r->usersum;
				$retArr[$tag][$r->uid]['name'] = $r->username;
			}
			$rs->free();

			//Processing counts by user
			$sql = 'SELECT q.uidprocessor, q.reviewstatus, q.isvolunteer, count(q.occid) as cnt '.
				'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid '.
				'WHERE c.collid = '.$this->collid.' AND q.uidprocessor IS NOT NULL '.
				'GROUP BY q.uidprocessor, q.reviewstatus, q.isvolunteer';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$tag = ($r->isvolunteer==1?'v':'e');
				$retArr[$tag][$r->uidprocessor][$r->reviewstatus] = $r->cnt;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getTopScores($catid){
		$retArr = array();
		//Get users
		$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) as user, q.reviewstatus, sum(IFNULL(q.points,3)) AS full_points '.
			'FROM omcrowdsourcequeue q INNER JOIN users u ON q.uidprocessor = u.uid ';
		if($catid) $sql .= 'INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid INNER JOIN omcollcatlink cat ON c.collid = cat.collid ';
		$sql .= 'WHERE q.reviewstatus IN(5,10) AND q.isvolunteer = 1 ';
		if(preg_match('/^[,\d]+$/', $catid)) $sql .= 'AND (cat.ccpk IN('.$catid.')) ';
		$sql .= 'GROUP BY u.firstname, u.lastname, q.reviewstatus ORDER BY full_points DESC ';
		$rs = $this->conn->query($sql);
		$cnt = 0;
		while($r = $rs->fetch_object()){
			$points = $r->full_points;
			if(!$points) $points = 0;
			$retArr[$r->user][$r->reviewstatus] = $points;
			$cnt++;
			if($cnt > 10) break;
		}
		$rs->free();
		return $retArr;
	}

	public function getUserStats($catid){
		$retArr = array();
		$sql = 'SELECT c.collid, CONCAT_WS(":",c.institutioncode,c.collectioncode) as collcode, c.collectionname, '.
			'q.reviewstatus, q.isvolunteer, COUNT(q.occid) AS cnt, SUM(IFNULL(q.points,2)) AS points '.
			'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral csc ON q.omcsid = csc.omcsid '.
			'INNER JOIN omcollections c ON csc.collid = c.collid ';
		if(preg_match('/^[,\d]+$/', $catid)) $sql .= 'INNER JOIN omcollcatlink cat ON c.collid = cat.collid WHERE (cat.ccpk IN('.$catid.')) ';
		$sql .= 'GROUP BY c.collid,q.reviewstatus,q.uidprocessor,q.isvolunteer '.
			'HAVING (q.uidprocessor = '.$GLOBALS['SYMB_UID'].' OR q.uidprocessor IS NULL) '.
			'ORDER BY c.collectionname ,q.reviewstatus';
		//echo $sql;
		$rs = $this->conn->query($sql);
		$pPoints = 0;
		$aPoints = 0;
		$nonVolunteerCnt = 0;
		$totalCnt = 0;
		while($r = $rs->fetch_object()){
			$retArr[$r->collid]['name'] = $r->collectionname.' ('.$r->collcode.')';
			$retArr[$r->collid]['cnt'][$r->reviewstatus] = $r->cnt;
			if($r->isvolunteer) $retArr[$r->collid]['points'][$r->reviewstatus] = $r->points;
			if($r->reviewstatus >= 10){
				if($r->isvolunteer){
					$aPoints += $r->points;
				}
			}
			elseif($r->reviewstatus == 5){
				if($r->isvolunteer){
					$pPoints += $r->points;
				}
			}
			if($r->reviewstatus > 0){
				if($r->isvolunteer){
					$totalCnt += $r->cnt;
				}
				else{
					$nonVolunteerCnt += $r->cnt;
				}
			}
		}
		$retArr['ppoints'] = $pPoints;
		$retArr['apoints'] = $aPoints;
		$retArr['totalcnt'] = $totalCnt;
		$retArr['nonvolcnt'] = $nonVolunteerCnt;
		$rs->free();
		return $retArr;
	}

	public function addToQueue($omcsid,$family,$taxon,$country,$stateProvince,$limit){
		$statusStr = 'SUCCESS: specimens added to queue';
		if(!$this->omcsid) return 'ERROR adding to queue, omcsid is null';
		if(!$this->collid) return 'ERROR adding to queue, collid is null';
		$con = MySQLiConnectionFactory::getCon("write");
		$sqlFrag = 'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
			'LEFT JOIN omcrowdsourcequeue q ON o.occid = q.occid '.
			'WHERE o.collid = '.$this->collid.' AND q.occid IS NULL AND (o.processingstatus = "unprocessed") ';
		if($family){
			$sqlFrag .= 'AND (o.family = "'.$this->cleanInStr($family).'") ';
		}
		if($taxon){
			$sqlFrag .= 'AND (o.sciname LIKE "'.$this->cleanInStr($taxon).'%") ';
		}
		if($country){
			$sqlFrag .= 'AND (o.country = "'.$this->cleanInStr($country).'") ';
		}
		if($stateProvince){
			$sqlFrag .= 'AND (o.stateprovince = "'.$this->cleanInStr($stateProvince).'") ';
		}
		//Get count
		$sqlCnt = 'SELECT COUNT(DISTINCT o.occid) AS cnt '.$sqlFrag;
		$rs = $con->query($sqlCnt);
		if($r = $rs->fetch_object()){
			$statusStr = $r->cnt;
			if($statusStr > $limit) $statusStr = $limit;
		}
		$rs->free();
		//Run insert query
		if($limit){
			$sqlFrag .= 'LIMIT '.$limit;
		}
		$sql = 'INSERT INTO omcrowdsourcequeue(occid, omcsid) SELECT DISTINCT o.occid, '.$omcsid.' AS csid '.$sqlFrag;
		if(!$con->query($sql)){
			$statusStr = 'ERROR adding to queue: '.$con->error;
			$statusStr .= '; SQL: '.$sql;
		}
		$con->close();
		return $statusStr;
	}

	public function deleteQueue(){
		$statusStr = 'SUCCESS: all specimens removed from queue';
		if(!$this->omcsid) return 'ERROR adding to queue, omcsid is null';
		if(!$this->collid) return 'ERROR adding to queue, collid is null';
		$con = MySQLiConnectionFactory::getCon("write");
		$sql = 'DELETE FROM omcrowdsourcequeue '.
			'WHERE omcsid = '.$this->omcsid.' AND uidprocessor IS NULL and reviewstatus = 0 ';
		if(!$con->query($sql)){
			$statusStr = 'ERROR removing specimens from queue: '.$con->error;
			$statusStr .= '; SQL: '.$sql;
		}
		$con->close();
		return $statusStr;
	}

	public function getQueueLimitCriteria(){
		$country = array();
		$state = array();
		$sql = 'SELECT DISTINCT o.country, o.stateprovince '.
			'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
			'LEFT JOIN omcrowdsourcequeue q ON o.occid = q.occid '.
			'WHERE o.collid = '.$this->collid.' AND (o.processingstatus = "unprocessed") AND q.occid IS NULL ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->country) $country[$r->country] = '';
			if($r->stateprovince) $state[$r->stateprovince] = '';
		}
		$rs->free();
		$retArr = array();
		$retArr['country'] = array_keys($country);
		$retArr['state'] = array_keys($state);
		//Add genera to $sciname
		$family = array();
		$sciname = array();
		$sql = 'SELECT DISTINCT o.family, o.sciname, t.unitname1 '.
			'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
			'LEFT JOIN omcrowdsourcequeue q ON o.occid = q.occid '.
			'LEFT JOIN taxa t ON o.tidinterpreted = t.tid '.
			'WHERE o.collid = '.$this->collid.' AND (o.processingstatus = "unprocessed") AND q.occid IS NULL ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->family) $family[$r->family] = '';
			if($r->sciname){
				$sciname[$r->sciname] = '';
				if($r->unitname1 && !array_key_exists($r->unitname1,$sciname)) $sciname[$r->unitname1] = '';
			}
		}
		$rs->free();
		$retArr['family'] = array_keys($family);
		$retArr['taxa'] = array_keys($sciname);
		return $retArr;
	}

	//Review functions
	public function getReviewArr($startIndex,$limit,$uid,$rStatus){
		$retArr = array();
		if($this->collid || $uid){
			$sql = 'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral csc ON q.omcsid = csc.omcsid '.
				'INNER JOIN omoccurrences o ON q.occid = o.occid '.
				'WHERE q.reviewstatus IN('.$rStatus.') ';
			if($this->collid){
				$sql .= 'AND csc.collid = '.$this->collid.' ';
			}
			if($uid){
				$sql .= 'AND (q.uidprocessor = '.$uid.') ';
			}
			//Get occurrence records
			$sqlRec = 'SELECT o.occid, '.implode(', ',$this->headArr).', q.uidprocessor, q.reviewstatus, q.points, q.notes '.
				$sql.'ORDER BY o.datelastmodified DESC LIMIT '.$startIndex.','.$limit;
			//echo $sqlRec;
			$rs = $this->conn->query($sqlRec);
			$headerArr = array();
			while($r = $rs->fetch_assoc()){
				$retArr[$r['occid']] = $r;
				//Collection fields that have a value in at least on record
				foreach($r as $field => $value){
					if($value && !in_array($field, $headerArr)) $headerArr[] = $field;
				}
			}
			$rs->free();
			//Remove fields from $this->headArr that are not in $headerArr
			$this->headArr = array_intersect($this->headArr,$headerArr);

			//Get count
			$sqlCnt = 'SELECT COUNT(o.occid) AS cnt '.$sql;
			//echo $sqlCnt;
			$rs = $this->conn->query($sqlCnt);
			if($row = $rs->fetch_object()){
				$retArr['totalcnt'] = $row->cnt;
			}
		}
		else{
			echo "ERROR: both collid and user id are null";
		}
		return $retArr;
	}

	public function submitReviews($postArr){
		$statusStr = '';
		$occidArr = $postArr['occid'];
		if($occidArr){
			$successArr = array();
			$con = MySQLiConnectionFactory::getCon("write");
			foreach($occidArr as $occid){
				if(isset($postArr['p-'.$occid])){
					$points = $postArr['p-'.$occid];
					$comments = $this->cleanInStr($postArr['c-'.$occid]);
					$sql = 'UPDATE omcrowdsourcequeue '.
						'SET points = '.$points.',notes = '.($comments?'"'.$comments.'"':'NULL').',reviewstatus = 10 '.
						'WHERE occid = '.$occid;
					if($con->query($sql)){
						$successArr[] = $occid;
					}
					else{
						$statusStr = 'ERROR submitting reviews; '.$con->error.'<br/>SQL = '.$sql;
					}
				}
			}
			if($successArr && isset($postArr['updateProcessingStatus']) && $postArr['updateProcessingStatus']){
				//Change status to reviewed
				$sql2 = 'UPDATE omoccurrences SET processingstatus = "reviewed" WHERE occid IN('.implode(',',$successArr).')';
				$con->query($sql2);
			}
			$con->close();
		}
		return $statusStr;
	}

	public function resetReviewStatus($postArr, $status){
		$statusStr = '';
		if($occidArr = $postArr['occid']){
			$successArr = array();
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omcrowdsourcequeue SET points = NULL, notes = NULL, reviewstatus = '.$status.' ';
			if($status === 0){
				$sql .= ', uidprocessor = NULL, isvolunteer = 1 ';
			}
			$sql .= 'WHERE occid IN('.implode(',',$occidArr).')';
			if(!$con->query($sql)){
				$statusStr = 'ERROR submitting reviews; '.$con->error.'<br/>SQL = '.$sql;
			}
			$con->close();
		}
		return $statusStr;
	}

	//Setters and Getters and General functions
	public function setCollid($id){
		if($id && is_numeric($id)){
			$this->collid = $id;
			if(!$this->omcsid){
				//set omcsid
				$sql = 'SELECT omcsid FROM omcrowdsourcecentral WHERE collid = '.$this->collid;
				//echo $sql;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$this->omcsid = $r->omcsid;
				}
				$rs->free();
			}
			if(!$this->omcsid){
				//If omcsid project doesn't exist yet, create one!
				$this->createNewProject();
			}
		}
	}

	public function setOmcsid($id){
		if($id && is_numeric($id)){
			$this->omcsid = $id;
		}
	}

	public function getOmcsid(){
		return $this->omcsid;
	}

	public function getEditorList(){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT DISTINCT u.uid, CONCAT_WS(", ", u.lastname, u.firstname) as user '.
				'FROM omcrowdsourcequeue q INNER JOIN users u ON q.uidprocessor = u.uid '.
				'INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid '.
				'WHERE c.collid = '.$this->collid.' '.
				'ORDER BY u.lastname, u.firstname';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->uid] = $r->user;
			}
			$rs->free();
		}
		return $retArr;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	public function getHeaderArr(){
    	return $this->headArr;
    }
}
?>