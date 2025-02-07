<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceImport.php');
if ($LANG_TAG != 'en' && !file_exists($SERVER_ROOT . '/content/lang/collections/admin/importextended.' . $LANG_TAG . '.php')) $LANG_TAG = 'en';
include_once($SERVER_ROOT . '/content/lang/collections/admin/importextended.' . $LANG_TAG . '.php');
header('Content-Type: text/html; charset=' . $CHARSET);

if (!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/importextended.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$importType = array_key_exists('importType', $_REQUEST) ? filter_var($_REQUEST['importType'], FILTER_SANITIZE_NUMBER_INT) : 0;
$associationType = array_key_exists('associationType', $_POST) ? $_POST['associationType'] : '';
$createNew = array_key_exists('createNew', $_POST) ? filter_var($_POST['createNew'], FILTER_SANITIZE_NUMBER_INT) : 0;
$fileName = array_key_exists('fileName', $_POST) ? $_POST['fileName'] : '';
$action = array_key_exists('submitAction', $_POST) ? $_POST['submitAction'] : '';

$importManager = new OccurrenceImport();
$importManager->setCollid($collid);
$importManager->setImportType($importType);
$importManager->setFileName($fileName);

$isEditor = false;
if ($IS_ADMIN || (array_key_exists('CollAdmin', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollAdmin']))) {
	$isEditor = true;
}
?>
<!DOCTYPE html>
<html lang="<?= $LANG_TAG ?>">

<head>
	<title><?= $DEFAULT_TITLE ?> - <?= $LANG['IMPORT_EXTEND'] ?> </title>
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script>
		function verifyFileSize(inputObj) {
			if (!window.FileReader) {
				//alert("The file API isn't supported on this browser yet.");
				return;
			}
			<?php
			$maxUpload = ini_get('upload_max_filesize');
			$maxUpload = str_replace("M", "000000", $maxUpload);
			if ($maxUpload > 10000000) $maxUpload = 10000000;
			echo 'var maxUpload = ' . $maxUpload . ";\n";
			?>
			var file = inputObj.files[0];
			if (file.size > maxUpload) {
				var msg = "<?= $LANG['IMPORT_FILE'] ?>" + file.name + " (" + Math.round(file.size / 100000) / 10 + "<?= $LANG['IS_TOO_BIG'] ?>" + (maxUpload / 1000000) + "MB).";
				if (file.name.slice(-3) != "zip") msg = msg + "<?= $LANG['MAYBE_ZIP'] ?>";
				alert(msg);
			}
		}

		function validateInitiateForm(f) {
			if (f.importFile.value == "") {
				alert("<?= $LANG['SELECT_FILE'] ?>");
				return false;
			}
			if (f.importType.value == "") {
				alert("<?= $LANG['SELECT_IMPORT_TYPE'] ?>");
				return false;
			} else if (f.importType.value == 1 && f.associationType.value == "") {
				alert("<?= $LANG['SELECT_ASSOC_TYPE'] ?>");
				return false;
			}
			return true;
		}


		function validateMappingForm(f) {
			let sourceArr = [];
			let targetArr = [];
			let requiredFieldArr = [];
			<?php
			if ($associationType == 'resource' || $associationType == 'externalOccurrence') {
				echo 'requiredFieldArr["resourceUrl"] = 0; ';
			} elseif ($associationType == 'observational') {
				echo 'requiredFieldArr["verbatimSciname"] = 0; ';
			}
			?>
			let subjectIdentifierIsMapped = false;
			let objectIdentifierIsMapped = false;

			const form_data = new FormData(f);

			for (const [key, value] of form_data.entries()) {
				if (key.substring(0, 3) == "sf[") {
					if (sourceArr.indexOf(value) > -1) {
						alert("<?= $LANG['ERR_DUPLICATE_SOURCE'] ?>" + value + ")");
						return false;
					}
					sourceArr[sourceArr.length] = value;
				} else if (value != "") {
					if (key.substring(0, 3) == "tf[") {
						if (targetArr.indexOf(value) > -1) {
							alert("<?= $LANG['ERR_DUPLICATE_TARGET'] ?>" + value + ")");
							return false;
						}
						targetArr[targetArr.length] = value;
					}
				}
				if (key.substring(0, 3) == "tf[") {
					if (value == "catalognumber") {
						subjectIdentifierIsMapped = true;
					} else if (value == "othercatalognumbers") {
						subjectIdentifierIsMapped = true;
					} else if (value == "occurrenceid") {
						subjectIdentifierIsMapped = true;
					} else if (value == "occid") {
						subjectIdentifierIsMapped = true;
					}
					<?php
					if ($associationType == 'internalOccurrence') {
					?>
						if (value == "object-catalognumber") {
							objectIdentifierIsMapped = true;
						} else if (value == "object-occurrenceid") {
							objectIdentifierIsMapped = true;
						} else if (value == "occidassociate") {
							objectIdentifierIsMapped = true;
						}
					<?php
					}
					?>
					for (const fieldName2 in requiredFieldArr) {
						if (value == fieldName2.toLowerCase()) requiredFieldArr[fieldName2] = 1;
					}
				}
			}
			if (!subjectIdentifierIsMapped) {
				alert("<?= $LANG['SUBJECT_ID_REQUIRED'] ?>");
				return false;
			}
			<?php
			if ($associationType == 'internalOccurrence') {
			?>
				if (!objectIdentifierIsMapped) {
					alert("<?= $LANG['OBJECT_ID_REQUIRED'] ?>");
					return false;
				}
			<?php
			}
			?>
			if (f.relationship && f.relationship.value == "") {
				alert("<?= $LANG['SELECT_RELATIONSHIP'] ?>");
				return false;
			}
			for (const fieldName in requiredFieldArr) {
				if (requiredFieldArr[fieldName] == 0) {
					alert(fieldName + " is a required import field");
					return false;
				}
			}
			return true;
		}

		function importTypeChanged(selectElement) {
			let f = selectElement.form;
			if (selectElement.value == 1) {
				document.getElementById("associationType-div").style.display = "block";
			} else {
				document.getElementById("associationType-div").style.display = "none";
			}
		}
	</script>
	<style>
		.formField-div {
			margin: 10px;
		}

		label {
			font-weight: bold;
		}

		fieldset {
			margin: 10px;
			padding: 10px;
		}

		legend {
			font-weight: bold;
		}

		.index-li {
			margin-left: 10px;
		}

		button {
			margin: 10px 15px
		}
	</style>
</head>

<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php"><?= $LANG['HOME'] ?></a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?= $collid ?>&emode=1"><?= $LANG['COLLECTION_MENU'] ?></a> &gt;&gt;
		<a href="importextended.php?collid=<?= $collid ?>"><b><?= $LANG['DATA_IMPORTER'] ?></b></a>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['DATA_IMPORTER']; ?></h1>
		<h2><?= $importManager->getCollMeta('collName'); ?></h2>
		<div class="pageDescription-div">
			<?= $LANG['INSTRUCTIONS'] ?>:
			<ul>
				<li><a href="https://biokic.github.io/symbiota-docs/coll_manager/upload/links" target="_blank"><?= $LANG['ASSOCIATIONS'] ?></a></li>
				<?php if ($IS_ADMIN) echo '<li><a href="https://biokic.github.io/symbiota-docs/portal_manager/determinations" target="_blank">' . $LANG['DETERMINATIONS'] . '</a></li>'; ?>
				<li><a href="https://biokic.github.io/symbiota-docs/coll_manager/images/url_upload" target="_blank"><?= $LANG['IMAGE_URLS'] ?></a></li>
			</ul>
		</div>
		<?php
		if (!$isEditor) {
			echo '<h2>' . $LANG['ERR_NOT_AUTH'] . '</h2>';
		} elseif (!$importManager->getCollMeta('collName')) {
			echo '<h2>' . $LANG['ERR_COLL_NOT_VALID'] . '</h2>';
		} else {
			$actionStatus = false;
			if ($action == 'importData') {
		?>
				<fieldset>
					<legend><?= $LANG['ACTION_PANEL'] ?></legend>
					<?php
					$importManager->setCreateNewRecord($createNew);
					echo '<ul>';
					echo '<li>' . $LANG['STARTING_PROCESS'] . ' ' . $fileName . ' (' . date('Y-m-d H:i:s') . ')</li>';
					if ($importManager->loadData($_POST)) {
						echo '<li>' . $LANG['DONE_PROCESSING'] . ' (' . date('Y-m-d H:i:s') . ')</li>';
					}
					echo '</ul>';
					?>
				</fieldset>
				<?php
			} elseif ($action == 'initiateImport') {
				if ($actionStatus = $importManager->importFile()) {
					$importManager->setTargetFieldArr($associationType);
				?>
					<form name="mappingform" action="importextended.php" method="post" onsubmit="return validateMappingForm(this)">
						<fieldset>
							<legend><b><?= $LANG['FIELD_MAPPING'] ?></b></legend>
							<?php
							if ($associationType) {
							?>
								<div class="formField-div">
									<label for="associationType"><?= $LANG['ASSOCIATION_TYPE'] ?>:</label> <?= $associationType ?>
									<input name="associationType" type="hidden" value="<?= $associationType ?>">
								</div>
							<?php
							}
							if ($importType == 1) {
							?>
								<div class="formField-div">
									<label><?= $LANG['RELATIONSHIP'] ?>:</label>
									<select name="relationship">
										<option value="">-------------------</option>
										<?php
										$filter = '';
										//if($associationType == 'resource') $filter = 'associationType:resource';
										$relationshipArr = $importManager->getControlledVocabulary('omoccurassociations', 'relationship', $filter);
										foreach ($relationshipArr as $term => $display) {
											echo '<option value="' . $term . '">' . $display . '</option>';
										}
										?>
										<option value="">-------------------</option>
										<option value="DELETE"><?= $LANG['BATCH_DELETE'] ?></option>
									</select>
								</div>
								<div class="formField-div">
									<label><?= $LANG['REL_SUBTYPE'] ?>:</label>
									<select name="subType">
										<option value="">-------------------</option>
										<?php
										$relationshipArr = $importManager->getControlledVocabulary('omoccurassociations', 'subtype');
										foreach ($relationshipArr as $term => $display) {
											echo '<option value="' . $term . '">' . $display . '</option>';
										}
										?>
									</select>
								</div>
							<?php
							}
							?>
							<div class="formField-div">
								<?php
								echo $importManager->getFieldMappingTable();
								?>
							</div>
							<?php
							if ($importType == 3) {
							?>
								<div class="formField-div">
									<input name="createNew" type="checkbox" value="1" <?= ($createNew ? 'checked' : '') ?>>
									<label for="createNew"><?= $LANG['NEW_BLANK_RECORD'] ?></label>
								</div>
							<?php
							} elseif ($importType == 1) {
							?>
								<div class="formField-div">
									<input name="replace" type="checkbox" value="1">
									<label for="replace"><?= $LANG['MATCHING_IDENTIFIERS'] ?></label>
								</div>
							<?php
							}
							if ($importType == 5) {
							?>
								<div class="formField-div">
									<label for='action'><?= $LANG['ACTION'] ?>:</label>
									<select name="action" id='action'>
										<option value="add-or-update"><?= $LANG['BATCH_ADD_OR_UPDATE'] ?></option>
										<option value="delete"><?= $LANG['BATCH_DELETE'] ?></option>
									</select>
								</div>
								<div class="formField-div">
									<input name="replace-identifier" type="checkbox" value="1">
									<label for="replace-identifier"><?= $LANG['IDENTIFIER_UPDATE_OR_DELETE'] ?></label>
								</div>
							<?php
							}
							?>
							<div style="margin:15px;">
								<input name="collid" type="hidden" value="<?= $collid; ?>">
								<input name="importType" type="hidden" value="<?= $importType ?>">
								<input name="fileName" type="hidden" value="<?= htmlspecialchars($importManager->getFileName(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>">
								<button name="submitAction" type="submit" value="importData"><?= $LANG['IMPORT_DATA'] ?></button>
							</div>
						</fieldset>
					</form>
				<?php
				} else echo $LANG['ERR_SETTING_IMPORT'] . ': ' . $importManager->getErrorMessage();
			}
			if (!$actionStatus) {
				?>
				<form name="initiateImportForm" action="importextended.php" method="post" enctype="multipart/form-data" onsubmit="return validateInitiateForm(this)">
					<fieldset>
						<legend><?= $LANG['INITIALIZE_IMPORT'] ?></legend>
						<div class="formField-div">
							<input name="importFile" type="file" onchange="verifyFileSize(this)" aria-label="<?php echo $LANG['CHOOSE_FILE'] ?>" />
						</div>
						<div class="formField-div">
							<label for="importType"><?= $LANG['IMPORT_TYPE'] ?>: </label>
							<select id="importType" name="importType" onchange="importTypeChanged(this)" aria-label="<?php echo $LANG['IMPORT_TYPE'] ?>">
								<option value="">-------------------</option>
								<option value="1"><?= $LANG['ASSOCIATIONS'] ?></option>
								<?php if ($IS_ADMIN) {
									echo '<option value="2">' . $LANG['DETERMINATIONS'] . '</option>';
								}
								?>
								<option value="3"><?= $LANG['IMAGE_FIELD_MAP'] ?></option>
								<?php
								if ($importManager->getCollMeta('materialSample')) {
									echo '<option value="4">' . $LANG['MATERIAL_SAMPLE'] . '</option>';
								}
								?>
								<option value="5"><?= $LANG['IDENTIFIERS'] ?></option>
							</select>
						</div>
						<div id="associationType-div" class="formField-div" style="display:none">
							<label for="associationType"><?= $LANG['ASSOCIATION_TYPE'] ?>: </label>
							<select id="associationType" name="associationType" aria-label="<?php echo $LANG['ASSOCIATION_TYPE'] ?>">
								<option value="">-------------------</option>
								<option value="resource"><?= $LANG['RESOURCE_LINK'] ?></option>
								<option value="internalOccurrence"><?= $LANG['INTERNAL_OCCURRENCE'] ?></option>
								<option value="externalOccurrence"><?= $LANG['EXTERNAL_OCCURRENCE'] ?></option>
								<option value="observational"><?= $LANG['OBSERVATION'] ?></option>
							</select>
						</div>
						<div class="formField-div">
							<input name="collid" type="hidden" value="<?= $collid ?>">
							<input name="MAX_FILE_SIZE" type="hidden" value="10000000" />
							<button name="submitAction" type="submit" value="initiateImport"><?= $LANG['INITIALIZE_IMPORT'] ?></button>
						</div>
					</fieldset>
				</form>
		<?php
			}
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>

</html>