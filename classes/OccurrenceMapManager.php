<?php
include_once('OccurrenceManager.php');
include_once('OccurrenceAccessStats.php');

class OccurrenceMapManager extends OccurrenceManager {

	private $recordCount = 0;
	private $collArrIndex = 0;

	public function __construct(){
		parent::__construct();
		$this->readGeoRequestVariables();
		$this->setGeoSqlWhere();
		$this->setRecordCnt();
	}

	public function __destruct(){
		parent::__destruct();
	}

	private function readGeoRequestVariables(){
		if(array_key_exists('gridSizeSetting',$_REQUEST)){
			$this->searchTermArr['gridSizeSetting'] = $this->cleanInStr($_REQUEST['gridSizeSetting']);
		}
		if(array_key_exists('minClusterSetting',$_REQUEST)){
			$this->searchTermArr['minClusterSetting'] = $this->cleanInStr($_REQUEST['minClusterSetting']);
		}
		if(array_key_exists('clusterSwitch',$_REQUEST)){
			$this->searchTermArr['clusterSwitch'] = $this->cleanInStr($_REQUEST['clusterSwitch']);
		}
		if(array_key_exists('cltype',$_REQUEST) && $_REQUEST['cltype']){
			if($_REQUEST['cltype'] == 'all') $this->searchTermArr['cltype'] = 'all';
			$this->searchTermArr['cltype'] = 'vouchers';
		}
		if(array_key_exists('poly_array',$_REQUEST) && $_REQUEST['poly_array']){
			$this->searchTermArr['polycoords'] = $_REQUEST['poly_array'];
		}
		elseif(array_key_exists('polycoords',$_REQUEST) && $_REQUEST['polycoords']){
			$this->searchTermArr['polycoords'] = $_REQUEST['polycoords'];
		}
		elseif($this->getClFootprintWkt()){
			$this->searchTermArr['polycoords'] = $this->getClFootprintWkt();
		}
		if(!$this->getSearchTerm('polycoords')){
			if($this->getSearchTerm('clid') && $this->getClFootprintWkt()){
				$this->searchTermArr['poly_array'] = $this->getClFootprintWkt();
			}
		}
	}

	//Coordinate retrival functions
	public function getCoordinateMap($start, $limit){
		//Used within dynamic map
		$coordArr = Array();
		if($this->sqlWhere){
			$statsManager = new OccurrenceAccessStats();
			$sql = 'SELECT o.occid, CONCAT_WS(" ",o.recordedby,IFNULL(o.recordnumber,o.eventdate)) AS identifier, '.
				'o.sciname, o.family, o.tidinterpreted, o.DecimalLatitude, o.DecimalLongitude, o.collid, o.catalognumber, '.
				'o.othercatalognumbers, c.institutioncode, c.collectioncode, c.CollectionName '.
				'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid ';
			$sql .= $this->getTableJoins($this->sqlWhere);
			$sql .= $this->sqlWhere;
			if(is_numeric($start) && $limit){
				$sql .= "LIMIT ".$start.",".$limit;
			}
			//echo "<div>SQL: ".$sql."</div>"; exit;
			$result = $this->conn->query($sql);
			$color = 'e69e67';
			$occidArr = array();
			while($row = $result->fetch_object()){
				if(($row->DecimalLongitude <= 180 && $row->DecimalLongitude >= -180) && ($row->DecimalLatitude <= 90 && $row->DecimalLatitude >= -90)){
					$occidArr[] = $row->occid;
					$collName = $row->CollectionName;
					$tidInterpreted = $this->htmlEntities($row->tidinterpreted);
					$latLngStr = $row->DecimalLatitude.",".$row->DecimalLongitude;
					$coordArr[$collName][$row->occid]["llStr"] = $latLngStr;
					$coordArr[$collName][$row->occid]["collid"] = $this->htmlEntities($row->collid);
					//$tidcode = strtolower(str_replace(" ", "",$tidInterpreted.$row->sciname));
					//$tidcode = preg_replace( "/[^A-Za-z0-9 ]/","",$tidcode);
					//$coordArr[$collName][$occId]["ns"] = $this->htmlEntities($tidcode);
					$coordArr[$collName][$row->occid]["tid"] = $tidInterpreted;
					$coordArr[$collName][$row->occid]["fam"] = ($row->family?strtoupper($row->family):'undefined');
					$coordArr[$collName][$row->occid]["sn"] = $row->sciname;
					$coordArr[$collName][$row->occid]["id"] = $this->htmlEntities($row->identifier);
					//$coordArr[$collName][$occId]["icode"] = $this->htmlEntities($row->institutioncode);
					//$coordArr[$collName][$occId]["ccode"] = $this->htmlEntities($row->collectioncode);
					//$coordArr[$collName][$occId]["cn"] = $this->htmlEntities($row->catalognumber);
					//$coordArr[$collName][$occId]["ocn"] = $this->htmlEntities($row->othercatalognumbers);
					$coordArr[$collName]["c"] = $color;
				}
			}
			$statsManager->recordAccessEventByArr($occidArr, 'map');
			if(array_key_exists('undefined',$coordArr)){
				$coordArr['undefined']['c'] = $color;
			}
			$result->free();
		}
		return $coordArr;
	}

