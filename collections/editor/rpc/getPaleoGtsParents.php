<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/RpcOccurrenceEditor.php');
header('Content-Type: application/json; charset='.$CHARSET);

$term = isset($_POST['term']) ? $_POST['term'] : '';

$retArr = array();
if($term){
	$searchManager = new RpcOccurrenceEditor();
	$retArr = $searchManager->getPaleoGtsParents($term);
}
echo json_encode($retArr);
?>