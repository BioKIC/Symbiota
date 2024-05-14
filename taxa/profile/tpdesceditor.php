<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TPDescEditorManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/taxa/profile/tpdesceditor.'.$LANG_TAG.'.php'))
include_once($SERVER_ROOT.'/content/lang/taxa/profile/tpdesceditor.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/taxa/profile/tpdesceditor.en.php');
header('Content-Type: text/html; charset='.$CHARSET);

$tid = array_key_exists('tid',$_REQUEST)?$_REQUEST['tid']:0;

$descEditor = new TPDescEditorManager();
if($tid) $descEditor->setTid($tid);

$isEditor = false;
if($IS_ADMIN || array_key_exists('TaxonProfile',$USER_RIGHTS)) $isEditor = true;

if($isEditor){
	$descList = $descEditor->getDescriptions();
	$langArr = $descEditor->getLangArr();
	?>
	<script type="text/javascript" src="../../js/tinymce/tinymce.min.js"></script>
	<script type="text/javascript">
		tinymce.init({
			selector: "textarea",
			width: "100%",
			height: 300,
			menubar: false,
			plugins: "link,charmap,code,paste",
			toolbar : ["bold italic underline | cut copy paste | outdent indent | subscript superscript | undo redo removeformat | link | charmap | code"],
			default_link_target: "_blank",
			paste_as_text: true,
			convert_urls: false
		});
	</script>
	<style>
		fieldset{ width:90%; margin:10px; padding:10px; }
		legend{ font-weight: bold }
	</style>
	<div style="float:right;" onclick="toggle('adddescrblock');" title="Add a New Description">
		<img style='border:0px;width:1.3em;' src='../../images/add.png'/>
	</div>
	<div id='adddescrblock' style='display:<?php echo ($descList?'none':''); ?>;'>
		<form name='adddescrblockform' action="tpeditor.php" method="post">
			<fieldset>
				<legend><?= $LANG['NEW_DESC_BLOCK'] ?></legend>
				<div>
					<?= $LANG['LANGUAGE'] ?>:
					<select name="langid">
						<option value=""><?= $LANG['SEL_LANGUAGE'] ?></option>
						<?php
						foreach($langArr as $langID => $langName){
							echo '<option value="' . $langID . '" ' . (strpos($langName,'(' . $DEFAULT_LANG . ')') ? 'SELECTED' : '') . '>' . $langName . '</option>';
						}
						?>
					</select>
				</div>
				<div>
					<?= $LANG['CAPTION'] ?>: <input id='caption' name='caption' style='width:300px;' type='text' />
				</div>
				<div>
					<?= $LANG['SOURCE'] ?>: <input id='source' name='source' style='width:450px;' type='text' />
				</div>
				<div>
					<?= $LANG['SOURCE_URL'] ?>: <input id='sourceurl' name='sourceurl' style='width:450px;' type='text' />
				</div>
				<div>
					<?= $LANG['NOTES'] ?>: <input id='notes' name='notes' style='width:450px;' type='text' />
				</div>
				<div>
					<?= $LANG['SORT_SEQUENCE'] ?>: <input name='displaylevel' style='width:40px;' type='text' />
				</div>
				<div>
					<button name='action' style='margin-top:5px;' type='submit' value='Add Description Block' ><?= $LANG['ADD_DESC_BLOCK'] ?></buton>
					<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
					<input type="hidden" name="tabindex" value="4" />
				</div>
			</fieldset>
		</form>
	</div>
	<?php
	if($descList){
		foreach($descList as $langid => $descArr){
			?>
			<fieldset>
				<legend><?php echo $langArr[$langid]; ?> <?= $LANG['DESCRIPTIONS'] ?></legend>
				<?php
				foreach($descArr as $tdbid => $dArr){
					?>
					<fieldset>
						<legend><?php echo ($dArr["caption"] ? $dArr["caption"] : $LANG['DESCRIPTION_'] . $dArr["displaylevel"]) . ' (#' . $tdbid . ')'; ?></legend>
						<div style="float:right;" onclick="toggle('dblock-<?php echo $tdbid;?>');" title="<?= $LANG['EDIT_DESC_BLOCK'] ?>">
							<img style='border:0px;width:1.3em;' src='../../images/edit.png'/>
						</div>
						<?php
						if($descEditor->getTid() != $dArr['tid']){
							?>
							<div style="margin:4px 0px;">
								<b><?= $LANG['LINKED_TO_SYN'] ?>:</b> <?php echo $dArr['sciname']; ?>
								(<a href="tpeditor.php?action=remap&tdbid=<?php echo htmlspecialchars($tdbid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&tid=' . htmlspecialchars($descEditor->getTid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?= $LANG['RELINK_TO_ACCEPTED'] ?></a>)
							</div>
							<?php
						}
						?>
						<div><b><?= $LANG['CAPTION'] ?>:</b> <?php echo $dArr["caption"]; ?></div>
						<div><b><?= $LANG['SOURCE'] ?>:</b> <?php echo $dArr["source"]; ?></div>
						<div><b><?= $LANG['SOURCE_URL'] ?>:</b> <a href='<?php echo htmlspecialchars($dArr["sourceurl"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>'><?php echo htmlspecialchars($dArr["sourceurl"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></div>
						<div><b><?= $LANG['NOTES'] ?>:</b> <?php echo $dArr["notes"]; ?></div>
						<div id="dblock-<?php echo $tdbid;?>" style="display:none;margin-top:10px;">
							<fieldset>
								<legend><?= $LANG['DESC_BLOCK_EDITS'] ?></legend>
								<form id='updatedescrblock' name='updatedescrblock' action="tpeditor.php" method="post">
									<div>
									<?= $LANG['LANGUAGE'] ?>:
										<select name="langid">
											<option value=""><?= $LANG['SEL_LANGUAGE'] ?></option>
											<?php
											foreach($langArr as $langID => $langName){
												echo '<option value="' . $langID . '" ' . ($langid==$langID ? 'SELECTED' : '') . '>' . $langName . '</option>';
											}
											?>
										</select>
									</div>
									<div>
										<?= $LANG['CAPTION'] ?>:
										<input id='caption' name='caption' style='width:450px;' type='text' value='<?php echo $dArr["caption"];?>' />
									</div>
									<div>
										<?= $LANG['SOURCE'] ?>:
										<input id='source' name='source' style='width:450px;' type='text' value='<?php echo $dArr["source"];?>' />
									</div>
									<div>
										<?= $LANG['SOURCE_URL'] ?>:
										<input id='sourceurl' name='sourceurl' style='width:500px;' type='text' value='<?php echo $dArr["sourceurl"];?>' />
									</div>
									<div>
										<?= $LANG['NOTES'] ?>:
										<input name='notes' style='width:450px;' type='text' value='<?php echo $dArr["notes"];?>' />
									</div>
									<div>
										<?= $LANG['DISPLAY_LEVEL'] ?>:
										<input name='displaylevel' style='width:40px;' type='text' value='<?php echo $dArr['displaylevel'];?>' />
									</div>
									<div style="margin:10px;">
										<input name="tdProfileID" type="hidden" value="<?php echo $dArr['tdProfileID'];?>" />
										<input name="tdbid" type="hidden" value="<?php echo $tdbid;?>" />
										<input name="tid" type="hidden" value="<?php echo $descEditor->getTid();?>" />
										<input name="tabindex" type="hidden" value="4" />
										<button name="action" type="submit" value="saveDescriptionBlock"><?= $LANG['SAVE_EDITS'] ?></button>
									</div>
								</form>
								<hr/>
								<div style='margin:10px;border:2px solid red;padding:15px;'>
									<form name='delstmt' action='tpeditor.php' method='post' onsubmit="return window.confirm('<?= $LANG['SURE_DELETE_DESC'] ?>');">
										<input type='hidden' name='tdbid' value='<?php echo $tdbid;?>' />
										<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
										<input type="hidden" name="tabindex" value="4" />
										<button class="button-danger" name='action' type="submit" value='Delete Description Block'><?= $LANG['DEL_DESC_BLOCK'] ?></button> <?= $LANG['INC_STATEMENTS_BELOW'] ?>
									</form>
								</div>
							</fieldset>
						</div>
						<div style="margin-top:10px;">
							<fieldset>
								<legend><?= $LANG['STATEMENTS'] ?></legend>
								<div onclick="toggle('addstmt-<?php echo $tdbid;?>');" style="float:right;" title="<?= $LANG['ADD_NEW_STATEMENT'] ?>">
									<img style='border:0px;width:1.3em;' src='../../images/add.png'/>
								</div>
								<div id='addstmt-<?php echo $tdbid;?>' style='display:<?php echo (isset($dArr["stmts"])?'none':'block'); ?>'>
									<form name='adddescrstmtform' action="tpeditor.php" method="post">
										<fieldset style='margin:5px 0px 0px 15px;'>
											<legend><?= $LANG['NEW_DESC_STATMENT'] ?></legend>
											<div style='margin:3px;'>
												<?= $LANG['HEADING'] ?>: <input name='heading' style='margin-top:5px;' type='text' />&nbsp;&nbsp;&nbsp;&nbsp;
												<input name='displayheader' type='checkbox' value='1' CHECKED /> Display Heading
											</div>
											<div style='margin:3px;'>
												<textarea name='statement'></textarea>
											</div>
											<div style='margin:3px;'>
												<?= $LANG['SORT_SEQUENCE'] ?>:
												<input name='sortsequence' style='margin-top:5px;width:40px;' type='text' value='' />
											</div>
											<div style="margin:10px;">
												<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
												<input type='hidden' name='tdbid' value='<?php echo $tdbid;?>' />
												<input type="hidden" name="tabindex" value="4" />
												<button name='action' type='submit' value='Add Statement' ><?= $LANG['ADD_STATEMENT'] ?></button>
											</div>
										</fieldset>
									</form>
								</div>
								<?php
								if(array_key_exists("stmts",$dArr)){
									$sArr = $dArr["stmts"];
									foreach($sArr as $tdsid => $stmtArr){
										?>
										<div style="margin-top:3px;clear:both;">
											<span onclick="toggle('edstmt-<?php echo $tdsid;?>');" title="<?= $LANG['EDIT_STATEMENT'] ?>"><img style='border:0px;width:1.2em;' src='../../images/edit.png'/></span>
											<?php
											echo ($stmtArr["heading"]?'<b>'.$stmtArr["heading"].'</b>:':'');
											echo $stmtArr["statement"];
											?>
										</div>
										<div class="edstmt-<?php echo $tdsid;?>" style="clear:both;display:none;">
											<div style='margin:5px 0px 5px 20px;border:2px solid cyan;padding:5px;'>
												<form id='updatedescr' name='updatedescr' action="tpeditor.php" method="post">
													<div>
														<b><?= $LANG['HEADING'] ?>:</b> <input name='heading' style='margin:3px;' type='text' value='<?php echo $stmtArr["heading"];?>' />
														<input name='displayheader' type='checkbox' value='1' <?php echo ($stmtArr["displayheader"]?"CHECKED":"");?> /> <?= $LANG['DISPLAY_HEADER'] ?>
													</div>
													<div>
														<textarea name='statement'  style="width:99%;height:200px;margin:3px;"><?php echo $stmtArr["statement"];?></textarea>
													</div>
													<div>
														<b><?= $LANG['SORT_SEQUENCE'] ?>:</b>
														<input name='sortsequence' style='width:40px;' type='text' value='<?php echo $stmtArr["sortsequence"];?>' />
													</div>
													<div style="margin:10px;">
														<input name="tdsid" type="hidden" value="<?php echo $tdsid;?>" />
														<input name="tid" type="hidden" value="<?php echo $descEditor->getTid();?>" />
														<input name="tabindex" type="hidden" value="4" />
														<button name="action" type="submit" value="saveStatementEdit"><?= $LANG['SAVE_EDITS'] ?></button>
													</div>
												</form>
											</div>
											<div style='margin:5px 0px 5px 20px;border:2px solid red;padding:15px;'>
												<form name='delstmt' action='tpeditor.php' method='post' onsubmit="return window.confirm('<?= $LANG['SURE_DELETE_STATEMENT'] ?>');">
													<input name='tdsid' type='hidden' value='<?php echo $tdsid;?>' />
													<input name='tid' type="hidden" value='<?php echo $descEditor->getTid();?>' />
													<input name="tabindex" type="hidden" value="4" />
													<button name='action' type="submit" value='Delete Statement'><?= $LANG['DEL_STATEMENT'] ?></button>
												</form>
											</div>
										</div>
										<?php
									}
								}
								?>
							</fieldset>
						</div>
					</fieldset>
					<?php
				}
				?>
			</fieldset>
			<?php
		}
	}
	else{
		echo '<h2 style="font-size: 2rem;">' . $LANG['NO_DESC'] . '</h2>';
	}
}
?>
