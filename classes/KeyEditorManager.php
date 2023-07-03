<?php
include_once($SERVER_ROOT.'/classes/KeyManager.php');

class KeyEditorManager extends KeyManager{

	private $tid;
	private $taxonName;
	private $chars = Array();
	private $charStates = Array();
	private $selectedStates = Array();
	private $parentTid;
	private $rankId;
	private $charDepArray = Array();

	public function __construct(){
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function setTid($t){
		if(is_numeric($t)){
			$this->tid = $t;
			$sql = 'SELECT t.SciName, ts.ParentTID, t.RankId FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE ts.taxauthid = 1 AND (t.TID = '.$this->tid.')';
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->taxonName = $row->SciName;
				$this->parentTid = $row->ParentTID;
				$this->rankId = $row->RankId;
		    }
			$result->free();
		}
	}

	public function getTaxonName(){
		return $this->taxonName;
	}

	public function getTid(){
		return $this->tid;
	}

	public function getParentTid(){
		return $this->parentTid;
	}

	public function getRankId(){
		return $this->rankId;
	}

	public function isSelected($str){
		if($this->selectedStates && array_key_exists($str, $this->selectedStates)){
			return true;
		}
		else{
			return false;
		}
	}

	public function getInheritedStr($str){
		if($this->selectedStates && array_key_exists($str, $this->selectedStates)){
			return $this->selectedStates[$str];
		}
		else{
			return "";
		}
	}

	public function getCharList(){
		$this->setCharList();
		$this->setSelectedStates();
		return $this->chars;
	}

	private function setCharList(){
		//chars Array: HeadingName => (cid => charName)
		$cidArray = Array();
		$parentStr = implode(',',$this->getParentArr($this->tid));
		$sql = 'SELECT c.CharName, c.CID, ch.headingname, dep.CIDDependance, dep.CSDependance '.
			'FROM kmcharacters c INNER JOIN kmchartaxalink ctl ON c.CID = ctl.CID '.
			'LEFT JOIN kmcharheading ch ON c.hid = ch.hid '.
			'LEFT JOIN kmchardependance dep ON c.CID = dep.CID '.
			'WHERE (ch.language IS NULL OR ch.language = "English") AND (c.CID Not In (SELECT DISTINCT chartl.CID FROM kmchartaxalink chartl WHERE (chartl.TID In ('.$parentStr.')) AND (chartl.Relation="exclude"))) '.
			'AND (c.chartype = "UM" Or c.chartype="OM") AND (ctl.TID In ('.$parentStr.')) AND (ctl.Relation="include") '.
			'ORDER BY ch.SortSequence,c.SortSequence';
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$charKey = $row->CID;
			$cidArray[$charKey] = "";
			$charValue = $row->CharName;
			$charHeading = $row->headingname;
			$pos = strpos($charHeading,":");
			if($pos) $charHeading = substr($charHeading, $pos + 2);
			$this->chars[$charHeading][$charKey] = $charValue;

			//Set CharDependance array values;
			$cidDepValue = $row->CIDDependance;
			$csDepValue = $row->CSDependance;
			if($cidDepValue) $this->charDepArray[$charKey] = $cidDepValue.":".$csDepValue;

		}
		if($cidArray) $this->setCharStates(implode(",",array_keys($cidArray)));
		$result->free();
	}

	public function getCharDepArray(){
		return $this->charDepArray;
	}

	private function setCharStates($cidStr){
		//cs Array: cid => (cs => charStateName)
		$sql = 'SELECT cid, cs, charstatename FROM kmcs WHERE (cid In ('.$cidStr.')) ORDER BY sortSequence, cs';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$this->charStates[$row->cid][$row->cs] = $row->charstatename;
	    }
		$result->free();
	}

	public function getCharStates(){
		return $this->charStates;
	}

	private function setSelectedStates(){
		//selectedStates Array: cid_cs (ex: 1_3=>" (i)")
		$sql = "SELECT d.CID, d.CS, d.Inherited FROM kmdescr d WHERE (d.TID = $this->tid)";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$cid = $row->CID;
			$cs = $row->CS;
			$this->selectedStates[$cid."_".$cs] = ($row->Inherited?" (i)":"");
		}
		$result->free();
	}

	public function processTaxa($addStates,$removeStates){
		$aStates = $this->processStateArr($addStates);
		$rStates = $this->processStateArr($removeStates);
		$charUsedStr = implode(',',array_unique(array_merge(array_keys($aStates),array_keys($rStates))));

		if($charUsedStr) $this->deleteInheritance($this->tid,$charUsedStr);

		if($rStates){
			//Delete all char/cs combinations in $rStates
			foreach($rStates as $cid => $csArr){
				foreach($csArr as $cs){
					$this->deleteDescr($this->tid, $cid, $cs);
				}
			}
		}

		if($aStates){
			//Add all char/cs combinations in $aStates
			foreach($aStates as $cid => $csArr){
				foreach($csArr as $cs){
					$this->insertDescr($this->tid, $cid, $cs);
				}
			}
		}

		$this->resetInheritance($this->tid, $charUsedStr);
	}

	private function processStateArr($stateArr){
		$retArr = array();
		if($stateArr){
			foreach($stateArr as $value){
				$tok = explode("_",$value);
				$cid = $tok[0];
				$cs = $tok[1];
				$retArr[$cid][] = $cs;
			}
		}
		return $retArr;
	}
}
?>