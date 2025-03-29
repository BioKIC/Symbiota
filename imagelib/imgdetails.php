<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/classes/Media.php');
include_once($SERVER_ROOT . '/classes/ImageDetailManager.php');
include_once($SERVER_ROOT . '/classes/utilities/GeneralUtil.php');
if ($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/imagelib/imgdetails.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/imagelib/imgdetails.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/imagelib/imgdetails.en.php');

header('Content-Type: text/html; charset=' . $CHARSET);

$mediaID = array_key_exists('mediaid', $_REQUEST) ? filter_var($_REQUEST['mediaid'], FILTER_SANITIZE_NUMBER_INT) : 0;
if (!$mediaID && array_key_exists('imgid', $_REQUEST)) $mediaID = filter_var($_REQUEST['imgid'], FILTER_SANITIZE_NUMBER_INT);
$action = array_key_exists('submitaction', $_REQUEST) ? $_REQUEST['submitaction'] : '';
$eMode = array_key_exists('emode', $_REQUEST) ? filter_var($_REQUEST['emode'], FILTER_SANITIZE_NUMBER_INT) : 0;

$imgArr = Media::getMedia($mediaID);
$creatorArray = Media::getCreatorArray();

$isEditor = false;
if ($IS_ADMIN || ($imgArr && ($imgArr['username'] === $USERNAME || ($imgArr['creatorUid'] && $imgArr['creatorUid'] == $SYMB_UID)))) {
	$isEditor = true;
}

$status = '';

if ($isEditor) {
	if ($action == 'Submit Image Edits') {
		Media::update($mediaID, $_POST, new LocalStorage());
	} elseif ($action == 'Transfer Image') {
		Media::update($mediaID, ['tid' => $_REQUEST['targettid']]);
		header('Location: ../taxa/profile/tpeditor.php?tid=' . $_REQUEST['targettid'] . '&tabindex=1');
	} elseif ($action == 'Delete Image') {
		$remove_files = $_REQUEST['removeimg'] ?? false;
		Media::delete(intval($mediaID), boolval($remove_files));
	}
	$imgArr = Media::getMedia($mediaID);
}
$serverPath = GeneralUtil::getDomain();
if ($imgArr) {
	$imgUrl = $imgArr['url'];
	$origUrl = $imgArr['originalUrl'];
	$metaUrl = $imgArr['url'];
	if (array_key_exists('MEDIA_DOMAIN', $GLOBALS)) {
		if (substr($imgUrl, 0, 1) == '/') {
			$imgUrl = $GLOBALS['MEDIA_DOMAIN'] . $imgUrl;
			$metaUrl = $GLOBALS['MEDIA_DOMAIN'] . $metaUrl;
		}
		if ($origUrl && substr($origUrl, 0, 1) == '/') {
			$origUrl = $GLOBALS['MEDIA_DOMAIN'] . $origUrl;
		}
	}
	if (substr($metaUrl, 0, 1) == '/') {
		$metaUrl = $serverPath . $metaUrl;
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>" />
	<?php
	if ($imgArr) {
	?>
		<meta property="og:title" content="<?php echo $imgArr["sciname"]; ?>" />
		<meta property="og:site_name" content="<?php echo $DEFAULT_TITLE; ?>" />
		<meta property="og:image" content="<?php echo $metaUrl; ?>" />
		<meta name="twitter:card" content="photo" data-dynamic="true" />
		<meta name="twitter:title" content="<?php echo $imgArr["sciname"]; ?>" />
		<meta name="twitter:image" content="<?php echo $metaUrl; ?>" />
		<meta name="twitter:url" content="<?php echo $serverPath . $CLIENT_ROOT . '/imagelib/imgdetails.php?mediaid=' . $mediaID; ?>" />
	<?php
	}
	?>
	<title><?php echo $DEFAULT_TITLE . " Image Details: #" . $mediaID; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	include_once($SERVER_ROOT . '/includes/googleanalytics.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../js/symb/shared.js" type="text/javascript"></script>
	<script>
		var clientRoot = "<?php echo $CLIENT_ROOT; ?>";

		(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s);
			js.id = id;
			js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.0";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));

		function verifyEditForm(f) {
			if (f.url.value.replace(/\s/g, "") == "") {
				window.alert("<?php echo $LANG['ERROR_FILE_PATH'] ?>");
				return false;
			}
			return true;
		}

		function verifyChangeTaxonForm(f) {
			var sciName = f.targettaxon.value.replace(/^\s+|\s+$/g, "");
			if (sciName == "") {
				window.alert("<?php echo $LANG['ENTER_TAXON_NAME'] ?>");
			} else {
				validateTaxon(f, true);
			}
			return false; //Submit takes place in the validateTaxon method
		}

		function openOccurrenceSearch(target) {
			occWindow = open("../collections/misc/occurrencesearch.php?targetid=" + target, "occsearch", "resizable=1,scrollbars=1,toolbar=0,width=750,height=750,left=400,top=40");
			if (occWindow.opener == null) occWindow.opener = self;
		}
	</script>
	<script src="../js/symb/api.taxonomy.taxasuggest.js?ver=3" type="text/javascript"></script>
	<style type="text/css">
		body {
			min-width: 400px;
		}

		#imageedit {
			min-width: 800px;
			padding: 10px;
			background-color: #FFFFFF;
		}
	</style>
</head>

<body>
	<div id="fb-root"></div>
	<?php
	//$displayLeftMenu = (isset($taxa_imgdetailsMenu)?$taxa_imgdetailsMenu:false);
	//include($SERVER_ROOT.'/includes/header.php');
	?>
	<!--
	<div class="navpath">
		<a href="../index.php">Home</a> &gt;&gt;
		<a href="index.php">Image Browser</a> &gt;&gt;
		<a href="search.php">Image Search</a> &gt;&gt;
		<?php
		//if(isset($imgArr['tid']) && $imgArr['tid']) echo '<a href="../taxa/index.php?tid=' . $imgArr['tid'] . '">Image Search</a> &gt;&gt;';
		//echo '<b>Image Profile: image <a href="imgdetails.php?mediaid=' . $mediaID . '">#' . $mediaID . '</a></b>';
		?>
	</div>
	 -->
	<div role="main" id="innertext">
		<!-- This is inner text! -->
		<h1 class="page-heading"><?php echo $LANG['IMG_DETAILS']; ?></h1>
		<?php
		if ($imgArr) {
		?>
			<div style="width:100%;float:right;clear:both;margin-top:10px;">
				<?php
				if ($SYMB_UID && ($IS_ADMIN || array_key_exists("TaxonProfile", $USER_RIGHTS))) {
				?>
					<div style="float:right;margin-right:15px;" title="<?php echo $LANG['TAXON_PROFILE_EDITING'] ?>">
						<a href="../taxa/profile/tpeditor.php?tid=<?php echo htmlspecialchars($imgArr['tid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&tabindex=1" target="_blank">
							<img src="../images/edit.png" style="width:1.3em;border:0px;" /><span style="font-size:70%"><?php echo $LANG['TP'] ?></span>
						</a>
					</div>
				<?php
				}
				if ($imgArr['occid']) {
				?>
					<div style="float:right;margin-right:15px;" title="<?php echo $LANG['EDITING_PRIVILEGES'] ?>">
						<a href="../collections/editor/occurrenceeditor.php?occid=<?php echo htmlspecialchars($imgArr['occid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&tabtarget=2" target="_blank">
							<img src="../images/edit.png" style="width:1.3em;border:0px;" /><span style="font-size:70%"><?php echo $LANG['SPEC'] ?></span>
						</a>
					</div>
					<?php
				} else {
					if ($isEditor) {
					?>
						<div style="float:right;margin-right:15px;">
							<a href="#" onclick="toggle('imageedit');return false" title="<?php echo $LANG['EDIT_IMAGE'] ?>">
								<img src="../images/edit.png" style="width:1.3em;border:0px;" /><span style="font-size:70%"><?php echo $LANG['IMG'] ?></span>
							</a>
						</div>
				<?php
					}
				}
				?>
				<div style="float:right;margin-right:10px;">
					<a class="twitter-share-button" data-text="<?php echo $imgArr["sciname"]; ?>" href="https://twitter.com/share" data-url="<?php echo htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . $CLIENT_ROOT . '/imagelib/imgdetails.php?mediaid=' . $mediaID; ?>"><?php echo $LANG['TWEET'] ?></a>
					<script>
						window.twttr = (function(d, s, id) {
							var js, fjs = d.getElementsByTagName(s)[0],
								t = window.twttr || {};
							if (d.getElementById(id)) return;
							js = d.createElement(s);
							js.id = id;
							js.src = "https://platform.twitter.com/widgets.js";
							fjs.parentNode.insertBefore(js, fjs);
							t._e = [];
							t.ready = function(f) {
								t._e.push(f);
							};
							return t;
						}(document, "script", "twitter-wjs"));
					</script>
				</div>
				<div style="float:right;margin-right:10px;">
					<div class="fb-share-button" data-href="" data-layout="button_count"></div>
				</div>
			</div>
		<?php
		}
		if ($status) {
		?>
			<hr />
			<div style="color:red;">
				<?php echo $status; ?>
			</div>
			<hr />
			<?php
		}
		if ($imgArr) {
			if ($isEditor && !$imgArr['occid']) {
			?>
				<div id="imageedit" style="display:<?php echo ($eMode ? 'block' : 'none'); ?>;">
					<form name="editform" action="imgdetails.php" method="post" target="_self" onsubmit="return verifyEditForm(this);">
						<fieldset style="margin:5px 0px 5px 5px;">
							<legend><b><?php echo $LANG['EDIT_IMAGE_DETAILS'] ?></b></legend>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['CAPTION'] ?>:</b>
								<input name="caption" type="text" value="<?php echo $imgArr["caption"]; ?>" style="width:250px;" />
							</div>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['CREATOR_USER_ID'] ?>:</b>
								<select name="creatorUid" name="creatorUid">
									<option value=""><?php echo $LANG['SELECT_CREATOR'] ?></option>
									<option value="">---------------------------------------</option>
									<?php
									foreach ($creatorArray as $id => $uname) {
										echo '<option value="' . $id . '" >';
										echo $uname;
										echo '</option>';
									}
									?>
								</select>
								* <?php echo $LANG['USER_REGISTERED_SYSTEM'] ?>
								<a href="#" onclick="toggle('iepor');return false;" title="<?php echo $LANG['DISPLAY_CREATOR_FIELD'] ?>">
									<img src="../images/editplus.png" style="border:0px;width:1.5em;" />
								</a>
							</div>
							<div id="iepor" style="margin-top:2px;display:<?php echo ($imgArr["creator"] ? 'block' : 'none'); ?>;">
								<b><?php echo $LANG['CREATOR_OVERRIDE'] ?>:</b>
								<input name="CREATOR" type="text" value="<?php echo $imgArr["creator"]; ?>" style="width:250px;" />
								* <?php echo $LANG['OVERRIDE_SELECTION'] ?>
							</div>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['MANAGER'] ?>:</b>
								<input name="owner" type="text" value="<?php echo $imgArr["owner"]; ?>" style="width:250px;" />
							</div>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['SOURCE_URL'] ?>:</b>
								<input name="sourceurl" type="text" value="<?php echo $imgArr["sourceUrl"]; ?>" style="width:450px;" />
							</div>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['COPYRIGHT'] ?>:</b>
								<input name="copyright" type="text" value="<?php echo $imgArr["copyright"]; ?>" style="width:450px;" />
							</div>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['RIGHTS'] ?>:</b>
								<input name="rights" type="text" value="<?php echo $imgArr["rights"]; ?>" style="width:450px;" />
							</div>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['LOCALITY'] ?>:</b>
								<input name="locality" type="text" value="<?php echo $imgArr["locality"]; ?>" style="width:550px;" />
							</div>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['OCCURRENCE_RECORD'] ?> #:</b>
								<input id="imgdisplay-<?php echo $mediaID; ?>" name="displayoccid" type="text" value="" disabled style="width:70px" />
								<input id="imgoccid-<?php echo $mediaID; ?>" name="occid" type="hidden" value="" />
								<span onclick="openOccurrenceSearch('<?php echo $mediaID; ?>');return false"><a href="#"><?php echo $LANG['LINK_OCCUR_RECORD'] ?></a></span>
							</div>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['NOTES'] ?>:</b>
								<input name="notes" type="text" value="<?php echo $imgArr["notes"]; ?>" style="width:550px;" />
							</div>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['SORT_SEQUENCE'] ?>:</b>
								<input name="sortsequence" type="text" value="<?php echo $imgArr["sortSequence"]; ?>" size="5" />
							</div>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['WEB_IMAGE'] ?>:</b><br />
								<input name="url" type="text" value="<?php echo $imgArr["url"]; ?>" style="width:90%;" />
								<?php
								if (stripos($imgArr["url"], $MEDIA_ROOT_URL) === 0) {
								?>
									<div style="margin-left:70px;">
										<input type="checkbox" name="renameweburl" value="1" />
										<?php echo $LANG['RENAME_WEB_IMAGE_FILE'] ?>
									</div>
									<input name="oldurl" type="hidden" value="<?php echo $imgArr["url"]; ?>" />
								<?php
								}
								?>
							</div>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['THUMBNAIL'] ?>:</b><br />
								<input name="thumbnailurl" type="text" value="<?php echo $imgArr["thumbnailUrl"]; ?>" style="width:90%;" />
								<?php
								if ($imgArr["thumbnailUrl"] && stripos($imgArr["thumbnailUrl"], $MEDIA_ROOT_URL) === 0) {
								?>
									<div style="margin-left:70px;">
										<input type="checkbox" name="renametnurl" value="1" />
										<?php echo $LANG['RENAME_THUMBNAIL_IMAGE_FILE'] ?>
									</div>
									<input name="oldthumbnailurl" type="hidden" value="<?php echo $imgArr["thumbnailUrl"]; ?>" />
								<?php
								}
								?>
							</div>
							<div style="margin-top:2px;">
								<b><?php echo $LANG['LARGE_IMAGE'] ?>:</b><br />
								<input name="originalUrl" type="text" value="<?php echo $imgArr["originalUrl"]; ?>" style="width:90%;" />
								<?php
								if (stripos($imgArr["originalUrl"], $MEDIA_ROOT_URL) === 0) {
								?>
									<div style="margin-left:80px;">
										<input type="checkbox" name="renameorigurl" value="1" />
										<?php echo $LANG['RENAME_LARGE_IMAGE_FILE'] ?>
									</div>
									<input name="oldoriginalurl" type="hidden" value="<?php echo $imgArr["originalUrl"]; ?>" />
								<?php
								}
								?>
							</div>
							<input name="mediaid" type="hidden" value="<?php echo $mediaID; ?>" />
							<div style="margin-top:2px;">
								<button type="submit" name="submitaction" id="editsubmit" value="Submit Image Edits"><?php echo $LANG['SUBMIT_IMAGE_EDITS'] ?></button>
							</div>
						</fieldset>
					</form>
					<form name="changetaxonform" action="imgdetails.php" method="post" target="_self" onsubmit="return verifyChangeTaxonForm(this);">
						<fieldset style="margin:5px 0px 5px 5px;">
							<legend><b><?php echo $LANG['TRANSFER_IMAGE_TO_DIFF_NAME'] ?></b></legend>
							<div style="font-weight:bold;">
								<?php echo $LANG['TRANSFER_TO_TAXON'] ?>:
								<input type="text" id="taxa" name="targettaxon" size="40" />
								<input type="hidden" id="tid" name="targettid" value="" />
								<input type="hidden" name="sourcetid" value="<?php echo $imgArr["tid"]; ?>" />
								<input type="hidden" name="mediaid" value="<?php echo $mediaID; ?>" />

								<input type="hidden" name="submitaction" value="Transfer Image" />
								<button type="submit" name="submitaction" value="Transfer Image"><?php echo $LANG['TRANSFER_IMAGE'] ?></button>
							</div>
						</fieldset>
					</form>
					<form name="deleteform" action="imgdetails.php" method="post" target="_self" onsubmit="return window.confirm('<?php echo $LANG['DELETE_IMAGE_FROM_SERVER'] ?>');">
						<fieldset style="margin:5px 0px 5px 5px;">
							<legend><b><?php echo $LANG['AUTHORIZED_REMOVE_IMAGE'] ?></b></legend>
							<input name="mediaid" type="hidden" value="<?php echo $mediaID; ?>" />
							<div style="margin-top:2px;">
								<button class="button-danger" type="submit" name="submitaction" id="submit" value="Delete Image"><?php echo $LANG['DELETE_IMAGE'] ?></button>
							</div>
							<input name="removeimg" type="checkbox" value="1" /> <?php echo $LANG['REMOVE_IMG_FROM_SERVER'] ?>
							<div style="margin-left:20px;color:red;">
								<?php echo $LANG['BOX_CHECKED_IMG_DELETED'] ?>
							</div>
						</fieldset>
					</form>
				</div>
			<?php
			}
			?>
			<div>
				<div style="width:350px;padding:10px;float:left;">
					<?php
					$imgDisplay = $imgUrl;
					$mediaType = MediaType::tryFrom($imgArr['mediaType']);
					if ((!$imgDisplay || $imgDisplay == 'empty') && $origUrl) $imgDisplay = $origUrl;
					?>
					<?php if ($mediaType === MediaType::Image): ?>
						<a href="<?php echo htmlspecialchars($imgDisplay, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
							<img src="<?php echo htmlspecialchars($imgDisplay, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" style="width:300px;" />
						</a>
						<?php
						if ($origUrl) echo '<div><a href="' . htmlspecialchars($origUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . $LANG['CLICK_IMAGE'] . '</a></div>';
						?>
					<?php elseif ($mediaType === MediaType::Audio): ?>
						<audio controls style="margin-top: 5rem">
							<source src="<?= $origUrl ?>" type="<?= $imgArr['format'] ?>">
							Your browser does not support the audio element.
						</audio>
					<?php endif ?>
				</div>
				<div style="padding:10px;float:left;">
					<div style="clear:both;margin-top:40px;">
						<b><?php echo $LANG['SCIENTIFIC_NAME'] ?>:</b> <?php echo '<a href="../taxa/index.php?taxon=' . htmlspecialchars($imgArr["tid"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><i>' . htmlspecialchars($imgArr["sciname"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</i> ' . htmlspecialchars($imgArr["author"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>'; ?>
					</div>
					<?php
					if ($imgArr['caption']) echo '<div><b>' . $LANG['CAPTION'] . ':</b> ' . $imgArr['caption'] . '</div>';
					if ($imgArr['creatorDisplay']) {
						echo '<div><b>' . $LANG['CREATOR'] . ':</b> ';
						if (!$imgArr['creator']) {
							$phLink = 'search.php?imagetype=all&phuid=' . $imgArr['creatorUid'] . '&submitaction=search';
							echo '<a href="' . htmlspecialchars($phLink, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
						}
						echo $imgArr['creatorDisplay'];
						if (!$imgArr['creator']) echo '</a>';
						echo '</div>';
					}
					if ($imgArr['owner']) echo '<div><b>' . $LANG['MANAGER'] . ':</b> ' . $imgArr['owner'] . '</div>';
					if ($imgArr['sourceUrl']) echo '<div><b>' . $LANG['IMAGE_SOURCE'] . ':</b> <a href="' . htmlspecialchars($imgArr['sourceurl'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($imgArr['sourceurl'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
					if ($imgArr['locality']) echo '<div><b>' . $LANG['LOCALITY'] . ':</b> ' . $imgArr['locality'] . '</div>';
					if ($imgArr['notes']) echo '<div><b>' . $LANG['NOTES'] . ':</b> ' . $imgArr['notes'] . '</div>';
					if ($imgArr['rights']) echo '<div><b>' . $LANG['RIGHTS'] . ':</b> ' . $imgArr['rights'] . '</div>';
					if ($imgArr['copyright']) {
						echo '<div>';
						echo '<b>' . $LANG['COPYRIGHT'] . ':</b> ';
						if (stripos($imgArr['copyright'], 'http') === 0) echo '<a href="' . htmlspecialchars($imgArr['copyright'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($imgArr['copyright'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
						else echo $imgArr['copyright'];
						echo '</div>';
					} else {
						echo '<div><a href="../includes/usagepolicy.php#images">' . $LANG['COPYRIGHT_DETAILS'] . '</a></div>';
					}
					if ($imgArr['occid']) echo '<div><a href="../collections/individual/index.php?occid=' . htmlspecialchars($imgArr['occid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . $LANG['DISPLAY_SPECIMEN_DETAILS'] . '</a></div>';
					if ($imgUrl) echo '<div><a href="' . htmlspecialchars($imgUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . $LANG['OPEN_MEDIUM_SIZED_IMAGE'] . '</a></div>';
					if ($origUrl) echo '<div><a href="' . htmlspecialchars($origUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . $LANG['OPEN_LARGE_IMAGE'] . '</a></div>';
					$emailAddress = $ADMIN_EMAIL;
					if ($emailAddress) {
					?>
						<div style="margin-top:20px;">
							<?php echo $LANG['ERROR_COMMENT_ABOUT_IMAGE'] ?> <br /><?php echo $LANG['SEND_EMAIL'] ?>:
							<?php
							$emailSubject = $DEFAULT_TITLE . ' ' . $LANG['IMG_NO'] . ' ' . $mediaID;
							$emailBody = 'Image being referenced: ' . urlencode($serverPath . $CLIENT_ROOT . '/imagelib/imgdetails.php?mediaid=' . $imgId);
							$emailRef = 'subject=' . $emailSubject . '&cc=' . $ADMIN_EMAIL . '&body=' . $emailBody;
							echo '<a href="mailto:' . $ADMIN_EMAIL . '?' . $emailRef . '">' . $emailAddress . '</a>';
							?>
						</div>
					<?php
					}
					?>
				</div>
				<div style="clear:both;"></div>
			</div>
		<?php
		} else {
			echo '<h2 style="margin:30px;">' . $LANG['UNABLE_TO_LOCATE'] . '</h2>';
		}
		?>
	</div>
	<?php
	//include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>

</html>