<?php
include_once 'OccurrenceEditorManager.php';
class OccurrenceEditorResource extends OccurrenceEditorManager {

	private $relationshipArr;

	public function __construct($conn = null){
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}

	//Occurrence relationships
	public function getOccurrenceRelationships(){
		$retArr = array();
		$relOccidArr = array();
		$uidArr = array();
		$sql = 'SELECT assocID, occid, occidAssociate, relationship, subType, resourceUrl, identifier, verbatimSciname, tid, locationOnHost, notes, dynamicProperties, '.
			'IFNULL(modifiedUid,createdUid) as uid, IFNULL(modifiedTimestamp, initialTimestamp) as ts '.
			'FROM omoccurassociations '.
			'WHERE (occid = '.$this->occid.') OR (occidAssociate = '.$this->occid.')';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$relOccid = $r->occidAssociate;
				$relationship = $r->relationship;
				if($this->occid == $r->occidAssociate){
					$relOccid = $r->occid;
					$relationship = $this->getInverseRelationship($relationship);
				}
				if($relOccid) $relOccidArr[$relOccid][] = $r->assocID;
				$retArr[$r->assocID]['occidAssociate'] = $relOccid;
				$retArr[$r->assocID]['relationship'] = $relationship;
				$retArr[$r->assocID]['subType'] = $r->subType;
				$retArr[$r->assocID]['resourceUrl'] = $r->resourceUrl;
				$retArr[$r->assocID]['identifier'] = $r->identifier;
				$retArr[$r->assocID]['sciname'] = $r->verbatimSciname;
				$retArr[$r->assocID]['tid'] = $r->tid;
				$retArr[$r->assocID]['locationOnHost'] = $r->locationOnHost;
				$retArr[$r->assocID]['notes'] = $r->notes;
				$retArr[$r->assocID]['dynamicProperties'] = $r->dynamicProperties;
				$retArr[$r->assocID]['ts'] = $r->ts;
				if(!$retArr[$r->assocID]['identifier'] && $retArr[$r->assocID]['resourceUrl']) $retArr[$r->assocID]['identifier'] = 'identifier undefined';
				if($r->uid) $uidArr[$r->uid][] = $r->assocID;
			}
			$rs->free();
			if($uidArr){
				//Update modifiedBy data
				$sql = 'SELECT uid, CONCAT_WS("; ",lastname, firstname) as username FROM users WHERE uid IN('.implode(',',array_keys($uidArr)).')';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					foreach($uidArr[$r->uid] as $targetAssocID){
						$retArr[$targetAssocID]['definedBy'] = $r->username;
					}
				}
				$rs->free();
			}
			if($relOccidArr){
				$sql = 'SELECT o.occid, CONCAT_WS("-",IFNULL(o.institutioncode,c.institutioncode),IFNULL(o.collectioncode,c.collectioncode)) as collcode, IFNULL(o.catalogNumber,o.otherCatalogNumbers) as catnum '.
					'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid '.
					'WHERE o.occid IN('.implode(',',array_keys($relOccidArr)).')';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					foreach($relOccidArr[$r->occid] as $targetAssocID){
						$retArr[$targetAssocID]['identifier'] = $r->collcode.':'.$r->catnum;
					}
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	private function getInverseRelationship($relationship){
		if(!$this->relationshipArr) $this->setRelationshipArr();
		if(array_key_exists($relationship, $this->relationshipArr)) return $this->relationshipArr[$relationship];
		return $relationship;
	}

	private function setRelationshipArr(){
		if(!$this->relationshipArr){
			$sql = 'SELECT t.term, t.inverseRelationship FROM ctcontrolvocabterm t INNER JOIN ctcontrolvocab v  ON t.cvid = v.cvid '.
				'WHERE v.tableName = "omoccurassociations" AND v.fieldName = "relationship" ';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$this->relationshipArr[$r->term] = $r->inverseRelationship;
				}
				$rs->free();
			}
			$this->relationshipArr = array_merge($this->relationshipArr,array_flip($this->relationshipArr));
			ksort($this->relationshipArr);
		}
	}

	public function addAssociation($postArr){
		$status = true;
		$sql = 'INSERT INTO omoccurassociations(occid, occidAssociate, relationship, subType, identifier, basisOfRecord, resourceUrl, verbatimSciname, locationOnHost, notes, createdUid) '.
			'VALUES('.$postArr['occid'].','.(isset($postArr['occidAssoc']) && $postArr['occidAssoc']?$this->cleanInStr($postArr['occidAssoc']):'NULL').','.
			($postArr['relationship']?'"'.$this->cleanInStr($postArr['relationship']).'"':'NULL').','.
			($postArr['subtype']?'"'.$this->cleanInStr($postArr['subtype']).'"':'NULL').','.
			($postArr['identifier']?'"'.$this->cleanInStr($postArr['identifier']).'"':'NULL').','.
			($postArr['basisofrecord']?'"'.$this->cleanInStr($postArr['basisofrecord']).'"':'NULL').','.
			($postArr['resourceurl']?'"'.$this->cleanInStr($postArr['resourceurl']).'"':'NULL').','.
			($postArr['verbatimsciname']?'"'.$this->cleanInStr($postArr['verbatimsciname']).'"':'NULL').','.
			($postArr['locationonhost']?'"'.$this->cleanInStr($postArr['locationonhost']).'"':'NULL').','.
			($postArr['notes']?'"'.$this->cleanInStr($postArr['notes']).'"':'NULL').','.
			$GLOBALS['SYMB_UID'].')';
		if(!$this->conn->query($sql)){
			$this->errorArr = 'ERROR saving occurrence association: '.$this->conn->error;
			$status = false;
		}
		return $status;
	}

	public function deleteAssociation($assocID){
		$status = true;
		if(is_numeric($assocID)){
			$sql = 'DELETE FROM omoccurassociations WHERE associd = '.$assocID;
			if(!$this->conn->query($sql)){
				$this->errorArr = 'ERROR deleting occurrence association: '.$this->conn->error;
				$status = false;
			}
		}
		return $status;
	}

	public function getRelationshipArr(){
		if(!$this->relationshipArr) $this->setRelationshipArr();
		return $this->relationshipArr;
	}

	public function getSubtypeArr(){
		$retArr = array();
		$sql = 'SELECT t.term FROM ctcontrolvocabterm t INNER JOIN ctcontrolvocab v  ON t.cvid = v.cvid WHERE v.tableName = "omoccurassociations" AND v.fieldName = "subType" ORDER BY t.term';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[] = $r->term;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getOccurrenceByIdentifier($id,$target,$collidTarget){
		//Used within occurrence editor rpc getAssocOccurrence AJAX call
		$retArr = array();
		$id = $this->cleanInStr($id);
		$sqlWhere = '';
		if($target == 'occid'){
			if(is_numeric($id)) $sqlWhere .= 'AND (occid = '.$id.') ';
		}
		else $sqlWhere .= 'AND ((catalogNumber = "'.$id.'") OR (othercatalognumbers = "'.$id.'")) ';
		if($sqlWhere){
			$sql = 'SELECT o.occid, o.catalogNumber, o.otherCatalogNumbers, o.recordedBy, o.recordNumber, IFNULL(o.eventDate,o.verbatimEventDate) as eventDate, '.
				'CONCAT_WS("-",c.institutionCode,c.collectionCode) AS collcode '.
				'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid WHERE '.substr($sqlWhere, 4);
			if($collidTarget && is_numeric($collidTarget)) $sql .= ' AND (o.collid = '.$collidTarget.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$catNum = '';
				if(strpos($r->catalogNumber,$r->collcode) === false) $catNum = $r->collcode.':';
				$catNum .= $r->catalogNumber;
				if($r->otherCatalogNumbers){
					if($catNum) $catNum .= ' ('.$r->otherCatalogNumbers.')';
					else $catNum = $r->otherCatalogNumbers;
				}
				$retArr[$r->occid]['catnum'] = $catNum;
				$retArr[$r->occid]['collinfo'] = $r->recordedBy.($r->recordNumber?' ('.$r->recordNumber.')':'').' '.$r->eventDate;
			}
			$rs->free();
		}
		return $retArr;
	}

	//Checklist voucher functions
	public function getVoucherChecklists(){
		$retArr = array();
		$sql = 'SELECT c.clid, c.name
			FROM fmchecklists c INNER JOIN fmchklsttaxalink ctl ON c.clid = ctl.clid
			INNER JOIN fmvouchers v ON ctl.clTaxaID = v.clTaxaID
			WHERE v.occid = '.$this->occid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->clid] = $r->name;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	//Genetic link functions
	public function getGeneticArr(){
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT idoccurgenetic, identifier, resourcename, locus, resourceurl, notes FROM omoccurgenetic WHERE occid = '.$this->occid;
			$result = $this->conn->query($sql);
			if($result){
				while($r = $result->fetch_object()){
					$retArr[$r->idoccurgenetic]['id'] = $r->identifier;
					$retArr[$r->idoccurgenetic]['name'] = $r->resourcename;
					$retArr[$r->idoccurgenetic]['locus'] = $r->locus;
					$retArr[$r->idoccurgenetic]['resourceurl'] = $r->resourceurl;
					$retArr[$r->idoccurgenetic]['notes'] = $r->notes;
				}
				$result->free();
			}
			else{
				trigger_error('Unable to get genetic data; '.$this->conn->error,E_USER_WARNING);
			}
		}
		return $retArr;
	}
}
?>