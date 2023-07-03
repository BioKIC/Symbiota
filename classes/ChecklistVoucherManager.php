<?php
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');

class ChecklistVoucherManager extends  ChecklistVoucherAdmin{

	private $tid;
	private $taxonName;

	function __construct() {
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getChecklistData(){
		$checklistData = Array();
		if($this->tid && $this->clid){
			$sql = 'SELECT t.SciName, cllink.Habitat, cllink.Abundance, cllink.Notes, cllink.internalnotes, cllink.source, cllink.familyoverride, cl.Name, cl.type, cl.locality '.
				'FROM fmchecklists cl INNER JOIN fmchklsttaxalink cllink ON cl.CLID = cllink.CLID '.
				'INNER JOIN taxa t ON cllink.TID = t.TID '.
				'WHERE (cllink.TID = '.$this->tid.') AND (cllink.CLID = '.$this->clid.')';
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$checklistData['habitat'] = $this->cleanOutStr($row->Habitat);
				$checklistData['abundance'] = $this->cleanOutStr($row->Abundance);
				$checklistData['notes'] = $this->cleanOutStr($row->Notes);
				$checklistData['internalnotes'] = $this->cleanOutStr($row->internalnotes);
				$checklistData['source'] = $this->cleanOutStr($row->source);
				$checklistData['familyoverride'] = $this->cleanOutStr($row->familyoverride);
				$checklistData['cltype'] = $row->type;
				$checklistData['locality'] = $row->locality;
				if(!$this->clName) $this->clName = $this->cleanOutStr($row->Name);
				if(!$this->taxonName) $this->taxonName = $this->cleanOutStr($row->SciName);
			}
			$result->free();
		}
		return $checklistData;
	}

	//Editing functions
	public function editClData($eArr){
		$retStr = '';
		$innerSql = '';
		foreach($eArr as $k => $v){
			$valStr = trim($v);
			$innerSql .= ",".$k."=".($valStr?'"'.$this->cleanInStr($valStr).'" ':'NULL');
		}
		$sqlClUpdate = 'UPDATE fmchklsttaxalink SET '.substr($innerSql,1).' WHERE (tid = '.$this->tid.') AND (clid = '.$this->clid.')';
		if(!$this->conn->query($sqlClUpdate)){
			$retStr = "ERROR editing details: ".$this->conn->error."<br/>SQL: ".$sqlClUpdate.";<br/> ";
		}
		return $retStr;
	}