	public function getMappingData($recLimit, $extraFieldArr = null){
		//Used for simple maps occurrence and taxon maps, and also KML download functions
		$start = 0;
		if(!$this->sqlWhere) $this->setSqlWhere();
		$coordArr = array();
		if($this->sqlWhere){
			$statsManager = new OccurrenceAccessStats();
			$sql = 'SELECT DISTINCT o.occid, CONCAT_WS(" ",o.recordedby,IFNULL(o.recordnumber,o.eventdate)) AS collector, o.sciname, o.tidinterpreted, '.
				'o.decimallatitude, o.decimallongitude, o.catalognumber, o.othercatalognumbers, c.institutioncode, c.collectioncode, c.colltype ';
			if(isset($extraFieldArr) && is_array($extraFieldArr)){
				foreach($extraFieldArr as $fieldName){
					$sql .= ", o.".$fieldName." ";
				}
			}
			$sql .= 'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid ';
			$sql .= $this->getTableJoins($this->sqlWhere);
			$sql .= $this->sqlWhere;
			$sql .= 'AND (o.decimallatitude BETWEEN -90 AND 90) AND (o.decimallongitude BETWEEN -180 AND 180) ';
			if(is_numeric($start) && $recLimit && is_numeric($recLimit)) $sql .= "LIMIT ".$start.",".$recLimit;
			//echo "<div>SQL: ".$sql."</div>";
			$rs = $this->conn->query($sql);
			$occidArr = array();
			while($r = $rs->fetch_assoc()){
				if(($r['decimallongitude'] <= 180 && $r['decimallongitude'] >= -180) && ($r['decimallatitude'] <= 90 && $r['decimallatitude'] >= -90)){
					$sciname = $r['sciname'];
					if(!$sciname) $sciname = 'undefined';
					$coordArr[$sciname][$r['occid']]['instcode'] = $r['institutioncode'];
					if($r['collectioncode']) $coordArr[$sciname][$r['occid']]['collcode'] = $r['collectioncode'];
					$collType = 'obs';
					if(stripos($r['colltype'],'specimen')) $collType = 'spec';
					$coordArr[$sciname][$r['occid']]['colltype'] = $collType;
					if($r['catalognumber']) $coordArr[$sciname][$r['occid']]['catnum'] = $r['catalognumber'];
					if($r['othercatalognumbers']) $coordArr[$sciname][$r['occid']]['ocatnum'] = $r['othercatalognumbers'];
					if($r['tidinterpreted']) $coordArr[$sciname]['tid'] = $r['tidinterpreted'];
					$coordArr[$sciname][$r['occid']]['collector'] = $r['collector'];
					$coordArr[$sciname][$r['occid']]['lat'] = $r['decimallatitude'];
					$coordArr[$sciname][$r['occid']]['lng'] = $r['decimallongitude'];
					if(isset($extraFieldArr) && is_array($extraFieldArr)){
						reset($extraFieldArr);
						foreach($extraFieldArr as $fieldName){
							if(isset($r[$fieldName])) $coordArr[$sciname][$r['occid']][$fieldName] = $r[$fieldName];
						}
					}
					$occidArr[] = $r['occid'];
				}
			}
			$rs->free();
			$statsManager->recordAccessEventByArr($occidArr, 'map');
		}
		return $coordArr;
	}

