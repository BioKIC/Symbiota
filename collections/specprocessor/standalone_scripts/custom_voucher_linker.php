<?php
/*
 * This tool can be used to batch link vouchers to a checklist based on project specific query terms
 * Script will estabish voucher linkages of specimens and observations based on $numberOfVouchersToLoad variable
 * Vouchers are ranked based on certain criteria
 * Steps to use: 1) Modify checklist identifier (clid), 2) Customize criteria and code to match project specific needs, 3) Uncomment line 14, 4) Run from command line: php voucher_linker.php
 */

include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;

$voucherLinker = new VoucherLinker();

if($clid && is_numeric($clid)) $voucherLinker->setClid($clid);

//$voucherLinker->linkVouchers();

class VoucherLinker {

	private $conn;
	private $clid = 4905;
	private $footprintWKT;
	private $numberOfVouchersToLoad = 5;
	private $linkSpecimens = true;
	private $linkObservations = true;
	private $excludeCultivated = true;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		set_time_limit(600);
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	public function linkVouchers(){
		//Uncomment following line to remove and reset existing voucher links
		$sql = 'DELETE FROM fmvouchers WHERE clid = '.$this->clid;
		//if(!$this->conn->query($sql)) echo 'ERROR resetting voucher: '.$this->conn->error."\n";

		//Set footprintWKT
		$sql = 'SELECT footprintwkt FROM fmchecklists WHERE clid = '.$this->clid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->footprintwkt) $this->footprintWKT = $r->footprintwkt;
		}
		$rs->free();

		//Get taxa and synonyms
		$taxaArr = array();
		$sql = 'SELECT DISTINCT c.tid, ts2.tid as syntid '.
			'FROM fmchklsttaxalink c INNER JOIN taxstatus ts ON c.tid = ts.tid '.
			'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
			'WHERE ts.taxauthid = 1 AND ts2.taxauthid = 1 AND c.clid = '.$this->clid;
		//echo $sql.'<br/>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$taxaArr[$r->tid][] = $r->syntid;
		}
		$rs->free();

		$cnt = 1;
		foreach($taxaArr as $targetTid => $tidArr){
			if($this->linkSpecimens) $this->loadVouchers($targetTid,$tidArr,array('Preserved Specimens'));
			if($this->linkObservations) $this->loadVouchers($targetTid,$tidArr,array('General Observations','Observations'));
			if($cnt%200 == 0) echo $cnt." taxa processed \n";
			$cnt++;
		}
	}

	public function loadVouchers($targetTid,$tidArr,$collTypeArr){
		//Load vouchers
		$voucherArr = array();
		$oldest = '';
		$oldestOccid = 0;
		$newest = '';
		$newestOccid = 0;
		$sql = 'SELECT DISTINCT o.collid, o.occid, o.recordedby, o.recordnumber, o.eventdate, o.establishmentmeans, o.decimallatitude, c.colltype ';
		if($this->footprintWKT) $sql .= ',ST_Within(p.point,GeomFromText("'.$this->footprintWKT.'")) as inzone ';
		$sql .= 'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid ';
		if($this->footprintWKT) $sql .= 'LEFT JOIN omoccurpoints p ON o.occid = p.occid ';
		$sql .= 'WHERE (o.stateprovince = "New York") AND (o.county LIKE "Bronx%" OR o.county LIKE "Kings%" OR o.county LIKE "New York%" OR o.county LIKE "Queens%" OR o.county LIKE "Richmond%") '.
			'AND (o.tidinterpreted IN('.implode(',',$tidArr).')) AND (cultivationStatus IS NULL OR cultivationStatus = 0) AND (c.colltype IN("'.implode('","', $collTypeArr).'"))';
		//echo $sql.'<br/>'; exit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$ranking = 0;
			if($r->eventdate){
				$ranking++;
				if(!$oldest || $oldest < $r->eventdate){
					$oldest = $r->eventdate;
					$oldestOccid = $r->occid;
				}
				if(!$newest || $newest > $r->eventdate){
					$newest = $r->eventdate;
					$newestOccid = $r->occid;
				}
			}
			if($this->excludeCultivated && stripos($r->establishmentmeans,'cultivate') !== false){
				continue;
			}
			if($r->collid == 40) $ranking++;
			if(strpos($r->recordedby,'Atha') !== false) $ranking++;
			if($r->decimallatitude){
				$ranking++;
				if($this->footprintWKT && $r->inzone == 0) continue;
			}
			if($r->recordnumber){
				if(!preg_match('/^s\.{0,1}n\.{0,1}$/', $r->recordnumber)) $ranking++;
				if($r->recordnumber > 1000) $ranking++;
			}
			$voucherArr[$r->occid] = $ranking;
		}
		$rs->free();
		if($oldestOccid && isset($voucherArr[$oldestOccid])) $voucherArr[$oldestOccid] += 20;
		if($newestOccid && isset($voucherArr[$newestOccid])) $voucherArr[$newestOccid] += 20;

		//Sort and load vouchers
		asort($voucherArr);
		$vouchersToLoad = array_slice($voucherArr,-1*$this->numberOfVouchersToLoad,null,true);
		foreach($vouchersToLoad as $occid => $r){
			//$sql2 = 'INSERT INTO fmvouchers(tid,clid,occid) VALUES('.$targetTid.','.$this->clid.','.$occid.')';
			if(!$this->conn->query($sql2)){
				echo 'ERROR loading voucher: '.$this->conn->error."\n";
				echo "\tSQL: ".$sql2."\n";
			}
		}
	}

	//Setter and getters
	public function setClid($clid){
		if(is_numeric($clid)) $this->clid = $clid;
	}
}
?>