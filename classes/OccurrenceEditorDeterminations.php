<?php
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/classes/OccurrenceEditorDeterminations.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/classes/OccurrenceEditorDeterminations.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/classes/OccurrenceEditorDeterminations.en.php');

class OccurrenceEditorDeterminations extends OccurrenceEditorManager{

	public function __construct(){
 		parent::__construct();
	}

	public function __destruct(){
 		parent::__destruct();
	}

	public function getDetMap($identBy, $dateIdent, $sciName){
		$retArr = array();
		$hasCurrent = 0;
		$sql = 'SELECT detid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, identificationQualifier, '.
			'iscurrent, printqueue, appliedstatus, identificationReferences, identificationRemarks, sortsequence '.
			'FROM omoccurdeterminations '.
			'WHERE (occid = '.$this->occid.') ORDER BY iscurrent DESC, sortsequence';
		//echo "<div>".$sql."</div>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$detId = $row->detid;
			$retArr[$detId]["identifiedby"] = $this->cleanOutStr($row->identifiedBy);
			$retArr[$detId]["dateidentified"] = $this->cleanOutStr($row->dateIdentified);
			$retArr[$detId]["sciname"] = $this->cleanOutStr($row->sciname);
			$retArr[$detId]["scientificnameauthorship"] = $this->cleanOutStr($row->scientificNameAuthorship);
			$retArr[$detId]["identificationqualifier"] = $this->cleanOutStr($row->identificationQualifier);
			if($row->iscurrent == 1) $hasCurrent = 1;
			$retArr[$detId]['iscurrent'] = $row->iscurrent;
			$retArr[$detId]['printqueue'] = $row->printqueue;
			$retArr[$detId]["appliedstatus"] = $row->appliedstatus;
			$retArr[$detId]["identificationreferences"] = $this->cleanOutStr($row->identificationReferences);
			$retArr[$detId]["identificationremarks"] = $this->cleanOutStr($row->identificationRemarks);
			$retArr[$detId]["sortsequence"] = $row->sortsequence;
		}
		$result->free();
		if(!$hasCurrent){
			//Try to guess which is current
			foreach($retArr as $detId => $detArr){
				if($detArr['identifiedby'] == $identBy && $detArr['dateidentified'] == $dateIdent && $detArr['sciname'] == $sciName){
					$retArr[$detId]["iscurrent"] = "1";
					break;
				}
			}
		}