	//Occurrence functions
	public function getOccurrenceArr($pageRequest,$cntPerPage){
		//Used in occurrence listing tab within dynamic map
		$retArr = Array();
		if($this->sqlWhere){
			$sql = 'SELECT o.occid, c.institutioncode, o.catalognumber, CONCAT_WS(" ",o.recordedby,o.recordnumber) AS collector, '.
				'o.eventdate, o.family, o.sciname, CONCAT_WS("; ",o.country, o.stateProvince, o.county) AS locality, o.DecimalLatitude, o.DecimalLongitude, '.
				'IFNULL(o.LocalitySecurity,0) AS LocalitySecurity, o.localitysecurityreason '.
				'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid ';
			$sql .= $this->getTableJoins($this->sqlWhere);
			$sql .= $this->sqlWhere;
			$bottomLimit = ($pageRequest - 1)*$cntPerPage;
			$sql .= "ORDER BY o.sciname, o.eventdate ";
			$sql .= "LIMIT ".$bottomLimit.",".$cntPerPage;
			//echo "<div>Spec sql: ".$sql."</div>";
			$result = $this->conn->query($sql);
			while($r = $result->fetch_object()){
				$occId = $r->occid;
				$retArr[$occId]['i'] = $this->cleanOutStr($r->institutioncode);
				$retArr[$occId]['cat'] = $this->cleanOutStr($r->catalognumber);
				$retArr[$occId]['c'] = $this->cleanOutStr($r->collector);
				$retArr[$occId]['e'] = $this->cleanOutStr($r->eventdate);
				$retArr[$occId]['f'] = $this->cleanOutStr($r->family);
				$retArr[$occId]['s'] = $this->cleanOutStr($r->sciname);
				$retArr[$occId]['l'] = $this->cleanOutStr($r->locality);
				$retArr[$occId]['lat'] = $this->cleanOutStr($r->DecimalLatitude);
				$retArr[$occId]['lon'] = $this->cleanOutStr($r->DecimalLongitude);
				$retArr[$occId]['l'] = str_replace('.,',',',$r->locality);
			}
			$result->free();
			//Set access statistics
			if($retArr){
				$statsManager = new OccurrenceAccessStats();
				$statsManager->recordAccessEventByArr(array_keys($retArr),'list');
			}
		}
		return $retArr;
	}

	private function setRecordCnt(){
		if($this->sqlWhere){
			$sql = "SELECT COUNT(DISTINCT o.occid) AS cnt FROM omoccurrences o ".$this->getTableJoins($this->sqlWhere).$this->sqlWhere;
			//echo "<div>Count sql: ".$sql."</div>";
			$result = $this->conn->query($sql);
			if($result){
				if($row = $result->fetch_object()){
					$this->recordCount = $row->cnt;
				}
				$result->free();
			}
		}
	}

	public function getRecordCnt(){
		return $this->recordCount;
	}

	//SQL where functions
	private function setGeoSqlWhere(){
		global $USER_RIGHTS;
		if($this->searchTermArr){
			$sqlWhere = $this->getSqlWhere();
			$sqlWhere .= ($sqlWhere?'AND ':'WHERE ').'(o.DecimalLatitude IS NOT NULL AND o.DecimalLongitude IS NOT NULL) ';
			if(array_key_exists('clid',$this->searchTermArr) && $this->searchTermArr['clid']){
				if(isset($this->searchTermArr['cltype']) && $this->searchTermArr['cltype'] == 'all'){
					$sqlWhere .= "AND (ST_Within(p.point,GeomFromText('".$this->getClFootprintWkt()." '))) ";
				}
				else{
					//$sqlWhere .= "AND (v.clid IN(".$this->searchTermArr['clid'].")) ";
				}
			}
			elseif(array_key_exists("polycoords",$this->searchTermArr)){
				$sqlWhere .= "AND (ST_Within(p.point,GeomFromText('".$this->searchTermArr["polycoords"]." '))) ";
			}
			//Check and exclude records with sensitive species protections
			if(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('CollAdmin',$USER_RIGHTS) || array_key_exists('RareSppAdmin',$USER_RIGHTS) || array_key_exists('RareSppReadAll',$USER_RIGHTS)){
				//Is global rare species reader, thus do nothing to sql and grab all records
			}
			elseif(isset($USER_RIGHTS['RareSppReader']) || isset($USER_RIGHTS['CollEditor'])){
				$securityCollArr = array();
				if(isset($USER_RIGHTS['CollEditor'])) $securityCollArr = $USER_RIGHTS['CollEditor'];
				if(isset($USER_RIGHTS['RareSppReader'])) $securityCollArr = array_unique(array_merge($securityCollArr, $USER_RIGHTS['RareSppReader']));
				$sqlWhere .= ' AND (o.CollId IN ('.implode(',',$securityCollArr).') OR (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL)) ';
			}
			else{
				$sqlWhere .= ' AND (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL) ';
			}
			$this->sqlWhere = $sqlWhere;
			//echo '<div style="margin-left:10px">sql: '.$this->sqlWhere.'</div>'; exit;
		}
	}

