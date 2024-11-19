<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/misc/collprops.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCollectionProperty.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/misc/collectionproperties.' . $LANG_TAG . '.php')){
	include_once($SERVER_ROOT.'/content/lang/collections/misc/collectionproperties.' . $LANG_TAG . '.php');
}
else include_once($SERVER_ROOT . '/content/lang/collections/misc/collectionproperties.en.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$collid = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$action = array_key_exists('submitaction' , $_POST) ? $_POST['submitaction'] : '';

$propManager = new OccurrenceCollectionProperty();
$propManager->setCollid($collid);
$collMeta = $propManager->getCollMetaArr();

$isEditor = 0;
if($SYMB_UID){
	if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))){
		$isEditor = 1;
	}
}

$statusStr = '';
if($isEditor){
	if($action == 'convertFormat'){
		if($propManager->transferDynamicProperties()) $statusStr = '<span style="color:green">' . $LANG['PROFILE_UPDATE_SUCCESS'] . '</span>';
		else $statusStr = '<span style="color:red">' . $LANG['ERROR'] . ':</span> ' . $LANG['UPDATING_COLLEC_PROFILE'] . ' : '.$propManager->getErrorMessage();
	}
	elseif($action == 'saveTitleOverride'){

	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $collMeta['collName'] . ': ' . $LANG['SPECIAL_PROPS']; ?></title>
	<?php

	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script>
		function verifyAddContactForm(f){
			if(f.uid.value == ""){
				alert("<?php echo $LANG['SELECT_USER']; ?>");
				return false;
			}
			return true;
		}
	</script>
	<style type="text/css">
		fieldset{ padding: 10px }
		legend{ font-weight: bold }
		.fieldRowDiv{ clear:both; margin: 2px 0px; }
		.fieldDiv{ float:left; margin: 2px 10px 2px 0px; }
		.fieldLabel{ font-weight: bold; display: block; }
		.fieldDiv button{ margin-top: 10px; }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'><?= $LANG['HOME'] ?></a> &gt;&gt;
		<a href='collprofiles.php?emode=1&collid=<?= $collid ?>'><?= $LANG['SPECIAL_PROPS'] ?></a> &gt;&gt;
		<b><?= $LANG['COLLEC_MANAGEMENT_PROPERTIES']; ?></b>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $collMeta['collName'] . ': ' . $LANG['MANAGEMENT_PROP']; ?></h1>
		<?php
		if($isEditor){
			$conversionCode = $propManager->getSystemConvertionCode();
			if($conversionCode == 0){
				$statusStr = '<span style="color:orange">' . $LANG['WARNING'] . ':</span> ' . $LANG['COLLEC_PROP_NOT_ACTIVATED'];
			}
			elseif($conversionCode == 2){
				$statusStr = '<span style="color:orange">' . $LANG['WARNING'] . ':</span> ' . $LANG['OLD_COLLEC_PROPERTIES'];
				$statusStr .= '<form name="convertPropForm" action="collectionproperties.php" method="post" style="display:inline; margin-left:20px"><input name="collid" type="hidden" value="'.$collid.'" />';
				$statusStr .= '<button name="submitaction" type="submit" value="convertFormat">' . $LANG['CONVERT_TO_NEW_FORMAT'] . '</button>';
				$statusStr .= '</form>';
			}
			if($statusStr){
				echo '<fieldset><legend>' . $LANG['ACTION_PANEL'] . '</legend>';
				echo $statusStr;
				echo '</fieldset>';
			}
			$dynamicProps = $propManager->getDynPropArr();
			?>
			<fieldset style="margin:15px;padding:15px;">
				<legend><?= $LANG['PUB_PROPS'] ?></legend>
				<div style="margin:20px"><?= $LANG['OVERRIDE_COLLECTION_TITLE'] ?></div>
				<form name="pubPropForm" action="collectionproperties.php" method="post" onsubmit="return verifyPubPropForm(this)">
					<div style="margin:25px;clear:both;">
						<span class="fieldLabel"><?= $LANG['TITLE_OVERRIDE'] ?>: </span>
						<input name="titleOverride" type="text" value="<?= $dynamicProps['publicationProps']['titleOverride'] ?>" style="width:80%" />
					</div>
					<div style="margin:25px;">
						<input type="hidden" name="collid" value="<?= $collid; ?>" />
						<button name="submitaction" type="submit" value="saveTitleOverride"><?= $LANG['SAVE_TITLE_OVERRIDE']; ?></button>
					</div>
				</form>
			</fieldset>
			<fieldset style="margin:15px;padding:15px;">
				<legend><?= $LANG['OCC_EDIT_PROPS'] ?></legend>
				<?php
				$moduleArr = array();
				if(isset($dynamicProps['editorProperties']['module'])) $moduleArr = $dynamicProps['editorProps']['modules-panel'];
				?>
				<div class="fieldRowDiv">
					<div class="fieldDiv">
						<span class="fieldLabel"><?= $LANG['TITLE_OVERRIDE'] ?>: </span>
						<input name="paleomodule" type="checkbox" value="1" <?= (isset($moduleArr['paleo']['status']) && $moduleArr['paleo']['status']?'checked':''); ?> />
					</div>
				</div>
			</fieldset>
			<fieldset style="margin:15px;padding:15px;">
				<legend><?= $LANG['OCC_EDIT_PROPS'] ?></legend>
				<?php
				$moduleArr = array();
				if(isset($dynamicProps['editorProperties']['module'])) $moduleArr = $dynamicProps['editorProps']['modules-panel'];
				?>
				<div class="fieldRowDiv">
					<div class="fieldDiv">
						<span class="fieldLabel"><?= $LANG['TITLE_OVERRIDE'] ?>: </span>
						<input name="paleomodule" type="checkbox" value="1" <?= (isset($moduleArr['paleo']['status']) && $moduleArr['paleo']['status']?'checked':''); ?> />
					</div>
				</div>
			</fieldset>

			sesarTools', 'IGSN Profile
			labelFormat

			<?php
		}
		else echo '<h2>' . $LANG['NOT_AUTH'] . '</h2>';
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>