		return $retArr;
	}

	public function addDetermination($detArr, $isEditor){
		global $LANG;
		$status = $LANG['DET_SUCCESS'];
		if(!$this->occid) return $LANG['ERROR_OCCID_NULL'];
		if(!$isEditor || $isEditor == 4) return $LANG['ERROR_LACK_PERM'];
		$isCurrent = 0;
		if(!array_key_exists('makecurrent',$detArr)) $detArr['makecurrent'] = 0;
		if(!array_key_exists('printqueue',$detArr)) $detArr['printqueue'] = 0;
		if($detArr['makecurrent'] == 1 && $isEditor < 3){
			$isCurrent = 1;
		}
		if($isEditor == 3) $status = $LANG['DET_ADDED_PENDING'];
		$sortSeq = 1;
		if(preg_match('/([1,2]{1}\d{3})/',$detArr['dateidentified'],$matches)){
			$sortSeq = date('Y')+1-$matches[1];
		}
		if($isCurrent){
			//Set all dets for this specimen to not current
			$sqlSetCur1 = 'UPDATE omoccurdeterminations SET iscurrent = 0 WHERE appliedstatus = 1 AND occid = '.$this->occid;
			if(!$this->conn->query($sqlSetCur1)){
				$status = $LANG['ERROR_DETS_NOT_CURRENT'].': '.$this->conn->error;
				//$status .= '; '.$sqlSetCur1;
			}
		}
		//Load new determination into omoccurdeterminations
		$sciname = $this->cleanInStr($detArr['sciname']);
		$notes = $this->cleanInStr($detArr['identificationremarks']);
		if($isEditor==3 && is_numeric($detArr['confidenceranking'])) {
			$notes .= ($notes?'; ':'').'ConfidenceRanking: '.$detArr['confidenceranking'];
		}
		$guid = UuidFactory::getUuidV4();
		$sql = 'INSERT INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, '.
			'identificationQualifier, iscurrent, printqueue, appliedStatus, identificationReferences, identificationRemarks, recordID, sortsequence) '.
			'VALUES ('.$this->occid.',"'.$this->cleanInStr($detArr['identifiedby']).'","'.$this->cleanInStr($detArr['dateidentified']).'","'.
			$sciname.'",'.($detArr['scientificnameauthorship']?'"'.$this->cleanInStr($detArr['scientificnameauthorship']).'"':'NULL').','.
			($detArr['identificationqualifier']?'"'.$this->cleanInStr($detArr['identificationqualifier']).'"':'NULL').','.
			$detArr['makecurrent'].','.$detArr['printqueue'].','.($isEditor==3?0:1).','.
			($detArr['identificationreferences']?'"'.$this->cleanInStr($detArr['identificationreferences']).'"':'NULL').','.
			($notes?'"'.$notes.'"':'NULL').',"'.$guid.'",'.$sortSeq.')';
		if($this->conn->query($sql)){
			//If is current, move old determination from omoccurrences to omoccurdeterminations and then load new record into omoccurrences
			if($isCurrent){
				//If determination is already in omoccurdeterminations, INSERT will fail move omoccurrences determination to  table
				$guid = UuidFactory::getUuidV4();
				$sqlInsert = 'INSERT INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, '.
					'identificationQualifier, identificationReferences, identificationRemarks, recordID, sortsequence) '.
					'SELECT occid, IFNULL(identifiedby,"unknown") AS idby, IFNULL(dateidentified,"s.d.") AS di, '.
					'sciname, scientificnameauthorship, identificationqualifier, identificationreferences, identificationremarks, "'.$guid.'", 10 AS sortseq '.
					'FROM omoccurrences WHERE (occid = '.$this->occid.') AND (identifiedBy IS NOT NULL OR dateIdentified IS NOT NULL OR sciname IS NOT NULL)';
				$this->conn->query($sqlInsert);
				$tidToAdd = $detArr['tidtoadd'];
				if($tidToAdd && !is_numeric($tidToAdd)) $tidToAdd = 0;

				//Check to see if taxon has a locality security protection (rare, threatened, or sensitive species)
				$sStatus = 0;
				if($tidToAdd){
					$sqlSs = 'SELECT securitystatus FROM taxa WHERE (tid = '.$tidToAdd.')';
					$rsSs = $this->conn->query($sqlSs);
					if($rSs = $rsSs->fetch_object()){
						if($rSs->securitystatus == 1) $sStatus = 1;
					}
					$rsSs->free();
					if(!$sStatus){
						$sql2 = 'SELECT c.clid '.
							'FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid '.
							'INNER JOIN taxstatus ts1 ON cl.tid = ts1.tid '.
							'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
							'INNER JOIN omoccurrences o ON c.locality = o.stateprovince '.
							'WHERE c.type = "rarespp" AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 '.
							'AND (ts2.tid = '.$tidToAdd.') AND (o.occid = '.$this->occid.')';
						$rsSs2 = $this->conn->query($sql2);
						if($rsSs2->num_rows){
							$sStatus = 1;
						}
						$rsSs2->free();
					}
				}

				//Load new determination into omoccurrences table
				$sqlNewDet = 'UPDATE omoccurrences '.
					'SET family = '.($detArr['family']?'"'.$this->cleanInStr($detArr['family']).'"':'NULL').','.
					'sciname = "'.$sciname.'",genus = NULL, specificEpithet = NULL, taxonRank = NULL, infraspecificepithet = NULL,'.
					'scientificNameAuthorship = '.($detArr['scientificnameauthorship']?'"'.$this->cleanInStr($detArr['scientificnameauthorship']).'"':'NULL').','.
					'tidinterpreted = '.($tidToAdd?$tidToAdd:'NULL').', localitysecurity = '.$sStatus;
				if($detArr['identifiedby'] != 'Nomenclatural Adjustment'){
					$sqlNewDet .= ',identifiedBy = "'.$this->cleanInStr($detArr['identifiedby']).'", dateIdentified = "'.$this->cleanInStr($detArr['dateidentified']).'",'.
						'identificationQualifier = '.($detArr['identificationqualifier']?'"'.$this->cleanInStr($detArr['identificationqualifier']).'"':'NULL').','.
						'identificationReferences = '.($detArr['identificationreferences']?'"'.$this->cleanInStr($detArr['identificationreferences']).'"':'NULL').','.
						'identificationRemarks = '.($detArr['identificationremarks']?'"'.$this->cleanInStr($detArr['identificationremarks']).'"':'NULL');
				}
				$sqlNewDet .= ' WHERE (occid = '.$this->occid.')';
				$this->conn->query($sqlNewDet);
				//Add identification confidence
				if(isset($detArr['confidenceranking'])){
					$idStatus = $this->editIdentificationRanking($detArr['confidenceranking'],'');
					if($idStatus) $status .= '; '.$idStatus;
				}
				//Remap images
				$sql = 'UPDATE images SET tid = '.($tidToAdd?$tidToAdd:'NULL').' WHERE (occid = '.$this->occid.')';
				if(!$this->conn->query($sql)){
					$status = $LANG['ERROR_ADDED_FAILED_IMAGES'];
					$status .= ': '.$this->conn->error;
				}
			}
		}
		else{
			$status = $LANG['ERROR_FAILED_ADD'].': '.$this->conn->error;
		}
		return $status;
	}

	public function editDetermination($detArr){
		global $LANG;
		if(!array_key_exists('printqueue',$detArr)) $detArr['printqueue'] = 0;
		$status = "Determination editted successfully!";
		//Update determination table
		$sql = 'UPDATE omoccurdeterminations '.
			'SET identifiedBy = "'.$this->cleanInStr($detArr['identifiedby']).'", '.
			'dateIdentified = "'.$this->cleanInStr($detArr['dateidentified']).'", '.
			'sciname = "'.$this->cleanInStr($detArr['sciname']).'", '.
			'scientificNameAuthorship = '.($detArr['scientificnameauthorship']?'"'.$this->cleanInStr($detArr['scientificnameauthorship']).'"':'NULL').','.
			'identificationQualifier = '.($detArr['identificationqualifier']?'"'.$this->cleanInStr($detArr['identificationqualifier']).'"':'NULL').','.
			'identificationReferences = '.($detArr['identificationreferences']?'"'.$this->cleanInStr($detArr['identificationreferences']).'"':'NULL').','.
			'identificationRemarks = '.($detArr['identificationremarks']?'"'.$this->cleanInStr($detArr['identificationremarks']).'"':'NULL').','.
			'sortsequence = '.($detArr['sortsequence']?$detArr['sortsequence']:'10').','.
			'printqueue = '.($detArr['printqueue']?$detArr['printqueue']:'NULL').' '.
			'WHERE (detid = '.$detArr['detid'].')';
		if(!$this->conn->query($sql)){
			$status = $LANG['ERROR_FAILED_EDIT'].': '.$this->conn->error;
		}
		return $status;
	}

	public function deleteDetermination($detId){
		global $LANG;
		$status = $LANG['DET_DEL_SUCCESS'];
		$isCurrent = 0;
		$occid = 0;

		$sql = 'SELECT occid, identifiedBy, dateIdentified, family, sciname, scientificNameAuthorship, tidInterpreted, identificationQualifier, isCurrent, printQueue,
			appliedStatus, detType, identificationReferences, identificationRemarks, taxonRemarks, sortSequence
			FROM omoccurdeterminations WHERE detid = '.$detId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$isCurrent = $r['isCurrent'];
			$occid = $r['occid'];
		}
		$rs->free();

		if($isCurrent){
			$prevDetId = 0;
			$sql2 = 'SELECT detid FROM omoccurdeterminations WHERE occid = '.$occid.' AND detid <> '.$detId.' ORDER BY detid DESC LIMIT 1 ';
			$rs = $this->conn->query($sql2);
			if($r = $rs->fetch_object()){
				$prevDetId = $r->detid;
			}
			if($prevDetId) $this->applyDetermination($prevDetId, 1);
		}

		$sql = 'DELETE FROM omoccurdeterminations WHERE (detid = '.$detId.')';
		if(!$this->conn->query($sql)){
			$status = $LANG['DET_DEL_FAIL'].': '.$this->conn->error;
		}

		return $status;
	}

	public function applyDetermination($detId, $makeCurrent){
		global $LANG;
		$statusStr = $LANG['DET_APPLIED'];
		//Get ConfidenceRanking value
		$iqStr = '';
		$sqlcr = 'SELECT identificationremarks FROM omoccurdeterminations WHERE detid = '.$detId;
		$rscr = $this->conn->query($sqlcr);
		if($rcr = $rscr->fetch_object()){
			$iqStr = $rcr->identificationremarks;
			if(preg_match('/ConfidenceRanking: (\d{1,2})/',$iqStr,$m)){
				if($makeCurrent) $this->editIdentificationRanking($m[1],'');
				$iqStr = trim(str_replace('ConfidenceRanking: '.$m[1],'',$iqStr),' ;');
			}
		}
		$rscr->free();

		//Update applied status of det
		$sql = 'UPDATE omoccurdeterminations '.
			'SET appliedstatus = 1, iscurrent = '.$makeCurrent.', '.
			'identificationremarks = '.($iqStr?'"'.$this->cleanInStr($iqStr).'"':'NULL').' WHERE detid = '.$detId;
		if(!$this->conn->query($sql)){
			$statusStr = $LANG['ERROR_ATTEMPT_DET'].': '.$this->conn->error;
		}
		if($makeCurrent){
			$this->makeDeterminationCurrent($detId);
		}
		return $statusStr;
	}

	public function makeDeterminationCurrent($detId){
		global $LANG;
		$status = $LANG['DET_NOW_CURRENT'];
		//Make sure determination data within omoccurrences is in omoccurdeterminations. If already there, INSERT will fail and nothing lost
		$guid = UuidFactory::getUuidV4();
		$sqlInsert = 'INSERT INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, '.
			'identificationQualifier, identificationReferences, identificationRemarks, recordID, sortsequence) '.
			'SELECT occid, IFNULL(identifiedby,"unknown") AS idby, IFNULL(dateidentified,"s.d.") AS iddate, sciname, scientificnameauthorship, '.
			'identificationqualifier, identificationreferences, identificationremarks, "'.$guid.'", 10 AS sortseq '.
			'FROM omoccurrences WHERE (occid = '.$this->occid.') AND (identifiedBy IS NOT NULL OR dateIdentified IS NOT NULL OR sciname IS NOT NULL)';
		$this->conn->query($sqlInsert);
		//Update omoccurrences to reflect this determination
		$tid = 0;
		$sStatus = 0;
		$family = '';
		$sqlTid = 'SELECT t.tid, t.securitystatus, ts.family '.
			'FROM omoccurdeterminations d INNER JOIN taxa t ON d.sciname = t.sciname '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (d.detid = '.$detId.') AND (taxauthid = 1)';
		$rs = $this->conn->query($sqlTid);
		if($r = $rs->fetch_object()){
			$tid = $r->tid;
			$family = $r->family;
			if($r->securitystatus == 1) $sStatus = 1;
		}
		$rs->free();
		if(!$sStatus && $tid){
			$sql2 = 'SELECT c.clid '.
				'FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid '.
				'INNER JOIN taxstatus ts1 ON cl.tid = ts1.tid '.
				'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN omoccurrences o ON c.locality = o.stateprovince '.
				'WHERE c.type = "rarespp" AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 '.
				'AND (ts2.tid = '.$tid.') AND (o.occid = '.$this->occid.')';
			//echo $sql; exit;
			$rsSs2 = $this->conn->query($sql2);
			if($rsSs2->num_rows){
				$sStatus = 1;
			}
			$rsSs2->free();
		}

		$sqlNewDet = 'UPDATE omoccurrences o INNER JOIN omoccurdeterminations d ON o.occid = d.occid '.
			'SET o.identifiedBy = d.identifiedBy, o.dateIdentified = d.dateIdentified,o.family = '.($family?'"'.$family.'"':'NULL').','.
			'o.sciname = d.sciname,o.genus = NULL,o.specificEpithet = NULL,o.taxonRank = NULL,o.infraspecificepithet = NULL,o.scientificname = NULL,'.
			'o.scientificNameAuthorship = d.scientificnameauthorship,o.identificationQualifier = d.identificationqualifier,'.
			'o.identificationReferences = d.identificationreferences,o.identificationRemarks = d.identificationremarks,'.
			'o.tidinterpreted = '.($tid?$tid:'NULL').', o.localitysecurity = '.$sStatus.
			' WHERE (detid = '.$detId.')';
		//echo "<div>".$sqlNewDet."</div>";
		$this->conn->query($sqlNewDet);
		//Set all dets for this specimen to not current
		$sqlSetCur1 = 'UPDATE omoccurdeterminations SET iscurrent = 0 WHERE occid = '.$this->occid;
		if(!$this->conn->query($sqlSetCur1)){
			$status = $LANG['ERROR_DETS_NOT_CURRENT'].': '.$this->conn->error;
			//$status .= '; '.$sqlSetCur1;
		}
		//Set targeted det to current
		$sqlSetCur2 = 'UPDATE omoccurdeterminations SET iscurrent = 1 WHERE detid = '.$detId;
		if(!$this->conn->query($sqlSetCur2)){
			$status = $LANG['ERROR_SETTING_CURRENT'].': '.$this->conn->error;
			//$status .= '; '.$sqlSetCur2;
		}

		if($tid){
			$sql = 'UPDATE images SET tid = '.$tid.' WHERE (occid = '.$this->occid.')';
			//echo $sql;
			$this->conn->query($sql);
		}
		else{
			$status = $LANG['ERROR_TAX_THESAURUS'];
		}
	}

	public function addNomAdjustment($detArr,$isEditor){
		$sql = 'SELECT identificationQualifier FROM omoccurrences WHERE occid = '.$this->occid;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$detArr['identificationqualifier'] = $r->identificationQualifier;
		}
		$rs->free();
		$detArr['identifiedby'] = 'Nomenclatural Adjustment';
		$detArr['dateidentified'] = date('F').' '.date('j').', '.date('Y');
		$this->addDetermination($detArr, $isEditor);
	}

	public function getNewDetItem($catNum, $sciName, $allCatNum = 0){
		$retArr = array();
		if($catNum || $sciName){
			$sql = 'SELECT o.occid, o.catalogNumber, o.otherCatalogNumbers, o.sciname, CONCAT_WS(" ", o.recordedby, IFNULL(o.recordnumber, o.eventdate)) AS collector, '.
				'CONCAT_WS(", ", o.country, o.stateprovince, o.county, o.locality) AS locality ';
			$catNumArr = explode(',',$catNum);
			if($catNum){
				foreach($catNumArr as $k => $u){
					$u = trim($u);
					if($u) $catNumArr[$k] = $this->cleanInStr($u);
					else unset($catNumArr[$k]);
				}
				if($allCatNum){
					$sql .= ', i.identifierValue FROM omoccurrences o LEFT JOIN omoccuridentifiers i ON o.occid = i.occid ';
				}
				else{
					$sql .= 'FROM omoccurrences o ';
				}
				$catNumStr = implode('","',$catNumArr);
				$sql .= 'WHERE o.collid = '.$this->collId.' AND (o.catalogNumber IN("'.$catNumStr.'") ';
				if($allCatNum){
					$sql .= 'OR o.otherCatalogNumbers IN("'.$catNumStr.'") OR i.identifierValue IN("'.$catNumStr.'") ';
				}
				$sql .= ') ';
			}
			elseif($sciName){
				$sql .= 'FROM omoccurrences o WHERE o.collid = '.$this->collId.' AND o.sciname = "'.$this->cleanInStr($sciName).'" ';
			}
			$sql .= 'LIMIT 400 ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if(!array_key_exists($r->occid, $retArr)){
					$retArr[$r->occid]['sn'] = $r->sciname;
					$retArr[$r->occid]['coll'] = $r->collector;
					$loc = $r->locality;
					if(strlen($loc) > 500) $loc = substr($loc,400);
					$retArr[$r->occid]['loc'] = $loc;
					$cn = $r->catalogNumber;
					if($r->otherCatalogNumbers){
						if(!$cn || in_array($r->otherCatalogNumbers, $catNumArr)) $cn = $r->otherCatalogNumbers;
					}
					$retArr[$r->occid]['cn'] = $cn;
				}
				if(!empty($r->identifierValue)){
					if(!$retArr[$r->occid]['cn'] || in_array($r->identifierValue, $catNumArr)) $retArr[$r->occid]['cn'] = $r->identifierValue;
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getCollName(){
		return $this->collMap['collectionname'].' ('.$this->collMap['institutioncode'].($this->collMap['collectioncode']?':'.$this->collMap['collectioncode']:'').')';
	}
}
?>
