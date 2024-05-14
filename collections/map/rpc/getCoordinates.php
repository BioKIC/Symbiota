<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/MapSupport.php');

header('Content-Type: application/json');

$tid = $_REQUEST['tid'];
$bounds = $_REQUEST['bounds'];

$retArr = false;
if($IS_ADMIN && is_numeric($tid)){
	$mapManager = new MapSupport();
	//$tid = $mapManager->sanitizeInt($tid);
	$retArr = $mapManager->getCoordinates($tid, $bounds);
}

echo json_encode($retArr);
?>
