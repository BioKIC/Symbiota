<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/RpcOccurrenceEditor.php');

$term = $_POST['term'];

$editorManager = new RpcOccurrenceEditor();
$retStr = $editorManager->getExsiccatiID($term);

echo $retStr;
?>