<?php
/**
 *  Base class for managers.  Supplies $conn for connection, $id for primary key, and
 *  $errorMessage/getErrorMessage(), along with supporting clean methods cleanOutStr()
 *  cleanInStr() and cleanInArray();
 */

include_once($SERVER_ROOT.'/config/dbconnection.php');

class Manager  {
	protected $conn = null;
	protected $isConnInherited = false;
	protected $id = null;
	protected $errorMessage = '';
	protected $warningArr = array();

	protected $logFH;
	protected $verboseMode = 0;

	public function __construct($id=null, $conType='readonly', $connOverride = null){
		if($connOverride){
			$this->conn = $connOverride;
			$this->isConnInherited = true;
		}
		else $this->conn = MySQLiConnectionFactory::getCon($conType);
 		if($id != null || is_numeric($id)){
	 		$this->id = $id;
 		}
	}

 	public function __destruct(){
 		if(!($this->conn === null) && !$this->isConnInherited) $this->conn->close();
		if($this->logFH){
			fwrite($this->logFH,"\n\n");
			fclose($this->logFH);
		}
	}

	protected function getConfigAttribute($attrName){
		$attrValue = '';
		if($attrName){
			$sql = 'SELECT attributeValue FROM adminconfig WHERE attributeName = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('s', $attrName);
				$stmt->execute();
				$stmt->bind_result($attrValue);
				$stmt->fetch();
				$stmt->close();
			}
		}
		return $attrValue;
	}

	protected function setLogFH($logPath){
		$this->logFH = fopen($logPath, 'a');
	}

	protected function logOrEcho($str, $indexLevel=0, $tag = 'li'){
		//verboseMode: 0 = silent, 1 = log, 2 = out to screen, 3 = both
		if($str && $this->verboseMode){
			if($this->verboseMode == 3 || $this->verboseMode == 1){
				if($this->logFH){
					fwrite($this->logFH,str_repeat("\t", $indexLevel).strip_tags($str)."\n");
				}
			}
			if($this->verboseMode == 3 || $this->verboseMode == 2){
				echo '<'.$tag.' style="'.($indexLevel?'margin-left:'.($indexLevel*15).'px':'').'">'.$str.'</'.$tag.'>';
				if (ob_get_level() > 0) {
					ob_flush();
				}
				flush();
			}
		}
	}

	public function setVerboseMode($c){
		if(is_numeric($c)) $this->verboseMode = $c;
	}

	public function getVerboseMode(){
		return $this->verboseMode;
	}

	public function getErrorMessage() {
		return $this->errorMessage;
	}

   public function getWarningArr(){
		return $this->warningArr;
	}

	public function getDomain(){
		$domain = 'http://';
		if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $domain = 'https://';
		if(!empty($GLOBALS['SERVER_HOST'])){
			if(substr($GLOBALS['SERVER_HOST'], 0, 4) == 'http') $domain = $GLOBALS['SERVER_HOST'];
			else $domain .= $GLOBALS['SERVER_HOST'];
		}
		else $domain .= $_SERVER['SERVER_NAME'];
		if($_SERVER['SERVER_PORT'] && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 && !strpos($domain, ':'.$_SERVER['SERVER_PORT'])){
			$domain .= ':'.$_SERVER['SERVER_PORT'];
		}
		$domain = filter_var($domain, FILTER_SANITIZE_URL);
		return $domain;
	}

	protected function cleanOutStr($str){
		$str = htmlspecialchars($str);
		return $str;
	}

	protected function cleanInStr($str){
		$newStr = trim($str);
		if($newStr){
			$newStr = preg_replace('/\s\s+/', ' ',$newStr);
			$newStr = $this->conn->real_escape_string($newStr);
		}
		return $newStr;
	}

	protected function cleanInArray($arr){
		$newArray = Array();
		foreach($arr as $key => $value){
			$newArray[$this->cleanInStr($key)] = $this->cleanInStr($value);
		}
		return $newArray;
	}

	protected function encodeString($inStr){
		$retStr = '';
		if($inStr){
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
			$inStr = str_replace($badwordchars, $fixedwordchars, $inStr);

			if($inStr){
				if(strtolower($GLOBALS['CHARSET']) == "utf-8" || strtolower($GLOBALS['CHARSET']) == "utf8"){
					if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
						$retStr = utf8_encode($inStr);
						//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
					}
				}
				elseif(strtolower($GLOBALS['CHARSET']) == "iso-8859-1"){
					if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
						$retStr = utf8_decode($inStr);
						//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
					}
				}
				//$line = iconv('macintosh', 'UTF-8', $line);
				//mb_detect_encoding($buffer, 'windows-1251, macroman, UTF-8');
	 		}
		}
		return $retStr;
	}
}
