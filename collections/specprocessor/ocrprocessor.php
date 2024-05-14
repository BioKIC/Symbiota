<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/specprocessor/specprocessor_tools.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/specprocessor_tools.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/specprocessor_tools.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$spprid = array_key_exists('spprid',$_REQUEST)?$_REQUEST['spprid']:0;
$procStatus = array_key_exists('procstatus',$_REQUEST)?$_REQUEST['procstatus']:'unprocessed';

$procManager = new SpecProcessorManager();
$procManager->setCollId($collid);
$procManager->setProjVariables('OCR Harvest');
?>
<script>
	$(function() {
		var dialogArr = new Array("speckeypattern","sourcepath","ocrfile","ocrsource");
		var dialogStr = "";
		for(i=0;i<dialogArr.length;i++){
			dialogStr = dialogArr[i]+"info";
			$( "#"+dialogStr+"dialog" ).dialog({
				autoOpen: false,
				modal: true,
				position: { my: "left top", at: "right bottom", of: "#"+dialogStr }
			});

			$( "#"+dialogStr ).click(function() {
				$( "#"+this.id+"dialog" ).dialog( "open" );
			});
		}

	});

	function validateStatQueryForm(f){
		if(f.pscrit.value == ""){
			alert("<?php echo $LANG['PLS_SEL_PROC_STATUS']; ?>");
			return false;
		}
		return true;
	}

	function validateOcrTessForm(f){
		if(f.procstatus.value == ""){
			alert("<?php echo $LANG['PLS_SEL_PROC_STATUS']; ?>");
			return false;
		}
		return true;
	}

	function validateOcrUploadForm(f){
		if(f.speckeypattern.value == ""){
			alert("<?php echo $LANG['ENTER_PATT_MATCH']; ?>");
			return false;
		}

		if(f.sourcepath.value == "" && f.ocrfile.value == ""){
			alert("<?php echo $LANG['SEL_OCR_INPUT']; ?>");
			return false;
		}
		var fileName = f.ocrfile.value;
		if(fileName != ""){
			var ext = fileName.split('.').pop();
			if(ext != 'zip' && ext != 'ZIP'){
				alert("<?php echo $LANG['UPLOAD_MUST_ZIP']; ?>");
				return false;
			}
		}
		return true;
	}
