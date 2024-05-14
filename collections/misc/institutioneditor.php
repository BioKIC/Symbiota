<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/InstitutionManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/misc/institutioneditor.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/misc/institutioneditor.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/misc/institutioneditor.en.php');

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/institutioneditor.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

//since integers are sanizated here, we don't need to resanitize using htmlspecialchars
$iid = array_key_exists('iid', $_REQUEST) ? filter_var($_REQUEST['iid'], FILTER_SANITIZE_NUMBER_INT) : '';
$targetCollid = array_key_exists('targetcollid', $_REQUEST) ? filter_var($_REQUEST['targetcollid'], FILTER_SANITIZE_NUMBER_INT) : '';

//emode is alway 0 or 1, thus we can explicit set to 1 whenever emode is true. No sanitation needed because value is not set as input value, which could be anything.
$eMode = !empty($_REQUEST['emode']) ? 1 : 0;

//$instCodeDefault this does need to be sanitized, but better to do further down where it is used as output. But OK to sanitize here, and maybe better if used numerous times as output.
//But not if it is set within the class to be used within an SQL statement. Then it's needs to be sanitized beyond the statements that send it to the class
$instCodeDefault = array_key_exists('instcode',$_REQUEST) ? $_REQUEST['instcode'] : '';

// $formSubmit does not have to be sanitized because it's only used within condition statements and not output to page
$formSubmit = array_key_exists('formsubmit', $_POST) ? $_POST['formsubmit'] : '';

$instManager = new InstitutionManager();

// text with $fullCollList is sanitized using cleanOutStr (including htmlspecialchars) within getCollectionList function.
// The iid value does not have to be sanitized because it is defined as an int, but CheckMarx probably won't know this a might have a problem
$fullCollList = $instManager->getCollectionList();
$instManager->setInstitutionId($iid);

//Create a list of collection that are linked to this institutions
$collList = array();
foreach($fullCollList as $k => $v){
	//Values were already sanitized when $fullCollList was obtained from DB, thus we should not double sanitized here (e.g. &amp; becomes &amp;amp;
	if($v['iid'] == $iid) $collList[$k] = $v['name'];
}

