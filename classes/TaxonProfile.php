<?php
include_once('Manager.php');

class TaxonProfile extends Manager {

	protected $tid;
	protected $rankId;
	private $parentTid;
	private $taxAuthId = 1;
	private $taxonName;
	private $taxonAuthor;
	private $taxonFamily;
	private $acceptance = true;
	private $forwarded = false;

	protected $acceptedArr = array();
	protected $synonymArr = array();
	protected $submittedArr = array();

	private $langArr = array();
	private $imageArr;
	private $sppArray;
	private $linkArr = false;

	private $displayLocality = 1;

	public function __construct(){
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function setTid($tid){
		if(is_numeric($tid)){
			$this->tid = $tid;
			if($this->setTaxon()) if(count($this->acceptedArr) == 1) $this->setSynonyms();
		}
	}

	private function setTaxon(){
		$status = false;
		if($this->tid){
			$sql = 'SELECT tid, sciname, author, rankid FROM taxa WHERE (tid = '.$this->tid.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->submittedArr['tid'] = $r->tid;
				$this->submittedArr['sciname'] = $r->sciname;
				$this->submittedArr['author'] = $r->author;
				$this->submittedArr['rankid'] = $r->rankid;
				$this->tid = $r->tid;
				$this->taxonName = $r->sciname;
				$this->taxonAuthor = $r->author;
				$this->rankId = $r->rankid;
			}
			$rs->free();

			//Set acceptance, parent, and family
			$sql2 = 'SELECT ts2.family, ts2.parenttid, t.tid, t.sciname, t.author, t.rankid, t.securitystatus '.
				'FROM taxstatus ts INNER JOIN taxa t ON ts.tidaccepted = t.tid '.
				'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tid '.
				'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (ts2.taxauthid = '.$this->taxAuthId.') AND (ts.tid = '.$this->tid.') ';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$this->acceptedArr[$r2->tid]['sciname'] = $r2->sciname;
				$this->acceptedArr[$r2->tid]['author'] = $r2->author;
				$this->acceptedArr[$r2->tid]['rankid'] = $r2->rankid;
				$this->acceptedArr[$r2->tid]['family'] = $r2->family;
				$this->acceptedArr[$r2->tid]['parenttid'] = $r2->parenttid;
				$this->taxonFamily = $r2->family;
				$this->parentTid = $r2->parenttid;
				if($r2->securitystatus > 0) $this->displayLocality = 0;
				$status = true;
			}
			$rs2->free();

			if($this->tid != key($this->acceptedArr)){
				if(count($this->acceptedArr) == 1){
					$this->forwarded = true;
					$this->tid = key($this->acceptedArr);
					$this->taxonName = $this->acceptedArr[$this->tid]['sciname'];
					$this->taxonAuthor = $this->acceptedArr[$this->tid]['author'];
					$this->rankId = $this->acceptedArr[$this->tid]['rankid'];
					$this->taxonFamily = $this->acceptedArr[$this->tid]['family'];
					$this->parentTid = $this->acceptedArr[$this->tid]['parenttid'];
				}
				else{
					$this->acceptance = false;
				}
			}

			if(!$this->displayLocality){
				if(isset($GLOBALS['IS_ADMIN']) && $GLOBALS['IS_ADMIN']) $this->displayLocality = 1;
				elseif(isset($GLOBALS['USER_RIGHTS'])){
					if(isset($GLOBALS['USER_RIGHTS']['RareSppReadAll'])) $this->displayLocality = 1;
					if(isset($GLOBALS['USER_RIGHTS']['RareSppAdmin'])) $this->displayLocality = 1;
					if(isset($GLOBALS['USER_RIGHTS']['CollAdmin'])) $this->displayLocality = 1;
				}
			}
		}
		return $status;
	}

	//Synonyms
	public function setSynonyms(){
		if($this->tid){
			$sql = 'SELECT t.tid, t.sciname, t.author '.
				'FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.tid '.
				'WHERE (ts.tidaccepted = '.$this->tid.') AND (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tidaccepted != t.tid) AND (ts.SortSequence < 90) '.
				'ORDER BY ts.SortSequence, t.SciName';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->synonymArr[$r->tid]['sciname'] = $r->sciname;
				$this->synonymArr[$r->tid]['author'] = $r->author;
			}
			$rs->free();
		}
	}

	//Vernaculars
	public function getVernaculars(){
		$retArr = array();
		if($this->tid){
			$tidStr = $this->tid;
			if($this->synonymArr) $tidStr .= ','.implode(',',array_keys($this->synonymArr));
			$sql = 'SELECT v.vid, v.vernacularname, l.iso639_1 as iso '.
				'FROM taxavernaculars v INNER JOIN adminlanguages l ON v.langid = l.langid '.
				'WHERE (v.TID IN('.$tidStr.')) AND (v.SortSequence < 90) '.
				'ORDER BY v.SortSequence,v.VernacularName';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->iso][$r->vid] = $r->vernacularname;
			}
			$rs->free();
		}
		return $retArr;
	}

	//Images functions
	public function echoImages($start, $length = 0, $useThumbnail = 1){		//length=0 => means show all images
		$status = false;
		if(!isset($this->imageArr)) $this->setTaxaImages();
		if(!$this->imageArr || count($this->imageArr) < $start) return false;
		$trueLength = ($length&&count($this->imageArr)>$length+$start?$length:count($this->imageArr)-$start);
		$iArr = array_slice($this->imageArr,$start,$trueLength,true);
		foreach($iArr as $imgId => $imgObj){
			if($start == 0 && $trueLength == 1) echo '<div id="centralimage">';
			else echo '<div class="imgthumb">';
			$imgUrl = $imgObj['url'];
			$imgAnchor = '../imagelib/imgdetails.php?imgid='.$imgId;
			$imgThumbnail = $imgObj['thumbnailurl'];
			if(array_key_exists('IMAGE_DOMAIN',$GLOBALS)){
				//Images with relative paths are on another server
				if(substr($imgUrl,0,1)=="/") $imgUrl = $GLOBALS['IMAGE_DOMAIN'].$imgUrl;
				if(substr($imgThumbnail,0,1)=="/") $imgThumbnail = $GLOBALS['IMAGE_DOMAIN'].$imgThumbnail;
			}
			if($imgObj['occid']) $imgAnchor = '../collections/individual/index.php?occid='.$imgObj['occid'];
			if($useThumbnail) if($imgObj['thumbnailurl']) $imgUrl = $imgThumbnail;
			echo '<div class="tptnimg"><a href="#" onclick="openPopup(\'' . htmlspecialchars($imgAnchor, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '\');return false;">';
			$titleStr = $imgObj['caption'];
			if($imgObj['sciname'] != $this->taxonName) $titleStr .= ' (linked from '.$imgObj['sciname'].')';
			echo '<img src="'.$imgUrl.'" title="'.$titleStr.'" alt="'.$this->taxonName.' image" />';
			/*
			if($length) echo '<img src="'.$imgUrl.'" title="'.$imgObj['caption'].'" alt="'.$spDisplay.' image" />';
			//else echo '<img class="delayedimg" src="" delayedsrc="'.$imgUrl.'" />';
			*/
			echo '</a></div>';
			echo '<div class="photographer">';
			if($imgObj['photographer']) echo $imgObj['photographer'];
			echo '</div>';
			echo '</div>';
			$status = true;
		}
		return $status;
	}

	private function setTaxaImages(){
		$this->imageArr = array();
		if($this->tid){
			$tidArr = Array($this->tid);
			$sql1 = 'SELECT DISTINCT ts.tid '.
				'FROM taxstatus ts INNER JOIN taxaenumtree tn ON ts.tid = tn.tid '.
				'WHERE tn.taxauthid = 1 AND ts.taxauthid = 1 AND ts.tid = ts.tidaccepted AND tn.parenttid = '.$this->tid;
			$rs1 = $this->conn->query($sql1);
			while($r1 = $rs1->fetch_object()){
				$tidArr[] = $r1->tid;
			}
			$rs1->free();

			$tidStr = implode(",",$tidArr);
			$sql = 'SELECT t.sciname, i.imgid, i.url, i.thumbnailurl, i.originalurl, i.caption, i.occid, i.photographer, CONCAT_WS(" ",u.firstname,u.lastname) AS photographerLinked '.
				'FROM images i LEFT JOIN users u ON i.photographeruid = u.uid '.
				'INNER JOIN taxstatus ts ON i.tid = ts.tid '.
				'INNER JOIN taxa t ON i.tid = t.tid '.
				'WHERE (ts.taxauthid = 1 AND ts.tidaccepted IN ('.$tidStr.')) AND i.SortSequence < 500 AND i.thumbnailurl IS NOT NULL ';
			if(!$this->displayLocality) $sql .= 'AND i.occid IS NULL ';
			$sql .= 'ORDER BY i.sortsequence, i.sortOccurrence LIMIT 100';
			/*
			$sql = 'SELECT t.sciname, i.imgid, i.url, i.thumbnailurl, i.originalurl, i.caption, i.occid, IFNULL(i.photographer,CONCAT_WS(" ",u.firstname,u.lastname)) AS photographer '.
				'FROM images i LEFT JOIN users u ON i.photographeruid = u.uid '.
				'INNER JOIN taxa t ON i.tid = t.tid '.
				'INNER JOIN taxstatus ts ON i.tid = ts.tid '.
				'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tid '.
				'INNER JOIN taxaenumtree e ON ts2.tid = e.tid '.
				'WHERE ts.taxauthid = 1 AND ts2.taxauthid = 1 AND e.taxauthid = 1 AND ts2.tid = ts2.tidaccepted AND e.parenttid = '.$this->tid.' AND i.SortSequence < 500 AND i.thumbnailurl IS NOT NULL ';
			if(!$this->displayLocality) $sql .= 'AND i.occid IS NULL ';
			$sql .= 'ORDER BY i.sortsequence LIMIT 100';
			*/
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$imgUrl = $row->url;
				if($imgUrl == 'empty') $imgUrl = '';
				if(!$imgUrl && $row->originalurl) $imgUrl = $row->originalurl;
				if(!$imgUrl) continue;
				$this->imageArr[$row->imgid]['url'] = $imgUrl;
				$this->imageArr[$row->imgid]['thumbnailurl'] = $row->thumbnailurl;
				if($row->photographerLinked) $this->imageArr[$row->imgid]['photographer'] = $row->photographerLinked;
				else $this->imageArr[$row->imgid]['photographer'] = $row->photographer;
				$this->imageArr[$row->imgid]['caption'] = $row->caption;
				$this->imageArr[$row->imgid]['occid'] = $row->occid;
				$this->imageArr[$row->imgid]['sciname'] = $row->sciname;
			}
			$result->free();
		}
	}

	public function getImageCount(){
		if(!isset($this->imageArr)) return 0;
		return count($this->imageArr);
	}

	//Map functions
	public function getMapArr($tidStr = 0){
		$maps = Array();
		if(!$tidStr){
			$tidArr = Array($this->tid,$this->submittedArr['tid']);
			if($this->synonymArr) $tidArr = array_merge($tidArr,array_keys($this->synonymArr));
			$tidStr = trim(implode(",",$tidArr),' ,');
		}
		if($tidStr){
			$sql = 'SELECT tm.url, t.sciname '.
					'FROM taxamaps tm INNER JOIN taxa t ON tm.tid = t.tid '.
					'WHERE (t.tid IN('.$tidStr.')) ORDER BY tm.initialtimestamp DESC';
			//echo $sql;
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$imgUrl = $row->url;
				if(array_key_exists("IMAGE_DOMAIN",$GLOBALS) && substr($imgUrl,0,1)=="/"){
					$imgUrl = $GLOBALS["IMAGE_DOMAIN"].$imgUrl;
				}
				$maps[] = $imgUrl;
			}
			$result->free();
		}
		return $maps;
	}

	public function getGoogleStaticMap($tidStr = 0){
		if(!$tidStr){
			$tidArr = Array($this->tid,$this->submittedArr['tid']);
			if($this->synonymArr) $tidArr = array_merge($tidArr,array_keys($this->synonymArr));
			$tidStr = trim(implode(",",$tidArr),' ,');
		}

		$mapArr = Array();
		if($tidStr){
			$minLat = 90;
			$maxLat = -90;
			$minLong = 180;
			$maxLong = -180;
			$latlonArr = array();
			if(isset($GLOBALS['MAPPING_BOUNDARIES'])){
				$latlonArr = explode(";",$GLOBALS['MAPPING_BOUNDARIES']);
			}

			$sqlBase = 'SELECT DecimalLatitude AS declat, DecimalLongitude AS declng FROM omoccurgeoindex WHERE (tid IN ('.$tidStr.')) ';
			/*
			$sqlBase = 'SELECT DISTINCT tidinterpreted, round(decimallatitude,2) AS declat, round(decimallongitude,2) AS declng '.
				'FROM omoccurrences '.
				'WHERE (tidinterpreted IN ('.$tidStr.')) AND (cultivationStatus IS NULL OR cultivationStatus = 0) AND (coordinateUncertaintyInMeters IS NULL OR coordinateUncertaintyInMeters < 10000) ';
			*/
			$sql = $sqlBase;
			if(count($latlonArr)==4){
				$sql .= 'AND (DecimalLatitude BETWEEN '.$latlonArr[2].' AND '.$latlonArr[0].') AND (DecimalLongitude BETWEEN '.$latlonArr[3].' AND '.$latlonArr[1].') ';
			}
			/*
			 else{
				$sql .= 'AND (DecimalLatitude BETWEEN -80 AND 80) AND (DecimalLongitude BETWEEN -160 AND 160) ';
			}
			*/
			$sql .= 'ORDER BY RAND() LIMIT 50';
			//echo "<div>".$sql."</div>"; exit;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$lat = round($row->declat,2);
				if($lat < $minLat) $minLat = $lat;
				if($lat > $maxLat) $maxLat = $lat;
				$long = round($row->declng,2);
				if($long < $minLong) $minLong = $long;
				if($long > $maxLong) $maxLong = $long;
				$mapArr[] = $lat.",".$long;
			}
			$result->free();
			if(count($mapArr) < 50 && $latlonArr){
				$result = $this->conn->query($sqlBase.' LIMIT 50');
				//$result = $this->conn->query($sqlBase.'AND (DecimalLatitude BETWEEN -80 AND 80) AND (DecimalLongitude BETWEEN -160 AND 160) LIMIT 50');
				while($row = $result->fetch_object()){
					$lat = round($row->declat,2);
					if($lat < $minLat) $minLat = $lat;
					if($lat > $maxLat) $maxLat = $lat;
					$long = round($row->declng,2);
					if($long < $minLong) $minLong = $long;
					if($long > $maxLong) $maxLong = $long;
					$mapArr[] = $lat.','.$long;
				}
				$result->free();
			}
			if(!$mapArr) return 0;
			$latDist = $maxLat - $minLat;
			$longDist = $maxLong - $minLong;

			$googleUrl = '//maps.googleapis.com/maps/api/staticmap?size=256x256&maptype=terrain';
			if(array_key_exists('GOOGLE_MAP_KEY',$GLOBALS) && $GLOBALS['GOOGLE_MAP_KEY']) $googleUrl .= '&key='.$GLOBALS['GOOGLE_MAP_KEY'];
			if($latDist < 3 || $longDist < 3) {
				$googleUrl .= '&zoom=6';
			}
		}
		$coordStr = implode('|',$mapArr);
		if(!$coordStr) return '';
		$googleUrl .= '&markers='.$coordStr;
		return $googleUrl;
	}

	//Taxon Descriptions
	private function getDescriptions(){
		$retArr = Array();
		if($this->tid){
			$rsArr = array();
			$sql = 'SELECT p.tdProfileID, IFNULL(d.caption, p.caption) as caption, IFNULL(d.source, p.publication) AS source, IFNULL(d.sourceurl, p.urlTemplate) AS sourceurl,
				IFNULL(d.displaylevel, p.defaultDisplayLevel) AS displaylevel, d.tid, d.tdbid, s.tdsid, s.heading, s.statement, s.displayheader, d.language, p.langid
				FROM taxadescrprofile p INNER JOIN taxadescrblock d ON p.tdProfileID = d.tdProfileID
				INNER JOIN taxadescrstmts s ON d.tdbid = s.tdbid
				LEFT JOIN adminlanguages l ON p.langid = l.langid ';
			if($this->acceptance){
				$sql .= 'INNER JOIN taxstatus ts ON ts.tid = d.tid WHERE (ts.tidaccepted = '.$this->tid.') AND (ts.taxauthid = '.$this->taxAuthId.') ';
			}
			else{
				$sql .= 'WHERE (d.tid = '.$this->tid.') ';
			}
			$sql .= 'ORDER BY p.defaultDisplayLevel, d.displayLevel, s.sortsequence';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_assoc()){
				$rsArr[] = $r;
			}
			$rs->free();

			//Get descriptions associated with target name only
			$usedCaptionArr = array();
			foreach($rsArr as $n => $rowArr){
				//if($rowArr['tid'] == $this->tid){
				if(!array_key_exists($rowArr['caption'], $usedCaptionArr) || $rowArr['caption'] != $rowArr['tdbid']){
					$indexKey = 0;
					if(!array_key_exists(strtolower($rowArr['language']), $this->langArr) && !in_array($rowArr['langid'], $this->langArr)){
						$indexKey = 1;
					}
					if(!isset($retArr[$indexKey]) || !array_key_exists($rowArr['tdbid'],$retArr[$indexKey])){
						$retArr[$indexKey][$rowArr['tdbid']]['caption'] = $rowArr['caption'];
						$retArr[$indexKey][$rowArr['tdbid']]['source'] = $rowArr['source'];
						$retArr[$indexKey][$rowArr['tdbid']]['url'] = $rowArr['sourceurl'];
					}
					$retArr[$indexKey][$rowArr['tdbid']]['desc'][$rowArr['tdsid']] = ($rowArr['displayheader'] && $rowArr['heading']?'<b>'.$rowArr['heading'].'</b>: ':'').$rowArr['statement'];
					$usedCaptionArr[$rowArr['caption']] = $rowArr['tdbid'];
				}
			}
			/*
			 //Then add description linked to synonyms ONLY if one doesn't exist with same caption
			 reset($rsArr);
			 foreach($rsArr as $n => $rowArr){
			 	if($rowArr['tid'] != $this->tid && !in_array($rowArr['caption'], $usedCaptionArr)){
					$indexKey = 0;
					if(array_key_exists(strtolower($rowArr['language']), $this->langArr) || in_array($rowArr['langid'], $this->langArr)){
						$indexKey = 1;
					}
					if(!isset($retArr[$indexKey]) || !array_key_exists($rowArr['tdbid'],$retArr[$indexKey])){
						$retArr[$indexKey][$rowArr['tdbid']]["caption"] = $rowArr['caption'];
						$retArr[$indexKey][$rowArr['tdbid']]["source"] = $rowArr['source'];
						$retArr[$indexKey][$rowArr['tdbid']]["url"] = $rowArr['sourceurl'];
					}
					$retArr[$indexKey][$rowArr['tdbid']]["desc"][$rowArr['tdsid']] = ($rowArr['displayheader'] && $rowArr['heading']?"<b>".$rowArr['heading']."</b>: ":"").$rowArr['statement'];
			 	}
			 }
			 ksort($retArr);
			*/
		}
		ksort($retArr);
		return $retArr;
	}

	public function getDescriptionTabs(){
		global $LANG;
		global $CALENDAR_TRAIT_PLOTS;
		$retStr = '';
		$descArr = $this->getDescriptions();
		$retStr .= '<div id="desctabs" class="ui-tabs" style="display:none">';
		$retStr .= '<ul class="ui-tabs-nav">';
		$capCnt = 1;
		foreach($descArr as $dArr){
			foreach($dArr as $id => $vArr){
				$cap = $vArr["caption"];
				if(!$cap){
					$cap = $LANG['DESCRIPTION'].' #'.$capCnt;
					$capCnt++;
				}
				$retStr .= '<li><a href="#tab'.$id.'">'.$cap.'</a></li>';
			}
		}
		if((isset($CALENDAR_TRAIT_PLOTS) && $CALENDAR_TRAIT_PLOTS > 0) && $this->rankId > 180) {
			$retStr .= '<li><a href="plottab.php?tid=' . htmlspecialchars($this->tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars(($LANG['CALENDAR_TRAIT_PLOT']?$LANG['CALENDAR_TRAIT_PLOT']:'Traits Plots'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
		}
		$retStr .= '<li><a href="resourcetab.php?tid=' . htmlspecialchars($this->tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars(($LANG['RESOURCES']?$LANG['RESOURCES']:'Resources'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
		$retStr .= '</ul>';
		foreach($descArr as $dArr){
			foreach($dArr as $id => $vArr){
				$retStr .= '<div id="tab'.$id.'" class="sptab">';
				if($vArr['source']){
					$retStr .= '<div id="descsource" style="float:right;">';
					if($vArr['url']){
						$retStr .= '<a href="'.$vArr['url'].'" target="_blank">';
					}
					$retStr .= $vArr['source'];
					if($vArr['url']){
						$retStr .= '</a>';
					}
					$retStr .= '</div>';
				}
				$descArr = $vArr['desc'];
				$retStr .= '<div style="clear:both;">';
				foreach($descArr as $tdsId => $stmt){
					$retStr .= $stmt.' ';
				}
				$retStr .= '</div>';
				$retStr .= '</div>';
			}
			$retStr .= '</div>';
		}
		return $retStr;
	}

	//Taxon Link functions
	private function setLinkArr(){
		if($this->linkArr === false && $this->tid){
			$this->linkArr = array();
			$sql = '(SELECT tlid, url, icon, title, notes, sortsequence
				FROM taxalinks l
				WHERE (l.tid = ' . $this->tid . ')
				UNION
				SELECT l.tlid, l.url, l.icon, l.title, l.notes, l.sortsequence
				FROM taxalinks l INNER JOIN taxaenumtree e ON l.tid = e.parenttid
				WHERE (e.tid = ' . $this->tid . '))
				ORDER BY sortsequence, title';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->linkArr[$r->tlid]['title'] = $r->title;
				$this->linkArr[$r->tlid]['url'] = str_replace('--SCINAME--',rawurlencode($this->taxonName),$r->url);
				$this->linkArr[$r->tlid]['icon'] = $r->icon;
				$this->linkArr[$r->tlid]['notes'] = $r->notes;
			}
			$rs->free();
		}
	}

	public function getRedirectLink(){
		$this->setLinkArr();
		if($this->linkArr){
			foreach($this->linkArr as $linkObj){
				if($linkObj['title'] == 'REDIRECT') return $linkObj['url'];
			}
		}
		return false;
	}

	public function getLinkArr(){
		if($this->linkArr === false) $this->setLinkArr();
		return $this->linkArr;
	}

	public function getResourceLinkArr(){
		$retArr = array();
		if($this->tid){
			$sql = 'SELECT taxaResourceID, sourceName, sourceIdentifier, sourceGuid, url, notes FROM taxaresourcelinks WHERE tid = '.$this->tid.' ORDER BY ranking, sourceName';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->taxaResourceID]['name'] = $r->sourceName;
				$retArr[$r->taxaResourceID]['id'] = $r->sourceIdentifier;
				$retArr[$r->taxaResourceID]['guid'] = $r->sourceGuid;
				$retArr[$r->taxaResourceID]['url'] = $r->url;
				$retArr[$r->taxaResourceID]['notes'] = $r->notes;
			}
			$rs->free();
		}
		return $retArr;
	}

	//Set children data for taxon higher than species level
	public function getSppArray($page, $taxaLimit, $pid, $clid){
		if(!$this->sppArray && $this->tid){
			$this->sppArray = Array();
			$start = ($page*$taxaLimit);
			$sql = '';
			if($clid && is_numeric($clid)){
				$sql = 'SELECT DISTINCT t.tid, t.sciname, t.securitystatus '.
					'FROM taxa t INNER JOIN taxaenumtree te ON t.tid = te.tid '.
					'INNER JOIN fmchklsttaxalink ctl ON ctl.TID = t.tid '.
					'WHERE (ctl.clid IN('.$this->getChildrenClid($clid).')) AND t.rankid = 220 AND (te.taxauthid = 1) AND (te.parenttid = '.$this->tid.') ';
			}
			elseif($pid && is_numeric($pid)){
				$sql = 'SELECT DISTINCT t.tid, t.sciname, t.securitystatus '.
					'FROM taxa t INNER JOIN taxaenumtree te ON t.tid = te.tid '.
					'INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted '.
					'INNER JOIN fmchklsttaxalink ctl ON ts.Tid = ctl.TID '.
					'INNER JOIN fmchklstprojlink cpl ON ctl.clid = cpl.clid '.
					'WHERE (ts.taxauthid = 1) AND (te.taxauthid = 1) AND (cpl.pid = '.$pid.') '.
					'AND (te.parenttid = '.$this->tid.') AND (t.rankid = 220) ';
			}
			else{
				$sql = 'SELECT DISTINCT t.sciname, t.tid, t.securitystatus '.
					'FROM taxa t INNER JOIN taxaenumtree te ON t.tid = te.tid '.
					'INNER JOIN taxstatus ts ON t.Tid = ts.tidaccepted '.
					'WHERE (te.taxauthid = 1) AND (ts.taxauthid = 1) AND (t.rankid = 220) AND (te.parenttid = '.$this->tid.') ';
			}
			$sql .= 'ORDER BY t.sciname LIMIT '.$start.','.($taxaLimit+1);
			//echo $sql; exit;

			$tids = Array();
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$sn = ucfirst(strtolower($row->sciname));
				$this->sppArray[$sn]['tid'] = $row->tid;
				$this->sppArray[$sn]['security'] = $row->securitystatus;
				$tids[] = $row->tid;
			}
			$result->free();

			//If no tids exist because there are no species in default project, grab all species from that taxon
			if(!$tids){
				$sql = 'SELECT DISTINCT t.sciname, t.tid, t.securitystatus '.
					'FROM taxa t INNER JOIN taxstatus ts ON t.Tid = ts.tid '.
					'INNER JOIN taxaenumtree te ON t.tid = te.tid '.
					'WHERE (te.taxauthid = 1) AND (ts.taxauthid = 1) AND (t.rankid = 220) AND (ts.tidaccepted = ts.tid) AND (te.parenttid = '.$this->tid.') '.
					'ORDER BY t.sciname LIMIT '.$start.','.($taxaLimit+1);
				//echo $sql;

				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$sn = ucfirst(strtolower($row->sciname));
					$this->sppArray[$sn]['tid'] = $row->tid;
					$this->sppArray[$sn]['security'] = $row->securitystatus;
					$tids[] = $row->tid;
				}
				$result->free();
			}

			if($tids){
				//Get Images
				$sql = 'SELECT t.sciname, t.tid, i.imgid, i.url, i.thumbnailurl, i.caption, i.photographer, CONCAT_WS(" ",u.firstname,u.lastname) AS photographerLinked '.
					'FROM images i INNER JOIN (SELECT ts1.tid, SUBSTR(MIN(CONCAT(LPAD(i.sortsequence,6,"0"),i.imgid)),7) AS imgid '.
					'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
					'INNER JOIN images i ON ts2.tid = i.tid '.
					'WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND (ts1.tid IN('.implode(',',$tids).')) AND (i.thumbnailurl IS NOT NULL) AND (i.url != "empty") '.
					'GROUP BY ts1.tid) i2 ON i.imgid = i2.imgid '.
					'INNER JOIN taxa t ON i2.tid = t.tid '.
					'LEFT JOIN users u ON i.photographeruid = u.uid ';
				//echo $sql;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$sciName = ucfirst(strtolower($r->sciname));
					if(!array_key_exists($sciName,$this->sppArray)){
						$firstPos = strpos($sciName," ",2)+2;
						$sciName = substr($sciName,0,strpos($sciName," ",$firstPos));
					}
					$this->sppArray[$sciName]['imgid'] = $r->imgid;
					$this->sppArray[$sciName]['url'] = $r->url;
					$this->sppArray[$sciName]['thumbnailurl'] = $r->thumbnailurl;
					if($r->photographerLinked) $this->sppArray[$sciName]['photographer'] = $r->photographerLinked;
					else $this->sppArray[$sciName]['photographer'] = $r->photographer;
					$this->sppArray[$sciName]['caption'] = $r->caption;
				}
				$rs->free();
			}

			//Get Maps, if rank is genus level or higher
			/* Deactivating display of static map to reduce Google charges
			if($this->rankId > 140){
				foreach($this->sppArray as $sn => $snArr){
					$tid = $snArr['tid'];
					if($mapArr = $this->getMapArr($tid)){
						$this->sppArray[$sn]["map"] = array_shift($mapArr);
					}
					else{
						$this->sppArray[$sn]["map"] = $this->getGoogleStaticMap($tid);
					}
				}
			}
			*/
		}
		return $this->sppArray;
	}

	//Misc functions
	private function getChildrenClid($clid){
		$clidArr = array($clid);
		$sqlBase = 'SELECT clidchild FROM fmchklstchildren WHERE clid != clidchild AND clid IN(';
		$sql = $sqlBase.$clid.')';
		do{
			$childStr = '';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$clidArr[] = $r->clidchild;
				$childStr .= ','.$r->clidchild;
			}
			$rs->free();
			$sql = $sqlBase.substr($childStr,1).')';
		}while($childStr);
		return implode(',',$clidArr);
	}

	public function taxonSearch($searchStr){
		$retArr = array();
		$sql = 'SELECT t.tid, ts.family, t.sciname, t.author, t.rankid, ts.parenttid '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (ts.taxauthid = ?) ';
		if(is_numeric($searchStr)){
			$sql .= 'AND (t.TID = ?) ';
		}
		else{
			$sql .= 'AND (t.SciName = ?) ';
		}
		$stmt = $this->conn->prepare($sql);
		if(is_numeric($searchStr)){
			$stmt->bind_param('is', $this->taxAuthId, $searchStr);
		}
		else{
			$stmt->bind_param('is', $this->taxAuthId, $searchStr);
		}
		$stmt->execute();
		$tid = 0;
		$family = '';
		$sciname = '';
		$author = '';
		$rankid = 0;
		$parentTid = 0;
		$stmt->bind_result($tid, $family, $sciname, $author, $rankid, $parentTid);
		while($stmt->fetch()){
			$retArr[$tid]['sciname'] = $sciname;
			$retArr[$tid]['family'] = $family;
			$retArr[$tid]['author'] = $author;
			$retArr[$tid]['rankid'] = $rankid;
			$retArr[$tid]['parenttid'] = $parentTid;
		}
		$stmt->close();

		if(count($retArr) > 1){
			//Get parents so that user can determine which taxon they are looking for
			$sql2 = 'SELECT e.tid, t.tid AS parenttid, t.sciname, t.rankid, ts.parenttid AS directparenttid '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.parenttid '.
				'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'WHERE (e.taxauthid = '.$this->taxAuthId.') AND (ts.taxauthid = '.$this->taxAuthId.') AND (e.tid IN('.implode(array_keys($retArr),',').'))';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$retArr[$tid]['parent'][$parentTid] = array('sciname' => $r2->sciname, 'rankid' => $r2->rankid, 'directparenttid' => $r2->directparenttid);
			}
			$rs2->free();
		}
		if(!$this->tid) $this->setTid(key($retArr));
		return $retArr;
	}

	public function getCloseTaxaMatches($testValue){
		$retArr = array();
		$sql = 'SELECT tid, sciname FROM taxa WHERE soundex(sciname) = soundex(?)';
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param('s', $testValue);
		$stmt->execute();
		$tid = 0;
		$sciname = '';
		$stmt->bind_result($tid, $sciname);
		while($stmt->fetch()){
			if($testValue != $sciname) $retArr[$tid] = $sciname;
		}
		$stmt->close();
		return $retArr;
	}

	/**
	* Gets occurrence counts of taxon in portal, to use in taxon profile
	* Searches for taxon and all its children
	* Checks taxon rank; counts turned off by default for anything above genus
	* $tid INTEGER taxon id
	* $taxonRank INTEGER taxon rank according to taxonunits table
	* $limitRank INTEGER
	* $collids ARRAY of collids to include in search
	*/
	public function getOccTaxonInDbCnt($limitRank = 170, $collidStr = 'all'){
		$count = -1;
		if ($this->rankId >= $limitRank) {
			//$sql = 'SELECT COUNT(o.occid) as cnt FROM omoccurrences o JOIN (SELECT DISTINCT e.tid, t.sciname FROM taxaenumtree e JOIN taxa t ON e.tid = t.tid WHERE parenttid = '.$this->tid.' OR e.tid = '.$this->tid.') AS parentAndChildren ON o.tidinterpreted = parentAndChildren.tid ';
			$sql = 'SELECT COUNT(o.occid) as cnt
				FROM omoccurrences o JOIN (SELECT DISTINCT ts.tid FROM taxaenumtree e JOIN taxa t ON e.tid = t.tid INNER JOIN taxstatus ts ON e.tid = ts.tidaccepted
				WHERE e.parenttid = '.$this->tid.' OR e.tid = '.$this->tid.') AS taxa ON o.tidinterpreted = taxa.tid ';
			if (preg_match('/^[,\d]+$/',$collidStr)) $sql .= 'AND o.collid IN('.$collidStr.')';
			$result = $this->conn->query($sql);
			while ($row = $result->fetch_object()){
				$count = $row->cnt;
			}
			$result->free();
		}
		return $count;
	}

	/**
	 * Returns link for specimen search (by taxon) if number of occurrences
	 * is within declared limit
	 * $tid INTEGER taxon id
	 * $searchUrl STRING customizable in taxon profile page
	 * $limitOccs INTEGER max number of occurrences in a search
	 */
	public function getSearchByTaxon($limitRank = 170, $collidStr = 'all', $limitOccs = 2000000){
		if($collidStr == 'neon') $collidStr = $this->getNeonCollidArr();
		$numOccs = $this->getOccTaxonInDbCnt($limitRank, $collidStr);
		$occMsg = '';
		if ((1 <= $numOccs) && ($numOccs <= $limitOccs)) {
			$occSrcUrl = '../collections/list.php?usethes=1&taxa='.$this->tid;
			if($collidStr != 'all') $occSrcUrl .= '&db='.$collidStr;
			$occMsg = '<a class="btn" href="' . htmlspecialchars($occSrcUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">Explore ' . htmlspecialchars(number_format($numOccs), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' occurrences</a>';
		} elseif ($numOccs > $limitOccs) {
			$occMsg = number_format($numOccs).' occurrences';
		} elseif ($numOccs == 0) {
			$occMsg = 'No occurrences found';
		} elseif ($numOccs == -1) {
			$occMsg = '';
		}
		return $occMsg;
	}

	private function getNeonCollidArr(){
		$retStr = array();
		$sql = 'SELECT GROUP_CONCAT(collid) as collidStr FROM omcollections WHERE institutionCode = "NEON"';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retStr = $r->collidStr;
		}
		$rs->free();
		return $retStr;
	}

	//Setters and getters
	public function getTid(){
		return $this->tid;
	}

	public function getTaxonName(){
		return $this->taxonName;
	}

	public function getTaxonAuthor(){
		return $this->taxonAuthor;
	}

	public function getTaxonFamily(){
		return $this->taxonFamily;
	}

	public function getSubmittedValue($k=0){
		return $this->submittedArr[$k];
	}

	public function setTaxAuthId($id){
		if(is_numeric($id)){
			$this->taxAuthId = $id;
		}
	}

	public function getRankId(){
		return $this->rankId;
	}

	public function getParentTid(){
		return $this->parentTid;
	}

	public function isAccepted(){
		return $this->acceptance;
	}

	public function isForwarded(){
		return $this->forwarded;
	}

	public function getAcceptedArr(){
		return $this->acceptedArr;
	}

	public function getSynonymArr(){
		return $this->synonymArr;
	}

	public function getDisplayLocality(){
		return $this->displayLocality;
	}

	public function getClName($clid){
		$clName = '';
		if(is_numeric($clid)){
			$sql = 'SELECT name FROM fmchecklists WHERE (clid = '.$clid.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$clName = $r->name;
			}
			$rs->free();
		}
		return $clName;
	}

	public function getParentChecklist($clid){
		$retArr = array();
		if($clid && is_numeric($clid)){
			//Direct parent checklist
			$sql = 'SELECT c.clid, c.name '.
				'FROM fmchecklists c INNER JOIN fmchklstchildren cp ON c.clid = cp.clid '.
				'WHERE cp.clid != cp.clidchild AND (cp.clidchild = '.$clid.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr[$r->clid] = $r->name;
			}
			$rs->free();
			if(!$retArr){
				//Most Inclusive Reference Checklist
				$sql = 'SELECT c.parentclid, cp.name '.
					'FROM fmchecklists c INNER JOIN fmchecklists cp ON cp.clid = c.parentclid '.
					'WHERE (c.CLID = '.$clid.')';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$retArr[$r->parentclid] = $r->name;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getProjName($pid){
		$projName = '';
		if($pid && is_numeric($pid)){
			$sql = 'SELECT projname FROM fmprojects WHERE (pid = '.$pid.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$projName = $r->projname;
			}
			$rs->free();
		}
		return $projName;
	}

	public function setLanguage($lang){
		$sql = 'SELECT langid, langname, iso639_1 FROM adminlanguages ';
		if(is_numeric($lang)) $sql .= 'WHERE langid = '.$lang;
		else $sql .= 'WHERE langname = "'.$this->cleanInStr($lang).'" OR iso639_1 = "'.$this->cleanInStr($lang).'"';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->langArr[strtolower($r->langname)] = $r->langid;
			$this->langArr[strtolower($r->iso639_1)] = $r->langid;
		}
		$rs->free();
	}
}
?>
