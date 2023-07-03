<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherReport.php');
include_once($SERVER_ROOT.'/content/lang/checklists/vamissingtaxa.'.$LANG_TAG.'.php');

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$displayMode = (array_key_exists('displaymode',$_REQUEST)?$_REQUEST['displaymode']:0);
$startIndex = array_key_exists("start",$_REQUEST)?$_REQUEST["start"]:0;

$vManager = new ChecklistVoucherReport();
$vManager->setClid($clid);
$vManager->setCollectionVariables();
$limitRange = 1000;

$isEditor = false;
if($IS_ADMIN || (array_key_exists("ClAdmin",$USER_RIGHTS) && in_array($clid,$USER_RIGHTS["ClAdmin"]))){
	$isEditor = true;
}
if($isEditor){
	$missingArr = array();
	if($displayMode==1) $missingArr = $vManager->getMissingTaxaSpecimens($startIndex, $limitRange);
	elseif($displayMode==2) $missingArr = $vManager->getMissingProblemTaxa();
	else $missingArr = $vManager->getMissingTaxa();
	?>
	<div id="innertext" style="background-color:white;">
		<div style='float:left;font-weight:bold;margin-left:5px'>
			<?php
			if($displayMode == 2){
			    echo (isset($LANG['PROBLEMS'])?$LANG['PROBLEMS']:'Problem Taxa').': ';
			}
			else{
			    echo (isset($LANG['POSS_MISSING'])?$LANG['POSS_MISSING']:'Possible Missing Taxa').': ';
			}
			echo $vManager->getMissingTaxaCount();
			?>
			<span style="margin-left:5px">
				<a href="voucheradmin.php?clid=<?php echo $clid.'&pid='.$pid.'&displaymode='.$displayMode; ?>&tabindex=1"><img src="../images/refresh.png" style="width:14px;vertical-align: middle;" title="<?php echo (isset($LANG['REFRESH'])?$LANG['REFRESH']:'Refresh List'); ?>" /></a>
			</span>
			<span style="margin-left:5px;">
				<a href="voucherreporthandler.php?rtype=<?php echo ($displayMode==2?'problemtaxacsv':'missingoccurcsv').'&clid='.$clid; ?>" target="_blank" title="<?php echo (isset($LANG['DOWNLOAD'])?$LANG['DOWNLOAD']:'Download Specimen Records'); ?>">
					<img src="<?php echo $CLIENT_ROOT; ?>/images/dl.png" style="vertical-align: middle;" />
				</a>
			</span>
		</div>
		<div style="float:right;">
			<form name="displaymodeform" method="post" action="voucheradmin.php">
				<b><?php echo (isset($LANG['DISP_MODE'])?$LANG['DISP_MODE']:'Display Mode'); ?>:</b>
				<select name="displaymode" onchange="this.form.submit()">
					<?php
					echo '<option value="0">'.(isset($LANG['SPEC_LIST'])?$LANG['SPEC_LIST']:'Species List').'</option>';
					echo '<option value="1"'.($displayMode==1?'SELECTED':'').'>'.(isset($LANG['BATCH_LINK'])?$LANG['BATCH_LINK']:'Batch Linking').'</option>';
                    echo '<option value="2"'.($displayMode==2?'SELECTED':'').'>'.(isset($LANG['PROBLEMS'])?$LANG['PROBLEMS']:'Problem Taxa').'</option>';
					?>
				</select>
				<input name="clid" id="clvalue" type="hidden" value="<?php echo $clid; ?>" />
				<input name="pid" type="hidden" value="<?php echo $pid; ?>" />
				<input name="tabindex" type="hidden" value="1" />
			</form>
		</div>
		<div>
			<?php
			$recCnt = 0;
			if($missingArr){
				if($displayMode==1){
					?>
					<div style="clear:both;margin:10px;">
						<?php echo (isset($LANG['NOT_FOUND'])?$LANG['NOT_FOUND']:'Listed below are specimens identified to a species not found in the checklist.
                        Use the form to add the names and link the vouchers as a batch action.'); ?>
					</div>
					<form name="batchmissingform" method="post" action="voucheradmin.php" onsubmit="return validateBatchMissingForm(this.form);">
						<table class="styledtable" style="width: 100%">
							<tr>
								<th>
									<span title="Select All">
										<input name="selectallbatch" type="checkbox" onclick="selectAll(this);" value="0-0" />
									</span>
								</th>
								<th><?php echo (isset($LANG['SPEC_ID'])?$LANG['SPEC_ID']:'Specimen ID'); ?></th>
								<th><?php echo (isset($LANG['COLLECTOR'])?$LANG['COLLECTOR']:'Collector'); ?></th>
								<th><?php echo (isset($LANG['LOCALITY'])?$LANG['LOCALITY']:'Locality'); ?></th>
							</tr>
							<?php
							ksort($missingArr);
							foreach($missingArr as $sciname => $sArr){
								foreach($sArr as $occid => $oArr){
									$sciStr = $sciname;
									if($sciStr != $oArr['o_sn']) $sciStr .= ' (syn: '.$oArr['o_sn'].')';
									echo '<tr>';
									echo '<td><input name="occids[]" type="checkbox" value="'.$occid.'-'.$oArr['tid'].'" /></td>';
									echo '<td><a href="../taxa/index.php?taxon='.$oArr['tid'].'" target="_blank">'.$sciStr.'</a></td>';
									echo '<td>';
									echo $oArr['recordedby'].' '.$oArr['recordnumber'].'<br/>';
									if($oArr['eventdate']) echo $oArr['eventdate'].'<br/>';
									echo '<a href="../collections/individual/index.php?occid='.$occid.'" target="_blank">';
									echo $oArr['collcode'];
									echo '</a>';
									echo '</td>';
									echo '<td>'.$oArr['locality'].'</td>';
									echo '</tr>';
									$recCnt++;
								}
							}
							?>
						</table>
						<div style="margin-top:8px;">
							<input name="usecurrent" type="checkbox" value="1" type="checkbox" checked /> <?php echo (isset($LANG['ADD_CURRENT'])?$LANG['ADD_CURRENT']:'Add name using current taxonomy'); ?>
						</div>
						<div style="margin-top:3px;">
							<input name="excludevouchers" type="checkbox" value="1" <?php echo ($_REQUEST['excludevouchers']?'checked':''); ?>/> <?php echo (isset($LANG['NO_VOUCHERS'])?$LANG['NO_VOUCHERS']:'Add names without linking vouchers'); ?>
						</div>
						<div style="margin-top:8px;">
							<input name="tabindex" value="1" type="hidden" />
							<input name="clid" value="<?php echo $clid; ?>" type="hidden" />
							<input name="pid" value="<?php echo $pid; ?>" type="hidden" />
							<input name="displaymode" value="1" type="hidden" />
							<input name="start" type="hidden" value="<?php echo $startIndex; ?>" />
							<button name="submitaction" type="submit" value="submitVouchers"><?php echo (isset($LANG['SUBMIT_VOUCHERS'])?$LANG['SUBMIT_VOUCHERS']:'Submit Vouchers'); ?></button>
						</div>
					</form>
					<?php
					echo '<div style="float:left">'.(isset($LANG['SPEC_COUNT'])?$LANG['SPEC_COUNT']:'Specimen Count').' '.$recCnt.'</div>';
					$queryStr = 'tabindex=1&displaymode=1&clid='.$clid.'&pid='.$pid.'&start='.(++$startIndex);
					if($recCnt > $limitRange) echo '<div style="float:right;margin-right:30px;"><a style="margin-left:10px;" href="voucheradmin.php?'.$queryStr.'">'.(isset($LANG['VIEW_NEXT'])?$LANG['VIEW_NEXT']:'View Next').' '.$limitRange.'</a></div>';
				}
				elseif($displayMode==2){
					?>
					<div style="clear:both;margin:10px;">
					<?php echo (isset($LANG['MISSING_TAXA_EXPL'])?$LANG['MISSING_TAXA_EXPL']:'Listed below are species name obtained from specimens
                        matching the above search term but are not found within the taxonomic thesaurus (possibly misspelled?). To add as a voucher,
						type the correct name from the checklist, and then click the Link Voucher button.
						The correct name must already be added to the checklist before voucher can be linked.');
					?>
					</div>
					<table class="styledtable" style="width: 100%">
						<tr>
							<th><?php echo (isset($LANG['SPEC_ID'])?$LANG['SPEC_ID']:'Specimen ID'); ?></th>
							<th><?php echo (isset($LANG['LINK_TO'])?$LANG['LINK_TO']:'Link to'); ?></th>
							<th><?php echo (isset($LANG['COLLECTOR'])?$LANG['COLLECTOR']:'Collector'); ?></th>
							<th><?php echo (isset($LANG['LOCALITY'])?$LANG['LOCALITY']:'Locality'); ?></th>
						</tr>
						<?php
						ksort($missingArr);
						foreach($missingArr as $sciname => $sArr){
							foreach($sArr as $occid => $oArr){
								?>
								<tr>
									<td><?php echo $sciname; ?></td>
									<td>
										<input id="tid-<?php echo $occid; ?>" class="taxon-input" name="sciname" type="text" onfocus="initAutoComplete('tid-<?php echo $occid; ?>')" />
										<button type="button" value="Link Voucher" onclick="linkVoucher(<?php echo $occid.','.$clid; ?>)"><?php echo (isset($LANG['LINK_VOUCHER'])?$LANG['LINK_VOUCHER']:'Link Voucher'); ?></button>
									</td>
									<?php
									echo '<td>';
									echo $oArr['recordedby'].' '.$oArr['recordnumber'].'<br/>';
									if($oArr['eventdate']) echo $oArr['eventdate'].'<br/>';
									echo '<a href="../collections/individual/index.php?occid='.$occid.'" target="_blank">';
									echo $oArr['collcode'];
									echo '</a>';
									echo '</td>';
									?>
									<td><?php echo $oArr['locality']; ?></td>
								</tr>
								<?php
								$recCnt++;
							}
						}
						?>
					</table>
					<?php
				}
				else{
					?>
					<div style="margin:20px;clear:both;">
						<div style="clear:both;margin:10px;">
							<?php echo (isset($LANG['NOT_IN_CHECKLIST'])?$LANG['NOT_IN_CHECKLIST']:'Listed below are taxon names not found in the checklist
                            but are represented by one or more specimens that have a locality matching the above search term.');
					        ?>
						</div>
						<?php
						foreach($missingArr as $tid => $sn){
							?>
							<div>
								<a href="#" onclick="openPopup('../taxa/index.php?taxauthid=1&taxon=<?php echo $tid.'&clid='.$clid; ?>','taxawindow');return false;"><?php echo $sn; ?></a>
								<a href="#" onclick="openPopup('../collections/list.php?db=all&usethes=1&reset=1&mode=voucher&taxa=<?php echo $tid.'&targetclid='.$clid.'&targettid='.$tid;?>','editorwindow');return false;">
									<img src="../images/link.png" style="width:13px;" title="<?php echo (isset($LANG['LINK_VOUCHERS'])?$LANG['LINK_VOUCHERS']:'Link Voucher Specimens'); ?>" />
								</a>
							</div>
							<?php
							$recCnt++;
						}
						?>
					</div>
					<?php
				}
			}
			?>
		</div>
	</div>
	<?php
}
?>