$editorCode = 0;
$statusStr = '';
if($IS_ADMIN){
	$editorCode = 3;
}
elseif(array_key_exists('CollAdmin', $USER_RIGHTS)){
	$editorCode = 1;
	if($collList && array_intersect($USER_RIGHTS['CollAdmin'], array_keys($collList))){
		$editorCode = 2;
	}
}
if($editorCode){
	if($formSubmit == 'Add Institution'){
		if($instManager->insertInstitution($_POST)){
			// Can only be set to int within class. Let's see if checkmarx knows this.
			$iid = $instManager->getInstitutionId();
			$statusStr = 'SUCCESS, institution added!';
			if($targetCollid) header('Location: ../misc/collprofiles.php?collid=' . $targetCollid);
		}
		else{
			$statusStr = 'ERROR creating institution: ' . $instManager->getErrorMessage();
		}
	}
	else{
		if($editorCode > 1){
			if($formSubmit == 'Update Institution Address'){
				if($instManager->updateInstitution($_POST)){
					if($targetCollid) header('Location: ../misc/collprofiles.php?collid=' . $targetCollid);
				}
				else{
					$statusStr = 'ERROR updating institutions record: ' . $instManager->getErrorMessage();
				}
			}
			elseif(!empty($_POST['deliid'])){
				if($instManager->deleteInstitution($_POST['deliid'])){
					$statusStr = 'SUCCESS! Institution deleted.';
					$iid = 0;
				}
				else{
					$statusStr = 'Unable to delete: ';
					$errorStr = $instManager->getErrorMessage();
					if($errorStr == 'LINKED_COLLECTIONS'){
						$statusStr .= 'following collections need to be unlinked before deletion is allowed';
						$statusStr .= '<ul><li>' . implode('</li><li>', $instManager->getWarningArr()) . '</li></ul>';
					}
					elseif($errorStr == 'LINKED_LOANS'){
						$statusStr .= 'institution is linked to ' . count($instManager->getWarningArr()) . ' loans';
					}
					else{
						$errorStr = 'ERROR deleting institution: ' . $errorStr;
					}
				}
			}
			elseif($formSubmit == 'Add Collection'){
				if($_POST['addcollid'] && is_numeric($_POST['addcollid'])){
					if($instManager->updateCollectionLink($_POST['addcollid'], $iid)){
						$collList[$_POST['addcollid']] = $fullCollList[$_POST['addcollid']]['name'];
					}
					else{
						$statusStr = 'ERROR linking collection to institution: ' . $instManager->getErrorMessage();
					}
				}
			}
			elseif(isset($_GET['removecollid'])){
				if($instManager->updateCollectionLink($_GET['removecollid'], null)){
					$statusStr = 'SUCCESS! Institution removed';
					unset($collList[$_GET['removecollid']]);
				}
				else{
					$statusStr = 'ERROR deleting institution: ' . $instManager->getErrorMessage();
				}
			}
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> <?php echo $LANG['INSTITUTION_EDITOR']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../../js/symb/collections.grscicoll.js?ver=2" type="text/javascript"></script>
	<script>
		function toggle(target){
			var tDiv = document.getElementById(target);
			if(tDiv != null){
				if(tDiv.style.display=="none"){
					tDiv.style.display="block";
				}
				else {
					tDiv.style.display="none";
				}
			}
			else{
				var divs = document.getElementsByTagName("div");
				for (var i = 0; i < divs.length; i++) {
				var divObj = divs[i];
					if(divObj.className == target){
						if(divObj.style.display=="none"){
							divObj.style.display="block";
						}
						else {
							divObj.style.display="none";
						}
					}
				}
			}
		}

		function validateAddCollectionForm(f){
			if(f.addcollid.value == ""){
				alert("<?php echo $LANG['SELECT_COLLECTION']; ?>");
				return false;
			}
			return true;
		}

	</script>
</head>
<body>
<?php
include($SERVER_ROOT.'/includes/header.php');
?>
<div class='navpath'>
	<a href='../../index.php'><?php echo $LANG['HOME']; ?></a> &gt;&gt;
	<?php
	if($targetCollid && !empty($collList[$targetCollid])){
		echo '<a href="../misc/collprofiles.php?collid=' . $targetCollid . '&emode=1">' . $collList[$targetCollid] . ' ' . $LANG['MANAGEMENT'] . '</a> &gt;&gt;';
	}
	else{
		echo '<a href="institutioneditor.php">' . $LANG['FULL_ADDRESS_LIST'] . '</a> &gt;&gt;';
	}
	?>
	<b><?php echo $LANG['INSTITUTION_EDITOR']; ?></b>
</div>
<!-- This is inner text! -->
<div role="main" id="innertext">
	<h1 class="page-heading"><?php echo $LANG['INSTITUTION_EDITOR']; ?></h1>
	<div id="dialog" title="" style="display: none;">
		<div id="dialogmsg"></div>
		<select id="getresult">
		</select>
	</div>
	<?php
	if($statusStr){
		?>
		<hr />
		<div style="margin:20px;color:<?php echo (substr($statusStr,0,5)=='ERROR'?'red':'green'); ?>;">
			<?php //$statusStr is only output from db engine, thus not a big threat, but Checkmarx won't know that, and better safe than sorry ?>
			<?= htmlspecialchars($statusStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>
		</div>
		<hr />
		<?php
	}
	if($iid){
		if($instArr = $instManager->getInstitutionData()){
			//Cleaning this in function when data is harvested is fine, but only if data is used as output by all pages.
			//If data from fuction is used by another class/page to be reinserted into data, than that would be a problem, but this is rare.
			$instArr = $instManager->cleanOutArray($instArr);
			?>
			<div style="float:right;">
				<a href="institutioneditor.php">
					<img src="<?php echo $CLIENT_ROOT;?>/images/toparent.png" style="width:1.2em;border:0px;" title="<?php echo $LANG['RETURN_TO_INST']; ?>" />
				</a>
				<?php
				if($editorCode > 1){
					?>
					<a href="#" onclick="toggle('editdiv');">
						<img src="<?php echo $CLIENT_ROOT;?>/images/edit.png" style="width:1.2em;border:0px;" title="<?php echo $LANG['EDIT_INST']; ?>" />
					</a>
					<?php
				}
				?>
			</div>
			<div style="clear:both;">
				<form id="insteditform" name="insteditform" action="institutioneditor.php" method="post">
					<fieldset style="padding:20px;">
						<legend><b><?php echo $LANG['ADDRESS_DETAILS']; ?></b></legend>
						<div style="position:relative;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['INSTITUTION_CODE']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['institutioncode']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="institutioncode" type="text" value="<?php echo $instArr['institutioncode']; ?>" />
								<button name="getgrscicoll" type="button" value="Update from GrSciColl" onClick="grscicoll('insteditform')"><?php echo $LANG['UPDATE_GRSCICOLL']; ?></button>
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['INSTITUTION_NAME']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['institutionname']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="institutionname" type="text" value="<?php echo $instArr['institutionname']; ?>" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['INSTITUTION_NAME_TWO']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['institutionname2']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="institutionname2" type="text" value="<?php echo $instArr['institutionname2']; ?>" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['ADDRESS']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['address1']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="address1" type="text" value="<?php echo $instArr['address1']; ?>" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['ADDRESS_TWO']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['address2']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="address2" type="text" value="<?php echo $instArr['address2']; ?>" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['CITY']; ?>City:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['city']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="city" type="text" value="<?php echo $instArr['city']; ?>" style="width:100px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['STATE_PROVINCE']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['stateprovince']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="stateprovince" type="text" value="<?php echo $instArr['stateprovince']; ?>" style="width:100px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['POSTAL_CODE']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['postalcode']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="postalcode" type="text" value="<?php echo $instArr['postalcode']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['COUNTRY']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['country']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="country" type="text" value="<?php echo $instArr['country']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['PHONE']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['phone']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="phone" type="text" value="<?php echo $instArr['phone']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['CONTACT']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['contact']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="contact" type="text" value="<?php echo $instArr['contact']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['EMAIL']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['email']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="email" type="text" value="<?php echo $instArr['email']; ?>" style="width:150px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['URL']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<a href="<?php echo $instArr['url']; ?>" target="_blank">
									<?php echo $instArr['url']; ?>
								</a>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="url" type="text" value="<?php echo $instArr['url']; ?>" style="width:400px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['NOTES']; ?>:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['notes']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="notes" type="text" value="<?php echo $instArr['notes']; ?>" style="width:400px" />
							</div>
						</div>
						<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;clear:both;margin:30px 0px 0px 20px;">
							<button name="formsubmit" type="submit" value="Update Institution Address" ><?php echo $LANG['UPDATE_INST_ADDRESS']; ?></button>
							<input name="iid" type="hidden" value="<?php echo $iid; ?>" />
							<input name="targetcollid" type="hidden" value="<?php echo $targetCollid; ?>" />
						</div>
					</fieldset>
				</form>
				<div style="clear:both;">
					<fieldset style="padding:20px;">
						<legend><b><?php echo $LANG['COLL_LINKED_TO _INST_ADDRESS']; ?></b></legend>
						<div>
							<?php
							if($collList){
								//$collName is cleaned within function that built data array
								foreach($collList as $id => $collName){
									echo '<div style="margin:5px;font-weight:bold;clear:both;height:15px;">';
									echo '<div style="float:left;"><a href="../misc/collprofiles.php?collid=' . $id . '">' . $collName . '</a></div> ';
									if($editorCode == 3 || in_array($id,$USER_RIGHTS["CollAdmin"]))
										echo ' <div class="editdiv" style="margin-left:10px;display:'.($eMode?'':'none').'"><a href="institutioneditor.php?iid=' . $iid . '&removecollid=' . $id . '"><img src="../../images/del.png" style="width:1em;"/></a></div>';
									echo '</div>';
								}
							}
							else{
								echo '<div style="margin:25px;"><b>' . $LANG['INST_NOT_LINKED'] . '</b></div>';
							}
							?>
						</div>
						<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
							<div style="margin:15px;clear:both;">* <?php echo $LANG['CLICK_ON_RED']; ?></div>
							<?php
							//Don't show collection that already linked and only show one that user can admin
							$addList = array();
							//$fullCollList cleaned when created
							foreach($fullCollList as $collid => $collArr){
								if($collArr['iid'] != $iid){
									if($IS_ADMIN || (isset($USER_RIGHTS["CollAdmin"]) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
										$addList[$collid] = $collArr;
									}
								}
							}
							if($addList){
								?>
								<hr />
								<form name="addcollectionform" method="post" action="institutioneditor.php" onsubmit="return validateAddCollectionForm(this)">
									<select name="addcollid" style="width:400px;">
										<option value=""><?php echo $LANG['SELECT_COLL_TO_ADD']; ?></option>
										<option value="">------------------------------------</option>
										<?php
										foreach($addList as $collid => $collArr){
											echo '<option value="'.$collid.'">'.$collArr['name'].'</option>';
										}
										?>
									</select>
									<input name="iid" type="hidden" value="<?php echo $iid; ?>" />
									<button name="formsubmit" type="submit" value="Add Collection" ><?php echo $LANG['ADD_COLLECTION']; ?></button>
								</form>
								<?php
							}
							?>
						</div>
					</fieldset>
					<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
						<fieldset style="padding:20px;">
							<legend><b><?php echo $LANG['DEL_INSTITUTION']; ?></b></legend>
							<form name="instdelform" action="institutioneditor.php" method="post" onsubmit="return confirm('<?php echo $LANG['WANT_TO_DELETE_INST']; ?>')">
								<div style="position:relative;clear:both;">
									<button class="button-danger" name="formsubmit" type="submit" value="Delete Institution" <?php if($collList) echo 'disabled'; ?> ><?php echo $LANG['DEL_INSTITUTION']; ?></button>
									<input name="deliid" type="hidden" value="<?php echo $iid; ?>" />
									<?php
									if($collList) echo '<div style="margin:15px;color:red;">' . $LANG['DELETION_OF_ADDRESS'] . '</div>';
									?>
								</div>
							</form>
						</fieldset>
					</div>
				</div>
			</div>
			<?php
		}
	}
	else{
		if($editorCode){
			?>
			<div style="float:right;">
				<a href="#" onclick="toggle('instadddiv');">
					<img src="<?php echo $CLIENT_ROOT;?>/images/add.png" style="width:1.5em;border:0px;" title="<?php echo $LANG['ADD_NEW_INST']; ?>" />
				</a>
			</div>
			<div id="instadddiv" style="display:<?php echo ($eMode?'block':'none'); ?>;margin-bottom:8px;">
				<form id="instaddform" name="instaddform" action="institutioneditor.php" method="post">
					<fieldset style="padding:20px;">
						<legend><b><?php echo $LANG['ADD_NEW_INSTITUTION']; ?></b></legend>
						<div style="position:relative;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['INSTITUTION_CODE']; ?>:
							</div>
							<div>
								<input name="institutioncode" type="text" value="<?= htmlspecialchars($instCodeDefault, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>" />
								<button name="getgrscicoll" type="button" value="Get data from GrSciColl" onClick="grscicoll('instaddform')"><?php echo $LANG['GET_DATA_FROM_GRSCICOLL']; ?></button>
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['INSTITUTION_NAME']; ?>:
							</div>
							<div>
								<input name="institutionname" type="text" value="" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['INSTITUTION_NAME_TWO']; ?>:

							</div>
							<div>
								<input name="institutionname2" type="text" value="" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['ADDRESS']; ?>:
							</div>
							<div>
								<input name="address1" type="text" value="" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['ADDRESS_TWO']; ?>:
							</div>
							<div>
								<input name="address2" type="text" value="" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['CITY']; ?>:
							</div>
							<div>
								<input name="city" type="text" value="" style="width:100px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['STATE_PROVINCE']; ?>:
							</div>
							<div>
								<input name="stateprovince" type="text" value="" style="width:100px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['POSTAL_CODE']; ?>:
							</div>
							<div>
								<input name="postalcode" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['COUNTRY']; ?>:
							</div>
							<div>
								<input name="country" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['PHONE']; ?>:
							</div>
							<div>
								<input name="phone" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['CONTACT']; ?>:
							</div>
							<div>
								<input name="contact" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['EMAIL']; ?>:
							</div>
							<div>
								<input name="email" type="text" value="" style="width:150px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['URL']; ?>:
							</div>
							<div>
								<input name="url" type="text" value="" style="width:400px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['NOTES']; ?>:
							</div>
							<div>
								<input name="notes" type="text" value="" style="width:400px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:155px;font-weight:bold;">
								<?php echo $LANG['LINK_TO']; ?>:
							</div>
							<div>
								<select name="targetcollid" style="width:400px;">
									<option value=""><?php echo $LANG['LEAVE_ORPHANED']; ?></option>
									<option value="">--------------------------------------</option>
									<?php
									foreach($fullCollList as $collid => $collArr){
										//Don't show collection that already linked and only show one that user can admin
										if($collArr['iid'] && ($IS_ADMIN || ($USER_RIGHTS["CollAdmin"] && in_array($collid,$USER_RIGHTS["CollAdmin"])))){
											echo '<option value="'.$collid.'"'.($collid == $targetCollid?'SELECTED':'').'>'.$collArr['name'].'</option>';
										}
									}
									?>
								</select>
							</div>
						</div>
						<div style="margin:20px;clear:both;">
							<button name="formsubmit" type="submit" value="Add Institution" ><?php echo $LANG['ADD_INST']; ?></button>
						</div>
					</fieldset>
				</form>
			</div>
			<?php
			if(!$eMode){
				?>
				<div style="padding-left:10px;">
					<h2><?php echo $LANG['SELECT_INST_FROM_LIST']; ?></h2>
					<ul>
						<?php
						$instList = $instManager->getInstitutionList();
						if($instList){
							foreach($instList as $iid => $iArr){
								echo '<li><a href="institutioneditor.php?iid='.$iid.'">';
								echo $iArr['institutionname'].' ('.$iArr['institutioncode'].')';
								if($editorCode == 3 || array_intersect(explode(',',$iArr['collid']),$USER_RIGHTS["CollAdmin"])){
									echo ' <a href="institutioneditor.php?emode=1&iid=' . $iid . '"><img src="' . $CLIENT_ROOT . '/images/edit.png" style="width:1.2em;" /></a>';
								}
								echo '</a></li>';
							}
						}
						else{
							echo "<div>" . $LANG['NO_RIGHTS_TO_EDIT_INST'] . "</div>";
						}
						?>
					</ul>
				</div>
				<?php
			}
		}
		else{
			echo "<div>" . $LANG['NEED_AMDINISTRATIVE_USER_RIGHTS'] . "</div>";
		}
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>
