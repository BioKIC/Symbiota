<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistAdmin.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/checklists/checklistadminmeta.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/checklists/checklistadminmeta.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/checklists/checklistadminmeta.en.php');
header('Content-Type: text/html; charset='.$CHARSET);

$clid = array_key_exists('clid', $_REQUEST) ? filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$pid = array_key_exists('pid', $_REQUEST) ? filter_var($_REQUEST['pid'], FILTER_SANITIZE_NUMBER_INT) : 0;

$clManager = new ChecklistAdmin();
$clManager->setClid($clid);

$clArray = $clManager->getMetaData($pid);
$clArray = $clManager->cleanOutArray($clArray);
$defaultArr = array();
if(isset($clArray['defaultsettings']) && $clArray['defaultsettings']){
	$defaultArr = json_decode($clArray['defaultsettings'], true);
}
$dynamPropsArr = array();
if(isset($clArray['dynamicProperties']) && $clArray['dynamicProperties']){
	$dynamPropsArr = json_decode($clArray['dynamicProperties'], true);
}
?>
<script type="text/javascript" src="../js/tinymce/tinymce.min.js"></script>
<script type="text/javascript">
	var f = document.getElementById("checklisteditform");

	if(f.type.value == "excludespp") setExclusionChecklistMode(f);

	tinymce.init({
		selector: "textarea",
		width: "100%",
		height: 300,
		menubar: false,
		plugins: "link,charmap,code,paste",
		toolbar : "bold italic underline cut copy paste outdent indent undo redo subscript superscript removeformat link charmap code",
		default_link_target: "_blank",
		paste_as_text: true
	});

	function validateChecklistForm(f){
		if(f.name.value == ""){
			alert("<?php echo $LANG['NEED_NAME']; ?>");
			return false;
		}
		if(f.latcentroid.value != ""){
			if(f.longcentroid.value == ""){
				alert("<?php echo $LANG['NEED_LONG']; ?>");
				return false;
			}
			if(!isNumeric(f.latcentroid.value)){
				alert("<?php echo $LANG['LAT_NUMERIC']; ?>");
				return false;
			}
			if(Math.abs(f.latcentroid.value) > 90){
				alert("<?php echo $LANG['NO_NINETY']; ?>");
				return false;
			}
		}
		if(f.longcentroid.value != ""){
			if(f.latcentroid.value == ""){
				alert("<?php echo $LANG['NEED_LAT']; ?>");
				return false;
			}
			if(!isNumeric(f.longcentroid.value)){
				alert("<?php echo $LANG['LONG_NUMERIC']; ?>");
				return false;
			}
			if(Math.abs(f.longcentroid.value) > 180){
				alert("<?php echo $LANG['NO_ONE_EIGHTY']; ?>");
				return false;
			}
		}
		if(f.type){
			if(f.type.value == "rarespp" && f.locality.value == ""){
				alert("<?php echo $LANG['NEED_STATE']; ?>");
				return false;
			}
			else if(f.type.value == "excludespp" && f.excludeparent.value == ""){
				alert("<?php echo $LANG['NEED_PARENT']; ?>");
				return false;
			}
		}
		return true;
	}

	function checklistTypeChanged(f){
		if(f.type.value == "excludespp"){
			setExclusionChecklistMode(f);
		}
		else{
			f.excludeparent.style.display = "none";
			document.getElementById("accessDiv").style.display = "block";
			document.getElementById("authorDiv").style.display = "block";
			document.getElementById("locDiv").style.display = "block";
			document.getElementById("inclusiveClDiv").style.display = "block";
			document.getElementById("geoDiv").style.display = "block";
		}
	}

	function setExclusionChecklistMode(f){
		f.excludeparent.style.display = "inline";
		document.getElementById("accessDiv").style.display = "none";
		document.getElementById("authorDiv").style.display = "none";
		document.getElementById("locDiv").style.display = "none";
		document.getElementById("inclusiveClDiv").style.display = "none";
		document.getElementById("geoDiv").style.display = "none";
		f.activatekey.checked = false;
	}

	function openMappingAid() {
		mapWindow=open("<?php echo $CLIENT_ROOT; ?>/collections/tools/mappointaid.php?clid=<?php echo $clid; ?>&formname=editclmatadata&latname=latcentroid&longname=longcentroid","mapaid","resizable=0,width=1000,height=800,left=20,top=20");
	    if(mapWindow.opener == null) mapWindow.opener = self;
	}

	function openMappingPolyAid() {
		var latDec = document.getElementById("decimallatitude").value;
		var lngDec = document.getElementById("decimallongitude").value;
		mapWindow=open("<?php echo $CLIENT_ROOT; ?>/checklists/tools/mappolyaid.php?clid=<?php echo $clid; ?>&formname=editclmatadata&latname=latcentroid&longname=longcentroid&latdef="+latDec+"&lngdef="+lngDec,"mapaid","resizable=0,width=1000,height=800,left=20,top=20");
	    if(mapWindow.opener == null) mapWindow.opener = self;
	}

	function enableDisableExtServiceFields() {
		let xsrv = document.getElementById('externalservice');
		let xsid = document.getElementById('externalserviceid');
		let xstaxonfilter = document.getElementById('externalserviceiconictaxon');
		if(xsrv.value == '') {
			xsid.setAttribute("disabled","");
			xstaxonfilter.setAttribute("disabled","");
		} else {
			xsid.removeAttribute("disabled");
			xstaxonfilter.removeAttribute("disabled");
		}
	}
