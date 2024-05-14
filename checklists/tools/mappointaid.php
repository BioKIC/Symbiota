<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/tools/mapaids.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$formName = array_key_exists("formname",$_REQUEST)?$_REQUEST["formname"]:"";
$latName = array_key_exists("latname",$_REQUEST)?$_REQUEST["latname"]:"";
$longName = array_key_exists("longname",$_REQUEST)?$_REQUEST["longname"]:"";
$latDef = array_key_exists("latdef",$_REQUEST)?$_REQUEST["latdef"]:0;
$lngDef = array_key_exists("lngdef",$_REQUEST)?$_REQUEST["lngdef"]:0;
$zoom = array_key_exists("zoom",$_REQUEST)&&$_REQUEST["zoom"]?$_REQUEST["zoom"]:5;
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
		<h1 class="page-heading screen-reader-only">Map Point Helper</h1>
		<div style="padding:0.5rem; width: fit-content; height:fit-content">
			<div>
				<?php echo isset($LANG['MPR_INSTRUCTIONS']) ?$LANG['MPR_INSTRUCTIONS']: 'Click once to capture coordinates. Click on the submit button to transfer coordinates.' ?>
			</div>
			<div>
				<b> <?php echo isset($LANG['MPR_LAT'])? $LANG['MPR_LAT']: 'Latitude' ?>:
				</b>&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="latbox" size="13" name="lat" value="<?php echo $latDef; ?>" />&nbsp;&nbsp;&nbsp;
				<b>
					<?php echo isset($LANG['MPR_LNG'])? $LANG['MPR_LNG']: 'Longitude' ?>:
				</b> <input type="text" id="lngbox" size="13" name="lon" value="<?php echo $lngDef; ?>" />
				<input type="submit" name="addcoords" value="<?php echo isset($LANG['SUBMIT'])? $LANG['SUBMIT']: 'Submit'?>" onclick="updateParentForm();" />&nbsp;&nbsp;&nbsp;
			</div>
		</div>
		<div id='map_canvas'></div>
	</body>
</html>
