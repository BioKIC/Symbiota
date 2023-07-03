<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/index.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:'';
if(!preg_match('/^[,\d]+$/',$catId)) $catId = '';
if($catId == '' && isset($DEFAULTCATID)) $catId = $DEFAULTCATID;

$collManager = new OccurrenceManager();
//$collManager->reset();

$collList = $collManager->getFullCollectionList($catId);
$specArr = (isset($collList['spec'])?$collList['spec']:null);
$obsArr = (isset($collList['obs'])?$collList['obs']:null);

$otherCatArr = $collManager->getOccurVoucherProjects();
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE.' '.$LANG['PAGE_TITLE']; ?></title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		include_once($SERVER_ROOT.'/includes/googleanalytics.php');
		?>
		<link href="<?php echo $CSS_BASE_PATH; ?>/symbiota/collections/listdisplay.css" type="text/css" rel="stylesheet" />
		<script src="../js/jquery-3.2.1.min.js" type="text/javascript"></script>
		<script src="../js/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
		<link href="../js/jquery-ui/jquery-ui.min.css" type="text/css" rel="Stylesheet" />
		<script src="../js/symb/collections.index.js?ver=20171215" type="text/javascript"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				$('#tabs').tabs({
					select: function(event, ui) {
						return true;
					},
					beforeLoad: function( event, ui ) {
						$(ui.panel).html("<p>Loading...</p>");
					}
				});
				sessionStorage.querystr = null;
				//document.collections.onkeydown = checkKey;
			});
		</script>
	</head>
	<body>
	<?php
	$displayLeftMenu = (isset($collections_indexMenu)?$collections_indexMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($collections_indexCrumbs)){
		if($collections_indexCrumbs){
			echo '<div class="navpath">';
			echo $collections_indexCrumbs;
			echo ' <b>'.$LANG['NAV_COLLECTIONS'].'</b>';
			echo '</div>';
		}
	}
	else{
		echo '<div class="navpath">';
		echo '<a href="../index.php">'.(isset($LANG['NAV_HOME'])?$LANG['NAV_HOME']:'Home').'</a> &gt;&gt; ';
		echo '<b>'.(isset($LANG['NAV_COLLECTIONS'])?$LANG['NAV_COLLECTIONS']:'Collections').'</b>';
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
        <div id="tabs" style="margin:0px;">
			<ul>
				<?php
				if($specArr && $obsArr) echo '<li><a href="#specobsdiv">'.(isset($LANG['TAB_1'])?$LANG['TAB_1']:'Specimens & Observations').'</a></li>';
				if($specArr) echo '<li><a href="#specimendiv">'.(isset($LANG['TAB_2'])?$LANG['TAB_2']:'Specimens').'</a></li>';
				if($obsArr) echo '<li><a href="#observationdiv">'.(isset($LANG['TAB_3'])?$LANG['TAB_3']:'Observations').'</a></li>';
				if($otherCatArr) echo '<li><a href="#otherdiv">'.(isset($LANG['TAB_4'])?$LANG['TAB_4']:'Federal Units').'</a></li>';
				?>
			</ul>
			<?php
			if($specArr && $obsArr){
				?>
				<div id="specobsdiv">
					<form name="collform1" action="harvestparams.php" method="post" onsubmit="return verifyCollForm(this)">
						<div style="margin:0px 0px 10px 5px;">
							<input id="dballcb" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" checked />
					 		<?php echo $LANG['SELECT_DESELECT'].' <a href="misc/collprofiles.php">'.$LANG['ALL_COLLECTIONS'].'</a>'; ?>
						</div>
						<?php
						$collManager->outputFullCollArr($specArr, $catId);
						if($specArr && $obsArr) echo '<hr style="clear:both;margin:20px 0px;"/>';
						$collManager->outputFullCollArr($obsArr, $catId);
						?>
						<div style="clear:both;">&nbsp;</div>
					</form>
				</div>
				<?php
			}
			if($specArr){
				?>
				<div id="specimendiv">
					<form name="collform2" action="harvestparams.php" method="post" onsubmit="return verifyCollForm(this)">
						<div style="margin:0px 0px 10px 20px;">
							<input id="dballspeccb" name="db[]" class="spec" value='allspec' type="checkbox" onclick="selectAll(this);" checked />
					 		<?php echo $LANG['SELECT_DESELECT'].' <a href="misc/collprofiles.php">'.$LANG['ALL_COLLECTIONS'].'</a>'; ?>
						</div>
						<?php
						$collManager->outputFullCollArr($specArr, $catId);
						?>
						<div style="clear:both;">&nbsp;</div>
					</form>
				</div>
				<?php
			}
			if($obsArr){
				?>
				<div id="observationdiv">
					<form name="collform3" action="harvestparams.php" method="post" onsubmit="return verifyCollForm(this)">
						<div style="margin:0px 0px 10px 20px;">
							<input id="dballobscb" name="db[]" class="obs" value='allobs' type="checkbox" onclick="selectAll(this);" checked />
					 		<?php echo $LANG['SELECT_DESELECT'].' <a href="misc/collprofiles.php">'.$LANG['ALL_COLLECTIONS'].'</a>'; ?>
						</div>
						<?php
						$collManager->outputFullCollArr($obsArr, $catId);
						?>
						<div style="clear:both;">&nbsp;</div>
					</form>
				</div>
				<?php
			}
			if($otherCatArr && isset($otherCatArr['titles'])){
				$catTitleArr = $otherCatArr['titles']['cat'];
				asort($catTitleArr);
				?>
				<div id="otherdiv">
					<form id="othercatform" action="harvestparams.php" method="post" onsubmit="return verifyOtherCatForm(this)">
						<?php
						foreach($catTitleArr as $catPid => $catTitle){
							?>
							<fieldset style="margin:10px;padding:10px;">
								<legend style="font-weight:bold;"><?php echo $catTitle; ?></legend>
								<div style="margin:0px 15px;float:right;">
									<button type="submit" name="submitaction"><?php echo isset($LANG['BUTTON_NEXT'])?$LANG['BUTTON_NEXT']:'Next >'; ?></button>
								</div>
								<?php
								$projTitleArr = $otherCatArr['titles'][$catPid]['proj'];
								asort($projTitleArr);
								foreach($projTitleArr as $pid => $projTitle){
									?>
									<div>
										<a href="#" onclick="togglePid('<?php echo $pid; ?>');return false;"><img id="plus-pid-<?php echo $pid; ?>" src="../images/plus_sm.png" /><img id="minus-pid-<?php echo $pid; ?>" src="../images/minus_sm.png" style="display:none;" /></a>
										<input name="pid[]" type="checkbox" value="<?php echo $pid; ?>" onchange="selectAllPid(this);" />
										<b><?php echo $projTitle; ?></b>
									</div>
									<div id="pid-<?php echo $pid; ?>" style="margin:10px 15px;display:none;">
										<?php
										$clArr = $otherCatArr[$pid];
										asort($clArr);
										foreach($clArr as $clid => $clidName){
											?>
											<div>
												<input name="clid[]" class="pid-<?php echo $pid; ?>" type="checkbox" value="<?php echo $clid; ?>" />
												<?php echo $clidName; ?>
											</div>
											<?php
										}
										?>
									</div>
									<?php
								}
								?>
							</fieldset>
							<?php
						}
						?>
					</form>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
	</body>
</html>