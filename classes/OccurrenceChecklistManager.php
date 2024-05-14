<?php
include_once('OccurrenceManager.php');

class OccurrenceChecklistManager extends OccurrenceManager{

	private $checklistTaxaCnt = 0;

	public function __construct($type='readonly'){
		parent::__construct($type);
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getChecklistTaxaCnt(){
		return $this->checklistTaxaCnt;
	}

	public function getChecklist($taxonAuthorityId){
		$returnVec = Array();
		$this->checklistTaxaCnt = 0;
		$sqlWhere = $this->getSqlWhere();
		if($sqlWhere){
			$sql = '';
			if($taxonAuthorityId && is_numeric($taxonAuthorityId)){
				$sql = 'SELECT DISTINCT ts2.family, t.sciname, t.tid '.
					'FROM omoccurrences o INNER JOIN taxstatus ts1 ON o.TidInterpreted = ts1.Tid '.
					'INNER JOIN taxa t ON ts1.TidAccepted = t.Tid '.
					'INNER JOIN taxstatus ts2 ON t.tid = ts2.tid '.
					$this->getTableJoins($sqlWhere).
					str_ireplace('o.sciname','t.sciname',str_ireplace('o.family','ts.family',$sqlWhere)).
					' AND ts1.taxauthid = '.$taxonAuthorityId.' AND ts2.taxauthid = '.$taxonAuthorityId.' AND t.RankId > 140 ';
			}
			else{
				$sql = 'SELECT DISTINCT IFNULL(ts1.family,o.family) AS family, o.sciname, o.tidinterpreted AS tid '.
					'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.tid '.
					'LEFT JOIN taxstatus ts1 ON t.tid = ts1.tid '.
					$this->getTableJoins($sqlWhere).
					$sqlWhere.' AND ((t.rankid > 140 AND ts1.taxauthid = 1) OR t.tid IS NULL) ';
			}
			$result = $this->conn->query($sql);
			while($r = $result->fetch_object()){
				$family = strtoupper($r->family);
				if(!$family) $family = 'undefined';
				$sciName = $r->sciname;
				if($sciName && substr($sciName,-5)!='aceae' && substr($sciName,-4)!='idae'){
					$returnVec[$family][$sciName] = $r->tid;
					$this->checklistTaxaCnt++;
				}
			}
			$result->free();
		}
		return $returnVec;
	}

	public function getTidChecklist($tidArr,$taxonFilter){
		$returnVec = Array();
		$tidStr = implode(',',$tidArr);
		$this->checklistTaxaCnt = 0;
		$sql = 'SELECT DISTINCT ts.family, t.sciname '.
			'FROM (taxstatus AS ts1 INNER JOIN taxa AS t ON ts1.TidAccepted = t.Tid) '.
			'INNER JOIN taxstatus AS ts ON t.tid = ts.tid '.
			'WHERE ts1.tid IN('.$tidStr.') AND ts1.taxauthid = '.$taxonFilter.' AND ts.taxauthid = '.$taxonFilter.' AND t.RankId > 140 ';
		//echo "<div>".$sql."</div>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$family = strtoupper($row->family);
			if(!$family) $family = 'undefined';
			if($row->sciname && substr($row->sciname,-5)!='aceae'){
				$returnVec[$family][] = $row->sciname;
				$this->checklistTaxaCnt++;
			}
		}
		return $returnVec;
	}

	public function buildSymbiotaChecklist($taxonAuthorityId,$tidArr = ''){
		$sqlTaxaBase = '';
		if($tidArr){
			if(is_numeric($taxonAuthorityId)){
				$sqlTaxaBase .= 'FROM taxstatus ts INNER JOIN taxa t ON ts.TidAccepted = t.Tid WHERE ts.tid IN('.implode(',',$tidArr).') AND ts.taxauthid = '.$taxonAuthorityId;
			}
			else $sqlTaxaBase .= 'FROM taxa t WHERE t.tid IN('.implode(',',$tidArr).') ';
		}
		else{
			$sqlWhere = $this->getSqlWhere();
			if($sqlWhere){
				if(is_numeric($taxonAuthorityId)){
					$sqlTaxaBase .= 'FROM omoccurrences o INNER JOIN taxstatus ts1 ON o.TidInterpreted = ts1.Tid '.
						'INNER JOIN taxa t ON ts1.TidAccepted = t.Tid '.$this->getTableJoins($sqlWhere).
						str_ireplace('o.sciname','t.sciname',str_ireplace('o.family','ts1.family',$sqlWhere)).'AND ts1.taxauthid = '.$taxonAuthorityId;
				}
				else $sqlTaxaBase .= 'FROM omoccurrences o INNER JOIN taxa t ON o.TidInterpreted = t.tid '.$this->getTableJoins($sqlWhere).$sqlWhere;
			}
		}
		$dynClid = 0;
		if($sqlTaxaBase){
			$searchStr = "";
			if($this->getTaxaSearchStr()) $searchStr .= "; ".$this->getTaxaSearchStr();
			if($this->getLocalSearchStr()) $searchStr .= "; ".$this->getLocalSearchStr();
			if($this->getCollectionSearchStr()) $searchStr .= "; ".$this->getCollectionSearchStr();
			$searchStr = trim($searchStr,'; ');
			if(strlen($searchStr) > 250) $searchStr = substr($searchStr,0,246).'...';
			$sql = 'INSERT INTO fmdynamicchecklists( name, details, uid, type, expiration ) '.
				'VALUES ("Specimen Checklist #'.time().'", "'.$this->cleanInStr($searchStr).'",'.($GLOBALS['SYMB_UID']?$GLOBALS['SYMB_UID']:'NULL').
				',"Specimen Checklist","'.date('Y-m-d H:i:s',time()+259200).'") ';
			if($this->conn->query($sql)){
				$dynClid = $this->conn->insert_id;
				$sqlTaxa = 'INSERT IGNORE INTO fmdyncltaxalink ( tid, dynclid ) SELECT DISTINCT t.tid, '.$dynClid.' '.$sqlTaxaBase.' AND t.RankId > 180';
				$this->conn->query($sqlTaxa);
				//Delete checklists that are greater than one week old
				$this->conn->query('DELETE FROM fmdynamicchecklists WHERE expiration < now()');
			}
			else{
				echo "ERROR: ".$this->conn->error;
			}
		}
		return $dynClid;
	}

	public function getTaxonAuthorityList(){
		$taxonAuthorityList = Array();
		$sql = "SELECT ta.taxauthid, ta.name FROM taxauthority ta WHERE (ta.isactive <> 0)";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$taxonAuthorityList[$row->taxauthid] = $row->name;
		}
		return $taxonAuthorityList;
	}
}
?>