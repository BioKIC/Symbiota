<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceManager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceAttributeSearch.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collManager = new OccurrenceManager();
$paleoTimes = $collManager->getPaleoTimes();
$searchVar = $collManager->getQueryTermStr();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['PAGE_TITLE']; ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
    include_once($SERVER_ROOT.'/includes/googleanalytics.php');
    ?>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../js/symb/collections.harvestparams.js?ver=3" type="text/javascript"></script>
	<script src="<?= $CLIENT_ROOT ?>/js/symb/mapAidUtils.js" type="text/javascript"></script>
	<script src="../js/symb/collections.traitsearch.js?ver=8" type="text/javascript"></script> <!-- Contains search-by-trait modifications -->
	<script src="../js/symb/wktpolygontools.js?ver=1c" type="text/javascript"></script>
	<script type="text/javascript">
		const paleoTimes = <?= json_encode($paleoTimes) ?>;
		var clientRoot = "<?php echo $CLIENT_ROOT; ?>";
		$(document).ready(function() {
			<?php
			if($searchVar){
				?>
				sessionStorage.querystr = "<?php echo $searchVar; ?>";
				<?php
			}
			?>
			setHarvestParamsForm(document.harvestparams);
		});
	</script>
	<script src="../js/symb/api.taxonomy.taxasuggest.js?ver=4" type="text/javascript"></script>
	<style type="text/css">
		hr{ clear:both; margin: 10px 0px }
		button{ margin: 2px }
		select{ margin-bottom: 4px }
		.catHeaderDiv { font-weight:bold; font-size: 18px }
		.coordBoxDiv { float:left; border:2px solid brown; padding:10px; margin:5px; white-space: nowrap; }
		.coordBoxDiv .labelDiv { font-weight:bold;float:left }
		.coordBoxDiv .iconDiv { float: left; margin-left: 5px; }
		.coordBoxDiv .iconDiv img { width:18px; }
		.coordBoxDiv .elemDiv { clear:both; }
	</style>
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_harvestparamsMenu)?$collections_harvestparamsMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($collections_harvestparamsCrumbs)){
		if($collections_harvestparamsCrumbs){
			echo '<div class="navpath">';
			echo $collections_harvestparamsCrumbs.' &gt;&gt; ';
			echo '<b>'.$LANG['NAV_SEARCH'].'</b>';
			echo '</div>';
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href="../index.php"><?php echo htmlspecialchars($LANG['NAV_HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
			<a href="index.php"><?php echo htmlspecialchars($LANG['NAV_COLLECTIONS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
			<b><?php echo $LANG['NAV_SEARCH']; ?></b>
		</div>
		<?php
	}
	?>
	<div role="main" id="innertext">
		<h1 class="page-heading bottom-breathing-room-rel top-breathing-room-rel"><?php echo $LANG['SEARCH']; ?></h1>
		<form name="harvestparams" id="harvestparams" action="list.php" method="post" onsubmit="return checkHarvestParamsForm(this)">
			<hr/>
			<div>
				<div style="float:left">
					<div>
						<div class="catHeaderDiv"><?php echo $LANG['TAXON_HEADER']; ?></div>
						<div style="margin:10px 0px 0px 5px;"><input type='checkbox' name='usethes' id='usethes' value='1' CHECKED />
						<label for="usethes"><?php echo $LANG['INCLUDE_SYNONYMS']; ?></div></label>
					</div>
					<section class="flex-form">
						<div>
							<label for="taxontype"><?php echo $LANG['SELECT_TAXON_TYPE'] ?>:</label>
							<select id="taxontype" name="taxontype">
								<?php
								$taxonType = 1;
								if(isset($DEFAULT_TAXON_SEARCH) && $DEFAULT_TAXON_SEARCH) $taxonType = $DEFAULT_TAXON_SEARCH;
								$taxonTypeRange = 6;
								if(isset($DISPLAY_COMMON_NAMES) && !$DISPLAY_COMMON_NAMES) $taxonTypeRange = 5;
								for($h=1;$h<$taxonTypeRange;$h++){
									echo '<option value="'.$h.'" '.($taxonType==$h?'SELECTED':'').'>'.$LANG['SELECT_1-'.$h].'</option>';
								}
								?>
							</select>
						</div>
						<div>
							<label for="taxa"><?php echo $LANG['TYPE_TAXON'] ?>:</label>
							<input id="taxa" type="text" size="60" name="taxa" id="taxa" value="" title="<?php echo $LANG['SEPARATE_MULTIPLE']; ?>" />
						</div>
					</section>
				</div>
				<div style='float:right;margin:0px 10px;'>
					<div><button type="submit" style="width:100%"><?php echo isset($LANG['BUTTON_NEXT_LIST'])?$LANG['BUTTON_NEXT_LIST']:'List Display'; ?></button></div>
					<div><button type="button" style="width:100%" onclick="displayTableView(this.form)"><?php echo isset($LANG['BUTTON_NEXT_TABLE'])?$LANG['BUTTON_NEXT_TABLE']:'Table Display'; ?></button></div>
					<div><button type="reset" style="width:100%" onclick="resetHarvestParamsForm()"><?php echo isset($LANG['BUTTON_RESET'])?$LANG['BUTTON_RESET']:'Reset Form'; ?></button></div>
				</div>
			</div>
			<hr/>
			<div>
				<div class="catHeaderDiv"><?php echo $LANG['LOCALITY_CRITERIA']; ?></div>
			</div>
			<div>
				<label for="country"><?php echo $LANG['COUNTRY']; ?>:</label>
				<input type="text" id="country" size="43" name="country" value="" title="<?php echo $LANG['SEPARATE_MULTIPLE']; ?>" />
			</div>
			<div>
				<label for="state"><?php echo $LANG['STATE']; ?>:</label>
				<input type="text" id="state" size="37" name="state" value="" title="<?php echo $LANG['SEPARATE_MULTIPLE']; ?>" />
			</div>
			<div>
				<label for="county"><?php echo $LANG['COUNTY']; ?>:</label>
				<input type="text" id="county" size="37"  name="county" value="" title="<?php echo $LANG['SEPARATE_MULTIPLE']; ?>" />
			</div>
			<div>
				<label for="locality"><?php echo $LANG['LOCALITY']; ?>:</label>
				<input type="text" id="locality" size="43" name="local" value="" />
			</div>
			<section class="flex-form">
				<div>
					<label for="elevlow"><?php echo $LANG['ELEV_INPUT_1']; ?>:</label>
					<input type="text" id="elevlow" size="10" name="elevlow" value="" onchange="cleanNumericInput(this);" />
				</div>
				<div>
					<label for="elevhigh"><?php echo $LANG['ELEV_INPUT_2']; ?>:</label>
					<input type="text" id="elevhigh" size="10" name="elevhigh" value="" onchange="cleanNumericInput(this);" />
				</div>
			</section>
			<hr>
			<div class="catHeaderDiv"><?php echo $LANG['LAT_LNG_HEADER']; ?></div>
			<div>
				<div class="coordBoxDiv">
					<div class="labelDiv">
						<?php echo $LANG['LL_BOUND_TEXT']; ?>
					</div>
					<div class="iconDiv">
						<a href="#" onclick="openCoordAid({map_mode: MAP_MODES.RECTANGLE, client_root: '<?= $CLIENT_ROOT?>'});return false;"><img src="../images/map.png" title="<?php echo htmlspecialchars((isset($LANG['MAP_AID'])?$LANG['MAP_AID']:'Mapping Aid'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" /></a>
					</div>
					<div class="elemDiv">
						<div>
							<label for="upperlat"><?php echo $LANG['LL_BOUND_NLAT']; ?>:</label>
							<input type="text" id="upperlat" name="upperlat" size="7" value="" onchange="cleanNumericInput(this);">
							<label for="upperlat_NS"><?php echo $LANG['DIRECTION'] ?>:</label>
							<select id="upperlat_NS" name="upperlat_NS">
								<option id="ulN" value="N"><?php echo $LANG['LL_N_SYMB']; ?></option>
								<option id="ulS" value="S"><?php echo $LANG['LL_S_SYMB']; ?></option>
							</select>
						</div>
						<div>
							<label for="bottomlat"><?php echo $LANG['LL_BOUND_SLAT']; ?>:</label>
							<input type="text" id="bottomlat" name="bottomlat" size="7" value="" onchange="cleanNumericInput(this);">
							<label for="bottomlat_NS"><?php echo $LANG['DIRECTION'] ?>:</label>
							<select id="bottomlat_NS" name="bottomlat_NS">
								<option id="blN" value="N"><?php echo $LANG['LL_N_SYMB']; ?></option>
								<option id="blS" value="S"><?php echo $LANG['LL_S_SYMB']; ?></option>
							</select>
						</div>
						<div>
							<label for="leftlong"><?php echo $LANG['LL_BOUND_WLNG']; ?>:</label>
							<input type="text" id="leftlong" name="leftlong" size="7" value="" onchange="cleanNumericInput(this);">
							<label for="leftlong_EW"><?php echo $LANG['DIRECTION'] ?>:</label>
							<select id="leftlong_EW" name="leftlong_EW">
								<option id="llW" value="W"><?php echo $LANG['LL_W_SYMB']; ?></option>
								<option id="llE" value="E"><?php echo $LANG['LL_E_SYMB']; ?></option>
							</select>
						</div>
						<div>
							<label for="rightlong"><?php echo $LANG['LL_BOUND_ELNG']; ?>:</label>
							<input type="text" id="rightlong" name="rightlong" size="7" value="" onchange="cleanNumericInput(this);" style="margin-left:3px;">
							<label for="rightlong_EW"><?php echo $LANG['DIRECTION'] ?>:</label>
							<select id="rightlong_EW" name="rightlong_EW">
								<option id="rlW" value="W"><?php echo $LANG['LL_W_SYMB']; ?></option>
								<option id="rlE" value="E"><?php echo $LANG['LL_E_SYMB']; ?></option>
							</select>
						</div>
					</div>
				</div>
				<div class="coordBoxDiv">
					<div class="labelDiv">
						<label for="footprintwkt">
							<?php echo isset($LANG['LL_POLYGON_TEXT'])?$LANG['LL_POLYGON_TEXT']:''; ?>
						</label>
					</div>
					<div class="iconDiv">
						&nbsp;<a href="#" onclick="openCoordAid({map_mode: MAP_MODES.POLYGON, polygon_text_type: POLYGON_TEXT_TYPES.GEOJSON, client_root: '<?= $CLIENT_ROOT?>'});return false;"><img src="../images/map.png" title="<?php echo htmlspecialchars((isset($LANG['MAP_AID'])?$LANG['MAP_AID']:'Mapping Aid'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" /></a>
					</div>
					<div class="elemDiv">
						<textarea id="footprintwkt" name="footprintGeoJson" onchange="this.value = validatePolygon(this.value)" style="zIndex:999;width:100%;height:90px"></textarea>
					</div>
				</div>
				<div class="coordBoxDiv">
					<div class="labelDiv">
						<?php echo $LANG['LL_P-RADIUS_TEXT']; ?>
					</div>
					<div class="iconDiv">
						<a href="#" onclick="openCoordAid({map_mode: MAP_MODES.CIRCLE, client_root: '<?= $CLIENT_ROOT?>'});return false;"><img src="../images/map.png" title="<?php echo (isset($LANG['MAP_AID'])?$LANG['MAP_AID']:'Mapping Aid'); ?>" /></a>
					</div>
					<div class="elemDiv">
						<section class="flex-form">
							<div>
								<label for="pointlat"><?php echo $LANG['LL_P-RADIUS_LAT']; ?>:</label>
								<input type="text" id="pointlat" name="pointlat" size="7" value="" onchange="cleanNumericInput(this);">
								<label for="pointlat_NS"><?php echo $LANG['DIRECTION'] ?>:</label>
								<select id="pointlat_NS" name="pointlat_NS">
									<option id="N" value="N"><?php echo $LANG['LL_N_SYMB']; ?></option>
									<option id="S" value="S"><?php echo $LANG['LL_S_SYMB']; ?></option>
								</select>
							</div>
						</section>
						<section class="flex-form">
							<div>
								<label for="pointlong"><?php echo $LANG['LL_P-RADIUS_LNG']; ?>:</label>
								<input type="text" id="pointlong" name="pointlong" size="7" value="" onchange="cleanNumericInput(this);">
								<label for="pointlong_EW"><?php echo $LANG['DIRECTION'] ?>:</label>
								<select id="pointlong_EW" name="pointlong_EW">
									<option id="W" value="W"><?php echo $LANG['LL_W_SYMB']; ?></option>
									<option id="E" value="E"><?php echo $LANG['LL_E_SYMB']; ?></option>
								</select>
							</div>
						</section>
						<section class="flex-form">
							<div>
								<label for="radius"><?php echo $LANG['LL_P-RADIUS_RADIUS']; ?>:</label>
								<input type="text" id="radius" name="radius" size="5" value="" onchange="cleanNumericInput(this);">
							</div>
							<div>
								<label for="radiusunits"><?php echo $LANG['DISTANCE_UNIT'] ?>:</label>
								<select id="radiusunits" name="radiusunits">
									<option value="km"><?php echo $LANG['LL_P-RADIUS_KM']; ?></option>
									<option value="mi"><?php echo $LANG['LL_P-RADIUS_MI']; ?></option>
								</select>
							</div>
						</section>
					</div>
				</div>
			</div>
			<hr/>
			<div class="catHeaderDiv"><?php echo $LANG['COLLECTOR_HEADER']; ?></div>
			<div>
				<label for="collector"><?php echo $LANG['COLLECTOR_LASTNAME']; ?>:</label>
				<input type="text" id="collector" size="32" name="collector" value="" title="<?php echo $LANG['SEPARATE_MULTIPLE']; ?>" />
			</div>
			<div>
				<label for="collnum"><?php echo $LANG['COLLECTOR_NUMBER']; ?>:</label>
				<input type="text" id="collnum" size="31" name="collnum" value="" title="<?php echo $LANG['TITLE_TEXT_2']; ?>" />
			</div>
			<section class="flex-form">
				<div>
					<label for="eventdate1"><?php echo $LANG['COLLECTOR_DATE']; ?>:</label>
					<input type="text" id="eventdate1" size="32" name="eventdate1" style="width:100px;" value="" title="<?php echo $LANG['TITLE_TEXT_3']; ?>" /> -
				</div>
				<div>
					<label for="eventdate2"><?php echo $LANG['COLLECTOR_DATE_END']; ?>:</label>
					<input type="text" id="eventdate2" size="32" name="eventdate2" style="width:100px;" value="" title="<?php echo $LANG['TITLE_TEXT_4']; ?>" />
				</div>
			</section>
			<hr/>
			<div style="float:left">
				<div>
					<div class="catHeaderDiv"><?php echo $LANG['SPECIMEN_HEADER']; ?></div>
				</div>
				<div>
					<label for="catnum"><?php echo $LANG['CATALOG_NUMBER']; ?>:</label>
					<input type="text" id="catnum" size="32" name="catnum" value="" title="<?php echo $LANG['SEPARATE_MULTIPLE']; ?>" />
					<input name="includeothercatnum" id="includeothercatnum" type="checkbox" value="1" checked />
					<label for="includeothercatnum"><?php echo $LANG['INCLUDE_OTHER_CATNUM']?></label>
				</div>
				<?php
				if($matSampleTypeArr = $collManager->getMaterialSampleTypeArr()){
					?>
					<div>
						<label for="materialsampletype"><?= $LANG['MATERIAL_SAMPLE_TYPE'] ?></label>
						<select name="materialsampletype" id="materialsampletype">
							<option value="">---------------</option>
							<option value="all-ms"><?= $LANG['ALL_MATERIAL_SAMPLE'] ?></option>
							<?php
							foreach($matSampleTypeArr as $matSampeType){
								echo '<option value="' . $matSampeType . '">' . $matSampeType . '</option>';
							}
							?>
						</select>
					</div>
					<?php
				}
				?>
				<div>
					<input type='checkbox' name='typestatus' id='typestatus' value='1' />
					<label for="typestatus"><?php echo isset($LANG['TYPE'])?$LANG['TYPE']:'Limit to Type Specimens Only'; ?></label>
				</div>
				<div>
					<input type='checkbox' name='hasimages' id='hasimages' value='1' />
					<label for="hasimages"><?php echo isset($LANG['HAS_IMAGE'])?$LANG['HAS_IMAGE']:'Limit to Specimens with Images Only'; ?></label>
				</div>
				<div>
					<input type='checkbox' name='hasgenetic' id='hasgenetic' value='1' />
					<label for="hasgenetic"><?php echo isset($LANG['HAS_GENETIC'])?$LANG['HAS_GENETIC']:'Limit to Specimens with Genetic Data Only'; ?></label>
				</div>
				<div>
					<input type='checkbox' name='hascoords' id='hascoords' value='1' />
					<label for="hascoords"><?php echo isset($LANG['HAS_COORDS'])?$LANG['HAS_COORDS']:'Limit to Specimens with Geocoordinates Only'; ?></label>
				</div>
				<div>
					<input type='checkbox' name='includecult' id='includecult' value='1' <?= !empty($SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT) ? 'checked' : '' ?> />
					<label for="includecult"><?php echo isset($LANG['INCLUDE_CULTIVATED'])?$LANG['INCLUDE_CULTIVATED']:'Include cultivated/captive occurrences'; ?></label>
				</div>
			</div>
			<?php
			if(!empty($ACTIVATE_PALEO)) {
				$gtsTermArr = $collManager->getPaleoGtsTerms();
				?>
				<hr/>
				<div id="searchFormPaleo" style="float:left">
					<div>
						<div class="catHeaderDiv bottom-breathing-room-rel-sm"><?php echo $LANG['GEO_CONTEXT']; ?></div>
					</div>
					<div>
						<div>
							<div>
								<div>
									<label for="lateInterval"><?php echo $LANG['LATE_INT'] ?>:</label>
									<select name="lateInterval" type="text" id="lateInterval">
										<option value=""></option>
										<?php
										$lateIntervalTerm = '';
										if(isset($occArr['lateInterval'])) $lateIntervalTerm = $occArr['lateInterval'];
										if($lateIntervalTerm && !array_key_exists($lateIntervalTerm, $gtsTermArr)){
											echo '<option value="'.$lateIntervalTerm.'" SELECTED>'.$lateIntervalTerm.' - mismatched term</option>';
											echo '<option value="">---------------------------</option>';
										}
										foreach($gtsTermArr as $term => $rankid){
											echo '<option value="'.$term.'" '.($lateIntervalTerm==$term?'SELECTED':'').'>'.$term.'</option>';
										}
										?>
									</select>
								</div>
								<label for="earlyInterval"><?php echo $LANG['EARLY_INT'] ?>:</label>
									<select name="earlyInterval" type="text" id="earlyInterval">
										<option value=""></option>
										<?php
										$earlyIntervalTerm = '';
										if(isset($occArr['earlyInterval'])) $earlyIntervalTerm = $occArr['earlyInterval'];
										if($earlyIntervalTerm && !array_key_exists($earlyIntervalTerm, $gtsTermArr)){
											echo '<option value="'.$earlyIntervalTerm.'" SELECTED>'.$earlyIntervalTerm.' - mismatched term</option>';
											echo '<option value="">---------------------------</option>';
										}
										foreach($gtsTermArr as $term => $rankid){
											echo '<option value="'.$term.'" '.($earlyIntervalTerm==$term?'SELECTED':'').'>'.$term.'</option>';
										}
										?>
									</select>
								</div>
						</div>
						<div>
							<div>
								<label for="lithogroup"><?php echo $LANG['LITHOGROUP']?>:</label>
									<input type="text" name="lithogroup" id="lithogroup"/>
							</div>
							<div>
								<label for="formation"> <?php echo $LANG['FORMATION']?>:</label>
									<input type="text" name="formation" id="formation"/>
							</div>
							<div>
								<label for="member"><?php echo $LANG['MEMBER']?>:</label>
									<input type="text" name="member" id="member"/>
							</div>
							<div>
								<label for="bed"><?php echo $LANG['BED']?>:</label>
									<input type="text" name="bed" id="bed"/>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			if(!empty($SEARCH_BY_TRAITS)) {
				$attribSearch = new OccurrenceAttributeSearch();
				$traitArr = $attribSearch->getTraitSearchArr($SEARCH_BY_TRAITS);
				if($traitArr){
					?>
					<hr/>
					<div style="float:left">
						<div>
							<div class="catHeaderDiv"><?php echo $LANG['TRAIT_HEADER']; ?></div>
							<div><?php echo $LANG['TRAIT_DESCRIPTION']; ?></div>
							<input type="hidden" id="SearchByTraits" value="true">
						</div>
						<?php
						foreach($traitArr as $traitID => $traitData){
							if(!isset($traitData['dependentTrait'])) {
								?>
								<fieldset style="margin-top:10px;display:inline;min-width:500px">
									<legend><b>Trait: <?php echo $traitData['name']; ?></b></legend>
									<div style="float:right">
										<div class="trianglediv" style="margin:4px 3px;float:right;cursor:pointer" onclick="setAttributeTree(this)" title="Toggle attribute tree open/close">
											<img class="triangleright" src="../images/triangleright.png" style="width:1.3em;display:none" />
											<img class="triangledown" src="../images/triangledown.png" style="width:1.3em;" />
										</div>
									</div>
									<div class="traitDiv" style="margin-left:5px;float:left">
										<?php $attribSearch->echoTraitSearchForm($traitID); ?>
									</div>
								</fieldset>
								<?php
							}
						}
						?>
					</div>
					<?php
				}
			}
			?>

			<div style="float:right;">
				<div><button type="submit" style="width:100%"><?php echo isset($LANG['BUTTON_NEXT_LIST'])?$LANG['BUTTON_NEXT_LIST']:'List Display'; ?></button></div>
				<div><button type="button" style="width:100%" onclick="displayTableView(this.form)"><?php echo isset($LANG['BUTTON_NEXT_TABLE'])?$LANG['BUTTON_NEXT_TABLE']:'Table Display'; ?></button></div>
				<div><button type="reset" style="width:100%" onclick="resetHarvestParamsForm()"><?php echo isset($LANG['BUTTON_RESET'])?$LANG['BUTTON_RESET']:'Reset Form'; ?></button></div>
			</div>
			<div>
				<input name="comingFrom" type="hidden" value="harvestparams" >
				<input type="hidden" name="db" value="<?php echo $collManager->getSearchTerm('db'); ?>" />
			</div>
			<hr/>
		</form>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
