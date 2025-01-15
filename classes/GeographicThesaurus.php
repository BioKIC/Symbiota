<?php
include_once($SERVER_ROOT . '/classes/Manager.php');
include_once($SERVER_ROOT . '/classes/utilities/QueryUtil.php');

class GeographicThesaurus extends Manager {

	function __construct(){
		parent::__construct(null, 'write');
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getGeograpicList($conditionTerm = null){
		$retArr = array();
		$sql = 'SELECT t.geoThesID, t.geoTerm, t.abbreviation, t.iso2, t.iso3, t.numCode, t.category, t.geoLevel, t.termStatus, t.acceptedID, a.geoterm as acceptedTerm
		FROM geographicthesaurus t LEFT JOIN geographicthesaurus a ON t.acceptedID = a.geoThesID ';
		if($conditionTerm && is_numeric($conditionTerm)) $sql .= 'WHERE (t.parentID = '.$conditionTerm.') ';
		else $sql .= 'WHERE (t.parentID IS NULL) ';
		$sql .= 'ORDER BY t.geoTerm';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->geoThesID]['geoTerm'] = $r->geoTerm;
			$retArr[$r->geoThesID]['abbreviation'] = $r->abbreviation;
			$retArr[$r->geoThesID]['iso2'] = $r->iso2;
			$retArr[$r->geoThesID]['iso3'] = $r->iso3;
			$retArr[$r->geoThesID]['numCode'] = $r->numCode;
			$retArr[$r->geoThesID]['category'] = $r->category;
			$retArr[$r->geoThesID]['geoLevel'] = $r->geoLevel;
			$retArr[$r->geoThesID]['termStatus'] = $r->termStatus;
			$retArr[$r->geoThesID]['acceptedID'] = $r->acceptedID;
			$retArr[$r->geoThesID]['acceptedTerm'] = $r->acceptedTerm;
		}
		$rs->free();