</script>
<?php
if(!$clid){
	?>
	<div style="float:right;">
		<a href="#" onclick="toggle('checklistDiv')" title="<?php echo htmlspecialchars($LANG['CREATE_CHECKLIST'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><img src="../images/add.png" style="width:1.5em;" /></a>
	</div>
	<?php
}
?>
<div id="checklistDiv" style="display:<?php echo ($clid?'block':'none'); ?>;">
	<form id="checklisteditform" action="<?php echo $CLIENT_ROOT; ?>/checklists/checklistadmin.php" method="post" name="editclmatadata" onsubmit="return validateChecklistForm(this)">
		<fieldset style="margin:15px;padding:10px;">
			<legend><b><?php echo ($clid?$LANG['EDITCHECKDET']:$LANG['CREATECHECKDET']); ?></b></legend>
			<div>
				<b><?php echo $LANG['CHECKNAME']; ?></b><br/>
				<input type="text" name="name" style="width:95%" value="<?php echo $clManager->getClName();?>" />
			</div>
			<div id="authorDiv">
				<b><?php echo $LANG['AUTHORS'];?></b><br/>
				<input type="text" name="authors" style="width:95%" value="<?php echo ($clArray?$clArray["authors"]:''); ?>" />
			</div>
			<div>
				<b><?php echo $LANG['CHECKTYPE'];?></b><br/>
				<?php
				$userClArr = $clManager->getUserChecklistArr();
				?>
				<select name="type" onchange="checklistTypeChanged(this.form)">
					<option value="static"><?php echo $LANG['GENCHECK'];?></option>
					<?php
					if($userClArr){
						?>
						<option value="excludespp" <?php echo ($clArray && $clArray["type"]=='excludespp'?'SELECTED':'') ?>><?php echo $LANG['EXCLUDESPP']; ?></option>
						<?php
					}
					if(isset($GLOBALS['USER_RIGHTS']['RareSppAdmin']) || $IS_ADMIN){
						echo '<option value="rarespp"' . ($clArray && $clArray["type"]=='rarespp'?'SELECTED':'') . '>' . $LANG['RARETHREAT'] . '</option>';
					}
					?>
				</select>
				<?php
				if($userClArr){
					?>
					<select name="excludeparent" style="<?php echo ($clid && isset($clArray['excludeparent'])?'':'display:none'); ?>">
						<option value=""><?php echo $LANG['SELECT_PARENT']; ?></option>
						<option value="">-------------------------------</option>
						<?php
						foreach($userClArr as $userClid => $userClValue){
							echo '<option value="' . $userClid . '" ' . (isset($clArray['excludeparent'])&&$userClid==$clArray['excludeparent']?'SELECTED':'') . '>' . $userClValue . '</option>';
						}
						?>
					</select>
					<?php
				}
				?>
			</div>
			<div class="top-breathing-room-rel">
				<b><?php echo $LANG['EXTSERVICE']; ?></b><br/>
				<select name="externalservice" id="externalservice" onchange="enableDisableExtServiceFields()">
					<option value=""></option>
					<option value="">-------------------------------</option>
					<option value="inaturalist" <?php echo ((isset($dynamPropsArr['externalservice']) && $dynamPropsArr['externalservice']=='inaturalist')?'selected':''); ?>><?php echo $LANG['INATURALIST']; ?></option>
				</select>
			</div>
			<div style="width:100%" class="top-breathing-room-rel">
				<div style="float:left;width:25%">
				<b><?php echo $LANG['EXTSERVICEID']; ?></b><br/>
				<input type="text" name="externalserviceid" id="externalserviceid" style="width:100%" value="<?php echo ($dynamPropsArr?$dynamPropsArr['externalserviceid']:''); ?>" />
				</div><div style="float:left;margin-left:15px;">
				<b><?php echo $LANG['EXTSERVICETAXON']; ?></b><br/>
				<input type="text" name="externalserviceiconictaxon" id="externalserviceiconictaxon" style="width:100%" value="<?php echo ($dynamPropsArr?$dynamPropsArr['externalserviceiconictaxon']:''); ?>" />
				</div>
			</div>
			<div id="locDiv" style="clear:both" class="top-breathing-room-rel">
				<b><?php echo $LANG['LOC']; ?></b><br/>
				<input type="text" name="locality" style="width:95%" value="<?php echo ($clArray?$clArray["locality"]:''); ?>" />
			</div>
			<div>
				<b><?php echo $LANG['CITATION']; ?></b><br/>
				<input type="text" name="publication" style="width:95%" value="<?php echo ($clArray?$clArray["publication"]:''); ?>" />
			</div>
			<div>
				<b><?php echo $LANG['ABSTRACT']; ?></b><br/>
				<textarea name="abstract" style="width:95%" rows="6"><?php echo ($clArray?$clArray["abstract"]:''); ?></textarea>
			</div>
			<div>
				<b><?php echo $LANG['NOTES']; ?></b><br/>
				<input type="text" name="notes" style="width:95%" value="<?php echo ($clArray?$clArray["notes"]:''); ?>" />
			</div>
			<div id="inclusiveClDiv">
				<b><?php echo $LANG['REFERENCE_CHECK']; ?>:</b><br/>
				<select name="parentclid">
					<option value=""><?php echo $LANG['NONE']; ?></option>
					<option value="">----------------------------------</option>
					<?php
					$refClArr = $clManager->getReferenceChecklists();
					foreach($refClArr as $id => $name){
						echo '<option value="'.$id.'" '.($clArray && $id==$clArray['parentclid']?'SELECTED':'').'>'.$name.'</option>';
					}
					?>
				</select>
			</div>
			<div id="geoDiv" style="width:100%" class="top-breathing-room-rel">
				<div style="float:left;">
					<b><?php echo $LANG['LATCENT']; ?></b><br/>
					<input id="decimallatitude" type="text" name="latcentroid" style="width:110px;" value="<?php echo ($clArray?$clArray["latcentroid"]:''); ?>" />
				</div>
				<div style="float:left;margin-left:15px;">
					<b><?php echo $LANG['LONGCENT']; ?></b><br/>
					<input id="decimallongitude" type="text" name="longcentroid" style="width:110px;" value="<?php echo ($clArray?$clArray["longcentroid"]:''); ?>" />
				</div>
				<div style="float:left;margin:25px 3px;">
					<a href="#" onclick="openMappingAid();return false;"><img src="../images/world.png" style="width:1em;" /></a>
				</div>
				<div style="float:left;margin-left:15px;">
					<b><?php echo $LANG['POINTRAD']; ?></b><br/>
					<input id="coordinateuncertaintyinmeters" type="number" name="pointradiusmeters" style="width:110px;" value="<?php echo ($clArray?$clArray["pointradiusmeters"]:''); ?>" />
				</div>
			</div>
			<div style="clear:both" class="top-breathing-room-rel">
				<fieldset style="width:350px;padding:10px">
					<legend><b><?php echo $LANG['POLYFOOT']; ?></b></legend>
					<span id="polyDefDiv" style="display:<?php echo ($clArray && $clArray["hasfootprintwkt"]?'inline':'none'); ?>;">
						<?php echo $LANG['POLYGON_DEFINED']; ?>
					</span>
					<span id="polyNotDefDiv" style="display:<?php echo ($clArray && $clArray["hasfootprintwkt"]?'none':'inline'); ?>;">
						<?php echo $LANG['POLYGON_NOT_DEFINED']; ?>
					</span>
					<span style="margin:10px;"><a href="#" onclick="openMappingPolyAid();return false;" title="<?php echo $LANG['CREATE_EDIT_POLYGON']; ?>"><img src="../images/world.png" style="width:1em;" /></a></span>
					<input type="hidden" id="footprintwkt" name="footprintwkt" value="<?= $clArray['footprintwkt']?>" />
				</fieldset>
			</div>
			<div style="clear:both;" class="top-breathing-room-rel">
				<fieldset style="width:600px;">
					<legend><b><?php echo $LANG['DEFAULTDISPLAY']; ?></b></legend>
					<div>
						<?php
						echo "<input id='dsynonyms' name='dsynonyms' type='checkbox' value='1' " . (isset($defaultArr["dsynonyms"])&&$defaultArr["dsynonyms"]?"checked":"") . " /> " . $LANG['DISPLAY_SYNONYMS'];
						?>
					</div>
					<div>
						<?php
						//Display Common Names: 0 = false, 1 = true
						if($DISPLAY_COMMON_NAMES) echo "<input id='dcommon' name='dcommon' type='checkbox' value='1' " . (($defaultArr&&$defaultArr["dcommon"])?"checked":"") . " /> " . $LANG['COMMON'];
						?>
					</div>
					<div>
						<!-- Display as Images: 0 = false, 1 = true  -->
						<input name='dimages' id='dimages' type='checkbox' value='1' <?php echo (($defaultArr&&$defaultArr["dimages"])?"checked":""); ?> onclick="showImagesDefaultChecked(this.form);" />
						<?php echo $LANG['DISPLAYIMAGES'];?>
					</div>
					<div>
						<!-- Display as Voucher Images: 0 = false, 1 = true  -->
						<input name='dvoucherimages' id='dvoucherimages' type='checkbox' value='1' <?php echo ((isset($defaultArr['dvoucherimages'])&&$defaultArr['dvoucherimages'])?"checked":""); ?> />
						<?php echo $LANG['DISPLAYVOUCHERIMAGES'];?>
					</div>
					<div>
						<!-- Display Details: 0 = false, 1 = true  -->
						<input name='ddetails' id='ddetails' type='checkbox' value='1' <?php echo (($defaultArr&&$defaultArr["ddetails"])?"checked":""); ?> />
						<?php echo $LANG['SHOWDETAILS'];?>
					</div>
					<div>
						<!-- Display as Vouchers: 0 = false, 1 = true  -->
						<input name='dvouchers' id='dvouchers' type='checkbox' value='1' <?php echo (($defaultArr&&$defaultArr["dimages"])?"disabled":(($defaultArr&&$defaultArr["dvouchers"])?"checked":"")); ?>/>
						<?php echo $LANG['NOTESVOUC'];?>
					</div>
					<div>
						<!-- Display Taxon Authors: 0 = false, 1 = true  -->
						<input name='dauthors' id='dauthors' type='checkbox' value='1' <?php echo (($defaultArr&&$defaultArr["dimages"])?"disabled":(($defaultArr&&$defaultArr["dauthors"])?"checked":"")); ?>/>
						<?php echo $LANG['TAXONAUTHOR'];?>
					</div>
					<div>
						<!-- Display Taxa Alphabetically: 0 = false, 1 = true  -->
						<input name='dalpha' id='dalpha' type='checkbox' value='1' <?php echo (!empty($defaultArr['dalpha'])?'checked':''); ?> />
						<?php echo $LANG['TAXONABC'];?>
					</div>
					<div>
						<!-- Display Taxa Alphabetically: 0 = false, 1 = true  -->
						<input name='dsubgenera' id='dsubgenera' type='checkbox' value='1' <?php echo (!empty($defaultArr['dsubgenera'])?'checked':''); ?> >
						<?php echo $LANG['SHOWSUBGENERA'];?>
					</div>
					<div>
						<?php
						// Activate Identification key: 0 = false, 1 = true
						$activateKey = $KEY_MOD_IS_ACTIVE;
						if(array_key_exists('activatekey', $defaultArr??[])) $activateKey = $defaultArr["activatekey"];
						?>
						<input name='activatekey' type='checkbox' value='1' <?php echo ($activateKey?"checked":""); ?> />
						<?php echo $LANG['ACTIVATEKEY']; ?>
					</div>
				</fieldset>
			</div>
			<div id="sortSeqDiv" style="clear:both;margin-top:15px;">
				<b><?php echo $LANG['DEFAULT_SORT']; ?>:</b>
				<input name="sortsequence" type="number" value="<?php echo ($clArray?$clArray['sortsequence']:'50'); ?>" style="width:40px" />
			</div>
			<div id="accessDiv" style="clear:both;margin-top:15px;">
				<b><?php echo $LANG['ACCESS']; ?>:</b>
				<select name="access">
					<option value="private"><?php echo $LANG['PRIVATE']; ?></option>
					<option value="private-strict" <?php echo ($clArray && $clArray['access']=='private-strict'?'selected':''); ?>><?php echo $LANG['PRIVATE_STRICT']; ?></option>
					<option value="public" <?php echo ($clArray && $clArray['access']=='public'?'selected':''); ?>><?php echo $LANG['PUBLIC']; ?></option>
				</select>
			</div>
			<div style="clear:both;float:left;margin-top:15px;">
				<?php
				if($clid){
					echo '<button type="submit" name="submitaction" value="submitEdit">' . $LANG['SAVE_EDITS'] . '</button>';
				}
				else{
					echo '<button type="submit" name="submitaction" value="submitAdd">' . $LANG['ADDCHECKLIST'] . '</button>';
				}
				?>
			</div>
			<input type="hidden" name="tabindex" value="1" />
			<input type="hidden" name="uid" value="<?php echo $SYMB_UID; ?>" />
			<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
			<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
		</fieldset>
	</form>
