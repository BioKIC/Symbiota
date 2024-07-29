<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/classes/ImageLibrarySearch.php');
if($LANG_TAG != 'en' && !file_exists($SERVER_ROOT . '/content/lang/imagelib/search.' . $LANG_TAG . '.php')) $LANG_TAG = 'en';
include_once($SERVER_ROOT . '/content/lang/imagelib/search.' . $LANG_TAG . '.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$taxonType = isset($_REQUEST['taxontype']) ? filter_var($_REQUEST['taxontype'], FILTER_SANITIZE_NUMBER_INT) : 0;
$useThes = array_key_exists('usethes',$_REQUEST) ? filter_var($_REQUEST['usethes'], FILTER_SANITIZE_NUMBER_INT) : 0;
$taxaStr = isset($_REQUEST['taxa']) ? htmlspecialchars($_REQUEST['taxa'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$phUid = array_key_exists('phuid',$_REQUEST) ? filter_var($_REQUEST['phuid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$tagExistance = array_key_exists('tagExistance',$_REQUEST) ? filter_var($_REQUEST['tagExistance'], FILTER_SANITIZE_NUMBER_INT) : 1;
$tag = array_key_exists('tag',$_REQUEST) ? $_REQUEST['tag'] : '';
$keywords = array_key_exists('keywords',$_REQUEST) ? $_REQUEST['keywords'] : '';
$imageCount = isset($_REQUEST['imagecount']) ? $_REQUEST['imagecount'] : 'all';
$imageType = isset($_REQUEST['imagetype']) ? filter_var($_REQUEST['imagetype'], FILTER_SANITIZE_NUMBER_INT) : 0;
$pageNumber = array_key_exists('page', $_REQUEST) ? filter_var($_REQUEST['page'], FILTER_SANITIZE_NUMBER_INT) : 1;
$cntPerPage = array_key_exists('cntperpage', $_REQUEST) && is_numeric($_REQUEST['cntperpage']) ? filter_var($_REQUEST['cntperpage'], FILTER_SANITIZE_NUMBER_INT) : 200;

$action = $_REQUEST['submitaction'] ?? '';

if(!$useThes && !$action) $useThes = 1;
if(!$taxonType && isset($DEFAULT_TAXON_SEARCH)) $taxonType = $DEFAULT_TAXON_SEARCH;

$connType = 'readonly';
if($action == 'batchAssignTag') $connType = 'write';
$imgLibManager = new ImageLibrarySearch($connType);
$imgLibManager->setTaxonType($taxonType);
$imgLibManager->setUseThes($useThes);
$imgLibManager->setTaxaStr($taxaStr);
$imgLibManager->setPhotographerUid($phUid);
$imgLibManager->setTagExistance($tagExistance);
$imgLibManager->setTag($tag);
$imgLibManager->setKeywords($keywords);
$imgLibManager->setImageCount($imageCount);
$imgLibManager->setImageType($imageType);
if(isset($_REQUEST['db'])) $imgLibManager->setCollectionVariables($_REQUEST);

$statusStr = '';
if($action == 'batchAssignTag'){
	$statusCnt = $imgLibManager->batchAssignImageTag($_POST);
	$statusArr = explode('-', $statusCnt);
	if(isset($statusArr[0]) && is_numeric($statusArr[0])){
		$statusStr = '<span style="color:green">';
		$statusStr .= $statusArr[0] . ' ' . $LANG['ACTION_SUCCESS'];
		if(isset($statusArr[1]) && is_numeric($statusArr[1])) $statusStr .= '; ' . $statusArr[1] . ' ' . $LANG['ACTION_FAILED'];
		$statusStr .= '</span>';
	}
	else{
		$statusStr = '<span style="color:red">' . $LANG['ACTION_ERROR'] . ': ' . $imgLibManager->getErrorStr() . '</span>';
	}
}
?>
<!DOCTYPE html>
<html lang="<?= $LANG_TAG ?>">
<head>
	<title><?= $DEFAULT_TITLE . ' ' . $LANG['IMAGE_SEARCH'] ?> </title>
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	include_once($SERVER_ROOT . '/includes/googleanalytics.php');
	?>
	<link href="<?= $CSS_BASE_PATH ?>/jquery-ui.min.css" type="text/css" rel="stylesheet">
	<link href="<?= $CSS_BASE_PATH; ?>/symbiota/collections/listdisplay.css" type="text/css" rel="stylesheet" />
	<link href="<?= $CSS_BASE_PATH; ?>/symbiota/collections/sharedCollectionStyling.css" type="text/css" rel="stylesheet" />
	<style>
		fieldset{ padding: 15px }
		fieldset legend{ font-weight:bold }
		label{ font-weight:bold }
		.row-div{ clear: both; margin: 3px; }
		#action-status-div{ padding: 15px; }
	</style>
	<script src="<?= $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?= $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../js/symb/collections.index.js?ver=2" type="text/javascript"></script>
	<script type="text/javascript">
		var clientRoot = "<?= $CLIENT_ROOT; ?>";

		function validateBatchActionBtn(f){
			if(f.imgTagAction.value == ""){
				alert("<?= $LANG['SELECT_TAG']; ?>");
				return false;
			}
			var formVerified = false;
			for(var h=0; h<f.length; h++){
				if(f.elements[h].name == "imgid[]" && f.elements[h].checked){
					formVerified = true;
					break;
				}
			}
			if(!formVerified){
				alert("<?= $LANG['SELECT_IMAGE']; ?>");
				return false;
			}
			return true;
		}

		function selectAllImages(cb){
			var boxesChecked = true;
			if(!cb.checked){
				boxesChecked = false;
			}
			var f = cb.form;
			for(var i=0; i<f.length; i++){
				if(f.elements[i].name == "imgid[]") f.elements[i].checked = boxesChecked;
			}
		}
	</script>
	<script src="../js/symb/api.taxonomy.taxasuggest.js?ver=4" type="text/javascript"></script>
	<script src="../js/symb/imagelib.search.js?ver=3b" type="text/javascript"></script>
</head>
<body>
	<?php
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div class="navpath">
		<a href="../index.php"><?= $LANG['HOME'] ?></a> &gt;&gt;
		<a href="contributors.php"><?= $LANG['IMAGE_CONTRIBUTORS'] ?></a> &gt;&gt;
		<b><?= $LANG['IMAGE_SEARCH'] ?></b>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['IMAGE_SEARCH']; ?></h1>
		<form name="imagesearchform" id="imagesearchform" action="search.php?<?=$imgLibManager->getQueryTermStr()?>" method="post">
			<?php
			if($statusStr){
				echo '<div id="action-status-div">' . $statusStr . '</div>';
			}
			?>
			<div id="search-div">
				<fieldset>
					<legend><?= $LANG['SEARCH_CRITERIA'] ?></legend>
					<div id="criteria-div">
						<div class="row-div flex-form">
							<?php
							$isEditor = 0;
							if($IS_ADMIN) $isEditor = 1;
							elseif(isset($USER_RIGHTS['CollAdmin']) || isset($USER_RIGHTS['CollEditor'])) $isEditor = 2;
							elseif(isset($USER_RIGHTS['TaxonProfile'])) $isEditor = 2;
							if($isEditor){
								?>
								<div id="edit-div" style="float:right">
									<a href="#" onclick="$('.editor-div').toggle(); return false;"><img class="icon-img" style="width:15px;" src="../images/edit.png"></a>
								</div>
								<?php
							}
							?>
							<div style="float:left;">
								<select id="taxontype" name="taxontype">
									<?php
									for($h=1;$h<6;$h++){
										echo '<option value="' . $h . '" ' . ($imgLibManager->getTaxonType() == $h ? 'SELECTED' : '') . '>' . $LANG['SELECT_1-'.$h] . '</option>';
									}
									?>
								</select>:&nbsp;
							</div>
							<div style="float:left;">
								<input id="taxa" name="taxa" type="text" style="width:450px;" value="<?= $imgLibManager->getTaxaStr() ?>" title="<?= $LANG['SEPARATE_MULTIPLE'] ?>" autocomplete="off" >
							</div>
							<div style="float:left;margin-left:10px;" >
								<input id="usethes" name="usethes" type="checkbox" value="1" <?php if(!$action || $imgLibManager->getUseThes()) echo 'CHECKED'; ?> > <label for="usethes"><?= $LANG['INCLUDE_SYN'] ?></label>
							</div>
						</div>
						<div class="row-div flex-form">
							<label for="phuid"><?= $LANG['PHOTOGRAPHER'] ?></label>:
							<select id="phuid" name="phuid">
								<option value="">-----------------------------</option>
								<?php
								$uidList = $imgLibManager->getPhotographerUidArr();
								foreach($uidList as $uid => $name){
									echo '<option value="' . $uid . '" ' . ($imgLibManager->getPhotographerUid() == $uid ? 'SELECTED' : '') . '>' . $name . '</option>';
								}
								?>
							</select>
						</div>
						<?php
						if($tagArr = $imgLibManager->getTagArr()){
							?>
							<div class="row-div flex-form">
								<label for="tagExistance"><?= $LANG['IMAGE_TAGS'] ?></label>:
								<select id="tagExistance" name="tagExistance">
									<option value="1"><?= $LANG['WITH'] ?></option>
									<option value="0" <?= ($tagExistance ? '' : 'SELECTED') ?>><?= $LANG['WITHOUT'] ?></option>
								</select>
								<select name="tag" >
									<option value="">--------------</option>
									<option value="ANYTAG" <?= ($tag == 'ANYTAG' ? 'selected' : '') ?>><?= $LANG['ANY_TAG'] ?></option>
									<?php
									foreach($tagArr as $tagKey => $displayText){
										echo '<option value="' . $tagKey . '" ' . ($tag == $tagKey ? 'SELECTED ' : '') . '>' . $displayText . '</option>';
									}
									?>
								</select>
							</div>
							<?php
						}
						?>
						<!--
						<div style="clear:both;margin-bottom:5px;">
							Image Keywords:
							<input type="text" id="keywords" style="width:350px;" name="keywords" value="<?php //echo $imgLibManager->getKeywordSuggest(); ?>" title="Separate multiple keywords w/ commas" />
						</div>
						-->
						<?php
						$collList = $imgLibManager->getFullCollectionList();
						$specArr = (isset($collList['spec'])?$collList['spec']:null);
						$obsArr = (isset($collList['obs'])?$collList['obs']:null);
						?>
						<div class="row-div flex-form">
							<fieldset>
								<legend> <?= $LANG['IMAGE_COUNTS'] ?> </legend>
								<input id="countAll" type="radio" name="imagecount" value="0" <?= (!$imgLibManager->getImageCount() ? 'CHECKED ' : '') ?>>
								<label for="countAll"> <?= $LANG['COUNT_ALL'] ?></label><br>
								<input id="countTaxon" type="radio" name="imagecount" value="1" <?= ($imgLibManager->getImageCount() == 1 ? 'CHECKED ' : '') ?>>
								<label for="countTaxon"> <?= $LANG['COUNT_TAXON'] ?></label><br>
								<?php
								if($specArr){
									?>
									<input id="countSpecimen" type="radio" name="imagecount" value="2" <?= ($imgLibManager->getImageCount() == 2 ? 'CHECKED ' : '') ?> >
									<label for="countSpecimen"> <?= $LANG['COUNT_SPECIMEN'] ?></label>
									<?php
								}
								?>
							</fieldset>
						</div>
						<div class="row-div flex-form">
							<fieldset>
								<?php
								$showCollections = false;
								if($imgLibManager->getImageType() == 1 || $imgLibManager->getImageType() == 2) $showCollections = true;
								?>
								<legend> <?= $LANG['IMAGE_TYPE'] ?> </legend>
								<input id="typeAll" type="radio" name="imagetype" value="0" <?= (!$imgLibManager->getImageType() ? 'CHECKED' : '') ?> onclick="deactivateCollectionControl()">
								<label for="typeAll"><?= $LANG['TYPE_ALL'] ?></label><br>
								<input id="typeSpecimen" type="radio" name="imagetype" value="1" <?= ($imgLibManager->getImageType() == 1 ? 'CHECKED' : '') ?> onclick="activateCollectionControl()">
								<label for="typeSpecimen">  <?= $LANG['TYPE_SPECIMEN'] ?></label>
								<span id="collections-control-span" style="margin-left: 5px; display: <?= ($showCollections ? '' : 'none') ?>">
									<span id="display-collections-span"><a href="#" onclick="showCollections()"><?= $LANG['DISPLAY_COLLECTIONS'] ?></a></span>
									<span id="hide-collections-span" style="display: none"><a href="#" onclick="hideCollections()"><?= $LANG['HIDE_COLLECTIONS'] ?></a></span>
								</span><br>
								<input id="typeField" type="radio" name="imagetype" value="3" <?= ($imgLibManager->getImageType() == 3 ? 'CHECKED' : '') ?> onclick="deactivateCollectionControl()">
								<label for="typeField"><?= $LANG['TYPE_FIELD'] ?></label>
							</fieldset>
						</div>
						<div class="row-div flex-form">
							<div style="margin-bottom:5px;float:left;">
								<label for="cntPerPage"><?= $LANG['COUNT_PER_PAGE'] ?></label>:
								<select id="cntPerPage" name="cntperpage">
									<option <?= ($cntPerPage==200 ? 'selected' : '') ?>>200</option>
									<option <?= ($cntPerPage==400 ? 'selected' : '') ?>>400</option>
									<option <?= ($cntPerPage==600 ? 'selected' : '') ?>>600</option>
									<option <?= ($cntPerPage==800 ? 'selected' : '') ?>>800</option>
									<option <?= ($cntPerPage==1000 ? 'selected' : '') ?>>1000</option>
								</select>
							</div>
						</div>
						<div class="row-div flex-form" style="padding-top:10px">
							<button name="submitaction" type="submit" value="search"><?= $LANG['LOAD_IMAGES'] ?></button>
						</div>
						<?php
						if($specArr || $obsArr){
							$allChecked = '';
							if(!$imgLibManager->getDbStr() || $imgLibManager->getDbStr() == 'all') $allChecked = 'checked';
							?>
							<div id="collection-div" style="margin:15px; clear:both; display:none">
								<fieldset>
									<legend><?= $LANG['COLLECTIONS'] ?></legend>
									<div id="specobsdiv">
										<div style="margin:0px 0px 10px 5px;">
											<input id="dballcb" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" <?= $allChecked ?> />
											<?= $LANG['SELECT_ALL'] ?>
										</div>
										<?php
										$imgLibManager->outputFullCollArr($specArr, 9999);
										if($specArr && $obsArr) echo '<hr style="clear:both;margin:20px 0px;"/>';
										$imgLibManager->outputFullCollArr($obsArr, 9999);
										?>
									</div>
								</fieldset>
							</div>
							<?php
						}
						?>
					</div>
					<?php
					if($isEditor){
						?>
						<div class="editor-div" style="display:none; clear: both;">
							<fieldset>
							<legend><?= $LANG['ACTION_PANEL'] ?></legend>
								<div class="row-div">
									<label for="imgTagAction"><?= $LANG['IMAGE_TAG'] ?>:</label>
									<select id="imgTagAction" name="imgTagAction">
										<option value="">---------------------</option>
										<?php
										foreach($tagArr as $tagKey => $displayText){
											echo '<option value="' . $tagKey . '">' . $displayText . '</option>';
										}
										?>
									</select>
									<button name="submitaction" type="submit" value="batchAssignTag" onclick="return validateBatchActionBtn(this.form)"><?= $LANG['BATCH_ASSIGN'] ?></button>
								</div>
								<div class="row-div">
									<input id="imgSelectAll" name="imgselectall" type="checkbox" onclick="selectAllImages(this);" />
									<label for="imgSelectAll"><?= $LANG['SELECT_ALL_IMAGES'] ?></label>
								</div>
							</fieldset>
						</div>
						<?php
					}
					?>
				</fieldset>
				<hr>
			</div>
			<?php
			if($action){
				$isEditorOfAtLeastOne = false;
				?>
				<div id="imagesdiv">
					<div id="imagebox">
						<?php
						$imageArr = $imgLibManager->getImageArr($pageNumber, $cntPerPage);
						$imageArr = $imgLibManager->cleanOutArray($imageArr);
						$recordCnt = $imgLibManager->getRecordCnt();
						if($imageArr){
							$lastPage = ceil($recordCnt / $cntPerPage);
							$startPage = ($pageNumber > 4?$pageNumber - 4:1);
							$endPage = ($lastPage > $startPage + 9 ? $startPage + 9 : $lastPage);
							$url = 'search.php?' . $imgLibManager->getQueryTermStr() . '&cntperpage=' . $cntPerPage . '&submitaction=search';
							$pageBar = '<div style="float:left" >';
							if($startPage > 1){
								$pageBar .= '<span class="pagination" style="margin-right:5px;"><a href="' . $url . '&page=1">' . $LANG['FIRST'] . '</a></span>';
								$pageBar .= '<span class="pagination" style="margin-right:5px;"><a href="' . $url . '&page=' . (($pageNumber - 10) < 1  ? 1 : $pageNumber - 10) . '">&lt;&lt;</a></span>';
							}
							for($x = $startPage; $x <= $endPage; $x++){
								if($pageNumber != $x){
									$pageBar .= '<span class="pagination" style="margin-right:3px;"><a href="' . $url . '&page=' . $x . '">' . $x . '</a></span>';
								}
								else{
									$pageBar .= '<span class="pagination" style="margin-right:3px;font-weight:bold;">' . $x . '</span>';
								}
							}
							if(($lastPage - $startPage) >= 10){
								$pageBar .= '<span class="pagination" style="margin-left:5px;"><a href="' . $url . '&page=' . (($pageNumber + 10) > $lastPage ? $lastPage : ($pageNumber + 10)) . '">&gt;&gt;</a></span>';
								if($recordCnt < 10000) $pageBar .= '<span class="pagination" style="margin-left:5px;"><a href="' . $url . '&page=' . $lastPage . '">' . $LANG['LAST'] . '</a></span>';
							}
							$pageBar .= '</div><div style="float:right;margin-top:4px;margin-bottom:8px;">';
							$beginNum = ($pageNumber - 1)*$cntPerPage + 1;
							$endNum = $beginNum + $cntPerPage - 1;
							if($endNum > $recordCnt) $endNum = $recordCnt;
							$pageBar .= $LANG['PAGE'] . ' ' . $pageNumber . ', ' . $LANG['RECORDS'] . ' ' . number_format($beginNum) . '-'.number_format($endNum) . ' ' . $LANG['OF'] . ' ' . number_format($recordCnt) . '</div>';
							$paginationStr = $pageBar;
							echo '<div style="width:100%;">' . $paginationStr . '</div>';
							echo '<div style="clear:both;margin:5 0 5 0;"><hr /></div>';
							echo '<div style="width:98%;margin-left:auto;margin-right:auto;">';
							$occArr = array();
							$collArr = array();
							if(isset($imageArr['occ'])){
								$occArr = $imageArr['occ'];
								unset($imageArr['occ']);
								$collArr = $imageArr['coll'];
								unset($imageArr['coll']);
							}
							foreach($imageArr as $imgId => $imgArr){
								$imgUrl = $imgArr['url'];
								$imgTn = $imgArr['thumbnailurl'];
								if($imgTn){
									$imgUrl = $imgTn;
									if($IMAGE_DOMAIN && substr($imgTn,0,1) == '/') $imgUrl = $IMAGE_DOMAIN . $imgTn;
								}
								elseif($IMAGE_DOMAIN && substr($imgUrl,0,1) == '/'){
									$imgUrl = $IMAGE_DOMAIN . $imgUrl;
								}
								?>
								<div class="tndiv" style="margin-bottom:15px;margin-top:15px;">
									<div class="tnimg">
										<?php
										$anchorLink = '';
										if($imgArr['occid']){
											$anchorLink = '<a href="#" onclick="openIndPU(' . $imgArr['occid'] . ');return false;">';
										}
										else{
											$anchorLink = '<a href="#" onclick="openImagePopup(' . $imgId . ');return false;">';
										}
										echo $anchorLink . '<img src="' . $imgUrl . '" /></a>';
										?>
									</div>
									<div class="details-div">
										<?php
										$isEditorOfThisImage = false;
										if($isEditor == 1) $isEditorOfThisImage = true;
										elseif($isEditor == 2){
											if($imgArr['occid']){
												$collid = $occArr[$imgArr['occid']]['collid'];
												if($collid){
													if(isset($USER_RIGHTS['CollAdmin'][$collid])) $isEditorOfThisImage = true;
													elseif(isset($USER_RIGHTS['CollEditor'][$collid])) $isEditorOfThisImage = true;
												}
											}
											else{
												if(isset($USER_RIGHTS['TaxonProfile'])) $isEditorOfThisImage = true;
											}
										}
										if($isEditorOfThisImage){
											$isEditorOfAtLeastOne = true;
											echo '<div class="editor-div" style="display:none;margin-top:3px;"><input name="imgid[]" type="checkbox" value="' . $imgId . '"></div>';
										}
										$sciname = $imgArr['sciname'];
										if(!$sciname && $imgArr['occid'] && $occArr[$imgArr['occid']]['sciname']) $sciname = $occArr[$imgArr['occid']]['sciname'];
										if($sciname){
											if(strpos($imgArr['sciname'], ' ')) $sciname = '<i>' . $sciname . '</i>';
											if($imgArr['tid']) echo '<a href="#" onclick="openTaxonPopup(' . $imgArr['tid'] . ');return false;" >';
											echo $sciname;
											if($imgArr['tid']) echo '</a>';
											echo '<br />';
										}
										$photoAuthor = '';
										$authorLink = '';
										if($imgArr['uid']){
											$photoAuthor = $uidList[$imgArr['uid']];
											if(strlen($photoAuthor) > 23){
												$nameArr = explode(',', $photoAuthor);
												$photoAuthor = array_shift($nameArr);
											}
										}
										if($imgArr['occid']){
											$authorLink = '<a href="#" onclick="openIndPU(' . $imgArr['occid'] . ');return false;">';
											if(!$photoAuthor){
												if($occArr[$imgArr['occid']]['recordedby']) $photoAuthor = $occArr[$imgArr['occid']]['recordedby'];
												else{
													if(strpos($occArr[$imgArr['occid']]['catnum'], $collArr[$occArr[$imgArr['occid']]['collid']]) !== 0)
														$photoAuthor = $collArr[$occArr[$imgArr['occid']]['collid']] . ': ';
													$photoAuthor .=  $occArr[$imgArr['occid']]['catnum'];
												}
											}
										}
										if(!$authorLink) $authorLink = $anchorLink;
										echo $authorLink . $photoAuthor . '</a>';
										?>
									</div>
								</div>
								<?php
							}
							echo '</div>';
							if($lastPage > $startPage){
								echo '<div style="clear:both;margin:5 0 5 0;"><hr /></div>';
								echo '<div style="width:100%;">' . $paginationStr . '</div>';
							}
							?>
							<div style="clear:both;"></div>
							<?php
						}
						else{
							echo '<h3>' . $LANG['NO_IMAGES'] . '.</h3>';
						}
						?>
					</div>
				</div>
				<?php
				if(!$isEditorOfAtLeastOne) echo '<script type="text/javascript">$(".editor-div").hide();$("#edit-div").hide();</script>';
			}
			?>
		</form>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>
</html>
