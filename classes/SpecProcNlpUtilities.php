<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceUtilities.php');

class SpecProcNlpUtilities {

	private $conn;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	public static function cleanDwcArr($dwcArrIn){
		$dwcArr = array();
		//Do some cleaning and standardization
		if($dwcArrIn && is_array($dwcArrIn)){
			$dwcArr = array_change_key_case($dwcArrIn);
			if(array_key_exists('scientificname',$dwcArr) && !array_key_exists('sciname',$dwcArr)){
				$dwcArr['sciname'] = $dwcArr['scientificname'];
				unset($dwcArr['scientificname']);
			}
			//Convert to UTF-8
			foreach($dwcArr as $k => $v){
				if($v){
					//If is a latin character set, convert to UTF-8
					if(mb_detect_encoding($v,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
						$dwcArr[$k] = utf8_encode($v);
						//$dwcArr[$k] = iconv("ISO-8859-1//TRANSLIT","UTF-8",$v);
					}
				}
				else{
					unset($dwcArr[$k]);
				}
			}
		}
		return $dwcArr;
	}

	public static function formatDate($inStr){
		return OccurrenceUtilities::formatDate($inStr);
	}

	public static function parseScientificName($inStr){
		return OccurrenceUtilities::parseScientificName($inStr);
	}

	public static function parseVerbatimElevation($inStr){
		return OccurrenceUtilities::parseVerbatimElevation($inStr);
	}

	public static function parseVerbatimCoordinates($inStr,$target=''){
		return OccurrenceUtilities::parseVerbatimCoordinates($inStr,$target='');
	}

	public static function convertUtmToLL($e, $n, $z, $d){
		return OccurrenceUtilities::convertUtmToLL($e, $n, $z, $d);
	}

	//Following functions need to be reworked if they are to be used
	private function parseRecordedBy(){
		$lineArr = explode("\n",$this->rawText);
		//Locate matching lines
		foreach($lineArr as $line){
			//Test for exsiccati title
			if(isset($indicatorTerms['recordedBy'])){
				foreach($indicatorTerms['recordedBy'] as $term){
					if(stripos($line,$term)) $this->fragMatches['recordedBy'] = trim($line);
				}
			}
			if(isset($pregMatchTerms['recordedBy'])){
				foreach($pregMatchTerms['recordedBy'] as $pattern){
					if(preg_match($pattern,$line,$m)){
						if(count($m) > 1) $this->fragMatches['recordedBy'] = trim($m[1]);
						else $this->fragMatches['recordedBy'] = $m[0];
					}
				}
			}
		}
		//If no match, try digging deeper
        // PJM: 2014 Nov 28, have done a simple subtitution of agents for omcollectors here,
        //      as this function doesn't appear to be in use, the agents and agentnames
        //      tables offer more options for more sophisticated checks for existing
        //      agents.
		if(!isset($this->fragMatches['recordedBy'])){
			foreach($lineArr as $line){
				if($nameTokens = str_word_count($line,1)){
					$sql = '';
					foreach($nameTodkens as $v){
						$sql .= 'OR familyname = "'.str_replace('"','',$v).'" ';
					}
					$sql = 'SELECT recordedbyid FROM agents WHERE '.substr($sql,2);
					if($rs = $this->conn->query($sql)){
						if($r = $rs->fetch_object()){
							$this->fragMatches['recordedBy'] = trim($line);
						}
						$rs->free();
					}
				}
			}
		}
		//And again a little deeper
        // TODO: Table agentnames has freetext index on agentnames.name and can
        //       return possible matches with a score - that free text search
        //       would be suitable for use here instead of a soundex on agent.familyname
		if(!isset($this->fragMatches['recordedBy'])){
			foreach($lineArr as $line){
				if($nameTokens = str_word_count($line,1)){
					$sql = '';
					foreach($nameTodkens as $v){
						$sql .= 'OR familyname SOUNDS LIKE "'.str_replace('"','',$v).'" ';
					}
					$sql = 'SELECT recordedbyid FROM agents WHERE '.substr($sql,2);
					if($rs = $this->conn->query($sql)){
						if($r = $rs->fetch_object()){
							$this->fragMatches['recordedBy'] = trim($line);
						}
						$rs->free();
					}
				}
			}
		}

		//Look for possible occurrence matches
		if(isset($this->fragMatches['recordedby'])){
			if(array_key_exists('exsiccatiNumber',$this->fragMatches)){
				$sql = '';
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_object()){
						$this->occDupes[] = $r->occid;
					}
					$rs->free();
				}
			}
		}
	}

	//Misc functions
	private function getPoliticalUnits($countrySeed = '', $stateSeed = '', $countySeed = '', $wildStr = ''){
		$retArr = array();
		$cnt = 0;
		$bestMatch = 0;
		$sqlBase = 'SELECT cr.geoTerm AS countryname, sp.geoTerm AS statename, c.geoTerm AS countyname '.
			'FROM geographicthesaurus cr INNER JOIN geographicthesaurus s ON cr.geoThesID = s.parentID '.
			'LEFT JOIN geographicthesaurus c ON s.geoThesID = c.parentID WHERE ';
		if($countrySeed || $stateSeed || $countySeed){
			//First look for exact match
			$sqlWhere = '';
			if($countrySeed){
				$sqlWhere .= 'AND (cr.geoTerm = "'.$countrySeed.'") ';
			}
			if($stateSeed){
				$sqlWhere .= 'AND (s.geoTerm = "'.$stateSeed.'") ';
			}
			if($countySeed){
				$sqlWhere .= 'AND ((c.geoTerm = "'.$stateSeed.'") OR (c.geoTerm LIKE "'.$stateSeed.'%")) ';
			}
			$rs = $this->conn->query($sqlBase.substr($sqlWhere,4));
			while($r = $rs->fetch_object()){
				$retArr[$cnt]['country'] = $r->countryname;
				$retArr[$cnt]['state'] = $r->statename;
				$retArr[$cnt]['county'] = $r->countyname;
				$cnt++;
			}
			$rs->free();
			if(!$retArr){
				$sqlWhere = '';
				//Nothing returns so lets go deeper
				if($countrySeed){
					$sqlWhere .= 'AND (SOUNDEX(cr.geoTerm) = SOUNDEX("'.$countrySeed.'")) ';
				}
				if($stateSeed){
					$sqlWhere .= 'AND (SOUNDEX(s.geoTerm) = SOUNDEX("'.$stateSeed.'")) ';
				}
				if($countySeed){
					$sqlWhere .= 'AND (SOUNDEX(c.geoTerm) = SOUNDEX("'.$stateSeed.'")) ';
				}
				$rs = $this->conn->query($sqlBase.substr($sqlWhere,4));
				while($r = $rs->fetch_object()){
					$retArr[$cnt]['country'] = $r->countryname;
					$retArr[$cnt]['state'] = $r->statename;
					$retArr[$cnt]['county'] = $r->countyname;
					$cnt++;
				}
				$rs->free();
			}

			//Check to see if we have more than one possible matches
			if(count($retArr) > 1){
				//Locate the best match
				$rateArr = array();
				foreach($retArr as $k => $vArr){
					$rating = 0;
					if($countrySeed) $rating += levenshtein($countrySeed,$vArr['country']);
					if($stateSeed) $rating += levenshtein($stateSeed,$vArr['state']);
					if($countySeed) $rating += levenshtein($countySeed,$vArr['county']);
					$rateArr[$k] = $rating;
					asort($rateArr,SORT_NUMERIC);
					$bestMatch = key($rateArr);
				}
			}
		}
		elseif($wildStr){
			//Look for possible matches
			$sqlWhere = '';
			//Split string into words separated by commas, semi-colons, or white spaces
			$wildArr = preg_split("/[\s,;]+/", $wildStr);
			foreach($wildArr as $k => $v){
				//Clean values
				$wildArr[$k] = trim($v);
				$sqlWhere .= 'OR (SOUNDEX(cr.geoTerm) = SOUNDEX("'.$wildArr[$k].'")) '.
					'OR (SOUNDEX(s.geoTerm) = SOUNDEX("'.$wildArr[$k].'")) '.
					'OR (SOUNDEX(c.geoTerm) = SOUNDEX("'.$wildArr[$k].'")) ';
			}
			if($sqlWhere){
				$rs = $this->conn->query($sqlBase.substr($sqlWhere,3));
				while($r = $rs->fetch_object()){
					$retArr[$cnt]['country'] = $r->countryname;
					$retArr[$cnt]['state'] = $r->statename;
					$retArr[$cnt]['county'] = $r->countyname;
					$cnt++;
				}
				$rs->free();
			}
			//Now let's see if we can figure out the best match
			if(count($retArr) > 1){
				$rateArr = array();
				$unitArr = array('country', 'state', 'county');
				foreach($retArr as $mk => $mArr){
					$rating = 0;
					foreach($wildArr as $wk => $wv){
						foreach($unitArr as $uv){
							$r = levenshtein($mArr[$uv],$wv);
							if($r == 0) $rating += 3;
							if($r == 1) $rating += 1;
						}
					}
					$rateArr[$mk] = $rating;
				}
				asort($rateArr, SORT_NUMERIC);
				end($rateArr);
				$bestMatch = key($rateArr);
			}
		}
		return (isset($retArr[$bestMatch])?$retArr[$bestMatch]:null);
	}

	//Misc functions
	public static function encodeString($inStr){
		global $CHARSET;
 		$retStr = $inStr;
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

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>