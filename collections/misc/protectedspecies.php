<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceProtectedSpecies.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/misc/protectedspecies.' . $LANG_TAG . '.php')){
	include_once($SERVER_ROOT.'/content/lang/collections/misc/protectedspecies.' . $LANG_TAG . '.php');
}
else{
	include_once($SERVER_ROOT.'/content/lang/collections/misc/protectedspecies.en.php');
}
header('Content-Type: text/html; charset=' . $CHARSET);

$searchTaxon = array_key_exists('searchtaxon', $_REQUEST) ? $_REQUEST['searchtaxon'] : '';
$action = array_key_exists('submitaction', $_REQUEST) ? $_REQUEST['submitaction'] : '';

$isEditor = 0;
if($IS_ADMIN || array_key_exists('RareSppAdmin', $USER_RIGHTS)){
	$isEditor = 1;
}

$rsManager = new OccurrenceProtectedSpecies($isEditor?'write':'readonly');

if($isEditor){
	if($action == 'addspecies'){
		$rsManager->addSpecies($_POST['tidtoadd']);
	}
	elseif($action == 'deletespecies'){
		$rsManager->deleteSpecies($_REQUEST['tidtodel']);
	}
}
$rsManager->setTaxonFilter($searchTaxon);
$rsArr = $rsManager->getProtectedSpeciesList();
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= $CHARSET; ?>">
	<title><?= $LANG['TITLE'] ?></title>
	<link href="<?= $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?= $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?= $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script>
		$(document).ready(function() {
			$("#speciestoadd").autocomplete({ source: "rpc/speciessuggest.php" },{ minLength: 3, autoFocus: true });
			$("#searchtaxon").autocomplete({ source: "rpc/speciessuggest.php" },{ minLength: 3 });
		});

		function navigateToSubmitAction(){
			window.location.href= 'protectedspecies.php?submitaction=checkstats';
		}

		function toggle(target){
		  	var divs = document.getElementsByTagName("div");
		  	for (var i = 0; i < divs.length; i++) {
		  	var divObj = divs[i];
				if(divObj.className == target){
					if(divObj.style.display=="none"){
						divObj.style.display="block";
					}
				 	else {
				 		divObj.style.display="none";
				 	}
				}
			}

		  	var spans = document.getElementsByTagName("span");
		  	for (var h = 0; h < spans.length; h++) {
		  	var spanObj = spans[h];
				if(spanObj.className == target){
					if(spanObj.style.display=="none"){
						spanObj.style.display="inline";
					}
				 	else {
				 		spanObj.style.display="none";
				 	}
				}
			}
		}

		function submitAddSpecies(f){
			var sciName = f.speciestoadd.value;
			if(sciName == ""){
				alert("<?= $LANG['ENTER_SCINAME'] ?>");
				return false;
			}

			$.ajax({
				type: "POST",
				url: "rpc/gettid.php",
				dataType: "json",
				data: { sciname: sciName }
			}).done(function( data ) {
				f.tidtoadd.value = data;
				f.submit();
			}).fail(function(jqXHR){
				alert("<?= $LANG['SCINAME_NOT_EXIST'] ?>");
			});
		}
	</script>
	<style>
		.icon-img{ width:1.5em; border:0px; }
		.message-div{ margin: 10px; font-weight: bold; }
	</style>
</head>
<body>
<?php
include($SERVER_ROOT.'/includes/header.php');
?>
<div class='navpath'>
	<a href='../index.php'><?= $LANG['HOME'] ?></a> &gt;&gt;
	<b><?= $LANG['SENSITIVE_TAXA'] ?></b>
