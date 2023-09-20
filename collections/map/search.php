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

//Gets Coordinates
$coordArr = $mapManager->getCoordinateMap(0,$recLimit);
$taxaArr = [];
$recordArr = [];
$collArr = [];
$defaultColor = "#B2BEB5";

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

// Break map data into 3 arrays for simplicity
if(!empty($coordArr)) {
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
            'color' => $coll['c'] 
            ];
         }

         //Collect all Collections
         if(!array_key_exists($record['collid'], $collArr)) {
            $collArr[$record['collid']] = [
               'name' => $collName,
               'collid' => $record['collid'],
               'color' => $coll['c'],
            ];
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
      }
   }
}

?>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $DEFAULT_TITLE; ?> - Map Interface</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	//include_once($SERVER_ROOT.'/includes/googleanalytics.php');
   include_once($SERVER_ROOT.'/includes/leafletMap.php');
   include_once($SERVER_ROOT.'/includes/googleMap.php');
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
	<script src="../../js/jquery.mobile-1.4.0.min.js" type="text/javascript"></script>
	<link href="../../css/jquery.mobile-1.4.0.min.css" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery.symbiota.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.popupoverlay.js" type="text/javascript"></script>
	<script src="../../js/jscolor/jscolor.js?ver=1" type="text/javascript"></script>
<!---	<script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'&key='.$GOOGLE_MAP_KEY:''); ?>&callback=Function.prototype" ></script> -->
	<script src="../../js/symb/collections.map.index.js?ver=2" type="text/javascript"></script>
	<script src="../../js/symb/collections.list.js?ver=1" type="text/javascript"></script>
	<script src="../../js/symb/markerclusterer.js?ver=1" type="text/javascript"></script>
	<script src="../../js/symb/oms.min.js" type="text/javascript"></script>
	<script src="../../js/symb/keydragzoom.js" type="text/javascript"></script>
	<script src="../../js/symb/infobox.js" type="text/javascript"></script>
	<script type="text/javascript">
      let recordArr = [];
      let taxaMap = [];
      let collArr = [];
      let searchVar = "";
      let map_bounds= [ [90, 180], [-90, -180] ];
      let puWin;

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
         let taxaHtml = "<div style='display:table;'>";
         let taxaArr = Object.values(taxaMap).sort((a, b) => a.family > b.family)

         for (let taxa of taxaArr) {
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
         console.log(collArr)

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
         let map = new LeafletMap('map')
         map.enableDrawing({
            polyline: false,
            circlemarker: false,
            marker: false,
            drawColor: {opacity: 0.85, fillOpacity: 0.55, color: '#000' }
         }, setQueryShape);
         let cluster = L.markerClusterGroup();
         let markers = [];
         let color = "B2BEB5";

         map.mapLayer.zoomControl.setPosition('topright');

         for(let record of recordArr) {
            let marker = (record.type === "specimen"?
               L.circleMarker([record.lat, record.lng], {
                  radius : 8,
                  color  : '#000000',
                  weight: 2,
                  fillColor: `#${taxaMap[record['tid']].color}`,
                  opacity: 1.0,
                  fillOpacity: 1.0
               }):               
               L.marker([record.lat, record.lng], {
                  icon: getObservationSvg({
                     color: `#${taxaMap[record['tid']].color}`, 
                     size: 30
                  })
               }))
               .on('click', function() { openIndPU(record.occid) })
               .bindTooltip(`<div>${record.id}`)

            markers.push(marker);

         }

         document.addEventListener('colorchange', function(e) {
            const [type, id] = e.target.id.split("-");
            const color = e.target.value;

            cluster.removeLayers(markers)

            for (let i = 0; i < markers.length; i++) {
               if(type === "taxa" && recordArr[i]['tid'] === id)
                  markers[i].options.fillColor =`#${color}` 
               else if (type === "coll" && recordArr[i]['collid'] === id) {
                  markers[i].options.fillColor =`#${color}` 
               }
            }
            cluster.addLayers(markers)
         });

         cluster.addLayers(markers)

         cluster.addTo(map.mapLayer);
         if(markers && markers.length > 0) {
            map.mapLayer.fitBounds(cluster.getBounds());
         } else if(map_bounds) {
            map.mapLayer.fitBounds(map_bounds);
         }
      }

      function googleInit() {
         let map = new GoogleMap('map')
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

      function initialize() {
         try {
            const data = document.getElementById('service-container');
            recordArr = JSON.parse(data.getAttribute('data-record-arr'));
            taxaMap = JSON.parse(data.getAttribute('data-taxa-arr'));
            collArr = JSON.parse(data.getAttribute('data-coll-arr'));
            map_bounds = JSON.parse(data.getAttribute('data-map-bounds'));

            searchVar = data.getAttribute('data-search-var');
            if(searchVar) sessionStorage.querystr = searchVar;
         } catch {

         }


         <?php if(!empty($LEAFLET)) { ?> 
            leafletInit();
         <?php } else { ?> 
            googleInit();
         <?php } ?>

         buildPanels();
      }
	</script>
	<script src="../../js/symb/api.taxonomy.taxasuggest.js?ver=4" type="text/javascript"></script>
</head>
<body style='width:100%;max-width:100%;min-width:500px;' <?php echo (!$activateGeolocation?'onload="initialize();"':''); ?>>
<div 
   id="service-container" 
   data-record-arr="<?= htmlspecialchars(json_encode($recordArr))?>"
   data-taxa-arr="<?= htmlspecialchars(json_encode($taxaArr))?>"
   data-coll-arr="<?= htmlspecialchars(json_encode($collArr))?>"
   data-search-var="<?=htmlspecialchars($searchVar)?>"
   data-map-bounds="<?=htmlspecialchars(json_encode($bounds))?>"
   class="service-container" 
/>
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
								<li><a href='occurrencelist.php?<?php echo htmlspecialchars($searchVar, HTML_SPECIAL_CHARS_FLAGS); ?>'><span><?php echo htmlspecialchars((isset($LANG['RECORDS'])?$LANG['RECORDS']:'Records'), HTML_SPECIAL_CHARS_FLAGS); ?></span></a></li>
								<li><a href='#symbology'><span><?php echo htmlspecialchars((isset($LANG['COLLECTIONS'])?$LANG['COLLECTIONS']:'Collections'), HTML_SPECIAL_CHARS_FLAGS); ?></span></a></li>
								<li><a href='#maptaxalist'><span><?php echo htmlspecialchars((isset($LANG['TAXA_LIST'])?$LANG['TAXA_LIST']:'Taxa List'), HTML_SPECIAL_CHARS_FLAGS); ?></span></a></li>
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