	//Shape functions
	public function createShape(){
		$queryShape = '';
		$properties = 'strokeWeight: 0,';
		$properties .= 'fillOpacity: 0.45,';
		$properties .= 'editable: true,';
		//$properties .= 'draggable: true,';
		$properties .= 'map: map});';

		if($this->getSearchTerm('upperlat')){
			$queryShape = 'var queryRectangle = new google.maps.Rectangle({';
			$queryShape .= 'bounds: new google.maps.LatLngBounds(';
			$queryShape .= 'new google.maps.LatLng('.$this->getSearchTerm('bottomlat').', '.$this->getSearchTerm('leftlong').'),';
			$queryShape .= 'new google.maps.LatLng('.$this->getSearchTerm('upperlat').', '.$this->getSearchTerm('rightlong').')),';
			$queryShape .= $properties;
			$queryShape .= "queryRectangle.type = 'rectangle';";
			$queryShape .= "google.maps.event.addListener(queryRectangle, 'click', function() {";
			$queryShape .= 'setSelection(queryRectangle);});';
			$queryShape .= "google.maps.event.addListener(queryRectangle, 'dragend', function() {";
			$queryShape .= 'setSelection(queryRectangle);});';
			$queryShape .= "google.maps.event.addListener(queryRectangle, 'bounds_changed', function() {";
			$queryShape .= 'setSelection(queryRectangle);});';
			$queryShape .= 'setSelection(queryRectangle);';
			$queryShape .= 'var queryShapeBounds = new google.maps.LatLngBounds();';
			$queryShape .= 'queryShapeBounds.extend(new google.maps.LatLng('.$this->getSearchTerm('bottomlat').', '.$this->getSearchTerm('leftlong').'));';
			$queryShape .= 'queryShapeBounds.extend(new google.maps.LatLng('.$this->getSearchTerm('upperlat').', '.$this->getSearchTerm('rightlong').'));';
			$queryShape .= 'map.fitBounds(queryShapeBounds);';
			$queryShape .= 'map.panToBounds(queryShapeBounds);';
		}
		if($this->getSearchTerm('pointlat')){
			$radius = (($this->getSearchTerm('radius')/0.6214)*1000);
			$queryShape = 'var queryCircle = new google.maps.Circle({';
			$queryShape .= 'center: new google.maps.LatLng('.$this->getSearchTerm('pointlat').', '.$this->getSearchTerm('pointlong').'),';
			$queryShape .= 'radius: '.$radius.',';
			$queryShape .= $properties;
			$queryShape .= "queryCircle.type = 'circle';";
			$queryShape .= "google.maps.event.addListener(queryCircle, 'click', function() {";
			$queryShape .= 'setSelection(queryCircle);});';
			$queryShape .= "google.maps.event.addListener(queryCircle, 'dragend', function() {";
			$queryShape .= 'setSelection(queryCircle);});';
			$queryShape .= "google.maps.event.addListener(queryCircle, 'radius_changed', function() {";
			$queryShape .= 'setSelection(queryCircle);});';
			$queryShape .= "google.maps.event.addListener(queryCircle, 'center_changed', function() {";
			$queryShape .= 'setSelection(queryCircle);});';
			$queryShape .= 'setSelection(queryCircle);';
			$queryShape .= 'var queryShapeBounds = queryCircle.getBounds();';
			$queryShape .= 'map.fitBounds(queryShapeBounds);';
			$queryShape .= 'map.panToBounds(queryShapeBounds);';
		}
		if($this->getSearchTerm('polycoords')){
			$wkt = $this->getSearchTerm('polycoords');
			if(substr($wkt,0,7) == 'POLYGON') $wkt = substr($wkt,7);
			else if(substr($wkt,0,12) == 'MULTIPOLYGON') $wkt = substr($wkt,12);
			$coordArr = explode('),(', $wkt);
			$shapeBounds = 'var queryShapeBounds = new google.maps.LatLngBounds();'."\n";
			foreach($coordArr as $k => $polyFrag){
				if($pointArr = explode(',', trim($polyFrag,' (),'))){
					$queryShape .= 'var queryPolygon'.$k.' = new google.maps.Polygon({';
					$points = '';
					foreach($pointArr as $ptStr){
						$ptArr = explode(' ',$ptStr);
						$points .= ',new google.maps.LatLng('.$ptArr[0].', '.$ptArr[1].')';
						$shapeBounds .= 'queryShapeBounds.extend(new google.maps.LatLng('.$ptArr[0].', '.$ptArr[1].'));'."\n";
					}
					$queryShape .= 'paths: ['.substr($points,1).'],';
					$queryShape .= $properties;
					$queryShape .= 'queryPolygon'.$k.'.type = "polygon";';
					$queryShape .= 'google.maps.event.addListener(queryPolygon'.$k.', "click", function() { setSelection(queryPolygon'.$k.');});';
					$queryShape .= 'google.maps.event.addListener(queryPolygon'.$k.', "dragend", function() { setSelection(queryPolygon'.$k.');});';
					$queryShape .= 'google.maps.event.addListener(queryPolygon'.$k.'.getPath(), "insert_at", function() { setSelection(queryPolygon'.$k.');});';
					$queryShape .= 'google.maps.event.addListener(queryPolygon'.$k.'.getPath(), "remove_at", function() { setSelection(queryPolygon'.$k.');});';
					$queryShape .= 'google.maps.event.addListener(queryPolygon'.$k.'.getPath(), "set_at", function() { setSelection(queryPolygon'.$k.');});';
					$queryShape .= 'setSelection(queryPolygon'.$k.');';
				}
			}
			$queryShape .= $shapeBounds;
			$queryShape .= 'map.fitBounds(queryShapeBounds);';
			$queryShape .= 'map.panToBounds(queryShapeBounds);'."\n";
		}
		return $queryShape;
	}

