<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/RpcOccurrenceEditor.php');

$occid = $_POST['occid'];

$retArr = array();
if(is_numeric($occid)){
	$editorManager = new RpcOccurrenceEditor();
	$retArr = $editorManager->getOccurrenceVouchers($occid);
}
echo json_encode($retArr);
?>