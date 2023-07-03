<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorResource.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/includes/resourcetab.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/resourcetab.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/resourcetab.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$occid = $_GET['occid'];
$collid = $_GET['collid'];
$occIndex = $_GET['occindex'];
$crowdSourceMode = $_GET['csmode'];

//Sanitation
if(!is_numeric($occid)) $occid = 0;
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($occIndex)) $occIndex = 0;

$occManager = new OccurrenceEditorResource();
$occManager->setOccId($occid);
$occManager->setCollId($collid);
$oArr = $occManager->getOccurMap();
$occArr = $oArr[$occid];

$genticArr = $occManager->getGeneticArr();

$dupManager = new OccurrenceDuplicate();
$dupClusterArr = $dupManager->getClusterArr($occid);
?>
<script>
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
							$( "#searchResultDiv" ).append( createAssocInput(occid,item.catnum,item.collinfo) );
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

	function createAssocInput(occid,catnum,collinfo){
		var newDiv = document.createElement("div");
		var newInput = document.createElement('input');
		newInput.setAttribute("name", "occidAssoc");
		newInput.setAttribute("type", "radio");
		newInput.setAttribute("value", occid);
		newDiv.appendChild(newInput);
		var newText = document.createTextNode(catnum+": "+collinfo);
		var newAnchor = document.createElement("a");
		newAnchor.setAttribute("href","#");
		newAnchor.setAttribute("onclick","openIndividual("+occid+");return false;");
		newAnchor.appendChild(newText);
		newDiv.appendChild(newAnchor);
		return newDiv;
	}

	function validateAssocForm(f){
		if(f.relationship.value == ""){
			alert("<?php echo $LANG['DEFINE_REL']; ?>");
			return false;
		}
		else if(f.resourceurl.value == "" && f.identifier.value == "" && f.verbatimsciname.value == "" && (!f.occidAssoc || f.occidAssoc.value == "")){
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
	.fieldRowDiv{ clear:both; margin: 2px 0px; }
	.fieldDiv{ float:left; margin: 2px 10px 2px 0px; }
	.fieldLabel{ font-weight: bold; display: block; }
	.fieldDiv button{ margin-top: 10px; }
</style>

<div id="voucherdiv" style="width:795px;">
	<?php
	$assocArr = $occManager->getOccurrenceRelationships();
	?>
	<fieldset>
		<legend><?php echo $LANG['ASSOC_OCC']; ?></legend>
		<div style="float:right;margin-right:10px;">
			<a href="#" onclick="toggle('new-association');return false;" title="<?php echo $LANG['CREATE_NEW_ASSOC']; ?>" ><img src="../../images/add.png" /></a>
		</div>
		<fieldset id="new-association" style="display:none">
			<legend><?php echo $LANG['CREATE_NEW_ASSOC']; ?></legend>
			<form name="addOccurAssocForm" action="resourcehandler.php" method="post" onsubmit="return validateAssocForm(this)">
				<fieldset>
					<legend><?php echo $LANG['OCC_WI_SYSTEM']; ?></legend>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel"><?php echo $LANG['IDENTIFIER']; ?>: </span>
							<input name="internalidentifier" type="text" value="" style="width:300px" />
						</div>
						<div class="fieldDiv">
							<span class="fieldLabel"><?php echo $LANG['SEARCH_TARGET']; ?>: </span>
							<select name="target">
								<option value="catnum"><?php echo $LANG['CAT_NUMS']; ?></option>
								<option value="occid"><?php echo $LANG['OCC_PK']; ?></option>
								<!-- <option value="occurrenceID">occurrenceID</option>  -->
							</select>
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel"><?php echo $LANG['SEARCH_COLS']; ?>: </span>
							<select name="collidtarget" style="width:90%">
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
						<div class="fieldDiv">
							<button type="button" onclick="assocIdentifierChanged(this.form)"><?php echo $LANG['SEARCH']; ?></button>
						</div>
					</div>
					<fieldset style="margin:0px">
						<legend><?php echo $LANG['OCC_MATCHES_AVAIL']; ?></legend>
						<div class="fieldDiv">
							<div id="searchResultDiv">--------------------------------------------</div>
						</div>
					</fieldset>
				</fieldset>
				<fieldset>
					<legend><?php echo $LANG['EXT_OCC']; ?></legend>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel"><?php echo $LANG['EXT_ID']; ?>: </span>
							<input name="identifier" type="text" value="" />
						</div>
						<div class="fieldDiv">
							<span class="fieldLabel"><?php echo $LANG['RES_URL']; ?>: </span>
							<input name="resourceurl" type="text" value="" style="width:400px" />
						</div>
					</div>
				</fieldset>
				<fieldset>
					<legend><?php echo $LANG['OBS_REF']; ?></legend>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel"><?php echo $LANG['VERBAT_SCINAME']; ?>: </span>
							<input name="verbatimsciname" type="text" value="" />
						</div>
					</div>
				</fieldset>
				<div class="fieldRowDiv" style="margin:10px">
					<div class="fieldDiv">
						<span class="fieldLabel"><?php echo $LANG['REL']; ?>: </span>
						<select name="relationship" required>
							<option value="">--------------------</option>
							<?php
							$relArr = $occManager->getRelationshipArr();
							foreach($relArr as $rKey => $rValue){
								echo '<option value="'.$rKey.'">'.$rKey.'</option>';
							}
							?>
						</select>
					</div>
					<div class="fieldDiv">
						<span class="fieldLabel"><?php echo $LANG['REL_SUBTYPE']; ?>: </span>
						<select name="subtype">
							<option value="">--------------------</option>
							<?php
							$subtypeArr = $occManager->getSubtypeArr();
							foreach($subtypeArr as $tValue){
								echo '<option value="'.$tValue.'">'.$tValue.'</option>';
							}
							?>
						</select>
					</div>
					<div class="fieldDiv">
						<span class="fieldLabel"><?php echo $LANG['BASIS_OF_RECORD']; ?>: </span>
						<select name="basisofrecord">
							<option value="">--------------------</option>
							<option value="HumanObservation"><?php echo $LANG['HUMAN_OBS']; ?></option>
							<option value="LivingSpecimen"><?php echo $LANG['LIVING_SPEC']; ?></option>
							<option value="MachineObservation"><?php echo $LANG['MACHINE_OBS']; ?></option>
							<option value="MaterialSample"><?php echo $LANG['MAT_SAMPLE']; ?></option>
							<option value="PreservedSpecimen"><?php echo $LANG['PRES_SAMPLE']; ?></option>
							<option value="ReferenceCitation"><?php echo $LANG['REF_CITATION']; ?></option>
						</select>
					</div>
					<div class="fieldDiv">
						<span class="fieldLabel"><?php echo $LANG['LOC_ON_HOST']; ?>: </span>
						<input name="locationonhost" type="text" value="" style="" />
					</div>
				</div>
				<div class="fieldRowDiv" style="margin:10px">
					<div class="fieldDiv" style="width:100%">
						<span class="fieldLabel"><?php echo $LANG['NOTES']; ?>: </span>
						<input name="notes" type="text" value="" style="width:100%" />
					</div>
				</div>
				<div class="fieldRowDiv" style="margin:10px">
					<div class="fieldDiv">
						<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="occindex" type="hidden" value="<?php echo $occIndex ?>" />
						<button name="submitaction" type="submit" value="createAssociation"><?php echo $LANG['CREATE_ASSOC']; ?></button>
					</div>
				</div>
			</form>
		</fieldset>
		<div id="occurAssocDiv" style="clear:both;margin:15px;">
			<?php
			if($assocArr){
				foreach($assocArr as $assocID => $assocUnit){
					echo '<div style="margin-bottom: 10px">';
					echo '<span title="Defined by: '.(isset($assocUnit['definedBy'])?$assocUnit['definedBy']:'unknown').' ('.$assocUnit['ts'].')'.'">'.$assocUnit['relationship'];
					if($assocUnit['subType']) echo ' ('.$assocUnit['subType'].')';
					echo ': ';
					if($assocUnit['identifier']){
						$identifier = $assocUnit['identifier'];
						if($assocUnit['occidAssociate']) $identifier = '<a href="#" onclick="openIndividual('.$assocUnit['occidAssociate'].')">'.$identifier.'</a>';
						elseif($assocUnit['resourceUrl']) $identifier = '<a href="'.$assocUnit['resourceUrl'].'" target="_blank">'.$identifier.'</a>';
						echo $identifier;
					}
					elseif($assocUnit['sciname']) echo $assocUnit['sciname'];
					echo '</span>';
					?>
					<form action="resourcehandler.php" method="post" style="display:inline">
						<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="occindex" type="hidden" value="<?php echo $occIndex; ?>" />
						<input name="delassocid" type="hidden" value="<?php echo $assocID; ?>" />
						<input type="image" src="../../images/del.png" style="width:13px" />
					</form>
					<?php
					if($assocUnit['locationOnHost']) echo '<div style="margin-left:15px">Location on host: '.$assocUnit['locationOnHost'].'</div>';
					if($assocUnit['notes']) echo '<div style="margin-left:15px">Notes: '.$assocUnit['notes'].'</div>';
					echo '</div>';
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
		if($userChecklists){
			?>
			<div style="float:right;margin-right:15px;">
				<a href="#" onclick="toggle('voucheradddiv');return false;" title="<?php echo $LANG['LINK_TO_CHECKLIST']; ?>" ><img src="../../images/add.png" /></a>
			</div>
			<div id="voucheradddiv" style="display:<?php echo ($checklistArr?'none':'block'); ?>;">
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
					<input name="submitaction" type="submit" value="<?php echo $LANG['LINK_TO_CHECKLIST_2']; ?>" />
				</form>
			</div>
			<?php
		}
		//Display list of checklists specimen is linked to
		if($checklistArr){
			foreach($checklistArr as $vClid => $vClName){
				echo '<div style="margin:3px">';
				echo '<a href="../../checklists/checklist.php?showvouchers=1&clid='.$vClid.'" target="_blank">'.$vClName.'</a> ';
				if(array_key_exists($vClid, $userChecklists)){
					$href = 'occurrenceeditor.php?submitaction=deletevoucher&delclid='.$vClid.'&occid='.$occid.'&tabtarget=3';
					echo '<a href="'.$href.'" title="'.$LANG['DELETE_VOUCHER_LINK'].'" onclick="return confirm(\''.$LANG['SURE_REMOVE_VOUCHER'].'\'">';
					echo '<img src="../../images/drop.png" style="width:12px;" />';
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
					echo '<button name="unlinkthisdupebutton" onclick="deleteDuplicateLink('.$dupid.','.$occid.')">'.$LANG['REM_FROM_CLUSTER'].'</button>';
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
					echo '<div style="font-weight:bold;font-size:120%;margin:15px 0px;">'.$LANG['NO_LINKED'].'</div>';
				}
			}
			?>
		</div>
	</fieldset>
</div>
<div id="geneticdiv">
	<fieldset>
		<legend><?php echo $LANG['GEN_RES']; ?></legend>
		<div style="float:right;">
			<a href="#" onclick="toggle('genadddiv');return false;" title="<?php echo $LANG['ADD_NEW_GEN']; ?>" ><img src="../../images/add.png" /></a>
		</div>
		<div id="genadddiv" style="display:<?php echo ($genticArr?'none':'block'); ?>;">
			<fieldset>
				<legend><b><?php echo $LANG['ADD_NEW_RES']; ?></b></legend>
				<form name="addgeneticform" method="post" action="occurrenceeditor.php">
					<div style="margin:2px;">
						<b><?php echo $LANG['NAME']; ?>:</b><br/>
						<input name="resourcename" type="text" value="" style="width:50%" />
					</div>
					<div style="margin:2px;">
						<b><?php echo $LANG['IDENTIFIER']; ?>:</b><br/>
						<input name="identifier" type="text" value="" style="width:50%" />
					</div>
					<div style="margin:2px;">
						<b><?php echo $LANG['LOCUS']; ?>:</b><br/>
						<input name="locus" type="text" value="" style="width:95%" />
					</div>
					<div style="margin:2px;">
						<b><?php echo $LANG['URL']; ?>:</b><br/>
						<input name="resourceurl" type="text" value="" style="width:95%" />
					</div>
					<div style="margin:2px;">
						<b><?php echo $LANG['NOTES']; ?>:</b><br/>
						<input name="notes" type="text" value="" style="width:95%" />
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
				<div style="float:right;">
					<a href="#" onclick="toggle('genedit-<?php echo $genId; ?>');return false;"><img src="../../images/edit.png" /></a>
				</div>
				<div style="margin:15px;">
					<div style="font-weight:bold;margin-bottom:5px;"><?php echo $gArr['name']; ?></div>
					<div style="margin-left:15px;"><b><?php echo $LANG['IDENTIFIER']; ?>:</b> <?php echo $gArr['id']; ?></div>
					<div style="margin-left:15px;"><b><?php echo $LANG['LOCUS']; ?>:</b> <?php echo $gArr['locus']; ?></div>
					<div style="margin-left:15px;">
						<b><?php echo $LANG['URL']; ?>:</b> <a href="<?php echo $gArr['resourceurl']; ?>" target="_blank"><?php echo $gArr['resourceurl']; ?></a>
					</div>
					<div style="margin-left:15px;"><b><?php echo $LANG['NOTES']; ?>:</b> <?php echo $gArr['notes']; ?></div>
				</div>
				<div id="genedit-<?php echo $genId; ?>" style="display:none;margin-left:25px;">
					<fieldset>
						<legend><?php echo $LANG['GEN_RES_EDITOR']; ?></legend>
						<form name="editgeneticform" method="post" action="occurrenceeditor.php">
							<div style="margin:2px;">
								<b><?php echo $LANG['NAME']; ?>:</b><br/>
								<input name="resourcename" type="text" value="<?php echo $gArr['name']; ?>" style="width:50%" />
							</div>
							<div style="margin:2px;">
								<b><?php echo $LANG['IDENTIFIER']; ?>:</b><br/>
								<input name="identifier" type="text" value="<?php echo $gArr['id']; ?>" style="width:50%" />
							</div>
							<div style="margin:2px;">
								<b><?php echo $LANG['LOCUS']; ?>:</b><br/>
								<input name="locus" type="text" value="<?php echo $gArr['locus']; ?>" style="width:95%" />
							</div>
							<div style="margin:2px;">
								<b><?php echo $LANG['URL']; ?>:</b><br/>
								<input name="resourceurl" type="text" value="<?php echo $gArr['resourceurl']; ?>" style="width:95%" />
							</div>
							<div style="margin:2px;">
								<b><?php echo $LANG['NOTES']; ?>:</b><br/>
								<input name="notes" type="text" value="<?php echo $gArr['notes']; ?>" style="width:95%" />
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
								<button name="subbut" type="button" value="Delete Resource" onclick="submitDeleteGeneticResource(this.form)" ><?php echo $LANG['DEL_RES']; ?></button>
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