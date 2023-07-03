<?php
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/content/lang/classes/TaxonomyEditorManager.'.$LANG_TAG.'.php');

class TaxonomyEditorManager extends Manager{

	private $taxAuthId = 1;
	private $tid = 0;
	private $family;
	private $sciName;
	private $kingdomName;
	private $rankid = 0;
	private $rankName;
	private $unitInd1;
	private $unitName1;
	private $unitInd2;
	private $unitName2;
	private $unitInd3;
	private $unitName3;
	private $author;
	private $parentTid = 0;
	private $parentName;
	private $parentNameFull;
	private $source;
	private $notes;
	private $hierarchyArr;
	private $securityStatus;
	private $isAccepted = -1;			// 1 = accepted, 0 = not accepted, -1 = not assigned, -2 in conflict
	private $acceptedArr = Array();
	private $synonymArr = Array();
	private $langArr;

	function __construct($type = 'write') {
		parent::__construct(null,$type);
		$this->langArr = $GLOBALS['LANG'];
	}

	function __destruct(){
		parent::__destruct();
	}

	public function setTaxon(){
		$sqlTaxon = 'SELECT tid, rankid, sciname, unitind1, unitname1, '.
			'unitind2, unitname2, unitind3, unitname3, author, source, notes, securitystatus, initialtimestamp '.
			'FROM taxa '.
			'WHERE (tid = '.$this->tid.')';
		//echo $sqlTaxon;
		$rs = $this->conn->query($sqlTaxon);
		if($r = $rs->fetch_object()){
			$this->sciName = $r->sciname;
			$this->rankid = $r->rankid;
			$this->unitInd1 = $r->unitind1;
			$this->unitName1 = $r->unitname1;
			$this->unitInd2 = $r->unitind2;
			$this->unitName2 = $r->unitname2;
			$this->unitInd3 = $r->unitind3;
			$this->unitName3 = $r->unitname3;
			$this->author = $r->author;
			$this->source = $r->source;
			$this->notes = $r->notes;
			$this->securityStatus = $r->securitystatus;
		}
		$rs->free();

		if($this->sciName){
			$this->setRankName();
			$this->setHierarchy();

			//Deal with TaxaStatus table stuff
			$sqlTs = "SELECT ts.parenttid, ts.tidaccepted, ts.unacceptabilityreason, ".
				"ts.family, t.sciname, t.author, t.notes, ts.sortsequence ".
				"FROM taxstatus ts INNER JOIN taxa t ON ts.tidaccepted = t.tid ".
				"WHERE (ts.taxauthid = ".$this->taxAuthId.") AND (ts.tid = ".$this->tid.')';
			//echo $sqlTs;
			$rsTs = $this->conn->query($sqlTs);
			if($row = $rsTs->fetch_object()){
				$this->parentTid = $row->parenttid;
				$this->family = $row->family;

				do{
					$tidAccepted = $row->tidaccepted;
					if($this->tid == $tidAccepted){
						if($this->isAccepted == -1 || $this->isAccepted == 1){
							$this->isAccepted = 1;
						}
						else{
							$this->isAccepted = -2;
						}
					}
					else{
						if($this->isAccepted == -1 || $this->isAccepted == 0){
							$this->isAccepted = 0;
						}
						else{
							$this->isAccepted = -2;
						}
						$this->acceptedArr[$tidAccepted]["unacceptabilityreason"] = $row->unacceptabilityreason;
						$this->acceptedArr[$tidAccepted]["sciname"] = $row->sciname;
						$this->acceptedArr[$tidAccepted]["author"] = $row->author;
						$this->acceptedArr[$tidAccepted]["usagenotes"] = $row->notes;
						$this->acceptedArr[$tidAccepted]["sortsequence"] = $row->sortsequence;
					}
				}while($row = $rsTs->fetch_object());
			}
			else{
				//Name has become unlinked to taxstatus table, thus we need to remap
				//First, get parent tid
				$sqlPar = 'SELECT t.tid, ts.family '.
					'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
					'WHERE ts.taxauthid = '.$this->taxAuthId.' AND ';
				if($this->rankid > 220){
					//Species is parent
					$sqlPar .= 't.rankid = 220 AND t.unitName1 = "'.$this->unitName1.'" AND t.unitName2 = "'.$this->unitName2.'" ';
				}
				elseif($this->rankid > 180){
					//Genus is parent
					$sqlPar .= 't.rankid = 180 AND t.unitName1 = "'.$this->unitName1.'" ';
				}
				elseif($this->kingdomName){
					//Kingdom is parent
					$sqlPar .= 't.rankid = 10 AND t.unitName1 = "'.$this->kingdomName.'"';
				}
				else{
					//Organism is parent
					$sqlPar .= 't.rankid = 1 ';
				}
				$rsPar = $this->conn->query($sqlPar);
				if($rPar = $rsPar->fetch_object()){
					$sqlIns = 'INSERT INTO taxstatus(tid, tidaccepted, taxauthid, parenttid, family) '.
						'VALUES('.$this->tid.','.$this->tid.','.$this->taxAuthId.','.$rPar->tid.','.
						($rPar->family?'"'.$rPar->family.'"':'NULL').')';
					if($this->conn->query($sqlIns)){
						$this->parentTid = $rPar->tid;
						$this->family = $rPar->family;
						$this->isAccepted = 1;
					}
				}
				$rsPar->free();
			}
			$rsTs->free();

			if($this->isAccepted == 1) $this->setSynonyms();
			if($this->parentTid) $this->setParentName();
		}
	}

