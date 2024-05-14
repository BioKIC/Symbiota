<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/classes/DynamicChecklistManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/checklists/checklist.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/checklists/checklist.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/checklists/checklist.en.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$lat = filter_var($_POST['lat'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$lng = filter_var($_POST['lng'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$radius = filter_var($_POST['radius'], FILTER_SANITIZE_NUMBER_INT);
$radiusUnits = $_POST['radiusunits'];
$dynamicRadius = isset($DYN_CHECKLIST_RADIUS) ?? 10;
$taxa = $_POST['taxa'];
$tid = filter_var($_POST['tid'], FILTER_SANITIZE_NUMBER_INT);
$interface = $_POST['interface'];

//sanitation
if($radiusUnits != 'mi') $radiusUnits == 'km';
echo 'lat: '.$lat.'; lng: '. $lng . '; radius: '. $radius;
$dynClManager = new DynamicChecklistManager();

if($taxa && !$tid) $tid = $dynClManager->getTid($taxa);
$dynClid = 0;
if($radius) $dynClid = $dynClManager->createChecklist($lat, $lng, $radius, $radiusUnits, $tid);
else $dynClid = $dynClManager->createDynamicChecklist($lat, $lng, $dynamicRadius, $tid);
if($dynClid){
	if($interface == "key"){
		header("Location: ".$CLIENT_ROOT."/ident/key.php?dynclid=".$dynClid."&taxon=All Species");
	}
	else{
		header("Location: ".$CLIENT_ROOT."/checklists/checklist.php?dynclid=".$dynClid);
	}
}
else echo $LANG['ERROR_GEN_CHECK'];
$dynClManager->removeOldChecklists();
?>
