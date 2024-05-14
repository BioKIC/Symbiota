<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GlossaryManager.php');
include_once($SERVER_ROOT.'/content/lang/glossary/sources.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$tid = array_key_exists('tid', $_REQUEST) ? filter_var($_REQUEST['tid'], FILTER_SANITIZE_NUMBER_INT) : '';
$searchTerm = array_key_exists('keyword',$_REQUEST)?$_REQUEST['keyword']:'';
$language = array_key_exists('language',$_REQUEST)?$_REQUEST['language']:'';
$taxa = array_key_exists('taxa',$_REQUEST)?$_REQUEST['taxa']:'';
$editMode = array_key_exists('emode',$_REQUEST)?1:0;

$isEditor = false;
if($IS_ADMIN || array_key_exists('GlossaryEditor',$USER_RIGHTS)) $isEditor = true;

$glosManager = new GlossaryManager();
$sourceArr = $glosManager->getTaxonSources($tid);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . $LANG['G_SOURCES']; ?></title>
	<link href="<?= $CSS_BASE_PATH ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="../js/symb/glossary.index.js"></script>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../index.php'><?php echo (isset($LANG['HOME'])?$LANG['HOME']:'Home'); ?></a> &gt;&gt;
		<a href='index.php'> <b><?php echo (isset($LANG['MAIN_G'])?$LANG['MAIN_G']:'Main Glossary'); ?></b></a> &gt;&gt;
		<b><?php echo (isset($LANG['G_CONTR'])?$LANG['G_CONTR']:'Glossary Contributors'); ?></b>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['G_SOURCES']; ?></h1>
		<?php
		if($editMode){
			if($isEditor){
				?>
				<div id="sourcedetaildiv" style="">
					<div id="termdetails" style="overflow:auto;">
						<form name="sourceeditform" action="index.php" method="post">
							<div style="padding-top:4px">
								<div>
									<b><?php echo (isset($LANG['TERM_CONTR'])?$LANG['TERM_CONTR']:'Terms and Definitions contributed by'); ?>: </b>
								</div>
								<div>
									<textarea name="contributorTerm" id="contributorTerm" rows="10" maxlength="1000" style="width:95%;height:40px;resize:vertical;" ><?php echo ($sourceArr?$sourceArr[$tid]['contributorTerm']:''); ?></textarea>
								</div>
							</div>
							<div style="padding-top:4px;">
								<div>
									<b><?php echo (isset($LANG['IMG_CONTR'])?$LANG['IMG_CONTR']:'Images contributed by'); ?>: </b>
								</div>
								<div>
									<textarea name="contributorImage" id="contributorImage" rows="10" maxlength="1000" style="width:95%;height:40px;resize:vertical;" ><?php echo ($sourceArr?$sourceArr[$tid]['contributorImage']:''); ?></textarea>
								</div>
							</div>
							<div style="padding-top:4px;">
								<div>
									<b><?php echo (isset($LANG['TRANS_BY'])?$LANG['TRANS_BY']:'Translations by'); ?>: </b>
								</div>
								<div>
									<textarea name="translator" id="translator" rows="10" maxlength="1000" style="width:95%;height:40px;resize:vertical;" ><?php echo ($sourceArr?$sourceArr[$tid]['translator']:''); ?></textarea>
								</div>
							</div>
							<div style="padding-top:4px;">
								<div>
									<b><?php echo (isset($LANG['TRAN_IMG_BY'])?$LANG['TRAN_IMG_BY']:'Translations and images were also sourced from the following references'); ?>: </b>
								</div>
								<div>
									<textarea name="additionalSources" id="additionalSources" rows="10" maxlength="1000" style="width:95%;height:150px;resize:vertical;" ><?php echo ($sourceArr?$sourceArr[$tid]['additionalSources']:''); ?></textarea>
								</div>
							</div>
							<div>
								<input name="tid" type="hidden" value="<?php echo $tid; ?>" />
								<input name="searchterm" type="hidden" value="<?= htmlspecialchars($searchTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>" />
								<input name="searchlanguage" type="hidden" value="<?= htmlspecialchars($language, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>" />
								<input name="searchtaxa" type="hidden" value="<?= htmlspecialchars($taxa, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>" />
							</div>
							<?php
							if($sourceArr){
								?>
								<div style="margin:20px;">
									<button name="formsubmit" type="submit" value="Edit Source"><?php echo (isset($LANG['SAVE'])?$LANG['SAVE']:'Save Edits'); ?></button>
								</div>
								<div style="margin:20px;">
									<button class="button-danger" name="formsubmit" type="submit" value="Delete Source" onclick="return confirm(<?php echo (isset($LANG['SURE_DEL'])?$LANG['SURE_DEL']:'Are you sure you want to delete this source?'); ?>)"><?php echo (isset($LANG['DEL_SRC'])?$LANG['DEL_SRC']:'Delete Source'); ?></button>
								</div>
								<?php
							}
							else{
								echo '<div style="margin:20px;"><button name="formsubmit" type="submit" value="Add Source">'.(isset($LANG['ADD_SRC'])?$LANG['ADD_SRC']:'Add Source').'</button></div>';
							}
							?>
						</form>
					</div>
				</div>
				<?php
			}
			else{
				echo '<h2>'.(isset($LANG['CANT_EDIT'])?$LANG['CANT_EDIT']:'You need to login or perhaps do not have the necessary permissions to edit glossary data, please contact your portal manager').'</h2>';
			}
		}
		else{
			//Display list of contributors
			if($sourceArr){
				echo '<h1>'.(isset($LANG['CONTRS'])?$LANG['CONTRS']:'Contributors').'</h1>';
				foreach($sourceArr as $tid => $sArr){
					echo '<div style="font-size:130%;margin:25px 10px 0px 10px;"><i><b><u>'.$sArr['sciname'].'</u></b></i></div>';
					if($sArr['contributorTerm']){
						echo '<div style="margin:8px 10px 0px 20px;"><i>'.(isset($LANG['TERM_CONTR'])?$LANG['TERM_CONTR']:'Terms and Definitions contributed by').':</i></div>';
						$termArr = explode(';', $sArr['contributorTerm']);
						foreach($termArr as $term){
							$term = '<b>'.str_replace('-', '</b>-', $term).(strpos($term,'-')?'':'</b>');
							echo '<div style="margin:8px 10px 0px 30px;">'.$term.'</div>';
						}
					}
					if($sArr['contributorImage']){
						echo '<div style="margin:8px 10px 0px 20px;"><i>'.(isset($LANG['IMG_CONTR'])?$LANG['IMG_CONTR']:'Images contributed by').':</i></div>';
						$termArr = explode(';', $sArr['contributorImage']);
						foreach($termArr as $term){
							$term = '<b>'.str_replace('-', '</b>-', $term).(strpos($term,'-')?'':'</b>');
							echo '<div style="margin:8px 10px 0px 30px;">'.$term.'</div>';
						}
					}
					if($sArr['translator']){
						echo '<div style="margin:8px 10px 0px 20px;"><i>'.(isset($LANG['TRANS_BY'])?$LANG['TRANS_BY']:'Translations by').':</i></div>';
						$termArr = explode(';', $sArr['translator']);
						foreach($termArr as $term){
							$term = '<b>'.str_replace('-', '</b>-', $term).(strpos($term,'-')?'':'</b>');
							echo '<div style="margin:8px 10px 0px 30px;">'.$term.'</div>';
						}
					}
					if($sArr['additionalSources']){
						echo '<div style="margin-top:8px;margin-left:10px;padding: 0px 10px;"><i>'.(isset($LANG['TRAN_IMG_BY'])?$LANG['TRAN_IMG_BY']:'Translations and images were also sourced from the following references').':</i></div>';
						echo '<div style="margin-top:8px;margin-left:20px;padding: 0px 10px;">'.$sArr['additionalSources'].'</div>';
					}
				}
			}
			else{
				echo '<div>'.(isset($LANG['NO_CONTRS'])?$LANG['NO_CONTRS']:'Contributor list is not available').'</div>';
			}
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
