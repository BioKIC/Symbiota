<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMapManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/map/mapbox.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/map/mapbox.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/map/mapbox.en.php');

header("Content-Type: text/html; charset=".$CHARSET);

$clid = array_key_exists('clid',$_REQUEST)?$_REQUEST['clid']:0;
$gridSize = array_key_exists('gridSizeSetting',$_REQUEST)?$_REQUEST['gridSizeSetting']:10;
$minClusterSize = array_key_exists('minClusterSetting',$_REQUEST)?$_REQUEST['minClusterSetting']:50;

$occurManager = new OccurrenceMapManager();
$coordArr = $occurManager->getMappingData(0);

//Build taxa mapping key
$colorKey = Array();
$taxaKey = Array();
$taxaArr = $occurManager->getTaxaArr();
if(array_key_exists('taxa', $taxaArr)){
	foreach($taxaArr['taxa'] as $scinameStr => $snArr){
		if(isset($snArr['tid'])){
			$snTid = key($snArr['tid']);
			$taxaKey[$snTid]['str'] = $scinameStr;
			$taxaKey[$snTid]['target'] = $snTid;
			if(array_key_exists('synonyms', $snArr)){
				foreach($snArr['synonyms'] as $synTid => $synStr){
					$taxaKey[$synTid]['str'] = $synStr;
					$taxaKey[$synTid]['target'] = $snTid;
				}
			}
		}
		else{
			$taxaKey['orphan'][$scinameStr] = '';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> - <?php echo $LANG['TAXON_MAP']; ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin=""/>
	<script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js" integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew==" crossorigin=""></script>
	<style>
		#mapid { width:100%; height:700px; }
	</style>
	<script type="text/javascript">
		function openIndPU(occId,clid){
			newWindow = window.open('../individual/index.php?occid='+occId+'&clid='+clid,'indspec' + occId,'scrollbars=1,toolbar=0,resizable=1,width=1100,height=800,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
			setTimeout(function () { newWindow.focus(); }, 0.5);
		}

		function addRefPoint(){
			var lat = document.getElementById("lat").value;
			var lng = document.getElementById("lng").value;
			var title = document.getElementById("title").value;
			if(!useLLDecimal){
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
			if(lat != null && lng != null){
				if(lat < -180 || lat > 180 || lng < -180 || lng > 180){
					window.alert("<?php echo $LANG['LAT_LONG_VALUES']; ?> (" + lat + ";" + lng + ")");
				}
				else{
					var addPoint = true;
					if(lng > 0) addPoint = window.confirm("<?php echo $LANG['LONGITUDE_IS_POSITIVE']; ?>?");
					if(!addPoint) lng = -1*lng;

					var iconImg = new google.maps.MarkerImage( '../../images/google/arrow.png' );

					var m = new google.maps.Marker({
						position: new google.maps.LatLng(lat,lng),
						map: map,
						title: title,
						icon: iconImg,
						zIndex: google.maps.Marker.MAX_ZINDEX
					});
				}
			}
			else{
				window.alert("<?php echo $LANG['ENTER_VALUES_IN_LAT_LONG']; ?>");
			}
		}

		function toggleLatLongDivs(){
			var divs = document.getElementsByTagName("div");
			for (i = 0; i < divs.length; i++) {
				var obj = divs[i];
				if(obj.getAttribute("class") == "latlongdiv" || obj.getAttribute("className") == "latlongdiv"){
					if(obj.style.display=="none"){
						obj.style.display="block";
					}
					else{
						obj.style.display="none";
					}
				}
			}
			if(useLLDecimal) useLLDecimal = false;
			else useLLDecimal = true;
		}

	</script>
</head>
<body style="background-color:#ffffff;width:100%">
	<h1 class="page-heading"><?php echo $LANG['TAXON_MAP']; ?></h1>
	<?php
	if(!$coordArr){
		?>
			<div style="font-size:120%;font-weight:bold;">
				<?php echo $LANG['QUERY_DOES_NOT_CONTAIN_RECORDS']; ?>.
			</div>
			<div style="margin-left:20px;">
				<?php echo $LANG['EITHER_REC_NOT_GEOREF']; ?><br/><br/>
			</div>
			<div style="margin-left:100px;">
				-<?php echo $LANG['OR']; ?>-
			</div>
			<div style="margin-left:20px;">
				<?php echo $LANG['RARE_STATUS_REQUIRES']; ?>.
			</div>
		<?php
	}
	?>
	<div id="mapid"></div>

	<table title='<?php echo $LANG['ADD_POINT_REF']; ?>' style="width:100%;" >
		<tr>
			<td style="width:330px" valign='top'>
				<fieldset>
					<legend><?php echo $LANG['LEGEND']; ?></legend>
					<div style="float:left;">
						<?php
						foreach($colorKey as $iconKey => $colorCode){
							echo '<div>';
							echo '<svg xmlns="http://www.w3.org/2000/svg" style="height:12px;width:12px;margin-bottom:-2px;"><g><rect x="1" y="1" width="11" height="10" fill="#'.$colorCode.'" stroke="#000000" stroke-width="1px" /></g></svg> ';
							if(!$iconKey) echo '= ' . $LANG['VARIOUS_TAXA'];
							elseif(is_numeric($iconKey)) echo '= <i>'.$taxaKey[$iconKey]['str'].'</i>';
							elseif(isset($taxaKey['orphan'][$iconKey])) echo '= <i>'.$iconKey.'</i>';
							else echo '= ' . $LANG['VARIOUS_TAXA'];
							echo '</div>';
						}
						?>
					</div>
					<div style="float:right;">
						<div>
							<svg xmlns="http://www.w3.org/2000/svg" style="height:15px;width:15px;margin-bottom:-2px;">">
								<g>
									<circle cx="7.5" cy="7.5" r="7" fill="white" stroke="#000000" stroke-width="1px" ></circle>
								</g>
							</svg> = <?php echo $LANG['COLLECTION']; ?>
						</div>
						<div>
							<svg style="height:14px;width:14px;margin-bottom:-2px;">" xmlns="http://www.w3.org/2000/svg">
								<g>
									<path stroke="#000000" d="m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z" stroke-width="1px" fill="white"/>
								</g>
							</svg> = <?php echo $LANG['OBSERVATION']; ?>
						</div>
					</div>
				</fieldset>
			</td>
			<td style="width:375px;" valign='top'>
				<div>
					<fieldset>
						<legend><?php echo $LANG['ADD_POINT_REF']; ?></legend>
						<div style='float:left;width:350px;'>
							<div class="latlongdiv">
								<div>
									<?php echo $LANG['LAT_DEC']; ?>: <input name='lat' id='lat' size='10' type='text' /> eg: 34.57
								</div>
								<div style="margin-top:5px;">
									<?php echo $LANG['LONG_DEC']; ?>: <input name='lng' id='lng' size='10' type='text' /> eg: -112.38
								</div>
								<div style='font-size:80%;margin-top:5px;'>
									<a href='#' onclick='toggleLatLongDivs();'><?php echo $LANG['ENTER_IN_DMS_FORMAT']; ?></a>
								</div>
							</div>
							<div class='latlongdiv' style='display:none;'>
								<div>
									<?php echo $LANG['LAT']; ?>:
									<input name='latdeg' id='latdeg' size='2' type='text' />&deg;
									<input name='latmin' id='latmin' size='5' type='text' />&prime;
									<input name='latsec' id='latsec' size='5' type='text' />&Prime;
									<select name='latns' id='latns'>
										<option value='N'><?php echo $LANG['NORTH']; ?></option>
										<option value='S'><?php echo $LANG['SOUTH']; ?></option>
									</select>
								</div>
								<div style="margin-top:5px;">
									<?php echo $LANG['LONG']; ?>:
									<input name='longdeg' id='longdeg' size='2' type='text' />&deg;
									<input name='longmin' id='longmin' size='5' type='text' />&prime;
									<input name='longsec' id='longsec' size='5' type='text' />&Prime;
									<select name='longew' id='longew'>
										<option value='E'><?php echo $LANG['EAST']; ?></option>
										<option value='W' selected><?php echo $LANG['WEST']; ?></option>
									</select>
								</div>
								<div style='font-size:80%;margin-top:5px;'>
									<a href='#' onclick='toggleLatLongDivs();'><?php echo $LANG['ENTER_IN_DEC_FORMAT']; ?></a>
								</div>
							</div>
						</div>
						<div style="float:right;width:100px;">
							<div style="float:right;">
								<?php echo $LANG['MARKER_NAME']; ?>: <input name='title' id='title' size='20' type='text' />
							</div><br />
							<div style="float:right;margin-top:10px;">
								<button type='submit' value='Add Marker' onclick='addRefPoint();' ><?php echo $LANG['ADD_MARKER']; ?></button>
							</div>
						</div>
					</fieldset>
				</div>
			</td>
		</tr>
	</table>

	<script>
		<?php
		$boundLatMin = -90;
		$boundLatMax = 90;
		$boundLngMin = -180;
		$boundLngMax = 180;
		$latCen = 41.0;
		$longCen = -95.0;
		if(isset($MAPPING_BOUNDARIES)){
			$coorArr = explode(";",$MAPPING_BOUNDARIES);
			if($coorArr && count($coorArr) == 4){
				$boundLatMin = $coorArr[2];
				$boundLatMax = $coorArr[0];
				$boundLngMin = $coorArr[3];
				$boundLngMax = $coorArr[1];
				$latCen = ($boundLatMax + $boundLatMin)/2;
				$longCen = ($boundLngMax + $boundLngMin)/2;
			}
		}
		?>

		var mapObj = L.map('mapid').setView([<?php echo $latCen.','.$longCen; ?>], 3);

		L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=<?php echo (isset($MAPBOX_API_KEY) && $MAPBOX_API_KEY?$MAPBOX_API_KEY:''); ?>', {
			attribution: '<?php echo $LANG['MAP_DATA']; ?> &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> <?php echo $LANG['CONTRIBUTORS']; ?>, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, <?php echo $LANG['IMAGERY']; ?> © <a href="https://www.mapbox.com/">Mapbox</a>',
			minZoom: 1,
			maxZoom: 18,
			id: 'taxon/streets-v11',
			tileSize: 512,
			zoomOffset: -1
		}).addTo(mapObj);

		var baseLayers = {
			"Mapbox": mapbox,
			"OpenStreetMap": osm
		};
		var overlays = {
			"Marker": marker,
			"Roads": roadsLayer
		};
		L.control.layers(baseLayers, overlays).addTo(mapObj);
		<?php
		$markerCnt = 0;
		$spCnt = 0;
		$iconColors = array('fc6355','5781fc','fcf357','00e13c','e14f9e','55d7d7','ff9900','7e55fc');
		$iconColorKey = 0;
		foreach($coordArr as $sciName => $valueArr){
			$iconColor = '';
			$tid = 0;
			if(array_key_exists('tid', $valueArr)){
				$tid = $valueArr['tid'];
				unset($valueArr['tid']);
				if(isset($taxaKey[$tid]['target'])){
					$tid = $taxaKey[$tid]['target'];
				}
				elseif($sciName != 'undefined' && isset($taxaKey['orphan'])){
					foreach($taxaKey['orphan'] as $nameKey => $nameRaw){
						if(stripos($sciName,$nameKey) !== false) $tid = $nameKey;
					}
				}
				else{
					$tid = 0;
				}
			}
			elseif($sciName != 'undefined' && isset($taxaKey['orphan'])){
				foreach($taxaKey['orphan'] as $nameKey => $nameRaw){
					if(stripos($sciName,$nameKey) !== false) $tid = $nameKey;
				}
			}
			if(!array_key_exists($tid, $colorKey)){
				$colorKey[$tid] = $iconColors[$iconColorKey%8];
				$iconColorKey++;
			}
			$iconColor = $colorKey[$tid];

			foreach($valueArr as $occid => $spArr){
				echo 'var m'.$markerCnt.' = L.marker(['.$spArr['lat'].','.$spArr['lng'].']).addTo(mapObj);'."\n";
				//echo 'm'.$markerCnt.'.bindPopup("<b>Hello world!</b><br>I am a popup.");';
				$markerCnt++;
			}
			$spCnt++;
		}
		?>
	</script>

</body>
</html>
