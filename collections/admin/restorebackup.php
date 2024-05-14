<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecUploadDwca.php');
include_once($SERVER_ROOT.'/content/lang/collections/admin/restorebackup.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/content/lang/collections/admin/specupload.'.$LANG_TAG.'.php');

header("Content-Type: text/html; charset=".$CHARSET);
ini_set('max_execution_time', 3600);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/reloadbackup.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = $_REQUEST["collid"];
$action = array_key_exists("action",$_REQUEST)?$_POST["action"]:"";
$includeIdentificationHistory = array_key_exists("includeidentificationhistory",$_REQUEST)?$_POST["includeidentificationhistory"]:"";
$includeImages = array_key_exists("includeimages",$_REQUEST)?$_POST["includeimages"]:"";
$ulPath = array_key_exists("ulpath",$_REQUEST)?$_POST["ulpath"]:"";

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

$duManager = new SpecUploadDwca();
$duManager->setCollId($collid);
$duManager->setUploadType(10);
$duManager->setTargetPath($ulPath);
$duManager->setIncludeIdentificationHistory($includeIdentificationHistory);
$duManager->setIncludeImages($includeImages);
$duManager->setMatchCatalogNumber(false);
$duManager->setMatchOtherCatalogNumbers(false);
$duManager->setVerifyImageUrls(false);

$isEditor = 0;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	$isEditor = 1;
}

$isLiveData = false;
if($duManager->getCollInfo("managementtype") == 'Live Data') $isLiveData = true;

