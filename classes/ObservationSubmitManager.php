<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/ImageShared.php');

class ObservationSubmitManager {

	private $conn;
	private $collId;
	private $collMap = Array();

	private $errArr = array();

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function addObservation($postArr){
		$newOccId = '';
		if($postArr && $this->collId){
			//Setup Event Date fields
			$eventYear = 'NULL'; $eventMonth = 'NULL'; $eventDay = 'NULL'; $startDay = 'NULL';
			if($dateObj = strtotime($postArr['eventdate'])){
				$eventYear = date('Y',$dateObj);
				$eventMonth = date('m',$dateObj);
				$eventDay = date('d',$dateObj);
				$startDay = date('z',$dateObj)+1;
			}
			//Get tid for scinetific name
			$tid = 0;
			$localitySecurity = (array_key_exists('localitysecurity',$postArr)?1:0);
			if($postArr['sciname']){
				$result = $this->conn->query('SELECT tid, securitystatus FROM taxa WHERE (sciname = "'.$postArr['sciname'].'")');
				if($row = $result->fetch_object()){
					$tid = $row->tid;
					if($row->securitystatus > 0) $localitySecurity = $row->securitystatus;
					if(!$localitySecurity){
						//Check to see if species is rare or sensitive within a state
						$sql = 'SELECT cl.tid '.
							'FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid '.
							'WHERE c.type = "rarespp" AND c.locality = "'.$postArr['stateprovince'].'" AND cl.tid = '.$tid;
						$rs = $this->conn->query($sql);
						if($rs->num_rows){
							$localitySecurity = 1;
						}
					}
				}
			}

			$verbatimElevation = $postArr['verbatimelevation'];
			if(is_numeric($verbatimElevation)) $verbatimElevation .= 'ft.';
			$sql = 'INSERT INTO omoccurrences(collid, basisofrecord, family, sciname, scientificname, '.
				'scientificNameAuthorship, tidinterpreted, taxonRemarks, identifiedBy, dateIdentified, '.
				'identificationReferences, recordedBy, recordNumber, '.
				'associatedCollectors, eventDate, year, month, day, startDayOfYear, habitat, substrate, occurrenceRemarks, associatedTaxa, '.
				'verbatimattributes, reproductiveCondition, cultivationStatus, establishmentMeans, country, '.
				'stateProvince, county, locality, localitySecurity, decimalLatitude, decimalLongitude, '.
				'geodeticDatum, coordinateUncertaintyInMeters, georeferenceRemarks, minimumElevationInMeters, verbatimElevation, observeruid, dateEntered) '.
				'VALUES ('.$this->collId.',"HumanObservation",'.($postArr['family']?'"'.$this->cleanInStr($postArr['family']).'"':'NULL').','.
				'"'.$this->cleanInStr($postArr['sciname']).'","'.
				$this->cleanInStr($postArr['sciname'].' '.$postArr['scientificnameauthorship']).'",'.
				($postArr['scientificnameauthorship']?'"'.$this->cleanInStr($postArr['scientificnameauthorship']).'"':'NULL').','.
				($tid?$tid:'NULL').','.($postArr['taxonremarks']?'"'.$this->cleanInStr($postArr['taxonremarks']).'"':'NULL').','.
				($postArr['identifiedby']?'"'.$this->cleanInStr($postArr['identifiedby']).'"':'NULL').','.
				($postArr['dateidentified']?'"'.$this->cleanInStr($postArr['dateidentified']).'"':'NULL').','.
				($postArr['identificationreferences']?'"'.$this->cleanInStr($postArr['identificationreferences']).'"':'NULL').','.
				'"'.$this->cleanInStr($postArr['recordedby']).'",'.
				($postArr['recordnumber']?'"'.$this->cleanInStr($postArr['recordnumber']).'"':'NULL').','.
				($postArr['associatedcollectors']?'"'.$this->cleanInStr($postArr['associatedcollectors']).'"':'NULL').','.
				'"'.$postArr['eventdate'].'",'.$eventYear.','.$eventMonth.','.$eventDay.','.$startDay.','.
				($postArr['habitat']?'"'.$this->cleanInStr($postArr['habitat']).'"':'NULL').','.
				($postArr['substrate']?'"'.$this->cleanInStr($postArr['substrate']).'"':'NULL').','.
				($postArr['occurrenceremarks']?'"'.$this->cleanInStr($postArr['occurrenceremarks']).'"':'NULL').','.
				($postArr['associatedtaxa']?'"'.$this->cleanInStr($postArr['associatedtaxa']).'"':'NULL').','.
				($postArr['verbatimattributes']?'"'.$this->cleanInStr($postArr['verbatimattributes']).'"':'NULL').','.
				($postArr['reproductivecondition']?'"'.$this->cleanInStr($postArr['reproductivecondition']).'"':'NULL').','.
				(array_key_exists('cultivationstatus',$postArr)?'1':'0').','.
				($postArr['establishmentmeans']?'"'.$this->cleanInStr($postArr['establishmentmeans']).'"':'NULL').','.
				'"'.$this->cleanInStr($postArr['country']).'",'.
				($postArr['stateprovince']?'"'.$this->cleanInStr($postArr['stateprovince']).'"':'NULL').','.
				($postArr['county']?'"'.$this->cleanInStr($postArr['county']).'"':'NULL').','.
				'"'.$this->cleanInStr($postArr['locality']).'",'.$localitySecurity.','.
				$postArr['decimallatitude'].','.$postArr['decimallongitude'].','.
				($postArr['geodeticdatum']?'"'.$this->cleanInStr($postArr['geodeticdatum']).'"':'NULL').','.
				($postArr['coordinateuncertaintyinmeters']?'"'.$postArr['coordinateuncertaintyinmeters'].'"':'NULL').','.
				($postArr['georeferenceremarks']?'"'.$this->cleanInStr($postArr['georeferenceremarks']).'"':'NULL').','.
				($postArr['minimumelevationinmeters']?$postArr['minimumelevationinmeters']:'NULL').','.
				($postArr['verbatimelevation']?'"'.$verbatimElevation.'"':'NULL').','.
				$GLOBALS['SYMB_UID'].',"'.date('Y-m-d H:i:s').'") ';
			//echo $sql;
			if($this->conn->query($sql)){
				$newOccId = $this->conn->insert_id;
				//Link observation to checklist
				if(isset($postArr['clid'])){
					$clid = $postArr['clid'];
					$clTaxaID = 0;
					if($tid){
						//If synonym is already linked, get tid of linked taxon. If not, then add using current tid
						$sql = 'SELECT cltl.tid, cltl.clTaxaID '.
							'FROM fmchklsttaxalink cltl INNER JOIN taxstatus ts1 ON cltl.tid = ts1.tid '.
							'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
							'WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND cltl.clid = '.$clid.' AND ts2.tid = '.$tid;
						$rs = $this->conn->query($sql);
						while($r = $rs->fetch_object()){
							$clTaxaID = $r->clTaxaID;
							if($r->tid == $tid) break;
						}
						$rs->free();
						if(!$clTaxaID){
							$sql = 'INSERT INTO fmchklsttaxalink(tid,clid) VALUES(?,?)';
							if($stmt = $this->conn->prepare($sql)){
								$stmt->bind_param('ii', $tid, $clid);
								$stmt->execute();
								$clTaxaID = $stmt->insert_id;
								$stmt->close();
							}
						}
						if($clTaxaID){
							$sql = 'INSERT INTO fmvouchers(clTaxaID, occid) VALUES(?,?) ';
							if($stmt = $this->conn->prepare($sql)){
								$stmt->bind_param('ii', $clTaxaID, $newOccId);
								$stmt->execute();
								$stmt->close();
							}
						}
					}
				}
				//Load images
				if(!$this->addImages($postArr,$newOccId,$tid)){
					$this->errArr[] = 'Observation added successfully, but images did not upload successful';
				}
				//Set verification status
				if(isset($postArr['confidenceranking']) && is_numeric($postArr['confidenceranking'])){
					$sqlVer = 'INSERT INTO omoccurverification(occid,category,ranking,uid) VALUES('.$newOccId.',"identification",'.$postArr['confidenceranking'].','.$GLOBALS['SYMB_UID'].')';
					if(!$this->conn->query($sqlVer)){
						$statusStr .= 'WARNING adding confidence ranking failed ('.$this->conn->error.') ';
					}
				}
			}
			else{
				$this->errArr[] = 'ERROR: Failed to load observation record.<br/> Err Descr: '.$this->conn->error;
			}
		}
		return $newOccId;
	}

