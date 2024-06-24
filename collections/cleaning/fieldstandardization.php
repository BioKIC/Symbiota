<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCleaner.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/cleaning/fieldstandardization.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/cleaning/fieldstandardization.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/cleaning/fieldstandardization.en.php');

header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$obsUid = array_key_exists('obsuid',$_REQUEST)?$_REQUEST['obsuid']:'';
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/fieldstandardization.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($obsUid)) $obsUid = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';


$cleanManager = new OccurrenceCleaner();
if($collid) $cleanManager->setCollId($collid);
$collMap = current($cleanManager->getCollMap());

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))
	|| ($collMap['colltype'] == 'General Observations')){
	$isEditor = 1;
}

//If collection is a general observation project, limit to User
if($collMap['colltype'] == 'General Observations' && $obsUid !== 0){
	$obsUid = $SYMB_UID;
	$cleanManager->setObsUid($obsUid);
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> <?php echo $LANG['FIELD_STANDARDIZATION'] ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	if(!$dupArr) include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php"><?php echo $LANG['HOME'] ?></a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo $LANG['COLLECTION_MANAGEMENT'] ?></a> &gt;&gt;
		<b><?php echo $LANG['BATCH_FIELD_TOOLS'] ?></b>
	</div>
	
	<!-- inner text -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?php echo $LANG['FIELD_STANDARDIZATION']; ?></h1>
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:20px;color:<?php echo (substr($statusStr,0,5)=='ERROR'?'red':'green');?>">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php
		}
		echo '<h2>'.$collMap['collectionname'].' ('.$collMap['code'].')</h2>';
		if($isEditor){
			?>
			<div>
				Description...
			</div>
			<?php
			if($action){

			}
			?>
			<fieldset style="padding:20px;">
				<legend><b><?php echo $LANG['COUNTRY'] ?></b></legend>
				<section class="flex-form">
					<div>
						<label for="country-old-field"><?php echo $LANG['OLD_FIELD'] ?>:</label>
						<select name="country-old-field" id="country-old-field">
							<option value=""><?php echo $LANG['SELECT_TARGET_FIELD'] ?></option>
							<option value="">--------------------------------</option>
							<?php
	
	
	
	
							?>
						</select>
					</div>
					<div>
						<label for="country-old-value"><?php echo $LANG['OLD_VALUE'] ?>:</label>
						<select name="country-old-value" id="country-old-value">
							<option value=""><?php echo $LANG['SELECT_TARGET_VALUE'] ?></option>
							<option value="">--------------------------------</option>
							<?php
	
	
	
	
							?>
						</select>
					</div>
				</section>
				<div style="margin:5px">
					<label for="country-new"><?php echo $LANG['REPLACEMENT_VALUE'] ?>:</label>
					<input name="country-new" id="country-new" type="text" value="" />
				</div>
			</fieldset>
			<?php
		}
		else{
			echo '<h2>' . $LANG['NOT_AUTHORIZED'] . '</h2>';
		}
		?>
	</div>
<?php
if(!$dupArr){
	include($SERVER_ROOT.'/includes/footer.php');
}
?>
</body>
</html>