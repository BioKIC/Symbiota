<?php
/*
 * This class is only conceptional and has not been developed much
 * A lot of work would be needed before it could be used
 */

class SpecProcNlpParser{

	private $conn;
	private $dcArr = array();
	private $tokenArr = array();
	private $fragMatches = array();

	private $indicatorTerms = array();
	private $pregMatchTerms = array();

	private $occDupes = array();

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		set_time_limit(7200);
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	//Parsing functions
	private function parse(){
		$lineArr = explode("\n",$this->rawText);
		foreach($lineArr as $str){
			if(stripos($str,'herbarium')) return 0;
			if(stripos($str,'university')) return 0;

			//Test for country, state via Plants/Lichens of ...
			if(!array_key_exists('stateprovince',$this->dcArr) && preg_match('/\w{1}\s+of\s+(.*)/',$str,$m)){
				$mStr = trim($m[1]);
				$sql = 'SELECT c.geoterm AS countryName, s.geoterm AS stateName
					FROM geographicthesaurus c INNER JOIN geographicthesaurus s ON c.geoThesID = s.parentID
					WHERE s.geoterm SOUNDS LIKE "'.$mStr.'"';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$this->dcArr['stateprovince'] = $r->stateName;
					if(!array_key_exists('country',$this->dcArr)) $this->dcArr['country'] = $r->countryName;
				}
				$rs->free();
			}

			if(!array_key_exists('county',$this->dcArr) && preg_match('/(\w+)\sCounty|Co\./',$str,$m)){
				//county match
				$words = explode(' ',trim($m[1]));
				$sTerm = array();
				$cnt = 0;
				while($w = array_pop($words)){
					if($cnt < 4) break;
					if($cnt == 0){
						$sTerm[0] = $w;
					}
					else{
						$sTerm[$cnt] = $w.' '.$sTerm[$cnt-1];
					}
					$cnt++;
				}
				$sqlWhere = '';
				foreach($sTerm as $v){
					$sqlWhere .= ' OR c.geoTerm SOUNDS LIKE "'.$v.'"';
				}
				$sql = 'SELECT c.geoterm AS countryName FROM geographicthesaurus c ';
				if(array_key_exists('stateprovince',$this->dcArr)) $sql .= 'INNER JOIN geographicthesaurus s ON c.geoThesID = s.parentID ';
				$sql .= 'WHERE c.geolevel = 50 AND ('.substr($sqlWhere,4).') ';
				if(array_key_exists('stateprovince',$this->dcArr)) $sql .= 'c.geolevel = 50 AND s.geoTerm = "'.$this->dcArr['stateprovince'].'"';
				$rs = $this->conn->query();
				$coStr = '';
				while($r = $rs->fetch_object()){
					if(strlen($r->countyName) > $coStr) $coStr = $r->countyName;
				}
				$rs->free();
				if($coStr) $this->dcArr['county'] = $coStr;
			}
			//Test for country


			//Test for collector, number, date

		}
	}

	public function parseCollectorField($collName){
		$lastName = "";
		$lastNameArr = explode(',',$collName);
		$lastNameArr = explode(';',$lastNameArr[0]);
		$lastNameArr = explode('&',$lastNameArr[0]);
		$lastNameArr = explode(' and ',$lastNameArr[0]);
		if(preg_match_all('/[A-Za-z]{3,}/',$lastNameArr[0],$match)){
			if(count($match[0]) == 1){
				$lastName = $match[0][0];
			}
			elseif(count($match[0]) > 1){
				$lastName = $match[0][1];
			}
		}


	}

	private function parseExsiccati(){
		$lineArr = explode("\n",$this->rawText);
		//Locate matching lines
		foreach($lineArr as $line){
			//Test for exsiccati title
			if(isset($indicatorTerms['exsiccatiTitle'])){
				foreach($indicatorTerms['exsiccatiTitle'] as $term){
					if(stripos($line,$term)) $this->fragMatches['exsiccatiTitle'] = trim($line);
				}
			}
			if(isset($pregMatchTerms['exsiccatiTitle'])){
				foreach($pregMatchTerms['exsiccatiTitle'] as $pattern){
					if(preg_match($pattern,$line,$m)){
						if(count($m) > 1) $this->fragMatches['exsiccatiTitle'] = trim($m[1]);
						else $this->fragMatches['exsiccatiTitle'] = $m[0];
					}
				}
			}
			//Test for exsiccati number
			if(isset($indicatorTerms['exsiccatiNumber'])){
				foreach($indicatorTerms['exsiccatiNumber'] as $term){
					if(stripos($line,$term)) $this->fragMatches['exsiccatiNumber'] = trim($line);
				}
			}
			if(isset($pregMatchTerms['exsiccatiNumber'])){
				foreach($pregMatchTerms['exsiccatiNumber'] as $pattern){
					if(preg_match($pattern,$line,$m)){
						if(count($m) > 1) $this->fragMatches['exsiccatiNumber'] = trim($m[1]);
						else $this->fragMatches['exsiccatiNumber'] = $m[0];
					}
				}
			}
		}

		//Look for possible occurrence matches
		if(isset($this->fragMatches['exsiccatiTitle'])){
			if(isset($this->fragMatches['exsiccatiNumber'])){
				$exactHits = false;
				$sql = 'SELECT ol.occid '.
					'FROM omexsiccatititles t INNER JOIN omexsiccatinumbers n ON t.ometid = n.ometid '.
					'INNER JOIN omexsiccatiocclink ol ON n.omenid = ol.omenid '.
					'WHERE ((t.title = "'.$this->fragMatches['exsiccatiTitle'].'") OR (t.abbreviation = "'.$this->fragMatches['exsiccatiTitle'].'")) '.
					'AND (n.excnumber = "'.$this->fragMatches['exsiccatiNumber'].'")';
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_object()){
						$this->occDupes[] = $r->occid;
						$exactHits = true;
					}
					$rs->free();
				}
				if(!$exactHits){
					//No exact hits, thus lets try to dig deeper
					$titleTokens = explode(' ',str_replace(array(',',';'),' ',$this->fragMatches['exsiccatiTitle']));
					$sql = 'SELECT ol.occid '.
						'FROM omexsiccatititles t INNER JOIN omexsiccatinumbers n ON t.ometid = n.ometid '.
						'INNER JOIN omexsiccatiocclink ol ON n.omenid = ol.omenid '.
						'WHERE (n.excnumber = "'.$this->fragMatches['exsiccatiNumber'].'") '.
						'AND ((t.title SOUNDS LIKE "'.$this->fragMatches['exsiccatiTitle'].'") '.
						'OR (t.abbreviation SOUNDS LIKE "'.$this->fragMatches['exsiccatiTitle'].'"))';
					if($rs = $this->conn->query($sql)){
						while($r = $rs->fetch_object()){
							$this->occDupes[] = $r->occid;
						}
						$rs->free();
					}
				}
			}
		}
	}

	//Batch processes
	public function batchNLP(){


	}

	private function tokenizeRawString(){
		$lineArr = explode("\n",$this->rawText);
		foreach($lineArr as $l){
			$this->tokenArr = array_merge($tokens,preg_split('/[\s,;]+/',$l));
		}
	}

}
?>