<?php
include_once($SERVER_ROOT.'/classes/SpecUploadBase.php');
include_once($SERVER_ROOT.'/classes/PortalIndex.php');

class SpecUploadDwca extends SpecUploadBase{

	private $metaArr;
	private $delimiter = ",";
	private $enclosure = '"';
	private $encoding = 'utf-8';
	private $loopCnt = 0;
	private $sourcePortalIndex = 0;
	private $publicationGuid = null;
	private $coreIdArr = array();

	function __construct() {
 		parent::__construct();
		$this->setUploadTargetPath();
	}

	public function __destruct(){
 		parent::__destruct();
	}

	public function uploadFile(){
		$retPath = '';
		if(array_key_exists('ulfnoverride',$_POST) && $_POST['ulfnoverride'] && !$this->path){
			$this->path = $_POST['ulfnoverride'];
		}
		if($this->path){
			if($this->uploadType == $this->IPTUPLOAD){
				$this->path = preg_replace('/&v=[\d\.]+/', '', $this->path);
				//If IPT resource URL was provided, adjust ULR to point to the Archive file
				if(strpos($this->path,'/resource.do')) $this->path = str_replace('/resource.do','/archive.do',$this->path);
				elseif(strpos($this->path,'/resource?')) $this->path = str_replace('/resource','/archive.do',$this->path);
			}
			elseif($this->uploadType == $this->SYMBIOTA){
				if(strpos($this->path, 'webservices/dwc/dwcapubhandler.php')){
					$this->publicationGuid = UuidFactory::getUuidV4();
					$this->path .= '&publicationguid='.$this->publicationGuid.'&portalguid='.$GLOBALS['PORTAL_GUID'];
				}
			}
			if((substr($this->path,0,1) == '/' || preg_match('/^[A-Za-z]{1}:/', $this->path)) && is_dir($this->path)){
				//Path is a local directory, possible manually extracted local DWCA directory
				if(substr($this->path,-1) != '/') $this->path .= '/';
				$this->uploadTargetPath = $this->path;
				$this->locateBaseFolder();
				$retPath = $this->uploadTargetPath;
			}
			else{
				$this->createTargetSubDir();
				$targetPath = $this->uploadTargetPath.'dwca.zip';
				//if(!$this->copyChunked($this->path,$targetPath)){
				if(!copy($this->path,$targetPath)){
					$this->errorStr = 'ERROR uploading file (path: '.$targetPath.')';
					if(!is_writable($this->uploadTargetPath)) $this->errorStr .= ', Permission issue: target directory is not writable';
					$this->outputMsg('<li>'.$this->errorStr.' </li>');
				}
				if($this->unpackArchive()) $retPath = $this->uploadTargetPath;
				else $this->uploadTargetPath = '';
			}
		}
		elseif(array_key_exists("uploadfile",$_FILES)){
			//File is delivered as a POST stream, probably from browser
			$this->createTargetSubDir();
			$targetPath = $this->uploadTargetPath.'dwca.zip';
			if(!move_uploaded_file($_FILES['uploadfile']['tmp_name'], $targetPath)){
				$msg = 'unknown';
				$err = $_FILES['uploadfile']['error'];
				if($err == 1) $msg = 'uploaded file exceeds the upload_max_filesize directive in php.ini';
				elseif($err == 2) $msg = 'uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				elseif($err == 3) $msg = 'uploaded file was only partially uploaded';
				elseif($err == 4) $msg = 'no file was uploaded';
				elseif($err == 5) $msg = 'unknown error 5';
				elseif($err == 6) $msg = 'missing a temporary folder';
				elseif($err == 7) $msg = 'failed to write file to disk';
				elseif($err == 8) $msg = 'a PHP extension stopped the file upload';

				$this->errorStr = 'ERROR uploading file: ';
				if($msg) $this->errorStr .= $msg.'; ';
				if(!is_writable($this->uploadTargetPath)) $this->errorStr .= 'permission issue, target directory is not writable (path: '.$targetPath.')';
				$this->outputMsg('<li>'.$this->errorStr.' </li>');
			}
			if($this->unpackArchive()) $retPath = $this->uploadTargetPath;
			else $this->uploadTargetPath = '';
		}
		return $retPath;
	}

	private function createTargetSubDir(){
		$this->deleteTempFiles();
		$localFolder = 'collid_'.$this->collId.'/';
		if(file_exists($this->uploadTargetPath.$localFolder)){
			//Reset upload directory
			$this->deleteTempFiles($localFolder, false);
			$this->uploadTargetPath .= $localFolder;
		}
		else{
			if(mkdir($this->uploadTargetPath.$localFolder)) $this->uploadTargetPath .= $localFolder;
		}
	}

	private function deleteTempFiles($pathFragment = '', $filetimeLimit = '-2 days'){
		$dirPath = $this->uploadTargetPath.$pathFragment;
		$allowedToBeDeleted = array('csv','dat','tab','txt','xml','zip');
		if($handle = opendir($dirPath)) {
			$loopCnt = 0;
			while(false !== ($item = readdir($handle))) {
				if($item){
					if(is_file($dirPath.$item) || strtolower(substr($item,-4)) == '.zip'){
						$ext = pathinfo($dirPath.$item, PATHINFO_EXTENSION);
						if(in_array(strtolower($ext), $allowedToBeDeleted)){
							if($filetimeLimit){
								if(!filemtime($dirPath.$item) || filemtime($dirPath.$item) > strtotime($filetimeLimit)) continue;
							}
							if(is_writable($dirPath.$item)) unlink($dirPath.$item);
						}
					}
					elseif(is_dir($dirPath.$item) && $item != '.' && $item != '..'){
						$this->deleteTempFiles($pathFragment.$item.'/', $filetimeLimit);
					}
					if($loopCnt > 15) break;
				}
				$loopCnt++;
			}
			closedir($handle);
		}
	}

