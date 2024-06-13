<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceSupport.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/misc/commentlist.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/misc/commentlist.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/misc/commentlist.en.php');header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../collections/misc/commentlist.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$start = array_key_exists('start',$_REQUEST) ? filter_var($_REQUEST['start'], FILTER_SANITIZE_NUMBER_INT) : 0;
$limit = array_key_exists('limit',$_REQUEST) ? filter_var($_REQUEST['limit'], FILTER_SANITIZE_NUMBER_INT) : 100;
$tsStart = array_key_exists('tsstart',$_POST) ? htmlspecialchars($_POST['tsstart'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$tsEnd = array_key_exists('tsend',$_POST) ? htmlspecialchars($_POST['tsend'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) :'';
$uid = array_key_exists('uid',$_POST) ? filter_var($_POST['uid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$rs = array_key_exists('rs',$_POST) ? filter_var($_POST['rs'], FILTER_SANITIZE_NUMBER_INT) : 1;
$showAllGeneralObservations = (array_key_exists('showallgenobs', $_POST) && $_POST['showallgenobs'] === 1) ? true : false;

//Sanition
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($start)) $start = 0;
if(!is_numeric($limit)) $limit = 100;
if(!preg_match('/^[\d-]+$/', $tsStart)) $tsStart = '';
if(!preg_match('/^[\d-]+$/', $tsEnd)) $tsEnd = '';
if(!is_numeric($uid)) $uid = 0;
if(!is_numeric($rs)) $rs = 1;

$commentManager = new OccurrenceSupport();
$commentManager->setCollid($collid);
$collMeta = $commentManager->getCollectionMetadata();

//Set editing rights
$isEditor = 0;
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif($collid){
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = 1;
		}
		elseif($collMeta['colltype'] == 'General Observations'){
			if(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
				$isEditor = 1;
			}
		}
	}
}

$statusStr = '';
$commentArr = null;
if($isEditor){
	$formSubmit = array_key_exists('formsubmit',$_REQUEST) ? $_REQUEST['formsubmit'] : '';
	if($formSubmit){
		$comId = htmlspecialchars($_POST['comid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?? '';
		if($formSubmit == 'Delete Comment'){
			if(!$commentManager->deleteComment($comId)){
				$statusStr = $commentManager->getErrorStr();
			}
		}
		elseif($formSubmit == 'Make Comment Public'){
			if(!$commentManager->setReviewStatus($comId,1)){
				$statusStr = $commentManager->getErrorStr();
			}
		}
		elseif($formSubmit == 'Hide Comment from Public'){
			if(!$commentManager->setReviewStatus($comId,2)){
				$statusStr = $commentManager->getErrorStr();
			}
		}
		elseif($formSubmit == 'Mark as Reviewed'){
			if(!$commentManager->setReviewStatus($comId,3)){
				$statusStr = $commentManager->getErrorStr();
			}
		}
		elseif($formSubmit == 'Mark as Unreviewed'){
			if(!$commentManager->setReviewStatus($comId,1)){
				$statusStr = $commentManager->getErrorStr();
			}
		}
	}
	$commentArr = $commentManager->getComments($start, $limit, $tsStart, $tsEnd, $uid, $rs, $showAllGeneralObservations);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $DEFAULT_TITLE.' '.$LANG['COMMENTS_LISTING']; ?></title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<style>
			.fieldset-like--small-float-right {
				float: right;
				width: 35rem;
			}
		</style>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/index.php"><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
			<?php
			if($collMeta['colltype'] == 'General Observations'){
				echo '<a href="../../profile/viewprofile.php?tabindex=1">' . htmlspecialchars($LANG['COL_MANAGE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> &gt;&gt;';
			}
			else{
				echo '<a href="../misc/collprofiles.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">Collection Management</a> &gt;&gt;';
			}
			?>
			<b><?php echo $LANG['OCC_COMMENTS_LISTING']; ?></b>
		</div>
		<?php
		if($statusStr){
			echo '<div style="margin:20px;color:red;">';
			echo $statusStr;
			echo '</div>';
		}
		?>
		<!-- This is inner text! -->
		<div role="main" id="innertext">
			<div class="justify-center">
				<h1 class="page-heading">Occurrence Comments For: <?php echo $collMeta['name']; ?></h1>
				<section class="fieldset-like fieldset-like--small-float-right">
						<h2>
							<span><?php echo $LANG['FILTER_OPT'];?></span>
						</h2>
						<form name="optionform" action="commentlist.php" method="post">
							<div>
								<label for="commenter"> <?php echo (isset($LANG['COMMENTER']) ? $LANG['COMMENTER'] : 'Commenter'); ?>:</label>
								<select id="commenter" name="uid">
									<option value="0"><?php echo $LANG['ALL_COMMENTERS']; ?></option>
									<option value="0">------------------------</option>
									<?php
									$userArr = $commentManager->getCommentUsers($showAllGeneralObservations);
									foreach($userArr as $userid => $userStr){
										echo '<option value="'.$userid.'" '.($uid==$userid?'selected':'').'>'.$userStr.'</option>';
									}
									?>
								</select>
							</div>
							<?php
							if($IS_ADMIN && $collMeta['colltype'] == 'General Observations'){
								echo '<div><input name="showallgenobs" type="checkbox" value="1" onchange="this.form.submit()" '.($showAllGeneralObservations?'checked':'').' /> '.$LANG['DISP_ALL_GEN_OBS'].'</div>';
							}
							?>
							<div>
								<label for="tsstart"><?php echo $LANG['DATE']; ?>: </label>
								<input id="tsstart" name="tsstart" type="date" value="<?php echo $tsStart; ?>" title="<?php echo (isset($LANG['START_DATE']) ? $LANG['START_DATE'] : 'Start Date'); ?>" aria-label="<?php echo (isset($LANG['START_DATE']) ? $LANG['START_DATE'] : 'Start Date'); ?>"/>
								- <input name="tsend" type="date" value="<?php echo $tsEnd; ?>" title="<?php echo (isset($LANG['END_DATE']) ? $LANG['END_DATE'] : 'End Date'); ?>" aria-label="<?php echo (isset($LANG['END_DATE']) ? $LANG['END_DATE'] : 'End Date'); ?>" />
							</div>
							<fieldset>
								<legend> <?php echo (isset($LANG['COMMENT_TYPE']) ? $LANG['COMMENT_TYPE'] : 'Comment Type'); ?> </legend>
								<input id="public" name="rs" type="radio" value="1" <?php echo ($rs==1?'checked':''); ?> /> <label for="public"> <?php echo $LANG['PUBLIC']; ?> <br/> </label>
								<input id="nonpublic" name="rs" type="radio" value="2" <?php echo ($rs==2?'checked':''); ?> /> <label for="nonpublic"> <?php echo $LANG['NON-PUBLIC']; ?> <br/> </label>
								<input id="reviewed" name="rs" type="radio" value="3" <?php echo ($rs==3?'checked':''); ?> /> <label for="reviewed"> <?php echo $LANG['REVIEWED']; ?> <br/> </label>
								<input id="all" name="rs" type="radio" value="0" <?php echo (!$rs?'checked':''); ?> /> <label for="all"> <?php echo $LANG['ALL']; ?> </label>
							</fieldset>
							<div class="top-breathing-room-rel" >
								<button type="submit" name="submitbutton" value="Refresh List"><?php echo $LANG['REFRESH_LIST']; ?></button>
							</div>
							<div>
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							</div>
						</form>
					</section>
			</div>
			<?php
			if($collid){
				$pageBar = '';
				if($commentArr){
					$recCnt = 0;
					if(isset($commentArr['cnt'])){
						$recCnt = $commentArr['cnt'];
						unset($commentArr['cnt']);
					}
					$urlVars = 'collid='.$collid.'&limit='.$limit.'&tsstart='.$tsStart.'&tsend='.$tsEnd.'&uid='.$uid.'&rs='.$rs;
					try {
						$currentPage = ($limit != 0) ? ($start / $limit) + 1 : 1;
					} catch (Exception $e) {
						$currentPage = 1;
					}
					try {
						$currentPage = ($limit != 0) ? ($start / $limit) + 1 : 1;
					} catch (Exception $e) {
						$lastPage = 1;
					}
					$startPage = $currentPage > 4 ? $currentPage - 4 : 1;
					$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
					$hrefPrefix = 'commentlist.php?' . $urlVars . "&start=";
					$pageBar .= "<span style='margin:5px;'>\n";
					if($endPage > 1){
					    $pageBar .= "<span style='margin-right:5px;'><a href='" . htmlspecialchars($hrefPrefix, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "0'>" . htmlspecialchars($LANG['FIRST_PAGE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a> &lt;&lt;</span>";
						for($x = $startPage; $x <= $endPage; $x++){
						    if($currentPage != $x){
						        $pageBar .= "<span style='margin-right:3px;margin-right:3px;'><a href='" . htmlspecialchars($hrefPrefix, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . htmlspecialchars((($x-1)*$limit), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'>" . htmlspecialchars($x, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a></span>";
						    }
						    else{
						        $pageBar .= "<span style='margin-right:3px;margin-right:3px;font-weight:bold;'>".$x."</span>";
						    }
						}
					}
					if($lastPage > $endPage){
					    $pageBar .= "<span style='margin-left:5px;'>&gt;&gt; <a href='" . htmlspecialchars($hrefPrefix, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . htmlspecialchars((($lastPage-1)*$limit), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'>Last Page</a></span>";
					}
					$pageBar .= "</span>";
					$endNum = $start + $limit;
					if($endNum > $recCnt) $endNum = $recCnt;
					$cntBar = ($start+1)."-".$endNum.' '.$LANG['OF'].' '.$recCnt.' '.$LANG['COMMENTS'];
					echo "<div><hr/></div>\n";
					echo '<div style="float:right;"><b>'.$pageBar.'</b></div>';
					echo '<div><b>'.$cntBar.'</b></div>';
					echo "<div style='clear:both;'><hr/></div>";
				}
				?>
				<!-- Option box -->
				<?php
				if($commentArr){
					foreach($commentArr as $comid => $cArr){
						echo '<div style="margin:15px;">';
						echo '<div style="margin-bottom:10px;"><a href="../individual/index.php?occid=' . htmlspecialchars($cArr['occid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" rel="noopener"><b>' . strip_tags($cArr['occurstr']) . '</b></a></div>';
						echo '<div>';
						echo '<b>'.$userArr[$cArr['uid']].'</b> <span style="color:gray;">'.$LANG['POSTED_ON'].' '.$cArr['ts'].'</span>';
						if($cArr['rs'] == 2 || $cArr['rs'] === '0'){
							echo '<span style="margin-left:20px;"><b>'.$LANG['STATUS'].':</b> </span><span style="color:red;" title="'.$LANG['VIEW_BY_ADMIN'].')">'.$LANG['NOT_PUBLIC'].'</span>';
						}
						elseif($cArr['rs'] == 3){
							echo '<span style="margin-left:20px;"><b>Status:</b> </span><span style="color:orange;">'.$LANG['REVIEWED'].'</span>';
						}
						echo '</div>';
						echo '<div style="margin:10px;">'.$cArr['str'].'</div>';
						?>
						<div style="margin:20px;">
							<form name="commentactionform" action="commentlist.php" method="post">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input name="start" type="hidden" value="<?php echo $start; ?>" />
								<input name="limit" type="hidden" value="<?php echo $limit; ?>" />
								<input name="tsstart" type="hidden" value="<?php echo $tsStart; ?>" />
								<input name="tsend" type="hidden" value="<?php echo $tsEnd; ?>" />
								<input name="uid" type="hidden" value="<?php echo $uid; ?>" />
								<input name="rs" type="hidden" value="<?php echo $rs; ?>" />
								<?php
								if($cArr['rs'] == 2){
									echo '<button name="formsubmit" type="submit" value="Make Comment Public" >'.$LANG['MAKE_PUBLIC'].'</button>';
								}
								else{
									echo '<button name="formsubmit" type="submit" value="Hide Comment from Public" >'.$LANG['HIDE_PUBLIC'].'</button>';
								}
								if($cArr['rs'] == 3){
									?>
									<span style="margin-left:20px;">
										<button name="formsubmit" type="submit" value="Mark as Unreviewed" ><?php echo $LANG['MARK_UNREVIEWED']; ?></button>
									</span>
									<?php
								}
								else{
									?>
									<span style="margin-left:20px;">
										<button name="formsubmit" type="submit" value="Mark as Reviewed" ><?php echo $LANG['MARK_REVIEWED']; ?></button>
									</span>
									<?php
								}
								?>
								<span style="margin-left:20px;">
									<button class="button-danger" name="formsubmit" type="submit" value="Delete Comment"  onclick="return confirm('<?php echo $LANG['SURE_DELETE_COMMENT']; ?>')" ><?php echo $LANG['DEL_COMMENT']; ?></button>
								</span>
								<input name="comid" type="hidden" value="<?php echo $comid; ?>" />
							</form>
						</div>
						<?php
						echo '</div>';
						echo '<hr style="color:gray;"/>';
					}
					echo '<div style="float:right;">'.$pageBar.'</div>';
					echo "<div style='clear:both;'><hr/></div></div>";
				}
				else{
					echo '<div style="font-weight:bold;font-size:120%;margin:20px;">';
					echo $LANG['NO_COMMENTS_MATCHING'].'. <br/>';
					if($rs == 1) echo $LANG['ONLY_PUBLIC_NONREVIEWED'];
					echo '</div>';
				}
			}
			else{
				echo '<div>'.$LANG['COLLID_NULL'].'</div>';
			}
			?>
		</div>
		<?php
			include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>
