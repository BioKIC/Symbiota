<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCrowdSource.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/specprocessor/crowdsource/review.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/crowdsource/review.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/crowdsource/review.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../../profile/index.php?refurl=../collections/specprocessor/index.php?tabindex=1?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$uid = array_key_exists('uid',$_REQUEST)?$_REQUEST['uid']:0;
$rStatus = array_key_exists('rstatus',$_REQUEST)?$_REQUEST['rstatus']:'5,10';
$start = array_key_exists('start',$_REQUEST)?$_REQUEST['start']:0;
$limit = array_key_exists('limit',$_REQUEST)?$_REQUEST['limit']:500;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

$csManager = new OccurrenceCrowdSource();
//If collid is null, it will be assumed that current user wants to review their own specimens (they can still edit pending, closed specimen can't be editted)
$csManager->setCollid($collid);

$isEditor = 0;
if(array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin'])){
	$isEditor = 1;
}

$statusStr = '';
if($isEditor && $action){
	if($action == 'submitReviews'){
		$statusStr = $csManager->submitReviews($_POST);
	}
	elseif($action == 'resetToNotReviewed'){
		$statusStr = $csManager->resetReviewStatus($_POST,5);
	}
	elseif($action == 'resetToOpen'){
		$statusStr = $csManager->resetReviewStatus($_POST,0);
	}
}

$projArr = $csManager->getProjectDetails();
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['CROWDSOURCING_REVIEW']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="../../../js/jquery.js" type="text/javascript"></script>
	<script" src="../../../js/jquery-ui.js" type="text/javascript"></script>
	<script type="text/javascript">
		function selectAll(cbObj){
			var cbStatus = cbObj.checked;
			var f = cbObj.form;
			for(var i = 0; i < f.length; i++) {
				if(f.elements[i].name == "occid[]") f.elements[i].checked = cbStatus;
			}
		}

		function selectCheckbox(occid){
			document.getElementById("o-"+occid).checked = true;
		}

		function expandNotes(textObj){
			textObj.style.width = "300px";
		}

		function collapseNotes(textObj){
			textObj.style.width = "60px";
		}

		function validateReviewForm(f){
			for(var i = 0; i < f.length; i++) {
				if(f.elements[i].name == "occid[]" && f.elements[i].checked) return true;
			}
			alert("No records have been selected");
			return false;
		}

		function showAdditionalActions(){
			$('#addActionsDiv').show();
			$('#showAddDiv').hide();
		}
	</script>