	private function setRankName(){
		if($this->rankid){
			$sql = 'SELECT rankname, kingdomname FROM taxonunits WHERE (rankid = '.$this->rankid.') ';
			//echo $sql;
			$rankArr = array();
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$rankArr[$r->kingdomname] = $r->rankname;
			}
			$rs->free();
			if($rankArr){
				$this->rankName = (array_key_exists($this->kingdomName,$rankArr)?$rankArr[$this->kingdomName]:current($rankArr));
			}
		}
	}

	private function setHierarchy(){
		unset($this->hierarchyArr);
		$this->hierarchyArr = array();
		$sql = 'SELECT parenttid FROM taxaenumtree WHERE (tid = '.$this->tid.') AND (taxauthid = '.$this->taxAuthId.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->hierarchyArr[] = $r->parenttid;
		}
		$rs->free();
		//Set kingdom name
		if($this->hierarchyArr){
			$sql2 = 'SELECT sciname FROM taxa WHERE (tid IN('.implode(',',$this->hierarchyArr).')) AND (rankid = 10)';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$this->kingdomName = $r2->sciname;
			}
			$rs2->free();
		}
	}

	private function setSynonyms(){
		$sql = "SELECT t.tid, t.sciname, t.author, ts.unacceptabilityreason, ts.notes, ts.sortsequence ".
			"FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.tid ".
			"WHERE (ts.taxauthid = ".$this->taxAuthId.") AND (ts.tid <> ts.tidaccepted) AND (ts.tidaccepted = ".$this->tid.") ".
			"ORDER BY ts.sortsequence,t.sciname";
		//echo $sql."<br>";
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->synonymArr[$r->tid]["sciname"] = $r->sciname;
			$this->synonymArr[$r->tid]["author"] = $r->author;
			$this->synonymArr[$r->tid]["unacceptabilityreason"] = $r->unacceptabilityreason;
			$this->synonymArr[$r->tid]["notes"] = $r->notes;
			$this->synonymArr[$r->tid]["sortsequence"] = $r->sortsequence;
		}
		$rs->free();
	}

	private function setParentName(){
		$sql = "SELECT t.sciname, t.author ".
			"FROM taxa t ".
			"WHERE (t.tid = ".$this->parentTid.")";
		//echo $sql."<br>";
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$this->parentNameFull = '<i>'.$r->sciname.'</i> '.$r->author;
			$this->parentName = $r->sciname;
		}
		$rs->free();
	}

	//Edit Functions
	public function submitTaxonEdits($postArr){
		$statusStr = '';
		$sql = 'UPDATE taxa SET '.
			'unitind1 = '.($postArr['unitind1']?'"'.$this->cleanInStr($postArr['unitind1']).'"':'NULL').', '.
			'unitname1 = "'.$this->cleanInStr($postArr['unitname1']).'",'.
			'unitind2 = '.($postArr['unitind2']?'"'.$this->cleanInStr($postArr['unitind2']).'"':'NULL').', '.
			'unitname2 = '.($postArr['unitname2']?'"'.$this->cleanInStr($postArr['unitname2']).'"':'NULL').', '.
			'unitind3 = '.($postArr['unitind3']?'"'.$this->cleanInStr($postArr['unitind3']).'"':'NULL').', '.
			'unitname3 = '.($postArr['unitname3']?'"'.$this->cleanInStr($postArr['unitname3']).'"':'NULL').', '.
			'author = "'.($postArr['author']?$this->cleanInStr($postArr['author']):'').'", '.
			'rankid = '.(is_numeric($postArr['rankid'])?$postArr['rankid']:'NULL').', '.
			'source = '.($postArr['source']?'"'.$this->cleanInStr($postArr['source']).'"':'NULL').', '.
			'notes = '.($postArr['notes']?'"'.$this->cleanInStr($postArr['notes']).'"':'NULL').', '.
			'securitystatus = '.(is_numeric($postArr['securitystatus'])?$postArr['securitystatus']:'0').', '.
			'modifiedUid = '.$GLOBALS['SYMB_UID'].', '.
			'modifiedTimeStamp = "'.date('Y-m-d H:i:s').'",'.
			'sciname = "'.$this->cleanInStr(($postArr["unitind1"]?$postArr["unitind1"]." ":"").
			$postArr["unitname1"].($postArr["unitind2"]?" ".$postArr["unitind2"]:"").
			($postArr["unitname2"]?" ".$postArr["unitname2"]:"").
			($postArr["unitind3"]?" ".$postArr["unitind3"]:"").
			($postArr["unitname3"]?" ".$postArr["unitname3"]:"")).'" '.
			'WHERE (tid = '.$this->tid.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$statusStr = (isset($this->langArr['ERROR_EDITING_TAXON'])?$this->langArr['ERROR_EDITING_TAXON']:'ERROR editing taxon').': '.$this->conn->error;
		}

		//If SecurityStatus was changed, set security status within omoccurrence table
		if($postArr['securitystatus'] != $_REQUEST['securitystatusstart']){
			if(is_numeric($postArr['securitystatus'])){
				$sql2 = 'UPDATE omoccurrences SET localitysecurity = '.$postArr['securitystatus'].' WHERE (tidinterpreted = '.$this->tid.') AND (localitySecurityReason IS NULL)';
				$this->conn->query($sql2);
			}
		}
		return $statusStr;
	}

	public function submitTaxStatusEdits($parentTid,$tidAccepted){
		$status = '';
		if(is_numeric($parentTid) && is_numeric($tidAccepted)){
			$this->setTaxon();
			$sql = 'UPDATE taxstatus '.
				'SET parenttid = '.$parentTid.' '.
				'WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$this->tid.') AND (tidaccepted = '.$tidAccepted.')';
			if($this->conn->query($sql)){
				$this->rebuildHierarchy();
			}
			else{
				$status = (isset($this->langArr['UNABLE_EDIT_TAX'])?$this->langArr['UNABLE_EDIT_TAX']:'Unable to edit taxonomic placement. SQL').': '.$sql;
			}
		}
		return $status;
	}

	public function submitSynonymEdits($targetTid, $tidAccepted, $unacceptabilityReason, $notes, $sortSeq){
		$statusStr = '';
		if(is_numeric($tidAccepted)){
			$sql = 'UPDATE taxstatus SET unacceptabilityReason = '.($unacceptabilityReason?'"'.$this->cleanInStr($unacceptabilityReason).'"':'NULL').', '.
				' notes = '.($notes?'"'.$this->cleanInStr($notes).'"':'NULL').', sortsequence = '.(is_numeric($sortSeq)?$sortSeq:'NULL').
				' WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$targetTid.') AND (tidaccepted = '.$tidAccepted.')';
			//echo $sql; exit();
			if(!$this->conn->query($sql)){
				$statusStr = (isset($this->langArr['ERROR_SYN_EDITS'])?$this->langArr['ERROR_SYN_EDITS']:'ERROR submitting synonym edits').': '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function submitAddAcceptedLink($tidAcc, $deleteOther = true){
		$family = "";$parentTid = 0;
		$statusStr = '';
		if(is_numeric($tidAcc)){
			$sqlFam = 'SELECT ts.family, ts.parenttid FROM taxstatus ts WHERE (ts.tid = '.$this->tid.') AND (ts.taxauthid = '.$this->taxAuthId.')';
			$rs = $this->conn->query($sqlFam);
			if($row = $rs->fetch_object()){
				$family = $row->family;
				$parentTid = $row->parenttid;
			}
			$rs->free();

			if($deleteOther){
				$sqlDel = "DELETE FROM taxstatus WHERE (tid = ".$this->tid.") AND (taxauthid = ".$this->taxAuthId.')';
				$this->conn->query($sqlDel);
			}
			$sql = 'INSERT INTO taxstatus (tid,tidaccepted,taxauthid,family,parenttid,modifiedUid) '.
				'VALUES ('.$this->tid.', '.$tidAcc.', '.$this->taxAuthId.','.($family?'"'.$family.'"':"NULL").','.$parentTid.','.$GLOBALS['SYMB_UID'].') ';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = (isset($this->langArr['ERROR_ADD_LINK'])?$this->langArr['ERROR_ADD_LINK']:'ERROR adding accepted link').': '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function removeAcceptedLink($tidAccepted){
		$statusStr = '';
		if(is_numeric($tidAccepted)){
			$sql = 'DELETE FROM taxstatus WHERE (tid = '.$this->tid.') AND (tidaccepted = '.$tidAccepted.') AND (taxauthid = '.$this->taxAuthId.')';
			if(!$this->conn->query($sql)){
				$statusStr = (isset($this->langArr['ERROR_REMOVING_LINK'])?$this->langArr['ERROR_REMOVING_LINK']:'ERROR removing tidAccepted link').': '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function submitChangeToAccepted($tid,$tidAccepted,$switchAcceptance = true){
		$statusStr = '';
		if(is_numeric($tid)){
			$sql = 'UPDATE taxstatus SET tidaccepted = '.$tid.' WHERE (tid = '.$tid.') AND (taxauthid = '.$this->taxAuthId.')';
			while(!$this->conn->query($sql)){
				if(stripos($statusStr,'duplicate entry') !== false){
					if(!$this->conn->query('DELETE FROM taxstatus WHERE (tid = '.$tid.') AND (taxauthid = '.$this->taxAuthId.') LIMIT 1')){
						break;
					}
				}
				else{
					break;
				}
			}

			if($switchAcceptance && is_numeric($tidAccepted)){
				$sqlSwitch = 'UPDATE taxstatus SET tidaccepted = '.$tid.' WHERE (tidaccepted = '.$tidAccepted.') AND (taxauthid = '.$this->taxAuthId.')';
				if(!$this->conn->query($sqlSwitch)){
					$statusStr = (isset($this->langArr['ERROR_CHANGING_ACCEPTED'])?$this->langArr['ERROR_CHANGING_ACCEPTED']:'ERROR changing to accepted').': '.$this->conn->error;
				}
				$this->updateDependentData($tidAccepted,$tid);
			}
		}
		return $statusStr;
	}

	public function submitChangeToNotAccepted($tid,$tidAccepted,$reason,$notes){
		$status = '';
		if(is_numeric($tid) && is_numeric($tidAccepted)){
			//Change subject taxon to Not Accepted
			$reason = $this->cleanInStr($reason);
			$notes = $this->cleanInStr($notes);
			$sql = 'UPDATE taxstatus '.
				'SET tidaccepted = '.$tidAccepted.', unacceptabilityreason = '.($reason?'"'.$reason.'"':'NULL').', notes = '.($notes?'"'.$notes.'"':'NULL').' '.
				'WHERE (tid = '.$tid.') AND (taxauthid = '.$this->taxAuthId.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = (isset($this->langArr['ERROR_SWITCH_ACCEPT'])?$this->langArr['ERROR_SWITCH_ACCEPT']:'ERROR: unable to switch acceptance').'; '.$this->conn->error;
				$status .= '<br/>SQL: '.$sql;
			}
			else{
				//Switch synonyms of subject to Accepted Taxon
				$sqlSyns = 'UPDATE taxstatus SET tidaccepted = '.$tidAccepted.' WHERE (tidaccepted = '.$tid.') AND (taxauthid = '.$this->taxAuthId.')';
				if(!$this->conn->query($sqlSyns)){
					$status = (isset($this->langArr['ERROR_LINK_SYNONYMS'])?$this->langArr['ERROR_LINK_SYNONYMS']:'ERROR: unable to transfer linked synonyms to accepted taxon').'; '.$this->conn->error;
				}

				$this->updateDependentData($tid,$tidAccepted);
			}
		}
		return $status;
	}

	private function updateDependentData($tid, $tidNew){
		//method to update descr, vernaculars,
		$this->conn->query('DELETE FROM kmdescr WHERE inherited IS NOT NULL AND (tid = '.$tid.')');
		$this->conn->query('UPDATE IGNORE kmdescr SET tid = '.$tidNew.' WHERE (tid = '.$tid.')');
		$this->conn->query('DELETE FROM kmdescr WHERE (tid = '.$tid.')');
		$this->resetCharStateInheritance($tidNew);

		$sqlVerns = 'UPDATE taxavernaculars SET tid = '.$tidNew.' WHERE (tid = '.$tid.')';
		$this->conn->query($sqlVerns);

		//$sqltd = 'UPDATE taxadescrblock tb LEFT JOIN (SELECT DISTINCT caption FROM taxadescrblock WHERE (tid = '.
		//	$tidNew.')) lj ON tb.caption = lj.caption '.
		//	'SET tid = '.$tidNew.' WHERE (tid = '.$tid.') AND lj.caption IS NULL';
		//$this->conn->query($sqltd);

		$sqltl = 'UPDATE taxalinks SET tid = '.$tidNew.' WHERE (tid = '.$tid.')';
		$this->conn->query($sqltl);
	}

	private function resetCharStateInheritance($tid){
		//set inheritance for target only
		$sqlAdd1 = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
			"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
			"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
			"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
			"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
			"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
			"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
			"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
			"WHERE (ts1.taxauthid = '.$this->taxAuthId.') AND (ts2.taxauthid = '.$this->taxAuthId.') AND (ts2.tid = ts2.tidaccepted) ".
			"AND (t2.tid = ".$tid.") And (d2.CID Is Null)";
		$this->conn->query($sqlAdd1);

		//Set inheritance for all children of target
		if($this->rankid == 140){
			$sqlAdd2a = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
				"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
				"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
				"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
				"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
				"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
				"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
				"WHERE (ts1.taxauthid = '.$this->taxAuthId.') AND (ts2.taxauthid = '.$this->taxAuthId.') AND (ts2.tid = ts2.tidaccepted) ".
				"AND (t2.RankId = 180) AND (t1.tid = ".$tid.") AND (d2.CID Is Null)";
			//echo $sqlAdd2a;
			$this->conn->query($sqlAdd2a);
			$sqlAdd2b = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
				"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
				"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
				"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
				"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
				"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
				"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
				"WHERE (ts1.taxauthid = '.$this->taxAuthId.') AND (ts2.taxauthid = '.$this->taxAuthId.') AND (ts2.family = '".
				$this->sciName."') AND (ts2.tid = ts2.tidaccepted) ".
				"AND (t2.RankId = 220) AND (d2.CID Is Null)";
			$this->conn->query($sqlAdd2b);
		}

		if($this->rankid > 140 && $this->rankid < 220){
			$sqlAdd3 = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
				"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
				"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
				"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
				"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
				"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
				"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
				"WHERE (ts1.taxauthid = '.$this->taxAuthId.') AND (ts2.taxauthid = '.$this->taxAuthId.') AND (ts2.tid = ts2.tidaccepted) ".
				"AND (t2.RankId = 220) AND (t1.tid = ".$tid.") AND (d2.CID Is Null)";
			//echo $sqlAdd2b;
			$this->conn->query($sqlAdd3);
		}
	}

	public function rebuildHierarchy($tid = 0){
		if(!$tid) $tid = $this->tid;
		if(!$this->rankid) $this->setTaxon();
		//Get parent array
		$parentArr = Array();
		$parCnt = 0;
		$targetTid = $tid;
		do{
			$sql1 = 'SELECT DISTINCT ts.parenttid '.
				'FROM taxstatus ts '.
				'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tid = '.$targetTid.')';
			//echo $sqlParents;
			$targetTid = 0;
			$rs1 = $this->conn->query($sql1);
			if($r1 = $rs1->fetch_object()){
				if($r1->parenttid){
					if(in_array($r1->parenttid,$parentArr)) break;
					$parentArr[] = $r1->parenttid;
					$targetTid = $r1->parenttid;
				}
			}
			$rs1->free();
			$parCnt++;
		}while($targetTid && $parCnt < 16);

		//Add hierarchy to taxaenumtree table
		$trueHierarchyStr = implode(",",array_reverse($parentArr));
		if($parentArr != $this->hierarchyArr){
			//Reset hierarchy for all children
			$branchTidArr = array($tid);
			$sql2 = 'SELECT DISTINCT tid FROM taxaenumtree WHERE parenttid = '.$tid;
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$branchTidArr[] = $r2->tid;
			}
			$rs2->free();
			if($this->hierarchyArr){
				//Delete hierarchy for this taxon AND hierachies for all children
				$sql2a = 'DELETE FROM taxaenumtree '.
					'WHERE parenttid IN('.implode(',',$this->hierarchyArr).') AND (tid IN ('.implode(',',$branchTidArr).')) '.
					'AND (taxauthid = '.$this->taxAuthId.') ';
				//echo $sql2a; exit;
				$this->conn->query($sql2a);
			}

			$sql3 = 'INSERT IGNORE INTO taxaenumtree(tid,parenttid,taxauthid) ';
			foreach($parentArr as $pid){
				//Reset hierarchy for children taxa
				$sql3a = $sql3.'SELECT DISTINCT tid,'.$pid.','.$this->taxAuthId.' FROM taxaenumtree WHERE parenttid = '.$tid;
				$this->conn->query($sql3a);
				//echo $sql3a.'<br/>';
				//Reset hierarchy for target taxon
				$sql3b = $sql3.'VALUES('.$tid.','.$pid.','.$this->taxAuthId.')';
				$this->conn->query($sql3b);
				//echo $sql3b.'<br/>';
			}
			$this->setHierarchy();
		}

		if($this->rankid > 140){
			//Update family in taxstatus table
			$newFam = '';
			$sqlFam1 = 'SELECT t.sciname FROM taxaenumtree e INNER JOIN taxa t ON e.parenttid = t.tid '.
				'WHERE (e.taxauthid = '.$this->taxAuthId.') AND (e.tid = '.$tid.') AND (t.rankid = 140)';
			$rsFam1 = $this->conn->query($sqlFam1);
			if($r1 = $rsFam1->fetch_object()){
				$newFam = $r1->sciname;
			}
			$rsFam1->free();

			//reset family of target taxon and all it's children
			$sql = 'UPDATE taxstatus ts INNER JOIN taxaenumtree e ON ts.tid = e.tid '.
				'SET ts.family = '.($newFam?'"'.$this->cleanInStr($newFam).'"':'NULL').' '.
				'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (e.taxauthid = '.$this->taxAuthId.') '.
				'AND ((ts.tid = '.$tid.') OR (e.parenttid = '.$tid.'))';
			if(!$this->conn->query($sql)){
				$this->errorMessage = (isset($this->langArr['ERROR_RESET_FAMILY'])?$this->langArr['ERROR_RESET_FAMILY']:'ERROR attempting to reset family string').': '.$this->conn->error;
				echo $this->errorMessage;
			}
		}
	}

	//Load Taxon functions
	public function loadNewName($dataArr){
		//Load new name into taxa table
		$tid = 0;
		$sqlTaxa = 'INSERT INTO taxa(sciname, author, rankid, unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, '.
			'source, notes, securitystatus, modifiedUid, modifiedTimeStamp) '.
			'VALUES ("'.$this->cleanInStr($dataArr['sciname']).'","'.
			($dataArr['author']?$this->cleanInStr($dataArr['author']):'').'",'.
			(isset($dataArr['rankid'])?$dataArr['rankid']:0).','.
			($dataArr['unitind1']?'"'.$this->cleanInStr($dataArr['unitind1']).'"':'NULL').',"'.
			$this->cleanInStr($dataArr['unitname1']).'",'.
			($dataArr['unitind2']?'"'.$this->cleanInStr($dataArr['unitind2']).'"':'NULL').','.
			($dataArr['unitname2']?'"'.$this->cleanInStr($dataArr['unitname2']).'"':'NULL').','.
			($dataArr['unitind3']?'"'.$this->cleanInStr($dataArr['unitind3']).'"':'NULL').','.
			($dataArr['unitname3']?'"'.$this->cleanInStr($dataArr['unitname3']).'"':'NULL').','.
			($dataArr['source']?'"'.$this->cleanInStr($dataArr['source']).'"':'NULL').','.
			($dataArr['notes']?'"'.$this->cleanInStr($dataArr['notes']).'"':'NULL').','.
			$this->cleanInStr($dataArr['securitystatus']).','.
			$GLOBALS['SYMB_UID'].',"'.date('Y-m-d H:i:s').'")';
		if($this->conn->query($sqlTaxa)){
			$tid = $this->conn->insert_id;
		 	//Load accepteance status into taxstatus table
			$tidAccepted = ($dataArr['acceptstatus']?$tid:$dataArr['tidaccepted']);
			$parTid = $this->cleanInStr($dataArr['parenttid']);
			if(!$parTid && $dataArr['rankid'] <= 10) $parTid = $tid;
			if(!$parTid && $dataArr['parentname']){
				$sqlPar = 'SELECT tid FROM taxa WHERE sciname = "'.$dataArr['parentname'].'"';
				$rsPar = $this->conn->query($sqlPar);
				if($rPar = $rsPar->fetch_object()){
					$parTid = $rPar->tid;
				}
				$rsPar->free();
			}
			if($parTid){
				//Get family from hierarchy
				$family = '';
				if($dataArr['rankid'] > 140){
					$sqlFam = 'SELECT t.sciname '.
						'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.parenttid '.
						'WHERE (t.tid = '.$parTid.' OR e.tid = '.$parTid.') AND t.rankid = 140 ';
					//echo $sqlFam; exit;
					$rsFam = $this->conn->query($sqlFam);
					if($r = $rsFam->fetch_object()){
						$family = $r->sciname;
					}
					$rsFam->free();
				}

				//Load new record into taxstatus table
				$sqlTaxStatus = 'INSERT INTO taxstatus(tid, tidaccepted, taxauthid, family, parenttid, unacceptabilityreason, modifiedUid) '.
					'VALUES ('.$tid.','.$tidAccepted.','.$this->taxAuthId.','.($family?'"'.$this->cleanInStr($family).'"':'NULL').','.
					$parTid.','.($dataArr["unacceptabilityreason"]?'"'.$this->cleanInStr($dataArr["unacceptabilityreason"]).'"':'NULL').','.$GLOBALS['SYMB_UID'].') ';
				//echo "sqlTaxStatus: ".$sqlTaxStatus;
				if(!$this->conn->query($sqlTaxStatus)){
					return (isset($this->langArr['ERROR_LOAD_TAXSTATUS'])?$this->langArr['ERROR_LOAD_TAXSTATUS']:'ERROR: Taxon loaded into taxa, but failed to load taxstatus').': '.$this->conn->error.'; '.$sqlTaxStatus;
				}

				//Load hierarchy into taxaenumtree table
				$sqlEnumTree = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid) '.
					'SELECT '.$tid.' as tid, parenttid, taxauthid FROM taxaenumtree WHERE tid = '.$parTid;
				if($this->conn->query($sqlEnumTree)){
					$sqlEnumTree2 = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid) '.
						'VALUES ('.$tid.','.$parTid.','.$this->taxAuthId.')';
					if(!$this->conn->query($sqlEnumTree2)){
						echo (isset($this->langArr['WARNING_TAXAENUMTREE2'])?$this->langArr['WARNING_TAXAENUMTREE2']:'WARNING: Taxon loaded into taxa, but failed to populate taxaenumtree(2)').': '.$this->conn->error;
					}
				}
				else{
					echo (isset($this->langArr['WARNING_TAXAENUMTREE'])?$this->langArr['WARNING_TAXAENUMTREE']:'WARNING: Taxon loaded into taxa, but failed to populate taxaenumtree').': '.$this->conn->error;
				}
			}
			else{
				return (isset($this->langArr['ERROR_MISSING_PARENTID'])?$this->langArr['ERROR_MISSING_PARENTID']:'ERROR loading taxon due to missing parentTid');
			}

			//Link new name to existing specimens and set locality secirity if needed
			$sql1 = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.TidInterpreted = t.tid ';
			if($dataArr['securitystatus'] == 1) $sql1 .= ',o.localitysecurity = 1 ';
			$sql1 .= 'WHERE (o.sciname = "'.$this->cleanInStr($dataArr["sciname"]).'") ';
			if(!$this->conn->query($sql1)){
				echo (isset($this->langArr['WARNING_OCCURRENCES_NOT UPDATED'])?$this->langArr['WARNING_OCCURRENCES_NOT']:'WARNING: Taxon loaded into taxa, but  occurrences must be updated with matching name').': '.$this->conn->error;
			}

			//Link occurrence images to the new name
			$occidArr = array();
			$sql2a = 'SELECT occid FROM omoccurrences WHERE (tidinterpreted = '.$tid.')';
			$rs2a = $this->conn->query($sql2a);
			while($r2a = $rs2a->fetch_object()){
				$occidArr[] = $r2a->occid;
			}
			$rs2a->free();

			if($occidArr){
				$sql2 = 'UPDATE images SET tid = '.$tid.' WHERE tid IS NULL AND occid IN('.implode(',',$occidArr).')';
				$this->conn->query($sql2);
				if(!$this->conn->query($sql2)){
					echo (isset($this->langArr['WARNING_UPDATE_IMAGES'])?$this->langArr['WARNING_UPDATE_IMAGES']:'WARNING: Taxon loaded into taxa, but occurrence images must be updated with matching name').': '.$this->conn->error;
				}
			}

			//Add their geopoints to omoccurgeoindex
			$sql3 = 'INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '.
				'SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,2), round(o.decimallongitude,2) '.
				'FROM omoccurrences o '.
				'WHERE (o.tidinterpreted '.$tid.') AND (o.decimallatitude between -90 and 90) AND (o.decimallongitude between -180 and 180) '.
				'AND (o.cultivationStatus IS NULL OR o.cultivationStatus = 0) AND (o.coordinateUncertaintyInMeters IS NULL OR o.coordinateUncertaintyInMeters < 10000) ';

			$this->conn->query($sql3);

			//Populate NULL kingdomName values
			$sql4 = 'UPDATE IGNORE taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid
				INNER JOIN taxa p ON e.parenttid = p.tid
				SET t.kingdomname = p.sciname
				WHERE p.rankid = 10 AND (t.kingdomname IS NULL or t.kingdomname = "")';
			$this->conn->query($sql4);
		}
		else{
			$this->errorMessage = (isset($this->langArr['ERROR_INSERT'])?$this->langArr['ERROR_INSERT']:'ERROR inserting new taxon').': '.$this->conn->error;
			//$this->errorMessage .= '; SQL = '.$sqlTaxa;
			return $this->errorMessage;
		}
		return $tid;
	}

	//Delete taxon functions
	public function verifyDeleteTaxon(){
		$retArr = array();

		//Children taxa
		$sql ='SELECT t.tid, t.sciname '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE ts.parenttid = '.$this->tid.' ORDER BY t.sciname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['child'][$r->tid] = $r->sciname;
		}
		$rs->free();

		//Synonym taxa
		$sql ='SELECT t.tid, t.sciname '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE ts.tidaccepted = '.$this->tid.' AND ts.tid <> ts.tidaccepted ORDER BY t.sciname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['syn'][$r->tid] = $r->sciname;
		}
		$rs->free();

		//Field images
		$sql ='SELECT COUNT(imgid) AS cnt FROM images WHERE tid = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['img'] = $r->cnt;
		}
		$rs->free();

		//Vernaculars
		$sql ='SELECT vernacularname FROM taxavernaculars WHERE tid = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['vern'][] = $r->vernacularname;
		}
		$rs->free();

		//Text Descriptions
		$sql ='SELECT tdbid,caption FROM taxadescrblock WHERE tid = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['tdesc'][$r->tdbid] = $r->caption;
		}
		$rs->free();

		//Occurrence records
		$sql ='SELECT occid FROM omoccurrences WHERE tidinterpreted = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['occur'][] = $r->occid;
		}
		$rs->free();

		//Occurrence determinations
		$sql ='SELECT occid FROM omoccurdeterminations WHERE tidinterpreted = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['dets'][] = $r->occid;
		}
		$rs->free();

		//Checklists and Vouchers
		$sql ='SELECT c.clid, c.name '.
			'FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid '.
			'WHERE cl.tid = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['cl'][$r->clid] = $r->name;
		}
		$rs->free();

		//Key descriptions
		$sql ='SELECT COUNT(*) AS cnt FROM kmdescr WHERE inherited IS NULL AND tid = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['kmdesc'] = $r->cnt;
		}
		$rs->free();

		//Taxon links
		$sql ='SELECT title FROM taxalinks WHERE tid = '.$this->tid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['link'][] = $r->title;
		}
		$rs->free();

		return $retArr;
	}

	public function transferResources($targetTid){
		$status = false;
		if(is_numeric($targetTid)){
			//Set occurrence and determination tids to NULL within delete function function below

			//Field images; specimen images set to null within delete function
			$sql ='UPDATE IGNORE images SET tid = '.$targetTid.' WHERE occid IS NULL AND tid = '.$this->tid;
			if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_TRANSFER_IMGS'])?$this->langArr['ERROR_TRANSFER_IMGS']:'ERROR transferring image links').' ('.$this->conn->error.')';

			//Vernaculars
			$sql ='UPDATE IGNORE taxavernaculars SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_TRANSFER_VERNACULARS'])?$this->langArr['ERROR_TRANSFER_VERNACULARS']:'ERROR transferring vernaculars').' ('.$this->conn->error.')';

			//Text Descriptions
			$sql ='UPDATE IGNORE taxadescrblock SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_TRANSFER_TAXADESC'])?$this->langArr['ERROR_TRANSFER_TAXADESC']:'ERROR transferring taxadescblocks').' ('.$this->conn->error.')';

			//Vouchers and checklists
			$sql ='UPDATE IGNORE fmchklsttaxalink SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_TRANSFER_CHECKLIST'])?$this->langArr['ERROR_TRANSFER_CHECKLIST']:'ERROR transferring checklist links').' ('.$this->conn->error.')';

			$sql ='DELETE FROM fmchklsttaxalink WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_TRANSFER_LCHECKLISTS'])?$this->langArr['ERROR_TRANSFER_LCHECKLISTS']:'ERROR deleting leftover checklist links').' ('.$this->conn->error.')';

			//Key descriptions
			$sql ='UPDATE IGNORE kmdescr SET tid = '.$targetTid.' WHERE inherited IS NULL AND tid = '.$this->tid;
			if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_TRANSFER_MORPHOLOGY'])?$this->langArr['ERROR_TRANSFER_MORPHOLOGY']:'ERROR transferring morphology for ID key').' ('.$this->conn->error.')';

			//Taxon links
			$sql ='UPDATE IGNORE taxalinks SET tid = '.$targetTid.' WHERE tid = '.$this->tid;
			if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_TRANSFER_TAXLINKS'])?$this->langArr['ERROR_TRANSFER_TAXLINKS']:'ERROR transferring taxa links').' ('.$this->conn->error.')';

			//Transfer children taxa
			$sql ='UPDATE taxstatus SET parenttid = '.$targetTid.' WHERE parenttid = '.$this->tid;
			if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_TRANSFER_CHILD'])?$this->langArr['ERROR_TRANSFER_CHILD']:'ERROR transferring child taxa').' ('.$this->conn->error.')';

			//Transfer Synonyms
			$sql ='UPDATE taxstatus SET tidaccepted = '.$targetTid.' WHERE tidaccepted = '.$this->tid;
			if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_TRANSFER_SYN'])?$this->langArr['ERROR_TRANSFER_SYN']:'ERROR transferring synonyms taxa').' ('.$this->conn->error.')';

			//Adjust taxaEnumTree index table
			$sql ='UPDATE taxaenumtree SET parenttid = '.$targetTid.' WHERE parenttid = '.$this->tid;
			if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_TRANSFER_TAXENUMTREE'])?$this->langArr['ERROR_TRANSFER_TAXENUMTREE']:'ERROR resetting taxaEnumTree index').' ('.$this->conn->error.')';

			$status = $this->deleteTaxon();
		}
		return $status;
	}

	public function deleteTaxon(){
		//Specimen images
		$sql ='UPDATE images SET tid = NULL WHERE occid IS NOT NULL AND tid = '.$this->tid;
		if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_SETTING_NULL'])?$this->langArr['ERROR_SETTING_NULL']:'ERROR setting tid to NULL for occurrence images in deleteTaxon method').' ('.$this->conn->error.')';

		/*
		$sql ='DELETE FROM images WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)) $this->warningArr[] = 'ERROR deleting remaining links in deleteTaxon method ('.$this->conn->error.')';
		*/

		//Vernaculars
		$sql ='DELETE FROM taxavernaculars WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_DEL_VERNACULARS'])?$this->langArr['ERROR_DEL_VERNACULARS']:'ERROR deleting vernaculars in deleteTaxon method').' ('.$this->conn->error.')';

		//Text Descriptions
		$sql ='DELETE FROM taxadescrblock WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_DEL_TAXDESC'])?$this->langArr['ERROR_DEL_TAXDESC']:'ERROR deleting taxa description blocks in deleteTaxon method').' ('.$this->conn->error.')';

		//Set occurrence and determination tids to NULL; collection can clean later using the taxonomic cleaning tools
		$sql = 'UPDATE omoccurrences SET tidinterpreted = NULL WHERE tidinterpreted = '.$this->tid;
		if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_TIDINT_NULL'])?$this->langArr['ERROR_TIDINT_NULL']:'ERROR setting tidinterpreted to NULL in deleteTaxon method').' ('.$this->conn->error.')';

		$sql ='UPDATE omoccurdeterminations SET tidinterpreted = NULL WHERE tidinterpreted = '.$this->tid;
		if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_TRANSFER_DETS'])?$this->langArr['ERROR_TRANSFER_DETS']:'ERROR transferring occurrence determination records').' ('.$this->conn->error.')';

		//Links to checklists
		$sql ='DELETE FROM fmchklsttaxalink WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_DEL_CHECKLIST'])?$this->langArr['ERROR_DEL_CHECKLIST']:'ERROR deleting checklist links in deleteTaxon method').' ('.$this->conn->error.')';

		//Key descriptions
		$sql ='DELETE FROM kmdescr WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_DEL_MORPHOLOGY'])?$this->langArr['ERROR_DEL_MORPHOLOGY']:'ERROR deleting morphology for ID Key in deleteTaxon method').' ('.$this->conn->error.')';

		//Taxon links
		$sql ='DELETE FROM taxalinks WHERE tid = '.$this->tid;
		if(!$this->conn->query($sql)) $this->warningArr[] = (isset($this->langArr['ERROR_DEL_TAXLINKS'])?$this->langArr['ERROR_DEL_TAXLINKS']:'ERROR deleting taxa links in deleteTaxon method').' ('.$this->conn->error.')';

		//Get taxon status details so if taxa removal fails, we can still initiate old name
		$taxStatusArr = array();
		$sqlTS = 'SELECT taxauthid, tidaccepted, parenttid, family, unacceptabilityreason, notes, sortsequence FROM taxstatus WHERE tid = '.$this->tid;
		$rs = $this->conn->query($sqlTS);
		while($r = $rs->fetch_object()){
			$taxStatusArr[$r->taxauthid]['tidaccepted'] = $r->tidaccepted;
			$taxStatusArr[$r->taxauthid]['parenttid'] = $r->parenttid;
			$taxStatusArr[$r->taxauthid]['family'] = $r->family;
			$taxStatusArr[$r->taxauthid]['unacceptabilityreason'] = $r->unacceptabilityreason;
			$taxStatusArr[$r->taxauthid]['notes'] = $r->notes;
			$taxStatusArr[$r->taxauthid]['sortsequence'] = $r->sortsequence;
		}
		$rs->free();

		//Delete taxon
		$status = true;
		$sql ='DELETE FROM taxstatus WHERE (tid = '.$this->tid.') OR (tidaccepted = '.$this->tid.')';
		if($this->conn->query($sql)){
			$sql ='DELETE FROM taxa WHERE (tid = '.$this->tid.')';
			if(!$this->conn->query($sql)){
				$this->errorMessage = (isset($this->langArr['ERROR_ATTEMPT_DELETE'])?$this->langArr['ERROR_ATTEMPT_DELETE']:'ERROR attempting to delete taxon').': '.$this->conn->error;
				$status = false;
				//Reinstate taxstatus record
				foreach($taxStatusArr as $taxAuthId => $tsArr){
					$tsNewSql = 'INSERT INTO taxstatus(tid,taxauthid,tidaccepted, parenttid, family, unacceptabilityreason, notes, sortsequence) '.
						'VALUES('.$this->tid.','.$taxAuthId.','.$taxStatusArr[$taxAuthId]['tidaccepted'].','.$taxStatusArr[$taxAuthId]['parenttid'].',"'.
						$taxStatusArr[$taxAuthId]['family'].'","'.$taxStatusArr[$taxAuthId]['unacceptabilityreason'].'","'.
						$taxStatusArr[$taxAuthId]['unacceptabilityreason'].'",'.$taxStatusArr[$taxAuthId]['sortsequence'].')';
					if(!$this->conn->query($tsNewSql)) $this->warningArr[] = (isset($this->langArr['ERROR_REESTABLISH'])?$this->langArr['ERROR_REESTABLISH']:'ERROR attempting to re-establish taxon').' ('.$this->conn->error.')';
				}
			}
		}
		else{
			$this->warningArr[] = (isset($this->langArr['ERROR_DEL_STATUS'])?$this->langArr['ERROR_DEL_STATUS']:'ERROR attempting to delete taxon status').': '.$this->conn->error;
		}

		return $status;
	}

	//setters and  getters
	public function getTargetName(){
		return $this->targetName;
	}

	public function setTid($tid){
		if(is_numeric($tid)){
			$this->tid = $tid;
		}
	}

	public function getTid(){
		return $this->tid;
	}

	public function setTaxAuthId($taid){
		if(is_numeric($taid)){
			$this->taxAuthId = $taid;
		}
	}

	public function getTaxAuthId(){
		return $this->taxAuthId;
	}

	public function getFamily(){
		return $this->family;
	}

	public function getSciName(){
		return $this->sciName;
	}

	public function getKingdomName(){
		return $this->kingdomName;
	}

	public function getRankId(){
		return $this->rankid;
	}

	public function getRankName(){
		return $this->rankName;
	}

	public function getUnitInd1(){
		return $this->unitInd1;
	}

	public function getUnitName1(){
		return $this->unitName1;
	}

	public function getUnitInd2(){
		return $this->unitInd2;
	}

	public function getUnitName2(){
		return $this->unitName2;
	}

	public function getUnitInd3(){
		return $this->unitInd3;
	}

	public function getUnitName3(){
		return $this->unitName3;
	}

	public function getAuthor(){
		return $this->author;
	}

	public function getParentTid(){
		return $this->parentTid;
	}

	public function getParentName(){
		return $this->parentName;
	}

	public function getParentNameFull(){
		return $this->parentNameFull;
	}

	public function getSource(){
		return $this->source;
	}

	public function getNotes(){
		return $this->notes;
	}

	public function getSecurityStatus(){
		return $this->securityStatus;
	}

	public function getIsAccepted(){
		return $this->isAccepted;
	}

	public function getAcceptedArr(){
		return $this->acceptedArr;
	}

	public function getSynonyms(){
		return $this->synonymArr;
	}

	//Misc methods for retrieving field data
	public function getTaxonomicThesaurusIds(){
		//For now, just return the default taxonomy (taxauthid = 1)
		$retArr = array();
		if($this->tid){
			$sql = 'SELECT ta.taxauthid, ta.name FROM taxauthority ta INNER JOIN taxstatus ts ON ta.taxauthid = ts.taxauthid '.
				'WHERE ta.isactive = 1 AND (ts.tid = ".$this->tid.") ORDER BY ta.taxauthid ';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$retArr[$row->taxauthid] = $row->name;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getRankArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT rankid, rankname FROM taxonunits ORDER BY rankid, rankname DESC';
		if($this->kingdomName) $sql = 'SELECT DISTINCT rankid, rankname FROM taxonunits WHERE (kingdomname = "'.$this->kingdomName.'") ORDER BY rankid, rankname DESC';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->rankid][] = $row->rankname;
		}
		$rs->free();
		if(!$retArr){
			$sql2 = 'SELECT DISTINCT rankid, rankname FROM taxonunits ORDER BY rankid, rankname DESC ';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$retArr[$r2->rankid][] = $r2->rankname;
			}
			$rs2->free();
		}
		return $retArr;
	}

	public function getHierarchyArr(){
		$retArr = array();
		if($this->hierarchyArr){
			$sql = 'SELECT t.tid, t.sciname, ts.parenttid, t.rankid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (t.tid IN('.implode(',',$this->hierarchyArr).')) '.
				'ORDER BY t.rankid, t.sciname ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			$nonRanked = array();
			while($r = $rs->fetch_object()){
				if($r->rankid){
					$retArr[$r->tid] = $r->sciname;
				}
				else{
					$nonRanked[$r->parenttid]['name'] = $r->sciname;
					$nonRanked[$r->parenttid]['tid'] = $r->tid;
				}
				if($nonRanked && array_key_exists($r->tid,$nonRanked)){
					$retArr[$nonRanked[$r->tid]['tid']] = $nonRanked[$r->tid]['name'];
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getChildren(){
		$retArr = array();
		$sql = 'SELECT t.tid, t.sciname, t.author, a.tid AS accTid, a.sciname AS accSciname, a.author AS accAuthor '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'INNER JOIN taxa a ON ts.tidaccepted = a.tid '.
			'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (ts.parenttid = '.$this->tid.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid]['sciname'] = $r->sciname;
			$retArr[$r->tid]['author'] = $r->author;
			$retArr[$r->tid]['accTid'] = $r->accTid;
			$retArr[$r->tid]['accSciname'] = $r->accSciname;
			$retArr[$r->tid]['accAuthor'] = $r->accAuthor;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	public function hasAcceptedChildren(){
		$bool = false;
		$sql = 'SELECT tid FROM taxstatus WHERE (taxauthid = '.$this->taxAuthId.') AND (parenttid = '.$this->tid.') AND (tid = tidaccepted) LIMIT 1';
		$rs = $this->conn->query($sql);
		if($rs->num_rows) $bool = true;
		$rs->free();
		return $bool;
	}
}
?>