	private function unpackArchive(){
		$status = true;
		if(file_exists($this->uploadTargetPath.'dwca.zip')){
			$zip = new ZipArchive;
			$zip->open($this->uploadTargetPath.'dwca.zip');
			if($zip->extractTo($this->uploadTargetPath)){
				//Get list of files
				$this->locateBaseFolder();
			}
			else{
				$err = $zip->getStatusString();
				if(!$err){
					if($this->uploadType == $this->IPTUPLOAD){
						$err = 'target path does not appear to be a valid IPT instance';
					}
					else{
						$err = 'Upload file or target path does not lead to a valid zip file';
					}
				}
				$this->outputMsg('<li>ERROR unpacking archive file: '.$err.'</li>');
				$this->errorStr = 'ERROR unpacking archive file: '.$err;
				$status = false;
			}
			$zip->close();
		}
		else{
			$this->errorStr = 'ERROR: dwca file does not exist (path: '.$this->uploadTargetPath.'dwca.zip)';
			$this->outputMsg('<li>'.$this->errorStr.' </li>');
		}
		return $status;
	}

	public function analyzeUpload(){
		$status = false;
		if($this->readMetaFile()){
			if(isset($this->metaArr['occur']['fields'])){
				$this->occurSourceArr = $this->metaArr['occur']['fields'];
				//Set identification and image source fields
				if(isset($this->metaArr['ident']['fields'])){
					$this->identSourceArr = $this->metaArr['ident']['fields'];
				}
				$this->setImageSourceArr();
			}
			$status = true;
		}
		return $status;
	}

	public function verifyBackupFile(){
		//File must contain eml.xml, meta.xml, occurrence.csv, image.csv, and identifications.csv
		if(!file_exists($this->uploadTargetPath.'occurrences.csv')){
			$this->errorStr = 'OccurrencesMissing';
			return false;
		}
		if(!file_exists($this->uploadTargetPath.'multimedia.csv') && !file_exists($this->uploadTargetPath.'images.csv')){
			$this->errorStr = 'ImagesMissing';
			return false;
		}
		if(!file_exists($this->uploadTargetPath.'identifications.csv')){
			$this->errorStr = 'IdentificationsMissing';
			return false;
		}
		if(!file_exists($this->uploadTargetPath.'meta.xml')){
			$this->errorStr = 'MetaMissing';
			return false;
		}
		if(!file_exists($this->uploadTargetPath.'eml.xml')){
			$this->errorStr = 'EmlMissing';
			return false;
		}
		if(!$this->readMetaFile()){
			$this->errorStr = 'MalformedMeta';
			return false;
		}

		//Verify that XML file matches identification of target collection
		$warningArr = array();
		$emlDoc = new DOMDocument();
		$emlDoc->load($this->uploadTargetPath.'eml.xml');
		$xpath = new DOMXpath($emlDoc);

		$nodeList = $xpath->query('//collection');
		if(!$nodeList){
			$warningArr[] = 'UnableToLocateCollectionElement';
		}
		if(count($nodeList) == 1){
			$node = $nodeList->item(0);
			if(!$node->hasAttribute('id') || $node->getAttribute('id') != $this->collId){
				$warningArr[] = 'CollectionIdNotMatching';
			}
			if($this->collMetadataArr["collguid"]){
				if(!$node->hasAttribute('identifier') || $node->getAttribute('identifier') != $this->collMetadataArr["collguid"]){
					$warningArr[] = 'CollectionGuidNotMatching';
				}
			}
		}
		else $warningArr[] = 'MultipleCollectionElements';
		if($warningArr) return $warningArr;
		return true;
	}

