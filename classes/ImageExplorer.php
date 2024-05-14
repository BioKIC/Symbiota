<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class ImageExplorer{
    private $debug = FALSE;
	private $conn;
	private $imgCnt = 0;

	public function __construct(){
	 	$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	/*
	 * Input: JSON array
	 * Input criteria: taxon (INT: tid), country (string), state (string), tag (string),
	 *     idNeeded (INT: 0,1), collid (INT), photographer (INT: photographerUid),
	 *     cntPerCategory (INT: 0-2), start (INT), limit (INT)
	 *     e.g. {"state": {"Arizona", "New Mexico"},"taxa":{"Pinus"}}
	 * Output: Array of images
	 */
	public function getImages($searchCriteria){
		$retArr = array();
        if (array_key_exists('taxon',$searchCriteria)) {
           // rewrite key "taxon" shown to users to that recognised for the fieldname "taxa".
           $searchCriteria['taxa'] = $searchCriteria['taxon'];
           unset($searchCriteria['taxon']);
        }
		$sql = $this->getSql($searchCriteria);
        if ($this->debug) { echo "ImageExplorer.getImages sql=[$sql]"; }
		$rs = $this->conn->query($sql);
		if($rs){
			while($r = $rs->fetch_assoc()){
				$retArr[$r['imgid']] = $r;
			}
			$rs->free();

			if($retArr){
				//Grab sciname and tid assigned to img, whether accepted or not
				$sql2 = 'SELECT i.imgid, t.tid, t.sciname FROM images i INNER JOIN taxa t ON i.tid = t.tid '.
					'WHERE i.imgid IN('.implode(',',array_keys($retArr)).')';
				$rs2 = $this->conn->query($sql2);
				if($rs2){
					while($r2 = $rs2->fetch_object()){
						$retArr[$r2->imgid]['tid'] = $r2->tid;
						$retArr[$r2->imgid]['sciname'] = $r2->sciname;
					}
					$rs2->free();
				}
				else{
					echo 'ERROR populating assigned tid and sciname for image: '.$this->conn->error.'<br/>';
					echo 'SQL: '.$sql2;
				}

				//Set image count
				$cntSql = 'SELECT count(DISTINCT i.imgid) AS cnt '.substr($sql,strpos($sql,' FROM '));
				$cntSql = substr($cntSql,0,strpos($cntSql,' LIMIT '));
				//echo '<br/>'.$cntSql.'<br/>';
				$cntRs = $this->conn->query($cntSql);
				if($cntR = $cntRs->fetch_object()){
					$this->imgCnt = $cntR->cnt;
					$retArr['cnt'] = $cntR->cnt;
				}
				$cntRs->free();
			}
            else{
                $retArr['cnt'] = 0;
            }
		}
		else{
			echo 'ERROR returning image recordset: '.$this->conn->error.'<br/>';
			echo 'SQL: '.$sql;
		}
		return $retArr;
	}

	/*
	 * Input: array of criteria (e.g. array("state" => array("Arizona", "New Mexico"))
	 * Input criteria: taxa (INT: tid), country (string), state (string), tag (string),
	 *     idNeeded (INT: 0,1), collid (INT), photographer (INT: photographerUid),
	 *     cntPerCategory (INT: 0-2), start (INT), limit (INT)
	 *     e.g. {"state": ["Arizona", "New Mexico"],"taxa":["Pinus"}}
	 * Output: String, SQL to be used to query database
	 */
	private function getSql($searchCriteria){
		$sqlWhere = '';

		//Set taxa
		if(isset($searchCriteria['taxa']) && $searchCriteria['taxa']){
			$accArr = array_unique($this->getAcceptedTid($searchCriteria['taxa']));
			if(count($accArr) == 1){
				$targetTid = array_shift($accArr);
				//$sqlFrag = $this->getChildSql($targetTid);
                $sqlFrag = $this->getChildTids($targetTid);
				$sqlWhere .= 'AND (i.tid IN('.$sqlFrag.')) ';
			}
			elseif(count($accArr) > 1){
				$tidArr = array_merge($this->getTaxaChildren($accArr),$accArr);
				$tidArr = $this->getTaxaSynonyms($tidArr);
				$sqlWhere .= 'AND (i.tid IN('.implode(',',$this->cleanInArray($tidArr)).')) ';
			}
		}

		// do something with "TEXT"
		if (isset($searchCriteria['text']) && $searchCriteria['text']) {
			$sqlWhere .= 'AND o.scientificName like "%'.$this->cleanInStr($searchCriteria['text'][0]).'%" ';
		}

		//Set country
		if(isset($searchCriteria['country']) && $searchCriteria['country']){
			$countryArr = $this->cleanInArray($searchCriteria['country']);

			//Deal with multiple USA synonyms
			$usaArr = array('usa','united states','united states of america','u.s.a','us');
			foreach($countryArr as $countryStr){
				if(in_array(strtolower($countryStr),$usaArr)){
					$countryArr = array_unique(array_merge($countryArr,$usaArr));
					break;
				}
			}
			$sqlWhere .= 'AND o.country IN("'.implode('","',$countryArr).'") ';
		}

		//Set state
		if(isset($searchCriteria['state']) && $searchCriteria['state']){
			$stateArr = $this->cleanInArray($searchCriteria['state']);
			$sqlWhere .= 'AND o.stateProvince IN("'.implode('","',$stateArr).'") ';
		}

		//Set tag
		if(isset($searchCriteria['tags']) && $searchCriteria['tags']){
			$sqlWhere .= 'AND it.keyvalue IN("'.implode('","',$this->cleanInArray($searchCriteria['tags'])).'") ';
		}
		else{
			/* If no tags, then limit to sort value less than 500,
			 * this is old system for limiting certain images to specimen details page only,
			 * will replace with tag system in near future
			*/
			$sqlWhere .= 'AND i.sortsequence < 500 ';
		}

		//Set collection
		if(isset($searchCriteria['collection']) && $searchCriteria['collection']){
			$sqlWhere .= 'AND o.collid IN('.implode(',',$this->cleanInArray($searchCriteria['collection'])).') ';
		}

		//Set photographers
		if(isset($searchCriteria['photographer']) && $searchCriteria['photographer']){
			$sqlWhere .= 'AND i.photographerUid IN('.implode(',',$this->cleanInArray($searchCriteria['photographer'])).') ';
		}

		if (isset($searchCriteria['idToSpecies']) && $searchCriteria['idToSpecies']
		 && isset($searchCriteria['idNeeded']) && $searchCriteria['idNeeded'] ) {
			// if both are checked, don't include filter on either
			$includeVerification = FALSE;  // used later to include/exclude the join to omoccurrverification
		} else {
			$includeVerification = FALSE;
		    //Needing to be identified to species or lower
		    if(isset($searchCriteria['idNeeded']) && $searchCriteria['idNeeded']){
	   		    $includeVerification = TRUE;
	   		    // include occurrences with no verification of identification and an id of genus or higher or those with an identification verification of poor
	   		    // differs from the query below only in rankid<220
	   		    // complexity is added by futureproofing for use of omoccurrverification for categories other than identification,
	   		    // testing for null omoccurrverification isn't adequate.
			    $sqlWhere .= "AND ( " .
			                 "   (o.occid NOT IN (SELECT occid FROM omoccurverification WHERE (category = \"identification\")) AND (t.rankid < 220 OR o.tidinterpreted IS NULL) ) " .
			                 // "   OR " .
			                 // "   (v.category = 'identification' AND v.ranking < 5) " .
			                 " ) ";
		    }
		    //Identified to species or lower
		    if(isset($searchCriteria['idToSpecies']) && $searchCriteria['idToSpecies']){
	   		   $includeVerification = TRUE;
	   		    // include occurrences with no verification of identification and an id of species or lower or those with an identification verification of good
	   		    // differs from the query above only in rankid>=220 and ranking>=5
			    $sqlWhere .= "AND ( (o.occid IS NULL AND t.rankid IN(220,230,240,260)) OR " .
			                 "   (o.occid NOT IN (SELECT occid FROM omoccurverification WHERE (category = \"identification\")) AND t.rankid IN(220,230,240,260)) " .
			                 "   OR " .
			                 "   (v.category = 'identification' AND v.ranking >= 5) " .
			                 " ) ";
		    }
            // identified with a low quality identification
		    if(isset($searchCriteria['idPoor']) && $searchCriteria['idPoor']){
	   		    $includeVerification = TRUE;
	   		    // include occurrences with an identification verification of poor
			    $sqlWhere .= "AND ( v.category = 'identification' AND v.ranking < 5 ) ";
		    }
		}

		$sqlStr = 'SELECT DISTINCT i.imgid, ts.tidaccepted, i.url, i.thumbnailurl, i.originalurl, '.
			'u.uid, CONCAT_WS(", ",u.lastname,u.firstname) as photographer, i.caption, '.
			'o.occid, o.stateprovince, o.catalognumber, CONCAT_WS("-",c.institutioncode, c.collectioncode) as instcode, '.
			'i.initialtimestamp '.
			'FROM images i LEFT JOIN taxa t ON i.tid = t.tid '.
			'LEFT JOIN taxstatus ts ON t.tid = ts.tid '.
			'LEFT JOIN users u ON i.photographeruid = u.uid '.
			'LEFT JOIN omoccurrences o ON i.occid = o.occid '.
			'LEFT JOIN omcollections c ON o.collid = c.collid ';
		if($includeVerification){
			$sqlStr .= 'LEFT JOIN omoccurverification v ON o.occid = v.occid ';
		}
		if(isset($searchCriteria['tags']) && $searchCriteria['tags']){
			$sqlStr .= 'LEFT JOIN imagetag it ON i.imgid = it.imgid ';
		}
		if(isset($searchCriteria['countPerCategory'])){
			$countPerCategory = (int)$searchCriteria['countPerCategory'];
			if($searchCriteria['countPerCategory'] === 'taxon'){
				//one per taxon, limit to first taxon authority, otherwise results reutrn one per taxon per taxon authority.
				$sqlWhere .= 'AND ts.taxauthid = 1 ';
			}
		}
		// Strip off the leading AND from the assembled where clause.
		if($sqlWhere) $sqlStr .= 'WHERE '.substr($sqlWhere,3);

		// add the group by clause
		if(isset($searchCriteria['countPerCategory'])){
			$countPerCategory = (int)$searchCriteria['countPerCategory'];
			if($searchCriteria['countPerCategory'] === 'taxon'){
				//one per taxon
				$sqlStr .= 'GROUP BY ts.tidaccepted ';
			}
			elseif($searchCriteria['countPerCategory'] === 'specimen'){
				//one per occurrence (countPerCategory == 1)
				$sqlStr .= 'GROUP BY o.occid ';
			}
			else{
				//return all (countPerCategory == 2)
				//Do nothing
			}
		}

		//$sqlStr .= 'ORDER BY i.sortsequence ';
		//Set start and limit
		$start = (isset($searchCriteria['start'])?$searchCriteria['start']:0);
		$limit = (isset($searchCriteria['limit'])?$searchCriteria['limit']:100);
		$sqlStr .= 'LIMIT '.$start.','.$limit;

        //error_log($sqlStr);
		//echo $sqlStr; exit;
		return $sqlStr;
	}

	public function testSql($searchCriteria){
		echo json_encode($searchCriteria).'<br/>';
		echo $this->getSql($searchCriteria).'<br/>';
		//$imgArr = $this->getImages($searchCriteria);
		//print_r($imgArr);
	}

	private function getAcceptedTid($inTidArr){
		$retArr = array();
		$sql = 'SELECT tidaccepted, tid FROM taxstatus WHERE taxauthid = 1 AND tid IN('.preg_replace('/^,+/','',implode(',',$inTidArr)).') ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid] = $r->tidaccepted;
		}
		$rs->free();
		return $retArr;
	}

	//Inner query gets all accepted children for input tid, which is an accepted tid
	//Outer query gets all synonyms for input tid and their children
	//Return should be all children and their synonyms
	private function getChildSql($inTid){
		$sqlInner = 'SELECT DISTINCT ts.tid '.
			'FROM taxstatus ts INNER JOIN taxaenumtree e ON ts.tid = e.tid '.
			'WHERE ts.taxauthid = 1 AND e.taxauthid = 1 AND ts.tid = ts.tidaccepted '.
			'AND (e.parenttid = '.$inTid.' OR ts.parenttid = '.$inTid.') ';
		$sql = 'SELECT DISTINCT tid FROM taxstatus '.
			'WHERE (taxauthid = 1) AND (tidaccepted = '.$inTid.' OR tidaccepted IN('.$sqlInner.'))';
		return $sql;
	}

    /**
     * Construct the same query as getChildSql, only execute the query and get the list of tids,
     * instead of returning the sql.  Nested query in this context ends up preventing use of
     * and index, and substatively slows down query on taxon names.
     *
     * @param inTid the taxon id for which to find the list of all synonyms and their children.
     * @return a list tids consisting of the provided tid, all synonyms and children.
     */
    private function getChildTids($inTid) {
        $result = "$inTid";
        $comma = ',';
		$sqlInner = 'SELECT DISTINCT ts.tid '.
			'FROM taxstatus ts INNER JOIN taxaenumtree e ON ts.tid = e.tid '.
			'WHERE ts.taxauthid = 1 AND e.taxauthid = 1 AND ts.tid = ts.tidaccepted '.
			'AND (e.parenttid = ? OR ts.parenttid = ? ) ';
		$sql = 'SELECT DISTINCT tid FROM taxstatus '.
			'WHERE (taxauthid = 1) AND (tidaccepted = ? OR tidaccepted IN('.$sqlInner.'))';
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
           $stmt->bind_param('iii',$inTid,$inTid,$inTid);
           $stmt->bind_result($tid);
           $stmt->execute();
           while ($stmt->fetch()) {
              $result .= $comma.$tid;
           }
           $stmt->close();
        }
        return $result;
    }

	private function getTaxaChildren($inTidArr){
		//Grab all accepted children
		$childArr = array();
		foreach($inTidArr as $tid){
			$sql = 'SELECT DISTINCT ts.tid '.
				'FROM taxstatus ts INNER JOIN taxaenumtree e ON ts.tid = e.tid '.
				'WHERE ts.taxauthid = 1 AND e.taxauthid = 1 AND ts.tid = ts.tidaccepted '.
				'AND (e.parenttid = '.$tid.' OR ts.parenttid = '.$tid.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$childArr[] = $r->tid;
			}
			$rs->free();
		}
		return array_unique($childArr);
	}

	private function getTaxaSynonyms($inTidArr){
		$synArr = array();
		$searchStr = implode(',',$inTidArr);
		$sql = 'SELECT tid, tidaccepted '.
			'FROM taxstatus '.
			'WHERE taxauthid = 1 AND (tidaccepted IN('.$searchStr.'))';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$synArr[] = $r->tid;
			$synArr[] = $r->tidaccepted;
		}
		$rs->free();
		return array_unique($synArr);
	}

	public function getCountries(){
		$retArr = array();
		$sql = 'SELECT geoterm FROM geographicthesaurus WHERE geolevel = 50';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->geoterm;
		}
		$rs->free();
		return $retArr;
	}

	public function getStates(){
		$retArr = array();
		$sql = 'SELECT DISTINCT geoterm FROM geographicthesaurus WHERE geolevel = 60 ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->statename;
		}
		$rs->free();
		return $retArr;
	}

	public function getCollections(){
		$retArr = array();
        /*
        $sql = 'SELECT count(i.imgid) as ct, c.collid, c.institutioncode, c.collectioncode ' .
               ' FROM omcollections c '.
               '    LEFT JOIN omoccurrences o ON c.collid = o.collid '.
               '    LEFT JOIN images i ON o.occid = i.occid '.
               ' WHERE i.imgid is not null  '.
               '       and i.sortsequence < 500 '.
               ' GROUP BY c.collid, c.institutioncode, c.collectioncode '.
               ' HAVING count(i.imgid) > 0 ';
        */
        $sql = 'SELECT count(i.imgid) as ct, c.collid, c.institutioncode, c.collectioncode ' .
               ' FROM omcollections c '.
               '    INNER JOIN omoccurrences o ON c.collid = o.collid '.
               '    INNER JOIN images i ON o.occid = i.occid '.
               ' WHERE i.sortsequence < 500 '.
               ' GROUP BY c.collid, c.institutioncode, c.collectioncode ';
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_result($count,$collid,$instcode,$collcode);
            $stmt->execute();
  		    while($stmt->fetch()){
              $retArr[] = (object)array(
                  'value' => $collid,
                  'label' => "$instcode-$collcode ($count)"
              );
		}
		   $stmt->close();
        }
		return json_encode($retArr);
	}

	public function getTags() {
		$retArr = array();
		$sql = "select tagkey from imagetagkey order by sortorder asc ";
	    $stmt = $this->conn->stmt_init();
	    $stmt->prepare($sql);
	    if ($stmt) {
            $stmt->execute();
           $stmt->bind_result($shortlabel);
           while ($stmt->fetch()) {
           	  $retArr[] = $shortlabel;
           }
	    }
		return json_encode($retArr);
	}

	//variable setters and getters
	public function getImgCnt(){
		return $this->imgCnt;
	}

	//Misc functions
 	private function cleanInArray($arr){
 		$newArray = Array();
 		foreach($arr as $key => $value){
 			$newArray[$this->cleanInStr($key)] = $this->cleanInStr($value);
 		}
 		return $newArray;
 	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>