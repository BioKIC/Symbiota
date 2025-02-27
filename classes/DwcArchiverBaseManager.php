<?php
include_once($SERVER_ROOT . '/classes/Manager.php');

class DwcArchiverBaseManager extends Manager{

	protected $delimiter = ',';
	protected $schemaType;
	protected $fieldArr;
	protected $charSetSource = '';
	protected $charSetOut = '';
	protected $sqlBase;
	private $fileHandler;

	public function __construct($conType, $connOverride){
		parent::__construct(null, $conType, $connOverride);
		$this->charSetSource = strtoupper($GLOBALS['CHARSET']);
		$this->charSetOut = $this->charSetSource;
	}

	public function __destruct(){
		parent::__destruct();
		if($this->fileHandler) fclose($this->fileHandler);
	}

	protected function setFileHandler($filePath){
		$this->fileHandler = fopen($filePath, 'w');
		if(!$this->fileHandler){
			$this->logOrEcho('ERROR establishing File Manager for extension output file ('.$filePath.'), perhaps target folder is not readable by web server.');
			return false;
		}
		//Write out header row
		$this->writeOutRecord(array_keys($this->fieldArr['fields']));
	}

	public function writeOutRecordBlock($occidArr, $targetField='occid'){
		if($occidArr){
			$sql = $this->sqlBase.' WHERE ' . $targetField . ' IN('.implode(',',$occidArr).') ';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_assoc()){
					$this->encodeArr($r);
					$this->addcslashesArr($r);
					$this->writeOutRecord($r);
				}
				$rs->free();
			}
			else{
				$this->logOrEcho('ERROR writing out to extension file: '.$this->conn->error."\n");
				$this->logOrEcho("\tSQL: ".$sql."\n");
			}
		}
	}

	private function writeOutRecord($outputArr){
		if($this->fileHandler){
			if($this->delimiter == ","){
				fputcsv($this->fileHandler, $outputArr);
			}
			else{
				foreach($outputArr as $k => $v){
					$outputArr[$k] = str_replace($this->delimiter,'',$v);
				}
				fwrite($this->fileHandler, implode($this->delimiter,$outputArr)."\n");
			}
		}
	}

	//Misc data support functions
	protected function encodeArr(&$inArr){
		if($this->charSetSource && $this->charSetOut != $this->charSetSource){
			foreach($inArr as $k => $v){
				$inArr[$k] = $this->encodeStr($v);
			}
		}
	}

	protected function encodeStr($inStr){
		$retStr = $inStr;
		if($inStr && $this->charSetSource){
			if($this->charSetOut == 'UTF-8' && $this->charSetSource == 'ISO-8859-1'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == 'ISO-8859-1'){
					$retStr = mb_convert_encoding($inStr, 'UTF-8', 'ISO-8859-1');
				}
			}
			elseif($this->charSetOut == 'ISO-8859-1' && $this->charSetSource == 'UTF-8'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == 'UTF-8'){
					$retStr = mb_convert_encoding($inStr, 'ISO-8859-1', 'UTF-8');
				}
			}
			else{
				$retStr = mb_convert_encoding($inStr, $this->charSetOut, mb_detect_encoding($inStr, 'UTF-8,ISO-8859-1,ISO-8859-15'));
			}
		}
		return $retStr;
	}

	protected function addcslashesArr(&$arr){
		foreach($arr as $k => $v){
			if($v) $arr[$k] = addcslashes($v,"\n\r\\");
		}
	}

	//Setters and getters
	public function setCharSetOut($cs){
		$cs = strtoupper($cs);
		if($cs == 'ISO-8859-1' || $cs == 'UTF-8'){
			$this->charSetOut = $cs;
		}
	}

	public function setSchemaType($type){
		$this->schemaType = $type;
	}

	public function getFieldArr(){
		return $this->fieldArr;
	}

	public function getFieldArrTerms(){
		return $this->fieldArr['terms'];
	}

	public function setDelimiter($d){
		return $this->delimiter = $d;
	}
}
?>