<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/checklists/checklistadmin.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/checklists/checklistadmin.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/checklists/checklistadmin.en.php');
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
if(!is_numeric($clid)) $clid = 0;
$clManager = new ChecklistVoucherAdmin();
$clManager->setClid($clid);
$voucherProjects = $clManager->getVoucherProjects();
?>
<div id="imgvouchertab">
	<form name="addimagevoucher" action="../collections/editor/observationsubmit.php" method="post">
		<fieldset style="margin:15px;padding:25px;">
			<legend><b><?php echo $LANG['ADDIMGVOUC'];?></b></legend>
			<?php echo $LANG['FORMADDVOUCH'];?><br><br>
			<?php echo $LANG['SELECTVOUCPROJ'];?>
			<div style="margin:5px;">
				<select name="collid">
					<?php
					$target = 0;
					if(isset($voucherProjects['target'])){
						$target = $voucherProjects['target'];
						unset($voucherProjects['target']);
					}
					foreach($voucherProjects as $k => $v){
						echo '<option value="' . $k . '" ' . ($target==$k?'SELECTED':'') . '>' . $v . '</option>';
					}
					?>
				</select><br/>
				<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
			</div>
			<div style="margin:5px;">
				<button type="submit" name="submitvoucher">
					<?php echo $LANG['ADDIMGVOUC'];?>
				</button>
			</div>
		</fieldset>
	</form>
</div>
