<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorImages.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/imageoccursubmit.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/imageoccursubmit.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/imageoccursubmit.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/imageoccursubmit.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid  = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$action = array_key_exists('action',$_POST)?$_POST['action']:'';

$occurManager = new OccurrenceEditorImages();
$occurManager->setCollid($collid);
$collMap = $occurManager->getCollMap();

$statusStr = '';
$isEditor = 0;
if($collid){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif(array_key_exists('CollAdmin', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollAdmin'])){
		$isEditor = 1;
	}
	elseif(array_key_exists('CollEditor', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollEditor'])){
		$isEditor = 1;
	}
}
if($isEditor){
	if($action == 'Submit Occurrence'){
		if($occurManager->addImageOccurrence($_POST)){
			$occid = $occurManager->getOccid();
			if($occid) $statusStr = $LANG['NEW_RECORD_CREATED'].': <a href="occurrenceeditor.php?occid=' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" rel="noopener">' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
		}
		else{
			$statusStr = $occurManager->getErrorStr();
		}
	}
}
if($collid && file_exists('includes/config/occurVarColl'.$collid.'.php')){
	//Specific to particular collection
	include('includes/config/occurVarColl'.$collid.'.php');
}
elseif(file_exists('includes/config/occurVarDefault.php')){
	//Specific to Default values for portal
	include('includes/config/occurVarDefault.php');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['IMAGE_SUBMIT']?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
    ?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../../js/symb/collections.imageoccursubmit.js?ver=1" type="text/javascript"></script>
	<script src="../../js/symb/collections.editor.tools.js?ver=1" type="text/javascript"></script>
	<script src="../../js/symb/shared.js?ver=141119" type="text/javascript"></script>
	<script type="text/javascript">
	function validateImgOccurForm(f){
		if(f.imgfile.value == "" && f.imgurl.value == ""){
			alert("<?php echo $LANG['SELECT_IMAGE']?>");
			return false;
		}
		else{
			if(f.imgfile.value != ""){
				var fName = f.imgfile.value.toLowerCase();
				if(fName.indexOf(".jpg") == -1 && fName.indexOf(".jpeg") == -1 && fName.indexOf(".gif") == -1 && fName.indexOf(".png") == -1){
					alert("<?php echo $LANG['IMAGE_TYPE']?>");
					return false;
				}
			}
			else if(f.imgurl.value != ""){
				var fileName = f.imgurl.value;
				if(fileName.substring(0,4).toLowerCase() != 'http'){
					alert("<?php echo $LANG['IMAGE_PATH_URL']?> ("+fileName.substring(0,4).toLowerCase()+")");
					return false
				}
				//Test to make sure file is correct mime type
				$.ajax({
					type: "POST",
					url: "rpc/getImageMime.php",
					async: false,
					data: { url: fileName }
				}).success(function( retStr ) {
					if(retStr == "image/jpeg" || retStr == "image/gif" || retStr == "image/png"){
						return true;
					}
					else{
						alert("<?php echo $LANG['IMAGE_FILE_TYPE']?>"+retStr+")");
						return false;
					}
				});
			}
		}
		return true;
	}
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php"><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE)?></a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo htmlspecialchars($LANG['COL_MNT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE)?></a> &gt;&gt;
		<b><?php echo $LANG['OCC_IMAGE_SUBMIT']?></b>
	</div>
	<div role="main" id="innertext">
		<h1 class="page-heading"><?php echo 'Occurrence Image Submission: ' . $collMap['collectionname']; ?></h1>
		<?php
		if($statusStr){
			echo '<div style="margin:15px;color:'.(stripos($statusStr,'error') !== false?'red':'green').';">'.$statusStr.'</div>';
		}
		if($isEditor){
			?>
			<form id='imgoccurform' name='imgoccurform' action='imageoccursubmit.php' method='post' enctype='multipart/form-data' onsubmit="return validateImgOccurForm(this)">
				<fieldset style="padding:15px;">
					<legend><b><?php echo $LANG['MANUAL_UPLOAD']?></b></legend>
					<div class="targetdiv">
						<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
						<div>
							<input name='imgfile' type='file' aria-label="<?php echo (isset($LANG['UPLOAD']) ? $LANG['UPLOAD'] : 'Upload the File'); ?>" />
						</div>
						<div id="newimagediv"></div>
						<div style="margin:10px 0px;">
							* <?php echo $LANG['WEB_READY_RECOMMENDED']?>
						</div>
					</div>
					<div class="targetdiv" style="display:none;">
						<div style="margin-bottom:10px;">
							<?php echo $LANG['ENTER_URL_EXPLAIN']?>
						</div>
						<div>
							<b><?php echo $LANG['IMAGE_URL']?>:</b><br/>
							<input type='text' name='imgurl' size='70' />
						</div>
						<div>
							<b><?php echo $LANG['MEDIUM_URL']?>:</b><br/>
							<input type='text' name='weburl' size='70' />
						</div>
						<div>
							<b><?php echo $LANG['THUMBNAIL_URL']?>:</b><br/>
							<input type='text' name='tnurl' size='70' />
						</div>
						<div>
							<input type="checkbox" name="copytoserver" value="1" <?php echo (isset($_POST['copytoserver'])&&$_POST['copytoserver']?'checked':''); ?> />
							<?php echo $LANG['COPY_LARGE']?>
						</div>
					</div>
					<div style="float:right;text-decoration:underline;font-weight:bold;">
						<div class="targetdiv">
							<a href="#" onclick="toggle('targetdiv');return false;"><?php echo $LANG['ENTER_URL']?></a>
						</div>
						<div class="targetdiv" style="display:none;">
							<a href="#" onclick="toggle('targetdiv');return false;"><?php echo $LANG['UPLOAD_LOCAL']?></a>
						</div>
					</div>
					<div>
						<input type="checkbox" id="nolgimage" name="nolgimage" value="1" <?php echo (isset($_POST['nolgimage'])&&$_POST['nolgimage']?'checked':''); ?>/>
						<label for="nolgimage"> <?php echo $LANG['DONT_MAP_LARGE']?> </label>
					</div>
					<div style="margin-top:10px;">
						<?php
						$processingStatusArr = array();
						if(isset($PROCESSINGSTATUS) && $PROCESSINGSTATUS){
							$processingStatusArr = $PROCESSINGSTATUS;
						}
						else{
							$processingStatusArr = array('unprocessed','unprocessed/NLP','stage 1','stage 2','stage 3','pending review-nfn','pending review','expert required','reviewed','closed');
						}
						?>
						<label for="processingstatus"> <b><?php echo (isset($LANG['PROCESSING_STATUS']) ? $LANG['PROCESSING_STATUS'] : 'Processing Status'); ?>:</b> </label>
						<select id="processingstatus" name="processingstatus">
							<option value=''><?php echo $LANG['NO_SET_STATUS']?></option>
							<option value=''>-------------------</option>
							<?php
							$pStatus = (isset($_POST['processingstatus']) ? $_POST['processingstatus'] : 'unprocessed');
							foreach($processingStatusArr as $v){
								$keyOut = strtolower($v);
								echo '<option value="'.$keyOut.'" '.($pStatus==$keyOut?'SELECTED':'').'>'.ucwords($v).'</option>';
							}
							?>
						</select>
					</div>
				</fieldset>
				<fieldset style="padding:15px;">
					<legend><b><?php echo $LANG['SKELETAL_DATA']?></b></legend>
					<div style="margin:3px;">
						<label for="catalognumber"> <b> <?php echo (isset($LANG['CAT_NUM']) ? $LANG['CAT_NUM'] : 'Catalog Number'); ?>:</b> </label>
						<input id="catalognumber" name="catalognumber" type="text" onchange="<?php if(!defined('CATNUMDUPECHECK') || CATNUMDUPECHECK) echo 'searchCatalogNumber(this.form, true)'; ?>" />
					</div>
					<div style="margin:3px;">
						<label for="sciname"> <b><?php echo (isset($LANG['SCINAME']) ? $LANG['SCINAME'] : 'Scientific Name');?>:</b> </label>
						<input id="sciname" name="sciname" type="text" value="<?php echo (isset($_POST['sciname']) ? $_POST['sciname'] : ''); ?>" style="width:300px"/>
						<input name="scientificnameauthorship" type="text" value="<?php echo (isset($_POST['scientificnameauthorship']) ? $_POST['scientificnameauthorship'] : ''); ?>" aria-label="<?php echo (isset($LANG['SCINAMEAUTH']) ? $LANG['SCINAMEAUTH'] : 'Scientific Name Authorship');?>" /><br/>
						<input type="hidden" id="tidinterpreted" name="tidinterpreted" value="<?php echo (isset($_POST['tidinterpreted']) ? $_POST['tidinterpreted'] : ''); ?>" />
						<label for="family"> <b><?php echo (isset($LANG['FAMILY']) ? $LANG['FAMILY'] : 'Family')?>:</b> </label>
						<input id="family" name="family" type="text" value="<?php echo (isset($_POST['family']) ? $_POST['family'] : ''); ?>" />
					</div>
					<div>
						<div style="float:left;margin:3px;">
							<label for="country"><b><?php echo (isset($LANG['COUNTRY']) ? $LANG['COUNTRY'] : 'Country')?>:</b><br/> </label>
							<input id="country" name="country" type="text" value="<?php echo (isset($_POST['country']) ? $_POST['country'] : ''); ?>" />
						</div>
						<div style="float:left;margin:3px;">
						<label for="state"><b><?php echo (isset($LANG['STATE_PROVINCE']) ? $LANG['STATE_PROVINCE'] : 'State/Province')?>:</b><br/> </label>
							<input id="state" name="stateprovince" type="text" value="<?php echo (isset($_POST['stateprovince']) ? $_POST['stateprovince'] : ''); ?>" />
						</div>
						<div style="float:left;margin:3px;">
						<label for="county"><b><?php echo (isset($LANG['COUNTY']) ? $LANG['COUNTY'] : 'County')?>:</b><br/> </label>
							<input id="county" name="county" type="text" value="<?php echo (isset($_POST['county']) ? $_POST['county'] : ''); ?>" />
						</div>
					</div>
					<div style="clear:both;margin:3px;">
						<?php
						if(isset($TESSERACT_PATH) && $TESSERACT_PATH){
							?>
							<div style="float:left;">
								<input name="tessocr" type="checkbox" value=1 <?php if(isset($_POST['tessocr'])) echo 'checked'; ?> />
								<?php echo $LANG['OCR_TEXT_ENGINE']?>
							</div>
							<?php
						}
						?>
						<div style="float:left;margin:8px 0px 0px 20px;">(<a href="#" onclick="toggle('manualocr')"><?php echo $LANG['MANUAL_OCR']?></a>)</div>
					</div>
					<div id="manualocr" style="clear:both;display:none;margin:3px;">
						<b><?php echo $LANG['OCR_TEXT']?></b><br/>
						<textarea name="ocrblock" style="width:100%;height:100px;"></textarea><br/>
						<b><?php echo $LANG['SOURCE']?>:</b> <input type="text" name="ocrsource" value="" />
					</div>
				</fieldset>
				<div style="margin:10px;clear:both;">
					<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
					<input type="submit" name="action" value="Submit Occurrence" />
					<input type="reset" name="reset" value="Reset Form" />
				</div>
			</form>
			<?php
		}
		else{
			echo $LANG['NOT_AUTH'].' ';
			echo '<br/><b>'.$LANG['CONTACT_ADMIN'].'</b> ';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>