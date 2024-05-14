<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/includes/admintab.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/admintab.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/admintab.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$occid = $_GET['occid'];
$occIndex = $_GET['occindex'];
$collId = $_GET['collid'];

$occManager = new OccurrenceEditorManager();
$occManager->setOccId($occid);
?>
<div id="admindiv">
	<?php
	$editArr = $occManager->getEditArr();
	$externalEdits = $occManager->getExternalEditArr();
	if($editArr || $externalEdits){
		if($editArr){
			?>
			<fieldset style="padding:15px;margin:10px 0px;">
				<legend><b><?php echo $LANG['EDIT_HISTORY_INT']; ?></b></legend>
				<?php
				if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollAdmin']))){
					?>
					<div style="float:right;" title="<?php echo $LANG['MANAGE_HISTORY']; ?>">
						<a href="../editor/editreviewer.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&occid=' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank"><img src="../../images/edit.png" style="border:0px;width:14px;" /></a>
					</div>
					<?php
				}
				foreach($editArr as $ts => $tsArr){
					?>
					<div>
						<b><?php echo $LANG['EDITOR']; ?>:</b> <?php echo $tsArr['editor']; ?>
						<span style="margin-left:30px;"><b><?php echo $LANG['DATE']; ?>:</b> <?php echo $ts; ?></span>
					</div>
					<?php
					foreach($tsArr['edits'] as $appliedStatus => $appliedArr){
						?>
						<div>
							<span><b><?php echo $LANG['APPLIED_STATUS']; ?>:</b> <?php echo ($appliedStatus?$LANG['APPLIED']:$LANG['NOT_APPLIED']); ?></span>
						</div>
						<?php
						foreach($appliedArr as $vArr){
							echo '<div style="margin:10px 15px;">';
							echo '<b>'.$LANG['FIELD'].':</b> '.$vArr['fieldname'].'<br/>';
							echo '<b>'.$LANG['OLD_VALUE'].($vArr['current'] == 2?' (current)':'').':</b> '.$vArr['old'].'<br/>';
							echo '<b>'.$LANG['NEW_VALUE'].($vArr['current'] == 1?' (current)':'').':</b> '.$vArr['new'].'<br/>';
							echo '</div>';
						}
					}
					echo '<div style="margin:5px 0px;">&nbsp;</div>';
					echo '<div style=""><hr></div>';
				}
				?>
			</fieldset>
			<?php
		}
		if($externalEdits){
			?>
			<fieldset style="margin-top:20px;padding:20px;">
				<legend><b><?php echo $LANG['EDIT_HISTORY_EXT']; ?></b></legend>
				<?php
				foreach($externalEdits as $orid => $eArr){
					foreach($eArr as $appliedStatus => $eArr2){
						$reviewStr = 'OPEN';
						if($eArr2['reviewstatus'] == 2) $reviewStr = 'PENDING';
						elseif($eArr2['reviewstatus'] == 3) $reviewStr = 'CLOSED';
						?>
						<div>
							<b><?php echo $LANG['EDITOR']; ?>:</b> <?php echo $eArr2['editor']; ?>
							<span style="margin-left:30px;"><b><?php echo $LANG['DATE']; ?>:</b> <?php echo $eArr2['ts']; ?></span>
							<span style="margin-left:30px;"><b><?php echo $LANG['SOURCE']; ?>:</b> <?php echo $eArr2['source']; ?></span>
						</div>
						<div>
							<span><b><?php echo $LANG['APPLIED_STATUS']; ?>:</b> <?php echo ($appliedStatus?'applied':'not applied'); ?></span>
							<span style="margin-left:30px;"><b><?php echo $LANG['REVIEW_STATUS']; ?>:</b> <?php echo $reviewStr; ?></span>
						</div>
						<?php
						$edArr = $eArr2['edits'];
						foreach($edArr as $fieldName => $vArr){
							echo '<div style="margin:15px;">';
							echo '<b>'.$LANG['FIELD'].':</b> '.$fieldName.'<br/>';
							echo '<b>'.$LANG['OLD_VALUE'].':</b> '.$vArr['old'].'<br/>';
							echo '<b>'.$LANG['NEW_VALUE'].':</b> '.$vArr['new'].'<br/>';
							echo '</div>';
						}
						echo '<div style="margin:15px 0px;"><hr/></div>';
					}
				}
				?>
			</fieldset>
			<?php
		}
	}
	else{
		echo '<div style="margin:10px">'.$LANG['NO_PREV_EDITS'].'</div>';
	}
	$collAdminList = $occManager->getCollectionList();
	unset($collAdminList[$collId]);
	if($collAdminList){
		?>
		<fieldset style="padding:15px;margin:10px 0px;">
			<legend><b><?php echo $LANG['TRANSFER_SPEC']; ?></b></legend>
			<form name="transrecform" method="post" target="occurrenceeditor.php">
				<div>
					<b><?php echo $LANG['TARGET_COL']; ?></b><br />
					<select name="transfercollid">
						<option value="0"><?php echo $LANG['SEL_COL']; ?></option>
						<option value="0">----------------------</option>
						<?php
						foreach($collAdminList as $kCollid => $vCollName){
							echo '<option value="'.$kCollid.'">'.$vCollName.'</option>';
						}
						?>
					</select><br />
					<input name="remainoncoll" type="checkbox" value="1" CHECKED /> <?php echo $LANG['REMAIN_CURRENT']; ?>
				</div>
				<div style="margin:10px;">
					<input name="occindex" type="hidden" value="<?php echo $occIndex; ?>" />
					<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
					<button name="submitaction" type="submit" value="Transfer Record" ><?php echo $LANG['TRANSFER_RECORD']; ?></button>
				</div>
			</form>
		</fieldset>
		<?php
	}
	?>
	<fieldset style="padding:15px;margin:10px 0px;">
		<legend><b><?php echo $LANG['DEL_RECORD']; ?></b></legend>
		<form name="deleteform" method="post" action="occurrenceeditor.php" onsubmit="return confirm('<?php echo $LANG['SURE_DEL']; ?>')">
			<div style="margin:15px">
				<?php echo $LANG['REC_MUST_EVALUATE']; ?>
				<div style="margin:15px;display:block;">
					<button class="button-danger" name="verifydelete" type="button" value="Evaluate record for deletion" onclick="verifyDeletion(this.form);" ><?php echo $LANG['EVALUATE_FOR_DEL']; ?></button>
				</div>
				<div id="delverimgdiv" style="margin:15px;">
					<b><?php echo $LANG['IMG_LINKS']; ?>: </b>
					<span id="delverimgspan" style="color:orange;display:none;"><?php echo $LANG['CHECKING_IMG_LINKS']; ?>...</span>
					<div id="delimgfailspan" style="display:none;style:0px 10px 10px 10px;">
						<span style="color:red;"><?php echo $LANG['WARNING']; ?>:</span>
						<?php echo $LANG['IMAGES_ARE_LINKED']; ?>
					</div>
					<div id="delimgappdiv" style="display:none;">
						<span style="color:green;"><?php echo $LANG['APPROVED_FOR_DEL']; ?>.</span>
						<?php echo $LANG['NO_IMGS']; ?>.
					</div>
				</div>
				<div id="delvervoucherdiv" style="margin:15px;">
					<b><?php echo $LANG['CHECKLIST_LINKS']; ?>: </b>
					<span id="delvervouspan" style="color:orange;display:none;"><?php echo $LANG['CHECKING_LINKS']; ?>...</span>
					<div id="delvouappdiv" style="display:none;">
						<span style="color:green;"><?php echo $LANG['APPROVED_FOR_DEL']; ?>.</span>
						<?php echo $LANG['NO_CHECKLISTS']; ?>.
					</div>
					<div id="delvoulistdiv" style="display:none;style:0px 10px 10px 10px;">
						<span style="color:red;"><?php echo $LANG['WARNING']; ?>:</span>
						<?php echo $LANG['CHECKLIST_IS_LINKED']; ?>.
						<ul id="voucherlist">
						</ul>
					</div>
				</div>
				<div id="delapprovediv" style="margin:15px;display:none;">
					<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
					<input name="occindex" type="hidden" value="<?php echo $occIndex; ?>" />
					<button class="button-danger" name="submitaction" type="submit" value="Delete Occurrence"><?php echo $LANG['DEL_OCC']; ?></button>
				</div>
			</div>
		</form>
	</fieldset>
</div>
