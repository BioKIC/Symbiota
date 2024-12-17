<?php
   include_once('../../config/symbini.php');
   if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/tools/mapaids.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/tools/mapaids.' . $LANG_TAG . '.php');
	else include_once($SERVER_ROOT . '/content/lang/collections/tools/mapaids.en.php');
   include_once($SERVER_ROOT.'/classes/ChecklistAdmin.php');
header("Content-Type: text/html; charset=".$CHARSET);

$clid = array_key_exists("clid",$_REQUEST) && is_numeric($_REQUEST["clid"])? $_REQUEST["clid"]:0;
$formSubmit = array_key_exists("formsubmit",$_POST)?$_POST["formsubmit"]:0;
$latDef = array_key_exists("latdef",$_REQUEST)?$_REQUEST["latdef"]:'';
$lngDef = array_key_exists("lngdef",$_REQUEST)?$_REQUEST["lngdef"]:'';
$zoom = array_key_exists("zoom",$_REQUEST)&&is_numeric($_REQUEST["zoom"])?$_REQUEST["zoom"]:5;
$mapMode = array_key_exists("map_mode",$_REQUEST)? htmlspecialchars($_REQUEST["map_mode"]):'';
$mapModeStrict = array_key_exists("map_mode_strict",$_REQUEST) && is_bool(boolval($_REQUEST["map_mode_strict"]))? boolval($_REQUEST["map_mode_strict"]):false;
$polygonInputId = array_key_exists("polygon_input_id", $_REQUEST)? htmlspecialchars($_REQUEST["polygon_input_id"]):"footprintwkt";
$outputType= array_key_exists("polygon_text_type", $_REQUEST)? htmlspecialchars($_REQUEST["polygon_text_type"]):"wkt";

if($formSubmit){
	if($formSubmit == 'save'){
		$clManager = new ChecklistAdmin();
		$clManager->setClid($clid);
		$clManager->savePolygon($_POST['footprintwkt']);
		$formSubmit = "exit";
	}
}

if($latDef == 0 && $lngDef == 0){
	$latDef = '';
	$lngDef = '';
}

