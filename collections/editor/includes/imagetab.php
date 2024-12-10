<?php
include_once('../../../config/symbini.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/includes/imagetab.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/imagetab.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/imagetab.en.php');
include_once($SERVER_ROOT . '/classes/Media.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$occId = filter_var($_GET['occid'], FILTER_SANITIZE_NUMBER_INT);
$occIndex = filter_var($_GET['occindex'], FILTER_SANITIZE_NUMBER_INT);
$crowdSourceMode = filter_var($_GET['csmode'], FILTER_SANITIZE_NUMBER_INT);

$specImgArr = [];
try {
	$specImgArr = Media::fetchOccurrenceMedia($occId);
	$mediaTags = Media::getMediaTags(array_keys($specImgArr));

	foreach($specImgArr as $key => $v) {
		if(array_key_exists($key, $mediaTags)) {
			$specImgArr[$key]["tags"] = $mediaTags[$key];
		}
	}
} catch(Exception $e) {
	error_log($e->getMessage());
}

$creatorArray = Media::getCreatorArray();
?>
<script type="text/javascript">
	function verifyImgAddForm(f){
		var filePath = f.elements["imgfile"].value;
		if(filePath == ""){
			if(f.elements["imgurl"].value == ""){
				alert("<?php echo $LANG['SELECT_FILE']; ?>");
				return false;
			}
			else{
				filePath = f.elements["imgfile"].value
			}
		}
		filePath = filePath.toLowerCase();
		if((filePath.indexOf(".tif") > -1) || (filePath.indexOf(".png") > -1) && (filePath.indexOf(".dng") > -1)){
			alert("<?php echo $LANG['NOT_WEB_OPTIMIZED']; ?>");
			return false;
		}
		return true;
	}

	function verifyImgEditForm(f){

		return true;
	}

	function verifyImgDelForm(f){
		if(confirm("<?php echo $LANG['CONFIRM_IMAGE_DELETE']; ?>")){
			return true;
		}
		return false;
	}

	function verifyImgRemapForm(f){
		if(f.targetoccid.value == ''){
			alert("<?php echo $LANG['SELECT_TARGET']; ?>");
			return false;
		}
		return true;
	}
</script>
<div id="imagediv" style="width:795px;">
	<div style="float:right;cursor:pointer;" onclick="toggle('addimgdiv');" title="<?php echo $LANG['ADD_IMG']; ?>">
		<img style="border:0px;width:1.5em;" src="../../images/add.png" />
	</div>
	<div id="addimgdiv" style="display:<?php echo ($specImgArr?'none':''); ?>;">
		<form name="imgnewform" action="occurrenceeditor.php" method="post" enctype="multipart/form-data" onsubmit="return verifyImgAddForm(this);">
			<fieldset style="padding:15px">
				<legend><b><?php echo $LANG['ADD_IMG']; ?></b></legend>
				<div style='padding:15px;width:90%;border:1px solid yellow;background-color:FFFF99;'>
					<div class="targetdiv" style="display:block;">
						<div style="font-weight:bold;font-size:110%;margin-bottom:5px;">
							<?php echo $LANG['SELECT_IMG']; ?>:
						</div>
						<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
						<input type='hidden' name='MAX_FILE_SIZE' value='20000000' />
						<div>
							<input name='imgfile' type='file' accept="<?= implode(",", $ALLOWED_MEDIA_MIME_TYPES) ?>" size='70'/>
						</div>
						<div style="float:right;text-decoration:underline;font-weight:bold;">
							<a href="#" onclick="toggle('targetdiv');return false;"><?php echo $LANG['ENTER_URL']; ?></a>
						</div>
					</div>
					<div class="targetdiv" style="display:none;">
						<div style="margin-bottom:10px;">
							<?php echo $LANG['ENTER_URL_EXPLAIN']; ?>
						</div>
						<div>
							<b><?php echo $LANG['IMG_URL']; ?>:</b><br/>
							<input type='text' name='originalUrl' size='70'/>
						</div>
						<div>
							<b><?php echo $LANG['MED_VERS'].(isset($IMG_WEB_WIDTH) && $IMG_WEB_WIDTH?', +-'.$IMG_WEB_WIDTH.'px':''); ?>):</b><br/>
							<input type='text' name='weburl' size='70'/>
						</div>
						<div>
							<b><?php echo $LANG['THUMB_VERS'].(isset($IMG_TN_WIDTH) && $IMG_TN_WIDTH?', +-'.$IMG_TN_WIDTH.'px':''); ?>):</b><br/>
							<input type='text' name='thumbnailUrl' size='70'/>
						</div>
						<div style="float:right;text-decoration:underline;font-weight:bold;">
							<a href="#" onclick="toggle('targetdiv');return false;">
								<?php echo $LANG['UPLOAD_LOCAL']; ?>
							</a>
						</div>
						<div>
							<input type="checkbox" name="copytoserver" value="1" /> <?php echo $LANG['COPY_TO_SERVER']; ?>
						</div>
					</div>
					<div>
						<input type="checkbox" name="nolgimage" value="1" /> <?php echo $LANG['DO_NOT_MAP_LARGE']; ?>
					</div>
				</div>
				<div style="clear:both;margin:20px 0px 5px 10px;">
					<b><?php echo $LANG['CAPTION']; ?>:</b>
					<input name="caption" type="text" size="40" value="" />
				</div>
				<div style='margin:0px 0px 5px 10px;'>
					<b><?php echo $LANG['CREATOR']; ?>:</b>
					<select name='photographeruid' name='photographeruid'>
						<option value=""><?php echo $LANG['SELECT_CREATOR']; ?></option>
						<option value="">---------------------------------------</option>
						<?php
						foreach($creatorArray as $id => $uname){
								echo '<option value="'.$id.'" >';
								echo $uname;
								echo '</option>';
							}
						?>
					</select>
					<a href="#" onclick="toggle('imgaddoverride');return false;" title="<?php echo $LANG['DISPLAY_PHOTOG_OVER']; ?>">
						<img src="../../images/editplus.png" style="border:0px;width:1.5em;" />
					</a>
				</div>
				<div id="imgaddoverride" style="margin:0px 0px 5px 10px;display:none;">
					<b><?php echo $LANG['PHOTOG_OVER']; ?>:</b>
					<input name='photographer' type='text' style="width:300px;" maxlength='100' />
					* <?php echo $LANG['WILL_OVERRIDE']; ?>
				</div>
				<div style="margin:0px 0px 5px 10px;">
					<b><?php echo $LANG['NOTES']; ?>:</b>
					<input name="notes" type="text" size="40" value="" />
				</div>
				<div style="margin:0px 0px 5px 10px;">
					<b><?php echo $LANG['COPYRIGHT']; ?>:</b>
					<input name="copyright" type="text" size="40" value="" />
				</div>
				<div style="margin:0px 0px 5px 10px;">
					<b><?php echo $LANG['SOURCE_WEBPAGE']; ?>:</b>
					<input name="sourceurl" type="text" size="40" value="" />
				</div>
				<div style="margin:0px 0px 5px 10px;">
					<b><?php echo $LANG['SORT']; ?>:</b>
					<input name="sortoccurrence" type="text" size="10" value="" />
				</div>
				<div style="margin:0px 0px 5px 10px;">
					<b><?php echo $LANG['DESCRIBE_IMAGE']; ?></b>
				</div>
					<?php
					$imageTagKeys = Media::getMediaTagKeys();
					foreach($imageTagKeys as $key => $description) {
						echo '<div style="margin-left:10px;">';
						echo '<input name="ch_'.$key.'" type="checkbox" value="1" /> '.$description.'</br>';
						echo '</div>';
					}
					?>
				<div style="margin:10px 0px 10px 20px;">
					<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
					<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
					<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
					<input type="hidden" name="tabindex" value="1" />
					<button type="submit" name="submitaction" value="Submit New Image"><?php echo $LANG['SUBMIT_NEW']; ?></button>
				</div>
			</fieldset>
		</form>
		<hr style="margin:30px 0px;" />
	</div>
	<div style="clear:both;margin:15px;">
		<?php
		if($specImgArr){
			?>
			<table>
				<?php
				foreach($specImgArr as $imgId => $imgArr){
					$imgUrl = $imgArr["url"];
					$origUrl = $imgArr["originalUrl"];
					$tnUrl = $imgArr["thumbnailUrl"];
					?>
					<tr>
					<?php if($imgArr['mediaType'] === 'image'):?>
						<td style="width:300px;text-align:center;padding:20px;">
							<?php
							if((!$imgUrl || $imgUrl == 'empty') && $origUrl) $imgUrl = $origUrl;
							if(!$tnUrl && $imgUrl) $tnUrl = $imgUrl;
							if(array_key_exists('MEDIA_DOMAIN', $GLOBALS)){
								if(substr($imgUrl, 0, 1) == '/'){
									$imgUrl = $GLOBALS['MEDIA_DOMAIN'] . $imgUrl;
								}
								if($origUrl && substr($origUrl, 0, 1) == '/'){
									$origUrl = $GLOBALS['MEDIA_DOMAIN'] . $origUrl;
								}
								if($tnUrl && substr($tnUrl, 0, 1) == '/'){
									$tnUrl = $GLOBALS['MEDIA_DOMAIN'] . $tnUrl;
								}
							}
							echo '<a href="' . $imgUrl . '" target="_blank">';
							if(array_key_exists('error', $imgArr)){
								echo '<div style="font-weight:bold;font-size:140%">'.$imgArr['error'].'</div>';
							}
							else{
								echo '<img src="'.$imgUrl.'" style="width:250px;" title="'.$imgArr["caption"].'" />';
							}
							echo '</a>';
							if($imgUrl != $origUrl) echo '<div><a href="' . $imgUrl .'" target="_blank">' . $LANG['OPEN_MED'] . '</a></div>';
							if($origUrl) echo '<div><a href="' . $origUrl . '" target="_blank">' . $LANG['OPEN_LARGE'] . '</a></div>';
							?>
						</td>
						<?php elseif($imgArr['mediaType'] === 'audio'):?>
						<td style="vertical-align: middle">
							<audio controls>
								<source src="<?= $origUrl ?>" type="<?=$imgArr['format']?>">
								Your browser does not support the audio element.
							</audio>
						</td>
						<?php endif?>
						<td class="imgInfo" style="text-align:left;padding:10px;">
							<div style="float:right;cursor:pointer;" onclick="toggle('img<?php echo $imgId; ?>editdiv');" title="<?php echo $LANG['EDIT_METADATA']; ?>">
								<img style="border:0px;width:1.2em;" src="../../images/edit.png" />
							</div>
							<div style="margin-top:30px">
								<div>
									<b><?php echo $LANG['CAPTION']; ?>:</b>
									<?php echo $imgArr["caption"]; ?>
								</div>
								<div>
									<b><?php echo $LANG['CREATOR']; ?>:</b>
									<?php
									if($imgArr["creator"]){
										echo $imgArr["creator"];
									}
									else if($imgArr["creatoruid"]){
										echo $creatorArray[$imgArr["creatoruid"]];
									}
									?>
								</div>
								<div>
									<b><?php echo $LANG['NOTES']; ?>:</b>
									<?php echo $imgArr["notes"]; ?>
								</div>
								<div>
									<b><?php echo $LANG['TAGS']; ?>:</b>
									<?php
									if(isset($imgArr['tags'])) echo implode(', ',$imgArr['tags']);
									?>
								</div>
								<div>
									<b><?php echo $LANG['COPYRIGHT']; ?>:</b>
									<?php echo $imgArr['copyright']; ?>
								</div>
								<div>
									<b><?php echo $LANG['SOURCE_WEBPAGE']; ?>:</b>
									<a href="<?php echo $imgArr['sourceurl']; ?>" target="_blank">
										<?php
										$sourceUrlDisplay = $imgArr['sourceurl'];
										if($sourceUrlDisplay && strlen($sourceUrlDisplay) > 60) $sourceUrlDisplay = '...'.substr($sourceUrlDisplay,-60);
										echo $sourceUrlDisplay;
										?>
									</a>
								</div>
								<div>
									<b><?php echo $LANG['WEB_URL']; ?>: </b>
									<a href="<?php echo $imgArr["url"]; ?>"  title="<?php echo $imgArr["url"]; ?>" target="_blank">
										<?php
										$urlDisplay = $imgArr["url"];
										if(strlen($urlDisplay) > 60) $urlDisplay = '...'.substr($urlDisplay,-60);
										echo $urlDisplay;
										?>
									</a>
								</div>
								<div>
									<b><?php echo $LANG['LARGE_IMG_URL']; ?>: </b>
									<a href="<?= $origUrl ?>" title="<?= $origUrl ?>" target="_blank">
										<?php
										echo $origUrl && strlen($origUrl) > 60?
										'...'.substr($origUrl,-60):
										$origUrl;
										?>
									</a>
								</div>
								<div>
									<b><?php echo $LANG['THUMB_URL']; ?>: </b>
									<a href="<?= $tnUrl ?>" title="<?= $tnUrl ?>" target="_blank">
										<?= $tnUrl && strlen($tnUrl) > 60 ?
											'...'.substr($tnUrl,-60) : $tnUrl?>
									</a>
								</div>
								<div>
									<b><?php echo $LANG['SORT']; ?>:</b>
									<?= $imgArr['sortOccurrence']; ?>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div id="img<?php echo $imgId; ?>editdiv" style="display:none;clear:both;">
								<form name="img<?php echo $imgId; ?>editform" action="occurrenceeditor.php" method="post" onsubmit="return verifyImgEditForm(this);">
									<fieldset style="padding:15px">
										<legend><b><?php echo $LANG['EDIT_IMG_DATA']; ?></b></legend>
										<div>
											<b><?php echo $LANG['CAPTION']; ?>:</b><br/>
											<input name="caption" type="text" value="<?php echo $imgArr["caption"]; ?>" style="width:300px;" />
										</div>
										<div>
											<b><?php echo $LANG['CREATOR']; ?>:</b><br/>
											<select name='photographeruid' name='photographeruid'>
												<option value=""><?php echo $LANG['SELECT_CREATOR']; ?></option>
												<option value="">---------------------------------------</option>
												<?php
												foreach($creatorArray as $id => $uname){
													echo "<option value='".$id."' ".($id == $imgArr["creatoruid"]?"SELECTED":"").">";
													echo $uname;
													echo "</option>\n";
												}
												?>
											</select>
											<a href="#" onclick="toggle('imgeditoverride<?php echo $imgId; ?>');return false;" title="<?php echo $LANG['DISPLAY_PHOTOG_OVER']; ?>">
												<img src="../../images/editplus.png" style="border:0px;width:1.5em;" />
											</a>
										</div>
										<div id="imgeditoverride<?php echo $imgId; ?>" style="display:<?php echo ($imgArr["creator"]?'block':'none'); ?>;">
											<b><?php echo $LANG['PHOTOG_OVER']; ?>:</b><br/>
											<input name='photographer' type='text' value="<?php echo $imgArr["creator"]; ?>" style="width:300px;" maxlength='100'>
											* <?php echo $LANG['WILL_OVERRIDE']; ?>
										</div>
										<div>
											<b><?php echo $LANG['NOTES']; ?>:</b><br/>
											<input name="notes" type="text" value="<?php echo $imgArr["notes"]; ?>" style="width:95%;" />
										</div>
										<div>
											<b><?php echo $LANG['COPYRIGHT']; ?>:</b><br/>
											<input name="copyright" type="text" value="<?php echo $imgArr["copyright"]; ?>" style="width:95%;" />
										</div>
										<div>
											<b><?php echo $LANG['SOURCE_WEBPAGE']; ?>:</b><br/>
											<input name="sourceurl" type="text" value="<?php echo $imgArr["sourceurl"]; ?>" style="width:95%;" />
										</div>
										<div>
											<b><?php echo $LANG['WEB_URL']; ?>: </b><br/>
											<input name="url" type="text" value="<?php echo $imgArr["url"]; ?>" style="width:95%;" />
											<?php if(stripos($imgArr['url'], $MEDIA_ROOT_URL) === 0){ ?>
												<div style="margin-left:10px;">
													<input type="checkbox" name="renameweburl" value="1" />
													<?php echo $LANG['RENAME_FILE']; ?>
												</div>
												<input name='old_url' type='hidden' value='<?php echo $imgArr["url"];?>' />
											<?php } ?>
										</div>
										<div>
											<b><?php echo $LANG['LARGE_IMG_URL']; ?>: </b><br/>
											<input name="originalUrl" type="text" value="<?php echo $imgArr["originalUrl"]; ?>" style="width:95%;" />
											<?php if(stripos($imgArr['originalUrl'], $MEDIA_ROOT_URL) === 0){ ?>
												<div style="margin-left:10px;">
													<input type="checkbox" name="renameorigurl" value="1" />
													<?php echo $LANG['RENAME_LARGE']; ?>
												</div>
												<input name='old_originalUrl' type='hidden' value='<?php echo $imgArr["originalUrl"];?>' />
											<?php } ?>
										</div>
										<div>
											<b><?php echo $LANG['THUMB_URL']; ?>: </b><br/>
											<input name="thumbnailUrl" type="text" value="<?php echo $imgArr["thumbnailUrl"]; ?>" style="width:95%;" />
											<?php if($imgArr['thumbnailUrl'] && stripos($imgArr['thumbnailUrl'], $MEDIA_ROOT_URL) === 0){ ?>
												<div style="margin-left:10px;">
													<input type="checkbox" name="renametnurl" value="1" />
													<?php echo $LANG['RENAME_THUMB']; ?>
												</div>
												<input name='old_thumbnailUrl' type='hidden' value='<?php echo $imgArr["thumbnailUrl"];?>' />
											<?php } ?>
										</div>
										<div>
											<b><?php echo $LANG['SORT']; ?>:</b><br/>
											<input name="sortoccurrence" type="text" value="<?php echo $imgArr['sortOccurrence']; ?>" style="width:10%;" />
										</div>
										<div>
										   <b><?php echo $LANG['TAGS']; ?>:</b>
										</div>
											<?php
											foreach($imageTagKeys as $tagKey => $tagDescr){
												echo '<div style="margin-left:10px;">';
												$value = 0;
												if(isset($imgArr['tags'][$tagKey])) $value = 1;
												echo '<input name="ch_'.$tagKey.'" type="checkbox" '.($value?'checked':'').' value="1" /> '.$tagDescr;
												echo '<input name="hidden_'.$tagKey.'" type="hidden" value="'.$value.'" />';
												echo '</div>';
											}
											?>
										<div style="margin-top:10px;">
											<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
											<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" />
											<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
											<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
											<button type="submit" name="submitaction" value="Submit Image Edits"><?php echo $LANG['SUBMIT_IMG_EDITS']; ?></button>
										</div>
									</fieldset>
								</form>
								<form name="img<?php echo $imgId; ?>delform" action="occurrenceeditor.php" method="post" onsubmit="return verifyImgDelForm(this);">
									<fieldset style="padding:15px">
										<legend><b><?php echo $LANG['DEL_IMG']; ?></b></legend>
										<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
										<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" />
										<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
										<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
										<input name="removeimg" type="checkbox" value="1" /> <?php echo $LANG['REM_FROM_SERVER']; ?>
										<div style="margin-left:20px;">
											<?php echo $LANG['RM_DB_NOT_SERVER']; ?>
										</div>
										<div style="margin:10px 20px;">
											<button class="button-danger" type="submit" name="submitaction" value="Delete Image"><?php echo $LANG['DEL_IMG']; ?></button>
										</div>
									</fieldset>
								</form>
								<?php
								if($displayRemapForm = Media::isRemappable($imgArr, $occId)){
									?>
									<form name="img<?php echo $imgId; ?>remapform" action="occurrenceeditor.php" method="post" onsubmit="return verifyImgRemapForm(this);">
										<fieldset style="padding:15px">
											<legend><b><?php echo $LANG['REMAP_TO_ANOTHER']; ?></b></legend>
											<div>
												<b><?php echo $LANG['TARGET_OCCID']; ?>:</b>
												<input id="imgdisplay-<?php echo $imgId; ?>" name="displayoccid" type="text" value="" disabled style="width:70px" />
												<span onclick="openOccurrenceSearch('<?php echo $imgId; ?>');return false"><a href="#"><?php echo $LANG['OPEN_LINK_AID']; ?></a></span>
											</div>
											<div style="margin:10px 20px;">
												<input id="imgoccid-<?php echo $imgId; ?>" name="targetoccid" type="hidden" value="" />
												<input name="occid" type="hidden" value="<?php echo $occId; ?>" />
												<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" />
												<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
												<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
												<button type="submit" name="submitaction" value="Remap Image"><?php echo $LANG['REMAP_IMG']; ?></button>
											</div>
										</fieldset>
									</form>
									<?php
								}
								?>
								<form action="occurrenceeditor.php" method="post">
									<fieldset style="padding:15px">
										<legend><b><?php echo $LANG['LINK_TO_BLANK']; ?></b></legend>
										<div style="margin:10px 20px;">
											<input name="occid" type="hidden" value="<?php echo $occId; ?>" />
											<input name="imgid" type="hidden" value="<?php echo $imgId; ?>" />
											<button name="submitaction" type="submit" value="remapImageToNewRecord"><?php echo $LANG['LINK_TO_NEW']; ?></button>
										</div>
									</fieldset>
								</form>
								<form action="occurrenceeditor.php" method="post">
									<fieldset style="padding:15px">
										<legend><b><?php echo $LANG['DISASSOCIATE_IMG_ALL']; ?></b></legend>
										<div style="margin:10px 20px;">
											<input name="occid" type="hidden" value="<?php echo $occId; ?>" />
											<input name="imgid" type="hidden" value="<?php echo $imgId; ?>" />
											<input name="occindex" type="hidden" value="<?php echo $occIndex; ?>" />
											<input name="csmode" type="hidden" value="<?php echo $crowdSourceMode; ?>" />
											<button name="submitaction" type="submit" value="Disassociate Image"><?php echo $LANG['DISASSOCIATE_IMG']; ?></button>
										</div>
										<div>
											* <?php echo $LANG['IMG_FROM_TAXON']; ?>
										</div>
									</fieldset>
								</form>
							</div>
							<hr/>
						</td>
					</tr>
					<?php
				}
				?>
			</table>
			<?php
		}
		?>
	</div>
</div>