	public function writeKMLFile($recLimit, $extraFieldArr = null){
		//Output data
		$fileName = $GLOBALS['DEFAULT_TITLE'];
		if($fileName){
			if(strlen($fileName) > 10) $fileName = substr($fileName,0,10);
			$fileName = str_replace(".","",$fileName);
			$fileName = str_replace(" ","_",$fileName);
		}
		else{
			$fileName = "symbiota";
		}
		$fileName .= time().".kml";
		header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-type: application/vnd.google-earth.kml+xml');
		header ('Content-Disposition: attachment; filename="'.$fileName.'"');
		echo "<?xml version='1.0' encoding='".$GLOBALS['CHARSET']."'?>\n";
		echo "<kml xmlns='http://www.opengis.net/kml/2.2'>\n";
		echo "<Document>\n";
		echo "<Folder>\n<name>".$GLOBALS['DEFAULT_TITLE']." Specimens - ".date('j F Y g:ia')."</name>\n";

		//Get and output data
		$cnt = 0;
		$coordArr = $this->getMappingData($recLimit, $extraFieldArr);
		if($coordArr){
			$this->googleIconArr = array('pushpin/ylw-pushpin','pushpin/blue-pushpin','pushpin/grn-pushpin','pushpin/ltblu-pushpin',
				'pushpin/pink-pushpin','pushpin/purple-pushpin', 'pushpin/red-pushpin','pushpin/wht-pushpin','paddle/blu-blank',
				'paddle/grn-blank','paddle/ltblu-blank','paddle/pink-blank','paddle/wht-blank','paddle/blu-diamond','paddle/grn-diamond',
				'paddle/ltblu-diamond','paddle/pink-diamond','paddle/ylw-diamond','paddle/wht-diamond','paddle/red-diamond','paddle/purple-diamond',
				'paddle/blu-circle','paddle/grn-circle','paddle/ltblu-circle','paddle/pink-circle','paddle/ylw-circle','paddle/wht-circle',
				'paddle/red-circle','paddle/purple-circle','paddle/blu-square','paddle/grn-square','paddle/ltblu-square','paddle/pink-square',
				'paddle/ylw-square','paddle/wht-square','paddle/red-square','paddle/purple-square','paddle/blu-stars','paddle/grn-stars',
				'paddle/ltblu-stars','paddle/pink-stars','paddle/ylw-stars','paddle/wht-stars','paddle/red-stars','paddle/purple-stars');
			$color = 'e69e67';
			foreach($coordArr as $sciname => $snArr){
				unset($snArr['tid']);
				$cnt++;
				$iconStr = $this->googleIconArr[$cnt%44];
				echo "<Style id='sn_".$iconStr."'>\n";
				echo "<IconStyle><scale>1.1</scale><Icon>";
				echo "<href>http://maps.google.com/mapfiles/kml/".$iconStr.".png</href>";
				echo "</Icon><hotSpot x='20' y='2' xunits='pixels' yunits='pixels'/></IconStyle>\n</Style>\n";
				echo "<Style id='sh_".$iconStr."'>\n";
				echo "<IconStyle><scale>1.3</scale><Icon>";
				echo "<href>http://maps.google.com/mapfiles/kml/".$iconStr.".png</href>";
				echo "</Icon><hotSpot x='20' y='2' xunits='pixels' yunits='pixels'/></IconStyle>\n</Style>\n";
				echo "<StyleMap id='".htmlspecialchars(str_replace(" ","_",$sciname), ENT_QUOTES)."'>\n";
				echo "<Pair><key>normal</key><styleUrl>#sn_".$iconStr."</styleUrl></Pair>";
				echo "<Pair><key>highlight</key><styleUrl>#sh_".$iconStr."</styleUrl></Pair>";
				echo "</StyleMap>\n";
				echo "<Folder><name>".htmlspecialchars($sciname, ENT_QUOTES)."</name>\n";
				foreach($snArr as $occid => $recArr){
					echo '<Placemark>';
					echo '<name>'.htmlspecialchars($recArr['collector'], ENT_QUOTES).'</name>';
					echo '<ExtendedData>';
					echo '<Data name="institutioncode">'.htmlspecialchars($recArr['instcode'], ENT_QUOTES).'</Data>';
					if(isset($recArr['collcode'])) echo '<Data name="collectioncode">'.htmlspecialchars($recArr['collcode'], ENT_QUOTES).'</Data>';
					echo '<Data name="catalognumber">'.(isset($recArr['catnum'])?htmlspecialchars($recArr['catnum'], ENT_QUOTES):'').'</Data>';
					if(isset($recArr['ocatnum'])) echo '<Data name="othercatalognumbers">'.htmlspecialchars($recArr['ocatnum'], ENT_QUOTES).'</Data>';
					echo '<Data name="DataSource">Data retrieved from '.$GLOBALS['DEFAULT_TITLE'].' Data Portal</Data>';
					$recUrl = 'http://';
					if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $recUrl = 'https://';
					$recUrl .= $_SERVER['SERVER_NAME'].$GLOBALS['CLIENT_ROOT'].'/collections/individual/index.php?occid='.$occid;
					echo '<Data name="RecordURL">'.$recUrl.'</Data>';
					if(isset($extraFieldArr) && is_array($extraFieldArr)){
						reset($extraFieldArr);
						foreach($extraFieldArr as $fieldName){
							if(isset($recArr[$fieldName])) echo '<Data name="'.$fieldName.'">'.htmlspecialchars($recArr[$fieldName], ENT_QUOTES).'</Data>';
						}
					}
					echo '</ExtendedData>';
					echo '<styleUrl>#'.htmlspecialchars(str_replace(' ','_',$sciname), ENT_QUOTES).'</styleUrl>';
					echo '<Point><coordinates>'.$recArr['lng'].','.$recArr['lat'].'</coordinates></Point>';
					echo "</Placemark>\n";
				}
				echo "</Folder>\n";
			}
		}
		echo "</Folder>\n";
		echo "</Document>\n";
		echo "</kml>\n";
	}

