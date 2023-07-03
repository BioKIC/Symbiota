<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');
include_once($SERVER_ROOT.'/classes/TaxonomyHarvester.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMaintenance.php');

class TaxonomyUpload{

	private $conn;
	private $uploadFileName;
	private $uploadTargetPath;
	private $taxAuthId = 1;
	private $kingdomName;
	private $kingdomTid;
	private $taxonUnitArr = array();
	private $statArr = array();
	private $langArr = false;

	private $verboseMode = 1; // 0 = silent, 1 = echo only, 2 = echo and log
	private $logFH;
	private $errorStr = '';

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
 		$this->setUploadTargetPath();
 		set_time_limit(3000);
		ini_set("max_input_time",120);
  		ini_set('auto_detect_line_endings', true);
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
		if($this->verboseMode == 2){
			if($this->logFH) fclose($this->logFH);
		}
	}

	public function setUploadFile($ulFileName = ""){
		if($ulFileName){
			//URL to existing file
			if(file_exists($ulFileName)){
				$pos = strrpos($ulFileName,"/");
				if(!$pos) $pos = strrpos($ulFileName,"\\");
				$this->uploadFileName = substr($ulFileName,$pos+1);
				//$this->outputMsg($this->uploadFileName;
				copy($ulFileName,$this->uploadTargetPath.$this->uploadFileName);
			}
		}
		elseif(array_key_exists('uploadfile',$_FILES)){
			$this->uploadFileName = $_FILES['uploadfile']['name'];
			move_uploaded_file($_FILES['uploadfile']['tmp_name'], $this->uploadTargetPath.$this->uploadFileName);
		}
		if(file_exists($this->uploadTargetPath.$this->uploadFileName) && substr($this->uploadFileName,-4) == ".zip"){
			$zip = new ZipArchive;
			$zip->open($this->uploadTargetPath.$this->uploadFileName);
			$zipFile = $this->uploadTargetPath.$this->uploadFileName;
			$this->uploadFileName = $zip->getNameIndex(0);
			$zip->extractTo($this->uploadTargetPath);
			$zip->close();
			unlink($zipFile);
		}
	}

	public function loadFile($fieldMap){
		//fieldMap = array(source field => target field)
		$this->outputMsg('Starting Upload',0);
		$this->conn->query("DELETE FROM uploadtaxa");
		$this->conn->query("OPTIMIZE TABLE uploadtaxa");
		if(!in_array('scinameinput',$fieldMap) && in_array('sciname',$fieldMap)){
			//Input sciname was mapped to sciname and not scinameinput
			$sourceScinameKey = array_search('sciname', $fieldMap);
			$fieldMap[$sourceScinameKey] = 'scinameinput';
		}

		if(($fh = fopen($this->uploadTargetPath.$this->uploadFileName,'r')) !== FALSE){
			$headerArr = fgetcsv($fh);
			$uploadTaxaFieldArr = $this->getUploadTaxaFieldArr();
			if(!$this->taxonUnitArr) $this->setTaxonUnitArr();
			$taxonUnitArr = $this->taxonUnitArr;
			foreach($taxonUnitArr as $tuKey => $tuVal){
				if($tuKey > 219) unset($taxonUnitArr[$tuKey]);
			}
			$scinameInputKey = false;
			$uploadTaxaIndexArr = array();		//Array of index values associated with uploadtaxa table; array(index => targetName)
			$taxonUnitIndexArr = array();		//Array of index values associated with taxonunits table;
			foreach($headerArr as $k => $sourceName){
				$sourceName = trim(strtolower($sourceName));
				if(array_key_exists($sourceName,$fieldMap)){
					$targetName = $fieldMap[$sourceName];
					if(in_array($targetName,$uploadTaxaFieldArr)){
						//Is a taxa table target field
						$uploadTaxaIndexArr[$k] = $targetName;
					}
					if($targetName == 'scinameinput') $scinameInputKey = $k;
					if($targetName == 'unitname1') $targetName = 'genus';
					if(in_array($targetName,$taxonUnitArr)){
						$taxonUnitIndexArr[$k] = array_search($targetName,$taxonUnitArr);  //array(recIndex => rankid)
					}
				}
			}
			$parentIndex = 0;
			if(!in_array('parentstr',$uploadTaxaIndexArr)){
				$parentIndex = max(array_keys($uploadTaxaIndexArr))+1;
				$uploadTaxaIndexArr[$parentIndex] = 'parentstr';
			}
			$familyIndex = 0;
			if(in_array('family',$fieldMap)) $familyIndex = array_search(array_search('family',$fieldMap),$headerArr);
			//scinameinput field is required
			if(in_array('scinameinput',$fieldMap) || count($taxonUnitIndexArr) > 2){
				$recordCnt = 1;
				asort($taxonUnitIndexArr);
				$childParentArr = array();		//array(taxon => array('p'=>parentStr,'r'=>rankid)
				$this->conn->query('SET autocommit=0');
				$this->conn->query('SET unique_checks=0');
				$this->conn->query('SET foreign_key_checks=0');
				while($recordArr = fgetcsv($fh)){
					//Load taxonunits fields into Array which will be loaded into taxon table at the end
					$parentStr = '';
					foreach($taxonUnitIndexArr as $index => $rankId){
						$taxonStr = $recordArr[$index];
						if($taxonStr){
							if(!array_key_exists($taxonStr,$childParentArr)){
								if($rankId == 10){
									//For kingdom taxa, parents are themselves
									$childParentArr[$taxonStr]['p'] = $taxonStr;
									$childParentArr[$taxonStr]['r'] = $rankId;
								}
								elseif($parentStr){
									$childParentArr[$taxonStr]['p'] = $parentStr;
									$childParentArr[$taxonStr]['r'] = $rankId;
									if($rankId > 140 && $familyIndex && $recordArr[$familyIndex]){
										$childParentArr[$taxonStr]['f'] = $recordArr[$familyIndex];
									}
								}
							}
							if($recordArr[$scinameInputKey] == $taxonStr) break;
							$parentStr = $taxonStr;
						}
					}
					if($parentIndex){
						$recordArr[$parentIndex] = 'PENDING:'.$parentStr;
					}
					if(in_array('scinameinput',$fieldMap)){
						//Load relavent fields into uploadtaxa table
						$inputArr = array();
						foreach($uploadTaxaIndexArr as $recIndex => $targetField){
							$valIn = trim($this->encodeString($recordArr[$recIndex]));
							if($targetField == 'acceptance' && !is_numeric($valIn)){
								$valInTest = strtolower($valIn);
								if($valInTest == 'accepted' || $valInTest == 'valid'){
									$valIn = 1;
								}
								elseif($valInTest == 'not accepted' || $valInTest == 'synonym'){
									$valIn = 0;
								}
								else{
									$valIn = '';
								}
							}
							if($valIn) $inputArr[$targetField] = $valIn;
						}
						//Some cleaning
						if(isset($inputArr['unitind3']) && $inputArr['unitind3']){
							//taxonRank was supplied, which can take different forms. Clean, translate, and convert to a valid unitind3
							if(!isset($inputArr['rankid']) || !$inputArr['rankid']){
								if($id = array_search($inputArr['unitind3'], $this->taxonUnitArr)){
									$inputArr['rankid'] = $id;
								}
							}
							if($infraArr = TaxonomyUtilities::cleanInfra($inputArr['unitind3'])){
								$inputArr['unitind3'] = $infraArr['infra'];
								$inputArr['rankid'] = $infraArr['rankid'];
							}
							if(isset($inputArr['rankid']) && $inputArr['rankid'] < 221) unset($inputArr['unitind3']);
							if(!isset($inputArr['unitname3']) || !$inputArr['unitname3']) unset($inputArr['unitind3']);
							if($this->kingdomName == 'Animalia'){
								unset($inputArr['unitind3']);
								if(isset($inputArr['rankid']) && $inputArr['rankid'] > 220) $inputArr['rankid'] = 230;
							}
						}
						//Insert record into uploadtaxa table
						if(array_key_exists('scinameinput', $inputArr)){
							if(!isset($inputArr['sciname']) && isset($inputArr['unitname1']) && $inputArr['unitname1']){
								//Build sciname
								$sciname = $inputArr['unitname1'];
								if(isset($inputArr['unitname2'])){
									$sciname .= ' '.$inputArr['unitname2'];
									if(isset($inputArr['unitname3'])){
										if(isset($inputArr['unitind3'])) $sciname .= ' '.$inputArr['unitind3'];
										$sciname .= ' '.$inputArr['unitname3'];
									}
								}
								$inputArr['sciname'] = trim($sciname);
							}
							if(isset($inputArr['rankid']) && $inputArr['rankid'] < 220 && isset($inputArr['sciname']) && !isset($inputArr['unitname1'])){
								$inputArr['unitname1'] = $inputArr['sciname'];
							}
							if(isset($inputArr['acceptedstr'])){
								if($this->kingdomName == 'Animalia') $inputArr['acceptedstr'] = str_replace(array(' subsp. ',' ssp. ',' var. ',' f. ',' fo. '), ' ', $inputArr['acceptedstr']);
							}
							$sciArr = TaxonomyUtilities::parseScientificName($inputArr['scinameinput'],$this->conn,(isset($inputArr['rankid'])?$inputArr['rankid']:0),$this->kingdomName);
							foreach($sciArr as $sciKey => $sciValue){
								if(!array_key_exists($sciKey, $inputArr) && $sciValue) $inputArr[$sciKey] = $sciValue;
							}
							if(isset($inputArr['unitind3']) && $inputArr['unitind3'] && isset($inputArr['unitname3']) && $inputArr['unitname3']){
								if(stripos($inputArr['unitname3'], $inputArr['unitind3'].' ') === 0) $inputArr['unitname3'] = trim(substr($inputArr['unitname3'], strlen($inputArr['unitind3']) + 1));
							}
							unset($inputArr['identificationqualifier']);
							if(isset($childParentArr[$inputArr['sciname']]['r']) && isset($inputArr['rankid']) && $childParentArr[$inputArr['sciname']]['r'] == $inputArr['rankid']) $childParentArr[$inputArr['sciname']]['s'] = 'skip';
							$sql1 = ''; $sql2 = '';
							foreach($inputArr as $k => $v){
								$sql1 .= ','.$k;
								$inValue = $this->cleanInStr($v);
								if($k == 'author') $sql2 .= ',"'.($inValue?$inValue:'').'"';
								else $sql2 .= ','.($inValue?'"'.$inValue.'"':'NULL');
							}
							$sql = 'INSERT INTO uploadtaxa('.substr($sql1,1).') VALUES('.substr($sql2,1).')';
							//echo '<div>'.$sql.'</div>';
							if($this->conn->query($sql)){
								if($recordCnt%1000 == 0){
									$this->outputMsg('Upload count: '.$recordCnt,1);
									ob_flush();
									flush();
								}
							}
							else{
								$this->outputMsg('ERROR loading taxon: '.$this->conn->error,2);
								//echo "<div>".$sql."</div>";
							}
						}
						unset($inputArr);
					}
					$recordCnt++;
				}
				$this->conn->query('COMMIT');
				$this->conn->query('SET autocommit=1');
				$this->conn->query('SET unique_checks=1');
				$this->conn->query('SET foreign_key_checks=1');

				//Process and load taxon units data ($childParentArr)
				foreach($childParentArr as $taxon => $tArr){
					if(isset($tArr['s'])) continue;
					$sql = 'INSERT IGNORE INTO uploadtaxa(scinameinput,sciname,rankid,parentstr,family,acceptance) '.
						'VALUES ("'.$taxon.'","'.$taxon.'",'.$tArr['r'].',"'.$tArr['p'].'",'.(array_key_exists('f',$tArr)?'"'.$tArr['f'].'"':'NULL').',1)';
					if(!$this->conn->query($sql)){
						$this->outputMsg('ERROR loading taxonunit: '.$this->conn->error);
					}
				}
				$this->outputMsg($recordCnt.' taxon records pre-processed');
			}
			else{
				$this->outputMsg('ERROR: Scientific name is not mapped to &quot;scinameinput&quot;');
			}
			fclose($fh);
			$this->removeUploadFile();
			$this->setUploadCount();
		}
		else{
			echo 'ERROR thrown opening input file: '.$this->uploadTargetPath.$this->uploadFileName.'<br/>';
			if(!is_writable($this->uploadTargetPath)) echo '<b>Target upload path is not writable. File permissions need to be adjusted</b>';
			exit;
		}
	}

	//ITIS import functions
	public function loadItisFile(){
		$this->outputMsg('Starting Upload');
		//Initiate upload process
		$extraArr = array();
		$authArr = array();
		$this->conn->query('DELETE FROM uploadtaxa');
		$this->conn->query('OPTIMIZE TABLE uploadtaxa');
		if(($fh = fopen($this->uploadTargetPath.$this->uploadFileName,'r')) !== FALSE){
			$this->outputMsg('Taxa file uploaded and successfully opened');

			//First run through file and grab and store Authors, Synonyms, and Vernaculars
			$delimtStr = "";
			$this->outputMsg('Harvesting authors, synonyms, and vernaculars');
			while($record = fgets($fh)){
				if(!$delimtStr){
					$delimtStr = "|";
					if(!strpos($record,"|") && strpos($record,",")){
						$delimtStr = ",";
					}
				}
				if(substr($record,4) != '[TU]'){
					$recordArr = explode($delimtStr,$record);
					$this->cleanInArr($recordArr);
					$this->encodeArr($recordArr);
					if($recordArr[0] == "[SY]"){
						$extraArr[$recordArr[2]]['s'] = $recordArr[3];
					}
					elseif($recordArr[0] == "[TA]"){
						$authArr[$recordArr[1]] = $recordArr[2];
					}
					elseif($recordArr[0] == "[VR]"){
						$extraArr[$recordArr[4]]['v'] = $recordArr[3];
						$extraArr[$recordArr[4]]['l'] = $recordArr[5];
					}
				}
			}
			if($authArr){
				$this->outputMsg('Authors mapped');
			}
			if($extraArr){
				$this->outputMsg('Synonyms and Vernaculars mapped');
			}

			//Load taxa records
			$this->outputMsg('Harvest and loading Taxa... ');
			$recordCnt = 0;
			rewind($fh);

			$this->conn->query('SET autocommit=0');
			$this->conn->query('SET unique_checks=0');
			$this->conn->query('SET foreign_key_checks=0');
			while($record = fgets($fh)){
				$recordArr = explode($delimtStr,$record);
				if($recordArr[0] == "[TU]"){
					$this->cleanInArr($recordArr);
					$this->encodeArr($recordArr);
					$this->loadItisTaxonUnit($recordArr,$extraArr,$authArr);
					$recordCnt++;
				}
			}
			$this->deleteIllegalHomonyms();
			$this->conn->query('COMMIT');
			$this->conn->query('SET autocommit=1');
			$this->conn->query('SET unique_checks=1');
			$this->conn->query('SET foreign_key_checks=1');

			$this->outputMsg($recordCnt.' records loaded');
			fclose($fh);
			$this->setUploadCount();
			$this->removeUploadFile();
		}
		else{
			echo 'ERROR thrown opening input file: '.$this->uploadTargetPath.$this->uploadFileName.'<br/>';
			if(!is_writable($this->uploadTargetPath)) echo '<b>Target upload path is not writable. File permissions need to be adjusted</b>';
			exit;
		}
	}

	private function loadItisTaxonUnit($tuArr,$extraArr,$authArr){
		if(count($tuArr) > 24){

			$unitInd3 = ($tuArr[8]?$tuArr[8]:$tuArr[6]);
			$unitName3 = ($tuArr[9]?$tuArr[9]:$tuArr[7]);
			$sciName = TRIM($tuArr[2]." ".$tuArr[3].($tuArr[4]?" ".$tuArr[4]:"")." ".$tuArr[5]." ".$unitInd3." ".$unitName3);
			$sciName = preg_REPLACE('/\s\s+/', ' ',$sciName);
			$author = '';
			if($tuArr[20] && array_key_exists($tuArr[20],$authArr)){
				$author = $authArr[$tuArr[20]];
				unset($authArr[$tuArr[20]]);
			}
			$sourceId = $tuArr[1];
			$sourceAcceptedId = '';
			$acceptance = '1';
			$vernacular = '';
			$vernlang = '';
			if(array_key_exists($sourceId,$extraArr)){
				$eArr = $extraArr[$sourceId];
				if(array_key_exists('s',$eArr)){
					$sourceAcceptedId = $eArr['s'];
					$acceptance = '0';
				}
				if(array_key_exists('v',$eArr)){
					$vernacular = $eArr['v'];
					$vernlang = $eArr['l'];
				}
				unset($extraArr[$sourceId]);
			}
			$sql = 'INSERT INTO uploadtaxa(SourceId,scinameinput,sciname,unitind1,unitname1,unitind2,unitname2,unitind3,'.
				'unitname3,SourceParentId,author,rankid,SourceAcceptedId,acceptance,vernacular,vernlang) '.
				'VALUES ('.$sourceId.',"'.$sciName.'","'.$sciName.'",'.
				($tuArr[2]?'"'.$tuArr[2].'"':'NULL').','.
				($tuArr[3]?'"'.$tuArr[3].'"':'NULL').','.
				($tuArr[4]?'"'.$tuArr[4].'"':'NULL').','.
				($tuArr[5]?'"'.$tuArr[5].'"':'NULL').','.
				($unitInd3?'"'.$unitInd3.'"':'NULL').','.($unitName3?'"'.$unitName3.'"':'NULL').','.
				($tuArr[18]?$tuArr[18]:'NULL').',"'.
				($author?$author:'NULL').'",'.
				($tuArr[24]?$tuArr[24]:'NULL').','.
				($sourceAcceptedId?$sourceAcceptedId:'NULL').','.$acceptance.','.
				($vernacular?'"'.$vernacular.'"':'NULL').','.
				($vernlang?'"'.$vernlang.'"':'NULL').')';
			//echo '<div>'.$sql.'</div>';
			if(!$this->conn->query($sql)){
				//Failed because name is already in table, thus replace if this one is accepted
				if($acceptance){
					$sql = 'REPLACE'.substr($sql,6);
					if(!$this->conn->query($sql)){
						$this->outputMsg('ERROR loading ITIS taxon: '.$this->conn->error);
					}
				}
			}
		}
	}

	private function deleteIllegalHomonyms(){
		$homonymArr = array();
		//Grab homonyms
		$sql = 'SELECT sciname, COUNT(*) cnt FROM uploadtaxa GROUP BY sciname HAVING cnt > 1';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$homonymArr[] = $r->sciname;
		}
		$rs->free();
		//Remove unaccepted, illegal homonyms
		if($homonymArr){
			$sql2 = 'DELETE FROM uploadtaxa WHERE (sciname IN("'.implode('','',$homonymArr).'")) AND (acceptance = 0) ';
			$this->conn->query($sql2);
		}
	}

	//Misc shared taxa processing functions
	private function removeUploadFile(){
		if($this->uploadTargetPath && $this->uploadFileName){
			if(file_exists($this->uploadTargetPath.$this->uploadFileName)){
				unlink($this->uploadTargetPath.$this->uploadFileName);
			}
		}
	}

	public function cleanUpload(){
		if(!$this->kingdomTid){
			$this->outputMsg('ABORT: kingdom identifier (TID) failed to populate (function: cleanUpload; kingdom: '.$this->kingdomName.')');
			return false;
		}

		$sql = 'UPDATE uploadtaxa SET unitind3 = NULL WHERE unitind3 IS NOT NULL AND unitname3 IS NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		$sql = 'UPDATE uploadtaxa u INNER JOIN uploadtaxa u2 ON u.sourceParentId = u2.sourceId '.
			'SET u.parentstr = u2.sciname '.
			'WHERE (u.parentstr IS NULL) AND (u.sourceParentId IS NOT NULL) AND (u2.sourceId IS NOT NULL)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u INNER JOIN uploadtaxa u2 ON u.sourceAcceptedId = u2.sourceId '.
			'SET u.acceptedstr = u2.sciname '.
			'WHERE (u.acceptedstr IS NULL) AND (u.sourceAcceptedId IS NOT NULL) AND (u2.sourceId IS NOT NULL)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		//Delete taxa where sciname can't be inserted. These are taxa where sciname already exists
		$sql = 'DELETE FROM uploadtaxa WHERE (sciname IS NULL)';
		$this->conn->query($sql);

		//Link names already in theusaurus
		$this->outputMsg('Linking names already in thesaurus... ');
		$sql = 'UPDATE uploadtaxa u INNER JOIN taxa t ON u.sciname = t.sciname SET u.tid = t.tid WHERE (u.tid IS NULL) AND (t.kingdomname = "'.$this->kingdomName.'") ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		//Update accepted name tid by matching on input sciname and then direct match to taxa table
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.acceptedstr = u2.scinameinput '.
			'SET u1.tidaccepted = u2.tid '.
			'WHERE u1.tidaccepted IS NULL AND u2.tid IS NOT NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u INNER JOIN taxa t ON u.acceptedstr = t.sciname '.
			'SET u.tidaccepted = t.tid '.
			'WHERE (u.tidaccepted IS NULL) AND (t.kingdomname = "'.$this->kingdomName.'")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		//Populate null family values
		$this->outputMsg('Populating null family values... ');
		$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.unitname1 = t.sciname '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'SET ut.family = ts.family '.
			'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (t.kingdomname = "'.$this->kingdomName.'") AND (ut.rankid > 140) AND (t.rankid = 180) AND (ts.family IS NOT NULL) AND (ut.family IS NULL)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.sourceParentId = u2.sourceId '.
			'SET u1.family = u2.sciname '.
			'WHERE u2.sourceId IS NOT NULL AND u1.sourceParentId IS NOT NULL AND u2.rankid = 140 AND u1.family IS NULL ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.unitname1 = u2.sciname '.
			'SET u1.family = u2.family '.
			'WHERE u1.family IS NULL AND u2.family IS NOT NULL ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.sourceAcceptedId = u2.sourceId '.
			'SET u1.family = u2.family '.
			'WHERE u1.sourceAcceptedId IS NOT NULL AND  u2.sourceId IS NOT NULL AND u1.family IS NULL AND u2.family IS NOT NULL ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.unitname1 = u2.sciname '.
			'INNER JOIN uploadtaxa u3 ON u2.sourceParentId = u3.sourceId '.
			'SET u1.family = u3.sciname '.
			'WHERE u2.sourceParentId IS NOT NULL AND u3.sourceId IS NOT NULL '.
			'AND u1.family IS NULL AND u1.rankid > 140 AND u2.rankid = 180 AND u3.rankid = 140';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u0 INNER JOIN uploadtaxa u1 ON u0.sourceAcceptedId = u1.sourceid '.
			'SET u0.family = u1.family '.
			'WHERE u0.sourceParentId IS NOT NULL AND u1.sourceId IS NOT NULL '.
			'AND u0.family IS NULL AND u0.rankid > 140 AND u1.family IS NOT NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u0 INNER JOIN uploadtaxa u1 ON u0.scinameinput = u1.acceptedstr '.
			'SET u0.family = u1.family '.
			'WHERE u0.family IS NULL AND u0.rankid > 140 AND u1.family IS NOT NULL';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET family = NULL WHERE family = ""';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		$this->outputMsg('Set null author values... ');
		$sql = 'UPDATE IGNORE uploadtaxa '.
			'SET author = TRIM(SUBSTRING(scinameinput,LENGTH(sciname)+1)) '.
			'WHERE (author IS NULL) AND (rankid <= 220) AND (LENGTH(scinameinput) > (LENGTH(sciname)+2))';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR (1): '.$this->conn->error,1);
		}
		$sql = 'UPDATE IGNORE uploadtaxa '.
			'SET author = TRIM(SUBSTRING(scinameinput,LOCATE(unitind3,scinameinput)+LENGTH(CONCAT_WS(" ",unitind3,unitname3)))) '.
			'WHERE (author IS NULL) AND rankid > 220 AND (LENGTH(scinameinput) > (LENGTH(sciname)+2))';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR (2): '.$this->conn->error,1);
		}
		$sql = 'UPDATE IGNORE uploadtaxa SET author = NULL WHERE author = ""';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR (3): '.$this->conn->error,1);
		}

		$this->outputMsg('Populating and mapping parent taxon... ');
		$sql = 'UPDATE uploadtaxa '.
			'SET parentstr = CONCAT_WS(" ", unitname1, unitname2) '.
			'WHERE ((parentstr IS NULL) OR (parentstr LIKE "PENDING:%")) AND (rankid > 220)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET parentstr = unitname1 '.
			'WHERE ((parentstr IS NULL) OR (parentstr LIKE "PENDING:%")) AND (rankid = 220)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET parentstr = family '.
			'WHERE ((parentstr IS NULL) OR (parentstr LIKE "PENDING:%")) AND (rankid = 180)';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa SET parentstr = SUBSTRING(parentstr,9) '.
			'WHERE (parentstr LIKE "PENDING:%")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}
		$sql = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.sourceAcceptedID = u2.sourceId '.
			'SET u1.sourceParentId = u2.sourceParentId, u1.parentStr = u2.parentStr '.
			'WHERE (u1.sourceParentId IS NULL) AND (u1.sourceAcceptedID IS NOT NULL) AND (u2.sourceParentId IS NOT NULL) AND (u1.rankid < 220) ';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname SET parenttid = t.tid WHERE (parenttid IS NULL) AND (t.kingdomname = "'.$this->kingdomName.'")';
		if(!$this->conn->query($sql)){
			$this->outputMsg('ERROR: '.$this->conn->error,1);
		}

		//Load into uploadtaxa parents of infrasp not yet in taxa table
		$this->outputMsg('Add parents that are not yet in uploadtaxa table... ');
		$sql = 'INSERT IGNORE INTO uploadtaxa(scinameinput, SciName, family, RankId, UnitName1, UnitName2, parentstr, Source) '.
			'SELECT DISTINCT ut.parentstr, ut.parentstr, ut.family, 220 as r, ut.unitname1, ut.unitname2, ut.unitname1, ut.source '.
			'FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname '.
			'WHERE (ut.parentstr <> "") AND (ut.parentstr IS NOT NULL) AND (ut.parenttid IS NULL) AND (ut.rankid > 220) AND (ut2.sciname IS NULL) ';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
			'SET up.parenttid = t.tid '.
			'WHERE (up.parenttid IS NULL) AND (t.kingdomname = "'.$this->kingdomName.'")';
		$this->conn->query($sql);

		//Load into uploadtaxa parents of species not yet in taxa table
		$sql = 'INSERT IGNORE INTO uploadtaxa (scinameinput, SciName, family, RankId, UnitName1, parentstr, Source) '.
			'SELECT DISTINCT ut.parentstr, ut.parentstr, ut.family, 180 as r, ut.unitname1, ut.family, ut.source '.
			'FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname '.
			'WHERE ut.parentstr <> "" AND ut.parentstr IS NOT NULL AND ut.parenttid IS NULL AND ut.family IS NOT NULL AND ut.rankid = 220 AND ut2.sciname IS NULL';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadtaxa up LEFT JOIN taxa t ON up.parentstr = t.sciname '.
			'SET up.parenttid = t.tid '.
			'WHERE ISNULL(up.parenttid) AND (t.kingdomname = "'.$this->kingdomName.'")';
		$this->conn->query($sql);

		//Set acceptance to 0 where sciname <> acceptedstr
		$sql = 'UPDATE uploadtaxa SET acceptance = 0 WHERE (acceptedstr IS NOT NULL) AND (sciname IS NOT NULL) AND (sciname <> acceptedstr)';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadtaxa SET acceptance = 1 WHERE (acceptedstr IS NULL) AND (TidAccepted IS NULL)';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadtaxa SET acceptance = 1 WHERE (sciname IS NULL) AND (sciname = acceptedstr)';
		$this->conn->query($sql);
		$this->outputMsg('Done processing taxa');
	}

	public function analysisUpload(){
		$retArr = array();
		//Get total number
		$sql1 = 'SELECT count(*) as cnt FROM uploadtaxa';
		$rs1 = $this->conn->query($sql1);
		while($r1 = $rs1->fetch_object()){
			$this->statArr['total'] = $r1->cnt;
		}
		$rs1->free();

		//Get number matching existing taxa and number of new
		$sql2 = 'SELECT count(*) as cnt FROM uploadtaxa WHERE tid IS NOT NULL';
		$rs2 = $this->conn->query($sql2);
		while($r2 = $rs2->fetch_object()){
			$this->statArr['exist'] = $r2->cnt;
			$this->statArr['new'] = $this->statArr['total'] - $this->statArr['exist'];
		}
		$rs2->free();

		//Get acceptance count
		$sql3 = 'SELECT acceptance, count(*) AS cnt FROM uploadtaxa GROUP BY acceptance';
		$rs3 = $this->conn->query($sql3);
		while($r3 = $rs3->fetch_object()){
			if($r3->acceptance == 0) $this->statArr['nonaccepted'] = $r3->cnt;
			if($r3->acceptance == 1) $this->statArr['accepted'] = $r3->cnt;
		}
		$rs3->free();

		//Tag bad taxa that didn't parse correctly
		$sql4 = 'UPDATE uploadtaxa SET ErrorStatus = "FAILED: Unable to parse input scientific name" WHERE sciname IS NULL';
		if(!$this->conn->query($sql4)){
			$this->outputMsg('ERROR non-parsed names: '.$this->conn->error,1);
		}

		//Tag non-accepted taxa linked to non-existent taxon
		$sql5 = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.acceptedStr = u2.sciname '.
			'SET u1.ErrorStatus = "FAILED: Non-accepted taxa linked to taxon not in input list nor thesaurus; add accepted names to list as a new records to resolve" '.
			'WHERE (u1.tid IS NULL) AND (u1.acceptance = 0) AND (u1.tidAccepted IS NULL) AND (u2.sciname IS NULL)';
		if(!$this->conn->query($sql5)){
			$this->outputMsg('FAILED: Non-accepted taxa linked to taxon not in thesaurus nor input list: '.$this->conn->error,1);
		}

		//Tag non-accepted linked to other non-accepted taxa
		$sql6a = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.acceptedStr = u2.sciname '.
			'SET u1.ErrorStatus = "FAILED: Non-accepted linked to another non-accepted taxon" '.
			'WHERE (u1.tid IS NULL) AND (u1.acceptance = 0) AND (u2.acceptance = 0)';
		if(!$this->conn->query($sql6a)){
			$this->outputMsg('ERROR non-accepted linked to non-accepted (#1): '.$this->conn->error,1);
		}
		$sql6b = 'UPDATE uploadtaxa u INNER JOIN taxstatus ts ON u.tidaccepted = ts.tid '.
			'SET u.ErrorStatus = "FAILED: Non-accepted linked to another non-accepted taxon already within database" '.
			'WHERE (u.tid IS NULL) AND (ts.taxauthid = '.$this->taxAuthId.') AND (u.acceptance = 0) AND (ts.tid <> ts.tidaccepted)';
		if(!$this->conn->query($sql6b)){
			$this->outputMsg('ERROR non-accepted linked to non-accepted (#2): '.$this->conn->error,1);
		}

		//Tag taxa with non-existent parents (parent not being added and does not exist within database
		$sql6 = 'UPDATE uploadtaxa u1 LEFT JOIN uploadtaxa u2 ON u1.parentStr = u2.sciname '.
			'SET u1.ErrorStatus = "FAILED: Taxa with non-existent parent taxon" '.
			'WHERE (u1.RankId > 10) AND (u1.tid IS NULL) AND (u1.parentTid IS NULL) AND (u2.sciname IS NULL) ';
		if(!$this->conn->query($sql6)){
			$this->outputMsg('ERROR taxa with non-existent parent taxon: '.$this->conn->error,1);
		}

		//Tag taxa with a FAILED parent
		$loopCnt = 0;
		do{
			$sql8 = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.parentStr = u2.sciname '.
				'SET u1.ErrorStatus = "FAILED: Taxa linked to a FAILED parent" '.
				'WHERE (u1.tid IS NULL) AND (u2.ErrorStatus LIKE "FAILED%") AND ((u1.ErrorStatus IS NULL) OR (u1.ErrorStatus NOT LIKE "FAILED%"))';
			if(!$this->conn->query($sql8)){
				$this->outputMsg('ERROR taxa with FAILED parents: '.$this->conn->error,1);
				break;
			}
			$loopCnt++;
			if($loopCnt > 20){
				$this->outputMsg('ERROR looping: too many parent loops',1);
				break;
			}
		}while($this->conn->affected_rows);

		//Tag non-accepted taxa linked to FAILED taxon
		$sql9 = 'UPDATE uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.acceptedStr = u2.sciname '.
			'SET u1.ErrorStatus = "FAILED: Non-accepted taxa linked to a FAILED name" '.
			'WHERE (u1.tid IS NULL) AND (u1.acceptance = 0) AND (u1.ErrorStatus NOT LIKE "FAILED%") AND (u2.ErrorStatus LIKE "FAILED%")';
		if(!$this->conn->query($sql9)){
			$this->outputMsg('ERROR non-accepeted linked to FAILED name: '.$this->conn->error,1);
		}

		//Get bad counts
		$sql = 'SELECT errorstatus, count(*) as cnt FROM uploadtaxa WHERE ErrorStatus LIKE "FAILED%" GROUP BY ErrorStatus';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->statArr['bad'][substr($r->errorstatus,7)] = $r->cnt;
		}
		$rs->free();

		return $retArr;
	}

	public function transferUpload(){
		$this->outputMsg('Starting data transfer...');
		if(!$this->kingdomTid){
			$this->outputMsg('ABORT: kingdom identifier (TID) failed to populate (function: transferUpload; kingdom: '.$this->kingdomName.')');
			return false;
		}
		//Prime table with kingdoms that are not yet in table
		$sql = 'INSERT INTO taxa(kingdomName, SciName, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes, modifiedUid, modifiedTimeStamp) '.
			'SELECT DISTINCT "'.$this->kingdomName.'", SciName, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, IFNULL(Author,"") AS author, Source, Notes, '.$GLOBALS['SYMB_UID'].' as uid, now() '.
			'FROM uploadtaxa '.
			'WHERE (TID IS NULL) AND (rankid = 10)';
		if($this->conn->query($sql)){
			$sql = 'INSERT INTO taxstatus(tid, tidaccepted, taxauthid, parenttid, modifiedUid) '.
				'SELECT DISTINCT t.tid, t.tid, '.$this->taxAuthId.', t.tid, '.$GLOBALS['SYMB_UID'].' FROM taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid WHERE (t.rankid = 10) AND (ts.tid IS NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}
		}
		else $this->outputMsg('ERROR: '.$this->conn->error,1);

		//Loop through and transfer taxa to taxa table
		$loopCnt = 0;
		do{
			$this->outputMsg('Starting loop '.$loopCnt);
			$this->outputMsg('Transferring taxa to taxon table... ',1);
			$sql = 'INSERT IGNORE INTO taxa(kingdomName, SciName, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes) '.
				'SELECT DISTINCT "'.$this->kingdomName.'", SciName, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes '.
				'FROM uploadtaxa '.
				'WHERE (tid IS NULL) AND (parenttid IS NOT NULL) AND (rankid IS NOT NULL) AND (ErrorStatus IS NULL) '.
				'ORDER BY RankId ASC ';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR loading taxa: '.$this->conn->error,1);
			}

			$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.sciname = t.sciname '.
				'SET ut.tid = t.tid '.
				'WHERE (ut.tid IS NULL) AND (t.kingdomname = "'.$this->kingdomName.'")';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR populating TIDs: '.$this->conn->error,1);
			}

			$sql = 'UPDATE uploadtaxa ut1 INNER JOIN uploadtaxa ut2 ON ut1.sourceacceptedid = ut2.sourceid '.
				'INNER JOIN taxa t ON ut2.sciname = t.sciname '.
				'SET ut1.tidaccepted = t.tid '.
				'WHERE (ut1.acceptance = 0) AND (ut1.tidaccepted IS NULL) AND (ut1.sourceacceptedid IS NOT NULL) AND (ut2.sourceid IS NOT NULL) AND (t.kingdomname = "'.$this->kingdomName.'")';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}

			$sql = 'UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.acceptedstr = t.sciname '.
				'SET ut.tidaccepted = t.tid '.
				'WHERE (ut.acceptance = 0) AND (ut.tidaccepted IS NULL) AND (ut.acceptedstr IS NOT NULL) AND (t.kingdomname = "'.$this->kingdomName.'")';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}

			$sql = 'UPDATE uploadtaxa SET tidaccepted = tid WHERE (acceptance = 1) AND (tidaccepted IS NULL) AND (tid IS NOT NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR: '.$this->conn->error,1);
			}

			$this->outputMsg('Create parent and accepted links... ',1);
			$sql = 'INSERT IGNORE INTO taxstatus(TID, TidAccepted, taxauthid, ParentTid, Family, UnacceptabilityReason) '.
				'SELECT DISTINCT TID, TidAccepted, '.$this->taxAuthId.', ParentTid, Family, UnacceptabilityReason '.
				'FROM uploadtaxa '.
				'WHERE (tid IS NOT NULL) AND (TidAccepted IS NOT NULL) AND (parenttid IS NOT NULL)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR creating taxstatus links: '.$this->conn->error,1);
			}

			$this->outputMsg('Transferring vernaculars for new taxa... ',1);
			//Covers taxa with newly assigned tids just before they are removed
			$this->transferVernaculars(1);

			$this->outputMsg('Preparing for next round... ',1);
			$sql = 'DELETE FROM uploadtaxa WHERE (tid IS NOT NULL) AND (tidaccepted IS NOT NULL) AND (parenttid IS NOT NULL)';
			$this->conn->query($sql);
			if(!$this->conn->affected_rows) break;

			//Update parentTids
			$sql = 'UPDATE uploadtaxa ut1 INNER JOIN uploadtaxa ut2 ON ut1.sourceparentid = ut2.sourceid '.
				'INNER JOIN taxa t ON ut2.sciname = t.sciname '.
				'SET ut1.parenttid = t.tid '.
				'WHERE (ut1.parenttid IS NULL) AND (ut1.sourceparentid IS NOT NULL) AND (ut2.sourceid IS NOT NULL) AND (t.kingdomname = "'.$this->kingdomName.'")';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR populating parent TIDs based on sourceIDs: '.$this->conn->error,1);
			}

			$sql = 'UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname '.
				'SET up.parenttid = t.tid '.
				'WHERE (up.parenttid IS NULL) AND (t.kingdomname = "'.$this->kingdomName.'")';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR populating parent TIDs: '.$this->conn->error,1);
			}
			$loopCnt++;
		}while($loopCnt < 30);

		$this->outputMsg('House cleaning... ');
		TaxonomyUtilities::buildHierarchyEnumTree($this->conn, $this->taxAuthId);

		//Update occurrences with new tids
		$occurMaintenance = new OccurrenceMaintenance($this->conn);
		$occurMaintenance->setCollidStr($this->collid);
		$occurMaintenance->generalOccurrenceCleaning();
		$occurMaintenance->batchUpdateGeoreferenceIndex();
	}

	private function transferVernaculars($secondRound = 0){
		if($this->langArr === false) $this->setLangArr();
		$sql = 'SELECT tid, vernacular, vernlang, source FROM uploadtaxa WHERE tid IS NOT NULL AND Vernacular IS NOT NULL ';
		if($secondRound) $sql .= 'AND tidaccepted IS NOT NULL';
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()){
			$vernArr = array();
			$vernStr = $r->vernacular;
			if(strpos($vernStr,"\t")) {
				$vernArr = explode("\t",$vernStr);
			}
			elseif(strpos($vernStr,"|")){
				$vernArr = explode("|",$vernStr);
			}
			elseif(strpos($vernStr,";")){
				$vernArr = explode(";",$vernStr);
			}
			elseif(strpos($vernStr,",")){
				$vernArr = explode(",",$vernStr);
			}
			else{
				$vernArr[] = $vernStr;
			}
			$langId = 1;
			if(array_key_exists($r->vernlang,$this->langArr)) $langId = $this->langArr[$r->vernlang];
			foreach($vernArr as $vStr){
				if($vStr){
					$sqlInsert = 'INSERT INTO taxavernaculars(tid, VernacularName, langid, source) VALUES('.$r->tid.',"'.$this->cleanInStr($vStr).'",'.$langId.','.($r->source?'"'.$r->source.'"':'NULL').')';
					if(!$this->conn->query($sqlInsert)){
						if(substr($this->conn->error,0,9) != 'Duplicate') $this->outputMsg('ERROR: '.$this->conn->error,1);
					}
				}
			}
		}
	}

	public function exportUploadTaxa(){
		$fieldArr = array('tid','family','scinameInput','sciname','author','rankId','unitInd1','unitName1','unitInd2','unitName2',
			'unitInd3','unitName3,parentTid','parentStr','acceptance','tidAccepted','acceptedStr','unacceptabilityReason',
			'securityStatus','source','notes','vernacular','vernlang','sourceId','sourceAcceptedId','sourceParentId','errorStatus');
		$fileName = 'taxaUpload_'.time().'.csv';
		header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ('Content-Disposition: attachment; filename="'.$fileName.'"');
		$sql = 'SELECT '.implode(',',$fieldArr).' FROM uploadtaxa ';
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$out = fopen('php://output', 'w');
			echo implode(',',$fieldArr)."\n";
			while($r = $rs->fetch_assoc()){
				fputcsv($out, $r);
			}
			fclose($out);
		}
		else{
			echo "Recordset is empty.\n";
		}
		$rs->free();
	}

	//Misc data retrival functions
	private function setTaxonUnitArr(){
		if($this->kingdomName){
			$sql = 'SELECT rankid, rankname FROM taxonunits WHERE (kingdomname = "'.$this->kingdomName.'") ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->taxonUnitArr[$r->rankid] = strtolower($r->rankname);
			}
			$rs->free();
			if(!$this->taxonUnitArr){
				$sql = 'SELECT DISTINCT rankid, rankname FROM taxonunits';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$this->taxonUnitArr[$r->rankid] = strtolower($r->rankname);
				}
				$rs->free();
			}
		}
	}

	private function setUploadCount(){
		$rs = $this->conn->query('SELECT count(*) as cnt FROM uploadtaxa');
		while($r = $rs->fetch_object()){
			$this->statArr['upload'] = $r->cnt;
		}
		$rs->free();
	}

	public function getTargetArr(){
		$retArr = $this->getUploadTaxaFieldArr();
		unset($retArr['unitind1']);
		unset($retArr['unitind2']);
		$retArr['unitname1'] = 'genus';
		//unset($retArr['genus']);
		$retArr['unitname2'] = 'specificepithet';
		$retArr['unitind3'] = 'taxonrank';
		$retArr['unitname3'] = 'infraspecificepithet';
		if(!$this->taxonUnitArr) $this->setTaxonUnitArr();
		foreach($this->taxonUnitArr as $rankid => $rankName){
			if($rankName != 'genus' && $rankid < 220) $retArr[$rankName] = $rankName;
		}
		return $retArr;
	}

	private function getUploadTaxaFieldArr(){
		//Get metadata
		$targetArr = array();
		$rs = $this->conn->query('SHOW COLUMNS FROM uploadtaxa');
		while($row = $rs->fetch_object()){
			$field = strtolower($row->Field);
			if($field != 'tid' && $field != 'tidaccepted' && $field != 'parenttid'){
				$targetArr[$field] = $field;
			}
		}
		$rs->free();
		return $targetArr;
	}

	public function getTaxonRankArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT rankid, rankname FROM taxonunits ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->rankid] = $r->rankname;
		}
		ksort($retArr);
		return $retArr;
	}

	public function getSourceArr(){
		$sourceArr = array();
		if(($fh = fopen($this->uploadTargetPath.$this->uploadFileName,'r')) !== FALSE){
			$headerArr = fgetcsv($fh);
			foreach($headerArr as $field){
				$fieldStr = strtolower(TRIM($field));
				if($fieldStr){
					$sourceArr[] = $fieldStr;
				}
				else{
					break;
				}
			}
		}
		else{
			echo 'ERROR thrown opening input file: '.$this->uploadTargetPath.$this->uploadFileName.'<br/>';
			if(!is_writable($this->uploadTargetPath)) echo '<b>Target upload path is not writable. File permissions need to be adjusted</b>';
			exit;
		}
		return $sourceArr;
	}

	public function getTaxAuthorityArr(){
		$retArr = array();
		if($rs = $this->conn->query('SELECT taxauthid, name FROM taxauthority')){
			while($r = $rs->fetch_object()){
				$retArr[$r->taxauthid] = $r->name;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getTaxAuthorityName(){
		$retStr = '';
		if($this->taxAuthId){
			if($rs = $this->conn->query('SELECT taxauthid, name FROM taxauthority WHERE (taxauthid = '.$this->taxAuthId.')')){
				while($r = $rs->fetch_object()){
					$retStr = $r->name;
				}
				$rs->free();
			}
		}
		return $retStr;
	}

	public function getKingdomArr(){
		$retArr = array();
		$rs = $this->conn->query('SELECT tid, sciname FROM taxa WHERE rankid = 10 ORDER BY sciname');
		while($r = $rs->fetch_object()){
			$retArr[$r->tid] = $r->sciname;
		}
		$rs->free();
		return $retArr;
	}

	private function setLangArr(){
		if($this->langArr === false){
			$this->langArr = array();
			$sql = 'SELECT langid, langname, iso639_1 FROM adminlanguages';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->langArr[$r->langname] = $r->langid;
				$this->langArr[$r->iso639_1] = $r->langid;
			}
			$rs->free();
		}
	}

	public function getTaxonomicResourceList(){
		$taArr = array('worms'=>'World Register of Marine Species','col'=>'Catalog of Life');
		$taArr = array('col'=>'Catalog of Life', 'worms'=>'World Register of Marine Species', 'bryonames' => 'The Bryophyte Nomenclator', 'fdex'=>'Index Fungorum via F-Dex', 'tropicos'=>'TROPICOS', 'eol'=>'Encyclopedia of Life');
		if(!isset($GLOBALS['TAXONOMIC_AUTHORITIES'])) return $taArr;
		return array_intersect_key($taArr,array_change_key_case($GLOBALS['TAXONOMIC_AUTHORITIES']));
	}

	//Setters and getters
	private function setUploadTargetPath(){
		$tPath = '';
		if(!$tPath && isset($GLOBALS["TEMP_DIR_ROOT"])){
			$tPath = $GLOBALS['TEMP_DIR_ROOT'];
			if(substr($tPath,-1) != '/') $tPath .= "/";
			if(file_exists($tPath.'downloads')) $tPath .= 'downloads/';
		}
		elseif(!$tPath){
			$tPath = ini_get('upload_tmp_dir');
		}
		if(!$tPath){
			$tPath = $GLOBALS['SERVER_ROOT'];
			if(substr($tPath,-1) != '/') $tPath .= "/";
			$tPath .= "temp/downloads/";
		}
		if(substr($tPath,-1) != '/') $tPath .= '/';
		$this->uploadTargetPath = $tPath;
	}

	public function setFileName($fName){
		$this->uploadFileName = $fName;
	}

	public function getFileName(){
		return $this->uploadFileName;
	}

	public function setTaxaAuthId($id){
		if(is_numeric($id)){
			$this->taxAuthId = $id;
		}
	}

	public function setKingdomName($str){
		if(preg_match('/^[a-zA-Z]+$/', $str)){
			$this->kingdomName = $str;
			$sql = 'SELECT tid FROM taxa WHERE sciname = "'.$this->cleanInStr($this->kingdomName).'" AND rankid = 10';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->kingdomTid = $r->tid;
			}
			$rs->free();

			//Batch populate NULL kingdomname values within taxa table
			$sql = 'UPDATE IGNORE taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid
				INNER JOIN taxa k ON e.parenttid = k.tid
				SET t.kingdomname = k.sciname
				WHERE t.kingdomname IS NULL AND k.rankid = 10;';
			if(!$this->conn->query($sql)){
				$this->outputMsg('ERROR updating kingdomName within taxa table: '.$this->conn->error,1);
			}
		}
	}

	public function getStatArr(){
		return $this->statArr;
	}

	public function getErrorStr(){
		return $this->errorStr;
	}

	public function setVerboseMode($vMode){
		global $SERVER_ROOT;
		if(is_numeric($vMode)){
			$this->verboseMode = $vMode;
			if($this->verboseMode == 2){
				//Create log File
				$logPath = $SERVER_ROOT;
				if(substr($SERVER_ROOT,-1) != '/' && substr($SERVER_ROOT,-1) != '\\') $logPath .= '/';
				$logPath .= "content/logs/taxaloader_".date('Ymd').".log";
				$this->logFH = fopen($logPath, 'a');
				fwrite($this->logFH,"Start time: ".date('Y-m-d h:i:s A')."\n");
			}
		}
	}

	//Misc functions
	private function outputMsg($str, $indent = 0){
		if($this->verboseMode > 0 || substr($str,0,5) == 'ERROR'){
			echo '<li style="margin-left:'.(10*$indent).'px;'.(substr($str,0,5)=='ERROR'?'color:red':'').'">'.$str.'</li>';
			@ob_flush();
			@flush();
		}
		if($this->verboseMode == 2){
			if($this->logFH) fwrite($this->logFH,($indent?str_repeat("\t",$indent):'').strip_tags($str)."\n");
		}
	}

	private function cleanInArr(&$inArr){
		foreach($inArr as $k => $v){
			$inArr[$k] = $this->cleanInStr($v);
		}
	}

	private function cleanInStr($str){
		$newStr = TRIM($str);
		$newStr = preg_REPLACE('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private function encodeArr(&$inArr){
		foreach($inArr as $k => $v){
			$inArr[$k] = $this->encodeString($v);
		}
	}

	private function encodeString($inStr){
		global $CHARSET;
		$retStr = $inStr;
		//Get rid of UTF-8 curly smart quotes and dashes
		$badwordchars=array("\xe2\x80\x98", // left single quote
							"\xe2\x80\x99", // right single quote
							"\xe2\x80\x9c", // left double quote
							"\xe2\x80\x9d", // right double quote
							"\xe2\x80\x94", // em dash
							"\xe2\x80\xa6" // elipses
		);
		$fixedwordchars=array("'", "'", '"', '"', '-', '...');
		$inStr = str_REPLACE($badwordchars, $fixedwordchars, $inStr);

		if($inStr){
			if(strtolower($CHARSET) == "utf-8" || strtolower($CHARSET) == "utf8"){
				//$this->outputMsg($inStr.': '.mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true);
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif(strtolower($CHARSET) == "iso-8859-1"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
 		}
		return $retStr;
	}
}
?>