//Grab field mapping, if mapping form was submitted
if(array_key_exists("sf",$_POST)){
	//Set field map for occurrences using mapping form
	$targetFields = $_POST["tf"];
	$sourceFields = $_POST["sf"];
	$fieldMap = Array();
	for($x = 0;$x<count($targetFields);$x++){
		if($targetFields[$x]){
			$tField = $targetFields[$x];
			if($tField == 'unmapped') $tField .= '-'.$x;
			$fieldMap[$tField]["field"] = $sourceFields[$x];
		}
	}
	//Set Source PK
	$duManager->setFieldMap($fieldMap);

	//Set field map for identification history
	if(array_key_exists("ID-sf",$_POST)){
		$targetIdFields = $_POST["ID-tf"];
		$sourceIdFields = $_POST["ID-sf"];
		$fieldIdMap = Array();
		for($x = 0;$x<count($targetIdFields);$x++){
			if($targetIdFields[$x]){
				$tIdField = $targetIdFields[$x];
				if($tIdField == 'unmapped') $tIdField .= '-'.$x;
				$fieldIdMap[$tIdField]["field"] = $sourceIdFields[$x];
			}
		}
		$duManager->setIdentFieldMap($fieldIdMap);
	}
	//Set field map for image history
	if(array_key_exists("IM-sf",$_POST)){
		$targetImFields = $_POST["IM-tf"];
		$sourceImFields = $_POST["IM-sf"];
		$fieldImMap = Array();
		for($x = 0;$x<count($targetImFields);$x++){
			if($targetImFields[$x]){
				$tImField = $targetImFields[$x];
				if($tImField == 'unmapped') $tImField .= '-'.$x;
				$fieldImMap[$tImField]["field"] = $sourceImFields[$x];
			}
		}
		$duManager->setImageFieldMap($fieldImMap);
	}
}
$duManager->loadFieldMap(true);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.(isset($LANG['RESTORE'])?$LANG['RESTORE']:'Restore Backup'); ?></title>
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
			if(f.uploadfile && f.uploadfile.value){
				 fileName = f.uploadfile.value;
			}
			else{
				fileName = f.ulfnoverride.value;
			}
			if(fileName == ""){
				alert("<?php echo (isset($LANG['PATH_EMPTY'])?$LANG['PATH_EMPTY']:'File path is empty. Please select the file that is to be restored.'); ?>");
				return false;
			}
			else{
				var ext = fileName.split('.').pop();
				if(ext == 'zip' || ext == 'ZIP') return true;
				else{
					alert("<?php echo (isset($LANG['MUST_ZIP'])?$LANG['MUST_ZIP']:'File must be a ZIP file (.zip) downloaded as a Symbiota backup.'); ?>");
					return false;
				}
			}
			return true;
		}

		function verifyFileSize(inputObj){
			inputObj.form.ulfnoverride.value = ''
			if (!window.FileReader) {
				//alert("<?php echo (isset($LANG['API_SUP'])?$LANG['API_SUP']:'The file API isn\'t supported on this browser yet.'); ?>");
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
				var msg = "<?php echo (isset($LANG['IMPORT_FILE'])?$LANG['IMPORT_FILE']:'Import file '); ?>"+file.name+" ("+Math.round(file.size/100000)/10+"<?php echo (isset($LANG['IS_BIGGER'])?$LANG['IS_BIGGER']:'MB) is larger than is allowed (current limit: '); ?>"+(maxUpload/1000000)+"MB).";
				if(file.name.slice(-3) != "zip") msg = msg + "<?php echo (isset($LANG['MAYBE_ZIP'])?$LANG['MAYBE_ZIP']:' Note that import file size can be reduced by compressing within a zip file. '); ?>";
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
	<a href="../../index.php"><?php echo htmlspecialchars((isset($LANG['HOME'])?$LANG['HOME']:'Home'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
	<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo htmlspecialchars((isset($LANG['COL_MGMNT'])?$LANG['COL_MGMNT']:'Collection Management Panel'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
	<b><?php echo (isset($LANG['BACKUP_MOD'])?$LANG['BACKUP_MOD']:'Backup Restore Module'); ?></b>
</div>
<!-- This is inner text! -->
<div role="main" id="innertext">
	<h1 class="page-heading">Restore Collection from Backup File</h1>
	<?php
	$recReplaceMsg = '<span style="color:orange"><b>'.(isset($LANG['CAUTION'])?$LANG['CAUTION']:'Caution').':</b></span> '.(isset($LANG['MATCH_REPLACE'])?$LANG['MATCH_REPLACE']:'Matching records will be replaced with incoming records');
	if($isEditor){
		if($collid){
			echo '<div style="font-weight:bold;font-size:130%;margin-bottom:20px">'.$duManager->getCollInfo('name').'</div>';
			if(!$action){
				?>
				<form name="fileuploadform" action="restorebackup.php" method="post" enctype="multipart/form-data" onsubmit="return verifyFileUploadForm(this)">
					<fieldset style="padding:25px;width:95%;">
						<legend style="font-weight:bold;"><?php echo (isset($LANG['SEL_BACKUP'])?$LANG['SEL_BACKUP']:'Select Backup File to Restore'); ?></legend>
						<div>
							<div>
								<input name="uploadfile" type="file" size="50" onchange="verifyFileSize(this)" />
							</div>
							<div class="ulfnoptions" style="display:none;margin:15px 0px">
								<b><?php echo (isset($LANG['RESOURCE_URL'])?$LANG['RESOURCE_URL']:'Resource Path or URL'); ?>:</b>
								<input name="ulfnoverride" type="text" size="70" /><br/>
								<div>
									<?php
									$workaroundStr = 'This option is for pointing to a data file that was manually uploaded to a server. This option offers a workaround for importing
										files that are larger than what is allowed by server upload limitations (e.g. PHP configuration limits)';
									echo (isset($LANG['WORKAROUND'])?$LANG['WORKAROUND']:$workaroundStr);
									?>
								</div>
							</div>
						</div>
						<div style="margin:10px 0px;">
							<input name="includeidentificationhistory" type="checkbox" value="1" checked /> <?php echo (isset($LANG['RESTORE_DETS'])?$LANG['RESTORE_DETS']:'Restore Determination History'); ?><br/>
							<input name="includeimages" type="checkbox" value="1" checked /> <?php echo (isset($LANG['RESTORE_IMGS'])?$LANG['RESTORE_IMGS']:'Restore Images'); ?><br/>
						</div>
						<div style="margin:10px 0px;">
							<button name="action" type="submit" value="AnalyzeFile"><?php echo (isset($LANG['ANALYZE'])?$LANG['ANALYZE']:'Analyze File'); ?></button>
							<input name="collid" type="hidden" value="<?php echo $collid;?>" />
							<input name="MAX_FILE_SIZE" type="hidden" value="100000000" />
						</div>
						<div class="ulfnoptions">
							<a href="#" onclick="toggle('ulfnoptions');return false;"><?php echo htmlspecialchars((isset($LANG['MANUAL'])?$LANG['MANUAL']:'Manual File Upload Option'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
						</div>
					</fieldset>
				</form>
				<?php
			}
			elseif($action == 'AnalyzeFile' || $action == 'Continue with Restore'){
				$uploadData = false;
				if($action == 'AnalyzeFile'){
					if($ulPath = $duManager->uploadFile()){
						if($verificationResult = $duManager->verifyBackupFile()){
							if($verificationResult === true){
								$uploadData = true;
							}
							elseif(is_array($verificationResult)){
								?>
								<form name="filemappingform" action="restorebackup.php" method="post" onsubmit="return verifyMappingForm(this)">
									<fieldset style="width:95%;padding:15px">
										<legend style="font-weight:bold;font-size:120%;"><?php echo (isset($LANG['BACKUP_MOD'])?$LANG['BACKUP_MOD']:'Backup Restore Module'); ?></legend>
										<div style="margin:15px">
											<div style="color:orange;font-weight:bold"><?php echo (isset($LANG['WARNINGS'])?$LANG['WARNINGS']:'Warnings exist'); ?>:</div>
											<div style="margin:10px">
												<?php
												foreach($verificationResult as $warningStr){
													if($warningStr == 'UnableToLocateCollectionElement') echo '<div><b>WARNING:</b> does NOT appear to be a valid backup file; unable to locate collection element within eml.xml</div>';
													elseif($warningStr == 'CollectionIdNotMatching') echo '<div><b>ABORT:</b> does NOT appear to be a valid backup file for this collection; collection ID not matching target collection</div>';
													elseif($warningStr == 'CollectionGuidNotMatching') echo '<div><b>ABORT:</b> does NOT appear to be a valid backup file for this collection; collection GUID not matching target collection</div>';
													elseif($warningStr == 'MultipleCollectionElements') echo '<div><b>WARNING:</b> does NOT appear to be a valid backup file; more than one collection element located within eml.xml</div>';
												}
												?>
												<div style="margin-top: 10px"><?php echo (isset($LANG['LIVE_DANGEROUSLY'])?$LANG['LIVE_DANGEROUSLY']:'If you think the warnings are in error, contact your portal administrator'); ?></div>
											</div>
										</div>
										<div style="margin:20px;">
											<!--
											<input type="submit" name="action" value="Continue with Restore" />
											 -->
 										</div>
									</fieldset>
									<input name="includeidentificationhistory" type="hidden" value="<?php echo $includeIdentificationHistory; ?>" />
									<input name="includeimages" type="hidden" value="<?php echo $includeImages; ?>" />
									<input name="collid" type="hidden" value="<?php echo $collid;?>" />
									<input name="ulpath" type="hidden" value="<?php echo $ulPath;?>" />
								</form>
								<?php
							}
						}
						else{
							echo '<div><span style="color:red">FATAL ERROR:</span> ';
							$errCode = $duManager->getErrorStr();
							if($errCode == 'OccurrencesMissing') echo 'Not a valid backup file: occurrences.csv file is missing';
							elseif($errCode == 'ImagesMissing') echo 'Not a valid backup file; images.csv file is missing';
							elseif($errCode == 'IdentificationsMissing') echo 'Not a valid backup file; identifications.csv file is missing';
							elseif($errCode == 'MetaMissing') echo 'Not a valid backup file; meta.xml file is missing';
							elseif($errCode == 'EmlMissing') echo 'Not a valid backup file; eml.xml file is missing';
							elseif($errCode == 'MalformedMeta') echo 'Not a valid backup file; malformed meta.xml file';
							echo '</div>';
						}
					}
				}
				if($action == 'Continue with Restore' || $uploadData){
					echo "<div style='font-weight:bold;font-size:120%'>Upload Status:</div>";
					echo "<ul style='margin:10px;font-weight:bold;'>";
					$duManager->uploadData(false);
					$duManager->cleanBackupReload();
					echo "</ul>";
					if($duManager->getTransferCount()){
						?>
						<fieldset style="margin:15px;">
							<legend style=""><b><?php echo (isset($LANG['FINAL_T'])?$LANG['FINAL_T']:'Final transfer'); ?></b></legend>
							<div style="margin:5px;">
								<?php
								$reportArr = $duManager->getTransferReport();
								echo '<div>'.(isset($LANG['OCCS_TRANSFERING'])?$LANG['OCCS_TRANSFERING']:'Occurrences pending transfer').': '.$reportArr['occur'];
								if($reportArr['occur']){
									echo ' <a href="uploadreviewer.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" title="' . htmlspecialchars((isset($LANG['PREVIEW'])?$LANG['PREVIEW']:'Preview 1st 1000 Records'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><img src="../../images/list.png" style="width:1.2em;" /></a>';
									echo ' <a href="uploadreviewer.php?action=export&collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_self" title="' . htmlspecialchars((isset($LANG['DOWNLOAD_RECS'])?$LANG['DOWNLOAD_RECS']:'Download Records'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><img src="../../images/dl.png" style="width:1.2em;" /></a>';
								}
								echo '</div>';
								echo '<div style="margin-left:15px;">';
								echo '<div>Records to be updated: ';
								echo $reportArr['update'];
								if($reportArr['update']){
									echo ' <a href="uploadreviewer.php?collid=' . $collid . '&searchvar=occid:NOT_NULL" target="_blank" title="' . (isset($LANG['PREVIEW'])?$LANG['PREVIEW']:'Preview 1st 1000 Records') . '"><img src="../../images/list.png" style="width:12px;" /></a>';
									echo ' <a href="uploadreviewer.php?action=export&collid=' . $collid . '&searchvar=occid:NOT_NULL" target="_self" title="' . (isset($LANG['DOWNLOAD_RECS'])?$LANG['DOWNLOAD_RECS']:'Download Records') . '"><img src="../../images/dl.png" style="width:12px;" /></a>';
								}
								echo '</div>';
								if($reportArr['new']){
									echo '<div>Records to be restored: ';
									echo $reportArr['new'];
									if($reportArr['new']){
										echo ' <a href="uploadreviewer.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&searchvar=new" target="_blank" title="' . htmlspecialchars((isset($LANG['PREVIEW'])?$LANG['PREVIEW']:'Preview 1st 1000 Records'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><img src="../../images/list.png" style="width:1.2em;" /></a>';
										echo ' <a href="uploadreviewer.php?action=export&collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&searchvar=new" target="_self" title="' . htmlspecialchars((isset($LANG['DOWNLOAD_RECS'])?$LANG['DOWNLOAD_RECS']:'Download Records'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><img src="../../images/dl.png" style="width:1.2em;" /></a>';
									}
									echo '</div>';
								}
								if(isset($reportArr['exist']) && $reportArr['exist']){
									echo '<div>Previous loaded records not matching incoming records: ';
									echo $reportArr['exist'];
									if($reportArr['exist']){
										echo ' <a href="uploadreviewer.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&searchvar=exist" target="_blank" title="' . htmlspecialchars((isset($LANG['PREVIEW'])?$LANG['PREVIEW']:'Preview 1st 1000 Records'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><img src="../../images/list.png" style="width:1.2em;" /></a>';
										echo ' <a href="uploadreviewer.php?action=export&collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&searchvar=exist" target="_self" title="' . htmlspecialchars((isset($LANG['DOWNLOAD_RECS'])?$LANG['DOWNLOAD_RECS']:'Download Records'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><img src="../../images/dl.png" style="width:1.2em;" /></a>';
									}
									echo '</div>';
									echo '<div style="margin-left:15px;">';
									echo (isset($LANG['DEL_OR_PREV'])?$LANG['DEL_OR_PREV']:'Note: These are records that were added after the backup was downloaded. You can delete these records one-by-one using preview link above').', ';
									echo (isset($LANG['OR_CONTACT'])?$LANG['OR_CONTACT']:'or contact your portal manager if you would rather delete these records in batch').'. ';
									echo '</div>';
								}
								echo '</div>';
								//Extensions
								if(isset($reportArr['ident'])){
									echo '<div>'.(isset($LANG['IDENT_TRANSFER'])?$LANG['IDENT_TRANSFER']:'Identification history count').': '.$reportArr['ident'].'</div>';
								}
								if(isset($reportArr['image'])){
									echo '<div>'.(isset($LANG['IMAGE_TRANSFER'])?$LANG['IMAGE_TRANSFER']:'Images count').': '.$reportArr['image'].'</div>';
								}

								?>
							</div>
							<form name="finaltransferform" action="restorebackup.php" method="post" style="margin-top:10px;" onsubmit="return confirm('Are you sure you want to transfer records from temporary table to central specimen table?');">
								<input name="includeidentificationhistory" type="hidden" value="<?php echo $includeIdentificationHistory; ?>" />
								<input name="includeimages" type="hidden" value="<?php echo $includeImages; ?>" />
								<input type="hidden" name="collid" value="<?php echo $collid;?>" />
								<div style="margin:5px;">
									<button name="action" type="submit" value="TransferRecords"><?php echo (isset($LANG['FINALIZE_RESTORE'])?$LANG['FINALIZE_RESTORE']:'Finalize Restore'); ?></button>
								</div>
							</form>
						</fieldset>
						<?php
					}
				}
			}
			elseif($action == 'TransferRecords'){
				echo '<ul>';
				$duManager->finalTransfer();
				echo '</ul>';
			}
		}
		else{
			?>
			<div style="font-weight:bold;font-size:120%;">
				<?php
				$errStr = 'ERROR: Either you have tried to reach this page without going through the collection management menuor you have tried to upload a file that is too large.
				You may want to breaking the upload file into smaller files or compressing the file into a zip archive (.zip extension). You may want to contact portal administrator
				to request assistance in uploading the file (hint to admin: increasing PHP upload limits may help, current upload_max_filesize = ';
				echo (isset($LANG['NO_SETTING'])?$LANG['NO_SETTING']:$errStr) . '. ' . ini_get("upload_max_filesize") . '; post_max_size = '.ini_get("post_max_size") . '. ';
				echo (isset($LANG['USE_BACK'])?$LANG['USE_BACK']:'Use the back arrows to get back to the file upload page.');
				?>
			</div>
			<?php
		}
	}
	else{
		echo '<div style="font-weight:bold;font-size:120%;">'.(isset($LANG['NOT_AUTH'])?$LANG['NOT_AUTH']:'ERROR: you are not authorized to upload to this collection').'</div>';
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>