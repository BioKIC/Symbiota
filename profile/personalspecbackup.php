<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
@include_once($SERVER_ROOT.'/content/lang/profile/personalspecbackup.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collId = $_REQUEST["collid"];
$action = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:'';
$cSet = array_key_exists("cset",$_REQUEST)?$_REQUEST["cset"]:'utf8';
$zipFile = array_key_exists("zipfile",$_REQUEST)?$_REQUEST["zipfile"]:0;

$dlManager = new ProfileManager();
$dlManager->setUid($SYMB_UID);

$editable = 0;
if($IS_ADMIN
	|| array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"])
	|| array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollEditor"])){
		$editable = 1;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $LANG['PERS_SPEC_BACKUP']; ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
</head>
<body>
<!-- This is inner text! -->
<div role="main" id="innertext">
	<h1 class="page-heading"><?= $LANG['PERS_SPEC_BACKUP']; ?></h1>
	<?php
	if($editable){
		if($action == 'Perform Backup'){
			echo '<ul>';
			$dlFile = $dlManager->dlSpecBackup($collId,$cSet,$zipFile);
			if($dlFile){
				echo '<li style="font-weight:bold;">'.(isset($LANG['BACK_COMPLETE'])?$LANG['BACK_COMPLETE']:'Backup Complete').'!</li>';
				echo '<li style="font-weight:bold;">'.(isset($LANG['CLICK'])?$LANG['CLICK']:'Click on file to download').': <a href="' . htmlspecialchars($dlFile, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($dlFile, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
				echo '</ul>';
			}
			echo '</ul>';
		}
		else{
			?>
			<form name="buform" action="personalspecbackup.php" method="post">
				<fieldset style="padding:15px;">
					<legend><?php echo (isset($LANG['DOWNLOAD_MOD'])?$LANG['DOWNLOAD_MOD']:'Download Module'); ?></legend>
					<div style="float:left;">
						<?php echo (isset($LANG['DATA_SET'])?$LANG['DATA_SET']:'Data Set'); ?>:
					</div>
					<div style="float:left;">
						<?php
						$cSet = str_replace('-','',strtolower($CHARSET));
						?>
						<input type="radio" name="cset" value="latin1" <?php echo ($cSet=='iso88591'?'checked':''); ?> /> <?php echo (isset($LANG['ISO'])?$LANG['ISO']:'ISO-8859-1 (western)'); ?><br/>
						<input type="radio" name="cset" value="utf8" <?php echo ($cSet=='utf8'?'checked':''); ?> /> <?php echo (isset($LANG['UTF'])?$LANG['UTF']:'UTF-8 (unicode)'); ?>
					</div>
					<div style="clear:both;">
						<input name="zipfile" type="checkbox" value="1" CHECKED />
						<?php echo (isset($LANG['COMPRESS'])?$LANG['COMPRESS']:'Compress data into a zip file'); ?>
					</div>
					<div style="clear:both;">
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<button type="submit" name="formsubmit" value="Perform Backup"><?php echo (isset($LANG['BACKUP'])?$LANG['BACKUP']:'Perform Backup'); ?></button>
					</div>
				</fieldset>
			</form>
			<?php
		}
	}
	?>
</div>
</body>
</html>