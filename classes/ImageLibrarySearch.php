<?php
include_once($SERVER_ROOT.'/classes/OccurrenceTaxaManager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceSearchSupport.php');

class ImageLibrarySearch extends OccurrenceTaxaManager{

	private $dbStr = '';
	private $taxonType = 2;
	private $taxaStr;
	private $useThes = 1;
	private $photographerUid;
	private $tagExistance = 0;
	private $tag;
	private $keywords;
	private $imageCount = 0;
	private $imageType = 0;
	private $mediaType = null;

	private $recordCount = 0;
	private $tidFocus;
	private $searchSupportManager = null;
	private $sqlWhere = '';
	private $errorStr = '';

	function __construct($type = 'readonly') {
		parent::__construct($type);
		if(array_key_exists('TID_FOCUS', $GLOBALS) && preg_match('/^[\d,]+$/', $GLOBALS['TID_FOCUS'])){
			$this->tidFocus = $GLOBALS['TID_FOCUS'];
		}
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getImageArr($pageRequest, $cntPerPage){
		$retArr = Array();
		$this->setSqlWhere();
		$this->setRecordCnt();
		$sql = 'SELECT m.mediaID, m.tid, IFNULL(t.sciname,o.sciname) as sciname, m.url, m.thumbnailurl, m.originalurl, m.creatorUid, m.caption, m.occid, m.mediaType ';
		$sqlWhere = $this->sqlWhere;
		if($this->imageCount == 1) $sqlWhere .= 'GROUP BY sciname ';
		elseif($this->imageCount == 2) $sqlWhere .= 'GROUP BY m.occid ';
		if($this->sqlWhere) $sqlWhere .= 'ORDER BY o.sciname ';
		$bottomLimit = ($pageRequest - 1)*$cntPerPage;
		$sql .= $this->getSqlBase().$sqlWhere.'LIMIT '.$bottomLimit.','.$cntPerPage;
		//echo '<div>Spec sql: '.$sql.'</div>';
		$occArr = array();
		$result = $this->conn->query($sql);
		$imgId = 0;
		while($r = $result->fetch_object()){
			if($imgId == $r->mediaID) continue;
			$imgId = $r->mediaID;
			$retArr[$imgId]['mediaID'] = $r->mediaID;
			//$retArr[$imgId]['tidaccepted'] = $r->tidinterpreted;
			$retArr[$imgId]['tid'] = $r->tid;
			$retArr[$imgId]['sciname'] = $r->sciname;
			$retArr[$imgId]['url'] = $r->url;
			$retArr[$imgId]['thumbnailurl'] = $r->thumbnailurl;
			$retArr[$imgId]['originalurl'] = $r->originalurl;
			$retArr[$imgId]['uid'] = $r->creatorUid;
			$retArr[$imgId]['caption'] = $r->caption;
			$retArr[$imgId]['occid'] = $r->occid;
			$retArr[$imgId]['mediaType'] = $r->mediaType;
			//$retArr[$imgId]['stateprovince'] = $r->stateprovince;
			//$retArr[$imgId]['catalognumber'] = $r->catalognumber;
			//$retArr[$imgId]['instcode'] = $r->instcode;
			if($r->occid) $occArr[$r->occid] = $r->occid;
		}
		$result->free();
		if($occArr){
			//Get occurrence data
			$collArr = array();
			$sql2 = 'SELECT occid, catalognumber, sciname, recordedby, stateprovince, collid FROM omoccurrences WHERE occid IN('.implode(',',$occArr).')';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$retArr['occ'][$r2->occid]['catnum'] = $r2->catalognumber;
				$retArr['occ'][$r2->occid]['sciname'] = $r2->sciname;
				$retArr['occ'][$r2->occid]['recordedby'] = $r2->recordedby;
				$retArr['occ'][$r2->occid]['stateprovince'] = $r2->stateprovince;
				$retArr['occ'][$r2->occid]['collid'] = $r2->collid;
				$collArr[$r2->collid] = $r2->collid;
			}
			$rs2->free();
			//Get collection data
			$sql3 = 'SELECT collid, CONCAT_WS("-",institutioncode, collectioncode) as instcode FROM omcollections WHERE collid IN('.implode(',',$collArr).')';
			$rs3 = $this->conn->query($sql3);
			while($r3 = $rs3->fetch_object()){
				$retArr['coll'][$r3->collid] = $r3->instcode;
			}
			$rs3->free();
		}
		return $retArr;
	}

