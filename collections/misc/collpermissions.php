<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/PermissionsManager.php');
include_once($SERVER_ROOT.'/content/lang/collections/misc/collpermissions.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

//Sanitization
$allowed_actions = array('Add Permissions for User','Add Identification Editor','Sponsor Personal Observation User','Sponsor Checklist User');
if (array_key_exists('action', $_REQUEST)){
	$action_key = array_search($_REQUEST["action"], $allowed_actions);
	if($action_key !== false){
		$action = $allowed_actions[$action_key];
	}
	else $action = false;
}
else $action = "";
$collId = array_key_exists("collid",$_REQUEST) ? filter_var($_REQUEST["collid"], FILTER_SANITIZE_NUMBER_INT) : 0;
$targetUID = array_key_exists('uid', $_POST) ? filter_var($_POST['uid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$persObsCollId = array_key_exists('persobscollid', $_POST) ? filter_var($_POST['persobscollid'], FILTER_SANITIZE_NUMBER_INT) : 0;

$permManager = new PermissionsManager();

$isEditor = 0;
if($SYMB_UID){
	if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))){
		$isEditor = 1;
	}
}

if($isEditor){
	if(array_key_exists('deladmin',$_GET)){
		$permManager->deletePermission($_GET['deladmin'],'CollAdmin',$collId);
	}
	elseif(array_key_exists('deleditor',$_GET)){
		$permManager->deletePermission($_GET['deleditor'],'CollEditor',$collId);
	}
	elseif(array_key_exists('delrare',$_GET)){
		$permManager->deletePermission($_GET['delrare'],'RareSppReader',$collId);
	}
	elseif(array_key_exists('delidenteditor',$_GET)){
		$permManager->deletePermission($_GET['delidenteditor'],'CollTaxon',$collId,$_GET['utid']);
		if(is_numeric($_GET['utid'])){
			$permManager->deletePermission($_GET['delidenteditor'],'CollTaxon',$collId,'all');
		}
	}
	elseif($action == 'Add Permissions for User'){
		$rightType = $_POST['righttype'];
		if($rightType == 'admin'){
			$permManager->addPermission($targetUID,"CollAdmin",$collId);
		}
		elseif($rightType == 'editor'){
			$permManager->addPermission($targetUID,"CollEditor",$collId);
		}
		elseif($rightType == 'rare'){
			$permManager->addPermission($targetUID,"RareSppReader",$collId);
		}
	}
// TODO: Identification Editor features need to be reviewed and refactored
//	elseif($action == 'Add Identification Editor'){
//		$identEditor = $_POST['identeditor'];
//		$pTokens = explode(':',$identEditor);
//		$permManager->addPermission($pTokens[0],'CollTaxon',$collId,$pTokens[1]);
//		//$permManager->addPermission($pTokens[0],'CollTaxon-'.$collId.':'.$pTokens[1]);
//	}
	elseif($action == 'Sponsor Personal Observation User'){
		$permManager->addPermission($targetUID,'CollEditor',$persObsCollId);
	}
	elseif($action == 'Sponsor Checklist User'){
		$permManager->addClCreateRole($targetUID);
	}
	elseif(array_key_exists('delpersobs',$_GET)){
		$permManager->deletePermission($_GET['delpersobs'],'CollEditor',$_GET['persobscollid']);
	}
}
$collMetadata = current($permManager->getCollectionMetadata($collId));
$isGenObs = 0;
if($collMetadata['colltype'] == 'General Observations') $isGenObs = 1;

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $collMetadata['collectionname'] . $LANG['COL_PERMISSIONS']; ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script>
		function verifyAddRights(f){
			if(f.uid.value == ""){
				alert("<?php echo (isset($LANG['PLS_SEL_USER'])?$LANG['PLS_SEL_USER']:'Please select a user from list'); ?>");
				return false;
			}
			else if(f.righttype && f.righttype.value == ""){
				alert("<?php echo (isset($LANG['SEL_PERMISSIONS'])?$LANG['SEL_PERMISSIONS']:'Please select the permissions you wish to assign this user'); ?>");
				return false;
			}
			else if(f.persobscollid && f.persobscollid.value == ""){
				alert("<?php echo (isset($LANG['SEL_OBS'])?$LANG['SEL_OBS']:'Please select a Personal Observation Management project'); ?>");
				return false;
			}
			return true;
		}
	</script>
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_collpermissionsMenu)?$collections_misc_collpermissionsMenu:true);
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($collections_misc_collpermissionsCrumbs)){
		if($collections_misc_collpermissionsCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>" . htmlspecialchars((isset($LANG['HOME'])?$LANG['HOME']:'Home'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a> &gt;&gt; ";
			echo $collections_misc_collpermissionsCrumbs;
			echo " <b>".($collMetadata['collectionname']?$collMetadata['collectionname']:(isset($LANG['COL_PROFS'])?$LANG['COL_PROFS']:'Collection Profiles'))."</b>";
			echo "</div>";
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../../index.php'><?php echo htmlspecialchars((isset($LANG['HOME'])?$LANG['HOME']:'Home'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
			<a href='collprofiles.php?emode=1&collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>'><?php echo htmlspecialchars((isset($LANG['COL_MANAGE'])?$LANG['COL_MANAGE']:'Collection Management'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
			<b><?php echo $collMetadata['collectionname'].' '.(isset($LANG['PERMISSIONS'])?$LANG['PERMISSIONS']:'Permissions'); ?></b>
		</div>
		<?php
	}
	?>

	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?php echo $LANG['COL_PERMISSIONS'] . ' for: ' . $collMetadata['collectionname']; ?></h1>
		<?php
		if($isEditor){
			$collPerms = $permManager->getCollectionEditors($collId);
			if(!$isGenObs){
				?>
				<section class="fieldset-like">
				<h2><span><b><?php echo (isset($LANG['ADMINS'])?$LANG['ADMINS']:'Administrators'); ?></b></span></h2>
					<?php
					if(array_key_exists('admin',$collPerms)){
						?>
						<ul>
						<?php
						$adminArr = $collPerms['admin'];
						foreach($adminArr as $uid => $uName){
							?>
							<li>
								<?php echo $uName; ?>
								<a href="collpermissions.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&deladmin=' . htmlspecialchars($uid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" onclick="return confirm('<?php echo htmlspecialchars(isset($LANG['YES_REM_ADMIN'])?$LANG['YES_REM_ADMIN']:'Are you sure you want to remove administrative rights for this user?', ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>');" title="<?php echo htmlspecialchars((isset($LANG['DEL_PERMISSIONS'])?$LANG['DEL_PERMISSIONS']:'Delete permissions for this user'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" aria-label="<?php echo $LANG['REMOVE_PERMISSIONS'] ?>">
									<img src="../../images/drop.png" style="width:1em;" alt="<?php echo $LANG['DROP_ICON']; ?>" />
								</a>
							</li>
							<?php
						}
						?>
						</ul>
						<?php
					}
					else{
						echo '<div style="font-weight:bold;">';
						echo (isset($LANG['NO_PERMS'])?$LANG['NO_PERMS']:'There are no administrative permissions (excluding Super Admins)');
						echo '</div>';
					}
					?>
				</section>
				<?php
			}
			?>
			<section class="fieldset-like">
				<h2><span><b><?php echo (isset($LANG['EDITORS'])?$LANG['EDITORS']:'Editors'); ?></b></span></h2> 
				<?php
				if(array_key_exists('editor',$collPerms)){
					?>
					<ul>
					<?php
					$editorArr = $collPerms['editor'];
					foreach($editorArr as $uid => $uName){
						?>
						<li>
							<?php echo $uName; ?>
							<a href="collpermissions.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&deleditor=' . htmlspecialchars($uid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" onclick="return confirm('<?php echo htmlspecialchars((isset($LANG['YES_REM_EDIT'])?$LANG['YES_REM_EDIT']:'Are you sure you want to remove editor rights for this user?'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>');" title="<?php echo htmlspecialchars((isset($LANG['DEL_PERMISSIONS'])?$LANG['DEL_PERMISSIONS']:'Delete permissions for this user'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" aria-label="<?php echo $LANG['REMOVE_PERMISSIONS'] ?>">
								<img src="../../images/drop.png" style="width:1em;" alt="<?php echo $LANG['DROP_ICON']; ?>" />
							</a>
						</li>
						<?php
					}
					?>
					</ul>
					<?php
				}
				else{
					echo '<div style="font-weight:bold;">';
					echo $LANG['NO_GENERAL_PERMS'];
					echo '</div>';
				}
				?>
				<div style="margin:10px">
					*<?php echo (isset($LANG['ADMINS_INHERIT'])?$LANG['ADMINS_INHERIT']:'Administrators automatically inherit editing rights'); ?>
				</div>
			</section>
			<?php
			if(!$isGenObs){
				?>
				<section class="fieldset-like">
					<h2><span><b><?php echo (isset($LANG['RARE_SP_READERS'])?$LANG['RARE_SP_READERS']:'Rare Species Readers'); ?></b></span></h2>
					<?php
					if(array_key_exists('rarespp',$collPerms)){
						?>
						<ul>
						<?php
						$rareArr = $collPerms['rarespp'];
						foreach($rareArr as $uid => $uName){
							?>
							<li>
								<?php echo $uName; ?>
								<a href="collpermissions.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&delrare=' . htmlspecialchars($uid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" onclick="return confirm('<?php echo htmlspecialchars((isset($LANG['YES_REM_RARE'])?$LANG['YES_REM_RARE']:'Are you sure you want to remove user rights to view locality details for rare species?'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>');" title="<?php echo htmlspecialchars((isset($LANG['DEL_PERMISSIONS'])?$LANG['DEL_PERMISSIONS']:'Delete permissions for this user'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" aria-label="<?php echo $LANG['REMOVE_PERMISSIONS'] ?>">
									<img src="../../images/drop.png" style="width:1em;" alt="<?php echo $LANG['DROP_ICON']; ?>" />
								</a>
							</li>
							<?php
						}
						?>
						</ul>
						<?php
					}
					else{
						echo '<div style="font-weight:bold;">';
						echo (isset($LANG['NO_RARE_READERS'])?$LANG['NO_RARE_READERS']:'There are no Sensitive Species Reader permissions');
						echo '</div>';
					}
					?>
					<div style="margin:10px">
						*<?php echo (isset($LANG['ADMINS_EDITS_INHERIT'])?$LANG['ADMINS_EDITS_INHERIT']:'Administrators and editors automatically inherit protected species viewing rights'); ?>
					</div>
				</section>
				<?php
			}
			$userArr = $permManager->getUsers();
			?>
			<section class="fieldset-like">
				<h2><span><b><?php echo (isset($LANG['ADD_NEW_USER'])?$LANG['ADD_NEW_USER']:'Add a New Admin/Editor/Reader'); ?></b></span></h2>
				<form name="addrights" action="collpermissions.php" method="post" onsubmit="return verifyAddRights(this)">
					<div>
					    <label for="uid"><?php echo $LANG['SEL_USER'] ?></label>
						<select id="uid" name="uid">
							<option value=""><?php echo (isset($LANG['SEL_USER'])?$LANG['SEL_USER']:'Select User'); ?></option>
							<option value="">-----------------------------------</option>
							<?php
							foreach($userArr as $uid => $uName){
								echo '<option value="'.$uid.'">'.$uName.'</option>';
							}
							?>
						</select>
					</div>
					<div style="margin:5px 0px 5px 0px;">
						<?php
						if($isGenObs){
							?>
							<input name="righttype" type="hidden" value="editor" aria-label="<?php echo $LANG['EDITOR'] ?> />
							<?php
						}
						else{
							?>
							<input name="righttype" type="radio" value="admin" aria-label="<?php echo $LANG['ADMIN'] ?>" /> <?php echo (isset($LANG['ADMIN'])?$LANG['ADMIN']:'Administrator'); ?> <br/>
							<input name="righttype" type="radio" value="editor" aria-label="<?php echo $LANG['EDITOR'] ?>" /> <?php echo (isset($LANG['EDITOR'])?$LANG['EDITOR']:'Editor'); ?> <br/>
							<input name="righttype" type="radio" value="rare" aria-label="<?php echo $LANG['RARE_SP_READ'] ?>" /> <?php echo (isset($LANG['RARE_SP_READ'])?$LANG['RARE_SP_READ']:'Rare Species Reader'); ?><br/>
							<?php
						}
						?>
					</div>
					<div style="margin:15px;">
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<button name="action" type="submit" value="Add Permissions for User"><?php echo (isset($LANG['ADD_PERMS'])?$LANG['ADD_PERMS']:'Add Permissions for User'); ?></button>
					</div>
				</form>
			</section>
			<?php
			//Personal specimen management sponsorship
			$genObsArr = $permManager->getGeneralObservationCollidArr();
			if(!$isGenObs && $genObsArr){
				?>
				<section class="fieldset-like">
					<h2><span><b><?php echo (isset($LANG['PERS_OBS_SPONSOR'])?$LANG['PERS_OBS_SPONSOR']:'Personal Observation Management Sponsorship'); ?></b></span></h2>					<div style="margin:10px">
					<?php echo (isset($LANG['SPONSOR_EXPLAIN'])?$LANG['SPONSOR_EXPLAIN']:'
						Collection administrators listed above can sponsor users for Personal Observation Management.
						This allows users to enter field data as observations that are linked directly to their user profile, print labels,
						and later collection data can be transferred once specimens are donated to this collection.
						Listed below are all users that have been given such rights by one of the collection administrators listed above.');
						?>
					</div>
					<ul>
						<?php
						$persManagementArr = $permManager->getPersonalObservationManagementArr($collId, $genObsArr);
						if($persManagementArr){
							foreach($persManagementArr as $uid => $pmArr){
								echo '<li>';
								$titleStr = 'Assigned by '.$pmArr['uab'].' on '.$pmArr['ts'];
								if(count($genObsArr) > 1) $titleStr .= ' access to '.$genObsArr[$pmArr['persobscollid']];
								echo '<span title="'.$titleStr.'">'.$pmArr['name'].'</span> ';
								if($SYMB_UID == $pmArr['uidab']){
									echo '<a href="collpermissions.php?collid=' . htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&delpersobs=' . htmlspecialchars($uid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&persobscollid=' . htmlspecialchars($pmArr['persobscollid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" onclick="return confirm(\'' . htmlspecialchars((isset($LANG['SURE_DELETE'])?$LANG['SURE_DELETE']:'Are you sure you want to delete these permissions?'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '\');" title="' . htmlspecialchars((isset($LANG['DEL_PERMISSIONS'])?$LANG['DEL_PERMISSIONS']:'Delete permissions for this user'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" aria-label="' . $LANG['REMOVE_PERMISSIONS'] . '">';
									echo '<img src="../../images/drop.png" style="width:1em;" alt="' . $LANG['DROP_ICON'] . '" />';
									echo '</a>';
								}
								echo '</li>';
							}
						}
						else{
							echo '<li>'.(isset($LANG['NONE_SPONSORED'])?$LANG['NONE_SPONSORED']:'No users have yet been sponsored').'</li>';
						}
						?>
					</ul>
					<?php
					if((array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))){
						?>
							<fieldset style="margin:40px 15px 0px 15px;padding:15px;">
								<legend><b><?php echo (isset($LANG['NEW_SPONSOR'])?$LANG['NEW_SPONSOR']:'New Sponsorship'); ?></b></legend>
								<form name="addpersobsman" action="collpermissions.php" method="POST" onsubmit="return verifyAddRights(this)">
									<div>
										<label for="uid"><?php echo $LANG['SEL_USER'] ?></label>
										<select id="uid" name="uid">
											<option value=""><?php echo (isset($LANG['SEL_USER'])?$LANG['SEL_USER']:'Select User'); ?></option>
											<option value="">-----------------------------------</option>
											<?php
											foreach($userArr as $uid => $uName){
												echo '<option value="'.$uid.'">'.$uName.'</option>';
											}
											?>
										</select>
									</div>
									<div>
										<?php
										if(count($genObsArr) == 1){
											echo '<input name="persobscollid" type="hidden" value="'.key($genObsArr).'" aria-label="' . $LANG['SEL_PERS_OBS'] . '" />';
										}
										else{
											echo '<label for="persobscollid">' . $LANG['SEL_PERS_OBS'] . '</label>';
											echo '<select id="persobscollid" name="persobscollid">';
											echo '<option value="">'.(isset($LANG['SEL_PERS_OBS'])?$LANG['SEL_PERS_OBS']:'Select Personal Observation Project').'</option>';
											echo '<option value="">-----------------------------------</option>';
											foreach($genObsArr as $persObsCollid => $perObsName){
												echo '<option value="'.$persObsCollid.'">'.$perObsName.'</option>';
											}
											echo '</select>';
										}
										?>
									</div>
									<div style="margin:15px;">
										<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
										<button name="action" type="submit" value="Sponsor Personal Observation User"><?php echo (isset($LANG['SPONSOR_USER'])?$LANG['SPONSOR_USER']:'Sponsor User'); ?></button>
									</div>
								</form>
							</fieldset>
						<?php
					}
					?>
				</section>
				<?php
			}
			//Checklist / Dataset sponsorship
				?>
				<section class="fieldset-like">
					<h2><span><b><?php echo (isset($LANG['CHECKLIST_SPONSOR'])?$LANG['CHECKLIST_SPONSOR']:'Checklist / Dataset Management Sponsorship'); ?></b></span></h2>
					<div style="margin:10px">
					<?php echo (isset($LANG['CHECKLIST_SPONSOR_EXPLAIN'])?$LANG['CHECKLIST_SPONSOR_EXPLAIN']:'
						Collection administrators listed above can sponsor users for Checklist and Dataset Management.
						This allows users to create new public and private checklists or datasets that are linked directly to their user profile.  Public Checklists and Datasets are visible to all portal visitors.');
						?>
					</div>
					<?php
					if((array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))){
						?>
							<fieldset style="margin:40px 15px 0px 15px;padding:15px;">
								<legend><b><?php echo (isset($LANG['NEW_SPONSOR'])?$LANG['NEW_SPONSOR']:'New Sponsorship'); ?></b></legend>
								<form name="addchecklistman" action="collpermissions.php" method="post" onsubmit="return verifyAddRights(this)">
									<div>
										<label for="uid"><?php echo $LANG['SEL_USER'] ?></label>
										<select id="uid" name="uid">
											<option value=""><?php echo (isset($LANG['SEL_USER'])?$LANG['SEL_USER']:'Select User'); ?></option>
											<option value="">-----------------------------------</option>
											<?php
											foreach($userArr as $uid => $uName){
												echo '<option value="'.$uid.'">'.$uName.'</option>';
											}
											?>
										</select>
									</div>
									<div style="margin:15px;">
										<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
										<button name="action" type="submit" value="Sponsor Checklist User"><?php echo (isset($LANG['SPONSOR_USER'])?$LANG['SPONSOR_USER']:'Sponsor User'); ?></button>
									</div>
								</form>
							</fieldset>
						<?php
					}
					?>
				</section>
				<?php
// TODO: Identification Editor features need to be reviewed and refactored
/*
			//Identification Editors
			$taxonEditorArr = $permManager->getTaxonEditorArr($collId,1);
			$taxonSelectArr = $permManager->getTaxonEditorArr($collId,0);
			if($taxonEditorArr || $taxonSelectArr){
				?>
				<form>
				<fieldset style="margin:15px;padding:15px;">
					<legend><b><?php echo (isset($LANG['ID_EDITS'])?$LANG['ID_EDITS']:'Identification Editors'); ?></b></legend>
					<div style="float:right;" title="Add a new user">
						<a href="#" onclick="toggle('addUserDiv');return false;">
							<img style='border:0px;width:1.5em;' src='../../images/add.png'/>
						</a>
					</div>
					<div id="addUserDiv" style="display:none;">
					<form>
						<fieldset style="margin:15px;padding:15px;">
							<legend><b><?php echo (isset($LANG['ADD_ID_EDIT'])?$LANG['ADD_ID_EDIT']:'Add Identification Editor'); ?></b></legend>
							<div style="margin:0px 20px 10px 10px;">
								<?php echo (isset($LANG['LIST_ID_EDITS'])?$LANG['LIST_ID_EDITS']:'The user list below contains only Identification Editors
								that been approved by a portal manager. Contact your portal manager to request the addition of a new user.'); ?>
							</div>
							<div style="margin:10px;">
								<form name="addidenteditor" action="collpermissions.php" method="post" onsubmit="return verifyAddIdentEditor(this)">
									<div>
										<b><?php echo (isset($LANG['USER'])?$LANG['USER']:'User'); ?></b><br/>
										<select name="identeditor">
											<option value=""><?php echo (isset($LANG['SEL_USER'])?$LANG['SEL_USER']:'Select User'); ?></option>
											<option value="">--------------------------</option>
											<?php
											foreach($taxonSelectArr as $uid => $uArr){
												$username = $uArr['username'];
												unset($uArr['username']);
												if(!isset($taxonEditorArr[$uid]['all'])) echo '<option value="'.$uid.':all">'.$username.' - '.(isset($LANG['ALL_APPROVED'])?$LANG['ALL_APPROVED']:'All Approved Taxonomy').'</option>';
												unset($uArr['all']);
												foreach($uArr as $utid => $sciname){
													if(!isset($taxonEditorArr[$uid]['utid'][$utid])) echo '<option value="'.$uid.':'.$utid.'">'.$username.' - '.$sciname.'</option>';
												}
											}
											?>
										</select>
									</div>
									<div style="margin:15px 0px">
										<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
										<button name="action" type="submit" value="Add Identification Editor"><?php echo (isset($LANG['ADD_ID_EDIT'])?$LANG['ADD_ID_EDIT']:'Add Identification Editor'); ?></button>
									</div>
								</form>
							</div>
						</fieldset>
					</form>
					</div>
					<div style="margin:10px;">
					<?php echo (isset($LANG['ID_EDIT_EXPLAIN'])?$LANG['ID_EDIT_EXPLAIN']:'
						Following users have permission to edit occurrence records that are
						insignificantly identified to a taxon that is within the scope of their taxonomic interest
						and has an identification confidence ranking value of less than 6.
						Identification Editors can also edit occurrence records that are only identified to
						order or above or lack an identification altogether.');
						?>
					</div>
					<?php
					if($taxonEditorArr){
						?>
						<ul>
						<?php
						foreach($taxonEditorArr as $uid => $uArr){
							$username = $uArr['username'];
							unset($uArr['username']);
							$hasAll = false;
							if(array_key_exists('all',$uArr)){
								$hasAll = true;
								unset($uArr['all']);
								?>
								<li>
									<?php echo $username.' ('.(isset($LANG['ALL_RANGES'])?$LANG['ALL_RANGES']:'All approved taxonomic ranges listed below').')'; ?>
									<a href="collpermissions.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&delidenteditor=' . htmlspecialchars($uid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&utid=all'; ?>" onclick="return confirm('<?php echo htmlspecialchars((isset($LANG['SURE_REM_ID'])?$LANG['SURE_REM_ID']:'Are you sure you want to remove identification editing rights for this user?'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>');" title="<?php echo htmlspecialchars((isset($LANG['DEL_PERMISSIONS'])?$LANG['DEL_PERMISSIONS']:'Delete permissions for this user'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" aria-label="<?php echo $LANG['REMOVE_PERMISSIONS'] ?>">
										<img src="../../images/drop.png" style="width:1em;" alt="<?php echo $LANG['DROP_ICON']; ?>" />
									</a>
								</li>
								<?php
							}
							foreach($uArr as $utid => $sciname){
								?>
								<li>
									<?php
									echo $username.' ('.$sciname.')';
									if(!$hasAll){
										?>
										<a href="collpermissions.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&delidenteditor=' . htmlspecialchars($uid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&utid=' . htmlspecialchars($utid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" onclick="return confirm('<?php echo htmlspecialchars((isset($LANG['SURE_REM_ID'])?$LANG['SURE_REM_ID']:'Are you sure you want to remove identification editing rights for this user?'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>');" title="<?php echo htmlspecialchars((isset($LANG['DEL_PERMISSIONS'])?$LANG['DEL_PERMISSIONS']:'Delete permissions for this user'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" aria-label="<?php echo $LANG['REMOVE_PERMISSIONS'] ?>">
											<img src="../../images/drop.png" style="width:1em;" alt="<?php echo $LANG['DROP_ICON']; ?>" />
										</a>
										<?php
									}
									?>
								</li>
								<?php
							}
						}
						?>
						</ul>
						<?php
					}
					else{
						echo '<div style="font-weight:bold;margin:20px">';
						echo (isset($LANG['NO_ID_PERMS'])?$LANG['NO_ID_PERMS']:'There are no Identification Editor permissions');
						echo '</div>';
					}
					?>
				</fieldset>
				</form>
				<?php
			}
*/
		}
		else{
			echo '<div style="font-weight:bold;font-size:120%;">';
			echo (isset($LANG['NOT_AUTH'])?$LANG['NOT_AUTH']:'Unauthorized to view this page. You must have administrative right for this collection.');
			echo '</div>';
		}
		?>
	</div>
	<?php
		include($SERVER_ROOT.'/includes/footer.php');
	?>

</body>
</html>
