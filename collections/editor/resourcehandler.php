<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceEditorResource.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$occid = filter_var($_POST['occid'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
$collid = filter_var($_POST['collid'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
$occIndex = filter_var($_POST['occindex'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
$action = (isset($_POST['submitaction']) ? $_POST['submitaction'] : '');

if($occid && $SYMB_UID){
	$occManager = new OccurrenceEditorResource();
	$occManager->setOccId($occid);
	$occManager->setCollId($collid);
	$occManager->getOccurMap();
	$isEditor = false;
	if($IS_ADMIN) $isEditor = true;
	elseif($collid && isset($USER_RIGHTS['CollAdmin']) && in_array($collid, $USER_RIGHTS['CollAdmin'])) $isEditor = true;
	elseif($collid && isset($USER_RIGHTS['CollEditor']) && in_array($collid, $USER_RIGHTS['CollEditor'])) $isEditor = true;
	elseif($occManager->isPersonalManagement()) $isEditor = true;
	if($isEditor){
		if($action == 'createAssociation'){
			$occManager->addAssociation($_POST);
		}
		elseif($action == 'editAssociation'){
			$occManager->updateAssociation($_POST);
		}
		elseif(array_key_exists('delassocid', $_POST)){
			$occManager->deleteAssociation($_POST['delassocid']);
		}
	}
	header('Location: occurrenceeditor.php?tabtarget=3&occid='.$occid.'&occindex='.$occIndex.'&collid='.$collid);
}

?>