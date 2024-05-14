<?php
include_once('Manager.php');

class InstitutionManager extends Manager{

	private $iid;
	private $collid;

	public function __construct(){
		parent::__construct(null, 'write');
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getInstitutionData(){
		$retArr = Array();
		if($this->iid){
			$sql = 'SELECT iid, institutioncode, institutionname, institutionname2, address1, address2, city, stateprovince, postalcode, country, phone, contact, email, url, notes
				FROM institutions
				WHERE iid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->iid);
				$stmt->execute();
				$rs = $stmt->get_result();
				while($r = $rs->fetch_assoc()){
					$retArr = $r;
				}
				$rs->free();
				$stmt->close();
			}
		}
		return $retArr;
	}

	public function updateInstitution($postData){
		$status = false;
		if(!empty($postData['institutioncode']) && !empty($postData['institutionname'])){
			$institutionCode = $postData['institutioncode'];
			$institutionName = $postData['institutionname'];
			$institutionName2 = !empty($postData['institutionname2']) ? $postData['institutionname2'] : null;
			$address1 = !empty($postData['address1']) ? $postData['address1'] : null;
			$address2 = !empty($postData['address2']) ? $postData['address2'] : null;
			$city = !empty($postData['city']) ? $postData['city'] : null;
			$stateProvince = !empty($postData['stateprovince']) ? $postData['stateprovince'] : null;
			$postalCode = !empty($postData['postalcode']) ? $postData['postalcode'] : null;
			$country = !empty($postData['country']) ? $postData['country'] : null;
			$phone = !empty($postData['phone']) ? $postData['phone'] : null;
			$contact = !empty($postData['contact']) ? $postData['contact'] : null;
			$email = !empty($postData['email']) ? $postData['email'] : null;
			$url = !empty($postData['url']) ? $postData['url'] : null;
			$notes = !empty($postData['notes']) ? $postData['notes'] : null;
			$modifiedUid = $GLOBALS['SYMB_UID'];
			$sql = 'UPDATE institutions SET institutioncode = ?, institutionname = ?, institutionname2 = ?, address1 = ?, address2 = ?, city = ?, stateprovince = ?,
				postalcode = ?, country = ?, phone = ?, contact = ?, email = ?, url = ?, notes = ?, modifiedUid = ?, modifiedTimeStamp = now()
				WHERE iid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('ssssssssssssssii', $institutionCode, $institutionName, $institutionName2, $address1, $address2,
					$city, $stateProvince, $postalCode, $country, $phone, $contact, $email, $url, $notes, $modifiedUid, $postData['iid']);
				$stmt->execute();
				if($stmt->affected_rows || !$stmt->error) $status = true;
				else $this->errorMessage = $stmt->error;
				$stmt->close();
			}
		}
		return $status;
	}

	public function insertInstitution($postData){
		$status = false;
		if(empty($postData['institutioncode']) || empty($postData['institutionname'])){
			$this->errorMessage = 'required field are null';
			return false;
		}
		$institutionCode = $postData['institutioncode'];
		$institutionName = $postData['institutionname'];
		$institutionName2 = !empty($postData['institutionname2']) ? $postData['institutionname2'] : null;
		$address1 = !empty($postData['address1']) ? $postData['address1'] : null;
		$address2 = !empty($postData['address2']) ? $postData['address2'] : null;
		$city = !empty($postData['city']) ? $postData['city'] : null;
		$stateProvince = !empty($postData['stateprovince']) ? $postData['stateprovince'] : null;
		$postalCode = !empty($postData['postalcode']) ? $postData['postalcode'] : null;
		$country = !empty($postData['country']) ? $postData['country'] : null;
		$phone = !empty($postData['phone']) ? $postData['phone'] : null;
		$contact = !empty($postData['contact']) ? $postData['contact'] : null;
		$email = !empty($postData['email']) ? $postData['email'] : null;
		$url = !empty($postData['url']) ? $postData['url'] : null;
		$notes = !empty($postData['notes']) ? $postData['notes'] : null;
		$modifiedUid = $GLOBALS['SYMB_UID'];
		$sql = 'INSERT INTO institutions (institutioncode, institutionname, institutionname2, address1, address2, city, stateprovince, postalcode, country, phone, contact, email, url, notes, modifiedUid, modifiedTimeStamp)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now())';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ssssssssssssssi', $institutionCode, $institutionName, $institutionName2, $address1, $address2,
				$city, $stateProvince, $postalCode, $country, $phone, $contact, $email, $url, $notes, $modifiedUid);
			$stmt->execute();
			if($stmt->affected_rows || !$stmt->error){
				$this->iid = $stmt->insert_id;
				$status = true;
			}
			else $this->errorMessage = $stmt->error;
			$stmt->close();
		}

		if($status && $this->iid){
			if(is_numeric($postData['targetcollid'])){
				$this->updateCollectionLink($postData['targetcollid'], $this->iid);
			}
		}
		return $status;
	}

	public function deleteInstitution($delIid){
		$status = true;
		if($this->verifyInstitutionDeletion($delIid)){
			$sql = 'DELETE FROM institutions WHERE iid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $delIid);
				$stmt->execute();
				if($stmt->affected_rows || !$stmt->error) $status = true;
				else{
					$status = false;
					$this->errorMessage = $stmt->error;
				}
				$stmt->close();
			}
		}
		return $status;
	}

	private function verifyInstitutionDeletion($iid){
		$status = true;
		//Check to see if record is linked to collections
		$sql = 'SELECT CONCAT_WS(" ", collectionName, CONCAT_WS(":", institutionCode, collectionCode)) AS name
			FROM omcollections
			WHERE iid = ?
			ORDER BY collectionName, institutionCode, collectionCode';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $iid);
			$stmt->execute();
			$collectionName = '';
			$stmt->bind_result($collectionName);
			while($stmt->fetch()){
				$this->warningArr[] = $collectionName;
				$status = false;
			}
			$stmt->close();
			if(!$status){
				$this->errorMessage = 'LINKED_COLLECTIONS';
				return false;
			}
		}

		//Check outgoing and incoming loans
		$sql = 'SELECT loanid FROM omoccurloans WHERE iidOwner = ? OR iidBorrower = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $iid, $iid);
			$stmt->execute();
			$loanID = '';
			$stmt->bind_result($loanID);
			while($stmt->fetch()){
				$this->warningArr[] = $loanID;
				$status = false;
			}
			$stmt->close();
			if(!$status){
				$this->errorMessage = 'LINKED_LOANS';
				return false;
			}
		}
		return $status;
	}

	//Collection functions
	public function updateCollectionLink($collid, $iid){
		$status = false;
		$sql = 'UPDATE omcollections SET iid = ? WHERE collid = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $iid, $collid);
			$stmt->execute();
			if($stmt->affected_rows || !$stmt->error) $status = true;
			else $this->errorMessage = $stmt->error;
			$stmt->close();
		}
		return $status;
	}

	//Misc data retrival functions
	public function getInstitutionList(){
		$retArr = Array();
		$sql = 'SELECT i.iid, c.collid, i.institutioncode, i.institutionname
			FROM institutions i LEFT JOIN omcollections c ON i.iid = c.iid
			ORDER BY i.institutionname, i.institutioncode';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if(isset($retArr[$r->iid])){
				$collStr = $retArr[$r->iid]['collid'] . ',' . $r->collid;
				$retArr[$r->iid]['collid'] = $collStr;
			}
			else{
				$retArr[$r->iid]['collid'] = $r->collid;
				$retArr[$r->iid]['institutioncode'] = $this->cleanOutStr($r->institutioncode);
				$retArr[$r->iid]['institutionname'] = $this->cleanOutStr($r->institutionname);
			}
		}
		$rs->free();
		return $retArr;
	}

	public function getCollectionList(){
		$retArr = Array();
		$sql = 'SELECT collid, iid, CONCAT(collectionname, " (", CONCAT_WS("-",institutioncode, collectioncode),")") AS collname
			FROM omcollections
			ORDER BY collectionname, institutioncode';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid]['name'] = $this->cleanOutStr($r->collname);
			$retArr[$r->collid]['iid'] = $r->iid;
		}
		$rs->free();
		return $retArr;
	}

	//Setters and getters
	public function setInstitutionId($id){
		$this->iid = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
	}

	public function getInstitutionId(){
		return $this->iid;
	}
}
?>