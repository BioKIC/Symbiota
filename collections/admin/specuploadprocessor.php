<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecUploadDirect.php');
include_once($SERVER_ROOT.'/classes/SpecUploadFile.php');
include_once($SERVER_ROOT.'/classes/SpecUploadDwca.php');
include_once($SERVER_ROOT.'/content/lang/collections/admin/specupload.'.$LANG_TAG.'.php');

header('Content-Type: text/html; charset='.$CHARSET);
ini_set('max_execution_time', 3600);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/specupload.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid',$_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : '';
$uploadType = array_key_exists('uploadtype',$_REQUEST) ? filter_var($_REQUEST['uploadtype'], FILTER_SANITIZE_NUMBER_INT) : '';
$uspid = array_key_exists('uspid',$_REQUEST) ? $_REQUEST['uspid'] : '';
$action = array_key_exists('action',$_REQUEST) ? $_REQUEST['action'] : '';
$ulPath = array_key_exists('ulpath',$_REQUEST) ? $_REQUEST['ulpath'] : '';
$importIdent = array_key_exists('importident',$_REQUEST) ? true : false;
$importImage = array_key_exists('importimage',$_REQUEST) ? true : false;
$observerUid = array_key_exists('observeruid',$_POST) ? filter_var($_POST['observeruid'], FILTER_SANITIZE_NUMBER_INT) : '';
$matchCatNum = array_key_exists('matchcatnum',$_REQUEST) ? true : false;
$matchOtherCatNum = array_key_exists('matchothercatnum',$_REQUEST)&&$_REQUEST['matchothercatnum'] ? true : false;
$versionData = array_key_exists('versiondata',$_REQUEST) && $_REQUEST['versiondata'] ? true : false;
$verifyImages = array_key_exists('verifyimages',$_REQUEST)&&$_REQUEST['verifyimages'] ? true : false;
$processingStatus = array_key_exists('processingstatus',$_REQUEST) ? $_REQUEST['processingstatus'] : '';
$finalTransfer = array_key_exists('finaltransfer',$_REQUEST) ? filter_var($_REQUEST['finaltransfer'], FILTER_SANITIZE_NUMBER_INT) : 0;
$dbpk = array_key_exists('dbpk',$_REQUEST) ? $_REQUEST['dbpk'] : '';
$sourceIndex = isset($_REQUEST['sourceindex']) ? filter_var($_REQUEST['sourceindex'], FILTER_SANITIZE_NUMBER_INT) : 0;
$publicationGuid = array_key_exists('publicationGuid',$_POST) ? $_POST['publicationGuid'] : '';

if(strpos($uspid,'-')){
	$tok = explode('-',$uspid);
	$uspid = $tok[0];
	$uploadType = $tok[1];
}

//Sanitation
if($importIdent !== true) $importIdent = false;
if($matchCatNum !== true) $matchCatNum = false;
if($matchOtherCatNum !== true) $matchOtherCatNum = false;
if($versionData !== true) $versionData = false;
if($verifyImages !== true) $verifyImages = false;
if(!preg_match('/^[a-zA-Z0-9\s_-]+$/',$processingStatus)) $processingStatus = '';
if($dbpk) $dbpk = htmlspecialchars($dbpk);

$DIRECTUPLOAD = 1; $FILEUPLOAD = 3; $STOREDPROCEDURE = 4; $SCRIPTUPLOAD = 5; $DWCAUPLOAD = 6; $SKELETAL = 7; $IPTUPLOAD = 8; $NFNUPLOAD = 9; $SYMBIOTA = 13;

$duManager = new SpecUploadBase();
if($uploadType == $DIRECTUPLOAD){
	$duManager = new SpecUploadDirect();
}
elseif($uploadType == $FILEUPLOAD || $uploadType == $NFNUPLOAD){
	$duManager = new SpecUploadFile();
	$duManager->setUploadFileName($ulPath);
}
elseif($uploadType == $SKELETAL){
	$duManager = new SpecUploadFile();
	$duManager->setUploadFileName($ulPath);
	$matchCatNum = true;
}
elseif($uploadType == $DWCAUPLOAD || $uploadType == $IPTUPLOAD || $uploadType == $SYMBIOTA){
	$duManager = new SpecUploadDwca();
	$duManager->setTargetPath($ulPath);
	$duManager->setIncludeIdentificationHistory($importIdent);
	$duManager->setIncludeImages($importImage);
	for($i=0;$i<3;$i++){
		if(isset($_POST['filter'.$i])){
			$duManager->addFilterCondition($_POST['filter'.$i], $_POST['condition'.$i], $_POST['value'.$i]);
		}
	}
	$duManager->setSourcePortalIndex($sourceIndex);
	$duManager->setPublicationGuid($publicationGuid);
}

