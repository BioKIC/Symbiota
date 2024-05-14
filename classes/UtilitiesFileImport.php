<?php
include_once('Manager.php');

class UtilitiesFileImport extends Manager {

	protected $targetPath = false;
	protected $fileName = false;
	private $fileHandler = null;
	protected $delimiter = ',';

	protected $targetFieldMap;
	protected $translationMap = null;
	protected $fieldMap = array();		//array(sourceName => symbiotaIndex)

	public function __construct(){
		parent::__construct(null, 'write');
	}

	public function __destruct(){
		if($this->fileHandler) fclose($this->fileHandler);
		parent::__destruct();
	}

	//File import functions
	public function importFile(){
		if($this->setTargetPath()){
			if(!empty($_POST['importFileOverride'])){
				$fileName = substr($_POST['importFileOverride'],strrpos($_POST['importFileOverride'], '/') + 1);
				if(copy($_POST['importFileOverride'], $this->targetPath . $this->fileName)){
					$this->fileName = $fileName;
				}
			}
			elseif(array_key_exists('importFile', $_FILES)){
				if($fileName = $this->cleanFileName($_FILES['importFile']['name'])){
					if(move_uploaded_file($_FILES['importFile']['tmp_name'], $this->targetPath . $fileName)){
						$this->fileName = $fileName;
					}
					else{
						$this->errorMessage = 'ERROR uploading file (code ' . $_FILES['importFile']['error'] . '): ';
						if(!is_writable($this->targetPath)){
							$this->errorMessage .= 'Target path ('.$this->targetPath.') is not writable ';
						}
						else{
							$this->errorMessage .= 'File might be too large for the upload limits set within the PHP configurations '.
								'(upload_max_filesize = '.ini_get('upload_max_filesize').'; post_max_size = '.ini_get('post_max_size').')';
						}
						return false;
					}
				}
				else return false;
			}
			//If a zip file, unpackage and assume that last or only file is the occurrrence file
			if($this->fileName && strtolower(substr($this->fileName,-4)) == '.zip'){
				$this->fileName = '';
				$zipFilePath = $this->targetPath . $this->fileName;
				$zip = new ZipArchive;
				$res = $zip->open($zipFilePath);
				if($res === TRUE) {
					for($i = 0; $i < $zip->numFiles; $i++) {
						$fileName = $zip->getNameIndex($i);
						if(substr($fileName,0,2) != '._'){
							$ext = strtolower(substr(strrchr($fileName, '.'), 1));
							if($ext == 'csv' || $ext == 'txt'){
								$zip->extractTo($this->targetPath, $fileName);
								if($cleanFileName = $this->cleanFileName($fileName)){
									if($cleanFileName != $fileName){
										if(rename($this->targetPath . $fileName, $this->targetPath . $cleanFileName)){
											$this->fileName = $cleanFileName;
										}
										else{
											$this->errorMessage = 'ERROR resetting zip content to cleaned filename';
											return false;
										}
									}
									else $this->fileName = $fileName;
								}
							}
						}
					}
				}
				else{
					$this->errorMessage = 'failed, code:' . $res;
					return false;
				}
				$zip->close();
				unlink($zipFilePath);
			}
		}
		return $this->fileName;
	}

	protected function setTargetPath(){
		if($this->targetPath) return true;
		$path = $GLOBALS['TEMP_DIR_ROOT'];
		if(!$path) $path = ini_get('upload_tmp_dir');
		if(!$path) $path = $GLOBALS['SERVER_ROOT'].'/temp';
		if(substr($path,-1) != '/') $path .= '/';
		if(is_dir($path.'data')) $path .= 'data/';
		if(!is_writable($path)){
			$this->errorMessage = 'FATAL ERROR: target directory does not exist or is not writable by web server ('.$this->targetPath.')';
			return false;
		}
		$this->targetPath = $path;
		return $path;
	}

