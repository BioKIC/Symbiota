<?php
include_once($SERVER_ROOT.'/classes/RpcBase.php');

class RpcTaxonomy extends RpcBase{

	private $taxAuthID = 1;

	function __construct(){
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getTaxaSuggest($term, $rankLimit, $rankLow, $rankHigh){
		$retArr = Array();
		//sanitation
		if(!is_numeric($rankLimit)) $rankLimit = 0;
		if(!is_numeric($rankLow)) $rankLow = 0;
		if(!is_numeric($rankHigh)) $rankHigh = 0;

		if($term){
			$term = $this->cleanInStr($term);
			$termArr = explode(' ',$term);
			foreach($termArr as $k => $v){
				if(mb_strlen($v) == 1) unset($termArr[$k]);
			}
			$sql = 'SELECT DISTINCT t.tid, t.sciname, t.author FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE ts.taxauthid = '.$this->taxAuthID.' AND (t.sciname LIKE "'.$term.'%" ';
			$sqlFrag = '';
			if($unit1 = array_shift($termArr)) $sqlFrag =  't.unitname1 LIKE "'.$unit1.'%" ';
			if($unit2 = array_shift($termArr)) $sqlFrag .=  'AND t.unitname2 LIKE "'.$unit2.'%" ';
			if($sqlFrag) $sql .= 'OR ('.$sqlFrag.')';
			$sql .= ') ';
			if($rankLimit) $sql .= 'AND (t.rankid = '.$rankLimit.') ';
			else{
				if($rankLow) $sql .= 'AND (t.rankid > '.$rankLow.' OR t.rankid IS NULL) ';
				if($rankHigh) $sql .= 'AND (t.rankid < '.$rankHigh.' OR t.rankid IS NULL) ';
			}
			$sql .= 'ORDER BY t.sciname';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()) {
				$sciname = $r->sciname.' '.$r->author;
				$retArr[] = array('id' => $r->tid,'label' => $sciname);
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getAcceptedTaxa($queryTerm){
		$retArr = Array();
		$sql = 'SELECT t.tid, t.sciname, t.author '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (ts.taxauthid = '.$this->taxAuthID.') AND (ts.tid = ts.tidaccepted) AND (t.sciname LIKE "'.$this->cleanInStr($queryTerm).'%") '.
			'ORDER BY t.sciname LIMIT 20';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$sciname = $r->sciname.' '.$r->author;
			$retArr[] = array('id' => $r->tid,'label' => $sciname);
		}
		$rs->free();
		return $retArr;
	}

	public function getTid($sciName, $rankid, $author){
		$retStr = 0;
		//Sanitation
		if(!is_numeric($rankid)) $rankid = 0;
		$sql = 'SELECT t.tid FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (ts.taxauthid = '.$this->taxAuthID.') AND (t.sciname = "'.$this->cleanInStr($sciName).'" OR CONCAT(t.sciname," ",t.author) = "'.$this->cleanInStr($sciName).'") ';
		if($rankid) $sql .= ' AND t.rankid = '.$rankid;
		if($author) $sql .= ' AND t.author = "'.$this->cleanInStr($author).'" ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retStr = $r->tid;
		}
		$rs->free();
		return $retStr;
	}

	public function getDynamicChildren($objId, $targetId, $displayAuthor, $limitToOccurrences, $isEditor){
		$retArr = Array();
		$childArr = Array();
		//Sanitation
		if(!is_numeric($targetId)) $targetId = 0;
		if(!is_numeric($displayAuthor)) $displayAuthor = 0;
		if(!is_numeric($isEditor)) $isEditor = 0;

		//Set rank array
		$taxonUnitArr = array(1 => 'Organism',10 => 'Kingdom');
		$sqlR = 'SELECT rankid, rankname FROM taxonunits';
		$rsR = $this->conn->query($sqlR);
		while($rR = $rsR->fetch_object()){
			$taxonUnitArr[$rR->rankid] = $rR->rankname;
		}
		$rsR->free();

		$urlPrefix = '../index.php?taxon=';
		if($isEditor) $urlPrefix = 'taxoneditor.php?tid=';

		if($objId == 'root'){
			$retArr['id'] = 'root';
			$retArr['label'] = 'root';
			$retArr['name'] = 'root';
			if($isEditor) $retArr['url'] = 'taxoneditor.php';
			else $retArr['url'] = '../index.php';
			$retArr['children'] = Array();
			$lowestRank = '';
			$sql = 'SELECT MIN(t.RankId) AS RankId FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE (t.rankid != 0) AND (ts.taxauthid = '.$this->taxAuthID.') LIMIT 1 ';
			//echo $sql.'<br>';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$lowestRank = $row->RankId;
			}
			$rs->free();
			$sql1 = 'SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid FROM taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid WHERE ts.taxauthid = '.$this->taxAuthID.' AND t.RankId = '.$lowestRank.' ';
			//echo "<div>".$sql1."</div>";
			$rs1 = $this->conn->query($sql1);
			$i = 0;
			while($row1 = $rs1->fetch_object()){
				$rankName = (isset($taxonUnitArr[$row1->rankid])?$taxonUnitArr[$row1->rankid]:'Unknown');
				$label = '2-'.$row1->rankid.'-'.$rankName.'-'.$row1->sciname;
				$sciName = $row1->sciname;
				if($row1->tid == $targetId) $sciName = '<b>'.$sciName.'</b>';
				$sciName = "<span style='font-size:75%;'>".$rankName.":</span> ".$sciName.($displayAuthor?" ".$row1->author:"");
				$childArr[$i]['id'] = $row1->tid;
				$childArr[$i]['label'] = $label;
				$childArr[$i]['name'] = $sciName;
				$childArr[$i]['url'] = $urlPrefix.$row1->tid;
				$sql3 = 'SELECT tid FROM taxaenumtree WHERE taxauthid = '.$this->taxAuthID.' AND parenttid = '.$row1->tid.' LIMIT 1 ';
				//echo "<div>".$sql3."</div>";
				$rs3 = $this->conn->query($sql3);
				if($row3 = $rs3->fetch_object()){
					$childArr[$i]['children'] = true;
				}
				else{
					$sql4 = 'SELECT DISTINCT tid, tidaccepted FROM taxstatus WHERE (taxauthid = '.$this->taxAuthID.') AND (tidaccepted = '.$row1->tid.') ';
					//echo "<div>".$sql4."</div>";
					$rs4 = $this->conn->query($sql4);
					while($row4 = $rs4->fetch_object()){
						if($row4->tid != $row4->tidaccepted){
							$childArr[$i]['children'] = true;
						}
					}
					$rs4->free();
				}
				$rs3->free();
				$i++;
			}
			$rs1->free();
		}
		else{
			$objId = filter_var($objId, FILTER_SANITIZE_NUMBER_INT);
			//Get children, but only accepted children
			$sql = 'SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid FROM taxa AS t INNER JOIN taxstatus AS ts ON t.tid = ts.tid ';
			if($limitToOccurrences) $sql .= 'INNER JOIN taxaenumtree e ON t.tid = e.parenttid INNER JOIN omoccurrences o ON e.tid = o.tidInterpreted ';
			$sql .=	'WHERE (ts.taxauthid = '.$this->taxAuthID.') AND (ts.tid = ts.tidaccepted) AND ((ts.parenttid = '.$objId.') OR (t.tid = '.$objId.')) ';
			//echo $sql.'<br>';
			$rs = $this->conn->query($sql);
			$i = 0;
			while($r = $rs->fetch_object()){
				$rankName = (isset($taxonUnitArr[$r->rankid])?$taxonUnitArr[$r->rankid]:'Unknown');
				$label = '2-'.$r->rankid.'-'.$rankName.'-'.$r->sciname;
				$sciName = $r->sciname;
				if($r->rankid >= 180) $sciName = '<i>'.$sciName.'</i>';
				if($r->tid == $targetId) $sciName = '<b>'.$sciName.'</b>';
				$sciName = "<span style='font-size:75%;'>".$rankName.":</span> ".$sciName.($displayAuthor?" ".$r->author:"");
				if($r->tid == $objId){
					$retArr['id'] = $r->tid;
					$retArr['label'] = $label;
					$retArr['name'] = $sciName;
					$retArr['url'] = $urlPrefix.$r->tid;
					$retArr['children'] = Array();
				}
				else{
					$childArr[$i]['id'] = $r->tid;
					$childArr[$i]['label'] = $label;
					$childArr[$i]['name'] = $sciName;
					$childArr[$i]['url'] = $urlPrefix.$r->tid;
					$sql3 = 'SELECT tid FROM taxaenumtree WHERE taxauthid = '.$this->taxAuthID.' AND parenttid = '.$r->tid.' LIMIT 1 ';
					//echo 'sql3: '.$sql3.'<br/>';
					$rs3 = $this->conn->query($sql3);
					if($row3 = $rs3->fetch_object()){
						$childArr[$i]['children'] = true;
					}
					else{
						$sql4 = 'SELECT DISTINCT tid, tidaccepted FROM taxstatus WHERE taxauthid = '.$this->taxAuthID.' AND tidaccepted = '.$r->tid.' ';
						//echo 'sql4: '.$sql4.'<br/>';
						$rs4 = $this->conn->query($sql4);
						while($row4 = $rs4->fetch_object()){
							if($row4->tid != $row4->tidaccepted){
								$childArr[$i]['children'] = true;
							}
						}
						$rs4->free();
					}
					$rs3->free();
					$i++;
				}
			}
			$rs->free();

			//Get synonyms for all accepted taxa
			$sqlSyns = 'SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid '.
				'FROM taxa AS t INNER JOIN taxstatus AS ts ON t.tid = ts.tid '.
				'WHERE (ts.tid <> ts.tidaccepted) AND (ts.taxauthid = '.$this->taxAuthID.') AND (ts.tidaccepted = '.$objId.')';
			//echo 'syn: '.$sqlSyns.'<br/>';
			$rsSyns = $this->conn->query($sqlSyns);
			while($row = $rsSyns->fetch_object()){
				$rankName = (isset($taxonUnitArr[$row->rankid])?$taxonUnitArr[$row->rankid]:'Unknown');
				$label = '1-'.$row->rankid.'-'.$rankName.'-'.$row->sciname;
				$sciName = $row->sciname;
				if($row->rankid >= 180) $sciName = '<i>'.$sciName.'</i>';
				if($row->tid == $targetId) $sciName = '<b>'.$sciName.'</b>';
				$sciName = '['.$sciName.']'.($displayAuthor?' '.$row->author:'');
				$childArr[$i]['id'] = $row->tid;
				$childArr[$i]['label'] = $label;
				$childArr[$i]['name'] = $sciName;
				$childArr[$i]['url'] = $urlPrefix.$row->tid;
				$i++;
			}
			$rsSyns->free();
		}

		usort($childArr, function ($a,$b){ return strnatcmp($a['label'],$b['label']);} );
		$retArr['children'] = $childArr;
		return $retArr;
	}

	//Setters and getters
	public function setTaxAuthId($id){
		if(is_numeric($id)) $this->taxAuthID = $id;
	}

	public function isValidApiCall(){
		//Verification also happening within haddler checking is user is logged in and a valid admin/editor
		$status = parent::isValidApiCall();
		if(!$status) return false;
		return true;
	}
}
?>