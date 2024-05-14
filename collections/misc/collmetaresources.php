<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCollectionProfile.php');
include_once($SERVER_ROOT.'/content/lang/collections/misc/collmetaresources.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
if(!is_numeric($collid)) $collid = 0;

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif($collid){
	if(array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin'])){
		$isEditor = true;
	}
}

if($collid && $isEditor){
	$collManager = new OccurrenceCollectionProfile('write');
	$collManager->setCollid($collid);
	$collMetaArr = current($collManager->getCollectionMetadata());

	$defaultLang = 'en';
	if(isset($EXTENDED_LANG) && $EXTENDED_LANG) $defaultLang = $EXTENDED_LANG;
	elseif(isset($DEFAULT_LANG)) $defaultLang = strtolower($DEFAULT_LANG);
	$langArr = explode(',',$defaultLang);
	?>
	<style>
		.link-div{ margin:10px 0px; }
		.link-div a{ margin-right:10px; }
		.link-div img{ width:13px; }
		.title-div{ margin-left: 10px; }
		.form-button{ margin:10px }
		#contact-listing{ padding: 10px 0px }
		.contact-div{ margin:10px 0px; }
		.contact-div a{ margin: 0px 10px; }
		.contact-div img{ width:13px; }
		#editContact-span{ display:none; }
		hr{ margin:10px 0px; }
	</style>
	<div id="contacts_resources">
		<h1 class="page-heading screen-reader-only">Contacts and Resources Tab</h1>
		<fieldset>
			<legend><?php echo (isset($LANG['LINK_RESOURCE'])?$LANG['LINK_RESOURCE']:'Link Resource Listing'); ?></legend>
			<div id="link-listing">
				<?php
				if($resourceArr = json_decode($collMetaArr['resourcejson'],true)){
					foreach($resourceArr as $key => $valueArr){
						echo '<div class="link-div"><span class="label">Link:</span> ';
						echo '<a href="' . htmlspecialchars($valueArr['url'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($valueArr['url'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
						echo '<a href="#" onclick="editLink('.$key.');return false"><img src="../../images/edit.png" style="width:1.2em" /></a>';
						echo '<a href="#" onclick="deleteLink('.$key.');return false"><img src="../../images/del.png" style="width:1.2em" /></a>';
						foreach($valueArr['title'] as $langCode => $titleValue){
							$langStr = $langCode;
							if($langCode == 'en') $langStr = (isset($LANG['ENGLISH'])?$LANG['ENGLISH']:'English');
							else if($langCode == 'es') $langStr = (isset($LANG['SPANISH'])?$LANG['SPANISH']:'Spanish');
							else if($langCode == 'fr') $langStr = (isset($LANG['FRENCH'])?$LANG['FRENCH']:'French');
							else if($langCode == 'pr') $langStr = (isset($LANG['PORTUGUESE'])?$LANG['PORTUGUESE']:'Portuguese');
							echo '<div class="title-div"><span class="label">'.(isset($LANG['TITLE'])?$LANG['TITLE']:'Title').' ('.$langStr.'):</span> '.$titleValue.'</div>';
						}
						echo '</div>';
					}
				}
				else echo (isset($LANG['NO_LINKS'])?$LANG['NO_LINKS']:'No links have yet been defined');
				?>
			</div>
			<div class="field-block">
				<form name="resourceLinkForm" action="collmetadata.php" method="post" onsubmit="return verifyResourceLinkForm(this)">
					<div class="form-button">
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="action" type="hidden" value="saveResourceLink" />
						<input id="resourceJsonInput" name="resourcejson" type="hidden" value="<?php echo htmlspecialchars($collMetaArr['resourcejson']); ?>" />
					</div>
				</form>
			</div>
			<hr/>
			<fieldset style="width:90%;">
				<legend><?php echo (isset($LANG['ADD_EDIT_LINK'])?$LANG['ADD_EDIT_LINK']:'Add/Edit External Link Resource'); ?></legend>
				<form name="linkForm" onsubmit="return false;">
					<div class="field-block" style="">
						<span class="field-label">URL:</span>
						<span class="field-elem"><input name="url" type="text" style="width:600px;" /></span>
					</div>
					<?php
					foreach($langArr as $langCode){
						$langStr = $langCode;
						if($langCode == 'en') $langStr = (isset($LANG['ENGLISH'])?$LANG['ENGLISH']:'English');
						elseif($langCode == 'es') $langStr = (isset($LANG['SPANISH'])?$LANG['SPANISH']:'Spanish');
						elseif($langCode == 'fr') $langStr = (isset($LANG['FRENCH'])?$LANG['FRENCH']:'French');
						elseif($langCode == 'pr') $langStr = (isset($LANG['PORTUGUESE'])?$LANG['PORTUGUESE']:'Portuguese');
						?>
						<div class="field-block" style="">
							<span class="field-label"><?php echo (isset($LANG['CAPTION_OVERRIDE'])?$LANG['CAPTION_OVERRIDE']:'Caption override').' ('.$langStr.'):'; ?></span>
							<span class="field-elem">
								<input name="title-<?php echo $langCode; ?>" type="text" value="Homepage" />
							</span>
						</div>
						<?php
					}
					?>
					<div class="field-block" id="add-link-div">
						<span class="form-button"><button type="button" value="addLink" onclick="addLink(this.form);"><?php echo (isset($LANG['ADD_LINK'])?$LANG['ADD_LINK']:'Add Link'); ?></button></span>
					</div>
					<div class="field-block" id="edit-link-div" style="display: none">
						<span class="form-button"><button type="button" value="editLink" onclick="applyEdits(this.form);"><?php echo (isset($LANG['APPLY_EDITS'])?$LANG['APPLY_EDITS']:'Apply Edits'); ?></button></span>
						<input name="linkIndex" type="hidden" />
					</div>
				</form>
			</fieldset>
		</fieldset>
		<fieldset>
			<legend><?php echo (isset($LANG['CONTACTS'])?$LANG['CONTACTS']:'Contacts'); ?></legend>
			<div id="contact-listing">
				<?php
				if($contactArr = json_decode($collMetaArr['contactjson'],true)){
					foreach($contactArr as $key => $valueArr){
						echo '<div class="contact-div">';
						echo '<div>'.$valueArr['firstName'].' '.$valueArr['lastName'];
						echo '<a href="#" onclick="editContact('.$key.');return false"><img src="../../images/edit.png" style="width:1.2em" /></a>';
						?>
						<form name="contactDelForm" action="collmetadata.php" method="post" style="display:inline">
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="contactIndex" type="hidden" value="<?php echo $key; ?>" />
							<input name="action" type="hidden" value="deleteContact" />
							<input type="image" src="../../images/del.png" style="width:1.2em" />
						</form>
						<?php
						echo '</div>';
						if(isset($valueArr['role'])) echo '<div style="margin-left:15px"><span class="label">'.(isset($LANG['ROLE'])?$LANG['ROLE']:'Role').': </span>'.$valueArr['role'].'</div>';
						if(isset($valueArr['email'])){
							echo '<div style="margin-left:15px">';
							echo '<span class="label">'.(isset($LANG['EMAIL'])?$LANG['EMAIL']:'Email').': </span>'.$valueArr['email'];
							if(isset($valueArr['centralContact'])) echo ' ('.(isset($LANG['C_CONTACT'])?$LANG['C_CONTACT']:'central contact').')';
							echo '</div>';
						}
						if(isset($valueArr['phone'])) echo '<div style="margin-left:15px"><span class="label">'.(isset($LANG['PHONE'])?$LANG['PHONE']:'phone').': </span>'.$valueArr['phone'].'</div>';
						if(isset($valueArr['orcid'])){
							echo '<div style="margin-left:15px">';
							echo '<span class="label">ORCID #: </span><a href="https://orcid.org/' . htmlspecialchars($valueArr['orcid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($valueArr['orcid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
							echo '</div>';
						}
						echo '</div>';
					}
				}
				else{
					echo (isset($LANG['NO_CONTACTS'])?$LANG['NO_CONTACTS']:'No contacts have yet been defined');
				}
				?>
			</div>
			<hr/>
			<fieldset style="width:90%;">
				<legend><?php echo (isset($LANG['ADD_EDIT_CONTACT'])?$LANG['ADD_EDIT_CONTACT']:'Add/Edit Contact'); ?></legend>
				<form name="contactEditForm" action="collmetadata.php" method="post" onsubmit="return verifyContactForm(this);">
					<div class="field-block">
						<span class="field-label"><?php echo (isset($LANG['FIRST_NAME'])?$LANG['FIRST_NAME']:'First name'); ?>:</span>
						<span class="field-elem"><input name="firstName" type="text" required /></span>
					</div>
					<div class="field-block">
						<span class="field-label"><?php echo (isset($LANG['LAST_NAME'])?$LANG['LAST_NAME']:'Last name'); ?>:</span>
						<span class="field-elem"><input name="lastName" type="text" required /></span>
					</div>
					<div class="field-block">
						<span class="field-label"><?php echo (isset($LANG['ROLE'])?$LANG['ROLE']:'Role'); ?>:</span>
						<span class="field-elem"><input name="role" type="text" /></span>
					</div>
					<div class="field-block">
						<span class="field-label"><?php echo (isset($LANG['EMAIL'])?$LANG['EMAIL']:'Email'); ?>:</span>
						<span class="field-elem"><input name="email" type="text" /></span>
					</div>
					<div class="field-block">
						<span class="field-elem"><input name="centralContact" type="checkbox" value="1" /></span>
						<span class="field-label"><?php echo (isset($LANG['IS_C_CONTACT'])?$LANG['IS_C_CONTACT']:'is central contact'); ?></span>
					</div>
					<div class="field-block">
						<span class="field-label"><?php echo (isset($LANG['PHONE'])?$LANG['PHONE']:'Phone'); ?>:</span>
						<span class="field-elem"><input name="phone" type="text" /></span>
					</div>
					<div class="field-block">
						<span class="field-label"><?php echo (isset($LANG['ORCID'])?$LANG['ORCID']:'ORCID #'); ?>:</span>
						<span class="field-elem"><input name="orcid" type="text" /></span>
					</div>
					<div class="field-block">
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="contactIndex" type="hidden" value="" />
						<span class="form-button"><button name="action" type="submit" value="saveContact"><span id="addContact-span"><?php echo (isset($LANG['ADD_CONTACT'])?$LANG['ADD_CONTACT']:'Add Contact'); ?></span>
						<span id="editContact-span"><?php echo (isset($LANG['EDIT_CONTACT'])?$LANG['EDIT_CONTACT']:'Edit Contact'); ?></span></button></span>
						<span class="form-button"><button name="reset" type="reset" onclick="resetContactForm()"><?php echo (isset($LANG['RESET_FORM'])?$LANG['RESET_FORM']:'Reset Form'); ?></button></span>
					</div>
				</form>
			</fieldset>
		</fieldset>
		<fieldset>
			<legend><?php echo (isset($LANG['MAILING_ADD'])?$LANG['MAILING_ADD']:'Mailing Address'); ?></legend>
			<?php
			if($instArr = $collManager->getAddress()){
				?>
				<div style="margin:25px;">
					<?php
					echo '<div>';
					echo $instArr['institutionname'].($instArr['institutioncode']?' ('.$instArr['institutioncode'].')':'');
					?>
					<a href="institutioneditor.php?emode=1&targetcollid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&iid=' . htmlspecialchars($instArr['iid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" title="<?php echo htmlspecialchars((isset($LANG['EDIT_ADDRESS'])?$LANG['EDIT_ADDRESS']:'Edit institution address'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
						<img src="../../images/edit.png" style="width:1.2em;" />
					</a>
					<a href="collmetadata.php?tabindex=1&collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&removeiid=' . htmlspecialchars($instArr['iid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" title="<?php echo htmlspecialchars((isset($LANG['UNLINK_ADDRESS'])?$LANG['UNLINK_ADDRESS']:'Unlink institution address'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
						<img src="../../images/drop.png" style="width:1.2em;" />
					</a>
					<?php
					echo '</div>';
					if($instArr['address1']) echo '<div>'.$instArr['address1'].'</div>';
					if($instArr['address2']) echo '<div>'.$instArr['address2'].'</div>';
					if($instArr['city'] || $instArr['stateprovince']) echo '<div>'.$instArr['city'].', '.$instArr['stateprovince'].' '.$instArr['postalcode'].'</div>';
					if($instArr['country']) echo '<div>'.$instArr['country'].'</div>';
					if($instArr['phone']) echo '<div>'.$instArr['phone'].'</div>';
					if($instArr['contact']) echo '<div>'.$instArr['contact'].'</div>';
					if($instArr['email']) echo '<div>'.$instArr['email'].'</div>';
					if($instArr['url']) echo '<div><a href="' . htmlspecialchars($instArr['url'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($instArr['url'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
					if($instArr['notes']) echo '<div>'.$instArr['notes'].'</div>';
					?>
				</div>
				<?php
			}
			else{
				//Link new institution
				?>
				<div style="margin:40px;"><b><?php echo (isset($LANG['NO_ADDRESS'])?$LANG['NO_ADDRESS']:'No addesses linked'); ?></b></div>
				<div style="margin:20px;">
					<form name="addaddressform" action="collmetadata.php" method="post" onsubmit="return verifyAddAddressForm(this)">
						<select name="iid" style="width:425px;">
							<option value=""><?php echo (isset($LANG['SEL_ADDRESS'])?$LANG['SEL_ADDRESS']:'Select Institution Address'); ?></option>
							<option value="">------------------------------------</option>
							<?php
							$addrArr = $collManager->getInstitutionArr();
							foreach($addrArr as $iid => $name){
								echo '<option value="'.$iid.'">'.$name.'</option>';
							}
							?>
						</select>
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="action" type="submit" value="<?php echo (isset($LANG['LINK_ADDRESS'])?$LANG['LINK_ADDRESS']:'Link Address'); ?>" />
					</form>
					<div style="margin:15px;">
						<a href="institutioneditor.php?emode=1&instcode=<?= $collMetaArr['institutioncode'] ?>&targetcollid=<?= $collid ?>" title="<?= (isset($LANG['ADD_ADDRESS']) ? htmlspecialchars($LANG['ADD_ADDRESS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : 'Add a new address not on the list') ?>">
							<b><?= (isset($LANG['ADD_INST']) ? $LANG['ADD_INST'] : 'Add an institution not on list') ?></b>
						</a>
					</div>
				</div>
				<?php
			}
			?>
		</fieldset>
	</div>
	<script type="text/javascript">
		//var resourceJSON = [{"title":{"en":"link1","es":"enlace1"},"url":"https:\/\/swbiodiversity.org\/seinet\/"},{"title":{"en":"link2","es":"enlace2"},"url":"https:\/\/swbiodiversity.org\/seinet2\/"}];
		var resourceJSON = <?php echo (isset($collMetaArr['resourcejson'])?$collMetaArr['resourcejson']:'[]'); ?>;
		var langArr = [<?php echo '"'.implode('","', $langArr).'"';?>];
		var contactJSON = <?php echo (isset($collMetaArr['contactjson'])?$collMetaArr['contactjson']:'[]'); ?>;

		function addLink(f){
			var jsonObj = getFormObj(f);
			if(jsonObj) resourceJSON.push(jsonObj);
			submitResourceForm();
		}

		function editLink(linkIndex){
			var f = document.linkForm;
			clearForm();
			f.url.value = resourceJSON[linkIndex].url;
			for(var i = 0; i < langArr.length; i++) {
				try {
					var titleValue = resourceJSON[linkIndex].title[langArr[i]];
					if(titleValue != undefined) f["title-"+langArr[i]].value = titleValue;
				}
				catch(err) {}
			}
			f.linkIndex.value = linkIndex;
			$("#add-link-div").hide();
			$("#edit-link-div").show();
		}

		function applyEdits(f){
			linkIndex = f.linkIndex.value;
			if(linkIndex!=""){
				var f = document.linkForm;
				var jsonObj = getFormObj(f);
				if(jsonObj) resourceJSON[linkIndex] = jsonObj;
				submitResourceForm();
			}
		}

		function getFormObj(f){
			var jsonObj;
			if(f.url.value != ""){
				jsonObj = {"title":{},"url":f.url.value};
				for(var i = 0; i < langArr.length; i++) {
					if(f["title-"+langArr[i]] && f["title-"+langArr[i]].value){
						jsonObj.title[langArr[i]] = f["title-"+langArr[i]].value;
					}
				}
				clearForm();
			}
			else alert("<?php echo (isset($LANG['LINK_URL_REQ'])?$LANG['LINK_URL_REQ']:'Link URL and title is required'); ?>");
			return jsonObj;
		}

		function deleteLink(linkIndex){
			resourceJSON.splice(linkIndex,1);
			submitResourceForm();
		}

		function clearForm(){
			var f = document.linkForm;
			f.url.value = "";
			f.url.focus();
			for(var i = 0; i < langArr.length; i++) {
				f["title-"+langArr[i]].value = "";
			}
			$("#add-link-div").show();
			$("#edit-link-div").hide();
		}

		function submitResourceForm(){
			var f = document.resourceLinkForm
			f.resourcejson.value = JSON.stringify(resourceJSON);
			f.submit();
		}

		function editContact(contactIndex){
			var f = document.contactEditForm;
			f.contactIndex.value = contactIndex;
			f.firstName.value = contactJSON[contactIndex].firstName;
			f.lastName.value = contactJSON[contactIndex].lastName;
			if(contactJSON[contactIndex].role != undefined) f.role.value = contactJSON[contactIndex].role;
			else f.role.value = "";
			if(contactJSON[contactIndex].email != undefined) f.email.value = contactJSON[contactIndex].email;
			else f.email.value = "";
			if(contactJSON[contactIndex].centralContact != undefined) f.centralContact.checked = true;
			else f.centralContact.checked = false;
			if(contactJSON[contactIndex].phone != undefined) f.phone.value = contactJSON[contactIndex].phone;
			else f.phone.value = "";
			if(contactJSON[contactIndex].orcid != undefined) f.orcid.value = contactJSON[contactIndex].orcid;
			else f.orcid.value = "";
			$("#editContact-span").show();
			$("#addContact-span").hide();
		}

		function resetContactForm(){
			$("#editContact-span").hide();
			$("#addContact-span").show();
		}

		function verifyContactForm(f){
			if(f.firstName.value == ""){
				alert("<?php echo (isset($LANG['FIRST_NAME_REQ'])?$LANG['FIRST_NAME_REQ']:'First name is a required field'); ?>");
				return false;
			}
			if(f.lastName.value == ""){
				alert("<?php echo (isset($LANG['LAST_NAME_REQ'])?$LANG['LAST_NAME_REQ']:'Last name is a required field'); ?>");
				return false;
			}
			return true;
		}
	</script>
	<?php
}
?>