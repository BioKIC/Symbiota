<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GlossaryManager.php');
include_once($SERVER_ROOT.'/content/lang/glossary/addterm.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../profile/index.php?refurl='.$CLIENT_ROOT.'/glossary/addterm.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$relatedGlossId = array_key_exists('relglossid',$_REQUEST)?$_REQUEST['relglossid']:0;
$taxaTid  = array_key_exists('taxatid',$_REQUEST)?$_REQUEST['taxatid']:0;
$taxaName  = array_key_exists('taxaname',$_REQUEST)?$_REQUEST['taxaname']:'';
$relationship = array_key_exists('relationship',$_REQUEST)?$_REQUEST['relationship']:'';
$relatedLanguage = array_key_exists('rellanguage',$_REQUEST)?$_REQUEST['rellanguage']:'';
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

//Sanitation
if(!is_numeric($relatedGlossId)) $relatedGlossId = 0;
if(!is_numeric($taxaTid)) $taxaTid = 0;
$taxaName = htmlspecialchars($taxaName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$relationship = htmlspecialchars($relationship, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$relatedLanguage = htmlspecialchars($relatedLanguage, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);

if(!$relatedLanguage) $relatedLanguage = $DEFAULT_LANG;
if($relatedLanguage == 'en') $relatedLanguage = 'English';
if($relatedLanguage == 'es') $relatedLanguage = 'Spanish';

$isEditor = false;
if($IS_ADMIN || array_key_exists('GlossaryEditor',$USER_RIGHTS)) $isEditor = true;

$glosManager = new GlossaryManager();

$closeWindow = false;
$statusStr = '';
if($isEditor){
	if($formSubmit == 'Create Term'){
		if($glosManager->createTerm($_POST)){
			if(isset($_POST['tid']) && $_POST['tid']){
				header('Location: termdetails.php?statusstr=successadd&glossid='.$glosManager->getGlossId());
			}
			elseif($relatedGlossId && isset($_POST['relation'])){
				if($_POST['relation'] == "translation"){
					header('Location: termdetails.php?glossid='.$relatedGlossId.'&statusstr=successadd#termtransdiv');
				}
				else{
					header('Location: termdetails.php?glossid='.$relatedGlossId.'&statusstr=successadd#termrelateddiv');
				}
			}
			else{
				$closeWindow = true;
			}
		}
		else{
			$statusStr = $glosManager->getErrorMessage();
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['GLOSSARY'] . ' - ' . $LANG['ADD_TERM']; ?> </title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			<?php
			if($closeWindow){
				echo 'window.opener.searchform.submit();';
				echo 'self.close();';
			}
			?>
		});

		function verifyNewTermForm(f){
			if(!f.term.value){
				alert("<?php echo (isset($LANG['PLEASE_ADD_TERM'])?$LANG['PLEASE_ADD_TERM']:'Please enter a value in the term field'); ?>");
				return false;
			}

			if(f.definition.value.length > 1998){
				if(!confirm("<?php echo (isset($LANG['WARNING_LONG'])?$LANG['WARNING_LONG']:'Warning, your definition is close to maximum size limit and may be cut off. Are you sure the definition is completely entered?'); ?>")) return false;
			}

			if(!f.language.value){
				alert("<?php echo (isset($LANG['PLEASE_LANG'])?$LANG['PLEASE_LANG']:'Please select a language'); ?>");
				return false;
			}

			var tidValue = '';
			if(f.tid) tidValue = f.tid.value;
			if(!f.relglossid.value && !tidValue){
				alert("<?php echo (isset($LANG['PLEASE_TAXON'])?$LANG['PLEASE_TAXON']:'Please enter a taxonomic group or a related term to which new term will be linked'); ?>");
				return false;
			}

			$.ajax({
				type: "POST",
				url: "rpc/checkterm.php",
				data: { term: f.term.value, language: f.language.value, tid: tidValue, relglossid: f.relglossid.value }
			}).success(function( data ) {
				if(data == "1") alert("<?php echo (isset($LANG['TERM_EXISTS'])?$LANG['TERM_EXISTS']:'Term already exists in database in that language and taxonomic group.'); ?>");
				else f.submit();
			});

			return false;
		}

	</script>
	<script src="../js/symb/glossary.index.js?ver=20160720" type="text/javascript"></script>
</head>
<body>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Add New Glossary Term</h1>
		<?php
		if($statusStr){
			?>
			<div style="margin:15px;color:red;">
				<?php echo $statusStr; ?>
			</div>
			<?php
		}
		if($isEditor){
			?>
			<div id="newtermdiv" style="margin-bottom:10px;">
				<form name="newtermform" action="addterm.php" method="post" onsubmit="return verifyNewTermForm(this)">
					<fieldset>
						<legend><b><?php echo (isset($LANG['ADD_TERM'])?$LANG['ADD_TERM']:'Add New Term'); ?></b></legend>
						<div style="clear:both;padding-top:4px;">
							<div style="float:left;">
								<b><?php echo (isset($LANG['TERM'])?$LANG['TERM']:'Term'); ?>: </b>
							</div>
							<div style="float:left;margin-left:10px;">
								<input type="text" name="term" id="term" maxlength="150" style="width:350px;" value="" onchange="" title="" />
							</div>
						</div>
						<div style="clear:both;padding-top:4px;width:100%;">
							<div style="float:left;">
								<b><?php echo (isset($LANG['DEFINITION'])?$LANG['DEFINITION']:'Definition'); ?>: </b>
							</div>
							<div style="float:left;margin-left:10px;width:95%;">
								<textarea name="definition" id="definition" rows="10" maxlength="2000" style="width:100%;height:200px;" ></textarea>
							</div>
						</div>
						<div style="clear:both;padding-top:4px;">
							<div style="float:left;">
								<b><?php echo (isset($LANG['LANGUAGE'])?$LANG['LANGUAGE']:'Language'); ?>: </b>
							</div>
							<div style="float:left;margin-left:10px;">
								<select id="langSelect" name="language">
									<option value=""><?php echo (isset($LANG['SELECT_LANG'])?$LANG['SELECT_LANG']:'Select a Language'); ?></option>
									<option value="">---------------------------</option>
									<?php
									$langArr = $glosManager->getLanguageArr('all');
									foreach($langArr as $langKey => $langValue ){
										if($relationship != 'translation' || $relatedLanguage != $langValue){
											echo '<option '.($relatedLanguage==$langValue || $relatedLanguage==$langKey?'SELECTED':'').'>'.$langValue.'</option>';
										}
									}
									?>
								</select>
								<a href="#" onclick="toggle('addLangDiv');return false;"><img src="../images/add.png" style="width:1.5em" /></a>&nbsp;&nbsp;
							</div>
							<div id="addLangDiv" style="float:left;display:none">
								<input name="newlang" type="text" maxlength="45" style="width:200px;" />
								<button onclick="addNewLang(this.form);return false;"><?php echo (isset($LANG['ADD_LANG'])?$LANG['ADD_LANG']:'Add language'); ?></button>
							</div>
						</div>
						<div style="clear:both;padding-top:4px;">
							<div style="float:left;">
								<b><?php echo (isset($LANG['AUTHOR'])?$LANG['AUTHOR']:'Author'); ?>: </b>
							</div>
							<div style="float:left;margin-left:10px;">
								<input type="text" name="author" maxlength="250" style="width:400px;" />
							</div>
						</div>
						<div style="clear:both;padding-top:4px;">
							<div style="float:left;">
								<b><?php echo (isset($LANG['TRANSLATOR'])?$LANG['TRANSLATOR']:'Translator'); ?>: </b>
							</div>
							<div style="float:left;margin-left:10px;">
								<input type="text" name="translator" maxlength="250" style="width:400px;" />
							</div>
						</div>
						<div style="clear:both;padding-top:4px;">
							<div style="float:left;">
								<b><?php echo (isset($LANG['SOURCE'])?$LANG['SOURCE']:'Source'); ?>: </b>
							</div>
							<div style="float:left;margin-left:10px;">
								<input type="text" name="source" maxlength="1000" style="width:700px;" />
							</div>
						</div>
						<div style="clear:both;padding-top:4px;">
							<div style="float:left;">
								<b><?php echo (isset($LANG['NOTES'])?$LANG['NOTES']:'Notes'); ?>: </b>
							</div>
							<div style="float:left;margin-left:10px;">
								<input type="text" name="notes" maxlength="250" style="width:700px;" />
							</div>
						</div>
						<div style="clear:both;padding-top:4px;">
							<div style="float:left;">
								<b><?php echo (isset($LANG['RES_URL'])?$LANG['RES_URL']:'Resource URL'); ?>: </b>
							</div>
							<div style="float:left;margin-left:10px;">
								<input type="text" name="resourceurl" maxlength="600" style="width:700px;" />
							</div>
						</div>
						<div style="clear:both;"></div>
						<div style="clear:both;">
							<fieldset style="padding:10px;margin-top:12px;">
								<?php
								if(!$relatedGlossId){
									?>
									<div style="">
										<?php echo (isset($LANG['PLEASE_TAXON_2'])?$LANG['PLEASE_TAXON_2']:'Please enter the taxonomic group (higher than family) to which this term applies <b>OR</b> link new term to a related existing term'); ?>
									</div>
									<div style="padding:4px;">
										<div style="">
											<b><?php echo (isset($LANG['TAX_GROUP'])?$LANG['TAX_GROUP']:'Taxonomic Group'); ?>: </b>
										</div>
										<div style="">
											<input id="taxagroup" name="taxagroup" type="text" maxlength="45" style="width:250px;" value="<?php echo $taxaName; ?>" />
											<input id="tid" name="tid" type="hidden" value="<?php echo $taxaTid; ?>" />
										</div>
									</div>
									<div style="padding:10px;">
										<b><?php echo '-- '.(isset($LANG['OR'])?$LANG['OR']:'OR').' --'; ?></b>
									</div>
									<?php
								}
								?>
								<div style="padding:4px;font-weight:bold;text-decoration:underline;"><?php echo (isset($LANG['LINK_REL_TERM'])?$LANG['LINK_REL_TERM']:'Link to related term'); ?></div>
								<div style="margin:10px">
									<div style="margin:3px">
										<b><?php echo (isset($LANG['REL'])?$LANG['REL']:'Relationship'); ?>:</b>
										<select name="relation" <?php if($relationship) echo 'readonly'; ?>>
											<option value=""><?php echo (isset($LANG['SEL_REL'])?$LANG['SEL_REL']:'Select Relationship'); ?></option>
											<option value="">----------------------------</option>
											<option value="synonym" <?php echo ($relationship=='synonym'?'selected':''); ?>><?php echo (isset($LANG['SYN'])?$LANG['SYN']:'Synonym'); ?></option>
											<option value="translation" <?php echo ($relationship=='translation'?'selected':''); ?>><?php echo (isset($LANG['TRANSLATION'])?$LANG['TRANSLATION']:'Translation'); ?></option>
										</select>
									</div>
									<div style="margin:3px">
										<b><?php echo (isset($LANG['REL_TERM'])?$LANG['REL_TERM']:'Related Term'); ?>:</b>
										<select name="relglossid" <?php if($relatedGlossId) echo 'readonly'; ?>>
											<option value=""><?php echo (isset($LANG['SEL_TERM'])?$LANG['SEL_TERM']:'Select Term to be Linked'); ?></option>
											<option value="">----------------------------</option>
											<?php
											$termList = $glosManager->getTermList($relatedGlossId,$relatedLanguage);
											foreach($termList as $id => $termName){
												echo '<option value="'.$id.'" '.($relatedGlossId==$id?'selected':'').'>'.$termName.'</option>';
											}
											?>
										</select>
									</div>
								</div>
							</fieldset>
						</div>
						<div style="clear:both;padding:25px;">
							<input name="formsubmit" type="hidden" value="Create Term" />
							<button name="submitbutton" type="submit" value="Create Term"><?php echo (isset($LANG['CREATE_TERM'])?$LANG['CREATE_TERM']:'Create Term'); ?></button>
						</div>
					</fieldset>
				</form>
			</div>
			<?php
		}
		else{
			echo '<div style="font-size:120%;font-weight:bold;margin:20px">'.(isset($LANG['NO_PERM'])?$LANG['NO_PERM']:'You do not have editing permissions for glossary').'</div>';
		}
		?>
	</div>
</body>
</html>