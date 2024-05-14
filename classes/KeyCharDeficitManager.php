<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class KeyCharDeficitManager{

	private $conn;
	private $clid;
	private $childClidArr = array();
	private $taxaCount;
	private $project;
	private $language = "English";

	function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function setLanguage($lang){
		$this->language = $lang;
	}

	public function setProject($proj){
		$this->project = $proj;
	}

	public function getClQueryList(){
		$returnList = Array();

		$sql = "SELECT DISTINCT cl.Name, cl.CLID ".
			"FROM (fmchecklists cl INNER JOIN fmchklstprojlink cpl ON cl.CLID = cpl.clid) ".
			"INNER JOIN fmprojects p ON cpl.pid = p.pid ";
		if($this->project) $sql .= "WHERE ".(intval($this->project)?"(p.pid = ".$this->project.") ":"(p.projname = '".$this->project."') ");
		$sql .= "ORDER BY cl.Name";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnList[$row->CLID] = $row->Name;
		}
		$result->free();
		return $returnList;
	}

	public function getTaxaQueryList(){
		$returnList = Array();

		$sql = "SELECT DISTINCT t.RankId, t.SciName, t.TID ".
			"FROM taxa t INNER JOIN kmchartaxalink ctl ON t.TID = ctl.TID ".
			"ORDER BY t.RankId, t.SciName";

		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnList[$row->TID] = $row->SciName;
		}
		$result->free();
		return $returnList;
	}

	public function getCharList($cfVal, $cidVal){
		$returnArray = Array();
		if($cfVal){
			$strFrag = implode(",",$this->getParents($cfVal));

			/*$sql = "SELECT DISTINCT charnames.Heading, charnames.CID, charnames.CharName ".
				"FROM (chartaxalink INNER JOIN characters ON chartaxalink.CID = characters.CID) INNER JOIN charnames ON characters.CID = charnames.CID ".
				"WHERE (((charnames.CID) Not In (SELECT DISTINCT chartaxalink.CID FROM chartaxalink WHERE (((chartaxalink.TID) In ($strFrag)) ".
				"AND ((chartaxalink.Relation)='exclude')))) AND ((characters.Type)='UM' Or (characters.Type)='OM') AND ((charnames.Language)='".
				$this->language."') AND ((chartaxalink.TID) In ($strFrag))) ".
				"ORDER BY charnames.Heading, charnames.CID";*/
			$sql = 'SELECT DISTINCT ch.headingname, c.CID, c.CharName '.
				'FROM kmchartaxalink ctl INNER JOIN kmcharacters c ON ctl.CID = c.CID '.
				'INNER JOIN kmcharheading ch ON c.hid = ch.hid '.
				'WHERE (c.CID NOT IN (SELECT DISTINCT CID FROM kmchartaxalink WHERE (TID In('.$strFrag.')) AND (Relation="exclude")) '.
				'AND (c.chartype IN("UM","OM")) AND (ch.language="English") AND (ctl.TID In ('.$strFrag.')) '.
				'ORDER BY c.hid, c.CID';
			//echo $sql;
			$headingArray = Array();		//Heading => Array(CID => CharName)
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$headingArray[$row->headingname][$row->CID] = $row->CharName;
			}
			$result->free();

			//Put harvested data into a simple output array
			ksort($headingArray);
			foreach($headingArray as $h => $charData){
				$returnArray[] = "<div style='margin-top:1em;font-size:125%;'>$h</div>";
				ksort($charData);
				foreach($charData as $cidKey => $charValue){
					$returnArray[] = "<div> <input name='cid' type='radio' value='".$cidKey."' ".($cidKey == $cidVal?"checked":"").">$charValue</div>";
				}
			}
		}
		return $returnArray;
	}

	private function getParents($t){
		//Returns a list of parent TIDs, including target
 		$parentList = Array();
		$targetTid = $t;
		$parentList[] = $targetTid;
		while($targetTid){
			$sql = "SELECT ts.ParentTID FROM taxstatus ts WHERE (ts.TID = ".$targetTid.") AND ts.taxauthid = 1";
			//echo $sql;
			$result = $this->conn->query($sql);
		    if ($row = $result->fetch_object()){
		    	if($targetTid == $row->ParentTID){
		    		break;
		    	}
				$targetTid = $row->ParentTID;
				if($targetTid) $parentList[] = $targetTid;
		    }
		}
		if($targetTid) $result->free();
		return $parentList;
	}

	public function getTaxaList($cidVal, $cfVal){
		$returnArray = Array();				//family => Array(tid => sciname)
		$spArray = Array();
		$sppStr = $this->getChildren($cidVal, $cfVal);
		$sql = "SELECT DISTINCT t.TID, ts.Family, t.SciName ".
			"FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) ".
			"LEFT JOIN (SELECT DISTINCT d1.TID FROM kmdescr d1 WHERE (d1.CID = ".$cidVal.")) AS d ON t.TID = d.TID ".
			"WHERE (ts.taxauthid = 1) AND (t.TID IN (".$sppStr.") AND (d.TID) Is Null) ".
			"ORDER BY ts.Family, t.SciName";
		//echo $sql;
		$result = $this->conn->query($sql);
		$this->taxaCount = 0;
		while($row = $result->fetch_object()){
			$returnArray[$row->Family][$row->TID] = $row->SciName;
			$this->taxaCount++;
		}
		$result->free();
		return $returnArray;
	}

	private function getChildren($cidVal, $cfVal){
		//Returns a list of children TIDs that are members of selected checklist and only of the 220 rank
 		//Get taxa to exclude
 		$excludeArray = Array();
 		$sqlEx = "SELECT c.TID FROM kmchartaxalink c WHERE (c.CID = ".$cidVal.") AND c.Relation = 'exclude'";
		$resultEx = $this->conn->query($sqlEx);
		while($row = $resultEx->fetch_object()){
 			$excludeArray[] = $row->TID;
		}
		$excludeStr = implode(",",$excludeArray);
		$resultEx->free();

		//get Children
		$clidStr = $this->clid;
		if($this->childClidArr){
			$clidStr .= ','.implode(',',array_keys($this->childClidArr));
		}
		$children = Array();
		$targetStr = $cfVal;
		do{
			if(isset($targetList)) unset($targetList);
			$targetList = Array();
			$sql = "SELECT DISTINCT t.TID, t.rankid, cl.clid ".
				"FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) ".
				"LEFT JOIN (SELECT ctl.tid, ctl.clid From fmchklsttaxalink ctl WHERE (ctl.clid IN(".$clidStr."))) AS cl ".
				"ON ts.TID = cl.tid ".
				"WHERE ts.taxauthid = 1 AND (ts.ParentTID IN(".$targetStr.")) ";
			if($excludeStr) $sql .= "AND (t.TID NOT IN(".$excludeStr."))";
			//echo $sql."<br/><br/>";
			$rankId = 0;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$rankId = $row->rankid;
				$targetList[] = $row->TID;
				if($rankId == 220 && $row->clid) $children[] = $row->TID;
			}
			if($targetList){
				$targetStr = implode(',', $targetList);
			}
		}while($targetList && $rankId > 10);
		$returnStr = implode(',',$children);
		return $returnStr;
	}

	public function getTaxaCount(){
		return $this->taxaCount;
	}

	public function setClid($clid){
		if(is_numeric($clid)){
			$this->clid = $clid;
			//Get children checklists
			$sqlBase = 'SELECT ch.clidchild, cl2.name
				FROM fmchecklists cl INNER JOIN fmchklstchildren ch ON cl.clid = ch.clid
				INNER JOIN fmchecklists cl2 ON ch.clidchild = cl2.clid
				WHERE (cl2.type != "excludespp") AND (ch.clid != ch.clidchild) AND cl.clid IN(';
			$sql = $sqlBase.$this->clid.')';
			do{
				$childStr = '';
				$rsChild = $this->conn->query($sql);
				while($r = $rsChild->fetch_object()){
					$this->childClidArr[$r->clidchild] = $r->name;
					$childStr .= ','.$r->clidchild;
				}
				$sql = $sqlBase.substr($childStr,1).')';
			}while($childStr);
		}
	}
}
?>