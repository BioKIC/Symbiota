<?php
include_once('OccurrenceUtilities.php');
include_once('UuidFactory.php');

class ImageShared{

	private $conn;
	private $connShared = false;
	private $sourceGdImg;

	private $imageRootPath = '';
	private $imageRootUrl = '';

	private $sourcePath = '';
	private $targetPath = '';
	private $urlBase = '';
	private $imgName = '';
	private $imgExt = '';

	private $sourceWidth = 0;
	private $sourceHeight = 0;
	private $sourceFileSize = 0;

	private $tnPixWidth = 200;
	private $webPixWidth = 1600;
	private $lgPixWidth = 3168;
	private $webFileSizeLimit = 300000;
	private $jpgCompression= 70;
	private $testOrientation = false;

	private $mapLargeImg = true;
	private $createWebDerivative = true;
	private $createThumbnailDerivative = true;

	//Image metadata
	private $caption = null;
	private $photographer = null;
	private $photographerUid = null;
	private $format = null;
	private $hashFunction = null;
	private $hashValue = null;
	private $mediaMD5 = null;
	private $owner = null;
	private $locality = null;
	private $occid = null;
	private $tid = null;
	private $sourceIdentifier = null;
	private $rights = null;
	private $accessRights = null;
	private $copyright = null;
	private $anatomy = null;
	private $notes = null;
	private $sortSeq = 50;
	private $sortOccurrence = 5;

	private $imgLgUrl = null;
	private $imgWebUrl = null;
	private $imgTnUrl = null;
	private $archiveUrl = null;
	private $sourceUrl = null;
	private $referenceUrl = null;

	private $activeImgId = 0;
	private $errArr = array();
	private $context = null;

