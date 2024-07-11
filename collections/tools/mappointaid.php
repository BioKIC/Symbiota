<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);
include_once($SERVER_ROOT.'/content/lang/collections/tools/mapaids.'.$LANG_TAG.'.php');
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/header.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/header.en.php');
else include_once($SERVER_ROOT . '/content/lang/header.' . $LANG_TAG . '.php');

if($MAPPING_BOUNDARIES){
	$boundaryArr = explode(";",$MAPPING_BOUNDARIES);
	$latCenter = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
	$lngCenter = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
} else{
	$latCenter = 42.877742;
	$lngCenter = -97.380979;
}

$errMode = array_key_exists("errmode",$_REQUEST)?$_REQUEST["errmode"]:1;
$shouldUseMinimalMapHeader = $SHOULD_USE_MINIMAL_MAP_HEADER ?? false;
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
	<title><?php echo $DEFAULT_TITLE; ?> - Point-Radius Aid</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />

		<?php
		include_once($SERVER_ROOT.'/includes/leafletMap.php');
		include_once($SERVER_ROOT.'/includes/googleMap.php');
		?>

		<script type="text/javascript">
		var map;

		//Global temp Values
		let errRadius = 0;
		let latlng;

		//Map Center
		let latCenter;
		let lngCenter;

		//Inputs
		let radiusInput;
		let latInput;
		let lngInput;

		const checkCoord = (coord, range) => {
			return !isNaN(coord) && coord <= range && coord >= -range;
		}

		//Function for reading reading lat lng inputs
		function getLatLng() {
			const pos = [
				parseFloat(document.getElementById("latbox").value),
				parseFloat(document.getElementById("lngbox").value)
			];

			if(checkCoord(pos[0], 90) && checkCoord(pos[1], 180)) {
				return pos;
			}

			return false;
		}
		//Funciton for setting lat lng inputs
		function setLatLngForm(lat, lng) {
			document.getElementById("latbox").value = parseFloat(lat).toFixed(5);
			document.getElementById("lngbox").value = parseFloat(lng).toFixed(5);
		}

		function clearForm() {
			document.getElementById("latbox").value = "";
			document.getElementById("lngbox").value = "";
			document.getElementById("errRadius").value = "";
		}

		//Function For Submission
		function SubmitCoordinates(lat, lng) {
			opener.document.getElementById("decimallatitude").value = lat;
			opener.document.getElementById("decimallongitude").value = lng;
		}

		function errRadiusChanged(e) {
			try {
				errRadius = parseFloat(e.value);
			} catch {
				errRadius = 0;
			}
		}

		function getErrorRadius() {
			if(opener.document.getElementById("coordinateuncertaintyinmeters") && opener.document.getElementById("coordinateuncertaintyinmeters").value){
				errRadius = opener.document.getElementById("coordinateuncertaintyinmeters").value;
				document.getElementById("errRadius").value = opener.document.getElementById("coordinateuncertaintyinmeters").value;
			}
		}

		function leafletInit() {
			//Setup Map Canvas
			map = new LeafletMap('map_canvas', {
				center: [latCenter, lngCenter], 
				zoom: 15,
				lang: "<?php echo $LANG_TAG; ?>"
			});

			var drawnItems = new L.FeatureGroup();
			map.mapLayer.addLayer(drawnItems);

			var drawControl = new L.Control.Draw({
				draw: {
					...map.DEFAULT_DRAW_OPTIONS,
					marker: true,
					polygon: false,
					circle: false,
					rectangle: false,
				},
				edit: {
					featureGroup: drawnItems,
				}
			});

			map.mapLayer.addControl(drawControl);
			const markerControl = document.querySelector(".leaflet-draw-draw-marker");

			let marker;
			let circ;

			let deleteOn = false;
			map.mapLayer.on(L.Draw.Event.DELETESTART, () => deleteOn = true)
			map.mapLayer.on(L.Draw.Event.DELETESTOP, () => deleteOn = false)

			let editOn = false;
			map.mapLayer.on(L.Draw.Event.EDITSTART, () => editOn = true)
			map.mapLayer.on(L.Draw.Event.EDITSTOP, () => editOn = false)

			function moveCircle(c, pos) {
				c.setLatLng(pos);
				if(editOn) {
					circ.editing.disable();
					circ.editing.enable();
				}
			}

			function createMarker(lat, lng)  {
				drawnItems.clearLayers();
				errRadius = parseFloat(document.getElementById("errRadius").value);

				latlng = [lat,lng];

				setLatLngForm(lat, lng);

				circ = errRadius && errRadius > 0?
					L.circle(latlng, errRadius): 
					false;
				marker = L.marker(latlng);

				function enableEdit() {
					try {
						//Very Jank and all the other ways current are also Jank 
						drawControl._toolbars.edit._modes.edit.button.click()
					} catch(e) {
						console.log("Failed to enable edit")
					}
				}

				function handleMarkerClick() {
					if(deleteOn && circ) drawnItems.removeLayer(circ);
					else enableEdit();
				}

				function handleCircleClick() {
					if(deleteOn) drawnItems.removeLayer(marker);
					else enableEdit();
				}

				function addCircleEvents(circle) {
					circle
						.on('drag', e=> {
							const pos = e.target.getLatLng()
							setLatLngForm(pos.lat, pos.lng);
							marker.setLatLng(pos);
						})
						.on('click', handleCircleClick)
						.setStyle(map.DEFAULT_SHAPE_OPTIONS)
					.addTo(drawnItems);
				}

				marker
					.on('click', handleMarkerClick)
					.on('drag', e => {
						const pos = e.target.getLatLng();
						setLatLngForm(pos.lat, pos.lng);
						if(circ) {
							moveCircle(circ, pos);
						}
					})
					.addTo(drawnItems)
				if(circ) {
					addCircleEvents(circ);

					map.mapLayer.on('draw:editresize', e => {
						document.getElementById("errRadius").value = e.layer._mRadius.toFixed(5);
					})

					map.mapLayer.fitBounds(circ.getBounds());
				} else {
					map.mapLayer.setView(latlng, map.mapLayer.getZoom());
				}

				map.mapLayer
					.on('draw:deleted', e => { 
						errRadius = 0;
						clearForm();
					})
					.on('draw:deletestop', e => { 
						const hasCircle = circ && drawnItems.hasLayer(circ);
						const hasMarker = drawnItems.hasLayer(marker);
						//Add Back marker or Circle if change Reverted and were present
						if(hasCircle && !hasMarker) {
							drawnItems.addLayer(marker);
						} else if(hasMarker && circ && !hasCircle) {
							drawnItems.addLayer(circ);
						}
					})
					.on('draw:edited', function(e) {
						latlng = getLatLng();
						errRadius = parseFloat(document.getElementById("errRadius").value);
					})
					.on('draw:editstop', e => {
						setLatLngForm(latlng[0], latlng[1]);
						document.getElementById("errRadius").value = errRadius;
					})
				if(radiusInput) {
					radiusInput.addEventListener("change", event => {
						const radius = parseFloat(event.target.value);

						if(!radius && circ) {
							drawnItems.removeLayer(circ);
						} else if(circ) {
							circ.setRadius(radius);
							if(editOn) {
								circ.editing.disable();
								circ.editing.enable();
							}
							map.mapLayer.fitBounds(circ.getBounds())
						} else if(radius) {
							if(!editOn) errRadius = radius;
							circ = L.circle(latlng, radius); 
							addCircleEvents(circ);
							map.mapLayer.fitBounds(circ.getBounds())
						} 

					});
				}

				const onFormChange = () => {
					const pos = getLatLng();
					marker.setLatLng(pos);
					if(circ) moveCircle(circ, pos);
				};

				latInput.addEventListener("change", onFormChange);
				lngInput.addEventListener("change", onFormChange);
			} 
			onFormChange = (event) => { 
				if(!marker) { 
					const pos = getLatLng();
					createMarker();
				} 
			}
			latInput.addEventListener("change", onFormChange);
			lngInput.addEventListener("change", onFormChange);

			map.mapLayer.on('draw:created', function(e) {
				//Clear between Draws
				if(e.layerType === "marker") {
					const lat = e.layer._latlng.lat;
					const lng = e.layer._latlng.lng;
					createMarker(lat, lng)

				} 
			})

			//Draw marker if one exists
			if(latlng) {
				createMarker(latlng[0], latlng[1]);
			} else if(markerControl) {
				markerControl.click();
			}
		}

		function googleInit() {
			//Setup Map Canvas
			map = new GoogleMap('map_canvas', {
				center: new google.maps.LatLng(latCenter, lngCenter), 
				zoom: 7
			});

			let radiusInput = document.getElementById("errRadius");

			let polyOptions = {
				strokeWeight: 0,
				fillOpacity: 0.45,
				editable: false,
				draggable: false
			};

			let drawingManager = new google.maps.drawing.DrawingManager({
				drawingMode: null,
				drawingControl: false,
				circleOptions: polyOptions
			});

			drawingManager.setMap(map.mapLayer);

			let marker;
			let errCircle;

			function createMarker(lat, lng) {
				latlng = [lat, lng];
				setLatLngForm(lat, lng);
				//Clears Last Marker Off Map
				if(marker) marker.setMap();
				marker = new google.maps.Marker({
					position: new google.maps.LatLng(lat, lng),
					draggable: true,
					map: map.mapLayer
				});

				marker.addListener('position_changed', function(){
					let newLatLng = (marker.getPosition());
					setLatLngForm(newLatLng.lat(), newLatLng.lng());
				});
				drawError();
			}

			function drawError() {
				if(!marker || isNaN(errRadius) || errRadius <= 0) return;
				if(errCircle) errCircle.setMap();

				errCircle = new google.maps.Circle({
					center: new google.maps.LatLng(latlng[0], latlng[1]),
					//Convert to Meters
					radius: errRadius,
					strokeWeight: 0,
					fillOpacity: 0.45,
					editable: true,
					draggable: true,
					map: map.mapLayer
				});
				google.maps.event.addListener(errCircle, 'radius_changed', function(){
					var radius = (errCircle.getRadius());
					document.getElementById("errRadius").value = radius.toFixed(5);
				});

				errCircle.bindTo('center', marker, 'position');
			}


			onFormChange = (event) => { 
				errRadius = parseFloat(event.target.value);
				const pos = getLatLng();
				if(pos) createMarker(pos[0], pos[1]);
			}
			if(radiusInput) radiusInput.addEventListener("change", onFormChange);
			latInput.addEventListener("change", onFormChange);
			lngInput.addEventListener("change", onFormChange);

			//Draw marker if one exists
		if(latlng) {
				createMarker(latlng[0], latlng[1]);
				map.mapLayer.setCenter(marker.getPosition());
			}
			//Setup OnClick Listeners
			google.maps.event.addListener(map.mapLayer, 'click', function(e) {

				createMarker(e.latLng.lat(), e.latLng.lng());
			if(errRadius) {
					drawError();
				}
			}) 
		}

		function initialize() {
			let lat = opener.document.getElementById("decimallatitude").value;
			let lng = opener.document.getElementById("decimallongitude").value;

			const data = document.getElementById('service-container');
			latCenter = parseFloat(data.getAttribute('data-lat'));
			lngCenter = parseFloat(data.getAttribute('data-lng'));

			radiusInput = document.getElementById("errRadius");
			latInput = document.getElementById("latbox");
			lngInput = document.getElementById("lngbox");
			getErrorRadius();

			if(lat && lng) {
				if(checkCoord(lat, 90) && checkCoord(lng, 180)) {
					setLatLngForm(lat, lng);
					latlng = [lat, lng];
				} else {
					alert(`Error: Not Coordinates lat: ${lat}, lng: ${lng}`);
				}
			} 
			<?php if(empty($GOOGLE_MAP_KEY)) { ?> 
			leafletInit();
			<?php } else { ?> 
			googleInit();
			<?php } ?>
		}

		function updateParentForm(f) {
			opener.document.getElementById("decimallatitude").value = f.latbox.value;
			opener.document.getElementById("decimallongitude").value = f.lngbox.value;
			try{
				if(opener.document.getElementById("coordinateuncertaintyinmeters")){
					opener.document.getElementById("coordinateuncertaintyinmeters").value = f.errRadius.value;
					opener.document.getElementById("coordinateuncertaintyinmeters").onchange();
				}
				if(opener.document.getElementById("geodeticdatum")){
					opener.document.getElementById("geodeticdatum").value = "WGS84";
					opener.document.getElementById("geodeticdatum").onchange();
				}
				opener.document.getElementById("decimallatitude").onchange();
				opener.document.getElementById("decimallongitude").onchange();
				opener.document.getElementById("saveEditsButton").disabled = false;
			}
			catch(myErr){
				//alert("Unable to trigger onchange");
			}
			finally{
				self.close();
				return false;
			}
		}
		</script>
		<style>
         body { padding:0; margin:0 }
			html, body, #map_canvas { width:100%; height: 100%;}
         .screen-reader-only{ 
            position: absolute;
            left: -10000px;
         }
		 <?php if($shouldUseMinimalMapHeader){ ?>
			.minimal-header-margin{
			   margin-top: 6rem;
			}
		<?php } ?>
      </style>
	</head>
	<body style="display:flex; flex-direction: column;background-color:#ffffff;" onload="initialize()">
		 <?php
			if($shouldUseMinimalMapHeader) include_once($SERVER_ROOT . '/includes/minimal_header_template.php');
		?>
		<h1 class="page-heading screen-reader-only">Point-Radius Aid</h1>
		<div
			id="service-container" 
			class="service-container" 
			data-lat="<?= htmlspecialchars($latCenter)?>"
			data-lng="<?= htmlspecialchars($lngCenter)?>"
			>
		</div>
		<form class="minimal-header-margin" style="padding:0.5rem" name="coordform" action="" method="post" onsubmit="return false">
			<div style="float:right;">
				<button name="addcoords" type="button" onclick="updateParentForm(this.form);">
					<b><?php echo isset($LANG['SUBMIT'])? $LANG['SUBMIT']: 'Submit' ?></b> 
				</button><br/>
			</div>
			<div style="margin:3px 20px 3px 0px;">
			<?php echo isset($LANG['MPR_INSTRUCTIONS']) ?$LANG['MPR_INSTRUCTIONS']: 'Click once to capture coordinates. Click on the submit coordinate button to transfer coordinates.' ?>
			<?php if($errMode) echo isset($LANG['MPR_UNCERTAINTY_INSTRUCTIONS']) ?$LANG['MPR_UNCERTAINTY_INSTRUCTIONS']: 'Enter uncertainty to create an error radius circle around the marker. '?>
			</div>
			<div style="margin-right:10px;">
				<b><?php echo isset($LANG['MPR_LAT'])? $LANG['MPR_LAT']: 'Latitude' ?>:</b> 
				<input type="text" id="latbox" name="lat" style="width:100px" />
				<b><?php echo isset($LANG['MPR_LNG'])? $LANG['MPR_LNG']: 'Longitude' ?>:</b> 
				<input type="text" id="lngbox" name="lon" style="width:100px" />
				<?php if($errMode):?>
				<b>
				<?php echo isset($LANG['UNCERTAINTY_METERS']) ?$LANG['UNCERTAINTY_METERS']: 'Uncertainty in Meters'?>:
				</b>
				<input type="text" id="errRadius" name="errRadius" size="13" />
				<?php endif?>
			</div>
		</form>
		<div id='map_canvas'></div>
	</body>
</html>