	private function readMetaFile(){
		//Read meta.xml file
		if(!$this->metaArr){
			$metaPath = $this->uploadTargetPath.'meta.xml';
			if(file_exists($metaPath)){
				$metaDoc = new DOMDocument();
				$metaDoc->load($metaPath);
				$coreId = '';
				//Get core (occurrences) file name
				if($coreElements = $metaDoc->getElementsByTagName('core')){
					//There may be more than one core elements, thus look for occurrence element
					foreach($coreElements as $coreElement){
						$rowType = $coreElement->getAttribute('rowType');
						if(stripos($rowType,'occurrence') || $coreElement->getElementsByTagName('id')->length){
							//Get index id
							if($idElement = $coreElement->getElementsByTagName('id')){
								$coreId = $idElement->item(0)->getAttribute('index');
								$this->metaArr['occur']['id'] = $coreId;
							}
							else{
								$this->outputMsg('WARNING: Core ID absent');
							}
							//Get file name
							if($locElements = $coreElement->getElementsByTagName('location')){
								$this->metaArr['occur']['name'] = $locElements->item(0)->nodeValue;
							}
							else{
								$this->outputMsg('ERROR: Unable to obtain the occurrence file name from meta.xml');
								$this->errorStr = 'ERROR: Unable to obtain the occurrence file name from meta.xml';
							}
							//Get the rest of the core attributes
							$this->metaArr['occur']['encoding'] = $coreElement->getAttribute('encoding');
							$this->metaArr['occur']['fieldsTerminatedBy'] = $coreElement->getAttribute('fieldsTerminatedBy');
							$this->metaArr['occur']['linesTerminatedBy'] = $coreElement->getAttribute('linesTerminatedBy');
							$this->metaArr['occur']['fieldsEnclosedBy'] = $coreElement->getAttribute('fieldsEnclosedBy');
							$this->metaArr['occur']['ignoreHeaderLines'] = $coreElement->getAttribute('ignoreHeaderLines');
							$this->metaArr['occur']['rowType'] = $rowType;
							//Get the Core field names
							if($fieldElements = $coreElement->getElementsByTagName('field')){
								foreach($fieldElements as $fieldElement){
									$term = $fieldElement->getAttribute('term');
									if(strpos($term,'/')) $term = substr($term,strrpos($term,'/')+1);
									$this->metaArr['occur']['fields'][$fieldElement->getAttribute('index')] = $term;
								}
							}
							if($coreId === '0' && !isset($this->metaArr['occur']['fields'][0])){
								//Set id
								$this->metaArr['occur']['fields'][0] = 'id';
							}
							//Test meta.xml field list against occurrence file
							if($this->metaArr['occur']['ignoreHeaderLines'] == 1){
								//Set delimiter
								if($this->metaArr['occur']['fieldsTerminatedBy']){
									if($this->metaArr['occur']['fieldsTerminatedBy'] == '\t') $this->delimiter = "\t";
									else $this->delimiter = $this->metaArr['occur']['fieldsTerminatedBy'];
									//Read occurrence header and compare
									$fh = fopen($this->uploadTargetPath.$this->metaArr['occur']['name'],'r') or die("Can't open occurrence file");
									$headerArr = $this->getRecordArr($fh,true);
									foreach($headerArr as $k => $v){
										$metaField = strtolower($this->metaArr['occur']['fields'][$k]);
										if(substr($metaField,0,6) == 'paleo-') $metaField = substr($metaField,6);
										if(strtolower($v) != $metaField){
											$msg = '<div style="margin-left:25px;">';
											$msg .= 'WARNING: meta.xml field order out of sync w/ '.$this->metaArr['occur']['name'].'; remapping: field #'.($k+1).' => '.$v;
											$msg .= '</div>';
											$this->outputMsg($msg);
											$this->errorStr = $msg;
											$this->metaArr['occur']['fields'][$k] = $v;
										}
									}
									fclose($fh);
								}
							}
							if($this->verboseMode == 2){
								/*
								$outputStr = 'DWCA details: encoding = '.$this->metaArr['occur']['encoding'].'; ';
								$outputStr .= 'fieldsTerminatedBy: '.$this->metaArr['occur']['fieldsTerminatedBy'].'; ';
								$outputStr .= 'linesTerminatedBy: '.$this->metaArr['occur']['linesTerminatedBy'].'; ';
								$outputStr .= 'fieldsEnclosedBy: '.$this->metaArr['occur']['fieldsEnclosedBy'].'; ';
								$outputStr .= 'ignoreHeaderLines: '.$this->metaArr['occur']['ignoreHeaderLines'].'; ';
								$outputStr .= 'rowType: '.$this->metaArr['occur']['rowType'];
								$this->outputMsg($outputStr);
								*/
							}
						}
					}
				}
				else{
					$this->outputMsg('ERROR: Unable to access core element in meta.xml');
					$this->errorStr = 'ERROR: Unable to access core element in meta.xml';
					return false;
				}
				if($this->metaArr){
					$extensionElements = $metaDoc->getElementsByTagName('extension');
					foreach($extensionElements as $extensionElement){
						$rowType = $extensionElement->getAttribute('rowType');
						$tagName = '';
						if(stripos($rowType,'identification')){
							//Is identification data related to core data
							$tagName = 'ident';
						}
						elseif(stripos($rowType,'image') || stripos($rowType,'audubon_core') || stripos($rowType,'Multimedia')){
							//Is image data related to core data
							$tagName = 'image';
						}
						$extCoreId = '';
						if($coreidElement = $extensionElement->getElementsByTagName('coreid')){
							$extCoreId = $coreidElement->item(0)->getAttribute('index');
							$this->metaArr[$tagName]['coreid'] = $extCoreId;
						}
						//If coreIds equal, retrieve determination data
						if($coreId === '' || $coreId === $extCoreId){
							if($tagName){
								if($locElements = $extensionElement->getElementsByTagName('location')){
									$this->metaArr[$tagName]['name'] = $locElements->item(0)->nodeValue;
								}
								else{
									$this->outputMsg('WARNING: Unable to obtain the '.$tagName.' file name from meta.xml');
								}
								//Get the rest of the core attributes
								$this->metaArr[$tagName]['encoding'] = $extensionElement->getAttribute('encoding');
								$this->metaArr[$tagName]['fieldsTerminatedBy'] = $extensionElement->getAttribute('fieldsTerminatedBy');
								$this->metaArr[$tagName]['linesTerminatedBy'] = $extensionElement->getAttribute('linesTerminatedBy');
								$this->metaArr[$tagName]['fieldsEnclosedBy'] = $extensionElement->getAttribute('fieldsEnclosedBy');
								$this->metaArr[$tagName]['ignoreHeaderLines'] = $extensionElement->getAttribute('ignoreHeaderLines');
								$this->metaArr[$tagName]['rowType'] = $rowType;
								//Get the Core field names
								if($fieldElements = $extensionElement->getElementsByTagName('field')){
									foreach($fieldElements as $fieldElement){
										$term = $fieldElement->getAttribute('term');
										if(strpos($term,'/')) $term = substr($term,strrpos($term,'/')+1);
										$index = $fieldElement->getAttribute('index');
										if(is_numeric($index)){
											$this->metaArr[$tagName]['fields'][$index] = $term;
										}
									}
								}
								$this->metaArr[$tagName]['fields'][$extCoreId] = 'coreid';

								//Test meta.xml field list against extension file
								if($this->metaArr[$tagName]['ignoreHeaderLines'] == 1){
									//Set delimiter
									if($this->metaArr[$tagName]['fieldsTerminatedBy']){
										if($this->metaArr[$tagName]['fieldsTerminatedBy'] == '\t'){
											$this->delimiter = "\t";
										}
										else{
											$this->delimiter = $this->metaArr[$tagName]['fieldsTerminatedBy'];
										}
										//Read extension file header and compare
										$fh = fopen($this->uploadTargetPath.$this->metaArr[$tagName]['name'],'r') or die("Can't open $tagName extension file");
										$headerArr = $this->getRecordArr($fh,true);
										if($headerArr){
											foreach($headerArr as $k => $v){
												$metaField = strtolower($this->metaArr[$tagName]['fields'][$k]);
												if(strtolower($v) != $metaField && $metaField != 'coreid'){
													$msg = '<div style="margin-left:25px;">';
													$msg .= 'WARNING: meta.xml field order out of sync with '.$this->metaArr[$tagName]['name'].'; remapping: field #'.($k+1).' => '.$v;
													$msg .= '</div>';
													$this->outputMsg($msg);
													$this->errorStr = $msg;
													$this->metaArr[$tagName]['fields'][$k] = $v;
												}
											}
										}
										fclose($fh);
									}
								}
							}
						}
					}
				}
				else{
					$this->errorStr = 'ERROR: Unable to obtain core element from meta.xml';
					$this->outputMsg($this->errorStr);
					return false;
				}
			}
			else{
				$this->errorStr = 'ERROR: Malformed DWCA, unable to locate ('.$metaPath.')';
				$this->outputMsg($this->errorStr);
				return false;
			}
		}
		return true;
	}

