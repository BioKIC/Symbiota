<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class TaxonomyUtilities {

	/*
	 * INPUT: String representing a verbatim scientific name
	 *        Name may have imbedded authors, cf, aff, hybrid
	 * OUTPUT: Array containing parsed values
	 *         Keys: sciname, unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, author, identificationqualifier, rankid
	 */
	public static function parseScientificName($inStr, $conn = null, $rankId = 0, $kingdomName = null){
		//Converts scinetific name with author embedded into separate fields
		$retArr = array('unitname1'=>'','unitname2'=>'','unitind3'=>'','unitname3'=>'');
		$inStr = trim($inStr);
		if($inStr && is_string($inStr)){
			//Remove underscores, common in NPS data
			$inStr = preg_replace('/_+/',' ',$inStr);
			//Replace misc
			$inStr = str_replace(array('?','*'),'',$inStr);

			if(stripos($inStr,'cfr. ') !== false || stripos($inStr,' cfr ') !== false){
				$retArr['identificationqualifier'] = 'cf. ';
				$inStr = str_ireplace(array(' cfr ','cfr. '),' ',$inStr);
			}
			elseif(stripos($inStr,'cf. ') !== false || stripos($inStr,'c.f. ') !== false || stripos($inStr,' cf ') !== false){
				$retArr['identificationqualifier'] = 'cf. ';
				$inStr = str_ireplace(array(' cf ','c.f. ','cf. '),' ',$inStr);
			}
			elseif(stripos($inStr,'aff. ') !== false || stripos($inStr,' aff ') !== false){
				$retArr['identificationqualifier'] = 'aff.';
				$inStr = trim(str_ireplace(array(' aff ','aff. '),' ',$inStr));
			}
			if(stripos($inStr,' spp.')){
				$rankId = 180;
				$inStr = str_ireplace(' spp.','',$inStr);
			}
			if(stripos($inStr,' sp.')){
				$rankId = 180;
				$inStr = str_ireplace(' sp.','',$inStr);
			}
			//Remove extra spaces
			$inStr = preg_replace('/\s\s+/',' ',$inStr);

			$sciNameArr = explode(' ',trim($inStr));
			$okToCloseConn = true;
			if($conn !== null) $okToCloseConn = false;
			if(count($sciNameArr)){
				if(strtolower($sciNameArr[0]) == 'x' || $sciNameArr[0] == '×' || mb_ord($sciNameArr[0]) == 215){
					$retArr['unitind1'] = array_shift($sciNameArr);
				}
				elseif($sciNameArr[0] == '†' || mb_ord($sciNameArr[0]) == 8224){
					$retArr['unitind1'] = array_shift($sciNameArr);
				}
				elseif(strpos($sciNameArr[0],chr(8224)) === 0 ){
					$retArr['unitind1'] = '†';
					$sciNameArr[0] = trim($sciNameArr[0],'†');
				}
				//Genus
				$retArr['unitname1'] = ucfirst(strtolower(array_shift($sciNameArr)));
				if(count($sciNameArr)){
					if(strtolower($sciNameArr[0]) == 'x' || mb_ord($sciNameArr[0]) == 215){
						//Species level hybrid
						$retArr['unitind2'] = array_shift($sciNameArr);
						$retArr['unitname2'] = array_shift($sciNameArr);
					}
					elseif(strpos($sciNameArr[0],'.') !== false){
						//It is assumed that Author has been reached, thus stop process
						$retArr['author'] = implode(' ',$sciNameArr);
						unset($sciNameArr);
					}
					else{
						if(strpos($sciNameArr[0],'(') !== false){
							//Assumed subgenus exists, but keep a author incase an epithet does exist
							$retArr['author'] = implode(' ',$sciNameArr);
							array_shift($sciNameArr);
						}
						//Specific Epithet
						$retArr['unitname2'] = array_shift($sciNameArr);
					}
					if($retArr['unitname2'] && !preg_match('/^[\-\'a-z]+$/',$retArr['unitname2'])){
						if(preg_match('/[A-Z]{1}[\-\'a-z]+/',$retArr['unitname2'])){
							//Check to see if is term is genus author
							if($conn === null) $conn = MySQLiConnectionFactory::getCon('readonly');
							$sql = 'SELECT tid FROM taxa WHERE unitname1 = "'.$conn->real_escape_string($retArr['unitname1']).'" AND unitname2 = "'.$conn->real_escape_string($retArr['unitname2']).'"';
							$rs = $conn->query($sql);
							if($rs->num_rows){
								if(isset($retArr['author'])) unset($retArr['author']);
							}
							else{
								//Second word is likely author, thus assume assume author has been reach and stop process
								$retArr['author'] = trim($retArr['unitname2'].' '.implode(' ', $sciNameArr));
								$retArr['unitname2'] = '';
								unset($sciNameArr);
							}
							$rs->free();
						}
						if($retArr['unitname2']){
							$retArr['unitname2'] = strtolower($retArr['unitname2']);
							if(!preg_match('/^[\-\'a-z]+$/',$retArr['unitname2'])){
								//Second word unlikely an epithet
								$retArr['author'] = trim($retArr['unitname2'].' '.implode(' ', $sciNameArr));
								$retArr['unitname2'] = '';
								unset($sciNameArr);
							}
						}
					}
				}
			}
			if(isset($sciNameArr) && $sciNameArr){
				if($rankId == 220){
					$retArr['author'] = implode(' ',$sciNameArr);
				}
				else{
					$authorArr = array();
					//cycles through the final terms to evaluate and extract infraspecific data
					while($sciStr = array_shift($sciNameArr)){
						if($testArr = self::cleanInfra($sciStr)){
							self::setInfraNode($sciStr, $sciNameArr, $retArr, $authorArr, $testArr['infra']);
						}
						elseif($kingdomName == 'Animalia' && !$retArr['unitname3'] && ($rankId == 230 || preg_match('/^[a-z]{3,}$/',$sciStr) || preg_match('/^[A-Z]{3,}$/',$sciStr))){
							$retArr['unitind3'] = '';
							$retArr['unitname3'] = strtolower($sciStr);
							unset($authorArr);
							$authorArr = array();
						}
						else{
							$authorArr[] = $sciStr;
						}
					}
					$retArr['author'] = implode(' ', $authorArr);
					//Double check to see if infraSpecificEpithet is still embedded in author due initial lack of taxonRank indicator
					if(!$retArr['unitname3'] && $retArr['author']){
						$arr = explode(' ',$retArr['author']);
						$firstWord = array_shift($arr);
						if(preg_match('/^[\-\'a-z]{2,}$/',$firstWord)){
							if($conn === null) $conn = MySQLiConnectionFactory::getCon('readonly');
							$sql = 'SELECT unitind3 FROM taxa WHERE unitname1 = "'.$conn->real_escape_string($retArr['unitname1']).'" AND unitname2 = "'.$conn->real_escape_string($retArr['unitname2']).'" AND unitname3 = "'.$conn->real_escape_string($firstWord).'" ';
							//echo $sql.'<br/>';
							$rs = $conn->query($sql);
							if($r = $rs->fetch_object()){
								$retArr['unitind3'] = $r->unitind3;
								$retArr['unitname3'] = $firstWord;
								$retArr['author'] = implode(' ',$arr);
							}
							$rs->free();
						}
					}
				}
			}
			if($conn !== null && $okToCloseConn) $conn->close();
			//Set taxon rankid
			if($rankId && is_numeric($rankId)){
				$retArr['rankid'] = $rankId;
			}
			else{
				if($retArr['unitname3']){
					if($retArr['unitind3'] == 'subsp.' || !$retArr['unitind3']){
						$retArr['rankid'] = 230;
					}
					elseif($retArr['unitind3'] == 'var.'){
						$retArr['rankid'] = 240;
					}
					elseif($retArr['unitind3'] == 'f.'){
						$retArr['rankid'] = 260;
					}
				}
				elseif($retArr['unitname2']){
					$retArr['rankid'] = 220;
				}
				elseif($retArr['unitname1']){
					if(substr($retArr['unitname1'],-5) == 'aceae' || substr($retArr['unitname1'],-4) == 'idae'){
						$retArr['rankid'] = 140;
					}
				}
			}
			if($kingdomName == 'Animalia'){
				if($retArr['unitind3']){
					$retArr['unitind3'] = '';
					if($retArr['rankid'] > 220) $retArr['rankid'] = 230;
				}
			}
			//Build sciname, without author
			$sciname = (isset($retArr['unitind1'])?$retArr['unitind1'].' ':'').$retArr['unitname1'].' ';
			$sciname .= (isset($retArr['unitind2'])?$retArr['unitind2'].' ':'').$retArr['unitname2'].' ';
			$sciname .= trim($retArr['unitind3'].' '.$retArr['unitname3']);
			$retArr['sciname'] = trim($sciname);
		}
		return $retArr;
	}

	public static function cleanInfra($testStr){
		$retArr = array();
		$testStr = str_replace(array('-','_',' '),'',$testStr);
		$testStr = strtolower(trim($testStr,'.'));
		if($testStr == 'cultivated' || $testStr == 'cv' ){
			$retArr['infra'] = 'cv.';
			$retArr['rankid'] = 300;
		}
		elseif($testStr == 'subform' || $testStr == 'subforma' || $testStr == 'subf' || $testStr == 'subfo'){
			$retArr['infra'] = 'subf.';
			$retArr['rankid'] = 270;
		}
		elseif($testStr == 'forma' || $testStr == 'f' || $testStr == 'fo'){
			$retArr['infra'] = 'f.';
			$retArr['rankid'] = 260;
		}
		elseif($testStr == 'subvariety' || $testStr == 'subvar' || $testStr == 'subv' || $testStr == 'sv'){
			$retArr['infra'] = 'subvar.';
			$retArr['rankid'] = 250;
		}
		elseif($testStr == 'variety' || $testStr == 'var' || $testStr == 'v'){
			$retArr['infra'] = 'var.';
			$retArr['rankid'] = 240;
		}
		elseif($testStr == 'subspecies' || $testStr == 'ssp' || $testStr == 'subsp'){
			$retArr['infra'] = 'subsp.';
			$retArr['rankid'] = 230;
		}
		return $retArr;
	}

	private static function setInfraNode($sciStr, &$sciNameArr, &$retArr, &$authorArr, $rankTag){
		if($sciNameArr){
			$infraStr = array_shift($sciNameArr);
			if(preg_match('/^[a-z]{3,}$/', $infraStr)){
				$retArr['unitind3'] = $rankTag;
				$retArr['unitname3'] = $infraStr;
				unset($authorArr);
				$authorArr = array();
			}
			else{
				$authorArr[] = $sciStr;
				$authorArr[] = $infraStr;
			}
		}
	}

	//Taxonomic indexing functions
	public static function rebuildHierarchyEnumTree($conn = null){
		$status = true;
		if(!$conn) $conn = MySQLiConnectionFactory::getCon('write');
		if($conn){
			if($conn->query('DELETE FROM taxaenumtree')){
				self::buildHierarchyEnumTree($conn);
			}
			else{
				$status = 'ERROR deleting taxaenumtree prior to re-populating: '.$conn->error;
			}
		}
		else $status = 'ERROR deleting taxaenumtree prior to re-populating: NULL connection object';
		return $status;
	}

	public static function buildHierarchyEnumTree($conn = null, $taxAuthId = 1){
		set_time_limit(600);
		$status = true;
		if(!$conn) $conn = MySQLiConnectionFactory::getCon('write');
		if($conn){
			//Seed taxaenumtree table
			$sql = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid)
				SELECT DISTINCT ts.tid, ts.parenttid, ts.taxauthid
				FROM taxstatus ts
				WHERE (ts.taxauthid = '.$taxAuthId.') AND ts.tid NOT IN(SELECT tid FROM taxaenumtree WHERE taxauthid = '.$taxAuthId.')';
			if($conn->query($sql)){
				//Set direct parents for all taxa
				$sql2 = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid)
					SELECT DISTINCT ts.tid, ts.parenttid, ts.taxauthid
					FROM taxstatus ts LEFT JOIN taxaenumtree e ON ts.tid = e.tid AND ts.parenttid = e.parenttid AND ts.taxauthid = e.taxauthid
					WHERE (ts.taxauthid = '.$taxAuthId.') AND (e.tid IS NULL)';
				if(!$conn->query($sql2)) $status = 'ERROR setting direct parents within taxaenumtree: '.$conn->error;

				//Continue adding more distint parents
				$sql3 = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid)
					SELECT DISTINCT e.tid, ts.parenttid, ts.taxauthid
					FROM taxaenumtree e INNER JOIN taxstatus ts ON e.parenttid = ts.tid AND e.taxauthid = ts.taxauthid
					LEFT JOIN taxaenumtree e2 ON e.tid = e2.tid AND ts.parenttid = e2.parenttid AND e.taxauthid = e2.taxauthid
					WHERE (ts.taxauthid = '.$taxAuthId.') AND (e2.tid IS NULL)';
				$cnt = 0;
				do{
					if(!$conn->query($sql3)){
						$status = 'ERROR building taxaenumtree: '.$conn->error;
						break;
					}
					if(!$conn->affected_rows) break;
					$cnt++;
				}while($cnt < 30);
			}
			else{
				$status = 'ERROR seeding taxaenumtree: '.$conn->error;
			}
		}
		else $status = 'ERROR re-populating taxaenumtree: NULL connection object';
		return $status;
	}

	/*
	public static function buildHierarchyNestedTree($conn, $taxAuthId = 1){
		if($conn){
			set_time_limit(1200);
			//Get root and then build down
			$startIndex = 1;
			$rankId = 0;
			$sql = 'SELECT ts.tid, t.rankid '.
				'FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.tid '.
				'WHERE (ts.taxauthid = '.$taxAuthId.') AND (ts.parenttid IS NULL OR ts.parenttid = ts.tid) '.
				'ORDER BY t.rankid ';
			if($rs = $conn->query($sql)){
				while($r = $rs->fetch_object()){
					if($rankId && $rankId <> $r->rankid) break;
					$rankId = $r->rankid;
					$startIndex = self::loadTaxonIntoNestedTree($conn, $r->tid, $taxAuthId, $startIndex);
				}
				$rs->free();
			}
		}
		else{
			$status = 'ERROR building hierarchy nested tree: NULL connection object';
		}
	}

	private static function loadTaxonIntoNestedTree($conn, $tid, $taxAuthId, $startIndex){
		$endIndex = $startIndex + 1;
		$sql = 'SELECT tid '.
			'FROM taxstatus '.
			'WHERE (taxauthid = '.$taxAuthId.') AND (parenttid = '.$tid.')';
		if($rs = $conn->query($sql)){
			while($r = $rs->fetch_object()){
				$endIndex = self::loadTaxonIntoNestedTree($conn, $r->tid, $taxAuthId, $endIndex);
			}
			$rs->free();
		}
		//Load into taxanestedtree
		$sqlInsert = 'REPLACE INTO taxanestedtree(tid,taxauthid,leftindex,rightindex) '.
			'VALUES ('.$tid.','.$taxAuthId.','.$startIndex.','.$endIndex.')';
		$conn->query($sqlInsert);
		//Return endIndex plus one
		$endIndex++;
		return $endIndex;
	}
	*/
}
?>