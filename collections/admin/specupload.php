<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecUploadBase.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/admin/specupload.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/admin/specupload.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/collections/admin/specupload.en.php');

header('Content-Type: text/html; charset='.$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/specupload.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT);
$uploadType = array_key_exists('uploadtype', $_REQUEST) ? filter_var($_REQUEST['uploadtype'], FILTER_SANITIZE_NUMBER_INT) : 0;
$uspid = array_key_exists('uspid', $_REQUEST) ? filter_var($_REQUEST['uspid'], FILTER_SANITIZE_NUMBER_INT) : 0;

$DIRECTUPLOAD = 1; $SKELETAL = 7; $IPTUPLOAD = 8; $NFNUPLOAD = 9; $STOREDPROCEDURE = 4; $SCRIPTUPLOAD = 5; $SYMBIOTA = 13;

$duManager = new SpecUploadBase();

$duManager->setCollId($collid);
$duManager->setUspid($uspid);
$duManager->readUploadParameters();
if($uploadType) $duManager->setUploadType($uploadType);
else $uploadType = $duManager->getUploadType();

$isEditor = 0;
if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))){
	$isEditor = 1;
}
if($uploadType == $IPTUPLOAD || $uploadType == $SYMBIOTA){
	if($duManager->getPath()) header('Location: specuploadmap.php?uploadtype='.$uploadType.'&uspid='.$uspid.'&collid='.$collid);
}
elseif($uploadType == $DIRECTUPLOAD || $uploadType == $STOREDPROCEDURE || $uploadType == $SCRIPTUPLOAD){
	header('Location: specuploadprocessor.php?uploadtype='.$uploadType.'&uspid='.$uspid.'&collid='.$collid);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['SPEC_UPLOAD']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../../js/symb/shared.js" type="text/javascript"></script>
	<script>
		function verifyFileUploadForm(f){
			var fileName = "";
			if(f.uploadfile || f.ulfnoverride){
				if(f.uploadfile && f.uploadfile.value){
					 fileName = f.uploadfile.value;
				}
				else{
					fileName = f.ulfnoverride.value;
				}
				if(fileName == ""){
					alert("<?php echo $LANG['PATH_EMPTY']; ?>");
					return false;
				}
				else{
					var ext = fileName.split('.').pop();
					if(ext == 'csv' || ext == 'CSV') return true;
					else if(ext == 'zip' || ext == 'ZIP') return true;
					else if(ext == 'txt' || ext == 'TXT') return true;
					else if(ext == 'tab' || ext == 'tab') return true;
					else if(fileName.substring(0,4) == 'http') return true;
					else{
						alert("<?php echo $LANG['MUST_CSV']; ?>");
						return false;
					}
				}
			}
			return true;
		}

		function verifyFileSize(inputObj){
			inputObj.form.ulfnoverride.value = ''
			if (!window.FileReader) {
				//alert("The file API isn't supported on this browser yet.");
				return;
			}
			<?php
			$maxUpload = ini_get('upload_max_filesize');
			$maxUpload = str_replace("M", "000000", $maxUpload);
			if($maxUpload > 100000000) $maxUpload = 100000000;
			echo 'var maxUpload = '.$maxUpload.";\n";
			?>
			var file = inputObj.files[0];
			if(file.size > maxUpload){
				var msg = "<?php echo $LANG['IMPORT_FILE']; ?>"+file.name+" ("+Math.round(file.size/100000)/10+"<?php echo $LANG['IS_BIGGER'] . ' '; ?>"+(maxUpload/1000000)+"MB).";
				if(file.name.slice(-3) != "zip") msg = msg + "<?php echo $LANG['MAYBE_ZIP']; ?>";
				alert(msg);
			}
		}
	</script>
</head>
<body>
	<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class="navpath">
	<a href="../../index.php"><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
	<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo htmlspecialchars($LANG['COL_MGMNT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
	<a href="specuploadmanagement.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($LANG['LIST_UPLOAD'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
	<b><?php echo $LANG['SPEC_UPLOAD']; ?></b>
</div>
<div role="main" id="innertext">
	<h1 class="page-heading"><?= $LANG['UP_MODULE']; ?></h1>
	<?php
	if($isEditor && $collid){
		//Grab collection name and last upload date and display for all
		echo '<div style="font-weight:bold;font-size:130%;">'.$duManager->getCollInfo('name').'</div>';
		echo '<div style="margin:0px 0px 15px 15px;"><b>' . $LANG['LAST_UPLOAD_DATE'] . ':</b> ' . ($duManager->getCollInfo('uploaddate')?$duManager->getCollInfo('uploaddate'):$LANG['NOT_REC']) . '</div>';
		?>
		<form name="fileuploadform" action="specuploadmap.php" method="post" enctype="multipart/form-data" onsubmit="return verifyFileUploadForm(this)">
			<fieldset style="width:95%;">
				<legend style="font-weight:bold;font-size:120%;<?php if($uploadType == $SKELETAL) echo 'background-color:lightgreen'; ?>"><?php echo $duManager->getTitle() . ': ' . $LANG['ID_SOURCE']; ?></legend>
				<div>
					<div style="margin:10px">
						<?php
						$pathLabel = $LANG['IPT_URL'];
						if($uploadType != $IPTUPLOAD){
							$pathLabel = $LANG['RESOURCE_URL'];
							?>
							<div>
								<input name="uploadfile" type="file" onchange="verifyFileSize(this)" aria-label="<?php echo $LANG['UPLOAD'] ?>" />
							</div>
							<?php
						}
						?>
						<div class="ulfnoptions" style="display:<?php echo ($uploadType!=$IPTUPLOAD?'none':''); ?>;margin:15px 0px">
							<b><?php echo $pathLabel; ?>:</b>
							<input name="ulfnoverride" type="text" size="70" /><br/>
							<?php
							if($uploadType != $IPTUPLOAD) echo '* ' . $LANG['WORKAROUND'];
							?>
						</div>
						<?php
						if($uploadType != $IPTUPLOAD){
							?>
							<div class="ulfnoptions">
								<a href="#" onclick="toggle('ulfnoptions');return false;"><?php echo htmlspecialchars($LANG['DISPLAY_OPS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
							</div>
							<?php
						}
						?>
					</div>
					<div style="margin:10px;">
						<?php
						if(!$uspid && $uploadType != $NFNUPLOAD)
							echo '<input id="automap" name="automap" type="checkbox" value="1" CHECKED /> <label for="automap"><b>' . $LANG['AUTOMAP'] . '</b></label><br/>';
						?>
					</div>
					<div style="margin:10px;">
						<button name="action" type="submit" value="Analyze File"><?php echo $LANG['ANALYZE_FILE']; ?></button>
						<input name="uspid" type="hidden" value="<?php echo $uspid; ?>" />
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="uploadtype" type="hidden" value="<?php echo $uploadType; ?>" />
						<input name="MAX_FILE_SIZE" type="hidden" value="100000000" />
					</div>
				</div>
			</fieldset>
		</form>
		<?php
	}
	else{
		if(!$isEditor || !$collid) echo '<div style="font-weight:bold;font-size:120%;">' . $LANG['NOT_AUTH'] . '</div>';
		else{
			echo '<div style="font-weight:bold;font-size:120%;">';
			echo $LANG['PAGE_ERROR'] . ' = ';
			echo ini_get("upload_max_filesize").'; post_max_size = '.ini_get('post_max_size');
			echo $LANG['USE_BACK'];
			echo '</div>';
		}
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>