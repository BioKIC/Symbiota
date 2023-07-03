<?php
include_once('TPEditorManager.php');

class TPDescEditorManager extends TPEditorManager{

 	public function __construct(){
 		parent::__construct();
 	}

 	public function __destruct(){
 		parent::__destruct();
 	}

	public function getDescriptions(){
		$descrArr = Array();
		$langArr = false;
		$sql = 'SELECT p.tdProfileID, IFNULL(d.caption, p.caption) as caption, IFNULL(d.source, p.publication) AS source, IFNULL(d.sourceurl, p.urlTemplate) AS sourceurl,
			IFNULL(d.displaylevel, p.defaultDisplayLevel) AS displaylevel, t.tid, t.sciname, d.tdbid, d.notes, l.langid, d.language
			FROM taxadescrprofile p INNER JOIN taxadescrblock d ON p.tdProfileID = d.tdProfileID
			LEFT JOIN adminlanguages l ON p.langid = l.langid ';
		if($this->acceptance){
			$sql .= 'INNER JOIN taxa t ON d.tid = t.tid
				INNER JOIN taxstatus ts ON t.tid = ts.tid
				WHERE (ts.TidAccepted = '.$this->tid.') AND (ts.taxauthid = '.$this->taxAuthId.') ';
		}
		else{
			$sql .= 'INNER JOIN taxa t ON d.tid = t.tid WHERE (d.tid = '.$this->tid.') ';
		}
		$sql .= 'ORDER BY p.defaultDisplayLevel, d.displayLevel ';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$langID = $r->langid;
				if(!$langID){
					if($langArr === false) $langArr = $this->getLangMap();
					if(array_key_exists($r->language, $langArr)) $langID = $langArr[$r->language];
					else $langID = 1;
				}
				$descrArr[$langID][$r->tdbid]['tdProfileID'] = $r->tdProfileID;
				$descrArr[$langID][$r->tdbid]['caption'] = $r->caption;
				$descrArr[$langID][$r->tdbid]['source'] = $r->source;
				$descrArr[$langID][$r->tdbid]['sourceurl'] = $r->sourceurl;
				$descrArr[$langID][$r->tdbid]['displaylevel'] = $r->displaylevel;
				$descrArr[$langID][$r->tdbid]['notes'] = $r->notes;
				$descrArr[$langID][$r->tdbid]['tid'] = $r->tid;
				$descrArr[$langID][$r->tdbid]['sciname'] = $r->sciname;
			}
			$rs->free();
		}
		else{
			trigger_error('Unable to get descriptions; '.$this->conn->error);
		}
		foreach($descrArr as $langId => $dArr){
			foreach($dArr as $tdbid => $d2Arr){
				$sql2 = 'SELECT tdbid, tdsid, heading, statement, notes, displayheader, sortsequence FROM taxadescrstmts WHERE (tdbid = '.$tdbid.') ORDER BY sortsequence';
				if($rs2 = $this->conn->query($sql2)){
					while($r2 = $rs2->fetch_object()){
						$descrArr[$langId][$tdbid]['stmts'][$r2->tdsid]['heading'] = $r2->heading;
						$descrArr[$langId][$tdbid]['stmts'][$r2->tdsid]['statement'] = $r2->statement;
						$descrArr[$langId][$tdbid]['stmts'][$r2->tdsid]['notes'] = $r2->notes;
						$descrArr[$langId][$tdbid]['stmts'][$r2->tdsid]['displayheader'] = $r2->displayheader;
						$descrArr[$langId][$tdbid]['stmts'][$r2->tdsid]['sortsequence'] = $r2->sortsequence;
					}
					$rs2->free();
				}
				else{
					trigger_error('Unable to get statements; '.$this->conn->error);
				}
			}
		}
		return $descrArr;
	}

	public function insertDescriptionProfile($postArr){
		$status = false;
		if(isset($postArr['title']) && isset($postArr['caption'])){
			$title =  $postArr['title'];
			$caption = $postArr['caption'];
			$authors = isset($postArr['author']) ? $postArr['author'] : null;
			$projectDescription = isset($postArr['projectDescription']) ? $postArr['projectDescription'] : null;
			$abstract = isset($postArr['abstract']) ? $postArr['abstract'] : null;
			$publication = isset($postArr['publication']) ? $postArr['publication'] : null;
			$urlTemplate = isset($postArr['urlTemplate']) ? $postArr['urlTemplate'] : null;
			$internalNotes = isset($postArr['internalNotes']) ? $postArr['internalNotes'] : null;
			$langid = isset($postArr['langid']) ? $postArr['langid'] : 1;
			$defaultDisplayLevel = isset($postArr['defaultDisplayLevel']) ? $postArr['defaultDisplayLevel'] : 1;
			$dynamicProperties = isset($postArr['dynamicProperties']) ? $postArr['dynamicProperties'] : null;
			$modifiedUid = $GLOBALS['SYMB_UID'];
			$sql = 'INSERT INTO taxadescrprofile(title, authors, caption, projectDescription, abstract, publication, urlTemplate, internalNotes, langid,
				defaultDisplayLevel, dynamicProperties, modifiedUid, modifiedTimestamp)
				VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('ssssssssiisi', $title, $authors, $caption, $projectDescription, $abstract, $publication, $urlTemplate, $internalNotes, $langid,
					$defaultDisplayLevel, $dynamicProperties, $modifiedUid);
				$stmt->execute();
				if($stmt->affected_rows) $status = $stmt->insert_id;
				elseif($stmt->error) $this->errorMessage = $stmt->error;
				$stmt->close();
			}
		}
		return $status;
	}

	public function updateDescriptionProfile($postArr){
		$status = false;
		if(is_numeric($postArr['tdProfileID'])){
			$profileFieldArr = array('title' => 's', 'caption' => 's', 'author' => 's', 'projectDescription' => 's', 'abstract' => 's', 'publication' => 's', 'urlTemplate' => 's',
				'internalNotes' => 's', 'langid' => 'i', 'defaultDisplayLevel' => 'i', 'dynamicProperties' => 's'
			);
			$sqlFrag = '';
			$paramArr = array();
			$paramType = '';
			foreach($postArr as $postName => $postValue){
				if(array_key_exists($postName, $profileFieldArr)){
					if(($postName == 'title' || $postName == 'caption') && !$postValue) continue;
					$sqlFrag .= $postName.' = ?, ';
					$paramType .= $profileFieldArr[$postName];
					if($postValue) $paramArr[] = $postValue;
					else $paramArr[] = null;
				}
			}
			if($paramArr){
				$paramArr[] = $GLOBALS['SYMB_UID'];
				$paramArr[] = $postArr['tdProfileID'];
				$paramType .= 'ii';
				$sql = 'UPDATE taxadescrprofile SET ' . $sqlFrag . ' modifiedUid = ?, modifiedTimestamp = NOW() WHERE (tdProfileID = ?)';
				if($stmt = $this->conn->prepare($sql)){
					$stmt->bind_param($paramType, ...$paramArr);
					$stmt->execute();
					if($stmt->affected_rows) $status = true;
					elseif($stmt->error) $this->errorMessage = $stmt->error;
					$stmt->close();
				}
				echo $this->conn->error;
			}
		}
		return $status;
	}

	public function insertDescriptionBlock($postArr){
		$status = false;
		if(is_numeric($postArr['tid']) && isset($postArr['caption'])){
			$profileArr = array('title' => $postArr['caption'], 'caption' => $postArr['caption']);
			$source = null;
			$sourceUrl = null;
			$displayLevel = 1;
			if($postArr['source']){
				$profileArr['publication'] = $postArr['source'];
				$source = $postArr['source'];
			}
			if($postArr['sourceurl']){
				$profileArr['urlTemplate'] = $postArr['sourceurl'];
				$sourceUrl = $postArr['sourceurl'];
			}
			if($postArr['displaylevel'] && is_numeric($postArr['displaylevel'])){
				$profileArr['defaultDisplayLevel'] = $postArr['displaylevel'];
				$displayLevel = $postArr['displaylevel'];
			}
			if($postArr['langid'] && is_numeric($postArr['langid'])) $profileArr['langid'] = $postArr['langid'];
			if($tdProfileID = $this->insertDescriptionProfile($profileArr)){
				$tid = $postArr['tid'];
				$note = isset($postArr['notes']) ? $postArr['notes'] : null;
				$modifiedUid = $GLOBALS['SYMB_UID'];
				$sql = 'INSERT INTO taxadescrblock(tdProfileID, tid, source, sourceurl, displaylevel, notes, uid) VALUES(?, ?, ?, ?, ?, ?, ?)';
				if($stmt = $this->conn->prepare($sql)){
					$stmt->bind_param('iissisi', $tdProfileID, $tid, $source, $sourceUrl, $displayLevel, $note, $modifiedUid);
					$stmt->execute();
					if($stmt->affected_rows) $status = $stmt->insert_id;
					elseif($stmt->error) $this->errorMessage = $stmt->error;
					$stmt->close();
				}
			}
		}
		return $status;
	}

	public function updateDescriptionBlock($postArr){
		$status = false;
		if(is_numeric($postArr['tdbid'])){
			$blockFieldArr = array('source' => 's', 'sourceurl' => 's', 'displaylevel' => 'i', 'notes' => 's' );
			$sqlFrag = '';
			$paramArr = array();
			$paramType = '';
			foreach($postArr as $postName => $postValue){
				if(array_key_exists($postName, $blockFieldArr)){
					$sqlFrag .= $postName.' = ?, ';
					$paramType .= $blockFieldArr[$postName];
					if($postValue) $paramArr[] = $postValue;
					else $paramArr[] = null;
				}
			}
			if($paramArr){
				$paramArr[] = $postArr['tdbid'];
				$paramType .= 'i';
				$sql = 'UPDATE taxadescrblock SET ' . trim($sqlFrag, ', ') . ' WHERE (tdbid = ?)';
				if($stmt = $this->conn->prepare($sql)){
					if($stmt->bind_param($paramType, ...$paramArr)){
						$stmt->execute();
						if($stmt->affected_rows) $status = true;
						elseif($stmt->error) $this->errorMessage = $stmt->error;
						$stmt->close();
					}
					else echo 'error binding: '.$stmt->error.'<br>';
				}
				else echo 'error preparing statement: '.$this->conn->error.'<br>';
			}
			// Temp code until total refactor: transfer selected fields to decription profile
			if(isset($postArr['source'])){
				$postArr['publication'] = $postArr['source'];
			}
			if(isset($postArr['displaylevel'])){
				$postArr['defaultDisplayLevel'] = $postArr['displaylevel'];
			}
			if(isset($postArr['sourceurl'])){
				$postArr['urlTemplate'] = $postArr['sourceurl'];
			}
			if($this->updateDescriptionProfile($postArr)) $status = true;
		}
		return $status;
	}

	public function deleteDescriptionBlock($tdbid){
		$status = false;
		if(is_numeric($tdbid)){
			$sql = 'DELETE FROM taxadescrblock WHERE (tdbid = ?)';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $tdbid);
				$stmt->execute();
				if($stmt->affected_rows) $status = true;
				elseif($stmt->error) $this->errorMessage = $stmt->error;
				$stmt->close();
			}
		}
		return $status;
	}

	public function remapDescriptionBlock($tdbid){
		$status = false;
		if(is_numeric($tdbid)){
			$displayLevel = 1;
			$sql = 'SELECT max(displaylevel) as maxdl FROM taxadescrblock WHERE tid = '.$this->tid;
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()){
					$displayLevel = $r->maxdl + 1;
				}
				$rs->free();
			}
			if($this->updateDescriptionBlock(array('tdbid' => $tdbid, 'tid' => $this->tid, 'displaylevel' => $displayLevel))){
				$status = true;
			}
		}
		return $status;
	}

	public function addStatement($stArr){
		$status = false;
		$stmtStr = $this->cleanInStr($stArr['statement']);
		if(substr($stmtStr,0,3) == '<p>' && substr($stmtStr,-4) == '</p>') $stmtStr = trim(substr($stmtStr,3,strlen($stmtStr)-7));
		if($stmtStr && $stArr['tdbid'] && is_numeric($stArr['tdbid'])){
			$sql = 'INSERT INTO taxadescrstmts(tdbid,heading,statement,displayheader'.($stArr['sortsequence']?',sortsequence':'').') '.
				'VALUES('.$stArr['tdbid'].','.($stArr['heading']?'"'.$this->cleanInStr($stArr['heading']).'"':'""').',"'.$stmtStr.'",'.(array_key_exists('displayheader',$stArr)?'1':'0').
				($stArr['sortsequence']?','.$this->cleanInStr($stArr['sortsequence']):'').')';
			if($this->conn->query($sql)) $status = true;
			else $this->errorMessage = 'ERROR adding description statement: '.$this->conn->error;
		}
		return $status;
	}

	public function editStatement($stArr){
		$status = false;
		$stmtStr = $this->cleanInStr($stArr['statement']);
		if(substr($stmtStr,0,3) == '<p>' && substr($stmtStr,-4) == '</p>') $stmtStr = trim(substr($stmtStr,3,strlen($stmtStr)-7));
		if($stmtStr && $stArr['tdsid'] && is_numeric($stArr["tdsid"])){
			$sql = 'UPDATE taxadescrstmts '.
				'SET heading = '.($stArr['heading']?'"'.$this->cleanInStr($stArr['heading']).'"':'""').','.
				'statement = "'.$stmtStr.'",displayheader = '.(array_key_exists('displayheader',$stArr)?'1':'0').
				(is_numeric($stArr['sortsequence'])?',sortsequence = '.$stArr['sortsequence']:'').
				' WHERE (tdsid = '.$stArr['tdsid'].')';
			//echo $sql;
			if($this->conn->query($sql)) $status = true;
			else $this->errorMessage = "ERROR editing description statement: ".$this->conn->error;
		}
		return $status;
	}

	public function deleteStatement($tdsid){
		$status = true;
		if(is_numeric($tdsid)){
			$sql = 'DELETE FROM taxadescrstmts WHERE (tdsid = '.$tdsid.')';
			if($this->conn->query($sql)) $status = true;
			else $this->errorMessage = "ERROR deleting description statement: ".$this->conn->error;
		}
		return $status;
	}
}
?>