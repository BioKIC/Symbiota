<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMapManager.php');
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/collections/map/index.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/map/index.en.php');
else include_once($SERVER_ROOT . '/content/lang/collections/map/index.' . $LANG_TAG . '.php');

header('Content-Type: text/html; charset='.$CHARSET);
header("Accept-Encoding: gzip, deflate, br");
ob_start('ob_gzhandler');
ini_set('max_execution_time', 180); //180 seconds = 3 minutes

$distFromMe = array_key_exists('distFromMe', $_REQUEST)?$_REQUEST['distFromMe']:'';
$gridSize = array_key_exists('gridSizeSetting', $_REQUEST) && $_REQUEST['gridSizeSetting']?$_REQUEST['gridSizeSetting']:60;
$minClusterSize = array_key_exists('minClusterSetting',$_REQUEST)&&$_REQUEST['minClusterSetting']?$_REQUEST['minClusterSetting']:10;
$clusterOff = array_key_exists('clusterSwitch',$_REQUEST)&&$_REQUEST['clusterSwitch']? $_REQUEST['clusterSwitch']:'y';
$menuClosed = array_key_exists('menuClosed',$_REQUEST)? true: false;
$recLimit = array_key_exists('recordlimit',$_REQUEST)?$_REQUEST['recordlimit']:15000;
$catId = array_key_exists('catid',$_REQUEST)?$_REQUEST['catid']:0;
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;
$submitForm = array_key_exists('submitform',$_REQUEST)?$_REQUEST['submitform']:'';

$shouldUseMinimalMapHeader = $SHOULD_USE_MINIMAL_MAP_HEADER ?? false;
$topVal = $shouldUseMinimalMapHeader ? '6rem' : '0';

if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;

$mapManager = new OccurrenceMapManager();
$searchVar = $mapManager->getQueryTermStr();
if($searchVar && $recLimit) $searchVar .= '&reclimit='.$recLimit;

$obsIDs = $mapManager->getObservationIds();

//Sanitation
if(!is_numeric($gridSize)) $gridSize = 60;
if(!is_numeric($minClusterSize)) $minClusterSize = 10;
if(!is_string($clusterOff) || strlen($clusterOff) > 1) $clusterOff = 'y';
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
$bounds = [ [$boundLatMax, $boundLngMax], [$boundLatMin, $boundLngMin] ];

//Gets Coordinates
$coordArr = $mapManager->getCoordinateMap(0,$recLimit);
$taxaArr = [];
$recordArr = [];
$collArr = [];
$defaultColor = "#B2BEB5";

$recordCnt = 0;

if(empty($EXTERNAL_PORTAL_HOSTS)) {
	$EXTERNAL_PORTAL_HOSTS = [];
}

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

