<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistAdmin.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/checklists/checklistadminchildren.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/checklists/checklistadminchildren.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/checklists/checklistadminchildren.en.php');

$clid = array_key_exists('clid',$_REQUEST)?$_REQUEST['clid']:0;
$pid = array_key_exists('pid',$_REQUEST)?$_REQUEST['pid']:'';
$targetClid = array_key_exists('targetclid',$_REQUEST)?$_REQUEST['targetclid']:'';
$transferMethod = array_key_exists('transmethod',$_REQUEST)?$_REQUEST['transmethod']:0;
$parentClid = array_key_exists('parentclid',$_REQUEST)?$_REQUEST['parentclid']:'';
$targetPid = array_key_exists('targetpid',$_REQUEST)?$_REQUEST['targetpid']:'';
$copyAttributes = array_key_exists('copyattributes',$_REQUEST)?$_REQUEST['copyattributes']:'';

//Sanitation
if(!is_numeric($clid)) $clid = 0;
if(!is_numeric($pid)) $pid = 0;
if(!is_numeric($targetClid)) $targetClid = 0;
if(!is_numeric($transferMethod)) $transferMethod = 0;
if(!is_numeric($parentClid)) $parentClid = '';
if(!is_numeric($targetPid)) $targetPid = 0;
if(!is_numeric($copyAttributes)) $copyAttributes = 0;

$clManager = new ChecklistAdmin();
$clManager->setClid($clid);

$clArr = $clManager->getUserChecklistArr();
$childArr = $clManager->getChildrenChecklist()
?>
<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>

<script>
	$("#taxon").autocomplete({
		source: function( request, response ) {
			$.getJSON( "<?php echo $CLIENT_ROOT; ?>/rpc/taxasuggest.php", { term: request.term }, response );
		},
		minLength: 3,
		autoFocus: true,
		select: function( event, ui ) {
			if(ui.item){
				$("#parsetid").val(ui.item.id);
			}
		}
	});

	function validateParseChecklistForm(){

	}

	function validateAddChildForm(f){

	}
</script>
<style>
	.section-div{ margin-bottom: 3px; }
	#taxa{ width:400px }
	#parsetid{ width:100px }
	button{ margin:20px; }
