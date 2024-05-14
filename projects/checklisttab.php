<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImInventories.php');
include_once($SERVER_ROOT.'/content/lang/projects/index.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

$pid = $_REQUEST['pid'];

//Sanitation
if(!is_numeric($pid)) $pid = 0;

$projManager = new ImInventories();
$projManager->setPid($pid);
$clAddArr = $projManager->getChecklistArr();
$clRemoveArr = $projManager->getChecklistArr($pid);
foreach($clRemoveArr as $id => $removeArr){
	if($removeArr['access'] == 'private-strict' && (!isset($USER_RIGHTS['ClAdmin']) || !in_array($id, $USER_RIGHTS['ClAdmin']))) unset($clRemoveArr[$id]);
}
?>
<div id="cltab">
	<div style="margin:10px;">
		<form name="claddform" action="index.php" method="post" onsubmit="return validateChecklistForm(this)">
			<fieldset class="form-color">
				<legend><b><?= $LANG['ADD_A_CHECKLIST']?></b></legend>
				<select name="clid" style="width:450px;">
					<option value=""><?= $LANG['SELECT_CHECKLIST_TO_ADD'] ?></option>
					<option value="">-----------------------------------------</option>
					<?php
					foreach($clAddArr as $clid => $clArr){
						if((isset($USER_RIGHTS['ClAdmin']) && in_array($clid, $USER_RIGHTS['ClAdmin'])) || $clArr['access'] == 'public'){
							if(!array_key_exists($clid, $clRemoveArr)){
								echo '<option value="'.$clid.'">'.$clArr['name'].(strpos($clArr['access'], 'private') !== false?' (private)':'').'</option>';
							}
						}
					}
					?>
				</select><br/>
				<input type="hidden" name="pid" value="<?php echo $pid;?>">
				<button style="margin-top:0.5rem" type="submit" name="projsubmit" value="Add Checklist"><?= $LANG['ADD_CHECKLIST']?></button>
			</fieldset>
		</form>
	</div>
	<div style="margin:10px;">
		<form name="cldeleteform" action="index.php" method="post" onsubmit="return validateChecklistForm(this)">
			<fieldset class="form-color">
				<legend><b><?= $LANG['DELETE_A_CHECKLIST']?></b></legend>
				<select name="clid" style="width:450px;">
					<option value=""><?= $LANG['SELECT_CHECKLIST_TO_DELETE']?></option>
					<option value="">-----------------------------------------</option>
					<?php
					foreach($clRemoveArr as $clid => $clArr){
						echo '<option value="'.$clid.'">'.$clArr['name'].'</option>';
					}
					?>
				</select><br/>
				<input type="hidden" name="pid" value="<?php echo $pid;?>">
				<button class="button-danger" style="margin-top:0.5rem" type="submit" name="projsubmit" value="Delete Checklist"><?= $LANG['DELETE_CHECKLIST']?></button>
			</fieldset>
		</form>
	</div>
</div>