	private function setSqlWhere(){
		$sqlWhere = '';
		if($this->dbStr){
			$sqlWhere .= OccurrenceSearchSupport::getDbWhereFrag($this->cleanInStr($this->dbStr));
		}
		if(isset($this->taxaArr['taxa'])){
			$sqlWhereTaxa = '';
			foreach($this->taxaArr['taxa'] as $searchTaxon => $searchArr){
				$taxonType = $this->taxaArr['taxontype'];
				if(isset($searchArr['taxontype'])) $taxonType = $searchArr['taxontype'];
				if($taxonType == TaxaSearchType::TAXONOMIC_GROUP){
					//Class, order, or other higher rank
					if(isset($searchArr['tid'])){
						$tidArr = array_keys($searchArr['tid']);
						//$sqlWhereTaxa .= 'OR (o.tidinterpreted IN(SELECT DISTINCT tid FROM taxaenumtree WHERE (taxauthid = '.$this->taxAuthId.') AND (parenttid IN('.trim($tidStr,',').') OR (tid = '.trim($tidStr,',').')))) ';
						$sqlWhereTaxa .= 'OR ((e.taxauthid = '.$this->taxAuthId.') AND ((m.tid IN('.implode(',', $tidArr).')) OR e.parenttid IN('.implode(',', $tidArr).'))) ';
					}
				}
				elseif($taxonType == TaxaSearchType::FAMILY_ONLY){
					$sqlWhereTaxa .= 'OR ((ts.family = "'.$searchTaxon.'") AND (ts.taxauthid = '.$this->taxAuthId.')) ';
				}
				else{
					if($taxonType == TaxaSearchType::COMMON_NAME){
						//Common name search
						$famArr = array();
						if(array_key_exists("families",$searchArr)){
							$famArr = $searchArr["families"];
						}
						if(array_key_exists("tid",$searchArr)){
							$tidArr = array_keys($searchArr['tid']);
							$sql = 'SELECT DISTINCT t.sciname '.
								'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
								'WHERE (t.rankid = 140) AND (e.taxauthid = '.$this->taxAuthId.') AND (e.parenttid IN('.implode(',',$tidArr).'))';
							$rs = $this->conn->query($sql);
							while($r = $rs->fetch_object()){
								$famArr[] = $r->sciname;
							}
						}
						if($famArr){
							$famArr = array_unique($famArr);
							$sqlWhereTaxa .= 'OR (ts.family IN("'.implode('","',$famArr).'")) ';
						}
						/*
						if(array_key_exists("scinames",$searchArr)){
							foreach($searchArr["scinames"] as $sciName){
								$sqlWhereTaxa .= "OR (o.sciname Like '".$sciName."%') ";
							}
						}
						*/
					}
					else{
						if(array_key_exists("tid",$searchArr)){
							$rankid = current($searchArr['tid']);
							$tidArr = array_keys($searchArr['tid']);
							$sqlWhereTaxa .= "OR (m.tid IN(".implode(',',$tidArr).")) ";
							if($rankid < 220) $sqlWhereTaxa .= 'OR ((e.taxauthid = '.$this->taxAuthId.') AND (e.parenttid IN('.implode(',', $tidArr).')) AND (ts.taxauthid = '.$this->taxAuthId.' AND ts.tid = ts.tidaccepted)) ';
							elseif($rankid == 220) $sqlWhereTaxa .= 'OR (ts.parenttid IN('.implode(',', $tidArr).') AND ts.taxauthid = '.$this->taxAuthId.' AND ts.tid = ts.tidaccepted) ';
						}
						else{
							//Return matches for "Pinus a"
							$sqlWhereTaxa .= "OR (t.sciname LIKE '".$this->cleanInStr($searchTaxon)."%') ";
						}
					}
					if(array_key_exists("synonyms",$searchArr)){
						$synArr = $searchArr["synonyms"];
						if($synArr){
							$sqlWhereTaxa .= 'OR (m.tid IN('.implode(',',array_keys($synArr)).')) ';
						}
					}
				}
			}
			if($sqlWhereTaxa) $sqlWhere .= "AND (".substr($sqlWhereTaxa,3).") ";
		}
		elseif($this->tidFocus){
			$sqlWhere .= 'AND (e.parenttid IN('.$this->tidFocus.')) AND (e.taxauthid = 1) ';
		}
		if($this->photographerUid){
			$sqlWhere .= 'AND (m.creatoruid IN('.$this->photographerUid.')) ';
		}
		if($this->tag){
			$sqlWhere .= 'AND m.mediaID ';
			$tagFrag = '';
			if($this->tag != 'ANYTAG') $tagFrag = 'WHERE keyvalue = "'.$this->cleanInStr($this->tag).'"';
			if(!$this->tagExistance){
				$sqlWhere .= 'NOT ';
			}
			$sqlWhere .= 'IN(SELECT mediaid FROM imagetag '.$tagFrag.')';
		}
		if($this->keywords){
			$keywordArr = explode(";",$this->keywords);
			$tempArr = Array();
			foreach($keywordArr as $value){
				$tempArr[] = "(ik.keyword LIKE '%".$this->cleanInStr($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
		}
		if($this->imageType){
			if($this->imageType == 1){
				//Specimen or Vouchered Observations Images
				$sqlWhere .= 'AND (m.occid IS NOT NULL) ';
			}
			elseif($this->imageType == 3){
				//Field Images (lacking specific locality details)
				$sqlWhere .= 'AND (m.occid IS NULL) ';
			}
		}
		if($this->mediaType){
			//Note mediaType is cleaned to only be 'image' and 'audio' strings
			$sqlWhere .= 'AND (m.mediaType = "' . $this->mediaType . '") ';
		}
		if(strpos($sqlWhere,'ts.taxauthid')) $sqlWhere = str_replace('m.tid', 'ts.tid', $sqlWhere);
		if($sqlWhere) $this->sqlWhere = 'WHERE '.substr($sqlWhere,4);
	}

	private function setRecordCnt(){
		$sql = 'SELECT COUNT(DISTINCT m.mediaID) AS cnt ';
		if($this->imageCount){
			if($this->imageCount == 1) $sql = 'SELECT COUNT(DISTINCT m.tid) AS cnt ';
			elseif($this->imageCount == 2) $sql = 'SELECT COUNT(DISTINCT m.occid) AS cnt ';
		}
		$sql .= $this->getSqlBase().$this->sqlWhere;
		$result = $this->conn->query($sql);
		if($row = $result->fetch_object()){
			$this->recordCount = $row->cnt;
		}
		$result->free();
	}

	private function getSqlBase(){
		$sql = 'FROM media m ';
		if($this->taxaArr){
			$sql .= 'INNER JOIN taxa t ON m.tid = t.tid ';
		}
		else{
			$sql .= 'LEFT JOIN taxa t ON m.tid = t.tid ';
		}
		if(strpos($this->sqlWhere,'ts.taxauthid')){
			$sql .= 'INNER JOIN taxstatus ts ON m.tid = ts.tid ';
		}
		if(strpos($this->sqlWhere,'e.taxauthid') || $this->tidFocus){
			$sql .= 'INNER JOIN taxaenumtree e ON m.tid = e.tid ';
		}
		if($this->keywords){
			$sql .= 'INNER JOIN imagekeywords ik ON m.mediaID = ik.mediaid ';
		}
		if($this->dbStr && $this->dbStr != 'all'){
			$sql .= 'INNER JOIN omoccurrences o ON m.occid = o.occid ';
		}
		else{
			$sql .= 'LEFT JOIN omoccurrences o ON m.occid = o.occid ';
		}
		return $sql;
	}

	public function getFullCollectionList($catId = ''){
		if(!$this->searchSupportManager) $this->searchSupportManager = new OccurrenceSearchSupport($this->conn);
		if($this->dbStr) $this->searchSupportManager->setCollidStr($this->dbStr);
		return $this->searchSupportManager->getFullCollectionList($catId, true);
	}

	public function outputFullCollArr($occArr, $targetCatID = 0){
		if(!$this->searchSupportManager) $this->searchSupportManager = new OccurrenceSearchSupport($this->conn);
		$this->searchSupportManager->outputFullCollArr($occArr, $targetCatID, false, false);
	}

	//Misc support functions
	public function getQueryTermStr(){
		$retStr = '';
		if($this->dbStr) $retStr .= '&db='.$this->dbStr;
		if($this->taxonType) $retStr .= '&taxontype='.$this->taxonType;
		if($this->taxaStr) $retStr .= '&taxa='.$this->taxaStr;
		if($this->useThes) $retStr .= '&usethes=1';
		if($this->photographerUid) $retStr .= '&phuid='.$this->photographerUid;
		$retStr .= '&tagExistance='.$this->tagExistance;
		if($this->tag) $retStr .= '&tag='.urlencode($this->tag);
		if($this->keywords) $retStr .= '&keywords='.$this->keywords;
		if($this->imageCount) $retStr .= '&imagecount='.$this->imageCount;
		if($this->imageType) $retStr .= '&imagetype='.$this->imageType;
		return trim($retStr,' &');
	}

	//Action editing functions
	public function batchAssignImageTag($postArr){
		$status = false;
		$imageArr = $postArr['mediaid'];
		$tagName = $postArr['imgTagAction'];
		if($imageArr && $tagName){
			$cnt = 0;
			$fail = 0;
			foreach($imageArr as $mediaID){
				if(is_numeric($mediaID)){
					$sql = 'INSERT IGNORE INTO imagetag(mediaid, keyValue) VALUE(?, ?)';
					if($stmt = $this->conn->prepare($sql)){
						$stmt->bind_param('is', $mediaID, $tagName);
						$stmt->execute();
						if($stmt->affected_rows) $cnt++;
						elseif($stmt->error){
							$this->errorStr = 'ERROR adding image tag: '.$this->error;
							$status = false;
						}
						else $fail++;
						$stmt->close();
					}
				}
			}
			$status = $cnt . '-' . $fail;
		}
		return $status;
	}

	//Listing functions
	public function getPhotographerUidArr(){
		$retArr = array();
		$sql1 = 'SELECT DISTINCT creatorUid FROM media WHERE creatorUid IS NOT NULL';
		$rs1 = $this->conn->query($sql1);
		while ($r1 = $rs1->fetch_object()) {
			$retArr[$r1->creatorUid] = '';
		}
		$rs1->free();
		if($retArr){
			$sql2 = 'SELECT uid, CONCAT_WS(", ", lastname, firstname) AS fullname FROM users WHERE uid IN(' . implode(',', array_keys($retArr)) . ')';
			$rs2 = $this->conn->query($sql2);
			while ($r2 = $rs2->fetch_object()) {
				$retArr[$r2->uid] = $r2->fullname;
			}
			$rs2->free();
		}
		asort($retArr, SORT_NATURAL | SORT_FLAG_CASE);
		return $retArr;
	}

	public function getTagArr(){
		$retArr = array();
		$sql = 'SELECT tagkey, CONCAT_WS(" - ",shortlabel,tagDescription) as displayText FROM imagetagkey ORDER BY tagkey';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->tagkey] = $r->displayText;
			}
		}
		$rs->free();
		return $retArr;
	}

	public function getKeywordSuggest($queryStr){
		global $CHARSET;
		$retArr = array();
		$sql = 'SELECT DISTINCT keyword FROM imagekeywords WHERE keyword LIKE "'.$this->cleanInStr($queryStr).'%" LIMIT 10 ';
		$rs = $this->conn->query($sql);
		$i = 0;
		while ($r = $rs->fetch_object()) {
			$retArr[$i]['name'] = html($r->keyword, ENT_COMPAT, $CHARSET);
			$i++;
		}
		$rs->free();
		return $retArr;
	}

	private function resetTaxaStr(){
		$sql = 'SELECT sciname FROM taxa WHERE (tid = '.$this->taxaStr.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()) {
			$this->taxaStr = $r->sciname;
		}
		$rs->free();
	}

	//Setters and getters
	public function getDbStr(){
		return $this->dbStr;
	}

	public function setCollectionVariables($reqArr){
		$this->dbStr = trim(OccurrenceSearchSupport::getDbRequestVariable(), ',; ');
	}

	public function setTaxonType($t){
		if(is_numeric($t)) $this->taxonType = $t;
	}

	public function getTaxonType(){
		return $this->taxonType;
	}

	public function setTaxaStr($str){
		if(strpos($str,'<') === false){
			$this->taxaStr = trim($str);
			if($this->taxaStr){
				if(is_numeric($this->taxaStr)) $this->resetTaxaStr();
				$this->setTaxonRequestVariable(array('taxa'=>$this->taxaStr,'taxontype'=>$this->taxonType,'usethes'=>$this->useThes));
			}
		}
	}

	public function getTaxaStr(){
		return $this->taxaStr;
	}

	public function setUseThes($u){
		if(is_numeric($u)) $this->useThes = $u;
	}

	public function getUseThes(){
		return $this->useThes;
	}

	public function setPhotographerUid($uid){
		if(is_numeric($uid)) $this->photographerUid = $uid;
	}

	public function getPhotographerUid(){
		return $this->photographerUid;
	}

	public function setTagExistance($t){
		$this->tagExistance = $t;
	}

	public function setTag($t){
		$this->tag = $t;
	}

	public function getTag(){
		return $this->tag;
	}

	public function setKeywords($k){
		$this->keywords = $k;
	}

	public function getKeywords(){
		return $this->keywords;
	}

	public function setImageCount($c){
		if(is_numeric($c)) $this->imageCount = $c;
	}

	public function getImageCount(){
		return $this->imageCount;
	}

	public function setImageType($t){
		if(is_numeric($t)) $this->imageType = $t;
	}

	public function getImageType(){
		return $this->imageType;
	}

	public function getRecordCnt(){
		return $this->recordCount;
	}

	public function getErrorStr(){
		return $this->errorStr;
	}

	public function setMediaType($type) {
		if($type === 'image' || $type === 'audio') {
			$this->mediaType = $type;
		}
	}

	public function getMediaType() {
		return $this->mediaType;
	}
}
?>
