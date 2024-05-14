<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditReview.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/editreviewer.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/editreviewer.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/editreviewer.en.php');
header('Content-Type: text/html; charset='.$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/editreviewer.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT);
$displayMode = array_key_exists('display', $_REQUEST) ? filter_var($_REQUEST['display'], FILTER_SANITIZE_NUMBER_INT) : '1';
$faStatus = array_key_exists('fastatus', $_REQUEST) ? filter_var($_REQUEST['fastatus'], FILTER_SANITIZE_NUMBER_INT) : '';
$frStatus = array_key_exists('frstatus', $_REQUEST)? htmlspecialchars($_REQUEST['frstatus'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '1,2';
$filterFieldName = array_key_exists('ffieldname', $_REQUEST) ? htmlspecialchars($_REQUEST['ffieldname'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$editor = array_key_exists('editor', $_REQUEST) ? htmlspecialchars($_REQUEST['editor'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$queryOccid = array_key_exists('occid', $_REQUEST) ? filter_var($_REQUEST['occid'], FILTER_SANITIZE_NUMBER_INT) : '';
$startDate = array_key_exists('startdate', $_REQUEST) ? htmlspecialchars($_REQUEST['startdate'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$endDate = array_key_exists('enddate', $_REQUEST) ? htmlspecialchars($_REQUEST['enddate'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$pageNum = array_key_exists('pagenum', $_REQUEST) ? filter_var($_REQUEST['pagenum'], FILTER_SANITIZE_NUMBER_INT) : '0';
$limitCnt = array_key_exists('limitcnt', $_REQUEST) ? filter_var($_REQUEST['limitcnt'], FILTER_SANITIZE_NUMBER_INT) : '1000';
$recCnt = array_key_exists('reccnt', $_REQUEST) ? filter_var($_REQUEST['reccnt'], FILTER_SANITIZE_NUMBER_INT) : '';

$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$reviewManager = new OccurrenceEditReview();
$collName = $reviewManager->setCollId($collid);
$reviewManager->setDisplay($displayMode);
if(is_numeric($queryOccid)){
	$reviewManager->setQueryOccidFilter($queryOccid);
	$faStatus = '';
	$frStatus = 0;
}
else{
	$reviewManager->setAppliedStatusFilter($faStatus);
	$reviewManager->setReviewStatusFilter($frStatus);
}
$reviewManager->setFieldNameFilter($filterFieldName);
$reviewManager->setEditorFilter($editor);
$reviewManager->setStartDateFilter($startDate);
$reviewManager->setEndDateFilter($endDate);
$reviewManager->setPageNumber($pageNum);
$reviewManager->setLimitNumber($limitCnt);

$isEditor = false;
if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))){
 	$isEditor = true;
}
elseif($reviewManager->getObsUid()){
	$isEditor = true;
}

$statusStr = "";
if($isEditor){
	if($formSubmit == 'updateRecords'){
		if(!$reviewManager->updateRecords($_POST)){
			$warningArr = $reviewManager->getWarningArr();
			foreach($warningArr as $warningKey => $warningText){
				$statusStr .= $LANG[$warningKey] . ': ' . $warningText . '<br>';
			}
		}
	}
	elseif($formSubmit == 'deleteSelectedEdits'){
		$idStr = implode(',',$_POST['id']);
		if(!$reviewManager->deleteEdits($idStr)){
			$statusStr = $LANG['ERROR_DEL_EDITS'] . ': ' . $reviewManager->getErrorMessage();
		}
	}
	elseif($formSubmit == 'downloadSelectedEdits'){
		$idStr = implode(',',$_POST['id']);
		if($reviewManager->exportCsvFile($idStr)){
			exit();
		}
	}
	elseif($formSubmit == "downloadAllRecords"){
		if($reviewManager->exportCsvFile('', true)){
			exit();
		}
	}
}
if(!$recCnt) $recCnt = $reviewManager->getEditCnt();

$subCnt = $limitCnt*($pageNum + 1);
if($subCnt > $recCnt) $subCnt = $recCnt;
$navPageBase = 'editreviewer.php?collid='.$collid.'&display='.$displayMode.'&fastatus='.$faStatus.'&frstatus='.$frStatus.'&ffieldname='.$filterFieldName.
	'&startdate='.$startDate.'&enddate='.$endDate.'&editor='.$editor.'&reccnt='.$recCnt;

$navStr = '<div class="navbarDiv" style="float:right;">';
if($pageNum) $navStr .= '<a href="' . htmlspecialchars($navPageBase, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pagenum=' . htmlspecialchars(($pageNum-1), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&limitcnt=' . htmlspecialchars($limitCnt, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" title="' . htmlspecialchars($LANG['PREVIOUS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . htmlspecialchars($limitCnt, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . htmlspecialchars($LANG['RECORDS1'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">&lt;&lt;</a>';
else $navStr .= '&lt;&lt;';
$navStr .= ' | ';
$navStr .= ($pageNum*$limitCnt).'-'.$subCnt.' of '.$recCnt.' '.$LANG['FIELDS_EDITED'];
$navStr .= ' | ';
if($subCnt < $recCnt) $navStr .= '<a href="' . htmlspecialchars($navPageBase, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pagenum=' . htmlspecialchars(($pageNum+1), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&limitcnt=' . htmlspecialchars($limitCnt, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" title="' . htmlspecialchars($LANG['NEXT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . htmlspecialchars($limitCnt, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . htmlspecialchars($LANG['RECORDS2'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">&gt;&gt;</a>';
else $navStr .= '&gt;&gt;';
$navStr .= '</div>';
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $LANG['EDIT_REVIEWER']; ?></title>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
		<script>
			function validateFilterForm(f){
				if(f.startdate.value > f.enddate.value){
					alert("<?php echo $LANG['DATE_PROBLEM']; ?>");
					return false;
				}
				return true
			}

			function selectAllId(cbObj){
				var eElements = document.getElementsByName("id[]");
				for(i = 0; i < eElements.length; i++){
					var elem = eElements[i];
					if(cbObj.checked){
						elem.checked = true;
					}
					else{
						elem.checked = false;
					}
				}
			}

			function validateEditForm(f){
				if(validateEditSelection(f)){
					if(f.applytask.value == "" && f.rstatus.value == 0){
						alert("<?php echo $LANG['NO_ACTION']; ?>");
						return false;
					}
					return true;
				}
				return false;
			}

			function validateDelete(f){
				if(validateEditSelection(f)){
					return confirm("<?php echo $LANG['SURE_DELETE_HISTORY']; ?>");
				}
				return false;
			}

			function validateEditSelection(f){
				var elements = document.getElementsByName("id[]");
				for(i = 0; i < elements.length; i++){
					var elem = elements[i];
					if(elem.checked) return true;
				}
			   	alert("<?php echo $LANG['PLEASE_CHECK_EDIT']; ?>");
		      	return false;
			}

			function printFriendlyMode(status){
				if(status){
					$(".navpath").hide();
					$(".header").hide();
					$(".navbarDiv").hide();
					$(".returnDiv").show();
					$("#filterDiv").hide();
					$("#actionDiv").hide();
					$(".footer").hide();
				}
				else{
					$(".navpath").show();
					$(".header").show();
					$(".navbarDiv").show();
					$(".returnDiv").hide();
					$("#filterDiv").show();
					$("#actionDiv").show();
					$(".footer").show();
				}
			}

			function openIndPU(occid,clid){
				var newWindow = window.open('../editor/occurrenceeditor.php?occid='+occid,'indspec' + occid,'scrollbars=1,toolbar=0,resizable=1,width=1000,height=700,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
			}
		</script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/shared.js" type="text/javascript" ></script>
		<style>
			#filterDiv{ width:450px; }
			.fieldDiv{ margin:3px; }
		</style>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/includes/header.php');
		echo '<div class="navpath">';
		echo '<a href="../../index.php">' . htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> &gt;&gt; ';
		if($reviewManager->getObsUid()){
			echo '<a href="../../profile/viewprofile.php?tabindex=1">' . htmlspecialchars($LANG['PERS_SPEC_MNG'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> &gt;&gt; ';
		}
		else{
			echo '<a href="../misc/collprofiles.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">' . htmlspecialchars($LANG['COL_MAN_PAN'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> &gt;&gt; ';
		}
		echo '<b>'.$LANG['EDIT_REVIEWER'].'</b>';
		echo '</div>';
		?>
		<!-- This is inner text! -->
		<div role="main" id="innertext" style="min-width:1100px">
			<h1 class="page-heading">Occurrence Edit Reviewer</h1>
			<?php
			if($collid && $isEditor){
				?>
				<div style="font-weight:bold;font-size:130%;"><?php echo $collName; ?></div>
				<?php
				if($statusStr){
					?>
					<div style='margin:20px;font-weight:bold;color:red;'>
						<?php echo $statusStr; ?>
					</div>
					<?php
				}
				$retToMenuStr = '<div class="returnDiv" style="display:none"><b><a href="#" onclick="printFriendlyMode(false)">Exit Print Mode</a></b></div>';
				echo $retToMenuStr;
				?>
				<div id="filterDiv" style="float:right;">
					<form name="filter" action="editreviewer.php" method="post" onsubmit="return validateFilterForm(this)">
						<fieldset>
							<legend><b><?php echo $LANG['FILTER']; ?></b></legend>
							<div class="fieldDiv">
								<label for="fastatus"> <?php echo $LANG['APPLIED_STATUS']; ?>: </label>
								<select id="fastatus" name="fastatus">
									<option value=""><?php echo $LANG['ALL_RECS']; ?></option>
									<option value="0" <?php echo ($faStatus=='0'?'SELECTED':''); ?>><?php echo $LANG['NOT_APPLIED']; ?></option>
									<option value="1" <?php echo ($faStatus=='1'?'SELECTED':''); ?>><?php echo $LANG['APPLIED']; ?></option>
								</select>
							</div>
							<div class="fieldDiv">
								<label for="frstatus"> <?php echo $LANG['REVIEW_STATUS']; ?>: </label>
								<select id="frstatus"name="frstatus">
									<option value="0"><?php echo $LANG['ALL_RECS']; ?></option>
									<option value="1,2" <?php echo ($frStatus=='1,2'?'SELECTED':''); ?>><?php echo $LANG['OPEN_PENDING']; ?></option>
									<option value="1" <?php echo ($frStatus=='1'?'SELECTED':''); ?>><?php echo $LANG['OPEN_ONLY']; ?></option>
									<option value="2" <?php echo ($frStatus=='2'?'SELECTED':''); ?>><?php echo $LANG['PENDING_ONLY']; ?></option>
									<option value="3" <?php echo ($frStatus=='3'?'SELECTED':''); ?>><?php echo $LANG['CLOSED']; ?></option>
								</select>
							</div>
							<div class="fieldDiv">
								<label for="ffieldname"> <?php echo $LANG['FIELD_NAME']; ?>: </label>
								<select id="ffieldname" name="ffieldname">
									<option value=""><?php echo $LANG['ALL_FIELDS']; ?></option>
									<option value="">----------------------</option>
									<?php
									$fieldList = $reviewManager->getFieldList();
									foreach($fieldList as $fName){
										echo '<option '.($filterFieldName==$fName?'SELECTED':'').'>'.$fName.'</option>';
									}
									?>
								</select>
							</div>
							<div class="fieldDiv">
								<label for="editor"> <?php echo $LANG['EDITOR']; ?>: </label>
								<select id="editor" name="editor">
									<option value=""><?php echo $LANG['ALL_EDITORS']; ?></option>
									<option value="">----------------------</option>
									<?php
									$editorArr = $reviewManager->getEditorList();
									foreach($editorArr as $id => $e){
										echo '<option value="'.$id.'" '.($editor==$id?'SELECTED':'').'>'.$e.'</option>';
									}
									?>
								</select>
							</div>
							<div class="fieldDiv">
								<label for="startdate"> <?php echo $LANG['DATE']; ?>: </label>
								<input id="startdate" name="startdate" type="date" value="<?php echo $startDate; ?>" aria-label="<?php echo $LANG['START_DATE']; ?>" /> -
								<input name="enddate" type="date" value="<?php echo $endDate; ?>" aria-label="<?php echo $LANG['END_DATE']; ?>"/>
							</div>
							<?php
							if($reviewManager->hasRevisionRecords() && !$reviewManager->getObsUid()){
								?>
								<div class="fieldDiv">
									<label for="display"> <?php echo $LANG['EDITING_SOURCE']; ?>: </label>
									<select id="display" name="display">
										<option value="1"><?php echo $LANG['INTERNAL']; ?></option>
										<option value="2" <?php if($displayMode == 2) echo 'SELECTED'; ?>><?php echo $LANG['EXTERNAL']; ?></option>
									</select>
								</div>
								<?php
							}
							?>
							<div style="margin:10px;">
								<button name="submitbutton" type="submit" value="submitfilter"><?php echo $LANG['SUBMIT_FILTER']; ?></button>
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							</div>
							<!--
							<div class="fieldDiv">
								Records per page: <input name="limitcnt" type="text" value="<?php echo $limitCnt; ?>" style="width:60px" />
							</div>
							 -->
						</fieldset>
					</form>
				</div>
				<form name="editform" action="editreviewer.php" method="post" >
					<div id="actionDiv" style="margin:10px;float:left;">
						<fieldset>
							<legend><b><?php echo $LANG['ACTION_PANEL']; ?></b></legend>
							<div style="margin:10px 10px;">
								<div style="float:left;margin-bottom:10px;">
									<input id="asIs" name="applytask" type="radio" value="" CHECKED> <label for="asIs"> <?php echo $LANG['LEAVE_AS_IS']; ?> </label> <br/>
									<input id="apply" name="applytask" type="radio" value="apply"> <label for="apply"> <?php echo $LANG['APPLY_EDITS']; ?> </label> <br/>
									<input id="revert" name="applytask" type="radio" value="revert"> <label for="revert"> <?php echo $LANG['REVERT_EDITS']; ?> </label>
								</div>
								<div style="float:left;margin-left:30px;">
									<label for="rstatus"> <b><?php echo $LANG['REVIEW_STATUS']; ?>:</b> </label>
									<select id="rstatus" name="rstatus">
										<option value="0"><?php echo $LANG['LEAVE_AS_IS']; ?></option>
										<option value="1"><?php echo $LANG['OPEN']; ?></option>
										<option value="2"><?php echo $LANG['PENDING']; ?></option>
										<option value="3"><?php echo $LANG['C_CLOSED']; ?></option>
									</select>
								</div>
								<div style="clear:both;margin:15px 5px;">
									<button name="formsubmit" type="submit" value="updateRecords" onclick="return validateEditForm(this.form);"><?php echo $LANG['UPDATE_SELECTED']; ?></button>
									<input name="collid" type="hidden" value="<?php echo $collid; ?>">
									<input name="fastatus" type="hidden" value="<?php echo $faStatus; ?>">
									<input name="frstatus" type="hidden" value="<?php echo $frStatus; ?>">
									<input name="ffieldname" type="hidden" value="<?php echo $filterFieldName; ?>">
									<input name="editor" type="hidden" value="<?php echo $editor; ?>">
									<input name="startdate" type="hidden" value="<?php echo $startDate; ?>">
									<input name="enddate" type="hidden" value="<?php echo $endDate; ?>">
									<input name="occid" type="hidden" value="<?php echo $queryOccid; ?>">
									<input name="pagenum" type="hidden" value="<?php echo $pageNum; ?>">
									<input name="limitcnt" type="hidden" value="<?php echo $limitCnt; ?>">
									<input name="display" type="hidden" value="<?php echo $displayMode; ?>">
								</div>
							</div>
							<div style="clear:both;margin:15px 0px;">
								<hr/>
								<a href="#" onclick="toggle('additional')"><b><?php echo $LANG['ADDITIONAL_ACTIONS']; ?></b></a>
							</div>
							<div id="additional" style="display:none">
								<div style="margin:10px 15px;">
									<button name="formsubmit" type="submit" value="deleteSelectedEdits" onclick="return validateDelete(this.form)"><?php echo $LANG['DELETE_SELECTED']; ?></button>
									<div style="margin:5px 0px 10px 10px;">* <?php echo $LANG['PERMANENTLY_CLEAR']; ?></div>
								</div>
								<div style="margin:5px 0px 10px 15px;">
									<button name="formsubmit" type="submit" value="downloadSelectedEdits" onclick="return validateEditSelection(this.form);" ><?php echo $LANG['DOWNLOAD_SELECTED']; ?></button>
								</div>
								<div style="margin:5px 0px 10px 15px;">
									<button name="formsubmit" type="submit" value="downloadAllRecords"><?php echo $LANG['DOWNLOAD_ALL']; ?></button>
									<div style="margin:5px 0px 10px 10px;">* <?php echo $LANG['BASED_ON_PARAMETERS']; ?></div>
								</div>
								<div style="margin:10px 15px;">
									<button name="printsubmit" type="button" onclick="printFriendlyMode(true)"><?php echo $LANG['PRINT_FRIENDLY']; ?></button>
								</div>
							</div>
						</fieldset>
					</div>
					<?php
					echo '<div style="clear:both">'.$navStr.'</div>';
					?>
					<table class="styledtable" style="font-family:Arial;font-size:1.25rem;" aria-label="<?php echo (isset($LANG['TABLE']) ? $LANG['TABLE'] : 'Table of Records'); ?>" aria-labeledby="table-desc">
						<caption id="table-desc" class="bottom-breathing-room-rel top-breathing-room-rel screen-reader-only">
							<?php echo $LANG['TABLE_DESC']; ?>
						</caption>
						<tr>
							<th> <input name='selectall' type="checkbox" onclick="selectAllId(this)" aria-label="<?php echo (isset($LANG['SELECT_ALL']) ? $LANG['SELECT_ALL'] : 'Select/Unselect All'); ?>" /></th>
							<th><?php echo $LANG['RECORD_NO']; ?></th>
							<th><?php echo $LANG['CAT_NUM']; ?></th>
							<th><?php echo $LANG['REVIEW_STATUS']; ?></th>
							<th><?php echo $LANG['APPLIED_STATUS']; ?></th>
							<th><?php echo $LANG['EDITOR']; ?></th>
							<th><?php echo $LANG['TIMESTAMP']; ?></th>
							<th><?php echo $LANG['FIELD_NAME']; ?></th>
							<th><?php echo $LANG['OLD_VALUE']; ?></th>
							<th><?php echo $LANG['NEW_VALUE']; ?></th>
						</tr>
						<?php
						$editArr = $reviewManager->getEditArr();
						if($editArr){
							$recCnt = 0;
							foreach($editArr as $occid => $editArr2){
								$catNum = $editArr2['catnum'];
								unset($editArr2['catnum']);
								foreach($editArr2 as $id => $editArr3){
									foreach($editArr3 as $appliedStatus => $edObj){
										$fieldArr = $edObj['f'];
										$displayAll = true;
										foreach($fieldArr as $fieldName => $fieldObj){
											?>
											<tr <?php echo ($recCnt%2?'class="alt"':'') ?>>
												<td>
													<?php
													if($displayAll){
														echo '<input id="id[]-' . $id . '" name="id[]" type="checkbox" value="'.$id.'" />';
														echo '<label class="screen-reader-only" for="id[]-' . $id . '">' . $id . '</label>';
													}
													?>
												</td>
												<td>
													<?php
													if($displayAll){
														?>
														<a href="#" onclick="openIndPU(<?php echo $occid; ?>);return false;">
															<?php echo $occid; ?>
														</a>
														<?php
													}
													?>
												</td>
												<td>
													<div title="<?php echo $LANG['CAT_NUM']; ?>">
														<?php if($displayAll) echo $catNum; ?>
													</div>
												</td>
												<td>
													<div title="<?php echo $LANG['REVIEW_STATUS']; ?>">
														<?php
														if($displayAll){
															$rStatus = $edObj['rstatus'];
															if($rStatus == 1) echo $LANG['OPEN'];
															elseif($rStatus == 2) echo $LANG['PENDING'];
															elseif($rStatus == 3) echo $LANG['C_CLOSED'];
															else echo $LANG['UNKNOWN'];
														}
														?>
													</div>
												</td>
												<td>
													<div title="<?php echo $LANG['APPLIED_STATUS']; ?>">
														<?php
														if($displayAll){
															if($appliedStatus == 1) echo $LANG['APPLIED'];
															else echo $LANG['NOT_APPLIED'];
														}
														?>
													</div>
												</td>
												<td>
													<div title="<?php echo $LANG['EDITOR']; ?>">
														<?php
														if($displayAll){
															$editorStr = '';
															if(isset($edObj['editor'])) $editorStr = $edObj['editor'];
															elseif(isset($edObj['uid'])) $editorStr = $editorArr[$edObj['uid']];
															if($displayMode == 2){
																if(!$editorStr) $editorStr = $edObj['exeditor'];
																if($edObj['exsource']) $editorStr = $edObj['exsource'].($editorStr?': '.$editorStr:'');
															}
															echo $editorStr;
														}
														?>
													</div>
												</td>
												<td>
													<div title="<?php echo $LANG['TIMESTAMP']; ?>">
														<?php if($displayAll) echo $edObj['ts']; ?>
													</div>
												</td>
												<td>
													<div title="<?php echo $LANG['FIELD_NAME']; ?>">
														<?php echo $fieldName; ?>
													</div>
												</td>
												<td>
													<div title="<?php echo $LANG['OLD_VALUE']; ?>">
														<?php echo wordwrap($fieldObj['old'],40,"<br />\n",true); ?>
													</div>
												</td>
												<td>
													<div title="<?php echo $LANG['NEW_VALUE']; ?>">
														<?php echo wordwrap($fieldObj['new'],40,"<br />\n",true); ?>
													</div>
												</td>
											</tr>
											<?php
											$displayAll = false;
										}
									}
									$recCnt++;
								}
							}
						}
						else{
							?>
							<tr>
								<td colspan="10">
									<div style="font-weight:bold;font-size:150%;margin:20px;"><?php echo $LANG['NONE_FOUND']; ?></div>
								</td>
							</tr>
							<?php
						}
						?>
					</table>
					<?php
					echo $retToMenuStr;
					echo $navStr;
					?>
				</form>
				<?php
			}
			else echo '<div>'.$LANG['ERROR'].'</div>';
			?>
		</div>
		<?php include($SERVER_ROOT.'/includes/footer.php');?>
	</body>
</html>