<?php
include_once($SERVER_ROOT.'/classes/KeyManager.php');

class KeyMatrixEditor extends KeyManager{

	private $clid;
	private $childClidArr = array();
	private $cid;
	private $taxaArr = array();
	private $stateArr = array();
	private $descrArr = array();
	private $cnt = 0;

	public function __construct(){
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getCharList($tidFilter){
		$retArr = Array();
		$sql = 'SELECT DISTINCT ch.headingName, c.cid, c.charName '.
			'FROM kmcharacters c INNER JOIN kmchartaxalink ctl ON c.cid = ctl.cid '.
			'LEFT JOIN kmcharheading ch ON c.hid = ch.hid '.
			'LEFT JOIN kmchardependance cd ON c.cid = cd.cid '.
			'WHERE (ch.language IS NULL OR ch.language = "'.$this->language.'") AND (c.chartype IN("UM","OM")) ';
		$strFrag = '';
		if($tidFilter && is_numeric($tidFilter)){
			$strFrag = implode(',',$this->getParentArr($tidFilter)).','.$tidFilter;
		}
		else{
			$parTidArr = $this->getChecklistParentArr();
			if($parTidArr) $strFrag = implode(',',$parTidArr);
		}
		if($strFrag){
			$strFrag = trim($strFrag, ', ');
			$sql .= 'AND (ctl.tid In ('.$strFrag.') AND ctl.relation = "include") '.
				'AND (c.cid NOT In(SELECT DISTINCT cid FROM kmchartaxalink WHERE (tid In ('.$strFrag.') AND relation = "exclude"))) ';
		}
		$sql .= 'ORDER BY c.hid, c.sortSequence, c.charName';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->headingName][$r->cid] = $r->charName;
		}
		$rs->free();
		return $retArr;
	}

	private function getChecklistParentArr(){
		$retArr = Array();
		$clidStr = $this->clid;
		if($this->childClidArr){
			$clidStr .= ','.implode(',',array_keys($this->childClidArr));
		}
		$sql = 'SELECT DISTINCT parenttid FROM fmchklsttaxalink c INNER JOIN taxaenumtree e ON c.tid = e.tid WHERE (e.taxauthid = '.$this->taxAuthId.') AND (c.clid IN('.$clidStr.'))';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->parenttid;
		}
		$rs->free();
		return $retArr;
	}

	private function setStates(){
		$sql = 'SELECT charstatename, cs FROM kmcs WHERE (cid = '.$this->cid.') ';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$this->stateArr[$row->cs] = $row->charstatename;
		}
		$rs->free();
		ksort($this->stateArr);
	}

	public function echoTaxaList($tidFilter, $generaOnly = false){
		$tidArr = Array();
		if(!is_numeric($tidFilter)) $tidFilter = 0;
		//Get taxonomic hierarchy limits
		$tidLimitArr = array();
		$sql = 'SELECT tid, relation FROM kmchartaxalink WHERE (cid = '.$this->cid.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$tidLimitArr[$r->relation][] = $r->tid;
		}
		$rs->free();

		//Get accepted taxa
		$clidStr = $this->clid;
		if($this->childClidArr){
			$clidStr .= ','.implode(',',array_keys($this->childClidArr));
		}
		$sql = 'SELECT DISTINCT ts.tidaccepted FROM taxstatus ts INNER JOIN fmchklsttaxalink c ON ts.tid = c.tid ';
		$sqlWhere = 'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (c.clid IN('.$clidStr.')) ';
		if(array_key_exists('include', $tidLimitArr)){
			$sql .= 'INNER JOIN taxaenumtree e1 ON ts.tid = e1.tid ';
			$sqlWhere .= ' AND (e1.taxauthid = '.$this->taxAuthId.') AND (e1.parenttid IN('.implode(',',$tidLimitArr['include']).')) ';
		}
		if($tidFilter){
			$sql .= 'INNER JOIN taxaenumtree e2 ON ts.tid = e2.tid ';
			$sqlWhere .= ' AND (e2.taxauthid = '.$this->taxAuthId.') AND (e2.parenttid = '.$tidFilter.') ';
		}
		$sql .= $sqlWhere;
		//echo $sql.'<br/>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$tidArr[$r->tidaccepted] = $r->tidaccepted;
		}
		$rs->free();
		if(array_key_exists('exclude', $tidLimitArr)){
			$sql = 'SELECT DISTINCT ts.tid '.
				'FROM taxstatus ts INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN fmchklsttaxalink c ON ts2.tid = c.tid '.
				'INNER JOIN taxaenumtree e ON ts.tid = e.tid '.
				'WHERE (ts.taxauthid = 1) AND (ts2.taxauthid = 1) AND (c.clid IN('.$clidStr.')) AND (e.taxauthid = 1) AND (e.parenttid IN('.implode(',',$tidLimitArr['exclude']).'))';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				unset($tidArr[$r->tid]);
			}
			$rs->free();
		}
		if($tidArr){
			//Get parents
			$sql2 = 'SELECT DISTINCT t.tid, t.sciname, ts.parenttid, t.rankid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'LEFT JOIN taxaenumtree e ON t.tid = e.parenttid '.
				'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (e.taxauthid = '.$this->taxAuthId.' OR e.taxauthid IS NULL) AND (ts.tid = ts.tidaccepted) '.
				'AND (e.tid IN('.implode(',',$tidArr).') OR t.tid IN('.implode(',',$tidArr).')) ';
			if($generaOnly) $sql2 .= 'AND (t.rankid BETWEEN 140 AND 180) ';
			else  $sql2 .= 'AND (t.rankid BETWEEN 140 AND 220) ';
			$sql2 .= 'ORDER BY t.sciname';
			//echo $sql2.'<br/>';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$pTid = $r2->parenttid;
				if($r2->rankid == 140) $pTid = 'p'.$r2->tid;
				$this->taxaArr[$pTid][$r2->tid] = $r2->sciname;
				$tidArr[$r2->tid] = $r2->tid;
			}
			$rs2->free();

			//Get descriptions
			$sql3 = 'SELECT tid, cid, cs, inherited FROM kmdescr WHERE (cid='.$this->cid.') AND (tid IN('.implode(',',$tidArr).'))';
			$rs3 = $this->conn->query($sql3);
			while($r3 = $rs3->fetch_object()){
				$this->descrArr[$r3->tid][$r3->cs] = ($r3->inherited?1:0);
			}
			$rs3->free();

			//Create and output header
			$this->setStates();
			echo '<tr><th><b><span style="font-size:120%;">'.$this->getCharacterName().':</span></b><br/><input type="submit" name="action" value="Save Changes" onclick="submitAttrs()" /></th>';
			foreach($this->stateArr as $cs => $csName){
				echo '<th style="text-align:center">'.str_replace(" ","<br/>",$csName).'</th>';
			}
			echo '</tr>';
			foreach($this->taxaArr as $parentTid => $tArr){
				if(!in_array($parentTid, $tidArr)){
					$this->processTaxa($parentTid);
				}
			}
		}
	}

	private function processTaxa($tid,$indent=0){
		if(isset($this->taxaArr[$tid])){
			$indent++;
			$childArr = $this->taxaArr[$tid];
			asort($childArr);
			foreach($childArr as $childTid => $childSciname){
				$this->echoTaxaRow($childTid,$childSciname,$indent);
				$this->processTaxa($childTid,$indent);
			}
		}
	}

	private function echoTaxaRow($tid,$sciname,$indent = 0){
		if(!is_numeric($indent)) $indent = 0;
		if(is_numeric($tid)){
			echo '<tr><td>';
			echo '<span style="margin-left:'.($indent*10).'px"><b>'.($indent?'<i>':'').htmlspecialchars($sciname, ENT_QUOTES, 'UTF-8').($indent?'</i>':'').'</b></span>';
			echo '<a href="editor.php?tid=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank"> <img src="../../images/edit.png" /></a>';
			echo '</td>';
			foreach($this->stateArr as $cs => $csName){
				$isSelected = false;
				$isInherited = false;
				if(isset($this->descrArr[$tid][$cs])){
					$isSelected = true;
					if($this->descrArr[$tid][$cs]) $isInherited = true;
				}
				echo '<td align="center" style="width:15px;white-space:nowrap;">';
				echo '<input type="checkbox" name="csDisplay" onclick="attrChanged(this,\''.$tid.'-'.$cs.'\')" '.($isSelected && !$isInherited?'CHECKED':'').' title="'.$csName.'"/>'.($isInherited?'(I)':'');
				echo "</td>\n";
			}
			echo '</tr>';
			$this->cnt++;
		}
	}

	public function processAttributes($rAttrs,$aAttrs){
		$removeArr = $this->processAttrArr($rAttrs);
		$addArr = $this->processAttrArr($aAttrs);
		$tidUsedStr = implode(',',array_unique(array_merge(array_keys($removeArr),array_keys($addArr))));
		if($tidUsedStr){
			$this->deleteInheritance($tidUsedStr,$this->cid);
			if($addArr) $this->processAddAttributes($addArr);
			if($removeArr) $this->processRemoveAttributes($removeArr);
			$this->resetInheritance($tidUsedStr,$this->cid);
		}
	}

	private function processAttrArr($inputArr){
		$retArr = array();
		if($inputArr){
			foreach($inputArr as $v){
				if($v){
					$t = explode("-",$v);
					$retArr[$t[0]][] = $t[1];
				}
			}
		}
 		return $retArr;
	}

	private function processRemoveAttributes($inputArr){
 		foreach($inputArr as $tid => $csArr){
 			foreach($csArr as $cs){
				$this->deleteDescr($tid, $this->cid, $cs);
 			}
 		}
	}

	private function processAddAttributes($addArr){
 		foreach($addArr as $tid => $csArr){
 			foreach($csArr as $cs){
				$this->insertDescr($tid, $this->cid, $cs);
 			}
 		}
	}

	//Misc functions
	public function getTaxaQueryList(){
		$retArr = Array();
		$clidStr = $this->clid;
		if($this->childClidArr){
			$clidStr .= ','.implode(',',array_keys($this->childClidArr));
		}
		$sql = 'SELECT DISTINCT t.tid, t.sciname '.
			'FROM fmchklsttaxalink c INNER JOIN taxaenumtree e ON c.tid = e.tid '.
			'INNER JOIN taxa t ON e.parenttid = t.tid '.
			'WHERE (c.clid IN('.$clidStr.')) AND (t.rankid < 181) AND (e.taxauthid = 1) '.
			'ORDER BY t.sciname ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid] = $r->sciname;
		}
		$rs->free();
		return $retArr;
	}

	public function getCharacterName(){
		$retStr = '';
		if($this->cid){
			$sql = 'SELECT charname FROM kmcharacters WHERE (cid = '.$this->cid.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retStr = $r->charname;
			}
			$rs->free();
		}
		return $retStr;
	}

	//Setter and getters
	public function setCid($cid){
		if(is_numeric($cid)) $this->cid = $cid;
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