	private function readEmlFile(){
		$emlDoc = new DOMDocument();
		if(file_exists($this->uploadTargetPath.'eml.xml')){
			$emlDoc->load($this->uploadTargetPath.'eml.xml');
			$xpath = new DOMXpath($emlDoc);
			if($symbiotaNodeList = $xpath->query('//symbiota')){
				if($node = $symbiotaNodeList->item(0)){
					if($node->hasAttribute('id')){
						if($symbiotaGuid = $node->getAttribute('id')){
							if(isset($GLOBALS['ACTIVATE_PORTAL_INDEX']) && $this->uploadType != $this->RESTOREBACKUP){
								$portalManager = new PortalIndex();
								if($portalArr = $portalManager->getPortalIndexArr($symbiotaGuid)){
									$this->sourcePortalIndex = key($portalArr);
								}
								if(!$this->sourcePortalIndex){
									$this->sourcePortalIndex = $symbiotaGuid;
									$urlNodeList = $xpath->query('/eml:eml/dataset/alternateIdentifier');
									if($urlNodeList && isset($urlNodeList->item(0)->nodeValue)){
										$urlRoot = $urlNodeList->item(0)->nodeValue;
										$urlRoot = substr($urlRoot,0,strpos($urlRoot,'/collections/misc/collprofiles.php'));
										$portalName = 'GUID: '.$symbiotaGuid;
										if($GLOBALS['DEFAULT_TITLE']) $portalName = $GLOBALS['DEFAULT_TITLE'];
										$portalManager->initiateHandshake($urlRoot);
									}
								}
							}
						}
					}
				}
			}
			else $this->errorStr = 'Unable to locate Symbiota element';
		}
	}

	private function locateBaseFolder($pathFrag = ''){
		if($handle = opendir($this->uploadTargetPath.$pathFrag)) {
			while(false !== ($item = readdir($handle))){
				if($item){
					if(is_file($this->uploadTargetPath.$pathFrag.$item)){
						if(strtolower($item) == 'meta.xml'){
							$this->uploadTargetPath .= $pathFrag;
							break;
						}
					}
					elseif(is_dir($this->uploadTargetPath.$pathFrag.$item) && $item != '.' && $item != '..'){
						$pathFrag .= $item.'/';
						$this->locateBaseFolder($pathFrag);
					}
				}
			}
			closedir($handle);
		}
	}

