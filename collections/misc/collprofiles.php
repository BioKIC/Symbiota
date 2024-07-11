<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/content/lang/collections/misc/collprofiles.' . $LANG_TAG . '.php');
include_once($SERVER_ROOT . '/classes/OccurrenceCollectionProfile.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
header('Content-Type: text/html; charset=' . $CHARSET);
unset($_SESSION['editorquery']);

$collManager = new OccurrenceCollectionProfile();

$collid = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$occIndex = array_key_exists('occindex',$_REQUEST)?$_REQUEST['occindex']:0;
$SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT = $SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT ?? false;
$SHOULD_USE_HARVESTPARAMS = $SHOULD_USE_HARVESTPARAMS ?? false;
$actionPage = $SHOULD_USE_HARVESTPARAMS ? ($CLIENT_ROOT . "/collections/harvestparams.php") : ($CLIENT_ROOT . "/collections/search/index.php");

$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : '';
$eMode = array_key_exists('emode', $_REQUEST) ? $collManager->sanitizeInt($_REQUEST['emode']) : 0;

if ($eMode && !$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/misc/collprofiles.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collManager->setCollid($collid);

$collData = $collManager->getCollectionMetadata();
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
	<title><?php echo $DEFAULT_TITLE . ' ' . ($collid && isset($collData[$collid])? $collData[$collid]['collectionname'] : ''); ?></title>
	<meta name="keywords" content="Natural history collections,<?php echo ($collid && array_key_exists($collid, $collData) ? $collData[$collid]['collectionname'] : ''); ?>" />
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

		@media (min-width: 1880px) {
			#quicksearch-box {
				margin: 0 -26rem 0 0;
				width: 25rem;
				top: 1rem;
				float: right;
				position:sticky;
			}
		}
		@media (max-width: 1880px) {
			#quicksearch-box {
				width:100%;
				margin: 1rem 0;
			}
			#quicksearch-btn-container {
				justify-content: right
			}
		}
	</style>
	<link href="<?php echo $CLIENT_ROOT ?>/collections/search/css/searchStyles.css?ver=1" type="text/css" rel="stylesheet" />
	<link href="<?php echo $CLIENT_ROOT ?>/collections/search/css/searchStylesInner.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_collprofilesMenu) ? $collections_misc_collprofilesMenu : true);
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php"><?php echo htmlspecialchars((isset($LANG['HOME']) ? $LANG['HOME'] : 'Home'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<a href="../index.php"><?php echo htmlspecialchars((isset($LANG['COLLECTION_SEARCH']) ? $LANG['COLLECTION_SEARCH'] : 'Collection Search Page'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<b><?php echo (isset($LANG['COLL_PROFILE']) ? $LANG['COLL_PROFILE'] : 'Collection Profile'); ?></b>
	</div>
	<div role="main" id="innertext" style="padding-top:0">
		<?php if ($collid && !$collid == 0){
		?>
			<section id="quicksearch-box" class="fieldset-like" >
				<h3><span><?php echo (isset($LANG['QUICK_SEARCH']) ? $LANG['QUICK_SEARCH'] : 'Quick Search'); ?></span></h3>
				<div id="dialogContainer" style="position: relative;">
					<form name="quicksearch" style="display: flex; align-items:center; gap:0.5rem; flex-wrap: wrap" action="javascript:void(0);" onsubmit="directSubmitAction(event)">
						<div>
						<label for="catalog-number"><?php echo (isset($LANG['OCCURENCE_IDENTIFIER']) ? $LANG['OCCURENCE_IDENTIFIER'] : 'Catalog Number'); ?></label>
						<span class="screen-reader-only">
							<?php
								echo (isset($LANG['IDENTIFIER_PLACEHOLDER_LIST']) ? $LANG['IDENTIFIER_PLACEHOLDER_LIST'] : 'Search by Catalog Number, Occurrence ID, or Record ID.') . ' ';
							?>
						</span>
						<input style="margin-bottom: 0" name="catalog-number" id="catalog-number" type="text" />
						<a href="#" id="q_catalognumberinfo" style="text-decoration:none;">
							<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo $LANG['MORE_INFO_ALT']; ?>" title="<?php echo $LANG['MORE_INFO']; ?>" aria-label="<?php echo $LANG['MORE_INFO']; ?>"/>
						</a>
						<dialog id="dialogEl" aria-live="polite" aria-label="Catalog number search dialog">
							<?php
								echo (isset($LANG['IDENTIFIER_PLACEHOLDER_LIST']) ? $LANG['IDENTIFIER_PLACEHOLDER_LIST'] : 'Search by Catalog Number, Occurrence ID, or Record ID.') . ' ';
							?>
							<button id="closeDialog">Close</button>
						</dialog>
						</div>
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="occindex" type="hidden" value="0" />
						<div>
						<label for="taxon-search"><?php echo (isset($LANG['TAXON']) ? $LANG['TAXON'] : 'Taxon'); ?></label>
						<input style="margin-bottom: 0" name="taxon-search" id="taxon-search" type="text" />
						</div>
						<div id="quicksearch-btn-container" style="display:flex; gap: 0.5rem; flex-grow:1">
							<?php
								if($editCode == 1 || $editCode == 2 || $editCode == 3){
							?>
								<button type="submit" id="search-by-catalog-number-admin-btn" value="edit"> 
									<?php echo (isset($LANG['OCCURRENCE_EDITOR']) ? $LANG['OCCURRENCE_EDITOR'] : 'Edit'); ?>
								</button>
							<?php
								}
							?>
							<button type="submit" value='search' id="search-by-catalog-number-btn" title="<?php echo (isset($LANG['IDENTIFIER_PLACEHOLDER_LIST']) ? $LANG['IDENTIFIER_PLACEHOLDER_LIST'] : 'Occurrence ID and Record ID also accepted.'); ?>">
								<?php echo (isset($LANG['SEARCH']) ? $LANG['SEARCH'] : 'Search'); ?>
							</button>
						</div>
					</form>
				</div>
			</section>
		<?php	
		} 
		?>
		
		<?php
		if ($editCode > 1) {
			if ($action == 'UpdateStatistics') {
				echo '<h2> ' . (isset($LANG['UPDATE_STATISTICS']) ? $LANG['UPDATE_STATISTICS'] : 'Updating statistics related to this collection...') . '</h2>';
				$collManager->updateStatistics(true);
				echo '<hr/>';
			}
		}

		if ($collid && isset($collData[$collid])) {
			$collData = $collData[$collid];
			$codeStr = ' (' . $collData['institutioncode'];
			if ($collData['collectioncode']) $codeStr .= '-' . $collData['collectioncode'];
			$codeStr .= ')';
			$_SESSION['colldata'] = $collData;
			echo '<h1 class="page-heading">' . $LANG['COLL_PROF_FOR'] . ': ' . $collData['collectionname'] . $codeStr . '</h1>';
			// GBIF citations widget
			if ($datasetKey) {
				echo '<div style="margin-left: 10px; margin-bottom: 20px;">';
				echo '<iframe title="GBIF citation" src="https://www.gbif.org/api/widgets/literature/button?gbifDatasetKey=' . $datasetKey . '" frameborder="0" allowtransparency="true" style="width: 140px; height: 24px;"></iframe>';
				// Check if the Bionomia badge has been created yet - typically lags ~2 weeks behind GBIF publication
				$bionomiaUrl = 'https://api.bionomia.net/dataset/' . $datasetKey . '/badge.svg';
				$ch = curl_init($bionomiaUrl);
				curl_setopt($ch, CURLOPT_NOBODY, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_exec($ch);
				$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				// Check the response code - display image if exists
				if ($responseCode === 200) {
    				echo '<a href="https://bionomia.net/dataset/' . $datasetKey . '"><img src="' . $bionomiaUrl . '" alt="Bionomia dataset badge" style="width:262px; height:24px; padding-left:10px;"></a>';
				}
				echo '</div>';
			}

			if ($editCode) {
				?>
				<button style="margin-bottom: 0.5rem" type="button" onclick="toggleById('controlpanel');" >
					<?= $LANG['TOGGLE_MAN'] ?>
				</button>
				<div id="controlpanel" style="display:<?php echo ($eMode ? 'block' : 'none'); ?>;">
					<section class="fieldset-like no-left-margin">
						<h2><span><?php echo (isset($LANG['DAT_EDIT']) ? $LANG['DAT_EDIT'] : 'Data Editor Control Panel'); ?></span></h2>
						<ul>
							<?php
							if (stripos($collData['colltype'], 'observation') !== false) {
								?>
								<li>
									<a href="../editor/observationsubmit.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo (isset($LANG['SUBMIT_IMAGE_V']) ? $LANG['SUBMIT_IMAGE_V'] : 'Submit an Image Voucher (observation supported by a photo)'); ?>
									</a>
								</li>
								<?php
							}
							?>
							<li>
								<a href="../editor/occurrenceeditor.php?gotomode=1&collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
									<?php echo htmlspecialchars((isset($LANG['ADD_NEW_OCCUR']) ? $LANG['ADD_NEW_OCCUR'] : 'Add New Occurrence Record'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
								</a>
							</li>
							<?php
							if ($collData['colltype'] == 'Preserved Specimens') {
								?>
								<li style="margin-left:10px">
									<a href="../editor/imageoccursubmit.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['CREATE_NEW_REC']) ? $LANG['CREATE_NEW_REC'] : 'Create New Records Using Image'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								<li style="margin-left:10px">
									<a href="../editor/skeletalsubmit.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['SKELETAL']) ? $LANG['SKELETAL'] : 'Add Skeletal Records'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								<?php
							}
							?>
							<li>
								<a href="../editor/occurrencetabledisplay.php?displayquery=1&collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
									<?php echo htmlspecialchars((isset($LANG['EDIT_EXISTING']) ? $LANG['EDIT_EXISTING'] : 'Edit Existing Occurrence Records'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
								</a>
							</li>
							<li>
								<a href="../editor/batchdeterminations.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
									<?php echo htmlspecialchars((isset($LANG['ADD_BATCH_DETER']) ? $LANG['ADD_BATCH_DETER'] : 'Add Batch Determinations/Nomenclatural Adjustments'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
								</a>
							</li>
							<li>
								<a href="../reports/labelmanager.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
									<?php echo htmlspecialchars((isset($LANG['PRINT_LABELS']) ? $LANG['PRINT_LABELS'] : 'Print Specimen Labels'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
								</a>
							</li>
							<li>
								<a href="../reports/annotationmanager.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
									<?php echo htmlspecialchars((isset($LANG['PRINT_ANNOTATIONS']) ? $LANG['PRINT_ANNOTATIONS'] : 'Print Annotations Labels'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
								</a>
							</li>
							<?php
							if ($collManager->traitCodingActivated()) {
								?>
								<li>
									<a href="#" onclick="$('li.traitItem').show(); return false;">
										<?php echo (isset($LANG['TRAIT_CODING_TOOLS']) ? $LANG['TRAIT_CODING_TOOLS'] : 'Occurrence Trait Coding Tools'); ?>
									</a>
								</li>
								<li class="traitItem" style="margin-left:10px;display:none;">
									<a href="../traitattr/occurattributes.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['TRAIT_CODING']) ? $LANG['TRAIT_CODING'] : 'Trait Coding from Images'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								<li class="traitItem" style="margin-left:10px;display:none;">
									<a href="../traitattr/attributemining.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['TRAIT_MINING']) ? $LANG['TRAIT_MINING'] : 'Trait Mining from Verbatim Text'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								<?php
							}
							?>
							<li>
								<a href="../georef/batchgeoreftool.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
									<?php echo htmlspecialchars((isset($LANG['BATCH_GEOREF']) ? $LANG['BATCH_GEOREF'] : 'Batch Georeference Specimens'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
								</a>
							</li>
							<?php
							if ($collData['colltype'] == 'Preserved Specimens') {
								?>
								<li>
									<a href="../loans/index.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['LOAN_MANAGEMENT']) ? $LANG['LOAN_MANAGEMENT'] : 'Loan Management'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								<?php
							}
							?>
						</ul>
					</section>
					<?php
					if ($editCode > 1) {
						?>
						<section class="fieldset-like no-left-margin">
							<h2><span><?php echo (isset($LANG['ADMIN_CONTROL']) ? $LANG['ADMIN_CONTROL'] : 'Administration Control Panel'); ?></span></h2>
							<ul>
								<li>
									<a href="commentlist.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['VIEW_COMMENTS']) ? $LANG['VIEW_COMMENTS'] : 'View Posted Comments'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
									<?php if ($commCnt = $collManager->unreviewedCommentsExist()) echo '- <span style="color:orange">' . $commCnt . ' ' . (isset($LANG['UNREVIEWED_COMMENTS']) ? $LANG['UNREVIEWED_COMMENTS'] : 'unreviewed comments') . '</span>'; ?>
								</li>
								<li>
									<a href="collmetadata.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['EDIT_META']) ? $LANG['EDIT_META'] : 'Edit Metadata'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								<!--
								<li>
									<a href="" onclick="$('li.metadataItem').show(); return false;"  >
										<?php echo htmlspecialchars((isset($LANG['OPEN_META']) ? $LANG['OPEN_META'] : 'Open Metadata'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								<li class="metadataItem" style="margin-left:10px;display:none;">
									<a href="collmetadata.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['EDIT_META']) ? $LANG['EDIT_META'] : 'Edit Metadata'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								<li class="metadataItem" style="margin-left:10px;display:none;">
									<a href="colladdress.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['EDIT_ADDRESS']) ? $LANG['EDIT_ADDRESS'] : 'Edit Mailing Address'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								<li class="metadataItem" style="margin-left:10px;display:none;">
									<a href="collproperties.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['EDIT_COLL_PROPS']) ? $LANG['EDIT_COLL_PROPS'] : 'Special Properties'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								 -->
								<li>
									<a href="collpermissions.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['MANAGE_PERMISSIONS']) ? $LANG['MANAGE_PERMISSIONS'] : 'Manage Permissions'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								<li>
									<a href="#" onclick="$('li.importItem').show(); return false;">
										<?php echo (isset($LANG['IMPORT_SPECIMEN']) ? $LANG['IMPORT_SPECIMEN'] : 'Import/Update Specimen Records'); ?>
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
								<li class="importItem">
									<a href="../admin/specupload.php?uploadtype=9&collid=<?php echo $collid; ?>">
										<?= $LANG['NFN_IMPORT'] ?>
									</a>
								</li>
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
											<a href="../specprocessor/index.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
												<?php echo htmlspecialchars((isset($LANG['PROCESSING_TOOLBOX']) ? $LANG['PROCESSING_TOOLBOX'] : 'Processing Toolbox'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
											</a>
										</li>
										<li>
											<a href="../datasets/datapublisher.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
												<?php echo htmlspecialchars((isset($LANG['DARWIN_CORE_PUB']) ? $LANG['DARWIN_CORE_PUB'] : 'Darwin Core Archive Publishing'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
											</a>
										</li>
										<?php
									}
									?>
									<li>
										<a href="../editor/editreviewer.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
											<?php echo htmlspecialchars((isset($LANG['REVIEW_SPEC_EDITS']) ? $LANG['REVIEW_SPEC_EDITS'] : 'Review/Verify Occurrence Edits'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
										</a>
									</li>
									<!--
									<li>
										<a href="../reports/accessreport.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
											<?php echo htmlspecialchars((isset($LANG['ACCESS_REPORT']) ? $LANG['ACCESS_REPORT'] : 'View Access Statistics'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
										</a>
									</li>
									 -->
									<?php
								}
								if (!empty($ACTIVATE_DUPLICATES)) {
									?>
									<li>
										<a href="../datasets/duplicatemanager.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
											<?php echo htmlspecialchars((isset($LANG['DUP_CLUSTER']) ? $LANG['DUP_CLUSTER'] : 'Duplicate Clustering'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
										</a>
									</li>
									<?php
								}
								?>
								<li>
									<?php echo (isset($LANG['MAINTENANCE_TASKS']) ? $LANG['MAINTENANCE_TASKS'] : 'General Maintenance Tasks'); ?>
								</li>
								<?php
								if ($collData['colltype'] != 'General Observations') {
									?>
									<li style="margin-left:10px;">
										<a href="../cleaning/index.php?obsuid=0&collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
											<?php echo htmlspecialchars((isset($LANG['DATA_CLEANING']) ? $LANG['DATA_CLEANING'] : 'Data Cleaning Tools'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
										</a>
									</li>
									<?php
								}
								?>
								<li style="margin-left:10px;">
									<a href="#" onclick="newWindow = window.open('collbackup.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>','bucollid','scrollbars=1,toolbar=0,resizable=1,width=600,height=250,left=20,top=20');">
										<?php echo htmlspecialchars((isset($LANG['BACKUP_DATA_FILE']) ? $LANG['BACKUP_DATA_FILE'] : 'Download Backup Data File'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								<?php
								if ($collData['managementtype'] == 'Live Data') {
									?>
									<li style="margin-left:10px;">
										<a href="../admin/restorebackup.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
											<?php echo htmlspecialchars((isset($LANG['RESTORE_BACKUP']) ? $LANG['RESTORE_BACKUP'] : 'Restore Backup File'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
										</a>
									</li>
									<?php
								}
								?>
								<!--
								<li style="margin-left:10px;">
									<a href="../../imagelib/admin/igsnmapper.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['GUID_MANAGEMENT']) ? $LANG['GUID_MANAGEMENT'] : 'IGSN GUID Management'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								 -->
								<li style="margin-left:10px;">
									<a href="../../imagelib/admin/thumbnailbuilder.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<?php echo htmlspecialchars((isset($LANG['THUMBNAIL_MAINTENANCE']) ? $LANG['THUMBNAIL_MAINTENANCE'] : 'Thumbnail Maintenance'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</a>
								</li>
								<li style="margin-left:10px;">
									<a href="collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&action=UpdateStatistics">
										<?php echo htmlspecialchars((isset($LANG['UPDATE_STATS']) ? $LANG['UPDATE_STATS'] : 'Update Statistics'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
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
				<?php
				echo $collManager->getVisibleMetadataHtml($LANG, $LANG_TAG);
				if ($collData['publishtogbif'] && $datasetKey) {
					$dataUrl = 'http://www.gbif.org/dataset/' . $datasetKey;
					?>
					<div style="margin-top:5px;">
						<div><b><?php echo (isset($LANG['GBIF_DATASET']) ? $LANG['GBIF_DATASET'] : 'GBIF Dataset page'); ?>:</b> <a href="<?php echo htmlspecialchars($dataUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($dataUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></div>
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
							<div><b><?php echo (isset($LANG['IDIGBIO_DATASET']) ? $LANG['IDIGBIO_DATASET'] : 'iDigBio Dataset page'); ?>:</b> <a href="<?php echo htmlspecialchars($dataUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($dataUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></div>
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
							if ($editCode > 1) echo ' <a href="institutioneditor.php?emode=1&targetcollid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&iid=' . htmlspecialchars($addrArr['iid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" title="' . htmlspecialchars((isset($LANG['EDIT_INST']) ? $LANG['EDIT_INST'] : 'Edit institution information'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><img src="../../images/edit.png" style="width:1.3em;" alt="edit icon" /></a>';
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
					<h2><span><?php echo (isset($LANG['COLL_STATISTICS']) ? $LANG['COLL_STATISTICS'] : 'Collection Statistics'); ?></span></h2>
					<div style="clear:both;margin-top:5px;">
						<ul style="margin-top:5px;">
							<li><?php echo number_format($statsArr["recordcnt"]) . ' ' . (isset($LANG['SPECIMEN_RECORDS']) ? $LANG['SPECIMEN_RECORDS'] : 'specimen records'); ?></li>
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
										echo number_format($imgSpecCnt) . ($imgPerc ? " (" . ($imgPerc > 1 ? round($imgPerc) : round($imgPerc, 2)) . "%)" : '') . ' ' . (isset($LANG['WITH_IMAGES']) ? $LANG['WITH_IMAGES'] : 'with images');
										if ($imgCnt) echo ' (' . number_format($imgCnt) . ' ' . (isset($LANG['TOTAL_IMAGES']) ? $LANG['TOTAL_IMAGES'] : 'total images') . ')';
										echo '</li>';
									}
								}
								$genRefStr = '';
								if (isset($extrastatsArr['gencnt']) && $extrastatsArr['gencnt']) $genRefStr = number_format($extrastatsArr['gencnt']) . ' ' . (isset($LANG['GENBANK_REF']) ? $LANG['GENBANK_REF'] : 'GenBank') . ', ';
								if (isset($extrastatsArr['boldcnt']) && $extrastatsArr['boldcnt']) $genRefStr .= number_format($extrastatsArr['boldcnt']) . ' ' . (isset($LANG['BOLD_REF']) ? $LANG['BOLD_REF'] : 'BOLD') . ', ';
								if (isset($extrastatsArr['geneticcnt']) && $extrastatsArr['geneticcnt']) $genRefStr .= number_format($extrastatsArr['geneticcnt']) . ' ' . (isset($LANG['OTHER_GENETIC_REF']) ? $LANG['OTHER_GENETIC_REF'] : 'other');
								if ($genRefStr) echo '<li>' . trim($genRefStr, ' ,') . ' ' . (isset($LANG['GENETIC_REF']) ? $LANG['GENETIC_REF'] : 'genetic references') . '</li>';
								if (isset($extrastatsArr['refcnt']) && $extrastatsArr['refcnt']) echo '<li>' . number_format($extrastatsArr['refcnt']) . ' ' . (isset($LANG['PUB_REFS']) ? $LANG['PUB_REFS'] : 'publication references') . '</li>';
								if (isset($extrastatsArr['SpecimensCountID']) && $extrastatsArr['SpecimensCountID'] && $statsArr['recordcnt'] !== 0) {
									$spidPerc = (100 * ($extrastatsArr['SpecimensCountID'] / $statsArr['recordcnt']));
									echo '<li>' . number_format($extrastatsArr['SpecimensCountID']) . ($spidPerc ? " (" . ($spidPerc > 1 ? round($spidPerc) : round($spidPerc, 2)) . "%)" : '') . ' ' . (isset($LANG['IDED_TO_SPECIES']) ? $LANG['IDED_TO_SPECIES'] : 'identified to species') . '</li>';
								}
								else if ($statsArr['recordcnt'] === 0){
									throw new Exception("Division by zero error.");
								}
							}
							if (isset($statsArr['familycnt']) && $statsArr['familycnt']) echo '<li>' . number_format($statsArr['familycnt']) . ' ' . (isset($LANG['FAMILIES']) ? $LANG['FAMILIES'] : 'families') . '</li>';
							if (isset($statsArr['genuscnt']) && $statsArr['genuscnt']) echo '<li>' . number_format($statsArr['genuscnt']) . ' ' . (isset($LANG['GENERA']) ? $LANG['GENERA'] : 'genera') . '</li>';
							if (isset($statsArr['speciescnt']) && $statsArr['speciescnt']) echo '<li>' . number_format($statsArr['speciescnt']) . ' ' . (isset($LANG['SPECIES']) ? $LANG['SPECIES'] : 'species') . '</li>';
							if ($extrastatsArr && $extrastatsArr['TotalTaxaCount']) echo '<li>' . number_format($extrastatsArr['TotalTaxaCount']) . ' ' . (isset($LANG['TOTAL_TAXA']) ? $LANG['TOTAL_TAXA'] : 'total taxa (including subsp. and var.)') . '</li>';
							//if($extrastatsArr&&$extrastatsArr['TypeCount']) echo '<li>'.number_format($extrastatsArr['TypeCount']).' '.(isset($LANG['TYPE_SPECIMENS'])?$LANG['TYPE_SPECIMENS']:'type specimens').'</li>';
							?>
						</ul>
					</div>
				</section>
			<section class="fieldset-like no-left-margin">
				<h2><span><?php echo (isset($LANG['EXTRA_STATS']) ? $LANG['EXTRA_STATS'] : 'Extra Statistics'); ?></span></h2>
				<div style="margin:3px;">
					<a href="collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&stat=geography#geographystats"><?php echo htmlspecialchars((isset($LANG['SHOW_GEOG_DIST']) ? $LANG['SHOW_GEOG_DIST'] : 'Show Geographic Distribution'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
				</div>
				<div style="margin:3px;">
					<a href="collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&stat=taxonomy#taxonomystats"><?php echo htmlspecialchars((isset($LANG['SHOW_FAMILY_DIST']) ? $LANG['SHOW_FAMILY_DIST'] : 'Show Family Distribution'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
				</div>
			</section>
			<?php
			echo $collManager->getAccordionMetadataHtml($LANG, $LANG_TAG);
			include('collprofilestats.php');
			?>
			<div style="margin-bottom: 2rem;">
			<form action="<?php echo $actionPage ?>">
				<input hidden id="'<?php 'coll-' . $collid . '-' ?>'" name="db[]" class="specobs" value='<?php echo $collid ?>' type="checkbox" onclick="selectAll(this);" checked />
				<button type="submit" class="button button-primary">
					<?php echo (isset($LANG['ADVANCED_SEARCH_THIS_COLLECTION'])?$LANG['ADVANCED_SEARCH_THIS_COLLECTION']:'Advanced Search this Collection'); ?>
				</button>
			</form>
			</div>
			<div>
				<span class="button button-primary">
					<a id="image-search" href="<?php echo $CLIENT_ROOT?>/imagelib/search.php?submitaction=search&db[]=<?php echo $collid ?>" ><?php echo (isset($LANG['IMAGE_SEARCH_THIS_COLLECTION'])?$LANG['IMAGE_SEARCH_THIS_COLLECTION']:'Image Search this Collection'); ?></a>
				</span>
			</div>
			<?php
		} elseif($collData) {
			?>
			<h2><?php echo $DEFAULT_TITLE . ' ' . (isset($LANG['COLLECTION_PROJECTS']) ? $LANG['COLLECTION_PROJECTS'] : 'Natural History Collections and Observation Projects'); ?></h2>
			<div>
				<a href="../datasets/rsshandler.php" target="_blank" rel="noopener noreferrer"><?php echo (isset($LANG['RSS_FEED']) ? $LANG['RSS_FEED'] : 'RSS feed'); ?></a>
				<hr />
			</div>
			<div class="gridlike-form">
				<?php
				foreach ($collData as $cid => $collArr) {
					?>
					<section class="bottom-breathing-room gridlike-form-row">
						<div class="gridlike-form">
							<?php
							$iconStr = $collArr['icon'];
							if ($iconStr) {
								if (substr($iconStr, 0, 6) == 'images') $iconStr = '../../' . $iconStr;
								?>
								<div class="justify-center">
									<img src='<?php echo $iconStr; ?>' class="col-profile-img" alt="icon for collection" /><br />
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
										<?php
										echo $collArr['institutioncode'] ?? '';
										if ($collArr['collectioncode']) echo '-' . $collArr['collectioncode'];
										?>
									</p>
								</div>
						</div>
						<div>
							<h3>
								<a class="col-profile-header" href='collprofiles.php?collid=<?php echo htmlspecialchars($cid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>'>
									<?php echo $collArr['collectionname']; ?>
								</a>
							</h3>
							<div style='margin:10px;'>
								<?php
								$collManager->setCollid($cid);
								echo $collManager->getVisibleMetadataHtml($LANG, $LANG_TAG);
								?>
							</div>
							<div style='margin:5px 0px 15px 10px;'>
								<a href='collprofiles.php?collid=<?php echo htmlspecialchars($cid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>'><?php echo htmlspecialchars((isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'More Information'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
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