if(isset($_REQUEST['llpoint'])) {
   $llpoint = explode(";", $_REQUEST['llpoint']);
   if(count($llpoint) === 4) {
      $pointLat = $llpoint[0];
      $pointLng = $llpoint[1];
      $pointRad = $llpoint[2];
      $pointUnit = $llpoint[3];
   }
} elseif(isset($_REQUEST['llbound'])) {
   $llbound = explode(";", $_REQUEST['llbound']);
   if(count($llbound) === 4) {
      $upperLat= $llbound[0];
      $lowerLat= $llbound[1];
      $upperLng= $llbound[2];
      $lowerLng = $llbound[3];
   }
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo $DEFAULT_TITLE; ?> - Map Interface</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<link href="<?= $CSS_BASE_PATH; ?>/symbiota/collections/listdisplay.css" type="text/css" rel="stylesheet" />
		<link href="<?= $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<style type="text/css">
			.panel-content a{ outline-color: transparent; font-size: .9rem; font-weight: normal; }
			.ui-front { z-index: 9999999 !important; }
			#cross_portal_record_label {
				display: none;
			}
		</style>
		<script src="<?= $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?= $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
		<link href="<?= $CSS_BASE_PATH ?>/symbiota/collections/sharedCollectionStyling.css" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery.symbiota.css" type="text/css" rel="stylesheet" />
		<script src="../../js/jquery.popupoverlay.js" type="text/javascript"></script>
		<script src="../../js/jscolor/jscolor.js?ver=1" type="text/javascript"></script>
		<!---	<script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?= (!empty($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY != 'DEV' ? 'key=' . $GOOGLE_MAP_KEY : '') ?>&callback=Function.prototype" ></script> -->
		<script src="../../js/symb/collections.map.index.js?ver=2" type="text/javascript"></script>

		<?php
		if(empty($GOOGLE_MAP_KEY)) {
			include_once($SERVER_ROOT.'/includes/leafletMap.php');
		} else {
			include_once($SERVER_ROOT.'/includes/googleMap.php');
		}
		?>

		<script src="../../js/symb/wktpolygontools.js" type="text/javascript"></script>
		<script src="../../js/symb/MapShapeHelper.js" type="text/javascript"></script>
		<script src="../../js/symb/localitySuggest.js" type="text/javascript"></script>
		<script src="../../js/symb/collections.list.js?ver=2" type="text/javascript"></script>

		<style type="text/css">
		.ui-front {
			z-index: 9999999 !important;
		}

		/* The sidepanel menu */
		.sidepanel {
			resize: horizontal;
			border-left: 2px, solid, black;
			height: 100%;
			width: 29rem;
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

		<?php if($shouldUseMinimalMapHeader){ ?>
			.leaflet-top {
				top: <?php echo $topVal; ?>;
				margin-top: 0px;
			}
			.leaflet-top .leaflet-control {
				margin-top: 0px;
			}
		<?php } ?>
		</style>
		<script type="text/javascript">
		//Clid
		let recordArr = [];
		let taxaMap = [];
		let collArr = [];
		let searchVar = "";
		let default_color = "E69E67";
		let cluster_radius;

		//Map Globals
		let shape;
		let map_bounds= [ [90, 180], [-90, -180] ];

		//Array for holding all search results from current and outside portals
		let mapGroups = [];

		//Array holding all external portals included in the map search
		let externalPortalhosts = [];

		//Object that maps taxa to matching mapGroup Index
		let taxaLegendMap = {}
		//Object that maps collections to matching mapGroup Index
		let collLegendMap = {}
		//Object that maps portals to matching mapGroup Index
		let portalLegendMap = {}

		//Indciates if clustering should be drawn. Only comes into effect after redraw or refreshes
		let clusteroff = true;

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

		function buildPanels(cross_portal_enabled) {
         const cross_portal_results = document.getElementById("cross_portal_results");
         const cross_portal_list = document.getElementById("cross_portal_list");
         const record_label = document.getElementById("standard_record_label");
         const cross_portal_record_label = document.getElementById("cross_portal_record_label");
         if(cross_portal_results) {
            if(cross_portal_enabled) {
               cross_portal_results.style.display = "block";
               cross_portal_list.style.display = "block";

			   //Swap record table label for cross portal searches
			   cross_portal_record_label.style.display = "block";
			   standard_record_label.style.display = "none";
            } else {
               cross_portal_results.style.display = "none";
               cross_portal_list.style.display = "none";

			   //Swap record table label for standard searches 
			   cross_portal_record_label.style.display = "none";
			   standard_record_label.style.display = "block";
            }
         }
			setPanels(true);
			$("#accordion").accordion("option",{active: 1});
			buildPortalLegend();
			buildTaxaLegend();
			buildCollectionLegend();

			//Calls init again because this html is js rendered
			jscolor.init();
		}

		function legendRow(id, color, innerHTML) {

			return (
			`<div style="display:table-row;height: 2rem">
				<div style="display:table-cell;vertical-align:middle;" >
					<div style="display:flex; align-items:center">
						<input
						data-role="none"
						id="${id}"
						class="color"
						onchange="onColorChange(this)"
						style="cursor:pointer;border:1px black solid;height:1rem;width:1rem;margin-bottom:-2px;font-size:0px;"
						value="${color}"
						/>
					</div>
				</div>
				<div style="display:table-cell;vertical-align:middle;padding-left:8px;"> = </div>
				<div style="display:table-cell;vertical-align:middle;padding-left:8px;">
					${innerHTML}
				</div>
			</div>
			<div style="display:table-row;height:8px;"></div>`
			)
		}

		function onColorChange(e) {
			e.dispatchEvent(colorChange)
		}

		function buildTaxaLegend() {
			taxaLegendMap = {}
			let taxaHtml = "";

			for(let i = 0; i < mapGroups.length; i++) {
				const origin = mapGroups[i].origin
				for(taxon of Object.values(mapGroups[i].taxonMapGroup.group_map)) {
					if(!taxaLegendMap[taxon.sn]) {
						taxaLegendMap[taxon.sn] = taxon
						taxaLegendMap[taxon.sn].origin = origin;
						taxaLegendMap[taxon.sn].id_map = [{tid: taxon.tid, index: i}];
						
					} else {
						taxaLegendMap[taxon.sn].id_map.push({tid: taxon.tid, index: i});
					}
				}
			}

			let taxaArr = Object.values(taxaLegendMap).sort((a, b) => {
				if(a.family === b.family) return 0;
				else if(a.family > b.family) return 1;
				else return -1;			
			})

			let prev_family;

			for (let taxa of taxaArr) {
				if(prev_family !== taxa.family) {
					if(taxaHtml) taxaHtml += "</div>";

					taxaHtml += `<h2 style="margin-bottom:0.5rem">${taxa.family}</h2>`;
					taxaHtml += "<div style='display:table;'>";
					prev_family = taxa.family;
				}
				const sn_link = `<a target="_blank" href="${taxa.origin? taxa.origin: '<?= $CLIENT_ROOT ?>'}/taxa/index.php?tid=${taxa.tid}">${taxa.sn}</a>`;
				taxaHtml += legendRow(`taxa-${taxa.id_map.map(id => `${id.index}*${id.tid}`).join(",")}`, taxa.color, sn_link);
			}

			taxaHtml += "</div>";

			document.getElementById("taxasymbologykeysbox").innerHTML = taxaHtml;
			document.getElementById("taxaCountNum").innerHTML = taxaArr.length;
		}

      function buildPortalLegend() {
         portalLegendMap = {};
         for(let i = 0; i < mapGroups.length; i++) {
            for(portal of Object.values(mapGroups[i].portalMapGroup.group_map)) {
					if(!portalLegendMap[portal.name]) {
						portalLegendMap[portal.name] = portal;
						portalLegendMap[portal.name].id_map = [{portalid: portal.portalid, index: i}];
					} else {
						portalLegendMap[portal.name].id_map.push({portalid: portal.portalid, index: i});
					}
            }
         }

			let html = "<div style='display:table;'>";

			for (let portal of Object.values(portalLegendMap)) {
				html += legendRow(`portal-${portal.id_map.map(v => `${v.index}*${v.portalid}`).join(",")}`, portal.color, portal.name);
			}

			document.getElementById("portalsymbologykeysbox").innerHTML = html;
      }

		function buildCollectionLegend() {
			collLegendMap = {}

			for(let i = 0; i < mapGroups.length; i++) {
				for(coll of Object.values(mapGroups[i].collectionMapGroup.group_map)) {
					if(!collLegendMap[coll.name]) {
						collLegendMap[coll.name] = coll
						collLegendMap[coll.name].id_map = [{collid: coll.collid, index: i}];
					} else {
						collLegendMap[coll.name].id_map.push({collid: coll.collid, index: i});
					}
				}
			}

			let html = "<div style='display:table;'>";

			for (let coll of Object.values(collLegendMap)) {
				html += legendRow(`coll-${coll.id_map.map(v => `${v.index}*${v.collid}`).join(",")}`, coll.color, coll.name);
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
			document.getElementById("polycoords").value = '';
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
			document.getElementById("polycoords").value = '';
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
			document.getElementById("polycoords").value = '';
			document.getElementById("distFromMe").value = '';
			document.getElementById("noshapecriteria").style.display = "none";
			document.getElementById("polygeocriteria").style.display = "none";
			document.getElementById("circlegeocriteria").style.display = "none";
			document.getElementById("rectgeocriteria").style.display = "block";
			document.getElementById("deleteshapediv").style.display = "block";
		}

		function setPolyCoords(wkt) {
			document.getElementById("polycoords").value = wkt;
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

		function addRefPoint() {
			let lat = document.getElementById("lat").value;
			let lng = document.getElementById("lng").value;
			let title = document.getElementById("title").value;
			let useLLDecimal = document.getElementById("useLLDecimal");
			if(useLLDecimal?.style?.display === 'block'){
				var latdeg = document.getElementById("latdeg").value;
				var latmin = document.getElementById("latmin").value;
				var latsec = document.getElementById("latsec").value;
				var latns = document.getElementById("latns").value;
				var longdeg = document.getElementById("longdeg").value;
				var longmin = document.getElementById("longmin").value;
				var longsec = document.getElementById("longsec").value;
				var longew = document.getElementById("longew").value;
				if(latdeg != null && longdeg != null){
					if(latmin == null) latmin = 0;
					if(latsec == null) latsec = 0;
					if(longmin == null) longmin = 0;
					if(longsec == null) longsec = 0;
					lat = latdeg*1 + latmin/60 + latsec/3600;
					lng = longdeg*1 + longmin/60 + longsec/3600;
					if(latns == "S") lat = lat * -1;
					if(longew == "W") lng = lng * -1;
				}
			}

			if((lat === null || lat === "") && (lng === null || lng === "")){
				window.alert("<?php echo $LANG['ENTER_VALUES_IN_LAT_LONG']; ?>");
			} else if(lat < -180 || lat > 180 || lng < -180 || lng > 180) {
				window.alert("<?php echo $LANG['LAT_LONG_MUST_BE_BETWEEN_VALUES']; ?> (" + lat + ";" + lng + ")");
			} else {
				var addPoint = true;
				if(lng > 0) addPoint = window.confirm("<?php echo $LANG['LONGITUDE_IS_POSITIVE']; ?>?");
				if(!addPoint) lng = -1*lng;

				document.dispatchEvent(new CustomEvent('addReferencePoint', {
					detail: {
						lat,
						lng,
						title
					}
				}));
			}
		}

		function leafletInit() {

			L.DivIcon.CustomColor = L.DivIcon.extend({
				createIcon: function(oldIcon) {
					var icon = L.DivIcon.prototype.createIcon.call(this, oldIcon);
					icon.style.textShadow="0 0 8px white, 0 0 8px white, 0 0 8px white";
					icon.style.margin ="0 0 0 0";
					return icon;
				}
			})

			let map = new LeafletMap('map', {
				lang: "<?php echo $LANG_TAG; ?>",
			})
			map.enableDrawing({
				polyline: false,
				circlemarker: false,
				marker: false,
				drawColor: {opacity: 0.85, fillOpacity: 0.55, color: '#000' },
			}, setQueryShape);

			let cluster_type = "taxa";

			let markers = [];

			let heatmapLayer;
			let heatmap;

			let groupClusters = [];

			let color = "B2BEB5";

			map.mapLayer.zoomControl.setPosition('topright');

			class LeafletMapGroup {
				markers = {};
				layer_groups = {};
				group_name;
				group_map;

				constructor(group_name, group_map) {
					this.group_name = group_name;
					this.group_map = group_map;
				}

				addMarker(id, marker) {
					if(!this.markers[id]) {
						this.markers[id] = [marker]
					} else {
						this.markers[id].push(marker);
					}
				}

				genLayer(id, cluster) {
					this.group_map[id].cluster = cluster;
					this.layer_groups[id] = L.layerGroup(this.markers[id]);
					this.group_map[id].cluster.addLayer(this.layer_groups[id]);
				}

				drawGroup() {
					for (let id of Object.keys(this.group_map)) {
						if(clusteroff) {
							this.layer_groups[id].addTo(map.mapLayer);
						} else if(!map.mapLayer.hasLayer(this.group_map[id].cluster)) {
							this.group_map[id].cluster.addTo(map.mapLayer)
						}
					}
				}

				removeGroup() {
					for (let id of Object.keys(this.group_map)) {
						if(clusteroff) {
							map.mapLayer.removeLayer(this.layer_groups[id])
						} else {
							map.mapLayer.removeLayer(this.group_map[id].cluster)
						}
					}
				}

				resetGroup() {
					for (let id of Object.keys(this.group_map)) {
						this.group_map[id].cluster.clearLayers();
						this.layer_groups[id].clearLayers();
						this.markers[id] = [];
					}
				}

				removeLayer(id) {
					this.group_map[id].cluster.clearLayers();
					map.mapLayer.removeLayer(this.group_map[id].cluster);
				}

				addLayer(id) {
					//First Add layer for both regular layer group and for clustering
					this.layer_groups[id] = L.layerGroup(this.markers[id]);
					this.group_map[id].cluster.addLayer(this.layer_groups[id])

					//Then Decide which is visible
					if(!heatmap) {
						if(clusteroff) {
							map.mapLayer.addLayer(this.layer_groups[id]);
						} else if(!map.mapLayer.hasLayer(this.group_map[id].cluster)) {
							this.group_map[id].cluster.addTo(map.mapLayer);
						}
					}
				}

				toggleClustering() {
					for(let id of Object.keys(this.group_map)) {
						if(clusteroff) {
							if(map.mapLayer.hasLayer(this.group_map[id].cluster)) {
								map.mapLayer.removeLayer(this.group_map[id].cluster);
							}
							map.mapLayer.addLayer(this.layer_groups[id]);
						} else {
							map.mapLayer.removeLayer(this.layer_groups[id]);
							if(!map.mapLayer.hasLayer(this.group_map[id].cluster)) {
								this.group_map[id].cluster.addTo(map.mapLayer);
							}
						}
					}
				}

				genClusters() {
					for(let id in this.group_map) {
						const cluster_rendered = this.group_map[id].cluster && map.mapLayer.hasLayer(this.group_map[id].cluster);
						if(cluster_rendered) {
							map.mapLayer.removeLayer(this.group_map[id].cluster);
						}
						const value = this.group_map[id];
						const colorCluster = (cluster) => {
							let childCount = cluster.getChildCount();
							cluster.bindTooltip(`<div style="font-size:1rem"><?=$LANG['CLICK_TO_EXPAND']?></div>`);
							cluster.on("click", e => e.target.spiderfy() )
							return new L.DivIcon.CustomColor({
								html: `<div class="symbiota-cluster" style="background-color: #${value.color};"><span>` + childCount + '</span></div>',
								className: `symbiota-cluster-div`,
								iconSize: new L.Point(20, 20),
								color: `#${value.color}77`,
								mainColor: `#${value.color}`,
							});
						}

						let cluster = L.markerClusterGroup({
							iconCreateFunction: colorCluster,
							//cluster_radius is a global
							maxClusterRadius: cluster_radius,
							zoomToBoundsOnClick: false,
							chunkedLoading: true
						});

						if(!this.layer_groups[id]) {
							this.genLayer(id, cluster);
						} else {
							this.group_map[id].cluster = cluster;
							this.group_map[id].cluster.addLayer(this.layer_groups[id]);
						}

						//Only Redraws if cluster of id was on map before regen
						if(!clusteroff && cluster_rendered) {
							this.group_map[id].cluster.addTo(map.mapLayer);
						}
					}
				}

				updateColor(id, color) {
					this.group_map[id].color = color;

					for (let marker of this.markers[id]) {
						if(marker.options.icon && marker.options.icon.options.observation) {
							marker.setIcon(getObservationSvg({color: `#${color}`, size:30 }))
						} else {
							marker.setStyle({fillColor: `#${color}`})
						}
					}
				}
			}

			function genMapGroups(records, tMap, cMap, origin) {
				let taxon = new LeafletMapGroup("taxa", tMap);
				let collections = new LeafletMapGroup("coll", cMap);
				let portal = new LeafletMapGroup("portal", { [origin]: { name: origin, portalid: origin, color: generateRandColor()} });

				for(let record of records) {
					let marker = (record.type === "specimen"?
						L.circleMarker([record.lat, record.lng], {
							radius : 8,
							color  : '#000000',
							weight: 2,
							fillColor: `#${tMap[record['tid']].color}`,
							opacity: 1.0,
							fillOpacity: 1.0,
							className: `coll-${record['collid']} taxa-${record['tid']}`
						}):
						L.marker([record.lat, record.lng], {
							icon: getObservationSvg({
								color: `#${tMap[record['tid']].color}`,
								className: `coll-${record['collid']} taxa-${record['tid']}`,
								size: 30
							})
						}))
					.on('click', function() { openRecord(record) })
					.bindTooltip(`<div style="font-size:1rem">${record.id}</div>`)

					markers.push(marker);

					taxon.addMarker(record['tid'], marker);
					collections.addMarker(record['collid'], marker);
					portal.addMarker(origin, marker);
				}

				return {taxonMapGroup: taxon, collectionMapGroup: collections, portalMapGroup: portal};
			}

			function drawPoints() {
				if(heatmap) {
					drawHeatmap();
				} else {
					if(cluster_type === "taxa") mapGroups.forEach(group => group.taxonMapGroup.drawGroup())
					else if(cluster_type === "coll") mapGroups.forEach(group => group.collectionMapGroup.drawGroup())
					else if(cluster_type === "portal") mapGroups.forEach(group => group.portalMapGroup.drawGroup())
				}
			}

			function drawHeatmap() {
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

			function fitMap() {
				if(shape && !map.activeShape) {
					map.drawShape(shape);
				} else if(map.activeShape) {
					map.mapLayer.fitBounds(map.activeShape.layer.getBounds());
				} else if(markers && markers.length > 0) {
					const group = new L.FeatureGroup(markers);
					map.mapLayer.fitBounds(group.getBounds());
				} else if(map_bounds) {
					map.mapLayer.fitBounds(map_bounds);
				}
			}

			document.addEventListener('resetMap', async e => {
				setPanels(false);
				mapGroups.forEach(group => {
					group.taxonMapGroup.resetGroup();
					group.collectionMapGroup.resetGroup();
					group.portalMapGroup.resetGroup();
				})

				markers = [];
				recordArr = [];

				if(heatmapLayer) map.mapLayer.removeLayer(heatmapLayer);
			})

			document.getElementById("mapsearchform").addEventListener('submit', async e => {
				e.preventDefault();
				if(!verifyCollForm(e.target)) return false;

				showWorking();

				let formData = new FormData(e.target);

				mapGroups.forEach(group => {
					group.taxonMapGroup.resetGroup();
					group.collectionMapGroup.resetGroup();
					group.portalMapGroup.resetGroup();
				})

				markers = [];

				if(heatmapLayer) map.mapLayer.removeLayer(heatmapLayer);

				getOccurenceRecords(formData).then(res => {
					if (res) loadOccurenceRecords(res);
				});

				let searches = [
					searchCollections(formData).then(res => {
						res.label = "<?= $LANG['CURRENT_PORTAL']?>";
						return res;
					})
				]

				//If Cross Portal Checkbox Enabled add cross portal search
				if(formData.get('cross_portal_switch') && formData.get('cross_portal')) {
					formData.set("taxa", formData.get('external-taxa-input'));
					searches.push(searchCollections(formData, formData.get('cross_portal')).then(res => {
						res.label= formData.get('cross_portal_label');
						return res;
					}));

					getOccurenceRecords(formData, formData.get('cross_portal')).then(res => {
						if (res) loadOccurenceRecords(res, "external_occurrencelist");
					});

				}

				//This is for handeling multiple portals
				searches = await Promise.all(searches)

				recordArr = [];
				mapGroups = [];
				let count = 0;

				for(let search of searches) {
					if(search.recordArr) {
						recordArr = recordArr.concat(search.recordArr)
						const group = genMapGroups(search.recordArr, search.taxaArr, search.collArr, search.label)
						group.origin = search.origin;
						mapGroups.push(group);
					}
					count++;
				}

				//Need to generate colors for each group
				buildPanels(formData.get('cross_portal_switch'));

				mapGroups.forEach(group => {
					group.taxonMapGroup.genClusters();
					group.collectionMapGroup.genClusters();
					group.portalMapGroup.genClusters();
				})

				autoColorTaxa();

				drawPoints();
				fitMap();
				hideWorking();
			});

			async function updateColor(type, id_arr, color) {

				for(let idParts of id_arr) {
					let [index, id] = idParts;

					if(type === "taxa") mapGroups[index].taxonMapGroup.removeLayer(id);
					else if(type === "coll") mapGroups[index].collectionMapGroup.removeLayer(id);
					else if(type === "portal") mapGroups[index].portalMapGroup.removeLayer(id);
				}

				for(let idParts of id_arr) {
					let [index, id] = idParts;

					if(type === "taxa") mapGroups[index].taxonMapGroup.updateColor(id, color);
					else if(type === "coll") mapGroups[index].collectionMapGroup.updateColor(id, color);
					else if(type === "portal") mapGroups[index].portalMapGroup.updateColor(id, color);
				}

				for(let idParts of id_arr) {
					let [index, id] = idParts;

					if(type === "taxa") mapGroups[index].taxonMapGroup.addLayer(id);
					else if(type === "coll") mapGroups[index].collectionMapGroup.addLayer(id);
					else if(type === "portal") mapGroups[index].portalMapGroup.addLayer(id);
				}
			}

			document.addEventListener('colorchange', function(e) {
				const [type, id] = e.target.id.split("-");
				const id_arr = id.split(",").map(part => part.split("*"));
				const color = e.target.value;

				updateColor(type, id_arr, color);
			});

			document.addEventListener('autocolor', async function(e) {
				const {type, colorMap} = e.detail;

				mapGroups.map(group => {
					if(cluster_type === "coll") {
						group.collectionMapGroup.removeGroup();
					} else if(cluster_type === "taxa") {
						group.taxonMapGroup.removeGroup();
					} else if(cluster_type === "portal") {
						group.portalMapGroup.removeGroup();
					}
				})

				cluster_type = type;

				for (let {id_arr, color} of Object.values(colorMap)) {
					updateColor(type, id_arr, color);
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

			document.addEventListener('addReferencePoint', e => {
				try {
					marker = L.marker([
						parseFloat(e.detail.lat),
						parseFloat(e.detail.lng)
					]);
					if(e.detail.title) {
						marker.bindTooltip(`<div style="font-size: 1rem">${e.detail.title}</div>`)
					}
					marker.addTo(map.drawLayer);
				} catch(e) {
					console.log('failed to add point because: ' + e)
				}
			});

			document.getElementById('clusteroff').addEventListener('change', e => {
				clusteroff = e.target.checked;
				if(!heatmap) {
					if(cluster_type === "taxa") mapGroups.forEach(group => group.taxonMapGroup.toggleClustering())
					else if(cluster_type === "coll") mapGroups.forEach(group => group.collectionMapGroup.toggleClustering())
					else if(cluster_type === "portal") mapGroups.forEach(group => group.portalMapGroup.toggleClustering())
				}
			});
			document.getElementById("cluster-radius").addEventListener('change', e => {
				const radius = parseInt(e.target.value);
				cluster_radius = radius
				mapGroups.forEach(group => {
					group.taxonMapGroup.genClusters();
					group.collectionMapGroup.genClusters();
					group.portalMapGroup.genClusters();
				})
			});

			document.getElementById('heatmap_on').addEventListener('change', e => {
				heatmap = e.target.checked;
				if(e.target.checked) {
					//Clear points
					if(cluster_type === "taxa") mapGroups.forEach(group => group.taxonMapGroup.removeGroup())
					else if(cluster_type === "coll") mapGroups.forEach(group => group.collectionMapGroup.removeGroup())
					else if(cluster_type === "portal") mapGroups.forEach(group => group.portalMapGroup.removeGroup())

					drawHeatmap();
				} else {
					map.mapLayer.removeLayer(heatmapLayer);
					if(cluster_type === "taxa") mapGroups.forEach(group => group.taxonMapGroup.drawGroup())
					else if(cluster_type === "coll") mapGroups.forEach(group => group.collectionMapGroup.drawGroup())
					else if(cluster_type === "portal") mapGroups.forEach(group => group.portalMapGroup.drawGroup())
				}
			});

			document.getElementById('heat-min-density').addEventListener('change', e => drawHeatmap())
			document.getElementById('heat-radius').addEventListener('change', e => drawHeatmap())
			document.getElementById('heat-max-density').addEventListener('change', e => drawHeatmap() )

			//Load Data if any with page Load
			if(recordArr.length > 0) {
				let formData = new FormData(document.getElementById("mapsearchform"));

				const group = genMapGroups(recordArr, taxaMap, collArr, "<?=$LANG['CURRENT_PORTAL']?>");
				group.origin = "<?= $SERVER_HOST . $CLIENT_ROOT?>";
				mapGroups = [group];

				getOccurenceRecords(formData).then(res => {
					if(res) loadOccurenceRecords(res);
					buildPanels(formData.get('cross_portal_switch'));

					mapGroups.forEach(group => {
						group.taxonMapGroup.genClusters();
						group.collectionMapGroup.genClusters();
						group.portalMapGroup.genClusters();
					})

					autoColorTaxa();

					drawPoints();

					fitMap();
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

			class GoogleMapGroup {
				markers = {};
				group_name;
				group_map;

				constructor(group_name, group_map) {
					this.group_name = group_name;
					this.group_map = group_map;
				}

				addMarker(id, marker) {
					if(!this.markers[id]) {
						this.markers[id] = [marker]
					} else {
						this.markers[id].push(marker);
					}
				}

				genLayer(id, cluster, oms) {
					for(let m of this.markers[id]) {
						oms.addMarker(m);
					}
					this.group_map[id].oms = oms;
					cluster.addMarkers(this.markers[id]);
					this.group_map[id].cluster = cluster;
				}

				drawGroup() {
					for (let id of Object.keys(this.group_map)) {
						this.addLayer(id);
					}
				}

				removeGroup() {
					for (let id of Object.keys(this.group_map)) {
						this.removeLayer(id);
					}
				}

				resetGroup() {
					for (let id of Object.keys(this.group_map)) {
						this.removeLayer(id);
					}
				}

				removeLayer(id) {
					if(clusteroff) {
						for(let marker of Object.values(this.markers[id])) {
							//marker.setMap(null)
							this.group_map[id].oms.removeMarker(marker)
						}
					} else {
						this.group_map[id].cluster.clearMarkers();
						this.group_map[id].cluster.setMap(null);
					}
				}

				addLayer(id) {
					if(!heatmapon) {
						if(clusteroff) {
							for(let marker of Object.values(this.markers[id])) {
								//marker.setMap(map.mapLayer)
								this.group_map[id].oms.addMarker(marker)
							}
						} else {
							this.group_map[id].cluster.addMarkers(this.markers[id])
							this.group_map[id].cluster.setMap(map.mapLayer);
						}
					}
				}

				toggleClustering() {
					for(let id of Object.keys(this.group_map)) {
						if(clusteroff) this.group_map[id].cluster.setMap(null)
						else this.addLayer(id)
					}
				}

				updateColor(id, color) {
					this.group_map[id].color = color;

					for (let marker of this.markers[id]) {
						marker.color = `#${color}`
						marker.icon.fillColor = `#${color}`
					}
				}
				updateGridSize(new_grid_size) {
					for(let id in this.group_map) {
						this.group_map[id].cluster.setMap(null);
						this.group_map[id].cluster.gridSize_ = new_grid_size
						this.group_map[id].cluster.setMap(map.mapLayer);
					}
				}
			}

			function genGroups(records, tMap, cMap, origin) {
				if(records.length < 1) return;

				let taxon = new GoogleMapGroup("taxa", tMap);
				let collections = new GoogleMapGroup("coll", cMap);
				let portals = new GoogleMapGroup("portal", { [origin]: { name: origin, portalid: origin, color: generateRandColor()} });

				bounds = new google.maps.LatLngBounds();

				for(let record of records) {
					let marker = new google.maps.Marker({
						position: new google.maps.LatLng(record['lat'], record['lng']),
						text: "Test",
						icon: record['type'] === "specimen"?
							{
								path: google.maps.SymbolPath.CIRCLE,
								fillColor: `#${tMap[record['tid']].color}`,
								fillOpacity: 1,
								scale: 7,
								strokeColor: "#000000",
								strokeWeight: 1
							}: {
								path: "m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",
								fillColor: `#${tMap[record['tid']].color}`,
								fillOpacity: 1,
								scale: 1,
								strokeColor: "#000000",
								strokeWeight: 1
							},
						selected: false,
						color: `#${tMap[record['tid']].color}`,
					})

					bounds.extend(marker.getPosition());

					const infoWin = new google.maps.InfoWindow({content:`<div>${record.id}</div>`});

					google.maps.event.addListener(marker, 'mouseover', function() {
						infoWin.open(map.mapLayer, marker);
					})

					google.maps.event.addListener(marker, 'mouseout', function() {
						infoWin.close();
					})

					google.maps.event.addListener(marker, 'spider_click', function(e) {
						openRecord(record);
					})

					if(clusteroff && !heatmapon) {
						marker.setMap(map.mapLayer);
					}

					taxon.addMarker(record['tid'], marker);
					collections.addMarker(record['collid'], marker);
					portals.addMarker(origin, marker);
				}

				return { taxonMapGroup: taxon, collectionMapGroup: collections, portalMapGroup: portals};
			}

			function drawPoints() {

				if(heatmapon) {
					if(!heatmapLayer) initHeatmap();
					else updateHeatmap();
				} else {
					mapGroups.forEach(g => {
						if(cluster_type === "taxa") g.taxonMapGroup.drawGroup();
						else if(cluster_type === "coll") g.collectionMapGroup.drawGroup();
						else if(cluster_type === "portal") g.portalMapGroup.drawGroup();
					})
				}
			}

			function genClusters(legendMap, type) {
				for(let val of Object.values(legendMap)) {

					const cluster = new MarkerClusterer(null, [], {
						styles: [{
							color: val.color,
						}],
						maxZoom: 20,
						gridSize: 60,
						minimumClusterSize: 2
					})

					var oms = new OverlappingMarkerSpiderfier(map.mapLayer, {
						markersWontMove: true,
						markersWontHide: true,
						basicFormatEvents: true
					});

					val.id_map.forEach(g=> {
						if(type === "taxa") {
							mapGroups[g.index].taxonMapGroup.genLayer(g.tid, cluster, oms);
						} else if(type === "coll") {
							mapGroups[g.index].collectionMapGroup.genLayer(g.collid, cluster, oms);
						} else if(type === "portal") {
							mapGroups[g.index].portalMapGroup.genLayer(g.portalid, cluster, oms);
						}
					});
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

			document.addEventListener('resetMap', async e => {
				setPanels(false);
				mapGroups.forEach(group => {
					group.taxonMapGroup.resetGroup();
					group.collectionMapGroup.resetGroup();
					group.portalMapGroup.resetGroup();
				})

				markers = [];
				recordArr = [];

				if(heatmapLayer) heatmapLayer.setData({data: []})
			})

			document.getElementById("mapsearchform").addEventListener('submit', async e => {
				e.preventDefault();
				if(!verifyCollForm(e.target)) return false;

				showWorking();
				let formData = new FormData(e.target);
				mapGroups.map(group => {
					group.taxonMapGroup.resetGroup();
					group.collectionMapGroup.resetGroup();
					group.portalMapGroup.resetGroup();
				});

				mapGroups = [];
				recordArr = [];

				if(heatmapLayer) heatmapLayer.setData({data: []})

				getOccurenceRecords(formData).then(res => {
					if (res) loadOccurenceRecords(res);
				});

				let searches = [
               searchCollections(formData).then(res=>{
                  res.label = "<?= $LANG['CURRENT_PORTAL']?>";
                  return res;
               }),
            ]

            //If Cross Portal Checkbox Enabled add cross portal search
            if(formData.get('cross_portal_switch') && formData.get('cross_portal')) {
               formData.set("taxa", formData.get('external-taxa-input'))
               searches.push(searchCollections(formData, formData.get('cross_portal')).then(res => {
                  res.label= formData.get('cross_portal_label')
                  return res;
               }))

               getOccurenceRecords(formData, formData.get('cross_portal')).then(res => {
                  if (res) loadOccurenceRecords(res, "external_occurrencelist");
               });
            }

				//This is for handeling multiple portals
				searches = await Promise.all(searches)

				for(let search of searches) {
					recordArr = recordArr.concat(search.recordArr);
					const group = genGroups(search.recordArr, search.taxaArr, search.collArr, search.label)
					group.origin = search.origin;
					mapGroups.push(group);
				}

				buildPanels(formData.get('cross_portal_switch'));

				//Must have build panels called b4
				genClusters(taxaLegendMap, "taxa");
				genClusters(collLegendMap, "coll");
				genClusters(portalLegendMap, "portal");

				autoColorTaxa();

				drawPoints();
				fitMap()
				hideWorking();
			});

			document.addEventListener('deleteShape', e => {
				clid_input = document.getElementById('clid');
				if(clid_input) clid_input.value = '';

				map.clearMap();
				shape = null;
			});

			document.addEventListener('addReferencePoint', e => {
				try {
					var iconImg = new google.maps.MarkerImage( '../../images/google/arrow.png' );
					let marker = new google.maps.Marker({
						position: new google.maps.LatLng(
							parseFloat(e.detail.lat),
							parseFloat(e.detail.lng)
						),
						icon: iconImg,
						zIndex: google.maps.Marker.MAX_ZINDEX
					});

					if(e.detail.title) {
						const infoWin = new google.maps.InfoWindow({
							content:`<div>${e.detail.title}</div>`
						});

						google.maps.event.addListener(marker, 'mouseover', () => {
							infoWin.open(map.mapLayer, marker);
						})

						google.maps.event.addListener(marker, 'mouseout', () => {
							infoWin.close();
						})
					}
					marker.setMap(map.mapLayer);
				} catch(e) {
					console.log('failed to add point because: ' + e)
				}
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

			async function updateColor(type, id_arr, color) {

				const cluster = new MarkerClusterer(null, [], {
					styles: [{
						color: color,
					}],
					maxZoom: 14,
					gridSize: 60,
					minimumClusterSize: 2
				})

				const getIndex = v => v[0];
				const getId = v => v[1];

				id_arr.forEach(v => {
					if(type === "taxa") {
						mapGroups[getIndex(v)].taxonMapGroup.removeLayer(getId(v));
					} else if (type === "coll") {
						mapGroups[getIndex(v)].collectionMapGroup.removeLayer(getId(v));
					} else if (type === "portal") {
						mapGroups[getIndex(v)].portalMapGroup.removeLayer(getId(v));
					}
				});

				id_arr.forEach(v => {
					if(type === "taxa") {
						mapGroups[getIndex(v)].taxonMapGroup.group_map[getId(v)].cluster = cluster;
						mapGroups[getIndex(v)].taxonMapGroup.updateColor(getId(v), color);
					} else if (type === "coll") {
						mapGroups[getIndex(v)].collectionMapGroup.group_map[getId(v)].cluster = cluster;
						mapGroups[getIndex(v)].collectionMapGroup.updateColor(getId(v), color);
					} else if (type === "portal") {
						mapGroups[getIndex(v)].portalMapGroup.group_map[getId(v)].cluster = cluster;
						mapGroups[getIndex(v)].portalMapGroup.updateColor(getId(v), color);
					}
				})

				id_arr.forEach(v => {
					if(type === "taxa") {
						mapGroups[getIndex(v)].taxonMapGroup.addLayer(getId(v));
					} else if (type === "coll") {
						mapGroups[getIndex(v)].collectionMapGroup.addLayer(getId(v));
					} else if (type === "portal") {
						mapGroups[getIndex(v)].portalMapGroup.addLayer(getId(v));
					}
				});
			}

			document.addEventListener('colorchange', function(e) {
				const [type, id] = e.target.id.split("-");
				const id_arr = id.split(",").map(part => part.split("*"));
				const color = e.target.value;

				updateColor(type, id_arr, color);
			});

			document.addEventListener('autocolor', async function(e) {
				const {type, colorMap} = e.detail;

				mapGroups.map(group => {
					if(cluster_type === "coll" && type !== "coll") {
						group.collectionMapGroup.removeGroup();
					} else if(cluster_type === "taxa" && type !== "taxa") {
						group.taxonMapGroup.removeGroup();
					} else if(cluster_type === "portal" && type !== "portal") {
						group.portalMapGroup.removeGroup();
					}
				})

				cluster_type = type;

				for (let {id_arr, color} of Object.values(colorMap)) {
					await updateColor(type, id_arr, color);
				}
			});

			document.getElementById('clusteroff').addEventListener('change', e => {
				clusteroff = e.target.checked;
				if(!heatmapon) {
					if(cluster_type === "taxa") mapGroups.map(g => g.taxonMapGroup.toggleClustering());
					else if(cluster_type === "coll") mapGroups.map(g => g.collectionMapGroup.toggleClustering());
					else if(cluster_type === "portal") mapGroups.map(g => g.portalMapGroup.toggleClustering());
				}
			});

			document.getElementById("cluster-radius").addEventListener('change', e => {
				const radius = parseInt(e.target.value);
				cluster_radius = radius;
				mapGroups.forEach(group => {
					group.taxonMapGroup.updateGridSize(radius);
					group.collectionMapGroup.updateGridSize(radius);
					group.portalMapGroup.updateGridSize(radius);
				})
			});

			document.getElementById('heatmap_on').addEventListener('change', e => {
				heatmapon = e.target.checked;

				if(e.target.checked) {
					//Clear points
					if(cluster_type === "taxa") {
						mapGroups.forEach(g => g.taxonMapGroup.resetGroup())
					} else if(cluster_type === "coll") {
						mapGroups.forEach(g => g.collectionMapGroup.resetGroup())
					} else if(cluster_type === "portal") {
						mapGroups.forEach(g => g.portalMapGroup.resetGroup())
					}
					if(!heatmapLayer) initHeatmap();
					else updateHeatmap();
				} else {
					if(heatmapLayer) {
						heatmapLayer.setData({data: []})
					};

					if(cluster_type == "taxa") {
						mapGroups.forEach(g => g.taxonMapGroup.drawGroup())
					} else if(cluster_type === "coll") {
						mapGroups.forEach(g => g.collectionMapGroup.drawGroup())
					} else if(cluster_type === "portal") {
						mapGroups.forEach(g => g.portalMapGroup.drawGroup())
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

				const group = genGroups(recordArr, taxaMap, collArr, "<?= $LANG['CURRENT_PORTAL']?>");
				group.origin = "<?= $SERVER_HOST . $CLIENT_ROOT?>";
				mapGroups = [
					group
				]

				getOccurenceRecords(formData).then(res => {
					if(res) loadOccurenceRecords(res);
					buildPanels(formData.get('cross_portal_switch'));

					genClusters(taxaLegendMap, "taxa");
					genClusters(collLegendMap, "coll");
					genClusters(portalLegendMap, "portal");

					autoColorTaxa();

					drawPoints();

					fitMap();
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

		async function searchCollections(body, host) {
         const emptyResponse = { taxaArr: [], collArr: [], recordArr: [], origin: host? host: "host" };
         sessionStorage.querystr = "";
			try {
				const url = host? `${host}/collections/map/rpc/searchCollections.php`: 'rpc/searchCollections.php'

				let response = await fetch(url, {
					method: "POST",
					mode: "cors",
					body: body,
			});
            if(response) {
             const search = await response.json()
               sessionStorage.querystr = search.query;
               return search;
            } else {
               return emptyResponse;
            }
			} catch(e) {
				return emptyResponse;
			}
		}

		async function getOccurenceRecords(body, host) {
			const url = host? `${host}/collections/map/occurrencelist.php`: 'occurrencelist.php'
			let response = await fetch(url, {
				method: "POST",
				credentials: "same-origin",
				body: body
			});

			return response? await response.text(): '';
		}

		function loadOccurenceRecords(html, id="occurrencelist") {
			document.getElementById(id).innerHTML = html;
			$('.pagination a').click(async function(e){
				e.preventDefault();
				let response = await fetch(e.target.href, {
					method: "GET",
					credentials: "same-origin",
				})
				loadOccurenceRecords(await response.text(), id)
				return false;
			});
		}

		function resetSymbology(keyMap, type, getId = v => v.id, fullreset) {
			let color_map = {};

			for(var key of Object.values(keyMap) ) {
				let id_arr = key.id_map.map(v => [v.index, getId(v)])
				let id = id_arr.map(parts => `${parts[0]}*${parts[1]}`).join(",")

				color_map[id] = ({color: default_color, id_arr: id_arr })

				const colorkey = document.getElementById(`${type}-${id}`);

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
			resetSymbology(collLegendMap, 'coll' ,v => v.collid, reset)
		};

		const resetTaxaSymbology = (reset = false) => {
			resetSymbology(taxaLegendMap, 'taxa', v => v.tid, reset);
		}

		const resetPortalSymbology = (reset = false) => {
			resetSymbology(portalLegendMap, 'portal', v => v.portalid, reset);
		}

		function autoColor(type, getId = v => v.id, keyMap) {
			var usedColors = {};
			for(let key of Object.values(keyMap)) {
				let id_arr = key.id_map.map(v => [v.index, getId(v)])
				var randColor = generateRandColor();

				while (usedColors[randColor] !== undefined) {
					randColor = generateRandColor();
				}

				usedColors[randColor] = {color: randColor, id_arr: id_arr};

				const colorkey = document.getElementById(`${type}-${id_arr.map(parts => `${parts[0]}*${parts[1]}`)}`)
				if(colorkey){
					colorkey.color.fromString(randColor);
				}
			}

			document.dispatchEvent(new CustomEvent('autocolor', {
				detail: {
					type: type,
					colorMap: usedColors,
				}
			}));
		}

		function autoColorTaxa() {
			resetCollSymbology();
			resetPortalSymbology();
			autoColor("taxa", v => v.tid, taxaLegendMap);
		}

		const autoColorColl = () => {
			resetTaxaSymbology();
			resetPortalSymbology();
			autoColor("coll", v => v.collid, collLegendMap)
		};

		const autoColorPortal = () => {
			resetTaxaSymbology();
			resetCollSymbology();
			autoColor("portal", v => v.portalid, portalLegendMap)
		};

		//This is used in occurrencelist.php which is submodule of this
		function emit_occurrence_click(occid) {
			document.dispatchEvent(new CustomEvent('occur_click', {
				detail: {
					occid: occid
				}
			}))
		}

		function deleteMapShape() {
			document.dispatchEvent(new Event('deleteShape'));
		}

		function initialize() {
			try {
				const data = document.getElementById('service-container');
				map_bounds = JSON.parse(data.getAttribute('data-map-bounds'));

				//Loads Init Map Coordinate Data if Any
				taxaMap = JSON.parse(data.getAttribute('data-taxa-map'));
				collArr = JSON.parse(data.getAttribute('data-coll-map'));
				recordArr = JSON.parse(data.getAttribute('data-records'));

				clusteroff = data.getAttribute('data-cluster-off') ==='y'? true: false;

				externalPortalHosts = JSON.parse(data.getAttribute('data-external-portal-hosts'));

				searchVar = data.getAttribute('data-search-var');
				if(searchVar) sessionStorage.querystr = searchVar;

				let shapeType;

				if(document.getElementById("pointlat").value) {
					shapeType = "circle"
				} else if(document.getElementById("upperlat").value) {
					shapeType = "rectangle"
				} else if(document.getElementById("polycoords").value) {
					shapeType = "polygon"
				}

				const cluster_radius_input = document.getElementById("cluster-radius")
				if(cluster_radius_input) {
					cluster_radius = parseInt(cluster_radius_input.value);
				}

				if(shapeType) {
					shape = loadMapShape(shapeType, {
						polygonLoader: () => document.getElementById("polycoords").value.trim(),
						circleLoader: () => {
                     const units = document.getElementById("pointunits").value;
							return {
								radius: parseFloat(document.getElementById("radius").value),
								radUnits: units == "mi" || units == "km"? units: "km",
								pointLng: parseFloat(document.getElementById("pointlong").value),
								pointLat: parseFloat(document.getElementById("pointlat").value)
							}
						},
						rectangleLoader: () => {
							return {
								upperLat: parseFloat(document.getElementById("upperlat").value),
								lowerLat: parseFloat(document.getElementById("bottomlat").value),
								rightLng: parseFloat(document.getElementById("rightlong").value),
								leftLng: parseFloat(document.getElementById("leftlong").value)
							}
						}
					})
				}
            document.addEventListener("deleteShape", () => setQueryShape(shape))

			window.initLocalitySuggest({
				country: {
					id: 'country',
				},
				state_province: {
					id: 'state',
				},
				county: {
					id: 'county',
				},
			})

			} catch(e) {
				alert("Failed to initialize map coordinate data")
			}

			<?php if(empty($GOOGLE_MAP_KEY)): ?>
				leafletInit();
			<?php else: ?>
				googleInit();
			<?php endif?>
	  }
		</script>
		<script src="../../js/symb/api.taxonomy.taxasuggest.js?ver=4" type="text/javascript"></script>
	</head>
	<body style='width:100%;max-width:100%;min-width:500px;' <?php echo (!$activateGeolocation?'onload="initialize();"':''); ?>>
		<?php
		if($shouldUseMinimalMapHeader) include_once($SERVER_ROOT . '/includes/minimalheader.php');
		?>
	  	<h1 class="page-heading screen-reader-only">Map Interface</h1>
		<div
			id="service-container"
			data-search-var="<?=htmlspecialchars($searchVar)?>"
			data-map-bounds="<?=htmlspecialchars(json_encode($bounds))?>"
			data-taxa-map="<?=htmlspecialchars(json_encode($taxaArr))?>"
			data-coll-map="<?=htmlspecialchars(json_encode($collArr))?>"
			data-records="<?=htmlspecialchars(json_encode($recordArr))?>"
			data-cluster-off="<?=htmlspecialchars($clusterOff)?>"
			data-external-portal-hosts="<?=htmlspecialchars(json_encode($EXTERNAL_PORTAL_HOSTS))?>"
			class="service-container"
		>
		</div>
		<div>
			<button onclick="document.getElementById('defaultpanel').style.width='29rem';  " style="position:absolute;top:0;left:0;margin:0px;z-index:10; gap: 0.2rem">
				<span style="padding-bottom:0.2rem">
					&#9776;
				</span>
				<b>Open Search Panel</b>
			</button>
		</div>
		<div id='map' style='width:100vw;height:100vh;z-index:1'></div>
		<div id="defaultpanel" class="sidepanel"  <?= $menuClosed? 'style="width: 0"': ''?>>
			<div class="menu" style="display:flex; align-items: center; background-color: var(--darkest-color); height: 2rem">
				<a style="text-decoration: none; margin-left: 0.5rem;" href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/index.php">
					<?php echo (isset($LANG['H_HOME'])?$LANG['H_HOME']:'Home'); ?>
				</a>
				<span style="display: flex; flex-grow: 1; margin-right:1rem; justify-content: right">
					<a onclick="document.getElementById('defaultpanel').style.width='0px'">Hide Panel</a>
				</span>
			</div>
			<div class="panel-content">
				<div id="mapinterface">
					<div id="accordion">
						<h3 style="margin-top:0"><?php echo (isset($LANG['SEARCH_CRITERIA'])?$LANG['SEARCH_CRITERIA']:'Search Criteria and Options'); ?></h3>
						<div id="tabs1" style="padding:0px;height:100%">
							<form name="mapsearchform" id="mapsearchform" data-ajax="false">
								<ul>
									<li><a href="#searchcollections"><span><?php echo htmlspecialchars($LANG['COLLECTIONS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></span></a></li>
									<li><a href="#searchcriteria"><span><?php echo htmlspecialchars($LANG['CRITERIA'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></span></a></li>
									<li><a href="#mapoptions"><span><?php echo htmlspecialchars((isset($LANG['MAP_OPTIONS'])?$LANG['MAP_OPTIONS']:'Map Options'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></span></a></li>
								</ul>
								<div id="searchcollections">
									<div >
										<?php
										$collList = $mapManager->getFullCollectionList($catId);
										$specArr = (isset($collList['spec']) ? $collList['spec'] : null);
										$obsArr = (isset($collList['obs']) ? $collList['obs'] : null);
										if($specArr || $obsArr){
										?>
										<div id="specobsdiv">
											<div style="margin:0px 0px 10px 5px;">
												<input id="dballcb" data-role="none" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" <?php echo (!$mapManager->getSearchTerm('db') || $mapManager->getSearchTerm('db')=='all'?'checked':'') ?> />
												<?php echo $LANG['SELECT_DESELECT'].' <a href="../misc/collprofiles.php" target="_blank">' . $LANG['ALL_COLLECTIONS'] . '</a>'; ?>
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
								<div id="searchcriteria" style="padding-top: 0.5rem">
									<div>
										<!-- <div style="float:left;<?php echo (isset($SOLR_MODE) && $SOLR_MODE?'display:none;':''); ?>">
Record Limit:
<input data-role="none" type="text" id="recordlimit" style="width:75px;" name="recordlimit" value="<?php echo ($recLimit?$recLimit:""); ?>" title="Maximum record amount returned from search." onchange="return checkRecordLimit(this.form);" />
										</div> -->
										<div style="display:flex; gap: 1rem; justify-content: right; height: 2rem">
											<input type="hidden" id="selectedpoints" value="" />
											<input type="hidden" id="deselectedpoints" value="" />
											<input type="hidden" id="selecteddspoints" value="" />
											<input type="hidden" id="deselecteddspoints" value="" />
											<input type="hidden" id="gridSizeSetting" name="gridSizeSetting" value="<?php echo $gridSize; ?>" />
											<input type="hidden" id="minClusterSetting" name="minClusterSetting" value="<?php echo $minClusterSize; ?>" />
											<input type="hidden" id="clusterSwitch" name="clusterSwitch" value="<?php echo $clusterOff; ?>" />
											<input type="hidden" id="pointlat" name="pointlat" value='<?php echo isset($pointLat)? $pointLat : "" ?>' />
											<input type="hidden" id="pointlong" name="pointlong" value='<?php echo isset($pointLng)? $pointLng : "" ?>' />
											<input type="hidden" id="pointunits" name="pointunits" value='<?php echo isset($pointUnit)? $pointUnit : "km" ?>' />
											<input type="hidden" id="radius" name="radius" value='<?php echo isset($pointRad)? $pointRad : "" ?>' />
											<input type="hidden" id="upperlat" name="upperlat" value='<?php echo isset($upperLat)? $upperLat : "" ?>' />
											<input type="hidden" id="rightlong" name="rightlong" value='<?php echo isset($upperLng)? $upperLng : "" ?>' />
											<input type="hidden" id="bottomlat" name="bottomlat" value='<?php echo isset($lowerLat)? $lowerLat : "" ?>' />
											<input type="hidden" id="leftlong" name="leftlong" value='<?php echo isset($lowerLng)? $lowerLng : "" ?>' />
											<input type="hidden" id="polycoords" name="polycoords" value='<?php echo $mapManager->getSearchTerm('polycoords'); ?>' />
											<button data-role="none" type="button" name="resetbutton" onclick="resetQueryForm(this.form)"><?php echo $LANG['RESET']; ?></button>
											<button data-role="none" name="submitform" type="submit" ><?php echo $LANG['SEARCH']; ?></button>
										</div>
									</div>
									<div style="margin:5 0 5 0;"><hr /></div>
									<div>
										<span style="">
											<input data-role="none" id="usethes" type="checkbox" name="usethes" value="1" <?php if($mapManager->getSearchTerm('usethes') || !$submitForm) echo "CHECKED"; ?> >
										<label for="usethes">
											<?php echo (isset($LANG['INCLUDE_SYNONYMS'])?$LANG['INCLUDE_SYNONYMS']:'Include Synonyms'); ?>
										</label>
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
											<?php echo $LANG['TAXA']; ?>:
											<input data-role="none" id="taxa" name="taxa" type="text" style="width:275px;" value="<?php echo $mapManager->getTaxaSearchTerm(); ?>" title="<?php echo (isset($LANG['SEPARATE_MULTIPLE'])?$LANG['SEPARATE_MULTIPLE']:'Separate multiple taxa w/ commas'); ?>" />
										</div>
									</div>
									<div style="margin:5 0 5 0;"><hr /></div>

									<?php if(!empty($ENABLE_CROSS_PORTAL)): ?>
									<?php include('./portalSelector.php')?>
									<div style="margin:5 0 5 0;"><hr /></div>
									<?php endif ?>
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
										<?php echo $LANG['COUNTRY']; ?>: <input data-role="none" type="text" id="country" style="width:225px;" name="country" value="<?php echo $mapManager->getSearchTerm('country'); ?>" title="<?php echo (isset($LANG['SEPARATE_MULTIPLE'])?$LANG['SEPARATE_MULTIPLE']:'Separate multiple taxa w/ commas'); ?>" />
									</div>
									<div style="margin-top:5px;">
										<?php echo (isset($LANG['STATE'])?$LANG['STATE']:'State/Province'); ?>: <input data-role="none" type="text" id="state" style="width:150px;" name="state" value="<?php echo $mapManager->getSearchTerm('state'); ?>" title="<?php echo (isset($LANG['SEPARATE_MULTIPLE'])?$LANG['SEPARATE_MULTIPLE']:'Separate multiple taxa w/ commas'); ?>" />
									</div>
									<div style="margin-top:5px;">
										<?php echo $LANG['COUNTY']; ?>: <input data-role="none" type="text" id="county" style="width:225px;"  name="county" value="<?php echo $mapManager->getSearchTerm('county'); ?>" title="<?php echo (isset($LANG['SEPARATE_MULTIPLE'])?$LANG['SEPARATE_MULTIPLE']:'Separate multiple taxa w/ commas'); ?>" />
									</div>
									<div style="margin-top:5px;">
										<?php echo $LANG['LOCALITY']; ?>: <input data-role="none" type="text" id="locality" style="width:225px;" name="local" value="<?php echo $mapManager->getSearchTerm('local'); ?>" />
									</div>
									<div style="margin:5 0 5 0;"><hr /></div>
									<div id="shapecriteria">
										<div id="noshapecriteria" style="display:<?php echo ((!$mapManager->getSearchTerm('polycoords') && !$mapManager->getSearchTerm('upperlat'))?'block':'none'); ?>;">
											<div id="geocriteria" style="display:<?php echo ((!$mapManager->getSearchTerm('polycoords') && !$distFromMe && !$mapManager->getSearchTerm('pointlat') && !$mapManager->getSearchTerm('upperlat'))?'block':'none'); ?>;">
												<div>
													<?php echo (isset($LANG['SHAPE_TOOLS'])?$LANG['SHAPE_TOOLS']:'Use the shape tools on the map to select occurrences within a given shape'); ?>.
												</div>
											</div>
											<div id="distancegeocriteria" style="display:<?php echo ($distFromMe?'block':'none'); ?>;">
												<div>
													<?php echo $LANG['WITHIN']; ?>
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
											<button class="button-danger" data-role="none" type="button" onclick="deleteMapShape()"><?php echo (isset($LANG['DELETE_SHAPE'])?$LANG['DELETE_SHAPE']:'Delete Selected Shape'); ?></button>
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
									<legend><?php echo $LANG['CLUSTERING']; ?></legend>
									<label><?php echo (isset($LANG['TURN_OFF_CLUSTERING'])?$LANG['TURN_OFF_CLUSTERING']:'Turn Off Clustering'); ?>:</label>
									<input data-role="none" type="checkbox" id="clusteroff" name="clusteroff" value='1' <?php echo ($clusterOff=="y"?'checked':'') ?>/>

									<span style="display: flex; align-items:center">
										<label for="cluster-radius"><?php echo (isset($LANG['CLUSTER_RADIUS'])? $LANG['CLUSTER_RADIUS']:'Radius') ?>: 1 </label>
										<input style="margin: 0 1rem;"type="range" value="1" id="cluster-radius" name="cluster-radius" min="1" max="100">100
									</span>
								</fieldset>
								<br/>
								<fieldset>
									<legend><?php echo $LANG['HEATMAP']; ?></legend>
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
								<br/>
								<fieldset>
									<legend>
									   <?= $LANG['ADD_REFERENCE_POINT'] ?>
									</legend>
									<div>
										<div>
									   <?= $LANG['MARKER_NAME'] ?>:
											<input name='title' id='title' size='15' type='text' />
										</div>
										<div class="latlongdiv">
											<div>
											 <div style="float:left;margin-right:5px">
												<?= $LANG['LATITUDE'] ?>
												(<?= $LANG['DECIMAL'] ?>):
												<input name='lat' id='lat' size='10' type='text' /> </div>
												<div style="float:left;">eg: 34.57</div>
											</div>
											<div style="margin-top:5px;clear:both">
											 <div style="float:left;margin-right:5px">
												<?= $LANG['LONGITUDE'] ?>
												(<?= $LANG['DECIMAL'] ?>):
												<input name='lng' id='lng' size='10' type='text' /> </div>
												<div style="float:left;">eg: -112.38</div>
											</div>
											<div style='font-size:80%;margin-top:5px;clear:both'>
											 <a href='#' onclick='toggleLatLongDivs();'>
												<?= $LANG['ENTER_IN_DMS']?>
											 </a>
											</div>
										</div>
										<div id="useLLDecimal" class='latlongdiv' style='display:none;clear:both'>
											<div>
												<?= $LANG['LATITUDE'] ?>:
												<input name='latdeg' id='latdeg' size='2' type='text' />&deg;
												<input name='latmin' id='latmin' size='4' type='text' />&prime;
												<input name='latsec' id='latsec' size='4' type='text' />&Prime;
												<select name='latns' id='latns'>
													<option value='N'><?= $LANG['NORTH']; ?></option>
													<option value='S'><?= $LANG['SOUTH']; ?></option>
												</select>
											</div>
											<div style="margin-top:5px;">
										  <?= $LANG['LONGITUDE'] ?>:
												<input name='longdeg' id='longdeg' size='2' type='text' />&deg;
												<input name='longmin' id='longmin' size='4' type='text' />&prime;
												<input name='longsec' id='longsec' size='4' type='text' />&Prime;
												<select name='longew' id='longew'>
													<option value='E'><?= $LANG['EAST']; ?></option>
													<option value='W' selected><?= $LANG['WEST']; ?></option>
												</select>
											</div>
											<div style='font-size:80%;margin-top:5px;'>
											 <a href='#' onclick='toggleLatLongDivs();'>
												<?= $LANG['ENTER_IN_DECIMAL'] ?>
											 </a>
											</div>
										</div>
										<div style="margin-top:10px;">
									   <button onclick='addRefPoint();'>
										  <?= $LANG['ADD_MARKER'] ?>
									   </button>
										</div>
									</div>
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
						<div id="tabs2" style="display:none;padding:0px;">
							<ul>
								<li><a href='#occurrencelist'>
									<span id="standard_record_label">
										<?= $LANG['RECORDS'] ?>
									</span>
									<span id="cross_portal_record_label">
										<?= $LANG['INTERNAL_RECORDS'] ?>
									</span></a>
								</li>
								<li id="cross_portal_results"><a href='#external_occurrencelist'><span><?= $LANG['EXTERNAL_RECORDS'] ?></span></a></li>
								<li id="cross_portal_list"><a href='#portalsymbology'><span><?= $LANG['PORTAL_LIST'] ?></span></a></li>
						   	<li><a href='#symbology'><span><?= $LANG['COLLECTIONS'] ?></span></a></li>
								<li><a href='#maptaxalist'><span><?= $LANG['TAXA_LIST'] ?></span></a></li>
							</ul>
							<div id="occurrencelist" style="">
								loading...
							</div>
							<div id="external_occurrencelist" style="">
								loading...
							</div>
							<div id="portalsymbology" style="">
								<div style="height:40px;margin-bottom:15px;">
								<div style="float:left;">
										<div>
											<svg xmlns="http://www.w3.org/2000/svg" style="height:15px;width:15px;margin-bottom:-2px;">">
												<g>
													<circle cx="7.5" cy="7.5" r="7" fill="white" stroke="#000000" stroke-width="1px" ></circle>
												</g>
											</svg> = <?php echo $LANG['COLLECTION']; ?>
										</div>
										<div style="margin-top:5px;" >
											<svg style="height:14px;width:14px;margin-bottom:-2px;">" xmlns="http://www.w3.org/2000/svg">
												<g>
													<path stroke="#000000" d="m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z" stroke-width="1px" fill="white"/>
												</g>
											</svg> = <?php echo $LANG['OBSERVATION']; ?>
										</div>
									</div>
									<div id="portalsymbolizeResetButt" style='float:right;margin-bottom:5px;' >
										<div>
											<button data-role="none" id="portalsymbolizeReset1" name="symbolizeReset1" onclick="resetPortalSymbology(true);" ><?php echo (isset($LANG['RESET_SYMBOLOGY'])?$LANG['RESET_SYMBOLOGY']:'Reset Symbology'); ?></button>
										</div>
										<div style="margin-top:5px;">
											<button data-role="none" id="randomColorPortal" name="randomColorColl" onclick='autoColorPortal();' ><?php echo (isset($LANG['AUTO_COLOR'])?$LANG['AUTO_COLOR']:'Auto Color'); ?></button>
										</div>
									</div>
								</div>
								<div style="margin:5 0 5 0;clear:both;"><hr /></div>
								<div style="" >
									<div style="margin-top:8px;">
										<div style="display:table;">
											<div id="portalsymbologykeysbox"></div>
										</div>
									</div>
								</div>
							</div>
							<div id="symbology">
								<div style="height:40px;margin-bottom:15px;">
								<div style="float:left;">
										<div>
											<svg xmlns="http://www.w3.org/2000/svg" style="height:15px;width:15px;margin-bottom:-2px;">">
												<g>
													<circle cx="7.5" cy="7.5" r="7" fill="white" stroke="#000000" stroke-width="1px" ></circle>
												</g>
											</svg> = <?php echo $LANG['COLLECTION']; ?>
										</div>
										<div style="margin-top:5px;" >
											<svg style="height:14px;width:14px;margin-bottom:-2px;">" xmlns="http://www.w3.org/2000/svg">
												<g>
													<path stroke="#000000" d="m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z" stroke-width="1px" fill="white"/>
												</g>
											</svg> = <?php echo $LANG['OBSERVATION']; ?>
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
							<div id="maptaxalist">
								<div style="height:40px;margin-bottom:15px;">
									<?php
									?>
								<div style="float:left;">
										<div>
											<svg xmlns="http://www.w3.org/2000/svg" style="height:15px;width:15px;margin-bottom:-2px;">">
												<g>
													<circle cx="7.5" cy="7.5" r="7" fill="white" stroke="#000000" stroke-width="1px" ></circle>
												</g>
											</svg> = <?php echo $LANG['COLLECTION']; ?>
										</div>
										<div style="margin-top:5px;" >
											<svg style="height:14px;width:14px;margin-bottom:-2px;">" xmlns="http://www.w3.org/2000/svg">
												<g>
													<path stroke="#000000" d="m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z" stroke-width="1px" fill="white"/>
												</g>
											</svg> = <?php echo $LANG['OBSERVATION']; ?>
										</div>
									</div>
									<?php
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
						?>
					</div>
				</div>
			</div><!-- /content wrapper for padding -->
		</div><!-- /defaultpanel -->
		<div id="loadingOverlay" data-role="popup" style="width:100%;position:relative;">
			<div id="loadingImage" style="width:100px;height:100px;position:absolute;top:50%;left:50%;margin-top:-50px;margin-left:-50px;">
				<img style="border:0px;width:100px;height:100px;" src="../../images/ajax-loader.gif" />
			</div>
		</div>
	</body>
</html>
