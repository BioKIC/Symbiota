<?php

class SpecProcNlpLbcc {

	protected $conn;
	protected $catalogNumber;
	protected $collId;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	public function parse($rawStr) {
		$rawStr = trim($this->convertBadChars(str_replace("\t", " ", $rawStr)));
		//If OCR source is from tesseract (utf-8 is default), convert to a latin1 character set
		//if(mb_detect_encoding($rawStr,'UTF-8,ISO-8859-1') == "UTF-8"){
		//	$rawStr = utf8_decode($rawStr);
		//}
		$rawStr = trim($this->fixString($rawStr));
		if($rawStr && !$this->isMostlyGarbage2($rawStr, 0.50)) {
			$results = array();
			$labelInfo = $this->getLabelInfo($rawStr, $this->collId);
			if($labelInfo) {

				$recordedBy = "";
				$recordedById = "";
				$recordNumber = "";
				$otherCatalogNumbers = '';
				$identifiedBy = '';
				$associatedCollectors = '';
				$rawStr_for_Collector = $rawStr;
				if(array_key_exists('rawstr', $labelInfo)) {
					$rawStr_for_Collector = $labelInfo['rawstr'];
					unset($labelInfo['rawstr']);
				}
				if(array_key_exists('recordedBy', $labelInfo)) $recordedBy = $labelInfo['recordedBy'];
				if(array_key_exists('recordedById', $labelInfo)) $recordedById = $labelInfo['recordedById'];
				if(array_key_exists('recordNumber', $labelInfo)) $recordNumber = $labelInfo['recordNumber'];
				if(array_key_exists('otherCatalogNumbers', $labelInfo)) $otherCatalogNumbers = $labelInfo['otherCatalogNumbers'];
				if(array_key_exists('associatedCollectors', $labelInfo)) $associatedCollectors = $labelInfo['associatedCollectors'];
				if(array_key_exists('identifiedBy', $labelInfo)) $identifiedBy = $labelInfo['identifiedBy'];
				//echo "\nline 36, recordedBy: ".$recordedBy."\nrecordNumber: ".$recordNumber."\nidentifiedBy: ".$identifiedBy."\notherCatalogNumbers: ".$otherCatalogNumbers."\n";
				if(!$recordedBy) {
					$collectorInfo = $this->getCollector($rawStr_for_Collector);
					if($collectorInfo) {
						if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
						if(!$recordedBy) {
							if(array_key_exists('collectorName', $collectorInfo)) $recordedBy = $collectorInfo['collectorName'];
						}
						if(!$recordNumber) {
							if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
						}
						if(!$identifiedBy) {
							if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
						}
						if(!$otherCatalogNumbers) {
							if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
						} else if(array_key_exists('otherCatalogNumbers', $collectorInfo)) {
							$temp = $collectorInfo['otherCatalogNumbers'];
							if($temp) $otherCatalogNumbers .= "; ".$collectorInfo['otherCatalogNumbers'];
						}
					}
				}
				$results['otherCatalogNumbers'] = $otherCatalogNumbers;
				$results['associatedCollectors'] = $associatedCollectors;
				$results['recordedBy'] = $recordedBy;
				$results['recordedById'] = $recordedById;
				$results['recordNumber'] = $recordNumber;
				if(array_key_exists('verbatimElevation', $labelInfo)) {
					$minMax = $this->calculateMaxMinElevation($labelInfo['verbatimElevation']);
					if($minMax) {
						if(array_key_exists('minimumElevationInMeters', $minMax)) $results['minimumElevationInMeters'] = $minMax['minimumElevationInMeters'];
						if(array_key_exists('maximumElevationInMeters', $minMax)) $results['maximumElevationInMeters'] = $minMax['maximumElevationInMeters'];
					}
				}
				$event_date = "";
				$det_date = "";
				if(array_key_exists('eventDate', $labelInfo)) {
					$t = $labelInfo['eventDate'];
					if(is_array($t) && $t) $event_date = $this->formatDate($t);
					unset($labelInfo['eventDate']);
				}
				if(array_key_exists('dateIdentified', $labelInfo)) {
					$t = $labelInfo['dateIdentified'];
					if(is_array($t) && $t) $det_date = $this->formatDate($t);
					unset($labelInfo['dateIdentified']);
				}
				$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
				if(strlen($identifiedBy) == 0) {
					$identifier = $this->getIdentifier($rawStr, $possibleMonths);
					if($identifier != null) {
						$identifiedBy = $identifier[0];
						if(!$det_date) $det_date = $this->formatDate($identifier[1]);
					}
				}
				if(!$event_date || !$det_date) {
					$dates = $this->getDates($rawStr, $possibleMonths);
					if($dates) {
						if(!$event_date) $event_date = $this->formatDate($this->min_date($dates));
						if(!$det_date && count($dates) > 1) $det_date = $this->formatDate($this->max_date($dates));
					}
				}
				$results['identifiedBy'] = $identifiedBy;
				$results['eventDate'] = $event_date;
				$results['dateIdentified'] = $det_date;
				$verbatimCoordinates = "";
				if(array_key_exists('verbatimCoordinates', $labelInfo)) $verbatimCoordinates = $labelInfo['verbatimCoordinates'];
				if(!$verbatimCoordinates) $verbatimCoordinates = $this->getVerbatimCoordinates($rawStr);
				if($verbatimCoordinates) {
					$results['verbatimCoordinates'] = $verbatimCoordinates;
					$latLongs = $this->getLatLongs($verbatimCoordinates);
					$cntLongLength = count($latLongs);
					$lat = "";
					$long = "";
					if($cntLongLength) {
						$latLong = $latLongs[$cntLongLength-1];
						if(array_key_exists('latitude', $latLong)) {
							$lat = $this->getDigitalLatLong($latLong['latitude']);
							if($lat) $results['decimalLatitude'] = round($lat, 5);
						}
						if(array_key_exists('longitude', $latLong)) {
							$long = $this->getDigitalLatLong($latLong['longitude']);
							if($long) $results['decimalLongitude'] = round($long, 5);
						}
					}
				}
				if(array_key_exists('ometid', $labelInfo)) {
					$exsTitleAndAbbr = $this->getExsiccatiTitleAndAbbreviation($labelInfo['ometid']);
					if($exsTitleAndAbbr) $results['exstitle'] = $exsTitleAndAbbr;
				}
			}
			//return $this->combineArrays($labelInfo, $results);
			return $this->combineArrays($results, $labelInfo);
		}
		return array();
	}

	protected function getLabelInfo($str) {
		if($this->isKryptogamaeExsiccatiVindobonensiLabel($str)) return $this->doKryptogamaeExsiccatiVindobonensiLabel($str);
		if($str) return $this->doGenericLabel($str);
		return array();
	}

	private function getExsiccatiTitleAndAbbreviation($ometid) {
		if($ometid) {
			$sql = "SELECT CONCAT(title, ' [', abbreviation, ']') titleAndAbbr from omexsiccatititles where ometid = ".$ometid;
			if($rs = $this->conn->query($sql)) {
				if($r = $rs->fetch_object()) return $r->titleAndAbbr;
			}
		}
	}

	protected function analyzeLocalityLine($line, $acceptableSmallWords="(?:road|pine|park|tree|fork|cove|camp|fire|bay|[lb]og|soil|sand|oaks?|base|area|lane|farm|bark|twig|mats?|Ilex)") {
		//countPotentialLocalityWords > 0 for the line and it doesn't begin with on or Fairly common on, etc
		$firstPart = "";
		$lastPart = "";
		$sLine = $this->splitLocalityLine($line, $acceptableSmallWords);
		if($sLine) {
			$firstPart = $sLine[0];
			$lastPart = $sLine[1];
			//echo "\nline 15697, line: ".$line."\nfirstPart: ".$firstPart."\nlastPart: ".$lastPart."\ncountPotentialLocalityWords(firstPart): ".$this->countPotentialLocalityWords($firstPart)."\ncountPotentialHabitatWords(firstPart): ".$this->countPotentialHabitatWords($firstPart)."\ncountPotentialLocalityWords(lastPart): ".$this->countPotentialLocalityWords($lastPart)."\ncountPotentialHabitatWords(lastPart): ".$this->countPotentialHabitatWords($lastPart)."\n";
			if(strlen($lastPart) < 4 && preg_match("/(.*[A-Za-z]{4,})[,;] (.+)/", $firstPart, $mats2)) {
				$firstPart = trim($mats2[1]);
				$lastPart = trim($mats2[2]);
			}
			$hCount1 = $this->countPotentialHabitatWords($firstPart);
			$lCount1 = $this->countPotentialLocalityWords($firstPart);
			$hCount2 = $this->countPotentialHabitatWords($lastPart);
			$lCount2 = $this->countPotentialLocalityWords($lastPart);
			if(preg_match("/^Habitat[:;]? ?(.*)/i", $firstPart, $mats3)) {
				if($hCount1 && $lCount2) return array('locality' => $lastPart, 'habitat' => trim($mats3[1]));
				else {
					$firstPart = trim($mats3[1]);
					$hCount1 = $this->countPotentialHabitatWords($firstPart);
					$lCount1 = $this->countPotentialLocalityWords($firstPart);
					if(preg_match("/^Habitat[:;]? ?(.*)/i", $line, $mats4)) $line = trim($mats4[1]);
				}
			} else if(preg_match("/^Habitat[:;] ?(.*)/i", $lastPart, $mats3)) {
				if($hCount1 && $lCount2) return array('locality' => $firstPart, 'habitat' => trim($mats3[1]));
				else {
					$lastPart = trim($mats3[1]);
					$hCount2 = $this->countPotentialHabitatWords($lastPart);
					$lCount2 = $this->countPotentialLocalityWords($lastPart);
				}
			}
			if($lCount1 == 0) {//no locality words in first part so all of them must be in the last part
				if($hCount1) return array('locality' => $lastPart, 'habitat' => $firstPart);
			}
			if($lCount2 == 0) {//no locality words in last part so all of them must be in the first part
				if(preg_match("/^(?:Fairly |Quite |Very |Not |Mostly )?(?:(?:Un)?Common|(?:In)?Frequent|(?:Locally )?Abundant|Found|Loose|Epi(?:phyt|lith|xyl)ic|Xylicolous|Growing) On .+/i", $lastPart)) return array('locality' => $firstPart, 'substrate' => $lastPart);
				if(preg_match("/^(.+)[,.;] {1,2}((?:(?:Fairly |Quite |Very |Not |Mostly )?(?:(?:Un)?Common |(?:In)?Frequent |(?:Locally )?Abundant |Found |Loose |Epi(?:phyt|lith|xyl)ic |Xylicolous |Growing ))?On .+)/i", $lastPart, $mats)) {
					//The last part contains a part that begins with "on" which suggests that it may contain the substrate
					$startOfLastPart = trim($mats[1]);
					//check to see if the part of the last part before the word "on" contains habitat words.  if not add it to the first part (locality)
					if($this->countPotentialHabitatWords($startOfLastPart)) return array('locality' => $firstPart, 'habitat' => $startOfLastPart, 'substrate' => trim($mats[2]));
					return array('locality' => $firstPart.". ".$startOfLastPart, 'substrate' => trim($mats[2]));
				}
				if($hCount2) {
					//the last part contains no locality words but does contain habitat words.
					//If it begins with "on " it is probably the substrate.  Otherwise it is the habitat
					if(strcasecmp(substr($lastPart, 0, 3), "On ") == 0) return array('locality' => $firstPart, 'substrate' => $lastPart);
					else {
						$sFirstPart = $this->splitLocalityLine($firstPart, $acceptableSmallWords);
						$startOfFirstPart = $sFirstPart[0];
						$endOfFirstPart = $sFirstPart[1];
						//echo "\nline 15742, startOfFirstPart: ".$startOfFirstPart."\nendOfFirstPart: ".$endOfFirstPart."\ncountPotentialLocalityWords(startOfFirstPart): ".$this->countPotentialLocalityWords($startOfFirstPart)."\ncountPotentialHabitatWords(startOfFirstPart): ".$this->countPotentialHabitatWords($startOfFirstPart)."\ncountPotentialLocalityWords(endOfFirstPart): ".$this->countPotentialLocalityWords($endOfFirstPart)."\ncountPotentialHabitatWords(endOfFirstPart): ".$this->countPotentialHabitatWords($endOfFirstPart)."\n";
						$locality = $firstPart;
						$habitat = $lastPart;
						$separator = $sFirstPart[2];
						while($this->countPotentialLocalityWords($startOfFirstPart) && $this->countPotentialLocalityWords($endOfFirstPart) == 0) {
							if(strcasecmp(substr($endOfFirstPart, 0, 3), "On ") == 0) {
								return array('locality' => $startOfFirstPart, 'substrate' => $endOfFirstPart, 'habitat' => $habitat);
							} else if($this->countPotentialHabitatWords($endOfFirstPart)) {
								$locality = $startOfFirstPart;
								$habitat = $endOfFirstPart.$separator.$habitat;
								$sFirstPart = $this->splitLocalityLine($startOfFirstPart, $acceptableSmallWords);
								if($sFirstPart) {
									$startOfFirstPart = $sFirstPart[0];
									$endOfFirstPart = $sFirstPart[1];
									$separator = $sFirstPart[2];
								} else break;
							} else break;
						}
						return array('locality' => $locality, 'habitat' => $habitat);
					}
				}
			}//at this point we know that if lCount2 == 0 then hCount2 == 0 as well
			if($hCount1 == 0 && $hCount2 > $lCount1 && $lCount1) {
				return array('locality' => $firstPart, 'habitat' => $lastPart);
			}
			if(preg_match("/^(.+)[),.] ((?:along|near|(?:with)?in|at|be(?:low|yond|neath|hind)|above|under) .+)$/i", $firstPart, $mats2)) {//foreach($mats2 as $k=>$v) echo "\nline 13615, ".$k.": ".$v."\n";
				$startOfFirstPart = trim($mats2[1]);
				if($this->countPotentialLocalityWords($startOfFirstPart) == 0 && $this->countPotentialHabitatWords($startOfFirstPart)) {
					return array('locality' => trim($mats2[2]).", ".$lastPart, 'habitat' => $startOfFirstPart);
				}
			}
			$endOfLastPart = "";
			$startOfLastPart = "";
			if(preg_match("/(.*[A-Za-z]{5,}\)?)\\. (.+)/", $lastPart, $mats2)) {
				$endOfLastPart = trim($mats2[2]);
				$startOfLastPart = trim($mats2[1]);
			} else if(preg_match("/(.+ ".$acceptableSmallWords."\)?)\\. (.+)/i", $lastPart, $mats2)) {
				$endOfLastPart = trim($mats2[2]);
				$startOfLastPart = trim($mats2[1]);
			}//echo "\nline 15779, line: ".$line."\nfirstPart: ".$firstPart."\nstartOfLastPart: ".$startOfLastPart."\nendOfLastPart: ".$endOfLastPart."\ncountPotentialLocalityWords(startOfLastPart): ".$this->countPotentialLocalityWords($startOfLastPart)."\ncountPotentialHabitatWords(startOfLastPart): ".$this->countPotentialHabitatWords($startOfLastPart)."\ncountPotentialLocalityWords(endOfLastPart): ".$this->countPotentialLocalityWords($endOfLastPart)."\ncountPotentialHabitatWords(endOfLastPart): ".$this->countPotentialHabitatWords($endOfLastPart)."\n";
			if($startOfLastPart && $endOfLastPart) {
				if($this->countPotentialLocalityWords($endOfLastPart) == 0 && $this->countPotentialHabitatWords($endOfLastPart)) {
					return array('locality' => $firstPart.". ".$startOfLastPart, 'habitat' => $endOfLastPart);
				}
				$startOfFirstPart = "";
				$endOfFirstPart = "";
				if(preg_match("/^(.*[A-Za-z]{5,}\)?)\\. (.+)/", $firstPart, $mats3)) {
					$startOfFirstPart = trim($mats3[1]);
					$endOfFirstPart = trim($mats3[2]);
				} else if(preg_match("/^(.+ ".$acceptableSmallWords."\)?)\\. (.+)/i", $firstPart, $mats3)) {
					$startOfFirstPart = trim($mats3[1]);
					$endOfFirstPart = trim($mats3[2]);
				}
				if($startOfFirstPart && $endOfFirstPart) {
					if($this->countPotentialLocalityWords($startOfFirstPart) == 0 && $this->countPotentialHabitatWords($startOfFirstPart)) {
						return array('locality' => $endOfFirstPart.". ".$lastPart, 'habitat' => $startOfFirstPart);
					}
				}
			}
			$startOfFirstPart = "";
			$endOfFirstPart = "";
			if(preg_match("/^(.*[A-Za-z]{5,}\)?)\\. (.+)/", $firstPart, $mats2)) {
				$startOfFirstPart = trim($mats2[1]);
				$endOfFirstPart = trim($mats2[2]);
			} else if(preg_match("/^(.+ ".$acceptableSmallWords."\)?)\\. (.+)/i", $firstPart, $mats2)) {
				$startOfFirstPart = trim($mats2[1]);
				$endOfFirstPart = trim($mats2[2]);
			}//echo "\nline 15807, firstPart: ".$firstPart."\nstartOfFirstPart: ".$startOfFirstPart."\nendOfFirstPart: ".$endOfFirstPart."\ncountPotentialLocalityWords(startOfFirstPart): ".$this->countPotentialLocalityWords($startOfFirstPart)."\ncountPotentialHabitatWords(startOfFirstPart): ".$this->countPotentialHabitatWords($startOfFirstPart)."\ncountPotentialLocalityWords(endOfFirstPart): ".$this->countPotentialLocalityWords($endOfFirstPart)."\ncountPotentialHabitatWords(endOfFirstPart): ".$this->countPotentialHabitatWords($endOfFirstPart)."\n";
			if($startOfFirstPart && $endOfFirstPart) {
				if($this->countPotentialLocalityWords($startOfFirstPart) == 0) {
					if($this->countPotentialHabitatWords($startOfFirstPart)) return array('locality' => $endOfFirstPart.". ".$lastPart, 'habitat' => $startOfFirstPart);
				} else if($lCount2 == 0) {//then hCount2 == 0 and lCount1 > 0
					if($this->countPotentialLocalityWords($endOfFirstPart) == 0) {//then countPotentialLocalityWords(startOfFirstPart) > 0
						if(strcasecmp(substr($endOfFirstPart, 0, 3), "on ") == 0) return array('locality' => $startOfFirstPart, 'substrate' => $endOfFirstPart, 'habitat' => $lastPart);
						if($this->countPotentialHabitatWords($endOfFirstPart)) return array('locality' => $startOfFirstPart, 'habitat' => $endOfFirstPart.". ".$lastPart);
					} else if($this->countPotentialHabitatWords($endOfFirstPart)) return array('locality' => $startOfFirstPart, 'habitat' => $endOfFirstPart.". ".$lastPart);
				}
			}
			$startOfFirstPart = "";
			$endOfFirstPart = "";
			if(preg_match("/^(.*\([^)]*[A-Za-z]{3,}\)), (.+)/", $firstPart, $mats2)) {
				$startOfFirstPart = trim($mats2[1]);
				$endOfFirstPart = trim($mats2[2]);
			} else if(preg_match("/^(.*\(.+\)[^()]*[A-Za-z]{3,}), (.+)/", $firstPart, $mats2)) {
				$startOfFirstPart = trim($mats2[1]);
				$endOfFirstPart = trim($mats2[2]);
			} else if(preg_match("/^([^(]*[A-Za-z]{3,}\)?), (.+)/", $firstPart, $mats2)) {
				$startOfFirstPart = trim($mats2[1]);
				$endOfFirstPart = trim($mats2[2]);
			}//echo "\nline 15829, firstPart: ".$firstPart."\nstartOfFirstPart: ".$startOfFirstPart."\nendOfFirstPart: ".$endOfFirstPart."\ncountPotentialLocalityWords(startOfFirstPart): ".$this->countPotentialLocalityWords($startOfFirstPart)."\ncountPotentialHabitatWords(startOfFirstPart): ".$this->countPotentialHabitatWords($startOfFirstPart)."\ncountPotentialLocalityWords(endOfFirstPart): ".$this->countPotentialLocalityWords($endOfFirstPart)."\ncountPotentialHabitatWords(endOfFirstPart): ".$this->countPotentialHabitatWords($endOfFirstPart)."\n";
			if($startOfFirstPart && $endOfFirstPart) {
				$hCount11 = $this->countPotentialHabitatWords($startOfFirstPart);
				if($this->countPotentialLocalityWords($startOfFirstPart) == 0) {
					if($hCount11 > $this->countPotentialHabitatWords($endOfFirstPart)) {
						return array('locality' => $endOfFirstPart.", ".$lastPart, 'habitat' => $startOfFirstPart);
					}
				} else if($this->countPotentialHabitatWords($endOfFirstPart) == 0 && $hCount2 == 0 && $hCount11) {
					if($this->countPotentialLocalityWords($endOfFirstPart)) {
						return array('locality' => $endOfFirstPart.", ".$lastPart, 'habitat' => $startOfFirstPart);
					}// else return array('locality' => $endOfFirstPart.", ".$lastPart, 'habitat' => $startOfFirstPart);
				}
			}
			$endOfLastPart = "";
			$startOfLastPart = "";
			if(preg_match("/^(.*\([^)]*[A-Za-z]{3,}\)), (.+)/", $lastPart, $mats2)) {
				$endOfLastPart = trim($mats2[2]);
				$startOfLastPart = trim($mats2[1]);
			} else if(preg_match("/^([^(]*[A-Za-z]{3,}), (.+)/", $lastPart, $mats2)) {
				$endOfLastPart = trim($mats2[2]);
				$startOfLastPart = trim($mats2[1]);
			} else if(preg_match("/^([^(]*\([^)]+\)[^()]*[A-Za-z]{3,}), (.+)/", $lastPart, $mats2)) {
				$endOfLastPart = trim($mats2[2]);
				$startOfLastPart = trim($mats2[1]);
			}//echo "\nline 13676, startOfLastPart: ".$startOfLastPart."\nendOfLastPart: ".$endOfLastPart."\ncountPotentialLocalityWords(".$startOfLastPart."): ".$this->countPotentialLocalityWords($startOfLastPart)."\ncountPotentialHabitatWords(".$startOfLastPart."): ".$this->countPotentialHabitatWords($startOfLastPart)."\ncountPotentialLocalityWords(".$endOfLastPart."): ".$this->countPotentialLocalityWords($endOfLastPart)."\ncountPotentialHabitatWords(".$endOfLastPart."): ".$this->countPotentialHabitatWords($endOfLastPart)."\n";
			if($startOfLastPart && $endOfLastPart) {
				$hCount22 = $this->countPotentialHabitatWords($endOfLastPart);
				if($this->countPotentialLocalityWords($endOfLastPart) == 0 && $hCount22 && $hCount22 > $this->countPotentialHabitatWords($startOfLastPart)) {
					return array('locality' => $firstPart.". ".$startOfLastPart, 'habitat' => $endOfLastPart);
				}
				$startOfFirstPart = "";
				$endOfFirstPart = "";
				if(preg_match("/^(.*\([^)]*[A-Za-z]{3,}\)), (.+)/", $firstPart, $mats3)) {
					$startOfFirstPart = trim($mats3[1]);
					$endOfFirstPart = trim($mats3[2]);
				} else if(preg_match("/^([^(]*[A-Za-z]{3,}), (.+)/", $firstPart, $mats3)) {
					$startOfFirstPart = trim($mats3[1]);
					$endOfFirstPart = trim($mats3[2]);
				} else if(preg_match("/^([^(]*\([^)]+\)[^()]*[A-Za-z]{3,}), (.+)/", $firstPart, $mats3)) {
					$startOfFirstPart = trim($mats3[1]);
					$endOfFirstPart = trim($mats3[2]);
				}
				if($startOfFirstPart && $endOfFirstPart) {
					$hCount11 = $this->countPotentialHabitatWords($startOfFirstPart);
					if($this->countPotentialLocalityWords($startOfFirstPart) == 0 && $hCount11 && $hCount11 > $this->countPotentialHabitatWords($endOfFirstPart)) {
						return array('locality' => $endOfFirstPart.". ".$lastPart, 'habitat' => $startOfFirstPart);
					}
				}
			}
			if(preg_match("/^(.*\([^)]*[A-Za-z]{3,}\)) (.+)/", $firstPart, $mats2)) {
				$startOfFirstPart = trim($mats2[1]);
				$endOfFirstPart = trim($mats2[2]);
			}
			if($startOfFirstPart && $endOfFirstPart) {
				$hCount1 = $this->countPotentialHabitatWords($startOfFirstPart);
				if($this->countPotentialLocalityWords($startOfFirstPart) == 0 && $hCount1 && $hCount1 > $this->countPotentialHabitatWords($endOfFirstPart)) {
					return array('locality' => $endOfFirstPart.". ".$lastPart, 'habitat' => $startOfFirstPart);
				}
			}//echo "\nline 15887, firstPart: ".$firstPart."\nstartOfFirstPart: ".$startOfFirstPart."\nendOfFirstPart: ".$endOfFirstPart."\ncountPotentialLocalityWords(startOfFirstPart): ".$this->countPotentialLocalityWords($startOfFirstPart)."\ncountPotentialHabitatWords(startOfFirstPart): ".$this->countPotentialHabitatWords($startOfFirstPart)."\ncountPotentialLocalityWords(endOfFirstPart): ".$this->countPotentialLocalityWords($endOfFirstPart)."\ncountPotentialHabitatWords(endOfFirstPart): ".$this->countPotentialHabitatWords($endOfFirstPart)."\n";
			if($hCount1 > $hCount2 && $hCount1 > $lCount1 && $lCount2) return array('locality' => $lastPart, 'habitat' => $firstPart);
			if($hCount2 > $hCount1 && $hCount2 > $lCount2 && $lCount1) return array('locality' => $firstPart, 'habitat' => $lastPart);
			//echo "\nline 15890, firstPart: ".$firstPart."\nlastPart: ".$lastPart."\nline: ".$line."\nthis->countPotentialHabitatWords(firstPart): ".$this->countPotentialHabitatWords($firstPart)."\nthis->countPotentialHabitatWords(lastPart): ".$this->countPotentialHabitatWords($lastPart)."\n";
		} else if(preg_match("/^Habitat:? ?(.*)/i", $line, $mats) && $this->countPotentialHabitatWords($line)) return array('habitat' => trim(str_replace(";;;", ";", $line)));
		return array('locality' => trim(str_replace(";;;", ";", $line)));
	}

	protected function splitHabitatLine($line, $acceptableSmallWords) {
		if(preg_match("/^([A-Za-z ,.()-]+?)[,.]? (?<!and )((?:(?:just|immediately|slightly|well|high) )?(?:along|among|near|within|at|be(?:low|yond|neath|hind)|above|under) .+)$/i", $line, $mats)) {
			$temp = trim($mats[1]);//echo "\nline 16004, temp: ".$temp."\n";
			if(!preg_match("/^(?:just|immediately|slightly)$/i", $temp)) return array($temp, trim($mats[2]), ", ");
		}
		if(preg_match("/^(.+?); (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), "; ");
		if(preg_match("/^(.*?\([^)]*[A-Za-z]{4,}\))\\. (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ". ");
		if(preg_match("/^([^(]*?[A-Za-z]{5,})\\. (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ". ");
		if(preg_match("/^([^(]*? ".$acceptableSmallWords.")\\. (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ". ");
		if(preg_match("/^(.*?\([^)]* ".$acceptableSmallWords."\))\\. (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ". ");
		if(preg_match("/^(.*?\([^)]*[A-Za-z]{3,}\\.?\)), (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ", ");
		if(preg_match("/^([^(]*?[A-Za-z]{3,}), (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ", ");
		if(preg_match("/^(.*?\(.+\)[^()]*[A-Za-z]{3,}?), (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ", ");
		if(preg_match("/^(.*[A-Za-z]{5,}\)?)\\. (.+)/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ". ");
		if(preg_match("/^(.+?): (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ": ");
	}

	protected function splitLocalityLine($line, $acceptableSmallWords) {
		if(preg_match("/^(.+?);;; (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), "; ");
		if(preg_match("/^(.*[A-Za-z]{4,}\)?); (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), "; ");
		if(preg_match("/^(.+?); (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), "; ");
		if(!preg_match("/^.* Sects\\. .+$/", $line) && preg_match("/^(.*[A-Za-z]{5,}\)?)\\. (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ". ");
		if(preg_match("/^(.* ".$acceptableSmallWords."\)?)\\. (.+)$/i", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ". ");
		if(preg_match("/^([A-Za-z ,.()-]+?)[,.]? (?<!and )((?:(?:just|immediately|slightly|well|high) )?(?:along|among|near|within|at|be(?:low|yond|neath|hind)|above|under) .+)$/i", $line, $mats)) {
			$temp = trim($mats[1]);
			if(!preg_match("/^(?:just|immediately|slightly|well|high)$/i", $temp)) return array($temp, trim($mats[2]), ", ");
		}
		if(preg_match("/^(.*\([^)]*[A-Za-z]{4,}+\)), (.+?)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ", ");
		if(preg_match("/^(.*\(.+\)[^()]*[A-Za-z]{4,}+), (?!(?:[A-Za-z]+, ?)?[A-Za-z]+,? (?:and |& ))(.+?)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ", ");
		if(preg_match("/^([^(]* [A-Za-z]{3,}+), ?(?!(?:[A-Za-z]+, ?)?[A-Za-z]+ (?:and |& ))(.+?)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ", ");
		if(preg_match("/^(.*[A-Za-z]{4,}\)?): (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), ": ");
		if(preg_match("/^(.+\)) (.+)$/", $line, $mats)) return array(trim($mats[1]), trim($mats[2]), " ");
	}

	protected function mergeFields($first, $last, $joiner=null) {//echo "\nInput to mergeFields: first: ".$first.", last: ".$last."\n";
		$first = trim($first);
		$last = trim($last);
		$l1 = strlen($first);
		if($l1 == 0) return $last;
		else if(!preg_match("/\\b".preg_quote(trim($first, " .,;:"), "/")."\\b/i", trim($last, " .,;:"))
			&& !preg_match("/\\b".preg_quote( trim($last, " .,;:"), "/")."\\b/i", trim($first, " .,;:"))) {
			if($joiner == null) {
				if(preg_match("/.*-$/", $first)) {
					$pos = strrpos($first, " ");
					if($pos !== FALSE) {
						$lastPart = trim(substr($first, $pos));
						if(strlen($lastPart) > 2) $joiner = "";//don't want to treat the minus in K- as a hyphen
						else $joiner = " ";
					} else if(strlen($first) > 2) $joiner = "";
					else $joiner = " ";
				} else if(preg_match("/.*([.,;:])$/", $first, $mats)) $joiner = trim($mats[1])." ";
				else if(preg_match("/\\bo[nrf]$/", $first)) $joiner = " ";
				else if(preg_match("/^[A-Z]/", $last)) $joiner = ". ";
				else $joiner = ", ";
			}
			return trim($first, " :;,.").$joiner.ltrim($last, " :;,.");
		} else if($l1 > strlen($last)) return $first;
		else return $last;
	}

	protected function extractAssociatedTaxa($input) {//echo "\nInput to extractAssociatedTaxa: input: ".$input."\n";
		if(preg_match("/(.*?[;,.]? ?)\\b((?:Growing |Associated |entangled )?(?:along |together )?with(?: dominant)?)[;,:]? (.+)/i", $input, $mats)) {
			$firstPart = trim($mats[1]);
			$temp1 = trim($mats[3], " ,");
			$assTaxaList = "";
			if(preg_match("/(?:[A-Za-z]+(?: [A-Za-z]+)?\\.?) ?- ?(?:[A-Za-z]+(?: [A-Za-z]+)?\\.?)/i", $temp1, $mats2)) {
				while(preg_match("/^([A-Za-z]+(?: [A-Za-z]+)?\\.?)[;,-] ?(.+)$/i", $temp1, $mats2)) {
					$temp = trim($mats2[1]);
					if($this->isPossibleSciName(trim($temp, " \"\'"))) {
						$assTaxaList .= trim($temp, " \"\'").", ";
						$temp1 = trim($mats2[2]);
					} else {
						$assTaxaList = "";
						$temp1 = "";
						break;
					}
				}
				while(preg_match("/^([A-Za-z]+(?: [A-Za-z]+)?\\.?)(?: ?& ?| ?- ?| and )([A-Za-z]+(?: [A-Za-z]+)?\\.?.*)$/i", $temp1, $mats2)) {
					$temp = trim($mats2[1]);
					if($this->isPossibleSciName(trim($temp, " \"\',"))) {
						$assTaxaList .= trim($temp, " \"\',").", ";
						$temp1 = trim($mats2[2]);
						//$temp = trim($mats2[2]);
					} else {
						$assTaxaList = "";
						$temp1 = "";
						break;
					}
				}
			} else {
				while(preg_match("/^([A-Za-z]+(?: [A-Za-z]+)?\\.?)[;,] ?(.+)$/i", $temp1, $mats2)) {
					$temp = trim($mats2[1]);
					if($this->isPossibleSciName(trim($temp, " \"\'"))) {
						$assTaxaList .= trim($temp, " \"\'").", ";
						$temp1 = trim($mats2[2]);
					} else {
						$assTaxaList = "";
						$temp1 = "";
						break;
					}
				}
				while(preg_match("/^([A-Za-z]+(?: [A-Za-z]+)?\\.?)(?: ?& ?| and )([A-Za-z]+(?: [A-Za-z]+)?\\.?.*)$/i", $temp1, $mats2)) {
					$temp = trim($mats2[1]);
					if($this->isPossibleSciName(trim($temp, " \"\',"))) {
						$assTaxaList .= trim($temp, " \"\',").", ";
						$temp1 = trim($mats2[2]);
						//$temp = trim($mats2[2]);
					} else {
						$assTaxaList = "";
						$temp1 = "";
						break;
					}
				}
			}
			$temp1 = trim($temp1, " \"\',");
			$temp = "";
			$temp2 = "";
			if(preg_match("/^([A-Za-z]+(?: [A-Za-z]+)?\\.?)(.*)/i", $temp1, $mats2)) {
				$temp2 = trim($mats2[1]);
				$temp = trim($mats2[2]);
			}
			if($this->isPossibleSciName($temp2)) {
				$assTaxaList .= $temp2;
				$temp1 = $temp;
			}
			if(strlen($assTaxaList)) return array('input' => $firstPart, 'associatedTaxa' => $assTaxaList, 'rest' => $temp1);
			else return array('input' => $firstPart, 'associatedTaxa' => "", 'rest' => trim($mats[2])." ".trim($mats[3]));
		}
		return array('input' => $input, 'associatedTaxa' => "", 'rest' => "");
	}

	protected function doGenericLabel($str, $ometid="", $fields=null) {//echo "\nDoin' Generic\n";
		$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|ch\\b)|Apr(?:\\.|il\\b)|May\\b|Jun(?:\\.|e\\b)|Jul(?:\\.|y\\b)|Aug(?:\\.|(?:ust\\b))|Sep(?:\\.|(?:t\\b\\.?)|tember\\b)|Oct(?:\\.|ober\\b)|Nov(?:\\.|ember\\b)|Dec(?:\\.|ember\\b)";
		$possibleNumbers = "[OQSZl|I!0-9]";
		$str = preg_replace
		(
			array
			(
				"/,\\n([A-Za-z]{3,}(?: [A-Za-z]{3,})?[.,])\\n/s",
				"/ASPIC[I1!|].{1,3}[I1!|]A/i",
				"/Deerlodge Co\\./i",
				"/\\bC[0O].{2,4}TY: ([A-Z])/i",
				"/([A-Za-z]) Co\\. , /",
				"/([A-Za-z]) Coo, ([A-Z])/",
				"/\\b199 ([0-9]) /",
				"/DESOTO PARISH/i",
				"/swamp along/i",
				"/C- Harris\\b/i"
			) ,
			array
			(
				", \${1}\n",
				"Aspicilia",
				"Deer Lodge Co.",
				"County: \${1}",
				"\${1} Co., ",
				"\${1} Co., \${2}",
				"199\${1} ",
				"De Soto Parish",
				"swamp, along",
				"C. Harris"
			),
			$str
		);
		//echo "\nline 16243, str\n".$str."\n";
		$acceptableSmallWords = "(?:road|pine|park|tree|fork|cove|camp|fire|bay|[lb]og|soil|sand|oaks?|base|area|lane|farm|bark|twig|mats?|Ilex)";
		$recordedBy = '';
		$recordNumber = '';
		$associatedCollectors = '';
		$exsNumber = '';
		$recordedById = '';
		$identifiedBy = '';
		$dateIdentified = array();
		$eventDate = array();
		$otherCatalogNumbers = '';
		$tempRecordNumber = "";
		$taxonRank = '';
		$taxonRemarks = '';
		$infraspecificEpithet = '';
		$verbatimAttributes = '';
		$georeferenceRemarks = '';
		$occurrenceRemarks = '';
		$habitat = '';
		$scientificName = "";
		$associatedTaxa = "";
		$stateProvince = "";
		$verbatimElevation = '';
		$verbatimCoordinates = '';
		$verbatimEventDate = '';
		$country = "";
		$county = "";
		$locality = "";
		$substrate = "";
		if($fields != null) {//foreach($fields as $k => $v) echo "\nline 16265, ".$k.": ".$v."\n";
			if(array_key_exists('recordedBy', $fields)) $recordedBy = $fields['recordedBy'];
			if(array_key_exists('recordNumber', $fields)) $recordNumber = $fields['recordNumber'];
			if(array_key_exists('exsNumber', $fields)) $exsNumber = $fields['exsNumber'];
			if(array_key_exists('recordedById', $fields)) $recordedById = $fields['recordedById'];
			if(array_key_exists('identifiedBy', $fields)) $identifiedBy = $fields['identifiedBy'];
			if(array_key_exists('dateIdentified', $fields)) $dateIdentified = $fields['dateIdentified'];
			if(array_key_exists('eventDate', $fields)) $eventDate = $fields['eventDate'];
			if(array_key_exists('otherCatalogNumbers', $fields)) $otherCatalogNumbers = $fields['otherCatalogNumbers'];
			if(array_key_exists('taxonRank', $fields)) $taxonRank = $fields['taxonRank'];
			if(array_key_exists('taxonRemarks', $fields)) $taxonRemarks = $fields['taxonRemarks'];
			if(array_key_exists('infraspecificEpithet', $fields)) $infraspecificEpithet = $fields['infraspecificEpithet'];
			if(array_key_exists('verbatimAttributes', $fields)) $verbatimAttributes = $fields['verbatimAttributes'];
			if(array_key_exists('georeferenceRemarks', $fields)) $georeferenceRemarks = $fields['georeferenceRemarks'];
			if(array_key_exists('occurrenceRemarks', $fields)) $occurrenceRemarks = $fields['occurrenceRemarks'];
			if(array_key_exists('habitat', $fields)) $habitat = $fields['habitat'];
			if(array_key_exists('scientificName', $fields)) $scientificName = $fields['scientificName'];
			if(array_key_exists('associatedTaxa', $fields)) $associatedTaxa = $fields['associatedTaxa'];
			if(array_key_exists('stateProvince', $fields)) $stateProvince = $fields['stateProvince'];
			if(array_key_exists('verbatimElevation', $fields)) $verbatimElevation = $fields['verbatimElevation'];
			if(array_key_exists('country', $fields)) $country = $fields['country'];
			if(array_key_exists('county', $fields)) $county = $fields['county'];
			if(array_key_exists('locality', $fields)) $locality = $fields['locality'];
			if(array_key_exists('substrate', $fields)) $substrate = $fields['substrate'];
			if(array_key_exists('associatedCollectors', $fields)) $associatedCollectors = $fields['associatedCollectors'];
			if(array_key_exists('verbatimCoordinates', $fields)) $verbatimCoordinates = $fields['verbatimCoordinates'];
			if(array_key_exists('verbatimEventDate', $fields)) $verbatimEventDate = $fields['verbatimEventDate'];
		}
		$foundSciName = false;
		if(strlen($scientificName) <= 3) $scientificName = $this->getScientificName($str);
		if(strlen($scientificName) > 3) $foundSciName = true;
		if(strlen($associatedTaxa) <= 3) $associatedTaxa = $this->getAssociatedTaxa($str);
		//echo "\nline 13626, str\n".$str."\n";
		if(strlen($recordedBy) == 0 || strlen($identifiedBy) == 0) {
			$collectorInfo = $this->getCollector($str);
			if($collectorInfo != null) {
				if(strlen($recordedBy) == 0 && array_key_exists('collectorName', $collectorInfo)) {
					$recordedBy = str_replace(" . ", ", ", $collectorInfo['collectorName']);
					if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
				}
				if(array_key_exists('collectorNum', $collectorInfo)) {
					if($ometid) $tempRecordNumber = $this->replaceMistakenNumbers($collectorInfo['collectorNum']);
					else if(strlen($recordNumber) == 0) {
						$recordNumber = $collectorInfo['collectorNum'];
						if(strlen($recordNumber) > 2) $str = preg_replace("/(?:\\bNo[,.:]?|#)? ?".preg_quote($recordNumber, '/')."/i", "", $str);
					}
				}
				if(strlen($identifiedBy) == 0 && array_key_exists('identifiedBy', $collectorInfo)) {
					$identifiedBy = $collectorInfo['identifiedBy'];
					if(strlen($identifiedBy) > 3) $str = preg_replace("/(?:(?:Det(?:[:;,.]|ermined by)|Identifie(?:r|ed by)) )?".preg_quote($identifiedBy, '/')."[.,;: ]{0,2}/i", "\n", $str);
				}
				if(strlen($associatedCollectors) == 0 && array_key_exists('associatedCollectors', $collectorInfo)) {
					$associatedCollectors = $collectorInfo['associatedCollectors'];
					if(strlen($associatedCollectors) > 3) $str = preg_replace("/".preg_quote($associatedCollectors, '/')."/i", "\n", $str);
				}
				if(strlen($otherCatalogNumbers) == 0 && array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
			}
		}
		if(strlen($identifiedBy) == 0) {
			$identifier = $this->getIdentifier($str, $possibleMonths);
			if($identifier != null) {
				$identifiedBy = $identifier[0];
				if(strlen($identifiedBy) > 3) $str = preg_replace("/(?:(?:Det(?:[:;,.]|ermined by)|Identifie(?:r|ed by)) )?".preg_quote($identifiedBy, '/')."[.,;: ]{0,2}/i", "\n", $str);
				$dateIdentified = $identifier[1];
				$fDI = $this->formatDate($dateIdentified);
				if(strlen($fDI) > 3) $str = preg_replace("/".preg_quote($fDI, '/')."/i", "", $str);
			}
		}
		if(strlen($recordedBy) > 3) $str = preg_replace("/(?:(?:Co[l1]{2}(?:[:;,.]|ect(?:ed(?: (?:and|&) (?:dist(?:[,.]|ributed)?|prep(?:ar)?ed)) by|ors?))) |Leg[:;,.] )?".preg_quote($recordedBy, '/')."[.,;: ]{0,2}/i", "\n", $str);
		$str = trim(preg_replace("/\n{2,}/", "\n", $str));
		$lookingForRecordNumber = false;
		if(strlen($substrate) == 0) {
			$subArray = $this->getSubstrate($str);
			if($subArray) {
				$temp = trim($subArray[2]);
				if($temp && !$this->isMostlyGarbage($temp, 0.48)) {
					$substrate = $temp;
					$str = trim(str_replace(array($subArray[1], "\n\n"), "\n", $str));
				}
			}
		}
		if(strlen($verbatimElevation) == 0) {
			$elevArr = $this->getElevation($str);
			$temp = $elevArr[1];
			if($temp) {
				$verbatimElevation = $temp;
				$str = trim(trim($elevArr[0])."\n".trim($elevArr[2]));
				//echo "\nstr\n".$str."\n";
			}
		}//echo "\nline 15258, str:\n".$str."\n";
		$states = array();
		if(!$county || !$country || !$stateProvince) {
			$matches = $this->getTaxonOfHeaderInfo($str);
			if($matches != null) {
				$info = $this->processTaxonOfHeaderInfo($matches);
				if($info) {//foreach($info as $k => $v) echo "\nline 16367, ".$k.": ".$v."\n";
					if(array_key_exists('county', $info) && strlen($county) == 0) $county = $info['county'];
					if(array_key_exists('country', $info) && strlen($country) == 0) $country = $info['country'];
					if(array_key_exists('stateProvince', $info) && strlen($stateProvince) == 0) $stateProvince = $info['stateProvince'];
					if(($county || $country || $stateProvince) && array_key_exists('endOfFile', $info)) $str = $info['endOfFile'];
				}
			}
		}//echo "\nline 14633, str:\n".$str."\n";
		if(!$county || !$country || !$stateProvince) {
			$countyMatches = $this->findCounty($str, $stateProvince);
			if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\ncountyMatches[".$i++."] = ".$countyMatche."\n";
				$firstPart = trim($countyMatches[0]);
				$lastPart = trim($countyMatches[3]);
				if(strlen($county) == 0) $county = trim($countyMatches[1]);
				if(strlen($country) == 0) $country = trim($countyMatches[2]);
				$m4 = trim($countyMatches[4]);
				if(strlen($stateProvince) == 0) {
					$sp = $this->getStateOrProvince($m4);
					if($sp) {
						$stateProvince = $sp[0];
						$country = $sp[1];
					}
				}//echo "\nline 15388, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\nscientificName: ".$scientificName."\ncounty: ".$county."\nstateProvince: ".$stateProvince."\ncountry: ".$country."\n";
				if($county && (!$stateProvince || !$country)) {
					$polInfo = $this->getPolticalInfoFromCounty($county);
					if($polInfo != null ) {
					$county = ucwords
						(
							strtolower
							(
								str_replace
								(
									array('1', '!', '|', '5'. '0'),
									array('l', 'l', 'l', 'S', 'O'),
									trim($polInfo['county'])
								)
							)
						);
						if(array_key_exists('state', $polInfo)) $stateProvince = $polInfo['state'];
						if(array_key_exists('country', $polInfo)) $country = $polInfo['country'];
					}
				}
			}//echo "\nline 14698, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\nscientificName: ".$scientificName."\ncounty: ".$county."\nstateProvince: ".$stateProvince."\ncountry: ".$country."\n";
		}//echo "\nline 16139, str:\n".$str."\n";
		$lines = explode("\n", $str);
		foreach($lines as $line) {//echo "\nline 16141, line: ".$line."\ncounty: ".$county."\nstateProvince: ".$stateProvince."\ncountry: ".$country."\nlocality: ".$locality."\n";
			$line = trim($line, " ,");
			if(preg_match("/^((?:[A-Za-z]{1,12}[,.]? )?[A-Za-z]{2,})[,.:;] (?:U[,.]? ?S[,.]? ?A[,.]?|United States)(.*)$/i", $line, $mats)) {
				$match = str_replace(",", ".", trim($mats[1]));
				if($this->isUSState($match)) {
					if(strlen($stateProvince) == 0) $stateProvince = ucwords($match);
					$line = rtrim(ltrim($mats[2], " .,:;"));
				} else if(strpos($match, " ") !== FALSE) {
					$match = trim($mats[1]);
					$match = trim(substr($match, strpos($match, " ")+1));
					if($this->isUSState($match)) {
						if(strlen($stateProvince) == 0) $stateProvince = ucwords($match);
						$match = trim($mats[1]);
						$line = trim(substr($match, 0, strpos($match, " ")));
						$match = trim($mats[2]);
						if(strlen($match) > 1) $line .= " ".ltrim($match, " :;,.");
					}
				}
				if(strlen($country) == 0) $country = "United States";
			} else if(preg_match("/^(.*?)\\b((?:[A-Za-z]{1,12}[,.]? )?[A-Za-z]{2,})[,.:;] (?:U[,.]? ?S[,.]? ?A[,.]?|United States)$/i", $line, $mats)) {
				$match = str_replace(",", ".", trim($mats[2]));
				if($this->isUSState($match)) {
					if(strlen($stateProvince) == 0) $stateProvince = ucwords($match);
					$line = trim($mats[1]);
				} else if(strpos($match, " ") !== FALSE) {
					$match = trim($mats[2]);
					$match = trim(substr($match, strpos($match, " ")+1));
					if($this->isUSState($match)) {
						if(strlen($stateProvince) == 0) $stateProvince = ucwords($match);
						$match = trim($mats[2]);
						$line = trim($mats[1])." ".trim(substr($match, 0, strpos($match, " ")));
					}
				}
				if(strlen($country) == 0) $country = "United States";
			} else if(preg_match("/^(?:U[,.]? ?S[,.]? ?A[,.]?|United States)[,.:; ]{1,3}((?:[A-Za-z]{1,12}[,.]? )?[A-Za-z]{2,})[,.] (.+)/i", $line, $mats)) {
				$match = trim($mats[1]);
				if($this->isUSState($match)) {
					if(strlen($stateProvince) == 0) $stateProvince = ucwords($match);
					$line = trim($mats[2]);
				}
				if(strlen($country) == 0) $country = "United States";
			} else if(preg_match("/^((?:[A-Za-z]{1,12}[,.]? )?[A-Za-z]{2,})[,.] Canada(.*)/i", $line, $mats)) {
				$match = trim($mats[1]);
				$sp = $this->getStateOrProvince($match);
				if($sp) {
					$pc = $sp[1];
					if(strcasecmp($pc, "Canada") == 0) {
						if(strlen($stateProvince) == 0) $stateProvince = ucwords($sp[0]);
						$country = $pc;
						$line = rtrim(ltrim($mats[2], " .,:;"));
						if(preg_match("/(.+)".$stateProvince."/i", $match, $mats2)) {
							if($line) $line = trim($mats2[1]).", ".$line;
							else $locality = $this->mergeFields(trim($mats2[1]), $locality, ", ");
						}
					}
				}
			} else if(preg_match("/^Canada(?:[,.;:]?( (?:[A-Za-z]{1,3}\\. |[A-Za-z]+ )?[A-Za-z]{2,})?(?: Province| Territory)[,.;:]?)(.*)$/i", $line, $mats)) {
				if(count($mats) == 3) {
					$match = trim($mats[1]);
					$sp = $this->getStateOrProvince($match);
					if($sp) {
						$pc = $sp[1];
						if(strcasecmp($pc, "Canada") == 0) {
							$stateProvince = ucwords($sp[0]);
							$country = $pc;
							$line = rtrim(ltrim($mats[2], " .,:;"));
						}
					}
				} else {
					$country = "Canada";
					$line = rtrim(ltrim($mats[1], " .,:;"));
				}
			} else if(preg_match("/^Canada(?:[,.;:]?( (?:[A-Za-z]{1,3}\\. |[A-Za-z]+ )?[A-Za-z]{2,})?[,.;:]?)(.*)$/i", $line, $mats)) {
				if(count($mats) == 3) {
					$match = trim($mats[1]);
					$sp = $this->getStateOrProvince($match);
					if($sp) {
						$pc = $sp[1];
						if(strcasecmp($pc, "Canada") == 0) {
							$stateProvince = ucwords($sp[0]);
							$country = $pc;
							$line = rtrim(ltrim($mats[2], " .,:;"));
							if(preg_match("/".$stateProvince."(.+)/i", $match, $mats2)) {
								if($line) $line = trim($mats2[1]).", ".$line;
								else $locality = $this->mergeFields(trim($mats2[1]), $locality, " ");
							}
						}
					}
				} else {
					$country = "Canada";
					$line = rtrim(ltrim($mats[1], " .,:;"));
				}
			}//echo "\nline 15348, line: ".$line."\ncounty: ".$county."\nstateProvince: ".$stateProvince."\ncountry: ".$country."\nlocality: ".$locality."\nhabitat: ".$habitat."\nsubstrate: ".$substrate."\n";
			if(strlen($stateProvince) > 1) {
				$pat;
				if(strlen($county) > 1) {
					if(preg_match("/^Loca(?:tion|l(?:e|ity))[.,:;]? (.*)$/i", $line, $mats)) $line = trim($mats[1]);
					$temp_county = preg_quote($county, "/");
					if(strcasecmp($temp_county, "Miami Dade") == 0) $temp_county = "Dade";
					if(stripos($temp_county, "Saint ") !== FALSE) $temp_county = str_ireplace("Saint ", "S(?:ain)?t\\.? ", $temp_county);
					if(stripos($temp_county, "Sainte ") !== FALSE) $temp_county = str_ireplace("Sainte ", "S(?:ain)?te?\\.? ", $temp_county);
					$pat = "/^".$temp_county." (?:[CG][O0]UNT[V?Yi]|PAR(?:\\.|[I1!||]SH)|B[O0]R[O0]U[GC]H|D[I1!||]STR[I1!||]CT|[CG]o\\b\\.?)(?! (?:PARK|FOREST|P?RESERVE))[;:.,]?(?: ".preg_quote($stateProvince, "/")."(?! (?:River|Mountains?|Mts?\\.?|FALLS|SPRINGS?|[A-Za-z]+(?: [A-Za-z]+) (?:STATE|NATIONAL|NAT.?L\\b\\.?|PROVINCIAL|COUNT[V?Yi]) (?:PARK|FOREST|P?RESERVE))))?(.*?)$/i";
					if(preg_match($pat, $line, $mats)) {/*echo "\nline 15460, matched\n";*/$line = trim($mats[1], " ,;:");}
					else if(preg_match("/^(?:(?:(?:STATE|PROVINCE) (?:[O0]F )?|ESTAD[O0] (?:DE )?)?".preg_quote($stateProvince, "/")."[.,:;] )?".$temp_county." (?:C[O0]UNT[V?Yi]|PAR(?:\\.|[I1!||]SH)|B[O0]R[O0]U[GC]H|C[O0]\\b\\.?)?[;:.,]?\\s(.+)/i", $line, $mats)) {/*echo "\nline 16218, matched\n";*/$line = trim($mats[1], " ,;:");}
					else if(preg_match("/^(?:(?:(?:STATE|PROVINCE) (?:[O0]F )?|ESTAD[O0] (?:DE )?)?".preg_quote($stateProvince, "/")."[.,:;]?) (?:C[O0]UNT[V?Yi]|PAR(?:\\.|[I1!||]SH)|B[O0]R[O0]U[GC]H|C[O0]\\b\\.?)?[;:.,]? ".$temp_county."[;:.,]? (?:Dist(?:rict|\\.)[;:.,]? ?)?(.*?)$/i", $line, $mats)) {/*echo "\nline 16219, matched\n";*/$line = trim($mats[1], " ,;:");}
					else if(preg_match("/^(?:(?:(?:STATE|PROVINCE) (?:[O0]F )?|ESTAD[O0] (?:DE )?)".preg_quote($stateProvince, "/")."[.,:;]?) ?(.*?)$/i", $line, $mats)) {/*echo "\nline 16220, matched\n";*/$line = trim($mats[1], " ,;:");}
					else if(preg_match("/^(?:(?:(?:United States(?: [O0]. America)?|U\\.?S\\.?A\\.?)[,.] )?".preg_quote($stateProvince, "/")."[.,:; ]{1,3})?".$temp_county."(?! (?:R[I1!||]ver|.iver|Mountains?|Mts?\\.?|FALLS|SPRINGS?|Range|(?:[A-Za-z]+(?: [A-Za-z]+) )?(?:STATE|NATIONAL|NAT.?L\\b\\.?|PROVINCIAL|COUNT[V?Yi]) (?:PARK|FOREST|P?RESERVE)))(?: (?:C[O0](?:\\.|UNT[V?Yi])|D[I1!||][S5]TR[I1!||]CT|PAR(?:\\.|[I1!||]SH)|B[O0]R[O0]U[GC]H|C[O0]\\b\\.?))?[;:.,]?(.*?)$/i", $line, $mats)) {/*echo "\nline 16222, matched\n";*/$line = trim($mats[1], " ,;:");}
					else if(preg_match("/^(?:(?:(?:United States(?: [O0]. America)?|U\\.?S\\.?A\\.?)[,.] )?".preg_quote($stateProvince, "/")."[.,:; ]{1,3})?".$temp_county."[.,]? (?:C[O0](?:\\.|UNT[V?Yi])|D[I1!||][S5]TR[I1!||]CT|PAR(?:\\.|[I1!||]SH)|B[O0]R[O0]U[GC]H|C[O0]\\b\\.?)[;:.,]?(.*)$/i", $line, $mats)) {/*$i=0;foreach($mats as $mat) echo "\nline 16221, mats[".$i++."] = ".$mat."\n";*/$line = trim($mats[1], " ,;:");}
					//else if(preg_match("/^(?:Canada[.,] ".preg_quote($stateProvince, "/")."[.,:;] )?".$temp_county." (?:C[O0](\\.|UNT[V?Yi])|PAR(?:\\.|[I1!||]SH)|B[O0]R[O0]U[GC]H|C[O0]\\b\\.?)?[;:.,]? (.+?)$/i", $line, $mats)) {/*echo "\nline 16223, matched\n";*/$line = trim($mats[1], " ,;:");}
					else if(preg_match("/^(?:Canada[.,:] (?:".preg_quote($stateProvince, "/")."[.,:;] )?)?".$temp_county."(?: (?:C[O0]UNT[V?Yi]|PAR(?:\\.|[I1!||]SH)|B[O0]R[O0]U[GC]H|C[O0]\\b\\.?))[;:.,]? (.+?)$/i", $line, $mats)) {/*echo "\nline 16224, matched\n";*/$line = trim($mats[1], " ,;:");}
					else if(preg_match("/^(?:C[O0]UNT[V?Yi]|PAR(?:\\.|[I1!||]SH)|B[O0]R[O0]U[GC]H|D[I1!||]STR[I1!||]CT)[;:.,*]? ".$temp_county."[;:.,]?\\s?(.*)/i", $line, $mats)) {/*echo "\nline 16225, matched\n";*/$line = trim($mats[1], " ,;:");}
					else if(preg_match("/^.{0,6}".preg_quote($stateProvince, "/")."[;:.,]{0,2} ".$temp_county." (?:C[O0]UNT[V?Yi]|PAR(?:\\.|[I1!||]SH)|B[O0]R[O0]U[GC]H|D[I1!||]STR[I1!||]CT|C[O0]\\b\\.?)[;:.,]?(.*)/i", $line, $mats)) {/*echo "\nline 16226, matched\n";*/$line = trim($mats[1], " ,;:");}
					else if(preg_match("/^.{0,6}".preg_quote($stateProvince, "/")."[;:.,]{1,2} (?!(?:River|Mountains?|Mts?\\.?|FALLS|SPRINGS?|[A-Za-z]+(?: [A-Za-z]+) (?:STATE|NATIONAL|NAT.?L\\b\\.?|PROVINCIAL|COUNT[V?Yi]) (?:PARK|FOREST|P?RESERVE)))(.+)/i", $line, $mats)) {/*echo "\nline 16226, matched\n";*/$line = trim($mats[1], " ,;:");}
					else if(preg_match("/^(.+)\\b".preg_quote($temp_county, "/")." (?:[CG][O0](?:[,.]|UNT[V?Yi])|PAR(?:\\.|[I1!||]SH)|B[O0]R[O0]U[GC]H|D[I1!||]STR[I1!||]CT)[;:.,]?(?: ".preg_quote($stateProvince, '/').")?.?$/i", $line, $mats)) {/*echo "\nline 16228, matched\n";*/$line = trim($mats[1], " ,;:");}
					else if(preg_match("/^.{0,6}".$temp_county."[;:.,]? (?!(?:River|Mountains?|Mts?\\.?|FALLS|SPRINGS?|(?:[A-Za-z]+(?: [A-Za-z]+) )?(?:STATE|NATIONAL|NAT.?L\\b\\.?|PROVINCIAL|COUNT[V?Yi]) (?:PARK|FOREST|P?RESERVE)))(?:C[O0](?:[,.]|UNT[V?Yi])|PAR(?:\\.|[I1!||]SH)|B[O0]R[O0]U[GC]H|D[I1!||]ST(?:\\.|R[I1!||]CT))[;:.,]?(.+)/i", $line, $mats)) {/*echo "\nline 16229, matched\n";*/$line = trim($mats[1], " ,;:");}
					$pat = "/^(.*?)(?: UNIVERSITY\\b|\\bC[O0]LL(?:\\.|ected by)|\\bHERBARIUM\\b|\\bDET(?:\\.|ermined)|\\bIdentified\\b|\\bNew\\s?Y[o0]rk\\s?B[o0]tan[1!il|]cal\\s?Garden\\b|\\bDate\\b|\\b[O|!lI0-9]{1,2}[- ](?:".$possibleMonths.")[- ][O|!lI0-9]{2,4}+| ?\\b(?:".$possibleMonths.")(?:[- ][O|!lI0-9]{1,2})?[, -][O|!lI0-9]{2,4}+)(.*)$/i";
				} else {
					if(preg_match("/^.{0,6}(?:".preg_quote($stateProvince, "/")."(?! (?:River|Mountains?|Mts?\\.?|FALLS|SPRINGS?|Range|[A-Za-z]+(?: [A-Za-z]+) (?:STATE|NATIONAL|NAT.?L\\b\\.?|PROVINCIAL|COUNT[V?Yi]) (?:PARK|FOREST|P?RESERVE))))(.*?)$/i", $line, $mats)) $line = trim($mats[1], " ,;:");
					else if(preg_match("/^(?:(?:STATE [O0]F |ESTAD[O0] (?:DE )?)".preg_quote($stateProvince, "/")."[.,:;])\\s(.+)/i", $line, $mats)) {/*$i=0;foreach($mats as $mat) echo "\nline 14211, mats[".$i++."] = ".$mat."\n";*/$line = trim($mats[1], " ,;:");}
					$pat = "/^(.*?)\\b(?:UNIVERSITY\\b|C[O0]LL(?:\\.|ected by)|HERBARIUM\\b|DET(?:\\.|ermined)|Identified\\b|New\\s?Y[o0]rk\\s?B[o0]tan[1!il|]cal\\s?Garden\\b|Date\\b|\\b[O|!lI0-9]{1,2}[- ](?:".$possibleMonths.")[- ][O|!lI0-9]{2,4}+| ?\\b(?:".$possibleMonths.")(?:[- ][O|!lI0-9]{1,2})?[, -][O|!lI0-9]{2,4}+)(.*)$/i";
				}
				if(strlen($pat) > 1 && preg_match($pat, $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 16428, mats[".$i++."] = ".$mat."\n";
					$temp = trim($mats[1], " ,;:");
					if($temp) $line = $temp;
					else if(count($mats) > 2) $line = trim($mats[2], " ,;:");
					if(preg_match($pat, $line, $mats)) {
						$temp = trim($mats[1], " ,;:");
						if($temp) $line = $temp;
						else if(count($mats) > 2) $line = trim($mats[2], " ,;:");
					}
				}
			} else if(strlen($county) > 1) {
				$temp_county = preg_quote($county, "/");
				if(stripos($temp_county, "Saint ") !== FALSE) $temp_county = str_ireplace("Saint ", "S(?:ain)?t\\.? ", $temp_county);
				if(stripos($temp_county, "Sainte ") !== FALSE) $temp_county = str_ireplace("Sainte ", "S(?:ain)?te?\\.? ", $temp_county);
				$pat = "/^".$temp_county." (?:C[O0]UNT[V?Yi]|PAR(?:\\.|[I1!||]SH)|B[O0]R[O0]U[GC]H|D[I1!||]STR[I1!||]CT|Co\\b\\.?)(?! (?:PARK|FOREST|P?RESERVE))[;:.,]?(.*?)$/i";
				if(preg_match($pat, $line, $mats)) {/*echo "\nline 13776, matched\n";*/$line = trim($mats[1], " ,;:");}
			} else if(preg_match("/^(U\\.?[S5]\\.?[S5]\\.?R\\.?) (.*)$/", $line, $mats)) {
				$country = str_replace("5", "S", $mats[1]);
				$line = trim($mats[2]);
			} else if(preg_match("/^Mexico\\b[.,:;]? (.*)$/i", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13811, mats[".$i++."] = ".$mat."\n";
				$country = "Mexico";
				$line = trim($mats[1]);
				if(preg_match("/^(?:STATE [O0]F |ESTAD[O0] (?:DE )?)(.+)/i", $line, $mats2)) $line = trim($mats2[1]);
				if(strlen($line) > 3) {
					$sp = $this->getStateOrProvince($line);
					if($sp) {
						if(strlen($stateProvince) == 0) $stateProvince = $sp[0];
						$line = ltrim(rtrim(substr($line, stripos($line, $stateProvince)+strlen($stateProvince))), " ;:.,");
					}
				}
			} else if(preg_match("/^([A-Za-z]{4,}(?: [A-Za-z]{4,}(?: [A-Za-z]{4,})?)?)\\. (([A-Za-z]{4,}(?: [A-Za-z]{3,}(?: [A-Za-z]{4,})?)?): ?(.*))$/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13842, mats[".$i++."] = ".$mat."\n";
				$temp = trim($mats[1]);
				if(preg_match("/^(?:(?:PROVINCE|STATE) [O0]F |ESTAD[O0] (?:DE )?)(.+)/i", $temp, $mats2)) $temp = trim($mats2[1]);
				if(strlen($temp) > 3 && str_word_count($temp) < 4) {
					$sp = $this->getStateOrProvince($temp);
					if($sp) {
						if(!$stateProvince) $stateProvince = $sp[0];
						if(!$country) $country = $sp[1];
						if($country) {
							$temp = trim($mats[3]);
							$cMatches = $this->getCounty($temp, $stateProvince);
							if($cMatches && array_key_exists('county', $cMatches)) {
								$county = $cMatches['county'];
								if(strlen(country) == 0 && array_key_exists('country', $cMatches)) $country = $cMatches['country'];
								$line = trim($mats[4]);
							} else $line = trim($mats[2]);
						} else $line = trim($mats[2]);
					} else if($country) {
						$states = $this->getStatesFromCountry($country);
						foreach($states as $state) {
							if(strcasecmp($state, $temp) == 0) {
								$stateProvince = $temp;
								$temp = trim($mats[3]);
								$cMatches = $this->getCounty($temp, $stateProvince);
								if($cMatches && array_key_exists('county', $cMatches)) {
									$county = $cMatches['county'];
									if(strlen(country) == 0 && array_key_exists('country', $cMatches)) $country = $cMatches['country'];
									$line = trim($mats[4]);
								} else $line = trim($mats[2]);
								break;
							}
						}
					} else {
						$country = $this->getCountry($temp);
						if($country) {
							$temp = trim($mats[3]);//echo "\nline 13866, temp: ".$temp."\n";
							$states = $this->getStatesFromCountry($country);
							foreach($states as $state) {
								if(strcasecmp($state, $temp) == 0) {
									$stateProvince = $temp;
									$line = trim($mats[4], " .;:");
									break;
								}
							}
							if(strlen($stateProvince) == 0) $line = trim($mats[2], " .;:");
						}
					}
				}
			} else if(preg_match("/^(.+?):(.*)$/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13890, mats[".$i++."] = ".$mat."\n";
				$temp = trim($mats[1]);
				if(preg_match("/^(?:(?:PROVINCE|STATE) [O0]F |ESTAD[O0] (?:DE )?)(.+)/i", $temp, $mats2)) $temp = trim($mats2[1]);
				if(strlen($temp) > 3 && str_word_count($temp) < 4) {
					$sp = $this->getStateOrProvince($temp);
					if($sp) {
						if(!$stateProvince) $stateProvince = $sp[0];
						if(!$country) $country = $sp[1];
						$line = trim($mats[2]);
					} else if($country) {
						$states = $this->getStatesFromCountry($country);
						foreach($states as $state) if(strcasecmp($state, $temp) == 0) {
							$stateProvince = $temp;
							$line = trim($mats[2]);
						}
					} else {
						$country = $this->getCountry($temp);
						if($country) {
							$line = trim($mats[2], " .;:");
							if(preg_match("/^(.+?[A-Za-z]{4,})[:;,.](.*+)$/", $line, $mats2)) {
								$temp = trim($mats2[1]);
								if(preg_match("/^(.+[A-Za-z]{4,})[:;,.](.*)/", $temp, $mats3)) {
									$temp = trim($mats3[1]);
									if(str_word_count($temp) < 4) {
										$sp = $this->getStateOrProvince($temp);
										if($sp) {
											if(strlen($stateProvince) == 0) $stateProvince = $sp[0];
											$line = trim($mats3[2]);
										}
									}
								} else if(str_word_count($temp) < 4) {
									$sp = $this->getStateOrProvince($temp);
									if($sp) {
										if(strlen($stateProvince) == 0) $stateProvince = $sp[0];
										$line = trim($mats2[2]);
									}
								}
							}
						}
					}
				}
			} else if(preg_match("/^([A-Za-z]{4,}(?: [A-Za-z]{4,}(?: [A-Za-z]{4,})?)?)\\. (.*)$/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13931, mats[".$i++."] = ".$mat."\n";
				$temp = trim($mats[1]);
				if(preg_match("/^(?:(?:PROVINCE|STATE) [O0]F |ESTAD[O0] (?:DE )?)(.+)/i", $temp, $mats2)) $temp = trim($mats2[1]);
				if(strlen($temp) > 3 && str_word_count($temp) < 4) {
					$sp = $this->getStateOrProvince($temp);
					if($sp) {
						if(!$stateProvince) $stateProvince = $sp[0];
						if(!$country) $country = $sp[1];
						$line = trim($mats[2]);
					} else if($country) {
						$states = $this->getStatesFromCountry($country);
						foreach($states as $state) if(strcasecmp($state, $temp) == 0) {
							$stateProvince = $temp;
							$line = trim($mats[2]);
						}
					} else {
						$country = $this->getCountry($temp);
						if($country) {
							$line = trim($mats[2], " .;:");
							if(preg_match("/^(.+?[A-Za-z]{4,})[,.] (.*+)$/", $line, $mats2)) {
								$temp = trim($mats2[1]);
								if(preg_match("/^(.+[A-Za-z]{4,})[:;](.*)/", $temp, $mats3)) {
									$temp = trim($mats3[1]);
									if(str_word_count($temp) < 4) {
										$sp = $this->getStateOrProvince($temp);
										if($sp) {
											if(!$stateProvince) $stateProvince = $sp[0];
											$line = trim($mats3[2]);
										}
									}
								} else if(str_word_count($temp) < 4) {
									$sp = $this->getStateOrProvince($temp);
									if($sp) {
										if(!$stateProvince) $stateProvince = $sp[0];
										$line = trim($mats2[2]);
									}
								}
							}
						}
					}
				}
			} else {
				$pat = "/(.*)\\b(?:UNIVERSITY|COLL(?:\\.|ect(?:ed)?)|HERBARIUM|DET(?:\\.|ermined)|Identified|New\\s?Y[o0]rk\\s?B[o0]tan[1!il|]cal\\s?Garden|Date|".preg_quote($possibleMonths, "/").")\\b(.*)/i";
				if(preg_match($pat, $line, $mats)) {
					$temp = trim($mats[1], " ,;:");
					if(strlen($temp) > 2) $line = $temp;
					else $line = trim($mats[2], " ,;:");
					if(preg_match($pat, $line, $mats)) {
						$temp = trim($mats[1], " ,;:");
						if(strlen($temp) > 2) $line = $temp;
						else $line = trim($mats[2], " ,;:");
					}
				}
			}//echo "\nline 15549, line: ".$line."\nlocality: ".$locality."\nhabitat: ".$habitat."\nsubstrate: ".$substrate."\n";
			$firstPart = "";
			$secondPart = "";
			//the following is intended to remove LatLongs or LongLats -- even malformed ones
			if(preg_match("/^([^�]*?)(?i:(?:Near|ca\\.?|Approx(?:[,.]|imately)?) (?-i))?(?i:Lat(?:\\.|itude)(?-i)[:;, ]{1,3})?".
				"\\b(?-i:(?:[1Il][0-9!l|IO]{2}|[1-9!l|I][0-9!l|IO]?)(?:[,.][0-9!l|IO]{1,6})? ?�".
				"(?: ?[0-9!l|I]{1,3}+(?:[,.][0-9!l|I]{1,3})? ?'(?: ?[0-9!l|I]{1,3}+ ?\")?)? ?".
				"(?i:N(?:[.,; ]|orth\\b)|S(?:[.,; ]|outh\\b))[.,; ]?(?-i:(?:[1Il][0-9!l|IO]{2}|[1-9!l|I][0-9!l|IO]?)(?:[,.][0-9!l|IO]{1,6})? ?�".
				"(?: ?[0-9!l|I]{1,3}+(?:[,.][0-9!l|I]{1,3})? ?'(?: ?[0-9!l|I]{1,3}+ ?\")?)? ?".
				"(?i:E(?:[.,;]|ast\\b)|W(?:[.,;]|est\\b))))(?: Long(?:[.,]|itude)?)?(.*)$/i", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 14605, mats[".$i++."] = ".$mat."\n";
				$firstPart = trim($mats[1]);
				$secondPart = trim($mats[2]);
				if(strlen($secondPart) == 0) $line = $firstPart;
			} else if(preg_match("/^([^�]*?)(?i:(?:Near|ca\\.?|Approx(?:[,.]|imately)?) (?-i))?(?i:Lat(?:\\.|itude)(?-i)[:;, ]{1,3})?(?<! [STR] )".
				"\\b(?-i:(?:[1Il][0-9!l|IO]{2}|[1-9!l|I][0-9!l|IO]?) ?[�\"' ]".
				"(?: ?[0-9!l|I]{1,3}+(?:[,.][0-9!l|I]{1,3})? ?['� ](?: ?[0-9!l|I]{1,3}+ ?\")?) ?".
				"(?i:N(?:[.,; ](?![EW]\\.)|orth\\b)|S(?:[.,; ](?![EW]\\.)|outh\\b)|E(?:[.,; ]|ast\\b)|W(?:[.,; ]|est\\b))(?! ?(?:of|from) )[.,; ]?)(.*)$/i", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13974, mats[".$i++."] = ".$mat."\n";
				$firstPart = trim($mats[1]);
				$secondPart = trim($mats[2]);
			} else if(preg_match("/^([^�]*?)(?i:(?:Near|ca\\.?|Approx(?:[,.]|imately)?) (?-i))?(?i:Lat(?:\\.|itude)(?-i)[:;, ]{1,3})?(?<! [STR] )".
				"\\b(?-i:(?:[1Il][0-9!l|IO]{2}|[1-9!l|I][0-9!l|IO]?)(?:[,.][0-9!l|IO]{1,6}) ?[�\"' ] ?".
				"(?i:N(?:[.,; ](?![EW]\\.)|orth\\b)|S(?:[.,; ](?![EW]\\.)|outh\\b)|E(?:[.,; ]|ast\\b)|W(?:[.,; ]|est\\b))(?! ?(?:of|from) )[.,; ]?)(.*)$/i", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13980, mats[".$i++."] = ".$mat."\n";
				$firstPart = trim($mats[1]);
				$secondPart = trim($mats[2]);
			} else if(preg_match("/^([^�']*?)\\b(?i:(?:Near|ca\\.?|Approx(?:[,.]|imately)?) (?-i))?(?i:Lat(?:\\.|itude)(?-i)[:;, ]{1,3})?(?<! [STR] )".
				"(?:(?:[1Il][0-9!l|IO]{2}|[1-9!l|I][0-9!l|IO]?) ?[�\"' ](?: ?[0-9!l|I]{1,3} ?[�\"']".
				"(?: ?[0-9!l|I]{1,3} ?[�\"' ])?)? ?(?:N(?:[.,;](?![EW]\\.)|orth\\b)?|S(?:[.,;](?![EW]\\.)|outh\\b)?|E(?:[.,;]|ast\\b)?|W(?:[.,;]|est\\b)?)(?! ?\\b(?:of|from) ))[.,; ](.+)/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 14616, mats[".$i++."] = ".$mat."\n";
				$firstPart = trim($mats[1]);
				$secondPart = rtrim(ltrim($mats[2], " \"';:,."));
			} else if(preg_match("/^([^�']*?)\\b[NS](?:[1-9!l|I][0-9!l|IO]?) ?[�\"' ](?: ?[0-9!l|I]{1,2} ?[�\"']".
				"(?: ?[0-9!l|I]{1,2}(?:[,.][1-9])? ?[�\"' ])?)? ?(?:\(WGS\\d{2,3}\))?[.,; ](.+)/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 14620, mats[".$i++."] = ".$mat."\n";
				$firstPart = trim($mats[1]);
				$secondPart = rtrim(ltrim($mats[2], " \"';:,."));
			}//echo "\nline 13967, firstPart: ".$firstPart."\nsecondPart: ".$secondPart."\n";
			if(preg_match("/^(?:lat|long)itude$/i", $secondPart)) {
				$secondPart = "";
				$line = $firstPart;
			}//echo "\nline 16471, line: ".$line."\nfirstPart: ".$firstPart."\nsecondPart: ".$secondPart."\n";
			if($secondPart) {
				if(preg_match("/\\b(?:[1Il][0-9!l|IO]{1,2}|[1-9!l|I][0-9!l|IO]?)(?:[,.][0-9!l|I]{1,6})? ?�(?:\\s?[0-9!l|I]{1,3}".
					"(?:[,.][0-9!l|I]{1,6})? ?[' ](?:\\s?[0-9!l|I]{1,2}(?:[,.][1-9!l|I]?)?[\" ]?)?)?+ ?".
					"(?:N(?:[,.]|orth)?|S(?:[,.]|outh)?|E(?:[,.]|ast)?|W(?:[,.]|est)?|5(?:[,.]|outh)?)[,.]?(?:\\sL(?i)(?:at|ong)(?:\\.|itude)(?-i))?[;.:]?(.*?)$/", $secondPart, $mats2)) {//$i=0;foreach($mats2 as $mat) echo "\nline 16475, mats2[".$i++."] = ".$mat."\n";
					$secondPart = trim($mats2[1]);
				} else if(preg_match("/\\b(?:[1Il][0-9!l|IO]{1,2}|[1-9!l|I][0-9!l|IO]?)(?:[,.][0-9!l|I]{1,6})? ?[� ](?:\\s?[0-9!l|I]{1,3}".
					"(?:[,.][0-9!l|I]{1,6})? ?[' ](?:\\s?[0-9!l|I]{1,2}(?:[,.][1-9!l|I]?)?[\" ]?)?)?+ ?".
					"(?:N(?:[,.]|orth)?|S(?:[,.]|outh)?|E(?:[,.]|ast)?|W(?:[,.]|est)?|5(?:[,.]|outh)?)[,.]?(?:\\sL(?i)ong(?:\\.|itude)(?-i))?[;.:]?(.*?)$/", $secondPart, $mats2)) {//$i=0;foreach($mats2 as $mat) echo "\nline 16479, mats2[".$i++."] = ".$mat."\n";
					$secondPart = trim($mats2[1]);
				} else if(preg_match("/\\b[EW](?:[1Il][0-9!l|IO]{1,2}|[1-9!l|I][0-9!l|IO]?)(?:[,.][0-9!l|I]{1,6})? ?[� ]".
					"(?:\\s?[0-9!l|I]{1,2}(?:[,.][0-9!l|I]{1,6})? ?[' ]".
					"(?:\\s?[0-9!l|I]{1,2}(?:[,.][0-9!l|I]?)? ?[\" ]?+)?)? ?(?:\(WGS\\d{2,3}\))?(.*?)$/", $secondPart, $mats2)) {//$i=0;foreach($mats2 as $mat) echo "\nline 16483, mats2[".$i++."] = ".$mat."\n";
					$secondPart = trim($mats2[1]);
				}
				if($firstPart) $line = rtrim(trim($firstPart, " ,.;").";;; ".ltrim($secondPart, " ,.;"), " ;");
				else $line = trim($secondPart, " ,.;");
			} else if(preg_match("/([^�]*?)\\b(?i:(?:Near|ca\\.?|Approx(?:[,.]|imately)?) (?-i))?(?:Lat(?:\\.|itude)[:;,]?)?[NS]".
				"[1-9!l|I][0-9!l|IO]?(?:[,.][0-9!l|I]{1,6})? ?�(?: ?[0-9!l|IO]{1,2}(?:[,.][0-9!l|IO]{1,3})? ?[\"\'](?: ?[0-9!l|IO]{1,2} ?[\"\'])?)?(.+)/i", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 14555, mats[".$i++."] = ".$mat."\n";
				$firstPart = trim($mats[1]);
				$secondPart = trim($mats[2]);
				if(preg_match("/[EW](?:[1Il][0-9!l|IO]{1,2}|[1-9!l|I][0-9!l|IO]?)(?:[,.] ?[0-9!l|I]{1,6})? ?�(?:\\s?[0-9!l|I]{1,2}(?:[,.][0-9!l|I]{1,6})? ?'".
					"(?:\\s?[0-9!l|I]{1,2}(?:[,.][1-9!l|I]?)? ?\")?)?\\s?(?:\\sLong(?:\\.|itude))?(?:\(WGS\\d{2,3}\))?[;.]?(.*?)$/i", $secondPart, $mats2)) $secondPart = trim($mats2[1]);
				else if(preg_match("/\\b[EW](?:[1Il][0-9!l|IO]{1,2}|[1-9!l|I][0-9!l|IO]?)(?:[,.][0-9!l|I]{1,6})? ?�(?:\\s?[0-9!l|I]{1,2}(?:[,.][0-9!l|I]{1,6})? ?'".
					"(?:\\s?[0-9!l|I]{1,2}(?:[,.][1-9!l|I]?)? ?\")?)?\\s?(?:\\sLong(?:\\.|itude))?(?:\(WGS\\d{2,3}\))?[;.]?(.*?)$/i", $secondPart, $mats2)) $secondPart = trim($mats2[1]);
				$line = trim(trim($firstPart, " ,.;").";;; ".ltrim($secondPart, " ,.;"), " ;");
			} else if($verbatimElevation) {
				if(preg_match("/(.+)\\b".preg_quote($verbatimElevation, "/")."(.+)/i", $line, $mats)) {
					$firstPart = trim($mats[1], " ,.;");
					$secondPart = ltrim($mats[2], " ,.;");
					if(preg_match("/(.+)\\b(?:elev(?:\\.|ation)?|alt(?:\\.|itude)?)/i", $firstPart, $mats2)) $firstPart = trim(rtrim($mats2[1], "("), " ,.;:");
					else if(preg_match("/(?:elev(?:\\.|ation)?|alt(?:\\.|itude)?)(.+)/i", $secondPart, $mats2)) $secondPart = trim(ltrim($mats2[1], ")"), " ,.;:");
					$line = trim($firstPart.";;; ".$secondPart, " ;");
				}
			}// else if($firstPart) $line = $firstPart;
			//echo "\nline 16506, line: ".$line."\n";
			$trsPatStr = "/(?(?=(?:.*)\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)".
				"R(?:\\.|ange)?\\s?(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".
				$possibleNumbers."{1,3})\\b)".
			//if the condition is true then the form is TRS
				"(.*)\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(?:".
				$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3})\\b(.+)|".
			//else the form is STR
				"(.*)\\b[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3}),?(?:\\s|\\n|\\r\\n)T(?:\\.|wnshp.?|ownship)?\\s?(?:".
				$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?\\b(.+))/is";
			if(preg_match($trsPatStr, $line, $trsMatches)) {//$i=0;foreach($trsMatches as $trsMatche) echo "\nline 12469, trsMatches[".$i++."] = ".$trsMatche."\n";
				if(count($trsMatches) > 4) $line = trim(trim($trsMatches[3], " ,.;").". ".ltrim($trsMatches[4], " ,.;"));
				else $line = trim(trim($trsMatches[1], " ,.;").". ".ltrim($trsMatches[2], " ,.;"));
			}
			$utmPatStr = "/(.*)\\b(?:UTM:?(?:\\s|\\n|\\r\\n)(?:Zone\\s)?(?:".$possibleNumbers."{1,2})(?:\\w?(?:\\s|\\n|\\r\\n))(?:".
				$possibleNumbers."{1,8}E?(?:\\s|\\n|\\r\\n)".$possibleNumbers."{1,8}N?))\\b(.*)/is";
			if(preg_match($utmPatStr, $line, $locMatches)) {
				$line = trim(trim($locMatches[1], " ,.;").". ".ltrim($locMatches[2], " ,.;"));
			}
			if(!$foundSciName) {
				$psn = $this->processSciName(trim(preg_replace(array("/\\bAnnotation(?: label)?[:;., ]?/i", "/ cf[.,]/"), "", $line), " \"\',"));
				if($psn != null) {//foreach($psn as $k => $v) echo "\nline 16692, ".$k.": ".$v."\n";
					if(array_key_exists('scientificName', $psn)) {
						$scientificName = $psn['scientificName'];
						$line2 = trim(preg_replace("/".preg_quote($scientificName, '/')."/i", "", $line));
						if(strcasecmp($line2, $line) == 0) {
							$words = array_reverse(explode(" ", $scientificName));
							if(count($words) > 1) {
								$word = $words[0];
								$wLength = strlen($word);
								if($wLength > 2) {
									$pos = stripos($line, $word);
									if($pos !== FALSE) $line = trim($this->removeAuthority(substr($line, $pos+$wLength), $scientificName));
								}
							}
						} else $line = $this->removeAuthority($line2, $scientificName);
					}
					if(array_key_exists('infraspecificEpithet', $psn)) {
						$infraspecificEpithet = $psn['infraspecificEpithet'];
						$line = trim($this->removeAuthority(preg_replace("/".preg_quote($infraspecificEpithet, '/')."/i", "", $line), $scientificName));
						if(array_key_exists('taxonRank', $psn)) {
							$taxonRank = $psn['taxonRank'];
							$line = trim($this->removeAuthority(preg_replace("/".preg_quote($taxonRank, '/')."/i", "", $line), $scientificName));
						}
					}
					if(array_key_exists('verbatimAttributes', $psn)) {
						$temp = $psn['verbatimAttributes'];
						$line = trim($this->removeAuthority(preg_replace("/".preg_quote($temp, '/')."/i", "", $line), $scientificName));
						$verbatimAttributes = $this->mergeFields($verbatimAttributes, $temp);
					}
					if(array_key_exists('associatedTaxa', $psn)) {
						$temp = $psn['associatedTaxa'];
						$line = trim($this->removeAuthority(preg_replace("/".preg_quote($temp, '/')."/i", "", $line), $scientificName));
						$associatedTaxa = $this->mergeFields($associatedTaxa, $temp);
					}
					if(array_key_exists('recordNumber', $psn)) {
						$temp = $psn['recordNumber'];
						if($ometid) {
							$exsNumber = $this->replaceMistakenNumbers($temp);
							$line = trim(ltrim(preg_replace("/(?:\\bNo\\.? ?)?".preg_quote($temp, '/')."/i", "", $line), " :;,."));
						} else if(strlen($recordNumber) == 0) {
							$recordNumber = $this->replaceMistakenNumbers($temp);
							$line = trim(ltrim(preg_replace("/(?:\\bNo\\.? ?)?".preg_quote($temp, '/')."/i", "", $line), " :;,."));
						}
					}
					if(array_key_exists('taxonRemarks', $psn)) {
						$temp = $psn['taxonRemarks'];
						$line = trim($this->removeAuthority(preg_replace("/".preg_quote($temp, '/')."/i", "", $line), $scientificName));
						$taxonRemarks = $this->mergeFields($taxonRemarks, $temp);
					}
					if(array_key_exists('substrate', $psn)) {
						$temp = $psn['substrate'];
						$line = trim($this->removeAuthority(preg_replace("/".preg_quote($temp, '/')."/i", "", $line), $scientificName));
						$substrate = $this->mergeFields($substrate, $temp);
					}
					$line = ltrim($this->removeAuthority($line, $scientificName), " .,:;");
					$foundSciName = true;
				}
			}//echo "\nline 15699, recordNumber: ".$recordNumber."\nline: ".$line."\ncountPotentialLocalityWords(line): ".$this->countPotentialLocalityWords($line)."\ncountPotentialHabitatWords(line): ".$this->countPotentialHabitatWords($line)."\n";
			if($ometid) {
				if(strlen($exsNumber) == 0) {
					if(preg_match("/^No\\.:?(.*)/", $line, $mats)) {
						$temp = trim($mats[1]);
						if(strlen($temp) == 0) $lookingForRecordNumber = true;
						else {
							$spacePos = strpos($temp, " ");
							if($spacePos !== FALSE) $temp = substr($temp, 0, $spacePos);
							if($this->containsNumber($temp)) $exsNumber = $this->replaceMistakenNumbers($temp);
						}
					} else if($lookingForRecordNumber) {
						$lookingForRecordNumber = false;
						$spacePos = strpos($line, " ");
						if($spacePos !== FALSE) $temp = substr($temp, 0, $spacePos);
						else $temp = $line;
						if($this->containsNumber($temp)) {
							$exsNumber = $this->replaceMistakenNumbers($temp);
							$line = trim(substr($line, 0, strpos($line, $temp)+strlen($temp)));
						}
					}
				}
			} else if(strlen($recordNumber) == 0) {
				if(preg_match("/^No\\.:?(.*)/", $line, $mats)) {
					$temp = trim($mats[1]);
					if(strlen($temp) == 0) $lookingForRecordNumber = true;
					else {
						$spacePos = strpos($temp, " ");
						if($spacePos !== FALSE) $temp = substr($temp, 0, $spacePos);
						if($this->containsNumber($temp)) $recordNumber = $temp;
					}
				} else if(preg_match("/^# ?(.*)/", $line, $mats)) {
					$temp = trim($mats[1]);
					if(strlen($temp) == 0) $lookingForRecordNumber = true;
					else {
						$spacePos = strpos($temp, " ");
						if($spacePos !== FALSE) $temp = substr($temp, 0, $spacePos);
						if($this->containsNumber($temp)) $recordNumber = $temp;
					}
				} else if($lookingForRecordNumber) {
					$lookingForRecordNumber = false;
					$spacePos = strpos($line, " ");
					if($spacePos !== FALSE) $temp = substr($temp, 0, $spacePos);
					else $temp = $line;
					if($this->containsNumber($temp)) {
						$recordNumber = $temp;
						$line = trim(substr($line, 0, strpos($line, $temp)+strlen($temp)));
					}
				}
			}
			$foundGeoRef = false;
			if(preg_match("/(.*)\\b[QO0G]uad\\.?[ \\/](?:M|H|IX|[Jfli1!|]{2})[ae]p[;:., ]{1,3}(.{2,})/i", $line, $mat)) {
				$georeferenceRemarks = "Quad Map: ".$mat[2];
				$line = trim($mat[1]);
				$foundGeoRef = true;
			} else if(preg_match("/(.*)\\b(?:M|H|IX|[Jfli1!|]{2})ap ?\/ ?[QO0G]ua[ad][;:., ]{1,3}(.{2,})/i", $line, $mats)) {
				$georeferenceRemarks = "Quad Map: ".trim($mats[2]);
				$line = trim($mats[1]);
				$foundGeoRef = true;
			}//echo "\nline 14166, line: ".$line."\ngeoreferenceRemarks: ".$georeferenceRemarks."\n";
			if($foundGeoRef) {
				if(preg_match("/^(.+\([A-Z])(.{0,6}\))(.*)$/i", $georeferenceRemarks, $mats)) {
					$georeferenceRemarks = $mats[1].$this->replaceMistakenNumbers($mats[2]);
					if(count($mats) > 3) $line = trim($line." ".trim($mats[3]));
				}
				if(preg_match("/^(.{3,})[0ODGQ]uad\\.?(.*)/i", $georeferenceRemarks, $mat)) {
					$georeferenceRemarks = trim($mat[1]);
					if(count($mats) > 2) $line = trim($line." ".trim($mats[2]));
				}
				if(preg_match("/(.+) ((?:Date|Elev(?:ation)?|Co(?:ounty|[li1!|]{2}ect(?:[0o]rs?|ed)))?[;:,. ].*)/i", $georeferenceRemarks, $mat)) {
					$georeferenceRemarks = trim($mat[1]);
					if(count($mats) > 2) $line = trim($line." ".trim($mats[2]));
				} else if(preg_match("/(.+)(".$possibleNumbers."{1,2}[ -]".$possibleMonths."[ -]".$possibleNumbers."{4}.*)/i", $georeferenceRemarks, $mat)) {
					$georeferenceRemarks = trim($mat[1]);
					if(count($mats) > 2) $line = trim($line." ".trim($mats[2]));
				} else if(preg_match("/(.+) (".$possibleMonths."[ -]".$possibleNumbers."{1,2}.*)/i", $georeferenceRemarks, $mat)) {
					$georeferenceRemarks = trim($mat[1]);
					if(count($mats) > 2) $line = trim($line." ".trim($mats[2]));
				} else if(preg_match("/(.+), (.+)/i", $georeferenceRemarks, $mat)) {
					$georeferenceRemarks = trim($mat[1]);
					if(count($mats) > 2) $line = trim($line." ".trim($mats[2]));
				}
			} else if(preg_match("/^(.*?)(([A-Za-z]+\\.?(?: [A-Za-z]+\\.?)? ?\(.+\)) ?[QO0G]ua[ad](?:r?\\b|rangle))(.*)/i", $line, $mats)) {//foreach($mats as $k => $v) echo "\nline 14648, ".$k.": ".$v."\n";
				$locality = trim(str_ireplace(trim($mats[2]), "", $locality));
				$georeferenceRemarks = "Quad Map: ".trim($mats[3]);
				$line = trim(trim($mats[1]).", ".ltrim($mats[4], " :;,."));
			} else if(preg_match("/^(([A-Za-z]+\\.?(?: [A-Za-z]+\\.?(?: [A-Za-z]+\\.?)?)?) ?\\b[QO0G]ua[ad](?:r?\\b|rangle))(.*)/i", $line, $mats)) {
				$locality = trim(str_ireplace(trim($mats[1]), "", $locality));
				$georeferenceRemarks = "Quad Map: ".trim($mats[2]);
				$line = ltrim(rtrim($mats[3]), " ;:.,");
			} else if(preg_match("/^(.*?)[;:,.'] (([A-Za-z]+\\.?(?: [A-Za-z]+\\.?(?: [A-Za-z]+\\.?)?)?) ?\\b[QO0G]ua[ad](?:r?\\b|rangle))(.*)/i", $line, $mats)) {
				$locality = trim(str_ireplace(trim($mats[2]), "", $locality));
				$georeferenceRemarks = "Quad Map: ".trim($mats[3]);
				$line = trim(trim($mats[1]).", ".ltrim($mats[4], " :;,."));
			} else if(preg_match("/^(.*?)(([A-Za-z]+\\.?(?: [A-Za-z]+\\.?)?) ?\\b[QO0G]ua[ad](?:r?\\b|rangle))(.*)/i", $line, $mats)) {
				$locality = trim(str_ireplace(trim($mats[2]), "", $locality));
				$georeferenceRemarks = "Quad Map: ".trim($mats[3]);
				$line = trim(trim($mats[1]).", ".ltrim($mats[4], " :;,."));
			}//echo "\nline 15796, line: ".$line."\ncountPotentialLocalityWords(line): ".$this->countPotentialLocalityWords($line)."\ncontainsVerbatimAttribute(line): ".$this->containsVerbatimAttribute($line)."\ncountPotentialHabitatWords(line): ".$this->countPotentialHabitatWords($line)."\n";
			if($this->containsVerbatimAttribute($line)) {
				$pos = strpos($line, ";;;");
				$rest = "";
				$additionalTerms = array("whit(?:e|ish)", "black", "green(?:ish)?", "orang(?:e|ish)", "brown(?:ish)?", "gold", "pale", "magenta", "grey", "round", "blu(?:e|ish)", "tan", "yellow(?:ish)?");//terms that are added to the list after the line has already been tagged as a verbatimAttribute
				if($pos !== FALSE) {
					$firstPart = trim(substr($line, 0, $pos));
					$lastPart = trim(substr($line, $pos+3));
					if($this->containsVerbatimAttribute($firstPart, $additionalTerms) && !$this->containsVerbatimAttribute($lastPart, $additionalTerms)) {
						$rest = $firstPart;
						$line = $lastPart;
					} else if($this->containsVerbatimAttribute($lastPart, $additionalTerms) && !$this->containsVerbatimAttribute($firstPart, $additionalTerms)) {
						$rest = $lastPart;
						$line = $firstPart;
					} else {
						$rest = $line;
						$line = "";
					}
				} else {
					$pos = strpos($line, "; ");
					if($pos !== FALSE) {
						$firstPart = trim(substr($line, 0, $pos));
						$lastPart = trim(substr($line, $pos+2));
						if($this->containsVerbatimAttribute($firstPart) && !$this->containsVerbatimAttribute($lastPart, $additionalTerms)) {
							$rest = $firstPart;
							$line = $lastPart;
						} else if($this->containsVerbatimAttribute($lastPart, $additionalTerms) && !$this->containsVerbatimAttribute($firstPart, $additionalTerms)) {
							$rest = $lastPart;
							$line = $firstPart;
						} else {
							$rest = $line;
							$line = "";
						}
					} else if(preg_match("/(.+[A-Za-z]{3,})\\. (.+)/i", $line, $mats)) {
						$firstPart = trim($mats[1]);
						$lastPart = trim($mats[2]);
						if($this->containsVerbatimAttribute($firstPart, $additionalTerms) && !$this->containsVerbatimAttribute($lastPart, $additionalTerms)) {
							$rest = $firstPart;
							$line = $lastPart;
						} else if($this->containsVerbatimAttribute($lastPart, $additionalTerms) && !$this->containsVerbatimAttribute($firstPart, $additionalTerms)) {
							$rest = $lastPart;
							$line = $firstPart;
						} else {
							$rest = $line;
							$line = "";
						}
					} else if(preg_match("/(.+[A-Za-z)]{4,}), (.+)/i", $line, $mats)) {
						$firstPart = trim($mats[1]);
						$lastPart = trim($mats[2]);
						if($this->containsVerbatimAttribute($firstPart, $additionalTerms) && !$this->containsVerbatimAttribute($lastPart, $additionalTerms)) {
							$rest = $firstPart;
							$line = $lastPart;
						} else if($this->containsVerbatimAttribute($lastPart, $additionalTerms) && !$this->containsVerbatimAttribute($firstPart, $additionalTerms)) {
							$rest = $lastPart;
							$line = $firstPart;
						} else {
							$rest = $line;
							$line = "";
						}
					} else {
						$words = explode(" ", $line);
						if(count($words) > 2) {
							$first = $words[0];
							$rest = trim(substr($line, strpos($line, $first)+strlen($first)));
							if($this->containsVerbatimAttribute($first) && !$this->containsVerbatimAttribute($rest, $additionalTerms)) {
								$verbatimAttributes = $this->mergeFields($verbatimAttributes, $first);
								$line = $rest;
								$rest = "";
							} else {
								$words = array_reverse($words);
								$last = $words[0];
								$rest = trim(substr($line, 0, strpos($line, $last)));
								if($this->containsVerbatimAttribute($last) && !$this->containsVerbatimAttribute($rest, $additionalTerms)) {
									$verbatimAttributes = $this->mergeFields($verbatimAttributes, $last);
									$line = $rest;
									$rest = "";
								} else {
									$rest = $line;
									$line = "";
								}
							}
						} else {
							$verbatimAttributes = $this->mergeFields($verbatimAttributes, $line);
							$line = "";
							$rest = "";
						}
					}
				}//echo "\nline 14218, line: ".$line."\nrest: ".$rest."\nsubstrate: ".$substrate."\n";
				if($rest) {
					$rest = trim(str_replace(";;;", ";", $rest));
					if(preg_match("/^((?:(?:Fairly |Quite |Very |Not |Mostly )?(?:(?:Un)?Common |(?:In)?Frequent |(?:Locally )?Abundant |Found |Loose |Growing |Epi(?:phyt|lith|xyl)ic |Xylicolous ))?On .+[A-Za-z]{4,})[;,.] (.++)$/i", $rest, $mats)) {
						$temp = trim($mats[1]);
						if(!$this->countPotentialLocalityWords($temp)) {
							if($this->containsVerbatimAttribute($temp)) {
								$temp2 = "";
								$rest2 = "";
								if(preg_match("/^(.+); (.+)/i", $temp, $mats2)) {
									$temp2 = trim($mats2[1]);
									$rest2 = trim($mats2[2]);
								} else if(preg_match("/^(.+[A-Za-z]{3,})\\. (.+)/i", $temp, $mats2)) {
									$temp2 = trim($mats2[1]);
									$rest2 = trim($mats2[2]);
								} else if(preg_match("/^(.+[A-Za-z]{3,}), (.+)/i", $temp, $mats2)) {
									$temp2 = trim($mats2[1]);
									$rest2 = trim($mats2[2]);
								}
								if($temp2 && !$this->countPotentialLocalityWords($temp2)) {
									$substrate = $this->mergeFields($substrate, $temp2);
									$rest = $rest2.". ".trim($mats[2], " ;:,.&");
								} else {
									$substrate = $this->mergeFields($substrate, $temp);
									$rest = trim($mats[2], " ;:,.&");
								}
							} else {
								$substrate = $this->mergeFields($substrate, $temp);
								$rest = trim($mats[2], " ;:,.&");
							}
						} else {
							$temp2 = "";
							$rest2 = "";
							if(preg_match("/(.+); (.+)/i", $temp, $mats2)) {
								$temp2 = trim($mats2[1]);
								$rest2 = trim($mats2[2]);
							} else if(preg_match("/(.+[A-Za-z]{3,})\\. (.+)/i", $temp, $mats2)) {
								$temp2 = trim($mats2[1]);
								$rest2 = trim($mats2[2]);
							} else if(preg_match("/(.+[A-Za-z]{3,}), (.+)/i", $temp, $mats2)) {
								$temp2 = trim($mats2[1]);
								$rest2 = trim($mats2[2]);
							}
							if($temp2 && $this->countPotentialLocalityWords($temp2) == 0) {
								$substrate = $this->mergeFields($substrate, $temp2);
								$rest = $rest2.". ".trim($mats[2], " ;:,.&");
							}
						}
					} else if(preg_match("/^(Corticolous (on .+))/i", $rest, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 14236, mats[".$i++."] = ".$mat."\n";
						$verbatimAttributes = "Corticolous";
						$temp = trim($mats[1]);
						if(preg_match("/^(Corticolous on [A-Za-z]+ )((?:along|on|at) .+)/i", $rest, $mats2)) {
							$temp = trim($mats2[2]);
							if($this->countPotentialLocalityWords($temp)) {
								$substrate = $this->mergeFields($substrate, trim($mats2[1]));
								$locality = $this->mergeFields($locality, trim($mats2[2]));
							} else if($this->countPotentialHabitatWords($temp)) {
								$substrate = $this->mergeFields($substrate, trim($mats2[1]));
								$habitat = $this->mergeFields($habitat, trim($mats2[2]));
							}
						} else $substrate = $this->mergeFields($substrate, $rest);
					} else if(preg_match("/(.+)[.,;] {1,2}((?:(?:Fairly |Quite |Very |Not |Mostly )?(?:(?:Un)?Common|(?:In)?Frequent|Abundant) |Found |Loose |Growing |Epi(?:phyt|lith|xyl)ic |Xylicolous )?On .+)/i", $rest, $mats)) {
						$temp = trim($mats[2]);
						if($this->countPotentialLocalityWords($temp) == 0) {
							$substrate = $this->mergeFields($substrate, $temp);
							$rest = trim($mats[1], " ;:,.&");
						} else {
							$temp2 = "";
							$rest2 = "";
							if(preg_match("/(.+); (.+)/i", $temp, $mats2)) {
								$temp2 = trim($mats2[1]);
								$rest2 = trim($mats2[2]);
							} else if(preg_match("/(.+[A-Za-z]{3,})\\. (.+)/i", $temp, $mats2)) {
								$temp2 = trim($mats2[1]);
								$rest2 = trim($mats2[2]);
							} else if(preg_match("/(.+?[A-Za-z]{3,}), (.+)/i", $temp, $mats2)) {
								$temp2 = trim($mats2[1]);
								$rest2 = trim($mats2[2]);
							}
							if($temp2 && $this->countPotentialLocalityWords($temp2) == 0) {
								$substrate = $this->mergeFields($substrate, $temp2);
								$rest = trim($mats[1], " ;:,.&");
								$locality = $this->mergeFields($locality, $rest2);
							}
						}
					}
					if(preg_match("/(.+[A-Za-z]{3,})\\. (.+)/i", $rest, $mats)) {
						$firstPart = trim($mats[1]);
						$lastPart = trim($mats[2]);
						if($this->containsVerbatimAttribute($firstPart) && !$this->containsVerbatimAttribute($lastPart)) {
							if($this->countPotentialHabitatWords($lastPart)) {
								$habitat = $this->mergeFields($habitat, $lastPart);
								$verbatimAttributes = $this->mergeFields($verbatimAttributes, $firstPart);
								$rest = "";
							} else if($this->countPotentialLocalityWords($lastPart)) {
								$locality = $this->mergeFields($locality, $lastPart);
								$verbatimAttributes = $this->mergeFields($verbatimAttributes, $firstPart);
								$rest = "";
							}
						} else if($this->containsVerbatimAttribute($lastPart) && !$this->containsVerbatimAttribute($firstPart)) {
							if($this->countPotentialHabitatWords($firstPart)) {
								$habitat = $this->mergeFields($habitat, $firstPart);
								$verbatimAttributes = $this->mergeFields($verbatimAttributes, $lastPart);
								$rest = "";
							} else if($this->countPotentialLocalityWords($firstPart)) {
								$locality = $this->mergeFields($locality, $firstPart);
								$verbatimAttributes = $this->mergeFields($verbatimAttributes, $lastPart);
								$rest = "";
							}
						}
					}//echo "\nline 14352, line: ".$line."\nrest: ".$rest."\nhabitat: ".$habitat."\n";
					if($rest && !$this->isMostlyGarbage2($rest, 0.48)) {
						if($this->containsVerbatimAttribute($rest)) {
							if(preg_match("/(.+) Substrate[;:]? (.*)/i", $rest, $mats)){
								$firstPart = trim($mats[1]);
								$lastPart = trim($mats[2]);
								if($this->containsVerbatimAttribute($firstPart) && !$this->containsVerbatimAttribute($lastPart)) {
									$verbatimAttributes = $this->mergeFields($verbatimAttributes, $firstPart);
									$substrate = $this->mergeFields($substrate, $lastPart);
								} else $verbatimAttributes = $this->mergeFields($verbatimAttributes, $rest);
							} else $verbatimAttributes = $this->mergeFields($verbatimAttributes, $rest);
						} else if($this->countPotentialHabitatWords($rest)) $habitat = $this->mergeFields($habitat, $rest);
						else if($this->countPotentialLocalityWords($rest)) $locality = $this->mergeFields($locality, $rest);
						else /*if(!$this->containsNumber($rest))*/ $occurrenceRemarks = $rest;
					}
				}
			}//echo "\nline 17014, line: ".$line."\nhabitat: ".$habitat."\nsubstrate: ".$substrate."\n";
			if(preg_match("/^(?:Fairly |Quite |Very |Not |Mostly )?(?:(?:Un)?Common|(?:In)?Frequent|(?:Locally )?Abundant|Found|Loose|Growing|Epi(?:phyt|lith)ic|Xylicolous) On .+/i", $line)) {
				$substrate = trim(str_replace(";;;", ";", $line));
				$sLLine = $this->splitHabitatLine($substrate, $acceptableSmallWords);
				if($sLLine) {//foreach($sLLine as $k => $v) echo "\nline 16014, ".$k.": ".$v."\n";
					$firstPart = $sLLine[0];
					$lastPart = $sLLine[1];
					$separator = $sLLine[2];
					$startOfLastPart = "";
					$endOfLastPart = "";
					if(preg_match("/([A-Za-z ,-]+)[,.]? ((?:along|among|near|(?:with)?in|at|be(?:low|yond|neath|hind)|above|under) .+)/i", $firstPart, $mats2)) {
						$firstPart = trim($mats2[1]);
						$lastPart = trim($mats2[2])." ".$lastPart;
					}
					if($this->countPotentialLocalityWords($lastPart) == 0) {
						if($this->countPotentialHabitatWords($lastPart)) {
							$substrate = $firstPart;
							$habitat = $this->mergeFields($habitat, $lastPart);
						}
					} else {//the last part has locality words
						$sLLine = $this->splitLocalityLine($lastPart, $acceptableSmallWords);
						if($sLLine) {//foreach($sLLine as $k => $v) echo "\nline 17082, ".$k.": ".$v."\n";
							$substrate = $firstPart;
							$startOfLastPart = $sLLine[0];
							$endOfLastPart = $sLLine[1];
							$separator = $sLLine[2];
							if($this->countPotentialLocalityWords($startOfLastPart) == 0) {//all of the locality words are in endOfLastPart
								if($this->countPotentialHabitatWords($startOfLastPart)) {
									$habitat = $this->mergeFields($habitat, $startOfLastPart);
									$locality = $this->mergeFields($locality, $endOfLastPart);
								} else $locality = $this->mergeFields($locality, $lastPart);
							} else if($this->countPotentialLocalityWords($endOfLastPart) == 0) {//all of the locality words are in startOfLastPart
								if($this->countPotentialHabitatWords($endOfLastPart)) {
									$locality = $this->mergeFields($locality, $startOfLastPart);
									$habitat = $this->mergeFields($habitat, $endOfLastPart);
								} else $locality = $this->mergeFields($locality, $lastPart);
							} else $locality = $this->mergeFields($locality, $lastPart);
						} else {
							$sLLine = $this->splitHabitatLine($firstPart, $acceptableSmallWords);
							if($sLLine) {//foreach($sLLine as $k => $v) echo "\nline 17100, ".$k.": ".$v."\n";
								$startOfFirstPart = $sLLine[0];
								$endOfFirstPart = $sLLine[1];
								$separator = $sLLine[2];
								if($this->countPotentialLocalityWords($startOfFirstPart) == 0) {
									if($this->countPotentialLocalityWords($endOfFirstPart) == 0) {
										$substrate = $firstPart;
										$locality = $this->mergeFields($locality, $lastPart);
									} else {
										$substrate = $startOfFirstPart;
										$locality = $this->mergeFields($locality, $endOfFirstPart.$separator.$lastPart);
									}
								}
							}
							//$substrate = $firstPart;
							//$locality = $this->mergeFields($locality, $lastPart);
						}
					}
				}
			} else if(preg_match("/^(?:Collected )?(On .+)/i", $line, $matches)) {//echo "\nline 16068, line: ".$line."\nthis->countPotentialLocalityWords(line): ".$this->countPotentialLocalityWords($line)."\nthis->countPotentialHabitatWords(line): ".$this->countPotentialHabitatWords($line)."\n";
				if(preg_match("/^(.*?)(?:".$possibleNumbers."{1,4}[ -])?(?:".$possibleMonths.")/i", $line, $mats)) {
					$line = trim($mats[1], " ;:,.");
					if(preg_match("/^(?:Collected )?On.{0,3}$/i", $line)) break;
				} else $line = trim($matches[1]);
				if($line && stripos($line, " Workshop") === FALSE && stripos($line, " foray") === FALSE && stripos($line, " Society") === FALSE) {
					$line = trim(str_replace(";;;", ";", $line));
					if($this->countPotentialLocalityWords($line)) {
						$sLLine = $this->splitHabitatLine($line, $acceptableSmallWords);
						if($sLLine) {
							$firstPart = $sLLine[0];
							$lastPart = $sLLine[1];
							$separator = $sLLine[2];
							if(strlen($lastPart) < 4 && preg_match("/^(.*[A-Za-z]{4,})[,;] (.+)$/", $firstPart, $mats2)) {
								$firstPart = trim($mats2[1]);
								$lastPart = trim($mats2[2]);
							}
							if($this->countPotentialLocalityWords($firstPart) == 0) {//echo "\nline 16085, firstPart: ".$firstPart."\nlastPart: ".$lastPart."\n";
								$substrate = $this->mergeFields($substrate, $firstPart);
								//$locality = $this->mergeFields($locality, $lastPart, $separator);
								if(strpos($firstPart, " ") !== FALSE && strpos($lastPart, " ") !== FALSE) {
									$sLLine = $this->splitLocalityLine($lastPart, $acceptableSmallWords);
									if($sLLine) {
										$startOfLastPart = $sLLine[0];
										$endOfLastPart = $sLLine[1];
										$separator = $sLLine[2];
										//echo "\nline 17099, startOfLastPart: ".$startOfLastPart."\nendOfLastPart: ".$endOfLastPart."\n".
										//"countPotentialLocalityWords(startOfLastPart): ".$this->countPotentialLocalityWords($startOfLastPart)."\n".
										//"countPotentialLocalityWords(endOfLastPart): ".$this->countPotentialLocalityWords($endOfLastPart)."\n".
										//"countPotentialHabitatWords(startOfLastPart): ".$this->countPotentialHabitatWords($startOfLastPart)."\n".
										//"countPotentialHabitatWords(endOfLastPart): ".$this->countPotentialHabitatWords($endOfLastPart)."\n";
										if($this->countPotentialLocalityWords($startOfLastPart) == 0 && $this->countPotentialHabitatWords($startOfLastPart)) {
											$habitat = $this->mergeFields($habitat, $startOfLastPart, $separator);
											$locality = $this->mergeFields($locality, $endOfLastPart, $separator);
										} else if($this->countPotentialLocalityWords($endOfLastPart) == 0 && $this->countPotentialHabitatWords($endOfLastPart)) {
											if(strcasecmp(substr($endOfLastPart, 0, 3), "on ") == 0) $substrate = $this->mergeFields($substrate, $endOfLastPart, $separator);
											else $habitat = $this->mergeFields($habitat, $endOfLastPart, $separator);
											$sLLine = $this->splitLocalityLine($startOfLastPart, $acceptableSmallWords);
											if($sLLine) {
												$temp = $sLLine[0];
												$temp2 = $sLLine[1];
												$separator2 = $sLLine[2];
												if($this->countPotentialLocalityWords($temp2) == 0 && $this->countPotentialHabitatWords($temp2)) {
													$habitat = $this->mergeFields($habitat, $temp2, $separator2);
												} else $locality = $this->mergeFields($locality, $startOfLastPart, $separator);
											} else $locality = $this->mergeFields($locality, $startOfLastPart, $separator);
										} else $locality = $this->mergeFields($locality, $lastPart, $separator);
									} else $locality = $this->mergeFields($locality, $lastPart, $separator);
								} else $locality = $this->mergeFields($locality, $lastPart, $separator);
							} else {
								$sLLine = $this->splitLocalityLine($firstPart, $acceptableSmallWords);
								$found = false;
								if($sLLine) {
									$startOfFirstPart = $sLLine[0];
									$endOfFirstPart = $sLLine[1];
									//echo "\nline 17128, startOfFirstPart: ".$startOfFirstPart."\nendOfFirstPart: ".$endOfFirstPart."\n";
									if(strlen($startOfFirstPart) > 2 && $this->countPotentialLocalityWords($startOfFirstPart) == 0) {
										if($substrate && $this->countPotentialHabitatWords($startOfFirstPart)) $habitat = $this->mergeFields($habitat, $startOfFirstPart);
										else $substrate = $this->mergeFields($substrate, $startOfFirstPart);
										$locality = $this->mergeFields($locality, $endOfFirstPart.$separator.$lastPart);
										$found = true;
									}
								}
								if(!$found) {
									if($this->countPotentialLocalityWords($lastPart) == 0) {
										if(preg_match("/^((?:(?:(?:Fairly |Quite |Very |Not )?(?:(?:Un)?Common |(?:Locally )?Abundant ))|Found |Loose |Growing |Epi(?:phyt|lith)ic |Xylicolous )?On .+)/i", $lastPart)) {
											$locality = $this->mergeFields($locality, $firstPart);
											$substrate = $this->mergeFields($substrate, $lastPart, $separator);
										} else if($this->countPotentialHabitatWords($lastPart)) {
											$locality = $this->mergeFields($locality, $firstPart);
											$habitat = $this->mergeFields($habitat, $lastPart, $separator);
										} else $locality = $this->mergeFields($locality, $line);
									} else $locality = $this->mergeFields($locality, $line);
								}
							}
						} else $locality = $this->mergeFields($locality, $line);
					} else if($this->countPotentialHabitatWords($line)) {
						$sLLine = $this->splitLocalityLine($line, $acceptableSmallWords);
						if($sLLine) {//$i=0;foreach($sLLine as $sLLin) echo "\nline 16146, sLLine[".$i++."] = ".$sLLin."\n";
							$firstPart = $sLLine[0];
							$lastPart = $sLLine[1];
							$separator = $sLLine[2];
							if(strlen($lastPart) < 4 && preg_match("/(.*[A-Za-z]{4,})[,;] (.+)/", $firstPart, $mats2)) {
								$firstPart = trim($mats2[1]);
								$lastPart = trim($mats2[2]);
							}
							if($this->countPotentialHabitatWords($lastPart)) {
								if($substrate) $habitat = $this->mergeFields($habitat, $line);
								else {
									$habitat = $this->mergeFields($habitat, $lastPart, $separator);
									$substrate = $this->mergeFields($substrate, $firstPart);
									$tSub = $this->terminateSubstrate($substrate);//$i=0;foreach($tSub as $tSu) echo "\nline 16146, tSub[".$i++."] = ".$tSu."\n";
									$substrate = $tSub[0];
									$temp = $tSub[1];
									if($this->countPotentialHabitatWords($temp)) $habitat = $this->mergeFields($temp, $habitat);
								}
							} else $substrate = $this->mergeFields($substrate, $line);
						} else $substrate = $this->mergeFields($substrate, $line);
					} else $substrate = $this->mergeFields($substrate, $line);
				} else break;
			} else if($this->countPotentialLocalityWords($line)) {// && !preg_match("/^(?:L|(?:\|_))[o0]ca(?:[1!l]ity|ti[o0]n|\\.)[:;]?/i", $line))
				if(preg_match("/^(?:L|(?:\|_))[o0]ca(?:[1!l]ity|ti[o0]n|\\.)[:;.]?$/i", $line)) $line = "";
				else if(preg_match("/^(?:L|(?:\|_))[o0]ca(?:[1!l]ity|ti[o0]n|\\.)[:;.](.*)/i", $line, $mats)) $line = trim($mats[1]);

				$localityAnalysis = $this->analyzeLocalityLine($line, $acceptableSmallWords);//foreach($localityAnalysis as $k => $v) echo "\nline 17223, ".$k.": ".$v."\n";
				if(array_key_exists('locality', $localityAnalysis)) $locality = $this->mergeFields($locality, $localityAnalysis['locality']);
				if(array_key_exists('habitat', $localityAnalysis)) $habitat = $this->mergeFields($habitat, $localityAnalysis['habitat']);
				if(array_key_exists('substrate', $localityAnalysis)) $substrate = $this->mergeFields($substrate, $localityAnalysis['substrate']);
			} else if($this->countPotentialHabitatWords($line) && strcasecmp($line, "Habitat") != 0) {//echo "\nline 17228, line: ".$line."\ncountPotentialHabitatWords(line): ".$this->countPotentialHabitatWords($line)."\n";
				$sLLine = $this->splitHabitatLine($line, $acceptableSmallWords);
				if($sLLine) {//foreach($sLLine as $k => $v) echo "\nline 17229, ".$k.": ".$v."\n";
					$firstPart = $sLLine[0];
					$lastPart = $sLLine[1];
					$separator = $sLLine[2];
					if(preg_match("/^((?:(?:Fairly |Quite |Very |Not |Mostly )?(?:(?:Un)?Common|(?:In)?Frequent|Abundant) |Found |Loose |Growing |Epi(?:phyt|lith|xyl)ic |Xylicolous )?On .+)/i", $lastPart)) {
						if(preg_match("/^([A-Za-z ,-]+)[,.]? (?<!and )((?:along|among|near|(?:with)?in|at|be(?:low|yond|neath|hind)|above|under) .+)/i", $lastPart, $mats)) {
							$substrate = $this->mergeFields($substrate, trim($mats[1]));
							$habitat = $this->mergeFields($habitat, $firstPart.", ".trim($mats[2]));
						} else {
							$substrate = $this->mergeFields($substrate, str_replace(";;;", ";", $lastPart));
							$habitat = $this->mergeFields($habitat, $firstPart);
						}
					} else {//echo "\nline 17241, habitat: ".$habitat."\nsubstrate: ".$substrate."\n";
						$sLLine = $this->splitHabitatLine($lastPart, $acceptableSmallWords);
						if($sLLine) {//foreach($sLLine as $k => $v) echo "\nline 17243, ".$k.": ".$v."\n";
							$endOfLastPart = $sLLine[1];
							$separator = $sLLine[2];
							if(preg_match("/^((?:(?:Fairly |Quite |Very |Not |Mostly )?(?:(?:Un)?Common|(?:In)?Frequent|Abundant) |Found |Loose |Growing |Epi(?:phyt|lith|xyl)ic |Xylicolous )?On .+)/i", $endOfLastPart)) {
								$substrate = $this->mergeFields($substrate, $endOfLastPart);
								$habitat = $this->mergeFields($habitat, str_replace(";;;", ";", $firstPart.$separator.$sLLine[0]), " ");
							} else $habitat = $this->mergeFields($habitat, str_replace(";;;", ";", $line), " ");
						} else $habitat = $this->mergeFields($habitat, str_replace(";;;", ";", $line), " ");
					}
				} else $habitat = $this->mergeFields($habitat, $line, " ");
			}
		}//echo "\nline 16203, locality: ".$locality."\nsubstrate: ".$substrate."\nhabitat: ".$habitat."\nverbatimAttributes: ".$verbatimAttributes."\nassociatedTaxa: ".$associatedTaxa."\n";
		$locality = str_replace
		(
			array("\r\n", "\n", "\r"),
			" ",
			trim($locality)
		);
		$localityPatStr = "/(.*?)(\\stype\\s)?\\b(?:L|(?:\|_))[o0]ca(?:[1!l]ity|ti[o0]n|\\.)[:;,)]?\\s(.+)/is";
		if(preg_match($localityPatStr, $locality, $locationMatches)) {
			if(count($locationMatches) == 4 && strlen(trim($locationMatches[2])) == 0) $locality = trim($locationMatches[1])." ".trim($locationMatches[3]);
		}// else if(preg_match($localityPatStr, $str, $locationMatches)) {
		//	if(count($locationMatches) == 4 && strlen(trim($locationMatches[2])) == 0) $locality = trim($locationMatches[3]);
		//}
		//echo "\nline 12700, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\nhabitat: ".$habitat."\n";
		$lPat = "/(.+?)\\b(?:[il1!|]at(?:[il1!|]tude)?\\b|quad\\b|[ec][lI!|][ec]v|Date|".
			"C[o0][lI!|](?:[lI!|]s?|[lI!|]ectors?|lected b[vyg]|[lI!|]?s)\\b|".
			"(?:H[o0]st\\s)?+Det(?:\\.|ermined by)|(?:THE )?NEW \\w{4} B[OD]TAN.+|(?:(?:Assoc(?:[,.]|".
			"[l!1|i]ated)\\s(?:Taxa|Spec[l!1|]es|P[l!1|]ants|spp[,.]?|with)[:;]?)|(?:along|together)\\swith)|(?:Substrat(?:um|e))|".
			"(?:[OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".$possibleNumbers."|3[1Iil!|OQ0\]][ -])(?:".
			$possibleMonths.")\\b|\\d{1,3}(?:\\.\\d{1,6})?\\s?�)/is";
		$hPat = "/(.+?)\\b(?:[il1!|]at(?:[il1!|]tude)?\\b|quad|[ec][lI!|][ec]v|[lI!|]ocality|[lI!|]ocation|".
			"[lI!|]oc\\.|Date|Col(?:\\.|:|l[:.]|lectors?|lected|l?s[:.]?)|leg(?:it|\\.):?|Identif|Det(?:\\.|ermined by)|".
			"[lI!|]at[li!|]tude|(?:THE )?NEW\\s\\w{4}\\sBOTAN.+|(?:(?:Assoc(?:[,.]|".
			"[l!1|i]ated)\\s(?:Taxa|Spec[l!1|]es|P[l!1|]ants|spp[,.]?|with)[:;]?)|(?:along|together)\\swith))/is";
		if($locality && !$verbatimElevation) {
			$elevArr = $this->getElevation($locality);
			$temp = $elevArr[0];
			if($temp) {
				$locality = trim($temp);
				$verbatimElevation = $elevArr[1];
				$temp = $elevArr[2];
				if(strlen($temp) > 3) {
					$temp = str_replace
					(
						array("\r\n", "\n", "\r"),
						array(" ", " ", " "),
						trim($temp)
					);
					if($this->countPotentialHabitatWords($temp)) {
						if(strlen($habitat) < 3) $habitat = $temp;
						else if(stripos($habitat, $temp) === FALSE) $habitat = trim($habitat, " :;.,").", ".$temp;
					}
				}
			}//echo "\nline 14687, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\nscientificName: ".$scientificName."\n";

			if($this->isCompleteGarbage($locality)) $locality = "";
			//else $locality = $this->terminateField($locality, $lPat);
			//echo "\nline 14599, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\nhabitat: ".$habitat."\n";
			if($this->isCompleteGarbage($habitat)) $habitat = "";
			else {
				$pat = "/(.+?)\\b(?:[OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".
					$possibleNumbers."|3[1Iil!|OQ0\]])[ -](?:".$possibleMonths.")\\b.*/i";
					if(preg_match($pat, $habitat, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 14696, mats[".$i++."] = ".$mat."\n";
					$habitat = trim(ltrim($mats[1], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
				}
				if(preg_match("/((?s).+?)(?:".$possibleMonths.")\\s(?:[OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".
					$possibleNumbers."|3[1Iil!|OQ0\]])[,.]?\\s(?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2}/i", $habitat, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 14700, mats[".$i++."] = ".$mat."\n";
					$habitat = trim(ltrim($mats[1], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
				}
				if(preg_match("/((?s).+?)(?:".$possibleMonths.")(?:[,.]\\s|[ -])(?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2}/i", $habitat, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 14703, mats[".$i++."] = ".$mat."\n";
					$habitat = trim(ltrim($mats[1], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
				}
				if(preg_match("/((?s).+?)(?:Sec?(:\\.|tion)|(?:C[OQ0][1Iil!|]{1,2}(?:\\.|ection|ected))|".
					"(?:Latitude[ ,:;])|(?:N\wW YORK)|(?:B[O0]TAN[1!|I]CAL))/i", $habitat, $mats)) $habitat = trim(ltrim($mats[1], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
				if(is_numeric($habitat)) $habitat = "";
			}
		}//echo "\nline 15251, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\nscientificName: ".$scientificName."\n";
		if(strlen($verbatimElevation) == 0) {
			$elevArr = $this->getElevation($str);
			$temp = $elevArr[1];
			if($temp) $verbatimElevation = $temp;
		}
		$datePat = "/(?:(?(?=(?:.+)\\b(?:\\d{1,2})[- ]?(?:(?i)".$possibleMonths.")[- ](?:\\d{4})))".
			"(.+)\\b(?:\\d{1,2})[- ]?(?:(?i)".$possibleMonths.")[- ](?:\\d{4})|".
			"(?=(?:.+)\\b(?:(?i)".$possibleMonths.")\\s(?:\\d{1,2}),?\\s(?:\\d{4}))".
			"(.+)\\b(?:(?i)".$possibleMonths.")\\s(?:\\d{1,2}),?\\s(?:\\d{4})|".
			"(.+)\\b(?:(?i)".$possibleMonths."),?\\s(?:\\d{4}))/s";
		if(preg_match($datePat, $locality, $dateMatches)) {
			$countMatches = count($dateMatches);
			$locality = trim($dateMatches[1]);
			if(strlen($locality) == 0 && $countMatches > 2) {
				$locality = trim($dateMatches[2]);
				if(strlen($locality) == 0 && $countMatches > 3) $locality = trim($dateMatches[3]);
			}
		}//echo "\nline 17282, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\n";
		if($scientificName && $locality) {
			if(strpos($locality, $scientificName) === 0) $locality = trim(substr($locality, strlen($scientificName)));
		}
		if($substrate) {
			if($locality) {
				$pos = strpos($locality, $substrate);
				if($pos !== FALSE) {
					if(strcasecmp($locality, $substrate) == 0) {
						$dotPos = strpos($substrate, ", ");
						if($dotPos !== FALSE) {
							$locality = trim(substr($substrate, $dotPos+2));
							$substrate = trim(substr($substrate, 0, $dotPos+1));
						} else {
							$dotPos = strpos($substrate, ". ");
							if($dotPos !== FALSE) {
								$locality = trim(substr($substrate, $dotPos+2));
								$substrate = trim(substr($substrate, 0, $dotPos+1));
							}
						}
					} else if($pos == 0) $locality = trim(substr($locality, strlen($substrate)));
					else {
						$temp = "On ".$substrate;
						if(stripos($locality, $temp) === 0) $locality = trim(substr($locality, strlen($temp)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
					}
				}
			} else {
				$dotPos = strpos($substrate, ". ");
				if($dotPos !== FALSE) {
					$temp = trim(substr($substrate, $dotPos+2));
					if($this->countPotentialLocalityWords($temp)) {
						$locality = $temp;
						$substrate = trim(substr($substrate, 0, $dotPos+1));
					}
				}
			}
		} else {//strlen(substrate) == 0
			$sPos = strpos($locality, ". On ");
			if($sPos !== FALSE) {
				$sub = trim(substr($locality, $sPos+2));
				if($this->countPotentialLocalityWords($sub) == 0) {
					$locality = trim(substr($locality, 0, $sPos));
					if(preg_match("/(.*[A-Za-z]{4,})\\. (.+)/", $sub, $mats)) {
						$temp = trim($mats[2]);
						if($this->countPotentialHabitatWords($temp)) {
							if(!$habitat) $habitat = $temp;
							$substrate = trim($mats[1]);
						} else $substrate = $sub;
					} else $substrate = $sub;
				}
			} else if(strcasecmp(substr($locality, 0, 3), "On ") == 0) {
				$sub = $locality;
				if(preg_match("/(.+),\\s((?:in|along|within|at)\\s.*)/i", $sub, $mats)) {
					$temp = trim($mats[2]);
					$sub = trim($mats[1]);
					if($this->countPotentialLocalityWords($sub) == 0) {
						$substrate = $sub;
						if($this->countPotentialLocalityWords($temp)) {
							$locality = $temp;
							$commaPos = strpos($locality, ", ");
							if($commaPos !== FALSE) {
								$temp2 = trim(substr($locality, $commaPos+2));
								$temp = trim(substr($locality, 0, $commaPos));
								if($this->countPotentialHabitatWords($temp) && $this->countPotentialLocalityWords($temp2)) {
									$locality = $temp2;
									$habitat = $temp;
								}
							}
						} else if($this->countPotentialHabitatWords($temp)) $habitat = $temp;
					}
				} else if(preg_match("/(.*[A-Za-z]{4,})\\. (.+)/", $sub, $mats)) {
					$temp = trim($mats[2]);
					$sub = trim($mats[1]);
					if($this->countPotentialLocalityWords($sub) == 0) {
						$substrate = $sub;
						$locality = $temp;
					} else if(preg_match("/(.*[A-Za-z]{4,})\\. (.+)/", $sub, $mats2)) {
						$temp = trim($mats2[2]);
						$sub = trim($mats2[1]);
						if($this->countPotentialLocalityWords($sub) == 0) {
							$substrate = $sub;
							$locality = $temp;
						}
					} else if(preg_match("/(.+), (.+)/", $sub, $mats2)) {
						$temp = trim($mats2[2]);
						$sub = trim($mats2[1]);
						if($this->countPotentialLocalityWords($sub) == 0) {
							$substrate = $sub;
							$locality = $temp;
						}
					}
				}
			} else if(preg_match("/(.*[A-Za-z]{4,}) (on .+) ((?:along|within) .+)/", $locality, $mats)) {//$i=0;foreach($mats as $mat) echo "\nmats[".$i++."] = ".$mat."\n";
				$temp = trim($mats[1]);
				$temp2 = trim($mats[2]);
				$temp3 = trim($mats[3]);
				$hCount1 = $this->countPotentialHabitatWords($temp);
				$hCount2 = $this->countPotentialHabitatWords($temp2);
				$hCount3 = $this->countPotentialHabitatWords($temp3);
				$lCount1 = $this->countPotentialLocalityWords($temp);
				$lCount2 = $this->countPotentialLocalityWords($temp2);
				$lCount3 = $this->countPotentialLocalityWords($temp3);
				//echo "\nhCount1: ".$hCount1."\nlCount1: ".$lCount1."\nhCount2: ".$hCount2."\nlCount2: ".$lCount2."\nhCount3: ".$hCount3."\nlCount3: ".$lCount3."\n";
				if($lCount2 == 0) {
					$substrate = $temp2;
					if($hCount1 > $lCount1) {
						if(strlen($habitat) == 0) $habitat = $temp;
						else if(stripos($habitat, $temp) === FALSE) $habitat = trim($habitat, " ;:,.").", ".$temp;
						$locality = "";
					} else if($lCount1) $locality = $temp;
					else $locality = "";
					if($hCount3 > $lCount3) {
						if(strlen($habitat) == 0) $habitat = trim($temp3, " ;:,.");
						else if(stripos($habitat, $temp3) === FALSE) $habitat = trim($habitat, " ;:,.").", ".$temp3;
					} else if($lCount3) {
						if(strlen($locality) == 0) $locality = $temp3;
						else if(stripos($locality, $temp3) === FALSE) $locality = trim($locality, " ;:,.").", ".$temp3;
					}
				}
			}
		}//echo "\nline 17460, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\n";
		if((!$stateProvince || !$country ) && $county) {
			$ps = $this->getStateFromCounty($county, $states);
			if($ps) {
				if(!$stateProvince) $stateProvince = $ps[0];
				if(!$country) $country = $ps[1];
			}
		}
		if($locality && $scientificName) {
			if(stripos($locality, $scientificName) === 0) $locality = substr($locality, strlen($scientificName));
		}
		if($habitat) {
			$tempArray = $this->extractAssociatedTaxa($habitat);
			$habitat = $tempArray['input'];
			$atl = $tempArray['associatedTaxa'];
			if($atl) $associatedTaxa = $this->mergeFields($associatedTaxa, $atl, ", ");
			$habitat = $this->mergeFields($habitat, $tempArray['rest'], ", ");
			//echo "\nline 17478, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\n";
			$subPat = "/((?s).*)\\b(?:[il1!|]at(?:[il1!|]tude|\\.|\\b)|quad|[ec][lI!|][ec]v|Date|Col(?:\\.|:|ls?[:.]|lectors?|lected|l?s[:.])|".
				"(?:H[o0]st\\s)?+Det(?:\\.|ermined by)|(?:THE )?NEW \\w{4} B[OD]TAN.+|State|(?:".$possibleNumbers."{2,4}[ -])?(?:".$possibleMonths.")";
			$subPat .= ")/i";
			$habitat = trim($this->terminateField($this->terminateField(trim($habitat), $subPat), $subPat));
		}//echo "\nline 17483, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\n";
		if($substrate) {
			$tempArray = $this->extractAssociatedTaxa($substrate);
			$substrate = $tempArray['input'];
			$atl = $tempArray['associatedTaxa'];
			if($atl) $associatedTaxa = $this->mergeFields($associatedTaxa, $atl, ", ");
			$habitat = $this->mergeFields($habitat, $tempArray['rest'], ", ");
			//echo "\nline 17453, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\n";
			if(preg_match("/^(.+?)[.,;:]? (?<! and )((?:along|among|near|(?:with)?in|at|be(?:low|yond|neath|hind)|above|under(?:neath)?) .+)$/i", $substrate, $mats)) {
				$temp = trim($mats[2]);
				if($this->countPotentialHabitatWords($temp)) {
					//$habitat = $this->mergeFields($habitat, $temp);
					$habitat = $this->mergeFields($temp, $habitat);
					$substrate = trim($mats[1]);
				}
			}
			$subPat = "/((?s).*)\\b(?:[il1!|]at(?:[il1!|]tude|\\.|\\b)|quad|[ec][lI!|][ec]v|Date|Col(?:\\.|:|ls?[:.]|lectors?|lected|l?s[:.])|".
				"(?:H[o0]st\\s)?+Det(?:\\.|ermined by)|(?:THE )?NEW \\w{4} B[OD]TAN.+|State|(?:".$possibleNumbers."{2,4}[ -])?(?:".$possibleMonths.")";
			$subPat .= ")/i";
			$substrate = trim($this->terminateField($this->terminateField(trim($substrate), $subPat), $subPat));
			if(strcasecmp($substrate, "on") == 0) $substrate = "";
		}
		if($habitat) {
			if(preg_match("/^Habitat[;:.,]?(.*)/i", $habitat, $mats)) $habitat = trim($mats[1]);
		}
		if($locality) {
			$tempArray = $this->extractAssociatedTaxa($locality);
			$locality = $tempArray['input'];
			$atl = $tempArray['associatedTaxa'];
			$rest = $tempArray['rest'];
			if($atl) {
				$associatedTaxa = $this->mergeFields($associatedTaxa, $atl, ", ");
				if($this->countPotentialHabitatWords($rest)) $habitat = $this->mergeFields($habitat, $rest, ", ");
				else $locality = $this->mergeFields($locality, $rest, " ");
			} else {
				$tempArray = $this->extractAssociatedTaxa($rest);
				$atl = $tempArray['associatedTaxa'];
				if($atl) {
					$associatedTaxa = $this->mergeFields($associatedTaxa, $atl, ", ");
					$part1 = $tempArray['input'];
					if($this->countPotentialHabitatWords($part1) && $this->countPotentialLocalityWords($part1) == 0) $habitat = $this->mergeFields($habitat, $part1, ", ");
					else $locality = $this->mergeFields($locality, $part1, " ");
					$rest = $tempArray['rest'];
					if($this->countPotentialHabitatWords($rest) && $this->countPotentialLocalityWords($rest) == 0) $habitat = $this->mergeFields($habitat, $rest, ", ");
					else $locality = $this->mergeFields($locality, $rest, " ");
				} else if($this->countPotentialHabitatWords($rest) && $this->countPotentialLocalityWords($rest) == 0) $habitat = $this->mergeFields($habitat, $rest, ", ");
				else $locality = $this->mergeFields($locality, $rest, " ");
			}
		}
		if($county && $stateProvince && strcasecmp($county, $stateProvince) != 0) {
			$pos = stripos($county, $stateProvince);
			if($pos !== FALSE) $county = trim(substr($county, $pos+strlen($stateProvince)), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		}
		if(preg_match("/(.*)[0OD]at[ec]/i", $georeferenceRemarks, $mat)) $georeferenceRemarks = $mat[1];
		$exsNumber = trim($exsNumber, " \t\n\r\0\x0B,:.;!\"\'\\~@#$%^&*_-");
		$recordNumber = trim($recordNumber, " \t\n\r\0\x0B,:.;!\"\'\\~@#$%^&*_-");
		//echo "\nline 14373, exsNumber: ".$exsNumber."\nrecordNumber: ".$recordNumber."\ntempRecordNumber: ".$tempRecordNumber."\n";
		if($ometid && strlen($recordNumber) == 0) {
			if(strcasecmp($exsNumber, $tempRecordNumber) != 0) $recordNumber = $tempRecordNumber;
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => ucfirst(trim($stateProvince, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => ucfirst(trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'georeferenceRemarks' => trim($georeferenceRemarks, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'occurrenceRemarks' => trim($occurrenceRemarks, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'locality' => trim($locality, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRemarks' => trim($taxonRemarks, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_"),
			'recordNumber' => $recordNumber,
			'exsNumber' => $exsNumber,
			'ometid' => trim($ometid, " \t\n\r\0\x0B,:.;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_"),
			'verbatimElevation' => trim($verbatimElevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'eventDate' => $eventDate,
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedById' => $recordedById,
			'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'dateIdentified' => $dateIdentified,
			'otherCatalogNumbers' => trim($otherCatalogNumbers, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedCollectors' => trim($associatedCollectors, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimCoordinates' => $verbatimCoordinates,
			'verbatimEventDate' => $verbatimEventDate,
			'rawstr' => trim($str)
		);
	}

	protected function isKryptogamaeExsiccatiVindobonensiLabel($s) {
		if(preg_match("/.*[CGK]r[yv][ypg]to[ypg]a(?:m|[ri]n)a[ce] [ce]xsi[ce]{2}ata[ce] [ce]d[1Il!|]ta[ce].*/is", $s)) return true;
		else if(preg_match("/Mus[ec][0o] H[1Il!|][s5]t[.,] Natur[.,] [VY][1Il!|]nd[0o]b[0o]n.+/is", $s)) return true;
		else return false;
	}

	protected function doKryptogamaeExsiccatiVindobonensiLabel($s) {
		$pattern =
			array
			(
				"/[CGK]r[yv][ypg]to[ypg]a(?:m|[ri]n)a[ce] [ce]xsi[ce]{2}ata[ce] [ce]d[1Il!|]ta[ce] a Mus[ce][o0] Hist[.,] Natur[.,] [VY]ind[o0]b[o0]n[ce]nsi/is",
				"/[CGK]r[yv][ypg]to[ypg]a(?:m|[ri]n)a[ce] [ce]xsi[ce]{2}ata[ce] [ce]d[1Il!|]ta[ce] a Mus[ce][o0] Hist[.,] Natur[.,] Vind[o0]b[o0]n.{0,3}/is",
				"/.{0,3}Mus[ec][0o] H[1Il!|][s5]t[.,] Natur[.,] V[1Il!|]nd[0o]b[0o]n.{0,3}/is",
				"/ \(sect[.,] .{3,15}\)/i"
			);
		$replacement =
			array
			(
				"",
				"",
				"",
				""
			);
		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 8296, s:\n".$s."\n";
		$ometid = "";
		$exsnumber = "";
		$scientificName = "";
		$infraspecificEpithet = "";
		$taxonRank = "";
		$verbatimAttributes = "";
		$associatedTaxa = "";
		$substrate = "";
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 9032, line: ".$line."\n";
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			$psn = $this->processSciName($line);
			if($psn != null) {
				if(array_key_exists ('scientificName', $psn)) {
					$scientificName = $psn['scientificName'];
					$s = trim(str_replace($scientificName, "", $s));
				}
				if(array_key_exists ('infraspecificEpithet', $psn)) {
					$infraspecificEpithet = $psn['infraspecificEpithet'];
					$s = trim(str_replace($infraspecificEpithet, "", $s));
				}
				if(array_key_exists ('taxonRank', $psn)) {
					$taxonRank = $psn['taxonRank'];
					$s = trim(str_replace($taxonRank, "", $s));
				}
				if(array_key_exists ('verbatimAttributes', $psn)) {
					$verbatimAttributes = $psn['verbatimAttributes'];
					$s = trim(str_replace($verbatimAttributes, "", $s));
				}
				if(array_key_exists ('associatedTaxa', $psn)) {
					$associatedTaxa = $psn['associatedTaxa'];
					$s = trim(str_replace($associatedTaxa, "", $s));
				}
				if(array_key_exists ('substrate', $psn)) {
					$substrate = $psn['substrate'];
					$s = trim(str_replace($substrate, "", $s));
				}
				if(array_key_exists ('recordNumber', $psn)) {
					$exsnumber = $psn['recordNumber'];
					$s = trim(str_replace($exsnumber, "", $s));
					//$s = trim(str_replace($line, "", $s));
					if($exsnumber) break;
				}
			}
		}
		$iExsNumber = 0;
		$exsnumber = str_replace(" ", "", $exsnumber);
		if(is_numeric($exsnumber)) $iExsNumber = intval($exsnumber);
		else if(strlen($exsnumber) > 1) {//remove the last character and see if the remainder is numeric
			$temp = trim(substr($exsnumber, 0, strlen($exsnumber)-1));
			if(is_numeric($temp)) $iExsNumber = intval($temp);
		}
		if($iExsNumber) {
			if($iExsNumber > 3200) $ometid = "343";
			else if($iExsNumber > 2600) $ometid = "222";
			else if($iExsNumber > 400) $ometid = "221";
			else if($iExsNumber > 100) $ometid = "220";
			else $ometid = "78";
		} else $ometid = "78";
		$fields = array();
		$fields['scientificName'] = $this->formatSciName($scientificName);
		$fields['exsNumber'] = $exsnumber;
		$fields['infraspecificEpithet'] = $infraspecificEpithet;
		$fields['taxonRank'] = $taxonRank;
		$fields['verbatimAttributes'] = $verbatimAttributes;
		$fields['associatedTaxa'] = $associatedTaxa;
		$fields['substrate'] = $substrate;
		return $this->doGenericLabel($s, $ometid, $fields);
	}

	private function getTaxonOfHeaderInfo($str) {
		$statePatStr = "/((?s).*)?((?:[EP][L1!|]ANT[5S]|(?:ASC[O0D]MYC[EC]T[EC]S[5S])|(?:FL[O0D]RA)|(?:F(?:U|(?:LI))N[EGC][Il!1.,])|".
			"(?:.{1,2}[I|!l][ECU](?:H|(?:I-?I)|TI)EN'?[5S]|FL[O0D]RA)|(?:CRYPT[O0DQU]GAM[5S])|".
			"(?:BRY[O0DQU](?:(?:FL[O0D]RA)|(?:PHYT(?:A|[EC][5S]))))|(?:M[O0][5S]{2}E[5S])|".
			"(?:MU[5S][CG][l1|I!])|(?:HEPAT[l1!|I]CA.)|(?:P[l1!|]ANT[5S])|(?:[5S]PHAGNA))".
			"(?:\\r\\n|\\n|\\r|\\s)?[DOU0][EFPKI1][RFPN]?)[;:]?(.*)(?:\\r\\n|\\n|\\r)((?s).*)/i";
		if(preg_match($statePatStr, $str, $matches)) {
			$first = $matches[1];
			if(preg_match($statePatStr, $first, $matches2)) {
				return array($matches2[1], $matches2[3], $matches2[4].$matches[2].$matches[3].$matches[4]);
			} else return array($first, $matches[3], $matches[4]);
		}
		return null;
	}

	private function processTaxonOfHeaderInfo($matches) {
		$country = "";
		$location = "";
		$state_province = "";
		$county = "";
		$startOfFile = trim($matches[0]);
		$endOfLine = trim($matches[1]);
		$endOfFile = trim($matches[2]);
		$workingEndOfFile = $endOfFile;
		$sLen = strlen($endOfLine);
		//echo "\nline 706, startOfFile: ".$startOfFile."\n\tendOfLine: ".$endOfLine."\n\tendOfFile: ".$endOfFile."\n";
		//if there is nothing after the "Taxons of", get it from the beginning of the next line
		if($sLen == 0) {
			$eolPos = strpos($endOfFile, "\n");
			if($eolPos !== FALSE) {
				$firstLine = trim(substr($endOfFile, 0, $eolPos));
				$workingEndOfFile = trim(substr($endOfFile, $eolPos+1));
				$commaPos = strpos($firstLine, ",");
				if($commaPos !== FALSE) {
					$firstPart = trim(substr($firstLine, 0, $commaPos));
					$lastPart = trim(substr($firstLine, $commaPos+1));
					$spacePos = strpos($lastPart, " ");
					if($spacePos !== FALSE) {
						$endOfLine = $firstPart.", ".substr($lastPart, 0, $spacePos);
						$workingEndOfFile = substr($lastPart, $spacePos+1)."\n".$workingEndOfFile;
					} else $endOfLine = $firstLine;
				} else {
					$spacePos = strpos($firstLine, " ");
					if($spacePos !== FALSE) {
						$endOfLine = trim(substr($firstLine, 0, $spacePos));
						$workingEndOfFile = substr($firstLine, $spacePos+1)."\n".$workingEndOfFile;
					} else $endOfLine = $firstLine;
				}
			}
		//if there is an isolated character at the end of the line, remove it
		} else if($sLen > 2 && substr($endOfLine, $sLen-2, 1) == " ") $endOfLine = trim(substr($endOfLine, 0, $sLen-1));
		//echo "\nline 732, startOfFile: ".$startOfFile."\n\nendOfLine: ".$endOfLine."\n\nendOfFile: ".$endOfFile."\n\nworkingEndOfFile: ".$workingEndOfFile."\n\n";
		$commaPos = strpos($endOfLine, ',');
		if($commaPos !== FALSE) {
			$firstPart = trim(substr($endOfLine, 0, $commaPos));
			$secondPart = trim(substr($endOfLine, $commaPos+1));
			$sp = $this->getStateOrProvince(ucfirst($firstPart));
			if($sp) {//$i=0;foreach($sp as $p) echo "line 720, sp[".$i++."] = ".$p."\n";
				$state_province = $sp[0];
				$country = $sp[1];
				if(preg_match("/.*[., ]?\\b".$state_province."\\b[., ]?.*/i", $firstPart)) {
					$firstPart = trim(preg_replace("/[., ]?".$state_province."[., ]?/i", "", $firstPart));
					if(strcmp($country, "USA") == 0) {
						if(preg_match("/(.*)\\b(?:United States|U[,.]?S[,.]?A[,.]?)(.*)/i", $secondPart, $mats)) {
							$temp = trim($mats[2]);
							$secondPart = trim($mats[1]);
							if(strlen($temp) > 3) $endOfFile = trim($temp." ".$endOfFile);
						} else if(preg_match("/(.*)\\b(?:United States|U[,.]?S[,.]?A[,.]?)(.*)/i", $firstPart, $mats)) {
							$temp = trim($mats[2]);
							$secondPart = trim($mats[1]);
							if(strlen($temp) > 3) $endOfFile = trim($temp." ".$endOfFile);
						}
					} else if(preg_match("/(.*)\\b".$country."(.*)/i", $secondPart, $mats)) {
						$temp = trim($mats[2]);
						$secondPart = trim($mats[1]);
						if(strlen($temp) > 3) $endOfFile = trim($temp." ".$endOfFile);
					} else if(preg_match("/(.*)\\b".$country."(.*)/i", $firstPart, $mats)) {
						$firstPart = trim(trim($mats[1])." ".trim($mats[2]));
					}
				} else {
					$temp = "";
					$pos = stripos($endOfLine, $state_province);
					if($pos !== FALSE) {
						$temp = trim(substr($endOfLine, $pos+strlen($state_province)));
						if(strlen($temp) <= 3) $temp = trim(substr($endOfLine, 0, $pos));
					}
					if(strlen($temp) > 3) $endOfFile = trim($temp." ".$endOfFile);
				}
				if(strlen($firstPart) > 3) {
					$countyMatches = $this->findCounty($firstPart, $state_province);
					if($countyMatches != null) {
						$tsp = trim($countyMatches[4]);
						if($tsp) $state_province = $tsp;
						return array
						(
							'country' => $sp[1],
							'stateProvince' => $state_province,
							'county' => trim($countyMatches[1]),
							'endOfFile' => $startOfFile."\n".$endOfFile
						);
					}
				}
				if(strlen($secondPart) > 3) {
					$countyMatches = $this->findCounty($secondPart, $state_province);
					if($countyMatches != null) {
						$tsp = trim($countyMatches[4]);
						if($tsp) $state_province = $tsp;
						return array
						(
							'country' => $sp[1],
							'stateProvince' => $state_province,
							'county' => trim($countyMatches[1]),
							'endOfFile' => $startOfFile."\n".$endOfFile
						);
					}
				}
				$countyMatches = $this->findCounty($endOfFile, $state_province);
				if($countyMatches != null) {
					$tsp = trim($countyMatches[4]);
					if($tsp) $state_province = $tsp;
					return array
					(
						'country' => $sp[1],
						'stateProvince' => $state_province,
						'county' => trim($countyMatches[1]),
						'endOfFile' => $startOfFile."\n".trim($countyMatches[0])."\n".trim($countyMatches[3])
					);
				} else {
					return array
					(
						'country' => $country,
						'stateProvince' => $state_province,
						'county' => "",
						'endOfFile' => $startOfFile."\n".$endOfFile
					);
				}
			} else {//failed to find state or province in first part
				$sp = $this->getStateOrProvince(ucfirst($secondPart));
				if($sp) {
					$state_province = $sp[0];
					$countyMatches = $this->findCounty($firstPart, $state_province);
					if($countyMatches != null) {
						$tsp = trim($countyMatches[4]);
						if($tsp) $state_province = $tsp;
						return array
						(
							'country' => $sp[1],
							'stateProvince' => $state_province,
							'county' => trim($countyMatches[1]),
							'endOfFile' => $startOfFile."\n".$endOfFile
						);
					}
					$countyMatches = $this->findCounty($endOfFile, $state_province);
					if($countyMatches != null) {
						$tsp = trim($countyMatches[4]);
						if($tsp) $state_province = $tsp;
						return array
						(
							'country' => $sp[1],
							'stateProvince' => $state_province,
							'county' => trim($countyMatches[1]),
							'endOfFile' => $startOfFile."\n".trim($countyMatches[0])."\n".trim($countyMatches[3])
						);
					} else {
						return array
						(
							'country' => $sp[1],
							'stateProvince' => $state_province,
							'county' => "",
							'endOfFile' => $startOfFile."\n".$endOfFile
						);
					}
				}//failed to find state or province in first and second parts
				$countyMatches = $this->findCounty($firstPart);
				if($countyMatches != null) {
					return array
					(
						'country' => trim($countyMatches[2]),
						'stateProvince' => trim($countyMatches[4]),
						'county' => trim($countyMatches[1]),
						'endOfFile' => $startOfFile."\n".$endOfFile
					);
				}
				$countyMatches = $this->findCounty($secondPart);
				if($countyMatches != null) {
					return array
					(
						'country' => trim($countyMatches[2]),
						'stateProvince' => trim($countyMatches[4]),
						'county' => trim($countyMatches[1]),
						'endOfFile' => $startOfFile."\n".$endOfFile
					);
				}
				if($this->isCountryInDatabase($firstPart)) {
					return array
					(
						'country' => $firstPart,
						'stateProvince' => "",
						'county' => "",
						'endOfFile' => $startOfFile."\n".$endOfFile
					);
				}
				$country = $this->getCountry(trim($firstPart));
				if(!$country) $country = $this->getCountry(trim($secondPart));
				if($country) {
					return array
					(
						'country' => $country,
						'stateProvince' => "",
						'county' => "",
						'endOfFile' => $startOfFile."\n".$endOfFile
					);
				}
			}
		} else {//endOfLine has no comma
			$ps = $this->getStateOrProvince(ucFirst(trim($endOfLine)));
			if($ps) {
				$state_province = $ps[0];
				$l = strlen($state_province);
				if($l) {//echo "\nline 884, state_province: ".$state_province."\n";
					$pos = stripos($endOfLine, $state_province);
					if($pos !== FALSE) {
						if($pos < 3) {
							$rest = trim(substr($endOfLine, $pos+$l));
							if(strlen($rest) > 2) $endOfFile = $rest."\n".$workingEndOfFile;
						} else {
							$countyMatches = $this->findCounty($endOfLine, $state_province);
							if($countyMatches != null) {
								$tsp = trim($countyMatches[4]);
								if($tsp) $state_province = $tsp;
								$temp = trim($countyMatches[3]);
								if(strlen($temp) > 3) $endOfFile = trim($temp."\n".$endOfFile);
								return array
								(
									'country' => $ps[1],
									'stateProvince' => $state_province,
									'county' => trim($countyMatches[1]),
									'endOfFile' => $startOfFile."\n".$endOfFile
								);
							}
						}
					}
					$countyMatches = $this->findCounty($endOfFile, $state_province);
					if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\nline 908, countyMatches[".$i++."] = ".$countyMatche."\n";
						$tsp = trim($countyMatches[4]);
						if($tsp) $state_province = $tsp;
						$temp = trim($countyMatches[3]);
						if(strlen($temp) > 3) $endOfFile = trim($temp."\n".$endOfFile);
						return array
						(
							'country' => $ps[1],
							'stateProvince' => $state_province,
							'county' => trim($countyMatches[1]),
							'endOfFile' => $startOfFile."\n".trim($countyMatches[0])."\n".trim($countyMatches[3])
						);
					} else {
						return array
						(
							'country' => $ps[1],
							'stateProvince' => $state_province,
							'county' => "",
							'endOfFile' => $startOfFile."\n".$endOfFile
						);
					}
				}
			}
			if(preg_match("/(.*)\\s(?:C[o0](?:\\.|unty)|Par[il!|]sh|B[o0]r[o0]ugh|Dist(?:\\.|rict))/i", $endOfLine, $cMatches)) {
				$countyMatches = $this->findCounty($endOfLine, $state_province);
				if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\nline 933, countyMatches[".$i++."] = ".$countyMatche."\n";
					$temp = trim($countyMatches[3]);
					if(strlen($temp) > 3) $endOfFile = trim($temp."\n".$endOfFile);
					return array
					(
						'country' => trim($countyMatches[2]),
						'stateProvince' => trim($countyMatches[4]),
						'county' => trim($countyMatches[1]),
						'endOfFile' => $startOfFile."\n".$endOfFile
					);
				}
			} else {//endOfLine does not contain state, province or county
				$country = $this->getCountry($endOfLine);
				if($country) {
					$countyMatches = $this->findCounty($endOfFile, $state_province);
					if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\nline 948, countyMatches[".$i++."] = ".$countyMatche."\n";
						return array
						(
							'country' => $country,
							'stateProvince' => trim($countyMatches[4]),
							'county' => trim($countyMatches[1]),
							'endOfFile' => $startOfFile."\n".trim($countyMatches[0])."\n".trim($countyMatches[3])
						);
					} else {
						return array
						(
							'country' => $country,
							'stateProvince' => "",
							'county' => "",
							'endOfFile' => $startOfFile."\n".$endOfFile
						);
					}
				}
			}
		}
		$countyMatches = $this->findCounty($endOfFile, $state_province);
		if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\nline 969, countyMatches[".$i++."] = ".$countyMatche."\n";
			return array
			(
				'country' => trim($countyMatches[2]),
				'stateProvince' => trim($countyMatches[4]),
				'county' => trim($countyMatches[1]),
				'endOfFile' => $startOfFile."\n".trim($countyMatches[0])."\n".trim($countyMatches[3])
			);
		}
	}

	protected function processSciName($name) {//echo "\nInput to processSciName: ".$name."\n";
		if($name) {
			$name = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $name));
			$recordNumber = "";
			if(preg_match("/^No[.,*#-]?\\s([UIO!SQil0-9]{1,6}[a-z]?)[.,*#]?\\s(.*)/", $name, $mats)) {
				$recordNumber = trim(str_replace(array("l", "!", "I", "U", "O", "Q", "S"), array("1", "1", "1", "4", "0", "0", "5"), $mats[1]));
				$name = trim($mats[2]);
			} else if(preg_match("/^No[.,*#-]\\s?([UIO!SQil0-9]{1,6}[a-z]?)[.,*#]?\\s(.*)/", $name, $mats)) {
				$recordNumber = trim(str_replace(array("l", "!", "I", "U", "O", "Q", "S"), array("1", "1", "1", "4", "0", "0", "5"), $mats[1]));
				$name = trim($mats[2]);
			} else if(preg_match("/^([0-9]{1,4}) ?([A-Za-z])[.,*#-]\\s(.*)/", $name, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 526, mats[".$i++."] = ".$mat."\n";
				$recordNumber = trim($mats[1]).trim($mats[2]);
				$name = trim($mats[3]);
			} else if(preg_match("/^([0-9]{1,4} ?)[.,*#-]\\s(.*)/", $name, $mats)) {
				$recordNumber = trim($mats[1]);
				$name = trim($mats[2]);
			} else if(preg_match("/^([UIO!SQl0-9]{1,6}[a-z]?)[.,*#-]\\s(.*)/", $name, $mats)) {
				$recordNumber = trim(str_replace(array("l", "!", "I", "U", "O", "Q", "S"), array("1", "1", "1", "4", "0", "0", "5"), $mats[1]));
				$name = trim($mats[2]);
			} else if(preg_match("/^([0-9]{2,6}[A-Za-z]?)[.,*#-]?\\s(.*)/", $name, $mats)) {
				$recordNumber = trim($mats[1]);
				$name = trim($mats[2]);
			} else if(preg_match("/^([0-9]{1,5})[.,*#-]?\\s(.*)/", $name, $mats)) {
				$recordNumber = trim($mats[1]);
				$name = trim($mats[2]);
			} else if(preg_match("/^[^0-9]{0,6}+([0-9]{1,3}[A-Za-z]?)[.,*#-]\\s(.*)/", $name, $mats)) {
				$recordNumber = trim($mats[1]);
				$name = trim($mats[2]);
			} else if(preg_match("/^([A-Za-z]+)\\s\([A-Za-z]+\)\\s([A-Za-z]+.*)/", $name, $mats)) {
				$name = trim($mats[1])." ".trim($mats[2]);
			} else if(preg_match("/^[A-Za-z]+ \(([A-Za-z]+ [A-Za-z]+)\)$/", $name, $mats)) {
				$name = trim($mats[1]);
			}//echo "\nline 691, name: ".$name."\nrecordNumber: ".$recordNumber."\n";
			$results = array();
			$foundSciName = false;
			$words = array_reverse(explode(" ", $name));
			$wordcount = count($words);
			if($wordcount > 3) {
				if($this->containsVerbatimAttribute($name)) {
					$potentialVerbatimAtt = "";
					$potentialSubstrate = "";
					$potentialSciName = "";
					if(stripos($name, " strain") !== FALSE) {
						if(strcasecmp(trim($words[0]), "strain") == 0) {
							$potentialSciName = trim($words[$wordcount-1]." ".$words[$wordcount-2]);
							if($this->isPossibleSciName($potentialSciName)) {
								$foundSciName = true;
								$results['scientificName'] = $potentialSciName;
								$results['recordNumber'] = $recordNumber;
								$results['verbatimAttributes'] = trim(substr($name, strpos($name, $potentialSciName)+strlen($potentialSciName)));
							} else if($wordcount < 5) $results['verbatimAttributes'] = $name;
						} else if(preg_match("/(.+)\\s(contain(?:s|ing))\\s(.+)/i", $name, $mats)) {
							$potentialSciName = trim($mats[1]);
							$results['verbatimAttributes'] = trim($mats[2]);
							if($this->isPossibleSciName($potentialSciName)) {
								$foundSciName = true;
								$results['scientificName'] = $potentialSciName;
								$results['recordNumber'] = $recordNumber;
							}
						}
					} else if($wordcount > 5) {
						$firstPart = "";
						$endPart = "";
						if(preg_match("/^(.+)[;,] ?(on .+)$/i", $name, $mats)) {
							$firstPart = trim($mats[1]);
							$endPart = trim($mats[2]);
						} else if(preg_match("/^(.*[A-Za-z]{3,}) (on .+)$/i", $name, $mats)) {
							$firstPart = trim($mats[1]);
							$endPart = trim($mats[2]);
						}
						if($firstPart && $endPart) {//echo "\nline 729, firstPart: ".$firstPart."\nendPart: ".$endPart."\n";
							if($this->countPotentialLocalityWords($endPart) == 0) {
								$potentialSubstrate = $endPart;
								if($this->containsVerbatimAttribute($endPart)) {
									if(preg_match("/(on .+) ((?:UV|[KPC])[+-].*)/", $endPart, $mats2)) {
										$potentialVerbatimAtt = trim($mats2[2]);
										$potentialSubstrate = trim($mats2[1]);
									} else if(preg_match("/(on .+); (.*)/", $endPart, $mats2)) {
										$temp = trim($mats2[2]);
										if($this->containsVerbatimAttribute($temp)) {
											$potentialSubstrate = trim($mats2[1]);
											$potentialVerbatimAtt = $temp;
										}
									}
								}
							}
							if($this->containsVerbatimAttribute($firstPart)) {
								if(preg_match("/^(.+) ((?:UV|[KPC])[+-].*)$/", $firstPart, $mats2)) {
									$potentialVerbatimAtt = trim($mats2[2]);
									$potentialSciName = trim($mats2[1]);
								} else if(preg_match("/^(.+), ([A-Za-z]{3,})$/", $firstPart, $mats2)) {
									$temp = trim($mats2[2]);
									if($this->containsVerbatimAttribute($temp)) {
										$potentialVerbatimAtt = $this->mergeFields($potentialVerbatimAtt, $temp);
										$potentialSciName = trim($mats2[1]);
									}
								}
							}
							if(strlen($potentialSciName) == 0) $potentialSciName = $firstPart;
						} else if(preg_match("/(.+) ((?:UV|[KPC])[+-].*)/", $name, $mats)) {
							$potentialVerbatimAtt = trim($mats[2]);
							$potentialSciName = trim($mats[1]);
						}
					} else if(preg_match("/(.+) ((?:UV|[KPC])[+-].*)/", $name, $mats)) {
						$potentialVerbatimAtt = trim($mats[2]);
						$potentialSciName = trim($mats[1]);
					} else if(preg_match("/^(.+), ([A-Za-z]{3,})$/", $name, $mats2)) {
						$temp = trim($mats2[2]);
						if($this->containsVerbatimAttribute($temp)) {
							$potentialVerbatimAtt = $this->mergeFields($potentialVerbatimAtt, $temp);
							$potentialSciName = trim($mats2[1]);
						}
					}
					if(strlen($potentialSciName) == 0) $potentialSciName = trim($words[$wordcount-1]." ".$words[$wordcount-2]);
					if(!$foundSciName) {
						if($this->isPossibleSciName($potentialSciName)) {
							$foundSciName = true;
							$results['scientificName'] = $potentialSciName;
							$results['recordNumber'] = $recordNumber;
							if($potentialVerbatimAtt) $results['verbatimAttributes'] = trim($this->removeAuthority($potentialVerbatimAtt, $potentialSciName));
							if($potentialSubstrate) $results['substrate'] = $potentialSubstrate;
							if(!$potentialVerbatimAtt) {
								$verbatimAttributes = trim($this->removeAuthority(substr($name, strpos($name, $potentialSciName)+strlen($potentialSciName)), $potentialSciName));
								if(preg_match("/(.+), ?(on .+)/i", $verbatimAttributes, $mats)) {
									$temp = trim($mats[2]);
									if($this->countPotentialLocalityWords($temp) == 0) {
										if(!$potentialVerbatimAtt) $results['verbatimAttributes'] = trim($this->removeAuthority(trim($mats[1]), $potentialSciName));
										if(!$potentialSubstrate) $results['substrate'] = $temp;
									}
								}
							}
						}
					}
				}
			}//foreach($results as $k => $v) echo "\nline 795, ".$k.": ".$v."\n";
			if(!$foundSciName) {
				$potentialSciName = "";
				$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))|May|Jun[.e]|Jul[.y]|Aug(?:\\.|(?:ust))|Sep(?:\\.|(?:t\\.?)|(?:tember))|Oct(?:\\.|(?:ober))|Nov(?:\\.|(?:ember))|Dec(?:\\.|(?:ember))";
				if(preg_match("/(.*)\\s(var[.,*#�]?|ssp[.,*#�]?|subsp[.,*#�]?)\\s(.*)/i", $name, $mats)) {
					$potentialSciName = $mats[1];
				} else if(preg_match("/(.*)\\s(v[.,*#�]|f[.,*#�])(?! Gray\\b) (.*)/i", $name, $mats)) {
					$potentialSciName = $mats[1];
				}
				if($this->isPossibleSciName($potentialSciName)) {
					$foundSciName = true;
					$results['scientificName'] = $potentialSciName;
					$results['recordNumber'] = $recordNumber;
					$temp = trim($mats[3]);
					if(preg_match("/(.*?)((?:(?:(?:Fairly |Quite |Very |Not )?(?:(?:Un)?Common(?: and abundant)?|(?:Locally )?Abundant))|Found|Loose|Growing|Epi(?:phyt|lith)ic|Xylicolous)?\\son\\s.*)/i", $temp, $mats2)) {
						$substrate = trim($mats2[2]);
						if($this->countPotentialLocalityWords($substrate) == 0 && !preg_match("/\\b(?:".$possibleMonths.")[ -,]/", $substrate)) $results['substrate'] = $substrate;
						$temp = trim($mats2[1]);
						$spacePos = strpos($temp, " ");
						//the first word in the infraspecificEpithet should be lower case.  the rest is left as is
						if($spacePos !== FALSE) $temp = strtolower(substr(trim($temp), 0, $spacePos))." ".trim(substr($temp, $spacePos+1));
					} else if(preg_match("/(.*)(\\sSyn(?:\\.|onym|type) ?of\\s.*)/i", $temp, $mats2)) {
						$results['taxonRemarks'] = trim($mats2[2]);
						$temp = trim($mats2[1]);
						$spacePos = strpos($temp, " ");
						if($spacePos !== FALSE) $temp = strtolower(substr(trim($temp), 0, $spacePos))." ".trim(substr($temp, $spacePos+1));
					} else {
						$spacePos = strpos($temp, " ");
						if($spacePos !== FALSE) $temp = strtolower(substr($temp, 0, $spacePos));
						else $temp = strtolower($temp);
					}
					if(strlen($temp) > 3) {
						$results['taxonRank'] = str_replace(array("�", "*", "#", ",", "v.", "ssp"), array(".", ".", ".", ".", "var.", "subsp"), strtolower($mats[2]));
						$results['infraspecificEpithet'] = $temp;
					}
				}
			}
			if(!$foundSciName && preg_match("/(.*?)(\\sSyn(?:\\.|onym|type) ?of\\s.*)/i", $name, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 3101, mats[".$i++."] = ".$mat."\n";
				$potentialSciName = trim($mats[1]);
				if($this->isPossibleSciName($potentialSciName)) {
					$foundSciName = true;
					$results['scientificName'] = $potentialSciName;
					$results['recordNumber'] = $recordNumber;
				}
				$temp = trim($mats[2]);
				if(preg_match("/(.*)\\s(var[.,*#�]?|ssp[.,*#�]?|subsp[.,*#�]?) (.*)/i", $temp, $mats2)) {
					$results['taxonRemarks'] = $mats2[1];
					$temp = trim($mats2[3]);
				} else if(preg_match("/(.*)\\s(v[.,*#�]|f[.,*#�])(?! Gray\\b) (.*)/i", $temp, $mats2)) {
					$results['taxonRemarks'] = $mats2[1];
					$temp = trim($mats2[3]);
				} else {
					$results['taxonRemarks'] = $temp;
					$temp = "";
				}
				if(strlen($temp) > 3) {
					$results['taxonRank'] = str_ireplace(array("�", "*", "#", ",", "v.", "ssp"), array(".", ".", ".", ".", "var.", "subsp"), strtolower($mats2[2]));
					$results['infraspecificEpithet'] = $temp;
				}
			}
			if(!$foundSciName && preg_match("/(.*)(?:(?<! UV| [KPC])\+|(?:\\salong|\\stogether|\\sfound|\\sassociated)?\\swith\\s)(.+)/i", $name, $mats)) {
				$potentialSciName = trim($mats[1]);
				if($this->isPossibleSciName($potentialSciName)) {
					$foundSciName = true;
					$results['scientificName'] = $potentialSciName;
					$possibleATs = explode(",", trim($mats[2]));
					$associatedTaxa = "";
					$index = 0;
					foreach($possibleATs as $possibleAT) {
						$possibleAT = trim($possibleAT);
						if($this->isPossibleSciName($possibleAT)) {
							if($index++ == 0) $associatedTaxa = $possibleAT;
							else $associatedTaxa .= ", ".$possibleAT;
						} else if(preg_match("/(.*)\\s(on\\s.*)/i", $possibleAT, $mats2)) {
							$potentialAssTaxa = $mats2[1];
							if($this->isPossibleSciName($potentialAssTaxa)) {
								if($index++ == 0) $associatedTaxa = $potentialAssTaxa;
								else $associatedTaxa .= ", ".$potentialAssTaxa;
								$substrate = $mats2[2];
								//if(!preg_match("/\\b(?:HWY|Highway|road)\\b/i", $substrate)) $results['substrate'] = $substrate;
								if($this->countPotentialLocalityWords($substrate) == 0 && !preg_match("/\\b(?:".$possibleMonths.")[ -,]/", $substrate)) $results['substrate'] = $substrate;
							}
						}
					}
					if($associatedTaxa) $results['associatedTaxa'] = $associatedTaxa;
					$results['recordNumber'] = $recordNumber;
				}
			}
			if(!$foundSciName && preg_match("/(.{4,}?)((?:(?:(?:Fairly |Quite |Very |Not )?(?:(?:Un)?C[o0]mm[o0]n(?: and abundant)?|(?:Locally )?Abundant))|F[o0]und|L[o0]{2}se|Growing|Epi(?:phyt|lith)ic|Xylicolous|[o0]ccasi[o0]na[lI1!|])?+\\son\\s.*)/i", $name, $mats)) {
				$potentialSciName = $mats[1];
				if($this->isPossibleSciName($potentialSciName)) {
					$foundSciName = true;
					$results['scientificName'] = $potentialSciName;
					$results['recordNumber'] = $recordNumber;
					$substrate = trim($mats[2]);
					if($this->countPotentialLocalityWords($substrate) == 0 && !preg_match("/\\b(?:".$possibleMonths.")[ -,]/", $substrate)) $results['substrate'] = $substrate;
					else {
						$pos = strpos($substrate, ";");
						if($pos !== FALSE) {
							$substrate = trim(substr($substrate, 0, $pos));
							if($this->countPotentialLocalityWords($substrate) == 0 && !preg_match("/\\b(?:".$possibleMonths.")[ -,]/", $substrate)) $results['substrate'] = $substrate;
						}
						if(!array_key_exists('substrate', $results)) {
							while(preg_match("/(.+\\s[a-zA-Z]{3,})\\..+/", $substrate, $mats)) {
								$substrate = trim($mats[1]);
								if($this->countPotentialLocalityWords($substrate) == 0 && !preg_match("/\\b(?:".$possibleMonths.")[ -,]/", $substrate)) {
									$results['substrate'] = $substrate;
									break;
								}
							}
							if(!array_key_exists('substrate', $results)) {
								$pos = strrpos($substrate, ",");
								while($pos !== FALSE) {
									$substrate = trim(substr($substrate, 0, $pos));
									if($this->countPotentialLocalityWords($substrate) == 0 && !preg_match("/\\b(?:".$possibleMonths.")[ -,]/", $substrate)) {
										$results['substrate'] = $substrate;
										break;
									}
									$pos = strrpos($substrate, ",");
								}
							}
						}
					}
				}
			}
			if(!$foundSciName) {
				if($this->isPossibleSciName($name)) return array('scientificName' => $name, 'recordNumber' => $recordNumber);
				$name = str_ireplace(array("0", "1", "!", "|", "5", "2"), array("O", "l", "l", "l", "S", "Z"), $name);
				//echo "\nline 934, name: ".$name."\n";
				if(preg_match("/^([A-Za-z.]{4,}\\s[A-Za-z.]{3,})(.*)/i", $name, $mats)) {
					$potentialSciName =$mats[1];
					$rest = trim($mats[2]);
					if($this->isPossibleSciName($potentialSciName)) {
						$results['scientificName'] = $potentialSciName;
						$results['recordNumber'] = $recordNumber;
						$foundSciName = true;
					} else {
						$potentialSciName = str_replace("q", "g", $potentialSciName);
						if($this->isPossibleSciName($potentialSciName)) {
							$results['scientificName'] = $potentialSciName;
							$results['recordNumber'] = $recordNumber;
							$foundSciName = true;
						} else {
							$potentialSciName = str_replace(".", "", $potentialSciName);
							if($this->isPossibleSciName($potentialSciName)) {
								$results['scientificName'] = $potentialSciName;
								$results['recordNumber'] = $recordNumber;
								$foundSciName = true;
							} else {
								$potentialSciName = str_replace("G", "C", $potentialSciName);
								if($this->isPossibleSciName($potentialSciName)) {
									$results['scientificName'] = $potentialSciName;
									$results['recordNumber'] = $recordNumber;
									$foundSciName = true;
								}
							}
						}
					}
					if($foundSciName && $rest) {
						$rest = trim($this->removeAuthority($rest, $potentialSciName));
						if($this->countPotentialLocalityWords($rest) == 0 &&
							preg_match("/^((?:(?:(?:Fairly |Quite |Very |Not )?(?:(?:Un)?C[o0]mm[o0]n(?: and abundant)? |(?:Locally )?Abundant)) |F[o0]und |L[o0]{2}se |Growing |Epi(?:phyt|lith)ic |Xylicolous |[o0]ccasi[o0]na[lI1!|] )?on .*)/i", $name, $mats)) {
							$results['substrate'] = $rest;
						} else if($this->countPotentialHabitatWords($rest)) $results['habitat'] = $rest;
					}
				}
			}
			return $results;
		}
	}

	protected function removeAuthority($line, $sciname) {
		if($line) {
			if($sciname) {
				$sql = "SELECT author FROM taxa t WHERE t.sciName = '".$sciname."'";
				if($rs = $this->conn->query($sql)) {
					$author = "";
					if($r = $rs->fetch_object()) $author = $r->author;
					else {
						$sql = "SELECT author FROM taxa t WHERE t.sciName = '".$this->formatSciname($sciname)."'";
						if($rs = $this->conn->query($sql)) {
							if($r = $rs->fetch_object()) $author = $r->author;
						}
					}
					if($author) {
						if(preg_match("/(.*)\(?".preg_quote($author, '/')." ?\)?(.*)/is", $line, $mats)) return trim(trim($mats[1])." ".trim($mats[2]));
						if(preg_match("/(.*)\(?".preg_quote(trim($author, " .,"), '/')." ?\)?(.*)/is", $line, $mats)) return trim(trim($mats[1])." ".trim($mats[2]));
						if(preg_match("/(.*)\(?".str_replace(". ", ".", $author)." ?\)?(.*)/is", $line, $mats)) return trim(trim($mats[1])." ".trim($mats[2]));
						if(preg_match("/(.*)\(?".str_replace(".", "", $author)." ?\)?(.*)/is", $line, $mats)) return trim(trim($mats[1])." ".trim($mats[2]));
					}
				}
			}
			return $line;
		} return "";
	}

	protected function isPossibleSciName($name) {//echo "\nInput to isPossibleSciName: ".$name."\n";
		$name = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $name), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
		$numPat = "/(.*)\\s\\w\\s(.*)/";
		if(preg_match($numPat, $name, $ns)) $name = trim($ns[1])." ".trim($ns[2]);
		$fPos = strpos($name, "�");
		if($fPos !== FALSE && $fPos < 9) $name = trim(substr($name, $fPos+1));
		$name = preg_replace("/([A-Za-z]) [ceo]f\\. ([A-Za-z])/i", "\${1} \${2}", $name);
		$name = str_ireplace(" cf ", " ", $name);
		//echo "\nInput to isPossibleSciName2: ".$name."\n";
		if(strlen($name) > 2 && !preg_match("/\\b(?:on|var\\.?|strain|contains|subsp\\.?|ssp\\.?|f\\.)\\b/i", $name)) {
			$name = trim(str_replace(array("\"", "'"), "", $name));
			$sql = "";
			if(strpos($name, " ") !== FALSE) $sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name."'";
			else $sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name."' and t.rankId > 140";
			if($r2s = $this->conn->query($sql)) if($r2s->num_rows) return true;
			$pos = strpos($name, " ");
			if($pos !== FALSE) {
				$words = explode(" ", $name);
				$wordCount = count($words);
				$word0 = str_replace(array("\"", "'"), "", trim($words[0]));
				$word1 = str_replace(array("\"", "'"), "", trim($words[1]));
				if($wordCount == 2 || ($wordCount == 3 && strlen($words[2]) < 9)) {
					$sql = "";
					if(strcasecmp($word0, "Lichen") == 0) $sql = "SELECT * FROM taxa t WHERE t.sciName = '".$word0." ".$word1."'";
					else $sql = "SELECT * FROM taxa t WHERE t.sciName = '".$word0."'";
					if($r2s = $this->conn->query($sql)) if($r2s->num_rows) return true;
					if(strlen($word1) > 3 && strcasecmp($word1, "florida") != 0 && strcasecmp($word1, "americani") != 0
						&& strcasecmp($word1, "clara") != 0 && strcasecmp($word1, "barbara") != 0
						&& strcasecmp($word1, "superior") != 0 && strcasecmp($word1, "minnesota") != 0
						 && strcasecmp($word1, "acre") != 0 && strcasecmp($word1, "sterile") != 0
						 && strcasecmp($word1, "montana") != 0 && strcasecmp($word1, "louisiana") != 0 && strcasecmp($word1, "california") != 0) {
						$sql = "SELECT * FROM taxa t WHERE t.unitName2 = '".$word1."'";
						if($r2s = $this->conn->query($sql)) if($r2s->num_row) return true;
					}
				}
				if($wordCount > 3) {
					$l0 = strlen($word0);
					$l1 = strlen($word1);
					$word2 = str_replace(array("\"", "'"), "", trim($words[2]));
					$l2 = strlen($word2);
					if($l1 < 4) {
						$name2 = str_replace(array("\"", "'"), "", $word0." ".trim($word1).trim($words[2]));
						$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name2."'";
						if($r2s = $this->conn->query($sql)) if($r2s->num_rows) return true;
						$name2 = str_replace(array("\"", "'"), "", $word0.trim($word1)." ".trim($words[2]));
						$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name2."'";
						if($r2s = $this->conn->query($sql)) if($r2s->num_rows) return true;
					} else if($l0 < 4) {
						$name2 = str_replace(array("\"", "'"), "", $word0.trim($word1)." ".trim($words[2]));
						$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name2."'";
						if($r2s = $this->conn->query($sql)) if($r2s->num_rows) return true;
					} else if($l2 < 4) {
						$name2 = str_replace(array("\"", "'"), "", $word0." ".trim($word1).trim($words[2]));
						$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name2."'";
						if($r2s = $this->conn->query($sql)) if($r2s->num_rows) return true;
					}
					if($l0 < 4 || $l1 < 4 || $l2 < 4) {
						$name2 = str_replace(array("\"", "'"), "", $word0.trim($word1).trim($words[2]));
						$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name2."'";
						if($r2s = $this->conn->query($sql)) if($r2s->num_rows) return true;
					}
				}
				if($wordCount < 7) {
					$name2 = trim($word0." ".trim($word1));
					$rest = trim(substr($name, stripos($name, $name2)+strlen($name2)));
					$name2 = trim(str_replace(array("1", "!", "|", "5", "0", "\"", "'"), array("l", "l", "l", "S", "O", "", ""), $name2));
					if($this->countPotentialLocalityWords($rest) == 0 && $this->countPotentialHabitatWords($rest) == 0) {
						if($wordCount < 5) {
							$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name2."'";
							if($r2s = $this->conn->query($sql)) if($r2s->num_rows) return true;
						} else if((strpos($name, "(") !== FALSE && strpos($name, ")") !== FALSE) ||
							strpos($name, "&") !== FALSE || strpos($name, ".") !== FALSE ||
							strpos($name, " ex ") !== FALSE || strpos($name, " et ") !== FALSE) {
							$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name2."'";
							if($r2s = $this->conn->query($sql)) if($r2s->num_rows) return true;
						}
					}
				}
				if(preg_match("/^\\w{3,24}\\s\\w{3,24}\\s\([a-zA-Z01���?&. ]{1,14}\\.?\\s?\)\\s?(?:[A-Z]\\.\\s){0,2}[a-zA-Z01��?&. ]{2,19}\\.?$/", $name)) {
					//echo "\nline 1389, match\n";
					return true;
				}
				if(preg_match("/^[a-zA-Z01]{6,24} spp?\\b/", $name)) return true;
				if(preg_match("/^[a-zA-Z01]{3,24} spp?\\./", $name)) return true;
			}
		}
		return false;
	}

	protected function formatSciName($scientificName) {
		if($scientificName) {
			$scientificName = trim($scientificName, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			$spacePos = strpos($scientificName, " ");
			if($spacePos !== FALSE) {
				$firstWord = ucfirst(strtolower(str_ireplace(array("!", "1", "|", "0", "."), array("l", "l", "l", "o", ""), trim(substr($scientificName, 0, $spacePos)))));
				$secondWord = trim(substr($scientificName, $spacePos));
				$spacePos = strpos($secondWord, " ");
				if($spacePos !== FALSE) {
					$rest = trim(substr($secondWord, $spacePos));
					$secondWord = trim(substr($secondWord, 0, $spacePos));
					return $firstWord." ".strtolower($secondWord)." ".$rest;
				} else {
					return $firstWord." ".strtolower($secondWord);
				}
			} else return ucfirst(strtolower(str_ireplace(array("!", "1", "|", "0", "."), array("l", "l", "l", "o", ""), $scientificName)));
		}
	}

	protected function formatDate($date) {
		if(array_key_exists('year', $date)) {
			$year = $date['year'];
			if(is_numeric($year)) {
				if($year > 2014) return "";
				if($year < 1800) return "";
			} else return "";
			$result = $year;
			if(array_key_exists('month', $date)) {
				$month = $date['month'];
				if(is_numeric($month)) {
					if($month > 12) return "";
				}
				$result .= "-".$month;
				if(array_key_exists('day', $date)) {
					$day = trim($date['day']);
					if(is_numeric($day)) {
						if($day > 31) return "";
					}
					$result .= "-".$day;
				} else $result .= "-00";
			} else $result .= "-00-00";
			return $result;
		}
		return "";
	}

	private function max_date($dateArr) {
		$resultDate = array();
		$resultIndex = 0;
		foreach($dateArr as $date) {
			if(array_key_exists('year', $resultDate)) {
				$maxYear = $resultDate['year'];
				$y = $date['year'];
				if($y > $maxYear) $resultDate = $date;
				else if($y == $maxYear) {
					if(array_key_exists('month', $date)) {
						if(array_key_exists('month', $resultDate)) {
							$month = $resultDate['month'];
							if(is_numeric($month)) $maxMonth = $month;
							else $maxMonth = $this->getNumericMonth($month);
							$m = $date['month'];
							if(is_numeric($m)) $nm = $m;
							else $nm = $this->getNumericMonth($m);
							if($nm > $maxMonth) $resultDate = $date;
							else if($nm == $maxMonth) {
								if(array_key_exists('day', $date)) {
									if(array_key_exists('day', $resultDate)) {
										$maxDay = $resultDate['day'];
										$d = $date['day'];
										if($d > $maxDay) $resultDate = $date;
									} else $resultDate = $date;
								}
							}
						} else $resultDate = $date;
					}
				}
			} else $resultDate = $date;
		}
		return $resultDate;
	}

	private function min_date($dateArr) {
		$resultDate = array();
		$resultIndex = 0;
		foreach($dateArr as $date) {
			if(array_key_exists('year', $resultDate)) {
				$maxYear = $resultDate['year'];
				$y = $date['year'];
				if($y < $maxYear) $resultDate = $date;
				else if($y == $maxYear) {
					if(array_key_exists('month', $resultDate)) {
						if(array_key_exists('month', $date)) {
							$month = $resultDate['month'];
							if(is_numeric($month)) $maxMonth = $month;
							else $maxMonth = $this->getNumericMonth($month);
							$m = $date['month'];
							if(is_numeric($m)) $nm = $m;
							else $nm = $this->getNumericMonth($m);
							if($nm < $maxMonth) $resultDate = $date;
							else if($nm == $maxMonth) {
								if(array_key_exists('day', $resultDate)) {
									if(array_key_exists('day', $date)) {
										$maxDay = $resultDate['day'];
										$d = $date['day'];
										if($d < $maxDay) $resultDate = $date;
									} else $resultDate = $date;
								}
							}
						} else $resultDate = $date;
					}
				}
			} else $resultDate = $date;
		}
		return $resultDate;
	}

	private function getDigitalLatLong($latlong) {
		$degs = 0;
		$digMins = 0;
		$digSecs = 0;
		$direction = "";
		$degPos = strpos($latlong, "�");
		if($degPos !== FALSE) {
			$minPos = strpos($latlong, "'");
			if($minPos !== FALSE) {
				$secPos = strpos($latlong, "\"");
				if($secPos !== FALSE) {
					$secs = trim(substr($latlong, $minPos+1, $secPos-$minPos-1));
					if($secs > 60) return null;
					$digSecs = $secs/3600;
					$direction = trim(substr($latlong, $secPos+1));
				} else $direction = trim(substr($latlong, $minPos+1));
				$mins = trim(substr($latlong, $degPos+1, $minPos-$degPos-1));
				if($mins > 60) return null;
				$digMins = $mins/60;
			} else $direction = trim(substr($latlong, $degPos+1));
			$degs = trim(substr($latlong, 0, $degPos));
		}
		if(strcasecmp(substr($direction, 0, 1), 'W') == 0 || strcasecmp(substr($direction, 0, 1), 'S') == 0) return -1*($degs + $digMins + $digSecs);
		else return $degs + $digMins + $digSecs;
		return null;
	}

	protected function containsNumber($s) {
		return preg_match("/\\d+/", $s);
	}

	protected function containsText($s) {
		return preg_match("/[A-Za-z]+/", $s);
	}

	protected function isText($s) {
		$splitChars = str_split($s);
		foreach($splitChars as $splitChar) {
			$ord = ord($splitChar);
			if(($ord < 65 && $ord != 46) || ($ord > 90 && $ord < 97) || $ord > 122) return FALSE;
		}
		return TRUE;
	}

	protected function combineArrays($array1, $array2) {//combines 2 arrays.  Unlike the PHP array_merge function, if the second array has a value it overwrites
		if($array1 && $array2) {
			$result = array();
			foreach($array2 as $k2 => $v2) {
				if(!is_array($v2) && $v2) $result[$k2] = $v2;
			}
			foreach($array1 as $k1 => $v1) if(!array_key_exists($k1, $result) && !is_array($v1) && $v1) $result[$k1] = $v1;
			return $result;
		} else if($array1) return $array1;
		else if($array2) return $array2;
		else return array();
	}

	protected function isMostlyGarbage2($s, $cutoff) {
		if($s) {
			if(stripos($s, "Failed OCR return") !== FALSE) return true;
			$total = 0;
			$good = 0;
			foreach (count_chars($s, 1) as $i => $val) {
				$total++;
				if(($i > 47 && $i < 58) || ($i > 64 && $i < 91) || ($i > 96 && $i < 123) || $i == 32) $good++;
			}
			if(round($good/$total, 2) < $cutoff) return true;
			else return false;
		}
		return false;
	}

	protected function isMostlyGarbage($s, $cutoff) {
		if($s) {
			$total = 0;
			$good = 0;
			foreach (count_chars($s, 1) as $i => $val) {
				$total++;
				if(($i > 47 && $i < 58) || ($i > 64 && $i < 91) || ($i > 96 && $i < 123) || $i == 32) $good++;
			}
			if(round($good/$total, 2) < $cutoff) return true;
			else return false;
		}
		return false;
	}

	protected function isCompleteGarbage($s) {
		if($s) {
			foreach (count_chars($s, 1) as $i => $val) {
				if(($i > 47 && $i < 58) || ($i > 64 && $i < 91) || ($i > 96 && $i < 123)) return false;
			}
		}
		return true;
	}

	protected function countPotentialLocalityWords($pLoc) {//echo "\ninput to countPotentialLocalityWords: ".$pLoc."\n";
		//$pLoc = preg_quote(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $pLoc), '/');
		$pLoc = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $pLoc));
		$lWords = array("/road(?:side|way)?\\b/i", "/\\bHighway\\b/i", "/\\bhwa?y\\b/i", "/ A(?i):rea\\b/", "/path\\b/i", "/\\br(?:ou)?te\\b/i",
			"/\\sCity\\b/i", "/\\bLoca(?:t(?:ed|ion)|lity)\\b/i", "/\\bmi(?:les?)?\\b/i", "/ L(?i)odge\\b/", "/ T(?i)rail\\b/", "/ A(?i)rboretum\\b/",
			"/\\bkm\\b/i", "/\\binternational\\b/i", "/ P(?i)arks?\\b/", "/ I(?i)sl(?:e|ands?)/", "/\\bcamp(?:grounds?)?\\b/i",
			"/ F(?i)alls/", "/\\bcounty\\b/i", "/\\bdistrict\\b/i", "/\\bjunction\\b/i", "/\\sC(?i)anyon\\b/", "/ C(?i)reek\\b/", "/\\bM(?i)ont\\b(?!(?:\\.|\)))/",
			"/ L(?i)oop\\b/", "/\\bservice\\b/", "/\\sstation\\b/", "/\\btown\\b/", "/\\bC(?i)oast\\b/", "/\\bS(?i)hore\\b/", "/\\bproperty\\b/i",
			"/\\bpeninsula\\b/i", "/\\bentrance\\b/i", "/\\bL(?i)akes?\\b/", "/\\sW(?i)ilderness\\b/", "/\\sR(?i)ange\\b/", "/\\bPass\\b/",
			"/\\b(?:N(?i)ationa[l1|I!]|(?-i)S(?i)t(?:\\.|ate)|(?-i)N(?i)at[l1|I!]?\\.)\\s(?-i)F(?i)orest\\b/", "/\\bvi[ce](?:\\.|init(?:y|ate)\\b)/i",
			"/\\sP(?i)eak\\b/", "/\\sS(?i)prings\\b/", "/\\bU\\.?S\\.? ?\\d{1,2}\\b/", "/\\bcamino\\b/", "/[A-Za-z]{3,} G(?i)ulch\\b/",
			"/\\b(?:N(?i)ationa[l1|I!]|(?-i)S(?i)t(?:\\.|ate)|(?-i)N(?i)at[l1|I!]?\\.|(?-i)P(?i)rov[l1|I!]nc[l1|I!]a[l1|I!])\\s(?-i)P(?i)ark\\b/i",
			"/\\bdrive\\b/i", "/[A-Za-z]{3,} R(?i)eserv(?:e|oir)\\b/", "/[A-Za-z]{3,} B(?i)utte\\b/", "/[A-Za-z]{3,} V(?i)alley\\b/",
			"/\\bReserve\\b:? ?/", "/ P(?i)reserve\\b/", "/\\bR(?i)d\\b/", "/\\bR(?i)egion(?:a[l1|I!])?\\b/", "/subdivision/i", "/C(?i)ape/",
			"/\\b[O0](?i)utlook\\b/", "/\\b(?:inter)?section\\b/i", "/\\bWildlife Management\\b/", "/\\sR(?i)anch\\b/", "/\\sP(?i)lantation\\b/",
			"/\\sstreet\\b/i", "/ Ave\\b/i", "/\\sLane\\b/i", "/ Divide\\b/i", "/\\bM(?:t\\.?|(?i)ount) /", "/[A-Za-z]{3,} I(?i)nlet\\b/",
			"/[A-Za-z] M(?:ts[,.]{0,2}|(?i)ountains?)\\b/", "/\\b(?:Conference|Visitors|Environmenta[l1|I!]) Center\\b/i", "/\\sR(?i)idge\\b/",
			"/\\b(?i:N(?:orth(?:east|west)?)?|S(?:outh(?:east|west)?)?|E(?:ast)?|W(?:est)?|[NE]?NE|[NW]?NW|[SE]?SE|[SW]?SW) (?:of|from)/i",
			"/\\b[A-Za-z]{3,}v[l1|I!]{3}e\\b/i", "/\\b[A-Z][A-Za-z]{2,}t[o0]wn\\b/i", "/~ ?\\d+[ -]/", "/ Gruppe\\b/", "/ A(?i)rea\\b/", "/ quarry/i",
			"/ T(?i)rail/", "/ B(?i)ay\\b/", "/[A-Za-z] A(?i)rboretum\\b/", "/ R(?i)iver\\b/", "/[A-Za-z]{3,} L(?i)ane\\b/", "/ L(?i)ac\\b/",
			"/[A-Za-z]{3,} R(?i)ock\\b/", "/[A-Za-z]{3,} K(?i)eys?\\b/", "/[A-Za-z]{3,} S(?i)ound\\b/", "/\\bstate [1-9]{1,3}[,.; ]/i",
			"/\\bprovince\\b/i", "/\\b(?:University|Co[l1|I!]{2}ege) (?:[A-Za-z]+ ){0,4}Campus\\b/", "/[A-Za-z]{3,} P(?i)eaks?/",
			"/F(?i)[ij]ord/", "/\\b(?<!Parish:)(?<!Parish: )(?<!Parish )S(?:ainte?|t\\.) (?![A-Za-z]+ Parish)/i", "/G(?i)[o0]rg[ec]/",
			"/\\bF(?i)(?:ort|(?<![0-9] F)t\\.(?! (?:alt(?:itude)?\\b|elev))) (?-i)[A-Z]/", "/[A-Za-z]{3,} H(?i)arbor/", "/[A-Z][A-Za-z]{3,} S(?i)ea/");
		$result = 0;
		foreach($lWords as $lWord) if(preg_match($lWord, $pLoc)) {/*echo "\nlocality matched: ".$lWord."\n";*/$result++;}
		if(preg_match("/\\b(?:N(?:[EW]|orth(?:east|west)?)?|S(?:[EW]|outh(?:east|west)?)?|E(?:ast)?|W(?:est)?)\\s[o0QD]f\\s.+/i", $pLoc)) $result++;
		if($this->containsNumber($pLoc) && $result &&
			!preg_match("/\\b(?:Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)\\b/i", $pLoc) &&
			!preg_match("/\\d{1,2}\/\\d{1,2}\/(?:\\d{2}|\\d{4})/", $pLoc) && !preg_match("/\\d ?�/", $pLoc)) $result++;
		return $result/(count(explode(" ", $pLoc))*count($lWords));
		//return $result/count(explode(" ", $pLoc));
	}

	protected function countPotentialHabitatWords($pHab) {//echo "\ninput to countPotentialHabitatWords: ".$pHab."\n";
		//$pHab = preg_quote(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $pHab), '/');
		$pHab = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $pHab));
		$hWords = array("rocks?", "quercus", "(?:hard)?woods?", "aspens?", "juniper(?:u?s)?", "p[l1|I!]ant(?! (?:sciences?|bio[l1|I!]ogy|exp[l1|I!]oration))",
			"understory", "grass(?:[l1|I!]and|es)?", "meadows?", "(?<!(?:National) )forest(?:ed)?", "ground", "mixed", "(?<!Jessie\\s)sa[l1|I!]ix",
			"a[l1|I!]ders?", "tundra","abies", "ca[l1|I!]careous", "outcrops?", "slop(?:ed|ing)", "dominated by", "bou[l1|I!]ders",
			"(?<!\()(?<!Co[l1|I!]orado )(?<!City of )(?<![NS]\\.[EW]\\. of )(?<!(?:North|South) of )(?<!(?:East|West) of )Bou[l1|I!]der(?!(?:\)| Co[l1|I!]orado| Creek| Canyon))",
			"Granit(?:e|ic)", "[l1|I!]imestone", "sandstone", "sand[ys]?", "cedars?", "trees?", "shrubs?", "(?:(?:sub)?a[l1|I!])?pine",
			"soi[l1|I!]s?", "(?:white)?bark", "open", "deciduous", "expos(?:ure|ed)", "aspect", "facing", "pinus", "habitat", "degrees?",
			"conifer(?:(?:ou)?s)?", "spruces?", "map[l1|I!]es?", "substrate", "th[uv]ja", "shad(?:y|ed?)", "(?:[a-z]{2,})?(?<!New)berry",
			"box e[l1|I!]ders?", "dry", "damp", "moist", "wet", "firs?", "basalt(?:ic)?", "Liriodendron", "Juglans", "A[l1|I!]nus",
			"f[l1|I!][0o]{2}d ?p[l1|I!]ain", "gneiss", "moss(?:es|y)?", "crust", "(?:sage|brush|sagebrush)", "pocosin", "bog", "swamp", "branches",
			"Picea", "savanna", "Magno[l1|I!]ia", "Rhododendron", "[l1|I!]{2}ex", "Carpinus", "ta[l1|I!]us", "Nyssa", "bottom(?:[l1|I!]ands?)?",
			"w[l1|I!]{3}[0o]ws?", "riperian", "Fraxinus", "Betu[l1|I!]a", "Persea", "Carya", "ravine", "Aescu[l1|I!]us", "cypress(?:es)?", "Empetrum",
			"Taxodium", "sparse(?:[l1|I!]y)?", "chaparra[l1|I!]", "temperate", "[5S]phagnum", "hem[l1|I!]ocks?", "Myrica", "[l1|I!]odgepo[l1|I!]e",
			"Cornus", "myrt[l1|I!]es?", "Gordonia", "Liquidamber", "cottonwoods?", "pasture", "stump", "pa[l1|I!]metto", "(?:mica)?schist(?:ose)?",
			"[l1|I!]itter", "scrub", "spp", "rotten", "logs?", "quartz(?:ite)?", "travertine", "grave[l1|I!](?! r(?:oa)?d)(?:[l1|I!]y)?", "duff",
			"seepage", "submerged", "graminoids", "forbs", "mound", "ferns?", "mahogany", "cherry", "regenerating", "introduced",
			"(?:Pseudo)?tsuga", "timber(?:[l1|I!]ine)?", "terraces?", "thickets?", "moraines?", "heath(?:er)?", "metamorphic",
			"vegetation", "quarry", "mats?", "depression", "ecotone", "fen", "Ombrotrophic", "rivulets?", "trunks?", "hummock[sy]?",
			"acer", "stand", "chert", "humus", "marsh", "abundant(?:[l1|I!]y)?", "pebbles?", "imbedded", "pools?", "twigs?",
			"cu[l1|I!]tivat[ec]d", "Agropyron", "barrens?", "climax", "Carex", "prairie", "crevices?", "[l1]oam", "shal[e|y]");
		$result = 0;
		foreach($hWords as $hWord) if(preg_match("/\\b".$hWord."\\b/i", $pHab)) {/*echo "\nhabitat matched: ".$hWord." with ".$pHab."\n";*/$result++;}
		return $result/(count(explode(" ", $pHab))*count($hWords));
		//return $result/count(explode(" ", $pHab));
	}

	protected function containsVerbatimAttribute($pAtt, $additionalTerms=array()) {
		//additional terms can be added to those strings that have already been discovered to contain verbatimAttributes
		$pAtt = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $pAtt));
		//if(strpos($pAtt, "|") !== FALSE || strpos($pAtt, "/") !== FALSE || strpos($pAtt, "\"") !== FALSE) $pAtt = preg_quote($pAtt, '/');
		$vaWords = array("atranorin", "fatty acids?", "cortex", "areo[l1|I!]ate", "medu[l1|I!]{2}ae?", "podeti(?:a|um)(?! ?\\/)", "dendrit(?:e|ic)",
			"(?:(?:a|hy)po|epi)theci(?:a|um)(?! ?(?:\\/|co[l1|I!]or))", "(?<!\/ )(?<!\/)asc(?:i|us)", "scabrous", "aggregated",
			"hya[l1|I!]ine(?! ?\\/)", "tha[l1|I!]{2}(?:us|i)", "strain", "dis[ck]s?(?! (?:convex\\/|co[l1|I!]or))", "squamu[l1|I!](?:es?|ose)",
			"soredi(?:a(?:te)?|um)", "fruticose", "fruit(?:icose|s|ing)?", "crust(?:ose)?", "(?:cort|sax|terr)ico[l1|I!]ous",
			"chemotype", "terpenes?", "variegated", "isidi(?:a(?:te)?|um)", "TLC", "parietin", "anthraquinones?", "pigment(?:s|ed)?",
			"sora[l1|I!]ia", "ostio[l1|I!]es?", "spores", "c[l1|I!]uster(?:s|ed)", "excip[l1|I!]e", "paraphyses(?! ?branched\\/)", "fo[l1|I!]iose",
			"pruinose", "Chemica[l1|I!] contents", "ci[l1|I!]iate", "steri[l1|I!]e", "septate(?! ?\\/)", "So[l1|I!]vent", "[A-Za-z]ic acids?");
		//foreach($vaWords as $vaWord) if(stripos($word, $vaWord) !== FALSE) return true;
		foreach($vaWords as $vaWord) if(preg_match("/\\b".$vaWord."\\b/i", $pAtt)) return true;
		foreach($additionalTerms as $additionalTerm) if(preg_match("/\\b".$additionalTerm."\\b/i", $pAtt)) return true;
		if(preg_match("/\\b[KPC][+-]\\B/", $pAtt)) return true;
		if(preg_match("/\\bUV[+-]\\B/", $pAtt)) return true;
		if(preg_match("/\\bPD[+-]\\B/", $pAtt)) return true;
		if(preg_match("/\\bHC[Il][+-]\\B/", $pAtt)) return true;
		return false;
	}

	protected function convertSlashedDates($string) {//echo "\nInput to convertSlashedDates: ".$string."\n";
		$tokens = explode("/", $string);
		$c = count($tokens);
		if($c) {
			$year = $tokens[$c-1];
			if(strlen($year) == 2) $year = "19".$year;
			if($c == 3) return $year."-".str_pad($tokens[0], 2, "0", STR_PAD_LEFT)."-".str_pad($tokens[1], 2, "0", STR_PAD_LEFT);
			if($c == 2) return $year."-".str_pad($tokens[0], 2, "0", STR_PAD_LEFT);
			return $year;
		}
		return "";
	}

	protected function isStateOrProvince($sp) {
		if($sp) {
			$sql = 'SELECT geothesid FROM geographicthesaurus WHERE geolevel = 60 AND geoterm = "'.str_replace(array('"', "'"), '', $sp).'"';
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()) return true;
				$rs->free();
			}
		}
		return false;
	}

	protected function isCountryInDatabase($c) {//echo "\nInput to isCountryInDatabase: ".$c."\n";
		if($c) {
			if(strcasecmp($c, "NEW GUINEA") == 0) return true;
			if(strcasecmp($c, "FORMOSA") == 0) return true;
			if(strcasecmp($c, "TAIWAN") == 0) return true;
			if(preg_match("/\\bU\\.?S\\.?S\\.?R\\b\\.?/", $c)) return true;
			$sql = 'SELECT geothesid FROM geographicthesaurus WHERE geolevel = 50 AND geoterm = "'.str_replace(array('"', "'"), '', $c).'"';
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()) return true;
				$rs->free();
			}
		}
		return false;
	}

	protected function getStatesFromCountry($c) {
		$cResult = array();
		if($c) {
			$sql = 'SELECT s.geoterm FROM geographicthesaurus s INNER JOIN geographicthesaurus c ON (s.parentID = c.geoThesID) WHERE s.geoLevel = 60 AND c.geoterm = "'.str_replace(array('"', "'"), '', $c).'"';
			if($rs = $this->conn->query($sql)) {
				while($r = $rs->fetch_object()) array_push($cResult, $r->geoterm);
				$rs->free();
			}
		}
		return $cResult;
	}

	protected function getCounties($s) {
		$cResult = array();
		if($s) {
			$sql = 'SELECT co.geoterm FROM geographicthesaurus co INNER JOIN geographicthesaurus s ON (co.parentID = s.geoThesID) WHERE co.geoLevel = 70 AND s.geoterm = "'.str_replace(array('"', "'"), '', $s).'"';
			if($rs = $this->conn->query($sql)) {
				while($r = $rs->fetch_object()) array_push($cResult, ucwords($r->geoterm));
				$rs->free();
			}
		}
		return $cResult;
	}

	protected function getStateFromCounty($c, $ss=null, $country="") {
		if($c) {
			$sql = 'SELECT cr.geoterm AS countryName, sp.geoterm AS stateName
				FROM geographicthesaurus cr INNER JOIN geographicthesaurus sp ON cr.geoThesID = sp.parentID
				INNER JOIN geographicthesaurus c ON sp.geoThesID = c.parentID
				WHERE c.geoterm = "'.str_replace(array('"', "'"), '', $c).'"';
			if($country) $sql .= ' AND cr.geoterm = "'.str_replace(array('"', "'"), '', $country).'"';
			if($r2s = $this->conn->query($sql)) {
				$num_rows = $r2s->num_rows;
				if($num_rows) {
					if($num_rows == 1) if($r2 = $r2s->fetch_object()) return array($r2->stateName, $r2->countryName);
					else {
						$shortest = 0;
						$closest = '';
						if(count($ss)) {
							while($r2 = $r2s->fetch_object()){
								foreach($ss as $s) {
									$lev = levenshtein($s, $r2->stateName);
									//echo "\nlevenshtein: ".$lev.", state: ".$s."\n";
									if($lev == 0) return $s;
									else if ($lev <= $shortest || $shortest == 0) {
										$closest  = array($s, $r2->countryName);
										$shortest = $lev;
									}
								}
							}
							return $closest;
						}
					}
				}
				$r2s->free();
			}
		}
		return array();
	}

	protected function getCountryFromState($state) {
		if($state) {
			$sql = 'SELECT c.geoTerm AS countryName FROM geographicthesaurus s INNER JOIN geographicthesaurus c ON s.parentID = c.geoThesID WHERE s.geoTerm = "Arizona"';
			if($rs = $this->conn->query($sql)) {
				if($r2 = $rs->fetch_object()) return $r2->countryName;
				$rs->free();
			}
		}
		return '';
	}

	protected function getPolticalInfoFromCounty($c, $ss=null) {
		if($c) {
			$result = array();
			$pos = strripos($c, " ");
			if($pos !== FALSE) {
				$potentialCounty = trim(substr($c, $pos), " ,.:;");//the county is probably the string after the last space
				$ps = $this->getStateFromCounty($potentialCounty, $ss);
				if($ps) {
					$result['county'] = $potentialCounty;
					$result['state'] = $ps[0];
					$result['country'] = $ps[1];
				} else {
					$c = trim(substr($c, 0, $pos), " ,.:;");
					$pos = strripos($c, " ");
					if($pos !== FALSE) {
						$potentialCounty2 = trim(substr($c, $pos), " ,:;")." ".$potentialCounty;//the county may be the string after the second from last space
						$ps = $this->getStateFromCounty($potentialCounty2, $ss);
						if($ps) {
							$result['county'] = $potentialCounty2;
							$result['state'] = $ps[0];
							$result['country'] = $ps[1];
						}
					} else {
						$c = trim(substr($c, 0, $pos), " ,.:;");
						$pos = strripos($c, " ");
						if($pos !== FALSE) {
							$potentialCounty2 = trim(substr($c, $pos), " ,:;")." ".$potentialCounty;//the county may be the string after the third from last space
							$ps = $this->getStateFromCounty($potentialCounty2, $ss);
							if($ps) {
								$result['county'] = $potentialCounty2;
								$result['state'] = $ps[0];
								$result['country'] = $ps[1];
							} else $result['county'] = $potentialCounty;//assume the county is the string after the last space
						}
					}
				}
				return $result;
			} else {
				$result['county'] = trim($c, " ,.:;");
				$ps = $this->getStateFromCounty($c, $ss);
				if($ps) {
					$result['state'] = $ps[0];
					$result['country'] = $ps[1];
					return $result;
				}
				return $result;
			}
		}
		return null;
	}

	protected function getIdentifier($str, $possibleMonths) {//echo "\nInput to getIdentifier: ".$str."\n";
		$identified_by = "";
		$detPatStr = "/[^(]\\b(?:(?:D[ec][trf](?:[.:;,=1]|[ec]rmin[ec]d)|(?:[Il!|]d[ec]nt[Il!|]f[Il!|][ec]d))(?:\\sb[vy])?)[;:]?\\s?(.+)(?:(?:\\n|\\r\\n)((?s).+))?/i";
		if(preg_match($detPatStr, $str, $detMatches)) {//$i=0;foreach($detMatches as $detMatche) echo "\nline 1350, detMatches[".$i++."] = ".$detMatche."\n";
			$identified_by = trim($detMatches[1], " .\t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(preg_match("/^(.*)\\bCo[Il!1|]{2}[;:,.]{1,2} .*/i", $identified_by, $mats)) $identified_by = trim($mats[1], " .\t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
		}
		else {
			$detPatStr = "/[^(]\\b(?:(?:D[ec][trf][ec]rmin[ec]r[sz]?)|(?:[Il!|]d[ec]nt[Il!|]f[Il!|][ec]r[sz]?))[;:]?\\s?(.+)(?:(?:\\n|\\r\\n)((?s).+))?/i";
			if(preg_match($detPatStr, $str, $detMatches)) {//$i=0;foreach($detMatches as $detMatche) echo "\nline 1356, detMatches[".$i++."] = ".$detMatche."\n";
				$identified_by = trim($detMatches[1], " .\t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			}
		}
		if($identified_by) {
			$date_identified = array();
			$datePatternStr = "/(?:(?(?=(?:.*)\\b(?:\\d{1,2})[- ]?+(?:(?i)".$possibleMonths.")[- ]?(?:(?:1[89]|20)\\d{2})))".

				"(.*)\\b(\\d{1,2})[- ]?+((?i)".$possibleMonths.")[- ]?((?:1[89]|20)\\d{2})|".

				"(?:(?(?=(?:.*)\\b(?:(?i)".$possibleMonths.")[,-]?\\s(?:\\d{1,2}),?\\s(?:(?:1[89]|20)\\d{2})))".

				"(.*)\\b((?i)".$possibleMonths.")\\s(\\d{1,2})[,-]?\\s((?:1[89]|20)\\d{2})|".

				"(?:(?(?=(?:.*)\\b(?:(?i)".$possibleMonths.")[,-]?\\s?(?:(?:1[89]|20)\\d{2})))".

				"(.*)\\b((?i)".$possibleMonths.")[,-]?\\s?((?:1[89]|20)\\d{2})|".

				"(.*)\\b((?:1[89]|20)\\d{2}(?! ?m))\\b)))/s";

			if(preg_match($datePatternStr, $identified_by, $dateMatches)) {
				//$i=0;
				//foreach($dateMatches as $dateMatch) echo "\ndateMatches[".$i++."] = ".$dateMatch."\n";
				$day = $dateMatches[2];
				if($day) {
					$identified_by = $dateMatches[1];
					$date_identified['day'] = $day;
					$date_identified['month'] = $dateMatches[3];
					$date_identified['year'] = $dateMatches[4];
				} else {
					$month = $dateMatches[6];
					if($month) {
						$identified_by = $dateMatches[5];
						$date_identified['month'] = $month;
						$date_identified['day'] = $dateMatches[7];
						$date_identified['year'] = $dateMatches[8];
					} else {
						$month = $dateMatches[10];
						if($month) {
							$identified_by = $dateMatches[9];
							$date_identified['month'] = $month;
							$date_identified['year'] = $dateMatches[11];
						} else {
							$identified_by = $dateMatches[12];
							$date_identified['year'] = $dateMatches[13];
						}
					}
				}
			} else if(count($detMatches) > 2) {
				$nextLine = trim($detMatches[2]);
				$pos = strpos($nextLine, "\n");
				if($pos !== FALSE) $nextLine = trim(substr($nextLine, 0, $pos));
				if(preg_match($datePatternStr, $nextLine, $dateMatches)) {
					$day = $dateMatches[2];
					if($day) {
						$date_identified['day'] = $day;
						$date_identified['month'] = $dateMatches[3];
						$date_identified['year'] = $dateMatches[4];
					} else {
						$month = $dateMatches[6];
						if($month) {
							$date_identified['month'] = $month;
							$date_identified['day'] = $dateMatches[7];
							$date_identified['year'] = $dateMatches[8];
						} else {
							$month = $dateMatches[10];
							if($month) {
								$date_identified['month'] = $month;
								$date_identified['year'] = $dateMatches[11];
							} else {
								//$identified_by .= $dateMatches[12];
								$date_identified['year'] = $dateMatches[13];
							}
						}
					}
				}
			}
			$ibLength = strlen($identified_by);
			if($ibLength > 1) {
				if(preg_match("/(.*)\\bDate\\b/i", $identified_by, $ms)) $identified_by = $ms[1];
				if(preg_match("/(.*)\\bAlt\\b/i", $identified_by, $ms)) $identified_by = $ms[1];
				if(preg_match("/(.*)\\bNo\\b/i", $identified_by, $ms)) $identified_by = $ms[1];
				if(preg_match("/(.*)\\bAccession\\b/i", $identified_by, $ms)) $identified_by = $ms[1];
				if(preg_match("/(.*)\\bUniversity\\b/i", $identified_by, $ms)) $identified_by = $ms[1];
				if(preg_match("/.*\\bDet[;:. ]{1,2}(.+)/i", $identified_by, $ms)) $identified_by = $ms[1];
				$identified_by = trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			}
			if(!$this->isMostlyGarbage2($identified_by, 0.50) && $this->containsText($identified_by)) {
				if(str_word_count($identified_by) >= 6) {
					$pos = stripos($identified_by, " by ");
					if($pos !== FALSE) $identified_by = trim(substr($identified_by, $pos+3));
				}
				if(str_word_count($identified_by) >= 6) {
					$words = explode(" ", $identified_by);
					$index = 0;
					foreach($words as $word) {
						if(is_numeric($word)) {
							$identified_by = "";
							break;
						}
						if($index == 0) $identified_by = $word;
						else if($index == 1) {
							$identified_by .= " ".$word;
							if(strlen($word) > 2) break;
						} else {
							$identified_by .= " ".$word;
							break;
						}
						$index++;
					}
				}
				return array($identified_by, $date_identified);
			} else return array("", $date_identified);
		} else if(count($detMatches) > 2) return $this->getIdentifier(trim($detMatches[2]), $possibleMonths);
		return null;
	}

	protected function processCollectorName($lastName, $name) {//echo "\nInput to processCollectorName: lastName: ".$lastName.", name: ".$name."\n";
		$name = trim(preg_replace("/^Coll(?:\\.|ect(?:or|ed (?:by)?))[:;]? ([A-Z])/i", "\${1}", $name));
		$firstPart = trim(substr($name, 0, strpos($name, $lastName)));
		if(preg_match("/.*\\b(?:Dr|Rev)\\.?(.+)/i", $firstPart, $mats)) $firstPart = trim($mats[1]);
		if(substr_count($firstPart, " ") > 1) {
			$words = array_reverse(explode(" ", $firstPart));
			$r = "";
			$index = 0;
			foreach($words as $word) {
				if($index++ < 5 && preg_match("/\\w/", $word)) $r = trim($word)." ".$r;
				else break;
			}
			$firstPart = $r;
		}
		if(preg_match("/^(?:.*?)([A-Z](?:[-_*.]|[A-Z]+)?) ?([A-Z][-_*.]?)? (?:and |&) ?(?:[A-Z](?:[-_*.]|[A-Z]+)? ?(\\w[-_*.]?)?)/i", $firstPart, $mats)) {
			if(count($mats) == 3) return array('fName' => trim($mats[1]), 'mName' => trim($mats[2]));
			else return array('fName' => trim($mats[1]), 'mName' => "");
		}
		$firstPart = trim(substr($name, 0, strpos($name, $lastName)));
		if(preg_match("/.*\\b(?:Dr|Rev)\\.?(.+)/i", $firstPart, $mats)) $firstPart = trim($mats[1]);
		if(substr_count($firstPart, " ") > 1) {
			$words = array_reverse(explode(" ", $firstPart));
			$r = "";
			$index = 0;
			foreach($words as $word) {
				if($index++ < 3 && $this->isText($word)) $r = trim($word)." ".$r;
				else break;
			}
			$firstPart = $r;
		}
		$fNamePat = "/(?:.*)\\b([a-zA-Z])(?:[-_*.]\\s|\\s|[-_*.])([a-zA-Z])\\b(?:[-_*.]\\s|\\s|[-_*.])?/i";//First Initial, Middle Initial
		if(preg_match($fNamePat, $firstPart, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 1999, mats[".$i++."] = ".$mat."\n";
			return array('fName' => trim($mats[1]), 'mName' => trim($mats[2]));
		}
		$fNamePat = "/(?:.*)\\b([a-zA-Z]{2,}+)(?:[-_*.]\\s|\\s|[-_*.])([a-zA-Z])\\b(?:[-_*.]\\s|\\s|[-_*.])?/i";//First Name, Middle Initial
		if(preg_match($fNamePat, $firstPart, $mats)) {
			return array('fName' => trim($mats[1]), 'mName' => trim($mats[2]));
		}
		$fNamePat = "/(?:.*)\\b([a-zA-Z]{2,}+)(?:[-_*.]\\s|\\s|[-_*.])([a-zA-Z]{2,}+)\\b(?:[-_*.]\\s|\\s|[-_*.])?/i";//First Name, Middle Name
		if(preg_match($fNamePat, $firstPart, $mats)) {
			return array('fName' => trim($mats[1]), 'mName' => trim($mats[2]));
		}
		$fNamePat = "/(?:.*)\\b([a-zA-Z]+)\\b(?:[-_*.]\\s|\\s|[-_*.])?/i";//First Name only
		if(preg_match($fNamePat, $firstPart, $mats)) {
			return array('fName' => trim($mats[1]), 'mName' => "");
		}
	}

	private function extractCollectorInfo($str, $cStr, $lineAfter=null) {
		//echo "\nExtracting: ".$cStr."\n";
		$detPat = "/\\b(?:Herbar|Cummings\\sHerb|Univ|Botanical\\sGarden)/i";
		if(preg_match($detPat, $cStr)) return null;
		$detPat = "/(.*)\\bdet(?:\\.?|ermined by)?/i";
		if(preg_match($detPat, $cStr, $dMatches)) $cStr = trim($dMatches[1]);
		if(!preg_match("/^Herb.*/i", $cStr)) {//echo "\nSecond Pattern Not Matched: ".$cStr."\n";
			$possibleMonths = "(?:Jan(?:\\.|(?:uar\\w{1,2}))?|Feb(?:\\.|(?:ruar\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:[tf]emb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)";
			$mPat = "/^".$possibleMonths."\\s(?:[o0Q]?[O0!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])".
				",?\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2})(.*)/i";
			if(preg_match($mPat, $cStr, $dMatches)) $cStr = trim($dMatches[1]);
			else {
				$mPat = "/(.+?)\\b(?:[o0Q]?+[O0!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])[ -]\\s?".$possibleMonths.
					"[ -]\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2})$/i";
				if(preg_match($mPat, $cStr, $dMatches)) $cStr = trim($dMatches[1]);
				else {
					$mPat = "/(.+?)".$possibleMonths."\\s(?:[o0Q]?+[O0!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])?".
						",?\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2})$/i";
					if(preg_match($mPat, $cStr, $dMatches)) $cStr = trim($dMatches[1]);
					else {
						$mPat = "/^(?:[o0Q]?[O0!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])[ -]\\s?".$possibleMonths.
							"[ -]\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2})(.+)/i";
						if(preg_match($mPat, $cStr, $dMatches)) $cStr = trim($dMatches[1]);
						else {
							$mPat = "/^(.+?)\\b(?:[o0Q]?+[!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])[ -]\\s?".$possibleMonths.
								"[ -]\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2})\\s(.+)/i";
							if(preg_match($mPat, $cStr, $dMatches)) {
								$cStr = trim($dMatches[1]);
								$temp = trim($dMatches[2]);
								if($this->containsNumber($temp)) $cStr .= " ".$temp;
							}
						}
					}
				}
			}//echo "\nline 2072, cStr: ".$cStr."\n";
			$collWords = array_reverse(explode(" ", $cStr));
			$cfds = array();
			foreach($collWords as $collWord) {
				$collWord = trim($collWord, "-,.");
				if(strlen($collWord) > 2 && !$this->containsNumber($collWord)) {
					$cfds = $this->getCollectorFromDatabase($collWord, $cStr);
					if($cfds) {
						$familyName = $cfds['familyName'];
						$collectorName = $cfds['collectorName'];
						$associatedCollectors = "";
						$collectorNum = trim(substr($cStr, strpos($cStr, $familyName)+strlen($familyName)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^*_-");
						if(preg_match("/(.*)(?:No\\b.?|#)(.*)/i", $collectorNum, $matches)) {
							$collectorNum = trim($matches[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^*_-");
							$firstPart = trim($matches[1]);
							$andPos = strpos($firstPart, "&");
							if($andPos !== FALSE && $andPos == 0) $associatedCollectors .= trim(substr($firstPart, 1));
						} else if(preg_match("/^(?:and |&)(.+)/i", $collectorNum, $mats)) {
							//echo "\nColector Number: ".$collectorNum."\n";
							$associatedCollectors = trim($mats[1]);
							$collectorNum = "";
						}
						if(preg_match("/(.*)\\bSpecimen:\\s(.*)/i", $collectorNum, $cMats)) {
							$temp = trim($cMats[2]);
							if($this->containsNumber($temp)) $collectorNum = $temp;
						}
						$collectorNum = $this->terminateCollectorNum($collectorNum);
						if(strlen($collectorNum) == 0) $collectorNum = $this->extractCollectorNum($str);
						if(preg_match("/^(?:&|and\\s)(.*)/i", $collectorNum, $mats)) {
							$temp = trim($mats[1]);
							$tempWords = array_reverse(explode(" ", $temp));
							if(count($tempWords) > 1) {
								$tempWord = trim($tempWords[0]);
								$l = strlen($tempWord);
								if($l > 0 && (is_numeric($tempWord) || is_numeric(trim(substr($tempWord, 0, $l-1))))) {
									$collectorNum = $tempWord;
									$collectorName .= " and ".trim(substr($temp, 0, strpos($temp, $tempWord)));
								} else {
									$collectorName .= " and ".$temp;
									$collectorNum = "";
								}
							} else {
								$collectorName .= " ".$temp;
								$collectorNum = "";
							}
						} else {
							if(preg_match("/(.*)\\b(?:&|and\\s)(.*)/i", $collectorNum, $mats)) {
								$temp = trim($mats[1]);
								$l = strlen($temp);
								if($l > 1 && (is_numeric($temp) || is_numeric(trim(substr($temp, 0, $l-1))))) {
									$collectorNum = $temp;
									$collectorName .= " ".trim($mats[2]);
									$collectorName = trim($collectorName);
								} else {
									$temp = trim($mats[2]);
									$tempWords = array_reverse(explode(" ", $temp));
									if(count($tempWords) > 1) {
										$tempWord = trim($tempWords[0]);
										$l = strlen($tempWord);
										if($l > 0 && (is_numeric($tempWord) || is_numeric(trim(substr($tempWord, 0, $l-1))))) {
											$collectorNum = $tempWord;
											$collectorName .= " and ".trim(substr($temp, 0, strpos($temp, $tempWord)));
										} else {
											$collectorName .= " ".$temp;
											$collectorNum = "";
										}
									} else {
										$collectorName .= " ".$temp;
										$collectorNum = "";
									}
								}
							}
						}
						if($this->containsNumber($collectorNum)) {
							$spacePos = strpos($collectorNum, " ");
							if($spacePos !== FALSE) {
								$temp2 = substr($collectorNum, 0, $spacePos);
								$temp3 = trim(substr($collectorNum, $spacePos+1));
								$spacePos = strpos($temp3, " ");
								if($spacePos !== FALSE) $temp3 = trim(substr($temp3, $spacePos+1));
								if($this->containsNumber($temp2)) {
									if(is_numeric($temp3) || strlen($temp3) < 3 || preg_match("/^\(.{0,9}\)/", $temp3)) $collectorNum = $temp2." ".$temp3;
									else $collectorNum = $temp2;
								} else if(strlen($temp2) < 3) {
									if($this->containsNumber($temp3)) $collectorNum = $temp2." ".$temp3;
								}
							}
							if(strlen($collectorNum) > 2) {
								if(strcmp(substr($collectorNum, 0, 1), "(") == 0) {
									if(strcmp(substr(strrev($collectorNum), 0, 1), "(") == 0) $collectorNum = trim($collectorNum, " ()");
								}
							}
							if(preg_match("/\\b(?:[1!l|I][89][OQ1!Il|ZS&0-9]{2}|[2Z][O0]{2}[OQ1!Il|ZS&0-9])/", $collectorNum) || str_word_count($collectorNum) > 2) $collectorNum = "";
						} else $collectorNum = "";
						$cfds['collectorName'] = $collectorName;
						$cfds['collectorNum'] = $collectorNum;
						$cfds['associatedCollectors'] = $associatedCollectors;
						//echo "\nline 2143, collectorName: ".$collectorName.", collectorNum: ".$collectorNum."\n";
						return $cfds;
					}
				}
			}
		}
		return null;
	}

	private function extractCollectorNum($s) {//echo "\ninput to extractCollectorNum, s: ".$s."\n";
		$s = trim($s);
		$sStrs = explode("\n", $s);
		if($sStrs) {
			$possibleNumbers = "[OQSZl|I!&0-9]";
			foreach($sStrs as $sStr) {
				$sStr = trim($sStr);
				$num = "";
				if(preg_match("/(?:(?:N(?:um(?:ber)?|[o0])\\.)|#)\\s?(".$possibleNumbers."{0,2}+,?".$possibleNumbers."{3}\\w?+)$/i", $sStr, $cMats)) $num = trim($cMats[1]);
				else if(preg_match("/N(?:um(?:ber)?|[o0])\\s(".$possibleNumbers."{0,2}+,?".$possibleNumbers."{3}\\w?+)$/i", $sStr, $cMats)) $num = trim($cMats[1]);
				else if(preg_match("/^(?:(?:N(?:um(?:ber)?|[o0])\\.)|#)\\s?(".$possibleNumbers."{0,2}+,?".$possibleNumbers."{3}\\w?+)\\b/i", $sStr, $cMats)) $num = trim($cMats[1]);
				else if(preg_match("/^N(?:um(?:ber)?|[o0])\\s(".$possibleNumbers."{0,2}+,?".$possibleNumbers."{3}\\w?+)\\b/i", $sStr, $cMats)) $num = trim($cMats[1]);
				else if(preg_match("/^(?:(?:N(?:um(?:ber)?|[o0])[.,;:*#])|#)\\s(".$possibleNumbers."{1,5}+\\w?+)\\b/i", $sStr, $cMats)) $num = trim($cMats[1]);
				if($num) {
					//echo "\nline 2202, num: ".$num."\n";
					if(preg_match("/^s\\.?n\\.?$/i", $num)) return $num;
					else return $this->replaceMistakenNumbers($num);
				}
				if(preg_match("/\\sSpecimen:\\s(.*)/i", $sStr, $cMats)) {
					$temp = trim($cMats[1]);
					if($this->containsNumber($temp)) return $temp;
				}
				if(preg_match("/^Specimen:\\s(.*)/i", $sStr, $cMats)) {
					$temp = trim($cMats[1]);
					if($this->containsNumber($temp)) return $temp;
				}
			}
			$lastLine = trim($sStrs[count($sStrs)-1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
			if(preg_match("/^[A-Za-z]{0,3}\\d{2,10}\\w?$/", $lastLine)) return $lastLine;
			else {
				$possibleMonths = "(?:Jan(?:\\.|(?:uar\\w{1,2}))?|Feb(?:\\.|(?:ruar\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)";
				$spacePos = strrpos($lastLine, " ");
				if($spacePos !== FALSE) {
					if(!preg_match("/".$possibleMonths." (?:".$possibleNumbers."{1,2}, )?".$possibleNumbers."{4}$/i", $lastLine)) {
						$lastWord = trim(substr($lastLine, $spacePos+1));
						if(preg_match("/^[A-Za-z]{0,3}\\d{2,10}\\w?$/", $lastWord)) return $lastWord;
					}
				}
			}
			$firstLine = trim($sStrs[0], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
			if(preg_match("/^[A-Za-z]{0,3}\\d{2,10}\\w?$/", $firstLine)) return $firstLine;
		}
		return "";
	}

	protected function removeDate($col) {//echo "\nInput to terminateCollectorNum: ".$col."\n";
		if(!$this->containsNumber($col)) return $col;
		$possibleMonths = "(?:Jan(?:\\.|(?:uar\\w{1,2}))?|Feb(?:\\.|(?:ruar\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)";
		$datPat = "/(.*?)\\b(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1])\\s?".
			"(?:-(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1]))?\\s?[.-]?\\s?".$possibleMonths.
			"\\s?[.-]?\\s?(?:[!Il1][89][O0Q!IlZS&|0-9]{2}|Z2[O0Q!Il1][O0Q!IlZS&|0-9])(.*)/i";
		if(preg_match($datPat, $col, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nTdateMatches[".$i++."] = ".$dateMatche."\n";
			return trim($dateMatches[1])." ".trim($dateMatches[2]);
		}
		$datPat = "/(.*)\\b".$possibleMonths."\\s(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1])[.,]?\\s".
			"(?:[!Il1][89][O0Q!IlZS&|0-9]{2}|Z2[O0Q!Il1][O0Q!IlZS&|0-9])(.*)/i";
		if(preg_match($datPat, $col, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nTdateMatches[".$i++."] = ".$dateMatche."\n";
			return trim($dateMatches[1])." ".trim($dateMatches[2]);
		}
		$datPat = "/(.*)\\b(?:[OQ0][!IlZS&|1-9]|[!Il1][!IlZ|12])[.\/](?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1])[.\/]\\s".
			"(?:[!Il1][89][O0Q!IlZS&|0-9]{2}|Z2[O0Q!Il1][O0Q!IlZS&|0-9])(.*)/i";
		if(preg_match($datPat, $col, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nTdateMatches[".$i++."] = ".$dateMatche."\n";
			return trim($dateMatches[1])." ".trim($dateMatches[2]);
		}
		return $col;
	}

	private function terminateCollectorNum($col) {//echo "\nInput to terminateCollectorNum: ".$col."\n";
		if(!$this->containsNumber($col)) return "";
		$possibleMonths = "(?:Jan(?:\\.|(?:uar\\w{1,2}))?|Feb(?:\\.|(?:ruar\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)";
		$datPat = "/(?:(?(?=(?:.*?)\\b(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1])\\s?".
			"(?:-(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1]))?\\s?[.-]?\\s?".$possibleMonths."))".

			"(.*?)\\b(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1])\\s?".
			"(?:-(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1]))?\\s?[.-]?\\s?".$possibleMonths."|".

			"(?:(?(?=(?:.*)\\s".$possibleMonths."\\s\\d{1,2}[.,]?\\s\\d{4}))".

			"(.*)\\s".$possibleMonths."\\s\\d{1,2}[.,]?\\s\\d{4}|".

			"(.*)\\b".$possibleMonths."[.,]?\\b))/i";

		if(preg_match($datPat, $col, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nTdateMatches[".$i++."] = ".$dateMatche."\n";
			$mNum = count($dateMatches);
			if($mNum > 3) $col = trim($dateMatches[3]);
			else if($mNum > 2) $col = trim($dateMatches[2]);
			else $col = trim($dateMatches[1]);
			if(preg_match($datPat, $col, $dateMatches2)) {
				$mNum = count($dateMatches2);
				if($mNum > 3) $col = trim($dateMatches2[3]);
				else if($mNum > 2) $col = trim($dateMatches2[2]);
				else $col = trim($dateMatches2[1]);
			}
			$pos = strpos($col, " ");
			if($pos !== FALSE) {
				$temp = trim(substr($col, 0, $pos));
				if($this->containsNumber($temp)) $col = $temp;
			}
		}
		if(preg_match("/(.*)\\bDet(?:\\.|#|ermine)/i", $col, $dMats)) $col = trim($dMats[1]);
		if(preg_match("/(.*)\\bElev(?:\\.|ation)/i", $col, $dMats)) $col = trim($dMats[1]);
		if(preg_match("/(.*)\\bAlt(?:[.,_*-]|itude)/i", $col, $dMats)) $col = trim($dMats[1]);
		$pos = stripos($col, " Date");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " NEW YORK BOTAN");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " QUAD");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " Map/Quad");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		if($this->containsNumber($col)) {
			$spacePos = strrpos($col, " ");
			if($spacePos !== FALSE) {
				$firstPart = trim(substr($col, 0, $spacePos));
				$lastPart = trim(substr($col, $spacePos+1));
				if(is_numeric($lastPart)) {
					$spacePos = strrpos($firstPart, " ");
					if($spacePos !== FALSE) {
						$endOfFirstPart = trim(substr($firstPart, $spacePos+1));
						if(strlen($endOfFirstPart) <= 3 && !is_numeric($endOfFirstPart)) return $endOfFirstPart." ".$lastPart;
						else return $lastPart;
					} else return $col;
				} else if($this->containsNumber($lastPart)) return $lastPart;
				else {
					$spacePos = strpos($firstPart, " ");
					if($spacePos === FALSE) {
						if(strlen($lastPart) == 1) return $col;
						else return $firstPart;
					} else {
						$startOfFirstPart = trim(substr($firstPart, 0, $spacePos), " .");
						if(preg_match("/^(.+)[;:.,]$/", $startOfFirstPart, $mats)) {
							$startOfFirstPart = trim($mats[1]);
							if($this->containsNumber($startOfFirstPart)) return $startOfFirstPart;
						} else {
							$endOfFirstPart = trim(substr($firstPart, $spacePos+1));
							if(is_numeric($startOfFirstPart)) {
								if(strlen($endOfFirstPart) == 1) return $startOfFirstPart.$endOfFirstPart;
								else return $startOfFirstPart;
							} else if($this->containsNumber($startOfFirstPart)) return $startOfFirstPart;
						}
					}
				}
			} else return $col;
		}
		return "";
	}

	private function terminateCollector($col) {//echo "\nInput to terminateCollector: ".$col."\n";
		//if(!$this->containsNumber($col)) return "";
		$possibleMonths = "(?:Jan(?:\\.|(?:uar\\w{1,2}))?|Feb(?:\\.|(?:ruar\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)";
		$datPat = "/(?:(?(?=(?:.*?)\\b(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1])\\s?".
			"(?:-(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1]))?\\s?[.-]?\\s?".$possibleMonths."))".

			"(.*?)\\b(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1])\\s?".
			"(?:-(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1]))?\\s?[.-]?\\s?".$possibleMonths."|".

			"(?:(?(?=(?:.*)\\s".$possibleMonths."\\s\\d{1,2}[.,]?\\s\\d{4}))".

			"(.*)\\s".$possibleMonths."\\s\\d{1,2}[.,]?\\s\\d{4}|".

			"(.*)\\b".$possibleMonths."[.,]?\\b))/i";

		if(preg_match($datPat, $col, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nTdateMatches[".$i++."] = ".$dateMatche."\n";
			$mNum = count($dateMatches);
			if($mNum > 3) $col = trim($dateMatches[3]);
			else if($mNum > 2) $col = trim($dateMatches[2]);
			else $col = trim($dateMatches[1]);
		}
		if(preg_match("/(.*)\\bDet(?:\\.|#|ermine)/i", $col, $dMats)) $col = trim($dMats[1]);
		if(preg_match("/(.*)\\bElev(?:\\.|ation)/i", $col, $dMats)) $col = trim($dMats[1]);
		if(preg_match("/(.*)\\bAlt(?:[.,_*-]|itude)/i", $col, $dMats)) $col = trim($dMats[1]);
		//echo "\nline 6833, col: ".$col."\n";
		//$pos = stripos($col, "(");
		//if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " Date");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " NEW YORK BOTAN");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " QUAD");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " Map/Quad");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		//if($this->containsNumber($col)) return $col;
		return $col;
	}

	protected function isNumericMDYDate($s) {
		if($s) {
			if(preg_match("/(?:[1I!|l][0O1I!|l2]|[0O]?[1-9I!|l])\/(?:[12][O0-9I!|l]|3[0O1I!|l]|[0O]?[1-9I!|l])\/(?:(?:[1I!|l][89])?[0-9OI!|l]{2}|(?:2[0O])?[0O][0-9OI!|l])/", $s)) return true;
		}
		return false;
	}

//this function returns the collector name if it is preceded by a collector label.
//If not found tries to find a collector name from the database in a likely place on many labels
	protected function getCollector($str) {//echo "\nInput to getCollector: ".$str."\n";
		if($str) {
			$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
			$collector = "";
			$nextLine = "";
			$identifiedBy = "";
			$otherCatalogNumbers = "";
			$collectorNum = "";
			$isIdentifier = false;
			//deal with labels with the collector name preceding the "Collector" tag first.  Based on COLO bryo labels
			if(preg_match("/(.+)\\bColl[.,i]?.{2,4}\\b[dl]et[,.]?\\sN[o0]?[,.]?\\s?(.+)/i", $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n2385, collMatches[".$i++."] = ".$collMatche."\n";
				$collector = trim($this->removeDate($collMatches[1]), " .,");
				if(preg_match("/(?:dup[,.]? )?verif[,.]? (.+)/i", $collector, $mats)) {
					$collector = trim($mats[1]);
					if(preg_match("/(?:[A-Za-z](?:\\.|[A-Za-z]+)(?: [A-Z]\\.)? [A-Za-z]+) (.+)/i", $collector, $mats)) $collector = trim($mats[1]);
				}
				$temp2 = trim($collMatches[2]);
				if($this->containsNumber($temp2)) {
					if(preg_match("/(.+) verif/i", $temp2, $mats)) {
						$collectorNum = $this->replaceMistakenNumbers($mats[1]);
					} else $collectorNum = $this->terminateCollectorNum($temp2);
				}
				$isIdentifier = true;
			} else if(preg_match("/(.+)\\bColl[.,i]?.{2,5}\\b[dl]et[,.]?\\s(?:".$possibleMonths.").*/i", $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n2398, collMatches[".$i++."] = ".$collMatche."\n";
				$collector = trim($collMatches[1], " .,");
				$isIdentifier = true;
			} else if(preg_match("/(.+)\\bColl[.,i]?\\s((?:".$possibleMonths.").*)/i", $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n2401, collMatches[".$i++."] = ".$collMatche."\n";
				$collector = trim($collMatches[1], " .,");
				$temp = trim($this->removeDate(trim($collMatches[2])));
				if(preg_match("/(.+)[,.]? [dl]et[,.]?$/i", $temp, $mats) && !preg_match("/.*(?:".$possibleMonths.").*/i", $temp)) {
					$identifiedBy = trim($mats[1]);
				}
			} else if(preg_match("/(.+)\\bColl[.,i]?\\sN(?:[o0][,.]? ?|[,.] ?(?=\\d))(.+\\s(?:".$possibleMonths.").*)/i", $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n2407, collMatches[".$i++."] = ".$collMatche."\n";
				$collector = trim($collMatches[1], " .,");
				$temp = trim($this->removeDate(trim($collMatches[2])));
				if($this->containsNumber($temp) && !preg_match("/.*(?:".$possibleMonths.").*/i", $temp)) {
					$collectorNum = $this->replaceMistakenNumbers($temp);
				}
			}
			if($collector && !preg_match("/.* L(?:at|ong)(?:[,.]|itude).{0,9}$/i", $collector)) {//echo "\ncollector: ".$collector."\n";
				$rPos = strrpos($collector, ".");
				if($rPos !== FALSE) {
					$firstPart = trim(substr($collector, $rPos+1));
					$temp = trim(substr($collector, 0, $rPos));//echo "\ncollector: ".$collector."\nfirstPart: ".$firstPart."\ntemp: ".$temp."\n";
					$commaPos = strrpos($firstPart, ",");
					if($commaPos !== FALSE) {
						$commaPos = strrpos($collector, ",");
						$temp = trim(substr($collector, 0, $commaPos));
						$collector = trim(substr($collector, $commaPos+1));
					} else $collector = $firstPart;
					$rPos = strrpos($temp, ".");
					if($rPos !== FALSE) {
						$temp2 = trim(substr($temp, $rPos+1));
						if(strlen($temp2) == 1) {
							$collector = $temp2.". ".$collector;
							$temp = trim(substr($temp, 0, $rPos));
							$rPos = strrpos($temp, ".");
							if($rPos !== FALSE) {
								$temp2 = trim(substr($temp, $rPos+1));
								if(strlen($temp2) == 1) $collector = $temp2.". ".$collector;
								else if(strpos($temp2, " ") === FALSE) $collector = $temp2." ".$collector;
							} else if(strpos($temp, " ") === FALSE) {
								if(strlen($temp) == 1) $collector = $temp.". ".$collector;
								else $collector = $temp." ".$collector;
							}
						} else if(strpos($temp2, " ") == FALSE) {
							if(strcasecmp($collector, "JR") ==0 || strcmp($collector, "III") ==0) {
								$collector = $temp2.", ".$collector;
								$temp2 = trim(substr($temp, 0, $rPos));
								if(strlen($temp2) < 6) $collector = $temp2." ".$collector;
							} else $collector = $temp2." ".$collector;
						} else {
							$rPos = strrpos($temp2, " ");
							$temp = trim(substr($temp2, $rPos+1));
							$temp2 = trim(substr($temp2, 0, $rPos));
							if(strlen($temp) == 1) $collector = $temp.". ".$collector;
							else {
								if(strcasecmp($collector, "JR") ==0 || strcmp($collector, "III") ==0) {
									$collector = $temp.", ".$collector;
									$rPos = strrpos($temp2, " ");
									if($rPos !== FALSE) {
										$temp2 = trim(substr($temp2, $rPos+1));
										$collector = $temp2." ".$collector;
									} else $collector = trim(substr($temp2, $rPos+1))." ".$collector;
								} else $collector = trim(substr($temp2, $rPos+1))." ".$collector;
							}
						}
					} else if(strpos($temp, " ") === FALSE) $collector = $temp.". ".$collector;
					else {
						$rPos = strrpos($temp, " ");
						$temp2 = trim(substr($temp, $rPos+1));//echo "\ncollector: ".$collector."\ntemp3: ".$temp3."\n";
						if(strlen($temp2) == 1) {
							$collector = $temp2.". ".$collector;
							$temp = trim(substr($temp, 0, $rPos));
							$rPos = strpos($temp, " ");
							if($rPos === FALSE) $collector = $temp." ".$collector;
						}
					}
				}
				if(preg_match("/\\d{2,}/", $collector) || strpos($collector, "�") !== FALSE) $collector = "";
				if($collector) {
					if($isIdentifier) $identifiedBy = $collector;
					$result = array('collectorName' => $collector);
					if($collectorNum) $result['collectorNum'] = $collectorNum;
					if($identifiedBy) $result['identifiedBy'] = $identifiedBy;
					return $result;
				}
			}
			$collPatternStr = "/\\bC[o0D](?:[l1|!i]{2}|U)[ec]{2}tion\\sdata[:,;. *]{1,3}(.*)(?:(?:\\r\\n|\\n|\\r)(.*))?/i";
			if(preg_match($collPatternStr, $str, $collMatches)) {
				$collector = trim($collMatches[1]);
				if(count($collMatches) > 2) {
					if($collector) $nextLine = trim($collMatches[2]);
					else {
						$collector = trim($collMatches[2]);
						$nextLine = "";
					}
				}
			} else if(preg_match("/\\b(?:leg(?:[,.*#]|it))\\s?[:,;.]{0,2}\\s(.+)(?:\\n(.+))?/i", $str, $collMatches)) {
				$collector = trim($collMatches[1]);
				$collPatternStr = "/\\b(?:leg(?:[,.*#]|it)) (?:et|and|&) ?(?:det(?:\\.|ermined)?|[l1|!i]den(?:t(?:[l1|!i]f[l1|!i]ed)?)?)(?:\\sb[vyg])?\\s?[:,;.]{0,2}\\s(.+)(?:\\n(.+))?/i";
				if(preg_match($collPatternStr, trim($collMatches[0]), $collMatches2)) {
					$collector = trim($collMatches2[1]);
					$isIdentifier = true;
				}
				if(count($collMatches) > 2) $nextLine = trim($collMatches[2]);
			} else if(preg_match("/\\bCo[l1|!i]{2}(?:[,.]|ected) b[vyg][:,;.]{0,2} (.+)(?:\\n(.+))?/i", $str, $collMatches)) {
				$collector = trim($collMatches[1]);
				if(count($collMatches) > 2) $nextLine = trim($collMatches[2]);
			} else if(preg_match("/(?<!Date )\\bC[o0D](?:[l1|!i]{2}|U)[ec]{2}t[ec]d[:,;. *](?!\\s?(?:(?:off|on|during|at|in|near|from|along|under|for (?:a|the)\\b)\\s(?:the\\s)?)|with suppo)(.+)(?:\\n(.+))?/i", $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n2384, collMatches[".$i++."] = ".$collMatche."\n";
				$collector = trim($collMatches[1]);
				$collPatternStr = "/\\bC[o0D](?:[l1|!I]{2}|U)[ec]{2}t[ec]d[:,;. *](?:\\s?(?:and|&)\\s(?:det(?:[;:,.=]|ermined)|[l1|!i]den(?:t(?:[l1|!i]f[l1|!i]ed)?)?)(?:\\sb[vyg])?)[:,;. *]{1,3}(.+)(?:\\n(.+))?/i";
				if(preg_match($collPatternStr, trim($collMatches[0]), $collMatches2)) {
					$collector = trim($collMatches2[1]);
					if(count($collMatches2) > 2) $nextLine = trim($collMatches2[2]);
					$isIdentifier = true;
				} else {
					$collPatternStr = "/C[o0D](?:[l1|!i]{2}|U)[ec]{2}t[ec]d(?:\\s?(?:and|&\\.?)\\s(?:prep(?:ared|[,.])?|dist(?:[,.]|ributed)?)\\sb[yxv].?)[:,;. *]{1,3}(.+)(?:(?:\\r\\n|\\n|\\r)(.*))?/i";
					if(preg_match($collPatternStr, trim($collMatches[0]), $collMatches2)) {//$i=0;foreach($collMatches2 as $collMatche2) echo "\n2420, collMatches2[".$i++."] = ".$collMatche2."\n";
						$collector = trim($collMatches2[1]);
						if(count($collMatches2) > 2) $nextLine = trim($collMatches2[2]);
					} else if(preg_match("/\\bC[o0D](?:[l1|!i]{2}|U)(?:\\.|[ec]{2}t[ec]d)\\s?b[vyg].?[:,;. *]{1,3}(.{3,}+)(?:\\n(.+))?/i", trim($collMatches[0]), $collMatches2)) {//$i=0;foreach($collMatches2 as $collMatche2) echo "\n2423, collMatches2[".$i++."] = ".$collMatche2."\n";
						$collector = trim($collMatches2[1]);
						if(count($collMatches2) > 2) $nextLine = trim($collMatches2[2]);
					} else if(preg_match("/\\bC[o0D](?:[l1|!i]{2}|U)(?:\\.|[ec]{2}t[ec]d)\\s?b[vyg].?\\n(.+)(?:\\n(.+))?/i", $str, $collMatches2)) {//$i=0;foreach($collMatches2 as $collMatche2) echo "\n2426, collMatches2[".$i++."] = ".$collMatche2."\n";
						$collector = trim($collMatches2[1]);
						if(count($collMatches2) > 2) $nextLine = trim($collMatches2[2]);
					} else if(preg_match("/\\bC[o0D](?:[l1|!i]{2}|U)(?:\\.|[ec]{2}t[ec]d)\\sb[vyg].?(.*)/i", $str, $collMatches2)) {//$i=0;foreach($collMatches2 as $collMatche2) echo "\n2429, collMatches2[".$i++."] = ".$collMatche2."\n";
						$collector = trim($collMatches2[1]);
					}
				}
				if(strlen($nextLine) == 0 && count($collMatches) > 2) $nextLine = trim($collMatches[2]);
			} else if(preg_match("/C[o0D](?:[l1|!i]{2}|U)[ec]{2}t[o0]r?s?[:,;. *=]{1,3}(.+)(?:(?:\\r\\n|\\n|\\r)(.*))?/i", $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n11513, collMatches[".$i++."] = ".$collMatche."\n";
				$collector = trim($collMatches[1]);
				if(count($collMatches) > 2) $nextLine = trim($collMatches[2]);
				$collPatternStr = "/(.*)\\b(?:(?:det(?:[;:,.=1]|ermined)|[l1|!i]den(?:t(?:[l1|!i]f[l1|!i]ed)?)?)(?:\\sb[vyg])?)[:,;. *]{1,3}(.+)/i";
				if(preg_match($collPatternStr, $collector, $collMatches2)) {
					$collector = trim($collMatches2[1]);
					$identifiedBy = trim($collMatches2[2]);
				}
			} else if(preg_match("/(.*)\\bC[o0D](?:[l1|!i ]{2,3}|U)s?[:,;. *]{1,3}(?!\\s?Date)(.+)(?:(?:\\r\\n|\\n|\\r)(.*))?/i", $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n2223, collMatches[".$i++."] = ".$collMatche."\n";
				$collector = trim($collMatches[2]);
				if(preg_match("/^b[gyq][:,;. *]{1,3}(.*)/i", $collector, $mats)) $collector = trim($mats[1]);
				if(count($collMatches) > 3) $nextLine = trim($collMatches[3]);
				$collPatternStr = "/(?:(?:and|&.?)\\s?(?:det(?:[;:,.=1]|ermined)|[l1|!i]den(?:t(?:[l1|!i]f[l1|!i]ed)?)?)(?:\\sb[vyg])?)[:,;. *]{1,3}(.+)(?:\\n(.+))?/i";
				if(preg_match($collPatternStr, $collector, $collMatches2)) {//$i=0;foreach($collMatches2 as $collMatche2) echo "\n1634, collMatches2[".$i++."] = ".$collMatche2."\n";
					$collector = trim($collMatches2[1]);
					$isIdentifier = true;
					if(preg_match("/(.{6,})\\b(?:No. ?|#[.,]?)(.+)/i", $collector, $mats)) {
						$collector = trim($mats[1], " ,.");
						$temp = trim($mats[2]);
						if($this->containsNumber($temp)) {
							$collectorNum = $temp;
							if(preg_match("/(.*)(?:".$possibleMonths.")/", $collectorNum, $mats2)) $collectorNum = trim($mats2[1], " ,.");
							if($collector && $collectorNum && str_word_count($collectorNum) < 3) {
								if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats2)) {
									$temp = trim($mats2[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
									$temp2 = trim($mats2[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
									$pos = strpos($temp2, " ");
									if(strpos($temp, " ") === FALSE && $pos !== FALSE) {
										$associatedCollectors = $temp2;
										$collector = $temp." ".trim(substr($temp2, $pos+1));
									} else {
										$collector = $temp;
										$associatedCollectors = $temp2;
									}
									return array
									(
										'collectorName' => $collector,
										'associatedCollectors' => $associatedCollectors,
										'collectorNum' => $collectorNum,
										'identifiedBy' => $collector
									);
								} else {
									return array
									(
										'collectorName' => $collector,
										'collectorNum' => $collectorNum,
										'identifiedBy' => $collector
									);
								}
							}
						}
					}
				} else if(preg_match("/(.+)[:,;. *]{1,3}Det(?:[:,;. *1]|ermined)(?:\\sb[vyg])?[:,;. *]?(.+)/i", $collector, $collMatches2)) {//$i=0;foreach($collMatches2 as $collMatche2) echo "\n2269, collMatches2[".$i++."] = ".$collMatche2."\n";
					$collector = trim($collMatches2[1]);
					$identifiedBy = trim($collMatches2[2]);
					$identifiedBy = trim(preg_replace("/(?:[!|I12Z]?[!|IZS0-9]|3[01!|I1]) (?:".$possibleMonths.") (?:[1!|!|I1]9[!|IZS0-9]{2}|[2Z]0[!|IZS0-9]{2})/", "", $identifiedBy));
					if(preg_match("/(.+) (?:N[0o]|#)[:,;. *]?(.+)/i", $identifiedBy, $mats3)) {
						$temp = trim($mats3[2]);
						if($this->containsNumber($temp) && str_word_count($temp) < 6) {
							$collectorNum = $this->terminateCollectorNum($temp);
							if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
								$temp = trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
								$temp2 = trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
								$pos = strpos($temp2, " ");
								if(strpos($temp, " ") === FALSE && $pos !== FALSE) {
									$associatedCollectors = $temp2;
									$collector = $temp." ".trim(substr($temp2, $pos+1));
								} else {
									$collector = $temp;
									$associatedCollectors = $temp2;
								}
								return array
								(
									'collectorName' => $collector,
									'associatedCollectors' => $associatedCollectors,
									'collectorNum' => $collectorNum,
									'identifiedBy' => trim($mats3[1])
								);
							} else {
								return array
								(
									'collectorName' => $collector,
									'collectorNum' => $collectorNum,
									'identifiedBy' => trim($mats3[1])
								);
							}
						}
					} else {
						$pos = strrpos($identifiedBy, "  ");
						if($pos === FALSE) $pos = strrpos($identifiedBy, " ");
						if($pos !== FALSE) {
							$temp2 = trim(substr($identifiedBy, $pos+1));
							$temp = trim(substr($identifiedBy, 0, $pos));
							if($this->containsNumber($temp2) && $temp && str_word_count($temp2) < 3) {
								if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
									$temp1 = trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
									$temp12 = trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
									$pos = strpos($temp12, " ");
									if(strpos($temp, " ") === FALSE && $pos !== FALSE) {
										$associatedCollectors = $temp12;
										$collector = $temp1." ".trim(substr($temp12, $pos+1));
									} else {
										$collector = $temp1;
										$associatedCollectors = $temp12;
									}
									return array
									(
										'collectorName' => $collector,
										'associatedCollectors' => $associatedCollectors,
										'collectorNum' => $temp2,
										'identifiedBy' => $temp
									);
								} else {
									return array
									(
										'collectorName' => $collector,
										'collectorNum' => $temp2,
										'identifiedBy' => $temp
									);
								}
							}
						}
					}
				} else if(preg_match("/(?<! Confirmed )\\bb[vyg][:,;. *]{1,3}(.+)/i", $collector, $collMatches2)) $collector = trim($collMatches2[1]);
				if(preg_match("/(.*)\\bDate\\b/i", $collector, $mats)) $collector = trim($mats[1]);
			} else {
				$collPatternStr = "/(?:\\r\\n|\\n|\\r)(.+),\\s?Co[l1!I]{2}(?:[,.*#]|ectors?)?\\b(?:(?:\\r\\n|\\n|\\r|\\s)(.*))?/i";
				if(preg_match($collPatternStr, $str, $collMatches)) {
					$collector = $collMatches[1];
					if(strpos($collector, " ") !==  FALSE) {
						$words = array_reverse(explode(" ", $collector));
						$index = 0;
						foreach($words as $word) {
							if($index++ == 0) $collector = $word;
							else {
								if($index < 4) {
									$dotPos = strpos($word, ".");
									if($dotPos !== FALSE) {
										if(strlen($word) == 2) $collector = $word." ".$collector;
										else break;
									} else $collector = $word." ".$collector;
								} else break;
							}
						}
					}
					if(count($collMatches) > 2) {
						$nextLine = $collMatches[2];
						if($this->containsNumber($nextLine) && !preg_match("/.*(?:".$possibleMonths.").*/i", $nextLine) && str_word_count($nextLine) < 3) {
							$collectorNum = $nextLine;
						}
					}
					if(preg_match("/(.+)(?: et\\.? | and |&)(.+)/i", $collector, $mats)) {
						$temp = trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
						$temp2 = trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
						$pos = strpos($temp2, " ");
						if(strpos($temp, " ") === FALSE && $pos !== FALSE) {
							$associatedCollectors = $temp2;
							$collector = $temp." ".trim(substr($temp2, $pos+1));
						} else {
							$collector = $temp;
							$associatedCollectors = $temp2;
						}
						return array
						(
							'collectorName' => $collector,
							'associatedCollectors' => $associatedCollectors,
							'collectorNum' => $collectorNum
						);
					} else {
						return array
						(
							'collectorName' => trim($collector, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					}
				} else {
					$collPatternStr = "/(.*)(?:\\r\\n|\\n|\\r)by (.+)(?:\\r\\n|\\n|\\r)((?s).*)/i";
					if(preg_match($collPatternStr, $str, $collMatches)) {
						$temp = trim($collMatches[1]);
						$spacePos = strrpos(" ", $temp);
						if($spacePos !== FALSE) $endWord = trim(substr($temp, $spacePos+1));
						else $endWord = $temp;
						if(preg_match("/C[o0D][l1|!i]{2}[ec]{2}t[ec]d/i", $endWord)) {
							$collector = trim($collMatches[2]);
							$nextLine = trim($collMatches[3]);
						} else {
							$cI = $this->getCollector($collMatches[3]);
							if($cI) return $cI;
							else {
								$cI = $this->getCollector($temp);
								if($cI) return $cI;
							}
						}
					}
				}
			}
			if(strlen($collector) > 1) {//echo "\nline 2717, collector = ".$collector."\ncollectorNum: ".$collectorNum."\nnextLine: ".$nextLine."\n";
				$collector = trim($collector);
				if(strlen($collector) <= 3 && strcasecmp(substr($collector, 0, 2), "No") == 0) $collector = "";
				else {
					$collector = trim(preg_replace("/\\s{2,}/m", " ", $collector));
					if(preg_match("/(.*)Acc(?:[,.]|ession)\\s(?:[NW][o0Q][.ou]|#)(.*)/i", $collector, $cMats)) {
						$collector = trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@$%^&*_-");
						$otherCatalogNumbers = trim($cMats[2]);
					} else if(strlen($nextLine) > 1) {
						if(preg_match("/(.*)Acc(?:[,.]|ession)?\\s(?:[NW][o0Q][.ou]|#)(.*)/i", $nextLine, $cMats)) {
							$collector .= " ".trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
							$otherCatalogNumbers = $this->terminateCollectorNum(trim($cMats[2]));
							$nextLine = "";
						}
					}
					if(preg_match("/(.*)(?:No\\.? |# ?)([1-9!|lI][0-9OQ!|lI]?,[0-9OQ!|lI]{3}-?[abc]?)/i", $collector, $cMats)) {//$i=0;foreach($cMats as $cMat) echo "\n2613, cMats[".$i++."] = ".$cMat."\n";
						if($isIdentifier) $identifiedBy = $collector;
						if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
							$temp = trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
							$temp2 = trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
							$pos = strpos($temp2, " ");
							if(strpos($temp, " ") === FALSE && $pos !== FALSE) {
								$associatedCollectors = $temp2;
								$collector = $temp." ".trim(substr($temp2, $pos+1));
							} else {
								$collector = $temp;
								$associatedCollectors = $temp2;
							}
							return array
							(
								'collectorName' => $collector,
								'associatedCollectors' => $associatedCollectors,
								'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						} else {
							return array
							(
								'collectorName' => trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						}
					}
					if(preg_match("/(.*)\(?(?:No\\b\\.? ?|# ?)([0-9OQ!|lI]+-?[abc]?)\)?/i", $collector, $cMats)) {//$i=0;foreach($cMats as $cMat) echo "\n2644, cMats[".$i++."] = ".$cMat."\n";
						$collector = trim($cMats[1]);
						if($isIdentifier) $identifiedBy = $collector;
						if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
							$temp = trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
							$temp2 = trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
							$pos = strpos($temp2, " ");
							if(strpos($temp, " ") === FALSE && $pos !== FALSE) {
								$associatedCollectors = $temp2;
								$collector = $temp." ".trim(substr($temp2, $pos+1));
							} else {
								$collector = $temp;
								$associatedCollectors = $temp2;
							}
							return array
							(
								'collectorName' => $collector,
								'associatedCollectors' => $associatedCollectors,
								'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						} else {
							return array
							(
								'collectorName' => trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						}
					}//echo "\nline 2759, collector = ".$collector."\ncollectorNum: ".$collectorNum."\nnextLine: ".$nextLine."\n";
					if(preg_match("/(.*)(?:\\bNo\\b\\.? ?|# ?)([0-9OQ!|lI]+[abc]?)/i", $collector, $cMats)) {
						$collector = trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
						if($isIdentifier) $identifiedBy = $collector;
						if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
							$temp = trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
							$temp2 = trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
							$pos = strpos($temp2, " ");
							if(strpos($temp, " ") === FALSE && $pos !== FALSE) {
								$associatedCollectors = $temp2;
								$collector = $temp." ".trim(substr($temp2, $pos+1));
							} else {
								$collector = $temp;
								$associatedCollectors = $temp2;
							}
							return array
							(
								'collectorName' => $collector,
								'associatedCollectors' => $associatedCollectors,
								'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						} else {
							return array
							(
								'collectorName' => $collector,
								'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						}
					}
					if(preg_match("/(.*)\\bSpecimen:\\s(.*)/i", $collector, $cMats)) {
						$temp = trim($cMats[2]);
						if($this->containsNumber($temp) && str_word_count($temp) < 3) {
							$collector  = trim($cMats[1]);
							$collectorNum = $temp;
						}
					}//echo "\n2796, collector = ".$collector."\ncollectorNum: ".$collectorNum."\nnextLine: ".$nextLine."\n";
					if(preg_match("/(.+) (?:elev(?:ation|[,.;:])|A[1!|Il]t[,.;:]?) [0-9OQ!|lI,]{1,5} ?(?:m(?:[,.;:]|eters)?|f(?:ee)?t[,.;:]?)/i", $collector, $mats)) $collector = trim($mats[1]);
					if(preg_match("/(.*)\\s(?:[NW][o0Q][.ou#*_])(.*)/i", $collector, $cMats)) {
						$collector = trim($cMats[1], " \t\n\r\0\x0B.,:;!\"'\\~@#$%^&*_-");
						$temp = trim($cMats[2]);
						if($this->containsNumber($temp)) {
							$temp = $this->terminateCollectorNum($temp);
							$spacePos = strpos($temp, " ");
							if($spacePos !== FALSE) {
								$temp2 = substr($temp, 0, $spacePos);
								$temp3 = trim(substr($temp, $spacePos+1));
								$spacePos = strpos($temp3, " ");
								if($spacePos !== FALSE) $temp3 = trim(substr($temp3, $spacePos+1));
								if($this->containsNumber($temp2)) {
									if(is_numeric($temp3) || strlen($temp3) < 3) $collectorNum = $temp2." ".$temp3;
									else $collectorNum = $temp2;
								} else if(strlen($temp2) < 3 && $this->containsNumber($temp3)) $collectorNum = $temp2." ".$temp3;
							} else $collectorNum = $temp;
							if($isIdentifier) $identifiedBy = $collector;
							if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
								$temp = trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
								$temp2 = trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
								$pos = strpos($temp2, " ");
								if(strpos($temp, " ") === FALSE && $pos !== FALSE) {
									$associatedCollectors = $temp2;
									$collector = $temp." ".trim(substr($temp2, $pos+1));
								} else {
									$collector = $temp;
									$associatedCollectors = $temp2;
								}
								return array
								(
									'collectorName' => $collector,
									'associatedCollectors' => $associatedCollectors,
									'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
									'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
								);
							} else {
								return array
								(
									'collectorName' => $collector,
									'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
									'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
								);
							}
						}
					} else if(preg_match("/(.+)\\ss\\.n\\b/i", $collector, $mats)) {
						if($isIdentifier) $identifiedBy = $collector;
						if(preg_match("/(.+)(?: et\\.? | and |&)(.+)/i", $collector, $mats2)) {
							$temp = trim($mats2[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
							$temp2 = trim($mats2[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
							$pos = strpos($temp2, " ");
							if(strpos($temp, " ") === FALSE && $pos !== FALSE) {
								$associatedCollectors = $temp2;
								$collector = $temp." ".trim(substr($temp2, $pos+1));
							} else {
								$collector = $temp;
								$associatedCollectors = $temp2;
							}
							return array
							(
								'collectorName' => $collector,
								'associatedCollectors' => $associatedCollectors,
								'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						} else {
							return array
							(
								'collectorName' => trim($mats[1]),
								'identifiedBy' => trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						}
					} else if($this->containsNumber($collector)) {
						$collector = trim($this->removeDate($collector), " ;:.,");
						if($this->containsNumber($collector)) {
							$pos = strrpos($collector, " ");
							if($pos !== FALSE) {
								$firstPart = trim(substr($collector, 0, $pos));
								$lastPart = trim(substr($collector, $pos));
								if($this->containsNumber($lastPart) && !preg_match("/(?:".$possibleMonths.")/", $lastPart)) {
									if($this->containsNumber($firstPart)) {
										$pos = strrpos($firstPart, " ");
										if($pos !== FALSE) {
											$startOfFirstPart = trim(substr($firstPart, 0, $pos));
											if(strpos($startOfFirstPart, " ") !== FALSE) {
												if(!$this->containsNumber($startOfFirstPart)) {
													$firstPart = $startOfFirstPart;
													$lastPart = trim(substr($firstPart, $pos+1))." ".$lastPart;
												} else {
													$pos2 = strrpos($startOfFirstPart, " ");
													$startOfStartOfFirstPart = trim(substr($startOfFirstPart, 0, $pos2));
													if(!$this->containsNumber($startOfStartOfFirstPart) && strpos($startOfStartOfFirstPart, " ") !== FALSE) {
														$lastPart = trim(substr($firstPart, $pos2+1))." ".$lastPart;
														$firstPart = $startOfStartOfFirstPart;
													}
												}
											}
										}
									}
									if(strpos($firstPart, " ") !== FALSE) {
										$collector = $this->terminateCollector($firstPart);
										if(strlen($collector) > 1) {
											if($this->containsNumber($collector)) $collector = str_replace("1", "l", $collector);
											if(!$this->containsNumber($collector)) {
												if(!preg_match("/(?:".$possibleMonths.")/", $lastPart)) $collectorNum = str_replace(" ", "", $lastPart);
												if(preg_match("/(.+)(?: and |&)(.+)/i", $firstPart, $mats)) {
													$temp = trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
													$temp2 = trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
													$pos = strpos($temp2, " ");
													if(strpos($temp, " ") === FALSE && $pos !== FALSE) {
														$associatedCollectors = $temp2;
														$collector = $temp." ".trim(substr($temp2, $pos+1));
													} else {
														$collector = $temp;
														$associatedCollectors = $temp2;
													}
													if($isIdentifier) $identifiedBy = $collector;
													return array
													(
														'collectorName' => $collector,
														'associatedCollectors' => $associatedCollectors,
														'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
														'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
													);
												} else {
													if($isIdentifier) $identifiedBy = $collector;
													return array
													(
														'collectorName' => $collector,
														'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
														'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
													);
												}
											}
										}
									}
								}
							}
						} else if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
							$temp = trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
							$temp2 = trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
							$pos = strpos($temp2, " ");
							if(strpos($temp, " ") === FALSE && $pos !== FALSE) {
								$associatedCollectors = $temp2;
								$collector = $temp." ".trim(substr($temp2, $pos+1));
							} else {
								$collector = $temp;
								$associatedCollectors = $temp2;
							}
							if($isIdentifier) $identifiedBy = $collector;
							return array
							(
								'collectorName' => $collector,
								'associatedCollectors' => $associatedCollectors,
								'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						} else {
							if($isIdentifier) $identifiedBy = $collector;
							return array
							(
								'collectorName' => $collector,
								'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						}
					}
				}
				if($nextLine && ($collector || $collectorNum)) {
					if(preg_match("/(.*)\\b(?:N[o0Q][.o]|#)(.*)/i", $nextLine, $cMats)) {//$i=0;foreach($cMats as $cMat) echo "\n2878, cMats[".$i++."] = ".$cMat."\n";
						$collector .= " ".trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
						$temp = trim($cMats[2]);
						if($this->containsNumber($temp)) $collectorNum = $this->terminateCollectorNum($temp);
					} else if(strpos($nextLine, " ") === FALSE && $this->containsNumber($nextLine)) $collectorNum = $nextLine;
				}
				if(preg_match("/(.*)\\bDet[.:;,](.*)/i", $collector, $mats)) {
					$temp = trim($mats[2]);
					$mPat = "/^.+?\\b(?:(?:[o0Q]?+[!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])[ -]\\s?(?:".$possibleMonths.
						")[ -]\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2}))\\s(.+)/i";
					if(preg_match($mPat, $temp, $dMatches)) {
						if(count($dMatches) > 1) {
							$temp = trim($dMatches[1]);
							if(strlen($temp) > 1 && $this->containsNumber($temp) && str_word_count($temp) < 3) $collectorNum = $temp;
						}
					}
					$collector = trim($mats[1]);
				} else if(preg_match("/(.*)\\bDet[.:;,]/i", $collectorNum, $mats)) {
					$temp = trim($mats[1]);
					if($this->containsNumber($temp)) $collectorNum = $temp;
					else $collectorNum = "";
				}
				$dateIndex = stripos($collector, " Date ");
				if($dateIndex !== false) $collector = substr($collector, 0, $dateIndex);
				if($collector) $collector = $this->terminateCollector($collector);
				if($collectorNum) {
					$temp = $this->terminateCollectorNum($collectorNum);
				}
				if($collector && !$collectorNum) {
					$spacePos = strrpos($collector, " ");
					if($spacePos !== FALSE) {//echo "\nline 6961, spacePos !== FALSE\n";
						$potCollector = trim(substr($collector, 0, $spacePos));
						$potCollNum = trim(substr($collector, $spacePos));
						while($spacePos !== FALSE && !$this->containsNumber($potCollNum)) {
							$potCollNum = trim(substr($potCollector, $spacePos));
							$potCollector = trim(substr($potCollector, 0, $spacePos));
							$spacePos = strrpos($potCollector, " ");
						}
						if(strlen($potCollector) > 3 && $this->containsNumber($potCollNum) && str_word_count($potCollNum) < 3) {
							$collector = $potCollector;
							$collectorNum = $potCollNum;
						}
					}
				}
				$collectorNum = trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
				if($collectorNum) $collectorNum = $this->extractCollectorNum($str);
				$collector = str_replace(chr(13), " ", $collector);
				if($collectorNum) {
					$andPos = strpos($collectorNum, " & ");
					if($andPos !== FALSE) {
						$collector .= " ".trim(substr($collectorNum, $andPos));
						$collectorNum = trim(substr($collectorNum, 0, $andPos));
					} else {
						$andPos = strpos($collectorNum, " and ");
						if($andPos !== FALSE) {
							$collector .= " ".trim(substr($collectorNum, $andPos));
							$collectorNum = trim(substr($collectorNum, 0, $andPos));
						}
					}
					if(preg_match("/(.*)Elev(?:\\.|ation)?/i", $collectorNum, $mats)) $collectorNum = trim($mats[1]);
					else if(preg_match("/(.+)\\s\\d{1,5}\\s?(?:m|rn|ft)\\.?/i", $collectorNum, $mats)) $collectorNum = trim($mats[1]);
					if(!$this->containsNumber($collectorNum)) $collectorNum = "";
				}//echo "\nline 3027, collector: ".$collector.", collectorNum: ".$collectorNum."\n";
				if(preg_match("/^(?:[A-Za-z]+\\s)?by\\s(.*)/i", $collector, $mats)) {
					$collector = trim($mats[1]);
					if(preg_match("/(.+)\\s[A-Za-z ,.]+\\sby\\s.*/i", $collector, $mats2)) {
						if(count($mats2) > 1) $collector = trim($mats2[1]);
					}
				}//echo "\nline 3036, collector: ".$collector.", collectorNum: ".$collectorNum."\n";
				if($collector) {
					if($isIdentifier) $identifiedBy = $collector;
					if(strlen($collectorNum) > 2) {
						if(strcmp(substr($collectorNum, 0, 1), "(") == 0) {
							if(strcmp(substr(strrev($collectorNum), 0, 1), "(") == 0) $collectorNum = trim($collectorNum, " ()");
						}
					} else {
						$lines = array_reverse(explode("\n", trim($str)));
						$count = count($lines);
						$index = 0;
						$lastLine = trim($lines[0]);
						while(strlen($lastLine) == 0 && $index < $count) {
							if(!$this->isMostlyGarbage($lastLine, 0.48)) $lastLine = trim($lines[$index++]);
							else $index++;
						}
						if(preg_match("/.?(?:#|N[o0][. :;])(.+)/", $lastLine, $mats)) {
							$temp = trim($mats[1]);
							if($this->containsNumber($temp) && !$this->isNumericMDYDate($temp)) $collectorNum = $temp;
							if(preg_match("/(.+)Det(?:[:,;. *]|ermined)/i", $collectorNum, $mats2)) $collectorNum = trim($mats2[1]);
						} else if(is_numeric($lastLine)) $collectorNum = $lastLine;
					}
					if(preg_match("/^([A-Za-z]+ [A-Z]\\.?)(?: et\\.? | and | ?& ?)([A-Za-z]+ .?[A-Z]\\.?) ([A-Z][A-Za-z]+.*)/i", $collector, $mats)) {
						return array
						(
							'collectorName' => trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")." ".trim($mats[3], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'associatedCollectors' => trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")." ".trim($mats[3], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'otherCatalogNumbers' => $otherCatalogNumbers,
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					} else if(preg_match("/^([A-Z](?:\\b\\.?|[A-Z]+)(?: ?[A-Z]\\.?)?)(?: et\\.? | and | ?& ?)([A-Z](?:\\b\\.?|[A-Z]+)(?: ?[A-Z]\\.?)?) ([A-Z][A-Za-z]+.*)/i", $collector, $mats)) {
						return array
						(
							'collectorName' => trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")." ".trim($mats[3], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'associatedCollectors' => trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")." ".trim($mats[3], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'otherCatalogNumbers' => $otherCatalogNumbers,
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					} else if(preg_match("/^((?:[^ ]+ ){1,2}[^ ]+)(?: et\\.? | and | ?&)(.+)/i", $collector, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 2817, mats[".$i++."] = ".$mat."\n";
						$collector = trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
						if($isIdentifier) $identifiedBy = $collector;
						return array
						(
							'collectorName' => $collector,
							'associatedCollectors' => trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'otherCatalogNumbers' => $otherCatalogNumbers,
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					} else if(preg_match("/^((?:[^ ]+ ){1,2}[^ ]+)[,;:] (?:[^ ]+ ){2,}/i", $collector, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 2812, mats[".$i++."] = ".$mat."\n";
						$collector = trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
						if($isIdentifier) $identifiedBy = $collector;
						return array
						(
							'collectorName' => $collector,
							'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'otherCatalogNumbers' => $otherCatalogNumbers,
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					} else {//echo "\nline 2822, collector: ".$collector.", collectorNum: ".$collectorNum."\n";
						if(preg_match("/^.*(?<!\\bcommanded )(?<!\\bdistributed )(?<!\\bedited )(?<!\\b(?:identified|determined) )\\bby[;:,]? (.*)/i", $collector, $nMats)) {//$i=0;foreach($nMats as $nMat) echo "\nline 2824, nMats[".$i++."] = ".$nMat."\n";
							$collector = trim($nMats[1]);
						}
						$namePat = "/^([A-Z](?i:\\.?|[A-Za-z]+) [A-Z]\\.?\\s[a-zA-Z]{2,}\\b(?:, (?:Jr\\.?|III))?)[:;,.]?(.*)/i";
						if(preg_match($namePat, $collector, $nMats)) {//$i=0;foreach($nMats as $nMat) echo "\nline 2824, nMats[".$i++."] = ".$nMat."\n";
							$collector = trim($nMats[1]);
							$temp = trim($nMats[2]);
							$spacePos = strpos($temp, " ");
							if($spacePos !== FALSE) {
								$temp2 = trim(substr($temp, 0, $spacePos));
								if(is_numeric($temp2)) {
									$rest = trim(substr($temp, $spacePos+1));
									if(!preg_match("/^(?:".$possibleMonths.").*/i", $rest) && strlen($collectorNum) == 0) {
										$collectorNum = $rest;
									}
								}
							} else if(is_numeric($temp) && strlen($collectorNum) == 0) $collectorNum = $temp;
						}
						if($isIdentifier) $identifiedBy = $collector;
						return array
						(
							'collectorName' => trim($collector, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'otherCatalogNumbers' => $otherCatalogNumbers,
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					}
				}
			}
			//I have observed that on many labels -- especially those from NYBG -- have a line that says
			//something like "Collected on the 4th Crum Bryophyte Workshop"
			//when this are there the collector is often a line or two above it
			$collPatternStr = "/(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)Co[l|1!I]{2}ected (?:during|on) the (?:.*)/i";
			if(preg_match($collPatternStr, $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n6984, collMatches[".$i++."] = ".$collMatche.".\n";
				$result = $this->extractCollectorInfo($str, trim($collMatches[4]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[3]), trim($collMatches[4]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
				if($result != null) return $result;
			}
			if(preg_match("/(.*)(?:Identified|Det(?:\\.|ermined))(?: by)?[:;, ](.*)/is", $str, $mats)) {
				if(strlen(trim($mats[1])) > strlen(trim($mats[2]))) {
					$collPatternStr = "/(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)(.*)(?:Identified|Det(?:\\.|ermined))(?: by)?[:;, ](?:.*)/i";
					if(preg_match($collPatternStr, $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n2851, collMatches[".$i++."] = ".$collMatche.".\n";
						$result = $this->extractCollectorInfo($str, trim($collMatches[3]));
						if($result != null) return $result;
						$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[1]));
						if($result != null) return $result;
						$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
						if($result != null) return $result;
					}
					$collPatternStr = "/(?:(?:(.*)(?:\\r\\n|\\n|\\r)(.*))?(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r))?(.*)$/i";
					if(preg_match($collPatternStr, $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n2023, collMatches[".$i++."] = ".$collMatche."\n";
						$countMatches = count($collMatches);
						if($countMatches == 5) {
							$result = $this->extractCollectorInfo($str, trim($collMatches[4]));
							if($result != null) return $result;
							$result = $this->extractCollectorInfo($str, trim($collMatches[3]), trim($collMatches[4]));
							if($result != null) return $result;
							$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
							if($result != null) return $result;
							$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
							if($result != null) return $result;
						} else if($countMatches == 4) {
							$result = $this->extractCollectorInfo($str, trim($collMatches[3]));
							if($result != null) return $result;
							$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
							if($result != null) return $result;
							$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
							if($result != null) return $result;
						} else if($countMatches == 3) {
							$result = $this->extractCollectorInfo($str, trim($collMatches[2]));
							if($result != null) return $result;
							$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
							if($result != null) return $result;
						} else if($countMatches == 2) {
							$result = $this->extractCollectorInfo($str, trim($collMatches[1]));
							if($result != null) return $result;
						}
					}
				} else {
					$collPatternStr = "/(?:(?:(.*)(?:\\r\\n|\\n|\\r)(.*))?(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r))?(.*)$/i";
					if(preg_match($collPatternStr, $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n2023, collMatches[".$i++."] = ".$collMatche."\n";
						$countMatches = count($collMatches);
						if($countMatches == 5) {
							$result = $this->extractCollectorInfo($str, trim($collMatches[4]));
							if($result != null) return $result;
							$result = $this->extractCollectorInfo($str, trim($collMatches[3]), trim($collMatches[4]));
							if($result != null) return $result;
							$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
							if($result != null) return $result;
							$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
							if($result != null) return $result;
						} else if($countMatches == 4) {
							$result = $this->extractCollectorInfo($str, trim($collMatches[3]));
							if($result != null) return $result;
							$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
							if($result != null) return $result;
							$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
							if($result != null) return $result;
						} else if($countMatches == 3) {
							$result = $this->extractCollectorInfo($str, trim($collMatches[2]));
							if($result != null) return $result;
							$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
							if($result != null) return $result;
						} else if($countMatches == 2) {
							$result = $this->extractCollectorInfo($str, trim($collMatches[1]));
							if($result != null) return $result;
						}
					}
					$collPatternStr = "/(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)(.*)(?:Identified|Det(?:\\.|ermined))(?: by)?[:;, ](?:.*)/i";
					if(preg_match($collPatternStr, $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n2851, collMatches[".$i++."] = ".$collMatche.".\n";
						$result = $this->extractCollectorInfo($str, trim($collMatches[3]));
						if($result != null) return $result;
						$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[1]));
						if($result != null) return $result;
						$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
						if($result != null) return $result;
					}
				}
			} else {
				$collPatternStr = "/(?:(?:(.*)(?:\\r\\n|\\n|\\r)(.*))?(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r))?(.*)$/i";
				if(preg_match($collPatternStr, $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n2023, collMatches[".$i++."] = ".$collMatche."\n";
					$countMatches = count($collMatches);
					if($countMatches == 5) {
						$result = $this->extractCollectorInfo($str, trim($collMatches[4]));
						if($result != null) return $result;
						$result = $this->extractCollectorInfo($str, trim($collMatches[3]), trim($collMatches[4]));
						if($result != null) return $result;
						$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
						if($result != null) return $result;
						$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
						if($result != null) return $result;
					} else if($countMatches == 4) {
						$result = $this->extractCollectorInfo($str, trim($collMatches[3]));
						if($result != null) return $result;
						$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
						if($result != null) return $result;
						$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
						if($result != null) return $result;
					} else if($countMatches == 3) {
						$result = $this->extractCollectorInfo($str, trim($collMatches[2]));
						if($result != null) return $result;
						$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
						if($result != null) return $result;
					} else if($countMatches == 2) {
						$result = $this->extractCollectorInfo($str, trim($collMatches[1]));
						if($result != null) return $result;
					}
				}
			}
			$collPatternStr = "/(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\n|\\n\\r)NEW \\w{4} BOTAN.+/i";
			if(preg_match($collPatternStr, $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n6973, collMatches[".$i++."] = ".$collMatche.".\n";
				$result = $this->extractCollectorInfo($str, trim($collMatches[4]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[3]), trim($collMatches[4]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
				if($result != null) return $result;
			}
		}
		return array();
	}


	private function processCollectorQueryResult($r2, $lName, $fName, $mName) {//echo "\nInput to processCollectorQueryResult:\nlName: ".$lName."\nfName: ".$fName."\nmName: ".$mName,"\n";
		$firstName = $r2->firstName;
		$middleInitial = $r2->middleName;
		if(strlen($fName) == 1 && strlen($firstName) > 1) $firstName = substr($firstName, 0, 1);
		if(strlen($mName) == 1 && strlen($middleInitial) > 1) $middleInitial = substr($middleInitial, 0, 1);
		if($firstName && strcmp($firstName, $fName) == 0) {
			if($middleInitial && $mName) {
                // TODO: Check if downstream consumers can substitute agentID for collectorID.
				if(strcasecmp($middleInitial, $mName) == 0) {
					return array
					(
						'agentID' => $r2->agentid,
						'collectorID' => $r2->agentid,
						'familyName' => $lName,
						'collectorName' => $r2->firstName." ".$middleInitial." ".$lName
					);
				}
			} else {
				return array
				(
					'agentID' => $r2->agentid,
				    'collectorID' => $r2->agentid,
					'familyName' => $lName,
					'collectorName' => $r2->firstName." ".$lName
				);
			}
		}
		return array();
	}

	private function getCollectorFromDatabase($name, $string) {
		if(strlen($name) > 2) {//echo "\nInput to getCollectorFromDatabase, Name: ".$name.", String: ".$string."\n";
			$sql = "SELECT c.agentid, c.familyName, c.firstName, c.middleName ".
				"FROM agents c ".
				"WHERE c.familyName = '".str_replace(array("\"", "'"), "", $name)."'";

			if($r2s = $this->conn->query($sql)) {
				$firstName = "";
				$middleName = "";
				if($pName = $this->processCollectorName($name, $string)) {
					if(array_key_exists('fName', $pName)) $firstName = $pName['fName'];
					if(array_key_exists('mName', $pName)) $middleName = $pName['mName'];
				}
				while($r2 = $r2s->fetch_object()){
					$result = $this->processCollectorQueryResult($r2, $name, $firstName, $middleName);
					if($result) return $result;
				}
				//failed to match whole first and middle names.  Try to match first and middle initials
				if(strlen($firstName) > 1 && strcasecmp($name, "Park") != 0) {//need exact match for such a common string
					$firstName = substr($firstName, 0, 1);
					if(strlen($middleName) > 1) $middleName = substr($middleName, 0, 1);
					if($r2s = $this->conn->query($sql)) {
						while($r2 = $r2s->fetch_object()){
							$result = $this->processCollectorQueryResult($r2, $name, $firstName, $middleName);
							if($result) return $result;
						}
					}
				}
			}
		}
		return array();
	}

	protected function getScientificName($s) {
		$sciNamePatStr = "/Scientific Name\\b[:;,]? (.*)/i";
		if(preg_match($sciNamePatStr, $s, $sciNameMatches)) return trim($sciNameMatches[1]);
		return "";
	}

	protected function getMunicipality($s) {
		$townPatStr = "/\\b(?:T[o0]wn|C[lI!|1]ty|V[lI!|1]{3}age) [o0][fr][;:]?\\s([!|0152\\w]+\\s?(?:[0152\\w]+)?)[:;,.]/is";
		if(preg_match($townPatStr, $s, $townMatches)) {
			return str_replace(array("0", "1", "!", "|", "5", "2"), array("O", "l", "l", "l", "S", "Z"), trim($townMatches[1]));
		}
	}

	protected function getAssociatedTaxa($str) {
		$atPat = "/(?:(?:Assoc(?:[,.]|[l!1|i]ated)\\s(?:Taxa|Spec[l!1|]es|P[l!1|]ants|with|spp[,.]?)[:;]?)|containing|".
			"(?:along|together)\\swith(?:,?\\s?e[.\\s]?g\\.,)?)\\s(?:include\\s)?(.*)/is";
		if(preg_match($atPat, $str, $matches)) {//$i=0;foreach($matches as $matche) echo "\nline 2563, matches[".$i++."] = ".$matche."\n";
			$taxa = trim($matches[1], " ,.:;");
			$result = "";
			while(preg_match("/^([A-Za-z]+\\.?(?: [A-Za-z]+\\.?)?)(?:,| ?&| and )(.*)/is", $taxa, $mats)) {
				$temp = trim($mats[1]);
				if($this->isPossibleSciName($temp)) {
					$result .= $temp.", ";
					$taxa = trim($mats[2]);
				} else break;
			}
			$result = trim($result, " ,");
			if($taxa) {
				if(preg_match("/^([A-Za-z]+\\.?(?: [A-Za-z]+)?)[. ]/", $taxa, $mats)) {
					$temp = trim($mats[1]);
					if($this->isPossibleSciName($temp)) {
						$result .= ", ".$temp;
						$result = trim($result, " ,");
					} else {
						$pos = strpos($temp, " ");
						if($pos !== FALSE) {
							$temp = trim(substr($temp, 0, $pos));
							if($this->isPossibleSciName($temp)) {
								$result .= ", ".$temp;
								$result = trim($result, " ,");
							}
						}
					}
				}
			}
			$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
			$endPatStr = "/(.*?)(?:(?:\\d{1,3}(?:\\.\\d{1,7})?\\s?�)|Alt.|Elev|\\son\\s|Date|[;:]|(?:(?:\\d{1,2}\\s)?(?:".$possibleMonths.")))/is";
			if(preg_match($endPatStr, $taxa, $tMatches)) $taxa = trim($tMatches[1]);
			return str_replace(array("\r\n","\n", "- "), array(" ", " ", ""), $result);
		}
		return "";
	}

	protected function getLocality($s) {
		//echo "\nInput to getLocality: ".$s."\n";
		$locationPatStr = "/\\n(?:L|(?:\|_))[o0]ca(?:[1!lI]{2}ty|[1!lI]e|ti[o0]n|\\.)[:;,)]? (?!Habitat)(.*)(?:(?:\\n|\\n\\r)(.*))?/i";
		if(preg_match($locationPatStr, $s, $locationMatches)) {//$i=0;foreach($locationMatches as $locationMatche) echo "\nline 2170, locationMatches[".$i++."] = ".$locationMatche."\n";
			$location = trim($locationMatches[1]);
			if(strlen($location) < 3 && count($locationMatches) == 3) $location .= " ".trim($locationMatches[2]);
			return $location;
		} if(preg_match("/\\bL[o0]ca(?:[1!lI]{2}ty|[1!lI]e|ti[o0]n|\\.)[:;,)] (?!Habitat)(.*)(?:(?:\\n|\\n\\r)(.*))?/i", $s, $locationMatches)) {//$i=0;foreach($locationMatches as $locationMatche) echo "\nline 2174, locationMatches[".$i++."] = ".$locationMatche."\n";
			$location = trim($locationMatches[1]);
			if(strlen($location) < 3 && count($locationMatches) == 3) $location .= " ".trim($locationMatches[2]);
			return $location;
		} else if(preg_match("/\\b(?:C[o0][il1!|]{2}[ec]{2}t[il1!|][o0]n )?S[il1!|]te[:;,.]\\s(.+)/i", $s, $locationMatches)) {//$i=0;foreach($locationMatches as $locationMatche) echo "\nline 2126, locationMatches[".$i++."] = ".$locationMatche."\n";
			$location = trim($locationMatches[1]);
			if(strlen($location) < 3 && count($locationMatches) == 3) $location .= " ".trim($locationMatches[2]);
			return $location;
		}
	}

	protected function terminateField($f, $regExp) {
		if(preg_match($regExp, preg_quote($f, '/'), $ms)) {
			$temp = str_replace
			(
				array("\r\n", "\n", "\r", "\\"),
				array(" ", " ", " ", ""),
				$ms[1]
			);
		} else {
			$temp = str_replace
			(
				array("\r\n", "\n", "\r"),
				array(" ", " ", ""),
				$f
			);
		}
		if($this->isMostlyGarbage2($temp, 0.50)) return "";
		else return $temp;
	}

	protected function getHabitat($string) {//echo "\nInput to getHabitat: ".$string."\n\n";
		if($string) {
			$string = str_replace("\\:", ":", preg_quote($string, "/"));
			$habitatPatStr = "/((?s).+)\\n(?:Micro)?Hab[il1!|]tat[:;,.]? (.+)(?:\\r\\n|\\n\\r|\\n|\\r)((?s).+)(?:\\r\\n|\\n\\r|\\n|\\r)/i";
			if(preg_match($habitatPatStr, $string, $habitatMatches)) {//$i=0;foreach($habitatMatches as $habitatMatche) echo "\nhabitatMatches[".$i++."] = ".$habitatMatche."\n";
				$firstPart = stripslashes(trim($habitatMatches[1]));
				$habitat = stripslashes(trim($habitatMatches[2]));
				$nextLine = stripslashes(trim($habitatMatches[3]));
				//echo "\nline 2148, firstPart: ".$firstPart.", habitat: ".$habitat.", nextLine: ".$nextLine."\n\n";
				if(!$this->isMostlyGarbage($habitat, 0.51) && !preg_match("/^.{0,3}(?:Co[il1!|]{2}ect[^i]|Det(?:\\.|ermine))/i", $habitat)) return array($firstPart, str_replace(array("\r\n", "\n", "\r"), array(" ", " ", " "), $habitat), $nextLine);
			} else {
				$habitatPatStr = "/((?s).+)\\n(?:Micro)?Hab[il1!|]tat[:;,.]? (.+)(?:\\r\\n|\\n\\r|\\n|\\r)((?s).+)/i";
				if(preg_match($habitatPatStr, $string, $habitatMatches)) {//echo "\nsecond Match\n";
					$firstPart = stripslashes(trim($habitatMatches[1]));
					$habitat = stripslashes(trim($habitatMatches[2]));
					$nextLine = stripslashes(trim($habitatMatches[3]));
					if(!$this->isMostlyGarbage($habitat, 0.51) && !preg_match("/^.{0,3}(?:Co[il1!|]{2}ect[^i]|Det(?:\\.|ermine))/i", $habitat)) return array($firstPart, str_replace(array("\r\n", "\n", "\r"), array(" ", " ", " "), $habitat), $nextLine);
				} else {
					$habitatPatStr = "/((?s).+)\\n(?:Micro)?Hab[il1!|]tat[:;,.]? (.+)/i";
					if(preg_match($habitatPatStr, $string, $habitatMatches)) {//echo "\nthird Match\n";
						$firstPart = stripslashes(trim($habitatMatches[1]));
						$habitat = stripslashes(trim($habitatMatches[2]));
						if(!$this->isMostlyGarbage($habitat, 0.51) && !preg_match("/^.{0,3}(?:Co[il1!|]{2}ect[^i]|Det(?:\\.|ermine))/i", $habitat)) return array($firstPart, str_replace(array("\r\n", "\n", "\r"), array(" ", " ", " "), $habitat), "");
					} else {
						$habitatPatStr = "/((?s).+) (?:Micro)?Hab[il1!|]tat[:;,.] (.+)/i";
						if(preg_match($habitatPatStr, $string, $habitatMatches)) {//echo "\nthird Match\n";
							$firstPart = stripslashes(trim($habitatMatches[1]));
							$habitat = stripslashes(trim($habitatMatches[2]));
							if(!$this->isMostlyGarbage($habitat, 0.51) && !preg_match("/^.{0,3}(?:Co[il1!|]{2}ect[^i]|Det(?:\\.|ermine))/i", $habitat)) return array($firstPart, str_replace(array("\r\n", "\n", "\r"), array(" ", " ", " "), $habitat), "");
						} else {
							$habitatPatStr = "/((?s).+)(?:\\n|\\r\\n)S[il1!|]te[:;,.]? (.+)(?:\\r\\n|\\n\\r|\\n|\\r)((?s).+)(?:\\r\\n|\\n\\r|\\n|\\r)/i";
							if(preg_match($habitatPatStr, $string, $habitatMatches)) {//echo "\nfourth Match\n";
								$firstPart = stripslashes(trim($habitatMatches[1]));
								$habitat = stripslashes(trim($habitatMatches[2]));
								$nextLine = stripslashes(trim($habitatMatches[3]));
								if(!$this->isMostlyGarbage($habitat, 0.51) && !preg_match("/^.{0,3}(?:Co[il1!|]{2}ect[^i]|Det(?:\\.|ermine))/i", $habitat)) return array($firstPart, str_replace(array("\r\n", "\n", "\r"), array(" ", " ", " "), $habitat), $nextLine);
							}
						}
					}
				}
			}
		}
		return array("", "", "");
	}

	protected function getSubstrate($string) {
		if($string) {
			$subPatStr = "/(Substrat(?:es?|um)(?:[:;,.]| (?:i|wa)s| (?:appear|seem)(?:s|ed)? to be)? (.{3,}?)[;:.\n] ?)/i";
			if(preg_match($subPatStr, $string, $subMatches)) return $subMatches;
		}
	}

	protected function terminateSubstrate($sub) {
		$inPos = stripos($sub, " in ");
		if($inPos !== FALSE) return array(substr($sub, 0, $inPos), trim(substr($sub, $inPos)));
		$inPos = stripos($sub, " - ");
		if($inPos !== FALSE) return array(substr($sub, 0, $inPos), trim(substr($sub, $inPos)));
		$inPos = stripos($sub, " at ");
		if($inPos !== FALSE) return array(substr($sub, 0, $inPos), trim(substr($sub, $inPos)));
		$inPos = stripos($sub, " by ");
		if($inPos !== FALSE) return array(substr($sub, 0, $inPos), trim(substr($sub, $inPos)));
		$inPos = stripos($sub, " over ");
		if($inPos !== FALSE) return array(substr($sub, 0, $inPos), trim(substr($sub, $inPos)));
		if(preg_match("/^(.+ on .+) (on .*)/i", $sub, $mats)) return array(trim($mats[1]), trim($mats[2]));
		if(preg_match("/^(on .+) (on .*)/i", $sub, $mats)) return array(trim($mats[1]), trim($mats[2]));
		return array($sub, "");
	}

	private function calculateMaxMinElevation($verbatimElevation) {//echo "\nInput to calculateMaxMinElevation: ".$verbatimElevation."\n";
		if($verbatimElevation) {
			$verbatimElevation = str_replace(",", "", $verbatimElevation);
			if(preg_match("/^\\D*([0-9]{1,5}) ?- ?([0-9]{1,5}) ?(f(?:ee)?t|m(?:eters?|\\.?s\\.?m)?)[.,;:]?/i", $verbatimElevation, $mats)) {
				$min = trim($mats[1]);
				$max = trim($mats[2]);
				if(is_numeric($min) && is_numeric($max)) {
					$units = trim($mats[3]);
					$uAbbr = substr($units, 0, 1);
					if(strcasecmp($uAbbr, "f") == 0) return array('minimumElevationInMeters' => round($min/3.2808), 'maximumElevationInMeters' => round($max/3.2808));
					if(strcasecmp($uAbbr, "m") == 0) return array('minimumElevationInMeters' => $min, 'maximumElevationInMeters' => $max);
				}
			}
			if(preg_match("/^\\D*([0-9]{1,5}) ?(f(?:ee)?t|m(?:eters?|\\.?s\\.?m)?)[.,;:]?/i", $verbatimElevation, $mats)) {
				$min = trim($mats[1]);
				if(is_numeric($min)) {
					$units = trim($mats[2]);
					$uAbbr = substr($units, 0, 1);
					if(strcasecmp($uAbbr, "f") == 0) return array('minimumElevationInMeters' => round($min/3.2808));
					if(strcasecmp($uAbbr, "m") == 0) return array('minimumElevationInMeters' => $min);
				}
			}
		}
	}

	protected function getElevation($string) {
		if($string) {//echo "\nInput to getElevation: ".$string."\n";
			$possibleNumbers = "[OQSZl|I!&0-9^]";
			$approximateIndicators = "ab[o0]ut |ab[o0](?:ve|w) |ca\\.? |approx(?:\\.|imately)? |ar[o0]und |[-~]";
			//first, units: m.s.m. with a range of numbers preceded by label (elevation or altitude)
			$elevPatStr = "/(.*)(\(?\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)[:;,]?\\s?".
				"((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[IO0-9])".
				"(?:(?: ?- ?| to )(?:[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[I1-9])))\\sm\\.?s\\.?(?:[mn]|r[na])\)?[,.;: ]{0,2})(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2580, elevMatches0[".$i++."]: ".$elevMatche."\n";
				$elevation = str_ireplace(" to ", " - ", trim($elevMatches[3]));
				return array(trim($elevMatches[1]), $this->replaceMistakenNumbers($elevation)." m.s.m.", trim($elevMatches[4]));
			}
			//next units: m.s.m. with a range of numbers followed by label
			$elevPatStr = "/(.*)(?: |\\n)(\(?((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[IO0-9])".
				"(?:(?: ?- ?| to )(?:[SZl|I!&1-9^],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[I1-9])))\\s".
				"m\\.?s\\.?(?:[mn]|r[na])[,.;: ]{0,2}".
				"(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))\)?[:;,]?)\\s?(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2590, elevMatches0[".$i++."]: ".$elevMatche."\n";
				$elevation = str_ireplace(" to ", " - ", trim($elevMatches[3]));
				return array(trim($elevMatches[1]), $this->replaceMistakenNumbers($elevation)." m.s.m.", trim($elevMatches[4]));
			}
			//next units: m.s.m. with a range of numbers
			$elevPatStr = "/(.*)(?: |\\n)(\(?((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[IO0-9])".
				"(?:(?: ?- ?| to )(?:[SZl|I!&1-9^],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[I1-9])))\\s".
				"m\\.?s\\.?(?:[mn]|r[na])\)?[,.;: ]{0,2})(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2599, elevMatches0[".$i++."]: ".$elevMatche."\n";
				$elevation = str_ireplace(" to ", " - ", trim($elevMatches[3]));
				return array(trim($elevMatches[1]), $this->replaceMistakenNumbers($elevation)." m.s.m.", trim($elevMatches[4]));
			}
			//next units: m.s.m. preceded by approximate indicator and label
			$elevPatStr = "/(.*)(\(?\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)[:;,]?\\s?".
				"(?: |\\n)(".$approximateIndicators.")((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}|".$possibleNumbers."))\\sm\\.?s\\.?(?:[mn]|r[na])\)?[,.;: ]{0,2})(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2608, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array
				(
					trim($elevMatches[1]),
					$this->replaceMistakenLetters(trim($elevMatches[3]))." ".$this->replaceMistakenNumbers(trim($elevMatches[4]))." m.s.m.",
					trim($elevMatches[5])
				);
			}
			//next units: m.s.m. preceded by approximate indicator, followed by label
			$elevPatStr = "/(.*)(?: |\\n)(\(?(".$approximateIndicators.")([SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}|".$possibleNumbers.")\\sm\\.?s\\.?(?:[mn]|r[na])[,.;: ]{0,2}".
				"(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)\)?[:;,]?)\\s?(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2622, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array
				(
					trim($elevMatches[1]),
					$this->replaceMistakenLetters(trim($elevMatches[3]))." ".$this->replaceMistakenNumbers(trim($elevMatches[4]))." m.s.m.",
					trim($elevMatches[5])
				);
			}
			//next units: m.s.m. preceded by approximate indicator
			$elevPatStr = "/(.*)(?: |\\n)(\(?(".$approximateIndicators.")([SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}|".$possibleNumbers.")\\sm\\.?s\\.?(?:[mn]|r[na])\)?[,.;: ]{0,2})(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2635, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array
				(
					trim($elevMatches[1]),
					$this->replaceMistakenLetters(trim($elevMatches[3]))." ".$this->replaceMistakenNumbers(trim($elevMatches[4]))." m.s.m.",
					trim($elevMatches[5])
				);
			}
			//next units: m.s.m. preceded by label
			$elevPatStr = "/(.*)(\(?\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))[:;,]?\\s?".
				"((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}|".$possibleNumbers."))\\sm\\.?s\\.?(?:[mn]|r[na])\)?[,.;: ]{0,2})(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2649, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array(trim($elevMatches[1]), $this->replaceMistakenNumbers(trim($elevMatches[3]))." m.s.m.", trim($elevMatches[4]));
			}
			//next units: m.s.m. followed by label
			$elevPatStr = "/(.*)(?: |\\n)(\(?((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|".$possibleNumbers."))\\sm\\.?s\\.?(?:[mn]|r[na])[,.;: ]{0,2}".
				"\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))\)?[:;,]?)\\s?(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2658, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array(trim($elevMatches[1]), $this->replaceMistakenNumbers(trim($elevMatches[3]))." m.s.m.", trim($elevMatches[4]));
			}
			//next units: m.s.m.
			$elevPatStr = "/(.*)(?: |\\n)(\(?((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|".$possibleNumbers."))\\sm\\.?s\\.?(?:[mn]|r[na])[,.;: ]{0,2})(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2666, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array(trim($elevMatches[1]), $this->replaceMistakenNumbers(trim($elevMatches[3]))." m.s.m.", trim($elevMatches[4]));
			}
			//feet or meters, range, preceded by label
			$elevPatStr = "/(.*)(\(?\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)[:;,]?\\s?".
				"((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,. \n]| [it]t[,. \n]|(?:m|rn)(?:[,. \n]|eter[5s]?)?|Feet)?".
				"(?: ?- ?| to )(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[I1-9]))".
				"\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))\)?[,.;: ]{0,2})(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2679, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array(" to ", "it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array(" - ", "ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[3])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
			//feet or meters, range followed by label
			$elevPatStr = "/(.*)(?: |\\n)(\(?((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,. \n]| [it]t[,. \n]|(?:m|rn)(?:[,. \n]|eter[5s]?)|Feet)?".
				"(?: ?- ?| to )(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[I1-9])".
				"\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))),? ?".
				"(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))\)?[,.;: ]{0,2})(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2698, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array(" to ", "it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array(" - ", "ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[3])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
			//feet or meters, approximate, preceded by label
			$elevPatStr = "/^(.*?)(\(?+\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)[:;,]?\\s?".
				"(".$approximateIndicators.")((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n*]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet)))\)?[,.;: ]{0,2})(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2714, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = $this->replaceMistakenLetters($elevMatches[3]).
				str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[4])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[5]));
			}
			//feet or meters, preceded by label
			$elevPatStr = "/(.*)(?: |\\n)(\(?(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)[:;,]?\\s?".
				"((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,.;: \n]| [it]t[,.; \n*]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet)))\)?[,.;: ]{0,2})(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2731, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[3])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
			//feet or meters, approximate, followed by label
			$elevPatStr = "/(.*)(?: |\\n)(\(?(".$approximateIndicators.")".
				"\\b((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n*]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))),? ?".
				"(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))\)?[,.;: ]{0,2})(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2749, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = $this->replaceMistakenLetters($elevMatches[3]).
				str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[4])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[5]));
			}
			//feet or meters, followed by label
			$elevPatStr = "/(.*)\\s(\(?([l|I!1]".$possibleNumbers."[,.]".$possibleNumbers."{3}|[SZl|I!&1-9^][,.]".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[IO0-9])".
				"\\s{0,2}(ft[,.; \n]| [it]t[,.; \n*]|(?:m|rn)(?:[.; \n]|eter[5s]?)|Feet),? ?".
				"(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))\)?[,.;: ]{0,2})(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2765, elevMatches[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array(".", "O", "Q", "l", "|", "I", "!", "S", "Z", "\r\n", "\n", "\r", "&"),
					array(",", "0", "0", "1", "1", "1", "1", "5", "2", " ", " ", " ", "6"),
					trim($elevMatches[3])
				)." ".str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "Z", "tt", "rn", "\r\n", "\n", "\r", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "2", "ft", "m", " ", " ", " ", "meters"),
					trim($elevMatches[4])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[5]));
			}
			//feet or meters, range, exact, no label
			$elevPatStr = "/(.*)(?: |\\n)(\(?((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,. \n]| [it]t[,. \n]|(?:m|rn)(?:[,. \n]|eter[5s]?)|Feet)?".
				"(?: ?- ?| to )(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[I1-9]))".
				"\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))\)?[,.;: ]{0,2})".
				"(?!(?:N(?:orth)? |S(?:outh)? |E(?:ast)? |W(?:est)? |[NE]?NE |[NW]?NW |[SE]?SE |[SW]?SW )?(?:of|from|above|be(?:low|neath|side|yond))\\b)(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2784, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array(" to ", "it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array(" - ", "ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[3])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
			//feet or meters, approximate, no label
			$elevPatStr = "/(.*)(?: |\\n)(\(?(".$approximateIndicators.")".
				"((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|(?!\\.)[I1-9])".
				"\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))\)?[,.;: ]{0,2})".
				"(?!(?:N(?:orth)? |S(?:outh)? |E(?:ast)? |W(?:est)? |[NE]?NE |[NW]?NW |[SE]?SE |[SW]?SW )?(?:of|from|above|be(?:low|neath|side))\\b)(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2801, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = $this->replaceMistakenLetters($elevMatches[3]).
				str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[4])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[5]));
			}
			//labels formated as XXXm/YYYft or XXXft/YYYm
			$elevPatStr = "/(.*)(?: |\\n|\\r)(\(?((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[1-9])".
				"\\s?(?:ft[,.; ]?|(?:m|rn)(?:[,.; \n]|eter[5s]?)?|Feet)\/(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[1-9])".
				"\\s?(?:ft[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))\)?)".
				"(?!(?:N(?:orth)? |S(?:outh)? |E(?:ast)? |W(?:est)? |[NE]?NE |[NW]?NW |[SE]?SE |[SW]?SW )?(?:of|from|above|be(?:low|neath|side))\\b)(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2820, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[3])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
			//feet, indicated by a single quote, approximate, followed by label
			$elevPatStr = "/(.*)\\s(\(?(?:".$approximateIndicators.") ?".
				"\\b([l|I!1][[l|I!1O0-9],[[l|I!1O0-9]{3}|[l|I!1-9^],[[l|I!1O0-9]{3}|[l|I!1][[l|I!1O0-9]{4}|[l|I!1-9^][[l|I!1O0-9]{1,3}|(?!\\.)\\d) ?',? ?".
				"(?i:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))\)?[,.;: ]{0,2})(.*+)/s";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2749, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("O", "l", "|", "I", "!", "^", "\r\n", "\n", "\r"),
					array("0", "1", "1", "1", "1", "4", " ", " ", " "),
					trim($elevMatches[3])
				)." ft.";
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
			//feet, indicated by a single quote, approximate, preceded by label
			$elevPatStr = "/(.*)\\s(\(?(?i:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))[,.;: ]{0,2}(?:".$approximateIndicators.") ?".
				"\\b([l|I!1][[l|I!1O0-9],[[l|I!1O0-9]{3}|[l|I!1-9^],[[l|I!1O0-9]{3}|[l|I!1][[l|I!1O0-9]{4}|[l|I!1-9^][[l|I!1O0-9]{1,3}|(?!\\.)\\d) ?'[,.]? ?\)?)(.*+)/s";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2749, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("O", "l", "|", "I", "!", "^", "\r\n", "\n", "\r"),
					array("0", "1", "1", "1", "1", "4", " ", " ", " "),
					trim($elevMatches[3])
				)." ft.";
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
			//next, units: feet indicated by a single quote preceded by label (elevation or altitude)
			$elevPatStr = "/(.*)(\(?\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)[:;,]?\\s?".
				"((?:[l|I!1][[l|I!1O0-9],[[l|I!1O0-9]{3}|[l|I!1-9^],[l|I!1O0-9]{3}|[l|I!1-9^][l|I!1O0-9]{1,3}|\\d)) ?'[,.;: ]{0,2})(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2580, elevMatches0[".$i++."]: ".$elevMatche."\n";
				$elevation = str_ireplace
				(
					array("O", "l", "|", "I", "!", "^", "\r\n", "\n", "\r"),
					array("0", "1", "1", "1", "1", "4", " ", " ", " "),
					trim($elevMatches[3])
				)." ft.";
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
			//just numbers, feet or meters
			$elevPatStr = "/(.*)\\s(\(?((?:1\\d,\\d{3}|[1-9^],\\d{3}|1\\d{4}|[1-9]\\d{1,3}+|(?!\\.)\\d )".
				" {0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))\)?[,.;: ]{0,2})".
				"(?!(?:N(?:orth)? |S(?:outh)? |E(?:ast)? |W(?:est)? |[NE]?NE |[NW]?NW |[SE]?SE |[SW]?SW )?(?:of|from|above|be(?:low|neath|side))\\b)(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 3147, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("it", "^", "Q", "tt", "rn", "\r\n", "\n", "\r", "meter5"),
					array("ft", "4", "0", "ft", "m", " ", " ", " ", "meters"),
					trim($elevMatches[3])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
			//just numbers, feet or meters
			$elevPatStr = "/(.*)\\s(\(?((?:[Il1][IlO0-9],[IlO0-9]{3}|[Il1-9^],[IlO0-9]{3}|[Il1][IlO0-9]{4}|[Il1-9^][IlO0-9]{1,3}+|(?!\\.)\\d)".
				" {1,2}(?i:ft[,.; \n]|meter[5s]?|Feet)(?-i))\)?[,.;: ]{0,2})".
				"(?!(?i:N(?:orth)?(?-i) |S(?:outh)?(?-i) |E(?:ast)?(?-i) |W(?:est)?(?-i) |[NE]?NE |[NW]?NW |[SE]?SE |[SW]?SW )?(?i:of|from|above|be(?:low|neath|side)(?-i))\\b)(.*+)/s";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 3162, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("it", "O", "l", "|", "I", "!", "^", "tt", "rn", "\r\n", "\n", "\r", "meter5"),
					array("ft", "0", "1", "1", "1", "1", "4", "ft", "m", " ", " ", " ", "meters"),
					trim($elevMatches[3])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
		}
		return array("", "", "");
	}

	private function findTokenAtEnd($string, $token) {//echo "\nInput to findTokenAtEnd, string: ".$string.", token: ".$token."\n";
		$spacePos = strrpos($string, " ");
		$endpart = "";
		if($spacePos !== FALSE) {
			$endpart = substr($string, $spacePos+1);
			//echo "\n\nEndPart: ".$endpart.", Token: ".strtoupper($token)."\n\n";
			$rest = trim(substr($string, 0, $spacePos));
			if(strcasecmp($endpart, $token) == 0) return $token;
			else {
				$spacePos = strrpos($rest, " ");
				if($spacePos !== FALSE) {
					$nextToEndPart = trim(substr($rest, $spacePos+1));
					$nextToNextToEndPart = trim(substr($rest, 0, $spacePos));
					$endpart = $nextToEndPart." ".$endpart;
					if(strcasecmp($endpart, $token) == 0) return $token;
					$spacePos = strrpos($nextToNextToEndPart, " ");
					if($spacePos !== FALSE) {
						if(strcasecmp(trim(substr($nextToNextToEndPart, $spacePos+1))." ".$endpart, $token) == 0) return $token;
					}
				} else {
					$endpart = $rest." ".$endpart;
					if(strcasecmp($endpart, $token) == 0) return $token;
				}
			}
		} else if(strcasecmp($string, $token) == 0) return $token;
		return '';
	}

	private function doCountyQuery($c, $stateProvince="") {
		if($c) {
			if(strlen($c) > 2 && !is_integer($c)) {
				$result = array();
				$c = trim($c, " \t\n\r\0\x0B,.:;!()\"\'\\~@#$%^&*_-");
				$containsAmpersand = false;
				$hasStateProvince = strlen($stateProvince) > 0;
				if(preg_match("/(.+)&(.+)/", $c, $mats)) {
					$c = trim($mats[1])." and ".trim($mats[2]);
					$containsAmpersand = true;
				}
				$sql = 'SELECT cr.geoterm AS countryName, s.geoterm AS stateName, c.geoterm AS countyName
					FROM geographicthesaurus cr INNER JOIN geographicthesaurus s ON cr.geoThesID = s.parentID
					INNER JOIN geographicthesaurus c ON s.geoThesID = c.parentID
					WHERE c.geoterm = "'.str_replace(array('"', "'"), '', $this->cleanInStr($c)).'" ';
				if($hasStateProvince) $sql .= " AND s.geoterm = '".$this->cleanInStr($stateProvince)."'";
				if($rs = $this->conn->query($sql)) {
					if($rs->num_rows) {
						if($r = $rs->fetch_object()) {
							$c = $r->countyName;
							if($containsAmpersand) $c = str_ireplace(" and ", " & ", $c);
							array_push($result, array('county' => $c, 'stateProvince' => $r->stateName, 'country' => $r->countryName));
							while($r = $rs->fetch_object()) {
								array_push($result, array('county' => $c, 'stateProvince' => $r->stateName, 'country' => $r->countryName));
							}
							return $result;
						}
					}
				}
			}
		}
	}

	protected function getCounty($c, $state_province="") {//echo "\nline 3491, input to getCounty: ".$c."\nstate_province: ".$state_province."\n";
		$countyQueryResult = $this->doCountyQuery($c, $state_province);
		if($countyQueryResult) return $countyQueryResult;
		$c = ucwords
		(
			strtolower
			(
				str_replace
				(
					array('1', '!', '|', '5', '2', '0', '[', ']', '(', ')', '"', '\'', '-'),
					array('l', 'l', 'l', 'S', 'Z', 'O', '', '', '', '', '', '', ' '),
					$c
				)
			)
		);
		$countyQueryResult = $this->doCountyQuery(preg_replace_callback('/\\bSt\\b\\.?(.+)/', function($matches){ return "Saint ".trim($matches[1]);}, $c), $state_province);
		if($countyQueryResult) return $countyQueryResult;
		$countyQueryResult = $this->doCountyQuery(preg_replace_callback('/\\bSt\\b\\.?(.+)/', function($matches){ return "Sainte ".trim($matches[1]);}, $c), $state_province);
		if($countyQueryResult) return $countyQueryResult;
		if(stripos($c, "berg") !== FALSE) {
			$countyQueryResult = $this->doCountyQuery(str_ireplace("berg", "burg", $c), $state_province);
			if($countyQueryResult) return $countyQueryResult;
		}
		if(stripos($c, "burg") !== FALSE) {
			$countyQueryResult = $this->doCountyQuery(str_ireplace("burg", "berg", $c), $state_province);
			if($countyQueryResult) return $countyQueryResult;
		}
	}

	private function processCountyMatches($firstPart, $lastPart, $state_province="") {//echo "\nLine 3833, Input to processCountyMatches, firstPart: ".$firstPart."\nlastPart: ".$lastPart."\nstate_province: ".$state_province."\n";
		$countyArray = null;
		$possibleState = "";
		$county = "";
		$possibleCountry = "";
		$nextWord = "";
		$firstPart = trim($firstPart, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		$lastPart = trim($lastPart, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		if(strpos($lastPart, "\n") !== FALSE) {
			$nextWords = explode("\n", $lastPart);
			$nextWord = trim($nextWords[0], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
		} else $nextWord = trim($lastPart, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
		if(strpos($firstPart, "\n") !== FALSE) {
			$prevLines = array_reverse(explode("\n", $firstPart));
			$num = 0;
			$i=0;
			foreach($prevLines as $prevLine) {
				if($i < 2) {
					if(strlen($prevLine) > 1) {
						$i++;
						if($countyArray == null || !$countyArray) {
							$countyArray = $this->processCountyString($prevLine, $nextWord, $state_province, false);
							if($countyArray) {
								if(count($countyArray) == 1) return array_pop($countyArray);
								else {
									foreach($countyArray as $vs) {
										if(array_key_exists('stateProvince', $vs)) {
											$possibleStateProvince = $vs['stateProvince'];
											if(preg_match("/\\b".preg_quote($possibleStateProvince, '/')."\\b/i", $prevLine)) return $vs;
											else {
												$temp = $this->fetchStateAndCountryFromLine(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $prevLine." ".$nextWord), $vs['county'], $possibleStateProvince);
												if($temp) return $vs;
											}
										}
									}
								}
							}
						} else {
							foreach($countyArray as $vs) {
								if(array_key_exists('stateProvince', $vs)) {
									if(stripos($prevLine, $vs['stateProvince']) !== FALSE) return $vs;
									else {
										$temp = $this->fetchStateAndCountryFromLine(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $prevLine." ".$nextWord), $vs['county'], $vs['stateProvince']);
										if($temp) return $vs;
									}
								}
							}
						}
					}
				} else if($countyArray) {
					foreach($countyArray as $vs) {//foreach($vs as $k => $v) echo "\nline 3636, ".$k.": ".$v."\n";
						if(array_key_exists('stateProvince', $vs)) {
							if(stripos($prevLine, $vs['stateProvince']) !== FALSE) return $vs;
							else {
								$temp = $this->fetchStateAndCountryFromLine(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $prevLine." ".$nextWord), $vs['county'], $vs['stateProvince']);
								if($temp) return $vs;
							}
						}
					}
				}
			}
			if($countyArray) {
				$county = $countyArray[0]['county'];
				if(strcasecmp($county, "Park") != 0
					&& strcasecmp($county, "Lane") != 0
					&& strcasecmp($county, "Lake") != 0
					&& strcasecmp($county, "Woods") != 0
					&& strcasecmp($county, "Creek") != 0
					&& strcasecmp($county, "Canyon") != 0
					&& strcasecmp($county, "Pine") != 0
					&& strcasecmp($county, "Boulder") != 0) return array('county' => $county);//these are so common in the labels it should not be returned unless its state is found
			}
		} else {
			if($firstPart) {
				$countyArray = $this->processCountyString($firstPart, $nextWord, $state_province, false);
				if($countyArray) {
					if(count($countyArray) == 1) return $countyArray[0];
					else {
						$county = $countyArray[0]['county'];
						if(strcasecmp($county, "Park") != 0
							&& strcasecmp($county, "Lane") != 0
							&& strcasecmp($county, "Lake") != 0
							&& strcasecmp($county, "Woods") != 0
							&& strcasecmp($county, "Creek") != 0
							&& strcasecmp($county, "Canyon") != 0
							&& strcasecmp($county, "Pine") != 0
							&& strcasecmp($county, "Boulder") != 0) return array('county' => $county);//park and lane are so common in the labels it should not be returned unless its state is found
					}
				}
			}
		}
		if(!$county) {
			if($lastPart) {
				if(strpos($lastPart, "\n") !== FALSE) {
					$nextLines = explode("\n", $lastPart);
					$i=0;
					$num=0;
					$prevLine = "";
					foreach($nextLines as $nextLine) {
						if($i == 0) {
							if(strlen($nextLine) > 2) {
								$countyArray = $this->processCountyString($nextLine, "", $state_province, true);
								if($countyArray) {
									if(count($countyArray) == 1) return $countyArray[0];
									else {
										foreach($countyArray as $vs) {
											$county = $vs['county'];
											if(array_key_exists('stateProvince', $vs)) {
												$sp = $vs['stateProvince'];
												if(strcasecmp($state_province, $sp) == 0)  return $vs;
												if(stripos($nextLine, $sp) !== FALSE) return $vs;
												$temp = $this->fetchStateAndCountryFromLine(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $nextLine), $county, $sp);
												if($temp) return $vs;
											}
										}
									}
								}
								$i++;
							}
						} else if($countyArray) {//found a county on the first line
							$county = $countyArray[0]['county'];
							if(strlen($prevLine) > 2) {
								foreach($countyArray as $vs) {
									if(array_key_exists('stateProvince', $vs)) {
										if(stripos($prevLine, $vs['stateProvince']) !== FALSE) {
											$possibleState = $vs['stateProvince'];
											$possibleCountry = $vs['country'];
											$num++;
										}
									}
								}
								break;
							}
						}
						$prevLine = $nextLine;
					}
					if($countyArray) {
						if($num == 1) return array('county' => $county, 'stateProvince' => $possibleState, 'country' => $possibleCountry);
						else {
							$county = $countyArray[0]['county'];
							if(strcasecmp($county, "Park") != 0
								&& strcasecmp($county, "Lane") != 0
								&& strcasecmp($county, "Lake") != 0
								&& strcasecmp($county, "Woods") != 0
								&& strcasecmp($county, "Creek") != 0
								&& strcasecmp($county, "Canyon") != 0
								&& strcasecmp($county, "Pine") != 0
								&& strcasecmp($county, "Boulder") != 0) {//these are so common in the labels they should not be returned unless its state is found
								if($state_province) return array('county' => $county, 'stateProvince' => $state_province);
							}
						}
					}
				} else {
					$countyArray = $this->processCountyString($lastPart, "", $state_province, true);
					if($countyArray) {
						if(count($countyArray) == 1) return $countyArray[0];
						else {
							$county = $countyArray[0]['county'];
							if(strcasecmp($county, "Park") != 0
								&& strcasecmp($county, "Lane") != 0
								&& strcasecmp($county, "Lake") != 0
								&& strcasecmp($county, "Woods") != 0
								&& strcasecmp($county, "Creek") != 0
								&& strcasecmp($county, "Canyon") != 0
								&& strcasecmp($county, "Pine") != 0
								&& strcasecmp($county, "Boulder") != 0) {
								if($state_province) return array('county' => $county, 'stateProvince' => $state_province);
							}
						}
					}
				}
			}
		}
	}

	private function fetchStateAndCountryFromLine($line, $county, $state) {//this is to handle states that might be abbreviated
		$spacePos = strpos($line, " ");
		//echo "\nline 4163, line: ".$line.", county: ".$county.", state: ".$state."\n";
		$tStates = $this->getStateOrProvince(ucfirst($state));
		if($tStates) {
			$tState = $tStates[0];
			$possibleStateAndCountry = array();
			if($spacePos !== FALSE) {
				$tokens = array_reverse(explode(" ", $line));
				$tokenCount = count($tokens);
				$numTokens = 0;
				if($tokenCount == 2) {
					$lastToken = trim($tokens[0]);
					$tokenBefore = trim($tokens[1]);
					$possibleStateAndCountry = $this->getStateOrProvince(ucfirst(trim($tokenBefore." ".$lastToken)));
					if(count($possibleStateAndCountry) == 0) {
						$possibleStateAndCountry = $this->getStateOrProvince(ucfirst($lastToken));
						if(count($possibleStateAndCountry) == 0) $possibleStateAndCountry = $this->getStateOrProvince(ucfirst($tokenBefore));
					}
					if($possibleStateAndCountry) {
						$temp = $possibleStateAndCountry[0];
						if(strcasecmp($temp, $tState) == 0) {
							return array(
								'county' => $county,
								'stateProvince' => $temp,
								'country' => $possibleStateAndCountry[1]
							);
						}
					}
				} else {
					$tokenCount = 0;
					$prevToken = "";
					$tokenBeforePrevToken = "";
					foreach($tokens as $token) {
						if($tokenCount > 1) {//echo "\nline 4196, token: ".$token.", prevToken: ".$prevToken.", tokenBeforePrevToken: ".$tokenBeforePrevToken."\n";
							if($prevToken) {
								if($tokenBeforePrevToken) {
									$temp = trim($token." ".$prevToken." ".$tokenBeforePrevToken);
									$possibleStateAndCountry = $this->getStateOrProvince(ucfirst($temp));
									if(count($possibleStateAndCountry) == 0) {
										$temp = trim($prevToken." ".$tokenBeforePrevToken);
										$possibleStateAndCountry = $this->getStateOrProvince(ucfirst($temp));
										if(count($possibleStateAndCountry) == 0) {
											$temp = trim($tokenBeforePrevToken);
											if(strlen($temp) > 3) $possibleStateAndCountry = $this->getStateOrProvince(ucfirst($temp));
											else $possibleStateAndCountry = $this->getStateOrProvince($temp);
											if(count($possibleStateAndCountry) == 0) {
												if(strlen($temp) > 3) $possibleStateAndCountry = $this->getStateOrProvince(ucfirst($prevToken));
												else $possibleStateAndCountry = $this->getStateOrProvince($prevToken);
											}
										}
									}
								} else {
									$temp = trim($token." ".$prevToken);
									$possibleStateAndCountry = $this->getStateOrProvince(ucfirst($temp));
								}
							} else if($tokenBeforePrevToken) {
								$temp = trim($token." ".$tokenBeforePrevToken);
								$possibleStateAndCountry = $this->getStateOrProvince($temp);
							}
							if(!$possibleStateAndCountry) {
								if(strlen($token) > 3) $possibleStateAndCountry = $this->getStateOrProvince(ucfirst($token));
								else $possibleStateAndCountry = $this->getStateOrProvince($token);
							}
							if($possibleStateAndCountry) {
								$temp = $possibleStateAndCountry[0];
								if(strcasecmp($temp, $tState) == 0) {
									return array(
										'county' => $county,
										'stateProvince' => $temp,
										'country' => $possibleStateAndCountry[1]
									);
								}
							}
						}
						$tokenBeforePrevToken = $prevToken;
						$prevToken = $token;
						$tokenCount++;
					}
				}
			} else {
				$possibleStateAndCountry = $this->getStateOrProvince(ucfirst($line));
				if($possibleStateAndCountry) {
					$temp = $possibleStateAndCountry[0];
					if(strcasecmp($temp, $tState) == 0) {
						return array(
							'county' => $county,
							'stateProvince' => $temp,
							'country' => $possibleStateAndCountry[1]
						);
					}
				}
			}
		}
	}

	protected function processCArray($cArray, $s, $nextWord = null, $state_province="") {//echo "\nLine 3231, Input to processCArray: ".$s.", nextWord: ".$nextWord."\n";
		if($cArray) {//echo "\nLine 1532, cArray is not null\n";
			$num = 0;
			foreach($cArray as $vs) {//foreach($vs as $k => $v) echo "\nline 3676, ".$k.": ".$v."\n";
				$county = $vs['county'];
				$sp = $vs['stateProvince'];
				if(strcasecmp($sp, $state_province) == 0) {
					$possibleState = $sp;
					$possibleCountry = $vs['country'];
					$num++;
				} else if($nextWord != null && stripos($nextWord, $sp) !== FALSE) {
					$possibleState = $sp;
					$possibleCountry = $vs['country'];
					$num++;
				} else if(stripos($s, $sp) !== FALSE) {
					$possibleState = $sp;
					$possibleCountry = $vs['country'];
					$num++;
				}
			}//echo "\nLine 3692, num: ".$num."\n";
			if($num == 1) {
				$results = array();
				array_push($results, array('county' => $county, 'stateProvince' => $possibleState, 'country' => $possibleCountry));
				return $results;
			} else  return $cArray;
		}
	}

	private function getFourWordPhrasesFromEnd($string, $forward=true) {
		$words = array();
		$string = preg_replace("/\\s{2,}/", " ", $string);
		if($forward) $words = explode(" ", $string);
		else $words = array_reverse(explode(" ", $string));
		$totalWordCount = count($words);
		if($totalWordCount == 1) return array($string);
		if($totalWordCount == 2) {
			if($forward) return array($words[0]." ".$words[1], $words[0]);
			return array($words[1]." ".$words[0], $words[0]);
		}
		if($totalWordCount == 3) {
			if($forward) return array($words[0]." ".$words[1]." ".$words[2], $words[0]." ".$words[1], $words[0]);
			return array($words[2]." ".$words[1]." ".$words[0], $words[1]." ".$words[0], $words[0]);
		}
		$result = array();
		$prevWord = "";
		$wordBeforePrevWord = "";
		$wordBeforeWordBeforePrevWord = "";
		$index = 0;
		foreach($words as $word) {
			if($prevWord) {
				if($wordBeforePrevWord) {
					if($wordBeforeWordBeforePrevWord) {
						if($forward) array_unshift($result, $wordBeforeWordBeforePrevWord." ".$wordBeforePrevWord." ".$prevWord." ".$word, $wordBeforeWordBeforePrevWord." ".$wordBeforePrevWord." ".$prevWord, $wordBeforeWordBeforePrevWord." ".$wordBeforePrevWord, $wordBeforeWordBeforePrevWord);
						else array_unshift($result, $word." ".$prevWord." ".$wordBeforePrevWord." ".$wordBeforeWordBeforePrevWord, $prevWord." ".$wordBeforePrevWord." ".$wordBeforeWordBeforePrevWord, $wordBeforePrevWord." ".$wordBeforeWordBeforePrevWord, $wordBeforeWordBeforePrevWord);
						break;
					}
				}
			}
			if($index++ > 3) break;
			$wordBeforeWordBeforePrevWord = $wordBeforePrevWord;
			$wordBeforePrevWord = $prevWord;
			$prevWord = $word;
		}
		return $result;
	}

	private function processCountyString($cString, $nextWord = null, $state_province="", $sense=true) {//echo "\nInput to processCountyString, cString: ".$cString."\nnextWord: ".$nextWord."\nstate_province: ".$state_province."\n";
		$cString = trim($cString, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		if($cString) {
			if((preg_match("/\\bSTATE\\b/i", $cString) || preg_match("/\\bPROVINCE\\b/i", $cString)) && $nextWord) {
				$nextWord = trim($nextWord, " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
				$fourWordPhrases = $this->getFourWordPhrasesFromEnd($nextWord, true);
				foreach($fourWordPhrases as $fourWordPhrase) {//echo "\nline 3896, threeWordPhrase: ".$threeWordPhrase."\n";
					$cArray = $this->getCounty($fourWordPhrase, $state_province);
					if($cArray) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
				}
			} else {
				//if($sense) echo "\nline 3872, sense: true\n";
				//else echo "\nline 3873, sense: false\n";
				if(preg_match("/^(.{2,})[,.] (?:U\\.?S\\.?A\\.?|United States|Canada|Mexico)[,.]?(.*)/i", $cString, $mats)) {
					$sp = $this->getStateOrProvince(ucfirst(trim($mats[1])));
					if($sp) $state_province = $sp[0];
				}
				$fourWordPhrases = $this->getFourWordPhrasesFromEnd($cString, $sense);
				foreach($fourWordPhrases as $fourWordPhrase) {//echo "\nline 3880, fourWordPhrase: ".$fourWordPhrase."\n";
					$cArray = $this->getCounty($fourWordPhrase, $state_province);
					if($cArray) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
				}
			}
			if($nextWord) {
				if(!preg_match("/\\bSTATE\\b/i", $cString) && !preg_match("/\\bPROVINCE\\b/i", $cString)) {
					$nextWord = trim($nextWord, " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
					$fourWordPhrases = $this->getFourWordPhrasesFromEnd($nextWord, true);
					foreach($fourWordPhrases as $fourWordPhrase) {//echo "\nline 3896, threeWordPhrase: ".$threeWordPhrase."\n";
						$cArray = $this->getCounty($fourWordPhrase, $state_province);
						if($cArray) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
					}
				}
				$pos = strrpos($nextWord, ",");
				$temp = "";
				if($pos !== FALSE) $temp = substr($nextWord, $pos);
				else {
					$pos = strrpos($nextWord, ";");
					if($pos !== FALSE) $temp = substr($nextWord, $pos);
					else $temp = $nextWord;
				}
				$statePos = stripos($temp, "State");
				if($statePos !== FALSE) $temp = trim(substr($temp, 0, $statePos));
				$cArray = $this->getCounty($temp, $state_province);
				if($cArray != null) {
					return $this->processCArray($cArray, $cString, $nextWord, $state_province);
				}
			}
			$results = array();
			$possibleState = "";
			$possibleCountry = "";
			if($sense) {
				$commaPos = strpos($cString, ",");
				if($commaPos !== FALSE) {
					$afterComma = trim(substr($cString, $commaPos+1));
					$beforeComma = trim(substr($cString, 0, $commaPos));
					if(substr_count($beforeComma, " ") < 3) {
						$cArray = $this->getCounty($beforeComma, $state_province);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
						$colonPos = strrpos($beforeComma, ":");
						if($colonPos !== FALSE) {
							$afterColon = trim(substr($afterComma, $colonPos+1));
							$beforeColon = trim(substr($afterComma, 0, $colonPos));
							if(substr_count($beforeColon, " ") < 3) {
								$cArray = $this->getCounty($beforeColon, $state_province);
								if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
							}
							if(substr_count($afterColon, " ") < 3) {
								$cArray = $this->getCounty($afterColon, $state_province);
								if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
							}
						}
					}
					if(substr_count($afterComma, " ") < 3) {
						$cArray = $this->getCounty($afterComma, $state_province);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
						$colonPos = strpos($afterComma, ":");
						if($colonPos !== FALSE) {
							$afterColon = trim(substr($afterComma, $colonPos+1));
							$beforeColon = trim(substr($afterComma, 0, $colonPos));
							if(substr_count($beforeColon, " ") < 3) {
								$cArray = $this->getCounty($beforeColon, $state_province);
								if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
							}
							if(substr_count($afterColon, " ") < 3) {
								$cArray = $this->getCounty($afterColon, $state_province);
								if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
							}
						}
					}
				}
			} else {
				$commaPos = strrpos($cString, ",");
				if($commaPos !== FALSE) {
					$afterComma = trim(substr($cString, $commaPos+1));
					$beforeComma = trim(substr($cString, 0, $commaPos));
					if(substr_count($afterComma, " ") < 3) {
						$cArray = $this->getCounty($afterComma, $state_province);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
						$colonPos = strpos($afterComma, ":");
						if($colonPos !== FALSE) {
							$afterColon = trim(substr($afterComma, $colonPos+1));
							$beforeColon = trim(substr($afterComma, 0, $colonPos));
							if(substr_count($afterColon, " ") < 3) {
								$cArray = $this->getCounty($afterColon, $state_province);
								if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
							}
							if(substr_count($beforeColon, " ") < 3) {
								$cArray = $this->getCounty($beforeColon, $state_province);
								if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
							}
						}
					}
					if(substr_count($beforeComma, " ") < 3) {
						$cArray = $this->getCounty($beforeComma, $state_province);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
						$colonPos = strpos($beforeComma, ":");
						if($colonPos !== FALSE) {
							$afterColon = trim(substr($beforeComma, $colonPos+1));
							$beforeColon = trim(substr($beforeComma, 0, $colonPos));
							if(substr_count($afterColon, " ") < 3) {
								$cArray = $this->getCounty($afterColon, $state_province);
								if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
							}
							if(substr_count($beforeColon, " ") < 3) {
								$cArray = $this->getCounty($beforeColon, $state_province);
								if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
							}
						}
					}
				}
			}
			$colonPos = strpos($cString, ":");
			if($colonPos !== FALSE) {
				$afterColon = trim(substr($cString, $colonPos+1));
				$beforeColon = trim(substr($cString, 0, $colonPos));
				if($sense) {
					if(substr_count($beforeColon, " ") < 3) {
						$cArray = $this->getCounty($beforeColon, $state_province);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
					}
					if(substr_count($afterColon, " ") < 3) {
						$cArray = $this->getCounty($afterColon, $state_province);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
					}
				} else {
					if(substr_count($afterColon, " ") < 3) {
						$cArray = $this->getCounty($afterColon, $state_province);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
					}
					if(substr_count($beforeColon, " ") < 3) {
						$cArray = $this->getCounty($beforeColon, $state_province);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
					}
				}
			}
			if(strpos($cString, ".") !== FALSE) {
				$dotPos = strpos($cString, ".");
				$afterDot = trim(substr($cString, $dotPos+1));
				$beforeDot = trim(substr($cString, 0, $dotPos));
				if($sense) {
					if(substr_count($beforeDot, " ") < 3) {
						$cArray = $this->getCounty($beforeDot, $state_province);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
					}
					$spacePos = strrpos($beforeDot, " ");
					if($spacePos !== FALSE) {
						$afterSpace = trim(substr($beforeDot, $spacePos+1));
						if(preg_match("/^St\\.?$/i", $afterSpace, $mats)) {
							$cArray = $this->getCounty($afterSpace." ".$afterDot, $state_province);
							if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
						}
						$beforeSpace = trim(substr($beforeDot, 0, $spacePos));
						$cArray = $this->getCounty($beforeSpace, $state_province);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
					}
				} else {
					if(substr_count($afterDot, " ") < 3) {
						$cArray = $this->getCounty($afterDot, $state_province);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
					}
					$spacePos = strrpos($beforeDot, " ");
					if($spacePos !== FALSE) {
						$afterSpace = trim(substr($beforeDot, $spacePos+1));
						if(preg_match("/^St\\.?$/i", $afterSpace)) {
							$cArray = $this->getCounty($afterSpace." ".$afterDot, $state_province);
							if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
						}
						$cArray = $this->getCounty($afterSpace, $state_province);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord, $state_province);
					}
				}
			}
		}
	}

	private function getCountyMatches($c) {//echo "\nLine 3994, Input to getCountyMatches: ".$c."\n";
	//this function has the side-effect of removing double quotes
		if($c) {
			$tempC = str_replace("\"", "\\\"", $c);
			$countyPatStr = "/(.{3,})(?<! of )(?:C[o0q]un[tf][yv?i]|Par(?:\\.|[il!|]sh)|B[o0]r[o0]ugh)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;](.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array(str_replace("\\\"", "\"", $countyMatches[1]), str_replace("\\\"", "\"", $countyMatches[2]));

			$countyPatStr = "/(.{3,})(?<! of )(?:C[o0q]un[tf][yv?i]|Par(?:\\.|[il!|]sh)|B[o0]r[o0]ugh)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[,.] (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array(str_replace("\\\"", "\"", $countyMatches[1]), str_replace("\\\"", "\"", $countyMatches[2]));

			$countyPatStr = "/(.{3,})(?<! of )\\b(?:C[o0][.,]?)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;] (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array(str_replace("\\\"", "\"", $countyMatches[1]), str_replace("\\\"", "\"", $countyMatches[2]));

			$countyPatStr = "/(.*)\\n(?:C[o0][.,]?)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;,] (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array(str_replace("\\\"", "\"", $countyMatches[1]), str_replace("\\\"", "\"", $countyMatches[2]));

			$countyPatStr = "/(.{3,})(?<! of )(?:C[o0q]un[tf][yv?i]|Par(?:\\.|[il!|]sh)|B[o0]r[o0]ugh)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))\\b(.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array(str_replace("\\\"", "\"", $countyMatches[1]), str_replace("\\\"", "\"", $countyMatches[2]));

			$countyPatStr = "/(.*)\\n(?:C[o0q]un[tf][yv?i]|Par(?:\\.|[il!|]sh)|B[o0]r[o0]ugh)[:;,.] (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array(str_replace("\\\"", "\"", $countyMatches[1]), str_replace("\\\"", "\"", $countyMatches[2]));

			$countyPatStr = "/(.{3,})(?<! of )\\b(?:C[o0q][.,]|Go\\.)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;,]? (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array(str_replace("\\\"", "\"", $countyMatches[1]), str_replace("\\\"", "\"", $countyMatches[2]));

			$countyPatStr = "/(.{3,})(?<! of )\\b(?:C[o0q][.,])(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;,]?(.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array(str_replace("\\\"", "\"", $countyMatches[1]), str_replace("\\\"", "\"", $countyMatches[2]));

			$countyPatStr = "/(.*)\\n(?:C[o0q][.,]?)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;,]? (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array(str_replace("\\\"", "\"", $countyMatches[1]), str_replace("\\\"", "\"", $countyMatches[2]));

			$countyPatStr = "/(.*)(?<! of )(?:C[o0q]un[tf][yv?i]|Par(?:\\.|[il!|]sh)|B[o0]r[o0]ugh)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;] (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array(str_replace("\\\"", "\"", $countyMatches[1]), str_replace("\\\"", "\"", $countyMatches[2]));

			$countyPatStr = "/(.*)(?<! of )(?:C[o0q]un[tf][yv?i]|Par(?:\\.|[il!|]sh)|B[o0]r[o0]ugh)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line))) (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array(str_replace("\\\"", "\"", $countyMatches[1]), str_replace("\\\"", "\"", $countyMatches[2]));

			$countyPatStr = "/(.*)(?<! of )(?:C[o0q]un[tf][yv?i]|Par(?:\\.|[il!|]sh)|B[o0]r[o0]ugh|Dist(?:\\.|rict))(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))\\b(.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array(str_replace("\\\"", "\"", $countyMatches[1]), str_replace("\\\"", "\"", $countyMatches[2]));
		}
	}

	protected function findCounty($c, $state_province="") {//echo "\nLine 3971, Input to findCounty: ".$c."\nstate_province: ".$state_province."\n";
		$c = str_ireplace(" Dade Co", " Miami Dade Co", $c);
		$cm = $this->getCountyMatches($c);
		if($cm != null) {//$i=0;foreach($cm as $m) echo "\nLine 3974, cm[".$i++."] = ".$m."\n";
			$firstPart = ltrim(rtrim($cm[0], " \t\n\r\0\x0B,:;.\"\'\\~@#$%^&*_-"));
			$lastPart = rtrim(ltrim($cm[1], " \t\n\r\0\x0B,:;.\"\'\\~@#$%^&*_-"));
			$state = "";
			$county = "";
			$country = "";
			$counties = $this->getCounties($state_province);
			if($counties) {
				foreach($counties as $countie) {
					if(strcasecmp($countie, $firstPart) == 0) return array("", $countie, $this->getCountryFromState($state_province), $lastPart, $state_province);
					else if(strcasecmp($countie, $lastPart) == 0) return array($firstPart, $countie, $this->getCountryFromState($state_province), "", $state_province);
				}
			}
			$cs = $this->processCountyMatches($firstPart, $lastPart, $state_province);
			if($cs != null) {//foreach($cs as $k => $v) echo "\nline 3987, ".$k.": ".$v."\n";
				$county = $cs['county'];
				if(array_key_exists('stateProvince', $cs)) {
					$sp = $cs['stateProvince'];
					if($sp) $state = $sp;
				}
				if(array_key_exists('country', $cs)) $country = $cs['country'];
			}
			if(!$county && $firstPart) {
				if($counties) {
					foreach($counties as $countie) {
						$county = $this->findTokenAtEnd($firstPart, $countie);
						if($county) break;
					}
				}
				if(strlen($county) == 0) {//the regular expression matches the last occurrence of the word "county" etc.
					//if can't resolve the county try looking for an earlier match
					$cm = $this->getCountyMatches($firstPart);
					if($cm != null) {
						$firstPart = trim($cm[0]);
						$lastPart = trim($cm[1])." ".$lastPart;
						$cs = $this->processCountyMatches($firstPart, $lastPart, $state_province);
						if($cs != null) {
							$county = $cs['county'];
							if(array_key_exists('stateProvince', $cs)) $state = $cs['stateProvince'];
							if(array_key_exists('country', $cs)) $country = $cs['country'];
						}
						if(!$county && $firstPart) {
							if($state_province) {
								if($counties) {
									foreach($counties as $countie) {
										$county = $this->findTokenAtEnd($firstPart, $countie);
										if($county) break;
									}
								}
							}
						}
					}
				}
			}
			if($county) {
				$pos = strripos($firstPart, $county);
				if($pos !== FALSE) {
					$t = substr($firstPart, 0, $pos);
					if(strlen($t) < 3) {
						$temp = substr($firstPart, $pos+strlen($county));
						$pos = strripos($temp, $county);
						if($pos !== FALSE) $firstPart = substr($temp, 0, $pos);
					} else $firstPart = $t;
				} else {
					$tCounty = str_replace("Saint", "St.", $county);
					$pos = stripos($firstPart, $tCounty);
					if($pos !== FALSE) {
						$t = substr($firstPart, 0, $pos);
						if(strlen($t) < 3) {
							$temp = substr($firstPart, $pos+strlen($tCounty));
							$pos = strripos($temp, $tCounty);
							if($pos !== FALSE) $firstPart = substr($temp, 0, $pos);
						} else $firstPart = $t;
					}
				}
				$pos = strrpos($county, ",");
				if($pos !== FALSE) {
					$temp = trim(substr($county, $pos+1));
					if($temp) $county = $temp;
				}
				$pos = strpos($county, ":");
				if($pos !== FALSE) $county = trim(substr($county, $pos+1));
				$pos = strpos($county, ".");
				if($pos !== FALSE) {
					$temp = trim(substr($county, 0, $pos));
					if(strlen($temp) > 3) $county = trim(substr($county, $pos+1));
				}
				if($state) {
					$tState = substr($state, 0, strlen($state)-1);
					if(strpos($tState, ".") !== FALSE) {
						$state = ucwords
						(
							strtolower
							(
								str_replace
								(
									array('1', '!', '|', '5', '2', '0'),
									array('l', 'l', 'l', 'S', 'Z', 'O'),
									trim
									(
										$this->terminateField
										(
											$state,
											"/((?s).*?)\\b(?:[il1!|]at(?:[il1!|]tude|\\.)?|quad|[ec][lI!|][ec]v|[ (]\\d{2,}|\\d{2,}\\s|[lI!|]ocality|[lI!|]ocation|".
											"[lI!|]oc\\.|Date|Col(?:\\.|:|l[:.]|lectors?|lected|s[:.])|leg(?:it|\\.):?|Identif|Det(?:\\.|ermined by)|NEW \\w{4} BOTAN.+)/i"
										),
										" \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"
									)
								)
							)
						);
					} else {
						$state = ucwords
						(
							strtolower
							(
								str_replace
								(
									array('1', '!', '|', '5', '2', '0'),
									array('l', 'l', 'l', 'S', 'Z', 'O'),
									trim
									(
										$this->terminateField
										(
											$state,
											"/((?s).*?)\\b(?:[il1!|]at(?:[il1!|]tude|\\.)?|quad|[ec][lI!|][ec]v|[ (]\\d{2,}|\\d{2,}\\s|[lI!|]ocality|[lI!|]ocation|".
											"[lI!|]oc\\.|Date|Col(?:\\.|:|l[:.]|lectors?|lected|s[:.])|leg(?:it|\\.):?|Identif|Det(?:\\.|ermined by)|NEW \\w{4} BOTAN.+)/i"
										),
										" \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"
									)
								)
							)
						);
					}
				}
				if($state) {
					if(!$country && $this->isUSState($state)) $country = "U.S.A.";
				} else $state = $state_province;
				$county = ucwords
				(
					strtolower
					(
						str_replace
						(
							array('1', '!', '|', '5', '2', '0'),
							array('l', 'l', 'l', 'S', 'Z', 'O'),
							trim($county)
						)
					)
				);
				return array($firstPart, $county, $country, $lastPart, $state);
			}
		}
	}

	private function getNumericMonth($m) {//echo "\nInput to getNumericMonth: ".$m."\n";
		$index = 1;
		$monthArray = array
		(
			"/Jan(?:\\.|(?:uary))?/i",
			"/Feb(?:\\.|(?:ruary))?/i",
			"/Mar(?:\\.|(?:ch))?/i",
			"/Apr(?:\\.|(?:il))?/i",
			"/May/i",
			"/Jun[.e]?/i",
			"/Jul[.y]?/i",
			"/Aug(?:\\.|(?:ust))?/i",
			"/Sep(?:\\.|(?:t\\.?)|(?:tember))?/i",
			"/Oct(?:\\.|(?:ober))?/i",
			"/Nov(?:\\.|(?:ember))?/i",
			"/Dec(?:\\.|(?:ember))?/i"
		);
		foreach ($monthArray as $monthPat) {
			$mat = preg_match($monthPat, $m);
			if($mat !== FALSE && $mat != FALSE) return $index;
			$index++;
		}
		return 0;
	}

//gets dates in the form DayMonthYear.  If not found it will get dates like Month, Year
	private function getDMYDates($str, $possibleMonths) {
		$results = array();
		if($str) {
			$datePatternStr = "/(?:(?(?=(?:.*)\\b(?:\\d{1,2})[-\\s]?(?:(?i)".$possibleMonths.")[-\\s]?(?:\\d{4})))".

				"(.*)\\b(\\d{1,2})[-\\s]?((?i)".$possibleMonths.")[-\\s]?((?:1[789]|20)\\d{2})|".

				"(.*)\\b((?i)".$possibleMonths."),?\\s?((?:1[789]|20)\\d{2}))/s";

			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 4373, dateMatches[".$i++."] = ".$dateMatche."\n";
				$day = $dateMatches[2];
				if($day) {
					$results[] = array("day" => str_pad($day, 2, "0", STR_PAD_LEFT), "month" => str_pad($this->getNumericMonth(str_replace(".", "", $dateMatches[3])), 2, "0", STR_PAD_LEFT), "year" => $dateMatches[4]);
					//$results[] = array("day" => $day, "month" => str_replace(".", "", $dateMatches[3]), "year" => $dateMatches[4]);
					$results = array_merge_recursive($results, $this->getDMYDates($dateMatches[1], $possibleMonths));
				} else {
					$results[] = array("month" => str_pad($this->getNumericMonth(str_replace(".", "", $dateMatches[6])), 2, "0", STR_PAD_LEFT), "year" => $dateMatches[7]);
					//$results[] = array("month" => str_replace(".", "", $dateMatches[6]), "year" => $dateMatches[7]);
					$results = array_merge_recursive($results, $this->getDMYDates($dateMatches[5], $possibleMonths));
				}

			}
		}
		return $results;
	}

	private function getMDYDates($str, $possibleMonths) {
		$results = array();
		if($str) {
			$datePatternStr = "/(.*)\\b((?i)".$possibleMonths.")\\s?(\\d{1,2})(?:[,.] ?| )((?:1[789]|20)\\d{2})(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 4393, dateMatches[".$i++."] = ".$dateMatche."\n";
				array_push($results, array("day" => str_pad($dateMatches[3], 2, "0", STR_PAD_LEFT), "month" => str_pad($this->getNumericMonth(str_replace(".", "", $dateMatches[2])), 2, "0", STR_PAD_LEFT), "year" => $dateMatches[4]));
				//array_push($results, array("day" => $dateMatches[3], "month" => str_replace(".", "", $dateMatches[2]), "year" => $dateMatches[4]));
				$results = array_merge_recursive($results, $this->getMDYDates($dateMatches[1], $possibleMonths));
			}
		}
		return $results;
	}

	private function getNumericDates($str) {
		$results = array();
		$letterMonths = array
		(
			1=>'January',
			2=>'February',
			3=>'March',
			4=>'April',
			5=>'May',
			6=>'June',
			7=>'July',
			8=>'August',
			9=>'September',
			10=>'October',
			11=>'November',
			12=>'December'
		);
		if($str) {
			$possibleNumbers = "[OQSZl|I!&0-9]";
			$datePatternStr = "/(?(?=(?:.*)\\b(?:(?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2})\\s?[-\/]\\s?(?:[OQ0]?".
				$possibleNumbers."|[Iil!|1][12Iil!|zZ])\\s?[-\/]\\s?(?:[OQ0]?".$possibleNumbers."|[Iil!|zZ12]".
				$possibleNumbers."|3[1Iil!|OQ0\]]))".

				"(.*)\\b((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2})\\s?[-\/]\\s?([OQ0]?".
				$possibleNumbers."|[Iil!|1][12Iil!|zZ])\\s?[-\/]\\s?([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".
				$possibleNumbers."|3[1Iil!|OQ0\]])|".

				"(.*)\\b([OQ0]?".$possibleNumbers."|[Iil!|1][12Iil!|zZ])\\s?[-\/]\\s?([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".
				$possibleNumbers."|3[1Iil!|OQ0\]])\\s?[-\/]\\s?((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2}))/s";

			if(preg_match($datePatternStr, $str, $dateMatches)) {
				$year = $this->replaceMistakenNumbers($dateMatches[2]);
				if($year != null && strlen($year) > 1) {
					$month = ltrim($this->replaceMistakenNumbers($dateMatches[3]), "0");
					if(is_numeric($month) && $month > 0 && $month <= 12) {
						array_push(
							$results,
							array
							(
								"day" => str_pad($this->replaceMistakenNumbers($dateMatches[4]), 2, "0", STR_PAD_LEFT),
								"month" => $month,
								//"month" => $letterMonths[$month],
								"year" => $year
							)
						);
					}
					$results = array_merge_recursive($results, $this->getNumericDates($dateMatches[1]));
				} else {
					$year = $this->replaceMistakenNumbers($dateMatches[8]);
					$month = $this->replaceMistakenNumbers($dateMatches[6]);
					if(is_numeric($month) && $month > 0 && $month <= 12) {
						array_push(
							$results,
							array
							(
								"day" => str_pad($this->replaceMistakenNumbers($dateMatches[7]), 2, "0", STR_PAD_LEFT),
								"month" => str_pad($month, 2, "0", STR_PAD_LEFT),
								"year" => $year
							)
						);
					}
					$results = array_merge_recursive($results, $this->getNumericDates($dateMatches[5]));
				}
			}
		}
		return $results;
	}

	protected function getTRSCoordinates($str) {
		$results = array();
		if($str) {
			$possibleNumbers = "[OQSZl|I!&\\d]";
			$trsPatStr = "/(?(?=(?:.*)\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)".
				"R(?:\\.|ange)?\\s?(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".
				$possibleNumbers."{1,3})\\b)".
//if the condition is true then the form is TRS
				"(.*)\\bT(?:\\.|wnshp.?|ownship)?\\s?(".$possibleNumbers."{1,3})\\s?([NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(".
				$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(".$possibleNumbers."{1,3})\\b|".
//else the form is STR
				"(.*)\\b[S5](?:\\.|ect?\\.?|ection)?\\s?(".$possibleNumbers."{1,3}),?(?:\\s|\\n|\\r\\n)T(?:\\.|wnshp.?|ownship)?\\s?(".
				$possibleNumbers."{1,3})\\s?([NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(".$possibleNumbers."{1,3}\\s?[EW])\\.?\\b)/is";

			if(preg_match($trsPatStr, $str, $trsMatches)) {
				$township = trim($trsMatches[2]);
				if($township) {
					$trs = "TRS: T.".$this->replaceMistakenNumbers($township).$trsMatches[3]."., R.".
						$this->replaceMistakenNumbers(trim($trsMatches[4]))."., Sec. ".$this->replaceMistakenNumbers(trim($trsMatches[5]));
					array_push($results, $trs);
					$results = array_merge_recursive($results, $this->getTRSCoordinates($trsMatches[1]));
				} else {
					$township = trim($trsMatches[8]);
					$trs = "TRS: T.".$this->replaceMistakenNumbers($township).$trsMatches[9]."., R.".
						$this->replaceMistakenNumbers(trim($trsMatches[10]))."., Sec. ".$this->replaceMistakenNumbers(trim($trsMatches[7]));
					array_push($results, $trs);
					$results = array_merge_recursive($results, $this->getTRSCoordinates($trsMatches[6]));
				}
			}
		}
		return $results;
	}

	protected function getUTMCoordinates($str) {
		$results = array();
		if($str) {
			$possibleNumbers = "[OQSZl|I!&\\d]";
			$utmPatStr = "/(.*)\\b(UTM:?(?:\\s|\\n|\\r\\n)(?:Zone\\s)?(".$possibleNumbers."{1,2})(\\w?(?:\\s|\\n|\\r\\n))(".
				$possibleNumbers."{1,8}E?(?:\\s|\\n|\\r\\n)".$possibleNumbers."{1,8}N?))\\b/is";
			if(preg_match($utmPatStr, $str, $utmMatches)) {
				$utm = "UTM: ".$this->replaceMistakenNumbers(trim($utmMatches[3])).$utmMatches[4].$this->replaceMistakenNumbers(trim($utmMatches[5]));
				array_push($results, $utm);
				$results = array_merge_recursive($results, $this->getUTMCoordinates($utmMatches[1]));
			}
		}
		return $results;
	}

	private function getLatLongs($str) {//echo "\nInput to getLatLongs:\n".$str."\n";
		if($str) {
			$results = array();
			$latLongPatStr = "/(.*?)\\b((?:\\d{1,3}+(?:\\.\\d{1,7})?) ?�\\s?(?:(?:\\d{1,3}(?:\\.\\d{1,3})?)?\\s?\'".
				"(?:\\s|\\n|\\r\\n)?(?:\\d{1,3}(?:\\.\\d{1,3})? ?\")?)?+\\s??(?:N(?:[,.]|orth)?|S(?:[,.]|outh)?))[,.;]?(?:\\s|\\n|\\r\\n)?".
				"(?:L(?:ong|at)(?:\\.|itude)?[:,]?(?:\\s|\\n|\\r\\n)?)?((?:\\d{1,3}+(?:\\.\\d{1,7})?) ?�".
				"(?:\\s|\\n|\\r\\n)?(?:(?:\\d{1,3}(?:\\.\\d{1,3})?)? ?\'(?:\\s|\\n|\\r\\n)?(?:\\d{1,3}(?:\\.\\d{1,3})? ?\")?)?+".
				"\\s?(?:E(?:[,.]|ast)?|W(?:[,.]|est)?))/is";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {//$i=0;foreach($latLongMatches as $latLongMatch) echo "\nline 4699, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				array_push($results, array("latitude" => str_replace("\n", "", trim($latLongMatches[2], " ,.;\r\n")), "longitude" => str_replace("\n", "", trim($latLongMatches[3], " ,.;\r\n"))));
				$temp = $this->getLatLongs($latLongMatches[1]);
				if($temp) return array_merge_recursive($results, $this->getLatLongs($latLongMatches[1]));
				else return $results;
			}
			//getLongLats
			$latLongPatStr = "/(.*?)\\b((?:\\d{1,3}+(?:\\.\\d{1,7})?) ?�\\s?(?:(?:\\d{1,3}(?:\\.\\d{1,3})?)?\\s?\'".
				"(?:\\s|\\n|\\r\\n)?(?:\\d{1,3}(?:\\.\\d{1,3})? ?\")?)?+\\s??(?:E(?:[,.]|ast)?|W(?:[,.]|est)?))[,.;]?(?:\\s|\\n|\\r\\n)?".
				"(?:L(?:ong|at)(?:\\.|itude)?[:,]?(?:\\s|\\n|\\r\\n)?)?((?:\\d{1,3}+(?:\\.\\d{1,7})?) ?�".
				"(?:\\s|\\n|\\r\\n)?(?:(?:\\d{1,3}(?:\\.\\d{1,3})?)? ?\'(?:\\s|\\n|\\r\\n)?(?:\\d{1,3}(?:\\.\\d{1,3})? ?\")?)?+".
				"\\s?(?:N(?:[,.]|orth)?|S(?:[,.]|outh)?))/is";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {//$i=0;foreach($latLongMatches as $latLongMatch) echo "\nline 4711, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				array_push($results, array("longitude" => str_replace("\n", "", trim($latLongMatches[2], " ,.;\r\n")), "latitude" => str_replace("\n", "", trim($latLongMatches[3], " ,.;\r\n"))));
				$temp = $this->getLatLongs($latLongMatches[1]);
				if($temp) return array_merge_recursive($results, $this->getLatLongs($latLongMatches[1]));
				else return $results;
			}
			//get latLongs with direction preceding coordinates. Possible Non-Integer seconds
			$latLongPatStr = "/(.*?)\\b(N(?:[,.]|orth)?|S(?:[,.]|outh)?) ?".
				"(\\d{1,2} ?�\\s?\\d{1,2} ?'\\s?\\d{1,2}(?:\\.\\d{1,3})?\")[:;,.]?\\s{0,2}".
				"(E(?:ast)?|(?:W|VV)(?:est)?) ?(\\d{1,3}+ ?�\\s?\\d{1,2} ?'?\\s?\\d{1,2}(?:\\.\\d{1,3})?\")/is";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {//$i=0;foreach($latLongMatches as $latLongMatch) echo "\nline 4729, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				$longitude = str_replace(array("\n", " "), "", trim($latLongMatches[5], " ,.;\n\r"));
				array_push
				(
					$results,
					array
					(
						"latitude" => str_replace(array("\n", " "), "", trim($latLongMatches[3], " ,.;\r\n")).strtoupper(substr(trim($latLongMatches[2]), 0, 1)),
						"longitude" => $longitude.str_replace("VV", "W", strtoupper(trim($latLongMatches[4])))
					)
				);
				$temp = $this->getLatLongs($latLongMatches[1]);
				if($temp) return array_merge_recursive($results, $temp);
				else return $results;
			}
			//get latLongs with direction preceding coordinates. Integer values.
			$latLongPatStr = "/(.*?)\\b(N(?:[,.]|orth)?|S(?:[,.]|outh)?) ?".
				"(\\d{1,2}+ ?�\\s?(?:(?:\\d{1,2})\\s?'\\s?(?:\\d{1,2} ?\")?)?)[:;,.]?\\s{0,2}".
				"(E(?:ast)?|(?:W|VV)(?:est)?) ?(\\d{1,3}+ ?�\\s?(?:\\d{1,2} ?'\\s?(?:\\d{1,2} ?\")?)?+)/is";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {//$i=0;foreach($latLongMatches as $latLongMatch) echo "\nline 4719, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				array_push
				(
					$results,
					array
					(
						"latitude" => str_replace(array("\n", " "), "", trim($latLongMatches[3], " ,.;\r\n")).strtoupper(substr(trim($latLongMatches[2]), 0, 1)),
						"longitude" => str_replace(array("\n", " "), "", trim($latLongMatches[5], " ,.;\n\r")).str_replace("VV", "W", strtoupper(trim($latLongMatches[4])))
					)
				);
				$temp = $this->getLatLongs($latLongMatches[1]);
				if($temp) return array_merge_recursive($results, $temp);
				else return $results;
			}
			//get latLongs with direction preceding coordinates. No seconds. Non-Integer values
			$latLongPatStr = "/(.*?)\\b(N(?:[,.]|orth)?|S(?:[,.]|outh)?) ?".
				"(\\d{1,2}+(?:\\.\\d{1,6})? ?�\\s?(?:(?:\\d{1,2})(?:\\.\\d{1,3})?\\s?')?)[:;,.]?\\s{0,2}".
				"(E(?:ast)?|(?:W|VV)(?:est)?) ?(\\d{1,3}+(?:\\.\\d{1,6} ?�| ?�\\s?\\d{1,2}(?:\\.\\d{1,3})? ?'?))/is";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {//$i=0;foreach($latLongMatches as $latLongMatch) echo "\nline 4729, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				$longitude = str_replace(array("\n", " "), "", trim($latLongMatches[5], " ,.;\n\r"));
				if(!(preg_match("/^.*�$/", $longitude) || preg_match("/^.*'$/", $longitude))) $longitude .= "'";
				array_push
				(
					$results,
					array
					(
						"latitude" => str_replace(array("\n", " "), "", trim($latLongMatches[3], " ,.;\r\n")).strtoupper(substr(trim($latLongMatches[2]), 0, 1)),
						"longitude" => $longitude.str_replace("VV", "W", strtoupper(trim($latLongMatches[4])))
					)
				);
				$temp = $this->getLatLongs($latLongMatches[1]);
				if($temp) return array_merge_recursive($results, $temp);
				else return $results;
			}
		}
	}

	private function fixLatLongs($str) {//echo "\nInput to fixLatLongs:\n".$str."\n";
		if($str) {
			$troubleShoot = FALSE;
			$possibleNumbers = "[OQSZl|I!&0-9]";
			//find patterns with periods instead of degree signs and minutes signs and seconds signs and no non-integer values
			$latLongPatStr = "/^((?s).*)\\bLat[,.] (".$possibleNumbers."{1,2}+)\\.".
				"(".$possibleNumbers."{1,2})\\.(".$possibleNumbers."{1,2}) ?(N|S)\\b[,.]{1,2} ".
				"Long\\. (".$possibleNumbers."{1,3})\\.(".$possibleNumbers."{1,2}) ?".
				"(E|W|VV)\\b[;:,._]{0,2}((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 4750, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = str_replace("_", ".", $this->replaceMistakenNumbers($latitude))."�";
					$next = trim($latLongMatches[3]);
					if($next) {
						$latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[4]);
						if($next) $latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$latitude .= strtoupper(substr(trim($latLongMatches[5]), 0, 1));
					$longitude = str_replace("_", ".", $this->replaceMistakenNumbers(trim($latLongMatches[6])))."�";
					$next = trim($latLongMatches[7]);
					if($next) $longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
					$longitude .= str_replace("VV", "W", strtoupper(trim($latLongMatches[8])));
					$rest = "";
					if(count($latLongMatches) > 9) {
						$rest = $latLongMatches[9];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//find patterns with degree signs and minutes signs and seconds signs and no non-integer values
			$latLongPatStr = "/^((?s).*)\(?\\b(?:ca\\.? )?+(?:Lat(?:[._]|itude)?:?,?\\s{0,2})?+(".$possibleNumbers."{1,2}+)\\s?(?:�|c|\?|\*|deg(?:[\\._]|rees)?)\\s?".
				"(".$possibleNumbers."{1,2})\\s?(?:'|\*|\?|,|min(?:[._]|utes)?)\\s?(".$possibleNumbers."{1,2})\\s?(?:\"|\?|sec(?:[._]|onds)?) ?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}(?:L(?:ong|at)(?:[._]|(?:itude))?)?[:;]?,?\\s{0,2}".
				"(".$possibleNumbers."{1,3})\\s?(?:�|c|\?|\*|deg(?:[\\._]|rees)?)\\s?".
				"(".$possibleNumbers."{1,2})\\s?(?:'|\*|\?|,|min(?:[\\._]|utes)?)\\s?".
				"(".$possibleNumbers."{1,2})\\s?(?:\"|\?|sec(?:[._]|onds)?) ?".
				"(E(?:ast)?|(?:W|VV)(?:est)?)\\b[;:,._]{0,2}\\s?(?:Long(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 4986, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = str_replace("_", ".", $this->replaceMistakenNumbers($latitude))."�";
					$next = trim($latLongMatches[3]);
					if($next) {
						$latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[4]);
						if($next) $latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$latitude .= strtoupper(substr(trim($latLongMatches[5]), 0, 1));
					$longitude = str_replace("_", ".", $this->replaceMistakenNumbers(trim($latLongMatches[6])))."�";
					$next = trim($latLongMatches[7]);
					if($next) {
						$longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[8]);
						if($next) $longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[9]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 10) {
						$rest = $latLongMatches[10];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//if not found look for patterns with 1s instead of minutes signs. No seconds
			$latLongPatStr = "/^((?s).*?)\(?\\b(?:ca\\.? )?+(?:Lat(?:[._]|itude)?:?,?\\s{0,2})?+".
				"(\\d?+ ?\\d) ?(?:�|c|\*|\?|deg(?:[._]|rees)?) ?".
				"(\\d? ?\\d(?:[._]".$possibleNumbers."{1,3})?) ?(?:[1lI!|]|'|\*|\?|\\s|min(?:[._]|utes)?) ?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}(?:L(?:ong|at)(?:[._]|itude)?)?[:; ,.]{0,3}".
				"(1 ?\\d? ?\\d|\\d? ?\\d) ?(?:�|c|\?| |\*|deg(?:[._]|rees)?) ?".
				"(\\d? ?\\d(?:[._]".$possibleNumbers."{1,3})?) ?(?:[1lI!|]|'|\*|\?| |min(?:[._]|utes)?) {0,2}".
				"(E(?:ast)?\\b|(?:W|VV?+)(?:est)?)\\b[;:,._]{0,2}\\s?(?:Long(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 4856, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = str_replace(" ", "", trim($latLongMatches[2]));
				if($latitude) {
					$latitude = $latitude."�";
					$next = str_replace(" ", "", trim($latLongMatches[3]));
					if($next) $latitude .= $next."'";
					$latitude .= strtoupper(substr(trim($latLongMatches[4]), 0, 1));
					$longitude = str_replace(" ", "", trim($latLongMatches[5]))."�";
					$next = trim($latLongMatches[6]);
					if($next) $longitude .= str_replace(" ", "", $next)."'";
					$longitude .= str_replace(array("VV", "V"), "W", strtoupper(substr(trim($latLongMatches[7]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 8) {
						$rest = $latLongMatches[8];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//if not found look for patterns with 0s instead of degree signs. No seconds
			$latLongPatStr = "/^((?s).*?)\(?\\b(?:ca\\.? )?+(?:Lat(?:[._]|itude)?:?,?\\s{0,2})?+".
				"(\\d\\d) ??(?:�|c|0|\*|\?| |deg(?:[._]|rees)?) ?".
				"(\\d? ?\\d(?:[._]".$possibleNumbers."{1,3})?) ?(?:'|\*|\?|\\s|min(?:[._]|utes)?) ?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}(?:L(?:ong|at)(?:[._]|itude)?)?[:; ,.]{0,3}".
				"(1\\d\\d|\\d\\d) ??(?:�|c|\?| |\*|0| |deg(?:[._]|rees)?) ?".
				"(\\d? ?\\d(?:[._]".$possibleNumbers."{1,3})?) ?(?:'|\*|\?| |min(?:[._]|utes)?) {0,2}".
				"(E(?:ast)?\\b|(?:W|VV?+)(?:est)?)\\b[;:,._]{0,2}\\s?(?:Long(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5006, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = str_replace(" ", "", trim($latLongMatches[2]));
				if($latitude) {
					$latitude = $latitude."�";
					$next = str_replace(" ", "", trim($latLongMatches[3]));
					if($next) $latitude .= $next."'";
					$latitude .= strtoupper(substr(trim($latLongMatches[4]), 0, 1));
					$longitude = str_replace(" ", "", trim($latLongMatches[5]))."�";
					$next = trim($latLongMatches[6]);
					if($next) $longitude .= str_replace(" ", "", $next)."'";
					$longitude .= str_replace(array("VV", "V"), "W", strtoupper(substr(trim($latLongMatches[7]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 8) {
						$rest = $latLongMatches[8];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//if not found find patterns with missing degree signs or minutes signs or seconds signs and no non-integer values
			$latLongPatStr = "/^((?s).*)\(?\\b(".$possibleNumbers."{1,2}+) ?(?:�|c|\?|\*| |deg(?:[\\._]|rees)?)\\s?".
				"(".$possibleNumbers."{1,2})(?:'|\*|\?| |min(?:[\\._]|utes)?)\\s?".
				"(".$possibleNumbers."{1,2})(?:\"|\?| |sec(?:[._]|onds)?) ?(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:L(?:ong|at)(?:[._]|(?:itude))?)?[:;]?,?\\s{0,2}".
				"(".$possibleNumbers."{1,3})(?:�|c|\?|\*| |deg(?:[\\._]|rees)?)\\s?".
				"(?:(".$possibleNumbers."{1,2})(?:'|\*|\?| |,|min(?:[\\._]|utes)?)\\s?".
				"(".$possibleNumbers."{1,2}) ?(?:\"|\?| |sec(?:[._]|onds)?)) ?(E(?:ast)?|(?:W|VV)(?:est)?)\\b[;:,._]{0,2}\\s?(?:Long(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 4886, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = str_replace("_", ".", $this->replaceMistakenNumbers($latitude))."�";
					$next = trim($latLongMatches[3]);
					if($next) {
						$latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[4]);
						if($next) $latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$latitude .= strtoupper(substr(trim($latLongMatches[5]), 0, 1));
					$longitude = str_replace("_", ".", $this->replaceMistakenNumbers(trim($latLongMatches[6])))."�";
					$next = trim($latLongMatches[7]);
					if($next) {
						$longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[8]);
						if($next) $longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[9]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 10) {
						$rest = $latLongMatches[10];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//if not found find patterns with missing degree signs or minutes signs or seconds signs and no non-integer values and possibly no seconds
			$latLongPatStr = "/^((?s).*)\(?(?<![�'])(?<![�'] )(?<!\\d[ .])(?<!\\d\\. )\\b(".$possibleNumbers."{2}+) ?".
				"(?:(?:�|c|\?|\*| |deg(?:[\\._]|rees)?)?([0-6O1I|!ZS&][02-9OQ])\\s?".
				"(?:(?:'|\*|\?| |min(?:[\\._]|utes)?)?([0-6O1I|!ZS&][02-9OQ])(?:\"|\?| |sec(?:[._]|onds)?)?|(?:'|\*|\?| |min(?:[\\._]|utes)?))|".
				"(?:�|c|\?|\*| |deg(?:[\\._]|rees)?)) ?(N(?:orth)?|S(?:outh)?)\\b[,.;:]?\\s{0,2}".
				"(?:L(?:ong|at)(?:[._]|(?:itude))?[:;]?,?\\s{0,2})?".
				"([1I|!]".$possibleNumbers."{2}|".$possibleNumbers."{1,2}+) ?(?:�|c|\?|\*| |deg(?:[\\._]|rees)?)?\\s?".
				"(?:([0-6O1I|!ZS&]?[0-9OQ])(?:'|\*|\?| |min(?:[\\._]|utes)?)?\\s?".
				"(?:(?:([0-6O1I|!ZS&]?[02-9OQ]))(?:\"|\?| |sec(?:[._]|onds)?)?)?)? ?".
				"(E(?:ast)?|(?:W|VV)(?:est)?)\\b[;:,._]{0,2}\\s?(?:Long(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";

			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5143, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = str_replace("_", ".", $this->replaceMistakenNumbers($latitude))."�";
					$next = trim($latLongMatches[3]);
					if($next) {
						$latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[4]);
						if($next) $latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$latitude .= strtoupper(substr(trim($latLongMatches[5]), 0, 1));
					$longitude = str_replace("_", ".", $this->replaceMistakenNumbers(trim($latLongMatches[6])))."�";
					$next = trim($latLongMatches[7]);
					if($next) {
						$longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[8]);
						if($next) $longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[9]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 10) {
						$rest = $latLongMatches[10];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}

			//if not found look for patterns with possible single quotes as sign for seconds with possible space only in degrees
			$latLongPatStr = "/^((?s).*)\(?\\b(".$possibleNumbers."{1,2}+)\\s?(?:�|c|\?|\*| |deg(?:[\\._]|rees)?)".
				"\\s?'?(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\*|\?|min(?:[\\._]|utes)?)".
				"\\s?(?:(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|sec(?:[\\._]|onds)?))?\\s?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)(?:L(?:ong|at)(?:[._]|itude)?)?[:;]?,?\\s{0,2}".
				"(".$possibleNumbers."{1,3})\\s?(?:�|c|\*|\?| |deg(?:[._]|rees)?)".
				"\\s?'?(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\*|\?|min(?:[._]|utes)?)".
				"\\s?(?:(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|sec(?:[._]|onds)?))?".
				"\\s?(E(?:ast)?|(?:W|VV)(?:est)?)\\b[;:,._]{0,2}\\s?(?:Long(?:[._]|itude)?[:;]?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 4955, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = str_replace("_", ".", $this->replaceMistakenNumbers($latitude))."�";
					$next = trim($latLongMatches[3]);
					if($next) {
						$latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[4]);
						if($next) $latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$latitude .= strtoupper(substr(trim($latLongMatches[5]), 0, 1));
					$longitude = str_replace("_", ".", $this->replaceMistakenNumbers(trim($latLongMatches[6])))."�";
					$next = trim($latLongMatches[7]);
					if($next) {
						$longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[8]);
						if($next) $longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[9]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 10) {
						$rest = $latLongMatches[10];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}

			//if not found look for patterns with possible single quotes as sign for degrees with possible space only in degrees
			$latLongPatStr = "/^((?s).*)\(?\\b(".$possibleNumbers."{1,2}+)\\s?(?:�|c|\?|\*| |'|deg(?:[\\._]|rees)?)".
				"\\s?'?(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\*|\?|min(?:[\\._]|utes)?)".
				"\\s?(?:(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|\?|sec(?:[\\._]|onds)?))?\\s?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)(?:L(?:ong|at)(?:[._]|itude)?)?[:;]?,?\\s{0,2}".
				"(".$possibleNumbers."{1,3})\\s?(?:�|c|\*|\?| |'|deg(?:[._]|rees)?)".
				"\\s?'?(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\*|\?|min(?:[._]|utes)?)".
				"\\s?(?:(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|\?|sec(?:[._]|onds)?))?".
				"\\s?(E(?:ast)?|(?:W|VV)(?:est)?)\\b[;:,._]{0,2}\\s?(?:Long(?:[._]|itude)?[:;]?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 4998, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = str_replace("_", ".", $this->replaceMistakenNumbers($latitude))."�";
					$next = trim($latLongMatches[3]);
					if($next) {
						$latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[4]);
						if($next) $latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$latitude .= strtoupper(substr(trim($latLongMatches[5]), 0, 1));
					$longitude = str_replace("_", ".", $this->replaceMistakenNumbers(trim($latLongMatches[6])))."�";
					$next = trim($latLongMatches[7]);
					if($next) {
						$longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[8]);
						if($next) $longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[9]), 0, 1)));
					//echo "\nstrlen(latitude) > 0\n\n";
					$rest = "";
					if(count($latLongMatches) > 10) {
						$rest = $latLongMatches[10];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//if not found look for patterns with 1s instead of minutes signs.
			$latLongPatStr = "/^((?s).*?)\(?\\b(?:(?:ca\\.? )?+(?:Lat(?:[._]|itude)?:?,?\\s{0,2})?+".
				"(\\d{1,2}) ?(?:�|c|\*|\?|deg(?:[._]|rees)?| )\\s?(\\d{2}) ?(?:[1lI!|]|'|\*|\?| |min(?:[._]|utes)?) ?".
				"(?:(\\d{1,2})\\s?(?:\"|\?|sec(?:[._]|onds)?))?\\s?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}(?:L(?:ong|at)(?:[._]|itude)?)?[:; ,.]{0,3}".
				"((?:1\\d{2}|\\d{1,2}))\\s?(?:�|c|\?|\*|deg(?:[._]|rees)?| )\\s?(\\d ?\\d) ?(?:[1lI!|]|'|\*|\?| |min(?:[._]|utes)?) {0,2}".
				"(?:(\\d{1,2}) ?(?:\"|\?|sec(?:[._]|onds)?))?".
				"\\s?(E(?:ast)?\\b|(?:W|VV?+)(?:est)?)\\b)[;:,._]{0,2}\\s?(?:Long(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5038, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = $latitude."�";
					$next = trim($latLongMatches[3]);
					if($next) {
						$latitude .= $next."'";
						$next = trim($latLongMatches[4]);
						if($next) $latitude .= $next."\"";
					}
					$latitude .= strtoupper(substr(trim($latLongMatches[5]), 0, 1));
					$longitude = trim($latLongMatches[6])."�";
					$next = trim($latLongMatches[7]);
					if($next) {
						$longitude .= str_replace(" ", "", $next)."'";
						$next = trim($latLongMatches[8]);
						if($next) $longitude .= $next."\"";
					}
					$longitude .= str_replace(array("VV", "V"), "W", strtoupper(substr(trim($latLongMatches[9]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 10) {
						$rest = $latLongMatches[10];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//if not found look for patterns with possible double quotes or spaces as sign for degrees or minutes
			$latLongPatStr = "/^((?s).*)\(?\\b(".$possibleNumbers."{1,2}+) ?(?:\"|�|c|\*|\?| |deg(?:[._]|rees)?)".
				"\\s?'?(".$possibleNumbers."{1,2}+(?:[._]".$possibleNumbers."{1,3})?) ?(?:'|\*|\"|\?|min(?:[._]|utes)?)?".
				"(?:\\s?(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|sec(?:[._]|onds)?)?)?\\s?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]? {0,2}".
				"(?: |\\n|\\r\\n)?(?:L(?:ong|at)(?:[._]|itude)?[:;]?,?\\s{0,2})?".
				"(".$possibleNumbers."{1,3})\\s?(?:\"|�|c|\?|\*| |deg(?:[._]|rees)?)".
				"\\s?'?(".$possibleNumbers."{1,2}+(?:[._]".$possibleNumbers."{1,3}+)?)\\s?(?:'|\"|\?|\*|min(?:[._]|utes)?)?".
				"(?:\\s?(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|\?|sec(?:[._]|onds)?)?)?".
				"\\s?(E(?:ast)?|(?:W|VV)(?:est)?)\\b[;:,._]{0,2}\\s?(?:Long(?:[._]|itude)?:?,? {0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5036, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = str_replace("_", ".", $this->replaceMistakenNumbers($latitude))."�";
					$next = trim($latLongMatches[3]);
					if($next) {
						$latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[4]);
						if($next) $latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$latitude .= strtoupper(substr(trim($latLongMatches[5]), 0, 1));
					$longitude = str_replace("_", ".", $this->replaceMistakenNumbers(trim($latLongMatches[6])))."�";
					$next = trim($latLongMatches[7]);
					if($next) {
						$longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[8]);
						if($next) $longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[9]), 0, 1)));
					//echo "\nstrlen(latitude) > 0\n\n";
					$rest = "";
					if(count($latLongMatches) > 10) {
						$rest = $latLongMatches[10];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}

			//if not found look for patterns with missing direction(s)
			$latLongPatStr = "/^((?s).*?)\(?\\b(?:(?:ca\\.? )?+(?:Lat(?:[._]|itude)?:?,?\\s{0,2})?+".
				"(".$possibleNumbers."{1,2}+(?:[._]".$possibleNumbers."{1,7})?) ?(?:�|c|\*|\?|deg(?:[._]|rees)?)".
				"\\s?(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?) ?(?:'|\*|\?|min(?:[._]|utes)?)".
				"\\s?(?:(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|sec(?:[._]|onds)?))?\\s?".
				"(N(?:orth)?\\b|S(?:outh)?\\b)?[;:,._ -]{1,2}\\s{0,2}(?:L(?:ong|at)(?:[._]|itude)?[:;]?,?\\s{0,2})?".
				"(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:�|c|\?|\*|deg(?:[._]|rees)?)".
				"\\s?(?:(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?) ?(?:'|\?|\*|min(?:[._]|utes)?))".
				"\\s?(?:(".$possibleNumbers."{1,2}(?:[._]".$possibleNumbers."{1,3})?) ?(?:\"|\?|sec(?:[._]|onds)?))?".
				"\\s?(E(?:ast)?\\b|(?:W|VV)(?:est)?\\b)?)[;:,._ -]{1,2}\\s?(?:Long(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5079, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				$latDir = trim($latLongMatches[5]);
				if(strlen($latDir) == 0) $latDir = "N";
				if($latitude) {
					$latitude = str_replace("_", ".", $this->replaceMistakenNumbers($latitude))."�";
					$next = trim($latLongMatches[3]);
					if($next) {
						$latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[4]);
						if($next) $latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$latitude .= strtoupper(substr($latDir, 0, 1));
					$longitude = str_replace("_", ".", $this->replaceMistakenNumbers(trim($latLongMatches[6])))."�";
					$longDir = trim($latLongMatches[9]);
					if(strlen($longDir) == 0) $longDir = "W";
					$next = trim($latLongMatches[7]);
					if($next) {
						$longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[8]);
						if($next) $longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$longitude .= str_replace("VV", "W", strtoupper(substr($longDir, 0, 1)));
					//echo "\nstrlen(latitude) > 0\n\n";
					$rest = "";
					if(count($latLongMatches) > 10) {
						$rest = $latLongMatches[10];
						if($rest) $rest = "\n".trim($rest);
					}
					//return $this->fixLatLongs(str_replace("\n", "", $latLongMatches[1]).$latitude).", ".str_replace("\n", "", $longitude).$rest;
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}

			//if not found look for patterns with As instead of 4s signs at beginning of minutes or seconds or Hs for 11s for minutes or seconds
			$latLongPatStr = "/^((?s).*?)\(?\\b(?:(?:ca\\.? )?+(?:Lat(?:[._]|itude)?:?,?\\s{0,2})?+".
				"(\\d{1,2}) ?(?:�|c|\*|\?|deg(?:[._]|rees)?| )\\s?((?-i)\\d{1,2}+|A\\d|H)(?i)(?:'|\*|\?|\\s|min(?:[._]|utes)?) ?".
				"(?:(?-i)(\\d{1,2}+|A\\d|H)(?i)\\s?(?:\"|\?|sec(?:[._]|onds)?))?\\s?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}(?:L(?:ong|at)(?:[._]|itude)?[:;]?,?\\s{0,2})?".
				"((?:1\\d{2}|\\d{1,2}))\\s?(?:�|c|\?|\*|deg(?:[._]|rees)?| )\\s?((?-i)\\d{1,2}+|A\\d|H)(?:'|\*|\?|\\s|min(?:[._]|utes)?) {0,2}".
				"(?:((?-i)\\d{1,2}+|A\\d|H)(?i) ?(?:\"|\?|sec(?:[._]|onds)?))?".
				"\\s?(E(?:ast)?|(?:W|VV)(?:est)?)\\b)[;:,._]{0,2}\\s?(?:Long(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5426, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = $latitude."�";
					$next = trim($latLongMatches[3]);
					if($next) {
						$latitude .= str_replace(array("A", "H"), array("4", "11"), $next)."'";
						$next = trim($latLongMatches[4]);
						if($next) $latitude .= str_replace(array("A", "H"), array("4", "11"), $next)."\"";
					}
					$latitude .= strtoupper(substr(trim($latLongMatches[5]), 0, 1));
					$longitude = trim($latLongMatches[6])."�";
					$next = trim($latLongMatches[7]);
					if($next) {
						$longitude .= str_replace(array("A", "H"), array("4", "11"), $next)."'";
						$next = trim($latLongMatches[8]);
						if($next) $longitude .= str_replace(array("A", "H"), array("4", "11"), $next)."\"";
					}
					$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[9]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 10) {
						$rest = $latLongMatches[10];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//if not found look for patterns with gaps in numbers
			$latLongPatStr = "/^((?s).*?)\(?\\b(?:(?:ca\\.? )?+(?:Lat(?:[._]|itude)?:?,?\\s{0,2})?+".
				"(\\d ?\\d?) ?(?:�|c|\*|deg(?:[._]|rees)?)\\s?([0-6]? ?\\d) ?(?:'|\*|min(?:[._]|utes)?) ?".
				"(?:([0-6]? ?\\d)\\s?(?:\"|sec(?:[._]|onds)?))?\\s?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}(?:L(?:ong|at)(?:[._]|itude)?[:;]?,?\\s{0,2})?".
				"(1 ?[0-7] ?\\d|0 ?\\d ?\\d|\\d ?\\d?) ?(?:�|c|\*|deg(?:[._]|rees)?)\\s?([0-6]? ?\\d) ?(?:'|\*|\\s|min(?:[._]|utes)?) {0,2}".
				"(?:([0-6]? ?\\d) ?(?:\"|\?|sec(?:[._]|onds)?))?".
				"\\s?(E(?:ast)?|(?:W|VV)(?:est)?)\\b)[;:,._]{0,2}\\s?(?:Long(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5159, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = str_replace(" ", "", $latitude)."�";
					$next = trim($latLongMatches[3]);
					if($next) {
						$latitude .= str_replace(" ", "", $next)."'";
						$next = trim($latLongMatches[4]);
						if($next) $latitude .= str_replace(" ", "", $next)."\"";
					}
					$latitude .= strtoupper(substr(trim($latLongMatches[5]), 0, 1));
					$longitude = str_replace(" ", "", trim($latLongMatches[6]))."�";
					$next = trim($latLongMatches[7]);
					if($next) {
						$longitude .= str_replace(" ", "", $next)."'";
						$next = trim($latLongMatches[8]);
						if($next) $longitude .= str_replace(" ", "", $next)."\"";
					}
					$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[9]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 10) {
						$rest = $latLongMatches[10];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//fix LongLats. No seconds
			$latLongPatStr = "/^((?s).*?)\(?\\b(?:ca\\.? )?+(?:Long(?:[._]|itude)?:?,?\\s{0,2})?+".
				"(1\\d{2}|\\d{1,2}+)(?:�|c| |\*|\?|deg(?:[._]|rees)?) ?".
				"(?:(\\d? ?\\d(?:[._]".$possibleNumbers."{1,3})?) ?(?:[1lI!|]|'|\*|\?| |,|min(?:[._]|utes)?)? ?)?".
				"(E(?:ast)?\\b|(?:W|VV?+)(?:est)?)\\b[,.]?\\s{0,2}(?:L(?:ong|at)(?:[._]|itude)?)?[:; ,.]{0,3}".
				"(\\d{1,2}) ?(?:�|c|\?| |\*|deg(?:[._]|rees)?) ?".
				"(?:(\\d? ?\\d(?:[._]".$possibleNumbers."{1,3})?)(?:[1lI!|]|'|\*|\?| |,|min(?:[._]|utes)?)? {0,2})?".
				"(N(?:orth)?|S(?:outh)?)\\s?\\b[;:,._]{0,2}\\s?(?:Lat(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5391, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = $latitude."�";
					$next = trim($latLongMatches[3]);
					if($next) $latitude .= $this->replaceMistakenNumbers($next)."'";
					$latitude .= strtoupper(substr(trim($latLongMatches[4]), 0, 1));
					$longitude = trim($latLongMatches[5])."�";
					$next = trim($latLongMatches[6]);
					if($next) $longitude .= $this->replaceMistakenNumbers(str_replace(" ", "", $next))."'";
					$longitude .= str_replace(array("VV", "V"), "W", strtoupper(substr(trim($latLongMatches[7]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 8) {
						$rest = $latLongMatches[8];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//fix LongLats. zeros instead of degrees
			$latLongPatStr = "/^((?s).*?)\(?\\b(?:ca\\.? )?+(?:Long(?:[._]|itude)?:?,?\\s{0,2})?+".
				"(1\\d{2}|\\d{1,2}+)(?:�|c|0|\*|\?) ?".
				"(?:(\\d\\d(?:[._]".$possibleNumbers."{1,3})?)(?:[1lI!|]|'|\*|\?|min(?:[._]|utes)?)? ?)?".
				"(E(?:ast)?\\b|(?:W|VV?+)(?:est)?)\\b[,.]?\\s{0,2}(?:L(?:ong|at)(?:[._]|itude)?)?[:; ,.]{0,3}".
				"(\\d{1,2})(?:�|c|\?|0|\*) ?".
				"(?:(\\d\\d(?:[._]".$possibleNumbers."{1,3})?)(?:[1lI!|]|'|\*|\?|min(?:[._]|utes)?)? {0,2})?".
				"(N(?:orth)?|S(?:outh)?)\\s?\\b[;:,._]{0,2}\\s?(?:Lat(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5540, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = $latitude."�";
					$next = trim($latLongMatches[3]);
					if($next) $latitude .= $this->replaceMistakenNumbers($next)."'";
					$latitude .= strtoupper(substr(trim($latLongMatches[4]), 0, 1));
					$longitude = trim($latLongMatches[5])."�";
					$next = trim($latLongMatches[6]);
					if($next) $longitude .= $this->replaceMistakenNumbers(str_replace(" ", "", $next))."'";
					$longitude .= str_replace(array("VV", "V"), "W", strtoupper(substr(trim($latLongMatches[7]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 8) {
						$rest = $latLongMatches[8];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//fix digital latlongs without degree signs
			$latLongPatStr = "/^((?s).*?)\(?\\b(?:(?:ca\\.? )?+(?:Lat(?:[._]|itude)?:?,?\\s{0,2})?+".
				"(\\d ?\\d?[._]".$possibleNumbers."{1,7}) ?(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}(?:L(?:ong|at)(?:[._]|itude)?[:;]?,?\\s{0,2})?".
				"((?:1 ?[0-7] ?\\d|0 ?\\d ?\\d|\\d ?\\d?)[._]".$possibleNumbers."{1,7}) ?(E(?:ast)?|(?:W|VV)(?:est)?)\\b)[;:,._]{0,2}\\s?".
				"(?:Long(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5569, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$latitude = trim($latLongMatches[2]);
				if($latitude) {
					$latitude = $this->replaceMistakenNumbers(str_replace(" ", "", $latitude))."�";
					$latitude .= strtoupper(substr(trim($latLongMatches[3]), 0, 1));
					$longitude = $this->replaceMistakenNumbers(str_replace(" ", "", trim($latLongMatches[4])))."�";
					$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[5]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 6) {
						$rest = $latLongMatches[6];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//fix digital longlats without degree signs
			$latLongPatStr = "/^((?s).*?)\(?\\b(?:(?:ca\\.? )?+(?:Long(?:[._]|itude)?:?,?\\s{0,2})?+".
				"((?:1 ?[0-7] ?\\d|0 ?\\d ?\\d|\\d ?\\d?)[._]".$possibleNumbers."{1,7}) ?(E(?:ast)?|(?:W|VV)(?:est)?)\\b[,.]?\\s{0,2}(?:L(?:ong|at)(?:[._]|itude)?[:;]?,?\\s{0,2})?".
				"(\\d ?\\d?[._]".$possibleNumbers."{1,7}) ?(N(?:orth)?|S(?:outh)?)\\b)[;:,._]{0,2}\\s?".
				"(?:Long(?:[._]|itude)?:?,?\\s{0,2})?\)?((?s).*)/i";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5590, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$longitude = trim($latLongMatches[2]);
				if($longitude) {
					$longitude = $this->replaceMistakenNumbers(str_replace(" ", "", $longitude))."�";
					$longitude .= strtoupper(substr(trim($latLongMatches[3]), 0, 1));
					$latitude = $this->replaceMistakenNumbers(str_replace(" ", "", trim($latLongMatches[4])))."�";
					$latitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[5]), 0, 1)));
					$rest = "";
					if(count($latLongMatches) > 6) {
						$rest = $latLongMatches[6];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1]).str_replace("\n", "", $latitude).", ".str_replace("\n", "", $longitude).$rest;
				}
			}
			//fix latLongs with direction preceding coordinates. Possible Non-Integer seconds.
			$latLongPatStr = "/(.*?)\\b(N(?:[,.]|orth)?|S(?:[,.]|outh)?) ?".
				"((?:[0O][SZl|I!&1-9]|[SZl|I!&1-8]".$possibleNumbers."|[SZl|I!&1-9]) ?�\\s?".
				"(?:".$possibleNumbers."{1,2} ?')\\s?(?:".$possibleNumbers."{1,2}(?:\\.".$possibleNumbers."{1,3})? ?\"))[:;,.]?\\s{0,2}".
				"(E(?:ast)?|(?:W|VV)(?:est)?) ?((?:[I1l|!]".$possibleNumbers."{2}|".$possibleNumbers."{1,2}) ?�".
				"\\s?".$possibleNumbers."{1,2} ?'?\\s?".$possibleNumbers."{1,2}(?:\\.".$possibleNumbers."{1,3})? ?\")((?s).*)/is";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5590, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$longitude = trim($latLongMatches[3]);
				if($longitude) {
					$longitude = $this->replaceMistakenNumbers(str_replace(" ", "", $longitude));
					$longitude = strtoupper(substr(trim($latLongMatches[2]), 0, 1)).$longitude;
					$latitude = $this->replaceMistakenNumbers(str_replace(" ", "", trim($latLongMatches[5])));
					$latitude = str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[4]), 0, 1))).$latitude;
					$rest = "";
					if(count($latLongMatches) > 6) {
						$rest = $latLongMatches[6];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1])." ".str_replace("\n", "", $longitude)."; ".str_replace("\n", "", $latitude).$rest;
				}
			}
			//fix latLongs with direction preceding coordinates. No seconds. Non-Integer values
			$latLongPatStr = "/(.*?)\\b(N(?:[,.]|orth)?|S(?:[,.]|outh)?) ?".
				"((?:[0O][SZl|I!&1-9]|[SZl|I!&1-8]".$possibleNumbers."|[SZl|I!&1-9])(?:\\.\\d{1,6})? ?�\\s?".
				"(?:(?:".$possibleNumbers."{1,2})(?:\\.".$possibleNumbers."{1,3})?\\s?')?)[:;,.]?\\s{0,2}(E(?:ast)?|(?:W|VV)(?:est)?) ?".
				"(".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6} ?�| ?�\\s?\\d{1,2}(?:\\.\\d{1,3})? ?'?))/is";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				if($troubleShoot) {
					$i=0;
					foreach($latLongMatches as $latLongMatch) echo "\nline 5590, latLongMatches[".$i++."] = ".$latLongMatch."\n";
				}
				$longitude = trim($latLongMatches[3]);
				if($longitude) {
					$longitude = $this->replaceMistakenNumbers(str_replace(" ", "", $longitude));
					$longitude = strtoupper(substr(trim($latLongMatches[2]), 0, 1)).$longitude;
					$latitude = $this->replaceMistakenNumbers(str_replace(" ", "", trim($latLongMatches[5])));
					$latitude = str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[4]), 0, 1))).$latitude;
					$rest = "";
					if(count($latLongMatches) > 6) {
						$rest = $latLongMatches[6];
						if($rest) $rest = "\n".trim($rest);
					}
					return $this->fixLatLongs($latLongMatches[1])." ".str_replace("\n", "", $longitude)."; ".str_replace("\n", "", $latitude).$rest;
				}
			}
		}
		return $str;
	}

	private function convertBadChars($str) {//echo "\nInput to convertBadChars: ".$str."\n";
		if($str) {
			if(@mb_ereg(chr(195).chr(162).chr(226).chr(130).chr(172).chr(194).chr(157), $str))
				$str = mb_ereg_replace(chr(195).chr(162).chr(226).chr(130).chr(172).chr(194).chr(157), "\"", $str);
			if(@mb_ereg(chr(195).chr(162).chr(226).chr(130).chr(172).chr(226).chr(132).chr(162), $str))
				$str = mb_ereg_replace(chr(195).chr(162).chr(226).chr(130).chr(172).chr(226).chr(132).chr(162), "'", $str);
			if(@mb_ereg(chr(195).chr(131).chr(194).chr(171), $str))
				$str = mb_ereg_replace(chr(195).chr(131).chr(194).chr(171), "e", $str);
			if(@mb_ereg(chr(195).chr(131).chr(194).chr(175), $str))
				$str = mb_ereg_replace(chr(195).chr(131).chr(194).chr(175), "i", $str);
			if(@mb_ereg(chr(195).chr(131).chr(226).chr(185), $str))
				$str = mb_ereg_replace(chr(195).chr(131).chr(226).chr(185), "E", $str);
			if(@mb_ereg(chr(195).chr(131).chr(226).chr(176), $str))
				$str = mb_ereg_replace(chr(195).chr(131).chr(226).chr(176), "E", $str);
			$str = preg_replace(array("/([A-Z])".chr(226).chr(128).chr(158)."/", "/([a-z])".chr(226).chr(128).chr(158)."/"), array("\${1}.", "\${1},"), $str);
			$needles = array(
				chr(226).chr(128).chr(158),
				chr(226).chr(128).chr(157),
				chr(194).chr(169),
				chr(226).chr(130).chr(172),
				chr(226).chr(128).chr(153),
				chr(194).chr(174),
				chr(194).chr(176),
				chr(226).chr(128).chr(148),
				chr(194).chr(171),
				chr(195).chr(177),
				chr(226).chr(128).chr(162),
				chr(195).chr(147),
				chr(194).chr(187),
				chr(195).chr(162),
				chr(195).chr(168),
				chr(195).chr(172),
				chr(195).chr(185),
				chr(195).chr(159),
				chr(195).chr(160),
				chr(195).chr(182),
				chr(195).chr(186),
				chr(195).chr(161),
				chr(195).chr(164),
				chr(195).chr(188),
				chr(195).chr(179),
				chr(195).chr(173),
				chr(195).chr(175),
				"(F£e)", "(F6e)", "/\\_", "/\\", "/'\\_", "/'\\", "/�\\",
				chr(65).chr(194).chr(163),
				chr(160), " V/",
				chr(194).chr(165), "Miill.", "&gt;", "&lt;",
				chr(151),
				chr(239).chr(187).chr(191), "&amp;", "&apos;", "&quot;", "\/V", " VV_", " VV.", "\/\/_", "\/\/", "\X/", "\\'X/",
				chr(157),
				chr(226).chr(128).chr(156),
				chr(195).chr(169), "/\ch.", "/\.", "/-\\", "X/", "\X/", "\Y/",
				chr(96).chr(92).chr(226).chr(128).chr(152).chr(105).chr(47),
				chr(96),
				chr(145),
				chr(146),
				chr(226).chr(128).chr(152),
				chr(226).chr(128).chr(153),
				chr(226).chr(128),
				chr(147),
				chr(148),
				chr(152), chr(186), chr(195).chr(156), chr(204), chr(205), chr(206), chr(207)
			);
			$replacements = array(
				"",
				"\"",
				"e",
				"C",
				"'",
				chr(194).chr(176),
				chr(194).chr(176),
				"-",
				".",
				"n",
				".",
				"O",
				".",
				"a",
				"e",
				"i",
				"u",
				"B",
				"a",
				"o",
				"u",
				"a",
				"a",
				"u",
				"o",
				"i",
				"i",
				"(Fee)", "(Fee)", "A.", "A", "A.", "A", "A",
				"AK",
				" ", " W ",
				"W", "Mull.", ">", "<",
				"-",
				"", "&", "'", "\"", "W", " W.", " W.", "W.", "W", "W", "W",
				"",
				"\"",
				"e", "Ach.", "A.", "A","W","W", "W",
				"W",
				"'",
				"'",
				"'",
				"'",
				"'",
				"\"",
				"\"",
				"\"",
				"\"", chr(194).chr(176), "U", "I", "I", "I", "I"
			);
			$str = str_replace($needles, $replacements, $str);
			$str = preg_replace(array("/([A-Za-z]) ?".chr(156)."/", "/(\\d) ?".chr(156)."/"), array("\${1}ae", "\${1}\""), $str);
			if(mb_detect_encoding($str, "auto")) $str = mb_convert_encoding($str, "ISO-8859-1", "auto");
			$ord = ord(substr($str, 0, 1));
			while($ord > 124 || $ord == 46) {
				$str = substr($str, 1);
				$ord = ord(substr($str, 0, 1));
			}
			return $str;
		}
		return "";
	}

	private function fixString($strIn) {//mb_internal_encoding("UTF-8");
		if($strIn) {//echo "\nInput to fixString: ".$str."\n";
			$catNo = $this->catalogNumber;
			$pat = "/\\A[^\w(]+(.*)/s";
			if(preg_match($pat, $strIn, $patMatches)) $strIn = trim($patMatches[1]);//return $str;
			//$strIn = trim($this->convertBadChars($strIn));
			if($catNo) {
				$strIn = str_replace("NY0".$catNo, "", $strIn);
				$strIn = str_replace("NYO".$catNo, "", $strIn);
				$strIn = str_replace("NY".$catNo, "", $strIn);
				$strIn = str_replace("0".$catNo, "", $strIn);
				$strIn = str_replace("O".$catNo, "", $strIn);
				$strIn = str_replace($catNo, "", $strIn);
				$firstChar = substr($catNo, 0, 1);
				if(!is_numeric($firstChar)) {
					while(strlen($catNo) > 1 && !is_numeric($firstChar)) {
						$catNo = substr($catNo, 1);
						$firstChar = substr($catNo, 0, 1);
					}
					$strIn = str_replace("0".$catNo, "", $strIn);
					$strIn = str_replace("O".$catNo, "", $strIn);
					$strIn = str_replace($catNo, "", $strIn);
				}
			}
			$strIn = preg_replace
			(
				array("/Rama.{1,2}ina /i", "/([A-Za-z]{4,14}+)'(of)\\s(\\w+)/i", "/\\s\)M[u][1!|l|]{2}\\.?\\s?Arg[.,]?/i", "/\(Ach\\.?\)\\sM[\\w01]{2,4}\\.?\\sArg\\.?/i", "/M[Uui][A-Za-z][l1!|I]\\.?\\sArg\\.?/", "/M[Uui][il1!| ]{2,5}\\.?\\sArg\\.?/", "/M[ul10|!\']{3,5}\\.?\\sArg\\.?/", "/U-[S5]-A.{0,2}/", "/\\.{3,}/", "/Lichens of(\\w+)/i", "/\\bQu\\wrcus\\b/i", "/\\bVVYOMING\\b/i", "/\) �.{0,2}ch\\./", "/\(�.{0,2}ch\\.\)/"),
				array("Ramalina ", "\${1} \${2} \${3}", ") Mull. Arg.", "(Ach.) Mull. Arg.", "Mull. Arg.", "Mull. Arg.", "Mull. Arg.", "USA", " ", "Lichens of \${1}", "Quercus", "Wyoming", ")Ach.", "(Ach.)"), $strIn, -1, $count
			);
			$sArray = explode("\n", $strIn);
			$str = "";
			foreach($sArray as $s) $str .= trim($s, " \0\x0B!\\~@$%^?*_")."\n";
			$str = preg_replace("/[\n\r]{2,}/", "\n", $str);
			$false_num_class = "[OSZl|I!\d]";//the regex class that represents numbers and characters that numbers are commonly replaced with
			$pattern =
				array(
				"/(?:THE )?L[O0]U[|!1Il]S[|!1Il]ANA STATE UN[|!1Il]VERS[|!1Il]TY(?:\\n| )(?:L[|!1Il][CG]HEN )?HERBAR[|!1Il]UM/is",
				"/(?:THE )?(?:MYC[O0]L[O0]G[|!1Il]CAL )?HERBAR[|!1Il]UM [ODQ0]F(?:\\n| )L[O0]U[|!1Il]S[|!1Il]ANA STATE UN[|!1Il]VERS[|!1Il]TY/is",
				"/H[ec]rbarium [o0]. th[ec] Univ[ec]rsity of C[o0][|!1Il][o0]rad[o0](?:\\s?-\\s?|\\s)?B[o0]u[|!1Il]d[ec]r/is",
				"/(?:For the )?H[ec]rbarium [o0]. th[ec] Univ[ec]rsity of (?:(?:(?:[A-Z][A-Za-z]?\\.|[A-Za-z]{3,8}) )?[A-Za-z]{6,}(?:(?:\\s?-\\s?|,\\s)(?:(?:[A-Z][A-Za-z]?\\.|[A-Za-z]{3,8}) )?[A-Za-z]{6,})?)/is",
				"/.{1,2}niversity.{3,21}Herbarium(?: ?\(.{1,4}\))?/i",
				"/.{1,2}niversity.{3,21}(?: ?\(.{2,4}\))/i",
				"/\\n\\.([1-9]\\d?\\b[^�]*)/",
				"/.*[esc]por[ec] no. \/ ?as[ec]us/is",
				"/.*co[|!1Il]or: ?h[ygq]a[|!1Il]{2}ne ?\/ ?brown ?\//is",
				"/\\n *[_-]{3,} */",
				"/ *[_-]{3,} */",
				"/(?:�|\^)\+(\\d)/",
				"/(\\d)(?:�|\^)\+/",
				"/\\bJParish\\b/i",
				"/\\b(hi(?:gh)?|low|up|grass|border|meadow|flat|wet)-\\n(lands?)\\b/i",
				"/([.A-Za-z]):([.A-Za-z])/",
				"/\\bWasnington\\b/",
				"/(\\d) ?' ?' ?([NSEWV])/",
				"/(\\d ?� ?\\d{1,2} ?'(?: ?\\d{1,2} ?\")?)\\n([EW][.,]{0,2}) /sm",
				"/((?:Fair[|!1Il]y |Quite |Very |Not |Most[|!1Il]y )?(?:(?:Un)?Common|(?:In)?Frequent|(?:Loca[|!1Il]{2}y )?Abundant|Found|Loose|[GC]rowing|Epi(?:phyt|lith|xyl)ic|Xy[|!1Il]icolous)) on\\n([A-Za-z])/i",
				"/ on\n([A-Za-z])/",
				"/Collected . ?y\\b/i",
				"/Collected b.\\b/i",
				"/JoDaviess County/i",
				"/\\bMAC FADDEN\\b/i",
				"/LASALLE PARISH/i",
				"/WEESTER COUNTY/i",
				"/ +([:;])([A-Za-z])/",
				"/ +([,.])([A-HJKLMNPRT-Ya-kmnp-y])/",
				"/ +([,:;.]\\W)/",
				"/([A-Za-z])Co\\., ([A-Z])/",
				"/^LEWI[S5]\\s?AND\\s?CLARK\\s?CAVERN[S5]\\s?[S5]TATE\\s?PARK/im",
				"/With the Cooperation of (?:Mr\\. )?Charles [DP]eering/is",
				"/(?:(?:HERBAR[|!1Il]UM [ODQ0]F\\s)?+The )?+NEW Y[O0]RK B[O0]TAN[|!1Il][CG]AL [CG]ARDEN/is",
				"/[|!1I ]{9,}/", //strings of ~s, 's, "s, @s, ls, Is, 1s, |s ,/s, \s, Us and Hs more than six characters long (from barcodes)
				"/[\|!Iil\"'1U~()@\[\]{}H\/\\\]{6,}/", //strings of ~s, 's, "s, @s, ls, Is, 1s, |s ,/s, \s, Us and Hs more than six characters long (from barcodes)
				"/^.{1,2}$/m", //one-character lines (Tesseract must generate a 2-char end of line)
				"/(lat|long)(\.|,|:|.:|itude)(:|,)?\s?(".$false_num_class."{1,3}(?:\.".$false_num_class."{1,7})?)\"/i" //the beginning of lat-long repair
				);
			$replacement =
				array(
					"",
					"",
					"",
					"",
					"",
					"",
					"\n0.\${1}",
					"",
					"",
					"\n",
					" ",
					"4\${1}",
					"\${1}4",
					"Parish",
					"\${1}\${2}",
					"\${1}: \${2}",
					"Washington",
					"\${1}\"\${2}",
					"\${1}\${2}\n",
					"\n\${1} on \${2}",
					"\non \${1}",
					"Collected by",
					"Collected by",
					"Jo Daviess County",
					"MacFadden",
					"La Salle Parish",
					"Webster County",
					"\${1} \${2}",
					"\${1} \${2}",
					"\${1}",
					"\${1} Co., \${2}",
					"Lichens of Lewis and Clark Caverns State Park",
					"",
					"",
					"",
					"",
					"",
					"\${1}\${2}\$3 \${4}".chr(176)
				);
			$str = preg_replace($pattern, $replacement, $str, -1);
			$str = str_replace("°", chr(176), $str);
			$str = $this->fixLatLongs($this->replaceMissingDegreeSignsInLatLongs($str));
			$str = $this->fixDates($str);
			$sArray = explode("\n", $str);
			$sResult = "";
			foreach($sArray as $s) $sResult .= trim(ltrim($s, " ;:.,'"), " \0\x0B!\\~@$%^?*_")."\n";
			$sResult = preg_replace("/[\n\r]{2,}/", "\n", $sResult);
			return $sResult;
		}
		return $strIn;
	}

	private function fixGapsInMDYDates($str, $possibleNumbers, $possibleMonths) {
		if($str) {
			$badDatePatternStr = "/(.*)\\b((?i)".$possibleMonths.")\\s([0123Ili!|oOzZ]\\s?".$possibleNumbers."?|[1-9Ili!|oOzZS])(?i:th|st|rd|nd)?,?\\s?([12Iil!|zZ]\\s?"
				."[7890Oo]\\s?".$possibleNumbers."\\s?".$possibleNumbers."\\b)(.*)/s";
			if(preg_match($badDatePatternStr, $str, $locMatches)) {//$i=0;foreach($locMatches as $locMatche) echo "\nline 5056, locMatche[".$i++."] = ".$locMatche."\n";
				return $this->fixGapsInMDYDates($locMatches[1], $possibleNumbers, $possibleMonths).$locMatches[2]." ".
					str_replace(' ', '', $locMatches[3]).", ".str_replace(' ', '', $locMatches[4]).$locMatches[5];
			}
		}
		return $str;
	}

//this function does not fix gaps in years when the days have only a single digit
	private function fixGapsInDMYDates($str, $possibleNumbers, $possibleMonths) {
		if($str) {
			$result = "";
			$badDatePatternStr = "/(.*)\\b([0123Iil!|oOQzZ]\\s?".$possibleNumbers.")([-\\s])?((?i)".$possibleMonths.")([-\\s]?)(".
				"[12Iil!|zZ]\\s?[7890OQo]\\s?".$possibleNumbers."\\s?".$possibleNumbers."\\b)(.*)/s";
			if(preg_match($badDatePatternStr, $str, $locMatches)) {//$i=0;foreach($locMatches as $locMatche) echo "\nline 5005, locMatche[".$i++."] = ".$locMatche."\n";
				return $this->fixGapsInDMYDates($locMatches[1], $possibleNumbers, $possibleMonths).str_replace(' ', '', $locMatches[2]).
					$locMatches[3].$locMatches[4].$locMatches[5].str_replace(' ', '', $locMatches[6]).$locMatches[7];
			}
		}
		return $str;
	}

	private function replaceMistakenCharactersInMDYDates($str, $possibleNumbers, $possibleMonths) {
		if($str) {
			$badDatePatternStr = "/(.*)\\b((?i)".$possibleMonths.")\\s([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".
				$possibleNumbers."|3[1Iil!|OQ0])[,.]?\\s((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2})\\b(.*)/s";
			if(preg_match($badDatePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 5017, dateMatches[".$i++."] = ".$dateMatche."\n";
				return $this->replaceMistakenCharactersInMDYDates($dateMatches[1], $possibleNumbers, $possibleMonths).
					ucfirst($this->replaceMistakenLetters($dateMatches[2]))." ".$this->replaceMistakenNumbers($dateMatches[3]).", ".
					$this->replaceMistakenNumbers($dateMatches[4]).$dateMatches[5];
			}
			$badDatePatternStr = "/(.*)\\b((?i)".$possibleMonths.")\\s([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".
				$possibleNumbers."|3[1Iil!|OQ0])[,.]?\\s((?-i)[1Iil!|])([OQ0])(?i)(".$possibleNumbers."{2})\\b(.*)/s";
			if(preg_match($badDatePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 5024, dateMatches[".$i++."] = ".$dateMatche."\n";
				$temp = str_replace(array("O", "0", "Q"), "9", trim($dateMatches[5]));
				return $this->replaceMistakenCharactersInMDYDates($dateMatches[1], $possibleNumbers, $possibleMonths).
					ucfirst($this->replaceMistakenLetters($dateMatches[2]))." ".$this->replaceMistakenNumbers($dateMatches[3]).", ".
					$this->replaceMistakenNumbers($dateMatches[4]).$temp.$this->replaceMistakenNumbers($dateMatches[6]).$dateMatches[7];
			}
		}
		return $str;
	}

//if it finds a date in the form DayMonthYear it fixes.
	private function replaceMistakenCharactersInDMYDates($str, $possibleNumbers, $possibleMonths) {
		if($str) {
			$datePatternStr = "/(.*)\\b([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".
				$possibleNumbers."|3[1Iil!|OQ0\]])[- ]?((?i)".$possibleMonths.")[- ]?((?:[1Iil!|][789]|[zZ2][OQ0])".
				$possibleNumbers."{2}\\b)(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 5040, dateMatches[".$i++."] = ".$dateMatche."\n";
				return $this->replaceMistakenCharactersInDMYDates($dateMatches[1], $possibleNumbers, $possibleMonths).
					$this->replaceMistakenNumbers($dateMatches[2])." ".
					ucfirst($this->replaceMistakenLetters($dateMatches[3]))." ".
					$this->replaceMistakenNumbers($dateMatches[4]).$dateMatches[5];
			}
		}
		return $str;
	}

//if it finds a date in the form MonthYear it fixes.
	private function replaceMistakenCharactersInMYDates($str, $possibleNumbers, $possibleMonths) {
		if($str) {
			$datePatternStr = "/(.*)\\b((?i)".$possibleMonths."),?\\s?((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2}\\b)(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 5053, dateMatches[".$i++."] = ".$dateMatche."\n";
				return $this->replaceMistakenCharactersInMYDates($dateMatches[1], $possibleNumbers, $possibleMonths).
					ucfirst($this->replaceMistakenLetters($dateMatches[2]))." ".
					$this->replaceMistakenNumbers($dateMatches[3]).$dateMatches[4];
			}
			$datePatternStr = "/(.*)\\b((?i)".$possibleMonths."),?\\s?(?-i)([1Iil!|])([OQ0])(?i)(".$possibleNumbers."{2})\\b(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 5060, dateMatches[".$i++."] = ".$dateMatche."\n";
				$temp = str_replace(array("O", "Q", "0"), "9", $dateMatches[4]);
				return $this->replaceMistakenCharactersInMYDates($dateMatches[1], $possibleNumbers, $possibleMonths).
					ucfirst($this->replaceMistakenLetters($dateMatches[2]))." ".
					$this->replaceMistakenNumbers($dateMatches[3]).$temp.$this->replaceMistakenNumbers($dateMatches[5]).$dateMatches[6];
			}
		}
		return $str;
	}

	private function convertRomanNumeralNumsToMonths($str) {
		if($str) {
			return str_replace
			(
				array("IV", "VIII", "VII", "III", "XII", "II", "IX", "VI", "V", "XI", "I", "X"),
				array("April", "August", "July", "March", "December", "February", "September", "June", "May", "November", "January", "October"),
				$str
			);
		}
		return $str;
	}

//if it finds a numeric date it converts it to a normal month in the format DD Month YYYY.
	private function convertNumericDates($str, $possibleMonths) {
		if($str) {
			$letterMonths = array
			(
				1=>'January',
				2=>'February',
				3=>'March',
				4=>'April',
				5=>'May',
				6=>'June',
				7=>'July',
				8=>'August',
				9=>'September',
				10=>'October',
				11=>'November',
				12=>'December'
			);
			$possibleNumbers = "[0-9&OQIil!|ozZsS]";
			$datePatternStr = "/(.*?)\\b([OQ0]?+".$possibleNumbers."|[Iil!|1][12Iil!|zZ])\\s?[.-]\\s?".
				"(?:(?:([OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".$possibleNumbers."|3[1Iil!|OQ0\]])\\s?[.-])\\s?)".
				"((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2})\\b(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 5751, dateMatches[".$i++."] = ".$dateMatche."\n";
				$mIndex = ltrim($this->replaceMistakenNumbers($dateMatches[2]), "0");
				if($mIndex > 0 && $mIndex <= 12) {
					return $this->convertNumericDates($dateMatches[1], $possibleMonths).
						$this->replaceMistakenNumbers($this->replaceMistakenNumbers($dateMatches[3]))." ".
						$letterMonths[$mIndex]." ".
						$this->replaceMistakenNumbers($dateMatches[4]).$dateMatches[5];
				} else return $str;
			}
			$datePatternStr = "/(.*?)\\b(?:(?:([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".$possibleNumbers."|3[1Iil!|OQ0\]])\\s?[.-])\\s?)".
				"([OQ0]?".$possibleNumbers."|[Iil!|1][12Iil!|zZ])\\s?[.-]\\s?((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2})\\b(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 5762, dateMatches[".$i++."] = ".$dateMatche."\n";
				$mIndex = ltrim($this->replaceMistakenNumbers($dateMatches[3]), "0");
				if($mIndex > 0 && $mIndex <= 12) {
					return $this->convertNumericDates($dateMatches[1], $possibleMonths).
						$this->replaceMistakenNumbers($this->replaceMistakenNumbers($dateMatches[2]))." ".
						$letterMonths[$mIndex]." ".
						$this->replaceMistakenNumbers($dateMatches[4]).$dateMatches[5];
				} else return $str;
			}
			$datePatternStr = "/(.*?)\\b(?<!January )(?<!February )(?<!March )(?<!Apr[1Iil!|]{2} )(?<!May )(?<!June )(?<!Ju[1Iil!|]y )(?<!August )".
				"(?<![S5]eptember )(?<![OQ0]ctober )(?<!November )(?<!December )([OQ0]?".$possibleNumbers."|[Iil!|1][12Iil!|zZ]) ?[.-] ?".
				"((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2})\\b(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 5772, dateMatches[".$i++."] = ".$dateMatche."\n";
				$firstPart = $dateMatches[1];
				if(preg_match("/\\d{1,2}:$/", trim($firstPart))) return $str;
				$mIndex = ltrim($this->replaceMistakenNumbers($dateMatches[2]), "0");
				if($mIndex > 0 && $mIndex <= 12) {
					return $this->convertNumericDates($firstPart, $possibleMonths).
						$letterMonths[$mIndex]." ".
						$this->replaceMistakenNumbers($dateMatches[3]).$dateMatches[4];
				} else return $str;
			}
		}
		return $str;
	}

//if it finds a numeric date with a Roman Numeral month it converts it to a normal month in the format DD-Month-YYYY.
	private function convertRomanNumeralDates($str) {
		if($str) {
			$possibleNumbers = "[0-9&OQIil!|ozZsS]";
			$datePatternStr = "/(.*?)\\b(?:([OQ0]?".$possibleNumbers."|[Iil!|zZ1l2]".$possibleNumbers."|3[1Iil!|OQ0])[,.-]\\s?)".
				"([1lI][1lI ]{1,3}+|[1lI] ?[VX]|V ?[1lI]{0,3}+|X ?[1lI]{0,2}+)[.-]\\s?((?:[1Iil!|][789]|[zZ12][OQ0])".$possibleNumbers."{2})(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 5794, dateMatches[".$i++."] = ".$dateMatche."\n";
				return $this->convertRomanNumeralDates($dateMatches[1]).
					$this->replaceMistakenNumbers(trim($dateMatches[2]))."-".
					$this->convertRomanNumeralNumsToMonths(str_replace(array("l", "1", " "), array("I", "I", ""), $dateMatches[3]))."-".
					$this->replaceMistakenNumbers($dateMatches[4]).$dateMatches[5];
			}
			$datePatternStr = "/(.*?)\\b([1lI][1lI ]{1,3}+|[1lI] ?[VX]|V ?[1lI]{0,3}+|X ?[1lI]{0,2}+)[.-] ?".
				"((?:[1Iil!|][789]|[zZ12][OQ0])".$possibleNumbers."{2})(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 5802, dateMatches[".$i++."] = ".$dateMatche."\n";
				$firstPart = trim($dateMatches[1]);
				if(preg_match("/\\d{1,2}:$/", trim($firstPart))) return $str;
				$revFirstPart = strrev($firstPart);
				if(strcmp(substr($revFirstPart, 0, 2), "/\\") == 0) {
					$firstPart = trim(substr($firstPart, 0, strlen($firstPart)-2));
					$pat2 = "/(.*?)\\b(?:([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".$possibleNumbers."|3[1Iil!|OQ0])\\.\\s?)$/s";
					if(preg_match($pat2, $firstPart, $dateMatches2)) {
						$firstPart = trim($dateMatches2[1]);
						return trim($this->convertRomanNumeralDates($firstPart))." ".
							$this->replaceMistakenNumbers(trim($dateMatches2[2]))."-".
							$this->convertRomanNumeralNumsToMonths(str_replace(array("l", "1", " "), array("I", "I", ""), "V".$dateMatches[2]))."-".
							$this->replaceMistakenNumbers($dateMatches[3]).$dateMatches[4];
					}
				}
				return trim($this->convertRomanNumeralDates($firstPart))." ".
					$this->convertRomanNumeralNumsToMonths(str_replace(array("l", "1", " "), array("I", "I", ""), $dateMatches[2]))."-".
					$this->replaceMistakenNumbers($dateMatches[3]).$dateMatches[4];
			}
			$datePatternStr = "/(.*?)\\b(?:([OQ0]?".$possibleNumbers."|[Iil!|zZ1l2]".$possibleNumbers."|3[1Iil!|OQ0])[,.-]\\s?)".
				"(i{1,3}+|i ?[vx]|v ?i{0,3}+|x ?i{0,2}+)[,.-]\\s?((?:[1Iil!|][789]|[zZ12][OQ0])".$possibleNumbers."{2})(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 5823, dateMatches[".$i++."] = ".$dateMatche."\n";
				return $this->convertRomanNumeralDates($dateMatches[1]).
					$this->replaceMistakenNumbers(trim($dateMatches[2]))."-".
					$this->convertRomanNumeralNumsToMonths(strtoupper(str_replace(" ", "", $dateMatches[3])))."-".
					$this->replaceMistakenNumbers($dateMatches[4]).$dateMatches[5];
			}
		}
		return $str;
	}

	private function fixDates($str) {
		if($str) {
			$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))?|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
			$possibleNumbers = "[0-9&OQIil!|ozZsS]";
			$str = preg_replace_callback(
				'/(]an(?:\\.|(?:uary))?|]un[\\.|e]?|]u[l1|I!][\\.y]?\\s[0123Il!|oOQzZ]\\s?'.
				$possibleNumbers.',?\\s[12Il!|zZ]\\s?[7890OQo]'.$possibleNumbers.'\\s?'.$possibleNumbers.')\\b/',
				function($matches){return str_replace("]", "J", $matches[1]);},
				$str
			);
			$result = $this->fixGapsInDMYDates($str, $possibleNumbers, $possibleMonths);
			$result = $this->fixGapsInMDYDates($result, $possibleNumbers, $possibleMonths);
			$result = $this->replaceMistakenCharactersInDMYDates($result, $possibleNumbers, $possibleMonths);
			$result = $this->replaceMistakenCharactersInMYDates($result, $possibleNumbers, $possibleMonths);
			$result = $this->replaceMistakenCharactersInMDYDates($result, $possibleNumbers, $possibleMonths);
			$result = $this->convertRomanNumeralDates($result);
			$result = $this->convertNumericDates($result, $possibleMonths);
			return $result;
		}
		return $str;
	}

//fix latLongs in the form "34Ol7'N, ll7O56'W".  It won't necessarily fix latLongs containing Os misplaced with 0s.  But will fix others
	private function replaceMissingDegreeSignsInLatLongs($str) {//echo "\nInput to replaceMissingDegreeSignsInLatLongs:\n".$str."\n";
		if($str) {//COLO-B-0006949, COLO-B-0001119, COLO-B-0000147
			//$badLatLongPatStr = "/(.*)\\b([SZl|IO!\d]{2})[0oOQ�]((?(?<=�)(?:[SZl|IO!\d]{2,3})|(?:[SZl|I!\d][SZl|I!O\d]{1,2}))) ?' ?".
			//	"(?:([OQSZl|I!\d]{1,2}) ?\")? ?(N(?i:orth)?|S(?:outh)?)'?[.,;]{0,2}(?: ?L(?:at|ong)(?:\\.|[i1!|]tude)?(?-i))?[;:]?,?(?:\\s|\\n|\\r\\n)".
			//	"((?(?=�)[1I!l|]?[SZl|IO!\d]{2}|(?:[SZl|I!O\d]{2}|[1I!l|][SZl|IO!\d][SZl|I!\d])))[0oOQ�] ?".
			//	"([SZl|I!\d][SZl|IO!\d]{1,2}) ?' ?(?:([OQSZl|I!\d]{1,2}|H)? ?[\"\'])? ?((?i)E(?:ast)?|(?:W|VV)(?:est)?)(?-i)\\b[.,;]{0,2}(.*)/s";
			$badLatLongPatStr = "/(.*)\\b([SZl|IO!\d]{2})[0oOQ�]((?(?<=�)(?: ?[SZl|IO!\d]{2,3})|(?:[SZl|I!\d][SZl|I!O\d]{1,2}))) ?' ?".
				"(?:([OQSZl|I!\d]{1,2}) ?\")? ?(N(?i:orth)?|S(?:outh)?)'?[.,;]{0,2}(?: ?L(?:at|ong)(?:\\.|[i1!|]tude)?(?-i))?[;:]?,?(?:\\s|\\n|\\r\\n)".
				"((?(?=�)[1I!l|]?[SZl|IO!\d]{2} ?|(?:[SZl|I!O\d]{2}|[1I!l|][SZl|IO!\d][SZl|I!\d])))[0oOQ�] ?".
				"([SZl|I!\d][SZl|IO!\d]{1,2}) ?' ?(?:([OQSZl|I!\d]{1,2}|H)? ?[\"\'])? ?((?i)E(?:ast)?|(?:W|VV)(?:est)?)(?-i)\\b[.,;]{0,2}(.*)/s";
			if(preg_match($badLatLongPatStr, $str, $latLongMatchesBad)) {//$i=0;foreach($latLongMatchesBad as $latLongMatchesBa) echo "\nlatLongMatchesBad[".$i++."] = ".$latLongMatchesBa."\n";
				$latSeconds = $latLongMatchesBad[4];
				if($latSeconds) {
					$latSeconds = str_replace
					(
						array("S", "Z", "l", "|", "I", "!", "'"),
						array("5", "2", "1", "1", "1", "1", "\""),
						$latSeconds
					)."\"";
				}
				$longSeconds = $latLongMatchesBad[8];
				if($longSeconds) {
					$longSeconds = str_replace
					(
						array("H", "S", "Z", "l", "|", "I", "!", "'"),
						array("11", "5", "2", "1", "1", "1", "1", "\""),
						$longSeconds
					)."\"";
				}
				$str = $this->replaceMissingDegreeSignsInLatLongs($latLongMatchesBad[1]).
					str_ireplace(array("S", "Z", "l", "|", "I", "!", "O"), array("5", "2", "1", "1", "1", "1", "0"), $latLongMatchesBad[2])."�".
					str_ireplace(array("S", "Z", "l", "|", "I", "!", "O"), array("5", "2", "1", "1", "1", "1", "0"), $latLongMatchesBad[3])."'".
					$latSeconds.$latLongMatchesBad[5].", ".
					str_ireplace(array("S", "Z", "l", "|", "I", "!", "O"), array("5", "2", "1", "1", "1", "1", "0"), $latLongMatchesBad[6])."�".
					str_ireplace(array("S", "Z", "l", "|", "I", "!", "O"), array("5", "2", "1", "1", "1", "1", "0"), $latLongMatchesBad[7])."'".
					$longSeconds.str_ireplace("VV", "W", $latLongMatchesBad[9]).$latLongMatchesBad[10];
			} else {
				$badLatLongPatStr = "/(.*)\\b((?(?=�)(?:[1I!l|][SZl|IO!\d]{2})|(?:[1I!l|][SZl|I!O\d][SZl|I!\d]|[SZl|I!O\d][SZl|I!\d])))[0oOQ� ]".
					"((?:[SZl|IO!\d]{2}))\\s?'\\s?(?:([OQSZl|I!\d]{1,2}|H)? ?[\"\'])? ?(E|W|VV)'?[.,;]{0,2}".
					"(?i: ?L(?:at|ong)(?:\\.|[i1!|]tude)?(?-i))?[;:]?,?(?:\\s|\\n|\\r\\n)".
					"((?(?=�)(?:[SZl|IO!\d]{2})|(?:[SZl|I!\d]{2})))[0oOQ� ]([SZl|IO!\d]{2}) ?'\\s?".
					"(?:([OQSZl|I!\d]{1,2}|H)? ?[\"\'])? ?([NS])\\b[.,;]{0,2}(.*)/s";
				if(preg_match($badLatLongPatStr, $str, $latLongMatchesBad)) {//$i=0;foreach($latLongMatchesBad as $latLongMatchesBa) echo "\nlatLongMatchesBad[".$i++."] = ".$latLongMatchesBa."\n";
					$longSeconds = $latLongMatchesBad[4];
					if($longSeconds) {
						$longSeconds = str_replace
						(
							array("H", "S", "Z", "l", "|", "I", "!", "'"),
							array("11", "5", "2", "1", "1", "1", "1", "\""),
							$longSeconds
						)."\"";
					}
					$latSeconds = $latLongMatchesBad[8];
					if($latSeconds) {
						$latSeconds = str_replace
						(
							array("H", "S", "Z", "l", "|", "I", "!", "'"),
							array("11", "5", "2", "1", "1", "1", "1", "\""),
							$latSeconds
						)."\"";
					}
					$str = $this->replaceMissingDegreeSignsInLatLongs($latLongMatchesBad[1]).
						str_ireplace(array("S", "Z", "l", "|", "I", "!", "O"), array("5", "2", "1", "1", "1", "1", "0"), $latLongMatchesBad[2])."�".
						str_ireplace(array("S", "Z", "l", "|", "I", "!", "O"), array("5", "2", "1", "1", "1", "1", "0"), $latLongMatchesBad[3])."'".
						$longSeconds.str_ireplace("VV", "W", $latLongMatchesBad[5]).", ".
						str_ireplace(array("S", "Z", "l", "|", "I", "!", "O"), array("5", "2", "1", "1", "1", "1", "0"), $latLongMatchesBad[6])."�".
						str_ireplace(array("S", "Z", "l", "|", "I", "!", "O"), array("5", "2", "1", "1", "1", "1", "0"), $latLongMatchesBad[7])."'".
						$latSeconds.$latLongMatchesBad[9].$latLongMatchesBad[10];
				}
			}
		}
		return $str;
	}

	protected function getCountry($c) {
		if($c) {
			if($this->isCountry($c)) {
				if(strpos($c, ".") !== FALSE) return $c;
				else return ucwords($c);
			} else {
				$c2 = str_replace("Q", "O", $c);
				if($this->isCountry($c2)) {
					if(strpos($c2, ".") !== FALSE) return $c2;
					else return ucwords($c2);
				} else {
					$c3 = str_replace("U", "O", $c);
					if($this->isCountry($c3)) {
						if(strpos($c3, ".") !== FALSE) return $c3;
						else return ucwords($c3);
					} else  {
						$c4 = str_replace("l", "I", $c);
						if($this->isCountry($c4)) {
							if(strpos($c4, ".") !== FALSE) return $c4;
							else return ucwords($c4);
						}
					}
				}
			}
		}
		return '';
	}

	protected function isCountry($country) {
		if($country != null) {
			$countryPatStr = '/(Canada|CA\\.?|Can\\.?|Un[il!|1]ted States(?: [o0]f America)?|U\\.?S\\.?(?:A\\.?)?|Mexic[o0]|MX\\.?|Mex\\.?|".
				"S[o0]UTH AFR[il!|1]CA|S[o0]\\.? AFR[il!|1]CA|C[o0]sta\\sR[il!|1]ca|N[o0]rway|[S5]w[ec]d[ec]n|F[il!|1]n[il!|1]and|Braz[il!|1]{2}|Japan|".
				"Russia|U\\.?[S5]\\.?[S5]\\.?R\\.?|[GC]reen[il!|1]and|[GC]uatama[il!|1]a|[GC]hi[il!|1]e)/i';
			$m = preg_match($countryPatStr, $country, $matches);
			if($m) {
				if(strcasecmp($country, $matches[1]) == 0) return true;
			}
			return $this->isCountryInDatabase($country);
		}
		return false;
	}

	protected function getStateOrProvince($d) {//echo "\nInput to getStateOrProvince: ".$d."\n";
		if($d != null) {
			$d = trim($d, " ,:;");
			$pos = stripos($d, " State");
			if($pos !== FALSE) $d = trim(substr($d, 0, $pos));
			$countries = array
			(
				'Mexico'=>array
				(
					'Aguascalientes'=>'/\\b(?i:Aguascal[il!|1][ec]nt[ec]s|(?-i)AG\\.?$)/',
					'Baja California'=>'/\\bBaja\\sCal[il!|1]forn[il!|1]a\\b/i',
					'Baja California Sur'=>'/\\b(?i:Baja Cal[il!|1]forn[il!|1]a Sur|(?-i)B\\.?[S5]\\.?$)/',
					'Campeche'=>'/\\b(?i:Camp[ec]{2}h[ec]|(?-i)CM\\.?$)/',
					'Chiapas'=>'/\\b(?i:Ch[il!|1]apas|(?-i)CS\\.?$)/',
					'Chihuahua'=>'/\\b(?i:Ch[il!|1]huahua|(?-i)CH\\.?$)/',
					'Coahuila'=>'/\\bCoahu[il!|1]{2}a\\b/i',
					'Colima'=>'/\\b(?i:Co[il!|1]{2}ma|(?-i)CL\\.?$)/',
					'Durango'=>'/\\b(?i:Durang[o0uq]|(?-i)DG\\.?$)/',
					'Guanajuato'=>'/\\b(?i:Guanajuat[o0uq]|(?-i)GT\\.?$)/',
					'Guerrero'=>'/\\b(?i:Gu[ec]rr[ec]r[o0uq]|(?-i)DG\\.?$)/',
					'Hidalgo'=>'/\\b(?i:H[il!|1]dalg[o0uq]|(?-i)HG\\b\\.?$)/',
					'Jalisco'=>'/\\b(?i:Ja[il!|1]{2}sc[o0uq]|(?-i)JA\\.?$)/',
					'M�xico'=>'/\\b(?<!New )M[�e]xic[o0uq]\\b/i',
					'Michoac�n'=>'/\\bM[il!|1]ch[o0uq]ac[�a]n\\b/i',
					'Morelos'=>'/\\bMorelo[o0uq]s\\b/i',
					'Nayarit'=>'/\\b(?i:Nayar[il!|1]t|(?-i)NA\\.?$)/',
					'Nuevo Le�n'=>'/\\b(?i:Nu[ec]v[o0uq]\\sL[ec]�n|Nu[ec]v[o0uq] L[ec][o0uq]n|(?-i)N\\.?L\\.?$)/',
					'Oaxaca'=>'/\\b(?:[o0uq]axaca|[o0uq]A\\b\\.?)/i',
					'Puebla'=>'/\\b(?i:Pu[ec]bla|(?-i)PB\\.?$)/',
					'Quer�taro'=>'/\\bQu[ec]r[ec�]tar[o0uq]\\b/i',
					'Quintana Roo'=>'/\\b(?i:Qu[il!|1]ntana\\sR[o0uq]{2}|(?-i)QR\\.?$)/',
					'San Luis Potos�'=>'/\\b(?i:San Lu[il!|1]s Potos[il!|1�]|(?-i)S\\.?L\\.?$)/',
					'Sinaloa'=>'/\\b(?i:S[il!|1]nal[o0uq]a|(?-i)SI\\.?$)/',
					'Tabasco'=>'/\\b(?i:Tabas[ec][o0uq]|(?-i)TB\\.?$)/',
					'Tamaulipas'=>'/\\b(?i:Tamaul[il!|1]pa[s5]|(?-i)TM\\.?$)/',
					'Tlaxcala'=>'/\\b(?i:T[il!|1]ax[ec]ala|(?-i)TL\\.?$)/',
					'Veracruz'=>'/\\b(?i:V[ec]ra[ec]ruz|(?-i)VE\\.?$)/',
					'Yucat�n'=>'/\\b(?i:Yu[ec]at�n|Yu[ec]atan|(?-i)YU\\.?$)/',
					'Zacatecas'=>'/\\b(?i:Za[ec]at[ec]{2}as|(?-i)ZA\\.?$)/'
				),
				'USA'=>array
				(
					'Alabama'=>'/(?i)(?<!University of )(?-i)\\b(?i:A[il!|1]abama|A[il!|1]a\\b\\.?|(?-i)AL\\.?$)/',
					'Alaska'=>'/(?i)(?<!University of )(?-i)\\b(?i:A[il!|1]aska|(?-i)AK\\.?$)/',
					'Arizona'=>'/(?i)(?<!University of )(?-i)\\b(?i:Ar[il!|1][z2][o0]na|Ariz\\b\\.?|(?-i)AZ\\.?$)/',
					'Arkansas'=>'/(?i)(?<!University of )(?-i)\\b(?i:Arkan[s5]a[s5]|Ark\\b\\.?|(?-i)AR\\.?$)/',
					'California'=>'/(?i)(?<!University of )(?-i)\\b(?:C(?i)a[il!|1]{2}f[o0uq]rn[il!|1]a|Cal[il!|1]f\\b\\.?|(?-i)C(?i)A\\.$)/',
					'Colorado'=>'/(?i)(?<!University of )(?-i)\\b(?:C(?i)[o0uq][il!|1][o0uq]rad[o0uq]|C[o0uq][il!|1][o0]$)/',
					'Connecticut'=>'/(?i)(?<!University of )(?-i)\\b(?i:C[o0uq]nn[ec]{2}t[il!|1]cut|C[o0uq]nn\\b\\.?|(?-i)CT\\.?$)/',
					'Delaware'=>'/(?i)(?<!University of )(?-i)\\b(?i:D[ec]lawar[ec](?! Mountains)|Del\\b\\.?|(?-i)DE\\b\\.$)/',
					'DC'=>'/\\b(?i:D[il!|1][s5]tr[il!|1]ct [o0uq]f C[o0uq][il!|1]umb[il!|1]a|(?-i)D\\.?C\\.?$)/',
					'Florida'=>'/(?i)(?<!University of )(?-i)\\b(?i:F[il!|1][o0uq]r[il!|1]da|F[il!|1]a\\b\\.?|(?-i)F[Ll1|!]\\.?$)/',
					'Georgia'=>'/(?i)(?<!University of )(?-i)\\b(?i:G[ec][o0uq]rg[il!|1]a|(?-i)G[Aa]\\.?$)/',
					'Hawaii'=>'/(?i)(?<!University of )(?-i)\\b(?i:Hawa[il!|1]{2}|(?-i)H[il!|1I]\\.?$)/',
					'Idaho'=>'/(?i)(?<!University of )(?-i)\\b(?i:[il!|1]dah[o0uq]|(?-i)[Il!|1]D\\.?$)/',
					'Illinois'=>'/(?i)(?<!University of )(?-i)\\b(?i:[il!|1]{4}n[o0Q][il!|1]s|(?-i)[Il!|1]{3}?\\.$)/',
					'Indiana'=>'/(?i)(?<!University of )(?-i)\\b(?i:[il!|1]nd[il!|1]ana|[il!|1]nd\\b\\.?|(?-i)[Il!|1]N\\b\\.$)/',
					'Iowa'=>'/(?i)(?<!University of )(?-i)\\b(?i:[il!|1][o0uq]wa|(?-i)I[Aa]\\.$)/',
					'Kansas'=>'/(?i)(?<!University of )(?-i)\\b(?i:Kansas|Kans\\b\\.?|(?-i)KS\\.?$)/',
					'Kentucky'=>'/(?i)(?<!University of )(?-i)\\b(?i:K[ec]ntucky|(?-i)KY\\.?$)/',
					'Louisiana'=>'/(?i)(?<!University of )(?-i)\\b(?i:[il!|1][o0uq]u[il!|1]s[il!|1]ana|(?-i)L[Aa]\\.?$)/',
					'Maine'=>'/(?i)(?<!University of )(?-i)\\b(?i:Ma[il!|1]ne|(?-i)M[Ee]\\b\\.?)/',
					'Maryland'=>'/(?i)(?<!University of )(?-i)\\b(?i:Mary[il!|1]and|(?-i)MD\\b\\.?)/',
					'Massachusetts'=>'/(?i)(?<!University of )(?-i)\\b(?i:Ma[s5]{2}achu[s5][ec]tt[s5]|Ma[s5]{2}\\b\\.?|(?-i)M[Aa]\\.?$)/',
					'Michigan'=>'/(?i)(?<!University of )(?-i)\\b(?:M(?i)[il!|1][ec]h[il!|1]gan|M[il!|1][ec]h\\b\\.?|(?-i)M(?i)[il!|1]\\.?$)/',
					'Minnesota'=>'/(?i)(?<!University of )(?-i)\\b(?i:M[il!|1]nn[ec]s[o0uq]ta|M[il!|1]nn\\b\\.?|(?-i)M[Nn]\\.?$)/',
					'Mississippi'=>'/(?i)(?<!University of )(?-i)\\b(?i:M[il!|1][s5]{2}[il!|1][s5]{2}[il!|1]pp[il!|1]|M[il!|1][s5]{2}\\b\\.?|(?-i)M[Ss5]\\.?$)/',
					'Missouri'=>'/(?i)(?<!University of )(?-i)\\b(?i:M[il!|1][s5]{2}[o0uq]ur[il!|1]|(?-i)M[Oo0Q]\\.?$)/',
					'Montana'=>'/(?i)(?<!University of )(?-i)\\b(?:M(?i)[o0uq]ntana|M[o0uq]nt\\b\\.?|(?-i)M(?i)T\\.?$)/',
					'Nebraska'=>'/(?i)(?<!University of )(?-i)\\b(?i:N[ec]braska|N[ec]br\\b\\.?|(?-i)N[eE]\\.?$)/',
					'Nevada'=>'/(?i)(?<!University of )(?-i)\\b(?i:N[ec]vada|N[ec]v\\b\\.?|(?-i)NV:|NV\\.?$)/',
					'New Hampshire'=>'/(?i)(?<!University of )(?-i)\\b(?i:N[ec]w\\sHampsh[il!|1]r[ec]|(?-i)N\\.?H\\.?$)/',
					'New Jersey'=>'/(?i)(?<!University of )(?-i)\\b(?i:N[ec]w\\sJ[ec]rs[ec]y|(?-i)N\\.?J\\.?$)/',
					'New Mexico'=>'/(?i)(?<!University of )(?-i)\\b(?i:N[ec]w\\sM[ec]x[il!|1][ec][o0uq]|N\\.? M[ec]x\\b\\.?|(?-i)NM:|NM\\.?$)/',
					'New York'=>'/(?i)(?<!University of )(?-i)\\b(?i:N[ec]w\\sY[o0uq]rk|(?-i)N\\.?Y\\.?$)/',
					'North Carolina'=>'/(?i)(?<!University of )(?-i)\\b(?i:N[o0uq]rth\\sCar[o0uq][il!|1]{2}na|(?-i)N\\.?C\\.?$)/',
					'North Dakota'=>'/(?i)(?<!University of )(?-i)\\b(?i:N[o0uq]rth\\sDak[o0uq]ta|N\\.? Dak\\b\\.?|(?-i)N\\.?D\\.?$)/',
					'Ohio'=>'/(?i)(?<!University of )(?-i)\\b(?i:[o0uq]h[il!|1][o0uq]|(?-i)[O0Q]H\\b\\.?)/',
					'Oklahoma'=>'/(?i)(?<!University of )(?-i)\\b(?i:[o0uq]k[il!|1]ah[o0uq]ma|[o0uq]k[il!|1]a\\b\\.?|(?-i)[O0Q][Kk]\\.$)/',
					'Oregon'=>'/(?i)(?<!University of )(?-i)\\b(?i:[o0uq]r[ec]g[o0uq]n|[o0uq]r[ec]g\\b\\.?|(?-i)[O0Q][Rr]\\.$)/',
					'Pennsylvania'=>'/(?i)(?<!University of )(?-i)\\b(?i:P[ec]nnsy[il!|1]van[il!|1]a|(?-i)P[Aa]\\.?$)/',
					'Puerto Rico'=>'/(?i)(?<!University of )(?-i)\\b(?i:Puert[o0] R[il!|1]c[o0]|(?-i)P\\.?R\\.?$)/',
					'Rhode Island'=>'/(?i)(?<!University of )(?-i)\\b(?i:Rh[o0uq]de\\s[il!|1]s[il!|1]and|(?-i)R\\.?[Ii1|]\\.?$)/',
					'South Carolina'=>'/(?i)(?<!University of )(?-i)\\b(?i:S[o0uq]uth\\sCar[o0uq][il!|1]{2}na|(?-i)S\\.?C\\.?$)/',
					'South Dakota'=>'/(?i)(?<!University of )(?-i)\\b(?i:S[o0uq]uth\\sDak[o0uq]ta|S\\.?\\sDak\\b\\.?|(?-i)S\\.?D\\.?$)/',
					'Tennessee'=>'/(?i)(?<!University of )(?-i)\\b(?i:T[ec]nn[ec]ss[ec]{2}|T[ec]nn\\b\\.?|(?-i)TN\\.?$)/',
					'Texas'=>'/(?i)(?<!University of )(?-i)\\b(?i:T[ec]xas|T[ec]x\\b\\.?|(?-i)TX:|TX\\.?$)/',
					'Utah'=>'/(?i)(?<!University of )(?-i)\\b(?i:Utah|(?-i)UT:|UT\\.?$)/',
					'Vermont'=>'/(?i)(?<!University of )(?-i)\\b(?i:V[ec]rm[o0uq]nt|(?-i)VT\\.?$)/',
					'Virginia'=>'/(?i)(?<!\\bWest )(?<!University of )(?-i)\\b(?i:V[il!|1]rg[il!|1]n[il!|1]a|(?-i)VA\\.?$)/',
					'Washington'=>'/(?i)(?<!University of )(?-i)\\b(?i:Wash[il!|1]ngt[o0uq]n|Wash\\b\\.?|(?-i)WA\\.?$)/',
					'West Virginia'=>'/(?i)(?<!University of )(?-i)\\b(?i:West V[il!|1]rg[il!|1]n[il!|1]a|(?-i)W\\.? ?V[aA]?\\.?$)/',
					'Wisconsin'=>'/(?i)(?<!University of )(?-i)\\b(?i:W[il!|1]s[ec][o0uq]ns[il!|1]n|(?-i)W[il!|1I][sS]?\\.?$)/',
					'Wyoming'=>'/(?i)(?<!University of )(?-i)\\b(?i:W[gy][o0uq]m[il!|1]n[gy]|(?-i)W[gyY][oO0Q]?\\.?$)/'
				),
				'Canada'=>array
				(
					'Ontario'=>'/(?i)(?<!University of )(?-i)\\b(?i:[o0uq]ntari[o0uq]|[o0uq]nt\\b\\.?|(?-i)[O0Q]N\\.$)/',
					'Quebec'=>'/(?i)(?<!University of )(?-i)\\b(?i:[o0uq]u[ec]b[ec]{2}|(?-i)[O0Q][Cc]\\.$)/',
					'Nova Scotia'=>'/(?i)(?<!University of )(?-i)\\b(?i:N[o0uq]va\\s[S5]c[o0uq]t[il!|1]a|(?-i)N\\.?[S5]\\.?$)/',
					'New Brunswick'=>'/(?i)(?<!University of )(?-i)\\b(?i:N[ec]w\\sBrunsw[il!|1][ec]k|(?-i)N\\.?B\\.?$)/',
					'Manitoba'=>'/(?i)(?<!University of )(?-i)\\b(?i:Man[il!|1]t[o0uq]ba|(?-i)MB\\.?$)/',
					'British Columbia'=>'/(?i)(?<!University of )(?-i)\\b(?i:Br[il!|1]t[il!|1][S5]h\\sC[o0uq]lumb[il!|1]a|(?-i)B\\.?C\\.?$)/',
					'Prince Edward Island'=>'/(?i)(?<!University of )(?-i)\\b(?i:Pr[il!|1]n[ec]{2}\\s[ec]dward\\s[il!|1]sland|(?-i)P\\.?E\\.?I\\b\\.?|P\\.?E\\.?$)/',
					'Saskatchewan'=>'/(?i)(?<!University of )(?-i)\\b(?i:[S5]a[S5]kat[ec]h[ec]wan|[S5]a[S5]k\\b\\.?|(?-i)[S5]K\\b\\.?)/',
					'Alberta'=>'/(?i)(?<!University of )(?-i)\\b(?i:A[il!|1]b[ec]rta|A[il!|1]b\\.?|(?-i)AB\\.?$)/',
					'Northwest Territories'=>'/(?i)(?<!University of )(?-i)\\b(?i:N[o0uq]rthw[ec]st T[ec]rr[il!|1]t[o0uq]r[il!|1]es|(?-i)N\\.?W\\.?T\\.?$)/',
					'Yukon'=>'/(?i)(?<!University of )(?-i)\\b(?i:Yuk[o0uq]n|(?-i)YT\\.?$)/',
					'Nunavut'=>'/(?i)(?<!University of )(?-i)\\b(?i:Nunavut|(?-i)NU\\.?$)/',
					'Newfoundland and Labrador'=>'/(?i)(?<!University of )(?-i)\\b(?i:N[ec]wf[o0uq]undland and Labrad[o0uq]r|N[ec]wf[o0uq]undland|Labrad[o0uq]r|(?-i)N\\.?L\\.?$)/'
				)
			);

			foreach($countries as $country=>$divisions) {
				foreach($divisions as $name=>$pat) if(preg_match($pat, $d)) {/*echo "\nreturnin line 5336, name: ".$name."\npat: ".$pat."\n";*/return array($name, $country);}
			}
		}
		return array();
	}

	protected function isUSState($state) {
		if($state != null) {//echo "\nInput State: ".$state."\n";
			$state = trim($state, " ,");
			$statePatStr = '(A[il!|1]abama|A[il!|1]\\.?|A[il!|1]a\\.?|'.
			'A[il!|1]aska|AK\\.?|'.
			'Ar[il!|1]z[o0uq]na|AZ\\.?|Ar[il!|1]z\\.?|'.
			'Arkansas|AR\\.?|Ark\\.?|'.
			'Ca[il!|1]{2}f[o0uq]rn[il!|1]a|CA\\.?|Cal[il!|1]f\\.?|'.
			'C[o0uq][il!|1][o0uq]rad[o0uq]|C[o0uq]\\.?|C[o0uq][il!|1][o0uq]\\.?|'.
			'C[o0uq]nnect[il!|1]cut|CT\\.?|C[o0uq]nn\\.?|'.
			'Delaware|DE\\.?|Del\\.?|'.
			'D[il!|1]str[il!|1]ct [o0uq]f C[o0uq]lumbia|D\\.?C\\.?|'.
			'F[il!|1][o0uq]r[il!|1]da|F[il!|1]\\.?|F[il!|1]a\\.?|'.
			'Ge[o0uq]rg[il!|1]a|GA\\.?|'.
			'Hawa[il!|1]{2}|H[il!|1]\\.?|'.
			'[il!|1]dah[o0uq]|[il!|1]D\\.?|'.
			'[il!|1]{4}n[o0uq][il!|1]s|[il!|1]{3}?\\.?|'.
			'[il!|1]nd[il!|1]ana|[il!|1]N\\.?|[il!|1]nd\\.?|'.
			'[il!|1][o0uq]wa|[il!|1]A\\.?|'.
			'Kansas|KS\\.?|Kans\\.?|'.
			'Kentucky|KY\\.?|'.
			'[il!|1][o0uq]u[il!|1]s[il!|1]ana|[il!|1]A\\.?|'.
			'Ma[il!|1]ne|ME\\.?|'.
			'Mary[il!|1]and|MD\\.?|'.
			'Massachusetts|MA\\.?|Mass\\.?|'.
			'M[il!|1]ch[il!|1]gan|M[il!|1]\\.?|M[il!|1]ch\\.?|'.
			'M[il!|1]nnes[o0uq]ta|MN\\.?|M[il!|1]nn\\.?|'.
			'M[il!|1]ss[il!|1]ss[il!|1]pp[il!|1]|MS\\.?|M[il!|1]ss\\.?|'.
			'M[il!|1]ss[o0uq]ur[il!|1]|M[o0uq]\\.?|'.
			'M[o0uq]ntana|MT\\.?|M[o0uq]nt\\.?|'.
			'Nebraska|NE\\.?|Nebr\\.?|'.
			'Nevada|NV\\.?|Nev\\.?|'.
			'New Hampsh[il!|1]re|N\\.?H\\.?|'.
			'New Jersey|N\\.?J\\.?|'.
			'New Mex[il!|1]c[o0uq]|NM\\.?|N\\.? Mex\\.?|'.
			'New Y[o0uq]rk|N\\.?Y\\.?|'.
			'N[o0uq]rth Car[o0uq][il!|1]{2}na|N\\.?C\\.?|'.
			'N[o0uq]rth Dak[o0uq]ta|N\\.?D\\.?|N\\.? Dak\\.?|'.
			'[o0uq]h[il!|1][o0uq]|[o0uq]H\\.?|'.
			'[o0uq]klah[o0uq]ma|[o0uq]K\\.?|[o0uq]kla\\.?|'.
			'[o0uq]reg[o0uq]n|[o0uq]R\\.?|[o0uq]reg\\.?|'.
			'Pennsylvan[il!|1]a|PA\\.?|'.
			'Puert[o0] R[il!|1]c[o0]|PR\\.?|'.
			'Rh[o0uq]de [il!|1]s[il!|1]and|R\\.?I\\.?|'.
			'S[o0uq]uth Car[o0uq]l[il!|1]na|S\\.?C\\.?|'.
			'S[o0uq]uth Dak[o0uq]ta|S\\.?D\\.?|S\\.? Dak\\.?|'.
			'Tennessee|TN\\.?|Tenn\\.?|'.
			'Texas|TX\\.?|Tex\\.?|'.
			'Utah|UT\\.?|'.
			'Verm[o0uq]nt|VT\\.?|'.
			'V[il!|1]rg[il!|1]n[il!|1]a|VA\\.?|'.
			'Wash[il!|1]ngt[o0uq]n|WA\\.?|Wash\\.?|'.
			'West V[il!|1]rg[il!|1]n[il!|1]a|W\\.? Va?\\.?|'.
			'W[il!|1]sc[o0uq]ns[il!|1]n|W[il!|1]s?\\.?|'.
			'Wy[o0uq]ming|Wy[o0uq]?\\.?'.
			')';
			$m = preg_match('/^'.$statePatStr.'$/i', $state, $matches);
			if($m) {
				$thisState = $matches[1];
				if($state == $thisState) return true;
			}
			$m = preg_match('/\\b'.$statePatStr.'[ ,.:;\n]{1,2}/i', $state, $matches);
			if($m) {
				$thisState = $matches[1];
				if($state == $thisState) return true;
			}
			return ($m && ($state == $matches[1] || $state == $matches[1]."."));
		}
		return false;
	}

	protected function replaceMistakenLetters($word) {
		$retStr = str_replace(array("1", "|", "!", "0", "5", "]"), array("l", "l", "l", "O", "S", "J"), $word);
		$patStr = "/(Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Sep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|Oct(?:\\.|(?:ob\\w{1,2}))?|Nov(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)/";
		if(preg_match($patStr, $retStr, $matches)) {
			$match = $matches[1];
			if(substr_compare($match, "Jan", 0, 3, TRUE) == 0) return "January";
			else if(substr_compare($match, "Feb", 0, 3, TRUE) == 0) return "February";
			else if(substr_compare($match, "Sep", 0, 3, TRUE) == 0) return "September";
			else if(substr_compare($match, "Oct", 0, 3, TRUE) == 0) return "October";
			else if(substr_compare($match, "Nov", 0, 3, TRUE) == 0) return "November";
			else if(substr_compare($match, "Dec", 0, 3, TRUE) == 0) return "December";
		}
		return $retStr;
	}

	protected function replaceMistakenNumbers($num) {
		return str_replace(array("l", "I", "|", "!", "i", "O", "Q", "o", "s", "S", "Z", "z", "&", "U", "[", "]"), array("1", "1", "1", "1", "1", "0", "0", "0", "5", "5", "2", "2", "6", "4", "1", "1"), $num);
	}

	private function getDates($str, $possibleMonths) {
		if($str) {
			return array_merge($this->getDMYDates($str, $possibleMonths), $this->getMDYDates($str, $possibleMonths), $this->getNumericDates($str));
		}
		return array();
	}

	protected function getVerbatimCoordinates($str) {
		if($str) {
			$result = "";
			$index = 0;
			if($latLongs = $this->getLatLongs($str)) {
				foreach($latLongs as $latLong) {
					if($index++ == 0) $result .= $latLong["latitude"].", ".$latLong["longitude"];
					else $result .= "; ".$latLong["latitude"].", ".$latLong["longitude"];
				}
			}
			$utms = $this->getUTMCoordinates($str);
			if($utms) {
				foreach($utms as $utm) {
					if($index++ == 0) $result .= $utm;
					else $result .= "; ".$utm;
				}
			}
			$trss = $this->getTRSCoordinates($str);
			if($trss) {
				foreach($trss as $trs) {
					if($index++ == 0) $result .= $trs;
					else $result .= "; ".$trs;
				}
			}
			return $result;
		}
		return "";
	}

	public function setCollId($id){
		if(is_numeric($id)){
			$this->collId = $id;
		}
	}

	public function setCatalogNumber($cn){
		$this->catalogNumber = $cn;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>