$duManager->setCollId($collid);
$duManager->setUspid($uspid);
$duManager->setUploadType($uploadType);
$duManager->setObserverUid($observerUid);
$duManager->setMatchCatalogNumber($matchCatNum);
$duManager->setMatchOtherCatalogNumbers($matchOtherCatNum);
$duManager->setVersionDataEdits($versionData);
$duManager->setVerifyImageUrls($verifyImages);
$duManager->setProcessingStatus($processingStatus);

$isEditor = 0;
if($IS_ADMIN) $isEditor = 1;
elseif(array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin'])) $isEditor = 1;
if($isEditor && $collid){
	$duManager->readUploadParameters();
	$duManager->setFieldMaps($_POST);
	$duManager->loadFieldMap();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.(isset($LANG['SPEC_UPLOAD']) ? $LANG['SPEC_UPLOAD'] : 'Specimen Uploader'); ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../../js/symb/shared.js" type="text/javascript"></script>
</head>
<body>
	<?php
$displayLeftMenu = (isset($collections_admin_specuploadMenu) ? $collections_admin_specuploadMenu:false);
include($SERVER_ROOT.'/includes/header.php');
?>
<div class="navpath">
	<a href="../../index.php"><?php echo (isset($LANG['HOME']) ? $LANG['HOME'] : 'Home'); ?></a> &gt;&gt;
	<a href="../misc/collprofiles.php?collid=<?= $collid ?>&emode=1"><?php echo (isset($LANG['COL_MGMNT']) ? $LANG['COL_MGMNT'] : 'Collection Management Panel'); ?></a> &gt;&gt;
	<a href="specuploadmanagement.php?collid=<?= $collid ?>"><?php echo (isset($LANG['LIST_UPLOAD']) ? $LANG['LIST_UPLOAD'] : 'List of Upload Profiles'); ?></a> &gt;&gt;
	<b><?php echo (isset($LANG['SPEC_UPLOAD']) ? $LANG['SPEC_UPLOAD'] : 'Specimen Uploader'); ?></b>
</div>
<div role="main" id="innertext">
	<h1 class="page-heading"><?= $LANG['UP_MODULE']; ?></h1>
	<?php
	if($isEditor && $collid){
		//Grab collection name and last upload date and display for all
		echo '<div style="font-weight:bold;font-size:130%;">'.$duManager->getCollInfo('name').'</div>';
		echo '<div style="margin:0px 0px 15px 15px;"><b>Last Upload Date:</b> '.($duManager->getCollInfo('uploaddate') ? $duManager->getCollInfo('uploaddate'):(isset($LANG['NOT_REC']) ? $LANG['NOT_REC'] : 'not recorded')).'</div>';
		if(($action == 'Start Upload') || (!$action && ($uploadType == $STOREDPROCEDURE || $uploadType == $SCRIPTUPLOAD))){
			//Upload records
			echo '<div style="font-weight:bold;font-size:120%">'.(isset($LANG['UP_STATUS']) ? $LANG['UP_STATUS'] : 'Upload Status').':</div>';
			echo '<ul style="margin:10px;font-weight:bold;">';
			$duManager->uploadData($finalTransfer);
			echo '</ul>';
			if(!$finalTransfer){
				?>
				<fieldset style="margin:15px;">
					<legend style="<?php if($uploadType == $SKELETAL) echo 'background-color:lightgreen'; ?>"><b><?php echo (isset($LANG['PENDING_REPORT']) ? $LANG['PENDING_REPORT'] : 'Pending Data Transfer Report'); ?></b></legend>
					<div style="margin:5px;">
						<?php
						$reportArr = $duManager->getTransferReport();
						echo '<div>'.(isset($LANG['OCCS_TRANSFERING']) ? $LANG['OCCS_TRANSFERING'] : 'Occurrences pending transfer').': '.$reportArr['occur'];
						if($reportArr['occur']){
							echo ' <a href="uploadreviewer.php?collid=' . $collid . '" target="_blank" title="' . (isset($LANG['PREVIEW']) ? $LANG['PREVIEW'] : 'Preview 1st 1000 Records') . '"><img src="../../images/list.png" style="width:12px;" /></a>';
							echo ' <a href="uploadreviewer.php?action=export&collid=' . $collid . '" target="_self" title="' . (isset($LANG['DOWNLOAD_RECS']) ? $LANG['DOWNLOAD_RECS'] : 'Download Records') . '"><img src="../../images/dl.png" style="width:12px;" /></a>';
						}
						echo '</div>';
						echo '<div style="margin-left:15px;">';
						echo '<div>'.$LANG['RECORDS_UPDATED'].': ';
						echo $reportArr['update'];
						if($reportArr['update']){
							$searchVar = 'occid:NOT_NULL';
							if(isset($reportArr['sync']) && $reportArr['sync']) $searchVar = 'syncnew';
							echo ' <a href="uploadreviewer.php?collid=' . $collid . '&searchvar=' . $searchVar . '" target="_blank" title="' . $LANG['PREVIEW'] . '"><img src="../../images/list.png" style="width:12px;" /></a>';
							echo ' <a href="uploadreviewer.php?action=export&collid=' . $collid . '&searchvar=' . $searchVar . '" target="_self" title="' . $LANG['DOWNLOAD_RECS'] . '"><img src="../../images/dl.png" style="width:12px;" /></a>';
							if($uploadType != $SKELETAL && $uploadType != $NFNUPLOAD){
								echo '<span style="color:orange;margin-left:10px"><b>'.$LANG['CAUTION'].':</b></span> '.$LANG['CAUTION_REPLACE'];
							}
						}
						echo '</div>';
						if($uploadType != $NFNUPLOAD || $reportArr['new']){
							if($uploadType == $NFNUPLOAD) echo '<div>'.(isset($LANG['MISMATCHED']) ? $LANG['MISMATCHED'] : 'Mismatched records').': ';
							else echo '<div>'.(isset($LANG['NEW_RECORDS']) ? $LANG['NEW_RECORDS'] : 'New records').': ';
							echo $reportArr['new'];
							if($reportArr['new']){
								echo ' <a href="uploadreviewer.php?collid=' . $collid . '&searchvar=occid:IS_NULL" target="_blank" title="' . (isset($LANG['PREVIEW']) ? $LANG['PREVIEW'] : 'Preview 1st 1000 Records') . '"><img src="../../images/list.png" style="width:12px;" /></a>';
								echo ' <a href="uploadreviewer.php?action=export&collid=' . $collid . '&searchvar=occid:IS_NULL" target="_self" title="' . (isset($LANG['DOWNLOAD_RECS']) ? $LANG['DOWNLOAD_RECS'] : 'Download Records') . '"><img src="../../images/dl.png" style="width:12px;" /></a>';
								if($uploadType == $NFNUPLOAD) echo '<span style="margin-left:15px;color:orange">&gt;&gt; '.(isset($LANG['FAILED_LINK']) ? $LANG['FAILED_LINK'] : 'Records failed to link to records within this collection and will not be imported').'</span>';
							}
							echo '</div>';
						}
						if(isset($reportArr['matchappend']) && $reportArr['matchappend']){
							echo '<div>'.(isset($LANG['MATCHING_CATALOG']) ? $LANG['MATCHING_CATALOG'] : 'Records matching on catalog number that will be appended').': ';
							echo $reportArr['matchappend'];
							if($reportArr['matchappend']){
								echo ' <a href="uploadreviewer.php?collid=' . $collid . '&searchvar=matchappend" target="_blank" title="' . (isset($LANG['PREVIEW']) ? $LANG['PREVIEW'] : 'Preview 1st 1000 Records') . '"><img src="../../images/list.png" style="width:12px;" /></a>';
								echo ' <a href="uploadreviewer.php?action=export&collid=' . $collid . '&searchvar=matchappend" target="_self" title="' . (isset($LANG['DOWNLOAD_RECS']) ? $LANG['DOWNLOAD_RECS'] : 'Download Records') . '"><img src="../../images/dl.png" style="width:12px;" /></a>';
							}
							echo '</div>';
							echo '<div style="margin-left:15px;"><span style="color:orange;">'.(isset($LANG['WARNING']) ? $LANG['WARNING'] : 'WARNING').':</span> ';
							echo (isset($LANG['WARNING_DUPES']) ? $LANG['WARNING_DUPES'] : 'This will result in records with duplicate catalog numbers').'</div>';
						}
						if($uploadType != $NFNUPLOAD && $uploadType != $SKELETAL){
							if(isset($reportArr['sync']) && $reportArr['sync']){
								echo '<div>'.(isset($LANG['RECS_SYNC']) ? $LANG['RECS_SYNC'] : 'Records that will be syncronized with central database').': ';
								echo $reportArr['sync'];
								if($reportArr['sync']){
									echo ' <a href="uploadreviewer.php?collid=' . $collid .'&searchvar=sync" target="_blank" title="' . (isset($LANG['PREVIEW']) ? $LANG['PREVIEW'] : 'Preview 1st 1000 Records') . '"><img src="../../images/list.png" style="width:12px;" /></a>';
									echo ' <a href="uploadreviewer.php?action=export&collid=' . $collid .'&searchvar=sync" target="_self" title="' . (isset($LANG['DOWNLOAD_RECS']) ? $LANG['DOWNLOAD_RECS'] : 'Download Records') . '"><img src="../../images/dl.png" style="width:12px;" /></a>';
								}
								echo '</div>';
								echo '<div style="margin-left:15px;">'.$LANG['EXPL_SYNC'].'.</div>';
								echo '<div style="margin-left:15px;"><span style="color:orange;">'.(isset($LANG['WARNING']) ? $LANG['WARNING'] : 'WARNING').':</span> '.$LANG['WARNING_REPLACE'].'</div>';
							}
							if(isset($reportArr['exist']) && $reportArr['exist']){
								echo '<div>'.$LANG['NOT_MATCHING'].': '.$reportArr['exist'];
								if($reportArr['exist']){
									echo ' <a href="uploadreviewer.php?collid=' . $collid .'&searchvar=exist" target="_blank" title="' . (isset($LANG['PREVIEW']) ? $LANG['PREVIEW'] : 'Preview 1st 1000 Records') . '"><img src="../../images/list.png" style="width:12px;" /></a>';
									echo ' <a href="uploadreviewer.php?action=export&collid=' . $collid .'&searchvar=exist" target="_self" title="' . (isset($LANG['DOWNLOAD_RECS']) ? $LANG['DOWNLOAD_RECS'] : 'Download Records') . '"><img src="../../images/dl.png" style="width:12px;" /></a>';
								}
								echo '</div>';
								echo '<div style="margin-left:15px;">'.$LANG['EXPECTED'].'. '.$LANG['FULL_REFRESH'].'</div>';
							}
							if(isset($reportArr['nulldbpk']) && $reportArr['nulldbpk']){
								echo '<div style="color:red;">'.(isset($LANG['NULL_RM']) ? $LANG['NULL_RM'] : 'Records that will be removed due to NULL Primary Identifier').': ';
								echo $reportArr['nulldbpk'];
								if($reportArr['nulldbpk']){
									echo ' <a href="uploadreviewer.php?collid=' . $collid .'&searchvar=dbpk:IS_NULL" target="_blank" title="' . (isset($LANG['PREVIEW']) ? $LANG['PREVIEW'] : 'Preview 1st 1000 Records') . '"><img src="../../images/list.png" style="width:12px;" /></a>';
									echo ' <a href="uploadreviewer.php?action=export&collid=' . $collid .'&searchvar=dbpk:IS_NULL" target="_self" title="' . (isset($LANG['DOWNLOAD_RECS']) ? $LANG['DOWNLOAD_RECS'] : 'Download Records') . '"><img src="../../images/dl.png" style="width:12px;" /></a>';
								}
								echo '</div>';
							}
							if(isset($reportArr['dupdbpk']) && $reportArr['dupdbpk']){
								echo '<div style="color:red;">'.(isset($LANG['DUP_RM']) ? $LANG['DUP_RM'] : 'Records that will be removed due to DUPLICATE Primary Identifier').': ';
								echo $reportArr['dupdbpk'];
								if($reportArr['dupdbpk']){
									echo ' <a href="uploadreviewer.php?collid=' . $collid .'&searchvar=dupdbpk" target="_blank" title="' . (isset($LANG['PREVIEW']) ? $LANG['PREVIEW'] : 'Preview 1st 1000 Records') . '"><img src="../../images/list.png" style="width:12px;" /></a>';
									echo ' <a href="uploadreviewer.php?action=export&collid=' . $collid .'&searchvar=dupdbpk" target="_self" title="' . (isset($LANG['DOWNLOAD_RECS']) ? $LANG['DOWNLOAD_RECS'] : 'Download Records') . '"><img src="../../images/dl.png" style="width:12px;" /></a>';
								}
								echo '</div>';
							}
						}
						echo '</div>';
						//Extensions
						if(isset($reportArr['ident'])) echo '<div>'.$LANG['IDENT_TRANSFER'].': '.$reportArr['ident'].'</div>';
						if(isset($reportArr['image'])) echo '<div>'.$LANG['IMAGE_TRANSFER'].': '.$reportArr['image'].'</div>';
						if($uploadType == $DWCAUPLOAD || $uploadType == $IPTUPLOAD || $uploadType == $SYMBIOTA){
							$sourceIndex = $duManager->getSourcePortalIndex();
							$publicationGuid = $duManager->getPublicationGuid();
						}
						?>
					</div>
					<form name="finaltransferform" action="specuploadprocessor.php" method="post" style="margin-top:10px;" onsubmit="return confirm('<?php echo $LANG['FINAL_TRANSFER']; ?>');">
						<input type="hidden" name="collid" value="<?php echo $collid;?>" >
						<input type="hidden" name="uploadtype" value="<?php echo $uploadType; ?>" >
						<input type="hidden" name="observeruid" value="<?php echo $observerUid; ?>" >
						<input type="hidden" name="versiondata" value="<?php echo ($versionData ? '1' : '0'); ?>" >
						<input type="hidden" name="verifyimages" value="<?php echo ($verifyImages ? '1' : '0'); ?>" >
						<input type="hidden" name="processingstatus" value="<?php echo $processingStatus;?>" >
						<input type="hidden" name="uspid" value="<?php echo $uspid;?>" >
						<input type="hidden" name="sourceindex" value="<?php echo $sourceIndex;?>" >
						<input type="hidden" name="publicationGuid" value="<?php echo $publicationGuid;?>" >
						<input type="hidden" name="fieldlist" value="<?php echo $duManager->getTargetFieldStr(); ?>" >
						<div style="margin:5px;">
							<button type="submit" name="action" value="activateOccurrences"><?php echo (isset($LANG['TRANS_RECS']) ? $LANG['TRANS_RECS'] : 'Transfer Records to Central Specimen Table'); ?></button>
						</div>
					</form>
				</fieldset>
				<?php
			}
		}
		elseif($action == 'activateOccurrences' || $finalTransfer){
			echo '<ul>';
			$duManager->setTargetFieldArr($_POST['fieldlist']);
			$duManager->finalTransfer();
			echo '</ul>';
		}
	}
	else{
		if(!$isEditor || !$collid) echo '<h2>'.(isset($LANG['NOT_AUTH']) ? $LANG['NOT_AUTH'] : 'ERROR: you are not authorized to upload to this collection').'</h2>';
		else{
			echo '<h2>';
			echo (isset($LANG['PAGE_ERROR']) ? $LANG['PAGE_ERROR'] : '').' = ';
			echo ini_get("upload_max_filesize").'; post_max_size = '.ini_get('post_max_size');
			echo (isset($LANG['USE_BACK']) ? $LANG['USE_BACK'] : 'Use the back arrows to get back to the file upload page.');
			echo '</h2>';
		}
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>