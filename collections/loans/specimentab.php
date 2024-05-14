<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceLoans.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/editor/includes/determinationtab.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/editor/includes/determinationtab.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/editor/includes/determinationtab.en.php');
header("Content-Type: text/html; charset=" . $CHARSET);
if(!$SYMB_UID) header('Location: ' . $CLIENT_ROOT . '/profile/index.php?refurl=../collections/loans/outgoing.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = $_REQUEST['collid'];
$loanId = $_REQUEST['loanid'];
$sortTag = (isset($_REQUEST['sortTag']) ? $_REQUEST['sortTag'] : '');

$loanManager = new OccurrenceLoans();

//Sanitation
$collid = $loanManager->sanitizeInt($collid);
$loanId = $loanManager->sanitizeInt($loanId);

if($collid) $loanManager->setCollId($collid);
$specList = $loanManager->getSpecimenList($loanId, $sortTag);
?>
<script type="text/javascript">
	var skipFormVerification = false;

	function initLoanDetAutocomplete(f){
		$( f.sciname ).autocomplete({
			source: "../editor/rpc/getspeciessuggest.php",
			minLength: 3,
			change: function(event, ui) {
				if(f.sciname.value){
					pauseSubmit = true;
					verifyLoanDetSciName(f);
				}
				else{
					f.scientificnameauthorship.value = "";
					f.family.value = "";
					f.tidtoadd.value = "";
				}
			}
		});
	}

	function verifyLoanDetSciName(f){
		$.ajax({
			type: "POST",
			url: "../editor/rpc/verifysciname.php",
			dataType: "json",
			data: { term: f.sciname.value }
		}).done(function( data ) {
			if(data){
				f.scientificnameauthorship.value = data.author;
				f.family.value = data.family;
				f.tidtoadd.value = data.tid;
			}
			else{
	            alert("<?php echo $LANG['TAXON_NOT_FOUND']; ?>");
				f.scientificnameauthorship.value = "";
				f.family.value = "";
				f.tidtoadd.value = "";
			}
		});
	}

	function verifyLoanDet(){
		if(document.getElementById('dafsciname').value == ""){
			alert("<?php echo $LANG['SCINAME_NEEDS_VALUE']; ?>");
			return false;
		}
		if(document.getElementById('identifiedby').value == ""){
			alert("<?php echo $LANG['DET_NEEDS_VALUE']; ?>");
			return false;
		}
		if(document.getElementById('dateidentified').value == ""){
			alert("<?php echo $LANG['DET_DATE_NEEDS_VALUE']; ?>");
			return false;
		}
		return true;
	}

	function verifySpecEditForm(f){
		var cbChecked = false;
		var dbElements = document.getElementsByName("occid[]");
		for(i = 0; i < dbElements.length; i++){
			var dbElement = dbElements[i];
			if(dbElement.checked){
				cbChecked = true;
				break;
			}
		}
		if(!cbChecked){
			alert("<?php echo $LANG['PLS_SEL_SPECIMENS']; ?>");
			return false;
		}
		return true;
	}

	function processSpecimen(f,splist){
		if(!f.catalognumber.value){
			alert("<?php echo $LANG['PLS_ENTER_CATNO']; ?>");
			return false;
		}
		else{
			let mode = f.processmode.value;
			//alert("rpc/processLoanSpecimens.php?loanid="+f.loanid.value+"&catalognumber="+f.catalognumber.value+"&target="+f.targetidentifier.value+"&collid="+f.collid.value+"&processmode="+f.processmode.value);
			$.ajax({
				method: "POST",
				data: { loanid: f.loanid.value, catalognumber: f.catalognumber.value, target: f.targetidentifier.value, collid: f.collid.value, processmode: mode },
				dataType: "text",
				url: "rpc/processLoanSpecimens.php"
			})
			.done(function(retStr) {
				if(retStr == "0"){
					$("#message-span").html("<?php echo $LANG['ERROR_NO_SPECS']; ?>");
					$("#message-span").css("color","red");
				}
				else if(retStr == "1"){
					f.catalognumber.value = '';
					let msgStr = "<?php echo $LANG['SUCCESS_SPEC'] . ' '; ?>";
					if(mode == "link") msgStr = msgStr + "<?php echo $LANG['LINKED']; ?>";
					else msgStr = msgStr + "<?php echo $LANG['CHECKED_IN']; ?>";
					$("#message-span").html(msgStr);
					$("#message-span").css("color","green");

					if(splist == 0){
						$("#speclist-div").show();
						$("#nospecdiv").hide();
					}
				}
				else if(retStr == "2"){
					if(mode == "link"){
						$("#message-span").html("<?php echo $LANG['MORE_THAN_ONE']; ?>");
						$("#message-span").css("color","red");
					}
					else{
						$("#message-span").html("<?php echo $LANG['SUCCESS_MORE_THAN']; ?>");
						$("#message-span").css("color","orange");
					}
				}
				else if(retStr == "3"){
					if(mode == "link"){
						$("#message-span").html("<?php echo $LANG['WARNING_ALREADY_LINKED']; ?>");
						$("#message-span").css("color","orange");
					}
					else{
						$("#message-span").html("<?php echo $LANG['WARNING_ALREADY_CHECKED']; ?>");
						$("#message-span").css("color","orange");
					}
				}
				else{
					f.catalognumber.value = "";
					document.refreshspeclist.submit();
				}
				setTimeout(function () {
					$("#message-span").html("");
				}, 4000);
			})
			.fail(function() {
				alert("<?php echo $LANG['TECHNICAL_ERROR']; ?>");
			});
		}
		return false;
	}

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

	function openCheckinPopup(loanId, occid, collid){
		urlStr = "specnoteseditor.php?loanid="+loanId+"&occid="+occid+"&collid="+collid;
		newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=0,resizable=1,width=800,height=300,left=60,top=250');
		window.name = "parentWin";
		if(newWindow.opener == null) newWindow.opener = self;
		return false;
	}


	function displayBarcodePanel(mode,tagName){
		if(mode){
			hideAll();
			$("#barcodeSpec-div").show();
			$("."+tagName).prop("checked", true);
		}
		else{
			$("#barcodeSpec-div").hide();
			$(".speccheckin").prop("checked", false);
			$(".speclink").prop("checked", false);
		}
	}

	function displayBatchPanel(mode,tagName){
		if(mode){
			hideAll();
			$("#batchSpec-div").show();
			$("."+tagName).prop("checked", true);
		}
		else{
			$("#batchSpec-div").hide();
			$(".speccheckin").prop("checked", false);
			$(".speclink").prop("checked", false);
		}
	}

	function displayNewDetPanel(mode){
		if(mode){
			hideAll();
			$(".form-checkbox").show();
			$('#newdet-div').show();
		}
		else{
			$(".form-checkbox").hide();
			$('#newdet-div').hide();
		}
	}

	function displayBatchActionPanel(mode){
		if(mode){
			hideAll();
			$(".form-checkbox").show();
			$("#batchaction-div").show();
		}
		else{
			$(".form-checkbox").hide();
			$("#batchaction-div").hide();
		}
	}

	function hideAll(){
		displayBarcodePanel(false,null);
		displayBatchPanel(false,null);
		displayNewDetPanel(false);
		displayBatchActionPanel(false);
	}
</script>
<style type="text/css">
	table th{ text-align:center; }
	.radio-span{ margin-left: 5px }
	.info-div{ margin-bottom:10px; }
	#message-span{ margin-left:30px; padding-bottom:2px; }
	.form-checkbox{ display:none; }
	label{ font-weight: bold }
	.field-div{ margin: 10px 0px }
</style>
<div id="outloanspecdiv">
	<div id="menu-div">
		<fieldset>
			<legend><?php echo $LANG['MENU_OPTIONS']; ?></legend>
			<ul>
				<li><a href="#" onclick="displayBatchPanel(true,'speclink');return false;"><?php echo $LANG['LINK_VIA_CATNUM']; ?></a></li>
				<li><a href="#" onclick="displayBarcodePanel(true,'speclink');return false;"><?php echo $LANG['LINK_VIA_BARCODE']; ?></a></li>
				<li><a href="#" onclick="displayBatchPanel(true,'speccheckin');return false;"><?php echo $LANG['CHECKIN_VIA_CATNUM']; ?></a></li>
				<li><a href="#" onclick="displayBarcodePanel(true,'speccheckin');return false;"><?php echo $LANG['CHECKIN_VIA_BARCODE']; ?></a></li>
				<li><a href="#" onclick="displayNewDetPanel(true);return false;"><?php echo $LANG['ADD_DETS']; ?></a></li>
				<li><a href="outgoing.php?formsubmit=exportSpecimenList&loanid=<?php echo htmlspecialchars($loanId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo $LANG['EXPORT_FULL_LIST']; ?></a></li>
				<li><a href="#" onclick="displayBatchActionPanel(true);return false;"><?php echo $LANG['DISPLAY_BATCH_FORM']; ?></a></li>
			</ul>
		</fieldset>
	</div>
	<div id="batchSpec-div" style="display:none">
		<fieldset>
			<legend><?php echo $LANG['BATCH_PROCESS_CATNUMS']; ?></legend>
			<div  class="info-div"><?php echo $LANG['BATCH_PROCESS_EXPLAIN']; ?></div>
			<form name="batchaddform" action="outgoing.php" method="post">
				<div class="field-div">
					<label><?php echo $LANG['PROC_MODE']; ?>:</label>
					<span class="radio-span"><input class="speclink" name="processmode" type="radio" value="link" /> <?php echo $LANG['SPEC_LINKING']; ?></span>
					<span class="radio-span"><input class="speccheckin" name="processmode" type="radio" value="checkin" /> <?php echo $LANG['SPEC_CHECKIN']; ?></span>
				</div>
				<div class="field-div">
					<label><?php echo $LANG['CATNUMS']; ?>:</label><br/>
					<textarea name="catalogNumbers" cols="6" style="width:700px"></textarea>
				</div>
				<div class="field-div">
					<label>Target:</label>
					<span class="radio-span"><input name="targetidentifier" type="radio" value="allid" /> <?php echo $LANG['ALL_IDS']; ?></span>
					<span class="radio-span"><input name="targetidentifier" type="radio" value="catnum" checked /> <?php echo $LANG['CATNO']; ?></span>
					<span class="radio-span"><input name="targetidentifier" type="radio" value="other" /> <?php echo $LANG['OTHER_CATNUMS']; ?></span>
				</div>
				<div class="field-div">
					<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
					<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
					<div style="float:left;margin-top:15px;margin-left:15px">
						<button name="formsubmit" type="submit" value="batchProcessSpecimens"><?php echo $LANG['PROCESS_SPECS']; ?></button>
					</div>
				</div>
			</form>
		</fieldset>
	</div>
	<div id="barcodeSpec-div" style="display:none">
		<fieldset>
			<legend><?php echo $LANG['BARCODE_SCANNING']; ?></legend>
			<form name="barcodeaddform" method="post" onsubmit="processSpecimen(this,<?php echo (!$specList?'0':'1'); ?>);return false;">
				<div class="info-div"><?php echo $LANG['BARCODE_SCANNING_EXPLAIN']; ?></div>
				<div class="field-div">
					<label>Processing mode:</label>
					<span class="radio-span"><input class="speclink" name="processmode" type="radio" value="link" /> <?php echo $LANG['SPEC_LINKING']; ?></span>
					<span class="radio-span"><input class="speccheckin" name="processmode" type="radio" value="checkin" /> <?php echo $LANG['SPEC_CHECKIN']; ?></span>
				</div>
				<div class="field-div">
					<label><?php echo $LANG['BARCODE_CATNUM']; ?>:</label>
					<input type="text" autocomplete="off" name="catalognumber" maxlength="255" style="width:300px;border:2px solid black;text-align:center;" value="" />
					<span id="message-span"></span>
				</div>
				<div class="field-div">
					<label>Target:</label>
					<span class="radio-span"><input name="targetidentifier" type="radio" value="allid" /> <?php echo $LANG['ALL_IDS']; ?></span>
					<span class="radio-span"><input name="targetidentifier" type="radio" value="catnum" checked /> <?php echo $LANG['CATNO']; ?></span>
					<span class="radio-span"><input name="targetidentifier" type="radio" value="other" /> <?php echo $LANG['OTHER_CATNUMS']; ?></span>
				</div>
				<div style="padding-top:8px;clear:left;float:left;">
					<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
					<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
					<button name="formsubmit" type="submit"><?php echo $LANG['PROCESS_SPEC']; ?></button>
				</div>
			</form>
			<form name="refreshspeclist" action="outgoing.php" method="post" style="float:left; margin-left:10px;">
				<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
				<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
				<input name="tabindex" type="hidden" value="1" />
				<button name="formsubmit" type="submit"><?php echo $LANG['REFRESH_LIST']; ?></button>
			</form>
		</fieldset>
	</div>
	<div id="speclist-div" style="<?php echo (!$specList?'display:none;':''); ?>">
		<form name="speceditform" action="outgoing.php" method="post" onsubmit="return verifySpecEditForm(this)" >
			<div id="newdet-div" style="display:none;">
				<fieldset>
					<legend><b><?php echo $LANG['ADD_A_DET']; ?></b></legend>
					<div style='margin:3px;'>
						<b><?php echo $LANG['ID_QUALIFIER']; ?>:</b>
						<input type="text" name="identificationqualifier" title="<?php echo $LANG['ID_QUALIFIER_EX']; ?>" />
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['SCI_NAME']; ?>:</b>
						<input type="text" id="dafsciname" name="sciname" style="background-color:lightyellow;width:350px;" onfocus="initLoanDetAutocomplete(this.form)" />
						<input type="hidden" id="daftidtoadd" name="tidtoadd" value="" />
						<input type="hidden" name="family" value="" />
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['AUTHOR']; ?>:</b>
						<input type="text" name="scientificnameauthorship" style="width:200px;" />
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['CONFIDENCE_IN_DET']; ?>:</b>
						<select name="confidenceranking">
							<option value="8"><?php echo $LANG['HIGH']; ?></option>
							<option value="5" selected><?php echo $LANG['MEDIUM']; ?></option>
							<option value="2"><?php echo $LANG['LOW']; ?></option>
						</select>
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['DETERMINER']; ?>:</b>
						<input type="text" name="identifiedby" id="identifiedby" style="background-color:lightyellow;width:200px;" />
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['DATE']; ?>:</b>
						<input type="text" name="dateidentified" id="dateidentified" style="background-color:lightyellow;" onchange="detDateChanged(this.form);" />
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['REFERENCE']; ?>:</b>
						<input type="text" name="identificationreferences" style="width:350px;" />
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['NOTES']; ?>:</b>
						<input type="text" name="identificationremarks" style="width:350px;" />
					</div>
					<div style='margin:3px;'>
						<input type="checkbox" name="makecurrent" value="1" /> <?php echo $LANG['MAKE_THIS_CURRENT']; ?>
					</div>
					<div style='margin:3px;'>
						<input type="checkbox" name="printqueue" value="1" /> <?php echo $LANG['ADD_TO_PRINT']; ?>
					</div>
					<div style='margin:15px;'>
						<div style="float:left;">
							<button type="submit" name="formsubmit" value="addDeterminations" onclick="return verifyLoanDet();"><?php echo $LANG['ADD_NEW_DET']; ?></button>
						</div>
					</div>
				</fieldset>
			</div>
			<div id="batchaction-div" style="margin:10px;display:none">
				<fieldset style="width:800px">
					<legend><?php echo $LANG['BATCH_FORM_ACTIONS']; ?></legend>
					<div style="float:left;margin-right:20px">
						<button name="formsubmit" type="submit" value="checkinSpecimens"><?php echo $LANG['BATCH_CHECK_IN']; ?></button><br/>
					</div>
					<div style="float:left;">
						<button name="formsubmit" type="submit" value="deleteSpecimens" onclick="return confirm('<?php echo $LANG['SURE_REMOVE_FROM_LOAN']; ?>')"><?php echo $LANG['REMOVE_SPECS']; ?></button><br/>
					</div>
					<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
					<input name="loanid" type="hidden" value="<?php echo $loanId; ?>" />
					<input name="tabindex" type="hidden" value="1" />
				</fieldset>
			</div>
			<table class="styledtable" style="font-family:Arial;font-size:12px;">
				<tr>
					<th class="form-checkbox"><input type="checkbox" onclick="selectAll(this);" title="<?php echo $LANG['SEC_DESEL_ALL']; ?>" /></th>
					<th>&nbsp;</th>
					<th><?php echo $LANG['CATNO']; ?>
					<?php
					$tagArr = $loanManager->getIdentifierTagArr();
					ksort($tagArr);
					if(count($tagArr) > 1){
						echo '<div style="font-weight:normal">' . $LANG['SORT_BY'] . ': <select name="sortTag" onchange="this.form.submit()">';
						foreach($tagArr as $tagKey => $tagValue){
							$tagKey = substr($tagKey,2);
							echo '<option value="' . $tagKey . '" ' . ($sortTag==$tagKey?'selected':'') . '>' . $tagValue . '</option>';
						}
						echo '</select></div>';
					}
					?>
					</th>
					<th><?php echo $LANG['DETAILS']; ?></th>
					<th><?php echo $LANG['DATE_RETURNED']; ?></th>
				</tr>
				<?php
				$specSortArr = $loanManager->getSpecimenSortArr();
				foreach($specSortArr as $occid => $identifier){
					$specArr = $specList[$occid];
					?>
					<tr>
						<td class="form-checkbox">
							<input name="occid[]" type="checkbox" value="<?php echo $occid; ?>" />
						</td>
						<td>
							<div>
								<a href="#" onclick="openIndPopup(<?php echo $occid; ?>); return false;"><img src="../../images/list.png" style="width:1.3em" title="<?php echo $LANG['OPEN_SPECIMEN_DETAILS']; ?>" /></a><br/>
							</div>
							<div>
								<a href="#" onclick="openEditorPopup(<?php echo $occid; ?>); return false;"><img src="../../images/edit.png" style="width:1.3em" title="<?php echo $LANG['OPEN_OCC_EDITOR']; ?>" /></a>
							</div>
						</td>
						<td>
							<?php
							if($specArr['catalognumber']) echo '<div>' . $specArr['catalognumber'] . '</div>';
							if(isset($specArr['othercatalognumbers'])) echo '<div>' . implode('<br/>',$specArr['othercatalognumbers']) . '</div>';
							if($specArr['collid'] != $collid) echo '<div style="color:orange">external</div>';
							?>
						</td>
						<td>
							<?php
							if($specArr['sciname']) echo '<i>' . $specArr['sciname'] . '</i>; ';
							$loc = $specArr['locality'];
							if(strlen($loc) > 500) $loc = substr($loc,400);
							if($specArr['collector']) echo $specArr['collector'] . '; ';
							echo $loc;
							if($specArr['notes']) echo '<div class="notesDiv"><b>Notes:</b> ' . $specArr['notes'],'</div>';
							?>
						</td>
						<td><?php
						echo '<div style="float:right"><a href="#" onclick="openCheckinPopup(' . $loanId . ',' . $occid . ',' . $collid . ');return false"><img src="../../images/edit.png" style="width:13px" title="' . $LANG['EDIT_NOTES'] . '" /></a></div>';
						echo $specArr['returndate'];
						?></td>
					</tr>
					<?php
				}
			?>
			</table>
		</form>
	</div>
	<div id="nospecdiv" style="margin:20px;font-size:120%;<?php echo ($specList?'display:none;':''); ?>"><?php echo $LANG['NO_SPECS_REGISTERED']; ?></div>
</div>