	public function renameTaxon($targetTid, $rareLocality = ''){
		$statusStr = false;
		if(is_numeric($targetTid)){
			$clTaxaID = $this->getClTaxaID($this->tid);
			$sql = 'UPDATE fmchklsttaxalink SET TID = '.$targetTid.' WHERE (clTaxaID = '.$clTaxaID.')';
			if($this->conn->query($sql)){
				$this->tid = $targetTid;
				$this->taxonName = '';
				$statusStr = true;
			}
			else{
				$sqlTarget = 'SELECT clTaxaID, Habitat, Abundance, Notes, internalnotes, source, Nativity FROM fmchklsttaxalink WHERE (tid = '.$targetTid.') AND (clid = '.$this->clid.')';
				$rsTarget = $this->conn->query($sqlTarget);
				if($row = $rsTarget->fetch_object()){
					$clTaxaIDTarget = $row->clTaxaID;
					$habitatTarget = $this->cleanInStr($row->Habitat);
					$abundTarget = $this->cleanInStr($row->Abundance);
					$notesTarget = $this->cleanInStr($row->Notes);
					$internalNotesTarget = $this->cleanInStr($row->internalnotes);
					$sourceTarget = $this->cleanInStr($row->source);
					$nativeTarget = $this->cleanInStr($row->Nativity);

					//Move all vouchers to new name
					$sqlVouch = 'UPDATE IGNORE fmvouchers SET clTaxaID = '.$clTaxaIDTarget.' WHERE (clTaxaID = '.$clTaxaID.')';

					if(!$this->conn->query($sqlVouch)){
						$this->errorMessage = 'ERROR transferring vouchers during taxon transfer: '.$this->conn->error;
					}
					//Delete all Vouchers that didn't transfer because they were already linked to target name
					$sqlVouchDel = 'DELETE FROM fmvouchers WHERE (clTaxaID = '.$clTaxaID.')';
					if(!$this->conn->query($sqlVouchDel)){
						$this->errorMessage = "ERROR removing vouchers during taxon transfer: ".$this->conn->error;
					}

					//Merge chklsttaxalink data
					//Harvest source (unwanted) chklsttaxalink data
					$sqlSourceCl = 'SELECT Habitat, Abundance, Notes, internalnotes, source, Nativity FROM fmchklsttaxalink WHERE (clTaxaID = '.$clTaxaID.')';
					$rsSourceCl =  $this->conn->query($sqlSourceCl);
					if($row = $rsSourceCl->fetch_object()){
						$habitatSource = $this->cleanInStr($row->Habitat);
						$abundSource = $this->cleanInStr($row->Abundance);
						$notesSource = $this->cleanInStr($row->Notes);
						$internalNotesSource = $this->cleanInStr($row->internalnotes);
						$sourceSource = $this->cleanInStr($row->source);
						$nativeSource = $this->cleanInStr($row->Nativity);
					}
					$rsSourceCl->free();
					//Transfer source chklsttaxalink data to target record
					$habitatStr = $habitatTarget.(($habitatTarget && $habitatSource)?'; ':'').$habitatSource;
					$abundStr = $abundTarget.(($abundTarget && $abundSource)?'; ':'').$abundSource;
					$notesStr = $notesTarget.(($notesTarget && $notesSource)?'; ':'').$notesSource;
					$internalNotesStr = $internalNotesTarget.(($internalNotesTarget && $internalNotesSource)?'; ':'').$internalNotesSource;
					$sourceStr = $sourceTarget.(($sourceTarget && $sourceSource)?'; ':'').$sourceSource;
					$nativeStr = $nativeTarget.(($nativeTarget && $nativeSource)?'; ':'').$nativeSource;
					$sqlCl = 'UPDATE fmchklsttaxalink SET
						Habitat = '.($habitatStr ? '"'.$this->cleanInStr($habitatStr).'"':'NULL').',
						Abundance = '.($abundStr ? '"'.$this->cleanInStr($abundStr).'"':'NULL').',
						Notes = '.($notesStr ? '"'.$this->cleanInStr($notesStr).'"' : 'NULL').',
						internalnotes = '.($internalNotesStr ? '"'.$this->cleanInStr($internalNotesStr).'"' : 'NULL').',
						source = '. ($sourceStr ? '"'.$this->cleanInStr($sourceStr).'"' : 'NULL').',
						Nativity = '. ($nativeStr? '"'.$this->cleanInStr($nativeStr).'"' : 'NULL').'
						WHERE (clTaxaID = '.$clTaxaIDTarget.')';
					if($this->conn->query($sqlCl)){
						//Delete unwanted taxon
						$sqlDel = 'DELETE FROM fmchklsttaxalink WHERE (clTaxaID = '.$clTaxaID.')';
						if($this->conn->query($sqlDel)){
							$this->tid = $targetTid;
							$this->taxonName = '';
							$statusStr = true;
						}
						else $this->errorMessage = 'ERROR removing taxon during taxon transfer: '.$this->conn->error;
					}
					else $this->errorMessage = 'ERROR updating new taxon during taxon transfer: '.$this->conn->error;
				}
				$rsTarget->free();
			}
			if($rareLocality){
				$this->removeStateRareStatus($rareLocality);
			}
		}
		return $statusStr;
	}

	public function deleteTaxon($rareLocality = ''){
		$statusStr = '';
		$clTaxaID = $this->getClTaxaID($this->tid);

		//Delete vouchers
		$vSql = 'DELETE FROM fmvouchers WHERE (clTaxaID = '.$clTaxaID.')';
		$this->conn->query($vSql);
		//Delete checklist record
		$sql = 'DELETE FROM fmchklsttaxalink WHERE (clTaxaID = '.$clTaxaID.')';
		if($this->conn->query($sql)){
			if($rareLocality){
				$this->removeStateRareStatus($rareLocality);
			}
		}
		else $statusStr = 'ERROR deleting taxon from checklist: '.$this->conn->error;
		return $statusStr;
	}

