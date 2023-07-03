<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonProfile.php');
include_once($SERVER_ROOT.'/classes/TraitPolarPlot.php');
include_once($SERVER_ROOT.'/classes/TraitBarPlot.php');
include_once($SERVER_ROOT.'/classes/TraitLinePlot.php');


class TraitPlotManager extends TaxonProfile {
	// consider extending TPEditorManager to have plots written to description block statements instead

	// PROPERTIES
	private $traitId;
	private $stateId;
	private $traitName;
	private $stateNameArr = array();
	// private $taxonArr = array(); // get this from parent
	private $summarizedByType;
	private $plotInstance;
	private $plotCaption;


	// METHODS

	// ### Public Methods ###
  public function __construct($type = "polar"){
		parent::__construct();
		switch(strtolower($type)) {
			case "polar":
				$this->plotInstance = new PolarPlot();
				break;
			case "bar":
				$this->plotInstance = new BarPlot();
				break;
			// case "box":
			// 	$this->plotInstance = new BoxPlot();
			// 	break;
			case "line":
				$this->plotInstance = new LinePlot();
				break;
			// case "point":
			// 	$this->plotInstance = new PointPlot();
			// 	break;
			default:
				$this->plotInstance = new PolarPlot();
		}
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function setTraitStateId($tsid){
		if(is_numeric($tsid) && $tsid > 0){
			$this->stateId = $tsid;
			$this->traitId = null;
			$this->setTraitStateNames();
			$this->setTraitName();
		}
	}

	public function setTraitId($traitid){
		if(is_numeric($traitid) && $traitid > 0){
			$this->traitId = $traitid;
			$this->stateId = null;
			$this->setTraitName();
			$this->setTraitStateNames();
		}
	}


	public function getTraitName(){
		if(isset($this->traitName)){
			$retStr = $this->traitName;
		} else {
			$retStr = "Trait information unavailable";
		}
		return $retStr;
	}

	public function getStateName(){
		if(isset($this->stateNameArr)){
			$retStr = implode(", ", $this->stateNameArr);
		} else {
			$retStr = "Trait state unavailable";
		}
		return $retStr;
	}

	public function getPlotCaption() {
		return $this->plotCaption;
	}

	public function getViewboxWidth() {
		return $this->plotInstance->getPlotWidth();
	}

	public function getViewboxHeight() {
		return $this->plotInstance->getPlotHeight();
	}

	public function getNumberOfSpecimens() {
		return $this->plotInstance->getNumDataValues();
	}

	public function calendarPlot() {
		if($this->rankId > 180) {  // limit to below genus
			$this->plotInstance->setAxisNumber(12);
			$this->plotInstance->setAxisRotation(0);
			$this->plotInstance->setTickNumber(10); //3
			$this->plotInstance->setAxisLabels(array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'));
			$this->plotInstance->setDataValues($this->summarizeTraitByMonth());
			$this->setPlotCaption();
		}
	}

	public function display() {
		return $this->plotInstance->display();
	} // this function looks weird because I want this manager
	  // to run different plot types, but that isn't finished yet.


	### Private methods ###
	private function setTraitName(){
		$this->traitName = null;
		if($this->traitId){
			$sql = 'SELECT DISTINCT traitname FROM tmtraits WHERE traitid = ' . $this->traitId;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->traitName = $r->traitname;
			}
			$rs->free();
		} elseif($this->stateId) {
			$sql = 'SELECT DISTINCT traitname FROM tmtraits WHERE traitid = (SELECT DISTINCT traitid FROM tmstates WHERE stateid = ' . $this->stateId .' LIMIT 1);';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->traitName = $r->traitname;
			}
			$rs->free();
		} else {
			$this->traitName = 'Trait information unavailable';
		}
	}

	private function setTraitStateNames(){
		$this->stateNameArr = array();  // clears array
		if($this->traitId){
			$sql = 'SELECT DISTINCT stateid, statename FROM tmstates WHERE traitid = ' . $this->traitId . ' ORDER BY sortseq;';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->stateNameArr[$r->stateid] = $r->statename;
			}
			$rs->free();
		} elseif($this->stateId) {
			$sql = 'SELECT statename FROM tmstates WHERE stateid = ' . $this->stateId . ';';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->stateNameArr[$this->stateId] = $r->statename;
			}
			$rs->free();
		} else {
			$this->stateNameArr[1] = "Trait state unavailable";
		}
	}

	private function setPlotCaption($timeStr = null) {
		// where $by IN (month, year, geographic region)
		global $DEFAULT_TITLE;
		$num = $this->plotInstance->getNumDataValues();
		if(!$timeStr) { $timeStr = date(DATE_RFC2822); }
	 	$numStr = 'specimens';
	 	if($num == 1) { $numStr = 'specimen'; }
	 	if(array_key_exists($this->tid, $this->acceptedArr)){
		 	$this->plotCaption = "Frequency of " . $this->traitName . ' - ' . $this->getStateName() . ", by " . $this->summarizedByType . ", for " . $num . " herbarium " .
		 		$numStr . " of <i>" . $this->acceptedArr[$this->tid]['sciname'] . "</i> " . $this->acceptedArr[$this->tid]['author'] . " from the ".
		 		$DEFAULT_TITLE." (http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]) sampled on " . $timeStr . ".";
				// . " (including the lower taxa or synonyms: " . implode($this->taxonArr['synonymNames']) . ") ;
	 	}
	}

 	private function summarizeTraitByMonth($missing = 0){
		/*
		  summarizeTraitByMonth returns a 12-element array. Months with no data are
			 filled with the value passed to $missing; default is 0. Data with invalid
			 months (>12, <1, blank, etc.) are ignored.
		*/
		$countArr = array_fill(0, 12, $missing);  // missing value array
		if($this->tid && ($this->traitId || $this->stateId)){
			$searchtids = array($this->tid);
			if(isset($this->synonymArr)){
				$searchtids = array_merge($searchtids, array_keys($this->synonymArr));
			}
			// need to deal with child tids if we want to display genus
			 $sql = 'SELECT o.month, COUNT(o.occid) AS count FROM tmattributes AS a LEFT JOIN omoccurrences AS o ON o.occid = a.occid WHERE o.tidinterpreted IN(' . implode(",", $searchtids) . ') AND ';
			 if($this->stateId) {
				 $sql .= 'a.stateid = '. $this->stateId;
			//} elseif ($this->traitId) {
			//	 $sql .= 'a.traitid = '. $this->traitId;
			 }
			 $sql .= ' GROUP BY o.month;';
			 $rs = $this->conn->query($sql);
			 while($r = $rs->fetch_object()){
				 if($r->month > 0 && $r->month < 13) {
					 $countArr[$r->month-1] = (int)$r->count;
				 }
			 }
			 $rs->free();
			 $this->summarizedByType = "month";
		}
    return $countArr;
  }

}

?>
