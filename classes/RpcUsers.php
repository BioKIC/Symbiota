<?php
include_once($SERVER_ROOT.'/classes/RpcBase.php');

class RpcUsers extends RpcBase{

	function __construct(){
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getUserArr($term){
		$retArr = array();
		$sql = 'SELECT uid, CONCAT(CONCAT_WS(", ", lastname, firstname)," (", username,")") as uname
			FROM users
			WHERE lastname LIKE "%'.$term.'%" OR firstname LIKE "%'.$term.'%" OR username LIKE "%'.$term.'%"
			ORDER BY lastname, firstname, username';
		$rs = $this->conn->query($sql);
		$cnt = 0;
		while($r = $rs->fetch_object()){
			$retArr[$cnt]['id'] = $r->uid;
			$retArr[$cnt]['label'] = $r->uname;
			$cnt++;
		}
		$rs->free();
		return $retArr;
	}

	public function isValidApiCall(){
		//Verification also happening within haddler checking is user is logged in and a valid admin/editor
		$status = parent::isValidApiCall();
		if(!$status) return false;
		return true;
	}
}
?>