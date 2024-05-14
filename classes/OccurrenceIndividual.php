<?php
include_once('Manager.php');
include_once('OccurrenceAccessStats.php');
include_once('ChecklistVoucherAdmin.php');

class OccurrenceIndividual extends Manager{

	private $occid;
	private $collid;
	private $dbpk;
	private $occArr = array();
	private $metadataArr = array();
	private $displayFormat = 'html';
	private $relationshipArr;
	private $activeModules = array();

	public function __construct($type = 'readonly') {
		parent::__construct(null, $type);
	}

	public function __destruct(){
		parent::__destruct();
	}

	private function loadMetadata(){
		if($this->collid){
			//$sql = 'SELECT institutioncode, collectioncode, collectionname, colltype, homepage, individualurl, contact, email, icon, publicedits, rights, rightsholder, accessrights, guidtarget FROM omcollections WHERE collid = ?';
			$sql = 'SELECT c.*, s.uploadDate FROM omcollections c INNER JOIN omcollectionstats s ON c.collid = s.collid WHERE c.collid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->collid);
				$stmt->execute();
				if($rs = $stmt->get_result()){
					$this->metadataArr = array_change_key_case($rs->fetch_assoc());
					if(isset($this->metadataArr['contactjson'])){
						//Test to see if contact is a JSON object or a simple string
						if($contactArr = json_decode($this->metadataArr['contactjson'],true)){
							$contactStr = '';
							foreach($contactArr as $cArr){
								if(!$contactStr || isset($cArr['centralContact'])){
									if(isset($cArr['firstName']) && $cArr['firstName']) $contactStr = $cArr['firstName'].' ';
									$contactStr .= $cArr['lastName'];
									if(isset($cArr['role']) && $cArr['role']) $contactStr .= ', ' . $cArr['role'];
									$this->metadataArr['contact'] = $contactStr;
									if(isset($cArr['email']) && $cArr['email']) $this->metadataArr['email'] = $cArr['email'];
									if(isset($cArr['centralContact'])) break;
								}
							}
						}
					}
					if($this->metadataArr['dynamicproperties']){
						if($propArr = json_decode($this->metadataArr['dynamicproperties'], true)) {
							if(isset($propArr['editorProps']['modules-panel'])) {
								foreach($propArr['editorProps']['modules-panel'] as $k => $modArr) {
									if(isset($modArr['paleo']['status'])) $this->activeModules['paleo'] = true;
									elseif (isset($modArr['matSample']['status'])) $this->activeModules['matSample'] = true;
								}
							}
						}
					}
					$rs->free();
				}
				else{
					$this->errorMessage = $stmt->error;
				}
				$stmt->close();
			}
		}
	}

	public function getMetadata(){
		return $this->cleanOutArray($this->metadataArr);
	}

	public function setGuid($guid){
		if(!$this->occid){
			//Check occurrence recordID
			$sql = 'SELECT occid FROM omoccurrences WHERE (occurrenceid = ?) OR (recordID = ?)';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('ss', $guid, $guid);
				$stmt->execute();
				$stmt->bind_result($this->occid);
				$stmt->close();
			}
		}
		if(!$this->occid){
			//Check image recordID
			$sql = 'SELECT occid FROM images WHERE recordID = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('s', $guid);
				$stmt->execute();
				$stmt->bind_result($this->occid);
				$stmt->close();
			}
		}
		if(!$this->occid){
			//Check identification recordID
			$sql = 'SELECT occid FROM omoccurdeterminations WHERE recordID = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('s', $guid);
				$stmt->execute();
				$stmt->bind_result($this->occid);
				$stmt->close();
			}
		}
		return $this->occid;
	}

	public function getOccData($fieldKey = ""){
		if($this->occid){
			if(!$this->occArr) $this->setOccurData();
			if($fieldKey){
				if(array_key_exists($fieldKey,$this->occArr)) return $this->occArr($fieldKey);
				return false;
			}
		}
		return $this->cleanOutArray($this->occArr);
	}

	public function setOccurData(){
		$status = false;
		/*
		$sql = 'SELECT o.occid, o.collid, o.institutioncode, o.collectioncode,
			o.occurrenceid, o.catalognumber, o.occurrenceremarks, o.tidinterpreted, o.family, o.sciname,
			o.scientificnameauthorship, o.identificationqualifier, o.identificationremarks, o.identificationreferences, o.taxonremarks,
			o.identifiedby, o.dateidentified, o.eventid, o.recordedby, o.associatedcollectors, o.recordnumber, o.eventdate, o.eventdate2, MAKEDATE(YEAR(o.eventDate),o.enddayofyear) AS eventdateend,
			o.verbatimeventdate, o.country, o.stateprovince, o.locationid, o.county, o.municipality, o.locality, o.localitysecurity, o.localitysecurityreason,
			o.decimallatitude, o.decimallongitude, o.geodeticdatum, o.coordinateuncertaintyinmeters, o.verbatimcoordinates, o.georeferenceremarks,
			o.minimumelevationinmeters, o.maximumelevationinmeters, o.verbatimelevation, o.minimumdepthinmeters, o.maximumdepthinmeters, o.verbatimdepth,
			o.verbatimattributes, o.locationremarks, o.lifestage, o.sex, o.individualcount, o.samplingprotocol, o.preparations, o.typestatus, o.dbpk, o.habitat,
			o.substrate, o.associatedtaxa, o.dynamicProperties, o.reproductivecondition, o.cultivationstatus, o.establishmentmeans, o.ownerinstitutioncode,
			o.othercatalognumbers, o.disposition, o.informationwithheld, o.modified, o.observeruid, o.recordenteredby, o.dateentered, o.recordid, o.datelastmodified
			FROM omoccurrences o ';
		*/
		$sql = 'SELECT o.*, MAKEDATE(YEAR(o.eventDate), o.enddayofyear) AS eventdateend FROM omoccurrences o ';
		if($this->occid) $sql .= 'WHERE (o.occid = ?)';
		elseif($this->collid && $this->dbpk) $sql .= 'WHERE (o.collid = ?) AND (o.dbpk = ?)';
		else{
			$this->errorMessage = 'NULL identifier';
			return false;
		}

		if($stmt = $this->conn->prepare($sql)){
			if($this->occid){
				$stmt->bind_param('i', $this->occid);
			}
			elseif($this->collid && $this->dbpk){
				$stmt->bind_param('is', $this->collid, $this->dbpk);
			}
			$stmt->execute();
			if($rs = $stmt->get_result()){
				if($occArr = $rs->fetch_assoc()){
					$rs->free();
					$this->occArr = array_change_key_case($occArr);
					if(!$this->occid) $this->occid = $this->occArr['occid'];
					if(!$this->collid) $this->collid = $this->occArr['collid'];
					$this->loadMetadata();
					if($this->occArr['institutioncode']){
						if($this->metadataArr['institutioncode'] != $this->occArr['institutioncode']) $this->metadataArr['institutioncode'] = $this->occArr['institutioncode'];
					}
					if($this->occArr['collectioncode']){
						if($this->metadataArr['collectioncode'] != $this->occArr['collectioncode']) $this->metadataArr['collectioncode'] = $this->occArr['collectioncode'];
					}
					if(!$this->occArr['occurrenceid']){
						//Set occurrence GUID based on GUID target, but only if occurrenceID field isn't already populated
						if($this->metadataArr['guidtarget'] == 'catalogNumber'){
							$this->occArr['occurrenceid'] = $this->occArr['catalognumber'];
						}
						elseif($this->metadataArr['guidtarget'] == 'symbiotaUUID'){
							if(isset($this->occArr['recordid'])) $this->occArr['occurrenceid'] = $this->occArr['recordid'];
						}
					}
					$this->setAdditionalIdentifiers();
					$this->setPaleo();
					$this->setLoan();
					$this->setOccurrenceRelationships();
					$this->setReferences();
					$this->setMaterialSamples();
					$this->setSource();
				}
				//Set access statistics
				$accessType = 'view';
				if(in_array($this->displayFormat, array('json', 'xml', 'rdf', 'turtle'))) $accessType = 'api' . strtoupper($this->displayFormat);
				$statsManager = new OccurrenceAccessStats();
				$statsManager->recordAccessEvent($this->occid, $accessType);
				$status = true;
			}
			else{
				$this->errorMessage = $stmt->error;
			}
			$stmt->close();
		}
		return $status;
	}

	public function applyProtections($isSecuredReader){
		if($this->occArr){
			$protectTaxon = false;
			/*
			 if(isset($this->occArr['scinameprotected']) && $this->occArr['scinameprotected'] && !$isSecuredReader){
			 $protectTaxon = true;
			 $this->occArr['taxonsecure'] = 1;
			 $this->occArr['sciname'] = $this->occArr['scinameprotected'];
			 $this->occArr['family'] = $this->occArr['familyprotected'];
			 $this->occArr['tidinterpreted'] = $this->occArr['tidprotected'];
			 //$this->occArr['informationWithheld'] .= 'identification and images redacted';
			 }
			 */
			$protectLocality = false;
			if($this->occArr['localitysecurity'] == 1 && !$isSecuredReader){
				$protectLocality = true;
				$this->occArr['localsecure'] = 1;
				$redactArr = array('recordnumber','eventdate','verbatimeventdate','locality','locationid','decimallatitude','decimallongitude','verbatimcoordinates',
					'locationremarks', 'georeferenceremarks', 'geodeticdatum', 'coordinateuncertaintyinmeters', 'minimumelevationinmeters', 'maximumelevationinmeters',
					'verbatimelevation', 'habitat', 'associatedtaxa');
				$infoWithheld = '';
				foreach($redactArr as $term){
					if($this->occArr[$term]){
						$this->occArr[$term] = '';
						$infoWithheld .= ', ' . $term;
					}
				}
				if($this->occArr['informationwithheld']) $infoWithheld = $this->occArr['informationwithheld'] . '; ' . $infoWithheld;
				$this->occArr['informationwithheld'] = trim($infoWithheld, ', ');
			}
			if(!$protectTaxon) $this->setDeterminations();
			if(!$protectLocality && !$protectTaxon) $this->setImages();
			if(!$protectLocality) $this->setExsiccati();
		}
	}

	private function setDeterminations(){
		$sql = 'SELECT detid, dateidentified, identifiedby, sciname, scientificnameauthorship, identificationqualifier, identificationreferences, identificationremarks, iscurrent
			FROM omoccurdeterminations
			WHERE (occid = ?) AND appliedstatus = 1
			ORDER BY sortsequence';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->occid);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($row = $rs->fetch_object()){
					$detId = $row->detid;
					$this->occArr['dets'][$detId]['date'] = $row->dateidentified;
					$this->occArr['dets'][$detId]['identifiedby'] = $row->identifiedby;
					$this->occArr['dets'][$detId]['sciname'] = $row->sciname;
					$this->occArr['dets'][$detId]['author'] = $row->scientificnameauthorship;
					$this->occArr['dets'][$detId]['qualifier'] = $row->identificationqualifier;
					$this->occArr['dets'][$detId]['ref'] = $row->identificationreferences;
					$this->occArr['dets'][$detId]['notes'] = $row->identificationremarks;
					$this->occArr['dets'][$detId]['iscurrent'] = $row->iscurrent;
				}
				$rs->free();
			}
			else{
				$this->warningArr[] = $stmt->error;
			}
			$stmt->close();
		}
	}

	private function setImages(){
		global $imageDomain;
		$sql = 'SELECT i.imgid, i.url, i.thumbnailurl, i.originalurl, i.sourceurl, i.notes, i.caption, 
      CONCAT_WS(" ",u.firstname,u.lastname) as innerPhotographer, i.photographer, i.rights, i.accessRights, i.copyright
			FROM images i LEFT JOIN users u ON i.photographeruid = u.uid
			WHERE (i.occid = ?) ORDER BY i.sortoccurrence,i.sortsequence';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->occid);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($row = $rs->fetch_object()){
					$imgId = $row->imgid;
					$url = $row->url;
					$tnUrl = $row->thumbnailurl;
					$lgUrl = $row->originalurl;
					if($imageDomain){
						if(substr($url,0,1)=='/') $url = $imageDomain . $url;
						if($lgUrl && substr($lgUrl, 0, 1) == '/') $lgUrl = $imageDomain . $lgUrl;
						if($tnUrl && substr($tnUrl, 0, 1) == '/') $tnUrl = $imageDomain . $tnUrl;
					}
					if((!$url || $url == 'empty') && $lgUrl) $url = $lgUrl;
					if(!$tnUrl && $url) $tnUrl = $url;
					$this->occArr['imgs'][$imgId]['url'] = $url;
					$this->occArr['imgs'][$imgId]['tnurl'] = $tnUrl;
					$this->occArr['imgs'][$imgId]['lgurl'] = $lgUrl;
					$this->occArr['imgs'][$imgId]['sourceurl'] = $row->sourceurl;
					$this->occArr['imgs'][$imgId]['caption'] = $row->caption;
					$this->occArr['imgs'][$imgId]['photographer'] = $row->photographer;
					$this->occArr['imgs'][$imgId]['rights'] = $row->rights;
					$this->occArr['imgs'][$imgId]['accessrights'] = $row->accessRights;
					$this->occArr['imgs'][$imgId]['copyright'] = $row->copyright;
          if($row->innerPhotographer) $this->occArr['imgs'][$imgId]['photographer'] = $row->innerPhotographer;
				}
				$rs->free();
			}
			else{
				$this->warningArr[] = $stmt->error;
			}
			$stmt->close();
		}
	}

	private function setAdditionalIdentifiers(){
		$retArr = array();
		$sql = 'SELECT idomoccuridentifiers, occid, identifiervalue, identifiername FROM omoccuridentifiers WHERE (occid = ?) ORDER BY sortBy';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->occid);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($r = $rs->fetch_object()){
					$identifierTag = $r->identifiername;
					if(!$identifierTag) $identifierTag = 0;
					$retArr[$r->idomoccuridentifiers]['name'] = $identifierTag;
					$retArr[$r->idomoccuridentifiers]['value'] = $r->identifiervalue;
				}
				$rs->free();
			}
		}
		if($retArr) $this->occArr['othercatalognumbers'] = $retArr;
		elseif($this->occArr['othercatalognumbers']){
			$this->occArr['othercatalognumbers'] = array(array('value' => $this->occArr['othercatalognumbers']));
		}
	}

	private function setPaleo(){
		if(isset($this->activeModules['paleo']) && $this->activeModules['paleo']){
			$sql = 'SELECT paleoid, eon, era, period, epoch, earlyinterval, lateinterval, absoluteage, storageage, stage, localstage, biota,
				biostratigraphy, lithogroup, formation, taxonenvironment, member, bed, lithology, stratremarks, element, slideproperties, geologicalcontextid
				FROM omoccurpaleo WHERE occid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->occid);
				$stmt->execute();
				if($rs = $stmt->get_result()){
					while($r = $rs->fetch_assoc()){
						$this->occArr = array_merge($this->occArr, $r);
					}
					$rs->free();
				}
				$stmt->close();
			}
		}
	}

	private function setLoan(){
		$sql = 'SELECT l.loanIdentifierOwn, i.institutioncode
			FROM omoccurloanslink llink INNER JOIN omoccurloans l ON llink.loanid = l.loanid
			INNER JOIN institutions i ON l.iidBorrower = i.iid
			WHERE (llink.occid = ?) AND (l.dateclosed IS NULL) AND (llink.returndate IS NULL)';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->occid);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($row = $rs->fetch_object()){
					$this->occArr['loan']['identifier'] = $row->loanIdentifierOwn;
					$this->occArr['loan']['code'] = $row->institutioncode;
				}
				$rs->free();
			}
			else{
				$this->warningArr[] = $stmt->error;
			}
			$stmt->close();
		}
	}

	private function setExsiccati(){
		$sql = 'SELECT t.title, t.editor, n.omenid, n.exsnumber
			FROM omexsiccatititles t INNER JOIN omexsiccatinumbers n ON t.ometid = n.ometid
			INNER JOIN omexsiccatiocclink l ON n.omenid = l.omenid
			WHERE (l.occid = ?)';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->occid);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($r = $rs->fetch_object()){
					$this->occArr['exs']['title'] = $r->title;
					$this->occArr['exs']['omenid'] = $r->omenid;
					$this->occArr['exs']['exsnumber'] = $r->exsnumber;
				}
				$rs->free();
			}
			else{
				$this->warningArr[] = $stmt->error;
			}
			$stmt->close();
		}
	}

	private function setOccurrenceRelationships(){
		$relOccidArr = array();
		$sql = 'SELECT assocID, occid, occidAssociate, relationship, subType, resourceUrl, objectID, dynamicProperties, verbatimSciname, tid
			FROM omoccurassociations
			WHERE occid = ? OR occidAssociate = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('ii', $this->occid, $this->occid);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($r = $rs->fetch_object()){
					$relOccid = $r->occidAssociate;
					$relationship = $r->relationship;
					if($this->occid == $r->occidAssociate){
						$relOccid = $r->occid;
						$relationship = $this->getInverseRelationship($relationship);
					}
					if($relOccid) $relOccidArr[$relOccid][] = $r->assocID;
					$this->occArr['relation'][$r->assocID]['relationship'] = $relationship;
					$this->occArr['relation'][$r->assocID]['subtype'] = $r->subType;
					$this->occArr['relation'][$r->assocID]['occidassoc'] = $relOccid;
					$this->occArr['relation'][$r->assocID]['resourceurl'] = $r->resourceUrl;
					$this->occArr['relation'][$r->assocID]['objectID'] = $r->objectID;
					$this->occArr['relation'][$r->assocID]['sciname'] = $r->verbatimSciname;
				}
				$rs->free();
			}
		}
		if($relOccidArr){
			$sql = 'SELECT o.occid, o.sciname,
				CONCAT_WS("-",IFNULL(o.institutioncode, c.institutioncode), IFNULL(o.collectioncode, c.collectioncode)) as collcode, IFNULL(o.catalogNumber, o.otherCatalogNumbers) as catnum
				FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid
				WHERE o.occid IN(' . implode(',', array_keys($relOccidArr)) . ')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				foreach($relOccidArr[$r->occid] as $targetAssocID){
					$this->occArr['relation'][$targetAssocID]['objectID'] = $r->collcode . ':' . $r->catnum;
					$this->occArr['relation'][$targetAssocID]['sciname'] = $r->sciname;
				}
			}
			$rs->free();
		}
	}

	private function getInverseRelationship($relationship){
		if(!$this->relationshipArr) $this->setRelationshipArr();
		if(array_key_exists($relationship, $this->relationshipArr)) return $this->relationshipArr[$relationship];
		return $relationship;
	}

	private function setRelationshipArr(){
		if(!$this->relationshipArr){
			$sql = 'SELECT t.term, t.inverseRelationship FROM ctcontrolvocabterm t INNER JOIN ctcontrolvocab v  ON t.cvid = v.cvid WHERE v.tableName = "omoccurassociations" AND v.fieldName = "relationship"';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$this->relationshipArr[$r->term] = $r->inverseRelationship;
					if($r->inverseRelationship && !isset($this->relationshipArr[$r->inverseRelationship])) $this->relationshipArr[$r->inverseRelationship] = $r->term;
				}
				$rs->free();
			}
		}
	}

	private function setReferences(){
		$sql = 'SELECT r.refid, r.title, r.secondarytitle, r.shorttitle, r.tertiarytitle, r.pubdate, r.edition, r.volume, r.numbervolumnes, r.number,
			r.pages, r.section, r.placeofpublication, r.publisher, r.isbn_issn, r.url, r.guid, r.cheatauthors, r.cheatcitation
			FROM referenceobject r INNER JOIN referenceoccurlink l ON r.refid = l.refid
			WHERE (l.occid = ?)';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->occid);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($r = $rs->fetch_object()){
					$this->occArr['ref'][$r->refid]['display'] = $r->cheatcitation;
					$this->occArr['ref'][$r->refid]['url'] = $r->url;
				}
				$rs->free();
			}
			else{
				$this->warningArr[] = $stmt->error;
			}
			$stmt->close();
		}
	}

	private function setMaterialSamples(){
		if(isset($this->activeModules['matSample']) && $this->activeModules['matSample']){
			$sql = 'SELECT m.matSampleID, m.sampleType, m.catalogNumber, m.guid, m.sampleCondition, m.disposition, m.preservationType, m.preparationDetails, m.preparationDate,
				m.preparedByUid, CONCAT_WS(", ",u.lastname,u.firstname) as preparedBy, m.individualCount, m.sampleSize, m.storageLocation, m.remarks, m.dynamicFields, m.recordID, m.initialTimestamp
				FROM ommaterialsample m LEFT JOIN users u ON m.preparedByUid = u.uid WHERE m.occid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->occid);
				$stmt->execute();
				if($rs = $stmt->get_result()){
					while($r = $rs->fetch_assoc()){
						$this->occArr['matSample'][$r['matSampleID']] = $r;
					}
					$rs->free();
				}
				$stmt->close();
			}
		}
	}

	private function setSource(){
		if(isset($GLOBALS['ACTIVATE_PORTAL_INDEX']) && $GLOBALS['ACTIVATE_PORTAL_INDEX']){
			$sql = 'SELECT o.remoteOccid, o.refreshTimestamp, o.verification, i.urlRoot, i.portalName
				FROM portaloccurrences o INNER JOIN portalpublications p ON o.pubid = p.pubid
				INNER JOIN portalindex i ON p.portalID = i.portalID
				WHERE (o.occid = ?) AND (p.direction = "import")';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->occid);
				$stmt->execute();
				if($rs = $stmt->get_result()){
					while($r = $rs->fetch_object()){
						$this->occArr['source']['type'] = 'symbiota';
						$this->occArr['source']['url'] = $r->urlRoot.'/collections/individual/index.php?occid='.$r->remoteOccid;
						$this->occArr['source']['sourceName'] = $r->portalName;
						$this->occArr['source']['refreshTimestamp'] = $r->refreshTimestamp;
						$this->occArr['source']['sourceID'] = $r->remoteOccid;
					}
					$rs->free();
				}
				$stmt->close();
			}
			if(isset($this->occArr['source'])){
				$sql2 = 'SELECT uploadDate FROM omcollectionstats WHERE collid = ?';
				if($stmt = $this->conn->prepare($sql2)){
					$stmt->bind_param('i', $this->collid);
					$stmt->execute();
					if($rs2 = $stmt->get_result()){
						if($r2 = $rs2->fetch_object()){
							if($r2->uploadDate > $this->occArr['source']['refreshTimestamp']) $this->occArr['source']['refreshTimestamp'] = $r2->uploadDate.' (batch update)';
						}
						$rs2->free();
					}
					$stmt->close();
				}
			}
		}

		//Format link out to source
		if(!isset($this->occArr['source']) && $this->metadataArr['individualurl']){
			$iUrl = trim($this->metadataArr['individualurl']);
			if(substr($iUrl, 0, 4) != 'http'){
				if($pos = strpos($iUrl, ':')){
					$this->occArr['source']['title'] = substr($iUrl, 0, $pos);
					$iUrl = trim(substr($iUrl, $pos+1));
				}
			}
			$displayStr = '';
			$indUrl = '';
			if(strpos($iUrl,'--DBPK--') !== false && $this->occArr['dbpk']){
				$indUrl = str_replace('--DBPK--',$this->occArr['dbpk'],$iUrl);
				$displayStr = $indUrl;
			}
			elseif(strpos($iUrl,'--CATALOGNUMBER--') !== false && $this->occArr['catalognumber']){
				$indUrl = str_replace('--CATALOGNUMBER--',$this->occArr['catalognumber'],$iUrl);
				$displayStr = $this->occArr['catalognumber'];
			}
			elseif(strpos($iUrl,'--OTHERCATALOGNUMBERS--') !== false && $this->occArr['othercatalognumbers']){
				if(substr($this->occArr['othercatalognumbers'],0,1) == '{'){
					if($this->occArr['othercatalognumbers']){
						foreach($this->occArr['othercatalognumbers'] as $idKey => $idArr){
							if(!$displayStr || $idKey == 'NEON sampleID' || $idKey == 'NEON sampleCode (barcode)'){
								$displayStr = $idArr[0];
								if($idKey == 'NEON sampleCode (barcode)') $iUrl = str_replace('sampleTag','barcode',$iUrl);
								$indUrl = str_replace('--OTHERCATALOGNUMBERS--',$idArr[0],$iUrl);
								if($idKey == 'NEON sampleCode (barcode)') break;
							}
						}
					}
				}
				else{
					$ocn = str_replace($this->occArr['othercatalognumbers'], ',', ';');
					$ocnArr = explode(';',$ocn);
					$ocnValue = trim(array_pop($ocnArr));
					if(stripos($ocnValue,':')) $ocnValue = trim(array_pop(explode(':',$ocnValue)));
					$indUrl = str_replace('--OTHERCATALOGNUMBERS--',$ocnValue,$iUrl);
					$displayStr = $ocnValue;
				}
			}
			elseif(strpos($iUrl,'--OCCURRENCEID--') !== false && $this->occArr['occurrenceid']){
				$indUrl = str_replace('--OCCURRENCEID--',$this->occArr['occurrenceid'],$iUrl);
				$displayStr = $this->occArr['occurrenceid'];
			}
			$this->occArr['source']['type'] = 'external';
			$this->occArr['source']['url'] = $indUrl;
			$this->occArr['source']['displayStr'] = $displayStr;
			$this->occArr['source']['refreshTimestamp'] = $this->metadataArr['uploaddate'];
		}
	}

	public function getDuplicateArr(){
		$retArr = array();
		$sqlBase = 'SELECT o.occid, c.institutioncode AS instcode, c.collectioncode AS collcode, c.collectionname AS collname, o.catalognumber, o.occurrenceid, o.sciname, '.
			'o.scientificnameauthorship AS author, o.identifiedby, o.dateidentified, o.recordedby, o.recordnumber, o.eventdate, IFNULL(i.thumbnailurl, i.url) AS url ';
		//Get exsiccati duplicates
		if(isset($this->occArr['exs'])){
			$sql = $sqlBase.'FROM omexsiccatiocclink l INNER JOIN omexsiccatiocclink l2 ON l.omenid = l2.omenid
				INNER JOIN omoccurrences o ON l2.occid = o.occid
				INNER JOIN omcollections c ON o.collid = c.collid
				LEFT JOIN images i ON o.occid = i.occid
				WHERE (o.occid != l.occid) AND (l.occid = ?)';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->occid);
				$stmt->execute();
				if($rs = $stmt->get_result()){
					while($r = $rs->fetch_assoc()){
						$retArr['exs'][$r['occid']] = array_change_key_case($r);
					}
					$rs->free();
				}
				$stmt->close();
			}
		}
		//Get specimen duplicates
		$sql = $sqlBase.'FROM omoccurduplicatelink d INNER JOIN omoccurduplicatelink d2 ON d.duplicateid = d2.duplicateid
			INNER JOIN omoccurrences o ON d2.occid = o.occid
			INNER JOIN omcollections c ON o.collid = c.collid
			LEFT JOIN images i ON o.occid = i.occid
			WHERE (d.occid = ?) AND (d.occid != d2.occid) ';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->occid);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($r = $rs->fetch_assoc()){
					if(!isset($retArr['exs'][$r['occid']])) $retArr['dupe'][$r['occid']] = array_change_key_case($r);
				}
				$rs->free();
			}
			$stmt->close();
		}
		return $this->cleanOutArray($retArr);
	}

	//Occurrence trait and attribute functions
	public function getTraitArr(){
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT t.traitid, t.traitName, t.traitType, t.description AS t_desc, t.refUrl AS t_url, s.stateid, s.stateName, s.description AS s_desc, s.refUrl AS s_url, d.parentstateid
				FROM tmattributes a INNER JOIN tmstates s ON a.stateid = s.stateid
				INNER JOIN tmtraits t ON s.traitid = t.traitid
				LEFT JOIN tmtraitdependencies d ON t.traitid = d.traitid
				WHERE t.isPublic = 1 AND a.occid = ? ORDER BY t.traitName, s.sortSeq';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->occid);
				$stmt->execute();
				if($rs = $stmt->get_result()){
					while($r = $rs->fetch_object()){
						$retArr[$r->traitid]['name'] = $r->traitName;
						$retArr[$r->traitid]['desc'] = $r->t_desc;
						$retArr[$r->traitid]['url'] = $r->t_url;
						$retArr[$r->traitid]['type'] = $r->traitType;
						$retArr[$r->traitid]['depStateID'] = $r->parentstateid;
						$retArr[$r->traitid]['state'][$r->stateid]['name'] = $r->stateName;
						$retArr[$r->traitid]['state'][$r->stateid]['desc'] = $r->s_desc;
						$retArr[$r->traitid]['state'][$r->stateid]['url'] = $r->s_url;
					}
					$rs->free();
				}
				$stmt->close();
			}
			if($retArr){
				//Set dependent traits
				$sql = 'SELECT DISTINCT s.traitid AS parentTraitID, d.parentStateID, d.traitid AS depTraitID
					FROM tmstates s INNER JOIN tmtraitdependencies d ON s.stateid = d.parentstateid
					WHERE s.traitid IN(' . implode(',', array_keys($retArr)) . ')';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$retArr[$r->parentTraitID]['state'][$r->parentStateID]['depTraitID'][] = $r->depTraitID;
				}
				$rs->free();
			}
		}
		return $this->cleanOutArray($retArr);
	}

	public function echoTraitDiv($traitArr, $targetID, $ident = 15){
		if(array_key_exists($targetID,$traitArr)){
			$tArr = $traitArr[$targetID];
			foreach($tArr['state'] as $sArr){
				$label = '';
				if($tArr['type'] == 'TF') $label = $traitArr[$targetID]['name'];
				$this->echoTraitUnit($sArr, $label, $ident);
				if(array_key_exists('depTraitID',$sArr)){
					foreach($sArr['depTraitID'] as $depTraitID){
						$this->echoTraitDiv($traitArr, $depTraitID, $ident+15);
					}
				}
			}
		}
	}

	public function echoTraitUnit($outArr, $label = '', $indent=0){
		if(isset($outArr['name'])){
			echo '<div style="margin-left:'.$indent.'px">';
			if(!empty($outArr['url'])) echo '<a href="' . $this->cleanOutStr($outArr['url']) . '" target="_blank">';
			echo '<span class="trait-name">';
			if(!empty($label)) echo $label.' ';
			echo $this->cleanOutStr($outArr['name']);
			echo '</span>';
			if(!empty($outArr['url'])) echo '</a>';
			if(!empty($outArr['desc'])) echo ': '.$this->cleanOutStr($outArr['desc']);
			echo '</div>';
		}
	}

	//Occurrence comment functions
	public function getCommentArr($isEditor){
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT c.comid, c.comment, u.username, c.reviewstatus, c.initialtimestamp FROM omoccurcomments c INNER JOIN users u ON c.uid = u.uid WHERE (c.occid = ?) ';
			if(!$isEditor) $sql .= 'AND c.reviewstatus IN(1,3) ';
			$sql .= 'ORDER BY c.initialtimestamp';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->occid);
				$stmt->execute();
				if($rs = $stmt->get_result()){
					while($row = $rs->fetch_object()){
						$comId = $row->comid;
						$retArr[$comId]['comment'] = $row->comment;
						$retArr[$comId]['reviewstatus'] = $row->reviewstatus;
						$retArr[$comId]['username'] = $row->username;
						$retArr[$comId]['initialtimestamp'] = $row->initialtimestamp;
					}
					$rs->free();
				}
				else{
					$this->errorMessage = $stmt->error;
				}
				$stmt->close();
			}
		}
		return $this->cleanOutArray($retArr);
	}

	public function addComment($commentStr){
		$status = false;
		if(is_numeric($GLOBALS['SYMB_UID'])){
	 		$commentStr = strip_tags($commentStr);
			$sql = 'INSERT INTO omoccurcomments(occid, comment, uid, reviewstatus) VALUES(?, ?, ?, 1)';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('isi', $this->occid, $commentStr, $GLOBALS['SYMB_UID']);
				$stmt->execute();
				if($stmt->affected_rows){
					$status = true;
				}
				elseif($stmt->error){
					$this->errorMessage = $stmt->error;
				}
				$stmt->close();
			}
		}
		return $status;
	}

	public function deleteComment($comId){
		$status = true;
		if(is_numeric($comId)){
			$sql = 'DELETE FROM omoccurcomments WHERE comid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $comId);
				$stmt->execute();
				if($stmt->affected_rows){
					$status = true;
				}
				elseif($stmt->error){
					$this->errorMessage = $stmt->error;
				}
				$stmt->close();
			}
		}
		return $status;
	}

	public function reportComment($repComId){
		global $LANG;
		$status = false;
		if(isset($GLOBALS['ADMIN_EMAIL'])){
			$sql = 'UPDATE omoccurcomments SET reviewstatus = 2 WHERE comid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $repComId);
				$stmt->execute();
				if($stmt->affected_rows){
					$status = true;
				}
				elseif($stmt->error){
					$this->errorMessage = $stmt->error;
				}
				$stmt->close();
			}

			//Email to portal admin
			$emailAddr = $GLOBALS['ADMIN_EMAIL'];
			$comUrl = $this->getDomain().$GLOBALS['CLIENT_ROOT'].'/collections/individual/index.php?occid=' . $this->occid . '#commenttab';
			$subject = $GLOBALS['DEFAULT_TITLE'] . ' '. $LANG['INAPPROPRIATE'] . '<br/>';
			$bodyStr = $LANG['REPORTED_AS_INAPPROPRIATE'] . ':<br/> <a href="' . $comUrl  . '">' . $comUrl . '</a>';
			$headerStr = "MIME-Version: 1.0 \r\nContent-type: text/html \r\nTo: " . $emailAddr . " \r\nFrom: Admin <" . $emailAddr . "> \r\n";
			if(!mail($emailAddr,$subject,$bodyStr,$headerStr)){
				$status = false;
			}
		}
		else{
			$this->errorMessage = $LANG['EMAIL_NOT_DEFINED'];
			$status = false;
		}
		return $status;
	}

	public function makeCommentPublic($comId){
		$status = false;
		$sql = 'UPDATE omoccurcomments SET reviewstatus = 1 WHERE comid = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $comId);
			$stmt->execute();
			if($stmt->affected_rows){
				$status = true;
			}
			elseif($stmt->error){
				$this->errorMessage = $stmt->error;
			}
			$stmt->close();
		}
		return $status;
	}

	//Genetic functions
	public function getGeneticArr(){
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT idoccurgenetic, identifier, resourcename, locus, resourceurl, notes FROM omoccurgenetic WHERE occid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->occid);
				$stmt->execute();
				if($rs = $stmt->get_result()){
					while($r = $rs->fetch_object()){
						$retArr[$r->idoccurgenetic]['id'] = $r->identifier;
						$retArr[$r->idoccurgenetic]['name'] = $r->resourcename;
						$retArr[$r->idoccurgenetic]['locus'] = $r->locus;
						$retArr[$r->idoccurgenetic]['resourceurl'] = $r->resourceurl;
						$retArr[$r->idoccurgenetic]['notes'] = $r->notes;
					}
					$rs->free();
				}
				else{
					$this->errorMessage = $stmt->error;
				}
				$stmt->close();
			}
		}
		return $this->cleanOutArray($retArr);
	}

	public function getEditArr(){
		$retArr = array();
		$sql = 'SELECT e.ocedid, e.fieldname, e.fieldvalueold, e.fieldvaluenew, e.reviewstatus, e.appliedstatus,
			CONCAT_WS(", ",u.lastname,u.firstname) as editor, e.initialtimestamp
			FROM omoccuredits e INNER JOIN users u ON e.uid = u.uid
			WHERE e.occid = ? ORDER BY e.initialtimestamp DESC ';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->occid);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($r = $rs->fetch_object()){
					$k = substr($r->initialtimestamp,0,16);
					if(!isset($retArr[$k])){
						$retArr[$k]['editor'] = $r->editor;
						$retArr[$k]['ts'] = $r->initialtimestamp;
						$retArr[$k]['reviewstatus'] = $r->reviewstatus;
					}
					$retArr[$k]['edits'][$r->appliedstatus][$r->ocedid]['fieldname'] = $r->fieldname;
					$retArr[$k]['edits'][$r->appliedstatus][$r->ocedid]['old'] = $r->fieldvalueold;
					$retArr[$k]['edits'][$r->appliedstatus][$r->ocedid]['new'] = $r->fieldvaluenew;
					$currentCode = 0;
					if(isset($this->occArr[strtolower($r->fieldname)])){
						$fName = $this->occArr[strtolower($r->fieldname)];
						if($fName == $r->fieldvaluenew) $currentCode = 1;
						elseif($fName == $r->fieldvalueold) $currentCode = 2;
					}
					$retArr[$k]['edits'][$r->appliedstatus][$r->ocedid]['current'] = $currentCode;
				}
				$rs->free();
			}
			$stmt->close();
		}
		return $this->cleanOutArray($retArr);
	}

	public function getExternalEditArr(){
		$retArr = Array();
		$sql = 'SELECT r.orid, r.oldvalues, r.newvalues, r.externalsource, r.externaleditor, r.reviewstatus, r.appliedstatus,
			CONCAT_WS(", ",u.lastname,u.firstname) AS username, r.externaltimestamp, r.initialtimestamp
			FROM omoccurrevisions r LEFT JOIN users u ON r.uid = u.uid
			WHERE (r.occid = ?) ORDER BY r.initialtimestamp DESC ';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->occid);
			$stmt->execute();
			if($rs = $stmt->get_result()){
				while($r = $rs->fetch_object()){
					$editor = $r->externaleditor;
					if($r->username) $editor .= ' ('.$r->username.')';
					$retArr[$r->orid][$r->appliedstatus]['editor'] = $editor;
					$retArr[$r->orid][$r->appliedstatus]['source'] = $r->externalsource;
					$retArr[$r->orid][$r->appliedstatus]['reviewstatus'] = $r->reviewstatus;
					$retArr[$r->orid][$r->appliedstatus]['ts'] = $r->initialtimestamp;

					$oldValues = json_decode($r->oldvalues,true);
					$newValues = json_decode($r->newvalues,true);
					foreach($oldValues as $fieldName => $value){
						$retArr[$r->orid][$r->appliedstatus]['edits'][$fieldName]['old'] = $value;
						$retArr[$r->orid][$r->appliedstatus]['edits'][$fieldName]['new'] = (isset($newValues[$fieldName])?$newValues[$fieldName]:'ERROR');
					}
				}
				$rs->free();
			}
			$stmt->close();
		}
		return $this->cleanOutArray($retArr);
	}

	public function getAccessStats(){
		$retArr = Array();
		if(isset($GLOBALS['STORE_STATISTICS'])){
			$sql = 'SELECT year(s.accessdate) as accessdate, s.accesstype, s.cnt
				FROM omoccuraccesssummary s INNER JOIN omoccuraccesssummarylink l ON s.oasid = l.oasid
				WHERE (l.occid = ?) GROUP BY s.accessdate, s.accesstype';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->occid);
				$stmt->execute();
				if($rs = $stmt->get_result()){
					while($r = $rs->fetch_object()){
						$retArr[$r->accessdate][$r->accesstype] = $r->cnt;
					}
					$rs->free();
				}
				$stmt->close();
			}
		}
		return $this->cleanOutArray($retArr);
	}

	//Voucher management
	public function getVoucherChecklists(){
		global $USER_RIGHTS, $LANG;
		$returnArr = Array();
		if($this->occid){
			$sql = 'SELECT c.clid, c.name, c.access, v.voucherID
				FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid
				INNER JOIN fmvouchers v ON cl.clTaxaID = v.clTaxaID
				WHERE v.occid = ? ';
			if(array_key_exists('ClAdmin', $USER_RIGHTS)){
				$sql .= 'AND (c.access = "public" OR c.clid IN(' . $this->cleanInStr(implode(',', $USER_RIGHTS['ClAdmin'])) . ')) ';
			}
			else{
				$sql .= 'AND (c.access = "public") ';
			}
			$sql .= 'ORDER BY c.name';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->occid);
				$stmt->execute();
				if($rs = $stmt->get_result()){
					while($r = $rs->fetch_object()){
						$nameStr = $r->name;
						if($r->access == 'private') $nameStr .= ' (' . $LANG['PRIVATE_STATUS'] . ')';
						$returnArr[$r->clid]['name'] = $nameStr;
						$returnArr[$r->clid]['voucherID'] = $r->voucherID;
					}
					$rs->free();
				}
				$stmt->close();
			}
		}
		return $this->cleanOutArray($returnArr);
	}

	public function linkVoucher($postArr){
		$status = false;
		if($this->occid && is_numeric($postArr['vclid'])){
			if(isset($GLOBALS['USER_RIGHTS']['ClAdmin']) && in_array($postArr['vclid'], $GLOBALS['USER_RIGHTS']['ClAdmin'])){
				$voucherManager = new ChecklistVoucherAdmin();
				$voucherManager->setClid($postArr['vclid']);
				if($voucherManager->linkVoucher($postArr['vtid'], $this->occid, '', $postArr['veditnotes'], $postArr['vnotes'])){
					$status = true;
				}
				else $this->errorMessage = $voucherManager->getErrorMessage();
			}
		}
		return $status;
	}

	public function deleteVoucher($voucherID){
		global $LANG;
		$status = false;
		$clid = 0;
		//Make sure user has checklist admin permission for checklist
		$sql = 'SELECT c.clid FROM fmvouchers v INNER JOIN fmchklsttaxalink c ON v.clTaxaID = c.clTaxaID WHERE v.voucherID = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $voucherID);
			$stmt->execute();
			$stmt->bind_result($clid);
			$stmt->fetch();
			$stmt->close();
		}
		if(!$clid){
			$this->errorMessage = $LANG['UNABLE_TO_VERIFY_TARGET'];
			return false;
		}
		if(isset($GLOBALS['USER_RIGHTS']['ClAdmin']) && in_array($clid, $GLOBALS['USER_RIGHTS']['ClAdmin'])){
			$voucherManager = new ChecklistVoucherAdmin();
			if($voucherManager->deleteVoucher($voucherID)){
				$status = true;
			}
			else{
				$this->errorMessage = $voucherManager->getErrorMessage();
				$status = false;
			}
		}
		else{
			$this->errorMessage = $LANG['PERMISSION_ERROR'];
			return false;
		}
		return $status;
	}

	//Data and general support functions
	public function getDatasetArr(){
		$retArr = array();
		$roleArr = array();
		if(is_numeric($GLOBALS['SYMB_UID'])){
			$sql = 'SELECT tablepk, role FROM userroles WHERE (tablename = "omoccurdatasets") AND (uid = ?) ';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $GLOBALS['SYMB_UID']);
				$stmt->execute();
				$tablePK = ''; $role = '';
				$stmt->bind_result($tablePK, $role);
				while($stmt->fetch()){
					$roleArr[$tablePK] = $role;
				}
				$stmt->close();
			}
		}

		$sql2 = 'SELECT datasetid, name, uid FROM omoccurdatasets ';
		if(!$GLOBALS['IS_ADMIN'] && is_numeric($GLOBALS['SYMB_UID'])){
			//Only get datasets for current user. Once we have appied isPublic tag, we can extend display to all public datasets
			$sql2 .= 'WHERE (uid = '.$GLOBALS['SYMB_UID'].') ';
			if($roleArr) $sql2 .= 'OR (datasetid IN('.implode(',',array_keys($roleArr)).')) ';
		}
		$sql2 .= 'ORDER BY name';
		$rs2 = $this->conn->query($sql2);
		if($rs2){
			while($r2 = $rs2->fetch_object()){
				$retArr[$r2->datasetid]['name'] = $r2->name;
				$roleStr = '';
				if(isset($GLOBALS['SYMB_UID']) && $GLOBALS['SYMB_UID'] == $r2->uid) $roleStr = 'owner';
				elseif(isset($roleArr[$r2->datasetid]) && $roleArr[$r2->datasetid])  $roleStr = $roleArr[$r2->datasetid];
				if($roleStr) $retArr[$r2->datasetid]['role'] = $roleStr;
			}
			$rs2->free();
		}
		else $this->errorMessage = 'ERROR: Unable to set datasets for user: '.$this->conn->error;

		$sql3 = 'SELECT datasetid, notes FROM omoccurdatasetlink WHERE occid = ?';
		if($stmt = $this->conn->prepare($sql3)){
			$stmt->bind_param('i', $this->occid);
			$stmt->execute();
			if($rs3 = $stmt->get_result()){
				while($r3 = $rs3->fetch_object()){
					if(isset($retArr[$r3->datasetid])){
						//Only display datasets linked to current user, at least for now. Once isPublic option is activated, we'll open this up further.
						$retArr[$r3->datasetid]['linked'] = 1;
						if($r3->notes) $retArr[$r3->datasetid]['notes'] = $r3->notes;
					}
				}
				$rs3->free();
			}
			else $this->errorMessage = $stmt->conn->error;
			$stmt->close();
		}
		return $this->cleanOutArray($retArr);
	}

	public function getChecklists($clidExcludeArr){
		global $USER_RIGHTS;
		if(!array_key_exists('ClAdmin', $USER_RIGHTS)) return null;
		$retArr = Array();
		$targetArr = array_diff($USER_RIGHTS['ClAdmin'], $clidExcludeArr);
		if($targetArr){
			$sql = 'SELECT name, clid FROM fmchecklists WHERE clid IN(' . implode(',', $targetArr) . ') ORDER BY Name';
			if($rs = $this->conn->query($sql)){
				while($row = $rs->fetch_object()){
					$retArr[$row->clid] = $row->name;
				}
				$rs->free();
			}
			else{
				$this->errorMessage = $this->conn->error;
			}
		}
		return $this->cleanOutArray($retArr);
	}

	public function checkArchive($guid){
		$retArr = array();
		$archiveObject = '';
		$notes = '';
		$sql = 'SELECT archiveobj, remarks FROM omoccurarchive ';
		if($this->occid){
			$sql .= 'WHERE occid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->occid);
				$stmt->execute();
				$stmt->bind_result($archiveObject, $notes);
				$stmt->fetch();
				$stmt->close();
			}
		}
		if(!$archiveObject && $guid){
			$sql .= 'WHERE (occurrenceid = ?) OR (recordID = ?) ';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('ss', $guid, $guid);
				$stmt->execute();
				$stmt->bind_result($archiveObject, $notes);
				$stmt->fetch();
				$stmt->close();
			}
		}
		if($archiveObject){
			$retArr['obj'] = json_decode($archiveObject, true);
			$retArr['notes'] = $notes;
		}
		return $this->cleanOutArray($retArr);
	}

	public function restoreRecord(){
		if($this->occid){
			$jsonStr = '';
			$sql = 'SELECT archiveobj FROM omoccurarchive WHERE (occid = ?)';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->occid);
				$stmt->execute();
				$stmt->bind_result($jsonStr);
				$stmt->fetch();
				$stmt->close();
			}
			if(!$jsonStr){
				return false;
			}
			$recArr = json_decode($jsonStr,true);
			$occSkipArr = array('dets','imgs','paleo','exsiccati','assoc','matSample');
			//Restore central record
			$occurFieldArr = array();
			$rsOccur = $this->conn->query('SHOW COLUMNS FROM omoccurrences ');
			while($rOccur = $rsOccur->fetch_object()){
				$occurFieldArr[] = strtolower($rOccur->Field);
			}
			$rsOccur->free();
			$sql1 = 'INSERT INTO omoccurrences(';
			$sql2 = 'VALUES(';
			foreach($recArr as $field => $value){
				if(in_array(strtolower($field),$occurFieldArr)){
					$sql1 .= $field . ',';
					$sql2 .= '"' . $this->cleanInStr($value) . '",';
				}
			}
			$sql = trim($sql1,' ,').') '.trim($sql2,' ,').')';
			if(!$this->conn->query($sql)){
				$this->errorMessage = $this->conn->error . ' (' . $sql . ')';
				return false;
			}

			//Restore determinations
			if(isset($recArr['dets']) && $recArr['dets']){
				$detFieldArr = array();
				$rsDet = $this->conn->query('SHOW COLUMNS FROM omoccurdeterminations ');
				while($rDet = $rsDet->fetch_object()){
					$detFieldArr[] = strtolower($rDet->Field);
				}
				$rsDet->free();
				foreach($recArr['dets'] as $secArr){
					$sql1 = 'INSERT INTO omoccurdeterminations(';
					$sql2 = 'VALUES(';
					foreach($secArr as $f => $v){
						if(in_array(strtolower($f),$detFieldArr)){
							$sql1 .= $f . ',';
							$sql2 .= '"' . $this->cleanInStr($v) . '",';
						}
					}
					$sql = trim($sql1, ' ,') . ') ' . trim($sql2, ' ,') . ')';
					if(!$this->conn->query($sql)){
						$this->errorMessage = $this->conn->error;
						return false;
					}
				}
			}

			//Restore images
			if(isset($recArr['imgs']) && $recArr['imgs']){
				$imgFieldArr = array();
				$rsImg = $this->conn->query('SHOW COLUMNS FROM images');
				while($rImg= $rsImg->fetch_object()){
					$imgFieldArr[] = strtolower($rImg->Field);
				}
				$rsImg->free();
				foreach($recArr['imgs'] as $pk => $secArr){
					$sql1 = 'INSERT INTO images(';
					$sql2 = 'VALUES(';
					foreach($secArr as $f => $v){
						if(in_array(strtolower($f),$imgFieldArr)){
							$sql1 .= $f . ',';
							$sql2 .= '"' . $this->cleanInStr($v) . '",';
						}
					}
					$sql = trim($sql1, ' ,') . ') ' . trim($sql2, ' ,') . ')';
					if(!$this->conn->query($sql)){
						$this->errorMessage = $this->conn->error;
						return false;
					}
				}
			}

			//Restore paleo
			if(isset($recArr['paleo']) && $recArr['paleo']){
				$paleoFieldArr = array();
				$rsPaleo = $this->conn->query('SHOW COLUMNS FROM omoccurpaleo');
				while($rPaleo= $rsPaleo->fetch_object()){
					$paleoFieldArr[] = strtolower($rPaleo->Field);
				}
				$rsPaleo->free();
				foreach($recArr['paleo'] as $pk => $secArr){
					$sql1 = 'INSERT INTO omoccurpaleo(';
					$sql2 = 'VALUES(';
					foreach($secArr as $f => $v){
						if(in_array(strtolower($f),$paleoFieldArr)){
							$sql1 .= $f.',';
							$sql2 .= '"'.$this->cleanInStr($v).'",';
						}
					}
					$sql = trim($sql1, ' ,') . ') ' . trim($sql2, ' ,') . ')';
					if(!$this->conn->query($sql)){
						$this->errorMessage = $this->conn->error . ' (' . $sql . ')';
						return false;
					}
				}
			}

			//Restore exsiccati
			if(isset($recArr['exsiccati']) && $recArr['exsiccati']){
				$sql = 'INSERT INTO omexsiccatiocclink(omenid, occid, ranking, notes) VALUES(' . $recArr['exsiccati']['ometid'] . ',' . $recArr['exsiccati']['occid'] . ','.
					(isset($recArr['exsiccati']['ranking']) ? $recArr['exsiccati']['ranking'] : 'NULL') . ','
					(isset($recArr['exsiccati']['notes']) ? '"' . $this->cleanInStr($recArr['exsiccati']['notes']) . '"' : 'NULL') . ')';
				if(!$this->conn->query($sql)){
					$this->errorMessage = $this->conn->error;
					return false;
				}
			}

			//Restore associations
			if(isset($recArr['assoc']) && $recArr['assoc']){
				$assocFieldArr = array();
				$rsAssoc = $this->conn->query('SHOW COLUMNS FROM omoccurassociations');
				while($rAssoc= $rsAssoc->fetch_object()){
					$assocFieldArr[] = strtolower($rAssoc->Field);
				}
				$rsAssoc->free();
				foreach($recArr['assoc'] as $pk => $secArr){
					$sql1 = 'INSERT INTO omoccurassociations(';
					$sql2 = 'VALUES(';
					foreach($secArr as $f => $v){
						if(in_array(strtolower($f), $assocFieldArr)){
							$sql1 .= $f . ',';
							$sql2 .= '"' . $this->cleanInStr($v) . '",';
						}
					}
					$sql = trim($sql1, ' ,') . ') ' . trim($sql2, ' ,') . ')';
					if(!$this->conn->query($sql)){
						$this->errorMessage = $this->conn->error;
						return false;
					}
				}
			}

			//Restore material sample
			if(isset($recArr['matSample']) && $recArr['matSample']){
				$msFieldArr = array();
				$rsMs = $this->conn->query('SHOW COLUMNS FROM ommaterialsample');
				while($rMs= $rsMs->fetch_object()){
					$msFieldArr[] = strtolower($rMs->Field);
				}
				$rsMs->free();
				foreach($recArr['matSample'] as $pk => $secArr){
					$sql1 = 'INSERT INTO ommaterialsample(';
					$sql2 = 'VALUES(';
					foreach($secArr as $f => $v){
						if(in_array(strtolower($f), $msFieldArr)){
							$sql1 .= $f . ',';
							$sql2 .= '"' . $this->cleanInStr($v) . '",';
						}
					}
					$sql = trim($sql1, ' ,') . ') ' . trim($sql2, ' ,') . ')';
					if(!$this->conn->query($sql)){
						$this->errorMessage = $this->conn->error;
						return false;
					}
				}
			}
			$this->setOccurData();
		}
		return true;
	}

	/*
	 * Return: 0 = false, 2 = full editor, 3 = taxon editor, but not for this collection
	 */
	public function isTaxonomicEditor(){
		$isEditor = 0;

		//Grab taxonomic node id and geographic scopes
		$editTidArr = array();
		$sqlut = 'SELECT tid, geographicscope FROM usertaxonomy WHERE editorstatus = "OccurrenceEditor" AND uid = ?';
		if($stmt = $this->conn->prepare($sqlut)){
			$stmt->bind_param('i', $GLOBALS['SYMB_UID']);
			$stmt->execute();
			$tid = '';
			$geographicScope = '';
			$stmt->bind_result($tid, $geographicScope);
			while($stmt->fetch()){
				$editTidArr[$tid] = $geographicScope;
			}
			$stmt->close();
		}

		//Get relevant tids for active occurrence
		if($editTidArr){
			$occTidArr = array();
			$sql = '';
			if($this->occArr['tidinterpreted']){
				$occTidArr[] = $this->occArr['tidinterpreted'];
				$sql = 'SELECT parenttid FROM taxaenumtree WHERE (taxauthid = 1) AND (tid = ' . $this->cleanInStr($this->occArr['tidinterpreted']) . ')';
			}
			elseif($this->occArr['sciname'] || $this->occArr['family']){
				//Get all relevant tids within the taxonomy hierarchy
				$sql = 'SELECT e.parenttid FROM taxaenumtree e INNER JOIN taxa t ON e.tid = t.tid WHERE (e.taxauthid = 1) ';
				if($this->occArr['sciname']){
					//Try to isolate genus
					$taxon = $this->occArr['sciname'];
					$tok = explode(' ', $this->occArr['sciname']);
					if(count($tok) > 1){
						if(strlen($tok[0]) > 2) $taxon = $tok[0];
					}
					$sql .= 'AND (t.sciname = "' . $this->cleanInStr($taxon) . '") ';
				}
				elseif($this->occArr['family']){
					$sql .= 'AND (t.sciname = "' . $this->cleanInStr($this->occArr['family']) . '") ';
				}
			}
			if($sql){
				$rs2 = $this->conn->query($sql);
				while($r2 = $rs2->fetch_object()){
					$occTidArr[] = $r2->parenttid;
				}
				$rs2->free();
			}
			if($occTidArr){
				if(array_intersect(array_keys($editTidArr), $occTidArr)){
					$isEditor = 3;
					//TODO: check to see if specimen is within geographic scope
				}
			}
		}
		return $isEditor;
	}

	public function activateOrcidID($inStr){
		$retStr = $inStr;
		$m = array();
		if(preg_match('#((https://orcid.org/)?\d{4}-\d{4}-\d{4}-\d{3}[0-9X])#', $inStr, $m)){
			$orcidAnchor = $m[1];
			if(substr($orcidAnchor,5) != 'https') $orcidAnchor = 'https://orcid.org/' . $orcidAnchor;
			$orcidAnchor = '<a href="' . $orcidAnchor . '" target="_blank">' . $m[1] . '</a>';
			$retStr = str_replace($m[1], $orcidAnchor, $retStr);
		}
		return $this->cleanOutStr($retStr);
	}

	// Setters and getters
	public function setOccid($occid){
		if(is_numeric($occid)){
			$this->occid = $occid;
		}
	}

	public function getOccid(){
		return $this->occid;
	}

	public function setCollid($id){
		if(is_numeric($id)){
			$this->collid = $id;
		}
	}

	public function getCollid(){
		return $this->collid;
	}

	public function setDbpk($pk){
		$this->dbpk = $pk;
	}

	public function setDisplayFormat($f){
		if(!in_array($f, array('json', 'xml', 'rdf', 'turtle', 'html'))) $f = 'html';
		$this->displayFormat = $f;
	}
}
?>
