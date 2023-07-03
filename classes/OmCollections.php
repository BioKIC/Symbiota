<?php
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/classes/UuidFactory.php');

class OmCollections extends Manager{

	protected $collid;

	public function __construct($connType = 'write'){
		parent::__construct(null,$connType);
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function collectionUpdate($postArr){
		$status = false;
		if($this->collid){
			$reqArr = $this->getRequestArr($postArr);
			//Update core fields
			$sql = 'UPDATE omcollections '.
				'SET institutionCode = ?, collectionCode = ?, collectionName = ?, collectionID = ?, fullDescription = ?, latitudeDecimal = ?, longitudeDecimal = ?, publishToGbif = ?, '.
				'publishToIdigbio = ?, publicEdits = ?, guidTarget = ?, rights = ?, rightsHolder = ?, accessRights = ?, icon = ?, individualUrl = ? '.
				'WHERE (collid = ?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param('sssssddiiissssssi', $reqArr['institutionCode'], $reqArr['collectionCode'], $reqArr['collectionName'], $reqArr['collectionID'], $reqArr['fullDescription'],
					$reqArr['latitudeDecimal'], $reqArr['longitudeDecimal'], $reqArr['publishToGbif'], $reqArr['publishToIdigbio'], $reqArr['publicEdits'], $reqArr['guidTarget'],
					$reqArr['rights'], $reqArr['rightsHolder'], $reqArr['accessRights'], $reqArr['icon'], $reqArr['individualUrl'], $this->collid);
				$stmt->execute();
				if($stmt->affected_rows || !$stmt->error) $status = true;
				else $this->errorMessage = 'ERROR updating omcollections record: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for updating omcollections: '.$this->conn->error;
			//Update SuperAdmin limited fields
			if(isset($reqArr['managementType']) && $GLOBALS['IS_ADMIN']){
				$sql = 'UPDATE omcollections SET managementType = ?, collType = ?, sortSeq = ? WHERE (collid = ?)';
				if($stmt = $this->conn->prepare($sql)) {
					$stmt->bind_param('ssii', $reqArr['managementType'], $reqArr['collType'], $reqArr['sortSeq'], $this->collid);
					$stmt->execute();
					if($stmt->affected_rows || !$stmt->error) $status = true;
					else $this->errorMessage = 'ERROR updating omcollections admin fields: '.$stmt->error;
					$stmt->close();
				}
				else $this->errorMessage = 'ERROR preparing statement for updating admin fields: '.$this->conn->error;
			}

			//Modify collection category, if needed
			if(isset($reqArr['ccpk'])){
				$rs = $this->conn->query('SELECT ccpk FROM omcollcatlink WHERE collid = '.$this->collid);
				if($r = $rs->fetch_object()){
					if($r->ccpk <> $reqArr['ccpk']){
						if(!$this->conn->query('UPDATE omcollcatlink SET ccpk = '.$reqArr['ccpk'].' WHERE ccpk = '.$r->ccpk.' AND collid = '.$this->collid)){
							$this->errorMessage = 'ERROR updating collection category link: '.$this->conn->error;
						}
					}
				}
				else{
					if(!$this->conn->query('INSERT INTO omcollcatlink (ccpk,collid) VALUES('.$reqArr['ccpk'].','.$this->collid.')')){
						$this->errorMessage = 'ERROR inserting collection category link(1): '.$this->conn->error;
					}
				}
			}
			else $this->conn->query('DELETE FROM omcollcatlink WHERE collid = '.$this->collid);
		}
		if(!$reqArr['securityKey']) $this->addGuid('securityKey');
		if(!$reqArr['collectionGuid']) $this->addGuid('collectionGuid');
		return $status;
	}

