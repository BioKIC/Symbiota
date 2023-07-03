<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/map/index.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMapManager.php');

header('Content-Type: text/html; charset='.$CHARSET);
ob_start('ob_gzhandler');
ini_set('max_execution_time', 180); //180 seconds = 3 minutes

$distFromMe = array_key_exists('distFromMe', $_REQUEST)?$_REQUEST['distFromMe']:'';
$gridSize = array_key_exists('gridSizeSetting', $_REQUEST) && $_REQUEST['gridSizeSetting']?$_REQUEST['gridSizeSetting']:60;
$minClusterSize = array_key_exists('minClusterSetting',$_REQUEST)&&$_REQUEST['minClusterSetting']?$_REQUEST['minClusterSetting']:10;
$clusterOff = array_key_exists('clusterSwitch',$_REQUEST)&&$_REQUEST['clusterSwitch']?$_REQUEST['clusterSwitch']:'n';
$recLimit = array_key_exists('recordlimit',$_REQUEST)?$_REQUEST['recordlimit']:15000;
$catId = array_key_exists('catid',$_REQUEST)?$_REQUEST['catid']:0;
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;
$submitForm = array_key_exists('submitform',$_REQUEST)?$_REQUEST['submitform']:'';

if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;

$mapManager = new OccurrenceMapManager();
$searchVar = $mapManager->getQueryTermStr();
if($searchVar && $recLimit) $searchVar .= '&reclimit='.$recLimit;

$obsIDs = $mapManager->getObservationIds();

//Sanitation
if(!is_numeric($gridSize)) $gridSize = 60;
if(!is_numeric($minClusterSize)) $minClusterSize = 10;
if(!is_string($clusterOff) || strlen($clusterOff) > 1) $clusterOff = 'n';
if(!is_numeric($recLimit)) $recLimit = 15000;
if(!is_numeric($distFromMe)) $distFromMe = '';
if(!is_numeric($catId)) $catId = 0;
if(!is_numeric($tabIndex)) $tabIndex = 0;

