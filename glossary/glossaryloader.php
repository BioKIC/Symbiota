<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GlossaryUpload.php');
include_once($SERVER_ROOT.'/classes/GlossaryManager.php');
include_once($SERVER_ROOT.'/content/lang/glossary/glossaryloader.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../profile/index.php?refurl='.$CLIENT_ROOT.'/glossary/glossaryloader.php');

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$ulFileName = array_key_exists("ulfilename",$_REQUEST)?$_REQUEST["ulfilename"]:"";
$ulOverride = array_key_exists("uloverride",$_REQUEST)?$_REQUEST["uloverride"]:"";
$batchTaxaStr = array_key_exists("batchtid",$_REQUEST)?$_REQUEST["batchtid"]:"";
$batchSource = array_key_exists("batchsources",$_REQUEST)?str_replace("'","&#39;",$_REQUEST["batchsources"]):"";

$isEditor = false;
if($IS_ADMIN || array_key_exists('GlossaryEditor',$USER_RIGHTS)) $isEditor = true;

$loaderManager = new GlossaryUpload();

$fieldMap = array();
if($isEditor){
	if($ulFileName){
		$loaderManager->setFileName($ulFileName);
	}
	else{
		$loaderManager->setUploadFile($ulOverride);
	}

	if(array_key_exists("sf",$_REQUEST)){
		//Grab field mapping, if mapping form was submitted
 		$targetFields = $_REQUEST["tf"];
 		$sourceFields = $_REQUEST["sf"];
		for($x = 0;$x<count($targetFields);$x++){
			if($targetFields[$x] && $sourceFields[$x]) $fieldMap[$sourceFields[$x]] = $targetFields[$x];
		}
		$languageArr = json_decode($_REQUEST["ullanguages"],true);
		$tidStr = $_REQUEST["ultids"];
		$ulSource = (array_key_exists("ulsources",$_REQUEST)?json_decode($_REQUEST["ulsources"]):'');
	}
	if($action == 'downloadcsv'){
		$loaderManager->exportUploadTerms();
		exit;
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['LOADER']; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$("#batchtaxagroup").autocomplete({
				source: function( request, response ) {
					$.getJSON( "rpc/taxalist.php", { term: request.term, t: "batch" }, response );
				},
				minLength: 3,
				autoFocus: true,
				select: function( event, ui ) {
					if(ui.item) document.getElementById('batchtid').value = ui.item.id;
				}
			});

		});

		function verifyUploadForm(f){
			var inputValue = f.uploadfile.value;
			var taxavals = $('#batchtaxagroup').manifest('values');
			if(inputValue.indexOf(".csv") == -1 && inputValue.indexOf(".CSV") == -1 && inputValue.indexOf(".zip") == -1){
				alert("Upload file must be a .csv or .zip file.");
				return false;
			}
			if(taxavals.length < 1){
				alert("<?php echo (isset($LANG['PLEASE_TAXON'])?$LANG['PLEASE_TAXON']:'Please enter at least one taxonomic group.'); ?>");
				return false;
			}
			return true;
		}

		function checkTransferForm(f){
			return true;
		}
	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($glossary_admin_glossaryloaderMenu)?$glossary_admin_glossaryloaderMenu:false);
