<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCollectionProfile.php');
include_once($SERVER_ROOT.'/content/lang/collections/misc/collorderstats.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);
ini_set('max_execution_time', 1200); //1200 seconds = 20 minutes

$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:0;
if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$totalCnt = array_key_exists("totalcnt",$_REQUEST)?$_REQUEST["totalcnt"]:0;

$collManager = new OccurrenceCollectionProfile();
$orderArr = Array();

if($collId){
	$orderArr = $collManager->getOrderStatsDataArr($collId);
	ksort($orderArr, SORT_STRING | SORT_FLAG_CASE);
}
$_SESSION['statsOrderArr'] = $orderArr;
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<meta name="keywords" content="Natural history collections yearly statistics" />
		<title><?php echo $DEFAULT_TITLE.' '. $LANG['ORDER_DIST']; ?></title>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
		<script type="text/javascript" src="../../js/symb/collections.index.js"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = (isset($collections_misc_collstatsMenu)?$collections_misc_collstatsMenu:false);
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div role="main" id="innertext">
			<h1 class="page-heading"><?= $LANG['ORDER_DIST']; ?></h1>
			<fieldset id="orderdistbox" style="clear:both;margin-top:15px;width:800px;">
				<legend><b><?php echo (isset($LANG['ORDER_DIST'])?$LANG['ORDER_DIST']:'Order Distribution'); ?></b></legend>
				<table class="styledtable" style="font-family:Arial;font-size:12px;width:780px;">
					<tr>
						<th style="text-align:center;"><?php echo (isset($LANG['ORDER'])?$LANG['ORDER']:'Order'); ?></th>
						<th style="text-align:center;"><?php echo (isset($LANG['SPECIMENS'])?$LANG['SPECIMENS']:'Specimens'); ?></th>
						<th style="text-align:center;"><?php echo (isset($LANG['GEOREFERENCED'])?$LANG['GEOREFERENCED']:'Georeferenced'); ?></th>
						<th style="text-align:center;"><?php echo (isset($LANG['SPECIES_ID'])?$LANG['SPECIES_ID']:'Species ID'); ?></th>
						<th style="text-align:center;"><?php echo (isset($LANG['GEOREFERENCED'])?$LANG['GEOREFERENCED']:'Georeferenced'); ?><br /><?php echo (isset($LANG['AND'])?$LANG['AND']:'and'); ?><br /><?php echo (isset($LANG['SPECIES_ID'])?$LANG['SPECIES_ID']:'Species ID'); ?></th>
					</tr>
					<?php
					$total = 0;
					foreach($orderArr as $name => $data){
						echo '<tr>';
						echo '<td>'.wordwrap($name,52,"<br />\n",true).'</td>';
						echo '<td>';
						if($data['SpecimensPerOrder'] == 1){
							echo '<a href="../list.php?db[]=' . htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&reset=1&taxa=' . htmlspecialchars($name, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">';
						}
						echo number_format($data['SpecimensPerOrder']);
						if($data['SpecimensPerOrder'] == 1){
							echo '</a>';
						}
						echo '</td>';
						echo '<td>'.($data['GeorefSpecimensPerOrder']?round(100*($data['GeorefSpecimensPerOrder']/$data['SpecimensPerOrder'])):0).'%</td>';
						echo '<td>'.($data['IDSpecimensPerOrder']?round(100*($data['IDSpecimensPerOrder']/$data['SpecimensPerOrder'])):0).'%</td>';
						echo '<td>'.($data['IDGeorefSpecimensPerOrder']?round(100*($data['IDGeorefSpecimensPerOrder']/$data['SpecimensPerOrder'])):0).'%</td>';
						echo '</tr>';
						$total = $total + $data['SpecimensPerOrder'];
					}
					?>
				</table>
				<div style="margin-top:10px;float:left;">
					<b><?php echo (isset($LANG['SPEC_W_ORDER'])?$LANG['SPEC_W_ORDER']:'Total Specimens with Order'); ?>:</b> <?php echo number_format($total); ?><br />
					<?php echo (isset($LANG['NO_ORDER'])?$LANG['NO_ORDER']:'Specimens without Order').': '.number_format($totalCnt-$total); ?><br />
				</div>
				<div style='float:left;margin-left:25px;margin-top:10px;width:16px;height:16px;padding:2px;' title="<?php echo (isset($LANG['SAVE_CSV'])?$LANG['SAVE_CSV']:'Save CSV'); ?>">
					<form name="orderstatscsv" id="orderstatscsv" action="collstatscsv.php" method="post" onsubmit="">
						<input type="hidden" name="action" value='<?php echo (isset($LANG['DOWNLOAD_ORDER'])?$LANG['DOWNLOAD_ORDER']:'Download Order Dist'); ?>'/>
						<input type="image" name="action" src="../../images/dl.png" onclick="" />
					</form>
				</div>
			</fieldset>
		</div>
		<!-- end inner text -->
		<?php
			include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>