	public function uploadData($finalTransfer){
		global $CHARSET;

		//First, delete all records in uploadspectemp table associated with this collection
		$this->prepUploadData();

		$fullPath = $this->uploadTargetPath;
		if(file_exists($fullPath)){
			if($this->readMetaFile() && isset($this->metaArr['occur']['fields'])){
				//Set parsing variables
				if(isset($this->metaArr['occur']['fieldsTerminatedBy']) && $this->metaArr['occur']['fieldsTerminatedBy']){
					if($this->metaArr['occur']['fieldsTerminatedBy'] == '\t'){
						$this->delimiter = "\t";
					}
					else{
						$this->delimiter = $this->metaArr['occur']['fieldsTerminatedBy'];
					}
				}
				else{
					$this->delimiter = '';
				}
				if(isset($this->metaArr['occur']['fieldsEnclosedBy'])){
					$this->enclosure = $this->metaArr['occur']['fieldsEnclosedBy'];
					if($this->delimiter == "\t" && !$this->enclosure) $this->enclosure = chr(127);		//Needed to keep fgetcsv from reverting back to " as the default enclosure character
				}
				if(isset($this->metaArr['occur']['encoding']) && $this->metaArr['occur']['encoding']){
					$this->encoding = strtolower(str_replace('-','',$this->metaArr['occur']['encoding']));
				}
				$id = $this->metaArr['occur']['id'];

				$fullPath .= $this->metaArr['occur']['name'];
				if(file_exists($fullPath)){
			 		$fh = fopen($fullPath,'r') or die("Can't open occurrence file");

			 		if($this->metaArr['occur']['ignoreHeaderLines'] == '1'){
			 			//Advance one record to go past header
						$this->getRecordArr($fh,true);
			 		}

					$cset = strtolower(str_replace('-','',$CHARSET));
					//Set source array
					$this->occurSourceArr = array();
					foreach($this->metaArr['occur']['fields'] as $k => $v){
						$this->occurSourceArr[$k] = strtolower($v);
					}
					//Set custom filters if they haven't yet been set
					if($this->queryStr && !$this->filterArr){
						$qArr = json_decode($this->queryStr,true);
						if($qArr){
							foreach($qArr as $qField => $aArr){
								foreach($aArr as $qCond => $bArr){
									foreach($bArr as $qValue){
										$this->addFilterCondition($qField, $qCond, $qValue);
									}
								}
							}
						}
					}

					//Grab data
					$this->transferCount = 0;
					if($this->uploadType == $this->RESTOREBACKUP){
						$this->occurFieldMap['dbpk']['field'] = 'sourceprimarykey-dbpk';
						$this->occurFieldMap['occid']['field'] = 'id';
						$this->occurFieldMap['sciname']['field'] = 'scientificname';
					}
			 		if(!isset($this->occurFieldMap['dbpk']['field']) || !in_array($this->occurFieldMap['dbpk']['field'],$this->occurSourceArr)){
						$this->occurFieldMap['dbpk']['field'] = strtolower($this->metaArr['occur']['fields'][$id]);
					}
					$collName = $this->collMetadataArr["name"].' ('.$this->collMetadataArr["institutioncode"];
					if($this->collMetadataArr["collectioncode"]) $collName .= '-'.$this->collMetadataArr["collectioncode"];
					$collName .= ')';
					$this->outputMsg('<li>Uploading data for: '.$collName.'</li>');
					$this->conn->query('SET autocommit=0');
					$this->conn->query('SET unique_checks=0');
					$this->conn->query('SET foreign_key_checks=0');
					while($recordArr = $this->getRecordArr($fh)){
						$addRecord = true;
						foreach($this->filterArr as $fieldName => $condArr){
							$filterIndexArr = array_keys($this->occurSourceArr,$fieldName);
							$filterIndex = array_shift($filterIndexArr);
							$targetValue = '';
							if(array_key_exists($filterIndex, $recordArr)) $targetValue = trim(strtolower($recordArr[$filterIndex]));
							foreach($condArr as $cond => $valueArr){
								foreach($valueArr as $k => $str){
									if(strpos($str,';')){
										unset($valueArr[$k]);
										foreach(explode(';',$str) as $subStr){
											$valueArr[] = trim($subStr);
										}
									}
								}
								if($cond == 'IS_NULL'){
									if($targetValue){
										$addRecord = false;
										continue 2;
									}
								}
								elseif($cond == 'NOT_NULL'){
									if(!$targetValue){
										$addRecord = false;
										continue 2;
									}
								}
								elseif($cond == 'EQUALS'){
									if(!in_array($targetValue, $valueArr)){
										$addRecord = false;
										continue 2;
									}
								}
								else{
									if($cond == 'STARTS_WITH'){
										//Multiple values treated as an OR condition
										$condMeet = false;
										foreach($valueArr as $filterValue){
											if(strpos($targetValue,$filterValue) === 0){
												$condMeet = true;
											}
										}
										if(!$condMeet){
											$addRecord = false;
											continue 2;
										}
									}
									elseif($cond == 'LIKE'){
										//Multiple values treated as an OR condition
										$condMeet = false;
										foreach($valueArr as $filterValue){
											if(strpos($targetValue,$filterValue) !== false){
												$condMeet = true;
											}
										}
										if(!$condMeet){
											$addRecord = false;
											continue 2;
										}
									}
									elseif($cond == 'LESS_THAN'){
										$filterValue = array_pop($valueArr);
										if($targetValue > $filterValue){
											$addRecord = false;
											continue 2;
										}
									}
									elseif($cond == 'GREATER_THAN'){
										$filterValue = array_pop($valueArr);
										if($targetValue < $filterValue){
											$addRecord = false;
											continue 2;
										}
									}
								}
							}
						}
						if($addRecord){
							if($this->filterArr && ($this->includeIdentificationHistory || $this->includeImages)){
								$this->coreIdArr[$recordArr[0]] = '';
							}
							$recMap = Array();
							foreach($this->occurFieldMap as $symbField => $sMap){
								if(substr($symbField,0,8) != 'unmapped'){
									//Apply source filter if they exist
									$indexArr = array_keys($this->occurSourceArr, $sMap['field']);
									$index = array_shift($indexArr);
									if(array_key_exists($index,$recordArr)){
										$valueStr = trim($recordArr[$index]);
										if($cset != $this->encoding) $valueStr = $this->encodeString($valueStr);
										$recMap[$symbField] = $valueStr;
									}
								}
							}
							$this->loadRecord($recMap);
							unset($recMap);
						}
					}
					fclose($fh);
					$this->conn->query('COMMIT');
					$this->conn->query('SET autocommit=1');
					$this->conn->query('SET unique_checks=1');
					$this->conn->query('SET foreign_key_checks=1');
					$this->outputMsg('<li style="margin-left:10px;">Complete: '.$this->getTransferCount().' occurrence records loaded</li>');
					if($this->getTransferCount()){
						//Upload identification history
						if($this->includeIdentificationHistory && isset($this->metaArr['ident'])){
							$this->outputMsg('<li>Loading identification history extension... </li>');
							if($this->uploadType == $this->RESTOREBACKUP){
								$this->identFieldMap['occid']['field'] = 'coreid';
								$this->identFieldMap['initialtimestamp']['field'] = 'modified';
							}
							$this->identFieldMap['sciname']['field'] = 'sciname,scientificname';
							foreach($this->metaArr['ident']['fields'] as $k => $v){
								$this->identSourceArr[$k] = strtolower($v);
							}
							$this->uploadExtension('ident', $this->identFieldMap, $this->identSourceArr);
							$this->outputMsg('<li style="margin-left:10px;">Complete: '.$this->identTransferCount.' records loaded</li>');
						}

						//Upload images
						if($this->includeImages){
							if($this->setImageSourceArr()){
								$this->outputMsg('<li>Loading image extension... </li>');
								if($this->uploadType == $this->RESTOREBACKUP){
									$this->imageFieldMap['occid']['field'] = 'coreid';
									$this->imageFieldMap['originalurl']['field'] = 'accessuri';
									$this->imageFieldMap['thumbnailurl']['field'] = 'thumbnailaccessuri';
									$this->imageFieldMap['url']['field'] = 'goodqualityaccessuri';
									$this->imageFieldMap['owner']['field'] = 'creator';
								}
								$this->conn->query('SET autocommit=0');
								$this->conn->query('SET unique_checks=0');
								$this->conn->query('SET foreign_key_checks=0');
								$this->uploadExtension('image',$this->imageFieldMap,$this->imageSourceArr);
								$this->conn->query('COMMIT');
								$this->conn->query('SET autocommit=1');
								$this->conn->query('SET unique_checks=1');
								$this->conn->query('SET foreign_key_checks=1');

								//Remove images that don't have an occurrence record in uploadspectemp table
								$sql = 'DELETE ui.* '.
									'FROM uploadimagetemp ui LEFT JOIN uploadspectemp u ON ui.collid = u.collid AND ui.dbpk = u.dbpk '.
									'WHERE (ui.occid IS NULL) AND (ui.collid = '.$this->collId.') AND (u.collid IS NULL)';
								if($this->conn->query($sql)){
									$this->outputMsg('<li style="margin-left:10px;">Removing images associated with excluded occurrence records... </li>');
								}
								else{
									$this->outputMsg('<li style="margin-left:20px;">WARNING deleting orphaned uploadimagetemp records: '.$this->conn->error.'</li> ');
								}
								$this->setImageTransferCount();
								$this->outputMsg('<li style="margin-left:10px;">Complete: '.$this->imageTransferCount.' records loaded</li>');
							}
						}
						$this->cleanUpload();
						if($finalTransfer){
							$this->finalTransfer();
							$this->finalCleanup();
						}
					}
					else{
						if($this->filterArr){
							$outStr = '';
							foreach($this->filterArr as $fName => $cArr){
								foreach($cArr as $cond => $vArr){
									foreach($vArr as $str){
										$outStr .= '; '.$fName.' '.$cond.' '.$str;
									}
								}
							}
							$this->outputMsg('<li>ABORTED due to no occurrences matched based on filter criteria: '.trim($outStr,'; ').'</li>');
						}
						else{
							$this->outputMsg('<li>ABORTED: no occurrences imported</li>');
						}
					}
					$this->readEmlFile();
					$this->removeFiles();
				}
				else{
					$this->errorStr = 'ERROR: unable to locate occurrence upload file ('.$fullPath.')';
					$this->outputMsg('<li>'.$this->errorStr.'</li>');
					return false;
				}
			}
		}
		else{
			$this->errorStr = 'ERROR: unable to locate base path ('.$fullPath.')';
			$this->outputMsg('<li>'.$this->errorStr.'</li>');
			return false;
		}
		return true;
	}

