<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class MapSupport extends Manager{

	private $targetPath = '';
	private $targetUrl = '';

	public function __construct(){
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}

	//Static Map functions
	public function getTaxaList(){
		$retArr = array();
		//Following SQL grabs all accepted taxa at species / infraspecific rank that don't yet have a distribution map
		//Eventually well probably add ability to refresh all maps older than a certain date, and/or by other criteria
		//Return limit is currently set at 1000 records, which maybe should be variable that is set by user?
		$sql = 'SELECT DISTINCT t.tid, t.sciname
			FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted
			INNER JOIN omoccurrences o ON ts.tid = o.tidinterpreted
			LEFT JOIN taxamaps m ON t.tid = m.tid
			WHERE t.rankid > 219 AND m.tid IS NULL
			ORDER BY t.sciname LIMIT 1000';
		if($stmt = $this->conn->prepare($sql)){
			//$stmt->bind_param();
			$stmt->execute();
			$stmt->bind_result($tid, $sciname);
			while($stmt->fetch()){
				$retArr[$tid] = $sciname;
			}
			$stmt->close();
		}
		return $retArr;
	}

	public function getCoordinates($tid, $bounds){
		$retArr = array();
		$tidArr = $this->getRelatedTids($tid);
		if($tidArr){
			$latMin = -90;
			$latMax = 90;
			$lngMin = -180;
			$lngMax = 180;
			if($bounds){
				$boundArr = explode(';', $bounds);
				$latMin = $boundArr[2];
				$latMax = $boundArr[0];
				$lngMin = $boundArr[1];
				$lngMax = $boundArr[3];
			}
			$sql = 'SELECT DISTINCT decimalLatitude, decimalLongitude
				FROM omoccurrences
				WHERE (decimalLatitude BETWEEN '.$latMin.' AND '.$latMax.') AND (decimalLongitude BETWEEN '.$lngMin.' AND '.$lngMax.')
				AND (cultivationStatus IS NULL OR cultivationStatus = 0) AND (localitySecurity IS NULL OR localitySecurity = 0)
				AND (coordinateUncertaintyInMeters IS NULL OR coordinateUncertaintyInMeters < 5000)
				AND tidinterpreted IN('.array_explode(',', $tidArr).')';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[] = $r->decimalLongitude.','.$r->decimalLatitude;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	private function getRelatedTids($tidAccepted){
		//First get all accepted children; currently expecting input to be at the species ranke, and thus only grabbing one layer down (subsp, var, f)
		$tidArr = array($tidAccepted => $tidAccepted);
		$sql = 'SELECT tid FROM taxstatus WHERE taxauthid = 1 AND tid = tidaccepted AND parenttid = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $tidAccepted);
			$stmt->execute();
			$stmt->bind_result($tid);
			while($stmt->fetch()){
				$tidArr[$tid] = $tid;
			}
			$stmt->close();
		}
		//Append all non-accepted synonyms
		$sql = 'SELECT tid FROM taxstatus WHERE taxauthid = 1 AND tidaccepted IN('.array_explode(',', $tidArr).')';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$tidArr[$r->tid] = $r->tid;
			}
			$rs->free();
		}
		return $tidArr;
	}

	private function filterPoints(){
		//Function to theoretically be used when generating a point map
		//Remove outlier points, which are likely georeferencing errors and untagged cultivated occurrences
		//Not needed for Heat Map, which is expected to automatically filter out outliers
		//Remove excess points that obsure map, if needed

	}

	public function postImage($portArr){
		$status = false;
		$tid = $portArr['tid'];
		$title = $portArr['title'];
		$mapType = 'heatmap';
		if(isset($portArr['maptype'])) $mapType = $portArr['maptype'];
		if(!empty($_FILES['mapupload']['name'])){
			$ext = substr($_FILES['mapupload']['name'], strrpos($_FILES['mapupload']['name'], '.'));
			$fileName = $tid.'_'.$mapType.'_'.time().'.'.$ext;
			$this->setTargetPaths();
			if(move_uploaded_file($_FILES['uploadfile']['tmp_name'], $this->targetPath.$fileName)){
				$status = $this->insertImage($tid, $title, $this->targetPath.$fileName);
			}
			else{
				$this->errorMessage = 'ERROR uploading file (code '.$_FILES['uploadfile']['error'].'): ';
				return false;
			}
		}
	}

	private function insertImage($tid, $title, $url){
		$status = false;
		$sql = 'INSERT INTO taxamaps(tid, title, url) VALUES(?, ?, ?)';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('iss', $tid, $title, $url);
			$stmt->execute();
			if($stmt->affected_rows) $status = true;
			elseif($stmt->error) $this->errorMessage = 'ERROR inserting taxon map: '.$stmt->error;
			$stmt->close();
		}
		return $status;
	}

	private function deleteAllTaxonMaps($tid){
		$status = false;
		$sql = 'DELETE FROM taxamaps WHERE tid = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $tid);
			$stmt->execute();
			if($stmt->affected_rows) $status = true;
			elseif($stmt->error) $this->errorMessage = 'ERROR deleting all taxon maps: '.$stmt->error;
			$stmt->close();
		}
		return $status;
	}

	private function deleteMap($mid){
		$status = false;
		$sql = 'DELETE FROM taxamaps WHERE mid = ?';
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param('i', $mid);
			$stmt->execute();
			if($stmt->affected_rows) $status = true;
			elseif($stmt->error) $this->errorMessage = 'ERROR deleting taxon map: '.$stmt->error;
			$stmt->close();
		}
		return $status;
	}

	private function setTargetPaths(){
		$targetPath = $GLOBALS['IMAGE_ROOT_PATH'];
		$targetUrl = $GLOBALS['IMAGE_ROOT_URL'];
		if(!is_writable($targetPath)){
			$this->errorMessage = 'ABORT: target path does not exist or is not writable';
			return false;
		}
		if(substr($targetPath, -1) != '/') $targetPath .= '/';
		if(substr($targetUrl, -1) != '/') $targetUrl .= '/';
		$targetPath .= 'maps/';
		$targetUrl .= 'maps/';
		if(!$targetPath) mkdir($targetPath);
		$ymd = date('Y-m-d');
		$targetPath .= $ymd.'/';
		$targetUrl .= $ymd.'/';
		if(!$targetPath) mkdir($targetPath);
		$this->targetPath = $targetPath;
		$this->targetUrl = $targetUrl;
	}

	//Deprecated Static Google Map functions
	public static function getStaticMap($coordArr){
		$mapThumbnails = false;
		if(isset($GLOBALS['MAP_THUMBNAILS']) && $GLOBALS['MAP_THUMBNAILS']) $mapThumbnails = $GLOBALS['MAP_THUMBNAILS'];
		$url = $GLOBALS['CLIENT_ROOT'].'/images/mappoint.png';
		if($mapThumbnails){
			$mapboxApiKey = '';
			if(isset($GLOBALS['MAPBOX_API_KEY']) && $GLOBALS['MAPBOX_API_KEY']) $mapboxApiKey = $GLOBALS['MAPBOX_API_KEY'];
			$googleMapApiKey = '';
			if(isset($GLOBALS['GOOGLE_MAP_KEY']) && $GLOBALS['GOOGLE_MAP_KEY']) $googleMapApiKey = $GLOBALS['GOOGLE_MAP_KEY'];
			if($mapboxApiKey){
				$url = '//api.mapbox.com/styles/v1/mapbox/outdoors-v11/static/';
				$overlay = '';
				foreach($coordArr as $coordStr){
					$llArr = explode(',', $coordStr);
					$overlay .= 'pin-s('.$llArr[1].','.$llArr[0].'),';
				}
				$url .= trim($overlay,', ').'/auto/200x200?access_token='.$mapboxApiKey;
			}
			elseif($googleMapApiKey){
				$url = '//maps.googleapis.com/maps/api/staticmap?size=200x200&maptype=terrain';
				if($coordArr) $url .= '&markers=size:tiny|'.implode('|',array_slice($coordArr,0,50));
				$url .= '&key='.$googleMapApiKey;
			}
		}
		return $url;
	}
}
?>