	public function collectionInsert($postArr){
		$cid = false;
		$reqArr = $this->getRequestArr($postArr);
		$reqArr['collectionGuid'] = UuidFactory::getUuidV4();
		$reqArr['securityKey'] = UuidFactory::getUuidV4();
		$sql = 'INSERT INTO omcollections(institutionCode, collectionCode, collectionName, collectionID, fullDescription, resourceJson, contactJson, latitudeDecimal, longitudeDecimal, '.
			'publishToGbif, publishToIdigbio, publicEdits, guidTarget, rights, rightsHolder, accessRights, icon, managementType, collType, collectionGuid, securityKey, individualUrl, sortSeq) '.
			'VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('sssssssddiiissssssssssi', $reqArr['institutionCode'], $reqArr['collectionCode'], $reqArr['collectionName'], $reqArr['collectionID'], $reqArr['fullDescription'],
				$reqArr['resourceJson'],$reqArr['contactJson'],$reqArr['latitudeDecimal'], $reqArr['longitudeDecimal'], $reqArr['publishToGbif'], $reqArr['publishToIdigbio'],
				$reqArr['publicEdits'], $reqArr['guidTarget'], $reqArr['rights'], $reqArr['rightsHolder'], $reqArr['accessRights'], $reqArr['icon'], $reqArr['managementType'],
				$reqArr['collType'], $reqArr['collectionGuid'], $reqArr['securityKey'], $reqArr['individualUrl'], $reqArr['sortSeq']);
			if($stmt->execute()){
				if($stmt->affected_rows || !$stmt->error){
					if($cid = $stmt->insert_id){
						$sql = 'INSERT INTO omcollectionstats(collid,recordcnt,uploadedby) VALUES('.$cid.',0,"'.$GLOBALS['USERNAME'].'")';
						$this->conn->query($sql);
						//Add collection to category
						if(isset($reqArr['ccpk']) && is_numeric($reqArr['ccpk'])){
							$sql = 'INSERT INTO omcollcatlink (ccpk,collid) VALUES('.$postArr['ccpk'].','.$cid.')';
							if(!$this->conn->query($sql)){
								//$this->errorMessage = 'ERROR inserting collection category link(2): '.$this->conn->error;
							}
						}
						$this->collid = $cid;
					}
				}
				else $this->errorMessage = 'ERROR inserting omcollections record (2): '.$stmt->error;
			}
			else $this->errorMessage = 'ERROR inserting omcollections record (1): '.$stmt->error;
			$stmt->close();
		}
		else $this->errorMessage = 'ERROR preparing statement for omcollections insert: '.$this->conn->error;
		return $cid;
	}

	private function getRequestArr($postArr){
		$retArr = array();
		$retArr['institutionCode'] = ($postArr['institutionCode']?trim($postArr['institutionCode']):NULL);
		$retArr['collectionCode'] = ($postArr['collectionCode']?trim($postArr['collectionCode']):'');
		$retArr['collectionName'] = ($postArr['collectionName']?trim($postArr['collectionName']):NULL);
		$retArr['collectionID'] = ($postArr['collectionID']?trim($postArr['collectionID']):NULL);
		$retArr['fullDescription'] = ($postArr['fullDescription']?trim($postArr['fullDescription']):NULL);
		$retArr['resourceJson'] = (isset($postArr['resourceJson'])&&$postArr['resourceJson']?$postArr['resourceJson']:NULL);
		$retArr['contactJson'] = (isset($postArr['contactJson'])&&$postArr['contactJson']?$postArr['contactJson']:NULL);
		$retArr['latitudeDecimal'] = (is_numeric($postArr['latitudeDecimal'])?$postArr['latitudeDecimal']:NULL);
		$retArr['longitudeDecimal'] = (is_numeric($postArr['longitudeDecimal'])?$postArr['longitudeDecimal']:NULL);
		$retArr['publishToGbif'] = (isset($postArr['publishToGbif']) && is_numeric($postArr['publishToGbif'])?$postArr['publishToGbif']:NULL);
		$retArr['publishToIdigbio'] = (isset($postArr['publishToIdigbio']) && is_numeric($postArr['publishToIdigbio'])?$postArr['publishToIdigbio']:NULL);
		$retArr['publicEdits'] = (isset($postArr['publicEdits']) && is_numeric($postArr['publicEdits'])?$postArr['publicEdits']:0);
		$retArr['guidTarget'] = (isset($postArr['guidTarget']) && $postArr['guidTarget']?trim($postArr['guidTarget']):NULL);
		$retArr['rights'] = ($postArr['rights']?trim($postArr['rights']):NULL);
		$retArr['rightsHolder'] = ($postArr['rightsHolder']?trim($postArr['rightsHolder']):NULL);
		$retArr['accessRights'] = ($postArr['accessRights']?trim($postArr['accessRights']):NULL);
		$retArr['individualUrl'] = ($postArr['individualUrl']?trim($postArr['individualUrl']):NULL);
		if(isset($_FILES['iconFile']['name']) && $_FILES['iconFile']['name']) $retArr['icon'] = $this->addIconImageFile($postArr);
		elseif(isset($postArr['iconUrl']) && $postArr['iconUrl']) $retArr['icon'] = trim($postArr['iconUrl']);
		elseif(isset($postArr['icon']) && $postArr['icon']) $retArr['icon'] = trim($postArr['icon']);
		else $retArr['icon'] = NULL;
		if(isset($postArr['managementType']) && $GLOBALS['IS_ADMIN']){
			$retArr['managementType'] = trim($postArr['managementType']);
			$retArr['collType'] = trim($postArr['collType']);
			if(isset($postArr['sortSeq']) && is_numeric($postArr['sortSeq'])) $retArr['sortSeq'] = $postArr['sortSeq'];
		}
		if(isset($postArr['ccpk']) && is_numeric($postArr['ccpk'])) $retArr['ccpk'] = $postArr['ccpk'];
		$retArr['securityKey'] = (isset($postArr['securityKey'])?$postArr['securityKey']:NULL);
		$retArr['recordID'] = (isset($postArr['recordID'])?$postArr['recordID']:NULL);
		return $retArr;
	}

	private function addIconImageFile($postArr){
		$targetPath = $GLOBALS['SERVER_ROOT'].'/content/collicon/';
		$urlBase = $this->getDomain().$GLOBALS['CLIENT_ROOT'].'/content/collicon/';

		//Clean file name
		$fileName = basename($_FILES['iconFile']['name']);
		$imgExt = '';
		if($p = strrpos($fileName,'.')) $imgExt = strtolower(substr($fileName,$p));
		$fileName = strtolower($postArr['institutionCode'].($postArr['collectionCode']?'-'.$postArr['collectionCode']:''));
		$fileName = preg_replace('/[^a-z_]+/', '',str_replace(array('%20','%23',' ','__'),'_',$fileName));
		if(strlen($fileName) > 30) $fileName = substr($fileName,0,30);
		$fileName .= $imgExt;

		//Upload file
		$fullUrl = '';
		if(move_uploaded_file($_FILES['iconFile']['tmp_name'], $targetPath.$fileName)) $fullUrl = $urlBase.$fileName;
		return $fullUrl;
	}

	private function addGuid($fieldName){
		if($this->collid && $fieldName){
			$guid= UuidFactory::getUuidV4();
			$sql = 'UPDATE omcollections SET '.$fieldName.' = "'.$guid.'" WHERE '.$fieldName.' IS NULL AND collid = '.$this->collid;
			$this->conn->query($sql);
		}
	}

	//Resource link functions
	public function saveResourceLink($postArr){
		if($this->collid){
			$sql = 'UPDATE omcollections SET resourceJson = '.($postArr['resourcejson']?'"'.$this->cleanInStr($postArr['resourcejson']).'"':'NULL').' WHERE collid = '.$this->collid;
			if(!$this->conn->query($sql)){
				$this->errorMessage = 'ERROR updating resource link: '.$this->conn->error;
				return false;
			}
		}
		return true;
	}

	//Collection contact functions
	public function saveContact($postArr){
		$modArr = array();
		$contactArr = $this->getContactArr();
		if($postArr['firstName']) $modArr['firstName'] = $postArr['firstName'];
		if($postArr['lastName']) $modArr['lastName'] = $postArr['lastName'];
		if($postArr['role']) $modArr['role'] = $postArr['role'];
		if($postArr['email']) $modArr['email'] = $postArr['email'];
		if(isset($postArr['centralContact']) && $postArr['centralContact']){
			$modArr['centralContact'] = $postArr['centralContact'];
			if($contactArr){
				foreach($contactArr as $cIndex => $cArr){
					if(isset($cArr['centralContact'])) unset($contactArr[$cIndex]['centralContact']);
				}
			}
		}
		if($postArr['phone']) $modArr['phone'] = $postArr['phone'];
		if($postArr['orcid']){
			if(preg_match('/(\d{4}-\d{4}-\d{4}-\d{4})/', $postArr['orcid'], $m)){
				$modArr['orcid'] = $m[1];
			}
		}
		$contactIndex = $postArr['contactIndex'];
		if(is_numeric($contactIndex)) $contactArr[$contactIndex] = $modArr;
		else $contactArr[] = $modArr;
		return $this->updateContactJson($contactArr);
	}

	public function deleteContact($index){
		$contactArr = $this->getContactArr();
		unset($contactArr[$index]);
		return $this->updateContactJson($contactArr);
	}

	private function updateContactJson($contactArr){
		$status = false;
		if($this->collid){
			$contactStr = json_encode(array_values($contactArr));
			$sql = 'UPDATE omcollections SET contactJson = ? WHERE collid = '.$this->collid;
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('s', $contactStr);
				$stmt->execute();
				if($stmt->affected_rows) $status = true;
				elseif($stmt->error) $this->errorMessage = $stmt->error;
				$stmt->close();
			}
		}
		return $status;
	}

	private function getContactArr(){
		$jsonStr = '';
		$sql = 'SELECT contactJson FROM omcollections WHERE collid = '.$this->collid;
		if($rs = $this->conn->query($sql)){
			if($r = $rs->fetch_object()){
				$jsonStr = $r->contactJson;
			}
			$rs->free();
		}
		return json_decode($jsonStr,true);
	}

	//Institution address functions
	public function getAddress(){
		$retArr = Array();
		if($this->collid){
			$sql = 'SELECT i.iid, i.institutioncode, i.institutionname, i.institutionname2, i.address1, i.address2, '.
				'i.city, i.stateprovince, i.postalcode, i.country, i.phone, i.contact, i.email, i.url, i.notes '.
				'FROM institutions i INNER JOIN omcollections c ON i.iid = c.iid '.
				'WHERE (c.collid = '.$this->collid.") ";
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['iid'] = $r->iid;
				$retArr['institutioncode'] = $r->institutioncode;
				$retArr['institutionname'] = $r->institutionname;
				$retArr['institutionname2'] = $r->institutionname2;
				$retArr['address1'] = $r->address1;
				$retArr['address2'] = $r->address2;
				$retArr['city'] = $r->city;
				$retArr['stateprovince'] = $r->stateprovince;
				$retArr['postalcode'] = $r->postalcode;
				$retArr['country'] = $r->country;
				$retArr['phone'] = $r->phone;
				$retArr['contact'] = $r->contact;
				$retArr['email'] = $r->email;
				$retArr['url'] = $r->url;
				$retArr['notes'] = $r->notes;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function linkAddress($addIID){
		$status = false;
		if($this->collid && is_numeric($addIID)){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omcollections SET iid = '.$addIID.' WHERE collid = '.$this->collid;
			if($con->query($sql)){
				$status = true;
			}
			else{
				$this->errorMessage = 'ERROR linking institution address: '.$con->error;
			}
			$con->close();
		}
		return $status;
	}

	public function removeAddress($removeIID){
		$status = false;
		if($this->collid && is_numeric($removeIID)){
			$con = MySQLiConnectionFactory::getCon('write');
			$sql = 'UPDATE omcollections SET iid = NULL WHERE collid = '.$this->collid.' AND iid = '.$removeIID;
			if($con->query($sql)){
				$status = true;
			}
			else{
				$this->errorMessage = 'ERROR removing institution address: '.$con->error;
			}
			$con->close();
		}
		return $status;
	}

	//Setters and getters
	public function setCollid($collid){
		if(is_numeric($collid)) $this->collid = $collid;
	}
}
?>