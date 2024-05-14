<?php
include_once($SERVER_ROOT.'/classes/OccurrenceTaxaManager.php');

class ImageLibraryBrowser extends OccurrenceTaxaManager{

	private $tidFocus;
	private $searchTerm;

	function __construct() {
		parent::__construct();
		if(array_key_exists('TID_FOCUS', $GLOBALS) && preg_match('/^[\d,]+$/', $GLOBALS['TID_FOCUS'])){
			$this->tidFocus = $GLOBALS['TID_FOCUS'];
		}
	}

	function __destruct(){
		parent::__destruct();
	}

	//Image browser functions
	public function getFamilyList(){
		$returnArray = Array();
		$sql = 'SELECT DISTINCT ts.Family '.$this->getListSql().' AND (ts.Family Is Not Null) ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$returnArray[] = $r->Family;
		}
		$rs->free();
		sort($returnArray);
		return $returnArray;
	}

	public function getGenusList(){
		$retArr = array();
		$sql = 'SELECT DISTINCT t.UnitName1 '.$this->getListSql().' ';
		if($this->searchTerm) $sql .= 'AND (ts.Family = "'.$this->cleanInputStr($this->searchTerm).'") ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->UnitName1;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	public function getSpeciesList(){
		$retArr = Array();
		$tidArr = Array();
		$taxon = $this->cleanInputStr($this->searchTerm);
		$taxon = trim($taxon,' %');
		if($taxon){
			$this->setTaxonRequestVariable(array('taxa'=>$taxon,'usethes'=>1,'taxontype'=>2));
			foreach($this->taxaArr['taxa'] as $taxName => $taxArr){
				if(isset($taxArr['tid'])) $tidArr = array_merge($tidArr,array_keys($taxArr['tid']));
				if(isset($taxArr['synonyms'])) $tidArr = array_merge($tidArr,array_keys($taxArr['synonyms']));
			}
		}
		if(!$taxon) $taxon = 'A';
		$sql = 'SELECT DISTINCT t.tid, t.SciName '.$this->getListSql().' AND (i.sortsequence < 500) ';
		if(strtolower(substr($taxon,-5)) == 'aceae' || strtolower(substr($taxon,-4)) == 'idae') $sql .= 'AND ((ts.family = "'.$taxon.'") ';
		else{
			$sql .= 'AND ((t.SciName LIKE "'.$taxon.'%") ';
			if($tidArr && strpos($taxon,' ')) $sql .= 'OR (t.tid IN('.implode(',', $tidArr).')) OR (ts.parenttid IN('.implode(',', $tidArr).'))';
		}
		$sql .= ') ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid] = $r->SciName;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	private function getListSql(){
		$sql = 'FROM images i INNER JOIN taxstatus ts ON i.tid = ts.tid INNER JOIN taxa t ON ts.tidaccepted = t.tid ';
		if($this->tidFocus) $sql .= 'INNER JOIN taxaenumtree e ON i.tid = e.tid ';
		$sql .= 'WHERE (ts.taxauthid = 1) AND (t.RankId > 219) ';
		if($this->tidFocus) $sql .= 'AND (e.parenttid IN('.$this->tidFocus.')) AND (e.taxauthid = 1) ';
		return $sql;
	}

	//Image contributor listings
	public function getCollectionImageList(){
		$retArr = array();
		if($this->tidFocus){
			//Get collection names
			$stagingArr = array();
			$sql = 'SELECT collid, CONCAT(collectionname, " (", CONCAT_WS("-",institutioncode,collectioncode),")") as collname, colltype FROM omcollections ORDER BY collectionname';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$stagingArr[$r->collid]['name'] = $r->collname;
				$stagingArr[$r->collid]['type'] = (strpos($r->colltype,'Observations') !== false?'obs':'coll');
			}
			$rs->free();
			//Get image counts
			$sql = 'SELECT o.collid, COUNT(i.imgid) AS imgcnt FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid ';
			if($this->tidFocus) $sql .= 'INNER JOIN taxaenumtree e ON i.tid = e.tid WHERE (e.parenttid IN('.$this->tidFocus.')) AND (e.taxauthid = 1) ';
			$sql .= 'GROUP BY o.collid ';
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$stagingArr[$row->collid]['imgcnt'] = $row->imgcnt;
			}
			$result->free();
			//Only return collections with images
			foreach($stagingArr as $id => $collArr){
				if(array_key_exists('imgcnt', $collArr)){
					$retArr[$collArr['type']][$id]['imgcnt'] = number_format($collArr['imgcnt']);
					$retArr[$collArr['type']][$id]['name'] = $collArr['name'];
				}
			}
		}
		else{
			$sql = 'SELECT c.collid, CONCAT(c.collectionname, " (", CONCAT_WS("-",c.institutioncode,c.collectioncode),")") as collname, c.colltype, '.
				'SUBSTRING_INDEX(SUBSTRING_INDEX(s.dynamicProperties,\'"imgcnt":"\',-1),\'"\',1) as imgcnt '.
				'FROM omcollections c INNER JOIN omcollectionstats s ON c.collid = s.collid '.
				'WHERE s.dynamicProperties LIKE "%imgcnt%" '.
				'ORDER BY collectionname';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$imgCntArr = explode(':',$r->imgcnt);
				if($imgCntArr[0]){
					$collType = (strpos($r->colltype,'Observations') !== false?'obs':'coll');
					$retArr[$collType][$r->collid]['name'] = $r->collname;
					$retArr[$collType][$r->collid]['imgcnt'] = number_format($imgCntArr[0]);
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getPhotographerList(){
		$retArr = array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ", u.lastname, u.firstname) as pname, CONCAT_WS(", ", u.firstname, u.lastname) as fullname, u.email, Count(ti.imgid) AS imgcnt '.
			'FROM users u INNER JOIN images ti ON u.uid = ti.photographeruid ';
		if($this->tidFocus) $sql .= 'INNER JOIN taxaenumtree e ON ti.tid = e.tid WHERE (e.parenttid IN('.$this->tidFocus.')) AND (e.taxauthid = 1) ';
		$sql .= 'GROUP BY u.uid ORDER BY u.lastname, u.firstname';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$retArr[$row->uid]['name'] = $row->pname;
			$retArr[$row->uid]['fullname'] = $row->fullname;
			$retArr[$row->uid]['imgcnt'] = number_format($row->imgcnt);
		}
		$result->free();
		return $retArr;
	}

	//Setters and getters
	public function setSearchTerm($t){
		$this->searchTerm = htmlspecialchars($t, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
	}
}
?>