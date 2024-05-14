<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class OccurrenceAttributes extends Manager {

	private $collidStr = 0;
	protected $traitArr = array();
	private $stateCodedArr = array();
	private $reviewSqlBase;
	private $occid = 0;
	private $sqlBody = '';
	private $filterArr = array();

	public function __construct($type = 'write'){
		parent::__construct(null, $type);
	}

	public function __destruct(){
		parent::__destruct();
	}

	//Edit functions
	public function addAttributes($postArr,$uid){
		if(!is_numeric($uid)){
			$this->errorMessage = 'ERROR saving occurrence attribute: bad input values; ';
			return false;
		}
		$status = true;
		$stateArr = array();
		foreach($postArr as $postKey => $postValue){
			if(substr($postKey,0,8) == 'traitid-'){
				if(is_array($postValue)){
					$stateArr = array_merge($stateArr,$postValue);
				}
				else{
					$stateArr[] = $postValue;
				}
			}
		}
		if($stateArr){
			//Insert attributes
			$sourceStr = 'viewingSpecimenImage';
			if(isset($postArr['source']) && $postArr['source']) $sourceStr = $postArr['source'];
			foreach($stateArr as $stateId){
				$xValue = 'NULL';
				if(strpos($stateId,'-')){
					$tempArr = explode('-', $stateId);
					$stateId = $tempArr[0];
					$xValue = $tempArr[1];
				}
				if(is_numeric($stateId)){
					$sql = 'INSERT INTO tmattributes(stateid,xvalue,occid,source,notes,createduid) '.
						'VALUES('.$stateId.','.$this->cleanInStr($xValue).','.$this->occid.','.($sourceStr?'"'.$this->cleanInStr($sourceStr).'"':'NULL').','.
						($postArr['notes']?'"'.$this->cleanInStr($postArr['notes']).'"':'NULL').','.$uid.') ';
					if(!$this->conn->query($sql)){
						$this->errorMessage .= 'ERROR saving occurrence attribute: '.$this->conn->error.'; ';
						$status = false;
					}
				}
				else{
					$this->errorMessage .= 'ERROR saving occurrence attribute: bad input values ('.$stateId.'); ';
					$status = false;
				}
			}
		}
		return $status;
	}

	public function editAttributes($postArr){
		$status = false;
		$stateArr = array();
		foreach($postArr as $postKey => $postValue){
			if(substr($postKey,0,8) == 'traitid-'){
				if(is_array($postValue)){
					$stateArr = array_merge($stateArr,$postValue);
				}
				else{
					$stateArr[] = $postValue;
				}
			}
		}
		$stateCleanArr = array();
		foreach($stateArr as $state){
			$xValue = 'NULL';
			if(strpos($state,'-')){
				$tempArr = explode('-', $state);
				$state = $tempArr[0];
				$xValue = $tempArr[1];
			}
			$stateCleanArr[$state] = $xValue;
		}
		//Edit states
		$setStatus = $postArr['setstatus'];
		$traitID = $postArr['traitid'];
		if(is_numeric($traitID) && is_numeric($setStatus)){
			$this->setTraitArr($traitID);
			//$this->setTraitStates();
			$attrArr = $this->setCodedAttribute();
			$addArr = array_diff_key($stateCleanArr,$attrArr);
			$delArr = array_diff_key($attrArr,$stateCleanArr);
			if($addArr){
				foreach($addArr as $stateIdAdd => $addValue){
					if(is_numeric($stateIdAdd)){
						$sql = 'INSERT INTO tmattributes(stateid,xvalue,occid,createduid) VALUES('.$stateIdAdd.','.$this->cleanInStr($addValue).','.$this->occid.','.$GLOBALS['SYMB_UID'].') ';
						//echo $sql.'<br/>';
						if($this->conn->query($sql)){
							$status = true;
						}
						else{
							$this->errorMessage = 'ERROR adding occurrence attribute: '.$this->conn->error;
							$status = false;
						}
					}
				}
			}
			if($delArr){
				foreach($delArr as $stateIdDel => $delValue){
					if(is_numeric($stateIdDel)){
						$sql = 'DELETE FROM tmattributes WHERE stateid = '.$stateIdDel.' AND occid = '.$this->occid;
						//echo $sql.'<br/>';
						if($this->conn->query($sql)){
							$status = true;
						}
						else{
							$this->errorMessage = 'ERROR removing occurrence attribute: '.$this->conn->error;
							$status = false;
						}
					}
				}
			}

			$sourceStr = 'viewingSpecimenImage';
			if(isset($postArr['source']) && $postArr['source']) $sourceStr = $postArr['source'];
			$sql = 'UPDATE tmattributes a INNER JOIN tmstates s ON a.stateid = s.stateid '.
				'SET a.statuscode = '.$setStatus.', a.notes = '.($postArr['notes']?'"'.$this->cleanInStr($postArr['notes']).'"':'NULL').','.
				'a.source = '.($sourceStr?'"'.$this->cleanInStr($sourceStr).'"':'NULL').','.
				'a.modifieduid = '.$GLOBALS['SYMB_UID'].', a.datelastmodified = NOW() '.
				'WHERE a.occid = '.$this->occid.' AND s.traitid IN('.implode(',',array_keys($this->traitArr)).')';
			if($this->conn->query($sql)){
				$status = true;
			}
			else{
				$this->errorMessage = 'ERROR updating occurrence attribute review status: '.$this->conn->error;
				$status = false;
			}
		}
		return $status;
	}

	public function deleteAttributes($delTraitID){
		$status = false;
		if(is_numeric($delTraitID) && $this->occid){
			$delTraitArr = array($delTraitID);
			$sql = 'SELECT DISTINCT d.traitid FROM tmstates s INNER JOIN tmtraitdependencies d ON s.stateid = d.parentstateid WHERE (s.traitid IN('.$delTraitID.'))';
			do{
				$directParents = '';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$delTraitArr[] = $r->traitid;
					$directParents .= ','.$r->traitid;
				}
				$sql = 'SELECT DISTINCT d.traitid FROM tmstates s INNER JOIN tmtraitdependencies d ON s.stateid = d.parentstateid WHERE (s.traitid IN('.trim($directParents,', ').'))';
				$rs->free();
			}while($directParents);

			$sql = 'DELETE a.* FROM tmattributes a INNER JOIN tmstates s ON a.stateid = s.stateid WHERE (a.occid = '.$this->occid.') AND (s.traitid IN('.implode(',',$delTraitArr).'))';
			if($this->conn->query($sql)){
				$status = true;
			}
			else{
				$this->errorMessage = 'ERROR deleting trait attributes: '.$this->conn->error;
			}
		}
		return $status;
	}

	//Get data functions
	public function getImageUrls(){
		$retArr = array();
		if($this->collidStr){
			if(!$this->sqlBody) $this->setSqlBody();
			$sql = 'SELECT i.occid, IFNULL(o.catalognumber, o.othercatalognumbers) AS catnum '.$this->sqlBody.'ORDER BY RAND() LIMIT 1';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr[$r->occid]['catnum'] = $r->catnum;
				$sql2 = 'SELECT i.imgid, i.url, i.originalurl, i.occid '.
					'FROM images i '.
					'WHERE (i.occid = '.$r->occid.') ';
				$rs2 = $this->conn->query($sql2);
				$cnt = 1;
				while($r2 = $rs2->fetch_object()){
					$retArr[$r2->occid][$cnt]['web'] = $r2->url;
					$retArr[$r2->occid][$cnt]['lg'] = $r2->originalurl;
					$cnt++;
				}
				$rs2->free();
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getSpecimenCount(){
		$retCnt = 0;
		if($this->collidStr){
			if(!$this->sqlBody) $this->setSqlBody();
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS cnt '.$this->sqlBody;
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retCnt = $r->cnt;
			}
			$rs->free();
		}
		return $retCnt;
	}

	private function setSqlBody(){
		$this->sqlBody = 'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
			'LEFT JOIN tmattributes a ON i.occid = a.occid '.
			'WHERE (a.occid IS NULL) AND (o.collid = '.$this->collidStr.') ';
		if(isset($this->filterArr['tidfilter']) && $this->filterArr['tidfilter']){
			//Get Synonyms
			$tidArr = array();
			$sql = 'SELECT ts1.tid '.
				'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'WHERE ts2.tid = '.$this->filterArr['tidfilter'].' AND ts1.taxauthid = 1 AND ts2.taxauthid = 1';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$tidArr[] = $r->tid;
			}
			$rs->free();
			$this->sqlBody = 'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'INNER JOIN taxaenumtree e ON i.tid = e.tid '.
				'LEFT JOIN tmattributes a ON i.occid = a.occid '.
				'WHERE (e.parenttid IN('.$this->filterArr['tidfilter'].') OR e.tid IN('.implode(',',$tidArr).')) '.
				'AND (a.occid IS NULL) AND (o.collid = '.$this->collidStr.') AND (e.taxauthid = 1) ';
		}
		if(isset($this->filterArr['localfilter']) && $this->filterArr['localfilter']){
			$this->sqlBody .= 'AND (o.country = "'.$this->filterArr['localfilter'].'" OR o.stateProvince = "'.$this->filterArr['localfilter'].'") ';
		}
	}

	public function getTraitNames(){
		$retArr = array();
		$sql = 'SELECT t.traitid, t.traitname '.
			'FROM tmtraits t LEFT JOIN tmtraitdependencies d ON t.traitid = d.traitid '.
			'WHERE t.traittype IN("UM","OM","TF","NU") AND d.traitid IS NULL';
		/*
		if(isset($this->filterArr['tidfilter']) && $this->filterArr['tidfilter']){
			$sql = 'SELECT DISTINCT t.traitid, t.traitname '.
				'FROM tmtraits t INNER JOIN tmtraittaxalink l ON t.traitid = l.traitid '.
				'INNER JOIN taxaenumtree e ON l.tid = e.parenttid '.
				'LEFT JOIN tmtraitdependencies d ON t.traitid = d.traitid '.
				'WHERE traittype IN("UM","OM","TF","NU") AND e.taxauthid = 1 AND d.traitid IS NULL AND e.tid = '.$this->filterArr['tidfilter'];
		}
		*/
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->traitid] = $r->traitname;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	public function getTraitArr($traitID = null, $setAttributes = true){
		if($traitID && !is_numeric($traitID)) return null;
		unset($this->traitArr);
		$this->traitArr = array();
		$this->setTraitArr($traitID);
		$this->setTraitStates();
		if($setAttributes) $this->setCodedAttribute();
		return $this->traitArr;
	}

	private function setTraitArr($traitID){
		$sql = 'SELECT traitid, traitname, traittype, units, description, refurl, notes, dynamicproperties FROM tmtraits WHERE traittype IN("UM","OM","TF","NU") ';
		if($traitID) $sql .= 'AND (traitid = '.$traitID.')';
		//echo $sql.'<br/>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if(!isset($this->traitArr[$r->traitid])){
				$this->traitArr[$r->traitid]['name'] = $r->traitname;
				$this->traitArr[$r->traitid]['type'] = $r->traittype;
				$this->traitArr[$r->traitid]['units'] = $r->units;
				$this->traitArr[$r->traitid]['description'] = $r->description;
				$this->traitArr[$r->traitid]['refurl'] = $r->refurl;
				$this->traitArr[$r->traitid]['notes'] = $r->notes;
				$this->traitArr[$r->traitid]['props'] = $r->dynamicproperties;
				//Get dependent traits and append to return array
				$this->setDependentTraits($r->traitid);
			}
		}
		$rs->free();
		return $this->traitArr;
	}

	protected function setDependentTraits($traitid){
		$sql = 'SELECT DISTINCT s.traitid AS parenttraitid, d.parentstateid, d.traitid AS depTraitID '.
			'FROM tmstates s INNER JOIN tmtraitdependencies d ON s.stateid = d.parentstateid '.
			'WHERE (s.traitid = '.$traitid.')';
		//echo $sql.'<br/>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->traitArr[$r->parenttraitid]['states'][$r->parentstateid]['dependTraitID'][] = $r->depTraitID;
			$this->setTraitArr($r->depTraitID);
			$this->traitArr[$r->depTraitID]['dependentTrait'] = 1;
		}
		$rs->free();
	}

	protected function setTraitStates(){
		$sql = 'SELECT traitid, stateid, statename, description, notes, refurl FROM tmstates ';
		if($this->traitArr) $sql .= 'WHERE traitid IN('.implode(',',array_keys($this->traitArr)).') ';
		$sql .= 'ORDER BY traitid, sortseq, statecode ';
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->traitArr[$r->traitid]['states'][$r->stateid]['name'] = $r->statename;
			$this->traitArr[$r->traitid]['states'][$r->stateid]['description'] = $r->description;
			$this->traitArr[$r->traitid]['states'][$r->stateid]['notes'] = $r->notes;
			$this->traitArr[$r->traitid]['states'][$r->stateid]['refurl'] = $r->refurl;
		}
		$rs->free();
	}

	private function setCodedAttribute(){
		$retArr = array();
		$sql = 'SELECT s.traitid, a.stateid, a.xvalue, a.source, a.notes, a.statuscode, a.modifieduid, a.datelastmodified, a.createduid, a.initialtimestamp '.
			'FROM tmattributes a INNER JOIN tmstates s ON a.stateid = s.stateid '.
			'WHERE (a.occid = '.$this->occid.') ';
		if($this->traitArr) $sql .= 'AND (s.traitid IN('.implode(',',array_keys($this->traitArr)).'))';
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->traitArr[$r->traitid]['states'][$r->stateid]['coded'] = $r->xvalue;
			$this->traitArr[$r->traitid]['states'][$r->stateid]['source'] = $r->source;
			$this->traitArr[$r->traitid]['states'][$r->stateid]['notes'] = $r->notes;
			$this->traitArr[$r->traitid]['states'][$r->stateid]['statuscode'] = $r->statuscode;
			$this->traitArr[$r->traitid]['states'][$r->stateid]['modifieduid'] = $r->modifieduid;
			$this->traitArr[$r->traitid]['states'][$r->stateid]['datelastmodified'] = $r->datelastmodified;
			$this->traitArr[$r->traitid]['states'][$r->stateid]['createduid'] = $r->createduid;
			$this->traitArr[$r->traitid]['states'][$r->stateid]['createduid'] = $r->initialtimestamp;
			$retArr[$r->stateid] = '';
		}
		$rs->free();
		return $retArr;
	}

	public function echoFormTraits($traitID){
		echo $this->getTraitUnitString($traitID,true);
	}

	private function getTraitUnitString($traitID,$display,$classStr=''){
		$controlType = '';
		if($this->traitArr[$traitID]['props']){
			$propArr = json_decode($this->traitArr[$traitID]['props'],true);
			if(isset($propArr[0]['controlType'])) $controlType = $propArr[0]['controlType'];
		}
		$innerStr = '<div style="clear:both">';
		if(isset($this->traitArr[$traitID]['states'])){
			if($this->traitArr[$traitID]['type']=='TF'){
				$innerStr .= '<div style="float:left;margin-left: 15px">'.$this->traitArr[$traitID]['name'].':</div>';
				$innerStr .= '<div style="clear:both;margin-left: 25px">';
			}
			else $innerStr .= '<div style="float:left;">';
			$attrStateArr = $this->traitArr[$traitID]['states'];
			foreach($attrStateArr as $sid => $sArr){
				$isCoded = false;
				if(array_key_exists('coded',$sArr)){
					if(is_numeric($sArr['coded'])) $isCoded = $sArr['coded'];
					else $isCoded = true;
					$this->stateCodedArr[$sid] = $sid;
				}
				$depTraitIdArr = array();
				if(isset($sArr['dependTraitID']) && $sArr['dependTraitID']) $depTraitIdArr = $sArr['dependTraitID'];
				if($this->traitArr[$traitID]['type']=='NU'){
					$innerStr .= '<div title="'.$sArr['description'].'" style="clear:both">';
					$innerStr .= $sArr['name'].
					$innerStr .= ': <input name="traitid-'.$traitID.'[]" class="'.$classStr.'" type="text" value="'.$sid.'-'.($isCoded!==false?$isCoded:'').'" onchange="traitChanged(this)" style="width:50px" /> ';
					if($depTraitIdArr){
						foreach($depTraitIdArr as $depTraitId){
							$innerStr .= $this->getTraitUnitString($depTraitId,$isCoded,trim($classStr.' child-'.$sid));
						}
					}
				}
				else{
					if($controlType == 'checkbox' || $controlType == 'radio'){
						$innerStr .= '<div title="'.$sArr['description'].'" style="clear:both">';
						$innerStr .= '<input name="traitid-'.$traitID.'[]" class="'.$classStr.'" type="'.$controlType.'" value="'.$sid.'" '.($isCoded?'checked':'').' onchange="traitChanged(this)" /> ';
						$innerStr .= $sArr['name'];
					}
					elseif($controlType == 'select'){
						$innerStr .= '<option value="'.$sid.'" '.($isCoded?'selected':'').'>'.$sArr['name'].'</option>';
					}
					if($depTraitIdArr){
						foreach($depTraitIdArr as $depTraitId){
							$innerStr .= $this->getTraitUnitString($depTraitId,$isCoded,trim($classStr.' child-'.$sid));
						}
					}
					if($controlType != 'select') $innerStr .= '</div>';
				}
			}
			$innerStr .= '</div>';
		}
		$innerStr .= '</div>';
		//Display if trait has been coded or is the first/base trait (e.g. $indend == 0)
		$divClass = '';
		if($classStr){
			$classArr = explode(' ',$classStr);
			$divClass = array_pop($classArr);
		}
		$outStr = '<div class="'.$divClass.'" style="margin-left:'.($classStr?'10':'').'px; display:'.($display?'block':'none').';">';
		if($controlType == 'select'){
			$outStr .= '<select name="stateid">';
			$outStr .= '<option value="">Select State</option>';
			$outStr .= '<option value="">------------------------------</option>';
			$outStr .= $innerStr;
			$outStr .= '</select>';
		}
		else{
			$outStr .= $innerStr;
		}
		$outStr .= '</div>';
		return $outStr;
	}

	public function getTaxonFilterSuggest($str,$exactMatch=false){
		$retArr = array();
		if($str){
			$sql = 'SELECT tid, sciname FROM taxa ';
			if($exactMatch) $sql .= 'WHERE sciname = "'.$str.'"';
			else $sql .= 'WHERE sciname LIKE "'.$str.'%"';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[] = array('id' => $r->tid, 'value' => $r->sciname);
			}
			$rs->free();
		}
		return json_encode($retArr);
	}

	//Attribute review functions
	public function getReviewUrls($traitID){
		$retArr = array();
		//Some sanitation
		if(is_numeric($traitID) && $this->collidStr){
			$targetOccid = 0;
			//$traitID is required
			$sql1 = 'SELECT DISTINCT o.occid, IFNULL(o.catalognumber, o.othercatalognumbers) AS catnum '.$this->getReviewSqlBase($traitID).' LIMIT '.$this->filterArr['start'].',1';
			$rs1 = $this->conn->query($sql1);
			while($r1 = $rs1->fetch_object()){
				$targetOccid = $r1->occid;
				$retArr[$r1->occid]['catnum'] = $r1->catnum;
			}
			$rs1->free();
			//Get images for target occid (isolation query into separate statements returns all images where there are multiples per specimen)
			$sql = 'SELECT imgid, url, originalurl, occid FROM images WHERE (occid = '.$targetOccid.')';
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			$cnt = 1;
			while($r = $rs->fetch_object()){
				$retArr[$r->occid][$cnt]['web'] = $r->url;
				$retArr[$r->occid][$cnt]['lg'] = $r->originalurl;
				$cnt++;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getReviewCount($traitID){
		$cnt = 0;
		//Some sanitation
		if(is_numeric($traitID) && $this->collidStr){
			//$traitID is required
			$sql = 'SELECT COUNT(DISTINCT o.occid) as cnt '.$this->getReviewSqlBase($traitID);
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	private function getReviewSqlBase($traitID){
		if($this->reviewSqlBase) return $this->reviewSqlBase;
		$stateArr = array();
		$sql = 'SELECT stateid FROM tmstates WHERE traitid = '.$traitID;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$stateArr[] = $r->stateid;
		}
		$rs->free();
		if($stateArr){
			$this->reviewSqlBase = 'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'INNER JOIN tmattributes a ON i.occid = a.occid '.
				'WHERE (a.stateid IN('.implode(',',$stateArr).')) AND (o.collid = '.$this->collidStr.') ';
			if(isset($this->filterArr['reviewuid']) && $this->filterArr['reviewuid']){
				$this->reviewSqlBase .= 'AND (a.createduid = '.$this->filterArr['reviewuid'].') ';
			}
			if(isset($this->filterArr['reviewdate']) && $this->filterArr['reviewdate']){
				$this->reviewSqlBase .= 'AND (date(a.initialtimestamp) = "'.$this->filterArr['reviewdate'].'") ';
			}
			if(isset($this->filterArr['reviewstatus']) && $this->filterArr['reviewstatus']){
				$this->reviewSqlBase .= 'AND (a.statuscode = '.$this->filterArr['reviewstatus'].') ';
			}
			else{
				$this->reviewSqlBase .= 'AND (a.statuscode IS NULL OR a.statuscode = 0) ';
			}
			if(isset($this->filterArr['sourcefilter']) && $this->filterArr['sourcefilter']){
				$this->reviewSqlBase .= 'AND (a.source = "'.$this->filterArr['sourcefilter'].'") ';
			}
			if(isset($this->filterArr['localfilter']) && $this->filterArr['localfilter']){
				$this->reviewSqlBase .= 'AND (o.country = "'.$this->filterArr['localfilter'].'" OR o.stateProvince = "'.$this->filterArr['localfilter'].'") ';
			}
		}
		return $this->reviewSqlBase;
	}

	public function getEditorArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT u.uid, u.lastname, u.firstname, u.username '.
			'FROM tmattributes a INNER JOIN users u ON a.createduid = u.uid '.
			'INNER JOIN omoccurrences o ON a.occid = o.occid '.
			'WHERE o.collid = '.$this->collidStr.' ORDER BY u.lastname, u.firstname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->uid] = $r->lastname.($r->firstname?', '.$r->firstname:'').' ('.$r->username.')';
		}
		$rs->free();
		return $retArr;
	}

	public function getEditDates(){
		$retArr = array();
		$sql = 'SELECT DISTINCT DATE(a.initialtimestamp) as d '.
			'FROM tmattributes a INNER JOIN omoccurrences o ON a.occid = o.occid '.
			'WHERE o.collid = '.$this->collidStr.' ORDER BY a.initialtimestamp DESC';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->d;
		}
		$rs->free();
		return $retArr;
	}

	//Attribute mining
	public function getFieldValueArr($traitID, $fieldName, $tidFilter, $stringFilter){
		$retArr = array();
		if(is_numeric($traitID)){
			$sql = 'SELECT o.'.$this->cleanInStr($fieldName).', count(DISTINCT o.occid) AS cnt FROM omoccurrences o '.
				$this->getMiningSqlFrag($traitID, $fieldName, $tidFilter, $stringFilter).
				'GROUP BY o.'.$fieldName;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_assoc()){
				if($r[$fieldName]) $retArr[] = strtolower($r[$fieldName]).' - ['.$r['cnt'].']';
			}
			$rs->free();
			sort($retArr);
		}
		return $retArr;
	}

	public function submitBatchAttributes($traitID, $fieldName, $tidFilter, $stateIDArr, $fieldValueArr, $notes, $reviewStatus){
		set_time_limit(1800);
		$status = true;
		$fieldArr = array();
		foreach($fieldValueArr as $fieldValue){
			$fieldValue = htmlspecialchars_decode($fieldValue);
			if(preg_match('/(.+) - \[\d+\]$/',$fieldValue,$m)) $fieldValue = $m[1];
			$fieldArr[] = $this->conn->real_escape_string($fieldValue);
		}
		if($fieldArr){
			$occArr = array();
			$sql = 'SELECT DISTINCT occid FROM omoccurrences o '.
				$this->getMiningSqlFrag($traitID, $fieldName, $tidFilter).
				'AND ('.$this->cleanInStr($fieldName).' IN("'.implode('","',$fieldArr).'")) ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$occArr[] = $r->occid;
			}
			$rs->free();
			$occidChuckArr = array_chunk($occArr, '100000');
			foreach($stateIDArr as $stateID){
				if(is_numeric($stateID)){
					foreach($occidChuckArr as $oArr){
						$sql = '';
						foreach($oArr as $occid){
							$sql .= ',('.$stateID.','.$occid.')';
						}
						if($sql){
							$sql = 'INSERT INTO tmattributes(stateid,occid) VALUES'.substr($sql,1);
							if(!$this->conn->query($sql)){
								$this->errorMessage .= 'ERROR saving batch occurrence attributes: '.$this->conn->error.'; ';
								$status = false;
							}
						}
					}
				}
			}
			//Add notes, source, and editor uid
			$occidChuckArr = array_chunk($occArr, '200000');
			foreach($occidChuckArr as $oArr){
				$sqlUpdate = 'UPDATE tmattributes SET source = "verbatimTextMining:'.$this->cleanInStr($fieldName).'", createduid = '.$GLOBALS['SYMB_UID'];
				if($notes) $sqlUpdate .= ', notes = "'.$this->cleanInStr($notes).'"';
				if(is_numeric($reviewStatus)) $sqlUpdate .= ', statuscode = "'.$this->cleanInStr($reviewStatus).'"';
				$sqlUpdate .= ' WHERE stateid IN('.implode(',',$stateIDArr).') AND occid IN('.implode(',',$oArr).')';
				//echo $sqlUpdate;
				if(!$this->conn->query($sqlUpdate)){
					$this->errorMessage .= 'ERROR saving batch occurrence attributes(2): '.$this->conn->error.'; ';
					$status = false;
				}
			}
		}
		return $status;
	}

	private function getMiningSqlFrag($traitID, $fieldName, $tidFilter, $stringFilter = ''){
		$sql = '';
		if(is_numeric($traitID)){
			if($tidFilter && is_numeric($tidFilter)) $sql = 'INNER JOIN taxaenumtree e ON o.tidinterpreted = e.tid ';
			$sql .= 'WHERE (o.'.$fieldName.' IS NOT NULL) AND (o.occid NOT IN(SELECT t.occid FROM tmattributes t INNER JOIN tmstates s ON t.stateid = s.stateid WHERE s.traitid = '.$traitID.')) ';
			if($tidFilter && is_numeric($tidFilter)) $sql .= 'AND (e.taxauthid = 1) AND (e.parenttid = '.$tidFilter.' OR o.tidinterpreted = '.$tidFilter.') ';
			if($this->collidStr != 'all') $sql .= 'AND (o.collid IN('.$this->collidStr.')) ';
			if($stringFilter) $sql .= 'AND (o.'.$this->cleanInStr($fieldName).' LIKE "%'.$this->cleanInStr($stringFilter).'%") ';
		}
		return $sql;
	}

	//Setters, getters, and misc get functions
	public function getObserverUid(){
		$retUid = 0;
		$sql = 'SELECT observeruid FROM omoccurrences WHERE occid = '.$this->occid;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retUid = $r->observeruid;
		}
		$rs->free();
		return $retUid;
	}

	public function getCollectionList($collArr){
		$retArr = array();
		$sql = 'SELECT collid, collectionname, CONCAT_WS("-",institutioncode,collectioncode) as instcode FROM omcollections ';
		if($collArr) $sql .= 'WHERE collid IN('.implode(',',$collArr).')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname.' ('.$r->instcode.')';
		}
		$rs->free();
		return $retArr;
	}

	public function setCollid($idStr){
		if(preg_match('/^[0-9,al]+$/', $idStr)){
			$this->collidStr = $idStr;
		}
	}

	public function setOccid($occid){
		if(is_numeric($occid)){
			$this->occid = $occid;
		}
	}

	public function getStateCodedStr(){
		return implode(',', $this->stateCodedArr);
	}

	public function setFilterAttributes($postArr){
		if(array_key_exists('taxonfilter', $postArr)) $this->filterArr['taxonfilter'] = $this->cleanInStr($postArr['taxonfilter']);
		if(array_key_exists('tidfilter', $postArr) && is_numeric($postArr['tidfilter'])) $this->filterArr['tidfilter'] = $postArr['tidfilter'];
		if(array_key_exists('reviewuid', $postArr) && is_numeric($postArr['reviewuid'])) $this->filterArr['reviewuid'] = $postArr['reviewuid'];
		if(array_key_exists('reviewdate', $postArr) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$postArr['reviewdate'])) $this->filterArr['reviewdate'] = $postArr['reviewdate'];
		if(array_key_exists('reviewstatus', $postArr) && is_numeric($postArr['reviewstatus'])) $this->filterArr['reviewstatus'] = $postArr['reviewstatus'];
		if(array_key_exists('sourcefilter', $postArr) && $postArr['sourcefilter']) $this->filterArr['sourcefilter'] = $this->cleanInStr($postArr['sourcefilter']);
		if(array_key_exists('localfilter', $postArr) && $postArr['localfilter']) $this->filterArr['localfilter'] = $this->cleanInStr($postArr['localfilter']);
		if(array_key_exists('start', $postArr) && is_numeric($postArr['start'])) $this->filterArr['start'] = $postArr['start'];
		else $this->filterArr['start'] = 0;
	}

	public function getFilterAttribute($attributeName){
		if(array_key_exists($attributeName, $this->filterArr)) return $this->filterArr[$attributeName];
		return '';
	}

	public function getLocalFilterOptions(){
		$retArr = array();
		$sql = 'SELECT geoterm FROM geographicthesaurus WHERE geolevel IN(50,60)';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->geoterm;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	public function getSourceControlledArr($currentSetStr=''){
		$sourceControlArr = array('machineLearning','physicalSpecimen','verbatimTextMining','viewingImage');
		if($currentSetStr){
			if(!in_array($currentSetStr, $sourceControlArr)) $sourceControlArr[] = $currentSetStr;
			sort($sourceControlArr);
		}
		return $sourceControlArr;
	}
}
?>
