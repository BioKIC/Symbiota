<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLoans.php');
require_once $SERVER_ROOT.'/vendor/phpoffice/phpword/bootstrap.php';

$collId = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$identifier = array_key_exists('identifier',$_REQUEST) ? filter_var($_REQUEST['identifier'], FILTER_SANITIZE_NUMBER_INT) : 0;
$loanType = array_key_exists('loantype',$_REQUEST) ? filter_var($_REQUEST['loantype'], FILTER_SANITIZE_NUMBER_INT) : 0;
$outputMode = $_POST['outputmode'];
$languageDef = $_POST['languagedef'];

$loanManager = new OccurrenceLoans();
if($collId) $loanManager->setCollId($collId);

$english = ($languageDef == 0 || $languageDef == 1);
$engspan = ($languageDef == 1);
$spanish = ($languageDef == 1 || $languageDef == 2);

$invoiceArr = $loanManager->getInvoiceInfo($identifier,$loanType);
$addressArr = $loanManager->getFromAddress($collId);

if($loanType == 'exchange'){
	$transType = 0;
	if(($invoiceArr['totalexunmounted'] || $invoiceArr['totalexmounted']) && (!$invoiceArr['totalgift'] && !$invoiceArr['totalgiftdet'])){
		$transType = 'ex';
	}
	elseif(($invoiceArr['totalexunmounted'] || $invoiceArr['totalexmounted']) && ($invoiceArr['totalgift'] || $invoiceArr['totalgiftdet'])){
		$transType = 'both';
	}
	elseif((!$invoiceArr['totalexunmounted'] || !$invoiceArr['totalexmounted']) && ($invoiceArr['totalgift'] || $invoiceArr['totalgiftdet'])){
		$transType = 'gift';
	}
}

$numSpecimens = 0;
if($loanType == 'exchange') $numSpecimens = $loanManager->getExchangeTotal($identifier);
else{
	$specList = $loanManager->getSpecimenList($identifier);
	if($specList){
		if(count($specList) == 1) $numSpecimens = 1;
		else $numSpecimens = count($specList);
	}
	else{
		if($invoiceArr['numspecimens'] == 1){$numSpecimens = 1;}
		else{$numSpecimens = $invoiceArr['numspecimens'];}
	}
}

$numBoxes = 0;
if($loanType == 'exchange'){$numBoxes = $invoiceArr['totalboxes'];}
else{
	if($loanType == 'out'){
		if($invoiceArr['totalboxes'] == 1){$numBoxes = 1;}
		else{$numBoxes = $invoiceArr['totalboxes'];}
	}
	else{
		if($invoiceArr['totalboxesreturned'] == 1){$numBoxes = 1;}
		else{$numBoxes = $invoiceArr['totalboxesreturned'];}
	}
}

