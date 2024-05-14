<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');

$dwcaManager = new DwcArchiverCore();

ob_start();
ob_clean();
ob_end_flush();
header('Content-Description: '.$DEFAULT_TITLE.' Collections RSS Feed');
header('Content-Type: text/xml; charset=utf-8');
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache");
header("Pragma: no-cache");

echo $dwcaManager->getFullRss();
?>