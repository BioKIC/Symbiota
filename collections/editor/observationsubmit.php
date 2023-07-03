<?php
//TODO: add code to automatically select hide locality details when taxon/state match name on list
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ObservationSubmitManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/observationsubmit.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/observationsubmit.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/observationsubmit.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/observationsubmit.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$action = array_key_exists("action",$_POST)?$_POST["action"]:"";
$collId  = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$clid  = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$recordedBy = array_key_exists("recordedby",$_POST)?$_POST["recordedby"]:0;
$uncertaintyInMeters = array_key_exists("coordinateuncertaintyinmeters",$_POST)?$_POST["coordinateuncertaintyinmeters"]:50;

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
	if($isEditor && $action == "Submit Observation"){
		$occid = $obsManager->addObservation($_POST);
	}
	if(!$recordedBy) $recordedBy = $obsManager->getUserName();
}
$clArr = $obsManager->getChecklists();
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['OBS_SUBMIT']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<style>
		.imgSubmitDiv{ padding:10px; width:700px; height:80px; border:1px solid grey; background-color:#F5F5F5; }
	</style>
	<script type="text/javascript">
		<?php
		$maxUpload = ini_get('upload_max_filesize');
		$maxUpload = str_replace("M", "000000", $maxUpload);
		if($maxUpload > 10000000) $maxUpload = 10000000;
		echo 'var maxUpload = '.$maxUpload.";\n";
		?>
	</script>
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script src="../../js/symb/collections.coordinateValidation.js?ver=1" type="text/javascript"></script>
	<script src="../../js/symb/collections.editor.observations.js?ver=1" type="text/javascript"></script>
	<style>
		#dmsdiv{ display: none; clear: both; padding: 15px; width: 300px; background-color: #f2f2f2; border: 2px outset #E8EEFA; }
		#dmsButton { margin: 0px 3px; }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_editor_observationsubmitMenu)?$collections_editor_observationsubmitMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	echo '<div class="navpath">';
	echo '<a href="../../index.php">Home</a> &gt;&gt; ';
	if(isset($collections_editor_observationsubmitCrumbs)){
		echo $collections_editor_observationsubmitCrumbs;
	}
	else{
		echo '<a href="../../profile/viewprofile.php?tabindex=1">Personal Management</a> &gt;&gt; ';
	}
	echo '<b>Observation Submission</b>';
	echo '</div>';
	?>
	<div id="innertext">
		<h1><?php echo $collMap['collectionname']; ?></h1>
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
						<?php echo $LANG['OPEN']; ?> <a href="../individual/index.php?occid=<?php echo $occid; ?>" target="_blank"><?php echo $LANG['OCC_DET_VIEW']; ?></a> <?php echo $LANG['TO_SEE_NEW']; ?>
					</div>
					<?php
					if($clid){
						$checklistName = 'target';
						if(isset($clArr[$clid])) $checklistName = $clArr[$clid];
						?>
						<div style="font:weight;font-size:120%;margin-top:10px;">
							<?php echo $LANG['GO_TO']; ?> <a href="../../checklists/checklist.php?clid=<?php echo $clid; ?>" target="_blank"><?php echo $checklistName; ?></a> <?php echo $LANG['CHECKLIST']; ?>
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
						<div style="margin:15px;float:right">
							<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
							<input type="submit" name="action" value="Submit Observation" />
						</div>
						<?php
						for($x=1;$x<6;$x++){
							?>
							<div class="imgSubmitDiv" id="img<?php echo $x; ?>div" style="<?php if($x > 1) echo 'display:none'; ?>">
								<div>
									<b><?php echo $LANG['IMAGE'].' '.$x; ?>:</b>
									<input name='imgfile<?php echo $x; ?>' type='file' onchange="verifyImageSize(this)" <?php if($x == 1) echo 'required'; ?> />
								</div>
								<div style="margin:5px;">
									<div style="float:left;">
										<?php echo $LANG['CAPTION']; ?>:
										<input name="caption<?php echo $x; ?>" type="text" style="width:200px;" />
									</div>
									<div style="float:left;margin-left:20px;">
										<?php echo $LANG['IMG_REMARKS']; ?>:
										<input name="notes<?php echo $x; ?>" type="text" style="width:275px;" />
									</div>
									<?php
									if($x < 5){
										?>
										<div style="clear:both;float:right;margin:10px 40px;">
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
						<!-- <div style="margin-left:10px;clear:both">* Uploading web-ready images recommended. Upload image size can not be greater than <?php echo ($maxUpload/1000000); ?>MB</div>  -->
					</fieldset>
					<fieldset>
						<legend><b><?php echo $LANG['OBSERVATION']; ?></b></legend>
						<div style="clear:both;" class="p1">
							<div style="float:left;">
								<b><?php echo $LANG['SCINAME']; ?>:</b><br/>
								<input type="text" id="sciname" name="sciname" maxlength="250" style="width:390px;" required />
								<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
							</div>
							<div style="float:left;">
								<?php echo $LANG['AUTHOR']; ?>:<br/>
								<input type="text" name="scientificnameauthorship" maxlength="100" tabindex="-1" style="" value="" />
							</div>
						</div>
						<div style="clear:both;margin-left:10px;padding:3px 0px 0px 10px;">
							<span><?php echo $LANG['FAMILY']; ?>:</span>
							<input type="text" name="family" size="30" maxlength="50" style="" tabindex="-1" value="" />
						</div>
						<div style="clear:both;">
							<div style="float:left;">
								<b><?php echo $LANG['OBSERVER']; ?>:</b><br/>
								<input type="text" name="recordedby" maxlength="255" style="width:250px;" value="<?php echo $recordedBy; ?>" required />
							</div>
							<div style="float:left">
								<?php echo $LANG['NUMBER']; ?>:<br/>
								<input type="text" name="recordnumber" maxlength="45" style="width:80px;" title="Observer Number, if observer uses a numbering system " />
							</div>
							<div style="float:left;">
								<b><?php echo $LANG['DATE']; ?>:</b><br/>
								<input type="text" id="eventdate" name="eventdate" style="width:120px;" onchange="verifyDate(this);" title="format: yyyy-mm-dd" required />
								<a style="margin:15px 0px 0px 5px;" onclick="toggle('obsextradiv');return false" title="Display additional fields">
									<img src="../../images/editplus.png" style="width:15px;" />
								</a>
							</div>
						</div>
						<div id="obsextradiv" style="clear:both;padding:3px 0px 0px 10px;margin-bottom:20px;display:none;">
							<div style="clear:both;margin-top:5px;">
								<?php echo $LANG['ASSOC_OBSERVERS']; ?>:
								<input type="text" name="associatedcollectors" maxlength="255" style="width:350px;" value="" />
							</div>
							<div style="float:left;margin:3px 0px 0px 0px;">
								<?php echo $LANG['IDED_BY']; ?>:
								<input type="text" name="identifiedby" maxlength="255" style="" value="" />
							</div>
							<div style="float:left;margin:3px 0px 0px 10px;">
								<?php echo $LANG['DATE_IDED']; ?>:
								<input type="text" name="dateidentified" maxlength="45" style="" value="" />
							</div>
							<div style="clear:both;padding:3px 0px 0px 0px;" >
								<?php echo $LANG['ID_REFS']; ?>:
								<input type="text" name="identificationreferences" style="width:450px;" title="cf, aff, etc" />
							</div>
							<div style="clear:both;padding:3px 0px 0px 0px;" >
								<?php echo $LANG['ID_REMARKS']; ?>:
								<input type="text" name="taxonremarks" style="width:500px;" value="" />
							</div>
						</div>
					</fieldset>
					<fieldset>
						<legend><b><?php echo $LANG['LOCALITY']; ?></b></legend>
						<div style="clear:both;">
							<div style="float:left;">
								<b><?php echo $LANG['COUNTRY']; ?></b><br/>
								<input type="text" name="country" style="width:150px;" value="" required />
							</div>
							<div style="float:left;">
								<b><?php echo $LANG['STATE_PROVINCE']; ?></b><br/>
								<input type="text" name="stateprovince" style="width:150px;" value="" required />
							</div>
							<div style="float:left;">
								<?php echo $LANG['COUNTY_PARISH']; ?><br/>
								<input type="text" name="county" style="width:150px;" value="" />
							</div>
						</div>
						<div style="clear:both;margin:4px 0px 2px 0px;">
							<b><?php echo $LANG['LOCALITY']; ?>:</b><br />
							<input type="text" name="locality" style="width:95%;" value="" required />
						</div>
						<div style="clear:both;margin-bottom:5px;">
							<input type="checkbox" name="localitysecurity" style="" value="1" title="<?php echo $LANG['HIDE_LOC_SHORT']; ?>" />
							<?php echo $LANG['HIDE_LOC_LONG']; ?>
						</div>
						<div style="clear:both;">
							<div style="float:left;">
								<b><?php echo $LANG['LATITUDE']; ?></b><br/>
								<input type="text" id="decimallatitude" name="decimallatitude" maxlength="10" style="width:88px;" value="" onchange="verifyLatValue(this.form)" title="Decimal Format (eg 34.5436)" required />
							</div>
							<div style="float:left;">
								<b><?php echo $LANG['LONGITUDE']; ?></b><br/>
								<input type="text" id="decimallongitude" name="decimallongitude" maxlength="13" style="width:88px;" value="" onchange="verifyLngValue(this.form)" title="Decimal Format (eg -112.5436)" required />
							</div>
							<div style="float:left;margin-top:15px; margin-left:3px;" >
								<a onclick="openMappingAid('obsform','decimallatitude','decimallongitude');return false;">
									<img src="../../images/world.png" style="width:15px;" title="Coordinate Map Aid" />
								</a>
								<button id="dmsButton" type="button" onclick="toggle('dmsdiv');"><?php echo $LANG['DMS']; ?></button>
							</div>
							<div style="float:left;">
								<b><?php echo $LANG['UNCERTAINTY_M']; ?></b><br/>
								<input type="text" id="coordinateuncertaintyinmeters" name="coordinateuncertaintyinmeters" maxlength="10" style="width:110px;" onchange="inputIsNumeric(this, 'Lat/long uncertainty')" title="Uncertainty in Meters" value="<?php echo $uncertaintyInMeters; ?>" required />
							</div>
							<div style="float:left;">
								<?php echo $LANG['DATUM']; ?><br/>
								<input type="text" name="geodeticdatum" maxlength="255" style="width:80px;" />
							</div>
							<div style="float:left;">
								<?php echo $LANG['ELEV_M']; ?><br/>
								<input type="text" name="minimumelevationinmeters" maxlength="6" style="width:95px;" value="" onchange="verifyElevValue(this)" title="Minumum Elevation In Meters" />
							</div>
							<div style="float:left;">
								<?php echo $LANG['ELEV_FT']; ?><br/>
								<input type="text" name="verbatimelevation" style="width:85px;" value="" onchange="convertElevFt(this.form)" title="Minumum Elevation In Feet" />
							</div>
							<div style="float:left;">
								<?php echo $LANG['GEO_REMARKS']; ?><br/>
								<input type="text" name="georeferenceremarks" maxlength="255" style="width:250px;" value="" />
							</div>
						</div>
						<div id="dmsdiv">
							<div>
								<?php echo $LANG['LATITUDE']; ?>:
								<input id="latdeg" style="width:35px;" title="<?php echo $LANG['LATITUDE_DEG']; ?>" />&deg;
								<input id="latmin" style="width:50px;" title="<?php echo $LANG['LATITUDE_MIN']; ?>" />'
								<input id="latsec" style="width:50px;" title="<?php echo $LANG['LATITUDE_SEC']; ?>" />&quot;
								<select id="latns">
									<option><?php echo $LANG['N']; ?></option>
									<option><?php echo $LANG['S']; ?></option>
								</select>
							</div>
							<div>
								<?php echo $LANG['LONGITUDE']; ?>:
								<input id="lngdeg" style="width:35px;" title="<?php echo $LANG['LONGITUDE_DEG']; ?>" />&deg;
								<input id="lngmin" style="width:50px;" title="<?php echo $LANG['LONGITUDE_MIN']; ?>" />'
								<input id="lngsec" style="width:50px;" title="<?php echo $LANG['LONGITUDE_SEC']; ?>" />&quot;
								<select id="lngew">
									<option><?php echo $LANG['E']; ?></option>
									<option SELECTED><?php echo $LANG['W']; ?></option>
								</select>
							</div>
							<div style="margin:5px;">
								<input type="button" value="Insert Lat/Long Values" onclick="insertLatLng(this.form)" />
							</div>
						</div>
					</fieldset>
					<fieldset>
						<legend><b><?php echo $LANG['MISC']; ?></b></legend>
						<div style="padding:3px;">
							<?php echo $LANG['HABITAT']; ?>:
							<input type="text" name="habitat" style="width:600px;" value="" />
						</div>
						<div style="padding:3px;">
							<?php echo $LANG['SUBSTRATE']; ?>:
							<input type="text" name="substrate" style="width:600px;" value="" />
						</div>
						<div style="padding:3px;">
							<?php echo $LANG['ASSOC_TAXA']; ?>:
							<input type="text" name="associatedtaxa" style="width:600px;background-color:" value="" />
						</div>
						<div style="padding:3px;">
							<?php echo $LANG['DESC_ORG']; ?>:
							<input type="text" name="verbatimattributes" style="width:600px;" value="" />
						</div>
						<div style="padding:3px;">
							<?php echo $LANG['GENERAL_NOTES']; ?>:
							<input type="text" name="occurrenceremarks" style="width:600px;" value="" title="Occurrence Remarks" />
						</div>
						<div style="padding:3px;">
							<span title="e.g. sterile, flw, frt, flw/frt ">
								<?php echo $LANG['REP_COND']; ?>:
								<input type="text" name="reproductivecondition" maxlength="255" style="width:140px;" value="" />
							</span>
							<span style="margin-left:30px;" title="e.g. planted, seeded, garden excape, etc">
								<?php echo $LANG['EST_MEANS']; ?>:
								<input type="text" name="establishmentmeans" maxlength="32" style="width:140px;" value="" />
							</span>
							<span style="margin-left:15px;" title="Click if specimen was cultivated or captive">
								<input type="checkbox" name="cultivationstatus" style="" value="" />
								<?php echo $LANG['CULT_CAPT']; ?>
							</span>
						</div>
					</fieldset>
					<?php
					if($clArr){
						?>
						<fieldset>
							<legend><b><?php echo $LANG['LINK_CHECK']; ?></b></legend>
							<?php echo $LANG['SP_LIST']; ?>:
							<select name='clid'>
								<option value="0"><?php echo $LANG['SEL_CHECKLIST']; ?></option>
								<option value="0">------------------------------</option>
								<?php
								foreach($clArr as $id => $clName){
									echo '<option value="'.$id.'" '.($id==$clid?'SELECTED':'').'>'.$clName.'</option>';
								}
								?>
							</select>
						</fieldset>
						<?php
					}
					?>
					<div style="margin:15px">
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<button type="submit" name="action" value="Submit Observation"><?php echo $LANG['SUBMIT_OBS']; ?></button>
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