</head>
<body style="margin-left: 0px; margin-right: 0px;background-color:white;">
	<div class='navpath'>
		<a href="../../../index.php"><?php echo $LANG['HOME']; ?></a> &gt;&gt;
		<a href="index.php"><?php echo $LANG['SCORE_BOARD']; ?></a> &gt;&gt;
		<?php
		if($collid) echo '<a href="../index.php?tabindex=1&collid='.$collid.'">'.$LANG['CONTROL_PANEL'].'</a> &gt;&gt;';
		?>
		<b><?php echo $LANG['CROWDSOURCING_REVIEW']; ?></b>
	</div>
	<div style="margin:10px;">
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:20px;color:<?php echo (substr($statusStr,0,5)=='ERROR'?'red':'green');?>">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php
		}
		if($recArr = $csManager->getReviewArr($start,$limit,$uid,$rStatus)){
			$totalCnt = $recArr['totalcnt'];
			unset($recArr['totalcnt']);
			//Set up navigation string
			$pageCnt = count($recArr);
			//echo json_encode($recArr);
			$end = ($start + $pageCnt);
			$urlPrefix = 'review.php?collid='.$collid.'&uid='.$uid.'&rstatus='.$rStatus;
			$navStr = '<b>';
			if($start > 0) $navStr .= '<a href="'.$urlPrefix.'&start=0&limit='.$limit.'">';
			$navStr .= '|&lt; ';
			if($start > 0) $navStr .= '</a>';
			$navStr .= '&nbsp;&nbsp;&nbsp;';
			if($start > 0) $navStr .= '<a href="'.$urlPrefix.'&start='.($start-$limit).'&limit='.$limit.'">';
			$navStr .= '&lt;&lt;';
			if($start > 0) $navStr .= '</a>';
			$navStr .= '&nbsp;&nbsp;|&nbsp;&nbsp;'.($start + 1).' - '.($end).' of '.number_format($totalCnt).'&nbsp;&nbsp;|&nbsp;&nbsp;';
			if($totalCnt > ($start+$limit)) $navStr .= '<a href="'.$urlPrefix.'&start='.($start+$limit).'&limit='.$limit.'">';
			$navStr .= '&gt;&gt;';
			if($totalCnt > ($start+$limit)) $navStr .= '</a>';
			$navStr .= '&nbsp;&nbsp;&nbsp;';
			if(($start+$pageCnt) < $totalCnt) $navStr .= '<a href="'.$urlPrefix.'&start='.(floor($totalCnt/$limit)*$limit).'&limit='.$limit.'">';
			$navStr .= '&gt;|';
			if(($start+$pageCnt) < $totalCnt) $navStr .= '</a> ';
			$navStr .= '</b>';
			?>
			<div style="width:850px;">
				<div style="float:right;">
					<form name="filter" action="review.php" method="get">
						<fieldset style="width:300px;text-align:left;">
							<legend><b>Filter</b></legend>
							<div style="margin:3px;">
								<b><?php echo $LANG['REVIEW_STATUS']; ?>:</b>
								<select name="rstatus" onchange="this.form.submit()">
									<option value="5,10"><?php echo $LANG['ALL_RECORDS']; ?></option>
									<option value="5,10">----------------------</option>
									<option value="5" <?php echo ($rStatus=='5'?'SELECTED':''); ?>><?php echo $LANG['NOT_REVIEWED']; ?></option>
									<option value="10" <?php echo ($rStatus=='10'?'SELECTED':''); ?>><?php echo $LANG['REVIEWED_APPROVED']; ?></option>
								</select>
							</div>
							<?php
							if($collid){
								?>
								<div style="margin:3px;">
									<b><?php echo $LANG['EDITOR']; ?>:</b>
									<select name="uid" onchange="this.form.submit()">
										<option value=""><?php echo $LANG['ALL_EDITORS']; ?></option>
										<option value="">----------------------</option>
										<?php
										$editorArr = $csManager->getEditorList();
										foreach($editorArr as $eUid => $eName){
											echo '<option value="'.$eUid.'" '.($eUid==$uid?'SELECTED':'').'>'.$eName.'</option>'."\n";
										}
										?>
									</select>
								</div>
								<?php
							}
							else{
								echo '<input name="uid" type="hidden" value="'.$uid.'" />';
							}
							?>
							<div style="margin:3px;">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<!-- <input name="action" type="submit" value="Filter Records" /> -->
							</div>
						</fieldset>
					</form>
				</div>
				<div style="font-weight:bold;font-size:130%;">
					<?php echo ($collid?$projArr['name']:$USER_DISPLAY_NAME); ?>
				</div>
				<div style="clear:both;">
					<?php echo $navStr; ?>
				</div>
			</div>
			<div style="clear:both;">
				<?php
				if($totalCnt){
					?>
					<div style="clear:both;">
						<form name="reviewform" method="post" action="review.php" onsubmit="return validateReviewForm(this)">
							<?php
							if($collid){
								echo '<input name="collid" type="hidden" value="'.$collid.'" />';
								echo '<input name="rstatus" type="hidden" value="'.$rStatus.'" />';
								echo '<input name="uid" type="hidden" value="'.$uid.'" />';
							}
							?>
							<table class="styledtable" style="font-family:Arial;font-size:12px;">
								<tr>
									<?php
									if($collid) echo '<th><span title="'.$LANG['SELECT_ALL'].'"><input name="selectall" type="checkbox" onclick="selectAll(this)" /></span></th>';
									?>
									<th><?php echo $LANG['POINTS']; ?></th>
									<th><?php echo $LANG['COMMENTS']; ?></th>
									<th><?php echo $LANG['EDIT']; ?></th>
									<?php
									//Display table header
									$header = $csManager->getHeaderArr();
									foreach($header as $v){
										echo '<th>'.$v.'</th>';
									}
									?>
								</tr>
								<?php
								$cnt = 0;
								//echo json_encode($recArr);
								foreach($recArr as $occid => $rArr){
									?>
									<tr <?php echo ($cnt%2?'class="alt"':'') ?>>
										<?php
										$notes = '';
										if(isset($rArr['notes'])) $notes = $rArr['notes'];
										$points = 2;
										if(isset($rArr['points'])) $points = $rArr['points'];
										if($collid){
											echo '<td><input id="o-'.$occid.'" name="occid[]" type="checkbox" value="'.$occid.'" /></td>';
											if(isset($rArr['points'])){
												echo '<td><input name="p-'.$occid.'" type="text" value="'.$points.'" style="width:40px;" DISABLED /></td>';
												echo '<td><b>'.$LANG['REVIEWED_APPROVED'].'</b></td>';
											}
											else{
												echo '<td><select name="p-'.$occid.'" style="width:45px;" onchange="selectCheckbox('.$occid.')">';
												echo '<option value="0" '.($points=='0'?'SELECTED':'').'>0</option>';
												echo '<option value="1" '.($points=='1'?'SELECTED':'').'>1</option>';
												echo '<option value="2" '.($points=='2'?'SELECTED':'').'>2</option>';
												echo '<option value="3" '.($points=='3'?'SELECTED':'').'>3</option>';
												echo '</select></td>';
												echo '<td><input name="c-'.$occid.'" type="text" value="'.$notes.'" style="width:60px;" onfocus="expandNotes(this)" onblur="collapseNotes(this)" onchange="selectCheckbox('.$occid.')" /></td>';
											}
										}
										else{
											echo '<td><input name="p-'.$occid.'" type="text" value="'.$points.'" style="width:15px;" DISABLED /></td>';
											echo '<td>'.$notes.'</td>';
										}
										?>
										<td>
											<?php
											if($isEditor || $rArr['reviewstatus'] == 5){
												echo '<a href="../../editor/occurrenceeditor.php?csmode=1&occid='.$occid.'" target="_blank">';
												echo '<img src="../../../images/edit.png" style="border:solid 1px gray;height:13px;" />';
												echo '</a>';
											}
											else{
												echo '<img src="../../../images/cross-out.png" style="border:solid 1px gray;height:13px;" />';
											}
											?>
										</td>
										<?php
										foreach($header as $v){
											$displayStr = $rArr[$v];
											if(strlen($displayStr) > 40){
												$displayStr = substr($displayStr,0,40).'...';
											}
											echo '<td>'.$displayStr.'</td>'."\n";
										}
										?>
									</tr>
									<?php
									$cnt++;
								}
								?>
							</table>
							<div style="width:850px;">
								<div>
									<?php echo $navStr; ?>
								</div>
								<div style="clear:both;">
									<?php
									if($collid){
										?>
										<div style="margin:10px;clear:both;">
											<button name="action" type="submit" value="submitReviews" ><?php echo $LANG['SUBMIT_REVIEWS']; ?></button>
											<input name="updateProcessingStatus" type="checkbox" value="1" checked />
											<?php echo $LANG['SET_PROC_TO_REVIEWED']; ?>
										</div>
										<div id="showAddDiv" style="margin:10px"><a href="#" onclick="showAdditionalActions();return false;"><?php echo $LANG['SHOW_ADD_ACTIONS']; ?></a></div>
										<div id="addActionsDiv" style="display:none;margin:20px 10px;">
											<div><button name="action" type="submit" value="resetToNotReviewed" onclick="return confirm('<?php echo $LANG['SURE_CHANGE_STATUS']; ?>')"><?php echo $LANG['REMOVE_POINTS_CHANGE_NR']; ?></button></div>
											<div style="margin-top:5px"><button name="action" type="submit" value="resetToOpen" onclick="return confirm('<?php echo $LANG['SURE_RESET_STATUS']; ?>')"><?php echo $LANG['MOVE_BACK_QUEUE']; ?></button></div>
										</div>
										<?php
									}
									?>
								</div>
							</div>
						</form>
					</div>
					<?php
				}
				else{
					if($collid && $rStatus == 5){
						?>
						<div style="clear:both;margin:30px 15px;font-weight:bold;">
							<div style="font-size:120%;">
								<?php echo $LANG['NO_RECS_THIS_USER']; ?>
							</div>
							<div style="margin:15px;">
								<?php echo $LANG['RETURN_TO']; ?> <a href="../index.php?tabindex=1&collid=<?php echo $collid; ?>"><?php echo $LANG['CONTROL_PANEL']; ?></a>
							</div>
							<div style="margin:15px;">
								<?php echo $LANG['RETURN_TO']; ?> <a href="index.php"><?php echo $LANG['SCORE_BOARD']; ?></a>
							</div>
						</div>
						<?php
					}
					else{
						?>
						<div style="clear:both;font-size:120%;padding-top:30px;font-weight:bold;">
							<?php echo $LANG['NO_RECS']; ?>
						</div>
						<?php
					}
				}
				?>
			</div>
			<?php
		}
		?>
	</div>
</body>
</html>