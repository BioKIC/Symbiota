<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLoans.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../collections/loans/exchange.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$exchangeId = array_key_exists('exchangeid',$_REQUEST)?$_REQUEST['exchangeid']:0;
$identifier = array_key_exists('identifier',$_REQUEST)?$_REQUEST['identifier']:0;
$formSubmit = array_key_exists('formsubmit',$_REQUEST)?$_REQUEST['formsubmit']:'';
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;

$isEditor = 0;
if($SYMB_UID && $collid){
	if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))
		|| (array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"]))){
		$isEditor = 1;
	}
}

$loanManager = new OccurrenceLoans();
if($collid) $loanManager->setCollId($collid);

$statusStr = '';
if($isEditor){
	if($formSubmit){
		if($formSubmit == 'createExchange'){
			$exchangeId = htmlspecialchars($loanManager->createNewExchange($_POST), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
			if(!$exchangeId) $statusStr = $loanManager->getErrorMessage();
		}
		elseif($formSubmit == 'Save Exchange'){
			$statusStr = htmlspecialchars($loanManager->editExchange($_POST), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
		}
		elseif ($formSubmit == "delAttachment") {
			// Delete correspondence attachment
			if (array_key_exists('attachid',$_REQUEST) && is_numeric($_REQUEST['attachid'])) $loanManager->deleteAttachment($_REQUEST['attachid']);
			$statusStr = $loanManager->getErrorMessage();
		}
		elseif ($formSubmit == "saveAttachment") {
			// Save correspondence attachment
			if (array_key_exists('uploadfile',$_FILES)) $loanManager->uploadAttachment($collid, 'exch', $exchangeId, $identifier, $_POST['uploadtitle'], $_FILES['uploadfile']);
			$statusStr = $loanManager->getErrorMessage();
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo $DEFAULT_TITLE . ': ' . $LANG['EXCHANGE_MNG']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;
	</script>
	<script type="text/javascript" src="../../js/symb/collections.loans.js?ver=2"></script>
	<style>
		fieldset{ padding:15px; margin:15px }
		fieldset legend{ font-weight:bold }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href='../../index.php'>Home</a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo $LANG['COL_MNG_MENU']; ?></a> &gt;&gt;
		<a href="index.php?tabindex=2&collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo $LANG['LOAN_INDEX']; ?></a> &gt;&gt;
		<a href="exchange.php?exchangeid=<?php echo htmlspecialchars($exchangeId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><b><?php echo $LANG['EXCHANGE_MNG']; ?></b></a>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['EXCHANGE_MNG']; ?></h1>
		<?php
		if($isEditor && $collid){
			if($statusStr){
				$colorStr = 'red';
				if(stripos($statusStr,'SUCCESS') !== false) $colorStr = 'green';
				?>
				<hr/>
				<div style="margin:15px;color:<?php echo $colorStr; ?>;">
					<?php echo $statusStr; ?>
				</div>
				<hr/>
				<?php
			}
			?>
			<div id="tabs" style="margin:0px;">
			    <ul>
					<li><a href="#exchangedetaildiv"><span><?php echo $LANG['EXCHANGE_DETS']; ?></span></a></li>
					<li><a href="#exchangedeldiv"><span><?php echo $LANG['ADMIN']; ?></span></a></li>
				</ul>
				<div id="exchangedetaildiv" style="">
					<?php
					$exchangeArr = $loanManager->getExchangeDetails($exchangeId);
					?>
					<form name="editexchangegiftform" action="exchange.php" method="post">
						<fieldset>
							<?php
							if($exchangeArr['transactiontype']=='Adjustment'){ ?>
								<legend><?php echo $LANG['EDIT_ADJ']; ?></legend>
								<div style="padding-top:4px;float:left;">
									<div style="padding-top:12px;float:left;">
										<span>
											<b><?php echo $LANG['TRANS_NO']; ?>:</b> <input type="text" autocomplete="off" name="identifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $exchangeArr['identifier']; ?>" disabled />
										</span>
									</div>
									<div style="margin-left:40px;float:left;">
										<span>
											<?php echo $LANG['TRANS_TYPE']; ?>:
										</span><br />
										<span>
												<?php if($exchangeArr['transactiontype']=='Shipment'){ ?>
													<option value="Shipment" <?php echo ($exchangeArr['transactiontype']=='Shipment'?'SELECTED':'');?>><?php echo $LANG['SHIPMENT']; ?></option>
												<?php }
												if($exchangeArr['transactiontype']=='Adjustment'){ ?>
													<option value="Adjustment" <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'SELECTED':'');?>><?php echo $LANG['ADJUSTMENT']; ?></option>
												<?php } ?>
											</select>
										</span>
									</div>
									<div style="margin-left:40px;float:left;">
										<span>
											<?php echo $LANG['ENTERED_BY']; ?>:
										</span><br />
										<span>
											<input type="text" autocomplete="off" name="createdby" maxlength="32" style="width:100px;" value="<?php echo $exchangeArr['createdby']; ?>" disabled />
										</span>
									</div>
								</div>
								<div style="padding-top:8px;float:left;">
									<div style="float:left;">
										<span>
											<?php echo $LANG['INSTITUTION']; ?>:
										</span>
										<span>
											<select name="iid" style="width:400px;" >
												<?php
												$instArr = $loanManager->getInstitutionArr();
												foreach($instArr as $k => $v){
													echo '<option value="' . $k.'" ' . ($k==$exchangeArr['iid']?'SELECTED':'') . '>' . $v . '</option>';
												}
												?>
											</select>
										</span>
									</div>
									<div style="float:left;">
										<span style="margin-left:40px;">
											<b><?php echo $LANG['ADJ_AMOUNT']; ?>:</b> <input type="text" autocomplete="off" name="adjustment" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['adjustment']; ?>" />
										</span>
									</div>
								</div>
								<?php
							}
							else{
								?>
								<legend><?php echo $LANG['EDIT_GIFT_EX']; ?></legend>
								<div style="padding-top:4px;float:left;">
									<div style="padding-top:12px;float:left;">
										<span>
											<b><?php echo $LANG['TRANS_NO']; ?>:</b> <input type="text" autocomplete="off" name="identifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $exchangeArr['identifier']; ?>" disabled />
										</span>
									</div>
									<div style="margin-left:40px;float:left;">
										<span>
											<?php echo $LANG['ENTERED_BY']; ?>:
										</span><br />
										<span>
											<input type="text" autocomplete="off" name="createdby" maxlength="32" style="width:100px;" value="<?php echo $exchangeArr['createdby']; ?>" disabled />
										</span>
									</div>
									<div style="margin-left:40px;float:left;">
										<span>
											<?php echo $LANG['DATE_SHIPPED']; ?>:
										</span><br />
										<span>
											<input type="date" name="datesent" value="<?php echo $exchangeArr['datesent']; ?>" />
										</span>
									</div>
									<div style="margin-left:40px;float:left;">
										<span>
											<?php echo $LANG['DATE_RECEIVED']; ?>:
										</span><br />
										<span>
											<input type="date" name="datereceived" value="<?php echo $exchangeArr['datereceived']; ?>" />
										</span>
									</div>
								</div>
								<div style="padding-top:8px;padding-bottom:8px;float:left;">
									<div style="float:left;">
										<span>
											<?php echo $LANG['INSTITUTION']; ?>:
										</span><br />
										<span>
											<select name="iid" style="width:400px;" >
												<?php
												$instArr = $loanManager->getInstitutionArr();
												foreach($instArr as $k => $v){
													echo '<option value="' . $k . '" ' . ($k==$exchangeArr['iid']?'SELECTED':'') . '>' . $v . '</option>';
												}
												?>
											</select>
										</span>
									</div>
									<div style="margin-left:40px;float:left;">
										<span>
											<?php echo $LANG['TRANS_TYPE']; ?>:
										</span><br />
										<span>
											<select name="transactiontype" style="width:150px;">
												<?php if($exchangeArr['transactiontype']=='Shipment'){ ?>
													<option value="Shipment" <?php echo ($exchangeArr['transactiontype']=='Shipment'?'SELECTED':'');?>><?php echo $LANG['SHIPMENT']; ?></option>
												<?php }
												if($exchangeArr['transactiontype']=='Adjustment'){ ?>
													<option value="Adjustment" <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'SELECTED':'');?>><?php echo $LANG['ADJUSTMENT']; ?></option>
												<?php } ?>
											</select>
										</span>
									</div>
									<div style="margin-left:40px;float:left;">
										<span>
											<?php echo $LANG['IN_OUT']; ?>:
										</span><br />
										<span>
											<select name="in_out" style="width:100px;">
												<?php if($exchangeArr['transactiontype']=='Adjustment'){ ?>
													<option value="" <?php echo (!$exchangeArr['in_out']?'SELECTED':'');?>>   </option>
												<?php }
												if($exchangeArr['transactiontype']=='Shipment'){ ?>
													<option value="Out" <?php echo ('Out'==$exchangeArr['in_out']?'SELECTED':'');?>><?php echo $LANG['OUT']; ?></option>
													<option value="In" <?php echo ('In'==$exchangeArr['in_out']?'SELECTED':'');?>><?php echo $LANG['IN']; ?></option>
												<?php } ?>
											</select>
										</span>
									</div>
								</div>
								<div style="padding-top:8px;padding-bottom:8px;">
									<table class="styledtable" style="font-family:Arial;font-size:12px;">
										<tr>
											<th style="width:220px;text-align:center;"><?php echo $LANG['GIFT_SPECIMENS']; ?></th>
											<th style="width:220px;text-align:center;"><?php echo $LANG['EXCH_SPECIMENS']; ?></th>
											<th style="width:220px;text-align:center;"><?php echo $LANG['TRANS_TOTALS']; ?></th>
										</tr>
										<tr style="text-align:right;">
											<td><b><?php echo $LANG['TOTAL_GIFTS']; ?>:</b> <input type="text" autocomplete="off" name="totalgift"  maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['totalgift']; ?>" <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'disabled':'');?> /></td>
											<td><b><?php echo $LANG['TOTAL_UNMOUNTED']; ?>:</b> <input type="text" autocomplete="off" name="totalexunmounted" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['totalexunmounted']; ?>" <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'disabled':'');?> /></td>
											<td><b><?php echo $LANG['EXCHANGE_VALUE']; ?>:</b> <input type="text" name="exchangevalue" maxlength="32" style="width:80px;border:1px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $loanManager->getExchangeValue($exchangeId); ?>" disabled="disabled" /></td>
										</tr>
										<tr style="text-align:right;">
											<td><b><?php echo $LANG['TOTAL_GIFTS_DET']; ?>:</b> <input type="text" autocomplete="off" name="totalgiftdet" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['totalgiftdet']; ?>" <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'disabled':'');?> /></td>
											<td><b><?php echo $LANG['TOTAL_MOUNTED']; ?>:</b> <input type="text" autocomplete="off" name="totalexmounted" maxlength="32" style="width:80px;" value="<?php echo $exchangeArr['totalexmounted']; ?>" <?php echo ($exchangeArr['transactiontype']=='Adjustment'?'disabled':'');?> /></td>
											<td><b><?php echo $LANG['TOTAL_SPECIMENS']; ?>:</b> <input type="text" name="totalspecimens" maxlength="32" style="width:80px;border:1px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $loanManager->getExchangeTotal($exchangeId); ?>" disabled="disabled" /></td>
										</tr>
									</table>
								</div>
								<div style="padding-top:8px;float:left;">
									<div style="padding-top:15px;float:left;">
										<span style="margin-left:25px;">
											<b><?php echo $LANG['CURRENT_BALANCE']; ?>:</b> <input type="text" name="invoicebalance" maxlength="32" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $exchangeArr['invoicebalance']; ?>" disabled />
										</span>
									</div>
									<div style="margin-left:100px;float:left;">
										<span>
											<?php echo $LANG['NO_BOXES']; ?>:
										</span><br />
										<span>
											<input type="text" autocomplete="off" name="totalboxes" maxlength="32" style="width:50px;" value="<?php echo $exchangeArr['totalboxes']; ?>" />
										</span>
									</div>
									<div style="margin-left:60px;float:left;">
										<span>
											<?php echo $LANG['SHIPPING_SERVICE']; ?>:
										</span><br />
										<span>
											<input type="text" autocomplete="off" name="shippingmethod" maxlength="32" style="width:180px;" value="<?php echo $exchangeArr['shippingmethod']; ?>" />
										</span>
									</div>
								</div>
								<div style="padding-top:8px;float:left;">
									<div style="float:left;">
										<span>
											<?php echo $LANG['DESCRIPTION']; ?>:
										</span><br />
										<span>
											<textarea name="description" rows="10" style="width:320px;resize:vertical;"><?php echo $exchangeArr['description']; ?></textarea>
										</span>
									</div>
									<div style="margin-left:40px;float:left;">
										<span>
											<?php echo $LANG['NOTES']; ?>:
										</span><br />
										<span>
											<textarea name="notes" rows="10" style="width:320px;resize:vertical;"><?php echo $exchangeArr['notes']; ?></textarea>
										</span>
									</div>
								</div>
								<div style="width:100%;padding-top:8px;float:left;">
									<hr />
								</div>
								<div style="padding-top:8px;float:left;">
									<span>
										<?php echo $LANG['ADDITIONAL_MESSAGE']; ?>:
									</span><br />
									<span>
										<textarea name="invoicemessage" rows="5" style="width:700px;resize:vertical;"><?php echo $exchangeArr['invoicemessage']; ?></textarea>
									</span>
								</div>
								<?php
							}
							?>
							<div style="clear:both;padding-top:8px;">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input name="exchangeid" type="hidden" value="<?php echo $exchangeId; ?>" />
								<input name="tabindex" type="hidden" value="2" />
								<button name="formsubmit" type="submit" value="Save Exchange"><?php echo $LANG['SAVE']; ?></button>
							</div>
						</fieldset>
					</form>
					<?php
					if($exchangeArr['transactiontype']=='Shipment'){
						//Following variables are used within reportsinclude.php, with different values when used on different pages
						$loanType = 'exchange';
						$identifier = $exchangeId;
						include('reportsinclude.php');
					}
					$attachments = $loanManager->getAttachments('exch', $exchangeId);
					if($attachments !== false){
						?>
						<div>
							<form id="attachmentform" name="attachmentform" action="exchange.php" method="post" enctype="multipart/form-data" onsubmit="return verifyFileUploadForm(this)">
								<fieldset>
									<legend><?php echo $LANG['CORRESPONDENCE_ATTACH']; ?></legend>
									<?php
									// Add any correspondence attachments
									if ($attachments) {
										echo '<ul>';
										foreach($attachments as $attachId => $attachArr){
											echo '<li><div style="float: left;">' . $attachArr['timestamp'] . ' -</div>';
											echo '<div style="float: left; margin-left: 5px;"><a href="../../' .
												$attachArr['path'] . $attachArr['filename']  .'" target="_blank" rel="noopener noreferrer">' .
												($attachArr['title'] != "" ? $attachArr['title'] : $attachArr['filename']) . '</a></div>';
											echo '<a href="exchange.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&exchangeid=' . htmlspecialchars($exchangeId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&attachid=' . htmlspecialchars($attachId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&formsubmit=delAttachment"><img src="../../images/del.png" style="width: 1.2em; margin-left: 5px;"></a></li>';
										}
										echo '</ul>';
									}
									?>
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
									<input name="exchangeid" type="hidden" value="<?php echo $exchangeId; ?>" />
									<input name="identifier" type="hidden" value="<?php echo $exchangeArr['identifier']; ?>" />
									<label style="font-weight: bold;"><?php echo $LANG['ADD_CORRESPONDENCE_ATTACH']; ?>:<sup>*</sup> </label><br/>
									<label><?php echo $LANG['ATTACH_TITLE']; ?>: </label>
									<input name="uploadtitle" type="text" placeholder=" optional, replaces filename" maxlength="80" size="30" />
									<input id="uploadfile" name="uploadfile" type="file" size="30" onchange="verifyFileSize(this)">
									<button name="formsubmit" type="submit" value="saveAttachment"><?php echo $LANG['SAVE_ATTACH']; ?></button>
									<div style="margin-left: 10px"><br/>
									<sup>*</sup><?php echo $LANG['ATTACH_DESCRIPTION']; ?>
									</div>
								</fieldset>
							</form>
						</div>
						<?php
					}
					?>
					<div style="margin:20px"><b>&lt;&lt; <a href="index.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo $LANG['RETURN_LOAN_INDEX']; ?></a></b></div>
				</div>
				<div id="exchangedeldiv">
					<form name="delexchangeform" action="index.php" method="post" onsubmit="return confirm('<?php echo $LANG['SURE_DELETE_EX']; ?>')">
						<fieldset>
							<legend><?php echo $LANG['DELETE_EXCHANGE']; ?></legend>
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="tabindex" type="hidden" value="2" />
							<input name="exchangeid" type="hidden" value="<?php echo $exchangeId; ?>" />
							<button class="button-danger" name="formsubmit" type="submit" value="Delete Exchange"><?php echo $LANG['DELETE_EXCHANGE']; ?></button>
						</fieldset>
					</form>
				</div>
			</div>
			<?php
		}
		else{
			if(!$isEditor) echo '<h2>' . $LANG['NOT_AUTHORIZED'] . '</h2>';
			else echo '<h2>' . $LANG['UNKNOWN_ERROR'] . '</h2>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
