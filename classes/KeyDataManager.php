<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class KeyDataManager extends Manager {

	private $sql = '';
	private $relevanceValue;		//Percent (as a decimal) of Taxa that must be coded for a CID to be displayed
	private $keyCon;
	private $taxonFilter;
	private $uid;
	private $clid;
	private $childClidArr = array();
	private $clName;
	private $clTitle;
	private $clAuthors;
	private $clType;
	private $dynamicSql;
	private $charArr = Array();
	private $taxaCount;
	private $lang;
	private $displayImages;
	private $sortBy;			//0=scientific name, 2=images
	private $displayCommon = false;
	private $pid;
	private $dynClid;

	function __construct() {
		parent::__construct();
		$this->relevanceValue = .9;
	}

	public function __destruct(){
		parent::__destruct();
	}


	//$returns an Array of ("chars" => $charArray, "taxa" => $taxaArray)
	//In coming attrs are in the form of: (cid => array of cs)
	public function getData(){
		$retArr = array();
		if(($this->clid && $this->taxonFilter) || $this->dynClid){
			$retArr['taxa'] = $this->getTaxaList();
			$retArr['chars'] = $this->getCharList();
		}
		return $retArr;
	}

	private function getCharList(){
		$returnArray = Array();
		//Rate char list: Get list of char that are coded for a percentage of taxa list that is greater than
		if($this->sql){
			$charList = Array();
			$countMin = $this->taxaCount * $this->relevanceValue;
			$loopCnt = 0;
			while(!$charList && $loopCnt < 10){
				$sqlRev = "SELECT tc.CID, Count(tc.TID) AS c FROM ".
					"(SELECT DISTINCT tList.TID, d.CID FROM ($this->sql) AS tList INNER JOIN kmdescr d ON tList.TID = d.TID WHERE (d.CS <> '-')) AS tc ".
					"GROUP BY tc.CID HAVING ((Count(tc.TID)) > $countMin)";
				$rs = $this->conn->query($sqlRev);
				//echo $sqlRev.'<br/>';
				while($row = $rs->fetch_object()){
					$charList[] = $row->CID;
				}
				$countMin = $countMin*0.9;
				$loopCnt++;
			}
			$charList = array_merge($charList,array_keys($this->charArr));

			if($charList){
				//Create sql string and get char record set
				/*$sqlChar = "SELECT DISTINCT cs.CID, cs.CS, cs.CharStateName, cs.Description AS csdescr, charnames.CharName, charnames.Description AS chardescr, ".
					"charnames.Heading, charnames.URL, Count(cs.CS) AS Ct, characters.DifficultyRank, charnames.Language ".
					"FROM ((($this->sql) AS tList INNER JOIN descr ON tList.TID = descr.TID) INNER JOIN cs ON (descr.CS = cs.CS) AND (descr.CID = cs.CID)) INNER JOIN ".
					"(characters INNER JOIN charnames ON characters.CID = charnames.CID) ON (cs.Language = charnames.Language) AND (cs.CID = charnames.CID) ".
					"GROUP BY cs.CID, cs.CS, cs.CharStateName, charnames.CharName, charnames.Heading, charnames.URL, characters.DifficultyRank, charnames.Language, characters.Type ".
					"HAVING (((cs.CID) In (".implode(",",$charList).")) AND ((cs.CS)<>'-') AND ((characters.Type)='UM' Or (characters.Type)='OM') AND characters.DifficultyRank < 3) ".
					"ORDER BY charnames.Heading, characters.SortSequence, cs.SortSequence";*/
				$sqlChar = 'SELECT DISTINCT cs.CID, cs.CS, cs.CharStateName, cs.Description AS csdescr, chars.CharName,
					chars.description AS chardescr, chars.hid, chead.headingname, chars.glossid, chars.helpurl, Count(cs.CS) AS Ct, chars.DifficultyRank
					FROM ('.$this->sql.') AS tList INNER JOIN kmdescr d ON tList.TID = d.TID
					INNER JOIN kmcs cs ON (d.CS = cs.CS) AND (d.CID = cs.CID)
					INNER JOIN kmcharacters chars ON chars.cid = cs.CID
					LEFT JOIN kmcharheading chead ON chars.hid = chead.hid
					GROUP BY chead.language, cs.CID, cs.CS, cs.CharStateName, chars.CharName, chead.headingname, chars.helpurl, chars.DifficultyRank, chars.chartype
					HAVING (chead.language = "English" OR chead.language IS NULL) AND (cs.CID In ('.implode(",",$charList).')) AND (cs.CS <> "-") AND (chars.chartype="UM" Or chars.chartype = "OM") AND (chars.DifficultyRank < 3)
					ORDER BY chead.hid,	chars.SortSequence, cs.SortSequence ';
				//echo $sqlChar.'<br/>';
				$result = $this->conn->query($sqlChar);

				//Process recordset
				$langList = Array('English');
				$headingArray = Array();
				if(!$result) return null;
				while($row = $result->fetch_object()){
					$ct = $row->Ct;			//count of how many times the CS was used in this species list
					$charCID = $row->CID;
					if($ct < $this->taxaCount || array_key_exists($charCID,$this->charArr)){		//add to return if stateUseCount is less than taxaCount (ie: state is useless if all taxa code true) or is an attribute selected by user
						$language = 'English';
						$headingID = $row->hid;
						$charName = $row->CharName;
						if($row->chardescr) $charName = '<span class="charHeading" title="'.$row->chardescr.'">'.$charName.'</span>';
						if($row->helpurl) $charName .= ' <a class="infoAnchor" href="'.$row->helpurl.'" target="_blank" title="external resource"><img src="../images/info.png"></a>';
						if($row->glossid) $charName .= ' <a class="infoAnchor" href="" onclick="openGlossaryPopup('.$row->glossid.');return false;" title="glossary term"><img src="../images/info.png"></a>';
						$diffRank = false;
						if($row->DifficultyRank && $row->DifficultyRank > 1 && !array_key_exists($charCID,$this->charArr)) $diffRank = true;

						//Set HeadingName within the $charArray, if not yet set
						$headingArray[$headingID]['HeadingNames'][$language] = $row->headingname;

						//Set CharName within the $stateArray, if not yet set
						if(!array_key_exists($headingID, $headingArray) || !array_key_exists($charCID, $headingArray[$headingID]) || !array_key_exists('CharNames', $headingArray[$headingID][$charCID]) || !array_key_exists($language, $headingArray[$headingID][$charCID]['CharNames'])){
							$headingArray[$headingID][$charCID]["CharNames"][$language] = "<div class='dynam'".($diffRank?" style='display:none;' ":" style='display:;'")."><span class='dynamlang' lang='".$language."'".
								($language==$this->lang?" style='display:;'":" style='display:none;'").">&nbsp;&nbsp;".$charName."</span></div>";
						}

						$checked = '';
						$cs = $row->CS;
						if($this->charArr && array_key_exists($charCID,$this->charArr) && in_array($cs,$this->charArr[$charCID])) $checked = 'checked';
						if(!array_key_exists($headingID,$headingArray) || !array_key_exists($charCID,$headingArray[$headingID]) || !array_key_exists($cs,$headingArray[$headingID][$charCID]) || !$headingArray[$headingID][$charCID][$cs]["ROOT"]){
							$headingArray[$headingID][$charCID][$cs]["ROOT"] = "<div class='dynamopt'".//($diffRank?" style='display:none;' class='dynam'":" style='display:;'").
								">&nbsp;&nbsp;<input type='checkbox' name='attr[]' id='cb".$charCID."-".$cs."' value='".$charCID."-".$cs."' $checked onclick='document.keyform.submit();' />";
						}
						$charStateName = $row->CharStateName;
						if($row->csdescr) $charStateName = '<span class="characterStateName" title="'.$row->csdescr.'">'.$row->CharStateName.'</span>';
						$headingArray[$headingID][$charCID][$cs][$language] = $charStateName;
					}
				}
				$result->free();
				//Ensures correct sorting and puts html output into returnStrings Array
				$returnArray['Languages'] = $langList;			//Put a list of languages in returnArray
				foreach($headingArray as $HID => $cArray){
					$displayHeading = true;
					$headNameArray = $cArray['HeadingNames'];
					unset($cArray['HeadingNames']);
					$endStr ="";
					foreach($cArray as $cid => $csArray){
						if(count($csArray) > 2 || array_key_exists($cid,$this->charArr)){
							if($displayHeading){
								$returnArray[] = '<div class="headingname" id="headingname'.$HID.'">';
								foreach($headNameArray as $langValue => $headValue){
									$returnArray[] .= "<span lang='".$langValue."' style='".($langValue==$this->lang?"display:;":"display:none;")."'>$headValue</span>\n";
								}
								$returnArray[] = '</div>';
								$returnArray[] = '<div class="heading" id="heading'.$HID.'">';
								$endStr = '</div>';
							}
							$displayHeading = false;
			//				ksort($csArray);
							$chars = $csArray['CharNames'];
							unset($csArray['CharNames']);
							$returnArray[] = '<div id="char'.$charCID.'">';
							foreach($chars as $names){
								$returnArray[] = $names;
							}
							foreach($csArray as $csKey => $stateNames){
								if(array_key_exists('ROOT',$stateNames)) $returnArray[] = $stateNames['ROOT'];
								unset($stateNames['ROOT']);
								foreach($stateNames as $csLang => $csValue){
									$returnArray[] = "<span lang='".$csLang."' ".
										($csLang==$this->lang?" style='display:;'":" style='display:none;'").">$csValue</span>";
								}
								$returnArray[] = '</div>';
							}
							$returnArray[] = '</div>';
						}
					}
					if($endStr) $returnArray[] = $endStr;
				}
			}
		}
		return $returnArray;
	}

	public function getCharArr(){
		$retArr = Array();
		if($this->sql){
			$charList = Array();
			$countMin = $this->taxaCount * $this->relevanceValue;
			$loopCnt = 0;
			while(!$charList && $loopCnt < 10){
				$sqlRev = 'SELECT tc.CID, Count(tc.TID) AS c '.
					'FROM (SELECT DISTINCT tList.TID, d.CID FROM ('.$this->sql.') AS tList INNER JOIN kmdescr d ON tList.TID = d.TID WHERE (d.CS <> "-")) AS tc '.
					'GROUP BY tc.CID HAVING ((Count(tc.TID)) > '.$countMin.')';
				$rs = $this->conn->query($sqlRev);
				//echo $sqlRev.'<br/>';
				while($row = $rs->fetch_object()){
					$charList[] = $row->CID;
				}
				$countMin = $countMin*0.9;
				$loopCnt++;
			}
			$charList = array_merge($charList,array_keys($this->charArr));

			if($charList){
				$sqlChar = 'SELECT DISTINCT cs.CID, cs.CS, cs.CharStateName, cs.Description AS csdescr, cs.glossid AS csglossid, chars.CharName,
					chars.description AS chardescr, chars.hid, chead.headingname, chars.glossid AS charglossid, chars.helpurl, Count(cs.CS) AS Ct, chars.DifficultyRank, csimg.url AS csimgurl
					FROM ('.$this->sql.') AS tList INNER JOIN kmdescr d ON tList.TID = d.TID
					INNER JOIN kmcs cs ON (d.CS = cs.CS) AND (d.CID = cs.CID)
					INNER JOIN kmcharacters chars ON chars.cid = cs.CID
					LEFT JOIN kmcharheading chead ON chars.hid = chead.hid
					LEFT JOIN kmcsimages csimg ON (cs.CS = csimg.CS) AND (cs.CID = csimg.CID)
					GROUP BY chead.language, cs.CID, cs.CS, cs.CharStateName, chars.CharName, chead.headingname, chars.helpurl, chars.DifficultyRank, chars.chartype
					HAVING (chead.language = "English" OR chead.language IS NULL) AND (cs.CID In ('.implode(",",$charList).')) AND (cs.CS <> "-")
					AND (chars.chartype="UM" Or chars.chartype = "OM") AND (chars.DifficultyRank < 3)
					ORDER BY chead.hid,	chars.SortSequence, cs.SortSequence ';
				//echo $sqlChar.'<br/>';
				$rs = $this->conn->query($sqlChar);

				//Process recordset
				$langList = Array('English');
				$headingArray = Array();
				if(!$rs) return null;
				while($r = $rs->fetch_object()){
					$ct = $r->Ct;			//count of how many times the CS was used in this species list
					$charCID = $r->CID;
					if($ct < $this->taxaCount || array_key_exists($charCID,$this->charArr)){
						//add to return if stateUseCount is less than taxaCount (ie: state is useless if all taxa code true) or is an attribute selected by user
						$headingID = $r->hid;
						$charName = $r->CharName;
						if($r->chardescr) $charName = '<span class="charHeading" title="'.$r->chardescr.'">'.$charName.'</span>';
						if($r->helpurl) $charName .= ' <a class="infoAnchor" href="'.$r->helpurl.'" target="_blank" title="external resource"><img src="../images/info.png" /></a>';
						if($r->charglossid) $charName .= ' <a class="infoAnchor" href="" onclick="openGlossaryPopup('.$r->charglossid.');return false;" title="glossary term"><img src="../images/info.png"></a>';
						$diffRank = false;
						if($r->DifficultyRank && $r->DifficultyRank > 1 && !array_key_exists($charCID,$this->charArr)) $diffRank = true;

						//Set HeadingName within the $charArray, if not yet set
						$language = 'English';
						$headingArray[$headingID]['HeadingNames'][$language] = $r->headingname;

						//Set CharName within the $stateArray, if not yet set
						if(!array_key_exists($headingID, $headingArray) || !array_key_exists($charCID, $headingArray[$headingID]) || !array_key_exists("CharNames", $headingArray[$headingID][$charCID]) || !array_key_exists($language, $headingArray[$headingID][$charCID]["CharNames"])){
							$charStr = '<div class="dynam" style="display:'.($diffRank?'none':'').';">';
							$charStr .= '<span class="dynamlang" lang="'.$language.'" style="display:'.($language==$this->lang?'':'none').'">'.$charName.'</span>';
							$charStr .= '</div>';
							$headingArray[$headingID][$charCID]['CharNames'][$language] = $charStr;
						}

						$cs = $r->CS;
						$checked = '';
						if($this->charArr && array_key_exists($charCID,$this->charArr) && in_array($cs,$this->charArr[$charCID])) $checked = "checked";
						if(!array_key_exists($headingID,$headingArray) || !array_key_exists($charCID,$headingArray[$headingID]) || !array_key_exists($cs,$headingArray[$headingID][$charCID]) || !$headingArray[$headingID][$charCID][$cs]["ROOT"]){
							$charState = '<input type="checkbox" name="attr[]" id="cb'.$charCID.'-'.$cs.'" value="'.$charCID.'-'.$cs.'" '.$checked.' onclick="this.form.submit();" />';
							$headingArray[$headingID][$charCID][$cs]['ROOT'] = $charState;
						}
						$charStateName = $r->CharStateName;
						if($r->csdescr) $charStateName = '<span class="characterStateName" title="'.$r->csdescr.'">'.$r->CharStateName.'</span>';
						if($r->csglossid) $charStateName .= ' <a class="infoAnchor" href="" onclick="openGlossaryPopup('.$r->csglossid.');return false;" title="glossary term"><img src="../images/info.png"></a>';
						if($r->csimgurl) $charStateName .= ' <a class="infoAnchor" href="'.$r->csimgurl.'" target="_blank" title="Character State Image"><img src="../images/image.png"></a>';
						$headingArray[$headingID][$charCID][$cs][$language] = $charStateName;
					}
				}
				$rs->free();
				//Ensures correct sorting and puts html output into returnStrings Array
				$retArr['Languages'] = $langList;			//Put a list of languages in returnArray
				foreach($headingArray as $HID => $cArray){
					$displayHeading = true;
					$headNameArray = $cArray['HeadingNames'];
					unset($cArray['HeadingNames']);
					$endStr ="";
					foreach($cArray as $cid => $csArray){
						if(count($csArray) > 2 || array_key_exists($cid,$this->charArr)){
							if($displayHeading){
								$retArr[] = '<div class="char-heading">';
								foreach($headNameArray as $langValue => $headValue){
									$retArr[] .= '<span lang="'.$langValue.'" style="display:'.($langValue==$this->lang?'':'none').'">'.$headValue.'</span>';
								}
								$retArr[] = '</div>';
								$retArr[] = '<div class="heading" id="heading'.$HID.'" >';
								$endStr = '</div>';
							}
							$displayHeading = false;
							// ksort($csArray);
							$retArr[] = '<div id="char'.$charCID.'">';
							$chars = $csArray['CharNames'];
							foreach($chars as $names){
								$retArr[] = $names;
							}
							unset($csArray['CharNames']);
							foreach($csArray as $csKey => $stateNames){
								$retArr[] = '<div class="cs-div">';
								if(array_key_exists('ROOT',$stateNames)) $retArr[] = $stateNames['ROOT'];
								unset($stateNames['ROOT']);
								foreach($stateNames as $csLang => $csValue){
									$retArr[] = '<span lang="'.$csLang.'" style="display:'.($csLang==$this->lang?'':'none').'">'.$csValue.'</span>';
								}
								$retArr[] = '</div>';
							}
							$retArr[] = '</div>';
						}
					}
					if($endStr) $retArr[] = $endStr;
				}
			}
		}
		return $retArr;
	}

	public function getTaxaArr(){
		$retArr = array();
		$this->setTaxaListSQL();
		if($this->sql){
			$rs = $this->conn->query($this->sql.' ORDER BY t.sciname');
			$this->taxaCount = 0;
			$taxaArr = array();
			while($r = $rs->fetch_object()){
				$this->taxaCount++;
				$family = $r->family;
				if($this->sortBy) $family = 0;
				$retArr[$family][$r->tid]['s'] = $r->sciname;
				$taxaArr[$r->tid] = $family;
			}
			$rs->free();
			if($taxaArr){
				if($this->displayCommon){
					$sql = 'SELECT tid, vernacularName FROM taxavernaculars WHERE langid = 1 AND SortSequence = 1 AND tid IN('.implode(',',array_keys($taxaArr)).')';
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						$retArr[$taxaArr[$r->tid]][$r->tid]['v'] = $r->vernacularName;
					}
					$rs->free();
				}
				if($this->displayImages){
					$sql = 'SELECT i2.tid, i.url, i.thumbnailurl FROM images i INNER JOIN '.
						'(SELECT ts1.tid, SUBSTR(MIN(CONCAT(LPAD(i.sortsequence,6,"0"),i.imgid)),7) AS imgid '.
						'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
						'INNER JOIN images i ON ts2.tid = i.tid '.
						'WHERE i.sortsequence < 500 AND (i.thumbnailurl IS NOT NULL) AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND (ts1.tid IN('.implode(',',array_keys($taxaArr)).')) '.
						'GROUP BY ts1.tid) i2 ON i.imgid = i2.imgid';
					//echo $sql;
					$rs = $this->conn->query($sql);
					$matchedArr = array();
					while($r = $rs->fetch_object()){
						$url = $r->thumbnailurl;
						if(!$url || $url == 'empty' || substr($url,10) == 'processing') $url = $r->url;
						if(substr($url,10) == 'processing') $url = '';
						$retArr[$taxaArr[$r->tid]][$r->tid]['i'] = $url;
						$matchedArr[] = $r->tid;
					}
					$rs->free();
					$missingArr = array_diff(array_keys($taxaArr),$matchedArr);
					if($missingArr){
						//Get children images
						$sql2 = 'SELECT i2.tid, i.url, i.thumbnailurl FROM images i INNER JOIN '.
							'(SELECT ts1.parenttid AS tid, SUBSTR(MIN(CONCAT(LPAD(i.sortsequence,6,"0"),i.imgid)),7) AS imgid '.
							'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
							'INNER JOIN images i ON ts2.tid = i.tid '.
							'WHERE i.sortsequence < 500 AND (i.thumbnailurl IS NOT NULL) AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND (ts1.parenttid IN('.implode(',',$missingArr).')) '.
							'GROUP BY ts1.tid) i2 ON i.imgid = i2.imgid';
						//echo $sql;
						$rs2 = $this->conn->query($sql2);
						while($r2 = $rs2->fetch_object()){
							$url = $r2->thumbnailurl;
							if(!$url || substr($url,10) == 'processing') $url = $r2->url;
							if(substr($url,10) == 'processing') $url = '';
							$retArr[$taxaArr[$r2->tid]][$r2->tid]['i'] = $url;
						}
						$rs2->free();
					}
				}
			}
		}
		return $retArr;
	}

	public function getTaxaList(){
		//Delete after current modifications are approved
		$retArr = array();
		$this->setTaxaListSQL();
		if($this->sql){
			$rs = $this->conn->query($this->sql);
			$this->taxaCount = 0;
			$taxaArr = array();
			while($r = $rs->fetch_object()){
				$this->taxaCount++;
				$retArr[$r->family][$r->tid] = $r->sciname;
				$taxaArr[$r->tid] = $r->family;
			}
			$rs->free();
			if($this->displayCommon){
				$sql = 'SELECT tid, vernacularName FROM taxavernaculars WHERE langid = 1 AND SortSequence = 1 AND tid IN('.implode(',',array_keys($taxaArr)).')';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$retArr[$taxaArr[$r->tid]][$r->tid] = $r->vernacularName;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function setTaxaListSQL(){
		if(!$this->sql){
			if($this->clid || $this->dynClid){
				$sqlFromBase = 'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted ';
				$sqlWhere = 'WHERE (ts.taxauthid = 1) AND (t.RankId BETWEEN 180 AND 220) ';
				if($this->dynClid){
					$sqlFromBase .= 'INNER JOIN fmdyncltaxalink clk ON ts.tid = clk.tid ';
					$sqlWhere .= 'AND (clk.dynclid = '.$this->dynClid.') ';
				}
				else{
					if($this->clType == 'dynamic'){
						$sqlFromBase .= 'INNER JOIN omoccurrences o ON ts.tid = o.tidinterpreted ';
						$sqlWhere .= 'AND ('.$this->dynamicSql.') ';
					}
					else{
						$sqlFromBase .= 'INNER JOIN fmchklsttaxalink clk ON ts.tid = clk.tid ';
						$clidStr = $this->clid;
						if($this->childClidArr){
							$clidStr .= ','.implode(',',array_keys($this->childClidArr));
						}
						$sqlWhere .= 'AND (clk.clid IN('.$clidStr.')) ';
					}
				}
				//If a taxon limit has been set, add taxon value to sql
				if($this->taxonFilter){
					if($this->taxonFilter == 'All Species'){
						//Do nothing
					}
					else{
						$sqlWhere .= 'AND ((ts.Family = "'.$this->cleanInStr($this->taxonFilter).'") OR (t.UnitName1 = "'.$this->cleanInStr($this->taxonFilter).'")) ';
					}
				}

				//Limit by character attribute selections
				$count = 0;
				if($this->charArr){
					//Create sql string
					foreach($this->charArr as $cid => $states){		//key=cid, value=array of cs
						$count++;
						$sqlFromBase .= 'INNER JOIN kmdescr AS D'.$count.' ON t.TID = D'.$count.'.TID ';
						$stateStr = '';
						foreach($states as $cs){
							 $stateStr.=(empty($stateStr)?'':'OR ').'(D'.$count.'.CS="'.$this->cleanInStr($cs).'") ';
						}
						$sqlWhere.=' AND (D'.$count.'.CID='.$cid.') AND ('.$stateStr.')';
					}
				}
				$this->sql = 'SELECT DISTINCT t.tid, ts.family, t.sciname '.$sqlFromBase.$sqlWhere;
			}
		}
	}

	//Misc functions
	public function getIntroHtml(){
		$returnStr = "<h2>Please enter a checklist, taxonomic group, and then select 'Submit Criteria'</h2>";
		$returnStr .= "This key is still in the developmental phase. The application, data model, and actual data will need tuning. ".
			"The key has been developed to minimize the exclusion of species due to the ".
			"lack of data. The consequences of this is that a 'shrubs' selection may show non-shrubs until that information is corrected. ".
			"User input is necessary for the key to improve! Please email me with suggestions, comments, or problems: <a href='".$GLOBALS['ADMIN_EMAIL']."'>".$GLOBALS['ADMIN_EMAIL']."</a><br><br>";
		$returnStr .= "<b>Note:</b> If few morphological characters are displayed for a particular checklist, it is likely due to not yet having enough ".
		"morphological data compiled for that subset of species. If you would like to help, please email me at the above address. ";
		return $returnStr;
	}

	public function setProject($projValue){
		if(is_numeric($projValue)){
			$this->pid = $projValue;
		}
	}

	public function getTaxaFilterList(){
		$returnArr = Array();
		$sql = 'SELECT DISTINCT nt.UnitName1, ts.Family ';
		if($this->clid && $this->clType == 'static'){
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',array_keys($this->childClidArr));
			}
			$sql .= 'FROM (taxstatus ts INNER JOIN taxa nt ON ts.tid = nt.tid) INNER JOIN fmchklsttaxalink cltl ON nt.TID = cltl.TID WHERE (ts.taxauthid = 1) AND (cltl.CLID IN('.$clidStr.')) ';
		}
		else if($this->dynClid){
			$sql .= 'FROM (taxstatus ts INNER JOIN taxa nt ON ts.tid = nt.tid) INNER JOIN fmdyncltaxalink dcltl ON nt.TID = dcltl.TID WHERE (ts.taxauthid = 1) AND (dcltl.dynclid = '.$this->dynClid.') ';
		}
		else{
			$sql .= 'FROM (((taxstatus ts INNER JOIN taxa nt ON ts.tid = nt.tid) '.
				'INNER JOIN fmchklsttaxalink cltl ON nt.TID = cltl.TID) '.
				'INNER JOIN fmchecklists cl ON cltl.CLID = cl.CLID) '.
				'INNER JOIN fmchklstprojlink clpl ON cl.CLID = clpl.clid '.
				'WHERE (ts.taxauthid = 1) ';
			if($this->pid) $sql .= 'AND (clpl.pid = '.$this->pid.') ';
		}
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$genus = $row->UnitName1;
			$family = $row->Family;
			if($genus) $returnArr[] = $genus;
			if($family) $returnArr[] = $family;
		}

		$rs->free();
		$returnArr = array_unique($returnArr);
		natcasesort($returnArr);
		array_unshift($returnArr,"--------------------------");
		array_unshift($returnArr, "All Species");
		return $returnArr;
	}

	public function setClValue($clid){
		$sql = "";
		if($this->dynClid){
			$sql = 'SELECT d.name, d.details, d.type FROM fmdynamicchecklists d WHERE (dynclid = '.$this->dynClid.')';
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->clName = $row->name;
				$this->clType = $row->type;
			}
			$result->free();
		}
		else{
			if(is_numeric($clid)){
				$this->clid = $clid;
				$this->setMetaData();
				//Get children checklists
				$sqlBase = 'SELECT ch.clidchild, cl2.name '.
					'FROM fmchecklists cl INNER JOIN fmchklstchildren ch ON cl.clid = ch.clid '.
					'INNER JOIN fmchecklists cl2 ON ch.clidchild = cl2.clid '.
					'WHERE (cl2.type != "excludespp") AND cl.clid IN(';
				$sql = $sqlBase.$this->clid.')';
				do{
					$childStr = "";
					$rsChild = $this->conn->query($sql);
					while($r = $rsChild->fetch_object()){
						$this->childClidArr[$r->clidchild] = $r->name;
						$childStr .= ','.$r->clidchild;
					}
					$sql = $sqlBase.substr($childStr,1).')';
				}while($childStr);
			}
		}
		return $this->clid;
	}

	private function setMetaData(){
		$sql = "SELECT name, authors, type, dynamicsql FROM fmchecklists WHERE (clid = ".$this->clid.")";
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$this->clName = $row->name;
			$this->clAuthors = $row->authors;
			$this->clType = ($row->type?$row->type:'static');
			$this->dynamicSql = $row->dynamicsql;
		}
		$rs->free();
	}

	public function setAttrs($attrs){
		if(is_array($attrs)){
			foreach($attrs as $attr){
				$fragments = explode("-",$attr);
				$cid = $fragments[0];
				$cs = $fragments[1];
				if(is_numeric($cid)) $this->charArr[$cid][] = $cs;
			}
		}
	}

	//General setters and getters
	public function getAttrs(){
		return $this->attrs;
	}

	public function setLanguage($l){
		$this->lang = $l;
	}

	public function setDisplayImages($bool){
		if($bool) $this->displayImages = 1;
		else $this->displayImages = 0;
	}

	public function setSortBy($bool){
		if($bool) $this->sortBy = 1;
		else $this->sortBy = 0;
	}

	public function setDisplayCommon($bool){
		if($bool) $this->displayCommon = true;
		else $this->displayCommon = false;
	}

	public function setTaxonFilter($t){
		$this->taxonFilter = $t;
	}

	public function getClid(){
		return $this->clid;
	}

	public function setDynClid($id){
		if(is_numeric($id)){
			$this->dynClid = $id;
		}
	}

	public function getTaxaCount(){
		return $this->taxaCount;
	}

	public function getClName(){
		return $this->clName;
	}

	public function getClAuthors(){
		return $this->clAuthors;
	}

	public function getClType(){
		return $this->clType;
	}

	public function setRelevanceValue($rel){
		if(is_numeric($rel)){
			$this->relevanceValue = $rel;
		}
	}

	public function getRelevanceValue(){
		return $this->relevanceValue;
	}
}
?>