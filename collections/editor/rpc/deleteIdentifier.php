<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/RpcOccurrenceEditor.php');

$identifierID = $_REQUEST['identifierID'];
$occid = $_REQUEST['occid'];

$retStr = 0;
if($identifierID && is_numeric($occid)){
	$editorManager = new RpcOccurrenceEditor('write');
	if($editorManager->deleteIdentifier($identifierID, $occid)) $retStr = 1;
}
echo $retStr;
?>