<?php
/*
 * Used by automatic nightly process and by the occurrence editor (/collections/editor/occurrenceeditor.php)
 */
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/classes/Encoding.php');

class SpecProcessorOcr extends Manager{

	private $tempPath;
	private $imgUrlLocal;
	private $deleteAllOcrFiles = 0;

	private $cropX = 0;
	private $cropY = 0;
	private $cropW = 1;
	private $cropH = 1;

	private $collid;
	private $specKeyPattern;
	private $ocrSource;

	function __construct() {
		parent::__construct(null,'write');
		$this->setTempPath();
	}

	function __destruct(){
		parent::__destruct();
		//unlink($this->imgUrlLocal);
	}

	public function ocrImageById($imgid, $target = 'tess', $getBest = 0, $sciName=''){
		$rawStr = '';
		if($imgUrl = $this->getImageUrl($imgid)){
			if($target == 'digi') $rawStr = $this->ocrImageViaDigiLeap($imgid);
			else $rawStr = $this->ocrImageByUrl($imgUrl, $getBest, $sciName);
		}
		return $rawStr;
	}

	private function ocrImageByUrl($imgUrl, $getBest = 0, $sciName=''){
		$rawStr = '';
		if($imgUrl){
			if($this->loadImage($imgUrl)){
				$this->cropImage();
				if($getBest){
					$rawStr = $this->getBestOCR($sciName);
				}
				else{
					$rawStr = $this->ocrImageViaTesseract();
				}
				if(!$rawStr) {
					//Check for and remove problematic boarder
					if($this->imageTrimBorder()){
						if($getBest){
							$rawStr = $this->getBestOCR($sciName);
						}
						else{
							$rawStr = $this->ocrImageViaTesseract();
						}
					}
					if(!$rawStr) $rawStr = 'Failed OCR return';
				}
				$rawStr = $this->cleanRawStr($rawStr);
				//Cleanup, remove image
				unlink($this->imgUrlLocal);
			}
			else{
				$err = 'ERROR: Unable to load image, URL: '.$imgUrl;
				$this->logOrEcho($err,1);
				$rawStr = 'ERROR';
			}
		}
		else{
			$err = 'ERROR: Empty URL';
			$this->logOrEcho($err,1);
			$rawStr = 'ERROR';
		}
		return $rawStr;
	}

	private function ocrImageViaTesseract($url = ""){
		global $TESSERACT_PATH;
		$retStr = '';
		if(!$url) $url = $this->imgUrlLocal;
		if($url){
			//OCR image, result text is output to $outputFile
			$output = array();
			$outputFile = substr($url,0,strlen($url)-4);
			if(isset($TESSERACT_PATH) && $TESSERACT_PATH){
				if(substr($TESSERACT_PATH,0,2) == 'C:'){
					//Full path to tesseract with quotes needed for Windows
					exec('"'.$TESSERACT_PATH.'" '.$url.' '.$outputFile,$output);
				}
				else{
					exec($TESSERACT_PATH.' '.$url.' '.$outputFile,$output);
				}
			}
			else{
				//If path is not set in the $symbini.php file, we assume a typial linux install
				exec('/usr/local/bin/tesseract '.$url.' '.$outputFile,$output);
			}

			//Obtain text from tesseract output file
			if(file_exists($outputFile.'.txt')){
				if($fh = fopen($outputFile.'.txt', 'r')){
					while (!feof($fh)) {
						$retStr .= $this->encodeString(fread($fh, 8192));
						//$retStr .= fread($fh, 8192);
					}
					fclose($fh);
				}
				unlink($outputFile.'.txt');
			}
			else{
				$this->logOrEcho("ERROR: Unable to locate output file",1);
			}
		}
		return $retStr;//$this->cleanRawStr($retStr);
	}

	//DigiLeap functions
	public function ocrImageViaDigiLeap($imgid){
		$ocrStr = '';
		if($imgUrl = $this->getImageUrl($imgid)){
			if($this->loadImage($imgUrl)){
				$this->cropImage();
				if($resArr = $this->getDigiLeapOcr($this->imgUrlLocal)){
					$ocrStr = $resArr['results'][0]['text'];
				}
				else{
					$ocrStr = $this->getErrorMessage();
				}
			}
		}
		return $ocrStr;
	}