	private function removeStateRareStatus($rareLocality){
		//Remove state based security protection only if name is not on global list
		$sql = 'SELECT IFNULL(securitystatus,0) as securitystatus FROM taxa WHERE tid = '.$this->tid;
		//echo $sql;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			if($r->securitystatus == 0){
				//Set occurrence
				$sqlRare = 'UPDATE omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
					'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
					'SET o.localitysecurity = NULL '.
					'WHERE (o.localitysecurity = 1) AND (o.localitySecurityReason IS NULL) AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) '.
					'AND o.stateprovince = "'.$rareLocality.'" AND ts2.tid = '.$this->tid;
				//echo $sqlRare; exit;
				if(!$this->conn->query($sqlRare)){
					$this->errorMessage = "ERROR resetting locality security during taxon delete: ".$this->conn->error;
				}
			}
		}
		$rs->free();
	}

	//Voucher functions
	public function getVoucherData(){
		$voucherData = Array();
		if($this->tid && $this->clid){
			if($clTaxaID = $this->getClTaxaID($this->tid)){
				$sql = 'SELECT v.voucherID, o.occid, CONCAT_WS(" ",o.recordedby,o.recordnumber) AS collector, o.catalognumber, o.sciname, o.eventdate, v.notes, v.editornotes
					FROM fmvouchers v INNER JOIN omoccurrences o ON v.occid = o.occid
					WHERE (v.clTaxaID = '.$clTaxaID.')';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$voucherData[$r->voucherID]['occid'] = $r->occid;
					$voucherData[$r->voucherID]['collector'] = $r->collector;
					$voucherData[$r->voucherID]['catalognumber'] = $r->catalognumber;
					$voucherData[$r->voucherID]['sciname'] = $r->sciname;
					$voucherData[$r->voucherID]['eventdate'] = $r->eventdate;
					$voucherData[$r->voucherID]['notes'] = $r->notes;
					$voucherData[$r->voucherID]['editornotes'] = $r->editornotes;
				}
				$rs->free();
			}
		}
		return $voucherData;
	}

	public function editVoucher($voucherID, $notes, $editorNotes){
		$status = false;
		if(is_numeric($voucherID)){
			if(!$notes) $notes = null;
			if(!$editorNotes) $editorNotes = null;
			$sql = 'UPDATE fmvouchers SET notes = ?, editornotes = ? WHERE (voucherID = ?)';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('ssi', $notes, $editorNotes, $voucherID);
				$stmt->execute();
				if($stmt->affected_rows) $status = true;
				elseif($stmt->error) $this->errorMessage = 'ERROR editing voucher: '.$stmt->error;
				$stmt->close();
			}
		}
		return $status;
	}

	public function addVoucher($vOccId, $vNotes, $vEditNotes){
		$status = false;
		if(is_numeric($vOccId) && $this->clid){
			$status = $this->addVoucherRecord($vOccId, $vNotes, $vEditNotes);
			if(!$status){
				$tid = $this->getTidInterpreted($vOccId);
				if($clTaxaID = $this->insertChecklistTaxaLink($tid, $this->clid)){
					$status = $this->insertVoucher($clTaxaID, $vOccId, $vNotes, $vEditNotes);
				}
			}
		}
		return $status;
	}

	private function addVoucherRecord($vOccId, $vNotes, $vEditNotes){
		//Checklist-taxon combination already exists
		$sql = 'SELECT DISTINCT o.occid, ctl.tid '.
			'FROM omoccurrences o INNER JOIN taxstatus ts1 ON o.TidInterpreted = ts1.tid '.
			'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
			'INNER JOIN fmchklsttaxalink ctl ON ts2.tid = ctl.tid '.
			'WHERE (ctl.clid = '.$this->clid.') AND (o.occid = '.$vOccId.') AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 '.
			'LIMIT 1';
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			if($clTaxaID = $this->getClTaxaID($row->tid)){
				if($this->insertVoucher($clTaxaID, $row->occid, $vNotes, $vEditNotes)){
					$this->tid = $row->tid;
				}
				else{
					$this->errorMessage = 'ERROR - Voucher insert failed: ' . $this->conn->error;
					return false;
				}
			}
			$rs-free();
			return true;
		}
		$this->errorMessage =  'ERROR: Neither the target taxon nor a sysnonym is present in this checklists. Taxon needs to be added.';
		return false;
	}

	public function deleteVoucher($voucherID){
		$status = false;
		if(is_numeric($voucherID)){
			$sql = 'DELETE FROM fmvouchers WHERE (voucherID = ?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('i', $voucherID);
				$stmt->execute();
				if($stmt->affected_rows) $status = true;
				elseif($stmt->error) $this->errorMessage = 'ERROR deleting vouchers: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for voucher deletion: '.$this->conn->error;
		}
		return $status;
	}

	//Setters and getters
	public function setTid($t){
		if(is_numeric($t)){
			$this->tid = $t;
		}
	}

	public function getTid(){
		return $this->tid;
	}

	public function getTaxonName(){
		return $this->taxonName;
	}
}
?>