	private function addImages($postArr,$newOccId,$tid){
		$status = true;
		$imgManager = new ImageShared();
		//Set target path
		$subTargetPath = $this->collMap['institutioncode'];
		if($this->collMap['collectioncode']) $subTargetPath .= '_'.$this->collMap['collectioncode'];

		for($i=1;$i<=5;$i++){
			//Set parameters
			$imgManager->reset();
			$imgManager->setTargetPath($subTargetPath.'/'.date('Ym').'/');
			//$imgManager->setMapLargeImg(false);
			$imgManager->setPhotographerUid($GLOBALS['SYMB_UID']);
			$imgManager->setSortSeq(40);
			$imgManager->setOccid($newOccId);
			$imgManager->setTid($tid);

			$imgFileName = 'imgfile'.$i;
			if(!array_key_exists($imgFileName,$_FILES) || !$_FILES[$imgFileName]['name']) break;
			//Set image metadata variables
			if(isset($postArr['caption'.$i])) $imgManager->setCaption($postArr['caption'.$i]);
			if(isset($postArr['notes'.$i])) $imgManager->setNotes($postArr['notes'.$i]);

			//Image is a file upload
			if($imgManager->uploadImage($imgFileName)){
				$status = $imgManager->processImage();
			}
			else{
				$status = false;
			}
			if(!$status){
				//Get errors and warnings
				if($errArr = $imgManager->getErrArr()) {
					foreach($errArr as $errStr){
						$this->errArr[] = $errStr;
					}
				}
			}
			$imgManager->reset();
		}
		return $status;
	}

