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
$wkt = $clManager->getFootprintWkt();

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
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
   <head>

      <?php
	   include_once($SERVER_ROOT.'/includes/head.php');
	   include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	   include_once($SERVER_ROOT.'/includes/leafletMap.php');
	   include_once($SERVER_ROOT.'/includes/googleMap.php');
      ?>

		<title><?php echo $DEFAULT_TITLE . ' - ' . $LANG['COORDINATE'] ?></title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/wktpolygontools.js?ver=5" type="text/javascript"></script>
<script src="https://unpkg.com/terraformer@1.0.8"></script>
<script src="https://unpkg.com/terraformer-wkt-parser@1.1.2"></script>
      <script type="text/javascript">
         let map;
         let selectedPoly;

         //Default is that trim is checked and lnglat/switchCoord is not
         let trimPolyFlag = true;
         let lnglatLayoutFlag = false;
		 let action = "<?= htmlspecialchars($formSubmit) ?>"
		 if(action === 'exit') {
			 window.close();
		 }

         function onPolyUpdate(shape) {
            setWkt(map.shapes);
         }

         function deleteSelectedPolygon() {

            selectedPoly.setMap(null);
            //Remove selected poly from shapes
            map.shapes = map.shapes.filter(s => s.id !== map.activeShape.id)
            map.activeShape = null;

            if(!Array.isArray(map.shapes) || map.shapes.length === 0) {
               return;
            } else {
               setWkt(map.shapes);
            }
         }

         //Parses Polygon and Multipolygon from wkt input
         //Relies on Terraformer library which is being brought in via cdn
         function loadPoly(wkt) {
            if(!wkt) return;
            const geoJson = Terraformer.WKT.parse(wkt);
            if(geoJson.type === "Polygon") {
               return{ type: "polygon", latlngs: geoJson.coordinates[0], wkt}
            } else if(geoJson.type === "MultiPolygon") {
               return geoJson.coordinates.map(c => ({type: 'polygon', latlngs: c[0], wkt, format: lnglatLayoutFlag?"lnglat": "latlng"}))
            }
         }

         function trimLatLng(latlng) {
            const TRIM_COUNT = 6;

            return [
               parseFloat(latlng[0].toFixed(TRIM_COUNT)),
               parseFloat(latlng[1].toFixed(TRIM_COUNT))
            ]
         }

         //Needs Attribute shapes
         function setWkt(shapeArr) {
            if(!Array.isArray(shapeArr) || shapeArr.length === 0) {
               document.getElementById('footprintwkt').value = "";
            }

            const swapCoords = (coords) => coords.map(latlng => [latlng[1], latlng[0]]);

            let coordinates = [];
            for(let shape of shapeArr) {
               let latlngs = trimPolyFlag?
                  shape.latlngs.map(ll => trimLatLng(ll)):
                  shape.latlngs;

               if(lnglatLayoutFlag) {
                  latlngs = swapCoords(latlngs);
               }

               if(shapeArr.length > 1)
                  coordinates.push([latlngs]);
               else
                  coordinates.push(latlngs);
            }

            let geoJson = {type: shapeArr.length > 1?"MultiPolygon":"Polygon", coordinates}
            document.getElementById('footprintwkt').value = Terraformer.WKT.convert(geoJson);
         }

         function leafletInit() {
            const mapOptions = {
               zoom: <?php echo $zoom; ?>,
               center: [<?php echo $latCenter?>, <?php echo $lngCenter?>],
               lang: "<?php echo $LANG_TAG; ?>"
            }

            map = new LeafletMap('map_canvas', mapOptions);
            map.enableDrawing({
               polyline: false,
               circlemarker: false,
               circle: false,
               rectangle: false,
               marker: false,
               multiDraw: true,
               drawColor: {opacity: 0.85, fillOpacity: 0.55, color: '#000' }
            }, onPolyUpdate);
         }

         function googleInit() {

            const mapOptions= {
               zoom: <?php echo $zoom; ?>,
               center: new google.maps.LatLng(<?php echo $latCenter . ',' . $lngCenter; ?>),
               mapTypeId: google.maps.MapTypeId.TERRAIN,
               scaleControl: true
            };

            map = new GoogleMap('map_canvas', mapOptions);
            const resetSelect = () => map.shapes.map( s => {
                  s.layer.setEditable(false);
                  s.layer.setDraggable(false);
               });

				google.maps.event.addListener(map.mapLayer, 'click', function(e) {
               selectedPoly = null;
               resetSelect();
            });

            map.enableDrawing({
               editable: false,
               draggable: false,
               circle: false,
               rectangle: false,
               multipolygon: true,
            }, shape => {
            //Init it Selected

				google.maps.event.addListener(shape.layer, 'click', function(e) {
                  resetSelect();
                  selectedPoly = shape.layer;
                  shape.layer.setEditable(true);
                  shape.layer.setDraggable(true);
            });

               onPolyUpdate(shape)
            })
         }

         function drawLoadedShape() {

            let wkt = document.getElementById("footprintwkt").value;

            const loadedShape = loadPoly(wkt);

            if(Array.isArray(loadedShape)) {
               loadedShape.forEach(s=> map.drawShape(s));
            } else if (loadedShape) {
               map.drawShape(loadedShape);
            }
         }

         function initialize() {
            let polygons;

            //Loads wkt from opener value otherwise db value is used
            let parent_wkt = opener.document.getElementById("footprintwkt");
			 console.log(parent_wkt)
			if(parent_wkt) {
				document.getElementById("footprintwkt").value = parent_wkt.value 
			}

            if(<?= (!empty($GOOGLE_MAP_KEY)?'true':'false') ?>) {
               googleInit();
            } else {
               leafletInit();
            }

            drawLoadedShape();
         }

         function reformatPolygons() {
            //setWkt with new settings
            setWkt(map.shapes);
            //Clear out currently draw shapes
            map.clearMap();
            //Redraw
            drawLoadedShape();
         }

			function polygonModified(f){
				f.redrawButton.disabled = false;

            trimPolyFlag =  f.trimCoord && f.trimCoord.checked? true: false;
            lnglatLayoutFlag =  f.switchCoord && f.switchCoord.checked? true: false;
         }

			function toggle(target){
				var ele = document.getElementById(target);
				if(ele){
					if(ele.style.display=="none") ele.style.display="";
					else ele.style.display="none";
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
				if(opener.document.getElementById("footprintwkt")) {
					opener.document.getElementById("footprintwkt").value = f.footprintwkt.value;
					// If Tool is filling out form value from parent form don't finish action on itself just forward value to parent form
					window.close();
				}
				return f.clid.value != 0;
			}
		</script>
	</head>
	<body style="background-color:#ffffff;" onload="initialize()">
         <h1 class="page-heading screen-reader-only">Map Polygon Helper</h1>
		<div id="map_canvas" style="width:100%;height:600px;"></div>
		<div style="width:100%;">
			<div id="helptext" style="display:none;margin:5px 0px">
            <?php echo $LANG['INSTRUCTION'] ?>
			</div>
			<form name="polygonSubmitForm" method="post" action="mappolyaid.php" onsubmit="return submitPolygonForm(this)">
				<div style="">
               <textarea id="footprintwkt" name="footprintwkt" style="width:98%;height:90px;" oninput="polygonModified(this.form)"><?php echo $wkt; ?></textarea>
					<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
					<input name="latdef" type="hidden" value="<?php echo $latDef; ?>" />
					<input name="lngdef" type="hidden" value="<?php echo $lngDef; ?>" />
					<input name="zoom" type="hidden" value="<?php echo $zoom; ?>" />
				</div>
				<div style="">
               <button name="formsubmit" type="submit" value="save" style="margin-right: 10px">
                  <?php echo isset($LANG['SAVE_N_CLOSE'])? $LANG['SAVE_N_CLOSE'] :'Save and Close'?>
               </button>
               <?php if(!empty($GOOGLE_MAP_KEY)):?>
               <button name="deleteButton" type="button" onclick="deleteSelectedPolygon()">
                  <?php echo isset($LANG['DELETE_SELECTED'])? $LANG['DELETE_SELECTED'] :'Delete Selected Polygon'?>
               </button>
					<a href="#" onclick="toggle('helptext')"><img alt="Display Help Text" src="../../images/qmark.png" style="width:1.3em;" /></a>
               <?php endif?>
					<fieldset id="reformatFieldset" style="width:300px;">
                  <legend>
                     <?php echo isset($LANG['POLYGON_FORMATTING'])? $LANG['POLYGON_FORMATTING'] :'Polygon Formatting'?>
                  </legend>
                  <button id="redrawButton" name="redrawButton" type="button" onclick="reformatPolygons(this.form);" disabled>
                     <?php echo isset($LANG['REDRAW'])? $LANG['REDRAW'] :'Redraw'?>
                  </button><br />
                  <input type="checkbox" name="trimCoord" value="1" onclick="polygonModified(this.form)" checked /> 
                  <?php echo isset($LANG['TRIM_DIGITS'])? $LANG['TRIM_DIGITS'] :'Trim to 6 significant digits'?>
                  <br />
                  <input type="checkbox" name="switchCoord" value="1" onclick="polygonModified(this.form)"/>
                  <?php echo isset($LANG['SWAP_COORDS'])? $LANG['SWAP_COORDS'] :'Switch lat/long coordinates'?>
					</fieldset>
				</div>
			</form>
		</div>
	</body>
</html>
