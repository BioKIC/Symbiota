<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceGeorefTools.php');
include_once($SERVER_ROOT.'/content/lang/collections/georef/georefclone.' . $LANG_TAG . '.php');
header("Content-Type: text/html; charset=".$CHARSET);
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/header.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/header.en.php');
else include_once($SERVER_ROOT . '/content/lang/header.' . $LANG_TAG . '.php');

$country = array_key_exists('country',$_REQUEST)?$_REQUEST['country']:'';
$state = array_key_exists('state',$_REQUEST)?$_REQUEST['state']:'';
$county = array_key_exists('county',$_REQUEST)?$_REQUEST['county']:'';
$locality = array_key_exists('locality',$_REQUEST)?$_REQUEST['locality']:'';
$searchType = array_key_exists('searchtype',$_POST)?$_POST['searchtype']:1;
$collType = array_key_exists('colltype',$_POST)?$_POST['colltype']:0;
$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$submitAction = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

$shouldUseMinimalMapHeader = $SHOULD_USE_MINIMAL_MAP_HEADER ?? false;
$topVal = $shouldUseMinimalMapHeader ? '1rem' : '0';

//Remove country, state, county from beginning of string
if(!$country || !$state || !$county){
	$locArr = explode(";",$locality);
	$locality = trim(array_pop($locArr));
	//if(!$country && $locArr) $country = trim(array_shift($locArr));
	//if(!$state && $locArr) $state = trim(array_shift($locArr));
	//if(!$county && $locArr) $county = trim(array_shift($locArr));
}
$locality = trim(preg_replace('/[\[\]\)\d\.\-,\s]*$/', '', $locality),'( ');

$geoManager = new OccurrenceGeorefTools();

$clones = $geoManager->getGeorefClones($locality, $country, $state, $county, $searchType, ($collType?$collid:'0'));