	public function getChecklists(){
		$retArr = Array();
		if(isset($GLOBALS['USER_RIGHTS']['ClAdmin'])){
			$sql = 'SELECT clid, name, access '.
				'FROM fmchecklists '.
				'WHERE clid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ClAdmin']).') '.
				'ORDER BY name';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$retArr[$row->clid] = $row->name.($row->access == 'private'?' (private)':'');
			}
		}
		return $retArr;
	}

	public function getCollMap(){
		return $this->collMap;
	}

	public function getErrorArr(){
		return $this->errArr;
	}

	public function setCollid($id){
		if(is_numeric($id)) $this->collId = $id;
		$this->setMetadata();
	}

	private function setMetadata(){
		$sql = 'SELECT collid, institutioncode, collectioncode, collectionname, colltype FROM omcollections ';
		if($this->collId){
			$sql .= 'WHERE (collid = '.$this->collId.')';
		}
		else{
			$sql .= 'WHERE (colltype = "General Observations")';
		}
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$this->collMap['collid'] = $r->collid;
			$this->collMap['institutioncode'] = $r->institutioncode;
			$this->collMap['collectioncode'] = $r->collectioncode;
			$this->collMap['collectionname'] = $this->cleanOutStr($r->collectionname);
			$this->collMap['colltype'] = $r->colltype;
			if(!$this->collId){
				$this->collId = $r->collid;
			}
		}
		$rs->free();
	}

	public function getUserName(){
		$retStr = '';
		if(is_numeric($GLOBALS['SYMB_UID'])){
			$sql = 'SELECT CONCAT_WS(", ",lastname,firstname) AS username FROM users WHERE uid = '.$GLOBALS['SYMB_UID'];
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retStr = $r->username;
			}
			$rs->free();
		}
		return $retStr;
	}

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>