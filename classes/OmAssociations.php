<?php
include_once('Manager.php');
include_once('utilities/OccurrenceUtil.php');
include_once('utilities/UuidFactory.php');
include_once('AssociationManager.php');

class OmAssociations extends Manager{

	private $assocID = null;
	private $occid = null;
	private $schemaMap = array();
	private $parameterArr = array();
	private $typeStr = '';
	private $controlledVocabArr;
	private $relationshipArr = array();

	public function __construct($conn){
		parent::__construct(null, 'write', $conn);
		$this->schemaMap = array('associationType' => 's', 'occidAssociate' => 'i', 'relationship' => 's', 'relationshipID' => 's', 'subType' => 's', 'objectID' => 's',
			'basisOfRecord' => 's', 'resourceUrl' => 's', 'verbatimSciname' => 's', 'tid' => 'i', 'locationOnHost' => 's', 'conditionOfAssociate' => 's', 'establishedDate' => 's',
			'imageMapJSON' => 's', 'dynamicProperties' => 's', 'notes' => 's', 'accordingTo' => 's', 'instanceID' => 's', 'recordID' => 's');
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getAssociationArr($filter = null, $excludeScriptGenerated = true){
		$retArr = array();
		$relOccidArr = array();
		$uidArr = array();
		$sql = 'SELECT assocID, occid, '.implode(', ', array_keys($this->schemaMap)).', modifiedUid, modifiedTimestamp, createdUid, initialTimestamp FROM omoccurassociations WHERE ';
		if($excludeScriptGenerated){
			$sql .= "(basisOfRecord IS NULL OR basisOfRecord != 'scriptGenerated') AND ";
		}
		if($this->assocID) $sql .= '(assocID = '.$this->assocID.') ';
		elseif($filter == 'FULL')$sql .= '(occid = '.$this->occid.' OR occidAssociate = '.$this->occid.') ';
		elseif($this->occid) $sql .= '(occid = '.$this->occid.') ';
		if(is_array($filter)){
			foreach($filter as $field => $cond){
				$sql .= 'AND '.$field.' = "'.$this->cleanInStr($cond).'" ';
			}
		}
		// echo 'sql is: ' . $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_assoc()){
				$retArr[$r['assocID']] = $r;
				if($r['occidAssociate']){
					if($this->occid == $r['occidAssociate']){
						//Reverse relationship to make it relavent to subject occurrence
						$retArr[$r['assocID']]['occidAssociate'] = $r['occid'];
						$retArr[$r['assocID']]['relationship'] = $this->getInverseRelationship($r['relationship']);
					}
					$relOccidArr[$retArr[$r['assocID']]['occidAssociate']][] = $r['assocID'];
				}
				if(isset($r['createdUid'])) $uidArr[$r['createdUid']]['id'][$r['assocID']] = $r['assocID'];
				if(isset($r['modifiedUid'])) $uidArr[$r['modifiedUid']]['id'][$r['assocID']] = $r['assocID'];
			}
			$rs->free();
		}
		if($uidArr){
			//Add user names for modified and created by
			$sql = 'SELECT uid, CONCAT_WS(", ",lastname, firstname) as fullname FROM users WHERE uid IN(' . implode(',', array_keys($uidArr)) . ')';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$uidArr[$r->uid]['n'] = $r->fullname;
				}
				$rs->free();
			}
			foreach($uidArr as $uid => $userArr){
				foreach($userArr['id'] as $id){
					if($uid == $retArr[$id]['createdUid']) $retArr[$id]['createdBy'] = $userArr['n'];
					if($uid == $retArr[$id]['modifiedUid']) $retArr[$id]['modifiedBy'] = $userArr['n'];
				}
			}
		}
		if($relOccidArr){
			//Get catalog numbers and scientific name for object occurrences
			$sql = 'SELECT o.occid, IFNULL(o.institutioncode, c.institutioncode) as instCode, IFNULL(o.collectioncode, c.collectioncode) as collCode, o.catalogNumber, o.sciname
				FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid
				WHERE o.occid IN('.implode(',',array_keys($relOccidArr)).')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$prefix = '';
				if($r->catalogNumber && strpos($r->catalogNumber, $r->instCode) === false){
					$prefix = $r->instCode;
					if($r->collCode) $prefix .= '-' . $r->collCode;
					$prefix .= ':';
				}
				foreach($relOccidArr[$r->occid] as $targetAssocID){
					$retArr[$targetAssocID]['object-catalogNumber'] = $prefix . ($r->catalogNumber ?? 'not defined');
					$retArr[$targetAssocID]['verbatimSciname'] = $r->sciname;
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function insertAssociation($inputArr){
		$status = false;
		if($this->occid){
			$occidAssociate = false;
			if(isset($inputArr['object-catalogNumber']) && $inputArr['object-catalogNumber']){
				$occidAssociate = $this->getOccidAsscoiate($inputArr['object-catalogNumber'], 'catalogNumber');
			}
			elseif(isset($inputArr['object-occurrenceID']) && $inputArr['object-occurrenceID']){
				$occidAssociate = $this->getOccidAsscoiate($inputArr['object-occurrenceID'], 'occurrenceID');
			}
			if($occidAssociate) $inputArr['occidAssociate'] = $occidAssociate;
			elseif($occidAssociate !== false){
				$this->errorMessage = 'Unable to locate internal association record';
				return false;
			}
			if(!isset($inputArr['createdUid'])) $inputArr['createdUid'] = $GLOBALS['SYMB_UID'];
			$sql = 'INSERT INTO omoccurassociations(occid, recordID';
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
			$insertedRecord = null;
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param($this->typeStr, ...$paramArr);
				try{
					if($stmt->execute()){
						if($stmt->affected_rows || !$stmt->error){
							$this->assocID = $stmt->insert_id;
							$status = true;

							$fetchSql = 'SELECT * FROM omoccurassociations WHERE assocID = ?';
							if ($fetchStmt = $this->conn->prepare($fetchSql)) {
								$fetchStmt->bind_param('i', $this->assocID);
								$fetchStmt->execute();
								$result = $fetchStmt->get_result();
								if ($result->num_rows > 0) {
									$insertedRecord = $result->fetch_assoc();
								} else {
									$this->errorMessage = 'Record not found after insertion.';
								}
								$fetchStmt->close();
							}
						}
						else $this->errorMessage = $stmt->error;
					}
				} catch (mysqli_sql_exception $e){
					$this->errorMessage = $stmt->error;
				} catch (Exception $e){
					$this->errorMessage = 'unknown error';
				}
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for omoccurassociations insert: '.$this->conn->error;
		}

		return $status;
	}

	public function updateAssociation($inputArr){
		$status = false;
		if($this->assocID && $this->conn){
			$occidAssociate = false;
			if(isset($inputArr['object-catalogNumber']) && $inputArr['object-catalogNumber']){
				$occidAssociate = $this->getOccidAsscoiate($inputArr['object-catalogNumber'], 'catalogNumber');
			}
			elseif(isset($inputArr['object-occurrenceID']) && $inputArr['object-occurrenceID']){
				$occidAssociate = $this->getOccidAsscoiate($inputArr['object-occurrenceID'], 'occurrenceID');
			}
			if($occidAssociate) $inputArr['occidAssociate'] = $occidAssociate;
			elseif($occidAssociate !== false){
				$this->errorMessage = 'Unable to locate internal association record';
				return false;
			}
			$this->setParameterArr($inputArr);
			$paramArr = array();
			$sqlFrag = '';
			foreach($this->parameterArr as $fieldName => $value){
				$sqlFrag .= $fieldName . ' = ?, ';
				$paramArr[] = $value;
			}
			$paramArr[] = $this->assocID;
			$this->typeStr .= 'i';
			$sql = 'UPDATE IGNORE omoccurassociations SET '.trim($sqlFrag, ', ').' WHERE (assocID = ?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param($this->typeStr, ...$paramArr);
				$stmt->execute();
				if($stmt->affected_rows || !$stmt->error) $status = true;
				else $this->errorMessage = 'ERROR updating omoccurassociations record: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for updating omoccurassociations: '.$this->conn->error;
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
					if(strtolower($postField) == 'establisheddate') $value = OccurrenceUtil::formatDate($value);
					if(strtolower($postField) == 'modifieduid') $value = OccurrenceUtil::verifyUser($value, $this->conn);
					if(strtolower($postField) == 'createduid') $value = OccurrenceUtil::verifyUser($value, $this->conn);
				}
				else $value = null;
				$this->parameterArr[$field] = $value;
				$this->typeStr .= $type;
			}
		}
		if(isset($inputArr['occid']) && $inputArr['occid'] && !$this->occid) $this->occid = $inputArr['occid'];
	}

	public function deleteAssociation(){
		if($this->assocID){
			$sql = 'DELETE FROM omoccurassociations WHERE assocID = '.$this->assocID;
			if($this->conn->query($sql)){
				return true;
			}
			else{
				$this->errorMessage = 'ERROR deleting omoccurassociations record: '.$this->conn->error;
				return false;
			}
		}
	}

	private function getOccidAsscoiate($identifier, $target){
		$occid = 0;
		$identifier = trim($identifier);
		if($identifier){
			$sql = 'SELECT occid FROM omoccurrences WHERE occurrenceID = ? OR recordID = ?';
			if($target == 'catalogNumber') $sql = 'SELECT occid FROM omoccurrences WHERE catalogNumber = ?';
			if($stmt = $this->conn->prepare($sql)){
				if($target == 'catalogNumber') $stmt->bind_param('s', $identifier);
				else $stmt->bind_param('ss', $identifier, $identifier);
				$stmt->execute();
				$stmt->bind_result($occid);
				$stmt->fetch();
				$stmt->close();
			}
		}
		return $occid;
	}

	//Relationships
	private function getInverseRelationship($relationship){
		if(array_key_exists($relationship, $this->relationshipArr)) $relationship = $this->relationshipArr[$relationship];
		return $relationship;
	}

	private function setControlledVocabArr(){
		if(!$this->controlledVocabArr){
			$sql = 'SELECT v.fieldName, v.filterVariable, t.term, t.termDisplay, t.inverseRelationship
				FROM ctcontrolvocabterm t INNER JOIN ctcontrolvocab v  ON t.cvid = v.cvid
				WHERE v.tableName = "omoccurassociations"
				ORDER BY t.termDisplay, t.term';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$filterVariable = 0;
					if($r->filterVariable) $filterVariable = $r->filterVariable;
					if($r->fieldName == 'relationship' && $r->inverseRelationship){
						$this->relationshipArr[$r->term] = $r->inverseRelationship;
						$this->relationshipArr[$r->inverseRelationship] = $r->term;
					}
					else $this->controlledVocabArr[$r->fieldName][$filterVariable][$r->term] = $r->termDisplay;
				}
				$rs->free();
				ksort($this->relationshipArr);
				$this->controlledVocabArr['relationship'][0] = $this->relationshipArr;
			}
		}
	}

	public function getControlledVocab($fieldName, $filterVariable = 0){
		$retArr = array();
		if(!$this->controlledVocabArr) $this->setControlledVocabArr();
		if(isset($this->controlledVocabArr[$fieldName][$filterVariable])) $retArr = $this->controlledVocabArr[$fieldName][$filterVariable];
		return $retArr;
	}

	//Setters and getters
	public function setAssocID($id){
		if(is_numeric($id)) $this->assocID = $id;
	}

	public function getAssocID(){
		return $this->assocID;
	}

	public function setOccid($id){
		if(is_numeric($id)) $this->occid = $id;
	}

	public function getSchemaMap(){
		return $this->schemaMap;
	}
}
?>
