<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/RpcOccurrenceEditor.php');

$occid = $_POST['occid'];

$editorManager = new RpcOccurrenceEditor();
$retStr = $editorManager->getImageCount($occid);

echo $retStr;
?>