	private function setImageSourceArr(){
		$status = false;
		if(isset($this->metaArr['image']['fields'])){
			foreach($this->metaArr['image']['fields'] as $k => $v){
				$v = strtolower($v);
				$prefixStr = '';
				if(in_array($v, $this->imageSourceArr)) $prefixStr = 'dcterms:';
				$this->imageSourceArr[$k] = $prefixStr.$v;
			}
			$status = true;
		}
		return $status;
	}

	private function removeFiles($pathFrag = ''){
		//First remove files
		$dirPath = $this->uploadTargetPath.$pathFrag;
		if($handle = opendir($dirPath)) {
			while(false !== ($item = readdir($handle))) {
				if($item){
					if(is_file($dirPath.$item) || strtolower(substr($item,-4)) == '.zip'){
						if(stripos($dirPath,$this->uploadTargetPath) === 0){
							unlink($dirPath.$item);
						}
					}
					elseif(is_dir($dirPath.$item) && $item != '.' && $item != '..'){
						$this->removeFiles($pathFrag . $item.'/');
					}
					if($this->loopCnt > 15) break;
				}
				$this->loopCnt++;
			}
			closedir($handle);
		}
		//Delete directory
		/*
		if(stripos($dirPath, $this->uploadTargetPath) === 0 && !preg_match('#/temp/data/$#', $dirPath)){
			rmdir($dirPath);
		}
		*/
	}

