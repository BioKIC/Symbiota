<?php
include_once('../../config/symbini.php');
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/header.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/header.en.php');
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

//Set default bounding box for portal
$boundLatMin = -90;
$boundLatMax = 90;
$boundLngMin = -180;
$boundLngMax = 180;
$latCen = 41.0;
$longCen = -95.0;
if(!empty($MAPPING_BOUNDARIES)){
	$coorArr = explode(';', $MAPPING_BOUNDARIES);
	if($coorArr && count($coorArr) == 4){
		$boundLatMin = $coorArr[2];
		$boundLatMax = $coorArr[0];
		$boundLngMin = $coorArr[3];
		$boundLngMax = $coorArr[1];
		$latCen = ($boundLatMax + $boundLatMin)/2;
		$longCen = ($boundLngMax + $boundLngMin)/2;
	}
}
$bounds = [ [$boundLatMax, $boundLngMax], [$boundLatMin, $boundLngMin]];

//Gets Coordinates
$coordArr = $mapManager->getCoordinateMap(0,$recLimit);
$taxaArr = [];
$recordArr = [];
$collArr = [];
$defaultColor = "#B2BEB5";

$recordCnt = 0;

foreach ($coordArr as $collName => $coll) {
   //Collect all the collections
   foreach ($coll as $recordId => $record) {
      if($recordId == 'c') continue;

      //Collect all taxon
      if(!array_key_exists($record['tid'], $taxaArr)) {
         $taxaArr[$record['tid']] = [
            'sn' => $record['sn'], 
            'tid' => $record['tid'], 
            'family' => $record['fam'],
            'color' => $coll['c'],
            'records' => [$recordCnt] 
         ];
      } else {
         array_push($taxaArr[$record['tid']]['records'], $recordCnt);
      }

      //Collect all Collections
      if(!array_key_exists($record['collid'], $collArr)) {
         $collArr[$record['collid']] = [
            'name' => $collName,
            'collid' => $record['collid'],
            'color' => $coll['c'],
            'records' => [$recordCnt] 
         ];
      } else {
         array_push($collArr[$record['collid']]['records'], $recordCnt);
      }

      $llstrArr = explode(',', $record['llStr']);
      if(count($llstrArr) != 2) continue;

      //Collect all records
      array_push($recordArr, [
         'id' => $record['id'], 
         'tid' => $record['tid'], 
         'collid' => $record['collid'], 
         'family' => $record['fam'],
         'occid' => $recordId,
         'collname' => $collName, 
         'type' => in_array($record['collid'], $obsIDs)? 'observation':'specimen', 
         'lat' => floatval($llstrArr[0]),
         'lng' => floatval($llstrArr[1]),
      ]);

      $recordCnt++;
   }
}

?>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $DEFAULT_TITLE; ?> - Map Interface</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/symbiota/collections/listdisplay.css" type="text/css" rel="stylesheet" />
	<style type="text/css">
		.panel-content a{ outline-color: transparent; font-size: 12px; font-weight: normal; }
		.ui-front { z-index: 9999999 !important; }
		#map { position: fixed !important; height: 100% !important; width: 100% !important; }
	</style>
	<script src="../../js/jquery-1.10.2.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
	<link href="../../js/jquery-ui/jquery-ui.min.css" type="text/css" rel="Stylesheet" />
	<link href="../../css/jquery.symbiota.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.popupoverlay.js" type="text/javascript"></script>
	<script src="../../js/jscolor/jscolor.js?ver=1" type="text/javascript"></script>
