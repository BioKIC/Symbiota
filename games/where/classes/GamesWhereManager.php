<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class GamesWhereManager extends Manager {

	private $stateStr ='';
	private $minLat = 0;
	private $maxLat = 0;
	private $minLon = 0;
	private $maxLon = 0;
	private $debugMode = 0;

	public function __construct(){
		parent::__construct(null,'readonly');
	}

	public function __destruct(){
 		parent::__destruct();
	}

	public function getResultArr(){
		$query = 'SELECT occid FROM omoccurrences
			WHERE sciname NOT LIKE "" AND sciname NOT LIKE "indet.%"
			AND decimalLatitude between '.$this->minLat.' AND '.$this->maxLat.' AND decimalLongitude between '.$this->minLon.' AND '.$this->maxLon.' ';
		if($this->stateStr != 'Full Region') $query .= 'AND stateProvince in ("'.str_replace(', ','","',$this->stateStr).'") ';
		$query .= 'LIMIT 20';

		$resultArray = array();
		$searchArray = array();
		$tries = 0;
		$latSize = ($this->maxLat - $this->minLat)/50;		//Break the region down into rectangles, 50x50 in the full region.
		$lonSize = ($this->maxLon - $this->minLon)/50;
		do{
			do{
				if($this->debugMode) $this->logOrEcho('CurrentArea: '.$this->minLat.' '.$this->maxLat,'; '.$this->minLon.' '.$this->maxLon);
				$mult = 100; 		//Basically how many decimal places to leave in the floating Lat and Long when finding a random square.  Probably doesn't matter much
				$randomArea = $this->getBoundingBox();
				do{ //Find an area that is not in the ocean.
					$randomArea['minLat'] = rand(floor($mult*$this->minLat),floor($mult*$this->maxLat)-$latSize)/$mult;
					$randomArea['maxLat'] = $randomArea->minLat+$latSize;
					$randomArea['minLon'] = rand(floor($mult*$this->minLon),floor($mult*$this->maxLon)-$lonSize)/$mult;
					$randomArea['maxLon'] = $randomArea->minLon+$lonSize;
				} while(!$this->checkOcean($randomArea));

				$queryFrag = '';
				if($this->stateStr != 'Full Region') $queryFrag = 'AND stateProvince in ("'.str_replace(', ','","',$this->stateStr).'") ';

				$query = 'SELECT sciname, decimalLatitude, decimalLongitude, habitat FROM omoccurrences '.
					'WHERE sciname NOT LIKE "" AND sciname NOT LIKE "indet.%" AND decimalLatitude between '.$randomArea->minLat.' AND '.$randomArea->maxLat.' '.
					'AND decimalLongitude between '.$randomArea->minLon.' AND '.$randomArea->maxLon.' '.$queryFrag.' LIMIT 200';
				$result = $this->conn->query($query);
				while($row = $result->fetch_assoc()){
					$searchArray[] = $row;
				}
				$result->free();

				if($this->debugMode) $this->logOrEcho('MQ Count = '.count($searchArray));
			}while(count($searchArray) < 10);
			if($searchArray < 5){
				$this->logOrEcho('Error: '.count($searchArray).' specimens found in whole area.');
				return false;
			}

			for($i = 0;$i < 50;$i++){//RandomChoice is just a counter.  50 trials should be plenty.
				//Randomly choose one of the specimens from this region.
				$select = rand(0,count($searchArray)-1);
				$degrees = .1;
				//Search for all plants within 0.1 degrees of this random specimen (about 10 kilometers).
				if($currentArea = $this->getBoundingBox($searchArray[$select]['decimalLatitude'] - $degrees, $searchArray[$select]['decimalLatitude'] + $degrees, $searchArray[$select]['decimalLongitude'] - $degrees, $searchArray[$select]['decimalLongitude'] + $degrees)){
					$query = 'SELECT sciname, decimalLatitude, decimalLongitude, habitat from omoccurrences
						WHERE sciname NOT LIKE "" AND sciname NOT LIKE "indet.%" AND decimalLatitude != "" AND (decimalLatitude between '.$currentArea['minLat'].
						'AND '.$currentArea['maxLat'].') AND (decimalLongitude between '.$currentArea['minLon'].' AND '.$currentArea['maxLon'].') '.$queryFrag;
					if($this->debugMode) $this->logOrEcho('Query = '.$query);
					//First query SEINet
					$result = $this->query($query);
					while($row = $result->fetch_assoc()){
						$resultArray[] = $row;
					}
					$result->free();
				}
				if($this->debugMode) $this->logOrEcho('Then Found nearby '.count($resultArray));
				if(count($resultArray) >= 10) break; //We found a specimen that has at least 10 specimens nearby.
			}
			$tries++;
			if($tries > 200){
				$this->errorMessage = 'Error: Search failed.  Try again.';
				return false;
			}
		}while(count($resultArray) < 5);
		return $resultArray;
	}

	public function GetImageURL($SciName, $DBNum, $Sort){
		//Kind of messy.  Determining the best image is not scientific.  If the image sortsequence is 1, then it's probably a good one.  But in many cases the sortsequence is 50 for every image.
		global $DB;
		$TIDQuery = "SELECT TID FROM taxa WHERE SciName LIKE '$SciName%'";
		$TIDresult = $DB[$DBNum]->query($TIDQuery);
		$TID = '';
		if($TIDresult->num_rows > 0)
		{
			while($OneTID = $TIDresult->fetch_assoc())
			{
				if($TID != "") $TID .= ",";
				$TID .= $OneTID['TID'];
			}
		}
		else return "";//No TID was found for this plant, so unable to search for an image.

		if($TID == "") return "";
		$ImageQuery = "SELECT * FROM media WHERE tid IN ($TID) AND sortsequence = 1 LIMIT 1";
		$ImageResult = $DB[$DBNum]->query($ImageQuery);
		if($ImageResult->num_rows == 0)
		{//Look for the best possible image first.
			if($Sort) return "";
			$ImageQuery = "SELECT * FROM media WHERE tid IN ($TID) AND sortsequence > 0 ORDER BY sortsequence";
			$ImageResult = $DB[$DBNum]->query($ImageQuery);
		}

		if($ImageResult->num_rows == 0)
		{
			//die("Error: $SciName, $ImageQuery");
			$ImageQuery = "SELECT * FROM media WHERE tid IN ($TID) AND url != 'empty' AND sortsequence > 0 ORDER BY sortsequence";
			$ImageResult = $DB[$DBNum]->query($ImageQuery);
		}
		if($ImageResult->num_rows == 0)
		{ //Last try.  Any image will be accepted.
			$ImageQuery = "SELECT * FROM media WHERE tid IN ($TID) AND url != 'empty' LIMIT 1";
			$ImageResult = $DB[$DBNum]->query($ImageQuery);
		}
		$OneImageURL = $ImageResult->fetch_assoc();

		$URL = $OneImageURL['url'];
		if(strlen($URL) < 10) $URL = $OneImageURL['originalurl'];
		if($URL == "") return "";

		if(strstr($URL,"http") === false && strlen($URL) > 10)
		{ //Images that are on the swbiodiversity server are often stored with just local location.  Need to add the http://...
			$URL = "http://swbiodiversity.org".$URL;
		}
		return $URL;
	}

	public function checkOcean($area = null){
		//Make sure the rectangle $Area does not lie within an ocean
		//Done before querying the database, much faster than a bunch of queries that turn up empty.
		$minLat = $this->minLat;
		$maxLat = $this->maxLat;
		$minLon = $this->minLon;
		$maxLon = $this->maxLon;
		if($area){

		}
		$oceans = $this->getOceanBounds();
		foreach($oceans as $oceanArr){
			if(($minLat > $oceanArr[0]) && ($maxLat < $oceanArr[1]) && ($minLon > $oceanArr[2]) && ($maxLon < $oceanArr[3])){
				if($this->debugMode) $this->logOrEcho('Rejected');
				return false;
			}
		}
		return true;
	}

	public function setSearchBoundingBox($minLat, $maxLat, $minLon, $maxLon){
		$bounds = $this->getBoundingBox($minLat, $maxLat, $minLon, $maxLon);
		//Reduce the search area slightly from the visible area so that the target won't fall right on the edge
		//It still might if the random plant selected only has neighbors towards the edge, but unlikely.
		$this->minLat += 0.05*$this->getLatSpan($bounds['minLat'], $bounds['maxLat']);	//Raise it a little more because of the text on the bottom of the map.
		$this->maxLat -= 0.03*$this->getLatSpan($bounds['minLat'], $bounds['maxLat']);
		$this->minLon += 0.03*$this->getLonSpan($bounds['minLon'], $bounds['maxLon']);
		$this->maxLon -= 0.03*$this->getLonSpan($bounds['minLon'], $bounds['maxLon']);

		//  If any states were selected, limit the search to a rectangle bounded by those states borders.
		if($this->stateStr){
			$minLatBound = 1000;
			$minLonBound = 1000;
			$maxLatBound = -1000;
			$maxLonBound = -1000;
			if($this->stateStr != 'Full Region'){
				$states = explode(', ',$this->stateStr);
				$bounds = $this->getStateBounds();

				//Checks to see that the state selected is actually in view based on rectangular bounds
				foreach($states as $oneState){
					foreach($bounds as $oneBound){
						$boundState = explode(',',$oneBound);
						if($boundState[0] != $oneState) continue;

						if($boundState[1] < $minLonBound) $minLonBound = $boundState[1];
						if($boundState[2] < $minLatBound) $minLatBound = $boundState[2];
						if($boundState[3] > $maxLonBound) $maxLonBound = $boundState[3];
						if($boundState[4] > $maxLatBound) $maxLatBound = $boundState[4];
					}
				}
				if($this->minLon < $minLonBound) $this->minLon = $minLonBound;
				if($this->minLat < $minLatBound) $this->minLat = $minLatBound;
				if($this->maxLon > $maxLonBound) $this->maxLon = $maxLonBound;
				if($this->maxLat > $maxLatBound) $this->maxLat = $maxLatBound;
			}
		}
	}

	public function getBoundingBox($minLat=0, $maxLat=0, $minLon=0, $maxLon=0){
		while($minLon < -180) $minLon = 360+$minLon;
		while($maxLon < -180) $maxLon = 360+$maxLon;

		while($minLon > 180) $minLon = $minLon-360;
		while($maxLon >180) $maxLon = $maxLon-360;
		if($maxLon < $minLon && ($maxLon > 0 || $minLon < 0)){
			$this->errorMessage = 'Error: Map too large';
			return false;
		}
		if($minLon > $maxLon) $minLon = -180;
		$retArr = array('minLat'=>$minLat, 'maxLat'=>$maxLat, 'minLon'=>$minLon, 'maxLon'=>$maxLon);
		return $retArr;
	}

	private function getLatSpan($min, $max){
		return $max - $min;
	}

	private function getLonSpan($min, $max){
		if($min >0 && $max < 0) return 360 - $min + $max;		//Straddling the 180 meridian
		else return $max - $min;
	}

	//Date funcitons
	private function getStateBounds(){
		$retArr = array();
		$retArr['Alabama'] = array(-88.473227,30.223334,-84.88908,35.008028);
		$retArr['Alaska'] = array(-179.148909,51.214183,179.77847,71.365162);
		$retArr['Arizona'] = array(-114.81651,31.332177,-109.045223,37.00426);
		$retArr['Arkansas'] = array(-94.617919,33.004106,-89.644395,36.4996);
		$retArr['California'] = array(-124.409591,32.534156,-114.131211,42.009518);
		$retArr['Colorado'] = array(-109.060253,36.992426,-102.041524,41.003444);
		$retArr['Connecticut'] = array(-73.727775,40.980144,-71.786994,42.050587);
		$retArr['Delaware'] = array(-75.788658,38.451013,-75.048939,39.839007);
		$retArr['Florida'] = array(-87.634938,24.523096,-80.031362,31.000888);
		$retArr['Georgia'] = array(-85.605165,30.357851,-80.839729,35.000659);
		$retArr['Hawaii'] = array(-178.334698,18.910361,-154.806773,28.402123);
		$retArr['Idaho'] = array(-117.243027,41.988057,-111.043564,49.001146);
		$retArr['Illinois'] = array(-91.513079,36.970298,-87.494756,42.508481);
		$retArr['Indiana'] = array(-88.09776,37.771742,-84.784579,41.760592);
		$retArr['Iowa'] = array(-96.639704,40.375501,-90.140061,43.501196);
		$retArr['Kansas'] = array(-102.051744,36.993016,-94.588413,40.003162);
		$retArr['Kentucky'] = array(-89.571509,36.497129,-81.964971,39.147458);
		$retArr['Louisiana'] = array(-94.043147,28.928609,-88.817017,33.019457);
		$retArr['Maine'] = array(-71.083924,42.977764,-66.949895,47.459686);
		$retArr['Maryland'] = array(-79.487651,37.911717,-75.048939,39.723043);
		$retArr['Massachusetts'] = array(-73.508142,41.237964,-69.928393,42.886589);
		$retArr['Michigan'] = array(-90.418136,41.696118,-82.413474,48.2388);
		$retArr['Minnesota'] = array(-97.239209,43.499356,-89.491739,49.384358);
		$retArr['Mississippi'] = array(-91.655009,30.173943,-88.097888,34.996052);
		$retArr['Missouri'] = array(-95.774704,35.995683,-89.098843,40.61364);
		$retArr['Montana'] = array(-116.050003,44.358221,-104.039138,49.00139);
		$retArr['Nebraska'] = array(-104.053514,39.999998,-95.30829,43.001708);
		$retArr['Nevada'] = array(-120.005746,35.001857,-114.039648,42.002207);
		$retArr['New Hampshire'] = array(-72.557247,42.69699,-70.610621,45.305476);
		$retArr['New Jersey'] = array(-75.559614,38.928519,-73.893979,41.357423);
		$retArr['New Mexico'] = array(-109.050173,31.332301,-103.001964,37.000232);
		$retArr['New York'] = array(-79.762152,40.496103,-71.856214,45.01585);
		$retArr['North Carolina'] = array(-84.321869,33.842316,-75.460621,36.588117);
		$retArr['North Dakota'] = array(-104.0489,45.935054,-96.554507,49.000574);
		$retArr['Ohio'] = array(-84.820159,38.403202,-80.518693,41.977523);
		$retArr['Oklahoma'] = array(-103.002565,33.615833,-94.430662,37.002206);
		$retArr['Oregon'] = array(-124.566244,41.991794,-116.463504,46.292035);
		$retArr['Pennsylvania'] = array(-80.519891,39.7198,-74.689516,42.26986);
		$retArr['Rhode Island'] = array(-71.862772,41.146339,-71.12057,42.018798);
		$retArr['South Carolina'] = array(-83.35391,32.0346,-78.54203,35.215402);
		$retArr['South Dakota'] = array(-104.057698,42.479635,-96.436589,45.94545);
		$retArr['Tennessee'] = array(-90.310298,34.982972,-81.6469,36.678118);
		$retArr['Texas'] = array(-106.645646,25.837377,-93.508292,36.500704);
		$retArr['Utah'] = array(-114.052962,36.997968,-109.041058,42.001567);
		$retArr['Vermont'] = array(-73.43774,42.726853,-71.464555,45.016659);
		$retArr['Virginia'] = array(-83.675395,36.540738,-75.242266,39.466012);
		$retArr['Washington'] = array(-124.763068,45.543541,-116.915989,49.002494);
		$retArr['West Virginia'] = array(-82.644739,37.201483,-77.719519,40.638801);
		$retArr['Wisconsin'] = array(-92.888114,42.491983,-86.805415,47.080621);
		$retArr['Wyoming'] = array(-111.056888,40.994746,-104.05216,45.005904);
		$retArr['Sonora'] = array(-115.09,26.28,-108.39,32.54);
		return $retArr;
	}

	private function getOceanBounds(){
		$retArr = array();
		$retArr[] = array(4,11,-51,-17);
		$retArr[] = array(14,29,-154,-115);
		$retArr[] = array(-70,4,-34,5);
		$retArr[] = array(22,42,-68,-18.5);
		$retArr[] = array(11,22,-60,-26.5);
		$retArr[] = array(-70,-12.8,-170,-77);
		$retArr[] = array(-12.9,14,-170,-92.5);
		$retArr[] = array(29,49,-180,-126);
		$retArr[] = array(20,49,155,180);
		$retArr[] = array(33,59,-52,-12);
		$retArr[] = array(-70,5,60,95);
		$retArr[] = array(-70,-35,5,60);
		$retArr[] = array(29,33,-126,-119);
		$retArr[] = array(-12.9,-1.6,-92.5,-81.5);
		$retArr[] = array(59,63,-40,-8);
		$retArr[] = array(63,68,-31.4,-25);
		return $retArr;
	}

	//Setters and getters
	public function setStateStr($str){
		$this->stateStr = $str;
	}

	public function getMinLat(){
		return $this->minLat;
	}

	public function getMaxLat(){
		return $this->maxLat;
	}

	public function getMinLon(){
		return $this->minLon;
	}

	public function getMaxLon(){
		return $this->maxLon;
	}

	public function setDebugMode($bool){
		if($bool) $this->debugMode = true;
		else $this->debugMode = false;
	}
}
?>
