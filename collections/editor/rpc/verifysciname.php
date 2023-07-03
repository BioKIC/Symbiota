<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/RpcOccurrenceEditor.php');
header('Content-Type: application/json; charset='.$CHARSET);

$term = trim($_POST['term']);

$retArr = array();
if($term){
	$editorManager = new RpcOccurrenceEditor();
	$retArr = $editorManager->getTaxonArr($term);
}
if($retArr) echo json_encode($retArr);
else echo 'null';
?>