<!---	<script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'&key='.$GOOGLE_MAP_KEY:''); ?>&callback=Function.prototype" ></script> -->
	<script src="../../js/symb/collections.map.index.js?ver=2" type="text/javascript"></script>

	<?php
      if(!empty($leaflet)) {
      include_once($SERVER_ROOT.'/includes/leafletMap.php');
      } else {
      include_once($SERVER_ROOT.'/includes/googleMap.php');
      }
	?>

	<script src="../../js/symb/wktpolygontools.js" type="text/javascript"></script>
	<script src="../../js/symb/MapShapeHelper.js" type="text/javascript"></script>

	<script src="../../js/symb/collections.list.js?ver=1" type="text/javascript"></script>
	<script src="../../js/symb/markerclusterer.js?ver=1" type="text/javascript"></script>
	<script src="../../js/symb/oms.min.js" type="text/javascript"></script>
	<script src="../../js/symb/keydragzoom.js" type="text/javascript"></script>
	<script src="../../js/symb/infobox.js" type="text/javascript"></script>

	<style type="text/css">
		.ui-front {
			z-index: 9999999 !important;
		}

		/* The sidepanel menu */
		.sidepanel {
			resize: horizontal;
			border-left: 2px, solid, black;
			height: 100%;
			width: 380;
			position: fixed;
			z-index: 20;
			top: 0;
			left: 0;
			background-color: #ffffff;
			overflow: hidden;
			transition: width 0.5s;
         transition-timing-function: ease;
		}

		.selectedrecord{
			border: solid thick greenyellow;
			font-weight: bold;
		}

		input[type=color]{
			border: none;
			background: none;
		}
		input[type="color"]::-webkit-color-swatch-wrapper {
			padding: 0;
		}
		input[type="color"]::-webkit-color-swatch {
			border: solid 1px #000; /*change color of the swatch border here*/
		}

		.small_color_input{
			margin: 0,0,-2px,0;
			height: 16px;
			width: 16px;
		}

		.mapGroupLegend{
			list-style-type: none;
  			margin: 0;
  			padding: 0;
		}

		.mapLegendEntry {
			display: grid;
			grid-template-columns: max-content auto;
		}

		.mapLegendEntryInputs {
			grid-column: 1;
		}

		.mapLegendEntryText {
			grid-column: 2;
		}

		table#mapSearchRecordsTable.styledtable tr:nth-child(odd) td{
			background-color: #ffffff;
		}

		#divMapSearchRecords{
			grid-column: 1;
			height: 100%;
		}
		#mapLoadMoreRecords{
			display: none;
		}

		#tabs2Items{
			grid-column: 1;
		}

		#records{
			display: grid;
    		grid-template-columns:	1;
			grid-auto-rows: minmax(min-content, max-content);
			height: 100%;
		}

		#mapSearchDownloadData {
			grid-column: 1;
		}

		#mapSearchRecordsTable {

			font-family:Arial;
			font-size:12px;
		}

		#mapSearchRecordsTable th {
			top: 0;
			position: sticky;
		}

		#tabs2 {
			display:none;
			padding:0px;
			display: block;
			height: 100%;
			/* overflow: scroll; */
      }
      .cluster text {
         text-shadow: 0 0 8px white, 0 0 8px white, 0 0 8px white;
      }
	</style>
	<script type="text/javascript">
      let recordArr = [];
      let taxaMap = [];
      let collArr = [];
      let searchVar = "";
      let map_bounds= [ [90, 180], [-90, -180] ];
      let default_color = "E69E67";
      let puWin;
      let shape;

      const colorChange = new Event("colorchange",  {
         bubbles: true,
         cancelable: true,
         composed: true,
      });

		function showWorking(){
			$('#loadingOverlay').popup('show');
		}

		function hideWorking(){
			$('#loadingOverlay').popup('hide');
      }

      function openIndPU(occId){
         newWindow = window.open('../individual/index.php?occid='+occId,'indspec' + occId,'scrollbars=1,toolbar=0,resizable=1,width=1100,height=800,left=20,top=20');
         if (newWindow.opener == null) newWindow.opener = self;
         setTimeout(function () { newWindow.focus(); }, 0.5);
      }

      function buildPanels() {
			setPanels(true);
			$("#accordion").accordion("option",{active: 1});
         buildTaxaLegend();
         buildCollectionLegend();

         //Calls init again because this html is js rendered
         jscolor.init();
      }

      function legendRow(id, color, innerHTML) {
         return (
            `<div style="display:table-row;">
               <div style="display:table-cell;vertical-align:middle;padding-bottom:5px;" >
                  <input 
                     data-role="none" 
                     id="${id}" 
                     class="color" 
                     onchange="onColorChange(this)"
                     style="cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" 
                     value="${color}"
                  />
               </div>
               <div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>
               <div style="display:table-cell;width:250px;vertical-align:middle;padding-left:8px;">${innerHTML}</div>
            </div>
            <div style="display:table-row;height:8px;"></div>`
         )
      }

      function onColorChange(e) {
         e.dispatchEvent(colorChange)
      }

      function buildTaxaLegend() {
         let taxaHtml = "";
         let taxaArr = Object.values(taxaMap).sort((a, b) => a.family > b.family)
         let prev_family;

         for (let taxa of taxaArr) {
            if(prev_family !== taxa.family) {
              if(taxaHtml) taxaHtml += "</div>";

              taxaHtml += `<div style="margin-left:5px;"><h3>${taxa.family}</h3></div>`;
              taxaHtml += "<div style='display:table;'>";
              prev_family = taxa.family;
            }
            taxaHtml += legendRow(`taxa-${taxa.tid}`, taxa.color, taxa.sn);
         }

         taxaHtml += "</div>";

         document.getElementById("taxasymbologykeysbox").innerHTML = taxaHtml;
         document.getElementById("taxaCountNum").innerHTML = taxaArr.length;
      }

      function changeTaxaColor() {
         for (let key of Object.keys(taxaMap)) {
            const taxa = taxaMap[key];

            document.getElementById(`taxa-${taxa.tid}`).style.backgroundColor = taxa.color;
         }
      }
      
      function buildCollectionLegend() {
         let html = "<div style='display:table;'>";

         for (let coll of Object.values(collArr)) {
            html += legendRow(`coll-${coll.collid}`, coll.color, coll.name);
         }

			document.getElementById("symbologykeysbox").innerHTML = html;

      }

      function changeCollColor() {
         for (let coll of Object.values(collArr)) {
            document.getElementById(`coll-${coll.collid}`).style.backgroundColor = coll.color;
         }
      }

      function setQueryShape(shape) {

         document.getElementById("pointlat").value = '';
         document.getElementById("pointlong").value = '';
         document.getElementById("radius").value = '';
         document.getElementById("upperlat").value = '';
         document.getElementById("leftlong").value = '';
         document.getElementById("bottomlat").value = '';
         document.getElementById("rightlong").value = '';
         document.getElementById("poly_array").value = '';
         document.getElementById("distFromMe").value = '';
         document.getElementById("noshapecriteria").style.display = "block";
         document.getElementById("polygeocriteria").style.display = "none";
         document.getElementById("circlegeocriteria").style.display = "none";
         document.getElementById("rectgeocriteria").style.display = "none";
         document.getElementById("deleteshapediv").style.display = "none";

         if(!shape) return;

         if(shape.type === 'circle') {
            setCircleCoords(shape.radius, shape.center.lat, shape.center.lng);
         } else if(shape.type === 'rectangle') {
            setRectangleCoords(shape.upperLat, shape.lowerLat, shape.leftLng, shape.rightLng);
         } else if (shape.type === 'polygon') {
            setPolyCoords(shape.wkt);
         }
      }

      function setCircleCoords(rad, lat, lng) {
         var radius = (rad/1000)*0.6214;
         document.getElementById("pointlat").value = lat;
         document.getElementById("pointlong").value = lng;
         document.getElementById("radius").value = radius;
         document.getElementById("upperlat").value = '';
         document.getElementById("leftlong").value = '';
         document.getElementById("bottomlat").value = '';
         document.getElementById("rightlong").value = '';
         document.getElementById("poly_array").value = '';
         document.getElementById("distFromMe").value = '';
         document.getElementById("noshapecriteria").style.display = "none";
         document.getElementById("polygeocriteria").style.display = "none";
         document.getElementById("circlegeocriteria").style.display = "block";
         document.getElementById("rectgeocriteria").style.display = "none";
         document.getElementById("deleteshapediv").style.display = "block";
      }

      function setRectangleCoords(upperlat, bottomlat, leftlong, rightlong) {
         document.getElementById("upperlat").value = upperlat;
         document.getElementById("rightlong").value = rightlong;
         document.getElementById("bottomlat").value = bottomlat;
         document.getElementById("leftlong").value = leftlong;
         document.getElementById("pointlat").value = '';
         document.getElementById("pointlong").value = '';
         document.getElementById("radius").value = '';
         document.getElementById("poly_array").value = '';
         document.getElementById("distFromMe").value = '';
         document.getElementById("noshapecriteria").style.display = "none";
         document.getElementById("polygeocriteria").style.display = "none";
         document.getElementById("circlegeocriteria").style.display = "none";
         document.getElementById("rectgeocriteria").style.display = "block";
         document.getElementById("deleteshapediv").style.display = "block";
      }

      function setPolyCoords(wkt) {
         document.getElementById("poly_array").value = wkt;
         document.getElementById("pointlat").value = '';
         document.getElementById("pointlong").value = '';
         document.getElementById("radius").value = '';
         document.getElementById("upperlat").value = '';
         document.getElementById("leftlong").value = '';
         document.getElementById("bottomlat").value = '';
         document.getElementById("rightlong").value = '';
         document.getElementById("distFromMe").value = '';
         document.getElementById("noshapecriteria").style.display = "none";
         document.getElementById("polygeocriteria").style.display = "block";
         document.getElementById("circlegeocriteria").style.display = "none";
         document.getElementById("rectgeocriteria").style.display = "none";
         document.getElementById("deleteshapediv").style.display = "block";
      }

      function leafletInit() { 

         L.DivIcon.CustomColor = L.DivIcon.extend({
            createIcon: function(oldIcon) {
               var icon = L.DivIcon.prototype.createIcon.call(this, oldIcon);
               icon.style.backgroundColor = this.options.color;

               icon.style.textShadow="0 0 8px white, 0 0 8px white, 0 0 8px white";
               icon.style.width="42px";
               icon.style.height="42px";

               icon.style.border=`1px solid ${this.options.mainColor}`;
               return icon;
            }
         })

         let map = new LeafletMap('map')
         map.enableDrawing({
            polyline: false,
            circlemarker: false,
            marker: false,
            drawColor: {opacity: 0.85, fillOpacity: 0.55, color: '#000' }
         }, setQueryShape);

         let cluster = L.markerClusterGroup();
         let clusteroff = false;
         let cluster_type = "taxa";

         let taxaClusters = {}
         let taxaGroups = {};
         let taxaMarkers = {};

         let markers = [];

         let collClusters = {}
         let collGroups = {};
         let collMarkers = {};

         let heatmapLayer;
         let heatmap;

         let color = "B2BEB5";

         map.mapLayer.zoomControl.setPosition('topright');

         function drawPoints() {
            for(let record of recordArr) {
               let marker = (record.type === "specimen"?
                  L.circleMarker([record.lat, record.lng], {
                     radius : 8,
                     color  : '#000000',
                     weight: 2,
                     fillColor: `#${taxaMap[record['tid']].color}`,
                     opacity: 1.0,
                     fillOpacity: 1.0,
                     className: `coll-${record['collid']} taxa-${record['tid']}`
                  }):               
                  L.marker([record.lat, record.lng], {
                     icon: getObservationSvg({
                        color: `#${taxaMap[record['tid']].color}`, 
                        className: `coll-${record['collid']} taxa-${record['tid']}`,
                        size: 30
                     })
                  }))
               .on('click', function() { openIndPU(record.occid) })
               .bindTooltip(`<div>${record.id}</div>`)

               markers.push(marker);

               popluateGroup(taxaMarkers, record['tid'], marker);
               popluateGroup(collMarkers, record['collid'], marker);
            }

            generateGroupLayers(taxaMap, "tid", "taxa", taxaMarkers, taxaClusters, taxaGroups);
            generateGroupLayers(collArr, "collid", "coll", collMarkers, collClusters, collGroups);

            if(heatmap) {
               drawHeatMap();
            } else {
               drawGroup(taxaMap, "tid", taxaClusters, taxaGroups);
            }
         }

         function drawHeatMap() {
            if(!heatmap) return;

            if(heatmapLayer) map.mapLayer.removeLayer(heatmapLayer);

            let radius_input = document.getElementById('heat-radius');
            let minDensityInput = document.getElementById('heat-min-density')
            let maxDensityInput = document.getElementById('heat-max-density')

            var cfg = {
               "radius": (radius_input? parseFloat(radius_input.value): 50) / 100.00,
               "maxOpacity": .9,
               "scaleRadius": true,
               "useLocalExtrema": false,
               latField: 'lat',
               lngField: 'lng',
            };
            heatmapLayer = new HeatmapOverlay(cfg);

            let heatMaxDensity = maxDensityInput? parseInt(maxDensityInput.value) : 3
            let heatMinDensity = minDensityInput? parseInt(minDensityInput.value) : 1

            heatmapLayer.addTo(map.mapLayer);

            heatmapLayer.setData({
               max: heatMaxDensity || 3,
               min: heatMinDensity || 1,
               data: recordArr 
            });
         }

         function popluateGroup(markerGroup, id, marker) {
            if(!markerGroup[id]) {
               markerGroup[id] = [marker]
            } else {
               markerGroup[id].push(marker);
            }
         }

         function generateGroupLayers(group_map, group_id, group_type, markerGroup, clusterGroup, layerGroup) {
            for (let group of Object.values(group_map)) {
               function colorCluster(cluster) {
                  let childCount = cluster.getChildCount();
                  return new L.DivIcon.CustomColor({ 
                     html: `<div style="background-color: #${group_map[group[group_id]].color};"><span>` + childCount + '</span></div>', 
                     className: `marker-cluster ${group_type}-${group[group_id]}`, 
                     iconSize: new L.Point(40, 40),
                     color: `#${group_map[group[group_id]].color}77`,
                     mainColor: `#${group_map[group[group_id]].color}`,
                  });
               }

               let cluster = L.markerClusterGroup({
                  iconCreateFunction: colorCluster 
               });

               cluster.addLayers(markerGroup[group[group_id]])

               clusterGroup[group[group_id]] = cluster;
               layerGroup[group[group_id]] = L.layerGroup(markerGroup[group[group_id]]);
            }
         }

         function drawGroup(group_map, group_id, clusterGroup, layerGroup) {
            for (let group of Object.values(group_map)) {
               if(clusteroff) {
                  layerGroup[group[group_id]].addTo(map.mapLayer);
               } else {
                  clusterGroup[group[group_id]].addTo(map.mapLayer);
               }
            }
         }

         function removeGroup(group_map, group_id, clusterGroup, layerGroup) {
            for (let group of Object.values(group_map)) {
               if(clusteroff) {
                  map.mapLayer.removeLayer(layerGroup[group[group_id]])
               } else {
                  map.mapLayer.removeLayer(clusterGroup[group[group_id]])
               }
            }
         }

         function resetGroup(group_map, markerGroup, clusterGroup, layerGroup) {
            for (let id of Object.keys(group_map)) {
               clusterGroup[id].removeLayers(markerGroup[id]);
               layerGroup[id].clearLayers();
               markerGroup[id] = [];
            }
         }

         function fitMap() {
            if(shape && !map.activeShape) {
               map.drawShape(shape);
            } else if(markers && markers.length > 0) {
               const group = new L.FeatureGroup(markers);
               map.mapLayer.fitBounds(group.getBounds());
            } else if(map_bounds) {
               map.mapLayer.fitBounds(map_bounds);
            }
         }

         document.getElementById("mapsearchform").addEventListener('submit', async e => {
            showWorking();
            e.preventDefault();
            let formData = new FormData(e.target);

            resetGroup(taxaMap, taxaMarkers, taxaClusters, taxaGroups);
            resetGroup(collArr, collMarkers, collClusters, collGroups);

            markers = []; 

            getOccurenceRecords(formData).then(res => {
               if (res) loadOccurenceRecords(res);
            });

            const results = await searchCollections(formData);

            recordArr = results.recordArr? results.recordArr: [];
            taxaMap = results.taxaArr? results.taxaArr: [];
            collArr = results.collArr? results.collArr: [];

            drawPoints();
            fitMap();
            buildPanels();
            hideWorking();
         });

         function removeGroupLayer(id, markerGroup, clusterGroup, layerGroup) {
            if(clusteroff) {
               layerGroup[id].clearLayers()
               if(map.mapLayer.hasLayer(layerGroup[id]))map.mapLayer.removeLayer(layerGroup[id]);
            } else {
               clusterGroup[id].clearLayers();
               if(map.mapLayer.hasLayer(clusterGroup[id])) map.mapLayer.removeLayer(clusterGroup[id]);
            }
         }

         function addGroupLayer(id, markerGroup, clusterGroup, layerGroup) {
            if(clusteroff) {
               layerGroup[id] = L.layerGroup(markerGroup[id]);
               map.mapLayer.addLayer(layerGroup[id]);
            } else {
               clusterGroup[id].addLayers(markerGroup[id])
               map.mapLayer.addLayer(clusterGroup[id]);
            }
         }

         async function updateColor(type, id, color) {
            if(type === "taxa") {
               removeGroupLayer(id, taxaMarkers, taxaClusters, taxaGroups);

               taxaMap[id].color = color;

               const taxa = taxaMap[id]
               for (marker of taxaMarkers[id]) {
                  if(marker.options.icon && marker.options.icon.options.observation) {
                     marker.setIcon(getObservationSvg({color: `#${color}`, size:30 }))
                  } else {
                     marker.options.fillColor =`#${color}` 
                  }
               }

               addGroupLayer(id, taxaMarkers, taxaClusters, taxaGroups);

            } else if (type === "coll") {
               removeGroupLayer(id, collMarkers, collClusters, collGroups);

               collArr[id].color = color;

               for (marker of collMarkers[id]) {
                  if(marker.options.icon && marker.options.icon.options.observation) {
                     marker.setIcon(getObservationSvg({color: `#${color}`, size:30 }))
                  } else {
                     marker.options.fillColor =`#${color}` 
                  }
               }

               addGroupLayer(id, collMarkers, collClusters, collGroups);
            }
         }

         document.addEventListener('colorchange', function(e) {
            const [type, id] = e.target.id.split("-");
            const color = e.target.value;

            updateColor(type, id, color);
         });

         document.addEventListener('autocolor', async function(e) {
            const {type, colorMap} = e.detail;

            if(cluster_type === "coll" && type === "taxa") {
               removeGroup(collArr, "collid", collClusters, collGroups);
            } else if(cluster_type === "taxa" && type === "coll") {
               removeGroup(taxaMap, "tid", taxaClusters, taxaGroups);
            }

            cluster_type = type;

            for (let {id, color} of Object.values(colorMap)) {
               await updateColor(type, id, color);
            }
         });

         document.addEventListener('occur_click', function(e) {
            for (let i = 0; i < markers.length; i++) {
               if(recordArr[i]['occid'] === e.detail.occid) {
                  const current_zoom = map.mapLayer.getZoom()
                  map.mapLayer.setView([recordArr[i]['lat'], recordArr[i]['lng']], current_zoom <= 12? 12: current_zoom)
                  break;
               }
            }
         });

         document.addEventListener('deleteShape', e => {
            clid_input = document.getElementById('clid');
            if(clid_input) clid_input.value = '';

            map.clearMap();
            shape = null;
         });

         function toggleGroupClustering(clusterGroup, layerGroup) {

            for(let id of Object.keys(clusterGroup)) {
               if(clusteroff) {
                  map.mapLayer.removeLayer(clusterGroup[id]);
                  map.mapLayer.addLayer(layerGroup[id]);
               } else {
                  map.mapLayer.removeLayer(layerGroup[id]);
                  clusterGroup[id].addTo(map.mapLayer);
               }
            }
         }
         
         document.getElementById('clusteroff').addEventListener('change', e => {
            clusteroff = e.target.checked;

            if(cluster_type === "taxa") toggleGroupClustering(taxaClusters, taxaGroups);
            else if(cluster_type === "coll") toggleGroupClustering(collClusters, collGroups);
         });

         document.getElementById('heatmap_on').addEventListener('change', e => {
            heatmap = e.target.checked;
            if(e.target.checked) {
               //Clear points 
               if(cluster_type == "taxa") {
                  removeGroup(taxaMap, "tid", taxaClusters, taxaGroups);
               } else if(cluster_type == "coll") {
                  removeGroup(collArr, "collid", collClusters, collGroups);
               }
               drawHeatMap();
            } else {
               map.mapLayer.removeLayer(heatmapLayer);
               if(cluster_type == "taxa") {
                  drawGroup(taxaMap, "tid", taxaClusters, taxaGroups);
               } else if(cluster_type == "coll") {
                  drawGroup(collArr, "collid", collClusters, collGroups);
               }
            }
         });

         document.getElementById('heat-min-density').addEventListener('change', e => drawHeatMap())
         document.getElementById('heat-radius').addEventListener('change', e => drawHeatMap())
         document.getElementById('heat-max-density').addEventListener('change', e => drawHeatMap() )

         //Load Data if any with page Load
         if(recordArr.length > 0) {
            let formData = new FormData(document.getElementById("mapsearchform"));
            drawPoints();
            getOccurenceRecords(formData).then(res => {
               if(res) loadOccurenceRecords(res);
               buildPanels();
            });
         }

         fitMap();
      }

      function googleInit() {
         let map = new GoogleMap('map')

         let taxaClusters = {};
         let taxaMarkers = {};

         let collClusters = {};
         let collMarkers = {};

         let heatmapon = false;
         let heatmapLayer;

         let bounds; 
         let oms;
         let clusteroff = false;

         let cluster_type = "taxa";

         map.enableDrawing({}, setQueryShape);

         //Add polygon bounding function
         if (!google.maps.Polygon.prototype.getBounds) {
            google.maps.Polygon.prototype.getBounds = function () {
               var bounds = new google.maps.LatLngBounds();
               this.getPath().forEach(function (element, index) { bounds.extend(element); });
               return bounds;
            }
         }

         function drawPoints() {
            if(recordArr.length < 1) return;

            bounds = new google.maps.LatLngBounds();

            for(let record of recordArr) {
               let marker = new google.maps.Marker({
                  position: new google.maps.LatLng(record['lat'], record['lng']),
                  text: "Test",
                  //map: map.mapLayer,
                  icon: record['type'] === "specimen"? 
                     {
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: `#${taxaMap[record['tid']].color}`,
                        fillOpacity: 1,
                        scale: 7,
                        strokeColor: "#000000",
                        strokeWeight: 1
                     }: {
                        path: "m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",
                        fillColor: `#${taxaMap[record['tid']].color}`,
                        fillOpacity: 1,
                        scale: 1,
                        strokeColor: "#000000",
                        strokeWeight: 1
                     },
                  selected: false,
                  color: `#${taxaMap[record['tid']].color}`,
               })

               bounds.extend(marker.getPosition());

               const infoWin = new google.maps.InfoWindow({content:`<div>${record.id}</div>`});

               google.maps.event.addListener(marker, 'mouseover', function() {
                  infoWin.open(map.mapLayer, marker); 
               })

               google.maps.event.addListener(marker, 'mouseout', function() {
                  infoWin.close(); 
               })
               
               google.maps.event.addListener(marker, 'click', function() { openIndPU(record.occid)})

               if(!taxaMarkers[record['tid']]) {
                  taxaMarkers[record['tid']] = [marker]
               } else {
                  taxaMarkers[record['tid']].push(marker);
               }

               if(!collMarkers[record['collid']]) {
                  collMarkers[record['collid']] = [marker]
               } else {
                  collMarkers[record['collid']].push(marker);
               }

               if(clusteroff && !heatmapon) {
                  marker.setMap(map.mapLayer)
               }
            }

            if(heatmapon) {
               if(!heatmapLayer) initHeatmap(); 
               else updateHeatmap(); 
            }

            for(let tid of Object.keys(taxaMarkers)) {
               taxaClusters[tid] = new MarkerClusterer(heatmapon || clusteroff? null: map.mapLayer, taxaMarkers[tid],{
                  styles: [{
                     color: taxaMap[tid].color,
                  }],
                  maxZoom: 13,
                  gridSize: 60,
                  minimumClusterSize: 2
               }
               );
            }

            for(let collid of Object.keys(collMarkers)) {
               collClusters[collid] = new MarkerClusterer(null, collMarkers[collid],{
                  styles: [{
                     color: collArr[collid].color,
                  }],
                  maxZoom: 13,
                  gridSize: 60,
                  minimumClusterSize: 2
               }
               );
            }
         }

         function resetGroup(group_map, markerGroup, clusterGroup) {
            for(let id of Object.keys(group_map)) {
               removeGroupMember(id, markerGroup, clusterGroup);
            }
         }

         function removeGroupMember(id, markerGroup, clusterGroup) {
            if(clusterGroup[id]) {
               clusterGroup[id].clearMarkers();
               clusterGroup[id].setMap(null);
            }
         }
         
         function addGroupMember(id, group_map, markerGroup, clusterGroup) {
            if(clusterGroup[id]) {
               clusterGroup[id] = new MarkerClusterer(clusteroff? null: map.mapLayer, markerGroup[id],{
                  styles: [{
                     color: group_map[id].color,
                  }],
                  maxZoom: 13,
                  gridSize: 60,
                  minimumClusterSize: 2
               })

               if(clusteroff) {
                  for(let marker of markerGroup[id]) {
                     marker.setMap(map.mapLayer)
                  }
               }
            }
         }

         function drawGroup(group_map, markerGroup, clusterGroup) {
            for(let id of Object.keys(group_map)) {
               addGroupMember(id, group_map, markerGroup, clusterGroup);
            }
         }

         function fitMap() {
            if(map.activeShape) map.mapLayer.fitBounds(map.activeShape.layer.getBounds())
            else if(bounds) map.mapLayer.fitBounds(bounds);
            else if (map_bounds) { 
               const new_bounds = new google.maps.LatLngBounds()
               new_bounds.extend(new google.maps.LatLng(parseFloat(map_bounds[0][0]), parseFloat(map_bounds[0][1])))
               new_bounds.extend(new google.maps.LatLng(parseFloat(map_bounds[1][0]), parseFloat(map_bounds[1][1])))
               map.mapLayer.fitBounds(new_bounds)
            }
         }

         function initHeatmap() {
            if(!heatmapon) return;

            let radius_input = document.getElementById('heat-radius');

            var cfg = {
               "radius": (radius_input? parseFloat(radius_input.value): 50) / 100.00,
               "maxOpacity": .9,
               "scaleRadius": true,
               "useLocalExtrema": false,
               latField: 'lat',
               lngField: 'lng',
            };
            heatmapLayer = new HeatmapOverlay(map.mapLayer, cfg);

            updateHeatmap();
         }

         function updateHeatmap() {
            let minDensityInput = document.getElementById('heat-min-density')
            let maxDensityInput = document.getElementById('heat-max-density')

            let heatMaxDensity = maxDensityInput? parseInt(maxDensityInput.value) : 3
            let heatMinDensity = minDensityInput? parseInt(minDensityInput.value) : 1

            heatmapLayer.setData({
               max: heatMaxDensity || 3,
               min: heatMinDensity || 1,
               data: recordArr 
            });
         }

         document.getElementById("mapsearchform").addEventListener('submit', async e => {
            showWorking();
            e.preventDefault();
            let formData = new FormData(e.target);

            resetGroup(taxaMap, taxaMarkers, taxaClusters);
            taxaMarkers = {}

            resetGroup(collArr, collMarkers, collClusters);
            collMarkers = {}

            if(heatmapLayer) heatmapLayer.setData({data: []})

            getOccurenceRecords(formData).then(res => {
               if (res) loadOccurenceRecords(res);
            });

            const results = await searchCollections(formData);

            recordArr = results.recordArr? results.recordArr: [];
            taxaMap = results.taxaArr? results.taxaArr: [];
            collArr = results.collArr? results.collArr: [];

            drawPoints();
            fitMap()
            buildPanels();
            hideWorking();
         });

         document.addEventListener('deleteShape', e => {
            clid_input = document.getElementById('clid');
            if(clid_input) clid_input.value = '';

            map.clearMap();
            shape = null;
         });

         document.addEventListener('occur_click', function(e) {
            for (let i = 0; i < recordArr.length; i++) {
               if(recordArr[i]['occid'] === e.detail.occid) {
                  const current_zoom = map.mapLayer.getZoom();
                  map.mapLayer.setCenter(new google.maps.LatLng(recordArr[i]['lat'], recordArr[i]['lng'])) 
                  map.mapLayer.setZoom(current_zoom > 12? current_zoom: 12);
                  break;
               }
            }
         });

         async function updateColor(type, id, color) {
            const updateTypeColor = (id, group_map, markerGroup, clusterGroup) => {
               removeGroupMember(id, markerGroup, clusterGroup);

               group_map[id].color = color;

               for (let marker of markerGroup[id]) {
                  marker.color = `#${color}`
                  marker.icon.fillColor = `#${color}`
               }

               addGroupMember(id, group_map, markerGroup, clusterGroup);
            }

            if(type === "taxa") updateTypeColor(id, taxaMap, taxaMarkers, taxaClusters);
            else if (type === "coll") updateTypeColor(id, collArr, collMarkers, collClusters);
         }

         document.addEventListener('colorchange', function(e) {
            const [type, id] = e.target.id.split("-");
            const color = e.target.value;
            updateColor(type, id, color);
         });

         document.addEventListener('autocolor', async function(e) {
            const {type, colorMap} = e.detail;

            if(cluster_type === "coll" && type === "taxa") {
               resetGroup(collArr, collMarkers, collClusters);
            } else if(cluster_type === "taxa" && type === "coll") {
               resetGroup(taxaMap, taxaMarkers, taxaClusters);
            }

            cluster_type = type;

            for (let {id, color} of Object.values(colorMap)) {
               await updateColor(type, id, color);
            }
         });

         document.getElementById('clusteroff').addEventListener('change', e => {
            clusteroff = e.target.checked;
            function toggleGroupClustering(clusterGroup) {
               for(let cluster of Object.values(clusterGroup)) {
                  if(clusteroff) cluster.setMap(null);
                  else cluster.setMap(map.mapLayer)
               }
            }

            if(cluster_type === "taxa") toggleGroupClustering(taxaClusters);
            else if(cluster_type === "coll") toggleGroupClustering(collClusters);
         });

         document.getElementById('heatmap_on').addEventListener('change', e => {
            heatmapon = e.target.checked;

            if(e.target.checked) {
               //Clear points 
               if(cluster_type == "taxa") {
                  resetGroup(taxaMap, taxaMarkers, taxaClusters);
               } else if(cluster_type == "coll") {
                  resetGroup(collArr, collMarkers, collClusters);
               }
               if(!heatmapLayer) initHeatmap();
               else updateHeatmap();
            } else {
               if(heatmapLayer) {
                  heatmapLayer.setData({data: []})
               };

               if(cluster_type == "taxa") {
                  drawGroup(taxaMap, taxaMarkers, taxaClusters);
               } else if(cluster_type == "coll") {
                  drawGroup(collArr, collMarkers, collClusters);
               }
            }
         });

         document.getElementById('heat-min-density').addEventListener('change', e => updateHeatmap())
         document.getElementById('heat-radius').addEventListener('change', e => {
            if(heatmapLayer) {
               heatmapLayer.cfg.radius = parseFloat(e.target.value) / 100.00;
               updateHeatmap();
            }
         })
         document.getElementById('heat-max-density').addEventListener('change', e => updateHeatmap())

         if(recordArr.length > 0) {
            if(shape) map.drawShape(shape);
            let formData = new FormData(document.getElementById("mapsearchform"));
            drawPoints();
            getOccurenceRecords(formData).then(res => {
               if(res) loadOccurenceRecords(res);
               buildPanels();
            });
         }

         fitMap();
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

      async function searchCollections(body) {
         let response = await fetch('rpc/searchcollections.php', {
            method: "POST",
            credentials: "same-origin",
            body: body 
         })

         return response? await response.json(): { taxaArr: [], collArr: [], recordArr: [] };
      }

      async function getOccurenceRecords(body) {
         let response = await fetch('occurrencelist.php', {
            method: "POST",
            credentials: "same-origin",
            body: body 
         })

         return response? await response.text(): 'Nada';
      }

      function loadOccurenceRecords(html) {
         document.getElementById("occurrencelist").innerHTML = html;

         $('.pagination a').click(async function(e){
            e.preventDefault();
            let response = await fetch(e.target.href, {
               method: "GET",
               credentials: "same-origin",
            })
            loadOccurenceRecords(await response.text())
            return false;
         });
      }

		function resetSymbology(typeMap, type, getId = v => v.id, fullreset) {
         let color_map = {};

			for(var val of Object.values(typeMap) ) {
            color_map[getId(val)] = ({color: default_color, id: getId(val)})

            const colorkey = document.getElementById(`${type}-${getId(val)}`)
            if(colorkey) {
               colorkey.color.fromString(default_color);
            }
         }

         if(fullreset) {
            document.dispatchEvent(new CustomEvent('autocolor', {
               detail: {
                  type: type,
                  colorMap: color_map,
               }
            }));
         }
      }

      const resetCollSymbology = (reset = false) => {
         resetSymbology(collArr, 'coll' ,v => v.collid, reset)
      };

      const resetTaxaSymbology = (reset = false) => {
         resetSymbology(taxaMap, 'taxa', v => v.tid, reset);   
      }

      function autoColor(type, id, usedColors = {}) {
         var randColor = generateRandColor();

         while (usedColors[randColor] !== undefined) {
            randColor = generateRandColor();
         }

         usedColors[randColor] = {color: randColor, id: id };

         const colorkey = document.getElementById(`${type}-${id}`)
         if(colorkey){
            colorkey.color.fromString(randColor);
         }
      }

		function autoColorTaxa(e){
			resetCollSymbology();
			var usedColors = [];

			for(var taxa of Object.values(taxaMap) ) {
            autoColor('taxa', taxa.tid, usedColors)
         }

         document.dispatchEvent(new CustomEvent('autocolor', {
            detail: {
               type: 'taxa',
               colorMap: usedColors,
            }
         }));
      }

		function autoColorColl(color){
         resetTaxaSymbology();
			var usedColors = [];

			for(var coll of Object.values(collArr) ) {
            autoColor('coll', coll.collid, usedColors)
         }

         document.dispatchEvent(new CustomEvent('autocolor', {
            detail: {
               type: 'coll',
               colorMap: usedColors,
            }
         }));
      }

      //This is used in occurrencelist.php which is submodule of this
      function emit_occurrence(occid) {
         document.dispatchEvent(new CustomEvent('occur_click', {
            detail: {
               occid: occid
            }
         }))
      }
      
      function deleteMapShape() {
         document.dispatchEvent(new Event('deleteShape'));
         setQueryShape(shape)
      }

      function initialize() {
         try {
            const data = document.getElementById('service-container');
            map_bounds = JSON.parse(data.getAttribute('data-map-bounds'));
            taxaMap = JSON.parse(data.getAttribute('data-taxa-map'));
            collArr = JSON.parse(data.getAttribute('data-coll-map'));
            recordArr = JSON.parse(data.getAttribute('data-records'));

            searchVar = data.getAttribute('data-search-var');
            if(searchVar) sessionStorage.querystr = searchVar;

            let shapeType;

            if(document.getElementById("pointlat").value) {
               shapeType = "circle"
            } else if(document.getElementById("upperlat").value) {
               shapeType = "rectangle"
            } else if(document.getElementById("poly_array").value) {
               shapeType = "polygon"
            }

            if(shapeType) {
               shape = loadMapShape(shapeType, {
                  polygonLoader: () => document.getElementById("poly_array").value,
                  circleLoader: () => {
                     return {
                        radius: document.getElementById("upperlat").value,
                        radUnits: "km",
                        pointLng: document.getElementById("pointlng").value,
                        pointLat: document.getElementById("pointlat").value
                     }
                  },
                  rectangleLoader: () => {
                     return {
                        upperLat: document.getElementById("upperlat").value,
                        lowerLat: document.getElementById("bottomlat").value,
                        rightLng: document.getElementById("rightlong").value,
                        leftLng: document.getElementById("leftlong").value
                     }
                  }
               })
            }

         } catch(e) {
            alert("Failed to initialize map coordinate data")
         }

         <?php if(!empty($LEAFLET)) { ?> 
            leafletInit();
         <?php } else { ?> 
            googleInit();
         <?php } ?>
      }
	</script>
	<script src="../../js/symb/api.taxonomy.taxasuggest.js?ver=4" type="text/javascript"></script>
</head>
<body style='width:100%;max-width:100%;min-width:500px;' <?php echo (!$activateGeolocation?'onload="initialize();"':''); ?>>
<div 
   id="service-container" 
   data-search-var="<?=htmlspecialchars($searchVar)?>"
   data-map-bounds="<?=htmlspecialchars(json_encode($bounds))?>"
   data-taxa-map="<?=htmlspecialchars(json_encode($taxaArr))?>"
   data-coll-map="<?=htmlspecialchars(json_encode($collArr))?>"
   data-records="<?=htmlspecialchars(json_encode($recordArr))?>"
   class="service-container" 
      />
      <div>
         <button onclick="document.getElementById('defaultpanel').style.width='380px';  " style="position:absolute;top:0;left:0;margin:0px;z-index:10;font-size: 14px;">&#9776; <b>Open Search Panel</b></button>
      </div>
   <div id="defaultpanel" class="sidepanel" style="width:380px">
         <div class="panel-content">
            <span style="position:absolute; top:0.7rem; right:0.7rem; z-index:1">
               <a href="<?php echo htmlspecialchars($CLIENT_ROOT, HTML_SPECIAL_CHARS_FLAGS); ?>/index.php">
						<?php echo (isset($LANG['H_HOME'])?$LANG['H_HOME']:'Home'); ?>
               </a>
         <button onclick="document.getElementById('defaultpanel').style.width='0px'" style="margin-left:1rem">&times</button>
            </span>
			<div id="mapinterface">
				<div id="accordion">
					<h3 style="padding-left:30px;"><?php echo (isset($LANG['SEARCH_CRITERIA'])?$LANG['SEARCH_CRITERIA']:'Search Criteria and Options'); ?></h3>
					<div id="tabs1" style="width:379px;padding:0px;height:100%">
						<form name="mapsearchform" id="mapsearchform" data-ajax="false" action="search.php" method="post" onsubmit="return verifyCollForm(this);">
							<ul>
								<li><a href="#searchcollections"><span><?php echo htmlspecialchars((isset($LANG['COLLECTIONS'])?$LANG['COLLECTIONS']:'Collections'), HTML_SPECIAL_CHARS_FLAGS); ?></span></a></li>
								<li><a href="#searchcriteria"><span><?php echo htmlspecialchars((isset($LANG['CRITERIA'])?$LANG['CRITERIA']:'Criteria'), HTML_SPECIAL_CHARS_FLAGS); ?></span></a></li>
								<li><a href="#mapoptions"><span><?php echo htmlspecialchars((isset($LANG['MAP_OPTIONS'])?$LANG['MAP_OPTIONS']:'Map Options'), HTML_SPECIAL_CHARS_FLAGS); ?></span></a></li>
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
										 		<?php echo $LANG['SELECT_DESELECT'].' <a href="misc/collprofiles.php">' . htmlspecialchars($LANG['ALL_COLLECTIONS'], HTML_SPECIAL_CHARS_FLAGS) . '</a>'; ?>
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
										<input type="hidden" id="poly_array" name="poly_array" value='<?php echo $mapManager->getSearchTerm('polycoords'); ?>' />
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
										<button data-role="none" type=button onclick="deleteMapShape()"><?php echo (isset($LANG['DELETE_SHAPE'])?$LANG['DELETE_SHAPE']:'Delete Selected Shape'); ?></button>
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
                     <fieldset>
                        <legend><?php echo (isset($LANG['CLUSTERING'])?$LANG['CLUSTERING']:'Clustering'); ?></legend>
                        <label><?php echo (isset($LANG['TURN_OFF_CLUSTERING'])?$LANG['TURN_OFF_CLUSTERING']:'Turn Off Clustering'); ?>:</label>
								<input data-role="none" type="checkbox" id="clusteroff" name="clusteroff" value='1' <?php echo ($clusterOff=="y"?'checked':'') ?>/>
                     </fieldset>
                     <br/>
                     <fieldset>
                        <legend><?php echo (isset($LANG['HEATMAP'])?$LANG['HEATMAP']:'Heatmap'); ?></legend>
                        <label><?php echo (isset($LANG['TURN_ON_HEATMAP'])?$LANG['TURN_ON_HEATMAP']:'Turn on heatmap'); ?>:</label>
                        <input data-role="none" type="checkbox" id="heatmap_on" name="heatmap_on" value='1'/>
                        <br/>
                        <span style="display: flex; align-items:center">
                           <label for="heat-radius"><?php echo (isset($LANG['HEAT_RADIUS'])? $LANG['HEAT_RADIUS']:'Radius') ?>: 0.1</label>
                           <input style="margin: 0 1rem;"type="range" value="70" id="heat-radius" name="heat-radius" min="1" max="100">1
                        </span>

                        <label for="heat-min-density"><?php echo (isset($LANG['MIN_DENSITY'])? $LANG['MIN_DENSITY']: 'Minimum Density') ?>: </label>
                        <input style="margin: 0 1rem; width: 5rem;"value="1" id="heat-min-density" name="heat-min-density">

                        <br/>
                        <label for="heat-max-density"><?php echo (isset($LANG['MAX_DENSITY'])?$LANG['MAX_DENSITY']: 'Maximum Density') ?>: </label>
                        <input style="margin: 0 1rem; width: 5rem;"value="3" id="heat-max-density" name="heat-max-density">
                  <br/>
                     </fieldset>
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
						<h3 id="recordstaxaheader" style="display:none;padding-left:30px;"><?php echo (isset($LANG['RECORDS_TAXA'])?$LANG['RECORDS_TAXA']:'Records and Taxa'); ?></h3>
						<div id="tabs2" style="display:none;width:379px;padding:0px;">
							<ul>
								<li><a href='#occurrencelist'><span><?php echo htmlspecialchars((isset($LANG['RECORDS'])?$LANG['RECORDS']:'Records'), HTML_SPECIAL_CHARS_FLAGS); ?></span></a></li>
								<li><a href='#symbology'><span><?php echo htmlspecialchars((isset($LANG['COLLECTIONS'])?$LANG['COLLECTIONS']:'Collections'), HTML_SPECIAL_CHARS_FLAGS); ?></span></a></li>
								<li><a href='#maptaxalist'><span><?php echo htmlspecialchars((isset($LANG['TAXA_LIST'])?$LANG['TAXA_LIST']:'Taxa List'), HTML_SPECIAL_CHARS_FLAGS); ?></span></a></li>
							</ul>
							<div id="occurrencelist" style="">
                        loading...
							</div>
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
									<div id="symbolizeResetButt" style='float:right;margin-bottom:5px;' >
										<div>
											<button data-role="none" id="symbolizeReset1" name="symbolizeReset1" onclick="resetCollSymbology(true);" ><?php echo (isset($LANG['RESET_SYMBOLOGY'])?$LANG['RESET_SYMBOLOGY']:'Reset Symbology'); ?></button>
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
											<button data-role="none" id="symbolizeReset2" name="symbolizeReset2" onclick='resetTaxaSymbology(true);' ><?php echo (isset($LANG['RESET_SYMBOLOGY'])?$LANG['RESET_SYMBOLOGY']:'Reset Symbology'); ?></button>
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
		</div><!-- /content wrapper for padding -->
	</div><!-- /defaultpanel -->
<div id='map' style='width:100%;height:100%;'></div>
<div id="loadingOverlay" data-role="popup" style="width:100%;position:relative;">
	<div id="loadingImage" style="width:100px;height:100px;position:absolute;top:50%;left:50%;margin-top:-50px;margin-left:-50px;">
		<img style="border:0px;width:100px;height:100px;" src="../../images/ajax-loader.gif" />
	</div>
</div>
</body>
</html>