<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorManager.php');
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/specprocessor/index.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT);
$menu = !empty($_REQUEST['menu']) ? 1 : 0;
$formAction = array_key_exists('formaction',$_REQUEST) ? $_REQUEST['formaction'] : 0;

$procManager = new SpecProcessorManager();
$procManager->setCollId($collid);
$tabIndex = 3;

$isEditor = false;
if($IS_ADMIN || (array_key_exists('CollAdmin', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollAdmin']))){
 	$isEditor = true;
}
?>
<div role="main" id="innertext" style="background-color:white;">
	<h1 class="page-heading screen-reader-only">Reports</h1>
	<?php
	if($isEditor){
		$reportTypes = array(0 => 'General Stats', 1 => 'User Stats');
		?>
		<form name="filterForm" action="index.php" method="get">
			<b>Report Type:</b>
			<select name="menu" onchange="this.form.submit()">
				<?php
				foreach($reportTypes as $k => $v){
					echo '<option value="'.$k.'" '.($menu==$k?'SELECTED':'').'>'.$v.'</option>';
				}
				?>
			</select>
			<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
			<input name="tabindex" type="hidden" value="<?php echo $tabIndex; ?>" />
		</form>

		<fieldset style="padding:15px">
			<legend><b><?php echo $reportTypes[$menu]; ?></b></legend>
			<?php
			$urlBase = '&occindex=0&q_catalognumber=';
			$eUrl = '../editor/occurrenceeditor.php?collid='.$collid;
			$beUrl = '../editor/occurrencetabledisplay.php?collid='.$collid;
			if(!$menu){
				//General stats
				$statsArr = $procManager->getProcessingStats();
				?>
				<div style="margin:10px;height:400px;">
					<div style="margin:5px;">
						<b>Total Specimens:</b>
						<?php
						echo $statsArr['total'];
						if($statsArr['total']){
							echo '<span style="margin-left:10px;"><a href="' . $eUrl . $urlBase . '" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
							echo '<span style="margin-left:10px;"><a href="' . $beUrl . $urlBase . '" target="_blank" title="Editor in Table View"><img src="../../images/list.png" style="width:12px;" /></a></span>';
							echo '<span style="margin-left:10px;"><a href="../misc/collbackup.php?collid=' . $collid . '" target="_blank" title="Download Full Data"><img src="../../images/dl.png" style="width:13px;" /></a></span>';
						}
						?>
					</div>
					<div style="margin:5px;">
						<b>Specimens without linked images:</b>
						<?php
						echo $statsArr['noimg'];
						if($statsArr['noimg']){
							$eUrl1 = $eUrl.$urlBase.'&q_withoutimg=1';
							$beUrl1 = $beUrl.$urlBase.'&q_withoutimg=1';
							echo '<span style="margin-left:10px;"><a href="' . $eUrl1 . '" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
							echo '<span style="margin-left:10px;"><a href="' . $beUrl1 . '" target="_blank" title="Batch Edit Records"><img src="../../images/list.png" style="width:12px;" /></a></span>';
							echo '<span style="margin-left:10px;"><a href="processor.php?submitaction=dlnoimg&tabindex=' . $tabIndex . '&collid=' . $collid . '" target="_blank" title="Download Report File"><img src="../../images/dl.png" style="width:13px;" /></a></span>';
						}
						?>
					</div>
					<?php
					if($statsArr['noskel']){
						?>
						<div style="margin:5px;">
							<b>Unprocessed records without Skeletal Data:</b>
							<?php
							echo $statsArr['noskel'];
							if($statsArr['noskel']){
								$eUrl3 = $eUrl.$urlBase.'&q_processingstatus=unprocessed&q_customfield1=stateProvince&q_customtype1=NULL&q_customfield2=sciname&q_customtype2=NULL';
								$beUrl3 = $beUrl.$urlBase.'&q_processingstatus=unprocessed&q_customfield1=stateProvince&q_customtype1=NULL&q_customfield2=sciname&q_customtype2=NULL';
								echo '<span style="margin-left:10px;"><a href="' . $eUrl3 . '" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
								echo '<span style="margin-left:10px;"><a href="' . $beUrl3 . '" target="_blank" title="Batch Edit Records"><img src="../../images/list.png" style="width:12px;" /></a></span>';
								echo '<span style="margin-left:10px;"><a href="processor.php?submitaction=noskel&tabindex=' . $tabIndex . '&collid=' . $collid . '" target="_blank" title="Download Report File"><img src="../../images/dl.png" style="width:14px;" /></a></span>';
							}
							?>
						</div>
						<?php
					}
					if($statsArr['unprocnoimg']){
						?>
						<div style="margin:5px;">
							<b>Unprocessed records without Images (<span style="color:orange">possible issue</span>):</b>
							<?php
							echo $statsArr['unprocnoimg'];
							if($statsArr['unprocnoimg']){
								$eUrl2 = $eUrl.$urlBase.'&q_processingstatus=unprocessed&q_withoutimg=1';
								$beUrl2 = $beUrl.$urlBase.'&q_processingstatus=unprocessed&q_withoutimg=1';
								echo '<span style="margin-left:10px;"><a href="' . $eUrl2 . '" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
								echo '<span style="margin-left:10px;"><a href="' . $beUrl2 . '" target="_blank" title="Batch Edit Records"><img src="../../images/list.png" style="width:12px;" /></a></span>';
								echo '<span style="margin-left:10px;"><a href="processor.php?submitaction=unprocnoimg&tabindex=' . $tabIndex . '&collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" title="Download Report File"><img src="../../images/dl.png" style="width:13px;" /></a></span>';
							}
							?>
						</div>
						<?php
					}
					if($statsArr['unprocwithdata']){
						?>
						<div style="margin:5px;">
							<b>Unprocessed records with Locality details (<span style="color:orange">possible issue</span>):</b>
							<?php
							echo $statsArr['unprocwithdata'];
							if($statsArr['unprocwithdata']){
								$eUrl3b = $eUrl.$urlBase.'&q_processingstatus=unprocessed&q_customfield1=locality&q_customtype1=NOT_NULL&q_customfield2=stateProvince&q_customtype2=NOT_NULL';
								$beUrl3b = $beUrl.$urlBase.'&q_processingstatus=unprocessed&q_customfield1=locality&q_customtype1=NOT_NULL&q_customfield2=stateProvince&q_customtype2=NOT_NULL';
								echo '<span style="margin-left:10px;"><a href="' . $eUrl3b . '" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
								echo '<span style="margin-left:10px;"><a href="' . $beUrl3b . '" target="_blank" title="Batch Edit Records"><img src="../../images/list.png" style="width:12px;" /></a></span>';
								echo '<span style="margin-left:10px;"><a href="processor.php?submitaction=unprocwithdata&tabindex=' . $tabIndex .'&collid=' . $collid . '" target="_blank" title="Download Report File"><img src="../../images/dl.png" style="width:14px;" /></a></span>';
							}
							?>
						</div>
						<?php
					}
					?>
					<div style="margin:20px 5px;">
						<table class="styledtable" style="width:400px;">
							<tr><th>Processing Status</th><th>Count</th></tr>
							<?php
							foreach($statsArr['ps'] as $ps => $cnt){
								if(!$ps) $ps = 'No Status Set';
								echo '<tr>';
								echo '<td>'.$ps.'</td>';
								echo '<td>';
								echo $cnt;
								if($cnt){
									$eUrl4 = $eUrl.$urlBase.'&q_processingstatus='.$ps;
									$beUrl4 = $beUrl.$urlBase.'&q_processingstatus='.$ps;
									echo '<span style="margin-left:10px;"><a href="' . $eUrl4 . '" target="_blank" title="Edit Records"><img src="../../images/edit.png" style="width:12px;" /></a></span>';
									echo '<span style="margin-left:10px;"><a href="' . $beUrl4 . '" target="_blank" title="Batch Edit Records"><img src="../../images/list.png" style="width:12px;" /></a></span>';
								}
								echo '</td>';
								echo '</tr>';
							}
							?>
						</table>
					</div>
				</div>
				<?php
			}
			elseif($menu == 1){
				$uid = (isset($_GET['uid']) ? filter_var($_GET['uid'], FILTER_SANITIZE_NUMBER_INT) : '');
				$interval= (isset($_GET['interval']) ? $_GET['interval'] : 'day');
				$startDate = (isset($_GET['startdate']) ? $_GET['startdate'] : '');
				$endDate = (isset($_GET['enddate']) ? $_GET['enddate'] : '');
				$processingStatus = (isset($_GET['processingstatus']) ? $_GET['processingstatus'] : 0);
				?>
				<fieldset style="padding:15px;width:600px">
					<legend><b>Filter</b></legend>
					<form name="userStatsFilterForm" method="get" action="index.php">
						<div style="margin:2px">
							Editors:
							<select name="uid">
								<option value="0">Show all users</option>
								<option value="0">-----------------------</option>
								<?php
								$userArr = $procManager->getUserList();
								foreach($userArr as $id => $uname){
									echo '<option value="'.$id.'" '.($uid==$id?'SELECTED':'').'>'.$uname.'</option>';
								}
								?>
							</select>
						</div>
						<div style="margin:2px">
							Interval:
							<select name="interval">
								<option value="hour" <?= ($interval=='hour'?'SELECTED':''); ?>>Hour</option>
								<option value="day" <?= ($interval=='day'?'SELECTED':''); ?>>Day</option>
								<option value="week" <?= ($interval=='week'?'SELECTED':''); ?>>Week</option>
								<option value="month" <?= ($interval=='month'?'SELECTED':''); ?>>Month</option>
							</select>
						</div>
						<div style="margin:2px">
							Date: <input name="startdate" type="date" value="<?= htmlspecialchars($startDate, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>" />
							to <input name="enddate" type="date" value="<?= htmlspecialchars($endDate, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>" />
						</div>
						<div style="margin:2px">
							Processing Status set to:
							<select name="processingstatus">
								<option value="0">Ignore Processing Status</option>
								<option value="all" <?php echo ($processingStatus && $processingStatus=='all' ? 'SELECTED' : ''); ?>>Any Processing Status</option>
								<option value="0">-----------------------</option>
								<?php
								$psArr = array('Unprocessed','Stage 1','Stage 2','Stage 3','Pending Duplicate','Pending Review-NfN','Pending Review','Expert Required','Reviewed','Closed');
								foreach($psArr as $psValue){
									$psValue = strtolower($psValue);
									echo '<option value="'.$psValue.'" '.($processingStatus && $processingStatus==$psValue ? 'SELECTED' : '').'>'.$psValue.'</option>';
								}
								?>
							</select>
						</div>
						<div style="float:right;margin-top:25px;">
							<?php
							$editReviewUrl = '../editor/editreviewer.php?collid='.$collid.'&editor='.$uid.'&startdate='.htmlspecialchars($startDate, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE).'&enddate='.htmlspecialchars($endDate, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
							echo '<a href="' . $editReviewUrl . '" target="_blank">Visit Edit Reviewer</a>';
							?>
						</div>
						<div style="margin-top:15px">
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="menu" type="hidden" value="1" />
							<input name="tabindex" type="hidden" value="<?php echo $tabIndex; ?>" />
							<button name="formaction" type="submit" value="displayReport">Display Report</button>
						</div>
					</form>
				</fieldset>
				<?php
				if($formAction && $formAction == 'displayReport'){
					echo '<table class="styledtable" style="width:500px;font-size:12px;">';
					echo '<tr><th>Time Period</th>';
					echo '<th>User</th>';
					if($processingStatus) echo '<th>Previous Status</th><th>Saved Status</th><th>Current Status</th>';
					echo '<th>Edit Count</th>';
					if($procManager->hasEditType()) echo '<th>Excluding Batch Edits</th>';
					echo '</tr>';
					$repArr = $procManager->getFullStatReport($_GET);
					if($repArr){
						//$editReviewUrl = '../editor/editreviewer.php?collid='.$collid.'&editor='.$uid.'&startdate='.htmlspecialchars($startDate, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE).'&enddate='.htmlspecialchars($endDate, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
						foreach($repArr as $t => $arr2){
							foreach($arr2 as $u => $arr3){
								echo '<tr><td>'.$t.'</td>';
								echo '<td>'.$u.'</td>';
								if(array_key_exists('cs', $arr3)){
									echo '<td>'.$arr3['os'].'</td>';
									echo '<td>'.$arr3['ns'].'</td>';
									echo '<td>'.$arr3['cs'].'</td>';
								}
								echo '<td>'.$arr3['cnt'].'</td>';
								if(array_key_exists('cntexcbatch', $arr3)) echo '<td>'.$arr3['cntexcbatch'].'</td>';
								echo '</tr>';
							}
						}
					}
					else{
						echo '<div style="font-weight:bold">No Records Returned</div>';
					}
					echo '</table>';
				}
			}
			?>
		</fieldset>
		<?php
	}
	?>
</div>