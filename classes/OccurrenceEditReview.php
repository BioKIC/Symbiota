<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class OccurrenceEditReview extends Manager{

	private $collid;
	private $collAcronym;
	private $obsUid = 0;

	private $display = 1;
	private $appliedStatusFilter = '';
	private $reviewStatusFilter;
	private $fieldNameFilter;
	private $editorFilter;
	private $queryOccidFilter;
	private $startDateFilter;
	private $endDateFilter;
	private $pageNumber = 0;
	private $limitNumber;
	private $sqlBase;

	function __construct(){
		parent::__construct(null,'write');
	}

	function __destruct(){
 		parent::__destruct();
	}

	public function setCollId($id){
		if(is_numeric($id)){
			$this->collid = $id;
			$sql = 'SELECT collectionname, institutioncode, collectioncode, colltype FROM omcollections WHERE (collid = '.$id.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$collName = $r->collectionname.' (';
				$this->collAcronym = $r->institutioncode;
				$collName .= $r->institutioncode;
				if($r->collectioncode){
					$collName .= ':'.$r->collectioncode;
					$this->collAcronym .= ':'.$r->collectioncode;
				}
				$collName .= ')';
				if($r->colltype == 'General Observations') $this->obsUid = $GLOBALS['SYMB_UID'];
			}
			$rs->free();
		}
		return $collName;
	}

	public function getEditCnt(){
		if($this->display == 1){
			return $this->getOccurEditCnt();
		}
		elseif($this->display == 2){
			return $this->getRevisionCnt();
		}
		return 0;
	}

	public function getEditArr(){
		if($this->display == 1){
			return $this->getOccurEditArr();
		}
		elseif($this->display == 2){
			return $this->getRevisionArr();
		}
		return null;
	}

	//Occurrence edits (omoccuredits)
	private function getOccurEditCnt(){
		$sql = 'SELECT COUNT(e.ocedid) AS fullcnt '.$this->getEditSqlBase();
		$rsCnt = $this->conn->query($sql);
		if($rCnt = $rsCnt->fetch_object()){
			$recCnt = $rCnt->fullcnt;
		}
		$rsCnt->free();
		return $recCnt;
	}

	private function getOccurEditArr(){
		$retArr = Array();
		$sql = 'SELECT e.ocedid,e.occid,o.catalognumber,o.othercatalognumbers,e.fieldname,e.fieldvaluenew,e.fieldvalueold,e.reviewstatus,e.appliedstatus,e.uid, e.initialtimestamp '.
			$this->getEditSqlBase().' ORDER BY e.initialtimestamp DESC, e.fieldname ASC '.
			'LIMIT '.($this->pageNumber*$this->limitNumber).','.($this->limitNumber+1);
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->occid][$r->ocedid][$r->appliedstatus]['ts'] = $r->initialtimestamp;
			$retArr[$r->occid][$r->ocedid][$r->appliedstatus]['rstatus'] = $r->reviewstatus;
			$retArr[$r->occid][$r->ocedid][$r->appliedstatus]['uid'] = $r->uid;
			$retArr[$r->occid][$r->ocedid][$r->appliedstatus]['f'][$r->fieldname]['old'] = $r->fieldvalueold;
			$retArr[$r->occid][$r->ocedid][$r->appliedstatus]['f'][$r->fieldname]['new'] = $r->fieldvaluenew;
			$retArr[$r->occid]['catnum'] = $r->catalognumber . ($r->catalognumber && $r->othercatalognumbers ? '<br>' : '') . $r->othercatalognumbers;
		}
		$rs->free();
		$this->appendAdditionalIdentifiers($retArr);
		return $retArr;
	}

	private function appendAdditionalIdentifiers(&$occArr){
		if($occArr){
			$sql = 'SELECT occid, identifierValue, identifierName FROM omoccuridentifiers WHERE occid IN('.implode(',',array_keys($occArr)).') ORDER BY sortBy';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if(!$occArr[$r->occid]['catnum'] || strpos($occArr[$r->occid]['catnum'], $r->identifierValue) === false){
					if($occArr[$r->occid]['catnum']) $occArr[$r->occid]['catnum'] .= '<br>';
					$occArr[$r->occid]['catnum'] .= $r->identifierValue;
				}
			}
			$rs->free();
		}
	}

	private function getEditSqlBase($includeUserTable=false){
		$sqlBase = '';
		if($this->collid){
			$sqlBase = 'FROM omoccuredits e INNER JOIN omoccurrences o ON e.occid = o.occid ';
			if($includeUserTable) $sqlBase .= 'INNER JOIN users u ON e.uid = u.uid ';
			$sqlBase .= 'WHERE (o.collid = '.$this->collid.') ';
			if($this->appliedStatusFilter !== ''){
				$sqlBase .= 'AND (e.appliedstatus = '.$this->appliedStatusFilter.') ';
			}
			if($this->reviewStatusFilter){
				$sqlBase .= 'AND (e.reviewstatus IN('.$this->reviewStatusFilter.')) ';
			}
			if($this->fieldNameFilter){
				$sqlBase .= 'AND (e.fieldName = "'.$this->cleanInStr($this->fieldNameFilter).'") ';
			}
			if($this->editorFilter){
				$sqlBase .= 'AND (e.uid = '.$this->editorFilter.') ';
			}
			if($this->queryOccidFilter){
				$sqlBase .= 'AND (e.occid = '.$this->queryOccidFilter.') ';
			}
			if($this->startDateFilter){
				$sqlBase .= 'AND (e.initialtimestamp >= "'.$this->startDateFilter.'") ';
			}
			if($this->endDateFilter){
				$sqlBase .= 'AND (e.initialtimestamp <= "'.$this->endDateFilter.'") ';
			}
			if($this->obsUid){
				$sqlBase .= 'AND (o.observeruid = '.$this->obsUid.') ';
			}
		}
		return $sqlBase;
	}

	//Occurrence revisions
	private function getRevisionCnt(){
		$sql = 'SELECT COUNT(r.orid) AS fullcnt '.$this->getRevisionSqlBase();
		//echo 'revision cnt: '.$sql.'<br/>';
		$rsCnt = $this->conn->query($sql);
		if($rCnt = $rsCnt->fetch_object()){
			$recCnt = $rCnt->fullcnt;
		}
		$rsCnt->free();
		return $recCnt;
	}

	private function getRevisionArr(){
		$retArr = Array();
		$sql = 'SELECT r.orid, r.occid, o.catalognumber, r.oldvalues, r.newvalues, r.externalsource, r.externaleditor, r.reviewstatus, r.appliedstatus, r.errormessage, '.
			'r.uid, r.externaltimestamp, r.initialtimestamp '.
			$this->getRevisionSqlBase().' ORDER BY r.initialtimestamp DESC '.
			'LIMIT '.($this->pageNumber*$this->limitNumber).','.($this->limitNumber+1);
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){

			$retArr[$r->occid][$r->orid][$r->appliedstatus]['ts'] = $r->initialtimestamp;
			$retArr[$r->occid][$r->orid][$r->appliedstatus]['rstatus'] = $r->reviewstatus;
			$retArr[$r->occid][$r->orid][$r->appliedstatus]['uid'] = $r->uid;
			$retArr[$r->occid][$r->orid][$r->appliedstatus]['exsource'] = $r->externalsource;
			$retArr[$r->occid][$r->orid][$r->appliedstatus]['exeditor'] = $r->externaleditor;
			$retArr[$r->occid][$r->orid][$r->appliedstatus]['errmsg'] = $r->errormessage;
			$retArr[$r->occid][$r->orid][$r->appliedstatus]['editor'] = $r->externaleditor;
			$retArr[$r->occid][$r->orid][$r->appliedstatus]['extstamp'] = $r->externaltimestamp;
			$retArr[$r->occid]['catnum'] = $r->catalognumber;

			$oldValues = json_decode($r->oldvalues,true);
			$newValues = json_decode($r->newvalues,true);
			$cnt = 0;
			foreach($oldValues as $fieldName => $value){
				if($fieldName != 'georeferencesources' && $fieldName != 'georeferencedby'){
					$retArr[$r->occid][$r->orid][$r->appliedstatus]['f'][$fieldName]['old'] = $value;
					$retArr[$r->occid][$r->orid][$r->appliedstatus]['f'][$fieldName]['new'] = (isset($newValues[$fieldName])?$newValues[$fieldName]:'ERROR');
					$cnt++;
				}
			}
		}
		$rs->free();
		return $retArr;
	}

	private function getRevisionSqlBase($includeUserTable = false){
		$sqlBase = '';
		if($this->collid){
			$sqlBase = 'FROM omoccurrevisions r INNER JOIN omoccurrences o ON r.occid = o.occid ';
			if($includeUserTable) $sqlBase .= 'INNER JOIN users u ON r.uid = u.uid ';
			$sqlBase .= 'WHERE (o.collid = '.$this->collid.') ';
			if($this->appliedStatusFilter !== ''){
				$sqlBase .= 'AND (r.appliedstatus = '.$this->appliedStatusFilter.') ';
			}
			if($this->reviewStatusFilter){
				$sqlBase .= 'AND (r.reviewstatus IN('.$this->reviewStatusFilter.')) ';
			}
			if($this->editorFilter){
				if(is_numeric($this->editorFilter)){
					$sqlBase .= 'AND (r.uid = '.$this->editorFilter.') ';
				}
				else{
					$sqlBase .= 'AND (r.externaleditor = "'.$this->editorFilter.'") ';
				}
			}
			if($this->startDateFilter){
				$sqlBase .= 'AND (r.initialtimestamp >= "'.$this->startDateFilter.'") ';
			}
			if($this->endDateFilter){
				$sqlBase .= 'AND (r.initialtimestamp <= "'.$this->endDateFilter.'") ';
			}
			if($this->queryOccidFilter){
				$sqlBase .= 'AND (r.occid = '.$this->queryOccidFilter.') ';
			}
			if($this->obsUid){
				$sqlBase .= 'AND (o.observeruid = '.$this->obsUid.') ';
			}
		}
		return $sqlBase;
	}

	//Actions
	public function updateRecords($postArr){
		if($this->display == 1){
			return $this->updateOccurEditRecords($postArr);
		}
		elseif($this->display == 2){
			return $this->updateRevisionRecords($postArr);
		}
		return null;
	}

	private function updateOccurEditRecords($postArr){
		if(!array_key_exists('id',$postArr)) return;
		$status = true;
		$idStr = implode(',',$postArr['id']);
		if($idStr){
			//$idStr = $this->getFullOcedidStr($idStr);
			//Apply edits
			$applyTask = $postArr['applytask'];
			//Apply edits with applied status = 0
			$sql = 'SELECT ocedid, occid, fieldname, fieldvalueold, fieldvaluenew, appliedstatus FROM omoccuredits WHERE (ocedid IN('.$idStr.')) ORDER BY initialtimestamp';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$status = false;
				if($applyTask){
					if($applyTask == 'apply') $value = $r->fieldvaluenew;
					else $value = $r->fieldvalueold;
					$tableName = 'omoccurrences';
					$fieldName = $r->fieldname;
					if($fieldName == 'omoccuridentifiers') $tableName = 'omoccuridentifiers';
					if(strpos($fieldName, ':')){
						$pArr = explode(':', $fieldName);
						$tableName = $pArr[0];
						$fieldName = $pArr[1];
					}
					if(($applyTask == 'apply' && !$r->appliedstatus) || ($applyTask != 'apply' && $r->appliedstatus)){
						if($tableName == 'omoccuridentifiers'){
							$status = $this->applyIdentifierEdits($r->occid, $fieldName, $r->fieldvalueold, $r->fieldvaluenew, $applyTask);
						}
						elseif($tableName == 'omoccurpaleo'){
							$status = $this->applyPaleoEdits($r->occid, $fieldName, $value, $applyTask);
						}
						elseif($tableName == 'omexsiccatiocclink'){
							$status = $this->applyExsiccatiEdits($r->occid, $fieldName, $value, $applyTask);
						}
						elseif($tableName == 'omoccurrences'){
							$status = $this->applyOccurrenceEdit($r->occid, $fieldName, $value, $applyTask);
						}
					}
					if($postArr['rstatus']) $status = true;
					if(!$status) $applyTask = '';
				}
			}
			$rs->free();
			$this->setEditStatus($idStr, 'omoccuredits', $applyTask, $postArr['rstatus']);
		}
		return $status;
	}

	private function applyOccurrenceEdit($occid, $fieldName, $value, $applyTask){
		$status = true;
		$sql = 'UPDATE omoccurrences SET '.$fieldName.' = '.($value !== ''?'"'.$this->cleanInStr($value).'"':'NULL').' WHERE (occid = '.$occid.')';
		if(!$this->conn->query($sql)){
			$warningKey = 'ERROR_REVERTING_EDITS';
			if($applyTask == 'apply') $warningKey = 'ERROR_APPLYING_EDITS';
			$this->warningArr[$warningKey] = $this->conn->error;
			$status = false;
		}
		return $status;
	}

	private function applyIdentifierEdits($occid, $fieldName, $oldValue, $newValue, $applyTask){
		$status = true;
		$matchValue = $oldValue;
		$changeValue = $newValue;
		if($applyTask == 'revert'){
			$matchValue = $newValue;
			$changeValue = $oldValue;
		}
		$matchTag = '';
		$changeTag = '';
		if($p = strpos($matchValue, ':')){
			$matchTag = substr($matchValue, 0, $p);
			$matchValue = trim(substr($matchValue, $p + 1));
		}
		if($p = strpos($changeValue, ':')){
			$changeTag = substr($changeValue, 0, $p);
			$changeValue = trim(substr($changeValue, $p + 1));
		}
		$sql = '';
		$idPK = 0;
		if($matchValue){
			$rs = $this->conn->query('SELECT idomoccuridentifiers FROM omoccuridentifiers WHERE (occid = '.$occid.') AND (identifierName = "'.$this->cleanInStr($matchTag).'") AND (identifierValue = "'.$this->cleanInStr($matchValue).'")');
			if($r = $rs->fetch_object()){
				$idPK = $r->idomoccuridentifiers;
			}
			$rs->free();
			if($idPK){
				$sql = 'DELETE FROM omoccuridentifiers ';
				if($changeValue) $sql = 'UPDATE omoccuridentifiers SET identifierName = "'.$this->cleanInStr($changeTag).'", identifierValue = "'.$this->cleanInStr($changeValue).'" ';
				$sql .= 'WHERE idomoccuridentifiers = '.$idPK;
			}
		}
		if(!$idPK && $changeValue){
			$sql = 'INSERT INTO omoccuridentifiers(occid, identifierName, identifierValue, modifiedUid)
				VALUES('.$occid.', "'.$this->cleanInStr($changeTag).'", "'.$this->cleanInStr($changeValue).'", '.$GLOBALS['SYMB_UID'].') ';
		}
		if($sql){
			if(!$this->conn->query($sql)){
				$warningKey = 'ERROR_REVERTING_ID';
				if($applyTask == 'apply') $warningKey = 'ERROR_APPLYING_ID';
				$this->warningArr[$warningKey] = $this->conn->error;
				$status = false;
			}
		}
		return $status;
	}

	private function applyPaleoEdits($occid, $fieldName, $value, $applyTask){
		$status = true;
		$sql = 'DELETE FROM omoccurpaleo WHERE (occid = '.$occid.')';
		if($value) $sql = 'UPDATE omoccurpaleo SET '.$fieldName.' = '.($value !== ''?'"'.$this->cleanInStr($value).'"':'NULL').' WHERE (occid = '.$occid.')';
		echo '<div>'.$sql.'</div>';
		if(!$this->conn->query($sql)){
			$warningKey = 'ERROR_REVERTING_PALEO';
			if($applyTask == 'apply') $warningKey = 'ERROR_APPLYING_PALEO';
			$this->warningArr[$warningKey] = $this->conn->error;
			$status = false;
		}
		return $status;
	}

	private function applyExsiccatiEdits($occid, $fieldName, $value, $applyTask){
		$status = false;

		return $status;
	}

	private function updateRevisionRecords($postArr){
		if(!array_key_exists('id',$postArr)) return false;
		$status = false;
		$idStr = implode(',',$postArr['id']);
		if($idStr){
			$applyTask = $postArr['applytask'];
			if($applyTask){
				//Apply edits with applied status = 0
				$sql = 'SELECT occid, newvalues, oldvalues '.
					'FROM omoccurrevisions '.
					'WHERE appliedstatus = '.($applyTask == 'apply'?'0':'1').' AND (orid IN('.$idStr.')) ORDER BY initialtimestamp';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$dwcArr = json_decode(($applyTask == 'apply')?$r->newvalues:$r->oldvalues);
					$sqlFrag = '';
					foreach($dwcArr as $fieldName => $fieldValue){
						$sqlFrag .= ','.$fieldName.' = '.($fieldValue?'"'.$this->cleanInStr($fieldValue).'"':'NULL').' ';
					}
					$uSql = 'UPDATE omoccurrences SET '.trim($sqlFrag,', ').' WHERE (occid = '.$r->occid.')';
					//echo '<div>'.$uSql.'</div>'; exit;
					if($this->conn->query($uSql)){
						$status = true;
					}
					else{
						$warningKey = 'ERROR_REVERTING_REVISIONS';
						if($applyTask == 'apply') $warningKey = 'ERROR_APPLYING_REVISIONS';
						$this->warningArr[$warningKey] = $this->conn->error;
						$status = false;
					}
				}
				$rs->free();
			}
			$this->setEditStatus($idStr, 'omoccurrevisions', $applyTask, $postArr['rstatus']);
		}
		return $status;
	}

	private function setEditStatus($idStr, $targetTable, $applyStatus, $reviewStatus){
		$status = false;
		$actionArr = array();
		if($applyStatus){
			if($applyStatus == 'apply') $applyStatus = 1;
			else $applyStatus = 0;
			$actionArr[] = 'appliedstatus = '.$applyStatus;
		}
		if(is_numeric($reviewStatus) && $reviewStatus){
			$actionArr[] = 'reviewstatus = '.$reviewStatus;
		}
		if($actionArr){
			$tablePK = 'ocedid';
			if($targetTable == 'omoccurrevisions') $tablePK = 'orid';
			$sql = 'UPDATE '.$targetTable.' SET '.implode(',', $actionArr).' WHERE '.$tablePK.' IN('.$idStr.')';
			if($this->conn->query($sql)){
				$status = false;
			}
		}
		return $status;
	}

	public function deleteEdits($idStr){
		if($this->display == 1){
			return $this->deleteOccurEdits($idStr);
		}
		elseif($this->display == 2){
			return $this->deleteRevisionsEdits($idStr);
		}
		return null;
	}

	private function deleteOccurEdits($idStr){
		$status = true;
		if(!preg_match('/^[\d,]+$/', $idStr)) return false;
		//$idStr = $this->getFullOcedidStr($idStr);
		$sql = 'DELETE FROM omoccuredits WHERE (ocedid IN('.$idStr.'))';
		//echo '<div>'.$sql.'</div>'; exit;
		if(!$this->conn->query($sql)){
			$this->errorMessage = $this->conn->error;
			$status = false;
		}
		return $status;
	}

	private function deleteRevisionsEdits($idStr){
		$status = true;
		if(!preg_match('/^[\d,]+$/', $idStr)) return false;
		$sql = 'DELETE FROM omoccurrevisions WHERE (orid IN('.$idStr.'))';
		//echo '<div>'.$sql.'</div>';
		if($this->conn->query($sql)){
			$this->errorMessage = $this->conn->error;
			$status = false;
		}
		return $status;
	}

	public function exportCsvFile($idStr, $exportAll = false){
		$status = true;
		//if($this->display == 1) $idStr = $this->getFullOcedidStr($idStr);
		//Get Records
		$sql = '';

		if($this->display == 1){
			$sql = 'SELECT e.ocedid AS id, o.occid, o.catalognumber, o.dbpk, e.fieldname, e.fieldvaluenew, e.fieldvalueold, e.reviewstatus, e.appliedstatus, '.
				'CONCAT_WS(", ",u.lastname,u.firstname) AS username, e.initialtimestamp ';
			if($exportAll) $sql .= $this->getEditSqlBase(true);
			else{
				$sql .= 'FROM omoccuredits e INNER JOIN omoccurrences o ON e.occid = o.occid '.
					'INNER JOIN users u ON e.uid = u.uid '.
					'WHERE (o.collid = '.$this->collid.') AND (e.ocedid IN('.$idStr.')) ';
				if($this->obsUid) $sql .= 'AND (o.observeruid = '.$this->obsUid.') ';
			}
			$sql .= 'ORDER BY e.fieldname ASC, e.initialtimestamp DESC';
		}
		else{
			$sql = 'SELECT r.orid AS id, o.occid, o.catalognumber, o.dbpk, r.oldvalues, r.newvalues, r.reviewstatus, r.appliedstatus, '.
				'r.externaleditor, CONCAT_WS(", ",u.lastname,u.firstname) AS username, r.externaltimestamp, r.initialtimestamp ';
			if($exportAll) $sql .= $this->getRevisionSqlBase(true);
			else{
				$sql .= 'FROM omoccurrevisions r INNER JOIN omoccurrences o ON r.occid = o.occid '.
					'LEFT JOIN users u ON r.uid = u.uid '.
					'WHERE (o.collid = '.$this->collid.') AND (r.orid IN('.$idStr.')) ';
				if($this->obsUid) $sql .= 'AND (o.observeruid = '.$this->obsUid.') ';
			}
			$sql .= 'ORDER BY r.initialtimestamp DESC';
		}
		//echo '<div>field: '.$this->fieldNameFilter.'; export: '.$sql.'</div>'; exit;
		if($sql){
			$rs = $this->conn->query($sql,MYSQLI_USE_RESULT);
			$fileName = $this->collAcronym.'SpecimenEdits_'.time().'.csv';
			header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header ('Content-Type: text/csv');
			header ('Content-Disposition: attachment; filename="'.$fileName.'"');
			$outFH = fopen('php://output', 'w');
			$headerArr = array('EditId','occid','CatalogNumber','dbpk','ReviewStatus','AppliedStatus','Editor','Timestamp','FieldName','OldValue','NewValue');
			fputcsv($outFH, $headerArr);
			while($r = $rs->fetch_object()){
				$outArr = array(0 => $r->id, 1 => $r->occid, 2 => $r->catalognumber, 3 => $r->dbpk);
				if($r->reviewstatus == 1) $outArr[4] = 'OPEN';
				elseif($r->reviewstatus == 2) $outArr[4] = 'PENDING';
				elseif($r->reviewstatus == 3) $outArr[4] = 'CLOSED';
				$outArr[5] = ($r->appliedstatus?"APPLIED":"NOT APPLIED");
				if($this->display == 1) $outArr[6] = $r->username;
				else  $outArr[6] = $r->externaleditor.($r->username?' ('.$r->username.')':'');
				if($this->display == 1){
					$outArr[7] = $r->initialtimestamp;
					if($r->fieldname == 'footprintwkt' && $this->fieldNameFilter != 'footprintwkt') continue;
					$outArr[8] = $r->fieldname;
					$outArr[9] = $r->fieldvalueold;
					$outArr[10] = $r->fieldvaluenew;
					fputcsv($outFH, $outArr);
				}
				else{
					$outArr[7] = $r->initialtimestamp.($r->externaltimestamp?' ('.$r->externaltimestamp.')':'');
					$oldValueArr = json_decode($r->oldvalues,true);
					$newValueArr = json_decode($r->newvalues,true);
					foreach($oldValueArr as $fieldName => $oldValue){
						$outArr[8] = $fieldName;
						$outArr[9] = $oldValue;
						$outArr[10] = $newValueArr[$fieldName];
						fputcsv($outFH, $outArr);
					}
				}
			}
			$rs->free();
			fclose($outFH);
		}
		return $status;
	}

	private function getFullOcedidStr($idStr){
		$ocedidArr = array();
		if($idStr){
			$sql = 'SELECT e.ocedid FROM omoccuredits e INNER JOIN omoccuredits e2 ON e.occid = e2.occid AND e.initialtimestamp = e2.initialtimestamp WHERE e2.ocedid IN('.$idStr.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$ocedidArr[] = $r->ocedid;
			}
			$rs->free();
		}
		return implode(',',$ocedidArr);
	}

	//Setters, getters, misc functions
	public function setDisplay($d){
		if(is_numeric($d)){
			$this->display = $d;
		}
	}

	public function setAppliedStatusFilter($status){
		if(is_numeric($status)){
			$this->appliedStatusFilter = $status;
		}
	}

	public function setReviewStatusFilter($status){
		if(preg_match('/^[,\d]+$/', $status)){
			$this->reviewStatusFilter = $status;
		}
	}

	public function setFieldNameFilter($f){
		$this->fieldNameFilter = $f;
	}

	public function setEditorFilter($f){
		$this->editorFilter = $this->cleanInStr($f);
	}

	public function setQueryOccidFilter($num){
		if(is_numeric($num)){
			$this->queryOccidFilter = $num;
		}
	}

	public function setStartDateFilter($d){
		if(preg_match('/^[\d-]+$/', $d)){
			$this->startDateFilter = $d;
		}
	}

	public function setEndDateFilter($d){
		if(preg_match('/^[\d-]+$/', $d)){
			$this->endDateFilter = $d;
		}
	}

	public function setPageNumber($num){
		if(is_numeric($num)){
			$this->pageNumber = $num;
		}
	}

	public function setLimitNumber($limit){
		if(is_numeric($limit)){
			$this->limitNumber = $limit;
		}
	}

	public function getObsUid(){
		return $this->obsUid;
	}

	public function getFieldList(){
		$retArr = array();
		$sql = 'SELECT DISTINCT e.fieldName FROM omoccurrences o INNER JOIN omoccuredits e ON o.occid = e.occid WHERE (o.collid = '.$this->collid.') ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->fieldName;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	public function getEditorList(){
		$retArr = array();
		$uidArr = array();
		if($this->display == 1){
			$sql = 'SELECT DISTINCT e.uid FROM omoccuredits e INNER JOIN omoccurrences o ON e.occid = o.occid WHERE (o.collid = '.$this->collid.') ';
			if($this->obsUid) $sql .= 'AND (o.observeruid = '.$this->obsUid.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$uidArr[] = $r->uid;
			}
			$rs->free();
		}
		else{
			$sql = 'SELECT DISTINCT IFNULL(r.uid,r.externaleditor) as id FROM omoccurrevisions r INNER JOIN omoccurrences o ON r.occid = o.occid WHERE (o.collid = '.$this->collid.') ';
			if($this->obsUid) $sql .= 'AND (o.observeruid = '.$this->obsUid.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if(is_numeric($r->id)) $uidArr[] = $r->uid;
				else $retArr[$r->id] = $r->id;
			}
			$rs->free();
		}
		if($uidArr){
			$sql = 'SELECT uid, CONCAT_WS(", ", lastname, firstname) AS name FROM users WHERE uid IN('.implode(',',$uidArr).') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->uid] = $r->name;
			}
			$rs->free();
		}
		asort($retArr);
		return $retArr;
	}

	public function hasRevisionRecords(){
		$status = false;
		$sql = 'SELECT orid FROM omoccurrevisions LIMIT 1';
		$result = $this->conn->query($sql);
		if($row = $result->fetch_object()){
			$status = true;
		}
		$result->free();
		return $status;
	}
}
?>