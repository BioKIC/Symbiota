<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecUploadDirect.php');
include_once($SERVER_ROOT.'/classes/SpecUploadFile.php');
include_once($SERVER_ROOT.'/classes/SpecUploadDwca.php');
include_once($SERVER_ROOT.'/content/lang/collections/admin/specupload.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/specupload.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = htmlspecialchars(array_key_exists('collid',$_REQUEST) ? $_REQUEST['collid']: '', ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$uploadType = htmlspecialchars(array_key_exists('uploadtype',$_REQUEST) ? $_REQUEST['uploadtype']: '', ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$uspid = htmlspecialchars(array_key_exists('uspid',$_REQUEST) ? $_REQUEST['uspid']: '', ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$autoMap = array_key_exists('automap',$_POST) ? true : false;
$action = htmlspecialchars(array_key_exists('action',$_REQUEST) ? $_REQUEST['action']: '', ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$ulPath = htmlspecialchars(array_key_exists('ulpath',$_REQUEST) ? $_REQUEST['ulpath']: '', ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$importIdent = array_key_exists('importident',$_REQUEST) ? true : false;
$importImage = array_key_exists('importimage',$_REQUEST) ? true : false;
$observerUid = htmlspecialchars(array_key_exists('observeruid',$_POST) ? $_POST['observeruid']: '', ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$matchCatNum = array_key_exists('matchcatnum',$_REQUEST) ? true : false;
$matchOtherCatNum = array_key_exists('matchothercatnum',$_REQUEST)&&$_REQUEST['matchothercatnum'] ? true : false;
$versionData = array_key_exists('versiondata',$_REQUEST) && $_REQUEST['versiondata'] ? true : false;
$verifyImages = array_key_exists('verifyimages',$_REQUEST) && $_REQUEST['verifyimages'] ? true : false;
$processingStatus = htmlspecialchars(array_key_exists('processingstatus',$_REQUEST) ? $_REQUEST['processingstatus']: '', ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$dbpk = htmlspecialchars(array_key_exists('dbpk',$_REQUEST) ? $_REQUEST['dbpk']: '', ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);

if(strpos($uspid,'-')){
	$tok = explode('-',$uspid);
	$uspid = $tok[0];
	$uploadType = $tok[1];
}

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($uploadType)) $uploadType = 0;
if($autoMap !== true) $autoMap = false;
if($importIdent !== true) $importIdent = false;
if($importImage !== true) $importImage = false;
if(!is_numeric($observerUid)) $observerUid = 0;
if($matchCatNum !== true) $matchCatNum = false;
if($matchOtherCatNum !== true) $matchOtherCatNum = false;
if($versionData !== true) $versionData = false;
if($verifyImages !== true) $verifyImages = false;
if(!preg_match('/^[a-zA-Z0-9\s_-]+$/',$processingStatus)) $processingStatus = '';
if($dbpk) $dbpk = htmlspecialchars($dbpk);

$FILEUPLOAD = 3; $DWCAUPLOAD = 6; $SKELETAL = 7; $IPTUPLOAD = 8; $NFNUPLOAD = 9; $SYMBIOTA = 13;

$duManager = new SpecUploadBase();
if($uploadType == $FILEUPLOAD || $uploadType == $NFNUPLOAD){
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
	if(array_key_exists('publicationGuid',$_REQUEST)) $duManager->setPublicationGuid($_REQUEST['publicationGuid']);
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

if($action == 'Automap Fields') $autoMap = true;

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN) $isEditor = 1;
elseif(array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin'])) $isEditor = 1;
if($isEditor && $collid){
	$duManager->readUploadParameters();
	$isLiveData = false;
	if($duManager->getCollInfo('managementtype') == 'Live Data') $isLiveData = true;

	//Grab field mapping, if mapping form was submitted
	if($action == 'Reset Field Mapping') $statusStr = $duManager->deleteFieldMap();
	else{
		$duManager->setFieldMaps($_POST);
		if($action == 'saveMapping'){
			$statusStr = $duManager->saveFieldMap($_POST);
			if(!$uspid) $uspid = $duManager->getUspid();
		}
	}
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
	<script>
		function verifyMappingForm(f){
			var sfArr = [];
			var idSfArr = [];
			var imSfArr = [];
			var tfArr = [];
			var idTfArr = [];
			var imTfArr = [];
			var catalogNumberIndex = 0;
			var possibleMappingErr = false;
			for(var i=0;i<f.length;i++){
				var obj = f.elements[i];
				if(obj.name == "sf[]"){
					if(sfArr.indexOf(obj.value) > -1){
						alert("<?php echo (isset($LANG['ERR_UNIQUE_D']) ? $LANG['ERR_UNIQUE_D'] : 'ERROR: Source field names must be unique (duplicate field: '); ?>"+obj.value+")");
						return false;
					}
					sfArr[sfArr.length] = obj.value;
					//Test value to make sure source file isn't missing the header and making directly to file record
					if(!possibleMappingErr){
						if(isNumeric(obj.value)){
							possibleMappingErr = true;
						}
						if(obj.value.length > 7){
							if(isNumeric(obj.value.substring(5))){
								possibleMappingErr = true;
							}
							else if(obj.value.slice(-5) == "aceae" || obj.value.slice(-4) == "idae"){
								possibleMappingErr = true;
							}
						}
					}
				}
				else if(obj.name == "ID-sf[]"){
					if(f.importident.value == "1"){
						if(idSfArr.indexOf(obj.value) > -1){
							alert("<?php echo (isset($LANG['ERR_UNIQUE_ID']) ? $LANG['ERR_UNIQUE_ID'] : 'ERROR: Source field names must be unique (Identification: '); ?>"+obj.value+")");
							return false;
						}
						idSfArr[idSfArr.length] = obj.value;
					}
				}
				else if(obj.name == "IM-sf[]"){
					if(f.importimage.value == "1"){
						if(imSfArr.indexOf(obj.value) > -1){
							alert("<?php echo (isset($LANG['ERR_UNIQUE_IM']) ? $LANG['ERR_UNIQUE_IM'] : 'ERROR: Source field names must be unique (Image: '); ?>"+obj.value+")");
							return false;
						}
						imSfArr[imSfArr.length] = obj.value;
					}
				}
				else if(obj.value != "" && obj.value != "unmapped"){
					if(obj.name == "tf[]"){
						if(tfArr.indexOf(obj.value) > -1){
							alert("<?php echo (isset($LANG['SAME_TARGET_D']) ? $LANG['SAME_TARGET_D'] : 'ERROR: Can\'t map to the same target field more than once ( '); ?>"+obj.value+")");
							return false;
						}
						tfArr[tfArr.length] = obj.value;
					}
					else if(obj.name == "ID-tf[]"){
						if(f.importident.value == "1"){
							if(idTfArr.indexOf(obj.value) > -1){
								alert("<?php echo (isset($LANG['SAME_TARGET_ID']) ? $LANG['SAME_TARGET_ID'] : 'ERROR: Can\'t map to the same target field more than once (Identification: '); ?>"+obj.value+")");
								return false;
							}
							idTfArr[idTfArr.length] = obj.value;
						}
					}
					else if(obj.name == "IM-tf[]"){
						if(f.importimage.value == "1"){
							if(imTfArr.indexOf(obj.value) > -1){
								alert("<?php echo (isset($LANG['SAME_TARGET_IM']) ? $LANG['SAME_TARGET_IM'] : 'ERROR: Can\'t map to the same target field more than once (Images: '); ?>"+obj.value+")");
								return false;
							}
							imTfArr[imTfArr.length] = obj.value;
						}
					}
				}
				if(obj.name == "tf[]"){
					//Is skeletal file upload
					if(obj.value == "catalognumber"){
						catalogNumberIndex = catalogNumberIndex + 1;
					}
					else if(obj.value == "othercatalognumbers"){
						catalogNumberIndex = catalogNumberIndex + 2;
					}
				}
			}
			if(f.uploadtype.value == 7){
				if(catalogNumberIndex == 0){
					//Skeletal records require catalog number to be mapped
					alert("<?php echo (isset($LANG['NEED_CAT']) ? $LANG['NEED_CAT'] : 'ERROR: catalogNumber or otherCatalogNumbers is required for Skeletal File Uploads'); ?>");
					return false;
				}
				else if(f.matchcatnum.checked == false && f.matchothercatnum.checked == false){
					alert("<?php echo (isset($LANG['SEL_MATCH']) ? $LANG['SEL_MATCH'] : 'ERROR: select which identifier will be used for record matching (required for Skeletal File imports)'); ?>");
					return false;
				}
				else{
					if((catalogNumberIndex == 1 && f.matchcatnum.checked == false) || (catalogNumberIndex == 2 && f.matchothercatnum.checked == false)){
						alert("<?php echo (isset($LANG['ID_NOT_MATCH']) ? $LANG['ID_NOT_MATCH'] : 'ERROR: identifier record matching does not match import fields (required for Skeletal File imports)'); ?>");
						return false;
					}
				}
			}
			if(f.observeruid && f.observeruid.value == ""){
				alert("<?php echo $LANG['SEL_TAR_USER']; ?>");
				return false;
			}
			if(possibleMappingErr){
				return confirm("<?php echo $LANG['FIRST_ROW']; ?>");
			}
			return true;
		}

		function verifySaveMapping(f){
			if(f.uspid.value == 0 && $("#newProfileNameDiv").is(':visible')==false){
				$("#newProfileNameDiv").show();
				alert("<?php echo $LANG['ENTER_PROF']; ?>");
				return false;
			}
			return true;
		}

		function pkChanged(selObj){
			if(selObj.value){
				$("#mdiv").show();
				//$("#uldiv").show();
			}
			else{
				$("#mdiv").hide();
				//$("#uldiv").show();
			}
		}
	</script>
	<style>
		.unmapped{ background: yellow; }
		fieldset{  padding: 15px; }
		legend{ font-weight: bold; }
	</style>
</head>
<body>
	<?php
$displayLeftMenu = (isset($collections_admin_specuploadMenu) ? $collections_admin_specuploadMenu:false);
include($SERVER_ROOT.'/includes/header.php');
?>
<div class="navpath">
	<a href="../../index.php"><?php echo htmlspecialchars((isset($LANG['HOME']) ? $LANG['HOME'] : 'Home'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
	<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo htmlspecialchars((isset($LANG['COL_MGMNT']) ? $LANG['COL_MGMNT'] : 'Collection Management Panel'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
	<a href="specuploadmanagement.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars((isset($LANG['LIST_UPLOAD']) ? $LANG['LIST_UPLOAD'] : 'List of Upload Profiles'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
	<b><?php echo (isset($LANG['SPEC_UPLOAD']) ? $LANG['SPEC_UPLOAD'] : 'Specimen Uploader'); ?></b>
</div>
<div role="main" id="innertext">
	<h1 class="page-heading"><?= $LANG['UP_MODULE']; ?></h1>
	<?php
	if($statusStr){
		echo '<hr />';
		echo '<div>'.$statusStr.'</div>';
		echo '<hr />';
	}
	$recReplaceMsg = '<span style="color:orange"><b>'.(isset($LANG['CAUTION']) ? $LANG['CAUTION'] : 'Caution').':</b></span> '.(isset($LANG['REC_REPLACE']) ? $LANG['REC_REPLACE'] : 'Matching records will be replaced with incoming records');
	if($isEditor && $collid){
		//Grab collection name and last upload date and display for all
		echo '<div style="font-weight:bold;font-size:130%;">'.$duManager->getCollInfo('name').'</div>';
		echo '<div style="margin:0px 0px 15px 15px;"><b>Last Upload Date:</b> '.($duManager->getCollInfo('uploaddate') ? $duManager->getCollInfo('uploaddate') : (isset($LANG['NOT_REC']) ? $LANG['NOT_REC'] : 'not recorded')).'</div>';
		$processingList = array('unprocessed' => 'Unprocessed', 'stage 1' => 'Stage 1', 'stage 2' => 'Stage 2', 'stage 3' => 'stage 3', 'pending review' => 'Pending Review',
			'expert required' => 'Expert Required', 'pending review-nfn' => 'Pending Review-NfN', 'reviewed' => 'Reviewed', 'closed' => 'Closed');
		if(!$ulPath) $ulPath = $duManager->uploadFile();
		if(($uploadType == $DWCAUPLOAD || $uploadType == $IPTUPLOAD || $uploadType == $SYMBIOTA) && $ulPath){
			//Data has been uploaded and it's a DWCA upload type
			if($duManager->analyzeUpload()){
				$metaArr = $duManager->getMetaArr();
				if(isset($metaArr['occur'])){
					?>
					<form name="dwcauploadform" action="specuploadmap.php" method="post" onsubmit="return verifyMappingForm(this)">
						<fieldset style="width:95%;">
							<legend><?php echo $duManager->getTitle();?></legend>
							<div style="margin:10px;">
								<b><?php echo (isset($LANG['SOURCE_ID']) ? $LANG['SOURCE_ID'] : 'Source Unique Identifier / Primary Key'); ?> (<span style="color:red"><?php echo (isset($LANG['REQ']) ? $LANG['REQ'] : 'required'); ?></span>): </b>
								<?php
								$dbpk = $duManager->getDbpk();
								$dbpkTitle = 'Core ID';
								if($dbpk == 'catalognumber') $dbpkTitle = 'Catalog Number';
								elseif($dbpk == 'occurrenceid') $dbpkTitle = 'Occurrence ID';
								echo $dbpkTitle;
								?>
								<div style="margin:10px;">
									<div>
										<input name="importspec" value="1" type="checkbox" checked />
										<?php echo (isset($LANG['IMPORT_OCCS']) ? $LANG['IMPORT_OCCS'] : 'Import Occurrence Records'); ?> (<a href="#" onclick="toggle('dwcaOccurDiv');return false;"><?php echo htmlspecialchars((isset($LANG['VIEW_DETS']) ? $LANG['VIEW_DETS'] : 'view details'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>)
									</div>
									<div id="dwcaOccurDiv" style="display:none;margin:20px;">
										<div style="margin-bottom:5px">
											<?php $duManager->echoFieldMapTable(true,'occur'); ?>
											<div>
												<?php echo '* '.(isset($LANG['UNVER']) ? $LANG['UNVER'] : 'Unverified mappings are displayed in yellow'); ?>
											</div>
										</div>
										<fieldset>
											<legend><?php echo (isset($LANG['CUSTOM_FILT']) ? $LANG['CUSTOM_FILT'] : 'Custom Occurrence Record Import Filters'); ?></legend>
											<?php
											$qArr = json_decode($duManager->getQueryStr(),true);
											$queryArr = array();
											if($qArr){
												foreach($qArr as $column => $aArr){
													foreach($aArr as $cond => $bArr){
														foreach($bArr as $v){
															$queryArr[] = array('col'=>$column,'cond'=>$cond,'val'=>$v);
														}
													}
												}
											}
											$sourceFields = $duManager->getSourceArr();
											sort($sourceFields);
											for($x=0;$x<3;$x++){
												$savedField = '';
												$savedCondition = '';
												$savedValue = '';
												if($action != 'Reset Field Mapping'){
													if(array_key_exists('filter'.$x, $_POST) && $_POST['filter'.$x]){
														$savedField = strtolower($_POST['filter'.$x]);
														$savedCondition = $_POST['condition'.$x];
														$savedValue = $_POST['value'.$x];
													}
													elseif(isset($queryArr[$x])){
														$savedField = $queryArr[$x]['col'];
														$savedCondition = $queryArr[$x]['cond'];
														$savedValue = $queryArr[$x]['val'];
													}
												}

												?>
												<div>
													<?php echo (isset($LANG['FIELD']) ? $LANG['FIELD'] : 'Field'); ?>:
													<select name="filter<?php echo $x; ?>" style="margin-right:10px">
														<option value=""><?php echo (isset($LANG['SEL_FIELD']) ? $LANG['SEL_FIELD'] : 'Select Field Name'); ?></option>
														<?php
														foreach($sourceFields as $f){
															echo '<option '.($savedField == strtolower($f) ? 'SELECTED' : '').'>'.$f.'</option>';
														}
														?>
													</select>
													<?php echo (isset($LANG['COND']) ? $LANG['COND'] : 'Condition'); ?>:
													<select name="condition<?php echo $x; ?>" style="margin-right:10px">
														<option value="EQUALS" <?php if($savedCondition == 'EQUALS') echo 'SELECTED'; ?>><?= $LANG['EQUALS'] ?></option>
														<option value="STARTS_WITH" <?php if($savedCondition == 'STARTS_WITH') echo 'SELECTED'; ?>><?= $LANG['STARTS_WITH'] ?></option>
														<option value="LIKE" <?php if($savedCondition == 'LIKE') echo 'SELECTED'; ?>><?= $LANG['LIKE'] ?></option>
														<option value="LESS_THAN" <?php if($savedCondition == 'LESS_THAN') echo 'SELECTED'; ?>><?= $LANG['LESS_THAN'] ?></option>
														<option value="GREATER_THAN" <?php if($savedCondition == 'GREATER_THAN') echo 'SELECTED'; ?>><?= $LANG['GREATER_THAN'] ?></option>
														<option value="IS_NULL" <?php if($savedCondition == 'IS_NULL') echo 'SELECTED'; ?>><?= $LANG['IS_NULL'] ?></option>
														<option value="NOT_NULL" <?php if($savedCondition == 'NOT_NULL') echo 'SELECTED'; ?>><?= $LANG['NOT_NULL'] ?></option>
													</select>
													<?php echo (isset($LANG['VALUE']) ? $LANG['VALUE'] : 'Value'); ?>:
													<input name="value<?php echo $x; ?>" type="text" value="<?php echo $savedValue; ?>" />
												</div>
												<?php
											}
											?>
											<div style="margin:5px"><?php echo '* '.(isset($LANG['MULT_TERMS']) ? $LANG['MULT_TERMS'] : 'Adding multiple terms separated by semi-colon will filter as an OR condition'); ?></div>
										</fieldset>
									</div>
									<div>
										<input name="importident" value="1" type="checkbox" <?php echo (isset($metaArr['ident']) ? 'checked' : 'disabled') ?> />
										<?php
										echo (isset($LANG['IMPORT_ID']) ? $LANG['IMPORT_ID'] : 'Import Identification History');
										if(isset($metaArr['ident'])){
											echo '(<a href="#" onclick="toggle(\'dwcaIdentDiv\');return false;">' . htmlspecialchars((isset($LANG['VIEW_DETS']) ? $LANG['VIEW_DETS'] : 'view details'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>)';
											?>
											<div id="dwcaIdentDiv" style="display:none;margin:20px;">
												<?php $duManager->echoFieldMapTable(true,'ident'); ?>
												<div>
													<?php echo '* '.(isset($LANG['UNVER']) ? $LANG['UNVER'] : 'Unverified mappings are displayed in yellow'); ?>
												</div>
											</div>
											<?php
										}
										else{
											echo '('.(isset($LANG['NOT_IN_DWC']) ? $LANG['NOT_IN_DWC'] : 'not present in DwC-Archive').')';
										}
										?>

									</div>
									<div>
										<input name="importimage" value="1" type="checkbox" <?php echo (isset($metaArr['image']) ? 'checked' : 'disabled') ?> />
										<?php echo (isset($LANG['IMP_IMG']) ? $LANG['IMP_IMG'] : 'Import Images'); ?>
										<?php
										if(isset($metaArr['image'])){
											echo '(<a href="#" onclick="toggle(\'dwcaImgDiv\');return false;">view details</a>)';
											?>
											<div id="dwcaImgDiv" style="display:none;margin:20px;">
												<?php $duManager->echoFieldMapTable(true,'image'); ?>
												<div>
													<?php echo '* '.(isset($LANG['UNVER']) ? $LANG['UNVER'] : 'Unverified mappings are displayed in yellow'); ?>
												</div>
											</div>
											<?php
										}
										else{
											echo '('.(isset($LANG['NOT_IN_DWC']) ? $LANG['NOT_IN_DWC'] : 'not present in DwC-Archive').')';
										}
										?>
									</div>
									<div style="margin:10px 0px;">
										<?php
										if($uspid) echo '<button type="submit" name="action" value="Reset Field Mapping">'.(isset($LANG['RESET_MAP']) ? $LANG['RESET_MAP'] : 'Reset Field Mapping').'</button>';
										echo '<button name="action" type="submit" value="saveMapping" onclick="return verifySaveMapping(this.form)" style="margin-left:5px">'.(isset($LANG['SAVE_MAP']) ? $LANG['SAVE_MAP'] : 'Save Mapping').'</button> ';
										if(!$uspid){
											echo '<span id="newProfileNameDiv" style="margin-left:15px;color:orange;display:none">';
											echo $LANG['NEW_PROF_TITLE'].': <input type="text" name="profiletitle" style="width:300px" value="'.$duManager->getTitle().'-'.date('Y-m-d').'" />';
											echo '</span>';
										}
										?>

									</div>
									<div style="margin-top:30px;">
										<?php
										if($isLiveData){
											if($duManager->getCollInfo('colltype') == 'General Observations'){
												echo (isset($LANG['TARGET_USER']) ? $LANG['TARGET_USER'] : 'Target User').': ';
												echo '<select name="observeruid">';
												echo '<option value="">'.(isset($LANG['SEL_TAR_USER']) ? $LANG['SEL_TAR_USER'] : 'Select Target User').'</option>';
												echo '<option value="">----------------------------</option>';
												$obsUidArr = $duManager->getObserverUidArr();
												foreach($obsUidArr as $uid => $userName){
													echo '<option value="'.$uid.'" '.($uid==$observerUid ? 'selected' : '').'>'.$userName.'</option>';
												}
												echo '</select>';
											}
											?>
											<div>
												<input name="matchcatnum" type="checkbox" value="1" checked />
												<?php echo (isset($LANG['MATCH_CAT']) ? $LANG['MATCH_CAT'] : 'Match on Catalog Number'); ?>
											</div>
											<div>
												<input name="matchothercatnum" type="checkbox" value="1" <?php echo ($matchOtherCatNum ? 'checked' : ''); ?> />
												<?php echo (isset($LANG['MATCH_O_CAT']) ? $LANG['MATCH_O_CAT'] : 'Match on Other Catalog Numbers'); ?>
											</div>
											<ul style="margin-top:2px">
												<li><?php echo $recReplaceMsg; ?></li>
												<li><?php echo $LANG['BOTH_CATS']; ?></li>
											</ul>
											<?php
										}
										?>
										<div style="margin:10px 0px;">
											<input name="verifyimages" type="checkbox" value="1" />
											<?php echo (isset($LANG['VER_LINKS']) ? $LANG['VER_LINKS'] : 'Verify image links'); ?>
										</div>
										<div style="margin:10px 0px;">
											<?php echo (isset($LANG['PROC_STATUS']) ? $LANG['PROC_STATUS'] : 'Processing Status'); ?>:
											<select name="processingstatus">
												<option value=""><?php echo (isset($LANG['NO_SETTING']) ? $LANG['NO_SETTING'] : 'Leave as is / No Explicit Setting'); ?></option>
												<option value="">--------------------------</option>
												<?php
												foreach($processingList as $ps){
													echo '<option value="'.$ps.'">'.ucwords($ps).'</option>';
												}
												?>
											</select>
										</div>
										<div style="margin:10px;">
											<button type="submit" name="action" value="Start Upload" onclick="this.form.action = 'specuploadprocessor.php'">
												<?php echo (isset($LANG['START_UPLOAD']) ? $LANG['START_UPLOAD'] : 'Start Upload'); ?>
											</button>
											<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
											<input type="hidden" name="collid" value="<?php echo $collid;?>" />
											<input type="hidden" name="publicationGuid" value="<?php echo $duManager->getPublicationGuid();?>" />
											<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
											<input type="hidden" name="ulpath" value="<?php echo $ulPath;?>" />
										</div>
									</div>
								</div>
							</div>
						</fieldset>
					</form>
					<?php
				}
			}
			else{
				if($duManager->getErrorStr()) echo '<div style="font-weight:bold;">'.$duManager->getErrorStr().'</div>';
				else echo '<div style="font-weight:bold;">'.(isset($LANG['UNK_ERR']) ? $LANG['UNK_ERR'] : 'Unknown error analyzing upload').'</div>';
			}
		}
		elseif($uploadType == $NFNUPLOAD && $ulPath){
			$duManager->analyzeUpload();
			?>
			<form name="filemappingform" action="specuploadprocessor.php" method="post" onsubmit="return verifyMappingForm(this)">
				<fieldset style="width:95%">
					<legend><?php echo (isset($LANG['NFN_IMPORT']) ? $LANG['NFN_IMPORT'] : 'Notes from Nature File Import'); ?></legend>
					<?php
					if($duManager->echoFieldMapTable(true, 'spec')){
						?>
						<div style="margin:10px 0px;">
							<?php echo (isset($LANG['PROC_STATUS']) ? $LANG['PROC_STATUS'] : 'Processing Status'); ?>:
							<select name="processingstatus">
								<option value=""><?php echo (isset($LANG['NO_SETTING']) ? $LANG['NO_SETTING'] : 'Leave as is / No Explicit Setting'); ?></option>
								<option value="">--------------------------</option>
								<?php
								foreach($processingList as $ps){
									echo '<option value="'.$ps.'">'.ucwords($ps).'</option>';
								}
								?>
							</select>
						</div>
						<div style="margin:20px;">
							<button type="submit" name="action" value="Start Upload"><?php echo (isset($LANG['START_UPLOAD']) ? $LANG['START_UPLOAD'] : 'Start Upload'); ?></button>
						</div>
						<?php
					}
					?>
				</fieldset>
				<input name="matchcatnum" type="hidden" value="0" />
				<input name="matchothercatnum" type="hidden" value="0" />
				<input name="uspid" type="hidden" value="<?php echo $uspid;?>" />
				<input name="collid" type="hidden" value="<?php echo $collid;?>" />
				<input name="uploadtype" type="hidden" value="<?php echo $uploadType;?>" />
				<input name="ulpath" type="hidden" value="<?php echo $ulPath;?>" />
			</form>
			<?php
		}
		elseif(($uploadType == $FILEUPLOAD || $uploadType == $SKELETAL) && $ulPath){
			$duManager->analyzeUpload();
			?>
			<form name="filemappingform" action="specuploadmap.php" method="post" onsubmit="return verifyMappingForm(this)">
				<fieldset style="width:95%;">
					<legend style="<?php if($uploadType == $SKELETAL) echo 'background-color:lightgreen'; ?>"><?php echo $duManager->getTitle(); ?></legend>
					<?php
					if(!$isLiveData && $uploadType != $SKELETAL){
						//Primary key field is required and must be mapped
						?>
						<div style="margin:20px;">
							<b><?php echo (isset($LANG['SOURCE_ID']) ? $LANG['SOURCE_ID'] : 'Source Unique Identifier / Primary Key'); ?> (<span style="color:red"><?php echo (isset($LANG['REQ']) ? $LANG['REQ'] : 'required'); ?></span>): </b>
							<?php
							$dbpk = $duManager->getDbpk();
							$dbpkOptions = $duManager->getDbpkOptions();
							?>
							<select name="dbpk" onchange="pkChanged(this);">
								<option value=""><?php echo (isset($LANG['SEL_KEY']) ? $LANG['SEL_KEY'] : 'Select Source Primary Key'); ?></option>
								<option value="">----------------------------------</option>
								<?php
								foreach($dbpkOptions as $f){
									echo '<option value="'.strtolower($f).'" '.($dbpk==strtolower($f) ? 'SELECTED' : '').'>'.$f.'</option>';
								}
								?>
							</select>
						</div>
						<?php
					}
					$displayStr = 'block';
					if(!$isLiveData) $displayStr = 'none';
					if($uploadType == $SKELETAL) $displayStr = 'block';
					if($dbpk) $displayStr = 'block';
					?>
					<div id="mdiv" style="display:<?php echo $displayStr; ?>">
						<?php $duManager->echoFieldMapTable($autoMap,'spec'); ?>
						<div>
							<?php
							echo '* '.$LANG['UNVER'].'<br/>';
							echo '* '.$LANG['SKIPPED'].'<br/>';
							echo '* '.$LANG['LEARN_MORE'].':';
							?>
							<div style="margin-left:15px;">
								<a href="https://symbiota.org/wp-content/uploads/SymbiotaOccurrenceFields.pdf" target="_blank">SymbiotaOccurrenceFields.pdf</a><br/>
								<a href="https://symbiota.org/symbiota-introduction/loading-specimen-data/" target="_blank"><?php echo htmlspecialchars((isset($LANG['LOADING_DATA']) ? $LANG['LOADING_DATA'] : 'Loading Data into Symbiota'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
							</div>
						</div>
						<div style="margin:10px;">
							<?php
							if($uspid){
								?>
								<button type="submit" name="action" value="Reset Field Mapping" ><?php echo (isset($LANG['RESET_MAP']) ? $LANG['RESET_MAP'] : 'Reset Field Mapping'); ?></button>
								<?php
							}
							?>
							<button type="submit" name="action" value="Automap Fields" ><?php echo (isset($LANG['AUTOMAP']) ? $LANG['AUTOMAP'] : 'Automap Fields'); ?></button>
							<button type="submit" name="action" value="Verify Mapping" ><?php echo (isset($LANG['VER_MAPPING']) ? $LANG['VER_MAPPING'] : 'Verify Mapping'); ?></button>
							<button type="submit" name="action" value="saveMapping" onclick="return verifySaveMapping(this.form)" ><?php echo (isset($LANG['SAVE_MAP']) ? $LANG['SAVE_MAP'] : 'Save Mapping'); ?></button>
							<span id="newProfileNameDiv" style="margin-left:15px;color:red;display:none">
								<?php echo (isset($LANG['NEW_PROF_TITLE']) ? $LANG['NEW_PROF_TITLE'] : 'New profile title'); ?>:
								<input type="text" name="profiletitle" style="width:300px" value="<?php echo $duManager->getTitle().'-'.date('Y-m-d'); ?>" />
							</span>
						</div>
						<hr />
						<div id="uldiv" style="margin-top:30px;">
							<?php
							if($isLiveData || $uploadType == $SKELETAL){
								if($duManager->getCollInfo('colltype') == 'General Observations'){
									echo (isset($LANG['TARGET_USER']) ? $LANG['TARGET_USER'] : 'Target User').': ';
									echo '<select name="observeruid">';
									echo '<option value="">'.(isset($LANG['SEL_TAR_USER']) ? $LANG['SEL_TAR_USER'] : 'Select Target User').'</option>';
									echo '<option value="">----------------------------</option>';
									$obsUidArr = $duManager->getObserverUidArr();
									foreach($obsUidArr as $uid => $userName){
										echo '<option value="'.$uid.'">'.$userName.'</option>';
									}
									echo '</select>';
								}
								?>
								<div>
									<input name="matchcatnum" type="checkbox" value="1" checked />
									<?php echo (isset($LANG['MATCH_CAT']) ? $LANG['MATCH_CAT'] : 'Match on Catalog Number'); ?>
								</div>
								<div>
									<input name="matchothercatnum" type="checkbox" value="1" <?php echo ($matchOtherCatNum ? 'checked' : ''); ?> />
									<?php echo (isset($LANG['MATCH_ON_CAT']) ? $LANG['MATCH_ON_CAT'] : 'Match on Other Catalog Numbers'); ?>
								</div>
								<ul style="margin-top:2px">
									<?php
									if($uploadType == $SKELETAL) echo '<li>'.$LANG['APPENDED'].'</li>';
									else echo '<li>'.$recReplaceMsg.'</li>';
									echo '<li>'.$LANG['BOTH_CATS'].'</li>';
									?>
								</ul>
								<?php
							}
							$versionCheckedStr = '';
							if($isLiveData) $versionCheckedStr = 'checked';
							?>
							<div style="margin:10px 0px;">
								<input name="versiondata" type="checkbox" value="1" <?php echo $versionCheckedStr; ?> />
								<?php echo (isset($LANG['VERSION_DATA_CHANGES']) ? $LANG['VERSION_DATA_CHANGES'] : 'Version data changes'); ?>
							</div>
							<div style="margin:10px 0px;">
								<input name="verifyimages" type="checkbox" value="1" />
								<?php echo (isset($LANG['VER_LINKS_MEDIA']) ? $LANG['VER_LINKS_MEDIA'] : 'Verify image links from associatedMedia field'); ?>
							</div>
							<div style="margin:10px 0px;">
								<?php echo (isset($LANG['PROC_STATUS']) ? $LANG['PROC_STATUS'] : 'Processing Status'); ?>:
								<select name="processingstatus">
									<option value=""><?php echo (isset($LANG['NO_SETTING']) ? $LANG['NO_SETTING'] : 'Leave as is / No Explicit Setting'); ?></option>
									<option value="">--------------------------</option>
									<?php
									foreach($processingList as $ps){
										echo '<option value="'.$ps.'">'.ucwords($ps).'</option>';
									}
									?>
								</select>
							</div>
							<div style="margin:20px;">
								<button type="submit" name="action" value="Start Upload" onclick="this.form.action = 'specuploadprocessor.php'">
									<?php echo (isset($LANG['START_UPLOAD']) ? $LANG['START_UPLOAD'] : 'Start Upload'); ?>
								</button>
							</div>
						</div>
						<?php
						if($uploadType == $SKELETAL){
							echo '<div style="margin-top:15px;">';
							echo (isset($LANG['SKEL_EXPLAIN']) ? $LANG['SKEL_EXPLAIN'] : '');
							echo '<ul>';
							echo '<li>'.(isset($LANG['SKEL_EXPLAIN_P1']) ? $LANG['SKEL_EXPLAIN_P1'] : '').'</li>';
							echo '<li>'.(isset($LANG['SKEL_EXPLAIN_P2']) ? $LANG['SKEL_EXPLAIN_P2'] : '').'</li>';
							echo '<li>'.(isset($LANG['SKEL_EXPLAIN_P3']) ? $LANG['SKEL_EXPLAIN_P3'] : '').'</li>';
							echo '<li>'.(isset($LANG['SKEL_EXPLAIN_P4']) ? $LANG['SKEL_EXPLAIN_P4'] : '').'</li>';
							echo '</ul>';
							echo '</div>';
						}
						?>
					</div>
				</fieldset>
				<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
				<input type="hidden" name="collid" value="<?php echo $collid;?>" />
				<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
				<input type="hidden" name="ulpath" value="<?php echo $ulPath;?>" />
			</form>
			<?php
		}
	}
	else{
		if(!$isEditor || !$collid) echo '<div style="font-weight:bold;font-size:120%;">'.(isset($LANG['NOT_AUTH']) ? $LANG['NOT_AUTH'] : 'ERROR: you are not authorized to upload to this collection').'</div>';
		else{
			echo '<div style="font-weight:bold;font-size:120%;">';
			echo (isset($LANG['PAGE_ERROR']) ? $LANG['PAGE_ERROR'] : '').' = ';
			echo ini_get("upload_max_filesize").'; post_max_size = '.ini_get('post_max_size');
			echo (isset($LANG['USE_BACK']) ? $LANG['USE_BACK'] : 'Use the back arrows to get back to the file upload page.');
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