	private function uploadExtension($targetStr, $fieldMap, $sourceArr){
		global $CHARSET;
		$fullPathExt = '';
		if($this->metaArr[$targetStr]['name']){
			$fullPathExt = $this->uploadTargetPath.$this->metaArr[$targetStr]['name'];
		}
		if($fullPathExt && file_exists($fullPathExt)){
			if(isset($this->metaArr[$targetStr]['fields'])){
				if(isset($this->metaArr[$targetStr]['fieldsTerminatedBy']) && $this->metaArr[$targetStr]['fieldsTerminatedBy']){
					if($this->metaArr[$targetStr]['fieldsTerminatedBy'] == '\t'){
						$this->delimiter = "\t";
					}
					else{
						$this->delimiter = $this->metaArr[$targetStr]['fieldsTerminatedBy'];
					}
				}
				else{
					$this->delimiter = '';
				}
				if(isset($this->metaArr[$targetStr]['fieldsEnclosedBy']) && $this->metaArr[$targetStr]['fieldsEnclosedBy']){
					$this->enclosure = $this->metaArr[$targetStr]['fieldsEnclosedBy'];
				}
				if(isset($this->metaArr[$targetStr]['encoding']) && $this->metaArr[$targetStr]['encoding']){
					$this->encoding = strtolower(str_replace('-','',$this->metaArr[$targetStr]['encoding']));
				}
		 		$fh = fopen($fullPathExt,'r') or die("Can't open extension file");

		 		if($this->metaArr[$targetStr]['ignoreHeaderLines'] == '1'){
		 			//Advance one record to go past header
		 			$this->getRecordArr($fh,true);
		 		}
				$cset = strtolower(str_replace('-','',$CHARSET));

				$fieldMap['dbpk']['field'] = 'coreid';
				//Load data
				$this->conn->query('SET autocommit=0');
				$this->conn->query('SET unique_checks=0');
				$this->conn->query('SET foreign_key_checks=0');
				while($recordArr = $this->getRecordArr($fh)){
					if(!$this->coreIdArr || isset($this->coreIdArr[$recordArr[0]])){
						$recMap = Array();
						foreach($fieldMap as $symbField => $iMap){
							if(substr($symbField, 0, 8) != 'unmapped'){
								$valueStr = '';
								$fieldTargetArr = explode(',', $iMap['field']);
								foreach($fieldTargetArr as $fieldTarget){
									$indexArr = array_keys($sourceArr, $fieldTarget);
									$index = array_shift($indexArr);
									if(array_key_exists($index, $recordArr)){
										$valueStr = trim($recordArr[$index]);
										if($cset != $this->encoding) $valueStr = $this->encodeString($valueStr);
										$recMap[$symbField] = $valueStr;
									}
									if($valueStr) break;
								}
							}
						}
						if($targetStr == 'ident'){
							$this->loadIdentificationRecord($recMap);
						}
						elseif($targetStr == 'image'){
							$this->loadImageRecord($recMap);
						}
						unset($recMap);
					}
				}
				$this->conn->query('COMMIT');
				$this->conn->query('SET autocommit=1');
				$this->conn->query('SET unique_checks=1');
				$this->conn->query('SET foreign_key_checks=1');
				fclose($fh);
			}
			else{
				$errMsg = 'ERROR: fields not defined within extension file ('.$fullPathExt.')';
				$this->outputMsg($errMsg);
				$this->errorStr = $errMsg;
			}
		}
		else{
			$errMsg = 'ERROR locating extension file within archive: '.$fullPathExt;
			$this->outputMsg($errMsg);
			$this->errorStr = $errMsg;
		}
	}

