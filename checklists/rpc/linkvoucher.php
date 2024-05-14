<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/ChecklistVoucherAdmin.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/checklists/rpc/linkvoucher.' . $LANG_TAG . '.php'))
	include_once($SERVER_ROOT.'/content/lang/checklists/rpc/linkvoucher.' . $LANG_TAG . '.php');
else
	include_once($SERVER_ROOT.'/content/lang/checklists/rpc/linkvoucher.en.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$clid = filter_var($_REQUEST['clid'] ?? '', FILTER_SANITIZE_NUMBER_INT);
$occid = filter_var($_REQUEST['occid'] ?? '', FILTER_SANITIZE_NUMBER_INT);
$taxon = $_REQUEST['taxon'] ?? '';

if($taxon && is_numeric($occid) && is_numeric($clid)){
	if($IS_ADMIN || (array_key_exists('ClAdmin', $USER_RIGHTS) && in_array($clid, $USER_RIGHTS['ClAdmin']))){
		$clManager = new ChecklistVoucherAdmin();
		$clManager->setClid($clid);
		if($clManager->linkVoucher($taxon, $occid)) echo $LANG['VOUCHER_ADDED'];
		else{
			$errStr = $clManager->getErrorMessage();
			if($errStr == 'voucherAlreadyLinked') $errStr = $LANG['VOUCHER_ALREADY_LINKED'];
			echo $LANG['UNABLE_TO_LINK'] . ': ' . $errStr;
		}
	}
}
?>