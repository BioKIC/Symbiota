<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/TaxonomyDisplayManager.php');
header('Content-Type: text/html; charset=' . $CHARSET);
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomydisplay.'.$LANG_TAG.'.php'))
	include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomydisplay.'.$LANG_TAG.'.php');
	else include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomydisplay.en.php');

$target = $_REQUEST['target'] ?? '';
$displayAuthor = !empty($_REQUEST['displayauthor']) ? 1: 0;
$matchOnWords = !empty($_POST['matchonwords']) ? 1 : 0;
$displayFullTree = !empty($_REQUEST['displayfulltree']) ? 1 : 0;
$displaySubGenera = !empty($_REQUEST['displaysubgenera']) ? 1 : 0;
$limitToOccurrences = !empty($_REQUEST['limittooccurrences']) ? 1 : 0;
$taxAuthId = array_key_exists('taxauthid', $_REQUEST) ? filter_var($_REQUEST['taxauthid'], FILTER_SANITIZE_NUMBER_INT) : 1;
$statusStr = array_key_exists('statusstr', $_REQUEST) ? $_REQUEST['statusstr'] : '';
$submitAction = array_key_exists('tdsubmit', $_POST) ? $_POST['tdsubmit'] : '';

if(!$target) $matchOnWords = 1;
$taxonDisplayObj = new TaxonomyDisplayManager();
$taxonDisplayObj->setTargetStr($target);
$taxonDisplayObj->setTaxAuthId($taxAuthId);
$taxonDisplayObj->setDisplayAuthor($displayAuthor);
$taxonDisplayObj->setMatchOnWholeWords($matchOnWords);
$taxonDisplayObj->setDisplayFullTree($displayFullTree);
$taxonDisplayObj->setDisplaySubGenera($displaySubGenera);
$taxonDisplayObj->setLimitToOccurrences($limitToOccurrences);

if($submitAction){
	if($submitAction == 'exportTaxonTree'){
		$taxonDisplayObj->exportCsv();
		exit;
	}
}

$isEditor = false;
if($IS_ADMIN || array_key_exists('Taxonomy', $USER_RIGHTS)){
	$isEditor = true;
}
?>
<!DOCTYPE html>
<html lang="<?= $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['TAX_DISPLAY'] . ': ' . $taxonDisplayObj->getTargetStr(); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="<?= $CSS_BASE_PATH ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$("#taxontarget").autocomplete({
				source: function( request, response ) {
					$.getJSON( "rpc/gettaxasuggest.php", { term: request.term, taid: document.tdform.taxauthid.value }, response );
				},
				autoFocus: true,
				minLength: 3 }
			);
		});

		function displayTaxomonyMeta(){
			$("#taxDetailDiv").hide();
			$("#taxMetaDiv").show();
		}
	</script>
	<style>
		label{ font-weight: bold; }
		.field-div{ margin:3px 0px }
		.icon-image{ border: 0px; width: 15px; }
	</style>
