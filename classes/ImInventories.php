<?php
include_once('Manager.php');

class ImInventories extends Manager{

	private $clid;
	private $pid;

	public function __construct($conType = 'write') {
		parent::__construct(null, $conType);
	}

	public function __destruct(){
		parent::__destruct();
	}

	//Checklist functions
	public function getChecklistMetadata($pid){
		$retArr = array();
		if($this->clid){
			$sql = 'SELECT clid, name, locality, publication, abstract, authors, parentclid, notes, latcentroid, longcentroid, pointradiusmeters,
				access, defaultsettings, dynamicsql, datelastmodified, uid, type, footprintwkt, sortsequence, initialtimestamp
				FROM fmchecklists WHERE (clid = '.$this->clid.')';
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$retArr['name'] = $this->cleanOutStr($row->name);
				$retArr['locality'] = $this->cleanOutStr($row->locality);
				$retArr['notes'] = $this->cleanOutStr($row->notes);
				$retArr['type'] = $row->type;
				$retArr['publication'] = $this->cleanOutStr($row->publication);
				$retArr['abstract'] = $this->cleanOutStr($row->abstract);
				$retArr['authors'] = $this->cleanOutStr($row->authors);
				$retArr['parentclid'] = $row->parentclid;
				$retArr['uid'] = $row->uid;
				$retArr['latcentroid'] = $row->latcentroid;
				$retArr['longcentroid'] = $row->longcentroid;
				$retArr['pointradiusmeters'] = $row->pointradiusmeters;
				$retArr['access'] = $row->access;
				$retArr['defaultsettings'] = $row->defaultsettings;
				$retArr['dynamicsql'] = $row->dynamicsql;
				$retArr['hasfootprintwkt'] = ($row->footprintwkt?'1':'0');
				$retArr['sortsequence'] = $row->sortsequence;
				$retArr['datelastmodified'] = $row->datelastmodified;
			}
			$result->free();
			if($retArr){
				if($retArr['type'] == 'excludespp'){
					$sql = 'SELECT clid FROM fmchklstchildren WHERE clidchild = '.$this->clid;
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						$retArr['excludeparent'] = $r->clid;
					}
					$rs->free();
				}
				if($pid && is_numeric($pid)){
					$sql = 'SELECT clNameOverride, mapChecklist, sortSequence, notes FROM fmchklstprojlink WHERE clid = '.$this->clid.' AND pid = '.$pid;
					$rs = $this->conn->query($sql);
					if($rs){
						if($r = $rs->fetch_object()){
							$retArr['clNameOverride'] = $this->cleanOutStr($r->clNameOverride);
							$retArr['mapchecklist'] = $r->mapChecklist;
							$retArr['sortsequence'] = $r->sortSequence;
						}
						$rs->free();
					}
				}
			}
		}
		return $retArr;
	}

	public function insertChecklist($fieldArr){
		$clid = false;
		if($fieldArr['name']){
			$clName = $fieldArr['name'];
			$authors = (!empty($fieldArr['authors']) ? $fieldArr['authors'] : NULL);
			$type = (!empty($fieldArr['type']) ? $fieldArr['type'] : 'static');
			$locality = (!empty($fieldArr['locality']) ? $fieldArr['locality'] : NULL);
			$publication = (!empty($fieldArr['publication']) ? $fieldArr['publication'] : NULL);
			$abstract = (!empty($fieldArr['abstract']) ? strip_tags($fieldArr['abstract'], '<i><u><b><a>') : NULL);
			$notes = (!empty($fieldArr['notes']) ? $fieldArr['notes'] : NULL);
			$latCentroid = (!empty($fieldArr['latcentroid']) && is_numeric($fieldArr['latcentroid']) ? $fieldArr['latcentroid'] : NULL);
			$longCentroid = (!empty($fieldArr['longcentroid']) && is_numeric($fieldArr['longcentroid']) ? $fieldArr['longcentroid'] : NULL);
			$pointRadiusMeters = (!empty($fieldArr['pointradiusmeters']) && is_numeric($fieldArr['pointradiusmeters']) ? $fieldArr['pointradiusmeters'] : NULL);
			$access = (!empty($fieldArr['access']) ? $fieldArr['access'] : 'private');
			$defaultSettings = (!empty($fieldArr['defaultsettings']) ? $fieldArr['defaultsettings'] : NULL);
			$dynamicSql = (!empty($fieldArr['dynamicsql']) ? $fieldArr['dynamicsql'] : NULL);
			$uid = (!empty($fieldArr['uid']) && is_numeric($fieldArr['uid']) && $fieldArr['uid'] ? $fieldArr['uid'] : NULL);
			$footprintWkt = (!empty($fieldArr['footprintwkt']) ? $fieldArr['footprintwkt'] : NULL);
			$sortSequence = (!empty($fieldArr['sortsequence']) && is_numeric($fieldArr['sortsequence']) ? $fieldArr['sortsequence'] : 50);
			$sql = 'INSERT INTO fmchecklists(name, authors, type, locality, publication, abstract, notes, latcentroid, longcentroid, pointradiusmeters, access, defaultsettings, dynamicsql, uid, footprintWkt, sortsequence) '.
				'VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('sssssssdddsssisi', $clName, $authors, $type, $locality, $publication, $abstract, $notes, $latCentroid, $longCentroid, $pointRadiusMeters, $access, $defaultSettings, $dynamicSql, $uid, $footprintWkt, $sortSequence);
				if($stmt->execute()){
					if($stmt->affected_rows && !$stmt->error){
						$clid = $stmt->insert_id;
					}
					else $this->errorMessage = 'ERROR inserting fmchecklists record (2): '.$stmt->error;
				}
				else $this->errorMessage = 'ERROR inserting fmchecklists record (1): '.$stmt->error;
				$stmt->close();
			}
		}
		return $clid;
	}

	public function updateChecklist($inputArr){
		$status = false;
		$sqlFrag = '';
		$fieldArr = array('name' => 's', 'authors' => 's', 'type' => 's', 'locality' => 's', 'publication' => 's', 'abstract' => 's', 'notes' => 's', 'latcentroid' => 'd', 'longcentroid' => 'd',
			'pointradiusmeters' => 'i', 'access' => 's', 'defaultsettings' => 's', 'dynamicsql' => 's', 'footprintWkt' => 's', 'uid' => 'i', 'sortsequence' => 'i');
		$typeStr = '';
		$paramArr = array();
		foreach($inputArr as $fieldName => $fieldValue){
			$fieldName = strtolower($fieldName);
			if(array_key_exists($fieldName, $fieldArr)){
				if($fieldArr[$fieldName] == 'i' || $fieldArr[$fieldName] == 'd'){
					if(!is_numeric($fieldValue)) $fieldValue = NULL;
					if($fieldName == 'sortsequence' && !$fieldValue) $fieldValue = 50;
				}
				else{
					if(!$fieldValue) $fieldValue = NULL;
				}
				$sqlFrag .= $fieldName.' = ?, ';
				$paramArr[] = $fieldValue;
				$typeStr .= $fieldArr[$fieldName];
			}
		}
		$sql = 'UPDATE fmchecklists SET '.trim($sqlFrag,', ').' WHERE (clid = ?)';
		if($paramArr){
			$paramArr[] = $this->clid;
			$typeStr .= 'i';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param($typeStr, ...$paramArr);
				if($stmt->execute()){
					if(!$stmt->error){
						$status = true;
					}
					else $this->errorMessage = 'ERROR updating fmchecklists record (2): '.$stmt->error;
				}
				else $this->errorMessage = 'ERROR updating fmchecklists record (1): '.$stmt->error;
				$stmt->close();
			}
			if($status){
				if($inputArr['type'] == 'rarespp' && $inputArr['locality']){
					$sql = 'UPDATE omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
						'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
						'INNER JOIN fmchklsttaxalink cl ON ts2.tid = cl.tid '.
						'SET o.localitysecurity = 1 '.
						'WHERE (cl.clid = '.$this->clid.') AND (o.stateprovince = "'.$this->cleanInStr($inputArr['locality']).'") AND (o.localitySecurityReason IS NULL) '.
						'AND (o.localitysecurity IS NULL OR o.localitysecurity = 0) AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) ';
					if(!$this->conn->query($sql)){
						$this->errorMessage = 'Error updating rare state species: '.$this->conn->error;
					}
				}
				elseif($inputArr['type'] == 'excludespp' && is_numeric($inputArr['excludeparent'])){
					$sql = 'INSERT IGNORE INTO fmchklstchildren(clid, clidchild) VALUES(?, ?)';
					if($stmt = $this->conn->prepare($sql)){
						$stmt->bind_param('ii', $inputArr['excludeparent'], $this->clid);
						if(!$stmt->execute()){
							$this->errorMessage = 'Error updating parent checklist for exclusion species list: '.$this->conn->error;
						}
						$stmt->close();
					}
				}
			}
		}
		return $status;
	}

	public function deleteChecklist(){
		$status = false;
		$roleArr = $this->getManagers('ClAdmin', 'fmchecklists', $this->clid);
		unset($roleArr[$GLOBALS['SYMB_UID']]);
		if(!$roleArr){
			$this->deleteChecklistTaxaLinks();
			$sql = 'DELETE FROM fmchecklists WHERE clid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->clid);
				$stmt->execute();
				if($stmt->affected_rows && !$stmt->error){
					$status = true;
					//Delete userpermissions reference once patch is submitted
					$this->deleteUserRole('ClAdmin', $this->clid, $GLOBALS['SYMB_UID']);
				}
				else $this->errorMessage = $stmt->error;
				$stmt->close();
			}
		}
		else{
			$this->errorMessage = 'Checklist cannot be deleted until all editors are removed. Remove editors and then try again.';
		}
		return $status;
	}

	public function getChecklistArr($pid = 0){
		$retArr = Array();
		$sql = 'SELECT c.clid, c.name, c.latcentroid, c.longcentroid, c.access FROM fmchecklists c ';
		if($pid && is_numeric($pid)) $sql .= 'INNER JOIN fmchklstprojlink pl ON c.clid = pl.clid WHERE (pl.pid = '.$pid.') ';
		$sql .= 'ORDER BY c.sortSequence, c.name';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->clid]['name'] = $r->name;
			$retArr[$r->clid]['lat'] = $r->latcentroid;
			$retArr[$r->clid]['lng'] = $r->longcentroid;
			$retArr[$r->clid]['access'] = $r->access;
		}
		$rs->free();
		return $retArr;
	}

	//Checklist taxa linkages
	private function deleteChecklistTaxaLinks(){
		$status = false;
		if($this->clid){
			$sql = 'DELETE FROM fmchklsttaxalink WHERE clid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->clid);
				$stmt->execute();
				if($stmt->error) $this->errorMessage = $stmt->error;
				else $status = true;
				$stmt->close();
			}
		}
		return $status;
	}

	//Child-Parent checklist functions
	public function insertChildChecklist($clidChild, $modifiedUid){
		$status = false;
		$sql = 'INSERT INTO fmchklstchildren(clid, clidchild, modifiedUid) VALUES(?,?,?) ';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('iii', $this->clid, $clidChild, $modifiedUid);
			if($stmt->execute()){
				if($stmt->affected_rows && !$stmt->error){
					$status = true;
				}
				else $this->errorMessage = 'ERROR inserting child checklist record (2): '.$stmt->error;
			}
			else $this->errorMessage = 'ERROR inserting child checklist record (1): '.$stmt->error;
			$stmt->close();
		}
		return $status;
	}

	public function deleteChildChecklist($clidDel){
		$status = false;
		if(is_numeric($clidDel)){
			$sql = 'DELETE FROM fmchklstchildren WHERE clid = '.$this->clid.' AND clidchild = '.$clidDel;
			if(!$this->conn->query($sql)){
				$this->errorMessage = 'ERROR deleting child checklist link';
			}
		}
		return $status;
	}

	//Inventory Project functions
	public function getProjectMetadata(){
		$returnArr = Array();
		if($this->pid){
			$sql = 'SELECT pid, projname, managers, fulldescription, notes, occurrencesearch, ispublic, sortsequence FROM fmprojects WHERE (pid = '.$this->pid.') ';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->pid = $row->pid;
				$returnArr['projname'] = $row->projname;
				$returnArr['managers'] = $row->managers;
				$returnArr['fulldescription'] = $row->fulldescription;
				$returnArr['notes'] = $row->notes;
				$returnArr['occurrencesearch'] = $row->occurrencesearch;
				$returnArr['ispublic'] = $row->ispublic;
				$returnArr['sortsequence'] = $row->sortsequence;
				if($row->ispublic == 0){
					$this->isPublic = 0;
				}
			}
			$rs->free();
			//Temporarly needed as a separate call until db_schema_patch-1.1.sql is applied
			$sql = 'SELECT headerurl FROM fmprojects WHERE (pid = '.$this->pid.')';
			$rs = $this->conn->query($sql);
			if($rs){
				if($r = $rs->fetch_object()){
					$returnArr['headerurl'] = $r->headerurl;
				}
				$rs->free();
			}
		}
		return $returnArr;
	}

	public function insertProject($inputArr){
		$newPid = 0;
		$projName = $inputArr['projname'];
		$managers = (isset($inputArr['managers'])?$inputArr['managers']:NULL);
		$fullDescription = (isset($inputArr['fulldescription'])?$inputArr['fulldescription']:NULL);
		$notes = (isset($inputArr['notes'])?$inputArr['notes']:NULL);
		$isPublic = (isset($inputArr['ispublic'])?$inputArr['ispublic']:0);
		$sql = 'INSERT INTO fmprojects(projname, managers, fulldescription, notes, ispublic) VALUES(?, ?, ?, ?, ?)';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ssssi', $projName, $managers, $fullDescription, $notes, $isPublic);
			if($stmt->execute()){
				if($stmt->affected_rows && !$stmt->error){
					$newPid = $stmt->insert_id;
					$this->pid = $newPid;
				}
				else $this->errorMessage = 'ERROR inserting fmprojects record (2): '.$stmt->error;
			}
			else $this->errorMessage = 'ERROR inserting fmprojects record (1): '.$stmt->error;
			$stmt->close();
		}
		return $newPid;
	}

	public function updateProject($inputArr){
		$status = false;
		$projName = $inputArr['projname'];
		$managers = $inputArr['managers'];
		$fullDescription = $inputArr['fulldescription'];
		$notes = $inputArr['notes'];
		$isPublic = $inputArr['ispublic'];

		$sql = 'UPDATE fmprojects SET projname = ?, managers = ?, fulldescription = ?, notes = ?, ispublic = ? WHERE (pid = ?)';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ssssii', $projName, $managers, $fullDescription, $notes, $isPublic, $this->pid);
			if($stmt->execute()){
				if(!$stmt->error){
					$status = true;
				}
				else $this->errorMessage = 'ERROR updating fmprojects record (2): '.$stmt->error;
			}
			else $this->errorMessage = 'ERROR updating fmprojects record (1): '.$stmt->error;
			$stmt->close();
		}
		return $status;
	}

	public function deleteProject($projID){
		$status = true;
		if($projID && is_numeric($projID)){
			$sql = 'DELETE FROM fmprojects WHERE pid = '.$projID;
			if(!$this->conn->query($sql)){
				$status = false;
				$this->errorStr = 'ERROR deleting inventory project: '.$this->conn->error;
			}
		}
		return $status;
	}

	public function getProjectList(){
		$retArr = Array();
		$sql = 'SELECT pid, projname, managers, fulldescription FROM fmprojects WHERE ispublic = 1 ORDER BY projname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->pid]['projname'] = $r->projname;
			$retArr[$r->pid]['managers'] = $r->managers;
			$retArr[$r->pid]['descr'] = $r->fulldescription;
		}
		$rs->free();
		return $retArr;
	}

	//Checklist Project Link functions
	public function insertChecklistProjectLink($clid){
		$status = true;
		if(is_numeric($clid)){
			$sql = 'INSERT INTO fmchklstprojlink(pid,clid) VALUES('.$this->pid.', '.$clid.') ';
			if(!$this->conn->query($sql)){
				$this->errorMessage = 'ERROR adding checklist to project: '.$this->conn->error;
			}
		}
		return $status;
	}

	public function deleteChecklistProjectLink($clid){
		$status = true;
		if(is_numeric($clid)){
			$sql = 'DELETE FROM fmchklstprojlink WHERE (pid = '.$this->pid.') AND (clid = '.$clid.')';
			if($this->conn->query($sql)){
				return 'ERROR deleting checklist from project:'.$this->conn->error;
			}
		}
		return $status;
	}

	//User role funcitons
	public function getManagers($role, $tableName, $tablePK){
		$retArr = array();
		if(is_numeric($tablePK)){
			$sql = 'SELECT u.uid, CONCAT_WS(", ", u.lastname, u.firstname) as fullname, u.username '.
				'FROM userroles r INNER JOIN users u ON r.uid = u.uid '.
				'WHERE r.role = "'.$this->cleanInStr($role).'" AND r.tableName = "'.$this->cleanInStr($tableName).'" AND r.tablepk = '.$tablePK;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->uid] = $r->fullname.' ('.$r->username.')';
			}
			$rs->free();
			asort($retArr);
		}
		return $retArr;
	}

	public function insertUserRole($uid, $role, $tableName, $tablePK, $uidAssignedBy){
		$status = false;
		$sql = 'INSERT INTO userroles (uid, role, tablename, tablepk, uidassignedby) VALUES(?,?,?,?,?) ';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('isssi', $uid, $role, $tableName, $tablePK, $uidAssignedBy);
			if($stmt->execute()){
				if($stmt->affected_rows && !$stmt->error){
					$status = true;
				}
				else $this->errorMessage = 'ERROR inserting user role record (2): '.$stmt->error;
			}
			else $this->errorMessage = 'ERROR inserting user role record (1): '.$stmt->error;
			$stmt->close();
		}
		return $status;
	}

	public function deleteUserRole($role, $tablePK, $uid){
		if(is_numeric($tablePK) && is_numeric($uid)){
			$sql = 'DELETE FROM userroles WHERE (role = "'.$this->cleanInStr($role).'") AND (tablepk = '.$tablePK.') AND (uid = '.$uid.')';
			$this->conn->query($sql);
		}
	}

	public function getUserArr(){
		$retArr = array();
		$sql = 'SELECT uid, CONCAT_WS(", ", lastname, firstname) as fullname, username FROM users ORDER BY lastname, firstname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->uid] = $r->fullname.' ('.$r->username.')';
		}
		$rs->free();
		return $retArr;
	}

	//Setter and getter functions
	public function setClid($clid){
		if(is_numeric($clid)) $this->clid = $clid;
	}

	public function getPid(){
		return $this->pid;
	}

	public function setPid($pid){
		if(is_numeric($pid)) $this->pid = $pid;
	}
}
?>