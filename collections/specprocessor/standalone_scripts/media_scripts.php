<?php
/*
 * Image maintenance scripts:
 *   1) Navigates through submitted image ids (imgid) and removes image records from database and deletes or moves physical image to an archive directory
 *   2) Script that assists in migrating images from a remote server to the portal mount
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/MediaResolutionTools.php');

$collid = (array_key_exists('collid', $_POST)?$_POST['collid']:'');
$imgIdStart = (array_key_exists('imgidstart', $_POST)?$_POST['imgidstart']:0);
$limit = (array_key_exists('limit', $_POST)?$_POST['limit']:10000);
$archiveImages = (array_key_exists('archiveimg', $_POST)?$_POST['archiveimg']:0);
$delThumb = (array_key_exists('delthumb', $_POST)?$_POST['delthumb']:0);
$delWeb = (array_key_exists('delweb', $_POST)?$_POST['delweb']:0);
$delLarge = (array_key_exists('dellarge', $_POST)?$_POST['dellarge']:0);
$imgidStr = (array_key_exists('imgidstr', $_POST)?$_POST['imgidstr']:'');
$transferThumbnail = (array_key_exists('transferThumbnail', $_POST)?$_POST['transferThumbnail']:0);
$transferWeb = (array_key_exists('transferWeb', $_POST)?$_POST['transferWeb']:0);
$transferLarge = (array_key_exists('transferLarge', $_POST)?$_POST['transferLarge']:0);
$matchTermThumbnail = (array_key_exists('matchTermThumbnail', $_POST)?$_POST['matchTermThumbnail']:'');
$matchTermWeb = (array_key_exists('matchTermWeb', $_POST)?$_POST['matchTermWeb']:'');
$matchTermLarge = (array_key_exists('matchTermLarge', $_POST)?$_POST['matchTermLarge']:'');
$deleteSource = (array_key_exists('deleteSource', $_POST)?$_POST['deleteSource']:0);
$imgRootUrl = (array_key_exists('imgRootUrl', $_POST)?$_POST['imgRootUrl']:'');
$imgRootPath = (array_key_exists('imgRootPath', $_POST)?$_POST['imgRootPath']:'');
$imgSubPath = (array_key_exists('imgSubPath', $_POST)?$_POST['imgSubPath']:'');
$submit = (array_key_exists('submitbutton', $_POST)?$_POST['submitbutton']:'');

//Sanitation
if(!is_numeric($collid)) $collid = '';
if(!is_numeric($imgIdStart)) $imgIdStart = 0;
if(!is_numeric($limit)) $limit = 0;
if(!is_numeric($archiveImages)) $archiveImages = 0;
if(!is_numeric($delThumb)) $delThumb = 0;
if(!is_numeric($delWeb)) $delWeb = 0;
if(!is_numeric($delLarge)) $delLarge = 0;
$imgidStr = filter_var($imgidStr,FILTER_SANITIZE_STRING);
if(!is_numeric($transferThumbnail)) $transferThumbnail = 0;
if(!is_numeric($transferWeb)) $transferWeb = 0;
if(!is_numeric($transferLarge)) $transferLarge = 0;
$matchTermThumbnail = filter_var($matchTermThumbnail,FILTER_SANITIZE_STRING);
$matchTermWeb = filter_var($matchTermWeb,FILTER_SANITIZE_STRING);
$matchTermLarge = filter_var($matchTermLarge,FILTER_SANITIZE_STRING);
if(!is_numeric($deleteSource)) $deleteSource = 0;
$imgRootUrl = filter_var($imgRootUrl,FILTER_SANITIZE_STRING);
$imgRootPath = filter_var($imgRootPath,FILTER_SANITIZE_STRING);
$imgSubPath = filter_var($imgSubPath,FILTER_SANITIZE_STRING);

$toolManager = new MediaResolutionTools();
$toolManager->setCollid($collid);

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
?>
<html>
<head>
	<title>Media Tools</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="../../../js/jquery.js" type="text/javascript"></script>
	<script src="../../../js/jquery-ui.js" type="text/javascript"></script>
	<script type="text/javascript">
		function verifyMigrationCode(f){
			if(f.matchTermThumbnail.value == "" && f.matchTermWeb.value == "" && f.matchTermLarge.value == ""){
				alert("You need at least one matching term defined");
				return false;
			}
			if(f.collid.value == ""){
				alert("Select a collection project");
				return false;
			}
			return true;
		}
	</script>
	<style type="text/css">
		fieldset{ padding: 10px; margin-bottom: 15px }
		legend{ font-weight: bold }
		.fieldRowDiv{ clear:both; margin: 2px 0px; }
		.fieldDiv{ float:left; margin: 2px 10px 2px 0px; }
		.fieldLabel{  }
		.fieldDiv button{ margin-top: 10px; }
	</style>
</head>
<body>
	<?php
	if($isEditor){
		?>
		<div id="innertext">
			<div id="actionDiv">
				<?php
				$imgidEnd = 0;
				if($submit){
					if($submit == 'transferImages'){
						?>
						<fieldset>
							<legend>Action Panel</legend>
							<ol>
							<?php
							$toolManager->setVerboseMode(2);
							$toolManager->setTransferThumbnail($transferThumbnail);
							$toolManager->setTransferWeb($transferWeb);
							$toolManager->setTransferLarge($transferLarge);
							$toolManager->setMatchTermThumbnail($matchTermThumbnail);
							$toolManager->setMatchTermWeb($matchTermWeb);
							$toolManager->setMatchTermLarge($matchTermLarge);
							$toolManager->setDeleteSource($deleteSource);
							$toolManager->setImgRootUrl($imgRootUrl);
							$toolManager->setImgRootPath($imgRootPath);
							$toolManager->setImgSubPath($imgSubPath);
							if($collid) $imgIdStart = $toolManager->migrateCollectionDerivatives($imgIdStart, $limit);
							else $imgIdStart = $toolManager->migrateFieldDerivatives($imgIdStart, $limit);
							?>
							</ol>
						</fieldset>
						<?php
					}
					elseif($submit == 'Process Images'){
						if($archiveImages) $toolManager->setArchiveImages($archiveImages);
						$toolManager->setDeleteThumbnail($delThumb);
						$toolManager->setDeleteWebImage($delWeb);
						$toolManager->setDeleteOriginal($delLarge);
						$toolManager->setImgidArr($imgidStr);
						$imgidEnd = $toolManager->archiveImageFiles($imgIdStart, $limit);
					}
					else{
						$delThumb = 1;
						$delWeb = 1;
						$delLarge = 1;
					}
				}
				?>
			</div>
			<fieldset>
				<legend>Image Archival/Removal Tools</legend>
				<div>This tool can be used to stash (i.e. archive) or delete images that are currently stored locally (server must have write access to images)</div>
				<form action="media_scripts.php" method="post">
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel">Collection ID (collid):</span>
							<select name="collid">
								<option value="">Select a Collection</option>
								<option value="">-----------------------------</option>
								<option value="0">Field Images</option>
								<?php
								$collArr = $toolManager->getCollectionMeta();
								foreach($collArr as $id => $collName){
									echo '<option value="'.$id.'" '.($collid==$id?'SELECTED':'').'>'.$collName.'</option>';
								}
								?>
							</select>
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<b>Starting Image ID:</b> <input type="text" name="imgidstart" value="<?php echo $imgidEnd; ?>" /><br />
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<b>Batch limit: </b><input type="text" name="limit" value="<?php echo $limit; ?>" /><br />
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<fieldset>
								<legend>Action</legend>
								<input type="radio" name="archiveimg" value="0" <?php echo ($archiveImages?'':'CHECKED'); ?> /> Delete Images<br />
								<input type="radio" name="archiveimg" value="1" <?php echo ($archiveImages?'CHECKED':''); ?> /> Archive Images<br />
							</fieldset>
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<fieldset>
								<legend>Image Targets</legend>
								<input type="checkbox" name="delthumb" value="1" <?php echo ($delThumb?'CHECKED':''); ?> /> Delete Thumbnail Derivative<br />
								<input type="checkbox" name="delweb" value="1" <?php echo ($delWeb?'CHECKED':''); ?> /> Delete Web Derivative<br />
								<input type="checkbox" name="dellarge" value="1" <?php echo ($delLarge?'CHECKED':''); ?> /> Delete Large Derivative<br />
							</fieldset>
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<b>imgids (enter multiple values delimited by commas)</b><br/>
							<textarea name="imgidstr" rows="8" cols="100"></textarea>
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<button name="submitbutton" type="submit" value="Process Images">Process Images</button>
						</div>
					</div>
				</form>
			</fieldset>
			<fieldset>
				<legend>Image Migration Tools</legend>
				<div>This tool can be used to migrate images located on a remote server to the local server that is currently hosting the portal</div>
				<form action="media_scripts.php" method="post" onsubmit="return verifyMigrationCode(this)">
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel">Collection ID (collid):</span>
							<select name="collid">
								<option value="">Select a Collection</option>
								<option value="">-----------------------------</option>
								<option value="0">Field Images</option>
								<?php
								$collArr = $toolManager->getCollectionMeta();
								foreach($collArr as $id => $collName){
									echo '<option value="'.$id.'" '.($collid==$id?'SELECTED':'').'>'.$collName.'</option>';
								}
								?>
							</select>
						</div>
					</div>
					<div class="fieldRowDiv">
						<fieldset>
							<legend>Transfer Target</legend>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input name="transferThumbnail" type="checkbox" value="1" <?php echo ($transferThumbnail?'CHECKED':''); ?> />
									<span class="fieldLabel">Transfer Thumbnail</span>
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input name="transferWeb" type="checkbox" value="1" <?php echo ($transferWeb?'CHECKED':''); ?> />
									<span class="fieldLabel">Transfer Web View (medium)</span>
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input name="transferLarge" type="checkbox" value="1" <?php echo ($transferLarge?'CHECKED':''); ?> />
									<span class="fieldLabel">Transfer Large Image</span>
								</div>
							</div>
							<div class="fieldRowDiv" style="padding-top:10px">
								<div class="fieldDiv">
									<input name="deleteSource" type="checkbox" value="1" <?php echo ($deleteSource?'CHECKED':''); ?> />
									<span class="fieldLabel">Delete source images</span>
								</div>
							</div>
						</fieldset>
					</div>
					<div class="fieldRowDiv">
						<fieldset>
							<legend>Transfer Source Query Term</legend>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Thumbnail Matching Term (thumbnailUrl):</span>
									<input name="matchTermThumbnail" type="text" value="<?php echo $matchTermThumbnail; ?>" style="width:300px" />
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Web Image (medium) Matching Term (url):</span>
									<input name="matchTermWeb" type="text" value="<?php echo $matchTermWeb; ?>" style="width:300px" />
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Large Image Matching Term (originalurl):</span>
									<input name="matchTermLarge" type="text" value="<?php echo $matchTermLarge; ?>" style="width:300px" />
								</div>
							</div>
						</fieldset>
					</div>
					<div class="fieldRowDiv">
						<fieldset>
							<legend>Path Variables</legend>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Image Root URL (imgRootUrl):</span>
									<input name="imgRootUrl" type="text" value="<?php echo ($imgRootUrl?$imgRootUrl:$IMAGE_ROOT_URL); ?>" style="width:400px" />
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Image Root Path (imgRootPath):</span>
									<input name="imgRootPath" type="text" value="<?php echo ($imgRootPath?$imgRootPath:$IMAGE_ROOT_PATH); ?>" style="width:400px" />
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Target Sub-Path:</span>
									<input name="imgSubPath" type="text" value="<?php echo $imgSubPath; ?>" style="width:400px" />
								</div>
							</div>
						</fieldset>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel">imgId start:</span>
							<input type="text" name="imgidstart" value="<?php echo $imgIdStart; ?>" />
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel">Batch limit:</span>
							<input type="text" name="limit" value="<?php echo $limit; ?>" />
						</div>
					</div>
					<div class="fieldRowDiv">
						<button name="submitbutton" type="submit" value="transferImages">Transfer Images</button>
					</div>
				</form>
			</fieldset>
		</div>
		<?php
	}
	else echo '<div>Permissions issue; are you logged in?</div>';
	?>
</body>
