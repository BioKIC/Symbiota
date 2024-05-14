<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyHarvester.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomymaintenance.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/taxa/taxonomy/taxonomymaintenance.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomymaintenance.en.php');

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../taxa/taxonomy/taxonomymaintenance.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

$harvesterManager = new TaxonomyHarvester();

$isEditor = false;
if($IS_ADMIN || array_key_exists("Taxonomy",$USER_RIGHTS)) $isEditor = true;

if($isEditor){
	if($action == 'buildenumtree'){
		if($harvesterManager->buildHierarchyEnumTree()){
			$statusStr = $LANG['SUCCESS_TAX_INDEX'];
		}
		else{
			$statusStr = $LANG['ERROR_TAX_INDEX'] . ': ' . $harvesterManager->getErrorMessage();
		}
	}
	elseif($action == 'rebuildenumtree'){
		if($harvesterManager->rebuildHierarchyEnumTree()){
			$statusStr = $LANG['SUCCESS_TAX_INDEX'];
		}
		else{
			$statusStr = $LANG['ERROR_TAX_INDEX'] . ': ' . $harvesterManager->getErrorMessage();
		}
	}
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . " " . $LANG['TAX_MAINT']; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($taxa_admin_taxonomydisplayMenu)?$taxa_admin_taxonomydisplayMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($taxa_admin_taxonomydisplayCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>" . $LANG['HOME'] ."</a> &gt; ";
		echo $taxa_admin_taxonomydisplayCrumbs;
		echo " <b>" . $LANG['TAX_TREE_VIEW'] . "</b>";
		echo "</div>";
	}
	if(isset($taxa_admin_taxonomydisplayCrumbs)){
		if($taxa_admin_taxonomydisplayCrumbs){
			echo '<div class="navpath">';
			echo $taxa_admin_taxonomydisplayCrumbs;
			echo ' <b>' . $LANG['TAX_TREE_VIEW'] . '</b>';
			echo '</div>';
		}
	}
	else{
		?>
		<div class="navpath">
			<a href="../../index.php"><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
			<a href="taxonomydisplay.php"><b><?php echo htmlspecialchars($LANG['TAX_TREE_VIEW'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></b></a>
		</div>
		<?php
	}
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['TAX_MAINT']; ?></h1>
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="color:<?php echo (strpos($statusStr,'SUCCESS') !== false?'green':'red'); ?>;margin:15px;">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php
		}
		if($isEditor){
			?>


			<?php
		}
		else{
			?>
			<div style="margin:30px;font-weight:bold;font-size:120%;">
				<?php echo $LANG['NOT_AUTH']; ?>
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