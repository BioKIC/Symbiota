<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyHarvester.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../taxa/taxonomy/quickload.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$loadManager = new TaxonomyHarvester();

$sciname = $_REQUEST['sciname'] ?? '';
$author = $_REQUEST['author'] ?? '';
$kingdom = $loadManager->cleanOutStr($_REQUEST['kingdom']) ?? '';
$submitAction = $_POST['submitaction'] ?? '';

$isEditor = false;
if($IS_ADMIN || array_key_exists('Taxonomy',$USER_RIGHTS)){
	$isEditor = true;
}

$status = '';
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Taxon Loader: </title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		function verifyLoadForm(f){
			if(f.sciname.value == ""){
				alert("Taxon name must have an value");
				return false;
			}
			return true;
		}

	</script>
</head>
<body>
<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php">Home</a> &gt;&gt;
		<a href="taxonomydisplay.php">Taxonomy Tree Viewer</a> &gt;&gt;
		<b>Taxonomy Loader</b>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Taxon Loader</h1>
		<?php
		if($status){
			echo '<div style="margin:20px;">'.$status.'</div>';
		}
		if($isEditor){
			if($submitAction){
				echo '<fieldset><legend>Action Panel</legend>';
				if($submitAction == 'submitName'){
					$loadManager->setVerboseMode(2);
					$loadManager->setDefaultAuthor($author);
					$kTid = substr($kingdom,0,strpos($kingdom,':'));
					$kName = substr($kingdom,strpos($kingdom,':')+1);
					$loadManager->setKingdomTid($kTid);
					$loadManager->setKingdomName($kName);
					$loadManager->setTaxonomicResources(array('col'));
					$tid = $loadManager->processSciname($sciname);
				}
				echo '</fieldset>';
			}
			?>
			<form id="loaderform" name="loaderform" action="quickload.php" method="post" onsubmit="return verifyLoadForm(this)">
				<fieldset>
					<legend><b>Add New Taxon</b></legend>
					<div>
						<div style="float:left;width:170px;">Taxon Name:</div>
						<input type="text" id="sciname" name="sciname" style="width:300px;border:inset;" value="<?php echo $loadManager->cleanOutStr($sciname); ?>" />
					</div>
					<div>
						<div style="float:left;width:170px;">Author:</div>
						<input type='text' id='author' name='author' style='width:300px;border:inset;' value="<?php echo $loadManager->cleanOutStr($author); ?>" />
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;">Kingdom:</div>
						<select id="rankid" name="kingdom" style="border:inset;">
							<option value="">Select Kingdom</option>
							<option value="">--------------------------------</option>
							<?php
							$defaultKingdom = $loadManager->getDefaultKingdom();
							$kindomArr = $loadManager->getKingdomArr();
							foreach($kindomArr as $kingdomTid => $kingdomName){
								$id = $kingdomTid.':'.$kingdomName;
								echo '<option value="'.$id.'" '.($kingdomTid==$defaultKingdom['tid']?' SELECTED':'').'>'.$kingdomName.'</option>';
							}
							?>
						</select>
					</div>
					<div style="clear:both;">
						<button type="submit" name="submitaction" value="submitName">Submit New Name</button>
					</div>
				</fieldset>
			</form>
			<?php
		}
		else{
			?>
			<div style="margin:30px;font-weight:bold;font-size:120%;">
				You do not have permission to access this page. Please contact the portal manager.
			</div>
			<?php
		}
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</div>
</body>
</html>