if($outputMode == 'doc'){
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	$phpWord->addParagraphStyle('header', array('align'=>'center','lineHeight'=>1.0,'spaceAfter'=>450));
	$phpWord->addFontStyle('headerFont', array('size'=>12,'bold'=>true,'name'=>'Arial'));
	$phpWord->addParagraphStyle('toAddress', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0));
	$phpWord->addFontStyle('toAddressFont', array('size'=>10,'name'=>'Arial'));
	$phpWord->addParagraphStyle('identifier', array('align'=>'right','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0));
	$phpWord->addFontStyle('identifierFont', array('size'=>10,'bold'=>true,'name'=>'Arial'));
	$phpWord->addParagraphStyle('sendwhom', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0));
	$phpWord->addFontStyle('sendwhomFont', array('size'=>10,'name'=>'Arial'));
	$phpWord->addParagraphStyle('returnamtdue', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0));
	$phpWord->addFontStyle('returnamtdueFont', array('size'=>10,'bold'=>true,'name'=>'Arial'));
	$phpWord->addParagraphStyle('other', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0));
	$phpWord->addFontStyle('otherFont', array('size'=>10,'name'=>'Arial'));
	$tableStyle = array('width'=>100);
	$colRowStyle = array('cantSplit'=>true);
	$phpWord->addTableStyle('headTable',$tableStyle,$colRowStyle);
	$cellStyle = array('valign'=>'top');

	$section = $phpWord->addSection(array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>1080,'marginRight'=>1080,'marginTop'=>1080,'marginBottom'=>0,'headerHeight'=>0,'footerHeight'=>600));

	$textrun = $section->addTextRun('header');
	$textrun->addText(htmlspecialchars($addressArr['institutionname'].' ('.$addressArr['institutioncode'].')'),'headerFont');
	$textrun->addTextBreak(1);
	if($addressArr['institutionname2']){
		$textrun->addText(htmlspecialchars($addressArr['institutionname2']),'headerFont');
		$textrun->addTextBreak(1);
	}
	if($addressArr['address1']){
		$textrun->addText(htmlspecialchars($addressArr['address1']),'headerFont');
		$textrun->addTextBreak(1);
	}
	if($addressArr['address2']){
		$textrun->addText(htmlspecialchars($addressArr['address2']),'headerFont');
		$textrun->addTextBreak(1);
	}
	$textrun->addText(htmlspecialchars($addressArr['city'].($addressArr['stateprovince']?', ':'').$addressArr['stateprovince'].' '.$addressArr['postalcode'].' '.$addressArr['country']),'headerFont');
	$textrun->addTextBreak(1);
	$textrun->addText(htmlspecialchars($addressArr['phone']),'headerFont');
	$textrun->addTextBreak(2);
	$textrun->addText(htmlspecialchars(($english?'SHIPPING INVOICE':'').($engspan?' / ':'').($spanish?'FACTURA DE REMESA':'')),'headerFont');
	$section->addTextBreak(1);
	$table = $section->addTable('headTable');
	$table->addRow();
	$cell = $table->addCell(5000,$cellStyle);
	$textrun = $cell->addTextRun('toAddress');
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
	$cell = $table->addCell(5000,$cellStyle);
	$textrun = $cell->addTextRun('identifier');
	$textrun->addText(htmlspecialchars(date('l').', '.date('F').' '.date('j').', '.date('Y')),'identifierFont');
	$textrun->addTextBreak(1);
	if($loanType == 'out'){
		$textrun->addText(htmlspecialchars($addressArr['institutioncode'].' Loan ID: '.$invoiceArr['loanidentifierown']),'identifierFont');
	}
	elseif($loanType == 'in'){
		$textrun->addText(htmlspecialchars($addressArr['institutioncode'].' Loan-in ID: '.$invoiceArr['loanidentifierborr']),'identifierFont');
	}
	elseif($loanType == 'exchange'){
		$textrun->addText(htmlspecialchars($addressArr['institutioncode'].' Transaction ID: '.$invoiceArr['identifier']),'identifierFont');
	}
	$section->addTextBreak(1);
	$textrun = $section->addTextRun('sendwhom');
	if($english){
		$textrun->addText(htmlspecialchars('We are sending you '.($numBoxes == 1?'1 box ':$numBoxes.' boxes ')),'sendwhomFont');
		$textrun->addText(htmlspecialchars('containing '.($numSpecimens == 1?'1 specimen. ':$numSpecimens.' specimens. ')),'sendwhomFont');
		if(($loanType == 'in' && $invoiceArr['shippingmethodreturn']) || $invoiceArr['shippingmethod']){
			$textrun->addText(htmlspecialchars(($numBoxes == 1?'This package was ':'These packages were ').'delivered via '.($loanType == 'in'?$invoiceArr['shippingmethodreturn']:$invoiceArr['shippingmethod']).'. '),'sendwhomFont');
		}
		$textrun->addText(htmlspecialchars('Upon arrival of the shipment, kindly verify its contents and acknowledge '),'sendwhomFont');
		$textrun->addText(htmlspecialchars('receipt by signing and returning the duplicate invoice to us.'),'sendwhomFont');
	}
	if($engspan){
		$textrun->addTextBreak(2);
	}
	if($spanish){
		$textrun->addText(htmlspecialchars('Estámos remitiendo a Uds. '.($numBoxes == 1?'1 caja ':$numBoxes.' cajas ')),'sendwhomFont');
		$textrun->addText(htmlspecialchars('de '.($numSpecimens == 1?'1 ejemplar. ':$numSpecimens.' ejemplares. ')),'sendwhomFont');
		if(($loanType == 'in' && $invoiceArr['shippingmethodreturn']) || $invoiceArr['shippingmethod']){
			$textrun->addText(htmlspecialchars(($numBoxes == 1?'Esta remesa hubiera enviado ':'Estas remesas hubieran enviado ').'por '.($loanType == 'in'?$invoiceArr['shippingmethodreturn']:$invoiceArr['shippingmethod']).'. '),'sendwhomFont');
		}
		$textrun->addText(htmlspecialchars('Al llegar la remesa, por favor verifique los contenidos y sírvase acusar '),'sendwhomFont');
		$textrun->addText(htmlspecialchars('recibo de esta remesa firmiendo una de las copias y devolviéndo la por correo.'),'sendwhomFont');
	}
	if($loanType == 'out'){
		$textrun->addTextBreak(2);
		if($english){
			$textrun->addText(htmlspecialchars('This shipment is a LOAN for study by '.$invoiceArr['forwhom']),'sendwhomFont');
		}
		if($engspan){
			$textrun->addTextBreak(2);
		}
		if($spanish){
			$textrun->addText(htmlspecialchars('Esta remesa es un PRESTAMO para el estudio de '.$invoiceArr['forwhom']),'sendwhomFont');
		}
		$textrun = $section->addTextRun('returnamtdue');
		$textrun->addTextBreak(1);
		if($english){
			$textrun->addText(htmlspecialchars('Loans are made for a period of 2 years. This loan will be due '.$invoiceArr['datedue'].'.'),'returnamtdueFont');
		}
		if($engspan){
			$textrun->addTextBreak(2);
		}
		if($spanish){
			$textrun->addText(htmlspecialchars('Los préstamos se extienden por un periodo de 2 años. Este préstamo tiene una fecha límite de '.$invoiceArr['datedue'].'.'),'returnamtdueFont');
		}
		$textrun->addTextBreak(2);
		if($english){
			$textrun->addText(htmlspecialchars('When circumstances warrant, the loan period may be extended. Specimens should be returned by '),'otherFont');
			$textrun->addText(htmlspecialchars('insured parcel post or by prepaid express. All material of this loan should be returned at the same time. Notes or '),'otherFont');
			$textrun->addText(htmlspecialchars('changes should be written on annotation labels. Reprints dealing with taxonomic groups will be appreciated.'),'otherFont');
		}
		if($engspan){
			$textrun->addTextBreak(2);
		}
		if($spanish){
			$textrun->addText(htmlspecialchars('Siempre y cuando las circunstancias se permiten, se puede pedir un prórroga de la fecha límite de este '),'otherFont');
			$textrun->addText(htmlspecialchars('préstamo. Todo material del préstamo debe devolverse en el mismo envío. Notas y cambios de identificación se '),'otherFont');
			$textrun->addText(htmlspecialchars('deben indicar con notas de anotación. Además, le pedimos mandar separatas de cualquier publicación '),'otherFont');
			$textrun->addText(htmlspecialchars('proveniente del uso de este material.'),'otherFont');
		}
	}
	elseif($loanType == 'in'){
		$section->addTextBreak(1);
		$textrun = $section->addTextRun('returnamtdue');
		if($english){
			$textrun->addText(htmlspecialchars('This shipment is a return of '.$invoiceArr['institutioncode'].' '),'returnamtdueFont');
			$textrun->addText(htmlspecialchars('loan '.$invoiceArr['loanidentifierown'].', received '.$invoiceArr['datereceivedborr']),'returnamtdueFont');
		}
		if($engspan){
			$textrun->addTextBreak(2);
		}
		if($spanish){
			$textrun->addText(htmlspecialchars('En esta remesa se devuelve el prestamo '.$invoiceArr['loanidentifierown'].' '),'returnamtdueFont');
			$textrun->addText(htmlspecialchars('de '.$invoiceArr['institutioncode'].', recibido '.$invoiceArr['datereceivedborr']),'returnamtdueFont');
		}
	}
	elseif($loanType == 'exchange'){
		if($transType == 'ex' || $transType == 'both'){
			$exchangeValue = $loanManager->getExchangeValue($identifier);
			$section->addTextBreak(1);
			$textrun = $section->addTextRun('returnamtdue');
			if($english){
				$textrun->addText(htmlspecialchars('This shipment is an EXCHANGE, consisting of '.($invoiceArr['totalexunmounted']?$invoiceArr['totalexunmounted'].' unmounted ':'')),'returnamtdueFont');
				$textrun->addText(htmlspecialchars((($invoiceArr['totalexunmounted'] && $invoiceArr['totalexmounted'])?'and ':'').($invoiceArr['totalexmounted']?$invoiceArr['totalexmounted'].' mounted ':'')),'returnamtdueFont');
				$textrun->addText(htmlspecialchars('specimens, for an exchange value of '.$exchangeValue.'. Please note that mounted specimens count as two.'),'returnamtdueFont');
			}
			if($engspan){
				$textrun->addTextBreak(2);
			}
			if($spanish){
				$textrun->addText(htmlspecialchars('Este envío es un INTERCAMBIO, consistiendo en '.($invoiceArr['totalexunmounted']?$invoiceArr['totalexunmounted'].' ejemplares no montados ':'')),'returnamtdueFont');
				$textrun->addText(htmlspecialchars((($invoiceArr['totalexunmounted'] && $invoiceArr['totalexmounted'])?'y ':'').($invoiceArr['totalexmounted']?$invoiceArr['totalexmounted'].' ejemplares montados ':'')),'returnamtdueFont');
				$textrun->addText(htmlspecialchars('con un valor de intercambio de '.$exchangeValue.'. Favor de notarse que las ejemplares montados son de valor 2.'),'returnamtdueFont');
			}
			if($transType == 'both'){
				$textrun->addTextBreak(2);
				if($english){
					$textrun->addText(htmlspecialchars('This shipment also contains '),'returnamtdueFont');
					if($invoiceArr['totalgift']){
						$textrun->addText(htmlspecialchars(($invoiceArr['totalgift'] == 1?'1 gift specimen':$invoiceArr['totalgift'].' gift')),'returnamtdueFont');
					}
					if($invoiceArr['totalgift'] == 1 && !$invoiceArr['totalgiftdet']){
						$textrun->addText(htmlspecialchars('.'),'returnamtdueFont');
					}
					if($invoiceArr['totalgift'] && $invoiceArr['totalgiftdet']){
						$textrun->addText(htmlspecialchars(' and '),'returnamtdueFont');
					}
					if($invoiceArr['totalgiftdet']){
						$textrun->addText(htmlspecialchars(($invoiceArr['totalgiftdet'] == 1?'1 gift-for-det specimen.':$invoiceArr['totalgiftdet'].' gift-for-det')),'returnamtdueFont');
					}
					if($invoiceArr['totalgift'] > 1 || $invoiceArr['totalgiftdet'] > 1){
						$textrun->addText(htmlspecialchars(' specimens.'),'returnamtdueFont');
					}
				}
				if($engspan){
					$textrun->addTextBreak(2);
				}
				if($spanish){
					$textrun->addText(htmlspecialchars('Esta remesa también contiene '),'returnamtdueFont');
					if($invoiceArr['totalgift']){
						$textrun->addText(htmlspecialchars(($invoiceArr['totalgift'] == 1?'1 ejemplar de regalo':$invoiceArr['totalgift'].' ejemplares de regalo')),'returnamtdueFont');
					}
					if($invoiceArr['totalgift'] && $invoiceArr['totalgiftdet']){
						$textrun->addText(htmlspecialchars(' y '),'returnamtdueFont');
					}
					if($invoiceArr['totalgiftdet']){
						$textrun->addText(htmlspecialchars(($invoiceArr['totalgiftdet'] == 1?'1 ejemplar de regalo para identificación':$invoiceArr['totalgiftdet'].' ejemplares de regalo para identificación')),'returnamtdueFont');
					}
					$textrun->addText(htmlspecialchars('.'),'returnamtdueFont');
				}
			}
			$textrun->addTextBreak(2);
			if($english){
				$textrun->addText(htmlspecialchars('Our records show a balance of '.abs($invoiceArr['invoicebalance']).' specimens '),'otherFont');
				$textrun->addText(htmlspecialchars('in '.($invoiceArr['invoicebalance']>0?'our':'your').' favor. Please contact us if your records differ significantly.'),'otherFont');
			}
			if($engspan){
				$textrun->addTextBreak(2);
			}
			if($spanish){
				$textrun->addText(htmlspecialchars('Nuestros registros muestran un balance de '.abs($invoiceArr['invoicebalance']).' ejemplares '),'otherFont');
				$textrun->addText(htmlspecialchars('a '.($invoiceArr['invoicebalance']>0?'nuestro':'su').' favor. Favor de contactarnos si sus '),'otherFont');
				$textrun->addText(htmlspecialchars('registros se dífieren de una manera apreciable.'),'otherFont');
			}
		}
		elseif($transType == 'gift'){
			$section->addTextBreak(1);
			$textrun = $section->addTextRun('returnamtdue');
			if($english){
				$textrun->addText(htmlspecialchars('This shipment is a '),'returnamtdueFont');
				if($invoiceArr['totalgift'] && !$invoiceArr['totalgiftdet']){
					$textrun->addText(htmlspecialchars('GIFT.'),'returnamtdueFont');
				}
				if($invoiceArr['totalgift'] && $invoiceArr['totalgiftdet']){
					$textrun->addText(htmlspecialchars('GIFT and GIFT-FOR-DET.'),'returnamtdueFont');
				}
				if(!$invoiceArr['totalgift'] && $invoiceArr['totalgiftdet']){
					$textrun->addText(htmlspecialchars('GIFT-FOR-DET.'),'returnamtdueFont');
				}
			}
			if($engspan){
				$textrun->addTextBreak(2);
			}
			if($spanish){
				$textrun->addText(htmlspecialchars('Este envío es un '),'returnamtdueFont');
				if($invoiceArr['totalgift'] && !$invoiceArr['totalgiftdet']){
					$textrun->addText(htmlspecialchars('REGALO.'),'returnamtdueFont');
				}
				if($invoiceArr['totalgift'] && $invoiceArr['totalgiftdet']){
					$textrun->addText(htmlspecialchars('REGALO y un REGALO PARA IDENTIFICACIÓN.'),'returnamtdueFont');
				}
				if(!$invoiceArr['totalgift'] && $invoiceArr['totalgiftdet']){
					$textrun->addText(htmlspecialchars('REGALO PARA IDENTIFICACIÓN.'),'returnamtdueFont');
				}
			}
		}
	}
	$section->addTextBreak(1);
	$textrun = $section->addTextRun('returnamtdue');
	$textrun->addText(htmlspecialchars(($english?'DESCRIPTION OF THE SPECIMENS':'').($engspan?' / ':'').($spanish?'DESCRIPCIÓN DE LOS EJEMPLARES':'').':'),'returnamtdueFont');
	$textrun->addTextBreak(2);
	$textrun->addText(htmlspecialchars(($invoiceArr['description']?$invoiceArr['description']:'')),'otherFont');
	$textrun->addTextBreak(2);
	if(array_key_exists('invoicemessage',$invoiceArr) || array_key_exists('invoicemessageown',$invoiceArr) || array_key_exists('invoicemessageborr',$invoiceArr)){
		if($loanType == 'exchange'){
			$textrun->addText(htmlspecialchars(($invoiceArr['invoicemessage']?$invoiceArr['invoicemessage']:'')),'otherFont');
		}
		elseif($loanType == 'out'){
			$textrun->addText(htmlspecialchars(($invoiceArr['invoicemessageown']?$invoiceArr['invoicemessageown']:'')),'otherFont');
		}
		elseif($loanType == 'in'){
			$textrun->addText(htmlspecialchars(($invoiceArr['invoicemessageborr']?$invoiceArr['invoicemessageborr']:'')),'otherFont');
		}
		$textrun->addTextBreak(2);
	}
	$textrun->addText(htmlspecialchars(($english?'Sincerely':'').($engspan?' / ':'').($spanish?'Sinceramente':'').','),'otherFont');
	$footer = $section->addFooter();
	$textrun = $footer->addTextRun('other');
	$textrun->addLine(array('weight'=>1,'width'=>670,'height'=>0,'dash'=>'dash'));
	$textrun->addTextBreak(1);
	if($english){
		$textrun->addText(htmlspecialchars('PLEASE SIGN AND RETURN ONE COPY UPON RECEIPT OF THIS SHIPMENT.'),'otherFont');
	}
	if($engspan){
		$textrun->addTextBreak(2);
	}
	if($spanish){
		$textrun->addText(htmlspecialchars('POR FAVOR FIRME Y DEVUELVE UNA COPIA AL LLEGAR ESTA REMESA.'),'otherFont');
	}
	$textrun->addTextBreak(2);
	$textrun->addText(htmlspecialchars(($english?'The above specimens were received in good condition':'').($engspan?' / ':'').($spanish?'Recibido en buenas condiciones':'').'.'),'otherFont');
	$textrun->addTextBreak(2);
	$textrun->addText(htmlspecialchars(($english?'Signed':'').($engspan?'/':'').($spanish?'Firma':'').':______________________________________  '.($english?'Date':'').($engspan?'/':'').($spanish?'Fecha':'').':______________'),'otherFont');

	$targetFile = $SERVER_ROOT.'/temp/report/'.$identifier.'_invoice.docx';
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
			<title><?php echo $identifier; ?> Invoice</title>
			<?php

			include_once($SERVER_ROOT.'/includes/head.php');
			?>
			<style type="text/css">
				body {font-family:arial,sans-serif;}
				p.printbreak {page-break-after:always;}
				.header {width:100%;text-align:center;font:bold 12pt arial,sans-serif;margin-bottom:30px;}
				.toaddress {float:left;text-align:left;font:10pt arial,sans-serif;margin-top:10px;}
				.identifier {float:right;text-align:right;font:bold 10pt arial,sans-serif;margin-top:10px;}
				.sending {width:100%;text-align:left;font:10pt arial,sans-serif;}
				.forwhom {width:100%;text-align:left;font:10pt arial,sans-serif;}
				.loanreturn {width:100%;text-align:left;font:bold 10pt arial,sans-serif;}
				.exchangeamts {width:100%;text-align:left;font:bold 10pt arial,sans-serif;}
				.duedate {width:100%;text-align:left;font:bold 10pt arial,sans-serif;}
				.exchangebal {width:100%;text-align:left;font:10pt arial,sans-serif;}
				.loanoutinfo {width:100%;text-align:left;font:10pt arial,sans-serif;}
				.description {width:100%;text-align:left;font:10pt arial,sans-serif;}
				.message {width:100%;text-align:left;font:10pt arial,sans-serif;}
				.saludos {width:100%;text-align:left;font:10pt arial,sans-serif;}
				.return {width:100%;text-align:left;font:10pt arial,sans-serif;position:relative;bottom:0;margin-top:20%;}
				/* Hide the edit button div when printing */
				@media print {.controls {display: none;}}
			</style>

			<script language="javascript">

				// Function to toggle editing of the invoice on or off
				function toggleEdits() {
					var invoice = document.getElementById('invoice');
					let isEditable = invoice.contentEditable === 'true';
					if (isEditable) {
						invoice.contentEditable = 'false';
						document.querySelector('#edit').innerText = 'Edit Invoice';
						invoice.style.border = 'none';
					} else {
						invoice.contentEditable = 'true';
						document.querySelector('#edit').innerText = 'Save';
						invoice.style.border = '2px solid #03fc88';
					}
				}
			</script>
		</head>
		<body style="background-color:#ffffff;">
			<h1 class="page-heading screen-reader-only">Invoice</h1>
			<div class="controls">
				<button id="edit" style="font-weight: bold;" onclick="toggleEdits();">Edit Invoice</button>
			</div>
			<table id="invoice" style="height:10in;">
				<tr>
					<td>
						<div>
							<table class="header" align="center">
								<tr>
									<td><?php echo $addressArr['institutionname']; ?> (<?php echo $addressArr['institutioncode']; ?>)</td>
								</tr>
								<?php if($addressArr['institutionname2']){ ?>
									<tr>
										<td><?php echo $addressArr['institutionname2']; ?></td>
									</tr>
								<?php } ?>
								<?php if($addressArr['address1']){ ?>
									<tr>
										<td><?php echo $addressArr['address1']; ?></td>
									</tr>
								<?php } ?>
								<?php if($addressArr['address2']){ ?>
									<tr>
										<td><?php echo $addressArr['address2']; ?></td>
									</tr>
								<?php } ?>
								<tr>
									<td><?php echo $addressArr['city'].($addressArr['stateprovince']?', ':'').$addressArr['stateprovince'].' '.$addressArr['postalcode'].' '.$addressArr['country']; ?></td>
								</tr>
								<tr>
									<td><?php echo $addressArr['phone']; ?></td>
								</tr>
								<tr style="height:10px;">
									<td></td>
								</tr>
								<tr>
									<?php
									echo '<td>'.($english?'SHIPPING INVOICE':'').($engspan?' / ':'').($spanish?'FACTURA DE REMESA':'').'</td>' ;
									?>
								</tr>
							</table>
							<br />
							<br />
							<table style="width:100%;">
								<tr>
									<td>
										<div class="toaddress">
											<?php
											echo $invoiceArr['contact'].'<br />';
											echo $invoiceArr['institutionname'].' ('.$invoiceArr['institutioncode'].')<br />';
											if($invoiceArr['institutionname2']) echo $invoiceArr['institutionname2'].'<br />';
											if($invoiceArr['address1']) echo $invoiceArr['address1'].'<br />';
											if($invoiceArr['address2']) echo $invoiceArr['address2'].'<br />';
											echo $invoiceArr['city'].($invoiceArr['stateprovince']?', ':'').$invoiceArr['stateprovince'].' '.$invoiceArr['postalcode'];
											echo '<br />'.$invoiceArr['country'];
											?>
										</div>
									</td>
									<td class="identifier">
										<div class="identifier">
											<?php
											echo date('l').', '.date('F').' '.date('j').', '.date('Y').'<br />';
											if($loanType == 'out'){
												echo $addressArr['institutioncode'].' Loan ID: '.$invoiceArr['loanidentifierown'];
											}
											elseif($loanType == 'in'){
												echo $addressArr['institutioncode'].' Loan-in ID: '.$invoiceArr['loanidentifierborr'];
											}
											elseif($loanType == 'exchange'){
												echo $addressArr['institutioncode'].' Transaction ID: '.$invoiceArr['identifier'];
											}
											?>
										</div>
									</td>
								</tr>
							</table>
							<br />
							<div class="sending">
								<?php
								if($english){
									echo '<div>We are sending you '.($numBoxes == 1?'1 box ':$numBoxes.' boxes ');
									echo 'containing '.($numSpecimens == 1?'1 specimen. ':$numSpecimens.' specimens. ');
									if(($loanType == 'in' && $invoiceArr['shippingmethodreturn']) || $invoiceArr['shippingmethod']){
										echo ($numBoxes == 1?'This package was ':'These packages were ').'delivered via '.($loanType == 'in'?$invoiceArr['shippingmethodreturn']:$invoiceArr['shippingmethod']).'. ';
									}
									echo 'Upon arrival of the shipment, kindly verify its contents and acknowledge ';
									echo 'receipt by signing and returning the duplicate invoice to us.</div><br />';
								}
								if($spanish){
									echo '<div>Est&aacute;mos remitiendo a Uds. '.($numBoxes == 1?'1 caja ':$numBoxes.' cajas ');
									echo 'de '.($numSpecimens == 1?'1 ejemplar. ':$numSpecimens.' ejemplares. ');
									if(($loanType == 'in' && $invoiceArr['shippingmethodreturn']) || $invoiceArr['shippingmethod']){
										echo ($numBoxes == 1?'Esta remesa hubiera enviado ':'Estas remesas hubieran enviado ').'por '.($loanType == 'in'?$invoiceArr['shippingmethodreturn']:$invoiceArr['shippingmethod']).'. ';
									}
									echo 'Al llegar la remesa, por favor verifique los contenidos y s&iacute;rvase acusar ';
									echo 'recibo de esta remesa firmiendo una de las copias y devolvi&eacute;ndo la por correo.</div><br />';
								}
								?>
							</div>
							<?php
							if($loanType == 'out'){ ?>
								<?php if($english){ ?>
									<div class="forwhom">This shipment is a LOAN for study by <?php echo $invoiceArr['forwhom']; ?>.</div><br />
								<?php }
								if($spanish){ ?>
									<div class="forwhom">Esta remesa es un PRESTAMO para el estudio de <?php echo $invoiceArr['forwhom']; ?>.</div><br />
								<?php }
								if($english){ ?>
									<div class="duedate">Loans are made for a period of 2 years. This loan will be due <?php echo $invoiceArr['datedue']; ?>.</div><br />
								<?php }
								if($spanish){ ?>
									<div class="duedate">Los pr&eacute;stamos se extienden por un periodo de 2 a&ntilde;os. Este pr&eacute;stamo tiene una fecha l&iacute;mite de <?php echo $invoiceArr['datedue']; ?>.</div><br />
								<?php }
								if($english){ ?>
									<div class="loanoutinfo">When circumstances warrant, the loan period may be extended. Specimens should be returned by
										insured parcel post or by prepaid express. All material of this loan should be returned at the same time. Notes or
										changes should be written on annotation labels. Reprints dealing with taxonomic groups will be appreciated.
									</div><br />
								<?php }
								if($spanish){ ?>
									<div class="loanoutinfo">Siempre y cuando las circunstancias se permiten, se puede pedir un pr&oacute;rroga de la fecha l&iacute;mite de este
										pr&eacute;stamo. Todo material del pr&eacute;stamo debe devolverse en el mismo env&iacute;o. Notas y cambios de identificaci&oacute;n se
										deben indicar con notas de anotaci&oacute;n. Adem&aacute;s, le pedimos mandar separatas de cualquier publicaci&oacute;n
										proveniente del uso de este material.
									</div><br />
								<?php }
							}
							elseif($loanType == 'in'){ ?>
								<?php if($english){ ?>
									<div class="loanreturn">This shipment is a return of <?php echo $invoiceArr['institutioncode']; ?>
										loan <?php echo $invoiceArr['loanidentifierown']; ?>, received <?php echo $invoiceArr['datereceivedborr']; ?>.</div><br />
								<?php }
								if($spanish){ ?>
									<div class="loanreturn">En esta remesa se devuelve el prestamo <?php echo $invoiceArr['loanidentifierown']; ?>
										de <?php echo $invoiceArr['institutioncode']; ?>, recibido <?php echo $invoiceArr['datereceivedborr']; ?>.</div><br />
								<?php } ?>
							<?php }
							elseif($loanType == 'exchange'){
								if($transType == 'ex' || $transType == 'both'){
									$exchangeValue = $loanManager->getExchangeValue($identifier);
									if($english){ ?>
										<div class="exchangeamts">This shipment is an EXCHANGE, consisting of <?php echo ($invoiceArr['totalexunmounted']?$invoiceArr['totalexunmounted'].' unmounted ':''); ?>
											<?php echo (($invoiceArr['totalexunmounted'] && $invoiceArr['totalexmounted'])?'and ':''); ?><?php echo ($invoiceArr['totalexmounted']?$invoiceArr['totalexmounted'].' mounted ':''); ?>
											specimens, for an exchange value of <?php echo $exchangeValue; ?>. Please note that mounted specimens count as two.
										</div><br />
									<?php }
									if($spanish){ ?>
										<div class="exchangeamts">Este env&iacute;o es un INTERCAMBIO, consistiendo en <?php echo ($invoiceArr['totalexunmounted']?$invoiceArr['totalexunmounted'].' ejemplares no montados ':''); ?>
											<?php echo (($invoiceArr['totalexunmounted'] && $invoiceArr['totalexmounted'])?'y ':''); ?><?php echo ($invoiceArr['totalexmounted']?$invoiceArr['totalexmounted'].' ejemplares montados ':''); ?>,
											con un valor de intercambio de <?php echo $exchangeValue; ?>. Favor de notarse que las ejemplares montados son de valor 2.
										</div><br />
									<?php }
									if($transType == 'both'){
										if($english){ ?>
											<div class="exchangeamts">
												<?php
													echo 'This shipment also contains ';
													if($invoiceArr['totalgift']){
														echo ($invoiceArr['totalgift'] == 1?'1 gift specimen':$invoiceArr['totalgift'].' gift');
													}
													if($invoiceArr['totalgift'] == 1 && !$invoiceArr['totalgiftdet']){
														echo '.';
													}
													if($invoiceArr['totalgift'] && $invoiceArr['totalgiftdet']){
														echo ' and ';
													}
													if($invoiceArr['totalgiftdet']){
														echo ($invoiceArr['totalgiftdet'] == 1?'1 gift-for-det specimen.':$invoiceArr['totalgiftdet'].' gift-for-det');
													}
													if($invoiceArr['totalgift'] > 1 || $invoiceArr['totalgiftdet'] > 1){
														echo ' specimens.';
													}
												?>
											</div><br />
										<?php }
										if($spanish){ ?>
											<div class="exchangeamts">
												<?php
													echo 'Esta remesa tambi&eacute;n contiene ';
													if($invoiceArr['totalgift']){
														echo ($invoiceArr['totalgift'] == 1?'1 ejemplar de regalo':$invoiceArr['totalgift'].' ejemplares de regalo');
													}
													if($invoiceArr['totalgift'] && $invoiceArr['totalgiftdet']){
														echo ' y ';
													}
													if($invoiceArr['totalgiftdet']){
														echo ($invoiceArr['totalgiftdet'] == 1?'1 ejemplar de regalo para identificaci&oacute;n':$invoiceArr['totalgiftdet'].' ejemplares de regalo para identificaci&oacute;n');
													}
													echo '.';
												?>
											</div><br />
										<?php }
									}
									if($english){ ?>
										<div class="exchangebal">Our records show a balance of <?php echo abs($invoiceArr['invoicebalance']); ?> specimens
											in <?php echo ($invoiceArr['invoicebalance']>0?'our':'your'); ?> favor. Please contact us if your records differ significantly.
										</div><br />
									<?php }
									if($spanish){ ?>
										<div class="exchangebal">Nuestros registros muestran un balance de <?php echo abs($invoiceArr['invoicebalance']); ?> ejemplares
											a <?php echo ($invoiceArr['invoicebalance']>0?'nuestro':'su'); ?> favor. Favor de contactarnos si sus
											registros se d&iacute;fieren de una manera apreciable.
										</div><br />
									<?php }
								}
								elseif($transType == 'gift'){
									if($english){ ?>
										<div class="exchangeamts">
											<?php
												echo 'This shipment is a ';
												if($invoiceArr['totalgift'] && !$invoiceArr['totalgiftdet']){
													echo 'GIFT.';
												}
												if($invoiceArr['totalgift'] && $invoiceArr['totalgiftdet']){
													echo 'GIFT and GIFT-FOR-DET.';
												}
												if(!$invoiceArr['totalgift'] && $invoiceArr['totalgiftdet']){
													echo 'GIFT-FOR-DET.';
												}
											?>
										</div><br />
									<?php }
									if($spanish){ ?>
										<div class="exchangeamts">
											<?php
												echo 'Este env&iacute;o es un ';
												if($invoiceArr['totalgift'] && !$invoiceArr['totalgiftdet']){
													echo 'REGALO.';
												}
												if($invoiceArr['totalgift'] && $invoiceArr['totalgiftdet']){
													echo 'REGALO y un REGALO PARA IDENTIFICACI&Oacute;N.';
												}
												if(!$invoiceArr['totalgift'] && $invoiceArr['totalgiftdet']){
													echo 'REGALO PARA IDENTIFICACI&Oacute;N.';
												}
											?>
										</div><br />
									<?php }
								}
							}
							?>
							<div class="description">
								<?php
									echo '<b>'.($english?'DESCRIPTION OF THE SPECIMENS':'').($engspan?' / ':'').($spanish?'DESCRIPCI&Oacute;N DE LOS EJEMPLARES':'').':</b><br /><br />' ;
									echo ($invoiceArr['description']?$invoiceArr['description']:'');
								?>

							</div>
							<br />
							<?php
							if(array_key_exists('invoicemessage',$invoiceArr) || array_key_exists('invoicemessageown',$invoiceArr) || array_key_exists('invoicemessageborr',$invoiceArr)){
								echo '<div class="message">';
								if($loanType == 'exchange'){
									echo ($invoiceArr['invoicemessage']?$invoiceArr['invoicemessage']:'');
								}
								elseif($loanType == 'out'){
									echo ($invoiceArr['invoicemessageown']?$invoiceArr['invoicemessageown']:'');
								}
								elseif($loanType == 'in'){
									echo ($invoiceArr['invoicemessageborr']?$invoiceArr['invoicemessageborr']:'');
								}
								echo '</div><br /><br />';
							} ?>
							<div class="saludos">
								<?php
									echo ($english?'Sincerely':'').($engspan?' / ':'').($spanish?'Sinceramente':'').',<br />' ;
								?>
							</div>
							<br />
							<br />
							<br />
							<br />
							<br />
							<br />
							<br />
							<br />
							<div class="return">
								<hr />
								<br />
								<?php
								if($english){
									echo 'PLEASE SIGN AND RETURN ONE COPY UPON RECEIPT OF THIS SHIPMENT<br />' ;
								}
								if($spanish){
									echo 'POR FAVOR FIRME Y DEVUELVE UNA COPIA AL LLEGAR ESTA REMESA.<br />' ;
								} ?>
								<br />
								<?php
									echo ($english?'The above specimens were received in good condition':'').($engspan?' / ':'').($spanish?'Recibido en buenas condiciones':'').'.<br />' ;
								?>
								<br />
								<?php
									echo ($english?'Signed':'').($engspan?'/':'').($spanish?'Firma':'').':______________________________________  '.($english?'Date':'').($engspan?'/':'').($spanish?'Fecha':'').':______________<br />' ;
								?>
							</div>
						</div>
					</td>
				</tr>
			</table>
		</body>
	</html>
	<?php
}
?>