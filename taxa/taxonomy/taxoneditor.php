<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyEditorManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxoneditor.' . $LANG_TAG . '.php'))
	include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxoneditor.' . $LANG_TAG . '.php');
	else include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxoneditor.en.php');

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../taxa/taxonomy/taxoneditor.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$tid = $_REQUEST['tid'] ?? 0;
$taxAuthId = array_key_exists('taxauthid', $_REQUEST)?$_REQUEST['taxauthid']:1;
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;
$submitAction = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

//Sanitation
if(!is_numeric($tid)) $tid = 0;
if(!is_numeric($taxAuthId)) $taxAuthId = 1;
if(!is_numeric($tabIndex)) $tabIndex = 0;

$taxonEditorObj = new TaxonomyEditorManager();
$taxonEditorObj->setTid($tid);
$taxonEditorObj->setTaxAuthId($taxAuthId);

$isEditor = false;
if($IS_ADMIN || array_key_exists("Taxonomy",$USER_RIGHTS)) $isEditor = true;

$statusStr = '';
if($isEditor){
	if(array_key_exists('taxonedits',$_POST)){
		$statusStr = $taxonEditorObj->submitTaxonEdits($_POST);
	}
	elseif($submitAction == 'updatetaxstatus'){
		$statusStr = $taxonEditorObj->submitTaxStatusEdits($_POST['parenttid'],$_POST['tidaccepted']);
	}
	elseif(array_key_exists("synonymedits",$_REQUEST)){
		$statusStr = $taxonEditorObj->submitSynonymEdits($_POST['tidsyn'], $tid, $_POST['unacceptabilityreason'], $_POST['notes'], $_POST['sortsequence']);
	}
	elseif($submitAction == 'linkToAccepted'){
		$deleteOther = array_key_exists("deleteother",$_REQUEST)?true:false;
		$statusStr = $taxonEditorObj->submitAddAcceptedLink($_REQUEST["tidaccepted"],$deleteOther);
	}
	elseif(array_key_exists('deltidaccepted',$_REQUEST)){
		$statusStr = $taxonEditorObj->removeAcceptedLink($_REQUEST['deltidaccepted']);
	}
	elseif(array_key_exists("changetoaccepted",$_REQUEST)){
		$tidAccepted = $_REQUEST["tidaccepted"];
		$switchAcceptance = array_key_exists("switchacceptance",$_REQUEST)?true:false;
		$statusStr = $taxonEditorObj->submitChangeToAccepted($tid,$tidAccepted,$switchAcceptance);
	}
	elseif($submitAction == 'changeToNotAccepted'){
		$tidAccepted = $_REQUEST["tidaccepted"];
		$statusStr = $taxonEditorObj->submitChangeToNotAccepted($tid,$tidAccepted,$_POST['unacceptabilityreason'],$_POST['notes']);
	}
	elseif($submitAction == 'updatehierarchy'){
		$statusStr = $taxonEditorObj->rebuildHierarchy($tid);
	}
	elseif($submitAction == 'remapTaxon'){
		$remapStatus = $taxonEditorObj->transferResources($_REQUEST['remaptid']);
		if($taxonEditorObj->getWarningArr()) $statusStr = $LANG['FOLLOWING_WARNINGS'] . ': ' . implode(';',$taxonEditorObj->getWarningArr());
		if($remapStatus){
			$statusStr = $LANG['SUCCESS_REMAPPING'] . ' ' . $statusStr;
			header('Location: taxonomydisplay.php?target=' . $_REQUEST["genusstr"] . '&statusstr=' . $statusStr);
		}
		else $statusStr = $taxonEditorObj->getErrorMessage();
	}
	elseif($submitAction == 'deleteTaxon'){
		$delStatus = $taxonEditorObj->deleteTaxon();
		if($taxonEditorObj->getWarningArr()) $statusStr = $LANG['FOLLOWING_WARNINGS'] . ': ' . implode(';',$taxonEditorObj->getWarningArr());
		if($delStatus){
			$statusStr = $LANG['SUCCESS_DELETING'] . ' ' . $statusStr;
			header('Location: taxonomydisplay.php?statusstr=' . $statusStr);
		}
		else $statusStr = $taxonEditorObj->getErrorMessage();
	}
	$taxonEditorObj->setTaxon();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . " " . $LANG['TAX_EDITOR'] . ": " . $tid; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>"/>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script>
		var tid = <?php echo $taxonEditorObj->getTid(); ?>;
		var tabIndex = <?php echo $tabIndex; ?>;
	</script>
	<script src="../../js/symb/taxa.taxonomyeditor.js?ver=2"></script>
	<style type="text/css">
		.editDiv{ clear:both; }
		.editLabel{ float:left; font-weight:bold; }
		.editfield{ float:left; margin-left:5px; }
		.tsedit{ float:left; margin-left:5px; }
		.headingDiv{ font-size:110%; font-weight:bold; padding-top:10px; }
		.taxonDiv{ font-size:1.125rem; margin-top:15px; margin-left:10px; }
		.taxonDiv a{ color:#990000; font-weight: bold; font-style: italic; }
		.taxonDiv img{ border: 0px; margin: 0px; height: 15px; }
	</style>
</head>
<body>
<?php
	$displayLeftMenu = (isset($taxa_admin_taxonomyeditorMenu)?$taxa_admin_taxonomyeditorMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($taxa_admin_taxonomyeditorCrumbs)){
		if($taxa_admin_taxonomyeditorCrumbs){
			echo "<div class='navpath'>";
			echo $taxa_admin_taxonomyeditorCrumbs;
			echo " <b>Taxonomy Editor</b>";
			echo "</div>";
		}
	}
	else{
		?>
		<div class="navpath">
			<a href="../../index.php"><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
			<a href="taxonomydisplay.php"><?php echo htmlspecialchars($LANG['TAX_TREE_VIEW'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
			<b><?php echo $LANG['TAXONOMY_EDITOR']; ?></b>
		</div>
		<?php
	}
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">
			<?php echo $LANG['TAX_EDITOR'] . ": <i>" . $taxonEditorObj->getSciName() . "</i> " . $taxonEditorObj->getAuthor() . " [" . $taxonEditorObj->getTid() . "]"; ?>
		</h1>
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="color:<?php echo (strpos($statusStr,$LANG['SUCCESS']) !== false?'green':'red'); ?>;margin:15px;">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php
		}
		if($isEditor && $tid){
			$hierarchyArr = $taxonEditorObj->getHierarchyArr()
			?>
			<div style="float:right;" title="<?php echo $LANG['GO_TAX_DISPLAY']; ?>">
				<a href="taxonomydisplay.php?target=<?php echo htmlspecialchars($taxonEditorObj->getUnitName1(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);?>&showsynonyms=1">
					<img style='border:0px;width:1.3em;' src='../../images/toparent.png'/>
				</a>
			</div>
			<div style="float:right;" title="<?php echo $LANG['ADD_NEW_TAXON']; ?>">
				<a href="taxonomyloader.php">
					<img style='border:0px;width:1.3em;' src='../../images/add.png'/>
				</a>
			</div>
			<h1>
				<?php
				echo "<div class='taxonDiv'><a href='../profile/tpeditor.php?tid=" . htmlspecialchars($taxonEditorObj->getTid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'>";
				echo "View Taxon Profile Editor";
				echo "</a></div>";
				?>
			</h1>
			<div id="tabs" class="taxondisplaydiv">
				<ul>
					<li><a href="#editorDiv"><?php echo htmlspecialchars($LANG['EDITOR'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
					<li><a href="#taxonstatusdiv"><?php echo htmlspecialchars($LANG['TAX_STATUS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
					<li><a href="#hierarchydiv"><?php echo htmlspecialchars($LANG['HIERARCHY'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
					<li><a href="taxonomychildren.php?tid=<?php echo htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&taxauthid=' . htmlspecialchars($taxAuthId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($LANG['CHILDREN_TAXA'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
					<li><a href="taxonomydelete.php?tid=<?php echo htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&genusstr=<?php echo htmlspecialchars($taxonEditorObj->getUnitName1(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($LANG['DELETE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
				</ul>
				<div id="editorDiv" style="height:400px;">
					<div style="float:right;cursor:pointer;" onclick="toggleEditFields()" title="<?= $LANG['TOGGLE_TAXON_EDITING'] ?>">
						<img style='width:1.3em;border:0px;' src='../../images/edit.png'/>
					</div>
					<form id="taxoneditform" name="taxoneditform" action="taxoneditor.php" method="post" onsubmit="return validateTaxonEditForm(this)">
						<div class="editDiv">
							<div class="editLabel"><?php echo $LANG['UNITNAME1']; ?>: </div>
							<div class="editfield">
								<?php
								$unitInd1 = $taxonEditorObj->getUnitInd1();
								echo ($unitInd1?$unitInd1.' ':'').$taxonEditorObj->getUnitName1();
								?>
							</div>
							<div class="editfield" style="display:none;">
								<select name="unitind1">
									<option value=""></option>
									<option value="&#215;" <?php echo ($unitInd1 && (mb_ord($unitInd1)==215 || strtolower($unitInd1) == 'x')?'selected':''); ?>>&#215;</option>
									<option value="&#8224;" <?php echo ($unitInd1 && mb_ord($unitInd1)==8224?'selected':''); ?>>&#8224;</option>
								</select>
								<input type="text" id="unitname1" name="unitname1" style="width:300px;border-style:inset;" value="<?php echo $taxonEditorObj->getUnitName1(); ?>" />
							</div>
						</div>
						<div class="editDiv">
							<div class="editLabel"><?php echo $LANG['UNITNAME2']; ?>: </div>
							<div class="editfield">
								<?php
								$unitInd2 = $taxonEditorObj->getUnitInd2();
								echo ($unitInd2?$unitInd2.' ':'').$taxonEditorObj->getUnitName2();
								?>
							</div>
							<div class="editfield" style="display:none;">
								<select name="unitind2">
									<option value=""></option>
									<option value="&#215;" <?php echo (ord($unitInd2 ?? '') == 195 || strtolower($unitInd2 ?? '') == 'x'?'selected':''); ?>>&#215;</option>
								</select>
								<input type="text" id="unitname2" name="unitname2" style="width:300px;border-style:inset;" value="<?php echo $taxonEditorObj->getUnitName2(); ?>" />
							</div>
						</div>
						<div class="editDiv">
							<div class="editLabel"><?php echo $LANG['UNITNAME3']; ?>: </div>
							<div class="editfield">
								<?php echo $taxonEditorObj->getUnitInd3()." ".$taxonEditorObj->getUnitName3();?>
							</div>
							<div class="editfield" style="display:none;">
								<input type="text" id="unitind3" name="unitind3" style="width:50px;border-style:inset;" value="<?php echo $taxonEditorObj->getUnitInd3(); ?>" />
								<input type="text" id="unitname3" name="unitname3" style="width:300px;border-style:inset;" value="<?php echo $taxonEditorObj->getUnitName3(); ?>" />
							</div>
						</div>
						<div class="editDiv">
							<div class="editLabel"><?php echo $LANG['AUTHOR']; ?>: </div>
							<div class="editfield">
								<?php echo $taxonEditorObj->getAuthor();?>
							</div>
							<div class="editfield" style="display:none;">
								<input type="text" id="author" name="author" style="width:400px;border-style:inset;" value="<?php echo $taxonEditorObj->getAuthor(); ?>" />
							</div>
						</div>
						<div id="kingdomdiv" class="editDiv">
							<div  class="editLabel"><?php echo $LANG['KINGDOM']; ?>: </div>
							<div class="editfield">
								<?php
								echo $taxonEditorObj->getKingdomName();
								?>
							</div>
						</div>
						<div class="editDiv">
							<div class="editLabel"><?php echo $LANG['RANK_NAME']; ?>: </div>
							<div class="editfield">
								<?php echo ($taxonEditorObj->getRankName()?$taxonEditorObj->getRankName(): $LANG['NON_RANKED_NODE'] ); ?>
							</div>
							<div class="editfield" style="display:none;">
								<select id="rankid" name="rankid">
									<option value="0"><?php echo $LANG['NON_RANKED_NODE']; ?></option>
									<option value="">---------------------------------</option>
									<?php
									$rankArr = $taxonEditorObj->getRankArr();
									foreach($rankArr as $rankId => $nameArr){
										foreach($nameArr as $rName){
											echo '<option value="' . $rankId . '" ' . ($taxonEditorObj->getRankId()==$rankId?'SELECTED':'') . '>' . $rName . '</option>';
										}
									}
									?>
								</select>
							</div>
						</div>
						<div class="editDiv">
							<div class="editLabel"><?php echo $LANG['NOTES']; ?>: </div>
							<div class="editfield">
								<?php echo $taxonEditorObj->getNotes();?>
							</div>
							<div class="editfield" style="display:none;width:90%;">
								<input type="text" id="notes" name="notes" style="width:100%;" value="<?php echo $taxonEditorObj->getNotes(); ?>" />
							</div>
						</div>
						<div class="editDiv">
							<div class="editLabel"><?php echo $LANG['SOURCE']; ?>: </div>
							<div class="editfield">
								<?php
								$safeSource = $taxonEditorObj->getSource() ?? '';
								if(stripos($safeSource, '<a ') === false){
									$safeSource = htmlspecialchars($safeSource);
								}
								echo $safeSource;
								?>
							</div>
							<div class="editfield" style="display:none;width:90%;">
								<input type="text" id="source" name="source" style="width:100%;" value="<?php echo htmlspecialchars($safeSource); ?>" />
							</div>
						</div>
						<div class="editDiv">
							<div class="editLabel"><?php echo $LANG['LOC_SECURITY']; ?>: </div>
							<div class="editfield">
								<?php
									switch($taxonEditorObj->getSecurityStatus()){
										case 0:
											echo $LANG['SHOW_ALL_LOC'];
											break;
										case 1:
											echo $LANG['HIDE_LOC'];
											break;
										default:
											echo $LANG['LOC_SEC_NOT_SET'];
											break;
									}
								?>
							</div>
							<div class="editfield" style="display:none;">
								<select id="securitystatus" name="securitystatus">
									<option value="0"><?php echo $LANG['SEL_LOC_SETTING']; ?></option>
									<option value="0">---------------------------------</option>
									<option value="0" <?php if($taxonEditorObj->getSecurityStatus()==0) echo "SELECTED"; ?>><?php echo $LANG['SHOW_ALL_LOC']; ?></option>
									<option value="1" <?php if($taxonEditorObj->getSecurityStatus()==1) echo "SELECTED"; ?>><?php echo $LANG['HIDE_LOC']; ?></option>
								</select>
								<input type='hidden' name='securitystatusstart' value='<?php echo $taxonEditorObj->getSecurityStatus(); ?>' />
							</div>
						</div>
						<div class="editfield" style="display:none;clear:both;margin:15px 0px">
							<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid(); ?>" />
							<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>">
							<button type="submit" id="taxoneditsubmit" name="taxonedits" value="submitEdits" ><?php echo $LANG['SUBMIT_EDITS']; ?></button>
						</div>
					</form>
				</div>
				<div id="taxonstatusdiv" style="min-height:400px;">
					<fieldset style="width:95%;">
						<legend><b><?php echo $LANG['TAX_PLACEMENT']; ?></b></legend>
						<div style="padding:3px 7px;margin:-12px -10px 5px 0px;float:right;">
							<form name="taxauthidform" action="taxoneditor.php" method="post">
								<select name="taxauthid" onchange="this.form.submit()">
									<option value="1"><?php echo $LANG['DEFAULT_TAX']; ?></option>
									<option value="1">----------------------------</option>
									<?php
										$ttIdArr = $taxonEditorObj->getTaxonomicThesaurusIds();
										foreach($ttIdArr as $ttID => $ttName){
											echo '<option value='.$ttID.' '.($taxAuthId==$ttID?'SELECTED':'').'>'.$ttName.'</option>';
										}
									?>
								</select>
								<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid(); ?>" />
								<input type="hidden" name="tabindex" value="1" />
							</form>
						</div>
						<div style="font-size:120%;font-weight:bold;"><?php echo $LANG['STATUS']; ?>:
							<span style='color:red;'>
								<?php
								switch($taxonEditorObj->getIsAccepted()){
									case -2:		//In conflict, needs to be resolved
										echo $LANG['IN_CONFLICT'];
										break;
									case -1:		//Taxonomic status not yet assigned
										echo $LANG['NOT_YET_DEFINED'];
										break;
									case 0:			//Not Accepted
										echo $LANG['NOT_ACCEPTED'];
										break;
									case 1:			//Accepted
										echo $LANG['ACCEPTED'];
										break;
								}
								?>
							</span>
						</div>
						<div style="clear:both;margin:10px;">
							<div style="float:right;">
								<a href="#" onclick="toggle('tsedit');return false;"><img style='width:1.3em;border:0px;' src='../../images/edit.png'/></a>
							</div>
							<div style="float:left">
								<form name="taxstatusform" action="taxoneditor.php" method="post">
									<?php
									if($taxonEditorObj->getRankId() > 140 && $taxonEditorObj->getFamily()){
										?>
										<div class="editDiv">
											<div class="editLabel"><?php echo $LANG['FAMILY']; ?>: </div>
											<div class="editField">
												<?php echo $taxonEditorObj->getFamily();?>
											</div>
										</div>
										<?php
									}
									?>
									<div class="editDiv">
										<div class="editLabel"><?php echo $LANG['PARENT_TAXON']; ?>: </div>
										<div class="tsedit">
											<?php echo '<a href="taxoneditor.php?tid=' . htmlspecialchars($taxonEditorObj->getParentTid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . '<i>' . htmlspecialchars($taxonEditorObj->getParentNameFull(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</i></a>';?>
										</div>
										<div class="tsedit" style="display:none;margin:3px;">
											<input id="parentstr" name="parentstr" type="text" value="<?php echo $taxonEditorObj->getParentName(); ?>" style="width:450px" />
											<input name="parenttid" type="hidden" value="<?php echo $taxonEditorObj->getParentTid(); ?>" />
										</div>
									</div>
									<div class="tsedit" style="display:none;clear:both;">
										<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid(); ?>" />
										<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>">
										<?php
										$aArr = $taxonEditorObj->getAcceptedArr();
										$aStr = key($aArr);
										?>
										<input type="hidden" name="tidaccepted" value="<?php echo ($taxonEditorObj->getIsAccepted()==1?$taxonEditorObj->getTid():$aStr); ?>" />
										<input type="hidden" name="tabindex" value="1" />
										<input type="hidden" name="submitaction" value="updatetaxstatus" />
										<button type="button" name="taxstatuseditsubmit" onclick="submitTaxStatusForm(this.form)"><?= $LANG['SUBMIT_UPPER_EDITS'] ?></button>
									</div>
								</form>
							</div>
						</div>
						<div id="AcceptedDiv" style="margin-top:30px;clear:both;">
							<?php
							if($taxonEditorObj->getIsAccepted() <> 1){	//Is Not Accepted
								$acceptedArr = $taxonEditorObj->getAcceptedArr();
								?>
								<div class="headingDiv"><?php echo $LANG['ACCEPTED_TAXON']; ?></div>
								<div style="float:right;">
									<a href="#" onclick="toggle('acceptedits');return false;"><img style="border:0px;width:1.3em;" src="../../images/edit.png" /></a>
								</div>
								<?php
								if($acceptedArr){
									echo "<ul>\n";
									foreach($acceptedArr as $tidAccepted => $linkedTaxonArr){
										echo "<li id='acclink-".$tidAccepted."'>\n";
										echo "<a href='taxoneditor.php?tid=" . htmlspecialchars($tidAccepted, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "&taxauthid=" . htmlspecialchars($taxAuthId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'><i>" . htmlspecialchars($linkedTaxonArr["sciname"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</i></a> " . htmlspecialchars($linkedTaxonArr["author"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "\n";
										if(count($acceptedArr)>1){
											echo '<span class="acceptedits" style="display:none;"><a href="taxoneditor.php?tabindex=1&tid=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&deltidaccepted=' . htmlspecialchars($tidAccepted, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&taxauthid=' . htmlspecialchars($taxAuthId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
											echo '<img style="border:0px;width:1.3em;" src="../../images/del.png" />';
											echo '</a></span>';
										}
										if($linkedTaxonArr["usagenotes"]){
											echo "<div style='margin-left:10px;'>";
											if($linkedTaxonArr["usagenotes"]) echo "<u>Notes</u>: ".$linkedTaxonArr["usagenotes"];
											echo "</div>\n";
										}
										echo "</li>\n";
									}

									echo "</ul>\n";
								}
								else{
									echo "<div style='margin:20px;'>" . $LANG['ACCEPTED_NOT_DESIGNATED'] . "</div>\n";
								}
								?>
								<div class="acceptedits" style="display:none;">
									<form id="accepteditsform" name="accepteditsform" action="taxoneditor.php" method="post" onsubmit="return verifyLinkToAcceptedForm(this);" >
										<fieldset style="width:80%;margin:20px;padding:15px">
											<legend><b><?php echo $LANG['LINK_TO_OTHER_NAME']; ?></b></legend>
											<div>
												<?php echo $LANG['ACCEPTED_TAXON']; ?>:
												<input id="aefacceptedstr" name="acceptedstr" type="text" style="width:450px;" />
												<input name="tidaccepted" type="hidden" />
											</div>
											<div>
												<input type="checkbox" name="deleteother" checked /> <?php echo $LANG['REMOVE_OTHER_LINKS']; ?>
											</div>
											<div>
												<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid();?>" />
												<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>" />
												<input type="hidden" name="tabindex" value="1" />
												<button name="submitaction" type="submit" value="linkToAccepted"><?php echo $LANG['ADD_LINK']; ?></button>
											</div>
										</fieldset>
									</form>
									<form id="changetoacceptedform" name="changetoacceptedform" action="taxoneditor.php" method="post">
										<fieldset style="width:80%;margin:20px;padding:15px;">
											<legend><b><?php echo $LANG['CHANGE_TO_ACCEPTED']; ?></b></legend>
											<?php
											$acceptedTid = key($acceptedArr);
											if($acceptedArr && count($acceptedArr)==1){
												if(!array_key_exists($acceptedTid, $hierarchyArr)){
													?>
													<div>
														<input type="checkbox" name="switchacceptance" value="1" checked /> <?php echo $LANG['SWITCH_ACCEPTANCE']; ?>
													</div>
													<?php
												}
											}
											?>
											<div>
												<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid();?>" />
												<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>" />
												<input type="hidden" name="tidaccepted" value="<?php echo $aStr; ?>" />
												<input type="hidden" name="tabindex" value="1" />
												<button type='submit' id='changetoacceptedsubmit' name='changetoaccepted' value='Change Status to Accepted'><?php echo $LANG['CHANGE_STATUS_ACCEPTED']; ?></button>
											</div>
										</fieldset>
									</form>
								</div>
							<?php
							}
							?>
						</div>
						<div id="SynonymDiv" style="clear:both;padding-top:15px;">
							<?php
							if($taxonEditorObj->getIsAccepted() <> 0){	//Is Accepted
								?>
								<div class="headingDiv"><?php echo $LANG['SYNONYMS']; ?></div>
								<div style="float:right;">
									<a href="#"  onclick="toggle('tonotaccepted');return false;"><img style='border:0px;width:1.3em;' src='../../images/edit.png'/></a>
								</div>
								<ul>
								<?php
								$synonymArr = $taxonEditorObj->getSynonyms();
								if($synonymArr){
									foreach($synonymArr as $tidSyn => $synArr){
										echo '<li> ';
										echo '<a href="taxoneditor.php?tid=' . htmlspecialchars($tidSyn, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&taxauthid=' . htmlspecialchars($taxAuthId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '"><i>' . htmlspecialchars($synArr['sciname'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</i></a> ' . htmlspecialchars($synArr['author'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ';
										echo '<a href="#" onclick="toggle(\'syn-'.$tidSyn.'\');">';
										echo '<img style="border:0px;width:1.3em;" src="../../images/edit.png" />';
										echo '</a>';
										if($synArr["notes"] || $synArr["unacceptabilityreason"]){
											if($synArr["unacceptabilityreason"]){
												echo "<div style='margin-left:10px;'>";
												echo "<u>" . $LANG['REASON'] . "</u>: " . htmlspecialchars($synArr["unacceptabilityreason"]);
												echo "</div>";
											}
											if($synArr["notes"]){
												echo "<div style='margin-left:10px;'>";
												echo "<u>" . $LANG['NOTES'] . "</u>: " . htmlspecialchars($synArr["notes"]);
												echo "</div>";
											}
										}
										echo '</li>';
										?>
										<fieldset id="syn-<?php echo $tidSyn;?>" style="display:none;">
											<legend><b><?php echo $LANG['SYN_LINK_EDITOR']; ?></b></legend>
											<form id="synform-<?php echo $tidSyn;?>" name="synform-<?php echo $tidSyn;?>" action="taxoneditor.php" method="post">
												<div style="clear:both;">
													<?php echo $LANG['UNACCEPT_REASON']; ?>:
													<input id='unacceptabilityreason' name='unacceptabilityreason' type='text' style="width:400px;" value='<?php echo htmlspecialchars($synArr['unacceptabilityreason'] ?? ''); ?>' />
												</div>
												<div>
													<?php echo $LANG['NOTES']; ?>:
													<input id='notes' name='notes' type='text' style="width:400px;" value='<?php echo htmlspecialchars($synArr['notes'] ?? ''); ?>' />
												</div>
												<div>
													<?php echo $LANG['SORT_SEQ']; ?>:
													<input id='sortsequence' name='sortsequence' type='text' style="width:60px;" value='<?php echo $synArr['sortsequence']; ?>' />
												</div>
												<div>
													<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid(); ?>" />
													<input type="hidden" name="tidsyn" value="<?php echo $tidSyn; ?>" />
													<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>">
													<input type="hidden" name="tabindex" value="1" />
													<button type="submit" id="syneditsubmit" name="synonymedits" value="submitChanges"><?php echo $LANG['SUBMIT_EDITS']; ?></button>
												</div>
											</form>
										</fieldset>
										<?php
									}
									?>
								</ul>
								<?php
								}
								else echo "<div style='margin:20px;'>" . $LANG['NO_SYN_LINKED_TAXON'] . "</div>";
								$hasAcceptedChildren = $taxonEditorObj->hasAcceptedChildren();
								?>
								<div id="tonotaccepted" style="display:none;">
									<form name="changeToNotAcceptedForm" action="taxoneditor.php" method="post" onsubmit="return verifyChangeToNotAcceptedForm(this);">
										<fieldset style="width:90%px;">
											<legend><b><?php echo $LANG['CHANGE_NOT_ACCEPTED']; ?></b></legend>
											<div style="margin:5px;">
												<?php echo $LANG['ACCEPTED_NAME']; ?>:
												<input id="ctnafacceptedstr" name="acceptedstr" type="text" style="width:550px;" />
												<input name="tidaccepted" type="hidden" value="" />
											</div>
											<div style="margin:5px;">
												<?php echo $LANG['REASON']; ?>:
												<input name="unacceptabilityreason" type="text" style="width:90%;" />
											</div>
											<div style="margin:5px;">
												<?php echo $LANG['NOTES']; ?>:
												<input name="notes" type="text" style="width:90%;" />
											</div>
											<div style="margin:5px;">
												<input name="tid" type="hidden" value="<?php echo $taxonEditorObj->getTid();?>" />
												<input name="taxauthid" type="hidden" value="<?php echo $taxAuthId;?>">
												<input name="tabindex" type="hidden" value="1" />
												<button name="submitaction" type="submit" value="changeToNotAccepted" <?php echo ($hasAcceptedChildren?'disabled':'')?>><?php echo $LANG['CHANGE_STAT_NOT_ACCEPT']; ?></button>
											</div>
											<?php
											if($hasAcceptedChildren) echo '<div style="margin:5px;color:orange;font-weight:bold;">' . $LANG['TAX_CANNOT_BE_NOT_ACCEPTED'] . '</div>';
											?>
											<div style="margin:5px;">
												* <?php echo $LANG['SYNONYMS_TRANSFERRED']; ?>
											</div>
										</fieldset>
									</form>
								</div>
								<?php
							}
							?>
						</div>
					</fieldset>
				</div>
				<div id="hierarchydiv" style="height:400px;">
					<fieldset style="width:420px;padding:25px;">
						<legend><b><?php echo $LANG['QUERY_HIERARCHY']; ?></b></legend>
						<div style="float:right;" title="<?php echo $LANG['REBUILD_HIERARCHY']; ?>">
							<form name="updatehierarchyform" action="taxoneditor.php" method="post">
								<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid(); ?>"/>
								<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>">
								<input type="hidden" name="submitaction" value="updatehierarchy" />
								<input type="hidden" name="tabindex" value="2" />
								<input type="image" name="imagesubmit" src="../../images/undo.png" style="width:20px;"/>
							</form>
						</div>
						<?php
						if($hierarchyArr){
							$indent = 0;
							foreach($hierarchyArr as $hierTid => $hierSciname){
								echo '<div style="margin-left:'.$indent.'px;">';
								echo '<a href="taxoneditor.php?tid=' . htmlspecialchars($hierTid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($hierSciname, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
								echo "</div>\n";
								$indent += 10;
							}
							echo '<div style="margin-left:'.$indent.'px;">';
							echo '<a href="taxoneditor.php?tid=' . htmlspecialchars($taxonEditorObj->getTid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($taxonEditorObj->getSciName(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
							echo "</div>\n";
						}
						else{
							echo "<div style='margin:10px;'>" . $LANG['EMPTY'] . "</div>";
						}
						?>
					</fieldset>
				</div>
			</div>
		<?php
		}
		else{
			if(!$tid){
				if($statusStr != 'SUCCESS: taxon deleted!'){
					echo "<div>" . $LANG['TARGET_TAXON_MISSING'] . "</div>";
				}
			}
			else{
				?>
				<div style="margin:30px;font-weight:bold;font-size:120%;">
					<?php echo $LANG['NOT_AUTH']; ?>
				</div>
				<?php
			}
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>