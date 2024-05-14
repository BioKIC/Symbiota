<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceLoans.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ' . $CLIENT_ROOT . '/profile/index.php?refurl=../collections/loans/incoming.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$loanId = array_key_exists('loanid',$_REQUEST)?$_REQUEST['loanid']:0;
$loanIdborr = array_key_exists('loanidentifierborr',$_REQUEST)?$_REQUEST['loanidentifierborr']:0;
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
		if($formSubmit == 'createLoanIn'){
			$loanId = filter_var($loanManager->createNewLoanIn($_POST), FILTER_SANITIZE_NUMBER_INT);
			if(!$loanId) $statusStr = $loanManager->getErrorMessage();
		}
		elseif($formSubmit == 'Save Incoming'){
			$statusStr = htmlspecialchars($loanManager->editLoanIn($_POST), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
		}
		elseif ($formSubmit == "delAttachment") {
			// Delete correspondence attachment
			if (array_key_exists('attachid',$_REQUEST) && is_numeric($_REQUEST['attachid'])) $loanManager->deleteAttachment($_REQUEST['attachid']);
			 $statusStr = $loanManager->getErrorMessage();
		}
		elseif ($formSubmit == "saveAttachment") {
			// Save correspondence attachment
			if (array_key_exists('uploadfile',$_FILES)) $loanManager->uploadAttachment($collid, 'loan', $loanId, $loanIdborr, $_POST['uploadtitle'], $_FILES['uploadfile']);
			$statusStr = $loanManager->getErrorMessage();
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo $DEFAULT_TITLE . ': ' . $LANG['INCOMING_LOAN_MANAGE']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;

		function verifyLoanInEditForm(f){
			var submitStatus = true;
			$("#editLoanInForm input[type=date]").each(function() {
				//Need for Safari browser which doesn't support date input types
				if(this.value != ""){
					var validFormat = /^\s*\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd
					if(!validFormat.test(this.value)){
						alert("<?php echo $LANG['DATE_EXAMPLE']; ?>"+this.name+"<?php echo $LANG['VALUES_FORMAT']; ?>");
						submitStatus = false;
					}
				}
			});
			if(f.iidowner.options[f.iidowner.selectedIndex].value == 0){
				alert("<?php echo $LANG['SEL_INSTITUTION']; ?>");
				submitStatus = false;
			}
			if(f.loanidentifierown.value == ""){
				alert("<?php echo $LANG['ENTER_LOAN_NO']; ?>");
				submitStatus = false;
			}
			return submitStatus;
		}
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
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div class="navpath">
		<a href='../../index.php'>Home</a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo $LANG['COL_MNG_MENU']; ?></a> &gt;&gt;
		<a href="index.php?tabindex=1&collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo $LANG['LOAN_INDEX']; ?></a> &gt;&gt;
		<a href="incoming.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&loanid=' . htmlspecialchars($loanId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><b><?php echo $LANG['INCOMING_LOAN_MANAGE']; ?></b></a>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['INCOMING_LOAN_MANAGE']; ?></h1>
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
			$specList = $loanManager->getSpecimenList($loanId);
			?>
			<div id="tabs" style="margin:0px;">
			    <ul>
					<li><a href="#loandiv"><span><?php echo $LANG['LOAN_DETAILS']; ?></span></a></li>
					<?php
					if($specList){
						?>
						<li><a href="#specdiv"><span><?php echo $LANG['SPECIMENS']; ?></span></a></li>
						<?php
					}
					?>
					<li><a href="#inloandeldiv"><span><?php echo $LANG['ADMIN']; ?></span></a></li>
				</ul>
				<div id="loandiv">
					<?php
					$loanArr = $loanManager->getLoanInDetails($loanId);
					?>
					<form id="editLoanInForm" name="editloanform" action="incoming.php" method="post" onsubmit="return verifyLoanInEditForm(this)">
						<fieldset>
							<legend><?php echo $LANG['LOAN_IN_DETAILS']; ?></legend>
							<div style="padding-top:18px;float:left;">
								<span>
									<b><?php echo $LANG['LOAN_NUMBER']; ?>:</b> <input type="text" autocomplete="off" name="loanidentifierborr" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo ($loanArr['loanidentifierborr']?$loanArr['loanidentifierborr']:$loanArr['loanidentifierown']); ?>" />
								</span>
							</div>
							<div style="margin-left:20px;padding-top:4px;float:left;">
								<span>
									<?php echo $LANG['ENTERED_BY']; ?>:
								</span><br />
								<span>
									<input type="text" autocomplete="off" name="createdbyborr" maxlength="32" style="width:100px;" value="<?php echo ($loanArr['createdbyborr']?$loanArr['createdbyborr']:$PARAMS_ARR['un']); ?>" onchange=" " disabled />
								</span>
							</div>
							<div style="margin-left:20px;padding-top:4px;float:left;">
								<span>
									<?php echo $LANG['PROCESSED_BY']; ?>:
								</span><br />
								<span>
									<input type="text" autocomplete="off" name="processedbyborr" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyborr']; ?>" onchange=" " />
								</span>
							</div>
							<div style="margin-left:20px;padding-top:4px;float:left;">
								<span>
									<?php echo $LANG['DATE_RECEIVED']; ?>:
								</span><br />
								<span>
									<input type="date" name="datereceivedborr" value="<?php echo $loanArr['datereceivedborr']; ?>" onchange="checkDate(this)" />
								</span>
							</div>
							<div style="margin-left:20px;padding-top:4px;float:left;">
								<span>
									<?php echo $LANG['DATE_DUE']; ?>:
								</span><br />
								<span>
									<input type="date" name="datedue" value="<?php echo $loanArr['datedue']; ?>" <?php echo ($loanArr['collidown']?'disabled':''); ?> onchange="checkDate(this)" />
								</span>
							</div>
							<div style="padding-top:8px;float:left;">
								<div style="float:left;">
									<span>
										<?php echo $LANG['SENT_FROM']; ?>:
									</span><br />
									<span>
										<select name="iidowner">
											<?php
											$instArr = $loanManager->getInstitutionArr();
											foreach($instArr as $k => $v){
												echo '<option value="' . $k . '" ' . ($loanArr['iidowner']==$k?'SELECTED':'') . '>' . $v . '</option>';
											}
											?>
										</select>
									</span>
								</div>
							</div>
							<div style="padding-top:8px;float:left;">
								<div style="float:left;margin-right:40px;">
									<span>
										<?php echo $LANG['SENDERS_LOAN_NUMBER']; ?>:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="loanidentifierown" maxlength="255" style="width:160px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $loanArr['loanidentifierown']; ?>" <?php echo ($loanArr['collidown']?'disabled':''); ?> />
									</span>
								</div>
								<div style="float:left;margin-right:40px;">
									<span>
										<?php echo $LANG['REQUESTED_FOR']; ?>:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="forwhom" maxlength="32" style="width:180px;" value="<?php echo $loanArr['forwhom']; ?>" onchange=" " />
									</span>
								</div>
								<div style="float:left;">
									<span>
										<b><?php echo $LANG['TOTAL_SPECIMENS']; ?>:</b><br />
										<input type="text" autocomplete="off" name="numspecimens" maxlength="32" style="width:150px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo ($loanArr['collidown']?count($specList):$loanArr['numspecimens']); ?>" onchange=" " <?php echo ($loanArr['collidown']?'disabled':''); ?> />
									</span>
								</div>
							</div>
							<div style="padding-top:8px;clear:both;">
								<div style="float:left;">
									<span>
										<?php echo $LANG['LOAN_DESCRIPTION']; ?>:
									</span><br />
									<span>
										<textarea name="description" rows="10" style="width:320px;resize:vertical;" onchange=" " <?php echo ($loanArr['collidown']?'disabled="disabled"':''); ?> ><?php echo $loanArr['description']; ?></textarea>
									</span>
								</div>
								<div style="margin-left:20px;float:left;">
									<span>
										<?php echo $LANG['NOTES']; ?>:
									</span><br />
									<span>
										<textarea name="notes" rows="10" style="width:320px;resize:vertical;" onchange=" " <?php echo ($loanArr['collidown']?'disabled="disabled"':''); ?> ><?php echo $loanArr['notes']; ?></textarea>
									</span>
								</div>
							</div>
							<div style="width:100%;padding-top:8px;float:left;">
								<hr />
							</div>
							<div style="padding-top:8px;float:left;">
								<div style="float:left;">
									<span>
										<?php echo $LANG['DATE_RETURNED']; ?>:
									</span><br />
									<span>
										<input type="date" name="datesentreturn" value="<?php echo $loanArr['datesentreturn']; ?>" onchange="checkDate(this)" />
									</span>
								</div>
								<div style="margin-left:40px;float:left;">
									<span>
										<?php echo $LANG['RET_PROCESSED_BY']; ?>:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="processedbyreturnborr" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyreturnborr']; ?>" onchange=" " />
									</span>
								</div>
								<div style="margin-left:40px;float:left;">
									<span>
										<?php echo $LANG['NO_BOXES']; ?>:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="totalboxesreturned" maxlength="32" style="width:50px;" value="<?php echo $loanArr['totalboxesreturned']; ?>" onchange=" " />
									</span>
								</div>
								<div style="margin-left:40px;float:left;">
									<span>
										<?php echo $LANG['SHIPPING_SERVICE']; ?>:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="shippingmethodreturn" maxlength="32" style="width:180px;" value="<?php echo $loanArr['shippingmethodreturn']; ?>" onchange=" " />
									</span>
								</div>
								<div style="margin-left:40px;float:left;">
									<span>
										<?php echo $LANG['DATE_CLOSED']; ?>:
									</span><br />
									<span>
										<input type="date" name="dateclosed" value="<?php echo $loanArr['dateclosed']; ?>" <?php echo ($loanArr['collidown']?'disabled':''); ?> onchange="checkDate(this)" />
									</span>
								</div>
							</div>
							<div style="padding-top:8px;float:left;">
								<div>
									<?php echo $LANG['ADD_INV_MESSAGE']; ?>:
								</div>
								<div>
									<textarea name="invoicemessageborr" rows="5" style="width:700px;resize:vertical;" onchange=" "><?php echo $loanArr['invoicemessageborr']; ?></textarea>
								</div>
							</div>
							<div style="clear:both;padding-top:8px;">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input name="collidborr" type="hidden" value="<?php echo $collid; ?>" />
								<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
								<button name="formsubmit" type="submit" value="Save Incoming"><?php echo $LANG['SAVE']; ?></button>
							</div>
						</fieldset>
					</form>
					<?php
					//Following variables are used within reportsinclude.php, with different values when used on different pages
					$specimenTotal = count($specList);
					$loanType = 'in';
					$identifier = $loanId;
					include('reportsinclude.php');
					$attachments = $loanManager->getAttachments('loan', $loanId);
					if($attachments !== false){
						?>
						<div>
							<form id="attachmentform" name="attachmentform" action="incoming.php" method="post" enctype="multipart/form-data" onsubmit="return verifyFileUploadForm(this)">
								<fieldset>
									<legend><?php echo $LANG['CORRESPONDENCE_ATTACH']; ?></legend>
									<?php
									// Add any correspondence attachments
									if ($attachments) {
										echo '<ul>';
										foreach($attachments as $attachId => $attachArr){
											echo '<li><div style="float: left;">' . $attachArr['timestamp'] . ' -</div>';
											echo '<div style="float: left; margin-left: 5px;"><a href="../../' .
												$attachArr['path'] . $attachArr['filename']  . '" target="_blank" rel="noopener">' .
												($attachArr['title'] != "" ? $attachArr['title'] : $attachArr['filename']) . '</a></div>';
											echo '<a href="incoming.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&loanid=' . htmlspecialchars($loanId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&attachid=' . htmlspecialchars($attachId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&formsubmit=delAttachment"><img src="../../images/del.png" style="width: 1.2em; margin-left: 5px;"></a></li>';
										}
										echo '</ul>';
									}
									?>

									<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
									<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
									<input name="loanidentifierborr" type="hidden" value="<?php echo ($loanArr['loanidentifierborr'] ? $loanArr['loanidentifierborr'] : $loanArr['loanidentifierown']); ?>" />
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
				<?php
				if($specList){
					?>
					<div id="specdiv">
						<table class="styledtable" style="font-family:Arial;font-size:12px;">
							<tr>
								<th style="width:100px;text-align:center;"><?php echo $LANG['CATNO']; ?></th>
								<th style="width:375px;text-align:center;"><?php echo $LANG['DETAILS']; ?></th>
								<th style="width:75px;text-align:center;"><?php echo $LANG['DATE_RETURNED']; ?></th>
							</tr>
							<?php
							foreach($specList as $occid => $specArr){
								?>
								<tr>
									<td>
										<div style="float:right">
											<a href="#" onclick="openIndPopup(<?php echo $occid; ?>); return false;"><img src="../../images/list.png" style="width:1.3em" title="<?php echo $LANG['OPEN_SPECIMEN_DETAILS']; ?>" /></a><br/>
										</div>
										<div style="float:right">
											<a href="#" onclick="openEditorPopup(<?php echo $occid; ?>); return false;"><img src="../../images/edit.png" style="width:1.3em" title="<?php echo $LANG['OPEN_OCC_EDITOR']; ?>" /></a>
										</div>
										<?php
										if($specArr['catalognumber']) echo '<div>' . $specArr['catalognumber'] . '</div>';
										if(isset($specArr['othercatalognumbers'])) echo '<div>' . implode('; ',$specArr['othercatalognumbers']) . '</a></div>';
										?>
									</td>
									<td>
										<?php
										$loc = $specArr['locality'];
										if(strlen($loc) > 500) $loc = substr($loc,400);
										echo '<i>' . $specArr['sciname'] . '</i>; ';
										echo  $specArr['collector'] . '; ' . $loc;
										if($specArr['notes']) echo '<div class="notesDiv"><b>Notes:</b> ' . $specArr['notes'],'</div>';
										?>
									</td>
									<td><?php echo $specArr['returndate']; ?></td>
								</tr>
								<?php
							}
							?>
						</table>
					</div>
					<?php
				}
				?>
				<div id="inloandeldiv">
					<form name="delinloanform" action="index.php" method="post" onsubmit="return confirm('<?php echo $LANG['SURE_DEL_LOAN']; ?>')">
						<fieldset>
							<legend><?php echo $LANG['DEL_INC_LOAN']; ?></legend>
							<?php
							if($specList){
								?>
								<div style=";margin-bottom:15px;">
									<?php echo $LANG['REMOVE_SPECIMENS_TO_DEL']; ?>
								</div>
								<?php
							}
							?>
							<button class="button-danger" name="formsubmit" type="submit" value="Delete Loan" <?php if($specList) echo 'DISABLED'; ?>><?php echo $LANG['DELETE_LOAN']; ?></button>
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
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
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>
</html>
