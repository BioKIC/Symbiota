<?php
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorOcr.php');
include_once($SERVER_ROOT.'/classes/ImageShared.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/classes/OccurrenceEditorImages'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/classes/OccurrenceEditorImages.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/classes/OccurrenceEditorImages.en.php');

class OccurrenceEditorImages extends OccurrenceEditorManager {

	private $photographerArr = Array();
	private $imageRootPath = "";
	private $imageRootUrl = "";
	private $activeImgId = 0;

	public function __construct(){
 		parent::__construct();
 		$this->imageRootPath = $GLOBALS["imageRootPath"];
 		if(substr($this->imageRootPath,-1) != "/") $this->imageRootPath .= "/";
 		$this->imageRootUrl = $GLOBALS["imageRootUrl"];
 		if(substr($this->imageRootUrl,-1) != "/") $this->imageRootUrl .= "/";
	}

	public function __destruct(){
 		parent::__destruct();
	}

	public function getImageMap($imgId = 0){
		$imageMap = parent::getImageMap($imgId);
		if($imageMap){
			$imageTagArr = $this->getImageTags(implode(',',array_keys($imageMap)));
			foreach($imageTagArr as $imgId => $vArr){
				$imageMap[$imgId]['tags'] = $vArr;
			}
		}
		return $imageMap;
	}

	/**
	 * Takes parameters from a form submission and modifies an existing image record
	 * in the database.
	 */
	public function addImageOccurrence($postArr){
		global $LANG;
		$status = true;
		if($this->addOccurrence($postArr)){
			if($this->addImage($postArr)){
				if($this->activeImgId){
					//Load OCR
					$rawStr = '';
					$ocrSource = '';
					if($postArr['ocrblock']){
						$rawStr = trim($postArr['ocrblock']);
						if($postArr['ocrsource']) $ocrSource = $postArr['ocrsource'];
						else $ocrSource = 'User submitted';
					}
					elseif(isset($postArr['tessocr']) && $postArr['tessocr']){
						$ocrManager = new SpecProcessorOcr();
						$rawStr = $ocrManager->ocrImageById($this->activeImgId);
						$ocrSource = 'Tesseract';
					}
					if($rawStr){
						if($ocrSource) $ocrSource .= ': '.date('Y-m-d');
						$sql = 'INSERT INTO specprocessorrawlabels(imgid, rawstr, source) VALUES('.$this->activeImgId.',"'.$this->cleanRawFragment($rawStr).'","'.$this->cleanInStr($ocrSource).'")';
						if(!$this->conn->query($sql)){
							$this->errorStr = $LANG['ERROR_LOAD_OCR'].': '.$this->conn->error;
						}
					}
				}
			}
		}
		else{
			$status = false;
		}
		return $status;
	}

	public function editImage($imgArr){
		$status = false;
		$imgId = $imgArr['imgid'];
		if(!$imgId) return false;
		$sql = 'UPDATE images SET ';
		$fieldArr = array();
		$types = '';

		$url = null;
		if(array_key_exists('url', $imgArr)) $url = $imgArr['url'];
		if(array_key_exists('renameweburl',$imgArr)){
			$url = $imgArr['oldurl'];
			if($newUrl = $this->renameImage($imgArr['oldurl'], $imgArr['url'])){
				$url = $newUrl;
				$this->errorArr['web'] = 1;
			}
			else $this->errorArr['web'] = 0;
		}
		if($url !== null){
			if($GLOBALS['IMAGE_DOMAIN'] && substr($url,0,1) == '/') $url = $GLOBALS['IMAGE_DOMAIN'].$url;
			$sql .= 'url=?, ';
			$fieldArr[] = ($url?$url:NULL);
			$types .= 's';
		}

		$tnUrl = null;
		if(array_key_exists('tnurl', $imgArr)) $tnUrl = $imgArr['tnurl'];
		elseif(array_key_exists('thumbnailurl', $imgArr)) $tnUrl = $imgArr['thumbnailurl'];
		if(array_key_exists('renametnurl',$imgArr)){
			$tnUrl = $imgArr['oldtnurl'];
			if($newUrl = $this->renameImage($imgArr['oldtnurl'], $imgArr['tnurl'])){
				$tnUrl = $newUrl;
				$this->errorArr['tn'] = 1;
			}
			else $this->errorArr['tn'] = 0;
		}
		if($tnUrl !== null){
			if($GLOBALS['IMAGE_DOMAIN'] && substr($tnUrl,0,1) == '/') $tnUrl = $GLOBALS['IMAGE_DOMAIN'].$tnUrl;
			$fieldArr[] = ($tnUrl?$tnUrl:NULL);
			$sql .= 'thumbnailurl=?, ';
			$types .= 's';
		}

		$origUrl = null;
		if(array_key_exists('origurl', $imgArr)) $origUrl = $imgArr['origurl'];
		elseif(array_key_exists('originalurl', $imgArr)) $origUrl = $imgArr['originalurl'];
		if(array_key_exists('renameorigurl',$imgArr)){
			$origUrl = $imgArr['oldorigurl'];
			if($newUrl = $this->renameImage($imgArr['oldorigurl'], $imgArr['origurl'])){
				$origUrl = $newUrl;
				$this->errorArr['orig'] = 1;
			}
			else $this->errorArr['orig'] = 0;
		}
		if($origUrl !== null){
			if($GLOBALS['IMAGE_DOMAIN'] && substr($origUrl,0,1) == '/') $origUrl = $GLOBALS['IMAGE_DOMAIN'].$origUrl;
			$fieldArr[] = $origUrl?$origUrl:NULL;
			$sql .= 'originalurl=?, ';
			$types .= 's';
		}

		$additionalFields= array('occid' => 'i', 'tidinterpreted' => 'i', 'caption' => 's', 'photographer' => 's', 'photographeruid' => 'i', 'notes' => 's', 'copyright' => 's', 'sortoccurrence' => 'i', 'sourceurl' => 's');
		foreach($additionalFields as $fieldName => $t){
			if(array_key_exists($fieldName, $imgArr)){
				if($imgArr[$fieldName]) $fieldArr[] = $imgArr[$fieldName];
				else $fieldArr[] = null;
				$sql .= $fieldName.'=?, ';
				$types .= $t;
			}
		}
		if($fieldArr){
			$fieldArr[] = 'specimen';
			$fieldArr[] = $imgId;
			$sql .= 'imagetype=? WHERE (imgid= ?)';
			$types .= 'si';
			$imgUpdateStatus = false;
			$stmt = $this->conn->stmt_init();
			$stmt->prepare($sql);
			$stmt->bind_param($types, ...$fieldArr);
			if($stmt->execute()){
				$imgUpdateStatus = true;
				if(array_key_exists('occid', $imgArr) || array_key_exists('tidinterpreted', $imgArr)){
					$imgSql = 'UPDATE images i INNER JOIN omoccurrences o ON i.occid = o.occid SET i.tid = o.tidinterpreted WHERE (i.imgid = '.$imgId.')';
					$this->conn->query($imgSql);
				}
				$status = true;
			}
			else $status = false;
			$stmt->close();

			if($imgUpdateStatus){
				// update image tags
				$kArr = $this->getImageTagArr();
				foreach($kArr as $key => $description) {
					// Note: By using check boxes, we can't tell the difference between
					// an unchecked checkbox and the checkboxes not being present on the
					// form, we'll get around this by including the original state of the
					// tags for each image in a hidden field.
					if(array_key_exists('hidden_'.$key, $imgArr)){
						$sql = null;
						if(array_key_exists("ch_$key",$imgArr)) {
							if(!$imgArr['hidden_'.$key]) $sql = 'INSERT IGNORE into imagetag (imgid,keyvalue) values (?,?)';
						}
						else{
							// checkbox is not selected and this tag was used for this image
							if($imgArr['hidden_'.$key] == 1) $sql = 'DELETE from imagetag where imgid = ? and keyvalue = ?';
						}
						if($sql) {
							$stmt = $this->conn->stmt_init();
							$stmt->prepare($sql);
							if ($stmt) {
								$stmt->bind_param('is',$imgId,$key);
								if (!$stmt->execute()) {
									//$status .= ' ('.$LANG['WARNING_FAILED_TAG'].' (tag: '.[$key].', imgid: '.$imgId.'): '.$stmt->error ;
								}
								$stmt->close();
							}
						}
					}
				}
			}
			else $this->errorArr['error'] = $this->conn->error;
		}
		return $status;
	}

	private function renameImage($currentUrl, $newUrl){
		if($currentUrl == $newUrl) return false;
		if(strpos($currentUrl,$this->imageRootUrl) !== 0) return false;
		if(strpos($newUrl,$this->imageRootUrl) !== 0) return false;
		$currentPath = $this->imageRootPath.substr($currentUrl, strlen($this->imageRootUrl));
		$newPath = $this->imageRootPath.substr($newUrl, strlen($this->imageRootUrl));
		$cnt = 0;
		while(file_exists($newPath)){
			$ext = substr($newPath, strrpos($newPath,'.'));
			$pathFrag = substr($newPath, 0, strrpos($newPath,'.'));
			$newPath = $pathFrag.'_'.$cnt.$ext;
			$cnt++;
		}
		if(is_writable($currentPath)){
			if(rename($currentPath, $newPath)){
				$finalUrl = $this->imageRootUrl.substr($newPath, strlen($this->imageRootPath));
				return $finalUrl;
			}
			else{
				return false;
			}
		}
	}

	public function deleteImage($imgIdDel, $removeImg){
		$status = true;
		$imgManager = new ImageShared();
		if(!$imgManager->deleteImage($imgIdDel, $removeImg)){
			$this->errorStr = implode('',$imgManager->getErrArr());
			$status = false;
		}
		return $status;
	}

	public function isRemappable($imgArr){
		$bool = false;
		//If all images are writable, then we can rename the images to ensure they will not match incoming images
		$bool = $this->imagesAreWritable($imgArr);
		if(!$bool){
			//Or if the image name doesn't contain the catalog number or there is a timestamp added to filename
			$bool = $this->imageNotCatalogNumberLimited($imgArr);
		}
		return $bool;
	}

	private function imagesAreWritable($imgArr){
		$bool = false;
		$testArr = array();
		if($imgArr['origurl']) $testArr[] = $imgArr['origurl'];
		if($imgArr['url']) $testArr[] = $imgArr['url'];
		if($imgArr['tnurl']) $testArr[] = $imgArr['tnurl'];
		foreach($testArr as $url){
			if(strpos($url, $this->imageRootUrl) === 0){
				$rootPath = $this->imageRootPath.substr($url, strlen($this->imageRootUrl));
				if(is_writable($rootPath)){
					$bool = true;
				}
				else{
					$bool = false;
					break;
				}
			}
		}
		return $bool;
	}

	private function imageNotCatalogNumberLimited($imgArr){
		$bool = true;
		$testArr = array();
		if($imgArr['origurl']) $testArr[] = $imgArr['origurl'];
		if($imgArr['url']) $testArr[] = $imgArr['url'];
		if($imgArr['tnurl']) $testArr[] = $imgArr['tnurl'];
		//Load identifiers
		$idArr = array();
		$sql = 'SELECT o.catalogNumber, o.otherCatalogNumbers, i.identifierValue FROM omoccurrences o LEFT JOIN omoccuridentifiers i ON o.occid = i.occid WHERE (o.occid = '.$this->occid.')';
		$rs = $this->conn->query($sql);
		$cnt = 0;
		while($r = $rs->fetch_object()){
			if(!$cnt){
				if($r->catalogNumber) $idArr[] = $r->catalogNumber;
				if($r->otherCatalogNumbers) $idArr[] = $r->otherCatalogNumbers;
			}
			if($r->identifierValue) $idArr[] = $r->identifierValue;
			$cnt++;
		}
		$rs->free();
		//Iterate through identifiers and check for identifiers in name
		foreach($idArr as $idStr){
			foreach($testArr as $url){
				if($fileName = substr($url, strrpos($url, '/'))){
					if(strpos($fileName, $idStr) !== false && !preg_match('/_\d{10}[_\.]{1}/', $fileName)){
						$bool = false;
						break 2;
					}
				}
			}
		}
		return $bool;
	}

	public function remapImage($imgId, $targetOccid = 0){
		global $LANG;
		$status = true;
		if(!is_numeric($imgId)) return false;
		if($targetOccid == 'new'){
			$sql = 'INSERT INTO omoccurrences(collid, observeruid,processingstatus) SELECT collid, observeruid, "unprocessed" FROM omoccurrences WHERE occid = '.$this->occid;
			if($this->conn->query($sql)){
				$targetOccid = $this->conn->insert_id;
				$status = $targetOccid;
			}
			else{
				$this->errorArr[] = $LANG['UNABLE_RELINK_BLANK'].': '.$this->conn->error;
				return false;
			}
		}
		if($targetOccid && is_numeric($targetOccid)){
			$imgArr = array_intersect_key(current(parent::getImageMap($imgId)), array('url'=>'','tnurl'=>'','origurl'=>''));
			$editArr = array('imgid' => $imgId, 'occid' => $targetOccid);
			if(!$this->imageNotCatalogNumberLimited($imgArr)){
				if($this->imagesAreWritable($imgArr)){
					//Rename images to ensure that files are not written over with file named using previous catalog number
					$ts = time();
					if(isset($imgArr['url']) && $imgArr['url']){
						$ext = substr($imgArr['url'], strrpos($imgArr['url'],'.'));
						$pathFrag = substr($imgArr['url'], 0, strrpos($imgArr['url'],'.'));
						$editArr['renameweburl'] = 1;
						$editArr['oldurl'] = $imgArr['url'];
						$editArr['url'] = $pathFrag.'_'.$ts.$ext;
					}
					if(isset($imgArr['tnurl']) && $imgArr['tnurl']){
						$ext = substr($imgArr['tnurl'], strrpos($imgArr['tnurl'],'.'));
						$pathFrag = substr($imgArr['tnurl'], 0, strrpos($imgArr['tnurl'],'.'));
						$editArr['renametnurl'] = 1;
						$editArr['oldtnurl'] = $imgArr['tnurl'];
						$editArr['tnurl'] = $pathFrag.'_'.$ts.$ext;
					}
					if(isset($imgArr['origurl']) && $imgArr['origurl']){
						$ext = substr($imgArr['origurl'], strrpos($imgArr['origurl'],'.'));
						$pathFrag = substr($imgArr['origurl'], 0, strrpos($imgArr['origurl'],'.'));
						$editArr['renameorigurl'] = 1;
						$editArr['oldorigurl'] = $imgArr['origurl'];
						$editArr['origurl'] = $pathFrag.'_'.$ts.$ext;
					}
				}
			}
			if(!$this->editImage($editArr)){
				$this->errorArr[] = $LANG['UNABLE_REMAP_ANOTHER'].': '.$this->conn->error;
				return false;
			}
		}
		else{
			$sql = 'UPDATE images SET occid = NULL WHERE (imgid = '.$imgId.')';
			if(!$this->conn->query($sql)){
				$this->errorArr[] = $LANG['UNABLE_DISSOCIATE'].': '.$this->conn->error;
				return false;
			}
		}
		return $status;
	}

	public function addImage($postArr){
		$status = false;
		$imgManager = new ImageShared();
		//Set target path
		$subTargetPath = $this->collMap['institutioncode'];
		if($this->collMap['collectioncode']) $subTargetPath .= '_'.$this->collMap['collectioncode'];
		$subTargetPath .= '/';
		if(!$this->occurrenceMap) $this->setOccurArr();
		$catNum = $this->occurrenceMap[$this->occid]['catalognumber'];
		if($catNum){
			$catNum = str_replace(array('/','\\',' '), '', $catNum);
			if(preg_match('/^(\D{0,8}\d{4,})/', $catNum, $m)){
				$catPath = substr($m[1], 0, -3);
				if(is_numeric($catPath) && strlen($catPath)<5) $catPath = str_pad($catPath, 5, "0", STR_PAD_LEFT);
				$subTargetPath .= $catPath.'/';
			}
			else{
				$subTargetPath .= '00000/';
			}
		}
		else{
			$subTargetPath .= date('Ym').'/';
		}
		$imgManager->setTargetPath($subTargetPath);

		//Import large image or not
		if(array_key_exists('nolgimage',$postArr) && $postArr['nolgimage']==1){
			$imgManager->setMapLargeImg(false);
		}
		else{
			$imgManager->setMapLargeImg(true);
		}

		//Set image metadata variables
		if(array_key_exists('caption',$postArr)) $imgManager->setCaption($postArr['caption']);
		if(array_key_exists('photographeruid',$postArr)) $imgManager->setPhotographerUid($postArr['photographeruid']);
		if(array_key_exists('photographer',$postArr)) $imgManager->setPhotographer($postArr['photographer']);
		if(array_key_exists('sourceurl',$postArr)) $imgManager->setSourceUrl($postArr['sourceurl']);
		if(array_key_exists('copyright',$postArr)) $imgManager->setCopyright($postArr['copyright']);
		if(array_key_exists('notes',$postArr)) $imgManager->setNotes($postArr['notes']);
		if(array_key_exists('sortoccurrence',$postArr)) $imgManager->setSortOccurrence($postArr['sortoccurrence']);
		if(strpos($this->collMap['colltype'], 'Observations') !== false)  $imgManager->setSortSeq(40);

		$sourceImgUri = $postArr['imgurl'];
		if($sourceImgUri){
			//Source image is a URI supplied by user
			$imgManager->parseUrl($sourceImgUri);
			$imgWeb = '';
			$imgThumb = '';
			if(isset($postArr['weburl']) && $postArr['weburl']) $imgWeb = $postArr['weburl'];
			if(isset($postArr['tnurl']) && $postArr['tnurl']) $imgThumb = $postArr['tnurl'];
			if($imgThumb && !$imgWeb) $imgManager->setCreateWebDerivative(false);
			if($imgWeb) $imgManager->setImgWebUrl($imgWeb);
			if($imgThumb) $imgManager->setImgTnUrl($imgThumb);
			if(array_key_exists('copytoserver',$postArr) && $postArr['copytoserver']){
				if($imgManager->copyImageFromUrl()) $status = true;
			}
			else $imgManager->setImgLgUrl($sourceImgUri);
		}
		else{
			//Image is a file upload
			if($imgManager->uploadImage()) $status = true;
		}
		$imgManager->setOccid($this->occid);
		if(isset($this->occurrenceMap[$this->occid]['tidinterpreted'])) $imgManager->setTid($this->occurrenceMap[$this->occid]['tidinterpreted']);
		if($imgManager->processImage()){
			$this->activeImgId = $imgManager->getActiveImgId();
			$status = true;
		}

		//Load tags
		$status = $imgManager->insertImageTags($postArr);

		//Get errors and warnings
		$this->errorStr = $imgManager->getErrStr();
		return $status;
	}

	public function getPhotographerArr(){
		if(!$this->photographerArr){
			$sql = "SELECT u.uid, CONCAT_WS(', ',u.lastname,u.firstname) AS fullname ".
				"FROM users u ORDER BY u.lastname, u.firstname ";
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$this->photographerArr[$row->uid] = $this->cleanOutStr($row->fullname);
			}
			$result->free();
		}
		return $this->photographerArr;
	}

	public function getImageTagArr(){
		$retArr = Array();
		$sql = 'SELECT tagkey, description_en FROM imagetagkey ORDER BY sortorder';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tagkey] = $r->description_en;
		}
		$rs->free();
		return $retArr;
	}
}
?>