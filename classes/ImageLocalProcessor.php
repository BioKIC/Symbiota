<?php
if(isset($SERVER_ROOT) && $SERVER_ROOT){
	include_once($SERVER_ROOT.'/config/dbconnection.php');
	include_once($SERVER_ROOT.'/classes/OccurrenceMaintenance.php');
	include_once($SERVER_ROOT.'/classes/UuidFactory.php');
	include_once($SERVER_ROOT.'/classes/ImageShared.php');
}

class ImageLocalProcessor {

	protected $conn;

	private $collArr = array();
	private $activeCollid = null;
	private $collProcessedArr = array();

	protected $sourcePathBase;
	private $targetPathBase;
	private $targetPathFrag;
	private $origPathFrag;
	private $imgUrlBase;
	private $symbiotaClassPath = null;
	protected $serverRoot;

	private $matchCatalogNumber = true;
	private $matchOtherCatalogNumbers = false;
	private $webPixWidth = '';
	private $tnPixWidth = '';
	private $lgPixWidth = '';
	private $webFileSizeLimit = 500000;
	private $lgFileSizeLimit = 10000000;
	private $jpgQuality= 80;
	private $medProcessingCode = 1;			// 1 = evaluate source and import, 2 = import and use as is, 3 = map to source, 0 = exclude
	private $tnProcessingCode = 1;			// 1 = create from source, 2 = import source (_tn.jpg), 3 = map to source (_tn.jpg), 0 = exclude
	private $lgProcessingCode = 1;			// 1 = import source, 2 = map to source, 3 = import large version (_lg.jpg), 4 = map large version (_lg.jpg), 0 = exclude
	private $medSourceSuffix = '_med';
	private $tnSourceSuffix = '_tn';
	private $lgSourceSuffix = '_lg';
	private $keepOrig = 0;
	private $customStoredProcedure;
	private $imageTableMap = array();

	private $skeletalFileProcessing = true;
	private $createNewRec = true;
	private $imgExists = 0;			// 0 = skip import, 1 = rename image and save both, 2 = replace image
	protected $dbMetadata = 1;
	private $processUsingImageMagick = 0;

	private $logMode = 0;			//0 = silent, 1 = html, 2 = log file, 3 = html and log file
	private $logFH;
	private $mdOutputFH;
	private $logPath;
	private $errorMessage;

	private $sourceGdImg;
	private $sourceImagickImg;

	private $monthNames = array('jan'=>'01','ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','apr'=>'04',
		'may'=>'05','jun'=>'06','jul'=>'07','ago'=>'08','aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12','dic'=>'12');

	/**  Track the list of xml files that have been processed to avoid
	 *   processing the same file more than once when collArr is configured
	 *   to contain more than one record for the same path (for image
	 *   uploads from an institution with more than one collection code).
	 */
	private $processedFiles = Array();


	function __construct(){
		ini_set('memory_limit','1024M');
		ini_set('auto_detect_line_endings', true);
		//Use deaults located within symbini, if they are available
		//Will be replaced by values within configuration file, if they are set
		if(isset($GLOBALS['imgWebWidth']) && $GLOBALS['imgWebWidth']) $this->webPixWidth = $GLOBALS['imgWebWidth'];
		if(isset($GLOBALS['imgTnWidth']) && $GLOBALS['imgTnWidth']) $this->tnPixWidth = $GLOBALS['imgTnWidth'];
		if(isset($GLOBALS['imgLgWidth']) && $GLOBALS['imgLgWidth']) $this->lgPixWidth = $GLOBALS['imgLgWidth'];
		if(isset($GLOBALS['imgFileSizeLimit']) && $GLOBALS['imgFileSizeLimit']) $this->webFileSizeLimit = $GLOBALS['imgFileSizeLimit'];
	}

	function __destruct(){
		//Close connection or MD output file
		if($this->dbMetadata){
			if(!($this->conn === false)) $this->conn->close();
		}
		//Close log file
		if($this->logFH) fclose($this->logFH);
	}

	public function initProcessor($logTitle = ''){
		if($this->logPath && $this->logMode > 1){
			//Create log File
			if(!file_exists($this->logPath)){
				if(!mkdir($this->logPath,0,true)){
					echo("Warning: unable to create log file: ".$this->logPath);
				}
			}
			if(file_exists($this->logPath)){
				$titleStr = str_replace(' ','_',$logTitle);
				if(strlen($titleStr) > 50) $titleStr = substr($titleStr,0,50);
				$logFile = $this->logPath.$titleStr."_".date('Y-m-d').".log";
				$this->logFH = fopen($logFile, 'a');
				$this->logOrEcho("\nDateTime: ".date('Y-m-d h:i:s A'));
			}
			else{
				echo 'ERROR creating Log file; path not found: '.$this->logPath."\n";
			}
		}
		if($this->dbMetadata){
			//Set collection
			if(class_exists('MySQLiConnectionFactory')){
				$this->conn = MySQLiConnectionFactory::getCon('write');
			}
			elseif(class_exists('ImageBatchConnectionFactory')){
				$this->conn = ImageBatchConnectionFactory::getCon('write');
			}
			if(!$this->conn){
				$this->logOrEcho("Image upload aborted: Unable to establish connection to ".$collName." database");
				exit("ABORT: Image upload aborted: Unable to establish connection to ".$collName." database");
			}
		}
	}

	public function batchLoadSpecimenImages(){
		$this->setImagePaths();
		if($this->logMode == 1) echo '<ul>';
		foreach($this->collArr as $collid => $cArr){
			$this->activeCollid = $collid;
			if(substr($this->collArr[$this->activeCollid]['pmterm'],-4) == '.csv'){
				if(!file_exists($this->sourcePathBase.'/'.$this->collArr[$this->activeCollid]['pmterm'])){
					$this->logOrEcho('ERROR accessing image mapping file: '.$this->sourcePathBase.'/'.$this->collArr[$this->activeCollid]['pmterm']);
					continue;
				}
			}
			$collStr = '';
			if(isset($cArr['instcode'])) $collStr = str_replace(' ','',$cArr['instcode'].($cArr['collcode']?'_'.$cArr['collcode']:''));
			if(!$collStr) $collStr = str_replace('/', '_', $cArr['sourcePathFrag']);

			$mdFileName = '';
			if(!$this->dbMetadata){
				//Create output file
				$mdFileName = $this->logPath.$collStr.'_imagedata_'.date('Y-m-d').'_'.time().'.csv';
				$this->mdOutputFH = fopen($mdFileName, 'w');
				//Establish the header
				fwrite($this->mdOutputFH, '"collid","catalogNumber","url","thumbnailUrl","originalUrl"'."\n");
				if($this->mdOutputFH){
					$this->logOrEcho("Image Metadata written out to CSV file: '".$mdFileName."' (same folder as script)");
				}
				else{
					//If unable to create output file, abort upload procedure
					$this->logOrEcho("Image upload aborted: Unable to establish connection to output file to where image metadata is to be written");
					exit("ABORT: Image upload aborted: Unable to establish connection to output file to where image metadata is to be written");
				}
			}

			//Set source and target path fragments
			$sourcePathFrag = '';
			$this->targetPathFrag = '';
			if(isset($cArr['sourcePathFrag'])){
				$sourcePathFrag = $cArr['sourcePathFrag'];
				$this->targetPathFrag = $cArr['sourcePathFrag'];
			}
			else $this->targetPathFrag .= $collStr;
			if(substr($this->targetPathFrag,-1) != "/" && substr($this->targetPathFrag,-1) != "\\"){
				$this->targetPathFrag .= '/';
			}
			if($sourcePathFrag && substr($sourcePathFrag,-1) != "/" && substr($sourcePathFrag,-1) != "\\"){
				$sourcePathFrag .= '/';
			}
			if(!file_exists($this->targetPathBase.$this->targetPathFrag)){
				if(!mkdir($this->targetPathBase.$this->targetPathFrag,0777,true)){
					$this->logOrEcho("ERROR: unable to create new folder (".$this->targetPathBase.$this->targetPathFrag.") ");
					exit("ABORT: unable to create new folder (".$this->targetPathBase.$this->targetPathFrag.")");
				}
			}

			//If originals are to be kept, make sure target folders exist
			if($this->keepOrig){
				//Variable used in path to store original files
				$this->origPathFrag = 'orig/'.date("Ym").'/';
				if(!file_exists($this->targetPathBase.$this->targetPathFrag.'orig/')){
					if(!mkdir($this->targetPathBase.$this->targetPathFrag.'orig/')){
						$this->logOrEcho("NOTICE: unable to create base folder to store original files (".$this->targetPathBase.$this->targetPathFrag.") ");
					}
				}
				if(file_exists($this->targetPathBase.$this->targetPathFrag.'orig/')){
					if(!file_exists($this->targetPathBase.$this->targetPathFrag.$this->origPathFrag)){
						if(!mkdir($this->targetPathBase.$this->targetPathFrag.$this->origPathFrag)){
							$this->logOrEcho("NOTICE: unable to create folder to store original files (".$this->targetPathBase.$this->targetPathFrag.$this->origPathFrag.") ");
						}
					}
				}
			}

			$this->logOrEcho('Starting image processing: '.$sourcePathFrag);
			if(strtolower(substr($this->collArr[$this->activeCollid]['pmterm'],-4)) == '.csv'){
				$this->processImageMap($sourcePathFrag);
			}
			elseif(substr($this->sourcePathBase,0,4) == 'http'){
				//http protocol, thus test for a valid page
				$this->processHtml($sourcePathFrag);
			}
			else $this->processFolder($sourcePathFrag);
			if(!$this->dbMetadata){
				if($this->mdOutputFH) fclose($this->mdOutputFH);
				if(array_key_exists('email', $cArr) && $cArr['email']) $this->sendMetadata($cArr['email'],$mdFileName);
			}
			if($this->customStoredProcedure){
				if($this->conn->query('call '.$this->customStoredProcedure)) $this->logOrEcho('Executed stored procedure: '.$this->customStoredProcedure);
				else $this->logOrEcho('<span style="color:red;">ERROR:</span> Stored Procedure failed ('.$this->customStoredProcedure.'): '.$this->conn->error);
			}
			$this->logOrEcho('Done uploading '.$sourcePathFrag.' ('.date('Y-m-d h:i:s A').')');
		}
		$this->updateCollectionStats();

		$this->logOrEcho('Image upload process finished! ('.date('Y-m-d h:i:s A').") \n");
		if($this->logMode == 1) echo '</ul>';
	}

