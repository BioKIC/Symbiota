<?php
include_once('../../config/symbini.php');
header('Content-Type: text/html; charset=' . $CHARSET);
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/collections/tools/mapaids.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/tools/mapaids.en.php');
else include_once($SERVER_ROOT . '/content/lang/collections/tools/mapaids.' . $LANG_TAG . '.php');

$formName = array_key_exists('formname', $_REQUEST) ? htmlspecialchars($_REQUEST['formname'], HTML_SPECIAL_CHARS_FLAGS) : '';
$latName = array_key_exists('latname', $_REQUEST) ? htmlspecialchars($_REQUEST['latname'], HTML_SPECIAL_CHARS_FLAGS) : '';
$longName = array_key_exists('longname', $_REQUEST) ? htmlspecialchars($_REQUEST['longname'], HTML_SPECIAL_CHARS_FLAGS) : '';
$latDef = array_key_exists('latdef', $_REQUEST) ? filter_var($_REQUEST['latdef'], FILTER_SANITIZE_NUMBER_FLOAT) : 0;
$lngDef = array_key_exists('lngdef', $_REQUEST) ? filter_var($_REQUEST['lngdef'], FILTER_SANITIZE_NUMBER_FLOAT) : 0;
$zoom = !empty($_REQUEST['zoom']) ? filter_var($_REQUEST['zoom'], FILTER_SANITIZE_NUMBER_INT) : 5;

if($latDef == 0 && $lngDef == 0){
	$latDef = '';
	$lngDef = '';
}

$lat = 0; $lng = 0;
if(is_numeric($latDef) && is_numeric($lngDef)){
	$lat = $latDef;
	$lng = $lngDef;
}
elseif($MAPPING_BOUNDARIES){
	$boundaryArr = explode(";",$MAPPING_BOUNDARIES);
	$lat = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
	$lng = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
}
else{
	$lat = 42.877742;
	$lng = -97.380979;
}

$shouldUseMinimalMapHeader = $SHOULD_USE_MINIMAL_MAP_HEADER ?? false;
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> - Coordinate Aid</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />

		<?php
		if(empty($GOOGLE_MAP_KEY)) {
			include_once($SERVER_ROOT.'/includes/leafletMap.php');
		} else {
			include_once($SERVER_ROOT.'/includes/googleMap.php');
		}
		?>

		<style>
         body { padding:0; margin:0 }
			html, body, #map_canvas { width:100%; height: 100%;}
		.screen-reader-only {
			position: absolute;
			left: -10000px;
		}
		<?php if($shouldUseMinimalMapHeader){ ?>
			.minimal-header-margin{
			   margin-top: 6rem;
			}
		<?php } ?>
		</style>
		<script type="text/javascript">
		var map;
		var currentMarker;

		function leafletInit() {
			var latCenter = <?php echo $lat; ?>;
			var lngCenter = <?php echo $lng; ?>;
			var latValue = opener.document.<?php echo $formName.'.'.$latName; ?>.value;
			var lngValue = opener.document.<?php echo $formName.'.'.$longName; ?>.value;

				if(latValue){
				latCenter = latValue;
				lngCenter = lngValue;
				document.getElementById("latbox").value = latValue;
				document.getElementById("lngbox").value = lngValue;
				}

				const MapOptions = {
				center: [latCenter, lngCenter],
				zoom: <?php echo $zoom?>,
            lang: "<?php echo $LANG_TAG; ?>"
				};

				map = new LeafletMap('map_canvas', MapOptions);

			let markerGroup = L.layerGroup().addTo(map.mapLayer);

			if(latValue && lngValue) {
				L.marker([latValue, lngValue]).addTo(markerGroup);
			}

			map.mapLayer.on('click', function(e) {
				markerGroup.clearLayers();
				L.marker(e.latlng).addTo(markerGroup);

				latValue = e.latlng.lat.toFixed(5);
				lonValue = e.latlng.lng.toFixed(5);
				document.getElementById("latbox").value = latValue;
				document.getElementById("lngbox").value = lonValue;
				})
		}

		function googleInit(){
			var latCenter = <?php echo $lat; ?>;
			var lngCenter = <?php echo $lng; ?>;
			var latValue = opener.document.<?php echo $formName.'.'.$latName; ?>.value;
			var lngValue = opener.document.<?php echo $formName.'.'.$longName; ?>.value;
			if(latValue){
				latCenter = latValue;
				lngCenter = lngValue;
				document.getElementById("latbox").value = latValue;
				document.getElementById("lngbox").value = lngValue;
			}
			var dmLatLng = new google.maps.LatLng(latCenter,lngCenter);
			var dmOptions = {
				zoom: <?php echo $zoom; ?>,
				center: dmLatLng,
				mapTypeId: google.maps.MapTypeId.TERRAIN
			};
			map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);
			if(latValue && lngValue){
				var mLatLng = new google.maps.LatLng(latValue,lngValue);
				var marker = new google.maps.Marker({
					position: mLatLng,
					map: map
				});
				currentMarker = marker;
			}

			google.maps.event.addListener(map, 'click', function(event) {
				mapZoom = map.getZoom();
				startLocation = event.latLng;
				setTimeout("placeMarker()", 500);
			});
		}
		function initialize() {
			<?php if(empty($GOOGLE_MAP_KEY)): ?>
				leafletInit();
			<?php else:?>
				googleInit();
			<?php endif ?>
		}

		function placeMarker() {
		if(currentMarker) currentMarker.setMap();
			if(mapZoom == map.getZoom()){
				var marker = new google.maps.Marker({
					position: startLocation,
					map: map
				});
				currentMarker = marker;

				var latValue = startLocation.lat();
				var lonValue = startLocation.lng();
				latValue = latValue.toFixed(5);
				lonValue = lonValue.toFixed(5);
				document.getElementById("latbox").value = latValue;
				document.getElementById("lngbox").value = lonValue;
			}
		}

		function updateParentForm() {
			try{
				var latObj = opener.document.<?php echo $formName . '.' . $latName; ?>;
				var lngObj = opener.document.<?php echo $formName . '.' . $longName; ?>;
				latObj.value = document.getElementById("latbox").value;
				lngObj.value = document.getElementById("lngbox").value;
				lngObj.onchange();
			}
			catch(myErr){
				//alert("Unable to transfer data. Please let an administrator know.");
			}
			self.close();
			return false;
		}
		</script>
	</head>
	<body style="display:flex; flex-direction: column; background-color:#ffffff;" onload="initialize()">
		<?php
		if($shouldUseMinimalMapHeader) include_once($SERVER_ROOT . '/includes/minimalheader.php');
		?>
		<h1 class="page-heading screen-reader-only"><?= $LANG['MAP_POINT_HELPER']; ?></h1>
		<div style="padding:0.5rem; width: fit-content; height:fit-content" class="minimal-header-margin">
			<div>
				<?= $LANG['MPR_INSTRUCTIONS'] ?>
			</div>
			<div>
				<b> <?= $LANG['MPR_LAT'] ?>:
				</b>&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="latbox" size="13" name="lat" value="<?= $latDef; ?>" />&nbsp;&nbsp;&nbsp;
				<b>
					<?= $LANG['MPR_LNG'] ?>:
				</b> <input type="text" id="lngbox" size="13" name="lon" value="<?= $lngDef; ?>" />
				<input type="submit" name="addcoords" value="<?= $LANG['SUBMIT'] ?>" onclick="updateParentForm();" />&nbsp;&nbsp;&nbsp;
			</div>
		</div>
		<div id='map_canvas'></div>
	</body>
</html>
