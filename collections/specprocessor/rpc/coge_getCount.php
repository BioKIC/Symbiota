<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Content-Type: text/html; charset=".$CHARSET);

$collid = $_REQUEST["collid"];
$cntStr = '';
if($collid && is_numeric($collid)){
	$isEditor = false;
	if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))) $isEditor = true;

	if($isEditor){
		$processingStatus = array_key_exists('ps',$_REQUEST)?$_REQUEST['ps']:'';
		$customField1 = array_key_exists('cf1',$_REQUEST)?$_REQUEST['cf1']:'';
		$customField2 = array_key_exists('cf2',$_REQUEST)?$_REQUEST['cf2']:'';
		$customField3 = array_key_exists('cf3',$_REQUEST)?$_REQUEST['cf3']:'';

		$dwcaHandler = new DwcArchiverCore();
		$dwcaHandler->setCollArr($collid);
		$dwcaHandler->setVerboseMode(0);
		$dwcaHandler->setOverrideConditionLimit(true);
		$dwcaHandler->addCondition('catalognumber','NOT_NULL');
		$dwcaHandler->addCondition('locality','NOT_NULL');
		if($processingStatus) $dwcaHandler->addCondition('processingstatus','EQUALS',$processingStatus);
		if($customField1) $dwcaHandler->addCondition($customField1,$_REQUEST['ct1'],$_REQUEST['cv1']);
		if($customField2) $dwcaHandler->addCondition($customField2,$_REQUEST['ct2'],$_REQUEST['cv2']);
		if($customField3) $dwcaHandler->addCondition($customField3,$_REQUEST['ct3'],$_REQUEST['cv3']);
		$cntStr = $dwcaHandler->getOccurrenceCnt();
	}
}
echo json_encode($cntStr)
?>