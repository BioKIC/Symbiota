<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageCleaner.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/cleaning/imagerecycler.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/cleaning/imagerecycler.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/cleaning/imagerecycler.en.php');

header("Content-Type: text/html; charset=".$CHARSET);

$action = array_key_exists("submitaction",$_POST)?$_POST["submitaction"]:"";
$collid = $_REQUEST['collid'];

$isEditor = false;
if($IS_ADMIN){
	$isEditor = true;
}

$imgManager = new ImageCleaner();

if($isEditor){
	if($action == 'remove_images'){
		if($_POST['target_imgid']){
			$imgManager->setCollid($collid);
			$imgManager->recycleImagesFromStr($_POST['target_imgid']);
		}
		else{
			//Get image ids from input fields
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> <?php echo $LANG['IMAGE_RECYCLER'] ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript">
		function verifyRecycleForm(f){
			return true;
		}
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php"><?php echo $LANG['HOMEPAGE'] ?></a> &gt;&gt;
		<a href="../../collections/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo $LANG['COL_MAN_MEN'] ?></a> &gt;&gt;
		<b><?php echo $LANG['BULK_IMAGE_RECYCLER'] ?></b>
	</div>
	<?php
	if($collid){
		?>
		<div role="main" id="innertext">
			<h1 class="page-heading"><?php echo $LANG['IMAGE_RECYCLER'] ?></h1>
			<form name="imgdelform" action="imagerecycler.php" method="post" enctype="multipart/form-data" onsubmit="return verifyRecycleForm(this)">
				<fieldset style="width:90%;">
					<legend style="font-weight:bold;font-size:120%;"><?php echo $LANG['BATCH_IMAGE_REMOVER'] ?></legend>
					<div style="margin:10px;">
						<?php echo $LANG['BATCH_IMAGE_DELETION_TOOL'] ?>
					</div>
					<div style="margin:10px;">
						<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
						<input name="uploadfile" type="file" size="40" />
					</div>
					<div style="margin:10px;">
						<b><?php echo $LANG['IMAGE_IDENTIFIERS'] ?></b><br/>
						<textarea name="target_imgid" style="width:300px;height:100px;"></textarea>
					</div>
					<div style="margin:20px;">
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<button class="button-danger" type="submit" name="submitaction" value="remove_images"><?php echo $LANG['BULK_REMOVE_IMAGE_FILES'] ?></button>
					</div>
				</fieldset>
			</form>
		</div>
		<?php
	}
	else{
		echo '<b>' . $LANG['ERROR_COLLECTION'] . '</b>';
	}
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
