<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyCleaner.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/cleaning/taxonomycleaner.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/cleaning/taxonomycleaner.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/cleaning/taxonomycleaner.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/taxonomycleaner.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST["collid"]:0;
$autoClean = array_key_exists('autoclean',$_POST)?$_POST['autoclean']:0;
$targetKingdom = array_key_exists('targetkingdom',$_POST)?$_POST['targetkingdom']:0;
$taxResource = array_key_exists('taxresource',$_POST)?$_POST['taxresource']:array();
$startIndex = array_key_exists('startindex',$_POST)?$_POST['startindex']:'';
$limit = array_key_exists('limit',$_POST)?$_POST['limit']:20;
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

$cleanManager = new TaxonomyCleaner();
if(is_array($collid)) $collid = implode(',',$collid);
$activeCollArr = explode(',', $collid);

foreach($activeCollArr as $k => $id){
	if(!isset($USER_RIGHTS["CollAdmin"]) || !in_array($id,$USER_RIGHTS["CollAdmin"])) unset($activeCollArr[$k]);
}
if(!$activeCollArr && strpos($collid, ',')) $collid = 0;
$cleanManager->setCollId($IS_ADMIN?$collid:implode(',',$activeCollArr));

$isEditor = false;
if($IS_ADMIN){
	$isEditor = true;
}
elseif($activeCollArr){
	$isEditor = true;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $DEFAULT_TITLE.' '.$LANG['OCC_TAX_CLEAN']; ?></title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
		<script>

			var cache = {};
			$( document ).ready(function() {
				$(".displayOnLoad").show();
				$(".hideOnLoad").hide();

				$(".taxon").each(function(){
					$( this ).autocomplete({
						minLength: 2,
						autoFocus: true,
						source: function( request, response ) {
							var term = request.term;
							if ( term in cache ) {
								response( cache[ term ] );
								return;
							}
							$.getJSON( "rpc/taxasuggest.php", request, function( data, status, xhr ) {
								cache[ term ] = data;
								response( data );
							});
						},
						change: function(event,ui) {
							if(ui.item == null && this.value.trim() != ""){
								alert("<?php echo $LANG['SCINAME_NOT_FOUND']; ?>");
								this.focus();
								this.form.tid.value = "";
							}
						},
						focus: function( event, ui ) {
							this.form.tid.value = ui.item.id;
						},
						select: function( event, ui ) {
							this.form.tid.value = ui.item.id;
						}
					});
				});
			});

			function remappTaxon(oldName,targetTid,idQualifier,msgCode){
				$.ajax({
					type: "POST",
					url: "rpc/remaptaxon.php",
					dataType: "json",
					data: { collid: "<?php echo $collid; ?>", oldsciname: oldName, tid: targetTid, idq: idQualifier }
				}).done(function( res ) {
					if(res == "1"){
						$("#remapSpan-"+msgCode).text("<?php echo ' >>> '.$LANG['REMAP_SUCCESS']; ?>");
						$("#remapSpan-"+msgCode).css('color', 'green');
					}
					else{
						$("#remapSpan-"+msgCode).text("<?php echo ' >>> '.$LANG['REMAP_FAIL']; ?>");
						$("#remapSpan-"+msgCode).css('color', 'orange');
					}
				});
				return false;
			}

			function batchUpdate(f, oldName, itemCnt){
				if(f.tid.value == ""){
					alert("<?php echo $LANG['TAXON_NOT_FOUND']; ?>");
					return false;
				}
				else{
					remappTaxon(oldName, f.tid.value, '', itemCnt+"-c");
				}
			}

			function checkSelectCollidForm(f){
				var formVerified = false;
				for(var h=0;h<f.length;h++){
					if(f.elements[h].name == "collid[]" && f.elements[h].checked){
						formVerified = true;
						break;
					}
				}
				if(!formVerified){
					alert("<?php echo $LANG['CHOOSE_ONE']; ?>");
					return false;
				}
				return true;
			}

			function selectAllCollections(cbObj){
				var cbStatus = cbObj.checked
				var f = cbObj.form;
				for(var i=0;i<f.length;i++){
					if(f.elements[i].name == "collid[]") f.elements[i].checked = cbStatus;
				}
			}

			function verifyCleanerForm(f){
				if(f.targetkingdom.value == ""){
					alert("<?php echo $LANG['SELECT_KINGDOM']; ?>");
					return false;
				}
				return true;
			}
		</script>
		<script src="../../js/symb/shared.js?ver=1" type="text/javascript"></script>
		<style>
			.top-breathing-room-rel-sm {
				margin-top: 3rem;
			}
			.underlined-text {
				text-decoration: underline;
			}
		</style>
	</head>
	<body>
		<?php
		$displayLeftMenu = (isset($taxa_admin_taxonomycleanerMenu)?$taxa_admin_taxonomycleanerMenu:'true');
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class='navpath'>
			<a href="../../index.php"><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
			<?php
			if($collid && is_numeric($collid)){
				?>
				<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo htmlspecialchars($LANG['COL_MAN_MEN'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
				<a href="index.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo htmlspecialchars($LANG['DATA_CLEAN_MEN'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
				<?php
			}
			else{
				?>
				<a href="../../profile/viewprofile.php?tabindex=1"><?php echo htmlspecialchars($LANG['SPEC_MAN'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
				<?php
			}
			?>
			<b><?php echo $LANG['TAX_NAME_CLEAN']; ?></b>
		</div>
		<!-- inner text block -->
		<div role="main" id="innertext">
			<?php
			$collMap = $cleanManager->getCollMap();
			if($collid){
				if($isEditor){
					?>
					<div style="float:left;font-weight: bold; font-size: 130%; margin-bottom: 10px">
						<?php
						if(is_numeric($collid)){
							echo '<h1 class="page-heading">Taxonomy Cleaning Tool: ' . $collMap[$collid]['collectionname'].' ('.$collMap[$collid]['code'].')</h1>';
						}
						else{
							echo '<h1 class="page-heading">' . $LANG['MULT_CLEAN_TOOL'].' '.'(<a href="#" onclick="$(\'#collDiv\').show()" style="color:blue;text-decoration:underline">' . htmlspecialchars(count($activeCollArr), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . htmlspecialchars($LANG['COLS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>)</h1>';
						}
						?>
					</div>
					<?php
					if(count($collMap) > 1 && $activeCollArr){
						?>
						<div style="float:left;margin-left:5px;"><a href="#" onclick="toggle('mult_coll_fs')"><img src="../../images/add.png" style="width:1em" alt="<?php echo (isset($LANG['ADD_BUTTON']) ? $LANG['ADD_BUTTON'] : 'Add Button') ?>" /></a></div>
						<div style="clear:both">
							<fieldset id="mult_coll_fs" style="display:none;padding: 15px;margin:20px;">
								<legend><b><?php echo $LANG['MULT_COL_SEL']; ?></b></legend>
								<form name="selectcollidform" action="taxonomycleaner.php" method="post" onsubmit="return checkSelectCollidForm(this)">
									<div> <input id="selectall" name="selectall" type="checkbox" onclick="selectAllCollections(this);" /> <label for="selectall"> <?php echo $LANG['SEL_UNSEL_ALL']; ?> </label> </div>
									<?php
									foreach($collMap as $id => $collArr){
										if(in_array($id, $USER_RIGHTS["CollAdmin"])){
											echo '<div>';
											echo '<input id="collid[' . $id . ']" name="collid[' . $id . ']" type="checkbox" value="" />' . '<label for="collid[' . $id . ']"> ' .$id.'" ' . (in_array($id,$activeCollArr) ? 'CHECKED' : '').' </label> ';
											echo $collArr['collectionname'].' ('.$collArr['code'].')';
											echo '</div>';
										}
									}
									?>
									<div style="margin: 15px">
										<button name="submitaction" type="submit" value="EvaluateCollections"><?php echo $LANG['EVAL_COLS']; ?></button>
									</div>
								</form>
								<div>* <?php echo $LANG['ONLY_ADMIN_COLS']; ?></div>
							</fieldset>
						</div>
						<?php
					}
					if(count($activeCollArr) > 1){
						echo '<div id="collDiv" style="display:none;margin:0px 20px;clear:both;">';
						foreach($activeCollArr as $activeCollid){
							echo '<div>'.$collMap[$activeCollid]['collectionname'].' ('.$collMap[$activeCollid]['code'].')</div>';
						}
						echo '</div>';
					}
					?>
					<div style="margin:20px;clear:both;">
						<?php
						if($action){
							if($action == 'deepindex'){
								$cleanManager->deepIndexTaxa();
							}
							elseif($action == 'AnalyzingNames'){
								echo '<ul>';
								$cleanManager->setAutoClean($autoClean);
								$kArr = explode(':',$targetKingdom);
								$cleanManager->setTargetKingdomTid($kArr[0]);
								$cleanManager->setTargetKingdomName($kArr[1]);
								$startIndex = $cleanManager->analyzeTaxa($taxResource, $startIndex, $limit);
								echo '</ul>';
							}
						}
						$badTaxaCount = $cleanManager->getBadTaxaCount();
						$badSpecimenCount = $cleanManager->getBadSpecimenCount();
						?>
					</div>
					<div class="top-breathing-room-rel-sm">
						<section class="fieldset-like">
							<h2> <span> <?php echo (isset($LANG['ACTION_MENU']) ? $LANG['ACTION_MENU'] : 'Action Menu'); ?> </span> </h2>
							<form name="maincleanform" action="taxonomycleaner.php" method="post" onsubmit="return verifyCleanerForm(this)">
								<div style="margin-bottom:15px;">
									<b><?php echo $LANG['SPECS_NOT_INDEXED']; ?></b>
									<div style="margin-left:10px;">
										<?php echo '<span class="underlined-text">'.$LANG['SPECS'].'</span>: '.$badSpecimenCount.'<br/>'; ?>
										<?php echo '<span class="underlined-text">'.$LANG['SCINAMES'].'</span>: '.$badTaxaCount.'<br/>'; ?>
									</div>
								</div>
								<hr/>
								<div style="margin:20px 10px">
									<div style="margin:10px 0px">
										<?php echo $LANG['WILL_RESOLVE_UNINDEXED']; ?>
									</div>
									<div style="margin:10px;">
										<div style="margin-bottom:5px;">
											<fieldset style="padding:15px;margin:10px 0px">
												<legend> <b> <?php echo $LANG['TAX_RESOURCE']; ?> </b> </legend>
												<?php
												$taxResourceList = $cleanManager->getTaxonomicResourceList();
												foreach($taxResourceList as $taKey => $taValue){
													echo '<input name="taxresource[' . $taKey . ']" id="taxresource[' . $taKey . ']" type="checkbox" value="'.$taKey.'" '.(in_array($taKey,$taxResource)?'checked':'').' /> ';
													echo '<label for="taxresource[' . $taKey . ']">' . $taValue . ' </label><br/>';
												}
												?>
											</fieldset>
										</div>
										<div style="margin-bottom:5px;">
											<label for="targetkingdom"> <?php echo $LANG['TARGET_KINGDOM']; ?>: </label>
											<select id="targetkingdom" name="targetkingdom">
												<option value=""><?php echo $LANG['SELECT_TARGET_KING']; ?></option>
												<option value="">--------------------------</option>
												<?php
												$kingdomArr = $cleanManager->getKingdomArr();
												foreach($kingdomArr as $kTid => $kSciname){
													$kingdomValue = $kTid.':'.$kSciname;
													echo '<option value="'.$kingdomValue.'" '.($targetKingdom==$kingdomValue?'selected':'').'>'.$kSciname.'</option>';
												}
												?>
											</select>
										</div>
										<div style="margin-bottom:5px;">
											<label for="limit"> <?php echo $LANG['PROC_PER_RUN']; ?>: </label>
											<input name="limit" id="limit" type="text" value="<?php echo $limit; ?>" style="width:40px" />
										</div>
										<div style="margin-bottom:5px;">
											<label for="startindex"> <?php echo $LANG['START_INDEX']; ?>: </label>
											<input id="startindex" name="startindex" type="text" value="<?php echo $startIndex; ?>" title="Enter a taxon name or letter of the alphabet to indicate where the processing should start" />
										</div>
										<fieldset class="bottom-breathing-room">
											<legend><?php echo $LANG['CLEAN_MAP_FUNCTION']; ?></legend>
											<div style="float:left;margin-left:15px;"><input name="autoclean" id="semi" type="radio" value="0" <?php echo (!$autoClean?'checked':''); ?> /> <label for="semi"> <?php echo $LANG['SEMI_MANUAL']; ?> </label> </div>
											<div style="float:left;margin-left:10px;"><input name="autoclean" id="fully" type="radio" value="1" <?php echo ($autoClean==1?'checked':''); ?> /> <label for="fully"> <?php echo $LANG['FULLY_AUTO']; ?> </label> </div>
										</fieldset>
										<div class="bottom-breathing-room">
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
											<button name="submitaction" type="submit" value="AnalyzingNames" ><?php echo ($startIndex?$LANG['CONTINUE_ANALYZING']:$LANG['ANALYZE_NAMES']); ?></button>
										</div>
									</div>
								</div>
							</form>
							<!--
							<hr/>
							<form name="deepindexform" action="taxonomycleaner.php" method="post">
								<div style="margin:20px 10px">
									<div style="margin:10px 0px">
										<?php echo $LANG['WILL_IMPROVE_LINKAGES']; ?>
									</div>
									<div style="margin:10px">
										<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
										<button name="submitaction" type="submit" value="deepindex"><?php echo $LANG['DEEP_INDEX']; ?></button>
									</div>
								</div>
							</form>
							 -->
						</section>
					</div>
					<?php
				}
				else{
					echo '<div><b>'.$LANG['NO_PERM'].'</b></div>';
				}
			}
			elseif($collMap){
				?>
				<div style="margin:0px 0px 20px 20px;font-weight:bold;font-size:120%;"><?php echo $LANG['BATCH_TAXON_CLEAN']; ?></div>
				<section class="fieldset-like">
					<h2> <span> <?php echo $LANG['COL_SELECTOR']; ?> </span> </h2>
					<form name="selectcollidform" action="taxonomycleaner.php" method="post" onsubmit="return checkSelectCollidForm(this)">
						<div><input name="selectall" type="checkbox" onclick="selectAllCollections(this);" /> <?php echo $LANG['SEL_UNSEL_ALL']; ?></div>
						<?php
						foreach($collMap as $id => $collArr){
							echo '<div>';
							echo '<input name="collid[]" type="checkbox" value="'.$id.'" /> ';
							echo $collArr['collectionname'].' ('.$collArr['code'].')';
							echo '</div>';
						}
						?>
						<div style="margin: 15px">
							<button name="submitaction" type="submit" value="EvaluateCollections"><?php echo $LANG['EVAL_COLS']; ?></button>
						</div>
					</form>
					<div>* <?php echo $LANG['ONLY_ADMIN_COLS']; ?></div>
				</section>
				<?php
			}
			else{
				?>
				<div style='font-weight:bold;font-size:120%;'>
					<?php echo $LANG['ERROR_COLID_NUL']; ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php include($SERVER_ROOT.'/includes/footer.php');?>
	</body>
</html>
