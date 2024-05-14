<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceLoans.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ' . $CLIENT_ROOT . '/profile/index.php?refurl=../collections/loans/outgoing.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = $_REQUEST['collid'];
$loanId = array_key_exists('loanid', $_REQUEST) ? $_REQUEST['loanid'] : 0;
$tabIndex = array_key_exists('tabindex', $_REQUEST) ? $_REQUEST['tabindex'] : 0;
$sortTag = (isset($_REQUEST['sortTag']) ? $_REQUEST['sortTag'] : '');
$formSubmit = array_key_exists('formsubmit', $_REQUEST) ? $_REQUEST['formsubmit'] : '';

$loanManager = new OccurrenceLoans();

//Sanitation
$collid = $loanManager->sanitizeInt($collid);
$loanId = $loanManager->sanitizeInt($loanId);
$tabIndex = $loanManager->sanitizeInt($tabIndex);

$isEditor = 0;
if($SYMB_UID && $collid){
	if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))
		|| (array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollEditor']))){
		$isEditor = 1;
	}
}

if($collid) $loanManager->setCollId($collid);

$statusStr = '';
if($isEditor){
	if($formSubmit){
		if($formSubmit == 'createLoanOut'){
			$loanId = $loanManager->createNewLoanOut($_POST);
			if(!$loanId) $statusStr = $loanManager->getErrorMessage();
		}
		elseif($formSubmit == 'Save Outgoing'){
			$statusStr = $loanManager->editLoanOut($_POST);
		}
		elseif($formSubmit == 'deleteSpecimens'){
			if(!$loanManager->deleteSpecimens($_POST['occid'], $_POST['loanid'])) $statusStr = $loanManager->getErrorMessage();
		}
		elseif($formSubmit == 'checkinSpecimens'){
			if(!$loanManager->batchCheckinSpecimens($_POST['occid'], $_POST['loanid'])) $statusStr = $loanManager->getErrorMessage();
		}
		elseif($formSubmit == 'addDeterminations'){
			include_once($SERVER_ROOT . '/classes/OccurrenceEditorDeterminations.php');
			$occManager = new OccurrenceEditorDeterminations();
			$occidArr = $_REQUEST['occid'];
			foreach($occidArr as $k){
				$occManager->setOccId($k);
				$occManager->addDetermination($_REQUEST,$isEditor);
			}
		}
		elseif($formSubmit == 'batchProcessSpecimens'){
			$cnt = $loanManager->batchProcessSpecimens($_POST);
			$statusStr = '<ul>';
			$statusStr .= '<li><b>' . $cnt . '</b> ' . $LANG['PROC_SUCCESS'] . '</li>';
			if($warnArr = $loanManager->getWarningArr()){
				if(isset($warnArr['missing'])){
					$statusStr .= '<li style="color:red;"><b>' . $LANG['CATNUMS_NOT_LOCATED'] . '</b></li>';
					foreach($warnArr['missing'] as $catNum){
						$statusStr .= '<li style="margin-left:10px;color:black;">' . $catNum . '</li>';
					}
				}
				if(isset($warnArr['multiple'])){
					$statusStr .= '<li style="color:orange;"><b>' . $LANG['CATNUM_MULTIPLE_MATCHES'] . '</b></li>';
					foreach($warnArr['multiple'] as $catNum){
						$statusStr .= '<li style="margin-left:10px;color:black;">' . $catNum . '</li>';
					}
				}
				if(isset($warnArr['dupe'])){
					$statusStr .= '<li style="color:orange"><b>' . $LANG['SPECS_ALREADY_LINKED'] . '</b></li>';
					foreach($warnArr['dupe'] as $catNum){
						$statusStr .= '<li style="margin-left:10px;color:black;">' . $catNum . '</li>';
					}
				}
				if(isset($warnArr['zeroMatch'])){
					$statusStr .= '<li style="color:orange;"><b>' . $LANG['ALREADY_CHECKED_IN'] . '</b></li>';
					foreach($warnArr['zeroMatch'] as $catNum){
						$statusStr .= '<li style="margin-left:10px;color:black;">' . $catNum . '</li>';
					}
				}
				if(isset($warnArr['error'])){
					$statusStr .= '<li style="color:red;"><b>' . $LANG['MISC_ERROR'] . '</b></li>';
					foreach($warnArr['error'] as $errStr){
						$statusStr .= '<li style="margin-left:10px;color:black;">' . $errStr . '</li>';
					}
				}
			}
			$statusStr .= '</ul>';
			$tabIndex = 1;
		}
		elseif($formSubmit == 'saveSpecimenDetails'){
			if($loanManager->editSpecimenDetails($loanId,$_POST['occid'],$_POST['returndate'],$_POST['notes'])) $statusStr = true;
			echo $statusStr = $loanManager->getErrorMessage();
		}
		elseif($formSubmit == 'exportSpecimenList'){
			$loanManager->exportSpecimenList($loanId);
			exit;
		}
		elseif ($formSubmit == "delAttachment") {
			// Delete correspondence attachment
			if (array_key_exists('attachid',$_REQUEST) && is_numeric($_REQUEST['attachid'])) $loanManager->deleteAttachment($_REQUEST['attachid']);
			$statusStr = $loanManager->getErrorMessage();
		}
		elseif ($formSubmit == "saveAttachment") {
			// Save correspondence attachment
			if (array_key_exists('uploadfile',$_FILES)) $loanManager->uploadAttachment($collid, 'loan', $loanId, $loanIdOwn, $_POST['uploadtitle'], $_FILES['uploadfile']);
			$statusStr = $loanManager->getErrorMessage();
		}
	}
}
$specimenTotal = $loanManager->getSpecimenTotal($loanId);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo $DEFAULT_TITLE . ': ' . $LANG['OUTGOING_LOAN_MANAGE']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;

		function verifyLoanOutEditForm(){
			var submitStatus = true;
			$("#editLoanOutForm input[type=date]").each(function() {
				//Need for Safari browser which doesn't support date input types
				if(this.value != ""){
					var validFormat = /^\s*\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd
					if(!validFormat.test(this.value)){
						alert("<?php echo $LANG['DATE_EXAMPLE']; ?>"+this.name+"<?php echo $LANG['VALUES_FORMAT']; ?>");
						submitStatus = false;
					}
				}
			});
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
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo $LANG['COL_MNG_MENU']; ?></a> &gt;&gt;
		<a href="index.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo $LANG['LOAN_INDEX']; ?></a> &gt;&gt;
		<a href="outgoing.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&loanid=' . htmlspecialchars($loanId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><b><?php echo $LANG['OUTGOING_LOAN_MANAGE']; ?></b></a>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['OUTGOING_LOAN_MANAGE']; ?></h1>
		<?php
		if($isEditor && $collid){
			//Collection is defined and User is logged-in and have permissions
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
					<li><a href="#outloandetaildiv"><span><?= $LANG['LOAN_DETAILS'] ?></span></a></li>
					<li><a href="specimentab.php?collid=<?= $collid . '&loanid=' . $loanId . '&sortTag=' . htmlspecialchars($sortTag, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>"><span><?= $LANG['CAP_SPECIMENS'] ?></span></a></li>
					<li><a href="#outloandeldiv"><span><?= $LANG['ADMIN'] ?></span></a></li>
				</ul>
				<div id="outloandetaildiv">
					<?php
					$loanArr = $loanManager->getLoanOutDetails($loanId);
					?>
					<form id="editLoanOutForm" name="editLoanOutForm" action="outgoing.php" method="post" onsubmit="return verifyLoanOutEditForm(this)">
						<fieldset>
							<legend><?php echo $LANG['LOAN_OUT_DETAILS']; ?></legend>
							<div style="padding-top:18px;float:left;">
								<span>
									<b><?php echo $LANG['LOAN_NUMBER']; ?>:</b> <input type="text" autocomplete="off" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $loanArr['loanidentifierown']; ?>" />
								</span>
							</div>
							<div style="margin-left:20px;padding-top:4px;float:left;">
								<span>
									<?php echo $LANG['ENTERED_BY']; ?>:
								</span><br />
								<span>
									<input type="text" autocomplete="off" name="createdbyown" maxlength="32" style="width:100px;" value="<?php echo $loanArr['createdbyown']; ?>" disabled />
								</span>
							</div>
							<div style="margin-left:20px;padding-top:4px;float:left;">
								<span>
									<?php echo $LANG['PROCESSED_BY']; ?>:
								</span><br />
								<span>
									<input type="text" autocomplete="off" name="processedbyown" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyown']; ?>" />
								</span>
							</div>
							<div style="margin-left:20px;padding-top:4px;float:left;">
								<span>
									<?php echo $LANG['DATE_SENT']; ?>:
								</span><br />
								<span>
									<input type="date" name="datesent" value="<?php echo $loanArr['datesent']; ?>" />
								</span>
							</div>
							<div style="margin-left:20px;padding-top:4px;float:left;">
								<span>
									<?php echo $LANG['DATE_DUE']; ?>:
								</span><br />
								<span>
									<input type="date" name="datedue" value="<?php echo $loanArr['datedue']; ?>" />
								</span>
							</div>
							<div style="padding-top:8px;float:left;">
								<span>
									<?php echo $LANG['SENT_TO']; ?>:
								</span><br />
								<span>
									<select name="iidborrower">
										<?php
										$instArr = $loanManager->getInstitutionArr();
										foreach($instArr as $k => $v){
											echo '<option value="' . $k . '" ' . ($loanArr['iidborrower']==$k?'SELECTED':'') . '>' . $v . '</option>';
										}
										?>
									</select>
								</span>
								<?php
								if($IS_ADMIN){
									?>
									<span>
										<a href="../misc/institutioneditor.php?iid=<?php echo htmlspecialchars($loanArr['iidborrower'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank" title="<?php echo $LANG['EDIT_INST_DETAILS']; ?>">
											<img src="../../images/edit.png" style="width:1.3em;" />
										</a>
									</span>
									<?php
								}
								?>
							</div>
							<div style="padding-top:8px;float:left;">
								<div style="float:left;">
									<span>
										<?php echo $LANG['REQUESTED_FOR']; ?>:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="forwhom" maxlength="32" style="width:180px;" value="<?php echo $loanArr['forwhom']; ?>" onchange=" " />
									</span>
								</div>
								<div style="margin-left:20px;float:left;">
									<span>
										<b><?php echo $LANG['TOTAL_SPECIMENS']; ?>:</b><br />
										<input type="text" name="totalspecimens" maxlength="32" style="width:150px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="<?php echo $specimenTotal; ?>" onchange=" " disabled />
									</span>
								</div>
								<div style="margin-left:20px;float:left;">
									<span>
										<?php echo $LANG['NO_BOXES']; ?>:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="totalboxes" maxlength="32" style="width:50px;" value="<?php echo $loanArr['totalboxes']; ?>" onchange=" " />
									</span>
								</div>
								<div style="margin-left:20px;float:left;">
									<span>
										<?php echo $LANG['SHIPPING_SERVICE']; ?>:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="shippingmethod" maxlength="32" style="width:180px;" value="<?php echo $loanArr['shippingmethod']; ?>" onchange=" " />
									</span>
								</div>
							</div>
							<div style="padding-top:8px;float:left;">
								<div style="float:left;">
									<span>
										<?php echo $LANG['LOAN_DESCRIPTION']; ?>:
									</span><br />
									<span>
										<textarea name="description" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $loanArr['description']; ?></textarea>
									</span>
								</div>
								<div style="margin-left:20px;float:left;">
									<span>
										<?php echo $LANG['NOTES']; ?>:
									</span><br />
									<span>
										<textarea name="notes" rows="10" style="width:320px;resize:vertical;" onchange=" "><?php echo $loanArr['notes']; ?></textarea>
									</span>
								</div>
							</div>
							<div style="width:100%;padding-top:8px;float:left;">
								<hr />
							</div>
							<div style="padding-top:8px;float:left;">
								<div style="float:left;">
									<span>
										<?php echo $LANG['DATE_RECEIVED']; ?>:
									</span><br />
									<span>
										<input type="date" name="datereceivedown" value="<?php echo $loanArr['datereceivedown']; ?>" />
									</span>
								</div>
								<div style="margin-left:40px;float:left;">
									<span>
										<?php echo $LANG['RET_PROCESSED_BY']; ?>:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="processedbyreturnown" maxlength="32" style="width:100px;" value="<?php echo $loanArr['processedbyreturnown']; ?>" onchange=" " />
									</span>
								</div>
								<div style="margin-left:40px;float:left;">
									<span>
										<?php echo $LANG['DATE_CLOSED']; ?>:
									</span><br />
									<span>
										<input type="date" name="dateclosed" value="<?php echo $loanArr['dateclosed']; ?>" />
									</span>
								</div>
							</div>
							<div style="clear:left;padding-top:8px;float:left;">
								<span>
									<?php echo $LANG['ADD_INV_MESSAGE']; ?>:
								</span><br />
								<span>
									<textarea name="invoicemessageown" rows="5" style="width:700px;resize:vertical;"><?php echo $loanArr['invoicemessageown']; ?></textarea>
								</span>
							</div>
							<div style="clear:both;padding:10px;">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
								<button name="formsubmit" type="submit" value="Save Outgoing"><?php echo $LANG['SAVE']; ?></button>
							</div>
						</fieldset>
					</form>
					<?php
					//Following variables are used within reportsinclude.php, with different values when used on different pages
					$loanType = 'out';
					$identifier = $loanId;
					include('reportsinclude.php');
					$attachments = $loanManager->getAttachments('loan', $loanId);
					if($attachments !== false){
						?>
						<div>
							<form id="attachmentform" name="attachmentform" action="outgoing.php" method="post" enctype="multipart/form-data" onsubmit="return verifyFileUploadForm(this)">
								<fieldset>
									<legend><?php echo $LANG['CORRESPONDENCE_ATTACH']; ?></legend>
									<?php
									// Add any correspondence attachments
									if ($attachments) {
										echo '<ul>';
										foreach($attachments as $attachId => $attachArr){
											echo '<li><div style="float: left;">' . $attachArr['timestamp'] . ' -</div>';
											echo '<div style="float: left; margin-left: 5px;"><a href="../../' .
												$attachArr['path'] . $attachArr['filename']  . '" target="_blank">' .
												($attachArr['title'] != "" ? $attachArr['title'] : $attachArr['filename']) . '</a></div>';
											echo '<a href="outgoing.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&loanid=' . htmlspecialchars($loanId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&attachid=' . htmlspecialchars($attachId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&formsubmit=delAttachment"><img src="../../images/del.png" style="width: 1.2em; margin-left: 5px;"></a></li>';
										}
										echo '</ul>';
									}
									?>
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
									<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
									<input name="loanidentifierown" type="hidden" value="<?php echo $loanArr['loanidentifierown']; ?>" />
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
					<div style="margin:20px"><b>&lt;&lt; <a href="index.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">Return to Loan Index Page</a></b></div>
				</div>
				<div id="outloandeldiv">
					<form name="deloutloanform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this loan?')">
						<fieldset>
							<legend><?php echo $LANG['DEL_OUTGOING_LOAN']; ?></legend>
							<?php
							if($specimenTotal){
								?>
								<div style=";margin-bottom:15px;">
									<?php echo $LANG['CANNOT_DEL_LOAN']; ?>
								</div>
								<?php
							}
							?>
							<button class="button-danger" name="formsubmit" type="submit" value="Delete Loan" <?php if($specimenTotal) echo 'DISABLED'; ?>><?php echo $LANG['DELETE_LOAN']; ?></button>
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
