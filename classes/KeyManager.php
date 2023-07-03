<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class KeyManager{

	protected $conn;
	protected $taxAuthId = 1;
	protected $language = "English";

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	//Management functions
	protected function deleteDescr($tidStr, $charStr = '', $csStr = ''){
		if($tidStr){
			$sqlWhere = '(TID In ('.$tidStr.')) ';
			if($charStr) $sqlWhere .= 'AND (CID IN ('.$charStr.'))';
			if($csStr) $sqlWhere .= 'AND (cs IN ('.$csStr.'))';

			//Delete descriptions
			$sql = 'DELETE FROM kmdescr WHERE '.$sqlWhere;
			$this->conn->query($sql);
		}
	}

	protected function insertDescr($tid, $cid, $cs){
		if(is_numeric($tid) && is_numeric($cid) && $cs){
			$sql = "INSERT INTO kmdescr (TID, CID, CS, Source) VALUES ($tid, $cid, '".$this->cleanInStr($cs)."', '".$GLOBALS['USERNAME']."')";
			$this->conn->query($sql);
		}
	}

	protected function deleteInheritance($tidStr,$cidStr){
		if($tidStr){
			//delete all inherited children traits for CIDs that will be modified
			$childrenStr = trim(implode(',',$this->getChildrenArr($tidStr)).','.$tidStr,' ,');
			$sql = "DELETE FROM kmdescr ".
				"WHERE (TID IN(".$childrenStr.")) ".
				"AND (CID IN(".$cidStr.")) AND (Inherited Is Not Null AND Inherited <> '')";
			//echo $sql;
			$this->conn->query($sql);
		}
	}

	protected function resetInheritance($tidStr, $cidStr){
		//Set inheritance for target and all children of target
		$cnt = 0;
		$childrenStr = trim(implode(',',$this->getChildrenArr($tidStr)).','.$tidStr,' ,');
		do{
			$sql = 'INSERT IGNORE INTO kmdescr( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) '.
				'SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, '.
				'd1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent '.
				'FROM taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID '.
				'INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid '.
				'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID '.
				'INNER JOIN taxa t2 ON ts2.tid = t2.tid '.
				'LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) '.
				'WHERE (ts1.taxauthid = '.$this->taxAuthId.') AND (ts2.taxauthid = '.$this->taxAuthId.') AND (ts2.tid = ts2.tidaccepted) '.
				'AND (d1.cid IN('.$cidStr.')) AND (t2.tid IN('.$childrenStr.')) AND (d2.CID Is Null) AND (t2.RankId <= 220)';
			//echo $sql.'<br/><br/>';
			if(!$this->conn->query($sql)){
				echo 'ERROR setting inheritance: '.$this->conn->error;
			}
			$cnt++;
		}while($this->conn->affected_rows && $cnt < 10);
	}

	protected function getChildrenArr($tid){
		//Return list of accepted taxa, not including target
		$retArr = Array();
		$targetStr = $tid;
		do{
			if(isset($targetList)) unset($targetList);
			$targetList = Array();
			$sql = 'SELECT t.tid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (ts.ParentTID In ('.$targetStr.')) AND (ts.tid = ts.tidaccepted)';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$targetList[] = $row->tid;
		    }
		    $rs->free();
			if($targetList){
				$targetStr = implode(",", $targetList);
				$retArr = array_merge($retArr, $targetList);
			}
		}while($targetList);
		return $retArr;
	}

	protected function getParentArr($tid){
 		$retArr = Array();
		$targetTid = $tid;
		while($targetTid){
			//$sql = 'SELECT parenttid FROM taxaenumtree WHERE taxauthid = 1 AND (tid = '.$this->tid.')';
			$sql = 'SELECT parenttid FROM taxstatus WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$targetTid.')';
			//echo $sql;
			$rs = $this->conn->query($sql);
		    if($row = $rs->fetch_object()){
		    	if(!$row->parenttid || $targetTid == $row->parenttid) break;
				$targetTid = $row->parenttid;
				if($targetTid) $retArr[] = $targetTid;
		    }
		}
		$rs->free();
		return $retArr;
	}

	//Setters and getters
	public function setTaxAuthId($id){
		if(is_numeric($id)) $this->taxAuthId = $id;
	}

	public function setLanguage($lang){
		$lang = strtolower($lang);
		if(strlen($lang) == 2){
			if($lang == 'en') $lang = 'english';
			if($lang == 'es') $lang = 'spanish';
			if($lang == 'fr') $lang = 'french';
		}
		$this->language = $lang;
	}

	//Misc functions
	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>