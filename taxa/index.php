<?php
include_once('../config/symbini.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/taxa/index.' . $LANG_TAG . '.php'))
include_once($SERVER_ROOT.'/content/lang/taxa/index.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/taxa/index.en.php');
include_once($SERVER_ROOT.'/classes/TaxonProfile.php');
Header('Content-Type: text/html; charset='.$CHARSET);

$taxonValue = array_key_exists('taxon', $_REQUEST) ? $_REQUEST['taxon'] : '';
$tid = array_key_exists('tid', $_REQUEST) ? $_REQUEST['tid'] : '';
$taxAuthId = array_key_exists('taxauthid', $_REQUEST) ? $_REQUEST['taxauthid'] : 1;
$clid = array_key_exists('clid', $_REQUEST) ? $_REQUEST['clid'] : 0;
$pid = array_key_exists('pid', $_REQUEST) ? $_REQUEST['pid'] : '';
$lang = array_key_exists('lang', $_REQUEST) ? $_REQUEST['lang']: $DEFAULT_LANG;
$taxaLimit = array_key_exists('taxalimit', $_REQUEST) ? $_REQUEST['taxalimit'] : 50;
$page = array_key_exists('page', $_REQUEST) ? $_REQUEST['page'] : 0;

$taxonManager = new TaxonProfile();