</style>
<!-- inner text -->
<div role="main" id="innertext" style="background-color:white;">
	<div style="float:right;">
		<a href="#" onclick="toggle('addchilddiv')"><img src="../images/add.png" style="width:1.5em;" /></a>
	</div>
	<div style="margin:15px;font-weight:bold;font-size:120%;">
		<u><?php echo $LANG['CHILD_CHECKLIST']; ?></u>
	</div>
	<div style="margin:25px;clear:both;">
		<?php echo $LANG['CHILD_DESCRIBE']; ?>
	</div>
	<div id="addchilddiv" style="margin:15px;display:none;">
		<fieldset style="padding:15px;">
			<legend><b><?php echo $LANG['LINK_NEW']; ?></b></legend>
			<form name="addchildform" target="checklistadmin.php" method="post" onsubmit="validateAddChildForm(this)">
				<div style="margin:10px;">
					<select name="clidadd">
						<option value=""><?php echo $LANG['SELECT_CHILD']; ?></option>
						<option value="">-------------------------------</option>
						<?php
						foreach($clArr as $k => $name){
							if(!isset($childArr[$k])) echo '<option value="'.$k.'">'.$name.'</option>';
						}
						?>
					</select>
				</div>
				<div style="margin:10px;">
					<button name="submitaction" type="submit" value="addChildChecklist"><?php echo $LANG['ADD_CHILD']; ?></button>
					<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
					<input name="pid" type="hidden" value="<?php echo $pid; ?>" />
					<input name="tabindex" type="hidden" value="2" />
				</div>
			</form>
		</fieldset>
	</div>
	<div style="margin:15px;">
		<ul>
			<?php
			if($childArr){
				foreach($childArr as $k => $cArr){
					?>
					<li>
						<a href="checklist.php?clid=<?php echo htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank"><?php echo htmlspecialchars($cArr['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
						<?php
						if($cArr['pclid'] == $clid){
							$confirmStr = $LANG['SURE'] . $cArr['name'] . $LANG['AS_CHILD'];
							echo '<a href="checklistadmin.php?submitaction=delchild&tabindex=2&cliddel=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" onclick="return confirm(\'' . htmlspecialchars($confirmStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '\')">';
							echo '<img src="../images/del.png" style="width:1em;" /></a>';
							echo '</a>';
						}
						?>
					</li>
					<?php
				}
			}
			else{
				echo '<div style="font-size:110%;">' . $LANG['NO_CHILDREN'] . '</div>';
			}
			?>
		</ul>
	</div>
	<div style="margin:30px 15px;font-weight:bold;font-size:120%;">
		<u><?php echo $LANG['PARENTS']; ?></u>
	</div>
		<ul>
			<?php
			if($parentArr = $clManager->getParentChecklists()){
				foreach($parentArr as $k => $name){
					?>
					<li>
						<a href="checklist.php?clid=<?php echo htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank"><?php echo htmlspecialchars($name, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
					</li>
					<?php
				}
			}
			else{
				echo '<div style="font-size:110%;">' . $LANG['NO_PARENTS'] . '</div>';
			}
			?>
		</ul>
	</div>
	<hr>
	<div style="margin:20px 0px;">
		<fieldset>
			<legend><?php echo $LANG['BATCH_PARSE_SP_LIST']; ?></legend>
			<div style="margin:10px 0px;"><?php echo $LANG['BATCH_PARSE_DESCRIBE']; ?></div>
			<form name="parsechecklistform" target="checklistadmin.php" method="post" onsubmit="validateParseChecklistForm(this)">
				<div class="section-div">
					<label for="taxon"><?php echo $LANG['TAXONOMICNODE'] ?>:</label>
					<input id="taxon" name="taxon" type="text" required />
					<label for="parsetid"><?php echo $LANG['PARSETID'] ?>:</label>
					<input id="parsetid" name="parsetid" type="text" required >
				</div>
				<div class="section-div">
					<label for="targetclid"><?php echo $LANG['TARGETCHECKLIST'] ?>:</label>
					<select name="targetclid" id="targetclid" required>
						<option value=""><?php echo $LANG['SELECTTARGETCHECKLIST'] ?></option>
						<option value="0"><?php echo $LANG['CREATENEWCHECKLIST'] ?></option>
						<option value="">--------------------------</option>
						<?php
						foreach($clArr as $k => $name){
							if(!isset($childArr[$k])) echo '<option value="'.$k.'" '.($targetClid == $k?'SELECTED':'').'>'.$name.'</option>';
						}
						?>
					</select>
				</div>
				<div class="section-div">
					<label><?php echo $LANG['TRANSFER_METHOD'] ?>:</label>
					<input name="transmethod" id="transtaxa" type="radio" value="0" <?php if(!$transferMethod) echo 'checked'; ?>> 
					<label for="transtaxa"><?php echo $LANG['TRANSFERTAXA'] ?></label>
					<input name="transmethod" id="copytaxa" type="radio" value="1" <?php if($transferMethod == 1) echo 'checked'; ?>> 
					<label for="copytaxa"><?php echo $LANG['COPYTAXA'] ?></label>
				</div>
				<div class="section-div">
					<label><?php echo $LANG['LINK_PARENT_CHECKLIST'] ?>:</label>
					<select name="parentclid">
						<option value=""><?php echo $LANG['NOPARENTCHECKLIST'] ?></option>
						<option value="0" <?php if($parentClid === 0) echo 'SELECTED'; ?>><?php echo $LANG['CREATENEWCHECKLIST'] ?></option>
						<option value="">--------------------------</option>
						<?php
						foreach($clArr as $k => $name){
							if(!isset($childArr[$k])) echo '<option value="' . $k . '" ' . ($parentClid == $k?'SELECTED':'') . '>' . $name . '</option>';
						}
						?>
					</select>
				</div>
				<div class="section-div">
					<label><?php echo $LANG['ADD_TO_PROJECT'] ?>:</label>
					<select name="targetpid">
						<option value="">--<?php echo $LANG['NO_ACTION'] ?>--</option>
						<option value="0"><?php echo $LANG['NEWPROJECT'] ?></option>
						<option value="">--------------------------</option>
						<?php
						$projArr = $clManager->getUserProjectArr();
						foreach($projArr as $k => $name){
							echo '<option value="'.$k.'" '.($targetPid == $k?'SELECTED':'').'>'.$name.'</option>';
						}
						?>
					</select>
				</div>
				<div class="section-div">
					<input name="copyattributes" id="copyattributes" type="checkbox" value="1" <?php if($copyAttributes) echo 'checked'; ?>>
					<label for="copyattributes"><?php echo $LANG['COPYPERMISSIONANDGENERAL'] ?></label>
				</div>
				<div class="section-div">
					<input name="tabindex" type="hidden" value="2" >
					<button name="submitaction" type="submit" value="parseChecklist"><?php echo $LANG['PARSE_CHECKLIST'] ?></button>
				</div>
			</form>
			<div><a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/taxa/taxonomy/taxonomydisplay.php" target="_blank"><?php echo $LANG['OPEN_TAX_THES_EXPLORE'] ?></a></div>
		</fieldset>
	</div>
</div>