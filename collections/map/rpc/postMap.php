<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/MapSupport.php');

header('Content-Type: application/json');

if($IS_ADMIN){
	$mapManager = new MapSupport();
	try {
		$result = $mapManager->postImage($_POST);
		echo json_encode(['msg' => $result]);
	} catch (Exception $e) {
		echo json_encode(['msg' => 'ERROR']);
	}
} else {
	echo json_encode(['msg' => 'Not Authorized']);
}
?>
