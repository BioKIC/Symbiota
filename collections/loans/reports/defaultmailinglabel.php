<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLoans.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/loans/reports/defaultmailinglabel.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/loans/reports/defaultmailinglabel.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/loans/reports/defaultmailinglabel.en.php');
require_once $SERVER_ROOT.'/vendor/phpoffice/phpword/bootstrap.php';

$collId = $_REQUEST['collid'];
$outputMode = $_POST['outputmode'];
$identifier = array_key_exists('identifier',$_REQUEST)?$_REQUEST['identifier']:0;
$loanType = array_key_exists('loantype',$_REQUEST)?$_REQUEST['loantype']:0;
$institution = array_key_exists('institution',$_POST)?$_POST['institution']:0;
$accountNum = array_key_exists('mailaccnum',$_POST)?$_POST['mailaccnum']:0;

$loanManager = new OccurrenceLoans();
if($collId) $loanManager->setCollId($collId);

if($institution){
	$invoiceArr = $loanManager->getToAddress($institution);
}
else{
	$invoiceArr = $loanManager->getInvoiceInfo($identifier,$loanType);
}
$addressArr = $loanManager->getFromAddress($collId);

if($outputMode == 'doc'){
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	$phpWord->addParagraphStyle('fromAddress', array('align'=>'left','lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
	$phpWord->addFontStyle('fromAddressFont', array('size'=>10,'name'=>'Arial'));
	$phpWord->addParagraphStyle('toAddress', array('align'=>'left','indent'=>2,'lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
	$phpWord->addFontStyle('toAddressFont', array('size'=>14,'name'=>'Arial'));

	$section = $phpWord->addSection(array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>360,'marginRight'=>360,'marginTop'=>360,'marginBottom'=>360,'headerHeight'=>0,'footerHeight'=>0));

	$textrun = $section->addTextRun('fromAddress');
	$textrun->addText(htmlspecialchars($addressArr['institutionname'].' ('.$addressArr['institutioncode'].')'),'fromAddressFont');
	$textrun->addTextBreak(1);
	if($addressArr['institutionname2']){
		$textrun->addText(htmlspecialchars($addressArr['institutionname2']),'fromAddressFont');
		$textrun->addTextBreak(1);
	}
	if($addressArr['address1']){
		$textrun->addText(htmlspecialchars($addressArr['address1']),'fromAddressFont');
		$textrun->addTextBreak(1);
	}
	if($addressArr['address2']){
		$textrun->addText(htmlspecialchars($addressArr['address2']),'fromAddressFont');
		$textrun->addTextBreak(1);
	}
	$textrun->addText(htmlspecialchars($addressArr['city'].($addressArr['stateprovince']?', ':'').$addressArr['stateprovince'].' '.$addressArr['postalcode']),'fromAddressFont');
	$textrun->addTextBreak(1);
	$textrun->addText(htmlspecialchars($addressArr['country']),'fromAddressFont');
	if($accountNum){
		$textrun->addTextBreak(1);
		$textrun->addText(htmlspecialchars('(Acct. #'.$accountNum.')'),'fromAddressFont');
	}
	$section->addTextBreak(2);
	$textrun = $section->addTextRun('toAddress');
	$textrun->addText(htmlspecialchars($invoiceArr['contact']),'toAddressFont');
	$textrun->addTextBreak(1);
	$textrun->addText(htmlspecialchars($invoiceArr['institutionname'].' ('.$invoiceArr['institutioncode'].')'),'toAddressFont');
	$textrun->addTextBreak(1);
	if($invoiceArr['institutionname2']){
		$textrun->addText(htmlspecialchars($invoiceArr['institutionname2']),'toAddressFont');
		$textrun->addTextBreak(1);
	}
	if($invoiceArr['address1']){
		$textrun->addText(htmlspecialchars($invoiceArr['address1']),'toAddressFont');
		$textrun->addTextBreak(1);
	}
	if($invoiceArr['address2']){
		$textrun->addText(htmlspecialchars($invoiceArr['address2']),'toAddressFont');
		$textrun->addTextBreak(1);
	}
	$textrun->addText(htmlspecialchars($invoiceArr['city'].($invoiceArr['stateprovince']?', ':'').$invoiceArr['stateprovince'].' '.$invoiceArr['postalcode']),'toAddressFont');
	$textrun->addTextBreak(1);
	$textrun->addText(htmlspecialchars($invoiceArr['country']),'toAddressFont');
	$targetFile = $SERVER_ROOT.'/temp/report/'.$PARAMS_ARR['un'].'_mailing_label.docx';
	$phpWord->save($targetFile, 'Word2007');

	ob_start();
	ob_clean();
	ob_end_flush();
	header('Content-Description: File Transfer');
	header('Content-type: application/force-download');
	header('Content-Disposition: attachment; filename='.basename($targetFile));
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: '.filesize($targetFile));
	readfile($targetFile);
	unlink($targetFile);
}
else{
	?>
	<!DOCTYPE html>
	<html lang="<?php echo $LANG_TAG ?>">
		<head>
			<title>Mailing Label</title>
			<?php

			include_once($SERVER_ROOT.'/includes/head.php');
			?>
			<style type="text/css">
				p.printbreak {page-break-after:always;}
				.fromaddress {font:10pt arial,sans-serif;}
				.toaddress {margin-left:1in;font:14pt arial,sans-serif;}
			</style>
		</head>
		<body style="background-color:#ffffff;">
			<h1 class="page-heading screen-reader-only"><?php echo $LANG['MAILING_LABEL']; ?></h1>
			<div>
				<table style="width:8in;">
					<tr>
						<td></td>
						<td>
							<div class="fromaddress">
								<?php
								echo $addressArr['institutionname'].' ('.$addressArr['institutioncode'].')<br />';
								if($addressArr['institutionname2']){
									echo $addressArr['institutionname2'].'<br />';
								}
								if($addressArr['address1']){
									echo $addressArr['address1'].'<br />';
								}
								if($addressArr['address2']){
									echo $addressArr['address2'].'<br />';
								}
								echo $addressArr['city'].($addressArr['stateprovince']?', ':'').$addressArr['stateprovince'].' '.$addressArr['postalcode'].'<br />'.$addressArr['country'].'<br />';
								if($accountNum){
									echo '(Acct. #'.$accountNum.')<br />';
								}
								echo '<br />';
								?>
							</div>
							<br />
							<br />
							<div class="toaddress">
								<?php
								echo $invoiceArr['contact'].'<br />';
								echo $invoiceArr['institutionname'].' ('.$invoiceArr['institutioncode'].')<br />';
								if($invoiceArr['institutionname2']){
									echo $invoiceArr['institutionname2'].'<br />';
								}
								if($invoiceArr['address1']){
									echo $invoiceArr['address1'].'<br />';
								}
								if($invoiceArr['address2']){
									echo $invoiceArr['address2'].'<br />';
								}
								echo $invoiceArr['city'].($invoiceArr['stateprovince']?', ':'').$invoiceArr['stateprovince'].' '.$invoiceArr['postalcode'].'<br />'.$invoiceArr['country'];
								?>
							</div>
						</td>
					</tr>
				</table>
			</div>
		</body>
	</html>
	<?php
}
?>