$latCenter = 0; $lngCenter = 0;
if(is_numeric($latDef) && is_numeric($lngDef)){
	$latCenter = $latDef;
	$lngCenter = $lngDef;
	$zoom = 12;
}
elseif($MAPPING_BOUNDARIES){
	$boundaryArr = explode(";",$MAPPING_BOUNDARIES);
	$latCenter = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
	$lngCenter = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
}
else{
	$latCenter = 42.877742;
	$lngCenter = -97.380979;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> - Taxon Map</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		if(empty($GOOGLE_MAP_KEY)) {
			include_once($SERVER_ROOT.'/includes/leafletMap.php');
		} else {
			include_once($SERVER_ROOT.'/includes/googleMap.php');
		}
		?>
		<meta charset="utf-8">
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/wktpolygontools.js" type="text/javascript"></script>
		<style>
			html, body, #map { width:100%; height: 100%; }
		</style>
	</head>
	<body style="background-color:#ffffff; display:flex; flex-direction:column;">
		<h1 class="page-heading screen-reader-only" style="margin-left:5px;"><?php echo $LANG['TAXON_MAP']; ?></h1>
		<div style="float:right;margin-top:5px;margin-bottom:5px;margin-right:15px;margin-left:5px;">
			<button name="closebutton" type="button" onclick="saveCoordAid()">
				<?php echo isset($LANG['SAVE_N_CLOSE'])? $LANG['SAVE_N_CLOSE'] :'Save and Close'?>
			</button>
			<?php echo isset($LANG['COORD_AID_HELP_TEXT'])? $LANG['COORD_AID_HELP_TEXT'] :'Click the map to start drawing or select from the shape controls to draw bounds of that shape'?>
		</div>
		<div id="helptext"></div>
		<div id="map"></div>
		<div 
			id="service-container" 
			data-zoom="<?= htmlspecialchars($zoom) ?>" 
			data-lat-center="<?= htmlspecialchars($latCenter) ?>" 
			data-lng-center="<?= htmlspecialchars($lngCenter) ?>" 
			data-map-mode="<?= htmlspecialchars($mapMode) ?>" 
			data-map-mode-strict="<?= htmlspecialchars($mapModeStrict) ?>"
			data-footprint-type="<?= htmlspecialchars($outputType) ?>"
			data-footprint-id="<?= htmlspecialchars($polygonInputId) ?>"
		></div>
		<script type="text/javascript">

		/* Type Definitions
		*
		* lat: -90 < float < 90;
		* lng: -180 < float < 180;
		*
		*/

		/* Shape Defintions:
		*
		* Polygon {
		*    type: polygon, 
		*    latlngs: [[lat, lng]...],
		*    wkt: String (Wkt format),
		* }
		*
		* Rectangle {
		*    type: "rectangle",
		*    upperLat: lat,
		*    lowerLat: lat,
		*    rightLng: lng,
		*    leftLng: lng,
		* }
		*
		* Circle { 
		*    type: "circle"
		*    radius: float,
		*    center [lat, lng]
		* }
		*/

		const MILEStoKM = 1.60934;
		const KMtoM = 1000; 
		const SIG_FIGS = 6;
		const data = document.getElementById("service-container");
		const mapMode = data.getAttribute('data-map-mode');
		const mapModeStrict = data.getAttribute('data-map-mode-strict');
		const zoom = data.getAttribute('data-zoom');
		const footprintId = data.getAttribute('data-footprint-id');
		const footprintType = data.getAttribute('data-footprint-type');
		const latCenter = data.getAttribute('data-lat-center');
		const lngCenter = data.getAttribute('data-lng-center');
		const lang = "<?= $LANG_TAG; ?>";

		const fieldBuffer = {};

		function commitFieldBuffer(buffer = {}) {
			for(id in buffer) {
				const elem = opener.document.getElementById(id);
				if(elem) elem.value = buffer[id];
			}
		}

		function saveCoordAid() {
			const leaflet_save = document.querySelector(".leaflet-draw-actions li a[title='Save changes']");
			if(leaflet_save) leaflet_save.click();
			self.close();
		}

		const setField = (id, v) => {
			var elem = opener.document.getElementById(id);
			if(elem) {
				elem.value = v;
				var event = new Event('change');
				elem.dispatchEvent(event);
			}
		};

		const getField = (id) => {
			var elem = opener.document.getElementById(id);
			return elem? elem.value: null;
		};

		function isNumeric(n) {
			return !isNaN(parseFloat(n)) && isFinite(n);
		}

		function setRectangle(upperLat, lowerLat, leftLng, rightLng) {

			setField("upperlat_NS", upperLat > 0 ? "N": "S");
			setField("upperlat", Math.abs(upperLat).toFixed(SIG_FIGS));

			setField("bottomlat_NS", lowerLat > 0 ? "N": "S");
			setField("bottomlat", Math.abs(lowerLat).toFixed(SIG_FIGS));

			setField("leftlong_EW", leftLng > 0 ? "E": "W");
			setField("leftlong", Math.abs(leftLng).toFixed(SIG_FIGS));

			setField("rightlong_EW", rightLng> 0 ? "E": "W");
			setField("rightlong", Math.abs(rightLng).toFixed(SIG_FIGS));
		}

		function setCircle(radius, center_lat, center_lng) {
			//Assuming Radius is always in meters
			setField("radius", ((isNaN(radius)? radius: Math.abs(radius)) / KMtoM).toFixed(SIG_FIGS));
			setField("radiusunits", "km");

			setField("pointlat_NS", center_lat > 0? "N": "S");
			setField("pointlat", Math.abs(center_lat).toFixed(SIG_FIGS));

			setField("pointlong_EW", center_lng > 0? "E": "W");
			setField("pointlong", Math.abs(center_lng).toFixed(SIG_FIGS));
		}

		function setPolygon(poly_output) {
			setField(footprintId, poly_output);
		}

		/* setShapeToSearchForm: 
		*
		* Sets Shape data to search form.
		*
		* activeShape: Shape Type (See Def at top of script)
		* 
		*/
		function setShapeToSearchForm(activeShape) {
			//Clear Form
			setField("pointlat", "");
			setField("pointlong", "");
			setField("radius", "");
			setField("radiusunits", "");

			setField(footprintId, "");

			setField("upperlat", "");
			setField("bottomlat", "");
			setField("leftlong", "");
			setField("rightlong", "");

			//If Active Shape is null bail
			if(!activeShape)
			return;

			switch(activeShape.type) {
				case "polygon":
					if(footprintType === "geoJson") {
						setPolygon(JSON.stringify({
							"type": "Feature",
							"properties": {},
							"geometry": {
								"type": "Polygon",
								"coordinates": [activeShape.latlngs.map(([lat, lng]) => [lng, lat])]
							},
						}));
					} else {
						setPolygon(activeShape.wkt);
					}
					break;
				case "rectangle":
					const rec = activeShape;
					setRectangle(rec.upperLat, rec.lowerLat, rec.leftLng, rec.rightLng);
					break;
				case "circle":
					const circ = activeShape; 
					setCircle(circ.radius, circ.center.lat, circ.center.lng);
					break;
			}
		}

		/* LoadShape Reads Coordinates from Form: 
		*
		* mapMode: enum ("polygon", "rectangle", "circle")
		*
		* Returns A Shape (See top of script):
		*/
		function loadShape(mapMode) {
			switch(mapMode) {
				case "polygon":
					const geoJsonStr = getField(footprintId);
					if(footprintType && footprintType.toLowerCase() === "geojson" && geoJsonStr) {
						try {
							const geoJson = JSON.parse(geoJsonStr);
							return { 
								type: "geoJSON", 
								geoJSON: geoJson
							};
						} catch(e) {
							alert(e.message);
						}
					} else {
						const origFootprint = getField(footprintId);
						try {
							const polyPoints = parseWkt(origFootprint);
							if(polyPoints) {
								return { type: "polygon", latlngs: polyPoints, wkt: getField(footprintId)};
							}
						} catch(e) {
							alert(e.message);
							opener.document.getElementById(footprintId).value = origFootprint;
						}
					}
				break;
				case "rectangle":
					const upperLat = getField("upperlat");
					const lowerLat= getField("bottomlat");
					const leftLng = getField("leftlong");
					const rightLng = getField("rightlong");

					if(isNumeric(upperLat) && isNumeric(lowerLat) && isNumeric(leftLng) && isNumeric(rightLng)) {
						return {
							type: "rectangle",
							upperLat: upperLat * (getField("upperlat_NS") === "N"? 1: -1),
							rightLng: rightLng * (getField("rightlong_EW") === "E"? 1: -1),

							lowerLat: lowerLat * (getField("bottomlat_NS") === "N"? 1: -1),
							leftLng: leftLng * (getField("leftlong_EW") === "E"? 1: -1),
						}
					}
				break;
				case "circle":
					const radius = getField("radius");
					const pointlat = getField("pointlat");
					const pointlng = getField("pointlong");
					const radUnits = getField("radiusunits", "");

					if(isNumeric(radius) && isNumeric(pointlng) && isNumeric(pointlng)) {
						return {
							type: "circle",
							radius: (radUnits === "mi"? radius * MILEStoKM: parseFloat(radius)) * KMtoM,
							latlng: [
								pointlat * (getField("pointlat_NS") === "N"? 1: -1), 
								pointlng * (getField("pointlong_EW") === "E"? 1: -1)
							]
						}
					}
				break;
				default:
					alert(`No Settings for Map Mode: ${mapMode}`)
					return false;
				break;
			} 
		}

		const formShape = loadShape(mapMode);
		function leafletInit() {
			const MapOptions = {
				center: [latCenter, lngCenter],
				zoom,
				lang
			};

			let map = new LeafletMap('map', MapOptions );

			map.enableDrawing({
				polyline: false,
				mode: mapMode,
				map_mode_strict: mapModeStrict,
				circlemarker: false,
				marker: false,
				drawColor: {opacity: 0.85, fillOpacity: 0.55, color: '#000' }
				}, setShapeToSearchForm);

			map.mapLayer.on("draw:edited", saveCoordAid);

			if(formShape) {
				map.drawShape(formShape);
			}
		}

		function googleInit() {
			const MapOptions= {
				center: new google.maps.LatLng(latCenter, lngCenter),
				zoom,
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				scaleControl: true
			};

			let map = new GoogleMap('map', MapOptions)
			map.enableDrawing({
				mode: mapMode,
				map_mode_strict: mapModeStrict
			}, setShapeToSearchForm);

			if(formShape)  {
				map.drawShape(formShape, setShapeToSearchForm)
			}
		}

		<?php if(empty($GOOGLE_MAP_KEY)): ?> 
			leafletInit();
		<?php else:?> 
			googleInit();
		<?php endif ?>
		</script>
	</body>
</html>
