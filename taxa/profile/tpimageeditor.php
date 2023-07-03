<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TPImageEditorManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/taxa/profile/tpimageeditor.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/taxa/profile/tpimageeditor.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/taxa/profile/tpimageeditor.en.php');
header('Content-Type: text/html; charset='.$CHARSET);

$tid = $_REQUEST['tid'];
$category = array_key_exists('cat',$_REQUEST)?$_REQUEST['cat']:'';

$imageEditor = new TPImageEditorManager();
$isEditor = false;

if($tid){
	$imageEditor->setTid($tid);
	if($IS_ADMIN || array_key_exists('TaxonProfile',$USER_RIGHTS)) $isEditor = true;
}
?>
<!-- This is inner text! -->
<div id="innertext" style="background-color:white;">
	<?php
	if($isEditor && $tid){
		if($category == "imagequicksort"){
			if($images = $imageEditor->getImages()){
				?>
				<div style='clear:both;'>
					<form action='tpeditor.php' method='post' target='_self'>
						<table border='0' cellspacing='0'>
							<tr>
								<?php
								$imgCnt = 0;
								foreach($images as $imgArr){
									$tnUrl = $imgArr["thumbnailurl"];
									if($tnUrl && substr($tnUrl,0,10) != 'processing'){
										$webUrl = $imgArr["url"];
										if($GLOBALS["imageDomain"]){
											if(substr($imgArr["url"],0,1)=="/") $webUrl = $GLOBALS["imageDomain"].$imgArr["url"];
											if(substr($imgArr["thumbnailurl"],0,1)=="/") $tnUrl = $GLOBALS["imageDomain"].$imgArr["thumbnailurl"];
										}
										?>
										<td align='center' valign='bottom'>
											<div style='margin:20px 0px 0px 0px;'>
												<a href="<?php echo $webUrl; ?>" target="_blank">
													<img width="150" src="<?php echo $tnUrl;?>" />
												</a>

											</div>
											<?php
											if($imgArr["photographerdisplay"]){
												?>
												<div>
													<?php echo $imgArr["photographerdisplay"];?>
												</div>
												<?php
											}
											if($imgArr["tid"] != $tid){
												?>
												<div>
													<a href="tpeditor.php?tid=<?php echo $imgArr["tid"];?>" target="" title="Linked from"><?php echo $imgArr["sciname"];?></a>
												</div>
												<?php
											}
											?>
											<div style='margin-top:2px;'>
												<?php echo $LANG['SORT_SEQUENCE'].': '.'<b>'.$imgArr["sortsequence"].'</b>'; ?>
											</div>
											<div>
												<?php echo $LANG['NEW_VALUE']; ?>:
												<input name="imgid-<?php echo $imgArr["imgid"];?>" type="text" size="5" maxlength="5" />
											</div>
										</td>
										<?php
										$imgCnt++;
										if($imgCnt%5 == 0){
											?>
											</tr>
											<tr>
												<td colspan='5'>
													<hr>
													<div style='margin-top:2px;'>
														<button type='submit' name='action' id='submit' value='Submit Image Sort Edits'><?php echo $LANG['SUBMIT_SORT_EDITS']; ?></button>
													</div>
												</td>
											</tr>
											<tr>
											<?php
										}
									}
								}
								for($i = (5 - $imgCnt%5);$i > 0; $i--){
									echo "<td>&nbsp;</td>";
								}
								?>
							</tr>
						</table>
						<input name='tid' type='hidden' value='<?php echo $imageEditor->getTid(); ?>'>
						<input type="hidden" name="tabindex" value="2" />
						<?php
						if($imgCnt%5 != 0) echo "<div style='margin-top:2px;'><button type='submit' name='action' id='imgsortsubmit' value='Submit Image Sort Edits'>".$LANG['SUBMIT_SORT_EDITS']."</button></div>\n";
						?>
					</form>
				</div>
				<?php
			}
			else{
				echo '<h2>'.$LANG['NO_IMAGES'].'.</h2>';
			}
		}
		elseif($category == "imageadd"){
			?>
			<div style='clear:both;'>
				<form enctype='multipart/form-data' action='tpeditor.php' id='imageaddform' method='post' target='_self' onsubmit='return submitAddImageForm(this);'>
					<fieldset style='margin:15px;padding:15px;width:90%;'>
				    	<legend><b>Add a New Image</b></legend>
						<div style='padding:10px;border:1px solid #c2c2c2;background-color:#f7f7f7;'>
							<div class="targetdiv" style="display:block;">
								<div style="font-weight:bold;margin-bottom:5px;">
									<?php echo $LANG['SELECT_IMAGE_TO_UPLOAD']; ?>:
								</div>
						    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
								<input type='hidden' name='MAX_FILE_SIZE' value='4000000' />
								<div>
									<input name='imgfile' id='imgfile' type='file' size='70'/>
								</div>
								<div style="margin-left:10px;">
									<input type="checkbox" name="createlargeimg" value="1" /> <?php echo $LANG['KEEP_LARGE_IMG']; ?>
								</div>
								<div style="margin-left:10px;"><?php echo $LANG['IMG_SIZE_NO_GREATER']; ?></div>
								<div style="margin:10px 0px 0px 350px;cursor:pointer;text-decoration:underline;font-weight:bold;" onclick="toggle('targetdiv')">
									<?php echo $LANG['LINK_TO_EXTERNAL']; ?>
								</div>
							</div>
							<div class="targetdiv" style="display:none;">
								<div style="font-weight:bold;margin-bottom:5px;">
									<?php echo $LANG['ENTER_URL_IMG']; ?>:
								</div>
								<div>
									URL:
									<input type='text' name='filepath' size='70'/>
								</div>
								<div style="margin-left:10px;">
									<input type="checkbox" name="importurl" value="1" /> <?php echo $LANG['IMPORT_IMG_LOCAL']; ?>
								</div>
								<div style="margin:10px 0px 0px 350px;cursor:pointer;text-decoration:underline;font-weight:bold;" onclick="toggle('targetdiv')">
									<?php echo $LANG['UPLOAD_LOCAL']; ?>
								</div>
							</div>
						</div>

						<!-- Image metadata -->
				    	<div style='margin-top:2px;'>
				    		<b><?php echo $LANG['CAPTION']; ?>:</b>
							<input name='caption' type='text' value='' size='25' maxlength='100'>
						</div>
						<div style='margin-top:2px;'>
							<b><?php echo $LANG['PHOTOGRAPHER']; ?>:</b>
							<select name='photographeruid' name='photographeruid'>
								<option value=""><?php echo $LANG['SEL_PHOTOGRAPHER']; ?></option>
								<option value="">---------------------------------------</option>
								<?php $imageEditor->echoPhotographerSelect($PARAMS_ARR["uid"]); ?>
							</select>
							<a href="#" onclick="toggle('photooveridediv');return false;" title="<?php echo $LANG['DISP_PHOTOGRAPHER_OVERRIDE']; ?>">
								<img src="../../images/editplus.png" style="border:0px;width:12px;" />
							</a>
						</div>
						<div id="photooveridediv" style='margin:2px 0px 5px 10px;display:none;'>
							<b><?php echo $LANG['PHOTOGRAPHER_OVERRIDE']; ?>:</b>
							<input name='photographer' type='text' value='' size='37' maxlength='100'><br/>
							* <?php echo $LANG['PHOTOGRAPHER_OVERRIDE_EXPLAIN']; ?>
						</div>
						<div style="margin-top:2px;" title="Use if manager is different than photographer">
							<b><?php echo $LANG['MANAGER']; ?>:</b>
							<input name='owner' type='text' value='' size='35' maxlength='100'>
						</div>
						<div style='margin-top:2px;' title="<?php echo $LANG['URL_TO_SOURCE']; ?>">
							<b><?php echo $LANG['SOURCE_URL']; ?>:</b>
							<input name='sourceurl' type='text' value='' size='70' maxlength='250'>
						</div>
						<div style='margin-top:2px;'>
							<b><?php echo $LANG['COPYRIGHT']; ?>:</b>
							<input name='copyright' type='text' value='' size='70' maxlength='250'>
						</div>
						<div style='margin-top:2px;'>
							<b><?php echo $LANG['OCC_REC_NUM']; ?>:</b>
							<input id="occidadd" name="occid" type="text" value=""/>
							<span style="cursor:pointer;color:blue;"  onclick="openOccurrenceSearch('occidadd')"><?php echo $LANG['LINK_TO_OCC']; ?></span>
						</div>
						<div style='margin-top:2px;'>
							<b><?php echo $LANG['LOCALITY']; ?>:</b>
							<input name='locality' type='text' value='' size='70' maxlength='250'>
						</div>
						<div style='margin-top:2px;'>
							<b><?php echo $LANG['NOTES']; ?>:</b>
							<input name='notes' type='text' value='' size='70' maxlength='250'>
						</div>
						<div style='margin-top:2px;'>
							<b><?php echo $LANG['SORT_SEQUENCE']; ?>:</b>
							<input name='sortsequence' type='text' value='' size='5' maxlength='5'>
						</div>
						<input name="tid" type="hidden" value="<?php echo $imageEditor->getTid();?>">
						<input type="hidden" name="tabindex" value="1" />
						<div style='margin-top:2px;'>
							<button type='submit' name='action' id='imgaddsubmit' value='Upload Image'><?php echo $LANG['UPLOAD_IMAGE']; ?></button>
						</div>
					</fieldset>
				</form>
			</div>
			<?php
		}
		else{
			if($images = $imageEditor->getImages()){
				?>
				<div style='clear:both;'>
					<table>
						<?php
						foreach($images as $imgArr){
							?>
							<tr><td>
								<div style="margin:20px;float:left;text-align:center;">
									<?php
									$webUrl = $imgArr["url"];
									$tnUrl = $imgArr["thumbnailurl"];
									if($GLOBALS['imageDomain']){
										if(substr($imgArr["url"],0,1) == "/") $webUrl = $GLOBALS["imageDomain"].$imgArr["url"];
										if(substr($imgArr["thumbnailurl"],0,1) == "/") $tnUrl = $GLOBALS["imageDomain"].$imgArr["thumbnailurl"];
									}
									if(!$tnUrl) $tnUrl = $webUrl;
									?>
									<a href="../../imagelib/imgdetails.php?imgid=<?php echo $imgArr['imgid']; ?>">
										<img src="<?php echo $tnUrl;?>" style="width:200px;"/>
									</a>
									<?php
									if($imgArr["originalurl"]){
										$origUrl = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["originalurl"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["originalurl"];
										?>
										<br /><a href="<?php echo $origUrl;?>"><?php echo $LANG['OPEN_LARGE_IMAGE']; ?></a>
										<?php
									}
									?>
								</div>
							</td>
							<td valign="middle" style="width:90%">
								<?php
								if($imgArr['occid']){
									?>
									<div style="float:right;margin-right:10px;" title="<?php echo $LANG['MUST_HAVE_EDIT_PERM']; ?>">
										<a href="../../collections/editor/occurrenceeditor.php?occid=<?php echo $imgArr['occid']; ?>&tabtarget=2" target="_blank">
											<img src="../../images/edit.png" style="border:0px;"/>
										</a>
									</div>
									<?php
								}
								else{
									?>
									<div style='float:right;margin-right:10px;'>
										<a href="../../imagelib/imgdetails.php?imgid=<?php echo $imgArr["imgid"];?>&emode=1">
											<img src="../../images/edit.png" style="border:0px;" />
										</a>
									</div>
									<?php
								}
								?>
								<div style='margin:60px 0px 10px 10px;clear:both;'>
									<?php
									if($imgArr["tid"] != $tid){
										?>
										<div>
											<b><?php echo $LANG['IMAGE_LINKED_FROM']; ?>:</b>
											<a href="tpeditor.php?tid=<?php echo $imgArr["tid"];?>" target=""><?php echo $imgArr["sciname"];?></a>
										</div>
										<?php
									}
									if($imgArr["caption"]){
										?>
										<div>
											<b><?php echo $LANG['CAPTION']; ?>:</b>
											<?php echo $imgArr["caption"];?>
										</div>
										<?php
									}
									?>
									<div>
										<b><?php echo $LANG['PHOTOGRAPHER']; ?>:</b>
										<?php echo $imgArr["photographerdisplay"];?>
									</div>
									<?php
									if($imgArr["owner"]){
										?>
										<div>
											<b><?php echo $LANG['MANAGER']; ?>:</b>
											<?php echo $imgArr["owner"];?>
										</div>
										<?php
									}
									if($imgArr["sourceurl"]){
										?>
										<div>
											<b><?php echo $LANG['SOURCE_URL']; ?>:</b>
											<a href="<?php echo $imgArr["sourceurl"];?>" target="_blank"><?php echo $imgArr["sourceurl"]; ?></a>
										</div>
										<?php
									}
									if($imgArr["copyright"]){
										?>
										<div>
											<b><?php echo $LANG['COPYRIGHT']; ?>:</b>
											<?php echo $imgArr["copyright"];?>
										</div>
										<?php
									}
									if($imgArr["locality"]){
										?>
										<div>
											<b><?php echo $LANG['LOCALITY']; ?>:</b>
											<?php echo $imgArr["locality"];?>
										</div>
										<?php
									}
									if($imgArr["occid"]){
										?>
										<div>
											<b><?php echo $LANG['OCC_REC_NUM']; ?>:</b>
											<a href="<?php echo $CLIENT_ROOT;?>/collections/individual/index.php?occid=<?php echo $imgArr["occid"]; ?>">
												<?php echo $imgArr["occid"];?>
											</a>
										</div>
										<?php
									}
									if($imgArr["notes"]){
										?>
										<div>
											<b><?php echo $LANG['NOTES']; ?>:</b>
											<?php echo $imgArr["notes"];?>
										</div>
										<?php
									}
									?>
									<div>
										<b><?php echo $LANG['SORT_SEQUENCE']; ?>:</b>
										<?php echo $imgArr["sortsequence"];?>
									</div>
								</div>

							</td></tr>
							<tr><td colspan='2'>
								<div style='margin:10px 0px 0px 0px;clear:both;'>
									<hr />
								</div>
							</td></tr>
							<?php
						}
						?>
					</table>
				</div>
				<?php
			}
			else{
				echo '<h2>'.$LANG['NO_IMAGES'].'</h2>';
			}
		}
	}
	?>
</div>