<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMapManager.php');

header('Content-Type: application/json;charset='.$CHARSET);
include_once($SERVER_ROOT . '/rpc/crossPortalHeaders.php');

/*
$distFromMe = array_key_exists('distFromMe', $_REQUEST)?$_REQUEST['distFromMe']:'';
$gridSize = array_key_exists('gridSizeSetting', $_REQUEST) && $_REQUEST['gridSizeSetting']?$_REQUEST['gridSizeSetting']:60;
$minClusterSize = array_key_exists('minClusterSetting',$_REQUEST)&&$_REQUEST['minClusterSetting']?$_REQUEST['minClusterSetting']:10;
$clusterOff = array_key_exists('clusterSwitch',$_REQUEST)&&$_REQUEST['clusterSwitch']?$_REQUEST['clusterSwitch']:'n';


$catId = array_key_exists('catid',$_REQUEST)?$_REQUEST['catid']:0;
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;
$submitForm = array_key_exists('submitform',$_REQUEST)?$_REQUEST['submitform']:'';

if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;

$recLimit = array_key_exists('recordlimit',$_REQUEST)?$_REQUEST['recordlimit']:15000;

//Sanitation
if(!is_numeric($gridSize)) $gridSize = 60;
if(!is_numeric($minClusterSize)) $minClusterSize = 10;
if(!is_string($clusterOff) || strlen($clusterOff) > 1) $clusterOff = 'n';
if(!is_numeric($distFromMe)) $distFromMe = '';
if(!is_numeric($catId)) $catId = 0;
if(!is_numeric($tabIndex)) $tabIndex = 0;
if(!is_numeric($recLimit)) $recLimit = 15000;
*/

ob_start();
$recLimit = array_key_exists('recordlimit',$_REQUEST)?$_REQUEST['recordlimit']:15000;
if(!is_numeric($recLimit)) $recLimit = 15000;

$mapManager = new OccurrenceMapManager();
$searchVar = $mapManager->getQueryTermStr();

$obsIDs = $mapManager->getObservationIds();

if($searchVar && $recLimit) $searchVar .= '&reclimit='.$recLimit;

//Gets Coordinates
$coordArr = $mapManager->getCoordinateMap(0,$recLimit);
$taxaArr = [];
$recordArr = [];
$collArr = [];
$defaultColor = "#B2BEB5";

$host = false;
if(isset($SERVER_HOST)) {
   $host = ($SERVER_HOST === '127.0.0.1' || $SERVER_HOST === 'localhost'? "http://": "https://") . $SERVER_HOST . $CLIENT_ROOT;
}

foreach ($coordArr as $collName => $coll) {
	//Collect all the collections
	foreach ($coll as $recordId => $record) {
		if($recordId == 'c') continue;

		//Collect all taxon
		if(!array_key_exists($record['tid'], $taxaArr)) {
			$taxaArr[$record['tid']] = [
				'sn' => $record['sn'], 
				'tid' => $record['tid'], 
				'family' => $record['fam'],
				'color' => $coll['c'],
			];
		}

		//Collect all Collections
		if(!array_key_exists($record['collid'], $collArr)) {
			$collArr[$record['collid']] = [
				'name' => $collName,
				'collid' => $record['collid'],
				'color' => $coll['c'],
			];
		} 

		$llstrArr = explode(',', $record['llStr']);
		if(count($llstrArr) != 2) continue;

		//Collect all records
		array_push($recordArr, [
			'id' => $record['id'], 
			'tid' => $record['tid'], 
			'collid' => $record['collid'], 
			'family' => $record['fam'],
			'occid' => $recordId,
			'host' => $host,
			'collname' => $collName, 
			'type' => in_array($record['collid'], $obsIDs)? 'observation':'specimen', 
			'lat' => floatval($llstrArr[0]),
			'lng' => floatval($llstrArr[1]),
		]);
	}
}
ob_get_clean();

echo json_encode(['taxaArr' => $taxaArr, 'collArr' => $collArr, 'recordArr' => $recordArr, 'origin' => $host, 'query' => $searchVar]);
?>
