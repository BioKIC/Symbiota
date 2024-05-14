<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCollectionProfile.php');
header("Content-Type: text/html; charset=".$CHARSET);
ini_set('max_execution_time', 1200); //1200 seconds = 20 minutes

$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:0;
if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
//$days = array_key_exists("days",$_REQUEST)?$_REQUEST["days"]:365;
//$months = array_key_exists("months",$_REQUEST)?$_REQUEST["months"]:12;
$years = array_key_exists("years",$_REQUEST)?$_REQUEST["years"]:1;

$days = 365 * $years;
$months = 12 * $years;

$collManager = new OccurrenceCollectionProfile();

if($collId){
	$dateArr = $collManager->getYearStatsHeaderArr($months);
	$statArr = $collManager->getYearStatsDataArr($collId,$days);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<meta name="keywords" content="Natural history collections yearly statistics" />
		<title><?php echo $DEFAULT_TITLE; ?> Year Statistics</title>
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
			<h1 class="page-heading">Year Statistics</h1>
			<fieldset id="yearstatsbox" style="clear:both;margin-top:15px;width:97%;">
				<legend><b>Month Totals</b></legend>
				<table class="styledtable" style="font-family:Arial;font-size:12px;width:98%;">
					<tr>
						<th style="text-align:center;">Institution</th>
						<th style="text-align:center;">Object</th>
						<?php
						foreach($dateArr as $i => $month){
							echo '<th style="text-align:center;">'.$month.'</th>';
						}
						?>
						<th style="text-align:center;">Total</th>
					</tr>
					<?php
					$recCnt = 0;
					foreach($statArr as $code => $data){
						echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
						echo '<td>'.wordwrap($data['collectionname'],32,"<br />\n",true).'</td>';
						echo '<td>Specimens</td>';
						$total = 0;
						foreach($dateArr as $i => $month){
							if(array_key_exists($month,$data['stats'])){
								if(array_key_exists('speccnt',$data['stats'][$month])){
									$total = $total + $data['stats'][$month]['speccnt'];
									echo '<td>'.$data['stats'][$month]['speccnt'].'</td>';
								}
								else{
									echo '<td>0</td>';
								}
							}
							else{
								echo '<td>0</td>';
							}
						}
						echo '<td>'.$total.'</td>';
						echo '</tr>';
						echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
						echo '<td></td>';
						echo '<td>Unprocessed</td>';
						$total = 0;
						foreach($dateArr as $i => $month){
							if(array_key_exists($month,$data['stats'])){
								if(array_key_exists('unprocessedCount',$data['stats'][$month])){
									$total = $total + $data['stats'][$month]['unprocessedCount'];
									echo '<td>'.$data['stats'][$month]['unprocessedCount'].'</td>';
								}
								else{
									echo '<td>0</td>';
								}
							}
							else{
								echo '<td>0</td>';
							}
						}
						echo '<td>'.$total.'</td>';
						echo '</tr>';
						echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
						echo '<td></td>';
						echo '<td>Stage 1</td>';
						$total = 0;
						foreach($dateArr as $i => $month){
							if(array_key_exists($month,$data['stats'])){
								if(array_key_exists('stage1Count',$data['stats'][$month])){
									$total = $total + $data['stats'][$month]['stage1Count'];
									echo '<td>'.$data['stats'][$month]['stage1Count'].'</td>';
								}
								else{
									echo '<td>0</td>';
								}
							}
							else{
								echo '<td>0</td>';
							}
						}
						echo '<td>'.$total.'</td>';
						echo '</tr>';
						echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
						echo '<td></td>';
						echo '<td>Stage 2</td>';
						$total = 0;
						foreach($dateArr as $i => $month){
							if(array_key_exists($month,$data['stats'])){
								if(array_key_exists('stage2Count',$data['stats'][$month])){
									$total = $total + $data['stats'][$month]['stage2Count'];
									echo '<td>'.$data['stats'][$month]['stage2Count'].'</td>';
								}
								else{
									echo '<td>0</td>';
								}
							}
							else{
								echo '<td>0</td>';
							}
						}
						echo '<td>'.$total.'</td>';
						echo '</tr>';
						echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
						echo '<td></td>';
						echo '<td>Stage 3</td>';
						$total = 0;
						foreach($dateArr as $i => $month){
							if(array_key_exists($month,$data['stats'])){
								if(array_key_exists('stage3Count',$data['stats'][$month])){
									$total = $total + $data['stats'][$month]['stage3Count'];
									echo '<td>'.$data['stats'][$month]['stage3Count'].'</td>';
								}
								else{
									echo '<td>0</td>';
								}
							}
							else{
								echo '<td>0</td>';
							}
						}
						echo '<td>'.$total.'</td>';
						echo '</tr>';
						echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
						echo '<td></td>';
						echo '<td>Images</td>';
						$total = 0;
						foreach($dateArr as $i => $month){
							if(array_key_exists($month,$data['stats'])){
								if(array_key_exists('imgcnt',$data['stats'][$month])){
									$total = $total + $data['stats'][$month]['imgcnt'];
									echo '<td>'.$data['stats'][$month]['imgcnt'].'</td>';
								}
								else{
									echo '<td>0</td>';
								}
							}
							else{
								echo '<td>0</td>';
							}
						}
						echo '<td>'.$total.'</td>';
						echo '</tr>';
						echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
						echo '<td></td>';
						echo '<td>Georeferenced</td>';
						$total = 0;
						foreach($dateArr as $i => $month){
							if(array_key_exists($month,$data['stats'])){
								if(array_key_exists('georcnt',$data['stats'][$month])){
									$total = $total + $data['stats'][$month]['georcnt'];
									echo '<td>'.$data['stats'][$month]['georcnt'].'</td>';
								}
								else{
									echo '<td>0</td>';
								}
							}
							else{
								echo '<td>0</td>';
							}
						}
						echo '<td>'.$total.'</td>';
						echo '</tr>';
						$recCnt++;
					}
					?>
				</table>
				<div style='float:right;margin:15px;' title="Save CSV">
					<form name="yearstatscsv" id="yearstatscsv" style="margin-bottom:0px" action="collstatscsv.php" method="post" onsubmit="">
						<input type="hidden" name="collids" id="collids" value='<?php echo $collId; ?>' />
						<input type="hidden" name="days" value="<?php echo $days; ?>" />
						<input type="hidden" name="months" value="<?php echo $months; ?>" />
                        <input type="hidden" name="years" value="<?php echo $years; ?>" />
						<input type="submit" name="action" value="Download CSV" />
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