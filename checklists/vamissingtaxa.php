<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherReport.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/checklists/vamissingtaxa.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/checklists/vamissingtaxa.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/checklists/vamissingtaxa.en.php');

$clid = array_key_exists('clid', $_REQUEST) ? filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$pid = array_key_exists('pid', $_REQUEST) ? htmlspecialchars($_REQUEST['pid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : "";
$displayMode = array_key_exists('displaymode', $_REQUEST) ? filter_var($_REQUEST['displaymode'], FILTER_SANITIZE_NUMBER_INT) : 0;
$startIndex = array_key_exists('start', $_REQUEST) ? filter_var($_REQUEST['start'], FILTER_SANITIZE_NUMBER_INT) : 0;

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
	<div role="main" id="innertext" style="background-color:white;">
		<div style='float:left;font-weight:bold;margin-left:5px'>
			<?php
			if($displayMode == 2){
			    echo $LANG['PROBLEMS'] . ': ';
			}
			else{
			    echo $LANG['POSS_MISSING'] . ': ';
			}
			echo $vManager->getMissingTaxaCount();
			?>
			<span style="margin-left:5px">
				<a href="voucheradmin.php?clid=<?php echo htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&displaymode=' . htmlspecialchars($displayMode, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&tabindex=1"><img src="../images/refresh.png" style="width:1.2em;vertical-align: middle;" title="<?php echo htmlspecialchars($LANG['REFRESH'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" /></a>
			</span>
			<span style="margin-left:5px;">
				<a href="voucherreporthandler.php?rtype=<?php echo htmlspecialchars(($displayMode==2?'problemtaxacsv':'missingoccurcsv'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank" rel="noopener" title="<?php echo htmlspecialchars($LANG['DOWNLOAD'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
					<img src="<?php echo $CLIENT_ROOT; ?>/images/dl.png" style="width:1.3em;vertical-align: middle;" />
				</a>
			</span>
		</div>
		<div style="float:right;">
			<form name="displaymodeform" method="post" action="voucheradmin.php">
				<b><?php echo $LANG['DISP_MODE']; ?>:</b>
				<select name="displaymode" onchange="this.form.submit()">
					<?php
					echo '<option value="0">' . $LANG['SPEC_LIST'] . '</option>';
					echo '<option value="1"' . ($displayMode==1?'SELECTED':'') . '>' . $LANG['BATCH_LINK'] . '</option>';
                    echo '<option value="2"' . ($displayMode==2?'SELECTED':'') . '>' . $LANG['PROBLEMS'] . '</option>';
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
						<?php echo $LANG['NOT_FOUND']; ?>
					</div>
					<form name="batchmissingform" method="post" action="voucheradmin.php" onsubmit="return validateBatchMissingForm(this.form);">
						<table class="styledtable" style="width: 100%">
							<tr>
								<th>
									<span title="<?php echo $LANG['SELECT_ALL']; ?>">
										<input name="selectallbatch" type="checkbox" onclick="selectAll(this);" value="0-0" />
									</span>
								</th>
								<th><?php echo $LANG['SPEC_ID']; ?></th>
								<th><?php echo $LANG['COLLECTOR']; ?></th>
								<th><?php echo $LANG['LOCALITY']; ?></th>
							</tr>
							<?php
							ksort($missingArr);
							foreach($missingArr as $sciname => $sArr){
								foreach($sArr as $occid => $oArr){
									$sciStr = $oArr['o_sn'];
									if(strtolower($sciname) != strtolower($oArr['o_sn'])) $sciStr .= ' (='.$sciname.')';
									echo '<tr>';
									echo '<td><input name="occids[]" type="checkbox" value="'.$occid.'-'.$oArr['tid'].'" /></td>';
									echo '<td><a href="../taxa/index.php?taxon=' . htmlspecialchars($oArr['tid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" rel="noopener">' . htmlspecialchars($sciStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></td>';
									echo '<td>';
									echo $oArr['recordedby'].' '.$oArr['recordnumber'].'<br/>';
									if($oArr['eventdate']) echo $oArr['eventdate'].'<br/>';
									echo '<a href="../collections/individual/index.php?occid=' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" rel="noopener">';
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
							<input name="usecurrent" type="checkbox" value="1" type="checkbox" checked /> <?php echo $LANG['ADD_CURRENT']; ?>
						</div>
						<div style="margin-top:3px;">
							<input name="excludevouchers" type="checkbox" value="1" <?php echo ($_REQUEST['excludevouchers']?'checked':''); ?>/> <?php echo $LANG['NO_VOUCHERS']; ?>
						</div>
						<div style="margin-top:8px;">
							<input name="tabindex" value="1" type="hidden" />
							<input name="clid" value="<?php echo $clid; ?>" type="hidden" />
							<input name="pid" value="<?php echo $pid; ?>" type="hidden" />
							<input name="displaymode" value="1" type="hidden" />
							<input name="start" type="hidden" value="<?php echo $startIndex; ?>" />
							<button name="submitaction" type="submit" value="submitVouchers"><?php echo $LANG['SUBMIT_VOUCHERS']; ?></button>
						</div>
					</form>
					<?php
					echo '<div style="float:left">' . $LANG['SPEC_COUNT'] . ' ' . $recCnt . '</div>';
					$queryStr = 'tabindex=1&displaymode=1&clid=' . $clid . '&pid=' . $pid . '&start=' . (++$startIndex);
					if($recCnt > $limitRange) echo '<div style="float:right;margin-right:30px;"><a style="margin-left:10px;" href="voucheradmin.php?' . htmlspecialchars($queryStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($LANG['VIEW_NEXT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . htmlspecialchars($limitRange, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
				}
				elseif($displayMode==2){
					?>
					<div style="clear:both;margin:10px;">
					<?php echo $LANG['MISSING_TAXA_EXPL']; ?>
					</div>
					<table class="styledtable" style="width: 100%">
						<tr>
							<th><?php echo $LANG['SPEC_ID']; ?></th>
							<th><?php echo $LANG['LINK_TO']; ?></th>
							<th><?php echo $LANG['COLLECTOR']; ?></th>
							<th><?php echo $LANG['LOCALITY']; ?></th>
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
										<button type="button" value="Link Voucher" onclick="linkVoucher(<?php echo $occid.','.$clid; ?>)"><?php echo $LANG['LINK_VOUCHER']; ?></button>
									</td>
									<?php
									echo '<td>';
									echo $oArr['recordedby'].' '.$oArr['recordnumber'].'<br/>';
									if($oArr['eventdate']) echo $oArr['eventdate'].'<br/>';
									echo '<a href="../collections/individual/index.php?occid=' . htmlspecialchars($occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank" rel="noopener">';
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
							<?php echo $LANG['NOT_IN_CHECKLIST'];
					        ?>
						</div>
						<?php
						foreach($missingArr as $tid => $sn){
							?>
							<div>
								<a href="#" onclick="openPopup('../taxa/index.php?taxauthid=1&taxon=<?php echo htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>','taxawindow');return false;"><?php echo htmlspecialchars($sn, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a>
								<a href="#" onclick="openPopup('../collections/list.php?db=all&usethes=1&reset=1&mode=voucher&taxa=<?php echo htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&targetclid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&targettid=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>','editorwindow');return false;">
									<img src="../images/link.png" style="width:1.2em;" title="<?php echo $LANG['LINK_VOUCHERS']; ?>" />
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