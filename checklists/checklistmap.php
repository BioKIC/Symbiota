<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
@include_once($SERVER_ROOT.'/content/lang/checklists/checklistmap.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

$clid = filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT);
$thesFilter = array_key_exists('thesfilter',$_REQUEST)?filter_var($_REQUEST['thesfilter'], FILTER_SANITIZE_NUMBER_INT):1;
$taxonFilter = array_key_exists('taxonfilter',$_REQUEST)?filter_var($_REQUEST['taxonfilter'], FILTER_SANITIZE_STRING):'';

if(!$thesFilter) $thesFilter = 1;

$clManager = new ChecklistManager();
$clManager->setClid($clid);
if($thesFilter) $clManager->setThesFilter($thesFilter);
if($taxonFilter) $clManager->setTaxonFilter($taxonFilter);

$coordArr = $clManager->getVoucherCoordinates();
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE.' - '.(isset($LANG['COORD_MAP'])?$LANG['COORD_MAP']:'Checklist Coordinate Map'); ?></title>
	<?php
	//include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'&key='.$GOOGLE_MAP_KEY:''); ?>&callback=Function.prototype"></script>
	<script type="text/javascript">
		var map;
		var puWin;

		function initialize(){
			var dmOptions = {
				zoom: 3,
				center: new google.maps.LatLng(41,-95),
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				scaleControl: true
			};

			var llBounds = new google.maps.LatLngBounds();
			<?php
			if($coordArr){
				?>
				map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);
				var vIcon = new google.maps.MarkerImage("../images/google/smpin_red.png");
				var pIcon = new google.maps.MarkerImage("../images/google/smpin_blue.png");
				<?php
				$mCnt = 0;
				foreach($coordArr as $tid => $cArr){
					foreach($cArr as $pArr){
						?>
						var pt = new google.maps.LatLng(<?php echo $pArr['ll']; ?>);
						llBounds.extend(pt);
						<?php
						if(array_key_exists('occid',$pArr)){
							?>
							var m<?php echo $mCnt; ?> = new google.maps.Marker({position: pt, map:map, title:"<?php echo $pArr['notes']; ?>", icon:vIcon});
							google.maps.event.addListener(m<?php echo $mCnt; ?>, "click", function(){ openIndPU(<?php echo $pArr['occid']; ?>); });
							<?php
						}
						else{
							?>
							var m<?php echo $mCnt; ?> = new google.maps.Marker({position: pt, map:map, title:"<?php echo $pArr['sciname']; ?>", icon:pIcon});
							<?php
						}
						$mCnt++;
					}
				}
			}
			$clMeta = $clManager->getClMetaData();
			if($clMeta['footprintwkt']){
				//Add checklist polygon
				$wkt = $clMeta['footprintwkt'];
				if(substr($wkt,0,7) == 'POLYGON') $wkt = substr($wkt,7);
				else if(substr($wkt,0,12) == 'MULTIPOLYGON') $wkt = substr($wkt,12);
				$coordArr = explode('),(', $wkt);
				foreach($coordArr as $k => $polyFrag){
					if($pointArr = explode(',', trim($polyFrag,' (),'))){
						echo 'var polyPointArr'.$k.' = [];';
						foreach($pointArr as $pointStr){
							$llArr = explode(' ', trim($pointStr));
							if($llArr[0] > 90 || $llArr[0] < -90) break;
							?>
							var polyPt = new google.maps.LatLng(<?php echo $llArr[0].','.$llArr[1]; ?>);
							polyPointArr<?php echo $k; ?>.push(polyPt);
							llBounds.extend(polyPt);
							<?php
						}
						?>
						var footPoly<?php echo $k; ?> = new google.maps.Polygon({
							paths: polyPointArr<?php echo $k; ?>,
							strokeWeight: 2,
							fillOpacity: 0.4,
							map: map
						});
						<?php
					}
				}
			}
			?>
			map.fitBounds(llBounds);
			map.panToBounds(llBounds);
		}

		function openIndPU(occId){
			if(puWin != null) puWin.close();
			var puWin = window.open('../collections/individual/index.php?occid='+occId,'indspec' + occId,'scrollbars=1,toolbar=0,resizable=1,width=900,height=600,left=20,top=20');
			if(puWin.opener == null) puWin.opener = self;
			setTimeout(function () { puWin.focus(); }, 0.5);
			return false;
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
<body style="background-color:#ffffff;" onload="initialize();">
<?php
	if(!$coordArr){
		?>
		<div style='font-size:120%;font-weight:bold;'>
			<?php echo (isset($LANG['NO_COORDS'])?$LANG['NO_COORDS']:'Your query apparently does not contain any records with coordinates that can be mapped'); ?>.
		</div>
		<div style="margin:15px;">
			<?php echo (isset($LANG['MAYBE_RARE'])?$LANG['MAYBE_RARE']:'It may be that the vouchers have rare/threatened status that require the locality coordinates be hidden'); ?>.
		</div>
		<?php
	}
	?>
	<div id='map_canvas'></div>
</body>
</html>