		if($retArr){
			$childCntArr = $this->setChildCnt(implode(',',array_keys($retArr)));
			foreach($childCntArr as $id => $cnt){
				$retArr[$id]['childCnt'] = $cnt;
			}
		}
		return $retArr;
	}

	public function getGeograpicUnit($geoThesID){
		$retArr = array();
		if(is_numeric($geoThesID)){
			$sql = 'SELECT t.geoThesID, t.geoTerm, t.abbreviation, t.iso2, t.iso3, t.numCode, t.category, t.geoLevel, t.parentID, p.geoTerm as parentTerm, t.notes, t.termStatus,
				t.acceptedID, a.geoterm as acceptedTerm, gp.footprintWKT as wkt, gp.geoJSON
				FROM geographicthesaurus t LEFT JOIN geographicthesaurus a ON t.acceptedID = a.geoThesID
				LEFT JOIN geographicthesaurus p ON t.parentID = p.geoThesID
				LEFT JOIN geographicpolygon gp ON t.geoThesID = gp.geoThesID
				WHERE t.geoThesID = ?';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param('i', $geoThesID);
				$stmt->execute();
				$rs = $stmt->get_result();
				while($r = $rs->fetch_object()){
					$retArr['geoThesID'] = $r->geoThesID;
					$retArr['geoTerm'] = $r->geoTerm;
					$retArr['abbreviation'] = $r->abbreviation;
					$retArr['iso2'] = $r->iso2;
					$retArr['iso3'] = $r->iso3;
					$retArr['numCode'] = $r->numCode;
					$retArr['category'] = $r->category;
					$retArr['geoLevel'] = $r->geoLevel;
					$retArr['acceptedID'] = $r->acceptedID;
					$retArr['acceptedTerm'] = $r->acceptedTerm;
					$retArr['parentID'] = $r->parentID;
					$retArr['parentTerm'] = $r->parentTerm;
					$retArr['notes'] = $r->notes;
					$retArr['termStatus'] = $r->termStatus;
					$retArr['wkt'] = $r->wkt;
					$retArr['geoJSON'] = $r->geoJSON;
				}
				$rs->free();
				$stmt->close();
			}
			if($retArr){
				$childArr = $this->setChildCnt($retArr['geoThesID']);
				$cnt = 0;
				if($childArr) $cnt = current($childArr);
				$retArr['childCnt'] = $cnt;
				if(!$retArr['acceptedID']) $retArr['synonyms'] = $this->getSynonyms($geoThesID);
			}
		}
		return $retArr;
	}

	private function getSynonyms($geoThesID){
		$retArr = array();
		$sql = 'SELECT geoThesID, geoTerm FROM geographicthesaurus WHERE acceptedID = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $geoThesID);
			$stmt->execute();
			$rs = $stmt->get_result();
			while($r = $rs->fetch_object()){
				$retArr[$r->geoThesID] = $r->geoTerm;
			}
		}
		return $retArr;
	}

	public function editGeoUnit($postArr){
		if(!is_numeric($postArr['geoThesID'])) {
			$this->errorMessage = 'ERROR editing geoUnit: geographic thesaurus id must be numeric';
			return false;
		} else {
			$postArr['geoThesID'] = intval($postArr['geoThesID']);
		}

		if(!$postArr['geoTerm']){
			$this->errorMessage = 'ERROR editing geoUnit: geographic term must have a value';
			return false;
		}

		$sql = <<<'SQL'
		UPDATE geographicthesaurus SET geoterm = ?, abbreviation = ?, iso2 = ?, iso3 = ?,
		numcode = ?, geoLevel = ?, acceptedID = ?, parentID = ?, notes = ?
		WHERE geoThesID = ?
		SQL;

		try {
			QueryUtil::executeQuery($this->conn,$sql, [
				$postArr['geoTerm'],
				empty($postArr['abbreviation'])? null: $postArr['abbreviation'],
				empty($postArr['iso2'])? null: $postArr['iso2'],
				empty($postArr['iso3'])? null: $postArr['iso3'],
				empty($postArr['numcode'])? null: $postArr['numcode'],
				empty($postArr['geoLevel'])? null: intval($postArr['geoLevel']),
				empty($postArr['acceptedID'])? null: $postArr['acceptedID'],
				empty($postArr['parentID'])? null: $postArr['parentID'],
				empty($postArr['notes'])? null: $postArr['notes'],
				$postArr['geoThesID']
			]);
		} catch (\Throwable $th) {
			$this->errorMessage = 'ERROR saving edits: '.$this->conn->error;
			return false;
		}

		if(!empty($postArr['polygon'])) {
			$sql = <<<'SQL'
			SELECT * from geographicpolygon WHERE geoThesID = ?
			SQL;

			$polygon_exists = QueryUtil::executeQuery($this->conn,$sql, [htmlspecialchars($postArr['geoThesID'])]);

			if(!$polygon_exists) {
				$this->errorMessage = 'ERROR saving polygon edits: '.$this->conn->error;
				return false;
			}

			if($polygon_exists->num_rows <= 0) {
				$this->addPolygon($postArr['geoThesID'], $postArr['polygon']);
			} else {
				$this->updatePolygon($postArr['geoThesID'], $postArr['polygon']);
			}
		} else {
			$this->deletePolygon($postArr['geoThesID']);
		}

		return true;
	}

	private function addPolygon($geoThesID, $polygon): bool {
		//Needs a stored procedured because of packet's being too large issue
		$sql = <<<'SQL'
		CALL insertGeographicPolygon(?, ?);
		SQL;
		try {
			QueryUtil::executeQuery($this->conn,$sql, [$geoThesID, $polygon]);
			return true;
		} catch (\Throwable $e) {
			$this->errorMessage = 'ERROR saving new polygon: ' . $e->getMessage();
			return false;
		}
	}

	private function updatePolygon($geoThesID, $polygon) {
		$sql = <<<'SQL'
		CALL updateGeographicPolygon(?, ?);
		SQL;
		try {
			QueryUtil::executeQuery($this->conn,$sql, [$geoThesID, $polygon]);
			return true;
		} catch (\Throwable $e) {
			$this->errorMessage = 'ERROR updatePolygon on '. $geoThesID .':' . $e->getMessage();
			return false;
		}
	}

	private function deletePolygon($geoThesID) {
		$sql = <<<'SQL'
		DELETE FROM geographicpolygon WHERE geoThesID = ?
		SQL;

		try {
			QueryUtil::executeQuery($this->conn,$sql, [$geoThesID]);
			return true;
		} catch (\Throwable $e) {
			$this->errorMessage = 'ERROR deletePolygon on '. $geoThesID . ':' . $e->getMessage();
			return false;
		}
	}

	public function addGeoUnit($postArr){
		try {
			if(!$postArr['geoTerm']){
				$this->errorMessage = 'ERROR adding geoUnit: geographic term must have a value';
				return false;
			}
			else{
				$sql = 'INSERT INTO geographicthesaurus(geoterm, abbreviation, iso2, iso3, numcode, geoLevel, acceptedID, parentID, notes) '.
					'VALUES("'.$this->cleanInStr($postArr['geoTerm']).'", '.
					($postArr['abbreviation']?'"'.$this->cleanInStr($postArr['abbreviation']).'"':'NULL').', '.
					($postArr['iso2']?'"'.$this->cleanInStr($postArr['iso2']).'"':'NULL').', '.
					($postArr['iso3']?'"'.$this->cleanInStr($postArr['iso3']).'"':'NULL').', '.
					(is_numeric($postArr['numCode'])?'"'.$this->cleanInStr($postArr['numCode']).'"':'NULL').', '.
					(is_numeric($postArr['geoLevel'])?$this->cleanInStr($postArr['geoLevel']):'NULL').', '.
					(is_numeric($postArr['acceptedID'])?'"'.$this->cleanInStr($postArr['acceptedID']).'"':'NULL').', '.
					(is_numeric($postArr['parentID'])?'"'.$this->cleanInStr($postArr['parentID']).'"':'NULL').', '.
					($postArr['notes']?'"'.$this->cleanInStr($postArr['notes']).'"':'NULL').')';

				$this->conn->query($sql);
				$geoThesID = $this->conn->insert_id;

				if(!empty($postArr['polygon']) && $geoThesID) {
					$this->addPolygon($geoThesID, $postArr['polygon']);
				}
			}
			return $geoThesID;
		} catch(Exception $e) {
			$this->errorMessage = 'ERROR adding geounit: '. $e->getMessage();
			return false;
		}
	}

	public function deleteGeoUnit($geoThesID){
		if(is_numeric($geoThesID)){
			$sql = 'DELETE FROM geographicthesaurus WHERE (geoThesID = '.$geoThesID.')';
			if(!$this->conn->query($sql)){
				$this->errorMessage = 'ERROR deleting geoUnit: '.$this->conn->error;
				return false;
			}
		}
		return true;
	}

	public function getChildren(array $parentIDs): array {
		if(count($parentIDs) <= 0) return [];

		$parameters = str_repeat('?,', count($parentIDs) - 1) . '?';
		$sql = <<<SQL
		SELECT g.geoThesID, g.geoterm, g.geoLevel, CASE WHEN gp.geoThesID IS NULL THEN false ELSE true END AS hasPolygon
		FROM geographicthesaurus AS g LEFT JOIN geographicpolygon AS gp ON gp.geoThesID = g.geoThesID WHERE parentID IN ($parameters)
		SQL;

		try {
			$result = QueryUtil::executeQuery($this->conn,$sql, $parentIDs);
			$children = $result->fetch_all(MYSQLI_ASSOC);
			$result->free();
			$children_ids = array_map(fn($v) => $v["geoThesID"], $children);

			return array_merge($children, $this->getChildren($children_ids));
		} catch(Exception $e) {
			$this->errorMessage = 'ERROR getting children for geoUnits (' . implode(',', $parentIDs) .'): '. $e->getMessage();
			return [];
		}
	}

	private function setChildCnt($geoIdStr){
		$retArr = array();
		$sql = 'SELECT parentID, count(*) AS cnt FROM geographicthesaurus WHERE parentID IN('.$geoIdStr.') GROUP BY parentID ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->parentID] = $r->cnt;
		}
		$rs->free();
		return $retArr;
	}

	public function getCoordStatistics(){
		$retArr = array();
		$totalCnt = 0;
		$sql = 'SELECT COUNT(*) AS cnt FROM omoccurrences WHERE (collid IN(' . $this->collStr . '))';
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			$totalCnt = $r->cnt;
		}
		$rs->free();

		// Full count
		$sql2 = 'SELECT COUNT(occid) AS cnt FROM omoccurrences WHERE (collid IN(' . $this->collStr . ')) AND (decimalLatitude IS NULL) AND (georeferenceVerificationStatus IS NULL) ';
		if ($rs2 = $this->conn->query($sql2)) {
			if ($r2 = $rs2->fetch_object()) {
				$retArr['total'] = $r2->cnt;
				$retArr['percent'] = round($r2->cnt * 100 / $totalCnt, 1);
			}
			$rs2->free();
		}

		return $retArr;
	}

	//Misc data retrieval functions
	public function getParentGeoTermArr($geoLevelMax = 0){
		$retArr = array();
		$sql = 'SELECT t.geoThesID, CONCAT_WS(" ",t.geoTerm,CONCAT(" (",p.geoTerm,")")) AS geoTerm FROM geographicthesaurus t LEFT JOIN geographicthesaurus p ON t.parentID = p.geoThesID ';
		if($geoLevelMax) $sql .= 'WHERE t.geoLevel < '.$geoLevelMax.' ';
		$sql .= 'ORDER BY t.geoLevel, t.geoTerm';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->geoThesID] = $r->geoTerm;
		}
		$rs->free();
		return $retArr;
	}

	public function getAcceptedGeoTermArr($geoLevelMax = 0, $parentID = 0){
		$retArr = array();
		$sql = 'SELECT geoThesID, geoTerm FROM geographicthesaurus ';
		$conditionArr = array();
		if($geoLevelMax) $conditionArr[] = '(geoLevel = '.$geoLevelMax.')';
		if($parentID) $conditionArr[] = '(parentID = '.$parentID.')';
		if($conditionArr){
			$sql .= 'WHERE ' . implode(' AND ', $conditionArr);
		}
		$sql .= 'ORDER BY geoTerm';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->geoThesID] = $r->geoTerm;
		}
		$rs->free();
		return $retArr;
	}

	public function getGeoRankArr(){
		$rankArr = array();
		if(isset($GLOBALS['GEO_THESAURUS_RANKING']) && is_array($GLOBALS['GEO_THESAURUS_RANKING'])){
			$rankArr = $GLOBALS['GEO_THESAURUS_RANKING'];
		}
		else{
			$rankArr = array(10 => 'Oceans', 20 => 'Island Group', 30 => 'Island', 40 => 'Continent/Region', 50 => 'Country', 60 => 'State/Province', 70 => 'County', 80 => 'Municipality',
				100 => 'City/Town', 110 => 'Place Name', 150 => 'Lake/Pond', 160 => 'River/Creek');
		}
		return $rankArr;
	}

	//Reporting and data transfer functions
	public function getThesaurusStatus() {
		$retArr = [];
		$fullCnt = 0;
		$sql = 'SELECT geoLevel, COUNT(*) as cnt FROM geographicthesaurus GROUP BY geoLevel';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['active'][$r->geoLevel] = $r->cnt;
			$fullCnt += $r->cnt;
		}
		$rs->free();
		try {

			if($this->lkupTablesExist() && $fullCnt < 100){
				$sql = 'SELECT COUNT(*) as cnt FROM lkupcountry ';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$retArr['lkup']['country'] = $r->cnt;
				}
				$rs->free();

				$sql = 'SELECT COUNT(*) as cnt FROM lkupstateprovince ';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$retArr['lkup']['state'] = $r->cnt;
				}
				$rs->free();

				$sql = 'SELECT COUNT(*) as cnt FROM lkupcounty ';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$retArr['lkup']['county'] = $r->cnt;
				}
				$rs->free();

				$sql = 'SELECT COUNT(*) as cnt FROM lkupmunicipality ';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$retArr['lkup']['municipality'] = $r->cnt;
				}
				$rs->free();
			}
			return !empty($retArr)? $retArr: false;
		} catch(Exception $e) {
			return false;
		}
	}

	public function transferDeprecatedThesaurus(){
		$status = true;
		if(!$this->lkupTablesExist()) return false;
		$sqlArr = array();
		$sqlArr[] = 'INSERT INTO geographicthesaurus(geoterm,iso2,iso3,numcode,category,geoLevel,termstatus)
		SELECT countryName, iso, iso3, numcode, "Country", 50 as geoLevel, 1 as termStatus FROM lkupcountry WHERE iso IS NOT NULL';

		$sqlArr[] = 'UPDATE geographicthesaurus SET acceptedID = (SELECT geoThesID FROM geographicthesaurus WHERE geoTerm = "United States") WHERE geoterm IN("USA","U.S.A.","United States of America")';

		$sqlArr[] = 'INSERT INTO geographicthesaurus(geoterm,abbreviation,parentID,category,geoLevel,termStatus)
		SELECT DISTINCT s.stateName, s.abbrev, t.geoThesID, "State", 60 as geoLevel, 1 as termStatus
		FROM lkupcountry c INNER JOIN lkupstateprovince s ON c.countryid = s.countryid
		INNER JOIN geographicthesaurus t ON c.iso = t.iso2
		WHERE t.category = "country" AND t.termstatus = 1 AND t.acceptedID IS NULL';

		$sqlArr[] = 'INSERT INTO geographicthesaurus(geoterm,parentID,category,geoLevel,termStatus)
		SELECT DISTINCT REPLACE(REPLACE(REPLACE(c.countyName," Co.","")," County","")," Parish",""), t.geoThesID, "County", 70 as geoLevel, 1 as termStatus
		FROM lkupstateprovince s INNER JOIN lkupcounty c ON s.stateid = c.stateid
		INNER JOIN geographicthesaurus t ON s.stateName = t.geoterm
		WHERE t.category = "State" AND t.termstatus = 1';

		foreach($sqlArr as $sql){
			if(!$this->conn->query($sql)){
				$status = false;
				$this->warningArr[] = $this->conn->error;
			}
		}
		return $status;
	}

	//geoBoundary harvesting functions
	public function getGBCountryList(){
		$retArr = array();
		$contArr = $this->getContinentArr();
		$url = 'https://www.geoboundaries.org/api/current/gbOpen/ALL/ADM0/';
		$json = $this->getGeoboundariesJSON($url);
		$obj = json_decode($json);
		if($obj){
			foreach($obj as $countryObj){
				$key = $countryObj->boundaryISO;
				$retArr[$key]['id'] = $countryObj->boundaryID;
				$retArr[$key]['name'] = $countryObj->boundaryName;
				$retArr[$key]['canonical'] = $countryObj->boundaryCanonical;
				$retArr[$key]['iso'] = $countryObj->boundaryISO;
				$retArr[$key]['license'] = $this->licenseTranslate($countryObj->boundaryLicense);
				$region = '';
				if(in_array($countryObj->Continent,$contArr)) $region = $countryObj->Continent;
				elseif(in_array($countryObj->{'UNSDG-subregion'},$contArr)) $region = $countryObj->{'UNSDG-subregion'};
				else $region = $countryObj->Continent.'/'.$countryObj->{'UNSDG-subregion'};
				if($region == 'Northern America') $region == 'North America';
				if($countryObj->boundaryISO == 'ATA') $region = 'Antartica';
				$retArr[$key]['region'] = $region;
				$retArr[$key]['geoJson'] = $countryObj->simplifiedGeometryGeoJSON;
				$retArr[$key]['img'] = $countryObj->imagePreview;
			}
			ksort($retArr);
			//Check to see if country is already in thesaurus
			$sql = 'SELECT g.geoThesID, g.iso3, p.geoThesID AS polygonID
				FROM geographicthesaurus g LEFT JOIN geographicpolygon p ON g.geoThesID = p.geoThesID
				WHERE g.geoLevel = 50 AND g.acceptedID IS NULL AND g.iso3 IN("'.implode('","', array_keys($retArr)) .'")';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->iso3]['geoThesID'] = $r->geoThesID;
				if($r->polygonID) $retArr[$r->iso3]['polygon'] = 1;
			}
			$rs->free();
		}

		return $retArr;
	}

	public function getGBGeoList($countryCode){
		$retArr = array();
		$contArr = $this->getContinentArr();
		$urlBase = 'https://www.geoboundaries.org/api/current/gbOpen/';
		$url = $urlBase . $countryCode.'/ALL/';
		$json = $this->getGeoboundariesJSON($url);
		$obj = json_decode($json);
		if($obj){
			foreach($obj as $boundaryObj){
				$type = $boundaryObj->boundaryType;
				if(preg_match('/^ADM[0-3]{1}$/',$type)){
					$retArr[$type]['id'] = $boundaryObj->boundaryID;
					$retArr[$type]['canonical'] = $boundaryObj->boundaryCanonical;
					$retArr[$type]['year'] = $boundaryObj->boundaryYearRepresented;
					$retArr[$type]['license'] = $this->licenseTranslate($boundaryObj->boundaryLicense);
					$retArr[$type]['licenseSource'] = $boundaryObj->licenseSource;
					$retArr[$type]['sourceURL'] = $boundaryObj->boundarySourceURL;
					$region = '';
					if(in_array($boundaryObj->Continent,$contArr)) $region = $boundaryObj->Continent;
					elseif(in_array($boundaryObj->{'UNSDG-subregion'},$contArr)) $region = $boundaryObj->{'UNSDG-subregion'};
					else $region = $boundaryObj->Continent.'/'.$boundaryObj->{'UNSDG-subregion'};
					if($region == 'Northern America') $region == 'North America';
					if($countryCode == 'ATA') $region = 'Antartica';
					$retArr[$type]['region'] = $region;
					$retArr[$type]['gbCount'] = $boundaryObj->admUnitCount;
					//$retArr[$type]['geoJson'] = $boundaryObj->gjDownloadURL;
					$retArr[$type]['geoJson'] = $boundaryObj->simplifiedGeometryGeoJSON;
					$retArr[$type]['link'] = $urlBase.$countryCode.'/'.$type.'/';
					$retArr[$type]['img'] = $boundaryObj->imagePreview;
				}
			}
			ksort($retArr);
			//Check to see if country is already in thesaurus
			$sql = <<<SQL
			SELECT g.geoThesID, g.iso3, p.geoThesID AS polygonID
			FROM geographicthesaurus g LEFT JOIN geographicpolygon p ON g.geoThesID = p.geoThesID
			WHERE g.geoLevel = 50 AND g.acceptedID IS NULL AND g.iso3 = ?
			SQL;

			try {
				$result = QueryUtil::executeQuery($this->conn,$sql, [$countryCode]);
				if(($row = $result->fetch_object()) && isset($retArr['ADM0'])) {
					$retArr['ADM0']['geoThesID'] = $row->geoThesID;
					if($row->polygonID) $retArr['ADM0']['polygon'] = 1;
					$children = $this->getChildren([$row->geoThesID]);

					$result->free();

					foreach ($retArr as $key => $value) {
						if($key === 'ADM0') continue;
						$geoLevel = $this->getGeoLevel($key);
						$geoThesIDs = array_filter($children, fn($val) => $val['hasPolygon'] === 1 && $val['geoLevel'] === $geoLevel);
						if(count($geoThesIDs) > 0) {
							$retArr[$key]['geoThesID'] = $geoThesIDs;
							$retArr[$key]['polygon'] = 1;
						}
					}
				}
			} catch (\Throwable $e) {
				$this->errorMessage = 'ERROR getGBGeoList on iso3 ' . $countryCode . ': ' . $e->getMessage();
			}
		}
		return $retArr;
	}

	private function licenseTranslate($licenseStr){
		$retStr = $licenseStr;
		if($licenseStr == 'Public Domain') $retStr = 'CC0';
		elseif($licenseStr == 'CC0 1.0 Universal (CC0 1.0) Public Domain Dedication') $retStr = 'CC0 1.0';
		elseif($licenseStr == 'Creative Commons Attribution 3.0 License') $retStr = 'CC BY 3.0';
		elseif($licenseStr == 'Creative Commons Attribution 3.0 Intergovernmental Organisations (CC BY 3.0 IGO)') $retStr = 'CC BY 3.0 IGO';
		elseif($licenseStr == 'Attribuzione 3.0 Italia (CC BY 3.0 IT)') $retStr = 'CC BY 3.0 IT';
		elseif($licenseStr == 'Creative Commons Attribution 4.0 (CC BY 4.0)') $retStr = 'CC BY 4.0';
		elseif($licenseStr == 'Creative Commons Attribution 4.0 International (CC BY 4.0)') $retStr = 'CC BY 4.0';
		elseif($licenseStr == 'Creative Commons Attribution-ShareAlike 2.0') $retStr = 'CC BY-SA 2.0';
		elseif($licenseStr == 'Creative Commons Attribution-ShareAlike 3.0 Unported') $retStr = 'CC BY-SA 3.0';
		elseif($licenseStr == 'Creative Commons Attribution-ShareAlike 4.0 International License') $retStr = 'CC BY-SA 4.0';
		elseif($licenseStr == 'Open Data Commons Open Database License 1.0') $retStr = 'ODbL';
		elseif($licenseStr == 'Open Government Licence v3.0') $retStr = 'OGL 3.0';

		return $retStr;
	}

	//Assumes Most Points probably the biggest or main polygon which is fine for
	//this function
	private function getBiggestPolygon($arr) {
		if(!is_array($arr) || count($arr) === 0) {
			return null;
		} elseif(is_array($arr[0]) && count($arr[0]) === 2 && !is_array($arr[0][0])) {
			return $arr;
		} else {
			$maxPoly = [];
			for ($i=0; $i < count($arr); $i++) {
				$poly = $this->getBiggestPolygon($arr[$i]);
				if(count($maxPoly) < count($poly)) {
					$maxPoly = $poly;
				}
			}
			return $maxPoly;
		}
	}

	private function normalize($v) {
		$mag = sqrt(pow($v[0], 2) + pow($v[1], 2));
		if($mag === 0) return;

		$v[0] = $v[0] / $mag;
		$v[1] = $v[1] / $mag;

		return $v;
	}

	private function getIntersection($v1,$o1, $v2, $o2) {

		//Currently igonores vertical slope case
		$m1 = $v1[0] != 0? $v1[1] / $v1[0]: 0;
		$m2 = $v2[0] != 0? $v2[1] / $v2[0]: 0;

		//If slopes are paralell then no intersection
		if($m1 === $m2) return false;

		$dm = ($m1 - $m2);

		$x = $dm != 0? ($o2 - $o1) / $dm: 0;
		return [
			$x,
			($m2 * $x) + $o2
		];
	}

	private function getPointWithinPoly($coordinates) {
		$polygon = $this->getBiggestPolygon($coordinates);

		if(!is_array($polygon) || count($polygon) < 2) return false;

		$maxDistance = 0;
		$maxIndex = 1;

		for ($i=1; $i < count($polygon); $i++) {
			$v1 = $polygon[$i];
			$v2 = $polygon[$i - 1];

			$distance = sqrt(pow($v1[0] - $v2[0], 2) + pow($v1[1] - $v2[1], 2));
			if ($maxDistance < $distance) {
				$maxIndex = $i;
				$maxDistance = $distance;
			}
		}

		$start = [
			($polygon[$maxIndex - 1][0] + $polygon[$maxIndex][0]) / 2,
			($polygon[$maxIndex - 1][1] + $polygon[$maxIndex][1]) / 2
		];
		$ray = $this->normalize([
			-($polygon[$maxIndex - 1][1] - $polygon[$maxIndex][1]),
			$polygon[$maxIndex - 1][0] - $polygon[$maxIndex][0]
		]);
		$crosses = [];

		for ($i=1; $i < count($polygon); $i++) {
			$edge1 = $polygon[$i - 1];
			$edge2 = $polygon[$i];

			$pt = [($edge1[0] + $edge2[0]) / 2, ($edge1[1] + $edge2[1]) / 2];

			if ($i === $maxIndex) {
				continue;
			}

			$edgeVector = $this->normalize([$edge1[0] - $edge2[0], $edge1[1] - $edge2[1]]);

			$ray_offest = $start[1] - (($ray[0] != 0? $ray[1] / $ray[0]: 0) * $start[0]);
			$edge_offset = $edge1[1] - (($edgeVector[0] != 0? $edgeVector[1] / $edgeVector[0]: 0) * $edge1[0]);

			$intersection = $this->getIntersection($ray, $ray_offest, $edgeVector, $edge_offset);

			if(!$intersection) continue;

			$longBounds = ($edge1[0] <= $intersection[0] && $edge2[0] >= $intersection[0]) ||
				($edge1[0] >= $intersection[0] && $edge2[0] <= $intersection[0]);

			$latBounds = ($edge1[1] <= $intersection[1] && $edge2[1] >= $intersection[1]) ||
				($edge1[1] >= $intersection[1] && $edge2[1] <= $intersection[1]);

			if($latBounds && $longBounds) {
				array_push($crosses, $intersection);
			}
		}

		if (count($crosses) === 0) {
			return false;
		}

		array_push($crosses, $start);

		usort($crosses, function ($a, $b) {
			if ($a[0] === $b[0]) {
				return $a[1] === $b[1]? 0: ($a[1] > $b[1]? 1: -1);
			} else {
				return $a[1] > $b[1]? 1: -1;
			}
		});

		$pt = ["long" => ($crosses[0][0] + $crosses[1][0]) / 2, "lat" => ($crosses[0][1] + $crosses[1][1]) / 2];
		return $pt;
	}

	public function addGeoBoundary(string $url, bool $addMissing = false, int $baseParentId = null, array $potentialParents = []): array {
		$json = $this->getGeoboundariesJSON($url);
		$obj = json_decode($json);
		unset($json);

		$results = [];

		foreach ($obj->features as $feature) {
			$properties = $feature->properties;
			$geoLevel = $this->getGeoLevel($properties->shapeType);
			$parentID = null;

			if($properties->shapeName === null) continue;

			$geoThesIDs = $this->getGeoThesIDByName($properties->shapeName, $geoLevel, $potentialParents);
			$iso = !empty($properties->shapeGroup)? $properties->shapeGroup: $properties->shapeISO;

			//only does iso check for adm0 or countries because only case where
			//there is just one
			if (empty($geoThesIDs) && $geoLevel === 50) {
				$geoThesIDs = $this->getGeoThesIDByIso3($iso, $geoLevel);
			}
			$geoThesIDs = array_filter(
				$geoThesIDs,
				fn($val) => $val['hasPolygon'] === 0
			);

			if(is_array($geoThesIDs) && count($geoThesIDs) != 1) {
				$testPoint = $this->getPointWithinPoly($feature->geometry->coordinates);
				$parents = !empty($geoThesIDs)?
				array_filter(array_map(fn($val) => $val['parentID'], $geoThesIDs), fn($val) => $val !== null):
				$potentialParents;

				if($testPoint) {
					$parentID = $this->findParentGeometry(
						$testPoint["lat"],
						$testPoint["long"],
						$geoLevel - 10,
						//map and grab parentIds
						$parents
					);
					$geoThesIDs = array_filter(
						$this->getGeoThesIDByName($properties->shapeName, $geoLevel, [$parentID]),
						fn($val) => $val['hasPolygon'] === 0,
					);
				}
			}

			if(is_array($geoThesIDs) && count($geoThesIDs) === 1) {
				$key = array_keys($geoThesIDs)[0];
				$this->addPolygon($geoThesIDs[$key]['geoThesID'], json_encode($feature));
				//update iso3 because data could be missing or wrong
				if($iso !== $geoThesIDs[$key]['iso3']) {
					try {
						$sql = <<<'SQL'
						UPDATE geographicthesaurus set iso3 = ? where geoThesID = ?
						SQL;
						QueryUtil::executeQuery($this->conn,$sql, [$iso, $geoThesIDs[$key]['geoThesID']]);
					} catch (\Throwable $e) {
						$this->errorMessage = 'ERROR updating iso3 to match boundaryISO:' . $e->getMessage();
					}
				}
				array_push($results, $geoThesIDs[$key]['geoThesID']);
			} else if ($addMissing) {
				array_push($results, $this->addGeoUnit([
					"geoTerm" => $properties->shapeName,
					"iso2" =>"",
					"iso3" => $iso,
					"geoLevel" => $geoLevel,
					"abbreviation" =>"",
					"numCode" =>"",
					"acceptedID" => "",
					"parentID" => $parentID ?$parentID: $baseParentId,
					"notes" =>"",
					"polygon" => json_encode($feature),
				]));
			}
		}

		return $results;
	}


	public function getGeoLevelString(int $geolevel) {
		switch($geolevel) {
			case 10: return 'Oceans';
			case 20: return 'Island Group';
			case 30: return 'Island';
			case 40: return 'Continent/Region';
			case 50: return 'Country';
			case 60: return 'State/Province';
			case 70: return 'County';
			case 80: return 'Municipality';
			case 100: return 'City/Town';
			case 110: return 'Place Name';
			case 150: return 'Lake/Pond';
			case 160: return 'River/Creek';
			//This seems like a sensible default
			default: return "Place Name";
		}
	}

	public function searchGeothesaurus(string $geoterm, $geolevel = null, $parent = null, bool $distict_geoterms = false) {

		if (!empty($parent))
			$parent = $this->findAcceptedGeoTerm($parent);

		$sql = <<<SQL
		SELECT g.geoThesID, g.geoterm, g.geoLevel, g.parentID, g2.geoterm AS parentterm, g2.geoLevel AS parentlevel FROM geographicthesaurus g 
		LEFT JOIN geographicthesaurus g2 ON g2.geoThesID = g.parentID
		WHERE g.geoterm LIKE ?
		SQL;

		$params = ['%' . $geoterm . '%'];

		if($geolevel !== null) {
			$sql .= ' and g.geoLevel = ?';
			array_push($params, $geolevel);
		}

		if($parent !== null) {
			$sql .= ' and g2.geoterm like ?';
			array_push($params, '%' . $parent . '%');
		}

		if($distict_geoterms) {
			$sql .= ' GROUP BY geoterm ';
		}

		$sql .= ' ORDER BY g.geoterm = ? DESC, g.geoterm LIKE ? DESC, g.geoterm, CHAR_LENGTH(g.geoterm)';
		array_push($params, $geoterm, $geoterm . '%');

		$result = QueryUtil::executeQuery($this->conn,$sql, $params);

		$geoterms = $result->fetch_all(MYSQLI_ASSOC);
		for($i=0; $i < count($geoterms); $i++) {
			$label = $geoterms[$i]["geoterm"];

			if($geolevel === null) {
				$label .= " (" . $this->getGeoLevelString($geoterms[$i]["geoLevel"]) . ")";
			}

			if($geoterms[$i]["parentID"] !== null) {
				$label .= " child of " . $geoterms[$i]["parentterm"] . " (" . $this->getGeoLevelString($geoterms[$i]["parentlevel"]) . ")";
			}

			$geoterms[$i]['label'] = $label;
		}
		// Check if parent is valid (exist in geographicthesaurus table)
		$parentValid = true;
		$parentCount = 0;
		if ($parent !== null) {
			$checkParentSql = 'SELECT COUNT(*) FROM geographicthesaurus WHERE geoterm = ?';
			$stmt = $this->conn->prepare($checkParentSql);
			$stmt->bind_param('s', $parent);
			$stmt->execute();
			$stmt->bind_result($parentCount);
			$stmt->fetch();
			$stmt->close();

			if ($parentCount == 0)
				$parentValid = false;
		}
		if (!$geoterms && !$parentValid)
			return $this->searchGeothesaurus($geoterm, $geolevel, null, false);
		return $geoterms;
	}

	public function getGeoLevel(string $type): int {
		switch ($type) {
			case 'ADM1':
				return 60;
			case 'ADM2':
				return 70;
			case 'ADM3':
				return 80;
			default:
			return 50;
		}
	}

	public function findAcceptedGeoTerm($geoterm) {
		$acceptedSql = <<<SQL
		SELECT g2.geoterm
		FROM geographicthesaurus g
		LEFT JOIN geographicthesaurus g2 ON g.acceptedID = g2.geoThesID
		WHERE g.geoterm = ?
		LIMIT 1;
		SQL;

		$newGeoterm = "";

		$stmt = $this->conn->prepare($acceptedSql);
		$stmt->bind_param("s", $geoterm);
		$stmt->execute();
		$stmt->bind_result($newGeoterm);
		$stmt->fetch();
		$stmt->close();

		return $newGeoterm ?: $geoterm;
	}
	public function findParentGeometry($lat, $long, $parentGeoLevel = 50, $potentialParents = []) {
		$sql = <<<'SQL'
		SELECT g.geoThesID from geographicthesaurus g
		join geographicpolygon gp on g.geoThesID = gp.geoThesID
		WHERE ST_CONTAINS(gp.footprintPolygon, ST_GEOMFROMTEXT(?)) = 1 and
		g.geoLevel <= ?
		SQL;

		$geom = 'POINT (' . $long . ' '. $lat . ')';

		if(!empty($potentialParents)) {
			$sql.=' and g.geoThesID in ('. implode(',', $potentialParents) .')';
		}

		$sql .= ' ORDER BY g.geoLevel DESC';

		try {
			$stmt = $this->conn->prepare($sql);
			$stmt->bind_param("si", $geom, $parentGeoLevel);
			$stmt->execute();
			$stmt->bind_result($result);
			$stmt->fetch();

			return $result;
		} catch(Exception $e) {
			$this->errorMessage = 'ERROR while finding parent polygon: ' . $e->getMessage();
			return false;
		}

	}

	public function getGeoThesIDByName(string $geoTerm, int $geoLevel = null, array $parentIDs = []): array {
		$params = [$geoTerm];
		$sql = <<<SQL
		SELECT g.geoThesID, g.parentID, g.iso3, CASE WHEN gp.geoThesID is null THEN false ELSE true END as hasPolygon
		FROM geographicthesaurus as g left join geographicpolygon as gp on gp.geoThesID = g.geoThesID
		WHERE geoTerm = ? and acceptedID is null
		SQL;

		if($geoLevel !== null) {
			$sql .= ' and geoLevel = ?';
			array_push($params, $geoLevel);
		}

		if(count($parentIDs)) {
			$parameters = str_repeat('?,', count($parentIDs) - 1) . '?';
			$sql .= ' and parentID in ('. $parameters .')';
			$params = array_merge($params, $parentIDs);
		}
		try {
			$result = QueryUtil::executeQuery($this->conn,$sql, $params);
			$geoThesID = $result->fetch_all(MYSQLI_ASSOC);
			$result->free();
			return $geoThesID;

		} catch (\Throwable $e) {
			$this->errorMessage = 'ERROR getGeoThesIDByName for ' . $geoTerm. ':' . $e->getMessage();
			return [];
		}
	}

	private function getGeoThesIDByIso3($iso3, $geoLevel = null){
		$params = [$iso3];
		$sql = <<<SQL
		SELECT g.geoThesID, g.parentID, g.iso3, CASE WHEN gp.geoThesID is null THEN false ELSE true END as hasPolygon
		FROM geographicthesaurus as g left join geographicpolygon as gp on gp.geoThesID = g.geoThesID
		WHERE iso3 = ? and acceptedID is null
		SQL;

		if($geoLevel !== null && is_numeric($geoLevel)) {
			$sql .= ' and geoLevel = ?';
			array_push($params, $geoLevel);
		}
		try {
			$result = QueryUtil::executeQuery($this->conn,$sql, $params);
			$geoThesID = $result->fetch_all(MYSQLI_ASSOC);
			$result->free();
			return $geoThesID;
		} catch(\Throwable  $e) {
			$this->errorMessage = 'ERROR getGeoThesIDByIso3 for ' . $iso3 . ':' . $e->getMessage();
			return [];
		}
	}

	private function getGeoboundariesJSON($url){
		$resJson = false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$resJson = curl_exec($ch);
		if(!$resJson){
			$this->errorMessage = 'FATAL CURL ERROR: '.curl_error($ch).' (#'.curl_errno($ch).')';
			echo 'ERROR: '.$this->errorMessage;
			//$header = curl_getinfo($ch);
		}
		curl_close($ch);
		return $resJson;
	}

	private function getContinentArr(){
		return array('Asia','Caribbean','Oceania','Africa','Europe','Central America','Northern America','South America');
	}

	//Mics support functions
	private function lkupTablesExist(){
		$bool = false;
		// Check to see is old deprecated lookup tables exist
		$sql = 'SHOW tables LIKE "lkupcountry"';
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$bool = true;
		}
		$rs->free();
		return $bool;
	}

	public function geocode($lng, $lat) {
		if(!$lng || !$lat) return [];

		$result = QueryUtil::execute_query($this->conn ,"
			SELECT g.geoThesID, g.geoterm, g.geoLevel, s.synonyms
			FROM geographicthesaurus g 
			JOIN geographicpolygon gp on gp.geoThesID = g.geoThesID 
			LEFT JOIN (SELECT acceptedID, GROUP_CONCAT(geoterm) as `synonyms` FROM geographicthesaurus WHERE acceptedID IS NOT NULL GROUP BY acceptedID) s ON s.acceptedID = g.geoThesID where ST_Within(Point(?, ?), gp.footprintPolygon) ORDER BY geolevel
			", [floatval($lng), floatval($lat)]);

		$matches = [];
		while($r = $result->fetch_assoc()) {
			array_push($matches, $r);
		}


		return $matches;
	}

	function placeExists($geo_data = []) {

		$form_conversion = [
			'country' => 50,
			'stateprovince' => 60,
			'county' => 70,
			'municipality' => 80,
		];

		$parameters = [];

		$binds = [];
		foreach ($form_conversion as $key => $geo_level) {
			if(isset($geo_data[$key])) {
				array_push($parameters, '(geoterm = ? and geolevel = ' . $geo_level . ')');
				array_push($binds, $geo_data[$key]);
			}
		}

		if(count($parameters) <= 0) {
			return false;
		}

		$sql = "SELECT g.geoThesID FROM geographicthesaurus g 
			JOIN geographicpolygon gp ON gp.geoThesID = g.geoThesID 
			WHERE " . implode(" or ", $parameters );

		$result = QueryUtil::execute_query(
			$this->conn,
			$sql, 
			$binds
		);

		return $result->fetch_assoc() ? true: false;
	}
}
?>
