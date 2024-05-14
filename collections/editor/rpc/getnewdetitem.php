<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorDeterminations.php');

$collid = array_key_exists('collid' ,$_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : '';
$catNum = array_key_exists('catalognumber', $_REQUEST) ? $_REQUEST['catalognumber'] : '';
$allCatNum = array_key_exists('allcatnum', $_REQUEST) ? filter_var($_REQUEST['allcatnum'], FILTER_SANITIZE_NUMBER_INT) : 0;
$sciName = array_key_exists('sciname', $_REQUEST) ? $_REQUEST['sciname'] : '';

$retArr = array();
if($collid){
	$occManager = new OccurrenceEditorDeterminations();
	$occManager->setCollId($collid);
	$retArr = $occManager->getNewDetItem($catNum, $sciName, $allCatNum);
}

echo json_encode($retArr);
?>