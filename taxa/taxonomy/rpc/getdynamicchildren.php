<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/RpcTaxonomy.php');
header('Content-Type: application/json; charset=' . $CHARSET);

$objId = array_key_exists('id',$_REQUEST)?$_REQUEST['id']:0;
$targetId = !empty($_REQUEST['targetid']) ? filter_var($_REQUEST['targetid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$taxAuthId = !empty($_REQUEST['taxauthid']) ? filter_var($_REQUEST['taxauthid'], FILTER_SANITIZE_NUMBER_INT) : 1;
$editorMode = empty($_REQUEST['emode']) ? 0 : 1;
$displayAuthor = !empty($_REQUEST['authors']) ? 1 : 0;
$limitToOccurrences = !empty($_REQUEST['limittooccurrences']) ? 1 : 0;

$rpcManager = new RpcTaxonomy();
$rpcManager->setTaxAuthId($taxAuthId);

$retArr = $rpcManager->getDynamicChildren($objId, $targetId, $displayAuthor, $limitToOccurrences, $editorMode);
echo json_encode($retArr);
?>