$activateGeolocation = 0;
if(isset($ACTIVATE_GEOLOCATION) && $ACTIVATE_GEOLOCATION == 1) $activateGeolocation = 1;
?>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $DEFAULT_TITLE; ?> - Map Interface</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<link href="<?php echo $CSS_BASE_PATH; ?>/symbiota/collections/listdisplay.css" type="text/css" rel="stylesheet" />
	<style type="text/css">
		.panel-content a{ outline-color: transparent; font-size: 12px; font-weight: normal; }
		.ui-front { z-index: 9999999 !important; }
		#map { position: fixed !important; height: 100% !important; width: 100% !important; }
	</style>
	<script src="../../js/jquery-1.10.2.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
	<link href="../../js/jquery-ui/jquery-ui.min.css" type="text/css" rel="Stylesheet" />
	<script src="../../js/jquery.mobile-1.4.0.min.js" type="text/javascript"></script>
	<link href="../../css/jquery.mobile-1.4.0.min.css" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery.symbiota.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.popupoverlay.js" type="text/javascript"></script>
	<script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'&key='.$GOOGLE_MAP_KEY:''); ?>&callback=Function.prototype" ></script>
	<script src="../../js/jscolor/jscolor.js?ver=1" type="text/javascript"></script>
	<script src="../../js/symb/collections.map.index.js?ver=2" type="text/javascript"></script>
	<script src="../../js/symb/collections.list.js?ver=1" type="text/javascript"></script>
	<script src="../../js/symb/markerclusterer.js?ver=1" type="text/javascript"></script>
	<script src="../../js/symb/oms.min.js" type="text/javascript"></script>
	<script src="../../js/symb/keydragzoom.js" type="text/javascript"></script>
	<script src="../../js/symb/infobox.js" type="text/javascript"></script>
	<script type="text/javascript">
		var clientRoot = "<?php echo $CLIENT_ROOT; ?>";

		$(document).ready(function() {
			<?php
			if($searchVar) echo 'sessionStorage.querystr = "'.$searchVar.'";';
			?>
		});

		//$( "#defaultpanel" ).panel( "open" );
		var map;
		var infoWins = [];
		var puWin;
		var markers = [];
		var dsmarkers = [];
		var dsoccids = [];
		var selections = [];
		var dsselections = [];
		var selectedds = '';
		var selecteddsrole = '';
		var marker;
		var drawingManager = null;
		var oms;
		var dsoms;
		var selectedShape = null;
		var gotCoords = <?php echo ($activateGeolocation?'true':'false'); ?>;
		var mapSymbol = 'coll';
		var selected = false;
		var deselected = false;
		var positionFound = false;
		var clid = '<?php echo ($mapManager->getSearchTerm('clid')?$mapManager->getSearchTerm('clid'):0); ?>';
		var clusterOff = '<?php echo $clusterOff; ?>';
		var obsIDs = JSON.parse('<?php echo json_encode($obsIDs); ?>');
		var grpArr = [];
		var markerArr = [];
		var tidArr = [];
		var taxaArr = [];
		var taxaCnt = 0;
		var collKeyArr = [];
		var collNameArr = [];
		var familyNameArr = [];
		var keyTidArr = [];
		var taxaKeyArr = [];
		var clusterCollArr = [];
		var clusterTaxArr = [];
		var optionsCollArr = [];
		var optionsTaxArr = [];
		var grpCnt = 1;
		var ibLabel = '';
		var mouseoverTimeout = '';
		var mouseoutTimeout = '';
		var pointBounds = new google.maps.LatLngBounds();
		var occArr = [];

		function showWorking(){
			$('#loadingOverlay').popup('show');
		}

		function hideWorking(){
			$('#loadingOverlay').popup('hide');
		}

		function getCoords(){
			if (navigator.geolocation){
				var options = {
					timeout: 4000,
					maximumAge: 0
				};
				setTimeout(function(){
					if(!positionFound){
						gotCoords = false;
						initialize();
					}
				},4005)
				navigator.geolocation.getCurrentPosition(
					initialize,
					function (error) {
						if (error.code){
							gotCoords = false;
							initialize();
						}
					},
					options
				);
			}
			else{
				gotCoords = false;
				initialize();
			}
		}

		function initialize(){
			<?php
			$latCen = 41.0;
			$longCen = -95.0;
			if(isset($MAPPING_BOUNDARIES)){
				$coorArr = explode(";",$MAPPING_BOUNDARIES);
				if($coorArr && count($coorArr) == 4){
					$latCen = ($coorArr[0] + $coorArr[2])/2;
					$longCen = ($coorArr[1] + $coorArr[3])/2;
				}
			}
			?>
			var pos = '';
			if(gotCoords==true){
				positionFound = true;
				pos = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
				posLat = position.coords.latitude;
				posLong = position.coords.longitude;
				document.getElementById("geocriteria").style.display = "none";
				document.getElementById("distancegeocriteria").style.display = "block";
			}
			else{
				pos = new google.maps.LatLng(<?php echo $latCen.', '.$longCen; ?>);
			}

			var dmOptions = {
				zoom: 6,
				minZoom: 3,
				center: pos,
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				mapTypeControl: true,
				mapTypeControlOptions: {
					style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
					position: google.maps.ControlPosition.TOP_RIGHT
				},
				panControl: true,
				panControlOptions: {
					position: google.maps.ControlPosition.RIGHT_TOP
				},
				zoomControl: true,
				zoomControlOptions: {
					style: google.maps.ZoomControlStyle.LARGE,
					position: google.maps.ControlPosition.RIGHT_TOP
				},
				scaleControl: true,
				scaleControlOptions: {
					position: google.maps.ControlPosition.RIGHT_TOP
				},
				streetViewControl: false
			};

			map = new google.maps.Map(document.getElementById("map"), dmOptions);

			oms = new OverlappingMarkerSpiderfier(map);

			dsoms = new OverlappingMarkerSpiderfier(map);

			map.enableKeyDragZoom({
				visualEnabled: true,
				visualPosition: google.maps.ControlPosition.LEFT,
				visualPositionOffset: new google.maps.Size(35, 0),
				visualPositionIndex: null,
				visualSize: new google.maps.Size(20, 20),
				visualTips: {
					off: "Turn on",
					on: "Turn off"
				}
			});

			var polyOptions = {
				strokeWeight: 0,
				fillOpacity: 0.45,
				editable: true,
				draggable: true
			};

			var drawingManager = new google.maps.drawing.DrawingManager({
				drawingMode: null,
				drawingControl: true,
				drawingControlOptions: {
					position: google.maps.ControlPosition.TOP_CENTER,
					drawingModes: [
						<?php if($mapManager->hasFullSpatialSupport()) echo 'google.maps.drawing.OverlayType.POLYGON,'; ?>
						google.maps.drawing.OverlayType.RECTANGLE,
						google.maps.drawing.OverlayType.CIRCLE
					]
				},
				markerOptions: {
					draggable: true
				},
				polylineOptions: {
					editable: true
				},
				rectangleOptions: polyOptions,
				circleOptions: polyOptions,
				polygonOptions: polyOptions
			});

			drawingManager.setMap(map);

			google.maps.event.addDomListener(
				document.getElementById("distFromMe"),
				'change',
				function(){
					var distance = document.getElementById("distFromMe").value;
					if(distance){
						document.getElementById("pointlat").value = posLat;
						document.getElementById("pointlong").value = posLong;
						document.getElementById("radius").value = distance;
					}
				}
			);

			google.maps.event.addListener(drawingManager, 'overlaycomplete', function(e) {
				if (e.type != google.maps.drawing.OverlayType.MARKER) {
					// Switch back to non-drawing mode after drawing a shape.
					deleteSelectedShape();
					drawingManager.setDrawingMode(null);

					var newShapeType = '';
					newShapeType = e.type;
					// Add an event listener that selects the newly-drawn shape when the user
					// mouses down on it.
					var newShape = e.overlay;
					newShape.type = e.type;
					google.maps.event.addListener(newShape, 'dragend', function() {
						setSelection(newShape);
					});
					if (newShapeType == 'circle'){
						getCircleCoords(newShape);
						google.maps.event.addListener(newShape, 'radius_changed', function() {
							setSelection(newShape);
						});
						google.maps.event.addListener(newShape, 'center_changed', function() {
							setSelection(newShape);
						});
					}
					if (newShapeType == 'rectangle'){
						getRectangleCoords(newShape);
						google.maps.event.addListener(newShape, 'bounds_changed', function() {
							setSelection(newShape);
						});
					}
					if (newShapeType == 'polygon'){
						getPolygonCoords(newShape);
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

			oms.addListener('click', function(marker, event) {
				occid = marker.occid;
				chbox = 'ch'+occid;
				if(selections.indexOf(occid) > -1){
					var index = selections.indexOf(occid);
					if (index > -1) {
						selections.splice(index, 1);
					}
					if(document.getElementById(chbox)){
						document.getElementById(chbox).checked = false;
						document.getElementById("selectallcheck").checked = false;
					}
					//removeSelectionRecord(occid);
					//adjustSelectionsTab();
					deselectMarker(marker);
				}
				else{
					selections.push(occid);
					if(document.getElementById(chbox)){
						document.getElementById(chbox).checked = true;
						var f = document.getElementById("selectform");
						var boxesChecked = true;
						for(var i=0;i<f.length;i++){
							if(f.elements[i].name == "occid[]"){
								if(f.elements[i].checked == false){
									boxesChecked = false;
								}
							}
						}
						if(boxesChecked == true){
							document.getElementById("selectallcheck").checked = true;
						}
					}
					//adjustSelectionsTab();
					selectMarker(marker);
				}
			});

			dsoms.addListener('click', function(marker, event) {
				occid = marker.occid;
				chbox = 'dsch'+occid;
				if(dsselections.indexOf(occid) > -1){
					var index = dsselections.indexOf(occid);
					if (index > -1) {
						dsselections.splice(index, 1);
					}
					if(document.getElementById(chbox)){
						document.getElementById(chbox).checked = false;
						document.getElementById("dsselectallcheck").checked = false;
					}
					deselectDsMarker(marker);
				}
				else{
					dsselections.push(occid);
					if(document.getElementById(chbox)){
						document.getElementById(chbox).checked = true;
						var f = document.getElementById("dsselectform");
						var boxesChecked = true;
						for(var i=0;i<f.length;i++){
							if(f.elements[i].name == "occid[]"){
								if(f.elements[i].checked == false){
									boxesChecked = false;
								}
							}
						}
						if(boxesChecked == true){
							document.getElementById("dsselectallcheck").checked = true;
						}
					}
					selectDsMarker(marker);
				}
			});

			oms.addListener('spiderfy', function(markers) {
				closeAllInfoWins();
			});

			dsoms.addListener('spiderfy', function(markers) {
				closeAllInfoWins();
			});

			// Clear the current selection when the drawing mode is changed, or when the
			// map is clicked.
			//google.maps.event.addListener(drawingManager, 'drawingmode_changed', clearSelection);
			//google.maps.event.addListener(map, 'click', clearSelection);


			<?php
			echo $mapManager->createShape();
			if($searchVar) echo "setPoints();";
			?>
		}

		function setPoints(){
			<?php
			$recordCnt = $mapManager->getRecordCnt();
			?>

			if(<?php echo $recordCnt; ?> > 0){
				var result = <?php echo $recordCnt; ?>;
				if(result <= <?php echo $recLimit; ?>) {
					<?php
					$coordArr = $mapManager->getCoordinateMap(0,$recLimit);
					echo 'var recArr = '.json_encode($coordArr).";\n";
					?>
					processPoints(recArr);
				}
				else{
					alert("Your search produced "+result+" results which exceeds the maximum of <?php echo $recLimit; ?>, please refine your search more.");
					//hideWorking();
				}
			}
			else{
				alert('There were no records matching your query.');
			}

			setTimeout(function() {
				afterEffects();
			}, 500);
		}

		function afterEffects(){
			setPanels(true);
			$("#accordion").accordion("option",{active: 1});
			buildCollKey();
			buildTaxaKey();
			jscolor.init();
			if(pointBounds){
				map.fitBounds(pointBounds);
				map.panToBounds(pointBounds);
			}
			setTimeout(function() {
				//hideWorking();
			}, 500);
		}

		function processPoints(pArr){
			var fndGrps = [];
			var finderArr = [];
			for(var key in pArr) {
				var iconColor = pArr[key]['c'];
				var tempGcntArr = [];
				var fndGrpCnt = 0;
				if(!finderArr[key]){
					finderArr[key] = grpCnt;
					fndGrpCnt = grpCnt;
					buildCollKeyPiece(key,iconColor);
				}
				else{
					fndGrpCnt = finderArr[key];
				}
				fndGrps.push(fndGrpCnt);
				delete pArr[key]['c'];
				for(var occ in pArr[key]) {
					if(occArr.indexOf(occ) < 0){
						var family = '';
						var tidinterpreted = pArr[key][occ]['tid'];
						var sciname = pArr[key][occ]['sn'];
						//var scinameStr = pArr[key][occ]['ns'];
						var scinameStr = tidinterpreted+sciname;
						scinameStr = scinameStr.replace(" ", "").toLowerCase();
						var tempArr = [];
						var tempArr = [];
						if (tidArr[scinameStr]) {
							if (tidArr[scinameStr].indexOf(grpCnt) > -1) {
								tempArr = tidArr[scinameStr];
							}
						}
						tempArr.push(grpCnt);
						tidArr[scinameStr] = tempArr;
						if (pArr[key][occ]['sn']) {
							sciname = pArr[key][occ]['sn'];
						}
						if (sciname) {
							var tempFamArr = [];
							var tempScinameArr = [];
							family = pArr[key][occ]['fam'];
							if ((familyNameArr.indexOf(family) < 0) && (family != 'undefined')) {
								familyNameArr.push(family);
							}
							if (taxaArr[family]) {
								tempFamArr = taxaArr[family];
								tempScinameArr = taxaArr[family]['sciname_arr'];
							}
							if (keyTidArr.indexOf(scinameStr) < 0) {
								tempScinameArr.push(sciname);
								keyTidArr.push(scinameStr);
								taxaCnt++;
							}
							tempFamArr[sciname] = scinameStr;
							taxaArr[family] = tempFamArr;
							taxaArr[family]['sciname_arr'] = tempScinameArr;
							buildTaxaKeyPiece(scinameStr, tidinterpreted, sciname);
							var llArr = pArr[key][occ]['llStr'].split(',');
							var spStr = '';
							var titleStr = pArr[key][occ]['llStr'];
							var type = '';
							var displayStr = pArr[key][occ]['id'];
							var iconColorStr = '#' + iconColor;
							if (obsIDs.indexOf(pArr[key][occ]['collid']) > -1) {
								type = 'obs';
								var markerIcon = {
									path: "m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",
									fillColor: iconColorStr,
									fillOpacity: 1,
									scale: 1,
									strokeColor: "#000000",
									strokeWeight: 1
								};
							}
							else {
								type = 'spec';
								var markerIcon = {
									path: google.maps.SymbolPath.CIRCLE,
									fillColor: iconColorStr,
									fillOpacity: 1,
									scale: 7,
									strokeColor: "#000000",
									strokeWeight: 1
								};
							}
							//Create Marker
							var m = new google.maps.Marker({
								position: new google.maps.LatLng(llArr[0], llArr[1]),
								text: displayStr,
								<?php
								if($clusterOff=="y"){
									?>
									map: map,
									<?php
								}
								?>
								icon: markerIcon,
								selected: false,
								color: iconColor,
								customInfo: type,
								taxatid: scinameStr,
								occid: occ,
								clid: 0
							});
							//Add marker listener
							m.addListener('mouseover', function() {
								var myOptions = {
									content: '<div>'+this.text+'<br /><a href="#" onclick="closeAllInfoWins();openIndPopup('+this.occid+','+this.clid+');return false;"><span style="color:blue;">See Details</span></a></div>',
									boxStyle: {
										border: "1px solid black",
										background: "#ffffff",
										textAlign: "center",
										padding: "2px",
										fontSize: "12px"
									},
									disableAutoPan: true,
									pixelOffset: new google.maps.Size(0,10),
									position: this.getPosition(),
									isHidden: false,
									closeBoxURL: "",
									pane: "floatPane",
									enableEventPropagation: false
								};

								if(mouseoutTimeout){
									if(ibLabel) {
										ibLabel.close();
									}
									clearTimeout(mouseoutTimeout);
									mouseoutTimeout = null;
								}

								mouseoverTimeout = setTimeout(
									function(){
										ibLabel = new InfoBox(myOptions);
										ibLabel.open(map);
									},1000
								);
							});

							m.addListener('mouseout', function() {
								if(mouseoverTimeout){
									clearTimeout(mouseoverTimeout);
									mouseoverTimeout = null;
								}
								mouseoutTimeout = setTimeout(
									function(){
										if(ibLabel){
											ibLabel.close();
										}
									},3000
								);
							});

							oms.addMarker(m);
							var markerPos = m.getPosition();
							pointBounds.extend(markerPos);
							if (grpArr[fndGrpCnt]) {
								var tempArr = grpArr[fndGrpCnt];
							}
							else {
								var tempArr = [];
							}
							tempArr.push(m);
							markerArr[occ] = m;
							grpArr[fndGrpCnt] = tempArr;
						}
						occArr.push(occ);
					}
				}

				for(var gc in fndGrps) {
					var markers = grpArr[fndGrps[gc]];

					optionsCollArr[fndGrps[gc]] = {
						styles: [{
							color: iconColor
						}],
						maxZoom: 13,
						gridSize: <?php echo $gridSize; ?>,
						minimumClusterSize: <?php echo $minClusterSize; ?>
					};

					if(clusterOff=="n"){
						if(clusterCollArr[fndGrps[gc]]){
							clusterCollArr[fndGrps[gc]].clearMarkers();
							clusterCollArr[fndGrps[gc]].setMap(null);
						}
						clusterCollArr[fndGrps[gc]] = new MarkerClusterer(map, markers, optionsCollArr[fndGrps[gc]]);
					}
				}

				grpCnt++;
			}
		}

		function setPanels(show){
			if(show){
				document.getElementById("recordstaxaheader").style.display = "block";
				document.getElementById("tabs2").style.display = "block";
			}
			else{
				document.getElementById("recordstaxaheader").style.display = "none";
				document.getElementById("tabs2").style.display = "none";
			}
		}

		function buildCollKeyPiece(key,iconColor){
			keyHTML = '';
			keyHTML += '<div style="display:table-row;">';
			keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input data-role="none" id="keyColor'+grpCnt+'" class="color" style="cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" value="'+iconColor+'" onchange="changeCollColor(this.value,'+grpCnt+');" /></div>';
			keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>';
			keyHTML += '<div style="display:table-cell;width:250px;vertical-align:middle;padding-left:8px;">'+key+'</div>';
			keyHTML += '</div>';
			keyHTML += '<div style="display:table-row;height:8px;"></div>';
			collKeyArr[key] = keyHTML;
			collNameArr.push(key);
		}

		function buildCollKey(){
			keyHTML = '';
			collNameArr.sort();
			for(var i=0,l=collNameArr.length;i<l;i++){
				keyHTML += collKeyArr[collNameArr[i]];
			}
			document.getElementById("symbologykeysbox").innerHTML = keyHTML;
		}

		function buildTaxaKeyPiece(key,tidinterpreted,sciname){
			keyHTML = '';
			keyLabel = "'"+key+"'";
			keyHTML += '<div id="'+key+'keyrow">';
			keyHTML += '<div style="display:table-row;">';
			keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" ><input data-role="none" id="taxaColor'+key+'" class="color" style="cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" value="e69e67" onchange="changeTaxaColor(this.value,'+keyLabel+');" /></div>';
			keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>';
			keyHTML += '<div style="display:table-cell;vertical-align:middle;padding-left:8px;">';
			if(tidinterpreted) keyHTML += '<i><a href="#" onclick="openPopup(\'../../taxa/index.php?tid='+tidinterpreted+'&display=1\');return false;">'+sciname+'</a></i>';
			else keyHTML += "<i>"+sciname+"</i>";
			keyHTML += '</div></div></div>';
			taxaKeyArr[key] = keyHTML;
		}

		function buildTaxaKey(){
			document.getElementById("taxaCountNum").innerHTML = taxaCnt;
			keyHTML = '';
			familyNameArr.sort();
			for(var i=0,l=familyNameArr.length;i<l;i++){
				tempArr = taxaArr[familyNameArr[i]]['sciname_arr'];
				if(tempArr.length > 0){
					tempArr.sort();
					keyHTML += "<div style='margin-left:5px;'><h3 style='margin-top:8px;margin-bottom:5px;'>" + familyNameArr[i] + "</h3></div>";
					keyHTML += "<div style='display:table;'>";
					for(var s = 0, w = tempArr.length; s < w; s++){
						var tidCode = taxaArr[familyNameArr[i]][tempArr[s]];
						if (taxaKeyArr[tidCode]) keyHTML += taxaKeyArr[tidCode];
					}
					keyHTML += "</div>";
				}
			}
			if(taxaArr['undefined']){
				tempArr = taxaArr['undefined']['sciname_arr'];
				if(tempArr.length > 0){
					tempArr.sort();
					keyHTML += "<div style='margin-left:5px;'><h3 style='margin-top:8px;margin-bottom:5px;'>Family Not Defined</h3></div>";
					keyHTML += "<div style='display:table;'>";
					for(var s = 0, w = tempArr.length; s < w; s++){
						var tidCode = taxaArr[familyNameArr[i]][tempArr[s]];
						keyHTML += taxaKeyArr[tidCode];
					}
					keyHTML += "</div>";
				}
			}
			document.getElementById("taxasymbologykeysbox").innerHTML = keyHTML;
		}

		function changeMainKey(gCnt,newColor){
			if(mapSymbol == 'taxa'){
				clearTaxaSymbology();
			}
			changeKeyColor(newColor,grpArr[gCnt]);
			if(clusterOff=="n"){
				if(clusterCollArr[gCnt]){
					clusterCollArr[gCnt].clearMarkers();
				}
				optionsCollArr[gCnt] = {
					styles: [{
						color: newColor
					}],
					maxZoom: 13,
					gridSize: <?php echo $gridSize; ?>,
					minimumClusterSize: <?php echo $minClusterSize; ?>
				};
				clusterCollArr[gCnt] = new MarkerClusterer(map, grpArr[gCnt], optionsCollArr[gCnt]);
			}
		}

		function findSelection(gCnt,id,dir){
			if (grpArr[gCnt]) {
				for (i in grpArr[gCnt]) {
					if(grpArr[gCnt][i].occid==id){
						if(grpArr[gCnt][i].customInfo=='obs'){
							var markerColor = '#'+grpArr[gCnt][i].color;
							if(dir == 'select'){
								var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:markerColor,fillOpacity:1,scale:1,strokeColor:"#10D8E6",strokeWeight:2};
							}
							else if(dir == 'deselect'){
								var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:markerColor,fillOpacity:1,scale:1,strokeColor:"#000000",strokeWeight:1};
							}
							grpArr[gCnt][i].setIcon(markerIcon);
						}
						if(grpArr[gCnt][i].customInfo=='spec'){
							var markerColor = '#'+grpArr[gCnt][i].color;
							if(dir == 'select'){
								var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:markerColor,fillOpacity:1,scale:7,strokeColor:"#10D8E6",strokeWeight:2};
							}
							else if(dir == 'deselect'){
								var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:markerColor,fillOpacity:1,scale:7,strokeColor:"#000000",strokeWeight:1};
							}
							grpArr[gCnt][i].setIcon(markerIcon);
						}
						if(dir == 'select'){
							grpArr[gCnt][i].selected = true;
							selected = true;
						}
						else if(dir == 'deselect'){
							grpArr[gCnt][i].selected = false;
							deselected = true;
						}
						return;
					}
				}
			}
		}

		function findGrpClusterSelection(gCnt,id){
			if(clusterCollArr[gCnt]){
				var clusters = clusterCollArr[gCnt].getClusters();
				for( var i=0, l=clusters.length; i<l; i++ ){
					var selCluster = false;
					var oldHtml = clusters[i].clusterIcon_.div_.innerHTML;
					for( var j=0, le=clusters[i].markers_.length; j<le; j++ ){
						if(clusters[i].markers_[j].selected==true){
							selCluster = true;
						}
					}
					if(selCluster == true){
						var newHtml = oldHtml.replace('></circle>', ' stroke="#10D8E6" stroke-width="3px"></circle>');
					}
					if(selCluster == false){
						var newHtml = oldHtml.replace(' stroke="#10D8E6" stroke-width="3px"></circle>', '></circle>');
					}
					clusters[i].clusterIcon_.div_.innerHTML = newHtml;
				}
			}
		}

		function findTaxClusterSelection(id){
			for(var tid in tidArr) {
				if(clusterTaxArr[tid]){
					var clusters = clusterTaxArr[tid].getClusters();
					for( var i=0, l=clusters.length; i<l; i++ ){
						var selCluster = false;
						var oldHtml = clusters[i].clusterIcon_.div_.innerHTML;
						for( var j=0, le=clusters[i].markers_.length; j<le; j++ ){
							if(clusters[i].markers_[j].selected==true){
								selCluster = true;
							}
						}
						if(selCluster == true){
							var newHtml = oldHtml.replace('></circle>', ' stroke="#10D8E6" stroke-width="3px"></circle>');
						}
						if(selCluster == false){
							var newHtml = oldHtml.replace(' stroke="#10D8E6" stroke-width="3px"></circle>', '></circle>');
						}
						clusters[i].clusterIcon_.div_.innerHTML = newHtml;
					}
				}
			}
		}

		function clearTaxaSymbology(){
			for(var tid in tidArr) {
				if(clusterTaxArr[tid]){
					clusterTaxArr[tid].clearMarkers();
				}
				var keyName = 'taxaColor'+tid;
				if(document.getElementById(keyName)){
					document.getElementById(keyName).color.fromString("E69E67");
				}
			}
		}

		function resetMainSymbology(){
			for(var gcnt in grpArr) {
				changeMainKey(gcnt,"E69E67");
				var keyName = 'keyColor'+gcnt;
				document.getElementById(keyName).color.fromString("E69E67");
			}
		}

		function changeCollColor(color,gcnt){
			changeMainKey(gcnt,color);
			mapSymbol = 'coll';
		}

		function changeTaxaColor(color,tidcode){
			if(mapSymbol == 'coll'){
				resetMainSymbology();
			}
			changeTaxaKey(tidcode,color);
			mapSymbol = 'taxa';
		}

		function changeTaxaKey(tid,newColor){
			if(clusterTaxArr[tid]){
				clusterTaxArr[tid].clearMarkers();
			}
			var tempArr = [];
			for(var gcnt in grpArr) {
				if(grpArr[gcnt]) {
					var newMarkerColor = '#'+newColor;
					for (i in grpArr[gcnt]) {
						if(grpArr[gcnt][i].taxatid == tid){
							if(grpArr[gcnt][i].customInfo=='obs'){
								if(grpArr[gcnt][i].selected==true){
									var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:newMarkerColor,fillOpacity:1,scale:1,strokeColor:"#10D8E6",strokeWeight:2};
								}
								else{
									var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:newMarkerColor,fillOpacity:1,scale:1,strokeColor:"#000000",strokeWeight:1};
								}
								grpArr[gcnt][i].color = newColor;
								grpArr[gcnt][i].setIcon(markerIcon);
							}
							if(grpArr[gcnt][i].customInfo=='spec'){
								if(grpArr[gcnt][i].selected==true){
									var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:newMarkerColor,fillOpacity:1,scale:7,strokeColor:"#10D8E6",strokeWeight:2};
								}
								else{
									var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:newMarkerColor,fillOpacity:1,scale:7,strokeColor:"#000000",strokeWeight:1};
								}
								grpArr[gcnt][i].color = newColor;
								grpArr[gcnt][i].setIcon(markerIcon);
							}
							tempArr.push(grpArr[gcnt][i]);
							if(clusterOff=="n"){
								if(clusterCollArr[gcnt]){
									clusterCollArr[gcnt].removeMarker(grpArr[gcnt][i]);
								}
							}
						}
					}
				}
			}

			if(clusterOff=="n"){
				optionsTaxArr[tid] = {
					styles: [{
						color: newColor
					}],
					maxZoom: 13,
					gridSize: <?php echo $gridSize; ?>,
					minimumClusterSize: <?php echo $minClusterSize; ?>
				};

				clusterTaxArr[tid] = new MarkerClusterer(map, tempArr, optionsTaxArr[tid]);
			}
		}

		function autoColorColl(){
			document.getElementById("randomColorColl").disabled = true;
			if(mapSymbol == 'taxa'){
				clearTaxaSymbology();
			}
			var usedColors = [];
			for(var gcnt in grpArr) {
				var randColor = generateRandColor();
				while (usedColors.indexOf(randColor) > -1) {
					randColor = generateRandColor();
				}
				usedColors.push(randColor);
				changeMainKey(gcnt,randColor);
				var keyName = 'keyColor'+gcnt;
				document.getElementById(keyName).color.fromString(randColor);
			}
			mapSymbol = 'coll';
			document.getElementById("randomColorColl").disabled = false;
		}

		function autoColorTaxa(){
			document.getElementById("randomColorTaxa").disabled = true;
			resetMainSymbology();
			var usedColors = [];
			for(var tid in tidArr) {
				var randColor = generateRandColor();
				while (usedColors.indexOf(randColor) > -1) {
					randColor = generateRandColor();
				}
				usedColors.push(randColor);
				changeTaxaKey(tid,randColor);
				var keyName = 'taxaColor'+tid;
				if(document.getElementById(keyName)){
					document.getElementById(keyName).color.fromString(randColor);
				}
			}
			mapSymbol = 'taxa';
			document.getElementById("randomColorTaxa").disabled = false;
		}

		function resetSymbology(){
			document.getElementById("symbolizeReset1").disabled = true;
			document.getElementById("symbolizeReset2").disabled = true;
			clearTaxaSymbology();
			resetMainSymbology();
			mapSymbol = 'coll';
			document.getElementById("symbolizeReset1").disabled = false;
			document.getElementById("symbolizeReset2").disabled = false;
		}

		/*
		function selectPoints(){
			var selectedpoints = document.getElementById("selectedpoints");
			selected = false;
			var selectedpoint = Number(selectedpoints.value);
			while (selected == false) {
				for(var gcnt in grpArr) {
					findSelection(gcnt,selectedpoint,'select');
					if(clusterOff=="n"){
						findGrpClusterSelection(gcnt,selectedpoint);
					}
				}
				if(clusterOff=="n"){
					findTaxClusterSelection(selectedpoint);
				}
			}
			if(selections.indexOf(selectedpoint) < 0){
				selections.push(selectedpoint);
			}
			adjustSelectionsTab();
		}
		*/

		/*
		function deselectPoints(){
			deselected = false;
			var deselectedpoint = Number(deselectedpoints.value);
			while (deselected == false) {
				for(var gcnt in grpArr) {
					findSelection(gcnt,deselectedpoint,'deselect');
					if(clusterOff=="n"){
						findGrpClusterSelection(gcnt,deselectedpoint);
					}
				}
				if(clusterOff=="n"){
					findTaxClusterSelection(deselectedpoint);
				}
			}
			var index = selections.indexOf(deselectedpoint);
			selections.splice(index, 1);
			adjustSelectionsTab();
		}
		*/

		/*
		function selectDSPoints(){
			selected = false;
			var selectedpoint = Number(selecteddspoints.value);
			while (selected == false) {
				if (dsmarkers) {
					for (i in dsmarkers) {
						if(dsmarkers[i].occid==selectedpoint){
							var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#ffffff",fillOpacity:1,scale:5,strokeColor:"#10D8E6",strokeWeight:2};
							dsmarkers[i].setIcon(markerIcon);
							dsmarkers[i].selected = true;
							selected = true;
						}
					}
				}
			}
			if(dsselections.indexOf(selectedpoint) < 0){
				dsselections.push(selectedpoint);
			}
		}
		*/

		/*
		function deselectDSPoints(){
			deselected = false;
			var deselectedpoint = Number(deselecteddspoints.value);
			while (deselected == false) {
				if (dsmarkers) {
					for (i in dsmarkers) {
						if(dsmarkers[i].occid==deselectedpoint){
							var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#ffffff",fillOpacity:1,scale:5,strokeColor:"#000000",strokeWeight:2};
							dsmarkers[i].setIcon(markerIcon);
							dsmarkers[i].selected = false;
							deselected = true;
						}
					}
				}
			}
			var index = dsselections.indexOf(deselectedpoint);
			dsselections.splice(index, 1);
		}
		*/

		/*
		function zoomToSelections(){
			var selectZoomBounds = new google.maps.LatLngBounds();
			for(var gcnt in grpArr) {
				for (var i=0; i < selections.length; i++) {
					occid = Number(selections[i]);
					if (grpArr[gcnt]) {
						for (j in grpArr[gcnt]) {
							if(grpArr[gcnt][j].occid==occid){
								var markerPos = grpArr[gcnt][j].getPosition();
								selectZoomBounds.extend(markerPos);
							}
						}
					}
				}
			}
			map.fitBounds(selectZoomBounds);
			map.panToBounds(selectZoomBounds);
		}
		*/

		<?php echo ($activateGeolocation?"google.maps.event.addDomListener(window, 'load', getCoords);":""); ?>
	</script>
	<script src="../../js/symb/api.taxonomy.taxasuggest.js?ver=4" type="text/javascript"></script>