</div>
<div role="main" id="innertext">
	<?php
	if($isEditor){
		?>
		<div style="float:right;" title="<?= $LANG['TOGGLE_EDIT'] ?>">
			<a href="#" onclick="toggle('editobj');" ><?= $LANG['EDIT'] ?> <img class="icon-img" src="../../images/edit.png" alt="<?= $LANG['PENCIL_ICON'] ?>"></a>
		</div>
		<?php
	}
	?>
	<h1 class="page-heading"><?= $LANG['PROTECTED_SPECIES'] ?></h1>
	<div style="float:right;">
		<form name="searchform" action="protectedspecies.php" method="post">
			<fieldset style="margin:0px 15px;padding:10px">
				<legend><?= $LANG['FILTER'] ?></legend>
				<div style="margin:3px">
					<label for="searchtaxon"><?= $LANG['TAXON_SEARCH'] ?>:</label>
					<input id="searchtaxon" name="searchtaxon" type="text" value="<?= htmlspecialchars($searchTaxon, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>" />
				</div>
				<div style="margin:3px">
					<button name="submitaction" type="submit" value="searchTaxonSubmit" ><?= $LANG['SEARCH']; ?></button>
				</div>
			</fieldset>
		</form>
	</div>
	<div class="bottom-breathing-room">
		<?= $LANG['DESCRIPTION'] ?>
	</div>
	<div>
		<?php
		$occurCnt = $rsManager->getSpecimenCnt();
		if($occurCnt) echo '<div class="bottom-breathing-room">' . $LANG['OCCURRENCES_PROTECTED'] . ': '.number_format($occurCnt).'</div>';
		if($isEditor){
			if($action == 'checkstats'){
				echo '<div>' . $LANG['NUMBER_AFFECTED'] . ': '.$rsManager->protectGlobalSpecies().'</div>';
			}
			else{
				echo "<div><button type=\"button\" onclick=\"navigateToSubmitAction()\">" . $LANG['VERIFY_PROTECTIONS'] . "</button></div>";
			}
		}
		?>
	</div>
	<div style="clear:both">
		<section class="fieldset-like">
			<h2><span><?= $LANG['GLOBAL_PROTECTIONS'] ?></span></h2>
			<br/>
			<?php
			if($isEditor){
				?>
				<div class="editobj" style="display:none;width:400px;margin-bottom:20px">
					<form name="addspeciesform" action='protectedspecies.php' method='post' >
						<fieldset style='margin:5px'>
							<legend><b><?= $LANG['ADD_TAXON'] ?></b></legend>
							<div style="margin:3px;">
								<label for="speciestoadd"><?= $LANG['SCIENTIFIC_NAME'] ?>:</label>
								<input type="text" id="speciestoadd" name="speciestoadd" style="width:300px" />
								<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
							</div>
							<div style="margin:3px;">
								<input type="hidden" name="submitaction" value="addspecies" />
								<button name="addSpeciesbutton" type="button" onclick="submitAddSpecies(this.form)" ><?= $LANG['ADD_SPECIES'] ?></button>
							</div>
						</fieldset>
					</form>
				</div>
				<?php
			}
			if($rsArr){
				foreach($rsArr as $family => $speciesArr){
					?>
					<h2>
						<span>
							<?= $family ?>
						</span>
					</h2>
					<div style='margin-left:20px; margin-bottom:20px;'>
						<?php
						foreach($speciesArr as $tid => $nameArr){
							echo '<div id="tid-'.$tid.'">';
							echo '<a href="../../taxa/index.php?taxon=' . $tid . '" target="_blank">';
							echo '<i>' . htmlspecialchars($nameArr['sciname'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</i> ';
							echo htmlspecialchars($nameArr['author'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> ';
							if($isEditor){
								?>
								<span class="editobj" style="display:none;">
									<a href="protectedspecies.php?submitaction=deletespecies&tidtodel=<?= $tid ?>">
										<img class="icon-img" src="../../images/del.png" title="<?= $LANG['REMOVE_SPECIES'] ?>" />
									</a>
								</span>
								<?php
							}
							echo '</div>';
						}
						?>
					</div>
					<?php
				}
			}
			else{
				?>
				<div class="message-div"><?= $LANG['NO_TAXA'] ?></div>
				<?php
			}
			?>
		</section>
		<section class="fieldset-like">
			<h2><span><?= $LANG['STATE_PROTECTIONS'] ?></span></h2>
			<?php
			$stateList = $rsManager->getStateList();
			$emptyList = true;
			foreach($stateList as $clid => $stateArr){
				if($isEditor || $stateArr['access'] == 'public'){
					echo '<div>';
					echo '<a href="../../checklists/checklist.php?clid=' . $clid . '">';
					echo $stateArr['locality'].': '.$stateArr['name'];
					echo '</a>';
					if(strpos($stateArr['access'] ?? '', 'private') !== false) echo ' (' . $LANG['PRIVATE'] . ')';
					echo '</div>';
					$emptyList = false;
				}
			}
			if($emptyList){
				?>
				<div class="message-div"><?= $LANG['NO_CHECKLIST'] ?></div>
				<?php
			}
			?>
		</section>
	</div>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php')
?>
</body>
</html>
