<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
@include_once($SERVER_ROOT.'/content/lang/profile/userprofile.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

$userId = $_REQUEST['userid'];

//Sanitation
if(!is_numeric($userId)) $userId = 0;

$pHandler = new ProfileManager();
$pHandler->setUid($userId);
$person = $pHandler->getPerson();

$isSelf = true;
if($userId != $SYMB_UID) $isSelf = false;

$isEditor = false;
if(isset($SYMB_UID) && $SYMB_UID){
	if($isSelf || $IS_ADMIN) $isEditor = true;
}
if($isEditor){
	?>
	<div style="padding:15px;">
		<div>
			<div>
				<b><u><?php echo (isset($LANG['DETAILS'])?$LANG['DETAILS']:'Profile Details'); ?></u></b>
			</div>
			<div style="margin:20px;">
				<?php
				echo '<div>'.$person->getFirstName().' '.$person->getLastName().'</div>';
				if($person->getEmail()) echo '<div>'.$person->getEmail().'</div>';
				if($person->getGUID()){
					$guid = $person->getGUID();
					if(preg_match('/^\d{4}-\d{4}-\d{4}-\d{4}$/',$guid)) $guid = 'https://orcid.org/'.$guid;
					echo '<div>';
					if(substr($guid,0,4) == 'http') echo '<a href="'.$guid.'" target="_blank">';
					echo $guid;
					if(substr($guid,0,4) == 'http') echo '</a>';
					echo '</div>';
				}
				if($person->getTitle()) echo '<div>'.$person->getTitle().'</div>';
				if($person->getInstitution()) echo '<div>'.$person->getInstitution().'</div>';
				$cityStateStr = trim($person->getCity().', '.$person->getState().' '.$person->getZip(),' ,');
				if($cityStateStr) echo '<div>'.$cityStateStr.'</div>';
				if($person->getCountry()) echo '<div>'.$person->getCountry().'</div>';
				echo '<div>Login name: '.($person->getUserName()?$person->getUserName():'not registered').'</div>';
				?>
				<div style="font-weight:bold;margin-top:10px;">
					<div><a href="#" onclick="toggleEditingTools('profileeditdiv');return false;"><?php echo (isset($LANG['EDIT_PROFILE'])?$LANG['EDIT_PROFILE']:'Edit Profile'); ?></a></div>
					<div><a href="#" onclick="toggleEditingTools('pwdeditdiv');return false;"><?php echo (isset($LANG['CHANGE_PASSWORD'])?$LANG['CHANGE_PASSWORD']:'Change Password'); ?></a></div>
					<div><a href="#" onclick="toggleEditingTools('logineditdiv');return false;"><?php echo (isset($LANG['CHANGE_LOGIN'])?$LANG['CHANGE_LOGIN']:'Change Login'); ?></a></div>
					<div><a href="#" onclick="toggleEditingTools('managetokensdiv');return false;"><?php echo (isset($LANG['MANAGE_ACCESS'])?$LANG['MANAGE_ACCESS']:'Manage Access'); ?></a></div>
				</div>
			</div>
		</div>
		<div id="profileeditdiv" style="display:none;margin:15px;">
			<form name="editprofileform" action="viewprofile.php" method="post">
				<fieldset>
					<legend><b><?php echo (isset($LANG['EDIT_U_PROFILE'])?$LANG['EDIT_U_PROFILE']:'Edit User Profile'); ?></b></legend>
					<table cellspacing='1' style="width:100%;">
						<tr>
							<td><b><?php echo (isset($LANG['FIRST_NAME'])?$LANG['FIRST_NAME']:'First Name'); ?>:</b></td>
							<td>
								<div>
									<input id="firstname" name="firstname" size="40" value="<?php echo $person->getFirstName();?>" required />
								</div>
							</td>
						</tr>
						<tr>
							<td><b><?php echo (isset($LANG['LAST_NAME'])?$LANG['LAST_NAME']:'Last Name'); ?>:</b></td>
							<td>
								<div>
									<input id="lastname" name="lastname" size="40" value="<?php echo $person->getLastName();?>" required />
								</div>
							</td>
						</tr>
						<tr>
							<td><b><?php echo (isset($LANG['EMAIL'])?$LANG['EMAIL']:'Email Address'); ?>:</b></td>
							<td>
								<div>
									<input id="email" name="email" type="email" size="40" value="<?php echo $person->getEmail();?>" required />
								</div>
							</td>
						</tr>
						<tr>
							<td><b><?php echo (isset($LANG['ORCID'])?$LANG['ORCID']:'ORCID or other GUID'); ?>:</b></td>
							<td>
								<div>
									<input name="guid" type="text" size="40" value="<?php echo $person->getGUID();?>" />
								</div>
							</td>
						</tr>
						<tr>
							<td><b><?php echo (isset($LANG['TITLE'])?$LANG['TITLE']:'Title'); ?>:</b></td>
							<td>
								<div>
									<input name="title"  size="40" value="<?php echo $person->getTitle();?>">
								</div>
							</td>
						</tr>
						<tr>
							<td><b><?php echo (isset($LANG['INSTITUTION'])?$LANG['INSTITUTION']:'Institution'); ?>:</b></td>
							<td>
								<div>
									<input name="institution"  size="40" value="<?php echo $person->getInstitution();?>">
								</div>
							</td>
						</tr>
						<tr>
							<td><b><?php echo (isset($LANG['CITY'])?$LANG['CITY']:'City'); ?>:</b></td>
							<td>
								<div>
									<input id="city" name="city" size="40" value="<?php echo $person->getCity();?>">
								</div>
							</td>
						</tr>
						<tr>
							<td><b><?php echo (isset($LANG['STATE'])?$LANG['STATE']:'State'); ?>:</b></td>
							<td>
								<div>
									<input id="state" name="state" size="40" value="<?php echo $person->getState();?>">
								</div>
							</td>
						</tr>
						<tr>
							<td><b><?php echo (isset($LANG['ZIP'])?$LANG['ZIP']:'Zip Code'); ?>:</b></td>
							<td>
								<div>
									<input name="zip" size="40" value="<?php echo $person->getZip();?>">
								</div>
							</td>
						</tr>
						<tr>
							<td><b><?php echo (isset($LANG['COUNTRY'])?$LANG['COUNTRY']:'Country'); ?>:</b></td>
							<td>
								<div>
									<input id="country" name="country" size="40" value="<?php echo $person->getCountry();?>">
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<div style="margin:10px;">
									<input type="hidden" name="userid" value="<?php echo $userId;?>" />
									<button type="submit" name="action" value="Submit Edits"><?php echo (isset($LANG['SUBMIT_EDITS'])?$LANG['SUBMIT_EDITS']:'Submit Edits'); ?></button>
								</div>
							</td>
						</tr>
					</table>
				</fieldset>
			</form>
			<form name="delprofileform" action="viewprofile.php" method="post" onsubmit="return window.confirm('<?php echo (isset($LANG['SURE_DELETE'])?$LANG['SURE_DELETE']:'Are you sure you want to delete profile?'); ?>');">
				<fieldset style="padding:15px;width:200px;">
					<legend><b><?php echo (isset($LANG['DELETE_PROF'])?$LANG['DELETE_PROF']:'Delete Profile'); ?></b></legend>
					<input type="hidden" name="userid" value="<?php echo $userId;?>" />
					<button type="submit" name="action" value="deleteProfile"><?php echo (isset($LANG['DELETE_PROF'])?$LANG['DELETE_PROF']:'Delete Profile'); ?></button>
				</fieldset>
			</form>
		</div>
		<div id="pwdeditdiv" style="display:none;margin:15px;">
			<form name="changepwdform" action="viewprofile.php" method="post" onsubmit="return verifyPwdForm(this);">
				<fieldset style='padding:15px;width:500px;'>
					<legend><b><?php echo (isset($LANG['CHANGE_PASSWORD'])?$LANG['CHANGE_PASSWORD']:'Change Password'); ?></b></legend>
					<table>
						<?php
						if($isSelf){
							?>
							<tr>
								<td>
									<b><?php echo (isset($LANG['CURRENT_PWORD'])?$LANG['CURRENT_PWORD']:'Current Password'); ?>:</b>
								</td>
								<td>
									<input id="oldpwd" name="oldpwd" type="password"/>
								</td>
							</tr>
							<?php
						}
						?>
						<tr>
							<td>
								<b><?php echo (isset($LANG['NEW_PWORD'])?$LANG['NEW_PWORD']:'New Password'); ?>:</b>
							</td>
							<td>
								<input id="newpwd" name="newpwd" type="password"/>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo (isset($LANG['PWORD_AGAIN'])?$LANG['PWORD_AGAIN']:'New Password Again'); ?>:</b>
							</td>
							<td>
								<input id="newpwd2" name="newpwd2" type="password"/>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<input type="hidden" name="userid" value="<?php echo $userId;?>" />
								<button type="submit" name="action" value="Change Password"><?php echo (isset($LANG['CHANGE_PASSWORD'])?$LANG['CHANGE_PASSWORD']:'Change Password'); ?></button>
							</td>
						</tr>
					</table>
				</fieldset>
			</form>
		</div>
		<div id="logineditdiv" style="display:none;margin:15px;">
			<fieldset style='padding:15px;width:500px;'>
				<legend><b><?php echo (isset($LANG['CHANGE_USERNAME'])?$LANG['CHANGE_USERNAME']:'Change Username'); ?></b></legend>
				<form name="modifyloginform" action="viewprofile.php" method="post" onsubmit="return verifyModifyLoginForm(this);">
					<div><b><?php echo (isset($LANG['NEW_USERNAME'])?$LANG['NEW_USERNAME']:'New Username'); ?>:</b> <input name="newlogin" type="text" /></div>
					<?php
					if($isSelf){
						?>
						<div><b><?php echo (isset($LANG['CURRENT_PWORD'])?$LANG['CURRENT_PWORD']:'Current Password'); ?>:</b> <input name="newloginpwd" id="newloginpwd" type="password" /></div>
						<?php
					}
					?>
					<div style="margin:10px">
						<input type="hidden" name="userid" value="<?php echo $userId;?>" />
						<button type="submit" name="action" value="changeLogin"><?php echo (isset($LANG['CHANGE_USERNAME'])?$LANG['CHANGE_USERNAME']:'Change Username'); ?></button>
					</div>
				</form>
			</fieldset>
		</div>
		<div id="managetokensdiv" style="display:none;margin:15px;">
			<fieldset style='padding:15px;width:500px;'>
				<legend><b><?php echo (isset($LANG['MANAGE_TOKENS'])?$LANG['MANAGE_TOKENS']:'Manage Access Tokens'); ?></b></legend>
				<form name="cleartokenform" action="viewprofile.php" method="post" onsubmit="">
					<div>
					<?php
					$tokenCount = $pHandler->getTokenCnt();
					echo (isset($LANG['YOU_HAVE'])?$LANG['YOU_HAVE']:'You currently have').' <b>'.($tokenCount?$tokenCount:0).' </b>'.
					(isset($LANG['EXPLAIN_TOKENS'])?$LANG['EXPLAIN_TOKENS']:''); ?>
					</div>
					<div style="margin:10px">
						<input type="hidden" name="userid" value="<?php echo $userId;?>" />
						<button type="submit" name="action" value="Clear Tokens"><?php echo (isset($LANG['CLEAR_TOKENS'])?$LANG['CLEAR_TOKENS']:'Clear Tokens'); ?></button>
					</div>
				</form>
			</fieldset>
		</div>
		<div>
			<div>
				<b><u><?php echo (isset($LANG['TAXON_RELS'])?$LANG['TAXON_RELS']:'Taxonomic Relationships'); ?></u></b>
				<a href="#" onclick="toggle('addtaxonrelationdiv')" title="<?php echo (isset($LANG['ADD_TAXON_REL'])?$LANG['ADD_TAXON_REL']:'Add a New Taxonomic Relationship'); ?>">
					<img style='border:0px;width:15px;' src='../images/add.png'/>
				</a>
			</div>
			<div id="addtaxonrelationdiv" style="display:none;">
				<fieldset style="padding:20px;margin:15px;">
					<legend><b><?php echo (isset($LANG['NEW_TAX_REGION'])?$LANG['NEW_TAX_REGION']:'New Taxonomic Region of Interest'); ?></b></legend>
					<div style="margin-bottom:10px;">
						<?php echo (isset($LANG['TAX_FORM'])?$LANG['TAX_FORM']:''); ?>
					</div>
					<form name="addtaxonomyform" action="viewprofile.php" method="post" onsubmit="return verifyAddTaxonomyForm(this)">
						<div style="margin:3px;">
							<b><?php echo (isset($LANG['TAXON'])?$LANG['TAXON']:'Taxon'); ?></b><br/>
							<input id="taxoninput" name="taxon" type="text" value="" style="width:90%;" onfocus="initTaxonAutoComplete()" />
						</div>
						<div style="margin:3px;">
							<b><?php echo (isset($LANG['SCOPE_OF_REL'])?$LANG['SCOPE_OF_REL']:'Scope of Relationship'); ?></b><br/>
							<select name="editorstatus">
								<option value="RegionOfInterest"><?php echo (isset($LANG['REGION'])?$LANG['REGION']:'Region of Interest'); ?></option>
								<!-- <option value="OccurrenceEditor">Occurrence Editor</option> -->
							</select>
						</div>
						<div style="margin:3px;">
							<b><?php echo (isset($LANG['SCOPE_LIMITS'])?$LANG['SCOPE_LIMITS']:'Geographic Scope Limits'); ?></b><br/>
							<input name="geographicscope" type="text" value="" style="width:90%;"/>
						</div>
						<div style="margin:3px;">
							<b><?php echo (isset($LANG['NOTES'])?$LANG['NOTES']:'Notes'); ?></b><br/>
							<input name="notes" type="text" value="" style="width:90%;" />
						</div>
						<div style="margin:20px 10px;">
							<button name="action" type="submit" value="Add Taxonomic Relationship"><?php echo (isset($LANG['ADD_TAX'])?$LANG['ADD_TAX']:'Add Taxonomic Relationship'); ?></button>
						</div>
					</form>
				</fieldset>
			</div>
			<?php
			$userTaxonomy = $person->getUserTaxonomy();
			if($userTaxonomy){
				ksort($userTaxonomy);
				foreach($userTaxonomy as $cat => $userTaxArr){
					if($cat == 'RegionOfInterest') $cat = (isset($LANG['REGION'])?$LANG['REGION']:'Region Of Interest');
					elseif($cat == 'OccurrenceEditor') $cat = (isset($LANG['OCC_EDIT'])?$LANG['OCC_EDIT']:'Occurrence Editor');
					elseif($cat == 'TaxonomicThesaurusEditor') $cat = (isset($LANG['TAX_THES'])?$LANG['TAX_THES']:'Taxonomic Thesaurus Editor');
					echo '<div style="margin:10px;">';
					echo '<div><b>'.$cat.'</b></div>';
					echo '<ul style="margin:10px;">';
					foreach($userTaxArr as $utid => $utArr){
						echo '<li>';
						echo $utArr['sciname'];
						if($utArr['geographicScope']) echo ' - '.$utArr['geographicScope'].' ';
						if($utArr['notes']) echo ', '.$utArr['notes'];
						echo ' <a href="viewprofile.php?action=delusertaxonomy&utid='.$utid.'&userid='.$userId.'"><img src="../images/drop.png" style="width:14px;" /></a>';
						echo '</li>';
					}
					echo '</ul>';
					echo '</div>';
				}
			}
			else{
				echo '<div style="margin:20px;">'.(isset($LANG['NO_RELS'])?$LANG['NO_RELS']:'No relationships defined').'</div>';
			}
			?>
		</div>
	</div>
	<?php
}
?>