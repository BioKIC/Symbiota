<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUpload.php');
include_once($SERVER_ROOT.'/classes/TaxonomyHarvester.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/taxa/taxonomy/batchloader.' . $LANG_TAG . '.php'))
	include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/batchloader.' . $LANG_TAG . '.php');
	else include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/batchloader.en.php');

header('Content-Type: text/html; charset=' . $CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/taxa/taxonomy/batchloader.php');
ini_set('max_execution_time', 3600);

$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : '';
$taxAuthId = (array_key_exists('taxauthid', $_REQUEST) ? filter_var($_REQUEST['taxauthid'], FILTER_SANITIZE_NUMBER_INT) : 1);
$kingdomName = (array_key_exists('kingdomname', $_REQUEST) ? htmlspecialchars($_REQUEST['kingdomname'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '');
$sciname = (array_key_exists('sciname', $_REQUEST) ? htmlspecialchars($_REQUEST['sciname'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '');
$targetApi = $_REQUEST['targetapi'] ?? '';
$rankLimit = (array_key_exists('ranklimit', $_REQUEST) ? filter_var($_REQUEST['ranklimit'], FILTER_SANITIZE_NUMBER_INT):'');

$isEditor = false;
if($IS_ADMIN || array_key_exists('Taxonomy', $USER_RIGHTS)){
	$isEditor = true;
}

$loaderManager = new TaxonomyUpload();
$loaderManager->setTaxaAuthId($taxAuthId);
$loaderManager->setKingdomName($kingdomName);

$fieldMap = Array();
if($isEditor){
	$ulFileName = array_key_exists('ulfilename',$_REQUEST)?$_REQUEST['ulfilename']:'';
	$ulOverride = array_key_exists('uloverride',$_REQUEST)?$_REQUEST['uloverride']:'';
	if($ulFileName){
		$loaderManager->setFileName($ulFileName);
	}
	else{
		$loaderManager->setUploadFile($ulOverride);
	}

	if(array_key_exists("sf",$_REQUEST)){
		//Grab field mapping, if mapping form was submitted
 		$targetFields = $_REQUEST["tf"];
 		$sourceFields = $_REQUEST["sf"];
		for($x = 0;$x<count($targetFields);$x++){
			if($targetFields[$x] && $sourceFields[$x]) $fieldMap[$sourceFields[$x]] = $targetFields[$x];
		}
	}

	if($action == 'downloadcsv'){
		$loaderManager->exportUploadTaxa();
		exit;
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['TAXA_LOADER']; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		var clientRoot = "<?php echo $CLIENT_ROOT; ?>";

		function toggle(target){
			var tDiv = document.getElementById(target);
			if(tDiv != null){
				if(tDiv.style.display=="none"){
					tDiv.style.display="block";
				}
			 	else {
			 		tDiv.style.display="none";
			 	}
			}
			else{
			  	var divs = document.getElementsByTagName("div");
			  	for (var i = 0; i < divs.length; i++) {
			  	var divObj = divs[i];
					if(divObj.className == target){
						if(divObj.style.display=="none"){
							divObj.style.display="block";
						}
					 	else {
					 		divObj.style.display="none";
					 	}
					}
				}
			}
		}

		function verifyItisUploadForm(f){
			if(f.uploadfile.value == "" && f.uloverride.value == ""){
				alert("<?php echo $LANG['ENTER_PATH']; ?>");
				return false;
			}
			return true;
		}

		function verifyUploadForm(f){
			var inputValue = f.uploadfile.value;
			if(inputValue == "") inputValue = f.uloverride.value;
			if(inputValue == ""){
				alert("<?php echo $LANG['ENTER_PATH'] ?>");
				return false;
			}
			else{
				if(inputValue.indexOf(".csv") == -1 && inputValue.indexOf(".CSV") == -1 && inputValue.indexOf(".zip") == -1){
					alert("<?php echo $LANG['UPLOAD_ZIP']; ?>");
					return false;
				}
			}
			if(f.kingdomname.value == ""){
				alert("<?php echo $LANG['SEL_KINGDOM']; ?>");
				return false;
			}
			return true;
		}

		function verifyMapForm(f){
			var sfArr = [];
			var tfArr = [];
			for(var i=0;i<f.length;i++){
				var obj = f.elements[i];
				if(obj.name == "sf[]"){
					if(sfArr.indexOf(obj.value) > -1){
						alert("<?php echo $LANG['ERROR_SOURCE_DUP']; ?>"+" "+obj.value+")");
						return false;
					}
					sfArr[sfArr.length] = obj.value;
				}
				else if(obj.value != "" && obj.value != "unmapped"){
					if(obj.name == "tf[]"){
						if(tfArr.indexOf(obj.value) > -1){
							alert("<?php echo $LANG['ERROR_TARGET']; ?>"+" ("+obj.value+")");
							return false;
						}
						tfArr[tfArr.length] = obj.value;
					}
				}
			}
			return true;
		}

		function checkTransferForm(f){
			return true;
		}

		function validateNodeLoaderForm(f){
			if(f.sciname.value == ""){
				alert("<?php echo $LANG['ENTER_TAX_NODE']; ?>");
				return false;
			}
			if(f.taxauthid.value == ""){
				alert("<?php echo $LANG['SEL_THESAURUS']; ?>");
				return false;
			}
			if(f.kingdomname.value == ""){
				alert("<?php echo $LANG['PLS_SEL_KINGDOM']; ?>");
				return false;
			}
			if($('input[name=targetapi]:checked').length == 0){
				alert("<?php echo $LANG['SEL_AUTHORITY']; ?>");
				return false;
			}
			return true;
		}
	</script>
	<script src="../../js/symb/api.taxonomy.taxasuggest.js?ver=4" type="text/javascript"></script>
	<style type="text/css">
		fieldset { width:90%; padding:10px 15px }
		legend { font-weight:bold; }
	</style>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_admin_taxaloaderMenu)?$taxa_admin_taxaloaderMenu:false);
include($SERVER_ROOT.'/includes/header.php');
?>
<div class="navpath">
	<a href="../../index.php"><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
	<a href="taxonomydisplay.php"><?php echo htmlspecialchars($LANG['BASIC_TREE_VIEWER'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
	<a href="taxonomydynamicdisplay.php"><?php echo htmlspecialchars($LANG['DYN_TREE_VIEWER'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
	<a href="batchloader.php"><b><?php echo htmlspecialchars($LANG['TAX_BATCH_LOADER'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></b></a>
</div>
<?php
if($isEditor){
	$rankArr = $loaderManager->getTaxonRankArr();
	?>
	<div role="main" id="innertext">
		<h1 class="page-heading"><?php echo $LANG['TAX_NAME_BATCH_LOADER']; ?></h1>
		<div style="margin:30px;">
			<div style="margin-bottom:30px;">
				<?php echo $LANG['TAX_UPLOAD_EXPLAIN1'] . ' '; ?>
				<a href="https://biokic.github.io/symbiota-docs/portal_manager/taxonomy/batch_load/">
					<?php echo htmlspecialchars($LANG['SYMB_DOC'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
				</a>
				<?php echo ' ' . htmlspecialchars($LANG['TAX_UPLOAD_EXPLAIN2'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
			</div>
			<?php
			if($action == 'mapInputFile' || $action == 'verifyMapping'){
				?>
				<form name="mapform" action="batchloader.php" method="post" onsubmit="return verifyMapForm(this)">
					<fieldset>
						<legend><?php echo $LANG['TAX_UPLOAD']; ?></legend>
						<div style="margin:10px;">
						</div>
						<table class="styledtable" style="width:450px">
							<tr>
								<th>
									<?php echo $LANG['SOURCE_FIELD']; ?>
								</th>
								<th>
									<?php echo $LANG['TARGET_FIELD']; ?>
								</th>
							</tr>
							<?php
							$translationMap = array('phylum'=>'division', 'division'=>'phylum', 'subphylum'=>'subdivision', 'subdivision'=>'subphylum', 'sciname'=>'scinameinput',
								'scientificname'=>'scinameinput', 'scientificnameauthorship'=>'author', 'vernacularname'=>'vernacular',
								'taxonid'=>'sourceid', 'parenttaxonid'=>'sourceparentid', 'parentscientificname'=>'parentstr',
								'acceptedtaxonid'=>'sourceacceptedid', 'acceptedscientificname'=>'acceptedstr', 'acceptedname'=>'acceptedstr'
							);
							$sArr = $loaderManager->getSourceArr();
							$tArr = $loaderManager->getTargetArr();
							asort($tArr);
							foreach($sArr as $sField){
								?>
								<tr>
									<td style='padding:2px;'>
										<?php
										echo $sField;
										$sField = strtolower($sField);
										$sTestField = str_replace(array(' ', '_'), '', $sField);
										if(isset($translationMap[$sTestField])) $sTestField = $translationMap[$sTestField];
										?>
										<input type="hidden" name="sf[]" value="<?php echo $sField; ?>" />
									</td>
									<td>
										<?php
										$selStr = '';
										$mappedTarget = (array_key_exists($sField,$fieldMap)?$fieldMap[$sField]:"");
										if($mappedTarget=='unmapped') $selStr = 'SELECTED';
										$optionStr = '<option value="unmapped" ' . $selStr . '>' . $LANG['LEAVE_UNMAPPED'] . '</option>';
										if($selStr) $selStr = 0;
										foreach($tArr as $k => $tField){
											if($selStr !== 0){
												$tTestField = strtolower($tField);
												if($mappedTarget && $mappedTarget == $k){
													$selStr = 'SELECTED';
												}
												elseif($tTestField == $sTestField && $tTestField != 'sciname'){
													$selStr = 'SELECTED';
												}
												elseif($sTestField == $k){
													$selStr = 'SELECTED';
												}
											}
											$optionStr .= '<option value="'.$k.'" '.($selStr?$selStr:'').'>'.$tField."</option>\n";
											if($selStr) $selStr = 0;
										}
										?>
										<select name="tf[]" style="background:<?php echo ($selStr !== '' ? '' : 'yellow'); ?>">
											<option value=""><?php echo $LANG['FIELD_UNMAPPED']; ?></option>
											<option value="">-------------------------</option>
											<?php
											echo $optionStr;
											?>
										</select>
									</td>
								</tr>
								<?php
							}
							?>
						</table>
						<div>
							* <?php echo $LANG['YELLOW_FIELDS']; ?>
						</div>
						<div style="margin-top:10px">
							<?php echo '<b>' . $LANG['TARGET_KINGDOM'] . ':</b> ' . $kingdomName . '<br/>'; ?>
							<?php echo '<b>' . $LANG['TARGET_THESAURUS'] . ':</b> ' . $loaderManager->getTaxAuthorityName(); ?>
						</div>
						<div style="margin:10px;">
							<button type="submit" name="action" value="verifyMapping"><?php echo $LANG['VERIFY_MAPPING']; ?></button>
							<button type="submit" name="action" value="uploadTaxa"><?php echo $LANG['UPLOAD_TAXA']; ?></button>
							<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>" />
							<input type="hidden" name="ulfilename" value="<?php echo $loaderManager->getFileName();?>" />
							<input type="hidden" name="kingdomname" value="<?php echo $kingdomName; ?>" />
						</div>
					</fieldset>
				</form>
				<?php
			}
			elseif($action == 'uploadTaxa' || $action == 'Upload ITIS File' || $action == 'Analyze Taxa'){
				echo '<ul>';
				if($action == 'uploadTaxa'){
					$loaderManager->loadFile($fieldMap);
					$loaderManager->cleanUpload();
				}
				elseif($action == 'Upload ITIS File'){
					$loaderManager->loadItisFile($fieldMap);
					$loaderManager->cleanUpload();
				}
				elseif($action == 'Analyze Taxa'){
					$loaderManager->cleanUpload();
				}
				$reportArr = $loaderManager->analysisUpload();
				echo '</ul>';
				?>
				<form name="transferform" action="batchloader.php" method="post" onsubmit="return checkTransferForm(this)">
					<fieldset style="width:450px;">
						<legend><?php echo $LANG['TRANSFER_TO_CENTRAL']; ?></legend>
						<div style="margin:10px;">
							<?php echo $LANG['REVIEW_BEFORE_ACTIVATE']; ?>
						</div>
						<div style="margin:10px">
							<?php echo $LANG['TARGET_KINGDOM'] . ': <b>' . $kingdomName . '</b><br/>'; ?>
							<?php echo $LANG['TARGET_THESAURUS'] . ': <b>' . $loaderManager->getTaxAuthorityName() . '</b>'; ?>
						</div>
						<div style="margin:10px;">
							<?php
							$statArr = $loaderManager->getStatArr();
							if($statArr){
								if(isset($statArr['upload'])) echo $LANG['TAXA_UPLOADED'] . ': <b>' . $statArr['upload'] . '</b><br/>';
								echo $LANG['TOTAL_TAXA'] . ': <b>' . $statArr['total'] . '</b> (' . $LANG['INCLUDES_PARENTS'] . ')<br/>';
								echo $LANG['TAXA_IN_THES'] . ': <b>' . (isset($statArr['exist'])?$statArr['exist']:0) . '</b><br/>';
								echo $LANG['NEW_TAXA'] . ': <b>' . (isset($statArr['new'])?$statArr['new']:0) . '</b><br/>';
								echo $LANG['ACCEPTED_TAXA'] . ': <b>' . (isset($statArr['accepted'])?$statArr['accepted']:0) . '</b><br/>';
								echo $LANG['NON_ACCEPTED_TAXA'] . ': <b>' . (isset($statArr['nonaccepted'])?$statArr['nonaccepted']:0) . '</b><br/>';
								if(isset($statArr['bad'])){
									?>
									<fieldset style="margin:15px;padding:15px;">
										<legend><b><?php echo $LANG['PROBLEM_TAXA']; ?></b></legend>
										<div style="margin-bottom:10px">
											<?php echo $LANG['TAXA_FAILED']; ?>
										</div>
										<?php
										foreach($statArr['bad'] as $msg => $cnt){
											echo '<div style="margin-left:10px">'.$msg.': <b>'.$cnt.'</b></div>';
										}
										?>
									</fieldset>
									<?php
								}
							}
							else{
								echo $LANG['STATS_NOT_AVAIL'];
							}
							?>
						</div>
						<!--
						<div style="margin:10px;">
							<label>Target Thesaurus:</label>
							<select name="taxauthid">
								<?php
								$taxonAuthArr = $loaderManager->getTaxAuthorityArr();
								foreach($taxonAuthArr as $k => $v){
									echo '<option value="' . $k . '" ' . ($k==$taxAuthId?'SELECTED':'') . '>' . $v . '</option>' . "\n";
								}
								?>
							</select>
						</div>
						-->
						<div style="margin:10px;">
							<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>" />
							<input name="kingdomname" type="hidden" value="<?php echo $kingdomName; ?>" />
							<button type="submit" name="action" value="activateTaxa"><?php echo $LANG['ACTIVATE_TAXA']; ?></button>
						</div>
						<div style="float:right;margin:10px;">
							<a href="batchloader.php?action=downloadcsv" target="_blank"><?php echo htmlspecialchars($LANG['DOWNLOAD_CSV'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
						</div>
					</fieldset>
				</form>
				<?php
			}
			elseif($action == 'activateTaxa'){
				echo '<ul>';
				$loaderManager->transferUpload($taxAuthId);
				echo '<li>' . $LANG['TAX_UPLOAD_SUCCESS'] . '</li>';
				echo '<li>' . $LANG['GO_TO'] . ' <a href="taxonomydisplay.php">' . $LANG['TAX_TREE_SEARCH'] . '</a> ' . $LANG['TO_QUERY'] . '</li>';
				echo '</ul>';
			}
			elseif($action == 'loadApiNode'){
				if($targetApi == 'col'){
					$harvester = new TaxonomyHarvester();
					$harvester->setVerboseMode(2);
					$harvester->setTaxAuthId($taxAuthId);
					$harvester->setKingdomName($kingdomName);
					if(isset($_REQUEST['dskey'])){
						echo '<fieldset>';
						echo '<legend>' . $LANG['ACTION_PANEL'] . '</legend>';
						$id = htmlspecialchars($_REQUEST['id'], HTML_SPECIAL_CHARS_FLAGS);
						$datasetKey = filter_var($_REQUEST['dskey'], FILTER_SANITIZE_NUMBER_INT);
						$harvester->addColNode($id, $datasetKey, $sciname, $rankLimit);
						echo '</fieldset>';
					}
					else{
						$targetArr = $harvester->fetchColNode($sciname);
						echo '<fieldset>';
						echo '<legend>' . $LANG['RESULT_TARGETS'] . '</legend>';
						if($targetArr){
							$numResults = $targetArr['number_results'];
							unset($targetArr['number_results']);
							echo '<div><b>' . $LANG['TARGET_TAXON'] . ':</b> ' . $sciname . '</div>';
							echo '<div><b>' . $LANG['KINGDOM'] . ':</b> ' . substr($kingdomName,strpos($kingdomName,':')+1) . '</div>';
							echo '<div><b>' . $LANG['LOWEST_RANK'] . ':</b> ' . $rankArr[$rankLimit] . '</div>';
							echo '<div><b>' . $LANG['SOURCE_LINK'] .':</b> <a href="https://www.catalogueoflife.org" target="_blank">https://www.catalogueoflife.org</a></div>';
							echo '<div><b>' . $LANG['TOTAL_RESULTS'] . ':</b> ' . $numResults . '</div>';
							echo '<div><hr/></div>';
							foreach($targetArr as $cbNameUsageID => $colArr){
								echo '<div style="margin-top:10px">';
								echo '<div><b>' . $LANG['ID'] . ':</b> '. $cbNameUsageID . '</div>';
								if(isset($colArr['error'])){
									echo '<div>' . $LANG['ERROR'] . ': ' . $colArr['error'] . '</div>';
								}
								else{
									echo '<div>' . $LANG['NAME'] . ': ' . $colArr['label'] . '</div>';
									echo '<div>' . $LANG['DATSET_KEY'] . ': <a href="https://api.catalogueoflife.org/dataset/' . htmlspecialchars($colArr['datasetKey'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($colArr['datasetKey'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
									echo '<div>'.(isset($LANG['STATUS'])?$LANG['STATUS']:'Status').': '.$colArr['status'].'</div>';
									if(isset($colArr['link'])) echo '<div>' . $LANG['SOURCE_LINK'] . ': <a href="' . $colArr['link'] . '" target="_blank">' . $colArr['link'] . '</a></div>';
									$targetStatus = '<span style="color:orange">' . $LANG['NOT_PREF'] . '</span>';
									if($colArr['isPreferred']) $targetStatus = '<span style="color:green">' . $LANG['PREF_TARGET'] . '</span>';
									echo '<div>' . $LANG['TARGET_STATUS'] . ': ' . $targetStatus . '</div>';
									if(isset($colArr['apiUrl'])) echo '<div>' . $LANG['API_URL'] . ': <a href="' . $colArr['apiUrl'] . '" target="_blank">' . $colArr['apiUrl'] . '</a></div>';
									if($colArr['datasetKey']){
										?>
										<form target="batchloader.php" method="post">
											<input type="hidden" name="id" value="<?= htmlspecialchars($cbNameUsageID, HTML_SPECIAL_CHARS_FLAGS) ?>">
											<input type="hidden" name="dskey" value="<?= filter_var($colArr['datasetKey'], FILTER_SANITIZE_NUMBER_INT) ?>">
											<input type="hidden" name="targetapi" value="col">
											<input type="hidden" name="taxauthid" value="<?= $taxAuthId ?>">
											<input type="hidden" name="kingdomname" value="<?= $kingdomName ?>">
											<input type="hidden" name="ranklimit" value="<?= $rankLimit ?>">
											<input type="hidden" name="sciname" value="<?= $sciname ?>">
											<button type="submit" name="action" value="loadApiNode" style="margin-top:10px"><?= $LANG['IMPORT_THIS_NODE'] ?></button>
										</form>
										<?php
									}
								}
								echo '</div>';
							}
						}
						else{
							echo $LANG['NO_VALID_COL'];
							return false;
						}
						echo '</fieldset>';
					}
				}
				elseif($targetApi == 'worms'){
					$harvester = new TaxonomyHarvester();
					$harvester->setVerboseMode(2);
					$harvester->setTaxAuthId($taxAuthId);
					$harvester->setKingdomName($kingdomName);
					echo '<ul>';
					if($harvester->addWormsNode($_POST)){
						echo '<li>' . $harvester->getTransactionCount() . ' ' . $LANG['TAXA_LOADED_SUCCESS'] . '</li>';
						echo '<li>' . $LANG['GO_TO'] . ' <a href="taxonomydisplay.php">' . htmlspecialchars($LANG['TAX_TREE_SEARCH'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> ' . htmlspecialchars($LANG['TO_QUERY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</li>';
					}
					echo '</ul>';
				}
			}
			?>
			<div>
				<form name="uploadform" action="batchloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyUploadForm(this)">
					<fieldset>
						<legend><?php echo $LANG['TAX_UPLOAD']; ?></legend>
						<div style="margin:10px;">
							<?php echo $LANG['TAX_UPLOAD_INSTRUCTIONS']; ?>
						</div>
						<input type='hidden' name='MAX_FILE_SIZE' value='100000000' />
						<div>
							<div class="overrideopt">
								<div style="margin:10px;">
									<input id="genuploadfile" name="uploadfile" type="file" size="40" />
								</div>
							</div>
							<div class="overrideopt" style="display:none;">
								<label><?php echo $LANG['FULL_FILE_PATH']; ?>:</label>
								<div style="margin:10px;">
									<input name="uloverride" type="text" size="50" /><br/>
									* <?php echo $LANG['FULL_FILE_EXPLAIN']; ?>
								</div>
							</div>
							<div style="margin:10px;">
								<label><?php echo $LANG['TARGET_THESAURUS']; ?>:</label>
								<select name="taxauthid">
									<?php
									$taxonAuthArr = $loaderManager->getTaxAuthorityArr();
									foreach($taxonAuthArr as $k => $v){
										echo '<option value="' . $k . '" ' . ($k==$taxAuthId?'SELECTED':'') . '>' . $v . '</option>' . "\n";
									}
									?>
								</select>
							</div>
							<div style="margin:10px;">
								<label><?php echo $LANG['TARGET_KINGDOM']; ?>:</label>
								<?php
								$kingdomArr = $loaderManager->getKingdomArr();
								echo '<select name="kingdomname">';
								echo '<option value="">' . $LANG['SEL_KINGDOM'] . '</option>';
								echo '<option value="">----------------------</option>';
								foreach($kingdomArr as $k => $kingName){
									echo '<option>'.$kingName.'</option>';
								}
								echo '</select>';
								?>
							</div>
							<div style="margin:10px;">
								<button type="submit" name="action" value="mapInputFile"><?php echo $LANG['MAP_INPUT_FILE']; ?></button>
							</div>
							<div style="float:right;" >
								<a href="#" onclick="toggle('overrideopt');return false;"><?php echo $LANG['TOGGLE_MANUAL']; ?></a>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<!--
			<div>
				<form name="itisuploadform" action="batchloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyItisUploadForm(this)">
					<fieldset>
						<legend>ITIS Upload File</legend>
						<div style="margin:10px;">
							ITIS data extract from the <a href="http://www.itis.gov/access.html" target="_blank">ITIS Download Page</a> can be uploaded
							using this function. Note that the file needs to be in their single file pipe-delimited format
							(example: <a href="CyprinidaeItisExample.bin">CyprinidaeItisExample.bin</a>).
							File might have .csv extension, even though it is NOT comma delimited.
							This upload option is not guaranteed to work if the ITIS download format change often.
							Large data files can be compressed as a ZIP file before import.
							If the file upload step fails without displaying an error message, it is possible that the
							file size exceeds the file upload limits set within your PHP installation (see your php configuration file).
							If synonyms and vernaculars are included, these data will also be incorporated into the upload process.
						</div>
						<input type='hidden' name='MAX_FILE_SIZE' value='100000000' />
						<div class="itisoverrideopt">
							<b>Upload File:</b>
							<div style="margin:10px;">
								<input id="itisuploadfile" name="uploadfile" type="file" size="40" />
							</div>
						</div>
						<div class="itisoverrideopt" style="display:none;">
							<b>Full File Path:</b>
							<div style="margin:10px;">
								<input name="uloverride" type="text" size="50" /><br/>
								* This option is for manual upload of a data file.
								Enter full path to data file located on working server.
							</div>
						</div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Upload ITIS File" />
						</div>
						<div style="float:right;">
							<a href="#" onclick="toggle('itisoverrideopt');return false;">Toggle Manual Upload Option</a>
						</div>
					</fieldset>
				</form>
			</div>
			-->
			<div>
				<form name="analyzeform" action="batchloader.php" method="post">
					<fieldset>
						<legend><?php echo $LANG['CLEAN_ANALYZE']; ?></legend>
						<div style="margin:10px;">
							<?php echo $LANG['CLEAN_ANALYZE_EXPLAIN']; ?>
						</div>
						<div style="margin:10px;">
							<label><?php echo $LANG['TARGET_THESAURUS']; ?>:</label>
							<select name="taxauthid">
								<?php
								$taxonAuthArr = $loaderManager->getTaxAuthorityArr();
								foreach($taxonAuthArr as $k => $v){
									echo '<option value="' . $k . '" ' . ($k==$taxAuthId?'SELECTED':'') . '>' . $v . '</option>' . "\n";
								}
								?>
							</select>
						</div>
						<div style="margin:10px;">
							<label><?php echo $LANG['TARGET_KINGDOM']; ?>:</label>
							<?php
							echo '<select name="kingdomname">';
							foreach($kingdomArr as $k => $kingName){
								echo '<option>' . $kingName . '</option>';
							}
							echo '</select>';
							?>
						</div>
						<div style="margin:10px;">
							<button type="submit" name="action" value="Analyze Taxa"><?php echo $LANG['ANALYZE_TAXA']; ?></button>
						</div>
					</fieldset>
				</form>
			</div>
			<div>
				<fieldset>
					<legend><?php echo $LANG['API_NODE_LOADER']; ?></legend>
					<form name="apinodeloaderform" action="batchloader.php" method="post" onsubmit="return validateNodeLoaderForm(this)">
						<div style="margin:10px;">
							<?php echo $LANG['API_NODE_LOADER_EXPLAIN'] ?>
						</div>
						<div style="margin:10px;">
							<fieldset style="padding:15px;margin:10px 0px">
								<legend><b><?php echo $LANG['TAX_RESOURCE'] ?></b></legend>
								<?php
								$taxApiList = $loaderManager->getTaxonomicResourceList();
								foreach($taxApiList as $taKey => $taValue){
									if($taKey == 'col' || $taKey == 'worms'){
										echo '<input name="targetapi" type="radio" value="' . $taKey . '" '.($targetApi == $taKey?'checked':'') . ' /> ' . $taValue . '<br/>';
									}
								}
								?>
							</fieldset>
						</div>
						<div style="margin:10px;">
							<label><?php echo $LANG['TARGET_NODE']; ?>:</label>
							<input id="taxa" name="sciname" type="text" value="<?php echo $sciname; ?>" />
						</div>
						<div style="margin:10px;">
							<label><?php echo $LANG['TAX_THESAURUS']; ?>:</label>
							<select name="taxauthid">
								<?php
								$taxonAuthArr = $loaderManager->getTaxAuthorityArr();
								foreach($taxonAuthArr as $k => $v){
									echo '<option value="' . $k . '" ' . ($k==$taxAuthId?'SELECTED':'') . '>' . $v . '</option>' . "\n";
								}
								?>
							</select>
						</div>
						<div style="margin:10px;">
							<label><?php echo $LANG['KINGDOM']; ?>:</label>
							<select name="kingdomname">
								<?php
								if($kingdomArr > 1){
									echo '<option value="">' . $LANG['SEL_KINGDOM'] . '</option>';
									echo '<option value="">-----------------------</option>';
								}
								foreach($kingdomArr as $k => $kName){
									$kKey = $k.':'.$kName;
									echo '<option value="' . $kKey . '" ' . ($kingdomName==$kKey?'selected':'') . '>' . $kName . '</option>';
								}
								?>
							</select>
						</div>
						<div style="margin:10px;">
							<label><?php echo $LANG['LOWEST_RANK']; ?></label>
							<select name="ranklimit">
								<option value="0"><?php echo $LANG['ALL_RANKS']; ?></option>
								<option>---------------------</option>
								<?php
								foreach($rankArr as $rankid => $rankName){
									echo '<option value="' . $rankid . '" ' . ($rankid==$rankLimit?'SELECTED':'') . '>' . $rankName . '</option>';
								}
								?>
							</select>
						</div>
						<div style="margin:10px;">
							<button id="submitButton" type="submit" name="action" value="loadApiNode"><?php echo $LANG['LOAD_NODE']; ?></button>
						</div>
					</form>
				</fieldset>
			</div>
		</div>
	</div>
	<?php
}
else{
	?>
	<div style='font-weight:bold;margin:30px;'>
		<?php echo $LANG['NO_PERMISSIONS']; ?>
	</div>
	<?php
}
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>