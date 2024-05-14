<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLoans.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ' . $CLIENT_ROOT.'/profile/index.php?refurl=../collections/loans/index.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = $_REQUEST['collid'];
$searchTerm = array_key_exists('searchterm',$_POST)?$_POST['searchterm']:'';
$displayAll = array_key_exists('displayall',$_POST)?$_POST['displayall']:0;
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

//Sanitation
$searchTerm = htmlspecialchars($searchTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($displayAll)) $displayAll = 0;
if(!is_numeric($tabIndex)) $tabIndex = 0;

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
		if($formSubmit == 'Delete Loan'){
			if($loanManager->deleteLoan($_POST['loanid'])){
				$statusStr = 'Loan deleted successfully!';
			}
		}
		elseif($formSubmit == 'Delete Exchange'){
			if($loanManager->deleteExchange($_POST['exchangeid'])){
				$statusStr = 'Exchange deleted successfully!';
			}
		}
		elseif($formSubmit == 'Save Incoming'){
			$statusStr = $loanManager->editLoanIn($_POST);
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['LOAN_MANAGE']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;

		function verifyLoanInAddForm(f){
			if(f.iidowner.options[f.iidowner.selectedIndex].value == 0){
				alert("<?php echo $LANG['SEL_INSTITUTION'] ?>");
				return false;
			}
			if(f.loanidentifierborr.value == ""){
				alert("<?php echo $LANG['ENTER_LOAN_ID']; ?>");
				return false;
			}
			$.ajax({
				method: "POST",
				data: { ident: f.loanidentifierborr.value, collid: f.collid.value, type: "in" },
				dataType: "text",
				url: "rpc/identifierCheck.php"
			})
			.done(function(retCode) {
				if(retCode == 1) alert("<?php echo $LANG['ID_EXISTS']; ?>");
				else f.submit();
			});
			return false;
		}

		function verfifyLoanOutAddForm(f){
			if(f.reqinstitution.options[f.reqinstitution.selectedIndex].value == 0){
				alert("<?php echo $LANG['SEL_INSTITUTION']; ?>");
				return false;
			}
			if(f.loanidentifierown.value == ""){
				alert("<?php echo $LANG['ENTER_LOAN_ID']; ?>");
				return false;
			}
			$.ajax({
				method: "POST",
				data: { ident: f.loanidentifierown.value, collid: f.collid.value, type: "out" },
				dataType: "text",
				url: "rpc/identifierCheck.php"
			})
			.done(function(retCode) {
				if(retCode == 1) alert("<?php echo $LANG['ID_EXISTS']; ?>");
				else f.submit();
			});
			return false;
		}

		function verfifyExchangeAddForm(f){
			if(f.iid.options[f.iid.selectedIndex].value == 0){
				alert("<?php echo $LANG['SEL_INSTITUTION']; ?>");
				return false;
			}
			if(f.identifier.value == ""){
				alert("<?php echo $LANG['ENTER_EX_ID']; ?>");
				return false;
			}
			$.ajax({
				method: "POST",
				data: { ident: f.identifier.value, collid: f.collid.value, type: "ex" },
				dataType: "text",
				url: "rpc/identifierCheck.php"
			})
			.done(function(retCode) {
				if(retCode == 1) alert("<?php echo $LANG['ID_EXISTS']; ?>");
				else f.submit();
			});
			return false;
		}

		function displayNewLoanOut(){
			if(document.getElementById("loanoutToggle")){
				toggle('newloanoutdiv');
			}
			var f = document.newloanoutform;
			if(f.loanidentifierown.value == ""){
				generateNewId(f.collid.value,f.loanidentifierown,"out");
			}
		}

		function displayNewLoanIn(){
			if(document.getElementById("loaninToggle")){
				toggle('newloanindiv');
			}
			var f = document.newloaninform;
			if(f.loanidentifierborr.value == ""){
				generateNewId(f.collid.value,f.loanidentifierborr,"in");
			}
		}

		function displayNewExchange(){
			if(document.getElementById("exchangeToggle")){
				toggle('newexchangediv');
			}
			var f = document.newexchangegiftform;
			if(f.identifier.value == ""){
				generateNewId(f.collid.value,f.identifier,"ex");
			}
		}

		function generateNewId(collId,targetObj,idType){
			$.ajax({
				method: "POST",
				data: { idtype: idType, collid: collId },
				dataType: "text",
				url: "rpc/generateNextID.php"
			})
			.done(function(retID) {
				targetObj.value = retID;
			})
			.fail(function() {
				alert("<?php echo $LANG['GENERATION_ID_FAILED'] ?>");
			});
		}
	</script>
	<script type="text/javascript" src="../../js/symb/collections.loans.js?ver=2"></script>
	<style>
		fieldset{ padding:10px; }
		fieldset legend{ font-weight:bold }
		.important{ color: red; }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'><?php echo $LANG['HOME'] ?></a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo $LANG['COL_MNG_MENU']; ?></a> &gt;&gt;
		<a href="index.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><b><?php echo $LANG['LOAN_INDEX']; ?></b></a>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['LOAN_MANAGE']; ?></h1>
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
					<li><a href="#loanoutdiv"><span><?php echo $LANG['OUTGOING_LOANS']; ?></span></a></li>
					<li><a href="#loanindiv"><span><?php echo $LANG['INCOMING_LOANS']; ?></span></a></li>
					<li><a href="exchangetab.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><span><?php echo $LANG['GIFTS_EX']; ?></span></a></li>
				</ul>
				<div id="loanoutdiv" style="">
					<div style="float:right;">
						<form name='optionform' action='index.php' method='post'>
							<fieldset>
								<legend><?php echo $LANG['OPTIONS'] ?></legend>
								<div>
									<b><label for="search-loanout"><?php echo $LANG['SEARCH'] ?></label> </b>
									<input type="text" id="search-loanout" autocomplete="off" name="searchterm" value="<?php echo $searchTerm;?>" size="20" />
								</div>
								<div>
									<input type="radio" name="displayall" value="0"<?php echo ($displayAll==0?'checked':'');?> aria-label="<?php echo $LANG['DISP_OUTSTANDING']; ?>" /> <?php echo $LANG['DISP_OUTSTANDING']; ?>
								</div>
								<div>
									<input type="radio" name="displayall" value="1"<?php echo ($displayAll?'checked':'');?> aria-label="<?php echo $LANG['DISP_ALL']; ?>" /> <?php echo $LANG['DISP_ALL']; ?>
								</div>
								<div style="float:right;">
									<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
									<button type="submit" name="formsubmit" value="Refresh List"><?php echo $LANG['REFRESH_LIST']; ?></button>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
					$loanOutList = $loanManager->getLoanOutList($searchTerm,$displayAll);
					if($loanOutList){
						?>
						<div id="loanoutToggle" style="float:right;margin:10px;">
							<a href="#" onclick="displayNewLoanOut();">
								<img src="../../images/add.png" style="width:1.5em;" title="<?php echo $LANG['CREATE_NEW_LOAN']; ?>" alt="<?php echo $LANG['CREATE_NEW_LOAN']; ?>" aria-label="<?php echo $LANG['CREATE_A_NEW_LOAN']; ?>"  />
							</a>
						</div>
						<?php
					}
					?>
					<div id="newloanoutdiv" style="display:<?php echo ($loanOutList || $searchTerm?'none':'block'); ?>;">
						<form name="newloanoutform" action="outgoing.php" method="post" onsubmit="return verfifyLoanOutAddForm(this);">
							<fieldset>
								<legend><?php echo $LANG['CREATE_OUTGOING']; ?></legend>
								<div style="padding-top:4px;float:left;">
									<span>
										<?php echo $LANG['ENTERED_BY']; ?>:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="createdbyown" maxlength="32" style="width:100px;" value="<?php echo $PARAMS_ARR['un']; ?>" title="<?php echo $LANG['ENTERED_BY'] ?>" aria-label="<?php echo $LANG['SUBMITTED_BY'] ?>" />
									</span>
								</div>
								<div style="padding-top:15px;float:right;">
									<span>
										<b><label for="loanidentifierown"><?php echo $LANG['LOAN_ID']; ?></label> </b><input type="text" id="loanidentifierown" autocomplete="off" name="loanidentifierown" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
									</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
										<?php echo $LANG['SEND_INSTITUTION']; ?>:
									</span><br />
									<span>
										<select name="reqinstitution" style="width:400px;" aria-label="<?php echo $LANG['SEND_INSTITUTION'] ?>" >
											<option value=""><?php echo $LANG['SEL_INST']; ?></option>
											<option value="">------------------------------------------</option>
											<?php
											$instArr = $loanManager->getInstitutionArr();
											foreach($instArr as $k => $v){
												echo '<option value="' . $k . '">' . $v . '</option>';
											}
											?>
										</select>
									</span>
									<span>
										<a href="../misc/institutioneditor.php?emode=1" target="_blank" title="<?php echo $LANG['ADD_NEW_INST']; ?>" aria-label="<?php echo $LANG['ADD_A_NEW_INST']; ?>">
											<img src="../../images/add.png" style="width:1.2em;" alt="<?php echo $LANG['ADD_NEW_INST']; ?>" />
										</a>
									</span>
								</div>
								<div style="clear:both;padding-top:8px;float:right;">
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
									<input name="formsubmit" type="hidden" value="createLoanOut" />
									<button name="submitButton" type="submit"><?php echo $LANG['CREATE_LOAN']; ?></button>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
					if(!$loanOutList) echo '<script type="text/javascript">displayNewLoanOut();</script>';
					?>
					<div>
						<?php
						if($loanOutList){
							echo '<ul>';
							foreach($loanOutList as $k => $loanArr){
								$targetCollid = $collid;
								if(isset($loanArr['isexternal'])) $targetCollid = $loanArr['isexternal'];

								// Loan has a due date and is not closed
								$due = '';
								if ($loanArr['datedue'] && !$loanArr['dateclosed']) {
									// Test whether the loan is overdue
									$overdue = strtotime($loanArr['datedue']) - time() < 0;

									// construct due date string
									$due = ' (<span class="' . ($overdue?'important':'') . '">' . $LANG['DUE'] . ': ' . $loanArr['datedue'] . '</span>)';
								}

								echo '<li>';
								echo '<a href="outgoing.php?collid=' . htmlspecialchars($targetCollid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&loanid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($loanArr['loanidentifierown'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' <img src="../../images/edit.png" style="width:1.2em"  /></a> ';
								if(isset($loanArr['isexternal'])) echo '<span style="color:orange">' . $LANG['EXTERNAL_COLL'] . '</span>';
								echo ': ' . ($loanArr['institutioncode'] ? $loanArr['institutioncode'] : ($loanArr['institutionname'] ? $loanArr['institutionname'] : '[no name]'));
								echo ' (' . $loanArr['forwhom'] . ') - ' . ($loanArr['dateclosed']? $LANG['CLOSED'] . ': ' . $loanArr['dateclosed']:'<b>' . $LANG['OPEN'] . '</b>');
								echo ($loanArr['dateclosed'] ? '' : ($loanArr['datedue'] ? $due : ''));
								echo '</li>';
							}
							echo '</ul>';
						}
						else{
							echo '<div style="font-size:120%;margin:20px;">';
							if($searchTerm) echo $LANG['NO_OUTGOING_LOANS'];
							else echo $LANG['NO_OUTGOING_LOANS_REG'];
							echo '</div>';
						}
						?>
					</div>
					<div style="clear:both;">&nbsp;</div>
				</div>
				<div id="loanindiv" style="">
					<div style="float:right;">
						<form name='optionform' action='index.php' method='post'>
							<fieldset>
								<legend><?php echo $LANG['OPTIONS']; ?></legend>
								<div>
									<b><label for="search-loanin"><?php echo $LANG['SEARCH'] ?></label> </b>
									<input type="text" id="search-loanin" autocomplete="off" name="searchterm" value="<?php echo $searchTerm;?>" size="20" />
								</div>
								<div>
									<input type="radio" name="displayall" value="0"<?php echo ($displayAll==0 ? 'checked' : ''); ?> aria-label="<?php echo $LANG['DISP_OUTSTANDING']; ?>" /> <?php echo $LANG['DISP_OUTSTANDING']; ?>
								</div>
								<div>
									<input type="radio" name="displayall" value="1"<?php echo ($displayAll ? 'checked' : ''); ?> aria-label="<?php echo $LANG['DISP_ALL']; ?>" /> <?php echo $LANG['DISP_ALL']; ?>
								</div>
								<div style="float:right;">
									<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
									<input type="hidden" name="tabindex" value="1" />
									<button type="submit" name="formsubmit" value="Refresh List"><?php echo $LANG['REFRESH_LIST']; ?></button>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
					$loansOnWay = $loanManager->getLoanOnWayList();
					$loanInList = $loanManager->getLoanInList($searchTerm,$displayAll);
					?>
					<div id="loaninToggle" style="float:right;margin:10px;">
						<a href="#" onclick="displayNewLoanIn();">
							<img src="../../images/add.png" style="width:1.5em;"  title="<?php echo $LANG['CREATE_NEW_LOAN']; ?>" alt="<?php echo $LANG['CREATE_NEW_LOAN']; ?>" aria-label="<?php echo $LANG['CREATE_A_NEW_LOAN']; ?>" />
						</a>
					</div>
					<div id="newloanindiv" style="display:<?php echo (($loanInList || $loansOnWay || $searchTerm)?'none':'block'); ?>;">
						<form name="newloaninform" action="incoming.php" method="post" onsubmit="return verifyLoanInAddForm(this);">
							<fieldset>
								<legend><?php echo $LANG['NEW_INCOMING_LOAN']; ?></legend>
								<div style="padding-top:4px;float:left;">
									<span>
										<?php echo $LANG['ENTERED_BY']; ?>:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="createdbyborr" maxlength="32" style="width:100px;" value="<?php echo $PARAMS_ARR['un']; ?>" aria-label="<?php echo $LANG['ENTERED_BY']; ?>" />
									</span>
								</div>
								<div style="padding-top:15px;float:right;">
									<span>
										<b><label for="loanidentifierborr"><?php echo $LANG['LOAN_ID']; ?></label>: </b>
										<input type="text" id="loanidentifierborr" autocomplete="off" name="loanidentifierborr" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="" />
									</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
										<?php echo $LANG['SENT_FROM']; ?>:
									</span><br />
									<span>
										<select name="iidowner" style="width:400px;" aria-label="<?php echo $LANG['SENT_FROM'] ?>">
											<option value="0"><?php echo $LANG['SEL_INST']; ?></option>
											<option value="0">------------------------------------------</option>
											<?php
											$instArr = $loanManager->getInstitutionArr();
											foreach($instArr as $k => $v){
												echo '<option value="' . $k . '">' . $v . '</option>';
											}
											?>
										</select>
									</span>
									<span>
										<a href="../misc/institutioneditor.php?emode=1" target="_blank" title="<?php echo $LANG['ADD_NEW_INST']; ?>" aria-label="<?php echo $LANG['ADD_A_NEW_INST']; ?>">
											<img src="../../images/add.png" style="width:1.2em;" alt="<?php echo $LANG['ADD_NEW_INST']; ?>" />
										</a>
									</span>
								</div>
								<div style="clear:both;padding-top:8px;float:right;">
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
									<input type="hidden" name="tabindex" value="1" />
									<input name="formsubmit" type="hidden" value="createLoanIn" />
									<button name="submitbutton" type="submit" value="Create Loan In"><?php echo $LANG['CREATE_LOAN']; ?></button>
								</div>
							</fieldset>
						</form>
					</div>
					<div>
						<?php
						if($loanInList){
							echo '<ul>';
							foreach($loanInList as $k => $loanArr){

								// Loan has a due date and is not closed
								$due = '';
								if ($loanArr['datedue'] && !$loanArr['dateclosed']) {
									// Test whether the loan is overdue
									$overdue = strtotime($loanArr['datedue']) - time() < 0;

									// construct due date string
									$due = ' (<span class="' . ($overdue?'important':'') . '">' . $LANG['DUE'] . ': ' . $loanArr['datedue'] . '</span>)';
								}
								echo '<li>';
								echo '<a href="incoming.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&loanid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($loanArr['loanidentifierborr'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' <img src="../../images/edit.png" style="width:1.2em" /></a>: ';
								echo ($loanArr['institutioncode'] ? $loanArr['institutioncode'] : ($loanArr['institutionname'] ? $loanArr['institutionname'] : '[' . $LANG['NO_NAME'] . ']'));
								echo ' (' . $loanArr['forwhom'] . ') - ' . ($loanArr['dateclosed'] ? $LANG['CLOSED'] . ': ' . $loanArr['dateclosed'] : '<b>' . $LANG['OPEN'] . '</b>');
								echo ($loanArr['dateclosed'] ? '' : ($loanArr['datedue'] ? $due : ''));
								echo '</li>';
							}
							echo '</ul>';
						}
						else{
							echo '<div style="font-size:120%;margin:20px;">';
							if($searchTerm) echo $LANG['NO_LOANS'];
							else echo $LANG['NO_LOANS_RECD'];
							echo '</div>';
						}
						?>
					</div>
					<div style="margin-top:50px">
						<?php
						if($loansOnWay){
							echo '<h3>' . $LANG['LOANS_TO_CHECK_IN'] . '</h3>';
							echo '<ul>';
							foreach($loansOnWay as $k => $loanArr){
								echo '<li>';
								echo '<a href="incoming.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&loanid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
								echo $loanArr['loanidentifierown'];
								echo ' from ' . $loanArr['collectionname'] . '</a>';
								echo '</li>';
							}
							echo '</ul>';
						}
						?>
					</div>
					<div style="clear:both;">&nbsp;</div>
				</div>
			</div>
			<?php
		}
		else{
			if(!$isEditor) echo '<h2>' . $LANG['NOT_AUTH_LOANS'] . '</h2>';
			else echo '<h2>' . $LANG['UNKNOWN_ERROR'] . '</h2>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>
</html>