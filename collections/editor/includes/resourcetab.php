<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceEditorResource.php');
include_once($SERVER_ROOT . '/classes/OccurrenceDuplicate.php');

if($LANG_TAG != 'en' && !file_exists($SERVER_ROOT.'/content/lang/collections/editor/includes/resourcetab.' . $LANG_TAG . '.php')) $LANG_TAG = 'en';
include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/resourcetab.' . $LANG_TAG . '.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$occid = filter_var($_GET['occid'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
$collid = filter_var($_GET['collid'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
$occIndex = filter_var($_GET['occindex'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
$crowdSourceMode = htmlspecialchars($_GET['csmode'], HTML_SPECIAL_CHARS_FLAGS);

$occManager = new OccurrenceEditorResource();
$occManager->setOccId($occid);
$occManager->setCollId($collid);
$oArr = $occManager->getOccurMap();
$occArr = $oArr[$occid];
$defaultRelationshipArr = $occManager->getRelationshipArr();
$resourceRelationshipArr = $occManager->getResourceRelationshipArr();

$genticArr = $occManager->getGeneticArr();

$dupManager = new OccurrenceDuplicate();
$dupClusterArr = $dupManager->getClusterArr($occid);
?>
<script>
	let defaultRelationships = ["<?= implode('","', $defaultRelationshipArr) ?>"];
	let resourceRelationships = ["<?= implode('","', $resourceRelationshipArr) ?>"];
	let assocSciname = [];

	$("#verbatimsciname").autocomplete({
		source: "rpc/getspeciessuggest.php",
		minLength: 3,
		autoFocus: true
	});

	function associationTypeChanged(selectElem){
		document.getElementById("subType-div").style.display = "block";
		document.getElementById("basisOfRecord-div").style.display = "block";
		document.getElementById("locationOnHost-div").style.display = "block";
		document.getElementById("taxonomy-fieldset").style.display = "block";
		document.getElementById("externalResource").style.display = "none";
		document.getElementById("internalResource").style.display = "none";
		if(selectElem.value == 'externalOccurrence'){
			document.getElementById("externalResource").style.display = "block";
		}
		else if(selectElem.value == 'internalOccurrence'){
			document.getElementById("internalResource").style.display = "block";
		}
		if(selectElem.value == 'resource'){
			document.getElementById("externalResource").style.display = "block";
			document.getElementById("basisOfRecord-div").style.display = "none";
			document.getElementById("locationOnHost-div").style.display = "none";
			document.getElementById("taxonomy-fieldset").style.display = "none";
			//setRelationshipSelect("resource");
		}
		else{
			setRelationshipSelect("default");
		}
	}

	function setRelationshipSelect(source){
		let itemArr = defaultRelationships;
		if(source == "resource") itemArr = resourceRelationships;
		let str = '<option value="">---------------------</option>';
		for (let item of itemArr) {
			str += "<option>" + item + "</option>";
		}
		document.getElementById("relationship-select").innerHTML = str;
	}

	function assocIdentifierChanged(f){
		if(f.internalidentifier.value){
			//alert("rpc/getAssocOccurrence.php?id="+f.internalidentifier.value+"&target="+f.target.value+"&collidtarget="+f.collidtarget.value);
			$.ajax({
				type: "POST",
				url: "rpc/getAssocOccurrence.php",
				dataType: "json",
				data: { id: f.internalidentifier.value, target: f.target.value, collidtarget: f.collidtarget.value }
			}).done(function( retObj ) {
				if(retObj){
					$( "#searchResultDiv" ).html("");
					var cnt = 0;
					$.each(retObj, function(occid, item) {
						if(f.occid.value != occid){
							$( "#searchResultDiv" ).append( createAssocInput(occid, item.catnum, item.collinfo) );
							if(item.sciname != "") assocSciname[occid] = item.sciname;
							cnt++;
						}
					});
					if(cnt == 0) $( "#searchResultDiv" ).html("<?php echo $LANG['NO_RESULTS']; ?>");
				}
				else{
					$( "#searchResultDiv" ).html("<?php echo $LANG['ERROR_UNABLE_RESULTS']; ?>");
				}
			});
		}
	}

	function createAssocInput(occid, catnum, collinfo){
		var newDiv = document.createElement("div");
		var newInput = document.createElement('input');
		newInput.setAttribute("name", "occidAssociate");
		newInput.setAttribute("type", "radio");
		newInput.setAttribute("value", occid);
		newInput.setAttribute("onclick", "occidAssociateChanged(this)");
		newDiv.appendChild(newInput);
		var newText = document.createTextNode(catnum + ": " + collinfo);
		var newAnchor = document.createElement("a");
		newAnchor.setAttribute("href","#");
		newAnchor.setAttribute("onclick", "openIndividual(" + occid + ");return false;");
		newAnchor.appendChild(newText);
		newDiv.appendChild(newAnchor);
		return newDiv;
	}

	function occidAssociateChanged(radioElem){
		if(assocSciname[radioElem.value]) radioElem.form.verbatimsciname.value = assocSciname[radioElem.value];
	}

	function validateAssocForm(f){
		if(f.relationship.value == ""){
			alert("<?php echo $LANG['DEFINE_REL']; ?>");
			return false;
		}
		else if(f.resourceurl.value == "" && f.objectid.value == "" && f.verbatimsciname.value == "" && (!f.occidAssociate || f.occidAssociate.value == "")){
			alert("<?php echo $LANG['REL_NOT_DEFINED']; ?>");
			return false;
		}
		return true;
	}

	function validateVoucherAddForm(f){
		if(f.clidvoucher.value == ""){
			alert("<?php echo $LANG['SELECT_CHECKLIST']; ?>");
			return false;
		}
		if(f.tidvoucher.value == ""){
			alert("<?php echo $LANG['VOUCHER_CANNOT_LINK']; ?>");
			return false;
		}
		return true;
	}

	function openDupeWindow(){
		$url = "rpc/dupelist.php?curoccid=<?php echo $occid.'&recordedby='.urlencode($occArr['recordedby']).'&recordnumber='.$occArr['recordnumber'].'&eventdate='.$occArr['eventdate']; ?>";
		dupeWindow=open($url,"dupelist","resizable=1,scrollbars=1,toolbar=0,width=900,height=600,left=20,top=20");
		if (dupeWindow.opener == null) dupeWindow.opener = self;
	}

	function deleteDuplicateLink(dupid, occid){
		if(confirm("<?php echo $LANG['SURE_UNLINK']; ?>")){
			$.ajax({
				type: "POST",
				url: "rpc/dupedelete.php",
				dataType: "json",
				data: { dupid: dupid, occid: occid }
			}).done(function( retStr ) {
				if(retStr == "1"){
					$("#dupediv-"+occid).hide();
				}
				else{
					alert("<?php echo $LANG['ERROR_DELETING']; ?>: "+retStr);
				}
			});
		}
	}

	function openIndividual(target) {
		occWindow=open("../individual/index.php?occid="+target,"occdisplay","resizable=1,scrollbars=1,toolbar=0,width=900,height=600,left=20,top=20");
		if (occWindow.opener == null) occWindow.opener = self;
	}

	function submitEditGeneticResource(f){
		if(f.resourcename.value == ""){
			alert("<?php echo $LANG['GEN_RES_NOT_BLANK']; ?>");
		}
		else{
			f.submit();
		}
	}

	function submitDeleteGeneticResource(f){
		if(confirm("<?php echo $LANG['PERM_REMOVE_RES']; ?>")){
			f.submit();
		}
	}

	function submitAddGeneticResource(f){
		if(f.resourcename.value == ""){
			alert("<?php echo $LANG['GEN_RES_NOT_BLANK']; ?>");
		}
		else{
			f.submit();
		}
	}
</script>
<style type="text/css">
	fieldset{ clear:both; margin:10px; padding:10px; }
	legend{ font-weight: bold }
	label{ font-weight: bold; }
	.formRow-div{ clear:both; margin: 2px 10px; }
	.field-div{ float:left; margin: 2px 10px 2px 0px; }
	.field-div label{ display: block; }
	.field-div button{ margin-top: 10px; }
	.assoc-div{ margin-bottom: 10px; }
	.icon-img{ width: 1.2em }
	#subType-div select{ min-width: 130px; }
	#taxonomy-fieldset{ display: none; }
</style>
<div id="voucherdiv" style="width:795px;">
	<?php
	$assocArr = $occManager->getOccurrenceRelationships();
	$basisOfRecordArr = array('HumanObservation' => $LANG['HUMAN_OBS'], 'LivingSpecimen' => $LANG['LIVING_SPEC'], 'MachineObservation' => $LANG['MACHINE_OBS'],
		'MaterialSample' => $LANG['MAT_SAMPLE'], 'PreservedSpecimen' => $LANG['PRES_SAMPLE'], 'ReferenceCitation' => $LANG['REF_CITATION']);
	?>
	<fieldset>
		<legend><?php echo $LANG['ASSOC_OCC']; ?></legend>
		<div style="float:right;margin-right:10px;">
			<a href="#" onclick="toggle('new-association');return false;" title="<?php echo $LANG['CREATE_NEW_ASSOC']; ?>" ><img class="icon-img" src="../../images/add.png" /></a>
		</div>
		<fieldset id="new-association" style="display:none">
			<legend><?php echo $LANG['CREATE_NEW_ASSOC']; ?></legend>
			<form name="addOccurAssocForm" action="resourcehandler.php" method="post" onsubmit="return validateAssocForm(this)">
				<div class="formRow-div" style="margin:10px">
					<div class="field-div">
						<label for="associationType"><?= $LANG['ASSOCIATION_TYPE'] ?>: </label>
						<select id="associationType" name="associationType" onchange="associationTypeChanged(this)" required>
							<option value="">-------------------</option>
							<option value="resource"><?= $LANG['RESOURCE_LINK'] ?></option>
							<option value="internalOccurrence"><?= $LANG['INTERNAL_OCCURRENCE'] ?></option>
							<option value="externalOccurrence"><?= $LANG['EXTERNAL_OCCURRENCE'] ?></option>
							<option value="observational"><?= $LANG['OBSERVATION'] ?></option>
						</select>
					</div>
					<div class="field-div">
						<label for="relationship-select"><?php echo $LANG['RELATIONSHIP']; ?>: </label>
						<select id="relationship-select" name="relationship" required>
							<option value="">--------------------</option>
							<?php
							foreach($defaultRelationshipArr as $rValue){
								echo '<option value="'.$rValue.'">'.$rValue.'</option>';
							}
							?>
						</select>
					</div>
					<div id="subType-div" class="field-div">
						<label for="subtype"><?php echo $LANG['REL_SUBTYPE']; ?>: </label>
						<select id="subtype" name="subtype">
							<option value="">--------------------</option>
							<?php
							$subtypeArr = $occManager->getSubtypeArr();
							foreach($subtypeArr as $term => $display){
								if(!$display) $display = $term;
								echo '<option value="'.$term.'">'.$display.'</option>';
							}
							?>
						</select>
					</div>
					<div id="basisOfRecord-div" class="field-div">
						<label for="basisofrecord"><?php echo $LANG['BASIS_OF_RECORD']; ?>: </label>
						<select id="basisofrecord" name="basisofrecord">
							<option value="">--------------------</option>
							<?php
							foreach($basisOfRecordArr as $borKey => $borName){
								echo '<option value="' . $borKey . '">' . $borName . '</option>';
							}
							?>
						</select>
					</div>
					<div id="locationOnHost-div" class="field-div">
						<label for="locationonhost"><?php echo $LANG['LOC_ON_HOST']; ?>: </label>
						<input id="locationonhost" name="locationonhost" type="text" value="" style="" />
					</div>
				</div>
				<div class="formRow-div" style="margin:10px">
					<div class="field-div" style="width:100%">
						<label for="notes"><?php echo $LANG['NOTES']; ?>: </label>
						<input id="notes" name="notes" type="text" value="" style="width:100%" />
					</div>
				</div>
				<fieldset id="internalResource" style="display:none">
					<legend><?php echo $LANG['INTERNAL_RESOURCE']; ?></legend>
					<div class="formRow-div">
						<div class="field-div">
							<label for="internalidentifier"><?php echo $LANG['IDENTIFIER']; ?>: </label>
							<input id="internalidentifier" name="internalidentifier" type="text" value="" style="width:300px" />
						</div>
						<div class="field-div">
							<label for="target"><?php echo $LANG['SEARCH_TARGET']; ?>: </label>
							<select id="target" name="target">
								<option value="catnum"><?php echo $LANG['CAT_NUMS']; ?></option>
								<option value="occid"><?php echo $LANG['OCC_PK']; ?></option>
								<!-- <option value="occurrenceID">occurrenceID</option>  -->
							</select>
						</div>
					</div>
					<div class="formRow-div">
						<div class="field-div">
							<label for="collidtarget"><?php echo $LANG['SEARCH_COLS']; ?>: </label>
							<select id="collidtarget" name="collidtarget" style="width:90%">
								<option value=""><?php echo $LANG['ALL_COLS']; ?></option>
								<option value="">-------------------------</option>
								<?php
								$collList = $occManager->getCollectionList(false);
								foreach($collList as $collID => $collName){
									echo '<option value="'.$collID.'">'.$collName.'</option>';
								}
								?>
							</select>
						</div>
						<div class="field-div">
							<button type="button" onclick="assocIdentifierChanged(this.form)"><?php echo $LANG['SEARCH']; ?></button>
						</div>
					</div>
					<fieldset style="margin:0px">
						<legend><?php echo $LANG['OCC_MATCHES_AVAIL']; ?></legend>
						<div class="field-div">
							<div id="searchResultDiv">--------------------------------------------</div>
						</div>
					</fieldset>
				</fieldset>
				<fieldset id="externalResource" style="display:none">
					<legend><?php echo $LANG['EXTERNAL_RESOURCE']; ?></legend>
					<div class="formRow-div">
						<div class="field-div">
							<label for="resourceurl"><?php echo $LANG['RESOURCE_URL']; ?>: </label>
							<input id="resourceurl" name="resourceurl" type="text" value="" style="width:400px" />
						</div>
					</div>
					<div class="formRow-div">
						<div class="field-div">
							<label for="objectid"><?php echo $LANG['ADDITIONAL_ID']; ?>: </label>
							<input id="objectid" name="objectid" type="text" value="" style="width:250px" >
						</div>
					</div>
				</fieldset>
				<fieldset id="taxonomy-fieldset">
					<legend><?php echo $LANG['TAXONOMY']; ?></legend>
					<div class="formRow-div">
						<div class="field-div">
							<label for="verbatimsciname"><?php echo $LANG['VERBAT_SCINAME']; ?>: </label>
							<input id="verbatimsciname" name="verbatimsciname" type="text" value="" style="width: 250px">
						</div>
					</div>
				</fieldset>
				<div class="formRow-div" style="margin:10px">
					<div class="field-div">
						<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="occindex" type="hidden" value="<?php echo $occIndex ?>" />
						<button name="submitaction" type="submit" value="createAssociation"><?php echo $LANG['CREATE_ASSOC']; ?></button>
					</div>
				</div>
			</form>
		</fieldset>
		<div id="occurAssocDiv" style="clear:both; margin: 15px 0px;">
			<?php
			if($assocArr){
				foreach($assocArr as $assocID => $assocUnit){
					?>
					<div class="assoc-div">
						<div><label><?= $LANG['ASSOCIATION_TYPE'] ?>:</label>
							<?= $assocUnit['associationType'] ?>
							<form action="resourcehandler.php" method="post" style="display:inline">
								<input name="occid" type="hidden" value="<?php echo $occid; ?>">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>">
								<input name="occindex" type="hidden" value="<?php echo $occIndex; ?>">
								<input name="delassocid" type="hidden" value="<?php echo $assocID; ?>">
								<input class="icon-img" type="image" src="../../images/del.png">
							</form>
							<a href="#" onclick="toggle('edit-assoc-div-<?= $assocID ?>')"><img class="icon-img" src="../../images/edit.png"></a>
						</div>
						<div id="edit-assoc-div-<?= $assocID ?>" style="display:none">
							<form name="edit-association-form-<?= $assocID ?>" action="resourcehandler.php" method="post">
								<fieldset>
									<legend>Edit Association</legend>
									<div class="formRow-div">
										<div class="field-div">
											<label for="relationship-<?= $assocID ?>"><?= $LANG['RELATIONSHIP']; ?>: </label>
											<select id="relationship-<?= $assocID ?>" name="relationship" required>
												<option value="">--------------------</option>
												<?php
												foreach($defaultRelationshipArr as $rValue){
													echo '<option value="'.$rValue.'" ' . ($assocUnit['relationship'] == $rValue ? 'selected' : '') . ' >'.$rValue.'</option>';
												}
												?>
											</select>
										</div>
										<div id="subType-div" class="field-div">
											<label for="subtype-<?= $assocID ?>"><?= $LANG['REL_SUBTYPE']; ?>: </label>
											<select id="subtype-<?= $assocID ?>" name="subtype">
												<option value="">--------------------</option>
												<?php
												$subtypeArr = $occManager->getSubtypeArr();
												foreach($subtypeArr as $term => $display){
													if(!$display) $display = $term;
													echo '<option value="'.$term.'" ' . ($assocUnit['subType'] == $term ? 'selected' : '') . ' >'.$display.'</option>';
												}
												?>
											</select>
										</div>
										<?php
										if($assocUnit['associationType'] != 'resource'){
											?>
											<div id="basisOfRecord-div" class="field-div">
												<label for="basisofrecord-<?= $assocID ?>"><?= $LANG['BASIS_OF_RECORD']; ?>: </label>
												<select id="basisofrecord-<?= $assocID ?>" name="basisofrecord">
													<option value="">--------------------</option>
													<?php
													foreach($basisOfRecordArr as $borKey => $borName){
														echo '<option value="' . $borKey . '" ' . ($assocUnit['basisOfRecord'] == $borKey ? 'selected' : '') . ' >' . $borName . '</option>';
													}
													?>
												</select>
											</div>
											<div id="locationOnHost-div" class="field-div">
												<label for="locationonhost-<?= $assocID ?>"><?= $LANG['LOC_ON_HOST']; ?>: </label>
												<input id="locationonhost-<?= $assocID ?>" name="locationonhost" type="text" value="<?= $assocUnit['locationOnHost'] ?>" >
											</div>
											<?php
										}
										?>
									</div>
									<div class="formRow-div">
										<div class="field-div" style="width:100%">
											<label for="notes-<?= $assocID ?>"><?= $LANG['NOTES']; ?>: </label>
											<input id="notes-<?= $assocID ?>" name="notes" type="text" value="<?= $assocUnit['notes'] ?>" style="width:100%" >
										</div>
									</div>
									<?php
									if($assocUnit['associationType'] == 'resource' || $assocUnit['associationType'] == 'externalOccurrence'){
										?>
										<div class="formRow-div">
											<div class="field-div">
												<label for="resourceurl-<?= $assocID ?>"><?= $LANG['RESOURCE_URL']; ?>: </label>
												<input id="resourceurl-<?= $assocID ?>" name="resourceurl" type="text" value="<?= $assocUnit['resourceUrl'] ?>" style="width:400px" >
											</div>
										</div>
										<div class="formRow-div">
											<div class="field-div">
												<label for="objectid-<?= $assocID ?>"><?= $LANG['ADDITIONAL_ID']; ?>: </label>
												<input id="objectid-<?= $assocID ?>" name="objectid" type="text" value="<?= $assocUnit['objectID'] ?>" style="width:250px" >
											</div>
										</div>
										<?php
									}
									if($assocUnit['associationType'] == 'externalOccurrence' || $assocUnit['associationType'] == 'observational'){
										?>
										<div class="formRow-div">
											<div class="field-div">
												<label for="verbatimsciname-<?= $assocID ?>"><?= $LANG['VERBAT_SCINAME']; ?>: </label>
												<input id="verbatimsciname-<?= $assocID ?>" name="verbatimsciname" type="text" value="<?= $assocUnit['verbatimSciname'] ?>" style="width: 250px">
											</div>
										</div>
										<?php
									}
									?>
									<div class="formRow-div" style="margin:10px">
										<div class="field-div">
											<input name="occid" type="hidden" value="<?= $occid; ?>" >
											<input name="collid" type="hidden" value="<?= $collid; ?>" >
											<input name="occindex" type="hidden" value="<?= $occIndex ?>" >
											<input name="assocID" type="hidden" value="<?= $assocID ?>" >
											<button name="submitaction" type="submit" value="editAssociation"><?= $LANG['SAVE_EDITS']; ?></button>
										</div>
									</div>
								</fieldset>
							</form>
						</div>
						<div>
							<?php
							$relationship = $assocUnit['relationship'];
							if($assocUnit['subType']) $relationship .= ' ('.$assocUnit['subType'].')';
							echo '<div><label>'.$LANG['RELATIONSHIP'].':</label> '.$relationship.'</div>';
							if($assocUnit['basisOfRecord']) echo '<div><label>'.$LANG['BASIS_OF_RECORD'].':</label> '.$assocUnit['basisOfRecord'].'</div>';
							if($assocUnit['accordingTo']) echo '<div><label>'.$LANG['ACCORDING_TO'].':</label> '.$assocUnit['accordingTo'].'</div>';
							if($assocUnit['objectID']) echo '<div><label>'.$LANG['OBJECT_IDENTIFIER'].':</label> '.$assocUnit['objectID'].'</div>';
							if($assocUnit['occidAssociate']){
								echo '<div><label>'.$LANG['INTERNAL_RESOURCE'].':</label> <a href="#" onclick="openIndividual('.$assocUnit['occidAssociate'].')">'.$assocUnit['occidAssociate'].'</a></div>';
							}
							elseif($assocUnit['resourceUrl']){
								echo '<div><label>'.$LANG['EXTERNAL_RESOURCE'].':</label> <a href="'.$assocUnit['resourceUrl'].'" target="_blank">'.$assocUnit['resourceUrl'].'</a></div>';
							}
							if($assocUnit['verbatimSciname']){
								$sciname = $assocUnit['verbatimSciname'];
								if($assocUnit['tid']) $sciname = '<a href="'.$SERVER_ROOT.'/taxa/index.php?tid='.$assocUnit['tid'].'" target="_blank">'.$sciname.'</a>';
								echo '<div><label>'.$LANG['VERBAT_SCINAME'].':</label> '.$sciname.'</div>';
							}
							if($assocUnit['locationOnHost']) echo '<div><label>'.$LANG['LOC_ON_HOST'].':</label> '.$assocUnit['locationOnHost'].'</div>';
							if($assocUnit['notes']) echo '<div><label>'.$LANG['NOTES'].':</label> '.$assocUnit['notes'].'</div>';
							if($assocUnit['establishedDate']) echo '<div><label>'.$LANG['ESTABLISHED_DATE'].':</label> '.$assocUnit['establishedDate'].'</div>';
							echo '<div><label>'.$LANG['RECORD_ID'].':</label> '.$assocUnit['recordID'].'</div>';
							echo '<div><label>'.$LANG['ENTERED_BY'].':</label> '.(empty($assocUnit['definedBy'])?'unknown':$assocUnit['definedBy']).' ('.$assocUnit['initialTimestamp'].')'.'</div>';
							?>
						</div>
					</div>
					<?php
				}
			}
			else echo '<div>'.$LANG['NO_ASSOCS'].'</div>';
			?>
		</div>
	</fieldset>
	<?php
	$userChecklists = $occManager->getUserChecklists();
	$checklistArr = $occManager->getVoucherChecklists();
	?>
	<fieldset>
		<legend><?php echo $LANG['CHECKLIST_LINKS']; ?></legend>
		<?php
		if(!$checklistArr){
			echo '<div style="margin-bottom: 1rem;">' . $LANG['NO_CHECKLISTS'] . '</div>';
		}
		if($userChecklists){
			?>
			<div style="float:right;margin-right:15px; display:<?= ($checklistArr ? 'block' : 'none') ?>">
				<a href="#" onclick="toggle('voucheradddiv');return false;" title="<?php echo $LANG['LINK_TO_CHECKLIST']; ?>" ><img class="icon-img" src="../../images/add.png"></a>
			</div>
			<div id="voucheradddiv" style="display:<?= ($checklistArr ? 'none' : 'block') ?>;">
				<form name="voucherAddForm" method="post" target="occurrenceeditor.php" onsubmit="return validateVoucherAddForm(this)">
					<select name="clidvoucher">
						<option value=""><?php echo $LANG['SEL_CHECKLIST']; ?></option>
						<option value="">---------------------------------------------</option>
						<?php
						foreach($userChecklists as $clid => $clName){
							echo '<option value="'.$clid.'">'.$clName.'</option>';
						}
						?>
					</select>
					<input name="tidvoucher" type="hidden" value="<?php echo $occArr['tidinterpreted']; ?>" />
					<input name="csmode" type="hidden" value="<?php echo $crowdSourceMode; ?>" />
					<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
					<input name="tabtarget" type="hidden" value="3" />
					<button name="submitaction" type="submit" value="linkChecklistVoucher"><?php echo $LANG['LINK_TO_CHECKLIST_2']; ?></button>
				</form>
			</div>
			<?php
		}
		//Display list of checklists specimen is linked to
		if($checklistArr){
			foreach($checklistArr as $vClid => $vClName){
				$vClid = filter_var($vClid, FILTER_SANITIZE_NUMBER_INT);
				echo '<div style="margin:3px">';
				echo '<a href="../../checklists/checklist.php?showvouchers=1&clid=' . htmlspecialchars($vClid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($vClName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> ';
				if(array_key_exists($vClid, $userChecklists)){
					$href = 'occurrenceeditor.php?submitaction=deletevoucher&delclid=' . $vClid . '&occid=' . $occid . '&tabtarget=3';
					echo '<a href="' . htmlspecialchars($href, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) .'" title="' . htmlspecialchars($LANG['DELETE_VOUCHER_LINK'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" onclick="return confirm(\'' . htmlspecialchars($LANG['SURE_REMOVE_VOUCHER'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '\'">';
					echo '<img class="icon-img" src="../../images/drop.png" >';
					echo '</a>';
				}
				echo '</div>';
			}
			echo '<div style="margin:15px 0px">* '.$LANG['IF_X'].'</div>';
		}
		?>
	</fieldset>
</div>
<div id="duplicatediv">
	<fieldset>
		<legend><?php echo $LANG['SPEC_DUPES']; ?></legend>
		<div style="float:right;margin-right:15px;">
			<button onclick="openDupeWindow();return false;"><?php echo $LANG['SEARCH_RECS']; ?></button>
		</div>
		<div style="clear:both;">
			<form id="dupeRefreshForm" name="dupeRefreshForm" method="post" target="occurrenceeditor.php">
				<input name="tabtarget" type="hidden" value="3" />
				<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
			</form>
			<?php
			if($dupClusterArr){
				foreach($dupClusterArr as $dupid => $dupArr){
					echo '<div id="dupediv-'.$occid.'">';
					echo '<div style="padding:15px;"><b>'.$LANG['CLUSTER_TITLE'].':</b> '.$dupArr['title'];
					echo '<div style="float:right" title="'.$LANG['UNLINK_BUT_MAINTAIN'].'">';
					echo '<button class="button-danger" name="unlinkthisdupebutton" onclick="deleteDuplicateLink('.$dupid.','.$occid.')">'.$LANG['REM_FROM_CLUSTER'].'</button>';
					echo '</div>';
					$note = trim($dupArr['description'].'; '.$dupArr['notes'],' ;');
					if($note) echo ' - '.$notes;
					echo '</div>';
					echo '<div style="20px 0px"><hr/><hr/></div>';
					$innerDupArr = $dupArr['o'];
					foreach($innerDupArr as $dupeOccid => $dArr){
						if($occid != $dupeOccid){
							?>
							<div id="dupediv-<?php echo $dupeOccid; ?>" style="clear:both;margin:15px;">
								<div style="font-weight:bold;font-size:120%;">
									<?php echo $dArr['collname'].' ('.$dArr['instcode'].($dArr['collcode']?':'.$dArr['collcode']:'').')'; ?>
								</div>
								<div style="float:right;">
									<button name="unlinkdupebut" onclick="deleteDuplicateLink(<?php echo $dupid.','.$dupeOccid; ?>)"><?php echo $LANG['UNLINK']; ?></button>
								</div>
								<?php
								echo '<div style="float:left;margin:5px 15px">';
								if($dArr['recordedby']) echo '<div>'.$dArr['recordedby'].' '.$dArr['recordnumber'].'<span style="margin-left:40px;">'.$dArr['eventdate'].'</span></div>';
								if($dArr['catnum']) echo '<div><b>'.$LANG['CAT_NUM'].':</b> '.$dArr['catnum'].'</div>';
								if($dArr['occurrenceid']) echo '<div><b>'.$LANG['GUID'].':</b> '.$dArr['occurrenceid'].'</div>';
								if($dArr['sciname']) echo '<div><b>'.$LANG['LATEST_ID'].':</b> '.$dArr['sciname'].'</div>';
								if($dArr['identifiedby']) echo '<div><b>'.$LANG['IDED_BY'].':</b> '.$dArr['identifiedby'].'<span stlye="margin-left:30px;">'.$dArr['dateidentified'].'</span></div>';
								if($dArr['notes']) echo '<div>'.$dArr['notes'].'</div>';
								echo '<div><a href="#" onclick="openIndividual('.$dupeOccid.')">'.$LANG['SHOW_FULL_DETS'].'</a></div>';
								echo '</div>';
								if($dArr['url']){
									$url = $dArr['url'];
									$tnUrl = $dArr['tnurl'];
									if(!$tnUrl) $tnUrl = $url;
									if($IMAGE_DOMAIN){
										if(substr($url,0,1) == '/') $url = $IMAGE_DOMAIN.$url;
										if(substr($tnUrl,0,1) == '/') $tnUrl = $IMAGE_DOMAIN.$tnUrl;
									}
									echo '<div style="float:left;margin:10px;">';
									echo '<a href="'.$url.'" target="_blank">';
									echo '<img src="'.$tnUrl.'" style="width:100px;border:1px solid grey" />';
									echo '</a>';
									echo '</div>';
								}
								echo '<div style="margin:10px 0px;clear:both"><hr/></div>';
								?>
							</div>
							<?php
						}
					}
					echo '</div>';
				}
			}
			else{
				if($dupClusterArr === false){
					echo $dupManager->getErrorStr();
				}
				else{
					echo '<div style="margin:15px 0px;">' . $LANG['NO_LINKED'] . '</div>';
				}
			}
			?>
		</div>
	</fieldset>
</div>
<div id="geneticdiv">
	<fieldset>
		<legend><?php echo $LANG['GEN_RES']; ?></legend>
		<?php if(!$genticArr){
			echo '<div>' . $LANG['NO_GENETIC_RESOURCES'] . '</div>';
		} ?>
		<div style="float:right; display:<?php echo ($genticArr?'block':'none'); ?>;">
			<a href="#" onclick="toggle('genadddiv');return false;" title="<?php echo $LANG['ADD_NEW_GEN']; ?>" ><img class="icon-img" src="../../images/add.png"></a>
		</div>
		<div id="genadddiv" style="display:<?php echo ($genticArr?'none':'block'); ?>;">
			<fieldset>
				<legend><b><?php echo $LANG['ADD_NEW_RES']; ?></b></legend>
				<form name="addgeneticform" method="post" action="occurrenceeditor.php">
					<div style="margin:2px;">
						<label for="genetic-resourcename"><?php echo $LANG['NAME']; ?>:</label><br/>
						<input id="genetic-resourcename" name="resourcename" type="text" value="" style="width:50%" />
					</div>
					<div style="margin:2px;">
						<label for="genetic-identifier"><?php echo $LANG['IDENTIFIER']; ?>:</label><br/>
						<input id="genetic-identifier" name="identifier" type="text" value="" style="width:50%" />
					</div>
					<div style="margin:2px;">
						<label for="genetic-locus"><?php echo $LANG['LOCUS']; ?>:</label><br/>
						<input id="genetic-locus" name="locus" type="text" value="" style="width:95%" />
					</div>
					<div style="margin:2px;">
						<label for="genetic-resourceurl"><?php echo $LANG['URL']; ?>:</label><br/>
						<input id="genetic-resourceurl" name="resourceurl" type="text" value="" style="width:95%" />
					</div>
					<div style="margin:2px;">
						<label for="genetic-notes"><?php echo $LANG['NOTES']; ?>:</label><br/>
						<input id="genetic-notes" name="notes" type="text" value="" style="width:95%" />
					</div>
					<div style="margin:2px;">
						<input name="submitaction" type="hidden" value="addgeneticsubmit" />
						<input name="csmode" type="hidden" value="<?php echo $crowdSourceMode; ?>" />
						<input name="tabtarget" type="hidden" value="3" />
						<button name="subbut" type="button" value="Add New Genetic Resource" onclick="submitAddGeneticResource(this.form)" ><?php echo $LANG['ADD_NEW_GEN_2']; ?></button>
						<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
					</div>
				</form>
			</fieldset>
		</div>
		<div style="clear:both;">
			<?php
			foreach($genticArr as $genId => $gArr){
				?>
				<div style="margin:15px;">
					<div style="font-weight:bold;margin-bottom:5px;"><?php echo $gArr['name']; ?><a href="#" onclick="toggle('genedit-<?php echo $genId; ?>');return false;"><img class="icon-img" src="../../images/edit.png"></a></div>
					<div style="margin-left:15px;"><b><?php echo $LANG['IDENTIFIER']; ?>:</b> <?php echo $gArr['id']; ?></div>
					<div style="margin-left:15px;"><b><?php echo $LANG['LOCUS']; ?>:</b> <?php echo $gArr['locus']; ?></div>
					<div style="margin-left:15px;">
						<b><?php echo $LANG['URL']; ?>:</b> <a href="<?php echo htmlspecialchars($gArr['resourceurl'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank"><?php echo htmlspecialchars($gArr['resourceurl'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
					</div>
					<div style="margin-left:15px;"><b><?php echo $LANG['NOTES']; ?>:</b> <?php echo $gArr['notes']; ?></div>
				</div>
				<div id="genedit-<?php echo $genId; ?>" style="display:none;margin-left:25px;">
					<fieldset>
						<legend><?php echo $LANG['GEN_RES_EDITOR']; ?></legend>
						<form name="editgeneticform" method="post" action="occurrenceeditor.php">
							<div style="margin:2px;">
								<label for="genetic-resourcename-<?= $genId ?>"><?php echo $LANG['NAME']; ?>:</label><br/>
								<input id="genetic-resourcename-<?= $genId ?>" name="resourcename" type="text" value="<?php echo $gArr['name']; ?>" style="width:50%" />
							</div>
							<div style="margin:2px;">
								<label for="genetic-identifier-<?= $genId ?>"><?php echo $LANG['IDENTIFIER']; ?>:</label><br/>
								<input id="genetic-identifier-<?= $genId ?>" name="identifier" type="text" value="<?php echo $gArr['id']; ?>" style="width:50%" />
							</div>
							<div style="margin:2px;">
								<label for="genetic-locus-<?= $genId ?>"><?php echo $LANG['LOCUS']; ?>:</label><br/>
								<input id="genetic-locus-<?= $genId ?>" name="locus" type="text" value="<?php echo $gArr['locus']; ?>" style="width:95%" />
							</div>
							<div style="margin:2px;">
								<label for="genetic-resourceurl-<?= $genId ?>"><?php echo $LANG['URL']; ?>:</label><br/>
								<input id="genetic-resourceurl-<?= $genId ?>" name="resourceurl" type="text" value="<?php echo $gArr['resourceurl']; ?>" style="width:95%" />
							</div>
							<div style="margin:2px;">
								<label for="genetic-notes-<?= $genId ?>"><?php echo $LANG['NOTES']; ?>:</label><br/>
								<input id="genetic-notes-<?= $genId ?>" name="notes" type="text" value="<?php echo $gArr['notes']; ?>" style="width:95%" />
							</div>
							<div style="margin:2px;">
								<input name="submitaction" type="hidden" value="editgeneticsubmit" />
								<button name="subbut" type="button" value="Save Edits" onclick="submitEditGeneticResource(this.form)" ><?php echo $LANG['SAVE_EDITS']; ?></button>
								<input name="genid" type="hidden" value="<?php echo $genId; ?>" />
								<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
								<input name="csmode" type="hidden" value="<?php echo $crowdSourceMode; ?>" />
								<input name="tabtarget" type="hidden" value="3" />
							</div>
						</form>
					</fieldset>
					<fieldset>
						<legend><?php echo $LANG['DEL_GEN_RES']; ?></legend>
						<form name="delgeneticform" method="post" action="occurrenceeditor.php">
							<div style="margin:2px;">
								<input name="submitaction" type="hidden" value="deletegeneticsubmit" />
								<button class="button-danger" name="subbut" type="button" value="Delete Resource" onclick="submitDeleteGeneticResource(this.form)" ><?php echo $LANG['DEL_RES']; ?></button>
								<input name="genid" type="hidden" value="<?php echo $genId; ?>" />
								<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
								<input name="csmode" type="hidden" value="<?php echo $crowdSourceMode; ?>" />
								<input name="tabtarget" type="hidden" value="3" />
							</div>
						</form>
					</fieldset>
				</div>
				<?php
			}
			?>
		</div>
	</fieldset>
</div>
