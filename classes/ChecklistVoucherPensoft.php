<?php
include_once($SERVER_ROOT.'/classes/ChecklistVoucherReport.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
require_once($SERVER_ROOT.'/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ChecklistVoucherPensoft extends ChecklistVoucherReport {

	function __construct() {
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	public function downloadPensoftXlsx(){
		$spreadsheet = new Spreadsheet();
		$taxaSheet = $spreadsheet->getActiveSheet()->setTitle('Taxa');
		$penArr = $this->getPensoftArr();
		$headerArr = $penArr['header'];
		$taxaArr = $penArr['taxa'];
		//print_r($taxaArr); exit;

		$letters = range('A', 'Z');
		//Output header
		$columnCnt = 0;
		foreach($headerArr as $headerValue){
			$colLet = $letters[$columnCnt%26].'1';
			if($columnCnt > 26) $colLet = $colLet.$letters[floor($columnCnt/26)];
			$taxaSheet->setCellValue($colLet, $headerValue);
			$columnCnt++;
		}

		//Output data
		$rowCnt = 2;
		foreach($taxaArr as $tid => $recArr){
			$columnCnt = 0;
			foreach($headerArr as $headerKey => $v){
				$colLet = $letters[$columnCnt%26].$rowCnt;
				if($columnCnt > 26) $colLet = $colLet.$letters[floor($columnCnt/26)];
				$cellValue = (isset($recArr[$headerKey])?$recArr[$headerKey]:'');
				$taxaSheet->setCellValue($colLet, $cellValue);
				$columnCnt++;
			}
			$rowCnt++;
		}

		//Create Materials worksheet
		$materialsSheet = $spreadsheet->createSheet(1)->setTitle('Materials');

		$dwcaHandler = new DwcArchiverCore();
		$dwcaHandler->setVerboseMode(0);
		$dwcaHandler->setCharSetOut('ISO-8859-1');
		$dwcaHandler->setSchemaType('pensoft');
		$dwcaHandler->setExtended(false);
		$dwcaHandler->setRedactLocalities(1);
		$dwcaHandler->addCondition('clid','EQUALS',$_REQUEST['clid']);
		$dwcArr = $dwcaHandler->getDwcArray();

		if($dwcArr){
			//Output header
			$headerArr = array_keys($dwcArr[0]);
			$columnCnt = 0;
			foreach($headerArr as $headerValue){
				$colLet = $letters[$columnCnt%26];
				if($columnCnt > 25) $colLet = $letters[floor(($columnCnt/26)-1)].$colLet;
				$materialsSheet->setCellValue($colLet.'1', $headerValue);
				$columnCnt++;
			}
			//Output occurrence records
			foreach($dwcArr as $cnt => $rowArr){
				$rowCnt = $cnt+2;
				$columnCnt = 0;
				foreach($rowArr as $colKey => $cellValue){
					$colLet = $letters[$columnCnt%26];
					if($columnCnt > 25) $colLet = $letters[floor(($columnCnt/26)-1)].$colLet;
					$materialsSheet->setCellValue($colLet.$rowCnt, $cellValue);
					$columnCnt++;
				}
			}
		}

		//Create ExternalLinks worksheet
		$spreadsheet->createSheet(2)->setTitle('ExternalLinks');

		//$file = $TEMP_DIR_ROOT.'/downloads/'.$this->getExportFileName().'.xlsx';
		$file = $this->getExportFileName().'.xlsx';
		ob_start();
		ob_clean();
		ob_end_flush();
		header('Content-Description: Checklist Pensoft Export');
		header('Content-Disposition: attachment; filename='.basename($file));
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		//header('Content-Length: '.filesize($file));
		header('Cache-Control: must-revalidate');
		header('Pragma: public');

		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');

	}

	protected function getPensoftArr(){
		$clidStr = $this->getClidFullStr();

		$clArr = array();
		$kingdomArr = array();
		//Get taxa data
		$sql = 'SELECT t.tid, t.kingdomname, t.sciname, t.author, t.unitname1, t.unitname2, t.unitind3, t.unitname3, t.rankid, c.familyoverride '.
			'FROM fmchklsttaxalink c INNER JOIN taxa t ON c.tid = t.tid '.
			'INNER JOIN taxstatus ts ON c.tid = ts.tid '.
			'WHERE (ts.taxauthid = 1) AND (c.clid IN('.$clidStr.')) '.
			'ORDER BY IFNULL(c.familyoverride, ts.family), t.sciname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if(isset($kingdomArr[$r->kingdomname])) $kingdomArr[$r->kingdomname] += 1;
			else $kingdomArr[$r->kingdomname] = 0;
			$clArr[$r->tid]['tid'] = $r->tid;
			//$clArr[$r->tid]['sciname'] = $r->sciname;
			$clArr[$r->tid]['author'] = $this->encodeStr($r->author);
			if($r->familyoverride) $clArr[$r->tid][140] = $r->familyoverride;
			if($r->rankid < 180){
				$clArr[$r->tid][$r->rankid] = $r->unitname1;
			}
			else{
				$clArr[$r->tid][180] = $r->unitname1;
				if($r->unitname2) $clArr[$r->tid]['epithet'] = $r->unitname2;
				if($r->unitname3){
					if($r->rankid == 230){
						$clArr[$r->tid]['subsp'] = $r->unitname3;
					}
					elseif($r->rankid == 240){
						$clArr[$r->tid]['var'] = $r->unitname3;
					}
					elseif($r->rankid == 260){
						$clArr[$r->tid]['f'] = $r->unitname3;
					}
					else{
						$clArr[$r->tid]['infra'] = $r->unitname3;
					}
				}
			}
		}
		$rs->free();

		$rankArr = array();
		//Get upper hierarchy
		$sql = 'SELECT t.tid, t2.sciname as parentstr, t2.rankid '.
			'FROM fmchklsttaxalink c INNER JOIN taxa t ON c.tid = t.tid '.
			'INNER JOIN taxaenumtree e ON c.tid = e.tid '.
			'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
			'WHERE (e.taxauthid = 1) AND (c.clid IN('.$clidStr.'))';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$clArr[$r->tid][$r->rankid] = $this->encodeStr($r->parentstr);
			$rankArr[$r->rankid] = $r->rankid;
		}
		$rs->free();

		$outArr = array();
		if($clArr){
			$outArr['taxa'] = $clArr;
			//Finish setting up rank array
			asort($kingdomArr);
			end($kingdomArr);
			$sql = 'SELECT rankid, rankname FROM taxonunits WHERE kingdomname = "'.key($kingdomArr).'" AND (rankid IN('.implode(',',$rankArr).')) ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$rankArr[$r->rankid] = $r->rankname;
			}
			$rs->free();

			//Set header array
			$headerArr = array('tid'=>'Taxon_Local_ID');
			ksort($rankArr);
			foreach($rankArr as $id => $name){
				if($id > 180 && !isset($headerArr[180])) $headerArr[180] = 'Genus';
				if($id >= 220) break;
				$headerArr[$id] = $name;
			}
			if(!isset($headerArr[180])) $headerArr[180] = 'Genus';
			$headerArr['epithet'] = 'Species';
			$headerArr['subsp'] = 'Subspecies';
			$headerArr['var'] = 'Variety';
			$headerArr['f'] = 'Form';
			$headerArr['author'] = 'Authorship';
			$headerArr['notes'] = 'Notes';
			$headerArr['habitat'] = 'Habitat';
			$headerArr['abundance'] = 'Abundance';
			$headerArr['source'] = 'Source';

			//set any unranked groups to unname node
			foreach($headerArr as $k => $v){
				if(is_numeric($v)) $headerArr[$k] = 'Unranked Node';
			}
			$outArr['header'] = $headerArr;
		}
		return $outArr;
	}
}
?>