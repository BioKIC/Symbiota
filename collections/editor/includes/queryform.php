<?php
if(!$displayQuery && array_key_exists('displayquery',$_REQUEST)) $displayQuery = $_REQUEST['displayquery'];
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/includes/queryform.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/queryform.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/queryform.en.php');

if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/specprocessor/exporter.' . $LANG_TAG . '.php')){
	include_once($SERVER_ROOT . '/content/lang/collections/specprocessor/exporter.' . $LANG_TAG . '.php');
} else{
	include_once($SERVER_ROOT . '/content/lang/collections/specprocessor/exporter.en.php');
}

$qryArr = $occManager->getQueryVariables();
// Construct a link containing the queryform search parameters
$queryLink = '?displayquery=1&collid='.$collId.'&'.http_build_query($qryArr, '', '&amp;');

$qCatalogNumber = (array_key_exists('cn',$qryArr)?$qryArr['cn']:'');
$qOtherCatalogNumbers = (array_key_exists('ocn',$qryArr)?$qryArr['ocn']:'');
$qRecordedBy = (array_key_exists('rb',$qryArr)?$qryArr['rb']:'');
$qRecordNumber = (array_key_exists('rn',$qryArr)?$qryArr['rn']:'');
$qEventDate = (array_key_exists('ed',$qryArr)?$qryArr['ed']:'');
$qRecordEnteredBy = (array_key_exists('eb',$qryArr)?$qryArr['eb']:'');
$qReturnAll = (array_key_exists('returnall',$qryArr)?$qryArr['returnall']:0);
$qProcessingStatus = (array_key_exists('ps',$qryArr)?$qryArr['ps']:'');
$qDateEntered = (array_key_exists('de',$qryArr)?$qryArr['de']:'');
$qDateLastModified = (array_key_exists('dm',$qryArr)?$qryArr['dm']:'');
$qExsiccatiId = (array_key_exists('exsid',$qryArr)?$qryArr['exsid']:'');
$qImgOnly = (array_key_exists('io',$qryArr)?$qryArr['io']:0);
$qWithoutImg = (array_key_exists('woi',$qryArr)?$qryArr['woi']:0);
$qOcrFrag = (array_key_exists('ocr',$qryArr)?htmlentities($qryArr['ocr'], ENT_COMPAT, $CHARSET):'');
$qOrderBy = (array_key_exists('orderby',$qryArr)?$qryArr['orderby']:'');
$qOrderByDir = (array_key_exists('orderbydir',$qryArr)?$qryArr['orderbydir']:'');
$qTraitIds = (array_key_exists('traitid',$qryArr)?$qryArr['traitid']: []);
$qTraitStateIds = (array_key_exists('stateid',$qryArr)?$qryArr['stateid']: []);

$qTraitArr = $occManager->getAttributeTraits($collId);

