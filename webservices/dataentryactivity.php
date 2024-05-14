<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDownload.php');

$format = isset($_REQUEST['format'])&&$_REQUEST['format']?$_REQUEST['format']:'rss';
$days = isset($_REQUEST['days']) ? filter_var($_REQUEST['days'], FILTER_SANITIZE_NUMBER_INT) : 0;
$limit = isset($_REQUEST['limit']) ? filter_var($_REQUEST['limit'], FILTER_SANITIZE_NUMBER_INT) : 0;

$activityManager = new OccurrenceDownload();

ob_start();
ob_clean();
ob_end_flush();
header('Content-Description: '.$GLOBALS['DEFAULT_TITLE'].' Data Entry Activity');
header('Content-Type: '.($format=='json'?'application/json':'text/xml'));
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Cache-Control: no-cache');
header('Pragma: no-cache');

echo $activityManager->getDataEntryActivity($format, $days, $limit);
?>