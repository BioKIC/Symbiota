<?php
include_once('Manager.php');
include_once('OccurrenceUtilities.php');
include_once('UuidFactory.php');

class OmDeterminations extends Manager{

	private $detID = null;
	private $occid = null;
	private $schemaMap = array();
	private $parameterArr = array();
	private $typeStr = '';

	public function __construct($conn){
		parent::__construct(null, 'write', $conn);
		$this->schemaMap = array('identifiedBy' => 's', 'dateIdentified' => 's', 'higherClassification' => 's', 'family' => 's', 'sciname' => 's', 'verbatimIdentification' => 's',
			'scientificNameAuthorship' => 's', 'identificationUncertain' => 'i', 'identificationQualifier' => 's', 'isCurrent' => 'i', 'printQueue' => 'i', 'appliedStatus' => 'i',
			'securityStatus' => 'i', 'securityStatusReason' => 's', 'detType' => 's', 'identificationReferences' => 's', 'identificationRemarks' => 's', 'taxonRemarks' => 's',
			'identificationVerificationStatus' => 's', 'taxonConceptID' => 's', 'sourceIdentifier' => 's', 'sortSequence' => 'i');
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getDeterminationArr($filterArr = null){
		$retArr = array();
		$uidArr = array();
		$sql = 'SELECT detID, occid, '.implode(', ', array_keys($this->schemaMap)).', initialTimestamp FROM omoccurdeterminations WHERE ';
		if($this->detID) $sql .= '(detID = '.$this->detID.') ';
		elseif($this->occid) $sql .= '(occid = '.$this->occid.') ';
		foreach($filterArr as $field => $cond){
			$sql .= 'AND '.$field.' = "'.$this->cleanInStr($cond).'" ';
		}
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_assoc()){
				$retArr[$r['detID']] = $r;
				$uidArr[$r['createdUid']] = $r['createdUid'];
				$uidArr[$r['modifiedUid']] = $r['modifiedUid'];
			}
			$rs->free();
		}
		if($uidArr){
			//Add user names for modified and created by
			$sql = 'SELECT uid, firstname, lastname, username FROM users WHERE uid IN('.implode(',', $uidArr).')';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$uidArr[$r->uid] = $r->lastname . ($r->firstname ? ', ' . $r->firstname : '');
				}
				$rs->free();
			}
			foreach($retArr as $detID => $detArr){
				if($detArr['createdUid'] && array_key_exists($detArr['createdUid'], $uidArr)) $retArr[$detID]['createdBy'] = $uidArr[$detArr['createdUid']];
				if($detArr['modifiedUid'] && array_key_exists($detArr['modifiedUid'], $uidArr)) $retArr[$detID]['modifiedBy'] = $uidArr[$detArr['modifiedUid']];
			}
		}
		return $retArr;
	}

	public function insertDetermination($inputArr){
		$status = false;
		if($this->occid){
			if(!isset($inputArr['createdUid'])) $inputArr['createdUid'] = $GLOBALS['SYMB_UID'];
			$sql = 'INSERT INTO omoccurdeterminations(occid, recordID';
			$sqlValues = '?, ?, ';
			$paramArr = array($this->occid);
			$paramArr[] = UuidFactory::getUuidV4();
			$this->typeStr = 'is';
			$this->setParameterArr($inputArr);
			foreach($this->parameterArr as $fieldName => $value){
				$sql .= ', '.$fieldName;
				$sqlValues .= '?, ';
				$paramArr[] = $value;
			}
			$sql .= ') VALUES('.trim($sqlValues, ', ').') ';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param($this->typeStr, ...$paramArr);
				if($stmt->execute()){
					if($stmt->affected_rows || !$stmt->error){
						$this->detID = $stmt->insert_id;
						$status = true;
					}
					else $this->errorMessage = 'ERROR inserting omoccurdeterminations record (2): '.$stmt->error;
				}
				else $this->errorMessage = 'ERROR inserting omoccurdeterminations record (1): '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for omoccurdeterminations insert: '.$this->conn->error;
		}
		return $status;
	}

	public function updateDetermination($inputArr){
		$status = false;
		if($this->detID && $this->conn){
			$this->setParameterArr($inputArr);
			$paramArr = array();
			$sqlFrag = '';
			foreach($this->parameterArr as $fieldName => $value){
				$sqlFrag .= $fieldName . ' = ?, ';
				$paramArr[] = $value;
			}
			$paramArr[] = $this->detID;
			$this->typeStr .= 'i';
			$sql = 'UPDATE omoccurdeterminations SET '.trim($sqlFrag, ', ').' WHERE (detID = ?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param($this->typeStr, ...$paramArr);
				$stmt->execute();
				if($stmt->affected_rows || !$stmt->error) $status = true;
				else $this->errorMessage = 'ERROR updating omoccurdeterminations record: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for updating omoccurdeterminations: '.$this->conn->error;
		}
		return $status;
	}

	private function setParameterArr($inputArr){
		foreach($this->schemaMap as $field => $type){
			$postField = '';
			if(isset($inputArr[$field])) $postField = $field;
			elseif(isset($inputArr[strtolower($field)])) $postField = strtolower($field);
			if($postField){
				$value = trim($inputArr[$postField]);
				if($value){
					$postField = strtolower($postField);
					if($postField == 'establisheddate') $value = OccurrenceUtilities::formatDate($value);
					if($postField == 'modifieduid') $value = OccurrenceUtilities::verifyUser($value, $this->conn);
					if($postField == 'createduid') $value = OccurrenceUtilities::verifyUser($value, $this->conn);
					if($postField == 'identificationuncertain' || $postField == 'iscurrent' || $postField == 'printqueue' || $postField == 'appliedstatus' || $postField == 'securitystatus'){
						if(!is_numeric($value)){
							$value = strtolower($value);
							if($value == 'yes' || $value == 'true') $value = 1;
							else $value = 0;
						}
					}
					if($postField == 'sortsequence'){
						if(!is_numeric($value)) $value = 10;
					}
				}
				else $value = null;
				$this->parameterArr[$field] = $value;
				$this->typeStr .= $type;
			}
		}
		if(isset($inputArr['occid']) && $inputArr['occid'] && !$this->occid) $this->occid = $inputArr['occid'];
	}

	public function deleteDetermination(){
		if($this->detID){
			$sql = 'DELETE FROM omoccurdeterminations WHERE detID = '.$this->detID;
			if($this->conn->query($sql)){
				return true;
			}
			else{
				$this->errorMessage = 'ERROR deleting omoccurdeterminations record: '.$this->conn->error;
				return false;
			}
		}
	}

	//Setters and getters
	public function setDetID($id){
		if(is_numeric($id)) $this->detID = $id;
	}

	public function getDetID(){
		return $this->detID;
	}

	public function setOccid($id){
		if(is_numeric($id)) $this->occid = $id;
	}

	public function getSchemaMap(){
		return $this->schemaMap;
	}
}
?>