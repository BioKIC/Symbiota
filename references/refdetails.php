<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ReferenceManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$refId = array_key_exists('refid',$_REQUEST)?$_REQUEST['refid']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$refManager = new ReferenceManager();
$refArr = '';

$statusStr = '';
if($formSubmit){
	if($formSubmit == 'Create Reference'){
		$statusStr = $refManager->createReference($_POST);
		$refId = $refManager->getRefId();
	}
	elseif($formSubmit == 'Edit Reference'){
		if($_POST['refGroup'] == 1){
			$statusStr = $refManager->editBookReference($_POST);
		}
		elseif($_POST['refGroup'] == 2){
			$statusStr = $refManager->editPerReference($_POST);
		}
		else{
			$statusStr = $refManager->editReference($_POST);
		}
	}
}
$refGroup = 0;
$refRank = 0;
$parentChild = 0;
if($refId){
	$refArr = $refManager->getRefArr($refId);
	$childArr = $refManager->getChildArr($refId);
	$authArr = $refManager->getRefAuthArr($refId);
	$fieldArr = $refManager->getRefTypeFieldArr($refArr["ReferenceTypeId"]);
	$refChecklistArr = $refManager->getRefChecklistArr($refId);
	$refCollArr = $refManager->getRefCollArr($refId);
	$refOccArr = $refManager->getRefOccArr($refId);
	$refTaxaArr = $refManager->getRefTaxaArr($refId);
	if($refArr["ReferenceTypeId"] == 3 || $refArr["ReferenceTypeId"] == 4 || $refArr["ReferenceTypeId"] == 6 || $refArr["ReferenceTypeId"] == 27){
		$refGroup = 1;
		$parentChild = 1;
		if($refArr["ReferenceTypeId"] == 4){
			$refRank = 1;
		}
		if($refArr["ReferenceTypeId"] == 3 || $refArr["ReferenceTypeId"] == 6){
			$refRank = 2;
		}
		if($refArr["ReferenceTypeId"] == 27){
			$refRank = 3;
		}
	}
	if($refArr["ReferenceTypeId"] == 2 || $refArr["ReferenceTypeId"] == 7 || $refArr["ReferenceTypeId"] == 8 || $refArr["ReferenceTypeId"] == 30){
		$refGroup = 2;
		$parentChild = 1;
		if($refArr["ReferenceTypeId"] == 2 || $refArr["ReferenceTypeId"] == 7 || $refArr["ReferenceTypeId"] == 8){
			$refRank = 1;
		}
		if($refArr["ReferenceTypeId"] == 30){
			$refRank = 2;
		}
	}
}
else{
	header("Location: index.php");
}

?>

