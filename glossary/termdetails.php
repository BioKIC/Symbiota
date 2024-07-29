<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GlossaryManager.php');
include_once($SERVER_ROOT.'/content/lang/glossary/termdetails.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../profile/index.php?refurl='.$CLIENT_ROOT.'/glossary/termdetails.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$glossId = array_key_exists('glossid', $_REQUEST) ? filter_var($_REQUEST['glossid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$formSubmit = array_key_exists('formsubmit', $_POST) ? htmlspecialchars($_POST['formsubmit']) : '';
$statusStr = array_key_exists('statusstr',$_REQUEST) ? htmlspecialchars($_REQUEST['statusstr'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$tabIndex = array_key_exists('tabindex', $_REQUEST) ? filter_var($_REQUEST['tabindex'], FILTER_SANITIZE_NUMBER_INT) : 0;
$glimgid = array_key_exists('glimgid', $_POST) ? filter_var($_POST['glimgid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$relglossid = array_key_exists('relglossid', $_POST) ? filter_var($_POST['relglossid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$relationship = array_key_exists('relationship', $_POST) ? htmlspecialchars($_POST['relationship'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 0;
$gltlinkid = array_key_exists('gltlinkid', $_POST) ? filter_var($_POST['gltlinkid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$tid = array_key_exists('tid', $_POST) ? filter_var($_POST['tid'], FILTER_SANITIZE_NUMBER_INT) : 0;

$isEditor = false;
if($IS_ADMIN || array_key_exists('GlossaryEditor', $USER_RIGHTS)) $isEditor = true;

$glosManager = new GlossaryManager();
$glosManager->setGlossId($glossId);

$hasImages = false;
$closeWindow = false;
if($formSubmit){
	if($formSubmit == 'Edit Term'){
		if(!$glosManager->editTerm($_POST)){
			$statusStr = $glosManager->getErrorMessage();
		}
	}
	elseif($formSubmit == 'Submit New Image'){
		$statusStr = $glosManager->addImage($_POST);
	}
	elseif($formSubmit == 'Save Image Edits'){
		$statusStr = $glosManager->editImageData($_POST);
	}
	elseif($formSubmit == 'Delete Image'){
		$statusStr = $glosManager->deleteImage($glimgid);
	}
	elseif($formSubmit == 'Link Translation'){
		if(!$glosManager->linkTranslation($relglossid)){
			$statusStr = $glosManager->getErrorMessage();
		}
		$glosManager->setGlossId($glossId);
	}
	elseif($formSubmit == 'Link Related Term'){
		if(!$glosManager->linkRelation($relglossid, $relationship)){
			$statusStr = $glosManager->getErrorMessage();
		}
		$glosManager->setGlossId($glossId);
	}
	elseif($formSubmit == 'Remove Translation'){
		if(!$glosManager->removeRelation($gltlinkid, $relglossid)){
			$statusStr = $glosManager->getErrorMessage();
		}
		$glosManager->setGlossId($glossId);
	}
	elseif($formSubmit == 'removeSynonym'){
		if(!$glosManager->removeRelation($gltlinkid, $relglossid)){
			$statusStr = $glosManager->getErrorMessage();
		}
		$glosManager->setGlossId($glossId);
	}
	elseif($formSubmit == 'Unlink Related Term'){
		if(!$glosManager->removeRelation($gltlinkid)){
			$statusStr = $glosManager->getErrorMessage();
		}
	}
	elseif($formSubmit == 'Add Taxa Group'){
		if(!$glosManager->addGroupTaxaLink($tid)){
			$statusStr = $glosManager->getErrorMessage();
		}
	}
	elseif($formSubmit == 'Delete Taxa Group'){
		if(!$glosManager->deleteGroupTaxaLink($tid)){
			$statusStr = $glosManager->getErrorMessage();
		}
	}
	elseif($formSubmit == 'Delete Term'){
		if($glosManager->deleteTerm($_POST)){
			$glossId = 0;
			$closeWindow = true;
		}
		else{
			$statusStr = $glosManager->getErrorMessage();
		}
	}
}
if($statusStr=='successadd') $statusStr = 'New term successfully created!';

if($glossId){
	$termArr = $glosManager->getTermArr();
	$taxaArr = $glosManager->getTermTaxaArr();
	$termImgArr = $glosManager->getImgArr();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . $LANG['G_MGMNT']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="../../js/tinymce/tinymce.min.js"></script>
	<script type="text/javascript" src="../js/symb/glossary.index.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			<?php
			if($closeWindow){
				echo 'window.opener.searchform.submit();';
				echo 'self.close();';
			}
			?>

			$('#tabs').tabs({
				active: <?php echo $tabIndex; ?>,
				beforeLoad: function( event, ui ) {
					$(ui.panel).html("<?php echo '<p>'.(isset($LANG['LOAD'])?$LANG['LOAD']:'Loading').'...</p>'; ?>");
				}
			});

			tinymce.init({
				selector: "textarea",
				width: "100%",
				height: 300,
				menubar: false,
				plugins: "link,charmap,code,paste",
				toolbar : ["bold italic underline | cut copy paste | outdent indent | subscript superscript | undo redo removeformat | link | charmap | code"],
				default_link_target: "_blank",
				paste_as_text: true
			});
		});

		function verifyTermEditForm(f){
			if(!f.term.value || !f.language.value){
				alert("Term and language must have a value");
				return false;
			}

			if(f.definition.value.length > 1998){
				if(!confirm("<?php echo (isset($LANG['WARNING_LONG'])?$LANG['WARNING_LONG']:'Warning, your definition is close to maximum size limit and may be cut off. Are you sure the definition is completely entered?'); ?>")) return false;
			}
			return true;
		}

		function verifyRelLinkForm(f){
			if(!f.relglossid.value){
				alert("<?php echo (isset($LANG['PLEASE_RELATE'])?$LANG['PLEASE_RELATE']:'Please select a related term'); ?>");
				return false;
			}
			return true;
		}

		function verifyTransLinkForm(f){
			if(!f.relglossid.value){
				alert("<?php echo (isset($LANG['PLEASE_TRANS'])?$LANG['PLEASE_TRANS']:'Please select a translation term'); ?>");
				return false;
			}
			return true;
		}

		function verifyNewImageForm(f){
			if(!document.getElementById("imgfile").files[0] && document.getElementById("imgurl").value == ""){
				alert("<?php echo (isset($LANG['PLEASE_IMG'])?$LANG['PLEASE_IMG']:'Please either upload an image or enter the url of an existing image'); ?>");
				return false;
			}
			return true;
		}

		function verifyImageEditForm(f){
			if(document.getElementById("editurl").value == ""){
				document.getElementById("editurl").value = document.getElementById("oldurl").value;
				alert("<?php echo (isset($LANG['PLEASE_URL'])?$LANG['PLEASE_URL']:'Please enter a url for the image to save'); ?>");
				return false;
			}
			return true;
		}

	</script>
	<style type="text/css">
		body{ width: 100%; max-width: 1100px; min-width: 300px; }
		fieldset{ padding: 15px }
		legend{ font-weight: bold }
		#tabs a{ outline-color: transparent; font-size: 12px; font-weight: normal; }
	</style>
</head>
<body>
	<div class='navpath'>
		&lt; &lt; <a href='individual.php?glossid=<?= $glossId ?>'><?= $LANG['GOTO_PUBLIC_DISPLAY'] ?></a>
	</div>
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['G_MGMNT']; ?></h1>
		<?php
		if($glossId && $isEditor){
			if($statusStr){
				?>
				<div style="margin:15px;color:<?php echo (stripos($statusStr, 'SUCCESS') !== false?'green':'red'); ?>;">
					<?php echo htmlspecialchars($statusStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
				</div>
				<?php
			}
			?>
			<div id="tabs" style="margin:0px;">
				<ul>
					<li><a href="#termdetaildiv"><?php echo htmlspecialchars((isset($LANG['DETAILS'])?$LANG['DETAILS']:'Details'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
					<li><a href="#termrelateddiv"><?php echo htmlspecialchars((isset($LANG['REL_TERMS'])?$LANG['REL_TERMS']:'Related Terms'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
					<li><a href="#termtransdiv"><?php echo htmlspecialchars((isset($LANG['TRANSS'])?$LANG['TRANSS']:'Translations'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
					<li><a href="#termimagediv"><?php echo htmlspecialchars((isset($LANG['IMAGES'])?$LANG['IMAGES']:'Images'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
					<li><a href="#termadmindiv"><?php echo htmlspecialchars((isset($LANG['ADMIN'])?$LANG['ADMIN']:'Admin'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
				</ul>
				<div id="termdetaildiv" style="">
					<div id="termdetails" style="overflow:auto;">
						<form name="termeditform" id="termeditform" action="termdetails.php" method="post" onsubmit="return verifyTermEditForm(this);">
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b><?php echo (isset($LANG['TERM'])?$LANG['TERM']:'Term'); ?>: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input type="text" name="term" id="term" maxlength="150" style="width:400px;" value="<?php echo $termArr['term']; ?>" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;width:100%;">
								<div style="float:left;">
									<b><?php echo (isset($LANG['DEFINITION'])?$LANG['DEFINITION']:'Definition'); ?>: </b>
								</div>
								<div style="float:left;margin-left:10px;width:95%;">
									<textarea name="definition" id="definition" rows="10" maxlength="2000" style="width:100%;height:200px;" ><?php echo $termArr['definition']; ?></textarea>
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b><?php echo (isset($LANG['LANGUAGE'])?$LANG['LANGUAGE']:'Language'); ?>: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<select id="langSelect" name="language">
										<?php
										$langArr = $glosManager->getLanguageArr('all');
										foreach($langArr as $langStr ){
											echo '<option '.($glosManager->getTermLanguage()==$langStr?'SELECTED':'').'>'.$langStr.'</option>';
										}
										?>
									</select>
									<a href="#" onclick="toggle('addLangDiv');return false;"><img src="../images/add.png" style="width:1.5em"/></a>&nbsp;&nbsp;
								</div>
								<div id="addLangDiv" style="float:left;display:none">
									<input name="newlang" type="text" maxlength="45" style="width:200px;" />
									<button onclick="addNewLang(this.form);return false;"><?php echo (isset($LANG['ADD_LANG'])?$LANG['ADD_LANG']:'Add language'); ?></button>
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b><?php echo (isset($LANG['AUTHOR'])?$LANG['AUTHOR']:'Author'); ?>: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input name="author" type="text" maxlength="250" style="width:500px;" value="<?php echo $termArr['author']; ?>" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b><?php echo (isset($LANG['TRANSLATOR'])?$LANG['TRANSLATOR']:'Translator'); ?>: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input name="translator" type="text" maxlength="250" style="width:500px;" value="<?php echo $termArr['translator']; ?>" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b><?php echo (isset($LANG['SOURCE'])?$LANG['SOURCE']:'Source'); ?>: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input name="source" type="text" maxlength="1000" style="width:500px;" value="<?php echo $termArr['source']; ?>" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b><?php echo (isset($LANG['NOTES'])?$LANG['NOTES']:'Notes'); ?>: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input name="notes" type="text" maxlength="250" style="width:380px;" value="<?php echo $termArr['notes']; ?>" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b><?php echo (isset($LANG['RES_URL'])?$LANG['RES_URL']:'Resource URL'); ?>: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input name="resourceurl" type="text" maxlength="600" style="width:600px;" value="<?php echo $termArr['resourceurl']; ?>" />
								</div>
							</div>
							<div style="clear:both;padding:20px;">
								<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
								<input id="origterm" type="hidden" value="<?php echo $termArr['term']; ?>" />
								<input id="origlang" type="hidden" value="<?php echo $glosManager->getTermLanguage(); ?>" />
								<button name="formsubmit" type="submit" value="Edit Term"><?php echo (isset($LANG['SAVE'])?$LANG['SAVE']:'Save Edits'); ?></button>
							</div>
						</form>
						<div style="clear:both;height:15px;"></div>
						<fieldset style='clear:both;padding:8px;margin-bottom:10px;'>
							<legend><?php echo (isset($LANG['TAX_GROUPS'])?$LANG['TAX_GROUPS']:'Taxonomic Groups'); ?></legend>
							<div style="clear:both;" onclick="" title="Taxa Groups">
								<ul>
									<?php
									foreach($taxaArr as $taxId => $sciname){
										echo '<li><form name="taxadelform" id="'.$sciname.'" action="termdetails.php" style="margin-top:0px;margin-bottom:0px;" method="post">';
										echo $sciname;
										echo '<input style="margin-left:5px;width:1.3em" type="image" src="../images/del.png" title=\"'.(isset($LANG['DEL_TAX'])?$LANG['DEL_TAX']:'Delete Taxon Group').'\">';
										echo '<input name="glossid" type="hidden" value="'.$glossId.'" />';
										echo '<input name="tid" type="hidden" value="'.$taxId.'" />';
										echo '<input name="formsubmit" type="hidden" value="Delete Taxa Group" />';
										echo '</form></li>';
									}
									?>
								</ul>
							</div>
							<div style="clear:both;margin:10px">
								<form name="taxaaddform" id="taxaaddform" action="termdetails.php" method="post" onsubmit="">
									<div style="float:left;">
										<b><?php echo (isset($LANG['ADD_TAX'])?$LANG['ADD_TAX']:'Add Taxonomic Group'); ?>: </b>
									</div>
									<div style="float:left;margin-left:10px;">
										<input type="text" name="taxagroup" id="taxagroup" maxlength="45" style="width:250px;" value="" onchange="" title="" />
										<input name="tid" id="tid" type="hidden" value="" />
									</div>
									<div style="float:left;margin-left:10px;">
										<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
										<button name="formsubmit" type="submit" value="Add Taxa Group"><?php echo (isset($LANG['ADD_GRP'])?$LANG['ADD_GRP']:'Add Group'); ?></button>
									</div>
								</form>
							</div>
						</fieldset>
					</div>
				</div>
				<div id="termrelateddiv">
					<?php
					$synonymArr = $glosManager->getSynonyms();
					$otherRelationshipsArr = $glosManager->getOtherRelatedTerms();
					?>
					<div style="margin:10px;float:right;cursor:pointer;<?php echo (!$synonymArr||$otherRelationshipsArr?'display:none;':''); ?>" onclick="toggle('addsyndiv');" title="Add a New Synonym">
						<img style="border:0px;width:1.5em;" src="../images/add.png" />
					</div>
					<div id="addsyndiv" style="margin-bottom:10px;<?php echo ($synonymArr||$otherRelationshipsArr?'display:none;':''); ?>;">
						<form name="relnewform" action="termdetails.php#termrelateddiv" method="post" onsubmit="return verifyRelLinkForm(this);">
							<fieldset style="padding:25px">
								<legend><?php echo (isset($LANG['LINK_REL'])?$LANG['LINK_REL']:'Link a Related Term'); ?></legend>
								<div style="clear:both;padding-top:4px;">
									<div style="">
										<b><?php echo (isset($LANG['THIS_T'])?$LANG['THIS_T']:'This term'); ?></b>
										<select name="relationship">
											<option value="synonym"><?php echo (isset($LANG['IS_SYN'])?$LANG['IS_SYN']:'is Synonym of').'...'; ?></option>
											<option value="subClassOf"><?php echo (isset($LANG['IS_SUB'])?$LANG['IS_SUB']:'is Subclass of').'...('.(isset($LANG['CHILD_OF'])?$LANG['CHILD_OF']:'Child of').'...)'; ?></option>
											<option value="superClassOf"><?php echo (isset($LANG['IS_SUP'])?$LANG['IS_SUP']:'is Superclass of').'...('.(isset($LANG['PARENT_OF'])?$LANG['PARENT_OF']:'Parent of').'...)'; ?></option>
											<option value="hasPart"><?php echo (isset($LANG['HAS_PART'])?$LANG['HAS_PART']:'has Part'); ?>...</option>
											<option value="partOf"><?php echo (isset($LANG['PART_OF'])?$LANG['PART_OF']:'is Part of'); ?>...</option>
										</select>
										<select name="relglossid">
											<option value=''><?php echo (isset($LANG['SEL_REL_T'])?$LANG['SEL_REL_T']:'Select Related Term'); ?></option>
											<option value=''>------------------------</option>
											<?php
											$relList = $glosManager->getTermList('related',$glosManager->getTermLanguage());
											unset($relList[$glossId]);
											$relList = array_diff_key($relList, $synonymArr, $otherRelationshipsArr);
											foreach($relList as $relId => $relName){
												echo '<option value="'.$relId.'">'.$relName.'</option>';
											}
											?>
										</select>
										<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
										<button name="formsubmit" type="submit" value="Link Related Term"><?php echo (isset($LANG['LINK_T'])?$LANG['LINK_T']:'Link Related Term'); ?></button>
									</div>
								</div>
								<div style="clear:both;"></div>
								<div style="clear:both;margin:30px 10px;">
									<div style="margin:3px"><?php echo (isset($LANG['OR_ADD'])?$LANG['OR_ADD']:'Or add a'); ?> <a href="addterm.php?relationship=synonym&relglossid=<?php echo htmlspecialchars($glossId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&rellanguage=' . htmlspecialchars($glosManager->getTermLanguage(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars((isset($LANG['NEW_SYN'])?$LANG['NEW_SYN']:'New Synonym'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> <?php echo htmlspecialchars((isset($LANG['NOT_YET'])?$LANG['NOT_YET']:'that is not yet in the system'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></div>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
					if($synonymArr){
						?>
						<fieldset style='clear:both;margin-bottom:10px;'>
							<legend><?php echo (isset($LANG['SYNS'])?$LANG['SYNS']:'Synonyms'); ?></legend>
							<?php
							foreach($synonymArr as $synGlossId => $synArr){
								?>
								<div style="margin:15px;padding:10px;border:1px solid gray">
									<?php
									$disableRemoveSyn = false;
									$removeSynTitle = 'Remove Synonym';
									if($synGlossId == $glosManager->getGlossGroupId()){
										$removeSynTitle = (isset($LANG['ROOT_REM'])?$LANG['ROOT_REM']:'Root term cannot be removed! Instead, go to root term and then remove other relations.');
										$disableRemoveSyn = true;
									}
									?>
									<div style="float:right;margin:5px;" title="<?php echo $removeSynTitle; ?>">
										<form name="syndelform" action="termdetails.php#termrelateddiv" method="post" onsubmit="<?php if($disableRemoveSyn) echo 'return false'; ?>">
											<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
											<input name="gltlinkid" type="hidden" value="<?php echo $synArr['gltlinkid']; ?>" />
											<input name="relglossid" type="hidden" value="<?php echo $synGlossId; ?>" />
											<input name="formsubmit" type="hidden" value="removeSynonym" >
											<input type="image" name="delimage" src='../images/del.png' style="width:1.3em" <?php if($disableRemoveSyn) echo 'disabled'; ?>>
										</form>
									</div>
									<div style="float:right;margin:5px;cursor:pointer;" title="Edit Term">
										<a href="termdetails.php?glossid=<?php echo htmlspecialchars($synGlossId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
											<img style="border:0px;width:1.2em;" src="../images/edit.png" />
										</a>
									</div>
									<div style='' >
										<b><?php echo (isset($LANG['TERM'])?$LANG['TERM']:'Term'); ?>:</b>
										<?php echo $synArr['term']; ?>
									</div>
									<div style='margin-top:8px;' >
										<b><?php echo (isset($LANG['DEFINITION'])?$LANG['DEFINITION']:'Definition'); ?>:</b>
										<?php echo $synArr['definition']; ?>
									</div>
									<div style='margin-top:8px;' >
										<b><?php echo (isset($LANG['SOURCE'])?$LANG['SOURCE']:'Source'); ?>:</b>
										<?php echo $synArr['source']; ?>
									</div>
								</div>
								<?php
							}
							?>
						</fieldset>
						<?php
					}
					//Other relationships (superclass, subclass, partOf, hasPart)
					if($otherRelationshipsArr){
						?>
						<fieldset style='clear:both;margin-bottom:10px;'>
							<legend><?php echo (isset($LANG['OTHER_RELS'])?$LANG['OTHER_RELS']:'Other Relationships'); ?></legend>
							<?php
							foreach($otherRelationshipsArr as $relType => $relTypeArr){
								$relStr = (isset($LANG['IS_REL'])?$LANG['IS_REL']:'is related to');
								if($relType == 'partOf') $relStr = (isset($LANG['IS_PART'])?$LANG['IS_PART']:'is part of');
								elseif($relType == 'hasPart') $relStr = (isset($LANG['HAS_PART_2'])?$LANG['HAS_PART_2']:'has part');
								elseif($relType == 'subClassOf') $relStr = (isset($LANG['SUB_CHILD'])?$LANG['SUB_CHILD']:'is subclass of (child of)');
								elseif($relType == 'superClassOf') $relStr = (isset($LANG['SUP_PAR'])?$LANG['SUP_PAR']:'is superclass of (parent of)');
								foreach($relTypeArr as $relGlossId => $relArr){
									$disableRemoveRel = false;
									$removeRelTitle = (isset($LANG['UNLINK_REL'])?$LANG['UNLINK_REL']:'Unlink Related Term');
									if($relGlossId == $glosManager->getGlossGroupId()){
										$removeRelTitle = (isset($LANG['ROOT_REM'])?$LANG['ROOT_REM']:'Root term cannot be removed! Instead, go to root term and then remove other relations.');
										$disableRemoveRel = true;
									}
									?>
									<div style="margin:15px;padding:10px;border:1px solid gray">
										<div style="float:right;margin:5px;" title="<?php echo $removeRelTitle; ?>">
											<form name="reldelform" action="termdetails.php#termrelateddiv" method="post" onsubmit="<?php if($disableRemoveRel) echo 'return false'; ?>">
												<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
												<input name="gltlinkid" type="hidden" value="<?php echo $relArr['gltlinkid']; ?>" />
												<input name="relglossid" type="hidden" value="<?php echo $relGlossId; ?>" />
												<input type="image" name="formsubmit" src='../images/del.png' value="Unlink Related Term" style="width:1.3em" <?php if($disableRemoveRel) echo 'disabled'; ?>>
											</form>
										</div>
										<div style="float:right;margin:5px;" title="<?php echo (isset($LANG['EDIT_T'])?$LANG['EDIT_T']:'Edit Term'); ?>">
											<a href="termdetails.php?glossid=<?php echo htmlspecialchars($relGlossId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
												<img style="border:0px;width:1.3em;" src="../images/edit.png" />
											</a>
										</div>
										<div>
											<?php echo (isset($LANG['CUR_TERM'])?$LANG['CUR_TERM']:'Current term').' '.$relStr.': <b>'.$relArr['term'].'</b>'; ?>
										</div>
										<div style='clear:both;margin-top:3px;' >
											<b><?php echo (isset($LANG['DEFINITION'])?$LANG['DEFINITION']:'Definition'); ?>:</b>
											<?php echo $relArr['definition']; ?>
										</div>
									</div>
									<?php
								}
							}
							?>
						</fieldset>
						<?php
					}
					?>
				</div>
				<div id="termtransdiv" style="">
					<?php
					$translationArr = $glosManager->getTranslations();
					?>
					<div style="margin:10px;float:right;cursor:pointer;<?php echo (!$translationArr?'display:none;':''); ?>" onclick="toggle('addtransdiv');" title="Add a New Translation">
						<img style="border:0px;width:1.5em;" src="../images/add.png" />
					</div>
					<div id="addtransdiv" style="margin-bottom:10px;<?php echo ($translationArr?'display:none;':''); ?>;">
						<form name="translinkform" action="termdetails.php#termtransdiv" method="post" onsubmit="return verifyTransLinkForm(this);">
							<fieldset style="padding:25px">
								<legend><?php echo (isset($LANG['LINK_TRANS'])?$LANG['LINK_TRANS']:'Link a Translation'); ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="float:left;">
										<b><?php echo (isset($LANG['LINK_EX_T'])?$LANG['LINK_EX_T']:'Link an existing term'); ?>: </b>
									</div>
									<div style="float:left;margin-left:10px;">
										<select name="relglossid">
											<option value=''><?php echo (isset($LANG['SEL_TRANS_T'])?$LANG['SEL_TRANS_T']:'Select Translation Term'); ?></option>
											<option value=''>------------------------</option>
											<?php
											$transList = $glosManager->getTermList('translation',$glosManager->getTermLanguage());
											$transList = array_diff_key($transList, $translationArr);
											foreach($transList as $transId => $transName){
												echo '<option value="'.$transId.'">'.$transName.'</option>';
											}
											?>
										</select>
									</div>
									<div style="float:left;margin-left:30px;">
										<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
										<button name="formsubmit" type="submit" value="Link Translation"><?php echo (isset($LANG['LINK_TR'])?$LANG['LINK_TR']:'Link Translation'); ?></button>
									</div>
								</div>
								<div style="clear:both;"></div>
								<div style="clear:both;margin: 30px 10px;">
									<?php echo (isset($LANG['OR_ADD'])?$LANG['OR_ADD']:'Or add a'); ?> <a href="addterm.php?relationship=translation&relglossid=<?php echo htmlspecialchars($glossId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&rellanguage=' . htmlspecialchars($glosManager->getTermLanguage(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars((isset($LANG['NEW_TRANS'])?$LANG['NEW_TRANS']:'New Translation'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> <?php echo htmlspecialchars((isset($LANG['TO_T'])?$LANG['TO_T']:'to this term'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
								</div>
							</fieldset>
						</form>
					</div>
					<div style="padding-top:15px">
						<?php
						if($translationArr){
							foreach($translationArr as $transGlossId => $transArr){
								?>
								<div style="width:95%;margin:15px;padding:10px;border:1px solid gray">
									<?php
									if($transArr['gltlinkid'] && $transGlossId != $glosManager->getGlossGroupId()){
										?>
										<div style="float:right;margin:5px;" title="<?php echo (isset($LANG['REM_TRANS'])?$LANG['REM_TRANS']:'Remove Translation'); ?>">
											<form name="transdelform" action="termdetails.php#termtransdiv" method="post">
												<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
												<input name="gltlinkid" type="hidden" value="<?php echo $transArr['gltlinkid']; ?>" />
												<input name="relglossid" type="hidden" value="<?php echo $transGlossId; ?>" />
												<input type="image" name="formsubmit" src='../images/del.png' value="Remove Translation" style="width:1.3em;">
											</form>
										</div>
										<?php
									}
									?>
									<div style="float:right;margin:5px;" title="<?php echo (isset($LANG['EDIT_T_DAT'])?$LANG['EDIT_T_DAT']:'Edit Term Data'); ?>">
										<a href="termdetails.php?glossid=<?php echo ≈; ?>">
											<img style="border:0px;width:1.3em;" src="../images/edit.png" />
										</a>
									</div>
									<div>
										<b><?php echo (isset($LANG['TERM'])?$LANG['TERM']:'Term'); ?>:</b>
										<?php echo $transArr['term']; ?>
									</div>
									<div style='margin-top:8px;' >
										<b><?php echo (isset($LANG['DEFINITION'])?$LANG['DEFINITION']:'Definition'); ?>:</b>
										<?php echo $transArr['definition']; ?>
									</div>
									<div style='margin-top:8px;' >
										<b><?php echo (isset($LANG['LANGUAGE'])?$LANG['LANGUAGE']:'Language'); ?>:</b>
										<?php echo $transArr['language']; ?>
									</div>
									<div style='margin-top:8px;' >
										<b><?php echo (isset($LANG['SOURCE'])?$LANG['SOURCE']:'Source'); ?>:</b>
										<?php echo $transArr['source']; ?>
									</div>
								</div>
								<?php
							}
						}
						?>
					</div>
				</div>
				<div id="termimagediv" style="min-height:300px;">
					<div id="imagediv" style="">
						<div style="margin:10px;float:right;cursor:pointer;<?php echo (!$termImgArr?'display:none;':''); ?>" onclick="toggle('addimgdiv');" title="Add a New Image">
							<img style="border:0px;width:1.5em;" src="../images/add.png" />
						</div>
						<div id="addimgdiv" style="<?php echo ($termImgArr?'display:none;':''); ?>;">
							<form name="imgnewform" action="termdetails.php#termimagediv" method="post" enctype="multipart/form-data" onsubmit="return verifyNewImageForm(this);">
								<fieldset>
									<legend><?php echo (isset($LANG['ADD_IMG'])?$LANG['ADD_IMG']:'Add a New Image'); ?></legend>
									<div style='padding:15px;border:1px solid yellow;background-color:FFFF99;'>
										<div class="targetdiv" style="display:block;">
											<div style="font-weight:bold;font-size:110%;margin-bottom:5px;">
												<?php echo (isset($LANG['SEL_IMG'])?$LANG['SEL_IMG']:'Select an image file located on your computer that you want to upload'); ?>:
											</div>
											<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
											<div style="height:10px;float:right;text-decoration:underline;font-weight:bold;">
												<a href="#" onclick="toggle('targetdiv');return false;"><?php echo htmlspecialchars((isset($LANG['ENT_URL'])?$LANG['ENT_URL']:'Enter URL'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
											</div>
											<input type='hidden' name='MAX_FILE_SIZE' value='20000000' />
											<div>
												<input name='imgfile' id='imgfile' type='file' size='70'/>
											</div>
										</div>
										<div class="targetdiv" style="display:none;">
											<div style="float:right;text-decoration:underline;font-weight:bold;">
												<a href="#" onclick="toggle('targetdiv');return false;">
													<?php echo (isset($LANG['UP_LOCAL'])?$LANG['UP_LOCAL']:'Upload Local Image'); ?>
												</a>
											</div>
											<div style="margin-bottom:10px;">
												<?php echo (isset($LANG['ENT_URL_LONG'])?$LANG['ENT_URL_LONG']:'Enter a URL to an image already located on a web server'); ?>
											</div>
											<div>
												<b><?php echo (isset($LANG['IMG_URL'])?$LANG['IMG_URL']:'Image URL'); ?>:</b><br/>
												<input type='text' name='imgurl' id='imgurl' size='70'/>
											</div>
										</div>
									</div>
									<div style="clear:both;padding-top:4px;float:left;">
										<div style="float:left;">
											<b><?php echo (isset($LANG['CREAT_BY'])?$LANG['CREAT_BY']:'Created By'); ?>:</b>
										</div>
										<div style="float:left;margin-left:10px;">
											<input name="createdBy" type="text" style="width:380px;" />
										</div>
									</div>
									<div style="clear:both;padding-top:4px;float:left;">
										<div style="float:left;">
											<b><?php echo (isset($LANG['STRUCTURES'])?$LANG['STRUCTURES']:'Structures'); ?>:</b>
										</div>
										<div style="float:left;margin-left:10px;">
											<input name="structures" type="text" style="width:380px;" />
										</div>
									</div>
									<div style="clear:both;padding-top:4px;float:left;">
										<div style="float:left;">
											<b><?php echo (isset($LANG['NOTES'])?$LANG['NOTES']:'Notes'); ?>:</b>
										</div>
										<div style="float:left;margin-left:10px;">
											<input name="notes" type="text" style="width:380px;" />
										</div>
									</div>
									<div style="clear:both;padding-top:8px;float:right;">
										<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
										<button type="submit" name="formsubmit" value="Submit New Image"><?php echo (isset($LANG['SUB_IMG'])?$LANG['SUB_IMG']:'Submit New Image'); ?></button>
									</div>
								</fieldset>
							</form>
						</div>
						<div style="clear:both;">
							<?php
							if($termImgArr){
								foreach($termImgArr as $imgId => $imgArr){
									$termImage = false;
									if($imgArr["glossid"] == $glossId){
										$termImage = true;
										$hasImages = true;
									}
									?>
									<fieldset>
										<div style="float:right;cursor:pointer;" onclick="toggle('img<?php echo $imgId; ?>editdiv');" title="<?php echo (isset($LANG['EDIT_META'])?$LANG['EDIT_META']:'Edit Image MetaData'); ?>">
											<img style="border:0px;width:1.3em;" src="../images/edit.png" />
										</div>
										<div style="float:left;">
											<?php
											$imgUrl = $imgArr["url"];
											if(array_key_exists('IMAGE_DOMAIN', $GLOBALS)){
												if(substr($imgUrl,0,1)=="/"){
													$imgUrl = $GLOBALS['IMAGE_DOMAIN'] . $imgUrl;
												}
											}
											$displayUrl = $imgUrl;
											?>
											<a href="<?php echo htmlspecialchars($imgUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>" target="_blank">
												<img src="<?php echo $displayUrl;?>" style="width:250px;" title="<?php echo $imgArr["structures"]; ?>" />
											</a>
										</div>
										<div style="float:left;margin-left:10px;padding:10px;width:350px;">
											<div style="">
												<?php
												if($imgArr["createdBy"]){
													?>
													<div style="overflow:hidden;">
														<b><?php echo (isset($LANG['CREAT_BY'])?$LANG['CREAT_BY']:'Structures'); ?>:</b>
														<?php echo wordwrap($imgArr["createdBy"], 150, "<br />\n"); ?>
													</div>
													<?php
												}
												if($imgArr["structures"]){
													?>
													<div style="overflow:hidden;">
														<b><?php echo (isset($LANG['STRUCTURES'])?$LANG['STRUCTURES']:'Structures'); ?>:</b>
														<?php echo wordwrap($imgArr["structures"], 150, "<br />\n"); ?>
													</div>
													<?php
												}
												if($imgArr["notes"]){
													?>
													<div style="overflow:hidden;margin-top:8px;">
														<b><?php echo (isset($LANG['NOTES'])?$LANG['NOTES']:'Notes'); ?>:</b>
														<?php echo wordwrap($imgArr["notes"], 150, "<br />\n"); ?>
													</div>
													<?php
												}
												?>
											</div>
										</div>
										<div id="img<?php echo $imgId; ?>editdiv" style="display:none;clear:both;">
											<form name="img<?php echo $imgId; ?>editform" action="termdetails.php" method="post" onsubmit="return verifyImageEditForm(this);">
												<fieldset style="">
													<legend><?php echo (isset($LANG['EDIT_IMG'])?$LANG['EDIT_IMG']:'Edit Image Data'); ?></legend>
													<div style="clear:both;">
														<div style="float:left;">
															<b><?php echo (isset($LANG['CREAT_BY'])?$LANG['CREAT_BY']:'Structures'); ?>:</b>
														</div>
														<div style="float:left;margin-left:10px;">
															<input name="createdBy" type="text" value="<?php echo $imgArr['createdBy']; ?>" style="width:380px;"  />
														</div>
													</div>
													<div style="clear:both;">
														<div style="float:left;">
															<b><?php echo (isset($LANG['STRUCTURES'])?$LANG['STRUCTURES']:'Structures'); ?>:</b>
														</div>
														<div style="float:left;margin-left:10px;">
															<input name="structures" type="text" value="<?php echo $imgArr['structures']; ?>" style="width:380px;" />
														</div>
													</div>
													<div style="clear:both;padding-top:10px;">
														<div style="float:left;">
															<b><?php echo (isset($LANG['NOTES'])?$LANG['NOTES']:'Notes'); ?>:</b>
														</div>
														<div style="float:left;margin-left:10px;">
															<input name="notes" type="text" value="<?php echo $imgArr['notes']; ?>" style="width:380px;" />
														</div>
													</div>
													<div style="clear:both;">
														<div style="padding-top:8px;">
															<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
															<input name="glimgid" type="hidden" value="<?php echo $imgId; ?>" />
															<input name="tabindex" type="hidden" value="3" />
															<button name="formsubmit" type="submit" value="Save Image Edits"><?php echo (isset($LANG['SAVE_IMG'])?$LANG['SAVE_IMG']:'Save Image Edits'); ?></button>
														</div>
													</div>
												</fieldset>
											</form>
											<?php
											if($termImage){
												?>
												<form name="img<?php echo $imgId; ?>delform" action="termdetails.php" method="post">
													<fieldset style="width: 300px">
														<legend><?php echo (isset($LANG['DEL_IMG'])?$LANG['DEL_IMG']:'Delete Image'); ?></legend>
														<div style="">
															<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
															<input name="glimgid" type="hidden" value="<?php echo $imgId; ?>" />
															<input name="tabindex" type="hidden" value="3" />
															<button class="button-danger" name="formsubmit" type="submit" value="Delete Image" onclick="return confirm(<?php echo (isset($LANG['SURE_DEL_IMG'])?$LANG['SURE_DEL_IMG']:'Are you sure you want to permanently delete this image?'); ?>);"><?php echo (isset($LANG['DEL_IMG'])?$LANG['DEL_IMG']:'Delete Image'); ?></button>
														</div>
													</fieldset>
												</form>
												<?php
											}
											?>
										</div>
									</fieldset>
									<?php
								}
							}
							?>
						</div>
					</div>
				</div>

				<div id="termadmindiv" style="">
					<form name="deltermform" action="termdetails.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this term?')">
						<fieldset style="width:350px;margin:20px;padding:20px;">
							<legend><b><?php echo (isset($LANG['DEL_T'])?$LANG['DEL_T']:'Delete Term'); ?></b></legend>
							<?php
							if($hasImages){
								echo '<div style="font-weight:bold;margin-bottom:15px;">';
								echo (isset($LANG['CANT_DEL'])?$LANG['CANT_DEL']:'Term cannot be deleted until all linked images are deleted');
								echo '</div>';
							}
							?>
							<button class="button-danger" name="formsubmit" type="submit" value="Delete Term" <?php if($hasImages) echo (isset($LANG['DISABLED'])?$LANG['DISABLED']:'DISABLED'); ?>>
								Delete Term
							</button>
							<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
						</fieldset>
					</form>
				</div>
			</div>
			<?php
		}
		else{
			echo '<h2>'.(isset($LANG['ERROR'])?$LANG['ERROR']:'Permissions or data error, please contact system administrator').'</h2>';
		}
		?>
	</div>
	<?php
	//include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
