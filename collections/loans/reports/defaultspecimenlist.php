<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLoans.php');
require_once $SERVER_ROOT.'/vendor/phpoffice/phpword/bootstrap.php';
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/loans/reports/defaultspecimenlist.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/loans/reports/defaultspecimenlist.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/reports/defaultspecimenlist.en.php');

$collId = $_REQUEST['collid'];
$outputMode = $_POST['outputmode'];
$loanId = array_key_exists('identifier',$_REQUEST)?$_REQUEST['identifier']:0;
$loanType = array_key_exists('loantype',$_REQUEST)?$_REQUEST['loantype']:0;

$loanManager = new OccurrenceLoans();
if($collId) $loanManager->setCollId($collId);

$invoiceArr = $loanManager->getInvoiceInfo($loanId,$loanType);
$addressArr = $loanManager->getFromAddress($collId);
$specList = $loanManager->getSpecimenList($loanId);
$targetCode = $invoiceArr['institutioncode'];
$sourceCode = $addressArr['institutioncode'];
if($loanType == 'in'){
	$targetCode = $addressArr['institutioncode'];
	$sourceCode = $invoiceArr['institutioncode'];
}

if($outputMode == 'doc'){
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	$phpWord->addParagraphStyle('header', array('align'=>'left','lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
	$phpWord->addFontStyle('headerFont', array('size'=>14,'name'=>'Arial'));
	$phpWord->addParagraphStyle('info', array('align'=>'left','lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
	$phpWord->addFontStyle('infoFont', array('size'=>11,'name'=>'Arial'));
	$phpWord->addFontStyle('colHeaderFont', array('size'=>8,'bold'=>true,'name'=>'Arial'));
	$phpWord->addFontStyle('colFont', array('size'=>8,'name'=>'Arial'));
	$phpWord->addParagraphStyle('colHeadSpace', array('lineHeight'=>1.5,'spaceAfter'=>0));
	$phpWord->addParagraphStyle('colSpace', array('lineHeight'=>1.3,'spaceAfter'=>0));
	$tableHeadStyle = array('borderBottomSize'=>10,'borderBottomColor'=>'000000','width'=>100);
	$tableStyle = array('width'=>100);
	$colRowStyle = array('cantSplit'=>true);
	$phpWord->addTableStyle('headerTable',$tableHeadStyle,$colRowStyle);
	$phpWord->addTableStyle('listTable',$tableStyle,$colRowStyle);
	$cellStyle = array('valign'=>'bottom');

	$section = $phpWord->addSection(array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>1080,'marginRight'=>1080,'marginTop'=>1080,'marginBottom'=>1080,'headerHeight'=>0,'footerHeight'=>0));

	$textrun = $section->addTextRun('header');
	$textrun->addText(htmlspecialchars('List of specimens loaned to: '.$targetCode),'headerFont');
	$section->addTextBreak(1);
	$textrun = $section->addTextRun('info');
	$textrun->addText(htmlspecialchars($sourceCode.' Loan ID: '.$invoiceArr['loanidentifierown']),'infoFont');
	$textrun->addTextBreak(1);
	$textrun->addText(htmlspecialchars('Date sent: '.$invoiceArr['datesent']),'infoFont');
	$textrun->addTextBreak(1);
	$textrun->addText(htmlspecialchars('Total specimens: '.$loanManager->getSpecimenTotal($loanId)),'infoFont');
	$section->addTextBreak(1);
	$table = $section->addTable('headerTable');
	$table->addRow();
	$table->addCell(2250,$cellStyle)->addText(htmlspecialchars('Catalog #'),'colHeaderFont','colHeadSpace');
	$table->addCell(4500,$cellStyle)->addText(htmlspecialchars('Collector + Number'),'colHeaderFont','colHeadSpace');
	$table->addCell(6000,$cellStyle)->addText(htmlspecialchars('Current Determination'),'colHeaderFont','colHeadSpace');
	$table = $section->addTable('listTable');
	foreach($specList as $specArr){
		$table->addRow();
		$table->addCell(2250,$cellStyle)->addText(htmlspecialchars(trim($specArr['catalognumber'].(isset($specArr['othercatalognumbers'])?', '.implode(', ',$specArr['othercatalognumbers']):''),' ,')),'colFont','colSpace');
		$table->addCell(4500,$cellStyle)->addText(htmlspecialchars($specArr['collector']),'colFont','colSpace');
		$table->addCell(6000,$cellStyle)->addText(htmlspecialchars($specArr['sciname']),'colFont','colSpace');
	}

	$targetFile = $SERVER_ROOT.'/temp/report/'.$loanId.'_specimen_list.docx';
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
			<title><?php echo $sourceCode.' '.$invoiceArr['loanidentifierown']; ?> <?php echo $LANG['SPECIMEN_LIST']; ?></title>
			<?php

			include_once($SERVER_ROOT.'/includes/head.php');
			?>
			<style type="text/css">
				p.printbreak {page-break-after:always;}
				.header {width:100%;text-align:left;font:14pt arial,sans-serif;}
				.loaninfo {width:100%;text-align:left;font:11pt arial,sans-serif;}
				.colheader {text-align:left;font:bold 8pt arial,sans-serif;border-bottom:1px solid black;vertical-align:text-bottom;}
				.specimen {text-align:left;font:8pt arial,sans-serif;}
			</style>
		</head>
		<body style="background-color:#ffffff;">
			<h1 class="page-heading screen-reader-only"><?php echo $sourceCode.' '.$invoiceArr['loanidentifierown']; ?> <?php echo $LANG['SPECIMEN_LIST']; ?></h1>
			<div>
				<div class="header">
					List of specimens loaned to: <?php echo $targetCode; ?>
				</div>
				<br />
				<div class="loaninfo">
					<?php echo $sourceCode; ?> Loan ID: <?php echo $invoiceArr['loanidentifierown']; ?><br />
					Date sent: <?php echo $invoiceArr['datesent']; ?><br />
					Total specimens: <?php echo $loanManager->getSpecimenTotal($loanId);?>
				</div>
				<br />
				<table class="colheader">
					<tr>
						<td style="width:150px;">
							<?php echo $sourceCode; ?><br />
							Catalog &#35;
						</td>
						<td style="width:300px;">
							Collector + Number
						</td>
						<td style="width:400px;">
							Current Determination
						</td>
						<td>  </td>
					</tr>
				</table>
				<table class="specimen">
					<?php
					foreach($specList as $specArr){
						echo '<tr>';
						echo '<td style="width:150px;">'.$specArr['catalognumber'].(isset($specArr['othercatalognumbers'])?'<br/>'.implode(', ',$specArr['othercatalognumbers']):'').'</td>';
						echo '<td style="width:300px;">'.$specArr['collector'].'</td>';
						echo '<td style="width:400px;">'.$specArr['sciname'].'</td>';
						echo '<td> </td>';
						echo '</tr>';
					}
					?>
				</table>
			</div>
		</body>
	</html>
	<?php
}
?>