<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/tools/mapaids.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/ChecklistAdmin.php');
header('Content-Type: text/html; charset='.$CHARSET);

$clid = array_key_exists('clid',$_REQUEST)?$_REQUEST['clid']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';
$latDef = array_key_exists('latdef',$_REQUEST)?$_REQUEST['latdef']:'';
$lngDef = array_key_exists('lngdef',$_REQUEST)?$_REQUEST['lngdef']:'';
$zoom = array_key_exists('zoom',$_REQUEST)&&$_REQUEST['zoom']?$_REQUEST['zoom']:5;

//Sanitation
if(!is_numeric($clid)) $clid = 0;
if(!is_numeric($latDef)) $latDef = 0;
if(!is_numeric($lngDef)) $lngDef = 0;
if(!is_numeric($zoom)) $zoom = 0;

$clManager = new ChecklistAdmin();
$clManager->setClid($clid);

if($formSubmit){
	if($formSubmit == 'save'){
		$clManager->savePolygon($_POST['footprintwkt']);
		$formSubmit = 'exit';
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
	$boundaryArr = explode(';',$MAPPING_BOUNDARIES);
	$latCenter = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
	$lngCenter = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
}
else{
	$latCenter = 42.877742;
	$lngCenter = -97.380979;
}
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> - Coordinate Aid</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<script src="//maps.googleapis.com/maps/api/js?<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'key='.$GOOGLE_MAP_KEY:''); ?>&libraries=drawing&v=weekly"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/wktpolygontools.js?ver=5" type="text/javascript"></script>
		<script type="text/javascript">
			var map;
			var polygons = [];
			var polyBounds = null;
			var selectedPolygon = null;
			var drawingManager = null;
			<?php
			if($formSubmit == 'exit') echo 'window.close();';
			?>
			function initialize(){
				if(opener.document.getElementById("footprintwkt") && opener.document.getElementById("footprintwkt").value != ""){
					if(document.getElementById('footprintwkt').value == ""){
						document.getElementById('footprintwkt').value = opener.document.getElementById("footprintwkt").value;
					}
				}
				var dmLatLng = new google.maps.LatLng(<?php echo $latCenter.','.$lngCenter; ?>);

				map = new google.maps.Map(document.getElementById("map_canvas"), {
					zoom: <?php echo $zoom; ?>,
					center: dmLatLng,
					mapTypeId: google.maps.MapTypeId.TERRAIN,
					scaleControl: true
				});

				polyBounds = new google.maps.LatLngBounds();

				drawingManager = new google.maps.drawing.DrawingManager({
					drawingMode: google.maps.drawing.OverlayType.POLYGON,
					drawingControl: true,
					drawingControlOptions: {
						position: google.maps.ControlPosition.TOP_CENTER,
						drawingModes: [ google.maps.drawing.OverlayType.POLYGON ]
					},
					markerOptions: {
						draggable: true
					},
					polyOptions: {
						fillOpacity: 0.45,
						strokeWeight: 0,
						editable: true,
						draggable: true
					},
				});

				drawingManager.setMap(map);

				google.maps.event.addListener(drawingManager, 'polygoncomplete', function (polygon) {
					drawingManager.setDrawingMode(null);
					addPolyListeners(polygon);
					polygon.id = polygons.length;
				    polygons.push(polygon);
				    selectedPolygon = polygon;
				    polygon.setEditable(true);
					setPolygonStr();
				});

				// Clear the current selection when the drawing mode is changed or when the map is clicked
				google.maps.event.addListener(drawingManager, 'drawingmode_changed', clearSelection);
				google.maps.event.addListener(map, 'click', clearSelection);
				if(drawPolygons()) drawingManager.setDrawingMode(null);
			}

			function drawPolygons(){
				polygons = [];
				let status = false;
				let footprintWkt = document.getElementById('footprintwkt').value.trim();
				if(footprintWkt != ""){
					if(footprintWkt.substring(0,7) == "POLYGON") footprintWkt = footprintWkt.substring(7).trim();
					else if(footprintWkt.substring(0,12) == "MULTIPOLYGON") footprintWkt = footprintWkt.substring(12).trim();
					footprintWkt = footprintWkt.slice(2,-2);
					let polyArr = footprintWkt.split(")),");
					for(let m=0; m < polyArr.length; m++){
						let pointArr = [];
						let polyStr = polyArr[m];
						while(polyStr.substring(0,1) == "("){
							polyStr = polyStr.substring(1).trim();
						}
						while(polyStr.substring(polyStr.length-1) == ")"){
							polyStr = polyStr.slice(0, -1).trim();
						}
						let strArr = polyStr.split(",");
						for(let n=0; n < strArr.length; n++){
							var xy = strArr[n].trim().split(" ");
							var pt = new google.maps.LatLng(xy[0],xy[1]);
							pointArr.push(pt);
							polyBounds.extend(pt);
						}

						if(pointArr.length > 0){
							let footPoly = new google.maps.Polygon({
								paths: pointArr,
								strokeWeight: 0,
								fillOpacity: 0.45,
								draggable: false,
								map: map
							});
							addPolyListeners(footPoly);
						    polygons.push(footPoly);
							map.fitBounds(polyBounds);
							map.panToBounds(polyBounds);
						}
					}
					status = true;
				}
				return status;
			}

			function addPolyListeners(poly){
				google.maps.event.addListener(poly, 'click', function() {
					clearSelection();
					selectedPolygon = poly;
					poly.setEditable(true);
				});
				google.maps.event.addListener(poly, 'dragend', function() { setPolygonStr(); });
				google.maps.event.addListener(poly.getPath(), 'insert_at', function() { setPolygonStr(); });
				google.maps.event.addListener(poly.getPath(), 'remove_at', function() { setPolygonStr(); });
				google.maps.event.addListener(poly.getPath(), 'set_at', function() { setPolygonStr(); });
			}

			function reformatPolygons(f){
				let footprintWkt = f.footprintwkt.value.trim();
				if(f.trimCoord.checked) trimCoord = true;
				else trimCoord = false;
				if(f.switchCoord.checked) switchCoord = true;
				else switchCoord = false;
				f.footprintwkt.value = validatePolygon(footprintWkt);
				clearSelection();
				for(var h=0;h<polygons.length;h++){
					polygons[h].setMap(null);
				}
				polyBounds = null;
				polyBounds = new google.maps.LatLngBounds();
				drawPolygons();
				drawingManager.setDrawingMode(null);
				f.redrawButton.disabled = true;
			}

			function clearSelection() {
				if(selectedPolygon){
					selectedPolygon.setEditable(false);
					selectedPolygon = null;
				}
			}

			function setPolygonStr() {
				var polyStrArr = [];
				for(var h=0;h<polygons.length;h++){
					let polygon = polygons[h];

					let coordinates = [];
					let coordinatesMVC = (polygon.getPath().getArray());
					for(var i=0;i<coordinatesMVC.length;i++){
						let mvcString = coordinatesMVC[i].toString();
						mvcString = mvcString.slice(1, -1);
						let latlngArr = mvcString.split(",");
						coordinates.push(parseFloat(latlngArr[0]).toFixed(6)+" "+parseFloat(latlngArr[1]).toFixed(6));
					}
					if(coordinates[0] != coordinates[i-1]) coordinates.push(coordinates[0]);
					let coordStr = coordinates.toString();
					if(coordStr && coordStr != "" && coordStr != undefined){
						polyStrArr.push("(("+coordStr+"))");
					}
				}

				document.getElementById("footprintwkt").value = "";
				if(polyStrArr.length == 1){
					document.getElementById("footprintwkt").value = "POLYGON "+polyStrArr[0];
				}
				else if(polyStrArr.length > 1){
					var outStr = '';
					for(var j=0;j<polyStrArr.length;j++){
						outStr = outStr + "," + polyStrArr[j];
					}
					document.getElementById("footprintwkt").value = "MULTIPOLYGON ("+outStr.substring(1)+")";
				}
				document.getElementById("redrawButton").disabled = true;
			}

			function deleteSelectedPolygon() {
				if(selectedPolygon){
					let index = selectedPolygon.id;
					polygons.splice(index, 1);
					for(var h=0;h<polygons.length;h++){
						polygons[h].id = h;
					}
					selectedPolygon.setMap(null);
					clearSelection();
					setPolygonStr();
				}
			}

			function submitPolygonForm(f){
				var str1 = "inline";
				var str2 = "none";
				if(f.clid.value == "" || f.footprintwkt.value == ""){
					str1 = "none";
					str2 = "inline";
				}
				if(opener.document.getElementById("polyDefDiv")){
					opener.document.getElementById("polyDefDiv").style.display = str1;
					opener.document.getElementById("polyNotDefDiv").style.display = str2;
				}
				opener.document.getElementById("footprintwkt").value = f.footprintwkt.value;
				if(f.clid.value == 0){
					window.close();
					return false;
				}
				return true;
			}

			function polygonModified(f){
				f.redrawButton.disabled = false;
			}

			function toggle(target){
				var ele = document.getElementById(target);
				if(ele){
					if(ele.style.display=="none") ele.style.display="";
					else ele.style.display="none";
				}
			}
		</script>
	</head>
	<body style="background-color:#ffffff;" onload="initialize()">
		<div id="map_canvas" style="width:100%;height:600px;"></div>
		<div style="width:100%;">
			<div id="helptext" style="display:none;margin:5px 0px">
				Click on polygon symbol to activate polygon tool and create a shape representing research area.
				Click save button to link polygon to checklist.
				The WKT polygon footprint within the text box can be modifed by hand and rebuilt on map using the Redraw Polygon button.
				A WKT polygon definition can be copied into text area from another application.
				Use Switch Coordinate Order button to convert Long-Lat coordinate pairs to Lat-Long format.
			</div>
			<form name="polygonSubmitForm" method="post" action="mappolyaid.php" onsubmit="return submitPolygonForm(this)">
				<div style="">
					<textarea id="footprintwkt" name="footprintwkt" style="width:98%;height:90px;" oninput="polygonModified(this.form)"><?php echo $clManager->getFootprintWkt(); ?></textarea>
					<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
					<input name="latdef" type="hidden" value="<?php echo $latDef; ?>" />
					<input name="lngdef" type="hidden" value="<?php echo $lngDef; ?>" />
					<input name="zoom" type="hidden" value="<?php echo $zoom; ?>" />
				</div>
				<div style="">
					<button name="formsubmit" type="submit" value="save" style="margin-right: 10px">Save Polygons</button>
					<button name="deleteButton" type="button" onclick="deleteSelectedPolygon()">Delete Selected Shape</button>
					<a href="#" onclick="toggle('helptext')"><img alt="Display Help Text" src="../../images/qmark_big.png" style="width:15px;" /></a>
					<fieldset id="reformatFieldset" style="width:300px;">
						<legend>Redraw / Reformat Polygons</legend>
						<button id="redrawButton" name="redrawButton" type="button" onclick="reformatPolygons(this.form);" disabled>Redraw</button><br />
						<input type="checkbox" name="trimCoord" value="1" onclick="polygonModified(this.form)" checked /> Trim to 6 significant digits<br />
						<input type="checkbox" name="switchCoord" value="1" onclick="polygonModified(this.form)" /> Switch lat/long coordinates
					</fieldset>
				</div>
			</form>
		</div>
	</body>
</html>