$customFieldArr = array();
if($crowdSourceMode){
	$customFieldArr = array('family'=>$LANG['FAMILY'],'sciname'=>$LANG['SCI_NAME'],'othercatalognumbers'=>$LANG['OTHER_CAT_NUMS'],
			'country'=>$LANG['COUNTRY'],'stateProvince'=>$LANG['STATE_PROVINCE'],'county'=>$LANG['COUNTY'],'municipality'=>$LANG['MUNICIPALITY'],
			'recordedby'=>$LANG['COLLECTOR'],'recordnumber'=>$LANG['COL_NUMBER'],'eventdate'=>$LANG['COL_DATE']);
}
else{
	$customFieldArr = array('associatedCollectors'=>$LANG['ASSOC_COLLECTORS'],'associatedOccurrences'=>$LANG['ASSOC_OCCS'],
			'associatedTaxa'=>$LANG['ASSOC_TAXA'],'attributes'=>$LANG['ATTRIBUTES'],'scientificNameAuthorship'=>$LANG['AUTHOR'],
			'basisOfRecord'=>$LANG['BASIS_OF_RECORD'],'behavior'=>$LANG['BEHAVIOR'],'catalogNumber'=>$LANG['CAT_NUM'],'collectionCode'=>$LANG['COL_CODE'],'recordNumber'=>$LANG['COL_NUMBER'],
			'recordedBy'=>$LANG['COL_OBS'],'continent'=>$LANG['CONTINENT'],'coordinateUncertaintyInMeters'=>$LANG['COORD_UNCERT_M'],'country'=>$LANG['COUNTRY'],
			'county'=>$LANG['COUNTY'],'cultivationStatus'=>$LANG['CULT_STATUS'],'dataGeneralizations'=>$LANG['DATA_GEN'],'eventDate'=>$LANG['DATE'],
			'dateEntered'=>$LANG['DATE_ENTERED'],'dateLastModified'=>$LANG['DATE_LAST_MODIFIED'],'dbpk'=>$LANG['DBPK'],'decimalLatitude'=>$LANG['DEC_LAT'],
			'decimalLongitude'=>$LANG['DEC_LONG'],'maximumDepthInMeters'=>$LANG['DEPTH_MAX'],'minimumDepthInMeters'=>$LANG['DEPTH_MIN'],
			'verbatimAttributes'=>$LANG['DESCRIPTION'],'disposition'=>$LANG['DISPOSITION'],'dynamicProperties'=>$LANG['DYNAMIC_PROPS'],
			'maximumElevationInMeters'=>$LANG['ELEV_MAX_M'],'minimumElevationInMeters'=>$LANG['ELEV_MIN_M'],
			'establishmentMeans'=>$LANG['ESTAB_MEANS'],'family'=>$LANG['FAMILY'],'fieldNotes'=>$LANG['FIELD_NOTES'],'fieldnumber'=>$LANG['FIELD_NUMBER'],
			'geodeticDatum'=>$LANG['GEO_DATUM'],'georeferenceProtocol'=>$LANG['GEO_PROTOCOL'],
			'georeferenceRemarks'=>$LANG['GEO_REMARKS'],'georeferenceSources'=>$LANG['GEO_SOURCES'],
			'georeferenceVerificationStatus'=>$LANG['GEO_VERIF_STATUS'],'georeferencedBy'=>$LANG['GEO_BY'],'habitat'=>$LANG['HABITAT'],
			'identificationQualifier'=>$LANG['ID_QUALIFIER'],'identificationReferences'=>$LANG['ID_REFERENCES'],
			'identificationRemarks'=>$LANG['ID_REMARKS'],'identifiedBy'=>$LANG['IDED_BY'],'individualCount'=>$LANG['IND_COUNT'],
			'informationWithheld'=>$LANG['INFO_WITHHELD'],'institutionCode'=>$LANG['INST_CODE'],'island'=>$LANG['ISLAND'],'islandgroup'=>$LANG['ISLAND_GROUP'],
			'labelProject'=>$LANG['LAB_PROJECT'],'language'=>$LANG['LANGUAGE'],'lifeStage'=>$LANG['LIFE_STAGE'],'locationid'=>$LANG['LOCATION_ID'],'locality'=>$LANG['LOCALITY'],
			'localitySecurity'=>$LANG['LOC_SEC'],'localitySecurityReason'=>$LANG['LOC_SEC_REASON'],'locationRemarks'=>$LANG['LOC_REMARKS'],
			'username'=>$LANG['MODIFIED_BY'],'municipality'=>$LANG['MUNICIPALITY'],'occurrenceRemarks'=>$LANG['NOTES_REMARKS'],'ocrFragment'=>$LANG['OCR_FRAGMENT'],
			'otherCatalogNumbers'=>$LANG['OTHER_CAT_NUMS'],'ownerInstitutionCode'=>$LANG['OWNER_CODE'],'preparations'=>$LANG['PREPARATIONS'],
			'reproductiveCondition'=>$LANG['REP_COND'],'samplingEffort'=>$LANG['SAMP_EFFORT'],'samplingProtocol'=>$LANG['SAMP_PROTOCOL'],
			'sciname'=>$LANG['SCI_NAME'],'sex'=>$LANG['SEX'],'stateProvince'=>$LANG['STATE_PROVINCE'],
			'substrate'=>$LANG['SUBSTRATE'],'taxonRemarks'=>$LANG['TAXON_REMARKS'],'typeStatus'=>$LANG['TYPE_STATUS'],'verbatimCoordinates'=>$LANG['VERBAT_COORDS'],
			'verbatimEventDate'=>$LANG['VERBATIM_DATE'],'verbatimDepth'=>$LANG['VERBATIM_DEPTH'],'verbatimElevation'=>$LANG['VERBATIM_ELE'],'waterbody'=> $LANG['WATER_BODY']);
}
$customTermArr = array('EQUALS', 'NOT_EQUALS', 'STARTS_WITH', 'LIKE', 'NOT_LIKE', 'GREATER_THAN', 'LESS_THAN', 'IS_NULL', 'NOT_NULL');
$customArr = array();
for($x=1; $x<9; $x++){
	if(array_key_exists('cao'.$x, $qryArr) && ($qryArr['cao'.$x] == 'AND' || $qryArr['cao'.$x] == 'OR')) $customArr[$x]['andor'] = $qryArr['cao'.$x];
	if(array_key_exists('cop'.$x, $qryArr) && preg_match('/^\({1,3}$/', $qryArr['cop'.$x])) $customArr[$x]['openparen'] = $qryArr['cop'.$x];
	if(array_key_exists('cf'.$x, $qryArr) && array_key_exists($qryArr['cf'.$x], $customFieldArr)) $customArr[$x]['field'] = $qryArr['cf'.$x];
	if(array_key_exists('ct'.$x, $qryArr) && in_array($qryArr['ct'.$x], $customTermArr)) $customArr[$x]['term'] = $qryArr['ct'.$x];
	if(array_key_exists('cv'.$x, $qryArr)) $customArr[$x]['value'] = htmlspecialchars($qryArr['cv'.$x], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
	if(array_key_exists('ccp'.$x, $qryArr) && preg_match('/^\){1,3}$/', $qryArr['ccp'.$x])) $customArr[$x]['closeparen'] = $qryArr['ccp'.$x];
}

//Set processing status
$processingStatusArr = array();
if(isset($PROCESSINGSTATUS) && $PROCESSINGSTATUS){
	$processingStatusArr = $PROCESSINGSTATUS;
}
else{
	$processingStatusArr = array('unprocessed','unprocessed/NLP','stage 1','stage 2','stage 3','pending review-nfn','pending review','expert required','reviewed','closed');
}
//if(!isset($_REQUEST['q_catalognumber'])) $displayQuery = true;
?>
<div id="querydiv" style="clear:both;width:fit-content; min-width: 900px; display:<?php echo ($displayQuery?'block':'none'); ?>;">
	<form name="queryform" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="post" onsubmit="return verifyQueryForm(this)">
		<fieldset style="padding:0.5rem; position: relative">
			<legend><?php echo $LANG['RECORD_SEARCH_FORM']; ?></legend>
			<button style="position: absolute; margin:0; right:0.5rem; top:0;" type="button" class="icon-button" onclick="copyQueryLink(event)" title="<?php echo $LANG['COPY_SEARCH']; ?>" aria-label="<?php echo $LANG['COPY_LINK']; ?>">
				<span style="display:flex; align-content: center;">
						<svg alt="Copies the search terms as a link." style="width:1.2em;margin-right:5px;" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M440-280H280q-83 0-141.5-58.5T80-480q0-83 58.5-141.5T280-680h160v80H280q-50 0-85 35t-35 85q0 50 35 85t85 35h160v80ZM320-440v-80h320v80H320Zm200 160v-80h160q50 0 85-35t35-85q0-50-35-85t-85-35H520v-80h160q83 0 141.5 58.5T880-480q0 83-58.5 141.5T680-280H520Z"/></svg>
						<span style="align-content: center;">
							<?php echo $LANG['COPY_LINK']; ?>
						</span>
				</span>
			</button>
			<?php
			if(!$crowdSourceMode){
				?>
				<div class="fieldGroupDiv">
					<div class="fieldDiv" title="<?php echo $LANG['WILD_EXPLAIN']; ?>">
						<label for="q_recordedby"><?php echo $LANG['COLLECTOR']; ?>:</label>
						<input type="text" name="q_recordedby" id="q_recordedby" value="<?php echo $qRecordedBy; ?>" onchange="setOrderBy(this)" />
					</div>
					<div class="fieldDiv" title="<?php echo $LANG['SEPARATE_RANGES']; ?>">
						<label for="q_recordnumber"><?php echo $LANG['NUMBER']; ?>:</label>
						<input type="text" name="q_recordnumber" id="q_recordnumber" value="<?php echo $qRecordNumber; ?>" style="width:120px;" onchange="setOrderBy(this)" />
							<label  title="<?php echo $LANG['ENTER_RANGES']; ?>" for="q_eventdate"><?php echo $LANG['DATE']; ?>:</label>
							<input type="text" name="q_eventdate" id="q_eventdate" value="<?php echo $qEventDate; ?>" style="width:160px" onchange="setOrderBy(this)" />
						</div>
					</div>
					
				<?php
			}
			?>
			<div class="fieldGroupDiv">
				<div class="fieldDiv" title="<?php echo $LANG['SEPARATE_RANGES']; ?>">
					<label for="q_catalognumber"><?php echo $LANG['CAT_NUM']; ?>:</label>
					<input type="text" name="q_catalognumber" id="q_catalognumber" value="<?php echo $qCatalogNumber; ?>" onchange="setOrderBy(this)" />
				</div>
				<?php
				if($crowdSourceMode){
					?>
					<div class="fieldDiv" title="Search for term embedded within OCR block of text">
						<label for="q_ocrfrag"></label><?php echo $LANG['OCR_FRAGMENT']; ?>:
						<input type="text" name="q_ocrfrag" id="q_ocrfrag" value="<?php echo $qOcrFrag; ?>" style="width:200px;" />
					</div>
					<?php
				}
				else{
					?>
					<div class="fieldDiv " title="<?php echo $LANG['SEPARATE_RANGES']; ?>">
						<label for="q_othercatalognumbers"><?php echo $LANG['OTHER_CAT_NUMS']; ?>:</label>
						<input type="text" name="q_othercatalognumbers" id="q_othercatalognumbers" value="<?php echo $qOtherCatalogNumbers; ?>" />
					</div>
					<?php
				}
				?>
			</div>
			<?php
			if(!$crowdSourceMode){
				?>
				<div class="fieldGroupDiv" <?php echo ($isGenObs?'style="display:none"':''); ?>>
					<div class="fieldDiv">
						<label for="q_recordenteredby"><?php echo $LANG['ENTERED_BY']; ?>:</label>
						<input type="text" name="q_recordenteredby" id="q_recordenteredby" value="<?php echo $qRecordEnteredBy; ?>" style="max-width:70px;" onchange="setOrderBy(this)" />
					</div>
					<button type="button" onclick="enteredByCurrentUser()" style="font-size:70%" title="<?php echo $LANG['LIMIT_TO_CURRENT']; ?>"><?php echo $LANG['CU']; ?></button>
					<div class="fieldDiv">
							<label title="<?php echo $LANG['ENTER_RANGES']; ?>" for="q_dateentered"><?php echo $LANG['DATE_ENTERED']; ?>:</label>
							<input type="text" name="q_dateentered" id="q_dateentered" value="<?php echo $qDateEntered; ?>" style="width:160px" onchange="setOrderBy(this)" />
					</div>
					<div class="fieldDiv" title="<?php echo $LANG['ENTER_RANGES']; ?>">
						<label for="q_datelastmodified"><?php echo $LANG['DATE_MODIFIED']; ?>:</label>
						<input type="text" name="q_datelastmodified" id="q_datelastmodified" value="<?php echo $qDateLastModified; ?>" style="width:160px" onchange="setOrderBy(this)" />
					</div>
				</div>
				<div class="fieldGroupDiv">
					<div class="fieldDiv">
						<label for="q_processingstatus"> <?php echo $LANG['PROC_STATUS']; ?>: </label>
						<select id="q_processingstatus" name="q_processingstatus" onchange="setOrderBy(this)">
							<option value=''><?php echo $LANG['ALL_RECORDS']; ?></option>
							<option>-------------------</option>
							<?php
							foreach($processingStatusArr as $v){
								//Don't display these options is editor is crowd sourced
								$keyOut = strtolower($v);
								echo '<option value="'.$keyOut.'" '.($qProcessingStatus==$keyOut?'SELECTED':'').'>'.ucwords($v).'</option>';
							}
							echo '<option value="isnull" '.($qProcessingStatus=='isnull'?'SELECTED':'').'>'.$LANG['NO_SET_STATUS'].'</option>';
							if($qProcessingStatus && $qProcessingStatus != 'isnull' && !in_array($qProcessingStatus,$processingStatusArr)){
								echo '<option value="'.$qProcessingStatus.'" SELECTED>'.$qProcessingStatus.'</option>';
							}
							?>
						</select>
					</div>
					<div class="fieldDiv">
						<input name="q_imgonly" id="q_imgonly" type="checkbox" value="1" <?php echo ($qImgOnly==1?'checked':''); ?> onchange="this.form.q_withoutimg.checked = false;" />
						<label for="q_imgonly"><?php echo $LANG['WITH_IMAGES']; ?></label>
					</div>
					<div class="fieldDiv">
						<input name="q_withoutimg" id="q_withoutimg" type="checkbox" value="1" <?php echo ($qWithoutImg==1?'checked':''); ?> onchange="this.form.q_imgonly.checked = false;" />
						<label for="q_withoutimg"><?php echo $LANG['WITHOUT_IMAGES']; ?></label>
					</div>
				</div>
				<?php if($qTraitArr): ?>
					<div style="margin-bottom: 10px">
						<div class="fieldGroupDiv">
							<div>
								<div>
									<?php echo $LANG['TRAIT_FILTER']; ?>:
								</div>
							</div>
							<div>
								<select name="q_traitid[]" multiple>
									<?php
										foreach($qTraitArr as $traitID => $tArr){
											echo '<option '. (in_array($traitID, $qTraitIds)? 'selected':'') .' value="'.$traitID.'">'.$tArr['name'].' [ID:'.$traitID.']</option>';
										}
									?>
								</select>
							</div>
							<div>
							-- <?php echo $LANG['OR_SPEC_ATTRIBUTE']; ?> --
							</div>
							<div>
								<select name="q_stateid[]" multiple>
									<?php
									foreach($qTraitArr as $traitID => $tArr){
										$stateArr = $tArr['state'];
										foreach($stateArr as $stateID => $stateName){
											echo '<option ' . (in_array($stateID, $qTraitStateIds)? 'selected':'') . ' value="'.$stateID.'">'.$tArr['name'].': '.$stateName.'</option>';
										}
									}
									?>
								</select>
							</div>
						</div>
						<div>
							* <?php echo $LANG['HOLD_CTRL']; ?>
						</div>
					</div>
				<?php endif ?>
				<?php
				if($ACTIVATE_EXSICCATI){
					if($exsList = $occManager->getExsiccatiList()){
						?>
						<div class="fieldGroupDiv" title="<?php echo $LANG['ENTER_EXS_TITLE']; ?>">
							<div class="fieldDiv">
								<?php echo $LANG['EXS_TITLE']; ?>:
								<select name="q_exsiccatiid" style="max-width:650px">
									<option value=""></option>
									<?php
									foreach($exsList as $exsID => $exsTitle){
										echo '<option value="'.$exsID.'" '.($qExsiccatiId==$exsID?'SELECTED':'').'>'.$exsTitle.'</option>';
									}
									?>
								</select>
							</div>
						</div>
						<?php
					}
				}
			}
			// sort($customFieldArr);
			for($x=1; $x<9; $x++){
				$cAndOr = ''; $cOpenParen = ''; $cCloseParen = ''; $cField = ''; $cTerm = ''; $cValue = '';
				if(isset($customArr[$x]['andor'])) $cAndOr = $customArr[$x]['andor'];
				if(isset($customArr[$x]['openparen'])) $cOpenParen = $customArr[$x]['openparen'];
				if(isset($customArr[$x]['closeparen'])) $cCloseParen = $customArr[$x]['closeparen'];
				if(isset($customArr[$x]['field'])) $cField = $customArr[$x]['field'];
				if(isset($customArr[$x]['term'])) $cTerm = $customArr[$x]['term'];
				if(isset($customArr[$x]['value'])) $cValue = $customArr[$x]['value'];

				$divDisplay = 'none';
				if($x == 1 || $cValue != '' || $cTerm == 'IS_NULL' || $cTerm == 'NOT_NULL') $divDisplay = '';
				?>
				<div id="customdiv<?php echo $x; ?>" class="fieldGroupDiv" <?php echo 'style="display:' . $divDisplay . '"' ?>>
					<?php echo $LANG['CUSTOM_FIELD'].' '.$x; ?>:
					<?php
					if($x > 1){
						?>
						<select name="q_customandor<?php echo $x; ?>" onchange="customSelectChanged(<?php echo $x; ?>)">
							<option value="AND"><?php echo $LANG['AND']; ?></option>
							<option <?php echo ($cAndOr == 'OR' ? 'SELECTED' : ''); ?> value="OR"><?php echo $LANG['OR']; ?></option>
						</select>
						<?php
					}
					?>
					<select name="q_customopenparen<?php echo $x; ?>" onchange="customSelectChanged(<?php echo $x; ?>)" aria-label="<?php echo $LANG['OPEN_PAREN_FIELD']; ?>">
						<option value="">---</option>
						<?php
						echo '<option '.($cOpenParen == '(' ? 'SELECTED' : '').' value="(">(</option>';
						if($x < 7) echo '<option '.($cOpenParen == '((' ? 'SELECTED' : '').' value="((">((</option>';
						if($x < 8) echo '<option '.($cOpenParen == '(((' ? 'SELECTED' : '').' value="(((">(((</option>';
						?>
					</select>
					<select name="q_customfield<?php echo $x; ?>" onchange="customSelectChanged(<?php echo $x; ?>)" aria-label="<?php echo $LANG['CRITERIA']; ?>">
						<option value=""><?php echo $LANG['SELECT_FIELD_NAME']; ?></option>
						<option value="">---------------------------------</option>
						<?php
						foreach($customFieldArr as $k => $v){
							echo '<option value="'.$k.'" '.($k == $cField ? 'SELECTED' : '').'>'.$v.'</option>';
						}
						?>
					</select>
					<select name="q_customtype<?php echo $x; ?>" aria-label="<?php echo $LANG['CONDITION']; ?>">
						<?php
						foreach($customTermArr as $term){
							echo '<option '.($cTerm == $term ? 'SELECTED' : '').' value="'.$term.'">'.$LANG[$term].'</option>' ;
						}
						?>
					</select>
					<input name="q_customvalue<?php echo $x; ?>" type="text" value="<?php echo $cValue; ?>" style="width:200px;" aria-label="<?php echo $LANG['CRITERIA']; ?>"/>
					<select name="q_customcloseparen<?php echo $x; ?>" onchange="customSelectChanged(<?php echo $x; ?>)" aria-label="<?php echo $LANG['CLOSE_PAREN_FIELD']; ?>">
						<option value="">---</option>
						<?php
						echo '<option '.($cCloseParen == ')' ? 'SELECTED' : '').' value=")">)</option>';
						if($x > 1) echo '<option '.($cCloseParen == '))' ? 'SELECTED' : '').' value="))">))</option>';
						if($x > 2) echo '<option '.($cCloseParen == ')))' ? 'SELECTED' : '').' value=")))">)))</option>';
						?>
					</select>
					<a href="#" onclick="toggleCustomDiv(<?php echo ($x+1); ?>);return false;">
						<img class="editimg" src="../../images/plus.png" style="width:1.2em;" alt="<?php echo htmlspecialchars($LANG['ADD_CUSTOM_FIELD'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" />
					</a>
				</div>
				<?php
			}
			?>
				<?php
				if($isGenObs && ($IS_ADMIN || ($collId && array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"])))){
					?>
					<div class="fieldGroupDiv">
						<div>
							<input type="checkbox" name="q_returnall" value="1" <?php echo ($qReturnAll?'CHECKED':''); ?> /> <?php echo $LANG['SHOW_RECS_ALL']; ?>
						</div>
					</div>
					<?php
				}
				?>
				<div>
					<div>

					<?php
					if(!$crowdSourceMode){
						$qryStr = '';
						if($qRecordedBy) $qryStr .= '&recordedby='.$qRecordedBy;
						if($qRecordNumber) $qryStr .= '&recordnumber='.$qRecordNumber;
						if($qEventDate) $qryStr .= '&eventdate='.$qEventDate;
						if($qCatalogNumber) $qryStr .= '&catalognumber='.$qCatalogNumber;
						if($qOtherCatalogNumbers) $qryStr .= '&othercatalognumbers='.$qOtherCatalogNumbers;
						if($qRecordEnteredBy) $qryStr .= '&recordenteredby='.$qRecordEnteredBy;
						if($qDateEntered) $qryStr .= '&dateentered='.$qDateEntered;
						if($qDateLastModified) $qryStr .= '&datelastmodified='.$qDateLastModified;
						if($qryStr){
					?>
						<a class="button button-primary icon-button bottom-breathing-room-rel" title="<?php echo $LANG['GO_LABEL_PRINT']; ?>" href="../reports/labelmanager.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . htmlspecialchars($qryStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank">
							<img src="../../images/list.png" style="width:1.3em" />
						</a>	
					<?php
						}
					}
					?>
					</div>
				</div>
				<div>
				<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
				<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
				<input type="hidden" name="occid" value="<?php echo $occManager->getOccId(); ?>" />
				<input type="hidden" name="occindex" value="<?php echo $occManager->getOccIndex(); ?>" />
				<input type="hidden" name="occidlist" value="<?php echo $occManager->getOccidIndexStr(); ?>" />
				<input type="hidden" name="direction" value="" />
				<section class="fieldGroupDiv">
					<div style="margin-left: 0;">
						<button name="submitaction" type="submit" onclick="submitQueryEditor(this.form)" ><?php echo $LANG['DISPLAY_EDITOR']; ?></button>
					</div>
					<div style="margin-left: 0;">
						<button name="submitaction" type="submit" onclick="submitQueryTable(this.form)" ><?php echo $LANG['DISPLAY_TABLE']; ?></button>
					</div>
					<div style="margin-left: 0;">
						<button type="button" name="reset" value="Reset Form" onclick="resetQueryForm(this.form)">Reset Form</button>
					</div>
				</section>
				<section class="fieldGroupDiv">
					<div class="" style="margin-left: 0;">
						<label for="orderby"><?php echo $LANG['SORT_BY']; ?>:</label>
						<select name="orderby" id="orderby">
							<option value=""></option>
							<option value="recordedby" <?php echo ($qOrderBy=='recordedby'?'SELECTED':''); ?>><?php echo $LANG['COLLECTOR']; ?></option>
							<option value="recordnumber" <?php echo ($qOrderBy=='recordnumber'?'SELECTED':''); ?>><?php echo $LANG['NUMBER']; ?></option>
							<option value="eventdate" <?php echo ($qOrderBy=='eventdate'?'SELECTED':''); ?>><?php echo $LANG['DATE']; ?></option>
							<option value="catalognumber" <?php echo ($qOrderBy=='catalognumber'?'SELECTED':''); ?>><?php echo $LANG['CAT_NUM']; ?></option>
							<option value="recordenteredby" <?php echo ($qOrderBy=='recordenteredby'?'SELECTED':''); ?>><?php echo $LANG['ENTERED_BY']; ?></option>
							<option value="dateentered" <?php echo ($qOrderBy=='dateentered'?'SELECTED':''); ?>><?php echo $LANG['DATE_ENTERED']; ?></option>
							<option value="datelastmodified" <?php echo ($qOrderBy=='datelastmodified'?'SELECTED':''); ?>><?php echo $LANG['DATE_LAST_MODIFIED']; ?></option>
							<option value="processingstatus" <?php echo ($qOrderBy=='processingstatus'?'SELECTED':''); ?>><?php echo $LANG['PROC_STATUS']; ?></option>
							<option value="sciname" <?php echo ($qOrderBy=='sciname'?'SELECTED':''); ?>><?php echo $LANG['SCI_NAME']; ?></option>
							<option value="family" <?php echo ($qOrderBy=='family'?'SELECTED':''); ?>><?php echo $LANG['FAMILY']; ?></option>
							<option value="country" <?php echo ($qOrderBy=='country'?'SELECTED':''); ?>><?php echo $LANG['COUNTRY']; ?></option>
							<option value="stateprovince" <?php echo ($qOrderBy=='stateprovince'?'SELECTED':''); ?>><?php echo $LANG['STATE_PROVINCE']; ?></option>
							<option value="county" <?php echo ($qOrderBy=='county'?'SELECTED':''); ?>><?php echo $LANG['COUNTY']; ?></option>
							<option value="municipality" <?php echo ($qOrderBy=='municipality'?'SELECTED':''); ?>><?php echo $LANG['MUNICIPALITY']; ?></option>
							<option value="locationid" <?php echo ($qOrderBy=='locationid'?'SELECTED':''); ?>><?php echo $LANG['LOCATION_ID']; ?></option>
							<option value="locality" <?php echo ($qOrderBy=='locality'?'SELECTED':''); ?>><?php echo $LANG['LOCALITY']; ?></option>
							<option value="decimallatitude" <?php echo ($qOrderBy=='decimallatitude'?'SELECTED':''); ?>><?php echo $LANG['DEC_LAT']; ?></option>
							<option value="decimallongitude" <?php echo ($qOrderBy=='decimallongitude'?'SELECTED':''); ?>><?php echo $LANG['DEC_LONG']; ?></option>
							<option value="minimumelevationinmeters" <?php echo ($qOrderBy=='minimumelevationinmeters'?'SELECTED':''); ?>><?php echo $LANG['ELEV_MIN']; ?></option>
							<option value="maximumelevationinmeters" <?php echo ($qOrderBy=='maximumelevationinmeters'?'SELECTED':''); ?>><?php echo $LANG['ELEV_MAX']; ?></option>
						</select>
					</div>
					<div>
						<label for="orderbydir"><?php echo $LANG['ORDER_BY'] ?>:</label>
						<select name="orderbydir" id="orderbydir">
							<option value="ASC"><?php echo $LANG['ASCENDING']; ?></option>
							<option value="DESC" <?php echo ($qOrderByDir=='DESC'?'SELECTED':''); ?>><?php echo $LANG['DESCENDING']; ?></option>
						</select>
					</div>
					<div >
						<label for="reclimit">
							<?php
							if(!isset($recLimit) || !$recLimit) $recLimit = 1000;
							echo $LANG['OUTPUT'] . ':';
							?>
						</label>
						<select name="reclimit" id="reclimit" >
							<option <?php echo ($recLimit==500?'selected':''); ?>>500</option>
							<option <?php echo ($recLimit==1000?'selected':''); ?>>1000</option>
							<option <?php echo ($recLimit==2000?'selected':''); ?>>2000</option>
							<option <?php echo ($recLimit==3000?'selected':''); ?>>3000</option>
						</select> <?php //echo $LANG['RECORDS']; ?>
					</div>
				</section>
				<div class="fieldGroupDiv">
					<input name="dynamictable" id="dynamictable-1" type="checkbox" value="1" <?php if(isset($dynamicTable) && $dynamicTable) echo 'checked'; ?> />
					<label for="dynamictable-1"><?php echo $LANG['DYNAMIC_TABLE']; ?></label>
				</div>
 			</div>
		</fieldset>
	</form>
</div>
<script>

	// Function to copy the query link to the clipboard
	function copyQueryLink(evt){

		// Prevent the button from triggering and reloading the page
		evt.preventDefault();

		// Get the queryform parameters, only the ones that are set
		var params = $('form[name="queryform"] :input').filter(function () { return $(this).val() != ""; }).serialize();

		// Check if the catalogNumber field is set, and if not, add it to the query
		var catalogNumber = $('input[name="q_catalognumber"]').val() == "" ? '&q_catalognumber=' : '';

		// Construct the full link to the query form search parameters. Add displayquery to show the query form
		var link = location.protocol + '//' + location.host + location.pathname + '?' + params + catalogNumber + '&displayquery=1';

		// Copy to clipboard
		navigator.clipboard.writeText(link).then(() => {
		  /* clipboard succcessfully set */
		  //console.log("Clipboard copy successful");
		}, () => {
		  /* clipboard write failed */
		  //console.log("Clipboard copy failed");
		});
	}

	function enteredByCurrentUser(){
		var f = document.queryform;
		resetQueryForm(f);
		f.q_recordenteredby.value = "<?php echo $GLOBALS['USERNAME']?>";
		var today = new Date();
		var dd = String(today.getDate()).padStart(2, '0');
		var mm = String(today.getMonth() + 1).padStart(2, '0');
		f.q_dateentered.value = today.getFullYear()+'-'+mm+'-'+dd;
	}

	function resetQueryForm(f){
		f.occid.value = "";
		f.occidlist.value = "";
		f.direction.value = "";
		f.occindex.value = "0";
		f.q_catalognumber.value = "";
		f.q_othercatalognumbers.value = "";
		f.q_recordedby.value = "";
		f.q_recordnumber.value = "";
		f.q_eventdate.value = "";
		f.q_recordenteredby.value = "";
		f.q_dateentered.value = "";
		f.q_datelastmodified.value = "";
		f.q_processingstatus.value = "";
		if(f.q_exsiccatiid) f.q_exsiccatiid.value = "";

		for(let x = 1; x < 9; x++){
			resetCustomElements(x);
			if(x > 1) document.getElementById("customdiv"+x).style.display = "none";
		}

		f.q_imgonly.checked = false;
		f.q_withoutimg.checked = false;
		f.orderby.value = "";
		f.orderbydir.value = "ASC";
	}
</script>
