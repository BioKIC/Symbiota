<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
@include_once($SERVER_ROOT.'/content/lang/header.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$pid = $_REQUEST['pid'];
$target = array_key_exists('target',$_REQUEST)?$_REQUEST['target']:'checklists';

//Sanitation
$pid = htmlspecialchars($pid, HTML_SPECIAL_CHARS_FLAGS);
if(!is_numeric($pid)) $pid = 0;

$clManager = new ChecklistManager();
$clManager->setProj($pid);
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE.' - '.(isset($LANG['H_INVENTORIES'])?$LANG['H_INVENTORIES']:'Species Checklists'); ?></title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<script src="//maps.googleapis.com/maps/api/js?<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'key='.$GOOGLE_MAP_KEY:''); ?>"></script>
		<script type="text/javascript">
			var map;
			var points = new Array();
			var infoWins = new Array();

			function initialize(){
				var dmLatLng = new google.maps.LatLng(41.0, -95.0);
				var dmOptions = {
					zoom: 13,
					center: dmLatLng,
					mapTypeId: google.maps.MapTypeId.TERRAIN
				};

				map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);
				<?php
				$clArr = $clManager->getResearchPoints();
				foreach($clArr as $clid => $inArr){
					echo "var point".$clid." = new google.maps.LatLng(".$inArr['lat'].", ".$inArr['lng'].");\n";
					echo "points.push( point".$clid." );\n";
					echo 'var marker'.$clid.' = new google.maps.Marker({ position: point'.$clid.', map: map, title: "'.$inArr['name'].'" });'."\n";
					//Single click event
					echo 'var infoWin'.$clid.' = new google.maps.InfoWindow({ content: "<div style=\'width:300px;\'><b>'.$inArr['name'].'</b><br/>Double Click to open</div>" });'."\n";
					echo "infoWins.push( infoWin".$clid." );\n";
					echo "google.maps.event.addListener(marker".$clid.", 'click', function(){ closeAllInfoWins(); infoWin".$clid.".open(map,marker".$clid."); });\n";
					//Double click event
					if($target == 'keys'){
						echo "var lStr".$clid." = '../ident/key.php?clid=".$clid."&pid=" . $pid."&taxon=All+Species';\n";
					}
					else{
						echo "var lStr".$clid." = 'checklist.php?clid=".$clid."&pid=" . $pid."';\n";
					}
					echo "google.maps.event.addListener(marker".$clid.", 'dblclick', function(){ closeAllInfoWins(); marker".$clid.".setAnimation(google.maps.Animation.BOUNCE); window.location.href = lStr".$clid."; });\n";
				}
				?>
				resizeMap();
			}

			function resizeMap() {
				var minLng = 180;
				var minLat = 180;
				var maxLng = -180;
				var maxLat = -180;

				// Find the max/min points
				for( var i = 0; i < points.length; i++ ) {
					var p = points[i];
					if ( p.lat() < minLat ) minLat = p.lat();
					if ( p.lat() > maxLat ) maxLat = p.lat();
					if ( p.lng() < minLng ) minLng = p.lng();
					if ( p.lng() > maxLng ) maxLng = p.lng();
				}
				var swLatLng = new google.maps.LatLng(minLat, minLng);
				var neLatLng = new google.maps.LatLng(maxLat, maxLng);
				if(minLat == maxLat && minLng == maxLng){
					map.setCenter(swLatLng);
				}
				else{
					var llBounds = new google.maps.LatLngBounds(swLatLng, neLatLng);
					map.fitBounds(llBounds);
				}
			}

			function closeAllInfoWins(){
				for( var w = 0; w < infoWins.length; w++ ) {
					var win = infoWins[w];
					win.close();
				}
			}
		</script>
		<style>
			html, body, #map_canvas {
				width: 100%;
				height: 100%;
				margin: 0;
				padding: 0;
			}
		</style>
	</head>
	<body style="background-color:#ffffff;" onload="initialize()">
		<div id="map_canvas"></div>
	</body>
</html>