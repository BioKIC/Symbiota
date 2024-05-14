<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceTaxaManager.php');

class TaxonSearchSupport{

	private $conn;
	private $queryString;
	private $taxonType;
	private $rankLow = 0;
	private $rankHigh;

 	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
 	}

	public function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getTaxaSuggest(){
		if($this->rankLow || $this->rankHigh) return $this->getTaxaSuggestByRank();
		else return $this->getTaxaSuggestByType();
	}

	private function getTaxaSuggestByType(){
		$retArr = Array();
		if($this->queryString){
			$sql = "";
			if($this->taxonType == TaxaSearchType::ANY_NAME){
			    global $LANG;
			    $sql =
			    "SELECT DISTINCT tid, CONCAT('".$LANG['SELECT_1-5'].": ',v.vernacularname) AS sciname ".
			    "FROM taxavernaculars v ".
			    "WHERE v.vernacularname LIKE '%".$this->queryString."%' ".

			    "UNION ".

			    "SELECT DISTINCT tid, CONCAT('".$LANG['SELECT_1-2'].": ', sciname) AS sciname ".
			    "FROM taxa ".
			    "WHERE sciname LIKE '%".$this->queryString."%' AND rankid > 179 ".

			    "UNION ".

			    "SELECT DISTINCT tid, CONCAT('".$LANG['SELECT_1-3'].": ', sciname) AS sciname ".
			    "FROM taxa ".
			    "WHERE sciname LIKE '".$this->queryString."%' AND rankid = 140 ".

			    "UNION ".

			    "SELECT tid, CONCAT('".$LANG['SELECT_1-4'].": ',sciname) AS sciname ".
			    "FROM taxa ".
			    "WHERE sciname LIKE '".$this->queryString."%' AND rankid > 20 AND rankid < 180 AND rankid != 140 ";

			}
			elseif($this->taxonType == TaxaSearchType::SCIENTIFIC_NAME){
				$sql = 'SELECT tid, sciname FROM taxa WHERE sciname LIKE "'.$this->queryString.'%" LIMIT 30';
			}
			elseif($this->taxonType == TaxaSearchType::FAMILY_ONLY){
				$sql = 'SELECT tid, sciname FROM taxa WHERE rankid = 140 AND sciname LIKE "'.$this->queryString.'%" LIMIT 30';
			}
			elseif($this->taxonType == TaxaSearchType::TAXONOMIC_GROUP){
				$sql = 'SELECT tid, sciname FROM taxa WHERE rankid > 20 AND rankid < 180 AND sciname LIKE "'.$this->queryString.'%" LIMIT 30';
			}
			elseif($this->taxonType == TaxaSearchType::COMMON_NAME){
				$sql = 'SELECT DISTINCT v.tid, CONCAT(v.vernacularname, " (", t.sciname, ")") AS sciname
					FROM taxavernaculars v INNER JOIN taxa t ON v.tid = t.tid
					WHERE v.vernacularname LIKE "%'.$this->queryString.'%" LIMIT 50';
				//$sql = 'SELECT DISTINCT tid, vernacularname AS sciname FROM taxavernaculars WHERE vernacularname LIKE "%'.$this->queryString.'%" LIMIT 50 ';
			}
			else{
				$sql = 'SELECT tid, sciname FROM taxa WHERE sciname LIKE "'.$this->queryString.'%" LIMIT 20';
			}
			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr[] = array('id' => $r->tid, 'value' => $r->sciname);
			}
			$rs->free();
		}
		return $retArr;
	}

	private function getTaxaSuggestByRank(){
		$retArr = Array();
		if($this->queryString){
			$sql = 'SELECT sciname FROM taxa WHERE (sciname LIKE "'.$this->queryString.'%") ';
			if(is_numeric($this->rankLow)){
				if($this->rankHigh) $sql .= 'AND (rankid BETWEEN '.$this->rankLow.' AND '.$this->rankHigh.') ';
				else $sql .= 'AND (rankid = '.$this->rankLow.') ';
			}
			$sql .= 'LIMIT 30';
			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr[] = $r->sciname;
			}
			$rs->free();
		}
		return $retArr;
	}

	//Setters and getters
	public function setQueryString($queryString){
		//$queryString = $this->cleanInStr($queryString);
		$queryString = preg_replace('/[\+\=@$%]+/i', '', $queryString);
		if(strpos($queryString, ' ')){
			$queryString = str_ireplace(array('"', "'"), '_', $queryString);
			$queryString = preg_replace('/\s{1}x{1}$/i', ' _', $queryString);
			$queryString = preg_replace('/\s{1}x{1}\s{1}/i', ' _ ', $queryString);
			$queryString = str_ireplace(' x ', ' _ ', $queryString);
			$queryString = str_ireplace(' x', ' _', $queryString);
		}
		$this->queryString = $queryString;
	}

	public function setTaxonType($t){
		if(is_numeric($t)) $this->taxonType = $t;
	}

	public function setRankLow($rank){
		if(is_numeric($rank)) $this->rankLow = $rank;
	}

	public function setRankHigh($rank){
		if(is_numeric($rank)) $this->rankHigh = $rank;
	}

	//Misc functions
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>