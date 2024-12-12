<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: text/html; charset='.$CHARSET);

$collid = $_POST['collid'];
$archiveFile = '';
$retArr = array();
if($collid && is_numeric($collid)){
	$isEditor = false;
	if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))){
	 	$isEditor = true;
	}

	if($isEditor){
		$processingStatus = array_key_exists('ps',$_POST)?$_POST['ps']:'';

		$dwcaHandler = new DwcArchiverCore();
		$dwcaHandler->setCollArr($collid);
		$dwcaHandler->setCharSetOut('UTF-8');
		$dwcaHandler->setSchemaType('coge');
		$dwcaHandler->setExtended(false);
		$dwcaHandler->setDelimiter('csv');
		$dwcaHandler->setVerboseMode(0);
		$dwcaHandler->setRedactLocalities(0);
		$dwcaHandler->setIncludeDets(0);
		$dwcaHandler->setIncludeImgs(0);
		$dwcaHandler->setIncludeAttributes(0);
		$dwcaHandler->setIncludeIdentifiers(0);
		$dwcaHandler->setIncludeAssociations(0);
		$dwcaHandler->setOverrideConditionLimit(true);
		$dwcaHandler->addCondition('catalognumber','NOT_NULL');
		$dwcaHandler->addCondition('locality','NOT_NULL');
		if($processingStatus) $dwcaHandler->addCondition('processingstatus','EQUALS',$processingStatus);
		for($i = 1; $i < 4; $i++){
			if(array_key_exists('cf'.$i,$_POST) && $_POST['cf'.$i]){
				$dwcaHandler->addCondition($_POST['cf'.$i],$_POST['ct'.$i],$_POST['cv'.$i]);
			}
		}

		//Set GeoLocate CoGe variables
		$dwcaHandler->setGeolocateVariables(array('cogecomm'=>$_POST['cogecomm'],'cogename'=>$_POST['cogename'],'cogedescr'=>$_POST['cogedescr'],));

		//Set direct path to file
		$tPath = $SERVER_ROOT;
		if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\'){
			$tPath .= '/';
		}
		$pathFrag = 'temp/data/';
		$tPath .= $pathFrag;
		$dwcaHandler->setTargetPath($tPath);
		$cnt = $dwcaHandler->getOccurrenceCnt();
		$fileName = 'CoGe'.'_'.time();
		$dwcaHandler->createDwcArchive($fileName);

		//Set URL path to file
		$urlPath = $dwcaHandler->getServerDomain().$CLIENT_ROOT;
		if(substr($urlPath,-1) != '/' && substr($urlPath,-1) != '\\') $urlPath .= '/';
		$urlPath .= $pathFrag.$fileName.'_DwC-A.zip';

		if($cnt){
			if((@fclose(@fopen($urlPath,'r')))){
				$retArr['result']['status'] = 'SUCCESS';
				$retArr['result']['cnt'] = $cnt;
				$retArr['result']['path'] = $urlPath;
			}
			else{
				$retArr['result']['status'] = 'ERROR';
				$retArr['result']['message'] = 'ERROR: File does not exist';
			}
		}
		else{
			$retArr['result']['status'] = 'ERROR';
			$retArr['result']['message'] = 'ERROR: Zero records returned';
		}
	}
}
echo json_encode($retArr)
?>