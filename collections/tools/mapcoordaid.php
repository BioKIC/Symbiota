<?php
   include_once('../../config/symbini.php');
   include_once($SERVER_ROOT.'/content/lang/collections/tools/mapaids.'.$LANG_TAG.'.php');
   include_once($SERVER_ROOT.'/classes/ChecklistAdmin.php');
header("Content-Type: text/html; charset=".$CHARSET);

$clid = array_key_exists("clid",$_REQUEST) && is_numeric($_REQUEST["clid"])? $_REQUEST["clid"]:0;
$formSubmit = array_key_exists("formsubmit",$_POST)?$_POST["formsubmit"]:0;
$latDef = array_key_exists("latdef",$_REQUEST)?$_REQUEST["latdef"]:'';
$lngDef = array_key_exists("lngdef",$_REQUEST)?$_REQUEST["lngdef"]:'';
$zoom = array_key_exists("zoom",$_REQUEST)&&is_numeric($_REQUEST["zoom"])?$_REQUEST["zoom"]:5;
$mapMode = array_key_exists("mapmode",$_REQUEST)? htmlspecialchars($_REQUEST["mapmode"]):'';
$mapModeStrict = array_key_exists("map_mode_strict",$_REQUEST)? true:false;
$wktInputId = array_key_exists("wkt_input_id", $_REQUEST)? htmlspecialchars($_REQUEST["wkt_input_id"]):"footprintwkt";
$outputType= array_key_exists("geoJson", $_REQUEST)?"geoJson":"wkt";

$clManager = new ChecklistAdmin();
$clManager->setClid($clid);

if($formSubmit){
	if($formSubmit == 'save'){
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
		<h1 class="page-heading" style="margin-left:5px;">Taxon Map</h1>
		<div style="float:right;margin-top:5px;margin-bottom:5px;margin-right:15px;margin-left:5px;">
			<button name="closebutton" type="button" onclick="self.close()">
				<?php echo isset($LANG['SAVE_N_CLOSE'])? $LANG['SAVE_N_CLOSE'] :'Save and Close'?>
			</button>
			<?php echo isset($LANG['COORD_AID_HELP_TEXT'])? $LANG['COORD_AID_HELP_TEXT'] :'Click the map to start drawing or select from the shape controls to draw bounds of that shape'?>
		</div>
		<div id="helptext">
		</div>
		<div id="map"></div>
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
		const wktInputId = "<?= $wktInputId?>";
		const polyOutputType = "<?= $outputType ?>";

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
			setField(wktInputId, poly_output);
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

			setField(wktInputId, "");

			setField("upperlat", "");
			setField("bottomlat", "");
			setField("leftlong", "");
			setField("rightlong", "");

			//If Active Shape is null bail
			if(!activeShape)
			return;

			switch(activeShape.type) {
				case "polygon":
					if(polyOutputType === "geoJson") {
						setPolygon( JSON.stringify({
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
					if(polyOutputType === "geoJson") {
						const geoJsonStr = getField(wktInputId);
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
						const origFootprintWkt = getField(wktInputId);
						try {
							const polyPoints = parseWkt(origFootprintWkt);
							if(polyPoints) {
								return { type: "polygon", latlngs: polyPoints, wkt: getField(wktInputId)};
							}
						} catch(e) {
							alert(e.message);
							opener.document.getElementById(wktInputId).value = origFootprintWkt;
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
		let formShape = loadShape("<?php echo $mapMode?>");
		let mapModeStrict = <?php echo $mapModeStrict? "true": "false"?>;
		function leafletInit() {
			const MapOptions = {
				center: [<?php echo $latCenter?>, <?php echo $lngCenter?>],
			zoom: <?php echo $zoom?>,
				lang: "<?php echo $LANG_TAG; ?>"
			};

			let map = new LeafletMap('map', MapOptions );
			let mode = "<?php echo $mapMode?>";

			map.enableDrawing({
				polyline: false,
				mode: "<?php echo $mapMode?>",
				map_mode_strict: mapModeStrict,
				circlemarker: false,
				marker: false,
				drawColor: {opacity: 0.85, fillOpacity: 0.55, color: '#000' }
				}, setShapeToSearchForm);

			if(formShape) {
				map.drawShape(formShape);
			}
		}
		function googleInit() {
			const MapOptions= {
				zoom: <?php echo $zoom; ?>,
				center: new google.maps.LatLng(<?php echo $latCenter . ',' . $lngCenter; ?>),
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				scaleControl: true
			};

			let map = new GoogleMap('map', MapOptions)
			map.enableDrawing({
				mode: "<?php echo $mapMode?>",
				map_mode_strict: mapModeStrict
				}, setShapeToSearchForm);

			if(formShape) 
			map.drawShape(formShape, setShapeToSearchForm)
		}

		<?php if(empty($GOOGLE_MAP_KEY)): ?> 
			leafletInit();
		<?php else:?> 
			googleInit();
		<?php endif ?>
		</script>
	</body>
</html>
