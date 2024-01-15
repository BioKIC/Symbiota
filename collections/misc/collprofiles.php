<!DOCTYPE html>
<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/content/lang/collections/misc/collprofiles.' . $LANG_TAG . '.php');
include_once($SERVER_ROOT . '/classes/OccurrenceCollectionProfile.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
header('Content-Type: text/html; charset=' . $CHARSET);
unset($_SESSION['editorquery']);

$collManager = new OccurrenceCollectionProfile();

$collid = isset($_REQUEST['collid']) ? $collManager->sanitizeInt($_REQUEST['collid']) : 0;
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
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . ($collid && isset($collData[$collid])? $collData[$collid]['collectionname'] : ''); ?></title>
	<meta name="keywords" content="Natural history collections,<?php echo ($collid ? $collData[$collid]['collectionname'] : ''); ?>" />
	<link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/jquery-ui.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="../../js/jquery.js?ver=20130917" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js?ver=20130917" type="text/javascript"></script>
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
					redirectUrl = clientRoot + '/collections/editor/occurrenceeditor.php?q_customfield1=sciname&q_customtype1=STARTS&q_customvalue1=' + encodeURIComponent(taxon) + '&collid=' + encodeURIComponent(collId) + '&displayquery=1&occindex=0&reset=1';
				}
				window.location.href = redirectUrl;
			}
		}
	</script>
	<style>
		.importItem { margin-left:10px; display:none; }
		.field-div { margin: 10px 0px; clear: both; }
		.label { font-weight: bold; }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_collprofilesMenu) ? $collections_misc_collprofilesMenu : true);
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php"><?php echo htmlspecialchars((isset($LANG['HOME']) ? $LANG['HOME'] : 'Home'), HTML_SPECIAL_CHARS_FLAGS); ?></a> &gt;&gt;
		<a href="../index.php"><?php echo htmlspecialchars((isset($LANG['COLLECTION_SEARCH']) ? $LANG['COLLECTION_SEARCH'] : 'Collection Search Page'), HTML_SPECIAL_CHARS_FLAGS); ?></a> &gt;&gt;
		<b><?php echo (isset($LANG['COLL_PROFILE']) ? $LANG['COLL_PROFILE'] : 'Collection Profile'); ?></b>
	</div>
	<div id="innertext">
		<section id="quicksearch-box" class="fieldset-like no-left-margin float-rt-no-overlap">
			<h1><span><?php echo (isset($LANG['QUICK_SEARCH']) ? $LANG['QUICK_SEARCH'] : 'Quick Search'); ?></span></h1>
			<div id="dialogContainer" style="position: relative;">
				<form name="quicksearch" action="javascript:void(0);" onsubmit="processEditQuickSearch('<?php echo $CLIENT_ROOT ?>')">
					<label for="catalog-number"><?php echo (isset($LANG['OCCURENCE_IDENTIFIER']) ? $LANG['OCCURENCE_IDENTIFIER'] : 'Catalog Number'); ?></label>
					<span class="skip-link">
						<?php
							echo (isset($LANG['IDENTIFIER_PLACEHOLDER_LIST']) ? $LANG['IDENTIFIER_PLACEHOLDER_LIST'] : 'Search by Catalog Number, Occurrence ID, or Record ID.') . ' ';
						?>
					</span>
					<input name="catalog-number" id="catalog-number" type="text" />
					<a href="#" id="q_catalognumberinfo" style="text-decoration:none;">
						<img src="../../images/info.png" style="width:15px;" alt="<?php echo (isset($LANG['MORE_INFO_ALT']) ? $LANG['MORE_INFO_ALT'] : 'More information about catalog number'); ?>" title="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'More information.'); ?>" aria-label="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'More information.'); ?>"/>
					</a>
					<dialog id="dialogEl" aria-live="polite" aria-label="Catalog number search dialog">
						<?php
							echo (isset($LANG['IDENTIFIER_PLACEHOLDER_LIST']) ? $LANG['IDENTIFIER_PLACEHOLDER_LIST'] : 'Search by Catalog Number, Occurrence ID, or Record ID.') . ' ';
						?>
						<button id="closeDialog">Close</button>
					</dialog>
					<br>
					<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
					<input name="occindex" type="hidden" value="0" />
					<label for="taxon-search"><?php echo (isset($LANG['TAXON']) ? $LANG['TAXON'] : 'Taxon'); ?></label>
					<input name="taxon-search" id="taxon-search" type="text" />
					<br>
					<?php 
						if($editCode == 1 || $editCode == 2 || $editCode == 3){
					?>
						<button type="submit" id="search-by-catalog-number-admin-btn">
							<?php echo (isset($LANG['OCCURRENCE_EDITOR']) ? $LANG['OCCURRENCE_EDITOR'] : 'Edit'); ?>
						</button>
					<?php 
						}
					?>
					
				</form>
				<form name="quicksearch" action="javascript:void(0);" onsubmit="submitAndRedirectSearchForm('<?php echo $CLIENT_ROOT ?>/collections/list.php?db=','&catnum=', '&taxa=', '&includecult=' + <?php echo $SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT ? '1' : '0' ?> + '&includeothercatnum=1', '&includecult=' + <?php echo $SHOULD_INCLUDE_CULTIVATED_AS_DEFAULT ? '1' : '0' ?> + '&usethes=1&taxontype=2 '); return false;">
					<button class="top-breathing-room-rel" type="submit" id="search-by-catalog-number-btn" title="<?php echo (isset($LANG['IDENTIFIER_PLACEHOLDER_LIST']) ? $LANG['IDENTIFIER_PLACEHOLDER_LIST'] : 'Occurrence ID and Record ID also accepted.'); ?>">
						<?php echo (isset($LANG['SEARCH']) ? $LANG['SEARCH'] : 'Search'); ?>
					</button>
				</form>
			</div>
		</section>
		<?php
		if ($editCode > 1) {
			if ($action == 'UpdateStatistics') {
				echo '<h2> ' . (isset($LANG['UPDATE_STATISTICS']) ? $LANG['UPDATE_STATISTICS'] : 'Updating statistics related to this collection...') . '</h2>';
				$collManager->updateStatistics(true);
				echo '<hr/>';
			}
		}
		if ($editCode && $collid) {
			?>
			<div style="float:right;margin:3px;cursor:pointer;" onclick="toggleById('controlpanel');" title="<?php echo (isset($LANG['TOGGLE_MAN']) ? $LANG['TOGGLE_MAN'] : 'Toggle Manager\'s Control Panel'); ?>">
				<img style='border:0px;' src='../../images/edit.png' alt="edit icon" />
			</div>
			<?php
		}
		if ($collid && isset($collData[$collid])) {
			$collData = $collData[$collid];
			$codeStr = ' (' . $collData['institutioncode'];
			if ($collData['collectioncode']) $codeStr .= '-' . $collData['collectioncode'];
			$codeStr .= ')';
			$_SESSION['colldata'] = $collData;
			echo '<h1>' . $collData['collectionname'] . $codeStr . '</h1>';
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
				<div id="controlpanel" style="margin-top: 9rem; display:<?php echo ($eMode ? 'block' : 'none'); ?>;">
					<section class="fieldset-like no-left-margin">
						<h1><span><?php echo (isset($LANG['DAT_EDIT']) ? $LANG['DAT_EDIT'] : 'Data Editor Control Panel'); ?></span></h1>
						<ul>
							<?php
							if (stripos($collData['colltype'], 'observation') !== false) {
								?>
								<li>
									<a href="../editor/observationsubmit.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo (isset($LANG['SUBMIT_IMAGE_V']) ? $LANG['SUBMIT_IMAGE_V'] : 'Submit an Image Voucher (observation supported by a photo)'); ?>
									</a>
								</li>
								<?php
							}
							?>
							<li>
								<a href="../editor/occurrenceeditor.php?gotomode=1&collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
									<?php echo htmlspecialchars((isset($LANG['ADD_NEW_OCCUR']) ? $LANG['ADD_NEW_OCCUR'] : 'Add New Occurrence Record'), HTML_SPECIAL_CHARS_FLAGS); ?>
								</a>
							</li>
							<?php
							if ($collData['colltype'] == 'Preserved Specimens') {
								?>
								<li style="margin-left:10px">
									<a href="../editor/imageoccursubmit.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['CREATE_NEW_REC']) ? $LANG['CREATE_NEW_REC'] : 'Create New Records Using Image'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								<li style="margin-left:10px">
									<a href="../editor/skeletalsubmit.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['SKELETAL']) ? $LANG['SKELETAL'] : 'Add Skeletal Records'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								<?php
							}
							?>
							<li>
								<a href="../editor/occurrencetabledisplay.php?displayquery=1&collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
									<?php echo htmlspecialchars((isset($LANG['EDIT_EXISTING']) ? $LANG['EDIT_EXISTING'] : 'Edit Existing Occurrence Records'), HTML_SPECIAL_CHARS_FLAGS); ?>
								</a>
							</li>
							<li>
								<a href="../editor/batchdeterminations.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
									<?php echo htmlspecialchars((isset($LANG['ADD_BATCH_DETER']) ? $LANG['ADD_BATCH_DETER'] : 'Add Batch Determinations/Nomenclatural Adjustments'), HTML_SPECIAL_CHARS_FLAGS); ?>
								</a>
							</li>
							<li>
								<a href="../reports/labelmanager.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
									<?php echo htmlspecialchars((isset($LANG['PRINT_LABELS']) ? $LANG['PRINT_LABELS'] : 'Print Specimen Labels'), HTML_SPECIAL_CHARS_FLAGS); ?>
								</a>
							</li>
							<li>
								<a href="../reports/annotationmanager.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
									<?php echo htmlspecialchars((isset($LANG['PRINT_ANNOTATIONS']) ? $LANG['PRINT_ANNOTATIONS'] : 'Print Annotations Labels'), HTML_SPECIAL_CHARS_FLAGS); ?>
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
									<a href="../traitattr/occurattributes.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['TRAIT_CODING']) ? $LANG['TRAIT_CODING'] : 'Trait Coding from Images'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								<li class="traitItem" style="margin-left:10px;display:none;">
									<a href="../traitattr/attributemining.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['TRAIT_MINING']) ? $LANG['TRAIT_MINING'] : 'Trait Mining from Verbatim Text'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								<?php
							}
							?>
							<li>
								<a href="../georef/batchgeoreftool.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
									<?php echo htmlspecialchars((isset($LANG['BATCH_GEOREF']) ? $LANG['BATCH_GEOREF'] : 'Batch Georeference Specimens'), HTML_SPECIAL_CHARS_FLAGS); ?>
								</a>
							</li>
							<?php
							if ($collData['colltype'] == 'Preserved Specimens') {
								?>
								<li>
									<a href="../loans/index.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['LOAN_MANAGEMENT']) ? $LANG['LOAN_MANAGEMENT'] : 'Loan Management'), HTML_SPECIAL_CHARS_FLAGS); ?>
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
							<h1><span><?php echo (isset($LANG['ADMIN_CONTROL']) ? $LANG['ADMIN_CONTROL'] : 'Administration Control Panel'); ?></span></h1>
							<ul>
								<li>
									<a href="commentlist.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['VIEW_COMMENTS']) ? $LANG['VIEW_COMMENTS'] : 'View Posted Comments'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
									<?php if ($commCnt = $collManager->unreviewedCommentsExist()) echo '- <span style="color:orange">' . $commCnt . ' ' . (isset($LANG['UNREVIEWED_COMMENTS']) ? $LANG['UNREVIEWED_COMMENTS'] : 'unreviewed comments') . '</span>'; ?>
								</li>
								<li>
									<a href="collmetadata.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['EDIT_META']) ? $LANG['EDIT_META'] : 'Edit Metadata'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								<!--
								<li>
									<a href="" onclick="$('li.metadataItem').show(); return false;"  >
										<?php echo htmlspecialchars((isset($LANG['OPEN_META']) ? $LANG['OPEN_META'] : 'Open Metadata'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								<li class="metadataItem" style="margin-left:10px;display:none;">
									<a href="collmetadata.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['EDIT_META']) ? $LANG['EDIT_META'] : 'Edit Metadata'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								<li class="metadataItem" style="margin-left:10px;display:none;">
									<a href="colladdress.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['EDIT_ADDRESS']) ? $LANG['EDIT_ADDRESS'] : 'Edit Mailing Address'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								<li class="metadataItem" style="margin-left:10px;display:none;">
									<a href="collproperties.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['EDIT_COLL_PROPS']) ? $LANG['EDIT_COLL_PROPS'] : 'Special Properties'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								 -->
								<li>
									<a href="collpermissions.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['MANAGE_PERMISSIONS']) ? $LANG['MANAGE_PERMISSIONS'] : 'Manage Permissions'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								<li>
									<a href="#" onclick="$('li.importItem').show(); return false;">
										<?php echo (isset($LANG['IMPORT_SPECIMEN']) ? $LANG['IMPORT_SPECIMEN'] : 'Import/Update Specimen Records'); ?>
									</a>
									<a id="importinfo" href="https://biokic.github.io/symbiota-docs/coll_manager/upload/" title="<?php echo $LANG['MORE_INFO']; ?>" aria-label="<?php echo $LANG['MORE_INFO']; ?>">
											<img src="../../images/info.png" style="width:13px;" alt="<?php echo (isset($LANG['INFO_ALT']) ? $LANG['INFO_ALT'] : 'Info. icon indicating link to information about import/update'); ?>" />
									</a><br/>
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
											<a href="../specprocessor/index.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
												<?php echo htmlspecialchars((isset($LANG['PROCESSING_TOOLBOX']) ? $LANG['PROCESSING_TOOLBOX'] : 'Processing Toolbox'), HTML_SPECIAL_CHARS_FLAGS); ?>
											</a>
										</li>
										<li>
											<a href="../datasets/datapublisher.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
												<?php echo htmlspecialchars((isset($LANG['DARWIN_CORE_PUB']) ? $LANG['DARWIN_CORE_PUB'] : 'Darwin Core Archive Publishing'), HTML_SPECIAL_CHARS_FLAGS); ?>
											</a>
										</li>
										<?php
									}
									?>
									<li>
										<a href="../editor/editreviewer.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
											<?php echo htmlspecialchars((isset($LANG['REVIEW_SPEC_EDITS']) ? $LANG['REVIEW_SPEC_EDITS'] : 'Review/Verify Occurrence Edits'), HTML_SPECIAL_CHARS_FLAGS); ?>
										</a>
									</li>
									<!--
									<li>
										<a href="../reports/accessreport.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
											<?php echo htmlspecialchars((isset($LANG['ACCESS_REPORT']) ? $LANG['ACCESS_REPORT'] : 'View Access Statistics'), HTML_SPECIAL_CHARS_FLAGS); ?>
										</a>
									</li>
									 -->
									<?php
								}
								if (!empty($ACTIVATE_DUPLICATES)) {
									?>
									<li>
										<a href="../datasets/duplicatemanager.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
											<?php echo htmlspecialchars((isset($LANG['DUP_CLUSTER']) ? $LANG['DUP_CLUSTER'] : 'Duplicate Clustering'), HTML_SPECIAL_CHARS_FLAGS); ?>
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
										<a href="../cleaning/index.php?obsuid=0&collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
											<?php echo htmlspecialchars((isset($LANG['DATA_CLEANING']) ? $LANG['DATA_CLEANING'] : 'Data Cleaning Tools'), HTML_SPECIAL_CHARS_FLAGS); ?>
										</a>
									</li>
									<?php
								}
								?>
								<li style="margin-left:10px;">
									<a href="#" onclick="newWindow = window.open('collbackup.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>','bucollid','scrollbars=1,toolbar=0,resizable=1,width=600,height=250,left=20,top=20');">
										<?php echo htmlspecialchars((isset($LANG['BACKUP_DATA_FILE']) ? $LANG['BACKUP_DATA_FILE'] : 'Download Backup Data File'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								<?php
								if ($collData['managementtype'] == 'Live Data') {
									?>
									<li style="margin-left:10px;">
										<a href="../admin/restorebackup.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
											<?php echo htmlspecialchars((isset($LANG['RESTORE_BACKUP']) ? $LANG['RESTORE_BACKUP'] : 'Restore Backup File'), HTML_SPECIAL_CHARS_FLAGS); ?>
										</a>
									</li>
									<?php
								}
								?>
								<!--
								<li style="margin-left:10px;">
									<a href="../../imagelib/admin/igsnmapper.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['GUID_MANAGEMENT']) ? $LANG['GUID_MANAGEMENT'] : 'IGSN GUID Management'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								 -->
								<li style="margin-left:10px;">
									<a href="../../imagelib/admin/thumbnailbuilder.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>">
										<?php echo htmlspecialchars((isset($LANG['THUMBNAIL_MAINTENANCE']) ? $LANG['THUMBNAIL_MAINTENANCE'] : 'Thumbnail Maintenance'), HTML_SPECIAL_CHARS_FLAGS); ?>
									</a>
								</li>
								<li style="margin-left:10px;">
									<a href="collprofiles.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>&action=UpdateStatistics">
										<?php echo htmlspecialchars((isset($LANG['UPDATE_STATS']) ? $LANG['UPDATE_STATS'] : 'Update Statistics'), HTML_SPECIAL_CHARS_FLAGS); ?>
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
						<div><b><?php echo (isset($LANG['GBIF_DATASET']) ? $LANG['GBIF_DATASET'] : 'GBIF Dataset page'); ?>:</b> <a href="<?php echo htmlspecialchars($dataUrl, HTML_SPECIAL_CHARS_FLAGS); ?>" target="_blank"><?php echo htmlspecialchars($dataUrl, HTML_SPECIAL_CHARS_FLAGS); ?></a></div>
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
							<div><b><?php echo (isset($LANG['IDIGBIO_DATASET']) ? $LANG['IDIGBIO_DATASET'] : 'iDigBio Dataset page'); ?>:</b> <a href="<?php echo htmlspecialchars($dataUrl, HTML_SPECIAL_CHARS_FLAGS); ?>" target="_blank"><?php echo htmlspecialchars($dataUrl, HTML_SPECIAL_CHARS_FLAGS); ?></a></div>
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
						<h1><span><?php echo (isset($LANG['ADDRESS']) ? $LANG['ADDRESS'] : 'Address'); ?>:</span></h1>
						<div class="bigger-left-margin-rel">
							<?php
							echo "<div>" . $addrArr["institutionname"];
							if ($editCode > 1) echo ' <a href="institutioneditor.php?emode=1&targetcollid=' . htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS) . '&iid=' . htmlspecialchars($addrArr['iid'], HTML_SPECIAL_CHARS_FLAGS) . '" title="' . htmlspecialchars((isset($LANG['EDIT_INST']) ? $LANG['EDIT_INST'] : 'Edit institution information'), HTML_SPECIAL_CHARS_FLAGS) . '" aria-label="' . htmlspecialchars((isset($LANG['EDIT_INST']) ? $LANG['EDIT_INST'] : 'Edit institution information'), HTML_SPECIAL_CHARS_FLAGS) . '"><img src="../../images/edit.png" style="width:13px;" alt="edit icon" /></a>';
							echo '</div>';
							if ($addrArr["institutionname2"]) echo "<div>" . $addrArr["institutionname2"] . "</div>";
							if ($addrArr["address1"]) echo "<div>" . $addrArr["address1"] . "</div>";
							if ($addrArr["address2"]) echo "<div>" . $addrArr["address2"] . "</div>";
							if ($addrArr["city"]) echo "<div>" . $addrArr["city"] . ", " . $addrArr["stateprovince"] . "&nbsp;&nbsp;&nbsp;" . $addrArr["postalcode"] . "</div>";
							if ($addrArr["country"]) echo "<div>" . $addrArr["country"] . "</div>";
							if ($addrArr["phone"]) echo "<div>" . $addrArr["phone"] . "</div>";
							if ($addrArr["url"]) echo '<div><a href="' . htmlspecialchars($addrArr['url'], HTML_SPECIAL_CHARS_FLAGS) . '">' . htmlspecialchars($addrArr['url'], HTML_SPECIAL_CHARS_FLAGS) . '</a></div>';
							if ($addrArr["notes"]) echo "<div>" . $addrArr["notes"] . "</div>";
							?>
						</div>
					</section>
					<?php
				}
				//Collection Statistics
				$statsArr = $collManager->getBasicStats();
				$georefPerc = 0;
				if ($statsArr['georefcnt'] && $statsArr['recordcnt']) $georefPerc = (100 * ($statsArr['georefcnt'] / $statsArr['recordcnt']));
				?>
				<section class="fieldset-like no-left-margin">
					<h1><span><?php echo (isset($LANG['COLL_STATISTICS']) ? $LANG['COLL_STATISTICS'] : 'Collection Statistics'); ?></span></h1>
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
										if ($statsArr['recordcnt']) $imgPerc = (100 * ($imgSpecCnt / $statsArr['recordcnt']));
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
								if (isset($extrastatsArr['SpecimensCountID']) && $extrastatsArr['SpecimensCountID']) {
									$spidPerc = (100 * ($extrastatsArr['SpecimensCountID'] / $statsArr['recordcnt']));
									echo '<li>' . number_format($extrastatsArr['SpecimensCountID']) . ($spidPerc ? " (" . ($spidPerc > 1 ? round($spidPerc) : round($spidPerc, 2)) . "%)" : '') . ' ' . (isset($LANG['IDED_TO_SPECIES']) ? $LANG['IDED_TO_SPECIES'] : 'identified to species') . '</li>';
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
				<h1><span><?php echo (isset($LANG['EXTRA_STATS']) ? $LANG['EXTRA_STATS'] : 'Extra Statistics'); ?></span></h1>
				<div style="margin:3px;">
					<a href="collprofiles.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>&stat=geography#geographystats"><?php echo htmlspecialchars((isset($LANG['SHOW_GEOG_DIST']) ? $LANG['SHOW_GEOG_DIST'] : 'Show Geographic Distribution'), HTML_SPECIAL_CHARS_FLAGS); ?></a>
				</div>
				<div style="margin:3px;">
					<a href="collprofiles.php?collid=<?php echo htmlspecialchars($collid, HTML_SPECIAL_CHARS_FLAGS); ?>&stat=taxonomy#taxonomystats"><?php echo htmlspecialchars((isset($LANG['SHOW_FAMILY_DIST']) ? $LANG['SHOW_FAMILY_DIST'] : 'Show Family Distribution'), HTML_SPECIAL_CHARS_FLAGS); ?></a>
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
					<a id="image-search" href="<?php echo $CLIENT_ROOT?>/imagelib/search.php?submitaction=search&db[]=1<?php echo $collid ?>" ><?php echo (isset($LANG['IMAGE_SEARCH_THIS_COLLECTION'])?$LANG['IMAGE_SEARCH_THIS_COLLECTION']:'Image Search this Collection'); ?></a>
				</span>
			</div>
			<?php
		} elseif($collData) {
			?>
			<h2><?php echo $DEFAULT_TITLE . ' ' . (isset($LANG['COLLECTION_PROJECTS']) ? $LANG['COLLECTION_PROJECTS'] : 'Natural History Collections and Observation Projects'); ?></h2>
			<div>
				<a href="../datasets/rsshandler.php" target="_blank"><?php echo (isset($LANG['RSS_FEED']) ? $LANG['RSS_FEED'] : 'RSS feed'); ?></a>
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
								<a class="col-profile-header" href='collprofiles.php?collid=<?php echo htmlspecialchars($cid, HTML_SPECIAL_CHARS_FLAGS); ?>'>
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
								<a href='collprofiles.php?collid=<?php echo htmlspecialchars($cid, HTML_SPECIAL_CHARS_FLAGS); ?>'><?php echo htmlspecialchars((isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'More Information'), HTML_SPECIAL_CHARS_FLAGS); ?></a>
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