<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceIndividual.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/individual/linkedresources.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/individual/linkedresources.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/individual/linkedresources.en.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$indManager = new OccurrenceIndividual();

$occid = isset($_GET['occid']) ? $indManager->sanitizeInt($_GET['occid']) : 0;
$tid = array_key_exists('tid', $_REQUEST) ? $indManager->sanitizeInt($_REQUEST['tid']) : 0;
$clid = array_key_exists('clid', $_REQUEST) ? $indManager->sanitizeInt($_REQUEST['clid']) : 0;

$indManager->setOccid($occid);
?>
<style>
	.section-title{  }
</style>
<div id='innertext' style='width:95%;min-height:400px;clear:both;background-color:white;'>
	<fieldset>
		<legend><?= $LANG['SPCHECKREL'] ?></legend>
		<?php
		$vClArr = $indManager->getVoucherChecklists();
		$clArr = $indManager->getChecklists(array_keys($vClArr));
		if($IS_ADMIN || $clArr) echo '<div style="float:right"><a href="#" onclick="toggle(\'voucher-block\');return false"><img src="../../images/add.png" /></a></div>';
		if($vClArr){
			echo '<div class="section-title">' . $LANG['VOUCHEROFFOLLOWING'] . '</div>';
			echo '<ul style="margin:15px 0px 25px 0px;">';
			foreach($vClArr as $vClid => $vClArr){
				echo '<li>';
				echo '<a href="../../checklists/checklist.php?showvouchers=1&clid=' . $vClid . '" target="_blank">' . $vClArr['name'] . '</a>&nbsp;&nbsp;';
				if(isset($USER_RIGHTS['ClAdmin']) && in_array($vClid, $USER_RIGHTS['ClAdmin'])){
					echo '<a href="index.php?formsubmit=deletevoucher&delvouch=' . $vClArr['voucherID'] . '&occid=' . $occid . '" title=' . $LANG['DELVOUCHER'] . ' onclick="return confirm(\"' . $LANG['CONFIRMVOUCHER'] . '\")"><img src="../../images/drop.png" style="width:12px;" /></a>';
				}
				echo '</li>';
			}
			echo '</ul>';
		}
		else{
			echo '<div style="margin:15px 0px">' . $LANG['NOTAVOUCHER'] . '</div>';
		}
		if($IS_ADMIN || $clArr){
			?>
			<div style='margin-top:15px; display: none;'  id="voucher-block" class="voucher-block">
				<fieldset>
					<legend><?= $LANG['NEWVOUCHER'] ?></legend>
					<?php
					if($tid){
						?>
						<div style="margin:10px;">
							<form action="index.php" method="post" onsubmit="return verifyVoucherForm(this);">
								<div>
									<?= $LANG['ADDVOUCHERCHECK'] ?>:
									<input name='occid' type='hidden' value='<?= $occid; ?>'>
									<input name='vtid' type='hidden' value='<?= $tid; ?>'>
									<select id='vclid' name='vclid'>
						  				<option value='0'><?= $LANG['SELECTCHECKLIST'] ?></option>
						  				<option value='0'>--------------------------</option>
						  				<?php
							  			foreach($clArr as $clKey => $clValue){
							  				echo "<option value='".$clKey."' ".($clid==$clKey?"SELECTED":"").">$clValue</option>\n";
										}
										?>
									</select>
								</div>
								<div style='margin:5px 0px 0px 10px;'>
									<?= $LANG['NOTES'] ?>:
									<input name="vnotes" type="text" size="50" title="<?= $LANG['VIEWABLEPUBLIC'] ?>" />
								</div>
								<div style='margin:5px 0px 0px 10px;'>
									<?= $LANG['EDITORNOTES'] ?>:
									<input name="veditnotes" type="text" size="50" title="<?= $LANG['VIEWABLEEDITORS']; ?>">
								</div>
								<div>
									<input name="tabindex" type="hidden" value="2" >
									<button type='submit' name='formsubmit' value="addVoucher"><?= $LANG['ADDVOUCHER'] ?></button>
								</div>
							</form>
						</div>
						<?php
					}
					else{
						?>
						<div style='margin:20px;'>
							<?= $LANG['UNABLETOADD'] ?>
						</div>
						<?php
					}
					?>
				</fieldset>
			</div>
			<?php
		}
		?>
	</fieldset>
	<?php
	$datasetArr = $indManager->getDatasetArr();
	if($datasetArr){
		echo '<fieldset>';
		echo '<legend>' . ($LANG['DATASETLINKAGES']) . '</legend>';
		if($SYMB_UID) echo '<div style="float:right"><a href="#" onclick="toggle(\'dataset-block\');return false"><img src="../../images/add.png" /></a></div>';
		$dsDisplayStr = '';
		foreach($datasetArr as $dsid => $dsArr){
			if(isset($dsArr['linked']) && $dsArr['linked']){
				$dsDisplayStr .= '<li>';
				$dsDisplayStr .= '<a href="../datasets/datasetmanager.php?datasetid=' . $dsid . '" target="_blank">' . $dsArr['name'] . '</a>';
				if(isset($dsArr['role']) && $dsArr['role']) $dsDisplayStr .= ' (role: '.$dsArr['role'].')';
				if(isset($dsArr['notes']) && $dsArr['notes']) $dsDisplayStr .= ' - '.$dsArr['notes'];
				$dsDisplayStr .= '</li>';
			}
		}
		if($dsDisplayStr){
			echo '<div class="section-title">' . $LANG['MEMBEROF'] . '</div>';
			echo '<ul>'.$dsDisplayStr.'</ul>';
		}
		else echo '<div style="margin:15px 0px">' . $LANG['OCCURRENCENOTLINKED'] . '</div>';
		if($SYMB_UID){
			?>
			<div class="dataset-block" id="dataset-block" style="display: none;">
				<fieldset>
					<legend><?= $LANG['CREATENEWREL'] ?></legend>
					<form action="../datasets/datasetHandler.php" method="post" onsubmit="return verifyDatasetForm(this);">
						<div style="margin:3px">
							<select name="targetdatasetid">
								<option value=""><?= $LANG['SELECTEXISTING'] ?></option>
								<option value="">----------------------------------</option>
								<?php
								foreach($datasetArr as $dsid => $dsArr){
									if(!array_key_exists('linked',$dsArr)){
										echo '<option value="'.$dsid.'">'.$dsArr['name'].'</option>';
									}
								}
								?>
								<option value="--newDataset"><?= $LANG['CREATENEWDATASET'] ?></option>
							</select>
						</div>
						<div style="margin:5px">
							<label><?= $LANG['NOTES'] ?>:</label><br/>
							<input name="notes" type="text" value="" maxlength="250" style="width:90%;" />
						</div>
						<div style="margin:15px">
							<input name="occid" type="hidden" value="<?= $occid; ?>" />
							<input name="sourcepage" type="hidden" value="individual" />
							<button name="action" type="submit" value="addSelectedToDataset" ><?= $LANG['LINKTO'] ?></button>
						</div>
					</form>
				</fieldset>
			</div>
			<?php
		}
		echo '</fieldset>';
	}
	?>
</div>