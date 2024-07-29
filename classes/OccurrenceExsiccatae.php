<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class OccurrenceExsiccatae {

	private $conn;

	function __construct($type = 'readonly') {
		$this->conn = MySQLiConnectionFactory::getCon($type);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getTitleObj($ometid){
		$retArr = array();
		if($ometid){
			//Display full list
			$sql = 'SELECT ometid, title, abbreviation, editor, exsrange, startdate, enddate, source, notes, lasteditedby FROM omexsiccatititles WHERE ometid = '.$ometid;
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['title'] = $this->cleanOutStr($r->title);
					$retArr['abbreviation'] = $this->cleanOutStr($r->abbreviation);
					$retArr['editor'] = $this->cleanOutStr($r->editor);
					$retArr['exsrange'] = $this->cleanOutStr($r->exsrange);
					$retArr['startdate'] = $this->cleanOutStr($r->startdate);
					$retArr['enddate'] = $this->cleanOutStr($r->enddate);
					$retArr['source'] = $this->cleanOutStr($r->source);
					$retArr['notes'] = $this->cleanOutStr($r->notes);
					$retArr['lasteditedby'] = $r->lasteditedby;
				}
				$rs->free();
			}
			//Once db patch with new sourceIdentifier field is released, we can merge following code into above statement
			$sql = 'SELECT sourceIdentifier FROM omexsiccatititles WHERE ometid = '.$ometid;
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['sourceidentifier'] = $this->cleanOutStr($r->sourceIdentifier);
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getTitleArr($searchTerm, $specimenOnly, $imagesOnly, $collId, $sortBy){
		$retArr = array();
		$sql = 'SELECT DISTINCT et.ometid, et.title, et.editor, et.exsrange, et.abbreviation ';
		$sqlWhere = '';
		if($specimenOnly){
			if($imagesOnly){
				$sql .= 'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
					'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
					'INNER JOIN images i ON ol.occid = i.occid ';
			}
			else{
				//Display only exsiccati that have linked specimens
				$sql .= 'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
					'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid ';
			}
			if($collId){
				$sql .= 'INNER JOIN omoccurrences o ON ol.occid = o.occid ';
				$sqlWhere = 'WHERE o.collid = '.$collId.' ';
			}
		}
		else{
			//Display full list
			$sql .= 'FROM omexsiccatititles et ';
		}
		if($searchTerm){
			$sqlWhere .= ($sqlWhere?'AND ':'WHERE ').'et.title LIKE "%'.$searchTerm.'%" OR et.abbreviation LIKE "%'.$searchTerm.'%" OR et.editor LIKE "%'.$searchTerm.'%" ';
		}
		$sql .= $sqlWhere.'ORDER BY '.($sortBy?"IFNULL(et.abbreviation,et.title)":"et.title").', et.startdate';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$titleStr = $r->title;
				if($sortBy == 1 && $r->abbreviation) $titleStr = $r->abbreviation;
				if(strlen($titleStr)>100) $titleStr = substr($titleStr,0,100).'...';
				$retArr[$r->ometid]['editor'] = $this->cleanOutStr($r->editor);
				$retArr[$r->ometid]['exsrange'] = $this->cleanOutStr($r->exsrange);
				$retArr[$r->ometid]['title'] = $this->cleanOutStr($titleStr);
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getExsNumberArr($ometid,$specimenOnly,$imagesOnly,$collid){
		$retArr = array();
		if($ometid){
			//Grab all numbers for that exsiccati title; only show number that have occid links
			$sql = 'SELECT DISTINCT en.omenid, en.exsnumber, en.notes, o.occid, o.catalognumber, o.sciname, '.
				'CONCAT(o.recordedby," (",IFNULL(o.recordnumber,"s.n."),") ",IFNULL(o.eventDate,"date unknown")) as collector '.
				'FROM omexsiccatinumbers en '.($specimenOnly || $imagesOnly?'INNER':'LEFT').' JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid '.
				($specimenOnly || $imagesOnly?'INNER':'LEFT').' JOIN omoccurrences o ON ol.occid = o.occid ';
			if($imagesOnly) $sql .= 'INNER JOIN images i ON o.occid = i.occid ';
			$sql .= 'WHERE en.ometid = '.$ometid.' ';
			if($collid) $sql .= 'AND o.collid = '.$collid.' ';
			$sql .= 'ORDER BY en.exsnumber+1,en.exsnumber,ol.ranking';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					if(!array_key_exists($r->omenid,$retArr)){
						$retArr[$r->omenid]['number'] = $this->cleanOutStr($r->exsnumber);
						$retArr[$r->omenid]['occurstr'] = $r->collector;
						if($r->occid && !$r->collector) $retArr[$r->omenid]['occurstr'] = $r->collector;
						$retArr[$r->omenid]['sciname'] = $r->sciname;
						$retArr[$r->omenid]['notes'] = $this->cleanOutStr($r->notes);
					}
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getExsNumberObj($omenid){
		$retArr = array();
		if($omenid){
			//Grab info for just that exsiccati number with the title info
			$sql = 'SELECT et.ometid, et.title, et.abbreviation, et.editor, et.exsrange, en.exsnumber, en.notes '.
				'FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid '.
				'WHERE en.omenid = '.$omenid;
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()){
					$retArr['ometid'] = $r->ometid;
					$retArr['title'] = $this->cleanOutStr($r->title);
					$retArr['abbreviation'] = $this->cleanOutStr($r->abbreviation);
					$retArr['editor'] = $this->cleanOutStr($r->editor);
					$retArr['exsrange'] = $this->cleanOutStr($r->exsrange);
					$retArr['exsnumber'] = $this->cleanOutStr($r->exsnumber);
					$retArr['notes'] = $this->cleanOutStr($r->notes);
				}
				$rs->free();
			}
			//Once db patch with new sourceIdentifier field is released, we can merge following code into above statement
			$sql = 'SELECT et.sourceIdentifier FROM omexsiccatititles et INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid WHERE en.omenid = '.$omenid;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['sourceidentifier'] = $this->cleanOutStr($r->sourceIdentifier);
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getExsOccArr($id, $target = 'omenid'){
		$retArr = array();
		$sql = 'SELECT en.omenid, en.exsnumber, ol.ranking, ol.notes, o.occid, o.occurrenceid, o.catalognumber, '.
			'c.collid, c.collectionname, CONCAT_WS("-",c.institutioncode,c.collectioncode) AS collcode, '.
			'o.sciname, o.scientificnameauthorship, o.recordedby, o.recordnumber, DATE_FORMAT(o.eventdate,"%d %M %Y") AS eventdate, '.
			'trim(o.country) AS country, trim(o.stateprovince) AS stateprovince, trim(o.county) AS county, '.
			'trim(o.municipality) AS municipality, o.locality, o.decimallatitude, o.decimallongitude, '.
			'i.imgid, i.thumbnailurl, i.url '.
			'FROM omexsiccatiocclink ol INNER JOIN omoccurrences o ON ol.occid = o.occid '.
			'INNER JOIN omcollections c ON o.collid = c.collid '.
			'INNER JOIN omexsiccatinumbers en ON ol.omenid = en.omenid '.
			'LEFT JOIN images i ON o.occid = i.occid ';
		if($target == 'omenid'){
			$sql .= 'WHERE ol.omenid = '.$id;
		}
		else{
			$sql .= 'WHERE en.ometid = '.$id;
		}
		$sql .= ' ORDER BY en.exsnumber+1, ol.ranking, o.recordedby, o.recordnumber';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				if(!isset($retArr[$r->omenid][$r->occid])){
					$retArr[$r->omenid][$r->occid]['exsnum'] = $this->cleanOutStr($r->exsnumber);
					$retArr[$r->omenid][$r->occid]['ranking'] = $this->cleanOutStr($r->ranking);
					$retArr[$r->omenid][$r->occid]['notes'] = $this->cleanOutStr($r->notes);
					$retArr[$r->omenid][$r->occid]['collid'] = $r->collid;
					$retArr[$r->omenid][$r->occid]['collname'] = $this->cleanOutStr($r->collectionname);
					$retArr[$r->omenid][$r->occid]['collcode'] = $this->cleanOutStr($r->collcode);
					$retArr[$r->omenid][$r->occid]['occurrenceid'] = $r->occurrenceid;
					$retArr[$r->omenid][$r->occid]['catalognumber'] = $r->catalognumber;
					$retArr[$r->omenid][$r->occid]['sciname'] = $this->cleanOutStr($r->sciname);
					$retArr[$r->omenid][$r->occid]['author'] = $this->cleanOutStr($r->scientificnameauthorship);
					$retArr[$r->omenid][$r->occid]['recby'] = $this->cleanOutStr($r->recordedby);
					$retArr[$r->omenid][$r->occid]['recnum'] = $this->cleanOutStr($r->recordnumber);
					$retArr[$r->omenid][$r->occid]['eventdate'] = $r->eventdate;
					$retArr[$r->omenid][$r->occid]['country'] = $r->country;
					$retArr[$r->omenid][$r->occid]['state'] = $r->stateprovince;
					$retArr[$r->omenid][$r->occid]['county'] = $r->county;
					$retArr[$r->omenid][$r->occid]['locality'] = $this->cleanOutStr(($r->municipality?$r->municipality.'; ':'').$r->locality);
					$retArr[$r->omenid][$r->occid]['lat'] = $r->decimallatitude;
					$retArr[$r->omenid][$r->occid]['lng'] = $r->decimallongitude;
				}
				if($r->url){
					$retArr[$r->omenid][$r->occid]['img'][$r->imgid]['url'] = $r->url;
					$retArr[$r->omenid][$r->occid]['img'][$r->imgid]['tnurl'] = ($r->thumbnailurl?$r->thumbnailurl:$r->url);
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function exportExsiccatiAsCsv($searchTerm, $specimenOnly, $imagesOnly, $collId, $titleOnly){
		$fileName = 'exsiccatiOutput_'.time().'.csv';
		header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ('Content-Disposition: attachment; filename="'.$fileName.'"');
		$sqlInsert = '';
		$sqlWhere = '';
		$fieldArr = array('titleID'=>'et.ometid', 'exsiccatiTitle'=>'et.title', 'abbreviation'=>'et.abbreviation', 'editors'=>'et.editor', 'range'=>'et.exsrange',
				'startDate'=>'et.startdate', 'endDate'=>'et.enddate', 'source'=>'et.source', 'sourceIdentifier'=>'et.sourceIdentifier', 'titleNotes'=>'et.notes AS titleNotes');
		if(!$titleOnly){
			$sqlInsert = 'INNER JOIN omexsiccatinumbers en ON et.ometid = en.ometid ';
			$fieldArr['exsiccatiNumber'] = 'en.exsnumber';
			if($collId || $specimenOnly){
				$sqlInsert .= 'INNER JOIN omexsiccatiocclink ol ON en.omenid = ol.omenid INNER JOIN omoccurrences o ON ol.occid = o.occid ';
				if($imagesOnly) $sqlInsert .= 'INNER JOIN images i ON o.occid = i.occid ';
				if($collId) $sqlWhere .= 'AND o.collid = '.$collId.' ';
				$fieldArr['occid'] = 'o.occid';
				$fieldArr['catalogNumber'] = 'o.catalognumber';
				$fieldArr['otherCatalogNumbers'] = 'o.othercatalognumbers';
				$fieldArr['occurrenceSourceId_dbpk'] = 'o.dbpk';
				$fieldArr['collector'] = 'o.recordedby';
				$fieldArr['collectorNumber'] = 'o.recordnumber';
				$fieldArr['occurrenceNotes'] = 'ol.notes AS occurrenceNotes';
				$refUrl = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $refUrl = "https://";
				$refUrl .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80 && $_SERVER['SERVER_PORT'] != 443) $refUrl .= ':'.$_SERVER["SERVER_PORT"];
				$refUrl .= $GLOBALS['CLIENT_ROOT'].'/collections/individual/index.php?occid=';
				$fieldArr['referenceUrl'] = 'CONCAT("'.$refUrl.'",o.occid) as referenceUrl';
			}
		}
		if($searchTerm){
			$sqlWhere .= 'AND (et.title LIKE "%'.$searchTerm.'%" OR et.abbreviation LIKE "%'.$searchTerm.'%" OR et.editor LIKE "%'.$searchTerm.'%") ';
		}
		$sql = 'SELECT '.implode(',',$fieldArr).' FROM omexsiccatititles et '.$sqlInsert;
		if($sqlWhere) $sql .= 'WHERE '.substr($sqlWhere,3);
		$sql .= 'ORDER BY et.title';
		if(!$titleOnly) $sql .= ', en.exsnumber+0';
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$out = fopen('php://output', 'w');
			fputcsv($out, array_keys($fieldArr));
			while($r = $rs->fetch_assoc()){
				foreach($r as $k => $v){
					if($v) $v = mb_convert_encoding($v, 'ISO-8859-1', $GLOBALS['CHARSET']);
					$r[$k] = $v;
				}
				fputcsv($out, $r);
			}
			fclose($out);
		}
		else{
			echo "Recordset is empty.\n";
		}
		$rs->free();
	}

	//Exsiccati edit functions
	public function addTitle($pArr,$editedBy){
		$statusStr = '';
		$sql = 'INSERT INTO omexsiccatititles(title, abbreviation, editor, exsrange, startdate, enddate, source, sourceIdentifier, notes,lasteditedby) '.
			'VALUES("'.$this->cleanInStr($pArr['title']).'",'.
			($pArr['abbreviation']?'"'.$this->cleanInStr($pArr['abbreviation']).'"':'NULL').','.
			($pArr['editor']?'"'.$this->cleanInStr($pArr['editor']).'"':'NULL').','.
			($pArr['exsrange']?'"'.$this->cleanInStr($pArr['exsrange']).'"':'NULL').','.
			($pArr['startdate']?'"'.$this->cleanInStr($pArr['startdate']).'"':'NULL').','.
			($pArr['enddate']?'"'.$this->cleanInStr($pArr['enddate']).'"':'NULL').','.
			($pArr['source']?'"'.$this->cleanInStr($pArr['source']).'"':'NULL').','.
			($pArr['sourceidentifier']?'"'.$this->cleanInStr($pArr['sourceidentifier']).'"':'NULL').','.
			($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').',"'.
			$editedBy.'")';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR adding title: '.$this->conn->error;
		}
		return $statusStr;
	}

	public function editTitle($pArr,$editedBy){
		$statusStr = '';
		$sql = 'UPDATE omexsiccatititles '.
			'SET title = "'.$this->cleanInStr($pArr['title']).'"'.
			', abbreviation = '.($pArr['abbreviation']?'"'.$this->cleanInStr($pArr['abbreviation']).'"':'NULL').
			', editor = '.($pArr['editor']?'"'.$this->cleanInStr($pArr['editor']).'"':'NULL').
			', exsrange = '.($pArr['exsrange']?'"'.$this->cleanInStr($pArr['exsrange']).'"':'NULL').
			', startdate = '.($pArr['startdate']?'"'.$this->cleanInStr($pArr['startdate']).'"':'NULL').
			', enddate = '.($pArr['enddate']?'"'.$this->cleanInStr($pArr['enddate']).'"':'NULL').
			', source = '.($pArr['source']?'"'.$this->cleanInStr($pArr['source']).'"':'NULL').
			', sourceIdentifier = '.($pArr['sourceidentifier']?'"'.$this->cleanInStr($pArr['sourceidentifier']).'"':'NULL').
			', notes = '.($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').' '.
			', lasteditedby = "'.$editedBy.'" '.
			'WHERE (ometid = '.$pArr['ometid'].')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR adding title: '.$this->conn->error;
		}
		return $statusStr;
	}

	public function deleteTitle($ometid){
		$statusStr = '';
		if($ometid && is_numeric($ometid)){
			$sql = 'DELETE FROM omexsiccatititles WHERE (ometid = '.$ometid.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR deleting exsiccate: '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function mergeTitles($ometid,$targetOmetid){
		$statusStr = '';
		if(is_numeric($ometid) && is_numeric($targetOmetid)){
			//Transfer omexsiccatinumbers that can be transferred (e.g. exsnumbers not yet existing for target exsiccati titles)
			$sql = 'UPDATE IGNORE omexsiccatinumbers SET ometid = '.$targetOmetid.' WHERE ometid = '.$ometid;
			if(!$this->conn->query($sql)){
				//$statusStr = 'ERROR transferring exsiccatae: '.$this->conn->error;
			}

			//Remap omexsiccatiocclink that are still linked to old exsiccati title
			$sql = 'UPDATE IGNORE omexsiccatiocclink o INNER JOIN omexsiccatinumbers n1 ON o.omenid = n1.omenid '.
				'INNER JOIN omexsiccatinumbers n2 ON n1.exsnumber = n2.exsnumber '.
				'SET o.omenid = n2.omenid '.
				'WHERE n1.ometid = '.$ometid.' AND n2.ometid = '.$targetOmetid;
			if(!$this->conn->query($sql)){
				//$statusStr = 'ERROR remapping exsiccatae numbers: '.$this->conn->error;
			}

			//DELETE omexsiccatinumbers for old ometids with no linked occurrences
			$sql = 'DELETE n.* FROM omexsiccatinumbers n LEFT JOIN omexsiccatiocclink o ON n.omenid = o.omenid WHERE o.omenid IS NULL AND n.ometid = '.$ometid;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR deleting omexsiccatinumbers: '.$this->conn->error;
			}

			//DELETE omexsiccatinumbers for old ometids with no linked occurrences
			$sql = 'DELETE FROM omexsiccatititles WHERE ometid = '.$ometid;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR deleting omexsiccatititles: '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function addNumber($pArr){
		$statusStr = '';
		if(is_numeric($pArr['ometid'])){
			$sql = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber,notes) '.
				'VALUES('.$pArr['ometid'].',"'.$this->cleanInStr($pArr['exsnumber']).'",'.($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').')';
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR adding exsiccati number: '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function editNumber($pArr){
		$statusStr = '';
		if(is_numeric($pArr['omenid'])){
			$sql = 'UPDATE omexsiccatinumbers '.
				'SET exsnumber = "'.$this->cleanInStr($pArr['exsnumber']).'",'.
				'notes = '.($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').' '.
				'WHERE (omenid = '.$pArr['omenid'].')';
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR editing exsiccati number: '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function deleteNumber($omenid){
		$statusStr = '';
		if(is_numeric($omenid)){
			$sql = 'DELETE FROM omexsiccatinumbers WHERE (omenid = '.$omenid.')';
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR deleting exsiccati number: possibly due to linked occurrences reocrds. Delete all occurrence records and then you should be able to delete this number.';
			}
		}
		return $statusStr;
	}

	public function transferNumber($omenid, $targetOmetid){
		$retStr = '';
		if(is_numeric($omenid) && is_numeric($targetOmetid)){
			//Check to see if a matching omexsiccatinumbers exists
			$sql = 'SELECT n1.omenid '.
				'FROM omexsiccatinumbers n1 INNER JOIN omexsiccatinumbers n2 ON n1.exsnumber = n2.exsnumber '.
				'WHERE n1.ometid = '.$targetOmetid.' AND n2.omenid = '.$omenid;
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				//Remap omexsiccatiocclink to existing omexsiccatinumbers
				$sql1 = 'UPDATE IGNORE omexsiccatiocclink SET omenid = '.$r->omenid.' WHERE omenid = '.$omenid;
				$this->conn->query($sql1);

				//DELETE omexsiccatinumbers for old omenid, given that there are no linked occurrences
				$sql2 = 'DELETE n.* FROM omexsiccatinumbers n LEFT JOIN omexsiccatiocclink o ON n.omenid = o.omenid WHERE o.omenid IS NULL AND n.omenid = '.$omenid;
				if(!$this->conn->query($sql2)){
					$retStr = 'ERROR deleting omexsiccatinumber: '.$this->conn->error;
				}
			}
			else{
				//Transfer omexsiccatinumber
				$sql1 = 'UPDATE omexsiccatinumbers SET ometid = '.$targetOmetid.' WHERE omenid = '.$omenid;
				echo $sql1;
				if(!$this->conn->query($sql1)){
					$retStr = 'ERROR transferring omexsiccatinumber: '.$this->conn->error;
				}
			}
		}
		return $retStr;
	}

	public function addOccLink($pArr){
		$retStr = '';
		$collId = $pArr['occaddcollid'];
		if($collId && $pArr['omenid'] && is_numeric($pArr['omenid'])){
			$ranking = 10;
			if($pArr['ranking'] && is_numeric($pArr['ranking'])) $ranking = $pArr['ranking'];
			$identifier = $pArr['identifier'];
			if($collId == 'occid' && $identifier && is_numeric($identifier)){
				//occid being supplied within identifier field (catalog number field)
				$sql = 'INSERT INTO omexsiccatiocclink(omenid,occid,ranking,notes) '.
					'VALUES ('.$pArr['omenid'].','.$identifier.','.$ranking.','.($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').')';
				if(!$this->conn->query($sql)){
					$retStr = 'ERROR linking occurrence to exsiccati number, SQL: '.$sql;
				}
			}
			elseif($collId && is_numeric($collId) && ($identifier || ($pArr['recordedby'] && $pArr['recordnumber']))){
				//Grab matching occid(s)
				$sql1 = 'SELECT o.occid '.
					'FROM omoccurrences o LEFT JOIN omoccurrencesfulltext f ON o.occid = f.occid '.
					'WHERE o.collid = '.$collId.' ';
				if($identifier){
					$sql1 .= 'AND (o.catalogNumber = '.(is_numeric($identifier)?$identifier:'"'.$identifier.'"').') ';
				}
				else{
					if(strlen($pArr['recordedby']) < 4 || in_array(strtolower($pArr['recordedby']),array('best','little'))){
						//Need to avoid FULLTEXT stopwords interfering with return
						$sql1 .= 'AND (o.recordedby LIKE "%'.$pArr['recordedby'].'%")';
					}
					else{
						$sql1 .= 'AND (MATCH(f.recordedby) AGAINST("'.$pArr['recordedby'].'")) ';
					}
					$sql1 .= 'AND (o.recordnumber = '.(is_numeric($pArr['recordnumber'])?$pArr['recordnumber']:'"'.$pArr['recordnumber'].'"').') ';
				}
				$sql1 .= 'LIMIT 5';
				//echo $sql1;
				$rs = $this->conn->query($sql1);
				$cnt = 0;
				while($r = $rs->fetch_object()){
					$sql = 'INSERT INTO omexsiccatiocclink(omenid,occid,ranking,notes) '.
						'VALUES('.$pArr['omenid'].', '.$r->occid.', '.$ranking.','.($pArr['notes']?'"'.$this->cleanInStr($pArr['notes']).'"':'NULL').')';
					if($this->conn->query($sql)){
						$cnt++;
					}
					else{
						$retStr = 'ERROR linking occurrence to exsiccati number, SQL: '.$this->conn->error;
					}
				}
				$rs->free();
				if($cnt) $retStr = 'SUCCESS: '.$cnt.' recorded loaded successfully';
				else{
					if($retStr){
						if(strpos($retStr,'Duplicate entry') !== false) $retStr = 'FAILED: occurrence record already linked to another exsiccate number';
					}
					else  $retStr = 'FAILED: no occurrence records located matching criteria';
				}
			}
		}
		else{
			$retStr = 'FAILED: criteria may have not been complete';
		}
		return $retStr;
	}

	public function editOccLink($pArr){
		$statusStr = '';
		if(is_numeric($pArr['omenid']) && is_numeric($pArr['occid']) && is_numeric($pArr['ranking'])){
			$sql = 'UPDATE omexsiccatiocclink '.
				'SET ranking = '.$pArr['ranking'].', notes = "'.$this->cleanInStr($pArr['notes']).'" '.
				'WHERE (omenid = '.$pArr['omenid'].') AND (occid = '.$pArr['occid'].')';
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR editing occurrence link: '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function deleteOccLink($omenid, $occid){
		$statusStr = '';
		if(is_numeric($omenid) && is_numeric($occid)){
			$sql = 'DELETE FROM omexsiccatiocclink WHERE (omenid = '.$omenid.') AND (occid = '.$occid.')';
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR deleting occurrence link: '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function transferOccurrence($omenid, $occid, $targetOmetid, $targetExsNumber){
		$statusStr = '';
		if(is_numeric($omenid) && is_numeric($targetOmetid) && $targetExsNumber){
			//Lookup omenid
			$targetOmenid = 0;
			$sql = 'SELECT omenid FROM omexsiccatinumbers WHERE ometid = '.$targetOmetid.' AND exsnumber = "'.$this->cleanInStr($targetExsNumber).'"';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$targetOmenid = $r->omenid;
			}
			else{
				//Create new omexsiccatinumber record and the transfer
				$sql1 = 'INSERT INTO omexsiccatinumbers(ometid, exsnumber) VALUES('.$targetOmetid.',"'.$this->cleanInStr($targetExsNumber).'") ';
				if($this->conn->query($sql1)){
					$targetOmenid = $this->conn->insert_id;
				}
				else{
					$statusStr = 'ERROR inserting new omexsiccatinumbers record, SQL: '.$this->conn->error;
				}
			}
			$rs->free();
			if($targetOmenid){
				//Transfer record
				$sql2 = 'UPDATE omexsiccatiocclink SET omenid = '.$targetOmenid.' WHERE occid = '.$occid.' AND omenid = '.$omenid;
				if(!$this->conn->query($sql2)){
					$statusStr = 'ERROR tranferring occurrence: '.$this->conn->error;
				}
			}
			else{
				$statusStr = 'ERROR looking up omenid while trying to transfer occurrence';
			}
		}
		return $statusStr;
	}

	//Batch transfer functions
	public function batchImport($targetCollid,$postArr){
		$statusStr = '';
		$transferCnt = 0;
		if(array_key_exists('occid[]',$postArr)){
			$datasetId = '';
			if(array_key_exists('dataset',$postArr) && $postArr['dataset']){
				//Create new dataset to link all new records
				$sqlDs = 'INSERT INTO omoccurdatasets(name, uid) VALUES("'.$this->cleanInStr($postArr['dataset']).'",'.$GLOBALS['SYMB_UID'].') ';
				if($this->conn->query($sqlDs)){
					$datasetId = $this->conn->insert_id;
				}
				else{
					$statusStr = 'ERROR creating dataset, '.$this->conn->error;
					//$statusStr .= '<br/>SQL: '.$sqlDs;
				}
			}
			//Transfer records
			$occidArr = $postArr['occid[]'];
			$targetFieldArr = $this->getTargetFields();
			$sqlBase = 'INSERT INTO omoccurrences('.implode(',',$targetFieldArr).',collid, catalognumber, dateEntered) '.
				'SELECT '.implode(',',$targetFieldArr).',';
			foreach($occidArr as $occid){
				if(is_numeric($occid)){
					$catNum = $this->cleanInStr($postArr['cat-'.$occid]);
					$sql1 = $sqlBase.$targetCollid.', "'.$catNum.'", "'.date('Y-m-d H:i:s').'" AS dateEntered FROM omoccurrences WHERE occid = '.$occid;
					if($this->conn->query($sql1)){
						$transferCnt++;
						//Add new record to exsiccati index
						$newOccid = $this->conn->insert_id;
						if($newOccid){
							$sql2 = 'INSERT INTO omexsiccatiocclink(omenid,occid) SELECT omenid, occid FROM omexsiccatiocclink WHERE occid = '.$newOccid;
							if(!$this->conn->query($sql2)){
								$statusStr = 'ERROR linking new record to exsiccati: '.$this->conn->error;
								//$statusStr .= '<br/>SQL: '.$sql2;
							}
						}
						if($datasetId){
							//Add to dataset
							$sql3 = 'INSERT INTO omoccurdatasetlink(occid,datasetid) VALUES('.$newOccid.','.$datasetId.') ';
							if(!$this->conn->query($sql3)){
								$statusStr = 'ERROR add new record to dataset: '.$this->conn->error;
								//$statusStr .= '<br/>SQL: '.$sql3;
							}
						}
					}
					else{
						$statusStr .= '<b/>ERROR transferring record #'.$occid.': '.$this->conn->error;
						//$statusStr .= '<br/>SQL: '.$sql1;
					}
				}
			}
		}
		if($transferCnt){
			$statusStr = 'SUCCESS transferring '.$transferCnt.' records ';
			//if($datasetId) $statusStr = '<br/>Records linked to dataset: <a href="">' . htmlspecialchars($datasetTitle, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
		}
		return $statusStr;
	}

	public function exportAsCsv($postArr){
		if(array_key_exists('occid[]',$postArr)){
			$fieldArr = $this->getTargetFields();
			$occidArr = array_flip($postArr['occid[]']);
			$fileName = 'exsiccatiOutput_'.time().'.csv';
			header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header ('Content-Type: text/csv');
			header ('Content-Disposition: attachment; filename="'.$fileName.'"');
			$sql = 'SELECT '.implode(',',$fieldArr).', occid FROM omoccurrences WHERE occid IN('.implode(',',$occidArr).') ';
			$rs = $this->conn->query($sql);
			if($rs->num_rows){
				$out = fopen('php://output', 'w');
				array_unshift($fieldArr,'catalogNumber');
				array_push($fieldArr,'occid');
				echo implode(',',$fieldArr)."\n";
				while($r = $rs->fetch_assoc()){
					array_unshift($r,$occidArr[$r->occid]);
					fputcsv($out, $r);
				}
				fclose($out);
			}
			else{
				echo "Recordset is empty.\n";
			}
			$rs->free();
		}
	}

	private function getTargetFields(){
		$fieldArr = array();
		$skipFields = array('occid','collid','dbpk','ownerinstitutioncode','institutionid','collectionid','datasetid','institutioncode','collectioncode',
			'occurrenceid', 'catalognumber', 'othercatalognumbers','previousidentifications', 'taxonremarks', 'identifiedby', 'dateidentified',
			'identificationreferences', 'identificationremarks', 'recordedbyid', 'informationwithheld', 'associatedoccurrences', 'datageneralizations',
			'dynamicproperties', 'verbatimcoordinatesystem', 'storagelocation', 'disposition', 'genericcolumn1', 'genericcolumn2', 'modified',
			'observeruid', 'processingstatus', 'recordenteredby', 'duplicatequantity', 'labelproject', 'dateentered', 'datelastmodified',
			'initialtimestamp');
		$sql = "SHOW COLUMNS FROM uploadspectemp";
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$field = strtolower($r->Field);
			if(!in_array($field, $skipFields)){
				$fieldArr[] = $field;
			}
		}
		$rs->free();
		return $fieldArr;
	}

	//Select form lookup functions
	public function getSelectLookupArr(){
		$retArr = array();
		$sql = 'SELECT ometid, IFNULL(abbreviation,title) AS titleStr, exsrange FROM omexsiccatititles ORDER BY titleStr';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$titleStr = $r->titleStr;
				if($r->exsrange) $titleStr .= ' ['.$r->exsrange.']';
				$retArr[$r->ometid] = $this->cleanOutStr($titleStr).' (#'.$r->ometid.')';
			}
			$rs->free();
		}
		return $retArr;
	}

	//AJAX function used in exsiccati suggest associated with editor
	public function getExsiccatiSuggest($term){
		$retArr = Array();
		$queryString = $this->cleanInStr($term);
		$sql = 'SELECT DISTINCT ometid, title, abbreviation, exsrange FROM omexsiccatititles '.
			'WHERE title LIKE "%'.$queryString.'%" OR abbreviation LIKE "%'.$queryString.'%" ORDER BY title';
		$rs = $this->conn->query($sql);
		$cnt = 0;
		while ($r = $rs->fetch_object()) {
			//$retArr[] = '"id": '.$r->ometid.',"value":"'.str_replace('"',"''",$r->title.($r->abbreviation?' ['.$r->abbreviation.']':'')).'"';
			$retArr[$cnt]['id'] = $r->ometid;
			$retArr[$cnt]['value'] = $r->title.($r->exsrange?' ['.$r->exsrange.']':'').($r->abbreviation?'; '.$r->abbreviation:'');
			$cnt++;
		}
		return $retArr;
	}

	public function getExsAbbrevSuggest($term){
		$retArr = Array();
		$queryString = $this->cleanInStr($term);
		$sql = 'SELECT DISTINCT ometid, abbreviation, exsrange FROM omexsiccatititles WHERE abbreviation LIKE "%'.$queryString.'%" ORDER BY title';
		$rs = $this->conn->query($sql);
		$cnt = 0;
		while ($r = $rs->fetch_object()) {
			$retArr[$cnt]['id'] = $r->ometid;
			$retArr[$cnt]['value'] = $r->abbreviation.($r->exsrange?' ['.$r->exsrange.']':'');
			$cnt++;
		}
		return $retArr;
	}

	//Misc
	public function getCollArr($ometid = 0){
		$retArr = array();
		$sql ='SELECT DISTINCT c.collid, c.collectionname, c.institutioncode, c.collectioncode FROM omcollections c ';
		if($ometid){
			if($ometid == 'all'){
				$sql .= 'INNER JOIN omoccurrences o ON c.collid = o.collid '.
					'INNER JOIN omexsiccatiocclink ol ON o.occid = ol.occid ';
			}
			elseif(is_numeric($ometid)){
				$sql .= 'INNER JOIN omoccurrences o ON c.collid = o.collid '.
					'INNER JOIN omexsiccatiocclink ol ON o.occid = ol.occid '.
					'INNER JOIN omexsiccatinumbers en ON ol.omenid = en.omenid ';
			}
		}
		$sql .= 'WHERE (colltype != "General Observations") ';
		if($ometid && is_numeric($ometid)){
			$sql .= 'AND (en.ometid = '.$ometid.') ';
		}
		$sql .= 'ORDER BY c.collectionname, c.institutioncode';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname.' ('.$r->institutioncode.($r->collectioncode?' - '.$r->collectioncode:'').')';
		}
		$rs->free();
		return $retArr;
	}

	public function getTargetCollArr(){
		global $USER_RIGHTS;
		$retArr = array();
		$collArr = array();
		if(isset($USER_RIGHTS['CollAdmin'])){
			$collArr = $USER_RIGHTS['CollAdmin'];
		}
		if(isset($USER_RIGHTS['CollEditor'])){
			$collArr = array_merge($collArr, $USER_RIGHTS['CollEditor']);
		}
		if($collArr){
			$sql ='SELECT DISTINCT c.collid, c.collectionname, c.institutioncode, c.collectioncode '.
				'FROM omcollections c '.
				'WHERE (colltype != "Preserved Specimens") '.
				'ORDER BY c.collectionname, c.institutioncode';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->collid] = $r->collectionname.' ('.$r->institutioncode.($r->collectioncode?' - '.$r->collectioncode:'').')';
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getExsTableRow($occid,$oArr,$omenid,$targetCollid){
		$isTarget = false;
		if($targetCollid == $oArr['collid']) $isTarget = true;
		$retStr = '<tr>';
		$retStr .= '<td align="center">';
		$retStr .= '<input id="'.$occid.'" name="occid[]" type="checkbox" value="'.$occid.'" '.($isTarget?'disabled':'').' />';
		$retStr .= '</td>';
		$retStr .= '<td align="center">';
		if($isTarget){
			$retStr .= '<span style="color:red;"><b>Cannot Import</b><br/>Is Target Collection</span>';
		}
		else{
			$retStr .= '<input name="cat-'.$occid.'" type="text" onchange="checkRecord(this,'.$occid.')" />';
		}
		$retStr .= '</td>';
		$retStr .= '<td align="center"><a href="#" onclick="openExsPU('.$omenid.')">#'.$oArr['exsnum'].'</a></td>';
		$retStr .= '<td>';
		$retStr .= '<span '.($isTarget?'style="color:red;"':'').' title="'.$oArr['collname'].'">'.$oArr['collcode'].'</span>, ';
		$retStr .= '<a href="#" onclick="openIndPU(' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ')">' . htmlspecialchars($oArr['recby'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . htmlspecialchars(($oArr['recnum']?$oArr['recnum']:'s.n.'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
		$retStr .= ($oArr['eventdate']?', '.$oArr['eventdate']:'');
		$retStr .= ', <i>'.$oArr['sciname'].'</i> '.$oArr['author'];
		$retStr .= $oArr['country'].', '.$oArr['state'].', '.$oArr['county'].', '.(strlen($oArr['locality'])>75?substr($oArr['locality'],0,75).'...':$oArr['locality']);
		if($oArr['lat']) $retStr .= ', '.$oArr['lat'].' '.$oArr['lng'];
		if($oArr['notes']) $retStr .= ', <b>'.$oArr['notes'].'</b>';
		$retStr .= '</td>';
		$retStr .= '</tr>';
		return $retStr;
	}

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>