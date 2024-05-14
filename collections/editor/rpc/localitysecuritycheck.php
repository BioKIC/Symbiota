<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/RpcOccurrenceEditor.php');
header('Content-Type: application/json; charset=' . $CHARSET);

$retStr = 0;
$tid = filter_var($_POST['tid'] ?? '', FILTER_SANITIZE_NUMBER_INT);
$state = $_POST['state'] ?? '';

if($tid && $state){
	$dataManager = new RpcOccurrenceEditor();
	$retStr = $dataManager->getStateSecuritySetting($tid, $state);
}

echo $retStr;
?>