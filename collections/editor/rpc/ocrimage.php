<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorOcr.php');

$imgid = filter_var($_REQUEST['imgid'], FILTER_SANITIZE_NUMBER_INT);
$x = array_key_exists('x', $_REQUEST) ? $_REQUEST['x'] : 0;
$y = array_key_exists('y', $_REQUEST) ? $_REQUEST['y'] : 0;
$w = array_key_exists('w', $_REQUEST) ? $_REQUEST['w'] : 1;
$h = array_key_exists('h', $_REQUEST) ? $_REQUEST['h'] : 1;
$ocrBest = array_key_exists('ocrbest', $_REQUEST) ? filter_var($_REQUEST['ocrbest'], FILTER_SANITIZE_NUMBER_INT) : 0;
$target = array_key_exists('target', $_REQUEST) ? $_REQUEST['target'] : 'tesseract';

$rawStr = '';
$ocrManager = new SpecProcessorOcr();
$ocrManager->setCropX($x);
$ocrManager->setCropY($y);
$ocrManager->setCropW($w);
$ocrManager->setCropH($h);
$rawStr = $ocrManager->ocrImageById($imgid, $target, $ocrBest);

echo $rawStr;
?>