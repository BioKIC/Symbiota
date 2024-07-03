<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLoans.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/loans/reports/defaultenvelope.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/loans/reports/defaultenvelope.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/loans/reports/defaultenvelope.en.php');
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

if($outputMode == 'doc'){
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	$phpWord->addParagraphStyle('acctnum', array('align'=>'left','indent'=>5.5,'lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
	$phpWord->addFontStyle('acctnumFont', array('size'=>8,'name'=>'Arial'));
	$phpWord->addParagraphStyle('toAddress', array('align'=>'left','indent'=>6,'lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
	$phpWord->addFontStyle('toAddressFont', array('size'=>12,'name'=>'Arial'));

	$section = $phpWord->addSection(array('pageSizeW'=>13662.992125984,'pageSizeH'=>5952.755905512,'marginLeft'=>360,'marginRight'=>360,'marginTop'=>360,'marginBottom'=>360,'headerHeight'=>0,'footerHeight'=>0));
	$section->addTextBreak(5);
	if($accountNum){
		$section->addText(htmlspecialchars('Acct. #'.$accountNum),'acctnumFont','acctnum');
	}
	$section->addTextBreak(1);
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

	$targetFile = $SERVER_ROOT.'/temp/report/'.$PARAMS_ARR['un'].'_addressed_envelope.docx';
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
			<title>Addressed Envelope</title>
			<?php

			include_once($SERVER_ROOT.'/includes/head.php');
			?>
			<style type="text/css">
				p.printbreak {page-break-after:always;}
				.accnum {margin-left:2.5in;font:8pt arial,sans-serif;}
				.toaddress {margin-left:3in;font:12pt arial,sans-serif;}
			</style>
		</head>
		<body style="background-color:#ffffff;">
			<h1 class="page-heading screen-reader-only"><?php echo $LANG['ADDRESSED_ENVELOPE']; ?></h1>
			<div>
				<table>
					<tr style="height:1in;">
						<td></td>
					</tr>
					<tr>
						<td></td>
					</tr>
					<tr style="">
						<td>
							<?php
							if($accountNum) echo '<div class="accnum">Acct. #'.$accountNum.'</div>';
							?>
						</td>
					</tr>
					<tr style="height:1.5in;">
						<td>
							<div class="toaddress">
								<?php
								echo $invoiceArr['contact'].'<br />';
								echo $invoiceArr['institutionname'].' ('.$invoiceArr['institutioncode'].')<br />';
								if($invoiceArr['institutionname2']) echo $invoiceArr['institutionname2'].'<br />';
								if($invoiceArr['address1']) echo $invoiceArr['address1'].'<br />';
								if($invoiceArr['address2']) echo $invoiceArr['address2'].'<br />';
								echo $invoiceArr['city'].($invoiceArr['stateprovince']?', ':'').$invoiceArr['stateprovince'].' '.$invoiceArr['postalcode'].'<br/>';
								echo $invoiceArr['country'];
								?>
							</div>
						</td>
					</tr>
					<tr>
						<td></td>
					</tr>
				</table>
			</div>
		</body>
	</html>
	<?php
}
?>