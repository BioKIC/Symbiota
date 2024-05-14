<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class OccurrenceLoans extends Manager{

	private $collid = 0;
	private $idTagArr = array();
	private $specimenSortArr = array();

	function __construct() {
		parent::__construct(null,'write');
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getLoanOutList($searchTerm,$displayAll){
		$retArr = array();
		$extLoanArr = array();
		//Get loans that are assigned to other collections but have linked occurrences from this collection (NEON Biorepo portal issue)
		/*
		$sql = 'SELECT DISTINCT l.loanid, o.collid '.
			'FROM omoccurloans l INNER JOIN omoccurloanslink ll ON l.loanid = ll.loanid '.
			'INNER JOIN omoccurrences o ON ll.occid = o.occid '.
			'WHERE o.collid = '.$this->collid.' AND l.collidown != '.$this->collid;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$extLoanArr[$r->loanid] = $r->collid;
			}
			$rs->free();
		}
		*/

		//Get loan details
		$sql = 'SELECT l.loanid, l.datesent, l.loanidentifierown, l.loanidentifierborr, i.institutioncode AS instcode1, c.institutioncode AS instcode2, i.institutionname, l.forwhom, l.dateclosed, l.datedue '.
			'FROM omoccurloans l LEFT JOIN institutions i ON l.iidborrower = i.iid '.
			'LEFT JOIN omcollections c ON l.collidborr = c.collid '.
			'WHERE (l.collidown = '.$this->collid.' ';
		if($extLoanArr) $sql .= 'OR l.loanid IN('.implode(',',$extLoanArr).')';
		$sql .= ') ';
		if(!$displayAll) $sql .= 'AND l.dateclosed IS NULL ';
		$sql .= 'ORDER BY l.loanidentifierown + 1 DESC';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				if(!$searchTerm || stripos($r->instcode1,$searchTerm) !== false || stripos($r->instcode2,$searchTerm) !== false || stripos($r->forwhom,$searchTerm) !== false){
					$retArr[$r->loanid]['loanidentifierown'] = $r->loanidentifierown;
					$retArr[$r->loanid]['institutioncode'] = $r->instcode1;
					$retArr[$r->loanid]['institutionname'] = $r->institutionname;
					$retArr[$r->loanid]['forwhom'] = $r->forwhom;
					$retArr[$r->loanid]['dateclosed'] = $r->dateclosed;
					$retArr[$r->loanid]['datedue'] = $r->datedue;
					if(array_key_exists($r->loanid, $extLoanArr)) $retArr[$r->loanid]['isexternal'] = $extLoanArr[$r->loanid];
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getLoanOutDetails($loanid){
		$retArr = array();
		if(is_numeric($loanid)){
			$sql = 'SELECT loanid, loanidentifierown, iidborrower, datesent, totalboxes, '.
				'shippingmethod, datedue, datereceivedown, dateclosed, forwhom, description, '.
				'notes, createdbyown, processedbyown, processedbyreturnown, invoicemessageown '.
				'FROM omoccurloans '.
				'WHERE loanid = '.$loanid;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['loanidentifierown'] = $r->loanidentifierown;
					$retArr['iidborrower'] = $r->iidborrower;
					$retArr['datesent'] = $r->datesent;
					$retArr['totalboxes'] = $r->totalboxes;
					$retArr['shippingmethod'] = $r->shippingmethod;
					$retArr['datedue'] = $r->datedue;
					$retArr['datereceivedown'] = $r->datereceivedown;
					$retArr['dateclosed'] = $r->dateclosed;
					$retArr['forwhom'] = $r->forwhom;
					$retArr['description'] = $r->description;
					$retArr['notes'] = $r->notes;
					$retArr['createdbyown'] = $r->createdbyown;
					$retArr['processedbyown'] = $r->processedbyown;
					$retArr['processedbyreturnown'] = $r->processedbyreturnown;
					$retArr['invoicemessageown'] = $r->invoicemessageown;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function createNewLoanOut($pArr){
		$loanid = 0;
		$sql = 'INSERT INTO omoccurloans(collidown,loanidentifierown,iidowner,iidborrower,createdbyown) '.
			'VALUES('.$this->collid.',"'.$this->cleanInStr($pArr['loanidentifierown']).'",(SELECT iid FROM omcollections WHERE collid = '.$this->collid.'), '.
			'"'.$this->cleanInStr($pArr['reqinstitution']).'","'.$this->cleanInStr($pArr['createdbyown']).'") ';
		//echo $sql;
		if($this->conn->query($sql)){
			$loanid = $this->conn->insert_id;
		}
		else{
			$this->errorMessage = 'ERROR: Creation of new loan out failed: '.$this->conn->error.'<br/>';
			//$this->errorMessage .= 'SQL: '.$sql;
		}
		return $loanid;
	}

	public function editLoanOut($pArr){
		$statusStr = '';
		$loanid = $pArr['loanid'];
		if(is_numeric($loanid)){
			unset($pArr['formsubmit']);
			unset($pArr['loanid']);
			unset($pArr['collid']);
			unset($pArr['tabindex']);
			$sql = '';
			foreach($pArr as $k => $v){
				$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
			}
			$sql = 'UPDATE omoccurloans SET '.substr($sql,1).' WHERE (loanid = '.$loanid.')';
			if($this->conn->query($sql)){
				$statusStr = 'SUCCESS: information saved';
			}
			else{
				$statusStr = 'ERROR: Editing of loan failed: '.$this->conn->error.'<br/>';
				$statusStr .= 'SQL: '.$sql;
			}
		}
		return $statusStr;
	}

	public function deleteLoan($loanid){
		$status = false;
		if(is_numeric($loanid)){

			// First, delete any file attachments
			$sql = 'SELECT attachmentid FROM omoccurloansattachment WHERE (loanid = ' . $loanid . ')';
			if($rs = $this->conn->query($sql)) {
				while($r = $rs->fetch_object()){
					$this->deleteAttachment($r->attachmentid);
				}
				$rs->free();
			}

			// Delete loan in database
			$sql = 'DELETE FROM omoccurloans WHERE (loanid = '.$loanid.')';
			if($this->conn->query($sql)){
				$status = true;
			}
		}
		return $status;
	}

	//Loan in functions
	public function getLoanInList($searchTerm,$displayAll){
		$retArr = array();
		$sql = 'SELECT l.loanid, l.loanidentifierborr, l.dateclosed, i.institutioncode AS instcode1, c.institutioncode AS instcode2, i.institutionname, l.forwhom, l.datedue '.
			'FROM omoccurloans l INNER JOIN institutions i ON l.iidowner = i.iid '.
			'LEFT JOIN omcollections c ON l.collidown = c.collid '.
			'WHERE l.collidborr = '.$this->collid.' ';
		if(!$displayAll) $sql .= 'AND l.dateclosed IS NULL ';
		$sql .= 'ORDER BY l.loanidentifierborr + 1';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				if(!$searchTerm || stripos($r->instcode1,$searchTerm) !== false || stripos($r->instcode2,$searchTerm) !== false || stripos($r->forwhom,$searchTerm) !== false){
					$retArr[$r->loanid]['loanidentifierborr'] = $r->loanidentifierborr;
					$retArr[$r->loanid]['institutioncode'] = $r->instcode1;
					$retArr[$r->loanid]['institutionname'] = $r->institutionname;
					$retArr[$r->loanid]['forwhom'] = $r->forwhom;
					$retArr[$r->loanid]['dateclosed'] = $r->dateclosed;
					$retArr[$r->loanid]['datedue'] = $r->datedue;
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getLoanInDetails($loanid){
		$retArr = array();
		if(is_numeric($loanid)){
			$sql = 'SELECT loanid, loanidentifierown, loanidentifierborr, collidown, iidowner, datesentreturn, totalboxesreturned, '.
				'shippingmethodreturn, datedue, datereceivedborr, dateclosed, forwhom, description, numspecimens, '.
				'notes, createdbyborr, processedbyborr, processedbyreturnborr, invoicemessageborr '.
				'FROM omoccurloans '.
				'WHERE loanid = '.$loanid;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['loanidentifierown'] = $r->loanidentifierown;
					$retArr['loanidentifierborr'] = $r->loanidentifierborr;
					$retArr['collidown'] = $r->collidown;
					$retArr['iidowner'] = $r->iidowner;
					$retArr['datesentreturn'] = $r->datesentreturn;
					$retArr['totalboxesreturned'] = $r->totalboxesreturned;
					$retArr['shippingmethodreturn'] = $r->shippingmethodreturn;
					$retArr['datedue'] = $r->datedue;
					$retArr['datereceivedborr'] = $r->datereceivedborr;
					$retArr['dateclosed'] = $r->dateclosed;
					$retArr['forwhom'] = $r->forwhom;
					$retArr['description'] = $r->description;
					$retArr['numspecimens'] = $r->numspecimens;
					$retArr['notes'] = $r->notes;
					$retArr['createdbyborr'] = $r->createdbyborr;
					$retArr['processedbyborr'] = $r->processedbyborr;
					$retArr['processedbyreturnborr'] = $r->processedbyreturnborr;
					$retArr['invoicemessageborr'] = $r->invoicemessageborr;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function createNewLoanIn($pArr){
		$loanid = 0;
		$sql = 'INSERT INTO omoccurloans(collidborr,loanidentifierown,loanidentifierborr,iidowner,createdbyborr) '.
			'VALUES('.$this->collid.',"","'.$this->cleanInStr($pArr['loanidentifierborr']).'","'.$this->cleanInStr($pArr['iidowner']).'",
			"'.$this->cleanInStr($pArr['createdbyborr']).'")';
		//echo $sql;
		if($this->conn->query($sql)){
			$loanid = $this->conn->insert_id;
		}
		else{
			$this->errorMessage = 'ERROR: Creation of new loan in failed: '.$this->conn->error.'<br/>';
			//$this->errorMessage .= 'SQL: '.$sql;
		}
		return $loanid;
	}

	public function editLoanIn($pArr){
		$statusStr = '';
		$loanid = $pArr['loanid'];
		if(is_numeric($loanid)){
			unset($pArr['formsubmit']);
			unset($pArr['loanid']);
			unset($pArr['collid']);
			unset($pArr['tabindex']);
			$sql = '';
			foreach($pArr as $k => $v){
				$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
			}
			$sql = 'UPDATE omoccurloans SET '.substr($sql,1).' WHERE (loanid = '.$loanid.')';
			if($this->conn->query($sql)){
				$statusStr = 'SUCCESS: information saved';
			}
			else{
				$statusStr = 'ERROR: Editing of loan failed: '.$this->conn->error.'<br/>';
			}
		}
		return $statusStr;
	}

	public function getLoanOnWayList(){
		$retArr = array();
		$sql = 'SELECT DISTINCT l.loanid, l.loanidentifierown, c2.collectionname '.
			'FROM omcollections c INNER JOIN omoccurloans l ON c.iid = l.iidBorrower '.
			'INNER JOIN omcollections c2 ON l.collidOwn = c2.collid '.
			'WHERE (c.CollID = '.$this->collid.') AND (l.collidBorr IS NULL) AND (l.dateClosed IS NULL)' ;
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->loanid]['loanidentifierown'] = $r->loanidentifierown;
				$retArr[$r->loanid]['collectionname'] = $r->collectionname;
			}
			$rs->free();
		}
		return $retArr;
	}

	//Exchange functions
	public function getExchangeDetails($exchangeId){
		$retArr = array();
		if(is_numeric($exchangeId)){
			$sql = 'SELECT exchangeid, identifier, collid, iid, transactiontype, in_out, datesent, datereceived, '.
				'totalboxes, shippingmethod, totalexmounted, totalexunmounted, totalgift, totalgiftdet, adjustment, '.
				'invoicebalance, invoicemessage, description, notes, createdby '.
				'FROM omoccurexchange '.
				'WHERE exchangeid = '.$exchangeId;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['identifier'] = $r->identifier;
					$retArr['collid'] = $r->collid;
					$retArr['iid'] = $r->iid;
					$retArr['transactiontype'] = $r->transactiontype;
					$retArr['in_out'] = $r->in_out;
					$retArr['datesent'] = $r->datesent;
					$retArr['datereceived'] = $r->datereceived;
					$retArr['totalboxes'] = $r->totalboxes;
					$retArr['shippingmethod'] = $r->shippingmethod;
					$retArr['totalexmounted'] = $r->totalexmounted;
					$retArr['totalexunmounted'] = $r->totalexunmounted;
					$retArr['totalgift'] = $r->totalgift;
					$retArr['totalgiftdet'] = $r->totalgiftdet;
					$retArr['adjustment'] = $r->adjustment;
					$retArr['invoicebalance'] = $r->invoicebalance;
					$retArr['invoicemessage'] = $r->invoicemessage;
					$retArr['description'] = $r->description;
					$retArr['notes'] = $r->notes;
					$retArr['createdby'] = $r->createdby;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getExchangeValue($exchangeId){
		$exchangeValue = 0;
		if(is_numeric($exchangeId)){
			$sql = 'SELECT totalexmounted, totalexunmounted FROM omoccurexchange WHERE exchangeid = '.$exchangeId;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$exchangeValue = (($r->totalexmounted)*2) + ($r->totalexunmounted);
				}
				$rs->free();
			}
		}
		return $exchangeValue;
	}

	public function getExchangeTotal($exchangeId){
		$exchangeTotal = 0;
		if(is_numeric($exchangeId)){
			$sql = 'SELECT totalexmounted, totalexunmounted, totalgift, totalgiftdet FROM omoccurexchange WHERE exchangeid = '.$exchangeId;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$exchangeTotal = ($r->totalexmounted) + ($r->totalexunmounted) + ($r->totalgift) + ($r->totalgiftdet);
				}
				$rs->free();
			}
		}
		return $exchangeTotal;
	}

	public function createNewExchange($pArr){
		$retID = '';
		$sql = 'INSERT INTO omoccurexchange(identifier,collid,iid,transactiontype,createdby) '.
			'VALUES("'.$this->cleanInStr($pArr['identifier']).'",'.$this->collid.',"'.$this->cleanInStr($pArr['iid']).'",
			"'.$this->cleanInStr($pArr['transactiontype']).'","'.$this->cleanInStr($pArr['createdby']).'")';
		if($this->conn->query($sql)){
			$retID = $this->conn->insert_id;
		}
		else{
			$this->errorMessage = 'ERROR: Creation of new exchange failed: '.$this->conn->error.'<br/>';
		}
		return $retID;
	}

	public function editExchange($pArr){
		$statusStr = '';
		$exchangeId = $pArr['exchangeid'];
		$collid = $pArr['collid'];
		$iid = $pArr['iid'];
		if(is_numeric($exchangeId) && is_numeric($collid) && is_numeric($iid)){
			unset($pArr['formsubmit']);
			unset($pArr['exchangeid']);
			unset($pArr['collid']);
			unset($pArr['tabindex']);
			$sql = '';
			foreach($pArr as $k => $v){
				$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
			}
			$sql = 'UPDATE omoccurexchange SET '.substr($sql,1).' WHERE (exchangeid = '.$exchangeId.')';
			if($this->conn->query($sql)){
				$statusStr = 'SUCCESS: information saved';
			}
			else{
				$statusStr = 'ERROR: Editing of exchange failed: '.$this->conn->error.'<br/>';
			}

			$sql = 'SELECT invoicebalance FROM omoccurexchange '.
				'WHERE exchangeid =  (SELECT MAX(exchangeid) FROM omoccurexchange '.
				'WHERE (exchangeid < '.$exchangeId.') AND (collid = '.$collid.') AND (iid = '.$iid.'))';
			$retArr = array();
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['invoicebalance'] = $r->invoicebalance;
				}
				$rs->free();
			}
			$prevBalance = 0;
			if(array_key_exists('invoicebalance',$retArr) && $retArr['invoicebalance']) $prevBalance = $retArr['invoicebalance'];
			$currentBalance = 0;
			if($pArr['transactiontype'] == 'Shipment'){
				$totalMounted = $pArr['totalexmounted']?$pArr['totalexmounted']:0;
				$totalUnmounted = $pArr['totalexunmounted']?$pArr['totalexunmounted']:0;
				if($pArr['in_out'] == 'In') $currentBalance = ($prevBalance - ((($totalMounted)*2) + ($totalUnmounted)));
				elseif($pArr['in_out'] == 'Out') $currentBalance = ($prevBalance + ((($totalMounted)*2) + ($totalUnmounted)));
			}
			elseif($pArr['transactiontype'] == 'Adjustment'){
				$currentBalance = ($prevBalance + $pArr['adjustment']);
			}
			$sql3 = '';
			$sql3 = 'UPDATE omoccurexchange SET invoicebalance = '.$currentBalance.' WHERE (exchangeid = '.$exchangeId.')';
			if($this->conn->query($sql3)){
				$statusStr .= ' and balance updated.';
			}
		}
		return $statusStr;
	}

	public function deleteExchange($exchangeId){
		$status = false;
		if(is_numeric($exchangeId)){

			// First, delete any file attachments
			$sql = 'SELECT attachmentid FROM omoccurloansattachment WHERE (exchangeid = ' . $exchangeId . ')';
			if($rs = $this->conn->query($sql)) {
				while($r = $rs->fetch_object()){
					$this->deleteAttachment($r->attachmentid);
				}
				$rs->free();
			}

			// Delete exchange in database
			$sql = 'DELETE FROM omoccurexchange WHERE (exchangeid = '.$exchangeId.')';
			if($this->conn->query($sql)){
				$status = true;
			}
		}
		return $status;
	}

	public function getTransInstList($collid){
		$iidArr = array();
		if(is_numeric($collid)){
			$sql = 'SELECT DISTINCT e.iid, i.institutioncode, i.institutionname '.
				'FROM omoccurexchange AS e INNER JOIN institutions AS i ON e.iid = i.iid '.
				'WHERE e.collid = '.$this->collid.' AND e.iid IS NOT NULL '.
				'ORDER BY i.institutioncode';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$iidArr[$r->iid]['institutioncode'] = $r->institutioncode;
					$iidArr[$r->iid]['institutionname'] = $r->institutionname;
				}
			}
			$sql = 'SELECT rt.iid, e.invoicebalance FROM omoccurexchange AS e '.
				'INNER JOIN (SELECT iid, MAX(exchangeid) AS exchangeid FROM omoccurexchange '.
				'GROUP BY iid,collid HAVING (collid = '.$this->collid.')) AS rt ON e.exchangeid = rt.exchangeid ';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$iidArr[$r->iid]['invoicebalance'] = $r->invoicebalance;
				}
				$rs->free();
			}
		}
		return $iidArr;
	}

	public function getTransactions($collid,$iid){
		$retArr = array();
		if(is_numeric($collid) && is_numeric($iid)){
			$sql = 'SELECT exchangeid, identifier, transactiontype, in_out, datesent, datereceived, '.
				'totalexmounted, totalexunmounted, totalgift, totalgiftdet, adjustment, invoicebalance '.
				'FROM omoccurexchange '.
				'WHERE collid = '.$collid.' AND iid = '.$iid.' '.
				'ORDER BY exchangeid DESC';
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$r->exchangeid]['identifier'] = $r->identifier;
					$retArr[$r->exchangeid]['transactiontype'] = $r->transactiontype;
					$retArr[$r->exchangeid]['in_out'] = $r->in_out;
					$retArr[$r->exchangeid]['datesent'] = $r->datesent;
					$retArr[$r->exchangeid]['datereceived'] = $r->datereceived;
					$retArr[$r->exchangeid]['totalexmounted'] = $r->totalexmounted;
					$retArr[$r->exchangeid]['totalexunmounted'] = $r->totalexunmounted;
					$retArr[$r->exchangeid]['totalgift'] = $r->totalgift;
					$retArr[$r->exchangeid]['totalgiftdet'] = $r->totalgiftdet;
					$retArr[$r->exchangeid]['adjustment'] = $r->adjustment;
					$retArr[$r->exchangeid]['invoicebalance'] = $r->invoicebalance;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	//Specimen listing and edit functions
	public function getSpecimenList($loanid, $sortTag = ''){
		$retArr = array();
		if(is_numeric($loanid)){
			$sql = 'SELECT o.collid, l.loanid, l.occid, l.returndate, l.notes, o.catalognumber, o.othercatalognumbers, o.sciname,
				CONCAT_WS(" ",o.recordedby,IFNULL(o.recordnumber,o.eventdate)) AS collector, CONCAT_WS(", ",stateprovince,county,locality) AS locality
				FROM omoccurloanslink l INNER JOIN omoccurrences o ON l.occid = o.occid
				WHERE l.loanid = '.$loanid.'
				ORDER BY o.catalognumber+1, o.othercatalognumbers+1';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$r->occid]['collid'] = $r->collid;
					$retArr[$r->occid]['catalognumber'] = $r->catalognumber;
					if($r->catalognumber){
						$retArr[$r->occid]['catalognumber'] = $r->catalognumber;
						$this->idTagArr['1-catalognumber'] = 'Catalog Numbers';
					}
					if($r->othercatalognumbers){
						$retArr[$r->occid]['othercatalognumbers'][] = $r->othercatalognumbers;
						$this->idTagArr['2-othercatalognumbers'] = 'Other Catalog Numbers';
					}
					$retArr[$r->occid]['sciname'] = $r->sciname;
					$retArr[$r->occid]['collector'] = $r->collector;
					$retArr[$r->occid]['locality'] = $r->locality;
					$retArr[$r->occid]['returndate'] = $r->returndate;
					$retArr[$r->occid]['notes'] = $r->notes;
					if($sortTag){
						if($sortTag == 'catalognumber') $this->specimenSortArr[$r->occid] = $r->catalognumber;
						elseif($sortTag == 'othercatalognumbers') $this->specimenSortArr[$r->occid] = $r->othercatalognumbers;
						else $this->specimenSortArr[$r->occid] = '';
					}
					else $this->specimenSortArr[$r->occid] = '';
				}
				$rs->free();
			}
			//Get additional identifiers
			$sql = 'SELECT i.occid, i.identifierName, i.identifierValue
				FROM omoccurloanslink l INNER JOIN omoccuridentifiers i ON l.occid = i.occid
				WHERE l.loanid = '.$loanid.'
				ORDER BY i.sortBy, i.identifierValue';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$idTag = $r->identifierName;
					$idValue = ($idTag ? $idTag.': ' : '').$r->identifierValue;
					if(isset($retArr[$r->occid]['othercatalognumbers'])){
						foreach($retArr[$r->occid]['othercatalognumbers'] as $k => $v){
							if($v == $r->identifierValue || $v == $idValue) unset($retArr[$r->occid]['othercatalognumbers'][$k]);
						}
					}
					$retArr[$r->occid]['othercatalognumbers'][] = $idValue;
					if(!$idTag) $idTag = 'otherCatalogNumbers';
					$this->idTagArr['3-'.strtolower($idTag)] = $idTag;
					if($sortTag && $sortTag != 'catalognumber'){
						if($sortTag == strtolower($idTag)) $this->specimenSortArr[$r->occid] = $r->identifierValue;
					}
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getSpecimenTotal($loanid){
		$retStr = 0;
		if(is_numeric($loanid)){
			$sql = 'SELECT COUNT(*) AS cnt FROM omoccurloanslink WHERE loanid = '.$loanid;
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()){
					$retStr = $r->cnt;
				}
				$rs->free();
			}
		}
		return $retStr;
	}

	public function exportSpecimenList($loanid){
		$occurArr = array();
		$headerArr = array();
		$sql = 'SELECT o.catalogNumber, o.otherCatalogNumbers, o.occurrenceID, l.returnDate, l.notes, o.family, o.sciname, o.recordedBy, o.recordNumber, o.eventDate, o.country, o.stateProvince, '.
			'o.county, o.locality, o.decimalLatitude, o.decimalLongitude, o.minimumElevationInMeters, o.dateEntered, o.dateLastModified, l.initialTimestamp as dateTimeAddToLoan, o.occid '.
			'FROM omoccurrences o INNER JOIN omoccurloanslink l ON o.occid = l.occid '.
			'WHERE l.loanid = '.$loanid;
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$fields = mysqli_fetch_fields($rs);
			foreach($fields as $val) $headerArr[] = $val->name;
			while($r = $rs->fetch_assoc()){
				$occurArr[$r['occid']] = $r;
			}
			$rs->free();
		}

		//Add additional identifiers
		$identArr = array();
		$sql = 'SELECT l.occid, i.identifierName, i.identifierValue FROM omoccurloanslink l INNER JOIN omoccuridentifiers i ON l.occid = i.occid WHERE l.loanid = '.$loanid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$idName = $r->identifierName;
			if(!$idName) $idName = 'OTHERCAT';
			$identArr[$idName][$r->occid] = $r->identifierValue;
		}
		$rs->free();
		foreach($identArr as $name => $valArr){
			if($name == 'OTHERCAT'){
				foreach($valArr as $occid => $idStr){
					if($occurArr[$occid]['otherCatalogNumbers']) $occurArr[$occid]['otherCatalogNumbers'] .= ', ';
					$occurArr[$occid]['otherCatalogNumbers'] .= $idStr;
				}
			}
			else{
				$headerArr[] = $name;
				foreach($occurArr as $occid => $recArr){
					if(array_key_exists($occid, $valArr)) $occurArr[$occid][$name] = $valArr[$occid];
					else $occurArr[$occid][$name] = '';
				}
			}
		}

		//Create output file
		$fileName = 'loanSpecList_'.date('Ymd').'.csv';
		header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ('Content-Disposition: attachment; filename="'.$fileName.'"');
		if($occurArr){
			$out = fopen('php://output', 'w');
			fputcsv($out, $headerArr);
			foreach($occurArr as $recArr){
				fputcsv($out, $recArr);
			}
			fclose($out);
		}
		else echo "Specimen recordset is empty.\n";
	}

	/*
	 * This method is used by the ajax script processLoanSpecimen.php
	 * return: 0 = failed due to no records matching catalog number, 1 = success, 2 = failed due to multiple records found with catalog number, 3 = failed to link
	 */
	public function linkSpecimen($loanid, $catNum, $target){
		//This method is used by the ajax script processLoanSpecimen.php
		if(is_numeric($loanid)){
			$occArr = $this->getOccid($catNum, $target);
			if(!$occArr) return 0;
			elseif(count($occArr) > 1) return 2;
			else{
				if($this->addLoanSpecimen($loanid, $occArr[0])) return 1;
				else return 3;
			}
		}
		return 0;
	}

	/*
	 * This method is used by the ajax script processLoanSpecimen.php
	 * return: 0 = failed due to no records matching catalog number, 1 = success, 2 = success, but multiple records check, 3 = failed to update record (not in loan or already checked-in)
	 */
	public function checkinSpecimen($loanid, $catNum, $target){
		if(is_numeric($loanid)){
			$occArr = $this->getOccid($catNum, $target);
			if(!$occArr) return 0;
			else{
				if($status = $this->batchCheckinSpecimens($occArr, $loanid)){
					if($status == 1) return 1;
					else return 2;
				}
				else return 3;
			}
		}
		return 0;
	}

	public function batchProcessSpecimens($postArr){
		$cnt = 0;
		if($this->collid && is_numeric($postArr['loanid'])){
			if($postArr['catalogNumbers']){
				$mode = $postArr['processmode'];
				$catNumStr = str_replace(array("\n", "\r\n", ";"), ",", $postArr['catalogNumbers']);
				$catArr = array_unique(explode(',',$catNumStr));
				foreach($catArr as $catStr){
					$catStr = trim($catStr);
					if($catStr){
						if($occArr = $this->getOccid($catStr, $postArr['targetidentifier'])){
							if(count($occArr) > 1) $this->warningArr['multiple'][] = $catStr;
							if($mode == 'link'){
								foreach($occArr as $occid){
									if($this->addLoanSpecimen($postArr['loanid'], $occid)) $cnt++;
									else{
										if(strpos($this->errorMessage,'Duplicate entry') === 0) $this->warningArr['dupe'][] = $catStr;
										else $this->warningArr['error'][] = $this->errorMessage;
									}
								}
							}
							elseif($mode == 'checkin'){
								if($status = $this->batchCheckinSpecimens($occArr, $postArr['loanid'])) $cnt++;
								elseif($status === 0) $this->warningArr['zeroMatch'][] = $catStr;
								else $this->warningArr['error'][] = $this->errorMessage;
							}
						}
						else $this->warningArr['missing'][] = $catStr;
					}
				}
			}
		}
		return $cnt;
	}

	private function getOccid($catNum, $method){
		$occArr = array();
		if(!$method || !in_array($method,array('allid','catnum','other'))) $method = 'allid';
		$sql = 'SELECT DISTINCT o.occid FROM omoccurrences o ';
		$sqlWhere = '';
		if($method == 'allid' || $method == 'other'){
			$sql .= 'LEFT JOIN omoccuridentifiers i ON o.occid = i.occid ';
			$catNum = $this->cleanInStr($catNum);
			$sqlWhere = 'OR (o.othercatalognumbers = "'.$catNum.'") OR (i.identifierValue = "'.$catNum.'") ';
		}
		if($method == 'allid' || $method == 'catnum') $sqlWhere .= 'OR (o.catalognumber = "'.$this->cleanInStr($catNum).'") ';
		if($sqlWhere){
			$sql .= 'WHERE ('.substr($sqlWhere,2).') ';
			if($this->collid) $sql .= 'AND (o.collid = '.$this->collid.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()) {
				$occArr[] = $r->occid;
			}
			$rs->free();
		}
		return $occArr;
	}

	private function addLoanSpecimen($loanid,$occid){
		$status = false;
		$sql = 'INSERT INTO omoccurloanslink(loanid,occid) VALUES ('.$loanid.','.$occid.') ';
		if($this->conn->query($sql)) $status = true;
		else $this->errorMessage = $this->conn->error;
		return $status;
	}

	public function getSpecimenDetails($loanId, $occid){
		$retArr = array();
		if(is_numeric($loanId) && is_numeric($occid)){
			$sql = 'SELECT DATE_FORMAT(returndate, "%Y-%m-%dT%H:%i") AS returndate, notes FROM omoccurloanslink WHERE loanid = '.$loanId.' AND occid = '.$occid;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['returnDate'] = $r->returndate;
					$retArr['notes'] = $r->notes;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function editSpecimenDetails($loanId, $occid, $returnDate, $noteStr){
		$status = false;
		if(is_numeric($loanId) && is_numeric($occid)){
			$sql = 'UPDATE omoccurloanslink '.
				'SET returnDate = '.($returnDate?'"'.$this->cleanInStr($returnDate).'"':'NULL').', notes = '.($noteStr?'"'.$this->cleanInStr($noteStr).'"':'NULL').' '.
				'WHERE (loanid = '.$loanId.') AND (occid = '.$occid.')';
			if($this->conn->query($sql)) $status = true;
			else $this->errorMessage = 'ERROR updating specimen notes: '.$this->conn->error;
		}
		return $status;
	}

	public function batchCheckinSpecimens($occidInput, $loanID){
		$status = false;
		$occidStr = '';
		if(is_numeric($occidInput)) $occidStr = $occidInput;
		else $occidStr = implode(',',$occidInput);
		if(is_numeric($loanID) && preg_match('/^[\d,]+$/', $occidStr)){
			$sql = 'UPDATE omoccurloanslink SET returndate = "'.date('Y-m-d H:i:s').'" WHERE loanid = '.$loanID.' AND (occid IN('.$occidStr.')) AND (returndate IS NULL) ';
			if($this->conn->query($sql)) $status = $this->conn->affected_rows;
			else $this->errorMessage = 'ERROR checking in specimens: '.$this->conn->error;
		}
		return $status;
	}

	public function deleteSpecimens($occidArr, $loanID){
		$status = false;
		$occidStr = implode(',',$occidArr);
		if(is_numeric($loanID) && preg_match('/^[\d,]+$/', $occidStr)){
			$sql = 'DELETE FROM omoccurloanslink WHERE loanid = '.$loanID.' AND (occid IN('.$occidStr.')) ';
			if($this->conn->query($sql)) $status = true;
			else $this->errorMessage = 'ERROR removing specimens from loan: '.$this->conn->error;
		}
		return $status;
	}

	//Report functions
	public function getInvoiceInfo($id,$loanType){
		$retArr = array();
		if(is_numeric($id)){
			if($loanType == 'exchange'){
				$sql = 'SELECT e.exchangeid, e.identifier, e.iid, '.
					'e.totalboxes, e.shippingmethod, e.totalexmounted, e.totalexunmounted, e.totalgift, e.totalgiftdet, '.
					'e.invoicebalance, e.invoicemessage, e.description, i.contact, i.institutionname, i.institutionname2, '.
					'i.institutioncode, i.address1, i.address2, i.city, i.stateprovince, i.postalcode, i.country '.
					'FROM omoccurexchange AS e LEFT OUTER JOIN institutions AS i ON e.iid = i.iid '.
					'WHERE exchangeid = '.$id;
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_object()){
						$retArr['exchangeid'] = $r->exchangeid;
						$retArr['identifier'] = $r->identifier;
						$retArr['iid'] = $r->iid;
						$retArr['totalboxes'] = $r->totalboxes;
						$retArr['shippingmethod'] = $r->shippingmethod;
						$retArr['totalexmounted'] = $r->totalexmounted;
						$retArr['totalexunmounted'] = $r->totalexunmounted;
						$retArr['totalgift'] = $r->totalgift;
						$retArr['totalgiftdet'] = $r->totalgiftdet;
						$retArr['invoicebalance'] = $r->invoicebalance;
						$retArr['invoicemessage'] = $r->invoicemessage;
						$retArr['description'] = $r->description;
						$retArr['contact'] = $r->contact;
						$retArr['institutionname'] = $r->institutionname;
						$retArr['institutionname2'] = $r->institutionname2;
						$retArr['institutioncode'] = $r->institutioncode;
						$retArr['address1'] = $r->address1;
						$retArr['address2'] = $r->address2;
						$retArr['city'] = $r->city;
						$retArr['stateprovince'] = $r->stateprovince;
						$retArr['postalcode'] = $r->postalcode;
						$retArr['country'] = $r->country;
					}
				}
			}
			else{
				$sql = 'SELECT e.loanid, e.loanidentifierown, e.loanidentifierborr, e.datesent, e.totalboxes, e.totalboxesreturned, '.
					'e.numspecimens, e.shippingmethod, e.shippingmethodreturn, e.datedue, e.datereceivedborr, e.forwhom, '.
					'e.description, e.invoicemessageown, e.invoicemessageborr, i.contact, i.institutionname, i.institutionname2, '.
					'i.institutioncode, i.address1, i.address2, i.city, i.stateprovince, i.postalcode, i.country ';
				if($loanType == 'out') $sql .= 'FROM omoccurloans AS e LEFT OUTER JOIN institutions AS i ON e.iidborrower = i.iid ';
				elseif($loanType == 'in') $sql .= 'FROM omoccurloans AS e LEFT OUTER JOIN institutions AS i ON e.iidowner = i.iid ';
				$sql .= 'WHERE loanid = '.$id;
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_object()){
						$retArr['loanid'] = $r->loanid;
						$retArr['loanidentifierown'] = $r->loanidentifierown;
						$retArr['loanidentifierborr'] = $r->loanidentifierborr;
						$retArr['datesent'] = $r->datesent;
						$retArr['totalboxes'] = $r->totalboxes;
						$retArr['totalboxesreturned'] = $r->totalboxesreturned;
						$retArr['numspecimens'] = $r->numspecimens;
						$retArr['shippingmethod'] = $r->shippingmethod;
						$retArr['shippingmethodreturn'] = $r->shippingmethodreturn;
						$retArr['datedue'] = $r->datedue;
						$retArr['datereceivedborr'] = $r->datereceivedborr;
						$retArr['forwhom'] = $r->forwhom;
						$retArr['description'] = $r->description;
						$retArr['invoicemessageown'] = $r->invoicemessageown;
						$retArr['invoicemessageborr'] = $r->invoicemessageborr;
						$retArr['contact'] = $r->contact;
						$retArr['institutionname'] = $r->institutionname;
						$retArr['institutionname2'] = $r->institutionname2;
						$retArr['institutioncode'] = $r->institutioncode;
						$retArr['address1'] = $r->address1;
						$retArr['address2'] = $r->address2;
						$retArr['city'] = $r->city;
						$retArr['stateprovince'] = $r->stateprovince;
						$retArr['postalcode'] = $r->postalcode;
						$retArr['country'] = $r->country;
					}
				}
			}
		}
		return $retArr;
	}

	public function getFromAddress($collid){
		$retArr = array();
		if(is_numeric($collid)){
			$sql = 'SELECT i.institutionname, i.institutionname2, i.phone, '.
				'i.institutioncode, i.address1, i.address2, i.city, i.stateprovince, i.postalcode, i.country '.
				'FROM omcollections o INNER JOIN institutions i ON o.iid = i.iid '.
				'WHERE o.collid = '.$collid;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['institutionname'] = $r->institutionname;
					$retArr['institutionname2'] = $r->institutionname2;
					$retArr['phone'] = $r->phone;
					$retArr['institutioncode'] = $r->institutioncode;
					$retArr['address1'] = $r->address1;
					$retArr['address2'] = $r->address2;
					$retArr['city'] = $r->city;
					$retArr['stateprovince'] = $r->stateprovince;
					$retArr['postalcode'] = $r->postalcode;
					$retArr['country'] = $r->country;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getToAddress($iid){
		$retArr = array();
		if(is_numeric($iid)){
			$sql = 'SELECT contact, institutionname, institutionname2, phone, institutioncode, address1, address2, city, stateprovince, postalcode, country '.
				'FROM institutions '.
				'WHERE iid = '.$iid;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['contact'] = $r->contact;
					$retArr['institutionname'] = $r->institutionname;
					$retArr['institutionname2'] = $r->institutionname2;
					$retArr['phone'] = $r->phone;
					$retArr['institutioncode'] = $r->institutioncode;
					$retArr['address1'] = $r->address1;
					$retArr['address2'] = $r->address2;
					$retArr['city'] = $r->city;
					$retArr['stateprovince'] = $r->stateprovince;
					$retArr['postalcode'] = $r->postalcode;
					$retArr['country'] = $r->country;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	// General AJAX functions
	public function identifierExists($identifier,$idType){
		$responseCode = 0;
		if($this->collid){
			$sql = '';
			if($idType == 'out'){
				$sql = 'SELECT loanid FROM omoccurloans WHERE loanidentifierown = "'.$this->cleanInStr($identifier).'" AND collidown = '.$this->collid;
			}
			elseif($idType == 'in'){
				$sql = 'SELECT loanid FROM omoccurloans WHERE loanIdentifierBorr = "'.$this->cleanInStr($identifier).'" AND collidborr = '.$this->collid;
			}
			elseif($idType == 'ex'){
				$sql = 'SELECT exchangeid FROM omoccurexchange WHERE identifier = "'.$this->cleanInStr($identifier).'" AND collid = '.$this->collid;
			}
			if($sql){
				$rs = $this->conn->query($sql);
				if($rs->num_rows)  $responseCode = 1;
				$rs->free();
			}
		}
		return $responseCode;
	}

	public function generateNextID($idType){
		$retStr = 1;
		if($this->collid){
			$sql = '';
			if($idType == 'out'){
				$sql = 'SELECT loanidentifierown AS id FROM omoccurloans WHERE collidown = '.$this->collid.' ORDER BY loanid desc LIMIT 3';
			}
			elseif($idType == 'in'){
				$sql = 'SELECT loanidentifierborr AS id FROM omoccurloans WHERE collidborr = '.$this->collid.' ORDER BY loanid desc LIMIT 3';
			}
			elseif($idType == 'ex'){
				$sql = 'SELECT identifier AS id FROM omoccurexchange WHERE collid = '.$this->collid.' ORDER BY exchangeid desc LIMIT 3';
			}
			else{
				return '';
			}

			if($rs = $this->conn->query($sql)){
				$maxnum = 0;
				while($r = $rs->fetch_object()){
					$num = '';

					// Replace all non-digits with -
					$id = preg_replace('/[^\d]+/', '-', $r->id);
					$id = preg_replace('/-{2,}/','-',$id);

					// Split into an array containing the number(s) in the string
					$numArr = explode('-',$id);

					// Get the last array element that is a number
					// Note: NOT the highest number, this could be something like a year, e.g., 2022-5.
					// The last number in the string is most likely the right one to increment
					while (!is_numeric($num)) $num = array_pop($numArr);

					// Check if the number found is the highest so far, if so, use that one to increment.
					if ($num > $maxnum) {
						$maxnum = $num;

						// Rather than using the number itself, replace the old number in the string with the incremented one & return that
						// This allows for alphanumeric identifiers like loan-102 or OSC-V-1524
						$retStr = str_replace($num, $num + 1, $r->id);
					}
				}
				$rs->free();
			}
		}
		return $retStr;
	}

	// Correspondence attachments management functions
	public function uploadAttachment($collid, $type, $transid, $identifier, $title, $file) {

		// Permissable mimetypes, see http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
		$mimetypes = array('text/plain', 'image/jpeg', 'image/png', 'application/pdf', 'application/msword',
			'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

		// File checking
		// Check to make sure it is an uploaded file and not spoofed
		if (!is_uploaded_file($file['tmp_name'])) {
			$this->errorMessage = 'Error: Not an uploaded file';
			return false;

		// Check to make sure the filesize is permissable
		} else if ($file['size'] > 10000000) {
			$this->errorMessage = 'Error: File size is too large: max size is 10 MB';
			return false;

		// Check the mimetype of the file, don't rely on the file extension
		} else if (!in_array(mime_content_type($file['tmp_name']), $mimetypes)) {
			$this->errorMessage = 'Error: File type does not match extension. File must be a PDF (.pdf), MS Word document (.doc or .docx), MS Excel file (.xls or .xlsx), image (.jpg, .jpeg, or .png). or a text file (.txt, .csv).';
			return false;
		}

		// Create the path for the attachment, storing under a subfolder for the particular collection
		$relPath = 'content/collections/loans/coll' . $collid . '/';
		$fullPath = $GLOBALS['SERVER_ROOT'] . '/' . $relPath;

		// Check to make sure the save path exists, creating it if permissions are sufficient
		if (!is_dir($fullPath)) {
			if (!mkdir($fullPath, 0775, true)) {
				$this->errorMessage = 'Error: Insufficient permissions to save files in content/collections/loans';
				return false;
			}
		}

		// Recreate the filename, appending the loan identifier and current date stamp to make the filename more unique
		$filename = pathinfo($file['name'], PATHINFO_FILENAME) . '_' . $type . $identifier .
			'_' . date('Y-m-d', time()) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);

		// Replace any invalid filename characters and sanitize filename
		$filename = $this->cleanInStr(str_replace(array('\\','/',':','*','?','"','<','>','|', '..'),'_',$filename));

		// Keep filename size within 255 bytes
		if (mb_strlen($filename, "UTF-8") > 255) {
			$this->errorMessage = 'Error: The filename of the attachment: ' . $file['name'] . 'is too long';
			return false;
		}

		// Check to make sure same filename doesn't already exist for this loan/date combo
		if (file_exists($fullPath . $filename)) {
				$this->errorMessage = 'Error: A file with the same name has already been uploaded today for this loan.';
				return false;
		}

		// Check to make sure this is an uploaded file and that it can be moved successfully
		if (move_uploaded_file($file['tmp_name'], $fullPath . $filename)) {

			// Moved successfully, save in database
			if ($this->saveAttachment($type, $transid, $title, $relPath, $filename)) {
				$this->errorMessage = "SUCCESS: Correspondence attachment saved successfully.";
				return true;
			}

			// Attachment failed to be saved in database
			return false;
		}
		else {
			$this->errorMessage = 'Error: The uploaded file could not be saved successfully.';
			return false;
		}
	}

	public function saveAttachment($type, $transid, $title, $path, $filename){
		$sql = 'INSERT INTO omoccurloansattachment (' . ($type == "loan" ? 'loanid' : 'exchangeid') . ', title, path, filename) '.
		'VALUES('.$transid . ',"' . $this->cleanInStr($title) . '","' . $path . '","' . $filename .'") ';

		if($this->conn->query($sql)){
			$attachmentid = $this->conn->insert_id;
			return $attachmentid;
		}
		else{
			$this->errorMessage = 'ERROR: attachment could not be saved in database: '.$this->conn->error.'<br/>';
			return false;
		}
	}

	public function deleteAttachment($attachid){

		if(is_numeric($attachid)){

			// First get the file path to delete
			$sql = 'SELECT path, filename FROM omoccurloansattachment WHERE (attachmentid = ' . $attachid . ')';
			if($rs = $this->conn->query($sql)) {
				while($r = $rs->fetch_object()){
					$path = $GLOBALS['SERVER_ROOT'] . '/' . $r->path . $r->filename;
				}
				$rs->free();
			}

			// Make sure we got a file path
			if(!isset($path)) {
				$this->errorMessage = 'Error: Attachment to delete not found in database';
				return false;
			}

			// Next delete from the database
			$sql = 'DELETE FROM omoccurloansattachment WHERE (attachmentid = '.$attachid.')';
			if(!$this->conn->query($sql)){
				$this->errorMessage = 'Error: Attachment could not be deleted from database';
				return false;
			}

			// Finally, delete the physical file
			if(!unlink($path)){
				$this->errorMessage = 'Error: Attachment file could not be deleted from server';
				return false;
			}
			return true;
		}
		return false;
	}

	public function getAttachments($type, $transid) {
		$retArr = false;
		$sql = 'SELECT attachmentid, title, path, filename, initialTimestamp ' .
			'FROM omoccurloansattachment ' .
			'WHERE '. ($type == "loan" ? 'loanid' : 'exchangeid') . ' = ' . $transid . ' ' .
			'ORDER BY initialTimestamp ASC;';

		if($rs = $this->conn->query($sql)){
			$retArr = array();
			while($r = $rs->fetch_object()){
				$retArr[$r->attachmentid]['title'] = $r->title;
				$retArr[$r->attachmentid]['path'] = $r->path;
				$retArr[$r->attachmentid]['filename'] = $r->filename;
				$retArr[$r->attachmentid]['timestamp'] = $r->initialTimestamp;
			}
			$rs->free();
		}
		return $retArr;
	}

	//General look up functions
	public function getInstitutionArr(){
		$retArr = array();
		$sql = 'SELECT i.iid, IFNULL(c.institutioncode,i.institutioncode) as institutioncode, i.institutionname '.
			'FROM institutions i LEFT JOIN (SELECT iid, institutioncode, collectioncode, collectionname '.
			'FROM omcollections WHERE colltype = "Preserved Specimens") c ON i.iid = c.iid '.
			'ORDER BY i.institutioncode,c.institutioncode,c.collectionname,i.institutionname';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->iid] = $r->institutioncode.' - '.$r->institutionname;
			}
		}
		return $retArr;
	}

	//Setters and getter
	public function setCollId($id){
		if(is_numeric($id)) $this->collid = $id;
	}

	public function getIdentifierTagArr(){
		return $this->idTagArr;
	}

	public function getSpecimenSortArr(){
		asort($this->specimenSortArr);
		return $this->specimenSortArr;
	}
}
?>