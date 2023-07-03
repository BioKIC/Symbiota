<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');

class UserTaxonomy {

	private $conn;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getTaxonomyEditors(){
		$retArr = array();
		$sql = 'SELECT ut.idusertaxonomy, u.uid, CONCAT_WS(", ", u.lastname, u.firstname) as fullname, t.sciname, ut.editorstatus, ut.geographicscope, ut.notes, u.username '.
			'FROM usertaxonomy ut INNER JOIN users u ON ut.uid = u.uid '.
			'INNER JOIN taxa t ON ut.tid = t.tid '.
			'ORDER BY u.lastname, u.firstname, t.sciname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$editorStatus = $r->editorstatus;
			if(!$editorStatus) $editorStatus = 'RegionOfInterest';
			$retArr[$editorStatus][$r->uid]['username'] = $r->fullname.' ('.$r->username.')';
			$retArr[$editorStatus][$r->uid][$r->idusertaxonomy]['sciname'] = $r->sciname;
			$retArr[$editorStatus][$r->uid][$r->idusertaxonomy]['geoscope'] = $r->geographicscope;
			$retArr[$editorStatus][$r->uid][$r->idusertaxonomy]['notes'] = $r->notes;
		}
		$rs->free();
		return $retArr;
	}

	public function deleteUser($utid,$uid,$editorStatus){
		$statusStr = '';
		$profileManager = new ProfileManager();
		$profileManager->setUid($uid);
		$statusStr = $profileManager->deleteUserTaxonomy($utid,$editorStatus);
		return $statusStr;
	}

	public function addUser($uid, $taxa, $editorStatus, $geographicScope, $notes){
		$statusStr = '';
		$profileManager = new ProfileManager();
		$profileManager->setUid($uid);
		$statusStr = $profileManager->addUserTaxonomy($taxa, $editorStatus, $geographicScope, $notes);
		return $statusStr;
	}

	//Get functions
	public function getUserArr(){
		$retArr = array();
		$sql = 'SELECT uid, CONCAT_WS(", ", lastname, firstname,CONCAT(" (", username,")")) as fullname FROM users ORDER BY lastname, firstname, username ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->uid] = $r->fullname;
		}
		$rs->free();
		return $retArr;
	}

	//Misc functions
	private function cleanOutStr($str){
		$str = str_replace('"',"&quot;",$str);
		$str = str_replace("'","&apos;",$str);
		return $str;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>