<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');

$collid = isset($_REQUEST['collid']) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : '';

if($collid){
	$dwcaManager = new DwcArchiverCore();
	$dwcaManager->setCollArr($collid);
	if($collArr = $dwcaManager->getCollArr()){

		ob_start();
		ob_clean();
		ob_end_flush();
		header('Content-Description: '.$collArr[$collid]['collname'].' EML');
		header('Content-Type: text/xml; charset=utf-8');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Cache-Control: no-cache");
		header("Pragma: no-cache");

		$xmlDom = $dwcaManager->getEmlDom();
		echo $xmlDom->saveXML();
	}
}
?>