	public function __construct($conn = null){
		if($conn){
			$this->conn = $conn;
			$this->connShared = true;
		}
		else $this->conn = MySQLiConnectionFactory::getCon('write');
		$this->imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($this->imageRootPath,-1) != "/") $this->imageRootPath .= "/";
		$this->imageRootUrl = $GLOBALS["imageRootUrl"];
		if(substr($this->imageRootUrl,-1) != "/") $this->imageRootUrl .= "/";
		if(array_key_exists('imgTnWidth',$GLOBALS)){
			$this->tnPixWidth = $GLOBALS['imgTnWidth'];
		}
		if(array_key_exists('imgWebWidth',$GLOBALS)){
			$this->webPixWidth = $GLOBALS['imgWebWidth'];
		}
		if(array_key_exists('imgLgWidth',$GLOBALS)){
			$this->lgPixWidth = $GLOBALS['imgLgWidth'];
		}
		if(array_key_exists('imgFileSizeLimit',$GLOBALS)){
			$this->webFileSizeLimit = $GLOBALS['imgFileSizeLimit'];
		}
		//Needed to avoid 403 errors
		ini_set('user_agent','Mozilla/4.0 (compatible; MSIE 6.0)');
		$opts = array(
			'http'=>array(
				'user_agent' => $GLOBALS['DEFAULT_TITLE'],
				'method'=>"GET",
				'header'=> implode("\r\n", array('Content-type: text/plain;'))
			)
		);
		$this->context = stream_context_create($opts);
		ini_set('memory_limit','512M');
	}

	public function __destruct(){
		if($this->sourceGdImg) imagedestroy($this->sourceGdImg);
		if(!$this->connShared){
			if(!($this->conn === null)){
				$this->conn->close();
				$this->conn = null;
			}
		}
	}

	public function reset(){
		if($this->sourceGdImg) imagedestroy($this->sourceGdImg);
		$this->sourceGdImg = null;

		$this->sourcePath = '';
		$this->imgName = '';
		$this->imgExt = '';

		$this->sourceWidth = 0;
		$this->sourceHeight = 0;

		$this->imgTnUrl = null;
		$this->imgWebUrl = null;
		$this->imgLgUrl = null;
		$this->archiveUrl = null;
		$this->sourceUrl = null;
		$this->referenceUrl = null;

		//Image metadata
		$this->caption = null;
		$this->photographer = null;
		$this->photographerUid = null;
		$this->format = null;
		$this->hashFunction = null;
		$this->hashValue = null;
		$this->mediaMD5 = null;
		$this->owner = null;
		$this->locality = null;
		$this->occid = null;
		$this->tid = null;
		$this->sourceIdentifier = null;
		$this->rights = null;
		$this->accessRights = null;
		$this->copyright = null;
		$this->anatomy = null;
		$this->notes = null;
		$this->sortSeq = 50;
		$this->sortOccurrence = 5;

		$this->activeImgId = 0;

		unset($this->errArr);
		$this->errArr = array();

	}

	public function uploadImage($imgFile = 'imgfile'){
		if($this->targetPath){
			if(file_exists($this->targetPath)){
				$imgFileName = basename($_FILES[$imgFile]['name']);
				$fileName = $this->cleanFileName($imgFileName);
				if(move_uploaded_file($_FILES[$imgFile]['tmp_name'], $this->targetPath.$fileName.$this->imgExt)){
					$this->sourcePath = $this->targetPath.$fileName.$this->imgExt;
					$this->imgName = $fileName;
					if($this->testOrientation) $this->evaluateOrientation();
					return true;
				}
				else{
					$this->errArr[] = 'FATAL ERROR: unable to move image to target ('.$this->targetPath.$fileName.$this->imgExt.')';
				}
			}
			else{
				$this->errArr[] = 'FATAL ERROR: Target path does not exist in uploadImage method ('.$this->targetPath.')';
				//trigger_error('Path does not exist in uploadImage method',E_USER_ERROR);
			}
		}
		else{
			$this->errArr[] = 'FATAL ERROR: Path NULL in uploadImage method';
			//trigger_error('Path NULL in uploadImage method',E_USER_ERROR);
		}
		return false;
	}

	public function copyImageFromUrl(){
		//Returns full path
		if(!$this->sourceUrl){
			$this->errArr[] = 'FATAL ERROR: Image source uri NULL in copyImageFromUrl method';
			//trigger_error('Image source uri NULL in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		if(!$this->uriExists($this->sourceUrl)){
			$this->errArr[] = 'FATAL ERROR: Image source file ('.$this->sourceUrl.') does not exist in copyImageFromUrl method';
			//trigger_error('Image source file ('.$sourceUri.') does not exist in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		if(!$this->targetPath){
			$this->errArr[] = 'FATAL ERROR: Image target url NULL in copyImageFromUrl method';
			//trigger_error('Image target url NULL in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		if(!file_exists($this->targetPath)){
			$this->errArr[] = 'FATAL ERROR: Image target file ('.$this->targetPath.') does not exist in copyImageFromUrl method';
			//trigger_error('Image target file ('.$this->targetPath.') does not exist in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		//Clean and copy file
		$fileName = $this->cleanFileName($this->sourceUrl);
		$origFileName = $fileName.'_orig'.$this->imgExt;
		if(copy($this->sourceUrl, $this->targetPath.$origFileName, $this->context)){
			$this->sourcePath = $this->targetPath.$origFileName;
			$this->imgName = $fileName;
			$this->imgLgUrl = $origFileName;
			if($this->imgWebUrl){
				$webFileName = $fileName.'_web'.$this->imgExt;
				if(copy($this->imgWebUrl, $this->targetPath.$webFileName, $this->context)){
					$this->imgWebUrl = $webFileName;
				}
			}
			if($this->imgTnUrl){
				$tnFileName = $fileName.'_tn'.$this->imgExt;
				if(copy($this->imgTnUrl, $this->targetPath.$tnFileName, $this->context)){
					$this->imgTnUrl = $tnFileName;
				}
			}
			if($this->testOrientation) $this->evaluateOrientation();
			return true;
		}
		$this->errArr[] = 'FATAL ERROR: Unable to copy image to target ('.$this->targetPath.$fileName.$this->imgExt.')';
		return false;
	}

	public function parseUrl($url){
		$status = false;
		$url = str_replace(' ','%20',$url);
		//If image is relative, add proper domain
		if(substr($url,0,1) == '/'){
			if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
				$url = $GLOBALS['imageDomain'].$url;
			}
			else{
				$url = $this->getDomainUrl().$url;
			}
		}

		$this->sourceUrl = $url;
		if($this->uriExists($url)){
			$this->sourcePath = $url;
			$this->imgName = $this->cleanFileName($url);
			if($this->testOrientation) $this->evaluateOrientation();
			$status = true;
		}
		else{
			$this->errArr[] = 'FATAL ERROR: image url does not exist ('.$url.')';
		}
		return $status;
	}

	public function cleanFileName($fPath){
		$fName = $fPath;
		if(strtolower(substr($fPath,0,7)) == 'http://' || strtolower(substr($fPath,0,8)) == 'https://'){
			//Image is URL
			if($dimArr = $this->getImgDim($fPath)){
				$this->sourceWidth = $dimArr[0];
				$this->sourceHeight = $dimArr[1];
			}

			if($pos = strrpos($fName,'/')){
				$fName = substr($fName,$pos+1);
			}
		}

		//Continue cleaning and parsing file name and extension
		if(strpos($fName,'?')) $fName = substr($fName,0,strpos($fName,'?'));
		if($p = strrpos($fName,'.')){
			$this->sourceIdentifier = 'filename: '.$fName;
			if(!$this->imgExt) $this->imgExt = strtolower(substr($fName,$p));
			$fName = substr($fName,0,$p);
		}

		$fName = str_replace(".","",$fName);
		$fName = str_replace(array("%20","%23"," ","__"),"_",$fName);
		$fName = str_replace("__","_",$fName);
		$fName = str_replace(array(chr(231),chr(232),chr(233),chr(234),chr(260)),"a",$fName);
		$fName = str_replace(array(chr(230),chr(236),chr(237),chr(238)),"e",$fName);
		$fName = str_replace(array(chr(239),chr(240),chr(241),chr(261)),"i",$fName);
		$fName = str_replace(array(chr(247),chr(248),chr(249),chr(262)),"o",$fName);
		$fName = str_replace(array(chr(250),chr(251),chr(263)),"u",$fName);
		$fName = str_replace(array(chr(264),chr(265)),"n",$fName);
		$fName = preg_replace("/[^a-zA-Z0-9\-_]/", "", $fName);
		$fName = trim($fName,' _-');

		if(strlen($fName) > 30) {
			$fName = substr($fName,0,30);
		}
		$fName .= '_'.time();
		//Test to see if target images exist (can happen batch loading images with similar names)
		if($this->targetPath){
			//Check and see if file already exists, if so, rename filename until it has a unique name
			$tempFileName = $fName;
			$cnt = 0;
			while(file_exists($this->targetPath.$tempFileName.'_tn.jpg')){
				$tempFileName = $fName.'_'.$cnt;
				$cnt++;
			}
			if($cnt) $fName = $tempFileName;
		}

		//Returns file name without extension
		return $fName;
	}

	public function setTargetPath($subPath = ''){
		$path = $this->imageRootPath;
		$url = $this->imageRootUrl;
		if(!$path){
			$this->errArr[] = 'FATAL ERROR: Path empty in setTargetPath method';
			trigger_error('Path empty in setTargetPath method',E_USER_ERROR);
			return false;
		}
		if($subPath){
			$badChars = array(' ',':','.','"',"'",'>','<','%','*','|','?');
			$subPath = str_replace($badChars, '', $subPath);
		}
		else{
			$subPath = 'misc/'.date('Ym').'/';
		}
		if(substr($subPath,-1) != '/') $subPath .= '/';

		$path .= $subPath;
		$url .= $subPath;
		if(!file_exists($path)){
			if(!mkdir( $path, 0777, true )){
				$this->errArr[] = 'FATAL ERROR: Unable to create directory: '.$path;
				//trigger_error('Unable to create directory: '.$path,E_USER_ERROR);
				return false;
			}
		}
		$this->targetPath = $path;
		$this->urlBase = $url;
		return true;
	}

	public function processImage(){
		if(!$this->imgName){
			$this->errArr[] = 'FATAL ERROR: Image file name null in processImage function';
			//trigger_error('Image file name null in processImage function',E_USER_ERROR);
			return false;
		}

		//Create thumbnail
		if(!$this->imgTnUrl && $this->createThumbnailDerivative){
			if($this->createNewImage('_tn',$this->tnPixWidth,70)){
				$this->imgTnUrl = $this->imgName.'_tn.jpg';
			}
		}

		//Get image variable
		if(!$this->sourceWidth || !$this->sourceHeight){
			list($this->sourceWidth, $this->sourceHeight) =  $this->getImgDim(str_replace(' ', '%20', $this->sourcePath));
		}
		$this->setSourceFileSize();

		//Create large image
		if($this->mapLargeImg && !$this->imgLgUrl){
			if($this->sourceWidth > ($this->webPixWidth*1.2) || $this->sourceFileSize > $this->webFileSizeLimit){
				//Source image is wide enough can serve as large image, or it's too large to serve as basic web image
				if(substr($this->sourcePath,0,4)=='http') {
					$this->imgLgUrl = $this->sourcePath;
				}
				else{
					if($this->sourceWidth < ($this->lgPixWidth*1.2)){
						//Image width is small enough to serve as large image
						if(copy($this->sourcePath,$this->targetPath.$this->imgName.'_lg'.$this->imgExt, $this->context)){
							$this->imgLgUrl = $this->imgName.'_lg'.$this->imgExt;
						}
					}
					else{
						if($this->createNewImage('_lg',$this->lgPixWidth)){
							$this->imgLgUrl = $this->imgName.'_lg.jpg';
						}
					}
				}
			}
		}

		//Create web url
		if(!$this->imgWebUrl && $this->createWebDerivative){
			if($this->sourceWidth < ($this->webPixWidth*1.2) && $this->sourceFileSize < $this->webFileSizeLimit){
				//Source image width and file size is small enough to serve as web image
				if(strtolower(substr($this->sourcePath,0,4)) == 'http'){
					if(copy($this->sourcePath, $this->targetPath.$this->imgName.$this->imgExt, $this->context)){
						$this->imgWebUrl = $this->imgName.$this->imgExt;
					}
				}
				elseif($this->imgLgUrl) $this->imgWebUrl = $this->imgLgUrl;
				else $this->imgWebUrl = basename($this->sourcePath);
			}
			else{
				//Image width or file size is too large
				$newWidth = ($this->sourceWidth<($this->webPixWidth*1.2)?$this->sourceWidth:$this->webPixWidth);
				$this->createNewImage('',$newWidth);
				$this->imgWebUrl = $this->imgName.'.jpg';
			}
		}

		$status = $this->insertImage();
		return $status;
	}

	public function createNewImage($subExt, $targetWidth, $qualityRating = 0, $targetPathOverride = ''){
		global $useImageMagick;
		$status = false;
		if($this->sourcePath){
			if(!$qualityRating) $qualityRating = $this->jpgCompression;

			if($useImageMagick) {
				// Use ImageMagick to resize images
				$status = $this->createNewImageImagick($subExt,$targetWidth,$qualityRating,$targetPathOverride);
			}
			elseif(extension_loaded('gd') && function_exists('gd_info')) {
				// GD is installed and working
				$status = $this->createNewImageGD($subExt,$targetWidth,$qualityRating,$targetPathOverride);
			}
			else{
				// Neither ImageMagick nor GD are installed
				$this->errArr[] = 'ERROR: No appropriate image handler for image conversions';
			}
		}
		else{
			//$this->errArr[] = 'ERROR: Empty sourcePath or failure in uriExist test (sourcePath: '.$this->sourcePath.')';
		}
		return $status;
	}

	private function createNewImageImagick($subExt,$newWidth,$qualityRating,$targetPathOverride){
		$targetPath = $targetPathOverride;
		if(!$targetPath) $targetPath = $this->targetPath.$this->imgName.$subExt.$this->imgExt;
		$ct;
		if($newWidth < 300){
			$ct = system('convert '.$this->sourcePath.' -thumbnail '.$newWidth.'x'.($newWidth*1.5).' '.$targetPath, $retval);
		}
		else{
			$ct = system('convert '.$this->sourcePath.' -resize '.$newWidth.'x'.($newWidth*1.5).($qualityRating?' -quality '.$qualityRating:'').' '.$targetPath, $retval);
		}
		if(file_exists($targetPath)){
			return true;
		}
		else{
			$this->errArr[] = 'ERROR: Image failed to be created in Imagick function (target path: '.$targetPath.')';
		}
		return false;
	}

	private function createNewImageGD($subExt, $newWidth, $qualityRating, $targetPathOverride){
		$status = false;

		if(!$this->sourceWidth || !$this->sourceHeight){
			list($this->sourceWidth, $this->sourceHeight) = $this->getImgDim(str_replace(' ', '%20', $this->sourcePath));
		}
		if($this->sourceWidth){
			$newHeight = round($this->sourceHeight*($newWidth/$this->sourceWidth));
			if($newWidth > $this->sourceWidth){
				$newWidth = $this->sourceWidth;
				$newHeight = $this->sourceHeight;
			}
			if(!$this->sourceGdImg){
				if($this->imgExt == '.gif'){
			  		$this->sourceGdImg = imagecreatefromgif($this->sourcePath);
					if(!$this->format) $this->format = 'image/gif';
				}
				elseif($this->imgExt == '.png'){
			  		$this->sourceGdImg = imagecreatefrompng($this->sourcePath);
					if(!$this->format) $this->format = 'image/png';
				}
				else{
					//JPG assumed
					$opts = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
						)
					);
					$context = stream_context_create($opts);
					if($file = file_get_contents($this->sourcePath, false, $context)){
						$this->sourceGdImg = imagecreatefromstring($file);
						if(!$this->format) $this->format = 'image/jpeg';
					}
				}
			}

			if($this->sourceGdImg){
				$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
				//imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
				imagecopyresized($tmpImg,$this->sourceGdImg,0,0,0,0,$newWidth,$newHeight,$this->sourceWidth,$this->sourceHeight);

				//Irrelevant of import image, output JPG
				$targetPath = $targetPathOverride;
				if(!$targetPath) $targetPath = $this->targetPath.$this->imgName.$subExt.'.jpg';
				if($qualityRating){
					$status = imagejpeg($tmpImg, $targetPath, $qualityRating);
				}
				else{
					$status = imagejpeg($tmpImg, $targetPath);
				}

				if(!$status){
					$this->errArr[] = 'ERROR: failed to create images using target path ('.$targetPath.')';
				}

				imagedestroy($tmpImg);
			}
			else{
				$this->errArr[] = 'ERROR: unable to create image object in createNewImageGD method (sourcePath: '.$this->sourcePath.')';
			}
		}
		else{
			$this->errArr[] = 'ERROR: unable to get source image width ('.$this->sourcePath.')';
		}
		return $status;
	}

	public function insertImage(){
		$status = false;
		if($this->imgLgUrl || $this->imgWebUrl){
			$urlBase = $this->getUrlBase();
			if($this->imgWebUrl && strtolower(substr($this->imgWebUrl,0,7)) != 'http://' && strtolower(substr($this->imgWebUrl,0,8)) != 'https://'){
				$this->imgWebUrl = $urlBase.$this->imgWebUrl;
			}
			if($this->imgTnUrl && strtolower(substr($this->imgTnUrl,0,7)) != 'http://' && strtolower(substr($this->imgTnUrl,0,8)) != 'https://'){
				$this->imgTnUrl = $urlBase.$this->imgTnUrl;
			}
			if($this->imgLgUrl && strtolower(substr($this->imgLgUrl,0,7)) != 'http://' && strtolower(substr($this->imgLgUrl,0,8)) != 'https://'){
				$this->imgLgUrl = $urlBase.$this->imgLgUrl;
			}

			//If is an occurrence image, get tid from occurrence
			if(!$this->tid && $this->occid){
				$sql1 = 'SELECT tidinterpreted FROM omoccurrences WHERE tidinterpreted IS NOT NULL AND occid = '.$this->occid;
				$rs1 = $this->conn->query($sql1);
				if($r1 = $rs1->fetch_object()){
					$this->tid = $r1->tidinterpreted;
				}
				$rs1->free();
			}

			//Save currently loaded record
			$guid = UuidFactory::getUuidV4();
			$sql = 'INSERT INTO images (tid, url, thumbnailurl, originalurl, archiveUrl, sourceurl, referenceUrl, photographer, photographeruid, format, caption, owner,
				locality, occid, anatomy, notes, username, sortsequence, sortoccurrence, sourceIdentifier, rights, accessrights, copyright, hashFunction, hashValue, mediaMD5, recordID)
				VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
			if($stmt = $this->conn->prepare($sql)) {
				$userName = $this->cleanInStr($GLOBALS['USERNAME']);
				if($stmt->bind_param('isssssssissssisssiissssssss', $this->tid, $this->imgWebUrl, $this->imgTnUrl, $this->imgLgUrl, $this->archiveUrl, $this->sourceUrl, $this->referenceUrl,
					$this->photographer, $this->photographerUid, $this->format, $this->caption, $this->owner, $this->locality, $this->occid, $this->anatomy,
					$this->notes, $userName, $this->sortSeq, $this->sortOccurrence, $this->sourceIdentifier, $this->rights, $this->accessRights, $this->copyright,
					$this->hashFunction, $this->hashValue, $this->mediaMD5, $guid)){
					$stmt->execute();
					if($stmt->affected_rows == 1){
						$status = true;
						$this->activeImgId = $stmt->insert_id;
					}
					else $this->errArr[] = 'ERROR adding image: '.$stmt->error;
					$stmt->close();
				}
				else $this->errArr[] = 'ERROR binding parameters for image insert: '.$this->conn->error;
			}
			else $this->errArr[] = 'ERROR preparing statement for image insert: '.$this->conn->error;
		}
		return $status;
	}

	public function getUrlBase(){
		$urlBase = $this->urlBase;
		//If central images are on remote server and new ones stored locally, then we need to use full domain
		//e.g. this portal is sister portal to central portal
		if($GLOBALS['imageDomain']) $urlBase = $this->getDomainUrl().$urlBase;
		return $urlBase;
	}

	public function deleteImage($imgIdDel, $removeImg){
		if(is_numeric($imgIdDel)){
			$imgUrl = ''; $imgThumbnailUrl = ''; $imgOriginalUrl = ''; $occid = 0;
			$sqlQuery = 'SELECT url, thumbnailUrl, originalUrl, tid, occid FROM images WHERE (imgid = '.$imgIdDel.')';
			$rs = $this->conn->query($sqlQuery);
			if($r = $rs->fetch_object()){
				$imgUrl = $r->url;
				$imgThumbnailUrl = $r->thumbnailUrl;
				$imgOriginalUrl = $r->originalUrl;
				$this->tid = $r->tid;
				$occid = $r->occid;
			}
			$rs->free();

			if($occid){
				//Remove any OCR text blocks linked to the image
				$this->conn->query('DELETE FROM specprocessorrawlabels WHERE (imgid = '.$imgIdDel.')');
			}
			//Remove image tags
			$this->conn->query('DELETE FROM imagetag WHERE (imgid = '.$imgIdDel.')');

			$sql = "DELETE FROM images WHERE (imgid = ".$imgIdDel.')';
			//echo $sql;
			if($this->conn->query($sql)){
				if($removeImg){
					//Search url with and without local domain name
					$domain = $this->getDomainUrl();
					if(stripos($imgUrl,$domain) === 0) $imgUrl = substr($imgUrl,strlen($domain));

					//Delete image from server
					$imgDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
					if(substr($imgDelPath,0,4) != 'http'){
						if(!unlink($imgDelPath)){
							$this->errArr[] = 'WARNING: Deleted records from database successfully but FAILED to delete image from server (path: '.$imgDelPath.')';
						}
					}

					//Delete thumbnail image
					if($imgThumbnailUrl){
						if(stripos($imgThumbnailUrl,$domain) === 0){
							$imgThumbnailUrl = substr($imgThumbnailUrl,strlen($domain));
						}
						$imgTnDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgThumbnailUrl);
						if(file_exists($imgTnDelPath) && substr($imgTnDelPath,0,4) != 'http') unlink($imgTnDelPath);
					}

					//Delete large version of image
					if($imgOriginalUrl){
						if(stripos($imgOriginalUrl,$domain) === 0){
							$imgOriginalUrl = substr($imgOriginalUrl,strlen($domain));
						}
						$imgOriginalDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgOriginalUrl);
						if(file_exists($imgOriginalDelPath) && substr($imgOriginalDelPath,0,4) != 'http') unlink($imgOriginalDelPath);
					}
				}
			}
			else{
				$this->errArr[] = 'ERROR: Unable to delete image record: '.$this->conn->error;
				return false;
				//echo 'SQL: '.$sql;
			}
			return true;
		}
		return false;
	}

	public function insertImageTags($reqArr){
		$status = true;
		if($this->activeImgId){
			// Find any tags providing classification of the image and insert them
			$kArr = $this->getImageTagValues();
			foreach($kArr as $key => $description) {
				if(array_key_exists("ch_$key",$reqArr)) {
					$sql = "INSERT INTO imagetag (imgid,keyvalue) VALUES (?,?) ";
					$stmt = $this->conn->stmt_init();
					$stmt->prepare($sql);
					if($stmt){
						$stmt->bind_param('is',$this->activeImgId,$key);
						if(!$stmt->execute()){
							$status = false;
							$this->errArr[] = "Warning: Failed to add image tag [$key] for $this->activeImgId.  " . $stmt->error;
						}
						$stmt->close();
					}
				}
			}
		}
		return $status;
	}

	private function getImageTagValues($lang='en') {
	   $returnArr = Array();
	   switch ($lang) {
		  case 'en':
		  default:
		   $sql = "select tagkey, description_en from imagetagkey order by sortorder";
	   }
	   $stmt = $this->conn->stmt_init();
	   $stmt->prepare($sql);
	   if ($stmt) {
		  $stmt->bind_result($key,$desc);
		  $stmt->execute();
		  while ($stmt->fetch()) {
			 $returnArr[$key]=$desc;
		  }
		  $stmt->close();
	   }
	   return $returnArr;
	}

	//Setter and Getter
	public function getActiveImgId(){
		return $this->activeImgId;
	}

	public function getImageRootPath(){
		return $this->imageRootPath;
	}

	public function getImageRootUrl(){
		return $this->imageRootUrl;
	}

	public function getSourcePath(){
		return $this->sourcePath;
	}

	public function getImgName(){
		return $this->imgName;
	}

	public function getImgExt(){
		return $this->imgExt;
	}

	public function getSourceWidth(){
		return $this->sourceWidth;
	}

	public function getSourceHeight(){
		return $this->sourceHeight;
	}

	public function getTnPixWidth(){
		return $this->tnPixWidth;
	}

	public function getWebPixWidth(){
		return $this->webPixWidth;
	}

	public function getLgPixWidth(){
		return $this->lgPixWidth;
	}

	public function getWebFileSizeLimit(){
		return $this->webFileSizeLimit;
	}

	public function setTestOrientation($bool){
		if($bool) $this->testOrientation = true;
		else $this->testOrientation = false;
	}

	public function setMapLargeImg($t){
		$this->mapLargeImg = $t;
	}

	public function setCreateWebDerivative($bool){
		if($bool === false || $bool === 0) $this->createWebDerivative = false;
		else $this->createWebDerivative = true;
	}

	public function setCreateThumbnailDerivative($bool){
		if($bool === false || $bool === 0) $this->createThumbnailDerivative = false;
		else $this->createThumbnailDerivative = true;
	}

	public function setCaption($v){
		$this->caption = $this->cleanInStr($v);
	}

	public function setPhotographer($v){
		$this->photographer = $this->cleanInStr($v);
	}

	public function setPhotographerUid($v){
		$v = OccurrenceUtilities::verifyUser($v, $this->conn);
		$this->photographerUid = $v;
	}

	public function setImgLgUrl($v){
		$this->imgLgUrl = $this->cleanInStr($v);
	}

	public function setImgWebUrl($v){
		$this->imgWebUrl = $this->cleanInStr($v);
	}

	public function setImgTnUrl($v){
		$this->imgTnUrl = $this->cleanInStr($v);
	}

	public function setArchiveUrl($v){
		$this->archiveUrl = $this->cleanInStr($v);
	}

	public function setSourceUrl($v){
		$this->sourceUrl = $this->cleanInStr($v);
	}

	public function setReferenceUrl($v){
		$this->referenceUrl = $this->cleanInStr($v);
	}

	public function getTargetPath(){
		return $this->targetPath;
	}

	public function setFormat($v){
		$this->format = $this->cleanInStr($v);
	}

	public function getFormat(){
		return $this->format;
	}

	public function setHashFunction($v){
		$this->hashFunction = $this->cleanInStr($v);
	}

	public function setHashValue($v){
		$this->hashValue = $this->cleanInStr($v);
	}

	public function setMediaMD5($v){
		$this->mediaMD5 = $this->cleanInStr($v);
	}

	public function setOwner($v){
		$this->owner = $this->cleanInStr($v);
	}

	public function setLocality($v){
		$this->locality = $this->cleanInStr($v);
	}

	public function setOccid($v){
		if(is_numeric($v)){
			$this->occid = $v;
		}
	}

	public function setTid($v){
		if(is_numeric($v)){
			$this->tid = $v;
		}
	}

	public function getTid(){
		return $this->tid;
	}

	public function setAnatomy($v){
		$this->anatomy = $this->cleanInStr($v);
	}

	public function setNotes($v){
		$this->notes = $this->cleanInStr($v);
	}

	public function setSortSeq($v){
		if(is_numeric($v)) $this->sortSeq = $v;
	}

	public function setSortOccurrence($v){
		if(is_numeric($v)) $this->sortOccurrence = $v;
	}

	public function getSourceIdentifier(){
		return $this->sourceIdentifier;
	}
	public function setSourceIdentifier($value){
		if($this->sourceIdentifier) $this->sourceIdentifier = '; '.$this->sourceIdentifier;
		$this->sourceIdentifier = $this->cleanInStr($value).$this->sourceIdentifier;
	}

	public function getRights(){
		return $this->rights;
	}
	public function setRights($value){
		$this->rights = $this->cleanInStr($value);
	}

	public function getAccessRights(){
		return $this->accessRights;
	}
	public function setAccessRights($value){
		$this->accessRights = $this->cleanInStr($value);
	}

	public function setCopyright($v){
		$this->copyright = $this->cleanInStr($v);
	}

	public function getErrArr(){
		$retArr = $this->errArr;
		unset($this->errArr);
		$this->errArr = array();
		return $retArr;
	}

	public function getErrStr(){
		$retStr = implode('; ',$this->errArr);
		unset($this->errArr);
		$this->errArr = array();
		return $retStr;
	}

	//Misc functions
	private function evaluateOrientation(){
		if($this->sourcePath){
			if($exif = @exif_read_data($this->sourcePath)){
				$ort = '';
				if(isset($exif['Orientation'])) $ort = $exif['Orientation'];
				elseif(isset($exif['IFD0']['Orientation'])) $ort = $exif['IFD0']['Orientation'];
				elseif(isset($exif['COMPUTED']['Orientation'])) $ort = $exif['COMPUTED']['Orientation'];

				if($ort && $ort > 1){
					if(!$this->sourceGdImg){
						if($this->imgExt == '.gif') $this->sourceGdImg = imagecreatefromgif($this->sourcePath);
						elseif($this->imgExt == '.png') $this->sourceGdImg = imagecreatefrompng($this->sourcePath);
						else $this->sourceGdImg = imagecreatefromjpeg($this->sourcePath);
					}
					if($this->sourceGdImg){
						switch($ort){
							case 2: // horizontal flip
								//$image->flipImage($public,1);
							break;
							case 3: // 180 rotate left
								$this->sourceGdImg = imagerotate($this->sourceGdImg,180,0);
							break;
							case 4: // vertical flip
								//$image->flipImage($public,2);
							break;
							case 5: // vertical flip + 90 rotate right
								//$image->flipImage($public, 2);
								//$image->rotateImage($public, 270);
							break;
							case 6: // 90 rotate right (clockwise)
								$this->sourceGdImg = imagerotate($this->sourceGdImg,270,0);
							break;
							case 7: // horizontal flip + 90 rotate right
								//$image->flipImage($public,1);
								//$image->rotateImage($public, 270);
							break;
							case 8:	// 90 rotate left (counter-clockwise)
								$this->sourceGdImg = imagerotate($this->sourceGdImg,90,0);
							break;
						}
						$this->sourceWidth = imagesx($this->sourceGdImg);
						$this->sourceHeight = imagesy($this->sourceGdImg);
					}
				}
			}
		}
	}

	private function getDomainUrl(){
		$domainPath = 'http://';
		if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $domainPath = 'https://';
		$domainPath .= $_SERVER['SERVER_NAME'];
		if($_SERVER['SERVER_PORT'] && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) $domainPath .= ':'.$_SERVER['SERVER_PORT'];
		return $domainPath;
	}

	public function getSourceFileSize(){
		if(!$this->sourceFileSize) $this->setSourceFileSize();
		return $this->sourceFileSize;
	}

	private function setSourceFileSize(){
		if($this->sourcePath && !$this->sourceFileSize){
			if(strtolower(substr($this->sourcePath,0,7)) == 'http://' || strtolower(substr($this->sourcePath,0,8)) == 'https://'){
				$x = array_change_key_case(get_headers($this->sourcePath, 1),CASE_LOWER);
				if ( strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0 ) {
					if(isset($x['content-length'][1])) $this->sourceFileSize = $x['content-length'][1];
					elseif(isset($x['content-length'])) $this->sourceFileSize = $x['content-length'];
				}
				else {
					if(isset($x['content-length'])) $this->sourceFileSize = $x['content-length'];
				}
				/*
				$ch = curl_init($this->sourcePath);
				curl_setopt($ch, CURLOPT_NOBODY, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36');
				curl_setopt($ch, CURLOPT_HEADER, true);
				$data = curl_exec($ch);
				curl_close($ch);
				if($data === false) {
					return 0;
				}
				if(preg_match('/Content-Length: (\d+)/', $data, $matches)) {
				  $this->sourceFileSize = (int)$matches[1];
				}
				*/
			}
			else{
				$this->sourceFileSize = filesize($this->sourcePath);
			}
		}
		return $this->sourceFileSize;
	}

	public function uriExists($uri) {
		$exists = false;

		$urlPrefix = $this->getDomainUrl();

		if(strpos($uri,$urlPrefix) === 0){
			$uri = substr($uri,strlen($urlPrefix));
		}
		if(substr($uri,0,1) == '/'){
			if($GLOBALS['IMAGE_ROOT_URL'] && strpos($uri,$GLOBALS['IMAGE_ROOT_URL']) === 0){
				$fileName = str_replace($GLOBALS['IMAGE_ROOT_URL'],$GLOBALS['IMAGE_ROOT_PATH'],$uri);
				if(file_exists($fileName)) return true;
			}
			if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
				$uri = $GLOBALS['imageDomain'].$uri;
			}
			else{
				$uri = $urlPrefix.$uri;
			}
		}
		if(!$exists){
			//First test, won't download file body
			if(function_exists('curl_init')){
				// Version 4.x supported
				$handle = curl_init($uri);
				if(false === $handle){
					$exists = false;
				}
				curl_setopt($handle, CURLOPT_HEADER, true);
				curl_setopt($handle, CURLOPT_NOBODY, true);
				curl_setopt($handle, CURLOPT_FAILONERROR, true);
				curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36');
				curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
				$exists = curl_exec($handle);
				$retCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
				//print_r(curl_getinfo($handle)); exit;
				// $retcode >= 400 -> not found, $retcode = 200, found.
				if($retCode < 400) $exists = true;
				if($retCode == 403) $this->errArr[] = "403 Forbidden error (resource is not public or portal's IP address has been blocked)";
				if($exists){
					if(!$this->format){
						if($this->format = curl_getinfo($handle, CURLINFO_CONTENT_TYPE)){
							if(!$this->imgExt){
								if($this->format == 'image/gif') $this->imgExt = '.gif';
								elseif($this->format == 'image/png') $this->imgExt = '.png';
								elseif($this->format == 'image/jpeg') $this->imgExt = '.jpg';
							}
						}
					}
					if(!$this->sourceFileSize){
						if($fileSize = curl_getinfo($handle, CURLINFO_CONTENT_LENGTH_DOWNLOAD )){
							$this->sourceFileSize = $fileSize;
						}
					}
				}
				curl_close($handle);
			}
		}

		//Next try
		if(!$exists){
			if(file_exists($uri) || is_array(@getimagesize(str_replace(' ', '%20', $uri)))){
				return true;
			}
		}

		//One last check
		if(!$exists){
			if($testFH = @fopen($uri,'r')){
				$exists = true;
				fclose($testFH);
			}
		}
		//Test to see if file is an image
		//if(!@exif_imagetype($uri)) $exists = false;
		return $exists;
	}

	public static function getImgDim($imgUrl){
		if(!$imgUrl) return false;
		$imgDim = false;

		$urlPrefix = 'http://';
		if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = 'https://';
		$urlPrefix .= $_SERVER['SERVER_NAME'];
		if($_SERVER['SERVER_PORT'] && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) $urlPrefix .= ':'.$_SERVER['SERVER_PORT'];

		if(strpos($imgUrl,$urlPrefix.$GLOBALS['IMAGE_ROOT_URL']) === 0){
			$imgUrl = substr($imgUrl, strlen($urlPrefix));
		}
		if(substr($imgUrl,0,1) == '/'){
			if($GLOBALS['IMAGE_ROOT_URL'] && strpos($imgUrl,$GLOBALS['IMAGE_ROOT_URL']) === 0){
				$imgUrl = str_replace($GLOBALS['IMAGE_ROOT_URL'],$GLOBALS['IMAGE_ROOT_PATH'],$imgUrl);
			}
			$imgDim = @getimagesize($imgUrl);
		}
		if(!$imgDim){
			$imgDim = self::getImgDim1($imgUrl);
			if(!$imgDim) $imgDim = self::getImgDim2($imgUrl);
			if(!$imgDim) $imgDim = @getimagesize($imgUrl);
		}
		return $imgDim;
	}

	// Retrieve JPEG width and height without downloading/reading entire image.
	private static function getImgDim1($imgUrl) {
		$opts = array(
			'http' => array(
				'user_agent' => $GLOBALS['DEFAULT_TITLE'],
				'method'=>"GET",
				'header'=> implode("\r\n", array('Content-type: text/plain;'))
			),
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
			)
		);
		$context = stream_context_create($opts);
		if($handle = fopen($imgUrl, "rb", false, $context)){
			$new_block = NULL;
			if(!feof($handle)) {
				$new_block = fread($handle, 32);
				$i = 0;
				if($new_block[$i]=="\xFF" && $new_block[$i+1]=="\xD8" && $new_block[$i+2]=="\xFF" && $new_block[$i+3]=="\xE0") {
					$i += 4;
					if($new_block[$i+2]=="\x4A" && $new_block[$i+3]=="\x46" && $new_block[$i+4]=="\x49" && $new_block[$i+5]=="\x46" && $new_block[$i+6]=="\x00") {
						// Read block size and skip ahead to begin cycling through blocks in search of SOF marker
						$block_size = unpack("H*", $new_block[$i] . $new_block[$i+1]);
						$block_size = hexdec($block_size[1]);
						while(!feof($handle)) {
							$i += $block_size;
							$new_block .= fread($handle, $block_size);
							if(isset($new_block[$i]) && $new_block[$i]=="\xFF") {
								// New block detected, check for SOF marker
								$sof_marker = array("\xC0", "\xC1", "\xC2", "\xC3", "\xC5", "\xC6", "\xC7", "\xC8", "\xC9", "\xCA", "\xCB", "\xCD", "\xCE", "\xCF");
								if(in_array($new_block[$i+1], $sof_marker)) {
									// SOF marker detected. Width and height information is contained in bytes 4-7 after this byte.
									//$size_data = $new_block[$i+2] . $new_block[$i+3] . $new_block[$i+4] . $new_block[$i+5] . $new_block[$i+6] . $new_block[$i+7] . $new_block[$i+8];
									$size_data = null;
									for($x = 2; $x < 9; $x++){
										if(isset($new_block[$i+$x])) $size_data .= $new_block[$i+$x];
									}
									$unpacked = unpack("H*", $size_data);
									$unpacked = $unpacked[1];
									if(!is_array($unpacked) || count($unpacked) < 13) return false;
									$height = hexdec($unpacked[6] . $unpacked[7] . $unpacked[8] . $unpacked[9]);
									$width = hexdec($unpacked[10] . $unpacked[11] . $unpacked[12] . $unpacked[13]);
									return array($width, $height);
								} else {
									// Skip block marker and read block size
									$i += 2;
									$block_size = unpack("H*", $new_block[$i] . $new_block[$i+1]);
									$block_size = hexdec($block_size[1]);
								}
							}
							else {
								return FALSE;
							}
						}
					}
				}
			}
		}
		return FALSE;
	}

	private static function getImgDim2($imgUrl) {
		$curl = curl_init($imgUrl);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array( "Range: bytes=0-65536" ));
		//curl_setopt($curl, CURLOPT_HTTPHEADER, array( "Range: bytes=0-32768" ));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		$data = curl_exec($curl);
		curl_close($curl);
		$width = 0; $height = 0;

		$im = @imagecreatefromstring($data);
		if($im){
			$width = @imagesx($im);
			$height = @imagesy($im);
			imagedestroy($im);
		}
		if(!$width || !$height) return false;
		return array($width,$height);
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>