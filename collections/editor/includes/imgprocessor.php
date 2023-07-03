<?php
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/includes/imgprocessor.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/imgprocessor.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/imgprocessor.en.php');
?>

<script>
	$(function() {
		$( "#zoomInfoDialog" ).dialog({
			autoOpen: false,
			position: { my: "left top", at: "right bottom", of: "#zoomInfoDiv" }
		});

		$( "#zoomInfoDiv" ).click(function() {
			$( "#zoomInfoDialog" ).dialog( "open" );
		});
	});

	function floatImgPanel(){
		$( "#labelProcFieldset" ).css('position', 'fixed');
		$( "#labelProcFieldset" ).css('top', '20px');
		var pos = $( "#labelProcDiv" ).position();
		var posLeft = pos.left - $(window).scrollLeft();
		$( "#labelProcFieldset" ).css('left', posLeft);
		$( "#floatImgDiv" ).hide();
		$( "#draggableImgDiv" ).hide();
		$( "#anchorImgDiv" ).show();
	}

	function draggableImgPanel(){
		$( "#labelProcFieldset" ).draggable();
		$( "#labelProcFieldset" ).draggable({ cancel: "#labelprocessingdiv" });
		$( "#labelHeaderDiv" ).css('cursor', 'move');
		$( "#labelProcFieldset" ).css('top', '10px');
		$( "#labelProcFieldset" ).css('left', '5px');
		$( "#floatImgDiv" ).hide();
		$( "#draggableImgDiv" ).hide();
		$( "#anchorImgDiv" ).show();
	}

	function anchorImgPanel(){
		$( "#draggableImgDiv" ).show();
		$( "#floatImgDiv" ).show();
		$( "#anchorImgDiv" ).hide();
		$( "#labelProcFieldset" ).css('position', 'static');
		$( "#labelProcFieldset" ).css('top', '');
		$( "#labelProcFieldset" ).css('left', '');
		try {
			$( "#labelProcFieldset" ).draggable( "destroy" );
			$( "#labelHeaderDiv" ).css('cursor', 'default');
		}
		catch(err) {
		}
	}
</script>
<style>
	.ocr-box{ padding: 5px; float:left; }
	.ocr-box button{ margin: 5px; }
