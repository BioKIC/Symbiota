<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/occurdataentry.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

$occManager = new OccurrenceEditorManager();

$isEditor = 0;		//If not editor, edits will be submitted to omoccuredits table but not applied to omoccurrences

$collMap = Array();
$statusStr = '';
$isGenObs = 0;

if($SYMB_UID){
	//Set variables
	$occManager->setCollId($collid);
	$collMap = $occManager->getCollMap();

	if($collMap && $collMap['colltype']=='General Observations') $isGenObs = 1;

	if($IS_ADMIN || ($collid && array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
		$isEditor = 1;
	}
	else{
		if(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
			$isEditor = 2;
		}
	}
	if($isEditor){
		if($action == 'Add Record'){
			$statusStr = $occManager->addOccurrence($_POST);
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Occurrence Editor</title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<link href="../../css/occureditor.css?ver=<?= $CSS_VERSION ?>" type="text/css" rel="stylesheet" id="editorCssLink" />
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		var collId = "<?php echo $collid; ?>";
	</script>
	<script type="text/javascript" src="../../js/symb/collections.occureditormain.js"></script>
	<script type="text/javascript" src="../../js/symb/collections.occureditortools.js"></script>
	<script type="text/javascript" src="../../js/symb/collections.occureditorshare.js?ver=201803"></script>
</head>
<body>
	<!-- inner text -->
	<div role="main" id="innertext">
		<?php
		if($isEditor && $collid){
			?>
			<div id="titleDiv">
				<?php
				echo '<h1 class="page-heading">' . $collMap['collectionname'].' ('.$collMap['institutioncode'].($collMap['collectioncode']?':'.$collMap['collectioncode']:'').')</h1>';
				?>
			</div>
			<div class='navpath'>
				<a href="../../index.php" onclick="return verifyLeaveForm()">Home</a> &gt;&gt;
				<?php
				if(!$isGenObs || $isEditor){
					?>
					<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1" onclick="return verifyLeaveForm()">Collection Management</a> &gt;&gt;
					<?php
				}
				if($isGenObs){
					?>
					<a href="../../profile/viewprofile.php?tabindex=1" onclick="return verifyLeaveForm()">Personal Management</a> &gt;&gt;
					<?php
				}
				?>
			</div>
			<?php
			if($statusStr){
				?>
				<div id="statusdiv" style="margin:5px 0px 5px 15px;">
					<b>Action Status: </b>
					<span style="color:<?php echo (stripos($statusStr,'ERROR')!==false?'red':'green'); ?>;"><?php echo $statusStr; ?></span>
				</div>
				<?php
			}
			if($occArr || $goToMode == 1 || $goToMode == 2){		//$action == 'gotonew'
				?>
				<div id="occedittabs" style="clear:both;">
					<form id="entryform" action="occurdataentry.php" method="post" onsubmit="return verifyFullForm(this);">
						<fieldset>
							<legend><b>Collection Event</b></legend>
							<div id="recordedByDiv">
								<?php echo (defined('RECORDEDBYLABEL')?RECORDEDBYLABEL:'Collector'); ?>
								<a href="#" onclick="return dwcDoc('recordedBy')"><img class="docimg" src="../../images/qmark.png" /></a>
								<br/>
								<input type="text" name="recordedby" tabindex="6" maxlength="255" value="<?php echo array_key_exists('recordedby',$occArr)?$occArr['recordedby']:''; ?>" onchange="fieldChanged('recordedby');" />
							</div>
							<div id="associatedCollectorsDiv">
								<div class="flabel">
									<?php echo (defined('ASSOCIATEDCOLLECTORSLABEL')?ASSOCIATEDCOLLECTORSLABEL:'Associated Collectors'); ?>
									<a href="#" onclick="return dwcDoc('associatedCollectors')"><img class="docimg" src="../../images/qmark.png" /></a>
								</div>
								<input type="text" name="associatedcollectors" tabindex="14" maxlength="255" value="<?php echo array_key_exists('associatedcollectors',$occArr)?$occArr['associatedcollectors']:''; ?>" onchange="fieldChanged('associatedcollectors');" />
							</div>
							<div id="eventDateDiv" title="Earliest Date Collected">
								<?php echo (defined('EVENTDATELABEL')?EVENTDATELABEL:'Date'); ?>
								<a href="#" onclick="return dwcDoc('eventDate')"><img class="docimg" src="../../images/qmark.png" /></a>
								<br/>
								<input type="text" name="eventdate" tabindex="10" value="<?php echo array_key_exists('eventdate',$occArr)?$occArr['eventdate']:''; ?>" onchange="eventDateChanged(this);" />
							</div>
							<div id="verbatimEventDateDiv">
								<div class="flabel">
									<?php echo (defined('VERBATIMEVENTDATELABEL')?VERBATIMEVENTDATELABEL:'Verbatim Date'); ?>
									<a href="#" onclick="return dwcDoc('verbatimEventDate')"><img class="docimg" src="../../images/qmark.png" /></a>
								</div>
								<input type="text" name="verbatimeventdate" tabindex="19" maxlength="255" value="<?php echo array_key_exists('verbatimeventdate',$occArr)?$occArr['verbatimeventdate']:''; ?>" onchange="verbatimEventDateChanged(this)" />
							</div>
						</fieldset>
						<fieldset>
							<legend><b>Locality</b></legend>
							<div style="clear:both;">
								<div id="countryDiv">
									<?php echo (defined('COUNTRYLABEL')?COUNTRYLABEL:'Country'); ?>
									<br/>
									<input type="text" id="ffcountry" name="country" tabindex="40" value="<?php echo array_key_exists('country',$occArr)?$occArr['country']:''; ?>" />
								</div>
								<div id="stateProvinceDiv">
									<?php echo (defined('STATEPROVINCELABEL')?STATEPROVINCELABEL:'State/Province'); ?>
									<br/>
									<input type="text" id="ffstate" name="stateprovince" tabindex="42" value="<?php echo array_key_exists('stateprovince',$occArr)?$occArr['stateprovince']:''; ?>" />
								</div>
								<div id="countyDiv">
									<?php echo (defined('COUNTYLABEL')?COUNTYLABEL:'County'); ?>
									<br/>
									<input type="text" id="ffcounty" name="county" tabindex="44" value="<?php echo array_key_exists('county',$occArr)?$occArr['county']:''; ?>" />
								</div>
								<div id="municipalityDiv">
									<?php echo (defined('MUNICIPALITYLABEL')?MUNICIPALITYLABEL:'Municipality'); ?>
									<br/>
									<input type="text" id="ffmunicipality" name="municipality" tabindex="45" value="<?php echo array_key_exists('municipality',$occArr)?$occArr['municipality']:''; ?>" />
								</div>
							</div>
							<div id="localityDiv">
								<?php echo (defined('LOCALITYLABEL')?LOCALITYLABEL:'Locality'); ?>
								<br />
								<input type="text" name="locality" tabindex="46" value="<?php echo array_key_exists('locality',$occArr)?$occArr['locality']:''; ?>" />
							</div>
							<div id="localSecurityDiv">
								<?php $hasValue = array_key_exists("localitysecurity",$occArr)&&$occArr["localitysecurity"]?1:0; ?>
								<input type="checkbox" name="localitysecurity" tabindex="0" value="1" <?php echo $hasValue?"CHECKED":""; ?> onchange="toggleLocSecReason(this.form);" title="Hide Locality Data from General Public" />
								<?php echo (defined('LOCALITYSECURITYLABEL')?LOCALITYSECURITYLABEL:'Locality Security'); ?>
								<span id="locsecreason" style="margin-left:40px;display:<?php echo ($hasValue?'inline':'none') ?>">
									<?php $lsrValue = array_key_exists('localitysecurityreason',$occArr)?$occArr['localitysecurityreason']:''; ?>
									<?php echo (defined('LOCALITYSECURITYREASONLABEL')?LOCALITYSECURITYREASONLABEL:'Security Reason Override'); ?>:
									<input type="text" name="localitysecurityreason" tabindex="0" value="<?php echo $lsrValue; ?>" title="Leave blank for default rare, threatened, or sensitive status" />
								</span>
							</div>
							<div style="clear:both;">
								<div id="decimalLatitudeDiv">
									<?php echo (defined('DECIMALLATITUDELABEL')?DECIMALLATITUDELABEL:'Latitude'); ?>
									<br/>
									<?php
									$latValue = "";
									if(array_key_exists("decimallatitude",$occArr) && $occArr["decimallatitude"] != "") {
										$latValue = $occArr["decimallatitude"];
									}
									?>
									<input type="text" id="decimallatitude" name="decimallatitude" tabindex="50" maxlength="15" value="<?php echo $latValue; ?>" onchange="decimalLatitudeChanged(this.form)" />
								</div>
								<div id="decimalLongitudeDiv">
									<?php echo (defined('DECIMALLONGITUDELABEL')?DECIMALLONGITUDELABEL:'Longitude'); ?>
									<br/>
									<?php
									$longValue = "";
									if(array_key_exists("decimallongitude",$occArr) && $occArr["decimallongitude"] != "") {
										$longValue = $occArr["decimallongitude"];
									}
									?>
									<input type="text" id="decimallongitude" name="decimallongitude" tabindex="52" maxlength="15" value="<?php echo $longValue; ?>" onchange="decimalLongitudeChanged(this.form);" />
								</div>
								<div id="coordinateUncertaintyInMetersDiv">
									<?php echo (defined('COORDINATEUNCERTAINITYINMETERSLABEL')?COORDINATEUNCERTAINITYINMETERSLABEL:'Uncertainty'); ?>
									<a href="#" onclick="return dwcDoc('coordinateUncertaintyInMeters')"><img class="docimg" src="../../images/qmark.png" /></a>
									<br/>
									<input type="text" id="coordinateuncertaintyinmeters" name="coordinateuncertaintyinmeters" tabindex="54" maxlength="10" value="<?php echo array_key_exists('coordinateuncertaintyinmeters',$occArr)?$occArr['coordinateuncertaintyinmeters']:''; ?>" onchange="coordinateUncertaintyInMetersChanged(this.form);" title="Uncertainty in Meters" />
								</div>
								<div id="googleDiv" onclick="openMappingAid();" title="Google Maps">
									<img src="../../images/world.png" />
								</div>
								<div id="geoLocateDiv" title="GeoLocate locality">
									<a href="#" onclick="geoLocateLocality();"><img src="../../images/geolocate.png"/></a>
								</div>
								<div id="geoToolsDiv" title="Other Coordinate Formats" >
									<input type="button" value="Tools" onclick="toggleCoordDiv();" />
								</div>
								<div id="geodeticDatumDiv">
									<?php echo (defined('GEODETICDATUMLABEL') ? GEODETICDATUMLABEL : 'Datum'); ?>
									<a href="#" onclick="return dwcDoc('geodeticDatum')"><img class="docimg" src="../../images/qmark.png" /></a>
									<br/>
									<input type="text" id="geodeticdatum" name="geodeticdatum" tabindex="56" maxlength="255" value="<?php echo array_key_exists('geodeticdatum',$occArr)?$occArr['geodeticdatum']:''; ?>" />
								</div>
								<div id="verbatimCoordinatesDiv">
									<div style="float:left;margin:18px 2px 0px 2px" title="Recalculate Decimal Coordinates">
										<a href="#" onclick="parseVerbatimCoordinates(document.entryform,1);return false">&lt;&lt;</a>
									</div>
									<div style="float:left;">
										<?php echo (defined('VERBATIMCOORDINATES')?VERBATIMCOORDINATES:'Verbatim Coordinates'); ?>
										<br/>
										<input type="text" name="verbatimcoordinates" tabindex="57" maxlength="255" value="<?php echo array_key_exists('verbatimcoordinates',$occArr)?$occArr['verbatimcoordinates']:''; ?>" onchange="verbatimCoordinatesChanged(this.form);" title="" />
									</div>
								</div>
							</div>
							<div style="clear:both;">
								<div id="elevationDiv">
									<?php echo (defined('ELEVATIONINMETERSLABEL')?ELEVATIONINMETERSLABEL:'Elevation in Meters'); ?>
									<br/>
									<input type="text" name="minimumelevationinmeters" tabindex="58" maxlength="6" value="<?php echo array_key_exists('minimumelevationinmeters',$occArr)?$occArr['minimumelevationinmeters']:''; ?>" onchange="minimumElevationInMetersChanged(this.form);" title="Minumum Elevation In Meters" /> -
									<input type="text" name="maximumelevationinmeters" tabindex="60" maxlength="6" value="<?php echo array_key_exists('maximumelevationinmeters',$occArr)?$occArr['maximumelevationinmeters']:''; ?>" onchange="maximumElevationInMetersChanged(this.form);" title="Maximum Elevation In Meters" />
								</div>
								<div id="verbatimElevationDiv">
									<div style="float:left;margin:18px 2px 0px 2px" title="Recalculate Elevation in Meters">
										<a href="#" onclick="parseVerbatimElevation(document.entryform);return false">&lt;&lt;</a>
									</div>
									<div style="float:left;">
										<?php echo (defined('VERBATIMELEVATION')?VERBATIMELEVATION:'Verbatim Elevation'); ?>
										<br/>
										<input type="text" name="verbatimelevation" tabindex="62" maxlength="255" value="<?php echo array_key_exists('verbatimelevation',$occArr)?$occArr['verbatimelevation']:''; ?>" onchange="verbatimElevationChanged(this.form);" title="" />
									</div>
								</div>
								<div id="georefExtraToggleDiv" onclick="toggle('georefExtraDiv');">
									<img src="../../images/editplus.png" style="width:15px;" />
								</div>
							</div>
							<?php
							include_once('includes/geotools.php');
							$georefExtraDiv = 'display:';
							if(array_key_exists("georeferencedby",$occArr) && $occArr["georeferencedby"]){
								$georefExtraDiv .= "block";
							}
							elseif(array_key_exists("footprintwkt",$occArr) && $occArr["footprintwkt"]){
								$georefExtraDiv .= "block";
							}
							elseif(array_key_exists("georeferenceprotocol",$occArr) && $occArr["georeferenceprotocol"]){
								$georefExtraDiv .= "block";
							}
							elseif(array_key_exists("georeferencesources",$occArr) && $occArr["georeferencesources"]){
								$georefExtraDiv .= "block";
							}
							elseif(array_key_exists("georeferenceverificationstatus",$occArr) && $occArr["georeferenceverificationstatus"]){
								$georefExtraDiv .= "block";
							}
							elseif(array_key_exists("georeferenceremarks",$occArr) && $occArr["georeferenceremarks"]){
								$georefExtraDiv .= "block";
							}
							?>
							<div id="georefExtraDiv" style="<?php echo $georefExtraDiv; ?>;">
								<div style="clear:both;">
									<div id="georeferencedByDiv">
										<?php echo (defined('GEOREFERENCEDBY')?GEOREFERENCEDBY:'Georeferenced By'); ?>
										<br/>
										<input type="text" name="georeferencedby" tabindex="66" maxlength="255" value="<?php echo array_key_exists('georeferencedby',$occArr)?$occArr['georeferencedby']:''; ?>" />
									</div>
									<div id="georeferenceSourcesDiv">
										<?php echo (defined('GEOREFERENCESOURCESLABEL')?GEOREFERENCESOURCESLABEL:'Georeference Sources'); ?>
										<a href="#" onclick="return dwcDoc('georeferenceSources')"><img class="docimg" src="../../images/qmark.png" /></a>
										<br/>
										<input type="text" name="georeferencesources" tabindex="70" maxlength="255" value="<?php echo array_key_exists('georeferencesources',$occArr)?$occArr['georeferencesources']:''; ?>" />
									</div>
									<div id="georeferenceRemarksDiv">
										<?php echo (defined('GEOREFERENCEREMARKSLABEL')?GEOREFERENCEREMARKSLABEL:'Georeference Remarks'); ?>
										<br/>
										<input type="text" name="georeferenceremarks" tabindex="74" maxlength="255" value="<?php echo array_key_exists('georeferenceremarks',$occArr)?$occArr['georeferenceremarks']:''; ?>" />
									</div>
								</div>
								<div style="clear:both;">
									<div id="georeferenceProtocolDiv">
										<?php echo (defined('GEOREFERENCEPROTOCOLLABEL')?GEOREFERENCEPROTOCOLLABEL:'Georeference Protocol'); ?>
										<a href="#" onclick="return dwcDoc('georeferenceProtocol')"><img class="docimg" src="../../images/qmark.png" /></a>
										<br/>
										<input type="text" name="georeferenceprotocol" tabindex="76" maxlength="255" value="<?php echo array_key_exists('georeferenceprotocol',$occArr)?$occArr['georeferenceprotocol']:''; ?>" />
									</div>
									<div id="georeferenceVerificationStatusDiv">
										<?php echo (defined('GEOREFERENCEVERIFICATIONSTATUSLABEL')?GEOREFERENCEVERIFICATIONSTATUSLABEL:'Georef Verification Status'); ?>
										<a href="#" onclick="return dwcDoc('georeferenceVerificationStatus')"><img class="docimg" src="../../images/qmark.png" /></a>
										<br/>
										<input type="text" name="georeferenceverificationstatus" tabindex="78" maxlength="32" value="<?php echo array_key_exists('georeferenceverificationstatus',$occArr)?$occArr['georeferenceverificationstatus']:''; ?>" />
									</div>
									<div id="footprintWktDiv">
										<?php echo (defined('FOOTPRINTWKTLABEL')?FOOTPRINTWKTLABEL:'footprint (polygon)'); ?>
										<br/>
										<textarea name="footprintwkt" ><?php echo array_key_exists('footprintwkt',$occArr)?$occArr['footprintwkt']:''; ?></textarea>
									</div>
								</div>
							</div>
							<div style="clear:both;">
								<div id="habitatDiv">
									<?php echo (defined('HABITATLABEL')?HABITATLABEL:'Habitat'); ?><br/>
									<input type="text" name="habitat" tabindex="80" value="<?php echo array_key_exists('habitat',$occArr)?$occArr['habitat']:''; ?>" />
								</div>
								<div id="associatedTaxaDiv">
									<?php echo (defined('ASSOCIATEDTAXALABEL')?ASSOCIATEDTAXALABEL:'Associated Taxa'); ?><br/>
									<textarea name="associatedtaxa" tabindex="84"><?php echo array_key_exists('associatedtaxa',$occArr)?$occArr['associatedtaxa']:''; ?></textarea>
									<?php
									if(!isset($ACTIVATEASSOCTAXAAID) || $ACTIVATEASSOCTAXAAID){
										echo '<a href="#" onclick="openAssocSppAid();return false;"><img src="../../images/list.png" /></a>';
									}
									?>
								</div>
							</div>
							<div style="padding:10px;">
								<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
								<input type="hidden" name="userid" value="<?php echo $PARAMS_ARR['un']; ?>" />
								<input type="hidden" name="observeruid" value="<?php echo $SYMB_UID; ?>" />
							</div>
						</fieldset>
						<fieldset>
							<legend><b>Collection Unit</b></legend>
							<div style="clear:both;">
								<div id="catalogNumberDiv">
									<?php echo (defined('CATALOGNUMBERLABEL')?CATALOGNUMBERLABEL:'Catalog Number'); ?>
									<a href="#" onclick="return dwcDoc('catalogNumber')"><img class="docimg" src="../../images/qmark.png" /></a>
									<br/>
									<input type="text" id="catalognumber" name="catalognumber" tabindex="2" maxlength="32" value="<?php echo array_key_exists('catalognumber',$occArr)?$occArr['catalognumber']:''; ?>" onchange="fieldChanged('catalognumber');<?php if(!defined('CATNUMDUPECHECK') || CATNUMDUPECHECK) echo 'searchCatalogNumber(this.form,true)'; ?>" <?php if(!$isEditor || $isEditor == 3) echo 'disabled'; ?> />
								</div>
								<div id="otherCatalogNumbersDiv">
									<?php echo (defined('OTHERCATALOGNUMBERSLABEL')?OTHERCATALOGNUMBERSLABEL:'Other Numbers'); ?>
									<a href="#" onclick="return dwcDoc('otherCatalogNumbers')"><img class="docimg" src="../../images/qmark.png" /></a>
									<br/>
									<input type="text" name="othercatalognumbers" tabindex="4" maxlength="255" value="<?php echo array_key_exists('othercatalognumbers',$occArr)?$occArr['othercatalognumbers']:''; ?>" onchange="fieldChanged('othercatalognumbers');<?php if(defined('OTHERCATNUMDUPECHECK') && OTHERCATNUMDUPECHECK) echo 'searchDupesOtherCatalogNumbers(this.form)'; ?>" />
								</div>
								<div id="recordNumberDiv">
									<?php echo (defined('RECORDNUMBERLABEL')?RECORDNUMBERLABEL:'Number'); ?>
									<a href="#" onclick="return dwcDoc('recordNumber')"><img class="docimg" src="../../images/qmark.png" /></a>
									<br/>
									<input type="text" name="recordnumber" tabindex="8" maxlength="45" value="<?php echo array_key_exists('recordnumber',$occArr)?$occArr['recordnumber']:''; ?>" onchange="recordNumberChanged(this);" />
								</div>
							</div>
							<div style="clear:both;">
								<div id="scinameDiv">
									<?php echo (defined('SCIENTIFICNAMELABEL')?SCIENTIFICNAMELABEL:'Scientific Name'); ?>
									<a href="#" onclick="return dwcDoc('scientificName')"><img class="docimg" src="../../images/qmark.png" /></a>
									<br/>
									<input type="text" id="ffsciname" name="sciname" maxlength="250" tabindex="28" value="<?php echo array_key_exists('sciname',$occArr)?$occArr['sciname']:''; ?>" onchange="fieldChanged('sciname');" <?php if((!$isEditor || $isEditor == 3) && $occArr['sciname']) echo 'disabled '; ?> />
									<input type="hidden" id="tidinterpreted" name="tidinterpreted" value="" />
									<?php
									if(!$isEditor && isset($occArr['sciname']) && $occArr['sciname'] != ''){
										echo '<div style="clear:both;color:red;margin-left:5px;">Note: Full editing permissions are needed to edit an identification</div>';
									}
									elseif($isEditor == 3){
										echo '<div style="clear:both;color:red;margin-left:5px;">Limited editing right: use determination tab to edit identification</div>';
									}
									?>
								</div>
								<div id="scientificNameAuthorshipDiv">
									<?php echo (defined('SCIENTIFICNAMEAUTHORSHIPLABEL')?SCIENTIFICNAMEAUTHORSHIPLABEL:'Author'); ?>
									<a href="#" onclick="return dwcDoc('scientificNameAuthorship')"><img class="docimg" src="../../images/qmark.png" /></a>
									<br/>
									<input type="text" name="scientificnameauthorship" maxlength="100" tabindex="0" value="<?php echo array_key_exists('scientificnameauthorship',$occArr)?$occArr['scientificnameauthorship']:''; ?>" onchange="fieldChanged('scientificnameauthorship');" <?php if(!$isEditor || $isEditor == 3) echo 'disabled'; ?> />
								</div>
							</div>
							<div style="clear:both;padding:3px 0px 0px 10px;">
								<div id="identificationQualifierDiv">
									<?php echo (defined('IDENTIFICATIONQUALIFIERLABEL')?IDENTIFICATIONQUALIFIERLABEL:'ID Qualifier'); ?>
									<a href="#" onclick="return dwcDoc('identificationQualifier')"><img class="docimg" src="../../images/qmark.png" /></a>
									<input type="text" name="identificationqualifier" tabindex="30" size="25" value="<?php echo array_key_exists('identificationqualifier',$occArr)?$occArr['identificationqualifier']:''; ?>" onchange="fieldChanged('identificationqualifier');" <?php if(!$isEditor || $isEditor == 3) echo 'disabled'; ?> />
								</div>
								<div  id="familyDiv">
									<?php echo (defined('FAMILYLABEL')?FAMILYLABEL:'Family'); ?>
									<a href="#" onclick="return dwcDoc('family')"><img class="docimg" src="../../images/qmark.png" /></a>
									<input type="text" name="family" maxlength="50" tabindex="0" value="<?php echo array_key_exists('family',$occArr)?$occArr['family']:''; ?>" onchange="fieldChanged('family');" />
								</div>
							</div>
							<div style="clear:both;padding:3px 0px 0px 10px;">
								<div id="identifiedByDiv">
									<?php echo (defined('IDENTIFIEDBYLABEL')?IDENTIFIEDBYLABEL:'Identified By'); ?>
									<a href="#" onclick="return dwcDoc('identifiedBy')"><img class="docimg" src="../../images/qmark.png" /></a>
									<input type="text" name="identifiedby" maxlength="255" tabindex="32" value="<?php echo array_key_exists('identifiedby',$occArr)?$occArr['identifiedby']:''; ?>" onchange="fieldChanged('identifiedby');" />
								</div>
								<div id="dateIdentifiedDiv">
									<?php echo (defined('DATEIDENTIFIEDLABEL')?DATEIDENTIFIEDLABEL:'Date Identified'); ?>
									<a href="#" onclick="return dwcDoc('dateIdentified')"><img class="docimg" src="../../images/qmark.png" /></a>
									<input type="text" name="dateidentified" maxlength="45" tabindex="34" value="<?php echo array_key_exists('dateidentified',$occArr)?$occArr['dateidentified']:''; ?>" onchange="fieldChanged('dateidentified');" />
								</div>
								<div id="idrefToggleDiv" onclick="toggle('idrefdiv');">
									<img src="../../images/editplus.png" style="width:15px;" />
								</div>
							</div>
							<div  id="idrefdiv">
								<div id="identificationReferencesDiv">
									<?php echo (defined('IDENTIFICATIONREFERENCELABEL')?IDENTIFICATIONREFERENCELABEL:'ID References'); ?>:
									<a href="#" onclick="return dwcDoc('identificationReferences')"><img class="docimg" src="../../images/qmark.png" /></a>
									<input type="text" name="identificationreferences" tabindex="36" value="<?php echo array_key_exists('identificationreferences',$occArr)?$occArr['identificationreferences']:''; ?>" onchange="fieldChanged('identificationreferences');" />
								</div>
								<div id="identificationRemarksDiv">
									<?php echo (defined('IDENTIFICATIONREMARKSLABEL')?IDENTIFICATIONREMARKSLABEL:'ID Remarks'); ?>:
									<a href="#" onclick="return dwcDoc('identificationRemarks')"><img class="docimg" src="../../images/qmark.png" /></a>
									<input type="text" name="identificationremarks" tabindex="38" value="<?php echo array_key_exists('identificationremarks',$occArr)?$occArr['identificationremarks']:''; ?>" onchange="fieldChanged('identificationremarks');" />
								</div>
								<div id="taxonRemarksDiv">
									<?php echo (defined('TAXONREMARKSLABEL')?TAXONREMARKSLABEL:'Taxon Remarks'); ?>:
									<a href="#" onclick="return dwcDoc('taxonRemarks')"><img class="docimg" src="../../images/qmark.png" /></a>
									<input type="text" name="taxonremarks" tabindex="39" value="<?php echo array_key_exists('taxonremarks',$occArr)?$occArr['taxonremarks']:''; ?>" onchange="fieldChanged('taxonremarks');" />
								</div>
							</div>
							<div>
								<div id="substrateDiv">
									<?php echo (defined('SUBSTRATELABEL')?SUBSTRATELABEL:'Substrate'); ?><br/>
									<input type="text" name="substrate" tabindex="82" maxlength="500" value="<?php echo array_key_exists('substrate',$occArr)?$occArr['substrate']:''; ?>" />
								</div>
								<div id="verbatimAttributesDiv">
									<?php echo (defined('VERBATIMATTRIBUTESLABEL')?VERBATIMATTRIBUTESLABEL:'Description'); ?><br/>
									<input type="text" name="verbatimattributes" tabindex="86" value="<?php echo array_key_exists('verbatimattributes',$occArr)?$occArr['verbatimattributes']:''; ?>" />
								</div>
								<div id="occurrenceRemarksDiv">
									<?php echo (defined('OCCURRENCEREMARKSLABEL')?OCCURRENCEREMARKSLABEL:'Notes'); ?><br/>
									<input type="text" name="occurrenceremarks" tabindex="88" value="<?php echo array_key_exists('occurrenceremarks',$occArr)?$occArr['occurrenceremarks']:''; ?>" title="Occurrence Remarks" />
									<span id="dynPropToggleSpan" onclick="toggle('dynamicPropertiesDiv');">
										<img src="../../images/editplus.png" />
									</span>
								</div>
								<div id="dynamicPropertiesDiv">
									<?php echo (defined('DYNAMICPROPERTIESLABEL')?DYNAMICPROPERTIESLABEL:'Dynamic Properties'); ?>
									<a href="#" onclick="return dwcDoc('dynamicProperties')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
									<input type="text" name="dynamicproperties" tabindex="89" value="<?php echo array_key_exists('dynamicproperties',$occArr)?$occArr['dynamicproperties']:''; ?>" />
								</div>
							</div>
							<div style="padding:2px;">
								<div id="lifeStageDiv">
									<?php echo (defined('LIFESTAGELABEL')?LIFESTAGELABEL:'Life Stage'); ?>
									<a href="#" onclick="return dwcDoc('lifeStage')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
									<input type="text" name="lifestage" tabindex="90" maxlength="45" value="<?php echo array_key_exists('lifestage',$occArr)?$occArr['lifestage']:''; ?>" />
								</div>
								<div id="sexDiv">
									<?php echo (defined('SEXLABEL')?SEXLABEL:'Sex'); ?>
									<a href="#" onclick="return dwcDoc('sex')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
									<input type="text" name="sex" tabindex="92" maxlength="45" value="<?php echo array_key_exists('sex',$occArr)?$occArr['sex']:''; ?>" />
								</div>
								<div id="individualCountDiv">
									<?php echo (defined('INDIVIDUALCOUNTLABEL')?INDIVIDUALCOUNTLABEL:'Individual Count'); ?>
									<a href="#" onclick="return dwcDoc('individualCount')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
									<input type="text" name="individualcount" tabindex="94" maxlength="45" value="<?php echo array_key_exists('individualcount',$occArr)?$occArr['individualcount']:''; ?>" />
								</div>
								<div id="samplingProtocolDiv">
									<?php echo (defined('SAMPLINGPROTOCOLLABEL')?SAMPLINGPROTOCOLLABEL:'Sampling Protocol'); ?>
									<a href="#" onclick="return dwcDoc('samplingProtocol')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
									<input type="text" name="samplingprotocol" tabindex="95" maxlength="100" value="<?php echo array_key_exists('samplingprotocol',$occArr)?$occArr['samplingprotocol']:''; ?>" />
								</div>
								<div id="preparationsDiv">
									<?php echo (defined('PREPARATIONSLABEL')?PREPARATIONSLABEL:'Preparations'); ?>
									<a href="#" onclick="return dwcDoc('preparations')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
									<input type="text" name="preparations" tabindex="97" maxlength="100" value="<?php echo array_key_exists('preparations',$occArr)?$occArr['preparations']:''; ?>" />
								</div>
								<div id="reproductiveConditionDiv">
									<?php echo (defined('REPRODUCTIVECONDITIONLABEL')?REPRODUCTIVECONDITIONLABEL:'Phenology'); ?>
									<a href="#" onclick="return dwcDoc('reproductiveCondition')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
									<?php
									if(isset($REPRODUCTIVE_CONDITION_TERMS)){
										if($REPRODUCTIVE_CONDITION_TERMS){
											?>
											<select name="reproductivecondition" tabindex="99" >
												<option value="">-----------------</option>
												<?php
												foreach($REPRODUCTIVE_CONDITION_TERMS as $term){
													echo '<option value="'.$term.'" '.(isset($occArr['reproductivecondition']) && $term==$occArr['reproductivecondition']?'SELECTED':'').'>'.$term.'</option>';
												}
												?>
											</select>
											<?php
										}
									}
									else{
									?>
										<input type="text" name="reproductivecondition" tabindex="99" maxlength="255" value="<?php echo array_key_exists('reproductivecondition',$occArr)?$occArr['reproductivecondition']:''; ?>" />
									<?php
									}
									?>

								</div>
								<div id="establishmentMeansDiv">
									<?php echo (defined('ESTABLISHMENTMEANSLABEL')?ESTABLISHMENTMEANSLABEL:'Establishment Means'); ?>
									<a href="#" onclick="return dwcDoc('establishmentMeans')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
									<input type="text" name="establishmentmeans" tabindex="100" maxlength="32" value="<?php echo array_key_exists('establishmentmeans',$occArr)?$occArr['establishmentmeans']:''; ?>" />
								</div>
								<div id="cultivationStatusDiv">
									<?php $hasValue = array_key_exists("cultivationstatus",$occArr)&&$occArr["cultivationstatus"]?1:0; ?>
									<input type="checkbox" name="cultivationstatus" tabindex="102" value="1" <?php echo $hasValue?'CHECKED':''; ?> />
									<?php echo (defined('CULTIVATIONSTATUSLABEL')?CULTIVATIONSTATUSLABEL:'Cultivated/Captive'); ?>
								</div>
							</div>
							<div style="padding:3px;">
								<div id="dispositionDiv">
									<?php echo (defined('DISPOSITIONLABEL')?DISPOSITIONLABEL:'Disposition'); ?>
									<a href="#" onclick="return dwcDoc('disposition')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
									<input type="text" name="disposition" tabindex="104" maxlength="32" value="<?php echo array_key_exists('disposition',$occArr)?$occArr['disposition']:''; ?>" />
								</div>
								<div id="occurrenceIdDiv" title="If different than institution code">
									<?php echo (defined('OCCURRENCEIDLABEL')?OCCURRENCEIDLABEL:'Occurrence ID'); ?>
									<a href="#" onclick="return dwcDoc('occurrenceid')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
									<input type="text" name="occurrenceid" tabindex="105" maxlength="255" value="<?php echo array_key_exists('occurrenceid',$occArr)?$occArr['occurrenceid']:''; ?>" />
								</div>
								<div id="fieldNumberDiv" title="If different than institution code">
									<?php echo (defined('FIELDNUMBERLABEL')?FIELDNUMBERLABEL:'Field Number'); ?>
									<a href="#" onclick="return dwcDoc('fieldnumber')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
									<input type="text" name="fieldnumber" tabindex="107" maxlength="45" value="<?php echo array_key_exists('fieldnumber',$occArr)?$occArr['fieldnumber']:''; ?>" />
								</div>
								<div id="basisOfRecordDiv">
									<?php echo (defined('BASISOFRECORDLABEL')?BASISOFRECORDLABEL:'Basis of Record'); ?>
									<a href="#" onclick="return dwcDoc('basisOfRecord')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
									<input type="text" name="basisofrecord" tabindex="109" maxlength="32" value="<?php echo array_key_exists('basisofrecord',$occArr)?$occArr['basisofrecord']:($collMap['colltype']=='Preserved Specimens'?'PreservedSpecimen':'Observation'); ?>" />
								</div>
								<div id="languageDiv">
									<?php echo (defined('LANGUAGELABEL')?LANGUAGELABEL:'Language'); ?><br/>
									<input type="text" name="language" tabindex="111" maxlength="20" value="<?php echo array_key_exists('language',$occArr)?$occArr['language']:''; ?>" />
								</div>
								<div id="labelProjectDiv">
									<?php echo (defined('LABELPROJECTLABEL')?LABELPROJECTLABEL:'Label Project'); ?><br/>
									<input type="text" name="labelproject" tabindex="112" maxlength="45" value="<?php echo array_key_exists('labelproject',$occArr)?$occArr['labelproject']:''; ?>" />
								</div>
								<div id="duplicateQuantityDiv" title="aka label quantity">
									<?php echo (defined('DUPLICATEQUALITYCOUNTLABEL')?DUPLICATEQUALITYCOUNTLABEL:'Dupe Count'); ?><br/>
									<input type="text" name="duplicatequantity" tabindex="116" value="<?php echo array_key_exists('duplicatequantity',$occArr)?$occArr['duplicatequantity']:''; ?>" />
								</div>
							</div>
							<div id="pkDiv">
								<hr/>
								<div style="float:left;margin-left:90px;">
									<?php if(array_key_exists('recordenteredby',$occArr)) echo 'Entered By: '.$occArr['recordenteredby']; ?>
								</div>
							</div>
						</fieldset>
						<div style="padding:10px;">
							<input type="submit" name="submitaction" value="Add Record" />
						</div>
					</form>
				</div>
				<?php
			}
		}
		else{
			echo '<h2>You are not authorized to add occurrence records</h2>';
		}
		?>
	</div>
</body>
</html>