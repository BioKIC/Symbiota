<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/tools/mapaids.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$formName = array_key_exists("formname",$_REQUEST)?$_REQUEST["formname"]:"";
$latName = array_key_exists("latname",$_REQUEST)?$_REQUEST["latname"]:"";
$longName = array_key_exists("longname",$_REQUEST)?$_REQUEST["longname"]:"";
$latDef = array_key_exists("latdef",$_REQUEST)?$_REQUEST["latdef"]:'';
$lngDef = array_key_exists("lngdef",$_REQUEST)?$_REQUEST["lngdef"]:'';
$zoom = array_key_exists("zoom",$_REQUEST)&&$_REQUEST["zoom"]?$_REQUEST["zoom"]:5;

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
		<title><?php echo $DEFAULT_TITLE; ?> - Coordinate Polygon Aid</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?= (!empty($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY != 'DEV' ? 'key=' . $GOOGLE_MAP_KEY : '') ?>"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/wktpolygontools.js" type="text/javascript"></script>
		<script type="text/javascript">
			var map;
			var polygonWkt;
			var selectedShape = null;

			function initialize(){
				if(opener.document.getElementById("footprintwkt").value != ''){
					polygonWkt = validatePolygon(opener.document.getElementById("footprintwkt").value);
				}
				var dmLatLng = new google.maps.LatLng(<?php echo $latCenter.','.$lngCenter; ?>);
				var dmOptions = {
					zoom: <?php echo $zoom; ?>,
					center: dmLatLng,
					mapTypeId: google.maps.MapTypeId.TERRAIN,
					scaleControl: true
				};
				map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);

				var polyOptions = {
					strokeWeight: 0,
					fillOpacity: 0.45,
					editable: true,
					draggable: true
				};

				var drawingManager = new google.maps.drawing.DrawingManager({
					drawingMode: google.maps.drawing.OverlayType.POLYGON,
					drawingControl: true,
					drawingControlOptions: {
						position: google.maps.ControlPosition.TOP_CENTER,
						drawingModes: [
							google.maps.drawing.OverlayType.POLYGON
						]
					},
					markerOptions: {
						draggable: true
					},
					polygonOptions: polyOptions
				});

				drawingManager.setMap(map);

				google.maps.event.addListener(drawingManager, 'overlaycomplete', function(e) {
					if (e.type != google.maps.drawing.OverlayType.MARKER) {
						// Switch back to non-drawing mode after drawing a shape.
						drawingManager.setDrawingMode(null);

						var newShapeType = e.type;
						// Add an event listener that selects the newly-drawn shape when the user
						// mouses down on it.
						var newShape = e.overlay;
						newShape.type = e.type;
						google.maps.event.addListener(newShape, 'click', function() {
							setSelection(newShape);
						});
						google.maps.event.addListener(newShape, 'dragend', function() {
							setSelection(newShape);
						});
						if (newShapeType == 'polygon'){
							setPolygonStr(newShape);
							google.maps.event.addListener(newShape.getPath(), 'insert_at', function() {
								setSelection(newShape);
							});
							google.maps.event.addListener(newShape.getPath(), 'remove_at', function() {
								setSelection(newShape);
							});
							google.maps.event.addListener(newShape.getPath(), 'set_at', function() {
								setSelection(newShape);
							});
						}
						setSelection(newShape);
					}
				});

				// Clear the current selection when the drawing mode is changed or when the map is clicked
				google.maps.event.addListener(drawingManager, 'drawingmode_changed', clearSelection);
				google.maps.event.addListener(map, 'click', clearSelection);
				setPolygon();
			}

			function setPolygon(){
				var pointArr = [];
				var polyBounds = new google.maps.LatLngBounds();
				if(polygonWkt){
					var footprintWKT = trimPolygon(polygonWkt);
					var strArr = footprintWKT.split(",");
					for(var i=0; i < strArr.length; i++){
						var xy = strArr[i].trim().split(" ");
						if(parseInt(Math.abs(xy[0])) > 90 || parseInt(Math.abs(xy[1])) > 180){
							alert("One or more coordinates are illegal or ordered incorrectly ("+xy[0]+" "+xy[1]+")");
							return false;
						}
						else{
							var pt = new google.maps.LatLng(xy[0],xy[1]);
							pointArr.push(pt);
							polyBounds.extend(pt);
						}
					}
				}
				if(pointArr.length > 0){
					var footPoly = new google.maps.Polygon({
						paths: pointArr,
						strokeWeight: 0,
						fillOpacity: 0.45,
						editable: true,
						draggable: true,
						map: map
					});
					footPoly.type = 'polygon';
					google.maps.event.addListener(footPoly, 'click', function() { setSelection(footPoly); });
					google.maps.event.addListener(footPoly, 'dragend', function() { setSelection(footPoly); });
					google.maps.event.addListener(footPoly.getPath(), 'insert_at', function() { setSelection(footPoly); });
					google.maps.event.addListener(footPoly.getPath(), 'remove_at', function() { setSelection(footPoly); });
					google.maps.event.addListener(footPoly.getPath(), 'set_at', function() { setSelection(footPoly); });
					setSelection(footPoly);
					map.fitBounds(polyBounds);
					map.panToBounds(polyBounds);
				}
			}

			function resetPolygon(){
				if(selectedShape) selectedShape.setMap(null);
				setPolygon();
			}

			function setSelection(shape) {
				selectedShape = shape;
				selectedShape.setEditable(true);
				if (shape.type == 'polygon') {
					setPolygonStr(shape);
				}
			}

			function clearSelection() {
				if(selectedShape){
					selectedShape.setEditable(false);
					selectedShape = null;
				}
			}

			function deleteSelectedShape() {
				if(selectedShape){
					selectedShape.setMap(null);
					clearSelection();
				}
				opener.document.getElementById("footprintwkt").value = "";
				polygonWkt = "";
			}

			function setPolygonStr(polygon) {
				var coordinates = [];
				var coordinatesMVC = (polygon.getPath().getArray());
				for(i=0;i<coordinatesMVC.length;i++){
					var mvcString = coordinatesMVC[i].toString();
					mvcString = mvcString.slice(1, -1);
					var latlngArr = mvcString.split(",");
					coordinates.push(parseFloat(latlngArr[0]).toFixed(6)+" "+parseFloat(latlngArr[1]).toFixed(6));
					//if(i==0) var firstSet = parseFloat(latlngArr[1]).toFixed(6)+" "+parseFloat(latlngArr[0]).toFixed(6);
				}
				//coordinates.push(firstSet);
				polygonWkt = coordinates.toString();
			}

			function updateParentForm() {
				if(opener.document.getElementById("footprintwkt")){
					if(polygonWkt && polygonWkt != "" && polygonWkt != undefined && polygonWkt.substring(7) != "POLYGON"){
						polygonWkt = "POLYGON (("+polygonWkt+"))";
					}
					opener.document.getElementById("footprintwkt").value = polygonWkt;
					try{
						opener.document.getElementById("footprintwkt").onchange();
					}
					catch(myErr){}
				}
				if(opener.document.getElementById("polySaveDiv")){
					opener.document.getElementById("polySaveDiv").style.display = "block";
				}
				if(opener.document.getElementById("delpolygon")){
					opener.document.getElementById("delpolygon").style.display = "block";
				}
				if(opener.document.getElementById("polyDefDiv")){
					opener.document.getElementById("polyDefDiv").style.display = "none";
				}
				if(opener.document.getElementById("polyNotDefDiv")){
					opener.document.getElementById("polyNotDefDiv").style.display = "none";
				}
				self.close();
				return false;
			}
		</script>
		<style>
			.screen-reader-only {
				position: absolute;
				left: -10000px;
			}
		</style>
	</head>
	<body style="background-color:#ffffff;" onload="initialize()">
		<h1 class="page-heading screen-reader-only">Coordinate Polygon Aid</h1>
		<div style="float:right" style="margin-left:20px;">
         <button type="submit" name="addcoords" onclick="updateParentForm()">
            <?php echo isset($LANG['SAVE_N_CLOSE'])? $LANG['SAVE_N_CLOSE'] :'Save and Close'?>
         </button>
		</div>
		<div style="float:right" style="margin-left:20px;">
         <button id="delete-button" onclick="deleteSelectedShape();return false">
            <?php echo isset($LANG['DELETE_SELECTED'])? $LANG['DELETE_SELECTED'] :'Delete Selected Polygon'?>
         </button>
		</div>
		<div id="helptext" style="">
         <?php echo isset($LANG['MPA_INSTRUCTIONS'])? $LANG['MPA_INSTRUCTIONS'] :'Click on polygon symbol to activate polygon tool. Click submit button transfer polygon to editor.'?>
			<br/>
		</div>
		<div id='map_canvas' style='width:100%;height:700px;'></div>
	</body>
</html>
