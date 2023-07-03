<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class TaxonomyCleanerOccurrences extends TaxonomyCleaner{

	private $collId;

	public function __construct(){
 		parent::__construct();
	}

	function __destruct(){
 		parent::__destruct();
	}

	public function verifyCollectionTaxa($collId){
		//Grab list of taxa, check each one, add valid taxa to taxonomic thesaurus, return number added and number problematic remaining
		$numGood = 0;
		$numBad = 0;
		$sql = 'SELECT DISTINCT o.sciname FROM omoccurrences o '.
			'WHERE o.tidinterpreted IS NULL AND o.sciname IS NOT NULL ';
		if($collId && is_numeric($collId)) $sql .= 'AND (o.collid = '.$collId.') ';
		$sql .= 'ORDER BY o.sciname LIMIT 1';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->sciname){
				$externalTaxonObj = $this->getTaxonObjSpecies2000($r->sciname);
				if($externalTaxonObj){
					//Name is good but not in thesuarus, thus add
					$numGood++;
					//External is accepted
						//Default to internal system's taxonomy
							//1. Go through external synonyms
							//2. If one is found and taxonomically tested, add new name linked to the accepted name of that taxon
							//3. Add and link rest of the Synonyms to this name
						//Default to external system's taxonomy
							//1. Add name as accepted
							//2. Go through synonyms and add linked to new name
							//3. If synonym already exists, link to accetped name
					//External is not accepted
						//1. Grab and test external accepted name
						//Default to internal system's taxonomy
							//2a. Accepted name does not exist: Go through synonyms and test,
								//3a. If one exists, map all to this accepted taxon
								//3b. If not, add accepted name and link all to it (including synonyms)
							//2b. Accepted name exists: Link all to it (including synonyms)
						//Default to external system's taxonomy
							//4a. External accepted does not exist: add name and link all to it (including synonyms that don't exist)
							//4b. External accepted does exists...

				}
				else{
					//Name is not good, mark as so
					$numBad++;
					$sql = 'UPDATE omoccurrences SET taxonstatus = 1 WHERE (sciname = "'.$r->sciname.'") AND tidinterpreted IS NULL ';
					$this->conn->query($sql);
				}
			}
		}
		$rs->close();
		$retArr['good'] = $numGood;
		$retArr['bad'] = $numBad;
		return $retArr;
	}

	private function getHierarchy($tid){
		$parentArr = Array($tid);
		$parCnt = 0;
		$targetTid = $tid;
		do{
			$sqlParents = "SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts WHERE (ts.tid = ".$targetTid.')';
			//echo "<div>".$sqlParents."</div>";
			$resultParent = $this->conn->query($sqlParents);
			if($rowParent = $resultParent->fetch_object()){
				$parentTid = $rowParent->parenttid;
				if($parentTid) {
					$parentArr[$parentTid] = $parentTid;
				}
			}
			else{
				break;
			}
			$resultParent->close();
			$parCnt++;
			if($targetTid == $parentTid) break;
			$targetTid = $parentTid;
		}while($targetTid && $parCnt < 16);

		return implode(",",array_reverse($parentArr));
	}

	public function getCollectionName(){
		$retStr;
		$sql = 'SELECT institutioncode, collectioncode, collectionname '.
			'FROM omcollections WHERE (collid = '.$this->collId.') ';
		if($rs = $this->conn->query($sql)){
			if($row = $rs->fetch_object()){
				$retStr = $row->collectionname;
				if($row->institutioncode) $retStr .= ' ('.$row->institutioncode.($row->collectioncode?':'.$row->collectioncode:'').')';
			}
			$rs->close();
		}
		return $retStr;
	}

	public function getTaxaList($index = 0){
		$retArr = array();
		$sql = 'SELECT sciname '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collId.') AND tidinterpreted IS NULL '.
			'ORDER BY sciname '.
			'LIMIT '.$index.',500 ';
		if($rs = $this->conn->query($sql)){
			if($row = $rs->fetch_object()){
				$retArr[] = $row->sciname;
			}
			$rs->close();
		}
		return $retArr;
	}

	public function analyzeTaxa($startIndex = 0, $limit = 10){
		$retArr = array();
		$sql = 'SELECT sciname '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collId.') AND tidinterpreted IS NULL '.
			'ORDER BY sciname '.
			'LIMIT '.$index.','.$limit;
		if($rs = $this->conn->query($sql)){
			if($row = $rs->fetch_object()){
				$sn = $row->sciname;
				$sxArr[$sn] = $sn;
				//Check name through Catalog of Life

				//Check for near match using SoundEx
				$sxArr = $this->getSoundexMatch($sn);
				if($sxArr) $retArr[$sn]['soundex'] = $sxArr;

			}
			$rs->close();
		}

		return $retArr;
	}

	public function getTaxaCount(){
		$retStr = '';
		$sql = 'SELECT count(DISTINCT sciname) AS taxacnt '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collId.') AND tidinterpreted IS NULL ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			if($row = $rs->fetch_object()){
				$retStr = $row->taxacnt;
			}
			$rs->close();
		}
		return $retStr;
	}

	public function setCollId($id){
		if(is_numeric($id)){
			$this->collId = $id;
		}
	}
}
?>