<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceIndividual.php');
@include_once($SERVER_ROOT.'/content/lang/collections/individual/linkedresources.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$occid = $_GET["occid"];
$tid = $_GET["tid"];
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;

//Sanitize input variables
if(!is_numeric($occid)) $occid = 0;
if(!is_numeric($tid)) $tid = 0;
if(!is_numeric($clid)) $clid = 0;

$indManager = new OccurrenceIndividual();
$indManager->setOccid($occid);

?>
<style>
	.section-title{  }
</style>
<div id='innertext' style='width:95%;min-height:400px;clear:both;background-color:white;'>
	<fieldset>
		<legend><?php echo (isset($LANG['SPCHECKREL'])?$LANG['SPCHECKREL']:'Species Checklist Relationships'); ?></legend>
		<?php
		$vClArr = $indManager->getVoucherChecklists();
		$clArr = $indManager->getChecklists(array_keys($vClArr));
		if($IS_ADMIN || $clArr) echo '<div style="float:right"><a href="#" onclick="toggle(\'voucher-block\');return false"><img src="../../images/add.png" /></a></div>';
		if($vClArr){
			echo '<div class="section-title">'.(isset($LANG['VOUCHEROFFOLLOWING'])?$LANG['VOUCHEROFFOLLOWING']:'Specimen voucher of the following checklists').'</div>';
			echo '<ul style="margin:15px 0px 25px 0px;">';
			foreach($vClArr as $vClid => $vClArr){
				echo '<li>';
				echo '<a href="../../checklists/checklist.php?showvouchers=1&clid='.$vClid.'" target="_blank">'.$vClArr['name'].'</a>&nbsp;&nbsp;';
				if(isset($USER_RIGHTS['ClAdmin']) && in_array($vClid, $USER_RIGHTS['ClAdmin'])){
					$delStr = (isset($LANG['DELVOUCHER'])?$LANG['DELVOUCHER']:'Delete voucher link');
					$confirmStr = (isset($LANG['CONFIRMVOUCHER'])?$LANG['CONFIRMVOUCHER']:'Are you sure you want to remove this voucher link?');
					echo '<a href="index.php?delvouch='.$vClArr['voucherID'].'&occid='.$occid.'" title='.$delStr.' onclick="return confirm(\"'.$confirmStr.'\")"><img src="../../images/drop.png" style="width:12px;" /></a>';
				}
				echo '</li>';
			}
			echo '</ul>';
		}
		else{
			echo '<div style="margin:15px 0px">'.(isset($LANG['NOTAVOUCHER'])?$LANG['NOTAVOUCHER']:'This occurrence has not been designated as a voucher for a species checklist').'</div>';
		}
		if($IS_ADMIN || $clArr){
			?>
			<div style='margin-top:15px;'>
				<fieldset id="voucher-block" style="display:none">
					<legend><?php echo (isset($LANG['NEWVOUCHER'])?$LANG['NEWVOUCHER']:'New Voucher Assignment'); ?></legend>
					<?php
					if($tid){
						?>
						<div style="margin:10px;">
							<form action="../../checklists/clsppeditor.php" method="post" onsubmit="return verifyVoucherForm(this);">
								<div>
									<?php echo (isset($LANG['ADDVOUCHERCHECK'])?$LANG['ADDVOUCHERCHECK']:'Add as voucher to checklist'); ?>:
									<input name='voccid' type='hidden' value='<?php echo $occid; ?>'>
									<input name='tid' type='hidden' value='<?php echo $tid; ?>'>
									<select id='clid' name='clid'>
						  				<option value='0'><?php echo (isset($LANG['SELECTCHECKLIST'])?$LANG['SELECTCHECKLIST']:'Select a Checklist'); ?></option>
						  				<option value='0'>--------------------------</option>
						  				<?php
							  			foreach($clArr as $clKey => $clValue){
							  				echo "<option value='".$clKey."' ".($clid==$clKey?"SELECTED":"").">$clValue</option>\n";
										}
										?>
									</select>
								</div>
								<div style='margin:5px 0px 0px 10px;'>
									<?php echo (isset($LANG['NOTES'])?$LANG['NOTES']:'Notes'); ?>:
									<input name="vnotes" type="text" size="50" title="<?php echo (isset($LANG['VIEWABLEPUBLIC'])?$LANG['VIEWABLEPUBLIC']:'Viewable to public'); ?>" />
								</div>
								<div style='margin:5px 0px 0px 10px;'>
									<?php echo (isset($LANG['EDITORNOTES'])?$LANG['EDITORNOTES']:'Editor Notes'); ?>:
									<input name="veditnotes" type="text" size="50" title="<?php echo (isset($LANG['VIEWABLEEDITORS'])?$LANG['VIEWABLEEDITORS']:'Viewable only to checklist editors'); ?>">
								</div>
								<div>
									<button type='submit' name='action' value="Add Voucher"><?php echo (isset($LANG['ADDVOUCHER'])?$LANG['ADDVOUCHER']:'Add Voucher'); ?></button>
								</div>
							</form>
						</div>
						<?php
					}
					else{
						?>
						<div style='margin:20px;'>
							<?php echo (isset($LANG['UNABLETOADD'])?$LANG['UNABLETOADD']:'Unable to use this specimen record as a voucher
							because scientific name counld not be verified in the taxonomic thesaurus (misspelled?)'); ?>
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
		echo '<legend>'.(isset($LANG['DATASETLINKAGES'])?$LANG['DATASETLINKAGES']:'Dataset Linkages').'</legend>';
		if($SYMB_UID) echo '<div style="float:right"><a href="#" onclick="toggle(\'dataset-block\');return false"><img src="../../images/add.png" /></a></div>';
		$dsDisplayStr = '';
		foreach($datasetArr as $dsid => $dsArr){
			if(isset($dsArr['linked']) && $dsArr['linked']){
				$dsDisplayStr .= '<li>';
				$dsDisplayStr .= '<a href="../datasets/datasetmanager.php?datasetid='.$dsid.'" target="_blank">'.$dsArr['name'].'</a>';
				if(isset($dsArr['role']) && $dsArr['role']) $dsDisplayStr .= ' (role: '.$dsArr['role'].')';
				if(isset($dsArr['notes']) && $dsArr['notes']) $dsDisplayStr .= ' - '.$dsArr['notes'];
				$dsDisplayStr .= '</li>';
			}
		}
		if($dsDisplayStr){
			echo '<div class="section-title">'.(isset($LANG['MEMBEROF'])?$LANG['MEMBEROF']:'Member of the following datasets').'</div>';
			echo '<ul>'.$dsDisplayStr.'</ul>';
		}
		else echo '<div style="margin:15px 0px">'.(isset($LANG['OCCURRENCENOTLINKED'])?$LANG['OCCURRENCENOTLINKED']:'Occurrence is not linked to any datasets').'</div>';
		if($SYMB_UID){
			?>
			<fieldset id="dataset-block" style="display:none">
				<legend><?php echo (isset($LANG['CREATENEWREL'])?$LANG['CREATENEWREL']:'Create New Dataset Relationship'); ?></legend>
				<form action="../datasets/datasetHandler.php" method="post" onsubmit="return verifyDatasetForm(this);">
					<div style="margin:3px">
						<select name="targetdatasetid">
							<option value=""><?php echo (isset($LANG['SELECTEXISTING'])?$LANG['SELECTEXISTING']:'Select an Existing Dataset'); ?></option>
							<option value="">----------------------------------</option>
							<?php
							foreach($datasetArr as $dsid => $dsArr){
								if(!array_key_exists('linked',$dsArr)){
									echo '<option value="'.$dsid.'">'.$dsArr['name'].'</option>';
								}
							}
							?>
							<option value="--newDataset"><?php echo (isset($LANG['CREATENEWDATASET'])?$LANG['CREATENEWDATASET']:'Create New Dataset'); ?></option>
						</select>
					</div>
					<div style="margin:5px">
						<b><?php echo (isset($LANG['NOTES'])?$LANG['NOTES']:'Notes'); ?>:<br/>
						<input name="notes" type="text" value="" maxlength="250" style="width:90%;" />
					</div>
					<div style="margin:15px">
						<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
						<input name="sourcepage" type="hidden" value="individual" />
						<button name="action" type="submit" value="addSelectedToDataset" ><?php echo (isset($LANG['LINKTO'])?$LANG['LINKTO']:'Link to Dataset'); ?></button>
					</div>
				</form>
			</fieldset>
			<?php
		}
		echo '</fieldset>';
	}
	?>
</div>