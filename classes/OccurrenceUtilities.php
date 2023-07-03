<?php
include_once($SERVER_ROOT.'/classes/GPoint.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');

class OccurrenceUtilities {

	static $monthRoman = array('I'=>'01','II'=>'02','III'=>'03','IV'=>'04','V'=>'05','VI'=>'06','VII'=>'07','VIII'=>'08','IX'=>'09','X'=>'10','XI'=>'11','XII'=>'12');
	static $monthNames = array('jan'=>'01','ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','apr'=>'04','may'=>'05','jun'=>'06','jul'=>'07','ago'=>'08',
		'aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12','dic'=>'12');

 	public function __construct(){
 	}

 	public function __destruct(){
 	}

	/*
	 * INPUT: String representing a verbatim date
	 * OUTPUT: String representing the date in MySQL format (YYYY-MM-DD)
	 *         Time is appended to end if present
	 *
	 */
	public static function formatDate($inStr){
		$retDate = '';
		$dateStr = trim($inStr,'.,; ');
		if(!$dateStr) return;
		$t = '';
		$y = '';
		$m = '00';
		$d = '00';
		//Remove time portion if it exists
		if(preg_match('/\d{2}:\d{2}:\d{2}/',$dateStr,$match)){
			$t = $match[0];
		}
		//Parse
		if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/',$dateStr,$match)){
			//Format: yyyy-m-d or yyyy-mm-dd
			$y = $match[1];
			$m = $match[2];
			$d = $match[3];
		}
		elseif(preg_match('/^(\d{4})-(\d{1,2})/',$dateStr,$match)){
			//Format: yyyy-m or yyyy-mm
			$y = $match[1];
			$m = $match[2];
		}
		elseif(preg_match('/^([\d-]{1,5})\.{1}([IVX]{1,4})\.{1}(\d{2,4})/i',$dateStr,$match)){
			//Roman numerial format: dd.IV.yyyy, dd.IV.yy, dd-IV-yyyy, dd-IV-yy
			$d = $match[1];
			if(!is_numeric($d)) $d = '00';
			$mStr = strtoupper($match[2]);
			$y = $match[3];
			if(array_key_exists($mStr,self::$monthRoman)){
				$m = self::$monthRoman[$mStr];
			}
		}
		elseif(preg_match('/^(\d{1,2})[\s\/-]{1}(\D{3,})\.*[\s\/-]{1}(\d{2,4})/',$dateStr,$match)){
			//Format: dd mmm yyyy, d mmm yy, dd-mmm-yyyy, dd-mmm-yy
			$d = $match[1];
			$mStr = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			if(array_key_exists($mStr,self::$monthNames)){
				$m = self::$monthNames[$mStr];
			}
		}
		elseif(preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})/',$dateStr,$match)){
			//Format: mm/dd/yyyy, m/d/yy
			$m = $match[1];
			$d = $match[2];
			$y = $match[3];
		}
		elseif(preg_match('/^(\D{3,})\.*\s{0,2}(\d{1,2})[,\s]+([1,2]{1}[0,5-9]{1}\d{2})$/',$dateStr,$match)){
			//Format: mmm dd, yyyy
			$mStr = $match[1];
			$d = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			if(array_key_exists($mStr,self::$monthNames)) $m = self::$monthNames[$mStr];
		}
		elseif(preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})/',$dateStr,$match)){
			//Format: mm-dd-yyyy, mm-dd-yy
			$m = $match[1];
			$d = $match[2];
			$y = $match[3];
		}
		elseif(preg_match('/^(\D{3,})\.*\s+([1,2]{1}[0,5-9]{1}\d{2})/',$dateStr,$match)){
			//Format: mmm yyyy
			$mStr = strtolower(substr($match[1],0,3));
			if(array_key_exists($mStr,self::$monthNames)) $m = self::$monthNames[$mStr];
			else $m = '00';
			$y = $match[2];
		}
		else{
			if(preg_match('/(1[5-9]{1}\d{2}|20\d{2})/',$dateStr,$match)) $y = $match[1];
			if(preg_match_all('/([a-z]+)/i',$dateStr,$match)){
				foreach($match[1] as $test){
					$subStr = strtolower(substr($test,0,3));
					if(array_key_exists($subStr, self::$monthNames)){
						$m = self::$monthNames[$subStr];
						break;
					}
				}
			}
			if(!(int)$m){
				if(preg_match('/([IVX]{1,4})/',$dateStr,$match)){
					$mStr = $match[1];
					if(array_key_exists($mStr,self::$monthRoman)) $m = self::$monthRoman[$mStr];
				}
			}
			if(!(int)$m){
				if(preg_match_all('/(\d+)/',$dateStr,$match)){
					foreach($match[1] as $test){
						if($test < 13){
							$m = $test;
							break;
						}
					}
				}
			}
		}
		//Clean, configure, return
		if(!is_numeric($y)) $y = 0;
		if(!is_numeric($m)) $m = '00';
		if(!is_numeric($d)) $d = '00';
		if($y){
			if(strlen($m) == 1) $m = '0'.$m;
			if(strlen($d) == 1) $d = '0'.$d;
			//Check to see if month is valid
			if($m > 12){
				$m = '00';
				$d = '00';
			}
			//check to see if day is valid for month
			if($m == 2 && $d == 29){
				//Test leap date
				if(!checkdate($m,$d,$y)) $d = '00';
			}
			elseif($d > 31 || $m == 2 && $d > 29 || (in_array($m, array(4,6,9,11)) && $d > 30)){
				$d = '00';
			}
			//Do some cleaning
			if(strlen($y) == 2){
				if($y <= date('y')) $y = '20'.$y;
				else $y = '19'.$y;
			}
			//Build
			$retDate = $y.'-'.$m.'-'.$d;
		}
		elseif(($timestamp = strtotime($retDate)) !== false){
			$retDate = date('Y-m-d', $timestamp);
		}
		if($t){
			$retDate .= ' '.$t;
		}
		return $retDate;
	}

	/*
	 * INPUT: String representing a verbatim scientific name
	 *        Name may have imbedded authors, cf, aff, hybrid
	 * OUTPUT: Array containing parsed values
	 *         Keys: unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, author, identificationqualifier
	 */
	public static function parseScientificName($inStr, $conn = null, $rankId = 0){
		$taxonArr = TaxonomyUtilities::parseScientificName($inStr, $conn, $rankId);
		if(array_key_exists('unitind1',$taxonArr)){
			$taxonArr['unitname1'] = $taxonArr['unitind1'].' '.$taxonArr['unitname1'];
			unset($taxonArr['unitind1']);
		}
		if(array_key_exists('unitind2',$taxonArr)){
			$taxonArr['unitname2'] = $taxonArr['unitind2'].' '.$taxonArr['unitname2'];
			unset($taxonArr['unitind2']);
		}
		return $taxonArr;
	}

	/*
	 * INPUT: String representing verbatim elevation
	 *        Verbatim string represent feet or meters
	 * OUTPUT: Array containing minimum and maximun elevation in meters
	 *         Keys: minelev, maxelev
	 */
	public static function parseVerbatimElevation($inStr){
		$retArr = array();
		//Start parsing
		if(preg_match('/([\.\d]+)\s*-\s*([\.\d]+)\s*meter/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
			$retArr['maxelev'] = $m[2];
		}
		elseif(preg_match('/([\.\d]+)\s*-\s*([\.\d]+)\s*m./i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
			$retArr['maxelev'] = $m[2];
		}
		elseif(preg_match('/([\.\d]+)\s*-\s*([\.\d]+)\s*m$/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
			$retArr['maxelev'] = $m[2];
		}
		elseif(preg_match('/([\.\d]+)\s*meter/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
		}
		elseif(preg_match('/([\.\d]+)\s*m./i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
		}
		elseif(preg_match('/([\.\d]+)\s*m$/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
		}
		elseif(preg_match('/([\.\d]+)[fet\']{,4}\s*-\s*([\.\d]+)\s{,1}[f\']{1}/i',$inStr,$m)){
			if(is_numeric($m[1])) $retArr['minelev'] = (round($m[1]*.3048));
			if(is_numeric($m[2])) $retArr['maxelev'] = (round($m[2]*.3048));
		}
		elseif(preg_match('/([\.\d]+)\s*[f\']{1}/i',$inStr,$m)){
			if(is_numeric($m[1])) $retArr['minelev'] = (round($m[1]*.3048));
		}
		//Clean
		if($retArr){
			if(array_key_exists('minelev',$retArr) && ($retArr['minelev'] > 8000 || $retArr['minelev'] < 0)) unset($retArr['minelev']);
			if(array_key_exists('maxelev',$retArr) && ($retArr['maxelev'] > 8000 || $retArr['maxelev'] < 0)) unset($retArr['maxelev']);
		}
		return $retArr;
	}

	/*
	 * INPUT: String representing verbatim coordinates
	 *        Verbatim string can be UTM, DMS
	 * OUTPUT: Array containing decimal values of latitude and longitude
	 *         Keys: lat, lng
	 */
	public static function parseVerbatimCoordinates($inStr,$target=''){
		$retArr = array();
		if(strpos($inStr,' to ')) return $retArr;
		if(strpos($inStr,' betw ')) return $retArr;

		//Try to parse lat/lng
		$latDeg = 'null';$latMin = 0;$latSec = 0;$latNS = 'N';
		$lngDeg = 'null';$lngMin = 0;$lngSec = 0;$lngEW = 'W';
		//Grab lat deg and min
		if(!$target || $target == 'LL'){
			if(preg_match('/([\sNSns]{0,1})(-?\d{1,2}\.{1}\d+)\D{0,1}\s{0,1}([NSns]{0,1})\D{0,1}([\sEWew]{1})(-?\d{1,4}\.{1}\d+)\D{0,1}\s{0,1}([EWew]{0,1})\D*/',$inStr,$m)){
				//Decimal degree format
				$retArr['lat'] = $m[2];
				$retArr['lng'] = $m[5];
				$latDir = $m[3];
				if(!$latDir && $m[1]) $latDir = trim($m[1]);
				if($retArr['lat'] > 0 && $latDir && ($latDir == 'S' || $latDir == 's')) $retArr['lat'] = -1*$retArr['lat'];
				$lngDir = $m[6];
				if(!$lngDir && $m[4]) $lngDir = trim($m[4]);
				if($retArr['lng'] > 0 && $latDir && ($lngDir == 'W' || $lngDir == 'w')) $retArr['lng'] = -1*$retArr['lng'];
			}
			elseif(preg_match('/(\d{1,2})[^\d]{1,3}\s{0,2}(\d{1,2}\.{0,1}\d*)[\']{1}(.*)/i',$inStr,$m)){
				//DMS format
				$latDeg = $m[1];
				$latMin = $m[2];
				$leftOver = str_replace("''",'"',trim($m[3]));
				//Grab lat NS and lng EW
				if(stripos($inStr,'N') === false && strpos($inStr,'S') !== false){
					$latNS = 'S';
				}
				if(stripos($inStr,'W') === false && stripos($inStr,'E') !== false){
					$lngEW = 'E';
				}
				//Grab lat sec
				if(preg_match('/^(\d{1,2}\.{0,1}\d*)["]{1}(.*)/i',$leftOver,$m)){
					$latSec = $m[1];
					if(count($m)>2){
						$leftOver = trim($m[2]);
					}
				}
				//Grab lng deg and min
				if(preg_match('/(\d{1,3})\D{1,3}\s{0,2}(\d{1,2}\.{0,1}\d*)[\']{1}(.*)/i',$leftOver,$m)){
					$lngDeg = $m[1];
					$lngMin = $m[2];
					$leftOver = trim($m[3]);
					//Grab lng sec
					if(preg_match('/^(\d{1,2}\.{0,1}\d*)["]{1}(.*)/i',$leftOver,$m)){
						$lngSec = $m[1];
						if(count($m)>2){
							$leftOver = trim($m[2]);
						}
					}
					if(is_numeric($latDeg) && is_numeric($latMin) && is_numeric($lngDeg) && is_numeric($lngMin)){
						if($latDeg < 90 && $latMin < 60 && $lngDeg < 180 && $lngMin < 60){
							$latDec = $latDeg + ($latMin/60) + ($latSec/3600);
							$lngDec = $lngDeg + ($lngMin/60) + ($lngSec/3600);
							if($latNS == 'S'){
								$latDec = -$latDec;
							}
							if($lngEW == 'W'){
								$lngDec = -$lngDec;
							}
							$retArr['lat'] = round($latDec,6);
							$retArr['lng'] = round($lngDec,6);
						}
					}
				}
			}
		}
		if((!$target && !$retArr) || $target == 'UTM'){
			//UTM parsing
			$d = '';
			if(preg_match('/NAD\s*27/i',$inStr)) $d = 'NAD27';
			if(preg_match('/\D*(\d{1,2}\D{0,1})\s+(\d{6,7})m{0,1}E\s+(\d{7})m{0,1}N/i',$inStr,$m)){
				$z = $m[1];
				$e = $m[2];
				$n = $m[3];
				if($n && $e && $z){
					$llArr = self::convertUtmToLL($e,$n,$z,$d);
					if(isset($llArr['lat'])) $retArr['lat'] = $llArr['lat'];
					if(isset($llArr['lng'])) $retArr['lng'] = $llArr['lng'];
				}

			}
			elseif(preg_match('/UTM/',$inStr) || preg_match('/\d{1,2}[\D\s]+\d{6,7}[\D\s]+\d{6,7}/',$inStr)){
				//UTM
				$z = ''; $e = ''; $n = '';
				if(preg_match('/^(\d{1,2}\D{0,1})[\s\D]+/',$inStr,$m)) $z = $m[1];
				if(!$z && preg_match('/[\s\D]+(\d{1,2}\D{0,1})$/',$inStr,$m)) $z = $m[1];
				if(!$z && preg_match('/[\s\D]+(\d{1,2}\D{0,1})[\s\D]+/',$inStr,$m)) $z = $m[1];
				if($z){
					if(preg_match('/(\d{6,7})m{0,1}E{1}[\D\s]+(\d{7})m{0,1}N{1}/i',$inStr,$m)){
						$e = $m[1];
						$n = $m[2];
					}
					elseif(preg_match('/m{0,1}E{1}(\d{6,7})[\D\s]+m{0,1}N{1}(\d{7})/i',$inStr,$m)){
						$e = $m[1];
						$n = $m[2];
					}
					elseif(preg_match('/(\d{7})m{0,1}N{1}[\D\s]+(\d{6,7})m{0,1}E{1}/i',$inStr,$m)){
						$e = $m[2];
						$n = $m[1];
					}
					elseif(preg_match('/m{0,1}N{1}(\d{7})[\D\s]+m{0,1}E{1}(\d{6,7})/i',$inStr,$m)){
						$e = $m[2];
						$n = $m[1];
					}
					elseif(preg_match('/(\d{6})[\D\s]+(\d{7})/',$inStr,$m)){
						$e = $m[1];
						$n = $m[2];
					}
					elseif(preg_match('/(\d{7})[\D\s]+(\d{6})/',$inStr,$m)){
						$e = $m[2];
						$n = $m[1];
					}
					if($e && $n){
						$llArr = self::convertUtmToLL($e,$n,$z,$d);
						if(isset($llArr['lat'])) $retArr['lat'] = $llArr['lat'];
						if(isset($llArr['lng'])) $retArr['lng'] = $llArr['lng'];
					}
				}
			}
		}
		//Clean
		if($retArr){
			if($retArr['lat'] < -90 || $retArr['lat'] > 90) return;
			if($retArr['lng'] < -180 || $retArr['lng'] > 180) return;
		}
		return $retArr;
	}

	public static function convertUtmToLL($e, $n, $z, $d){
		$retArr = array();
		if($e && $n && $z){
			$gPoint = new GPoint($d);
			$gPoint->setUTM($e,$n,$z);
			$gPoint->convertTMtoLL();
			$lat = $gPoint->Lat();
			$lng = $gPoint->Long();
			if($lat && $lng){
				$retArr['lat'] = round($lat,6);
				$retArr['lng'] = round($lng,6);
			}
		}
		return $retArr;
	}

	public static function occurrenceArrayCleaning($recMap){
		//Trim all field values
		foreach($recMap as $k => $v){
			$recMap[$k] = trim($v);
		}
		//Date cleaning
		if(isset($recMap['eventdate']) && $recMap['eventdate']){
			if(!preg_match('/\d{4}-\d{2}-\d{2}/', $recMap['eventdate'])){
				if(is_numeric($recMap['eventdate'])){
					$recMap['eventdate'] = self::dateCheck($recMap['eventdate']);
				}
				else{
					//Make sure event date is a valid format or drop into verbatimEventDate
					$dateStr = self::formatDate($recMap['eventdate']);
					if($dateStr){
						if($recMap['eventdate'] != $dateStr && (!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate'])){
							$recMap['verbatimeventdate'] = $recMap['eventdate'];
						}
						$recMap['eventdate'] = $dateStr;
					}
					else{
						if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
							$recMap['verbatimeventdate'] = $recMap['eventdate'];
						}
						unset($recMap['eventdate']);
					}
				}
			}
		}
		if(array_key_exists('eventdate2',$recMap) && $recMap['eventdate2'] && is_numeric($recMap['eventdate2'])){
			$recMap['eventdate2'] = self::dateCheck($recMap['eventdate2']);
			if($recMap['eventdate2'] == $recMap['eventdate']) unset($recMap['eventdate2']);
			else $recMap['verbatimeventdate'] .= ' - '.$recMap['eventdate2'];
		}
		if(array_key_exists('verbatimeventdate',$recMap) && $recMap['verbatimeventdate'] && is_numeric($recMap['verbatimeventdate'])
				&& $recMap['verbatimeventdate'] > 2100 && $recMap['verbatimeventdate'] < 45000){
					//Date field was converted to Excel's numeric format (number of days since 01/01/1900)
					$recMap['verbatimeventdate'] = date('Y-m-d', mktime(0,0,0,1,$recMap['verbatimeventdate']-1,1900));
		}
		if(array_key_exists('dateidentified',$recMap) && $recMap['dateidentified'] && is_numeric($recMap['dateidentified'])
				&& $recMap['dateidentified'] > 2100 && $recMap['dateidentified'] < 45000){
					//Date field was converted to Excel's numeric format (number of days since 01/01/1900)
					$recMap['dateidentified'] = date('Y-m-d', mktime(0,0,0,1,$recMap['dateidentified']-1,1900));
		}
		//If month, day, or year are text, avoid SQL error by converting to numeric value
		if(array_key_exists('year',$recMap) || array_key_exists('month',$recMap) || array_key_exists('day',$recMap)){
			$y = (array_key_exists('year',$recMap)?$recMap['year']:'');
			$m = (array_key_exists('month',$recMap)?$recMap['month']:'');
			$d = (array_key_exists('day',$recMap)?$recMap['day']:'');
			$vDate = trim($y.'-'.$m.'-'.$d,'- ');
			if(isset($recMap['day']) && $recMap['day'] && !is_numeric($recMap['day'])){
				unset($recMap['day']);
				$d = '00';
			}
			if(isset($recMap['year']) && !is_numeric($recMap['year'])){
				unset($recMap['year']);
			}
			if(isset($recMap['month']) && $recMap['month'] && !is_numeric($recMap['month'])){
				if(!is_numeric($recMap['month'])){
					$monAbbr = strtolower(substr($recMap['month'],0,3));
					if(preg_match('/^[IVX]{1-4}$/',$recMap['month'])){
						$vDate = $d.'-'.$recMap['month'].'-'.$y;
						$recMap['month'] = self::$monthRoman[$recMap['month']];
						$recMap['eventdate'] = self::formatDate($y.'-'.$recMap['month'].'-'.($d?$d:'00'));
					}
					elseif(preg_match('/^\D{3,}$/',$recMap['month']) && array_key_exists($monAbbr,self::$monthNames)){
						$vDate = $d.' '.$recMap['month'].' '.$y;
						$recMap['month'] = self::$monthNames[$monAbbr];
						$recMap['eventdate'] = self::formatDate($y.'-'.$recMap['month'].'-'.($d?$d:'00'));
					}
					elseif(preg_match('/^(\d{1,2})\s{0,1}-\s{0,1}(\D{3,10})$/',$recMap['month'],$m)){
						$recMap['month'] = $m[1];
						$recMap['eventdate'] = self::formatDate(trim($y.'-'.$recMap['month'].'-'.($d?$d:'00'),'- '));
						$vDate = $d.' '.$m[2].' '.$y;
					}
					else{
						unset($recMap['month']);
					}
				}
			}
			if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
				$recMap['verbatimeventdate'] = $vDate;
			}
			if($vDate && (!array_key_exists('eventdate',$recMap) || !$recMap['eventdate'])){
				$recMap['eventdate'] = self::formatDate($vDate);
			}
		}
		//eventDate IS NULL && year IS NULL && verbatimEventDate NOT NULL
		if((!array_key_exists('eventdate',$recMap) || !$recMap['eventdate']) && array_key_exists('verbatimeventdate',$recMap) && $recMap['verbatimeventdate'] && (!array_key_exists('year',$recMap) || !$recMap['year'])){
			$dateStr = self::formatDate($recMap['verbatimeventdate']);
			if($dateStr) $recMap['eventdate'] = $dateStr;
		}
		if((isset($recMap['recordnumberprefix']) && $recMap['recordnumberprefix']) || (isset($recMap['recordnumbersuffix']) && $recMap['recordnumbersuffix'])){
			$recNumber = $recMap['recordnumber'];
			if(isset($recMap['recordnumberprefix']) && $recMap['recordnumberprefix']) $recNumber = $recMap['recordnumberprefix'].'-'.$recNumber;
			if(isset($recMap['recordnumbersuffix']) && $recMap['recordnumbersuffix']){
				if(is_numeric($recMap['recordnumbersuffix']) && $recMap['recordnumber']) $recNumber .= '-';
				$recNumber .= $recMap['recordnumbersuffix'];
			}
			$recMap['recordnumber'] = $recNumber;
		}
		//If lat or long are not numeric, try to make them so
		if(array_key_exists('decimallatitude',$recMap) || array_key_exists('decimallongitude',$recMap)){
			$latValue = (array_key_exists('decimallatitude',$recMap)?$recMap['decimallatitude']:'');
			$lngValue = (array_key_exists('decimallongitude',$recMap)?$recMap['decimallongitude']:'');
			if(($latValue && !is_numeric($latValue)) || ($lngValue && !is_numeric($lngValue))){
				$llArr = self::parseVerbatimCoordinates(trim($latValue.' '.$lngValue),'LL');
				if(array_key_exists('lat',$llArr) && array_key_exists('lng',$llArr)){
					$recMap['decimallatitude'] = $llArr['lat'];
					$recMap['decimallongitude'] = $llArr['lng'];
				}
				else{
					unset($recMap['decimallatitude']);
					unset($recMap['decimallongitude']);
				}
				$vcStr = '';
				if(array_key_exists('verbatimcoordinates',$recMap) && $recMap['verbatimcoordinates']){
					$vcStr .= $recMap['verbatimcoordinates'].'; ';
				}
				$vcStr .= $latValue.' '.$lngValue;
				if(trim($vcStr)) $recMap['verbatimcoordinates'] = trim($vcStr);
			}
		}
		//Transfer verbatim Lat/Long to verbatim coords
		if(isset($recMap['verbatimlatitude']) || isset($recMap['verbatimlongitude'])){
			if(isset($recMap['verbatimlatitude']) && isset($recMap['verbatimlongitude'])){
				if(!isset($recMap['decimallatitude']) || !isset($recMap['decimallongitude'])){
					if((is_numeric($recMap['verbatimlatitude']) && is_numeric($recMap['verbatimlongitude']))){
						if($recMap['verbatimlatitude'] > -90 && $recMap['verbatimlatitude'] < 90
								&& $recMap['verbatimlongitude'] > -180 && $recMap['verbatimlongitude'] < 180){
									$recMap['decimallatitude'] = $recMap['verbatimlatitude'];
									$recMap['decimallongitude'] = $recMap['verbatimlongitude'];
						}
					}
					else{
						//Attempt to extract decimal lat/long
						$coordArr = self::parseVerbatimCoordinates($recMap['verbatimlatitude'].' '.$recMap['verbatimlongitude'],'LL');
						if($coordArr){
							if(array_key_exists('lat',$coordArr)) $recMap['decimallatitude'] = $coordArr['lat'];
							if(array_key_exists('lng',$coordArr)) $recMap['decimallongitude'] = $coordArr['lng'];
						}
					}
				}
			}
			//Place into verbatim coord field
			$vCoord = '';
			if(isset($recMap['verbatimcoordinates']) && $recMap['verbatimcoordinates']) $vCoord = $recMap['verbatimcoordinates'].'; ';
			if(isset($recMap['verbatimlatitude']) && stripos($vCoord,$recMap['verbatimlatitude']) === false) $vCoord .= $recMap['verbatimlatitude'].', ';
			if(isset($recMap['verbatimlongitude']) && stripos($vCoord,$recMap['verbatimlongitude']) === false) $vCoord .= $recMap['verbatimlongitude'];
			if($vCoord) $recMap['verbatimcoordinates'] = trim($vCoord,' ,;');
		}
		//Transfer DMS to verbatim coords
		if(isset($recMap['latdeg']) && $recMap['latdeg'] && isset($recMap['lngdeg']) && $recMap['lngdeg']){
			//Attempt to create decimal lat/long
			if(is_numeric($recMap['latdeg']) && is_numeric($recMap['lngdeg']) && (!isset($recMap['decimallatitude']) || !$recMap['decimallatitude'] || !isset($recMap['decimallongitude']) || $recMap['decimallongitude'])){
				$latDec = abs($recMap['latdeg']);
				if(isset($recMap['latmin']) && $recMap['latmin'] && is_numeric($recMap['latmin'])) $latDec += $recMap['latmin']/60;
				if(isset($recMap['latsec']) && $recMap['latsec'] && is_numeric($recMap['latsec'])) $latDec += $recMap['latsec']/3600;
				if($latDec > 0){
					if(isset($recMap['latns']) && stripos($recMap['latns'],'s') === 0) $latDec *= -1;
					elseif($recMap['latdeg'] < 0) $latDec *= -1;
				}
				$lngDec = abs($recMap['lngdeg']);
				if(isset($recMap['lngmin']) && $recMap['lngmin'] && is_numeric($recMap['lngmin'])) $lngDec += $recMap['lngmin']/60;
				if(isset($recMap['lngsec']) && $recMap['lngsec'] && is_numeric($recMap['lngsec'])) $lngDec += $recMap['lngsec']/3600;
				if($lngDec > 0){
					if(isset($recMap['lngew']) && stripos($recMap['lngew'],'w') === 0) $lngDec *= -1;
					elseif($recMap['lngdeg'] < 0) $lngDec *= -1;
					elseif(in_array(strtolower($recMap['country']), array('usa','united states','canada','mexico','panama'))) $lngDec *= -1;
				}
				$recMap['decimallatitude'] = round($latDec,6);
				$recMap['decimallongitude'] = round($lngDec,6);
			}
			//Place into verbatim coord field
			$vCoord = (isset($recMap['verbatimcoordinates'])?$recMap['verbatimcoordinates']:'');
			if($vCoord) $vCoord .= '; ';
			$vCoord .= $recMap['latdeg'].chr(176).' ';
			if(isset($recMap['latmin']) && $recMap['latmin']) $vCoord .= $recMap['latmin'].'m ';
			if(isset($recMap['latsec']) && $recMap['latsec']) $vCoord .= $recMap['latsec'].'s ';
			if(isset($recMap['latns'])) $vCoord .= $recMap['latns'].'; ';
			$vCoord .= $recMap['lngdeg'].chr(176).' ';
			if(isset($recMap['lngmin']) && $recMap['lngmin']) $vCoord .= $recMap['lngmin'].'m ';
			if(isset($recMap['lngsec']) && $recMap['lngsec']) $vCoord .= $recMap['lngsec'].'s ';
			if(isset($recMap['lngew'])) $vCoord .= $recMap['lngew'];
			$recMap['verbatimcoordinates'] = $vCoord;
		}
		/*
		 if(array_key_exists('verbatimcoordinates',$recMap) && $recMap['verbatimcoordinates'] && (!isset($recMap['decimallatitude']) || !isset($recMap['decimallongitude']))){
		 $coordArr = self::parseVerbatimCoordinates($recMap['verbatimcoordinates']);
		 if($coordArr){
		 if(array_key_exists('lat',$coordArr)) $recMap['decimallatitude'] = $coordArr['lat'];
		 if(array_key_exists('lng',$coordArr)) $recMap['decimallongitude'] = $coordArr['lng'];
		 }
		 }
		 */
		//Convert UTM to Lat/Long
		if((array_key_exists('utmnorthing',$recMap) && $recMap['utmnorthing']) || (array_key_exists('utmeasting',$recMap) && $recMap['utmeasting'])){
			$no = (array_key_exists('utmnorthing',$recMap)?$recMap['utmnorthing']:'');
			$ea = (array_key_exists('utmeasting',$recMap)?$recMap['utmeasting']:'');
			$zo = (array_key_exists('utmzoning',$recMap)?$recMap['utmzoning']:'');
			$da = (array_key_exists('geodeticdatum',$recMap)?$recMap['geodeticdatum']:'');
			if(!isset($recMap['decimallatitude']) || !isset($recMap['decimallongitude'])){
				if($no && $ea && $zo){
					//Northing, easting, and zoning all had values
					$llArr = self::convertUtmToLL($ea,$no,$zo,$da);
					if(isset($llArr['lat'])) $recMap['decimallatitude'] = $llArr['lat'];
					if(isset($llArr['lng'])) $recMap['decimallongitude'] = $llArr['lng'];
				}
				else{
					//UTM was a single field which was placed in UTM northing field within uploadspectemp table
					$coordArr = self::parseVerbatimCoordinates(trim($zo.' '.$ea.' '.$no),'UTM');
					if($coordArr){
						if(array_key_exists('lat',$coordArr)) $recMap['decimallatitude'] = $coordArr['lat'];
						if(array_key_exists('lng',$coordArr)) $recMap['decimallongitude'] = $coordArr['lng'];
					}
				}
			}
			$vCoord = (isset($recMap['verbatimcoordinates'])?$recMap['verbatimcoordinates']:'');
			if(!($no && strpos($vCoord,$no))) $recMap['verbatimcoordinates'] = ($vCoord?$vCoord.'; ':'').$zo.' '.$ea.'E '.$no.'N';
		}
		//Transfer TRS to verbatim coords
		if(isset($recMap['trstownship']) && $recMap['trstownship'] && isset($recMap['trsrange']) && $recMap['trsrange']){
			$vCoord = (isset($recMap['verbatimcoordinates'])?$recMap['verbatimcoordinates']:'');
			if($vCoord) $vCoord .= '; ';
			$vCoord .= (stripos($recMap['trstownship'],'t') === false?'T':'').$recMap['trstownship'].' ';
			$vCoord .= (stripos($recMap['trsrange'],'r') === false?'R':'').$recMap['trsrange'].' ';
			if(isset($recMap['trssection'])) $vCoord .= (stripos($recMap['trssection'],'s') === false?'sec':'').$recMap['trssection'].' ';
			if(isset($recMap['trssectiondetails'])) $vCoord .= $recMap['trssectiondetails'];
			$recMap['verbatimcoordinates'] = trim($vCoord);
		}
		//coordinate uncertainity
		$radius = '';
		$unitStr = '';
		if(isset($recMap['coordinateuncertaintyinmeters']) && $recMap['coordinateuncertaintyinmeters'] && !is_numeric($recMap['coordinateuncertaintyinmeters'])){
			if(preg_match('/([\d.-]+)\s*([a-z\']+)/i', $recMap['coordinateuncertaintyinmeters'], $m)){
				//value is not numeric only and thus probably have units imbedded
				$radius = $m[1];
				$unitStr = strtolower($m[2]);
			}
			$recMap['coordinateuncertaintyinmeters'] = '';
		}
		if(isset($recMap['coordinateuncertaintyradius']) && is_numeric($recMap['coordinateuncertaintyradius']) && isset($recMap['coordinateuncertaintyunits']) && $recMap['coordinateuncertaintyunits']){
			//uncertainity was supplied a separate values
			$radius = $recMap['coordinateuncertaintyradius'];
			$unitStr = strtolower($recMap['coordinateuncertaintyunits']);
		}
		if($radius && $unitStr && $unitStr != 'n/a' && (!isset($recMap['coordinateuncertaintyinmeters']) || !$recMap['coordinateuncertaintyinmeters'])){
			if($unitStr == 'mi' || $unitStr == 'mile' || $unitStr == 'miles') $recMap['coordinateuncertaintyinmeters'] = round($radius*1609);
			elseif($unitStr == 'km' || $unitStr == 'kilometers') $recMap['coordinateuncertaintyinmeters'] = round($radius*1000);
			elseif($unitStr == 'm' || $unitStr == 'meter' || $unitStr == 'meters') $recMap['coordinateuncertaintyinmeters'] = $radius;
			elseif($unitStr == 'ft' || $unitStr == 'f' || $unitStr == 'feet' || $unitStr == "'") $recMap['coordinateuncertaintyinmeters'] = round($radius*0.3048);
		}
		if(!isset($recMap['coordinateuncertaintyinmeters'])){
			//Assume a mapping error and meters were mapped to wrong field
			if(isset($recMap['coordinateuncertaintyradius']) && is_numeric($recMap['coordinateuncertaintyradius'])) $recMap['coordinateuncertaintyinmeters'] = $recMap['coordinateuncertaintyradius'];
			elseif(isset($recMap['coordinateuncertaintyunits']) && is_numeric($recMap['coordinateuncertaintyunits'])) $recMap['coordinateuncertaintyinmeters'] = $recMap['coordinateuncertaintyunits'];
		}
		unset($recMap['coordinateuncertaintyradius']);
		unset($recMap['coordinateuncertaintyunits']);
		//Check to see if evelation are valid numeric values
		if((isset($recMap['minimumelevationinmeters']) && $recMap['minimumelevationinmeters'] && !is_numeric($recMap['minimumelevationinmeters']))
				|| (isset($recMap['maximumelevationinmeters']) && $recMap['maximumelevationinmeters'] && !is_numeric($recMap['maximumelevationinmeters']))){
					$vStr = (isset($recMap['verbatimelevation'])?$recMap['verbatimelevation']:'');
					if(isset($recMap['minimumelevationinmeters']) && $recMap['minimumelevationinmeters']) $vStr .= ($vStr?'; ':'').$recMap['minimumelevationinmeters'];
					if(isset($recMap['maximumelevationinmeters']) && $recMap['maximumelevationinmeters']) $vStr .= '-'.$recMap['maximumelevationinmeters'];
					$recMap['verbatimelevation'] = $vStr;
					$recMap['minimumelevationinmeters'] = '';
					$recMap['maximumelevationinmeters'] = '';
		}
		//Verbatim elevation
		if(array_key_exists('verbatimelevation',$recMap) && $recMap['verbatimelevation'] && (!array_key_exists('minimumelevationinmeters',$recMap) || !$recMap['minimumelevationinmeters'])){
			$eArr = self::parseVerbatimElevation($recMap['verbatimelevation']);
			if($eArr){
				if(array_key_exists('minelev',$eArr)){
					$recMap['minimumelevationinmeters'] = $eArr['minelev'];
					if(array_key_exists('maxelev',$eArr)) $recMap['maximumelevationinmeters'] = $eArr['maxelev'];
				}
			}
		}
		//Deal with elevation when in two fields (number and units)
		if(isset($recMap['elevationnumber']) && $recMap['elevationnumber']){
			$elevStr = $recMap['elevationnumber'].$recMap['elevationunits'];
			//Try to extract meters
			$eArr = self::parseVerbatimElevation($elevStr);
			if($eArr){
				if(array_key_exists('minelev',$eArr)){
					$recMap['minimumelevationinmeters'] = $eArr['minelev'];
					if(array_key_exists('maxelev',$eArr)) $recMap['maximumelevationinmeters'] = $eArr['maxelev'];
				}
			}
			if(!$eArr || !stripos($elevStr,'m')){
				$vElev = (isset($recMap['verbatimelevation'])?$recMap['verbatimelevation']:'');
				if($vElev) $vElev .= '; ';
				$recMap['verbatimelevation'] = $vElev.$elevStr;
			}
		}
		//Concatenate collectorfamilyname and collectorinitials into recordedby
		if(isset($recMap['collectorfamilyname']) && $recMap['collectorfamilyname'] && (!isset($recMap['recordedby']) || !$recMap['recordedby'])){
			$recordedBy = $recMap['collectorfamilyname'];
			if(isset($recMap['collectorinitials']) && $recMap['collectorinitials']) $recordedBy .= ', '.$recMap['collectorinitials'];
			$recMap['recordedby'] = $recordedBy;
			//Need to add code that maps to collector table

		}

		if(array_key_exists("specificepithet",$recMap)){
			if($recMap["specificepithet"] == 'sp.' || $recMap["specificepithet"] == 'sp') $recMap["specificepithet"] = '';
		}
		if(array_key_exists("taxonrank",$recMap)){
			$tr = strtolower($recMap["taxonrank"]);
			if($tr == 'species' || !isset($recMap["specificepithet"]) || !$recMap["specificepithet"]) $recMap["taxonrank"] = '';
			if($tr == 'subspecies') $recMap["taxonrank"] = 'subsp.';
			if($tr == 'variety') $recMap["taxonrank"] = 'var.';
			if($tr == 'forma') $recMap["taxonrank"] = 'f.';
		}

		//Populate sciname if null
		if(array_key_exists('sciname',$recMap) && $recMap['sciname']){
			if(substr($recMap['sciname'],-4) == ' sp.') $recMap['sciname'] = substr($recMap['sciname'],0,-4);
			if(substr($recMap['sciname'],-3) == ' sp') $recMap['sciname'] = substr($recMap['sciname'],0,-3);

			$recMap['sciname'] = str_replace(array(' ssp. ',' ssp '),' subsp. ',$recMap['sciname']);
			$recMap['sciname'] = str_replace(' var ',' var. ',$recMap['sciname']);

			$pattern = '/\b(cf\.|cf|aff\.|aff)\s{1}/';
			if(preg_match($pattern,$recMap['sciname'],$m)){
				$recMap['identificationqualifier'] = $m[1];
				$recMap['sciname'] = preg_replace($pattern,'',$recMap['sciname']);
			}
		}
		else{
			if(array_key_exists('genus',$recMap) && array_key_exists('specificepithet',$recMap) && array_key_exists('infraspecificepithet',$recMap)){
				//Build sciname from individual units supplied by source
				$sciName = trim($recMap['genus'].' '.$recMap['specificepithet']);
				if(array_key_exists('taxonrank',$recMap)) $sciName .= ' '.$recMap['taxonrank'];
				$sciName .= ' '.$recMap['infraspecificepithet'];
				$recMap['sciname'] = trim($sciName);
			}
			elseif(array_key_exists('scientificname',$recMap)){
				//Clean and parse scientific name
				$parsedArr = TaxonomyUtilities::parseScientificName($recMap['scientificname']);
				$scinameStr = '';
				if(array_key_exists('unitname1',$parsedArr)){
					$scinameStr = $parsedArr['unitname1'];
					if(!array_key_exists('genus',$recMap) || $recMap['genus']){
						$recMap['genus'] = $parsedArr['unitname1'];
					}
				}
				if(array_key_exists('unitname2',$parsedArr)){
					$scinameStr .= ' '.$parsedArr['unitname2'];
					if(!array_key_exists('specificepithet',$recMap) || !$recMap['specificepithet']){
						$recMap['specificepithet'] = $parsedArr['unitname2'];
					}
				}
				if(array_key_exists('unitind3',$parsedArr)){
					$scinameStr .= ' '.$parsedArr['unitind3'];
					if((!array_key_exists('taxonrank',$recMap) || !$recMap['taxonrank'])){
						$recMap['taxonrank'] = $parsedArr['unitind3'];
					}
				}
				if(array_key_exists('unitname3',$parsedArr)){
					$scinameStr .= ' '.$parsedArr['unitname3'];
					if(!array_key_exists('infraspecificepithet',$recMap) || !$recMap['infraspecificepithet']){
						$recMap['infraspecificepithet'] = $parsedArr['unitname3'];
					}
				}
				if(array_key_exists('author',$parsedArr)){
					if(!array_key_exists('scientificnameauthorship',$recMap) || !$recMap['scientificnameauthorship']){
						$recMap['scientificnameauthorship'] = $parsedArr['author'];
					}
				}
				$recMap['sciname'] = trim($scinameStr);
			}
		}
		if(isset($recMap['authorinfraspecific']) && $recMap['authorinfraspecific']){
			$recMap['scientificnameauthorship'] = $recMap['authorinfraspecific'];
		}
		elseif(isset($recMap['authorspecies']) && $recMap['authorspecies']){
			$recMap['scientificnameauthorship'] = $recMap['authorspecies'];
		}
		unset($recMap['authorinfraspecific']);
		unset($recMap['authorspecies']);
		//Deal with Specify specific fields
		if(isset($recMap['specify:forma']) && $recMap['specify:forma']){
			$recMap['taxonrank'] = 'f.';
			$recMap['infraspecificepithet'] = $recMap['specify:forma'];
			if(isset($recMap['specify:forma_author']) && $recMap['specify:forma_author']){
				$recMap['scientificnameauthorship'] = $recMap['specify:forma_author'];
			}
		}
		elseif(isset($recMap['specify:variety']) && $recMap['specify:variety']){
			$recMap['taxonrank'] = 'var.';
			$recMap['infraspecificepithet'] = $recMap['specify:variety'];
			if(isset($recMap['specify:variety_author']) && $recMap['specify:variety_author']){
				$recMap['scientificnameauthorship'] = $recMap['specify:variety_author'];
			}
		}
		elseif(isset($recMap['specify:subspecies']) && $recMap['specify:subspecies']){
			$recMap['taxonrank'] = 'subsp.';
			$recMap['infraspecificepithet'] = $recMap['specify:subspecies'];
			if(isset($recMap['specify:subspecies_author']) && $recMap['specify:subspecies_author']){
				$recMap['scientificnameauthorship'] = $recMap['specify:subspecies_author'];
			}
		}
		unset($recMap['specify:forma']);
		unset($recMap['specify:forma_author']);
		unset($recMap['specify:variety']);
		unset($recMap['specify:variety_author']);
		unset($recMap['specify:subspecies']);
		unset($recMap['specify:subspecies_author']);
		if(isset($recMap['specify:collector_last_name']) && $recMap['specify:collector_last_name']){
			$recordedByStr = '';
			if(isset($recMap['specify:collector_first_name']) && $recMap['specify:collector_first_name']){
				$recordedByStr = $recMap['specify:collector_first_name'];
			}
			if(isset($recMap['specify:collector_middle_initial']) && $recMap['specify:collector_middle_initial']){
				$recordedByStr .= ' '.$recMap['specify:collector_middle_initial'];
			}
			$recordedByStr .= ' '.$recMap['specify:collector_last_name'];
			if($recordedByStr) $recMap['recordedby'] = trim($recordedByStr);
		}
		unset($recMap['specify:collector_first_name']);
		unset($recMap['specify:collector_middle_initial']);
		unset($recMap['specify:collector_last_name']);
		if(isset($recMap['specify:determiner_last_name']) && $recMap['specify:determiner_last_name']){
			$identifiedBy = '';
			if(isset($recMap['specify:determiner_first_name']) && $recMap['specify:determiner_first_name']){
				$identifiedBy = $recMap['specify:determiner_first_name'];
			}
			if(isset($recMap['specify:determiner_middle_initial']) && $recMap['specify:determiner_middle_initial']){
				$identifiedBy .= ' '.$recMap['specify:determiner_middle_initial'];
			}
			$identifiedBy .= ' '.$recMap['specify:determiner_last_name'];
			if($identifiedBy) $recMap['identifiedby'] = trim($identifiedBy);
		}
		unset($recMap['specify:determiner_first_name']);
		unset($recMap['specify:determiner_middle_initial']);
		unset($recMap['specify:determiner_last_name']);
		if(isset($recMap['specify:qualifier_position'])){
			if($recMap['specify:qualifier_position']){
				$idQualifier = '';
				if(isset($recMap['identificationqualifier'])) $idQualifier = $recMap['identificationqualifier'];
				$recMap['identificationqualifier'] = trim($idQualifier.' '.$recMap['specify:qualifier_position']);
			}
			unset($recMap['specify:qualifier_position']);
		}
		if(isset($recMap['specify:latitude1']) && $recMap['specify:latitude1']){
			$verbLatLngStr = '';
			if(isset($recMap['specify:latitude2']) && $recMap['specify:latitude2'] && $recMap['specify:latitude2'] != 'null' && $recMap['specify:latitude1'] != $recMap['specify:latitude2']){
				$verbLatLngStr = $recMap['specify:latitude1'].' to '.$recMap['specify:latitude2'];
			}
			if(isset($recMap['specify:longitude2']) && $recMap['specify:longitude2'] && $recMap['specify:longitude2'] != 'null' && $recMap['specify:longitude1'] != $recMap['specify:longitude2']){
				$verbLatLngStr .= '; '.$recMap['specify:longitude1'].' to '.$recMap['specify:longitude2'];
				//todo: populate decimal Lat/long with mid-point and radius
			}
			if($verbLatLngStr){
				if(isset($recMap['verbatimcoordinates']) && $recMap['verbatimcoordinates']) $recMap['verbatimcoordinates'] .= '; '.$verbLatLngStr;
				else $recMap['verbatimcoordinates'] = $verbLatLngStr;
			}
			else{
				$recMap['decimallatitude'] = $recMap['specify:latitude1'];
				$recMap['decimallongitude'] = $recMap['specify:longitude1'];
			}
		}
		unset($recMap['specify:latitude1']);
		unset($recMap['specify:latitude2']);
		unset($recMap['specify:longitude1']);
		unset($recMap['specify:longitude2']);
		if(isset($recMap['specify:land_ownership']) && $recMap['specify:land_ownership']){
			$locStr = $recMap['specify:land_ownership'];
			if(isset($recMap['locality']) && $recMap['locality']){
				if(stripos($recMap['locality'], $recMap['specify:land_ownership']) === false) $locStr .= $recMap['locality'];
				else $locStr = '';
			}
			if($locStr) $recMap['locality'] = trim($locStr,';, ');
		}
		unset($recMap['specify:land_ownership']);
		if(isset($recMap['specify:topo_quad']) && $recMap['specify:topo_quad']){
			$locStr = '';
			if(isset($recMap['locality']) && $recMap['locality']) $locStr = $recMap['locality'];
			$recMap['locality'] = trim($locStr.'; '.$recMap['specify:topo_quad'],'; ');
		}
		unset($recMap['specify:topo_quad']);
		if(isset($recMap['specify:georeferenced_by_last_name']) && $recMap['specify:georeferenced_by_last_name']){
			$georefBy = '';
			if(isset($recMap['specify:georeferenced_by_first_name']) && $recMap['specify:georeferenced_by_first_name']){
				$georefBy = $recMap['specify:georeferenced_by_first_name'];
			}
			if(isset($recMap['specify:georeferenced_by_middle_initial']) && $recMap['specify:georeferenced_by_middle_initial']){
				$georefBy .= ' '.$recMap['specify:georeferenced_by_middle_initial'];
			}
			$georefBy .= ' '.$recMap['specify:georeferenced_by_last_name'];
			if($georefBy) $recMap['georeferencedby'] = trim($georefBy);
		}
		unset($recMap['specify:georeferenced_by_first_name']);
		unset($recMap['specify:georeferenced_by_middle_initial']);
		unset($recMap['specify:georeferenced_by_last_name']);
		if(isset($recMap['specify:locality_continued'])){
			if($recMap['specify:locality_continued']) $recMap['locality'] .= trim(' '.$recMap['specify:locality_continued']);
			unset($recMap['specify:locality_continued']);
		}
		if(isset($recMap['specify:georeferenced_date'])){
			if($recMap['specify:georeferenced_date']){
				$georefBy = '';
				if(isset($recMap['georeferencedby']) && $recMap['georeferencedby']) $georefBy = $recMap['georeferencedby'];
				$recMap['georeferencedby'] = trim($georefBy.'; georef date: '.$recMap['specify:georeferenced_date'],'; ');
			}
			unset($recMap['specify:georeferenced_date']);
		}
		if(isset($recMap['specify:elevation_(ft)'])){
			if($recMap['specify:elevation_(ft)']){
				$verbElev = '';
				if(isset($recMap['verbatimelevation']) && $recMap['verbatimelevation']) $verbElev = $recMap['verbatimelevation'];
				$recMap['verbatimelevation'] = trim($verbElev.'; '.$recMap['specify:elevation_(ft)'].'ft.','; ');
			}
			unset($recMap['specify:elevation_(ft)']);
		}
		if(isset($recMap['specify:preparer_last_name']) && $recMap['specify:preparer_last_name']){
			$prepStr = '';
			if(isset($recMap['preparations']) && $recMap['preparations']) $prepStr = $recMap['preparations'];
			$prepBy = '';
			if(isset($recMap['specify:preparer_first_name']) && $recMap['specify:preparer_first_name']){
				$prepBy .= $recMap['specify:preparer_first_name'];
			}
			if(isset($recMap['specify:preparer_middle_initial']) && $recMap['specify:preparer_middle_initial']){
				$prepBy .= ' '.$recMap['specify:preparer_middle_initial'];
			}
			$prepBy = ' '.$recMap['specify:preparer_last_name'];
			if($prepBy) $recMap['preparations'] = trim($prepStr.'; preparer: '.$prepBy);
		}
		unset($recMap['specify:preparer_first_name']);
		unset($recMap['specify:preparer_middle_initial']);
		unset($recMap['specify:preparer_last_name']);
		if(isset($recMap['specify:prepared_by_date'])){
			if($recMap['specify:prepared_by_date']){
				$prepStr = '';
				if(isset($recMap['preparations']) && $recMap['preparations']) $prepStr = $recMap['preparations'];
				$recMap['preparations'] = $prepStr.'; prepared by date: '.$recMap['specify:prepared_by_date'];
			}
			unset($recMap['specify:prepared_by_date']);
		}
		if(isset($recMap['specify:cataloger_last_name']) && $recMap['specify:cataloger_last_name']){
			$enteredBy = '';
			if(isset($recMap['specify:cataloger_first_name']) && $recMap['specify:cataloger_first_name']){
				$enteredBy = $recMap['specify:cataloger_first_name'];
			}
			if(isset($recMap['specify:cataloger_middle_initial']) && $recMap['specify:cataloger_middle_initial']){
				$enteredBy .= ' '.$recMap['specify:cataloger_middle_initial'];
			}
			$enteredBy .= ' '.$recMap['specify:cataloger_last_name'];
			$recMap['recordenteredby'] = trim($enteredBy);
		}
		unset($recMap['specify:cataloger_first_name']);
		unset($recMap['specify:cataloger_middle_initial']);
		unset($recMap['specify:cataloger_last_name']);
		if(isset($recMap['specify:cataloged_date'])){
			if($recMap['specify:cataloged_date']){
				$recBy = '';
				if(isset($recMap['recordenteredby']) && $recMap['recordenteredby']) $recBy = $recMap['recordenteredby'];
				$recMap['recordenteredby'] = trim($recBy.'; cataloged date: '.$recMap['specify:cataloged_date'],'; ');
			}
			unset($recMap['specify:cataloged_date']);
		}
		return $recMap;
	}

	private static function dateCheck($inStr){
		$retStr = $inStr;
		if($inStr > 2100 && $inStr < 45000){
			//Date field was converted to Excel's numeric format (number of days since 01/01/1900)
			$retStr = date('Y-m-d', mktime(0,0,0,1,$inStr-1,1900));
		}
		elseif($inStr > 2200000 && $inStr < 2500000){
			$dArr = explode('/',jdtogregorian($inStr));
			$retStr = $dArr[2].'-'.$dArr[0].'-'.$dArr[1];
		}
		elseif($inStr > 19000000){
			$retStr = substr($inStr,0,4).'-'.substr($inStr,4,2).'-'.substr($inStr,6,2);
		}
		return $retStr;
	}
}
?>
