<?php
require_once($SERVER_ROOT.'/config/dbconnection.php');
require_once($SERVER_ROOT.'/classes/OccurrenceMaintenance.php');
include_once($SERVER_ROOT.'/classes/UuidFactory.php');

class ImageProcessor {

	private $conn;
	private $collid = 0;
	private $sprid;
	private $collArr;
	private $matchCatalogNumber = true;
	private $matchOtherCatalogNumbers = false;
	private $createNewRecord = false;

	private $logMode = 0;		//0 = silent, 1 = html, 2 = log file, 3 = both html & log
	private $logFH;
	private $destructConn = true;

	function __construct($con = null){
		ini_set('auto_detect_line_endings', true);
		if($con){
			//Inherits connection from another class
			$this->conn = $con;
			$this->destructConn = false;
		}
		else{
			$this->conn = MySQLiConnectionFactory::getCon('write');
			if($this->conn === false) exit("ABORT: Image upload aborted: Unable to establish connection to database");
		}
	}

	function __destruct(){
		if($this->destructConn && !($this->conn === false)) $this->conn->close();
		if($this->logFH){
			fwrite($this->logFH,"\n\n");
			fclose($this->logFH);
		}
	}

	private function initProcessor($logDir){
		//Close log file
		if($this->logFH) fclose($this->logFH);
		if($this->logMode > 1){
			$logPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) == '/'?'':'/').'content/logs/';
			if($logDir) $logPath .= $logDir.'/';
			if(!file_exists($logPath)) mkdir($logPath);
			if(file_exists($logPath)){
				$logFile = $logPath.$this->collid.'_'.$this->collArr['instcode'];
				if($this->collArr['collcode']) $logFile .= '-'.$this->collArr['collcode'];
				$logFile .= '_'.date('Y-m-d').'.log';
				$this->logFH = fopen($logFile, 'a');
			}
			else{
				echo 'ERROR creating Log file; path not found: '.$logPath."\n";
			}
		}
	}

	//CyVerse functions
	public function batchProcessCyVerseImages(){
		//Start processing images for each day from the start date to the current date
		$status = false;
		if($this->logMode == 1) echo '<ul>';
		$processList = array();
		$sql = 'SELECT collid, speckeypattern, source FROM specprocessorprojects WHERE (title = "IPlant Image Processing") ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->collid = $r->collid;
			$this->setLogMode(2);
			$status = $this->processCyVerseImages();
			if($status){
				$processList[] = $this->collid;
			}
		}
		$rs->free();
		if($status) $this->cleanHouse($processList);
		$this->logOrEcho("Image upload process finished! (".date('Y-m-d h:i:s A').") \n");
		if($this->logMode == 1) echo '</ul>';
	}

	public function processCyVerseImages($pmTerm, $postArr){
		set_time_limit(1000);
		$lastRunDate = $postArr['startdate'];
		$CyVerseSourcePath = (array_key_exists('sourcepath', $postArr)?$postArr['sourcepath']:'');
		$this->matchCatalogNumber = (array_key_exists('matchcatalognumber', $postArr)?true:false);
		$this->matchOtherCatalogNumbers = (array_key_exists('matchothercatalognumbers', $postArr)?true:false);
		if($this->collid){
			$iCyVerseDataUrl = 'https://bisque.cyverse.org/data_service/';
			$iCyVerseImageUrl = 'https://bisque.cyverse.org/image_service/image/';

			if(!$CyVerseSourcePath && array_key_exists('IPLANT_IMAGE_IMPORT_PATH', $GLOBALS)) $CyVerseSourcePath = $GLOBALS['IPLANT_IMAGE_IMPORT_PATH'];
			if($CyVerseSourcePath){
				if(strpos($CyVerseSourcePath, '--INSTITUTION_CODE--')) $CyVerseSourcePath = str_replace('--INSTITUTION_CODE--', $this->collArr['instcode'], $CyVerseSourcePath);
				if(strpos($CyVerseSourcePath, '--COLLECTION_CODE--')) $CyVerseSourcePath = str_replace('--COLLECTION_CODE--', $this->collArr['collcode'], $CyVerseSourcePath);
			}
			else{
				echo '<div style="color:red">CyVerse image import path (IPLANT_IMAGE_IMPORT_PATH) not set within symbini configuration file</div>';
				return false;
			}
			$this->initProcessor('cyverse');
			$collStr = $this->collArr['instcode'].($this->collArr['collcode']?'-'.$this->collArr['collcode']:'');
			$this->logOrEcho('Starting image processing: '.$collStr.' ('.date('Y-m-d h:i:s A').')');

			if(!$pmTerm){
				$this->logOrEcho('COLLECTION SKIPPED: Pattern matching term is NULL');
				return false;
			}
			if(substr($pmTerm,0,1) != '/' || substr($pmTerm,-1) != '/'){
				$this->logOrEcho('COLLECTION SKIPPED: Regular Expression term illegal due to missing forward slashes: '.$pmTerm);
				return false;
			}
			if(!strpos($pmTerm,'(') || !strpos($pmTerm,')')){
				$this->logOrEcho('COLLECTION SKIPPED: Regular Expression term illegal due to missing capture term: '.$pmTerm);
				return false;
			}
			$cyVerseBasePath = $iCyVerseDataUrl.'image?value=*'.$CyVerseSourcePath.'*&tag_query=upload_datetime:';
			$this->logOrEcho('CyVerse base path used: '.$cyVerseBasePath);

			//Get start date
			if(!$lastRunDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$lastRunDate)) $lastRunDate = '2019-05-01';
			while(strtotime($lastRunDate) < strtotime('now')){
				$url = $cyVerseBasePath.$lastRunDate.'*';
				$contents = @file_get_contents($url);
				//check if response is received from CyVerse
				if(!empty($http_response_header)) {
					$result = $http_response_header;
					//check if response is 200
					if(strpos($result[0],'200') !== false) {
						$xml = '';
						try {
							$xml = new SimpleXMLElement($contents);
						}
						catch (Exception $e) {
							$this->logOrEcho('ABORTED: bad content received from CyVerse: '.$contents);
							//return false;
						}
						if(count($xml->image)){
							$this->logOrEcho('Starting to process '.count($xml->image).' images uploaded on '.$lastRunDate,1);
							$cnt = 0;
							foreach($xml->image as $i){
								$fileName = $i['name'];
								if(preg_match($pmTerm,$fileName,$matchArr)){
									if(array_key_exists(1,$matchArr) && $matchArr[1]){
										$targetIdentifier = $matchArr[1];
										if($postArr['patternreplace']) $targetIdentifier = preg_replace($postArr['patternreplace'],$postArr['replacestr'],$targetIdentifier);
										$guid = $i['resource_uniq'];
										$occid = $this->getPrimaryKey($targetIdentifier,$guid,$fileName);
										$baseUrl = $iCyVerseImageUrl.$guid;
										$fieldArr = array();
										$fieldArr['url'] = $baseUrl.'/resize:1250/format:jpeg';
										$fieldArr['thumbnailurl'] = $baseUrl.'/thumbnail:200,200';
										$fieldArr['originalurl'] = $baseUrl.'/resize:4000/format:jpeg';
										$fieldArr['archiveurl'] = $baseUrl;
										$fieldArr['owner'] = $this->collArr['collname'];
										$fieldArr['sourceIdentifier'] = $guid.'; filename: '.$fileName;
										//$fieldArr['url'] = $baseUrl.'?resize=1250&format=jpeg';
										//$fieldArr['thumbnailurl'] = $baseUrl.'?thumbnail=200,200';
										//$fieldArr['originalurl'] = $baseUrl.'?resize=4000&format=jpeg';
										if($occid){
											if(is_numeric($occid)) $this->insertImage($occid,$fieldArr);
											elseif(substr($occid,0,6) == 'imgid-') $this->updateImage(substr($occid,6),$fieldArr);
										}
									}
									else $this->logOrEcho("NOTICE: File skipped, unable to extract specimen identifier (".$fileName.")",2);
								}
								$cnt++;
							}
						}
						else $this->logOrEcho('No images were loaded on this date: '.$lastRunDate,1);
					}
					else{
						if(strpos($result[0],'503')){
							$this->logOrEcho('ERROR: CyVerse Bisque system appears to be offline',1);
							$this->logOrEcho('Response code: '.$result[0],2);
							$this->logOrEcho('FAILED URL: <a href="'.$url.'" target="_blank">'.$url.'</a>',2);
							return false;
						}
						else{
							$this->logOrEcho("ERROR: bad response status code returned for $url (code: $result[0])",1);
						}
					}
				}
				else{
					$this->logOrEcho("ERROR: failed to obtain response from CyVerse (".$url.")",1);
					return false;
				}
				$this->updateLastRunDate($lastRunDate);
				$lastRunDate = date('Y-m-d', strtotime($lastRunDate. ' + 1 days'));
			}
			$this->cleanHouse(array($this->collid));
			$this->logOrEcho("Image upload process finished! (".date('Y-m-d h:i:s A').") \n");
		}
		return true;
	}

	//Image file upload
	public function loadImageFile(){
		$inFileName = basename($_FILES['uploadfile']['name']);
		$ext = substr(strrchr($inFileName, '.'), 1);
		$fileName = 'imageMappingFile_'.time();
		$fullPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) != '/'?'/':'').'temp/data/';
		if(move_uploaded_file($_FILES['uploadfile']['tmp_name'],$fullPath.$fileName.'.'.$ext)){
			if($ext == 'zip'){
				$zipFilePath = $fullPath.$fileName.'.zip';
				$ext = '';
				$zip = new ZipArchive;
				$res = $zip->open($zipFilePath);
				if($res === TRUE) {
					for($i = 0; $i < $zip->numFiles; $i++){
						$fileExt = substr(strrchr($zip->getNameIndex($i), '.'), 1);
						if($fileExt == 'csv' || $fileExt == 'txt'){
							$ext = $fileExt;
							$zip->renameIndex($i, $fileName.'.'.$ext);
							$zip->extractTo($fullPath,$fileName.'.'.$ext);
							$zip->close();
							unlink($zipFilePath);
							break;
						}
					}
				}
				else{
					echo 'failed, code:' . $res;
					return false;
				}
			}
			return $fileName.'.'.$ext;
		}
		return '';
	}

	public function getHeaderArr($fileName){
		$retArr = array();
		$fullPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) != '/'?'/':'').'temp/data/'.$fileName;
		if($fh = fopen($fullPath,'rb')){
			$headerArr = fgetcsv($fh,0,',');
			foreach($headerArr as $i => $sourceField){
				if($sourceField != 'collid') $retArr[$i] = $sourceField;
			}
			fclose($fh);
		}
		return $retArr;
	}

	public function loadFileData($postArr){
		if(isset($postArr['filename']) && isset($postArr['tf'])){
			$fieldMap = array_flip($postArr['tf']);
			$fullPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) != '/'?'/':'').'temp/data/'.$postArr['filename'];
			if($fh = fopen($fullPath,'rb')){
				$this->initProcessor('processing/imgmap');
				$this->logOrEcho('Starting to process image URLs within image mapping file '.$postArr['filename'].' ('.date('Y-m-d H:i:s').')');
				fgetcsv($fh);	//Advance one row to skipper header row
				$cnt = 1;
				while($recordArr = fgetcsv($fh)){
					$catalogNumber = (isset($fieldMap['catalognumber'])?$this->cleanInStr($recordArr[$fieldMap['catalognumber']]):'');
					$otherCatalogNumbers = (isset($fieldMap['othercatalognumbers'])?$this->cleanInStr($recordArr[$fieldMap['othercatalognumbers']]):'');
					$originalUrl = (isset($fieldMap['originalurl'])?$this->cleanInStr($recordArr[$fieldMap['originalurl']]):'');
					$url = (isset($fieldMap['url'])?$this->cleanInStr($recordArr[$fieldMap['url']]):'');
					if(!$url) $url = 'empty';
					$thumbnailUrl = (isset($fieldMap['thumbnailurl'])?$this->cleanInStr($recordArr[$fieldMap['thumbnailurl']]):'');
					$sourceUrl = (isset($fieldMap['sourceurl'])?$this->cleanInStr($recordArr[$fieldMap['sourceurl']]):'');
					if(($catalogNumber || $otherCatalogNumbers) && ($originalUrl || ($url && $url != 'empty'))){
						$this->logOrEcho('#'.$cnt.': Processing Catalog Number: '.($catalogNumber?$catalogNumber:$otherCatalogNumbers));
						$occArr = array();
						$sql = '';
						if($catalogNumber){
							$sql .= 'SELECT occid, tidinterpreted FROM omoccurrences WHERE (collid = '.$this->collid.') AND (catalognumber = "'.$catalogNumber.'")';
						}
						else{
							$sql .= 'SELECT DISTINCT o.occid, o.tidinterpreted
								FROM omoccurrences o LEFT JOIN omoccuridentifiers i ON o.occid = i.occid
								WHERE (o.collid = '.$this->collid.') AND (o.othercatalognumbers = "'.$otherCatalogNumbers.'" OR i.identifierValue = "'.$otherCatalogNumbers.'")';
						}
						$rs = $this->conn->query($sql);
						while($r = $rs->fetch_object()){
							$occArr[$r->occid] = $r->tidinterpreted;
						}
						$rs->free();
						if($occArr){
							//Check to see if image with matching filename is already linked. If so, remove and replace with new
							$origUrl = substr($originalUrl, 5);
							$baseUrl = substr($url, 5);
							foreach($occArr as $occid => $tid){
								$sql1 = 'SELECT imgid, url, originalurl, thumbnailurl FROM images WHERE (occid = '.$occid.')';
								$rs1 = $this->conn->query($sql1);
								while($r1 = $rs1->fetch_object()){
									$testOrigUrl = substr($r1->originalurl,5);
									$testBaseUrl = substr($r1->url,5);
									$replaceImg = false;
									if($testOrigUrl && $testOrigUrl == $origUrl) $replaceImg = true;
									elseif($testBaseUrl && $testBaseUrl == $baseUrl) $replaceImg = true;
									elseif($testOrigUrl && $testOrigUrl == $baseUrl) $replaceImg = true;
									elseif($testBaseUrl && $testBaseUrl == $origUrl) $replaceImg = true;
									if($replaceImg){
										$sql2 = 'UPDATE images '.
											'SET url = "'.$url.'", originalurl = "'.$originalUrl.'", thumbnailurl = '.($thumbnailUrl?'"'.$thumbnailUrl.'"':'NULL').', '.
											'sourceurl = '.($sourceUrl?'"'.$sourceUrl.'"':'NULL').' '.
											'WHERE imgid = '.$r1->imgid;
										if($this->conn->query($sql2)){
											$this->logOrEcho('Existing image replaced with new image mapping: <a href="../editor/occurrenceeditor.php?occid='.$occid.'" target="_blank">'.($catalogNumber?$catalogNumber:$otherCatalogNumbers).'</a>',1);
											//Delete physical images it previous version was mapped locally
											$this->deleteImage($r1->url);
											$this->deleteImage($r1->originalurl);
											$this->deleteImage($r1->thumbnailurl);
											unset($occArr[$occid]);
											break;
										}
										else{
											$this->logOrEcho('ERROR updating existing image record: '.$this->conn->error,1);
										}
									}
								}
								$rs1->free();
							}
						}
						else{
							if($this->createNewRecord){
								//Create new occurrence record to link image
								$sqlIns = 'INSERT INTO omoccurrences(collid,'.($catalogNumber?'catalogNumber':'otherCatalogNumbers').',processingstatus,dateentered) '.
									'VALUES('.$this->collid.',"'.($catalogNumber?$catalogNumber:$otherCatalogNumbers).'","unprocessed",now())';
								if($this->conn->query($sqlIns)){
									$occArr[$this->conn->insert_id] = 0;
									$this->logOrEcho('Unable to find record with matching '.($catalogNumber?'catalogNumber':'otherCatalogNumbers').'; new occurrence record created',1);
								}
								else{
									$this->logOrEcho('ERROR creating new occurrence record: '.$this->conn->error,1);
								}
							}
							else{
								$this->logOrEcho('SKIPPED: Unable to find record with matching '.($catalogNumber?'catalogNumber':'otherCatalogNumbers').', image not mapped',1);
							}
						}
						foreach($occArr as $occid => $tid){
							$fieldArr = array();
							$fieldArr['url'] =  $url;
							if($thumbnailUrl) $fieldArr['thumbnailurl'] =  $thumbnailUrl;
							$fieldArr['originalurl'] =  $originalUrl;
							if($sourceUrl) $fieldArr['sourceurl'] = $sourceUrl;
							if($tid) $fieldArr['tid'] =  $tid;
							if(!$this->insertImage($occid,$fieldArr)) $this->logOrEcho('ERROR loading image: '.$this->conn->error,1);
						}
					}
					$cnt++;
				}
				fclose($fh);

				$occurMain = new OccurrenceMaintenance($this->conn);

				$this->logOrEcho('Updating statistics...');
				if(!$occurMain->updateCollectionStatsBasic($this->collid)){
					$errorArr = $occurMain->getErrorArr();
					foreach($errorArr as $errorStr){
						$this->logOrEcho($errorStr,1);
					}
				}

				$this->logOrEcho('Generating recordID GUID for all new records...');
				$uuidManager = new UuidFactory();
				$uuidManager->setSilent(1);
				$uuidManager->populateGuids($this->collid);
			}
			unlink($fullPath);
			$this->logOrEcho('Done process image mapping ('.date('Y-m-d H:i:s').')');
		}
	}

	private function deleteImage($imgUrl){
		if(stripos($imgUrl, 'http') === 0 || stripos($imgUrl, 'https') === 0){
			$imgUrl = parse_url($imgUrl, PHP_URL_PATH);
		}
		if($GLOBALS['IMAGE_ROOT_URL'] && strpos($imgUrl,$GLOBALS['IMAGE_ROOT_URL']) === 0){
			$imgPath = $GLOBALS['IMAGE_ROOT_PATH'].substr($imgUrl,strlen($GLOBALS['IMAGE_ROOT_URL']));
			unlink($imgPath);
		}
	}

	//Shared functions
	private function getPrimaryKey($targetIdentifier,$sourceIdentifier,$fileName = ''){
		$occid = false;
		if($this->collid){
			//Check to see if record with pk already exists
			if($this->matchCatalogNumber){
				$sql = 'SELECT occid FROM omoccurrences WHERE (collid = '.$this->collid.') AND (catalognumber IN("'.$targetIdentifier.'"'.(substr($targetIdentifier,0,1)=='0'?',"'.ltrim($targetIdentifier,'0 ').'"':'').')) ';
				$rs = $this->conn->query($sql);
				if($row = $rs->fetch_object()){
					$occid = $row->occid;
				}
				$rs->free();
			}
			if(!$occid && $this->matchOtherCatalogNumbers){
				$searchStr = '"'.$targetIdentifier.'"'.(substr($targetIdentifier,0,1)=='0'?',"'.ltrim($targetIdentifier,'0 ').'"':'');
				$sql = 'SELECT o.occid
					FROM omoccurrences o LEFT JOIN omoccuridentifiers i ON o.occid = i.occid
					WHERE (o.collid = '.$this->collid.')
					AND (o.othercatalognumbers IN('.$searchStr.') OR (i.identifierValue IN('.$searchStr.'))) ';
				$rs = $this->conn->query($sql);
				if($row = $rs->fetch_object()){
					$occid = $row->occid;
				}
				$rs->free();
			}
			if($occid){
				if($fileName){
					//Is CyVerse mapped image
					//Check to see if image has already been linked
					$fileBaseName = $fileName;
					$fileExt = '';
					$dotPos = strrpos($fileName,'.');
					if($dotPos){
						$fileBaseName = substr($fileName,0,$dotPos);
						$fileExt = strtolower(substr($fileName,$dotPos+1));
					}
					//Grab existing images for that occurrence
					$imgArr = array();
					$sqlTest = 'SELECT imgid, sourceidentifier FROM images WHERE (occid = '.$occid.') ';
					$rsTest = $this->conn->query($sqlTest);
					while($rTest = $rsTest->fetch_object()){
						$imgArr[$rTest->imgid] = $rTest->sourceidentifier;
					}
					$rsTest->free();
					//Process images to determine if new images should be added
					$highResList = array('cr2','dng','tiff','tif','nef');
					foreach($imgArr as $imgId => $sourceId){
						if($sourceId){
							if(preg_match('/^([A-Za-z0-9\-]+);\sfilename:\s(.+)$/',$sourceId,$m)){
								$guid = $m[1];
								$sourceFileName = $m[2];
								$fnArr = explode('.',$sourceFileName);
								$fnExt = strtolower(array_pop($fnArr));
								$fnBase = implode($fnArr);
								if($guid == $sourceIdentifier){
									//Image file already loaded (based on identifier, thus abort and don't reload
									$occid = 'imgid-'.$imgId;
									break;
								}
								elseif($sourceFileName == $fileName){
									//Image file already loaded, thus abort and don't reload
									$occid = 'imgid-'.$imgId;
									//$this->logOrEcho('NOTICE: Image mapping skipped; file ('.$fileName.') already in system (#'.$occLink.')',2);
									break;
								}
								elseif($fileBaseName  == $fnBase && $fnExt == 'jpg'){
									//JPG already mapped for this image, thus abort and don't reload
									$occid = false;
									//$this->logOrEcho('NOTICE: Image mapping skipped; high-res image with same name already in system ('.$fileName.'; '.$occLink.')',2);
									break;
								}
								elseif($fileExt == 'jpg' && in_array($fnExt,$highResList)){
									//$this->logOrEcho('NOTICE: Replacing exist map of high-res with this JPG version ('.$fileName.'; #'.$occLink.')',2);
									//Replace high res source with JPG by deleteing high res from database
									$this->conn->query('DELETE FROM images WHERE imgid = '.$imgId);
								}
							}
						}
					}
				}
				else{
					if($sourceIdentifier){
						//Check to see if image was previous loaded into system, if so remove
						$sql = 'DELETE i.* FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
							'WHERE (o.occid = '.$occid.') AND (i.originalurl LIKE "http%://api.idigbio.org%") AND (i.sourceIdentifier = "'.$sourceIdentifier.'")';
						$this->conn->query($sql);
						if($this->conn->affected_rows) $this->logOrEcho('Replacing previously mapped image with new input',3);
					}
				}
			}
			else{
				//Records does not exist, create a new one to which image will be linked
				$sql2 = 'INSERT INTO omoccurrences(collid,'.($this->matchCatalogNumber?'catalognumber':'othercatalognumbers').',processingstatus,dateentered) '.
					'VALUES('.$this->collid.',"'.$targetIdentifier.'","unprocessed","'.date('Y-m-d H:i:s').'")';
				if($this->conn->query($sql2)){
					$occid = $this->conn->insert_id;
					$this->logOrEcho('Linked image to new "unprocessed" specimen record (#<a href="../individual/index.php?occid='.$occid.'" target="_blank">'.$occid.'</a>) ',2);
				}
				else $this->logOrEcho("ERROR creating new occurrence record: ".$this->conn->error,2);
			}
		}
		return $occid;
	}

	private function insertImage($occid,$targetFieldArr){
		$status = true;
		if($occid && $targetFieldArr){
			$format = 'image/jpeg';
			if(!array_key_exists('format', $targetFieldArr) || !$targetFieldArr['format']) $targetFieldArr['format'] = $format;

			$sqlInsert = '';
			$sqlValues = '';
			foreach($targetFieldArr as $fieldName => $value){
				if($value){
					$sqlInsert .= ','.$fieldName;
					$sqlValues .= ',"'.$this->cleanInStr($value).'"';
				}
			}
			$sql = 'INSERT INTO images(occid'.$sqlInsert.') VALUES ('.$occid.$sqlValues.')';
			if($this->conn->query($sql)){
				$occLink = '<a href="../individual/index.php?occid='.$occid.'" target="_blank">#'.$occid.'</a>';
				$this->logOrEcho('Image linked to existing record'.(isset($targetFieldArr['sourceIdentifier'])?' ('.$targetFieldArr['sourceIdentifier'].')':'').': '.$occLink,2);
			}
			else{
				$status = false;
				$this->logOrEcho('ERROR: Unable to load image record into database: '.$this->conn->error,3);
				//$this->logOrEcho($sql);
			}
		}
		else{
			$status = false;
			if(!$occid) $this->logOrEcho('ERROR inserting image: missing occid (omoccurrences PK) ',2);
			else $this->logOrEcho('ERROR inserting image: missing image data ',2);
		}
		return $status;
	}

	private function updateImage($imgid,$targetFieldArr){
		$status = true;
		if($imgid && $targetFieldArr){
			$format = 'image/jpeg';
			if(!array_key_exists('format', $targetFieldArr) || !$targetFieldArr['format']) $targetFieldArr['format'] = $format;
			$sqlFrag = '';
			foreach($targetFieldArr as $fieldName => $value){
				$sqlFrag .= $fieldName.' = '.($value?'"'.$this->cleanInStr($value).'"':'NULL').',';
			}
			$sql = 'UPDATE images SET '.trim($sqlFrag,' ,').' WHERE (imgid = '.$imgid.')';
			if($this->conn->query($sql)){
				$this->logOrEcho('Existing image data updated '.(isset($targetFieldArr['sourceIdentifier'])?'('.$targetFieldArr['sourceIdentifier'].')':''),2);
			}
			else{
				$status = false;
				$this->logOrEcho('ERROR: Unable to load image record into database: '.$this->conn->error,3);
				//$this->logOrEcho($sql);
			}
		}
		else{
			$status = false;
			if(!$imgid) $this->logOrEcho('ERROR updating image: missing imgid',2);
			else $this->logOrEcho('ERROR updating image: missing image data',2);
		}
		return $status;
	}

	private function cleanHouse($collList){
		$this->logOrEcho('Updating collection statistics...',1);
		$occurMain = new OccurrenceMaintenance($this->conn);

		/*
		$this->logOrEcho('General cleaning...',2);
		$collString = implode(',',$collList);
		$occurMain->setCollidStr($collString);
		if(!$occurMain->generalOccurrenceCleaning()){
			$errorArr = $occurMain->getErrorArr();
			foreach($errorArr as $errorStr){
				$this->logOrEcho($errorStr,1);
			}
		}
		$this->logOrEcho('Protecting sensitive species...',2);
		$protectCnt = $occurMain->protectRareSpecies();
		$this->logOrEcho($protectCnt.' records protected',1);
		*/

		if($collList){
			$this->logOrEcho('Updating collection statistics...',2);
			foreach($collList as $collid){
				if(!$occurMain->updateCollectionStatsBasic($collid)){
					$errorArr = $occurMain->getErrorArr();
					foreach($errorArr as $errorStr){
						$this->logOrEcho($errorStr,1);
					}
				}
			}
		}
		$occurMain->__destruct();

		$this->logOrEcho('Populating recordID UUIDs for all records...',2);
		$uuidManager = new UuidFactory($this->conn);
		$uuidManager->setSilent(1);
		$uuidManager->populateGuids();
		$uuidManager->__destruct();
	}

	private function updateLastRunDate($date){
		if($this->spprid){
			$sql = 'UPDATE specprocessorprojects SET lastrundate = "'.$date.'" WHERE spprid = '.$this->spprid;
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR updating last run date: '.$this->conn->error);
			}
		}
	}

	//Set and Get functions
	private function setCollArr(){
		if($this->collid){
			$sql = 'SELECT collid, institutioncode, collectioncode, collectionname, managementtype '.
				'FROM omcollections '.
				'WHERE (collid = '.$this->collid.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->collArr['instcode'] = $r->institutioncode;
				$this->collArr['collcode'] = $r->collectioncode;
				$this->collArr['collname'] = $r->collectionname;
				$this->collArr['managementtype'] = $r->managementtype;
			}
			$rs->free();
		}
	}

	public function setCollid($id){
		if(is_numeric($id)){
			$this->collid = $id;
			$this->setCollArr();
		}
	}

	public function setSpprid($spprid){
		if(is_numeric($spprid)){
			$this->spprid = $spprid;
		}
	}

	public function setMatchCatalogNumber($b){
		if($b) $this->matchCatalogNumber = true;
		else $this->matchCatalogNumber = false;
	}

	public function setMatchOtherCatalogNumbers($b){
		if($b) $this->matchOtherCatalogNumbers = true;
		else $this->matchOtherCatalogNumbers = false;
	}

	public function setCreateNewRecord($b){
		if($b) $this->createNewRecord = true;
		else $this->createNewRecord = false;
	}

	public function setLogMode($c){
		$this->logMode = $c;
	}

	public function getLogMode(){
		return $this->logMode;
	}

	//Misc functions
	private function cleanInStr($inStr){
		$retStr = trim($inStr);
		$retStr = str_replace(chr(10),' ',$retStr);
		$retStr = str_replace(chr(11),' ',$retStr);
		$retStr = str_replace(chr(13),' ',$retStr);
		$retStr = str_replace(chr(20),' ',$retStr);
		$retStr = str_replace(chr(30),' ',$retStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}

	private function logOrEcho($str,$indent = 0){
		if($this->logMode > 1){
			if($this->logFH){
				if($indent) $str = "\t".$str;
				fwrite($this->logFH,strip_tags($str)."\n");
			}
		}
		if($this->logMode == 1 || $this->logMode == 3){
			echo '<li '.($indent?'style="margin-left:'.($indent*15).'px"':'').'>'.$str."</li>\n";
			ob_flush();
			flush();
		}
	}
}
?>