	private function getDigiLeapOcr($imgUrl){
		$resJson = false;
		$url = 'http://3.89.120.132/ocr-labels';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		$loginStr = $GLOBALS['PORTAL_GUID'].':'.$this->getConfigAttribute('DigiLeapApiKey');
		curl_setopt($ch, CURLOPT_USERPWD, $loginStr);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		$headerArr = array (
			'Accept: application/json',
			'Content-Type: multipart/form-data'
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
		$cfile = new CURLFile($imgUrl, 'image/jpeg', basename($imgUrl));
		$postData = array(
			'labels' => '',
			'extract' => 'typewritten'
		);
		$postData['sheet'] = $cfile;
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		$resJson = curl_exec($ch);
		$retArr['retCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($retArr['retCode'] != 200){
			if(curl_errno($ch)) $this->errorMessage = 'FATAL CURL ERROR: '.curl_error($ch).' (#'.curl_errno($ch).') '.$retArr['retCode'];
			$this->errorMessage = 'Problem retrieving OCR, HTTP code: '.$retArr['retCode'];
			return false;
		}
		curl_close($ch);
		return json_decode(json_decode($resJson), true);
	}

	public function digiLeapIsActive(){
		if($this->getConfigAttribute('DigiLeapApiKey')) return true;
		return false;
	}

	//Misc OCR support functions
	private function getImageUrl($imgid){
		$retUrl = false;
		if(is_numeric($imgid)){
			$sql = 'SELECT url, originalurl FROM images WHERE imgid = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $imgid);
				$stmt->execute();
				$url = '';
				$stmt->bind_result($url, $retUrl);
				$stmt->fetch();
				$stmt->close();
				if(!$retUrl) $retUrl = $url;
			}
		}
		return $retUrl;
	}

	private function databaseRawStr($imgId,$rawStr,$notes,$source){
		if(is_numeric($imgId) && $rawStr){
			$rawStr = $this->cleanInStr($this->encodeString($rawStr));
			$score = '';
			if($rawStr == 'Failed OCR return') $score = 0;
			$sql = 'INSERT INTO specprocessorrawlabels(imgid,rawstr,notes,source,score) '.
				'VALUE ('.$imgId.',"'.$rawStr.'",'.
				($notes?'"'.$this->cleanInStr($notes).'"':'NULL').','.
				($source?'"'.$this->cleanInStr($source).'"':'NULL').','.
				($score?'"'.$this->cleanInStr($score).'"':'NULL').')';
			//echo 'SQL: '.$sql."\n";
			if($this->conn->query($sql)){
				return true;
			}
			else{
				$this->logOrEcho("ERROR: Unable to load fragment into database: ".$this->conn->error,1);
				$this->logOrEcho("SQL: ".$sql,2);
				return false;
			}
		}
	}

	private function loadImage($imgUrl){
		$status = false;
		if($imgUrl){
			if(substr($imgUrl,0,1)=="/"){
				if(array_key_exists("imageDomain",$GLOBALS) && $GLOBALS["imageDomain"]){
					//If there is an image domain name is set in symbini.php and url is relative,
					//then it's assumed that image is located on another server, thus add domain to url
					$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
				}
				else{
					$imgUrl = $this->getDomain().$imgUrl;
				}
			}
			//Set temp folder path and file names
			$ts = time();
			$this->imgUrlLocal = $this->tempPath.$ts.'_img.jpg';

			//Copy image to temp folder
			$status = copy($imgUrl,$this->imgUrlLocal);
		}
		return $status;
	}

