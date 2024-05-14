<?php
include_once 'OccurrenceEditorManager.php';
include_once 'OmAssociations.php';

class OccurrenceEditorResource extends OccurrenceEditorManager {

	private $assocManager = null;

	public function __construct($conn = null){
		parent::__construct();
		$this->assocManager = new OmAssociations($this->conn);
	}

	public function __destruct(){
		parent::__destruct();
	}

	//Occurrence relationships
	public function getOccurrenceRelationships(){
		$retArr = array();
		$this->assocManager->setOccid($this->occid);
		$retArr = $this->assocManager->getAssociationArr('FULL');
		foreach($retArr as $assocID => $assocArr){
			$createdBy = '';
			if(!empty($assocArr['modifiedBy'])) $createdBy = $assocArr['modifiedBy'];
			elseif(!empty($assocArr['createdBy'])) $createdBy = $assocArr['createdBy'];
			$retArr[$assocID]['definedBy'] = $createdBy;
		}
		return $retArr;
	}

	public function addAssociation($postArr){
		$status = true;
		$this->assocManager->setOccid($postArr['occid']);
		$status = $this->assocManager->insertAssociation($postArr);
		if(!$status) $this->errorArr[] = $this->assocManager->getErrorMessage();
		return $status;
	}

	public function updateAssociation($postArr){
		$status = true;
		$this->assocManager->setOccid($postArr['occid']);
		$this->assocManager->setAssocID($postArr['assocID']);
		$status = $this->assocManager->updateAssociation($postArr);
		if(!$status) $this->errorArr[] = $this->assocManager->getErrorMessage();
		return $status;
	}

	public function deleteAssociation($assocID){
		$status = true;
		if(is_numeric($assocID)){
			$this->assocManager->setAssocID($assocID);
			$status = $this->assocManager->deleteAssociation();
			if(!$status) $this->errorArr[] = $this->assocManager->getErrorMessage();
		}
		return $status;
	}

	public function getAssociationTypeArr(){
		return $this->assocManager->getControlledVocab('associationType');
	}

	public function getRelationshipArr(){
		return array_keys($this->assocManager->getControlledVocab('relationship'));
	}

	public function getResourceRelationshipArr(){
		return array_keys($this->assocManager->getControlledVocab('relationship', 'associationType:resource'));
	}

	public function getSubtypeArr(){
		return $this->assocManager->getControlledVocab('subType');
	}

	//RPC calls: Used within occurrence editor rpc getAssocOccurrence AJAX call
	public function getOccurrenceByIdentifier($id,$target,$collidTarget){
		$retArr = array();
		$id = $this->cleanInStr($id);
		$sqlWhere = '';
		if($target == 'occid'){
			if(is_numeric($id)) $sqlWhere .= 'AND (occid = '.$id.') ';
		}
		else $sqlWhere .= 'AND ((catalogNumber LIKE "'.$id.'") OR (othercatalognumbers = "'.$id.'")) ';
		if($sqlWhere){
			$sql = 'SELECT o.occid, o.catalogNumber, o.otherCatalogNumbers, o.recordedBy, o.recordNumber, IFNULL(o.eventDate,o.verbatimEventDate) as eventDate, '.
				'CONCAT_WS("-",c.institutionCode,c.collectionCode) AS collcode, o.sciname, o.tidInterpreted '.
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
				$retArr[$r->occid]['sciname'] = $r->sciname;
				$retArr[$r->occid]['tid'] = $r->tidInterpreted;
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