	//Dataset functions
	public function getOccurrences($datasetId){
		$retArr = array();
		if($datasetId){
			$sql = 'SELECT o.occid, o.catalognumber, CONCAT_WS(" ",o.recordedby,o.recordnumber) AS collector, o.eventdate, '.
				'o.family, o.sciname, CONCAT_WS("; ",o.country, o.stateProvince, o.county) AS locality, o.DecimalLatitude, o.DecimalLongitude '.
				'FROM omoccurrences o LEFT JOIN omoccurdatasetlink dl ON o.occid = dl.occid '.
				'WHERE dl.datasetid = '.$datasetId.' '.
				'ORDER BY o.sciname ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid]['occid'] = $r->occid;
				$retArr[$r->occid]['sciname'] = $r->sciname;
				$retArr[$r->occid]['catnum'] = $r->catalognumber;
				$retArr[$r->occid]['coll'] = $r->collector;
				$retArr[$r->occid]['eventdate'] = $r->eventdate;
				$retArr[$r->occid]['occid'] = $r->occid;
				$retArr[$r->occid]['lat'] = $r->DecimalLatitude;
				$retArr[$r->occid]['long'] = $r->DecimalLongitude;
			}
			$rs->free();
		}
		if(count($retArr)>1){
			return $retArr;
		}
		else{
			return;
		}
	}

	public function getPersonalRecordsets($uid){
		$retArr = Array();
		$sql = "";
		//Get datasets owned by user
		$sql = 'SELECT datasetid, name '.
			'FROM omoccurdatasets '.
			'WHERE (uid = '.$uid.') '.
			'ORDER BY name';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->datasetid]['datasetid'] = $r->datasetid;
			$retArr[$r->datasetid]['name'] = $r->name;
			$retArr[$r->datasetid]['role'] = "DatasetAdmin";
		}
		$sql2 = 'SELECT d.datasetid, d.name, r.role '.
			'FROM omoccurdatasets d LEFT JOIN userroles r ON d.datasetid = r.tablepk '.
			'WHERE (r.uid = '.$uid.') AND (r.role IN("DatasetAdmin","DatasetEditor","DatasetReader")) '.
			'ORDER BY sortsequence,name';
		$rs = $this->conn->query($sql2);
		while($r = $rs->fetch_object()){
			$retArr[$r->datasetid]['datasetid'] = $r->datasetid;
			$retArr[$r->datasetid]['name'] = $r->name;
			$retArr[$r->datasetid]['role'] = $r->role;
		}
		$rs->free();
		return $retArr;
	}

	//Misc functions
	public function getObservationIds(){
		$retVar = array();
		$sql = 'SELECT collid FROM omcollections WHERE CollType IN("Observations","General Observations") ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retVar[] = $r->collid;
		}
		$rs->free();
		return $retVar;
	}

	public function hasFullSpatialSupport(){
		$serverStr = '';
		if(mysqli_get_server_info($this->conn)) $serverStr = mysqli_get_server_info($this->conn);
		else $serverStr = shell_exec('mysql -V');
		if($serverStr){
			if(strpos($serverStr,'MariaDB') !== false) return true;
			else{	//db = mysql;
				preg_match('@[0-9]+\.[0-9]+\.[0-9]+@',$serverStr,$m);
				$mysqlVerNums = explode(".", $m[0]);
				if($mysqlVerNums[0] > 5) return true;
				elseif($mysqlVerNums[0] == 5){
					if($mysqlVerNums[1] > 6) return true;
					elseif($mysqlVerNums[1] == 6){
						if($mysqlVerNums[2] >= 1) return true;
					}
				}
			}
		}
		return false;
	}

	//Misc support functions
	private function htmlEntities($string){
		return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
	}
}
?>