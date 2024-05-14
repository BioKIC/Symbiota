<?php
//TODO: add code to automatically select hide locality details when taxon/state match name on list
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ObservationSubmitManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/observationsubmit.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/observationsubmit.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/observationsubmit.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/observationsubmit.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$action = array_key_exists("action",$_POST) ? $_POST["action"] : "";
$collId  = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$clid  = array_key_exists("clid",$_REQUEST) ? filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$recordedBy = array_key_exists("recordedby",$_POST) ? $_POST["recordedby"] : 0;
$uncertaintyInMeters = array_key_exists("coordinateuncertaintyinmeters",$_POST) ? filter_var($_POST["coordinateuncertaintyinmeters"], FILTER_SANITIZE_NUMBER_INT) : 50;

//Sanitation
if(!is_numeric($collId)) $collId = 0;
if(!is_numeric($clid)) $clid = 0;
$recordedBy = htmlspecialchars(strip_tags($recordedBy));
if(!is_numeric($uncertaintyInMeters)) $uncertaintyInMeters = 50;

$obsManager = new ObservationSubmitManager();
$obsManager->setCollid($collId);
$collMap = $obsManager->getCollMap();
if(!$collId && $collMap) $collId = $collMap['collid'];

$isEditor = 0;
$occid = 0;
if($collMap){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollAdmin'])){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollEditor'])){
		$isEditor = 1;
	}
	if(($isEditor && $action == (isset($LANG['SUBMIT_OBS']) ? $LANG['SUBMIT_OBS'] : 'Submit Observation')) || $isEditor && $action == "Submit"){
		$occid = htmlspecialchars($obsManager->addObservation($_POST), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
	}
	if(!$recordedBy) $recordedBy = $obsManager->getUserName();
}
$clArr = $obsManager->getChecklists();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['OBS_SUBMIT']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<style>
		.imgSubmitDiv{ padding:10px; width:700px; border:1px solid grey; background-color:#F5F5F5; }
	</style>
	<script type="text/javascript">
		<?php
		$maxUpload = ini_get('upload_max_filesize');
		$maxUpload = str_replace("M", "000000", $maxUpload);
		if($maxUpload > 10000000) $maxUpload = 10000000;
		echo 'var maxUpload = '.$maxUpload.";\n";
		?>
	</script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../../js/symb/collections.coordinateValidation.js?ver=1" type="text/javascript"></script>
	<script src="../../js/symb/collections.editor.observations.js?ver=1" type="text/javascript"></script>
	<style>
		#dmsdiv{ display: none; clear: both; padding: 15px; width: 565px; background-color: #f2f2f2; border: 2px outset #E8EEFA; }
		#dmsButton { margin: 0px 3px; }
		.lat-long-group-label {
			margin-top: 4px;
		}
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_editor_observationsubmitMenu)?$collections_editor_observationsubmitMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	echo '<div class="navpath">';
	echo '<a href="../../index.php">' . (isset($LANG['HOME']) ? $LANG['HOME'] : 'Home') . '</a> &gt;&gt; ';
	if(isset($collections_editor_observationsubmitCrumbs)){
		echo $collections_editor_observationsubmitCrumbs;
	}
	else{
		echo '<a href="../../profile/viewprofile.php?tabindex=1">' . (isset($LANG['PERS_MNGT']) ? $LANG['PERS_MNGT'] : 'Personal Management') . '</a> &gt;&gt; ';
	}
	echo '<b>' . (isset($LANG['OBS_SUB']) ? $LANG['OBS_SUB'] : 'Observation Submission') . '</b>';
	echo '</div>';
	?>
	<div role="main" id="innertext">
		<h1 class="page-heading"><?php echo $collMap['collectionname'] ?? $LANG['NO_COLLECTION']; ?></h1>
		<?php
		if($action || (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post' && empty($_FILES) && empty($_POST))){
			?>
			<hr />
			<div style="margin:15px;font-weight:bold;">
				<?php
				if($occid){
					?>
					<div style="color:green;">
						<?php echo $LANG['SUCCESS_IMAGE']; ?>
					</div>
					<div style="font:weight;font-size:120%;margin-top:10px;">
						<?php echo $LANG['OPEN']; ?> <a href="../individual/index.php?occid=<?php echo htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($LANG['OCC_DET_VIEW'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> <?php echo htmlspecialchars($LANG['TO_SEE_NEW'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
					</div>
					<?php
					if($clid){
						$checklistName = 'target';
						if(isset($clArr[$clid])) $checklistName = $clArr[$clid];
						?>
						<div style="font:weight;font-size:120%;margin-top:10px;">
							<?php echo $LANG['GO_TO']; ?> <a href="../../checklists/checklist.php?clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($checklistName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> <?php echo htmlspecialchars($LANG['CHECKLIST'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
						</div>
						<?php
					}
				}
				$errArr = $obsManager->getErrorArr();
				if($errArr){
					echo '<div style="color:red;">';
					echo $LANG['ERROR'].':<ol>';
					foreach($errArr as $e){
						echo '<li>'.$e.'</li>';
					}
					echo '</ol>';
					echo '</div>';
				}
				if(!$action){
					echo $LANG['UNK_ERROR'];
				}
				?>
			</div>
			<hr />
			<?php
		}
		if($isEditor){
			?>
			<div>* <?php echo $LANG['FIELD_NAMES_REQ']; ?></div>
			<div style="margin:10px;">
				<form id='obsform' name='obsform' action='observationsubmit.php' method='post' enctype='multipart/form-data' onsubmit="return verifyObsForm(this)">
					<fieldset>
						<legend><b><?php echo $LANG['IMAGES']; ?></b></legend>
				    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
						<input type='hidden' name='MAX_FILE_SIZE' value='<?php echo $maxUpload; ?>' />
						<?php
						for($x=1;$x<6;$x++){
							?>
							<div class="imgSubmitDiv" id="img<?php echo $x; ?>div" style="<?php if($x > 1) echo 'display:none'; ?>">
								<div style="margin-bottom: 10px;">
									<label for="imgfile<?php echo $x; ?>"><?php echo $LANG['IMAGE'].' '.$x; ?>:</label>
									<input name='imgfile<?php echo $x; ?>' id='imgfile<?php echo $x; ?>' type='file' onchange="verifyImageSize(this)" <?php if($x == 1) echo 'required'; ?> />
								</div>
								<div>
									<section class="flex-form">
										<div style="margin-bottom: 10px;">
											<label for="caption<?php echo $x; ?>"><?php echo $LANG['CAPTION']; ?>:</label>
											<input name="caption<?php echo $x; ?>" id="caption<?php echo $x; ?>" type="text" style="width:200px;" />
										</div>
										<div style="margin-bottom: 10px;">
											<label for="notes<?php echo $x; ?>"><?php echo $LANG['IMG_REMARKS']; ?>:</label>
											<input name="notes<?php echo $x; ?>" id="notes<?php echo $x; ?>" type="text" style="width:275px;" />
										</div>
									</section>

									<?php
									if($x < 5){
										?>
										<div style="margin-bottom: 10px;">
											<a href="#" onclick="toggle('img<?php echo ($x+1); ?>div');return false">
												<?php echo $LANG['ADD_ANOTHER']; ?>
											</a>
										</div>
										<?php
									}
									?>
								</div>
							</div>
							<?php
						}
						?>
					</fieldset>
					<div style="margin:15px">
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<input type="submit" name="action" value="<?php echo (isset($LANG['SUBMIT_OBS']) ? $LANG['SUBMIT_OBS'] : 'Submit Observation'); ?>" />
					</div>
					<!-- <div style="margin-left:10px;clear:both">* Uploading web-ready images recommended. Upload image size can not be greater than <?php echo ($maxUpload/1000000); ?>MB</div>  -->
					<fieldset>
						<legend><b><?php echo $LANG['OBSERVATION']; ?></b></legend>
						<div style="clear:both;" class="p1">
							<section class="flex-form">
								<div>
									<label for="sciname"><?php echo $LANG['SCINAME']; ?>:</label>
									<input type="text" id="sciname" name="sciname" maxlength="250" style="width:390px;" required />
									<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
								</div>
								<div>
									<label for="scientificnameauthorship"><?php echo $LANG['AUTHOR']; ?>:</label>
									<input type="text" name="scientificnameauthorship" id="scientificnameauthorship" maxlength="100" value="" />
								</div>
							</section>
							<div style="clear:both;" class="flex-form">
								<label for="family"><?php echo $LANG['FAMILY']; ?>:</label>
								<input type="text" name="family" id="family" size="30" maxlength="50" value="" />
							</div>
						</div>
						<div style="clear:both;" class="flex-form">
							<div>
								<label for="recordedby"><?php echo $LANG['OBSERVER']; ?>:</label>
								<input type="text" name="recordedby" id="recordedby" maxlength="255" style="width:250px;" value="<?php echo $recordedBy; ?>" required />
							</div>
							<div>
								<label for="recordnumber"><?php echo $LANG['NUMBER']; ?>:</label>
								<input type="text" name="recordnumber" id="recordnumber" maxlength="45" style="width:80px;" title="Observer Number, if observer uses a numbering system " />
							</div>
							<div>
								<label for="eventdate"><?php echo $LANG['DATE']; ?>:</label>
								<input type="text" id="eventdate" name="eventdate" style="width:120px;" onchange="verifyDate(this);" title="format: yyyy-mm-dd" required />
								<a href="#" style="margin:15px 0px 0px 5px;" onclick="toggle('obsextradiv');return false" title="<?php echo (isset($LANG['IEDIT_BTN']) ? $LANG['EDIT_BTN'] : 'Display additional fields'); ?>" aria-label="<?php echo (isset($LANG['IEDIT_BTN']) ? $LANG['EDIT_BTN'] : 'Display additional fields'); ?>">
									<img src="../../images/editplus.png" style="width:1.5em;" alt="<?php echo (isset($LANG['IMG_EDIT']) ? $LANG['IMG_EDIT'] : 'Display additional fields image'); ?>"/>
								</a>
							</div>
						</div>
						<div id="obsextradiv" style="clear:both;padding:3px 0px 0px 10px;margin-bottom:20px;display:none;">
							<div>
								<label for="associatedcollectors"><?php echo $LANG['ASSOC_OBSERVERS']; ?>:
								</label>
								<input type="text" name="associatedcollectors" id="associatedcollectors" maxlength="255" style="width:350px;" value="" />
							</div>
							<section class="flex-form">
								<div>
									<label for="identifiedby"><?php echo $LANG['IDED_BY']; ?>:</label>
									<input type="text" name="identifiedby" id="identifiedby" maxlength="255" style="" value="" />
								</div>
								<div>
									<label for="dateidentified"><?php echo $LANG['DATE_IDED']; ?>:</label>
									<input type="text" name="dateidentified" id="dateidentified" maxlength="45" style="" value="" />
								</div>
							</section>
							<div>
								<label for="identificationreferences"><?php echo $LANG['ID_REFS']; ?>:</label>
								<input type="text" name="identificationreferences" id="identificationreferences" style="width:450px;" title="cf, aff, etc" />
							</div>
							<div style="clear:both;padding:3px 0px 0px 0px;" >
								<label for="taxonremarks"><?php echo $LANG['ID_REMARKS']; ?>:</label>
								<input type="text" name="taxonremarks" id="taxonremarks" style="width:500px;" value="" />
							</div>
						</div>
					</fieldset>
					<fieldset style="margin-top:10px;">
						<legend><b><?php echo $LANG['LOCALITY']; ?></b></legend>
						<div style="clear:both;" class="flex-form">
							<div>
								<label for="country"><?php echo $LANG['COUNTRY']; ?>:</label>
								<input type="text" name="country" id="country" style="width:150px;" value="" required />
							</div>
							<div>
								<label for="stateprovince"><?php echo $LANG['STATE_PROVINCE']; ?>:</label>
								<input type="text" name="stateprovince" id="stateprovince" style="width:150px;" value="" required />
							</div>
							<div>
								<label for="county"><?php echo $LANG['COUNTY_PARISH']; ?>:</label>
								<input type="text" name="county" id="county" style="width:150px;" value="" />
							</div>
						</div>
						<div style="clear:both;margin:4px 0px 2px 0px;">
							<label for="locality"><?php echo $LANG['LOCALITY']; ?>:</label>
							<input type="text" name="locality" id="locality" style="width:95%;" value="" required />
						</div>
						<div style="clear:both;margin-bottom:5px;">
							<input type="checkbox" name="localitysecurity" id="localitysecurity" style="" value="1" title="<?php echo $LANG['HIDE_LOC_SHORT']; ?>" />
							<label for="localitysecurity"><?php echo $LANG['HIDE_LOC_LONG']; ?></label>
						</div>
						<div style="clear:both;" class="flex-form">
							<div>
								<label for="decimallatitude"><?php echo $LANG['LATITUDE']; ?>:</label>
								<input type="text" id="decimallatitude" name="decimallatitude" maxlength="10" style="width:88px;" value="" onchange="verifyLatValue(this.form)" title="Decimal Format (eg 34.5436)" required />
							</div>
							<div>
								<label for="decimallongitude"><?php echo $LANG['LONGITUDE']; ?>:</label>
								<input type="text" id="decimallongitude" name="decimallongitude" maxlength="13" style="width:88px;" value="" onchange="verifyLngValue(this.form)" title="Decimal Format (eg -112.5436)" required />
							</div>
							<div style="margin-top:10px; margin-left:3px; margin-bottom:10px" >
								<a tabindex="0" onclick="openMappingAid('obsform','decimallatitude','decimallongitude');return false;">
									<img src="../../images/world.png" style="width:1.3em;" title="Coordinate Map Aid" alt="<?php echo (isset($LANG['IMG_GLOBE']) ? $LANG['IMG_GLOBE'] : 'A small image of the globe'); ?>" />
								</a>
								<button id="dmsButton" type="button" onclick="toggle('dmsdiv');"><?php echo $LANG['DMS']; ?></button>
							</div>
							<div>
								<label for="coordinateuncertaintyinmeters"><?php echo $LANG['UNCERTAINTY_M']; ?>:</label>
								<input type="text" id="coordinateuncertaintyinmeters" name="coordinateuncertaintyinmeters" maxlength="10" style="width:110px;" onchange="inputIsNumeric(this, 'Lat/long uncertainty')" title="Uncertainty in Meters" value="<?php echo $uncertaintyInMeters; ?>" required />
							</div>
							<div>
								<label for="geodeticdatum"><?php echo $LANG['DATUM']; ?>:</label>
								<input type="text" name="geodeticdatum" id="geodeticdatum" maxlength="255" style="width:80px;" />
							</div>
							<div>
								<label for="minimumelevationinmeters"><?php echo $LANG['ELEV_M']; ?>:</label>
								<input type="text" name="minimumelevationinmeters" id="minimumelevationinmeters" maxlength="6" style="width:95px;" value="" onchange="verifyElevValue(this)" title="Minumum Elevation In Meters" />
							</div>
							<div>
								<label for="verbatimelevation"><?php echo $LANG['ELEV_FT']; ?>:</label>
								<input type="text" name="verbatimelevation" id="verbatimelevation" style="width:85px;" value="" onchange="convertElevFt(this.form)" title="Minumum Elevation In Feet" />
							</div>
							<div>
								<label for="georeferenceremarks"><?php echo $LANG['GEO_REMARKS']; ?>:</label>
								<input type="text" name="georeferenceremarks" id="georeferenceremarks" maxlength="255" style="width:250px;" value="" />
							</div>
						</div>
						<div id="dmsdiv">
							<section class="flex-form">
								<div class="lat-long-group-label">
									<em><?php echo $LANG['LATITUDE']; ?>: </em><br>
								</div>
								<div>
									<label for="latdeg"><?php echo $LANG['LATITUDE_DEG']; ?>: </label>
									<input id="latdeg" style="width:35px;" title="<?php echo $LANG['LATITUDE_DEG']; ?>" />
								</div>
								<div>
									<label for="latmin"><?php echo $LANG['LATITUDE_MIN']; ?>:</label>
									<input id="latmin" style="width:50px;" title="<?php echo $LANG['LATITUDE_MIN']; ?>" />
								</div>
								<div>
									<label for="latsec"><?php echo $LANG['LATITUDE_SEC']; ?>:</label>
									<input id="latsec" style="width:50px;" title="<?php echo $LANG['LATITUDE_SEC']; ?>" />
								</div>
								<div>
									<label for="latns"><?php echo $LANG['LATDIRECTION'] ?>:</label>
									<select id="latns">
										<option><?php echo $LANG['N']; ?></option>
										<option><?php echo $LANG['S']; ?></option>
									</select>
								</div>
							</section>
							<section class="flex-form">
								<div class="lat-long-group-label">
									<em><?php echo $LANG['LONGITUDE']; ?>:</em><br>
								</div>
								<div>
									<label for="lngdeg"><?php echo $LANG['LONGITUDE_DEG']; ?>:</label>
									<input id="lngdeg" style="width:35px;" title="<?php echo $LANG['LONGITUDE_DEG']; ?>" />
								</div>
								<div>
									<label for="lngmin"><?php echo $LANG['LONGITUDE_MIN']; ?>:</label>
									<input id="lngmin" style="width:50px;" title="<?php echo $LANG['LONGITUDE_MIN']; ?>" />
								</div>
								<div>
									<label for="lngsec"><?php echo $LANG['LONGITUDE_SEC']; ?>:</label>
									<input id="lngsec" style="width:50px;" title="<?php echo $LANG['LONGITUDE_SEC']; ?>" />
								</div>
								<div>
									<label for="lngew"><?php echo $LANG['LONGDIRECTION'] ?>:</label>
									<select id="lngew">
										<option><?php echo $LANG['E']; ?></option>
										<option SELECTED><?php echo $LANG['W']; ?></option>
									</select>
								</div>
							</section>
							<div style="margin:5px;">
								<input type="button" value="Insert Lat/Long Values" onclick="insertLatLng(this.form)" />
							</div>
						</div>
					</fieldset>
					<fieldset style="margin-top:10px;">
						<legend><b><?php echo $LANG['MISC']; ?></b></legend>
						<div style="padding:3px;">
							<label for="habitat"><?php echo $LANG['HABITAT']; ?>:</label>
							<input type="text" name="habitat" id="habitat" style="width:600px;" value="" />
						</div>
						<div style="padding:3px;">
							<label for="substrate"><?php echo $LANG['SUBSTRATE']; ?>:</label>
							<input type="text" name="substrate" id="substrate" style="width:600px;" value="" />
						</div>
						<div style="padding:3px;">
							<label for="associatedtaxa"><?php echo $LANG['ASSOC_TAXA']; ?>:</label>
							<input type="text" name="associatedtaxa" id="associatedtaxa" style="width:600px;" value="" />
						</div>
						<div style="padding:3px;">
							<label for="verbatimattributes"><?php echo $LANG['DESC_ORG']; ?>:</label>
							<input type="text" name="verbatimattributes" id="verbatimattributes" style="width:600px;" value="" />
						</div>
						<div style="padding:3px;">
							<label for="occurrenceremarks"><?php echo $LANG['GENERAL_NOTES']; ?>:</label>
							<input type="text" name="occurrenceremarks" id="occurrenceremarks" style="width:600px;" value="" title="Occurrence Remarks" />
						</div>
						<section class="flex-form">
							<div style="padding:3px;">
								<span title="<?php echo (isset($LANG['REP_COND_EG']) ? $LANG['REP_COND_EG'] : 'e.g. sterile, flw, frt, flw/frt'); ?>">
									<label for="reproductivecondition"><?php echo $LANG['REP_COND']; ?>:</label>
									<input type="text" name="reproductivecondition" id="reproductivecondition" maxlength="255" style="width:140px;" value="" placeholder="<?php echo (isset($LANG['REP_COND_EG']) ? $LANG['REP_COND_EG'] : 'e.g. sterile, flw, frt, flw/frt'); ?>" />
								</span>
							</div>
							<div style="padding:3px;">
								<span title="<?php echo (isset($LANG['EST_MEANS_EG']) ? $LANG['EST_MEANS_EG'] : 'e.g. planted, seeded, garden escape, etc.'); ?> ">
									<label for="establishmentmeans"><?php echo $LANG['EST_MEANS']; ?>:</label>
									<input type="text" name="establishmentmeans" id="establishmentmeans" maxlength="32" style="width: 230px;" value="" placeholder="<?php echo (isset($LANG['EST_MEANS_EG']) ? $LANG['EST_MEANS_EG'] : 'e.g. planted, seeded, garden escape, etc.'); ?>" />
								</span>
							</div>
							<div style="padding:3px;">
								<span title="<?php echo (isset($LANG['CULT_CAPT_EG']) ? $LANG['CULT_CAPT_EG'] : 'Click if specimen was cultivated or captive'); ?>">
									<input type="checkbox" name="cultivationstatus" id="repcond" style="" value="" />
									<label for="repcond"> <?php echo $LANG['CULT_CAPT']; ?></label>
								</span>
							</div>
						</section>
					</fieldset>
					<?php
					if($clArr){
						?>
						<fieldset class="top-breathing-room-rel">
							<legend><b><?php echo $LANG['LINK_CHECK']; ?></b></legend>
							<label for="clid"><?php echo $LANG['SP_LIST']; ?>:</label>
							<select name='clid' id='clid'>
								<option value="0"><?php echo $LANG['SEL_CHECKLIST']; ?></option>
								<option value="0">------------------------------</option>
								<?php
								foreach($clArr as $id => $clName){
									echo '<option value="' . $id . '" ' . ($id==$clid?'SELECTED':'') . '>' . $clName . '</option>';
								}
								?>
							</select>
						</fieldset>
						<?php
					}
					?>
					<div class="top-breathing-room-rel">
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<button type="submit" name="action" value="Submit"><?php echo $LANG['SUBMIT']; ?></button>
					</div>
				</form>
			</div>
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
