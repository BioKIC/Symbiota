<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherReport.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/checklists/vaconflicts.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/checklists/vaconflicts.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/checklists/vaconflicts.en.php');

$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:"";
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);

$vManager = new ChecklistVoucherReport();
$vManager->setClid($clid);

$isEditor = false;
if($IS_ADMIN || (array_key_exists("ClAdmin",$USER_RIGHTS) && in_array($clid,$USER_RIGHTS["ClAdmin"]))){
	$isEditor = true;
}

?>
<script>
	function selectAll(cbox){
		var boxesChecked = true;
		if(!cbox.checked) boxesChecked = false;
		var f = cbox.form;
		for(var i=0;i<f.length;i++){
			if(f.elements[i].name == "occid[]") f.elements[i].checked = boxesChecked;
		}
	}

	function validateBatchConflictForm(f){
		var formVerified = false;
		for(var h=0;h<f.length;h++){
			if(f.elements[h].name == "occid[]" && f.elements[h].checked){
				formVerified = true;
				break;
			}
		}
		if(!formVerified){
			alert("<?php echo $LANG['SELECT_ONE']; ?>");
			return false;
		}
		f.submit();
	}
</script>
<div role="main" id="innertext" style="background-color:white;">
	<div style="margin-bottom:10px;">
		<?php
		echo $LANG['EXPLAIN_PARAGRAPH'];
		?>
	</div>
	<?php
	if($conflictArr = $vManager->getConflictVouchers()){
		echo '<div style="font-weight:bold;">' . $LANG['CONFLICT_COUNT'] . ': ' . count($conflictArr) . '</div>';
		?>
		<form name="batchConflictForm" method="post" action="voucheradmin.php">
			<table class="styledtable">
				<tr>
					<th><input type="checkbox" onclick="selectAll(this)" /></th>
					<th><b><?php echo $LANG['CHECK_ID']; ?></b></th>
					<th><b><?php echo $LANG['VOUCHER_SPEC']; ?></b></th>
					<th><b><?php echo $LANG['CORRECTED_ID']; ?></b></th>
					<th><b><?php echo $LANG['IDED_BY']; ?></b></th>
				</tr>
				<?php
				foreach($conflictArr as $id => $vArr){
					?>
					<tr>
						<td>
							<input name="occid[]" type="checkbox" value="<?php echo $vArr['occid']; ?>" />
						</td>
						<td>
							<a href="#" onclick="return openPopup('clsppeditor.php?tid=<?php echo htmlspecialchars($vArr['tid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&clid=" . htmlspecialchars($vArr['clid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>','editorwindow');">
								<?php echo $vArr['listid']; ?>
							</a>
							<?php
							if($vArr['clid'] != $clid) echo '<br/>' . $LANG['FROM_CHILD'];
							?>
						</td>
						<td>
							<a href="#" onclick="return openPopup('../collections/individual/index.php?occid=<?php echo htmlspecialchars($vArr['occid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>','occwindow');">
								<?php echo $vArr['recordnumber']; ?>
							</a>
						</td>
						<td>
							<?php echo $vArr['specid'] ?>
						</td>
						<td>
							<?php echo $vArr['identifiedby'] ?>
						</td>
					</tr>
					<?php
				}
				?>
			</table>
			<div>
				<input name="removetaxa" type="checkbox" value="1" checked />
				<?php echo $LANG['REMOVE_TAXA']; ?>
			</div>
			<div style="margin: 10px 0px">
				<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
				<input name="pid" type="hidden" value="<?php echo $pid; ?>" />
				<input name="tabindex" type="hidden" value="2" />
				<input name="submitaction" type="hidden" value="resolveconflicts" />
				<b><?php echo $LANG['BATCH_ACTION']; ?>:</b>
				<button name="submitbutton" type="button" value="Link Vouchers to Corrected Identification" onclick="return validateBatchConflictForm(this.form)"><?php echo $LANG['LINK_VOUCHERS']; ?></button><br/>
				<div>* <?php echo $LANG['CORRECTED_WILL_ADD']; ?></div>
			</div>
		</form>
		<?php
	}
	else echo '<h3>' . $LANG['NO_CONFLICTS'] . '</h3>';
	?>
</div>
