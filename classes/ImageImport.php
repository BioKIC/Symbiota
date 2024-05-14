<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once("ImageShared.php");

class ImageImport{

	private $conn;

	private $uploadTargetPath;
	private $uploadFileName;

	private $targetArr;
	private $fieldMap = array();		//array(sourceName => symbIndex)
	private $translationMap = array('imageurl'=>'url','accessuri'=>'url','sciname'=>'scientificname');

	private $verbose = 1;

	function __construct() {
		set_time_limit(2000);
		$this->conn = MySQLiConnectionFactory::getCon("write");

		$this->setUploadTargetPath();

		$this->targetArr = array('url','originalUrl','scientificName','tid','photographer','photographerUid','caption',
			'locality','sourceUrl','anatomy','notes','owner','copyright','sortSequence',
			'institutionCode','collectionCode','catalogNumber','occid');
	}

	function __destruct(){
		if($this->conn) $this->conn->close();
		if(file_exists($this->uploadTargetPath.$this->uploadFileName)){
			//unlink($this->uploadTargetPath.$this->uploadFileName);
		}
	}

	public function loadFile($postArr){
		//fieldMap = array(source field => target field)
		echo "<li>Starting Upload</li>";
		ob_flush();
		flush();
		$basePath = $postArr['basepath'];
		$lgImg = $postArr['lgimg'];
		//Start importing data
		$headerArr = fgetcsv($fh);
		$recordCnt = 0;
		if($this->fieldMap){
			//url field is required (symbIndex == 0)
			if(in_array(0,$this->fieldMap) && $this->fieldMap[0]){
				$sqlBase = 'INSERT INTO images('.implode(',',array_keys($fieldMap)).') ';
				while($recordArr = fgetcsv($fh)){

					if(in_array("sciname",$fieldMap)){


					}
					//Load relavent fields into uploadtaxa table
					$sql = $sqlBase;
					$valueSql = "";


					$recordCnt++;
				}
				echo '<li>'.$recordCnt.' taxon records pre-processed</li>';
				ob_flush();
				flush();
			}
			else{
				echo '<li>ERROR: record skipped, url null</li>';
				ob_flush();
				flush();
			}
			fclose($fh);
		}
	}

	public function setUploadFile($ulFileName){
		if($ulFileName){
			$this->uploadFileName = $ulFileName;
		}
		elseif(array_key_exists('uploadfile',$_FILES)){
			$this->uploadFileName = time().'_'.$_FILES['uploadfile']['name'];
			if(!move_uploaded_file($_FILES['uploadfile']['tmp_name'], $this->uploadTargetPath.$this->uploadFileName)){
				//echo 'Error';
			}
		}
        if(file_exists($this->uploadTargetPath.$this->uploadFileName) && substr($this->uploadFileName,-4) == ".zip"){
			$zip = new ZipArchive;
			$zip->open($this->uploadTargetPath.$this->uploadFileName);
			$zipFile = $this->uploadTargetPath.$this->uploadFileName;
			$fileName = $zip->getNameIndex(0);
			$zip->extractTo($this->uploadTargetPath);
			$zip->close();
			unlink($zipFile);
			$this->uploadFileName = time().'_'.$fileName;
			rename($this->uploadTargetPath.$fileName,$this->uploadTargetPath.$this->uploadFileName);
        }
	}

	//Basic setters and getters
	public function setUploadFileName($fileName){
		$this->uploadFileName = $fileName;
	}

	public function getUploadFileName(){
		return $this->uploadFileName;
	}

	public function getSourceArr(){
		$sourceArr = array();
		$fh = fopen($this->uploadTargetPath.$this->uploadFileName,'rb') or die("Can't open file");
		$headerArr = fgetcsv($fh);
		foreach($headerArr as $k => $field){
			$fieldStr = strtolower(trim($field));
			if($fieldStr){
				$sourceArr[$k] = $fieldStr;
			}
		}
		return $sourceArr;
	}

	public function getTargetArr(){
		return $this->targetArr;
	}

	public function setFieldMap($fm){
		$this->fieldMap = $fm;
	}

	public function getFieldMap(){
		return $this->fieldMap;
	}

	public function setVerbose($verb){
		$this->verbose = $verb;
	}

	public function getTranslation($inStr){
		$retStr = '';
		$inStr = strtolower($inStr);
		if(array_key_exists($inStr,$this->translationMap)) $retStr = $this->translationMap[$inStr];
		return $retStr;
	}

	private function setUploadTargetPath(){
		$tPath = $GLOBALS["tempDirRoot"];
		if(!$tPath){
			$tPath = ini_get('upload_tmp_dir');
		}
		if(!$tPath){
			$tPath = $GLOBALS["serverRoot"]."/temp/downloads";
		}
		if(substr($tPath,-1) != '/') $tPath .= "/";
		$this->uploadTargetPath = $tPath;
    }
}
?>