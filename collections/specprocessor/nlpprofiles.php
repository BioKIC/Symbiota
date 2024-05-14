<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcNlpProfiles.php');
header("Content-Type: text/html; charset=".$CHARSET);

$action = array_key_exists('formsubmit',$_REQUEST) ? $_REQUEST['formsubmit'] : '';
$collId = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$spNlpId = array_key_exists('spnlpid',$_REQUEST)? filter_var($_REQUEST['spnlpid'], FILTER_SANITIZE_NUMBER_INT) : 0;

$nlpManager = new SpecProcNlpProfiles();
//$nlpManager->setCollId($collId);

$isEditor = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))){
 	$isEditor = true;
}

$status = "";
if($isEditor){
	if($action == 'Add New Profile'){
		$status = $nlpManager->addProfile($_REQUEST);
	}
	elseif($action == 'Edit Profile'){
		$status = $nlpManager->editProfile($_REQUEST);
	}
	elseif($action == 'Delete Profile'){
		$status = $nlpManager->deleteProfile($_REQUEST['spnlpid']);
		$spNlpId = 0;
	}
	elseif($action == 'Add New Field Fragment'){
		$status = $nlpManager->addProfileFrag($_REQUEST);
	}
	elseif($action == 'Edit Field Fragment'){
		$status = $nlpManager->editProfileFrag($_REQUEST);
	}
	elseif($action == 'Delete Field Fragment'){
		$status = $nlpManager->deleteProfileFrag($_REQUEST['spnlpfragid']);
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title>Specimen NLP Profile Manager</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script>
			function toggle(target){
				divObj = document.getElementById(target);
				if(divObj != null){
					if(divObj.style.display == "block"){
						divObj.style.display = "none";
					}
					else{
						divObj.style.display = "block";
					}
				}
				else{
					divObjs = document.getElementsByTagName("div");
					divObjLen = divObjs.length;
					for(i = 0; i < divObjLen; i++) {
						var obj = divObjs[i];
						if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
							if(obj.style.display=="none"){
								obj.style.display="inline";
							}
							else {
								obj.style.display="none";
							}
						}
					}
				}
			}

		</script>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<!-- This is inner text! -->
		<div role="main" id="innertext">
			<h1 class="page-heading">Specimen NLP Profile Manager</h1>
			<?php
			if($status){
				?>
				<div style='margin:20px 0px 20px 0px;'>
					<hr/>
					<?php echo $status; ?>
					<hr/>
				</div>
				<?php
			}
			if($isEditor && $SYMB_UID && $collId){
				$profileArr = $nlpManager->getProfileArr($spNlpId);
				if(!$spNlpId){
					?>
					<div style="float:right;margin:10px;" onclick="toggle('addprofilediv');">
						<img src="../../images/add.png" style="width:1.5em;border:0px" />
					</div>
					<div id="addprofilediv" style="display:none;">
						<form name="addprofileform" action="nlpprofiles.php" method="post">
							<fieldset>
								<legend>Add New Profile</legend>
								<div>
									Title:
									<input name="title" type="text" />
								</div>
								<div>
									SQL Fragment:
									<input name="sqlfrag" type="text" style="width:200px;" />
								</div>
								<div>
									Pattern Match:
									<input name="patternmatch" type="text" style="width:200px;" />
								</div>
								<div>
									Notes:
									<input name="notes" type="text" style="width:450px;" />
								</div>
								<div>
									<input name="formsubmit" type="submit" value="Add New Profile" />
									<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
								</div>
							</fieldset>
						</form>
					</div>
					<div>
						<?php
						if($profileArr){
							?>
							<ul>
								<?php
								foreach($profileArr as $k => $vArr){
									?>
									<li>
										<a href="nlpprofiles.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&spnlpid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
											<?php echo $vArr['title']; ?>
										</a>
									</li>
									<?php
								}
								?>
							</ul>
							<?php
						}
						else{
							echo '<div style="margin:20px;font-weight:bold;">There are no NLP profiles for this collection</div>';
						}
						?>
					</div>
					<?php
				}
				else{
					$pArr = array_shift($profileArr);
					?>
					<div style="float:right;margin:10px;" onclick="toggle('editdiv');">
						<img src="../../images/edit.png" style="width:1.3em;border:0px" />
					</div>
					<fieldset>
						<legend><b><?php echo $pArr['title']; ?></b></legend>
						<form name="profileeditform" action="nlpprofiles.php" method="post">
							<div>
								<div style="float:left;width:150px;">
									<b>SQL Fragment:</b>
								</div>
								<div class="editdiv" style="float:left;">
									<?php echo $pArr['sqlfrag']; ?>
								</div>
								<div class="editdiv" style="float:left;display:none;">
									<input name="sqlfrag" type="text" value="<?php echo $pArr['sqlfrag']; ?>" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="float:left;width:150px;">
									<b>Pattern Match:</b>
								</div>
								<div class="editdiv" style="float:left;">
									<?php echo $pArr['patternmatch']; ?>
								</div>
								<div class="editdiv" style="float:left;display:none;">
									<input name="patternmatch" type="text" value="<?php echo $pArr['patternmatch']; ?>" />
								</div>
							</div>
							<div style="clear:both;">
								<div style="float:left;width:150px;">
									<b>Notes:</b>
								</div>
								<div class="editdiv" style="float:left;">
									<?php echo $pArr['notes']; ?>
								</div>
								<div class="editdiv" style="float:left;display:none;">
									<input name="notes" type="text" value="<?php echo $pArr['notes']; ?>" />
								</div>
							</div>
							<div style="clear:both;display:none;">
								<input name="formsubmit" type="submit" value="Edit Profile" />
								<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
								<input name="spnlpid" type="hidden" value="<?php echo $spNlpId; ?>" />
							</div>
						</form>
						<div style="font-weight:bold;clear:both;">Field Parsing Fragments</div>
						<?php
						$profileFragArr = $nlpManager->getProfileFragments($spNlpId);
						?>
						<div id="fragadddiv" style="display:none;">
							<form name="fragaddform" action="nlpprofiles.php" method="post">
								<fieldset>
									<legend>Add New Parsing Fragment</legend>
									<div>
										<b>Field Name:</b>
										<input name="fieldname" type="text" />
									</div>
									<div>
										<b>Pattern Match:</b>
										<input name="patternmatch" type="text" />
									</div>
									<div>
										<b>Notes:</b>
										<input name="notes" type="text" />
									</div>
									<div>
										<b>Sort Sequence:</b>
										<input name="sortseq" type="text" />
									</div>
									<div>
										<input name="formsubmit" type="submit" value="Add Fragment" />
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<input name="spnlpid" type="hidden" value="<?php echo $spNlpId; ?>" />
									</div>
								</fieldset>
							</form>
						</div>
						<ul>
							<?php
							if($profileFragArr){
								foreach($profileFragArr as $k => $vArr){
									?>
									<li>
										<?php echo '<b>'.$vArr['fieldname'].':</b> '.$vArr['patternmatch']; ?>
										<img src="../../images/edit.png" style="width:1.2em" onclick="toggle('frageditdiv-<?php echo $k; ?>')" />
										<div style="margin-left:25px;">
											<?php echo $vArr['notes']; ?>
											<div id="frageditdiv-<?php echo $k; ?>" style="display:none;">
												<form name="frageditform-<?php echo $k; ?>" action="nlpprofiles.php" method="post">
													<fieldset>
														<legend>Editor</legend>
														<div>
															<b>Field Name:</b>
															<input name="fieldname" type="text" value="<?php echo $vArr['fieldname']; ?>" />
														</div>
														<div>
															<b>Pattern Match:</b>
															<input name="patternmatch" type="text" value="<?php echo $vArr['patternmatch']; ?>" />
														</div>
														<div>
															<b>Notes:</b>
															<input name="notes" type="text" value="<?php echo $vArr['notes']; ?>" />
														</div>
														<div>
															<b>Sort Sequence:</b>
															<input name="sortseq" type="text" value="<?php echo $vArr['sortseq']; ?>" />
														</div>
														<div>
															<input name="formsubmit" type="submit" value="Edit Fragment" />
															<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
															<input name="spnlpid" type="hidden" value="<?php echo $spNlpId; ?>" />
															<input name="spnlpfragid" type="hidden" value="<?php echo $k; ?>" />
														</div>
													</fieldset>
												</form>
												<form name="fragdelform-<?php echo $k; ?>" action="nlpprofiles.php" method="post">
													<fieldset>
														<legend>Delete Fragment</legend>
														<div>
															<input name="formsubmit" type="submit" value="Delete Fragment" />
															<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
															<input name="spnlpid" type="hidden" value="<?php echo $spNlpId; ?>" />
															<input name="spnlpfragid" type="hidden" value="<?php echo $k; ?>" />
														</div>
													</fieldset>
												</form>
											</div>
										</div>
									</li>
									<?php
								}
							}
							else{
								echo '<li>There are no parsing fragments</li>';
							}
							?>
						</ul>
					</fieldset>
					<div class="editdiv" style="display:none;">
						<form name="profiledelform" action="nlpprofiles.php" method="post" onsubmit="return confirm('Are you sure you want to delete this profile')">
							<fieldset style="padding:20px;">
								<legend><b>Delete <?php echo $pArr['title']; ?></b></legend>
								<div style="clear:both;">
									<input name="formsubmit" type="submit" value="Delete Profile" />
									<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
									<input name="spnlpid" type="hidden" value="<?php echo $spNlpId; ?>" />
								</div>
							</fieldset>
						</form>

					</div>
					<?php
				}
			}
			else{
				if(!$SYMB_UID){
					?>
					<div style='font-weight:bold;'>
						Please <a href='../../profile/index.php?refurl=<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/collections/specprocessor/index.php'>login</a>!
					</div>
					<?php
				}
				elseif(!$isEditor){
					?>
					<div style='font-weight:bold;'>
						You do not have the necessary rights to access NLP profiles
					</div>
					<?php
				}
				else{
					?>
					<div style='font-weight:bold;color:red;'>
						Unidentified Error
					</div>
					<?php
				}
			}
			?>
		</div>
		<?php
			include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>
