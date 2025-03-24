<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDownload.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/specprocessor/exporter.' . $LANG_TAG . '.php')){
	include_once($SERVER_ROOT . '/content/lang/collections/specprocessor/exporter.' . $LANG_TAG . '.php');
}else{
	include_once($SERVER_ROOT . '/content/lang/collections/specprocessor/exporter.en.php');
} 
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/customsearchtype.' . $LANG_TAG . '.php')){
	include_once($SERVER_ROOT . '/content/lang/collections/customsearchtype.' . $LANG_TAG . '.php');
}
else{
	include_once($SERVER_ROOT . '/content/lang/collections/customsearchtype.en.php');
}
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$displayMode = array_key_exists('displaymode',$_REQUEST)?$_REQUEST['displaymode']:0;

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($displayMode)) $displayMode = 0;

$customField = array(); $customType = array(); $customValue = array();
for($h=1;$h<4;$h++){
	$customField[$h] = array_key_exists('customfield'.$h,$_REQUEST)?$_REQUEST['customfield'.$h]:'';
	$customType[$h] = array_key_exists('customtype'.$h,$_REQUEST)?$_REQUEST['customtype'.$h]:'';
	$customValue[$h] = array_key_exists('customvalue'.$h,$_REQUEST)?$_REQUEST['customvalue'.$h]:'';
}

$dlManager = new OccurrenceDownload();
$collMeta = $dlManager->getCollectionMetadata($collid);

$isEditor = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
 	$isEditor = true;
}