<!DOCTYPE HTML>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo $DEFAULT_TITLE; ?> Reference Management</title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<style type="text/css">
		#tabs a{
			outline-color: transparent;
			font-size: 0.9rem;
			font-weight: normal;
		}
	</style>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="../js/symb/references.index.js"></script>
	<script type="text/javascript">
		var refid = <?php echo $refId; ?>;
		var parentChild = false;

		<?php
		if($parentChild){
			echo 'parentChild = true;';
		}
		?>
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($reference_indexMenu)?$reference_indexMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($reference_indexCrumbs)){
		if($reference_indexCrumbs){
			?>
			<div class='navpath'>
				<a href='../index.php'>Home</a> &gt;&gt;
				<?php echo $reference_indexCrumbs; ?>
				<a href='index.php'> <b>Reference Management</b></a>
			</div>
			<?php
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../index.php'>Home</a> &gt;&gt;
			<a href='index.php'> <b>Reference Management</b></a>
		</div>
		<?php
	}
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Reference Management</h1>
		<?php
		if($SYMB_UID){
			if($statusStr){
				?>
				<div style="margin:15px;color:red;">
					<?php echo $statusStr; ?>
				</div>
				<?php
			}
			?>
			<div id="tabs" style="margin:0px;">
				<ul>
					<li><a href="#refdetaildiv">Reference Details</a></li>
					<li><a href="#reflinksdiv">Links</a></li>
					<li><a href="#refadmindiv">Admin</a></li>
				</ul>

				<div id="refdetaildiv" style="">
					<div style="width:300px;float:right;">
						<form name='authorform' id='authorform' action='index.php' method='post'>
							<fieldset>
								<legend><b>Authors</b></legend>
								<div>
									<div>
										<b>Add Author By Last Name: </b>
									</div>
									<div>
										<input type="text" name="addauthorsearch" id="addauthorsearch" style="width:200px;" value="" size="20" />
										<input id="refauthorid" name="refauthorid" type="hidden" value="" />
									</div>
								</div>
								<hr />
								<div id="authorlistdiv">
									<?php
									if($authArr){
										echo '<ul>';
										foreach($authArr as $k => $v){
											echo '<li>';
											echo '<a href="authoreditor.php?authid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($v, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
											echo ' <input type="image" style="width:1.3em;margin-left:5px;" src="../images/del.png" onclick="deleteRefAuthor('.$k.');" title="Delete author">';
											echo '</li>';
										}
										echo '</ul>';
									}
									else{
										echo '<div><b>There are currently no authors connected with this reference.</b></div>';
									}
									?>
								</div>
							</fieldset>
						</form>
					</div>
					<div id="refdetails" style="overflow:auto;">
						<form name="referenceeditform" id="referenceeditform" action="refdetails.php" method="post" onsubmit="return verifyEditRefForm(this.form);">
							<div style="width:400px;">
								<div style="width:200px;padding-top:6px;float:left;">
									<div>
										<b>Reference Type: </b>
									</div>
									<div>
										<select name="ReferenceTypeId" id="ReferenceTypeId" style="width:200px;" onchange="verifyRefTypeChange();">
											<option value="">Select Reference Type</option>
											<option value="">-------------------------------</option>
											<?php
											$typeArr = $refManager->getRefTypeArr();
											foreach($typeArr as $k => $v){
												echo '<option value="'.$k.'" '.($refArr['ReferenceTypeId']==$k?'SELECTED':'').'>'.$v.'</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div style="width:100px;margin-top:25px;float:right;">
									<b>Published: </b><input type="checkbox" id="ispublishedcheck" onchange="updateIspublished(this.form);" value="" <?php echo (!$refArr['ispublished']?'':'checked'); ?> />
								</div>
							</div>
							<?php
							if($fieldArr['Title']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['Title']; ?>: </b>
									</div>
									<div>
										<textarea name="title" id="title" rows="10" style="width:380px;height:40px;resize:vertical;" ><?php echo $refArr['title']; ?></textarea>
									</div>
								</div>
								<?php
							}
							if($fieldArr['Pages']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['Pages']; ?>: </b>
									</div>
									<div>
										<input type="text" name="pages" id="pages" tabindex="100" maxlength="45" style="width:200px;" value="<?php echo $refArr['pages']; ?>" onchange="" title="" />
									</div>
								</div>
								<?php
							}
							if($fieldArr['TypeWork']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['TypeWork']; ?>: </b>
									</div>
									<div>
										<textarea name="typework" id="typework" rows="10" style="width:380px;height:40px;resize:vertical;" ><?php echo $refArr['typework']; ?></textarea>
									</div>
								</div>
								<?php
							}
							if($fieldArr['Section']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['Section']; ?>: </b>
									</div>
									<div>
										<input type="text" name="section" id="section" tabindex="100" maxlength="45" style="width:150px;" value="<?php echo $refArr['section']; ?>" onchange="" title="" />
									</div>
								</div>
								<?php
							}
							if($fieldArr['SecondaryTitle']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['SecondaryTitle']; ?>: </b>
									</div>
									<div>
										<textarea name="secondarytitle" id="secondarytitle" rows="10" onchange="" style="width:380px;height:40px;resize:vertical;" ><?php echo $refArr['secondarytitle']; ?></textarea>
									</div>
								</div>
								<?php
							}
							if($fieldArr['TertiaryTitle']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['TertiaryTitle']; ?>: </b>
									</div>
									<div>
										<textarea name="tertiarytitle" id="tertiarytitle" onchange="" rows="10" style="width:380px;height:40px;resize:vertical;" ><?php echo $refArr['tertiarytitle']; ?></textarea>
									</div>
								</div>
								<?php
							}
							if($fieldArr['AlternativeTitle']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['AlternativeTitle']; ?>: </b>
									</div>
									<div>
										<textarea name="alternativetitle" id="alternativetitle" rows="10" style="width:380px;height:40px;resize:vertical;" ><?php echo $refArr['alternativetitle']; ?></textarea>
									</div>
								</div>
								<?php
							}
							if($fieldArr['ShortTitle']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['ShortTitle']; ?>: </b>
									</div>
									<div>
										<textarea name="shorttitle" id="shorttitle" onchange="" rows="10" style="width:380px;height:40px;resize:vertical;" ><?php echo $refArr['shorttitle']; ?></textarea>
									</div>
								</div>
								<?php
							}
							if($fieldArr['Volume']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['Volume']; ?>: </b>
									</div>
									<div>
										<input type="text" name="volume" id="volume" onchange="" tabindex="100" maxlength="45" style="width:100px;" value="<?php echo $refArr['volume']; ?>" onchange="" title="" />
									</div>
								</div>
								<?php
							}
							if($fieldArr['Number']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['Number']; ?>: </b>
									</div>
									<div>
										<input type="text" name="number" id="number" onchange="" tabindex="100" maxlength="45" style="width:100px;" value="<?php echo $refArr['number']; ?>" onchange="" title="" />
									</div>
								</div>
								<?php
							}
							if($fieldArr['NumberVolumes']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['NumberVolumes']; ?>: </b>
									</div>
									<div>
										<input type="text" name="numbervolumnes" id="numbervolumnes" onchange="" tabindex="100" maxlength="45" style="width:100px;" value="<?php echo $refArr['numbervolumnes']; ?>" onchange="" title="" />
									</div>
								</div>
								<?php
							}
							if($fieldArr['Date']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['Date']; ?>: </b>
									</div>
									<div>
										<input type="text" name="pubdate" id="pubdate" onchange="" tabindex="100" maxlength="45" style="width:150px;" value="<?php echo $refArr['pubdate']; ?>" onchange="" title="" />
									</div>
								</div>
								<?php
							}
							if($fieldArr['Edition']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['Edition']; ?>: </b>
									</div>
									<div>
										<input type="text" name="edition" id="edition" onchange="" tabindex="100" maxlength="45" style="width:150px;" value="<?php echo $refArr['edition']; ?>" onchange="" title="" />
									</div>
								</div>
								<?php
							}
							if($fieldArr['Publisher']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['Publisher']; ?>: </b>
									</div>
									<div>
										<input type="text" name="publisher" id="publisher" onchange="" tabindex="100" maxlength="150" style="width:300px;" value="<?php echo $refArr['publisher']; ?>" onchange="" title="" />
									</div>
								</div>
								<?php
							}
							if($fieldArr['PlacePublished']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['PlacePublished']; ?>: </b>
									</div>
									<div>
										<input type="text" name="placeofpublication" id="placeofpublication" onchange="" tabindex="100" maxlength="45" style="width:300px;" value="<?php echo $refArr['placeofpublication']; ?>" onchange="" title="" />
									</div>
								</div>
								<?php
							}
							if($fieldArr['ISBN_ISSN']){
								?>
								<div style="clear:both;padding-top:6px;float:left;">
									<div>
										<b><?php echo $fieldArr['ISBN_ISSN']; ?>: </b>
									</div>
									<div>
										<input type="text" name="isbn_issn" id="isbn_issn" onchange="" tabindex="100" maxlength="45" style="width:300px;" value="<?php echo $refArr['isbn_issn']; ?>" onchange="" title="" />
									</div>
								</div>
								<?php
							}
							?>
							<div style="clear:both;padding-top:6px;float:left;">
								<div>
									<b>GUID: </b>
								</div>
								<div>
									<input type="text" name="guid" id="guid" tabindex="100" maxlength="45" style="width:350px;" value="<?php echo $refArr['guid']; ?>" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:6px;float:left;">
								<div>
									<b>URL: </b>
								</div>
								<div>
									<textarea name="url" id="url" rows="10" style="width:380px;height:40px;resize:vertical;" ><?php echo $refArr['url']; ?></textarea>
								</div>
							</div>
							<div style="clear:both;padding-top:6px;float:left;">
								<div>
									<b>Notes: </b>
								</div>
								<div>
									<textarea name="notes" id="notes" rows="10" style="width:380px;height:40px;resize:vertical;" ><?php echo $refArr['notes']; ?></textarea>
								</div>
							</div>
							<div style="clear:both;padding-top:8px;float:left;">
								<input name="refid" type="hidden" value="<?php echo $refId; ?>" />
								<input name="parentRefId" id="parentRefId" type="hidden" value="<?php echo $refArr['parentRefId']; ?>" />
								<input name="parentRefId2" id="parentRefId2" type="hidden" value="<?php echo $refArr['parentRefId2']; ?>" />
								<input name="refGroup" id="refGroup" type="hidden" value="<?php echo $refGroup; ?>" />
								<input name="ispublished" id="ispublished" type="hidden" value="<?php echo $refArr['ispublished']; ?>" />
								<div id="dynamicInput"></div>
								<button name="formsubmit" type="submit" value="Edit Reference">Save Edits</button>
							</div>
						</form>
					</div>
				</div>

				<div id="reflinksdiv" style="">
					<div style="width:600px;">
						<?php
						if($refChecklistArr || $refCollArr || $refOccArr || $refTaxaArr){
							echo '<h2>Reference Links:</h2>';

							echo '<b>Checklist links:</b>';
							echo '<div id="referencechecklistlink">';
							if($refChecklistArr){
								echo '<ul>';
								foreach($refChecklistArr as $k => $v){
									echo '<li>';
									echo '<a href="../checklists/checklist.php?clid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=" target="_blank" >';
									echo $v;
									echo '</a>';
									echo '</li>';
								}
								echo '</ul>';
							}
							else{
								echo 'There are no checklists linked with this reference';
							}
							echo '</div><br />';

							echo '<b>Collection links:</b>';
							echo '<div id="referencecollectionlink">';
							if($refCollArr){
								echo '<ul>';
								foreach($refCollArr as $k => $v){
									echo '<li>';
									echo '<a href="../collections/misc/collprofiles.php?collid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" >';
									echo $v;
									echo '</a>';
									echo '</li>';
								}
								echo '</ul>';
							}
							else{
								echo 'There are no collections linked with this reference';
							}
							echo '</div><br />';

							echo '<b>Occurrence links:</b>';
							echo '<div id="referenceoccurlink">';
							if($refOccArr){
								echo '<ul>';
								foreach($refOccArr as $k => $v){
									echo '<li>';
									echo '<a href="../collections/individual/index.php?occid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&clid=0" target="_blank" >';
									echo $v;
									echo '</a>';
									echo '</li>';
								}
								echo '</ul>';
							}
							else{
								echo 'There are no occurrences linked with this reference';
							}
							echo '</div><br />';

							echo '<b>Taxa links:</b>';
							echo '<div id="referencetaxalink">';
							if($refTaxaArr){
								echo '<ul>';
								foreach($refTaxaArr as $k => $v){
									$name = str_replace(' ','%20',$v);
									echo '<li>';
									echo '<a href="../taxa/index.php?taxon=' . htmlspecialchars($name, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" >';
									echo $v;
									echo '</a>';
									echo '</li>';
								}
								echo '</ul>';
							}
							else{
								echo 'There are no taxa linked with this reference';
							}
							echo '</div>';
						}
						else{
							echo '<h2>There are no records linked with this reference</h2>';
						}
						?>
					</div>
				</div>

				<div id="refadmindiv" style="">
					<form name="delrefform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this reference?')">
						<fieldset style="width:350px;margin:20px;padding:20px;">
							<legend><b>Delete Reference</b></legend>
							<?php
							if($refChecklistArr || $refCollArr || $refOccArr || $refTaxaArr){
								echo '<div style="font-weight:bold;margin-bottom:15px;">';
								echo 'Reference cannot be deleted until all linked records are removed.';
								echo '</div>';
							}
							if($childArr){
								echo '<div style="font-weight:bold;margin-bottom:15px;">';
								echo 'Reference is a parent reference and cannot be deleted until all child references are deleted.';
								echo '</div>';
							}
							?>
							<input name="formsubmit" type="submit" value="Delete Reference" <?php if($childArr || $refChecklistArr || $refCollArr || $refOccArr || $refTaxaArr) echo 'DISABLED'; ?> />
							<input name="refid" type="hidden" value="<?php echo $refId; ?>" />
						</fieldset>
					</form>
				</div>
			</div>
			<?php
		}
		else{
			if(!$SYMB_UID){
				echo 'Please <a href="../profile/index.php?refurl=../references/index.php">login</a>';
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
