<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/MapSupport.php');
include_once($SERVER_ROOT.'/content/lang/collections/map/staticmaphandler.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$mapManager = new MapSupport();
$taxaList = $mapManager->getTaxaList();

//Set default bounding box for portal
$boundLatMin = -90;
$boundLatMax = 90;
$boundLngMin = -180;
$boundLngMax = 180;
$latCen = 0;
$longCen = 0;

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
$bounds = [$boundLatMax, $boundLngMax, $boundLatMin, $boundLngMin];

//Redirects User if not an Admin
if(!isset($IS_ADMIN) || !$IS_ADMIN || !isset($SYMB_UID) || !$SYMB_UID) {
   header("Location: ". $CLIENT_ROOT . '/index.php');
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> - Static distribution map generator</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/leafletMap.php');
      ?>
<script 
   src="<?php echo $CLIENT_ROOT?>/js/dom-to-image/dist/dom-to-image.min.js"
   type="text/javascript">
</script>
	<script type="text/javascript">
         let map;
         let coordLayer;

         async function getTaxaCoordinates(tid, bounds) {
            let bounds_str = encodeURI(`${bounds[0][0]};${bounds[0][1]};${bounds[1][0]};${bounds[1][1]}`)

            const response = await fetch(`rpc/getCoordinates.php?tid=${tid}&bounds=${bounds_str}`, {
               method: "GET",
               credentials: "same-origin",
               headers: {"Content-Type": "application/json"},
            });
            return await response.json();
         }

         async function getTaxaList(scinames) {
            const response = await fetch(`rpc/getTaxa.php?scinames=${scinames}`, {
               method: "GET",
               credentials: "same-origin",
               headers: {"Content-Type": "application/json"},
            });
            return await response.json();
         }

         async function buildMaps(preview = true) {
            //Clear Old Layer if It Exists
            if(coordLayer) map.mapLayer.removeLayer(coordLayer);

            let mapProgress = document.getElementById("map-generation-progress");

            let thumbnailResults = document.getElementById("thumbnail-results");
            if(thumbnailResults) thumbnailResults.style.display = preview? "none":"block";

            let resultsTBody = document.getElementById("thumbnail-results-body");
            if(resultsTBody) resultsTBody.innerHTML = "";

            const data = document.getElementById('service-container');

            let taxon_groups = []; 

            const leafletControls = document.querySelector('.leaflet-control-container')
            leafletControls.style.display = "none";

            const taxa = document.getElementById('taxa').value;

            if(taxa) taxon_groups = await getTaxaList(taxa);

            if(!preview) mapProgress.style.display = "block";

            let maptype;
            for (let maptype_option of document.getElementsByName("maptype"))  {
               if(maptype_option && maptype_option.checked) {
                  maptype = maptype_option.value;
                  break;
               }
            }

            let maxCount = taxon_groups.reduce((max, tg) => max + tg.taxa_list.length, 0);

            document.getElementById('loading-bar-max').innerHTML = `/ ${maxCount}`; 
            let autoSnap = document.getElementById('auto-snap-coords').checked;

            let basebounds = getMapBounds();
            let userZoom = map.mapLayer.getZoom();
            let baseZoom = userZoom >= 7 ? userZoom: 7;
            let count = 0;

            for (let taxon_group of taxon_groups) {
               for (let taxa of taxon_group.taxa_list) {
                  let coords = await getTaxaCoordinates(taxa.tid, basebounds);
                  count++;

                  if(coords && coords.length > 0) { 
                     coordLayer = generateMap({maptype, coordinates: coords});
                     if(autoSnap) {
                        //Fits bounds within our search bounds for a better image
                        map.mapLayer.fitBounds(coords.map(c => [c.lat, c.lng]));

                        //bounds need time before adjusting the zoom
                        await new Promise(r => setTimeout(r, 100));

                        //Scale Back the zoom value if zoomed in too much
                        let newZoom = map.mapLayer.getZoom()
                        map.mapLayer.setZoom(newZoom <= baseZoom? newZoom: baseZoom)
                     }

                     if(preview) break;

                     if(!preview) {
                        //Wait for Map to Render and Pan to Points
                        await new Promise(r => setTimeout(r, 1000));

                        let map_blob = await getMapImage(); 
                        map.mapLayer.removeLayer(coordLayer);
                        postImage({
                           tid: taxa.tid, 
                           title: taxa.sciname, 
                           map_blob,
                           maptype, 
                        }).then(res => addResultTableEntry({
                              tid: taxa.tid, 
                              taxon: taxa.sciname, 
                              status: res.status === 200?
                                 `<?php echo isset($LANG['SUCCESS'])? $LANG['SUCCESS']: 'Success'?>`:
                                 `<?php echo isset($LANG['FAILURE'])? $LANG['Failure']: 'Failure'?>`
                           }))
                     } 
                  } else if (preview && count >= taxon_group.taxa_list.length) {
                     alert(`There are no records of ${taxa.scimane} within your bounds!`)
                  } 

                  if(!preview) {
                     incrementLoadingBar(maxCount);
                     if(coords.length <= 0) {
                        addResultTableEntry({tid: taxa.tid, taxon: taxa.sciname, status: "<?php echo isset($LANG['NO_COORDINATES'])? $LANG['NO_COORDINATES']: 'No coordinates to map'?>"});
                     }
                  }
               }
            }
            if(preview) {
               setTimeout(() => setBoundInputs(basebounds[0], basebounds[1]), 500);
            } else {
               setTimeout(() => map.mapLayer.fitBounds(basebounds), 500);
            }

            //Turn Controls back on when done processing maps
            leafletControls.style.display = "block";
            mapProgress.style.display = "none";
         }

         async function getMapImage() {
            return await domtoimage.toBlob(document.getElementById('map'), {
               height: 500,
               width: 500
            });
         }

         async function postImage({tid, title, maptype, map_blob}) {
            let formData = new FormData();
            formData.append('mapupload', map_blob, `map.png`)
            formData.append('tid', tid)
            formData.append('title', title)
            formData.append('maptype', maptype)
            //tid, title, maptype
            return fetch('rpc/postMap.php', {
               method: "POST",
               credentials: "same-origin",
               body: formData
            })
         }

         function generateMap({maptype, coordinates}) {
            return maptype === "dotmap"?
               buildDotMap(coordinates):
               buildHeatMap(coordinates);
         }

         function addResultTableEntry(row) {
            let resultsTBody = document.getElementById("thumbnail-results-body");

            if(!resultsTBody) return;
            const rowTemplate = document.createElement("template");
rowTemplate.innerHTML = `<tr><td><a target="_blank" href=\"<?php echo $CLIENT_ROOT ?>/taxa/index.php?tid=${row.tid}\">${row.tid}<a></td><td>${row.taxon}</td><td>${row.status}</td></tr>`

            resultsTBody.appendChild(rowTemplate.content.cloneNode(true));
         }

         function buildHeatMap(coordinates) {
            //Input is between 1 and 100 and needs to be translated between 0.1
            //and 1
            let radiusInput = document.getElementById("heat-radius").value;
            let heatMaxDensity = document.getElementById("heat-max-density").value;
            let heatMinDensity = document.getElementById("heat-min-density").value;

            var cfg = {
               "radius": parseFloat(radiusInput / 100),
               "maxOpacity": .9,
               "scaleRadius": true,
               "useLocalExtrema": false,
               latField: 'lat',
               lngField: 'lng',
            };
            let heatmapLayer = new HeatmapOverlay(cfg);

            heatmapLayer.addTo(map.mapLayer);

            heatmapLayer.setData({
               max: parseInt(heatMaxDensity) || 3,
               min: parseInt(heatMinDensity) || 1,
               data: coordinates
            });
 
            return heatmapLayer;
         }

         function buildDotMap(coordinates) {
            const color_input = document.getElementById("dot-color");

            const markerGroup = L.featureGroup(coordinates.map(coord =>  {
               return L.circleMarker([coord.lat, coord.lng], {
                     radius : 8,
                     color  : '#000000',
                     fillColor: `#${color_input.value? color_input.value: 'B2BEB5'}`,
                     weight: 2,
                     opacity: 1.0,
                     fillOpacity: 1.0
               });
            })).addTo(map.mapLayer);

            return markerGroup;
         }

         async function incrementLoadingBar(maxCount) {
            let count = parseInt(document.getElementById('loading-bar-count').innerHTML) + 1;
            document.getElementById('loading-bar-count').innerHTML = count; 

            let new_percent = (count / maxCount) * 100;
            document.getElementById('loading-bar').style.width = `${new_percent}%`;

            if(count === maxCount) {
               document.getElementById('loading-bar').style.width = `0%`;
               document.getElementById('loading-bar-count').innerHTML = 0; 
            } 
         }

         function updateMapBounds(new_bounds) {
            map.mapLayer.fitBounds(new_bounds);
         }

         function refreshBoundInputs() {
            mapBounds = map.mapLayer.getBounds();
            let northEast = mapBounds.getNorthEast();
            let southWest = mapBounds.getSouthWest();
            setBoundInputs([northEast.lat, northEast.lng], [southWest.lat, southWest.lng]);
         }

         function setBoundInputs(upperBound, lowerBound) {
            function bindValue(value, absLimit) {
               const sign = value > 0? 1: -1;
               return (sign * value) > absLimit? (-1 * sign * absLimit) + (value - (sign * absLimit)): value;
            }
            const lat = 0;
            const lng = 1;

            document.getElementById("upper_lat").value = bindValue(upperBound[lat].toFixed(6), 90);
            document.getElementById("upper_lng").value = bindValue(upperBound[lng].toFixed(6), 180);

            document.getElementById("lower_lat").value = bindValue(lowerBound[lat].toFixed(6), 90);
            document.getElementById("lower_lng").value = bindValue(lowerBound[lng].toFixed(6), 180);
         }

         function getMapBounds() { 
            return [
               [parseFloat(document.getElementById("upper_lat").value), parseFloat(document.getElementById("upper_lng").value)],
               [parseFloat(document.getElementById("lower_lat").value), parseFloat(document.getElementById("lower_lng").value)]
            ];
         }

         function getState() {
            const data = document.getElementById('service-container');
            let latlng = [
               parseFloat(data.getAttribute('data-lat')),
               parseFloat(data.getAttribute('data-lng'))
            ]

            let bounds = JSON.parse(
               document.getElementById("service-container")
                  .getAttribute("data-bounds")
            );

            bounds = bounds.map(b => parseFloat(b));
            bounds = [
               [bounds[0], bounds [1]],
               [bounds[2], bounds [3]]
            ];

            return { bounds, latlng };
         }

         function resetBounds(bounds) {
            const state = getState();
            if(state.latlng[0] === 0 && state.latlng[1] === 0) {
               setGlobalBounds();
            } else {
               updateMapBounds(bounds);
               refreshBoundInputs();
            }
         }

         function setGlobalBounds() {
            map.mapLayer.setView([0,0], 1)
         }

         function initialize() {
            const state = getState();

            map = new LeafletMap('map', {
               center: state.latlng, 
               zoom: state.latlng[0] === 0 && state.latlng[0] === 0? 1 : 6, 
               scale: false, 
               lang: "<?php echo $LANG_TAG; ?>"
            });

            let drawControl = new L.Control.Draw({
               draw: {
                  ...map.DEFAULT_DRAW_OPTIONS,
                  marker: false,
                  polygon: false,
                  circle: false,
                  circle: false,
                  rectangle: true,
               }
            });

			   map.mapLayer.addControl(drawControl);

            map.mapLayer.on('draw:created', function(e) {
               if(e.layerType === "rectangle") {
                  updateMapBounds(e.layer.getBounds());
                  refreshBoundInputs();
               }
            })

            let inputChanged = false;

            map.mapLayer.on('dragend', () => refreshBoundInputs())
            map.mapLayer.on('zoom', (e) => {
               if(!inputChanged) refreshBoundInputs()
               else inputChanged = false;
            })

            document.getElementById("upper_lat").addEventListener("input", e => {
               inputChanged = true;
               let lat = parseFloat(e.target.value);
               if(lat <= 90 && lat >= -90) {
                  let new_bounds = getMapBounds()
                  new_bounds[0][0] = lat;
                  updateMapBounds(new_bounds);
               }
            })
            document.getElementById("upper_lng").addEventListener("input", e => {
               inputChanged = true;
               let lng = parseFloat(e.target.value);
               if(lng <= 180 && lng >= -180) { 
                  let new_bounds = getMapBounds()
                  new_bounds[0][1] = lng;
                  updateMapBounds(new_bounds);
               }
            })
            document.getElementById("lower_lat").addEventListener("input", e => {
               inputChanged = true;
               let lat = parseFloat(e.target.value);
               if(lat <= 90 && lat >= -90) {
                  let new_bounds = getMapBounds()
                  new_bounds[1][0] = lat;
                  updateMapBounds(new_bounds);
               }
            })
            document.getElementById("lower_lng").addEventListener("input", e => {
               inputChanged = true;
               let lng = parseFloat(e.target.value);
               if(lng <= 180 && lng >= -180) { 
                  let new_bounds = getMapBounds()
                  new_bounds[1][1] = lng;
                  updateMapBounds(new_bounds);
               }
            })
         }
	</script>
   <link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../../js/symb/api.taxonomy.taxasuggest.js?ver=4" type="text/javascript"></script>
	<script src="../../js/jscolor/jscolor.js?ver=1" type="text/javascript"></script>
   <style>
      #thumbnail-results th {
         text-align: left;
      }
   </style>
</head>
   <body onload="initialize()">
      <?php include($SERVER_ROOT . '/includes/header.php');?>
      <div id="service-container"
         data-taxa-list="<?= htmlspecialchars(json_encode($taxaList))?>"
         data-bounds="<?= htmlspecialchars(json_encode($bounds))?>"
         data-lat="<?= htmlspecialchars($latCen)?>"
         data-lng="<?= htmlspecialchars($longCen)?>"
      ></div>
      <div role="main" id="innertext">
         <h1 class="page-heading">Static distribution map generator</h1>
         <div style="display:flex; justify-content:center">
            <div id="map" style="width:50rem;height:50rem;"></div>
         </div>
         <br/>
         <div id="map-generation-progress" style="display: none">
            <div style="background-color:#E9E9ED">
               <div id="loading-bar" style="height:2rem; width:0%; background-color:#1B3D2F"></div>
            </div>
            <div style="text-align: center; padding-top:0.5rem">
               <?php echo $LANG['MAPS_GENERATED'] ?>
               <span id="loading-bar-count">0</span>
               <span id="loading-bar-max">/ <?php echo count($taxaList)?></span>
            </div>
         </div>
         <form id="thumbnailBuilder" name="thumbnailBuilder" method="post" action="">

            <label for="taxa"><?php echo $LANG['TYPE_TAXON'] ?>:</label>
            <input id="taxa" type="text" size="60" name="taxa" id="taxa" value="" title="<?php echo $LANG['SEPARATE_MULTIPLE']; ?>" /><br/><br/>

            <fieldset>
               <legend><?php echo $LANG['MAP_TYPE'] ?></legend>
               <input type="radio" name="maptype" id ="heatmap" value="heatmap" checked />
               <label for="heatmap">
                  <?php echo $LANG['HEAT_MAP'] ?>
                  <span id="heat-radius-container" style="display: flex; align-items:center">
                     <label for="heat-radius"><?php echo $LANG['HEAT_RADIUS'] ?>: 0.1</label>
                     <input style="margin: 0 1rem;"type="range" value="70" id="heat-radius" name="heat-radius" min="1" max="100">1
                  </span>
                  <label for="heat-min-density"><?php echo $LANG['MIN_DENSITY'] ?>: </label>
                  <input style="margin: 0 1rem; width: 5rem;"value="1" id="heat-min-density" name="heat-min-density">
                  <label for="heat-max-density"><?php echo $LANG['MAX_DENSITY'] ?>: </label>
                  <input style="margin: 0 1rem; width: 5rem;"value="3" id="heat-max-density" name="heat-max-density">
                  <br/>
               </label><br/>
            <input type="radio" name="maptype" id ="dotmap" value="dotmap"/>
               <label for="dotmap">
                  <?php echo $LANG['DOT_MAP'] ?>:
                  <div style="display:flex">
                     <label for="dotColor"><?php echo $LANG['COLOR'] ?>: </label>
                     <input data-role="none" id="dot-color" name="dot-color" class="color" style="margin-left: 0.5rem;cursor:pointer;border:1px black solid;height:12px;width:12px;margin-bottom:-2px;font-size:0px;" value="B2BEB5"/>
                  </div>
               </label><br/>
            </fieldset><br/>
            <fieldset>
               <legend><?php echo $LANG['BOUNDS'] ?></legend>
               <?php echo $LANG['UPPER_BOUND'] ?><br/>
               <label for="upper_lat"><?php echo $LANG['LATITUDE'] ?></label>
               <input id="upper_lat" name="upper_lat" onkeydown="return event.key != 'Enter';" value="<?php echo $boundLatMax?>" placeholder="<?php echo $boundLatMax?>"/>
               <label for="upper_lng"><?php echo $LANG['LONGITUDE'] ?></label>
               <input id="upper_lng" name="upper_lng" onkeydown="return event.key != 'Enter';" value="<?php echo $boundLngMax?>" placeholder="<?php echo $boundLngMax?>"/><br>

               <?php echo $LANG['LOWER_BOUND'] ?><br/>
               <label for="lower_lat"><?php echo $LANG['LATITUDE'] ?></label>
               <input id="lower_lat" name="lower_lat"onkeydown="return event.key != 'Enter';" value="<?php echo $boundLatMin?>" placeholder="<?php echo $boundLatMin?>"/>
               <label for="lower_lng"><?php echo $LANG['LONGITUDE'] ?></label>
               <input id="lower_lng" name="lower_lat" onkeydown="return event.key != 'Enter';" value="<?php echo $boundLngMin?>" placeholder="<?php echo $boundLngMin?>"/><br>
               <button type="button" onclick="resetBounds(getState().bounds)"><?php echo $LANG['RESET_BOUNDS'] ?></button>
               <button type="button" onclick="setGlobalBounds()"><?php echo $LANG['GLOBAL_BOUNDS'] ?></button><br/>
            </fieldset><br/>
            <div style="margin-bottom:1rem">
               <input type="checkbox" name="auto-snap-coords" id="auto-snap-coords" value="true" >
               <label for="auto-snap-coords"><?php echo $LANG['AUTOMATIC_BOUNDS_DESC'] ?></label>
            </div>
            <button type="button" onclick="buildMaps(false)"><?= $LANG['BUILD_MAPS'] ?></button>
            <button type="button" onclick="buildMaps(true)"><?= $LANG['PREVIEW_MAP'] ?></button>
         </form>
         <br/>
         <fieldset id="thumbnail-results" style="display: none">
            <legend><?php echo isset($LANG['RESULTS'])? $LANG['RESULTS']: 'Results'?></legend>
            <table style="width: 100%">
               <thead>
                  <th><?php echo isset($LANG['TID'])? $LANG['TID']: 'Tid'?></th>
                  <th><?php echo isset($LANG['TAXON'])? $LANG['TAXON']: 'Taxon'?></th>
                  <th><?php echo isset($LANG['STATUS'])? $LANG['STATUS']: 'Status'?></th>
               </thead>
               <tbody id="thumbnail-results-body">
               </tbody>
            </table>
         </fieldset>
      </div>
      <?php include($SERVER_ROOT . '/includes/footer.php');?>
   </body>
</html>

