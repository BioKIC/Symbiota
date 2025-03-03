<?php
include_once($SERVER_ROOT . '/classes/SpecUpload.php');
include_once($SERVER_ROOT . '/classes/OccurrenceMaintenance.php');
include_once($SERVER_ROOT . '/classes/GuidManager.php');
include_once($SERVER_ROOT . '/classes/utilities/OccurrenceUtil.php');
include_once($SERVER_ROOT . '/classes/utilities/Encoding.php');
include_once($SERVER_ROOT . '/classes/utilities/QueryUtil.php');
include_once($SERVER_ROOT . '/classes/Media.php');

class SpecUploadBase extends SpecUpload{

	protected $transferCount = 0;
	protected $identTransferCount = 0;
	protected $imageTransferCount = 0;
	protected $includeIdentificationHistory = true;
	protected $includeImages = true;
	private $observerUid;
	private $matchCatalogNumber = 1;
	private $matchOtherCatalogNumbers = 0;
	private $versionDataEdits = false;
	private $verifyImageUrls = false;
	private $processingStatus = '';
	protected $nfnIdentifier;
	protected $uploadTargetPath;

	protected $occurSourceArr = Array();
	protected $identSourceArr = Array();
	protected $imageSourceArr = Array();
	protected $occurFieldMap = Array();
	protected $identFieldMap = Array();
	protected $imageFieldMap = Array();
	protected $symbFields = array();
	protected $identSymbFields = array();
	protected $imageSymbFields = array();
	protected $filterArr = array();
	private $targetFieldArr = array();

	private $sourceCharset;
	private $targetCharset = 'UTF-8';
	private $sourceDatabaseType = '';
	private $dbpkCnt = 0;
	private $relationshipArr;

	function __construct() {
		parent::__construct();
		set_time_limit(7200);
		ini_set('max_input_time',600);
		ini_set('default_socket_timeout', 6000);
		ini_set('memory_limit','512M');
		if(isset($GLOBALS['CHARSET']) && $GLOBALS['CHARSET']){
			$this->targetCharset = strtoupper($GLOBALS['CHARSET']);
			if($this->targetCharset == 'UTF8') $this->targetCharset == 'UTF-8';
		}
	}

	function __destruct(){
		parent::__destruct();
	}

	public function setFieldMaps($postArr){
		if(array_key_exists('sf',$postArr)){
			//Set field map for occurrences using mapping form
			$targetFields = $postArr['tf'];
			$sourceFields = $postArr['sf'];
			for($x = 0;$x<count($targetFields);$x++){
				if($targetFields[$x]){
					$tField = $targetFields[$x];
					if($tField == 'unmapped') $tField .= '-'.$x;
					$this->occurFieldMap[$tField]['field'] = $sourceFields[$x];
				}
			}
			if(isset($postArr['dbpk']) && $postArr['dbpk']) $this->occurFieldMap['dbpk']['field'] = $postArr['dbpk'];

			//Set field map for identification history
			if(array_key_exists('ID-sf',$postArr)){
				$targetIdFields = $postArr['ID-tf'];
				$sourceIdFields = $postArr['ID-sf'];
				for($x = 0;$x<count($targetIdFields);$x++){
					if($targetIdFields[$x]){
						$tIdField = $targetIdFields[$x];
						if($tIdField == 'unmapped') $tIdField .= '-'.$x;
						$this->identFieldMap[$tIdField]['field'] = $sourceIdFields[$x];
					}
				}
			}
			//Set field map for image history
			if(array_key_exists('IM-sf',$postArr)){
				$targetImFields = $postArr['IM-tf'];
				$sourceImFields = $postArr['IM-sf'];
				for($x = 0;$x<count($targetImFields);$x++){
					if($targetImFields[$x]){
						$tImField = $targetImFields[$x];
						if($tImField == 'unmapped') $tImField .= '-'.$x;
						$this->imageFieldMap[$tImField]['field'] = $sourceImFields[$x];
					}
				}
			}
		}
	}

	public function setFieldMap($fm){
		$this->occurFieldMap = $fm;
	}

	public function getFieldMap(){
		return $this->occurFieldMap;
	}

	public function setIdentFieldMap($fm){
		$this->identFieldMap = $fm;
	}

	public function setImageFieldMap($fm){
		$this->imageFieldMap = $fm;
	}

	public function getDbpk(){
		$dbpk = '';
		if(array_key_exists('dbpk',$this->occurFieldMap)) $dbpk = strtolower($this->occurFieldMap['dbpk']['field']);
		return $dbpk;
	}

	public function loadFieldMap($autoBuildFieldMap = false){
		//Get Field Map for $fieldMap
		if($this->uspid && !$this->occurFieldMap){
			switch ($this->uploadType) {
				case $this->FILEUPLOAD:
				case $this->SKELETAL:
				case $this->DWCAUPLOAD:
				case $this->IPTUPLOAD:
				case $this->SYMBIOTA:
				case $this->DIRECTUPLOAD:
				case $this->SCRIPTUPLOAD:
					$sql = 'SELECT sourcefield, symbspecfield FROM uploadspecmap WHERE (uspid = '.$this->uspid.')';
					$rs = $this->conn->query($sql);
					while($row = $rs->fetch_object()){
						$symbFieldPrefix = substr($row->symbspecfield,0,3);
						$symbFieldName = substr($row->symbspecfield,3);
						if($symbFieldPrefix == 'ID-'){
							$this->identFieldMap[$symbFieldName]["field"] = $row->sourcefield;
						}
						elseif($symbFieldPrefix == 'IM-'){
							$this->imageFieldMap[$symbFieldName]["field"] = $row->sourcefield;
						}
						else{
							$this->occurFieldMap[$row->symbspecfield]["field"] = $row->sourcefield;
						}
					}
					$rs->free();
			}
		}

		//Get uploadspectemp metadata
		$this->setSkipOccurFieldArr();
		if($this->uploadType == $this->RESTOREBACKUP){
			unset($this->skipOccurFieldArr);
			$this->skipOccurFieldArr = array();
		}
		//Other to deal with/skip later: 'ownerinstitutioncode'
		$sql = "SHOW COLUMNS FROM uploadspectemp";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$field = strtolower($row->Field);
			if(!in_array($field,$this->skipOccurFieldArr)){
				if($autoBuildFieldMap){
					$this->occurFieldMap[$field]["field"] = $field;
				}
				$type = $row->Type;
				$this->symbFields[] = $field;
				if(array_key_exists($field,$this->occurFieldMap)){
					if(strpos($type,"double") !== false || strpos($type,"int") !== false){
						$this->occurFieldMap[$field]["type"] = "numeric";
					}
					elseif(strpos($type,"decimal") !== false){
						$this->occurFieldMap[$field]["type"] = "decimal";
						if(preg_match('/\((.*)\)$/', $type, $matches)){
							$this->occurFieldMap[$field]["size"] = $matches[1];
						}
					}
					elseif(strpos($type,"date") !== false){
						$this->occurFieldMap[$field]["type"] = "date";
					}
					else{
						$this->occurFieldMap[$field]["type"] = "string";
						if(preg_match('/\((\d+)\)$/', $type, $matches)){
							$this->occurFieldMap[$field]["size"] = substr($matches[0],1,strlen($matches[0])-2);
						}
					}
				}
			}
		}
		$rs->free();
		//Add additional fields that are used for mapping to other fields just before record is imported into uploadspectemp
		$this->symbFields[] = 'coordinateuncertaintyradius';
		$this->symbFields[] = 'coordinateuncertaintyunits';
		$this->symbFields[] = 'authorspecies';
		$this->symbFields[] = 'authorinfraspecific';
		sort($this->symbFields);
		if($this->paleoSupport) $this->symbFields = array_merge($this->symbFields,$this->getPaleoTerms());
		// if($this->materialSampleSupport) $this->symbFields = array_merge($this->symbFields,$this->getMaterialSampleTerms());

	/*	//Associated Occurrence fields
		// All-purpose fields
		$this->symbFields[] = 'associatedOccurrences';
		$this->symbFields[] = 'associatedOccurrence:type';
		$this->symbFields[] = 'associatedOccurrence:basisOfRecord';
		$this->symbFields[] = 'associatedOccurrence:relationship';
		$this->symbFields[] = 'associatedOccurrence:subType';
		$this->symbFields[] = 'associatedOccurrence:locationOnHost';
		$this->symbFields[] = 'associatedOccurrence:notes';
		// internalOccurrence
		$this->symbFields[] = 'associatedOccurrence:occidAssociate';
		// externalOccurrence
		$this->symbFields[] = 'associatedOccurrence:identifier';
		$this->symbFields[] = 'associatedOccurrence:resourceUrl';
		// genericObservation
		$this->symbFields[] = 'associatedOccurrence:verbatimSciname';
	*/
		//Specify fields
		$this->symbFields[] = 'specify:subspecies';
		$this->symbFields[] = 'specify:subspecies_author';
		$this->symbFields[] = 'specify:variety';
		$this->symbFields[] = 'specify:variety_author';
		$this->symbFields[] = 'specify:forma';
		$this->symbFields[] = 'specify:forma_author';
		$this->symbFields[] = 'specify:collector_first_name';
		$this->symbFields[] = 'specify:collector_middle_initial';
		$this->symbFields[] = 'specify:collector_last_name';
		$this->symbFields[] = 'specify:determiner_first_name';
		$this->symbFields[] = 'specify:determiner_middle_initial';
		$this->symbFields[] = 'specify:determiner_last_name';
		$this->symbFields[] = 'specify:qualifier_position';
		$this->symbFields[] = 'specify:latitude1';
		$this->symbFields[] = 'specify:latitude2';
		$this->symbFields[] = 'specify:longitude1';
		$this->symbFields[] = 'specify:longitude2';
		$this->symbFields[] = 'specify:land_ownership';
		$this->symbFields[] = 'specify:topo_quad';
		$this->symbFields[] = 'specify:georeferenced_by_first_name';
		$this->symbFields[] = 'specify:georeferenced_by_middle_initial';
		$this->symbFields[] = 'specify:georeferenced_by_last_name';
		$this->symbFields[] = 'specify:locality_continued';
		$this->symbFields[] = 'specify:georeferenced_date';
		$this->symbFields[] = 'specify:elevation_(ft)';
		$this->symbFields[] = 'specify:preparer_first_name';
		$this->symbFields[] = 'specify:preparer_middle_initial';
		$this->symbFields[] = 'specify:preparer_last_name';
		$this->symbFields[] = 'specify:prepared_by_date';
		$this->symbFields[] = 'specify:cataloger_first_name';
		$this->symbFields[] = 'specify:cataloger_middle_initial';
		$this->symbFields[] = 'specify:cataloger_last_name';
		$this->symbFields[] = 'specify:cataloged_date';