$latCen = 41.0;
$lngCen = -95.0;
$coorArr = explode(';', $MAPPING_BOUNDARIES);
if($coorArr && count($coorArr) == 4){
	$latCen = ($coorArr[0] + $coorArr[2])/2;
	$lngCen = ($coorArr[1] + $coorArr[3])/2;
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title>Georeference Clone Tool</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		include_once($SERVER_ROOT.'/includes/leafletMap.php');
		?>
		<script src="//www.google.com/jsapi"></script>
		<script src="//maps.googleapis.com/maps/api/js?<?= (!empty($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY != 'DEV' ? 'key=' . $GOOGLE_MAP_KEY : '') ?>"></script>
		<script type="text/javascript">
		var map;
		let lat, lng = 0;
		let clones = [];

		function info_popup(pt) {
			return `<div>${pt.lat}, ${pt.lng} (+- ${pt.err})</div>` +
				(pt.georefby ?`<br/>Georeferenced by: ${pt.georefby}`: "")+
				`<div>${pt.cnt} matching records</div>` +
				`<div>${pt.locality}<br/>` +
				`<a href="#" title="Clone Coordinates" onClick="cloneCoord(${pt.lat}, ${pt.lng}, ${pt.err})"><b>Use Coordinates</b></a></div>`;
		}

		function leafletInit() {
			var dmOptions = {
				zoom: 3,
				center: [lat, lng],
			};
			map = new LeafletMap('map_canvas', dmOptions);

			const markers = [];

			for(let point of clones) {
				const latlng = [
					parseFloat(point.lat),
					parseFloat(point.lng)
				];

				markers.push(L.marker(latlng)
					.bindPopup(info_popup(point)));
			}
			let markerGroup = L.featureGroup(markers).addTo(map.mapLayer);
			map.mapLayer.fitBounds(markerGroup.getBounds());
		}

		function googleInit() {
			var dmLatLng = new google.maps.LatLng(lat, lng);
			var dmOptions = {
				zoom: 3,
				center: dmLatLng,
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				scaleControl: true
			};
			map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);

			let activeWindow;

			let bounds = new google.maps.LatLngBounds();
			for(let point of clones) {
				const pt_lat = parseFloat(point.lat);
				const pt_lng = parseFloat(point.lng);

				const latlng = new google.maps.LatLng(pt_lat, pt_lng);
				let marker = new google.maps.Marker({
					position: latlng,
					map: map
				});

				const infowindow = new google.maps.InfoWindow({
					content: info_popup(point),
				});

				marker.addListener("click", () => {
					if(activeWindow) activeWindow.close();
					infowindow.open({
						anchor: marker,
						map,
					});
					activeWindow = infowindow;
				});

				bounds.extend(latlng);
			}
			google.maps.event.addListener(map, "click", function(event) {
				infowindow.close();
			});

			map.fitBounds(bounds);
		}

		function initialize() {
			try {
				const data = document.getElementById('service-container');
				lat = parseFloat(data.getAttribute('data-lat'));
				lng = parseFloat(data.getAttribute('data-lng'));
				clones = JSON.parse(data.getAttribute('data-clones'))
			} catch {
				alert("Couldn't load server map data")
			}

			<?php if(empty($GOOGLE_MAP_KEY)) { ?>
				leafletInit();
			<?php } else { ?>
				googleInit();
			<?php } ?>
	  }

		function cloneCoord(lat,lng,err){
			try{
				if(err == 0) err = "";
				opener.document.getElementById("decimallatitude").value = lat;
				opener.document.getElementById("decimallongitude").value = lng;
				opener.document.getElementById("coordinateuncertaintyinmeters").value = err;
				opener.document.getElementById("decimallatitude").onchange();
				opener.document.getElementById("decimallongitude").onchange();
				opener.document.getElementById("coordinateuncertaintyinmeters").onchange();
			}
			catch(myErr){
			}
			finally{
				self.close();
				return false;
			}
		}

		function verifyCloneForm(f){
			if(f.locality.value == ""){
				alert(" <?php echo isset($LANG['LOCALITY_MISSING_ERROR'])? $LANG['LOCALITY_MISSING_ERROR']: 'Locality field must have a value' ?>");
				return false
			}
			if(document.getElementById("deepsearch").checked == true){
				var locArr = f.locality.value.split(" ");
				if(locArr.length > 4){
alert("<?php echo isset($LANG['LOCALITY_INVALID_ERROR'])? $LANG['LOCALITY_INVALID_ERROR']: 'Locality field cannot contain more than 4 words while doing a Deep Search. Just enter a few keywords.' ?>");
					return false
				}
			}
			return true;
		}

		</script>
		<style type="text/css">
		.header-wrapper {
			z-index: 1000;
		}
		</style>
	</head>
	<body style="background-color:#ffffff;" onload="initialize()">
		<?php
			if($shouldUseMinimalMapHeader) include_once($SERVER_ROOT . '/includes/minimal_header_template.php');
		?>
		<!-- Data Container for Passing to Js -->
		<div id="service-container"
		data-clones="<?=htmlspecialchars(json_encode($clones))?>"
		data-lat="<?=htmlspecialchars($latCen)?>"
		data-lng="<?=htmlspecialchars($lngCen)?>"
		/>
		<!-- This is inner text! -->
		<div role="main" id="innertext" style="margin-top: <?php echo $topVal; ?>">
			<h1 class="page-heading">Georeference Clone Tool</h1>
			<fieldset style="padding:10px;">
            <legend><b>
               <?php echo isset($LANG['SEARCH_FORM'])? $LANG['SEARCH_FORM']:'Search Form' ?>
            </b></legend>
				<form name="cloneform" action="georefclone.php" method="post" onsubmit="return verifyCloneForm(this)">
					<div>
                  <?php echo isset($LANG['LOCALITY'])? $LANG['LOCALITY']:'Locality' ?>:
						<input name="locality" type="text" value="<?php echo $locality; ?>" style="width:600px" />
					</div>
					<div>
                  <input id="exactinput" name="searchtype" type="radio" value="1" <?php echo ($searchType=='1'?'checked':''); ?> />
                  <?php echo isset($LANG['EXACT_MATCH'])? $LANG['EXACT_MATCH']:'Exact Match' ?>
                  <input id="wildsearch" name="searchtype" type="radio" value="2" <?php echo ($searchType=='2'?'checked':''); ?> />
                  <?php echo isset($LANG['CONTAINS'])? $LANG['CONTAINS']:'Contains' ?>
                  <input id="deepsearch" name="searchtype" type="radio" value="3" <?php echo ($searchType=='3'?'checked':''); ?> />
                  <?php echo isset($LANG['DEEP_SEARCH'])? $LANG['DEEP_SEARCH']:'Deep Search' ?>
					</div>
					<?php if($collid):?>
						<div>
                  <input name="colltype" type="radio" value="0" <?php echo ($collType?'':'checked'); ?> />

                  <?php echo isset($LANG['SEARCH_ALL_COLS'])? $LANG['SEARCH_ALL_COLS']:'Search all collections' ?>
                  <input name="colltype" type="radio" value="1" <?php echo ($collType?'checked':''); ?> />

                  <?php echo isset($LANG['TARGET_COL_ONLY'])? $LANG['TARGET_COL_ONLY']:'Target collection only' ?>
						</div>
					<?php endif?>
					<div style="float:left;margin:5px 20px;">
						<input name="country" type="hidden" value="<?php echo $country; ?>" />
						<input name="state" type="hidden" value="<?php echo $state; ?>" />
						<input name="county" type="hidden" value="<?php echo $county; ?>" />
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="submitaction" type="submit" value="Search" />
					</div>
				</form>
			</fieldset>
         <?php if($clones):?>
         <div style="margin:3px;font-weight:bold;">
            <?php echo isset($LANG['GEO_CLONE_INSTRUCTIONS'])? $LANG['GEO_CLONE_INSTRUCTIONS']:'Click on markers to view and clone coordinates' ?>
         </div>
         <div id='map_canvas' style='width:100%; height:600px; clear:both;'></div>
         <?php else: ?>
         <div style="margin:30px">
            <h2>
               <?php echo isset($LANG['FAILED_GEO_REF'])? $LANG['FAILED_GEO_REF']:'Search failed to return specimen matches' ?>
            </h2>
         </div>
         <?php endif ?>
      </div>
   </body>
</html>
