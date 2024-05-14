<?php
require_once($SERVER_ROOT.'/classes/APIBase.php');

class APITaxonomy extends Manager{

	public function __construct(){
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getTaxon($sciname){
		$retArr = array();
		$sciname = preg_replace('/[^a-zA-Z\-\.× ()]+/', '', $sciname);
		$sql = 'SELECT tid, sciname, author FROM taxa WHERE (sciname = "'.$sciname.'")';
		if(preg_match('/\s{1}\D{1}\s{1}/i',$sciname)){
			$sciname = preg_replace('/\s{1}x{1}\s{1}|\s{1}×{1}\s{1}/i', ' _ ', $sciname);
			$sql = 'SELECT tid, sciname, author FROM taxa WHERE (sciname LIKE "'.$sciname.'")';
		}
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid]['sciname'] = $r->sciname;
			$retArr[$r->tid]['author'] = $r->author;
		}
		$rs->free();
		if(count($retArr) > 1){
			//Is a Homonym, thus get kingdom
			$sql = 'SELECT e.tid, t.sciname FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.parenttid '.
				'WHERE (e.taxauthid = 1) AND (t.rankid = 10) AND (e.tid IN("'.implode(',',array_keys($retArr)).'"))';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->tid]['kingdom'] = $r->sciname;
			}
			$rs->free();
		}
		return $retArr;
	}

	//Setters and getters

}
?>