<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class KeyCharAdmin{

	private $conn;
	private $cid = 0;
	private $lang = 'english';
	private $langId;
	//private $langId;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if($this->conn) $this->conn->close();
	}

	public function getCharacterArr(){
		$retArr = array();
		$headingArr = array();
		$sql = 'SELECT c.cid, IFNULL(cl.charname, c.charname) AS charname, c.hid '.
			'FROM kmcharacters c LEFT JOIN (SELECT cid, charname FROM kmcharacterlang WHERE langid = "'.$this->langId.'") cl ON c.cid = cl.cid '.
			'ORDER BY c.sortsequence, cl.charname, c.charname';
		//echo $sql; exit;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$hid = ($r->hid?$r->hid:0);
				$retArr[$hid][$r->cid] = $this->cleanOutStr($r->charname);
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getCharDetails(){
		$retArr = array();
		if($this->cid){
			$sql = 'SELECT cid, charname, chartype, difficultyrank, hid, units, description, glossid, helpurl, notes, enteredby, sortsequence FROM kmcharacters WHERE cid = '.$this->cid;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['charname'] = $this->cleanOutStr($r->charname);
					$retArr['chartype'] = $r->chartype;
					$retArr['difficultyrank'] = $r->difficultyrank;
					$retArr['hid'] = $r->hid;
					$retArr['units'] = $this->cleanOutStr($r->units);
					$retArr['description'] = $this->cleanOutStr($r->description);
					$retArr['glossid'] = $r->glossid;
					$retArr['helpurl'] = $r->helpurl;
					$retArr['notes'] = $this->cleanOutStr($r->notes);
					$retArr['enteredby'] = $r->enteredby;
					$retArr['sortsequence'] = $r->sortsequence;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function createCharacter($pArr,$un){
		$statusStr = 'SUCCESS: character added to database';
		$dRank = $this->cleanInStr($pArr['difficultyrank']);
		if(!$dRank) $dRank = 1;
		$hid = $this->cleanInStr($pArr['hid']);
		if(!$hid) $hid = 'NULL';
		$sql = 'INSERT INTO kmcharacters(charname,chartype,difficultyrank,hid,enteredby,sortsequence) '.
			'VALUES("'.$this->cleanInStr($pArr['charname']).'","'.$this->cleanInStr($pArr['chartype']).'",'.
			$dRank.','.$hid.',"'.$un.'",'.(is_numeric($pArr['sortsequence'])?$pArr['sortsequence']:1000).') ';
		//echo $sql;
		if($this->conn->query($sql)){
			$this->cid = $this->conn->insert_id;
			if(($pArr['chartype'] == 'IN') || ($pArr['chartype'] == 'RN')){
				//If new character is a numeric type, automatically load character sets with set values
				$sql2 = 'INSERT INTO kmcs(cid,cs,charstatename) '.
					'VALUES('.$this->cid.',"+High","Upper value of unspecified range (could be µ+s.d., but not known)"),'.
					'('.$this->cid.',"-Low","Lower value of unspecified range (could be µ-s.d., but not known)"),'.
					'('.$this->cid.',"Max","Maximum value"),'.
					'('.$this->cid.',"Mean","Mean (= average)"),'.
					'('.$this->cid.',"Min","Minimum value")';
				if(!$this->conn->query($sql2)){
					trigger_error('unable to load numeric character set values; '.$this->conn->error);
					$statusStr = 'unable to load numeric character set values; '.$this->conn->error;
				}
			}
		}
		else{
			trigger_error('Creation of new character failed; '.$this->conn->error);
			$statusStr = 'ERROR: Creation of new character failed: '.$this->conn->error.'<br/>SQL: '.$sql;
		}
		return $statusStr;
	}

	public function editCharacter($pArr){
		$statusStr = '';
		$targetArr = array('charname','chartype','units','difficultyrank','hid','description','helpurl','glossid','notes','sortsequence');
		$sql = '';
		foreach($pArr as $k => $v){
			if(in_array($k,$targetArr)){
				$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
			}
		}
		$sql = 'UPDATE kmcharacters SET '.substr($sql,1).' WHERE (cid = '.$this->cid.')';
		if($this->conn->query($sql)){
			$statusStr = 'SUCCESS: information saved';
		}
		else{
			$statusStr = 'ERROR: Editing of character failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}

	public function deleteChar(){
		$status = true;

		//Delete character taxa links
		$sql = 'DELETE FROM kmchartaxalink WHERE (cid = '.$this->cid.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$status = 'ERROR deleting character taxa links: '.$this->conn->error.', '.$sql;
		}

		//Delete character dependance links
		$sql = 'DELETE FROM kmchardependance WHERE (cid = '.$this->cid.') OR (ciddependance = '.$this->cid.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$status = 'ERROR deleting character dependance links: '.$this->conn->error.', '.$sql;
		}

		//Delete language links
		$sql = 'DELETE FROM kmcharacterlang WHERE (cid = '.$this->cid.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$status = 'ERROR deleting character languages: '.$this->conn->error.', '.$sql;
		}

		//Delete characters
		$sql = 'DELETE FROM kmcharacters WHERE (cid = '.$this->cid.')';
		if(!$this->conn->query($sql)){
			$status = 'ERROR deleting descriptions linked to character: '.$this->conn->error.', '.$sql;
		}

		return $status;
	}

	public function getCharStateArr(){
		$retArr = array();
		if($this->cid){
			$sql = 'SELECT cid, cs, charstatename, implicit, notes, description, illustrationurl, glossid, sortsequence, enteredby FROM kmcs WHERE cid = '.$this->cid.' ORDER BY sortsequence';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					if(is_numeric($r->cs)){
						$retArr[$r->cs]['charstatename'] = $this->cleanOutStr($r->charstatename);
						$retArr[$r->cs]['implicit'] = $r->implicit;
						$retArr[$r->cs]['notes'] = $this->cleanOutStr($r->notes);
						$retArr[$r->cs]['description'] = $this->cleanOutStr($r->description);
						$retArr[$r->cs]['illustrationurl'] = $r->illustrationurl;
						$retArr[$r->cs]['glossid'] = $r->glossid;
						$retArr[$r->cs]['sortsequence'] = $this->cleanOutStr($r->sortsequence);
						$retArr[$r->cs]['enteredby'] = $r->enteredby;
					}
				}
				$rs->free();
			}
			else{
				trigger_error('unable to return character state array; '.$this->conn->error);
			}
			if($retArr){
				//Grab character set illustration
				$sql2 = 'SELECT cs, url, csimgid FROM kmcsimages WHERE cid = '.$this->cid.' AND cs IN ('.implode(',',array_keys($retArr)).')';
				//echo $sql2;
				$rs = $this->conn->query($sql2);
				while($r = $rs->fetch_object()){
					$retArr[$r->cs]['url'] = $r->url;
					$retArr[$r->cs]['csimgid'] = $r->csimgid;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function createCharState($postArr, $un){
		$csValue = 1;
		if($this->cid){
			//Get highest character set ID value (CS) and increase by 1
			$sql = 'SELECT cs FROM kmcs WHERE cid = '.$this->cid.' ORDER BY (cs+1) DESC ';
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()){
					if(is_numeric($r->cs)){
						$csValue = $r->cs + 1;
					}
				}
				$rs->free();
			}
			$csName = $postArr['charstatename'];
			$glossID = null;
			if(isset($postArr['glossid']) && is_numeric($postArr['glossid'])) $glossID = $postArr['glossid'];
			$description = $postArr['description'];
			$notes = $postArr['notes'];
			$sortSequence = $postArr['sortsequence'];
			$sql = 'INSERT INTO kmcs(cid,cs,charstatename,implicit,glossid,description,notes,sortsequence,enteredby) '.
				'VALUES('.$this->cid.',"'.$csValue.'","'.$this->cleanInStr($csName).'",1,'.
				($glossID?$glossID:'NULL').','.
				($description?'"'.$this->cleanInStr($description).'"':'NULL').','.
				($notes?'"'.$this->cleanInStr($notes).'"':'NULL').','.
				(is_numeric($sortSequence)?$this->cleanInStr($sortSequence):100).',"'.$un.'") ';
			//echo $sql;
			if(!$this->conn->query($sql)){
				trigger_error('ERROR: Creation of new character failed: '.$this->conn->error);
			}
		}
		return $csValue;
	}

	public function editCharState($pArr){
		$statusStr = '';
		$cs = $pArr['cs'];
		$targetArr = array('charstatename','glossid','description','notes','sortsequence');
		$sql = '';
		foreach($pArr as $k => $v){
			if(in_array($k,$targetArr)){
				$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
			}
		}
		$sql = 'UPDATE kmcs SET '.substr($sql,1).' WHERE (cid = '.$this->cid.') AND (cs = '.$cs.')';
		//echo $sql;
		if($this->conn->query($sql)){
			$statusStr = 'SUCCESS: information saved';
		}
		else{
			$statusStr = 'ERROR: Editing of character state failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}

	public function deleteCharState($cs){
		$status = '';
		if(is_numeric($cs)){
			//Delete images links
			$sql = 'DELETE FROM kmcsimages WHERE (cid = '.$this->cid.') AND (cs = '.$cs.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = 'ERROR deleting character state images: '.$this->conn->error.', '.$sql;
			}

			//Delete language links
			$sql = 'DELETE FROM kmcslang WHERE (cid = '.$this->cid.') AND (cs = '.$cs.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = 'ERROR deleting character state languages: '.$this->conn->error.', '.$sql;
			}

			//Delete character dependance links
			$sql = 'DELETE FROM kmchardependance WHERE (ciddependance = '.$this->cid.') AND (csdependance = '.$cs.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = 'ERROR deleting character dependance linked to character state: '.$this->conn->error.', '.$sql;
			}

			//Delete description links
			$sql = 'DELETE FROM kmdescr WHERE (cid = '.$this->cid.') AND (cs = '.$cs.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = 'ERROR deleting descriptions linked to character state: '.$this->conn->error.', '.$sql;
			}

			//Delete character states
			$sql = 'DELETE FROM kmcs WHERE (cid = '.$this->cid.') AND (cs = '.$cs.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = 'ERROR deleting character state: '.$this->conn->error.', '.$sql;
			}
		}
		return $status;
	}

	public function uploadCsImage($formArr){
		global $PARAMS_ARR;
		$statusStr = '';
		if(is_numeric($formArr['cid']) && is_numeric($formArr['cs'])){
	 		$imageRootPath = $GLOBALS["imageRootPath"];
			if(substr($imageRootPath,-1) != "/") $imageRootPath .= "/";
			if(file_exists($imageRootPath)){
				$imageRootPath .= 'ident/';
				if(!file_exists($imageRootPath)){
					if(!mkdir($imageRootPath)){
						return 'ERROR, unable to create upload directory: '.$imageRootPath;
					}
				}
				$imageRootPath .= 'csimgs/';
				if(!file_exists($imageRootPath)){
					if(!mkdir($imageRootPath)){
						return 'ERROR, unable to create upload directory: '.$imageRootPath;
					}
				}
				//Create url prefix
				$imageRootUrl = $GLOBALS["imageRootUrl"];
				if(substr($imageRootUrl,-1) != "/") $imageRootUrl .= "/";
				$imageRootUrl .= 'ident/csimgs/';

				//Image is to be downloaded
				$fileName = $this->cleanFileName(basename($_FILES['urlupload']['name']),$imageRootUrl);
				$imagePath = $imageRootPath.str_replace('.','_temp.',$fileName);
				move_uploaded_file($_FILES['urlupload']['tmp_name'], $imagePath);
				if(file_exists($imagePath)){
					if($this->createNewCsImage($imagePath)){
						//Add url to database
						$notes = $this->cleanInStr($formArr['notes']);
						$sql = 'INSERT INTO kmcsimages(cid, cs, url, notes, sortsequence, username) '.
							'VALUES('.$formArr['cid'].','.$formArr['cs'].',"'.$imageRootUrl.$fileName.'",'.
							($notes?'"'.$notes.'"':'NULL').','.
							(is_numeric($formArr['sortsequence'])?$formArr['sortsequence']:'50').',"'.$PARAMS_ARR['un'].'")';
						if(!$this->conn->query($sql)){
							$statusStr = 'ERROR loading char state image: '.$this->conn->error;
						}
						unlink($imagePath);
					}
				}
				else{
					return 'ERROR uploading file, file does not exist: '.$imagePath;
				}
			}
		}
		else{
			$statusStr = 'ERROR: Upload path does not exist (path: '.$imageRootPath.')';
		}
		return $statusStr;
	}


	private function cleanFileName($fName,$subPath){
		if($fName){
			$pos = strrpos($fName,'.');
			$ext = substr($fName,$pos+1);
			$fName = substr($fName,0,$pos);
			$fName = str_replace(" ","_",$fName);
			$fName = str_replace(array(chr(231),chr(232),chr(233),chr(234),chr(260)),"a",$fName);
			$fName = str_replace(array(chr(230),chr(236),chr(237),chr(238)),"e",$fName);
			$fName = str_replace(array(chr(239),chr(240),chr(241),chr(261)),"i",$fName);
			$fName = str_replace(array(chr(247),chr(248),chr(249),chr(262)),"o",$fName);
			$fName = str_replace(array(chr(250),chr(251),chr(263)),"u",$fName);
			$fName = str_replace(array(chr(264),chr(265)),"n",$fName);
			$fName = preg_replace("/[^a-zA-Z0-9\-_\.]/", "", $fName);
			if(strlen($fName) > 30) {
				$fName = substr($fName,0,30);
			}
			//Check and see if file already exists, if so, rename filename until it has a unique name
	 		$tempFileName = $fName;
	 		$cnt = 1;
	 		while(file_exists($subPath.$fName)){
	 			$tempFileName = str_ireplace(".jpg","_".$cnt.".jpg",$fName);
	 			$cnt++;
	 		}
		}
 		return $tempFileName.'.'.$ext;
 	}

	private function createNewCsImage($path){
		$status = false;
		$imgWidth = 800;
		$qualityRating= 100;
		list($width, $height) = getimagesize(str_replace(' ', '%20', $path));
		$imgHeight = ($imgWidth*($height/$width));
		echo $imgHeight;
   		$sourceImg = imagecreatefromjpeg($path);
		$newImg = imagecreatetruecolor($imgWidth,$imgHeight);
		imagecopyresampled($newImg,$sourceImg,0,0,0,0,$imgWidth,$imgHeight,$width,$height);
		//imagecopyresized($newImg,$sourceImg,0,0,0,0,$imgWidth,$imgHeight,$width,$height);
		$status = imagejpeg($newImg, str_replace('_temp','',$path), $qualityRating);
		if(!$status){
			echo 'Error: Unable to create image file: '.$path;
		}
		imagedestroy($newImg);
		imagedestroy($sourceImg);
		return $status;
	}

	public function deleteCsImage($csImgId){
		$statusStr = 'SUCCESS: image uploaded successful';
		//Remove image from file system
	 	$imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($imageRootPath,-1) != "/") $imageRootPath .= "/";
		$imageRootPath .= 'ident/csimgs/';
		$sql = 'SELECT url FROM kmcsimages WHERE csimgid = '.$csImgId;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$url = $r->url;
			$url = substr($url,strrpos($url,'/')+1);
			unlink($imageRootPath.$url);
		}
		$rs->free();
		//Remove image record from database
		$sqlDel = 'DELETE FROM kmcsimages WHERE csimgid = '.$csImgId;
		if(!$this->conn->query($sqlDel)){
			$statusStr = 'ERROR: unable to delete image; '.$this->error;
		}
		return $statusStr;
	}

	public function getTaxonRelevance(){
		$retArr = array();
		if($this->cid){
			$sql = 'SELECT l.tid, l.relation, l.notes, t.sciname FROM kmchartaxalink l INNER JOIN taxa t ON l.tid = t.tid WHERE l.cid = '.$this->cid;
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$r->relation][$r->tid]['sciname'] = $r->sciname;
					$retArr[$r->relation][$r->tid]['notes'] = $r->notes;
				}
				$rs->free();
			}
			else{
				trigger_error('unable to get Taxon Links; '.$this->conn->error);
			}
		}
		return $retArr;
	}

	public function saveTaxonRelevance($tid,$rel,$notes){
		$statusStr = '';
		if($this->cid && is_numeric($tid)){
			$sql = 'INSERT INTO kmchartaxalink(cid,tid,relation,notes) VALUES('.$this->cid.','.$tid.',"'.$this->cleanInStr($rel).'","'.$this->cleanInStr($notes).'")';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR: unable to add Taxon Relevance; '.$this->conn->error;
				//trigger_error('ERROR: unable to add Taxon Relevance; '.$this->conn->error);
			}
		}
		return $statusStr;
	}

	public function deleteTaxonRelevance($tid){
		$statusStr = 'SUCCESS: taxon linkage removed';
		if($this->cid && is_numeric($tid)){
			$sql = 'DELETE FROM kmchartaxalink WHERE cid = '.$this->cid.' AND tid = '.$tid;
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR: unable to delete Taxon Relevance; '.$this->conn->error;
				trigger_error('ERROR: unable to delete Taxon Relevance; '.$this->conn->error);
			}
		}
		return $statusStr;
	}

	public function getHeadingArr(){
		$retArr = array();
		$sql = 'SELECT hid, headingname, notes, sortsequence FROM kmcharheading ';
		if($this->langId) $sql .= 'WHERE (langid = '.$this->langId.') ';
		$sql .= 'ORDER BY sortsequence,headingname';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->hid]['name'] = $this->cleanOutStr($r->headingname);
			$retArr[$r->hid]['notes'] = $this->cleanOutStr($r->notes);
			$retArr[$r->hid]['sortsequence'] = $r->sortsequence;
		}
		$rs->free();
		return $retArr;
	}

	public function addHeading($name,$notes,$sortSeq){
		$statusStr = '';
		$sql = 'INSERT INTO kmcharheading(headingname,notes,langid,sortsequence) VALUES ("'.$name.'",'.($notes?'"'.$notes.'"':'NULL').','.$this->langId.','.(is_numeric($sortSeq)?$sortSeq:'NULL').')';
		if(!$this->conn->query($sql)){
			$statusStr = 'Error adding heading: '.$this->conn->error;
		}
		return $statusStr;
	}

	public function editHeading($hid,$name,$notes,$sortSeq){
		$statusStr = '';
		$sql = 'UPDATE kmcharheading '.
			'SET headingname = "'.$name.'", '.
			'notes = '.($notes?'"'.$notes.'"':'NULL').', '.
			'sortsequence = '.(is_numeric($sortSeq)?$sortSeq:'NULL').
			' WHERE hid = '.$hid;
		if(!$this->conn->query($sql)){
			$statusStr = 'Error editing heading: '.$this->conn->error;
		}
		return $statusStr;
	}

	public function deleteHeading($hid){
		$statusStr = '';
		$sql = 'DELETE FROM kmcharheading WHERE hid = '.$hid;
		if(!$this->conn->query($sql)){
			$statusStr = 'Error deleting heading: '.$this->conn->error;
		}
		return $statusStr;
	}

	//Data retrival functions
	public function getGlossaryList(){
		$retArr = array();
		$sql = 'SELECT glossid, term, language FROM glossary';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			//$k variable is needed to so that list can be alphabetical even when html tags (e.g. italics) are embedded into the terms
			$k = strip_tags(strtolower($r->term));
			$retArr[$k][$r->glossid]['term'] = $r->term;
			$retArr[$k][$r->glossid]['lang'] = $r->language;
		}
		$rs->free();
		ksort($retArr);
		return $retArr;
	}

	public function getLanguageArr(){
		$retArr = array();
		$sql = 'SELECT langid, langname FROM adminlanguages ORDER BY langname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->langid] = $r->langname;
		}
		$rs->free();
		return $retArr;
	}

	//Setters and getters
	public function getCid(){
		return $this->cid;
	}

	public function setCid($cid){
		if(is_numeric($cid)) $this->cid = $cid;
	}

	public function setLanguage($l){
		$this->lang = $l;
	}

	public function setLangId($lang=''){
		if(!$lang){
			if($GLOBALS['defaultLang']) $lang = $GLOBALS['defaultLang'];
			else $lang = 'English';
		}
		if(is_numeric($lang)) $this->langId = $lang;
		else{
			$sql = 'SELECT langid FROM adminlanguages WHERE langname = "'.$this->cleanInStr($lang).'" OR iso639_1 = "'.$this->cleanInStr($lang).'" OR iso639_2 = "'.$this->cleanInStr($lang).'" ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$this->langId = $r->langid;
			}
			$rs->free();
		}
	}

	//General functions
	private function cleanOutStr($str){
		$newStr = $str;
		if(isset($str)){
			$newStr = str_replace('"',"&quot;",$str);
			$newStr = str_replace("'","&apos;",$newStr);
		}
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