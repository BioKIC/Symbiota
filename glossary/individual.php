<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GlossaryManager.php');
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/glossary/individual.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/glossary/individual.en.php');
else include_once($SERVER_ROOT.'/content/lang/glossary/individual.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$glossId = array_key_exists('glossid', $_REQUEST) ? filter_var($_REQUEST['glossid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$term = array_key_exists('term', $_REQUEST) ? $_REQUEST['term'] : 0;

$isEditor = false;
if($IS_ADMIN || array_key_exists('GlossaryEditor',$USER_RIGHTS)) $isEditor = true;

$glosManager = new GlossaryManager();
if(!$glossId && $term){
	$glossId = $glosManager->getGlossIdByTerm($term);
}

$termArr = array();
$termImgArr = array();
$redirectStr = '';
if($glossId){
	$glosManager->setGlossId($glossId);
	if($termArr = $glosManager->getTermArr()){
		$synonymArr = $glosManager->getSynonyms();
		if(!$termArr['definition'] && $synonymArr){
			$newID = '';
			foreach($synonymArr as $sID => $sArr){
				$newID = $sID;
				if($sArr['definition']) break;
			}
			if($newID){
				$redirectStr = 'redirected from '.$termArr['term'];
				$glossId = $newID;
				$glosManager->setGlossId($newID);
				$termArr = $glosManager->getTermArr();
				$synonymArr = $glosManager->getSynonyms();
			}
		}
		$termImgArr = $glosManager->getImgArr();
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . $LANG['GLOSS_TERM_INFO']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="../js/symb/glossary.index.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$('#tabs').tabs({
				beforeLoad: function( event, ui ) {
					$(ui.panel).html("<?php echo '<p>'.(isset($LANG['LOADING'])?$LANG['LOADING']:'Loading').'...</p>' ?>");
				}
			});
		});
	</script>
	<style>
		body{ margin-left: auto; margin-right: auto; }
	</style>
</head>

