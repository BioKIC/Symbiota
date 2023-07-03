<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
include_once($SERVER_ROOT.'/classes/MapSupport.php');
include_once($SERVER_ROOT.'/content/lang/checklists/checklist.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$clid = array_key_exists('clid', $_REQUEST) ? filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT) : 0;
if(!$clid && array_key_exists('cl',$_REQUEST)) $clid = $_REQUEST['cl'];
$dynClid = array_key_exists('dynclid', $_REQUEST) ? filter_var($_REQUEST['dynclid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$pageNumber = array_key_exists('pagenumber', $_REQUEST) ? filter_var($_REQUEST['pagenumber'], FILTER_SANITIZE_NUMBER_INT) : 1;
$pid = array_key_exists('pid', $_REQUEST) ? filter_var($_REQUEST['pid'], FILTER_SANITIZE_NUMBER_INT) : '';
$thesFilter = array_key_exists('thesfilter', $_REQUEST) ? filter_var($_REQUEST['thesfilter'], FILTER_SANITIZE_NUMBER_INT) : 0;
$taxonFilter = array_key_exists('taxonfilter', $_REQUEST) ? filter_var($_REQUEST['taxonfilter'], FILTER_SANITIZE_STRING) : '';
$showAuthors = array_key_exists('showauthors', $_REQUEST) ? filter_var($_REQUEST['showauthors'], FILTER_SANITIZE_NUMBER_INT) : 0;
$showSynonyms = array_key_exists('showsynonyms', $_REQUEST) ? filter_var($_REQUEST['showsynonyms'], FILTER_SANITIZE_NUMBER_INT) : 0;
$showCommon = array_key_exists('showcommon', $_REQUEST) ? filter_var($_REQUEST['showcommon'], FILTER_SANITIZE_NUMBER_INT) : 0;
$showImages = array_key_exists('showimages', $_REQUEST) ? filter_var($_REQUEST['showimages'], FILTER_SANITIZE_NUMBER_INT) : 0 ;
$limitImagesToVouchers = array_key_exists('voucherimages', $_REQUEST) ? filter_var($_REQUEST['voucherimages'], FILTER_SANITIZE_NUMBER_INT) : 0;
$showVouchers = array_key_exists('showvouchers', $_REQUEST) ? filter_var($_REQUEST['showvouchers'], FILTER_SANITIZE_NUMBER_INT) : 0;
$showAlphaTaxa = array_key_exists('showalphataxa', $_REQUEST) ? filter_var($_REQUEST['showalphataxa'], FILTER_SANITIZE_NUMBER_INT) : 0;
$searchCommon = array_key_exists('searchcommon', $_REQUEST) ? filter_var($_REQUEST['searchcommon'], FILTER_SANITIZE_NUMBER_INT) : 0;
$searchSynonyms = array_key_exists('searchsynonyms', $_REQUEST) ? filter_var($_REQUEST['searchsynonyms'], FILTER_SANITIZE_NUMBER_INT) : 0;
$defaultOverride = array_key_exists('defaultoverride', $_REQUEST) ? filter_var($_REQUEST['defaultoverride'], FILTER_SANITIZE_NUMBER_INT) : 0;
$printMode = array_key_exists('printmode', $_REQUEST) ? filter_var($_REQUEST['printmode'], FILTER_SANITIZE_NUMBER_INT) : 0;

$statusStr='';

//Search Synonyms is default
if($action != 'Rebuild List' && !array_key_exists('dllist_x',$_POST)) $searchSynonyms = 1;
if($action == 'Rebuild List') $defaultOverride = 1;

$clManager = new ChecklistManager();
if($clid) $clManager->setClid($clid);
elseif($dynClid) $clManager->setDynClid($dynClid);
$clArray = $clManager->getClMetaData();
$activateKey = $KEY_MOD_IS_ACTIVE;
$showDetails = 0;
if(isset($clArray['defaultSettings'])){
	$defaultArr = json_decode($clArray['defaultSettings'], true);
	$showDetails = $defaultArr['ddetails'];
	if(!$defaultOverride){
		if(array_key_exists('dsynonyms',$defaultArr)) $showSynonyms = $defaultArr['dsynonyms'];
		if(array_key_exists('dcommon',$defaultArr)) $showCommon = $defaultArr['dcommon'];
		if(array_key_exists('dimages',$defaultArr)) $showImages = $defaultArr['dimages'];
		if(array_key_exists('dvoucherimages',$defaultArr)) $limitImagesToVouchers = $defaultArr['dvoucherimages'];
		if(array_key_exists('dvouchers',$defaultArr)) $showVouchers = $defaultArr['dvouchers'];
		if(array_key_exists('dauthors',$defaultArr)) $showAuthors = $defaultArr['dauthors'];
		if(array_key_exists('dalpha',$defaultArr)) $showAlphaTaxa = $defaultArr['dalpha'];
	}
	if(isset($defaultArr['activatekey'])) $activateKey = $defaultArr['activatekey'];
}
if($pid) $clManager->setProj($pid);
elseif(array_key_exists('proj',$_REQUEST) && $_REQUEST['proj']) $pid = $clManager->setProj($_REQUEST['proj']);
if($thesFilter) $clManager->setThesFilter($thesFilter);
if($taxonFilter) $clManager->setTaxonFilter($taxonFilter);
$clManager->setLanguage($LANG_TAG);
if($searchCommon){
	$showCommon = 1;
	$clManager->setSearchCommon(true);
}
if($searchSynonyms) $clManager->setSearchSynonyms(true);
if($showAuthors) $clManager->setShowAuthors(true);
if($showSynonyms) $clManager->setShowSynonyms(true);
if($showCommon) $clManager->setShowCommon(true);
if($showImages) $clManager->setShowImages(true);
if($limitImagesToVouchers) $clManager->setLimitImagesToVouchers(true);
if($showVouchers) $clManager->setShowVouchers(true);
if($showAlphaTaxa) $clManager->setShowAlphaTaxa(true);
$clid = $clManager->getClid();
$pid = $clManager->getPid();

if(array_key_exists('dllist_x',$_POST)){
	$clManager->downloadChecklistCsv();
	exit();
}
elseif(array_key_exists('printlist_x',$_POST)){
	$printMode = 1;
}

$isEditor = false;
if($IS_ADMIN || (array_key_exists('ClAdmin',$USER_RIGHTS) && in_array($clid,$USER_RIGHTS['ClAdmin']))){
	$isEditor = true;
}
if($isEditor && array_key_exists('formsubmit',$_POST)){
	if($_POST['formsubmit'] == 'AddSpecies'){
		$statusStr = $clManager->addNewSpecies($_POST);
	}
}
$taxaArray = $clManager->getTaxaList($pageNumber,($printMode?0:500));
?>
<html>
<head>
	<meta charset="<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.(isset($LANG['CHECKLIST'])?$LANG['CHECKLIST']:'Checklist').': '.$clManager->getClName(); ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<link href="<?php echo $CSS_BASE_PATH; ?>/symbiota/checklists/checklist.css" type="text/css" rel="stylesheet" />
	<script src="../js/jquery.js" type="text/javascript"></script>
	<script src="../js/jquery-ui.js" type="text/javascript"></script>
	<script type="text/javascript">
		<?php
		if($clid) echo 'var clid = '.$clid.';'."\n";
		echo 'var taxaCount = '.count($taxaArray).';'."\n";
		?>
		$( function() {
			$( document ).tooltip();
		} );

		function changeImageSource(elem){
			let f = document.optionform;
			if(elem.id == "vi_voucher") f.voucherimages.value = "1";
			else f.voucherimages.value = "0";
			f.submit();
		}
	</script>
	<script type="text/javascript" src="../js/symb/checklists.checklist.js?ver=3"></script>
	<style type="text/css">
		<?php
		if($printMode){
			?>
			body{ background-color:#ffffff;  }
			#innertext{ background-color:#ffffff; }
			.printoff{ display:none; }
			a{ color: currentColor; cursor: none; pointer-events: none; text-decoration: none; }
			<?php
		}
		?>
		#editsppon { display: none; color:green; font-size: 70%; font-weight:bold; padding-bottom: 5px; position: relative; top: -4px; }
	</style>
</head>
<body>
	<?php
	$HEADER_URL = '';
	if(isset($clArray['headerurl']) && $clArray['headerurl']) $HEADER_URL = $CLIENT_ROOT.$clArray['headerurl'];
	$displayLeftMenu = (isset($checklists_checklistMenu)?$checklists_checklistMenu:false);
	if(!$printMode) include($SERVER_ROOT.'/includes/header.php');
	echo '<div class="navpath printoff">';
	if($pid){
		echo '<a href="../index.php">' . $LANG['NAV_HOME'] . '</a> &gt; ';
		echo '<a href="' . $CLIENT_ROOT . '/projects/index.php?pid=' . $pid . '">';
		echo $clManager->getProjName();
		echo '</a> &gt; ';
		echo '<b>' . $clManager->getClName() . '</b>';
	}
	else{
		echo '<a href="../index.php">' . $LANG['NAV_HOME'] . '</a> &gt;&gt; ';
		echo '<a href="checklist.php?clid='. $clid . '&pid=' . $pid . ($dynClid ? '&dynclid=' . $dynClid : $dynClid) . '"><b>' . $clManager->getClName() . '</b></a>';
	}
	echo '</div>';
	?>
	<!-- This is inner text! -->
	<div id='innertext'>
		<?php
		if(($clid || $dynClid) && $clArray){
			if($clid && $isEditor){
				?>
				<div class="printoff" style="float:right;width:auto;">
					<span style="">
						<a href="checklistadmin.php?clid=<?php echo $clid.'&pid='.$pid; ?>" style="margin-right:10px;" title="<?php echo (isset($LANG['CHECKLIST_ADMIN'])?$LANG['CHECKLIST_ADMIN']:'Checklist Administration'); ?>">
						<img src="../images/editadmin.png" srcset="../images/editA.svg" style="height:15px" /></a>
					</span>
					<span style="">
						<a href="voucheradmin.php?clid=<?php echo $clid.'&pid='.$pid; ?>" style="margin-right:10px;" title="<?php echo (isset($LANG['MANAGE_VOUCHERS'])?$LANG['MANAGE_VOUCHERS']:'Manage Linked Vouchers'); ?>">
							<img style="border:0px;height:15px;" src="../images/editvoucher.png" srcset="../images/editV.svg" style="height:15px" /></a>
					</span>
					<span style="" onclick="toggleSppEditControls();return false;">
						<a href="#" title="<?php echo (isset($LANG['EDIT_LIST'])?$LANG['EDIT_LIST']:'Edit Species List'); ?>">
							<img style="border:0px;height:15px;" src="../images/editspp.png" srcset="../images/editspp.svg" style="height:15px" /><span id="editsppon">-ON</span></a>
					</span>
				</div>
				<?php
			}
			?>
			<div id="title-div">
				<?php echo $clManager->getClName(); ?>
			</div>
			<?php
			if($activateKey){
				?>
				<div class="printoff" style="float:left;padding:5px;">
					<a href="../ident/key.php?clid=<?php echo $clid."&pid=".$pid."&dynclid=".$dynClid;?>&taxon=All+Species">
						<img src='../images/key.png' srcset="../images/key.svg" style="width:15px; height:15px" title='<?php echo (isset($LANG['OPEN_KEY'])?$LANG['OPEN_KEY']:'Open Symbiota Key'); ?>' />
					</a>
				</div>
				<?php
			}
			if($taxaArray){
				?>
				<div class="printoff" style="padding:5px;">
					<ul id="game-dropdown">
						<li>
							<span onmouseover="mopen('m1')" onmouseout="mclosetime()">
								<img src="../images/games/games.png" style="height:17px;" />
							</span>
							<div id="m1" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">
								<?php
								$varStr = "?clid=".$clid."&dynclid=".$dynClid."&listname=".$clManager->getClName()."&taxonfilter=".$taxonFilter."&showcommon=".$showCommon.($clManager->getThesFilter()?"&thesfilter=".$clManager->getThesFilter():"");
								?>
								<a href="../games/namegame.php<?php echo $varStr; ?>"><?php echo $LANG['NAMEGAME'];?></a>
								<a href="../games/flashcards.php<?php echo $varStr; ?>"><?php echo $LANG['FLASH'];?></a>
							</div>
						</li>
					</ul>
				</div>
				<?php
			}
			echo '<div style="clear:both;"></div>';
			$argStr = '&clid='.$clid.'&dynclid='.$dynClid.($showCommon?'&showcommon='.$showCommon:'').($showSynonyms?'&showsynonyms='.$showSynonyms:'').($showVouchers?'&showvouchers='.$showVouchers:'');
			$argStr .= ($showAuthors?'&showauthors='.$showAuthors:'').($clManager->getThesFilter()?'&thesfilter='.$clManager->getThesFilter():'');
			$argStr .= ($pid?'&pid='.$pid:'').($showImages?'&showimages=1':'').($taxonFilter?'&taxonfilter='.$taxonFilter:'').($limitImagesToVouchers?'&voucherimages=1':'');
			$argStr .= ($searchCommon?'&searchcommon='.$searchCommon:'').($searchSynonyms?'&searchsynonyms='.$searchSynonyms:'');
			$argStr .= ($showAlphaTaxa?'&showalphataxa='.$showAlphaTaxa:'');
			$argStr .= ($defaultOverride?'&defaultoverride='.$defaultOverride:'');
			//Do not show certain fields if Dynamic Checklist ($dynClid)
			if($clid){
				if($clArray['type'] == 'rarespp'){
					echo '<div style="clear:both;"><b>'.(isset($LANG['SENSITIVE_SPECIES'])?$LANG['SENSITIVE_SPECIES']:'Sensitive species checklist for').':</b> '.$clArray["locality"].'</div>';
					if($isEditor && $clArray["locality"]){
						include_once($SERVER_ROOT.'/classes/OccurrenceMaintenance.php');
						$occurMaintenance = new OccurrenceMaintenance();
						echo '<div style="margin-left:15px">'.(isset($LANG['NUMBER_PENDING'])?$LANG['NUMBER_PENDING']:'Number of specimens pending protection').': ';
						if($action == 'protectspp'){
							$occurMaintenance->protectStateRareSpecies($clid,$clArray["locality"]);
							echo '0';
						}
						elseif($action == 'checkstatus'){
							$protectCnt = $occurMaintenance->getStateProtectionCount($clid, $clArray["locality"]);
							echo $protectCnt;
							if($protectCnt){
								echo '<span style="margin-left:10px"><a href="checklist.php?submitaction=protectspp'.$argStr.'">';
								echo '<button style="font-size:70%">'.(isset($LANG['PROTECT_LOCALITY'])?$LANG['PROTECT_LOCALITY']:'Protect Localities').'</button>';
								echo '</a></span>';
							}
						}
						else{
							echo '<span style="margin-left:10px"><a href="checklist.php?submitaction=checkstatus'.$argStr.'">';
							echo '<button style="font-size:70%">'.(isset($LANG['CHECK_STATUS'])?$LANG['CHECK_STATUS']:'Check Status').'</button>';
							echo '</a></span>';
						}
						echo '</div>';
					}
				}
				elseif($clArray['type'] == 'excludespp'){
					$parentArr = $clManager->getParentChecklist();
					echo '<div style="clear:both;">'.(isset($LANG['EXCLUSION_LIST'])?$LANG['EXCLUSION_LIST']:'Exclusion Species List for').' <b><a href="checklist.php?pid='.$pid.'&clid='.key($parentArr).'">'.current($parentArr).'</a></b></div>';
				}
				if($childArr = $clManager->getChildClidArr()){
					echo '<div style="float:left;">'.(isset($LANG['INCLUDE_TAXA'])?$LANG['INCLUDE_TAXA']:'Includes taxa from following child checklists').':</div>';
					echo '<div style="margin-left:10px;float:left">';
					foreach($childArr as $childClid => $childName){
						echo '<div style="clear:both;"><b><a href="checklist.php?pid='.$pid.'&clid='.$childClid.'">'.$childName.'</a></b></div>';
					}
					echo '</div>';
				}
				if($exclusionArr = $clManager->getExclusionChecklist()){
					echo '<div class="printoff" style="clear:both">'.(isset($LANG['TAXA_EXCLUDED'])?$LANG['TAXA_EXCLUDED']:'Taxa explicitly excluded').': <b><a href="checklist.php?pid='.$pid.'&clid='.key($exclusionArr).'">'.current($exclusionArr).'</a></b></div>';
				}
				if($clArray["authors"] && $clArray['type'] != 'excludespp'){
					?>
					<div id="author-div">
						<span class="md-label">
							<?php echo (isset($LANG['AUTHORS'])?$LANG['AUTHORS']:'Authors'); ?>:
						</span>
						<?php echo $clArray['authors']; ?>
					</div>
					<?php
				}
				if($clArray['publication']){
					$pubStr = $clArray['publication'];
					if(substr($pubStr,0,4)=='http' && !strpos($pubStr,' ')) $pubStr = '<a href="'.$pubStr.'" target="_blank">'.$pubStr.'</a>';
					echo '<div style="clear:both;"><span class="md-label">'.(isset($LANG['CITATION'])?$LANG['CITATION']:'Citation').':</span> '.$pubStr.'</div>';
				}
			}
			if(($clArray["locality"] || ($clid && ($clArray["latcentroid"] || $clArray["abstract"])) || $clArray["notes"])){
				?>
				<div class="moredetails printoff" style="<?php echo (($showDetails)?'display:none;':''); ?>"><a href="#" onclick="toggle('moredetails');return false;"><?php echo $LANG['MOREDETAILS'];?></a></div>
				<div class="moredetails" style="display:<?php echo (($showDetails || $printMode)?'block':'none'); ?>;">
					<?php
					if($clArray['type'] != 'excludespp'){
						$locStr = $clArray["locality"];
						if($clid && $clArray["latcentroid"]) $locStr .= " (".$clArray["latcentroid"].", ".$clArray["longcentroid"].")";
						if($locStr){
							echo '<div><span  class="md-label">'.$LANG['LOCALITY'].': </span>'.$locStr.'</div>';
						}
					}
					if($clid && $clArray["abstract"]){
						$abstractTitle = $LANG['ABSTRACT'];
						if($clArray['type'] == 'excludespp') $abstractTitle = $LANG['COMMENTS'];
						echo '<div><span  class="md-label">'.$abstractTitle.': </span>'.$clArray['abstract'].'</div>';
					}
					if($clArray['notes']){
						echo '<div><span class="md-label">'.(isset($LANG['NOTES'])?$LANG['NOTES']:'Notes').': </span>'.$clArray['notes'].'</div>';
					}
					?>
				</div>
				<div class="moredetails printoff" style="display:<?php echo (($showDetails)?'block':'none'); ?>"><a href="#" onclick="toggle('moredetails');return false;"><?php echo $LANG['LESSDETAILS'];?></a></div>
				<?php
			}
			if($statusStr){
				?>
				<hr />
				<div style="margin:20px;font-weight:bold;color:red;">
					<?php echo $statusStr; ?>
				</div>
				<hr />
				<?php
			}
			?>
			<div style="clear:both">
				<hr/>
			</div>
			<div id="checklist-container">
				<!-- Option box -->
				<div class="printoff" id="cloptiondiv">
					<div style="">
						<form id="optionform" name="optionform" action="checklist.php" method="post">
							<fieldset style="background-color:white;padding-bottom:10px;">
								<legend><b><?php echo $LANG['OPTIONS'];?></b></legend>
								<!-- Taxon Filter option -->
								<div id="taxonfilterdiv">
									<div>
										<b><?php echo $LANG['SEARCH'];?>:</b>
										<input type="text" id="taxonfilter" name="taxonfilter" value="<?php echo $taxonFilter;?>" size="20" />
									</div>
									<div>
										<div style="margin-left:10px;">
											<?php
											if($DISPLAY_COMMON_NAMES){
												echo "<input type='checkbox' name='searchcommon' value='1'".($searchCommon?"checked":"")."/> ".$LANG['COMMON']."<br/>";
											}
											?>
											<input type="checkbox" name="searchsynonyms" value="1"<?php echo ($searchSynonyms?"checked":"");?>/> <?php echo $LANG['SYNONYMS'];?>
										</div>
									</div>
								</div>
								<div>
									<b><?php echo (isset($LANG['FILTER'])?$LANG['FILTER']:'Taxonomic Filter');?>:</b><br/>
									<select name='thesfilter'>
										<option value='0'><?php echo $LANG['OGCHECK'];?></option>
										<?php
										$taxonAuthList = Array();
										$taxonAuthList = $clManager->getTaxonAuthorityList();
										foreach($taxonAuthList as $taCode => $taValue){
											echo "<option value='".$taCode."'".($taCode == $clManager->getThesFilter()?" selected":"").">".$taValue."</option>\n";
										}
										?>
									</select>
								</div>
								<div id="showsynonymsdiv" style="display:<?php echo ($showImages?"none":"block");?>">
									<input name='showsynonyms' type='checkbox' value='1' <?php echo ($showSynonyms?"checked":""); ?> />
									<?php echo $LANG['DISPLAY_SYNONYMS'];?>
								</div>
								<?php
								if($DISPLAY_COMMON_NAMES){
									echo '<div>';
									echo "<input id='showcommon' name='showcommon' type='checkbox' value='1' ".($showCommon?"checked":"")."/> ".$LANG['COMMON']."";
									echo '</div>';
								}
								?>
								<div>
									<input name='showimages' type='checkbox' value='1' <?php echo ($showImages?"checked":""); ?> onclick="showImagesChecked(this.form);" />
									<?php echo $LANG['DISPLAYIMAGES'];?>
								</div>
								<?php
								if($clid){
									?>
									<div id="showvouchersdiv" style="display:<?php echo ($showImages?"none":"block");?>">
										<input name='showvouchers' type='checkbox' value='1' <?php echo ($showVouchers?"checked":""); ?>/>
										<?php echo $LANG['NOTESVOUC'];?>
									</div>
									<?php
								}
								?>
								<div id="showauthorsdiv" style='display:<?php echo ($showImages?"none":"block");?>'>
									<input name='showauthors' type='checkbox' value='1' <?php echo ($showAuthors?"checked":""); ?>/>
									<?php echo $LANG['TAXONAUTHOR'];?>
								</div>
								<div style='' id="showalphataxadiv">
									<input name='showalphataxa' type='checkbox' value='1' <?php echo ($showAlphaTaxa?"checked":""); ?>/>
									<?php echo $LANG['TAXONABC'];?>
								</div>
								<div style="margin:5px 0px 0px 5px;">
									<div style="float:left;margin-bottom:5px">
										<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
										<input type="hidden" name="dynclid" value="<?php echo $dynClid; ?>" />
										<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
										<input type="hidden" name="defaultoverride" value="1" />
										<input type="hidden" name="voucherimages" value="<?php echo $limitImagesToVouchers; ?>" />
										<?php if(!$taxonFilter) echo '<input type="hidden" name="pagenumber" value="'.$pageNumber.'" />'; ?>
										<button name="submitaction" type="submit" value="Rebuild List" onclick="changeOptionFormAction('checklist.php?clid=<?php echo $clid."&pid=".$pid."&dynclid=".$dynClid; ?>','_self');"><?php echo (isset($LANG['BUILD_LIST'])?$LANG['BUILD_LIST']:'Build List'); ?></button>
									</div>
									<div style="float:right">
										<div class="button" style="float:right;margin-right:10px;width:16px;height:16px;padding:2px;" title="<?php echo (isset($LANG['DOWNLOAD_CHECKLIST'])?$LANG['DOWNLOAD_CHECKLIST']:'Download Checklist'); ?>">
											<input type="image" name="dllist" value="Download List" src="../images/dl.png" onclick="changeOptionFormAction('checklist.php?clid=<?php echo $clid."&pid=".$pid."&dynclid=".$dynClid; ?>','_self');" />
										</div>
										<div class="button" style="float:right;margin-right:10px;width:16px;height:16px;padding:2px;" title="<?php echo (isset($LANG['PRINT_BROWSER'])?$LANG['PRINT_BROWSER']:'Print in Browser'); ?>">
											<input type="image" name="printlist" value="Print List" src="../images/print.png" onclick="changeOptionFormAction('checklist.php','_blank');" />
										</div>
										<div class="button" id="wordicondiv" style="float:right;margin-right:10px;width:16px;height:16px;padding:2px;<?php echo ($showImages?'display:none;':''); ?>" title="<?php echo (isset($LANG['EXPORT_DOCX'])?$LANG['EXPORT_DOCX']:'Export to DOCX'); ?>">
											<input type="image" name="exportdoc" value="Export to DOCX" src="../images/wordicon.png" srcset="../images/file-text.svg" onclick="changeOptionFormAction('mswordexport.php','_self');" />
										</div>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
					if($clid && $isEditor){
						?>
						<div class="editspp" style="width:250px;display:none;">
							<form id='addspeciesform' action='checklist.php' method='post' name='addspeciesform' onsubmit="return validateAddSpecies(this);">
								<fieldset style='margin:5px 0px 5px 5px;background-color:#FFFFCC;'>
									<legend><b><?php echo $LANG['NEWSPECIES'];?></b></legend>
									<div>
										<?php echo $LANG['TAXON']; ?>:<br/>
										<input type="text" id="speciestoadd" name="speciestoadd" style="width:174px;" />
										<input type="hidden" id="tid" name="tid" />
									</div>
									<!--
									<div>
										<?php echo $LANG['MORPHOSPECIES']; ?>:<br/>
										<input type="text" name="morphospecies" style="width:122px;" title="" />
									</div>
									-->
									<div>
										<?php echo $LANG['FAMILYOVERRIDE']; ?>:<br/>
										<input type="text" name="familyoverride" style="width:122px;" title="<?php echo (isset($LANG['FAMILYOVERRIDE_DESCR'])?$LANG['FAMILYOVERRIDE_DESCR']:'For overriding current family'); ?>" />
									</div>
									<div>
										<?php echo $LANG['HABITAT']; ?>:<br/>
										<input type="text" name="habitat" style="width:170px;" />
									</div>
									<div>
										<?php echo $LANG['ABUNDANCE']; ?>:<br/>
										<input type="text" name="abundance" style="width:145px;" />
									</div>
									<div>
										<?php echo $LANG['NOTES']; ?>:<br/>
										<input type="text" name="notes" style="width:175px;" />
									</div>
									<div style="padding:2px;">
										<?php echo $LANG['INTNOTES']; ?>:<br/>
										<input type="text" name="internalnotes" style="width:126px;" title="<?php echo (isset($LANG['ADMIN_ONLY'])?$LANG['ADMIN_ONLY']:'Displayed to administrators only'); ?>" />
									</div>
									<div>
										<?php echo $LANG['SOURCE']; ?>:<br/>
										<input type="text" name="source" style="width:167px;" />
									</div>
									<div style="margin-top:5px">
										<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
										<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
										<input type="hidden" name="showsynonyms" value="<?php echo $showSynonyms; ?>" />
										<input type="hidden" name="showcommon" value="<?php echo $showCommon; ?>" />
										<input type="hidden" name="showvouchers" value="<?php echo $showVouchers; ?>" />
										<input type="hidden" name="showimages" value="<?php $showImages; ?>" />
										<input type="hidden" name="voucherimages" value="<?php $limitImagesToVouchers; ?>" />
										<input type="hidden" name="showauthors" value="<?php echo $showAuthors; ?>" />
										<input type="hidden" name="thesfilter" value="<?php echo $clManager->getThesFilter(); ?>" />
										<input type="hidden" name="taxonfilter" value="<?php echo $taxonFilter; ?>" />
										<input type="hidden" name="searchcommon" value="<?php echo $searchCommon; ?>" />
										<input type="hidden" name="formsubmit" value="AddSpecies" />
										<button name="submitbtn" type="submit"><?php echo (isset($LANG['ADD_SPECIES'])?$LANG['ADD_SPECIES']:'Add Species to List'); ?></button>
										<hr />
									</div>
									<div style="text-align:center;">
										<a href="tools/checklistloader.php?clid=<?php echo $clid.'&pid='.$pid;?>"><?php echo $LANG['BATCH_LOAD_SPREADSHEET'];?></a>
									</div>
								</fieldset>
							</form>
						</div>
						<?php
					}
					if(!$showImages){
						?>
						<div style="text-align:center">
							<?php
							$coordArr = $clManager->getVoucherCoordinates(200);
							if($coordArr){
								$tnUrl = MapSupport::getStaticMap($coordArr);
								$tnWidth = 100;
								if(strpos($tnUrl,$CLIENT_ROOT) === 0) $tnWidth = 50;
								?>
								<div style="text-align:center;font-weight:bold;margin-bottom:5px"><?php echo (isset($LANG['VOUCHER_MAPPING'])?$LANG['VOUCHER_MAPPING']:'Voucher Mapping'); ?></div>
								<div style="display: flex; align-items: center; justify-content: center;">
									<div style="float:left;" title="<?php echo (isset($LANG['VOUCHERS_SIMPLE_MAP'])?$LANG['VOUCHERS_SIMPLE_MAP']:'Display Vouchers in Simply Map'); ?>">
										<a href="checklistmap.php?clid=<?php echo $clid.'&thesfilter='.$thesFilter.'&taxonfilter='.$taxonFilter; ?>" target="_blank">
											<img src="<?php echo $tnUrl; ?>" style="border:0px;width:<?php echo $tnWidth; ?>px" /><br/>
											<?php echo (isset($LANG['SIMPLE_MAP'])?$LANG['SIMPLE_MAP']:'Simply Map'); ?>
										</a>
									</div>
									<div style="float:left;margin-left:15px" title="<?php echo (isset($LANG['VOUCHERS_DYNAMIC_MAP'])?$LANG['VOUCHERS_DYNAMIC_MAP']:'Display Vouchers in Dynamic Map'); ?>">
										<a href="../collections/map/index.php?clid=<?php echo $clid.'&cltype=vouchers&taxonfilter='.$taxonFilter; ?>&db=all&type=1&reset=1" target="_blank">
											<img src="<?php echo $tnUrl; ?>" style="width:<?php echo $tnWidth; ?>px" /><br/>
											<?php echo (isset($LANG['DYNAMIC_MAP'])?$LANG['DYNAMIC_MAP']:'Dynamic Map'); ?>
										</a>
									</div>
								</div>
								<?php
							}
							if(false && $clArray['dynamicsql']){
								//Temporarily turned off
								?>
								<span style="margin:5px">
									<a href="../collections/map/index.php?clid=<?php echo $clid.'&cltype=all&taxonfilter='.$taxonFilter; ?>&db=all&type=1&reset=1" target="_blank">
										<?php
										if($coordArr){
											echo '<img src="../images/world.png" style="width:30px" title="'.(isset($LANG['OCCUR_DYNAMIC_MAP'])?$LANG['OCCUR_DYNAMIC_MAP']:'Display All Occurrence in Dynamic Map').'" />';
										}
										else{
											$polygonCoordArr = $clManager->getPolygonCoordinates();
											$googleUrl .= '&markers=size:tiny|'.implode('|',$polygonCoordArr);
											echo '<img src="'.$googleUrl.'" style="border:0px;" /><br/>';
										}
										?>
									</a>
								</span>
								<?php
							}
							?>
						</div>
						<?php
					}
					?>
				</div>
				<div id="img-container">
					<div style="margin:3px;">
						<?php
						echo '<b>'.$LANG['FAMILIES'].'</b>: ';
						echo $clManager->getFamilyCount();
						?>
					</div>
					<div style="margin:3px;">
						<?php
						echo '<b>'.$LANG['GENERA'].'</b>: ';
						echo $clManager->getGenusCount();
						?>
					</div>
					<div style="margin:3px;">
						<?php
						echo '<b>'.$LANG['SPECIES'].'</b>: ';
						echo $clManager->getSpeciesCount();
						?>
					</div>
					<div style="margin:3px;">
						<?php
						echo '<b>' . $LANG['TOTAL_TAXA'] . '</b>: ';
						echo $clManager->getTaxaCount();
						?>
					</div>
					<hr />
					<div class="printoff">
						<?php
						$taxaLimit = ($showImages?$clManager->getImageLimit():$clManager->getTaxaLimit());
						$pageCount = ceil($clManager->getTaxaCount()/$taxaLimit);
						if(($pageNumber)>$pageCount) $pageNumber = 1;
						echo $LANG['PAGE'].'<b> '.($pageNumber).'</b> '.$LANG['OF'].' <b>'.$pageCount.'</b>: ';
						for($x=1;$x<=$pageCount;$x++){
							if($x>1) echo " | ";
							if(($pageNumber) == $x) echo '<b>';
							else echo '<a href="checklist.php?pagenumber='.$x.$argStr.'">';
							echo ($x);
							if(($pageNumber) == $x) echo '</b>';
							else echo '</a>';
						}
						if($showImages){
							?>
							<div style="float:right;">
								Image source:
								<input id="vi_all" name="voucherimages" type="radio" onclick="changeImageSource(this)" <?php echo ($limitImagesToVouchers?'':'checked'); ?> /> all images
								<input id="vi_voucher" name="voucherimages" type="radio" onclick="changeImageSource(this)" <?php echo ($limitImagesToVouchers?'checked':''); ?> /> linked voucher images only
							</div>
							<?php
						}
						?>
					</div>
					<hr />
					<?php
					if($showImages){
						$prevfam = '';
						foreach($taxaArray as $tid => $sppArr){
							$tu = (array_key_exists('tnurl',$sppArr)?$sppArr['tnurl']:'');
							$u = (array_key_exists('url',$sppArr)?$sppArr['url']:'');
							$imgSrc = ($tu?$tu:$u);
							?>
							<div class="tndiv">
								<div class="tnimg" style="<?php echo ($imgSrc?'':'border:1px solid black;'); ?>">
									<?php
									$spUrl = "../taxa/index.php?taxauthid=1&taxon=$tid&clid=".$clid;
									if($imgSrc){
										$imgSrc = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgSrc,0,4)!="http"?$GLOBALS["imageDomain"]:"").$imgSrc;
										echo "<a href='".$spUrl."' target='_blank'>";
										echo "<img src='".$imgSrc."' />";
										echo "</a>";
									}
									else{
										?>
										<div style="margin-top:50px;">
											<b><?php echo $LANG['IMAGE'];?><br/><?php echo $LANG['NOTY'];?><br/><?php echo $LANG['AVAIL'];?></b>
										</div>
										<?php
									}
									?>
								</div>
								<div style="clear:both">
									<?php
									echo '<a href="'.$spUrl.'" target="_blank">';
									echo '<b>'.$sppArr['sciname'].'</b>';
									echo '</a>';
									?>
									<div class="editspp printoff" style="float:left;display:none;">
										<?php
										if(isset($sppArr['clid'])){
											$clidArr = explode(',',$sppArr['clid']);
											foreach($clidArr as $id){
												?>
												<a href="#" onclick="return openPopup('clsppeditor.php?tid=<?php echo $tid."&clid=".$id; ?>','editorwindow');">
													<img src='../images/edit.png' style='width:13px;' title='<?php echo (isset($LANG['EDIT_DETAILS'])?$LANG['EDIT_DETAILS']:'edit details'); ?>' />
												</a>
												<?php
											}
										}
										?>
									</div>
									<?php
									if(array_key_exists('vern',$sppArr)){
										echo "<div style='font-weight:bold;'>".$sppArr["vern"]."</div>";
									}
									if(!$showAlphaTaxa){
										$family = $sppArr['family'];
										if($family != $prevfam){
											?>
											<div class="familydiv" id="<?php echo $family; ?>">
												[<?php echo $family; ?>]
											</div>
											<?php
											$prevfam = $family;
										}
									}
									?>
								</div>
							</div>
							<?php
						}
					}
					else{
						//Display taxa
						echo '<div id="taxalist-div">';
						$voucherArr = array();
						if($showVouchers) $voucherArr = $clManager->getVoucherArr();
						$prevGroup = '';
						foreach($taxaArray as $tid => $sppArr){
							$group = $sppArr['taxongroup'];
							if($group != $prevGroup){
								$famUrl = '../taxa/index.php?taxauthid=1&taxon='.strip_tags($group).'&clid='.$clid;
								//Edit family name display style here
								?>
								<div class="family-div" id="<?php echo strip_tags($group);?>">
									<a href="<?php echo $famUrl; ?>" target="_blank" style="color:black;"><?php echo $group;?></a>
								</div>
								<?php
								$prevGroup = $group;
							}
							echo '<div id="tid-'.$tid.'" class="taxon-container">';
							//Edit species name display style here
							echo '<div class="taxon-div">';
							if(!preg_match('/\ssp\d/',$sppArr["sciname"])) echo '<a href="../taxa/index.php?taxauthid=1&taxon='.$tid.'&clid='.$clid.'" target="_blank">';
							echo '<span class="taxon-span">'.$sppArr['sciname'].'</span> ';
							if(array_key_exists("author",$sppArr)) echo $sppArr["author"];
							if(!preg_match('/\ssp\d/',$sppArr["sciname"])) echo "</a>";
							if(array_key_exists('vern',$sppArr)){
								echo ' - <span class="vern-span">'.$sppArr['vern'].'</span>';
							}
							if($clid && $clArray['dynamicsql']){
								?>
								<span class="view-specimen-span printoff">
									<a href="../collections/list.php?usethes=1&taxontype=2&taxa=<?php echo $tid."&targetclid=".$clid."&targettid=".$tid;?>" target="_blank">
										<img src="../images/list.png" style="width:12px;" title="<?php echo (isset($LANG['VIEW_RELATED'])?$LANG['VIEW_RELATED']:'View Related Specimens'); ?>" />
									</a>
								</span>
								<?php
							}
							if($isEditor){
								if(isset($sppArr['clid'])){
									$clidArr = explode(',',$sppArr['clid']);
									foreach($clidArr as $id){
										?>
										<span class="editspp" style="display:none;">
											<a href="#" onclick="return openPopup('clsppeditor.php?tid=<?php echo $tid."&clid=".$id; ?>','editorwindow');">
												<img src="../images/edit.png" style="width:13px;" title="<?php echo (isset($LANG['EDIT_DETAILS'])?$LANG['EDIT_DETAILS']:'edit details'); ?> (clid = <?php echo $id; ?>)" />
											</a>
										</span>
										<?php
									}
									if(in_array($clid, $clidArr) && $showVouchers && $clArray['dynamicsql']){
										?>
										<span class="editspp" style="margin-left:5px;display:none">
											<a href="../collections/list.php?usethes=1&taxontype=2&taxa=<?php echo $tid."&targetclid=".$clid."&targettid=".$tid.'&mode=voucher'; ?>" target="_blank">
												<img src="../images/link.png" style="width:12px;" title="<?php echo (isset($LANG['VIEW_RELATED'])?$LANG['VIEW_RELATED']:'Link Specimen Vouchers'); ?>" /><span style="font-size:70%">V</span>
											</a>
										</span>
										<?php
									}
								}
							}
							echo "</div>\n";
							if($showSynonyms && isset($sppArr['syn'])){
								echo '<div class="syn-div">['.$sppArr['syn'].']</div>';
							}
							if($showVouchers){
								$voucStr = '';
								if(array_key_exists('notes',$sppArr)) $voucStr .= $sppArr['notes'].'; ';
								if(array_key_exists($tid,$voucherArr)){
									$voucCnt = 0;
									foreach($voucherArr[$tid] as $occid => $collName){
										if($voucCnt == 4 && !$printMode){
											$voucStr .= '<a href="#" id="morevouch-'.$tid.'" onclick="return toggleVoucherDiv('.$tid.');">'.$LANG['MORE'].'...</a>'.
												'<span id="voucdiv-'.$tid.'" style="display:none;">';
										}
										$voucStr .= '<a href="#" onclick="return openIndividualPopup('.$occid.')">'.$collName.'</a>, ';
										$voucCnt++;
									}
									if($voucCnt > 4 && !$printMode) $voucStr .= '</span><a href="#" id="lessvouch-'.$tid.'" style="display:none;" onclick="return toggleVoucherDiv('.$tid.');">...'.$LANG['LESS'].'</a>';
								}
								$voucStr = trim($voucStr,' ;,');
								if($voucStr) echo '<div class="note-div">'.$voucStr.'</div>';
							}
							echo "</div>\n";
						}
						echo '</div>';
					}
					$taxaLimit = ($showImages?$clManager->getImageLimit():$clManager->getTaxaLimit());
					if($clManager->getTaxaCount() > (($pageNumber)*$taxaLimit)){
						echo '<div class="printoff" style="margin:20px;clear:both;">';
						echo '<a href="checklist.php?pagenumber='.($pageNumber+1).$argStr.'"> '.$LANG['DISPLAYNEXT'].' '.$taxaLimit.' '.$LANG['TAXA'].'...</a>';
						echo '</div>';
					}
					if(!$taxaArray) echo "<h1 style='margin:40px;'>".$LANG['NOTAXA']."</h1>";
					?>
				</div>
			</div>
			<?php
		}
		else{
			?>
			<div style="color:red;">
				<?php echo $LANG['CHECKNULL']; ?>!
			</div>
			<?php
		}
		?>
	</div>
	<?php
	if(!$printMode) include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