$advFieldArr = array('family'=>'Family','sciname'=>'Scientific Name','identifiedBy'=>'Identified By','typeStatus'=>'Type Status',
	'catalogNumber'=>'Catalog Number','otherCatalogNumbers'=>'Other Catalog Numbers','occurrenceId'=>'Occurrence ID (GUID)',
	'recordedBy'=>'Collector/Observer','recordNumber'=>'Collector Number','associatedCollectors'=>'Associated Collectors',
	'eventDate'=>'Collection Date','verbatimEventDate'=>'Verbatim Date','habitat'=>'Habitat','substrate'=>'Substrate','occurrenceRemarks'=>'Occurrence Remarks',
	'associatedTaxa'=>'Associated Taxa','verbatimAttributes'=>'Description','reproductiveCondition'=>'Reproductive Condition',
	'establishmentMeans'=>'Establishment Means','lifeStage'=>'Life Stage','sex'=>'Sex',
	'individualCount'=>'Individual Count','samplingProtocol'=>'Sampling Protocol','country'=>'Country',
	'stateProvince'=>'State/Province','county'=>'County','municipality'=>'Municipality','locality'=>'Locality',
	'decimalLatitude'=>'Decimal Latitude','decimalLongitude'=>'Decimal Longitude','geodeticDatum'=>'Geodetic Datum',
	'coordinateUncertaintyInMeters'=>'Uncertainty (m)','verbatimCoordinates'=>'Verbatim Coordinates',
	'georeferencedBy'=>'Georeferenced By','georeferenceProtocol'=>'Georeference Protocol','georeferenceSources'=>'Georeference Sources',
	'georeferenceVerificationStatus'=>'Georeference Verification Status','georeferenceRemarks'=>'Georeference Remarks',
	'minimumElevationInMeters'=>'Elevation Minimum (m)','maximumElevationInMeters'=>'Elevation Maximum (m)',
	'verbatimElevation'=>'Verbatim Elevation','disposition'=>'Disposition','processingStatus'=>'Processing Status','dbpk'=>'Source Identifier (dbpk)');
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $LANG['OCC_EXP_MAN']; ?></title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
		<script src="../../js/symb/shared.js" type="text/javascript"></script>
		<script>
			$(function() {
				var dialogArr = new Array("schemanative","schemadwc","newrecs");
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

			function validateDownloadForm(f){
				if(f.newrecs && f.newrecs.checked == true && (f.processingstatus.value == "unprocessed" || f.processingstatus.value == "")){
					alert("<?php echo $LANG['NEW_RECORDS_PROC_STATUS']; ?>");
					return false;
				}
				return true;
			}

			function extensionSelected(obj){
				if(obj.checked == true){
					obj.form.zip.checked = true;
				}
			}

			function zipChanged(cbObj){
				if(cbObj.checked == false){
					var f = cbObj.form;
					f.identifications.checked = false;
					f.images.checked = false;
					f.associations.checked = false;
					if(f.attributes) f.attributes.checked = false;
				}
			}
		</script>
	</head>
	<body>
		<!-- This is inner text! -->
		<div role="main" id="innertext" style="background-color:white;">
			<h1 class="page-heading screen-reader-only"><?= $LANG['OCC_EXP_MAN']; ?></h1>
			<div style="float:right;width:165px;margin-right:100px">
				<fieldset>
					<legend><b><?php echo $LANG['EXP_TYPE']; ?></b></legend>
					<form name="submenuForm" method="post" action="index.php">
						<select name="displaymode" onchange="this.form.submit()">
							<option value="0"><?php echo $LANG['CUSTOM_EXP']; ?></option>
							<option value="1" <?php echo ($displayMode==1?'selected':''); ?>><?php echo $LANG['GEO_EXP']; ?></option>
						</select>
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="tabindex" type="hidden" value="4" />
					</form>
				</fieldset>
			</div>
			<div style="padding:15px 0px;">
				<?php echo $LANG['EXPORT_EXPLAIN'];
				if($collMeta['manatype'] == 'Snapshot'){
					?>
					<a href="#" onclick="toggle('moreinfodiv');this.style.display = 'none';return false;" style="font-size:90%"><?php echo $LANG['MORE'].'...'; ?></a>
					<span id="moreinfodiv" style="display:none;">
						<?php echo $LANG['EXPORT_EXPLAIN_2']; ?>
					</span>
					<?php
				}
				?>
			</div>
			<?php
			if($collid && $isEditor){
				echo '<div style="clear:both;">';
				$filterOptions = array('EQUALS', 'NOT_EQUALS', 'STARTS_WITH', 'LESS_THAN', 'GREATER_THAN', 'LIKE', 'NOT_LIKE', 'IS_NULL', 'NOT_NULL');
				if($displayMode == 1){
					if($collMeta['manatype'] == 'Snapshot'){
						?>
						<form name="exportgeorefform" action="../download/downloadhandler.php" method="post" onsubmit="return validateExportGeorefForm(this);">
							<fieldset>
								<legend><b><?php echo $LANG['EXPORT_BATCH_GEO']; ?></b></legend>
								<div style="margin:15px;">
									<?php echo $LANG['EXPORT_BATCH_GEO_EXPLAIN_1'].' '.'
									<a href="../georef/batchgeoreftool.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank">' . htmlspecialchars($LANG['BATCH_GEO_TOOLS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> '.
									$LANG['EXPORT_BATCH_GEO_EXPLAIN_2']; ?>
								</div>
								<table>
									<tr>
										<td>
											<div style="margin:10px;">
												<b><?php echo $LANG['PROCESSING_STATUS']; ?>:</b>
											</div>
										</td>
										<td>
											<div style="margin:10px 0px;">
												<select name="processingstatus">
													<option value=""><?php echo $LANG['ALL_RECORDS']; ?></option>
													<?php
													$statusArr = $dlManager->getProcessingStatusList($collid);
													foreach($statusArr as $v){
														echo '<option value="'.$v.'">'.ucwords($v).'</option>';
													}
													?>
												</select>
											</div>
										</td>
									</tr>
									<tr>
										<td valign="top">
											<div style="margin:10px;">
												<b><?php echo $LANG['COMPRESSION']; ?>:</b>
											</div>
										</td>
										<td>
											<div style="margin:10px 0px;">
												<input type="checkbox" name="zip" value="1" checked /> <?php echo $LANG['ARCHIVE_DATA_PACK']; ?><br/>
											</div>
										</td>
									</tr>
									<tr>
										<td valign="top">
											<div style="margin:10px;">
												<b><?php echo $LANG['FILE_FORMAT']; ?>:</b>
											</div>
										</td>
										<td>
											<div style="margin:10px 0px;">
												<input type="radio" name="format" value="csv" CHECKED /> <?php echo $LANG['CSV']; ?><br/>
												<input type="radio" name="format" value="tab" /> <?php echo $LANG['TAB_DELIMITED']; ?><br/>
											</div>
										</td>
									</tr>
									<tr>
										<td valign="top">
											<div style="margin:10px;">
												<b><?php echo $LANG['CHAR_SET']; ?>:</b>
											</div>
										</td>
										<td>
											<div style="margin:10px 0px;">
												<?php
												//$cSet = strtolower($CHARSET);
												$cSet = 'iso-8859-1';
												?>
												<input type="radio" name="cset" value="iso-8859-1" <?php echo ($cSet=='iso-8859-1'?'checked':''); ?> /> ISO-8859-1 (western)<br/>
												<input type="radio" name="cset" value="utf-8" <?php echo ($cSet=='utf-8'?'checked':''); ?> /> UTF-8 (unicode)
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<div style="margin:10px;">
												<input name="customfield1" type="hidden" value="georeferenceSources" />
												<input name="customtype1" type="hidden" value="STARTS_WITH" />
												<input name="customvalue1" type="hidden" value="georef batch tool" />
												<input name="targetcollid" type="hidden" value="<?php echo $collid; ?>" />
												<input name="schema" type="hidden" value="georef" />
												<input name="extended" type="hidden" value="1" />
												<input name="overrideconditionlimit" type="hidden" value="1" />
												<input name="submitaction" type="submit" value="Download Records" />
											</div>
										</td>
									</tr>
								</table>
							</fieldset>
						</form>
						<?php
					}
					//Export for georeferencing (e.g. GeoLocate)
					?>
					<form name="expgeoform" action="../download/downloadhandler.php" method="post" onsubmit="return validateExpGeoForm(this);">
						<fieldset>
							<legend><b><?php echo $LANG['EXPORT_LACKING_GEO']; ?></b></legend>
							<div style="margin:15px;">
								<?php echo $LANG['EXPORT_LACKING_GEO_EXPLAIN']; ?>
							</div>
							<table>
								<tr>
									<td>
										<div style="margin:10px;">
											<b><?php echo $LANG['PROCESSING_STATUS']; ?>:</b>
										</div>
									</td>
									<td>
										<div style="margin:10px 0px;">
											<select name="processingstatus">
												<option value=""><?php echo $LANG['ALL_RECORDS']; ?></option>
												<?php
												$statusArr = $dlManager->getProcessingStatusList($collid);
												foreach($statusArr as $v){
													echo '<option value="'.$v.'">'.ucwords($v).'</option>';
												}
												?>
											</select>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<div style="margin:10px;">
											<b><?php echo $LANG['COORDINATES']; ?>:</b>
										</div>
									</td>
									<td>
										<div style="margin:10px 0px;">
											<input name="customtype2" type="radio" value="IS_NULL" checked /> <?php echo $LANG['ARE_EMPTY']; ?><br/>
											<input name="customtype2" type="radio" value="NOT_NULL" /> <?php echo $LANG['HAVE_VALUES']; ?>
											<input name="customfield2" type="hidden" value="decimallatitude" />
											<input name="customvalue2" type="hidden" value="" />
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<div style="margin:10px;">
											<b><?php echo $LANG['ADDITIONAL_FILTERS']; ?>:</b>
										</div>
									</td>
									<td>
										<div style="margin:10px 0px;">
											<select name="customfield1" style="width:200px">
												<option value=""><?php echo $LANG['SELECT_FIELD']; ?></option>
												<option value="">---------------------------------</option>
												<?php
												foreach($advFieldArr as $k => $v){
													echo '<option value="'.$k.'" '.($k==$customField[1]?'SELECTED':'').'>'.$v.'</option>';
												}
												?>
											</select>
											<select name="customtype1">
												<?php
												foreach($filterOptions as $filterCode){
													echo '<option ' . ($customType[1] == $filterCode ? 'SELECTED' : '') . ' value="'.$filterCode.'">'.$LANG[$filterCode].'</option>';
												}
												?>
											</select>
											<input name="customvalue1" type="text" value="<?php echo $customValue[1]; ?>" style="width:200px;" />
										</div>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<div style="margin:10px;">
											<input name="customfield3" type="hidden" value="locality" />
											<input name="customtype3" type="hidden" value="NOT_NULL" />
											<input name="customvalue3" type="hidden" value="" />
											<input name="format" type="hidden" value="csv" />
											<input name="cset" type="hidden" value="utf-8" />
											<input name="zip" type="hidden" value="1" />
											<input name="targetcollid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="schema" type="hidden" value="dwc" />
											<input name="extended" type="hidden" value="1" />
											<input name="overrideconditionlimit" type="hidden" value="1" />
											<button name="submitaction" type="submit" value="Download Records"><?php echo $LANG['DOWNLOAD_RECORDS']; ?></button>
										</div>
									</td>
								</tr>
							</table>
						</fieldset>
					</form>
					<?php
				}
				else{
					?>
					<form name="downloadform" action="../download/downloadhandler.php" method="post" onsubmit="return validateDownloadForm(this);">
						<fieldset>
							<legend><b><?php echo $LANG['DOWNLOAD_SPEC_RECORDS']; ?></b></legend>
							<table>
								<tr>
									<td>
										<div style="margin:10px;">
											<b><?php echo $LANG['PROCESSING_STATUS']; ?>:</b>
										</div>
									</td>
									<td>
										<div style="margin:10px 0px;">
											<select name="processingstatus">
												<option value=""><?php echo $LANG['ALL_RECORDS']; ?></option>
												<?php
												$statusArr = $dlManager->getProcessingStatusList($collid);
												foreach($statusArr as $v){
													echo '<option value="'.$v.'">'.ucwords($v).'</option>';
												}
												?>
											</select>
										</div>
									</td>
								</tr>
								<?php
								if($collMeta['manatype'] == 'Snapshot'){
									?>
									<tr>
										<td>
											<div style="margin:10px;">
												<b><?php echo $LANG['NEW_RECORDS_ONLY']; ?>:</b>
											</div>
										</td>
										<td>
											<div style="margin:10px 0px;">
												<input type="checkbox" name="newrecs" value="1" /> <?php echo $LANG['EG_IN_PORTAL']; ?>
												<a id="newrecsinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
													<img src="../../images/info.png" style="width:1.2em;" />
												</a>
												<div id="newrecsinfodialog">
													<?php echo $LANG['MORE_INFO_TEXT']; ?>
												</div>
											</div>
										</td>
									</tr>
									<?php
								}
								?>
								<tr>
									<td>
										<div style="margin:10px;">
											<b><?php echo $LANG['ADDITIONAL_FILTERS']; ?>:</b>
										</div>
									</td>
									<td>
										<?php
										for($i=1;$i<4;$i++){
											?>
											<div style="margin:10px 0px;">
												<select name="customfield<?php echo $i; ?>" style="width:200px">
													<option value=""><?php echo $LANG['SELECT_FIELD']; ?></option>
													<option value="">---------------------------------</option>
													<?php
													foreach($advFieldArr as $k => $v){
														echo '<option value="'.$k.'" '.($k==$customField[1]?'SELECTED':'').'>'.$v.'</option>';
													}
													?>
												</select>
												<select name="customtype<?php echo $i; ?>">
													<?php
													foreach($filterOptions as $filterCode){
														echo '<option ' . ($customType[$i] == $filterCode ? 'SELECTED' : '') . ' value="'.$filterCode.'">'.$LANG[$filterCode].'</option>';
													}
													?>
												</select>
												<input name="customvalue<?= $i; ?>" type="text" value="<?php echo $customValue[1]; ?>" style="width:200px;" />
											</div>
											<?php
										}
										?>
									</td>
								</tr>
								<?php
								$traitArr = $dlManager->getAttributeTraits($collid);
								if($traitArr){
									?>
									<tr>
										<td valign="top">
											<div style="margin:10px;">
												<b><?php echo $LANG['TRAIT_FILTER']; ?>:</b>
											</div>
										</td>
										<td>
											<div style="margin:10px;">
												<select name="traitid[]" multiple>
													<?php
														foreach($traitArr as $traitID => $tArr){
															echo '<option value="'.$traitID.'">'.$tArr['name'].' [ID:'.$traitID.']</option>';
														}
													?>
												</select>
											</div>
											<div style="margin:10px;">
												-- <?php echo $LANG['OR_SPEC_ATTRIBUTE']; ?> --
											</div>
											<div style="margin:10px;">
												<select name="stateid[]" multiple>
													<?php
													foreach($traitArr as $traitID => $tArr){
														$stateArr = $tArr['state'];
														foreach($stateArr as $stateID => $stateName){
															echo '<option value="'.$stateID.'">'.$tArr['name'].': '.$stateName.'</option>';
														}
													}
													?>
												</select>
											</div>
											<div style="">
												* <?php echo $LANG['HOLD_CTRL']; ?>
											</div>
										</td>
									</tr>
									<?php
								}
								?>
								<tr>
									<td valign="top">
										<div style="margin:10px;">
											<b><?php echo $LANG['STRUCTURE']; ?>:</b>
										</div>
									</td>
									<td>
										<div style="margin:10px 0px;">
											<input type="radio" name="schema" value="symbiota" CHECKED />
											<?php echo $LANG['SYMB_NATIVE']; ?>
											<a id="schemanativeinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
												<img src="../../images/info.png" style="width:1.2em;" />
											</a><br/>
											<div id="schemanativeinfodialog">
												<?php echo $LANG['SYMB_NATIVE_EXPLAIN']; ?>
											</div>
											<input type="radio" name="schema" value="dwc" />
											Darwin Core
											<a id="schemadwcinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFO']; ?>">
												<img src="../../images/info.png" style="width:1.2em;" />
											</a><br/>
											<div id="schemadwcinfodialog">
												<?php echo $LANG['DWC_EXPLAIN']; ?>
											</div>
											<!--  <input type="radio" name="schema" value="specify" /> Specify -->
										</div>
									</td>
								</tr>
								<tr>
									<td valign="top">
										<div style="margin:10px;">
											<b><?php echo $LANG['DATA_EXTENSIONS']; ?>:</b>
										</div>
									</td>
									<td>
										<div style="margin:10px 0px;">
											<input type="checkbox" name="identifications" value="1" onchange="extensionSelected(this)" checked /> <?php echo $LANG['INCLUDE_DET']; ?><br/>
											<input type="checkbox" name="images" value="1" onchange="extensionSelected(this)" checked /> <?php echo $LANG['INCLUDE_IMAGES']; ?><br/>
											<input type="checkbox" name="associations" value="1" onchange="extensionSelected(this)" checked /> <?php echo $LANG['INCLUDE_ASSOCIATIONS']; ?><br/>
											<?php
											if($traitArr) echo '<input type="checkbox" name="attributes" value="1" onchange="extensionSelected(this)" checked /> '.$LANG['INCLUDE_ATTRIBUTES'].'<br/>';
											?>
											*<?php echo $LANG['OUTPUT_COMPRESSED']; ?>
										</div>
									</td>
								</tr>
								<tr>
									<td valign="top">
										<div style="margin:10px;">
											<b><?php echo $LANG['COMPRESSION']; ?>:</b>
										</div>
									</td>
									<td>
										<div style="margin:10px 0px;">
											<input type="checkbox" name="zip" value="1" onchange="zipChanged(this)" checked /> <?php echo $LANG['ARCHIVE_DATA_PACK']; ?><br/>
										</div>
									</td>
								</tr>
								<tr>
									<td valign="top">
										<div style="margin:10px;">
											<b><?php echo $LANG['FILE_FORMAT']; ?>:</b>
										</div>
									</td>
									<td>
										<div style="margin:10px 0px;">
											<input type="radio" name="format" value="csv" CHECKED /> <?php echo $LANG['CSV']; ?><br/>
											<input type="radio" name="format" value="tab" /> <?php echo $LANG['TAB_DELIMITED']; ?><br/>
										</div>
									</td>
								</tr>
								<tr>
									<td valign="top">
										<div style="margin:10px;">
											<b><?php echo $LANG['CHAR_SET']; ?>:</b>
										</div>
									</td>
									<td>
										<div style="margin:10px 0px;">
											<?php
											//$cSet = strtolower($CHARSET);
											$cSet = 'iso-8859-1';
											?>
											<input type="radio" name="cset" value="iso-8859-1" <?php echo ($cSet=='iso-8859-1'?'checked':''); ?> /> ISO-8859-1 (western)<br/>
											<input type="radio" name="cset" value="utf-8" <?php echo ($cSet=='utf-8'?'checked':''); ?> /> UTF-8 (unicode)
										</div>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<div style="margin:10px;">
											<input name="targetcollid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="extended" type="hidden" value="1" />
											<input name="overrideconditionlimit" type="hidden" value="1" />
											<button name="submitaction" type="submit" value="Download Records"><?php echo $LANG['DOWNLOAD_RECORDS']; ?></button>
										</div>
									</td>
								</tr>
							</table>
						</fieldset>
					</form>
					<?php
				}
				echo '</div>';
			}
			else{
				echo '<div style="font-weight:bold;">'.$LANG['ACCESS_DENIED'].'</div>';
			}
			?>
		</div>
	</body>
</html>