include($SERVER_ROOT.'/includes/header.php');
if(isset($glossary_admin_glossaryloaderCrumbs)){
	if($glossary_admin_glossaryloaderCrumbs){
		echo '<div class="navpath">';
		echo $glossary_admin_glossaryloaderCrumbs;
		echo ' <b>Glossary Batch Loader</b>';
		echo '</div>';
	}
}
else{
	?>
	<div class="navpath">
		<a href="../index.php"><?php echo htmlspecialchars((isset($LANG['HOME'])?$LANG['HOME']:'Home'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<a href="index.php"><b><?php echo htmlspecialchars((isset($LANG['GLOSS_MGMNT'])?$LANG['GLOSS_MGMNT']:'Glossary Management'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></b></a> &gt;&gt;
		<b><?php echo htmlspecialchars((isset($LANG['BATCH_LOAD'])?$LANG['BATCH_LOAD']:'Glossary Batch Loader'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></b>
	</div>
	<?php
}

if($isEditor){
	?>
	<div role="main" id="innertext">
		<h1 class="page-heading"><?php echo (isset($LANG['G_BATCH_LOAD'])?$LANG['G_BATCH_LOAD']:'Glossary Term Batch Loader'); ?></h1>
		<div style="margin:30px;">
			<div style="margin-bottom:30px;">
				<?php echo (isset($LANG['BATCH_EXPLAIN'])?$LANG['BATCH_EXPLAIN']:'This page allows a Taxonomic Administrator to batch upload glossary data files.'); ?>
			</div>
			<?php
			if($action == 'Map Input File' || $action == 'Verify Mapping'){
				?>
				<form name="mapform" action="glossaryloader.php" method="post">
					<fieldset style="width:90%;">
						<legend style="font-weight:bold;font-size:120%;"><?php echo (isset($LANG['UPLOAD_FORM'])?$LANG['UPLOAD_FORM']:'Term Upload Form'); ?></legend>
						<div style="margin:10px;">
						</div>
						<table border="1" cellpadding="2" style="border:1px solid black">
							<tr>
								<th>
									<?php echo (isset($LANG['SOURCE_FIELD'])?$LANG['SOURCE_FIELD']:'Source Field'); ?>
								</th>
								<th>
									<?php echo (isset($LANG['TARGET_FIELD'])?$LANG['TARGET_FIELD']:'Target Field'); ?>
								</th>
							</tr>
							<?php
							$fArr = $loaderManager->getFieldArr();
							$sArr = $fArr['source'];
							$tArr = $fArr['target'];
							asort($tArr);
							foreach($sArr as $sField){
								?>
								<tr>
									<td style='padding:2px;'>
										<?php echo $sField; ?>
										<input type="hidden" name="sf[]" value="<?php echo $sField; ?>" />
									</td>
									<td>
										<select name="tf[]" style="background:yellow">
											<option value=""><?php echo (isset($LANG['UNMAPPED'])?$LANG['UNMAPPED']:'Field Unmapped'); ?></option>
											<option value="">-------------------------</option>
											<?php
											$selStr = "";
											echo "<option value='unmapped' ".$selStr.">".(isset($LANG['LEAVE_UNMAPPED'])?$LANG['LEAVE_UNMAPPED']:'Leave Field Unmapped')."</option>";
											if($selStr){
												$selStr = 0;
											}
											foreach($tArr as $k => $tField){
												if($selStr !== 0 && $tField==$sField){
													$selStr = "SELECTED";
												}
												elseif($selStr !== 0 && $tField==$sField.'_term'){
													$selStr = "SELECTED";
												}
												echo '<option value="'.$tField.'" '.($selStr?$selStr:'').'>'.$tField."</option>\n";
												if($selStr){
													$selStr = 0;
												}
											}
											?>
										</select>
									</td>
								</tr>
								<?php
							}
							?>
						</table>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Upload Terms" />
							<input type="hidden" name="ultids" value='<?php echo $batchTaxaStr;?>' />
							<input type="hidden" name="ulsources" value='<?php echo json_encode($batchSource);?>' />
							<input type="hidden" name="ullanguages" value='<?php echo $fArr['languages'];?>' />
							<input type="hidden" name="ulfilename" value="<?php echo $loaderManager->getFileName();?>" />
						</div>
					</fieldset>
				</form>
				<?php
			}
			elseif($action == 'Upload Terms'){
				echo '<ul>';
				if($action == 'Upload Terms'){
					$loaderManager->loadFile($fieldMap,$languageArr,$tidStr,$ulSource);
					$loaderManager->cleanUpload($tidStr);
				}
				$reportArr = $loaderManager->analysisUpload();
				echo '</ul>';
				?>
				<form name="transferform" action="glossaryloader.php" method="post" onsubmit="return checkTransferForm(this)">
					<fieldset style="width:450px;">
						<legend style="font-weight:bold;font-size:120%;"><?php echo (isset($LANG['TRANSFER_TERMS'])?$LANG['TRANSFER_TERMS']:'Transfer Terms To Central Table'); ?></legend>
						<div style="margin:10px;">
							<?php echo (isset($LANG['REVIEW_STATS'])?$LANG['REVIEW_STATS']:'Review upload statistics below before activating. Use the download option to review and/or adjust for reload if necessary.'); ?>
						</div>
						<div style="margin:10px;">
							<?php
							$statArr = $loaderManager->getStatArr();
							if($statArr){
								if(isset($statArr['upload'])) echo '<u>'.(isset($LANG['TERMS_UPLOADED'])?$LANG['TERMS_UPLOADED']:'Terms uploaded').'</u>: <b>'.$statArr['upload'].'</b><br/>';
								echo '<u>'.(isset($LANG['TOTAL_TERMS'])?$LANG['TOTAL_TERMS']:'Total terms').'</u>: <b>'.$statArr['total'].'</b><br/>';
								echo '<u>'.(isset($LANG['IN_DB'])?$LANG['IN_DB']:'Terms already in database').'</u>: <b>'.(isset($statArr['exist'])?$statArr['exist']:0).'</b><br/>';
								echo '<u>'.(isset($LANG['NEW_TERMS'])?$LANG['NEW_TERMS']:'New terms').'</u>: <b>'.(isset($statArr['new'])?$statArr['new']:0).'</b><br/>';
							}
							else{
								echo (isset($LANG['UNAVAILABLE'])?$LANG['UNAVAILABLE']:'Upload statistics are unavailable');
							}
							?>
						</div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Activate Terms" />
						</div>
						<div style="float:right;margin:10px;">
							<a href="glossaryloader.php?action=downloadcsv" ><?php echo htmlspecialchars((isset($LANG['DOWNLOAD_TERMS'])?$LANG['DOWNLOAD_TERMS']:'Download CSV Terms File'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
						</div>
					</fieldset>
				</form>
				<?php
			}
			elseif($action == "Activate Terms"){
				echo '<ul>';
				$loaderManager->transferUpload();
				echo "<li>".(isset($LANG['TERM_SUCCESS'])?$LANG['TERM_SUCCESS']:'Terms upload appears to have been successful').".</li>";
				echo "<li>".(isset($LANG['GO_TO'])?$LANG['GO_TO']:'Go to')." <a href='index.php'>" . htmlspecialchars((isset($LANG['G_SEARCH'])?$LANG['G_SEARCH']:'Glossary Search'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a> " . htmlspecialchars((isset($LANG['TO_SEARCH'])?$LANG['TO_SEARCH']:'page to search for a loaded name.'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</li>";
				echo '</ul>';
			}
			else{
				?>
				<div>
					<form name="uploadform" action="glossaryloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyUploadForm(this)">
						<fieldset style="width:90%;">
							<legend style="font-weight:bold;font-size:120%;"><?php echo (isset($LANG['UPLOAD_FORM'])?$LANG['UPLOAD_FORM']:'Term Upload Form'); ?></legend>
							<div style="margin:10px;">
								<?php echo (isset($LANG['UPLOAD_EXPLAIN'])?$LANG['UPLOAD_EXPLAIN']:'
								Flat structured, CSV (comma delimited) text files can be uploaded here.
								Please specify the taxonomic group to which the terms will be related.
								If your file contains terms in multiple languages, label each column of terms as the language the terms are in (e.g., English),
								and then name all columns related to that term as the language, underscore, and then the column name
								(e.g., English, English_definition, Spanish, Spanish_definition, etc.). Columns can be added for the definition,
								author, translator, source, notes, and an online resource url.
								Synonyms can be added by naming the column the language, underscore, and synonym (e.g., English_synonym).
								A source can be added for all of the terms by filling in the Enter Sources box below.
								Please do not use spaces in the column names or file names.
								If the file upload step fails without displaying an error message, it is possible that the
								file size exceeds the file upload limits set within your PHP installation (see your php configuration file).
								'); ?>

							</div>
							<input type='hidden' name='MAX_FILE_SIZE' value='100000000' />
							<div>
								<div class="overrideopt">
									<b><?php echo (isset($LANG['ENTER_TAXON'])?$LANG['ENTER_TAXON']:'Enter Taxonomic Group'); ?>:</b>
									<div style="margin:10px;">
										<input type="text" name="batchtaxagroup" id="batchtaxagroup" style="width:550px;" value="" onchange="" autocomplete="off" />
										<input name="batchtid" id="batchtid" type="hidden" value="" />
									</div>
								</div>
							</div>
							<div>
								<div class="overrideopt">
									<b><?php echo (isset($LANG['ENTER_SOURCE'])?$LANG['ENTER_SOURCE']:'Enter Sources'); ?>:</b>
									<div style="margin:10px;">
										<textarea name="batchsources" id="batchsources" maxlength="1000" rows="10" style="width:450px;height:40px;resize:vertical;" ></textarea>
									</div>
								</div>
							</div>
							<div>
								<div class="overrideopt">
									<b><?php echo (isset($LANG['UPLOAD'])?$LANG['UPLOAD']:'Upload File'); ?>:</b>
									<div style="margin:10px;">
										<input id="genuploadfile" name="uploadfile" type="file" size="40" />
									</div>
								</div>
								<div style="margin:10px;">
									<input type="submit" name="action" value="Map Input File" />
								</div>
							</div>
						</fieldset>
					</form>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}
else{
	?>
	<div style='font-weight:bold;margin:30px;'>
		<?php echo (isset($LANG['NO_PERM'])?$LANG['NO_PERM']:'You do not have permissions to batch upload glossary data'); ?>
	</div>
	<?php
}
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>