	public function batchOcrUnprocessed($inCollStr,$procStatus = 'unprocessed',$limit = 0,$getBest = 0){
		//OCR all images with a status of "unprocessed" and change to "unprocessed/OCR"
		//Triggered automaticly (crontab) on a nightly basis
		if($inCollStr) {
			set_time_limit(600);
			ini_set('memory_limit','512M');

			//Get collection list
			$sql = 'SELECT DISTINCT collid, CONCAT_WS("-",institutioncode,collectioncode) AS instcode '.
				'FROM omcollections '.
				'WHERE collid IN('.$inCollStr.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$collArr[$r->collid] = $r->instcode;
			}
			$rs->free();

			//Batch OCR
			foreach($collArr as $collid => $instCode){
				$this->logOrEcho('Starting batch processing for '.$instCode);
				$sql = 'SELECT i.imgid, IFNULL(i.originalurl, i.url) AS url, o.sciName, i.occid '.
					'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
					'LEFT JOIN specprocessorrawlabels r ON i.imgid = r.imgid '.
					'WHERE (o.collid = '.$collid.') AND r.prlid IS NULL ';
				if($procStatus) $sql .= 'AND o.processingstatus = "unprocessed" ';
				if($limit) $sql .= 'LIMIT '.$limit;
				if($rs = $this->conn->query($sql)){
					$recCnt = 1;
					while($r = $rs->fetch_object()){
						$rawStr = $this->ocrImageByUrl($r->url,$getBest,$r->sciName);
						if($rawStr != 'ERROR'){
							$this->logOrEcho('#'.$recCnt.': image <a href="../editor/occurrenceeditor.php?occid='.$r->occid.'" target="_blank">'.$r->imgid.'</a> processed ('.date("Y-m-d H:i:s").')');
							$notes = '';
							$source = 'Tesseract: '.date('Y-m-d');
							$this->databaseRawStr($r->imgid,$rawStr,$notes,$source);
						}
						ob_flush();
						flush();
						$recCnt++;
					}
		 			$rs->free();
				}
			}
		}
	}

	//OCR upload functions
	public function harvestOcrText($postArr){
		$status = true;
		set_time_limit(3600);
		$this->collid = $postArr['collid'];
		$this->ocrSource = $postArr['ocrsource'];
		$this->specKeyPattern = $postArr['speckeypattern'];
		if(!$this->specKeyPattern){
			$this->errorMessage = 'ERROR loading OCR files: Specimen catalog number pattern missing';
			$this->logOrEcho($this->errorMessage);
			return false;
		}
		$sourcePath = '';
		if(array_key_exists('sourcepath',$postArr) && $postArr['sourcepath']){
			$sourcePath = $postArr['sourcepath'];
		}
		else{
			$this->deleteAllOcrFiles = 1;
			$sourcePath = $this->uploadOcrFile();
		}
		if(!$sourcePath){
			$this->errorMessage = 'ERROR loading OCR files: OCR source path is missing';
			$this->logOrEcho($this->errorMessage);
			return false;
		}
		if(substr($sourcePath,0,4) == 'http'){
			//http protocol, thus test for a valid page
			$headerArr = get_headers($sourcePath);
			if(!$headerArr){
				$this->errorMessage = 'ERROR loading OCR files: sourcePath returned bad headers ('.$sourcePath.')';
				$this->logOrEcho($this->errorMessage);
				return false;
			}
			preg_match('/http.+\s{1}(\d{3})\s{1}/i',$headerArr[0],$codeArr);
			if($codeArr[1] == '403'){
				$this->errorMessage = 'ERROR loading OCR files: sourcePath returned Forbidden ('.$sourcePath.')';
				$this->logOrEcho($this->errorMessage);
				return false;
			}
			if($codeArr[1] == '404'){
				$this->errorMessage = 'ERROR loading OCR files: sourcePath returned a page Not Found error ('.$sourcePath.')';
				$this->logOrEcho($this->errorMessage);
				return false;
			}
			if($codeArr[1] != '200'){
				$this->errorMessage = 'ERROR loading OCR files: sourcePath returned error code '.$codeArr[1].' ('.$sourcePath.')';
				$this->logOrEcho($this->errorMessage);
				return false;
			}
		}
		elseif(!file_exists($sourcePath)){
			$this->errorMessage = 'ERROR loading OCR files: sourcePath does not exist ('.$sourcePath.')';
			$this->logOrEcho($this->errorMessage);
			return false;
		}
		//Initiate processing
		if(substr($sourcePath,-1) != '/') $sourcePath .= '/';
		if(substr($sourcePath,0,4) == 'http'){
			$this->processOcrHtml($sourcePath);
		}
		else{
			$this->processOcrFolder($sourcePath);
		}
		$this->logOrEcho('Done loading OCR files ');


		return $status;
	}

	private function uploadOcrFile(){
		$retPath = '';
		if(!array_key_exists('ocrfile',$_FILES)){
			$this->errorMessage = 'ERROR loading OCR file: OCR file missing';
			$this->logOrEcho($this->errorMessage);
			return ;
		}
		if(!$this->tempPath){
			$this->errorMessage = 'ERROR loading OCR file: temp target path empty';
			$this->logOrEcho($this->errorMessage);
			return ;
		}
		$zipPath = $this->tempPath.'ocrupload.zip';
		if(file_exists($zipPath)) unlink($zipPath);
		if(move_uploaded_file($_FILES['ocrfile']['tmp_name'], $zipPath)){
			$zip = new ZipArchive;
			$res = $zip->open($zipPath);
			if($res === TRUE) {
				$extractPath = $this->tempPath.'ocrtext_'.time().'/';
				mkdir($extractPath);
				if($zip->extractTo($extractPath)){
					$retPath = $extractPath;
				}
				$zip->close();
				unlink($zipPath);
			}
			else{
				$this->errorMessage = 'ERROR unpacking OCR file: '.$res;
				$this->logOrEcho($this->errorMessage);
				return ;
			}
		}
		else{
			$this->errorMessage = 'ERROR loading OCR file: input file lacks zip extension';
			$this->logOrEcho($this->errorMessage);
			return ;
		}
		return $retPath;
	}

	private function processOcrHtml($sourcePath){
		$dom = new DOMDocument();
		$dom->loadHTMLFile($sourcePath);
		$aNodes= $dom->getElementsByTagName('a');
		$skipAnchors = array('Name','Last modified','Size','Description');
		foreach( $aNodes as $aNode ) {
			$fileName = $aNode->nodeValue;
			if(!in_array($fileName,$skipAnchors)){
				$fileExt = strtolower(substr($fileName,strrpos($fileName,'.')+1));
				if($fileExt){
					$this->logOrEcho("Processing OCR File: ".$fileName);
					if($fileExt == "txt"){
						$this->processOcrFile($fileName,$sourcePath);
					}
					else{
						$this->logOrEcho("ERROR: File skipped, not a supported OCR file with .txt extension: ".$sourcePath.$fileName);
					}
				}
				elseif(stripos($fileName,'Parent Dir') === false){
					$this->logOrEcho('New dir path: '.$sourcePath.$fileName);
					$this->processOcrHtml($sourcePath.$fileName.'/');
				}
			}
		}
	}

	private function processOcrFolder($sourcePath){
		//$this->logOrEcho("Processing: ".$sourcePath.$pathFrag);
		//Read directory and loop through OCR files
		if($dirFH = opendir($sourcePath)){
			while($fileName = readdir($dirFH)){
				if($fileName != "." && $fileName != ".." && $fileName != ".svn"){
					if(is_file($sourcePath.$fileName)){
						$this->logOrEcho("Processing OCR File: ".$fileName);
						$fileExt = strtolower(substr($fileName,strrpos($fileName,'.')));
						if($fileExt == ".txt"){
							$this->processOcrFile($fileName,$sourcePath);
						}
						else{
							$this->logOrEcho("ERROR: File skipped, not a supported OCR text file (.txt): ".$fileName);
						}
					}
					elseif(is_dir($sourcePath.$fileName)){
						$this->processOcrFolder($sourcePath.$fileName."/");
					}
				}
			}
			if($dirFH) closedir($dirFH);
		}
		else{
			$this->logOrEcho("ERROR: unable to access source directory: ".$sourcePath,1);
		}
		if($this->deleteAllOcrFiles) unlink($sourcePath);
	}

	private function processOcrFile($fileName,$sourcePath){
		$ocrCnt = 0;
		//$this->logOrEcho('Starting OCR text processing... ',1);
		if($rawTextFH = fopen($sourcePath.$fileName, 'r')){
			$rawStr = fread($rawTextFH, filesize($sourcePath.$fileName));
			fclose($rawTextFH);
			if($this->deleteAllOcrFiles) unlink($sourcePath.$fileName);
			//Grab specimen primary key (e.g. catalog number
			$catNumber = '';
			if(preg_match($this->specKeyPattern,$fileName,$matchArr)){
				if(array_key_exists(1,$matchArr) && $matchArr[1]){
					$catNumber = $matchArr[1];
				}
			}
			if($catNumber){
				//Grab image primary key (imgid)
				$imgArr = array();
				$sql = 'SELECT i.imgid, IFNULL(i.originalurl,i.url) AS url '.
					'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
					'WHERE (o.collid = '.$this->collid.') AND (o.catalognumber = "'.$this->cleanInStr($catNumber).'")';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$imgArr[$r->imgid] = $r->url;
				}
				$rs->free();
				if(!$imgArr){
					$fileBaseName = basename($sourcePath.$fileName, ".txt");
					if(strlen($fileBaseName)>4){
						$sql = 'SELECT i.imgid, IFNULL(i.originalurl,i.url) AS url '.
							'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
							'WHERE (o.collid = '.$this->collid.') AND ((i.originalurl LIKE "%/'.$this->cleanInStr($fileBaseName).'.jpg") OR (i.url LIKE "%/'.$this->cleanInStr($fileBaseName).'.jpg"))';
						$rs = $this->conn->query($sql);
						while($r = $rs->fetch_object()){
							$imgArr[$r->imgid] = $r->url;
						}
						$rs->free();
					}
				}
				if($imgArr){
					$imgId = key($imgArr);
					if(count($imgArr) > 1){
						// By default will link to first image, unless there is another image with exact name as OCR file
						$fileBaseName = basename($sourcePath.$fileName, ".txt");
						$imgIdOverride = '';
						foreach($imgArr as $k => $v){
							if(stripos($v,'/'.$fileBaseName.'.') || stripos($v,'/'.$fileBaseName.'_lg.')){
								$imgIdOverride= $k;
								break;
							}
							elseif(stripos($v,'/'.$fileBaseName.'_')){
								$imgIdOverride= $k;
							}
						}
						if($imgIdOverride) $imgId = $imgIdOverride;
					}
					//Process and database OCR string
					if($this->databaseRawStr($imgId,$rawStr,'',$this->ocrSource.': '.date('Y-m-d'))){
						if(file_exists($sourcePath.$fileName)) unlink($sourcePath.$fileName);
						$ocrCnt++;
					}
				}
				else{
					$this->logOrEcho('ERROR: unable locate specimen image (catalog #: '.$catNumber.')',1);
				}
			}
			else{
				$this->logOrEcho('ERROR: unable to extract catalog number ('.$fileName.' using '.$this->specKeyPattern.')',1);
			}
		}
		else{
			$this->logOrEcho('ERROR: unable to read rawOcr file: '.$fileName,1);
		}
	}

	//Image manipulations and adjustments
	private function cropImage(){
		$status = false;
		if($this->cropX || $this->cropY || $this->cropW < 1 || $this->cropH < 1){
			// Create image instances
			try{
				if($img = imagecreatefromjpeg($this->imgUrlLocal)){
					$imgW = imagesx($img);
					$imgH = imagesy($img);
					if(($this->cropX + $this->cropW) > 1) $this->cropW = 1 - $this->cropX;
					if(($this->cropY + $this->cropH) > 1) $this->cropH = 1 - $this->cropY;
					$pX = $imgW*$this->cropX;
					$pY = $imgH*$this->cropY;
					$pW = $imgW*$this->cropW;
					$pH = $imgH*$this->cropH;
					$dest = imagecreatetruecolor($pW,$pH);

					// Copy image
					if(imagecopy($dest,$img,0,0,$pX,$pY,$pW,$pH)){
						//$status = imagejpeg($dest,str_replace('_img.jpg','_crop.jpg',$this->imgUrlLocal));
						$status = imagejpeg($dest,$this->imgUrlLocal);
					}
					imagedestroy($dest);
					imagedestroy($img);
				}
			}
			catch(Exception $e){
				//echo 'Caught exception: '.$e->getMessage();
			}
		}
		return $status;
	}

	private function imageTrimBorder($c=0,$t=100){
		$img = imagecreatefromjpeg($this->imgUrlLocal);
		if (!is_numeric($c) || $c < 0 || $c > 255) {
			// Color ($c) not valid, thus grab the color from the top left corner and use that as default
			$rgb = imagecolorat($im, 2, 2); // 2 pixels in to avoid messy edges
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			$c = round(($r+$g+$b)/3); // average of rgb is good enough for a default
		}
		// if tolerance ($t) isn't a number between 0 - 255, set default
		if (!is_numeric($t) || $t < 0 || $t > 255) $t = 30;

		$width = imagesx($img);
		$height = imagesy($img);
		$bTop = 0;
		$bLeft = 0;
		$bBottom = $height - 1;
		$bRight = $width - 1;

		//top
		for(; $bTop < $height; $bTop=$bTop+2) {
			for($x = 0; $x < $width; $x=$x+2) {
				$rgb = imagecolorat($img, $x, $bTop);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if(($r < $c-$t || $r > $c+$t) && ($g < $c-$t || $g > $c+$t) && ($b < $c-$t || $b > $c+$t)){
					break 2;
				}
			}
		}

		// return false when all pixels are trimmed
		if ($bTop == $height) return false;

		// bottom
		for(; $bBottom >= 0; $bBottom=$bBottom-2) {
			for($x = 0; $x < $width; $x=$x+2) {
				$rgb = imagecolorat($img, $x, $bBottom);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if(($r < $c-$t || $r > $c+$t) && ($g < $c-$t || $g > $c+$t) && ($b < $c-$t || $b > $c+$t)){
					break 2;
				}
			}
		}

		// left
		for(; $bLeft < $width; $bLeft=$bLeft+2) {
			for($y = $bTop; $y <= $bBottom; $y=$y+2) {
				$rgb = imagecolorat($img, $bLeft, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if(($r < $c-$t || $r > $c+$t) && ($g < $c-$t || $g > $c+$t) && ($b < $c-$t || $b > $c+$t)){
					break 2;
				}
			}
		}

		// right
		for(; $bRight >= 0; $bRight=$bRight-2) {
			for($y = $bTop; $y <= $bBottom; $y=$y+2) {
				$rgb = imagecolorat($img, $bRight, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if(($r < $c-$t || $r > $c+$t) && ($g < $c-$t || $g > $c+$t) && ($b < $c-$t || $b > $c+$t)){
					break 2;
				}
			}
		}

		$bBottom++;
		$bRight++;

		$w = $bRight - $bLeft;
		$h = $bBottom - $bTop;
		if($w < $width || $h < $height){
			$dest = imagecreatetruecolor($w,$h);
			if(imagecopy($dest, $img, 0, 0, $bLeft, $bTop, $w, $h)){
				$status = imagejpeg($dest,$this->imgUrlLocal);
			}
			imagedestroy($dest);
			imagedestroy($img);
			return true;
		}
		return false;
	}

	//Roberts scoring and treatment functions
	private function getBestOCR($sciName = ''){
		//Base run
		$rawStr_base = $this->ocrImageViaTesseract();
		$score_base = $this->scoreOCR($rawStr_base, $sciName);
		$urlTemp = str_replace('.jpg','_f1.jpg',$this->imgUrlLocal);
		copy($this->imgUrlLocal,$urlTemp);
		$this->filterImage($urlTemp);
		$rawStr_treated = $this->ocrImageViaTesseract($urlTemp);
		$score_treated = $this->scoreOCR($rawStr_treated, $sciName);
		unlink($urlTemp);
		if($score_treated > $score_base) {
			$this->logOrEcho('Best Score applied',1);
			return $rawStr_treated;
		} else {
			return $rawStr_base;
		}
	}

	private function filterImage($url=''){
		$status = false;
		if(!$url) $url = $this->imgUrlLocal;
		if($img = imagecreatefromjpeg($url)){
			imagefilter($img,IMG_FILTER_GRAYSCALE);
			imagefilter($img,IMG_FILTER_BRIGHTNESS,10);
			imagefilter($img,IMG_FILTER_CONTRAST,1);
			$sharpenMatrix = array
			(
				array(-1.2, -1, -1.2),
				array(-1, 20, -1),
				array(-1.2, -1, -1.2)
			);
			// calculate the sharpen divisor
			$divisor = array_sum(array_map("array_sum", $sharpenMatrix));
			$offset = 0;
			// apply the matrix
			imageconvolution($img, $sharpenMatrix, $divisor, $offset);
			imagegammacorrect($img, 6, 1.0);
			$status = imagejpeg($img,$url);
			imagedestroy($img);
		}
		return $status;
	}

	private function scoreOCR($rawStr, $sciName = '') {
		$sLength = strlen($rawStr);
		if($sLength > 12) {
			$numWords = 0;
			$numBadLinesIncremented = false;
			$numBadLines = 1;
			$lines = explode("\n", $rawStr);
			foreach($lines as $line) {
				$line = trim($line);
				if(strlen($line) > 2) {
					$words = explode(" ", $line);
					foreach($words as $word) {
						if(strlen($word) > 2)
						{
							$goodChars = 0;
							$badChars = 0;
							foreach (count_chars($word, 1) as $i => $let) {
								if(($i > 47 && $i < 60) || ($i > 64 && $i < 91) || ($i > 96 && $i < 123) || $i == 176) {
									$goodChars++;
								}
								else if(($i < 44 || $i > 59) && !($i == 32 || $i == 35 || $i == 34 || $i == 39 || $i == 38 || $i == 40 || $i == 41 || $i == 61)) {
									$badChars++;
								}
							}
							if($goodChars > 3*$badChars) $numWords++;
						}
					}
				} else {
					if($numBadLines == 1) {
						if($numBadLinesIncremented) $numBadLines++;
						else $numBadLinesIncremented = true;
					} else $numBadLines++;
				}
			}
			$numGoodChars = 0;
			$numBadChars = 1;
			$numBadIncremented = false;
			foreach (count_chars($rawStr, 1) as $i => $val) {
				if(($i > 47 && $i < 60) || ($i > 64 && $i < 91) || ($i > 96 && $i < 123) || $i == 176) {
					$numGoodChars += $val;
				}
				else if(($i < 44 || $i > 59) && !($i == 32 || $i == 35 || $i == 34 || $i == 39 || $i == 38 || $i == 40 || $i == 41 || $i == 61)) {
					if($numBadChars == 1) {
						if($numBadIncremented) $numBadChars += $val;
						else {
							$numBadIncremented = true;
							$numBadChars += ($val-1);
						}
					} else $numBadChars += $val;
				}
			}
			return (($numWords*$numGoodChars)/($sLength*$numBadChars*$numBadLines)) + $this->findSciName($rawStr,$sciName);
		} else return 0;
	}

	private function findSciName($rawStr,$sciName) {
		$result = 0;
		if(strlen($sciName) > 0) {
			$words = explode(" ", $sciName);
			foreach($words as $word) {
				$wrdLen = strlen($word);
				if($wrdLen > 4) {
					if(stripos($rawStr,$word) !== false) $result += 0.3;
					else if(stripos($rawStr,str_replace("g", "p", $word)) !== false) $result += 0.2;
					else if(stripos($rawStr,str_replace("q", "p", $word)) !== false) $result += 0.2;
					else if(stripos($rawStr,str_replace("1", "l", $word)) !== false) $result += 0.2;
					else if(stripos($rawStr,str_replace("1", "i", $word)) !== false) $result += 0.2;
					else if(stripos($rawStr,str_replace("b", "h", $word)) !== false) $result += 0.2;
					else if(stripos($rawStr,str_replace("v", "y", $word)) !== false) $result += 0.2;
					else {
						$shrtWrd = substr($word, 1);
						if(stripos($rawStr,$shrtWrd) !== false) $result += 0.1;
						else if(stripos($rawStr,str_replace("I", "l", $shrtWrd)) !== false) $result += 0.1;
						else if(stripos($rawStr,str_replace("H", "ll", $shrtWrd)) !== false) $result += 0.1;
						else {
							$shrtWrd = substr($word, 0, $wrdLen-1);
							if(stripos($rawStr,$shrtWrd) !== false) $result += 0.1;
						}
					}
				}
			}
		}
		$goodWords =
			array (
					"collect", "fungi", "location", "locality", "along", "rock", "outcrop", "thallus", "pseudotsuga",
					"habitat", "det.", "determine",	"date", "long.", "latitude", "lat.", "shale", "laevis",
					"longitude", "elevation", "elev.", "quercus", "acer", "highway", "preserve", "hardwood",
					"road", "sandstone", " granit", "slope", "county", "near", "north", "forest", "Bungartz",
					"south", "east", "west", "stream", "Wetmore", "Nash", "Imsaug", "mile", "wood", "Esslinger",
					"Thomson", "Lendemer", "Johnson", "Harris", "Rosentretter", "Hodges", "Malachowski",
					"Tucker", "Egan", "Fink", "Shushan", "Sullivan", "Crane", "Schoknecht", "Marsh", "Lumbsch",
					"Trana", "Phillipe", "Landron", "Eyerdam", "Sharnoff", "Schuster", "Perlmutter", "Fryday",
					"Ohlsson", "Howard", "Taylor", "Arnot", "Gowan", "Dey", "Scotter", "Llano", "Keith", "Moberg",
					"Brako", "Ricklefs", "Darrow", "Macoun", "Barclay", "Culberson", "Alvarez", "ground", "ridge",
					"Wong", "Gould", "Shchepanek", "Wheeler", "Hasse", "Kashiwadani", "Havaas", "Weise", "Sheard",
					"Malme", "Hansen", "Erbisch", "Degelius", "Hafellner", "Reed", "Sweat", "Streimann", "McCune",
					"Ryan", "Brodo", "Bratt", "Burnett", "Knudsen", "Weber", "Vezda", "Langlois", "Follmann",
					"Buck", "Arnold", "Thaxter", "Armstrong", "Ahti", "Wheeler", "Britton", "Marble", "national",
					"January", "February", "March", "April", "May", "June", "July", "August", "September", "October",
					"November", "December", "Jan", "Feb", "Mar", "Apr", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
					"Calkins", "McHenry", "Schofield", "SHIMEK", "Hepp", "Talbot", "Riefner", "WAGHORNE", "Becking",
					"Nebecker", "Lebo", "Advaita", "DeBolt", "Austin", "Brouard", "Amtoft", "KIENER", "Kalb", "Hertel",
					"Clair", "Nee", "Boykin", "Sundberg", "Elix", "Santesson", "plant", "glade", "parish", "swamp",
					"Ilex", "Diospyros", "(Ach.)", "Leight", "river", "trail", "mount", "wall", "index", "pine",
					"vicinity", "durango", "madre", "stalk", "moss", "down", "some", "base", "alga", "brown", "punta",
					"dirt", "stand", "meter", "dead", "steep", "isla", "town", "station", "picea", "shore", "over",
					"attached", "apothecia", "spruce", "upper", "rosa", "rocky", "litter", "about", "shade", "coast",
					"tree", "live", "fork", "cliff", "amabilis", "facing", "junction", "white", "partial", "bare",
					"scrub", "then", "boulder", "conifer", "branch", "adjacent", "peak", "sonoran", "maple", "sample",
					"expose", "parashant", "pinyon", "growing", "fragment", "shrub", "below", "limestone", "scatter",
					"snag", "douglas", "secondary", "state", "point", "pass", "basalt", "edge", "year", "hemlock",
					"vigor", "association", "cedar", "community", "head", "cowlitz", "tsuga", "juniper", "monument",
					"between", "baker-snoqualmie", "menziesii", "heterophylla", "just", "wenatchee", "ranger", "grand",
					"mixed", "rhyolite", "plot", "growth", "desert", "spore", "sierra", "abies", "small", "gifford",
					"pinchot", "district", "pinus", "valley", "aspect", "santa", "open", "service", "degree", "above",
					"island", "side", "bark", "lake", "creek", "canyon", "from", "substrate", "slope", "with", "area"
			);
		foreach($goodWords as $goodWord) {
			if(stripos($rawStr,$goodWord) !== false) $result += 0.2;
		}
		//return $index*$result;
		return $result;
	}

	//General setters and getters
	public function setCropX($x){
		$this->cropX = $this->cleanImageValue($x);
	}
	public function setCropY($y){
		$this->cropY = $this->cleanImageValue($y);
	}
	public function setCropW($w){
		$this->cropW = $this->cleanImageValue($w);
	}
	public function setCropH($h){
		$this->cropH = $this->cleanImageValue($h);
	}

	private function cleanImageValue($d){
		if($d < 0) $d = 0;
		elseif($d > 1) $d = 1;
		return $d;
	}

	private function setTempPath(){
		$tempPath = 0;
		if(array_key_exists('tempDirRoot',$GLOBALS)){
			$tempPath = $GLOBALS['tempDirRoot'];
		}
		else{
			$tempPath = ini_get('upload_tmp_dir');
		}
		if(!$tempPath){
			$tempPath = $GLOBALS['SERVER_ROOT'];
			if(substr($tempPath,-1) != '/') $tempPath .= '/';
			$tempPath .= 'temp/';
		}
		if(substr($tempPath,-1) != '/') $tempPath .= '/';
		if(file_exists($tempPath.'symbocr/') || mkdir($tempPath.'symbocr/')){
			$tempPath .= 'symbocr/';
		}

		$this->tempPath = $tempPath;
	}

	/*public function addFilterVariable($k,$v){
		$this->filterArr[0][$k] = $v;
	}*/

	//Misc functions
	private function cleanRawStr($inStr){
		$retStr = trim($inStr);
		//$retStr = $this->encodeString($retStr);
		$retStr = Encoding::toUTF8($retStr);

		$retStr = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+[\s\t]*[\r\n]+/", "\n\n", $retStr);

		//replace commonly misinterpreted characters
		$replacements = array("/\." => "A.", "/-\\" => "A", "\X/" => "W", "\Y/" => "W", "`\‘i/" => "W", chr(96) => "'", chr(145) => "'", chr(146) => "'",
			"�" => "'", "�" => '"', "�" => '"', "�" => '"', chr(147) => '"', chr(148) => '"', chr(152) => '"', chr(239) => "�");
		$retStr = str_replace(array_keys($replacements), $replacements, $retStr);

		//replace \V and \W in longitudes and latitudes with W
		$retStr = preg_replace("/(\d\s?[".chr(176)."'\"])\s?\\\[VW]/", "\${1}W", $retStr, -1);
		return $retStr;
	}
}
?>