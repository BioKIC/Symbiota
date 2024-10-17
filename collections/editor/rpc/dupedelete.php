<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/rpc/editor_rpc.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/editor/rpc/editor_rpc.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/editor/rpc/editor_rpc.en.php');

$dupid = array_key_exists('dupid',$_REQUEST)?$_REQUEST['dupid']:'';
$occid = array_key_exists('occid',$_REQUEST)?$_REQUEST['occid']:'';

$isEditor = false;
if(array_key_exists("CollAdmin",$USER_RIGHTS)) $isEditor = true;
elseif(array_key_exists("CollEditor",$USER_RIGHTS)) $isEditor = true;
if($IS_ADMIN || $isEditor){
	if(is_numeric($occid) && is_numeric($dupid)){
		$dupeManager = new OccurrenceDuplicate();
		if($dupeManager->deleteOccurFromCluster($dupid, $occid)){
			echo '1';
		}
		else{
			echo $dupeManager->getErrorStr();
		}
	}
	else{
		echo $LANG['ERROR_1'];
	}
}
else{
	echo $LANG['ERROR_2'];
}
?>