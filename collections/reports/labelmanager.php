<?php
include_once('../../config/symbini.php');
@include_once('Image/Barcode.php');
@include_once('Image/Barcode2.php');
include_once($SERVER_ROOT.'/classes/OccurrenceLabel.php');
include_once($SERVER_ROOT.'/content/lang/collections/reports/labelmanager.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/reports/labelmanager.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = $_REQUEST['collid'];
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

//Sanitation
if(!is_numeric($collid)) $collid = 0;

$labelManager = new OccurrenceLabel();
$labelManager->setCollid($collid);

$limit = (ini_get('max_input_vars')/2) - 100;
if(!$limit) $limit = 400;
elseif($limit > 1000) $limit = 1000;

$isEditor = 0;
$occArr = array();
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	$isEditor = 1;
}
elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
	$isEditor = 1;
}
if($isEditor){
	if($action == (isset($LANG['FILT_SPEC_REC']) ? $LANG['FILT_SPEC_REC'] : 'Filter Specimen Records')){
		$occArr = $labelManager->queryOccurrences($_POST, $limit);
	}
}
$labelFormatArr = $labelManager->getLabelFormatArr(true);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE; ?> <?php echo $LANG['SPEC_LABEL_MANAGER'] ?> </title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script type="text/javascript">
			<?php
			if($labelFormatArr) echo "var labelFormatObj = ".json_encode($labelFormatArr).";";
			?>

			function selectAll(cb){
				boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					dbElement.checked = boxesChecked;
				}
			}

			function validateQueryForm(f){
				if(!validateDateFields(f)){
					return false;
				}
				return true;
			}

			function validateDateFields(f){
				var status = true;
				var validformat1 = /^\s*\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd
				if(f.date1.value !== "" && !validformat1.test(f.date1.value)) status = false;
				if(f.date2.value !== "" && !validformat1.test(f.date2.value)) status = false;
				if(!status) alert("<?php echo (isset($LANG['ALERT_DATE']) ? $LANG['ALERT_DATE'] : 'Date entered must follow the format YYYY-MM-DD') ?>");
				return status;
			}

			function validateSelectForm(f){
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked){
						var quantityObj = document.getElementsByName("q-"+dbElement.value);
						if(quantityObj && quantityObj[0].value > 0) return true;
					}
				}
			   	alert("<?php echo (isset($LANG['ALERT_SPEC']) ? $LANG['ALERT_SPEC'] : 'At least one specimen checkbox needs to be selected with a label quantity greater than 0') ?>");
			  	return false;
			}

			function openIndPopup(occid){
				openPopup('../individual/index.php?occid=' + occid);
			}

			function openEditorPopup(occid){
				openPopup('../editor/occurrenceeditor.php?occid=' + occid);
			}

			function openPopup(urlStr){
				var wWidth = 900;
				if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.9;
				if(wWidth > 1200) wWidth = 1200;
				newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
				return false;
			}

			function changeFormExport(buttonElem, action, target){
				var f = buttonElem.form;
				if(action == "labeldynamic.php" && buttonElem.value == "<?php echo (isset($LANG['PRINT_BROWSER']) ? $LANG['PRINT_BROWSER'] : 'Print in Browser') ?>"){
					if(!f["labelformatindex"] || f["labelformatindex"].value == ""){
						alert("<?php echo (isset($LANG['ALERT_LABEL']) ? $LANG['ALERT_LABEL'] : 'Please select a Label Format Profile') ?>");
						return false;
					}
				}
				else if(action == "labelsword.php" && f.labeltype.valye == "packet"){
					alert("<?php echo (isset($LANG['ALERT_PACKET_LABEL']) ? $LANG['ALERT_PACKET_LABEL'] : 'Packet labels are not yet available as a Word document') ?>");
					return false;
				}
				if(f.bconly && f.bconly.checked && action == "labeldynamic.php") action = "barcodes.php";
				f.action = action;
				f.target = target;
				return true;
			}

			function checkPrintOnlyCheck(f){
				if(f.bconly.checked){
					f.speciesauthors.checked = false;
					f.catalognumbers.checked = false;
					f.bc.checked = false;
					f.symbbc.checked = false;
				}
			}

			function checkBarcodeCheck(f){
				if(f.bc.checked || f.symbbc.checked || f.speciesauthors.checked || f.catalognumbers.checked){
					f.bconly.checked = false;
				}
			}

			function labelFormatChanged(selObj){
				if(selObj && labelFormatObj){
					var catStr = selObj.value.substring(0,1);
					var labelIndex = selObj.value.substring(2);
					var f = document.selectform;
					if(catStr != ''){
						f.hprefix.value = labelFormatObj[catStr][labelIndex].labelHeader.prefix;
						var midIndex = labelFormatObj[catStr][labelIndex].labelHeader.midText;
						document.getElementById("hmid"+midIndex).checked = true;
						f.hsuffix.value = labelFormatObj[catStr][labelIndex].labelHeader.suffix;
						f.lfooter.value = labelFormatObj[catStr][labelIndex].labelFooter.textValue;
						if(labelFormatObj[catStr][labelIndex].displaySpeciesAuthor == 1) f.speciesauthors.checked = true;
						else f.speciesauthors.checked = false;
						if(f.bc){
							if(labelFormatObj[catStr][labelIndex].displayBarcode == 1) f.bc.checked = true;
							else f.bc.checked = false;
						}
						f.labeltype.value = labelFormatObj[catStr][labelIndex].labelType;
					}
				}
			}
		</script>
		<style>
			fieldset{ margin:10px; padding:15px; }
			fieldset legend{ font-weight:bold; }
			.fieldDiv{ clear:both; padding:5px 0px; margin:5px 0px }
			.fieldLabel{ font-weight: bold; display:block }
			.checkboxLabel{ font-weight: bold; }
			.fieldElement{  }
		</style>
	</head>
	<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'> <?php echo (isset($LANG['HOME']) ? $LANG['HOME'] : 'Home') ?> </a> &gt;&gt;
		<?php
		if(stripos(strtolower($labelManager->getMetaDataTerm('colltype')), "observation") !== false){
			echo '<a href="../../profile/viewprofile.php?tabindex=1">' . (isset($LANG["PERS_MANAG_MENU"]) ? $LANG["PERS_MANAG_MENU"] : "Personal Management Menu") . '</a> &gt;&gt; ';
		}
		else{
			echo '<a href="../misc/collprofiles.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">' . (isset($LANG["COLL_MANAG_PANEL"]) ? $LANG["COLL_MANAG_PANEL"] : "Collection Management Panel") . '</a> &gt;&gt; ';
		}
		?>
		<b> <?php echo (isset($LANG['LABEL_PRINT']) ? $LANG['LABEL_PRINT'] : 'Label Printing') ?> </b>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['SPEC_LABEL_MANAGER']; ?></h1>
		<?php
		if($isEditor){
			$reportsWritable = false;
			if(is_writable($SERVER_ROOT.'/temp/report')) $reportsWritable = true;
			if(!$reportsWritable){
				?>
				<div style="padding:5px;">
					<span style="color:red;"> <?php echo (isset($LANG['CONTACT_ADMIN']) ? $LANG['CONTACT_ADMIN'] : 'Please contact the site administrator to make temp/report folder writable in order to export to docx files.') ?></span>
				</div>
				<?php
			}
			$isGeneralObservation = (($labelManager->getMetaDataTerm('colltype') == 'General Observations')?true:false);
			echo '<h2>'.$labelManager->getCollName().'</h2>';
			?>
			<div>
				<form name="datasetqueryform" action="labelmanager.php" method="post" onsubmit="return validateQueryForm(this)">
					<fieldset>
						<legend><b> <?php echo (isset($LANG['DEF_SPEC_REC']) ? $LANG['DEF_SPEC_REC'] : 'Define Specimen Recordset') ?> </b></legend>
						<div style="margin:3px;">
							<div title="<?php echo (isset($LANG['DEF_SPEC_REC']) ? $LANG['DEF_SPEC_REC'] : 'Scientific name as entered in database.') ?>">
								<label for="taxa"> <?php echo (isset($LANG['SCI_NAME']) ? $LANG['SCI_NAME'] : 'Scientific Name: ') ?></label>
								<input type="text" name="taxa" id="taxa" size="60" value="<?php echo (array_key_exists('taxa',$_REQUEST)?$_REQUEST['taxa']:''); ?>" />
							</div>
						</div>
						<div style="margin:3px;clear:both;">
							<div style="float:left;" title="<?php echo (isset($LANG['FULL_NAME']) ? $LANG['FULL_NAME'] : 'Full or last name of collector as entered in database.') ?>">
								<label for="recordedby"><?php echo (isset($LANG['COLLECTOR']) ? $LANG['COLLECTOR'] : 'Collector:') ?></label>
								<input type="text" name="recordedby" id="recordedby" style="width:150px;" value="<?php echo (array_key_exists('recordedby',$_REQUEST)?$_REQUEST['recordedby']:''); ?>" />
							</div>
							<div style="float:left;margin-left:20px;" title="<?php echo (isset($LANG['SEPARATE_TERMS']) ? $LANG['SEPARATE_TERMS'] : 'Separate multiple terms by comma and ranges by \' - \' (space before and after dash required), e.g.: 3542,3602,3700 - 3750') ?>">
								<label for="recordnumber"><?php echo (isset($LANG['REC_NUM']) ? $LANG['REC_NUM'] : 'Record Number(s):') ?></label>
								<input type="text" name="recordnumber" id="recordnumber" style="width:150px;" value="<?php echo (array_key_exists('recordnumber',$_REQUEST)?$_REQUEST['recordnumber']:''); ?>" />
							</div>
							<div style="float:left;margin-left:20px;" title="<?php echo (isset($LANG['SEPARATE_TERMS']) ? $LANG['SEPARATE_TERMS'] : 'Separate multiple terms by comma and ranges by \' - \' (space before and after dash required), e.g.: 3542,3602,3700 - 3750') ?>">
								<label for="identifier"><?php echo (isset($LANG['CAT_NUM']) ? $LANG['CAT_NUM'] : 'Catalog Number(s):') ?></label>
								<input type="text" name="identifier" id="identifier" style="width:150px;" value="<?php echo (array_key_exists('identifier',$_REQUEST)?$_REQUEST['identifier']:''); ?>" />
							</div>
						</div>
						<div style="margin:3px;clear:both;">
							<div style="float:left;">
								<label for="recordenteredby"> <?php echo (isset($LANG['ENTER_BY']) ? $LANG['ENTER_BY'] : 'Entered by:') ?> </label>
								<input type="text" name="recordenteredby" id="recordenteredby" value="<?php echo (array_key_exists('recordenteredby',$_REQUEST)?$_REQUEST['recordenteredby']:''); ?>" style="width:100px;" title="<?php echo (isset($LANG['LOG_NAME']) ? $LANG['LOG_NAME'] : 'login name of data entry person') ?> " aria-label="<?php echo (isset($LANG['ENTER_BY']) ? $LANG['ENTER_BY'] : 'Entered by:') ?>" />
							</div>
							<div style="margin-left:20px;float:left;">
								<label for="date1"><?php echo (isset($LANG['DATE_RANGE']) ? $LANG['DATE_RANGE'] : 'Date range:') ?></label>
								<input type="text" name="date1" id="date1" style="width:100px;" value="<?php echo (array_key_exists('date1',$_REQUEST)?$_REQUEST['date1']:''); ?>" onchange="validateDateFields(this.form)" />
								<label for="date2"> <?php echo (isset($LANG['TO']) ? $LANG['TO'] : 'to') ?> </label>
								<input type="text" name="date2" id="date2" style="width:100px;" value="<?php echo (array_key_exists('date2',$_REQUEST)?$_REQUEST['date2']:''); ?>" onchange="validateDateFields(this.form)" />
								<label for="datetarget"><?php echo (isset($LANG['ITYPE_OF_DATE']) ? $LANG['TYPE_OF_DATE'] : 'Type of date'); ?>:</label>
								<select name="datetarget" id="datetarget">
									<option value="dateentered"><?php echo (isset($LANG['DATE_ENTERED']) ? $LANG['DATE_ENTERED'] : 'Date Entered') ?></option>
									<option value="datelastmodified" <?php echo (isset($_POST['datetarget']) && $_POST['datetarget'] == 'datelastmodified'?'SELECTED':''); ?>><?php echo (isset($LANG['DATE_MOD']) ? $LANG['DATE_MOD'] : 'Date Modified') ?></option>
									<option value="eventdate"<?php echo (isset($_POST['datetarget']) && $_POST['datetarget'] == 'eventdate'?'SELECTED':''); ?>><?php echo (isset($LANG['DATE_COLL']) ? $LANG['DATE_COLL'] : 'Date Collected') ?></option>
								</select>
							</div>
						</div>
						<div style="margin:3px;clear:both;">
							<label for="labelproject"> <?php echo (isset($LANG['LABEL_PROJ']) ? $LANG['LABEL_PROJ'] : 'Label Projects:') ?></label>
							<select name="labelproject" id="labelproject">
								<option value=""> <?php echo (isset($LANG['ALL_PROJ']) ? $LANG['ALL_PROJ'] : 'All Projects') ?> </option>
								<option value="">-------------------------</option>
								<?php
								$lProj = '';
								if(array_key_exists('labelproject',$_REQUEST)) $lProj = $_REQUEST['labelproject'];
								$lProjArr = $labelManager->getLabelProjects();
								foreach($lProjArr as $projStr){
									echo '<option '.($lProj==$projStr?'SELECTED':'').'>'.$projStr.'</option>'."\n";
								}
								?>
							</select>
							<!--
							Dataset Projects:
							<select name="datasetproject" >
								<option value=""></option>
								<option value="">-------------------------</option>
								<?php
								/*
								$datasetProj = '';
								if(array_key_exists('datasetproject',$_REQUEST)) $datasetProj = $_REQUEST['datasetproject'];
								$dProjArr = $labelManager->getDatasetProjects();
								foreach($dProjArr as $dsid => $dsProjStr){
									echo '<option id="'.$dsid.'" '.($datasetProj==$dsProjStr?'SELECTED':'').'>'.$dsProjStr.'</option>'."\n";
								}
								*/
								?>
							</select>
							-->
							<?php
							echo '<span style="margin-left:15px;"><input name="extendedsearch" id="extendedsearch" type="checkbox" value="1" '.(array_key_exists('extendedsearch', $_POST)?'checked':'').' /></span> ';
							?>
							<label for="extendedsearch">
							<?php
							if($isGeneralObservation) echo (isset($LANG['SEARCH_OUT']) ? $LANG['SEARCH_OUT'] : 'Search outside user profile');
							else echo (isset($LANG['SEARCH_IN']) ? $LANG['SEARCH_IN'] : 'Search within all collections');
							?>
							</label>
						</div>
						<div style="clear:both;">
							<div style="float:left;">
								<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
								<button type="submit" name="submitaction" value="<?php echo $LANG['FILT_SPEC_REC'] ?>">
									<?php echo $LANG['FILT_SPEC_REC'] ?>
								</button>
							</div>
							<div style="margin-left:20px;float:left;">
								* <?= (isset($LANG['SPEC_LIM']) ? $LANG['SPEC_LIM'] : 'Specimen return is limited to') ?>: <?= $limit ?>
							</div>
						</div>
					</fieldset>
				</form>
				<div style="clear:both;">
					<?php
					if($action == (isset($LANG['FILT_SPEC_REC']) ? $LANG['FILT_SPEC_REC'] : 'Filter Specimen Records')){
						if($occArr){
							?>
							<form name="selectform" id="selectform" action="labeldynamic.php" method="post" onsubmit="return validateSelectForm(this);">
								<table class="styledtable" style="font-family:Arial;font-size:12px;">
									<tr>
										<th title="Select/Deselect all Specimens"><input type="checkbox" onclick="selectAll(this);" /></th>
										<th title="Label quantity"> <?php echo (isset($LANG['QTY']) ? $LANG['QTY'] : 'Qty') ?> </th>
										<th> <?php echo (isset($LANG['COLLECTOR']) ? $LANG['COLLECTOR'] : 'Collector') ?> </th>
										<th> <?php echo (isset($LANG['SCI_NAME']) ? $LANG['SCI_NAME'] : ' Scientific Name') ?></th>
										<th> <?php echo (isset($LANG['LOCALITY']) ? $LANG['LOCALITY'] : 'Locality') ?></th>
									</tr>
									<?php
									$trCnt = 0;
									foreach($occArr as $occId => $recArr){
										$trCnt++;
										?>
										<tr <?php echo ($trCnt%2?'class="alt"':''); ?>>
											<td>
												<input type="checkbox" name="occid[]" value="<?php echo $occId; ?>" />
											</td>
											<td>
												<input type="text" name="q-<?php echo $occId; ?>" value="<?php echo $recArr["q"]; ?>" style="width:20px;border:inset;" title="<?php echo (isset($LANG['LABEL_QTY']) ? $LANG['LABEL_QTY'] : 'Label quantity') ?>" />
											</td>
											<td>
												<a href="#" onclick="openIndPopup(<?php echo $occId; ?>); return false;">
													<?php echo $recArr["c"]; ?>
												</a>
												<?php
												if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($recArr["collid"],$USER_RIGHTS["CollAdmin"])) || (array_key_exists("CollEditor",$USER_RIGHTS) && in_array($recArr["collid"],$USER_RIGHTS["CollEditor"]))){
													if(!$isGeneralObservation || $recArr['uid'] == $SYMB_UID){
														?>
														<a href="#" onclick="openEditorPopup(<?php echo $occId; ?>); return false;">
															<img src="../../images/edit.png" style="width:1.3em" />
														</a>
														<?php
													}
												}
												?>
											</td>
											<td>
												<?php echo $recArr["s"]; ?>
											</td>
											<td>
												<?php echo $recArr["l"]; ?>
											</td>
										</tr>
										<?php
									}
									?>
								</table>
								<fieldset style="margin-top:15px;">
									<legend> <?php echo (isset($LANG['LABEL_PRINT']) ? $LANG['LABEL_PRINT'] : ' Label Printing') ?></legend>
										<div class="fieldDiv">
											<div class="fieldLabel"> <?php echo (isset($LANG['LABEL_PROFILE']) ? $LANG['LABEL_PROFILE'] : 'Label Profiles:') ?>
												<?php
												echo '<span title="Open label profile manager"><a href="labelprofile.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><img src="../../images/edit.png" style="width:1.2em" /></a></span>';
												?>
											</div>
											<div class="fieldElement">
												<div>
													<select name="labelformatindex" onchange="labelFormatChanged(this)">
														<option value=""> <?php echo (isset($LANG['SEL_LABEL_FORMAT']) ? $LANG['SEL_LABEL_FORMAT'] : 'Select a Label Format') ?> </option>
														<?php
														foreach($labelFormatArr as $cat => $catArr){
															echo '<option value="">---------------------------</option>';
															foreach($catArr as $k => $labelArr){
																echo '<option value="'.$cat.'-'.$k.'">'.$labelArr['title'].'</option>';
															}
														}
														?>
													</select>
												</div>
												<?php
												if(!$labelFormatArr) echo '<b>' . (isset($LANG['LABEL_NOT_SET']) ? $LANG['LABEL_NOT_SET'] : 'label profiles have not yet been set within portal') . '</b>';
												?>
											</div>
										</div>
									<div class="fieldDiv">
										<div class="fieldLabel"> <?php echo (isset($LANG['HEAD_PREFIX']) ? $LANG['HEAD_PREFIX'] : 'Heading Prefix:') ?> </div>
										<div class="fieldElement">
											<input type="text" name="hprefix" value="" style="width:450px" /> <?php echo (isset($LANG['E_G_PLANTS']) ? $LANG['E_G_PLANTS'] : '(e.g. Plants of, Insects of, Vertebrates of)') ?>
										</div>
									</div>
									<div class="fieldDiv">
										<div class="checkboxLabel"> <?php echo (isset($LANG['HEAD_MID']) ? $LANG['HEAD_MID'] : 'Heading Mid-Section:') ?> </div>
										<div class="fieldElement">
											<input type="radio" id="hmid1" name="hmid" value="1" /> <?php echo (isset($LANG['COUNTRY']) ? $LANG['COUNTRY'] : 'Country') ?>
											<input type="radio" id="hmid2" name="hmid" value="2" /> <?php echo (isset($LANG['STATE']) ? $LANG['STATE'] : 'State') ?>
											<input type="radio" id="hmid3" name="hmid" value="3" /> <?php echo (isset($LANG['COUNTY']) ? $LANG['COUNTY'] : 'County') ?>
											<input type="radio" id="hmid4" name="hmid" value="4" /> <?php echo (isset($LANG['FAMILY']) ? $LANG['FAMILY'] : 'Family') ?>
											<input type="radio" id="hmid0" name="hmid" value="0" checked/> <?php echo (isset($LANG['BLANK']) ? $LANG['BLANK'] : 'Blank') ?>
										</div>
									</div>
									<div class="fieldDiv">
										<span class="fieldLabel"> <?php echo (isset($LANG['HEAD_SUFF']) ? $LANG['HEAD_SUFF'] : 'Heading Suffix:') ?> </span>
										<span class="fieldElement">
											<input type="text" name="hsuffix" value="" style="width:450px" />
										</span>
									</div>
									<div class="fieldDiv">
										<span class="fieldLabel"> <?php echo (isset($LANG['FOOTER']) ? $LANG['FOOTER'] : 'Footer:') ?> </span>
										<span class="fieldElement">
											<input type="text" name="lfooter" value="" style="width:450px" />
										</span>
									</div>
									<div class="fieldDiv">
										<input type="checkbox" name="speciesauthors" value="1" onclick="checkBarcodeCheck(this.form);" />
										<span class="checkboxLabel"> <?php echo (isset($LANG['PRINT_AUTH']) ? $LANG['PRINT_AUTH'] : 'Print species authors for infraspecific taxa') ?> </span>
									</div>
									<div class="fieldDiv">
										<input type="checkbox" name="catalognumbers" value="1" onclick="checkBarcodeCheck(this.form);" />
										<span class="checkboxLabel"> <?php echo (isset($LANG['PRINT_CAT_NUM']) ? $LANG['PRINT_CAT_NUM'] : 'Print Catalog Numbers') ?> </span>
									</div>
									<?php
									if(class_exists('Image_Barcode2') || class_exists('Image_Barcode')){
										?>
										<div class="fieldDiv">
											<input type="checkbox" name="bc" value="1" onclick="checkBarcodeCheck(this.form);" />
											<span class="checkboxLabel"> <?php echo (isset($LANG['INCL_BARCODE']) ? $LANG['INCL_BARCODE'] : 'Include barcode of Catalog Number') ?> </span>
										</div>
										<!--
										<div class="fieldDiv">
											<input type="checkbox" name="symbbc" value="1" onclick="checkBarcodeCheck(this.form);" />
											<span class="checkboxLabel">Include barcode of Symbiota Identifier</span>
										</div>
										 -->
										<div class="fieldDiv">
											<input type="checkbox" name="bconly" value="1" onclick="checkPrintOnlyCheck(this.form);" />
											<span class="checkboxLabel"> <?php echo (isset($LANG['PRINT_BARCODE']) ? $LANG['PRINT_BARCODE'] : 'Print only Barcode') ?> </span>
										</div>
										<?php
									}
									?>
									<div class="fieldDiv">
										<span class="fieldLabel"> <?php echo (isset($LANG['LABEL_TYPE']) ? $LANG['LABEL_TYPE'] : 'Label Type:') ?> </span>
										<span class="fieldElement">
											<select name="labeltype">
												<option value="1"> 1 <?php echo (isset($LANG['COLL_PAGE']) ? $LANG['COLL_PAGE'] : 'columns per page') ?> </option>
												<option value="2" selected>2 <?php echo (isset($LANG['COLL_PAGE']) ? $LANG['COLL_PAGE'] : 'columns per page') ?> </option>
												<option value="3">3 <?php echo (isset($LANG['COLL_PAGE']) ? $LANG['COLL_PAGE'] : 'columns per page') ?> </option>
												<option value="4">4 <?php echo (isset($LANG['COLL_PAGE']) ? $LANG['COLL_PAGE'] : 'columns per page') ?> </option>
												<option value="5">5 <?php echo (isset($LANG['COLL_PAGE']) ? $LANG['COLL_PAGE'] : 'columns per page') ?> </option>
												<option value="6">6 <?php echo (isset($LANG['COLL_PAGE']) ? $LANG['COLL_PAGE'] : 'columns per page') ?> </option>
												<option value="7">7 <?php echo (isset($LANG['COLL_PAGE']) ? $LANG['COLL_PAGE'] : 'columns per page') ?> </option>
												<option value="packet"><?php echo (isset($LANG['PACKET_LABEL']) ? $LANG['PACKET_LABEL'] : 'Packet labels') ?> </option>
											</select>
										</span>
									</div>
									<div style="float:left;margin: 15px 50px;">
										<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
										<div style="margin:10px">
											<input type="submit" name="submitaction" onclick="return changeFormExport(this,'labeldynamic.php','_blank');" value="<?php echo (isset($LANG['PRINT_BROWSER']) ? $LANG['PRINT_BROWSER'] : 'Print in Browser') ?>" <?php echo ($labelFormatArr?'':'DISABLED title="' . (isset($LANG["CONTACT_ADMIN"]) ? $LANG["CONTACT_ADMIN"] : "Browser based label printing has not been activated within the portal. Contact Portal Manager to activate this feature.") . '"'); ?> />
										</div>
										<div style="margin:10px">
											<input type="submit" name="submitaction" onclick="return changeFormExport(this,'labeldynamic.php','_self');" value="<?php echo (isset($LANG['EXP_CSV']) ? $LANG['EXP_CSV'] : 'Export to CSV') ?>" />
										</div>
										<?php
										if($reportsWritable){
											?>
											<div style="margin:10px">
												<input type="submit" name="submitaction" onclick="return changeFormExport(this,'labelsword.php','_self');" value="<?php echo (isset($LANG['EXP_DOCX']) ? $LANG['EXP_DOCX'] : 'Export to DOCX') ?>" />
											</div>
											<?php
										}
										?>
									</div>
										<?php
										if($reportsWritable){
											?>
											<div style="clear:both;padding:10px 0px">
												<b><?php echo (isset($LANG['NOTE']) ? $LANG['NOTE'] : 'Note:') ?></b> <?php echo (isset($LANG['NOTE_1']) ? $LANG['NOTE_1'] : 'Currently, Word (DOCX) output only generates the old static label format.') ?><br/><?php echo (isset($LANG['NOTE_2']) ? $LANG['NOTE_2'] : 'Output of variable Label Formats (pulldown options) as a Word document is not yet supported.') ?><br/>
												<?php echo (isset($LANG['NOTE_3']) ? $LANG['NOTE_3'] : 'A possible work around is to print labels as PDF and then convert to a Word doc using Adobe tools.') ?><br/>
												<?php echo (isset($LANG['NOTE_4']) ? $LANG['NOTE_4'] : 'Another alternatively, is to output the data as CSV and then setup a Mail Merge Word document.') ?>
											</div>
											<?php
										}
										?>
								</fieldset>
							</form>
							<?php
						}
						else{
							?>
							<div style="font-weight:bold;margin:20px;font-weight:150%;">
								<?php echo (isset($LANG['NO_DATA']) ? $LANG['NO_DATA'] : 'Query returned no data!') ?>
							</div>
							<?php
						}
					}
					?>
				</div>
			</div>
			<?php
		}
		else{
			?>
			<div style="font-weight:bold;margin:20px;font-weight:150%;">
				<?php echo (isset($LANG['NO_PERM']) ? $LANG['NO_PERM'] : 'You do not have permissions to print labels for this collection.
				Please contact the site administrator to obtain the necessary permissions.') ?>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
	</body>
</html>