</head>
<body style='width:100%;max-width:100%;min-width:500px;' <?php echo (!$activateGeolocation?'onload="initialize();"':''); ?>>
<div data-role="page" id="page1">
	<div role="main" class="ui-content" style="height:400px;">
		<a href="#defaultpanel" style="position:absolute;top:0;left:0;margin-top:0px;z-index:10;padding-top:3px;padding-bottom:3px;text-decoration:none;" data-role="button" data-inline="true" data-icon="bars">Open Search Panel</a>
	</div>
	<div id="defaultpanel" data-role="panel" data-dismissible="false" class="overflow: hidden;" style="width:380px" data-position="left" data-display="overlay" >
		<div class="panel-content">
			<div id="mapinterface">
				<div id="accordion" style="" >
					<?php
					/*
					echo "MySQL Version: ".$mysqlVersion;
					echo $mapManager->hasFullSpatialSupport()?"yes":"no";
					echo "Request: ".json_encode($_REQUEST);
					echo "mapWhere: ".$mapWhere;
					echo "coordArr: ".json_encode($coordArr);
					echo "clusteringOff: ".$clusterOff;
					echo "coordArr: ".$coordArr;
					echo "tIdArr: ".json_encode($tIdArr);
					echo "minLat:".$minLat."maxLat:".$maxLat."minLng:".$minLng."maxLng:".$maxLng;
					*/
					?>
					<h3 style="padding-left:30px;"><?php echo (isset($LANG['SEARCH_CRITERIA'])?$LANG['SEARCH_CRITERIA']:'Search Criteria and Options'); ?></h3>
					<div id="tabs1" style="width:379px;padding:0px;">
						<form name="mapsearchform" id="mapsearchform" data-ajax="false" action="index.php" method="post" onsubmit="return verifyCollForm(this);">
							<ul>
								<li><a href="#searchcollections"><span><?php echo (isset($LANG['COLLECTIONS'])?$LANG['COLLECTIONS']:'Collections'); ?></span></a></li>
								<li><a href="#searchcriteria"><span><?php echo (isset($LANG['CRITERIA'])?$LANG['CRITERIA']:'Criteria'); ?></span></a></li>
								<li><a href="#mapoptions"><span><?php echo (isset($LANG['MAP_OPTIONS'])?$LANG['MAP_OPTIONS']:'Map Options'); ?></span></a></li>
							</ul>
							<div id="searchcollections" style="">
								<div class="mapinterface">
									<?php
									$collList = $mapManager->getFullCollectionList($catId);
									$specArr = (isset($collList['spec'])?$collList['spec']:null);
									$obsArr = (isset($collList['obs'])?$collList['obs']:null);
									if($specArr || $obsArr){
										?>
										<div id="specobsdiv">
											<div style="margin:0px 0px 10px 5px;">
												<input id="dballcb" data-role="none" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" <?php echo (!$mapManager->getSearchTerm('db') || $mapManager->getSearchTerm('db')=='all'?'checked':'') ?> />
										 		<?php echo $LANG['SELECT_DESELECT'].' <a href="misc/collprofiles.php">'.$LANG['ALL_COLLECTIONS'].'</a>'; ?>
											</div>
											<?php
											if($specArr){
												$mapManager->outputFullCollArr($specArr, $catId, false, false);
											}
											if($specArr && $obsArr) echo '<hr style="clear:both;margin:20px 0px;"/>';
											if($obsArr){
												$mapManager->outputFullCollArr($obsArr, $catId, false, false);
											}
											?>
											<div style="clear:both;">&nbsp;</div>
										</div>
										<?php
									}
									?>
								</div>
							</div>
							<div id="searchcriteria" style="">
								<div style="height:25px;">
									<!-- <div style="float:left;<?php echo (isset($SOLR_MODE) && $SOLR_MODE?'display:none;':''); ?>">
										Record Limit:
										<input data-role="none" type="text" id="recordlimit" style="width:75px;" name="recordlimit" value="<?php echo ($recLimit?$recLimit:""); ?>" title="Maximum record amount returned from search." onchange="return checkRecordLimit(this.form);" />
									</div> -->
									<div style="float:right;">
										<input type="hidden" id="selectedpoints" value="" />
										<input type="hidden" id="deselectedpoints" value="" />
										<input type="hidden" id="selecteddspoints" value="" />
										<input type="hidden" id="deselecteddspoints" value="" />
										<input type="hidden" id="gridSizeSetting" name="gridSizeSetting" value="<?php echo $gridSize; ?>" />
										<input type="hidden" id="minClusterSetting" name="minClusterSetting" value="<?php echo $minClusterSize; ?>" />
										<input type="hidden" id="clusterSwitch" name="clusterSwitch" value="<?php echo $clusterOff; ?>" />
										<input type="hidden" id="pointlat" name="pointlat" value='<?php echo $mapManager->getSearchTerm('pointlat'); ?>' />
										<input type="hidden" id="pointlong" name="pointlong" value='<?php echo $mapManager->getSearchTerm('pointlong'); ?>' />
										<input type="hidden" id="radius" name="radius" value='<?php echo $mapManager->getSearchTerm('radius'); ?>' />
										<input type="hidden" id="upperlat" name="upperlat" value='<?php echo $mapManager->getSearchTerm('upperlat'); ?>' />
										<input type="hidden" id="rightlong" name="rightlong" value='<?php echo $mapManager->getSearchTerm('rightlong'); ?>' />
										<input type="hidden" id="bottomlat" name="bottomlat" value='<?php echo $mapManager->getSearchTerm('bottomlat'); ?>' />
										<input type="hidden" id="leftlong" name="leftlong" value='<?php echo $mapManager->getSearchTerm('leftlong'); ?>' />
										<input type="hidden" id="poly_array" name="poly_array" value='<?php echo $mapManager->getSearchTerm('poly_array'); ?>' />
										<button data-role="none" type="button" name="resetbutton" onclick="resetQueryForm(this.form)"><?php echo (isset($LANG['RESET'])?$LANG['RESET']:'Reset'); ?></button>
										<button data-role="none" name="submitform" type="submit" ><?php echo (isset($LANG['SEARCH'])?$LANG['SEARCH']:'Search'); ?></button>
									</div>
								</div>
								<div style="margin:5 0 5 0;"><hr /></div>
								<div>
									<span style=""><input data-role="none" type="checkbox" name="usethes" value="1" <?php if($mapManager->getSearchTerm('usethes') || !$submitForm) echo "CHECKED"; ?> ><?php echo (isset($LANG['INCLUDE_SYNONYMS'])?$LANG['INCLUDE_SYNONYMS']:'Include Synonyms'); ?></span>
								</div>
								<div>
									<div style="margin-top:5px;">
										<select data-role="none" id="taxontype" name="taxontype">
											<?php
											$taxonType = 2;
											if(isset($DEFAULT_TAXON_SEARCH) && $DEFAULT_TAXON_SEARCH) $taxonType = $DEFAULT_TAXON_SEARCH;
											if($mapManager->getSearchTerm('taxontype')) $taxonType = $mapManager->getSearchTerm('taxontype');
											for($h=1;$h<6;$h++){
												echo '<option value="'.$h.'" '.($taxonType==$h?'SELECTED':'').'>'.$LANG['SELECT_1-'.$h].'</option>';
											}
											?>
										</select>
									</div>
									<div style="margin-top:5px;">
										<?php echo (isset($LANG['TAXA'])?$LANG['TAXA']:'Taxa'); ?>:
										<input data-role="none" id="taxa" name="taxa" type="text" style="width:275px;" value="<?php echo $mapManager->getTaxaSearchTerm(); ?>" title="<?php echo (isset($LANG['SEPARATE_MULTIPLE'])?$LANG['SEPARATE_MULTIPLE']:'Separate multiple taxa w/ commas'); ?>" />
									</div>
								</div>
								<div style="margin:5 0 5 0;"><hr /></div>
								<?php
								if($mapManager->getSearchTerm('clid')){
									?>
									<div>
										<div style="clear:both;text-decoration: underline;">Species Checklist:</div>
										<div style="clear:both;margin:5px 0px">
											<?php echo $mapManager->getClName(); ?><br/>
											<input data-role="none" type="hidden" id="checklistname" name="checklistname" value="<?php echo $mapManager->getClName(); ?>" />
											<input id="clid" name="clid" type="hidden"  value="<?php echo $mapManager->getSearchTerm('clid'); ?>" />
										</div>
										<div style="clear:both;margin-top:5px;">
											<div style="float:left">
												Display:
											</div>
											<div style="float:left;margin-left:10px;">
												<input data-role="none" name="cltype" type="radio" value="all" <?php if($mapManager->getSearchTerm('cltype') == 'all') echo 'checked'; ?> />
												all specimens within polygon<br/>
												<input data-role="none" name="cltype" type="radio" value="vouchers" <?php if(!$mapManager->getSearchTerm('cltype') || $mapManager->getSearchTerm('cltype') == 'vouchers') echo 'checked'; ?> />
												vouchers only
											</div>
											<div style="clear: both"></div>
										</div>
									</div>
									<div style="clear:both;margin:0 0 5 0;"><hr /></div>
									<?php
								}
								?>
								<div>
									<?php echo (isset($LANG['COUNTRY'])?$LANG['COUNTRY']:'Country'); ?>: <input data-role="none" type="text" id="country" style="width:225px;" name="country" value="<?php echo $mapManager->getSearchTerm('country'); ?>" title="<?php echo (isset($LANG['SEPARATE_MULTIPLE'])?$LANG['SEPARATE_MULTIPLE']:'Separate multiple taxa w/ commas'); ?>" />
								</div>
								<div style="margin-top:5px;">
									<?php echo (isset($LANG['STATE'])?$LANG['STATE']:'State/Province'); ?>: <input data-role="none" type="text" id="state" style="width:150px;" name="state" value="<?php echo $mapManager->getSearchTerm('state'); ?>" title="<?php echo (isset($LANG['SEPARATE_MULTIPLE'])?$LANG['SEPARATE_MULTIPLE']:'Separate multiple taxa w/ commas'); ?>" />
								</div>
								<div style="margin-top:5px;">
									<?php echo (isset($LANG['COUNTY'])?$LANG['COUNTY']:'County'); ?>: <input data-role="none" type="text" id="county" style="width:225px;"  name="county" value="<?php echo $mapManager->getSearchTerm('county'); ?>" title="<?php echo (isset($LANG['SEPARATE_MULTIPLE'])?$LANG['SEPARATE_MULTIPLE']:'Separate multiple taxa w/ commas'); ?>" />
								</div>
								<div style="margin-top:5px;">
									<?php echo (isset($LANG['LOCALITY'])?$LANG['LOCALITY']:'Locality'); ?>: <input data-role="none" type="text" id="locality" style="width:225px;" name="local" value="<?php echo $mapManager->getSearchTerm('local'); ?>" />
								</div>
								<div style="margin:5 0 5 0;"><hr /></div>
								<div id="shapecriteria">
									<div id="noshapecriteria" style="display:<?php echo ((!$mapManager->getSearchTerm('poly_array') && !$mapManager->getSearchTerm('upperlat'))?'block':'none'); ?>;">
										<div id="geocriteria" style="display:<?php echo ((!$mapManager->getSearchTerm('poly_array') && !$distFromMe && !$mapManager->getSearchTerm('pointlat') && !$mapManager->getSearchTerm('upperlat'))?'block':'none'); ?>;">
											<div>
												<?php echo (isset($LANG['SHAPE_TOOLS'])?$LANG['SHAPE_TOOLS']:'Use the shape tools on the map to select occurrences within a given shape'); ?>.
											</div>
										</div>
										<div id="distancegeocriteria" style="display:<?php echo ($distFromMe?'block':'none'); ?>;">
											<div>
												<?php echo (isset($LANG['WITHIN'])?$LANG['WITHIN']:'Within'); ?>
												 <input data-role="none" type="text" id="distFromMe" style="width:40px;" name="distFromMe" value="<?php $distFromMe; ?>" /> miles from me, or
												<?php echo (isset($LANG['SHAPE_TOOLS'])?strtolower($LANG['SHAPE_TOOLS']):'use the shape tools on the map to select occurrences within a given shape'); ?>.
											</div>
										</div>
									</div>
									<div id="polygeocriteria" style="display:<?php echo (($mapManager->getSearchTerm('polycoords'))?'block':'none'); ?>;">
										<div>
											<?php echo (isset($LANG['WITHIN_POLYGON'])?$LANG['WITHIN_POLYGON']:'Within the selected polygon'); ?>.
										</div>
									</div>
									<div id="circlegeocriteria" style="display:<?php echo (($mapManager->getSearchTerm('pointlat') && !$distFromMe)?'block':'none'); ?>;">
										<div>
											<?php echo (isset($LANG['WITHIN_CIRCLE'])?$LANG['WITHIN_CIRCLE']:'Within the selected circle'); ?>.
										</div>
									</div>
									<div id="rectgeocriteria" style="display:<?php echo ($mapManager->getSearchTerm('upperlat')?'block':'none'); ?>;">
										<div>
											<?php echo (isset($LANG['WITHIN_RECTANGLE'])?$LANG['WITHIN_RECTANGLE']:'Within the selected rectangle'); ?>.
										</div>
									</div>
									<div id="deleteshapediv" style="margin-top:5px;display:<?php echo (($mapManager->getSearchTerm('pointlat') || $mapManager->getSearchTerm('upperlat') || $mapManager->getSearchTerm('polycoords'))?'block':'none'); ?>;">
										<button data-role="none" type=button onclick="deleteSelectedShape()"><?php echo (isset($LANG['DELETE_SHAPE'])?$LANG['DELETE_SHAPE']:'Delete Selected Shape'); ?></button>
									</div>
								</div>
								<div style="margin:5 0 5 0;"><hr /></div>
								<div>
									<?php echo (isset($LANG['COLLECTOR_LASTNAME'])?$LANG['COLLECTOR_LASTNAME']:"Collector's Last Name"); ?>:
									<input data-role="none" type="text" id="collector" style="width:125px;" name="collector" value="<?php echo $mapManager->getSearchTerm('collector'); ?>" title="" />
								</div>
								<div style="margin-top:5px;">
									<?php echo (isset($LANG['COLLECTOR_NUMBER'])?$LANG['COLLECTOR_NUMBER']:"Collector's Number"); ?>:
									<input data-role="none" type="text" id="collnum" style="width:125px;" name="collnum" value="<?php echo $mapManager->getSearchTerm('collnum'); ?>" title="Separate multiple terms by commas and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750" />
								</div>
								<div style="margin-top:5px;">
									<?php echo (isset($LANG['COLLECTOR_DATE'])?$LANG['COLLECTOR_DATE']:'Collection Date'); ?>:
									<input data-role="none" type="text" id="eventdate1" style="width:80px;" name="eventdate1" style="width:100px;" value="<?php echo $mapManager->getSearchTerm('eventdate1'); ?>" title="Single date or start date of range" /> -
									<input data-role="none" type="text" id="eventdate2" style="width:80px;" name="eventdate2" style="width:100px;" value="<?php echo $mapManager->getSearchTerm('eventdate2'); ?>" title="End date of range; leave blank if searching for single date" />
								</div>
								<div style="margin:10 0 10 0;"><hr></div>
								<div>
									<?php echo (isset($LANG['CATALOG_NUMBER'])?$LANG['CATALOG_NUMBER']:'Catalog Number'); ?>:
									<input data-role="none" type="text" id="catnum" style="width:150px;" name="catnum" value="<?php echo $mapManager->getSearchTerm('catnum'); ?>" title="" />
								</div>
								<div style="margin-left:15px;">
									<input data-role="none" name="includeothercatnum" type="checkbox" value="1" checked /> <?php echo (isset($LANG['INCLUDE_OTHER_CATNUM'])?$LANG['INCLUDE_OTHER_CATNUM']:'Include other catalog numbers and GUIDs')?>
								</div>
								<div style="margin-top:10px;">
									<input data-role="none" type='checkbox' name='typestatus' value='1' <?php if($mapManager->getSearchTerm('typestatus')) echo "CHECKED"; ?> >
									 <?php echo (isset($LANG['LIMIT_TO_TYPE'])?$LANG['LIMIT_TO_TYPE']:'Limit to Type Specimens Only'); ?>
								</div>
								<div style="margin-top:5px;">
									<input data-role="none" type='checkbox' name='hasimages' value='1' <?php if($mapManager->getSearchTerm('hasimages')) echo "CHECKED"; ?> >
									 <?php echo (isset($LANG['LIMIT_IMAGES'])?$LANG['LIMIT_IMAGES']:'Limit to Specimens with Images Only'); ?>
								</div>
								<div style="margin-top:5px;">
									<input data-role="none" type='checkbox' name='hasgenetic' value='1' <?php if($mapManager->getSearchTerm('hasgenetic')) echo "CHECKED"; ?> >
									 <?php echo (isset($LANG['LIMIT_GENETIC'])?$LANG['LIMIT_GENETIC']:'Limit to Specimens with Genetic Data Only'); ?>
								</div>
								<div style="margin-top:5px;">
									<input data-role="none" type='checkbox' name='includecult' value='1' <?php if($mapManager->getSearchTerm('includecult')) echo "CHECKED"; ?> >
									 <?php echo (isset($LANG['INCLUDE_CULTIVATED'])?$LANG['INCLUDE_CULTIVATED']:'Include cultivated/captive specimens'); ?>
								</div>
								<div><hr></div>
								<input type="hidden" name="reset" value="1" />
							</div>
						</form>
						<div id="mapoptions" style="">
							<div style="border:1px black solid;margin-top:10px;padding:5px;" >
								<b><?php echo (isset($LANG['CLUSTERING'])?$LANG['CLUSTERING']:'Clustering'); ?></b>
								<div style="margin-top:8px;">
									<div>
										<?php echo (isset($LANG['GRID_SIZE'])?$LANG['GRID_SIZE']:'Grid Size'); ?>:
										 <input name="gridsize" id="gridsize" data-role="none" type="text" value="<?php echo $gridSize; ?>" style="width:50px;" onchange="setClustering();" />
									</div>
									<div>
										<?php echo (isset($LANG['CLUSTER_SIZE'])?$LANG['CLUSTER_SIZE']:'Min. Cluster Size'); ?>:
										 <input name="minclustersize" id="minclustersize" data-role="none" type="text" value="<?php echo $minClusterSize; ?>" style="width:50px;" onchange="setClustering();" />
									</div>
								</div>
								<div style="clear:both;margin-top:8px;">
									<?php echo (isset($LANG['TURN_OFF_CLUSTERING'])?$LANG['TURN_OFF_CLUSTERING']:'Turn Off Clustering'); ?>:
									 <input data-role="none" type="checkbox" id="clusteroff" name="clusteroff" value='1' <?php echo ($clusterOff=="y"?'checked':'') ?> onchange="setClustering();"/>
								</div>
							</div>
							<?php
							if(true){
								?>
								<div style="clear:both;">
									<div style="float:right;margin-top:10px;">
										<button data-role="none" id="refreshCluster" name="refreshCluster" onclick="refreshClustering();" ><?php echo (isset($LANG['REFRESH_MAP'])?$LANG['REFRESH_MAP']:'Refresh Map'); ?></button>
									</div>
								</div>
								<?php
							}
							?>
						</div>
						<form style="display:none;" name="csvcontrolform" id="csvcontrolform" action="csvdownloadhandler.php" method="post" onsubmit="">
							<input data-role="none" name="selectionscsv" id="selectionscsv" type="hidden" value="" />
							<input data-role="none" name="starrcsv" id="starrcsv" type="hidden" value="" />
							<input data-role="none" name="typecsv" id="typecsv" type="hidden" value="" />
							<input data-role="none" name="schema" id="schemacsv" type="hidden" value="" />
							<input data-role="none" name="identifications" id="identificationscsv" type="hidden" value="" />
							<input data-role="none" name="images" id="imagescsv" type="hidden" value="" />
							<input data-role="none" name="format" id="formatcsv" type="hidden" value="" />
							<input data-role="none" name="cset" id="csetcsv" type="hidden" value="" />
							<input data-role="none" name="zip" id="zipcsv" type="hidden" value="" />
							<input data-role="none" name="csvreclimit" id="csvreclimit" type="hidden" value="<?php echo $recLimit; ?>" />
						</form>
					</div>
					<?php
					if($searchVar){
						?>
						<h3 id="recordstaxaheader" style="display:none;padding-left:30px;"><?php echo (isset($LANG['RECORDS_TAXA'])?$LANG['RECORDS_TAXA']:'Records and Taxa'); ?></h3>
						<div id="tabs2" style="display:none;width:379px;padding:0px;">
							<ul>
								<li><a href='occurrencelist.php?<?php echo $searchVar; ?>'><span><?php echo (isset($LANG['RECORDS'])?$LANG['RECORDS']:'Records'); ?></span></a></li>
								<li><a href='#symbology'><span><?php echo (isset($LANG['COLLECTIONS'])?$LANG['COLLECTIONS']:'Collections'); ?></span></a></li>
								<li><a href='#maptaxalist'><span><?php echo (isset($LANG['TAXA_LIST'])?$LANG['TAXA_LIST']:'Taxa List'); ?></span></a></li>
							</ul>
							<div id="symbology" style="">
								<div style="height:40px;margin-bottom:15px;">
									<?php
									if($obsIDs){
										?>
										<div style="float:left;">
											<div>
												<svg xmlns="http://www.w3.org/2000/svg" style="height:15px;width:15px;margin-bottom:-2px;">">
													<g>
														<circle cx="7.5" cy="7.5" r="7" fill="white" stroke="#000000" stroke-width="1px" ></circle>
													</g>
												</svg> = <?php echo (isset($LANG['COLLECTION'])?$LANG['COLLECTION']:'Collection'); ?>
											</div>
											<div style="margin-top:5px;" >
												<svg style="height:14px;width:14px;margin-bottom:-2px;">" xmlns="http://www.w3.org/2000/svg">
													<g>
														<path stroke="#000000" d="m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z" stroke-width="1px" fill="white"/>
													</g>
												</svg> = <?php echo (isset($LANG['OBSERVATION'])?$LANG['OBSERVATION']:'Observation'); ?>
											</div>
										</div>
										<?php
									}
									?>
									<div id="symbolizeResetButt" style='float:right;margin-bottom:5px;' >
										<div>
											<button data-role="none" id="symbolizeReset1" name="symbolizeReset1" onclick='resetSymbology();' ><?php echo (isset($LANG['RESET_SYMBOLOGY'])?$LANG['RESET_SYMBOLOGY']:'Reset Symbology'); ?></button>
										</div>
										<div style="margin-top:5px;">
											<button data-role="none" id="randomColorColl" name="randomColorColl" onclick='autoColorColl();' ><?php echo (isset($LANG['AUTO_COLOR'])?$LANG['AUTO_COLOR']:'Auto Color'); ?></button>
										</div>
									</div>
								</div>
								<div style="margin:5 0 5 0;clear:both;"><hr /></div>
								<div style="" >
									<div style="margin-top:8px;">
										<div style="display:table;">
											<div id="symbologykeysbox"></div>
										</div>
									</div>
								</div>
							</div>
							<div id="maptaxalist" >
								<div style="height:40px;margin-bottom:15px;">
									<?php
									if($obsIDs){
										?>
										<div style="float:left;">
											<div>
												<svg xmlns="http://www.w3.org/2000/svg" style="height:15px;width:15px;margin-bottom:-2px;">">
													<g>
														<circle cx="7.5" cy="7.5" r="7" fill="white" stroke="#000000" stroke-width="1px" ></circle>
													</g>
												</svg> = <?php echo (isset($LANG['COLLECTION'])?$LANG['COLLECTION']:'Collection'); ?>
											</div>
											<div style="margin-top:5px;" >
												<svg style="height:14px;width:14px;margin-bottom:-2px;">" xmlns="http://www.w3.org/2000/svg">
													<g>
														<path stroke="#000000" d="m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z" stroke-width="1px" fill="white"/>
													</g>
												</svg> = <?php echo (isset($LANG['OBSERVATION'])?$LANG['OBSERVATION']:'Observation'); ?>
											</div>
										</div>
										<?php
									}
									?>
									<div id="symbolizeResetButt" style='float:right;margin-bottom:5px;' >
										<div>
											<button data-role="none" id="symbolizeReset2" name="symbolizeReset2" onclick='resetSymbology();' ><?php echo (isset($LANG['RESET_SYMBOLOGY'])?$LANG['RESET_SYMBOLOGY']:'Reset Symbology'); ?></button>
										</div>
										<div style="margin-top:5px;">
											<button data-role="none" id="randomColorTaxa" name="randomColorTaxa" onclick='autoColorTaxa();' ><?php echo (isset($LANG['AUTO_COLOR'])?$LANG['AUTO_COLOR']:'Auto Color'); ?></button>
										</div>
									</div>
								</div>
								<div style="margin:5 0 5 0;clear:both;"><hr /></div>
								<div style='font-weight:bold;'><?php echo (isset($LANG['TAXA_COUNT'])?$LANG['TAXA_COUNT']:'Taxa Count'); ?>: <span id="taxaCountNum">0</span></div>
								<div id="taxasymbologykeysbox"></div>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<!-- <a href="../../index.php" style="position:absolute;top:0;right:0;margin-right:38px;margin-bottom:0px;margin-top:1px;padding-top:3px;padding-bottom:3px;z-index:10;" data-role="button" data-inline="true" >Home</a> -->
			<a href="#" style="position:absolute;top:2;right:0;margin-right:0px;margin-bottom:0px;margin-top:1px;padding-top:3px;padding-bottom:3px;padding-left:20px;z-index:10;height:20px;" data-rel="close" data-role="button" data-theme="a" data-icon="delete" data-inline="true"></a>
		</div><!-- /content wrapper for padding -->
	</div><!-- /defaultpanel -->
</div>
<div id='map' style='width:100%;height:100%;'></div>
<div id="loadingOverlay" data-role="popup" style="width:100%;position:relative;">
	<div id="loadingImage" style="width:100px;height:100px;position:absolute;top:50%;left:50%;margin-top:-50px;margin-left:-50px;">
		<img style="border:0px;width:100px;height:100px;" src="../../images/ajax-loader.gif" />
	</div>
</div>
</body>
</html>