</head>
<body>
	<?php
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php"><?= $LANG['HOME'] ?></a> &gt;&gt;
		<a href="taxonomydisplay.php"><b><?= $LANG['TAX_TREE_VIEWER'] ?></b></a>
	</div>
	<div role="main" id="innertext">
		<h1 class="page-heading">Central Taxonomic Thesaurus</h1>
		<?php
		if($statusStr){
			$statusStr = str_replace(';', '<br/>', htmlspecialchars($statusStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE));
			?>
			<hr/>
			<div style="color:<?php echo (stripos($statusStr,'SUCCESS') !== false?'green':'red'); ?>;margin:15px;">
				<?= $statusStr; ?>
			</div>
			<hr/>
			<?php
		}
		if($isEditor){
			?>
			<div style="float:right;" title="<?= $LANG['ADD_NEW_TAXON'] ?>">
				<a href="taxonomyloader.php">
					<img class="icon-image" src='../../images/add.png' alt="Plus sign">
				</a>
			</div>
			<?php
		}
		?>
		<div>
			<?php
			$taxMetaArr = $taxonDisplayObj->getTaxonomyMeta();
			if(count($taxMetaArr) > 1){
				//echo '<div id="taxDetailDiv" style="margin-top:15px;margin-left:5px;float:left;font-size:80%"><a href="#" onclick="displayTaxomonyMeta()">(more details)</a></div>';
				echo '<div id="taxMetaDiv" style="margin:10px 15px 35px 15px;display:none;clear:both;">';
				if(isset($taxMetaArr['description'])) echo '<div class="field-div"><label>' . $LANG['DESCRIPTION'] . '</label>" '.$taxMetaArr['description'].'</div>';
				if(isset($taxMetaArr['editors'])) echo '<div class="field-div"><label>' . $LANG['EDITORS'] . ':</label> ' . $taxMetaArr['editors'] . '</div>';
				if(isset($taxMetaArr['contact'])) echo '<div class="field-div"><label>' . $LANG['CONTACT'] . ':</label> ' . $taxMetaArr['contact'] . '</div>';
				if(isset($taxMetaArr['email'])) echo '<div class="field-div"><label>' . $LANG['EMAIL'] . ':</label> ' . $taxMetaArr['email'] . '</div>';
				if(isset($taxMetaArr['url'])) echo '<div class="field-div"><label>URL:</label> <a href="' . $taxMetaArr['url'] . '" target="_blank">' . $taxMetaArr['url'] . '</a></div>';
				if(isset($taxMetaArr['notes'])) echo '<div class="field-div"><label>' . $LANG['NOTES'] . ':</label> ' . $taxMetaArr['notes'] . '</div>';
				echo '</div>';
			}
			?>
		</div>
		<div style="clear:both;">
			<form id="tdform" name="tdform" action="taxonomydisplay.php" method='POST'>
				<fieldset style="padding:10px;max-width:850px;" class="flex-form">
					<legend><b><?= $LANG['TAX_SEARCH'] ?></b></legend>
					<div>
						<label for="taxontarget"> <?= $LANG['TAXON'] ?>: </label>
						<input id="taxontarget" class="search-bar" name="target" type="text" value="<?= $taxonDisplayObj->getTargetStr(); ?>" />

						<div>
							<input id="displayauthor" name="displayauthor" type="checkbox" value="1" <?= ($displayAuthor ? 'checked' : '') ?> />
							<label for="displayauthor" > <?= $LANG['DISP_AUTHORS']; ?> </label>
						</div>
						<div>
							<input id="matchonwords" name="matchonwords" type="checkbox" value="1" <?= ($matchOnWords ? 'checked' : '') ?> />
							<label for="matchonwords" > <?= $LANG['MATCH_WHOLE_WORDS'] ?> </label>
						</div>
						<div>
							<input id="displayfulltree" name="displayfulltree" type="checkbox" value="1" <?= ($displayFullTree ? 'checked' : '') ?> />
							<label for="displayfulltree" > <?= $LANG['DISP_FULL_TREE'] ?> </label>
						</div>
						<div>
							<input id="displaysubgenera" name="displaysubgenera" type="checkbox" value="1" <?= ($displaySubGenera ? 'checked' : '') ?> />
							<label for="displaysubgenera"> <?= $LANG['DISP_SUBGENERA'] ?> </label>
						</div>
						<div>
							<input id="limittooccurrences" name="limittooccurrences" type="checkbox" value="1" <?= ($limitToOccurrences ? 'checked' : '') ?> />
							<label for="limittooccurrences"> <?= $LANG['LIMIT_TO_OCCURRENCES'] ?> </label>
						</div>
					</div>
					<div class="flex-form" style="margin: 10px">
						<div style="float: right">
							<button name="tdsubmit" type="submit" value="exportTaxonTree"><?= $LANG['EXPORT_TREE'] ?></button>
						</div>
						<div>
							<button name="tdsubmit" type="submit" value="displayTaxonTree"><?= $LANG['DISP_TAX_TREE'] ?></button>
							<input name="taxauthid" type="hidden" value="<?= $taxAuthId; ?>" />
						</div>
					</div>
				</fieldset>
			</form>
		</div>
		<?php
		if(!$taxonDisplayObj->displayTaxonomyHierarchy()){
			echo '<div style="margin:20px;">' . $LANG['NO_TAXA_FOUND'] . '</div>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