</div>

<div>
	<?php
	if(array_key_exists("userid",$_REQUEST)){
		$userId = $_REQUEST["userid"];
		echo '<div style="font-weight:bold;font:bold 14pt;">' . $LANG['ASSIGNED_CHECKLISTS'] . '</div>';
		$listArr = $clManager->getManagementLists($userId);
		if(array_key_exists('cl',$listArr)){
			$clArr = $listArr['cl'];
			?>
			<ul>
			<?php
			foreach($clArr as $kClid => $vName){
				?>
				<li>
					<a href="../checklists/checklist.php?clid=<?php echo htmlspecialchars($kClid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=0">
						<?php echo htmlspecialchars($vName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
					</a>
					<a href="../checklists/checklistadmin.php?clid=<?php echo htmlspecialchars($kClid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1">
						<img src="../images/edit.png" style="width:1em;border:0px;" title="<?php echo htmlspecialchars($LANG['EDITCHECKLIST'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>" />
					</a>
				</li>
				<?php
			}
			?>
			</ul>
			<?php
		}
		else{
			?>
			<div style="margin:10px;">
				<div><?php echo $LANG['NO_CHECKLISTS']; ?></div>
				<div class="top-breathing-room-rel">
					<a href="#" onclick="toggle('checklistDiv')"><?php echo htmlspecialchars($LANG['CLICK_TO_CREATE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?></a>
				</div>
			</div>
			<?php
		}

		echo '<div style="font-weight:bold;font:bold 14pt;margin-top:25px;">' . $LANG['PROJ_ADMIN'] . '</div>';
		if(array_key_exists('proj',$listArr)){
			$projArr = $listArr['proj'];
			?>
			<ul>
			<?php
			foreach($projArr as $pid => $projName){
				?>
				<li>
					<a href="../projects/index.php?pid=<?php echo htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=0">
						<?php echo htmlspecialchars($projName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
					</a>
					<a href="../projects/index.php?pid=<?php echo htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1">
						<img src="../images/edit.png" style="width:1em;border:0px;" title="<?php echo htmlspecialchars($LANG['EDIT_PROJECT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>" />
					</a>
				</li>
				<?php
			}
			?>
			</ul>
			<?php
		}
		else{
			echo '<div style="margin:10px;">' . $LANG['NO_PROJECTS'] . '</div>';
		}
	}
	?>
</div>
