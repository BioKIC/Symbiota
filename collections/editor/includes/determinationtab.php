<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorDeterminations.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/includes/determinationtab.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/determinationtab.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/determinationtab.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$occId = $_GET['occid'];
$occIndex = $_GET['occindex'];
$identBy = $_GET['identby'];
$dateIdent = $_GET['dateident'];
$sciName = $_GET['sciname'];
$crowdSourceMode = $_GET['csmode'];
$editMode = $_GET['em'];

$annotatorname = $_GET['annotatorname'];
$annotatoremail = $_GET['annotatoremail'];
$catalognumber = $_GET['catalognumber'];
$institutioncode = $_GET['institutioncode'];

$occManager = new OccurrenceEditorDeterminations();

$occManager->setOccId($occId);
$detArr = $occManager->getDetMap($identBy, $dateIdent, $sciName);
$idRanking = $occManager->getIdentificationRanking();

$specImgArr = $occManager->getImageMap();  // find out if there are images in order to show/hide the button to display/hide images.

?>
<div id="determdiv" style="width:795px;">
	<div style="margin:15px 0px 40px 15px;">
		<div>
			<b><u><?php echo $LANG['ID_CONFIDENCE']; ?></u></b>
			<?php
			if($editMode < 3){
				?>
				<a href="#" title="<?php echo $LANG['MODIFY_CURRENT_RANKING']; ?>" onclick="toggle('idrankeditdiv');toggle('idrankdiv');return false;">
					<img src="../../images/edit.png" style="border:0px;width:1.2em;" />
				</a>
				<?php
			}
			?>
		</div>
		<?php
		if($editMode < 3){
			?>
			<div id="idrankeditdiv" style="display:none;margin:15px;">
				<form name="editidrankingform" action="occurrenceeditor.php" method="post">
					<div style='margin:3px;'>
						<b><?php echo $LANG['CONFIDENCE_IN_DET']; ?>:</b>
						<select name="confidenceranking">
							<?php
							$currentRanking = 5;
							if($idRanking) $currentRanking = $idRanking['ranking'];
							?>
							<option value="10" <?php echo ($currentRanking==10?'SELECTED':''); ?>>10 - <?php echo $LANG['ABSOLUTE']; ?></option>
							<option value="9" <?php echo ($currentRanking==9?'SELECTED':''); ?>>9 - <?php echo $LANG['HIGH']; ?></option>
							<option value="8" <?php echo ($currentRanking==8?'SELECTED':''); ?>>8 - <?php echo $LANG['HIGH']; ?></option>
							<option value="7" <?php echo ($currentRanking==7?'SELECTED':''); ?>>7 - <?php echo $LANG['HIGH']; ?></option>
							<option value="6" <?php echo ($currentRanking==6?'SELECTED':''); ?>>6 - <?php echo $LANG['MEDIUM']; ?></option>
							<option value="5" <?php echo ($currentRanking==5?'SELECTED':''); ?>>5 - <?php echo $LANG['MEDIUM']; ?></option>
							<option value="4" <?php echo ($currentRanking==4?'SELECTED':''); ?>>4 - <?php echo $LANG['MEDIUM']; ?></option>
							<option value="3" <?php echo ($currentRanking==3?'SELECTED':''); ?>>3 - <?php echo $LANG['LOW']; ?></option>
							<option value="2" <?php echo ($currentRanking==2?'SELECTED':''); ?>>2 - <?php echo $LANG['LOW']; ?></option>
							<option value="1" <?php echo ($currentRanking==1?'SELECTED':''); ?>>1 - <?php echo $LANG['LOW']; ?></option>
							<option value="0" <?php echo ($currentRanking==0?'SELECTED':''); ?>>0 - <?php echo $LANG['UNLIKELY']; ?></option>
						</select>
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['NOTES']; ?>:</b>
						<input name="notes" type="text" value="<?php echo ($idRanking?$idRanking['notes']:''); ?>" style="width:90%;" />
					</div>
					<div style='margin:15px;'>
						<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
						<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
						<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
						<input type="hidden" name="ovsid" value="<?php echo ($idRanking?$idRanking['ovsid']:''); ?>" />
						<button type="submit" name="submitaction" value="Submit Verification Edits"><?php echo $LANG['SUBMIT_VERIFY_EDITS']; ?></button>
					</div>
				</form>
			</div>
			<?php
		}
		?>
		<div id="idrankdiv" style="margin:15px;">
			<?php
			if($idRanking){
				echo '<div>';
				echo '<b>'.$LANG['RANK'].': </b> '.$idRanking['ranking'];
				if($idRanking['ranking'] < 4){
					echo ' - '.$LANG['L_LOW'].' ';
				}
				elseif($idRanking['ranking'] < 8){
					echo ' - '.$LANG['L_MEDIUM'].' ';
				}
				elseif($idRanking['ranking'] > 7){
					echo ' - '.$LANG['L_HIGH'].' ';
				}
				echo '</div>';
				echo '<div><b>'.$LANG['SET_BY'].':</b> '.($idRanking['username']?$idRanking['username']:'undefined').'</div>';
				if($idRanking['notes']) echo '<div><b>'.$LANG['NOTES'].':</b> '.$idRanking['notes'].'</div>';
			}
			else{
				echo $LANG['NOT_RANKED'];
			}
			?>
		</div>
	</div>
	<div style="clear:both">
		<fieldset style="margin:15px;padding:15px;">
			<legend><b><?php echo $LANG['DET_HISTORY']; ?></b></legend>
			<div style="float:right;">
				<a href="#" onclick="toggle('newdetdiv');return false;" title="<?php echo $LANG['ADD_NEW_DET']; ?>" ><img style="border:0px;width:1.5em;" src="../../images/add.png" /></a>
			</div>
			<?php
			if(!$detArr){
				?>
				<div style="font-weight:bold;margin:10px;font-size:120%;">
					<?php echo $LANG['NO_HIST_ANNOTATIONS']; ?>
				</div>
				<?php
			}
			?>
			<div id="newdetdiv" style="display:<?php echo ($detArr?'none':''); ?>;">
				<form name="detaddform" action="occurrenceeditor.php" method="post" onsubmit="return verifyDetForm(this)">
					<fieldset style="margin:15px;padding:15px;">
						<legend><b><?php echo $LANG['ADD_NEW_DET']; ?></b></legend>
						<div style="float:right;margin:-7px -4px 0px 0px;font-weight:bold;">
							<span id="imgProcOnSpanDet" style="display:block;">
								<?php
								if($specImgArr){
									?>
									<a href="#" onclick="toggleImageTdOn();return false;">&gt;&gt;</a>
									<?php
								}
								?>
							</span>
							<span id="imgProcOffSpanDet" style="display:none;">
								<?php
								if($specImgArr){
									?>
									<a href="#" onclick="toggleImageTdOff();return false;">&lt;&lt;</a>
									<?php
								}
								?>
							</span>
						</div>
						<?php
						if($editMode == 3){
							?>
							<div style="color:red;margin:10px;">
								<?php echo $LANG['NO_RIGHTS']; ?>
							</div>
							<?php
						}
						?>
						<div style='margin:3px;'>
							<b><?php echo $LANG['ID_QUALIFIER']; ?>:</b>
							<input type="text" name="identificationqualifier" title="e.g. cf, aff, etc" />
						</div>
						<div style='margin:3px;'>
							<b><?php echo $LANG['SCI_NAME']; ?>:</b>
							<input type="text" id="dafsciname" name="sciname" style="background-color:lightyellow;width:350px;" onfocus="initDetAutocomplete(this.form)" />
							<input type="hidden" id="daftidtoadd" name="tidtoadd" value="" />
							<input type="hidden" name="family" value="" />
						</div>
						<div style='margin:3px;'>
							<b><?php echo $LANG['AUTHOR']; ?>:</b>
							<input type="text" name="scientificnameauthorship" style="width:200px;" />
						</div>
						<div style='margin:3px;'>
							<b><?php echo $LANG['CONFIDENCE_IN_DET']; ?>:</b>
							<select name="confidenceranking">
								<option value="8"><?php echo $LANG['HIGH']; ?></option>
								<option value="5" selected><?php echo $LANG['MEDIUM']; ?></option>
								<option value="2"><?php echo $LANG['LOW']; ?></option>
							</select>
						</div>
						<div style='margin:3px;'>
							<b><?php echo $LANG['DETERMINER']; ?>:</b>
							<input type="text" name="identifiedby" style="background-color:lightyellow;width:200px;" />
						</div>
						<div style='margin:3px;'>
							<b><?php echo $LANG['DATE']; ?>:</b>
							<input type="text" name="dateidentified" style="background-color:lightyellow;" onchange="detDateChanged(this.form);" />
						</div>
						<div style='margin:3px;'>
							<b><?php echo $LANG['REFERENCE']; ?>:</b>
							<input type="text" name="identificationreferences" style="width:350px;" />
						</div>
						<div style='margin:3px;'>
							<b><?php echo $LANG['NOTES']; ?>:</b>
							<input type="text" name="identificationremarks" style="width:350px;" />
						</div>
						<div style='margin:3px;'>
							<input type="checkbox" name="makecurrent" value="1" /> <?php echo $LANG['MAKE_THIS_CURRENT']; ?>
						</div>
						<div style='margin:3px;'>
							<input type="checkbox" name="printqueue" value="1" /> <?php echo $LANG['ADD_TO_PRINT']; ?>
						</div>
						<div style='margin:15px;'>
							<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
							<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
							<input type="hidden" name="annotatorname" value="<?php echo $annotatorname; ?>" />
							<input type="hidden" name="annotatoremail" value="<?php echo $annotatoremail; ?>" />
							<input type="hidden" name="catalognumber" value="<?php echo $catalognumber; ?>" />
							<input type="hidden" name="institutioncode" value="<?php echo $institutioncode; ?>" />
							<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
							<div style="float:left;">
								<button type="submit" name="submitaction" value="submitDetermination" ><?php echo $LANG['SUBMIT_DET']; ?></button>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<?php
			foreach($detArr as $detId => $detRec){
				$canEdit = 0;
				if($editMode < 3 || !$detRec['appliedstatus']) $canEdit = 1;
				?>
				<div id="detdiv-<?php echo $detId;?>">
					<div>
						<?php
						if($detRec['identificationqualifier']) echo $detRec['identificationqualifier'].' ';
							$nonItalicized = ' ';
							if (!empty($detRec['nonItalicized']))
								$nonItalicized .= $detRec['nonItalicized'];
							echo '<b><i>' . $detRec['sciname'] . '</i>' . $nonItalicized .'</b> ' . $detRec['scientificnameauthorship'];
						if($detRec['iscurrent']){
							if($detRec['appliedstatus']){
								echo '<span style="margin-left:10px;color:red;">'.$LANG['CURRENT_DET'].'</span>';
							}
						}
						if($canEdit){
							?>
							<a href="#" onclick="toggle('editdetdiv-<?php echo $detId;?>');return false;" title="<?php echo $LANG['EDIT_DET']; ?>"><img style="border:0px;width:1.2em;" src="../../images/edit.png" /></a>
							<?php
						}
						if(!$detRec['appliedstatus']){
							?>
							<span style="color:red;margin-left:15px;">
								<?php echo $LANG['APPLIED_STATUS_PENDING']; ?>
							</span>
							<?php
						}
						?>
					</div>
					<div style='margin:3px 0px 0px 15px;'>
						<b><?php echo $LANG['DETERMINER']; ?>:</b> <?php echo $detRec['identifiedby']; ?>
						<span style="margin-left:40px;">
							<b><?php echo $LANG['DATE']; ?>:</b> <?php echo $detRec['dateidentified']; ?>
						</span>
					</div>
					<?php
					if($detRec['identificationreferences']){
						?>
						<div style='margin:3px 0px 0px 15px;'>
							<b><?php echo $LANG['REFERENCE']; ?>:</b> <?php echo $detRec['identificationreferences']; ?>
						</div>
						<?php
					}
					if($detRec['identificationremarks']){
						?>
						<div style='margin:3px 0px 0px 15px;'>
							<b><?php echo $LANG['NOTES']; ?>:</b> <?php echo $detRec['identificationremarks']; ?>
						</div>
						<?php
					}
					if($detRec['printqueue']){
						?>
						<div style='margin:3px 0px 0px 15px;color:orange'>
							<?php echo $LANG['ADDED_TO_QUEUE']; ?>
						</div>
						<?php
					}
					?>
				</div>
				<?php
				if($canEdit){
					?>
					<div id="editdetdiv-<?php echo $detId;?>" style="display:none;margin:15px 5px;">
						<fieldset>
							<legend><b><?php echo $LANG['EDIT_DET']; ?></b></legend>
							<form name="deteditform" action="occurrenceeditor.php" method="post" onsubmit="return verifyDetForm(this);">
								<div style='margin:3px;'>
									<b><?php echo $LANG['ID_QUALIFIER']; ?>:</b>
									<input type="text" name="identificationqualifier" value="<?php echo $detRec['identificationqualifier']; ?>" title="e.g. cf, aff, etc" />
								</div>
								<div style='margin:3px;'>
									<b><?php echo $LANG['SCI_NAME']; ?>:</b>
									<input type="text" id="defsciname-<?php echo $detId;?>" name="sciname" value="<?php echo $detRec['sciname']; ?>" style="background-color:lightyellow;width:350px;" onfocus="initDetAutocomplete(this.form)" />
									<input type="hidden" id="deftidtoadd" name="tidtoadd" value="" />
									<input type="hidden" name="family" value="" />
								</div>
								<div style='margin:3px;'>
									<b><?php echo $LANG['AUTHOR']; ?>:</b>
									<input type="text" name="scientificnameauthorship" value="<?php echo $detRec['scientificnameauthorship']; ?>" style="width:200px;" />
								</div>
								<div style='margin:3px;'>
									<b><?php echo $LANG['DETERMINER']; ?>:</b>
									<input type="text" name="identifiedby" value="<?php echo $detRec['identifiedby']; ?>" style="background-color:lightyellow;width:200px;" />
								</div>
								<div style='margin:3px;'>
									<b><?php echo $LANG['DATE']; ?>:</b>
									<input type="text" name="dateidentified" value="<?php echo $detRec['dateidentified']; ?>" style="background-color:lightyellow;" />
								</div>
								<div style='margin:3px;'>
									<b><?php echo $LANG['REFERENCE']; ?>:</b>
									<input type="text" name="identificationreferences" value="<?php echo $detRec['identificationreferences']; ?>" style="width:350px;" />
								</div>
								<div style='margin:3px;'>
									<b><?php echo $LANG['NOTES']; ?>:</b>
									<input type="text" name="identificationremarks" value="<?php echo $detRec['identificationremarks']; ?>" style="width:350px;" />
								</div>
								<div style='margin:3px;'>
									<b><?php echo $LANG['SORT_SEQUENCE']; ?>:</b>
									<input type="text" name="sortsequence" value="<?php echo $detRec['sortsequence']; ?>" style="width:40px;" />
								</div>
								<div style='margin:3px;'>
									<input type="checkbox" name="printqueue" value="1" <?php if($detRec['printqueue']) echo 'checked'; ?> /> <?php echo $LANG['ADDED_TO_QUEUE']; ?>
								</div>
								<div style='margin:15px;'>
									<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
									<input type="hidden" name="detid" value="<?php echo $detId; ?>" />
									<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
									<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
									<button type="submit" name="submitaction" value="submitDeterminationEdit"><?php echo $LANG['SUBMIT_DET_EDITS']; ?></button>
								</div>
							</form>
							<?php
							if($editMode < 3 && !$detRec['iscurrent']){
								?>
								<div style="padding:15px;background-color:lightgreen;width:280px;margin:15px;">
									<form name="detremapform" action="occurrenceeditor.php" method="post">
										<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
										<input type="hidden" name="detid" value="<?php echo $detId; ?>" />
										<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
										<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
										<?php
										if($detRec['appliedstatus']){
											?>
											<button type="submit" name="submitaction" value="Make Determination Current" ><?php echo $LANG['MAKE_DET_CURRENT']; ?></button>
											<?php
										}
										else{
											?>
											<input type="submit" name="submitaction" value="Apply Determination" /><br/>
											<input type="checkbox" name="makecurrent" value="1" <?php echo ($detRec['iscurrent']?'checked':''); ?> /> <?php echo $LANG['MAKE_CURRENT']; ?>
											<?php
										}
										?>
									</form>
								</div>
								<?php
							}
							?>
							<div style="width:155px;margin:15px;">
								<form name="detdelform" action="occurrenceeditor.php" method="post" onsubmit="return window.confirm('<?php echo $LANG['SURE_DELETE']; ?>');">
									<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
									<input type="hidden" name="detid" value="<?php echo $detId; ?>" />
									<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
									<input type="hidden" name=" <?php echo $crowdSourceMode; ?>" />
									<button class="button-danger" type="submit" name="submitaction" value="Delete Determination" ><?php echo $LANG['DELETE_DET']; ?></button>
								</form>
							</div>
						</fieldset>
					</div>
					<?php
				}
				?>
				<hr style='margin:10px 0px 10px 0px;' />
				<?php
			}
			?>
		</fieldset>
	</div>
</div>
