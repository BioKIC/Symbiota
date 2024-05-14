<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverExpedition.php');

$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : '';
$collid = array_key_exists('collid', $_REQUEST) ? $_REQUEST['collid'] : 0;
$cond = array_key_exists('cond', $_REQUEST) ? $_REQUEST['cond'] : '';

$collid = '22';
$cond = 'stateprovince:Arizona;ocr:exsic';

if($collid){
	$dwcaHandler = new DwcArchiverExpedition();

	$dwcaHandler->setSilent(1);
	$dwcaHandler->setFileName('webreq');
	$dwcaHandler->setCollArr($collid);
	if($cond) $dwcaHandler->setConditionStr($cond);

	$archiveFile = $dwcaHandler->createDwcArchive();

	ob_start();
	ob_clean();
	ob_end_flush();
	if($archiveFile){
		header('Content-Description: DwC-A File Transfer');
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename='.basename($archiveFile));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($archiveFile));
		ob_clean();
		flush();
		//od_end_clean();
		readfile($archiveFile);
		unlink($archiveFile);
		exit;
	}
	else{
		header('Content-Description: DwC-A File Transfer Error');
		header('Content-Type: text/plain');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		echo 'Error: unable to create archive';
	}
}
else{
	ob_start();
	ob_clean();
	ob_end_flush();
	header('Content-Description: DwC-A File Transfer Error');
	header('Content-Type: text/plain');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	echo 'Error: collection identifier is not defined';
}
?>