</script>
<div style="margin:15px;">
	<h1 class="page-heading screen-reader-only">Optical Character Recognition</h1>
	<?php
	$cntTotal = $procManager->getSpecWithImage();
	$cntUnproc = $procManager->getSpecWithImage($procStatus);
	$cntUnprocNoOcr = $procManager->getSpecNoOcr($procStatus);
	if($procStatus == 'null') $procStatus = 'No Status';
	?>
	<fieldset style="padding:20px;">
		<legend><b><?php echo $LANG['SPEC_IMG_STATS']; ?></b></legend>

		<div><?php echo '<b>' . $LANG['TOTAL_W_IMGS'] . ':</b> ' . $cntTotal; ?></div>
		<div><?php echo '<b>"' . $procStatus . '" ' . $LANG['SPEC_W_IMGS'] . ':</b> ' . $cntUnproc; ?></div>
		<div style="margin-left:15px;"><?php echo $LANG['W_OCR'] . ': ' . ($cntUnproc-$cntUnprocNoOcr); ?></div>
		<div style="margin-left:15px;"><?php echo $LANG['WO_OCR'] . ': ' . $cntUnprocNoOcr; ?> </div>

		<div style="margin:15px">
			<b><?php echo $LANG['CUSTOM_QUERY']; ?>: </b><br/>
			<form name="statqueryform" action="index.php" method="post" onsubmit="return validateStatQueryForm(this)">
				<select name="procstatus">
					<option value=""><?php echo $LANG['SEL_PROC_STATUS']; ?></option>
					<option value="">-----------------------------------</option>
					<option value="null"><?php echo $LANG['NO_STATUS']; ?></option>
					<?php
					$psList = $procManager->getProcessingStatusList();
					foreach($psList as $psVal){
						echo '<option value="'.$psVal.'">'.$psVal.'</option>';
					}
					?>
				</select>
				<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
				<input name="tabindex" type="hidden" value="2" />
				<button name="submitaction" type="submit" value="Reset Statistics" ><?php echo $LANG['RESET_STATS']; ?></button>
			</form>
		</div>
	</fieldset>

	<fieldset style="padding:20px;margin-top:20px;">
		<legend><b><?php echo $LANG['BATCH_OCR_IMGS']; ?></b></legend>
		<?php
		if(isset($TESSERACT_PATH) && $TESSERACT_PATH){
			?>
			<form name="batchTessform" action="processor.php" method="post" onsubmit="return validateBatchTessForm(this)">
				<div style="padding:3px;">
					<b><?php echo $LANG['PROC_STATUS']; ?>:</b>
					<select name="procstatus">
						<option value="unprocessed"><?php echo $LANG['UNPROCESSED']; ?></option>
						<option value="">-----------------------------------</option>
						<option value="null"><?php echo $LANG['NO_STATUS']; ?></option>
						<?php
						$psList = $procManager->getProcessingStatusList();
						foreach($psList as $psVal){
							if($psVal != 'unprocessed'){
								echo '<option value="'.$psVal.'">'.$psVal.'</option>';
							}
						}
						?>
					</select><br/>
				</div>
				<div style="padding:3px;">
					<b><?php echo $LANG['NUM_RECORDS_PROCESS']; ?>:</b>
					<input name="batchlimit" type="text" value="100" style="width:60px" />
				</div>
				<div style="padding:15px;">
					<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
					<input name="tabindex" type="hidden" value="2" />
					<button name="submitaction" type="submit" value="Run Batch OCR" ><?php echo $LANG['RUN_BATCH_OCR']; ?></button>
				</div>
				<div style="margin:15px">
					<?php echo $LANG['TESSERACT_DEPEND']; ?>
				</div>
			</form>
			<?php
		}
		else{
			echo '<div style="margin:25px"><b>';
			echo $LANG['NO_TESSERACT'] . ' ';
			echo $LANG['CONTACT_SYSADMIN'];
			echo '</b></div>';
		}
		?>
	</fieldset>

	<fieldset style="padding:20px;margin-top:20px;">
		<legend><b><?php echo $LANG['OCR_IMPORT_TOOL']; ?></b></legend>
		<form name="ocruploadform" action="processor.php" method="post" enctype="multipart/form-data" onsubmit="return validateOcrUploadForm(this);">
			<div style="margin:15px">
				<?php echo $LANG['OCR_IMPORT_EXPLAIN']; ?>
			</div>
			<div style="margin:15px">
				<b><?php echo $LANG['REQS']; ?>:</b>
				<ul>
					<li><?php echo $LANG['REQ1']; ?></li>
					<li><?php echo $LANG['REQ2']; ?></li>
					<li><?php echo $LANG['REQ3']; ?></li>
					<li><?php echo $LANG['REQ4']; ?></li>
					<li><?php echo $LANG['REQ5']; ?></li>
				</ul>
			</div>
			<div style="margin:15px">
				<table style="width:100%;">
					<tr>
						<td style="width:200px">
							<b><?php echo $LANG['REGEX']; ?>:</b>
						</td>
						<td>
							<input name="speckeypattern" type="text" style="width:300px;" value="<?php echo $procManager->getSpecKeyPattern(); ?>" />
							<a id="speckeypatterninfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
								<img src="../../images/info.png" style="width:1.3em;" />
							</a>
							<div id="speckeypatterninfodialog">
								<?php echo $LANG['REGEX_EXPLAIN']; ?>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo $LANG['ZIP_W_OCR']; ?>:</b>
						</td>
						<td>
							<div style="float:right;"><a href="#" onclick="toggle('pathElem');return false;" title="<?php echo $LANG['TOGGLE_FULL_PATH']; ?>"><?php echo $LANG['FULL_PATH']; ?></a></div>
							<div class="pathElem">
								<input name="ocrfile" type="file" size="50" onchange="this.form.sourcepath.value = ''" />
								<input name="MAX_FILE_SIZE" type="hidden" value="10000000" />
								<a id="ocrfileinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
									<img src="../../images/info.png" style="width:1.3em;" />
								</a>
								<div id="ocrfileinfodialog">
									<?php echo $LANG['BROWSE_SEL_ZIP']; ?>
								</div>
							</div>
							<div class="pathElem" style="display:none;">
								<input name="sourcepath" type="text" style="width:350px;" value="<?php echo $procManager->getSourcePath(); ?>" />
								<a id="sourcepathinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
									<img src="../../images/info.png" style="width:1.3em;" />
								</a>
								<div id="sourcepathinfodialog">
									<?php echo $LANG['SOURCE_PATH_EXPLAIN']; ?>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo $LANG['OCR_SOURCE']; ?>:</b>
						</td>
						<td>
							<input name="ocrsource" type="text" value="" />
							<a id="ocrsourceinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
								<img src="../../images/info.png" style="width:1.3em;" />
							</a>
							<div id="ocrsourceinfodialog">
								<?php echo $LANG['OCR_SOURCE_EXPLAIN']; ?>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<input name="title" type="hidden" value="OCR Harvest" />
							<input name="newprofile" type="hidden" value="<?php echo ($procManager->getSpecKeyPattern()?'0':'1'); ?>" />
							<input name="spprid" type="hidden" value="<?php echo $spprid; ?>" />
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="tabindex" type="hidden" value="2" />
							<div style="margin:25px">
								<button name="submitaction" type="submit" value="Load OCR Files" ><?php echo $LANG['LOAD_OCR_FILES']; ?></button>
							</div>
						</td>
					</tr>
				</table>
			</div>
		</form>
	</fieldset>
</div>