		switch ($this->uploadType) {
			case $this->FILEUPLOAD:
			case $this->SKELETAL:
			case $this->DWCAUPLOAD:
			case $this->IPTUPLOAD:
			case $this->SYMBIOTA:
			case $this->RESTOREBACKUP:
			case $this->DIRECTUPLOAD:
				//Get identification metadata
				$skipDetFields = array('detid','occid','tidinterpreted','idbyid','appliedstatus','sortsequence','initialtimestamp');
				if($this->uploadType == $this->RESTOREBACKUP){
					unset($skipDetFields);
					$skipDetFields = array();
				}
				$rs = $this->conn->query('SHOW COLUMNS FROM uploaddetermtemp');
				while($r = $rs->fetch_object()){
					$field = strtolower($r->Field);
					if(!in_array($field,$skipDetFields)){
						if($autoBuildFieldMap){
							$this->identFieldMap[$field]["field"] = $field;
						}
						$type = $r->Type;
						$this->identSymbFields[] = $field;
						if(array_key_exists($field,$this->identFieldMap)){
							if(strpos($type,"double") !== false || strpos($type,"int") !== false || strpos($type,"decimal") !== false){
								$this->identFieldMap[$field]["type"] = "numeric";
							}
							elseif(strpos($type,"date") !== false){
								$this->identFieldMap[$field]["type"] = "date";
							}
							else{
								$this->identFieldMap[$field]["type"] = "string";
								if(preg_match('/\(\d+\)$/', $type, $matches)){
									$this->identFieldMap[$field]["size"] = substr($matches[0],1,strlen($matches[0])-2);
								}
							}
						}
					}
				}
				$rs->free();

				$this->identSymbFields[] = 'genus';
				$this->identSymbFields[] = 'specificepithet';
				$this->identSymbFields[] = 'taxonrank';
				$this->identSymbFields[] = 'infraspecificepithet';
				$this->identSymbFields[] = 'coreid';

				//Get image metadata
				$skipImageFields = array('tid','creatorUid','imagetype','occid','dbpk','specimengui','collid','username','sortsequence','initialtimestamp');
				if($this->uploadType == $this->RESTOREBACKUP){
					unset($skipImageFields);
					$skipImageFields = array();
				}
				$rs = $this->conn->query('SHOW COLUMNS FROM uploadimagetemp');
				while($r = $rs->fetch_object()){
					$field = strtolower($r->Field);
					if(!in_array($field,$skipImageFields)){
						if($autoBuildFieldMap){
							$this->imageFieldMap[$field]["field"] = $field;
						}
						$type = $r->Type;
						$this->imageSymbFields[] = $field;
						if(array_key_exists($field,$this->imageFieldMap)){
							if(strpos($type,"double") !== false || strpos($type,"int") !== false || strpos($type,"decimal") !== false){
								$this->imageFieldMap[$field]["type"] = "numeric";
							}
							elseif(strpos($type,"date") !== false){
								$this->imageFieldMap[$field]["type"] = "date";
							}
							else{
								$this->imageFieldMap[$field]["type"] = "string";
								if(preg_match('/\(\d+\)$/', $type, $matches)){
									$this->imageFieldMap[$field]["size"] = substr($matches[0],1,strlen($matches[0])-2);
								}
							}
						}
					}
				}
				$rs->free();
		}
	}

	public function echoFieldMapTable($autoMap, $mode){
		$prefix = '';
		$fieldMap = $this->occurFieldMap;
		$symbFieldsRaw = $this->symbFields;
		$sourceArr = $this->occurSourceArr;
		$translationMap = array('accession'=>'catalognumber','accessionid'=>'catalognumber','accessionnumber'=>'catalognumber','guid'=>'occurrenceid',
			'taxonfamilyname'=>'family','scientificname'=>'sciname','fullname'=>'sciname','speciesauthor'=>'authorspecies','species'=>'specificepithet','commonname'=>'taxonremarks',
			'observer'=>'recordedby','collector'=>'recordedby','primarycollector'=>'recordedby','field:collector'=>'recordedby','collectedby'=>'recordedby',
			'userlogin'=>'recordedby','collectornumber'=>'recordnumber','collectionnumber'=>'recordnumber','field:collectorfieldnumber'=>'recordnumber','collectors'=>'associatedcollectors',
			'datecollected'=>'eventdate','date'=>'eventdate','collectiondate'=>'eventdate','observedon'=>'eventdate','dateobserved'=>'eventdate','collectionstartdate'=>'eventdate','collectionverbatimdate'=>'verbatimeventdate',
			'cf' => 'identificationqualifier','qualifier'=>'identificationqualifier','position'=>'specify:qualifier_position','detby'=>'identifiedby','determinor'=>'identifiedby',
			'determinationdate'=>'dateidentified','determineddate'=>'dateidentified','determinedremarks'=>'identificationremarks','placecountryname'=>'country',
			'placestatename'=>'stateprovince','state'=>'stateprovince','placecountyname'=>'county','municipiocounty'=>'county','location'=>'locality','field:localitydescription'=>'locality',
			'placeguess'=>'locality','localitynotes'=>'locationremarks','latitude'=>'verbatimlatitude','longitude'=>'verbatimlongitude',
			'errorradius'=>'coordinateuncertaintyradius','publicpositionalaccuracy'=>'coordinateuncertaintyinmeters','errorradiusunits'=>'coordinateuncertaintyunits','errorradiusunit'=>'coordinateuncertaintyunits',
			'datum'=>'geodeticdatum','utmzone'=>'utmzoning','township'=>'trstownship','range'=>'trsrange','section'=>'trssection','georeferencingsource'=>'georeferencesources','georefremarks'=>'georeferenceremarks',
			'elevationmeters'=>'minimumelevationinmeters','minelevationm'=>'minimumelevationinmeters','maxelevationm'=>'maximumelevationinmeters','verbatimelev'=>'verbatimelevation',
			'field:associatedspecies'=>'associatedtaxa','associatedspecies'=>'associatedtaxa','assoctaxa'=>'associatedtaxa','specimennotes'=>'occurrenceremarks','notes'=>'occurrenceremarks',
			'generalnotes'=>'occurrenceremarks','plantdescription'=>'verbatimattributes','description'=>'verbatimattributes','specimendescription'=>'verbatimattributes',
			'phenology'=>'reproductivecondition','field:habitat'=>'habitat','habitatdescription'=>'habitat','sitedeschabitat'=>'habitat','captivecultivated'=>'cultivationstatus',
			'ometid'=>'exsiccatiidentifier','exsiccataeidentifier'=>'exsiccatiidentifier','exsnumber'=>'exsiccatinumber','exsiccataenumber'=>'exsiccatinumber',
			'group'=>'paleo-lithogroup',//'materialsample-materialsampleid'=>'materialsample-guid','preparationdetails'=>'materialsample-preparationprocess','materialsampletype'=>'materialsample-sampletype',
			'lithostratigraphic'=>'paleo-lithology','imageurl'=>'associatedmedia','subject_references'=>'tempfield01',
			'subject_recordid'=>'tempfield02'
		);
		$autoMapExclude = array('institutioncode','collectioncode');

		if($this->paleoSupport){
			$paleoArr = $this->getPaleoTerms();
			foreach($paleoArr as $v){
				$translationMap[substr($v,6)] = $v;
			}
		}
		// if($this->materialSampleSupport){
		// 	$msArr = $this->getMaterialSampleTerms();
		// 	foreach($msArr as $term){
		// 		$term = strtolower($term);
		// 		$baseTerm = substr($term,15);
		// 		if($baseTerm != 'individualcount' && $baseTerm != 'disposition' && $baseTerm != 'catalognumber') $translationMap[$baseTerm] = $term;
		// 	}
		// }
		if($mode == 'ident'){
			$prefix = 'ID-';
			$fieldMap = $this->identFieldMap;
			$symbFieldsRaw = $this->identSymbFields;
			$sourceArr = $this->identSourceArr;
			$translationMap = array('scientificname'=>'sciname','identificationiscurrent'=>'iscurrent','detby'=>'identifiedby','determinor'=>'identifiedby',
				'determinationdate'=>'dateidentified','notes'=>'identificationremarks','cf' => 'identificationqualifier');
		}
		elseif($mode == 'image'){
			$prefix = 'IM-';
			$fieldMap = $this->imageFieldMap;
			$symbFieldsRaw = $this->imageSymbFields;
			$sourceArr = $this->imageSourceArr;
			$translationMap = array('accessuri'=>'originalurl','thumbnailaccessuri'=>'thumbnailurl','goodqualityaccessuri'=>'url',
				'providermanagedid'=>'sourceidentifier','usageterms'=>'copyright','webstatement'=>'accessrights','creator'=>'creator',
				'comments'=>'notes','associatedspecimenreference'=>'referenceurl');
		}

		$symbFields = array();
		foreach($symbFieldsRaw as $sValue){
			$symbFields[$sValue] = strtolower($sValue);
		}

		//Build a Source => Symbiota field Map
		$sourceSymbArr = Array();
		foreach($fieldMap as $symbField => $fArr){
			if($symbField != 'dbpk') $sourceSymbArr[$fArr["field"]] = $symbField;
		}

		if($this->uploadType == $this->NFNUPLOAD && !in_array('subject_references', $this->occurSourceArr) && !in_array('subject_recordid', $this->occurSourceArr)){
			echo '<div style="color:red">ERROR: input file does not contain proper identifier field (e.g. occid as subject_references or recordID as subject_recordid)</div>';
			return false;
		}
		//Output table rows for source data
		echo '<table class="styledtable" style="width:600px;font-size:12px;">';
		echo '<tr><th>Source Field</th><th>Target Field</th></tr>'."\n";
		foreach($sourceArr as $fieldName){
			if($fieldName == 'coreid') continue;
			$diplayFieldName = $fieldName;
			$fieldName = trim(strtolower($fieldName));
			if($this->uploadType == $this->NFNUPLOAD && ($fieldName == 'subject_recordid' || $fieldName == 'subject_references')){
				echo '<input type="hidden" name="sf[]" value="'.$fieldName.'" />';
				echo '<input type="hidden" name="tf[]" value="'.$translationMap[$fieldName].'" />';
			}
			else{
				if($this->uploadType == $this->NFNUPLOAD && substr($fieldName,0,8) == 'subject_') continue;
				$isAutoMapped = false;
				$tranlatedFieldName = str_replace(array('_',' ','.','(',')'),'',$fieldName);
				if($autoMap){
					if(array_key_exists($tranlatedFieldName,$translationMap)) $tranlatedFieldName = strtolower($translationMap[$tranlatedFieldName]);
					if(in_array($tranlatedFieldName,$symbFields) && !in_array($fieldName,$autoMapExclude)){
						$isAutoMapped = true;
					}
					elseif(in_array('specify:'.$fieldName,$symbFields)){
						$tranlatedFieldName = strtolower('specify:'.$fieldName);
						$isAutoMapped = true;
					}
					// elseif(in_array('associatedOccurrence:'.$fieldName,$symbFields)){
					// 	$tranlatedFieldName = strtolower('associatedOccurrence:'.$fieldName);
					// 	$isAutoMapped = true;
					// }
				}
				echo "<tr>\n";
				echo '<td style="padding:2px;">';
				echo $diplayFieldName;
				echo '<input type="hidden" name="'.$prefix.'sf[]" value="'.$fieldName.'" />';
				echo '</td>';
				echo '<td>';
				$className = '';
				if(!$isAutoMapped && !array_key_exists($fieldName,$sourceSymbArr)) $className = 'unmapped';
				echo '<select name="'.$prefix.'tf[]" class="'.$className.'">';
				echo '<option value="">Select Target Field</option>';
				echo '<option value="unmapped"'.(isset($sourceSymbArr[$fieldName]) && substr($sourceSymbArr[$fieldName],0,8)=='unmapped'?'SELECTED':'').'>Leave Field Unmapped</option>';
				echo '<option value="">-------------------------</option>';
				if(array_key_exists($fieldName,$sourceSymbArr)){
					//Source Field is mapped to Symbiota Field
					foreach($symbFields as $sFieldDisplay => $sField){
						echo "<option ".($sourceSymbArr[$fieldName]==$sField?"SELECTED":"").">".$sFieldDisplay."</option>\n";
					}
				}
				elseif($isAutoMapped){
					//Source Field = Symbiota Field
					foreach($symbFields as $sFieldDisplay => $sField){
						$selStr = '';
						if($tranlatedFieldName==$sField && !in_array($sField,$autoMapExclude)) $selStr = 'SELECTED';
						echo '<option '.$selStr.'>'.$sFieldDisplay.'</option>';
					}
				}
				else{
					foreach($symbFields as $sFieldDisplay => $sField){
						echo '<option>'.$sFieldDisplay.'</option>';
					}
				}
				echo "</select></td>\n";
				echo "</tr>\n";
			}
		}
		echo '</table>';
		return true;
	}

	public function saveFieldMap($postArr){
		$statusStr = '';
		if(array_key_exists('sf',$postArr)){
			if(!$this->uspid && array_key_exists('profiletitle',$postArr)){
				$this->uspid = $this->createUploadProfile(array('uploadtype'=>$this->uploadType,'title'=>$postArr['profiletitle']));
				$this->readUploadParameters();
			}
			if($this->uspid){
				$this->deleteFieldMap();
				$sqlInsert = 'INSERT INTO uploadspecmap(uspid,symbspecfield,sourcefield) ';
				$sqlValues = 'VALUES ('.$this->uspid;
				foreach($this->occurFieldMap as $k => $v){
					$sourceField = $v['field'];
					$sql = $sqlInsert.$sqlValues.',"'.$k.'","'.$sourceField.'")';
					if(!$this->conn->query($sql)){
						$statusStr = 'ERROR saving field map: '.$this->conn->error;
					}
				}
				//Save custom occurrence filter variables
				if($this->filterArr){
					$sql = 'UPDATE uploadspecparameters SET querystr = "'.$this->cleanInStr(json_encode($this->filterArr)).'" WHERE uspid = '.$this->uspid;
					if(!$this->conn->query($sql)){
						$statusStr = 'ERROR saving custom filter variables: '.$this->conn->error;
					}
				}
				//Save identification field map
				foreach($this->identFieldMap as $k => $v){
					$sourceField = $v["field"];
					$sql = $sqlInsert.$sqlValues.",'ID-".$k."','".$sourceField."')";
					//echo "<div>".$sql."</div>";
					if(!$this->conn->query($sql)){
						$statusStr = 'ERROR saving identification field map: '.$this->conn->error;
					}
				}
				//Save image field map
				foreach($this->imageFieldMap as $k => $v){
					$sourceField = $v["field"];
					$sql = $sqlInsert.$sqlValues.",'IM-".$k."','".$sourceField."')";
					//echo "<div>".$sql."</div>";
					if(!$this->conn->query($sql)){
						$statusStr = 'ERROR saving image field map: '.$this->conn->error;
					}
				}

			}
		}
		return $statusStr;
	}

	public function deleteFieldMap(){
		$statusStr = '';
		if($this->uspid){
			$sql = 'DELETE FROM uploadspecmap WHERE (uspid = '.$this->uspid.') ';
			//echo "<div>$sql</div>";
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR deleting field map: '.$this->conn->error;
			}
			$sql = 'UPDATE uploadspecparameters SET querystr = NULL WHERE (uspid = '.$this->uspid.') ';
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR deleting field map: '.$this->conn->error;
			}
			$this->queryStr = '';
		}
		return $statusStr;
	}

	public function analyzeUpload(){
		return true;
	}

	protected function prepUploadData(){
		$this->outputMsg('<li>Clearing staging tables</li>');
		$sqlDel1 = 'DELETE FROM uploadspectemp WHERE (collid IN('.$this->collId.'))';
		$this->conn->query($sqlDel1);
		$sqlDel2 = 'DELETE FROM uploaddetermtemp WHERE (collid IN('.$this->collId.'))';
		$this->conn->query($sqlDel2);
		$sqlDel3 = 'DELETE FROM uploadimagetemp WHERE (collid IN('.$this->collId.'))';
		$this->conn->query($sqlDel3);
		$sqlDel4 = 'DELETE FROM uploadkeyvaluetemp WHERE (collid IN('.$this->collId.'))';
		$this->conn->query($sqlDel4);
	}

	public function uploadData($finalTransfer){
		//Stored Procedure upload; other upload types are controlled by their specific class functions
		$this->outputMsg('<li>Initiating data upload</li>');
		$this->prepUploadData();

		if($this->uploadType == $this->STOREDPROCEDURE){
			$this->cleanUpload();
		}
		elseif($this->uploadType == $this->SCRIPTUPLOAD){
			if(system($this->queryStr)){
				$this->outputMsg('<li>Script Upload successful</li>');
				$this->outputMsg('<li>Initializing final transfer steps...</li>');
				$this->cleanUpload();
			}
		}
		if($finalTransfer) $this->finalTransfer();
		else $this->outputMsg('<li>Record upload complete, ready for final transfer and activation</li>');
	}

	protected function cleanUpload(){

		if($this->collMetadataArr["managementtype"] == 'Snapshot' || $this->collMetadataArr["managementtype"] == 'Aggregate'){
			//If collection is a snapshot, map upload to existing records. These records will be updated rather than appended
			$this->outputMsg('<li>Linking records (e.g. matching Primary Identifier)... </li>');
			$this->updateOccidMatchingDbpk();
		}

		$this->prepareAssociatedMedia();

		//Run custom cleaning Stored Procedure, if one exists
		if($this->storedProcedure){
			if($this->conn->query('CALL '.$this->storedProcedure)){
				$this->outputMsg('<li>Stored procedure executed: '.$this->storedProcedure.'</li>');
				if($this->conn->more_results()) $this->conn->next_result();
			}
			else{
				$this->outputMsg('<li><span style="color:red;">ERROR: Stored Procedure failed ('.$this->storedProcedure.'): '.$this->conn->error.'</span></li>');
			}
		}

		if($this->collMetadataArr["managementtype"] == 'Live Data' || $this->uploadType == $this->SKELETAL){
			if($this->matchCatalogNumber){
				if(!$this->updateOccidMatchingCatalogNumber()){
					$this->outputMsg('<li><span style="color:red;">Warning: unable to match on catalog number: '.$this->errorStr.'</span></li>');
				}
			}
			if($this->matchOtherCatalogNumbers){
				if(!$this->updateOccidMatchingOtherCatalogNumbers()){
					$this->outputMsg('<li><span style="color:red;">Warning: unable to match on otherCatalogNumbers/omoccuridentifiers: '.$this->errorStr.'</span></li>');
				}
			}
		}
		if($this->collMetadataArr["managementtype"] == 'Live Data'){
			//Make sure that explicitly set occurrenceID GUIDs are not lost during special imports using catalogNumber matching
			$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid SET u.occurrenceID = o.occurrenceID WHERE o.occurrenceID IS NOT NULL AND u.occurrenceID IS NULL ';
			if(!$this->conn->query($sql)){
				$this->outputMsg('<li><span style="color:red;">Warning: issue attempting to preserve explicitly defined GUID (e.g. externally generated GUIDs) within a Live Managed collection: '.$this->conn->error.'</span></li>');
			}
		}

		//Links UploadKeyValueTemp occid to uploadspectemp's based on dbfk
		$this->linkTempKeyValueOccurrences();

		//Prefrom general cleaning and parsing tasks
		$this->recordCleaningStage1();

		$this->cleanImages();
		//Reset $treansferCnt so that count is accurate since some records may have been deleted due to data integrety issues
		$this->setTransferCount();
		$this->setIdentTransferCount();
		$this->setImageTransferCount();
	}

	private function linkTempKeyValueOccurrences() {
		$this->outputMsg('<li>Linking key value data to occurrences...</li>');
		$sql = 'UPDATE uploadkeyvaluetemp kv JOIN uploadspectemp s ON s.dbpk = kv.dbpk SET kv.occid = s.occid, kv.collid = s.collid';
		$this->conn->query($sql);
	}

	private function recordCleaningStage1(){
		$this->outputMsg('<li>Data cleaning:</li>');
		$this->outputMsg('<li style="margin-left:10px;">Cleaning event dates...</li>');

		$sql = 'UPDATE uploadspectemp u '.
			'SET u.year = YEAR(u.eventDate) '.
			'WHERE (u.collid IN('.$this->collId.')) AND (u.eventDate IS NOT NULL) AND (u.year IS NULL)';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u '.
			'SET u.month = MONTH(u.eventDate) '.
			'WHERE (u.collid IN('.$this->collId.')) AND (u.month IS NULL) AND (u.eventDate IS NOT NULL)';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u '.
			'SET u.day = DAY(u.eventDate) '.
			'WHERE u.collid IN('.$this->collId.') AND u.day IS NULL AND u.eventDate IS NOT NULL';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u '.
			'SET u.startDayOfYear = DAYOFYEAR(u.eventDate) '.
			'WHERE u.collid IN('.$this->collId.') AND u.startDayOfYear IS NULL AND u.eventDate IS NOT NULL';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u '.
			'SET u.endDayOfYear = DAYOFYEAR(u.eventDate2) '.
			'WHERE u.collid IN('.$this->collId.') AND u.endDayOfYear IS NULL AND u.eventDate2 IS NOT NULL';
		$this->conn->query($sql);

		$sql = 'UPDATE IGNORE uploadspectemp u
			SET u.eventDate = CONCAT_WS("-",LPAD(u.year,4,"19"),IFNULL(LPAD(u.month,2,"0"),"00"),IFNULL(LPAD(u.day,2,"0"),"00"))
			WHERE (u.eventDate IS NULL) AND (u.year > 1300) AND (u.year <= '.date('Y').') AND (collid IN('.$this->collId.'))';
		$this->conn->query($sql);

		$this->outputMsg('<li style="margin-left:10px;">Cleaning country and state/province ...</li>');
		//Convert country abbreviations to full spellings
		$sql = 'UPDATE uploadspectemp u INNER JOIN geographicthesaurus c ON u.country = c.iso3
			SET u.country = c.geoTerm
			WHERE c.geolevel = 50 AND (u.collid IN('.$this->collId.'))';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadspectemp u INNER JOIN geographicthesaurus c ON u.country = c.iso2
			SET u.country = c.geoTerm
			WHERE c.geolevel = 50 AND (u.collid IN('.$this->collId.'))';
		$this->conn->query($sql);

		//Convert state abbreviations to full spellings
		$sql = 'UPDATE uploadspectemp u INNER JOIN geographicthesaurus s ON u.stateProvince = s.abbreviation
			SET u.stateProvince = s.geoTerm
			WHERE s.geoLevel = 60 AND u.collid IN('.$this->collId.')';
		$this->conn->query($sql);

		//Fill null country with state matches
		$sql = 'UPDATE uploadspectemp u INNER JOIN geographicthesaurus s ON u.stateprovince = s.geoTerm '.
			'INNER JOIN geographicthesaurus c ON s.parentID = c.geoThesID '.
			'SET u.country = "United States" '.
			'WHERE s.geoLevel = 60 AND c.geoLevel = 50 AND u.country IS NULL AND c.geoTerm = "United States" AND u.collid IN('.$this->collId.')';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadspectemp u INNER JOIN geographicthesaurus s ON u.stateprovince = s.geoterm '.
			'INNER JOIN geographicthesaurus c ON s.parentID = c.geoThesID '.
			'SET u.country = c.geoterm '.
			'WHERE s.geoLevel = 60 AND c.geoLevel = 50 AND u.country IS NULL AND u.collid IN('.$this->collId.')';
		$this->conn->query($sql);

		$this->outputMsg('<li style="margin-left:10px;">Cleaning coordinates...</li>');
		$sql = 'UPDATE uploadspectemp '.
			'SET DecimalLongitude = -1*DecimalLongitude '.
			'WHERE (DecimalLongitude > 0) AND (Country IN("USA","United States","U.S.A.","Canada","Mexico")) AND (stateprovince != "Alaska" OR stateprovince IS NULL) AND (collid IN('.$this->collId.'))';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET DecimalLatitude = NULL, DecimalLongitude = NULL '.
			'WHERE DecimalLatitude = 0 AND DecimalLongitude = 0 AND collid IN('.$this->collId.')';
		$this->conn->query($sql);

		//Move illegal coordinates to verbatim
		$sql = 'UPDATE uploadspectemp '.
			'SET verbatimcoordinates = CONCAT_WS(" ",DecimalLatitude, DecimalLongitude) '.
			'WHERE verbatimcoordinates IS NULL AND collid IN('.$this->collId.') '.
			'AND (DecimalLatitude < -90 OR DecimalLatitude > 90 OR DecimalLongitude < -180 OR DecimalLongitude > 180)';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET DecimalLatitude = NULL, DecimalLongitude = NULL '.
			'WHERE collid IN('.$this->collId.') AND (DecimalLatitude < -90 OR DecimalLatitude > 90 OR DecimalLongitude < -180 OR DecimalLongitude > 180)';
		$this->conn->query($sql);

		$this->outputMsg('<li style="margin-left:10px;">Cleaning taxonomy...</li>');
		$sql = 'UPDATE uploadspectemp SET family = sciname WHERE (family IS NULL) AND (sciname LIKE "%aceae" OR sciname LIKE "%idae")';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp SET sciname = family WHERE (family IS NOT NULL) AND (sciname IS NULL) ';
		$this->conn->query($sql);

		#Updating records with null author
		$sql = 'UPDATE uploadspectemp u INNER JOIN taxa t ON u.sciname = t.sciname '.
			'SET u.scientificNameAuthorship = t.author '.
			'WHERE u.scientificNameAuthorship IS NULL AND t.author IS NOT NULL';
		$this->conn->query($sql);

		//Lock security setting if set so that local system can't override
		$sql = 'UPDATE uploadspectemp '.
			'SET localitySecurityReason = "Locked: set via import file" '.
			'WHERE localitySecurity > 0 AND localitySecurityReason IS NULL AND collid IN('.$this->collId.')';
		$this->conn->query($sql);

		if($this->sourceDatabaseType == 'specify'){
			$sql = 'UPDATE uploaddetermtemp SET isCurrent = 1 WHERE collid IN('.$this->collId.')';
			$this->conn->query($sql);
		}

		$this->outputMsg('<li style="margin-left:10px;">Ensuring current identifications are set properly set within central occurrence table...</li>');
		$sql = 'UPDATE uploadspectemp s INNER JOIN uploaddetermtemp d ON s.dbpk = d.dbpk
			SET s.sciname = d.sciname, s.identifiedBy = if(d.identifiedBy="", NULL, d.identifiedBy), s.dateIdentified = if(d.dateIdentified="", NULL, d.dateIdentified),
			s.scientificNameAuthorship = d.scientificNameAuthorship, s.identificationQualifier = d.identificationQualifier,
			s.identificationReferences = d.identificationReferences, s.identificationRemarks = d.identificationRemarks
			WHERE s.collid IN('.$this->collId.') AND d.collid IN('.$this->collId.') AND d.isCurrent = 1 AND s.sciname IS NULL AND s.identifiedBy IS NULL AND s.dateIdentified IS NULL ';
		$this->conn->query($sql);

		$this->outputMsg('<li style="margin-left:10px;">Setting basisOfRecord for new records, if not designated within import file...</li>');
		$borValue = 'PreservedSpecimen';
		if(strpos($this->collMetadataArr['colltype'], 'Observations') !== false) $borValue = 'HumanObservation';
		$sql = 'UPDATE uploadspectemp SET basisOfRecord = "'.$borValue.'" WHERE basisOfRecord IS NULL AND occid IS NULL';
		$this->conn->query($sql);
	}

	public function getTransferReport(){
		$reportArr = array();
		$reportArr['occur'] = $this->getTransferCount();

		//Number of new specimen records
		$sql = 'SELECT count(*) AS cnt '.
			'FROM uploadspectemp u LEFT JOIN omoccurrences o ON u.occid = o.occid '.
			'WHERE (u.collid IN('.$this->collId.')) AND (u.occid IS NULL OR o.occid IS NULL)';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$reportArr['new'] = $r->cnt;
		}
		$rs->free();

		//Number of matching records that will be updated
		$sql = 'SELECT count(*) AS cnt FROM uploadspectemp WHERE (occid IS NOT NULL) AND (collid IN('.$this->collId.'))';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$reportArr['update'] = $r->cnt;
		}
		$rs->free();

		if($this->collMetadataArr['managementtype'] == 'Live Data' && !$this->matchCatalogNumber  && !$this->matchOtherCatalogNumbers && $this->uploadType != $this->RESTOREBACKUP){
			//Records that can be matched on Catalog Number, but will be appended
			$matchAppendArr = array();
			$sql = 'SELECT DISTINCT o.occid
				FROM uploadspectemp u INNER JOIN omoccurrences o ON u.catalogNumber = o.catalogNumber
				WHERE (u.collid IN('.$this->collId.')) AND (o.collid IN('.$this->collId.')) AND (u.occid IS NULL) ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$matchAppendArr[$r->occid] = 1;
			}
			$rs->free();

			$sql = 'SELECT DISTINCT o.occid
				FROM uploadspectemp u INNER JOIN omoccurrences o ON u.othercatalogNumbers = o.othercatalogNumbers
				WHERE (u.collid IN('.$this->collId.')) AND (o.collid IN('.$this->collId.')) AND (u.occid IS NULL) ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$matchAppendArr[$r->occid] = 1;
			}
			$rs->free();

			$sql = 'SELECT DISTINCT o.occid
				FROM uploadspectemp u INNER JOIN omoccuridentifiers i ON u.othercatalogNumbers = i.identifierValue
				INNER JOIN omoccurrences o ON i.occid = o.occid
				WHERE (u.collid IN('.$this->collId.')) AND (o.collid IN('.$this->collId.')) AND (u.occid IS NULL) ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$matchAppendArr[$r->occid] = 1;
			}
			$rs->free();

			$reportArr['matchappend'] = count($matchAppendArr);
		}

		if($this->uploadType == $this->RESTOREBACKUP || ($this->collMetadataArr["managementtype"] == 'Snapshot' && $this->uploadType != $this->SKELETAL)){
			//Records already in portal that won't match with an incoming record
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o LEFT JOIN uploadspectemp u  ON (o.occid = u.occid) '.
				'WHERE (o.collid IN('.$this->collId.')) AND (u.occid IS NULL)';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$reportArr['exist'] = $r->cnt;
			}
			$rs->free();
		}

		if($this->uploadType != $this->SKELETAL && $this->collMetadataArr["managementtype"] == 'Snapshot' && $this->uploadType != $this->RESTOREBACKUP){
			//Match records that were processed via the portal, walked back to collection's central database, and come back to portal
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
				'WHERE (u.collid IN('.$this->collId.')) AND (u.occid IS NULL) AND (u.catalogNumber IS NOT NULL) '.
				'AND (o.catalogNumber IS NOT NULL) AND (o.dbpk IS NULL)';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$reportArr['sync'] = $r->cnt;
				$newCnt = $reportArr['new'] - $r->cnt;
				if($newCnt >= -1) $reportArr['new'] = $newCnt;
				$reportArr['update'] += $r->cnt;
				$existCnt = $reportArr['exist'] - $r->cnt;
				if($existCnt >= -1) $reportArr['exist'] = $existCnt;
			}
			$rs->free();
		}

		if($this->uploadType != $this->SKELETAL && $this->uploadType != $this->RESTOREBACKUP && ($this->collMetadataArr["managementtype"] == 'Snapshot' || $this->collMetadataArr["managementtype"] == 'Aggregate')){
			//Look for null dbpk
			$sql = 'SELECT count(*) AS cnt FROM uploadspectemp WHERE (dbpk IS NULL) AND (collid IN('.$this->collId.'))';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$reportArr['nulldbpk'] = $r->cnt;
			}
			$rs->free();

			//Look for duplicate dbpk
			$sql = 'SELECT dbpk FROM uploadspectemp GROUP BY dbpk, collid, basisofrecord HAVING (Count(*)>1) AND (collid IN('.$this->collId.'))';
			$rs = $this->conn->query($sql);
			$reportArr['dupdbpk'] = $rs->num_rows;
			$rs->free();
		}

		if($this->identTransferCount) $reportArr['ident'] = $this->identTransferCount;
		if($this->imageTransferCount) $reportArr['image'] = $this->imageTransferCount;

		return $reportArr;
	}

	public function finalTransfer(){
		$this->recordCleaningStage2();
		$this->transferOccurrences();
		$this->transferIdentificationHistory();
		$this->transferImages();
		// if($GLOBALS['QUICK_HOST_ENTRY_IS_ACTIVE']) $this->transferHostAssociations();
		// $this->transferAssociatedOccurrences();
		$this->finalCleanup();
		$this->outputMsg('<li style="">Upload Procedure Complete ('.date('Y-m-d h:i:s A').')!</li>');
		$this->outputMsg(' ');
	}

	protected function recordCleaningStage2(){
		$this->outputMsg('<li>Starting Stage 2 cleaning</li>');

		if($this->uploadType == $this->NFNUPLOAD){
			//Remove specimens without links back to source
			$sql = 'DELETE FROM uploadspectemp WHERE (occid IS NULL) AND (collid IN('.$this->collId.'))';
			if(!$this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:10px"><span style="color:red;">ERROR</span> deleting specimens ('.$this->conn->error.')</li>');
			}
		}
		else{
			if($this->collMetadataArr["managementtype"] == 'Snapshot' || $this->uploadType == $this->SKELETAL){
				//Match records that were processed via the portal, walked back to collection's central database, and come back to portal
				$this->outputMsg('<li style="margin-left:10px;">Populating source identifiers (dbpk) to relink specimens processed within portal...</li>');
				$sql = 'UPDATE IGNORE uploadspectemp u INNER JOIN omoccurrences o ON (u.occurrenceID = o.occurrenceID) AND (u.collid = o.collid)
					SET u.occid = o.occid, o.dbpk = u.dbpk
					WHERE (u.collid IN('.$this->collId.')) AND (u.occid IS NULL) AND (o.dbpk IS NULL) ';
				$this->conn->query($sql);

				$sql = 'UPDATE IGNORE uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid)
					SET u.occid = o.occid, o.dbpk = u.dbpk
					WHERE (u.collid IN('.$this->collId.')) AND (u.occid IS NULL) AND (o.dbpk IS NULL)';
				$this->conn->query($sql);
			}

			if(($this->collMetadataArr["managementtype"] == 'Snapshot' && $this->uploadType != $this->SKELETAL) || $this->collMetadataArr["managementtype"] == 'Aggregate'){
				$this->outputMsg('<li style="margin-left:10px;">Remove NULL dbpk values...</li>');
				$sql = 'DELETE FROM uploadspectemp WHERE (dbpk IS NULL) AND (collid IN('.$this->collId.'))';
				$this->conn->query($sql);

				$this->outputMsg('<li style="margin-left:10px;">Remove duplicate dbpk values...</li>');
				$sql = 'DELETE u.* '.
					'FROM uploadspectemp u INNER JOIN (SELECT dbpk FROM uploadspectemp '.
					'GROUP BY dbpk, collid HAVING Count(*)>1 AND collid IN('.$this->collId.')) t2 ON u.dbpk = t2.dbpk '.
					'WHERE collid IN('.$this->collId.')';
				if(!$this->conn->query($sql)){
					$this->outputMsg('<li style="margin-left:10px"><span style="color:red;">ERROR</span> ('.$this->conn->error.')</li>');
				}
			}
		}
	}

	protected function transferOccurrences(){
		//Clean and Transfer records from uploadspectemp to specimens
		if($this->uploadType == $this->NFNUPLOAD){
			//Transfer edits to revision history table
			$this->outputMsg('<li>Transferring edits to versioning tables...</li>');
			$this->versionExternalEdits();
		}
		$this->versionInternalEdits();
		$transactionInterval = 1000;
		$this->outputMsg('<li>Updating existing records in batches of '.$transactionInterval.'... </li>');
		//Grab specimen intervals for updating records in batches
		$intervalArr = array();
		$sql = 'SELECT occid FROM ( SELECT @row := @row +1 AS rownum, occid FROM ( SELECT @row :=0) r, uploadspectemp WHERE occid IS NOT NULL AND collid = '.
			$this->collId.' ORDER BY occid) ranked WHERE rownum % '.$transactionInterval.' = 1';
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$intervalArr[] = $r->occid;
		}
		$rs->free();

		$fieldArr = $this->getTransferFieldArr();
		//Update matching records
		$sqlFragArr = array();
		foreach($fieldArr as $v){
			if($v == 'processingstatus' && $this->processingStatus){
				$sqlFragArr[$v] = 'o.processingStatus = u.processingStatus';
			}
			elseif($this->uploadType == $this->SKELETAL || $this->uploadType == $this->NFNUPLOAD){
				$sqlFragArr[$v] = 'o.'.$v.' = IFNULL(o.'.$v.',u.'.$v.')';
			}
			else{
				$sqlFragArr[$v] = 'o.'.$v.' = u.'.$v;
			}
		}
		$obsUidTarget = 'NULL';
		if($this->uploadType == $this->RESTOREBACKUP) $obsUidTarget = 'u.observeruid';
		elseif($this->observerUid) $obsUidTarget = $this->observerUid;
		$sqlBase = 'UPDATE IGNORE uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid SET o.observeruid = '.$obsUidTarget.','.implode(',',$sqlFragArr);
		if($this->collMetadataArr['managementtype'] == 'Snapshot') $sqlBase .= ', o.dateLastModified = CURRENT_TIMESTAMP() ';
		$sqlBase .= ' WHERE (u.collid IN('.$this->collId.')) ';
		$cnt = 1;
		$previousInterval = 0;
		foreach($intervalArr as $intValue){
			if($previousInterval){
				$sql = $sqlBase.'AND (o.occid BETWEEN '.$previousInterval.' AND '.($intValue-1).') ';
				if($this->conn->query($sql)) $this->outputMsg('<li style="margin-left:10px">'.$cnt.': '.$transactionInterval.' updated ('.$this->conn->affected_rows.' changed)</li>');
				else $this->outputMsg('<li style="margin-left:10px">FAILED updating records: '.$this->conn->error.'</li> ');
				$cnt++;
			}
			$previousInterval = $intValue;
		}
		$sql = $sqlBase.'AND (o.occid >= '.$previousInterval.')';
		if($this->conn->query($sql)) $this->outputMsg('<li style="margin-left:10px">'.$cnt.': '.$this->conn->affected_rows.' updated</li>');
		else $this->outputMsg('<li style="margin-left:10px">ERROR updating records: '.$this->conn->error.'</li> ');

		//Insert new records
		if($this->uploadType != $this->NFNUPLOAD){
			$this->outputMsg('<li>Transferring new records in batches of '.$transactionInterval.'...</li>');
			$insertTarget = 0;
			$sql = 'SELECT COUNT(*) AS cnt FROM uploadspectemp WHERE occid IS NULL AND collid IN('.$this->collId.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()) $insertTarget = $r->cnt;
			$rs->free();
			$cnt = 1;
			while($insertTarget > 0){
				$sql = 'INSERT IGNORE INTO omoccurrences (collid, dbpk, dateentered, observerUid, '.implode(', ',$fieldArr).' ) '.
					'SELECT u.collid, u.dbpk, "'.date('Y-m-d H:i:s').'", '.$obsUidTarget.', u.'.implode(', u.',$fieldArr).' FROM uploadspectemp u '.
					'WHERE u.occid IS NULL AND u.collid IN('.$this->collId.') LIMIT '.$transactionInterval;
				$insertCnt = 0;
				if($this->conn->query($sql)){
					$insertCnt = $this->conn->affected_rows;
					$warnCnt = $this->conn->warning_count;
					if($warnCnt){
						$this->outputMsg('<li style="margin-left:10px"><span style="color:orange">WARNING</span>: '.$warnCnt.' records failed to load:</li>');
						$warning = $this->conn->get_warnings();
						if ($warning) {
							do {
								$this->outputMsg('<li style="margin-left:10px">'.$warning->errno.': '.$warning->message.'</li>');
							} while ($warning->next());
						}
					}
				}
				else{
					$this->outputMsg('<li>FAILED! ERROR: '.$this->conn->error.'</li> ');
					//$this->outputMsg($sql);
				}
				if(!$this->updateOccidMatchingDbpk()){
					$this->outputMsg('<li>ERROR updating occid on recent Insert batch: '.$this->errorStr.'</li> ');
				}
				$this->outputMsg('<li style="margin-left:10px">'.$cnt.': '.$insertCnt.' inserted</li>');
				$insertTarget -= $transactionInterval;
				$cnt++;
			};

			//Link all newly intersted records back to uploadspectemp in prep for loading determiantion history and associatedmedia
			$this->outputMsg('<li>Linking records in prep for loading extended data...</li>');
			if(!$this->updateOccidMatchingDbpk()){
				$this->outputMsg('<li>ERROR updating occid after occurrence insert: '.$this->errorStr.'</li>');
			}
			//Update occid by linking catalognumbers
			//if(!$this->updateOccidMatchingCatalogNumber()){
				//$this->outputMsg('<li>ERROR updating occid (2nd step) after occurrence insert: '.$this->errorStr.'</li>');
			//}

			$this->transferExsiccati();
			$this->transferGeneticLinks();
			$this->transferPaleoData();
			//$this->transferMaterialSampleData();

			//Setup and add datasets and link datasets to current user
		}
		$this->setDeterminations();
		$this->setOtherCatalogNumbers();
	}

	private function versionInternalEdits(){
		if($this->versionDataEdits){
			if($this->collMetadataArr['managementtype'] == 'Live Data' && $this->uploadType != $this->RESTOREBACKUP){
				$sqlFrag = '';
				$excludedFieldArr = array('dateentered','observeruid');
				foreach($this->targetFieldArr as $field){
					if(!in_array($field, $excludedFieldArr)) $sqlFrag .= ',u.'.$field.',o.'.$field.' as old_'.$field;
				}
				$sql = 'SELECT o.occid'.$sqlFrag.' FROM omoccurrences o INNER JOIN uploadspectemp u ON o.occid = u.occid WHERE o.collid IN('.$this->collId.') AND u.collid IN('.$this->collId.')';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_assoc()){
					foreach($this->targetFieldArr as $field){
						if(in_array($field, $excludedFieldArr)) continue;
						if($r[$field] != $r['old_'.$field]){
							if($this->uploadType == $this->SKELETAL && $r['old_'.$field]) continue;
							$this->insertOccurEdit($r['occid'], $field, $r[$field], $r['old_'.$field]);
						}
					}
				}
				$rs->free();
			}
		}
	}

	private function insertOccurEdit($occid, $fieldName, $fieldValueNew, $fieldValueOld){
		if($fieldValueNew == NULL) $fieldValueNew = '';
		if($fieldValueOld == NULL) $fieldValueOld = '';
		$symbUid = $GLOBALS['SYMB_UID'];
		$appliedStatus = 1;
		$sql = 'INSERT INTO omoccuredits(occid, fieldName, fieldValueNew, fieldValueOld, appliedStatus, uid) VALUES(?,?,?,?,?,?)';
		if($stmt = $this->conn->prepare($sql)) {
			$stmt->bind_param('isssii', $occid, $fieldName, $fieldValueNew, $fieldValueOld, $appliedStatus, $symbUid);
			if(!$stmt->execute()){
				$this->errorStr = 'ERROR inserting Occurrence Edit: '.$stmt->error;
				echo $this->errorStr.'<br/>';
				return false;
			}
			$stmt->close();
		}
		else $this->errorStr = mysqli_error($this->conn);
	}

	private function versionExternalEdits(){
		$nfnFieldArr = $this->getOccurrenceFieldArr($this->symbFields);
		$sqlFrag = '';
		foreach($nfnFieldArr as $field){
			$sqlFrag .= ',u.'.$field.',o.'.$field.' as old_'.$field;
		}
		$sql = 'SELECT o.occid'.$sqlFrag.' FROM omoccurrences o INNER JOIN uploadspectemp u ON o.occid = u.occid WHERE o.collid IN('.$this->collId.') AND u.collid IN('.$this->collId.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$editArr = array();
			foreach($nfnFieldArr as $field){
				if($r[$field] && $r['old_'.$field] != $r[$field]){
					if($r['old_'.$field] && $field != 'processingstatus'){
						$editArr[0]['old'][$field] = $r['old_'.$field];
						$editArr[0]['new'][$field] = $r[$field];
					}
					else{
						$editArr[1]['old'][$field] = $r['old_'.$field];
						$editArr[1]['new'][$field] = $r[$field];
					}
				}
			}
			//Load into revisions table
			foreach($editArr as $appliedStatus => $eArr){
				$sql = 'INSERT INTO omoccurrevisions(occid, oldValues, newValues, externalSource, reviewStatus, appliedStatus) '.
					'VALUES('.$r['occid'].',"'.$this->cleanInStr(json_encode($eArr['old'])).'","'.$this->cleanInStr(json_encode($eArr['new'])).'","Notes from Nature Expedition",1,'.$appliedStatus.')';
				if(!$this->conn->query($sql)){
					$this->outputMsg('<li style="margin-left:10px;">ERROR adding edit revision ('.$this->conn->error.')</li>');
				}
			}
		}
		$rs->free();
	}

	private function getTransferFieldArr(){
		//Get uploadspectemp supported fields
		$uploadArr = array();
		$sql1 = 'SHOW COLUMNS FROM uploadspectemp';
		$rs1 = $this->conn->query($sql1);
		while($r1 = $rs1->fetch_object()){
			$uploadArr[strtolower($r1->Field)] = 0;
		}
		$rs1->free();
		//Get omoccurrences supported fields
		$specArr = array();
		$sql2 = 'SHOW COLUMNS FROM omoccurrences';
		$rs2 = $this->conn->query($sql2);
		while($r2 = $rs2->fetch_object()){
			$specArr[strtolower($r2->Field)] = 0;
		}
		$rs2->free();
		//Get union of both tables
		$fieldArr = array_intersect_assoc($uploadArr,$specArr);
		unset($fieldArr['occid']);
		unset($fieldArr['collid']);
		if($this->uploadType != $this->RESTOREBACKUP){
			unset($fieldArr['dbpk']);
			unset($fieldArr['observeruid']);
			unset($fieldArr['dateentered']);
			unset($fieldArr['initialtimestamp']);
		}
		return array_keys($fieldArr);
	}

	private function transferExsiccati(){
		$this->outputMsg('<li>Loading Exsiccati numbers...</li>');
		//Add any new exsiccati numbers
		$sqlNum = 'INSERT INTO omexsiccatinumbers(ometid, exsnumber) '.
			'SELECT DISTINCT u.exsiccatiIdentifier, u.exsiccatinumber '.
			'FROM uploadspectemp u LEFT JOIN omexsiccatinumbers e ON u.exsiccatiIdentifier = e.ometid AND u.exsiccatinumber = e.exsnumber '.
			'WHERE (u.collid IN('.$this->collId.')) AND (u.occid IS NOT NULL) '.
			'AND (u.exsiccatiIdentifier IS NOT NULL) AND (u.exsiccatinumber IS NOT NULL) AND (e.exsnumber IS NULL)';
		if(!$this->conn->query($sqlNum)){
			$this->outputMsg('<li>ERROR adding new exsiccati numbers: '.$this->conn->error.'</li>');
		}
		//Load exsiccati
		$sqlLink = 'INSERT IGNORE INTO omexsiccatiocclink(omenid,occid) '.
			'SELECT e.omenid, u.occid '.
			'FROM uploadspectemp u INNER JOIN omexsiccatinumbers e ON u.exsiccatiIdentifier = e.ometid AND u.exsiccatinumber = e.exsnumber '.
			'WHERE (u.collid IN('.$this->collId.')) AND (e.omenid IS NOT NULL) AND (u.occid IS NOT NULL)';
		if(!$this->conn->query($sqlLink)){
			$this->outputMsg('<li>ERROR adding new exsiccati numbers: '.$this->conn->error.'</li>',1);
		}
	}

	private function transferGeneticLinks(){
		$this->outputMsg('<li>Linking genetic records (aka associatedSequences)...</li>');
		$sql = 'SELECT occid, associatedSequences FROM uploadspectemp WHERE collid IN('.$this->collId.') AND occid IS NOT NULL AND associatedSequences IS NOT NULL ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$seqArr = explode(';', str_replace(array(',','|',''),';',$r->associatedSequences));
			foreach($seqArr as $str){
				//$urlPattern = '/((http|https)\:\/\/)?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.([a-zA-Z0-9\&\.\/\?\:@\-_=#])*/';
				if(preg_match('$((http|https)\://[^\s;,]+)$', $str, $match)){
					$url = $match[1];
					$noteStr = trim(str_replace($url, '', $str),',;| ');
					$resNameStr = 'undefined';
					$idenStr = '';
					if(preg_match('$ncbi\.nlm\.nih\.gov.+/([A-Z]+\d+)$', $str, $matchNCBI)){
						//https://www.ncbi.nlm.nih.gov/nuccore/AY138416
						$resNameStr = 'GenBank';
						$idenStr = $matchNCBI[1];
					}
					elseif(preg_match('/boldsystems\.org.*processid=([A-Z\d-]+)/', $str, $matchBOLD)){
						//http://www.boldsystems.org/index.php/Public_RecordView?processid=BSAMQ088-09
						$resNameStr = 'BOLD Systems';
						$idenStr = $matchBOLD[1];
					}
					$seqSQL = 'INSERT INTO omoccurgenetic(occid, resourcename, identifier, resourceurl, notes) '.
						'VALUES('.$r->occid.',"'.$this->cleanInStr($resNameStr).'",'.($idenStr?'"'.$this->cleanInStr($idenStr).'"':'NULL').
						',"'.$url.'",'.($noteStr?'"'.$this->cleanInStr($noteStr).'"':'NULL').')';
					if(!$this->conn->query($seqSQL) && $this->conn->errno != '1062'){
						$this->outputMsg('<li>ERROR adding genetic resource: '.$this->conn->error.'</li>',1);
					}
				}
			}
		}
		$rs->free();
	}

	private function transferPaleoData(){
		if($this->paleoSupport){
			$this->outputMsg('<li>Linking Paleo data...</li>');
			$sql = 'SELECT occid, catalogNumber, paleoJSON FROM uploadspectemp WHERE (occid IS NOT NULL) AND (paleoJSON IS NOT NULL) AND (collid = '.$this->collId.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				try{
					$paleoArr = json_decode($r->paleoJSON,true);
					//Deal with DwC terms
					$eonTerm = '';
					if(isset($paleoArr['earliesteonorlowesteonothem']) && $paleoArr['earliesteonorlowesteonothem']) $eonTerm = $paleoArr['earliesteonorlowesteonothem'];
					if(isset($paleoArr['latesteonorhighesteonothem']) && $paleoArr['latesteonorhighesteonothem'] != $eonTerm) $eonTerm .= ' - '.$paleoArr['latesteonorhighesteonothem'];
					if($eonTerm && !isset($paleoArr['eon'])) $paleoArr['eon'] = $eonTerm;
					unset($paleoArr['earliesteonorlowesteonothem']);
					unset($paleoArr['latesteonorhighesteonothem']);

					$eraTerm = '';
					if(isset($paleoArr['earliesteraorlowesterathem']) && $paleoArr['earliesteraorlowesterathem']) $eraTerm = $paleoArr['earliesteraorlowesterathem'];
					if(isset($paleoArr['latesteraorhighesterathem']) && $paleoArr['latesteraorhighesterathem'] != $eraTerm) $eraTerm .= ' - '.$paleoArr['latesteraorhighesterathem'];
					if($eraTerm && !isset($paleoArr['era'])) $paleoArr['era'] = $eraTerm;
					unset($paleoArr['earliesteraorlowesterathem']);
					unset($paleoArr['latesteraorhighesterathem']);

					$periodTerm = '';
					if(isset($paleoArr['earliestperiodorlowestsystem']) && $paleoArr['earliestperiodorlowestsystem']) $periodTerm = $paleoArr['earliestperiodorlowestsystem'];
					if(isset($paleoArr['latestperiodorhighestsystem']) && $paleoArr['latestperiodorhighestsystem'] != $periodTerm) $periodTerm .= ' - '.$paleoArr['latestperiodorhighestsystem'];
					if($periodTerm && !isset($paleoArr['period'])) $paleoArr['period'] = $periodTerm;
					unset($paleoArr['earliestperiodorlowestsystem']);
					unset($paleoArr['latestperiodorhighestsystem']);

					$epochTerm = '';
					if(isset($paleoArr['earliestepochorlowestseries']) && $paleoArr['earliestepochorlowestseries']) $epochTerm = $paleoArr['earliestepochorlowestseries'];
					if(isset($paleoArr['latestepochorhighestseries']) && $paleoArr['latestepochorhighestseries'] != $epochTerm) $epochTerm .= ' - '.$paleoArr['latestepochorhighestseries'];
					if($epochTerm && !isset($paleoArr['epoch'])) $paleoArr['epoch'] = $epochTerm;
					unset($paleoArr['earliestepochorlowestseries']);
					unset($paleoArr['latestepochorhighestseries']);

					$stageTerm = '';
					if(isset($paleoArr['earliestageorloweststage']) && $paleoArr['earliestageorloweststage']) $stageTerm = $paleoArr['earliestageorloweststage'];
					if(isset($paleoArr['latestageorhigheststage']) && $paleoArr['latestageorhigheststage'] != $stageTerm) $stageTerm .= ' - '.$paleoArr['latestageorhigheststage'];
					if($stageTerm && !isset($paleoArr['stage'])) $paleoArr['stage'] = $stageTerm;
					unset($paleoArr['earliestageorloweststage']);
					unset($paleoArr['latestageorhigheststage']);

					$biostratigraphyTerm = '';
					if(isset($paleoArr['lowestbiostratigraphiczone']) && $paleoArr['lowestbiostratigraphiczone']) $biostratigraphyTerm = $paleoArr['lowestbiostratigraphiczone'];
					if(isset($paleoArr['highestbiostratigraphiczone']) && $paleoArr['highestbiostratigraphiczone'] != $biostratigraphyTerm) $biostratigraphyTerm .= ' - '.$paleoArr['highestbiostratigraphiczone'];
					if($biostratigraphyTerm && !isset($paleoArr['biostratigraphy'])) $paleoArr['biostratigraphy'] = $biostratigraphyTerm;
					unset($paleoArr['lowestbiostratigraphiczone']);
					unset($paleoArr['highestbiostratigraphiczone']);

					$insertSQL = '';
					$valueSQL = '';
					foreach($paleoArr as $k => $v){
						$insertSQL .= ','.$k;
						$valueSQL .= ',"'.$this->cleanInStr($v).'"';
					}
					$sql = 'REPLACE INTO omoccurpaleo(occid'.$insertSQL.') VALUES('.$r->occid.$valueSQL.')';
					if(!$this->conn->query($sql)){
						$this->outputMsg('<li>ERROR adding paleo resources: '.$this->conn->error.'</li>',1);
					}
				}
				catch(Exception $e){
					$this->outputMsg('<li>ERROR adding paleo record (occid: '.$r->occid.', catalogNumber: '.$r->catalogNumber.'): '.$e->getMessage().'</li>',1);
				}
			}
			$rs->free();
		}
	}

	// private function transferMaterialSampleData(){
	// 	if($this->materialSampleSupport){
	// 		$this->outputMsg('<li>Linking Material Sample data...</li>');
	// 		$sql = 'SELECT occid, catalogNumber, materialSampleJSON FROM uploadspectemp WHERE (occid IS NOT NULL) AND (materialSampleJSON IS NOT NULL) AND (collid = '.$this->collId.')';
	// 		$rs = $this->conn->query($sql);
	// 		while($r = $rs->fetch_object()){
	// 			try{
	// 				$matSampleArr = json_decode($r->materialSampleJSON,true);
	// 				$insertSQL = '';
	// 				$valueSQL = '';
	// 				foreach($matSampleArr as $k => $v){
	// 					$insertSQL .= ','.$k;
	// 					$valueSQL .= ',"'.$this->cleanInStr($v).'"';
	// 				}
	// 				$sql = 'REPLACE INTO ommaterialsample(occid'.$insertSQL.') VALUES('.$r->occid.$valueSQL.')';
	// 				if(!$this->conn->query($sql)){
	// 					$this->outputMsg('<li>ERROR adding Material Sample resources: '.$this->conn->error.'</li>',1);
	// 				}
	// 			}
	// 			catch(Exception $e){
	// 				$this->outputMsg('<li>ERROR adding Material Sample record (occid: '.$r->occid.', catalogNumber: '.$r->catalogNumber.'): '.$e->getMessage().'</li>',1);
	// 			}
	// 		}
	// 		$rs->free();
	// 	}
	// }
	private function setOtherCatalogNumbers(){
		if($this->uploadType == $this->FILEUPLOAD || $this->uploadType == $this->SKELETAL){
			$sql = 'INSERT IGNORE INTO omoccuridentifiers (occid, identifiername, identifiervalue, modifiedUid)
			SELECT o.occid, kv.key as identifiername, kv.value as identifiervalue, kv.uploadUid as modifiedUid
			FROM uploadkeyvaluetemp kv
			INNER JOIN uploadspectemp u on u.dbpk = kv.dbpk
			INNER JOIN omoccurrences o on o.occid = u.occid
			WHERE type = "omoccuridentifiers" AND kv.collid = ?';

			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $this->collId);
				$stmt->execute();
				if($stmt->error) $this->outputMsg('<li>ERROR adding other catalog numbers to identifiers: '.$stmt->error.'</li>');
				$stmt->close();
			}
		}
	}

	private function setDeterminations(){
		if($this->collId){
			if($this->uploadType == $this->FILEUPLOAD || $this->uploadType == $this->SKELETAL){
				//Reset existing current determinations to match fields in the omoccurrences table (e.g. import data changes, will equal current determinations)
				$sql = 'UPDATE IGNORE uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid
					INNER JOIN omoccurdeterminations d ON o.occid = d.occid
					SET d.sciname = IFNULL(o.sciname, "undetermined"), d.identifiedBy = IFNULL(o.identifiedBy, "unknown"), d.dateIdentified = IFNULL(o.dateIdentified, "s.d."),
					d.family = o.family, d.scientificNameAuthorship = o.scientificNameAuthorship, d.tidInterpreted = o.tidInterpreted, d.identificationQualifier = o.identificationQualifier,
					d.identificationReferences = o.identificationReferences, d.identificationRemarks = o.identificationRemarks, d.taxonRemarks = o.taxonRemarks
					WHERE o.collid = ? AND d.isCurrent = 1';
				if($stmt = $this->conn->prepare($sql)){
					$stmt->bind_param('i', $this->collId);
					$stmt->execute();
					if($stmt->error) $this->outputMsg('<li>ERROR resetting determinations: '.$stmt->error.'</li>');
					$stmt->close();
				}

				//Add new determinations to omoccurdetermination table
				$sql = 'INSERT IGNORE INTO omoccurdeterminations(occid, sciname, identifiedBy, dateIdentified, family, scientificNameAuthorship, tidInterpreted, identificationQualifier,
					identificationReferences, identificationRemarks, taxonRemarks, isCurrent)
					SELECT o.occid, IFNULL(o.sciname, "undetermined") AS sciname, IFNULL(o.identifiedBy, "unknown") AS identifiedBy, IFNULL(o.dateIdentified, "s.d.") AS dateIdentified,
					o.family, o.scientificNameAuthorship, o.tidInterpreted, o.identificationQualifier, o.identificationReferences, o.identificationRemarks, o.taxonRemarks, 1 AS isCurrent
					FROM uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid
					LEFT JOIN omoccurdeterminations d ON o.occid = d.occid
					WHERE o.collid = ? AND d.occid IS NULL;';
				if($stmt = $this->conn->prepare($sql)){
					$stmt->bind_param('i', $this->collId);
					$stmt->execute();
					if($stmt->error) $this->outputMsg('<li>ERROR adding determinations: '.$stmt->error.'</li>');
					$stmt->close();
				}
			}
		}
	}

	protected function transferIdentificationHistory(){
		$identificationsExist = false;
		$sql = 'SELECT count(*) AS cnt FROM uploaddetermtemp WHERE (collid IN('.$this->collId.'))';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			if($r->cnt) $identificationsExist = true;
		}
		$rs->free();

		if($identificationsExist){
			$this->outputMsg('<li>Transferring Determination History...</li>');

			//Update occid for determinations of occurrence records already in portal
			$sql = 'UPDATE uploaddetermtemp ud INNER JOIN uploadspectemp u ON ud.collid = u.collid AND ud.dbpk = u.dbpk '.
				'SET ud.occid = u.occid '.
				'WHERE (ud.occid IS NULL) AND (u.occid IS NOT NULL) AND (ud.collid IN('.$this->collId.'))';
			if(!$this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:20px;">WARNING updating occids within uploaddetermtemp: '.$this->conn->error.'</li> ');
			}

			//Update determinations where the sourceIdentifiers match
			$sql = 'UPDATE IGNORE omoccurdeterminations d INNER JOIN uploaddetermtemp u ON d.occid = u.occid '.
				'SET d.sciname = u.sciname, d.scientificNameAuthorship = u.scientificNameAuthorship, d.identifiedBy = u.identifiedBy, d.dateIdentified = u.dateIdentified, '.
				'd.identificationQualifier = u.identificationQualifier, d.iscurrent = u.iscurrent, d.identificationReferences = u.identificationReferences, '.
				'd.identificationRemarks = u.identificationRemarks, d.sourceIdentifier = u.sourceIdentifier '.
				'WHERE (u.collid IN('.$this->collId.')) AND (d.sourceIdentifier = u.sourceIdentifier)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:20px;">ERROR updating determinations with matching sourceIdentifiers: '.$this->conn->error.'</li> ');
			}
			$sql = 'DELETE u.* FROM omoccurdeterminations d INNER JOIN uploaddetermtemp u ON d.occid = u.occid
				WHERE (u.collid IN('.$this->collId.')) AND (d.sourceIdentifier = u.sourceIdentifier) ';
			if(!$this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:20px;">ERROR removing determinations with matching sourceIdentifiers: '.$this->conn->error.'</li> ');
			}

			//Delete duplicate determinations (likely previously loaded)
			$sqlDel = 'DELETE IGNORE u.* '.
				'FROM uploaddetermtemp u INNER JOIN omoccurdeterminations d ON u.occid = d.occid '.
				'WHERE (u.collid IN('.$this->collId.')) AND (d.sciname = u.sciname) AND (d.identifiedBy = u.identifiedBy) AND (d.dateIdentified = u.dateIdentified)';
			$this->conn->query($sqlDel);

			//Load identification history records
			$sql = 'INSERT IGNORE INTO omoccurdeterminations (occid, sciname, scientificNameAuthorship, identifiedBy, dateIdentified, '.
				'identificationQualifier, iscurrent, identificationReferences, identificationRemarks, sourceIdentifier) '.
				'SELECT u.occid, u.sciname, u.scientificNameAuthorship, u.identifiedBy, u.dateIdentified, '.
				'u.identificationQualifier, u.iscurrent, u.identificationReferences, u.identificationRemarks, sourceIdentifier '.
				'FROM uploaddetermtemp u '.
				'WHERE u.occid IS NOT NULL AND (u.collid IN('.$this->collId.'))';
			if($this->conn->query($sql)){
				//Delete all determinations
				$sqlDel = 'DELETE FROM uploaddetermtemp WHERE (collid IN('.$this->collId.'))';
				$this->conn->query($sqlDel);
			}
			else{
				$this->outputMsg('<li>FAILED! ERROR: '.$this->conn->error.'</li> ');
			}
		}
	}

	private function prepareAssociatedMedia(){
		//parse, check, and transfer all good URLs
		$sql = 'SELECT associatedmedia, tidinterpreted, occid, dbpk FROM uploadspectemp WHERE (associatedmedia IS NOT NULL) AND (collid IN('.$this->collId.'))';
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$this->outputMsg('<li>Loading associatedMedia into image staging table...</li>');
			while($r = $rs->fetch_object()){
				$mediaArr = explode(',',trim(str_replace(array(';','|'),',',$r->associatedmedia),', '));
				foreach($mediaArr as $mediaUrl){
					$mediaUrl = trim($mediaUrl);
					if(strpos($mediaUrl,'"')) continue;
					$this->loadMediaRecord(array('occid'=>$r->occid,'collid'=>$this->collId,'dbpk'=>$r->dbpk,'tid'=>($r->tidinterpreted?$r->tidinterpreted:''),'originalurl'=>$mediaUrl));
				}
			}
		}
		$rs->free();
	}

	private function cleanImages(){
		$sql = 'SELECT collid FROM uploadimagetemp WHERE collid = '.$this->collId.' LIMIT 1';
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$this->outputMsg('<li>Removing previously linked images and bad image paths... </li>');
			//Remove images that are not a ULR or local path
			$sql = 'DELETE FROM uploadimagetemp WHERE (originalurl NOT LIKE "http%" AND originalurl NOT LIKE "/%") AND (collid = '.$this->collId.')';
			if(!$this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:20px;">WARNING removing non-jpgs from uploadimagetemp: '.$this->conn->error.'</li> ');
			}
			//Remove images that are obviously not JPGs
			$sql = 'DELETE FROM uploadimagetemp WHERE (originalurl LIKE "%.dng" OR originalurl LIKE "%.tif") AND (collid = '.$this->collId.')';
			if(!$this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:20px;">WARNING removing non-jpgs from uploadimagetemp: '.$this->conn->error.'</li> ');
			}
			//Update occid for images of occurrence records already in portal
			$sql = 'UPDATE uploadimagetemp ui INNER JOIN uploadspectemp u ON ui.collid = u.collid AND ui.dbpk = u.dbpk '.
				'SET ui.occid = u.occid '.
				'WHERE (ui.occid IS NULL) AND (u.occid IS NOT NULL) AND (ui.collid = '.$this->collId.')';
			if(!$this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:20px;">WARNING updating occids within uploadimagetemp: '.$this->conn->error.'</li> ');
			}
			//Remove and skip previously loaded images where urls match exactly
			$sql = 'DELETE u.* FROM uploadimagetemp u INNER JOIN media m ON u.occid = m.occid WHERE (u.collid = '.$this->collId.') AND (u.originalurl = m.originalurl)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:20px;">ERROR deleting uploadimagetemp records with matching urls: '.$this->conn->error.'</li> ');
			}
			$sql = 'DELETE u.* FROM uploadimagetemp u INNER JOIN media m ON u.occid = m.occid WHERE (u.collid = '.$this->collId.') AND (u.originalurl IS NULL) AND (m.originalurl IS NULL) AND (u.url = m.url)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:20px;">ERROR deleting image records with matching originalurls: '.$this->conn->error.'</li> ');
			}
		}
		$rs->free();
	}

	protected function transferImages(){
		$sql = 'SELECT count(*) AS cnt FROM uploadimagetemp WHERE (collid = '.$this->collId.')';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			if($r->cnt){
				$this->outputMsg('<li>Transferring images...</li>');
				//Update occid for images of new records
				$sql = 'UPDATE uploadimagetemp ui INNER JOIN uploadspectemp u ON ui.collid = u.collid AND ui.dbpk =u.dbpk '.
					'SET ui.occid = u.occid '.
					'WHERE (ui.occid IS NULL) AND (u.occid IS NOT NULL) AND (ui.collid = '.$this->collId.')';
				//echo $sql.'<br/>';
				if(!$this->conn->query($sql)){
					$this->outputMsg('<li style="margin-left:20px;">WARNING updating occids within uploadimagetemp: '.$this->conn->error.'</li> ');
				}

				//Set image transfer count
				$this->setImageTransferCount();

				//Get shared field names for transferring between image tables
				$imageFieldArr = array();
				$rs1 = $this->conn->query('SHOW COLUMNS FROM uploadimagetemp');
				while($r1 = $rs1->fetch_object()){
					$imageFieldArr[strtolower($r1->Field)] = 0;
				}

				$rs1->free();
				$rs2 = $this->conn->query('SHOW COLUMNS FROM media ');
				while($r2 = $rs2->fetch_object()){
					$fieldName = strtolower($r2->Field);
					if(array_key_exists($fieldName, $imageFieldArr)) $imageFieldArr[$fieldName] = 1;
				}
				$rs2->free();
				foreach($imageFieldArr as $k => $v){
					if(!$v) unset($imageFieldArr[$k]);
				}
				unset($imageFieldArr['sortsequence']);
				unset($imageFieldArr['initialtimestamp']);

				//Remap URLs and remove from import images where source identifiers match, but original URLs differ (e.g. host server is changed)
				$sql = 'UPDATE uploadimagetemp u INNER JOIN media m ON u.occid = m.occid '.
					'SET m.originalurl = u.originalurl, m.url = IFNULL(u.url,if(SUBSTRING(m.url,1,1)="/",m.url,NULL)), m.thumbnailurl = IFNULL(u.thumbnailurl,if(SUBSTRING(m.thumbnailurl,1,1)="/",m.thumbnailurl,NULL)) '.
					'WHERE (u.collid = '.$this->collId.') AND (u.sourceIdentifier = m.sourceIdentifier) ';
				if(!$this->conn->query($sql)){
					$this->outputMsg('<li style="margin-left:20px;">ERROR remapping URL with matching sourceIdentifier: '.$this->conn->error.'</li> ');
				}
				$sql = 'DELETE u.* FROM uploadimagetemp u INNER JOIN media m ON u.occid = m.occid WHERE (u.collid = '.$this->collId.') AND (u.sourceIdentifier = m.sourceIdentifier)';
				if(!$this->conn->query($sql)){
					$this->outputMsg('<li style="margin-left:20px;">ERROR deleting incoming image records that have matching sourceIdentifier: '.$this->conn->error.'</li> ');
				}

				//Load images
				$sql = 'INSERT INTO media ('.implode(',',array_keys($imageFieldArr)).') '.
					'SELECT ' . implode(',',array_keys($imageFieldArr)) . ' FROM uploadimagetemp WHERE (occid IS NOT NULL) AND (collid = '.$this->collId.')';
				if($this->conn->query($sql)){
					$this->outputMsg('<li style="margin-left:10px;">'.$this->imageTransferCount.' images transferred</li> ');
				}
				else{
					$this->outputMsg('<li>FAILED! ERROR: '.$this->conn->error.'</li> ');
				}
			}
		}
		$rs->free();
	}

	protected function transferHostAssociations(){
		$sql = 'SELECT count(*) AS cnt FROM uploadspectemp WHERE collid = '.$this->collId.' AND `host` IS NOT NULL';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			if($r->cnt){
				$this->outputMsg('<li>Transferring host associations...</li>');
				//Update existing host association records
				$sql = 'UPDATE uploadspectemp s LEFT JOIN omoccurassociations a ON s.occid = a.occid '.
					'SET a.verbatimsciname = s.`host` '.
					'WHERE a.occid IS NOT NULL AND s.`host` IS NOT NULL AND a.relationship = "host" ';
				if(!$this->conn->query($sql)){
					$this->outputMsg('<li style="margin-left:20px;">WARNING updating host associations within omoccurassociations: '.$this->conn->error.'</li> ');
				}

				//Load images
				$sql = 'INSERT INTO omoccurassociations(occid,relationship,verbatimsciname) '.
					'SELECT s.occid, "host", s.`host` FROM uploadspectemp s LEFT JOIN omoccurassociations a ON s.occid = a.occid '.
					'WHERE (a.occid IS NULL) AND (s.`host` IS NOT NULL) ';
				if($this->conn->query($sql)){
					$this->outputMsg('<li style="margin-left:10px;">Host associations updated</li> ');
				}
				else{
					$this->outputMsg('<li>FAILED! ERROR: '.$this->conn->error.'</li> ');
				}
			}
		}
		$rs->free();
	}

	// This function looks for records being imported with JSON in the associatedOccurrences field,
	// parses it, and attempts to add or update any associated occurrences in the omoccurassociations table.
	protected function transferAssociatedOccurrences() {

		// Select records that appear to have symbiotaAssociations JSON:
		$sql = 'SELECT occid, associatedOccurrences FROM `uploadspectemp` WHERE collid = ' . $this->collId .
			' AND `associatedOccurrences` LIKE \'%{"type":"symbiotaAssociations"%\'';

		// Run the query
		$rs = $this->conn->query($sql);

		// If any records appear to have associatedOccurrences JSON, transfer associated occurrences
		if ($rs->num_rows) {

			$this->outputMsg('<li>Transferring associated occurrences for ' . $rs->num_rows . ' records...</li>');

			// Get current user ID
			$symbUid = $GLOBALS['SYMB_UID'];

			// Counter for the number of associations being imported
			$assocCount = 0;

			// Handle each record with associated occurrences JSON
			while ($r = $rs->fetch_object()) {

				// Check if the contents of the field is proper JSON
				if ($assocOccArr = json_decode($r->associatedOccurrences, true)) {
					// Proper JSON, parsed successfully

					// Find the symbiotaAssociations and verbatimText arrays in the JSON and save them.
					foreach ($assocOccArr as $index) {

						// Check if there's an associations array present with all the keys. If so, save it
						if (array_key_exists('type', $index) && $index['type'] == 'symbiotaAssociations' &&
							array_key_exists('associations', $index) && array_key_exists('version', $index)) $assocOccur = $index;

						// Check if verbatimText is present and save it.
						// If so, we'll use it to replace the associatedOccurrences JSON, sanitizing it first for SQL
						if (array_key_exists('type', $index) && $index['type'] == 'verbatimText' &&
							array_key_exists('verbatimText', $index)) $verbatimText = $this->cleanInStr($index['verbatimText']);
					}

					// Check to make sure we found an associated occurrence array
					if (isset($assocOccur)) {

						// Check symbiotaAssociations version
						if ($assocOccur['version'] != OccurrenceUtil::$assocOccurVersion) {

							// JSON symbiotaAssociations versions don't match
							// TODO: What should we do here?
						}

					} else {

						// No associated occurrence array found. It must be missing the associations key.
						// Skip the record and output an error.
						$this->outputMsg('<li>Transferring associations failed for occid: ' . $r->occid . '. ERROR: malformed associations JSON</li> ');
						continue;
					}

					// If no verbatimText was found, just set it to an empty string.
					if (!isset($verbatimText)) $verbatimText = '';

				} else {
					// JSON didn't parse, even though it appears to be there. Skip the record and output an error.
					$this->outputMsg('<li>Transferring associations failed for occid: ' . $r->occid . '. ERROR: malformed JSON</li> ');
					continue;
				}

				// Insert each associated occurrence contained in the associatedOccurrences JSON
				foreach ($assocOccur['associations'] as $assoc) {

					// Increment associations counter
					$assocCount++;

					// Sanitize the variables for SQL
					$assoc = array_map(array($this, 'cleanInStr'), $assoc);

					// Get the type, and remove it from the array
					// TODO: Anything needed to be done with the type here?
					$type = $assoc['type'];
					unset($assoc['type']);

					// Association is marked as an internal occurrence, but includes an identifier (guid), and resourceUrl
					// Need to determine if it is still an internal occurrence, and if so, get its guid
					// TODO: Should there be a check to make sure that this is an internal occurrence,
					//   regardless of whether identifier/resourceURL are present?
					if($type == 'internalOccurrence' && array_key_exists('identifier', $assoc) && array_key_exists('resourceUrl', $assoc)) {

						// Construct and run query to get occid from guid
						// Check for an occurrenceID first, then a guid.
						// Finally, check for an occurrenceID or a dbpk that matches
						$sql = "SELECT occid FROM omoccurrences WHERE occurrenceID = '" . $assoc['identifier'] .
							"' UNION " .
							"SELECT occid FROM guidoccurrences WHERE guid = '" . $assoc['identifier'] .
							"' UNION " .
							"SELECT occid FROM uploadspectemp WHERE (occurrenceID = '" . $assoc['identifier'] .
							"' OR dbpk = '" . $assoc['identifier'] . "' OR dbpk = " . $assoc['occidAssociate'] . ")";

						//echo $sql;
						$rs1 = $this->conn->query($sql);

						// Check to see if we got an occid back. If so, update to use that
						if ($r1 = $rs1->fetch_object()) {

							// Update the occidAssociate field to use the new occid
							$assoc['occidAssociate'] = $r1->occid;

							// Remove the externalOccurrence fields, no longer needed
							unset($assoc['identifier'], $assoc['resourceUrl']);

						} else {

							// GUID record was not found, convert to external occurrence
							$type = 'externalOccurrence';

							// Remove the internalOccurrence fields
							unset($assoc['occidAssociate']);
						}
					}

					// First, try to update the association record if it already exists
					// If if exists, it should have identical occid/occidAssociate, relationship, and one of:
					//   occidAssociate/occid, verbatimSciname, identifier, or resourcUrl

					// Set up a where clause to test whether or not the association already exists
					// Check whether occidAssociate is set. If so, then the relationships apply to both specimens with one entry
					if (array_key_exists('occidAssociate', $assoc)) {

						// Check for an identical internal association, and also for its inverse relationship
						$sqlWhere = " WHERE ((occid = " . $r->occid . " AND occidAssociate = " . $assoc['occidAssociate'] .
							" AND relationship = '" . $assoc['relationship'] . "') OR (occid = " . $assoc['occidAssociate'] .
							" AND occidAssociate = " . $r->occid . " AND relationship = '" .
							$this->getInverseRelationship($assoc['relationship']) . "'))";
					} else {

						// Check for verbatimSciname if set, otherwise check for identifier if set, and finally for resourceUrl if set
						$sqlWhere = " WHERE occid = " . $r->occid . " AND relationship = '" . $assoc['relationship'] . "'" .
							(array_key_exists('verbatimSciname', $assoc) ? " AND verbatimSciname = '" . $assoc['verbatimSciname'] . "'" :
							(array_key_exists('identifier', $assoc) ? " AND identifier = '" . $assoc['identifier'] . "'" :
							(array_key_exists('resourceUrl', $assoc) ? " AND resourceUrl = '" . $assoc['resourceUrl'] . "'" : '')));
					}

					// Check for matching rows: the association already exists
					$sql = 'SELECT subType, identifier, basisOfRecord, resourceUrl, verbatimSciname, locationOnHost, notes ' .
						'FROM omoccurassociations' . $sqlWhere;
					$rs1 = $this->conn->query($sql);

					// If there are matching rows, see if data has changed and we need to update, rather than insert a new association
					if ($existingAssoc = $rs1->fetch_assoc()) {

						// Filter out any empty fields from the existing data
						$existingAssoc = array_filter($existingAssoc);

						// Make a new array for updating, and remove the occidAssociate key and relationship, which shouldn't need to change
						$updateAssoc = $assoc;
						unset($updateAssoc['occidAssociate'], $updateAssoc['relationship']);

						// If there are keys or data that has changed from the existing data, then update
						// If nothing has changed, just move on, nothing to insert or update in the omoccurassociations table
						if (array_diff_assoc($updateAssoc, $existingAssoc)) {

							// Construct update query
							$sql = 'UPDATE omoccurassociations SET ';

							// Add all the fields that are present in the JSON to the update query, except occidAssociate
							$sql .= implode(', ', array_map(function($key, $value) {
								return "{$key} = '{$value}'";
							}, array_keys($updateAssoc), $updateAssoc));

							// Add where clause to update query.
							$sql .= $sqlWhere;

							//echo $sql . '<br/>';

							// Run update query, reporting any error
							if (!$this->conn->query($sql)) {
								$this->outputMsg('<li>Updating association failed for occid: ' . $r->occid .
									'. ERROR: '.$this->conn->error.'</li> ');
							}
						}

					} else {

						// Build insert query to insert a new association
						$sql = 'INSERT INTO omoccurassociations (occid, createdUid, '. implode(', ', array_keys($assoc)) . ') ' .
							'VALUES('.$r->occid . ', ' . $symbUid . ", " .
							implode(', ', array_map(function($value) {
								return("'{$value}'");
							}, $assoc)) . ');';

						//echo $sql . '<br/>';

						// Run insert query, reporting any error
						if (!$this->conn->query($sql)) {
							$this->outputMsg('<li>Transferring association failed for occid: ' . $r->occid . '. ERROR: '.$this->conn->error.'</li> ');
						}
					}
					$rs1->free();
				}

				// Build query to update the associatedOccurrences field for the occurrence record
				// If there was text there before the JSON, this is replaced, otherwise the field is set to NULL.
				$sql = "UPDATE omoccurrences SET associatedOccurrences = '". ($verbatimText ? $verbatimText : 'NULL') .
					"' WHERE occid = " . $r->occid;

				// Run update query, reporting any error
				if (!$this->conn->query($sql)) {
					$this->outputMsg('<li>Restoring associatedOccurrences text failed for occid: ' . $r->occid . '. ERROR: '.$this->conn->error.'</li> ');
				}

				// Delete these variables if they exist, so we can check if they got set with the next ocurrence record
				unset($assocOccur, $verbatimText);
			}
			$this->outputMsg('<li style="margin-left:10px;">' . $assocCount . ' associated occurrences transferred or updated</li> ');
		}

		// Free the database queries
		$rs->free();
	}

	protected function finalCleanup(){
		$this->outputMsg('<li>Record transfer complete!</li>');
		$this->outputMsg('<li>Cleaning house...</li>');

		//Update uploaddate
		$sql = 'UPDATE omcollectionstats SET uploaddate = CURDATE() WHERE collid IN('.$this->collId.')';
		$this->conn->query($sql);

		//Remove records from occurrence temp table (uploadspectemp)
		$sql = 'DELETE FROM uploadspectemp WHERE (collid IN('.$this->collId.')) OR (initialtimestamp < DATE_SUB(CURDATE(),INTERVAL 3 DAY))';
		$this->conn->query($sql);
		//Optimize table to reset indexes
		$this->conn->query('OPTIMIZE TABLE uploadspectemp');

		//Remove records from determination temp table (uploaddetermtemp)
		$sql = 'DELETE FROM uploaddetermtemp WHERE (collid IN('.$this->collId.')) OR (initialtimestamp < DATE_SUB(CURDATE(),INTERVAL 3 DAY))';
		$this->conn->query($sql);
		//Optimize table to reset indexes
		$this->conn->query('OPTIMIZE TABLE uploaddetermtemp');

		//Remove records from image temp table (uploadimagetemp)
		$sql = 'DELETE FROM uploadimagetemp WHERE (collid IN('.$this->collId.')) OR (initialtimestamp < DATE_SUB(CURDATE(),INTERVAL 3 DAY))';
		$this->conn->query($sql);
		//Optimize table to reset indexes
		$this->conn->query('OPTIMIZE TABLE uploadimagetemp');

		//Remove temporary dbpk values
		if($this->collMetadataArr['managementtype'] == 'Live Data' || $this->uploadType == $this->SKELETAL){
			$sql = 'UPDATE omoccurrences SET dbpk = NULL WHERE (collid IN('.$this->collId.')) AND (dbpk LIKE "SYMBDBPK-%")';
			$this->conn->query($sql);
		}

		//Do some more cleaning of the data after it has been indexed in the omoccurrences table
		$occurMain = new OccurrenceMaintenance($this->conn);
		$occurMain->setCollidStr($this->collId);

		if(!$occurMain->generalOccurrenceCleaning()){
			$errorArr = $occurMain->getErrorArr();
			foreach($errorArr as $errorStr){
				$this->outputMsg('<li style="margin-left:20px;">'.$errorStr.'</li>',1);
			}
		}

		$this->outputMsg('<li style="margin-left:10px;">Protecting sensitive species...</li>');
		$protectCnt = $occurMain->protectRareSpecies();

		$this->outputMsg('<li style="margin-left:10px;">Updating statistics...</li>');
		if(!$occurMain->updateCollectionStatsFull()){
			$errorArr = $occurMain->getErrorArr();
			foreach($errorArr as $errorStr){
				$this->outputMsg('<li style="margin-left:20px;">'.$errorStr.'</li>',1);
			}
		}

		$this->outputMsg('<li style="margin-left:10px;">Populating recordID UUIDs for all records... </li>');
		$guidManager = new GuidManager();
		$guidManager->setSilent(1);
		$guidManager->populateGuids($this->collId);

		if($this->imageTransferCount){
			$this->outputMsg('<li style="margin-left:10px;color:orange">WARNING: Image thumbnails may need to be created using the <a href="../../imagelib/admin/thumbnailbuilder.php?collid=' . htmlspecialchars($this->collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">Images Thumbnail Builder</a></li>');
		}
	}

	protected function loadRecord($recMap){
		//Only import record if at least one of the minimal fields have data
		$recMap = OccurrenceUtil::occurrenceArrayCleaning($recMap);

		//Prime the targetFieldArr
		if(!$this->targetFieldArr) $this->targetFieldArr = $this->getOccurrenceFieldArr(array_keys($recMap));
		$loadRecord = false;
		if($this->uploadType == $this->NFNUPLOAD) $loadRecord = true;
		elseif(isset($recMap['occid']) && $recMap['occid']) $loadRecord = true;
		elseif(isset($recMap['dbpk']) && $recMap['dbpk']) $loadRecord = true;
		elseif(isset($recMap['catalognumber']) && $recMap['catalognumber']) $loadRecord = true;
		elseif(isset($recMap['othercatalognumbers']) && $recMap['othercatalognumbers']) $loadRecord = true;
		elseif(isset($recMap['occurrenceid']) && $recMap['occurrenceid']) $loadRecord = true;
		elseif(isset($recMap['recordedby']) && $recMap['recordedby']) $loadRecord = true;
		elseif(isset($recMap['eventdate']) && $recMap['eventdate']) $loadRecord = true;
		elseif(isset($recMap['sciname']) && $recMap['sciname']) $loadRecord = true;
		if($loadRecord){
			//Remove institution and collection codes when they match what is in omcollections
			if(array_key_exists('institutioncode',$recMap) && $recMap['institutioncode'] == $this->collMetadataArr['institutioncode']){
				unset($recMap['institutioncode']);
			}
			if(array_key_exists('collectioncode',$recMap) && $recMap['collectioncode'] == $this->collMetadataArr['collectioncode']){
				unset($recMap['collectioncode']);
			}

			//Do some cleaning on the dbpk; remove leading and trailing whitespaces and convert multiple spaces to a single space
			if(array_key_exists('dbpk',$recMap) && $recMap['dbpk']){
				$recMap['dbpk'] = trim(preg_replace('/\s\s+/',' ',$recMap['dbpk']));
			}
			else{
				if($this->collMetadataArr['managementtype'] == 'Live Data' || $this->uploadType == $this->SKELETAL){
					//If dbpk does not exist, set a temp value
					if(!isset($recMap['dbpk']) || !$recMap['dbpk']) $recMap['dbpk'] = 'SYMBDBPK-'.$this->dbpkCnt.'-'.time();
					$this->dbpkCnt++;
				}
			}

			//Set processingStatus to value defined by loader
			if($this->processingStatus){
				$recMap['processingstatus'] = $this->processingStatus;
			}

			//Temporarily code until Specify output UUID as occurrenceID
			if($this->sourceDatabaseType == 'specify' && (!isset($recMap['occurrenceid']) || !$recMap['occurrenceid'])){
				if(strlen($recMap['dbpk']) == 36) $recMap['occurrenceid'] = $recMap['dbpk'];
			}
			try {
				$this->buildPaleoJSON($recMap);
			} catch (Exception $e){
				$this->outputMsg('<li>Error JSON encoding paleo data for record #'.$this->transferCount.'</li>');
				$this->outputMsg('<li style="margin-left:10px;">Error: '.$e->getMessage().'</li>');
			}
			// try {
			// 	$this->buildMaterialSampleJSON($recMap);
			// } catch (Exception $e){
			// 	$this->outputMsg('<li>Error JSON encoding material sample data for record #'.$this->transferCount.'</li>');
			// 	$this->outputMsg('<li style="margin-left:10px;">Error: '.$e->getMessage().'</li>');
			// }


			$sqlFragments = $this->getSqlFragments($recMap,$this->occurFieldMap);
			if($sqlFragments){
				$sql = 'INSERT INTO uploadspectemp(collid'.$sqlFragments['fieldstr'].') VALUES('.$this->collId.$sqlFragments['valuestr'].')';
				if($this->conn->query($sql)){
					$this->transferCount++;
					if($this->transferCount%1000 == 0) $this->outputMsg('<li style="margin-left:10px;">Count: '.$this->transferCount.'</li>');
					//$this->outputMsg("<li>");
					//$this->outputMsg("Appending/Replacing observation #".$this->transferCount.": SUCCESS");
					//$this->outputMsg("</li>");
				}
				else{
					$sql = Encoding::fixUTF8($sql);
					if(!$this->conn->query($sql)){
						$this->outputMsg('<li>FAILED adding record #'.$this->transferCount.'</li>');
						$this->outputMsg('<li style="margin-left:10px;">Error: '.$this->conn->error.'</li>');
						$this->outputMsg('<li style="margin:0px 0px 10px 10px;">SQL: '.$sql.'</li>');
					}
				}

				if(isset($recMap['othercatalognumbers']) && $recMap['othercatalognumbers']) {
					$parsedCatalogNumbers = self::parseOtherCatalogNumbers($recMap['othercatalognumbers']);
					$sql = 'INSERT INTO uploadkeyvaluetemp (`key`, `value`, dbpk, uploadUid, type) VALUES (?, ?, ?, ?, "omoccuridentifiers")';
					foreach ($parsedCatalogNumbers as $entry) {
						QueryUtil::executeQuery($this->conn, $sql, [$entry['key'], $entry['value'], $recMap['dbpk'], $GLOBALS['SYMB_UID']]);
					}
				}
			}

		}
	}
	private static function parseOtherCatalogNumbers($otherCatalogNumbers): Array {
		$catalogNumbers = explode(';', str_replace(['|',','], ';', $otherCatalogNumbers));
		$parsedCatalogNumbers = [];

		for ($i = 0; $i < count($catalogNumbers); $i++) {
			$key_value = explode(':', $catalogNumbers[$i]);

			if(count($key_value) == 2) {
				array_push($parsedCatalogNumbers, [
					'key' => trim($key_value[0]),
					'value' => trim($key_value[1]),
				]);
			} else if(count($key_value) > 0) {
				array_push($parsedCatalogNumbers ,[
					'key' => '',
					'value' => trim($key_value[0]),
				]);
			}
		}

		return $parsedCatalogNumbers;
	}

	private function buildPaleoJSON(&$recMap){
		if($this->paleoSupport){
			$paleoTermArr = $this->getPaleoTerms();
			$paleoArr = array();
			foreach($paleoTermArr as $fieldName){
				$k = strtolower($fieldName);
				if(isset($recMap[$k])){
					if($recMap[$k] !== '') $paleoArr[substr($k,6)] = $recMap[$k];
					unset($recMap[$k]);
				}
			}
			if($paleoArr){
				$recMap['paleoJSON'] = json_encode($paleoArr);
				if(json_last_error() !== JSON_ERROR_NONE){
					throw new Exception("JSON encoding error: ".json_last_error_msg());
				}
			}
		}
	}

	// private function buildMaterialSampleJSON(&$recMap){
	// 	if($this->materialSampleSupport){
	// 		$msTermArr = $this->getMaterialSampleTerms();
	// 		$msArr = array();
	// 		foreach($msTermArr as $fieldName){
	// 			if(isset($recMap[$fieldName])){
	// 				if($recMap[$fieldName] !== '') $msArr[substr($fieldName,15)] = $recMap[$fieldName];
	// 				unset($recMap[$fieldName]);
	// 			}
	// 		}
	// 		if($msArr){
	// 			$recMap['materialSampleJSON'] = json_encode($msArr);
	// 			if(json_last_error() !== JSON_ERROR_NONE){
	// 				throw new Exception("JSON encoding error: ".json_last_error_msg());
	// 			}
	// 		}
	// 	}
	// }

	protected function loadIdentificationRecord($recMap){
		if($recMap){
			//coreId should go into dbpk
			if(isset($recMap['coreid']) && !isset($recMap['dbpk'])){
				$recMap['dbpk'] = $recMap['coreid'];
				unset($recMap['coreid']);
			}

			//Import record only if required fields have data (coreId and a scientificName)
			if(isset($recMap['dbpk']) && $recMap['dbpk'] && (isset($recMap['sciname']) || isset($recMap['genus']))){

				//Do some cleaning
				//Populate sciname if null
				if(!array_key_exists('sciname',$recMap) || !$recMap['sciname']){
					if(array_key_exists('genus',$recMap) && array_key_exists('specificepithet',$recMap) && array_key_exists('infraspecificepithet',$recMap)){
						//Build sciname from individual units supplied by source
						$sciName = $recMap['genus'];
						if(array_key_exists('specificepithet',$recMap) && $recMap['specificepithet']) $sciName .= ' '.$recMap['specificepithet'];
						if(array_key_exists('infraspecificepithet',$recMap) && $recMap['infraspecificepithet']){
							if(array_key_exists('taxonrank',$recMap) && $recMap['taxonrank']){
								$infraStr = $recMap['taxonrank'];
								if($infraStr == 'subspecies') $infraStr = 'subsp.';
								elseif($infraStr == 'ssp.') $infraStr = 'subsp.';
								elseif($infraStr == 'variety') $infraStr = 'var.';
								$sciName .= ' '.$infraStr;
							}
							$sciName .= ' '.$recMap['infraspecificepithet'];
						}
						$recMap['sciname'] = trim($sciName);
					}
				}
				//Remove fields that are not in the omoccurdetermination tables
				unset($recMap['genus']);
				unset($recMap['specificepithet']);
				unset($recMap['taxonrank']);
				unset($recMap['infraspecificepithet']);
				//Try to get author, if it's not there
				/*
				if(!array_key_exists('scientificnameauthorship',$recMap) || !$recMap['scientificnameauthorship']){
					//Parse scientific name to see if it has author imbedded
					$parsedArr = OccurrenceUtil::parseScientificName($recMap['sciname'],$this->conn);
					if(array_key_exists('author',$parsedArr)){
						$recMap['scientificnameauthorship'] = $parsedArr['author'];
						//Load sciname from parsedArr since if appears that author was embedded
						$recMap['sciname'] = trim($parsedArr['unitname1'].' '.$parsedArr['unitname2'].' '.$parsedArr['unitind3'].' '.$parsedArr['unitname3']);
					}
				}
				*/
				if(!isset($recMap['sciname']) || !$recMap['sciname']) return false;

				if(!isset($recMap['identifiedby'])) $recMap['identifiedby'] = '';
				if(!isset($recMap['dateidentified'])) $recMap['dateidentified'] = '';
				$sqlFragments = $this->getSqlFragments($recMap, $this->identFieldMap);
				if($sqlFragments){
					$sql = 'INSERT INTO uploaddetermtemp(collid'.$sqlFragments['fieldstr'].') VALUES('.$this->collId.$sqlFragments['valuestr'].')';
					//echo '<div>SQL: '.$sql.'</div>'; exit;
					if($this->conn->query($sql)){
						$this->identTransferCount++;
						if($this->identTransferCount%1000 == 0) $this->outputMsg('<li style="margin-left:10px;">Count: '.$this->identTransferCount.'</li>');
					}
					else{
						$outStr = '<li>FAILED adding identification history record #'.$this->identTransferCount.'</li>';
						$outStr .= '<li style="margin-left:10px;">Error: '.$this->conn->error.'</li>';
						$outStr .= '<li style="margin:0px 0px 10px 10px;">SQL: '.$sql.'</li>';
						$this->outputMsg($outStr);
					}
				}
			}
		}
	}

	protected function loadMediaRecord($recMap){
		if($recMap){
			//Test images
			$testUrl = '';
			if(isset($recMap['originalurl']) && $recMap['originalurl'] && substr($recMap['originalurl'],0,10) != 'processing'){
				$testUrl = $recMap['originalurl'];
			}
			elseif(isset($recMap['url']) && $recMap['url'] && $recMap['url'] != 'empty'){
				$testUrl = $recMap['url'];
			}
			else{
				//Abort, no images avaialble
				return false;
			}

			$file = Media::parseFileName($testUrl);
			$parsed_mime = Media::ext2Mime($file['extension']);
			if(!$parsed_mime) {
				try {
					$file = Media::getRemoteFileInfo($testUrl);
					$parsed_mime = $file['type'];
				} catch(Throwable $error) {
					error_log('SpecUploadBase: Failed to Parse File: ' . $error->getMessage() . ' ' . $testUrl . ' ' . __LINE__ . ' ');
					$this->outputMsg('<li style="margin-left:20px;">File format could not be parsed: ' . $testUrl . ' </li>');
					return false;
				}
				
			}
			$mime = Media::getAllowedMime($parsed_mime);
			if(!$mime) {
				if($parsed_mime) {
					$this->outputMsg('<li style="margin-left:20px;">Unsupported File Format: ' . $parsed_mime . ' from url ' . $testUrl . ' </li>');
				} else {
					$this->outputMsg('<li style="margin-left:20px;">Could Not Parse the File Format: ' . $testUrl . ' </li>');
				}
				// Not Supported extension
				return false;
			} else {
				$recMap['format'] = $mime;
			}

			$mediaTypeStr = explode('/', $mime)[0];
			$mediaType = MediaType::tryFrom($mediaTypeStr);

			if(!$mediaType) {
				$this->outputMsg('<li style="margin-left:20px;">Invalid Media Type: ' . $mediaType . ' from url ' . $testUrl . ' </li>');
				return false;
			}

			$recMap['mediaType'] = $mediaType; 

			if($this->verifyImageUrls){
				if(!$this->urlExists($testUrl)){
					$this->outputMsg('<li style="margin-left:20px;">Bad url: '.$testUrl.'</li>');
					return false;
				}
			}

			if(strpos($testUrl,'inaturalist.org') || strpos($testUrl,'inaturalist-open-data')){
				//Special processing for iNaturalist imports
				if(strpos($testUrl,'/original.')){
					$recMap['originalurl'] = $testUrl;
					$recMap['url'] = str_replace('/original.', '/medium.', $testUrl);
					$recMap['thumbnailurl'] = str_replace('/original.', '/small.', $testUrl);
				}
				elseif(strpos($testUrl,'/medium.')){
					$recMap['url'] = $testUrl;
					$recMap['thumbnailurl'] = str_replace('/medium.', '/small.', $testUrl);
					$recMap['originalurl'] = str_replace('/medium.', '/original.', $testUrl);
				}
			}

			if(!isset($recMap['url'])) $recMap['url'] = '';
			if(!array_key_exists('sourceidentifier', $recMap) && in_array('sourceidentifier',$this->imageSymbFields)){
				$url = $recMap['originalurl'];
				if(!$url) $url = $recMap['url'];
				if(preg_match('=/([^/?*;:{}\\\\]+\.[jpegpn]{3,4}$)=', $url, $m)){
					$recMap['sourceidentifier'] = $m[1];
				}
			}
			$sqlFragments = $this->getSqlFragments($recMap,$this->imageFieldMap);
			if($sqlFragments){
				$sql = 'INSERT INTO uploadimagetemp(collid'.$sqlFragments['fieldstr'].') VALUES('.$this->collId.$sqlFragments['valuestr'].')';
				if($this->conn->query($sql)){
					$this->imageTransferCount++;
					$repInt = 1000;
					if($this->verifyImageUrls) $repInt = 100;
					if($this->imageTransferCount%$repInt == 0) $this->outputMsg('<li style="margin-left:10px;">'.$this->imageTransferCount.' images processed</li>');
				}
				else{
					$this->outputMsg("<li>FAILED adding image record #".$this->imageTransferCount."</li>");
					$this->outputMsg("<li style='margin-left:10px;'>Error: ".$this->conn->error."</li>");
					$this->outputMsg("<li style='margin:0px 0px 10px 10px;'>SQL: $sql</li>");
				}
			}
		}
	}

	private function getSqlFragments($recMap,$fieldMap){
		$hasValue = false;
		$sqlFields = '';
		$sqlValues = '';
		foreach($recMap as $symbField => $valueStr){
			if(substr($symbField,0,8) != 'unmapped' && $symbField != 'collid'){
				$sqlFields .= ','.$symbField;
				$valueStr = $this->encodeString($valueStr);
				$valueStr = $this->cleanInStr($valueStr ?? '');
				$valueStr = $this->removeEmoji($valueStr);
				if($valueStr) $hasValue = true;
				//Load data
				$type = '';
				$size = 0;
				if(array_key_exists($symbField,$fieldMap)){
					if(array_key_exists('type',$fieldMap[$symbField])){
						$type = $fieldMap[$symbField]["type"];
					}
					if(array_key_exists('size',$fieldMap[$symbField])){
						$size = $fieldMap[$symbField]["size"];
					}
				}
				switch($type){
					case "numeric":
						if(is_numeric($valueStr)){
							if($symbField == 'coordinateuncertaintyinmeters' && $valueStr < 0) $valueStr = abs($valueStr);
							$sqlValues .= ",".$valueStr;
						}
						elseif(is_numeric(str_replace(',',"",$valueStr))){
							$sqlValues .= ",".str_replace(',',"",$valueStr);
						}
						else{
							$sqlValues .= ",NULL";
						}
						break;
					case "decimal":
						if(strpos($valueStr,',')){
							$sqlValues = str_replace(',','',$valueStr);
						}
						if($valueStr && $size && strpos($size,',') !== false){
							$tok = explode(',',$size);
							$m = $tok[0];
							$d = $tok[1];
							if($m && $d){
								$dec = substr($valueStr,strpos($valueStr,'.'));
								if(strlen($dec) > $d){
									$valueStr = round($valueStr,$d);
								}
								$rawLen = strlen(str_replace(array('-','.'),'',$valueStr));
								if($rawLen > $m){
									if(strpos($valueStr,'.') !== false){
										$decLen = strlen(substr($valueStr,strpos($valueStr,'.')));
										if($decLen < ($rawLen - $m)){
											$valueStr = '';
										}
										else{
											$valueStr = round($valueStr,$decLen - ($rawLen - $m));
										}
									}
									else{
										$valueStr = '';
									}
								}
							}
						}
						if(is_numeric($valueStr)){
							$sqlValues .= ",".$valueStr;
						}
						else{
							$sqlValues .= ",NULL";
						}
						break;
					case "date":
						$dateStr = OccurrenceUtil::formatDate($valueStr);
						if($dateStr){
							$sqlValues .= ',"'.$dateStr.'"';
						}
						else{
							$sqlValues .= ",NULL";
						}
						break;
					default:	//string
						if($size && strlen($valueStr) > $size){
							$valueStr = substr($valueStr,0,$size);
						}
						if(substr($valueStr,-1) == "\\"){
							$valueStr = rtrim($valueStr,"\\");
						}
						if($valueStr){
							$sqlValues .= ',"'.$valueStr.'"';
						}
						elseif($symbField == 'identifiedby' || $symbField == 'dateidentified'){
							$sqlValues .= ',""';
						}
						else{
							$sqlValues .= ",NULL";
						}
				}
			}
		}
		if(!$hasValue) return false;
		return array('fieldstr' => $sqlFields,'valuestr' => $sqlValues);
	}

	public function getTransferCount(){
		if(!$this->transferCount) $this->setTransferCount();
		return $this->transferCount;
	}

	protected function setTransferCount(){
		if($this->collId){
			$sql = 'SELECT count(*) AS cnt FROM uploadspectemp WHERE (collid IN('.$this->collId.')) ';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->transferCount = $row->cnt;
			}
			$rs->free();
		}
	}

	public function getIdentTransferCount(){
		if(!$this->identTransferCount) $this->setIdentTransferCount();
		return $this->identTransferCount;
	}

	protected function setIdentTransferCount(){
		if($this->collId){
			$sql = 'SELECT count(*) AS cnt FROM uploaddetermtemp WHERE (collid IN('.$this->collId.'))';
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->identTransferCount = $row->cnt;
			}
			$rs->free();
		}
	}

	private function getImageTransferCount(){
		if(!$this->imageTransferCount) $this->setImageTransferCount();
		return $this->imageTransferCount;
	}

	protected function setImageTransferCount(){
		if($this->collId){
			$sql = 'SELECT count(*) AS cnt FROM uploadimagetemp WHERE (collid IN('.$this->collId.'))';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$this->imageTransferCount = $r->cnt;
			}
			else{
				$this->outputMsg('<li style="margin-left:20px;">ERROR setting image upload count: '.$this->conn->error.'</li> ');
			}
			$rs->free();
		}
	}

	protected function setUploadTargetPath(){
		$tPath = $GLOBALS['TEMP_DIR_ROOT'];
		if(!$tPath){
			$tPath = ini_get('upload_tmp_dir');
		}
		if(!$tPath){
			$tPath = $GLOBALS['SERVER_ROOT'].'/temp';
		}
		if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\'){
			$tPath .= '/';
		}
		if(file_exists($tPath.'downloads')){
			$tPath .= 'data/';
		}
		$this->uploadTargetPath = $tPath;
	}

	public function addFilterCondition($columnName, $condition, $value){
		if($columnName && ($value || $condition == 'IS_NULL' || $condition == 'NOT_NULL')){
			$this->filterArr[strtolower($columnName)][$condition][] = strtolower(trim($value,'; '));
		}
	}

	//Occurrence PK coordination functions
	private function updateOccidMatchingDbpk(){
		$status = false;
		$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON u.dbpk = o.dbpk AND u.collid = o.collid
			SET u.occid = o.occid
			WHERE u.occid IS NULL AND u.dbpk IS NOT NULL AND o.collid = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->collId);
			$stmt->execute();
			if(!$stmt->error) $status = true;
			else $this->errorStr = $stmt->error;
			$stmt->close();
		}
		return $status;
	}

	private function updateOccidMatchingCatalogNumber(){
		$status = false;
		$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON u.catalogNumber = o.catalogNumber AND u.collid = o.collid
			SET u.occid = o.occid
			WHERE u.occid IS NULL AND u.catalogNumber IS NOT NULL AND o.collid = ? ';
		if($this->collMetadataArr['colltype'] == 'General Observations' && $this->observerUid) $sql .= ' AND o.observeruid = '.$this->observerUid;
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->collId);
			$stmt->execute();
			if(!$stmt->error) $status = true;
			else $this->errorStr = $stmt->error;
			$stmt->close();
		}
		return $status;
	}

	private function updateOccidMatchingOtherCatalogNumbers(){
		$status = false;
		$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON u.otherCatalogNumbers = o.otherCatalogNumbers AND u.collid = o.collid
			SET u.occid = o.occid
			WHERE u.occid IS NULL AND u.otherCatalogNumbers IS NOT NULL AND o.collid = ? ';
		if($this->collMetadataArr['colltype'] == 'General Observations' && $this->observerUid) $sql .= 'AND o.observeruid = '.$this->observerUid;
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $this->collId);
			$stmt->execute();
			if(!$stmt->error) $status = true;
			else $this->errorStr = $stmt->error;
			$stmt->close();
		}

		$sql2 = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON u.collid = o.collid
			INNER JOIN omoccuridentifiers i ON (o.occid = i.occid) AND (u.othercatalogNumbers = i.identifiervalue)
			SET u.occid = o.occid
			WHERE u.occid IS NULL AND o.collid = ? ';
		if($this->collMetadataArr['colltype'] == 'General Observations' && $this->observerUid) $sql2 .= 'AND o.observeruid = '.$this->observerUid;
		if($stmt2 = $this->conn->prepare($sql2)){
			$stmt2->bind_param('i', $this->collId);
			$stmt2->execute();
			if(!$stmt2->error) $status = true;
			else $this->errorStr = $stmt2->error;
			$stmt2->close();
		}
		return $status;
	}

	//Data functions
	private function getPaleoTerms(){
		$paleoTermArr = array_merge($this->getPaleoDwcTerms(),$this->getPaleoSymbTerms());
		sort($paleoTermArr);
		return $paleoTermArr;
	}

	private function getPaleoDwcTerms(){
		$paleoTermArr = array('paleo-earliesteonorlowesteonothem','paleo-latesteonorhighesteonothem','paleo-earliesteraorlowesterathem',
			'paleo-latesteraorhighesterathem','paleo-earliestperiodorlowestsystem','paleo-latestperiodorhighestsystem','paleo-earliestepochorlowestseries',
			'paleo-latestepochorhighestseries','paleo-earliestageorloweststage','paleo-latestageorhigheststage','paleo-lowestbiostratigraphiczone','paleo-highestbiostratigraphiczone');
		return $paleoTermArr;
	}

	private function getPaleoSymbTerms(){
		$paleoTermArr = array('paleo-geologicalcontextid','paleo-lithogroup','paleo-formation','paleo-member','paleo-bed','paleo-eon','paleo-era','paleo-period','paleo-epoch',
			'paleo-earlyinterval','paleo-lateinterval','paleo-absoluteage','paleo-storageage','paleo-stage','paleo-localstage','paleo-biota','paleo-biostratigraphy',
			'paleo-taxonenvironment','paleo-lithology','paleo-stratremarks','paleo-element','paleo-slideproperties');
		return $paleoTermArr;
	}

	// private function getMaterialSampleTerms(){
	// 	$msTermArr = array('materialSample-guid','materialSample-sampleType','materialSample-catalogNumber','materialSample-sampleCondition','materialSample-disposition',
	// 		'materialSample-preservationType','materialSample-preparationProcess','materialSample-preparationDate','materialSample-individualCount',
	// 		'materialSample-sampleSize','materialSample-storageLocation','materialSample-remarks');
	// 	//Get dynamic fields
	// 	$sql = 'SELECT t.term FROM ctcontrolvocab v INNER JOIN ctcontrolvocabterm t ON v.cvID = t.cvID WHERE v.tableName = "ommaterialsampleextended" AND v.fieldName = "fieldName"';
	// 	$rs = $this->conn->query($sql);
	// 	while($r = $rs->fetch_object()){
	// 		$msTermArr[] = 'materialSample-'.$r->term;
	// 	}
	// 	$rs->free();
	// 	return $msTermArr;
	// }

	public function getObserverUidArr(){
		$retArr = array();
		if($this->collId){
			$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname, u.firstname) as user '.
				'FROM users u INNER JOIN userroles r ON u.uid = r.uid '.
				'WHERE r.tablepk = '.$this->collId.' AND r.role IN("CollEditor","CollAdmin")';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->uid] = $r->user;
			}
			$rs->free();
		}
		asort($retArr);
		return $retArr;
	}

	private function getOccurrenceFieldArr($filterArr){
		$retArr = array();
		$sql = 'SHOW COLUMNS FROM omoccurrences';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$field = strtolower($row->Field);
			if(in_array($field, $filterArr)) $retArr[] = $field;
		}
		$rs->free();
		return $retArr;
	}

	//Setters and getters
	public function setIncludeIdentificationHistory($boolIn){
		$this->includeIdentificationHistory = $boolIn;
	}

	public function setIncludeImages($boolIn){
		$this->includeImages = $boolIn;
	}

	public function setObserverUid($id){
		if(is_numeric($id)) $this->observerUid = $id;
	}

	public function setMatchCatalogNumber($match){
		$this->matchCatalogNumber = $match;
	}

	public function setMatchOtherCatalogNumbers($match){
		$this->matchOtherCatalogNumbers = $match;
	}

	public function setVersionDataEdits($v){
		$this->versionDataEdits = $v;
	}

	public function setVerifyImageUrls($v){
		$this->verifyImageUrls = $v;
	}

	public function setProcessingStatus($s){
		$this->processingStatus = $s;
	}

	public function setSourceCharset($cs){
		$this->sourceCharset = $cs;
	}

	public function setSourceDatabaseType($type){
		$this->sourceDatabaseType = $type;
	}

	public function getSourceArr(){
		return $this->occurSourceArr;
	}

	public function setTargetFieldArr($targetStr){
		//Need to check field names against database to protect against SQL injection
		if($targetStr){
			$targetFieldArr = explode(',', $targetStr);
			$this->targetFieldArr = $this->getOccurrenceFieldArr($targetFieldArr);
		}
	}

	public function getTargetFieldStr(){
		return implode(',', $this->targetFieldArr);
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
					$this->relationshipArr[$r->inverseRelationship] = $r->term;
				}
				$rs->free();
			}
		}
	}

	//Misc support functions
	protected function copyChunked($from, $to){
		/*
		 * If transfers fail for large files, you may need to increase following php.ini variables:
		 * 		max_input_time, max_execution_time, default_socket_timeout
		 */

		//2 meg at a time
		$buffer_size = 2097152;		//1048576;
		$byteCount = 0;
		$fin = fopen($from, "rb");
		$fout = fopen($to, "w");
		if($fin && $fout){
			while(!feof($fin)) {
				$byteCount += fwrite($fout, fread($fin, $buffer_size));
			}
		}
		fclose($fin);
		fclose($fout);
		return $byteCount;
	}

	private function getMimeType($url){
		if(!strstr($url, "http")){
			$url = "http://".$url;
		}
		$handle = curl_init($url);
		curl_setopt($handle, CURLOPT_HEADER, true);
		curl_setopt($handle, CURLOPT_NOBODY, true);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($handle, CURLOPT_TIMEOUT, 3);
		curl_exec($handle);
		return curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
	}

	protected function urlExists($url) {
		$exists = false;
		if(!strstr($url, "http")){
			$url = "http://".$url;
		}
		if(function_exists('curl_init')){
			// Version 4.x supported
			$handle = curl_init($url);
			if (false === $handle){
				$exists = false;
			}
			curl_setopt($handle, CURLOPT_HEADER, false);
			curl_setopt($handle, CURLOPT_FAILONERROR, true);
			curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") ); // request as if Firefox
			curl_setopt($handle, CURLOPT_NOBODY, true);
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
			$exists = curl_exec($handle);
			curl_close($handle);
		}

		if(!$exists && file_exists($url)){
			$exists = true;
		}

		//One more  check
		if(!$exists){
			$exists = (@fclose(@fopen($url,"r")));
		}
		return $exists;
	}

	protected function encodeString($inStr){
		if($inStr){
			/*
			 * Previously used 'UTF-8, ISO-8859-1, ISO-8859-15'
			 * And UTF-8 strings were not being detected resulting
			 * Double encoding issue. Proceed with caution when adding encoding params
			 */
			$inStr = mb_convert_encoding($inStr, $this->targetCharset, mb_detect_encoding($inStr));

			//Get rid of UTF-8 curly smart quotes and dashes
			$badwordchars=array(
				"\xe2\x80\x98", // left single quote
				"\xe2\x80\x99", // right single quote
				"\xe2\x80\x9c", // left double quote
				"\xe2\x80\x9d", // right double quote
				"\xe2\x80\x94", // em dash
				"\xe2\x80\xa6" // elipses
			);
			$fixedwordchars=array("'", "'", '"', '"', '-', '...');
			$inStr = str_replace($badwordchars, $fixedwordchars, $inStr);
		}
		return $inStr;
	}

	function removeEmoji($string){
		// Match Enclosed Alphanumeric Supplement
		$regexAlphanumeric = '/[\x{1F100}-\x{1F1FF}]/u';
		$clearString = preg_replace($regexAlphanumeric, '', $string);

		// Match Miscellaneous Symbols and Pictographs
		$regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
		$clearString = preg_replace($regexSymbols, '', $clearString);

		// Match Emoticons
		$regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
		$clearString = preg_replace($regexEmoticons, '', $clearString);

		// Match Transport And Map Symbols
		$regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
		$clearString = preg_replace($regexTransport, '', $clearString);

		// Match Supplemental Symbols and Pictographs
		$regexSupplemental = '/[\x{1F900}-\x{1F9FF}]/u';
		$clearString = preg_replace($regexSupplemental, '', $clearString);

		// Match Miscellaneous Symbols
		$regexMisc = '/[\x{2600}-\x{26FF}]/u';
		$clearString = preg_replace($regexMisc, '', $clearString);

		// Match Dingbats
		$regexDingbats = '/[\x{2700}-\x{27BF}]/u';
		$clearString = preg_replace($regexDingbats, '', $clearString);

		return $clearString;
	}
}
?>
