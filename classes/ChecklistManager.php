<?php
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');

class ChecklistManager extends Manager{

	private $clid;
	private $dynClid;
	private $clName;
	private $clMetadata;
	private $childClidArr = array();
	private $voucherArr = array();
	private $pid = '';
	private $projName = '';
	private $taxaList = array();
	private $langId;
	private $thesFilter = 0;
	private $taxonFilter;
	private $showAuthors = false;
	private $showCommon = false;
	private $showSynonyms = false;
	private $showImages = false;
	private $limitImagesToVouchers = false;
	private $showVouchers = false;
	private $showAlphaTaxa = false;
	private $searchCommon = false;
	private $searchSynonyms = true;
	private $filterArr = array();
	private $imageLimit = 100;
	private $taxaLimit = 500;
	private $speciesCount = 0;
	private $taxaCount = 0;
	private $familyCount = 0;
	private $genusCount = 0;
	private $basicSql;

	function __construct() {
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	public function setClid($clid){
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

	public function setDynClid($id){
		if(is_numeric($id)){
			$this->dynClid = $id;
			$this->setDynamicMetaData();
		}
	}

	private function setMetaData(){
		if($this->clid){
			$sql = 'SELECT c.clid, c.name, c.locality, c.publication, c.abstract, c.authors, c.parentclid, c.notes, '.
				'c.latcentroid, c.longcentroid, c.pointradiusmeters, c.footprintwkt, c.access, c.defaultSettings, '.
				'c.dynamicsql, c.datelastmodified, c.uid, c.type, c.initialtimestamp '.
				'FROM fmchecklists c WHERE (c.clid = '.$this->clid.')';
		 	$result = $this->conn->query($sql);
			if($result){
		 		if($row = $result->fetch_object()){
					$this->clName = $row->name;
					$this->clMetadata["locality"] = $row->locality;
					$this->clMetadata["notes"] = $row->notes;
					$this->clMetadata["type"] = $row->type;
					$this->clMetadata["publication"] = $row->publication;
					$this->clMetadata["abstract"] = $row->abstract;
					$this->clMetadata["authors"] = $row->authors;
					$this->clMetadata["parentclid"] = $row->parentclid;
					$this->clMetadata["uid"] = $row->uid;
					$this->clMetadata["latcentroid"] = $row->latcentroid;
					$this->clMetadata["longcentroid"] = $row->longcentroid;
					$this->clMetadata["pointradiusmeters"] = $row->pointradiusmeters;
					$this->clMetadata['footprintwkt'] = $row->footprintwkt;
					$this->clMetadata["access"] = $row->access;
					$this->clMetadata["defaultSettings"] = $row->defaultSettings;
					$this->clMetadata["dynamicsql"] = $row->dynamicsql;
					$this->clMetadata["datelastmodified"] = $row->datelastmodified;
				}
				$result->free();
			}
			else{
				trigger_error('ERROR: unable to set checklist metadata => '.$sql, E_USER_ERROR);
			}
			//Temporarly needed as a separate call until db_schema_patch-1.1.sql is applied
			$sql = 'SELECT headerurl FROM fmchecklists WHERE (clid = '.$this->clid.')';
			$rs = $this->conn->query($sql);
			if($rs){
				if($r = $rs->fetch_object()){
					$this->clMetadata['headerurl'] = $r->headerurl;
				}
				$rs->free();
			}
		}
	}

	private function setDynamicMetaData(){
		if($this->dynClid){
			$sql = 'SELECT name, details, uid, type, initialtimestamp FROM fmdynamicchecklists WHERE (dynclid = '.$this->dynClid.')';
			$result = $this->conn->query($sql);
			if($result){
				if($row = $result->fetch_object()){
					$this->clName = $row->name;
					$this->clMetadata['notes'] = $row->details;
					$this->clMetadata['type'] = $row->type;
					$this->clMetadata['locality'] = '';
				}
				$result->free();
			}
			else{
				trigger_error('ERROR: unable to set dynamic checklist metadata => '.$sql, E_USER_ERROR);
			}
		}
	}

	public function getClMetaData(){
		if(!$this->clMetadata){
			if($this->clid) $this->setMetaData();
			else $this->setDynamicMetaData();
		}
		return $this->clMetadata;
	}

	public function getParentChecklist(){
		$parentArr = array();
		if($this->clid){
			$sql = 'SELECT cl.clid, cl.name FROM fmchecklists cl INNER JOIN fmchklstchildren ch ON cl.clid = ch.clid WHERE ch.clidchild = '.$this->clid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$parentArr[$r->clid] = $r->name;
			}
			$rs->free();
		}
		return $parentArr;
	}

	public function getExclusionChecklist(){
		$excludeArr = array();
		if($this->clid){
			$sql = 'SELECT cl.clid, cl.name FROM fmchklstchildren ch INNER JOIN fmchecklists cl ON ch.clidchild = cl.clid WHERE (cl.type = "excludespp") AND ch.clid = '.$this->clid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$excludeArr[$r->clid] = $r->name;
			}
			$rs->free();
		}
		return $excludeArr;
	}