</style>
<div id="labelProcDiv" style="width:100%;height:1050px;position:relative">
	<fieldset id="labelProcFieldset" style="height:95%;background-color:white;">
		<legend><b><?php echo $LANG['LABEL_PROCESSING']; ?></b></legend>
		<div id="labelHeaderDiv" style="margin-top:-10px;height:15px;position:relative">
			<div style="float:left;margin-top:3px;margin-right:15px"><a id="zoomInfoDiv" href="#"><?php echo $LANG['ZOOM']; ?></a></div>
			<div id="zoomInfoDialog">
				<?php echo $LANG['ZOOM_DIRECTIONS']; ?>
			</div>
			<div style="float:left;margin-right:15px">
				<div id="draggableImgDiv" style="float:left" title="<?php echo $LANG['MAKE_DRAGGABLE']; ?>"><a href="#" onclick="draggableImgPanel()"><img src="../../images/draggable.png" style="width:15px" /></a></div>
				<div id="floatImgDiv" style="float:left;margin-left:10px" title="<?php echo $LANG['ALLOW_REMAIN_ACTIVE']; ?>"><a href="#" onclick="floatImgPanel()"><img src="../../images/floatdown.png" style="width:15px" /></a></div>
				<div id="anchorImgDiv" style="float:left;margin-left:10px;display:none" title="<?php echo $LANG['ANCHOR_IMG']; ?>"><a href="#" onclick="anchorImgPanel()"><img src="../../images/anchor.png" style="width:15px" /></a></div>
			</div>
			<div style="float:left;;padding-right:10px;margin:2px 20px 0px 0px;"><?php echo $LANG['ROTATE']; ?>: <a href="#" onclick="rotateImage(-90)">&nbsp;L&nbsp;</a> &lt;&gt; <a href="#" onclick="rotateImage(90)">&nbsp;R&nbsp;</a></div>
			<div style="float:right;padding:0px 3px;margin:0px 3px;"><input id="imgreslg" name="resradio" type="radio" onchange="changeImgRes('lg')" /><?php echo $LANG['HIGH_RES']; ?>.</div>
			<div style="float:right;padding:0px 3px;margin:0px 3px;"><input id="imgresmed" name="resradio"  type="radio" checked onchange="changeImgRes('med')" /><?php echo $LANG['MED_RES']; ?>.</div>
		</div>
		<div id="labelprocessingdiv" style="clear:both;">
			<?php
			$imgCnt = 1;
			foreach($imgArr as $imgCnt => $iArr){
				$iUrl = $iArr['web'];
				$imgId = $iArr['imgid'];
				?>
				<div id="labeldiv-<?php echo $imgCnt; ?>" style="display:<?php echo ($imgCnt==1?'block':'none'); ?>;">
					<div>
						<img id="activeimg-<?php echo $imgCnt; ?>" src="<?php echo $iUrl; ?>" style="width:400px;height:400px" />
					</div>
					<?php
					if(array_key_exists('error', $iArr)){
						echo '<div style="font-weight:bold;color:red">'.$iArr['error'].'</div>';
					}
					?>
					<div style="width:100%;clear:both;">
						<fieldset class="ocr-box">
							<legend>Tesseract OCR</legend>
							<input type="checkbox" id="ocrfull-tess" value="1" /> <?php echo $LANG['OCR_WHOLE_IMG']; ?><br/>
							<input type="checkbox" id="ocrbest" value="1" /> <?php echo $LANG['OCR_ANALYSIS']; ?>
							<div>
								<button value="OCR Image" onclick="ocrImage(this,'tess', <?php echo $imgId.','.$imgCnt; ?>);" ><?php echo $LANG['OCR_IMAGE']; ?></button>
								<img id="workingcircle-tess-<?php echo $imgCnt; ?>" src="../../images/workingcircle.gif" style="display:none;" />
							</div>
						</fieldset>
						<?php
						if(!empty($DIGILEAP_OCR_ACTIVATED)){
							?>
							<fieldset class="ocr-box">
								<legend>DigiLeap OCR</legend>
								<input type="checkbox" id="ocrfull-digi" value="1" /> <?php echo $LANG['OCR_WHOLE_IMG']; ?><br/>
								<div>
									<button value="OCR Image" onclick="ocrImage(this,'digi', <?php echo $imgId.','.$imgCnt; ?>);" ><?php echo $LANG['OCR_IMAGE']; ?></button>
									<img id="workingcircle-digi-<?php echo $imgCnt; ?>" src="../../images/workingcircle.gif" style="display:none;" />
								</div>
							</fieldset>
							<?php
						}
						?>
						<div style="float:right;margin-right:20px;font-weight:bold;">
							<?php echo $LANG['IMAGE'].' '.$imgCnt.' '.$LANG['OF'].' ';
							echo count($imgArr);
							if(count($imgArr)>1){
								echo '<a href="#" onclick="return nextLabelProcessingImage('.($imgCnt+1).');">=&gt;&gt;</a>';
							}
							?>
						</div>
					</div>
					<div style="width:100%;clear:both;">
						<?php
						$fArr = array();
						if(array_key_exists($imgId,$fragArr)){
							$fArr = $fragArr[$imgId];
						}
						?>
						<div id="tfadddiv-<?php echo $imgCnt; ?>" style="display:none;">
							<form id="ocraddform-<?php echo $imgCnt; ?>" name="ocraddform-<?php echo $imgId; ?>" method="post" action="occurrenceeditor.php">
								<div>
									<textarea name="rawtext" rows="12" cols="48" style="width:97%;background-color:#F8F8F8;"></textarea>
								</div>
								<div title="OCR Notes">
									<b><?php echo $LANG['NOTES']; ?>:</b>
									<input name="rawnotes" type="text" value="" style="width:97%;" />
								</div>
								<div title="OCR Source">
									<b><?php echo $LANG['SOURCE']; ?>:</b>
									<input name="rawsource" type="text" value="" style="width:97%;" />
								</div>
								<div style="float:left">
									<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" />
									<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
									<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
									<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
									<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
									<button name="submitaction" type="submit" value="Save OCR" ><?php echo $LANG['SAVE_OCR']; ?></button>
								</div>
							</form>
							<div style="font-weight:bold;float:right;"><?php echo '&lt;'.$LANG['NEW'].'&gt; '.$LANG['OF'].' '.count($fArr); ?></div>
						</div>
						<div id="tfeditdiv-<?php echo $imgCnt; ?>" style="clear:both;">
							<?php
							if(array_key_exists($imgId,$fragArr)){
								$fragCnt = 1;
								$targetPrlid = '';
								if(isset($newPrlid) && $newPrlid) $targetPrlid = $newPrlid;
								if(array_key_exists('editprlid',$_REQUEST)) $targetPrlid = $_REQUEST['editprlid'];
								foreach($fArr as $prlid => $rArr){
									$displayBlock = 'none';
									if($targetPrlid){
										if($prlid == $targetPrlid){
											$displayBlock = 'block';
										}
									}
									elseif($fragCnt==1){
										$displayBlock = 'block';
									}
									?>
									<div id="tfdiv-<?php echo $imgCnt.'-'.$fragCnt; ?>" style="display:<?php echo $displayBlock; ?>;">
										<form id="tfeditform-<?php echo $prlid; ?>" name="tfeditform-<?php echo $prlid; ?>" method="post" action="occurrenceeditor.php">
											<div>
												<textarea name="rawtext" rows="12" cols="48" style="width:97%"><?php echo $rArr['raw']; ?></textarea>
											</div>
											<div title="OCR Notes">
												<b><?php echo $LANG['NOTES']; ?>:</b>
												<input name="rawnotes" type="text" value="<?php echo $rArr['notes']; ?>" style="width:97%;" />
											</div>
											<div title="OCR Source">
												<b><?php echo $LANG['SOURCE']; ?>:</b>
												<input name="rawsource" type="text" value="<?php echo $rArr['source']; ?>" style="width:97%;" />
											</div>
											<div style="float:left;margin-left:10px;">
												<input type="hidden" name="editprlid" value="<?php echo $prlid; ?>" />
												<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
												<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
												<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
												<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
												<button name="submitaction" type="submit" value="Save OCR Edits" ><?php echo $LANG['SAVE_OCR_EDITS']; ?></button>
											</div>
											<div style="float:left;margin-left:20px;">
												<input type="hidden" name="iurl" value="<?php echo $iUrl; ?>" />
												<input type="hidden" id="cnumber" name="cnumber" value="<?php echo array_key_exists('catalognumber',$occArr)?$occArr['catalognumber']:''; ?>" />
												<?php
												if(isset($NLP_SALIX_ACTIVATED) && $NLP_SALIX_ACTIVATED){
													echo '<input name="salixocr" type="button" value="SALIX Parser" onclick="nlpSalix(this,'.$prlid.')" />';
													echo '<img id="workingcircle_salix-'.$prlid.'" src="../../images/workingcircle.gif" style="display:none;" />';
												}
												if(isset($NLP_LBCC_ACTIVATED) && $NLP_LBCC_ACTIVATED){
													echo '<input id="nlplbccbutton" name="nlplbccbutton" type="button" value="LBCC Parser" onclick="nlpLbcc(this,'.$prlid.')" />';
													echo '<img id="workingcircle_lbcc-'.$prlid.'" src="../../images/workingcircle.gif" style="display:none;" />';
												}
												?>
											</div>
										</form>
										<div style="float:right;font-weight:bold;margin-right:20px;">
											<?php
											echo $fragCnt.' of '.count($fArr);
											if(count($fArr) > 1){
												?>
												<a href="#" onclick="return nextRawText(<?php echo $imgCnt.','.($fragCnt+1); ?>)">=&gt;&gt;</a>
												<?php
											}
											?>
										</div>
										<div style="clear:both;">
											<form name="tfdelform-<?php echo $prlid; ?>" method="post" action="occurrenceeditor.php" style="margin-left:10px;width:100px;" >
												<input type="hidden" name="delprlid" value="<?php echo $prlid; ?>" />
												<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
												<input type="hidden" name="occid" value="<?php echo $occId; ?>" /><br/>
												<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
												<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
												<button name="submitaction" type="submit" value="Delete OCR" ><?php echo $LANG['DELETE_OCR']; ?></button>
											</form>
										</div>
									</div>
									<?php
									$fragCnt++;
								}
							}
							?>
						</div>
					</div>
				</div>
				<?php
				$imgCnt++;
			}
			?>
		</div>
	</fieldset>
</div>