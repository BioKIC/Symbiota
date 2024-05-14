<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/KeyCharAdmin.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../ident/admin/index.php');

$formSubmit = array_key_exists('formsubmit', $_POST) ? $_POST['formsubmit'] : '';
$cid = array_key_exists('cid', $_REQUEST) ? filter_var($_REQUEST['cid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$tabIndex = array_key_exists('tabindex', $_REQUEST) ? filter_var($_REQUEST['tabindex'], FILTER_SANITIZE_NUMBER_INT) : 0;
$langId = array_key_exists('langid', $_REQUEST) ? $_REQUEST['langid'] : '';

$isEditor = false;
if($IS_ADMIN || array_key_exists('KeyAdmin',$USER_RIGHTS)) $isEditor = true;

$keyManager = new KeyCharAdmin();
$keyManager->setLangId($langId);
//$keyManager->setCollId($collId);

$keyManager->setCid($cid);

$statusStr = '';
if($formSubmit && $isEditor){
	if($formSubmit == 'Create'){
		$statusStr = $keyManager->createCharacter($_POST,$PARAMS_ARR['un']);
		$cid = $keyManager->getCid();
	}
	elseif($formSubmit == 'Save Char'){
		$statusStr = $keyManager->editCharacter($_POST);
	}
	elseif($formSubmit == 'Add State'){
		$keyManager->createCharState($_POST,$PARAMS_ARR['un']);
		$tabIndex = 1;
	}
	elseif($formSubmit == 'Save State'){
		$statusStr = $keyManager->editCharState($_POST);
		$tabIndex = 1;
	}
	elseif($formSubmit == 'Delete Char'){
		$statusStr = $keyManager->deleteChar();
		if($statusStr == true) $cid = 0;
	}
	elseif($formSubmit == 'Delete State'){
		$statusStr = $keyManager->deleteCharState($_POST['cs']);
		$tabIndex = 1;
	}
	elseif($formSubmit == 'Upload Image'){
		$statusStr = $keyManager->uploadCsImage($_POST);
		$tabIndex = 1;
	}
	elseif($formSubmit == 'Delete Image'){
		$statusStr = $keyManager->deleteCsImage($_POST['csimgid']);
		$tabIndex = 1;
	}
	elseif($formSubmit == 'Save Taxonomic Relevance'){
		if(isset($_POST['tid']) && $_POST['tid']){
			$statusStr = $keyManager->saveTaxonRelevance($_POST['tid'], $_POST['relation'], $_POST['notes']);
			$tabIndex = 2;
		}
	}
	elseif($formSubmit == 'deltaxon'){
		$statusStr = $keyManager->deleteTaxonRelevance($_POST['tid']);
		$tabIndex = 2;
	}
}

if(!$cid) header('Location: index.php');

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title>Character Admin</title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;

		$(document).ready(function() {
			$('#tabs').tabs({
				active: tabIndex,
				beforeLoad: function( event, ui ) {
					$(ui.panel).html("<p>Loading...</p>");
				}
			});
		});

		function toggleCharState(csId){
			toggle('cs-'+csId+'Div');
			toggle('csplus-'+csId);
		}

		function updateUnits(obj){
			var unitObj = document.getElementById("units");
			if(obj.value == "IN" || obj.value == "RN"){
				unitObj.style.display = "block";
			}
			else{
				unitObj.style.display = "none";
			}
		}

		function validateCharEditForm(f){
			if(f.charname.value == ""){
				alert("Character name must not be null");
				return false;
			}
			if(f.chartype.value == ""){
				alert("Character type must not be null");
				return false;
			}
			if(f.sortsequence.value && !isNumeric(f.sortsequence.value)){
				alert("Sort Sequence can only be a numeric value");
				return false;
			}
			return true;
		}

		function validateStateAddForm(f){
			if(f.charstatename.value == ""){
				alert("Character state must not be null");
				return false;
			}
			if(f.sortsequence.value && !isNumeric(f.sortsequence.value)){
				alert("Sort sequence can only be a numeric value");
				return false;
			}
			return true;
		}

		function validateStateEditForm(f){
			if(f.sortsequence.value && !isNumeric(f.sortsequence.value)){
				alert("Sort Sequence field must be numeric");
				return false;
			}
			return true;
		}

		function verifyStateIllustForm(f){
			if(!f.urlupload.files[0]){
				alert("Select a file to upload");
				return false;
			}
			return true;
		}

		function verifyCharStateDeletion(f){
			var cid = f.cid.value;
			var cs = f.cs.value;

			//Restriction when images are linked
			document.getElementById("delvercsimgspan-"+cs).style.display = "block";
			verifyCharStateImages(cid,cs);

			//Restriction when language definitions are linked
			document.getElementById("delvercslangspan-"+cs).style.display = "block";
			verifyCharStateLang(cid,cs);

			//Restriction when descriptions are linked
			document.getElementById("delverdescrspan-"+cs).style.display = "block";
			verifyDescr(cid,cs);

			f.formsubmit.disabled = false;
		}

		function verifyCharStateImages(cid,cs){
			$.ajax({
				type: "POST",
				url: 'rpc/getcharstateimgcnt.php',
				data: { cidinput: cid, csinput: cs }
			}).done(function( msg ) {
				document.getElementById("delvercsimgspan-"+cs).style.display = "none";
				if(msg > 0){
					document.getElementById("delcsimgfaildiv-"+cs).style.display = "block";
				}
				else{
					document.getElementById("delcsimgappdiv-"+cs).style.display = "block";
				}
			});
		}

		function verifyCharStateLang(cid,cs){
			$.ajax({
				type: "POST",
				url: 'rpc/getcharstatelangcnt.php',
				data: { cidinput: cid, csinput: cs }
			}).done(function( msg ) {
				document.getElementById("delvercslangspan-"+cs).style.display = "none";
				if(msg > 0){
					document.getElementById("delcslangfaildiv-"+cs).style.display = "block";
				}
				else{
					document.getElementById("delcslangappdiv-"+cs).style.display = "block";
				}
			});
		}

		function verifyDescr(cid,cs){
			$.ajax({
				type: "POST",
				url: 'rpc/getdescrcnt.php',
				data: { cidinput: cid, csinput: cs }
			}).done(function( msg ) {
				document.getElementById("delverdescrspan-"+cs).style.display = "none";
				if(msg > 0){
					document.getElementById("deldescrfaildiv-"+cs).style.display = "block";
				}
				else{
					document.getElementById("deldescrappdiv-"+cs).style.display = "block";
				}
			});
		}

		function validateTaxonAddForm(f){
			if(f.tid.value == ''){
				alert("Please select a taxonomic name!");
				return false;
			}
			return true;
		}

		function openHeadingAdmin(){
			newWindow = window.open("headingadmin.php","headingWin","scrollbars=1,toolbar=0,resizable=1,width=800,height=600,left=50,top=50");
			if (newWindow.opener == null) newWindow.opener = self;
		}

		function openGlossaryPopup(glossid){
			var urlStr = "../../glossary/individual.php?glossid="+glossid;
			glossWindow = window.open(urlStr,'popup','toolbar=0,status=1,scrollbars=1,width=900,height=450,left=20,top=20');
			if(glossWindow.opener == null) glossWindow.opener = self;
			return false;
		}
	</script>
	<style type="text/css">
		fieldset{ margin:15px;padding:15px; }
	</style>
</head>
<body>
	<?php
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt;
		<a href='index.php'> <b>Character Management</b></a>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Taxon Character Administration</h1>
		<?php
		if($isEditor){
			if($statusStr){
				?>
				<hr/>
				<div style="margin:15px;color:<?php echo (strpos($statusStr,'SUCCESS')===0?'green':'red'); ?>;">
					<?php echo $statusStr; ?>
				</div>
				<hr/>
				<?php
			}
			$charStateArr = $keyManager->getCharStateArr($cid);
			$charArr = $keyManager->getCharDetails($cid);
			?>
			<div style="font-weight:bold;font-size:150%;margin:15px;"><?php echo $charArr['charname']; ?></div>
			<div id="tabs" style="margin:0px;">
			    <ul>
					<li><a href="#chardetaildiv"><span>Details</span></a></li>
					<li><a href="#charstatediv"><span>Character States</span></a></li>
					<li><a href="taxonomylinkage.php?cid=<?php echo $cid; ?>"><span>Taxonomic Linkages</span></a></li>
					<li><a href="#chardeldiv"><span>Admin</span></a></li>
				</ul>
				<div id="chardetaildiv">
					<form name="chareditform" action="chardetails.php" method="post" onsubmit="return validateCharEditForm(this)">
						<fieldset>
							<legend><b>Character Details</b></legend>
							<div style="padding-top:4px;">
								<b>Character Name</b><br />
								<input type="text" name="charname" maxlength="150" style="width:400px;" value="<?php echo $charArr['charname']; ?>" />
							</div>
							<div style="padding-top:8px;float:left;">
								<div style="float:left;">
									<b>Type</b><br />
									<select id="type" name="chartype" style="width:180px;" onchange="updateUnits(this);">
										<option value="UM">Multi-state</option>
										<option value="IN" <?php echo ($charArr['chartype']=='IN'?'SELECTED':'');?>>Integer</option>
										<option value="RN" <?php echo ($charArr['chartype']=='RN'?'SELECTED':'');?>>Real Number</option>
									</select>
								</div>
								<div id="units" style="display:<?php echo ((($charArr['chartype']=='IN')||($charArr['chartype']=='RN'))?'block':'none');?>;margin-left:15px;float:left;">
									<b>Units</b><br />
									<input type="text" name="units" maxlength="45" style="width:100px;" value="<?php echo $charArr['units']; ?>" title="" />
								</div>
								<div style="margin-left:15px;float:left;">
									<b>Difficulty</b><br />
									<select name="difficultyrank" style="width:100px;">
										<option value="1">Easy</option>
										<option value="2" <?php echo ($charArr['difficultyrank']=='2'?'SELECTED':'');?>>Intermediate</option>
										<option value="3" <?php echo ($charArr['difficultyrank']=='3'?'SELECTED':'');?>>Advanced</option>
										<option value="4" <?php echo ($charArr['difficultyrank']=='4'?'SELECTED':'');?>>Hidden</option>
									</select>
								</div>
								<div style="float:left;margin-left:15px;">
									<b>Grouping</b><br />
									<select name="hid">
										<option value="">Not Assigned</option>
										<option value="">---------------------</option>
										<?php
										$headingArr = $keyManager->getHeadingArr();
										asort($headingArr);
										foreach($headingArr as $k => $v){
											echo '<option value="'.$k.'" '.($k==$charArr['hid']?'SELECTED':'').'>'.$v['name'].'</option>';
										}
										?>
									</select>
									<a href="#" title="Edit Groupings" onclick="openHeadingAdmin(); return false;"><img src="../../images/edit.png" style="width:1em;" alt="Edit Icon" /></a>
								</div>
							</div>
							<div style="padding-top:8px;clear:both;">
								<b>Help URL</b><br />
								<input type="text" name="helpurl" maxlength="500" style="width:80%;" value="<?php echo $charArr['helpurl']; ?>" />
								<?php
								if($charArr['helpurl'] && substr($charArr['helpurl'],0,4) == 'http') echo '<a href="' . $charArr['helpurl'] . '" target="_blank"><img src="../../images/link2.png" style="width:1em" ></a>';
								?>
							</div>
							<?php
							$glossaryArr = $keyManager->getGlossaryList();
							if($glossaryArr){
								?>
								<div style="padding-top:8px;clear:both;">
									<b>Glossary link</b><br />
									<select name="glossid">
										<option value="">------------------------</option>
										<?php
										foreach($glossaryArr as $glossArr){
											foreach($glossArr as $glossID => $gArr){
												echo '<option value="'.$glossID.'" '.($charArr['glossid']==$glossID?'selected':'').'>'.$gArr['term'].' ('.$gArr['lang'].')</option>';
											}
										}
										?>
									</select>
									<?php
									if($charArr['glossid']) echo '<a href="#" onclick="openGlossaryPopup('.$charArr['glossid'].');return false;"><img src="../../images/link2.png" style="width:1em;" /></a>';
									?>
								</div>
								<?php
							}
							?>
							<div style="padding-top:8px;">
								<b>Description</b><br />
								<input type="text" name="description" maxlength="255" style="width:80%;" value="<?php echo $charArr['description']; ?>" />
							</div>
							<div style="padding-top:8px;">
								<b>Notes</b><br />
								<input type="text" name="notes" maxlength="255" style="width:80%;" value="<?php echo $charArr['notes']; ?>" />
							</div>
							<div style="padding-top:8px;">
								<b>Sort Sequence</b><br />
								<input type="text" name="sortsequence" style="width:80px;" value="<?php echo $charArr['sortsequence']; ?>" />
							</div>
							<div style="width:100%;padding-top:6px;">
								<div style="float:left;">
									<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
									<button name="formsubmit" type="submit" value="Save Char">Save</button>
								</div>
								<div style="float:right;">
									Entered By:
									<input type="text" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $charArr['enteredby']; ?>" onchange=" " disabled />
								</div>
							</div>
						</fieldset>
					</form>
				</div>
				<div id="charstatediv">
					<div style="float:right;margin:10px;">
						<a href="#" title="Create New Character State" onclick="toggle('newstatediv');">
							<img src="../../images/add.png" style="width:1.5em;" alt="Create New Character State" />
						</a>
					</div>
					<div id="newstatediv" style="display:<?php echo ($charStateArr?'none':'block');?>;">
						<form name="stateaddform" action="chardetails.php" method="post" onsubmit="return validateStateAddForm(this)">
							<fieldset>
								<legend><b>Add Character State</b></legend>
								<div style="padding-top:4px;">
									<b>Character State Name</b><br />
									<input type="text" name="charstatename" maxlength="255" style="width:400px;" />
								</div>
								<div style="padding-top:4px;">
									<b>Description</b><br />
									<input type="text" name="description" maxlength="255" style="width:80%;" />
								</div>
								<?php
								if($glossaryArr){
									?>
									<div style="padding-top:8px;clear:both;">
										<b>Glossary link</b><br />
										<select name="glossid">
											<option value="">------------------------</option>
											<?php
											foreach($glossaryArr as $glossArr){
												foreach($glossArr as $glossID => $gArr){
													echo '<option value="'.$glossID.'">'.$gArr['term'].' ('.$gArr['lang'].')</option>';
												}
											}
											?>
										</select>
									</div>
									<?php
								}
								?>
								<div style="padding-top:4px;">
									<b>Notes</b><br />
									<input type="text" name="notes" style="width:80%;" />
								</div>
								<div style="padding-top:4px;">
									<b>Sort Sequence</b><br />
									<input type="text" name="sortsequence" style="width:80px" />
								</div>
								<div style="width:100%;padding-top:6px;">
									<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
									<button name="formsubmit" type="submit" value="Add State">Add Character State</button>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
					if($charStateArr){
						echo '<h3>Character States</h3>';
						foreach($charStateArr as $cs => $stateArr){
							?>
							<div>
								<div id="csplus-<?php echo $cs; ?>" style="margin:5px;">
									<a href="#" onclick="toggleCharState(<?php echo $cs; ?>);return false;">
										<img src="../../images/plus.png" style="width:1em;" />
										<?php echo $stateArr['charstatename']; ?>
									</a>
								</div>
								<div id="<?php echo 'cs-'.$cs.'Div'; ?>" style="display:none;">
									<div style="margin:5px;">
										<a href="#" onclick="toggleCharState(<?php echo $cs; ?>);return false;">
											<img src="../../images/minus.png" style="width:1em;" />
											<?php echo $stateArr['charstatename']; ?>
										</a>
									</div>
									<form name="stateeditform-<?php echo $cs; ?>" action="chardetails.php" method="post" onsubmit="return validateStateEditForm(this)">
										<fieldset>
											<legend><b>Character State Details</b></legend>
											<div>
												<b>Character State Name</b><br />
												<input type="text" name="charstatename" maxlength="255" style="width:300px;" value="<?php echo $stateArr['charstatename']; ?>" />
											</div>
											<div style="padding-top:2px;">
												<b>Description</b><br />
												<input type="text" name="description" maxlength="255" style="width:80%;" value="<?php echo $stateArr['description']; ?>"/>
											</div>
											<?php
											if($glossaryArr){
												?>
												<div style="padding-top:8px;clear:both;">
													<b>Glossary link</b><br />
													<select name="glossid">
														<option value="">------------------------</option>
														<?php
														foreach($glossaryArr as $glossArr){
															foreach($glossArr as $glossID => $gArr){
																echo '<option value="'.$glossID.'" '.($stateArr['glossid']==$glossID?'selected':'').'>'.$gArr['term'].' ('.$gArr['lang'].')</option>';
															}
														}
														?>
													</select>
													<?php
													if($stateArr['glossid']) echo '<a href="#" onclick="openGlossaryPopup('.$stateArr['glossid'].');return false;"><img src="../../images/link2.png" style="width:1em" /></a>';
													?>
												</div>
												<?php
											}
											?>
											<div style="padding-top:2px;">
												<b>Notes</b><br />
												<input type="text" name="notes" style="width:80%;" value="<?php echo $stateArr['notes']; ?>" />
											</div>
											<div style="padding-top:2px;">
												<div style="float:right;">
													Entered By:<br/>
													<input type="text" name="enteredby" value="<?php echo $stateArr['enteredby']; ?>" disabled />
												</div>
												<div>
													<b>Sort Sequence</b><br />
													<input type="text" name="sortsequence" value="<?php echo $stateArr['sortsequence']; ?>" style="width:80px" />
												</div>
											</div>
											<div style="width:100%;margin:20px 0px 10px 20px;">
												<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
												<input name="cs" type="hidden" value="<?php echo $cs; ?>" />
												<button name="formsubmit" type="submit" value="Save State">Save</button>
											</div>
										</fieldset>
									</form>
									<fieldset>
										<legend><b>Illustration</b></legend>
										<?php
										if(isset($stateArr['csimgid'])){
											?>
											<div style="padding-top:2px;">
												<a href="<?php echo $stateArr['url']; ?>" target="_blank"><img src="<?php echo $stateArr['url']; ?>" style="width:200px;" /></a>
											</div>
											<form name="stateillustdelform-<?php echo $stateArr['csimgid']; ?>" action="chardetails.php" method="post" onsubmit="return verifyStateIllustDelForm(this)" >
												<div style="margin:10px;">
													<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
													<input name="cs" type="hidden" value="<?php echo $cs; ?>" />
													<input name="csimgid" type="hidden" value="<?php echo $stateArr['csimgid']; ?>" />
													<button name="formsubmit" type="submit" value="Delete Image">Delete Image</button>
												</div>
											</form>
											<?php
										}
										else{
											?>
											<form name="stateillustform-<?php echo $cs; ?>" action="chardetails.php" method="post" enctype="multipart/form-data" onsubmit="return verifyStateIllustForm(this)" >
												<div style="padding-top:2px;">
													<b>File Upload: </b>
													<input name="urlupload" type="file" size="50" />
													<input name="MAX_FILE_SIZE" type="hidden" value="1000000" />
												</div>
												<div style="padding-top:2px;">
													<b>Notes:</b>
													<input name="notes" type="text" style="width:90%" />
												</div>
												<div style="padding-top:2px;">
													<b>Sort:</b>
													<input name="sortsequence" type="text" />
												</div>
												<div style="padding-top:2px;">
													<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
													<input name="cs" type="hidden" value="<?php echo $cs; ?>" />
													<button name="formsubmit" type="submit" value="Upload Image">Upload Image</button>
												</div>
											</form>
											<?php
										}
										?>
									</fieldset>
									<form name="statedelform-<?php echo $cs; ?>" action="chardetails.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this character state?')">
										<fieldset>
											<legend><b>Delete Character State</b></legend>
											Record first needs to be evaluated before it can be deleted from the system.
											The evaluation ensures that the deletion will not interfer with
											the integrity of linked data.
											<div style="margin:15px;">
												<input name="verifycsdelete" type="button" value="Evaluate record for deletion" onclick="verifyCharStateDeletion(this.form);return false;" />
											</div>
											<div id="delverimgdiv" style="margin:15px;">
												<b>Image Links: </b>
												<span id="delvercsimgspan-<?php echo $cs; ?>" style="color:orange;display:none;">checking image links...</span>
												<div id="delcsimgfaildiv-<?php echo $cs; ?>" style="display:none;style:0px 10px 10px 10px;">
													<span style="color:red;">Warning:</span>
													One or more images are linked to this charcter state.
													Deleting this character state will also permanently remove these images.
												</div>
												<div id="delcsimgappdiv-<?php echo $cs; ?>" style="display:none;">
													<span style="color:green;">Approved for deletion.</span>
													No images are directly associated with this character state.
												</div>
											</div>
											<div id="delverlangdiv" style="margin:15px;">
												<b>Language Links: </b>
												<span id="delvercslangspan-<?php echo $cs; ?>" style="color:orange;display:none;">checking language links...</span>
												<div id="delcslangfaildiv-<?php echo $cs; ?>" style="display:none;style:0px 10px 10px 10px;">
													<span style="color:red;">Warning:</span>
													Charcter state has links to langauge records.
													Deleting this character state will also permanently remove this data.
												</div>
												<div id="delcslangappdiv-<?php echo $cs; ?>" style="display:none;">
													<span style="color:green;">Approved for deletion.</span>
													No langage mappings are directly associated with this character state.
												</div>
											</div>
											<div id="delverdescrdiv" style="margin:15px;">
												<b>Description Links: </b>
												<span id="delverdescrspan-<?php echo $cs; ?>" style="color:orange;display:none;">checking description links...</span>
												<div id="deldescrfaildiv-<?php echo $cs; ?>" style="display:none;style:0px 10px 10px 10px;">
													<span style="color:red;">Warning:</span>
													One or more descriptions are linked to this charcter state.
													Delete this character state will also permanently remove these descriptions.
												</div>
												<div id="deldescrappdiv-<?php echo $cs; ?>" style="display:none;">
													<span style="color:green;">Approved for deletion.</span>
													No descriptions are directly associated with this character state.
												</div>
											</div>
											<div style="margin:15px;">
												<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
												<input name="cs" type="hidden" value="<?php echo $cs; ?>" />
												<input name="formsubmit" type="submit" value="Delete State" disabled />
											</div>
										</fieldset>
									</form>
								</div>
							</div>
							<?php
						}
					}
					?>
				</div>
				<div id="chardeldiv">
					<form name="delcharform" action="chardetails.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this character?')">
						<fieldset style="width:700px;">
							<legend><b>Delete Character</b></legend>
							<?php
							if($charStateArr){
								echo '<div style="margin-bottom:15px;">';
								echo 'Character cannot be deleted until all character states are removed';
								echo '</div>';
							}
							?>
							<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
							<button name="formsubmit" type="submit" value="Delete Char" <?php if($charStateArr) echo 'DISABLED'; ?>>Delete</button>
						</fieldset>
					</form>
				</div>
			</div>
			<?php
		}
		else{
			if(!$isEditor){
				echo '<h2>You are not authorized to add characters</h2>';
			}
			else{
				echo '<h2>ERROR: unknown error, please contact system administrator</h2>';
			}
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>