	public function echoFilterList(){
		echo "'".implode("','",$this->filterArr)."'";
	}

	public function getTaxonAuthorityList(){
		$taxonAuthList = Array();
		$sql = "SELECT ta.taxauthid, ta.name FROM taxauthority ta WHERE (ta.isactive <> 0)";
 		$rs = $this->conn->query($sql);
		while ($row = $rs->fetch_object()){
			$taxonAuthList[$row->taxauthid] = $row->name;
		}
		$rs->free();
		return $taxonAuthList;
	}

	//return an array: family => array(TID => sciName)
	public function getTaxaList($pageNumber = 1,$retLimit = 500){
		if(!$this->clid && !$this->dynClid) return array();
		//Get species list
		$speciesPrev="";
		$taxonPrev="";
		$genusCntArr = Array();
		$familyCntArr = Array();
		$speciesRankNeededArr = array();
		if($this->showImages && $retLimit) $retLimit = $this->imageLimit;
		if(!$this->basicSql) $this->setClSql();
		$result = $this->conn->query($this->basicSql);
		while($row = $result->fetch_object()){
			$family = strtoupper($row->family);
			if($row->rankid > 140 && !$family) $family = 'Incertae Sedis';
			$this->filterArr[$family] = '';
			$taxonGroup = $family;
			if($this->showAlphaTaxa) $taxonGroup = $row->unitname1;
			$tid = $row->tid;
			$sciName = $this->cleanOutStr($row->sciname);
			$taxonTokens = explode(" ",$sciName);
			if(in_array("x",$taxonTokens) || in_array("X",$taxonTokens)){
				if(in_array("x",$taxonTokens)) unset($taxonTokens[array_search("x",$taxonTokens)]);
				if(in_array("X",$taxonTokens)) unset($taxonTokens[array_search("X",$taxonTokens)]);
				$newArr = array();
				foreach($taxonTokens as $v){
					$newArr[] = $v;
				}
				$taxonTokens = $newArr;
			}
			if(!$retLimit || ($this->taxaCount >= (($pageNumber-1)*$retLimit) && $this->taxaCount <= ($pageNumber)*$retLimit)){
			    if(isset($row->morphospecies) && $row->morphospecies) $sciName .= ' '.$row->morphospecies;
				elseif($row->rankid == 180) $sciName .= " sp.";
				if($row->rankid > 220 && $this->clMetadata['type'] != 'rarespp' && !array_key_exists($row->parenttid, $this->taxaList)){
					$this->taxaList[$row->parenttid]['taxongroup'] = '<i>'.$taxonGroup.'</i>';
					$this->taxaList[$row->parenttid]['family'] = $family;
					//$this->taxaList[$row->parenttid]['clid'] = $row->clid;
					$speciesRankNeededArr[] = $row->parenttid;
				}
				if($this->showVouchers){
					$clStr = "";
					if($row->habitat) $clStr = ", ".$row->habitat;
					if($row->abundance) $clStr .= ", ".$row->abundance;
					if($row->notes) $clStr .= ", ".$row->notes;
					if($row->source) $clStr .= ", <u>source</u>: ".$row->source;
					if($clStr) $this->taxaList[$tid]["notes"] = substr($clStr,2);
				}
				$this->taxaList[$tid]['sciname'] = $sciName;
				$this->taxaList[$tid]['family'] = $family;
				$this->taxaList[$tid]['taxongroup'] = '<i>'.$taxonGroup.'</i>';
				if(isset($this->taxaList[$tid]['clid'])){
					if($this->taxaList[$tid]['clid'] != $row->clid) $this->taxaList[$tid]['clid'] = $this->taxaList[$tid]['clid'].','.$row->clid;
				}
				else $this->taxaList[$tid]['clid'] = $row->clid;
				if($this->showAuthors) $this->taxaList[$tid]['author'] = $this->cleanOutStr($row->author);
			}
			if(!in_array($family,$familyCntArr)){
				$familyCntArr[] = $family;
			}
			if(!in_array($taxonTokens[0],$genusCntArr)){
				$genusCntArr[] = $taxonTokens[0];
			}
			$this->filterArr[$taxonTokens[0]] = "";
			if(count($taxonTokens) > 1 && $taxonTokens[0]." ".$taxonTokens[1] != $speciesPrev){
				$this->speciesCount++;
				$speciesPrev = $taxonTokens[0]." ".$taxonTokens[1];
			}
			if(!$taxonPrev || strpos($sciName,$taxonPrev) === false){
				$this->taxaCount++;
			}
			$taxonPrev = implode(" ",$taxonTokens);
		}
		$this->familyCount = count($familyCntArr);
		$this->genusCount = count($genusCntArr);
		$this->filterArr = array_keys($this->filterArr);
		sort($this->filterArr);
		$result->free();
		if($this->taxaCount < (($pageNumber-1)*$retLimit)){
			$this->taxaCount = 0; $this->genusCount = 0; $this->familyCount = 0;
			unset($this->filterArr);
			$this->filterArr = array();
			return $this->getTaxaList(1,$retLimit);
		}
		if($this->taxaList){
			if($this->showVouchers){
				//Get voucher data; note that dynclid list won't have vouchers
				$clidStr = $this->clid;
				if($this->childClidArr){
					$clidStr .= ','.implode(',',array_keys($this->childClidArr));
				}
				$vSql = 'SELECT DISTINCT cl.tid, v.occid, c.institutioncode, v.notes, o.catalognumber, o.othercatalognumbers, o.recordedby, o.recordnumber, o.eventdate, o.collid
					FROM fmvouchers v INNER JOIN fmchklsttaxalink cl ON v.clTaxaID = cl.clTaxaID
					INNER JOIN omoccurrences o ON v.occid = o.occid
					INNER JOIN omcollections c ON o.collid = c.collid
					WHERE (cl.clid IN ('.$clidStr.')) AND cl.tid IN('.implode(',',array_keys($this->taxaList)).')
					ORDER BY o.collid';
				if($this->thesFilter){
					$vSql = 'SELECT DISTINCT ts.tidaccepted AS tid, v.occid, c.institutioncode, v.notes, o.catalognumber, o.othercatalognumbers, o.recordedby, o.recordnumber, o.eventdate, o.collid
						FROM fmvouchers v INNER JOIN fmchklsttaxalink cl ON v.clTaxaID = cl.clTaxaID
						INNER JOIN omoccurrences o ON v.occid = o.occid
						INNER JOIN omcollections c ON o.collid = c.collid
						INNER JOIN taxstatus ts ON cl.tid = ts.tid
						WHERE (ts.taxauthid = '.$this->thesFilter.') AND (cl.clid IN ('.$clidStr.'))
						AND (ts.tidaccepted IN('.implode(',',array_keys($this->taxaList)).'))
						ORDER BY o.collid';
				}
				//echo $vSql; exit;
		 		$vResult = $this->conn->query($vSql);
				while ($row = $vResult->fetch_object()){
					$displayStr = ($row->recordedby?$row->recordedby:($row->catalognumber?$row->catalognumber:$row->othercatalognumbers));
					if(strlen($displayStr) > 25){
						//Collector string is too big, thus reduce
						$strPos = strpos($displayStr,';');
						if(!$strPos) $strPos = strpos($displayStr,',');
						if(!$strPos) $strPos = strpos($displayStr,' ',10);
						if($strPos) $displayStr = substr($displayStr,0,$strPos).'...';
					}
					if($row->recordnumber) $displayStr .= ' '.$row->recordnumber;
					else $displayStr .= ' '.$row->eventdate;
					if(!trim($displayStr)) $displayStr = 'undefined voucher';
					$displayStr .= ' ['.$row->institutioncode.']';
					$this->voucherArr[$row->tid][$row->occid] = trim($displayStr);
				}
				$vResult->free();
			}
			if($this->showImages) $this->setImages();
			if($this->showCommon) $this->setVernaculars();
			if($this->showSynonyms) $this->setSynonyms();
			if($speciesRankNeededArr && $this->clMetadata['type'] != 'rarespp'){
				//Get species ranked taxa that are not explicited linked into checklist
				$sql = 'SELECT tid, sciname, author FROM taxa WHERE tid IN('.implode(',',$speciesRankNeededArr).')';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$this->taxaList[$r->tid]['sciname'] = $r->sciname;
					if($this->showAuthors) $this->taxaList[$r->tid]['author'] = $r->author;
				}
				$rs->free();
			}
		}
		return $this->taxaList;
	}

	private function setImages(){
		if($this->taxaList){
			$matchedArr = array();
			if($this->limitImagesToVouchers){
				$sql = 'SELECT i.tid, i.url, i.thumbnailurl, i.originalurl
					FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid
					INNER JOIN fmvouchers v ON o.occid = v.occid
					INNER JOIN fmchklsttaxalink cl ON v.clTaxaID = cl.clTaxaID
					WHERE (cl.clid = 2) AND (i.tid IN('.implode(',',array_keys($this->taxaList)).'))';
				$matchedArr = $this->setImageSubset($sql);
			}
			if($missingArr = array_diff(array_keys($this->taxaList),$matchedArr)){
				$sql = 'SELECT i2.tid, i.url, i.thumbnailurl FROM images i INNER JOIN '.
					'(SELECT ts1.tid, SUBSTR(MIN(CONCAT(LPAD(i.sortsequence,6,"0"),i.imgid)),7) AS imgid '.
					'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
					'INNER JOIN images i ON ts2.tid = i.tid '.
					'WHERE i.sortsequence < 500 AND (i.thumbnailurl IS NOT NULL) AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND (ts1.tid IN('.implode(',',$missingArr).')) '.
					'GROUP BY ts1.tid) i2 ON i.imgid = i2.imgid';
				$matchedArr = $this->setImageSubset($sql);
				if($missingArr = array_diff(array_keys($this->taxaList),$matchedArr)){
					//Get children images
					$sql = 'SELECT i2.tid, i.url, i.thumbnailurl FROM images i INNER JOIN '.
						'(SELECT ts1.parenttid AS tid, SUBSTR(MIN(CONCAT(LPAD(i.sortsequence,6,"0"),i.imgid)),7) AS imgid '.
						'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
						'INNER JOIN images i ON ts2.tid = i.tid '.
						'WHERE i.sortsequence < 500 AND (i.thumbnailurl IS NOT NULL) AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND (ts1.parenttid IN('.implode(',',$missingArr).')) '.
						'GROUP BY ts1.tid) i2 ON i.imgid = i2.imgid';
					$this->setImageSubset($sql);
				}
			}
		}
	}

	private function setImageSubset($sql){
		$matchTidArr = array();
		if($this->taxaList){
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if(!in_array($r->tid,$matchTidArr)){
					$this->taxaList[$r->tid]['url'] = $r->url;
					$this->taxaList[$r->tid]['tnurl'] = $r->thumbnailurl;
					$matchTidArr[$r->tid] = $r->tid;
				}
			}
			$rs->free();
		}
		return $matchTidArr;
	}

	private function setVernaculars(){
		if($this->taxaList){
			$sql = 'SELECT ts1.tid, v.vernacularname '.
				'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN taxavernaculars v ON ts2.tid = v.tid '.
				'WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND (ts1.tid IN('.implode(',',array_keys($this->taxaList)).')) ';
			if($this->langId) $sql .= 'AND v.langid = '.$this->langId.' ';
			$sql .= 'ORDER BY v.sortsequence DESC ';
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->vernacularname) $this->taxaList[$r->tid]['vern'] = $this->cleanOutStr($r->vernacularname);
			}
			$rs->free();
		}
	}

	private function setSynonyms(){
		if($this->taxaList){
			$tempArr = array();
			$sql = 'SELECT ts.tid, t.sciname, t.author '.
				'FROM taxstatus ts INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN taxa t ON ts2.tid = t.tid '.
				'WHERE (ts.taxauthid = '.($this->thesFilter?$this->thesFilter:'1').') AND (ts2.taxauthid = '.($this->thesFilter?$this->thesFilter:'1').') '.
				'AND (ts.tid IN('.implode(',',array_keys($this->taxaList)).')) AND (ts.tid != ts2.tid) '.
				'ORDER BY t.sciname';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$tempArr[$r->tid][] = '<i>'.$r->sciname.'</i>'.($this->showAuthors && $r->author?' '.$r->author:'');
			}
			$rs->free();
			foreach($tempArr as $k => $vArr){
				$this->taxaList[$k]['syn'] = implode(', ',$vArr);
			}
		}
	}

	public function getVoucherCoordinates($limit=0){
		$retArr = array();
		if(!$this->basicSql) $this->setClSql();
		if($this->clid){
			//Add children checklists to query
			$clidStr = $this->clid;
			if($this->childClidArr) $clidStr .= ','.implode(',',array_keys($this->childClidArr));

			//Grab general points
			$retCnt = 0;
			$sql1 = 'SELECT DISTINCT cc.tid, t.sciname, cc.decimallatitude, cc.decimallongitude, cc.notes '.
				'FROM fmchklstcoordinates cc INNER JOIN ('.$this->basicSql.') t ON cc.tid = t.tid '.
				'WHERE cc.clid IN ('.$clidStr.') AND cc.decimallatitude BETWEEN -90 AND 90 AND cc.decimallongitude  BETWEEN -180 AND 180 ';
			if($limit) $sql1 .= 'ORDER BY RAND() LIMIT '.$limit;
			//echo $sql1;
			$rs1 = $this->conn->query($sql1);
			if($rs1){
				while($r1 = $rs1->fetch_object()){
					if($limit){
						$retArr[] = $r1->decimallatitude.','.$r1->decimallongitude;
					}
					else{
						$retArr[$r1->tid][] = array('ll'=>$r1->decimallatitude.','.$r1->decimallongitude,'sciname'=>$this->cleanOutStr($r1->sciname),'notes'=>$this->cleanOutStr($r1->notes));
					}
					$retCnt++;
				}
				$rs1->free();
			}
			else echo 'ERROR getting general coordinates: '.$this->conn->error;

			if(!$limit || $retCnt < 50){
				//Grab voucher points
				$sql2 = 'SELECT DISTINCT cl.tid, o.occid, o.decimallatitude, o.decimallongitude, CONCAT(o.recordedby," (",IFNULL(o.recordnumber,o.eventdate),")") as notes
					FROM omoccurrences o INNER JOIN fmvouchers v ON o.occid = v.occid
					INNER JOIN fmchklsttaxalink cl ON v.clTaxaID = cl.clTaxaID
					INNER JOIN ('.$this->basicSql.') t ON cl.tid = t.tid
					WHERE cl.clid IN ('.$clidStr.') AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL
					AND (o.localitysecurity = 0 OR o.localitysecurity IS NULL) ';
				if($limit) $sql2 .= 'ORDER BY RAND() LIMIT '.$limit;
				//echo $sql2;
				$rs2 = $this->conn->query($sql2);
				if($rs2){
					while($r2 = $rs2->fetch_object()){
						if($limit){
							$retArr[] = $r2->decimallatitude.','.$r2->decimallongitude;
						}
						else{
							$retArr[$r2->tid][] = array('ll'=>$r2->decimallatitude.','.$r2->decimallongitude,'notes'=>$this->cleanOutStr($r2->notes),'occid'=>$r2->occid);
						}
					}
					$rs2->free();
				}
				//else echo 'ERROR getting voucher coordinates: '.$this->conn->error;
			}
		}
		return $retArr;
	}

	public function getPolygonCoordinates(){
		$retArr = array();
		if($this->clid){
			if($this->clMetadata['dynamicsql']){
				$sql = 'SELECT o.decimallatitude, o.decimallongitude FROM omoccurrences o ';
				if($this->clMetadata['footprintwkt'] && substr($this->clMetadata['footprintwkt'],0,7) == 'POLYGON'){
					$sql .= 'INNER JOIN omoccurpoints p ON o.occid = p.occid WHERE (ST_Within(p.point,GeomFromText("'.$this->clMetadata['footprintwkt'].'"))) ';
				}
				else{
					$voucherManager = new ChecklistVoucherAdmin($this->conn);
					$voucherManager->setClid($this->clid);
					$voucherManager->setCollectionVariables();
					$sql .= 'WHERE ('.$voucherManager->getSqlFrag().') ';
				}
				$sql .= 'LIMIT 50';
				//echo $sql; exit;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$retArr[] = $r->decimallatitude.','.$r->decimallongitude;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function downloadChecklistCsv(){
		if(!$this->basicSql) $this->setClSql();
		//Output checklist
		$fileName = $this->clName."_".time().".csv";
		header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\"");
		$this->showAuthors = 1;
		if($taxaArr = $this->getTaxaList(1,0)){
			$fh = fopen('php://output', 'w');
			$headerArr = array('Family','ScientificName','ScientificNameAuthorship');
			if($this->showCommon) $headerArr[] = 'CommonName';
			$headerArr[] = 'Notes';
			$headerArr[] = 'TaxonId';
			fputcsv($fh,$headerArr);
			foreach($taxaArr as $tid => $tArr){
				$outArr = array($tArr['family']);
				$outArr[] = html_entity_decode($tArr['sciname'],ENT_QUOTES|ENT_XML1);
				$outArr[] = html_entity_decode($tArr['author'],ENT_QUOTES|ENT_XML1);
				if($this->showCommon) $outArr[] = (array_key_exists('vern',$tArr)?html_entity_decode($tArr['vern'],ENT_QUOTES|ENT_XML1):'');
				$outArr[] = (array_key_exists('notes',$tArr)?strip_tags(html_entity_decode($tArr['notes'],ENT_QUOTES|ENT_XML1)):'');
				$outArr[] = $tid;
				fputcsv($fh,$outArr);
			}
			fclose($fh);
		}
		else{
			echo "Recordset is empty.\n";
		}
	}

	private function setClSql(){
		if($this->clid){
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',array_keys($this->childClidArr));
			}
			$this->basicSql = 'SELECT t.tid, ctl.clid, t.sciname, t.author, ctl.morphospecies, t.unitname1, t.rankid, ctl.habitat, ctl.abundance, ctl.notes, ctl.source, ts.parenttid, ';
			if($this->thesFilter){
				$this->basicSql .= 'ts2.family FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted '.
					'INNER JOIN fmchklsttaxalink ctl ON ts.tid = ctl.tid '.
					'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tid '.
			  		'WHERE (ts.taxauthid = '.$this->thesFilter.') AND (ctl.clid IN ('.$clidStr.')) ';
			}
			else{
				$this->basicSql .= 'IFNULL(ctl.familyoverride,ts.family) AS family FROM taxa t INNER JOIN fmchklsttaxalink ctl ON t.tid = ctl.tid '.
					'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			  		'WHERE (ts.taxauthid = 1) AND (ctl.clid IN ('.$clidStr.')) ';
			}
		}
		else{
			$this->basicSql = 'SELECT t.tid, ctl.dynclid as clid, ts.family, t.sciname, t.author, t.unitname1, t.rankid, ts.parenttid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'INNER JOIN fmdyncltaxalink ctl ON t.tid = ctl.tid '.
		  		'WHERE (ts.taxauthid = '.($this->thesFilter?$this->thesFilter:'1').') AND (ctl.dynclid = '.$this->dynClid.') ';
		}
		if($this->taxonFilter){
			if($this->searchCommon){
				$this->basicSql .= 'AND ts.tidaccepted IN(SELECT ts2.tidaccepted FROM taxavernaculars v INNER JOIN taxstatus ts2 ON v.tid = ts2.tid WHERE (v.vernacularname LIKE "%'.$this->taxonFilter.'%")) ';
			}
			else{
				//Search direct name, which is particularly good for a genera term
				$sqlWhere = 'OR (t.SciName Like "'.$this->taxonFilter.'%") ';
				if($this->clid && (substr($this->taxonFilter,-5) == 'aceae' || substr($this->taxonFilter,-4) == 'idae')){
					//Include taxn filter in familyoverride
					$sqlWhere .= "OR (ctl.familyoverride = '".$this->taxonFilter."') ";
				}
				if($this->searchSynonyms){
					$sqlWhere .= "OR (ts.tidaccepted IN(SELECT ts2.tidaccepted FROM taxa t2 INNER JOIN taxstatus ts2 ON t2.tid = ts2.tid WHERE (t2.sciname Like '".$this->taxonFilter."%') ";
					//if(substr_count($this->taxonFilter,' ') > 1) $sqlWhere .= 'AND (t2.rankid = 220 OR ts2.tid = ts2.tidaccepted) ';
					$sqlWhere .= ")) ";
				}
				//Include parents
				$sqlWhere .= 'OR (t.tid IN(SELECT e.tid '.
					'FROM taxa t3 INNER JOIN taxaenumtree e ON t3.tid = e.parenttid '.
					'WHERE (e.taxauthid = '.($this->thesFilter?$this->thesFilter:'1').') AND (t3.sciname = "'.$this->taxonFilter.'")))';
				if($sqlWhere) $this->basicSql .= 'AND ('.substr($sqlWhere,2).') ';
			}
		}
		if($this->showAlphaTaxa){
			$this->basicSql .= " ORDER BY sciname";
		}
		else{
			$this->basicSql .= " ORDER BY family, sciname";
		}
		//echo $this->basicSql; exit;
	}

	//Checklist editing functions
	public function addNewSpecies($postArr){
		if(!$this->clid) return 'ERROR adding species: checklist identifier not set';
		$insertStatus = false;
		$dataArr = array('tid','familyoverride','morphospecies','habitat','abundance','notes','source','internalnotes');
		$colSql = '';
		$valueSql = '';
		foreach($dataArr as $v){
			if(isset($postArr[$v]) && $postArr[$v]){
				$colSql .= ','.$v;
				if(is_numeric($postArr[$v])) $valueSql .= ','.$postArr[$v];
				else $valueSql .= ',"'.$this->cleanInStr($postArr[$v]).'"';
			}
		}
		$conn = MySQLiConnectionFactory::getCon('write');
		$sql = 'INSERT INTO fmchklsttaxalink (clid'.$colSql.') VALUES ('.$this->clid.$valueSql.')';
		if($conn->query($sql)){
			if($this->clMetadata['type'] == 'rarespp' && $this->clMetadata['locality'] && is_numeric($postArr['tid'])){
				$sqlRare = 'UPDATE omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
					'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
					'SET o.localitysecurity = 1 '.
					'WHERE (o.localitysecurity IS NULL OR o.localitysecurity = 0) AND (o.localitySecurityReason IS NULL) '.
					'AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (o.stateprovince = "'.$this->clMetadata['locality'].'") AND (ts2.tid = '.$postArr['tid'].')';
				//echo $sqlRare; exit;
				$conn->query($sqlRare);
			}
		}
		else{
			$mysqlErr = $conn->error;
			$insertStatus = 'ERROR adding species: ';
			if(strpos($mysqlErr,'Duplicate') !== false){
				$insertStatus .= 'Species already exists within checklist';
			}
			else{
				$insertStatus .= $conn->error;
			}
		}
		$conn->close();
		return $insertStatus;
	}

	//Checklist index page fucntions
	public function getChecklists($limitToKey=false){
		$retArr = Array();
		$sql = 'SELECT p.pid, p.projname, p.ispublic, c.clid, c.name, c.access, c.defaultSettings, COUNT(l.tid) AS sppcnt
			FROM fmchecklists c LEFT JOIN fmchklstprojlink cpl ON c.clid = cpl.clid
			INNER JOIN fmchklsttaxalink l ON c.clid = l.clid
			LEFT JOIN fmprojects p ON cpl.pid = p.pid
			WHERE ((c.access LIKE "public%") ';
		if(isset($GLOBALS['USER_RIGHTS']['ClAdmin']) && $GLOBALS['USER_RIGHTS']['ClAdmin']) $sql .= 'OR (c.clid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ClAdmin']).'))';
		$sql .= ') AND ((p.pid IS NULL) OR (p.ispublic = 1) ';
		if(isset($GLOBALS['USER_RIGHTS']['ProjAdmin']) && $GLOBALS['USER_RIGHTS']['ProjAdmin']) $sql .= 'OR (p.pid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ProjAdmin']).'))';
		$sql .= ') ';
		if($this->pid) $sql .= 'AND (p.pid = '.$this->pid.') ';
		$sql .= 'GROUP BY p.projname, c.Name HAVING sppcnt > 10';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			if($limitToKey){
				if($row->defaultSettings && strpos($row->defaultSettings,'"activatekey":0')) continue;
			}
			if($row->pid){
				$pid = $row->pid;
				$projName = $row->projname.(!$row->ispublic?' (Private)':'');
			}
			else{
				$pid = 0;
				$projName = 'Miscellaneous Inventories';
			}
			$retArr[$pid]['name'] = $this->cleanOutStr($projName);
			$retArr[$pid]['clid'][$row->clid] = $this->cleanOutStr($row->name).($row->access=='private'?' (Private)':'');
		}
		$rs->free();
		if(isset($retArr[0])){
			$tempArr = $retArr[0];
			unset($retArr[0]);
			$retArr[0] = $tempArr;
		}
		return $retArr;
	}

	public function getResearchPoints(){
		$retArr = array();
		$sql = 'SELECT c.clid, c.name, c.latcentroid, c.longcentroid '.
			'FROM fmchecklists c LEFT JOIN fmchklstprojlink cpl ON c.CLID = cpl.clid '.
			'LEFT JOIN fmprojects p ON cpl.pid = p.pid '.
			'WHERE (c.latcentroid IS NOT NULL) AND (c.longcentroid IS NOT NULL) ';
		if($this->pid) $sql .= 'AND (p.pid = '.$this->pid.') ';
		else $sql .= 'AND (p.pid IS NULL) ';
		$sql .= 'AND ((c.access LIKE "public%") ';
		if(isset($GLOBALS['USER_RIGHTS']['ClAdmin']) && $GLOBALS['USER_RIGHTS']['ClAdmin']) $sql .= 'OR (c.clid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ClAdmin']).'))';
		$sql .= ') ';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->clid]['name'] = $this->cleanOutStr($row->name);
			$retArr[$row->clid]['lat'] = $row->latcentroid;
			$retArr[$row->clid]['lng'] = $row->longcentroid;
		}
		$rs->free();
		return $retArr;
	}

	//Taxon suggest functions
	public function getTaxonSearch($term, $clid, $deep=0){
		$retArr = array();
		$term = preg_replace('/\s{1}[\D]{1}\s{1}/i', ' _ ', trim($term));
		$term = preg_replace('/[^a-zA-Z_\-\. ]+/', '', $term);
		if(!is_numeric($clid)) $clid = 0;
		if($term && $clid){
			$sql = '(SELECT t.sciname '.
				'FROM taxa t INNER JOIN fmchklsttaxalink cl ON t.tid = cl.tid '.
				'WHERE t.sciname LIKE "'.$term.'%" AND cl.clid = '.$clid.') ';
			if($deep){
				$sql .= 'UNION DISTINCT '.
					'(SELECT DISTINCT t.sciname '.
					'FROM fmchklsttaxalink cl INNER JOIN taxaenumtree e ON cl.tid = e.tid '.
					'INNER JOIN taxa t ON e.parenttid = t.tid '.
					'WHERE e.taxauthid = 1 AND t.sciname LIKE "'.$term.'%" AND cl.clid = '.$clid.')';
			}
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[] = $r->sciname;
			}
			$rs->free();
			sort($retArr);
		}
		return $retArr;
	}

	public function getSpeciesSearch($term){
		$retArr = array();
		$term = preg_replace('/[^a-zA-Z\-\. ]+/', '', $term);
		$term = preg_replace('/\s{1}x{1}\s{0,1}$/i', ' _ ', $term);
		$term = preg_replace('/\s{1}[\D]{1}\s{1}/i', ' _ ', $term);
		if($term){
			$sql = 'SELECT tid, sciname FROM taxa WHERE (rankid > 179) AND (sciname LIKE "'.$term.'%")';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[] = $r->sciname;
			}
			$rs->free();
		}
		return $retArr;
	}

	//Setters and getters
	public function setThesFilter($filt){
		if(is_numeric($filt)) $this->thesFilter = $filt;
	}

	public function getThesFilter(){
		return $this->thesFilter;
	}

	public function setTaxonFilter($tFilter){
		$term = preg_replace('/[^a-zA-Z\-\. ]+/', '', $tFilter);
		$this->taxonFilter = preg_replace('/\s{1}[\D]{1}\s{1}/i', ' _ ', $term);
	}

	public function setShowAuthors($bool){
		if($bool) $this->showAuthors = true;
	}

	public function setShowCommon($bool){
		if($bool) $this->showCommon = true;
	}

	public function setShowSynonyms($bool){
		if($bool) $this->showSynonyms = true;
	}

	public function setShowImages($bool){
		if($bool) $this->showImages = true;
	}

	public function setLimitImagesToVouchers($bool){
		if($bool) $this->limitImagesToVouchers = true;
	}

	public function setShowVouchers($bool){
		if($bool) $this->showVouchers = true;
	}

	public function setShowAlphaTaxa($bool){
		if($bool) $this->showAlphaTaxa = true;
	}

	public function setSearchCommon($bool){
		if($bool) $this->searchCommon = true;
	}

	public function setSearchSynonyms($bool){
		if($bool) $this->searchSynonyms = true;
	}

	public function getClid(){
		return $this->clid;
	}

	public function getChildClidArr(){
		return $this->childClidArr;
	}

	public function getVoucherArr(){
		return $this->voucherArr;
	}

	public function getClName(){
		return $this->clName;
	}

	public function setProj($pid){
		$this->setPid($pid);
	}

	public function setPid($pid){
		if(is_numeric($pid)){
			$sql = 'SELECT pid, projname FROM fmprojects WHERE (pid = '.$pid.')';
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()){
					$this->pid = $r->pid;
					$this->projName = $this->cleanOutStr($r->projname);
				}
				$rs->free();
			}
			else{
				trigger_error('ERROR: Unable to project => SQL: '.$sql, E_USER_WARNING);
			}
		}
		return $this->pid;
	}

	public function getProjName(){
		return $this->projName;
	}

	public function getPid(){
		return $this->pid;
	}

	public function setLanguage($l){
		if(is_numeric($l)) $this->langId = $l;
		else{
			$sql = 'SELECT langid FROM adminlanguages WHERE langname = "'.$this->cleanInStr($l).'" OR iso639_1 = "'.$this->cleanInStr($l).'"';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->langId = $r->langid;
			}
			$rs->free();
		}
	}

	public function setImageLimit($cnt){
		$this->imageLimit = $cnt;
	}

	public function getImageLimit(){
		return $this->imageLimit;
	}

	public function setTaxaLimit($cnt){
		$this->taxaLimit = $cnt;
	}

	public function getTaxaLimit(){
		return $this->taxaLimit;
	}

	public function getTaxaCount(){
		return $this->taxaCount;
	}

	public function getFamilyCount(){
		return $this->familyCount;
	}

	public function getGenusCount(){
		return $this->genusCount;
	}

	public function getSpeciesCount(){
		return $this->speciesCount;
	}

	//Misc functions
	public function cleanOutText($str){
		//Need to clean for MS Word ouput: strip html tags, convert all html entities and then reset as html tags
		$str = strip_tags($str);
		$str = html_entity_decode($str);
		$str = htmlspecialchars($str);
		return $str;
	}
}
?>