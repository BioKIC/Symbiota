<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/CollectionMetadata.php');
include_once($SERVER_ROOT . '/classes/DatasetsMetadata.php');
include_once($SERVER_ROOT.'/classes/OccurrenceManager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceAttributeSearch.php');
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT . '/content/lang/collections/search/index.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/search/index.en.php');
else include_once($SERVER_ROOT . '/content/lang/collections/search/index.' . $LANG_TAG . '.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$dbsWithBracketsRemoved = array_key_exists("db",$_GET) ?  str_replace(array('[',']'), '', $_GET["db"]) : '';
$explodable = $dbsWithBracketsRemoved;
if(is_array($dbsWithBracketsRemoved)){
	$explodable = $dbsWithBracketsRemoved[0];
}
$collIdsFromUrl = array_key_exists("db",$_GET) ? explode(",", $explodable) : '';

$collManager = new OccurrenceManager();
$collectionSource = $collManager->getQueryTermStr();

$SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT = $SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT ?? false;
$collData = new CollectionMetadata();
$siteData = new DatasetsMetadata();

$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:'';
$collList = $collManager->getFullCollectionList($catId);
$specArr = (isset($collList['spec'])?$collList['spec']:null);
$obsArr = (isset($collList['obs'])?$collList['obs']:null);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">

<head>
	<title><?php echo $DEFAULT_TITLE; ?> - <?php echo $LANG['SAMPLE_SEARCH'] ?></title>
	<link href="<?= $CSS_BASE_PATH ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<link href="<?= $CLIENT_ROOT ?>/collections/search/css/searchStyles.css?ver=1" type="text/css" rel="stylesheet">
	<link href="<?= $CLIENT_ROOT ?>/collections/search/css/searchStylesInner.css" type="text/css" rel="stylesheet">
	<link href="<?= $CLIENT_ROOT ?>/collections/search/css/tables.css" type="text/css" rel="stylesheet">
	<link href="<?= $CSS_BASE_PATH ?>/symbiota/collections/sharedCollectionStyling.css" type="text/css" rel="stylesheet">
	<script src="<?= $CLIENT_ROOT ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT . '/js/jquery-ui.min.js'; ?>" type="text/javascript"></script>
	<script src="../../js/symb/localitySuggest.js" type="text/javascript"></script>
	<script>
		const clientRoot = '<?php echo $CLIENT_ROOT; ?>';
		const handleAccordionExpand = () => {
			const accordions = document.querySelectorAll('input[class="accordion-selector"]');
			accordions.forEach(accordion => {
				accordion.checked = true;
			});

			const expandButton = document.getElementById("expand-all-button");
			expandButton.setAttribute('style', 'display: none;');
			const collapseButton = document.getElementById("collapse-all-button");
			collapseButton.removeAttribute('style', 'display: none;');
		};

		const handleAccordionCollapse = ()=>{
			const accordions = document.querySelectorAll('input[class="accordion-selector"]');
			accordions.forEach(accordion => {
				accordion.checked=false;
			})

			const collapseButton = document.getElementById("collapse-all-button");
			collapseButton.setAttribute('style', 'display: none;');
			const expandButton = document.getElementById("expand-all-button");
			expandButton.removeAttribute('style', 'display: none;');
		};
	</script>

	<?php include_once($SERVER_ROOT . '/includes/googleanalytics.php'); ?>
	<!-- Search-specific styles -->
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<style>
		.bounding-box-form__header {
			font-size: 1.3rem;
		}
		.full-width-pcnt {
			width: 100%;
		}
	</style>
</head>

<body>
	<?php
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext" class="inner-search" style="max-width: 1920px">
		<h1 class="page-heading"><?php echo $LANG['SAMPLE_SEARCH'] ?></h1>
		<div id="error-msgs" class="errors"></div>
		<div style="display: grid; grid-template-columns: 3fr 1fr;">
			<button onClick="handleAccordionExpand()" class="inner-search button" id="expand-all-button" type="button" style="font-size: 1rem;"><?= $LANG['EXPAND_ALL_SECTIONS']; ?></button>
			<button onClick="handleAccordionCollapse()" class="inner-search button" id="collapse-all-button" type="button" style="display: none; font-size: 1rem;"><?= $LANG['COLLAPSE_ALL_SECTIONS']; ?></button>
		</div>
		<form id="params-form" action="javascript:void(0);">
			<!-- Criteria forms -->
			<div class="accordions">
				<!-- Taxonomy -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="taxonomy" class="accordion-selector" checked />

					<!-- Accordion header -->
					<label for="taxonomy" class="accordion-header"><?php echo $LANG['TAXONOMY'] ?></label>

					<!-- Taxonomy -->
					<div id="search-form-taxonomy" class="content">
						<div id="taxa-text" class="input-text-container">
							<label for="taxa" class="input-text--outlined">
								<span class="screen-reader-only"><?php echo $LANG['TAXON'] ?></span>
								<input type="text" name="taxa" id="taxa" data-chip="<?php echo $LANG['TAXON'] ?>" />
								<span class="inset-input-label"><?php echo $LANG['TAXON'] ?></span>
							</label>
							<span class="assistive-text"><?php echo $LANG['TYPE_CHAR_FOR_SUGGESTIONS'] ?></span>
						</div>
							<div style="padding-top:14px">
						<div class="select-container" style="position: relative">
							<label for="taxontype" class="screen-reader-only"><?php echo $LANG['TAXON_TYPE'] ?></label>
							<select name="taxontype" id="taxontype" style="margin-top:0;padding-top:0; margin-bottom: 0.5rem">
								<option id="taxontype-scientific" value="2" data-chip="<?php echo $LANG['TAXON'] . ': ' . $LANG['SCIENTIFIC_NAME'] ?>"><?php echo $LANG['SCIENTIFIC_NAME'] ?></option>
								<option id="taxontype-family" value="3" data-chip="<?php echo $LANG['TAXON'] . ': ' . $LANG['FAMILY'] ?>"><?php echo $LANG['FAMILY'] ?></option>
								<option id="taxontype-group" value="4" data-chip="<?php echo $LANG['TAXON'] . ': ' . $LANG['TAXONOMIC_GROUP'] ?>"><?php echo $LANG['TAXONOMIC_GROUP'] ?></option>
								<option id="taxontype-common" value="5" data-chip="<?php echo $LANG['TAXON'] . ': ' . $LANG['COMMON_NAME'] ?>"><?php echo $LANG['COMMON_NAME'] ?></option>
							</select>
							<span class="inset-input-label"><?php echo $LANG['TAXON_TYPE'] ?></span>
						</div>
							</div>
						<div>
							<input type="checkbox" name="usethes" id="usethes" data-chip="<?php echo $LANG['INCLUDE_SYNONYMS'] ?>" value="1" checked />
							<label for="usethes">
								<span class="ml-1"><?php echo $LANG['INCLUDE_SYNONYMS'] ?></span>
							</label>
						</div>
					</div>
				</section>

				<!-- Locality -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="locality" name="locality" class="accordion-selector" />
					<!-- Accordion header -->
					<label for="locality" class="accordion-header"><?php echo $LANG['LOCALITY'] ?></label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-locality">
							<div>
								<div class="input-text-container">
									<label for="country" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['COUNTRY'] ?></span>
										<input type="text" name="country" id="country" data-chip="<?php echo $LANG['COUNTRY'] ?>" />
										<span class="inset-input-label"><?php echo $LANG['COUNTRY'] ?></span>
									</label>
									<span class="assistive-text"><?php echo $LANG['SEPARATE_MULTIPLE_W_COMMA'] ?></span>
								</div>
								<div class="input-text-container">
									<label for="state" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['STATE'] ?></span>
										<input type="text" name="state" id="state" data-chip="<?php echo $LANG['STATE'] ?>" />
										<span class="inset-input-label"><?php echo $LANG['STATE'] ?></span>
									</label>
									<span class="assistive-text"><?php echo $LANG['SEPARATE_MULTIPLE_W_COMMA'] ?></span>
								</div>
								<div class="input-text-container">
									<label for="county" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['COUNTY'] ?></span>
										<input type="text" name="county" id="county" data-chip="<?php echo $LANG['COUNTY'] ?>" />
										<span class="inset-input-label"><?php echo $LANG['COUNTY'] ?></span>
									</label>
									<span class="assistive-text"><?php echo $LANG['SEPARATE_MULTIPLE_W_COMMA'] ?></span>
								</div>
							</div>
<div>
								<div class="input-text-container">
									<label for="local" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['LOCALITY_LOCALITIES'] ?></span>
										 <input type="text" name="local" id="local" data-chip="<?php echo $LANG['LOCALITY'] ?>" />
										<span class="inset-input-label"><?php echo $LANG['LOCALITY_LOCALITIES'] ?></span>
									</label>
									<span class="assistive-text"><?php echo $LANG['SEPARATE_MULTIPLE_W_COMMA'] ?></span>
								</div>
								<div class="grid grid--half">
									<div class="input-text-container">
										<label for="elevlow" class="input-text--outlined">
											<span class="screen-reader-only"><?php echo $LANG['MINIMUM_ELEVATION'] ?></span>
											<input type="number" step="any" name="elevlow" id="elevlow" data-chip="<?php echo $LANG['MIN_ELEVATION'] ?>" />
											<span class="inset-input-label"><?php echo $LANG['MINIMUM_ELEVATION'] ?></span>
										</label>
										<span class="assistive-text"><?php echo $LANG['NUMBER_IN_METERS'] ?></span>
									</div>
									<div class="input-text-container">
										<label for="elevhigh" class="input-text--outlined">
											<span class="screen-reader-only"><?php echo $LANG['MAXIMUM_ELEVATION'] ?></span>
											<input type="number" step="any" name="elevhigh" id="elevhigh" data-chip="<?php echo $LANG['MAX_ELEVATION'] ?>" />
											<span class="inset-input-label"><?php echo $LANG['MAXIMUM_ELEVATION'] ?></span>
										</label>
										<span class="assistive-text"><?php echo $LANG['NUMBER_IN_METERS'] ?></span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</section>

				<!-- Latitude & Longitude -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="lat-long" class="accordion-selector" />
					<!-- Accordion header -->
					<label for="lat-long" class="accordion-header"><?php echo $LANG['LATITUDE_LONGITUDE'] ?></label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-latlong">
							<div id="bounding-box-form">
								<h1 class="bounding-box-form__header"><?php echo $LANG['BOUNDING_BOX'] ?></h1>

								<button type="button" onclick="openCoordAid('rectangle');"><?php echo $LANG['SELECT_IN_MAP'] ?></button>
								<div class="input-text-container">
										<label for="upperlat" class="input-text--outlined">
											<span class="screen-reader-only"><?php echo $LANG['NORTHERN_LATITUDE'] ?></span>
											<input type="number" step="any" min="-90" max="90" id="upperlat" name="upperlat" data-chip="<?php echo $LANG['UPPER_LAT'] ?>" />
											<span class="inset-input-label"><?php echo $LANG['NORTHERN_LATITUDE'] ?></span>
											<span class="assistive-text"><?php echo $LANG['VALUE_BETWEEN_NUM'] ?></span>
										</label>

										<label for="upperlat_NS" class="input-text--outlined">
											<span class="screen-reader-only"><?php echo $LANG['SELECT_UPPER_LAT_DIRECTION_NORTH_SOUTH'] ?></span>
											<select class="mt-1" id="upperlat_NS" name="upperlat_NS">
												<option value=""><?php echo $LANG['SELECT_NORTH_SOUTH'] ?></option>
												<option id="ulN" value="N"><?php echo $LANG['NORTH'] ?></option>
												<option id="ulS" value="S"><?php echo $LANG['SOUTH'] ?></option>
											</select>
										</label>
								</div>
								<div class="input-text-container">
									<label for="bottomlat" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['SOUTHERN_LATITUDE'] ?></span>
										<input type="number" step="any" min="-90" max="90" id="bottomlat" name="bottomlat" data-chip="<?php echo $LANG['BOTTOM_LAT'] ?>" />
										<span class="inset-input-label"><?php echo $LANG['SOUTHERN_LATITUDE'] ?></span>
										<span class="assistive-text"><?php echo $LANG['VALUE_BETWEEN_NUM'] ?></span>
									</label>
									<label for="bottomlat_NS">
										<span class="screen-reader-only"><?php echo $LANG['SELECT_BOTTOM_LAT_DIREC_NORTH_SOUTH'] ?></span>
										<select class="mt-1" id="bottomlat_NS" name="bottomlat_NS">
											<option value=""><?php echo $LANG['SELECT_NORTH_SOUTH'] ?></option>
											<option id="blN" value="N"><?php echo $LANG['NORTH'] ?></option>
											<option id="blS" value="S"><?php echo $LANG['SOUTH'] ?></option>
										</select>
									</label>
								</div>
								<div class="input-text-container">
									<label for="leftlong" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['LEFT_LONGITUDE'] ?></span>
										<input type="number" step="any" min="-180" max="180" id="leftlong" name="leftlong" data-chip="<?php echo $LANG['LEFT_LONG'] ?>" />
										<span class="inset-input-label"><?php echo $LANG['WESTERN_LONGITUDE'] ?></span>
										<span class="assistive-text"><?php echo $LANG['VALUES_BETWEEN_NEG180_TO_180'] ?></span>
									</label>
									<label for="leftlong_EW" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['SELECT_LEFT_LONG_DIREC_WEST_EAST'] ?></span>
										<select class="mt-1" id="leftlong_EW" name="leftlong_EW">
											<option value=""><?php echo $LANG['SELECT_WEST_EAST'] ?></option>
											<option id="llW" value="W"><?php echo $LANG['WEST'] ?></option>
											<option id="llE" value="E"><?php echo $LANG['EAST'] ?></option>
										</select>
									</label>
								</div>
								<div class="input-text-container">
									<label for="rightlong" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['RIGHT_LONGITUDE'] ?></span>
										<input type="number" step="any" min="-180" max="180" id="rightlong" name="rightlong" data-chip="<?php echo $LANG['RIGHT_LONG'] ?>" />
										<span class="inset-input-label"><?php echo $LANG['EASTERN_LONGITUDE'] ?></span>
										<span class="assistive-text"><?php echo $LANG['VALUES_BETWEEN_NEG180_TO_180'] ?></span>
									</label>
										<label for="rightlong_EW" class="input-text--outlined">
											<span class="screen-reader-only"><?php echo $LANG['SELECT_RIGHT_LONG_DIREC_WEST_EAST'] ?></span>
											<select class="mt-1" id="rightlong_EW" name="rightlong_EW">
												<option value=""><?php echo $LANG['SELECT_WEST_EAST'] ?></option>
												<option id="rlW" value="W"><?php echo $LANG['WEST'] ?></option>
												<option id="rlE" value="E"><?php echo $LANG['EAST'] ?></option>
											</select>
										</label>
								</div>
							</div>
							<div id="polygon-form">
								<h1 class="bounding-box-form__header"><?php echo $LANG['POLYGON_WKT_FOOTPRINT'] ?></h1>
								<button type="button" onclick="openCoordAid('polygon')"><?php echo $LANG['SELECT_MAP_POLYGON'] ?></button>
								<div class="text-area-container">
									<label for="footprintwkt" class="text-area--outlined">
										<span class="screen-reader-only"><?php echo $LANG['POLYGON'] ?></span>
										<textarea id="footprintwkt" name="footprintwkt" class="full-width-pcnt" rows="5"></textarea>
										<span class="inset-input-label"><?php echo $LANG['POLYGON'] ?></span>
									</label>
									<span class="assistive-text"><?php echo $LANG['SELECT_MAP_BUTTON_PASTE'] ?></span>
								</div>
							</div>
							<div id="point-radius-form">
								<h1 class="bounding-box-form__header"><?php echo $LANG['POINT_RADIUS'] ?></h1>
								<button type="button" onclick="openCoordAid('circle');"><?php echo $LANG['SELECT_MAP_PR'] ?></button>
								<div class="input-text-container">
									<label for="pointlat" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['POINT_LATITUDE'] ?></span>
										<input type="number" step="any" min="-90" max="90" id="pointlat" name="pointlat" data-chip="<?php echo $LANG['POINT_LAT'] ?>" />
										<span class="inset-input-label"><?php echo $LANG['LATITUDE'] ?></span>
										<span class="assistive-text"><?php echo $LANG['VALUE_BETWEEN_NUM'] ?></span>
									</label>
									<label for="pointlat_NS" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['POINT_LAT_DIREC_NORTH_SOUTH'] ?></span>
										<select class="mt-1" id="pointlat_NS" name="pointlat_NS">
											<option value=""><?php echo $LANG['SELECT_NORTH_SOUTH'] ?></option>
											<option id="N" value="N"><?php echo $LANG['NORTH'] ?></option>
											<option id="S" value="S"><?php echo $LANG['SOUTH'] ?></option>
										</select>
									</label>
								</div>
								<div class="input-text-container">
									<label for="pointlong" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['POINT_LONGITUDE'] ?></span>
										<input type="number" step="any" min="-180" max="180" id="pointlong" name="pointlong" data-chip="<?php echo $LANG['POINT_LONG'] ?>" />
										<span class="inset-input-label"><?php echo $LANG['LONGITUDE'] ?></span>
										<span class="assistive-text"><?php echo $LANG['VALUES_BETWEEN_NEG180_TO_180'] ?></span>
									</label>
									<label for="pointlong_EW" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['POINT_LONGITUDE_DIREC_EAST_WEST'] ?></span>
										<select class="mt-1" id="pointlong_EW" name="pointlong_EW">
											<option value=""><?php echo $LANG['SELECT_WEST_EAST'] ?></option>
											<option id="W" value="W"><?php echo $LANG['WEST'] ?></option>
											<option id="E" value="E"><?php echo $LANG['EAST'] ?></option>
										</select>
									</label>
								</div>
								<div class="input-text-container">
									<label for="radius" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['RADIUS'] ?></span>
										<input type="number" min="0" step="any" id="radius" name="radius" data-chip="<?php echo $LANG['RADIUS'] ?>" />
										<span class="inset-input-label"><?php echo $LANG['RADIUS'] ?></span>
										<span class="assistive-text"><?php echo $LANG['ANY_POSITIVE_VALUES'] ?></span>
									</label>
									<label for="radiusunits" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['SELECT_RADIUS_UNITS'] ?></span>
										<select class="mt-1" id="radiusunits" name="radiusunits">
											<option value=""><?php echo $LANG['SELECT_UNIT'] ?></option>
											<option value="km"><?php echo $LANG['KILOMETERS'] ?></option>
											<option value="mi"><?php echo $LANG['MILES'] ?></option>
										</select>
									</label>
								</div>
							</div>
						</div>
					</div>
				</section>

				<!-- Collecting Event -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="coll-event" class="accordion-selector" />
					<!-- Accordion header -->
					<label for="coll-event" class="accordion-header"><?php echo $LANG['COLLECTING_EVENT'] ?></label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-coll-event">
							<div class="input-text-container">
								<label for="eventdate1" class="input-text--outlined">
									<span class="screen-reader-only"><?php echo $LANG['COLLECTION_START_DATE'] ?></span>
									<input type="text" name="eventdate1" id="eventdate1" data-chip="<?php echo $LANG['EVENT_DATE_START'] ?>" />
									<span class="inset-input-label"><?php echo $LANG['COLLECTION_START_DATE'] ?></span>
								</label>
								<span class="assistive-text"><?php echo $LANG['SINGLE_DATE_START_DATE'] ?></span>
							</div>
							<div class="input-text-container">
								<label for="eventdate2" class="input-text--outlined">
									<span class="screen-reader-only"><?php echo $LANG['COLLECTION_END_DATE'] ?></span>
									<input type="text" name="eventdate2" id="eventdate2" data-chip="<?php echo $LANG['EVENT_DATE_END'] ?>" />
									<span class="inset-input-label"><?php echo $LANG['COLLECTION_END_DATE'] ?></span>
								</label>
								<span class="assistive-text"><?php echo $LANG['SINGLE_DATE_END_DATE'] ?></span>
							</div>
							<div class="input-text-container">
								<label for="collector" class="input-text--outlined">
									<span class="screen-reader-only"><?php echo $LANG['COLLECTOR_LAST_NAME'] ?></span>
									<input type="text" id="collector" size="32" name="collector" value="" title="<?php echo $LANG['SEPARATE_MULTIPLE']; ?>" data-chip="<?php echo $LANG['COLLECTOR_LAST'] ?>" />
									<span class="inset-input-label"><?php echo $LANG['COLLECTOR_LASTNAME']; ?>:</span>
								</label>
							</div>
							<div class="input-text-container">
								<label for="collnum" class="input-text--outlined">
									<span class="screen-reader-only"><?php echo $LANG['COLLECTOR_NUMBER_'] ?></span>
									<input type="text" id="collnum" size="31" name="collnum" value="" title="<?php echo htmlspecialchars($LANG['TITLE_TEXT_2'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" data-chip="<?php echo $LANG['COLLECTOR_NUMBER'] ?>" />
									<span class="inset-input-label"><?php echo $LANG['COLLECTOR_NUMBER']; ?>:</span>
								</label>
							</div>
						</div>
					</div>
				</section>

				<!-- Sample Properties -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="sample" class="accordion-selector" />
					<!-- Accordion header -->
					<label for="sample" class="accordion-header"><?php echo $LANG['SAMPLE_PROPERTIES'] ?></label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-sample">
							<div>
								<div>
									<input type="checkbox" name="includeothercatnum" id="includeothercatnum" value="1" data-chip="<?php echo $LANG['INCLUDE_OTHER_IDS'] ?>" checked />
									<label for="includeothercatnum"><?php echo $LANG['INCLUDE_CATA_NUM_GUIDs'] ?></label>
								</div>
								<div class="input-text-container">
									<label for="catnum" class="input-text--outlined">
										<span class="screen-reader-only"><?php echo $LANG['CATALOG_NUMBER'] ?></span>
										<input type="text" name="catnum" id="catnum" data-chip="<?php echo $LANG['CATALOG_NUMBER'] ?>" />
										<span class="inset-input-label"><?php echo $LANG['CATALOG_NUMBER'] ?></span>
									</label>
									<span class="assistive-text"><?php echo $LANG['SEPARATE_MULTIPLE_W_COMMA_DASH'] ?></span>
								</div>
							</div>
							<div>
								<div>
									<input type='checkbox' name='typestatus' id='typestatus' value='1' data-chip="<?php echo $LANG['ONLY_TYPE_SPECIMENS'] ?>" />
									<label for="typestatus"><?php echo $LANG['TYPE'] ?></label>
								</div>
								<div>
									<input type="checkbox" name="hasimages" id="hasimages" value=1 data-chip="<?php echo $LANG['ONLY_WITH_IMAGES'] ?>" />
									<label for="hasimages"><?php echo $LANG['LIMIT_TO_SPECIMENS_W_IMAGES'] ?></label>
								</div>
								<div>
									<input type="checkbox" name="hasgenetic" id="hasgenetic" value=1 data-chip="<?php echo $LANG['ONLY_WITH_GENETIC'] ?>" />
									<label for="hasgenetic"><?php echo $LANG['LIMIT_TO_SPECIMENS_W_GENETIC_DATA'] ?></label>
								</div>
								<div>
									<input type='checkbox' name='hascoords' id='hascoords' value='1' data-chip="<?php echo $LANG['ONLY_WITH_COORDINATES'] ?>" />
									<label for="hascoords"><?php echo $LANG['HAS_COORDS'] ?></label>
								</div>
								<div>
									<input type='checkbox' name='includecult' id='includecult' value='1' data-chip="<?php echo $LANG['INCLUDE_CULTIVATED'] ?>" <?php echo $SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT ? 'checked' : '' ?> />
									<label for="includecult"><?php echo $LANG['INCLUDE_CULTIVATED'] ?></label>
								</div>
							</div>
							<?php
								if($matSampleTypeArr = $collManager->getMaterialSampleTypeArr()){
								?>
								<div class="select-container">
									<label for="materialsampletype"><?= $LANG['MATERIAL_SAMPLE_TYPE'] ?></label>
									<select name="materialsampletype" id="materialsampletype">
										<option id="materialsampletype-none" data-chip="<?php echo $LANG['MATERIAL_SAMPLE'] . ': ---' ?>" value="">---------------</option>
										<option id="materialsampletype-all-ms" data-chip="<?php echo $LANG['MATERIAL_SAMPLE'] . ': ' . $LANG['ALL_MATERIAL_SAMPLE'] ?>" value="all-ms"><?= $LANG['ALL_MATERIAL_SAMPLE'] ?></option>
										<?php
										foreach($matSampleTypeArr as $matSampeType){
											echo '<option id="materialsampletype-' . $matSampeType . '" data-chip="' . $LANG['MATERIAL_SAMPLE'] . ': ' . $matSampeType . '" value="' . $matSampeType . '">' . $matSampeType . '</option>';
										}
										?>
									</select>
								</div>
								<?php
							}
							?>
						</div>
					</div>
				</section>

				<!-- Traits -->
				<?php
					if(!empty($SEARCH_BY_TRAITS)) {
						$attribSearch = new OccurrenceAttributeSearch();
						$traitArr = $attribSearch->getTraitSearchArr($SEARCH_BY_TRAITS);
						if($traitArr){
							?>
							<section>
								<!-- Accordion selector -->
								<input type="checkbox" id="trait" class="accordion-selector" />
								<!-- Accordion header -->
								<label for="trait" class="accordion-header"><?php echo $LANG['TRAIT_CRITERIA'] ?></label>
								<!-- Accordion content -->
								<div class="content">
									<div id="search-form-trait">
										<div>
											<div>
												<div>
													<div class="bottom-breathing-room-rel"><?php echo $LANG['TRAIT_DESCRIPTION']; ?></div>
													<input type="hidden" id="SearchByTraits" value="true" />
												</div>
												<?php
												foreach($traitArr as $traitID => $traitData){
													if(!isset($traitData['dependentTrait'])) {
														?>
														<fieldset class="bottom-breathing-room-rel">
															<legend><?= $LANG['TRAIT']; ?>: <?php echo $traitData['name']; ?></legend>
															<div>
															</div>
															<div class="traitDiv">
																<?php $attribSearch->echoTraitSearchForm($traitID); ?>
															</div>
														</fieldset>
														<?php
														}
													}
												?>
											</div>
										</div>
									</div>
								</div>
							</section>
							<?php
						}
					}
				?>

				<!-- Collections -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="collections" class="accordion-selector" />
					<!-- Accordion header -->
					<label for="collections" class="accordion-header"><?php echo $LANG['COLLECTIONS'] ?></label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-colls">
							<!-- Open Collections modal -->
							<div id="specobsdiv">
								<?php
								include_once('./collectionContent.php');
								?>
							</div>
						</div>
					</div>
				</section>

			</div>

			<!-- Criteria panel -->
			<div id="criteria-panel" style="position: sticky; top: 0; height: 100vh">
			<fieldset class="bottom-breathing-room-rel">
				<legend>
					<?php echo $LANG['DISPLAY_FORMAT']; ?>
				</legend>
				<div style="display: flex; align-items: center;" class="bottom-breathing-room-rel">
					<input style="margin-bottom:0; margin-right: 0.5rem;" name="display-format-pref" id="list-button" type="radio" value="list" checked />
					<label for="list-button"><?php echo $LANG['LIST'] ?></label>
				</div>
				<div style="display: flex; align-items: center;">
					<input style="margin-bottom:0; margin-right: 0.5rem;" name="display-format-pref" id="table-button" type="radio" value="table" />
					<label for="table-button"><?php echo $LANG['TABLE'] ?></label>
				</div>
			</fieldset>
				<button id="search-btn" onclick="simpleSearch()"><?php echo $LANG['SEARCH'] ?></button>
				<button id="reset-btn"><?php echo $LANG['RESET'] ?></button>
				<h2><?php echo $LANG['CRITERIA'] ?></h2>
				<div id="chips"></div>
			</div>
		</form>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>
<script src="js/searchform.js?ver=1" type="text/javascript"></script>
<script src="<?php echo $CLIENT_ROOT . '/collections/search/js/alerts.js?v=202107'; ?>" type="text/javascript"></script>
<script src="<?php echo $CLIENT_ROOT . '/js/symb/api.taxonomy.taxasuggest.js'; ?>" type="text/javascript"></script>
<script src="<?php echo $CLIENT_ROOT . '/js/symb/collections.index.js?ver=20171215' ?>" type="text/javascript"></script>
<script type="text/javascript">
	$(document).ready(function() {
		<?php
		if($collectionSource){
			?>
			sessionStorage.querystr = "<?php echo $collectionSource; ?>";
			<?php
		}
		?>
		setSearchForm(document.getElementById("params-form"));
	});
</script>
<script>
	let alerts = [{
		'alertMsg': '<?php echo $LANG['ALERT_MSG_PREVIOUS_SEARCH_FORM'] ?> <a href="<?php echo $CLIENT_ROOT ?>/collections/index.php" alt="Traditional Sample Search Form"><?= $LANG['PREVIOUS_SAMPLE_SEARCH']; ?></a>.'
	}];
	handleAlerts(alerts, 3000);

	// resize the autocomplete window width to match the input width (from https://stackoverflow.com/questions/5643767/jquery-ui-autocomplete-width-not-set-correctly)
	jQuery.ui.autocomplete.prototype._resizeMenu = function () {
		var ul = this.menu.element;
		ul.outerWidth(this.element.outerWidth());
	}
	const collectionSource = <?php echo isset($collectionSource) ? json_encode($collectionSource) : 'null'; ?>;
	const collIdsFromUrl = <?php echo isset($collIdsFromUrl) ? json_encode($collIdsFromUrl) : 'null'; ?>;
	if (collIdsFromUrl && Array.isArray(collIdsFromUrl) && collIdsFromUrl.length > 0) {
		uncheckEverything();
		checkTheCollectionsThatShouldBeChecked(collIdsFromUrl);
	}
	const sanitizedCollectionSource = collectionSource.replace('db=','');
	if (collectionSource) {
		uncheckEverything();
		checkTheCollectionsThatShouldBeChecked(sanitizedCollectionSource);
		updateChip();
	}

	window.initLocalitySuggest({
		country: {
			id: 'country',
		},
		state_province: {
			id: 'state',
		},
		county: {
			id: 'county',
		},
	})
</script>

</html>
