<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceCollectionProfile.php');
include_once($SERVER_ROOT . '/content/lang/collections/misc/collmetadata.' . $LANG_TAG . '.php');
header('Content-Type: text/html; charset=' . $CHARSET);

if (!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/misc/collmetadata.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$tabIndex = array_key_exists('tabindex', $_REQUEST) ? filter_var($_REQUEST['tabindex'], FILTER_SANITIZE_NUMBER_INT) : 0;
$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : '';

$isEditor = 0;
if ($IS_ADMIN) $isEditor = 1;
elseif ($collid) {
	if (array_key_exists('CollAdmin', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollAdmin'])) {
		$isEditor = 1;
	}
}

$connType = 'readonly';
if($isEditor && ($action || !$collid)) $connType = 'write';
$collManager = new OccurrenceCollectionProfile($connType);
$collManager->setCollid($collid);

$statusStr = '';
if ($isEditor) {
	if ($action == 'saveEdits') {
		$statusStr = $collManager->collectionUpdate($_POST);
		if ($statusStr === true) { 
			header('Location: collprofiles.php?collid=' . $collid);
		} else {
			$statusStr = $collManager->getErrorMessage();
		}
	}
	elseif ($action == 'newCollection') {
		if ($IS_ADMIN) {
			$newCollid = $collManager->collectionInsert($_POST);
			if ($newCollid) {
				$statusStr = '<span style="color:green">' . (isset($LANG['ADD_SUCCESS']) ? $LANG['ADD_SUCCESS'] : 'New collection added successfully') . '!</span><br/>' .
					(isset($LANG['ADD_STUFF']) ? $LANG['ADD_STUFF'] : 'Add contacts, resource links, or institution address below') . '.';
				$collid = $newCollid;
				$tabIndex = 1;
			}
			else $statusStr = $collManager->getErrorMessage();
		}
	}
	elseif ($action == 'saveResourceLink') {
		if (!$collManager->saveResourceLink($_POST)) $statusStr = $collManager->getErrorMessage();
		$tabIndex = 1;
	}
	elseif ($action == 'saveContact') {
		if (!$collManager->saveContact($_POST)) $statusStr = $collManager->getErrorMessage();
		$tabIndex = 1;
	}
	elseif ($action == 'deleteContact') {
		if (!$collManager->deleteContact($_POST['contactIndex'])) $statusStr = $collManager->getErrorMessage();
		$tabIndex = 1;
	}
	elseif ($action == 'Link Address') {
		if (!$collManager->linkAddress($_POST['iid'])) $statusStr = $collManager->getErrorMessage();
	}
	elseif (array_key_exists('removeiid', $_GET)) {
		if (!$collManager->removeAddress($_GET['removeiid'])) $statusStr = $collManager->getErrorMessage();
	}
}
$collData = current($collManager->getCollectionMetadata());
$collManager->cleanOutArr($collData);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">

<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . ($collid ? $collData['collectionname'] : '') . ' ' . $LANG['COL_PROFS']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../../js/symb/common.js" type="text/javascript"></script>
	<script type="text/javascript" src="../../js/tinymce/tinymce.min.js"></script>
	<script>
		// Adds WYSIWYG editor to description field
		tinymce.init({
			selector: '#full-description',
			plugins: 'code link lists image',
			menubar: '',
			toolbar: ['undo redo | bold italic underline | link | alignleft aligncenter alignright | formatselect | bullist numlist | indent outdent | blockquote | image | code | charmap'],
			branding: false,
			default_link_target: "_blank",
			paste_as_text: false,
			block_unsupported_drop: true,
			images_file_types: 'jpg,jpeg,png,gif',
			images_upload_url: 'tinymceimagehandler.php',
			a11y_advanced_options: true,
			init_instance_callback: function (editor) {
				var iframeBody = editor.getBody();
				iframeBody.setAttribute('aria-label', <?php echo json_encode($LANG['TINYMCE_INFO'], JSON_UNESCAPED_UNICODE); ?>);
			}
		});

		$(function() {
			var dialogArr = new Array("instcode", "collcode", "pedits", "pubagg", "rights", "rightsholder", "accessrights", "guid", "colltype", "management", "icon", "collectionid", "sourceurl", "sort");
			var dialogStr = "";
			for (i = 0; i < dialogArr.length; i++) {
				dialogStr = dialogArr[i] + "info";
				$("#" + dialogStr + "dialog").dialog({
					autoOpen: false,
					modal: true,
					position: {
						my: "left",
						at: "center",
						of: "#" + dialogStr
					}
				});
				$("#" + dialogStr).click(function() {
					$("#" + this.id + "dialog").dialog("open");
				});
			}

			$('#tabs').tabs({
				select: function(event, ui) {
					return true;
				},
				active: <?php echo $tabIndex; ?>,
				beforeLoad: function(event, ui) {
					$(ui.panel).html("<?php echo (isset($LANG['LOADING']) ? $LANG['LOADING'] : '<p>Loading...</p>'); ?>");
				}
			});
		});

		function verifyCollEditForm(f) {
			if (f.institutionCode.value == '') {
				alert("<?php echo (isset($LANG['NEED_INST_CODE']) ? $LANG['NEED_INST_CODE'] : 'Institution Code must have a value'); ?>");
				return false;
			}
			if (f.collectionName.value == '') {
				alert("<?php echo (isset($LANG['NEED_COLL_VALUE']) ? $LANG['NEED_COLL_VALUE'] : 'Collection Name must have a value'); ?>");
				return false;
			}
			if (f.managementType && f.managementType.value == "Snapshot") {
				if (f.guidTarget.value == "symbiotaUUID") {
					alert("<?php echo (isset($LANG['CANNOT_GUID']) ? $LANG['CANNOT_GUID'] : 'The Symbiota Generated GUID option cannot be selected for a collection that is managed locally outside of the data portal (e.g. Snapshot management type). In this case, the GUID must be generated within the source collection database and delivered to the data portal as part of the upload process.'); ?>");
					return false;
				}
			}
			if (!isNumeric(f.latitudeDecimal.value) || !isNumeric(f.longitudeDecimal.value)) {
				alert("<?php echo (isset($LANG['NEED_DECIMAL']) ? $LANG['NEED_DECIMAL'] : 'Latitude and longitude values must be in the decimal format (numeric only)'); ?>");
				return false;
			}
			if (f.rights.value == "") {
				alert("<?php echo (isset($LANG['NEED_RIGHTS']) ? $LANG['NEED_RIGHTS'] : 'Rights field (e.g. Creative Commons license) must have a selection'); ?>");
				return false;
			}
			if (f.sortSeq && !isNumeric(f.sortSeq.value)) {
				alert("<?php echo (isset($LANG['SORT_NUMERIC']) ? $LANG['SORT_NUMERIC'] : 'Sort sequence must be numeric only'); ?>");
				return false;
			}
			return true;
		}

		function managementTypeChanged(selElem) {
			if (selElem.managementType.value == "Live Data") $(".sourceurl-div").hide();
			else $(".sourceurl-div").show();
			checkManagementTypeGuidSource(selElem);
		}

		function checkManagementTypeGuidSource(f) {
			if (f.managementType.value == "Snapshot" && f.guidTarget.value == "symbiotaUUID") {
				alert("<?php echo (isset($LANG['CANNOT_GUID']) ? $LANG['CANNOT_GUID'] : 'The Symbiota Generated GUID option cannot be selected for a collection that is managed locally outside of the data portal (e.g. Snapshot management type). In this case, the GUID must be generated within the source collection database and delivered to the data portal as part of the upload process.'); ?>");
				f.guidTarget.value = '';
			} else if (f.managementType.value == "Aggregate" && f.guidTarget.value != "" && f.guidTarget.value != "occurrenceId") {
				alert("<?php echo (isset($LANG['AGG_GUID']) ? $LANG['AGG_GUID'] : 'An Aggregate dataset (e.g. specimens coming from multiple collections) can only have occurrenceID selected for the GUID source'); ?>");
				f.guidTarget.value = 'occurrenceId';
			}
			if (!f.guidTarget.value) f.publishToGbif.checked = false;
		}

		function checkGUIDSource(f) {
			if (f.publishToGbif.checked == true) {
				if (!f.guidTarget.value) {
					alert("<?php echo (isset($LANG['NEED_GUID']) ? $LANG['NEED_GUID'] : 'You must select a GUID source in order to publish to data aggregators.'); ?>");
					f.publishToGbif.checked = false;
				}
			}
		}

		function verifyAddAddressForm(f) {
			if (f.iid.value == "") {
				alert("<?php echo (isset($LANG['SEL_INST']) ? $LANG['SEL_INST'] : 'Select an institution to be linked'); ?>");
				return false;
			}
			return true;
		}

		function verifyIconImage(f) {
			var iconImageFile = document.getElementById("iconfile").value;
			if (iconImageFile) {
				var iconExt = iconImageFile.substr(iconImageFile.length - 4);
				iconExt = iconExt.toLowerCase();
				if ((iconExt != '.jpg') && (iconExt != 'jpeg') && (iconExt != '.png') && (iconExt != '.gif')) {
					document.getElementById("iconfile").value = '';
					alert("<?php echo (isset($LANG['NOT_SUPP']) ? $LANG['NOT_SUPP'] : 'The file you have uploaded is not a supported image file. Please upload a jpg, png, or gif file.'); ?>");
				} else {
					var fr = new FileReader;
					fr.onload = function() {
						var img = new Image;
						img.onload = function() {
							if ((img.width > 500) || (img.height > 500)) {
								document.getElementById("iconfile").value = '';
								img = '';
								alert("<?php echo (isset($LANG['MUST_SMALL']) ? $LANG['MUST_SMALL'] : 'The image file must be less than 500 pixels in both width and height.'); ?>");
							}
						};
						img.src = fr.result;
					};
					fr.readAsDataURL(document.getElementById("iconfile").files[0]);
				}
			}
		}

		function verifyIconURL(f) {
			var iconImageFile = document.getElementById("iconurl").value;
			if (iconImageFile && (iconImageFile.substr(iconImageFile.length - 4) != '.jpg') && (iconImageFile.substr(iconImageFile.length - 4) != '.png') && (iconImageFile.substr(iconImageFile.length - 4) != '.gif')) {
				alert("<?php echo (isset($LANG['NOT_SUPP_URL']) ? $LANG['NOT_SUPP_URL'] : 'The url you have entered is not for a supported image file. Please enter a url for a jpg, png, or gif file.'); ?>");
			}
		}
	</script>
	<style>
		fieldset {
			background-color: #f9f9f9;
			padding: 15px
		}

		legend {
			font-weight: bold;
		}

		.field-block {
			margin: 5px 0px;
		}

		.field-label {}

		.max-width-fit-75 {
			max-width: 100%;
			width: 75rem;
		}
	</style>
</head>

<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_collmetadataMenu) ? $collections_misc_collmetadataMenu : true);
	include($SERVER_ROOT . '/includes/header.php');
	echo '<div class="navpath">';
	echo '<a href="../../index.php">' . htmlspecialchars((isset($LANG['HOME']) ? $LANG['HOME'] : 'Home'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> &gt;&gt; ';
	if ($collid) {
		echo '<a href="collprofiles.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">' . htmlspecialchars((isset($LANG['COL_MGMNT']) ? $LANG['COL_MGMNT'] : 'Collection Management'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> &gt;&gt; ';
		echo '<b>' . $collData['collectionname'] . ' ' . (isset($LANG['META_EDIT']) ? $LANG['META_EDIT'] : 'Metadata Editor') . '</b>';
	}
	else echo '<b>' . (isset($LANG['CREATE_COLL']) ? $LANG['CREATE_COLL'] : 'Create New Collection Profile') . '</b>';
	echo '</div>';
	?>
	<div role="main" id="innertext">
		<?php
		if ($statusStr) {
			?>
			<hr />
			<div style="margin:20px;">
				<?php echo $statusStr; ?>
			</div>
			<hr />
			<?php
		}
		?>
		<div id="tabs" style="margin:0px;">
			<?php
			if ($isEditor) {
				if ($collid) echo '<h1 class="page-heading">Edit Collection Metadata & Contacts: ' . $collData['collectionname'] . (array_key_exists('institutioncode', $collData) ? ' (' . $collData['institutioncode'] . ')' : '') . '</h1>';
				?>
				<ul>
					<li><a href="#colleditor"><?php echo htmlspecialchars((isset($LANG['COL_META_EDIT']) ? $LANG['COL_META_EDIT'] : 'Collection Metadata Editor'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
					<?php
					if ($collid) echo '<li><a href="collmetaresources.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars((isset($LANG['CONT_RES']) ? $LANG['CONT_RES'] : 'Contacts & Resources'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
					?>
				</ul>
				<div id="colleditor">
					<h1 class="page-heading screen-reader-only">Collection Metadata Editor Tab</h1>
					<section class="fieldset-like">
						<h2> <span> <?php echo ($collid ? 'Edit' : 'Add New') . ' ' . (isset($LANG['COL_INFO']) ? $LANG['COL_INFO'] : 'Collection Information'); ?> </span> </h2>
						<form id="colleditform" name="colleditform" action="collmetadata.php" method="post" enctype="multipart/form-data" onsubmit="return verifyCollEditForm(this)">
							<div class="field-block">
								<span class="field-elem">
									<label for="institutionCode"> <?php echo (isset($LANG['INST_CODE']) ? htmlspecialchars($LANG['INST_CODE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'Institution Code'); ?>: </label>
									<span class="screen-reader-only">
										<?php
											echo (isset($LANG['NAME_ONE']) ? $LANG['NAME_ONE'] : '') . ' ';
										?>
									</span>
									<input id="institutionCode" type="text" name="institutionCode" value="<?php echo ($collid ? $collData['institutioncode'] : ''); ?>" required />
									<a id="instcodeinfo" href="#" onclick="return false" tabindex="0"	>
										<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>" title="<?php echo (isset($LANG['MORE_INST_CODE']) ? $LANG['MORE_INST_CODE'] : 'More information about Institution Code'); ?>"/>
									</a>
									<span id="instcodeinfodialog" aria-live="polite">
										<?php
											echo (isset($LANG['NAME_ONE']) ? $LANG['NAME_ONE'] : '') . ' ';
											echo '<a href="http://rs.tdwg.org/dwc/terms/index.htm#institutionCode" target="_blank">' . htmlspecialchars((isset($LANG['DWC_DEF']) ? $LANG['DWC_DEF'] : 'Darwin Core definition'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>.';
										?>
									</span>
								</span>
							</div>
							<div class="field-block">
								<span class="field-elem">
									<label for="collectionCode"> <?php echo (isset($LANG['COLL_CODE']) ? htmlspecialchars($LANG['COLL_CODE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'Collection Code'); ?>: </label>
									<span class="screen-reader-only">
										<?php
											echo (isset($LANG['NAME_ACRO']) ? $LANG['NAME_ACRO'] : '') . ' ';
										?>
									</span>
									<input id="collectionCode" type="text" name="collectionCode" value="<?php echo ($collid ? $collData['collectioncode'] : ''); ?>" />
									<a id="collcodeinfo" href="#" onclick="return false" tabindex="0">
										<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>" title="<?php echo (isset($LANG['MORE_COLL_CODE']) ? $LANG['MORE_COLL_CODE'] : 'More information about Collection Code'); ?>"/>
									</a>
									<span id="collcodeinfodialog" aria-live="polite">
										<?php
										echo (isset($LANG['NAME_ACRO']) ? $LANG['NAME_ACRO'] : '') . ' ';
										echo '<a href="http://rs.tdwg.org/dwc/terms/index.htm#institutionCode" target="_blank">' . htmlspecialchars((isset($LANG['DWC_DEF']) ? $LANG['DWC_DEF'] : 'Darwin Core definition'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>.'
										?>
									</span>
								</span>
							</div>
							<div class="field-block">
								<span class="field-elem">
									<label for="collectionName"> <?php echo (isset($LANG['COLL_NAME']) ? htmlspecialchars($LANG['COLL_NAME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'Collection Name'); ?>: </label>
									<input id="collectionName" type="text" name="collectionName" value="<?php echo ($collid ? $collData['collectionname'] : ''); ?>" class="max-width-fit-75" required />
								</span>
							</div>
							<div class="field-block">
								<div class="field-elem">
									<label for="full-description"  tabindex="0" > <?php echo htmlspecialchars($LANG['DESC'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>: </label>
									<textarea id="full-description" name="fullDescription" style="width:95%;height:90px;"><?php echo ($collid ? $collData["fulldescription"] : ''); ?></textarea>
								</div>
							</div>
							<div class="field-block">
								<span class="field-elem">
									<label for="decimallatitude"> <?php echo (isset($LANG['LAT']) ? htmlspecialchars($LANG['LAT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'Latitude'); ?>: </label>
									<input id="decimallatitude" name="latitudeDecimal" type="text" value="<?php echo ($collid ? $collData["latitudedecimal"] : ''); ?>" />
									<a href="#" onclick="openPopup('../tools/mappointaid.php?errmode=0');return false;" tabindex="0"><img src="../../images/world.png" alt="<?php echo (isset($LANG['MAP']) ? $LANG['MAP'] : 'Open map'); ?>" style="width:12px;" /></a>
								</span>
							</div>
							<div class="field-block">
								<span class="field-elem">
									<label for="decimallongitude"> <?php echo (isset($LANG['LONG']) ? htmlspecialchars($LANG['LONG'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'Longitude'); ?>: </label>
									<input id="decimallongitude" name="longitudeDecimal" type="text" value="<?php echo ($collid ? $collData["longitudedecimal"] : ''); ?>" />
								</span>
							</div>
							<?php
							$fullCatArr = $collManager->getCategoryArr();
							if ($fullCatArr) {
								?>
								<div class="field-block">
									<span class="field-elem">
									<label for="ccpk"> <?php echo (isset($LANG['CATEGORY']) ? htmlspecialchars($LANG['CATEGORY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'Category'); ?>: </label>
										<select id="ccpk" name="ccpk">
											<option value=""><?php echo (isset($LANG['NO_CATEGORY']) ? $LANG['NO_CATEGORY'] : 'No Category'); ?></option>
											<option value="">-------------------------------------------</option>
											<?php
											$catArr = $collManager->getCollectionCategories();
											foreach ($fullCatArr as $ccpk => $category) {
												echo '<option value="' . $ccpk . '" ' . ($collid && array_key_exists($ccpk, $catArr) ? 'SELECTED' : '') . '>' . $category . '</option>';
											}
											?>
										</select>
									</span>
								</div>
								<?php
							}
							?>
							<div class="field-block">
								<span class="field-elem">
									<span class="screen-reader-only">
										<?php
											echo (isset($LANG['EXPLAIN_PUBLIC']) ? $LANG['EXPLAIN_PUBLIC'] : '') . ' ';
										?>
									</span>
									<input id="publicEdits" type="checkbox" name="publicEdits" value="1" <?php echo ($collData && $collData['publicedits'] ? 'CHECKED' : ''); ?> />
									<label for="publicEdits"> <?php echo (isset($LANG['ALLOW_PUBLIC_EDITS']) ? htmlspecialchars($LANG['ALLOW_PUBLIC_EDITS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'Allow Public Edits'); ?> </label>
									<a id="peditsinfo" href="#" onclick="return false" tabindex="0">
										<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>" title="<?php echo (isset($LANG['MORE_PUB_EDITS']) ? $LANG['MORE_PUB_EDITS'] : 'More information about Public Edits'); ?>"/>
									</a>
									<span id="peditsinfodialog" aria-live="polite">
										<?php  echo (isset($LANG['EXPLAIN_PUBLIC']) ? $LANG['EXPLAIN_PUBLIC'] : ''); ?>
									</span>
								</span>
							</div>
							<div class="field-block">
								<span class="field-elem">
								<a class="screen-reader-only">
										<?php
											echo (isset($LANG['LEGAL_DOC']) ? $LANG['LEGAL_DOC'] : '') . ' ';
										?>
								</a>
								<label for="rights"> <?php echo (isset($LANG['LICENSE']) ? htmlspecialchars($LANG['LICENSE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'License'); ?>: </label>
									<?php
									if (isset($RIGHTS_TERMS)) {
										?>
										<select id="rights" name="rights" class="max-width-fit-75">
											<?php
											$hasOrphanTerm = true;
											if (!$collid) $hasOrphanTerm = false;
											$rightsCurrent = strtolower(substr($collData['rights'], strpos($collData['rights'], '//'), -4));
											foreach ($RIGHTS_TERMS as $k => $v) {
												$selectedTerm = '';
												$rightsValue = strtolower(substr($v, strpos($v, '//'), -4));
												if ($collid && $rightsCurrent == $rightsValue) {
													$selectedTerm = 'SELECTED';
													$hasOrphanTerm = false;
												}
												echo '<option value="' . $v . '" ' . $selectedTerm . '>' . $k . '</option>' . "\n";
											}
											if ($hasOrphanTerm && array_key_exists('rights', $collData)) {
												echo '<option value="' . $collData['rights'] . '" SELECTED>' . $collData['rights'] . ' [' . (isset($LANG['ORPHANED']) ? $LANG['ORPHANED'] : 'orphaned term') . ']</option>' . "\n";
											}
											?>
										</select>
										<?php
									}
									else {
										?>
										<input type="text" name="rights" value="<?php echo ($collid ? $collData['rights'] : ''); ?>" style="width:90%;" />
										<?php
									}
									?>
									<a id="rightsinfo" href="#" onclick="return false" tabindex="0">
										<img src="../../images/info.png" style="width:1.3em;" alt="Show more information button" title="<?php echo (isset($LANG['MORE_INFO_RIGHTS']) ? $LANG['MORE_INFO_RIGHTS'] : 'More information about Rights'); ?>"/>
									</a>
									<span id="rightsinfodialog" aria-live="polite">
										<?php
										echo (isset($LANG['LEGAL_DOC']) ? $LANG['LEGAL_DOC'] : '') . ' ';
										echo '<a href="http://rs.tdwg.org/dwc/terms/index.htm#dcterms:license" target="_blank">';
										echo (isset($LANG['DWC_DEF']) ? $LANG['DWC_DEF'] : 'Darwin Core definition') . '</a>.'
										?>
									</span>
								</span>
							</div>
							<div class="field-block">
								<span class="field-elem">
									<label for="rightsHolder"> <?php echo (isset($LANG['RIGHTS_HOLDER']) ? htmlspecialchars($LANG['RIGHTS_HOLDER'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'Rights Holder'); ?>: </label>
									<span class="screen-reader-only">
										<?php
											echo (isset($LANG['HOLDER_DEF']) ? $LANG['HOLDER_DEF'] : '') . ' ';
										?>
									</span>
									<input type="text" id="rightsHolder" name="rightsHolder" value="<?php echo ($collid ? $collData["rightsholder"] : ''); ?>" style="max-width: 100%; width:600px" />
									<a id="rightsholderinfo" href="#" onclick="return false" tabindex="0">
										<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>" title="<?php echo (isset($LANG['MORE_INFO_RIGHTS_H']) ? $LANG['MORE_INFO_RIGHTS_H'] : 'More information about Rights Holder'); ?>"/>
									</a>
									<span id="rightsholderinfodialog" aria-live="polite">
										<?php
										echo (isset($LANG['HOLDER_DEF']) ? $LANG['HOLDER_DEF'] : 'The organization or person managing or owning the rights of the resource. For more details, see') . ' ';
										echo '<a href="http://rs.tdwg.org/dwc/terms/index.htm#dcterms:rightsHolder" target="_blank">' . htmlspecialchars((isset($LANG['DWC_DEF']) ? $LANG['DWC_DEF'] : 'Darwin Core definition'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>.'
										?>
									</span>
								</span>
							</div>
							<div class="field-block">
								<span class="field-elem">
									<label for="accessRights"> <?php echo (isset($LANG['ACCESS_RIGHTS']) ? htmlspecialchars($LANG['ACCESS_RIGHTS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'Access Rights'); ?>: </label>
									<span class="screen-reader-only">
										<?php
											echo (isset($LANG['ACCESS_DEF']) ? $LANG['ACCESS_DEF'] : '') . ' ';
										?>
									</span>
									<input type="text" id="accessRights" name="accessRights" value="<?php echo ($collid ? $collData["accessrights"] : ''); ?>" style="max-width: 100%; width:600px" />
									<a id="accessrightsinfo" href="#" onclick="return false" tabindex="0">
										<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>" title="<?php echo htmlspecialchars((isset($LANG['MORE_INFO_ACCESS_RIGHTS']) ? $LANG['MORE_INFO_ACCESS_RIGHTS'] : 'More information about Access Rights'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"/>
									</a>
									<span id="accessrightsinfodialog" aria-live="polite">
										<?php
										echo (isset($LANG['ACCESS_DEF']) ? $LANG['ACCESS_DEF'] : 'Information or a URL link to page with details explaining how one can use the data. See') . ' ';
										echo '<a href="http://rs.tdwg.org/dwc/terms/index.htm#dcterms:accessRights" target="_blank">' . htmlspecialchars((isset($LANG['DWC_DEF']) ? $LANG['DWC_DEF'] : 'Darwin Core definition'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>.';
										?>
									</span>
								</span>
							</div>
							<?php
							if ($IS_ADMIN) {
								?>
								<div class="field-block">
									<span class="field-elem">
										<label for="collType"> <?php echo (isset($LANG['DATASET_TYPE']) ? htmlspecialchars($LANG['DATASET_TYPE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'Dataset Type'); ?>: </label>
										<select id="collType" name="collType">
											<option value="Preserved Specimens"><?php echo (isset($LANG['PRES_SPECS']) ? $LANG['PRES_SPECS'] : 'Preserved Specimens'); ?></option>
											<option value="Observations" <?php echo ($collid && $collData["colltype"] == 'Observations' ? 'SELECTED' : ''); ?>><?php echo $LANG['OBSERVATIONS']; ?></option>
											<option value="General Observations" <?php echo ($collid && $collData["colltype"] == 'General Observations' ? 'SELECTED' : ''); ?>><?php echo $LANG['PERS_OBS_MAN']; ?></option>
										</select>
										
										<a id="colltypeinfo" href="#" onclick="return false" tabindex="0">
											<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>" title="<?php echo (isset($LANG['MORE_COL_TYPE']) ? $LANG['MORE_COL_TYPE'] : 'More information about Collection Type'); ?>"/>
										</a>
										<span id="colltypeinfodialog" aria-live="polite">
											<?php echo (isset($LANG['COL_TYPE_DEF']) ? $LANG['COL_TYPE_DEF'] : ''); ?>
										</span>
									</span>
								</div>
								<div class="field-block">
									<fieldset>
										<legend> <?php echo htmlspecialchars($LANG['MANAGEMENT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>: </legend>
										
										<input class="top-breathing-room-rel-sm" id="snapshot" type="radio" name="managementType" value="Snapshot" CHECKED> <label for="snapshot">  <?php echo $LANG['SNAPSHOT']; ?> </label> <br/>
										<input id="liveData" type="radio" name="managementType" value="Live Data" <?php echo ($collid && $collData['managementtype'] == 'Live Data' ? 'CHECKED' : ''); ?>> <label for="liveData"> <?php echo $LANG['LIVE_DATA']; ?> </label> <br/>
										<input id="aggregate" type="radio" name="managementType" value="Aggregate" <?php echo ($collid && $collData['managementtype'] == 'Aggregate' ? 'CHECKED' : ''); ?>> <label for="aggregate"> <?php echo $LANG['AGGREGATE']; ?> </label>
										
										<a id="managementinfo" href="#" onclick="return false" tabindex="0">
											<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>" title="<?php echo (isset($LANG['MORE_INFO_TYPE']) ? $LANG['MORE_INFO_TYPE'] : 'More information about Management Type'); ?>"/>
										</a>

										<script src="../../js/symb/collections.misc.collmetadata.js"></script>
									</fieldset>
									<span id="managementinfodialog" aria-live="polite">
										<?php echo (isset($LANG['SNAPSHOT_DEF']) ? $LANG['SNAPSHOT_DEF'] : ''); ?>
									</span>
								</div>
								<?php
							}
							?>
							<div class="field-block">
									<fieldset>
										<legend> <?php echo (isset($LANG['GUID_SOURCE']) ? htmlspecialchars($LANG['GUID_SOURCE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'GUID source'); ?>: </legend>
										
										<input class="top-breathing-room-rel-sm" id="occurrenceId" type="radio" name="guidTarget" value="occurrenceId" CHECKED> <label for="occurrenceId">  <?php echo (isset($LANG['OCCURRENCE_ID']) ? $LANG['OCCURRENCE_ID'] : 'occurrenceID GUID'); ?> </label> <br/>
										<input id="catalogNumber" type="radio" name="guidTarget" value="catalogNumber" <?php echo ($collid && $collData['guidtarget'] == 'catalogNumber' ? 'CHECKED' : ''); ?>> <label for="catalogNumber"> <?php echo (isset($LANG['CAT_NUM']) ? $LANG['CAT_NUM'] : 'Catalog Number'); ?> </label> <br/>
										<input id="symbiotaUUID" type="radio" name="guidTarget" value="symbiotaUUID" <?php echo ($collid && $collData['guidtarget'] == 'symbiotaUUID' ? 'CHECKED' : ''); ?>> <label for="symbiotaUUID">  <?php echo (isset($LANG['SYMB_GUID']) ? $LANG['SYMB_GUID'] : 'Symbiota Generated GUID (UUID)'); ?> </label>
										
										<a id="guidinfo" href="#" onclick="return false" tabindex="0">
											<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>" title="<?php echo (isset($LANG['MORE_INFO_GUID']) ? $LANG['MORE_INFO_GUID'] : 'More information about Global Unique Identifier'); ?>"/>
										</a>

										<script src="../../js/symb/collections.misc.collmetadata.js"></script>
									</fieldset>
									<span id="guidinfodialog" aria-live="polite">
										<?php
										echo (isset($LANG['OCCID_DEF_1']) ? $LANG['OCCID_DEF_1'] : '');
										echo ' <a href="http://rs.tdwg.org/dwc/terms/index.htm#occurrenceID" target="_blank">';
										echo (isset($LANG['OCCURRENCEID']) ? $LANG['OCCURRENCEID'] : 'occurrenceId') . '</a>';
										echo (isset($LANG['OCCID_DEF_2']) ? ' ' . $LANG['OCCID_DEF_2'] : '');
										?>
									</span>
							</div>
							<?php
							if (isset($GBIF_USERNAME) && isset($GBIF_PASSWORD) && isset($GBIF_ORG_KEY) && $GBIF_ORG_KEY) {
								?>
								<div class="field-block">
									<span class="field-label"><?php echo (isset($LANG['PUBLISH_TO_AGGS']) ? $LANG['PUBLISH_TO_AGGS'] : 'Publish to Aggregators'); ?>:</span>
									<span class="field-elem">
										GBIF <input type="checkbox" name="publishToGbif" value="1" onchange="checkGUIDSource(this.form);" <?php echo ($collData['publishtogbif'] ? 'CHECKED' : ''); ?> />
										<a id="pubagginfo" href="#" onclick="return false" tabindex="0">
											<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>" title="More information about Publishing to Aggregators"/>
										</a>
										<!--
										<span>
											iDigBio <input type="checkbox" name="publishToIdigbio" value="1" onchange="checkGUIDSource(this.form);" <?php echo ($collData['publishtoidigbio'] ? 'CHECKED' : ''); ?> />
										</span>
										 -->
										<span id="pubagginfodialog" aria-live="polite">
											<?php echo (isset($LANG['ACTIVATE_GBIF']) ? $LANG['ACTIVATE_GBIF'] : 'Activates GBIF publishing tools available within Darwin Core Archive Publishing menu option'); ?>.
										</span>
									</span>
								</div>
								<?php
							}
							?>
							<div class="field-block">
								<div class="sourceurl-div" style="display:<?php echo ($collData["managementtype"] == 'Live Data' ? 'none' : 'inline'); ?>">
									<span class="field-label"><?php echo (isset($LANG['SOURCE_REC_URL']) ? $LANG['SOURCE_REC_URL'] : 'Source Record URL'); ?>:</span>
									
									<span class="field-elem">
										<input type="text" name="individualUrl" class="max-width-fit-75" value="<?php echo ($collid ? $collData["individualurl"] : ''); ?>" title="<?php echo (isset($LANG['DYNAMIC_LINK_REC']) ? $LANG['DYNAMIC_LINK_REC'] : 'Dynamic link to source database individual record page'); ?>" />
										<a id="sourceurlinfo" href="#" onclick="return false" tabindex="0">
											<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>" title="<?php echo (isset($LANG['MORE_INFO_SOURCE']) ? $LANG['MORE_INFO_SOURCE'] : 'More information about Source Records URL'); ?>"/>
										</a>
										<span id="sourceurlinfodialog" aria-live="polite">
											<?php
											echo (isset($LANG['ADVANCE_SETTING']) ? $LANG['ADVANCE_SETTING'] : '');
											echo ':http://swbiodiversity.org/seinet/collections/individual/index.php?occid=--DBPK--&quot; ';
											echo (isset($LANG['ADVANCE_SETTING_2']) ? $LANG['ADVANCE_SETTING_2'] : '');
											echo ' &quot;http://www.inaturalist.org/observations/--DBPK--&quot; ';
											echo (isset($LANG['ADVANCE_SETTING_3']) ? $LANG['ADVANCE_SETTING_3'] : '');
											?>
										</span>
									</span>
								</div>
							</div>
							<div class="field-block">
								<span class="field-elem">
									<span class="icon-elem" style="display:<?php echo (($collid && $collData["icon"]) ? 'none;' : 'inline'); ?>">
										<input type='hidden' name='MAX_FILE_SIZE' value='20000000' />
										<label for="iconFile"> <?php echo (isset($LANG['ICON_URL']) ? htmlspecialchars($LANG['ICON_URL'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'Icon URL'); ?>: </label>
										<input id="iconFile" name='iconFile' type='file' onchange="verifyIconImage(this.form);" />
									</span>
									<span class="icon-elem" style="display:<?php echo (($collid && $collData["icon"]) ? 'inline' : 'none'); ?>">
										<label for="iconurl"><?= $LANG['ICON_URL']; ?>: </label>
										<input style="max-width: 100%; width:600px;" type='text' name='iconUrl' id='iconurl' value="<?php echo ($collid ? $collData["icon"] : ''); ?>" onchange="verifyIconURL(this.form);" />
									</span>
									<a id="iconinfo" href="#" onclick="return false" title="<?php echo (isset($LANG['WHAT_ICON']) ? $LANG['WHAT_ICON'] : 'What is an Icon?'); ?>" tabindex="0"><img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>"/></a>
									<span id="iconinfodialog" aria-live="polite">
										<?php echo (isset($LANG['UPLOAD_ICON']) ? $LANG['UPLOAD_ICON'] : ''); ?>
									</span>
								</span>
								<span class="icon-elem" style="display:<?php echo (($collid && $collData["icon"]) ? 'none;' : 'inline'); ?>">
									<a href="#" onclick="toggle('icon-elem');return false;"><?php echo (isset($LANG['ENTER_URL']) ? $LANG['ENTER_URL'] : 'Enter URL'); ?></a>
								</span>
								<span class="icon-elem" style="display:<?php echo (($collid && $collData["icon"]) ? 'inline' : 'none;'); ?>">
									<a href="#" onclick="toggle('icon-elem');return false;">
										<?php echo (isset($LANG['UPLOAD_LOCAL']) ? $LANG['UPLOAD_LOCAL'] : 'Upload Local Image'); ?>
									</a>
								</span>
							</div>
							<?php
							if ($IS_ADMIN) {
								?>
								<div class="field-block" style="clear:both">
									<span class="field-elem">
										<label for="sortSeq"> <?php echo htmlspecialchars($LANG['SORT_SEQUENCE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>: </label>
										<span class="screen-reader-only">
											<?php
												echo (isset($LANG['LEAVE_IF_ALPHABET']) ? $LANG['LEAVE_IF_ALPHABET'] : 'Leave this field empty if you want the collections to sort alphabetically (default)') . ' ';
											?>
										</span>
										<input id="sortSeq" type="text" name="sortSeq" value="<?php echo ($collid ? $collData["sortseq"] : ''); ?>" />
										<a id="sortinfo" href="#" onclick="return false" tabindex="0">
											<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>" title="<?php echo (isset($LANG['MORE_SORTING']) ? $LANG['MORE_SORTING'] : 'More information about Sorting'); ?>"/>
										</a>
										<span id="sortinfodialog" aria-live="polite">
											<?php echo (isset($LANG['LEAVE_IF_ALPHABET']) ? $LANG['LEAVE_IF_ALPHABET'] : 'Leave this field empty if you want the collections to sort alphabetically (default)'); ?>
										</span>
									</span>
								</div>
								<?php
							}
							?>
							<div class="field-block">
								<span class="field-elem">
									<label for="collectionID"> <?php echo htmlspecialchars($LANG['COLLECTION_ID'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>: </label>
									<span class="screen-reader-only">
											<?php
												echo (isset($LANG['EXPLAIN_COLLID']) ? $LANG['EXPLAIN_COLLID'] : '') . ' ';
											?>
									</span>
									<input id="collectionID" type="text" name="collectionID" value="<?php echo ($collid ? $collData["collectionid"] : ''); ?>" style="max-width: 100%; width:400px" />
									<a id="collectionidinfo" href="#" onclick="return false" tabindex="0">
										<img src="../../images/info.png" style="width:1.3em;" alt="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'Show more information'); ?>" title="<?php echo (isset($LANG['MORE_INFO']) ? $LANG['MORE_INFO'] : 'More information'); ?>"/>
									</a>
									<span id="collectionidinfodialog" aria-live="polite">
										<?php echo (isset($LANG['EXPLAIN_COLLID']) ? $LANG['EXPLAIN_COLLID'] : 'Global Unique Identifier for this collection (see') .
											' <a href="https://dwc.tdwg.org/terms/#dwc:collectionID" target="_blank">' . htmlspecialchars((isset($LANG['DWC_COLLID']) ? $LANG['DWC_COLLID'] : 'dwc:collectionID'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) .
											'</a>): ' . (isset($LANG['EXPLAIN_COLLID_2']) ? $LANG['EXPLAIN_COLLID_2'] : 'If your collection already has a previously assigned GUID, that identifier should be represented here.
										For physical specimens, the recommended best practice is to use an identifier from a collections registry such as the
										Global Registry of Biodiversity Repositories') . ' (<a href="http://grbio.org" target="_blank">http://grbio.org</a>).';
										?>
									</span>
								</span>
							</div>
							<?php
							if ($collid) {
								?>
								<div class="field-block">
									<span class="field-label"><?php echo (isset($LANG['SECURITY_KEY']) ? $LANG['SECURITY_KEY'] : 'Security Key'); ?>:</span>
									<span class="field-elem">
										<?php echo $collData['securitykey']; ?>
									</span>
								</div>
								<div class="field-block">
									<span class="field-label"><?php echo (isset($LANG['RECORDID']) ? $LANG['RECORDID'] : 'recordID'); ?>:</span>
									<span class="field-elem">
										<?php echo $collData['recordid']; ?>
									</span>
								</div>
								<?php
							}
							?>
							<div class="field-block">
								<div style="margin:20px;">
									<?php
									if ($collid) {
										?>
										<input type="hidden" name="securityKey" value="<?php echo $collData['securitykey']; ?>" />
										<input type="hidden" name="recordID" value="<?php echo $collData['recordid']; ?>" />
										<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
										<button type="submit" name="action" value="saveEdits"><?php echo (isset($LANG['SAVE_EDITS']) ? $LANG['SAVE_EDITS'] : 'Save Edits'); ?></button>
										<?php
									}
									else {
										?>
										<button type="submit" name="action" value="newCollection"><?php echo (isset($LANG['CREATE_COLL_2']) ? $LANG['CREATE_COLL_2'] : 'Create New Collection'); ?></button>
										<?php
									}
									?>
								</div>
							</div>
						</form>
								</section>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>

</html>