	private function getRecordArr($fHandler,$isFirstRow=false){
		$recordArr = Array();
		if($this->delimiter){
			$recordArr = fgetcsv($fHandler,0,$this->delimiter,$this->enclosure);
			//Test for a UTF-8 BOM (Byte Order Mark), and remove if it exists
			if($isFirstRow){
				if(substr($recordArr[0],0,3)==chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF'))) $recordArr[0] = trim(substr($recordArr[0],3),' "');
			}
		}
		else{
			//Check to see if we can figure out the delimiter
			$record = fgets($fHandler);
			if(substr($this->metaArr['occur']['name'],-4) == ".csv"){
				$this->delimiter = ',';
			}
			elseif(strpos($record,"\t") !== false){
				$this->delimiter = "\t";
			}
			elseif(strpos($record,"|") !== false){
				$this->delimiter = "|";
			}
			else{
				$this->delimiter = ',';
			}
			//Get data
			$recordArr = explode($this->delimiter,$record);
			//Remove enclosures
			if($this->enclosure){
				foreach($recordArr as $k => $v){
					if(substr($v,0,1) == $this->enclosure && substr($v,-1) == $this->enclosure){
						$recordArr[$k] = substr($v,1,strlen($v)-2);
					}
				}
			}
		}
		return $recordArr;
	}

	public function finalTransfer(){
		$this->recordCleaningStage2();
		$this->transferOccurrences();
		$this->transferIdentificationHistory();
		$this->transferImages();
		if($GLOBALS['QUICK_HOST_ENTRY_IS_ACTIVE']) $this->transferHostAssociations();
		if($this->sourcePortalIndex && $this->collMetadataArr['managementtype'] == 'Snapshot'){
			$portalManager = new PortalIndex();
			$pubArr = array('pubTitle' => 'Symbiota Portal Index import - '.date('Y-m-d'), 'portalID' => $this->sourcePortalIndex, 'collid' => $this->collId, 'direction' => 'import', 'lastDateUpdate' => date('Y-m-d h:i:s'), 'guid' => $this->publicationGuid);
			$pubID = $portalManager->createPortalPublication($pubArr);
			if($pubID){
				if($portalManager->crossMapUploadedOccurrences($pubID, $this->collId)){
					$this->outputMsg('<li>Occurrences cross-mapped to Symbiota source portal</li> ');
				}
				else{
					$this->outputMsg('<li>ERROR cross-mapping occurrences to Symbiota source portal: '.$portalManager->getErrorMessage().'</li> ');
				}
			}
			else $this->outputMsg('<li>ERROR cross-mapping occurrences: '.$portalManager->getErrorMessage().'</li> ');
		}
		$this->transferAssociatedOccurrences();
		$this->finalCleanup();
		$this->outputMsg('<li style="">Upload Procedure Complete ('.date('Y-m-d h:i:s A').')!</li>');
		$this->outputMsg(' ');
	}

	public function cleanBackupReload(){
		//Delete records where occid is not within target collection
		$sql = 'SELECT count(u.occid) as cnt FROM uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid WHERE (u.collid = '.$this->collId.') AND (o.collid != '.$this->collId.')';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$badCnt = $r->cnt;
			if($badCnt > 0){
				$this->outputMsg('<li style="margin-left:10px">Removing '.$badCnt.' specimen records that are identified to belong to separate collection within this portal. This is typically due to restoring a backup into the wrong collection...</li>',1);
				$sql = 'DELETE u.* FROM uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid '.
					'WHERE (u.collid = '.$this->collId.') AND (o.collid != '.$this->collId.')';
				if(!$this->conn->query($sql)){
					$this->errorStr = '<li style="margin-left:10px">Unable to remove illegal specimens records belonging to another collection</li>';
				}
				$sql2 = 'DELETE u.* FROM uploaddetermtemp u INNER JOIN omoccurrences o ON u.occid = o.occid '.
					'WHERE (u.collid = '.$this->collId.') AND (o.collid != '.$this->collId.')';
				if(!$this->conn->query($sql2)){
					$this->errorStr = '<li style="margin-left:10px">Unable to remove illegal determination records belonging to another collection</li>';
				}
				$sql3 = 'DELETE u.* FROM uploadimagetemp u INNER JOIN omoccurrences o ON u.occid = o.occid '.
					'WHERE (u.collid = '.$this->collId.') AND (o.collid != '.$this->collId.')';
				if(!$this->conn->query($sql3)){
					$this->errorStr = '<li style="margin-left:10px">Unable to remove illegal image records belonging to another collection</li>';
				}
				$this->setTransferCount();
				$this->setIdentTransferCount();
				$this->setImageTransferCount();
			}
		}
		$rs->free();

		//Delete tidinterpreted values that were deleted from taxa table
		$this->outputMsg('<li style="margin-left:10px">Cleaning taxonomic thesaurus indexing...</li>',1);
		$sql = 'UPDATE uploadspectemp u LEFT JOIN taxa t ON u.tidinterpreted = t.tid '.
			'SET u.tidinterpreted = NULL '.
			'WHERE (u.collid = '.$this->collId.') AND (t.tid IS NULL)';
		if(!$this->conn->query($sql)){
			$this->errorStr = '<li style="margin-left:10px">Unable to remove bad taxonomic index links</li>';
		}

		//Remove occurrenceID GUIDs that already match the values in recordID field
		$this->outputMsg('<li style="margin-left:10px">Syncronizing occurrenceID GUIDs...</li>',1);
		$sql = 'UPDATE uploadspectemp SET occurrenceID = NULL WHERE (collid = '.$this->collId.') AND (occurrenceID = recordID)';
		if(!$this->conn->query($sql)){
			$this->errorStr = '<li style="margin-left:10px">Unable to remove duclicate GUID references</li>';
		}
	}

	//Setters and getters
	public function setTargetPath($targetPath){
		if($targetPath) $this->uploadTargetPath = $targetPath;
	}

	public function getDbpk(){
		$dbpk = parent::getDbpk();
		if(!$dbpk) $dbpk = 'id';
		return $dbpk;
	}

	public function getMetaArr(){
		return $this->metaArr;
	}

	public function setSourcePortalIndex($index){
		if($index) $this->sourcePortalIndex = $index;
	}

	public function getSourcePortalIndex(){
		return $this->sourcePortalIndex;
	}

	public function setPublicationGuid($guid){
		if(UuidFactory::is_valid($guid)) $this->publicationGuid = $guid;
	}

	public function getPublicationGuid(){
		return $this->publicationGuid;
	}
}
?>