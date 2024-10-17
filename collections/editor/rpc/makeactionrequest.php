<?php
	include_once('../../../config/symbini.php');
	include_once($SERVER_ROOT.'/classes/OccurrenceActionManager.php');
   if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/rpc/editor_rpc.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/editor/rpc/editor_rpc.' . $LANG_TAG . '.php');
   else include_once($SERVER_ROOT . '/content/lang/collections/editor/rpc/editor_rpc.en.php');
	
	$occid = array_key_exists('occid',$_REQUEST)             ? $_REQUEST['occid']        : null;
	$requesttype = array_key_exists('requesttype',$_REQUEST) ? $_REQUEST['requesttype']  : null ;
	$remarks = array_key_exists('remarks',$_REQUEST)         ? $_REQUEST['remarks']      : '';
    $uid = $SYMB_UID;	

    if ($uid!=null) { 
	   $actionManager = new OccurrenceActionManager();
       $result = $actionManager->makeOccurrenceActionRequest($uid,$occid,$requesttype,$remarks);
       if ($result==null) { 
          $returnValue = $LANG['FAILED_ADD_REQUEST'] . '. ' . $actionManager->getErrorMessage();
       } else { 
          $returnValue = $LANG['ADDED_REQUEST'] . ' ' . $requesttype [$result];
       }
    } 

	echo $returnValue;
?>
