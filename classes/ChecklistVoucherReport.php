<?php
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');

class ChecklistVoucherReport extends ChecklistVoucherAdmin {

	private $missingTaxaCount = 0;

	function __construct($con = null) {
		parent::__construct($con);
	}

	function __destruct(){
		parent::__destruct();
	}

	//Listing function for tabs
	public function getVoucherCnt(){
		$vCnt = 0;
		if($this->clid){
			$sql = 'SELECT count(v.voucherID) AS vcnt FROM fmvouchers v INNER JOIN fmchklsttaxalink c ON v.clTaxaID = c.clTaxaID WHERE (c.clid = '.$this->clid.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$vCnt = $r->vcnt;
			}
			$rs->free();
		}
		return $vCnt;
	}

	public function getNonVoucheredCnt(){
		$uvCnt = 0;
		if($this->clid){
			$sql = 'SELECT count(c.clTaxaID) AS uvcnt
				FROM fmchklsttaxalink c LEFT JOIN fmvouchers v ON c.clTaxaID = v.clTaxaID
				WHERE v.clTaxaID IS NULL AND (c.clid = '.$this->clid.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$uvCnt = $r->uvcnt;
			}
			$rs->free();
		}
		return $uvCnt;
	}

	public function getNonVoucheredTaxa($startLimit, $limit = 100){
		$retArr = Array();
		if($this->clid){
			$sql = 'SELECT ctl.clTaxaID, t.tid, ts.family, TRIM(CONCAT_WS(" ", t.sciname, ctl.morphoSpecies)) as sciname
				FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
				INNER JOIN fmchklsttaxalink ctl ON t.tid = ctl.tid
				LEFT JOIN fmvouchers v ON ctl.clTaxaID = v.clTaxaID
				WHERE v.voucherID IS NULL AND (ctl.clid = '.$this->clid.') AND ts.taxauthid = 1
				ORDER BY ts.family, t.sciname
				LIMIT '.($startLimit ? $startLimit.',' : '') . $limit;
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->family][$r->clTaxaID]['s'] = $this->cleanOutStr($r->sciname);
				$retArr[$r->family][$r->clTaxaID]['t'] = $r->tid;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getNewVouchers($startLimit = 500, $includeAll = 1){
		$retArr = Array();
		if($this->clid){
			if($sqlFrag = $this->getSqlFrag()){
				if($includeAll == 1 || $includeAll == 2){
					$sql = 'SELECT DISTINCT cl.clTaxaID, TRIM(CONCAT_WS(" ",t.sciname,cl.morphoSpecies)) AS clsciname, o.occid, c.institutioncode, c.collectioncode, o.catalognumber, '.
						'o.tidinterpreted, o.sciname, o.recordedby, o.recordnumber, o.eventdate, CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
						'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid '.
						'INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
						'INNER JOIN fmchklsttaxalink cl ON ts.tidaccepted = cl.tid '.
						'INNER JOIN taxa t ON cl.tid = t.tid ';
					$sql .= $this->getTableJoinFrag($sqlFrag);
					$sql .= 'WHERE ('.$sqlFrag.') AND (cl.clid = '.$this->clid.') AND (ts.taxauthid = 1) ';
					if($includeAll == 1){
						$idStr = $this->getVoucherTidStr('tid');
						if($idStr) $sql .= 'AND cl.tid NOT IN('.$idStr.') ';
					}
					elseif($includeAll == 2){
						$idStr = $this->getVoucherOccidStr();
						if($idStr) $sql .= 'AND o.occid NOT IN('.$idStr.') ';
					}
					$sql .= 'ORDER BY ts.family, o.sciname LIMIT '.$startLimit.', 1000';
					//echo '<div>'.$sql.'</div>';
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						$retArr[$r->clTaxaID][$r->occid]['tid'] = $r->tidinterpreted;
						$sciName = $r->clsciname;
						if($r->clsciname <> $r->sciname) $sciName .= '<br/>specimen id: '.$r->sciname;
						$retArr[$r->clTaxaID][$r->occid]['sciname'] = $sciName;
						$collCode = '';
						if(!$r->catalognumber || strpos($r->catalognumber, $r->institutioncode) === false){
							$collCode = $r->institutioncode.($r->collectioncode?'-'.$r->collectioncode:'');
						}
						$collCode .= ($collCode?'-':'').($r->catalognumber?$r->catalognumber:'[catalog number null]');
						$retArr[$r->clTaxaID][$r->occid]['collcode'] = $collCode;
						$retArr[$r->clTaxaID][$r->occid]['recordedby'] = $r->recordedby;
						$retArr[$r->clTaxaID][$r->occid]['recordnumber'] = $r->recordnumber;
						$retArr[$r->clTaxaID][$r->occid]['eventdate'] = $r->eventdate;
						$retArr[$r->clTaxaID][$r->occid]['locality'] = $r->locality;
					}
				}
				elseif($includeAll == 3){
					$sql = 'SELECT DISTINCT cl.clTaxaID, TRIM(CONCAT_WS(" ",t.sciname,cl.morphoSpecies)) AS clsciname, o.occid, '.
						'c.institutioncode, c.collectioncode, o.catalognumber, '.
						'o.tidinterpreted, o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
						'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
						'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '.
						'LEFT JOIN taxa t ON o.tidinterpreted = t.TID '.
						'LEFT JOIN taxstatus ts ON t.TID = ts.tid ';
					$sql .= $this->getTableJoinFrag($sqlFrag);
					$sql .= 'WHERE ('.$sqlFrag.') AND ((t.RankId < 220)) ';
					$idStr = $this->getVoucherOccidStr();
					if($idStr) $sql .= 'AND (o.occid NOT IN('.$idStr.')) ';
					$sql .= 'ORDER BY o.family, o.sciname LIMIT '.$startLimit.', 500';
					//echo '<div>'.$sql.'</div>';
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						$retArr[$r->clTaxaID][$r->occid]['tid'] = $r->tidinterpreted;
						$sciName = $r->clsciname;
						if($r->clsciname <> $r->sciname) $sciName .= '<br/>specimen id: '.$r->sciname;
						$retArr[$r->clTaxaID][$r->occid]['sciname'] = $sciName;
						$collCode = '';
						if(!$r->catalognumber || strpos($r->catalognumber, $r->institutioncode) === false){
							$collCode = $r->institutioncode.($r->collectioncode?'-'.$r->collectioncode:'');
						}
						$collCode .= ($collCode?'-':'').($r->catalognumber?$r->catalognumber:'[catalog number null]');
						$retArr[$r->clTaxaID][$r->occid]['collcode'] = $collCode;
						$retArr[$r->clTaxaID][$r->occid]['recordedby'] = $r->recordedby;
						$retArr[$r->clTaxaID][$r->occid]['recordnumber'] = $r->recordnumber;
						$retArr[$r->clTaxaID][$r->occid]['eventdate'] = $r->eventdate;
						$retArr[$r->clTaxaID][$r->occid]['locality'] = $r->locality;
					}
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getMissingTaxa(){
		$retArr = Array();
		if($sqlFrag = $this->getSqlFrag()){
			$sql = 'SELECT DISTINCT t.tid, t.sciname, o.sciname AS occur_sciname '.$this->getMissingTaxaBaseSql($sqlFrag).' LIMIT 1000 ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$sciStr = $r->sciname;
				if($r->sciname != $r->occur_sciname) $sciStr .= ' (syn: '.$r->occur_sciname.')';
				$retArr[$r->tid] = $this->cleanOutStr($sciStr);
			}
			asort($retArr);
			$rs->free();
		}
		$this->missingTaxaCount = count($retArr);
		return $retArr;
	}

	public function getMissingTaxaSpecimens($limitIndex, $limitRange = 1000){
		$retArr = Array();
		if($sqlFrag = $this->getSqlFrag()){
			$sqlBase = $this->getMissingTaxaBaseSql($sqlFrag);
			$sql = 'SELECT DISTINCT o.occid, c.institutioncode ,c.collectioncode, o.catalognumber, o.tidinterpreted, t.sciname, o.sciname AS occur_sciname, '.
				'o.recordedby, o.recordnumber, o.eventdate, CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
				$sqlBase.' LIMIT '.($limitIndex?($limitIndex*1000).',':'').$limitRange;
			//echo '<div>'.$sql.'</div>';
			$cnt = 0;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->sciname][$r->occid]['o_sn'] = $r->occur_sciname;
				$retArr[$r->sciname][$r->occid]['tid'] = $r->tidinterpreted;
				$collCode = '';
				if(!$r->catalognumber || strpos($r->catalognumber, $r->institutioncode) === false){
					$collCode = $r->institutioncode.($r->collectioncode?'-'.$r->collectioncode:'');
				}
				$collCode .= ($collCode?'-':'').($r->catalognumber?$r->catalognumber:'[catalog number null]');
				$retArr[$r->sciname][$r->occid]['collcode'] = $collCode;
				$retArr[$r->sciname][$r->occid]['recordedby'] = $r->recordedby;
				$retArr[$r->sciname][$r->occid]['recordnumber'] = $r->recordnumber;
				$retArr[$r->sciname][$r->occid]['eventdate'] = $r->eventdate;
				$retArr[$r->sciname][$r->occid]['locality'] = $r->locality;
				$cnt++;
			}
			$rs->free();
			ksort($retArr);

			//Set missing taxa count
			if($cnt<1000){
				$sqlB = 'SELECT COUNT(DISTINCT ts.tidaccepted) as cnt '.$sqlBase;
				$rsB = $this->conn->query($sqlB);
				if($r = $rsB->fetch_object()){
					$this->missingTaxaCount = $r->cnt;
				}
				$rsB->free();
			}
			else $this->missingTaxaCount = '1000+';
		}
		return $retArr;
	}

	public function getConflictVouchers(){
		$retArr = Array();
		$clidStr = $this->getClidFullStr();
		if($clidStr){
			$sql = 'SELECT DISTINCT cl.tid, cl.clid, TRIM(CONCAT_WS(" ",t.sciname,cl.morphoSpecies)) AS listid, o.recordedby, o.recordnumber, o.sciname, o.identifiedby, o.dateidentified, o.occid '.
				'FROM taxstatus ts1 INNER JOIN omoccurrences o ON ts1.tid = o.tidinterpreted '.
				'INNER JOIN fmvouchers v ON o.occid = v.occid '.
				'INNER JOIN fmchklsttaxalink cl ON v.clTaxaID = cl.clTaxaID '.
				'INNER JOIN taxstatus ts2 ON cl.tid = ts2.tid '.
				'INNER JOIN taxa t ON cl.tid = t.tid '.
				'INNER JOIN taxstatus ts3 ON ts1.tidaccepted = ts3.tid '.
				'WHERE (cl.clid IN('.$clidStr.')) AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND ts1.tidaccepted <> ts2.tidaccepted '.
				'AND ts1.parenttid <> ts2.tidaccepted AND cl.tid <> o.tidinterpreted AND ts3.parenttid <> cl.tid '.
				'ORDER BY t.sciname ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			$cnt = 0;
			while($row = $rs->fetch_object()){
				$clSciname = $row->listid;
				$voucherSciname = $row->sciname;
				//if(str_replace($voucherSciname)) continue;
				$retArr[$cnt]['tid'] = $row->tid;
				$retArr[$cnt]['clid'] = $row->clid;
				$retArr[$cnt]['occid'] = $row->occid;
				$retArr[$cnt]['listid'] = $clSciname;
				$collStr = $row->recordedby;
				if($row->recordnumber) $collStr .= ' ('.$row->recordnumber.')';
				$retArr[$cnt]['recordnumber'] = $this->cleanOutStr($collStr);
				$retArr[$cnt]['specid'] = $this->cleanOutStr($voucherSciname);
				$idBy = $row->identifiedby;
				if($row->dateidentified) $idBy .= ' ('.$this->cleanOutStr($row->dateidentified).')';
				$retArr[$cnt]['identifiedby'] = $this->cleanOutStr($idBy);
				$cnt++;
			}
			$rs->free();
		}
		return $retArr;
	}

	//Export functions used within voucherreporthandler.php
	public function exportMissingOccurCsv(){
		if($sqlFrag = $this->getSqlFrag()){
			$fileName = 'Missing_'.$this->getExportFileName().'.csv';

			$fieldArr = $this->getOccurrenceFieldArr();
			$exportSql = 'SELECT '.implode(',',$fieldArr).', o.localitysecurity, o.collid '.
				$this->getMissingTaxaBaseSql($sqlFrag);
			//echo $exportSql;
			$this->exportCsv($fileName, $exportSql);
		}
	}

	private function getMissingTaxaBaseSql($sqlFrag){
		$clidStr = $this->getClidFullStr();
		if($clidStr){
			$retSql = 'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid '.
				'INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
				'INNER JOIN taxa t ON ts.tidaccepted = t.tid ';
			$retSql .= $this->getTableJoinFrag($sqlFrag);
			$retSql .= 'WHERE ('.$sqlFrag.') AND (t.rankid > 219) AND (ts.taxauthid = 1) ';
			$idStr = $this->getVoucherOccidStr();
			if($idStr) $retSql .= 'AND (o.occid NOT IN('.$idStr.')) ';
			$retSql .= 'AND (ts.tidaccepted NOT IN(SELECT ts.tidaccepted FROM fmchklsttaxalink cl INNER JOIN taxstatus ts ON cl.tid = ts.tid WHERE ts.taxauthid = 1 AND cl.clid IN('.$clidStr.'))) ';
		}
		return $retSql;
	}

	public function getMissingProblemTaxa(){
		$retArr = Array();
		if($sqlFrag = $this->getSqlFrag()){
			//Grab records
			$sql = 'SELECT DISTINCT o.occid, c.institutioncode, c.collectioncode, o.catalognumber, o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
				'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
				$this->getProblemTaxaSql($sqlFrag);
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$sciname = $r->sciname;
				if($sciname){
					$collCode = '';
					if(!$r->catalognumber || strpos($r->catalognumber, $r->institutioncode) === false){
						$collCode = $r->institutioncode.($r->collectioncode?'-'.$r->collectioncode:'');
					}
					$collCode .= ($collCode?'-':'').($r->catalognumber?$r->catalognumber:'[catalog number null]');
					$retArr[$sciname][$r->occid]['collcode'] = $collCode;
					$retArr[$sciname][$r->occid]['recordedby'] = $r->recordedby;
					$retArr[$sciname][$r->occid]['recordnumber'] = $r->recordnumber;
					$retArr[$sciname][$r->occid]['eventdate'] = $r->eventdate;
					$retArr[$sciname][$r->occid]['locality'] = $r->locality;
				}
			}
			$rs->free();
		}
		$this->missingTaxaCount = count($retArr);
		return $retArr;
	}

	public function exportProblemTaxaCsv(){
		$fileName = 'ProblemTaxa_'.$this->getExportFileName().'.csv';
		if($sqlFrag = $this->getSqlFrag()){
			$fieldArr = $this->getOccurrenceFieldArr();
			$sql = 'SELECT DISTINCT '.implode(',',$fieldArr).', o.localitysecurity, o.collid '.$this->getProblemTaxaSql($sqlFrag);
			$this->exportCsv($fileName, $sql);
		}
	}

	private function getProblemTaxaSql($sqlFrag){
		//$clidStr = $this->getClidFullStr();
		$retSql = 'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.CollID '.
			$this->getTableJoinFrag($sqlFrag).
			'WHERE ('.$sqlFrag.') AND (o.tidinterpreted IS NULL) AND (o.sciname IS NOT NULL) ';
		$idStr = $this->getVoucherOccidStr();
		if($idStr) $retSql .= 'AND (o.occid NOT IN('.$idStr.')) ';
		return $retSql;
	}

	private function getVoucherOccidStr(){
		$idArr = array();
		$clidStr = $this->getClidFullStr();
		if($clidStr){
			$sql = 'SELECT DISTINCT v.occid FROM fmvouchers v INNER JOIN fmchklsttaxalink c ON v.clTaxaID = c.clTaxaID WHERE c.clid IN('.$clidStr.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$idArr[] = $r->occid;
			}
			$rs->free();
		}
		return implode(',',$idArr);
	}

	private function getVoucherTidStr(){
		$idArr = array();
		$clidStr = $this->getClidFullStr();
		if($clidStr){
			$sql = 'SELECT DISTINCT tid FROM fmchklsttaxalink WHERE clid IN('.$clidStr.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$idArr[] = $r->tid;
			}
			$rs->free();
		}
		return implode(',',$idArr);
	}

	private function getTableJoinFrag($sqlFrag){
		$retSql = '';
		if(strpos($sqlFrag,'MATCH(f.recordedby)') || strpos($sqlFrag,'MATCH(f.locality)')){
			$retSql .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
		}
		if(strpos($sqlFrag,'p.point')){
			$retSql .= 'INNER JOIN omoccurpoints p ON o.occid = p.occid ';
		}
		return $retSql;
	}

	public function downloadChecklistCsv(){
		if($this->clid){
			$clidStr = $this->getClidFullStr();
			if($clidStr){
				$fileName = $this->getExportFileName().'.csv';
				$sql = 'SELECT DISTINCT ctl.clid, t.tid AS taxonID, IFNULL(ctl.familyoverride, ts.family) AS family,
					t.sciName AS scientificNameBase, TRIM(CONCAT_WS(" ",t.sciName,ctl.morphoSpecies)) AS scientificName, t.author AS scientificNameAuthorship,
					ctl.habitat AS habitat, ctl.abundance, ctl.notes, ctl.source, ctl.internalNotes
					FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid
					INNER JOIN fmchklsttaxalink ctl ON ctl.tid = t.tid
					WHERE (ts.taxauthid = 1) AND (ctl.clid IN('.$clidStr.'))
					ORDER BY ctl.familyoverride, ts.family, t.sciName';
				header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header ('Content-Type: text/csv');
				header ('Content-Disposition: attachment; filename="'.$fileName.'"');
				$rs = $this->conn->query($sql);
				if($rs->num_rows){
					$headerArr = array();
					$fields = mysqli_fetch_fields($rs);
					foreach ($fields as $val) {
						$headerArr[] = $val->name;
					}
					$out = fopen('php://output', 'w');
					fputcsv($out, $headerArr);
					$rowOut = null;
					while($row = $rs->fetch_assoc()){
						foreach($row as $k => $v){
							$row[$k] = strip_tags($v);
						}
						if($rowOut){
							if($rowOut['taxonID'] == $row['taxonID']){
								if($row['clid'] == $this->clid){
									$rowOut = $row;
								}
							}
							else{
								$this->encodeArr($rowOut);
								fputcsv($out, $rowOut);
								$rowOut = $row;
							}
						}
						else $rowOut = $row;
					}
					if($rowOut) fputcsv($out, $rowOut);
					$rs->free();
					fclose($out);
				}
				else{
					echo "Recordset is empty.\n";
				}
			}
		}
	}

	public function downloadVoucherCsv(){
		if($this->clid){
			$fileName = $this->getExportFileName().'.csv';

			$fieldArr = array('t.tid AS taxonID');
			$fieldArr[] = 'IFNULL(ctl.familyoverride,ts.family) AS family';
			$fieldArr[] = 't.sciname AS scientificNameBase';
			$fieldArr[] = 'TRIM(CONCAT_WS(" ", t.sciname, ctl.morphoSpecies)) as sciname';
			$fieldArr[] = 't.author AS scientificNameAuthorship';
			$fieldArr[] = 'ctl.habitat AS cl_habitat';
			$fieldArr[] = 'ctl.abundance';
			$fieldArr[] = 'ctl.notes';
			$fieldArr[] = 'ctl.source';
			$fieldArr[] = 'ctl.internalnotes';
			$fieldArr = array_merge($fieldArr,$this->getOccurrenceFieldArr());

			$clidStr = $this->getClidFullStr();
			$sql = 'SELECT DISTINCT '.implode(',',$fieldArr).', o.localitysecurity, o.collid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'INNER JOIN fmchklsttaxalink ctl ON ctl.tid = t.tid '.
				'LEFT JOIN fmvouchers v ON ctl.clTaxaID = v.clTaxaID '.
				'LEFT JOIN omoccurrences o ON v.occid = o.occid '.
				'LEFT JOIN omcollections c ON o.collid = c.collid '.
				'WHERE (ts.taxauthid = 1) AND (ctl.clid IN('.$clidStr.')) ';
			$this->exportCsv($fileName, $sql);
		}
	}

	public function downloadAllOccurrenceCsv(){
		if($this->clid){
			$fileName = $this->getExportFileName().'.csv';
			if($sqlFrag = $this->getSqlFrag()){
				$fieldArr = array('t.tid AS taxonID');
				$fieldArr[] = 'IFNULL(ctl.familyoverride,ts.family) AS family';
				$fieldArr[] = 't.sciname AS scientificNameBase';
				$fieldArr[] = 'TRIM(CONCAT_WS(" ", t.sciname, ctl.morphoSpecies)) as sciname';
				$fieldArr[] = 't.author AS scientificNameAuthorship';
				$fieldArr[] = 'ctl.habitat AS cl_habitat';
				$fieldArr[] = 'ctl.abundance';
				$fieldArr[] = 'ctl.notes';
				$fieldArr[] = 'ctl.source';
				$fieldArr[] = 'ctl.internalnotes';
				$fieldArr = array_merge($fieldArr,$this->getOccurrenceFieldArr());

				$clidStr = $this->getClidFullStr();
				$sql = 'SELECT DISTINCT '.implode(',',$fieldArr).', o.localitysecurity, o.collid '.
					'FROM fmchklsttaxalink ctl INNER JOIN taxa t ON ctl.tid = t.tid '.
					'INNER JOIN taxstatus ts ON ctl.tid = ts.tid '.
					'LEFT JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
					'LEFT JOIN omoccurrences o ON ts2.tid = o.tidinterpreted '.
					'LEFT JOIN omcollections c ON o.collid = c.collid '.
					$this->getTableJoinFrag($sqlFrag).
					'WHERE ('.$sqlFrag.') AND (ts.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ctl.clid IN('.$clidStr.')) ';
				$this->exportCsv($fileName, $sql);
			}
		}
	}

	private function exportCsv($fileName, $sql){
		header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ('Content-Disposition: attachment; filename="'.$fileName.'"');
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$headerArr = array();
			$fields = mysqli_fetch_fields($rs);
			foreach ($fields as $val) {
				$headerArr[] = $val->name;
			}
			$localitySecurityFields = array('recordNumber','eventDate','locality','decimalLatitude','decimalLongitude','minimumElevationInMeters','minimumElevationInMeters','habitat','occurrenceRemarks');
			$rareSpeciesReader = $this->isRareSpeciesReader();
			$out = fopen('php://output', 'w');
			fputcsv($out, $headerArr);
			while($row = $rs->fetch_assoc()){
				$localSecurity = ($row["localitysecurity"]?$row["localitysecurity"]:0);
				if(!$rareSpeciesReader && $localSecurity != 1 && (!array_key_exists('RareSppReader', $GLOBALS['USER_RIGHTS']) || !in_array($row['collid'],$GLOBALS['USER_RIGHTS']['RareSppReader']))){
					$redactStr = '';
					foreach($localitySecurityFields as $fieldName){
						if($row[$fieldName]) $redactStr .= ','.$fieldName;
					}
					if($redactStr) $row['informationWithheld'] = 'Fields with redacted values (e.g. rare species localities):'.trim($redactStr,', ');
				}
				$this->encodeArr($row);
				fputcsv($out, $row);
			}
			$rs->free();
			fclose($out);
		}
		else{
			echo "Recordset is empty.\n";
		}
	}

	protected function getExportFileName(){
		$fileName = $this->clName;
		if($fileName){
			$fileName = str_replace(Array('.',' ',':','&','"',"'",'(',')','[',']'),'',$fileName);
			if(strlen($fileName) > 20){
				$nameArr = explode(' ',$fileName);
				foreach($nameArr as $k => $w){
					if(strlen($w) > 3) $nameArr[$k] = substr($w,0,4);
				}
				$fileName = implode('',$nameArr);
			}
		}
		else{
			$fileName = 'symbiota';
		}
		$fileName .= '_'.time();
		return $fileName;
	}

	private function getOccurrenceFieldArr(){
		$retArr = array('o.family AS family_occurrence', 'o.sciName AS scientificName_occurrence', 'IFNULL(o.institutionCode,c.institutionCode) AS institutionCode','IFNULL(o.collectionCode,c.collectionCode) AS collectionCode',
			'CASE guidTarget WHEN "symbiotaUUID" THEN IFNULL(o.occurrenceID,o.recordID) WHEN "occurrenceId" THEN o.occurrenceID WHEN "catalogNumber" THEN o.catalogNumber ELSE "" END AS occurrenceID',
			'o.catalogNumber', 'o.otherCatalogNumbers', 'o.identifiedBy', 'o.dateIdentified',
 			'o.recordedBy', 'o.recordNumber', 'o.eventDate', 'o.country', 'o.stateProvince', 'o.county', 'o.municipality', 'o.locality',
 			'o.decimalLatitude', 'o.decimalLongitude', 'o.coordinateUncertaintyInMeters', 'o.minimumElevationInMeters', 'o.maximumelevationinmeters',
			'o.verbatimelevation', 'o.habitat', 'o.occurrenceRemarks', 'o.associatedTaxa', 'o.reproductivecondition', 'o.informationWithheld', 'o.occid');
		$retArr[] = 'o.recordID AS recordID';
		$retArr[] = 'CONCAT("' . $this->getDomain() . $GLOBALS['CLIENT_ROOT'] . '/collections/individual/index.php?occid=",o.occid) as `references`';
		return $retArr;

		/*
		return array('family'=>'o.family','scientificName'=>'o.sciName AS scientificName_occurrence','institutionCode'=>'IFNULL(o.institutionCode,c.institutionCode) AS institutionCode',
			'collectionCode'=>'IFNULL(o.collectionCode,c.collectionCode) AS collectionCode','occurrenceID'=>'o.occurrenceID',
			'catalogNumber'=>'o.catalogNumber','identifiedBy'=>'o.identifiedBy','dateIdentified'=>'o.dateIdentified',
			'recordedBy'=>'o.recordedBy','recordNumber'=>'o.recordNumber','eventDate'=>'o.eventDate','country'=>'o.country',
			'stateProvince'=>'o.stateProvince','county'=>'o.county','municipality'=>'o.municipality','locality'=>'o.locality',
			'decimalLatitude'=>'o.decimalLatitude','decimalLongitude'=>'o.decimalLongitude','coordinateUncertaintyInMeters'=>'o.coordinateUncertaintyInMeters','minimumElevationInMeters'=>'o.minimumElevationInMeters',
			'maximumElevationInMeters'=>'o.maximumelevationinmeters','verbatimElevation'=>'o.verbatimelevation',
			'habitat'=>'o.habitat','occurrenceRemarks'=>'o.occurrenceRemarks','associatedTaxa'=>'o.associatedTaxa',
			'reproductiveCondition'=>'o.reproductivecondition','informationWithheld'=>'o.informationWithheld','occid'=>'o.occid');
		*/
	}

	//Misc fucntions
	public function getMissingTaxaCount(){
		return $this->missingTaxaCount;
	}

	private function isRareSpeciesReader(){
		$canReadRareSpp = false;
		if($GLOBALS['IS_ADMIN']
			|| array_key_exists("CollAdmin", $GLOBALS['USER_RIGHTS'])
			|| array_key_exists("RareSppAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppReadAll", $GLOBALS['USER_RIGHTS'])){
			$canReadRareSpp = true;
		}
		return $canReadRareSpp;
	}
}
?>