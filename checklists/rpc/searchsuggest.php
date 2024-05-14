<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
header('Content-Type: text/html; charset='.$CHARSET);

$clid = $_REQUEST['clid'];
$term = $_REQUEST['term'];
$deep = (isset($_REQUEST['deep'])?$_REQUEST['deep']:0);

$clManager = new ChecklistManager();
$retArr = $clManager->getTaxonSearch($term, $clid, $deep);
echo json_encode($retArr);
?>