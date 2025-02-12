<?php
include_once($SERVER_ROOT.'/classes/RpcBase.php');

class RpcOccurrenceEditor extends RpcBase{

	function __construct($connType = 'readonly'){
		parent::__construct($connType);
	}

	function __destruct(){
		parent::__destruct();
	}

	public function deleteIdentifier($identifierID, $occid){
		$bool = false;
		if(is_numeric($identifierID)){
			$origOcnStr = '';
			$sql = 'SELECT CONCAT_WS(": ",identifierName,identifierValue) as identifier FROM omoccuridentifiers WHERE (idomoccuridentifiers = '.$identifierID.') ORDER BY sortBy ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$origOcnStr = $r->identifier;
			}
			$rs->free();
			$sql = 'DELETE FROM omoccuridentifiers WHERE idomoccuridentifiers = '.$identifierID;
			if($this->conn->query($sql)){
				$bool = true;
				if($origOcnStr){
					$sql = 'INSERT INTO omoccuredits(occid, fieldName, fieldValueNew, fieldValueOld, appliedStatus, uid)
						VALUES('.$occid.',"omoccuridentifiers","","'.$this->cleanInStr($origOcnStr).'",1,'.$GLOBALS['SYMB_UID'].')';
					$this->conn->query($sql);
				}
			}
			else $this->errorMessage = 'ERROR deleting occurrence identifier: '.$this->conn->error;
		}
		elseif(is_numeric($occid)){
			if(strpos($identifierID,'ocnid-') === 0){
				$ocnIndex = substr($identifierID,6);
				$origOcnStr = '';
				$sql = 'SELECT otherCatalogNumbers FROM omoccurrences WHERE occid = '.$occid;
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()) $origOcnStr = $r->otherCatalogNumbers;
				$rs->free();
				$ocnStr = trim($origOcnStr,',;| ');
				$otherCatNumArr = array();
				if($ocnStr){
					$ocnStr = str_replace(array(',',';'),'|',$ocnStr);
					$ocnArr = explode('|',$ocnStr);
					$cnt = 0;
					foreach($ocnArr as $identUnit){
						if($ocnIndex == $cnt) continue;
						$unitArr = explode(':',trim($identUnit,': '));
						$tag = '';
						if(count($unitArr) > 1) $tag = trim(array_shift($unitArr));
						$value = trim(implode(', ',$unitArr));
						$otherCatNumArr[$value] = $tag;
						$cnt++;
					}
				}
				$newOcnStr = '';
				foreach($otherCatNumArr as $v => $t){
					$newOcnStr .= ($t?$t.': ':'').$v.'; ';
				}
				$newOcnStr = trim($newOcnStr,'; ');
				if($newOcnStr != $origOcnStr){
					$sql = 'UPDATE omoccurrences SET otherCatalogNumbers = '.($newOcnStr?'"'.$this->cleanInStr($newOcnStr).'"':'NULL').' WHERE occid = '.$occid;
					if($this->conn->query($sql)){
						$bool = true;
						$sql = 'INSERT INTO omoccuredits(occid, fieldName, fieldValueNew, fieldValueOld, appliedStatus, uid)
							VALUES('.$occid.',"omoccuridentifiers","'.$this->cleanInStr($newOcnStr).'","'.$this->cleanInStr($origOcnStr).'",1,'.$GLOBALS['SYMB_UID'].')';
						$this->conn->query($sql);
					}
					else echo 'ERROR deleting occurrence identifier: '.$this->conn->error;
				}
			}
		}
		return $bool;
	}

	public function getDupesCatalogNumber($catNum, $collid, $skipOccid){
		$retArr = array();
		$catNumber = $this->cleanInStr($catNum);
		if(is_numeric($collid) && is_numeric($skipOccid) && $catNumber){
			$sql = 'SELECT occid FROM omoccurrences WHERE (catalognumber = ?) AND (collid = ?) AND (occid != ?) ';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('sii', $catNum, $collid, $skipOccid);
				$stmt->execute();
				$occid = 0;
				$stmt->bind_result($occid);
				while($stmt->fetch()){
					$retArr[$occid] = $occid;
				}
				$stmt->close();
			}
		}
		return $retArr;
	}

	public function getDupesOtherCatalogNumbers($otherCatNum, $collid, $skipOccid){
		$retArr = array();
		if(is_numeric($collid) && is_numeric($skipOccid) && $otherCatNum){
			$sql = 'SELECT o.occid FROM omoccurrences o INNER JOIN omoccuridentifiers i ON o.occid = i.occid
				WHERE (i.identifierValue = ?) AND (o.collid = ?)
				UNION
				SELECT occid FROM omoccurrences
				WHERE (othercatalognumbers = ?) AND (collid = ?) ';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('sisi', $otherCatNum, $collid, $otherCatNum, $collid);
				$stmt->execute();
				$occid = 0;
				$stmt->bind_result($occid);
				while($stmt->fetch()){
					if($occid != $skipOccid) $retArr[$occid] = $occid;
				}
				$stmt->close();
			}
		}
		return $retArr;
	}

	public function getOccurrenceVouchers($occid){
		$retArr = array();
		if(is_numeric($occid)){
			$sql = 'SELECT c.clid, c.name FROM fmvouchers v INNER JOIN fmchklsttaxalink cl ON v.clTaxaID = cl.clTaxaID INNER JOIN fmchecklists c ON cl.clid = c.clid WHERE v.occid = ?';
			if($stmt = $this->conn->prepare($sql)) {
				if($stmt->bind_param('i', $occid)){
					$stmt->execute();
					$clid = '';
					$name = '';
					$stmt->bind_result($clid, $name);
					while($stmt->fetch()){
						$retArr[$clid] = $name;
					}
					$stmt->close();
				}
				else $this->errorMessage = 'ERROR binding params for getOccurrenceVouchers: '.$stmt->error;
			}
			else $this->errorMessage = 'ERROR preparing statement for getOccurrenceVouchers: '.$this->conn->error;
		}
		return $retArr;
	}

	public function getImageCount($occid){
		$retCnt = 0;
		if(is_numeric($occid)){
			$sql = 'SELECT count(*) AS imgcnt FROM images WHERE occid = ?';
			if($stmt = $this->conn->prepare($sql)){
				if($stmt->bind_param('i', $occid)){
					$stmt->execute();
					$stmt->bind_result($retCnt);
					$stmt->fetch();
					$stmt->close();
				}
			}
		}
		return $retCnt;
	}

	//Used by /collections/editor/rpc/exsiccativalidation.php
	public function getExsiccatiID($queryTerm){
		$ometid = '';
		if($queryTerm){
			$sql = 'SELECT ometid FROM omexsiccatititles WHERE CONCAT_WS("",title,CONCAT(" [",abbreviation,"]")) = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('s', $queryTerm);
				$stmt->execute();
				$stmt->bind_result($ometid);
				$stmt->fetch();
				$stmt->close();
			}
		}
		return $ometid;
	}

	//Used by /collections/editor/rpc/getspeciessuggest.php,
	public function getSpeciesSuggest($term){
		$retArr = Array();
		$term = preg_replace('/[^a-zA-Z()\-. ]+/', '', $term);
		$term = preg_replace('/\s{1}x{1}\s{0,1}$/i', ' _ ', $term);
		$term = preg_replace('/\s{1}x{1}\s{1}/i', ' _ ', $term);

		// Enable scientific name entry shortcuts: 2-3 letter codes separated by spaces, e.g. "pse men"
		// Split the search string by spaces if there are any.
		$str1 = ''; $str2 = ''; $str3 = '';
		$strArr = explode(' ',$term);
		$strCnt = count($strArr);
		$str1 = $strArr[0];
		if($strCnt > 1){
			$str2 = $strArr[1];
		}
		if($strCnt > 2){
			$str3 = $strArr[2];
		}

		// Construct the SQL query
		$sql = 'SELECT DISTINCT tid, sciname FROM taxa WHERE unitname1 LIKE "'.$str1.'%" ';
		if($str2){
			$sql .= 'AND unitname2 LIKE "'.$str2.'%" ';
		}
		if($str3){
			$sql .= 'AND unitname3 LIKE "'.$str3.'%" ';
		}
		$sql .= 'ORDER BY sciname';

		// If the search term has an infraspecific separator, use the old version of the SQL, otherwise, no matches will be returned
		if(array_intersect($strArr, array("var.", "ssp.", "nothossp.", "f.", "×", "x", "†"))) $sql = 'SELECT DISTINCT tid, sciname FROM taxa WHERE sciname LIKE "'.$term.'%" ';

		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()){
			$retArr[] = array('id' => $r->tid, 'value' => $r->sciname);
		}
		$rs->free();
		return $retArr;
	}

	public function getTaxonArr($term){
		$retArr = array();
		if($term){
			$sql = 'SELECT DISTINCT t.tid, t.author, ts.family, t.securitystatus FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE t.sciname = ? AND ts.taxauthid = 1 ';
			if($stmt = $this->conn->prepare($sql)){
				if($stmt->bind_param('s', $term)){
					$stmt->execute();
					$tid = 0;
					$family = null;
					$author = null;
					$status = null;
					$stmt->bind_result($tid, $author, $family, $status);
					while($stmt->fetch()){
						$retArr['tid'] = $tid;
						$retArr['family'] = $family;
						$retArr['author'] = $author;
						$retArr['status'] = $status;
					}
					$stmt->close();
				}
			}
		}
		return $retArr;
	}

	//Used by /collections/editor/rpc/localitysecuritycheck.php
	public function getStateSecuritySetting($tid, $state){
		$retStr = 0;
		if(is_numeric($tid) && $state){
			$sql = 'SELECT c.clid
				FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid
				INNER JOIN taxstatus ts1 ON cl.tid = ts1.tid
				INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted
				WHERE c.type = "rarespp" AND ts1.taxauthid = 1 AND ts2.taxauthid = 1
				AND (ts2.tid = ?) AND (c.locality = ?)';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('is', $tid, $state);
				$stmt->execute();
				$stmt->store_result();
				if($stmt->num_rows) $retStr = 1;
				$stmt->close();
			}
		}
		return $retStr;
	}

	//Used by Geographic Thesaurus calls
	public function getGeography($term, $target, $parentTerm){
		$retArr = Array();
		$sql = 'SELECT geoterm FROM geographicthesaurus WHERE geoterm LIKE "'.$this->cleanInStr($term).'%" AND geolevel = 50 ';
		if($target == 'state'){
			$sql = 'SELECT DISTINCT s.geoterm FROM geographicthesaurus s ';
			$sqlWhere = 'WHERE s.geolevel = 60 AND s.geoterm LIKE "'.$this->cleanInStr($term).'%" ';
			if($parentTerm){
				$sql .= 'INNER JOIN geographicthesaurus c ON s.parentID = c.geoThesID ';
				$sqlWhere .= 'AND c.geolevel = 50 AND c.geoterm = "'.$this->cleanInStr($parentTerm).'" ';
			}
			$sql .= $sqlWhere;
		}
		elseif($target == 'county'){
			$sql = 'SELECT DISTINCT c.geoterm FROM geographicthesaurus c ';
			$sqlWhere = 'WHERE c.geolevel = 70 AND c.geoterm LIKE "'.$this->cleanInStr($term).'%" ';
			if($parentTerm){
				$sql .= 'INNER JOIN geographicthesaurus s ON c.parentID = s.geoThesID ';
				$sqlWhere .= 'AND s.geolevel = 60 AND s.geoterm = "'.$this->cleanInStr($parentTerm).'" ';
			}
			$sql .= $sqlWhere;
		}
		elseif($target == 'municipality'){
			$sql = 'SELECT DISTINCT m.geoterm FROM geographicthesaurus m ';
			$sqlWhere = 'WHERE m.geolevel = 80 AND m.geoterm LIKE "'.$this->cleanInStr($term).'%" ';
			if($parentTerm){
				$sql .= 'INNER JOIN geographicthesaurus s ON m.parentID = s.geoThesID ';
				$sqlWhere .= 'AND s.geolevel = 70 AND s.geoterm = "'.$this->cleanInStr($parentTerm).'" ';
			}
			$sql .= $sqlWhere;
		}
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			$retArr[] = $r->geoterm;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	//Used by /collections/editor/rpc/getPaleoGtsParents.php
	public function getPaleoGtsParents($term){
		$retArr = Array();
		$sql = 'SELECT gtsid, gtsterm, rankid, rankname, parentgtsid FROM omoccurpaleogts WHERE rankid > 10 AND gtsterm = "'.$this->cleanInStr($term).'"';
		$parentId = '';
		do{
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				if($parentId == $r->parentgtsid){
					$parentId = 0;
				}
				else{
					$retArr[] = array("rankid" => $r->rankid, "value" => $r->gtsterm);
					$parentId = $r->parentgtsid;
				}
			}
			else $parentId = 0;
			$rs->free();
			$sql = 'SELECT gtsid, gtsterm, rankid, rankname, parentgtsid FROM omoccurpaleogts WHERE rankid > 10 AND gtsid = '.$parentId;
		}while($parentId);
		return $retArr;
	}

	//Setters and getters
	public function isValidApiCall(){
		//Verification also happening within haddler checking is user is logged in and a valid admin/editor
		$status = parent::isValidApiCall();
		if(!$status) return false;
		return true;
	}
}
?>