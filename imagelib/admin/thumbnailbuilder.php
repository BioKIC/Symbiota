<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageCleaner.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/imagelib/admin/thumbnailbuilder.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/imagelib/admin/thumbnailbuilder.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/imagelib/admin/thumbnailbuilder.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../imagelib/admin/thumbnailbuilder.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : '';
$tid = array_key_exists('tid', $_REQUEST) ? filter_var($_REQUEST['tid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$buildMediumDerivatives = array_key_exists('buildmed', $_POST) ? filter_var($_POST['buildmed'], FILTER_SANITIZE_NUMBER_INT) : 0;
$evaluateOrientation = array_key_exists('evalorientation', $_POST) ? filter_var($_POST['evalorientation'], FILTER_SANITIZE_NUMBER_INT) : 0;
$limit = array_key_exists('limit', $_POST) ? filter_var($_POST['limit'], FILTER_SANITIZE_NUMBER_INT) : '';
$action = array_key_exists('action', $_REQUEST) ? filter_var($_REQUEST['action'], FILTER_SANITIZE_STRING) : '';

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif($collid){
	if(array_key_exists('CollAdmin', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollAdmin'])){
		$isEditor = true;
	}
}

$imgManager = new ImageCleaner();
$imgManager->setCollid($collid);
$imgManager->setTid($tid);
$imgManager->setBuildMediumDerivative($buildMediumDerivatives);
$imgManager->setTestOrientation($evaluateOrientation);

//Set default actions
if(!$buildMediumDerivatives && $imgManager->getManagementType() == 'Live Data') $buildMediumDerivatives = true;
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['THUMB_BUILDER']; ?></title>
	<?php

	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript">
		function resetRebuildForm(f){
			f.catNumLow.value = "";
			f.catNumHigh.value = "";
			f.catNumList.value = "";
		}
	</script>
	<style type="text/css">
		fieldset{ padding: 10px }
		fieldset legend{ font-weight: bold }
		.fieldRowDiv{ clear:both; margin: 2px 0px; }
		.fieldRowDiv button{ margin-top: 10px; }
		.fieldDiv{ float:left; margin: 2px 10px 2px 0px; }
		.fieldLabel{ }
		hr{ margin: 10px 0px; }

	</style>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php"><?php echo $LANG['HOME']; ?></a> &gt;&gt;
		<?php
		if($collid) echo '<a href="../../collections/misc/collprofiles.php?collid='.$collid.'&emode=1">'.$LANG['COL_MAN_MENU'].'</a> &gt;&gt;';
		else echo '<a href="../../sitemap.php">'.$LANG['SITEMAP'].'</a> &gt;&gt;';
		?>
		<b>Thumbnail Builder</b>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($isEditor){
			echo '<h2>'.$LANG['THUMB_MAINT_TOOL'];
			if($collid) echo ' - '.$imgManager->getCollectionName();
			elseif($collid === '0') echo ' - '.$LANG['FIELD_IMAGES'];
			echo '</h2>';
			if($action && $action != 'none'){
				if($action == 'resetprocessing'){
					$imgManager->resetProcessing();
				}
				else{
					?>
					<fieldset style="margin:10px;padding:15px">
						<legend><b><?php echo $LANG['PROCESSING_PANEL']; ?></b></legend>
						<div style="font-weight:bold;"><?php echo $LANG['START_PROCESSING']; ?>...</div>
						<?php
						if($action == 'buildThumbnails') $imgManager->buildThumbnailImages($limit);
						elseif($action == 'Refresh Thumbnails'){
							echo '<div style="margin-bottom:10px;">' . $LANG['NUM_IMGS_REFRESHED'] . ': ' . $imgManager->getProcessingCnt($_POST) . '</div>';
							$imgManager->refreshThumbnails($_POST);
						}
						?>
						<div style="margin-top:10px;font-weight:bold;"><?php echo $LANG['FINISHED']; ?></div>
					</fieldset>
					<?php
				}
			}
			?>
			<fieldset style="margin:30px 10px;padding:15px;">
				<legend><b><?php echo $LANG['THUMB_BUILDER']; ?></b></legend>
				<div>
					<?php
					$reportArr = $imgManager->getReportArr();
					if($reportArr){
						echo '<b>'.$LANG['IMG_COUNT_EXPLAIN'].'</b> - '.$LANG['THUMB_IMG_EXPLAIN'];
						if($tid) echo '<div style="margin:5px 25px">'.$LANG['TAX_FILTER'].': '.$imgManager->getSciname().' (tid: '.$tid.')</div>';
						echo '<ul>';
						foreach($reportArr as $id => $retArr){
							echo '<li>';
							echo '<a href="thumbnailbuilder.php?collid='.$id.'&tid='.$tid.'&action=none">';
							echo $retArr['name'];
							echo '</a>';
							echo ': '.$retArr['cnt'].' images';
							echo '</li>';
						}
						echo '</ul>';
					}
					else{
						echo '<div>'.$LANG['ALL_THUMBS_DONE'].'</div>';
					}
					?>
				</div>
				<div style="margin:25px;">
					<?php
					if($reportArr){
						?>
						<form name="tnbuilderform" action="thumbnailbuilder.php" method="post">
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input name="buildmed" type="checkbox" value="1" <?php echo ($buildMediumDerivatives?'checked':''); ?> />
									<span class="fieldLabel"> <?php echo $LANG['INCLUDE_MED']; ?></span>
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input name="evalorientation" type="checkbox" value="1" <?php echo ($evaluateOrientation?'checked':''); ?> />
									<span class="fieldLabel"> <?php echo $LANG['ROTATE_IMGS']; ?></span>
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<?php echo $LANG['PROCESSING_LIMIT']; ?>:
									<input name="limit" type="number" value="<?php echo $limit; ?>" />
								</div>
							</div>
							<div class="fieldRowDiv">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>">
								<input name="tid" type="hidden" value="<?php echo $tid; ?>">
								<button name="action" type="submit" value="buildThumbnails"><?php echo $LANG['BUILD_THUMBS']; ?></button>
							</div>
						</form>
						<?php
						if($collid && $action == 'buildThumbnails' && $reportArr[$collid]['cnt']){
							//Thumbnails have been processed but there are still some that missed processing
							?>
							<hr>
							<div><?php echo $LANG['NOT_PROCESSING_ERROR']; ?> </div>
							<div class="fieldRowDiv">
								<form name="resetform" action="thumbnailbuilder.php" method="post">
									<input name="collid" type="hidden" value="<?php echo $collid; ?>">
									<input name="tid" type="hidden" value="<?php echo $tid; ?>">
									<button name="action" type="submit" value="resetprocessing"><?php echo $LANG['RESET_PROCESSING']; ?></button>
								</form>
							</div>
							<?php
						}
					}
					?>
				</div>
			</fieldset>
			<?php
			if($collid){
				if($remoteImgCnt = $imgManager->getRemoteImageCnt()){
					?>
					<fieldset style="margin:30px 10px;padding:15px">
						<legend><b><?php echo $LANG['THUMB_REMAPPER']; ?></b></legend>
						<form name="tnrebuildform" action="thumbnailbuilder.php" method="post">
							<div style="margin-bottom:20px;">
								<?php echo $LANG['THUMB_REMAP_EXPLAIN']; ?>
							</div>
							<div style="margin-bottom:10px;">
								<?php echo $LANG['IMAGES_AVAIL_REFRESH'].': '.$remoteImgCnt; ?>
							</div>
							<div style="margin-bottom:10px;">
								<?php echo $LANG['CATNUM_RANGE']; ?>: <input name="catNumLow" type="text" value="<?php echo (isset($_POST['catNumLow']) ? filter_var($_POST['catNumLow'], FILTER_SANITIZE_STRING) : ''); ?>" /> -
								<input name="catNumHigh" type="text" value="<?php echo (isset($_POST['catNumHigh']) ? filter_var($_POST['catNumHigh'], FILTER_SANITIZE_STRING) : ''); ?>" />
							</div>
							<div style="margin-bottom:10px;vertical-align:top;height:90px">
								<div style="float:left"><?php echo $LANG['CATNUM_LIST']; ?>: </div>
								<div style="margin-left:5px;float:left"><textarea name="catNumList" rows="5" cols="50"><?php echo (isset($_POST['catNumList']) ? filter_var($_POST['catNumList'], FILTER_SANITIZE_STRING) : ''); ?></textarea></div>
							</div>
							<div style="margin-bottom:10px;">
								<input name="evaluate_ts" type="radio" value="1" checked /> <?php echo $LANG['ONLY_PROCESS_RECENT']; ?><br/>
								<input name="evaluate_ts" type="radio" value="0" /> <?php echo $LANG['FORCE_REBUILD']; ?>
							</div>
							<div class="fieldRowDiv">
								<input name="buildmed" type="checkbox" value="1" <?php echo ($buildMediumDerivatives?'checked':''); ?> />
								<span class="fieldLabel"> <?php echo $LANG['INCLUDE_MED']; ?></span>
							</div>
							<div style="margin-bottom:10px;">
								<input name="evalorientation" type="checkbox" value="1" <?php echo ($evaluateOrientation?'checked':''); ?> /> <?php echo $LANG['ROTATE_IMGS']; ?>
							</div>
							<div style="margin:20px;clear:both">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<button name="action" type="submit" value="Refresh Thumbnails"><?php echo $LANG['REFRESH_THUMBS']; ?></button>
								<button type="button" onclick="resetRebuildForm(this.form)"><?php echo $LANG['RESET']; ?></button>
							</div>
						</form>
					</fieldset>
					<?php
				}
			}
		}
		else{
			echo '<div><b>'.$LANG['ERROR_PERMISSIONS'].'</b></div>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>