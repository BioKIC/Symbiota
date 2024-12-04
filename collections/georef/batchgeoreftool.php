<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceGeorefTools.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/georef/batchgeoreftool.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/georef/batchgeoreftool.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/georef/batchgeoreftool.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../profile/index.php?refurl=../collections/georef/batchgeoreftool.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid',$_REQUEST) ? $_REQUEST['collid' ] : 0;
$submitAction = array_key_exists('submitaction',$_POST) ? htmlspecialchars($_POST['submitaction'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';

$qCountry = array_key_exists('qcountry',$_POST) ? htmlspecialchars($_POST['qcountry'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$qState = array_key_exists('qstate',$_POST) ? htmlspecialchars($_POST['qstate'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$qCounty = array_key_exists('qcounty',$_POST) ? htmlspecialchars($_POST['qcounty'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$qMunicipality = array_key_exists('qmunicipality',$_POST) ? htmlspecialchars($_POST['qmunicipality'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$qLocality = array_key_exists('qlocality',$_POST) ? htmlspecialchars($_POST['qlocality'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$qDisplayAll = array_key_exists('qdisplayall',$_POST) ? $_POST['qdisplayall' ] :0;
$qVStatus = array_key_exists('qvstatus',$_POST) ? htmlspecialchars($_POST['qvstatus'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$qSciname = array_key_exists('qsciname',$_POST) ? htmlspecialchars($_POST['qsciname'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$qProcessingStatus = array_key_exists('qprocessingstatus',$_POST) ? htmlspecialchars($_POST['qprocessingstatus'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';

//$latDeg = array_key_exists('latdeg',$_POST)? $_POST['latdeg'] : '';
//$latMin = array_key_exists('latmin',$_POST)? $_POST['latmin'] : '';
//$latSec = array_key_exists('latsec',$_POST)? $_POST['latsec'] : '';
//$decimalLatitude = array_key_exists('decimallatitude',$_POST)? $_POST['decimallatitude'] : '';
//$latNS = array_key_exists('latns',$_POST)? $_POST['latns'] : '';

//$lngDeg = array_key_exists('lngdeg',$_POST)? $_POST['lngdeg'] : '';
//$lngMin = array_key_exists('lngmin',$_POST)? $_POST['lngmin'] : '';
//$lngSec = array_key_exists('lngsec',$_POST)? $_POST['lngsec'] : '';
//$decimalLongitude = array_key_exists('decimallongitude',$_POST)? $_POST['decimallongitude'] : '';
//$lngEW = array_key_exists('lngew',$_POST)? $_POST['lngew'] : '';

//$coordinateUncertaintyInMeters = array_key_exists('coordinateuncertaintyinmeters',$_POST)? $_POST['coordinateuncertaintyinmeters'] : '';
//$geodeticDatum = array_key_exists('geodeticdatum',$_POST)? $_POST['geodeticdatum'] : '';
$georeferenceSources = array_key_exists('georeferencesources',$_POST) ? htmlspecialchars($_POST['georeferencesources'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$georeferenceProtocol = array_key_exists('georeferenceprotocol',$_POST) ? htmlspecialchars($_POST['georeferenceprotocol'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
//$georeferenceRemarks = array_key_exists('georeferenceremarks',$_POST) ? htmlspecialchars($_POST['georeferenceremarks'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
//$footprintWKT = array_key_exists('footprintwkt',$_POST) ? htmlspecialchars($_POST['footprintwkt'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$georeferenceVerificationStatus = array_key_exists('georeferenceverificationstatus',$_POST) ? htmlspecialchars($_POST['georeferenceverificationstatus'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
//$minimumElevationInMeters = array_key_exists('minimumelevationinmeters',$_POST) ? htmlspecialchars($_POST['minimumelevationinmeters'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
//$maximumElevationInMeters = array_key_exists('maximumelevationinmeters',$_POST) ? htmlspecialchars($_POST['maximumelevationinmeters'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
//$minimumElevationInFeet = array_key_exists('minimumelevationinfeet',$_POST) ? htmlspecialchars($_POST['minimumelevationinfeet'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
//$maximumElevationInFeet = array_key_exists('maximumelevationinfeet',$_POST) ? htmlspecialchars($_POST['maximumelevationinfeet'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';

if(is_array($collid)) $collid = implode(',',$collid);

//Sanitation
if(!preg_match('/^[,\d]+$/',$collid)) $collid = 0;
$submitAction = htmlspecialchars($submitAction, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$qCountry = htmlspecialchars($qCountry, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$qState = htmlspecialchars($qState, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$qCounty = htmlspecialchars($qCounty, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$qMunicipality = htmlspecialchars($qMunicipality, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$qLocality = htmlspecialchars($qLocality, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$qDisplayAll = htmlspecialchars($qDisplayAll, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$qVStatus = htmlspecialchars($qVStatus, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$qSciname = htmlspecialchars($qSciname, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$qProcessingStatus = htmlspecialchars($qProcessingStatus, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$georeferenceSources = htmlspecialchars($georeferenceSources, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$georeferenceProtocol = htmlspecialchars($georeferenceProtocol, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$georeferenceVerificationStatus = htmlspecialchars($georeferenceVerificationStatus, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);

if(!$georeferenceSources) $georeferenceSources = 'georef batch tool '.date('Y-m-d');
//if(!$georeferenceVerificationStatus) $georeferenceVerificationStatus = 'reviewed - high confidence';

$geoManager = new OccurrenceGeorefTools();
$activeCollArr = explode(',', $collid);
foreach($activeCollArr as $k => $id){
	if((!isset($USER_RIGHTS["CollAdmin"]) || !in_array($id,$USER_RIGHTS["CollAdmin"])) && (!isset($USER_RIGHTS["CollEditor"]) || !in_array($id,$USER_RIGHTS["CollEditor"]))){
		unset($activeCollArr[$k]);
	}
}
$geoManager->setCollId($IS_ADMIN?$collid:implode(',',$activeCollArr));
$collMap = $geoManager->getCollMap();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif($activeCollArr) $isEditor = true;

$statusStr = '';
if($isEditor && $submitAction){
	if($qCountry) $geoManager->setQueryVariables('qcountry',$qCountry);
	if($qState) $geoManager->setQueryVariables('qstate',$qState);
	if($qCounty) $geoManager->setQueryVariables('qcounty',$qCounty);
	if($qMunicipality) $geoManager->setQueryVariables('qmunicipality',$qMunicipality);
	if($qSciname) $geoManager->setQueryVariables('qsciname',$qSciname);
	if($qDisplayAll) $geoManager->setQueryVariables('qdisplayall',$qDisplayAll);
	if($qVStatus) $geoManager->setQueryVariables('qvstatus',$qVStatus);
	if($qLocality) $geoManager->setQueryVariables('qlocality',$qLocality);
	if($qProcessingStatus) $geoManager->setQueryVariables('qprocessingstatus',$qProcessingStatus);
	if($submitAction == 'Update Coordinates') $statusStr = $geoManager->updateCoordinates($_POST);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $DEFAULT_TITLE.' '.$LANG['GEOREF_TOOLS']; ?></title>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/collections.georef.js?ver=1" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/collections.georef.batchgeoreftool.js?ver=201912" type="text/javascript"></script>
	</head>
	<body>
		<a class="screen-reader-only" href="#queryform-section"><?php echo $LANG['SKIP_NAV'] ?></a>
		<!-- This is inner text! -->
		<div  id='innertext'>
			<h1 class="page-heading"><?php echo $LANG['BATCH_GEO_TOOLS']; ?></h1>
			<?php
			if($collid){
				?>
				<div id="breadcrumbs-section" style="float:left;">
					<div style="font-weight: bold; font-size:140%;float:left">
						<?php
						if(is_numeric($collid)) echo $collMap[$collid]['collectionname'].' ('.$collMap[$collid]['code'].')';
						else echo 'Multiple Collection Cleaning Tool (<a href="#" onclick="$(\'#collDiv\').show()" style="color:blue;text-decoration:underline">'.count($activeCollArr).' collections</a>)';
						?>
					</div>
					<?php
					if(count($collMap) > 1 && $activeCollArr){
						?>
						<div style="float:left;margin-left:5px;"><a href="#" onclick="toggle('mult_coll_div')" aria-label="<?php echo $LANG['MULT_COLL_DIV'] ?>" ><img src="../../images/add.png" alt="<?php echo $LANG['ADD_ICON'] ?>" style="width:1em" /></a></div>
						<?php
					}
					?>
					<div class='navpath' style="margin:10px;clear:both;">
						<a href='../../index.php'><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
						<?php
						if(is_numeric($collid)){
							?>
							<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars( $collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo htmlspecialchars($LANG['COL_MAN_MENU'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
							<?php
						}
						else{
							?>
							<a href="../../profile/viewprofile.php?tabindex=1"><?php echo htmlspecialchars($LANG['SPEC_MANAGEMENT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
							<?php
						}
						?>
						<b><?php echo $LANG['BATCH_GEO_TOOLS']; ?></b>
					</div>
					<?php
					if($statusStr){
						?>
						<div style='margin:20px;font-weight:bold;color:red;'>
							<?php echo $statusStr; ?>
						</div>
						<?php
					}
					if(count($collMap) > 1 && $activeCollArr){
						?>
						<div id="mult_coll_div" style="clear:both;display:none;">
							<fieldset style="padding: 15px;margin:20px;">
								<legend><b><?php echo $LANG['MULT_COL_SELECT']; ?></b></legend>
								<form name="selectcollidform" action="batchgeoreftool.php" method="post" onsubmit="return checkSelectCollidForm(this)">
									<div><input name="selectall" type="checkbox" onclick="selectAllCollections(this);" /> <?php echo $LANG['SEL_DESEL_ALL']; ?></div>
									<?php
									foreach($collMap as $id => $collArr){
										if(!empty($USER_RIGHTS['CollAdmin'][$id]) || !empty($USER_RIGHTS['CollEditor'][$id])){
											echo '<div>';
											echo '<input name="collid[]" type="checkbox" value="'.$id.'" '.(in_array($id,$activeCollArr)?'CHECKED':'').' /> ';
											echo $collArr['collectionname'].' ('.$collArr['code'].')';
											echo '</div>';
										}
									}
									?>
									<div style="margin: 15px">
										<button name="submitaction" type="submit" value="EvaluateCollections"><?php echo $LANG['EVAL_COLLS']; ?></button>
									</div>
								</form>
								<div>* <?php echo $LANG['ONLY_ADMIN_COLS']; ?></div>
							</fieldset>
						</div>
						<?php
					}
					if(count($activeCollArr) > 1){
						echo '<div id="collDiv" style="display:none;margin:0px 20px;clear:both;">';
						foreach($activeCollArr as $activeCollid){
							echo '<div>'.$collMap[$activeCollid]['collectionname'].' ('.$collMap[$activeCollid]['code'].')</div>';
						}
						echo '</div>';
					}
					?>
				</div>
				<?php
			}
			if($collid){
				if($isEditor){
					?>
					<div id="queryform-section" style="float:right;">
						<form name="queryform" method="post" action="batchgeoreftool.php" onsubmit="return verifyQueryForm(this)">
							<fieldset class="fieldset-like-box" style="width:600px">
								<legend><b><?php echo $LANG['QUERY_FORM']; ?></b></legend>
								<div style="height:20px;">
									<div style="clear:both;">
										<div style="float:left;margin-right:10px;">
											<select id="qcountry" name="qcountry" style="width:150px;" aria-label="<?php echo $LANG['ALL_COUNTRIES'] ?>">
												<option value=''><?php echo $LANG['ALL_COUNTRIES']; ?></option>
												<option value=''>--------------------</option>
												<?php
												$countryStr = array_key_exists('countrystr',$_POST)?strip_tags($_POST['countrystr']):'';
												$countryArr = array();
												if($countryStr) {
													 $countryArr = explode('|',$countryStr);
												} else {
													$countryArr = $geoManager->getCountryArr();
												}
												foreach($countryArr as $c){
													echo '<option '.($qCountry==$c?'SELECTED':'').'>'.$c.'</option>';
												}
												?>
											</select>
										</div>
										<div style="float:left;margin-right:10px;">
											<select id="qstate" name="qstate" style="width:150px;" aria-label="<?php echo $LANG['ALL_STATES'] ?>">
												<option value=''><?php echo $LANG['ALL_STATES']; ?></option>
												<option value=''>--------------------</option>
												<?php
												$stateStr = array_key_exists('statestr',$_POST)?strip_tags($_POST['statestr']):'';
												$stateArr = array();
												if($stateStr) {
													$stateArr = explode('|',$stateStr);
												} else {
													$stateArr = $geoManager->getStateArr();
												}
												foreach($stateArr as $s){
													echo '<option '.($qState==$s?'SELECTED':'').'>'.$s.'</option>';
												}
												?>
											</select>
										</div>
										<div style="float:left;margin-right:10px;">
											<select id="qcounty" name="qcounty" style="width:180px;" aria-label="<?php echo $LANG['ALL_COUNTIES'] ?>">
												<option value=''><?php echo $LANG['ALL_COUNTIES']; ?></option>
												<option value=''>--------------------</option>
												<?php
												$countyStr = array_key_exists('countystr',$_POST)?strip_tags($_POST['countystr']):'';
												$countyArr = array();
												if($countyStr) {
													$countyArr = explode('|',$countyStr);
												} else {
													$countyArr = $geoManager->getCountyArr();
												}
												foreach($countyArr as $c){
													echo '<option '.($qCounty==$c?'SELECTED':'').'>'.$c.'</option>';
												}
												?>
											</select>
										</div>
									</div>
									<div style="clear:both;margin-top:5px;">
										<div style="float:left;margin-right:10px;">
											<select id="qmunicipality" name="qmunicipality" style="width:180px;" aria-label="<?php echo $LANG['ALL_MUNS'] ?>">
												<option value=''><?php echo $LANG['ALL_MUNS']; ?></option>
												<option value=''>--------------------</option>
												<?php
												$municipalityStr = array_key_exists('municipalitystr',$_POST)?strip_tags($_POST['municipalitystr']):'';
												$municipalityArr = array();
												if($municipalityStr) { 
													$municipalityArr = explode('|',$municipalityStr); 
												} else {
													$municipalityArr = $geoManager->getMunicipalityArr();
												}
												foreach($municipalityArr as $m){
													echo '<option '.($qMunicipality==$m?'SELECTED':'').'>'.$m.'</option>';
												}
												?>
											</select>
										</div>
										<div style="float:left;margin-right:10px;">
											<select name="qprocessingstatus" aria-label="<?php echo $LANG['ALL_PROC_STAT'] ?>">
												<option value=""><?php echo $LANG['ALL_PROC_STAT']; ?></option>
												<option value="">-----------------------</option>
												<?php
												$processingStr = array_key_exists('processingstr',$_POST)?strip_tags($_POST['processingstr']):'';
												$processingArr = array();
												if($processingStr) {
													$processingArr = explode('|',$processingStr);
												} else {
													$processingArr = $geoManager->getProcessingStatus();
												}
												foreach($processingArr as $pStatus){
													echo '<option '.($qProcessingStatus==$pStatus?'SELECTED':'').'>'.$pStatus.'</option>';
												}
												?>
											</select>
										</div>
										<div style="float:left;">
											<img src="../../images/add.png" style="width:1.5em;" onclick="toggle('advfilterdiv')" alt="<?php echo $LANG['ADVANCED_OPT']; ?>" />
										</div>
									</div>
								</div>
								<div id="advfilterdiv" style="clear:both;margin-top:5px;display:<?php echo ($qSciname || $qVStatus || $qDisplayAll?'block':'none'); ?>;">
									<div style="float:left;margin-right:15px;">
										<b><?php echo $LANG['VERIF_STATUS']; ?>:</b>
										<input id="qvstatus" name="qvstatus" type="text" value="<?php echo $qVStatus; ?>" style="width:175px;" />
									</div>
									<div style="float:left;">
										<b><?php echo $LANG['FAMILY_GENUS']; ?>:</b>
										<input name="qsciname" type="text" value="<?php echo $qSciname; ?>" style="width:150px;" />
									</div>
									<div style="clear:both;margin-top:5px;">
										<input name="qdisplayall" type="checkbox" value="1" <?php echo ($qDisplayAll?'checked':''); ?> />
										<?php echo $LANG['INCLUDE_PREV_GEOREF']; ?>
									</div>
								</div>
								<div style="margin-top:5px;clear:both;">
									<div style="float:left">
										<b><?php echo $LANG['LOCALITY_TERM']; ?>:</b>
										<input name="qlocality" type="text" value="<?php echo $qLocality; ?>" style="width:250px;" aria-label="<?php echo $LANG['LOCALITY_TERM'] ?>" />
									</div>
									<div style="float:right;">
										<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
										<button name="submitaction" type="submit" value="Generate List" ><?php echo $LANG['GENERATE_LIST']; ?></button>
										<span id="qworkingspan" style="display:none;">
											<img src="../../images/workingcircle.gif" />
										</span>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<div style="clear:both;">
						<form name="georefform" method="post" action="batchgeoreftool.php" onsubmit="return verifyGeorefForm(this)">
							<div style="float:right;">
								<span>
									<a href="#" onclick="geoCloneTool();" title="<?php echo $LANG['SEARCH_CLONES']; ?>" aria-label="<?php echo $LANG['FIND_CLONES']; ?>" ><img src="../../images/list.png" alt="<?php echo $LANG['SEARCH_CLONES']; ?>" style="width:1.3em;" aria-label="<?php echo $LANG['FIND_CLONES']; ?>"/></a>
								</span>
								<span style="margin-left:10px;">
									<a href="#" onclick="geoLocateLocality();" title="<?php echo $LANG['GEOLOCATE_LOCALITY']; ?>" aria-label="<?php echo $LANG['LOCATE_GEO_AREA']; ?>" ><img src="../../images/geolocate.png" alt="<?php echo $LANG['GEOLOCATE_LOCALITY']; ?>" style="width:1.3em;" aria-label="<?php echo $LANG['LOCATE_GEO_AREA']; ?>"/></a>
								</span>
								<span style="margin-left:10px;">
									<a href="#" onclick="analyseLocalityStr();" title="<?php echo $LANG['ANALYZE_FOR_COORDS']; ?>" aria-label="<?php echo $LANG['EXAMINE_FOR_COORDS']; ?>" ><img src="../../images/find.png" alt="<?php echo $LANG['ANALYZE_FOR_COORDS']; ?>" style="width:1.3em;" aria-label="<?php echo $LANG['EXAMINE_FOR_COORDS']; ?>" /></a>
								</span>
								<?php
								if(!strpos($collid,',')){
									?>
									<span style="margin-left:10px;">
										<a href="#" onclick="openFirstRecSet();" aria-label="<?php echo $LANG['INITIAL_RECORDS_EDIT']; ?>" ><img src="../../images/edit.png" alt="<?php echo $LANG['EDIT_FIRST_SET']; ?>" style="width:1.3em;" /></a>
									</span>
									<?php
								}
								?>
							</div>
							<div>
								<?php
								$localArr = $geoManager->getLocalityArr();
								$localCnt = '---';
								if(isset($localArr)) $localCnt = count($localArr);
								if($localCnt == 1000) $localCnt = '1000 '.$LANG['LIMIT_REACHED'];
								echo '<b>'.$LANG['RETURN_COUNT'].':</b> '.$localCnt;
								?>
							</div>
							<div style="clear:both;border:2px solid;width:100%;height:200px;resize: both;overflow: auto">
								<select id="locallist" name="locallist[]" multiple="multiple" style="width:100%;height:100%" aria-label="<?php echo $LANG['LOCALLIST'] ?>">
									<?php
									if(isset($localArr)){
										if($localArr){
											foreach($localArr as $k => $v){
												$locStr = '';
												if(!$qCountry && $v['country']) $locStr = $v['country'].'; ';
												if(!$qState && $v['stateprovince']) $locStr .= $v['stateprovince'].'; ';
												if(!$qCounty && $v['county']) $locStr .= $v['county'].'; ';
												if(!$qMunicipality && $v['municipality']) $locStr .= $v['municipality'].'; ';
												if($v['locality']) $locStr .= str_replace(';',',',$v['locality']);
												if($v['verbatimcoordinates']) $locStr .= ', '.$v['verbatimcoordinates'];
												if(array_key_exists('decimallatitude',$v) && $v['decimallatitude']){
													$locStr .= ' ('.$v['decimallatitude'].', '.$v['decimallongitude'].') ';
												}
												echo '<option value="' . $v['occid'].'">'.trim($locStr ?? '',' ,') . ' [' . $v['cnt'] . ']</option>' . "\n";
											}
										}
										else{
											echo '<option value="">'.$LANG['NO_LOCALITIES_RETURNED'].'</option>';
										}
									}
									else{
										echo '<option value="">'.$LANG['USE_QUERY_FORM]'].'</option>';
									}
									?>
								</select>
							</div>
							<div style="float:right;">
								<fieldset>
									<legend><b><?php echo $LANG['STATISTICS']; ?></b></legend>
									<div style="">
										<?php echo $LANG['RECS_TO_GEOREF']; ?>
									</div>
									<div style="margin:5px;">
										<?php
										$statArr = $geoManager->getCoordStatistics();
										echo '<div>'.$LANG['TOTAL'].': '.$statArr['total'].'</div>';
										echo '<div>'.$LANG['PERCENT'].': '.$statArr['percent'].'%</div>';
										?>
									</div>
								</fieldset>
							</div>
							<div style="margin:15px;">
							    <span id="tableContent" style="display: none;"><?php echo $LANG['TABLE_CONTENT'] ?></span>
								<section class="gridlike-form" aria-describedby="tableContent">
								    <section class="gridlike-form-row bottom-breathing-room-rel">
										<div style="width:65px;"></div>
										<div style="width:30px;"><b><?php echo $LANG['DEG']; ?>.</b></div>
										<div style="width:50px;"><b><?php echo $LANG['MIN']; ?>.</b></div>
										<div style="width:55px;"><b><?php echo $LANG['SEC']; ?>.</b></div>
										<div style="width:20px;">&nbsp;</div>
										<div style="width:15px;">&nbsp;</div>
										<div><b><?php echo $LANG['DECIMAL']; ?></b></div>
									</section>
									<section class="gridlike-form-row bottom-breathing-room-rel">
										<div style="width:62px;"><b><?php echo $LANG['LATITUDE']; ?>:</b> </div>
										<div><input name="latdeg" type="text" value="" onchange="updateLatDec(this.form)" style="width:30px;" aria-label="<?php echo $LANG['LATITUDE_DEGREE'] ?>" /></div>
										<div><input name="latmin" type="text" value="" onchange="updateLatDec(this.form)" style="width:50px;" aria-label="<?php echo $LANG['LATITUDE_MINUTES'] ?>" /></div>
										<div><input name="latsec" type="text" value="" onchange="updateLatDec(this.form)" style="width:50px;" aria-label="<?php echo $LANG['LATITUDE_SECONDS'] ?>" /></div>
										<div>
											<select name="latns" aria-label="<?php echo $LANG['LATITUDE_NORTH_SOUTH'] ?>" onchange="updateLatDec(this.form)">
												<option><?php echo $LANG['N']; ?></option>
												<option ><?php echo $LANG['S']; ?></option>
											</select>
										</div>
										<div> = </div>
										<div>
											<input id="decimallatitude" name="decimallatitude" type="text" value="" style="width:80px;" aria-label="<?php echo $LANG['DECIMAL_LATITUDE'] ?>" />
											<span style="cursor:pointer;padding:3px;" onclick="openMappingAid();">
												<img src="../../images/world.png" alt="<?php echo $LANG['WORLD_ICON'] ?>" style="border:0px;width:1.2em;" aria-label="<?php echo $LANG['WORLD_ICON'] ?>" />
											</span>
										</div>
									</section>
									<section class="gridlike-form-row bottom-breathing-room-rel">
										<div style="vertical-align:middle"><b><?php echo $LANG['LONGITUDE']; ?>:</b> </div>
										<div><input name="lngdeg" type="text" value="" onchange="updateLngDec(this.form)" style="width:30px;" aria-label="<?php echo $LANG['LONGITUDE_DEGREE'] ?>" /></div>
										<div><input name="lngmin" type="text" value="" onchange="updateLngDec(this.form)" style="width:50px;" aria-label="<?php echo $LANG['LONGITUDE_MINUTES'] ?>" /></div>
										<div><input name="lngsec" type="text" value="" onchange="updateLngDec(this.form)" style="width:50px;" aria-label="<?php echo $LANG['LONGITUDE_SECONDS'] ?>" /></div>
										<div style="width:32px;">
											<select name="lngew" aria-label="<?php echo $LANG['LONGITUDE_EAST_WEST'] ?>" onchange="updateLngDec(this.form)">
												<option><?php echo $LANG['E']; ?></option>
												<option SELECTED><?php echo $LANG['W']; ?></option>
											</select>
										</div>
										<div> = </div>
										<div><input id="decimallongitude" name="decimallongitude" type="text" value="" style="width:80px;" aria-label="<?php echo $LANG['DECIMAL_LONGITUDE'] ?>" /></div>
									</section>
									<section class="gridlike-form-row bottom-breathing-room-rel">
										<div style="vertical-align:middle">
											<b><?php echo $LANG['ERROR_METERS']; ?>:</b>
										</div>
										<div style="vertical-align:middle">
											<input id="coordinateuncertaintyinmeters" name="coordinateuncertaintyinmeters" type="text" value="" style="width:50px;" aria-label="<?php echo $LANG['ERROR_METERS'] ?>" onchange="verifyCoordUncertainty(this)" />
										</div>
										<div style="vertical-align:middle">
											<span style="margin-left:20px;font-weight:bold;"><?php echo $LANG['DATUM']; ?>:</span>
											<input id="geodeticdatum" name="geodeticdatum" type="text" value="" style="width:75px;" aria-label="<?php echo $LANG['DATUM'] ?>" />
											<span style="cursor:pointer;margin-left:3px;" onclick="toggle('utmdiv');">
												<img src="../../images/editplus.png" alt="<?php echo $LANG['EDIT_PLUS_ICON']; ?>" style="border:0px;width:1.5em;" aria-label="<?php echo $LANG['EDIT_PLUS_ICON']; ?>" />
											</span>
										</div>
									</section>
									<section class="gridlike-form-row bottom-breathing-room-rel">
										<div style="vertical-align:middle">
											<b><?php echo $LANG['FOOTPRINT_WKT']; ?>:</b>
										</div>
										<div style="vertical-align:middle">
											<input id="footprintwkt" name="footprintwkt" type="text" value="" style="width:500px;" aria-label="<?php echo $LANG['FOOTPRINT_WKT'] ?>" onchange="verifyFootprintWKT(this)" />
										</div>
									</section>
									<section class="gridlike-form-row bottom-breathing-room-rel">
										<div>
											<div id="utmdiv" class="fieldset-like-box">
												<div>
													<div style="margin:3px;float:left;">
														<?php echo $LANG['EAST']; ?>: <input name="utmeast" type="text" style="width:100px;" aria-label="<?php echo $LANG['EAST'] ?>" />
													</div>
													<div style="margin:3px;float:left;">
														<?php echo $LANG['NORTH']; ?>: <input name="utmnorth" type="text" style="width:100px;" aria-label="<?php echo $LANG['NORTH'] ?>" />
													</div>
													<div style="margin:3px;float:left;">
														<?php echo $LANG['ZONE']; ?>: <input name="utmzone" style="width:40px;" aria-label="<?php echo $LANG['ZONE'] ?>" />
													</div>
												</div>
												<div>
													<div>
														<?php echo $LANG['HEMISPHERE']; ?>:
														<select name="hemisphere" title="Use hemisphere designator (e.g. 12N) rather than grid zone "aria-label="<?php echo $LANG['HEMISPHERE'] ?>">
															<option value="Northern"><?php echo $LANG['NORTH']; ?></option>
															<option value="Southern"><?php echo $LANG['SOUTH']; ?></option>
														</select>
													</div>
													<div style="padding-top:1em">
														<button value="Convert UTM values to lat/long " onclick="insertUtm(this.form)" ><?php echo $LANG['CONVERT_UTMS']; ?></button>
													</div>
												</div>
											</div>
										</div>
									</section>
									<section class="gridlike-form-row bottom-breathing-room-rel">
										<div style="vertical-align:middle">
											<b><?php echo $LANG['SOURCES']; ?>:</b>
										</div>
										<div>
											<input id="georeferencesources" name="georeferencesources" type="text" value="<?php echo $georeferenceSources; ?>" style="width:500px;" aria-label="<?php echo $LANG['SOURCES'] ?>" />
										</div>
									</section>
									<section class="gridlike-form-row bottom-breathing-room-rel">
										<div style="vertical-align:middle">
											<b><?php echo $LANG['PROTOCOLS']; ?>:</b>
										</div>
										<div>
											<input id="georeferenceprotocol" name="georeferenceprotocol" type="text" value="<?php echo $georeferenceProtocol; ?>" style="width:500px;" aria-label="<?php echo $LANG['PROTOCOLS'] ?>" />
										</div>
									</section>
									<section class="gridlike-form-row bottom-breathing-room-rel">
										<div style="vertical-align:middle">
											<b><?php echo $LANG['REMARKS']; ?>:</b>
										</div>
										<div>
											<input name="georeferenceremarks" type="text" value="" style="width:500px;" aria-label="<?php echo $LANG['REMARKS'] ?>" />
										</div>
									</section>
									<section class="gridlike-form-row bottom-breathing-room-rel">
										<div style="vertical-align:middle">
											<b><?php echo $LANG['VERIF_STATUS']; ?>:</b>
										</div>
										<div>
											<input id="georeferenceverificationstatus" name="georeferenceverificationstatus" type="text" value="<?php echo $georeferenceVerificationStatus; ?>" style="width:400px;" aria-label="<?php echo $LANG['VERIF_STATUS'] ?>" />
										</div>
									</section>
									<section class="gridlike-form-row bottom-breathing-room-rel">
										<div style="vertical-align:middle">
											<b><?php echo $LANG['ELEVATION']; ?>:</b>
										</div>
										<div>
											<input name="minimumelevationinmeters" type="text" value="" style="width:50px;" aria-label="<?php echo $LANG['MINIMUM_ELEVATION_IN_METERS'] ?>" /> <?php echo $LANG['TO']; ?>
											<input name="maximumelevationinmeters" type="text" value="" style="width:50px;" aria-label="<?php echo $LANG['MAXIMUM_ELEVATION_IN_METERS'] ?>" /> <?php echo $LANG['METERS']; ?>
											<span style="margin-left:80px;">
												<input type="text" value="" style="width:50px;" aria-label="<?php echo $LANG['MINIMUM_ELEVATION'] ?>" onchange="updateMinElev(this.value)" /> <?php echo $LANG['TO']; ?>
												<input type="text" value="" style="width:50px;" aria-label="<?php echo $LANG['MAXIMUM_ELEVATION'] ?>" onchange="updateMaxElev(this.value)" /> <?php echo $LANG['FEET']; ?>
											</span>
										</div>
									</section>
									<section class="gridlike-form-row bottom-breathing-room-rel">
										<div>
											<b><?php echo $LANG['PROCESSING_STATUS']; ?>: </b>
										</div>
										<div>
											<select name="processingstatus" aria-label="<?php echo $LANG['PROCESSING_STATUS'] ?>">
												<option value=""><?php echo $LANG['LEAVE_AS_IS']; ?></option>
												<option value="unprocessed"><?php echo $LANG['UNPROCESSED']; ?></option>
												<option value="unprocessed/NLP"><?php echo $LANG['UNPROCESSED_NLP']; ?></option>
												<option value="stage 1"><?php echo $LANG['STAGE_1']; ?></option>
												<option value="stage 2"><?php echo $LANG['STAGE_2']; ?></option>
												<option value="stage 3"><?php echo $LANG['STAGE_3']; ?></option>
												<option value="pending review-nfn"><?php echo $LANG['PENDING_NFN']; ?></option>
												<option value="pending review"><?php echo $LANG['PENDING_REVIEW']; ?></option>
												<option value="expert required"><?php echo $LANG['EXPERT_REQUIRED']; ?></option>
												<option value="reviewed"><?php echo $LANG['REVIEWED']; ?></option>
												<option value="closed"><?php echo $LANG['CLOSED']; ?></option>
											</select>
											<span style="margin-left:20px;font-size:80%">
												<?php echo $LANG['GEOREF_BY']; ?>:
												<input name="georeferencedby" type="text" value="<?php echo $USERNAME; ?>" style="width:75px" readonly aria-label="<?php echo $LANG['GEOREF_BY'] ?>" />
											</span>
										</div>
								    </section>
									<section class="gridlike-form-row bottom-breathing-room-rel">
										<div>
											<button name="submitaction" type="submit" value="Update Coordinates" aria-label="<?php echo $LANG['UPDATE_COORDS'] ?>" ><?php echo $LANG['UPDATE_COORDS']; ?></button>
											<span id="workingspan" style="display:none;">
												<img src="../../images/workingcircle.gif" />
											</span>
											<input name="qcountry" type="hidden" value="<?php echo $qCountry; ?>" />
											<input name="qstate" type="hidden" value="<?php echo $qState; ?>" />
											<input name="qcounty" type="hidden" value="<?php echo $qCounty; ?>" />
											<input name="qmunicipality" type="hidden" value="<?php echo $qMunicipality; ?>" />
											<input name="qlocality" type="hidden" value="<?php echo $qLocality; ?>" />
											<input name="qsciname" type="hidden" value="<?php echo $qSciname; ?>" />
											<input name="qvstatus" type="hidden" value="<?php echo $qVStatus; ?>" />
											<input name="qprocessingstatus" type="hidden" value="<?php echo $qProcessingStatus; ?>" />
											<input name="qdisplayall" type="hidden" value="<?php echo $qDisplayAll; ?>" />
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
										</div>
									</section>
							    </section>
								<div style="margin-top:15px"><?php echo $LANG['NOTE_EXISTING_GEOREFS']; ?> </div>
							</div>
							<div>
								<?php
								if(!$countryStr && $countryArr) $countryStr = implode('|',$countryArr);
								if(!$stateStr && $stateArr) $stateStr = implode('|',$stateArr);
								if(!$countyStr && $countyArr) $countyStr = implode('|',$countyArr);
								if(!$municipalityStr && $municipalityArr) $municipalityStr = implode('|',$municipalityArr);
								if(!$processingStr && $processingArr) $processingStr = implode('|',$processingArr);
								?>
								<input name="countrystr" type="hidden" value="<?php echo htmlentities($countryStr); ?>" />
								<input name="statestr" type="hidden" value="<?php echo htmlentities($stateStr); ?>" />
								<input name="countystr" type="hidden" value="<?php echo htmlentities($countyStr); ?>" />
								<input name="municipalitystr" type="hidden" value="<?php echo htmlentities($municipalityStr); ?>" />
								<input name="processingstr" type="hidden" value="<?php echo htmlentities($processingStr); ?>" />
							</div>
						</form>
					</div>
					<?php
				}
				else{
					?>
					<div style='font-weight:bold;font-size:120%;'>
						<?php echo $LANG['ERROR_NO_PERMISSIONS']; ?>
					</div>
					<?php
				}
			}
			elseif($collMap){
				?>
				<div style="margin:0px 0px 20px 20xp;font-weight:bold;font-size:120%;"><?php echo $LANG['BATCH_GEO_TOOL']; ?></div>
				<fieldset style="padding: 15px;margin:20px;">
					<legend><b><?php echo $LANG['COL_SELECTOR']; ?></b></legend>
					<form name="selectcollidform" action="batchgeoreftool.php" method="post" onsubmit="return checkSelectCollidForm(this)">
						<div><input name="selectall" type="checkbox" onclick="selectAllCollections(this);" /> <?php echo $LANG['SEL_DESEL_ALL']; ?></div>
						<?php
						foreach($collMap as $id => $collArr){
							echo '<div>';
							echo '<input name="collid[]" type="checkbox" value="'.$id.'" /> ';
							echo $collArr['collectionname'].' ('.$collArr['code'].')';
							echo '</div>';
						}
						?>
						<div style="margin: 15px">
							<button name="submitaction" type="submit" value="EvaluateCollections"><?php echo $LANG['EVAL_COLLS']; ?></button>
						</div>
					</form>
					<div>* <?php echo $LANG['ONLY_ADMIN_COLS']; ?></div>
				</fieldset>
				<?php
			}
			else{
				?>
				<div style='font-weight:bold;font-size:120%;'>
					<?php echo $LANG['ERROR_COL_ID_NULL']; ?>
				</div>
				<?php
			}
			?>
		</div>
	</body>
</html>
