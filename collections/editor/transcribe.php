<?php
include_once('../../config/symbini.php');
// TODO: double check what is this file for
include_once($SERVER_ROOT.'/classes/OccurrenceEditorDeterminations.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/transcribe.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/transcribe.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/transcribe.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/transcribe.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = $_REQUEST["collid"];
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

if(!is_numeric($collid)) $collid = 0;

$occManager = new OccurrenceEditorDeterminations();
$occManager->setCollId($collid);
$collMap = $occManager->getCollMap();

if($collId && isset($collMap['collid']) && $collId != $collMap['collid']){
	$collId = $collMap['collid'];
	$occManager->setCollId($collId);
}
if($collMap){
	if($collMap['colltype']=='General Observations'){
		$isGenObs = 1;
		$collType = 'obs';
	}
	elseif($collMap['colltype']=='Observations'){
		$collType = 'obs';
	}
	$propArr = $occManager->getDynamicPropertiesArr();
	if(isset($propArr['modules-panel'])){
		foreach($propArr['modules-panel'] as $module){
			if(isset($module['paleo']['status']) && $module['paleo']['status']) $moduleActivation[] = 'paleo';
			elseif(isset($module['matSample']['status']) && $module['matSample']['status']){
				$moduleActivation[] = 'matSample';
				if($tabTarget > 3) $tabTarget++;
			}
		}
	}
}

$isEditor = 0;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	$isEditor = 1;
}
elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
	$isEditor = 1;
}
$statusStr = '';
?>

<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE.$LANG['IMAGE_BATCH']; ?></title>
		<?php
		$activateJQuery = true;
		if(file_exists($SERVER_ROOT.'/includes/head.php')){
			include_once($SERVER_ROOT.'/includes/head.php');
		}
		else{
			echo '<link href="'.$CLIENT_ROOT.'/css/jquery-ui.css" type="text/css" rel="stylesheet" />';
			echo '<link href="'.$CLIENT_ROOT.'/css/base.css?ver=1" type="text/css" rel="stylesheet" />';
			echo '<link href="'.$CLIENT_ROOT.'/css/main.css?ver=1" type="text/css" rel="stylesheet" />';
		}
		?>
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script type="text/javascript">
			function initScinameAutocomplete(f){
				$( f.sciname ).autocomplete({
					source: "rpc/getspeciessuggest.php",
					minLength: 3,
					change: function(event, ui) {
					}
				});
			}

            // TODO: update this batch form accordingly
			function submitBatchForm(f){
				var workingObj = document.getElementById("workingcircle");
				workingObj.style.display = "inline"
				var allCatNum = 0;
				if(f.allcatnum.checked) allCatNum = 1;

				$.ajax({
					type: "POST",
					url: "rpc/getnewdetitem.php",
					dataType: "json",
					data: {
						catalognumber: f.catalognumber.value,
						allcatnum: allCatNum,
						sciname: f.sciname.value,
						collid: f.collid.value
					}
				}).done(function( retStr ) {
					if(retStr != ""){
						for (var occid in retStr) {
							var occObj = retStr[occid];
							if(f.catalognumber.value && checkCatalogNumber(occid, occObj["cn"])){
								alert("<?php echo $LANG['RECORD_EXISTS']; ?>");
							}
							else{
								var trNode = createNewTableRow(occid, occObj);
								var tableBody = document.getElementById("catrecordstbody");
								tableBody.insertBefore(trNode, tableBody.firstElementChild);
							}
						}
						document.getElementById("accrecordlistdviv").style.display = "block";
					}
					else{
						alert("<?php echo $LANG['NO_RECORDS']; ?>");
					}
				});

				if(f.catalognumber.value != ""){
					f.catalognumber.value = '';
					f.catalognumber.focus();
				}
				workingObj.style.display = "none";
				return false;
			}
			
		</script>
	</head>
	<body>
	<?php
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'><?php echo $LANG['HOME']; ?></a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1"><?php echo $LANG['COLL_MANAGE']; ?></a> &gt;&gt;
		<b><?php echo $LANG['BATCH_DETERS']; ?></b>
	</div>
	<h1><?php //echo($collid) ?></h1>
	<h1><?php //echo($collType) ?></h1>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($isEditor){
			echo '<h2>'.$occManager->getCollName().'</h2>';
			?>
			<div style="margin:0px;">
				<fieldset style="padding:10px;">
					<legend><b><?php echo $LANG['TRANSCRIBE_INTO_SPECIFY']; ?></b></legend>
					<div style="margin:15px;width:700px;">
                        <!-- TODO: update the submit function of the form -->
						<form name="batchform" action="transcribe.php" method="post" onsubmit="return submitBatchForm(this);">
                            <div style="margin-bottom:15px;">
                                <!-- TODO: figure out what is this line is, then customized the content -->
                                <!-- TODO: onclick function of the buttons -->
                                <b>Batch: <?php echo('[hmerchant/202209-08]') ?></b>
                                <button type="button"><?php echo $LANG['START_FROM']; ?> first.</button>
                                <!-- TODO: need to customize the page number -->
                                <button type="button"><?php echo $LANG['START_FROM']; ?> 188</button>
                            </div>
							<div>
								<b><?php echo $LANG['WORK_ON_BATCH']; ?></b>
								<input type="text" id="batch" name="batch" style="width:400px;" />
							</div>
						</form>
					</div>
				</fieldset>
				<!-- TODO: need to figure out what this status is -->
				<fieldset>
					<div>
						<p style="margin:0px;"><?php echo $LANG['STATUS']; ?></p>
					</div>
				</fieldset>
				<fieldset>
					<div>
						<b style="margin:0px;">
							<?php if(array_key_exists('recordenteredby',$collArr)){
									echo ($collArr['recordenteredby']?$collArr['recordenteredby']:$LANG['NO_RECORDS']);
								}
								if(isset($collArr['dateentered']) && $collArr['dateentered']) echo ' ['.$collArr['dateentered'].']'; 
								?>
							<?php echo $jumpStr; ?>
						</b>
					</div>
				</fieldset>
			</div>
			<?php
		}
		else{
			?>
			<div style="font-weight:bold;margin:20px;font-weight:150%;">
				<?php echo $LANG['NO_PERMISSIONS']; ?>
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