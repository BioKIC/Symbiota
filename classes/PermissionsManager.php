<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

/*
SuperAdmin			Edit all data and assign new permissions

RareSppAdmin		Add or remove species from rare species list
RareSppReadAll		View and map rare species collection data for all collections
RareSppReader-#		View and map rare species collecton data for specific collections
CollAdmin-#			Upload records; modify metadata
CollEditor-#		Edit collection records
CollTaxon-#:#		Edit collection records within taxonomic speciality

ClCreate			Create Checklists
ClAdmin-#			Checklist write access
ProjAdmin-#			Project admin access
KeyAdmin			Edit identification key characters and character states
KeyEditor			Edit identification key data
TaxonProfile		Modify decriptions; add images;
Taxonomy			Add names; edit name; change taxonomy
*/

class PermissionsManager{

	private $conn;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getUser($uid){
		$returnArr = Array();
		if(is_numeric($uid)){
			$sql = 'SELECT uid, firstname, lastname, title, institution, city, state, zip, country, '.
				'email, url, guid, notes, username, IFNULL(lastlogindate, initialTimestamp) AS lastlogindate '.
				'FROM users '.
				'WHERE (uid = '.$uid.')';
			//echo "<div>$sql</div>";
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$returnArr["uid"] = $row->uid;
				$returnArr["firstname"] = $row->firstname;
				$returnArr["lastname"] = $row->lastname;
				$returnArr["title"] = $row->title;
				$returnArr["institution"] = $row->institution;
				$returnArr["city"] = $row->city;
				$returnArr["state"] = $row->state;
				$returnArr["zip"] = $row->zip;
				$returnArr["country"] = $row->country;
				$returnArr["email"] = $row->email;
				$returnArr['url'] = $row->url;
				$returnArr['guid'] = $row->guid;
				$returnArr['notes'] = $row->notes;
				$returnArr['username'] = $row->username;
				$returnArr['lastlogindate'] = $row->lastlogindate;
			}
			$result->free();
		}
		return $returnArr;
	}

	public function getUserPermissions($uid){
		$perArr = Array();
		if(is_numeric($uid)){
			$sql = 'SELECT r.role, r.tablepk, CONCAT_WS(", ",u.lastname,u.firstname) AS assignedby, r.initialtimestamp '.
				'FROM userroles r LEFT JOIN users u ON r.uidassignedby = u.uid '.
				'WHERE (r.uid = '.$uid.') ';
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$assignedBy = 'assigned by: '.($row->assignedby?$row->assignedby.' ('.$row->initialtimestamp.')':'unknown');
				if($row->tablepk){
					//CollAdmin, CollEditor, RareSppReader
					$perArr[$row->role][$row->tablepk]['aby'] = $assignedBy;
				}
				else{
					//RareSppAdmin, RareSppReader, KeyEditor, TaxonProfile, Taxonomy
					$perArr[$row->role]['aby'] = $assignedBy;
					$perArr[$row->role]['role'] = $row->role;
				}
			}
			$result->free();

			//If there are collections, get names
			if(array_key_exists('CollAdmin',$perArr)){
				$sql = 'SELECT collid, collectionname, colltype FROM omcollections WHERE (collid IN('.implode(',',array_keys($perArr['CollAdmin'])).')) ORDER BY collectionname';
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					if($row->colltype == 'General Observations'){
						$perArr['PersonalObsAdmin'][$row->collid]['name'] = $row->collectionname;
						//Migrate other details
						$perArr['PersonalObsAdmin'][$row->collid]['aby'] = $perArr['CollAdmin'][$row->collid]['aby'];
						unset($perArr['CollAdmin'][$row->collid]);
					}
					else{
						$perArr['CollAdmin'][$row->collid]['name'] = $row->collectionname;
					}
				}
				$result->free();
				uasort($perArr["CollAdmin"], array($this,'sortByName'));
				if(array_key_exists('CollAdmin', $perArr) && !$perArr['CollAdmin']) unset($perArr['CollAdmin']);
			}
			if(array_key_exists('CollEditor',$perArr)){
				$sql = 'SELECT collid, collectionname, colltype FROM omcollections WHERE (collid IN('.implode(',',array_keys($perArr['CollEditor'])).')) ORDER BY collectionname';
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					if($row->colltype == 'General Observations'){
						$perArr['PersonalObsEditor'][$row->collid]['name'] = $row->collectionname;
						//Migrate other details
						$perArr['PersonalObsEditor'][$row->collid]['aby'] = $perArr['CollEditor'][$row->collid]['aby'];
						unset($perArr['CollEditor'][$row->collid]);
					}
					else{
						$perArr['CollEditor'][$row->collid]['name'] = $row->collectionname;
					}
				}
				$result->free();
				uasort($perArr["CollEditor"], array($this,'sortByName'));
				if(array_key_exists('CollEditor', $perArr) && !$perArr['CollEditor']) unset($perArr['CollEditor']);
			}
			if(array_key_exists('RareSppReader',$perArr)){
				$sql = 'SELECT collid, collectionname, colltype FROM omcollections WHERE (collid IN('.implode(',',array_keys($perArr['RareSppReader'])).')) ORDER BY collectionname';
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					if($row->colltype == 'General Observations'){
						$perArr['PersonalObsReader'][$row->collid]['name'] = $row->collectionname;
						//Migrate other details
						$perArr['PersonalObsReader'][$row->collid]['aby'] = $perArr['RareSppReader'][$row->collid]['aby'];
						unset($perArr['RareSppReader'][$row->collid]);
					}
					else{
						$perArr['RareSppReader'][$row->collid]['name'] = $row->collectionname;
					}
				}
				$result->free();
				uasort($perArr["RareSppReader"], array($this,'sortByName'));
				if(array_key_exists('RareSppReader', $perArr) && !$perArr['RareSppReader']) unset($perArr['RareSppReader']);
			}

			//If there are checklist, fetch names
			if(array_key_exists("ClAdmin",$perArr)){
				$sql = 'SELECT clid, name FROM fmchecklists WHERE clid IN('.implode(',',array_keys($perArr['ClAdmin'])).') ORDER BY name';
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$perArr["ClAdmin"][$row->clid]['name'] = $row->name;
				}
				uasort($perArr["ClAdmin"], array($this,'sortByName'));
				$result->free();
			}

			//If there are project admins, fetch project names
			if(array_key_exists("ProjAdmin",$perArr)){
				$sql = 'SELECT pid, projname FROM fmprojects WHERE (pid IN('.implode(',',array_keys($perArr['ProjAdmin'])).')) ORDER BY projname';
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$perArr["ProjAdmin"][$row->pid]['name'] = $row->projname;
				}
				uasort($perArr['ProjAdmin'], array($this,'sortByName'));
				$result->free();
			}
		}
		return $perArr;
	}

	public function deletePermission($id, $role, $tablePk, $secondaryVariable = ''){
		$statusStr = '';
		if(is_numeric($id)){
			$sql = 'DELETE FROM userroles WHERE (uid = '.$id.') AND (role = "'.$role.'") AND (tablepk '.($tablePk?' = '.$tablePk:' IS NULL').') ';
			if($secondaryVariable) $sql .= 'AND (secondaryVariable = "'.$secondaryVariable.'") ';
			$this->conn->query($sql);
		}
		return $statusStr;
	}

	public function addPermission($uid,$role,$tablePk,$secondaryVariable = ''){
		$statusStr = '';
		if(is_numeric($uid)){
			if(!$tablePk){
				if($role == 'ClAdmin') $tablePk = 'fmchecklists';
				if($role == 'ProjAdmin') $tablePk = 'fmprojects';
			}
			$sql = 'SELECT uid,role,tablepk,secondaryVariable,uidassignedby FROM userroles WHERE (uid = '.$uid.') AND (role = "'.$role.'") ';
			if($tablePk) $sql .= 'AND (tablepk = '.$tablePk.') ';
			if($secondaryVariable) $sql .= 'AND (secondaryVariable = '.$secondaryVariable.') ';
			$rs = $this->conn->query($sql);
			if(!$rs->num_rows){
				$sql1 = 'INSERT INTO userroles(uid,role,tablepk,secondaryVariable,uidassignedby) '.
					'VALUES('.$uid.',"'.$role.'",'.($tablePk?'"'.$tablePk.'"':'NULL').','.($secondaryVariable?'"'.$secondaryVariable.'"':'NULL').','.$GLOBALS["SYMB_UID"].')';
				if(!$this->conn->query($sql1)){
					$statusStr = 'ERROR adding user permission: '.$this->conn->error;
				}
			}
			$rs->free();
		}
		return $statusStr;
	}

	public function addClCreateRole($uid){
		$statusStr = '';
		if(is_numeric($uid)){

			$sql = 'SELECT uid,role,tablepk,secondaryVariable,uidassignedby FROM userroles WHERE (uid = '.$uid.') AND (role = "ClCreate");';
			$rs = $this->conn->query($sql);
			if(!$rs->num_rows){
				$sql1 = 'INSERT INTO userroles(uid,role,uidassignedby) '.
					'VALUES('.$uid.',"ClCreate",'.$GLOBALS["SYMB_UID"].')';
				if(!$this->conn->query($sql1)){
					$statusStr = 'ERROR adding user permission: '.$this->conn->error;
				}
			}
			$rs->free();
		}
		return $statusStr;
	}

	public function getTaxonEditorArr($collid, $limitByColl = 0){
		//grab the current permissions
		$pArr = array();
		$sql2 = 'SELECT uid, role, tablepk, secondaryvariable FROM userroles WHERE role = ("CollTaxon") AND (tablepk = '.$collid.') ';
		$rs2 = $this->conn->query($sql2);
		while($r2 = $rs2->fetch_object()){
			if(($r2->role == 'CollTaxon') && ($r2->tablepk = $collid) && ($r2->secondaryvariable = 'all')){
				$pArr[$r2->uid]['all'] = 1;
			}
			else{
				$pArr[$r2->uid]['utid'][] = $r2->secondaryvariable;
				//$utId = substr($r2->pname,strrpos($r2->pname,':')+1);
				//$pArr[$r2->uid]['utid'][] = $utId;
			}
			/*
			if($r2->pname == 'CollTaxon-'.$collid.':all'){
				$pArr[$r2->uid]['all'] = 1;
			}
			else{
				$utId = substr($r2->pname,strrpos($r2->pname,':')+1);
				$pArr[$r2->uid]['utid'][] = $utId;
			}
			*/
		}
		$rs2->free();
		//Get editors
		$retArr = array();
		$sql = 'SELECT ut.idusertaxonomy, u.uid, CONCAT_WS(", ", u.lastname, u.firstname) as fullname, t.sciname, u.username '.
			'FROM usertaxonomy ut INNER JOIN users u ON ut.uid = u.uid '.
			'INNER JOIN taxa t ON ut.tid = t.tid '.
			'WHERE ut.editorstatus = "OccurrenceEditor" ';
		if($limitByColl && $pArr){
			$sql .= 'AND ut.uid IN('.implode(',',array_keys($pArr)).') ';
		}
		$sql .= 'ORDER BY u.lastname, u.firstname, t.sciname';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($limitByColl){
				//Add all that have permissions within given collection
				if(isset($pArr[$r->uid])){
					if(isset($pArr[$r->uid]['all']) || in_array($r->idusertaxonomy,$pArr[$r->uid]['utid'])){
						$retArr[$r->uid]['username'] = $r->fullname.' ('.$r->username.')';
						$retArr[$r->uid][$r->idusertaxonomy] = $r->sciname;
					}
				}
			}
			else{
				if(!isset($pArr[$r->uid]) || !isset($pArr[$r->uid]['utid']) || !in_array($r->idusertaxonomy,$pArr[$r->uid]['utid'])){
					$retArr[$r->uid]['username'] = $r->fullname.' ('.$r->username.')';
					$retArr[$r->uid][$r->idusertaxonomy] = $r->sciname;
				}
			}
		}
		$rs->free();
		//Add permissions for those with all
		foreach($pArr as $uid => $pArr){
			if(array_key_exists('all',$pArr)) $retArr[$uid]['all'] = 1;
		}

		return $retArr;
	}

	//General get list functions
	public function getCollectionMetadata($collType = ''){
		$retArr = Array();
		$sql = 'SELECT collid, collectionname, institutioncode, collectioncode, colltype FROM omcollections ';
		if(is_numeric($collType)) $sql .= 'WHERE (collid = '.$collType.') ';
		else $sql .= 'WHERE (colltype = "'.$this->cleanInStr($collType).'") ';
		$sql .= 'ORDER BY collectionname';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid]['collectionname'] = $this->cleanOutStr($r->collectionname);
			$retArr[$r->collid]['institutioncode'] = $r->institutioncode;
			$retArr[$r->collid]['collectioncode'] = $r->collectioncode;
			$retArr[$r->collid]['colltype'] = $r->colltype;
		}
		$rs->free();
		return $retArr;
	}

	public function getCollectionEditors($collid){
		$returnArr = Array();
		if($collid){
			$sql = 'SELECT ur.uid, ur.role, ur.tablepk, CONCAT_WS(", ",u.lastname,u.firstname) AS uname, '.
				'CONCAT_WS(", ",u2.lastname,u2.firstname) AS assignedby, ur.initialtimestamp '.
				'FROM userroles ur INNER JOIN users u ON ur.uid = u.uid '.
				'LEFT JOIN users u2 ON ur.uidassignedby = u2.uid '.
				'WHERE (ur.role = "CollAdmin" AND ur.tablepk = '.$collid.') OR (ur.role = "CollEditor" AND ur.tablepk = '.$collid.') '.
				'OR (ur.role = "RareSppReader" AND ur.tablepk = '.$collid.') '.
				'ORDER BY u.lastname,u.firstname';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$pGroup = 'rarespp';
				if($r->role == 'CollAdmin') $pGroup = 'admin';
				elseif($r->role == 'CollEditor') $pGroup = 'editor';
				$outStr = '<span title="assigned by: '.($r->assignedby?$r->assignedby.' ('.$r->initialtimestamp.')':'unknown').'">'.$this->cleanOutStr($r->uname).'</span>';
				$returnArr[$pGroup][$r->uid] = $outStr;
				/*
				$pGroup = 'rarespp';
				if(substr($r->pname,0,9) == 'CollAdmin') $pGroup = 'admin';
				elseif(substr($r->pname,0,10) == 'CollEditor') $pGroup = 'editor';
				$outStr = '<span title="assigned by: '.($r->assignedby?$r->assignedby.' ('.$r->initialtimestamp.')':'unknown').'">'.$this->cleanOutStr($r->uname).'</span>';
				$returnArr[$pGroup][$r->uid] = $outStr;
				*/
			}
			$rs->free();
		}
		return $returnArr;
	}

	public function getUsers($searchTerm=''){
		$retArr = Array();
		$sql = 'SELECT uid, CONCAT_WS(", ", lastname, firstname) AS uname, username FROM users ';
		if($searchTerm){
			$searchTerm = $this->cleanInStr($searchTerm);
			$sql .= 'WHERE (lastname LIKE "'.$searchTerm.'%") ';
			if(strlen($searchTerm) > 1) $sql .= 'OR (username LIKE "'.$searchTerm.'%") ';
		}
		$sql .= 'ORDER BY lastname, firstname';
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->uid] = $this->cleanOutStr($r->uname.($r->username?' ('.$r->username.')':''));
		}
		$rs->free();
		//asort($retArr);
		return $retArr;
	}

	public function getProjectArr($pidKeys){
		$returnArr = Array();
		$sql = 'SELECT pid, projname FROM fmprojects ';
		if($pidKeys) $sql .= 'WHERE (pid NOT IN('.implode(',',$pidKeys).')) ';
		$sql .= 'ORDER BY projname';
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->pid] = $row->projname;
		}
		$result->free();
		return $returnArr;
	}

	public function getChecklistArr($clKeys){
		$returnArr = Array();
		$sql = 'SELECT cl.clid, cl.name FROM fmchecklists cl ';
		if($clKeys) $sql .= 'WHERE (cl.access != "private") AND (cl.clid NOT IN('.implode(',',$clKeys).')) ';
		$sql .= 'ORDER BY cl.name';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->clid] = $row->name;
		}
		$result->free();
		return $returnArr;
	}

	public function getGeneralObservationCollidArr(){
		$retArr = array();
		$sql = 'SELECT collid, CONCAT(collectionname, " (", CONCAT_WS("-", institutioncode, collectioncode),")") AS collname FROM omcollections WHERE (colltype = "General Observations")';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collname;
		}
		$rs->free();
		return $retArr;
	}

	public function getPersonalObservationManagementArr($collid, $genObsCollidArr){
		$retArr = array();
		$sql = 'SELECT DISTINCT r.uid, CONCAT_WS(", ",u.lastname,u.firstname) as usrname, r.tablepk, r.uidassignedby, CONCAT_WS(", ",u2.lastname,u2.firstname) as uab, r.initialtimestamp '.
			'FROM userroles r INNER JOIN users u ON r.uid = u.uid '.
			'INNER JOIN users u2 ON r.uidassignedby = u2.uid '.
			'WHERE (r.tablepk IN('.implode(',',array_keys($genObsCollidArr)).')) AND (r.role = "CollEditor") '.
			'AND (r.uidassignedby NOT IN(SELECT uid FROM userroles WHERE role = "SuperAdmin" AND uid != '.$GLOBALS['SYMB_UID'].')) '.
			'AND (r.uidassignedby IN(SELECT uid FROM userroles WHERE (role = "CollAdmin") AND (tablepk = '.$collid.')))';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->uid]['name'] = $r->usrname;
			$retArr[$r->uid]['persobscollid'] = $r->tablepk;
			$retArr[$r->uid]['uidab'] = $r->uidassignedby;
			$retArr[$r->uid]['uab'] = $r->uab;
			$retArr[$r->uid]['ts'] = $r->initialtimestamp;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	//Misc fucntions
	private function sortByName($a, $b) {
		if(!isset($a["name"])) return -1;
		if(!isset($b["name"])) return 1;
		return strcmp($a["name"], $b["name"]);
	}

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>