	public function cleanFileName($fileName){
		$supportedExtensions = array('csv','txt','tab','zip');
		$ext = strtolower(substr($fileName, -3));
		if(!in_array($ext, $supportedExtensions)){
			$this->errorMessage = 'Unsupported file type';
			return false;
		}
		$fileName = substr($fileName, 0, -4);
		$fileName = str_replace(array('%20', '%23' ,' ','__'), '_', $fileName);
		$fileName = preg_replace('/[^a-zA-Z0-9\-_]/', '', $fileName);
		$fileName = trim($fileName,' _-');
		if(strlen($fileName) > 30) $fileName = substr($fileName, 0, 30);
		$fileName .= '_' . time() . '.' . $ext;
		return $fileName;
	}

	protected function deleteImportFile(){
		unlink($this->targetPath . $this->fileName);
	}

	//Field mapping functions
	public function getFieldMappingTable(){
		$tableHtml = '<table class="styledtable" style="width:600px;font-family:Arial;font-size:12px;">';
		$tableHtml .= '<tr><th>Source Field</th><th>Target Field</th></tr>';
		$sourceFieldArr = $this->getHeaderArr();
		foreach($sourceFieldArr as $i => $sourceField){
			$tableHtml .= '<tr><td>';
			$tableHtml .= $sourceField;
			$sourceField = strtolower($sourceField);
			$tableHtml .= '<input type="hidden" name="sf['.$i.']" value="'.$sourceField.'" />';
			$translatedSourceField = strtolower($this->getTranslation($sourceField));
			$tableHtml .= '</td><td>';
			$tableHtml .= '<select name="tf['.$i.']" style="background:'.(array_key_exists($translatedSourceField, $this->targetFieldMap)?'':'yellow').'">';
			$tableHtml .= '<option value="">Select Target Field</option>';
			$tableHtml .= '<option value="">-------------------------</option>';
			foreach($this->targetFieldMap as $k => $v){
				$tableHtml .= '<option value="' . $k . '" ' . ($k == $translatedSourceField ? 'SELECTED' : '') . '>' . $v . '</option>';
			}
			$tableHtml .= '</select>';
			$tableHtml .= '</td></tr>';
		}
		$tableHtml .= '</table>';
		return $tableHtml;
	}

	protected function getHeaderArr(){
		$sourceArr = array();
		if($this->fileName){
			$this->fileHandler = fopen($this->targetPath . $this->fileName, 'rb') or die('unable to open file');
			$headerData = fgets($this->fileHandler);
			//Check to see if we can figure out the delimiter, comma delimited it assumed to be the default
			if(strpos($headerData, ',') === false){
				if(strpos($headerData, "\t") !== false){
					$this->delimiter = "\t";
				}
				elseif(strpos($headerData, '|') !== false){
					$this->delimiter = '|';
				}
			}
			//Grab header terms
			$headerArr = Array();
			if($this->delimiter == ','){
				rewind($this->fileHandler);
				$headerArr = fgetcsv($this->fileHandler, 0, $this->delimiter);
			}
			else{
				$headerArr = explode($this->delimiter, $headerData);
			}
			foreach($headerArr as $k => $field){
				$fieldStr = $this->encodeString(trim($field));
				if($fieldStr){
					$sourceArr[$k] = $fieldStr;
				}
			}
		}
		return $sourceArr;
	}

	protected function getRecordArr(){
		$recordArr = Array();
		if($this->delimiter == ','){
			$recordArr = fgetcsv($this->fileHandler,0,$this->delimiter);
		}
		else{
			$record = fgets($this->fileHandler);
			if($record){
				$recordArr = explode($this->delimiter, $record);
				foreach($recordArr as $k => $v) $recordArr[$k] = trim($v);
			}
		}
		return $recordArr;
	}

	public function getTranslation($sourceField){
		$retStr = strtolower($sourceField);
		if($p =strpos($retStr, ':')) $retStr = substr($retStr,$p);
		$retStr = preg_replace('/[^a-z0-9_#-]+/', '', $retStr);
		if(array_key_exists($retStr, $this->translationMap)) $retStr = $this->translationMap[$retStr];
		return $retStr;
	}

	//Setters and getters
	public function getFileName(){
		return $this->fileName;
	}

	public function setFileName($fileName){
		$this->fileName = $fileName;
	}

	public function setTranslationMap($map){
		$this->translationMap = $map;
	}
}
