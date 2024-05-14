<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCrowdSource.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/specprocessor/crowdsource/index.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/crowdsource/index.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/crowdsource/index.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$catid = array_key_exists('catid',$_REQUEST)?$_REQUEST['catid']:'';

if(isset($DEFAULTCATID) && $DEFAULTCATID && $catid === '') $catid = $DEFAULTCATID;

$csManager = new OccurrenceCrowdSource();

$pArr = array();
if($SYMB_UID){
	if(array_key_exists("CollAdmin",$USER_RIGHTS)){
		$pArr = $USER_RIGHTS['CollAdmin'];
	}
}

$statusStr = '';
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['CROWDSOURCE_SCORE_BOARD']; ?></title>
	<?php

	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript">

	</script>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($crowdsourcecentral_listCrumbs)){
		if($crowdsourcecentral_listCrumbs){
			echo $crowdsourcecentral_listCrumbs;
		}
	}
	else{
		echo "<div class='navpath'>";
		echo "<a href='../../../index.php'>Home</a> &gt;&gt; ";
		echo "<b>".$LANG['CROWDSOURCE_SCORE_BOARD']."</b>";
		echo "</div>";
	}
	?>

	<!-- inner text -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?php echo $LANG['CROWDSOURCE_SCORE_BOARD']; ?></h1>

		<div style="margin:20px;">
			<h2><?php echo $LANG['TOP_SCORES']; ?></h2>
			<table class="styledtable" style="font-family:Arial;font-size:12px;width:300px;">
				<tr><th style="min-width: 150px"><b><?php echo $LANG['USER']; ?></b></th><th style="text-align:center"><b><?php echo $LANG['APPROVED_SCORE']; ?></b></th><th style="text-align:center"><b><?php echo $LANG['PENDING_SCORE']; ?></b></th></tr>
				<?php
				$topScoreArr = $csManager->getTopScores($catid);
				if($topScoreArr){
					foreach($topScoreArr as $userName => $pArr){
						$approved = 0;
						if(isset($pArr[10])) $approved = $pArr[10];
						$pending = $approved;
						if(isset($pArr[5])) $pending = $pArr[5];
						echo '<tr><td style="">'.$userName.'</td>';
						echo '<td style="text-align:center">'.number_format($approved).'</td>';
						echo '<td style="text-align:center">'.number_format($pending).'</td></tr>';
					}
				}
				else echo '<tr><td>'.$LANG['TOP_SCORES_NOT_AVAIL'].'</td><td>------</td></tr>';
				?>
			</table>
		</div>

		<div style="margin-top:30px;margin-left:20px;clear:both">
			<?php
			$userStats = $csManager->getUserStats($catid);
			?>
			<fieldset style="background-color:white;margin-bottom:15px;width:600px;padding:15px;">
				<legend><b><?php echo $LANG['YOUR_STANDING']; ?></b></legend>
				<?php
				if($SYMB_UID){
					echo '<div style="margin-top:5px">' . $LANG['SPEC_PROC_AS_VOL'] . ': ' . number_format($userStats['totalcnt']) . '</div>';
					if($userStats['nonvolcnt']) echo '<div style="margin-left:25px">(' . $LANG['ADD_AS_NONVOL'] . ': '.number_format($userStats['nonvolcnt']) . '*)</div>';
					echo '<div style="margin-top:5px">' . $LANG['PEND_POINTS'] . ': '. number_format($userStats['ppoints']);
					if($userStats['ppoints']) echo ' (<a href="review.php?rstatus=5&uid=' . htmlspecialchars($SYMB_UID, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) .  '">' . htmlspecialchars($LANG['VIEW_RECORDS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>)';
					echo '</div>';
					echo '<div style="margin-top:5px">' . $LANG['APP_POINTS'] . ': ' . number_format($userStats['apoints']);
					if($userStats['apoints']) echo ' (<a href="review.php?rstatus=10&uid=' . htmlspecialchars($SYMB_UID, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($LANG['VIEW_RECORDS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>)';
					echo '</div>';
					echo '<div style="margin-top:5px">' . $LANG['TOT_POSS_SCORE'] . ': ' . number_format($userStats['ppoints']+$userStats['apoints']) . '</div>';
					if($userStats['nonvolcnt']) echo '<div style="margin-top:10px">* ' . $LANG['ONLY_PROCESSED_SPECIMENS_ELIGIBLE'] . '</div>';
				}
				else{
					?>
					<div>
						<a href="../../../profile/index.php?refurl=../collections/specprocessor/crowdsource/index.php"><?php echo htmlspecialchars($LANG['LOGIN'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> <?php echo htmlspecialchars($LANG['TO_VIEW_CURRENT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
					</div>
					<?php
				}
				?>
			</fieldset>
		</div>
		<div style="padding:20px;clear:both;">
			<h2><?php echo $LANG['YOUR_STATS_BY_COLL']; ?></h2>
			<table class="styledtable" style="font-family:Arial;font-size:12px;">
				<tr>
					<th><b><?php echo $LANG['COLLECTION']; ?></b></th>
					<th><b><?php echo $LANG['SPEC_COUNTS']; ?></b></th>
					<th><b><?php echo $LANG['PENDING_POINTS']; ?></b></th>
					<th><b><?php echo $LANG['APPROVED_POINTS']; ?></b></th>
					<th><b><?php echo $LANG['OPEN_RECORDS']; ?></b></th>
				</tr>
				<?php
				unset($userStats['totalcnt']);
				unset($userStats['nonvolcnt']);
				unset($userStats['apoints']);
				unset($userStats['ppoints']);
				foreach($userStats as $collId => $sArr){
					$pointArr = $sArr['points'];
					$cntArr = $sArr['cnt'];
					echo '<tr>';
					echo '<td>';
					echo '<b>'.$sArr['name'].'</b>';
					if($IS_ADMIN || in_array($collId, $pArr)) echo ' <a href="../index.php?tabindex=1&collid=' . htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><img src="../../../images/edit.png" style="width:1.3em;" /></a>';
					echo '</td>';
					echo '<td>'.number_format((array_key_exists(5,$cntArr)?$cntArr[5]:0)+(array_key_exists(10,$cntArr)?$cntArr[10]:0)).'</td>';
					echo '<td>'.number_format(array_key_exists(5,$pointArr)?$pointArr[5]:0).'</td>';
					echo '<td>'.number_format(array_key_exists(10,$pointArr)?$pointArr[10]:0).'</td>';
					echo '<td><a href="../../editor/occurrencetabledisplay.php?csmode=1&occindex=0&displayquery=1&reset=1&collid=' . htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars(number_format(array_key_exists(0,$cntArr)?$cntArr[0]:0), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></td>';
					echo '</tr>';
				}
				?>
			</table>
		</div>
		<?php
		if(isset($USER_RIGHTS['CollAdmin']) || isset($USER_RIGHTS['CollEditor'])){
			?>
			<div style="clear:both;margin:30px;">
				<?php echo $LANG['NOTE_EDITOR']; ?>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>