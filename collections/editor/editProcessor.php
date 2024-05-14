<?php
if($isEditor){
	if(strpos($action,'MaterialSample')){
		include_once $SERVER_ROOT.'/classes/OmMaterialSample.php';
		$materialSampleManager = new OmMaterialSample();
		$materialSampleManager->setOccid($occId);
		$matSampleID = isset($_REQUEST['matSampleID'])?$_REQUEST['matSampleID']:'';
		if(!is_numeric($matSampleID)) $matSampleID = 0;
		$materialSampleManager->setMatSampleID($matSampleID);
		if($action == 'insertMaterialSample'){
			$materialSampleManager->insertMaterialSample($_POST);
		}
		elseif($action == 'updateMaterialSample'){
			$materialSampleManager->updateMaterialSample($_POST);
		}
		elseif($action == 'deleteMaterialSample'){
			$materialSampleManager->deleteMaterialSample();
		}
		$statusStr = $materialSampleManager->getErrorMessage();
	}
}
?>