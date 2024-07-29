<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceUtilities.php');
include_once($SERVER_ROOT.'/classes/SpecProcNlpSalix.php');
include_once($SERVER_ROOT.'/classes/SpecProcNlpLbcc.php');
include_once($SERVER_ROOT.'/classes/SpecProcNlpLbccLichen.php');
include_once($SERVER_ROOT.'/classes/SpecProcNlpLbccBryophyte.php');

class SpecProcNlpHandler {

	private $conn;
	private $parserTag = '';			//lbccLichen, lbccBryophyte, salix, nybg

	private $printMode = null;		//0 = database, 1 = report, 2 = csv
	private $logErrors = 0;
	private $totalStats = array();
	private $csvHeaderArr = array();

	private $outFH;
	private $logFH;
	private $outFilePath;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->outFilePath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/')."content/logs/LbccParser_".date('Y-m-d_his');
		set_time_limit(7200);
	}

	function __destruct(){
		if($this->printMode == 1){
			$this->printSummary();
			$this->outToReport('Processing finished: '.date('Y-m-d h:i:s A')."\n\n");
			echo '<div style="margin-left:10px;">Output file: <a href="' . htmlspecialchars($this->outFilePath, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '.txt">' . htmlspecialchars($this->outFilePath, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '.txt</a></div>';
			echo '<div style="margin-left:10px;">Log file: <a href="' . htmlspecialchars($this->outFilePath, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '.log">' . htmlspecialchars($this->outFilePath, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '.log</a></div>';
			if($this->outFH) fclose($this->outFH);
		}
		elseif($this->printMode == 2){
			//Create new final file and prime with header
			fclose($this->outFH);
			$outFinalFH = fopen($this->outFilePath.'.csv', 'w');
			fputcsv($outFinalFH,array_keys($this->csvHeaderArr));
			//Append data from temp file to final file
			$outTempFH = fopen($this->outFilePath.'_temp.csv', 'r');
			while (!feof($outTempFH)) {
				$contents = fread($outTempFH,8192);
				fwrite($outFinalFH,$contents);
			}
			fclose($outTempFH);
			unlink($this->outFilePath.'_temp.csv');
			fclose($outFinalFH);

			$this->logMsg($this->totalStats['collmeta']['totalcnt'].' records output to CSV');
			echo '<div style="margin-left:10px;">Output file: <a href="' . htmlspecialchars($this->outFilePath, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE)  .'.csv">' . htmlspecialchars($this->outFilePath, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '.csv</a></div>';
		}
		elseif($this->printMode === 0){
			$this->logMsg($this->totalStats['collmeta']['totalcnt'].' records processed and databased');
		}
		if($this->logFH){
			$this->logMsg('Processing finished: '.date('Y-m-d h:i:s A')."\n");
			fclose($this->logFH);
		}
		if(!($this->conn === false)) $this->conn->close();
	}

	public function parseRawOcrRecord($prlid){
		$dwcArr = array();
		if(is_numeric($prlid)){
			$recArr = $this->getRawOcr($prlid);
			if($recArr) {
				$dwcArr = $this->parse($recArr['rawocr'],$recArr['collid'],$recArr['catnum']);
			}
		}
		return $dwcArr;
	}

	public function parse($rawOcr, $collid = 0, $catNum = '' ){
		$dwcArr = array();
		if($rawOcr) {
			if($this->parserTag == 'salix'){
				$dwcArr = $this->parseSalix($rawOcr);
			}
			else{
				$dwcArr = $this->parseLbcc($rawOcr, $collid, $catNum);
			}
			//A little cleaning and unification
			$dwcArr = array_change_key_case($dwcArr);
			if(array_key_exists('scientificname',$dwcArr) && !array_key_exists('sciname',$dwcArr)){
				$dwcArr['sciname'] = $dwcArr['scientificname'];
				unset($dwcArr['scientificname']);
			}
		}
		return $dwcArr;
	}

	private function parseLbcc($rawOcr,$collid,$catNum){
		//Parse and return
		if($rawOcr) {
			$handler;
			if($this->parserTag == 'lbccBryophyte') $handler = new SpecProcNlpLbccBryophyte();
			elseif($this->parserTag == 'lbccLichen') $handler = new SpecProcNlpLbccLichen();
			else $handler = new SpecProcNlpLbcc();
			if($handler) {
				$handler->setCollId($collid);
				$handler->setCatalogNumber($catNum);
				return $handler->parse($rawOcr);
			}
		}
		return;
	}

	private function parseSalix($rawOcr){
		//Parse and return
		if($rawOcr) {
			$parser = new SpecProcNlpSalix();
			return $parser->parse($rawOcr);
		}
		return;
	}

	private function getRawOcr($prlid){
		$retArr = array();
		//Get raw OCR string
		$sql = 'SELECT r.rawstr, o.collid, o.catalogNumber '.
			'FROM omoccurrences o '.
			'INNER JOIN images i ON o.occid = i.occid '.
			'INNER JOIN specprocessorrawlabels r ON i.imgid = r.imgid '.
			'WHERE (r.prlid = '.$prlid.')';
		//echo $sql;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retArr['rawocr'] = $r->rawstr;
			$retArr['collid'] = $r->collid;
			$retArr['catnum'] = $r->catalogNumber;
		}
		$rs->free();
		return $retArr;
	}

	public function batchProcess($collTarget, $source = 'abbyy'){
		$this->setCollectionMetadata($collTarget);
		$collArr = explode(',',$collTarget);
		$totalCnt = 0;
		foreach($collArr as $collid){
			$this->setCollId($collid);
			$sql = 'SELECT r.prlid, r.rawstr, r.source, o.occid, o.collid, o.catalognumber, IFNULL(i.originalurl,i.url) AS url '.
				'FROM specprocessorrawlabels r LEFT JOIN images i ON r.imgid = i.imgid '.
				'INNER JOIN omoccurrences o ON IFNULL(i.occid,r.occid) = o.occid '.
				'WHERE length(r.rawstr) > 20 AND (o.processingstatus = "unprocessed") ';
			//if($this->collId) $sql .= 'AND (o.collid = '.$this->collId.') ';
			if($source) $sql .= 'AND r.source LIKE "%'.$source.'%" ';
			$sql .= 'LIMIT 10';
			//echo $sql;
			$cnt = 0;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$rawOcr = $r->rawstr;
				$ocrSource = $r->source;
				$url = $r->url;
				$prlid = $r->prlid;
				$occid = $r->occid;
				$collid = $r->collid;
				$catNumb = $r->catalognumber;

				//Process string and load results into $dwcArr
				//Exceptions must be caught in try/catch blocks
				$dwcArr = array();
				try{
					$dwcArr = $this->parse($rawOcr, $collid, $catNumb);
				}
				catch(Exception $e){
					$eStr = 'ERROR: '.$e->getMessage();
					//echo $eStr;
					$this->logMsg($eStr);
					if($this->printMode == 1) $this->outToReport($eStr);
				}

				if($this->printMode == 1){
					//Output to report file
					$this->printResult($rawOcr,$dwcArr);
				}
				elseif($this->printMode == 2){
					$dwcArr['occid'] = $occid;
					$dwcArr['prlid'] = $prlid;
					$dwcArr['ocrsource'] = $ocrSource;
					$dwcArr['imageurl'] = $url;
					if(!array_key_exists('catalogNumber', $dwcArr)) $dwcArr['catalogNumber'] = $catNumb;
					//Output to csv file
					$this->printCsv($dwcArr,$totalCnt);
				}
				else{
					//Output to database
					$this->loadParsedData($dwcArr);
				}
				$cnt++;
			}
			$this->totalStats['collmeta'][$collid]['cnt'] = $cnt;
			$totalCnt += $cnt;
		}
		$this->totalStats['collmeta']['totalcnt'] = $totalCnt;
	}

	private function getRawOcr($prlid){
		$retArr = array();
		//Get raw OCR string
		$sql = 'SELECT r.rawstr, o.collid, o.catalogNumber '.
			'FROM omoccurrences o '.
			'INNER JOIN images i ON o.occid = i.occid '.
			'INNER JOIN specprocessorrawlabels r ON i.imgid = r.imgid '.
			'WHERE (r.prlid = '.$prlid.')';
		//echo $sql;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retArr['rawocr'] = $r->rawstr;
			$retArr['collid'] = $r->collid;
			$retArr['catnum'] = $r->catalogNumber;
		}
		$rs->free();
		return $retArr;
	}

	public function convertDwcArrToJson($dwcArr){
		//Convert to UTF-8, json_encode call requires UTF-8
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
		return json_encode($dwcArr);
	}

	/*
	 * @param 	Array of parsed term/values.
	 * 			Key: DwC term; Value: Output text
	 * @return 	TRUE on success
	 */
	private function loadParsedData($inArr){
		if(!$inArr){
			$this->logMsg('ERROR: input empty');
			return false;
		}
		if(!is_array($inArr)){
			$this->logMsg('ERROR: input is not an array');
			return false;
		}
		$dwcArr = array_change_key_case($inArr);

		//Check to make sure occid and prlid variables are available (both required)
		if(!$this->prlid){
			$this->logMsg('ERROR: prlid is needed to load parsed data');
			return false;
		}
		if(!$this->occid){
			$this->logMsg('ERROR: occid is needed to load parsed data');
			return false;
		}

		//Grab target fields
		$targetFields = array();
		$rsMD = $this->conn->query('SHOW COLUMNS FROM omoccurrences');
		while($r = $rsMD->fetch_object()){
			$targetFields[strtolower($r->Field)] = $r->Type;
		}
		$rsMD->free();
		//Remove some internal system fields
		unset($targetFields['occid']);
		unset($targetFields['collid']);
		unset($targetFields['dbpk']);
		unset($targetFields['tidinterpreted']);
		unset($targetFields['instititioncode']);
		unset($targetFields['collectioncode']);
		unset($targetFields['recordedbyid']);
		unset($targetFields['modified']);
		unset($targetFields['observeruid']);
		unset($targetFields['processingstatus']);
		unset($targetFields['recordenteredby']);
		unset($targetFields['dateLastModified']);

		//Get existing data
		$curOccArr = array();
		if($rs = $this->conn->query('SELECT * FROM omoccurrences WHERE occid = '.$this->occid)){
			$curOccArr = array_change_key_case($rs->fetch_assoc());
		}
		else{
			$this->logMsg('ERROR: unable to populate $curOccArr');
			return false;
		}

		//Do some cleaning
		if(isset($dwcArr['month']) && !is_numeric($dwcArr['month'])){
			//Month should be numeric, yet is a sting. Check to see if it is the month name or abbreviation
			$mStr = strtolower(substr($dwcArr['month'],0,3));
			if(array_key_exists($mStr,OccurrenceUtilities::$monthNames)){
				$dwcArr['month'] = OccurrenceUtilities::$monthNames[$mStr];
			}
			else{
				if(!isset($dwcArr['verbatimeventdate']) || !$dwcArr['verbatimeventdate']){
					$vDate = '';
					if(isset($dwcArr['day'])) $vDate = $dwcArr['day'].' ';
					$vDate .= $dwcArr['month'].' ';
					if(isset($dwcArr['year'])) $vDate .= $dwcArr['year'];
					$dwcArr['verbatimeventdate'] = trim($vDate);
				}
				unset($dwcArr['month']);
			}
		}
		if(!isset($dwcArr['eventdate']) && isset($dwcArr['year']) && isset($dwcArr['month'])){
			//If not eventdate and year/month exists, build event date from year-month-day
			if(!isset($dwcArr['day'])) $dwcArr['day'] = "00";
			$dwcArr['eventdate'] = $dwcArr['year'].'-'.$dwcArr['month'].'-'.$dwcArr['day'];
		}
		if(!isset($dwcArr['eventdate']) && isset($dwcArr['verbatimeventdate'])){
			$dwcArr['eventdate'] = OccurrenceUtilities::formatDate($dwcArr['verbatimeventdate']);
		}

		//Load data
		$dataToLoad = array_intersect_key($dwcArr,$targetFields);
		$leftOverData = array_diff_key($dwcArr,$targetFields);
		$sqlFrag = '';
		$finalFields = array();
		//int, double, varchar, text, date
		foreach($dataToLoad as $fieldTerm => $value){
			$valueStr = $this->encodeString($value);
			$valueStr = $this->cleanInStr($valueStr);
			if($valueStr){
				if(!$curOccArr[$fieldTerm]){
					//A value does not already exist in existing record, thus OK to populate field
					$valueIn = '';
					if(strpos($targetFields[$fieldTerm],'int') === 0 || strpos($targetFields[$fieldTerm],'double') === 0 || strpos($targetFields[$fieldTerm],'decimal') === 0){
						//Target field is a numeric data type
						if(is_numeric($valueStr)){
							$valueIn = $valueStr;
						}
						else{
							$this->logMsg('WARNING: '.$fieldTerm.' skipped ("'.$valueStr.'" not numeric)');
						}
					}
					elseif(strpos($targetFields[$fieldTerm],'date') === 0){
						//Target field is a date data type
						$dateValue = OccurrenceUtilities::formatDate($valueStr);
						if($dateValue){
							$valueIn = '"'.$dateValue.'"';
						}
						else{
							$this->logMsg('WARNING: '.$fieldTerm.' skipped ("'.$valueStr.'" not a valid date)');
						}
					}
					else{
						//Target field is a text data type
						$valueIn = '"'.$valueStr.'"';
					}
					//Add to SQL if has value
					if($valueIn){
						$finalFields[] = $fieldTerm;
						$sqlFrag .= ','.$fieldTerm.'='.$valueIn;
					}
				}
				else{
					$this->logMsg('WARNING: '.$fieldTerm.' skipped because value already existed in DB ("'.$valueStr.'")');
				}
			}
		}

		if($sqlFrag){
			//Code that modifies the processing status
			//processingStatus = unprocessed-NLP
			$sqlFrag .= ', processingstatus = "unprocessed-NLP"';

			//Load data into existing record
			$sql = 'UPDATE omoccurrences SET '.substr($sqlFrag,1).' WHERE occid = '.$this->occid;
			echo $sql.'<br/>';
			if($this->conn->query($sql)){
				//Version field that were added along with the time stamp
				$sql = 'INSERT INTO specprocnlpversion(prlid, archivestr) '.
					'VALUES('.$this->prlid.',"'.implode(',',$finalFields).'")';
				echo $sql.'<br/>';
				if(!$this->conn->query($sql)){
					$this->logMsg('WARNING: unable to version edit: ; '.$this->conn->error);
					$this->logMsg('Error details: ; '.$this->conn->error);
				}

				//Deal with Exsiccati data
				if(isset($dwcArr['exsiccatinumber']) && $dwcArr['exsiccatinumber'] && !isset($dwcArr['exsnumber'])){
					//exsiccatinumber variable submitted instead of exsnumber
					$dwcArr['exsnumber'] = $dwcArr['exsiccatinumber'];
				}
				//Exsiccati number is required, if not there no need to progress
				if((isset($dwcArr['exsnumber']) && $dwcArr['exsnumber']) || (isset($dwcArr['omenid']) && $dwcArr['omenid'])){
					if(isset($dwcArr['exsiccatititle']) && $dwcArr['exsiccatititle'] && (!isset($dwcArr['ometid']) || !$dwcArr['ometid'])){
						//Get ometid (title number) since only exsiccatiTitle exists
						$sql = 'SELECT ometid FROM omexsiccatititles '.
							'WHERE (title = "'.trim($dwcArr['exsiccatititle']).'") OR (abbreviation = "'.trim($dwcArr['exsiccatititle']).'")';
						$rs = $this->conn->query($sql);
						if($r = $rs->fetch_object()){
							$dwcArr['ometid'] = $r->ometid;
						}
						$rs->free();
					}
					if($dwcArr['ometid']){
						if(!isset($dwcArr['omenid']) && !$dwcArr['omenid'] && isset($dwcArr['exsnumber']) && $dwcArr['exsnumber']){
							//Get exsiccati number id (omenid), since exsnumber was only supplied
							$sql = 'SELECT omenid FROM omexsiccatinumbers '.
								'WHERE ometid = ('.$dwcArr['ometid'].') AND (exsnumber = "'.trim($dwcArr['exsnumber']).'")';
							$rs = $this->conn->query($sql);
							if($r = $rs->fetch_object()){
								$dwcArr['omenid'] = $r->omenid;
							}
							$rs->free();
							if(!isset($dwcArr['omenid'])){
								//Exsiccati number needs to be added
								$sql = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber) '.
									'VALUES('.$dwcArr['ometid'].',"'.trim($dwcArr['exsnumber']).'")';
								if($this->conn->query($sql)) $dwcArr['omenid'] = $this->conn->insert_id;
							}
						}
						if($dwcArr['omenid']){
							//ometid and omenid both exists, thus load Exsiccati
							$sqlExs ='INSERT INTO omexsiccatiocclink(omenid,occid) VALUES('.$dwcArr['omenid'].','.$this->occid.')';
							if($this->conn->query($sqlExs)){
								//Remove exsiccati fields from $leftOverData
								unset($leftOverData['ometid']);
								unset($leftOverData['omenid']);
								if(isset($leftOverData['exsnumber'])) unset($leftOverData['exsnumber']);
								if(isset($leftOverData['exsiccatinumber'])) unset($leftOverData['exsiccatinumber']);
								if(isset($leftOverData['exsiccatititle'])) unset($leftOverData['exsiccatititle']);
							}
							else{
								$this->logMsg("ERROR linking exsiccati record (".$dwcArr['omenid'].'-'.$this->occid."): ".$this->conn->error);
							}
						}
					}
				}
			}
			else{
				$this->logMsg('ERROR: unable to load data; '.$this->conn->error);
				return false;
			}
		}

		if(count($leftOverData)) $this->logMsg('WARNING: Unmatched data fields: '.implode(', ',array_keys($leftOverData)));

		return true;
	}

	//Ouput functions
	private function printCsv($dwcArr){
		//Add new header names to header array
		$newHeadArr = array_diff_key($dwcArr, $this->csvHeaderArr);
		foreach($newHeadArr as $newK => $nV){
			$this->csvHeaderArr[$newK] = '';
		}
		//Output values in exact order as header array
		$outputArr = $this->csvHeaderArr;
		foreach($dwcArr as $k => $v){
			$outputArr[$k] = $v;
		}
		if($this->outFH) fputcsv($this->outFH,$outputArr);
	}

	private function printResult($rawStr,$dwcArr){
		$this->outToReport("collid: ".$this->collId.", occid: ".$this->occid);
		$this->outToReport($this->url."\n");
		//If string is UTF-8, convert to latin1
		if(mb_detect_encoding($rawStr,'UTF-8,ISO-8859-1') == "UTF-8"){
			$rawStr = utf8_decode($rawStr);
		}
		$this->outToReport("OCR string:\n".$rawStr."\n");
		$this->outToReport("Results:");
		foreach($dwcArr as $fieldName => $fieldValue) {
			$this->outToReport("\t".$fieldName.": ".$fieldValue);
			//Collect field stats for final report
			if(isset($this->totalStats[$fieldName][$this->collId])) {
				$num = ++$this->totalStats[$fieldName][$this->collId];
				$this->totalStats[$fieldName][$this->collId] = $num;
			}
			else{
				$this->totalStats[$fieldName][$this->collId] = 1;
			}
		}
	}

	private function printSummary(){
		$collArr = $this->totalStats['collmeta'];
		$this->outToReport("------------------------------------------------------------------------------------------");
		$this->outToReport("\n\nSummary of ".$collArr['totalcnt']." labels:");
		unset($this->totalStats['collmeta']);
		//Show stats for all collections
		foreach($this->totalStats as $fieldName => $fieldArr){
			$tCnt = 0;
			foreach($fieldArr as $collId => $fCnt){
				$tCnt += $fCnt;
			}
			$perc = ($collArr['totalcnt']?round(100*$tCnt/$collArr['totalcnt']):0);
			$this->outToReport("\t".$fieldName." ".$tCnt." times (".$perc."%)");
		}

		//Show stats for each collection processed
		foreach($collArr as $collId => $collArr){
			if($collId){
				$this->outToReport("\nCollection: ".$collArr['name']." (#".$collId."), total labels: ".$collArr['cnt']);
				foreach($this->totalStats as $fieldName => $fieldArr){
					if(isset($fieldArr[$collId])){
						$perc = ($collArr['cnt']?round(100*$fieldArr[$collId]/$collArr['cnt']):0);
						$this->outToReport("\t".$fieldName.": ".$fieldArr[$collId].' times ('.$perc.'%)' );
					}
				}
			}
		}
	}

	private function outToReport($str){
		if($this->outFH){
			fwrite($this->outFH,$str."\n");
		}
		else{
			echo $str."\n";
		}
	}

	private function logMsg($str){
		if($this->logErrors){
			if(!$this->logFH){
				$this->logFH = fopen($this->outFilePath.'.log', 'a');
				$this->logMsg('Starting batch processing ('.date('Y-m-d h:i:s A').')');
			}
			if($this->logFH){
				fwrite($this->logFH,$str."\n");
			}
			else{
				echo $str."\n";
			}
		}
	}

	public function setPrintMode($m){
		if(is_numeric($m)) $this->printMode = $m;
		if($this->printMode && !$this->outFH){
			//$outFilePath = '';
			if($this->printMode == 1){
				$this->outFH = fopen($this->outFilePath.'.txt', 'w');
			}
			elseif($this->printMode == 2){
				$this->outFH = fopen($this->outFilePath.'_temp.csv', 'w');
			}
			if($this->printMode == 1){
				$this->outToReport('Start time: '.date('Y-m-d h:i:s A')."\n\n");
			}
		}
	}

	//Setters and getters
	public function setParserTag($tag){
		$this->parserTag = $tag;
	}

	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>