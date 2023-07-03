<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');
header('Content-Type: text/html; charset='.$CHARSET);

$clid = filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT);
$occid = filter_var($_REQUEST['occid'], FILTER_SANITIZE_NUMBER_INT);
$taxon = $_REQUEST['taxon'];

if($taxon && is_numeric($occid) && is_numeric($clid)){
	if($IS_ADMIN || (array_key_exists('ClAdmin', $USER_RIGHTS) && in_array($clid, $USER_RIGHTS['ClAdmin']))){
		$clManager = new ChecklistVoucherAdmin();
		$clManager->setClid($clid);
		if($clManager->linkVoucher($taxon, $occid)) echo 'Success! Voucher added to checklist.';
		else echo 'Unable to link voucher: '.$clManager->getErrorMessage();
	}
}
?>