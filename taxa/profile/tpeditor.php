<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TPEditorManager.php');
include_once($SERVER_ROOT.'/classes/TPDescEditorManager.php');
include_once($SERVER_ROOT.'/classes/TPImageEditorManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/taxa/profile/tpeditor.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/taxa/profile/tpeditor.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/taxa/profile/tpeditor.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:0;
$taxon = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:"";
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0;

if(!is_numeric($tid)) $tid = 0;
if(!is_numeric($tabIndex)) $tabIndex = 0;

$tEditor = null;
if($tabIndex == 1 || $tabIndex == 2){
	$tEditor = new TPImageEditorManager();
}
elseif($tabIndex == 4){
	$tEditor = new TPDescEditorManager();
}
else{
	$tEditor = new TPEditorManager();
}

$taxaArr = array();
if(!$tid && $taxon){
	if(is_numeric($taxon)) $tid = $taxon;
	else{
		$taxaArr = $tEditor->getTidFromStr($taxon);
		if($taxaArr){
			if(count($taxaArr) == 1) $tid = key($taxaArr);
		}
	}
}
$tEditor->setTid($tid);
$tid = $tEditor->getTid();

$statusStr = "";
$isEditor = false;
if($IS_ADMIN || array_key_exists("TaxonProfile",$USER_RIGHTS)) $isEditor = true;

if($isEditor && $action){
	if($action == "Edit Synonym Sort Order"){
		$synSortArr = Array();
		foreach($_REQUEST as $sortKey => $sortValue){
			if($sortValue && (substr($sortKey,0,4) == "syn-")){
				$synSortArr[substr($sortKey,4)] = $sortValue;
			}
		}
		$statusStr = $tEditor->editSynonymSort($synSortArr);
	}
	elseif($action == "Submit Common Name Edits"){
		if(!$tEditor->editVernacular($_POST)) $statusStr = $tEditor->getErrorMessage();
	}
	elseif($action == "Add Common Name"){
		if(!$tEditor->addVernacular($_POST)) $statusStr = $tEditor->getErrorMessage();
	}
	elseif($action == "Delete Common Name"){
		if(!$tEditor->deleteVernacular($_REQUEST["delvern"])) $statusStr = $tEditor->getErrorMessage();
	}
	elseif($action == 'Add Description Block'){
		if(!$tEditor->insertDescriptionBlock($_POST)){
			$statusStr = 'ERROR inserting description block: ' . $tEditor->getErrorMessage();
		}
	}
	elseif($action == 'saveDescriptionBlock'){
		if(!$tEditor->updateDescriptionBlock($_POST)){
			$statusStr = 'ERROR editing description block: '.$tEditor->getErrorMessage();
		}
	}
	elseif($action == 'Delete Description Block'){
		if(!$tEditor->deleteDescriptionBlock($_POST['tdbid'])){
			$statusStr = 'ERROR deleting description block: ' . $tEditor->getErrorMessage();
		}
	}
	elseif($action == 'remap'){
		if(!$tEditor->remapDescriptionBlock($_GET['tdbid'])){
			$statusStr = 'ERROR remapping description block: ' . $tEditor->getErrorMessage();
		}
	}
	elseif($action == 'Add Statement'){
		if(!$tEditor->addStatement($_POST)){
			$statusStr = $tEditor->getErrorMessage();
		}
	}
	elseif($action == 'saveStatementEdit'){
		if(!$tEditor->editStatement($_POST)){
			$statusStr = $tEditor->getErrorMessage();
		}
	}
	elseif($action == 'Delete Statement'){
		if(!$tEditor->deleteStatement($_POST['tdsid'])){
			$statusStr = $tEditor->getErrorMessage();
		}
	}
	elseif($action == 'Submit Image Sort Edits'){
		$imgSortArr = Array();
		foreach($_REQUEST as $sortKey => $sortValue){
			if($sortValue && substr($sortKey,0,6) == 'imgid-'){
				$imgSortArr[substr($sortKey,6)]  = $sortValue;
			}
		}
		$statusStr = $tEditor->editImageSort($imgSortArr);
	}
	elseif($action == 'Upload Image'){
		if($tEditor->loadImage($_POST)){
			$statusStr = 'Image uploaded successful';
		}
		if($tEditor->getErrorMessage()){
			$statusStr .= '<br/>'.$tEditor->getErrorMessage();
		}
	}
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['TAXON_EDITOR'] .': '.$tEditor->getSciName(); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript">
		var clientRoot = "<?php echo $CLIENT_ROOT; ?>";

		$(document).ready(function() {
			$('#tabs').tabs({
				active: <?php echo $tabIndex; ?>
			});

		});

		function checkGetTidForm(f){
			if(f.taxon.value == ""){
				alert("<?php echo $LANG['ENTER_SCINAME']; ?>");
				return false;
			}
			return true;
		}

		function submitAddImageForm(f){
			var fileBox = document.getElementById("imgfile");
			var file = fileBox.files[0];
			if(file.size>4000000){
				alert("<?php echo $LANG['IMG_TOO_LARGE']; ?>");
				return false;
			}
		}

		function openOccurrenceSearch(target) {
			occWindow=open("../../collections/misc/occurrencesearch.php?targetid="+target,"occsearch","resizable=1,scrollbars=1,width=700,height=500,left=20,top=20");
			if (occWindow.opener == null) occWindow.opener = self;
		}
	</script>
	<script src="../../js/symb/api.taxonomy.taxasuggest.js?ver=4" type="text/javascript"></script>
	<style type="text/css">
		.sectionDiv{ clear:both; }
		.sectionDiv div{ float:left }
		.labelDiv{ margin-right: 5px }
		#redirectedfrom{ font-size:16px; margin-top:5px; margin-left:10px; font-weight:bold; }
		#taxonDiv{ font-size:18px; margin-top:15px; margin-left:10px; }
		#taxonDiv a{ color:#990000; font-weight: bold; font-style: italic; }
		#taxonDiv img{ border: 0px; margin: 0px; height: 15px; }
		#familyDiv{ margin-left:20px; margin-top:0.25em; }
		.tox-dialog{ min-height: 400px }
		input{ margin:3px; border:inset; }
		hr{ margin:30px 0px; }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($taxa_admin_tpeditorMenu)?$taxa_admin_tpeditorMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php"><?php echo $LANG['HOME']; ?></a> &gt;&gt;
		<?php
		if($tid) echo '<a href="../index.php?tid='.$tid.'">'.$LANG['TAX_PROF_PUBLIC_DISP'].'</a> &gt;&gt; ';
		echo '<b>'.$LANG['TAX_PROF_EDITOR'].'</b>';
		?>
	</div>
	<div id="innertext">
		<?php
		if($tEditor->getTid()){
			if($isEditor){
				if($tEditor->isForwarded()) echo '<div id="redirectedfrom">'.$LANG['REDIRECTED_FROM'].': <i>'.$tEditor->getSubmittedValue('sciname').'</i></div>';
				echo '<div id="taxonDiv"><a href="../index.php?taxon='.$tEditor->getTid().'">'.$tEditor->getSciName().'</a> '.$tEditor->getAuthor();
				if($tEditor->getRankId() > 140) echo "&nbsp;<a href='tpeditor.php?tid=".$tEditor->getParentTid()."'><img src='../../images/toparent.png' title='".$LANG['GO_TO_PARENT']."' /></a>";
				echo "</div>\n";
				if($tEditor->getFamily()) echo '<div id="familyDiv"><b>'.$LANG['FAMILY'].':</b> '.$tEditor->getFamily().'</div>'."\n";
				if($statusStr) echo '<div style="margin:15px;font-weight:bold;font-size:120%;color:'.(stripos($statusStr,'error') !== false?'red':'green') .';">'.$statusStr.'</div>';
				?>
				<div id="tabs" style="margin:10px;">
					<ul>
						<li><a href="#commontab"><span><?php echo $LANG['SYN_VERNAC']; ?></span></a></li>
						<li><a href="tpimageeditor.php?tid=<?php echo $tEditor->getTid().'"><span>'.$LANG['IMAGES'].'</span></a></li>'; ?>
						<li><a href="tpimageeditor.php?tid=<?php echo $tEditor->getTid().'&cat=imagequicksort'.'"><span>'.$LANG['IMAGE_SORT'].'</span></a></li>'; ?>
						<li><a href="tpimageeditor.php?tid=<?php echo $tEditor->getTid().'&cat=imageadd'.'"><span>'.$LANG['ADD_IMAGE'].'</span></a></li>'; ?>
						<li><a href="tpdesceditor.php?tid=<?php echo $tEditor->getTid().'&action='.$action.'"><span>'.$LANG['DESCRIPTIONS'].'</span></a></li>'; ?>
					</ul>
					<div id="commontab">
						<?php
						//Display Common Names (vernaculars)
						$vernacularList = $tEditor->getVernaculars();
						$langArr = $tEditor->getLangArr();
						?>
						<div>
							<div style="margin:10px 0px" title="<?php echo $LANG['ADD_COMMON_NAME']; ?>">
								<b><?php echo ($vernacularList?$LANG['COMMON_NAMES']:$LANG['NO_COMMON_NAMES']); ?></b>
								<a href="#" onclick="toggle('addvern');return false;">
									<img style="border:0px;width:15px;" src="../../images/add.png"/>
								</a>
							</div>
							<div id="addvern" class="addvern" style="display:<?php echo ($vernacularList?'none':'block'); ?>;">
								<form name="addvernform" action="tpeditor.php" method="post" >
									<fieldset style="width:650px;margin:5px 0px 0px 20px;">
										<legend><b><?php echo $LANG['NEW_COMMON_NAME']; ?></b></legend>
										<div>
											<?php echo $LANG['COMMON_NAME']; ?>:
											<input name="vernname" type="text" style="width:250px" />
										</div>
										<div>
											<?php echo $LANG['LANGUAGE']; ?>:
											<select name="langid">
												<option value=""><?php echo $LANG['SEL_LANGUAGE']; ?></option>
												<?php
												foreach($langArr as $langID => $langName){
													echo '<option value="'.$langID.'" '.(strpos($langName,'('.$DEFAULT_LANG.')')?'SELECTED':'').'>'.$langName.'</option>';
												}
												?>
											</select>
										</div>
										<div>
											<?php echo $LANG['NOTES']; ?>:
											<input name="notes" type="text" style="width:500px" />
										</div>
										<div>
											<?php echo $LANG['SOURCE']; ?>:
											<input name="source" type="text" style="width:500px" />
										</div>
										<div>
											<?php echo $LANG['SORT_SEQUENCE']; ?>:
											<input name="sortsequence" style="width:40px" type="text" />
										</div>
										<div>
											<input type="hidden" name="tid" value="<?php echo $tEditor->getTid(); ?>" />
											<button id="vernsadd" name="action" style="margin-top:5px;" type="submit" value="Add Common Name" ><?php echo $LANG['ADD_COMMON_NAME']; ?></button>
										</div>
									</fieldset>
								</form>
							</div>
							<?php
							foreach($vernacularList as $lang => $vernsList){
								?>
								<div style="width:650px;margin:5px 0px 0px 15px;">
									<fieldset style="width:650px;margin:5px 0px 0px 15px;">
										<legend><b><?php echo $lang; ?></b></legend>
										<?php
										foreach($vernsList as $vid => $vernArr){
											?>
											<div style="margin-left:10px;" title="<?php echo $LANG['EDIT_COMMON_NAME']; ?>">
												<b><?php echo $vernArr['vernname']; ?></b>
												<a href="#" onclick="toggle('vid-<?php echo $vid; ?>');return false;">
													<img style="border:0px;width:12px;" src="../../images/edit.png" />
												</a>
											</div>
											<form name="updatevern" action="tpeditor.php" method="post" style="margin:15px;clear:both">
												<div class="sectionDiv">
													<div class='vid-<?php echo $vid; ?>' style='display:none;'>
														<input id="vernname" name="vernname" type="text" value="<?php echo $vernArr["vernname"]; ?>" style="width:250px" />
													</div>
												</div>
												<div class="sectionDiv">
													<div class="labelDiv"><?php echo $LANG['LANGUAGE']; ?>:</div>
													<div class='vid-<?php echo $vid; ?>'><?php echo $langArr[$vernArr['langid']]; ?></div>
													<div class='vid-<?php echo $vid; ?>' style='display:none;'>
														<select name="langid">
															<option value=""><?php echo $LANG['SEL_LANGUAGE']; ?></option>
															<?php
															foreach($langArr as $langID => $langName){
																echo '<option value="'.$langID.'" '.($vernArr['langid']==$langID?'SELECTED':'').'>'.$langName.'</option>';
															}
															?>
														</select>
													</div>
												</div>
												<div class="sectionDiv">
													<div class="labelDiv"><?php echo $LANG['NOTES']; ?>:</div>
													<div class="vid-<?php echo $vid; ?>"><?php echo $vernArr['notes']; ?></div>
													<div class="vid-<?php echo $vid; ?>" style="display:none;">
														<input id='notes' name='notes' type='text' value='<?php echo $vernArr['notes'];?>' style="width:500px" />
													</div>
												</div>
												<div class="sectionDiv">
													<div class="labelDiv"><?php echo $LANG['SOURCE']; ?>:</div>
													<div class="vid-<?php echo $vid; ?>"> <?php echo $vernArr['source']; ?></div>
													<div class="vid-<?php echo $vid; ?>" style='display:none;'>
														<input id='source' name='source' type='text' value='<?php echo $vernArr['source']; ?>' style="width:500px" />
													</div>
												</div>
												<div class="sectionDiv">
													<div class="labelDiv"><?php echo $LANG['SORT_SEQUENCE']; ?>:</div>
													<div class='vid-<?php echo $vid; ?>'><?php echo $vernArr['sort'];?></div>
													<div class='vid-<?php echo $vid; ?>' style='display:none;'>
														<input id='sortsequence' name='sortsequence' style='width:40px;' type='text' value='<?php echo $vernArr['sort']; ?>' />
													</div>
												</div>
												<div class="sectionDiv">
													<input type='hidden' name='vid' value='<?php echo $vid; ?>' />
													<input type='hidden' name='tid' value='<?php echo $tEditor->getTid();?>' />
													<div class='vid-<?php echo $vid;?>' style='display:none;'>
														<button name='action' type='submit' value='Submit Common Name Edits' ><?php echo $LANG['SUBMIT_COMMON_EDITS']; ?></button>
													</div>
												</div>
											</form>
											<div class="vid-<?php echo $vid; ?>" style="display:none;padding-top:15px;padding-left:15px;clear:both">
												<form id="delvern" name="delvern" action="tpeditor.php" method="post" onsubmit="return window.confirm('<?php echo $LANG['SURE_DELETE_COMMON']; ?>')">
													<input type="hidden" name="delvern" value="<?php echo $vid; ?>" />
													<input type="hidden" name="tid" value="<?php echo $tEditor->getTid(); ?>" />
													<button name="action" type="submit" value="Delete Common Name"><?php echo $LANG['DELETE_COMMON']; ?></button>
												</form>
											</div>
											<div style="clear:both;margin:10px 0px"><hr/></div>
											<?php
										}
										?>
									</fieldset>
								</div>
								<?php
							}
							?>
						</div>
						<hr/>
						<fieldset style="width:650px;margin:5px 0px 0px 15px;">
							<legend><b><?php echo $LANG['SYNONYMS']; ?></b></legend>
							<?php
							//Display Synonyms
							if($synonymArr = $tEditor->getSynonym()){
								?>
								<div style="float:right;" title="<?php echo $LANG['EDIT_SYN_ORDER']; ?>">
									<a href="#"  onclick="toggle('synsort');return false;"><img style="border:0px;width:12px;" src="../../images/edit.png"/></a>
								</div>
								<div style="font-weight:bold;margin-left:15px;">
									<ul>
										<?php
										foreach($synonymArr as $tidKey => $valueArr){
											 echo '<li>'.$valueArr["sciname"].'</li>';
										}
										?>
									</ul>
								</div>
								<div class="synsort" style="display:none;">
									<form name="synsortform" action="tpeditor.php" method="post">
										<input type="hidden" name="tid" value="<?php echo $tEditor->getTid(); ?>" />
										<fieldset style='margin:5px 0px 5px 5px;margin-left:20px;width:350px;'>
										<legend><b><?php echo $LANG['SYN_SORT_ORDER']; ?></b></legend>
										<?php
										foreach($synonymArr as $tidKey => $valueArr){
											?>
												<div>
													<b><?php echo $valueArr["sortsequence"]; ?></b> -
													<?php echo $valueArr["sciname"]; ?>
												</div>
												<div style="margin:0px 0px 5px 10px;">
													new sort value:
													<input type="text" name="syn-<?php echo $tidKey; ?>" style="width:35px;border:inset;" />
												</div>
												<?php
											}
											?>
											<div>
												<input type="submit" name="action" value="<?php echo $LANG['EDIT_SYN_ORDER']; ?>" />
											</div>
										</fieldset>
									</form>
								</div>
								<?php
							}
							else{
								echo '<div style="margin:20px 0px"><b>'.$LANG['NO_SYN_LINK'].'</b></div>';
							}
							?>
							<div style="margin:10px;">
								*<?php echo $LANG['MOST_SYN_IN_TAX_THES'].' <a href="../../sitemap.php">'.$LANG['SITEMAP'].'</a>).'; ?>
							</div>
						</fieldset>
					</div>
				</div>
				<?php
			}
			else{
				?>
				<div style="margin:30px;">
					<h2><?php echo $LANG['NOT_AUTH']; ?></h2>
				</div>
				<?php
			}
		}
		else{
			?>
			<div style="margin:20px;">
				<form name="gettidform" action="tpeditor.php" method="post" onsubmit="return checkGetTidForm(this);">
					<b><?php echo $LANG['SCINAME']; ?>: </b><input id="taxa" name="taxon" value="<?php echo $taxon; ?>" size="40" />
					<input type="hidden" name="tabindex" value="<?php echo $tabIndex; ?>" />
					<button type="submit" name="action" value="Edit Taxon" ><?php echo $LANG['EDIT_TAXON']; ?></button>
				</form>
			</div>
			<?php
			if(count($taxaArr) > 1){
				echo '<div style="margin:15px">'.$LANG['MORE_THAN_ONE_TAXON'].': </div>';
				echo '<div style="margin:10px">';
				foreach($taxaArr as $tidKey => $sciArr){
					$outStr = '<b>'.$sciArr['sciname'];
					if($sciArr['rankid'] > 179) $outStr = '<i>'.$outStr.'</i> ';
					$outStr .= $sciArr['author'].'</b> ';
					if(isset($sciArr['rankname'])) $outStr .= '- '.$sciArr['rankname'].' rank ';
					if(isset($sciArr['kingdom'])) $outStr .= ' ('.$sciArr['kingdom'].')';
					echo '<div><a href="tpeditor.php?tid='.$tidKey.'">'.$outStr.'</a></div>';
				}
				echo '</div>';
			}
			else{
				echo '<div style="margin:15px">';
				if($taxon) echo "<i>".ucfirst($taxon)."</i> ".$LANG['NOT_IN_SYSTEM'].".";
				echo '</div>';
			}
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