<body style="overflow-x:hidden;overflow-y:auto;width:800px;min-width:800px">
	<?php
	if($termArr){
		$glosManager->remapDescriptionCrossLinks($termArr);
		if(strpos($_SERVER['HTTP_REFERER'], 'individual.php')){
			echo '<div class="navpath"><a href="#" onclick="history.back();">&lt;&lt; ' . $LANG['RETURN_TO_PREVIOUS'] . '</a></div>';
		}
		?>
		<!-- This is inner text! -->
		<h1 class="page-heading"><?= $LANG['GLOSS_TERM_INFO']; ?></h1>
		<div style="width:100%;margin-left:auto;margin-right:auto">
			<div id="tabs" style="padding:10px">
				<div style="clear:both;">
					<?php
					if($isEditor){
						?>
						<div style="float:right;margin-right:15px;" title="Edit Term Data">
							<a href="termdetails.php?glossid=<?php echo $glossId;?>" onclick="self.resizeTo(1250, 900);">
								<img style="border:0px;width:1.3em;" src="../images/edit.png" />
							</a>
						</div>
						<?php
					}
					?>
					<div style="float:left;">
							<?php echo '<span style="font-size:18px;font-weight:bold;">'.$termArr['term'].'</span> '.$redirectStr; ?>
					</div>
				</div>
				<div style="clear:both;width:670px;">
					<div id="terminfo" style="float:left;width:<?php echo ($termImgArr?'380':'670'); ?>px;padding:10px;">
						<div style="clear:both;">
							<div style='' >
								<div style='margin-top:8px;width:95%' >
									<b><?php echo (isset($LANG['DEFINITION'])?$LANG['DEFINITION']:'Definition'); ?>:</b>
									<?php echo $termArr['definition']; ?>
								</div>
								<?php
								if($termArr['author']){
									?>
									<div style='margin-top:8px;' >
										<b><?php echo (isset($LANG['AUTHOR'])?$LANG['AUTHOR']:'Author'); ?>:</b>
										<?php echo $termArr['author']; ?>
									</div>
									<?php
								}
								if($termArr['translator']){
									?>
									<div style='margin-top:8px;' >
										<b><?php echo (isset($LANG['TRANSLATOR'])?$LANG['TRANSLATOR']:'Translator'); ?>:</b>
										<?php echo $termArr['translator']; ?>
									</div>
									<?php
								}
								if($synonymArr){
									echo '<div style="margin-top:8px;" ><b>'.(isset($LANG['SYNS'])?$LANG['SYNS']:'Synonyms').':</b> ';
									$i = 0;
									foreach($synonymArr as $synGlossId => $synArr){
										if($i) echo ', ';
										echo '<a href="individual.php?glossid='.$synGlossId.'">'.$synArr['term'].'</a>';
										$i++;
									}
									echo '</div>';
								}
								$translationArr = $glosManager->getTranslations();
								if($translationArr){
									echo '<div style="margin-top:8px;" ><b>'.(isset($LANG['TRANSS'])?$LANG['TRANSS']:'Translations').':</b> ';
									$i = 0;
									foreach($translationArr as $transGlossId => $transArr){
										if($i) echo ', ';
										echo '<a href="individual.php?glossid='.$transGlossId.'">'.$transArr['term'].'</a> ('.$transArr['language'].')';
										$i++;
									}
									echo '</div>';
								}
								$otherRelationshipsArr = $glosManager->getOtherRelatedTerms();
								if($otherRelationshipsArr){
									echo '<div style="margin-top:8px;" ><b>'.(isset($LANG['OTHER_REL'])?$LANG['OTHER_REL']:'Other Related Terms').':</b> ';
									$delimter = '';
									foreach($otherRelationshipsArr as $relType => $relTypeArr){
										$relStr = '';
										if($relType == 'partOf') $relStr = 'has part';
										elseif($relType == 'hasPart') $relStr = 'part of';
										elseif($relType == 'subClassOf') $relStr = 'superclass or parent term';
										elseif($relType == 'superClassOf') $relStr = 'subclass or child term';
										foreach($relTypeArr as $relGlossId => $relArr){
											echo $delimter.'<a href="individual.php?glossid='.$relGlossId.'">'.$relArr['term'].'</a> ('.$relStr.')';
											$delimter = ', ';
										}
									}
									echo '</div>';
								}
								if($termArr['notes']){
									?>
									<div style='margin-top:8px;' >
										<b><?php echo (isset($LANG['NOTES'])?$LANG['NOTES']:'Notes'); ?>:</b>
										<?php echo $termArr['notes']; ?>
									</div>
									<?php
								}
								if($termArr['resourceurl']){
									$resource = '';
									if(substr($termArr['resourceurl'],0,4)=="http" || substr($termArr['resourceurl'],0,4)=="www."){
										$resource = "<a href='".$termArr['resourceurl']."' target='_blank'>".wordwrap($termArr['resourceurl'],($termImgArr?37:70),'<br />\n',true)."</a>";
									}
									else{
										$resource = $termArr['resourceurl'];
									}
									?>
									<div style='margin-top:8px;' >
										<b><?php echo (isset($LANG['RES_URL'])?$LANG['RES_URL']:'Resource URL'); ?>:</b>
										<?php echo $resource; ?>
									</div>
									<?php
								}
								if($termArr['source']){
									?>
									<div style='margin-top:8px;' >
										<b><?php echo (isset($LANG['SOURCE'])?$LANG['SOURCE']:'Source'); ?>:</b>
										<?php echo $termArr['source']; ?>
									</div>
									<?php
								}
								?>
							</div>
							<div style="clear:both;margin:15px 0px;">
								<b><?php echo (isset($LANG['RELEV_TAXA'])?$LANG['RELEV_TAXA']:'Relevant Taxa'); ?>:</b>
								<?php
								$sourceArr = $glosManager->getTaxonSources();
								$taxaArr = $glosManager->getTermTaxaArr();
								$delimter = '';
								foreach($taxaArr as $tid => $sciname){
									echo $delimter.$sciname;
									if(array_key_exists($tid, $sourceArr)) echo ' [<a href="#" onclick="toggle(\''.$tid.'-sourcesdiv\');return false;"><span style="font-size:90%">show sources</span></a>]';
									$delimter = ', ';
								}
								?>
							</div>
						</div>
					</div>
					<?php
					if($termImgArr){
						?>
						<div id="termimagediv" style="float:right;width:250px;padding:10px;">
							<?php
							foreach($termImgArr as $imgId => $imgArr){
								$imgUrl = $imgArr["url"];
								if(substr($imgUrl,0,1)=="/"){
									if(!empty($GLOBALS['IMAGE_DOMAIN'])){
										$imgUrl = $GLOBALS['IMAGE_DOMAIN'] . $imgUrl;
									}
									else{
										$imgUrl = $glosManager->getDomain() . $imgUrl;
									}
								}
								?>
								<fieldset style='clear:both;border:0px;padding:0px;margin-top:10px;'>
									<div style='width:250px;'>
										<?php
										$imgWidth = 0;
										$imgHeight = 0;
										if($size = getimagesize(str_replace(' ', '%20', $imgUrl))){
											if($size[0] > 240){
												$imgWidth = 240;
												$imgHeight = 0;
											}
											if($size[0] < 245 && $size[1] > 500){
												$imgWidth = 0;
												$imgHeight = 500;
											}
										}
										?>
										<img src='<?php echo $imgUrl; ?>' style="margin:auto;display:block;border:1px;<?php echo ($imgWidth?'width:'.$imgWidth.'px;':'').($imgHeight?'height:'.$imgHeight.'px;':''); ?>" title='<?php echo $imgArr['structures']; ?>'/>
									</div>
									<div style='width:250px;'>
										<?php
										if($imgArr['createdBy']){
											?>
											<div style='overflow:hidden;width:250px;margin-top:2px;font-size:12px;' >
												<?php echo (isset($LANG['IMG_FROM'])?$LANG['IMG_FROM']:'Image courtesy of'); ?>: <?php echo wordwrap($imgArr['createdBy'], 370, "<br />\n"); ?>
											</div>
											<?php
										}
										if($imgArr['structures']){
											?>
											<div style='overflow:hidden;width:250px;margin-top:8px;' >
												<b><?php echo (isset($LANG['STRUCTURES'])?$LANG['STRUCTURES']:'Structures'); ?>:</b>
												<?php echo wordwrap($imgArr["structures"], 370, "<br />\n"); ?>
											</div>
											<?php
										}
										if($imgArr['notes']){
											?>
											<div style='overflow:hidden;width:250px;margin-top:8px;' >
												<b><?php echo (isset($LANG['NOTES'])?$LANG['NOTES']:'Notes'); ?>:</b>
												<?php echo wordwrap($imgArr["notes"], 370, "<br />\n"); ?>
											</div>
											<?php
										}
										?>
									</div>
								</fieldset>
								<?php
							}
							?>
						</div>
						<?php
					}
					foreach($sourceArr as $tid => $arr){
						?>
						<div id="<?php echo $tid; ?>-sourcesdiv" style="display:none;margin-top:20px">
							<fieldset style="margin:10px; padding:10px;background-color:white;">
								<legend><b><?php echo (isset($LANG['CONTR_FOR'])?$LANG['CONTR_FOR']:'Contributors for').' '.$arr['sciname']; ?></b></legend>
								<?php
								if($arr['contributorTerm']){
									?>
									<div style="">
										<?php echo '<b>'.(isset($LANG['TERM_DEF_BY'])?$LANG['TERM_DEF_BY']:'Term and Definition contributed by').':</b>'.$arr['contributorTerm']; ?>
									</div>
									<?php
								}
								if($arr['contributorImage'] && $termImgArr){
									?>
									<div style="margin-top:8px;">
										<?php echo '<b>'.(isset($LANG['IMG_BY'])?$LANG['IMG_BY']:'Image contributed by').':</b>'.$arr['contributorImage']; ?>
									</div>
									<?php
								}
								if($arr['translator'] && $translationArr){
									?>
									<div style="margin-top:8px;">
										<?php echo '<b>'.(isset($LANG['TRANS_BY'])?$LANG['TRANS_BY']:'Translation by').':</b>'.$arr['translator']; ?>
									</div>
									<?php
								}
								if($arr['additionalSources'] && ($translationArr || $termImgArr)){
									?>
									<div style="margin-top:8px;">
										<?php echo '<b>'.(isset($LANG['ALSO_FROM'])?$LANG['ALSO_FROM']:'Translation and/or image were also sourced from the following references').':</b> '.$arr['additionalSources']; ?>
									</div>
									<?php
								}
								?>
							</fieldset>
							<div style="clear:both">&nbsp;</div>
						</div>
						<?php
					}
					?>
				</div>
				<div style="clear:both"></div>
			</div>
		</div>
		<?php
	}
	?>
</body>
</html>