	private function setImagePaths(){
		if(substr($this->sourcePathBase,0,4) == 'http'){
			//http protocol, thus test for a valid page
			$headerArr = get_headers($this->sourcePathBase);
			if(!$headerArr){
				$this->logOrEcho('ABORT: sourcePathBase returned bad headers ('.$this->sourcePathBase.')');
				exit();
			}
			$codeArr = array();
			preg_match('/http.+\s{1}(\d{3})\s{1}/i',$headerArr[0],$codeArr);
			if($codeArr[1] == '403'){
				$this->logOrEcho('ABORT: sourcePathBase returned Forbidden ('.$this->sourcePathBase.')');
				exit();
			}
			if($codeArr[1] == '404'){
				$this->logOrEcho('ABORT: sourcePathBase returned a page Not Found error ('.$this->sourcePathBase.')');
				exit();
			}
			if($codeArr[1] != '200'){
				$this->logOrEcho('ABORT: sourcePathBase returned error code '.$codeArr[1].' ('.$this->sourcePathBase.')');
				exit();
			}
		}
		elseif(!file_exists($this->sourcePathBase)){
			//Make sure source path exists
			$this->logOrEcho('ABORT: sourcePathBase does not exist ('.$this->sourcePathBase.')');
			exit();
		}
		//Set target base path
		if(!$this->targetPathBase){
			//Assume that we should use the portal's default image root path
			$this->targetPathBase = $GLOBALS['imageRootPath'];
		}
		if($this->targetPathBase && substr($this->targetPathBase,-1) != '/' && substr($this->targetPathBase,-1) != "\\"){
			$this->targetPathBase .= '/';
		}

		//Set image base URL
		if(!$this->imgUrlBase){
			//Assume that we should use the portal's default image url prefix
			$this->imgUrlBase = $GLOBALS['imageRootUrl'];
		}
		if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
			//Since imageDomain is set, portal is not central portal thus add portals domain to url base
			if(substr($this->imgUrlBase,0,7) != 'http://' && substr($this->imgUrlBase,0,8) != 'https://'){
				$urlPrefix = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
				$urlPrefix .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80 && $_SERVER['SERVER_PORT'] != 443) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
				$this->imgUrlBase = $urlPrefix.$this->imgUrlBase;
			}
		}
		if($this->imgUrlBase && substr($this->imgUrlBase,-1) != '/' && substr($this->imgUrlBase,-1) != "\\"){
			$this->imgUrlBase .= '/';
		}
	}

	private function processFolder($pathFrag = ''){
		set_time_limit(3600);
		//$this->logOrEcho("Processing: ".$this->sourcePathBase.$pathFrag);
		//Read file and loop through images
		if(file_exists($this->sourcePathBase.$pathFrag)){
			if($dirFH = opendir($this->sourcePathBase.$pathFrag)){
				while($fileName = readdir($dirFH)){
					if(substr($fileName,0,1) != '.'){
						if(is_file($this->sourcePathBase.$pathFrag.$fileName)){
							$this->processFile($fileName, $pathFrag);
						}
						elseif(is_dir($this->sourcePathBase.$pathFrag.$fileName)){
							//if(strpos($fileName,'.')) $fileName = str_replace('.','\.',$fileName);
							$this->processFolder($pathFrag.$fileName."/");
						}
					}
				}
				if($dirFH) closedir($dirFH);
			}
			else{
				$this->logOrEcho('ERROR: unable to access source directory: '.$this->sourcePathBase.$pathFrag,1);
			}
		}
		else{
			$this->logOrEcho('Source path does not exist: '.$this->sourcePathBase.$pathFrag,1);
			//exit('ABORT: Source path does not exist: '.$this->sourcePathBase.$pathFrag);
		}
	}

	private function processHtml($pathFrag = ''){
		set_time_limit(3600);
		//$this->logOrEcho("Processing: ".$this->sourcePathBase.$pathFrag);
		//Check  to make sure page is readable
		$headerArr = get_headers($this->sourcePathBase.$pathFrag);
		preg_match('/http.+\s{1}(\d{3})\s{1}/i',$headerArr[0],$codeArr);
		if($codeArr[1] == '200'){
			$dom = new DOMDocument();
			$dom->loadHTMLFile($this->sourcePathBase.$pathFrag);
			$aNodes= $dom->getElementsByTagName('a');
			$skipAnchors = array('Name','Last modified','Size','Description','Parent Directory');
			foreach( $aNodes as $aNode ) {
				$fileName = '';
				if($aNode->hasAttribute('href')) $fileName = $aNode->getAttribute('href');
				else $fileName = rawurlencode(trim($aNode->nodeValue));
				if(in_array($aNode->nodeValue,$skipAnchors) || substr($fileName,0,1)=='?') continue;
				$fileExt = '';
				if(strrpos($fileName,'.')) $fileExt = strtolower(substr($fileName,strrpos($fileName,'.')+1));
				if($fileExt){
					$this->processFile($fileName, $pathFrag);
				}
				elseif(stripos($fileName,'Parent Dir') === false){
					$this->logOrEcho('New dir path: '.$this->sourcePathBase.$pathFrag.$fileName.'<br/>');
					if(substr($fileName,-1) != '/') $fileName .= '/';
					$this->processHtml($pathFrag.$fileName);
				}
			}
		}
		else{
			$this->logOrEcho('Source directory skipped (code '.$codeArr[0].') : '.$this->sourcePathBase.$pathFrag,1);
			//exit("ABORT: Source path does not exist: ".$this->sourcePathBase.$pathFrag);
		}
	}

	private function processFile($fileName, $pathFrag){
		//Only target source input files, thus skip any predesignated files, unless _lg is the source file (largest res)
		if($this->tnProcessingCode > 1 && stripos($fileName, $this->tnSourceSuffix.'.j')) return false;
		if($this->medProcessingCode > 3 && stripos($fileName, $this->medSourceSuffix.'.j')) return false;
		if($this->lgProcessingCode > 2 && stripos($fileName,$this->lgSourceSuffix.'.j') && $this->medProcessingCode < 4) return false;
		$this->logOrEcho('Processing File ('.date('Y-m-d h:i:s A').'): '.$fileName);
		$fileExt = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
		if($fileExt == 'jpg' || $fileExt == 'jpeg'){
			$catalogNumber = $this->getPrimaryKey($fileName);
			if(!$catalogNumber){
				$this->logOrEcho('File skipped ('.$fileName.'), unable to extract specimen identifier',1);
				return false;
			}
			$targetPathFrag = $this->getTargetPathFrag($catalogNumber);
			$occid = $this->getOccid($catalogNumber);
			if($occid === false) return false;
			$targetFileName = $this->prepTarget($this->targetPathBase.$targetPathFrag, $fileName, $occid);
			if(!$targetFileName) return false;
			$sourceArr['originalurl'] = $fileName;
			if($imgArr = $this->processImageFile($sourceArr, $targetFileName, $this->targetPathBase.$targetPathFrag, $pathFrag)){
				$imgArr['occid'] = $occid;
				$this->recordImageMetadata($imgArr, $targetPathFrag);
				if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
			}
			else if($this->errorMessage == 'abort') return false;
		}
		elseif($fileExt == 'tif' || $fileExt == 'tiff'){
			$this->logOrEcho("ERROR: File skipped, TIFFs image files are not a supported: ".$fileName,1);
			//Do something, like convert to jpg???
			//but for now do nothing
		}
		elseif(($fileExt == 'csv' || $fileExt == 'txt' || $fileExt == 'tab' || $fileExt == 'dat')){
			if($this->skeletalFileProcessing){
				//Is skeletal file exists. Append data to database records
				$this->processSkeletalFile($this->sourcePathBase.$pathFrag.$fileName);
				if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
			}
			else $this->logOrEcho("Skeletal file processing is set to be bypassed ",2);
		}
		elseif($fileExt == 'xml') {
			if($this->skeletalFileProcessing){
				// The loop through collArr can result in same file being processed more than
				// once if the same pathFrag is associated with more than one collection.
				if (!in_array("$pathFrag$fileName",$this->processedFiles)) {
					$this->processXMLFile($fileName,$pathFrag);
					$this->processedFiles[] = "$pathFrag$fileName";
					// TODO: It would seem that adding the collection to collProcessedArr
					// should accomplish what processedFiles[] is being added above to
					// do, need to investigate further and perhaps use it as a fix.
					if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
				}
			}
		}
		elseif($fileExt == 'ds_store' || strtolower($fileName) == 'thumbs.db'){
			@unlink($this->sourcePathBase.$pathFrag.$fileName);
		}
		else{
			$this->logOrEcho('ERROR: File skipped, not a supported image file: '.$fileName, 1);
		}
	}

	private function processImageMap(){
		set_time_limit(3600);
		//Read image map and loop through image names
		$this->medProcessingCode = 2;
		$this->tnProcessingCode = 2;
		$this->lgProcessingCode = 3;
		$this->medSourceSuffix = '';
		$this->tnSourceSuffix = '';
		$this->lgSourceSuffix = '';
		$fh = fopen($this->sourcePathBase.$this->collArr[$this->activeCollid]['pmterm'],'r');
		//Read header
		$this->setImageTableMap();
		$headerMap = array();
		if($headArr = fgetcsv($fh)){
			//Remove BOM in first element of csv if present
			$headArr[0] = preg_replace( '/[^[:print:]\r\n]/', '', $headArr[0]);
			foreach($headArr as $k => $fieldName){
				$fieldName = trim(strtolower($fieldName));
				if($fieldName === 'catalognumber' || $fieldName === 'othercatalognumbers' || array_key_exists($fieldName, $this->imageTableMap)){
					$headerMap[$fieldName] = $k;
				}
			}
		}
		if(!isset($headerMap['catalognumber'])){
			if(isset($headerMap['othercatalognumbers'])){
				$headerMap['catalognumber'] = $headerMap['othercatalognumbers'];
				unset($headerMap['othercatalognumbers']);
			}
			else{
				$this->logOrEcho('ABORT: Catalog Number is not defined', 1);
				return false;
			}
		}
		//Process file
		while($recArr = fgetcsv($fh)){
			$sourceArr = array();
			foreach($headerMap as $field => $index){
				$sourceArr[$field] = trim($recArr[$index]);
			}
			if(!isset($sourceArr['catalognumber']) || !$sourceArr['catalognumber']){
				if(isset($sourceArr['othercatalognumbers']) && $sourceArr['othercatalognumbers']){
					$sourceArr['catalognumber'] = $sourceArr['othercatalognumbers'];
					unset($sourceArr['othercatalognumbers']);
				}
			}
			$catalogNumber = $sourceArr['catalognumber'];
			if(!$catalogNumber) continue;
			if(!isset($sourceArr['originalurl']) && !$sourceArr['originalurl']){
				if(isset($sourceArr['url']) && $sourceArr['url']){
					$sourceArr['originalurl'] = $sourceArr['url'];
					unset($sourceArr['url']);
				}
			}
			if(!isset($sourceArr['originalurl'])) continue;
			$this->logOrEcho('Processing File ('.date('Y-m-d h:i:s A').'): '.$sourceArr['originalurl']);
			//Verify that image include file extension and are accessible, or file name can can be fixed
			if(!file_exists($this->sourcePathBase.'/'.$sourceArr['originalurl'])){
				$extArr = array('jpg','JPG','jpeg','JPEG');
				$verified = false;
				foreach($extArr as $e){
					if(file_exists($this->sourcePathBase.'/'.$sourceArr['originalurl'].'.'.$e)){
						$sourceArr['originalurl'] .= '.'.$e;
						if(isset($sourceArr['url'])) $sourceArr['url'] .= '.'.$e;
						if(isset($sourceArr['thumbnailurl'])) $sourceArr['thumbnailurl'] .= '.'.$e;
						$verified = true;
						break;
					}
				}
				if(!$verified){
					$this->logOrEcho('ERROR: unable to locate file within base folder: '.$this->sourcePathBase.$sourceArr['originalurl'],1);
					continue;
				}
			}
			$fileExt = strtolower(substr($sourceArr['originalurl'], strrpos($sourceArr['originalurl'],'.')));
			if($fileExt == '.jpg' || $fileExt == '.jpeg'){
				$targetPathFrag = $this->getTargetPathFrag($catalogNumber);
				$occid = $this->getOccid($catalogNumber);
				if($occid === false) continue;
				$targetFileName = $this->prepTarget($this->targetPathBase.$targetPathFrag, $sourceArr['originalurl'], $occid);
				if(!$targetFileName) continue;
				if($imgArr = $this->processImageFile($sourceArr, $targetFileName, $this->targetPathBase.$targetPathFrag)){
					$imgArr['occid'] = $occid;
					$this->recordImageMetadata($imgArr, $targetPathFrag);
					if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
				}
			}
			else $this->logOrEcho('ERROR: File skipped, image is not a JPG: '.$sourceArr['originalurl'], 1);
		}
		fclose($fh);
	}

	private function getTargetPathFrag($catalogNumber){
		$targetFolder = '';
		if(strlen($catalogNumber) > 3){
			$folderName = $catalogNumber;
			if(preg_match('/^(\D*\d+)\D+/',$folderName,$m)){
				$folderName = $m[1];
			}
			$targetFolder = substr($folderName,0,strlen($folderName)-3);
			$targetFolder = str_replace(array('.','\\','/','#',' '),'',$targetFolder).'/';
			if($targetFolder && strlen($targetFolder) < 6 && is_numeric(substr($targetFolder,0,1))){
				$targetFolder = str_repeat('0',6-strlen($targetFolder)).$targetFolder;
			}
		}
		if(!$targetFolder) $targetFolder = date('Ym').'/';
		$targetPath = $this->targetPathFrag.$targetFolder;
		if(!file_exists($this->targetPathBase.$targetPath)){
			if(!mkdir($this->targetPathBase.$targetPath)){
				$this->logOrEcho('ERROR: unable to create new folder ('.$this->targetPathBase.$targetPath.') ');
			}
		}
		return $targetPath;
	}

	private function prepTarget($targetPath, $fileName, $occid){
		$targetFileName = str_replace('%20', '_', $fileName);
		$targetFileName = str_replace(array(' '), '_', $targetFileName);
		$targetFileName = str_replace(array('(',')'), '', $targetFileName);
		if($this->medProcessingCode == 1 || $this->medProcessingCode == 2){
			//Check to see if image already exists at target, if so, delete or rename target
			if(file_exists($targetPath.$targetFileName)){
				if($this->imgExists == 2){
					//Replace image (ie remove old images)
					unlink($targetPath.$targetFileName);
					if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."tn.jpg")){
						unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."tn.jpg");
					}
					if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."_tn.jpg")){
						unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."_tn.jpg");
					}
					if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."lg.jpg")){
						unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."lg.jpg");
					}
					if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."_lg.jpg")){
						unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."_lg.jpg");
					}
				}
				elseif($this->imgExists == 1){
					//Rename image before saving
					$cnt = 1;
					$tempFileName = $targetFileName;
					while(file_exists($targetPath.$targetFileName)){
						$targetFileName = str_ireplace(".jpg","_".$cnt.".jpg",$tempFileName);
						$cnt++;
					}
				}
				else{
					// skip import of image ($this->imgExists === 0)
					$this->logOrEcho("NOTICE: image import skipped because image file already exists ",1);
					return false;
				}
			}
		}
		elseif($this->medProcessingCode == 3){
			if(!$this->imgExists){
				if($occid){
					//Check to see if database record already exists, and if so skip import
					$recExists = 0;
					$sql = 'SELECT url FROM images WHERE (occid = '.$occid.') ';
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						if(stripos($r->url,$fileName) || stripos($r->url,str_replace('%20', '_', $fileName)) || stripos($r->url,str_replace('%20', ' ', $fileName))){
							$recExists = 1;
						}
					}
					$rs->free();
					if($recExists){
						$this->logOrEcho("NOTICE: image import skipped because specimen record already exists ",1);
						return false;
					}
				}
			}
		}
		return $targetFileName;
	}

	private function processImageFile($imageArr, $targetFileName, $targetPath, $pathFrag = ''){
		$sourceFileName = $imageArr['originalurl'];
		$sourcePath = $this->sourcePathBase.$pathFrag;
		//$this->logOrEcho("Processing image (".date('Y-m-d h:i:s A')."): ".$fileName);
		//ob_flush();
		flush();
		//$fileName = rawurlencode($fileName);
		$fileNameExt = '.jpg';
		$fileNameBase = $sourceFileName;
		if($p = strrpos($sourceFileName,'.')){
			$fileNameExt = substr($sourceFileName,$p);
			$fileNameBase = substr($sourceFileName,0,$p);
		}
		list($width, $height) = ImageShared::getImgDim($sourcePath.$sourceFileName);
		if($width && $height){
			$fileSize = 0;
			if(substr($sourcePath,0,7)=='http://' || substr($sourcePath,0,8)=='https://') {
				$x = array_change_key_case(get_headers($sourcePath.$sourceFileName,1),CASE_LOWER);
				if ( strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0 )  $fileSize = $x['content-length'][1];
				else $fileSize = $x['content-length'];
			}
			else $fileSize = @filesize($sourcePath.$sourceFileName);

			//$this->logOrEcho("Loading image (".date('Y-m-d h:i:s A').")",1);
			//ob_flush();
			//flush();

			//Set medium web image
			$medUrl = '';
			if($this->medProcessingCode){
				$medFileName = $fileNameBase.$this->medSourceSuffix.$fileNameExt;
				$medTargetFileName = substr($targetFileName,0,-4).$this->medSourceSuffix.'.jpg';
				if(isset($imageArr['url']) && $imageArr['url']){
					$medFileName = $imageArr['url'];
					$medTargetFileName = $imageArr['url'];
				}
				if($this->medProcessingCode == 1){
					// evaluate source and import
					if($fileSize < $this->webFileSizeLimit && $width < ($this->webPixWidth*2)){
						if(copy($sourcePath.$sourceFileName, $targetPath.$medTargetFileName)){
							$medUrl = $medTargetFileName;
							$this->logOrEcho('Source image imported as web image ', 1);
						}
					}
					else{
						if($this->createNewImage($sourcePath.$sourceFileName, $targetPath.$medTargetFileName, $this->webPixWidth, round($this->webPixWidth*$height/$width), $width, $height)){
							$medUrl = $medTargetFileName;
							$this->logOrEcho('Web image created from source image ', 1);
						}
					}
				}
				elseif($this->medProcessingCode == 2){
					// import source and use as is
					if($this->uriExists($sourcePath.$medFileName)){
						if(copy($sourcePath.$medFileName, $targetPath.$medTargetFileName)){
							$medUrl = $medTargetFileName;
							$this->logOrEcho('Web image imported as is ', 1);
						}
					}
					else $this->logOrEcho('WARNING: predesignated medium does not appear to exist ('.$sourcePath.$medFileName.') ', 1);
				}
				elseif($this->medProcessingCode == 3){
					// map to source as the web image
					if($this->uriExists($sourcePath.$medFileName)){
						$medUrl = $sourcePath.$medFileName;
						$this->logOrEcho('Source used as web image ', 1);
					}
					else{
						$this->logOrEcho('WARNING: predesignated medium does not appear to exist ('.$sourcePath.$medFileName.') ',1);
					}
				}
			}
			if($medUrl) $imageArr['url'] = $medUrl;
			else $this->logOrEcho('Failed to create web image ', 1);
			//Set large image
			$lgUrl = '';
			if($this->lgProcessingCode){
				$lgTargetFileName = $targetFileName;
				if($this->lgProcessingCode == 1){
					// 1 = evaluate source for import as large image
					if($width > ($this->webPixWidth*1.3)){
						//Source image is big enough to serve as large version
						if($width > $this->lgPixWidth){
							//Image is too wide, thus let's resize and import
							if($this->createNewImage($sourcePath.$sourceFileName, $targetPath.$lgTargetFileName, $this->lgPixWidth, round($this->lgPixWidth*$height/$width), $width, $height)){
								$lgUrl = $lgTargetFileName;
								$this->logOrEcho('Resized source as large derivative ',1);
							}
						}
						elseif($fileSize && $fileSize > $this->lgFileSizeLimit) {
							// Image file size is too big, thus let's resize and import

							// Figure out what factor to reduce filesize by
							$scaleFactor = sqrt($this->lgFileSizeLimit / $fileSize);

							// Scale by a factor of the square root of the filesize ratio
							// Note, this is a good approximation to reduce the filesize, but will not be exact
							// True reduction will also depend on the JPEG quality of the source & the large file
							$newWidth = round($width * $scaleFactor);

							// Resize the image
							if($this->createNewImage($sourcePath.$sourceFileName, $targetPath.$lgTargetFileName, $newWidth, round($newWidth*$height/$width), $width, $height)){
								$lgUrl = $lgTargetFileName;
								$this->logOrEcho('Resized source as large derivative ',1);
							}
						}
						else{
							//Source can serve as large version, thus just import as is
							if(copy($sourcePath.$sourceFileName, $targetPath.$lgTargetFileName)){
								$lgUrl = $lgTargetFileName;
								$this->logOrEcho('Imported source as large derivative ',1);
							}
							else{
								$this->logOrEcho("WARNING: unable to import large derivative (".$sourcePath.$sourceFileName.") ",1);
							}
						}
						$imgHash = md5_file($targetPath.$lgTargetFileName);
						if($imgHash) $imageArr['mediamd5'] = $imgHash;
					}
				}
				elseif($this->lgProcessingCode == 2){
					// 2 = map to source
					$lgUrl = $sourcePath.$sourceFileName;
					if(!isset($imageArr['mediamd5']) || !$imageArr['mediamd5']){
						//Don't override MD5 hash is supplied by source (possible todo: compare source hash with new hash and report if they mismatch)
						$imgHash = md5_file($sourcePath.$sourceFileName);
						if($imgHash) $imageArr['mediamd5'] = $imgHash;
					}
					$this->logOrEcho('Mapped to source as large derivative ',1);
				}
				elseif($this->lgProcessingCode == 3){
					// 3 = import predesignated large version from image map or look for file with $this->lgSourceSuffix
					$lgSourceFileName = $fileNameBase.$this->lgSourceSuffix.$fileNameExt;
					if($this->uriExists($sourcePath.$lgSourceFileName)){
						if(copy($sourcePath.$lgSourceFileName, $targetPath.$lgTargetFileName)){
							if(substr($sourcePath,0,4) != 'http') @unlink($sourcePath.$lgSourceFileName);
							$lgUrl = $lgTargetFileName;
							if(!isset($imageArr['mediamd5']) || !$imageArr['mediamd5']){
								//Don't override MD5 hash is supplied by source (possible todo: compare source hash with new hash and report if they mismatch)
								$imgHash = md5_file($targetPath.$lgTargetFileName);
								if($imgHash) $imageArr['mediamd5'] = $imgHash;
							}
							$this->logOrEcho('Large derivative imported as is ',1);
						}
					}
					else{
						$this->logOrEcho('WARNING: unable to import large derivative ('.$sourcePath.$lgSourceFileName.') ',1);
					}
				}
				elseif($this->lgProcessingCode == 4){
					// 4 = map to predesignated large version: look for and use file with $this->lgSourceSuffix, if it exists
					$lgSourceFileName = $fileNameBase.$this->lgSourceSuffix.$fileNameExt;
					if($this->uriExists($sourcePath.$lgSourceFileName)){
						$lgUrl = $sourcePath.$lgSourceFileName;
						if(!isset($imageArr['mediamd5']) || !$imageArr['mediamd5']){
							//Don't override MD5 hash is supplied by source (possible todo: compare source hash with new hash and report if they mismatch)
							$imgHash = md5_file($sourcePath.$lgSourceFileName);
							if($imgHash) $imageArr['mediamd5'] = $imgHash;
						}
						$this->logOrEcho('Large derivative mapped to existing source ',1);
					}
					else{
						$this->logOrEcho('WARNING: unable to map to large derivative ('.$sourcePath.$lgSourceFileName.') ',1);
					}
				}
			}
			if($lgUrl) $imageArr['originalurl'] = $lgUrl;
			//Set thumbnail image
			$tnUrl = '';
			if($this->tnProcessingCode){
				$tnFileName = $fileNameBase.$this->tnSourceSuffix.$fileNameExt;
				$tnTargetFileName = substr($targetFileName,0,-4).$this->tnSourceSuffix.'.jpg';
				if(isset($imageArr['thumbnailurl']) && $imageArr['thumbnailurl']){
					$tnFileName = $imageArr['thumbnailurl'];
					$tnTargetFileName = $imageArr['thumbnailurl'];
				}
				if($this->tnProcessingCode == 1){
					// create tn from source
					if($this->createNewImage($sourcePath.$sourceFileName,$targetPath.$tnTargetFileName,$this->tnPixWidth,round($this->tnPixWidth*$height/$width),$width,$height)){
						$tnUrl = $tnTargetFileName;
						$this->logOrEcho('Created thumbnail from source ',1);
					}
				}
				elseif($this->tnProcessingCode == 2){
					// import predesignated tn; look for and use file with $this->tnSourceSuffix, if it exists
					if($this->uriExists($sourcePath.$tnFileName)){
						copy($sourcePath.$tnFileName,$targetPath.$tnTargetFileName);
						if(substr($sourcePath,0,4) != 'http') @unlink($sourcePath.$tnFileName);
					}
					$tnUrl = $tnTargetFileName;
					$this->logOrEcho('Thumbnail derivative imported as is ',1);
				}
				elseif($this->tnProcessingCode == 3){
					// 3 = map to predesignated tn: look for and use file with $this->tnSourceSuffix, if it exists
					if($this->uriExists($sourcePath.$tnFileName)){
						$tnUrl = $sourcePath.$tnFileName;
						$this->logOrEcho('Thumbnail is map of source thumbnail ',1);
					}
				}
			}
			if($tnUrl) $imageArr['thumbnailurl'] = $tnUrl;

			//Start clean up
			if($this->sourceGdImg){
				imagedestroy($this->sourceGdImg);
				$this->sourceGdImg = null;
			}
			if($this->sourceImagickImg){
				$this->sourceImagickImg->clear();
				$this->sourceImagickImg = null;
			}
			//Final cleaning stage
			if($this->keepOrig){
				if(file_exists($this->targetPathBase.$this->targetPathFrag.$this->origPathFrag)){
					rename($sourcePath.$sourceFileName,$this->targetPathBase.$this->targetPathFrag.$this->origPathFrag.$sourceFileName.".orig");
				}
			}
			else {
				if(file_exists($sourcePath.$sourceFileName)) unlink($sourcePath.$sourceFileName);
				if($this->tnProcessingCode < 3 && isset($imageArr['thunbnailurl'])) @unlink($sourcePath.$imageArr['thunbnailurl']);
				if($this->medProcessingCode < 3 && isset($imageArr['url'])) @unlink($sourcePath.$imageArr['url']);
			}
			$this->logOrEcho('Image processed successfully ('.date('Y-m-d h:i:s A').')!',1);
		}
		else{
			$this->logOrEcho('File skipped ('.$sourcePath.$sourceFileName.'), unable to obtain dimensions of original image',1);
			return false;
		}
		//ob_flush();
		flush();
		return $imageArr;
	}

	private function createNewImage($sourcePathBase, $targetPath, $newWidth, $newHeight, $sourceWidth, $sourceHeight){
		$status = false;
		if($this->processUsingImageMagick) {
			// Use ImageMagick to resize images
			$status = $this->createNewImageImagick($sourcePathBase,$targetPath,$newWidth,$newHeight);
		}
		elseif(extension_loaded('gd') && function_exists('gd_info')) {
			// GD is installed and working
			$status = $this->createNewImageGD($sourcePathBase,$targetPath,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
		}
		else{
			// Neither ImageMagick nor GD are installed
			$this->logOrEcho("FATAL ERROR: No appropriate image handler for image conversions",1);
			exit("ABORT: No appropriate image handler for image conversions");
		}
		return $status;
	}

	private function createNewImageImagick($sourceImg,$targetPath,$newWidth,$newHeight){
		$status = false;
		$ct = null;
		$retval = null;

		if(!$newWidth || !$newHeight){
			$this->logOrEcho("ERROR: Unable to create image because new width or height is not set (w:".$newWidth.' h:'.$newHeight.')');
			return $status;
		}

		if($newWidth < 300){
			$ct = system('convert '.$sourceImg.' -thumbnail '.$newWidth.'x'.$newHeight.' '.$targetPath, $retval);
		}
		else{
			$ct = system('convert '.$sourceImg.' -resize '.$newWidth.'x'.$newHeight.($this->jpgQuality?' -quality '.$this->jpgQuality:'').' '.$targetPath, $retval);
		}
		if(file_exists($targetPath)){
			$status = true;
		}
		else{
			echo $ct;
			echo $retval;
		}
		return $status;
	}

	private function createNewImageGD($sourcePathBase, $targetPath, $newWidth, $newHeight, $sourceWidth, $sourceHeight){
		$status = false;
		if(!$this->sourceGdImg){
			$this->sourceGdImg = imagecreatefromjpeg($sourcePathBase);
		}
		if(!$newWidth || !$newHeight){
			$this->logOrEcho("ERROR: Unable to create image because new width or height is not set (w:".$newWidth.' h:'.$newHeight.')');
			return $status;
		}
		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		//imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
		imagecopyresized($tmpImg,$this->sourceGdImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);

		if($this->jpgQuality) $status = imagejpeg($tmpImg, $targetPath, $this->jpgQuality);
		else $status = imagejpeg($tmpImg, $targetPath);

		if(!$status) $this->logOrEcho("ERROR: Unable to resize and write file: ".$targetPath,1);

		imagedestroy($tmpImg);
		return $status;
	}

	/**
	 * Extract a primary key (catalog number) from a string (e.g file name, catalogNumber field),
	 * applying patternMatchingTerm, and, if they apply, patternReplacingTerm, and
	 * replacement.  If patternMatchingTerm contains a backreference,
	 * and there is a match, the return value is the backreference.  If
	 * patternReplacingTerm and replacement are modified, they are applied
	 * before the result is returned.
	 *
	 * param: str  String from which to extract the catalogNumber
	 * return: an empty string if there is no match of patternMatchingTerm on
	 *		str, otherwise the match as described above.
	 */
	private function getPrimaryKey($str){
		$specPk = '';
		if(isset($this->collArr[$this->activeCollid]['pmterm'])){
			$pmTerm = $this->collArr[$this->activeCollid]['pmterm'];
			if(substr($pmTerm,0,1) != '/' || stripos(substr($pmTerm,-3),'/') === false){
				$this->errorMessage = 'Regular Expression term illegal due to missing forward slashes delimiting the term: '.$pmTerm;
				$this->logOrEcho('PROCESS ABORTED: '.$this->errorMessage,1);
				exit('ABORT: '.$this->errorMessage);
			}
			if(!strpos($pmTerm,'(') || !strpos($pmTerm,')')){
				$this->errorMessage = 'Regular Expression term illegal due to missing capture term: '.$pmTerm;
				$this->logOrEcho('PROCESS ABORTED: '.$this->errorMessage,1);
				exit('ABORT: '.$this->errorMessage);
			}
			if(preg_match($pmTerm,$str,$matchArr)){
				if(array_key_exists(1,$matchArr) && $matchArr[1]){
					$specPk = $matchArr[1];
				}
				if(isset($this->collArr[$this->activeCollid]['prpatt'])) {
					$specPk = preg_replace($this->collArr[$this->activeCollid]['prpatt'],$this->collArr[$this->activeCollid]['prrepl'],$specPk);
				}
				if(isset($matchArr[2])){
					$this->medSourceSuffix = $matchArr[2];
				}
			}
		}
		return $specPk;
	}

	private function getOccid($catalogNumber){
		$occid = 0;
		if($this->dbMetadata){
			$occid = false;
			//Check to see if record with pk already exists
			if($this->matchCatalogNumber){
				$sql = 'SELECT occid FROM omoccurrences '.
					'WHERE (catalognumber IN("'.$catalogNumber.'"'.(substr($catalogNumber,0,1)=='0'?',"'.ltrim($catalogNumber,'0 ').'"':'').')) '.
					'AND (collid = '.$this->activeCollid.')';
				$rs = $this->conn->query($sql);
				if($row = $rs->fetch_object()){
					$occid = $row->occid;
				}
				$rs->free();
			}
			if($this->matchOtherCatalogNumbers){
				$sql = 'SELECT DISTINCT o.occid '.
					'FROM omoccurrences o LEFT JOIN omoccuridentifiers i ON o.occid = i.occid '.
					'WHERE (o.collid = '.$this->activeCollid.') '.
					'AND ((o.othercatalognumbers IN("'.$catalogNumber.'"'.(substr($catalogNumber,0,1)=='0'?',"'.ltrim($catalogNumber,'0 ').'"':'').')) OR (i.identifierValue = "'.$catalogNumber.'")) ';
				$rs = $this->conn->query($sql);
				if($row = $rs->fetch_object()){
					$occid = $row->occid;
				}
				$rs->free();
			}
			if(!$occid && $this->createNewRec){
				//Records does not exist, create a new one to which image will be linked
				$sql2 = 'INSERT INTO omoccurrences(collid,'.($this->matchCatalogNumber?'catalognumber':'othercatalognumbers').',processingstatus,dateentered) '.
					'VALUES('.$this->activeCollid.',"'.$catalogNumber.'","unprocessed","'.date('Y-m-d H:i:s').'")';
				if($this->conn->query($sql2)){
					$occid = $this->conn->insert_id;
					$this->logOrEcho('Specimen record does not exist; new empty specimen record created and assigned an "unprocessed" status (occid = <a href="../individual/index.php?occid='.$occid.'" target="_blank">'.$occid.'</a>) ',1);
				}
				else $this->logOrEcho("ERROR creating new occurrence record: ".$this->conn->error,1);
			}
			if(!$occid) $this->logOrEcho("ERROR: File skipped, unable to locate specimen record ".$catalogNumber." (".date('Y-m-d h:i:s A').") ",1);
		}
		return $occid;
	}

	private function recordImageMetadata($imgArr, $targetPathFrag){
		$status = false;
		if(isset($imgArr['url']) && substr($imgArr['url'],0,4) != 'http') $imgArr['url'] = $this->imgUrlBase.$targetPathFrag.$imgArr['url'];
		if(isset($imgArr['originalurl']) && substr($imgArr['originalurl'],0,4) != 'http') $imgArr['originalurl'] = $this->imgUrlBase.$targetPathFrag.$imgArr['originalurl'];
		if(isset($imgArr['thumbnailurl']) && substr($imgArr['thumbnailurl'],0,4) != 'http') $imgArr['thumbnailurl'] = $this->imgUrlBase.$targetPathFrag.$imgArr['thumbnailurl'];
		if($this->dbMetadata){
			$status = $this->databaseImage($imgArr);
		}
		else{
			$status = $this->writeMetadataToFile($imgArr);
		}
		return $status;
	}

	private function databaseImage($imgArr){
		$status = true;
		$this->logOrEcho('Preparing to load record into database', 1);
		if(isset($imgArr['url']) && $imgArr['url']){
			if(!isset($imgArr['originalurl'])) $imgArr['originalurl'] = $imgArr['url'];
			$occid = 0;
			if(isset($imgArr['occid'])) $occid = $imgArr['occid'];
			if($occid){
				//Check to see if image url already exists for that occid
				$sql = 'SELECT imgid, url, thumbnailUrl, originalUrl, sourceIdentifier, mediaMD5 FROM images WHERE (occid = '.$occid.') ';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$isExactMatch = false;
					if(strcasecmp($r->url, $imgArr['url']) == 0 || strcasecmp($r->url, $imgArr['originalurl']) == 0) $isExactMatch = true;
					if(isset($imgArr['mediamd5']) && $imgArr['mediamd5'] && $imgArr['mediamd5'] == $r->mediaMD5) $isExactMatch = true;
					if($isExactMatch){
						//exact match, thus reset record data with current image urls (thumbnail or original image might be in different locality)
						if(!$this->conn->query('DELETE FROM specprocessorrawlabels WHERE imgid = '.$r->imgid)){
							$this->logOrEcho('ERROR deleting OCR for image record #'.$r->imgid.' (equal URLs): '.$this->conn->error,1);
						}
						if(!$this->conn->query('DELETE FROM images WHERE imgid = '.$r->imgid)){
							$this->logOrEcho('ERROR deleting image record #'.$r->imgid.' (equal URLs): '.$this->conn->error,1);
						}
					}
					elseif($this->imgExists == 2 && strcasecmp(basename($r->url),basename($imgArr['url'])) == 0){
						//Copy-over-image is set to true and basenames equal, thus delete image PLUS delete old images
						if(!$this->conn->query('DELETE FROM specprocessorrawlabels WHERE imgid = '.$r->imgid)){
							$this->logOrEcho('ERROR deleting OCR for image record #'.$r->imgid.' (equal basename): '.$this->conn->error,1);
						}
						if($this->conn->query('DELETE FROM images WHERE imgid = '.$r->imgid)){
							//Remove images
							$urlPath = current(parse_url($r->url, PHP_URL_PATH));
							if($urlPath && strpos($urlPath, $this->imgUrlBase) === 0){
								$wFile = str_replace($this->imgUrlBase,$this->targetPathBase,$urlPath);
								if(file_exists($wFile) && is_writable($wFile)) unlink($wFile);
							}
							$urlTnPath = current(parse_url($r->thumbnailUrl, PHP_URL_PATH));
							if($urlTnPath && strpos($urlTnPath, $this->imgUrlBase) === 0){
								$wFile = str_replace($this->imgUrlBase,$this->targetPathBase,$urlTnPath);
								if(file_exists($wFile) && is_writable($wFile)) unlink($wFile);
							}
							$urlLgPath = current(parse_url($r->originalUrl, PHP_URL_PATH));
							if($urlLgPath && strpos($urlLgPath, $this->imgUrlBase) === 0){
								$wFile = str_replace($this->imgUrlBase,$this->targetPathBase,$urlLgPath);
								if(file_exists($wFile) && is_writable($wFile)) unlink($wFile);
							}
						}
						else{
							$this->logOrEcho('ERROR: Unable to delete image record #'.$r->imgid.' (equal basename): '.$this->conn->error,1);
						}
					}
				}
				$rs->free();
			}
			if(!isset($imgArr['format'])) $imgArr['format'] = 'image/jpeg';
			if(isset($this->collArr[$this->activeCollid]['collname'])){
				if(!isset($imgArr['owner'])) $imgArr['owner'] = $this->collArr[$this->activeCollid]['collname'];
				if(!isset($imgArr['imagetype'])) $imgArr['imagetype'] = 'specimen';
			}
			$this->setImageTableMap();
			$paramType = '';
			$paramArr = array();
			$sql1 = '';
			$sql2 = '';
			foreach($imgArr as $fieldName => $fieldValue){
				if(array_key_exists($fieldName, $this->imageTableMap)){
					$sql1 .= $fieldName.',';
					$sql2 .= '?, ';
					$paramType .= $this->imageTableMap[$fieldName]['type'];
					$paramArr[] = $fieldValue;
				}
			}
			if($paramArr){
				$sql = 'INSERT INTO images('.trim($sql1,', ').') VALUES ('.trim($sql2,', ').')';
				if($stmt = $this->conn->prepare($sql)){
					$stmt->bind_param($paramType, ...$paramArr);
					$stmt->execute();
					if($stmt->affected_rows && !$stmt->error){
						$msg = 'SUCCESS: Image record loaded into database ';
						if($occid) $msg .= 'and linked to occurrence record <a href="../individual/index.php?occid='.$occid.'" target="_blank">'.$occid.'</a>';
						$this->logOrEcho($msg,1);
					}
					else{
						$status = false;
						$this->errorMessage = 'ERROR: Unable to load image record into database: '.$this->conn->error;
						$this->logOrEcho($this->errorMessage, 1);
					}
					$stmt->close();
				}
			}
		}
		else{
			$status = false;
			$this->logOrEcho('ERROR: web url not set within imgArr ', 1);
		}
		//ob_flush();
		flush();
		return $status;
	}

	private function writeMetadataToFile($imgArr){
		$status = true;
		if($this->mdOutputFH){
			$status = fwrite($this->mdOutputFH, $this->activeCollid.',"'.$imgArr['catalognumber'].'","'.$imgArr['url'].'","'.$imgArr['thumbnailurl'].'","'.$imgArr['originalurl'].'"'."\n");
		}
		return $status;
	}

	private function processSkeletalFile($filePath){
		$this->logOrEcho("Preparing to load Skeletal file into database",1);
		$fh = fopen($filePath,'r');
		$hArr = array();
		if($fh){
			$fileExt = substr($filePath,-4);
			$delimiter = '';
			if($fileExt == '.csv'){
				//Comma delimited
				$hArr = fgetcsv($fh);
				$delimiter = 'csv';
			}
			elseif($fileExt == '.tab'){
				//Tab delimited assumed
				$headerStr = fgets($fh);
				$hArr = explode("\t",$headerStr);
				$delimiter = "\t";
			}
			elseif($fileExt == '.dat' || $fileExt == '.txt'){
				//Test to see if comma, tab delimited, or pipe delimited
				$headerStr = fgets($fh);
				if(strpos($headerStr,"\t") !== false){
					$hArr = explode("\t",$headerStr);
					$delimiter = "\t";
				}
				elseif(strpos($headerStr,"|") !== false){
					$hArr = explode("|",$headerStr);
					$delimiter = "|";
				}
				elseif(strpos($headerStr,",") !== false){
					rewind($fh);
					$hArr = fgetcsv($fh);
					$delimiter = "csv";
				}
				else{
					$this->logOrEcho("ERROR: Unable to identify delimiter for metadata file ",1);
					return false;
				}
			}
			else{
				$this->logOrEcho("ERROR: Skeletal file skipped: unable to determine file type ",1);
				return false;
			}
			if($hArr){
				//Clean and finalize header array
				$headerArr = array();
				foreach($hArr as $field){
					$fieldStr = strtolower(trim($field));
					if($fieldStr == 'exsnumber') $fieldStr = 'exsiccatinumber';
					if($fieldStr){
						$headerArr[] = $fieldStr;
					}
					else{
						break;
					}
				}

				//Read and database each record, only if field for catalognumber was supplied
				$symbMap = array();
				if(in_array('catalognumber',$headerArr)){
					//Get map of value Symbiota occurrence fields
					$sqlMap = "SHOW COLUMNS FROM omoccurrences";
					$rsMap = $this->conn->query($sqlMap);
					while($rMap = $rsMap->fetch_object()){
						$field = strtolower($rMap->Field);
						if(in_array($field,$headerArr)){
							$type = $rMap->Type;
							if(strpos($type,"double") !== false || strpos($type,"int") !== false || strpos($type,"decimal") !== false){
								$symbMap[$field]["type"] = "numeric";
							}
							elseif(strpos($type,"date") !== false){
								$symbMap[$field]["type"] = "date";
							}
							else{
								$symbMap[$field]["type"] = "string";
								if(preg_match('/\(\d+\)$/', $type, $matches)){
									$symbMap[$field]["size"] = substr($matches[0],1,strlen($matches[0])-2);
								}
							}
						}
					}
					//Remove field that shouldn't be loaded
					unset($symbMap['datelastmodified']);
					unset($symbMap['occid']);
					unset($symbMap['collid']);
					unset($symbMap['catalognumber']);
					unset($symbMap['institutioncode']);
					unset($symbMap['collectioncode']);
					unset($symbMap['dbpk']);
					unset($symbMap['processingstatus']);
					unset($symbMap['observeruid']);
					unset($symbMap['tidinterpreted']);

					//Add exsiccati titles and numbers to $symbMap
					$symbMap['ometid']['type'] = "numeric";
					$symbMap['exsiccatititle']['type'] = "string";
					$symbMap['exsiccatititle']['size'] = 150;
					$symbMap['exsiccatinumber']['type'] = "string";
					$symbMap['exsiccatinumber']['size'] = 45;
					$exsiccatiTitleMap = array();

					//Fetch each record within file and process accordingly
					while($recordArr = $this->getRecordArr($fh,$delimiter)){
						//Clean record and creaet map array
						$catNum = 0;
						$recMap = Array();
						foreach($headerArr as $k => $hStr){
							if($hStr == 'catalognumber') $catNum = $recordArr[$k];
							if(array_key_exists($hStr,$symbMap)){
								$valueStr = '';
								if(array_key_exists($k,$recordArr)) $valueStr = $recordArr[$k];
								if($valueStr){
									//If value is enclosed by quotes, remove quotes
									if(substr($valueStr,0,1) == '"' && substr($valueStr,-1) == '"'){
										$valueStr = substr($valueStr,1,strlen($valueStr)-2);
									}
									$valueStr = trim($valueStr);
									if($valueStr) $recMap[$hStr] = $valueStr;
								}
							}
						}

						//If sciname does not exist but genus or scientificname does, create sciname
						if((!array_key_exists('sciname',$recMap) || !$recMap['sciname'])){
							if(array_key_exists('genus',$recMap) && $recMap['genus']){
								$sn = $recMap['genus'];
								if(array_key_exists('specificepithet',$recMap) && $recMap['specificepithet']) $sn .= ' '.$recMap['specificepithet'];
								if(array_key_exists('taxonrank',$recMap) && $recMap['taxonrank']) $sn .= ' '.$recMap['taxonrank'];
								if(array_key_exists('infraspecificepithet',$recMap) && $recMap['infraspecificepithet']) $sn .= ' '.$recMap['infraspecificepithet'];
								$recMap['sciname'] = $sn;
							}
							elseif(array_key_exists('scientificname',$recMap) && $recMap['scientificname']){
								$recMap['sciname'] = $this->formatScientificName($recMap['scientificname']);
							}
							if(array_key_exists('sciname',$recMap)){
								$symbMap['sciname']['type'] = 'string';
								$symbMap['sciname']['size'] = 255;
							}
						}

						//If verbatimEventDate exists and eventDate doesn't, try to convert
						if(!array_key_exists('eventdate',$recMap) || !$recMap['eventdate']){
							if(array_key_exists('verbatimeventdate',$recMap) && $recMap['verbatimeventdate']){
								$dateStr = $this->formatDate($recMap['verbatimeventdate']);
								if($dateStr){
									$recMap['eventdate'] = $dateStr;
									if($dateStr == $recMap['verbatimeventdate']) unset($recMap['verbatimeventdate']);
									if(!array_key_exists('eventdate',$symbMap)){
										$symbMap['eventdate']['type'] = 'date';
									}
								}
							}
						}

						//If exsiccatiTitle and exsiccatiNumber exists but ometid (title number) does not
						if(array_key_exists('exsiccatinumber',$recMap) && $recMap['exsiccatinumber']){
							if(array_key_exists('exsiccatititle',$recMap) && $recMap['exsiccatititle'] && (!array_key_exists('ometid',$recMap) || !$recMap['ometid'])){
								//Get ometid
								if(array_key_exists($recMap['exsiccatititle'],$exsiccatiTitleMap)){
									//ometid was already harvested for that title
									$recMap['ometid'] = $exsiccatiTitleMap[$recMap['exsiccatititle']];
								}
								else{
									$titleStr = trim($this->conn->real_escape_string($recMap['exsiccatititle']));
									$sql = 'SELECT ometid FROM omexsiccatititles '.
										'WHERE (title = "'.$titleStr.'") OR (abbreviation = "'.$titleStr.'")';
									$rs = $this->conn->query($sql);
									if($r = $rs->fetch_object()){
										$recMap['ometid'] = $r->ometid;
										$exsiccatiTitleMap[$recMap['exsiccatititle']] = $r->ometid;
									}
									$rs->free();
								}
							}
							//Get exsiccati number id (omenid)
							if(array_key_exists('ometid',$recMap) && $recMap['ometid']){
								$numStr = trim($this->conn->real_escape_string($recMap['exsiccatinumber'])," #num");
								$sql = 'SELECT omenid FROM omexsiccatinumbers '.
									'WHERE ometid = ('.$recMap['ometid'].') AND (exsnumber = "'.$numStr.'")';
								$rs = $this->conn->query($sql);
								if($r = $rs->fetch_object()){
									$recMap['omenid'] = $r->omenid;
								}
								$rs->free();
								if(!array_key_exists('omenid',$recMap)){
									//Exsiccati number needs to be added
									$sql = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber) '.
										'VALUES('.$recMap['ometid'].',"'.$numStr.'")';
									if($this->conn->query($sql)) $recMap['omenid'] = $this->conn->insert_id;
								}
							}
						}
						//If exsiccati info is there, but we can't link to an indexed exsiccati, then lets keep that info and put in occurrenceRemarks
						if(array_key_exists('exsiccatititle',$recMap) && $recMap['exsiccatititle'] && (!array_key_exists('ometid',$recMap) || !$recMap['ometid'])){
							$exsStr = $recMap['exsiccatititle'];
							if(array_key_exists('exsiccatinumber',$recMap) && $recMap['exsiccatinumber']){
								$exsStr .= ', '.$recMap['exsiccatinumber'].'; ';
							}
							$occRemarks = $recMap['occurrenceremarks'];
							if($occRemarks) $occRemarks .= '; ';
							$recMap['occurrenceremarks'] = $occRemarks.$exsStr;
						}

						//Load record
						if($catNum){
							$occid = 0;
							//Check to see if regular expression term is needed to extract correct part of catalogNumber
							$deltaCatNum = $this->getPrimaryKey($catNum);
							if ($deltaCatNum!='') { $catNum = $deltaCatNum; }

							//Remove exsiccati fields
							$activeFields = array_keys($recMap);
							if(array_search('ometid',$activeFields) !== false) unset($activeFields[array_search('ometid',$activeFields)]);
							if(array_search('omenid',$activeFields) !== false) unset($activeFields[array_search('omenid',$activeFields)]);
							if(array_search('exsiccatititle',$activeFields) !== false) unset($activeFields[array_search('exsiccatititle',$activeFields)]);
							if(array_search('exsiccatinumber',$activeFields) !== false) unset($activeFields[array_search('exsiccatinumber',$activeFields)]);

							//Check to see if matching record already exists in database
							$termArr = array();
							if($this->matchCatalogNumber) $termArr[] = '(o.catalognumber IN("'.$catNum.'"'.(substr($catNum,0,1)=='0'?',"'.ltrim($catNum,"0 ").'"':'').'))';
							if($this->matchOtherCatalogNumbers){
								$termArr[] = '(o.othercatalognumbers IN("'.$catNum.'"'.(substr($catNum,0,1)=='0'?',"'.ltrim($catNum,"0 ").'"':'').'))';
								$termArr[] = '(i.identifierValue = "'.$catNum.'")';
							}
							if($termArr){
								$sql = 'SELECT DISTINCT o.occid'.(!array_key_exists('occurrenceremarks',$recMap)?',o.occurrenceremarks':'').
									($activeFields?','.implode(',',$activeFields):'').' '.
									'FROM omoccurrences o LEFT JOIN omoccuridentifiers i ON o.occid = i.occid '.
									'WHERE (o.collid = '.$this->activeCollid.') AND ('.implode(' OR ', $termArr).')';
								//echo $sql;
								$rs = $this->conn->query($sql);
								if($r = $rs->fetch_assoc()){
									//Record already exists, thus just append values to record
									$occid = $r['occid'];
									if($activeFields){
										$updateValueArr = array();
										$occRemarkArr = array();
										foreach($activeFields as $activeField){
											$activeValue = $this->cleanInString($recMap[$activeField]);
											if(!trim($r[$activeField])){
												//Field is empty for existing record, thus load new data
												$type = (array_key_exists('type',$symbMap[$activeField])?$symbMap[$activeField]['type']:'string');
												$size = (array_key_exists('size',$symbMap[$activeField])?$symbMap[$activeField]['size']:0);
												if($type == 'numeric'){
													if(is_numeric($activeValue)){
														$updateValueArr[$activeField] = $activeValue;
													}
													else{
														//Not numeric, thus load into occRemarks
														//$occRemarkArr[$activeField] = $activeValue;
													}
												}
												elseif($type == 'date'){
													$dateStr = $this->formatDate($activeValue);
													if($dateStr){
														$updateValueArr[$activeField] = $activeValue;
													}
													else{
														//Not valid date, thus load into verbatiumEventDate or occRemarks
														if($activeField == 'eventdate'){
															if(!array_key_exists('verbatimeventdate',$updateValueArr) || $updateValueArr['verbatimeventdate']){
																$updateValueArr['verbatimeventdate'] = $activeValue;
															}
														}
														else{
															//$occRemarkArr[$activeField] = $activeValue;
														}
													}
												}
												else{
													//Type assumed to be a string
													if($size && strlen($activeValue) > $size){
														$activeValue = substr($activeValue,0,$size);
													}
													$updateValueArr[$activeField] = $activeValue;
												}
											}
											elseif($activeValue != $r[$activeField]){
												//Target field is not empty and values not equal, thus add value into occurrenceRemarks
												//$occRemarkArr[$activeField] = $activeValue;
											}
										}
										$updateFrag = '';
										foreach($updateValueArr as $k => $uv){
											$updateFrag .= ','.$k.'="'.$this->encodeString($uv).'"';
										}
										if($occRemarkArr){
											$occStr = '';
											foreach($occRemarkArr as $k => $orv){
												$occStr .= ','.$k.': '.$this->encodeString($orv);
											}
											$updateFrag .= ',occurrenceremarks="'.($r['occurrenceremarks']?$r['occurrenceremarks'].'; ':'').substr($occStr,1).'"';
										}
										if($updateFrag){
											$sqlUpdate = 'UPDATE omoccurrences SET '.substr($updateFrag,1).' WHERE occid = '.$occid;
											if(!$this->conn->query($sqlUpdate)){
												$this->logOrEcho("ERROR: Unable to update existing record with new skeletal record ");
												$this->logOrEcho("SQL : $sqlUpdate ",1);
											}
										}
									}
								}
								$rs->free();
							}
							if(!$occid){
								//Insert new record
								if($activeFields){
									$sqlIns1 = 'INSERT INTO omoccurrences(collid,'.($this->matchCatalogNumber?'catalogNumber':'othercatalogNumbers').',processingstatus,dateentered';
									$sqlIns2 = 'VALUES ('.$this->activeCollid.',"'.$catNum.'","unprocessed","'.date('Y-m-d H:i:s').'"';
									foreach($activeFields as $aField){
										$sqlIns1 .= ','.$aField;
										$value = $this->cleanInString($recMap[$aField]);
										$type = (array_key_exists('type',$symbMap[$aField])?$symbMap[$aField]['type']:'string');
										$size = (array_key_exists('size',$symbMap[$aField])?$symbMap[$aField]['size']:0);
										if($type == 'numeric'){
											if(is_numeric($value)){
												$sqlIns2 .= ",".$value;
											}
											else{
												$sqlIns2 .= ",NULL";
											}
										}
										elseif($type == 'date'){
											$dateStr = $this->formatDate($value);
											if($dateStr){
												$sqlIns2 .= ',"'.$dateStr.'"';
											}
											else{
												$sqlIns2 .= ",NULL";
												//Not valid date, thus load into verbatiumEventDate if it's the eventDate field
												if($aField == 'eventdate' && !array_key_exists('verbatimeventdate',$symbMap)){
													$sqlIns1 .= ',verbatimeventdate';
													$sqlIns2 .= ',"'.$value.'"';
												}
											}
										}
										else{
											if($size && strlen($value) > $size){
												$value = substr($value,0,$size);
											}
											if($value){
												$sqlIns2 .= ',"'.$this->encodeString($value).'"';
											}
											else{
												$sqlIns2 .= ',NULL';
											}
										}
									}
									$sqlIns = $sqlIns1.') '.$sqlIns2.')';
									if($this->conn->query($sqlIns)){
										$occid = $this->conn->insert_id;
									}
									else{
										$this->logOrEcho('ERROR trying to load new skeletal record: '.$this->conn->error);
										//$this->logOrEcho("SQL : $sqlIns ",1);
									}
								}
							}
							//Load Exsiccati if it exists
							if(isset($recMap['omenid']) && $occid){
								$sqlExs ='INSERT INTO omexsiccatiocclink(omenid,occid) VALUES('.$recMap['omenid'].','.$occid.')';
								if(!$this->conn->query($sqlExs)){
									$this->logOrEcho('ERROR linking record to exsiccati ('.$recMap['omenid'].'-'.$occid.'): '.$this->conn->error);
									//$this->logOrEcho('SQL : '.$sqlExs,1);
								}
							}
						}
						unset($recMap);
					}
				}
				else{
					$this->logOrEcho("ERROR: Failed to locate catalognumber MD within file (".$filePath."),  ",1);
					return false;
				}
			}
			$this->logOrEcho("Skeletal file loaded ",1);
			fclose($fh);
			//if($this->keepOrig){
			//Skeletal data files are small, thus let's keep them by default
			if(true){
				$fileName = substr($filePath,strrpos($filePath,'/')).'.orig_'.time();
				if(!file_exists($this->targetPathBase.$this->targetPathFrag.'orig_skeletal')){
					mkdir($this->targetPathBase.$this->targetPathFrag.'orig_skeletal');
				}
				if(!rename($filePath,$this->targetPathBase.$this->targetPathFrag.'orig_skeletal'.$fileName)){
					$this->logOrEcho("ERROR: unable to move (".$filePath.") ",1);
				}
			}
			else{
				if(!unlink($filePath)){
					$this->logOrEcho("ERROR: unable to delete file (".$filePath.") ",1);
				}
			}
		}
		else{
			$this->logOrEcho("ERROR: Can't open skeletal file ".$filePath." ");
		}
	}

	/**
	 * Examine an xml file, and if it conforms to supported expectations,
	 * add the data it contains to the Symbiota database.
	 * Currently supported expectations are: (1) the GPI/ALUKA/LAPI schema
	 * and (2) RDF/XML containing oa/oad annotations asserting new occurrence
	 * records in dwcFP, supporting the NEVP TCN.
	 *
	 * @param fileName the name of the xml file to process.
	 * @param pathFrag the path from sourcePathBase to the file to process.
	 */
	private function processXMLFile($fileName,$pathFrag='') {
		if ($this->serverRoot) {
			$foundSchema = false;
			$xml = XMLReader::open($this->sourcePathBase.$pathFrag.$fileName);
			if($xml->read()) {
				// $this->logOrEcho($fileName." first node: ". $xml->name);
				if ($xml->name=="DataSet") {
					$xml = XMLReader::open($this->sourcePathBase.$pathFrag.$fileName);
					$lapischema = $this->serverRoot . "/collections/admin/schemas/lapi_schema_v2.xsd";
					$xml->setParserProperty(XMLReader::VALIDATE, true);
					if (file_exists($lapischema)) {
						$isLapi = $xml->setSchema($lapischema);
					}
					else {
						$this->logOrEcho("ERROR: Can't find $lapischema",1);
					}
					// $this->logOrEcho($fileName." valid lapi xml:" . $xml->isValid() . " [" . $isLapi .  "]");
					if ($xml->isValid() && $isLapi) {
						// File complies with the Aluka/LAPI/GPI schema
						$this->logOrEcho('Processing GPI batch file: '.$pathFrag.$fileName);
						if (class_exists('GPIProcessor')) {
							$processor = new GPIProcessor();
							$result = $processor->process($this->sourcePathBase.$pathFrag.$fileName);
							$foundSchema = $result->couldparse;
							if (!$foundSchema || $result->failurecount>0) {
								$this->logOrEcho("ERROR: Errors processing $fileName: $result->errors.",1);
							}
						}
						else {
							// fail gracefully if this instalation isn't configured with this parser.
							$this->logOrEcho("ERROR: SpecProcessorGPI.php not available.",1);
						}
					}
				}
				elseif ($xml->name=="rdf:RDF") {
					// $this->logOrEcho($fileName." has oa:" . $xml->lookupNamespace("oa"));
					// $this->logOrEcho($fileName." has oad:" . $xml->lookupNamespace("oad"));
					// $this->logOrEcho($fileName." has dwcFP:" . $xml->lookupNamespace("dwcFP"));
					$hasAnnotation = $xml->lookupNamespace("oa");
					$hasDataAnnotation = $xml->lookupNamespace("oad");
					$hasdwcFP = $xml->lookupNamespace("dwcFP");
					// Note: contra the PHP xmlreader documentation, lookupNamespace
					// returns the namespace string not a boolean.
					if ($hasAnnotation && $hasDataAnnotation && $hasdwcFP) {
						// File is likely an annotation containing DarwinCore data.
						$this->logOrEcho('Processing RDF/XML annotation file: '.$pathFrag.$fileName);
						if (class_exists('NEVPProcessor')) {
							$processor = new NEVPProcessor();
							$result = $processor->process($this->sourcePathBase.$pathFrag.$fileName);
							$foundSchema = $result->couldparse;
							if (!$foundSchema || $result->failurecount>0) {
								$this->logOrEcho("ERROR: Errors processing $fileName: $result->errors.",1);
							}
						}
						else {
							// fail gracefully if this instalation isn't configured with this parser.
							$this->logOrEcho("ERROR: SpecProcessorNEVP.php not available.",1);
						}
					}
				}
				$xml->close();
				if ($foundSchema>0) {
					$this->logOrEcho("Proccessed $pathFrag$fileName, records: $result->recordcount, success: $result->successcount, failures: $result->failurecount, inserts: $result->insertcount, updates: $result->updatecount.");
					if ($result->imagefailurecount>0) {
						$this->logOrEcho("ERROR: not moving (".$fileName."), image failure count " . $result->imagefailurecount . " greater than zero.",1);
					}
					else {
						$oldFile = $this->sourcePathBase.$pathFrag.$fileName;
						if($this->keepOrig){
							$newFileName = substr($pathFrag,strrpos($pathFrag,'/')).'orig_'.time().'.'.$fileName;
							if(!file_exists($this->targetPathBase.$this->targetPathFrag.'orig_xml')){
								mkdir($this->targetPathBase.$this->targetPathFrag.'orig_xml');
							}
							if(!rename($oldFile,$this->targetPathBase.$this->targetPathFrag.'orig_xml/'.$newFileName)){
								$this->logOrEcho("ERROR: unable to move (".$oldFile." =>".$newFileName.") ",1);
							}
						}
						else {
							if(!unlink($oldFile)){
								$this->logOrEcho("ERROR: unable to delete file (".$oldFile.") ",1);
							}
						}
					}
				}
				else {
					$this->logOrEcho("ERROR: Unable to match ".$pathFrag.$fileName." to a known schema.",1);
				}
			}
			else {
				$this->logOrEcho("ERROR: XMLReader couldn't read ".$pathFrag.$fileName,1);
			}
		}
	}

	private function getRecordArr($fh, $delimiter){
		if(!$delimiter) return;
		$recordArr = Array();
		if($delimiter == 'csv'){
			$recordArr = fgetcsv($fh);
		}
		else{
			$recordStr = fgets($fh);
			if($recordStr) $recordArr = explode($delimiter,$recordStr);
		}
		return $recordArr;
	}

	private function updateCollectionStats(){
		if($this->dbMetadata && $this->collProcessedArr){
			//Do some more cleaning of the data after it haas been indexed in the omoccurrences table
			$occurMain = new OccurrenceMaintenance($this->conn);

			$this->logOrEcho('Cleaning house...');
			$collString = implode(',',$this->collProcessedArr);
			$occurMain->setCollidStr($collString);
			if(!$occurMain->generalOccurrenceCleaning()){
				$errorArr = $occurMain->getErrorArr();
				foreach($errorArr as $errorStr){
					$this->logOrEcho($errorStr,1);
				}
			}

			$this->logOrEcho('Protecting sensitive species...');
			$protectCnt = $occurMain->protectRareSpecies();
			$this->logOrEcho($protectCnt.' records protected',1);

			$this->logOrEcho('Updating statistics...');
			foreach($this->collProcessedArr as $collid){
				if(!$occurMain->updateCollectionStatsBasic($collid)){
					$errorArr = $occurMain->getErrorArr();
					foreach($errorArr as $errorStr){
						$this->logOrEcho($errorStr,1);
					}
				}
			}
			$occurMain->__destruct();

			$this->logOrEcho('Populating recordID UUIDs for all records...');
			$uuidManager = new UuidFactory($this->conn);
			$uuidManager->setSilent(1);
			$uuidManager->populateGuids();
			$uuidManager->__destruct();
			$this->logOrEcho('Stats update completed');
		}
	}

	private function sendMetadata($email,$mdFileName){
		if($email && $mdFileName){
			$subject = 'Images processed on '.date('Y-m-d');

			$separator = md5(time());
			$eol = "\r\n";

			$headers = 'MIME-Version: 1.0 '.$eol.
				'Content-Type: multipart/mixed; boundary="'.$separator.'"'.$eol;
				'To: '.$email.$eol.
				'From: Admin <seinetAdmin@asu.edu> '.$eol.
				'Content-Transfer-Encoding: 8bit'.$eol.
				'This is a MIME encoded message.'.$eol;

			$url = 'http://swbiodiversity.org/seinet/collections/misc/specprocessor/index.php?tabindex=1&collid='.$this->activeCollid;
			$body = "--".$separator.$eol.
				'Content-Type: text/html; charset=iso-8859-1'.$eol.
				'Content-Transfer-Encoding: 8bit'.$eol.
				'Images in the attached file have been processed and are ready to be uploaded into your collection. '.
				'This can be done using the image loading tools located in the Processing Tools (see link below).'.
				'<a href="'.$url.'">'.$url.'</a>'.
				'<br/>If you have problems with the new password, contact the System Administrator ';

			//Add attachment
			$fname = substr(strrchr($mdFileName, "/"), 1);
			$data = file_get_contents($mdFileName);
			$body .= "--" . $separator . $eol.
				'Content-Type: application/octet-stream; name="'.$fname.'"'.$eol.
				'Content-Transfer-Encoding: base64'.$eol.
				'Content-Disposition: attachment'.$eol.
				chunk_split( base64_encode($data)).$eol.
				'--'.$separator.'--';

			if(!mail($email,$subject,$body,$headers)){
				echo 'Mail send ... ERROR!';
				print_r( error_get_last() );
			}
		}
	}

	//Misc data functions
	private function setImageTableMap(){
		if(!$this->imageTableMap){
			$sql = 'SHOW COLUMNS FROM images';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$field = strtolower($r->Field);
					$type = $r->Type;
					if(strpos($type, 'int') !== false){
						$this->imageTableMap[$field]['type'] = 'i';
					}
					elseif(strpos($type, 'double') !== false || strpos($type, 'decimal') !== false){
						$this->imageTableMap[$field]['type'] = 'd';
					}
					elseif(strpos($type, 'date') !== false){
						$this->imageTableMap[$field]['type'] = 's';
					}
					else{
						$this->imageTableMap[$field]['type'] = 's';
						if(preg_match('/\(\d+\)$/', $type, $matches)){
							$this->imageTableMap[$field]['size'] = substr($matches[0], 1, strlen($matches[0])-2);
						}
					}
				}
				$rs->free();
				unset($this->imageTableMap['imgid']);
				unset($this->imageTableMap['dynamicProperties']);
				unset($this->imageTableMap['initialTimestamp']);
			}
		}
	}

	//Setter and getter functions
	public function setCollArr($cArr){
		if($cArr){
			if(is_array($cArr)){
				$this->collArr = $cArr;
				//Set additional collection info
				if($this->dbMetadata){
					$sql = 'SELECT collid, institutioncode, collectioncode, collectionname, managementtype FROM omcollections WHERE (collid IN('.implode(',',array_keys($cArr)).'))';
					if($rs = $this->conn->query($sql)){
						if($rs->num_rows){
							while($r = $rs->fetch_object()){
								$this->collArr[$r->collid]['instcode'] = $r->institutioncode;
								$this->collArr[$r->collid]['collcode'] = $r->collectioncode;
								$this->collArr[$r->collid]['collname'] = $r->collectionname;
								$this->collArr[$r->collid]['managementtype'] = $r->managementtype;
							}
						}
						else{
							$this->logOrEcho('ABORT: unable to get collection metadata from database (collids might be wrong) ');
							exit('ABORT: unable to get collection metadata from database');
						}
						$rs->free();
					}
					else{
						$this->logOrEcho('ABORT: unable run SQL to obtain additional collection metadata: '.$this->conn->error);
						exit('ABORT: unable run SQL to obtain additional collection metadata'.$this->conn->error);
					}
				}
			}
		}
		else{
			$this->logOrEcho("Error: collection array does not exist");
			exit("ABORT: collection array does not exist");
		}
	}

	public function setSourcePathBase($p){
		if($p && substr($p,-1) != '/' && substr($p,-1) != "\\") $p .= '/';
		$this->sourcePathBase = $p;
	}

	public function getSourcePathBase(){
		return $this->sourcePathBase;
	}

	public function setTargetPathBase($p){
		if($p && substr($p,-1) != '/' && substr($p,-1) != "\\") $p .= '/';
		$this->targetPathBase = $p;
	}

	public function getTargetPathBase(){
		return $this->targetPathBase;
	}

	public function setImgUrlBase($u){
		if($u && substr($u,-1) != '/') $u .= '/';
		$this->imgUrlBase = $u;
	}

	public function getImgUrlBase(){
		return $this->imgUrlBase;
	}

	public function setServerRoot($path) {
		$this->serverRoot = $path;
	}

	public function setMatchCatalogNumber($b){
		if($b) $this->matchCatalogNumber = true;
		else $this->matchCatalogNumber = false;
	}

	public function setMatchOtherCatalogNumbers($b){
		if($b) $this->matchOtherCatalogNumbers = true;
		else $this->matchOtherCatalogNumbers = false;
	}

	public function setWebPixWidth($w){
		$this->webPixWidth = $w;
	}

	public function getWebPixWidth(){
		return $this->webPixWidth;
	}

	public function setTnPixWidth($tn){
		$this->tnPixWidth = $tn;
	}

	public function getTnPixWidth(){
		return $this->tnPixWidth;
	}

	public function setLgPixWidth($lg){
		$this->lgPixWidth = $lg;
	}

	public function getLgPixWidth(){
		return $this->lgPixWidth;
	}

	public function setWebFileSizeLimit($size){
		$this->webFileSizeLimit = $size;
	}

	public function getWebFileSizeLimit(){
		return $this->webFileSizeLimit;
	}

	public function setLgFileSizeLimit($size){
		$this->lgFileSizeLimit = $size;
	}

	public function getLgFileSizeLimit(){
		return $this->lgFileSizeLimit;
	}

	public function setJpgQuality($q){
		$this->jpgQuality = $q;
	}

	public function getJpgQuality(){
		return $this->jpgQuality;
	}

	public function setMedProcessingCode($c){
		$this->medProcessingCode = $c;
	}

	public function setTnProcessingCode($c){
		$this->tnProcessingCode = $c;
	}

	public function setLgProcessingCode($c){
		$this->lgProcessingCode = $c;
	}

	public function setMedSourceSuffix($s){
		$this->medSourceSuffix = $s;
	}

	public function setTnSourceSuffix($s){
		$this->tnSourceSuffix = $s;
	}

	public function setLgSourceSuffix($s){
		$this->lgSourceSuffix = $s;
	}

	public function setKeepOrig($c){
		$this->keepOrig = $c;
	}

	public function getKeepOrig(){
		return $this->keepOrig;
	}

	public function setCustomStoredProcedure($c){
		$this->customStoredProcedure = $c;
	}

	public function getCustomStoredProcedure(){
		return $this->customStoredProcedure;
	}

	public function setSkeletalFileProcessing($c){
		$this->skeletalFileProcessing = $c;
	}

	public function getSkeletalFileProcessing(){
		return $this->skeletalFileProcessing;
	}

	public function setCreateNewRec($c){
		$this->createNewRec = $c;
	}

	public function getCreateNewRec(){
		return $this->createNewRec;
	}

	public function setCopyOverImg($c){
		if($c == 1){
			$this->imgExists = 2;
		}
		else{
			$this->imgExists = 1;
		}
	}

	public function getImgExists(){
		return $this->imgExists;
	}

	public function setImgExists($c){
		$this->imgExists = $c;
	}

	public function setDbMetadata($v){
		$this->dbMetadata = $v;
	}

	public function setUseImageMagick($useIM){
		$this->processUsingImageMagick = $useIM;
	}

	public function setLogMode($c){
		$this->logMode = $c;
	}

	public function getLogMode(){
		return $this->logMode;
	}

	public function setLogPath($path){
		if($path && substr($path,-1) != '/' && substr($path,-1) != "\\") $path .= '/';
		$this->logPath = $path;
	}

	//Misc support functions
	private function formatDate($inStr){
		$dateStr = trim($inStr);
		if(!$dateStr) return;
		$t = '';
		$y = '';
		$m = '00';
		$d = '00';
		if(preg_match('/\d{2}:\d{2}:\d{2}/',$dateStr,$match)){
			//Extract time
			$t = $match[0];
		}
		if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})\D*/',$dateStr,$match)){
			//Format: yyyy-mm-dd, yyyy-m-d
			$y = $match[1];
			$m = $match[2];
			$d = $match[3];
		}
		elseif(preg_match('/^(\d{1,2})\s{1}(\D{3,})\.*\s{1}(\d{2,4})/',$dateStr,$match)){
			//Format: dd mmm yyyy, d mmm yy
			$d = $match[1];
			$mStr = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			$m = $this->monthNames[$mStr];
		}
		elseif(preg_match('/^(\d{1,2})-(\D{3,})-(\d{2,4})/',$dateStr,$match)){
			//Format: dd-mmm-yyyy
			$d = $match[1];
			$mStr = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			$m = $this->monthNames[$mStr];
		}
		elseif(preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})/',$dateStr,$match)){
			//Format: mm/dd/yyyy, m/d/yy
			$m = $match[1];
			$d = $match[2];
			$y = $match[3];
		}
		elseif(preg_match('/^(\D{3,})\.*\s{1}(\d{1,2}),{0,1}\s{1}(\d{2,4})/',$dateStr,$match)){
			//Format: mmm dd, yyyy
			$mStr = $match[1];
			$d = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			$m = $this->monthNames[$mStr];
		}
		elseif(preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})/',$dateStr,$match)){
			//Format: mm-dd-yyyy, mm-dd-yy
			$m = $match[1];
			$d = $match[2];
			$y = $match[3];
		}
		elseif(preg_match('/^(\D{3,})\.*\s([1,2]{1}[0,5-9]{1}\d{2})/',$dateStr,$match)){
			//Format: mmm yyyy
			$mStr = strtolower(substr($match[1],0,3));
			$m = $this->monthNames[$mStr];
			$y = $match[2];
		}
		elseif(preg_match('/([1,2]{1}[0,5-9]{1}\d{2})/',$dateStr,$match)){
			//Format: yyyy
			$y = $match[1];
		}
		if($y){
			if(strlen($y) == 2){
				if($y < 20) $y = '20'.$y;
				else $y = '19'.$y;
			}
			if(strlen($m) == 1) $m = '0'.$m;
			if(strlen($d) == 1) $d = '0'.$d;
			$dateStr = $y.'-'.$m.'-'.$d;
		}
		else{
			$timeStr = strtotime($dateStr);
			if($timeStr) $dateStr = date('Y-m-d H:i:s', $timeStr);
		}
		if($t){
			$dateStr .= ' '.$t;
		}
		return $dateStr;
	}

	private function formatScientificName($inStr){
		$sciNameStr = trim($inStr);
		$sciNameStr = preg_replace('/\s\s+/', ' ',$sciNameStr);
		$tokens = explode(' ',$sciNameStr);
		if($tokens){
			$sciNameStr = array_shift($tokens);
			if(strlen($sciNameStr) < 2) $sciNameStr = ' '.array_shift($tokens);
			if($tokens){
				$term = array_shift($tokens);
				$sciNameStr .= ' '.$term;
				if($term == 'x') $sciNameStr .= ' '.array_shift($tokens);
			}
			$tRank = '';
			$infraSp = '';
			foreach($tokens as $c => $v){
				switch($v) {
					case 'subsp.':
					case 'subsp':
					case 'ssp.':
					case 'ssp':
					case 'subspecies':
					case 'var.':
					case 'var':
					case 'variety':
					case 'forma':
					case 'form':
					case 'f.':
					case 'fo.':
						if(array_key_exists($c+1,$tokens) && ctype_lower($tokens[$c+1])){
							$tRank = $v;
							if(substr($tRank,-1) != '.' && ($tRank == 'ssp' || $tRank == 'subsp' || $tRank == 'var')) $tRank .= '.';
							$infraSp = $tokens[$c+1];
						}
				}
			}
			if($infraSp){
				$sciNameStr .= ' '.$tRank.' '.$infraSp;
			}
		}
		return $sciNameStr;
	}

	private function uriExists($url) {
		$exists = false;
		//First simple check
		if(file_exists($url)) return true;

		$localUrl = '';
		if(substr($url,0,1) == '/'){
			if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
				$url = $GLOBALS['imageDomain'].$url;
			}
			elseif($GLOBALS['imageRootUrl'] && strpos($url,$GLOBALS['imageRootUrl']) === 0){
				$localUrl = str_replace($GLOBALS['imageRootUrl'],$GLOBALS['imageRootPath'],$url);
			}
			else{
				$urlPrefix = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = 'https://';
				$urlPrefix .= $_SERVER['SERVER_NAME'];
				if($_SERVER['SERVER_PORT'] && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) $urlPrefix .= ':'.$_SERVER['SERVER_PORT'];
				$url = $urlPrefix.$url;
			}
		}

		//Second simple check
		if(file_exists($url)) return true;
		if($localUrl && file_exists($localUrl)) return true;

		//Third check
		if(!$exists){
			// Version 4.x supported
			$handle   = curl_init($url);
			if (false === $handle){
				$exists = false;
			}
			curl_setopt($handle, CURLOPT_HEADER, false);
			curl_setopt($handle, CURLOPT_FAILONERROR, true);  // this works
			curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") ); // request as if Firefox
			curl_setopt($handle, CURLOPT_NOBODY, true);
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
			$exists = curl_exec($handle);
			curl_close($handle);
		}

		//One last check
		if(!$exists){
			if($fh = @fopen($url,'r')){
				$exists = true;
				@fclose($fh);
			}
		}

		//Test to see if file is an image
		if(!@exif_imagetype($url)) $exists = false;

		return $exists;
	}

	private function encodeString($inStr){
		global $CHARSET;
		$retStr = trim($inStr);

		if($inStr){
			if(strtolower($CHARSET) == "utf-8" || strtolower($CHARSET) == "utf8"){
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

	private function cleanInString($inStr){
		$retStr = trim($inStr);
		$retStr = str_replace(array(chr(10),chr(11),chr(13),chr(20),chr(30)),' ',$retStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}

	protected function logOrEcho($str,$indent = 0){
		if($this->logMode > 1){
			if($this->logFH){
				if($indent) $str = "\t".$str;
				fwrite($this->logFH,strip_tags($str)."\n");
			}
		}
		if($this->logMode == 1 || $this->logMode == 3){
			echo '<li '.($indent?'style="margin-left:'.($indent*15).'px"':'').'>'.$str."</li>\n";
			@ob_flush();
			@flush();
		}
	}
}
?>