//Sanitation
if(!is_string($taxonValue)) $taxonValue = '';
$taxonValue = preg_replace('/[^a-zA-Z0-9\-\s.†×]/', '', $taxonValue);
$taxonValue = htmlspecialchars($taxonValue, ENT_QUOTES, 'UTF-8');
$tid = $taxonManager->sanitizeInt($tid);
$taxAuthId = $taxonManager->sanitizeInt($taxAuthId);
$clid = $taxonManager->sanitizeInt($clid);
$pid = $taxonManager->sanitizeInt($pid);
$lang = htmlspecialchars($lang, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$taxaLimit = $taxonManager->sanitizeInt($taxaLimit);
$page = $taxonManager->sanitizeInt($page);

if($taxAuthId) $taxonManager->setTaxAuthId($taxAuthId);
if($tid) $taxonManager->setTid($tid);
elseif($taxonValue){
	$tidArr = $taxonManager->taxonSearch($taxonValue);
	$tid = key($tidArr);
	//Need to add code that allows user to select target taxon when more than one homonym is returned
}

$taxonManager->setLanguage($lang);
if($pid === '' && isset($DEFAULT_PROJ_ID) && $DEFAULT_PROJ_ID) $pid = $DEFAULT_PROJ_ID;

if($redirect = $taxonManager->getRedirectLink()){
	header('Location: '.$redirect);
	exit;
}

$isEditor = false;
if($SYMB_UID){
	if($IS_ADMIN || array_key_exists('TaxonProfile',$USER_RIGHTS)){
		$isEditor = true;
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . " - " . $taxonManager->getTaxonName(); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/taxa/index.css" type="text/css" rel="stylesheet" />
	<link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/taxa/traitplot.css" type="text/css" rel="stylesheet" >
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../js/symb/taxa.index.js?ver=202101" type="text/javascript"></script>
	<script src="../js/symb/taxa.editor.js?ver=202101" type="text/javascript"></script>
	<style type="text/css">
		.resource-title{ font-weight: bold; }
	</style>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div id="popup-innertext">
	<h1 class="page-heading"><?= $taxonManager->getTaxonName() ?></h1>
	<?php
	if($taxonManager->getTaxonName()){
		if(count($taxonManager->getAcceptedArr()) == 1){
			$taxonRank = $taxonManager->getRankId();
			if($taxonRank > 180){
				?>
				<table id="innertable">
				<tr>
					<td colspan="2" valign="bottom">
						<?php
						if($isEditor){
							?>
							<div id="editorDiv">
								<?php
								echo '<a href="profile/tpeditor.php?tid=' . htmlspecialchars($taxonManager->getTid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" title="' . htmlspecialchars($LANG['EDIT_TAXON_DATA'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
								echo '<img class="navIcon" src="../images/edit.png" style="width:1.3em" />';
								echo '</a>';
								?>
							</div>
							<?php
						}
						?>
						<div id="scinameDiv">
							<?php echo '<span id="'.($taxonManager->getRankId() > 179?'sciname':'taxon').'">'.$taxonManager->getTaxonName().'</span>'; ?>
							<span id="author"><?php echo $taxonManager->getTaxonAuthor(); ?></span>
							<?php
							$parentLink = 'index.php?tid='.$taxonManager->getParentTid().'&clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&taxauthid='.$taxAuthId;
							echo '&nbsp;<a href="' . htmlspecialchars($parentLink, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><img class="navIcon" src="../images/toparent.png" style="width:1.3em" title="' . $LANG['GO_TO_PARENT'] . '" /></a>';
							if($taxonManager->isForwarded()){
						 		echo '<span id="redirectedfrom"> (' . $LANG['REDIRECT'] . ': <i>' . $taxonManager->getSubmittedValue('sciname') . '</i> ' . $taxonManager->getSubmittedValue('author') . ')</span>';
						 	}
						 	?>
						</div>
						<?php
						if($linkArr = $taxonManager->getLinkArr()){
							?>
							<div id="linkDiv">
								<?php
								foreach($linkArr as $linkObj){
									if($linkObj['icon']) echo '<span title="' . htmlspecialchars($linkObj['title'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><a href="' . htmlspecialchars($linkObj['url'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank"><img src="' . htmlspecialchars($linkObj['icon'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" /></a></span>';
								}
								?>
							</div>
							<?php
						}
						?>
					</td>
				</tr>
				<tr>
					<td width="300" valign="top">
						<div id="family"><?php echo '<b>' . $LANG['FAMILY'] . ':</b> ' . $taxonManager->getTaxonFamily(); ?></div>
						<?php
						if($vernArr = $taxonManager->getVernaculars()){
							$primerArr = array();
							$targetLang = $lang;
							if(!array_key_exists($targetLang, $vernArr)) $targetLang = 'en';
							if(array_key_exists($targetLang, $vernArr)){
								$primerArr = $vernArr[$targetLang];
								unset($vernArr[$targetLang]);
							}
							else $primerArr = array_shift($vernArr);
							$vernStr = array_shift($primerArr);
							if($primerArr || $vernArr){
								$vernStr.= ', <span class="verns"><a href="#" onclick="toggle(\'verns\')" title="' . $LANG['CLICK_TO_SHOW_COMMONS'] . '">' . $LANG['MORE'] . '...</a></span>';
								$vernStr.= '<span class="verns" onclick="toggle(\'verns\');" style="display:none;">';
								$vernStr.= implode(', ',$primerArr) . ' ';
								foreach($vernArr as $langName => $vArr){
									$vernStr.= '(' . $langName . ': ' . implode(', ',$vArr) . '), ';
								}
								$vernStr = trim($vernStr,', ').'</span>';
							}
							?>
							<div id="vernacularDiv">
								<?php echo $vernStr; ?>
							</div>
							<?php
						}
						if($synArr = $taxonManager->getSynonymArr()){
							$primerArr = array_shift($synArr);
							$synStr = '<i>' . $primerArr['sciname'] . '</i>' . (isset($primerArr['author']) && $primerArr['author'] ? ' ' . $primerArr['author'] : '');
							if($synArr){
								$synStr .= ', <span class="synSpan"><a href="#" onclick="toggle(\'synSpan\')" title="' . $LANG['CLICK_VIEW_MORE_SYNS'] . '">' . $LANG['MORE'] . '</a></span>';
								$synStr .= '<span class="synSpan" onclick="toggle(\'synSpan\')" style="display:none">';
								foreach($synArr as $synKey => $sArr){
									$synStr .= '<i>' . $sArr['sciname'] . '</i> ' . $sArr['author'] . ', ';
								}
								$synStr = trim($synStr,', ') . '</span>';
							}
							echo '<div id="synonymDiv" title="' . $LANG['SYNONYMS'] . '">[';
							echo $synStr;
							echo ']</div>';
						}

						if(!$taxonManager->echoImages(0,1,0)){
							echo '<div class="image" style="width:260px;height:260px;border-style:solid;margin-top:5px;margin-left:20px;text-align:center;">';
							if($isEditor){
								echo '<a href="profile/tpeditor.php?category=imageadd&tid=' . htmlspecialchars($taxonManager->getTid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><b>' . htmlspecialchars($LANG['ADD_IMAGE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</b></a>';
							}
							else{
								echo $LANG['IMAGE_NOT_AVAILABLE'];
							}
							echo '</div>';
						}
						?>
					</td>
					<td class="desc">
						<?php
						echo $taxonManager->getDescriptionTabs();
						?>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<div id="img-div" style="height:300px;overflow:hidden;">
							<?php
							//Map
							$aUrl = ''; $gAnchor = '';
							$url = '';
							if(isset($MAP_THUMBNAILS) && $MAP_THUMBNAILS) $url = $taxonManager->getGoogleStaticMap();
							else $url = $CLIENT_ROOT.'/images/mappoint.png';
							if($occurrenceModIsActive && $taxonManager->getDisplayLocality()){
								$gAnchor = "openMapPopup('" . $taxonManager->getTid() . "'," . $clid . ','. (!empty($GOOGLE_MAP_KEY) ? 'false' : 'true') . ")";
							}
							if($mapSrc = $taxonManager->getMapArr()){
								$url = array_shift($mapSrc);
								$aUrl = $url;
							}
							if($url){
								echo '<div class="mapthumb">';
								if($gAnchor){
									echo '<a href="#" onclick="' . $gAnchor . ';return false">';
								}
								elseif($aUrl){
									echo '<a href="' . htmlspecialchars($aUrl, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
								}
								echo '<img src="' . $url . '" title="' . $taxonManager->getTaxonName() . '" alt="' . $taxonManager->getTaxonName() . '" />';
								if($aUrl || $gAnchor) echo '</a>';
								if($gAnchor) echo '<br /><a href="#" onclick="'.$gAnchor.';return false">' . $LANG['OPEN_MAP'] . '</a>';
								echo "</div>";
							}
							$taxonManager->echoImages(1);
							?>
						</div>
						<?php
						$imgCnt = $taxonManager->getImageCount();
						$tabText = $LANG['TOTAL_IMAGES'];
						if($imgCnt == 100){
							$tabText = $LANG['INITIAL_IMAGES'] . '<br/>- - - - -<br/>';
							$tabText .= '<a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/imagelib/search.php?submitaction=search&taxa=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($LANG['VIEW_ALL_IMAGES'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
						}
						?>
						<div id="img-tab-div" style="display:<?php echo $imgCnt > 6?'block':'none';?>;border-top:2px solid gray;margin-top:2px;">
							<div style="background:#eee;padding:10px;border: 1px solid #ccc;width:110px;margin:auto;text-align:center">
								<a href="#" onclick="expandExtraImages();return false;">
									<?php echo $LANG['CLICK_TO_DISPLAY'] . '<br/>' . $imgCnt . ' ' . $tabText; ?>
								</a>
							</div>
						</div>
					</td>
				</tr>
				</table>
				<?php
			}
			else{
				?>
				<table id="innertable">
				<tr>
					<td colspan="2" style="vertical-align:top;">
						<?php
						if($isEditor){
							?>
							<div id="editorDiv">
								<a href="profile/tpeditor.php?tid=<?php echo htmlspecialchars($taxonManager->getTid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" title="<?php echo htmlspecialchars($LANG['EDIT_TAXON_DATA'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
									<img class="navIcon" src='../images/edit.png' style='width:1.3em' />
								</a>
							</div>
							<?php
						}
						?>
						<div id="scinameDiv">
							<?php
							$displayName = $taxonManager->getTaxonName();
							if($taxonRank > 140){
								$parentLink = "index.php?tid=" . $taxonManager->getParentTid() . "&clid=" . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&pid=".$pid."&taxauthid=".$taxAuthId;
								$displayName .= ' <a href="' . htmlspecialchars($parentLink, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
								$displayName .= '<img class="navIcon" src="../images/toparent.png" style="width:1.3em" title="' . $LANG['GO_TO_PARENT'] . '" />';
								$displayName .= '</a>';
							}
							echo '<div id="taxon">' . $displayName . '</div>';
							?>
						</div>
					</td>
				</tr>
				<tr>
					<td width="300" valign="top">
						<?php
						if($taxonRank > 140) echo '<div id="family"><b>' . $LANG['FAMILY'] . ':</b> ' . $taxonManager->getTaxonFamily() . '</div>';
						if(!$taxonManager->echoImages(0,1,0)){
							echo "<div class='image' style='width:260px;height:260px;border-style:solid;margin-top:5px;margin-left:20px;text-align:center;'>";
							if($isEditor){
								echo '<a href="profile/tpeditor.php?category=imageadd&tid=' . htmlspecialchars($taxonManager->getTid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><b>' . htmlspecialchars($LANG['ADD_IMAGE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</b></a>';
							}
							else{
								echo $LANG['IMAGE_NOT_AVAILABLE'];
							}
							echo '</div>';
						}
						?>
					</td>
					<td class="desc">
						<?php
						echo $taxonManager->getDescriptionTabs();
						?>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<?php
						if($sppArr = $taxonManager->getSppArray($page, $taxaLimit, $pid, $clid)){
							?>
							<fieldset style="padding:10px 2px 10px 2px;">
								<?php
								$legendStr = '';
								if($clid){
									if($checklistName = $taxonManager->getClName($clid)){
										$legendStr .= $LANG['SPECIES_CHECKLIST'] . ': <b>' . $checklistName . '</b>';
									}
									if($parentChecklistArr = $taxonManager->getParentChecklist($clid)){
										$titleStr = $LANG['GO_TO_PARENT_CHECKLIST'] . ': ' . current($parentChecklistArr);
										$legendStr .= ' <a href="index.php?tid=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&clid='. htmlspecialchars(key($parentChecklistArr), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&taxauthid=' . htmlspecialchars($taxAuthId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" title="' . htmlspecialchars($titleStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
										$legendStr .= '<img style="border:0px;width:1.3em;" src="../images/toparent.png"/>';
										$legendStr .= '</a>';
									}
									elseif($pid){
										$projName = $taxonManager->getProjName($pid);
										if($projName) $titleStr = $LANG['WITHIN_INVENTORY'] . ': ' . $projName;
										else $titleStr = $LANG['SHOW_ALL_TAXA'];
										$legendStr .= ' <a href="index.php?tid=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&clid=0&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&taxauthid=' . htmlspecialchars($taxAuthId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" title="' . htmlspecialchars($titleStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
										$legendStr .= '<img style="border:0px;width:1.3em;" src="../images/toparent.png"/>';
										$legendStr .= '</a>';
									}
								}
								elseif($pid){
									$projName = $taxonManager->getProjName($pid);
									if($projName) $legendStr .= $LANG['WITHIN_INVENTORY'] . ': <b>' . $projName . '</b>';
									else $legendStr = $LANG['SHOW_ALL_TAXA'];
									$titleStr = $LANG['SHOW_ALL_TAXA'];
									$legendStr .= ' <a href="index.php?tid=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&clid=0&pid=0&taxauthid=' . htmlspecialchars($taxAuthId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" title="' . htmlspecialchars($titleStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
									$legendStr .= '<img style="border:0px;width:1.3em;" src="../images/toparent.png"/>';
									$legendStr .= '</a>';
								}
								if($legendStr){
									$legendStr = '<span style="margin:0px 10px">'.$legendStr.'</span>';
								}

								$taxonCnt = count($sppArr);
								if($taxonCnt > $taxaLimit || $page){
									$navStr = '<span style="margin:0px 10px">';
									$dynLink = 'tid='.$tid.'&taxauthid=' . htmlspecialchars($taxAuthId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&clid=' . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&pid=' . htmlspecialchars($pid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&lang='.$lang.'&taxalimit='.$taxaLimit;
									if($page) $navStr .= '<a href="index.php?' . htmlspecialchars($dynLink, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&page=' . htmlspecialchars(($page-1), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">&lt;&lt;</a>';
									else $navStr .= '&lt;&lt;';
									$upperCnt = ($page+1)*$taxaLimit;
									if($taxonCnt < $taxaLimit) $upperCnt = ($page*$taxaLimit)+$taxonCnt;
									$navStr .= ' ' . (($page*$taxaLimit)+1) . ' - ' . $upperCnt . ' taxa ';
									if($taxonCnt > $taxaLimit) $navStr .= '<a href="index.php?' . htmlspecialchars($dynLink, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&page=' . htmlspecialchars(($page+1), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">&gt;&gt;</a>';
									else $navStr .= '&gt;&gt;';
									$navStr .= '</span>';
									if($legendStr) $legendStr .= ' || ';
									$legendStr .= ' ' . $navStr . ' ';
								}

								if($legendStr) echo '<legend>'.$legendStr.'</legend>';
								?>
								<div>
								<?php
									$cnt = 1;
									foreach($sppArr as $sciNameKey => $subArr){
										echo "<div class='spptaxon'>";
										echo "<div style='margin-top:10px;'>";
										echo "<a href='index.php?tid=" . htmlspecialchars($subArr["tid"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&taxauthid=" . htmlspecialchars($taxAuthId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&clid=" . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'>";
										echo "<i>" . $sciNameKey . "</i>";
										echo "</a></div>\n";
										echo "<div class='sppimg' style='overflow:hidden;'>";

										if(array_key_exists("url",$subArr)){
											$imgUrl = $subArr["url"];
											if(array_key_exists("imageDomain",$GLOBALS) && substr($imgUrl,0,1)=="/"){
												$imgUrl = $GLOBALS["imageDomain"] . $imgUrl;
											}
											echo "<a href='index.php?tid=" . htmlspecialchars($subArr["tid"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&taxauthid=" . htmlspecialchars($taxAuthId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&clid=" . htmlspecialchars($clid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'>";

											if($subArr["thumbnailurl"]){
												$imgUrl = $subArr["thumbnailurl"];
												if(array_key_exists("imageDomain",$GLOBALS) && substr($subArr["thumbnailurl"],0,1)=="/"){
													$imgUrl = $GLOBALS["imageDomain"] . $subArr["thumbnailurl"];
												}
											}
											elseif($image = exif_thumbnail($imgUrl)){
												$imgUrl = 'data:image/jpeg;base64,' . base64_encode($image);
											}
											echo '<img src="' . $imgUrl . '" title="' . $subArr['caption'] . '" alt="' . $LANG['IMAGE_OF'] . ' ' . $sciNameKey . '" style="z-index:-1" />';
											echo '</a>';
											echo '<div style="text-align:right;position:relative;top:-26px;left:5px;" title="' . $LANG['PHOTOGRAPHER'] . ': ' . $subArr['photographer'] . '">';
											echo '</div>';
										}
										elseif($isEditor){
											echo '<div class="spptext"><a href="profile/tpeditor.php?category=imageadd&tid=' . htmlspecialchars($subArr['tid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($LANG['ADD_IMAGE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '!</a></div>';
										}
										else{
											echo '<div class="spptext">' . $LANG['IMAGE_NOT_AVAILABLE'] . '</div>';
										}
										echo "</div>\n";
										if(isset($MAP_THUMBNAILS) && $MAP_THUMBNAILS){
											//Display thumbnail map
											if($taxonManager->getRankId() > 140){
												echo '<div class="sppmap">';
												if(array_key_exists("map",$subArr) && $subArr["map"]){
													echo '<img src="' . $subArr['map'] . '" title="' . $taxonManager->getTaxonName() . '" alt="' . $taxonManager->getTaxonName() . '" />';
												}
												else{
													echo '<div class="spptext">' . $LANG['MAP_NOT_AVAILABLE'] . '</div>';
												}
												echo '</div>';
											}
										}
										echo "</div>";
										$cnt++;
										if($cnt > $taxaLimit) break;
									}
									?>
								</div>
							</fieldset>
							<?php
						}
					?>
					</td>
				</tr>
				</table>
				<?php
			}
		}
		else{
			?>
			<div id="innerDiv">
				<?php
				if($isEditor){
					?>
					<div id="editorDiv">
						<?php
						echo '<a href="profile/tpeditor.php?tid=' . htmlspecialchars($taxonManager->getTid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" title="' . htmlspecialchars($LANG['EDIT_TAXON_DATA'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
						echo '<img class="navIcon" src="../images/edit.png" style="width:1.3em" />';
						echo '</a>';
						?>
					</div>
					<?php
				}
				?>
				<div id="scinameDiv"><span id="taxon"><?php echo $taxonManager->getTaxonName(); ?></span></div>
				<div>
					<div id="leftPanel">
						<fieldset style="clear:both">
							<legend><?php echo $LANG['ACCEPTED_TAXA']; ?></legend>
							<div>
								<?php
								$acceptedArr = $taxonManager->getAcceptedArr();
								foreach($acceptedArr as $accTid => $accArr){
									echo '<div><a href="index.php?tid=' . htmlspecialchars($accTid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><b>' . htmlspecialchars($accArr['sciname'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</b></a></div>';
								}
								?>
							</div>
						</fieldset>
					</div>
					<div id="rightPanel"><?php echo $taxonManager->getDescriptionTabs(); ?></div>
				</div>
			</div>
			<?php
		}
	}
	else{
		?>
		<div style="margin-top:45px;margin-left:20px">
			<h1><?php echo '<i>' . htmlspecialchars($taxonValue, ENT_QUOTES, 'UTF-8') . '</i> ' . $LANG['NOT_FOUND']; ?></h1>
			<?php
			if($matchArr = $taxonManager->getCloseTaxaMatches($taxonValue)){
				?>
				<div style="margin-left: 15px;font-weight:bold;font-size:120%;">
					<?php echo $LANG['DID_YOU_MEAN'];?>
					<div style="margin-left:25px;">
						<?php
						foreach($matchArr as $t => $n){
							echo '<a href="index.php?tid=' . htmlspecialchars($t, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($n, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a><br/>';
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>
