<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceCollectionProfile.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
include_once($SERVER_ROOT.'/classes/UtilityFunctions.php');
if($LANG_TAG == 'en' ||!file_exists($SERVER_ROOT . '/content/lang/collections/misc/collprofiles.' . $LANG_TAG . '.php'))
	include_once($SERVER_ROOT . '/content/lang/collections/misc/collprofiles.en.php');
else include_once($SERVER_ROOT . '/content/lang/collections/misc/collprofiles.' . $LANG_TAG . '.php');
header('Content-Type: text/html; charset=' . $CHARSET);
unset($_SESSION['editorquery']);

$collManager = new OccurrenceCollectionProfile();

$collid = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$eMode = array_key_exists('emode', $_REQUEST) ? $collManager->sanitizeInt($_REQUEST['emode']) : 0;
$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : '';

$SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT = $SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT ?? false;
$SHOULD_USE_HARVESTPARAMS = $SHOULD_USE_HARVESTPARAMS ?? false;
$actionPage = $SHOULD_USE_HARVESTPARAMS ? ($CLIENT_ROOT . "/collections/harvestparams.php") : ($CLIENT_ROOT . "/collections/search/index.php");


if ($eMode && !$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/misc/collprofiles.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collManager->setCollid($collid);

$collectionData = $collManager->getCollectionMetadata();
$datasetKey = $collManager->getDatasetKey();

$editCode = 0;		//0 = no permissions; 1 = CollEditor; 2 = CollAdmin; 3 = SuperAdmin
if ($SYMB_UID) {
	if ($IS_ADMIN) {
		$editCode = 3;
	}
	else if ($collid) {
		if (array_key_exists('CollAdmin', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollAdmin'])) $editCode = 2;
		elseif (array_key_exists('CollEditor', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollEditor'])) $editCode = 1;
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . ($collid && isset($collectionData[$collid])? $collectionData[$collid]['collectionname'] : ''); ?></title>
	<meta name="keywords" content="Natural history collections,<?php echo ($collid && array_key_exists($collid, $collectionData) ? $collectionData[$collid]['collectionname'] : ''); ?>" />
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script>

		function toggleById(target) {
			if (target != null) {
				var obj = document.getElementById(target);
				if (obj.style.display == "none" || obj.style.display == "") {
					obj.style.display = "block";
				} else {
					obj.style.display = "none";
				}
			}
			return false;
		}

		function submitAndRedirectSearchForm(urlPtOne, urlPtTwo, urlPtTwoAlt, urlPtThree, urlPtThreeAlt) {
			try{
				const collId = document?.forms['quicksearch']['collid']?.value;
				const hasIdentifier = Boolean(document?.forms['quicksearch']['catalog-number']?.value);
				const val = hasIdentifier ? document?.forms['quicksearch']['catalog-number']?.value : document?.forms['quicksearch']['taxon-search']?.value;
				if(!val){
					alert("You must provide a search term.");
				}else{
					const url = urlPtOne + collId + (hasIdentifier? urlPtTwo: urlPtTwoAlt) + val + (hasIdentifier ? urlPtThree : urlPtThreeAlt);
					window.location.href = url;
				}
			}catch(err){
				console.log(err);
			}
		}

		function processEditQuickSearch(clientRoot){
			const collId = document?.forms['quicksearch']['collid']?.value || null;
			const catNum = document?.forms['quicksearch']['catalog-number']?.value || null;
			const taxon = document?.forms['quicksearch']['taxon-search']?.value || null;
			if(collId){
				let redirectUrl = clientRoot + '/collections/editor/occurrencetabledisplay.php?displayquery=1&collid=' + encodeURIComponent(collId);
				if(catNum){
					redirectUrl = clientRoot + '/collections/editor/occurrenceeditor.php?q_customfield1=catalogNumber&q_customtype1=EQUALS&q_customvalue1=' + encodeURIComponent(catNum) + '&q_customandor2=OR&q_customfield2=otherCatalogNumbers&q_customtype2=EQUALS&q_customvalue2=' + encodeURIComponent(catNum) + '&collid=' + encodeURIComponent(collId) + '&displayquery=1&occindex=0&reset=1';
				}
				if(taxon && !catNum){
					redirectUrl = clientRoot + '/collections/editor/occurrenceeditor.php?q_customfield1=sciname&q_customtype1=STARTS_WITH&q_customvalue1=' + encodeURIComponent(taxon) + '&collid=' + encodeURIComponent(collId) + '&displayquery=1&occindex=0&reset=1';
				}
				window.location.href = redirectUrl;
			}
		}
		function directSubmitAction(e) {
			if(!e.submitter || !e.submitter.value) return false;

			if(e.submitter.value === "edit") {
				return processEditQuickSearch('<?php echo $CLIENT_ROOT ?>')
			} else if(e.submitter.value === "search") {
				return submitAndRedirectSearchForm('<?php echo $CLIENT_ROOT ?>/collections/list.php?db=','&catnum=', '&taxa=', '&includecult=' + <?php echo $SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT ? '1' : '0' ?> + '&includeothercatnum=1', '&includecult=' + <?php echo $SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT ? '1' : '0' ?> + '&usethes=1&taxontype=2 ');
			}

			e.preventDefault();
			return false;
		}
	</script>
	<style>
		.importItem { margin-left:10px; display:none; }
		.field-div { margin: 10px 0px; clear: both; }
		.label { font-weight: bold; }
		.float-rt-no-overlap {
			/* this should occur after fieldset-like definitions */
			float: right;
			clear: both;
			margin: 2rem 2rem 2rem 2rem;
		}
		.no-left-margin {
			margin-left: 0;
		}
		.col-profile-img {
			border: 1px;
			height: 6.4rem;
			width: 6.4rem;
		}
		.col-profile-header {
			margin-left: 0.5em;
		}
		.col-profile-inst-code {
			min-width: 9rem;
			max-width: 9rem;
		}
		.bigger-left-margin-rel {
			margin-left: 3rem;
		}

		#quicksearch-box input {
			width: 100%;
		}
		.quicksearch-input-container {
			display: flex;
			flex-wrap: wrap;
			width:100%;
		}
		.quicksearch-container {
			top: 1rem;
			right: 1rem;
			position:sticky;
			width: 100vw;
			margin-left: calc(50% - 50vw);
		}

		@media (max-width: 1424px) {
			#quicksearch-box {
				width:100%;
				margin: 1rem 0;
			}
			#quicksearch-btn-container {
				justify-content: right
			}
			.quicksearch-container {
				width: 100%;
				position: static;
				margin: 0 0;
			}
			.quicksearch-input-container {
				display: flex;
				flex-wrap: wrap;
				min-width: 10rem;
				max-width: 30%;
			}
			.quicksearch-btn-container {
				max-width: 30%;
			}
		}
		@media (max-width: 560px) {
			.quicksearch-input-container {
				display: flex;
				flex-wrap: wrap;
				min-width: 10rem;
				max-width: 100%;
			}
		}
		@media (min-width: 1425px) {
			#quicksearch-box {
				width: 12vw;
				right: 1rem;
				float: right;
			}
		}
		@media (min-width: 1500px) {
			#quicksearch-box {
				width: 14vw;
				right: 1rem;
				float: right;
			}
		}
		@media (min-width: 1550px) {
			#quicksearch-box {
				width: 15vw;
				right: 1rem;
				float: right;
			}
		}
		@media (min-width: 1700px) {
			#quicksearch-box {
				width: 18vw;
				right: 1rem;
				float: right;
			}
		}
		@media (min-width: 1880px) {
			#quicksearch-box {
				width: 21vw;
				right: 1rem;
				float: right;
			}
		}
	</style>
	<link href="<?php echo $CLIENT_ROOT ?>/collections/search/css/searchStyles.css?ver=1" type="text/css" rel="stylesheet" />
	<link href="<?php echo $CLIENT_ROOT ?>/collections/search/css/searchStylesInner.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<?php
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php"><?= $LANG['HOME'] ?></a> &gt;&gt;
		<a href="../index.php"><?= $LANG['COLLECTION_SEARCH'] ?></a> &gt;&gt;
		<b><?= $LANG['COLL_PROFILE'] ?></b>
	</div>
	<div role="main" id="innertext" style="padding-top:0">
		<?php
		if ($collid && !$collid == 0){
			?>
			<div class="quicksearch-container">
			<section id="quicksearch-box" class="fieldset-like" >
				<h3><span><?= $LANG['QUICK_SEARCH'] ?></span></h3>
				<div id="dialogContainer" style="position: relative;">
					<form name="quicksearch" style="display: flex; align-items:center; gap:0.5rem; flex-wrap: wrap" action="javascript:void(0);" onsubmit="directSubmitAction(event)">
						<div class="quicksearch-input-container">
								<label style="display:flex; align-items: center; position: relative; margin-right: 1.5rem" for="catalog-number"><?= $LANG['OCCURENCE_IDENTIFIER'] ?>
						<a href="#" id="q_catalognumberinfo" style="text-decoration:none; position: absolute; right: -1.5rem">
							<img src="../../images/info.png" style="width:1.3em;" alt="<?= $LANG['MORE_INFO_ALT']; ?>" title="<?= $LANG['MORE_INFO']; ?>" aria-label="<?= $LANG['MORE_INFO']; ?>"/>
						</a>
								</label>
						<span class="screen-reader-only">
							<?= $LANG['IDENTIFIER_PLACEHOLDER_LIST'] . ' ' ?>
						</span>
						<input style="margin-bottom: 0" name="catalog-number" id="catalog-number" type="text" />
						<dialog id="dialogEl" aria-live="polite" aria-label="Catalog number search dialog">
							<?= $LANG['IDENTIFIER_PLACEHOLDER_LIST'] . ' ' ?>
							<button id="closeDialog">Close</button>
						</dialog>
						</div>
						<input name="collid" type="hidden" value="<?= $collid; ?>" />
						<input name="occindex" type="hidden" value="0" />
						<div class="quicksearch-input-container">
						<label for="taxon-search"><?= $LANG['TAXON'] ?></label>
						<input style="margin-bottom: 0" name="taxon-search" id="taxon-search" type="text" />
						</div>
						<div id="quicksearch-btn-container" style="display:flex; gap: 0.5rem; flex-grow:1">
							<?php
							if($editCode == 1 || $editCode == 2 || $editCode == 3){
								?>
								<button type="submit" id="search-by-catalog-number-admin-btn" value="edit">
									<?= $LANG['OCCURRENCE_EDITOR'] ?>
								</button>
								<?php
							}
							?>
							<button type="submit" value='search' id="search-by-catalog-number-btn" title="<?= $LANG['IDENTIFIER_PLACEHOLDER_LIST'] ?>">
								<?= $LANG['SEARCH'] ?>
							</button>
						</div>
					</form>
				</div>
			</section>
			</div>
		<?php
		}
		if ($editCode > 1) {
			if ($action == 'UpdateStatistics') {
				echo '<h2> ' . $LANG['UPDATE_STATISTICS'] . '</h2>';
				$collManager->updateStatistics(true);
				echo '<hr/>';
			}
		}

		if ($collid && isset($collectionData[$collid])) {
			$collData = $collectionData[$collid];
			$codeStr = ' (' . $collData['institutioncode'];
			if ($collData['collectioncode']) $codeStr .= '-' . $collData['collectioncode'];
			$codeStr .= ')';
			$_SESSION['colldata'] = $collData;
			echo '<h1 class="page-heading">' . $LANG['COLL_PROF_FOR'] . ':<br>' . $collData['collectionname'] . $codeStr . '</h1>';
			// GBIF citations widget
			if ($datasetKey) {
				echo '<div style="margin-left: 10px; margin-bottom: 20px;">';
				echo '<iframe title="GBIF citation" src="https://www.gbif.org/api/widgets/literature/button?gbifDatasetKey=' . $datasetKey . '" frameborder="0" allowtransparency="true" style="width: 140px; height: 24px;"></iframe>';
    			echo '<a href="https://bionomia.net/dataset/' . $datasetKey . '"><img src="https://api.bionomia.net/dataset/' . $datasetKey . '/badge.svg" onerror="this.style.display=\'none\'" alt="Bionomia dataset badge" style="width:262px; height:24px; padding-left:10px;"></a>';
				echo '</div>';
			}

			if ($editCode) {
				$deactivateStyle = '';
				$deactivateTag = '';
				$deactivateMsg = '';
				if ($collData['managementtype'] != 'Live Data'){
					//Deactivated until these changes can be better reviewed - shooting to re-activate for 3.2
					//$deactivateStyle = 'style="pointer-events: none"';
					//$deactivateTag = '&nbsp;(*' . $LANG['DEACTIVATED'] . ')';
					//$deactivateMsg = '<div>* ' . $LANG['DEACTIVATED_MESSAGE'] . '</div>';
				}
				?>
				<button style="margin-bottom: 0.5rem" type="button" onclick="toggleById('controlpanel');" >
					<?= $LANG['TOGGLE_MAN'] ?>
				</button>
				<div id="controlpanel" style="display:<?php echo ($eMode ? 'block' : 'none'); ?>;">
					<section class="fieldset-like no-left-margin">
						<h2><span><?= $LANG['DAT_EDIT'] ?></span></h2>
						<ul>
							<?php
							if (stripos($collData['colltype'], 'observation') !== false) {
								?>
								<li>
									<a href="../editor/observationsubmit.php?collid=<?= $collid ?>" <?= $deactivateStyle ?>>
										<?= $LANG['SUBMIT_IMAGE_V'] ?>
									</a><?= $deactivateTag ?>
								</li>
								<?php
							}
							?>
							<li>
								<a href="../editor/occurrenceeditor.php?gotomode=1&collid=<?= $collid ?>" <?= $deactivateStyle ?>>
									<?= $LANG['ADD_NEW_OCCUR'] ?>
								</a><?= $deactivateTag ?>
							</li>
							<?php
							if ($collData['colltype'] == 'Preserved Specimens') {
								?>
								<li style="margin-left:10px">
									<a href="../editor/imageoccursubmit.php?collid=<?= $collid ?>" <?= $deactivateStyle ?>>
										<?= $LANG['CREATE_NEW_REC'] ?>
									</a><?= $deactivateTag ?>
								</li>
								<li style="margin-left:10px">
									<a href="../editor/skeletalsubmit.php?collid=<?= $collid ?>" <?= $deactivateStyle ?>>
										<?= $LANG['SKELETAL'] ?>
									</a><?= $deactivateTag ?>
								</li>
								<?php
							}
							?>
							<li>
								<a href="../editor/occurrencetabledisplay.php?displayquery=1&collid=<?= $collid ?>">
									<?= $LANG['EDIT_EXISTING'] ?>
								</a>
							</li>
							<li>
								<a href="../editor/batchdeterminations.php?collid=<?= $collid ?>">
									<?= $LANG['ADD_BATCH_DETER'] ?>
								</a>
							</li>
							<li>
								<a href="../reports/labelmanager.php?collid=<?= $collid ?>">
									<?= $LANG['PRINT_LABELS'] ?>
								</a>
							</li>
							<li>
								<a href="../reports/annotationmanager.php?collid=<?= $collid ?>">
									<?= $LANG['PRINT_ANNOTATIONS'] ?>
								</a>
							</li>
							<?php
							if ($collManager->traitCodingActivated()) {
								?>
								<li>
									<a href="#" onclick="$('li.traitItem').show(); return false;">
										<?= $LANG['TRAIT_CODING_TOOLS'] ?>
									</a>
								</li>
								<li class="traitItem" style="margin-left:10px;display:none;">
									<a href="../traitattr/occurattributes.php?collid=<?= $collid ?>">
										<?= $LANG['TRAIT_CODING'] ?>
									</a>
								</li>
								<li class="traitItem" style="margin-left:10px;display:none;">
									<a href="../traitattr/attributemining.php?collid=<?= $collid ?>">
										<?= $LANG['TRAIT_MINING'] ?>
									</a>
								</li>
								<?php
							}
							?>
							<li>
								<a href="../georef/batchgeoreftool.php?collid=<?= $collid ?>">
									<?= $LANG['BATCH_GEOREF'] ?>
								</a>
							</li>
							<?php
							if ($collData['colltype'] == 'Preserved Specimens') {
								?>
								<li>
									<a href="../loans/index.php?collid=<?= $collid ?>" <?= $deactivateStyle ?>>
										<?= $LANG['LOAN_MANAGEMENT'] ?>
									</a><?= $deactivateTag ?>
								</li>
								<?php
							}
							?>
						</ul>
						<?= $deactivateMsg ?>
					</section>
					<?php
					if ($editCode > 1) {
						?>
						<section class="fieldset-like no-left-margin">
							<h2><span><?= $LANG['ADMIN_CONTROL'] ?></span></h2>
							<ul>
								<li>
									<a href="commentlist.php?collid=<?= $collid ?>">
										<?= $LANG['VIEW_COMMENTS'] ?>
									</a>
									<?php if ($commCnt = $collManager->unreviewedCommentsExist()) echo '- <span style="color:orange">' . $commCnt . ' ' . $LANG['UNREVIEWED_COMMENTS'] . '</span>'; ?>
								</li>
								<li>
									<a href="collmetadata.php?collid=<?= $collid ?>">
										<?= $LANG['EDIT_META'] ?>
									</a>
								</li>
								<!--
								<li>
									<a href="" onclick="$('li.metadataItem').show(); return false;"  >
										<?= $LANG['OPEN_META'] ?>
									</a>
								</li>
								<li class="metadataItem" style="margin-left:10px;display:none;">
									<a href="collmetadata.php?collid=<?= $collid ?>">
										<?= $LANG['EDIT_META'] ?>
									</a>
								</li>
								<li class="metadataItem" style="margin-left:10px;display:none;">
									<a href="colladdress.php?collid=<?= $collid ?>">
										<?= $LANG['EDIT_ADDRESS'] ?>
									</a>
								</li>
								<li class="metadataItem" style="margin-left:10px;display:none;">
									<a href="collproperties.php?collid=<?= $collid ?>">
										<?= $LANG['EDIT_COLL_PROPS'] ?>
									</a>
								</li>
								 -->
								<li>
									<a href="collpermissions.php?collid=<?= $collid ?>">
										<?= $LANG['MANAGE_PERMISSIONS'] ?>
									</a>
								</li>
								<li>
									<a href="#" onclick="$('li.importItem').show(); return false;">
										<?= $LANG['IMPORT_SPECIMEN'] ?>
									</a>
								</li>
								<li class="importItem">
									<a href="../admin/specupload.php?uploadtype=7&collid=<?php echo $collid; ?>">
										<?= $LANG['SKELETAL_FILE_IMPORT'] ?>
									</a>
								</li>
								<li class="importItem">
									<a href="../admin/specupload.php?uploadtype=3&collid=<?php echo $collid; ?>">
										<?= $LANG['TEXT_FILE_IMPORT'] ?>
									</a>
								</li>
								<li class="importItem">
									<a href="../admin/specupload.php?uploadtype=6&collid=<?php echo $collid; ?>">
										<?= $LANG['DWCA_IMPORT'] ?>
									</a>
								</li>
								<li class="importItem">
									<a href="../admin/specupload.php?uploadtype=8&collid=<?php echo $collid; ?>">
										<?= $LANG['IPT_IMPORT'] ?>
									</a>
								</li>
								<li class="importItem">
									<a href="../admin/importextended.php?collid=<?php echo $collid; ?>">
										<?= $LANG['EXTENDED_IMPORT'] ?>
									</a>
								</li>
								<?php
								if ($collData['managementtype'] == 'Live Data') {
									?>
									<li class="importItem">
										<a href="../admin/specupload.php?uploadtype=9&collid=<?php echo $collid; ?>">
											<?= $LANG['NFN_IMPORT'] ?>
										</a>
									</li>
									<?php
								}
								?>
								<li class="importItem">
									<a href="../admin/specuploadmanagement.php?collid=<?php echo $collid; ?>">
										<?= $LANG['IMPORT_PROFILES'] ?>
									</a>
								</li>
								<li class="importItem">
									<a href="../admin/specuploadmanagement.php?action=addprofile&collid=<?php echo $collid; ?>">
										<?= $LANG['CREATE_PROFILE'] ?>
									</a>
								</li>
								<?php
								if ($collData['colltype'] != 'General Observations') {
									if ($collData['managementtype'] != 'Aggregate') {
										?>
										<li>
											<a href="../specprocessor/index.php?collid=<?= $collid ?>">
												<?= $LANG['PROCESSING_TOOLBOX'] ?>
											</a>
										</li>
										<li>
											<a href="../datasets/datapublisher.php?collid=<?= $collid ?>">
												<?= $LANG['DARWIN_CORE_PUB'] ?>
											</a>
										</li>
										<?php
									}
									?>
									<li>
										<a href="../editor/editreviewer.php?collid=<?= $collid ?>">
											<?= $LANG['REVIEW_SPEC_EDITS'] ?>
										</a>
									</li>
									<!--
									<li>
										<a href="../reports/accessreport.php?collid=<?= $collid ?>">
											<?= $LANG['ACCESS_REPORT'] ?>
										</a>
									</li>
									 -->
									<?php
								}
								if (!empty($ACTIVATE_DUPLICATES)) {
									?>
									<li>
										<a href="../datasets/duplicatemanager.php?collid=<?= $collid ?>">
											<?= $LANG['DUP_CLUSTER'] ?>
										</a>
									</li>
									<?php
								}
								?>
								<li>
									<?= $LANG['MAINTENANCE_TASKS'] ?>
								</li>
								<?php
								if ($collData['colltype'] != 'General Observations') {
									?>
									<li style="margin-left:10px;">
										<a href="../cleaning/index.php?obsuid=0&collid=<?= $collid ?>">
											<?= $LANG['DATA_CLEANING'] ?>
										</a>
									</li>
									<?php
								}
								?>
								<li style="margin-left:10px;">
									<a href="#" onclick="newWindow = window.open('collbackup.php?collid=<?= $collid ?>','bucollid','scrollbars=1,toolbar=0,resizable=1,width=600,height=250,left=20,top=20');">
										<?= $LANG['BACKUP_DATA_FILE'] ?>
									</a>
								</li>
								<?php
								if ($collData['managementtype'] == 'Live Data') {
									?>
									<li style="margin-left:10px;">
										<a href="../admin/restorebackup.php?collid=<?= $collid ?>">
											<?= $LANG['RESTORE_BACKUP'] ?>
										</a>
									</li>
								<!--
								<li style="margin-left:10px;">
									<a href="../../imagelib/admin/igsnmapper.php?collid=<?= $collid ?>">
										<?= $LANG['GUID_MANAGEMENT'] ?>
									</a>
								</li>
								 -->
									<?php
								}
								?>
								<li style="margin-left:10px;">
									<a href="../../imagelib/admin/thumbnailbuilder.php?collid=<?= $collid ?>">
										<?= $LANG['THUMBNAIL_MAINTENANCE'] ?>
									</a>
								</li>
								<li style="margin-left:10px;">
									<a href="collprofiles.php?collid=<?= $collid ?>&action=UpdateStatistics">
										<?= $LANG['UPDATE_STATS'] ?>
									</a>
								</li>
							</ul>
						</section>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>
			<div class="coll-description bottom-breathing-room-rel"><?= $collData['fulldescription'] ?></div>
			<?php
			if(isset($collData['resourcejson'])){
				if($resourceArr = json_decode($collData['resourcejson'], true)){
					$title = $LANG['HOMEPAGE'];
					foreach($resourceArr as $rArr){
						if(!empty($rArr['title'][$LANG_TAG])) $title = $rArr['title'][$LANG_TAG];
						?>
						<div class="field-div">
							<a href="<?= htmlspecialchars($rArr['url'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>" target="_blank"><?= $title ?></a>
						</div>
						<?php
					}
				}
			}
			if(!empty($collData['contactjson'])){
				if($contactArr = json_decode($collData['contactjson'], true)){
					if(!empty($contactArr)){
						?>
						<section style="margin-left: 0;">
							<h1><span><?= $LANG['CONTACT'] ?>: </span></h1>
							<ul>
								<?php
								foreach($contactArr as $cArr){
									?>
									<li>
										<div class="field-div">
											<?php
											if(!empty($cArr['role'])){
												echo '<span class="label">' . $cArr['role'] . ': </span>';
											}
											echo $cArr['firstName'].' '.$cArr['lastName'];
											if(!empty($cArr['email'])) echo ', ' . $cArr['email'];
											if(!empty($cArr['phone'])) echo ', ' . $cArr['phone'];
											if(!empty($cArr['orcid'])) echo ' (ORCID #: <a href="https://orcid.org/' . $cArr['orcid'] . '" target="_blank">'. $cArr['orcid'] . '</a>)';
											?>
										</div>
									</li>
									<?php
								}
								?>
							</ul>
						</section>
						<?php
					}
				}
			}
			if ($collData['publishtogbif'] && $datasetKey) {
				$dataUrl = 'http://www.gbif.org/dataset/' . $datasetKey;
				?>
				<div style="margin-top:5px;">
					<div><b><?= $LANG['GBIF_DATASET'] ?>:</b> <a href="<?= $dataUrl ?>" target="_blank" rel="noopener noreferrer"><?= $dataUrl ?></a></div>
				</div>
				<?php
			}
			if ($collData['publishtoidigbio']) {
				$idigbioKey = $collManager->getIdigbioKey();
				if (!$idigbioKey) $idigbioKey = $collManager->findIdigbioKey($collData['recordid']);
				if ($idigbioKey) {
					$dataUrl = 'https://www.idigbio.org/portal/recordsets/' . $idigbioKey;
					?>
					<div style="margin-top:5px;">
						<div><b><?= $LANG['IDIGBIO_DATASET'] ?>:</b> <a href="<?= $dataUrl ?>" target="_blank" rel="noopener noreferrer"><?= $dataUrl ?></a></div>
					</div>
					<?php
				}
			}
			if (file_exists($SERVER_ROOT . '/includes/citationcollection.php')) {
				echo '<div class="field-div"><span class="label">Cite this collection:</span><blockquote>';
				// If GBIF dataset key is available, fetch GBIF format from API
				if ($collData['publishtogbif'] && $datasetKey && file_exists($SERVER_ROOT . '/includes/citationgbif.php')) {
					$gbifUrl = 'http://api.gbif.org/v1/dataset/' . $datasetKey;
					$responseData = json_decode(file_get_contents($gbifUrl));
					if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {
						error_log('Error in JSON decoding: ' . json_last_error_msg());
						throw new Exception('Error in JSON decoding');
					}
					$collData['gbiftitle'] = $responseData->title;
					$collData['doi'] = $responseData->doi;
					$_SESSION['colldata'] = $collData;
					include($SERVER_ROOT . '/includes/citationgbif.php');
				} else {
					include($SERVER_ROOT . '/includes/citationcollection.php');
				}
				echo '</blockquote></div>';
			}
			if ($addrArr = $collManager->getAddress()) {
				?>
				<section class="fieldset-like no-left-margin">
					<h2><span><?php echo (isset($LANG['ADDRESS']) ? $LANG['ADDRESS'] : 'Address'); ?>:</span></h2>
					<div class="bigger-left-margin-rel">
						<?php
						echo "<div>" . $addrArr["institutionname"];
						if ($editCode > 1) echo ' <a href="institutioneditor.php?emode=1&targetcollid=' . $collid . '&iid=' . htmlspecialchars($addrArr['iid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" title="' . $LANG['EDIT_INST'] . '"><img src="../../images/edit.png" style="width:1.3em;" alt="edit icon" /></a>';
						echo '</div>';
						if ($addrArr["institutionname2"]) echo "<div>" . $addrArr["institutionname2"] . "</div>";
						if ($addrArr["address1"]) echo "<div>" . $addrArr["address1"] . "</div>";
						if ($addrArr["address2"]) echo "<div>" . $addrArr["address2"] . "</div>";
						if ($addrArr["city"]) echo "<div>" . $addrArr["city"] . ", " . $addrArr["stateprovince"] . "&nbsp;&nbsp;&nbsp;" . $addrArr["postalcode"] . "</div>";
						if ($addrArr["country"]) echo "<div>" . $addrArr["country"] . "</div>";
						if ($addrArr["phone"]) echo "<div>" . $addrArr["phone"] . "</div>";
						if ($addrArr["url"]) echo '<div><a href="' . htmlspecialchars($addrArr['url'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($addrArr['url'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
						if ($addrArr["notes"]) echo "<div>" . $addrArr["notes"] . "</div>";
						?>
					</div>
				</section>
				<?php
			}
			//Collection Statistics
			$statsArr = $collManager->getBasicStats();
			$georefPerc = 0;
			if ($statsArr['georefcnt'] && $statsArr['recordcnt'] && $statsArr['recordcnt'] !== 0){
				$georefPerc = (100 * ($statsArr['georefcnt'] / $statsArr['recordcnt']));
			}
			else if ($statsArr['recordcnt'] === 0){
				throw new Exception("Division by zero error.");
			}
			?>
			<section class="fieldset-like no-left-margin">
				<h2><span><?= $LANG['COLL_STATISTICS'] ?></span></h2>
				<div style="clear:both;margin-top:5px;">
					<ul style="margin-top:5px;">
						<li><?php echo number_format($statsArr["recordcnt"]) . ' ' . $LANG['SPECIMEN_RECORDS'] ?></li>
						<li><?php echo ($statsArr['georefcnt'] ? number_format($statsArr['georefcnt']) : 0) . ($georefPerc ? " (" . ($georefPerc > 1 ? round($georefPerc) : round($georefPerc, 2)) . "%)" : '') . ' ' . (isset($LANG['GEOREFERENCED']) ? $LANG['GEOREFERENCED'] : 'georeferenced'); ?></li>
						<?php
						$extrastatsArr = array();
						if ($statsArr['dynamicProperties']) $extrastatsArr = json_decode($statsArr['dynamicProperties'], true);
						if ($extrastatsArr) {
							if ($extrastatsArr['imgcnt']) {
								$imgSpecCnt = $extrastatsArr['imgcnt'];
								$imgCnt = 0;
								if (strpos($imgSpecCnt, ':')) {
									$imgCntArr = explode(':', $imgSpecCnt);
									$imgCnt = $imgCntArr[0];
									$imgSpecCnt = $imgCntArr[1];
								}
								if ($imgSpecCnt) {
									$imgPerc = 0;
									if ($statsArr['recordcnt'] && $statsArr['recordcnt'] !== 0){
										$imgPerc = (100 * ($imgSpecCnt / $statsArr['recordcnt']));
									}
									else if ($statsArr['recordcnt'] === 0){
										throw new Exception("Division by zero error.");
									}
									echo '<li>';
									echo number_format($imgSpecCnt) . ($imgPerc ? " (" . ($imgPerc > 1 ? round($imgPerc) : round($imgPerc, 2)) . "%)" : '') . ' ' . $LANG['WITH_IMAGES'];
									if ($imgCnt) echo ' (' . number_format($imgCnt) . ' ' . $LANG['TOTAL_IMAGES'] . ')';
									echo '</li>';
								}
							}
							$genRefStr = '';
							if (isset($extrastatsArr['gencnt']) && $extrastatsArr['gencnt']) $genRefStr = number_format($extrastatsArr['gencnt']) . ' ' . $LANG['GENBANK_REF']  . ', ';
							if (isset($extrastatsArr['boldcnt']) && $extrastatsArr['boldcnt']) $genRefStr .= number_format($extrastatsArr['boldcnt']) . ' ' . $LANG['BOLD_REF']  . ', ';
							if (isset($extrastatsArr['geneticcnt']) && $extrastatsArr['geneticcnt']) $genRefStr .= number_format($extrastatsArr['geneticcnt']) . ' ' . $LANG['OTHER_GENETIC_REF'];
							if ($genRefStr) echo '<li>' . trim($genRefStr, ' ,') . ' ' . $LANG['GENETIC_REF'] . '</li>';
							if (isset($extrastatsArr['refcnt']) && $extrastatsArr['refcnt']) echo '<li>' . number_format($extrastatsArr['refcnt']) . ' ' . $LANG['PUB_REFS'] . '</li>';
							if (isset($extrastatsArr['SpecimensCountID']) && $extrastatsArr['SpecimensCountID'] && $statsArr['recordcnt'] !== 0) {
								$spidPerc = (100 * ($extrastatsArr['SpecimensCountID'] / $statsArr['recordcnt']));
								echo '<li>' . number_format($extrastatsArr['SpecimensCountID']) . ($spidPerc ? " (" . ($spidPerc > 1 ? round($spidPerc) : round($spidPerc, 2)) . "%)" : '') . ' ' . $LANG['IDED_TO_SPECIES'] . '</li>';
							}
							else if ($statsArr['recordcnt'] === 0){
								throw new Exception("Division by zero error.");
							}
						}
						if (isset($statsArr['familycnt']) && $statsArr['familycnt']) echo '<li>' . number_format($statsArr['familycnt']) . ' ' . $LANG['FAMILIES'] . '</li>';
						if (isset($statsArr['genuscnt']) && $statsArr['genuscnt']) echo '<li>' . number_format($statsArr['genuscnt']) . ' ' . $LANG['GENERA'] . '</li>';
						if (isset($statsArr['speciescnt']) && $statsArr['speciescnt']) echo '<li>' . number_format($statsArr['speciescnt']) . ' ' . $LANG['SPECIES'] . '</li>';
						if ($extrastatsArr && $extrastatsArr['TotalTaxaCount']) echo '<li>' . number_format($extrastatsArr['TotalTaxaCount']) . ' ' . $LANG['TOTAL_TAXA'] . '</li>';
						//if($extrastatsArr&&$extrastatsArr['TypeCount']) echo '<li>'.number_format($extrastatsArr['TypeCount']) . ' ' . $LANG['TYPE_SPECIMENS'] . '</li>';
						?>
					</ul>
				</div>
			</section>
			<section class="fieldset-like no-left-margin">
				<h2><span><?= $LANG['EXTRA_STATS'] ?></span></h2>
				<div style="margin:3px;">
					<a href="collprofiles.php?collid=<?= $collid ?>&stat=geography#geographystats"><?= $LANG['SHOW_GEOG_DIST'] ?></a>
				</div>
				<div style="margin:3px;">
					<a href="collprofiles.php?collid=<?= $collid ?>&stat=taxonomy#taxonomystats"><?= $LANG['SHOW_FAMILY_DIST'] ?></a>
				</div>
			</section>
			<div class="accordions" style="margin-bottom: 1.5rem;">
				<section>
					<input type="checkbox" id="more-details" class="accordion-selector" />
					<label for="more-details" class="accordion-header"><?= $LANG['MORE_INFO'] ?></label>
					<div id="collection-type" class="content">
						<div class="bottom-breathing-room-rel">
							<span class="label"><?= $LANG['COLLECTION_TYPE'] ?>:</span> <?= $collData['colltype'] ?>
						</div>
						<div class="bottom-breathing-room-rel">
							<span class="label"><?= $LANG['MANAGEMENT'] ?>:</span>
							<?php
							if($collData['managementtype'] == 'Live Data'){
								echo $LANG['LIVE_DATA'];
							}
							else{
								if($collData['managementtype'] == 'Aggregate'){
									echo $LANG['DATA_AGGREGATE'];
								}
								else{
									echo $LANG['DATA_SNAPSHOT'];
								}
							}
							?>
						</div>
						<div class="bottom-breathing-room-rel">
							<span class="label"><?= $LANG['LAST_UPDATE'] ?>:</span>
							<?= $collData['uploaddate'] ?>
						</div>
						<?php
						if($collData['managementtype'] == 'Live Data'){
							?>
							<div class="bottom-breathing-room-rel">
								<span class="label"><?= $LANG['GLOBAL_UNIQUE_ID'] ?>:</span> <?= $collData['recordid'] ?>
							</div>
							<?php
						}
						if($collData['dwcaurl']){
							?>
							<div class="bottom-breathing-room-rel">
							<a href="<?= $collData['dwcaurl'] ?>"><?= $LANG['DWCA_PUB'] ?></a>
							</div>
							<?php
						}
						?>
						<div class="bottom-breathing-room-rel">
							<span class="label"><?= $LANG['DIGITAL_METADATA'] ?>:</span>
							<a href="../datasets/emlhandler.php?collid=<?= $collData['collid'] ?>" target="_blank">EML File</a>
						</div>
						<?php
						if($collData['managementtype'] == 'Live Data'){
							if($GLOBALS['SYMB_UID']){
								?>
								<div class="bottom-breathing-room-rel">
									<span class="label"><?= $LANG['LIVE_DOWNLOAD'] ?>:</span>
									<a href="../../webservices/dwc/dwcapubhandler.php?collid=<?= $collData['collid'] ?>"><?= $LANG['FULL_DATA'] ?></a>
								</div>
								<?php
							}
						}
						elseif($collData['managementtype'] == 'Snapshot'){
							if($pathArr = $collManager->getDwcaPath($collid)){
								?>
								<div class="bottom-breathing-room-rel">
									<span class="label"><?= $LANG['IPT_SOURCE'] ?>:</span>
									<?php
									$delimiter = '';
									foreach($pathArr as $titleStr => $pathStr){
										echo $delimiter . '<a href="' . htmlspecialchars($pathStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($titleStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
										$delimiter = '; ';
									}
									?>
								</div>
								<?php
							}
						}
						if($collData['rights']){
							$rightsHtml = UtilityFunctions::getRightsHtml($collData['rights']);
							?>
							<div class="bottom-breathing-room-rel">
								<span class="label"><?= $LANG['USAGE_RIGHTS'] ?>:</span>
								<?= $rightsHtml ?>
							</div>
							<?php
						}
						elseif(file_exists('../../includes/usagepolicy.php')){
							?>
							<div class="bottom-breathing-room-rel">
								<a href="../../includes/usagepolicy.php" target="_blank"><?= $LANG['USAGE_POLICY']?></a>
							</div>
							<?php
						}
						if($collData['rightsholder']){
							?>
							<div class="bottom-breathing-room-rel">
								<span class="label"><?= $LANG['RIGHTS_HOLDER'] ?>:</span>
								<?= $collData['rightsholder'] ?>
							</div>
							<?php
						}
						if($collData['accessrights']){
							?>
							<div class="bottom-breathing-room-rel">
								<span class="label"><?= $LANG['ACCESS_RIGHTS'] ?>:</span> <?= $collData['accessrights'] ?>
							</div>
							<?php
						}
						?>
					</div>
				</section>
			</div>
			<?php
			include('collprofilestats.php');
			?>
			<div style="margin-bottom: 2rem;">
				<form name="coll-search-form" action="<?= $actionPage ?>" method="get">
					<input name="db" value="<?= $collid ?>" type="hidden">
					<button type="submit" class="button button-primary">
						<?= $LANG['ADVANCED_SEARCH_THIS_COLLECTION'] ?>
					</button>
				</form>
			</div>
			<div>
				<form name="image-search-form" action="<?= $CLIENT_ROOT ?>/imagelib/search.php" method="get">
					<input name="db" value="<?= $collid ?>" type="hidden">
					<input name="imagetype" value="1" type="hidden">
					<button name="submitaction" type="submit" value="search" class="button button-primary">
						<?= $LANG['IMAGE_SEARCH_THIS_COLLECTION'] ?>
					</button>
				</form>
			</div>
			<?php
		} elseif($collectionData) {
			?>
			<h2><?= $DEFAULT_TITLE . ' ' . $LANG['COLLECTION_PROJECTS']  ?></h2>
			<div>
				<a href="../datasets/rsshandler.php" target="_blank" rel="noopener noreferrer"><?= $LANG['RSS_FEED'] ?></a>
				<hr />
			</div>
			<div class="gridlike-form">
				<?php
				foreach ($collectionData as $cid => $collArr) {
					$collManager->setCollid($cid);
					$cid = htmlspecialchars($cid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
					?>
					<section class="bottom-breathing-room gridlike-form-row">
						<div class="gridlike-form">
							<?php
							$iconStr = $collArr['icon'];
							if ($iconStr) {
								if (substr($iconStr, 0, 6) == 'images') $iconStr = '../../' . $iconStr;
								?>
								<div class="justify-center">
									<img src='<?= $iconStr ?>' class="col-profile-img" alt="icon for collection" /><br />
								</div>
								<?php
							} else{ // placeholder for missing icon
								?>
								<div class="justify-center">
									<p class="col-profile-img"></p><br />
								</div>
								<?php
							}
							?>
							<div class="gridlike-form-row col-profile-inst-code justify-center">
								<p>
									<?= $collArr['institutioncode'] . ($collArr['collectioncode'] ? '-' . $collArr['collectioncode'] : '') ?>
								</p>
							</div>
						</div>
						<div>
							<h3>
								<a class="col-profile-header" href='collprofiles.php?collid=<?= $cid ?>'>
									<?php echo $collArr['collectionname']; ?>
								</a>
							</h3>
							<div style='margin:10px;'>
								<div class="coll-description bottom-breathing-room-rel"><?= $collData['fulldescription'] ?></div>
								<?php
								if(isset($collData['resourcejson'])){
									if($resourceArr = json_decode($collData['resourcejson'], true)){
										$title = $LANG['HOMEPAGE'];
										foreach($resourceArr as $rArr){
											if(!empty($rArr['title'][$LANG_TAG])) $title = $rArr['title'][$LANG_TAG];
											?>
											<div class="field-div">
												<a href="<?= htmlspecialchars($rArr['url'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>" target="_blank"><?= $title ?></a>
											</div>
											<?php
										}
									}
								}
								if(!empty($collData['contactjson'])){
									if($contactArr = json_decode($collData['contactjson'], true)){
										if(!empty($contactArr)){
											?>
											<section style="margin-left: 0;">
												<h1 style="font: 1.5rem normal;"><span><?= $LANG['CONTACT'] ?>: </span></h1>
												<ul>
													<?php
													foreach($contactArr as $cArr){
														?>
														<li>
															<div class="field-div">
																<?php
																if(!empty($cArr['role'])){
																	echo '<span class="label">' . $cArr['role'] . ': </span>';
																}
																echo $cArr['firstName'].' '.$cArr['lastName'];
																if(!empty($cArr['email'])) echo ', ' . $cArr['email'];
																if(!empty($cArr['phone'])) echo ', ' . $cArr['phone'];
																if(!empty($cArr['orcid'])) echo ' (ORCID #: <a href="https://orcid.org/' . $cArr['orcid'] . '" target="_blank">'. $cArr['orcid'] . '</a>)';
																?>
															</div>
														</li>
														<?php
													}
													?>
												</ul>
											</section>
											<?php
										}
									}
								}
								?>
							</div>
							<div style='margin:5px 0px 15px 10px;'>
								<a href='collprofiles.php?collid=<?= $cid ?>'><?= $LANG['MORE_INFO'] ?></a>
							</div>
						</div>
					</section>
					<hr class="test" />
					<?php
				}
				?>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
	<script>
		const showDialogLink = document.getElementById('q_catalognumberinfo');
		const closeDialogButton = document.getElementById('closeDialog');
		const dialogEl = document.getElementById('dialogEl');
		const dialogContainer = document.getElementById('dialogContainer');

		showDialogLink.addEventListener('click', (e) => {
			e.preventDefault();
			dialogEl.showModal();

			dialogContainer.style.position = 'relative';
			dialogContainer.appendChild(dialogEl);

		});

		closeDialogButton.addEventListener('click', (e) => {
			e.preventDefault();
			dialogEl.close();
		});
	</script>
</body>
</html>
